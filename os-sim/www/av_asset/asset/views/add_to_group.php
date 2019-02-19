<?php
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

Session::logcheck('environment-menu', 'PolicyHosts');

$asset_id = GET('asset_id');

ossim_valid($asset_id,    OSS_HEX, OSS_NULLABLE,    'illegal:' . _('Asset ID'));

if (ossim_error())
{
    die(ossim_error());
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title><?php echo _('AlienVault ' . (Session::is_pro() ? 'USM' : 'OSSIM')) ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    
    <?php
        //CSS Files
        $_files = array(
            array('src' => 'av_common.css',                 'def_path' => TRUE),
            array('src' => 'jquery-ui.css',                 'def_path' => TRUE),
            array('src' => 'jquery.dataTables.css',         'def_path' => TRUE),
            array('src' => 'lightbox.css',                  'def_path' => TRUE),
            array('src' => 'av_table.css',                  'def_path' => TRUE),
            array('src' => 'assets/asset_group_form.css',   'def_path' => TRUE)
        );
        
        Util::print_include_files($_files, 'css');
        
        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',                          'def_path' => TRUE),
            array('src' => 'jquery.number.js.php',                   'def_path' => TRUE),
            array('src' => 'utils.js',                               'def_path' => TRUE),
            array('src' => 'token.js',                               'def_path' => TRUE),
            array('src' => 'notification.js',                        'def_path' => TRUE),
            array('src' => 'jquery.dataTables.js',                   'def_path' => TRUE),
            array('src' => 'av_storage.js.php',                      'def_path' => TRUE),
            array('src' => 'av_table.js.php',                        'def_path' => TRUE),
            array('src' => '/av_asset/group/js/group_common.js.php', 'def_path' => FALSE)
        );
        
        Util::print_include_files($_files, 'js');
    ?>    
    

    <script type='text/javascript'>
        
        var __dt  = null;
        
        $(document).ready(function()
        {
            __dt = $("[data-bind='av_table_groups']").AV_table(
        	    {
        			"ajax_url"   : __cfg.asset.providers + "dt_group_asset.php",
        			"load_params": 
        		    [
        			    { "name": "asset_id", "value": "<?php echo $asset_id ?>"},
        			    { "name": "sSearch",  "value": function(){ return $('#search_filter').val(); } }
        		    ],
        		    "dt_params"  :
        		    {
        			    "aoColumns": 
        			    [
                        { "bSortable": true,  "sClass": "left" },
                        { "bSortable": false, "sClass": "center", "sWidth": "50px" }
        	            ],
            	        "bLengthChange": false,
                	    "iDisplayLength": 5
                },
                "on_draw_row": function(ui, nRow, aData, iDrawIndex, iDataIndex)
                {
                    var group_id = aData['DT_RowId'];
                    var _b_class = (aData['DT_RowData'].editable) ? 'av_b_secondary small' : 'av_b_secondary small disabled av_b_disabled';
                    
                    var input = $('<input>',
                        {
                            'type'  : 'button',
                            'value' : '+',
                            'class' : _b_class,
                            'click' : function()
                            {
                                if (aData['DT_RowData'].editable)
                                {
                                    add_assets_to_group(group_id);
                                }
                                else
                                {
                                    return false;
                                }
                            }
                        }).appendTo($("td:nth-child(2)", nRow));
                }
        	    });
    	    
    	    
            $("[data-bind='close-av-lb']").on("click",function()
            {
                if (typeof parent.GB_close == 'function')
                {
                    parent.GB_close();
                }
            });
            
            
            $("[data-bind='addnew-av-lb']").on("click",function()
            {
                create_group($('#new_group_input').val(), '', '');
            });

            // Search by ENTER key
            $("#search_filter").keyup(function (e) 
            {
                if (e.keyCode == 13) 
                {
                    __dt.reload_table();
                }
            });
        });
        
    </script>
</head>

<body>

<div id='addto_container'>


    <div id="save_ag_notif"></div>
    

    <div id='addto_search_container'>
        <input type='text' name='sSearch' id='search_filter' value='' placeholder='<?php echo _('Search') ?>'/>
    </div>
    
    <div class=''>
        <!-- GROUP LIST -->
        <div data-name="groups" data-bind="av_table_groups">
	
            	<table class="table_data" id="table_data_groups">
            	    <thead>
            	        <tr>
            	            <th><?php echo _('Name')?></th>
            	            <th><?php echo _('Actions')?></th>
            	        </tr>
            	    </thead>
            	    <tbody>
            	        <tr>
            	            <td colspan='2'></td>
            	        </tr>            
            	    </tbody>
            	</table>
        	
        </div>
    </div>
    
    <div id='add_new_container'>
    
        <div class='add_new_title'>
            <?php echo _('New Group') ?>
        </div>
        
        <div id='add_new_input_container'>
            <div class='add_new_input'>
                <input type='text' name='new_group_name' id='new_group_input' value=''/>
            </div>
            <div class='add_new_button'>
                <button data-bind='addnew-av-lb' class='av_b_secondary small'><?php echo _('+') ?></button>
            </div>
        </div>
        
    </div>

</div>    

</body>

</html>
