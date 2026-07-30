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
 * Data helpers for the evaluation request table.
 *
 * @package    local_thlevasys
 * @copyright  2026 Jan Rieger <jan.rieger@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_thlevasys;

defined('MOODLE_INTERNAL') || die();

/**
 * Builds filter options and table rows for evaluation requests.
 */
class request_helper {

    /**
     * Categories where the user has local/thlevasys:requestevaluation.
     *
     * @param int|null $userid User id or null for the current user.
     * @return array Category id => category name only (without parent path).
     */
    public static function get_filter_categories(?int $userid = null): array {
        global $USER;

        $userid = $userid ?? $USER->id;
        $options = [];

        // Keep the usual category tree order from make_categories_list().
        foreach (\core_course_category::make_categories_list() as $categoryid => $unusedname) {
            $context = \context_coursecat::instance($categoryid);
            if (!has_capability('local/thlevasys:requestevaluation', $context, $userid)) {
                continue;
            }
            $category = \core_course_category::get($categoryid, IGNORE_MISSING, true);
            if (!$category) {
                continue;
            }
            $options[$categoryid] = $category->get_formatted_name();
        }

        return $options;
    }

    /**
     * Whether the user may request evaluations for courses in the given category.
     *
     * @param int $categoryid Course category id.
     * @param int|null $userid User id or null for the current user.
     * @return bool
     */
    public static function can_request_in_category(int $categoryid, ?int $userid = null): bool {
        global $USER;

        $userid = $userid ?? $USER->id;
        return has_capability(
            'local/thlevasys:requestevaluation',
            \context_coursecat::instance($categoryid),
            $userid
        );
    }

    /**
     * Courses for which the user may request evaluations, optionally filtered.
     *
     * @param int $filtercategoryid Category id to filter by (0 = all allowed).
     * @param int|null $userid User id or null for the current user.
     * @return \stdClass[] Course records keyed by id.
     */
    public static function get_requestable_courses(int $filtercategoryid = 0, ?int $userid = null): array {
        global $USER;

        $userid = $userid ?? $USER->id;

        if ($filtercategoryid) {
            if (!self::can_request_in_category($filtercategoryid, $userid)) {
                return [];
            }
            $category = \core_course_category::get($filtercategoryid, MUST_EXIST, true);
            $candidates = $category->get_courses([
                'recursive' => true,
                'sort' => ['fullname' => 1],
            ]);
        } else {
            $candidates = [];
            $allowedcategories = self::get_filter_categories($userid);
            // Prefer root-most allowed categories to avoid duplicate course fetches.
            foreach (self::get_root_allowed_category_ids(array_keys($allowedcategories)) as $categoryid) {
                $category = \core_course_category::get($categoryid, IGNORE_MISSING, true);
                if (!$category) {
                    continue;
                }
                foreach ($category->get_courses(['recursive' => true, 'sort' => ['fullname' => 1]]) as $course) {
                    $candidates[$course->id] = $course;
                }
            }
        }

        $courses = [];
        foreach ($candidates as $course) {
            if ((int) $course->id === (int) SITEID) {
                continue;
            }
            if (!self::can_request_in_category((int) $course->category, $userid)) {
                continue;
            }
            $courses[$course->id] = $course;
        }

        \core_collator::asort_objects_by_property($courses, 'fullname', \core_collator::SORT_NATURAL);
        return $courses;
    }

    /**
     * Table rows: one entry per enrolled editing teacher of each requestable course.
     *
     * @param int $filtercategoryid Category filter (0 = all).
     * @param int|null $userid User id or null for the current user.
     * @return array<int, \stdClass>
     */
    public static function get_table_rows(int $filtercategoryid = 0, ?int $userid = null): array {
        global $DB;

        $editingteacherroleid = $DB->get_field('role', 'id', ['shortname' => 'editingteacher']);
        if (!$editingteacherroleid) {
            return [];
        }

        $rows = [];
        $courses = self::get_requestable_courses($filtercategoryid, $userid);

        foreach ($courses as $course) {
            $coursecontext = \context_course::instance($course->id);
            $teachers = get_role_users(
                $editingteacherroleid,
                $coursecontext,
                false,
                'u.id, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename'
            );

            $participantcount = count_enrolled_users($coursecontext, '', 0, true);
            $groups = groups_get_all_groups($course->id);

            foreach ($teachers as $teacher) {
                if (!is_enrolled($coursecontext, $teacher, '', true)) {
                    continue;
                }

                $row = new \stdClass();
                $row->rowkey = $course->id . '_' . $teacher->id;
                $row->courseid = $course->id;
                $row->coursename = format_string($course->fullname, true, ['context' => $coursecontext]);
                $row->teacherid = $teacher->id;
                $row->teachername = fullname($teacher);
                $row->participantcount = $participantcount;
                $row->groups = $groups;
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * From a set of allowed category ids, keep those that are not descendants of another allowed id.
     *
     * @param int[] $categoryids Allowed category ids.
     * @return int[]
     */
    protected static function get_root_allowed_category_ids(array $categoryids): array {
        if (empty($categoryids)) {
            return [];
        }

        $allowed = array_fill_keys(array_map('intval', $categoryids), true);
        $roots = [];

        foreach ($categoryids as $categoryid) {
            $category = \core_course_category::get($categoryid, IGNORE_MISSING, true);
            if (!$category) {
                continue;
            }
            $pathids = array_filter(array_map('intval', explode('/', trim($category->path, '/'))));
            $hasallowedancestor = false;
            foreach ($pathids as $pathid) {
                if ($pathid === (int) $categoryid) {
                    break;
                }
                if (isset($allowed[$pathid])) {
                    $hasallowedancestor = true;
                    break;
                }
            }
            if (!$hasallowedancestor) {
                $roots[] = (int) $categoryid;
            }
        }

        return $roots;
    }
}
