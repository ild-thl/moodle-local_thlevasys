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
 * Languages configuration for the local_thlevasys plugin.
 *
 * @package   local_thlevasys
 * @copyright 2026 Jan Rieger <jan.rieger@th-luebeck.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'THL EvaSys';
$string['privacy:metadata'] = 'The THL EvaSys plugin does not store any personal data.';

// Capabilities.
$string['thlevasys:requestevaluation'] = 'Request evaluations';
$string['thlevasys:managesettings'] = 'Edit global THL EvaSys settings';

// Roles.
$string['role_evaluationofficer'] = 'Evaluation officer';
$string['role_evaluationofficer_desc'] = 'Can request evaluations in THL EvaSys.';
$string['role_evaluationadmin'] = 'Evaluation admin';
$string['role_evaluationadmin_desc'] = 'Can edit global THL EvaSys plugin settings.';

// Settings.
$string['settings'] = 'THL EvaSys settings';
$string['requestperiod'] = 'Evaluation request period';
$string['requestperiod_desc'] = 'Define the period during which evaluations may be requested.';
$string['requestperiod_from'] = 'Request period from';
$string['requestperiod_from_desc'] = 'First day of the evaluation request period (inclusive).';
$string['requestperiod_to'] = 'Request period until';
$string['requestperiod_to_desc'] = 'Last day of the evaluation request period (inclusive).';
$string['error_invaliddate'] = 'Please enter a valid date.';
$string['error_requestperiodorder'] = 'The end of the request period must not be before the start.';

// Evaluation request page.
$string['requestevaluation'] = 'Request evaluation';
$string['error_requestnotavailable'] = 'An evaluation cannot be requested at this time. Check the request period and your permissions.';
