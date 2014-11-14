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
require_once 'server_get_servers.php';


Session::logcheck('configuration-menu', 'PolicyServers');

$db         = new ossim_db();
$conn       = $db->connect();

$browser    = new Browser(); //For checking the browser

$servers    = array();
$servers    = Server::get_list($conn);

list($total_servers, $active_servers) = server_get_servers($servers);

$active_servers = ($active_servers == 0) ? "<font color=red><b>$active_servers</b></font>" : "<font color=green><b>$active_servers</b></font>";
$total_servers  = "<b>$total_servers</b>";



/*********  Arbor Info  *********/
$data = array();
$edge = array();

foreach($servers as $server)
{

	$item   = "'".$server->get_id()."':{";
	
	$data[] = "'".$server->get_id()."':{'color':'green','shape':'rectangle','label':'". $server->get_name() ."'}";
	
	// get childs with uuid like a parent
	$sql = "SELECT distinct(HEX(server_dst_id)) as id FROM server_forward_role WHERE server_src_id=UNHEX(?)";
	
	if (!$rs = $conn->Execute($sql, array($server->get_id()))) 
	{
	    Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
	}
	
	$aux = array();
	
	while (!$rs->EOF) 
	{
	    $aux[] = "'".$rs->fields["id"]."':{directed:true, length:5, weight:2, color: '#999999'}";
	    
	    $rs->MoveNext();
	}
	
	$item .= implode(",", $aux);
	$item .= '}';
	
	$edge[] = $item;
	
	
}
    	
$data_txt = implode(',', $data);
$edge_txt = implode(',', $edge);

/*********  End of Arbor  *********/



// load column layout
require_once '../conf/layout.php';

$category    = 'policy';
$name_layout = 'servers_layout';
$layout      = load_layout($name_layout, $category);


$db->close();
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
	<link type="text/css" rel="stylesheet" href="../style/tree.css" />
	
	<script type="text/javascript" src="../js/jquery.min.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.min.js"></script>
	<script type="text/javascript" src="../js/jquery.flexigrid.js"></script>
	<script type="text/javascript" src="../js/jquery.tmpl.1.1.1.js"></script>  
	<script type="text/javascript" src="../js/jquery.dynatree.js"></script>
	<script type="text/javascript" src="../js/urlencode.js"></script>
	<script type="text/javascript" src="../js/notification.js"></script>
	<script language="javascript" type="text/javascript" src="../js/arbor/arbor.js" ></script>
	<script language="javascript" type="text/javascript" src="../js/arbor/graphics.js" ></script>
	<script language="javascript" type="text/javascript" src="../js/arbor/renderer.js" ></script>
	<script type="text/javascript" src="../js/token.js"></script>
	
	<script type='text/javascript'>

		$(document).ready(function(){
						
			<?php 
			if (GET('msg') == "created") 
			{ 
				?>
				notify('<?php echo _("The Server has been created successfully")?>', 'nf_success');
				<?php 
			} 
			elseif (GET('msg') == "updated") 
			{ 
				?>
				notify('<?php echo _("The Server has been updated successfully")?>', 'nf_success');
				<?php 
			}
			elseif (GET('msg') == "nodeleteremote") 
			{ 
				?>
				notify('<?php echo _("Unable to delete a parent server. Go to Configuration->Deployment->Alienvault Center and delete the system")?>', 'nf_error');
				<?php 
			}
			elseif (GET('msg') == "nodelete") 
			{ 
				?>
				notify('<?php echo _("Unable to delete the local server")?>', 'nf_error');
				<?php 
			}
			elseif (GET('msg') == "unknown_error") 
			{ 
				?>
				notify('<?php echo _("Sorry, operation was not completed due to an unknown error")?>', 'nf_error');
				<?php 
			} 
			elseif (GET('msg') == "unallowed") 
			{ 
				?>
				notify('<?php echo _("Sorry, action not allowed")?>', 'nf_error');
				<?php 
			}  
			
			if (Session::is_pro() && $browser->name !='msie') 
			{ 
				?>			
				var sys = arbor.ParticleSystem({friction:0.5, stiffness:500, repulsion:700, dt:<?php echo (count($data) > 1)? '0.009' : 0 ?>}) // create the system with sensible repulsion/stiffness/friction 
				sys.parameters({gravity:true}); 
				sys.renderer = Renderer("#viewport") ;
				var data = {
					nodes: {
					<?php                	
						echo $data_txt;
					?>
					}, 
					edges:{
					<?php                	
						echo $edge_txt;
					?>
					}
				};
				sys.graft(data);
				
				setTimeout(function(){
					sys.parameters({dt:0});
				}, 7500);

				<?php 
			} 
			?>
		
			$("#flextable").flexigrid({
				url: 'getserver.php',
				dataType: 'xml',
				colModel : [
				<?php
				$default = array(
					"ip" => array(
						_('IP'),
						80,
						'true',
						'center',
						false
					) ,
					"name" => array(
						_('Name'),
						180,
						'true',
						'center',
						false
					) ,
					"port" => array(
						_('Port'),
						30,
						'true',
						'center',
						false
					) ,
					"sim" => array(
						_('SIEM'),
						40,
						'false',
						'center',
						false
					) ,
					"qualify" => array(
						_('Risk Assessment'),
						65,
						'false',
						'center',
						false
					) ,
					"correlate" => array(
						_('Correlation'),
						75,
						'false',
						'center',
						false
					) ,
					"cross correlate" => array(
						_('Cross correlation'),
						75,
						'false',
						'center',
						false
					) ,
					"store" => array(
						_('SQL Storage'),
						50,
						'false',
						'center',
						false
					) ,
					"alarm to syslog" => array(
						_('Alarm Syslog'),
						50,
						'false',
						'center',
						false
					) ,
					"reputation" => array(
						_('IP Rep'),
						30,
						'false',
						'center',
						false
					) ,
					"sem" => array(
						_('Logger'),
						50,
						'false',
						'center',
						false
					) ,
					"sign" => array(
						_('Sign'),
						45,
						'false',
						'center',
						false
					) ,
					"resend_alarms" => array(
						_('Forward Alarms'),
						50,
						'false',
						'center',
						false
					) ,
					"resend_events" => array(
						_('Forward Events'),
						50,
						'false',
						'center',
						false
					) ,
					"desc" => array(
						_('Description'),
						210,
						'false',
						'left',
						false
					)
				);
				list($colModel, $sortname, $sortorder, $height) = print_layout($layout, $default, "name", "asc", 300);
				echo "$colModel\n";
				?>
					],
				buttons : [
					<?php if (Session::is_pro()) { ?>
					{name: '<?=_("New")?>', bclass: 'add', onpress : action},
					{separator: true},
					{name: '<?=_("Delete selected")?>', bclass: 'delete', onpress : action},
					{separator: true},
					<?php } ?>
					{name: '<?=_("Modify")?>', bclass: 'modify', onpress : action},
					{separator: true},
					{name: '<?=_("Active Servers")?>: <?php echo $active_servers ?>', bclass: 'info', iclass: 'ibutton'},
					{name: '<?=_("Total Servers")?>: <?php echo $total_servers ?>', bclass: 'info', iclass: 'ibutton'}
					],
				sortname: "<?php echo $sortname ?>",
				sortorder: "<?php echo $sortorder ?>",
				usepager: true,
				pagestat: '<?=_("Displaying")?> {from} <?=_("to")?> {to} <?=_("of")?> {total} <?=_("servers")?>',
				nomsg: '<?=_("No servers found in the system")?>',
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
	
	
		function action(com,grid) 
		{
			var items = $('.trSelected', grid);
			
			if (com == '<?php echo _('Delete selected')?>') 
			{
				//Delete host by ajax
				if (typeof(items[0]) != 'undefined') 
				{
					if(confirm('<?php echo Util::js_entities(_("Do you want to delete this server?")) ?>')) 
					{
						var dtoken = Token.get_token("delete_server");
						document.location.href = 'deleteserver.php?confirm=yes&id='+urlencode(items[0].id.substr(3))+"&token="+dtoken;
					}
				}
				else 
				{
				    alert('<?=Util::js_entities(_("You must select a server"))?>');
				}
			}
			else if (com == '<?php echo _('Modify')?>') 
			{
				if (typeof(items[0]) != 'undefined') 
				{
				    document.location.href = 'newserverform.php?id='+urlencode(items[0].id.substr(3))
				}
				else 
				{
				    alert('<?=Util::js_entities(_("You must select a server"))?>');
				}
			}
			else if (com == '<?php echo _('New')?>') 
			{
				document.location.href = 'newserverform.php'
			}
		}
		
		
		function save_layout(clayout) 
		{
			$("#flextable").changeStatus('<?=_("Saving column layout")?>...', false);
			
			$.ajax({
				type: "POST",
				url: "../conf/layout.php",
				data: { name:"<?php echo $name_layout ?>", category:"<?php echo $category ?>", layout:serialize(clayout) },
				success: function(msg) {
					$("#flextable").changeStatus(msg,true);
				}
			});
		}

        function apply_changes()
        {
			<?php $back = preg_replace ('/([&|\?]msg\=)(\w+)/', '\\1', $_SERVER["REQUEST_URI"]);?>
			document.location.href = '../conf/reload.php?what=servers&back=<?php echo urlencode($back);?>'         
        }		
		
		function linked_to(rowid) 
		{
			document.location.href = 'newserverform.php?id='+urlencode(rowid);
		}	
		
		function menu_action(com,id,fg,fp) 
		{
			if (com=='<?php echo _('delete')?>') 
			{
				//Delete host by ajax
				if (typeof(id) != 'undefined') 
				{
					if(confirm('<?php echo Util::js_entities(_("Do you want to delete this server?")) ?>')) 
					{
						var dtoken = Token.get_token("delete_server");
						document.location.href = 'deleteserver.php?confirm=yes&id='+id+"&token="+dtoken;
					}
				}
				else 
				{
				   alert('<?=Util::js_entities(_("Server unselected"))?>');
				}
			}

			if (com=='<?php echo _('modify')?>') 
			{
				if (typeof(id) != 'undefined') 
				{
				   document.location.href = 'newserverform.php?id='+id
				}
				else 
				{
				   alert('<?=Util::js_entities(_("Server unselected"))?>');
				}
			}

			if (com == '<?php echo _('newport')?>')
			{
			    document.location.href = 'newserverform.php';
			}	  
		}  
	
	</script>
	
</head>
<body style="margin:0">

    <?php 
    //Local menu		      
    include_once '../local_menu.php';
    ?>
		
	<table id="flextable" style="display:none"></table>
    <?php
        if (Web_indicator::is_on("Reload_servers"))
        {
            echo "<button class='button' onclick='apply_changes()'>"._("Apply Changes")."</button>";
        }
    ?>    
    	
	<?php 
	if (Session::is_pro()) 
	{ 	
	    ?>
		<br>
		<div style='padding-bottom:5px;font-size:13px;'>
			<img src="../pixmaps/arrow_green.gif" align="absmiddle" border="0"/>
			<a href='javascript:;' class='lnk_sh' onclick="$('#server_hierarchy').toggle()"><?php echo _("Server Hierarchy") ?></a>
		</div>
		
		
		<div id='server_hierarchy'>
			<?php 
			if ($browser->name =='msie') 
			{ 			
                ?>
                <div style='font-weight:bold;'><?php echo _('Server Hierarchy Graph is not available in Internet Explorer') ?></div>
                <?php 
            } 
            ?>
			<canvas id="viewport" width='800' height="250"></canvas>
		</div>
		<br>
		<?php 
    } 
    ?>	
	
	<!-- Right Click Menu -->
	<ul id="myMenu" class="contextMenu">
        <li class="hostreport"><a href="#modify" class="greybox" style="padding:3px"><img src="../pixmaps/tables/table_edit.png" align="absmiddle"/> <?=_("Modify")?></a></li>
        <li class="hostreport"><a href="#delete" class="greybox" style="padding:3px"><img src="../pixmaps/tables/table_row_delete.png" align="absmiddle"/> <?=_("Delete")?></a></li>
        <li class="hostreport"><a href="#newport" class="greybox" style="padding:3px"><img src="../pixmaps/tables/table_row_insert.png" align="absmiddle"/> <?=_("New Server")?></a></li>
    </ul>

</body>
</html>
