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
 * Installation and upgrade helpers for local_thlevasys.
 *
 * @package    local_thlevasys
 * @copyright  2026 Jan Rieger <jan.rieger@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_thlevasys;

defined('MOODLE_INTERNAL') || die();

/**
 * Ensures plugin roles exist and have the intended capabilities.
 */
class setup {

    /** @var string Shortname for the evaluation officer role. */
    public const ROLE_EVALUATIONOFFICER = 'thlevasys_evaluationofficer';

    /** @var string Shortname for the evaluation admin role. */
    public const ROLE_EVALUATIONADMIN = 'thlevasys_evaluationadmin';

    /**
     * Create plugin roles if missing and assign their capabilities.
     *
     * Safe to call repeatedly (install and upgrade). Calls update_capabilities()
     * first because install.php / upgrade.php run before Moodle registers caps.
     */
    public static function ensure_roles(): void {
        global $DB;

        update_capabilities('local_thlevasys');

        $systemcontext = \context_system::instance();

        $roles = [
            self::ROLE_EVALUATIONOFFICER => [
                'name' => get_string('role_evaluationofficer', 'local_thlevasys'),
                'description' => get_string('role_evaluationofficer_desc', 'local_thlevasys'),
                'capability' => 'local/thlevasys:requestevaluation',
                'contextlevels' => [
                    CONTEXT_SYSTEM,
                    CONTEXT_COURSECAT,
                ],
            ],
            self::ROLE_EVALUATIONADMIN => [
                'name' => get_string('role_evaluationadmin', 'local_thlevasys'),
                'description' => get_string('role_evaluationadmin_desc', 'local_thlevasys'),
                'capability' => 'local/thlevasys:managesettings',
                'contextlevels' => [
                    CONTEXT_SYSTEM,
                ],
            ],
        ];

        foreach ($roles as $shortname => $definition) {
            $roleid = $DB->get_field('role', 'id', ['shortname' => $shortname]);
            if (!$roleid) {
                $roleid = create_role(
                    $definition['name'],
                    $shortname,
                    $definition['description']
                );
            }

            set_role_contextlevels($roleid, $definition['contextlevels']);
            assign_capability(
                $definition['capability'],
                CAP_ALLOW,
                $roleid,
                $systemcontext->id,
                true
            );
        }
    }
}
