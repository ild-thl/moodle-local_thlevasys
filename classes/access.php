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
 * Access checks for local_thlevasys.
 *
 * @package    local_thlevasys
 * @copyright  2026 Jan Rieger <jan.rieger@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_thlevasys;

defined('MOODLE_INTERNAL') || die();

/**
 * Capability and enrolment checks for evaluation requests.
 */
class access {

    /**
     * Whether a user may request an evaluation for the given course.
     *
     * Requires active enrolment in the course and the requestevaluation capability
     * in the course category context of the course.
     *
     * @param int $courseid Course id.
     * @param int|null $userid User id or null for current user.
     * @return bool
     */
    public static function can_request_evaluation(int $courseid, ?int $userid = null): bool {
        global $USER;

        if ($courseid == SITEID) {
            return false;
        }

        $userid = $userid ?? $USER->id;
        $coursecontext = \context_course::instance($courseid);
        $course = get_course($courseid);
        $categorycontext = \context_coursecat::instance($course->category);

        if (!is_enrolled($coursecontext, $userid, '', true)) {
            return false;
        }

        return has_capability('local/thlevasys:requestevaluation', $categorycontext, $userid);
    }
}
