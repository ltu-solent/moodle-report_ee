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
 * Form for external examines to review assignments
 *
 * @package   report_ee
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");
require_once('locallib.php');
/**
 * EE form
 */
class externalexaminerform extends moodleform {
    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG, $DB;
        $mform = $this->_form;
        $courseid = $this->_customdata['courseid'];
        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);
        $forceopen = false;
        // Get the assignments.
        $assignments = report_ee\helper::get_assignments($courseid);
        if (!$assignments) {
            $mform->addElement('html', '<p>' .
                get_string('noassessments', 'report_ee',
                get_config('report_ee', 'studentregemail')) . '</p>');
            $mform->addElement('cancel');
            return;
        }
        $locked = $this->_customdata['locked'] ?? 0;
        $edit = $this->_customdata['edit'] ?? false;
        $admin = $this->_customdata['admin'] ?? false;
        $options = [
            get_string('notset', 'report_ee'),
            get_string('yes'),
            get_string('no'),
        ];

        foreach ($assignments as $assign) {
            // Add the assignment elements.
            $mform->addElement('header', 'assignment_' . s($assign->idnumber), s($assign->name));
            $mform->setExpanded('assignment_' . $assign->idnumber, $forceopen, $forceopen);
            // Samples select.
            $sampleid = 'assign_' . $assign->id. '_sample';
            $mform->addElement('select', $sampleid,
                get_string('sample', 'report_ee'),
                $options,
                'class="ee_sample_select"');
            $mform->addHelpButton($sampleid, 'helpsample', 'report_ee');
            if ($locked != 0 || $edit == false) {
                $mform->hardFreeze($sampleid);
            }
            // Level select.
            $levelid = 'assign_' . $assign->id. '_level';
            $mform->addElement('select', $levelid,
                get_string('level', 'report_ee'),
                $options,
                'class="ee_level_select"');
            $mform->addHelpButton($levelid, 'helplevel', 'report_ee');
            if ($locked != 0 || $edit == false) {
                $mform->hardFreeze($levelid);
            }
            // National select.
            $nationalid = 'assign_' . $assign->id. '_national';
            $mform->addElement('select', $nationalid,
                get_string('national', 'report_ee'),
                $options,
                'class="ee_national_select"');
            $mform->addHelpButton($nationalid, 'helpnational', 'report_ee');
            if ($locked != 0 || $edit == false) {
                $mform->hardFreeze($nationalid);
            }
        }

        $mform->addElement('header', 'sampleheader', new lang_string('samples', 'report_ee'));
        $mform->setExpanded('sampleheader', true, $forceopen);
        $options = ['subdirs' => 1, 'maxbytes' => $CFG->maxbytes, 'maxfiles' => 50];
        $fm = $mform->addElement('filemanager', 'samples', get_string('samples', 'report_ee'), null, $options);
        $fm->setValue($courseid);

        $mform->addElement('header', 'summary', get_string('feedbacksummary', 'report_ee'));
        $mform->setExpanded('summary', true, $forceopen);
        // Comments text area.
        $mform->addElement('textarea', 'comments',
            get_string('comments', 'report_ee'), 'wrap="virtual" rows="20" cols="50"');
        if ($locked != 0 || $edit == false) {
            $mform->disabledIf('comments', 'locked', 'checked');
            $mform->hardFreeze('comments');
        }
        // Locked checkbox.
        $mform->addElement('advcheckbox', 'locked', get_string('lock', 'report_ee'));
        $mform->addHelpButton('locked', 'helplock', 'report_ee');
        // Only allow EEs to lock the form. Only allow Student registry to unlock the form.
        if (($locked != 0 && $admin == false)) {
            $mform->hardFreeze('locked');
        }
        if ($edit == false && $admin == false || $locked == 0 && $admin == true) {
            $mform->hardFreeze('locked');
        }
        // Locked warning populated via jQuery onclick of 'locked'.
        $mform->addElement('html', '<div class="lockedwarning">');
        $mform->addElement('html', '</div><br>');

        // Locked by info populated via setdata().
        if ($locked != 0) {
            $mform->addElement('static', 'lockedby', get_string('lockedby', 'report_ee'));
        }
        if ($locked != 0 && $admin) {
            $this->add_action_buttons();
        } else if ($locked == 0 && ($admin || $edit)) {
            $this->add_action_buttons();
        }
    }

    /**
     * Extra validation
     *
     * @param array $data Formdata
     * @param array $files Info about files
     * @return array Errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        // On final submit (locked) check all fields have been populated.
        if (isset($data['locked']) && $data['locked'] == 1) {
            foreach ($data as $k => $v) {
                if ((strpos($k, 'assign') === 0) && ($v == 0)) {
                    $errors[$k] = get_string('errselect', 'report_ee');
                }
                if ($k == 'comments' && $v == "") {
                    $errors[$k] = get_string('errcomment', 'report_ee');
                }
            }
        }
        return $errors;
    }
}
