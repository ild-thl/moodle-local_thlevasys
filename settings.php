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
 * Plugin settings for local_thlevasys.
 *
 * Accessible to users with local/thlevasys:managesettings (Evaluation admin)
 * and to site administrators.
 *
 * @package   local_thlevasys
 * @copyright 2026 Jan Rieger <jan.rieger@th-luebeck.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$systemcontext = context_system::instance();

if (has_capability('local/thlevasys:managesettings', $systemcontext)) {
    $settings = new admin_settingpage(
        'local_thlevasys',
        new lang_string('settings', 'local_thlevasys'),
        'local/thlevasys:managesettings'
    );

    if ($ADMIN->fulltree) {
        $settings->add(new admin_setting_heading(
            'local_thlevasys/requestperiodheading',
            new lang_string('requestperiod', 'local_thlevasys'),
            new lang_string('requestperiod_desc', 'local_thlevasys')
        ));

        $settings->add(new \local_thlevasys\admin_setting_configdate(
            'local_thlevasys/requestperiod_from',
            new lang_string('requestperiod_from', 'local_thlevasys'),
            new lang_string('requestperiod_from_desc', 'local_thlevasys'),
            0,
            false
        ));

        $settings->add(new \local_thlevasys\admin_setting_configdate(
            'local_thlevasys/requestperiod_to',
            new lang_string('requestperiod_to', 'local_thlevasys'),
            new lang_string('requestperiod_to_desc', 'local_thlevasys'),
            0,
            true
        ));
    }

    // localplugins exists only for users with moodle/site:config.
    if ($ADMIN->locate('localplugins')) {
        $ADMIN->add('localplugins', $settings);
    } else {
        $ADMIN->add('root', $settings);
    }
}
