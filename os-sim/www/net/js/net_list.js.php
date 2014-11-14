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


/*  Function to link to the network detail  */
function link_to(id)
{
    if (typeof id != 'undefined' && id != '')
    {
        if (typeof top.av_menu.load_content  == 'function' && typeof top.av_menu.get_menu_url  == 'function')
    	{
    	    var url = '/asset_details/index.php?id='+ urlencode(id);
    	        url = top.av_menu.get_menu_url(url, 'environment', 'assets_groups', 'networks');
    	    
    	    top.av_menu.load_content(url);
        }
        else
    	{
    	    document.location.href = __path_ossim + '/asset_details/index.php?id='+urlencode(id);
        } 
    }
}


/*  GB_onclose event handler  */
function GB_onclose()
{
    if (datatables_assets != null && typeof datatables_assets._fnAjaxUpdate == 'function')
    {
        datatables_assets._fnAjaxUpdate();
    }
}

/*  GB_onhide event handler  */
function GB_onhide()
{
    if (datatables_assets != null && typeof datatables_assets._fnAjaxUpdate == 'function')
    {
        datatables_assets._fnAjaxUpdate();
    }
}


/*  
 Function for add button. 
 Return false since it is a dropdown menu  
*/
function add_button_action(elem)
{
    return false;
}


<?php
if (Session::can_i_create_assets() == TRUE)
{
	?>
	/*  Function to open the new net form lightbox  */
    function add_net()
    {
        GB_show("<?php echo _('Add Network') ?>", __path_ossim + '/net/net_form.php', '700', '720');
        
        return false;
    }
	<?php
}
?>

/*  Function to link to net exportation page  */
function export_net()
{
    document.location.href = __path_ossim + '/net/export_all_nets.php';
    
    return false;
}


/*  Function to open CSV importation lightbox  */
function import_csv()
{
    GB_show("<?php echo _('Import CSV') ?>", __path_ossim + '/net/import_all_nets.php', '700', '900');
    
    return false;
}


/*  Function to retieve tray information  */
function get_tray_data(nTr)
{
    var id  = $(nTr).attr('id');
     
    return $.ajax(
    {
        type: 'GET',
        url:  __path_ossim + '/net/ajax/net_tray.php?id=' + id,
    });           
}

/* Function to delete all networks */
function delete_all()
{ 
    if (datatables_assets.fnSettings().aoData.length === 0)
    {
        av_alert('<?php echo Util::js_entities(_("No networks to delete with this filter criteria"))?>');
        
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
        url: __path_ossim + "/net/ajax/delete_all.php",
        data: h_data,
        dataType: "json",
        beforeSend: function()
        {
            $('#asset_notif').empty();            
            
            var _msg = '<?php echo _("Deleting networks ..., please wait")?>';
            
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
				var _msg = (cnd_1 == true) ? "<?php echo _("Sorry, operation was not completed due to an unknown error")?>" : data.data;
			    show_notification('asset_notif', _msg, 'nf_error', 15000, true, style);
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
