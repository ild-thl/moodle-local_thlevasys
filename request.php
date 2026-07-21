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
 * Central page to request evaluations.
 *
 * @package    local_thlevasys
 * @copyright  2026 Jan Rieger <jan.rieger@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

if (!\local_thlevasys\access::can_view_request_navigation()) {
    throw new moodle_exception('error_requestnotavailable', 'local_thlevasys');
}

$pagetitle = get_string('requestevaluation', 'local_thlevasys');
$url = new moodle_url('/local/thlevasys/request.php');

$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);

echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle);

if (!\local_thlevasys\access::can_submit_request_now()) {
    echo $OUTPUT->notification(get_string('error_outside_requestperiod', 'local_thlevasys'), 'warning');
    echo $OUTPUT->footer();
    exit;
}

echo $OUTPUT->footer();
