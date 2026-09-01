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
 * Export option fields for the admin overview (preparation for XML export).
 *
 * @package    local_thlevasys
 * @copyright  2026 Jan Rieger <jan.rieger@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_thlevasys\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Renders export option fields for the EvaSys XML export.
 */
class export_options {

    /** @var string HTML form id used to associate fields and the submit button. */
    public const FORM_ID = 'local-thlevasys-export-form';

    /**
     * Render export option fields above the admin table.
     *
     * @return string HTML
     */
    public static function render(): string {
        $now = self::format_datetime_local_value(time());

        $html = self::render_form_opening();
        $html .= \html_writer::start_div('local-thlevasys-export-options mb-4', [
            'data-region' => 'local-thlevasys-export-options',
        ]);
        $html .= \html_writer::tag('h3', get_string('export_settings_heading', 'local_thlevasys'), [
            'class' => 'h5',
        ]);

        $html .= \html_writer::start_div('local-thlevasys-export-options-grid');

        $html .= self::render_datetime_field('evaluationstart', get_string('export_evalstart', 'local_thlevasys'), $now);
        $html .= self::render_datetime_field('evaluationreminder', get_string('export_evalreminder', 'local_thlevasys'), $now);
        $html .= self::render_datetime_field('evaluationend', get_string('export_evalend', 'local_thlevasys'), $now);
        $html .= self::render_text_field('semester', get_string('export_semester', 'local_thlevasys'));
        $html .= self::render_text_field('questionnairede', get_string('export_questionnaire_de', 'local_thlevasys'));
        $html .= self::render_text_field('questionnaireen', get_string('export_questionnaire_en', 'local_thlevasys'));

        $html .= \html_writer::end_div();
        $html .= \html_writer::end_div();

        return $html;
    }

    /**
     * Render the hidden POST form shell (fields are associated via the form attribute).
     *
     * @return string HTML
     */
    protected static function render_form_opening(): string {
        $exporturl = new \moodle_url('/local/thlevasys/export_evasys.php');

        $html = \html_writer::start_tag('form', [
            'method' => 'post',
            'action' => $exporturl->out(false),
            'id' => self::FORM_ID,
            'class' => 'd-none',
        ]);
        $html .= \html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'sesskey',
            'value' => sesskey(),
        ]);
        $html .= \html_writer::end_tag('form');

        return $html;
    }

    /**
     * @param string $name Field name.
     * @param string $label Field label.
     * @param string $value Default value.
     * @return string HTML
     */
    protected static function render_datetime_field(string $name, string $label, string $value): string {
        $id = 'local-thlevasys-export-' . $name;

        $html = \html_writer::start_div('local-thlevasys-export-field');
        $html .= \html_writer::tag('label', $label, [
            'for' => $id,
            'class' => 'form-label',
        ]);
        $html .= \html_writer::empty_tag('input', [
            'type' => 'datetime-local',
            'name' => $name,
            'id' => $id,
            'value' => $value,
            'class' => 'form-control local-thlevasys-export-input',
            'form' => self::FORM_ID,
        ]);
        $html .= \html_writer::end_div();

        return $html;
    }

    /**
     * @param string $name Field name.
     * @param string $label Field label.
     * @return string HTML
     */
    protected static function render_text_field(string $name, string $label): string {
        $id = 'local-thlevasys-export-' . $name;

        $html = \html_writer::start_div('local-thlevasys-export-field');
        $html .= \html_writer::tag('label', $label, [
            'for' => $id,
            'class' => 'form-label',
        ]);
        $html .= \html_writer::empty_tag('input', [
            'type' => 'text',
            'name' => $name,
            'id' => $id,
            'value' => '',
            'class' => 'form-control local-thlevasys-export-input',
            'form' => self::FORM_ID,
        ]);
        $html .= \html_writer::end_div();

        return $html;
    }

    /**
     * Format a unix timestamp for HTML datetime-local inputs.
     *
     * @param int $timestamp Unix timestamp.
     * @return string
     */
    protected static function format_datetime_local_value(int $timestamp): string {
        $datetime = new \DateTime('@' . $timestamp);
        $datetime->setTimezone(\core_date::get_user_timezone_object());

        return $datetime->format('Y-m-d\TH:i');
    }
}
