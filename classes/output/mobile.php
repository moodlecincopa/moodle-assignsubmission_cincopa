<?php

namespace assignsubmission_cincopa\output;

define('ASSIGNSUBMISSION_CINCOPA_FILEAREA', 'submissions_cincopa');

class mobile  {
    public static function mobile_submission_edit($args) {
        global $OUTPUT, $DB;
        $configs = $DB->get_records_sql("SELECT * FROM {assign_plugin_config} WHERE name = 'courseApiToken' OR name = 'courseAssetTypes'");
        $args = (object) $args;
        $token = get_config('assignsubmission_cincopa', 'api_token_cincopa');
        $userid = $args->userid;
        $template = get_config('assignsubmission_cincopa', 'template_cincopa');
        $defaultView = get_config('assignsubmission_cincopa', 'submission_thumb_size_cincopa');
        $url = "https://api.cincopa.com/v2/ping.json?api_token=".$token;
        $result = file_get_contents($url);
        if($result) {
            $result = json_decode($result, true);
            $uid = $result['accid'];
        }

        return [
            'templates' => [
                [
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template('assignsubmission_cincopa/mobile_view_page', (object) array('token' => $token, 'userid' => $userid, 'uid' => ($uid ? $uid : ''), 'template' => $template, 'view' => $defaultView)),
                ]
            ],
            'javascript' => '
            var that = this; var phpUserId = "'.$userid.'"; var defToken = "'.$token.'"; var configs = '.json_encode(array_values($configs)).';
            var result = {
                isEnabledForEdit: function () {
                    return true;
                },
                componentInit: async function() {       
                         
                    console.warn("Plugin did load Javascript");
                    console.log("Plugin loaded!");
                    // @codingStandardsIgnoreStart
                    // Wait for the DOM to be rendered.
                    setTimeout(() => {
                        console.log("DOM Loaded!")
                    });
                    
                    this.currentToken = configs?.find?.(el => el.assignment == this.assign.id && el.name == "courseApiToken")?.value;
                    this.allowedTypes = "all";
                    this.allowedExtension = "all";
                    if(configs?.find?.(el => el.assignment == this.assign.id && el.name == "courseAssetTypes")?.value) {
                        this.allowedTypes = configs?.find?.(el => el.assignment == this.assign.id && el.name == "courseAssetTypes")?.value;
                    }
                    if(this.currentToken) {
                        const req = await fetch("https://api.cincopa.com/v2/ping.json?api_token=" + this.currentToken);
                        const res = await req.json();
                        this.currentUID = res?.accid;
                    }
                    if(this.submission?.status == "submitted") {
                        this.hasAssignmentSubmitted = true;
                    } else {
                        this.hasAssignmentSubmitted = false;
                    }
                    try {
                        if(!this.edit) {
                            const galleryName = "rrid:assign:"+this.assign.id+":"+phpUserId;
                            const galleryReq = await fetch("https://api.cincopa.com/v2/gallery.list.json?api_token="+(this.currentToken ?? defToken)+"&search=caption="+galleryName);
                            const galleryRes = await galleryReq.json();
    
                            if(galleryRes?.success && galleryRes?.galleries?.length) {
                                const fid = galleryRes.galleries[0].fid;
                                const itemsReq = await fetch("https://api.cincopa.com/v2/gallery.get_items.json?api_token="+(this.currentToken ?? defToken)+"&fid="+fid);
                                const itemsRes = await itemsReq.json();
                                if(itemsRes?.success && !itemsRes?.folder?.items_data?.items_count) {
                                    this.hasAssignmentSubmitted = false;
                                }
                            }
                        }
                    } catch(e) {
                        console.log(e);
                    }
                    // @codingStandardsIgnoreEnd
                    return true;
                },

                hasDataChanged: function() {
                    return true;
                },

                canEditOffline: function() {
                    return false;
                },

                prepareSubmissionData: function(assign, submission, plugin, inputData, pluginData) {
                    pluginData.onlinetext_editor = {
                        text: "submission for" + "rrid:assign"+assign.id+":"+submission.userid,
                        format: 1,
                        itemid: 0,
                    };
                }

            };
            result;',
        ];
    
    }
}