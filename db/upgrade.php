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
 * Upgrade steps for local_thlevasys.
 *
 * @package   local_thlevasys
 * @copyright 2026 Jan Rieger <jan.rieger@th-luebeck.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Execute upgrade steps.
 *
 * @param int $oldversion The version we are upgrading from.
 * @return bool Always true.
 */
function xmldb_local_thlevasys_upgrade($oldversion) {
    if ($oldversion < 2026072001) {
        \local_thlevasys\setup::ensure_roles();
        upgrade_plugin_savepoint(true, 2026072001, 'local', 'thlevasys');
    }

    if ($oldversion < 2026072002) {
        // Move requestevaluation to coursecat context; refresh role context levels.
        \local_thlevasys\setup::ensure_roles();
        upgrade_plugin_savepoint(true, 2026072002, 'local', 'thlevasys');
    }

    return true;
}
