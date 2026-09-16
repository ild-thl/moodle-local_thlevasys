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
 * Download table contents as Excel-friendly CSV.
 *
 * Independent of the EvaSys XML export – only mirrors the visible table data.
 * Uses Moodle csv_export_writer with semicolon separator and UTF-8 BOM so
 * German Excel opens values in separate columns.
 *
 * @package    local_thlevasys
 * @copyright  2026 Jan Rieger <jan.rieger@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_OUTPUT_BUFFERING', true);

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/csvlib.class.php');

require_login();
require_sesskey();

/**
 * Send an Excel-friendly CSV download (semicolon + UTF-8 BOM) and exit.
 *
 * @param string $filenamebase Filename prefix without extension.
 * @param string[] $headers Column headers (display labels).
 * @param iterable $rows Row objects/arrays.
 * @param callable $mapper Maps each row to a list of cell values (same order as headers).
 */
function local_thlevasys_download_excel_csv(string $filenamebase, array $headers, iterable $rows, callable $mapper): void {
    $csv = new csv_export_writer('semicolon', '"', 'application/download', true);
    $csv->set_filename($filenamebase);
    $csv->add_data($headers);

    foreach ($rows as $row) {
        $csv->add_data($mapper($row));
    }

    $csv->download_file();
}

$scope = required_param('scope', PARAM_ALPHA);

if ($scope === 'admin') {
    if (!\local_thlevasys\access::is_evaluation_admin()) {
        throw new moodle_exception('error_requestnotavailable', 'local_thlevasys');
    }

    $search = optional_param('search', '', PARAM_RAW_TRIMMED);

    $columns = [
        'courseid' => get_string('col_courseid', 'local_thlevasys'),
        'teachername' => get_string('col_teacher', 'local_thlevasys'),
        'teacheremail' => get_string('col_teacher_email', 'local_thlevasys'),
        'coursename' => get_string('col_coursename', 'local_thlevasys'),
        'courseidnumber' => get_string('col_courseidnumber', 'local_thlevasys'),
        'studiengang' => get_string('col_studiengang', 'local_thlevasys'),
        'participantcount' => get_string('col_participants', 'local_thlevasys'),
        'groupid' => get_string('col_groupid', 'local_thlevasys'),
        'langlabel' => get_string('col_language', 'local_thlevasys'),
    ];

    $rows = \local_thlevasys\request_helper::get_admin_table_rows();
    $rows = \local_thlevasys\request_helper::filter_table_rows_by_search($rows, trim($search), array_keys($columns));
    $rows = \local_thlevasys\request_helper::sort_table_rows($rows, ['coursename' => SORT_ASC]);

    local_thlevasys_download_excel_csv(
        'thlevasys_admin_requests',
        array_values($columns),
        $rows,
        static function(\stdClass $row): array {
            return [
                $row->courseid,
                $row->teachername,
                $row->teacheremail,
                $row->coursename,
                $row->courseidnumber,
                $row->studiengang,
                $row->participantcount,
                $row->groupid,
                $row->langlabel,
            ];
        }
    );
}

if ($scope === 'request') {
    global $USER;

    if (!\local_thlevasys\access::can_view_request_navigation()) {
        throw new moodle_exception('error_requestnotavailable', 'local_thlevasys');
    }
    if (\local_thlevasys\access::is_evaluation_admin()) {
        throw new moodle_exception('error_requestnotavailable', 'local_thlevasys');
    }
    if (!\local_thlevasys\access::can_submit_request_now()) {
        throw new moodle_exception('error_outside_requestperiod', 'local_thlevasys');
    }

    $categoryid = optional_param('categoryid', 0, PARAM_INT);
    $search = optional_param('search', '', PARAM_RAW_TRIMMED);
    if ($categoryid && !\local_thlevasys\request_helper::can_request_in_category($categoryid)) {
        throw new moodle_exception('error_requestnotavailable', 'local_thlevasys');
    }

    $columns = [
        get_string('col_courseid', 'local_thlevasys'),
        get_string('col_courseidnumber', 'local_thlevasys'),
        get_string('col_coursename', 'local_thlevasys'),
        get_string('col_teacher', 'local_thlevasys'),
        get_string('col_participants', 'local_thlevasys'),
        get_string('col_group', 'local_thlevasys'),
        get_string('col_language', 'local_thlevasys'),
        get_string('col_select', 'local_thlevasys'),
    ];

    $rows = \local_thlevasys\request_helper::get_table_rows($categoryid);
    $existing = \local_thlevasys\request_repository::get_requests_for_user((int) $USER->id);
    $rows = (new \local_thlevasys\output\request_table())->enrich_rows_for_sorting($rows, $existing);
    foreach ($rows as $row) {
        $row->langlabel = \local_thlevasys\request_helper::format_language_label($row->lang);
        $row->selectedlabel = !empty($row->selected) ? get_string('yes') : get_string('no');
    }
    $rows = \local_thlevasys\request_helper::filter_table_rows_by_search($rows, trim($search), [
        'courseid',
        'courseidnumber',
        'coursename',
        'teachername',
        'participantcount',
        'groupname',
        'langlabel',
        'selectedlabel',
    ]);
    $rows = \local_thlevasys\request_helper::sort_table_rows($rows, ['coursename' => SORT_ASC]);

    local_thlevasys_download_excel_csv(
        'thlevasys_requests',
        $columns,
        $rows,
        static function(\stdClass $row): array {
            $langlabel = ($row->lang === 'en')
                ? get_string('lang_en_short', 'local_thlevasys')
                : get_string('lang_de_short', 'local_thlevasys');

            return [
                $row->courseid,
                $row->courseidnumber,
                $row->coursename,
                $row->teachername,
                $row->participantcount,
                $row->groupname,
                $langlabel,
                !empty($row->selected) ? get_string('yes') : get_string('no'),
            ];
        }
    );
}

throw new moodle_exception('error_requestnotavailable', 'local_thlevasys');
