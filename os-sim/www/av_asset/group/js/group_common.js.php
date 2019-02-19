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

/*********************************************************************
 ************************  COMMON VARIABLES   ************************
 *********************************************************************/

var __cfg = <?php echo Asset::get_path_url() ?>;




/******************************************************
 *****************  HELPER FUNCTIONS  *****************
 ******************************************************/


function add_assets_to_group(id)
{
    var parameters         = {};
    parameters['action']   = 'add_new_assets';
    parameters['asset_id'] = id; // Group ID

    perform_group_action(parameters);
}


function create_group(name, descr, empty)
{
    var parameters       = {};
    parameters['action'] = 'create_group';
    parameters['name']   = name; // Name for the New Group
    parameters['descr']  = descr;
    parameters['empty']  = empty;
    
    if (parameters['name'] != '')
    {
        perform_group_action(parameters);
    }
    else
    {
        show_notification('save_ag_notif', "<?php echo _('Asset Group Name is Required') ?>", 'nf_error', 5000, true);
    }
}

function perform_group_action(parameters)
{
    var ctoken          = Token.get_token("ag_form");
    parameters['token'] = ctoken;

    $.ajax({
        type: 'POST',
        url:  __cfg.group.controllers + "group_actions.php",
        dataType: 'json',
        data: parameters,
        success: function(data)
        {
            if (typeof(data) != 'undefined' && typeof(data.status) != 'undefined' && data.status == 'success')
            {
                if (typeof parent.GB_hide == 'function')
                {
                    var params = new Array();

                    if (typeof data.id != 'undefined' && data.id != null)
                    {
                        params['id'] = data.id;
                    }
                    else if (typeof parameters['asset_id'] != 'undefined')
                    {
                        params['id'] = parameters['asset_id'];
                    }
                    else
                    {
                        params['id'] = '';
                    }

                    parent.GB_hide(params);
                }
            }
            else
            {
                msg = (typeof(data.data) != 'undefined' && data.data != '') ? data.data : '<?php echo _('Error adding assets to the group')?>';
                show_notification('save_ag_notif', msg, 'nf_error', 5000, true);
            }
        },
        error: function(XMLHttpRequest, textStatus, errorThrown)
        {
            //Checking expired session
            var session = new Session(XMLHttpRequest, '');
            if (session.check_session_expired() == true)
            {
                session.redirect();
                return;
            }
            
            var error = XMLHttpRequest.responseText;
            show_notification('save_ag_notif', error, 'nf_error', 5000, true);
        }
    });

}