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
 * Privacy Subsystem implementation for local_thlevasys.
 *
 * @package    local_thlevasys
 * @copyright  2026 Jan Rieger <jan.rieger@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_thlevasys\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy Subsystem for local_thlevasys.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Describe personal data stored by this plugin.
     *
     * @param collection $items Existing metadata items.
     * @return collection
     */
    public static function get_metadata(collection $items): collection {
        $items->add_database_table('local_thlevasys_requests', [
            'courseid' => 'privacy:metadata:local_thlevasys_requests:courseid',
            'editingteacher' => 'privacy:metadata:local_thlevasys_requests:editingteacher',
            'groupid' => 'privacy:metadata:local_thlevasys_requests:groupid',
            'lang' => 'privacy:metadata:local_thlevasys_requests:lang',
            'requestedby' => 'privacy:metadata:local_thlevasys_requests:requestedby',
            'timecreated' => 'privacy:metadata:local_thlevasys_requests:timecreated',
        ], 'privacy:metadata:local_thlevasys_requests');

        return $items;
    }

    /**
     * Get contexts containing user data.
     *
     * @param int $userid User id.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = "SELECT ctx.id
                  FROM {local_thlevasys_requests} r
                  JOIN {context} ctx ON ctx.instanceid = r.courseid AND ctx.contextlevel = :contextlevel
                 WHERE r.requestedby = :requestedby OR r.editingteacher = :editingteacher";

        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_COURSE,
            'requestedby' => $userid,
            'editingteacher' => $userid,
        ]);

        return $contextlist;
    }

    /**
     * Get users with data in a context.
     *
     * @param userlist $userlist User list to populate.
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if (!$context instanceof \context_course) {
            return;
        }

        $sql = "SELECT requestedby AS userid
                  FROM {local_thlevasys_requests}
                 WHERE courseid = :courseid
             UNION
                SELECT editingteacher AS userid
                  FROM {local_thlevasys_requests}
                 WHERE courseid = :courseid2";

        $userlist->add_from_sql('userid', $sql, [
            'courseid' => $context->instanceid,
            'courseid2' => $context->instanceid,
        ]);
    }

    /**
     * Export user data for the approved contexts.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        $courseids = [];
        foreach ($contextlist->get_contexts() as $context) {
            if ($context instanceof \context_course) {
                $courseids[] = $context->instanceid;
            }
        }
        if (empty($courseids)) {
            return;
        }

        list($coursesql, $courseparams) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
        $params = $courseparams + [
            'requestedby' => $userid,
            'editingteacher' => $userid,
        ];

        $records = $DB->get_records_sql(
            "SELECT *
               FROM {local_thlevasys_requests}
              WHERE courseid {$coursesql}
                AND (requestedby = :requestedby OR editingteacher = :editingteacher)",
            $params
        );

        foreach ($records as $record) {
            $context = \context_course::instance($record->courseid);
            writer::with_context($context)->export_data(
                [get_string('privacy:path:requests', 'local_thlevasys'), $record->id],
                (object) [
                    'courseid' => $record->courseid,
                    'editingteacher' => $record->editingteacher,
                    'groupid' => $record->groupid,
                    'lang' => $record->lang,
                    'requestedby' => $record->requestedby,
                    'timecreated' => \core_privacy\local\request\transform::datetime($record->timecreated),
                ]
            );
        }
    }

    /**
     * Delete all data for all users in the context.
     *
     * @param \context $context Context.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if (!$context instanceof \context_course) {
            return;
        }

        $DB->delete_records('local_thlevasys_requests', ['courseid' => $context->instanceid]);
    }

    /**
     * Delete all user data for the specified user in the approved contexts.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_course) {
                continue;
            }
            $DB->delete_records_select(
                'local_thlevasys_requests',
                'courseid = :courseid AND (requestedby = :requestedby OR editingteacher = :editingteacher)',
                [
                    'courseid' => $context->instanceid,
                    'requestedby' => $userid,
                    'editingteacher' => $userid,
                ]
            );
        }
    }

    /**
     * Delete data for multiple users in a context.
     *
     * @param approved_userlist $userlist Approved users.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof \context_course) {
            return;
        }

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        list($requestersql, $requesterparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'req');
        list($teachersql, $teacherparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'tea');
        $params = $requesterparams + $teacherparams + ['courseid' => $context->instanceid];

        $DB->delete_records_select(
            'local_thlevasys_requests',
            "courseid = :courseid AND (requestedby {$requestersql} OR editingteacher {$teachersql})",
            $params
        );
    }
}
