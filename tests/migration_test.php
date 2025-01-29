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
use context_user;
use mod_folder_generator;

/**
 * Tests for External examiner feedback
 *
 * @package    report_ee
 * @category   test
 * @copyright  2024 Solent University {@link https://www.solent.ac.uk}
 * @author Mark Sharp <mark.sharp@solent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class migration_test extends \advanced_testcase {
    /**
     * Test migration process
     * @covers \report_ee\migration
     * @return void
     */
    public function test_migration(): void {
        global $DB;
        $this->resetAfterTest();
        $fs = get_file_storage();
        // Create module leader and external examiner roles.
        $roles = [
            'ml' => $this->getDataGenerator()->create_role((object)[
                'name' => 'Module leader',
                'shortname' => 'moduleleader',
                'archetype' => 'editingteacher',
            ]),
            'ee' => $this->getDataGenerator()->create_role((object)[
                'name' => 'External Examiner',
                'shortname' => 'ee',
                'archetype' => 'teacher',
            ]),
        ];
        $moduleleader = $this->getDataGenerator()->create_user();
        $externalexaminer = $this->getDataGenerator()->create_user();
        $this->setUser($externalexaminer);
        $eecontext = context_user::instance($externalexaminer->id);
        $module = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($moduleleader->id, $module->id, 'moduleleader');
        $this->getDataGenerator()->enrol_user($externalexaminer->id, $module->id, 'ee');

        // Create a folder.
        /** @var mod_folder_generator $foldergen */
        $foldergen = $this->getDataGenerator()->get_plugin_generator('mod_folder');
        $draftid = file_get_unused_draft_itemid();
        $filerecord = [
            'contextid' => $eecontext->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftid,
            'filepath' => '/',
        ];
        $filerecord['filename'] = 'Freddy_Richards.txt';
        $filerecord['filepath'] = '/All briefs and peer reviews/';
        $fs->create_file_from_string($filerecord, 'Freddy Richards content');
        $filerecord['filename'] = 'ABC101_Resit_Freddy_grades.txt';
        $filerecord['filepath'] = '/Resit samples and internal Moderation Records/';
        $fs->create_file_from_string($filerecord, 'Freddy resit grades content');
        $filerecord['filename'] = 'ABC101_Freddy_grades.txt';
        $filerecord['filepath'] = '/Samples and internal Moderation Records/';
        $fs->create_file_from_string($filerecord, 'Freddy sample grades content');

        $eefolder = $foldergen->create_instance([
            'course' => $module,
            'name' => 'Moderation (External Examiners) Private Folder',
            'files' => $draftid,
            'visible' => 0,
        ]);
        // You need to be an admin user to do the migration process. This will probably be run by cron or upgrade script.
        $this->setAdminUser();
        $foldercontext = context_module::instance($eefolder->cmid);
        assign_capability('mod/folder:view', CAP_ALLOW, $roles['ml'], $foldercontext->id);
        assign_capability('moodle/course:manageactivities', CAP_PREVENT, $roles['ml'], $foldercontext->id);
        assign_capability('moodle/course:activityvisibility', CAP_PREVENT, $roles['ml'], $foldercontext->id);
        assign_capability('mod/folder:view', CAP_ALLOW, $roles['ee'], $foldercontext->id);
        assign_capability('mod/folder:managefiles', CAP_ALLOW, $roles['ee'], $foldercontext->id);
        $ras = $DB->get_records('role_capabilities', ['contextid' => $foldercontext->id]);
        $this->assertCount(5, $ras);

        $folders = migration::get_folders_to_migrate();
        foreach ($folders as $folder) {
            $oldcontext = context_module::instance($folder->cmid);
            $this->assertEquals('Moderation (External Examiners) Private Folder', $folder->name);
            $files = $fs->get_area_files($oldcontext->id, 'mod_folder', 'content', false, 'id', false);
            $this->assertCount(3, $files);

            migration::move_folder_files_to_reportee($folder);

            $files = $fs->get_area_files($oldcontext->id, 'mod_folder', 'content', false, 'id', false);
            $this->assertCount(0, $files);

            $sqllike = $DB->sql_like('name', ':name', false);
            $labelexists = $DB->record_exists_select('label', $sqllike, [
                'course' => $folder->course,
                'name' => '%' . get_string('foldername', 'report_ee') . '%',
            ]);
            $this->assertTrue($labelexists);
            // All the capabilities for the Folder should have been deleted.
            $ras = $DB->get_records('role_capabilities', ['contextid' => $foldercontext->id]);
            $this->assertCount(0, $ras);
            $this->expectOutputString('- 3 files migrated for ' . $module->shortname . PHP_EOL .
                '- Label for EE Folder replacement created' . PHP_EOL
            );
        }
    }
}
