# Local User Avatar

Ein einfaches und leichtgewichtiges WordPress Plugin, das es ermöglicht, lokale Avatar-Bilder für Benutzerprofile hochzuladen und Gravatar vollständig zu ersetzen.

## Beschreibung

**Local User Avatar** fügt Benutzerprofilen ein Avatar-Feld hinzu und ersetzt Gravatar mit lokal hochgeladenen Bildern. Das Plugin bietet eine datenschutzfreundliche Alternative zu Gravatar, da keine externen Dienste für Profilbilder verwendet werden müssen.

### Features

- ✅ **Lokale Avatar-Uploads**: Lade Avatare direkt in die WordPress-Mediathek hoch
- ✅ **Gravatar-Ersatz**: Ersetzt automatisch alle Gravatar-Aufrufe durch lokale Avatare
- ✅ **WordPress Media Library Integration**: Nutzt die native WordPress-Medienverwaltung
- ✅ **Responsive Vorschau**: Zeigt eine Live-Vorschau des ausgewählten Avatars
- ✅ **Platzhalter-SVG**: Zeigt automatisch einen generischen Avatar an, wenn kein Bild hochgeladen wurde
- ✅ **Einfache Bedienung**: Intuitives Interface im Benutzerprofil
- ✅ **Sicher**: Nutzt WordPress Nonces und Capability-Checks
- ✅ **Leichtgewichtig**: Single-File Plugin ohne externe Abhängigkeiten

## Installation

### Manuelle Installation

1. Lade die Datei `local-avatar.php` in das Verzeichnis `/wp-content/plugins/local-user-avatar/` hoch
2. Aktiviere das Plugin über das 'Plugins' Menü in WordPress
3. Gehe zu **Benutzer → Profil** um deinen lokalen Avatar hochzuladen

### Installation via Upload

1. Gehe zu **Plugins → Installieren** in deinem WordPress-Dashboard
2. Klicke auf **Plugin hochladen**
3. Wähle die ZIP-Datei aus und klicke auf **Jetzt installieren**
4. Aktiviere das Plugin nach der Installation

## Verwendung

### Avatar hochladen

1. Gehe zu **Benutzer → Profil** oder bearbeite ein Benutzerprofil
2. Scrolle zum Abschnitt **Avatar**
3. Klicke auf **Select or Upload Avatar**
4. Wähle ein Bild aus der Mediathek oder lade ein neues Bild hoch
5. Klicke auf **Use this avatar**
6. Speichere dein Profil

### Avatar entfernen

1. Gehe zu deinem Benutzerprofil
2. Klicke auf **Remove Avatar**
3. Speichere dein Profil

Der Avatar wird automatisch überall dort angezeigt, wo WordPress Avatare verwendet:
- Kommentare
- Benutzerlisten
- Toolbar
- Alle Theme- und Plugin-Bereiche, die `get_avatar()` verwenden

## Anforderungen

- WordPress 5.0 oder höher
- PHP 7.4 oder höher
- Berechtigung zum Hochladen von Dateien (`upload_files` Capability)

## Technische Details

### Hooks & Filter

Das Plugin nutzt folgende WordPress-Hooks:

#### Actions
- `show_user_profile` - Zeigt das Avatar-Feld im eigenen Profil
- `edit_user_profile` - Zeigt das Avatar-Feld beim Bearbeiten anderer Profile
- `personal_options_update` - Speichert Avatar-Änderungen im eigenen Profil
- `edit_user_profile_update` - Speichert Avatar-Änderungen in anderen Profilen
- `admin_head-profile.php` - Blendet Gravatar-Abschnitt aus
- `admin_head-user-edit.php` - Blendet Gravatar-Abschnitt aus

#### Filter
- `get_avatar` - Ersetzt Avatar-HTML mit lokalem Avatar
- `get_avatar_data` - Ersetzt Avatar-URL-Daten mit lokalem Avatar

### User Meta Felder

Das Plugin speichert folgende User Meta Felder:
- `local_user_avatar_id` - Attachment-ID des Avatar-Bildes
- `local_user_avatar_url` - URL des Avatar-Bildes (Fallback)

## Häufig gestellte Fragen (FAQ)

### Kann ich das Plugin mit Gravatar verwenden?

Nein, das Plugin ersetzt Gravatar vollständig. Dies ist beabsichtigt, um eine datenschutzfreundliche Lösung anzubieten.

### Welche Bildformate werden unterstützt?

Alle von WordPress unterstützten Bildformate (JPG, PNG, GIF, WebP, etc.).

### Was passiert, wenn kein Avatar hochgeladen wurde?

Das Plugin zeigt automatisch einen generischen SVG-Platzhalter-Avatar an.

### Funktioniert das Plugin mit allen Themes?

Ja, das Plugin funktioniert mit allen Themes, die die WordPress-Standard-Avatar-Funktionen (`get_avatar()`) verwenden.

### Beeinflusst das Plugin die Performance?

Nein, da die Avatare lokal gehostet werden, entfallen externe HTTP-Anfragen an Gravatar, was die Ladezeit sogar verbessern kann.

## Deinstallation

1. Deaktiviere das Plugin über **Plugins → Installierte Plugins**
2. Lösche das Plugin
3. Optional: Entferne die User Meta Felder `local_user_avatar_id` und `local_user_avatar_url` aus der Datenbank

## Changelog

### 1.0.0
- Erste Veröffentlichung
- Avatar-Upload-Funktionalität
- Gravatar-Ersetzung
- SVG-Platzhalter für Benutzer ohne Avatar
- Integration der WordPress Media Library

## Autor

**Henrik Hansen**

## Lizenz

Dieses Plugin ist frei verfügbar. Bitte füge eine passende Lizenz hinzu (z.B. GPL-2.0+, MIT).

## Support

Für Bugs, Feature-Requests oder Fragen, bitte öffne ein Issue auf GitHub:
[https://github.com/hhansen06/wordpress_local-avatar/issues](https://github.com/hhansen06/wordpress_local-avatar/issues)

## Mitwirken

Pull Requests sind willkommen! Für größere Änderungen öffne bitte zuerst ein Issue, um zu besprechen, was du ändern möchtest.

---

**Hinweis zur Datenschutzfreundlichkeit:** Dieses Plugin sendet keine Daten an externe Dienste und ist vollständig DSGVO-konform, da alle Avatare lokal gespeichert werden.