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
 * Category filter form with searchable autocomplete (like course settings).
 *
 * @package    local_thlevasys
 * @copyright  2026 Jan Rieger <jan.rieger@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_thlevasys\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Filter the request table by course category.
 */
class category_filter extends \moodleform {

    /**
     * Form definition.
     */
    public function definition(): void {
        $mform = $this->_form;
        $options = $this->_customdata['options'] ?? [];

        $mform->addElement(
            'autocomplete',
            'categoryid',
            get_string('filter_category', 'local_thlevasys'),
            $options,
            [
                'placeholder' => get_string('search'),
            ]
        );
        $mform->setType('categoryid', PARAM_INT);
        $mform->setDefault('categoryid', 0);
    }
}
