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

class assign_submission_cincopa extends assign_submission_plugin
{
    static $uid;
    /**
     * Get the name of the cincopa submission plugin
     * @return string
     */
    public function get_uid(){
        if(!self::$uid) {
            $defaultapitoken = get_config('assignsubmission_cincopa', 'api_token_cincopa');
            if ($this->get_config('courseApiToken')) {
                $defaultapitoken = $this->get_config('courseApiToken');
            }
    
            $url = "https://api.cincopa.com/v2/ping.json?api_token=" . $defaultapitoken;
            $result = file_get_contents($url);
            if ($result) {
                $result = json_decode($result, true);
                self::$uid = $result['accid'];
            }
        }

        return self::$uid;
    }
    public function get_name()
    {
        return get_string('cincopa', 'assignsubmission_cincopa');
    }

    /**
     * Get the settings for Online PoodLLsubmission plugin form
     *
     * @global stdClass $CFG
     * @global stdClass $COURSE
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform)
    {
        global $CFG, $COURSE;
        function get_cp_course_metadata($courseid)
        {
            $handler = \core_customfield\handler::get_handler('core_course', 'course');
            // This is equivalent to the line above.
            //$handler = \core_course\customfield\course_handler::create();
            $datas = $handler->get_instance_data($courseid);
            $metadata = [];
            foreach ($datas as $data) {
                if (empty($data->get_value())) {
                    continue;
                }
                $metadata[$data->get_field()->get('shortname')] = $data->get_value();
            }
            return $metadata;
        }
        $courseToken = $this->get_config('courseApiToken');
        $courseAssetTypes = $this->get_config('courseAssetTypes');
        $mform->addElement('text', 'assignsubmission_cincopa_courseApiToken', 'API Token (Optional)', '');
        $mform->addElement('text', 'assignsubmission_cincopa_courseAssetTypes', 'Allowed Asset Types (Optional)', '');


        // INITIAL VALUES SET
        if ($courseToken) {
            $mform->setDefault('assignsubmission_cincopa_courseApiToken', $courseToken);
        }
        if($courseAssetTypes) {
            $mform->setDefault('assignsubmission_cincopa_courseAssetTypes', $courseAssetTypes);
        }
        // INITIAL VALUES SET

        // METADATA SET
        if(get_cp_course_metadata($COURSE->id)['cp_token']) {
            $mform->setDefault('assignsubmission_cincopa_courseApiToken', get_cp_course_metadata($COURSE->id)['cp_token']);
            $mform->disabledIf('assignsubmission_cincopa_courseApiToken', 'assignsubmission_cincopa_enabled', 'notchecked');
            $mform->disabledIf('assignsubmission_cincopa_courseApiToken', 'assignsubmission_cincopa_enabled', 'checked');
        }
        if(get_cp_course_metadata($COURSE->id)['cp_asset_types']) {
            $mform->setDefault('assignsubmission_cincopa_courseAssetTypes', get_cp_course_metadata($COURSE->id)['cp_asset_types']);
            $mform->disabledIf('assignsubmission_cincopa_courseAssetTypes', 'assignsubmission_cincopa_enabled', 'notchecked');
            $mform->disabledIf('assignsubmission_cincopa_courseAssetTypes', 'assignsubmission_cincopa_enabled', 'checked');
        }       

        // METADATA SET 

        //If  M3.4 or higher we can hide unneeded elements
        if ($CFG->version >= 2017111300) {
            $mform->hideIf('assignsubmission_cincopa_courseApiToken', 'assignsubmission_cincopa_enabled', 'notchecked');
            $mform->hideIf('assignsubmission_cincopa_courseAssetTypes', 'assignsubmission_cincopa_enabled', 'notchecked');           

        }
    }
    /**
     * Save the settings for file submission plugin
     *
     * @param stdClass $data
     * @return bool 
     */
    public function save_settings(stdClass $data)
    {
        if (isset($data->{'assignsubmission_cincopa_courseApiToken'})) {
            $this->set_config('courseApiToken', $data->{'assignsubmission_cincopa_courseApiToken'});
        }
        if (isset($data->{'assignsubmission_cincopa_courseAssetTypes'})) {
            $this->set_config('courseAssetTypes', $data->{'assignsubmission_cincopa_courseAssetTypes'});
        }

        return true;
    }

    /**
     * Add elements to submission form
     *
     * @param mixed $submission stdClass|null
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return true if elements were added to the form
     */
    public function get_form_elements($submission, MoodleQuickForm $mform, stdClass $data)
    {
        if (!isset($data->cincopa)) {
            $data->cincopa = '';
        }

        if ($submission) {
            $defaultapitoken = get_config('assignsubmission_cincopa', 'api_token_cincopa');
            $defaultView = get_config('assignsubmission_cincopa', 'submission_thumb_size_cincopa');
            $cmid = $submission->assignment;
            $userid = $submission->userid;
            $studentgallery = "assign:" . $cmid . ":" . $userid;
            if ($this->get_config('courseApiToken')) {
                $defaultapitoken = $this->get_config('courseApiToken');
            }
            if($this->get_config('courseAssetTypes')) {
                $allowedAssets = $this->get_config('courseAssetTypes');
            } else {
                $allowedAssets = 'all';
            }
            if($this->get_config('courseAssetTypesExtensions')) {
                $allowedExtensions = $this->get_config('courseAssetTypesExtensions');
            } else {
                $allowedExtensions = 'all';
            }
            $iframe = '<iframe height="500" width="100%"  allow="microphone *; camera *; display-capture *" allowfullscreen src="https://api.cincopa.com/v2/upload.iframe?api_token=' . $defaultapitoken . '&rrid=' . $studentgallery . '&disable_mobile_app=true&disable-undo=true&view=' . $defaultView . '&allow=' . $allowedAssets . '&allowExtensions='.$allowedExtensions.'" ></iframe>';
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
    public function save(stdClass $submission, stdClass $data)
    {
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
    public function view_summary(stdClass $submission, &$showviewlink)
    {   
        $showviewlink = ($submission->status == 'submitted' || $submission->timemodified) ? true : false;

        $result = '';
       
        $title = 'Gallery';

        if ($submission && ($submission->status == 'submitted' || $submission->timemodified)) {
                                    $result .= $title;
                            } else {
                        $result = 'No Cincopa Submission';
        }
        return $result;
    }

    /**
     * Display gallery in iframe
     * 
     * @param stdClass $submission
     * @return string
     */
    public function view(stdClass $submission)
    {
        $cmid = $submission->assignment;
        $defaultapitoken = get_config('assignsubmission_cincopa', 'api_token_cincopa');
        $defaulttemplate = get_config('assignsubmission_cincopa', 'template_cincopa');
        if ($this->get_config('courseApiToken')) {
            $defaultapitoken = $this->get_config('courseApiToken');
        }

        $uid = $this->get_uid();
        $iframeteacher = '<div id="deleteBox"></div><div id="cp_widget_1">...</div>
                            <div id="recorderBox"><div id="recorderBox_title"></div><div id="recorderBox_recorder"></div></div> 
                            <script type="text/javascript"> 
                                var url = new URL(location.href);
                                var cpo = []; var token =  "'. $defaultapitoken .'"; var submissionStatus = "'.$submission->status.'";
                                cpo["_object"] ="cp_widget_1"; 
                                cpo["_fid"] = "rrid:assign:' . $cmid . ':' . $submission->userid . '!'.$uid.'!'.$defaulttemplate.'";
                                var _cpmp = _cpmp || []; _cpmp.push(cpo); 
                                (function() { var cp = document.createElement("script"); cp.type = "text/javascript"; 
                                cp.async = true; cp.src = "//rtcdn.cincopa.com/libasync.js"; 
                                var c = document.getElementsByTagName("script")[0]; 
                                c.parentNode.insertBefore(cp, c);
                                cp.onload = async function() { 
                                    if(location.hash.indexOf("purgecache") > -1){
                                        window.cincopa = window.cincopa || {};
                                        window.cincopa.qs = window.cincopa.qs || {};
                                        window.cincopa.qs["cpdebug"] = "purgecache";
                                    }
                                    window.cincopa.registeredFunctions.push({
                                        func: function (name, data, gallery) {
                                            gallery.args.add_text = "title";
                                            //push this line with grader code
                                            //gallery.args.lightbox_text = url.searchParams.get("action") == "grader" ? "title and description and exif" : "title and description";
                                            gallery.args.lightbox_text = "title and description";
                                            gallery.args.lightbox_zoom = true;
                                            gallery.args.lightbox_zoom_type = "mousewheel";
                                            gallery.args.lightbox_video_autoplay = false;
                                            gallery.args.optimize_load = "load_all";
                                            if(url.searchParams.get("action") == "grader"){
                                                gallery.args.showProcessingItems = true;
                                                gallery.args.reloadForProcessing = true;
                                            }                                            
                                        }, filter: "runtime.on-args"
                                    });

                                    window.cincopa.registeredFunctions.push({
                                        func: function (name, data, gallery) {
                                                if(gallery && gallery.args && gallery.args.fid ) {
                                                    const fid = gallery.args.fid;
                                                    if(!document.querySelector(".delete_submission")){
                                                        var deleteButton = document.createElement("button");
                                                        deleteButton.className = "btn btn-primary delete_submission";
                                                        deleteButton.innerText = "Delete Submission";
                                                        deleteButton.style.marginBottom = "20px";
                                                        deleteButton.style.outline = "none";
                                                        document.getElementById("deleteBox").append(deleteButton);

                                                        var confirmDeleteBlock = document.createElement("div");
                                                        confirmDeleteBlock.className = "confirm-delete-block";                                        
                                                        confirmDeleteBlock.style.marginBottom = "20px";
                                                        confirmDeleteBlock.style.display = "none";
                                                        confirmDeleteBlock.style.border = "1px solid #d6d6d6";
                                                        confirmDeleteBlock.style.maxWidth = "360px";
                                                        confirmDeleteBlock.style.padding = "20px";
                                                        document.getElementById("deleteBox").append(confirmDeleteBlock);

                                                        var confirmDeleteMessage = document.createElement("div");
                                                        confirmDeleteMessage.innerText = "Are you sure you want to delete this gallery?";
                                                        confirmDeleteMessage.style.marginBottom = "20px";
                                                        confirmDeleteMessage.style.fontWeight = "700";
                                                        confirmDeleteBlock.append(confirmDeleteMessage);

                                                        var confirmDeleteYes = document.createElement("button");
                                                        confirmDeleteYes.className = "btn btn-primary";
                                                        confirmDeleteYes.innerText = "Yes";
                                                        confirmDeleteBlock.append(confirmDeleteYes);

                                                        var confirmDeleteNo = document.createElement("button");
                                                        confirmDeleteNo.className = "btn btn-primary";
                                                        confirmDeleteNo.innerText = "No";
                                                        confirmDeleteNo.style.backgroundColor = "#db4c3f";
                                                        confirmDeleteNo.style.borderColor = "#db4c3f";
                                                        confirmDeleteNo.style.marginLeft = "10px";
                                                        confirmDeleteBlock.append(confirmDeleteNo);

                                                        deleteButton.onclick = async function() {
                                                            confirmDeleteBlock.style.display = "block";
                                                        }

                                                        confirmDeleteNo.onclick = async function() {
                                                            confirmDeleteBlock.style.display = "none";
                                                        }

                                                        confirmDeleteYes.onclick = async function() {
                                                            const deleteReq = await fetch("https://api.cincopa.com/v2/gallery.delete.json?api_token="+token+"&fid="+fid+"&delete_assets=yes");
                                                            const deleteRes = await deleteReq.json();
                                                            var oldHash = location.hash;
                                                            location.hash = oldHash.indexOf("#") > -1 ? oldHash + "&purgecache" : "#purgecache";
                                                            setTimeout(function(){
                                                                location.reload();
                                                            }, 1000);
                                                        }
                                                    }
                                                    
                                                    // Check if only in grader page
                                                    //return;
                                                    if(url.searchParams.get("action") == "grader" && !window.isRecorderInit) {
                                                        const recorderBox = document.getElementById("recorderBox_title");
                                                        recorderBox.innerHTML = "<br /><br /><h4 class=\"cp_recording_title\">Recording</h4><h3>Grade your student\'s work by recording your screen, leave Notify student checkmark active so student will see new recording in his submission</h3><br /><br />"
                                                        const uploadScript = document.createElement("script");
                                                        uploadScript.src = "//wwwcdn.cincopa.com/_cms/ugc/uploaderUI.js";
                                                        c.parentNode.insertBefore(uploadScript, cp.nextSibling);

                                                        const recorderBoxRecorder = document.getElementById("recorderBox_recorder");

                                                        uploadScript.onload = function() {
                                                            const recorderScript = document.createElement("script");
                                                            recorderScript.src = "//www.cincopa.com/_cms/ugc/v2/recorderui.js";
                                                            c.parentNode.insertBefore(recorderScript, uploadScript.nextSibling);
                                                            var reloadTimer;
                                                            recorderScript.onload = function() {
                                                                const uploadURL = gallery.args.upload_url.replace("&addtofid=*", "&addtofid="); //res.galleries[0].upload_url;
                                                                console.log(uploadURL);
                                                                const cpRecorder = new cpRecorderUI(recorderBoxRecorder, {
                                                                    width: "400px",
                                                                    height: "400px",
                                                                    resolution: "480",
                                                                    frameRate: 25,
                                                                    theme_color: "#37b3ff",
                                                                    uploadWhileRecording: true,
                                                                    default_tab: "screen",
                                                                    upload_url: uploadURL,
                                                                    rectraceMode: true,
                                                                    textRetake: "The video has been processed.",
                                                                    textRetakeLink: "if you would like to delete and retake the video.",
                                                                    onUploadComplete: async function(e) {
                                                                        const rid = e.rid;
                                                                        const req = await fetch("https://api.cincopa.com/v2/asset.set_meta.json?api_token=" + token + "&rid=" + rid + "&caption=Teacher grade recording " + Date.now());
                                                                        const res = await req.json();
                                                                        console.log(res);
                                                                        clearTimeout(reloadTimer);
                                                                        reloadTimer = setTimeout(function(){
                                                                            window.cincopa = window.cincopa || {};
                                                                            window.cincopa.qs = window.cincopa.qs || {};
                                                                            window.cincopa.qs["cpdebug"] = "purgecache";
                                                                            window.cincopa.boot_gallery({"_object": "cp_widget_1" ,"_fid" : "rrid:assign:' . $cmid . ':' . $submission->userid . '!'.$uid.'!'.$defaulttemplate.'"});
                                                                        },5000);
                                                                    },
                                                                    onDelete: async function(e){
                                                                        var serverData = e.xhr.responseText;
                                                                        var rid = serverData.split("\n")[5].split(" ")[3];
                                                                        const deleteReq = await fetch("https://api.cincopa.com/v2/asset.delete.json?api_token="+token+"&rid="+rid);
                                                                        const deleteRes = await deleteReq.json();
                                                                        clearTimeout(reloadTimer);
                                                                        window.cincopa = window.cincopa || {};
                                                                        window.cincopa.qs = window.cincopa.qs || {};
                                                                        window.cincopa.qs["cpdebug"] = "purgecache";
                                                                        window.cincopa.boot_gallery({"_object": "cp_widget_1" ,"_fid" : "rrid:assign:' . $cmid . ':' . $submission->userid . '!'.$uid.'!'.$defaulttemplate.'"});
                                                                    },
                                                                });
                                                                console.log(cpRecorder);
                                                                window.isRecorderInit = false;
                                                                document.querySelector(".assignsubmission_cincopa .expandsummaryicon").onclick = function() {
                                                                    if(window.isRecorderInit) {
                                                                        return;
                                                                    }
                                                                    window.isRecorderInit = true;
                                                                    if(this.querySelector("fa-plus")) {
                                                                        return true
                                                                    } else cpRecorder.start();
                                                                }
                                                            }
                                                        }
                                                    }
                                            }
                                        }, filter: "runtime.on-media-json"
                                    });                                    
                                }
                            })(); 
                        </script>';
        if ($submission->status != 'submitted') {
            return '';
        } else {
            return $iframeteacher;
        }
    }

    /**
     * 
     * @param stdClass $submission
     * @return type
     */
    public function is_empty(stdClass $submission)
    {
        return $this->view($submission) == '';
    }
}
