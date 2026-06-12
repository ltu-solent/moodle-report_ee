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
 * Form for external examiners to review assignments
 *
 * @package   report_ee
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once('form.php');
require_once('locallib.php');

// Some old reports will link with the id param, but using courseid.
$id = optional_param('id', 0, PARAM_INT);
if ($id == 0) {
    $courseid = required_param('courseid', PARAM_INT);
} else {
    $courseid = $id;
}

$course = get_course($courseid);

$PAGE->set_url('/report/ee/index.php', ['courseid' => $courseid]);
$PAGE->set_pagelayout('report');

require_login($course);

// Check permissions.
$coursecontext = context_course::instance($courseid);
$PAGE->set_context($coursecontext);
require_capability('report/ee:view', $coursecontext);
$PAGE->set_title($course->shortname . ': ' . get_string('pluginname', 'report_ee'));
$PAGE->set_heading($course->shortname . ': ' . get_string('pluginname', 'report_ee'));

$data = report_ee_get_report_data($courseid);
$setdata = report_ee_set_data($data, $courseid);

$locked = $setdata->locked ?? 0;

$admin = false;
$edit = false;
if (has_capability('report/ee:admin', $coursecontext)) {
    $admin = true;
}
if (has_capability('report/ee:edit', $coursecontext)) {
    $edit = true;
}


// Trigger a report viewed event.
$event = \report_ee\event\report_viewed::create([
            'context' => $coursecontext,
            'userid' => $USER->id,
          ]);
$event->trigger();

$mform = new externalexaminerform(null, [
    'courseid' => $courseid,
    'locked' => $locked,
    'admin' => $admin,
    'edit' => $edit,
]);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
} else if ($formdata = $mform->get_data()) {
    $formdata->locked = $formdata->locked ?? 0;
    report_ee_save_form_data($formdata);
    if ($formdata->locked != 0) {
        report_ee_send_emails($formdata);
        $event = \report_ee\event\report_submitted::create([
            'context' => $coursecontext,
            'userid' => $USER->id,
        ]);
        $event->trigger();
    } else if ($formdata->locked < $locked) {
        $event = \report_ee\event\report_unlocked::create([
            'context' => $coursecontext,
            'userid' => $USER->id,
        ]);
        $event->trigger();
    } else {
        $event = \report_ee\event\report_updated::create([
            'context' => $coursecontext,
            'userid' => $USER->id,
        ]);
        $event->trigger();
    }
    redirect(
        $CFG->wwwroot . '/course/view.php?id=' . $courseid,
        get_string('saved', 'report_ee'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
$PAGE->requires->js_call_amd('report_ee/submit', 'init', [$admin]);

$mform->set_data($setdata);
$mform->display();

echo $OUTPUT->footer();
