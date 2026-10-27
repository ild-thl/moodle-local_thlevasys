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
 * Reset (delete) all evaluation requests.
 *
 * @package    local_thlevasys
 * @copyright  2026 Jan Rieger <jan.rieger@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

if (!\local_thlevasys\access::is_evaluation_admin()) {
    throw new moodle_exception('error_requestnotavailable', 'local_thlevasys');
}

$confirm = optional_param('confirm', 0, PARAM_BOOL);

$returnurl = new moodle_url('/local/thlevasys/admin_requests.php');
$pagetitle = get_string('reset_requests', 'local_thlevasys');

$PAGE->set_url(new moodle_url('/local/thlevasys/reset_requests.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);

$count = \local_thlevasys\request_repository::count_all_requests();

if (!$confirm) {
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(
        get_string('reset_requests_confirm', 'local_thlevasys', $count),
        new moodle_url('/local/thlevasys/reset_requests.php', [
            'confirm' => 1,
            'sesskey' => sesskey(),
        ]),
        $returnurl
    );
    echo $OUTPUT->footer();
    exit;
}

require_sesskey();

$deleted = \local_thlevasys\request_repository::delete_all_requests();

redirect(
    $returnurl,
    get_string('reset_requests_success', 'local_thlevasys', $deleted),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
