# Changelog

Alle wesentlichen Änderungen an `local_thlevasys` werden in dieser Datei festgehalten.

Format angelehnt an [Keep a Changelog](https://keepachangelog.com/de/1.1.0/).
Versionierung: Plugin-`release` sowie Moodle-`version` (`YYYYMMDDXX`).

## [0.1.0] - 2026-07-21

Plugin-Version: `2026072102` · Maturity: Alpha

### Hinzugefügt

- Grundgerüst als Local-Plugin mit Privacy-`null_provider` und Sprachpaketen Deutsch/Englisch
- Moodle-Kompatibilität 4.5–5.2 (`requires` 4.5, `supported` 405–502)
- Capabilities `local/thlevasys:requestevaluation` und `local/thlevasys:managesettings` (ohne Standard-Rollen-Archetypes)
- Rollen **Evaluationsbeauftragte\*r** und **Evaluations-Admin**, automatische Anlage bei Install/Upgrade
- Admin-Einstellungen für den Beantragungszeitraum (von/bis)
- Zentrale Seite `/local/thlevasys/request.php` zum Beantragen von Evaluationen
- Link „Evaluation beantragen“ in der Primärnavigation für Evaluationsbeauftragte\*r und Evaluations-Admins (Hook `primary_extend`)

### Geändert

- Beantragung nicht mehr kursbezogen über das Menü **Mehr**, sondern zentral über die Primärnavigation
- Zeitraumprüfung auf der Beantragungsseite: Hinweis außerhalb des Zeitraums für Evaluationsbeauftragte\*r; Evaluations-Admins sind davon ausgenommen

### Entfernt

- Navigationseintrag „Evaluation beantragen“ im Kursmenü (**Mehr**)
