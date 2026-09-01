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
 * Admin overview of submitted evaluation requests.
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
 * Renders the admin overview table of submitted evaluation requests.
 */
class admin_request_table {

    /**
     * Render the admin overview table.
     *
     * @return string HTML
     */
    public function render(string $search = ''): string {
        global $OUTPUT;

        $html = '';
        $search = trim($search);
        $bounds = \local_thlevasys\access::get_request_period_bounds();
        if ($bounds === null) {
            $html .= $OUTPUT->notification(get_string('error_requestperiodnotconfigured', 'local_thlevasys'), 'warning');
            return $html;
        }

        $html .= $OUTPUT->notification(
            get_string(
                'admin_requestperiod_info',
                'local_thlevasys',
                (object) [
                    'from' => userdate($bounds['from'], get_string('strftimedatefullshort', 'langconfig')),
                    'to' => userdate($bounds['to'], get_string('strftimedatefullshort', 'langconfig')),
                ]
            ),
            'info'
        );

        $html .= export_options::render();

        $baseurl = new \moodle_url('/local/thlevasys/admin_requests.php');
        $tablebaseurl = clone $baseurl;
        if ($search !== '') {
            $baseurl->param('search', $search);
        }

        $html .= table_search::render($tablebaseurl, $search, [], true);

        $rows = \local_thlevasys\request_helper::get_admin_table_rows();
        if (empty($rows) && $search === '') {
            $html .= $OUTPUT->notification(get_string('admin_requesttable_empty', 'local_thlevasys'), 'info');
            return $html;
        }

        $rows = \local_thlevasys\request_helper::filter_table_rows_by_search($rows, $search, [
            'courseid',
            'coursename',
            'teachername',
            'courseidnumber',
            'participantcount',
            'groupid',
            'langlabel',
        ]);

        if (empty($rows)) {
            $html .= $OUTPUT->notification(get_string('requesttable_search_empty', 'local_thlevasys'), 'info');
            return $html;
        }

        $table = new bottom_paging_table('local-thlevasys-admin-requests');
        $table->define_columns([
            'courseid',
            'coursename',
            'teachername',
            'courseidnumber',
            'participantcount',
            'groupid',
            'langlabel',
        ]);
        $table->define_headers([
            get_string('col_courseid', 'local_thlevasys'),
            get_string('col_coursename', 'local_thlevasys'),
            get_string('col_teacher', 'local_thlevasys'),
            get_string('col_courseidnumber', 'local_thlevasys'),
            get_string('col_participants', 'local_thlevasys'),
            get_string('col_groupid', 'local_thlevasys'),
            get_string('col_language', 'local_thlevasys'),
        ]);
        $table->define_baseurl($baseurl);
        $table->sortable(true, 'coursename', SORT_ASC);
        $table->collapsible(false);
        $table->attributes['class'] = 'generaltable local-thlevasys-admin-request-table';
        $table->attributes['id'] = 'local-thlevasys-admin-request-table';
        $table->setup();

        $rows = \local_thlevasys\request_helper::sort_table_rows($rows, $table->get_sort_columns());
        $table->pagesize(10, count($rows));
        $rows = array_slice($rows, $table->get_page_start(), $table->get_page_size());

        foreach ($rows as $row) {
            $table->add_data([
                $row->courseid,
                $this->render_course_link($row),
                $this->render_teacher_link($row),
                $row->courseidnumber,
                $row->participantcount,
                $row->groupid,
                $row->langlabel,
            ]);
        }

        ob_start();
        $table->finish_output();
        $html .= \html_writer::div(ob_get_clean(), 'local-thlevasys-request-wrapper');

        return $html;
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
    protected function render_teacher_link(\stdClass $row): string {
        return \html_writer::link(
            new \moodle_url('/user/view.php', ['id' => $row->teacherid, 'course' => $row->courseid]),
            $row->teachername
        );
    }
}
