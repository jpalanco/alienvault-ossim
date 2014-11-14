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

Session::logcheck("configuration-menu", "PolicyActions");

// load column layout
require_once '../conf/layout.php';

$category    = "policy";
$name_layout = "actions_layout";
$layout      = load_layout($name_layout, $category);

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
    	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
    	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    	<meta http-equiv="Pragma" content="no-cache"/>
    	<meta http-equiv="X-UA-Compatible" content="IE=7" />
    	
    	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    	<link rel="stylesheet" type="text/css" href="../style/flexigrid.css"/>
    	
    	<script type="text/javascript" src="../js/jquery.min.js"></script>
    	<script type="text/javascript" src="../js/jquery.flexigrid.js"></script>
    	<script type="text/javascript" src="../js/urlencode.js"></script>
      
      	<style type='text/css'>
      	
        	table, th, tr, td 
        	{
        		background:transparent;
        		border-radius: 0px;
        		-moz-border-radius: 0px;
        		-webkit-border-radius: 0px;
        		border:none;
        		padding:0px; margin:0px;
        	}
        	
        	input, select 
        	{
        		border-radius: 0px;
        		-moz-border-radius: 0px;
        		-webkit-border-radius: 0px;
        		border: 1px solid #8F8FC6;
        		font-size:12px; font-family:arial; vertical-align:middle;
        		padding:0px; margin:0px;
        	}
        	
    	</style>
    	
    	<script type='text/javascript'>
    	
            function menu_action(com,id,fg,fp) 
            {
                if (com=='modify') 
                {
                    if (typeof(id) != 'undefined')
                    {
                        document.location.href = 'actionform.php?id='+id;
                    }
                    else
                    {
                        alert('<?php echo Util::js_entities(_("Action unselected"))?>');
                    }
                    
                }
                else if (com=='delete') 
                {
                    if (typeof(id) != 'undefined') 
                    {
                        $("#flextable").changeStatus('<?=_("Deleting action")?>...',false);
                        $.ajax({
                            type: "GET",
                            url: "deleteaction.php?id="+urlencode(id),
                            data: "",
                            success: function(msg) {
                                $("#flextable").flexReload();
                            }
                        });
                    }
                    else 
                    {
                        alert('<?php echo Util::js_entities(_("Action unselected"))?>');
                    }
                    
                }
                else if (com == 'new')
                {
                    document.location.href = 'actionform.php';
                }
            }
            
            
            function linked_to(rowid) 
            {
                document.location.href = 'actionform.php?id='+urlencode(rowid);
            }	
        	
        	
        	function action(com,grid) 
        	{
        		var items = $('.trSelected', grid);
        		
        		if (com=='<?php echo _("Delete selected");?>') 
        		{
        			//Delete host by ajax
        			if (typeof(items[0]) != 'undefined') 
        			{
        				$("#flextable").changeStatus('<?=_("Deleting action")?>...',false);
        				
        				$.ajax(
        				{
    						type: "GET",
    						url: "deleteaction.php?id="+urlencode(items[0].id.substr(3)),
    						data: "",
    						success: function(msg) {
    							$("#flextable").flexReload();
    						}
        				});
        			}
        			else 
        			{
            			alert('<?php echo Util::js_entities(_("Action unselected"))?>');
        			}
        			     
        		}
        		else if (com=='<?php echo _("Modify");?>') 
        		{
                    if (typeof(items[0]) != 'undefined')
                    {
                        document.location.href = 'actionform.php?id='+urlencode(items[0].id.substr(3))	
                    } 
                    else 
                    {
                        alert('<?php echo Util::js_entities(_("Action unselected"))?>');
                    }
                    
        		}
        		else if (com=='<?php echo _("New");?>') 
        		{
        			document.location.href = 'actionform.php'
        		}
        	}
        	
        	
        	function save_layout(clayout) 
        	{
        		$("#flextable").changeStatus('<?=_("Saving column layout")?>...',false);
        		
        		$.ajax(
        		{
    				type: "POST",
    				url: "../conf/layout.php",
    				data: { name:"<?php echo $name_layout?>", category:"<?php echo $category?>", layout:serialize(clayout) },
    				success: function(msg) 
    				{
    					$("#flextable").changeStatus(msg,true);
    				}
        		});
        	}
        	
        	
        	$(document).ready(function()
        	{
            	$("#flextable").flexigrid(
            	{
            		url: 'getaction.php',
            		dataType: 'xml',
            		colModel : [
            			<?php
            			$default = array(
            				"name" => array(
            					_('Name'),
            					260,
            					'true',
            					'left',
            					false
            				) ,
            				"action_type" => array(
            					_('Type'),
            					100,
            					'true',
            					'left',
            					false
            				) ,
            				"descr" => array(
            					_('Description'),
            					816,
            					'true',
            					'left',
            					false
            				) ,
            			);
            			list($colModel, $sortname, $sortorder, $height) = print_layout($layout, $default, "descr", "asc", 300);
            			echo "$colModel\n";
            			?>
            			],
            		buttons : [
            			{name: '<?=_("New")?>', bclass: 'add', onpress : action},
            			{separator: true},
            			{name: '<?=_("Modify")?>', bclass: 'modify', onpress : action},
            			{separator: true},
            			{name: '<?=_("Delete selected")?>', bclass: 'delete', onpress : action}
            			],
            		sortname: "<?php echo $sortname?>",
            		sortorder: "<?php echo $sortorder?>",
            		usepager: true,
            		pagestat: '<?=_("Displaying {from} to {to} of {total} Actions")?>',
            		nomsg: '<?=_("No Actions found in the system")?>',
            		useRp: true,
            		rp: 20,
            		contextMenu: 'myMenu',
            		onContextMenuClick: menu_action,
            		showTableToggleBtn: false,
            		singleSelect: true,
            		width: get_flexi_width(),
            		height: 'auto',
            		onColumnChange: save_layout,
            		onDblClick: linked_to,
            		onEndResize: save_layout
            	});  
            	
            }); 
            
        </script>
    	
    </head>

    <body style="margin:0">
    
    	<br><table id="flextable" style="display:none"></table>
    	
    	 <!-- Right Click Menu -->
        <ul id="myMenu" class="contextMenu" style="width:110px">
            <li class="hostreport"><a href="#new" class="greybox" style="padding:3px"><img src="../pixmaps/tables/table_row_insert.png" align="absmiddle"/> <?=_("New Action")?></a></li>
            <li class="hostreport"><a href="#modify" class="greybox" style="padding:3px"><img src="../pixmaps/tables/table_edit.png" align="absmiddle"/> <?=_("Modify")?></a></li>
            <li class="hostreport"><a href="#delete" class="greybox" style="padding:3px"><img src="../pixmaps/tables/table_row_delete.png" align="absmiddle"/> <?=_("Delete")?></a></li>
        </ul>
    	
    </body>
</html>

