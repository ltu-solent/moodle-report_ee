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
 * TODO describe file folder
 *
 * @package    report_ee
 * @copyright  2023 Solent University {@link https://www.solent.ac.uk}
 * @author Mark Sharp <mark.sharp@solent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

require_login();

$courseid = required_param('courseid', PARAM_INT);
$context = context_course::instance($courseid);
$url = new moodle_url('/report/ee/folder.php', ['courseid' => $courseid]);
$PAGE->set_url($url);
$PAGE->set_context($context);

$PAGE->set_heading($SITE->fullname);
echo $OUTPUT->header();
$usercontext = context_user::instance($USER->id);

$fs = get_file_storage();
$data = new stdClass();
$data->courseid = $courseid;
// Do I already have a file structure?
if ($files = $fs->get_area_files($context->id, 'report_ee', 'attachments', $courseid)) {
    $data->attachments = $courseid;
} else {
    $args = [
        'contextid' => $context->id,
        'component' => 'report_ee',
        'filearea' => 'attachments',
        'itemid' => $courseid,
    ];
    $fs->create_directory(...array_merge($args, ['filepath' => '/All briefs and peer reviews/']));
    $fs->create_directory(...array_merge($args, ['filepath' => '/Samples and Internal Moderation Records/']));
    $fs->create_directory(...array_merge($args, ['filepath' => '/Resit samples and Internal Moderation Records/']));
}

$draftitemid = file_get_submitted_draft_itemid('attachments');
file_prepare_draft_area(
    // The $draftitemid is the target location.
    $draftitemid,
    // The combination of contextid / component / filearea / itemid
    // form the virtual bucket that files are currently stored in
    // and will be copied from.
    $context->id,
    'report_ee',
    'attachments',
    $courseid,
    [
        'subdirs' => 1,
        'maxbytes' => $maxbytes,
        'maxfiles' => 50,
    ]
);

$options = ['subdirs' => 1, 'maxbytes' => $CFG->maxbytes, 'maxfiles' => 50];

$folder = new report_ee\forms\folder_form(null, ['data' => $data, 'options' => $options]);
$folder->set_data((object) array('attachments' => $draftitemid));
if ($data = $folder->get_data()) {
    file_save_draft_area_files(
        $draftitemid,
        $context->id,
        'report_ee',
        'attachments',
        $courseid,
        [
            'subdirs' => 1,
            'maxbytes' => $maxbytes,
            'maxfiles' => 50,
        ]
    );
}

$folder->display();

echo $OUTPUT->footer();
