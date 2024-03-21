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
 * Locallib file for external examiners
 *
 * @package   report_ee
 * @author    Mark Sharp <mark.sharp@solent.ac.uk>
 * @copyright 2022 Solent University {@link https://www.solent.ac.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use report_ee\helper;

/**
 * Get all first sitting assignments.
 *
 * @param int $course Courseid
 * @return array Assignments for specified courseid
 */
function report_ee_get_assignments($course) {
    global $DB, $USER, $COURSE;

    $assignments = $DB->get_records_sql('SELECT a.id, a.name, cm.idnumber
            FROM {assign} a
            JOIN {course_modules} cm ON cm.instance = a.id
            JOIN {modules} m ON m.id = cm.module AND m.name = "assign"
            JOIN {local_quercus_tasks_sittings} s ON s.assign = a.id
            WHERE a.course = ?
            AND cm.idnumber != ""
            AND s.sitting_desc = "FIRST_SITTING"', [$course]);

    return $assignments;
}

/**
 * Course fullname
 *
 * @param int $course
 * @return string
 */
function report_ee_get_course_fullname($course) {
    global $DB;
    $coursefullname = $DB->get_field("course", "fullname", ['id' => $course]);
    return $coursefullname;
}

/**
 * Save form data
 *
 * @param stdClass $formdata
 * @return void
 */
function report_ee_save_form_data($formdata) {
    global $DB, $USER;
    $date = new DateTime("now", core_date::get_user_timezone_object());
    $courseid = $formdata->courseid;
    // Check to see if record exists in ee table for course.
    $report = $DB->get_record('report_ee', ['course' => $courseid]);
    if (!$report) {
        $record = new stdClass();
        $record->course = $courseid;
        $record->comments = $formdata->comments;
        // Timestamp when locked; 1 when being locked now; 0 not locked.
        $locked = $formdata->locked ?? 0;
        if ($locked == 1) {
            $record->locked = $date->getTimestamp();
        } else {
            $record->locked = $locked;
        }
        $record->timecreated = $date->getTimestamp();
        $id = $DB->insert_record('report_ee', $record);
        $report = $DB->get_record('report_ee', ['id' => $id]);
    }
    // Get the assignid as the first field, so we can match below.
    $assigns = $DB->get_records('report_ee_assign',
        ['report' => $report->id],
        '',
        'assign, id, report, user, sample, level, national'
    );

    foreach ($formdata as $fieldname => $value) {
        $arr = explode("_", $fieldname);
        if ($arr[0] == 'assign') {
            $assignid = $arr[1];
            $field = $arr[2];
            if (!in_array($field, helper::FEEDBACK_TYPES)) {
                continue;
            }
            if (!isset($assigns[$assignid])) {
                $assigns[$assignid] = new stdClass();
                $assigns[$assignid]->report = $report->id;
                $assigns[$assignid]->user = $USER->id;
                $assigns[$assignid]->assign = $assignid;
            }
            $assigns[$assignid]->{$field} = $value;
        }

        if ($fieldname == 'comments') {
            $report->comments = $value;
        }
        if ($fieldname == 'locked') {
            if ($value == 1) {
                $report->locked = $date->getTimestamp();
            } else {
                $report->locked = $value;
            }
        }
    }
    foreach ($assigns as $assign) {
        if (isset($assign->id)) {
            $DB->update_record('report_ee_assign', $assign);
        } else {
            $DB->insert_record('report_ee_assign', $assign);
        }
    }
    $report->timemodified = $date->getTimestamp();
    $DB->update_record('report_ee', $report, false);
}

/**
 * Given courseid, get assignment reports for that course
 *
 * @param int $courseid courseid
 * @return object
 */
function report_ee_get_report_data($courseid) {
    global $DB;
    $report = $DB->get_record('report_ee', ['course' => $courseid]);
    if (!$report) {
        return null;
    }
    $assigns = $DB->get_records('report_ee_assign', ['report' => $report->id]);
    $report->assigns = $assigns;
    return $report;
}

/**
 * Get existing data to populate the form
 *
 * @param object $data
 * @param int $courseid
 * @return stdClass Form data
 */
function report_ee_set_data($data, $courseid) {
    global $CFG;
    $assign = 0;
    $username = null;
    $setdata = new stdClass();
    $coursecontext = context_course::instance($courseid);

    $fs = get_file_storage();
    if ($data && $fs->get_area_files($coursecontext->id, 'report_ee', 'samples', $courseid)) {
        $data->samples = $courseid;
    } else {
        $args = [
            'contextid' => $coursecontext->id,
            'component' => 'report_ee',
            'filearea' => 'samples',
            'itemid' => $courseid,
        ];
        $fs->create_directory(...array_merge($args, ['filepath' => '/All briefs and peer reviews/']));
        $fs->create_directory(...array_merge($args, ['filepath' => '/Samples and Internal Moderation Records/']));
        $fs->create_directory(...array_merge($args, ['filepath' => '/Resit samples and Internal Moderation Records/']));
    }

    $draftitemid = file_get_submitted_draft_itemid('samples');
    file_prepare_draft_area(
        // The $draftitemid is the target location.
        $draftitemid,
        // The combination of contextid / component / filearea / itemid
        // form the virtual bucket that files are currently stored in
        // and will be copied from.
        $coursecontext->id,
        'report_ee',
        'samples',
        $courseid,
        [
            'subdirs' => 1,
            'maxbytes' => $CFG->maxbytes,
            'maxfiles' => 50,
        ]
    );

    // Set this by default.
    if (!$data) {
        $setdata = (object)helper::default_form_data($courseid);
        $setdata->samples = $draftitemid;
        return $setdata;
    }

    $setdata->samples = $draftitemid;
    $setdata->comments = $data->comments;

    $locked = $data->locked ?? 0;
    $setdata->locked = $locked;

    foreach ($data->assigns as $assign) {
        $assignid = $assign->assign;
        $sample = 'assign_'. $assignid .'_sample';
        $setdata->{$sample} = $assign->sample;

        $level = 'assign_'. $assignid .'_level';
        $setdata->{$level} = $assign->level;

        $national = 'assign_'. $assignid .'_national';
        $setdata->{$national} = $assign->national;
        if ($locked && !isset($setdata->lockedby)) {
            $date = new DateTime();
            $date->setTimestamp(intval($setdata->locked));
            $date = userdate($date->getTimestamp());
            if (isset($assign->userid)) {
                $lockedby = core_user::get_user($assign->userid);
                $setdata->lockedby = get_string('lockedbydata', 'report_ee', ['username' => fullname($lockedby), 'date' => $date]);
            }
        }
    }

    return $setdata;
}

/**
 * Get email addresses for the module leaders as a CSV
 *
 * @return stdClass
 */
function report_ee_get_module_leader_emails() {
    global $DB, $COURSE;
    $moduleleaders = $DB->get_record_sql("SELECT GROUP_CONCAT(u.email SEPARATOR ',') emailto
                        FROM {user} u
                        INNER JOIN {role_assignments} ra ON ra.userid = u.id
                        INNER JOIN {context} ct ON ct.id = ra.contextid
                        INNER JOIN {course} c ON c.id = ct.instanceid
                        INNER JOIN {role} r ON r.id = ra.roleid
                        WHERE r.shortname = ?
                        AND c.id = ?",
                        [get_config('report_ee', 'moduleleadershortname'), $COURSE->id]);
    return $moduleleaders;
}

/**
 * Get the external examiner on current course
 *
 * @param int $courseid
 * @return stdClass
 */
function report_ee_get_external_examiner($courseid) {
    global $DB;
    // Could there be more than one?
    $shortname = get_config('report_ee', 'externalexaminershortname');
    if ($shortname == '') {
        return get_string('unknown', 'report_ee');
    }
    $ees = $DB->get_record_sql("
        SELECT GROUP_CONCAT(CONCAT(u.firstname, ' ', u.lastname) SEPARATOR ', ') name
        FROM {user} u
            INNER JOIN {role_assignments} ra ON ra.userid = u.id
            INNER JOIN {context} ct ON ct.id = ra.contextid
            INNER JOIN {course} c ON c.id = ct.instanceid
            INNER JOIN {role} r ON r.id = ra.roleid
        WHERE r.shortname = :shortname
            AND c.id = :courseid",
        ['shortname' => $shortname, 'courseid' => $courseid]);
    if ($ees) {
        return $ees->name;
    }
    return get_string('unknown', 'report_ee');
}

/**
 * Get field label
 *
 * @param string $string
 * @return string label
 */
function report_ee_get_label_string($string) {
    switch ($string) {
        case 'sample':
            $string = get_string('sample', 'report_ee');
            return $string;
        case 'level':
            $string = get_string('level', 'report_ee');
            return $string;
        case 'national':
            $string = get_string('national', 'report_ee');
            return $string;
        default:
            return "";
    }
}

/**
 * Send emails when EE report has been completed.
 *
 * @param stdClass $formdata EE form data
 * @return void
 */
function report_ee_send_emails($formdata) {
    global $DB, $COURSE, $USER, $CFG;
    $assign = 0;
    $assignmessage = "";
    $actionrequired = "";
    $subject = '';
    $to = [];
    $mls = report_ee_get_module_leader_emails();
    if (isset($mls->emailto)) {
        $moduleleaders = explode(',', $mls->emailto);
        foreach ($moduleleaders as $moduleleader) {
            $to[$moduleleader] = $moduleleader;
        }

    }
    if ($reg = get_config('report_ee', 'studentregemail')) {
        $to[$reg] = $reg;
    }
    $qa = get_config('report_ee', 'qualityemail');
    $negativeoutcometext = '';
    $courseid = $formdata->courseid;
    $course = get_course($courseid);

    foreach ($formdata as $fieldname => $value) {
        $arr = explode("_", $fieldname);
        if ($arr[0] == 'assign') { // If this is an assignment value.
            $assignid = $arr[1];
            $field = $arr[2];
            if (!in_array($field, helper::FEEDBACK_TYPES)) {
                continue;
            }
            if ($assignid !== $assign) {
                // Get assign name.
                $assignment = $DB->get_record('assign', ['id' => $assignid]);
                $assignmessage .= html_writer::tag(
                    'h4',
                    get_string('emailassignmentname', 'report_ee', s($assignment->name)));
            }

            switch ($value) {
                case 1:
                    $assignmessage .= html_writer::tag(
                        'p',
                        get_string($field, 'report_ee') . ' - ' . get_string('yes')
                    );
                    break;
                case 2:
                    $assignmessage .= html_writer::tag(
                        'p',
                        get_string($field, 'report_ee') . ' - ' . get_string('no'),
                        [
                            'style' => 'color:red;font-weight:bold;',
                        ]
                        );
                    $actionrequired = get_string('actionrequired', 'report_ee');
                    // This is something QA need to know about.
                    if ($qa) {
                        $to[$qa] = $qa;
                    }
                    $negativeoutcometext = html_writer::tag(
                        'p',
                        get_string('negativeoutcometext', 'report_ee'),
                        [
                            'style' => 'font-weight:bold;',
                        ]
                    );
                    break;
            }
        }
    }

    $startdate = userdate($course->startdate, '%d/%m/%Y');
    $enddate = userdate($course->enddate, '%d/%m/%Y');

    // Should this be the full module instance?
    $shortname = substr($course->shortname, 0, strpos($course->shortname, "_"));

    $subject = $actionrequired .
        get_string('subject', 'report_ee', [
            'shortname' => $shortname,
            'startdate' => $startdate,
            'enddate' => $enddate,
        ]);
    $headers = "From: " . $CFG->noreplyaddress . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $externalexaminers = report_ee_get_external_examiner($courseid);
    $messagebody = html_writer::tag('p', get_string('externalname', 'report_ee', $externalexaminers));
    $submittedby = fullname($USER);
    $messagebody .= html_writer::tag('p', get_string('submittedby', 'report_ee', $submittedby));
    $messagebody .= $assignmessage;
    $messagebody .= html_writer::tag('h4', get_string('comments'). ':');
    $messagebody .= html_writer::tag(
        'p',
        format_text_email($formdata->comments, FORMAT_HTML)
    );
    $messagebody .= $negativeoutcometext;
    $url = new moodle_url('/report/ee/index.php', ['courseid' => $courseid]);
    $messagebody .= html_writer::tag('p',
        html_writer::link($url, get_string('reportlink', 'report_ee'))
    );
    mail(join(',', $to), $subject, $messagebody, $headers);
}
