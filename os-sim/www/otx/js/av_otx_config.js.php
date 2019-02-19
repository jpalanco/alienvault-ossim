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

function AV_otx_config(p)
{  
    var perms = $.extend(
    {
        "admin" : true
    }, p || {});
    
    
    this.token         = '';
    this.user_id       = '';
    this.username      = '';
    this.contributing  = false;
    this.key_version   = 0;
    this.latest_update = '';
    
    var url_controller = "<?php echo AV_MAIN_PATH ?>/otx/controllers/"
    var url_provider   = "<?php echo AV_MAIN_PATH ?>/otx/providers/"
    var confirm_keys   = {"yes": "<?php echo Util::js_entities(_('Yes')) ?>", "no": "<?php echo Util::js_entities(_('No')) ?>"};

    var pulse_list = {}
    
    var self = this;
    
    
    this.load_data = function()
    {     
        return $.ajax(
        {
            data    : {"action": "info"},
            type    : "POST",
            url     : url_provider + "otx_config.php",
            dataType: "json"
        }).done(function(data)
        {
            self.token         = data.token;
            self.username      = data.username;
            self.user_id       = data.user_id;
            self.contributing  = data.contributing;
            self.key_version   = data.key_version;
            self.latest_update = data.latest_update;
            
            if (self.key_version < 2)
            {
                self.username = "";
            }
        });
    }
    

    this.register_token = function(token)
    {
        return $.ajax(
        {
            data    : {"action": "activate", "data": {"token": token}},
            type    : "POST",
            url     : url_controller + "otx_config.php",
            dataType: "json"
        }).done(function(data)
        {
            self.token         = data.token;
            self.username      = data.username;
            self.user_id       = data.user_id;
            self.contributing  = true;
            self.key_version   = data.key_version;
            self.latest_update = data.latest_update;
            
            if (self.key_version < 2)
            {
                self.username = "";
            }
        });
    }
    
    
    this.remove_account = function()
    {
        return $.ajax(
        {
            data    : {"action": "remove"},
            type    : "POST",
            url     : url_controller + "otx_config.php",
            dataType: "json"
        }).done(function(data)
        {
            self.token         = "";
            self.username      = "";
            self.user_id       = "";
            self.contributing  = false;
            self.key_version   = 0;
            self.latest_update = "";
        });
    }
    
    
    this.toggle_contributing = function(status)
    {
        return $.ajax(
        {
            data    : {"action": "change_contribution", "data": {"status": status}},
            type    : "POST",
            url     : url_controller + "otx_config.php",
            dataType: "json"
        }).done(function(data)
        {
            self.contributing = status ? true : false;
        });
    }    
    
    
    this.init = function()
    {
        //Setting the config for ajax request errors.
        $.ajaxSetup(
        {
            error: function(XMLHttpRequest, textStatus, errorThrown) 
            {
                //Checking expired session
                var session = new Session(XMLHttpRequest, '');
                if (session.check_session_expired() == true)
                {
                    session.redirect();
                    return;
                }
                
                var msg  = XMLHttpRequest.responseText;
                show_notification('otx_notif', msg, "nf_error", 15000, true);
            }
        });
        
        // Starting the toggle button
        $('[data-otx="account-status"]').toggles(
        {
            "text" : 
            {
                "on" : "<?php echo _('Yes')?>",
                "off": "<?php echo _('No')?>"
            },
            "on"   : false,
            "click":false,
            "drag" : false
        });
        
        if (perms.admin)
        {
            //Binding Elements to actions
            $('[data-otx="connect-account"]').on('click', activate_account);
            $('[data-otx="remove-account"]').on('click', remove_account);
            $('[data-otx="view-account"]').on('click', view_account);
            $('[data-otx="edit-account"]').on('click', __edit_view);
            $('[data-otx="cancel-edition"]').on('click', __refresh_data);
            $('[data-otx="get-token"]').on('click', get_token);
            $('[data-otx="get-token-login"]').on('click', get_token_login);
            $('[data-otx="account-status"]').on('toggle', change_contributing);
            
            //Loading the OTX detail
            self.load_data().done(function()
            {
                __refresh_data(); 
                pulse_list = $('[data-pulse-list="wrapper"]').AV_otx_pulse_list()[0];
                $('#otx_loading').hide();
            });
        }
        else
        {
            $('.otx_admin').hide();
            pulse_list = $('[data-pulse-list="wrapper"]').AV_otx_pulse_list()[0];
        }
    }
    
    this.init();

    
    //Private Functions
    
    function activate_account()
    {
        var token  = $('[data-otx="token"]').find('input').val();
        var button = $(this);
        
        __show_loading(button);
        self.register_token(token).done(function(data)
        {
            __refresh_data();
            show_notification('otx_notif', data.msg, "nf_success", 15000, true);
            
            pulse_list.reload();
            
        }).always(function()
        {
            __hide_loading(button);
        }); 
    }
    
    function remove_account()
    {
        var msg = "<?php echo _('Are you sure you want to disconnect your OTX account?') ?>";
                
        av_confirm(msg, confirm_keys).done(function()
        {
            var button = $('[data-otx="account-actions"]');
            
            __show_loading(button);
            
            self.remove_account().done(function(data)
            {
                __refresh_data();
                show_notification('otx_notif', data.msg, "nf_success", 15000, true);
                
                pulse_list.reload();
                
            }).always(function()
            {
                __hide_loading(button);
            });
        });    
    }
    
    
    function change_contributing(e, status)
    {        
        self.toggle_contributing(~~status).done(function(data)
        {
            __refresh_data();
            
            var n_type = (status) ? 'nf_success' : 'nf_warning'
            show_notification('otx_notif', data.msg, n_type, 15000, true);
        });       
    }
    
    
    function view_account()
    {
        var url = "<?php echo Otx::OTX_URL ?>user/"+ self.username +"/<?php echo Otx::get_anchor() ?>";
        window.open(url, '_blank');          
    }
    
    
    function get_token()
    { 
        var url = "<?php echo Otx::getLoginURL(true) ?>";
        av_window_open(url); 
    }

    function get_token_login()
    {
        var url = "<?php echo Otx::getLoginURL(false) ?>";
        av_window_open(url);
    }
    
    
    
    /* Drawing Helper Functions */
    
    function __refresh_data()
    {    
        if (self.token == '')
        {
            __edit_view();
            
            $('#otx_key_warning').empty().hide();
        }
        else
        {            
            __update_toggle_button(self.contributing);
            __enable_toggle_button();
            
            __draw_val($('[data-otx="token"]'), self.token);
            
            $('[data-otx="account-actions"]').show();
            $('[data-otx="actions"]').hide();
            $('[data-otx="text-token"]').hide();
            
            if (self.key_version < 2)
            {
                var msg = "<?php echo _("OTX upgrade available. Please <a href='". Otx::OTX_URL_UPGRADE_LOGIN ."' target='_blank'>re-authenticate your OTX account</a> to take advantage of the new features!") ?>";
                        
                show_notification('otx_key_warning', msg, "nf_info", 0, false);
                $('#otx_key_warning').show()
            }
            else
            {
                $('#otx_key_warning').empty().hide();
            }
        }
        
        __draw_val($('[data-otx="username"]'), self.username);
        __draw_val($('[data-otx="latest-update"]'), self.latest_update);
    }
    
    
    function __edit_view()
    {
        __transform_input($('[data-otx="token"]'), self.token, false);
        __disable_toggle_button();
        
        if (self.token == '')
        {
            __update_toggle_button(false);
            
            $('[data-otx="account-actions"]').hide();
            $('[data-otx="cancel-edition"]').hide();
        }
        else
        {
            $('[data-otx="cancel-edition"]').show();
        }
        
        $('[data-otx="actions"]').show();
        $('[data-otx="text-token"]').show();
    }
    
    
    function __transform_input($elem, val, disabled)
    {
        var $input = $('<input>', 
        {
            'class': 'otx_text_input',
            'type' : 'text',
            'value': val
        }).prop('disabled', disabled);
        
        $elem.html($input);
    }
    
    
    function __draw_val($elem, val)
    {
        if (val != '')
        {
            $elem.text(val);
        }
        else
        {
            $elem.html("<span class='empty_val'><?php echo _('Unknown') ?></span>");
        }
    }
    
    
    function __show_loading($elem)
    {
        $('.otx_action').prop('disabled', true);
        $elem.addClass('av_b_f_processing');
    }
    
    
    function __hide_loading($elem)
    {
        $('.otx_action').prop('disabled', false);
        $elem.removeClass('av_b_f_processing');
    }
    

    function __update_toggle_button(status)
    {
        try
        {
            var toggle = $('[data-otx="account-status"]').data('toggles');
            
            toggle.toggle(status, true, true);
            
        } catch (Err) {}
    }
    
    
    function __disable_toggle_button()
    {
        var $tg = $('[data-otx="account-status"]').addClass('disabled');
        try
        {
            var toggle = $tg.data('toggles');
            
            toggle.opts.click = false;
            toggle.opts.drag  = false;

            toggle.bindEvents();
            
        } catch (Err) {}
    }
    
    
    function __enable_toggle_button()
    {
        var $tg = $('[data-otx="account-status"]').removeClass('disabled');
        try
        {
            var toggle = $tg.data('toggles');
            
            toggle.opts.click = true;
            toggle.opts.drag  = true;

            toggle.bindEvents();
            
        } catch (Err) {}
    }
}
