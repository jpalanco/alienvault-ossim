<?php
header('Content-type: text/javascript');

/**
*
* License:
*
* Copyright (c) 2003-2006 ossim.net
* Copyright (c) 2007-2013 AlienVault
* All rights reserved.
*
* This package is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; version 2 dated June, 1991.
* You may not use, modify or distribute this program under any other version
* of the GNU General Public License.
*
* This package is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this package; if not, write to the Free Software
* Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
* MA  02110-1301  USA
*
*
* On Debian GNU/Linux systems, the complete text of the GNU General
* Public License can be found in `/usr/share/common-licenses/GPL-2'.
*
* Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
*
*/
require_once 'av_init.php';
?>


function Hids_agent()
{
    var __cfg = <?php echo Asset::get_path_url()?>;

    var __msg_container = 'av_info';

    //Messages to show
    var __messages = {
        "deploying_agents"     : "<?php echo _('Deploying HIDS agent')?> ...",
        "deploying_all_agents" : "<?php echo _('Deploying HIDS agents')?> ...",
        "showing_assets"       : "<?php echo _('Showing assets')?> ...",
        "saving_os"            : "<?php echo _('Saving Operating System')?> ...",
        "unknown_error"        : "<?php echo _('Sorry, operation was not completed due to an error when processing the request. Please try again')?>"
    };

    this.mode = '';


    //Copy of this
    var __self = this;


    /*********************************************************************/
    /***************************  FUNCTIONS  *****************************/
    /*********************************************************************/


    this.deploy = function(asset_id, action)
    {
        this.mode = 'single';

        var deploy_options = __get_deploy_options(action);

        $.ajax({
            type: "POST",
            url: __cfg.asset.controllers + "deploy_hids.php",
            data: deploy_options,
            dataType: "json",
            beforeSend: function(xhr){

                show_loading_box('c_deploy', __messages.deploying_agents, '');
            },
            error: function(xhr){

                hide_loading_box();

                //Check expired session
                var session = new Session(xhr.responseText, '');

                if (session.check_session_expired() == true)
                {
                    session.redirect();
                    return;
                }

                var __error_msg = __messages.unknown_error;

                if (typeof(xhr.responseText) != 'undefined' && xhr.responseText != '')
                {
                    __error_msg = xhr.responseText;
                }

                var __nf_type = (__error_msg.match(/Warning/i)) ? 'nf_warning' : 'nf_error';

                var __style = 'width: 100%; text-align:left; margin:0px auto;';
                show_notification(__msg_container, __error_msg, __nf_type, null, true, __style);

                $('#go_to_mc').off('click').on('click', function(e){
                    e.preventDefault();
                    parent.GB_close({"action" : "go_to_mc"});
                });
            },
            success: function(data){

                hide_loading_box();

                parent.GB_close({"action" : "agent_deployed", "msg": data.data});
            }
        });
    };


    this.set_os = function(asset_id)
    {
        this.mode = 'single';

        var deploy_options = __get_deploy_options('select_os');

        $.ajax({
            type: "POST",
            url: __cfg.asset.controllers + "deploy_hids.php",
            data: deploy_options,
            dataType: "json",
            beforeSend: function(xhr){

                show_loading_box('c_deploy', __messages.saving_os, '');
            },
            error: function(xhr){

                hide_loading_box();

                //Check expired session
                var session = new Session(xhr.responseText, '');

                if (session.check_session_expired() == true)
                {
                    session.redirect();
                    return;
                }

                var __error_msg = __messages.unknown_error;

                if (typeof(xhr.responseText) != 'undefined' && xhr.responseText != '')
                {
                    __error_msg = xhr.responseText;
                }

                var __nf_type = (__error_msg.match(/Warning/i)) ? 'nf_warning' : 'nf_error';

                var __style = 'width: 100%; text-align:left; margin:0px auto;';
                show_notification(__msg_container, __error_msg, __nf_type, null, true, __style);
            },
            success: function(data){

                try
                {
                    if (data.status == 'warning')
                    {
                        var __style = 'width: 100%; text-align:center; margin:0px auto;';
                        show_notification(__msg_container, data.data, 'nf_warning', null, true, __style);

                        hide_loading_box();
                    }
                    else
                    {
                        top.frames['main'].__asset_detail.draw_info();
                        document.location.href = '/ossim/av_asset/asset/views/deploy_hids_form.php?asset_id=' + asset_id;
                    }
                }
                catch(Err)
                {
                    //console.log(Err);
                }
            }
        });
    };


    this.bulk_deploy = function(action)
    {
        this.mode = 'bulk';

        var deploy_options = __get_deploy_options(action);

        $.ajax({
            type: "POST",
            url: __cfg.common.controllers + "bk_deploy_hids.php",
            data: deploy_options,
            dataType: "json",
            beforeSend: function(xhr){

                show_loading_box('c_deploy', __messages.deploying_all_agents, '');
            },
            error: function(xhr){

                hide_loading_box();

                //Check expired session
                var session = new Session(xhr.responseText, '');

                if (session.check_session_expired() == true)
                {
                    session.redirect();
                    return;
                }

                var __error_msg = __messages.unknown_error;

                if (typeof(xhr.responseText) != 'undefined' && xhr.responseText != '')
                {
                    __error_msg = xhr.responseText;
                }

                var __nf_type = (__error_msg.match(/Warning/i)) ? 'nf_warning' : 'nf_error';
                var __style = 'width: 100%; text-align:left; margin:0px auto;';

                show_notification(__msg_container, __error_msg, __nf_type, null, true, __style);
            },
            success: function(data){

                hide_loading_box();

                parent.GB_close({"action" : "agents_deployed", "status": data.status, "msg": data.data});
            }
        });
    };


    this.skip_assets = function(action)
    {
        this.mode = 'bulk';

        var deploy_options = __get_deploy_options(action);

        return $.ajax({
            type: "POST",
            url: __cfg.common.controllers + "bk_deploy_hids.php",
            data: deploy_options,
            dataType: "json",
            beforeSend: function(xhr){

                var msg_loading_box = (action == 'show_unsupported') ? __messages.showing_assets : __messages.deploying_all_agents;

                show_loading_box('c_deploy', msg_loading_box, '');
            },
            error: function(xhr){

                hide_loading_box();

                //Check expired session
                var session = new Session(xhr.responseText, '');

                if (session.check_session_expired() == true)
                {
                    session.redirect();
                    return;
                }

                var __error_msg = __messages.unknown_error;

                if (typeof(xhr.responseText) != 'undefined' && xhr.responseText != '')
                {
                    __error_msg = xhr.responseText;
                }

                var __nf_type = (__error_msg.match(/Warning/i)) ? 'nf_warning' : 'nf_error';
                var __style = 'width: 100%; text-align:left; margin:0px auto;';

                show_notification(__msg_container, __error_msg, __nf_type, null, true, __style);
            },
            success: function(data){

            }
        });
    };


    function __get_deploy_options(action)
    {
        var deploy_options = $('#deploy_form').serialize() + '&token=' + Token.get_token('deploy_form');
            deploy_options += "&action=" + action;

        return deploy_options;
    };
};
