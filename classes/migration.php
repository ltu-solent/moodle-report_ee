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

use context_course;
use context_module;
use html_writer;
use moodle_url;
use stdClass;

/**
 * Class migration
 *
 * @package    report_ee
 * @copyright  2024 Solent University {@link https://www.solent.ac.uk}
 * @author Mark Sharp <mark.sharp@solent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class migration {
    /**
     * Get all Folder activities that look like EE folders.
     *
     * @return array
     */
    public static function get_folders_to_migrate():array {
        global $DB;
        // There are ~27k folders in production, so we may need to chunk this.
        $folders = $DB->get_records_sql("
            SELECT f.*, cm.visible, cm.section, cm.id cmid, cs.sequence, cs.section as sectionnum
            FROM {folder} f
            JOIN {course_modules} cm ON cm.instance = f.id AND cm.module = (SELECT id FROM {modules} WHERE name = 'folder')
            JOIN {course_sections} cs ON cs.id = cm.section
            WHERE f.name LIKE '%External Examiner%'
        ");
        return $folders;
    }

    /**
     * Move EE folder files to the EE report file area for EE folders
     *
     * @param object $folder
     * @return void
     */
    public static function move_folder_files_to_reportee($folder) {
        global $DB;
        $oldcontext = context_module::instance($folder->cmid);
        $newcontext = context_course::instance($folder->course);
        $fs = get_file_storage();
        $files = $fs->get_area_files($oldcontext->id, 'mod_folder', 'content', false, 'id', false);
        foreach ($files as $oldfile) {
            $filerecord = new stdClass();
            $filerecord->contextid = $newcontext->id;
            $filerecord->component = 'report_ee';
            $filerecord->filearea = 'samples';
            $filerecord->itemid = $folder->course;
            $fs->create_file_from_storedfile($filerecord, $oldfile);
        }

        // Is there already a label with a link? This is a bit fuzzy because the label
        // name is automatically generated from text.
        $sqllike = $DB->sql_like('name', ':name', false);
        $labelexists = $DB->record_exists_select('label', $sqllike, [
            'course' => $folder->course,
            'name' => '%' . get_string('foldername', 'report_ee') . '%',
        ]);
        if (!$labelexists) {
            // Create new label with link.
            self::create_eefolder_label($folder);
        }
        // Delete old folder.
        course_delete_module($folder->cmid);
        // Might need to rebuild cache.
        rebuild_course_cache($folder->course);
    }

    /**
     * Create a label replacement for the EE folder which we're going to delete.
     *
     * @param object $folder
     * @return void
     */
    private static function create_eefolder_label($folder) {
        global $DB;
        $reporteeurl = new moodle_url('/report/ee/index.php', ['courseid' => $folder->course]);
        $labelcontent = html_writer::tag('p',
            html_writer::link($reporteeurl, get_string('foldername', 'report_ee'))
        );
        $labelcontent .= html_writer::tag('p', 'The Moderation folder has moved to the External Examiner report page. ' .
            'You can get there by clicking on Reports -> External examiner feedback');
        $course = get_course($folder->course);
        [$module, $context, $cw, $cm, $data] = \prepare_new_moduleinfo_data($course, 'label', $folder->sectionnum);
        $data->visible = 0;
        $data->coursemodule = '';
        $cm = self::add_course_module($folder);
        $label = new stdClass();
        $label->course = $folder->course;
        $label->name = get_string('foldername', 'report_ee');
        $label->timecreated = time();
        $label->timemodified = time();
        $label->intro = $labelcontent;
        $label->introformat = FORMAT_HTML;
        $label->coursemodule = $cm->id;
        $label->cm = $cm;

        $id = label_add_instance($label);
        $label->id = $id;
        $cm->instance = $id;
        $DB->update_record('course_modules', $label->cm);
        // If label didn't exist, insert the new label where the old one was.
        course_add_cm_to_section($folder->course, $label->coursemodule, $folder->sectionnum, $folder->cmid);
    }

    /**
     * Create a new Label in place of the folder.
     *
     * @param object $folder
     * @return object
     */
    private static function add_course_module($folder) {
        global $DB;
        $labelmodule = $DB->get_record('modules', ['name' => 'label']);
        $newcm = new stdClass();
        $newcm->course = $folder->course;
        $newcm->module = $labelmodule->id;
        $newcm->visible = 0;
        $newcm->visibleoncoursepage = 1;
        $newcm->section = $folder->section;
        $newcm->indent = 0;
        $newcm->groupmode = 0;
        $newcm->groupingid = 0;
        $newcm->completion = 0;
        $newcm->completionexpected = 0;

        $newcm->id = add_course_module($newcm);
        return $newcm;
    }
}
