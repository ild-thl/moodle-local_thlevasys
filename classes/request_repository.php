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
 * Persistence helpers for evaluation requests.
 *
 * @package    local_thlevasys
 * @copyright  2026 Jan Rieger <jan.rieger@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_thlevasys;

defined('MOODLE_INTERNAL') || die();

/**
 * Create, delete and look up evaluation request records.
 */
class request_repository {

    /**
     * Get existing requests for a user, keyed by "courseid_editingteacher".
     *
     * @param int $userid Requester user id.
     * @return array<string, \stdClass>
     */
    public static function get_requests_for_user(int $userid): array {
        global $DB;

        $records = $DB->get_records('local_thlevasys_requests', ['requestedby' => $userid]);
        $indexed = [];
        foreach ($records as $record) {
            $indexed[$record->courseid . '_' . $record->editingteacher] = $record;
        }
        return $indexed;
    }

    /**
     * Add or update a request for the current user.
     *
     * @param int $courseid Course id.
     * @param int $editingteacher Teacher user id.
     * @param int $groupid Group id or 0.
     * @param string $lang Language code.
     * @param int|null $userid Requester; defaults to current user.
     * @return int Request record id.
     */
    public static function add_request(
        int $courseid,
        int $editingteacher,
        int $groupid,
        string $lang,
        ?int $userid = null
    ): int {
        global $DB, $USER;

        $userid = $userid ?? (int) $USER->id;
        self::validate_request_data($courseid, $editingteacher, $groupid, $lang, $userid);

        $existing = $DB->get_record('local_thlevasys_requests', [
            'courseid' => $courseid,
            'editingteacher' => $editingteacher,
            'requestedby' => $userid,
        ]);

        if ($existing) {
            $existing->groupid = $groupid;
            $existing->lang = $lang;
            $DB->update_record('local_thlevasys_requests', $existing);
            return (int) $existing->id;
        }

        $record = (object) [
            'courseid' => $courseid,
            'editingteacher' => $editingteacher,
            'groupid' => $groupid,
            'lang' => $lang,
            'requestedby' => $userid,
            'timecreated' => time(),
        ];
        return (int) $DB->insert_record('local_thlevasys_requests', $record);
    }

    /**
     * Delete a request for the current user.
     *
     * @param int $courseid Course id.
     * @param int $editingteacher Teacher user id.
     * @param int|null $userid Requester; defaults to current user.
     * @return bool True if a record was deleted.
     */
    public static function delete_request(int $courseid, int $editingteacher, ?int $userid = null): bool {
        global $DB, $USER;

        $userid = $userid ?? (int) $USER->id;

        if (!access::can_submit_request_now($userid)) {
            throw new \moodle_exception('error_outside_requestperiod', 'local_thlevasys');
        }

        return (bool) $DB->delete_records('local_thlevasys_requests', [
            'courseid' => $courseid,
            'editingteacher' => $editingteacher,
            'requestedby' => $userid,
        ]);
    }

    /**
     * Validate that the user may create this request and that values are consistent.
     *
     * @param int $courseid Course id.
     * @param int $editingteacher Teacher user id.
     * @param int $groupid Group id or 0.
     * @param string $lang Language code.
     * @param int $userid Requester user id.
     */
    public static function validate_request_data(
        int $courseid,
        int $editingteacher,
        int $groupid,
        string $lang,
        int $userid
    ): void {
        global $DB;

        if (!access::can_submit_request_now($userid)) {
            throw new \moodle_exception('error_outside_requestperiod', 'local_thlevasys');
        }

        $course = get_course($courseid);
        if (!request_helper::can_request_in_category((int) $course->category, $userid)) {
            throw new \moodle_exception('error_requestnotavailable', 'local_thlevasys');
        }

        if (!in_array($lang, ['de', 'en'], true)) {
            throw new \invalid_parameter_exception('Invalid language');
        }

        $coursecontext = \context_course::instance($courseid);
        $editingteacherroleid = $DB->get_field('role', 'id', ['shortname' => 'editingteacher'], MUST_EXIST);
        $teachers = get_role_users($editingteacherroleid, $coursecontext, false, 'u.id');
        if (!isset($teachers[$editingteacher]) || !is_enrolled($coursecontext, $editingteacher, '', true)) {
            throw new \moodle_exception('error_requestnotavailable', 'local_thlevasys');
        }

        if ($groupid) {
            $group = groups_get_group($groupid, 'id, courseid', MUST_EXIST);
            if ((int) $group->courseid !== $courseid) {
                throw new \invalid_parameter_exception('Invalid group');
            }
        }
    }
}
