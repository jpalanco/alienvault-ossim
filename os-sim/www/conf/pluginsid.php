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


$plugin_id      = GET('plugin_id');
$category_id    = GET('category_id');
$subcategory_id = GET('subcategory_id');

ossim_valid($plugin_id,         OSS_ALPHA,                      'illegal:' . _("Plugin ID"));
ossim_valid($category_id,       OSS_DIGIT, OSS_NULLABLE,        'illegal:' . _("Category ID"));
ossim_valid($subcategory_id,    OSS_DIGIT, OSS_NULLABLE,        'illegal:' . _("SubCategory ID"));

if (ossim_error()) 
{
    die(ossim_error());
}

$db   = new ossim_db();
$conn = $db->connect();


// translate category id
$category_name = '';

if ($category_id != "") 
{
    if ($category_list = Category::get_list($conn, "WHERE id = '$category_id'")) 
    {
        $category_name = $category_list[0]->get_name();
    }
}


//Subcategory
$subcategory_name = '';

if($subcategory_id != "")
{
	
	if ($subcategory_list = Subcategory::get_list($conn, "WHERE id = '$subcategory_id'"))
	{
		$subcategory_name = $subcategory_list[0]->get_name();
	}
}

$category_filter = ($subcategory_name != "") ? "$category_name - $subcategory_name" : $category_name;



$dt_url = "getpluginsid.php?plugin_id=$plugin_id";

if ($category_id != "")
{
    $dt_url .= "&category_id=$category_id"; 
}
if ($subcategory_id != "")
{
    $dt_url .= "&subcategory_id=$subcategory_id"; 
}

$back_url = urlencode(preg_replace ('/([&|\?]msg\=)(\w+)/', '\\1', $_SERVER["REQUEST_URI"]));
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
	
		#av_info
		{
			position: relative;
			margin: auto;
			height: 1px;
			width: 400px;
		}
		
		.back_ds
		{
			text-decoration:underline; 
			font-weight: bold; 
			font-size: 13px;
			color: black;
		}
		
		#c_actions
		{
    		text-align:center;    		
		}
		
		#sid_actions
		{
    		text-align: right;
    		margin: 20px 0 15px auto;
    		clear: both;
		}
		
		.img_sid_detail, .img_sid_delete
		{
    		height: 17px;
    		cursor: pointer;
		}
		
		
	</style>
	
	<script type="text/javascript">
		
		function change_pri_rel(row) 
		{
    		var sid = $(row).attr('id')
			var pri = $('select#priority', row).val();
			var rel = $('select#reliability', row).val();
			
			document.fc.pri.value = pri
			document.fc.rel.value = rel
			
			$.ajax(
			{
				type: "POST",
				url: "modifypluginsid.php",
				data: "change_properties=1&plugin_id=<?php echo $plugin_id?>&sid="+sid+"&priority="+document.fc.pri.value+"&reliability="+document.fc.rel.value,
				dataType: "json",
				beforeSend: function(xhr) 
				{
					$('#av_info').html('');
					$('#av_msg_info').html('');
				},
				success: function(data) 
				{
					if (typeof(data) == 'undefined' || data == null || data.status != 'OK')
					{
						var config_nt = { 
							content: '<?php echo _("Sorry, operation was not completed due to an error")?>', 
							options: {
								type:'nf_error',
								cancel_button: false
							},
							style: 'width: 100%; display:none; position: absolute; z-index:10000; text-align: center; top: 62px;'
						};
						
						nt = new Notification('nt_1',config_nt);
						
						$('#av_info').html(nt.show());
    					nt.fade_in(1000);
    					
    					setTimeout("$('#nt_cpr').fadeOut()", 5000);
    					
					}					
				}
			});
		}
	
				
        function edit_sid(row) 
        {
            var sid = $(row).attr('id')
            var url = 'modifypluginsidform.php?plugin_id=<?php echo $plugin_id ?>&sid=' + sid
            
            document.location.href = url
        }
        
        function delete_sid(row) 
        {
            var sid = $(row).attr('id')
            var url = '/ossim/conf/delete_pluginsid.php?plugin_id=<?php echo $plugin_id?>&sid=' + sid

            var msg  = "<?php echo Util::js_entities(_("You are about to delete this Plugin SID, this action cannot be undone. Do you want to continue?")) ?>"
            var opts = {"yes": "<?php echo _('Yes') ?>", "no": "<?php echo _('No') ?>"}
            
    		av_confirm(msg, opts).done(function()
    		{
        		document.location.href = url
    		});
        }	
		
				
		$(document).ready(function() 
		{
			
            <?php 
            if (GET('msg') == "created") 
            { 
            ?>
                notify('<?php echo _("Event type successfully created")?>', 'nf_success');
            <?php 
            } 
            elseif (GET('msg') == "updated") 
            { 
            ?>
                notify('<?php echo _("Event type successfully updated")?>', 'nf_success');
            <?php 
            }
            elseif (GET('msg') == "unknown_error") 
            { 
            ?>
                notify('<?php echo _("Sorry, operation was not completed due to an unexpected error")?>', 'nf_error');
            <?php 
            } 			
            ?>
            
            //Remove search filter url
            $('#back_sid').on('click', function()
            {
                document.location.href='pluginsid.php?plugin_id=<?php echo $plugin_id?>'; 
            });
            

            //Insert
            $('#button_insert').on('click', function()
            {
                document.location.href = 'newpluginsidform.php?plugin_id=<?php echo $plugin_id?>'; 
            });
                       
            //Apply            
            $('#button_apply').on('click', function()
            {
				document.location.href = 'reload.php?what=plugins&back=<?php echo $back_url ?>';
            });


            $(document).on('dblclick', '.table_data tr', function(e)
            {
                $(this).disableTextSelect();
                
                edit_sid(this);
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
                "aaSorting": [[ 1, "desc" ]],
                "aoColumns": [
                    { "bSortable": false, "sClass": "left"},
                    { "bSortable": true,  "sClass": "left"},
                    { "bSortable": false, "sClass": "left"},
                    { "bSortable": false, "sClass": "left"},
                    { "bSortable": false, "sClass": "left"},
                    { "bSortable": true,  "sClass": "left"},
                    { "bSortable": true,  "sClass": "center"},
                    { "bSortable": true,  "sClass": "center"},
                    { "bSortable": false, "sClass": "center", "sWidth": "85px"}
                ],
                "fnCreatedRow": function(nRow, aData, iDataIndex)
                {
                    var d_img = $('<img/>', 
                    {
                        'src'     : '/ossim/pixmaps/show_details.png',
                        'class'   : 'img_sid_detail',
                        'title'   : "<?php echo _('Edit Event Type') ?>",
                        'click'   : function()
                        {
                            edit_sid(nRow);
                        }                        
                    });
                    
                    var r_img = $('<img/>', 
                    {
                        'src'     : '/ossim/pixmaps/delete.png',
                        'class'   : 'img_sid_delete',
                        'title'   : "<?php echo _('Delete Event Type') ?>",
                        'click'   : function()
                        {
                            delete_sid(nRow);
                        }                        
                    });
                    
                    $('td:eq(8)', nRow).empty().append(r_img).append(d_img)
                    
                    
                    var p_select = $('<select></select>',
                    {
                        'id'    : 'priority',
                        'change': function()
                        {
                            change_pri_rel(nRow);
                        }   
                    });
                    
                    for (i = 0; i < 6; i++) 
                    { 
                        $('<option></option>',
                        {
                            'text' : i,
                            'value': i
                        }).appendTo(p_select)
                    }
                    
                    p_select.val(aData[6])
                    
                    $('td:eq(6)', nRow).html(p_select)
                    
                    
                    var r_select = $('<select></select>',
                    {
                        'id'    : 'reliability',
                        'change': function()
                        {
                            change_pri_rel(nRow);
                        }   
                    });
                    
                    for (i = 0; i < 11; i++) 
                    { 
                        $('<option></option>',
                        {
                            'text' : i,
                            'value': i
                        }).appendTo(r_select)
                    }
                    
                    r_select.val(aData[7])
                    
                    $('td:eq(7)', nRow).html(r_select)
                    
        
                },
                oLanguage :
                {
                    "sProcessing": "&nbsp;<?php echo _('Loading Event Types') ?> <img src='/ossim/pixmaps/loading3.gif' align='absmiddle'/>",
                    "sLengthMenu": "&nbsp;Show _MENU_ entries",
                    "sZeroRecords": "&nbsp;<?php echo _('No matching records found') ?>",
                    "sEmptyTable": "&nbsp;<?php echo _('No event types found in the system') ?>",
                    "sLoadingRecords": "&nbsp;<?php echo _('Loading') ?>...",
                    "sInfo": "&nbsp;<?php echo _('Showing _START_ to _END_ of _TOTAL_ event types') ?>",
                    "sInfoEmpty": "&nbsp;<?php echo _('Showing 0 to 0 of 0 event types') ?>",
                    "sInfoFiltered": "(<?php echo _('filtered from _MAX_ total event types') ?>)",
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
	
	<form onsubmit="return false" name="fc">
		<input type='hidden' name="pri" value=""/>
		<input type='hidden' name="rel" value=""/>
	</form>
	
	<div id='sid_actions'>
        <button id='button_insert'>
            <?php echo _('INSERT NEW EVENT TYPE') ?>
        </button>
        <button id='button_apply' class='av_b_secondary'>
            <?php echo _('APPLY') ?>
        </button>
    </div>
    
    <?php
    if ($category_id != "") 
    {	
    ?>
        <div id='c_actions'>
            <input type='button' id="back_sid" value='<?php echo _("Remove Category Filter").": ".$category_filter?>'>
        </div>
    <?php 
    } 
    ?>
		
	
	
	<div id='av_info'></div>

	<table class='noborder table_data'>
        <thead>
            <tr>
                <th><?php echo _('Data Source ID') ?></th>
                <th><?php echo _('Event Type ID') ?></th>
                <th><?php echo _('Category') ?></th>
                <th><?php echo _('Subcategory') ?></th>
                <th><?php echo _('Class') ?></th>
                <th><?php echo _('Name') ?></th>
                <th><?php echo _('Priority') ?></th>
                <th><?php echo _('Reliability') ?></th>
                <th></th>
            </tr>
        </thead>
        
        <tbody>
            <tr><td></td></tr>
        </tbody>
        
    </table>

	
</body>
</html>
<?php $db->close();?>
