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
$string['privacy:metadata'] = 'Das Plugin THL EvaSys speichert keine personenbezogenen Daten.';

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
