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
 * Data generator class
 *
 * @package    report_ee
 * @category   test
 * @copyright  2023 Solent University {@link https://www.solent.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_ee_generator extends component_generator_base {
    /**
     * Create eefeedback database entry
     *
     * @param array $eefeedback
     * @return void
     */
    public function create_eefeedback($eefeedback) {
        global $DB, $USER;
        $ee = new stdClass();
        $ee->courseid = $eefeedback['courseid'];
        $ee->comments = $eefeedback['comments'] ?? '';
        $ee->locked = $eefeedback['locked'] ?? 0;
        $ee->timemodified = time();
        $eeinstance = $DB->get_record('report_ee', ['courseid' => $eefeedback['courseid']]);
        if (!$eeinstance) {
            $ee->timecreated = time();
            $insertid = $DB->insert_record('report_ee', $ee);
            $ee->id = $insertid;
        } else {
            $ee->id = $eeinstance->id;
            $DB->update_record('report_ee', $ee);
        }
        $eeassign = new stdClass();
        $eeassign->reportid = $ee->id;
        if (isset($eefeedback['modifiedby'])) {
            $eeassign->userid = core_user::get_user_by_username($eefeedback['modifiedby'])->id;
        } else {
            $eeassign->userid = $USER->id;
        }
        $eeassign->assignid = $eefeedback['instanceid'];
        $eeassign->samplestatus = $eefeedback['samplestatus'] ?? 0;
        $eeassign->levelstatus = $eefeedback['levelstatus'] ?? 0;
        $eeassign->nationalstatus = $eefeedback['nationalstatus'] ?? 0;
        $DB->insert_record('report_ee_assign', $eeassign);
    }
}
