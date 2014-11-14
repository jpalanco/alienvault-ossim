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

var datatables_assets = false;

//Double click issue variables
var click_delay  = 300, n_clicks = 0, click_timer = null;


/*  Function to load the events for the lists  */
function list_handlers()
{
    $(document).on('click', '.table_data tr', function()
    {
        $(this).disableTextSelect();
        
        n_clicks++;  //count clicks
    
        var row = this;
        
        if(n_clicks === 1) //Single click event
        {
            click_timer = setTimeout(function() 
            {
                $(this).enableTextSelect();
                
                n_clicks = 0;             //reset counter
                tr_click_function(row);  //perform single-click action    
    
            }, click_delay);
        } 
        else //Double click event
        {
            clearTimeout(click_timer);  //prevent single-click action
            n_clicks = 0;               //reset counter
            tr_dblclick_function(row);  //perform double-click action
        }
        
    }).on('dblclick', '.table_data tr', function(e)
    {
        e.preventDefault();
    });
}


/*  Function for single click event in datatables row. It opens the tray  */
function tr_click_function(row)
{
    var nTr  = row;
    var that = $(row);
    
    if (datatables_assets.fnIsOpen(nTr))
    {
        datatables_assets.fnClose(nTr);
    }
    else
    {
        var tray_timeout = null;
        
        var data = get_tray_data(nTr);
        
        that.addClass('tray_wait');

        
        
        tray_timeout = setTimeout(function()
        {
            var ld_tray = " \
            <div class='tray_loading'> \
                <?php echo _('Loading Tray Info') ?>... \
            </div>";
            
            datatables_assets.fnOpen(nTr, ld_tray, 'tray_details');
        }, 100);
        
        
        $.when(data).then(function(theData) 
        {
            clearTimeout(tray_timeout);
            
            that.removeClass('tray_wait');
            
            datatables_assets.fnOpen(nTr, theData, 'tray_details');
            
        });
    }
}


/*  Function for double click event in datatables row. It goes to the detail  */
function tr_dblclick_function(row)
{
    var id  = $(row).attr('id');
    
    //This function is defined in group_list.js or net_list.js
    link_to(id)
    
    return false;
}


/*  Function to show loading message in datatables  */
function datatables_loading(loading)
{
    if (loading)
    {
        $('.table_data').css('min-height', '250px');
        $('.table_data').css('visibility', 'hidden');
    }
    else
    {
        $('.table_data').css('min-height', '0');
        $('.table_data').css('visibility', 'visible');
    }
}