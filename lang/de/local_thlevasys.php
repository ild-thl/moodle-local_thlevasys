<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * German language strings for the local_thlevasys plugin.
 *
 * @package   local_thlevasys
 * @copyright 2026 Jan Rieger <jan.rieger@th-luebeck.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'THL EvaSys';
$string['privacy:metadata:local_thlevasys_requests'] = 'Speichert Beantragungen von Evaluationen.';
$string['privacy:metadata:local_thlevasys_requests:courseid'] = 'Die ID des Kurses, für den eine Evaluation beantragt wurde.';
$string['privacy:metadata:local_thlevasys_requests:editingteacher'] = 'Die ID der Dozentin bzw. des Dozenten, für die bzw. den die Evaluation beantragt wurde.';
$string['privacy:metadata:local_thlevasys_requests:groupid'] = 'Die ID der Kursgruppe, falls eine ausgewählt wurde.';
$string['privacy:metadata:local_thlevasys_requests:lang'] = 'Die gewählte Evaluationssprache.';
$string['privacy:metadata:local_thlevasys_requests:requestedby'] = 'Die ID der Person, die die Evaluation beantragt hat.';
$string['privacy:metadata:local_thlevasys_requests:timecreated'] = 'Der Zeitpunkt der Beantragung.';
$string['privacy:path:requests'] = 'Evaluationsbeantragungen';

// Capabilities.
$string['thlevasys:requestevaluation'] = 'Evaluationen beantragen';
$string['thlevasys:managesettings'] = 'Globale THL-EvaSys-Einstellungen bearbeiten';

// Roles.
$string['role_evaluationofficer'] = 'Evaluationsbeauftragte*r';
$string['role_evaluationofficer_desc'] = 'Kann Evaluationen in THL EvaSys beantragen.';
$string['role_evaluationadmin'] = 'Evaluations-Admin';
$string['role_evaluationadmin_desc'] = 'Kann globale Plugin-Einstellungen von THL EvaSys bearbeiten.';

// Settings.
$string['settings'] = 'THL-EvaSys-Einstellungen';
$string['requestperiod'] = 'Beantragungszeitraum für Evaluationen';
$string['requestperiod_desc'] = 'Zeitraum, in dem Evaluationen beantragt werden dürfen.';
$string['requestperiod_from'] = 'Beantragungszeitraum von';
$string['requestperiod_from_desc'] = 'Erster Tag des Beantragungszeitraums (einschließlich).';
$string['requestperiod_to'] = 'Beantragungszeitraum bis';
$string['requestperiod_to_desc'] = 'Letzter Tag des Beantragungszeitraums (einschließlich).';
$string['error_invaliddate'] = 'Bitte ein gültiges Datum eingeben.';
$string['error_requestperiodorder'] = 'Das Ende des Beantragungszeitraums darf nicht vor dem Beginn liegen.';

// Evaluation request page.
$string['requestevaluation'] = 'Evaluation beantragen';
$string['error_requestnotavailable'] = 'Sie haben keine Berechtigung, Evaluationen zu beantragen.';
$string['error_outside_requestperiod'] = 'Derzeit können keine Evaluationen beantragt werden, da der Beantragungszeitraum nicht aktiv ist.';
$string['error_requestperiodnotconfigured'] = 'Es ist kein Beantragungszeitraum konfiguriert.';
$string['admin_requestoverview'] = 'Übersicht beantragter Evaluationen';
$string['admin_requesttable_empty'] = 'Im konfigurierten Beantragungszeitraum wurden keine Evaluationen beantragt.';
$string['admin_requestperiod_info'] = 'Angezeigt werden Beantragungen vom {$a->from} bis {$a->to}.';
$string['export_settings_heading'] = 'Export-Einstellungen';
$string['export_evalstart'] = 'Evaluations-Beginn';
$string['export_evalreminder'] = 'Evaluations-Erinnerung';
$string['export_evalend'] = 'Evaluations-Ende';
$string['export_semester'] = 'Semester';
$string['export_questionnaire_de'] = 'Fragebogen (deutsch)';
$string['export_questionnaire_en'] = 'Fragebogen (englisch)';
$string['filter_category'] = 'Kursbereich';
$string['filter_allcategories'] = 'Alle Kursbereiche';
$string['requesttable_empty'] = 'Es wurden keine Kurse mit eingeschriebenen Dozierenden gefunden, für die eine Beantragung möglich ist.';
$string['requesttable_search_empty'] = 'Für den eingegebenen Suchbegriff wurden keine Einträge gefunden.';
$string['search_label'] = 'Suche';
$string['search_placeholder'] = 'Tabelleninhalt durchsuchen …';
$string['search_submit'] = 'Suchen';
$string['search_clear'] = 'Suche zurücksetzen';
$string['col_courseid'] = 'Kurs-ID';
$string['col_coursename'] = 'Kursname';
$string['col_teacher'] = 'Dozent*in';
$string['col_participants'] = 'Teilnehmer*innen';
$string['col_group'] = 'Gruppe';
$string['col_language'] = 'Sprache';
$string['col_select'] = 'Auswahl';
$string['col_requestedby'] = 'Beantragt von';
$string['col_timecreated'] = 'Beantragt am';
$string['group_none'] = 'Keine Gruppe';
$string['lang_de'] = 'Deutsch';
$string['lang_en'] = 'Englisch';
$string['lang_de_short'] = 'DE';
$string['lang_en_short'] = 'EN';
