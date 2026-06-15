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

namespace report_ee;

/**
 * Class helper
 *
 * @package    report_ee
 * @author Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright  2023 Solent University {@link https://www.solent.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {
    /**
     * Feedback stypes
     */
    public const FEEDBACK_TYPES = ['levelstatus', 'nationalstatus', 'samplestatus'];

    /**
     * Returns some default data for all the assignments
     *
     * @param int $courseid
     * @return array Formdata
     */
    public static function default_form_data(int $courseid): array {
        $assignments = self::get_assignments($courseid);
        $formdata = [];

        foreach ($assignments as $id => $assignment) {
            foreach (self::FEEDBACK_TYPES as $feedbacktype) {
                $formdata['assign_' . $id . '_' . $feedbacktype] = '';
            }
        }
        $formdata['comments'] = '';
        $formdata['locked'] = 0;
        $formdata['courseid'] = $courseid;
        return $formdata;
    }

    /**
     * Get and/or Quercus and SITS first attempt assignments on this module
     *
     * @param int $courseid
     * @return array
     */
    public static function get_assignments(int $courseid): array {
        global $DB;

        $assignments = $DB->get_records_sql("
            SELECT a.id, a.name, cm.idnumber
            FROM {assign} a
                JOIN {course_modules} cm ON cm.instance = a.id
                JOIN {modules} m ON m.id = cm.module AND m.name = 'assign'
                JOIN {local_quercus_tasks_sittings} s ON s.assign = a.id
            WHERE a.course = :courseid1
                AND cm.idnumber != ''
                AND s.sitting_desc = 'FIRST_SITTING'
            UNION
            SELECT a.id, a.name, cm.idnumber
            FROM {assign} a
                JOIN {course_modules} cm ON cm.instance = a.id
                JOIN {modules} m ON m.id = cm.module AND m.name = 'assign'
                JOIN {local_solsits_assign} s ON s.sitsref = cm.idnumber
            WHERE a.course = :courseid2
                AND s.reattempt = 0", ['courseid1' => $courseid, 'courseid2' => $courseid]);

        return $assignments;
    }
}
