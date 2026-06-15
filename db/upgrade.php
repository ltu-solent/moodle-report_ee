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
 * Upgrade steps for External examiner feedback
 *
 * Documentation: {@link https://moodledev.io/docs/guides/upgrade}
 *
 * @package    report_ee
 * @category   upgrade
 * @copyright  2026 Southampton Solent University {@link https://www.solent.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Execute the plugin upgrade steps from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_report_ee_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();
    if ($oldversion < 2026061500) {
        // Change [course] field to [courseid] in the report_ee table to match install.xml.
        $table = new xmldb_table('report_ee');
        $field = new xmldb_field('course', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'courseid');
        }
        // Change [user, assign, sample, level, national] field names to
        // [userid, assignid, samplestatus, levelstatus, nationalstatus] in the report_ee_assign table to match install.xml.
        $table = new xmldb_table('report_ee_assign');
        $field = new xmldb_field('report', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'reportid');
        }
        $field = new xmldb_field('user', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'userid');
        }
        $field = new xmldb_field('assign', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'assignid');
        }
        $field = new xmldb_field('sample', XMLDB_TYPE_INTEGER, '4');
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'samplestatus');
        }
        $field = new xmldb_field('level', XMLDB_TYPE_INTEGER, '4');
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'levelstatus');
        }
        $field = new xmldb_field('national', XMLDB_TYPE_INTEGER, '4');
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'nationalstatus');
        }
        upgrade_plugin_savepoint(true, 2026061500, 'report', 'ee');
    }

    return true;
}
