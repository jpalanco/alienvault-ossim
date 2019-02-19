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

Session::logcheck("configuration-menu", "PolicyPorts");

// load column layout
require_once '../conf/layout.php';

$category    = "policy";
$name_layout = "port";
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
  <script type="text/javascript" src="../js/notification.js"></script>
  <script type="text/javascript" src="../js/token.js"></script>

  <style type='text/css'>
		table, th, tr, td {
			background:transparent;
			border-radius: 0px;
			-moz-border-radius: 0px;
			-webkit-border-radius: 0px;
			border:none;
			padding:0px;
			margin:0px;
		}

		input, select {
			border-radius: 0px;
			-moz-border-radius: 0px;
			-webkit-border-radius: 0px;
			border: 1px solid #8F8FC6;
			font-size:12px;
			font-family:arial;
			vertical-align:middle;
			padding:0px;
			margin:0px;
		}
	</style>

	<script type='text/javascript'>

        function get_height(){
           return parseInt($(document).height()) - 200;
        }


		function linked_to(rowid) {
			document.location.href = 'modifyportform.php?id='+rowid;
		}

		function action(com,grid) {
			var items = $('.trSelected', grid);
			if (com=='<?php echo _("Delete selected")?>') {
				//Delete host by ajax
				if (typeof(items[0]) != 'undefined') {
					if (confirm("<?php echo Util::js_entities(_("Are you sure you want to delete the selected port?")) ?>")) {
						$("#flextable").changeStatus('<?php echo _("Deleting port")?>...',false);
                        var dtoken = Token.get_token("delete_port");
						$.ajax({
							type: "GET",
							url: "deleteport.php?confirm=yes&id="+items[0].id.substr(3)+"&token="+dtoken,
							data: "",
							success: function(msg) {
                                if(msg.match("Action not allowed")) {
                                    notify('<?php echo _("Action not allowed") ?>', 'nf_error');
                                }
                                else if(msg.match("ERROR_CANNOT")) {
                                    notify('<?php echo _("Sorry, cannot delete this port because it belongs to a policy") ?>', 'nf_error');
                                }
								else{
									$("#flextable").flexReload();
								}
							}
						});
					}
				}
				else{
					alert('<?php echo Util::js_entities(_("You must select a port group"))?>');
				}
			}
			else if (com=='<?php echo _("Modify")?>') {
				if (typeof(items[0]) != 'undefined') {
					document.location.href = 'modifyportform.php?id='+items[0].id.substr(3);
				}
				else {
					alert('<?php echo Util::js_entities(_("You must select a port group"))?>');
				}
			}
			else if (com=='<?php echo _("New port")?>') {
				document.location.href = 'newportform.php'
			}
		}

		function save_layout(clayout) {
			$("#flextable").changeStatus('<?php echo _("Saving column layout")?>...',false);
			$.ajax({
				type: "POST",
				url: "../conf/layout.php",
				data: { name:"<?php echo $name_layout ?>", category:"<?php echo $category ?>", layout:serialize(clayout) },
				success: function(msg) {
					$("#flextable").changeStatus(msg,true);
				}
			});
		}

		function menu_action(com,id,fg,fp) {
		   var port = id;

			if (com=='modify')
			{
				if (typeof(port) != 'undefined')
					document.location.href = 'modifyportform.php?id='+port;
				else
					alert('<?php echo Util::js_entities(_("Port unselected"))?>');
			}


			if (com=='delete') {

				if (typeof(port) != 'undefined') {
					if (confirm("<?php echo Util::js_entities(_("Are you sure you want to delete this port group?")) ?>")) {
						$("#flextable").changeStatus('<?php echo _("Deleting port group")?>...',false);
                        var dtoken = Token.get_token("delete_port");
						$.ajax({
                                type: "GET",
                                url: "deleteport.php?confirm=yes&id="+port+"&token="+dtoken,
                                data: "",
                                success: function(msg) {
                                    if(msg.match("Action not allowed")) {
                                        notify('<?php echo _("Action not allowed") ?>', 'nf_error');
                                    }
                                    else if(msg.match("ERROR_CANNOT")) {
                                        notify('<?php echo _("Sorry, cannot delete this port because it belongs to a policy") ?>', 'nf_error');
                                    }
                                    else
                                        $("#flextable").flexReload();
                                }
						});
					}
				}
				else
					alert('<?php echo Util::js_entities(_("Port unselected"))?>');
			}

			if (com == 'newport')
			  document.location.href = 'newportform.php';
		}

		$(document).ready(function(){

			<?php
			if ( GET('msg') == "created" )
			{
				?>
				notify('<?php echo _("The Port has been created successfully") ?>', 'nf_success');
				<?php
			}
			elseif ( GET('msg') == "updated" )
			{
				?>
				notify('<?php echo _("The Port has been updated successfully") ?>', 'nf_success');
				<?php
			}
			elseif ( GET('msg') == "unknown_error" )
			{
				?>
				notify('<?php echo _("Invalid action - Operation cannot be completed")?>', 'nf_error');
				<?php
			}
			?>

			$("#flextable").flexigrid({
				url: 'getport.php',
				dataType: 'xml',
				colModel : [
					<?php
					$default = array(
						"port_number" => array(
							_("Port"),
							50,
							'true',
							'left',
							false
						),
						"protocol" => array(
							_("Protocol"),
							100,
							'false',
							'left',
							false
						),
						"service" => array(
							_("Service"),
							310,
							'false',
							'left',
							false
						),
						"desc" => array(
							_("Description"),
							708,
							'false',
							'left',
							false
						)
					);

					list($colModel, $sortname, $sortorder, $height) = print_layout($layout, $default, "port_number", "asc", 300);
					echo "$colModel\n";
					?>
					],
				buttons : [
					{name: '<?php echo _("New port")?>', bclass: 'add', onpress : action},
					{separator: true},
					{name: '<?php echo _("Modify")?>', bclass: 'modify', onpress : action},
					{separator: true},
					{name: '<?php echo _("Delete selected")?>', bclass: 'delete', onpress : action}
					],
				sortname: "<?php echo $sortname?>",
				sortorder: "<?php echo $sortorder?>",
				usepager: true,
				pagestat: '<?php echo _("Displaying {from} to {to} of {total} ports")?>',
				nomsg: '<?php echo _("No ports found in the system")?>',
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
<body style="margin:0px">

<?php
    //Local menu
    include_once '../local_menu.php';
?>
	<table id="flextable" style="display:none"></table>

    <!-- Right Click Menu -->
	<ul id="myMenu" class="contextMenu" style="width:130px">
	    <li class="hostreport"><a href="#modify" class="greybox" style="padding:3px"><img src="../pixmaps/tables/table_edit.png" align="absmiddle"/> <?php echo _("Modify")?></a></li>
        <li class="hostreport"><a href="#delete" class="greybox" style="padding:3px"><img src="../pixmaps/tables/table_row_delete.png" align="absmiddle"/> <?php echo _("Delete")?></a></li>
        <li class="hostreport"><a href="#newport" class="greybox" style="padding:3px"><img src="../pixmaps/tables/table_row_insert.png" align="absmiddle"/> <?php echo _("New Port")?></a></li>
    </ul>
</body>
</html>
