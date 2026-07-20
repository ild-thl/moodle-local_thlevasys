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
 * Admin setting for a calendar date stored as unix timestamp.
 *
 * @package    local_thlevasys
 * @copyright  2026 Jan Rieger <jan.rieger@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_thlevasys;

defined('MOODLE_INTERNAL') || die();

/**
 * Date picker admin setting (HTML5 date input, stored as unix timestamp).
 */
class admin_setting_configdate extends \admin_setting {

    /** @var bool If true, store end of the selected day (23:59:59). */
    protected bool $endofday;

    /**
     * Constructor.
     *
     * @param string $name Unique ascii name ('local_thlevasys/mysetting').
     * @param \lang_string|string $visiblename Localised name.
     * @param \lang_string|string $description Localised description.
     * @param mixed $defaultsetting Default unix timestamp or 0.
     * @param bool $endofday Store 23:59:59 of the selected day.
     */
    public function __construct($name, $visiblename, $description, $defaultsetting, bool $endofday = false) {
        $this->endofday = $endofday;
        parent::__construct($name, $visiblename, $description, $defaultsetting);
    }

    /**
     * Get the stored unix timestamp.
     *
     * @return int|null
     */
    public function get_setting() {
        $result = $this->config_read($this->name);
        if (is_null($result)) {
            return null;
        }
        return (int) $result;
    }

    /**
     * Store the date as unix timestamp.
     *
     * @param string $data Date string in Y-m-d format, or empty.
     * @return string Empty string on success, error message otherwise.
     */
    public function write_setting($data) {
        $data = trim((string) $data);

        if ($data === '') {
            return ($this->config_write($this->name, 0) ? '' : get_string('errorsetting', 'admin'));
        }

        $timezone = \core_date::get_server_timezone_object();
        $datetime = \DateTime::createFromFormat('Y-m-d', $data, $timezone);
        if (!$datetime || $datetime->format('Y-m-d') !== $data) {
            return get_string('error_invaliddate', 'local_thlevasys');
        }

        if ($this->endofday) {
            $datetime->setTime(23, 59, 59);
        } else {
            $datetime->setTime(0, 0, 0);
        }

        $timestamp = $datetime->getTimestamp();

        // Keep from/to chronologically ordered when both are set.
        // After parse_setting_name(), $this->name is the key without the plugin prefix.
        if ($this->name === 'requestperiod_to') {
            $from = (int) get_config('local_thlevasys', 'requestperiod_from');
            if ($from > 0 && $timestamp < $from) {
                return get_string('error_requestperiodorder', 'local_thlevasys');
            }
        } else if ($this->name === 'requestperiod_from') {
            $to = (int) get_config('local_thlevasys', 'requestperiod_to');
            if ($to > 0 && $timestamp > $to) {
                return get_string('error_requestperiodorder', 'local_thlevasys');
            }
        }

        return ($this->config_write($this->name, $timestamp) ? '' : get_string('errorsetting', 'admin'));
    }

    /**
     * Output the date field.
     *
     * @param mixed $data Current unix timestamp.
     * @param string $query Search query.
     * @return string HTML
     */
    public function output_html($data, $query = '') {
        $default = $this->get_defaultsetting();
        $defaultinfo = null;
        if (!empty($default)) {
            $defaultinfo = userdate((int) $default, get_string('strftimedate', 'langconfig'));
        }

        $value = '';
        if (!empty($data)) {
            $datetime = (new \DateTime('@' . (int) $data))->setTimezone(\core_date::get_server_timezone_object());
            $value = $datetime->format('Y-m-d');
        }

        $attributes = [
            'type' => 'date',
            'id' => $this->get_id(),
            'name' => $this->get_full_name(),
            'value' => $value,
            'class' => 'form-control',
        ];

        $return = \html_writer::empty_tag('input', $attributes);
        return format_admin_setting($this, $this->visiblename, $return, $this->description, true, '', $defaultinfo, $query);
    }
}
