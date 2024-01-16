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
 * Scheduled task definitions for External examiner feedback
 *
 * Documentation: {@link https://moodledev.io/docs/apis/subsystems/task}
 *
 * @package    report_ee
 * @category   task
 * @copyright  2024 Solent University {@link https://www.solent.ac.uk}
 * @author Mark Sharl <mark.sharp@solent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => '\report_ee\task\migrate_eefolders_task',
        'blocking' => 0,
        'minute' => '*/10',
        'hour' => '1-6',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
];
