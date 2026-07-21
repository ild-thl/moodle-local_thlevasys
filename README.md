# local_thlevasys (THL EvaSys)

Moodle-Plugin vom Typ `local` der Technischen Hochschule Lübeck zur Anbindung an EvaSys.

## Anforderungen

| Eigenschaft | Wert |
| --- | --- |
| Plugin-Typ | `local` |
| Frankenstyle | `local_thlevasys` |
| Mindestversion | Moodle 4.5 |
| Unterstützte Versionen | Moodle 4.5, 5.0, 5.1, 5.2 |
| Sprachen | Englisch (`en`), Deutsch (`de`) |
| Lizenz | GNU GPL v3 or later |

## Installation

1. Plugin-Verzeichnis nach `{moodleroot}/local/thlevasys` kopieren bzw. dort auschecken.
2. Als Administrator unter **Website-Administration → Benachrichtigungen** die Installation bzw. das Upgrade ausführen.

Bei Installation und Upgrade legt das Plugin die benötigten Capabilities und Rollen automatisch an (sofern die Rollen-Shortnames noch nicht existieren).

## Capabilities und Rollen

Die Capabilities werden **keiner** Standard-Moodle-Rolle zugeordnet (`archetypes` leer). Stattdessen gibt es zwei Plugin-Rollen:

| Rolle (DE) | Shortname | Capability |
| --- | --- | --- |
| Evaluationsbeauftragte*r | `thlevasys_evaluationofficer` | `local/thlevasys:requestevaluation` |
| Evaluations-Admin | `thlevasys_evaluationadmin` | `local/thlevasys:managesettings` |

- **Evaluationsbeauftragte\*r** – Capability im Kursbereich-Kontext; Rolle zuweisbar auf System- und Kursbereichsebene
- **Evaluations-Admin** – zuweisbar auf Systemebene (globale Plugin-Einstellungen)

Zuweisungen erfolgen manuell über die Moodle-Rollenverwaltung. Rollen werden beim Deinstallieren des Plugins nicht automatisch entfernt.

## Evaluation beantragen (zentrale Seite)

Nutzer mit der Rolle **Evaluationsbeauftragte\*r** oder **Evaluations-Admin** sehen in der **Primärnavigation** den Eintrag „Evaluation beantragen“ (unabhängig vom Beantragungszeitraum).

- URL: `/local/thlevasys/request.php`
- Sichtbarkeit des Links: Rollen-Zuweisung als Evaluationsbeauftragte\*r oder Evaluations-Admin
- Außerhalb des Beantragungszeitraums: Evaluationsbeauftragte\*r sehen auf der Seite eine Hinweis-Meldung; Evaluations-Admins dürfen die Seite weiterhin nutzen
- Umsetzung: Hook `\core\hook\navigation\primary_extend` in `db/hooks.php`

### Rolle Evaluationsbeauftragte\*r zuweisen

1. Website-Administration → Kurse → Kurse und Kursbereiche verwalten
2. Kontextmenü des gewünschten Kursbereichs → Rechte → Rollen zuweisen
3. Rolle **Evaluationsbeauftragte\*r** auswählen und den Nutzer zuweisen

Die Capability `local/thlevasys:requestevaluation` gilt damit im jeweiligen Kursbereich.

## Einstellungen

Pfad für Site-Admins: **Website-Administration → Plugins → Lokale Plugins → THL-EvaSys-Einstellungen**

Evaluations-Admins ohne `moodle/site:config` erreichen dieselbe Seite über die Website-Administration (Eintrag auf Root-Ebene).

| Einstellung | Config-Key | Beschreibung |
| --- | --- | --- |
| Beantragungszeitraum von | `local_thlevasys/requestperiod_from` | Erster Tag (Unix-Timestamp, Tagesbeginn) |
| Beantragungszeitraum bis | `local_thlevasys/requestperiod_to` | Letzter Tag inklusive (Unix-Timestamp, Tagesende) |

Zugriff erfordert `local/thlevasys:managesettings`. Werte lesen: `get_config('local_thlevasys', 'requestperiod_from')`.

## Verzeichnisstruktur

```text
local/thlevasys/
├── version.php
├── request.php
├── settings.php
├── classes/
│   ├── access.php
│   ├── admin_setting_configdate.php
│   ├── hook_callbacks.php
│   ├── privacy/
│   │   └── provider.php
│   └── setup.php
├── db/
│   ├── access.php
│   ├── hooks.php
│   ├── install.php
│   └── upgrade.php
├── lang/
│   ├── de/
│   │   └── local_thlevasys.php
│   └── en/
│       └── local_thlevasys.php
├── README.md
└── CHANGELOG.md
```

Hinweis: Die Versionshistorie steht in [CHANGELOG.md](CHANGELOG.md).

## Privacy

Das Plugin speichert derzeit keine personenbezogenen Daten und implementiert die Moodle Privacy API als `null_provider`.

## Entwicklung

- Moodle Developer Resources: [Local plugins](https://moodledev.io/docs/4.5/apis/plugintypes/local)
- Hooks API: [Hooks](https://moodledev.io/docs/4.5/apis/core/hooks) (`primary_extend`)
- Admin settings: [Admin settings](https://moodledev.io/docs/4.5/apis/subsystems/admin)
- Access API: [Capabilities](https://moodledev.io/docs/4.5/apis/subsystems/access)
- Common files: [version.php](https://moodledev.io/docs/4.5/apis/commonfiles/version.php), [Privacy API](https://moodledev.io/docs/4.5/apis/subsystems/privacy)

Bei Änderungen an diesem Plugin die zur Zielversion passende Dokumentation unter `https://moodledev.io/docs/{4.5|5.0|5.1|5.2}/` verwenden.

## Autor

Jan Rieger \<jan.rieger@th-luebeck.de\>
