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

## Verzeichnisstruktur

```text
local/thlevasys/
├── version.php
├── classes/
│   ├── privacy/
│   │   └── provider.php
│   └── setup.php
├── db/
│   ├── access.php
│   ├── install.php
│   └── upgrade.php
├── lang/
│   ├── de/
│   │   └── local_thlevasys.php
│   └── en/
│       └── local_thlevasys.php
└── README.md
```

## Privacy

Das Plugin speichert derzeit keine personenbezogenen Daten und implementiert die Moodle Privacy API als `null_provider`.

## Entwicklung

- Moodle Developer Resources: [Local plugins](https://moodledev.io/docs/4.5/apis/plugintypes/local)
- Access API: [Capabilities](https://moodledev.io/docs/4.5/apis/subsystems/access)
- Common files: [version.php](https://moodledev.io/docs/4.5/apis/commonfiles/version.php), [Privacy API](https://moodledev.io/docs/4.5/apis/subsystems/privacy)

Bei Änderungen an diesem Plugin die zur Zielversion passende Dokumentation unter `https://moodledev.io/docs/{4.5|5.0|5.1|5.2}/` verwenden.

## Autor

Jan Rieger \<jan.rieger@th-luebeck.de\>
