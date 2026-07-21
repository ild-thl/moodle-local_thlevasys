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
 * Hook callbacks for local_thlevasys.
 *
 * @package    local_thlevasys
 * @copyright  2026 Jan Rieger <jan.rieger@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_thlevasys;

defined('MOODLE_INTERNAL') || die();

/**
 * Callbacks registered via db/hooks.php.
 */
class hook_callbacks {

    /**
     * Add the evaluation request link to the primary navigation.
     *
     * @param \core\hook\navigation\primary_extend $hook Hook payload.
     */
    public static function extend_primary_navigation(\core\hook\navigation\primary_extend $hook): void {
        if (during_initial_install()) {
            return;
        }

        if (!get_config('local_thlevasys', 'version')) {
            return;
        }

        if (!isloggedin() || isguestuser()) {
            return;
        }

        if (!access::can_view_request_navigation()) {
            return;
        }

        $view = $hook->get_primaryview();
        $view->add(
            get_string('requestevaluation', 'local_thlevasys'),
            new \moodle_url('/local/thlevasys/request.php'),
            \navigation_node::TYPE_CUSTOM,
            null,
            'thlevasys_requestevaluation'
        );
    }
}
