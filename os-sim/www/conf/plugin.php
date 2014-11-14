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

Session::logcheck("configuration-menu", "ConfigurationPlugins");


$db    = new ossim_db();
$conn  = $db->connect();


$category_id    = GET('category_id');
$subcategory_id = GET('subcategory_id');
$sourcetype     = GET('sourcetype');


ossim_valid($category_id, OSS_DIGIT, OSS_NULLABLE,                      'illegal:' . _("Category ID"));
ossim_valid($subcategory_id, OSS_DIGIT, OSS_NULLABLE,                   'illegal:' . _("SubCategory ID"));
ossim_valid($sourcetype, OSS_ALPHA, OSS_SPACE, OSS_NULLABLE, OSS_SLASH, 'illegal:' . _("Product Type"));

if (ossim_error()) 
{
	die(ossim_error());
}

if (GET('restore') != "" && Session::am_i_admin()) 
{
	Plugin_sid::restore_plugins($conn);
}


$ptypes = Plugin::get_ptypes($conn);

$dt_url = "getplugin.php";

if ($sourcetype != "")
{
    $dt_url .= "?type=$sourcetype&field=sourcetype"; 
}
elseif ($category_id != "")
{
    $dt_url .= "?type=$category_id&field=category_id&subcategory_id=".$subcategory_id;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title> <?php echo _('AlienVault ' . (Session::is_pro() ? 'USM' : 'OSSIM')); ?> </title>
    <link rel="Shortcut Icon" type="image/x-icon" href="/ossim/favicon.ico">
    <meta http-equiv="Content-Type" content="text/html;charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    
    <?php

        //CSS Files
        $_files = array(
            array('src' => 'av_common.css',             'def_path' => TRUE),
            array('src' => 'jquery-ui.css',             'def_path' => TRUE),
            array('src' => 'jquery.dataTables.css',     'def_path' => TRUE)
        );
    
        Util::print_include_files($_files, 'css');
    
    
        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',                                 'def_path' => TRUE),
            array('src' => 'jquery-ui.min.js',                              'def_path' => TRUE),
            array('src' => 'utils.js',                                      'def_path' => TRUE),
            array('src' => 'notification.js',                               'def_path' => TRUE),
            array('src' => 'jquery.dataTables.js',                          'def_path' => TRUE),
            array('src' => 'jquery.dataTables.plugins.js',                  'def_path' => TRUE)
        );
    
        Util::print_include_files($_files, 'js');

    ?>
	
	<style type='text/css'>
		
		#c_actions
		{
  		    text-align: center;
		}
			
		.img_plugin_detail
		{
    		cursor: pointer;
		}
				
		#plugin_list_container
		{
    		margin: 10px auto 20px auto;
    		position: relative;
    		min-height: 450px;
		}
		
		#plugin_actions
		{
    		margin: 0 auto 15px auto;
    		clear: both;
		}		
		   
	</style>
	
	
	<script type='text/javascript'>   	
    	
    	function edit_plugin(row)
    	{
        	var id = $(row).attr('id');
                            
            document.location.href = 'pluginsid.php?plugin_id=' + id;
    	}
    	
        $(document).ready(function()
        {

            $('#back_plugin').on('click', function()
            {
                document.location.href = '/ossim/conf/plugin.php' 
            });
            
            //Manage Plugins References
            $('#button_manage').on('click', function()
            {
                document.location.href = '/ossim/forensics/manage_references.php'; 
            });
            
            //Restore Plugins
            $('#button_restore').on('click', function()
            {
                var msg  = "<?php echo Util::js_entities(_("Are you sure to restore the plugins to the original configuration?")) ?>"
                var opts = {"yes": "<?php echo _('Yes') ?>", "no": "<?php echo _('No') ?>"}
                
        		av_confirm(msg, opts).done(function()
        		{
            		document.location.href = '/ossim/conf/plugin.php?restore=1'
        		});
            });
            
            $(document).on('dblclick', '.table_data tr', function(e)
            {
                $(this).disableTextSelect();
                
                edit_plugin(this);
            });
        
        
            $('.table_data').dataTable(
            {
                "bProcessing": true,
                "bServerSide": true,
                "bDeferRender": true,
                "iDisplayLength": 20,
                "sAjaxSource": "<?php echo $dt_url ?>",
                "sServerMethod": "POST",
                "aLengthMenu": [10, 20, 50],
                "sPaginationType": "full_numbers",
                "bFilter": true,
                "bJQueryUI": true,
                "aaSorting": [[ 0, "desc" ]],
                "aoColumns": [
                    { "bSortable": true, "sClass": "left"},
                    { "bSortable": true, "sClass": "left"},
                    { "bSortable": true, "sClass": "left"},
                    { "bSortable": true, "sClass": "left"},
                    { "bSortable": true, "sClass": "left"},
                    { "bSortable": false, "sClass": "center", "sWidth": "50px"}
                ],
                "fnCreatedRow": function(nRow, aData, iDataIndex)
                {
                    var pimg = $('<img/>', 
                    {
                        'src'     : '/ossim/pixmaps/show_details.png',
                        'class'   : 'img_plugin_detail',
                        'click' : function()
                        {
                            edit_plugin(nRow);
                        }                        
                    });
                    
                    $('td:eq(5)', nRow).html(pimg);
        
                },
                oLanguage :
                {
                    "sProcessing": "&nbsp;<?php echo _('Loading Plugins') ?> <img src='/ossim/pixmaps/loading3.gif' align='absmiddle'/>",
                    "sLengthMenu": "&nbsp;Show _MENU_ entries",
                    "sZeroRecords": "&nbsp;<?php echo _('No matching records found') ?>",
                    "sEmptyTable": "&nbsp;<?php echo _('No plugins found in the system') ?>",
                    "sLoadingRecords": "&nbsp;<?php echo _('Loading') ?>...",
                    "sInfo": "&nbsp;<?php echo _('Showing _START_ to _END_ of _TOTAL_ plugins') ?>",
                    "sInfoEmpty": "&nbsp;<?php echo _('Showing 0 to 0 of 0 plugins') ?>",
                    "sInfoFiltered": "(<?php echo _('filtered from _MAX_ total plugins') ?>)",
                    "sInfoPostFix": "",
                    "sInfoThousands": ",",
                    "sSearch": "<?php echo _('Search') ?>",
                    "sUrl": "",
                    "oPaginate":
                    {
                        "sFirst":    "<?php echo _('First') ?>",
                        "sPrevious": "<?php echo _('Previous') ?>",
                        "sNext":     "<?php echo _('Next') ?>",
                        "sLast":     "<?php echo _('Last') ?>"
                    }
                }
            }).fnSetFilteringDelay(600);
            
        });
		
	</script>
	
	
	
</head>

<body>
    
     <?php 
    //Local menu		      
    if ($category_id == '')
    {
        include_once '../local_menu.php';
    }
    ?>
        
    <div id='plugin_list_container'>
    
        <?php
    	if ($sourcetype != "")
    	{ 
    	?>
    		<div id='c_actions'>
                <input type='button' id='back_plugin' value='<?php echo _("Remove Source Type Filter").": ".$ptypes[$sourcetype]?>'>
            </div>
        <?php
        }
    	?>
    	    
    	<table class='noborder table_data'>
            <thead>
                <tr>
                    <th><?php echo _('Data Source ID') ?></th>
                    <th><?php echo _('Name') ?></th>
                    <th><?php echo _('Type') ?></th>
                    <th><?php echo _('Product Type') ?></th>
                    <th><?php echo _('Description') ?></th>
                    <th></th>
                </tr>
            </thead>
            
            <tbody>
                <tr><td></td></tr>
            </tbody>
            
        </table>
               
    </div>
            
    <div id='plugin_actions'>
        <button id='button_manage' class='button av_b_secondary'>
            <?php echo _('Manage References') ?>
        </button>
        <button id='button_restore' class='button av_b_secondary'>
            <?php echo _('Restore Plugins') ?>
        </button>
    </div>

	
</body>
</html>