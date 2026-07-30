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

/**
 * Renders filter and request table markup.
 */
class request_table {

    /**
     * Render category filter and request table.
     *
     * @param int $filtercategoryid Currently selected category id (0 = all).
     * @return string HTML
     */
    public function render(int $filtercategoryid = 0): string {
        global $OUTPUT;

        $categories = \local_thlevasys\request_helper::get_filter_categories();
        $html = '';

        $filteroptions = [0 => get_string('filter_allcategories', 'local_thlevasys')] + $categories;
        if (!isset($filteroptions[$filtercategoryid])) {
            $filtercategoryid = 0;
        }

        $select = new \single_select(
            new \moodle_url('/local/thlevasys/request.php'),
            'categoryid',
            $filteroptions,
            $filtercategoryid,
            null
        );
        $select->set_label(get_string('filter_category', 'local_thlevasys'));
        $select->class = 'local-thlevasys-category-filter mb-3';
        $html .= $OUTPUT->render($select);

        $rows = \local_thlevasys\request_helper::get_table_rows($filtercategoryid);
        if (empty($rows)) {
            $html .= $OUTPUT->notification(get_string('requesttable_empty', 'local_thlevasys'), 'info');
            return $html;
        }

        $table = new \html_table();
        $table->attributes['class'] = 'generaltable local-thlevasys-request-table';
        $table->id = 'local-thlevasys-request-table';
        $table->head = [
            get_string('col_courseid', 'local_thlevasys'),
            get_string('col_coursename', 'local_thlevasys'),
            get_string('col_teacher', 'local_thlevasys'),
            get_string('col_participants', 'local_thlevasys'),
            get_string('col_group', 'local_thlevasys'),
            get_string('col_language', 'local_thlevasys'),
            get_string('col_select', 'local_thlevasys'),
        ];

        $sesskey = sesskey();
        $html .= \html_writer::start_tag('form', [
            'method' => 'post',
            'action' => (new \moodle_url('/local/thlevasys/request.php'))->out(false),
            'id' => 'local-thlevasys-request-form',
        ]);
        $html .= \html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'sesskey',
            'value' => $sesskey,
        ]);
        $html .= \html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'categoryid',
            'value' => $filtercategoryid,
        ]);

        foreach ($rows as $row) {
            if (empty($row->groups)) {
                $groupselect = get_string('group_none', 'local_thlevasys');
            } else {
                $groupoptions = [0 => get_string('group_none', 'local_thlevasys')];
                foreach ($row->groups as $group) {
                    $groupoptions[$group->id] = format_string($group->name);
                }
                $groupselect = \html_writer::select(
                    $groupoptions,
                    'group[' . $row->rowkey . ']',
                    0,
                    false,
                    ['id' => 'group_' . $row->rowkey, 'class' => 'form-select']
                );
            }

            $languageselect = \html_writer::select(
                [
                    'de' => get_string('lang_de', 'local_thlevasys'),
                    'en' => get_string('lang_en', 'local_thlevasys'),
                ],
                'language[' . $row->rowkey . ']',
                'de',
                false,
                ['id' => 'language_' . $row->rowkey, 'class' => 'form-select']
            );

            $checkbox = \html_writer::checkbox(
                'selected[' . $row->rowkey . ']',
                1,
                false,
                '',
                ['id' => 'selected_' . $row->rowkey, 'class' => 'form-check-input']
            );

            $table->data[] = [
                $row->courseid,
                $row->coursename,
                $row->teachername,
                $row->participantcount,
                $groupselect,
                $languageselect,
                $checkbox,
            ];
        }

        $html .= \html_writer::table($table);
        $html .= \html_writer::end_tag('form');

        return $html;
    }
}
