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
 * Download EvaSys XML export for requested evaluations.
 *
 * @package    local_thlevasys
 * @copyright  2026 Jan Rieger <jan.rieger@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');

require_login();
require_sesskey();

if (!\local_thlevasys\access::is_evaluation_admin()) {
    throw new moodle_exception('error_requestnotavailable', 'local_thlevasys');
}

$options = [
    'evaluationstart' => required_param('evaluationstart', PARAM_RAW_TRIMMED),
    'evaluationreminder' => required_param('evaluationreminder', PARAM_RAW_TRIMMED),
    'evaluationresponserate' => required_param('evaluationresponserate', PARAM_RAW_TRIMMED),
    'evaluationend' => required_param('evaluationend', PARAM_RAW_TRIMMED),
    'semester' => required_param('semester', PARAM_TEXT),
    'questionnairede' => required_param('questionnairede', PARAM_TEXT),
    'questionnaireen' => required_param('questionnaireen', PARAM_TEXT),
];

$xml = \local_thlevasys\evasys_xml_exporter::generate($options);

$filename = 'evasys_export_' . userdate(time(), '%Y%m%d_%H%M%S') . '.xml';
send_file($xml, $filename, 0, 0, true, true, 'application/xml');
