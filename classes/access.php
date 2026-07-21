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
 * Access checks for local_thlevasys.
 *
 * @package    local_thlevasys
 * @copyright  2026 Jan Rieger <jan.rieger@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_thlevasys;

defined('MOODLE_INTERNAL') || die();

/**
 * Role and period checks for evaluation requests.
 */
class access {

    /**
     * Whether the user has the evaluation officer role (any context).
     *
     * @param int|null $userid User id or null for current user.
     * @return bool
     */
    public static function is_evaluation_officer(?int $userid = null): bool {
        return self::user_has_plugin_role(setup::ROLE_EVALUATIONOFFICER, $userid);
    }

    /**
     * Whether the user has the evaluation admin role (any context).
     *
     * @param int|null $userid User id or null for current user.
     * @return bool
     */
    public static function is_evaluation_admin(?int $userid = null): bool {
        return self::user_has_plugin_role(setup::ROLE_EVALUATIONADMIN, $userid);
    }

    /**
     * Whether the primary navigation entry / request page entry should be shown.
     *
     * Visible for evaluation officers and evaluation admins, independent of the request period.
     *
     * @param int|null $userid User id or null for current user.
     * @return bool
     */
    public static function can_view_request_navigation(?int $userid = null): bool {
        return self::is_evaluation_officer($userid) || self::is_evaluation_admin($userid);
    }

    /**
     * Whether the current time is within the configured request period.
     *
     * Both start and end must be configured. The period is inclusive
     * (from day start through to day end as stored in settings).
     *
     * @param int|null $now Optional unix timestamp for testing; defaults to time().
     * @return bool
     */
    public static function is_within_request_period(?int $now = null): bool {
        $from = (int) get_config('local_thlevasys', 'requestperiod_from');
        $to = (int) get_config('local_thlevasys', 'requestperiod_to');

        if ($from <= 0 || $to <= 0) {
            return false;
        }

        $now = $now ?? time();

        return ($now >= $from && $now <= $to);
    }

    /**
     * Whether the user may use the request form right now.
     *
     * Evaluation admins may always proceed. Evaluation officers only within the request period.
     *
     * @param int|null $userid User id or null for current user.
     * @return bool
     */
    public static function can_submit_request_now(?int $userid = null): bool {
        if (!self::can_view_request_navigation($userid)) {
            return false;
        }

        if (self::is_evaluation_admin($userid)) {
            return true;
        }

        return self::is_within_request_period();
    }

    /**
     * Whether the given user has a plugin role assignment somewhere.
     *
     * @param string $shortname Role shortname.
     * @param int|null $userid User id or null for current user.
     * @return bool
     */
    protected static function user_has_plugin_role(string $shortname, ?int $userid = null): bool {
        global $DB, $USER;

        $userid = $userid ?? $USER->id;
        if (empty($userid) || isguestuser($userid)) {
            return false;
        }

        $roleid = $DB->get_field('role', 'id', ['shortname' => $shortname]);
        if (!$roleid) {
            return false;
        }

        return user_has_role_assignment($userid, $roleid, 0);
    }
}
