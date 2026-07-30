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
 * External function to toggle an evaluation request.
 *
 * @package    local_thlevasys
 * @copyright  2026 Jan Rieger <jan.rieger@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_thlevasys\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_thlevasys\request_repository;

defined('MOODLE_INTERNAL') || die();

/**
 * Toggle evaluation request record for the current user.
 */
class toggle_request extends external_api {

    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id'),
            'editingteacher' => new external_value(PARAM_INT, 'Editing teacher user id'),
            'groupid' => new external_value(PARAM_INT, 'Course group id, 0 if none'),
            'lang' => new external_value(PARAM_ALPHANUMEXT, 'Language code (de|en)'),
            'selected' => new external_value(PARAM_BOOL, 'Whether the request should exist'),
        ]);
    }

    /**
     * Create or delete the request.
     *
     * @param int $courseid Course id.
     * @param int $editingteacher Teacher user id.
     * @param int $groupid Group id or 0.
     * @param string $lang Language code.
     * @param bool $selected True to create/update, false to delete.
     * @return array
     */
    public static function execute(
        int $courseid,
        int $editingteacher,
        int $groupid,
        string $lang,
        bool $selected
    ): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'editingteacher' => $editingteacher,
            'groupid' => $groupid,
            'lang' => $lang,
            'selected' => $selected,
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_login();

        if ($params['selected']) {
            $id = request_repository::add_request(
                $params['courseid'],
                $params['editingteacher'],
                $params['groupid'],
                $params['lang'],
                (int) $USER->id
            );
            return [
                'success' => true,
                'selected' => true,
                'id' => $id,
            ];
        }

        request_repository::delete_request(
            $params['courseid'],
            $params['editingteacher'],
            (int) $USER->id
        );

        return [
            'success' => true,
            'selected' => false,
            'id' => 0,
        ];
    }

    /**
     * Return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the operation succeeded'),
            'selected' => new external_value(PARAM_BOOL, 'Whether a request record exists afterwards'),
            'id' => new external_value(PARAM_INT, 'Request record id, 0 if deleted'),
        ]);
    }
}
