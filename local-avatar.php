<?php
/**
 * Plugin Name: Local User Avatar
 * Description: Adds an avatar field to user profiles and replaces Gravatar with the locally uploaded image.
 * Version: 1.0.2
 * Author: Henrik Hansen
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Local_User_Avatar
{
    const META_AVATAR_ID = 'local_user_avatar_id';
    const META_AVATAR_URL = 'local_user_avatar_url';

    public function __construct()
    {
        add_action('show_user_profile', [$this, 'render_field']);
        add_action('edit_user_profile', [$this, 'render_field']);

        add_action('personal_options_update', [$this, 'save_field']);
        add_action('edit_user_profile_update', [$this, 'save_field']);

        add_filter('get_avatar', [$this, 'filter_get_avatar'], 10, 6);
        add_filter('get_avatar_data', [$this, 'filter_get_avatar_data'], 10, 2);
        add_filter('kses_allowed_protocols', [$this, 'allow_data_protocol']);

        add_action('admin_head-profile.php', [$this, 'hide_gravatar_section']);
        add_action('admin_head-user-edit.php', [$this, 'hide_gravatar_section']);

        add_filter('user_has_cap', [$this, 'grant_upload_for_abonennten'], 10, 4);
        add_action('admin_menu', [$this, 'remove_media_menu_for_abonent'], 999);
        add_action('admin_init', [$this, 'block_media_library_for_abonent']);
        add_filter('ajax_query_attachments_args', [$this, 'limit_media_library_for_abonent']);
        add_action('wp_ajax_vv_avatar_crop', [$this, 'handle_avatar_crop']);
    }

    public function render_field($user): void
    {
        if (!$this->can_manage_avatar($user->ID)) {
            return;
        }

        wp_enqueue_media();

        $avatar_id = (int) get_user_meta($user->ID, self::META_AVATAR_ID, true);
        $avatar_url = (string) get_user_meta($user->ID, self::META_AVATAR_URL, true);

        if ($avatar_id) {
            $image_src = wp_get_attachment_image_src($avatar_id, 'thumbnail');
            if (!empty($image_src[0])) {
                $avatar_url = $image_src[0];
            }
        }
        ?>
        <h2><?php esc_html_e('Avatar', 'local-user-avatar'); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th><label for="local-user-avatar"><?php esc_html_e('Profil Foto', 'local-user-avatar'); ?></label></th>
                <td>
                    <div id="local-user-avatar-preview" style="margin-bottom:10px;">
                        <?php if ($avatar_url): ?>
                            <img src="<?php echo esc_url($avatar_url); ?>" alt=""
                                style="width:96px;height:96px;border-radius:50%;object-fit:cover;" />
                        <?php else: ?>
                            <span style="display:inline-block;width:96px;height:96px;border-radius:50%;background:#f0f0f0;"></span>
                        <?php endif; ?>
                    </div>

                    <input type="hidden" name="local_user_avatar_id" id="local-user-avatar-id"
                        value="<?php echo esc_attr($avatar_id); ?>" />
                    <input type="hidden" name="local_user_avatar_url" id="local-user-avatar-url"
                        value="<?php echo esc_url($avatar_url); ?>" />

                    <button type="button" class="button" id="local-user-avatar-upload">
                        <?php esc_html_e('Select or Upload Avatar', 'local-user-avatar'); ?>
                    </button>
                    <button type="button" class="button" id="local-user-avatar-remove" style="margin-left:6px;">
                        <?php esc_html_e('Remove Avatar', 'local-user-avatar'); ?>
                    </button>

                    <?php wp_nonce_field('local_user_avatar_update', 'local_user_avatar_nonce'); ?>
                </td>
            </tr>
        </table>

        <script>
            (function ($) {
                var frame;
                var $preview = $('#local-user-avatar-preview');
                var $id = $('#local-user-avatar-id');
                var $url = $('#local-user-avatar-url');
                var cropNonce = '<?php echo esc_js(wp_create_nonce('vv_avatar_crop')); ?>';

                function setAvatar(url, id) {
                    $id.val(id || '');
                    $url.val(url || '');
                    $preview.html('<img src="' + url + '" alt="" style="width:96px;height:96px;border-radius:50%;object-fit:cover;" />');
                }

                function openCropper(attachment, fallbackUrl) {
                    try {
                        var Cropper = wp.media.controller.Cropper.extend({
                            doCrop: function (attachmentModel) {
                                return wp.ajax.post('vv_avatar_crop', {
                                    id: attachmentModel.get('id'),
                                    cropDetails: attachmentModel.get('cropDetails'),
                                    nonce: cropNonce
                                });
                            }
                        });

                        var cropFrame = wp.media({
                            frame: 'crop',
                            state: 'cropper'
                        });

                        cropFrame.states.add([new Cropper({
                            selection: new Backbone.Collection([attachment]),
                            imgSelectOptions: function () {
                                return { aspectRatio: '1:1' };
                            }
                        })]);

                        cropFrame.on('cropped', function (response) {
                            if (response && response.url) {
                                setAvatar(response.url, attachment.get('id'));
                            } else if (fallbackUrl) {
                                setAvatar(fallbackUrl, attachment.get('id'));
                            }
                        });

                        cropFrame.on('skippedcrop', function () {
                            if (fallbackUrl) {
                                setAvatar(fallbackUrl, attachment.get('id'));
                            }
                        });

                        cropFrame.open();
                    } catch (e) {
                        if (fallbackUrl) {
                            setAvatar(fallbackUrl, attachment.get('id'));
                        }
                    }
                }

                $('#local-user-avatar-upload').on('click', function (e) {
                    e.preventDefault();

                    if (frame) {
                        frame.open();
                        return;
                    }

                    frame = wp.media({
                        title: '<?php echo esc_js(__('Select an avatar', 'local-user-avatar')); ?>',
                        button: { text: '<?php echo esc_js(__('Use this avatar', 'local-user-avatar')); ?>' },
                        multiple: false,
                        library: { type: 'image' }
                    });

                    frame.on('select', function () {
                        var attachment = frame.state().get('selection').first();
                        var data = attachment.toJSON();
                        var imgUrl = data.sizes && data.sizes.thumbnail ? data.sizes.thumbnail.url : data.url;

                        if (data.width && data.height && data.width !== data.height) {
                            var model = wp.media.attachment(data.id);
                            model.fetch();
                            openCropper(model, imgUrl);
                            return;
                        }

                        setAvatar(imgUrl, data.id || '');
                    });

                    frame.open();
                });

                $('#local-user-avatar-remove').on('click', function (e) {
                    e.preventDefault();
                    $id.val('');
                    $url.val('');
                    $preview.html('<span style="display:inline-block;width:96px;height:96px;border-radius:50%;background:#f0f0f0;"></span>');
                });
            })(jQuery);
        </script>
        <?php
    }

    public function save_field(int $user_id): void
    {
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }

        if (empty($_POST['local_user_avatar_nonce']) || !wp_verify_nonce($_POST['local_user_avatar_nonce'], 'local_user_avatar_update')) {
            return;
        }

        $avatar_id = isset($_POST['local_user_avatar_id']) ? (int) $_POST['local_user_avatar_id'] : 0;
        $avatar_url = isset($_POST['local_user_avatar_url']) ? esc_url_raw($_POST['local_user_avatar_url']) : '';

        if ($avatar_id) {
            update_user_meta($user_id, self::META_AVATAR_ID, $avatar_id);
            if ($avatar_url) {
                update_user_meta($user_id, self::META_AVATAR_URL, $avatar_url);
            } else {
                $cropped_url = $this->create_square_avatar($avatar_id, $user_id);
                if ($cropped_url) {
                    update_user_meta($user_id, self::META_AVATAR_URL, $cropped_url);
                } else {
                    delete_user_meta($user_id, self::META_AVATAR_URL);
                }
            }
        } elseif ($avatar_url) {
            update_user_meta($user_id, self::META_AVATAR_URL, $avatar_url);
            delete_user_meta($user_id, self::META_AVATAR_ID);
        } else {
            delete_user_meta($user_id, self::META_AVATAR_ID);
            delete_user_meta($user_id, self::META_AVATAR_URL);
        }
    }

    public function handle_avatar_crop(): void
    {
        if (!current_user_can('edit_user', get_current_user_id())) {
            wp_send_json_error();
        }

        check_ajax_referer('vv_avatar_crop', 'nonce');

        $attachment_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $crop = isset($_POST['cropDetails']) ? (array) $_POST['cropDetails'] : [];

        $x = isset($crop['x1']) ? (int) $crop['x1'] : 0;
        $y = isset($crop['y1']) ? (int) $crop['y1'] : 0;
        $w = isset($crop['width']) ? (int) $crop['width'] : 0;
        $h = isset($crop['height']) ? (int) $crop['height'] : 0;

        if (!$attachment_id || $w <= 0 || $h <= 0) {
            wp_send_json_error();
        }

        $file_path = get_attached_file($attachment_id);
        if (!$file_path || !file_exists($file_path)) {
            wp_send_json_error();
        }

        $cropped = wp_crop_image($file_path, $x, $y, $w, $h, 300, 300);
        if (is_wp_error($cropped) || !$cropped) {
            wp_send_json_error();
        }

        $uploads = wp_upload_dir();
        if (empty($uploads['basedir']) || empty($uploads['baseurl'])) {
            wp_send_json_error();
        }

        $url = str_replace($uploads['basedir'], $uploads['baseurl'], $cropped);
        wp_send_json_success(['url' => $url]);
    }

    private function create_square_avatar(int $attachment_id, int $user_id): string
    {
        $file_path = get_attached_file($attachment_id);
        if (!$file_path || !file_exists($file_path)) {
            return '';
        }

        $editor = wp_get_image_editor($file_path);
        if (is_wp_error($editor)) {
            return '';
        }

        $size = $editor->get_size();
        if (empty($size['width']) || empty($size['height'])) {
            return '';
        }

        $min = min($size['width'], $size['height']);
        $x = (int) floor(($size['width'] - $min) / 2);
        $y = (int) floor(($size['height'] - $min) / 2);
        $editor->crop($x, $y, $min, $min, 300, 300, false);

        $uploads = wp_upload_dir();
        if (empty($uploads['path']) || empty($uploads['url'])) {
            return '';
        }

        $filename = wp_unique_filename($uploads['path'], 'avatar-' . $user_id . '.jpg');
        $dest_path = trailingslashit($uploads['path']) . $filename;

        $saved = $editor->save($dest_path, 'image/jpeg');
        if (is_wp_error($saved) || empty($saved['path'])) {
            return '';
        }

        return trailingslashit($uploads['url']) . $filename;
    }

    private function can_manage_avatar(int $user_id): bool
    {
        if (current_user_can('upload_files')) {
            return true;
        }

        $current_id = get_current_user_id();
        if ($current_id && $current_id === $user_id && $this->is_abonent_user($current_id)) {
            return true;
        }

        return false;
    }

    private function is_abonent_user(int $user_id): bool
    {
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        foreach ((array) $user->roles as $role) {
            if ($role === 'subscriber') {
                return true;
            }
            if ($role === 'abonennten' || $role === 'abonnenten' || $role === 'abonenten') {
                return true;
            }
            if (stripos($role, 'abon') !== false) {
                return true;
            }
        }

        return false;
    }

    public function grant_upload_for_abonennten(array $allcaps, array $caps, array $args, $user): array
    {
        if (!is_admin()) {
            return $allcaps;
        }

        if (!$user || !($user instanceof WP_User)) {
            return $allcaps;
        }

        if (!empty($allcaps['upload_files'])) {
            return $allcaps;
        }

        if (!$this->is_abonent_user((int) $user->ID)) {
            return $allcaps;
        }

        $pagenow = $GLOBALS['pagenow'] ?? '';
        $allowed_pages = ['profile.php', 'user-edit.php', 'async-upload.php', 'media-upload.php', 'admin-ajax.php'];
        if (!in_array($pagenow, $allowed_pages, true)) {
            return $allcaps;
        }

        $target_id = isset($_REQUEST['user_id']) ? (int) $_REQUEST['user_id'] : (int) $user->ID;
        if ($target_id !== (int) $user->ID) {
            return $allcaps;
        }

        $allcaps['upload_files'] = true;
        return $allcaps;
    }

    public function remove_media_menu_for_abonent(): void
    {
        $user_id = get_current_user_id();
        if ($user_id && $this->is_abonent_user($user_id)) {
            remove_menu_page('upload.php');
        }
    }

    public function block_media_library_for_abonent(): void
    {
        $user_id = get_current_user_id();
        if (!$user_id || !$this->is_abonent_user($user_id)) {
            return;
        }

        $pagenow = $GLOBALS['pagenow'] ?? '';
        if ($pagenow === 'upload.php') {
            wp_safe_redirect(admin_url('profile.php'));
            exit;
        }
    }

    public function limit_media_library_for_abonent(array $args): array
    {
        $user_id = get_current_user_id();
        if ($user_id && $this->is_abonent_user($user_id)) {
            $args['author'] = $user_id;
            $args['posts_per_page'] = 0;
        }

        return $args;
    }

    private function get_user_id_from_mixed($id_or_email): int
    {
        if (is_numeric($id_or_email)) {
            return (int) $id_or_email;
        }

        if ($id_or_email instanceof WP_User) {
            return (int) $id_or_email->ID;
        }

        if ($id_or_email instanceof WP_Post) {
            return (int) $id_or_email->post_author;
        }

        if (is_string($id_or_email)) {
            $user = get_user_by('email', $id_or_email);
            return $user ? (int) $user->ID : 0;
        }

        if (is_object($id_or_email) && property_exists($id_or_email, 'user_id')) {
            return (int) $id_or_email->user_id;
        }

        return 0;
    }

    public function filter_get_avatar($avatar, $id_or_email, $size, $default, $alt, $args): string
    {
        $user_id = $this->get_user_id_from_mixed($id_or_email);
        $local_avatar = $user_id ? $this->get_local_avatar_url($user_id, $size) : '';
        $final_avatar = $local_avatar ? $local_avatar : $this->get_placeholder_svg((int) $size);

        $alt = $alt ? $alt : __('Avatar', 'local-user-avatar');
        $size = (int) $size;

        return sprintf(
            '<img alt="%s" src="%s" class="avatar avatar-%d photo" height="%d" width="%d" loading="lazy" decoding="async" />',
            esc_attr($alt),
            esc_url($final_avatar),
            $size,
            $size,
            $size
        );
    }

    public function filter_get_avatar_data(array $args, $id_or_email): array
    {
        $user_id = $this->get_user_id_from_mixed($id_or_email);
        $local_avatar = $user_id ? $this->get_local_avatar_url($user_id, (int) $args['size']) : '';
        $args['found_avatar'] = true;
        $args['url'] = $local_avatar ? $local_avatar : $this->get_placeholder_svg((int) $args['size']);

        return $args;
    }

    public function allow_data_protocol(array $protocols): array
    {
        if (!in_array('data', $protocols, true)) {
            $protocols[] = 'data';
        }

        return $protocols;
    }

    private function get_local_avatar_url(int $user_id, int $size): string
    {
        $avatar_url = (string) get_user_meta($user_id, self::META_AVATAR_URL, true);
        if ($avatar_url) {
            return $avatar_url;
        }

        $avatar_id = (int) get_user_meta($user_id, self::META_AVATAR_ID, true);
        if ($avatar_id) {
            $image_src = wp_get_attachment_image_src($avatar_id, [$size, $size]);
            if (!empty($image_src[0])) {
                return $image_src[0];
            }
        }

        return '';
    }

    public function hide_gravatar_section(): void
    {
        echo '<style>
            #profile-page .user-profile-picture, .user-edit-php .user-profile-picture,
            #profile-page .user-syntax-highlighting-wrap, .user-edit-php .user-syntax-highlighting-wrap,
            #profile-page .user-admin-color-wrap, .user-edit-php .user-admin-color-wrap,
            #profile-page .user-comment-shortcuts-wrap, .user-edit-php .user-comment-shortcuts-wrap,
            #profile-page .user-admin-bar-front-wrap, .user-edit-php .user-admin-bar-front-wrap,
            #profile-page .show-admin-bar, .user-edit-php .show-admin-bar {
                display: none !important;
            }
        </style>';
    }

    private function get_placeholder_svg(int $size): string
    {
        $size = max(1, $size);
        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%1$d" height="%1$d" viewBox="0 0 100 100" role="img" aria-label="Anonymous user"><circle cx="50" cy="50" r="50" fill="#E5E7EB"/><circle cx="50" cy="38" r="16" fill="#C5CBD3"/><path d="M18 84c6-16 19-24 32-24s26 8 32 24" fill="#C5CBD3"/><rect x="22" y="62" width="56" height="20" rx="10" fill="#9CA3AF"/><text x="50" y="76" text-anchor="middle" font-family="Arial, sans-serif" font-size="10" fill="#ffffff"></text></svg>',
            $size
        );

        return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
    }
}

new Local_User_Avatar();
