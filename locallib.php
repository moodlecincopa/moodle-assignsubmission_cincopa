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
 * This file contains the definition for the library class for cincopa submission plugin
 *
 * This class provides all the functionality for the new assign module.
 *
 * @package    assignsubmission_cincopa
 * @copyright  2017 Cincopa LTD <moodle@cincopa.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();
define('ASSIGNSUBMISSION_CINCOPA_FILEAREA', 'submissions_cincopa');

/**
 * library class for onlinetext submission plugin extending submission plugin base class
 *
 * @package    assignsubmission_cincopa
 * @copyright  2017 Cincopa LTD <moodle@cincopa.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

 */
class assign_submission_cincopa extends assign_submission_plugin {

    /**
     * Get the name of the cincopa submission plugin
     * @return string
     */
    public function get_name() {
        return get_string('cincopa', 'assignsubmission_cincopa');
    }

    /**
     * Add elements to submission form
     *
     * @param mixed $submission stdClass|null
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return true if elements were added to the form
     */
    public function get_form_elements($submission, MoodleQuickForm $mform, stdClass $data) {
        global $USER, $CFG;
        $elements = array();

        $submissionid = $submission ? $submission->id : 0;

        if (!isset($data->cincopa)) {
            $data->cincopa = '';
        }

        if ($submission) {
            $defaultapitoken = get_config('assignsubmission_cincopa', 'api_token_cincopa');
           $cmid = $submission->assignment;
            $userid = $submission->userid;
            $studentgallery = "assign:" . $cmid . ":" . $userid;
            $iframe = '<iframe height="500" width="900" src="https://api.cincopa.com/v2/upload.iframe?api_token=' . $defaultapitoken . '&rrid=' . $studentgallery . '" ></iframe>';
            $mform->addElement('html', $iframe);
        }

        return true;
    }

   /**
    * 
    * @param stdClass $submission
    * @param stdClass $data
    * @return boolean
    */
    public function save(stdClass $submission, stdClass $data) {
        $cmid = $submission->assignment;
        $userid = $submission->userid;
        $studentgallery = "assign:" . $cmid . ":" . $userid;
        if ($submission) {
            return true;
        }
    }
    /**
     * 
     * @param stdClass $submission
     * @param boolean $showviewlink
     * @return string
     */
    public function view_summary(stdClass $submission, & $showviewlink) {
        $showviewlink = true;

        $result = '';

        $title = 'Gallery';

        if ($submission) {
            $result .= $title;
        }
        return $result;
    }

  /**
   * Display gallery in iframe
   * 
   * @param stdClass $submission
   * @return string
   */
    public function view(stdClass $submission) {
        $cmid = $submission->assignment;
        $defaultapitoken = get_config('assignsubmission_cincopa', 'api_token_cincopa');
        $defaulttemplate = get_config('assignsubmission_cincopa', 'template_cincopa');
        $iframeteacher = '<div id="cp_widget_1">...</div> 
                            <script type="text/javascript"> 
                            var cpo = []; 
                            cpo["_object"] ="cp_widget_1"; 
                            cpo["_uid"] = "' . explode("i", $defaultapitoken)[0] . '"; 
                            cpo["_rrid"] = "assign:' . $cmid . ':' . $submission->userid . '" 
                            cpo["_template"] ="' . $defaulttemplate . '"; 
                            var _cpmp = _cpmp || []; _cpmp.push(cpo); 
                            (function() { var cp = document.createElement("script"); cp.type = "text/javascript"; 
                            cp.async = true; cp.src = "//www.cincopa.com/media-platform/runtime/libasync.js"; 
                            var c = document.getElementsByTagName("script")[0]; 
                            c.parentNode.insertBefore(cp, c); })(); 
                            </script>';
        if ($submission->status == 'submitted') {
            
            return $iframeteacher;
        }else{
           return ''; 
        
        }
    }

   /**
    * 
    * @param stdClass $submission
    * @return type
    */
    public function is_empty(stdClass $submission) {
        return $this->view($submission) == '';
    }

}
