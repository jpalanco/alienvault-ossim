<?php
header("Content-type: text/javascript");

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
var __path_ossim = "<?php echo AV_MAIN_PATH ?>";


/*  Function to link to the group detail  */

function link_to(id)
{
    if (typeof id != 'undefined' && id != '')
    {
        if (typeof top.av_menu.load_content  == 'function' && typeof top.av_menu.get_menu_url  == 'function')
    	{
    	    var url = '/asset_details/index.php?id='+ urlencode(id);
    	        url = top.av_menu.get_menu_url(url, 'environment', 'assets_groups', 'host_groups');

    	    top.av_menu.load_content(url);
        }
        else
    	{
    	    document.location.href = __path_ossim + '/asset_details/index.php?id='+urlencode(id);
        }
    }
}

/*  Function to link to create new group (Asset search page)  */

function add_button_action()
{
    if (typeof(top.av_menu.load_content) == 'function')
    {
        var url = '/assets/index.php';
        url     = top.av_menu.get_menu_url(url, 'environment', 'assets', 'assets');

        top.av_menu.load_content(url);
    }
    else
    {
        document.location.href = __path_ossim + '/assets/index.php';
    }
}


/*  Function to retieve tray information  */
function get_tray_data(nTr)
{
    var id  = $(nTr).attr('id');

    return $.ajax(
    {
        type: 'GET',
        url:  __path_ossim + '/group/ajax/group_tray.php?id=' + id,
    });
}

/* Function to delete all groups */
function delete_all()
{
    if (datatables_assets.fnSettings().aoData.length === 0)
    {
        av_alert('<?php echo Util::js_entities(_("No groups to delete with this filter criteria"))?>');

        return false;
    }

    //Notification style
    style = 'width: 600px; top: -2px; text-align:center ;margin:0px auto;';

    //AJAX data

    var h_data = {
        "token" : Token.get_token("delete_all"),
        "search" : __search_val
    };

    $.ajax(
    {
        type: "POST",
        url: __path_ossim + "/group/ajax/delete_all.php",
        data: h_data,
        dataType: "json",
        beforeSend: function()
        {
            $('#asset_notif').empty();

            var _msg = '<?php echo _("Deleting groups ..., please wait")?>';

			show_loading_box('main_container', _msg , '');
        },
        success: function(data)
        {
            //Check expired session
			var session = new Session(data, '');

			if (session.check_session_expired() == true)
			{
				session.redirect();
				return;
			}

			hide_loading_box();

			var cnd_1  = (typeof(data) == 'undefined' || data == null);
			var cnd_2  = (typeof(data) != 'undefined' && data != null && data.status != 'OK');

			//There is an unknown error
			if (cnd_1 || cnd_2)
			{
				var _msg  = (cnd_1 == true) ? "<?php echo _("Sorry, operation was not completed due to an unknown error")?>" : data.data;
				var _type = (_msg.match(/policy/)) ? 'nf_warning' : 'nf_error';

			    show_notification('asset_notif', _msg, _type, 15000, true, style);
			    datatables_assets.fnDraw();
            }
			else
			{
			    show_notification('asset_notif', data.data, 'nf_success', 15000, true);
			    $('#list_search').val('');
			    __search_val = '';
			    datatables_assets.fnDraw();
			}

        },
        error: function(data)
        {
            //Check expired session
            var session = new Session(data, '');

            if (session.check_session_expired() == true)
            {
                session.redirect();
                return;
            }

            hide_loading_box();

            var _msg = "<?php echo _("Sorry, operation was not completed due to an unknown error")?>";

            show_notification('asset_notif', _msg, 'nf_error', 15000, true, style);
        }
    });
}
