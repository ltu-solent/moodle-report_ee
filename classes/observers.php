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

use assign;
use context_course;
use file_storage;
use local_solsits\sitsassign;
use stdClass;

/**
 * Class observers
 *
 * @package    report_ee
 * @copyright  2024 Solent University {@link https://www.solent.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observers {
    public static function submission_graded(\mod_assign\event\submission_graded $event) {
        global $DB;
        $cmid = $event->contextinstanceid;
        // We're only doing this for summative assignments.
        $issummative = \local_solsits\helper::is_summative_assignment($cmid);
        if (!$issummative) {
            return;
        }

        $cm = get_fast_modinfo($event->courseid)->get_cm($event->contextinstanceid);
        $issitsassign = (\local_solsits\helper::is_sits_assignment($cmid));
        $userid = $event->relateduserid;
        $grade = $DB->get_record('assign_grades', ['id' => $event->objectid]);
        $course = $cm->get_course();
        $coursecontext = context_course::instance($course->id);
        $submission = $DB->get_record('assign_submission', [
            'assignment' => $cm->instance,
            'userid' => $userid,
            'attemptnumber' => $grade->attemptnumber,
        ]);
        $sample = $DB->get_record('assignfeedback_sample', [
            'assignment' => $cm->instance,
            'grade' => $grade->id,
        ]);
        // No sample, do nothing or remove sample? It can still be manually removed, but helps prevent accidents keeping it.
        if (!$sample) {
            return;
        }
        // I only want to store a file if it has been marked as a Sample.
        // I might want to check if I'm removing the sample.
        // I'm getting this after the event, so I have no way of knowing if this is something that's changed.
        $fs = get_file_storage();
        $samplefiles = $fs->get_area_files($event->contextid, 'assignsubmission_file', 'submission_files', $submission->id, 'itemid, filepath, filename', false);
        $reportfiles = $fs->get_area_files($coursecontext->id, 'report_ee', 'samples', false, 'itemid, filepath, filename', true);
        $assignpath = '';
        $args = [
            'contextid' => $coursecontext->id,
            'component' => 'report_ee',
            'filearea' => 'samples',
            'itemid' => $course->id,
        ];
        $paths = [
            'sample' => '/Samples and Internal Moderation Records/',
            'resits' => '/Resit samples and Internal Moderation Records/',
            'briefs' => '/All briefs and peer reviews/',
        ];
        // Double check the file structure is in place - it may not be if the report hasn't been viewed yet.
        $pathstructs = $paths;
        foreach ($reportfiles as $reportfile) {
            if (!$reportfile->is_directory()) {
                continue;
            }
            foreach ($pathstructs as $key => $struct) {
                if ($reportfile->get_filepath() == $struct) {
                    unset($key);
                }
            }
        }
        foreach ($pathstructs as $struct) {
            $fs->create_directory(...array_merge($args, ['filepath' => $struct]));
        }

        $student = \core_user::get_user($userid);
        if ($issitsassign) {
            $sitsassign = sitsassign::get_record(['cmid' => $cmid]);
            if ($sitsassign->get('reattempt') > 0) {
                $assignpath = $paths['resits'];
            } else {
                $assignpath = $paths['sample'];
            }
            $assignpath .= str_replace('/', '-', $sitsassign->get('sitsref')) . '/';
        } else {
            $quercusassign = \local_quercus_tasks\api::get_quercus_assignment($cm->instance);
            if ($quercusassign->sitting_desc == 'FIRST_SITTING') {
                $assignpath = $paths['sample'];
            } else {
                $assignpath = $paths['resits'];
            }
            // This will come out as something like PROJ1_2022-FIRST_SITTING.
            $assignpath .= $quercusassign->idnumber . '-' . $quercusassign->sitting_desc . '/';
        }
        // Add student number as a directory.
        if ($student->idnumber != '') {
            $assignpath .= $student->idnumber . '/';
        }
        foreach ($samplefiles as $samplefile) {
            $newfile = new stdClass();
            $newfile->contextid = $coursecontext->id;
            $newfile->component = 'report_ee';
            $newfile->filearea = 'samples';
            $newfile->filepath = $assignpath . $samplefile->get_filepath();
            $newfile->itemid = $event->courseid;
            $fs->create_file_from_storedfile($newfile, $samplefile);
        }
    }
}
