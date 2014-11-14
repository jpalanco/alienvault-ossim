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
Session::logcheck('configuration-menu', 'AlienVaultInventory');

require_once '../conf/layout.php';

$category    = 'inventory';
$name_layout = 'inventory_layout';

//Load column layout
$layout  = load_layout($name_layout, $category);
$s_type  = (GET('s_type') == 'nmap' || GET('s_type') == 'ocs' || GET('s_type') == 'wmi') ? GET('s_type') : 'nmap';

$_SESSION['av_inventory_type'] = $s_type; 

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php echo _('OSSIM Framework');?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<meta http-equiv="X-UA-Compatible" content="IE=7"/>	
	<script type="text/javascript" src="../js/jquery.min.js"></script>
	<script type="text/javascript" src="../js/jquery.flexigrid.js"></script>
	<script type="text/javascript" src="../js/notification.js"></script>
	<script type="text/javascript" src="../js/token.js"></script>
	
	<link rel="stylesheet" type="text/css" href="../style/jquery-ui-1.7.custom.css"/>
	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>	
	<link rel="stylesheet" type="text/css" href="../style/flexigrid.css"/>
	<style type='text/css'>
		
		body 
		{ 
			margin: 0px;
		}
		
		#headerh1
		{
			width:100%;
			
			height:1px;
		}
		
		#container
		{
    		position:relative;
    		margin:10px auto;
		}
		
		#flextable
		{
			display:none;
		}		
	
		table, th, tr, td 
		{
			background:transparent;			
			border:none;
			padding:0px; 
			margin:0px;
		}
		
		input, select 
		{			
			border: 1px solid #8F8FC6;
			font-size:12px; 
			font-family:arial; 
			vertical-align:middle;
			padding:0px; 
			margin:0px;
		}
	</style>
	
	
	<script type="text/javascript">
	
		function action(com,grid) 
		{
			var items = $('.trSelected', grid);
			
			if (com == '<?php echo _('Delete selected')?>') 
			{
				if (typeof(items[0]) != 'undefined') 
				{					
					if (confirm("<?php echo Util::js_entities(_('Are you sure to delete this inventory task?')) ?>")) 
					{
						var dtoken = Token.get_token("form_task");
						
						$("#flextable").changeStatus('<?php echo _('Deleting Task')?>...', false);
						
						//AJAX data
                        var d_data = {
                            "s_type"   : "<?php echo $s_type ?>",
                            "mode"     : "delete",
                            "delete"   : items[0].id.substr(3),                                                          
                            "token"    : dtoken
                        };
						
						$.ajax({
							type: "GET",
							url: "task_edit.php",
							data: d_data,
							dataType: "json",
							cache: false,
							error: function(msg){
								
								var msg = '<?php echo _("Permission error!! You can not remove this task")?>';
								
								notify(msg, 'nf_error');
								
								$("#flextable").changeStatus('', false);								
							},
							success: function(msg) {
								if (typeof(msg) != 'undefined' && msg != null)
								{
									var msg_text = msg.data;
									var msg_type = (msg.status == 'OK') ? 'nf_success' : 'nf_error';
									
									$("#flextable").changeStatus('', false);
									
									notify(msg_text, msg_type);
									
									$("#flextable").flexReload();
								}
							}
						});
					}					
				}
				else 
				{
					alert('<?php echo Util::js_entities(_('You must select a task'))?>');
				}
			}
			else if (com == '<?php echo _('Modify')?>')
			{
				if (typeof(items[0]) != 'undefined') 
				{
					document.location.href = 'task_edit.php?id='+items[0].id.substr(3)+'&s_type=<?php echo $s_type?>';
				}
				else 
				{
					alert('<?php echo Util::js_entities(_('You must select a task'))?>');
				}
			}
			else if (com == '<?php echo _('New')?>') 
			{
				document.location.href = 'task_edit.php?s_type=<?php echo $s_type?>';
			}
			else if (com == '<?php echo _('Apply')?>') 
			{
				<?php $back = preg_replace ('/&msg\=(\w+)/', '', $_SERVER["REQUEST_URI"]);?>
				
				document.location.href = '../conf/reload.php?what=tasks&back=<?php echo urlencode($back); ?>';
			}
			
		}
	
		function linked_to(rowid) 
		{
			document.location.href = 'task_edit.php?s_type=<?php echo $s_type ?>&id='+rowid;
		}	
	
		function menu_action(com,id,fg,fp) 
		{
			if (com == 'modify')
			{
				if (typeof(id) != 'undefined')
				{
					document.location.href = 'task_edit.php?s_type=<?php echo $s_type ?>&id='+id;
				}
				else
				{
					alert('<?php echo Util::js_entities(_('Task unselected'))?>');
				}
			}

			if (com == 'delete') 
			{
			  if (typeof(id) != 'undefined') 
			  {
					if (confirm("<?php echo Util::js_entities(_('Are you sure to delete this inventory task?')) ?>")) 
					{
						var dtoken = Token.get_token("form_task");
												
						//AJAX data
                        var d_data = {
                            "s_type"   : "<?php echo $s_type ?>",
                            "mode"     : "delete",
                            "delete"   : id,                                                          
                            "token"    : dtoken
                        };
						
						
						$("#flextable").changeStatus('<?php echo _('Deleting Task')?>...', false);
						
						$.ajax({
							type: "GET",
							url: "task_edit.php",
							data: d_data,
							dataType: "json",
							cache: false,
							error: function(msg){
								
								var msg = "<?php echo _('Permission error!! You can not remove this task')?>";
								
								notify(msg, 'nf_error');
								
								$("#flextable").changeStatus('',false);								
							},
							success: function(msg) {
								
								if (typeof(msg) != 'undefined' && msg != null)
								{
									var msg_text = msg.data;
									var msg_type = ( msg.status == 'OK' ) ? 'nf_success' : 'nf_error';
									
									$("#flextable").changeStatus('', false);
									
									notify(msg_text, msg_type);
									
									$("#flextable").flexReload();
								}
							}
						});
					}
				}
				else
				{
				   alert('<?php echo Util::js_entities(_('Task unselected'))?>');
				}
			}

			if (com == 'new')
			{
			   document.location.href = 'task_edit.php?s_type=<?php echo $s_type ?>';
			}
		}
        

		function save_layout(clayout) 
		{
			$("#flextable").changeStatus('<?php echo _('Saving column layout')?>...', false);
			
			$.ajax({
				type: "POST",
				url: "../conf/layout.php",
				data: { name:"<?php echo $name_layout ?>", category:"<?php echo $category ?>", layout:serialize(clayout) },
				success: function(msg) {
					$("#flextable").changeStatus(msg,true);
				}
			});
		}

	$(document).ready(function()
	{
        if (typeof parent.is_lightbox_loaded == 'function' && parent.is_lightbox_loaded(window.name))
        { 			
            $('#c_lmenu').hide();
        }

		<?php 
		if (GET('msg') == 'saved') 
		{ 
			?>
			notify('<?php echo _('The Task has been saved successfully') ?>', 'nf_success');
			<?php 
		} 	
		?>

		$("#flextable").flexigrid({
			url: 'get_tasks.php?s_type=<?php echo $s_type ?>',
			dataType: 'xml',
			colModel : [
				<?php				
				switch($s_type)
				{
					case 'nmap':
						$default = array(
							'name'      => array(_('Title'),     160, 'false', 'left',   FALSE),
							'sensor'    => array(_('Sensor'),    250, 'false', 'left',   FALSE),
							'param'     => array(_('Network'),   510, 'false', 'left',   FALSE),
							'frequency' => array(_('Frequency'), 150, 'false', 'left',   FALSE),
							'enable'    => array(_('Enabled'),   90,  'false', 'center', FALSE)
						);
					break;
					
					case 'wmi':
						$default = array(
							'name'      => array(_('Title'),       160, 'false', 'left',    FALSE),
							'sensor'    => array(_('Sensor'),      200, 'false', 'left',    FALSE),
							'param'     => array(_('Credentials'), 300, 'false', 'left',    FALSE),
							'frequency' => array(_('Frequency'),   150, 'false', 'left',    FALSE),
							'enable'    => array(_('Enabled'),     350,  'false', 'center', FALSE)
						);
					break;
					
					default:
						$default = array(
							'name'      => array(_('Title'),     160, 'false', 'left',   FALSE),
							'sensor'    => array(_('Sensor'),    220, 'false', 'left',   FALSE),
							'frequency' => array(_('Frequency'), 200, 'false', 'left',   FALSE),
							'enable'    => array(_('Enabled'),   588, 'false', 'center', FALSE)
					);
				}
				
				list($colModel, $sortname, $sortorder, $height) = print_layout($layout, $default, 'name', 'asc', 300);
				
				echo "$colModel\n";
				?>
			],
			buttons : [
				{name: '<?php echo _('New')?>', bclass: 'add', onpress : action},
				{separator: true},
				{name: '<?php echo _('Modify')?>', bclass: 'modify', onpress : action},
				{separator: true},
				{name: '<?php echo _('Delete selected')?>', bclass: 'delete', onpress : action},
				],
			sortname: "<?php echo $sortname ?>",
			sortorder: "<?php echo $sortorder ?>",
			usepager: true,
			pagestat: '<?php echo _('Displaying {from} to {to} of {total} tasks')?>',
			nomsg: '<?php echo _('No '.strtoupper($s_type).' tasks found in the system')?>',
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

<body>
    <div id='container'>
        <?php 
            //Local menu             
            include_once '../local_menu.php';
            session_write_close();
        ?>
    
        <ul id="myMenu" class="contextMenu" style="width:180px">
            <li class="hostreport">
                <a href="#modify" style="padding:3px">
                    <img src="../pixmaps/tables/table_edit.png" align="absmiddle"/> <?php echo _('Modify')?>
                </a>
            </li>
            <li class="hostreport">
                <a href="#delete" style="padding:3px">
                    <img src="../pixmaps/tables/table_row_delete.png" align="absmiddle"/> <?php echo _('Delete')?>
                </a>
            </li>
            <li class="hostreport">
                <a href="#new"  style="padding:3px">
                    <img src="../pixmaps/tables/table_row_insert.png" align="absmiddle"/> <?php echo _('New Task')?>
                </a>
            </li>
        </ul>
            
    	<table id="flextable"></table>    	
    </div>
</body>
</html>