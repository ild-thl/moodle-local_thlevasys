# Changelog

Alle wesentlichen Änderungen an `local_thlevasys` werden in dieser Datei festgehalten.

Format angelehnt an [Keep a Changelog](https://keepachangelog.com/de/1.1.0/).
Versionierung: Plugin-`release` sowie Moodle-`version` (`YYYYMMDDXX`).

## [0.4.0] - 2026-08-31

Plugin-Version: `2026083100` · Maturity: Alpha

### Hinzugefügt

- Eigene Übersichtsseite für Evaluations-Admins unter `/local/thlevasys/admin_requests.php`
- Tabelle aller Beantragungen aller Evaluationsbeauftragten im konfigurierten Beantragungszeitraum (Sortierung, Paginierung)
- Suchfeld zum Filtern des Tabelleninhalts in der Admin-Übersichtstabelle

### Geändert

- Navigationslink „Evaluation beantragen“ führt Evaluations-Admins auf die Übersichtsseite

## [0.3.6] - 2026-08-04

Plugin-Version: `2026080405` · Maturity: Alpha

### Hinzugefügt

- Sortierung der Beantragungstabelle nach allen Spalten (Moodle `flexible_table`, Standard: Kursname aufsteigend)
- Paginierung der Beantragungstabelle (10 Einträge pro Seite)

## [0.3.5] - 2026-08-04

Plugin-Version: `2026080404` · Maturity: Alpha

### Geändert

- Beantragungstabelle kompakter (`table-layout: fixed`, kürzere Sprachkürzel DE/EN), damit sie ohne horizontalen Scrollbalken in den Inhaltsbereich passt

## [0.3.4] - 2026-08-04

Plugin-Version: `2026080403` · Maturity: Alpha

### Behoben

- Checkboxen in der Beantragungstabelle erschienen neben der Tabelle (`form-check-input` mit `position:absolute` ohne Wrapper)

## [0.3.3] - 2026-08-04

Plugin-Version: `2026080402` · Maturity: Alpha

### Behoben

- Beantragungstabelle: Checkboxen nicht mehr außerhalb der Zeilenhintergründe (Override von `.generaltable { width: 100% }`, Tabelle scrollt als Ganzes)

## [0.3.2] - 2026-08-04

Plugin-Version: `2026080401` · Maturity: Alpha

### Behoben

- Layout der Beantragungstabelle: Checkbox-Spalte blieb bei vielen Zeilen nicht mehr innerhalb der Tabelle (Select-Breiten begrenzt)

## [0.3.1] - 2026-08-04

Plugin-Version: `2026080400` · Maturity: Alpha

### Geändert

- Kursname und Dozent\*innen-Name in der Beantragungstabelle sind klickbar (Kursseite bzw. Nutzerprofil im Kurs)

## [0.3.0] - 2026-07-30

Plugin-Version: `2026073001` · Maturity: Alpha

### Hinzugefügt

- Datenbanktabelle `local_thlevasys_requests` für Evaluationsbeantragungen (courseid, editingteacher, groupid, lang, requestedby, timecreated)
- Sofortiges Speichern bzw. Löschen beim Setzen/Entfernen der Auswahl-Checkbox (AJAX-Webservice)
- Privacy-API für die gespeicherten Beantragungsdaten

### Geändert

- Bestehende Beantragungen werden in der Tabelle vorausgewählt (inkl. Gruppe und Sprache)

## [0.2.0] - 2026-07-30

Plugin-Version: `2026073000` · Maturity: Alpha

### Hinzugefügt

- Beantragungstabelle auf `/local/thlevasys/request.php` mit Spalten Kurs-ID, Kursname, Dozent\*in, Teilnehmer\*innen, Gruppe, Sprache, Auswahl
- Eine Tabellenzeile pro eingeschriebenem editingteacher in Kursen mit `requestevaluation` im Kursbereich
- Kursbereichs-Filter (inkl. Unterbereiche)
- Gruppen- und Sprach-Dropdowns sowie Auswahl-Checkboxen (Formular vorbereitet)

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
