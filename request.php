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
 * Request an evaluation for a course.
 *
 * @package    local_thlevasys
 * @copyright  2026 Jan Rieger <jan.rieger@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$courseid = required_param('id', PARAM_INT);

$course = get_course($courseid);
require_login($course);

$coursecontext = context_course::instance($courseid);
$categorycontext = context_coursecat::instance($course->category);

if (!is_enrolled($coursecontext, null, '', true)) {
    throw new moodle_exception('nopermissions', 'error', '', get_string('requestevaluation', 'local_thlevasys'));
}

require_capability('local/thlevasys:requestevaluation', $categorycontext);

$pagetitle = get_string('requestevaluation', 'local_thlevasys');
$url = new moodle_url('/local/thlevasys/request.php', ['id' => $courseid]);

$PAGE->set_url($url);
$PAGE->set_context($coursecontext);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title($pagetitle);
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle);
echo $OUTPUT->footer();
