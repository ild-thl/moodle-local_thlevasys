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
 * Search form for evaluation request tables.
 *
 * @package    local_thlevasys
 * @copyright  2026 Jan Rieger <jan.rieger@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_thlevasys\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Renders a GET search form above request tables.
 */
class table_search {

    /**
     * Render the search form.
     *
     * @param \moodle_url $actionurl Form action URL (without search param).
     * @param string $search Current search term.
     * @param array $hiddenparams Additional hidden GET parameters.
     * @param bool $showexportlink Whether to show the EvaSys XML export link.
     * @return string HTML
     */
    public static function render(
        \moodle_url $actionurl,
        string $search,
        array $hiddenparams = [],
        bool $showexportlink = false
    ): string {
        $formid = 'local-thlevasys-table-search';
        $html = \html_writer::start_tag('form', [
            'method' => 'get',
            'action' => $actionurl->out(false),
            'class' => 'local-thlevasys-table-search mb-3',
            'id' => $formid,
        ]);

        foreach ($hiddenparams as $name => $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            $html .= \html_writer::empty_tag('input', [
                'type' => 'hidden',
                'name' => $name,
                'value' => $value,
            ]);
        }

        $html .= \html_writer::start_div('d-flex flex-wrap align-items-center');
        if ($showexportlink) {
            $csvurl = new \moodle_url('/local/thlevasys/export_csv.php', [
                'scope' => 'admin',
                'sesskey' => sesskey(),
            ]);
            if ($search !== '') {
                $csvurl->param('search', $search);
            }
            $html .= \html_writer::link(
                $csvurl,
                get_string('export_table_csv', 'local_thlevasys'),
                [
                    'class' => 'btn btn-secondary',
                    'style' => 'margin-left: 0.25rem; margin-right: 0.5rem;',
                ]
            );
            $html .= \html_writer::empty_tag('input', [
                'type' => 'submit',
                'class' => 'btn btn-secondary me-3',
                'form' => export_options::FORM_ID,
                'value' => get_string('export_evasys_xml', 'local_thlevasys'),
                'style' => 'margin-right: 1rem;',
            ]);
        }
        $html .= \html_writer::empty_tag('input', [
            'type' => 'search',
            'name' => 'search',
            'id' => $formid . '-input',
            'value' => $search,
            'class' => 'form-control me-2',
            'placeholder' => get_string('search_placeholder', 'local_thlevasys'),
            'aria-label' => get_string('search_label', 'local_thlevasys'),
            'style' => 'width: 14rem; max-width: 14rem; flex: 0 0 14rem;',
        ]);
        $html .= \html_writer::empty_tag('input', [
            'type' => 'submit',
            'class' => 'btn btn-secondary',
            'value' => get_string('search_submit', 'local_thlevasys'),
        ]);
        $html .= \html_writer::end_div();
        $html .= \html_writer::end_tag('form');

        if ($search !== '') {
            $clearurl = clone $actionurl;
            $clearurl->remove_params(['search', 'page']);
            $html .= \html_writer::div(
                \html_writer::link(
                    $clearurl,
                    get_string('search_clear', 'local_thlevasys'),
                    ['class' => 'btn btn-link ps-0']
                ),
                'local-thlevasys-table-search-clear mb-3'
            );
        }

        return $html;
    }
}
