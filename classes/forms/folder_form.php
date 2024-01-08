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

namespace report_ee\forms;

use moodleform;
use stdClass;

/**
 * Class folder_form
 *
 * @package    report_ee
 * @copyright  2023 Solent University {@link https://www.solent.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class folder_form extends moodleform {
    public function definition() {
        global $CFG;
        $mform = $this->_form;
        $data = $this->_customdata['data'];
        $options = $this->_customdata['options'];
        
        // Add other form elements here
        // $options = [
        //     'subdirs' => 1,
        //     'maxbytes' => $CFG->maxbytes,
        //     'maxfiles' => 10,
        //     'accepted_types' => '*',
        //     'return_types' => FILE_INTERNAL,
        //     'component' => 'report_ee'
        // ];

        // Add a file manager element for multiple file uploads
        $fm = $mform->addElement('filemanager', 'attachments', get_string('attachments', 'report_ee'), null, $options);
        $fm->setValue($data->itemid);
        $mform->addElement('hidden', 'courseid', $data->courseid);
        $mform->setType('courseid', PARAM_INT);
        // Add standard form buttons
        $this->add_action_buttons();
    }

    // public function set_data($data) {

    //     print_r($data);
    //     parent::set_data($data);
    // }
}
