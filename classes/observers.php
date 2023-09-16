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
 * assign_submission_cincopa events subscription
 *
 * @package    assignsubmission_cincopa
 * @copyright  2015 Lancaster University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/mod/assign/submission/cincopa/lib.php');

/**
 * Event handler for assign_submission_cincopa plugin.
 */

 class assignsubmission_cincopa_observers {
    public static function submission_updated(\mod_assign\event\submission_status_updated $event) {
        global $DB;
        $userid = $event->get_data()['relateduserid'];
        $assign = $event->get_assign();
        $assignid = $assign->get_instance()->id;
        $galleryname = 'rrid:assign:'.$assignid.':'.$userid;
        $status = $event->get_data()['other']['newstatus'];
        $assignconfig = $DB->get_records_sql("SELECT * FROM {assign_plugin_config} WHERE name = 'courseApiToken' AND assignment = '{$assignid}'");
        $assigntoken = array_values($assignconfig)[0]->value;

        if($status !== 'submitted') {
            $getres = file_get_contents("https://api.cincopa.com/v2/gallery.list.json?api_token=".$assigntoken."&search=caption=".$galleryname);
            $getres = json_decode($getres);
            if($getres->success && $getres->galleries[0]) {
                $fid = $getres->galleries[0]->fid;
                file_get_contents("https://api.cincopa.com/v2/gallery.delete.json?api_token=".$assigntoken."&fid=".$fid."&delete_assets=yes");
            }
        }

    }
 }