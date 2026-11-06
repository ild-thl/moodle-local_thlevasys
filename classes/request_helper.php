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

        // Keep the usual category tree order and hierarchical names from make_categories_list().
        foreach (\core_course_category::make_categories_list() as $categoryid => $name) {
            $context = \context_coursecat::instance($categoryid);
            if (!has_capability('local/thlevasys:requestevaluation', $context, $userid)) {
                continue;
            }
            $options[$categoryid] = $name;
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
        if (!self::get_role_id_by_shortname('editingteacher')) {
            return [];
        }

        $rows = [];
        $courses = self::get_requestable_courses($filtercategoryid, $userid);

        foreach ($courses as $course) {
            $coursecontext = \context_course::instance($course->id);
            $teachers = self::get_editing_teachers($coursecontext);
            if (empty($teachers)) {
                continue;
            }

            $groups = groups_get_all_groups($course->id);
            // Only the whole-course count here; per-group counts are filled later for
            // visible table rows (or for selected groups needed for sort/CSV).
            $participantcounts = [0 => self::count_student_participants($coursecontext)];

            foreach ($teachers as $teacher) {
                $row = new \stdClass();
                $row->rowkey = $course->id . '_' . $teacher->id;
                $row->courseid = $course->id;
                $row->courseidnumber = format_string($course->idnumber, true, ['context' => $coursecontext]);
                $row->coursename = format_string($course->fullname, true, ['context' => $coursecontext]);
                $row->teacherid = $teacher->id;
                $row->teachername = fullname($teacher);
                $row->participantcounts = $participantcounts;
                $row->participantcount = $participantcounts[0];
                $row->groups = $groups;
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * Actively enrolled users with the editingteacher role in a course.
     *
     * @param \context_course $context Course context.
     * @return \stdClass[] Users indexed by id (fields needed for fullname()).
     */
    public static function get_editing_teachers(\context_course $context): array {
        global $DB;

        $roleid = self::get_role_id_by_shortname('editingteacher');
        if (!$roleid) {
            return [];
        }

        [$esql, $params] = get_enrolled_sql($context, '', 0, true);
        $params['thlevasysroleid'] = $roleid;
        $params['thlevasyscontextid'] = $context->id;

        $userfieldsapi = \core_user\fields::for_name();
        $nameselects = $userfieldsapi->get_sql('u', false, '', '', false)->selects;

        $sql = "SELECT u.id, {$nameselects}
                  FROM {user} u
                  JOIN ($esql) je ON je.id = u.id
                  JOIN {role_assignments} ra ON ra.userid = u.id
                                           AND ra.roleid = :thlevasysroleid
                                           AND ra.contextid = :thlevasyscontextid
                 WHERE u.deleted = 0";

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Table rows for the admin overview of submitted requests in the current period.
     *
     * @return array<int, \stdClass>
     */
    public static function get_admin_table_rows(): array {
        $requests = request_repository::get_requests_in_period();
        if (empty($requests)) {
            return [];
        }

        $rows = [];
        foreach ($requests as $request) {
            $course = get_course($request->courseid, false);
            if (!$course) {
                continue;
            }

            $coursecontext = \context_course::instance($request->courseid);
            $teacher = \core_user::get_user($request->editingteacher, '*', IGNORE_MISSING);
            $requester = \core_user::get_user($request->requestedby, '*', IGNORE_MISSING);
            if (!$teacher || !$requester) {
                continue;
            }

            $row = new \stdClass();
            $row->id = (int) $request->id;
            $row->courseid = (int) $request->courseid;
            $row->coursename = format_string($course->fullname, true, ['context' => $coursecontext]);
            $row->teacherid = (int) $request->editingteacher;
            $row->teachername = fullname($teacher);
            $row->teacheremail = $teacher->email ?? '';
            $row->courseidnumber = format_string($course->idnumber, true, ['context' => $coursecontext]);
            $row->studiengang = self::get_studiengang_idnumber((int) $course->category);
            $row->participantcount = self::count_student_participants($coursecontext, (int) $request->groupid);
            $row->groupid = (int) $request->groupid;
            $row->lang = $request->lang;
            $row->langlabel = self::format_language_label($request->lang);
            $row->requesterid = (int) $request->requestedby;
            $row->requestername = fullname($requester);
            $row->timecreated = (int) $request->timecreated;
            $row->timecreatedlabel = userdate($request->timecreated);
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Filter rows where any of the given fields contains the search term.
     *
     * @param array $rows Rows to filter.
     * @param string $search Search term.
     * @param string[] $fields Row property names to search in.
     * @return array
     */
    public static function filter_table_rows_by_search(array $rows, string $search, array $fields): array {
        $search = trim($search);
        if ($search === '' || empty($rows)) {
            return $rows;
        }

        $needle = \core_text::strtolower($search);

        return array_values(array_filter($rows, static function ($row) use ($fields, $needle) {
            foreach ($fields as $field) {
                $value = \core_text::strtolower((string) ($row->{$field} ?? ''));
                if ($value !== '' && strpos($value, $needle) !== false) {
                    return true;
                }
            }
            return false;
        }));
    }

    /**
     * Sort table rows according to flexible_table sort columns.
     *
     * @param array $rows Rows to sort.
     * @param array $sortcolumns Column => SORT_ASC|SORT_DESC.
     * @return array
     */
    public static function sort_table_rows(array $rows, array $sortcolumns): array {
        if (empty($rows) || empty($sortcolumns)) {
            return $rows;
        }

        usort($rows, static function ($a, $b) use ($sortcolumns) {
            foreach ($sortcolumns as $column => $order) {
                $va = $a->{$column} ?? '';
                $vb = $b->{$column} ?? '';

                if (is_numeric($va) && is_numeric($vb)) {
                    $cmp = (float) $va <=> (float) $vb;
                } else {
                    $cmp = strcoll(
                        \core_text::strtolower((string) $va),
                        \core_text::strtolower((string) $vb)
                    );
                }

                if ($cmp !== 0) {
                    return $order == SORT_DESC ? -$cmp : $cmp;
                }
            }
            return 0;
        });

        return $rows;
    }

    /**
     * @param string $lang Language code.
     * @return string Localised short label.
     */
    public static function format_language_label(string $lang): string {
        if ($lang === 'en') {
            return get_string('lang_en_short', 'local_thlevasys');
        }

        return get_string('lang_de_short', 'local_thlevasys');
    }

    /**
     * Ensure participant counts exist for each row's currently selected group.
     *
     * Used before sorting/search/CSV when only the whole-course count was loaded.
     *
     * @param array $rows Table rows (modified in place).
     * @return array
     */
    public static function ensure_participant_counts_for_selected_groups(array $rows): array {
        $cache = [];

        foreach ($rows as $row) {
            $groupid = (int) ($row->groupid ?? 0);
            if ($groupid === 0) {
                continue;
            }

            $counts = $row->participantcounts ?? [];
            if (array_key_exists($groupid, $counts)) {
                $row->participantcount = (int) $counts[$groupid];
                continue;
            }

            $courseid = (int) $row->courseid;
            $cachekey = $courseid . ':' . $groupid;
            if (!array_key_exists($cachekey, $cache)) {
                $cache[$cachekey] = self::count_student_participants(
                    \context_course::instance($courseid),
                    $groupid
                );
            }

            $row->participantcounts[$groupid] = $cache[$cachekey];
            $row->participantcount = $cache[$cachekey];
        }

        return $rows;
    }

    /**
     * Fill per-group participant counts for the given rows (typically one table page).
     *
     * Counts are computed once per course and shared across teacher rows of that course.
     *
     * @param array $rows Table rows (modified in place).
     * @return array
     */
    public static function fill_group_participant_counts(array $rows): array {
        if (empty($rows)) {
            return $rows;
        }

        $bycourse = [];
        foreach ($rows as $row) {
            $courseid = (int) $row->courseid;
            if (isset($bycourse[$courseid])) {
                continue;
            }
            $bycourse[$courseid] = [
                'groups' => $row->groups ?? [],
                'counts' => $row->participantcounts ?? [0 => (int) ($row->participantcount ?? 0)],
            ];
        }

        foreach ($bycourse as $courseid => $data) {
            if (empty($data['groups'])) {
                continue;
            }
            $context = \context_course::instance($courseid);
            foreach ($data['groups'] as $group) {
                $groupid = (int) $group->id;
                if (array_key_exists($groupid, $bycourse[$courseid]['counts'])) {
                    continue;
                }
                $bycourse[$courseid]['counts'][$groupid] = self::count_student_participants($context, $groupid);
            }
        }

        foreach ($rows as $row) {
            $courseid = (int) $row->courseid;
            $row->participantcounts = $bycourse[$courseid]['counts'];
            $groupid = (int) ($row->groupid ?? 0);
            $row->participantcount = (int) ($row->participantcounts[$groupid] ?? $row->participantcounts[0] ?? 0);
        }

        return $rows;
    }

    /**
     * Count actively enrolled users with the student role.
     *
     * Uses a SQL COUNT over active enrolments + role assignment (no per-user is_enrolled).
     *
     * @param \context_course $context Course context.
     * @param int $groupid Optional group id (0 = whole course).
     * @return int
     */
    public static function count_student_participants(\context_course $context, int $groupid = 0): int {
        global $DB;

        $studentroleid = self::get_role_id_by_shortname('student');
        if (!$studentroleid) {
            return 0;
        }

        [$esql, $params] = get_enrolled_sql($context, '', $groupid ?: 0, true);
        $params['thlevasysroleid'] = $studentroleid;
        $params['thlevasyscontextid'] = $context->id;

        $sql = "SELECT COUNT(1)
                  FROM ($esql) je
                  JOIN {role_assignments} ra ON ra.userid = je.id
                                           AND ra.roleid = :thlevasysroleid
                                           AND ra.contextid = :thlevasyscontextid";

        return (int) $DB->count_records_sql($sql, $params);
    }

    /**
     * Actively enrolled users with the student role.
     *
     * @param \context_course $context Course context.
     * @param int $groupid Optional group id (0 = whole course).
     * @return \stdClass[] Users indexed by id.
     */
    public static function get_student_participants(\context_course $context, int $groupid = 0): array {
        global $DB;

        $studentroleid = self::get_role_id_by_shortname('student');
        if (!$studentroleid) {
            return [];
        }

        [$esql, $params] = get_enrolled_sql($context, '', $groupid ?: 0, true);
        $params['thlevasysroleid'] = $studentroleid;
        $params['thlevasyscontextid'] = $context->id;

        $sql = "SELECT u.id, u.firstname, u.lastname, u.email, u.username
                  FROM {user} u
                  JOIN ($esql) je ON je.id = u.id
                  JOIN {role_assignments} ra ON ra.userid = u.id
                                           AND ra.roleid = :thlevasysroleid
                                           AND ra.contextid = :thlevasyscontextid
                 WHERE u.deleted = 0";

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Resolve a role id by shortname (cached for the request).
     *
     * @param string $shortname Role shortname.
     * @return int|null
     */
    protected static function get_role_id_by_shortname(string $shortname): ?int {
        global $DB;

        static $cache = [];

        if (!array_key_exists($shortname, $cache)) {
            $roleid = $DB->get_field('role', 'id', ['shortname' => $shortname]);
            $cache[$shortname] = $roleid ? (int) $roleid : null;
        }

        return $cache[$shortname];
    }

    /**
     * Study programme idnumber for a course category.
     *
     * Expected tree: faculty (top) > study programme > optional nested categories > course category.
     * Returns the idnumber of the study programme category (direct child of the faculty category).
     *
     * @param int $categoryid Course category id.
     * @return string Category idnumber or empty string if not found.
     */
    public static function get_studiengang_idnumber(int $categoryid): string {
        static $cache = [];

        if (array_key_exists($categoryid, $cache)) {
            return $cache[$categoryid];
        }

        $category = \core_course_category::get($categoryid, IGNORE_MISSING, true);
        if (!$category) {
            $cache[$categoryid] = '';
            return '';
        }

        $pathids = array_values(array_filter(array_map('intval', explode('/', trim($category->path, '/')))));
        if (count($pathids) < 2) {
            $cache[$categoryid] = '';
            return '';
        }

        $studiengangcategory = \core_course_category::get($pathids[1], IGNORE_MISSING, true);
        if (!$studiengangcategory || trim((string) $studiengangcategory->idnumber) === '') {
            $cache[$categoryid] = '';
            return '';
        }

        $context = \context_coursecat::instance($studiengangcategory->id);
        $cache[$categoryid] = format_string($studiengangcategory->idnumber, true, ['context' => $context]);
        return $cache[$categoryid];
    }

    /**
     * @param int $groupid Group id or 0.
     * @param int $courseid Course id.
     * @return string Group name or "no group" string.
     */
    protected static function format_group_name(int $groupid, int $courseid): string {
        if (!$groupid) {
            return get_string('group_none', 'local_thlevasys');
        }

        $group = groups_get_group($groupid, 'id, courseid, name', IGNORE_MISSING);
        if (!$group || (int) $group->courseid !== $courseid) {
            return get_string('group_none', 'local_thlevasys');
        }

        return format_string($group->name);
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
