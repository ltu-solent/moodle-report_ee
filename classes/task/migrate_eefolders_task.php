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

namespace report_ee\task;

use core\task\scheduled_task;
use report_ee\migration;

/**
 * Class migrate_eefolders_task
 *
 * @package    report_ee
 * @copyright  2024 Solent University {@link https://www.solent.ac.uk}
 * @author Mark Sharp <mark.sharp@solent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class migrate_eefolders_task extends scheduled_task {
    /**
     * Get task name
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('migrate_eefolders_task', 'report_ee');
    }

    /**
     * Execute task
     *
     * @return void
     */
    public function execute() {
        $migrateno = get_config('report_ee', 'migrateno') ?? 10;
        $folders = migration::get_folders_to_migrate($migrateno);
        $start = time();
        $max = $start + 300;
        foreach ($folders as $folder) {
            // Skip processing more migrations if over 5 mins runtime.
            if (time() > $max) {
                continue;
            }
            mtrace("Migrating {$folder->name} in {$folder->shortname}");
            migration::move_folder_files_to_reportee($folder);
        }
    }
}
