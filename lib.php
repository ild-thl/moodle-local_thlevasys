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
 * Plugin callbacks for local_thlevasys.
 *
 * @package   local_thlevasys
 * @copyright 2026 Jan Rieger <jan.rieger@th-luebeck.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Add the evaluation request page to the course "More" navigation.
 *
 * @param navigation_node $navigation The course administration navigation node.
 * @param stdClass $course The course object.
 * @param context $context The course context.
 */
function local_thlevasys_extend_navigation_course(navigation_node $navigation, stdClass $course, context $context) {
    global $PAGE;

    if ($course->id == SITEID) {
        return;
    }

    if (!\local_thlevasys\access::can_request_evaluation($course->id)) {
        return;
    }

    $url = new moodle_url('/local/thlevasys/request.php', ['id' => $course->id]);
    $title = get_string('requestevaluation', 'local_thlevasys');

    $node = navigation_node::create(
        $title,
        $url,
        navigation_node::TYPE_SETTING,
        null,
        'thlevasys_requestevaluation',
        new pix_icon('i/report', $title)
    );
    $node->set_force_into_more_menu(true);

    if ($PAGE->url->compare($url, URL_MATCH_BASE)) {
        $node->make_active();
    }

    $navigation->add_node($node);
}
