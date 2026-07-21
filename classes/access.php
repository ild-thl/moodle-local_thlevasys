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
 * Capability, enrolment and period checks for evaluation requests.
 */
class access {

    /**
     * Whether the current time is within the configured request period.
     *
     * Both start and end must be configured. The period is inclusive
     * (from day start through to day end as stored in settings).
     *
     * @param int|null $now Optional unix timestamp for testing; defaults to time().
     * @return bool
     */
    public static function is_within_request_period(?int $now = null): bool {
        $from = (int) get_config('local_thlevasys', 'requestperiod_from');
        $to = (int) get_config('local_thlevasys', 'requestperiod_to');

        if ($from <= 0 || $to <= 0) {
            return false;
        }

        $now = $now ?? time();

        return ($now >= $from && $now <= $to);
    }

    /**
     * Whether a user may request an evaluation for the given course.
     *
     * Requires active enrolment, the requestevaluation capability in the course
     * category context, and that the current time is within the request period.
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

        if (!self::is_within_request_period()) {
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
