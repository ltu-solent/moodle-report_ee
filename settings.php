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
 * This file defines the admin settings for this plugin
 *
 * @package   report_ee
 * @copyright 2020 Solent University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('report_ee', new lang_string('pluginname', 'report_ee'));
    $settings->add(new admin_setting_configtext('report_ee/studentregemail', get_string('studentregemail', 'report_ee'), '', ''));
    $settings->add(new admin_setting_configtext('report_ee/qualityemail', get_string('qualityemail', 'report_ee'), '', ''));
    $settings->add(new admin_setting_configtext('report_ee/moduleleadershortname',
        get_string('moduleleadershortname', 'report_ee'), '', ''));
    $settings->add(new admin_setting_configtext('report_ee/externalexaminershortname',
        get_string('externalexaminershortname', 'report_ee'), '', ''));
    $options = [
        0 => new lang_string('unlimited'),
        5 => '5',
        10 => '10',
        20 => '20',
        30 => '30',
    ];
    $settings->add(new admin_setting_configselect(
        'report_ee/migrateno',
        new lang_string('migrateno', 'report_ee'),
        new lang_string('migrateno_desc', 'report_ee'),
        10,
        $options
    ));
}
