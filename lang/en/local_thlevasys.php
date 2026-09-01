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
$string['privacy:metadata:local_thlevasys_requests'] = 'Stores evaluation requests.';
$string['privacy:metadata:local_thlevasys_requests:courseid'] = 'The ID of the course for which an evaluation was requested.';
$string['privacy:metadata:local_thlevasys_requests:editingteacher'] = 'The ID of the editing teacher for whom the evaluation was requested.';
$string['privacy:metadata:local_thlevasys_requests:groupid'] = 'The ID of the course group, if one was selected.';
$string['privacy:metadata:local_thlevasys_requests:lang'] = 'The selected evaluation language.';
$string['privacy:metadata:local_thlevasys_requests:requestedby'] = 'The ID of the user who submitted the request.';
$string['privacy:metadata:local_thlevasys_requests:timecreated'] = 'The time when the request was created.';
$string['privacy:path:requests'] = 'Evaluation requests';

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
$string['error_requestnotavailable'] = 'You do not have permission to request evaluations.';
$string['error_outside_requestperiod'] = 'Evaluations cannot be requested at the moment because the request period is not active.';
$string['error_requestperiodnotconfigured'] = 'No request period has been configured.';
$string['admin_requestoverview'] = 'Overview of requested evaluations';
$string['admin_requesttable_empty'] = 'No evaluations were requested during the configured request period.';
$string['admin_requestperiod_info'] = 'Showing requests from {$a->from} to {$a->to}.';
$string['export_settings_heading'] = 'Export settings';
$string['export_evalstart'] = 'Evaluation start';
$string['export_evalreminder'] = 'Evaluation reminder';
$string['export_evalend'] = 'Evaluation end';
$string['export_semester'] = 'Semester';
$string['export_questionnaire_de'] = 'Questionnaire (German)';
$string['export_questionnaire_en'] = 'Questionnaire (English)';
$string['export_evasys_xml'] = 'EvaSys XML';
$string['export_lecture_type_default'] = 'Course';
$string['error_export_norequests'] = 'There are no requested evaluations in the configured request period.';
$string['error_export_semesterrequired'] = 'Please enter a semester for the export.';
$string['error_export_questionnaire_de_required'] = 'Please enter the questionnaire (German) for the export.';
$string['error_export_questionnaire_en_required'] = 'Please enter the questionnaire (English) for the export.';
$string['error_export_daterequired'] = 'Please enter evaluation start, reminder and end dates.';
$string['error_export_reminderbeforestart'] = 'The evaluation reminder must be after the evaluation start.';
$string['error_export_endbeforereminder'] = 'The evaluation end must be after the evaluation reminder.';
$string['filter_category'] = 'Course category';
$string['filter_allcategories'] = 'All categories';
$string['requesttable_empty'] = 'No courses with enrolled editing teachers were found for which a request is possible.';
$string['requesttable_search_empty'] = 'No entries were found for the search term entered.';
$string['search_label'] = 'Search';
$string['search_placeholder'] = 'Search table content …';
$string['search_submit'] = 'Search';
$string['search_clear'] = 'Clear search';
$string['col_courseid'] = 'Course ID';
$string['col_courseidnumber'] = 'Course identifier';
$string['col_coursename'] = 'LV name';
$string['col_teacher'] = 'Teacher';
$string['col_teacher_email'] = 'Email';
$string['col_participants'] = 'LV-TN';
$string['col_group'] = 'Group';
$string['col_groupid'] = 'Group ID';
$string['col_studiengang'] = 'Study programme';
$string['col_language'] = 'Language';
$string['col_select'] = 'Select';
$string['col_requestedby'] = 'Requested by';
$string['col_timecreated'] = 'Requested on';
$string['group_none'] = 'No group';
$string['lang_de'] = 'German';
$string['lang_en'] = 'English';
$string['lang_de_short'] = 'DE';
$string['lang_en_short'] = 'EN';
