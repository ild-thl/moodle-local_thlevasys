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
 * Output helpers for the evaluation request page.
 *
 * @package    local_thlevasys
 * @copyright  2026 Jan Rieger <jan.rieger@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_thlevasys\output;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/tablelib.php');

/**
 * Renders filter and sortable request table markup.
 */
class request_table {

    /**
     * Render category filter and request table.
     *
     * @param int $filtercategoryid Currently selected category id (0 = all).
     * @param \local_thlevasys\form\category_filter|null $filterform Searchable category filter form.
     * @return string HTML
     */
    public function render(int $filtercategoryid = 0, ?\local_thlevasys\form\category_filter $filterform = null): string {
        global $OUTPUT, $USER;

        $html = '';

        $baseurl = new \moodle_url('/local/thlevasys/request.php');
        if ($filtercategoryid) {
            $baseurl->param('categoryid', $filtercategoryid);
        }

        if ($filterform) {
            $html .= \html_writer::div($filterform->render(), 'local-thlevasys-category-filter mb-3');
        }

        $rows = \local_thlevasys\request_helper::get_table_rows($filtercategoryid);
        if (empty($rows)) {
            $html .= $OUTPUT->notification(get_string('requesttable_empty', 'local_thlevasys'), 'info');
            return $html;
        }

        $csvurl = new \moodle_url('/local/thlevasys/export_csv.php', [
            'scope' => 'request',
            'sesskey' => sesskey(),
        ]);
        if ($filtercategoryid) {
            $csvurl->param('categoryid', $filtercategoryid);
        }
        $html .= \html_writer::div(
            \html_writer::link(
                $csvurl,
                get_string('export_table_csv', 'local_thlevasys'),
                ['class' => 'btn btn-secondary']
            ),
            'local-thlevasys-table-csv-export mb-3'
        );

        $existing = \local_thlevasys\request_repository::get_requests_for_user((int) $USER->id);
        $rows = $this->enrich_rows_for_sorting($rows, $existing);

        $table = new bottom_paging_table('local-thlevasys-requests');
        $table->define_columns([
            'courseid',
            'courseidnumber',
            'coursename',
            'teachername',
            'participantcount',
            'groupname',
            'lang',
            'selected',
        ]);
        $table->define_headers([
            get_string('col_courseid', 'local_thlevasys'),
            get_string('col_courseidnumber', 'local_thlevasys'),
            get_string('col_coursename', 'local_thlevasys'),
            get_string('col_teacher', 'local_thlevasys'),
            get_string('col_participants', 'local_thlevasys'),
            get_string('col_group', 'local_thlevasys'),
            get_string('col_language', 'local_thlevasys'),
            get_string('col_select', 'local_thlevasys'),
        ]);
        $table->define_baseurl($baseurl);
        $table->sortable(true, 'coursename', SORT_ASC);
        $table->collapsible(false);
        $table->column_class('selected', 'text-center');
        $table->attributes['class'] = 'generaltable local-thlevasys-request-table';
        $table->attributes['id'] = 'local-thlevasys-request-table';
        $table->setup();

        $rows = \local_thlevasys\request_helper::sort_table_rows($rows, $table->get_sort_columns());
        $table->pagesize(10, count($rows));
        $rows = array_slice($rows, $table->get_page_start(), $table->get_page_size());

        foreach ($rows as $row) {
            $table->add_data([
                $row->courseid,
                $row->courseidnumber,
                $this->render_course_link($row),
                $this->render_teacher_link($row),
                $this->render_participant_cell($row),
                $this->render_group_cell($row),
                $this->render_language_cell($row),
                $this->render_select_cell($row),
            ]);
        }

        ob_start();
        $table->finish_output();
        $html .= \html_writer::div(ob_get_clean(), 'local-thlevasys-request-wrapper', [
            'data-region' => 'local-thlevasys-requests',
        ]);

        return $html;
    }

    /**
     * Add sort fields from existing requests (group, language, selection).
     *
     * @param array $rows Table rows.
     * @param array $existing Existing request records keyed by rowkey.
     * @return array
     */
    public function enrich_rows_for_sorting(array $rows, array $existing): array {
        foreach ($rows as $row) {
            $request = $existing[$row->rowkey] ?? null;
            $row->selected = $request ? 1 : 0;
            $row->lang = $request->lang ?? 'de';
            $row->groupid = $request ? (int) $request->groupid : 0;

            if ($row->groupid && !empty($row->groups[$row->groupid])) {
                $row->groupname = format_string($row->groups[$row->groupid]->name);
            } else {
                $row->groupname = get_string('group_none', 'local_thlevasys');
            }

            $counts = $row->participantcounts ?? [];
            $row->participantcount = (int) ($counts[$row->groupid] ?? $counts[0] ?? 0);
        }

        return $rows;
    }

    /**
     * @param \stdClass $row Table row.
     * @return string
     */
    protected function render_course_link(\stdClass $row): string {
        return \html_writer::link(
            new \moodle_url('/course/view.php', ['id' => $row->courseid]),
            $row->coursename
        );
    }

    /**
     * @param \stdClass $row Table row.
     * @return string
     */
    protected function render_participant_cell(\stdClass $row): string {
        $counts = $row->participantcounts ?? [0 => (int) $row->participantcount];

        return \html_writer::span((string) (int) $row->participantcount, 'local-thlevasys-participant-count', [
            'id' => 'participants_' . $row->rowkey,
            'data-counts' => json_encode($counts, JSON_FORCE_OBJECT),
        ]);
    }

    /**
     * @param \stdClass $row Table row.
     * @return string
     */
    protected function render_teacher_link(\stdClass $row): string {
        return \html_writer::link(
            new \moodle_url('/user/view.php', ['id' => $row->teacherid, 'course' => $row->courseid]),
            $row->teachername
        );
    }

    /**
     * @param \stdClass $row Table row.
     * @return string
     */
    protected function render_group_cell(\stdClass $row): string {
        if (empty($row->groups)) {
            return get_string('group_none', 'local_thlevasys');
        }

        $groupoptions = [0 => get_string('group_none', 'local_thlevasys')];
        foreach ($row->groups as $group) {
            $groupoptions[$group->id] = format_string($group->name);
        }

        return \html_writer::select(
            $groupoptions,
            'group[' . $row->rowkey . ']',
            $row->groupid,
            false,
            [
                'id' => 'group_' . $row->rowkey,
                'class' => 'form-select form-select-sm local-thlevasys-group',
                'data-rowkey' => $row->rowkey,
            ]
        );
    }

    /**
     * @param \stdClass $row Table row.
     * @return string
     */
    protected function render_language_cell(\stdClass $row): string {
        return \html_writer::select(
            [
                'de' => get_string('lang_de_short', 'local_thlevasys'),
                'en' => get_string('lang_en_short', 'local_thlevasys'),
            ],
            'language[' . $row->rowkey . ']',
            $row->lang,
            false,
            [
                'id' => 'language_' . $row->rowkey,
                'class' => 'form-select form-select-sm local-thlevasys-language',
                'data-rowkey' => $row->rowkey,
                'title' => get_string('col_language', 'local_thlevasys'),
            ]
        );
    }

    /**
     * @param \stdClass $row Table row.
     * @return string
     */
    protected function render_select_cell(\stdClass $row): string {
        return \html_writer::div(
            \html_writer::checkbox(
                'selected[' . $row->rowkey . ']',
                1,
                (bool) $row->selected,
                '',
                [
                    'id' => 'selected_' . $row->rowkey,
                    'class' => 'form-check-input local-thlevasys-select',
                    'data-courseid' => $row->courseid,
                    'data-editingteacher' => $row->teacherid,
                    'data-rowkey' => $row->rowkey,
                ]
            ),
            'form-check form-check-inline m-0'
        );
    }
}
