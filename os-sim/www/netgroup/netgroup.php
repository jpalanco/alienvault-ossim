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
Session::logcheck('environment-menu', 'PolicyNetworks');

// load column layout
require_once '../conf/layout.php';

$category    = 'policy';
$name_layout = 'net_group_layout';

$layout = load_layout($name_layout, $category);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title> <?php echo _('OSSIM Framework'); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<meta http-equiv="X-UA-Compatible" content="IE=7" />
	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<link rel="stylesheet" type="text/css" href="../style/flexigrid.css"/>
	<script type="text/javascript" src="../js/jquery.min.js"></script>
	<script type="text/javascript" src="../js/jquery.flexigrid.js"></script>
	<script type="text/javascript" src="../js/urlencode.js"></script>
	<script type="text/javascript" src="../js/greybox.js"></script>
	<script type="text/javascript" src="../js/notification.js"></script>
	<script type="text/javascript" src="../js/token.js"></script>

	<style type='text/css'>

		#av_ng_notif
		{
    	    position: absolute;
    	    width: 100%;
    	    top: 40px;
    		text-align: center;
            margin: 0 auto;
            z-index: 999;
		}
	</style>

	<script type='text/javascript'>

		GB_TYPE = 'w';

		function GB_onclose()
    	{
            document.location.reload();
    	}

		function GB_edit(url)
		{
			GB_show("<?php echo _('Knowledge DB')?>", url, "460", "700");
			return false;
		}

		function GB_notes(url)
		{
			GB_show("<?php echo _('Asset Notes')?>", url, "460", "700");
			return false;
		}

        function get_height()
        {
            return parseInt($(document).height()) - 200;
        }


        var notif_style = 'width:400px%; text-align:center; margin:0px auto';

        function delete_netgroup(name)
        {
            var dtoken = Token.get_token("ng_form");

			$.ajax({
				type: "POST",
				url: "netgroup_actions.php",
				data: {
				    "action": "delete_netgroup",
				    "name": name,
				    "token": dtoken
				},
				dataType: "json",
				beforeSend: function()
				{
					$("#flextable").changeStatus('<?=_('Deleting network group')?>...', false);

					$('#av_msg_info').remove();
					$('#av_ng_notif').empty();
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
                    var _msg = "<?php echo _('Sorry, operation was not completed due to an error when processing the request')?>";

					show_notification('av_ng_notif', _msg, 'nf_error', 7500, true, notif_style);
				},
				success: function(data)
				{
                    var cnd_1  = (typeof(data) == 'undefined' || data == null);
                    var cnd_2  = (typeof(data) != 'undefined' && data != null && data.status != 'OK');

                    if (!cnd_1 && !cnd_2)
                    {
                        show_notification('av_ng_notif', data.data, 'nf_success', 7500, true, notif_style);

    				    $('.flexigrid .reload').addClass('reload_red').removeClass('reload');
    				    $("#flextable").flexReload();
                    }
                    else
                    {
                        show_notification('av_ng_notif', data.data, 'nf_error', 7500, true, notif_style);
                    }
				}
			});
        }


        function action(com, grid)
		{
			var items = $('.trSelected', grid);

			if (com == '<?=_("Delete selected")?>')
			{
				//Delete host by ajax
				if (typeof(items[0]) != 'undefined')
				{
					if (confirm("<?php echo Util::js_entities(_('Are you sure you want to delete the selected network group?')) ?>"))
					{
						for (var ids='',i=0;i<items.length;i++)
						{
	                        ids = ids + (ids!='' ? ';' : '') + items[i].id.substr(3);
						}

	                    delete_netgroup(ids);
					}
				}
				else
				{
				    alert('<?php echo Util::js_entities(_("You must select a network group"))?>');
				}
			}
			else if (com == '<?=_("Modify")?>')
			{
				if (typeof(items[0]) != 'undefined')
				{
				    document.location.href = 'netgroup_form.php?id='+items[0].id.substr(3)
				}
				else
				{
				    alert('<?php echo Util::js_entities(_('You must select a network group'))?>');
				}
			}
			else if (com == '<?=_("New")?>')
			{
				document.location.href = 'netgroup_form.php'
			}
	    }

		function linked_to(rowid)
		{
			document.location.href = 'netgroup_form.php?id='+rowid;
		}

		function menu_action(com,id,fg,fp)
		{
			if (com == 'modify')
			{
				if (typeof(id) != 'undefined')
				{
					document.location.href = 'netgroup_form.php?id='+id
				}
				else
				{
					alert('<?php echo Util::js_entities(_('Network group unselected'))?>');
				}
			}


			if (com == 'delete')
			{
				if (typeof(id) != 'undefined')
				{
					if (confirm("<?php echo Util::js_entities(_("Are you sure you want to delete this network group?"))?>"))
					{
						var name = urlencode(id);

                        delete_netgroup(name);
					}
				}
				else
				{
					alert('<?php echo Util::js_entities(_("Network group unselected"))?>');
				}
			}

			if (com == 'new')
			{
			   document.location.href = 'netgroup_form.php';
			}
		}

		function save_layout(clayout)
		{
			$("#flextable").changeStatus('<?=_('Saving column layout')?>...', false);

			$.ajax({
				type: "POST",
				url: "../conf/layout.php",
				data: { name:"<?php echo $name_layout?>", category:"<?php echo $category?>", layout:serialize(clayout) },
				success: function(msg) {
					$("#flextable").changeStatus(msg, true);
				}
			});
		}

        $(document).ready(function(){
			<?php
			if (GET('msg') == 'saved')
			{
				?>
				notify('<?php echo _("The Network Group has been saved successfully")?>', 'nf_success');
				<?php
			}
			elseif (GET('msg') == 'unknown_error')
			{
				?>
				notify('<?php echo _("Invalid action - Operation cannot be completed")?>', 'nf_error');
				<?php
			}
			?>

			$("#flextable").flexigrid({
    			url: 'getnetgroup.php',
    			dataType: 'xml',
    			colModel : [
    			    <?php
    				$default = array(
    					'name' => array(
    						_('Name'),
    						230,
    						'true',
    						'left',
    						FALSE
    					) ,
    					'networks' => array(
    						_('Networks'),
    						355,
    						'false',
    						'left',
    						FALSE
    					),
    					'desc' => array(
    						_('Description'),
    						416,
    						'false',
    						'left',
    						FALSE
    					) ,
    					'repository' => array(
    						_('Knowledge DB')."&nbsp;<a href='".Menu::get_menu_url('../repository/index.php', 'configuration', 'threat_intelligence', 'knowledgebase')."'><img src='../pixmaps/tables/table_edit.png' title='Edit KDB' alt='Edit KDB' border='0' align='absmiddle'/></a>",
    						110,
    						'false',
    						'center',
    						FALSE
    					),
    					'notes' => array(
    						_('Notes'),
    						49,
    						'false',
    						'center',
    						FALSE
                       )
    				);
    				list($colModel, $sortname, $sortorder, $height) = print_layout($layout, $default, "name", "asc", 300);
    				echo "$colModel\n";
    				?>
    			],
    			buttons : [
    				<?php
    				if (Session::can_i_create_assets() == TRUE)
    				{
        				?>
        				{name: '<?=_('New')?>', bclass: 'add', onpress : action},
        				{separator: true},
        				<?php
    				}
    				?>
    				{name: '<?=_('Modify')?>', bclass: 'modify', onpress : action},
    				{separator: true},
    				{name: '<?=_('Delete selected')?>', bclass: 'delete', onpress : action}
    				//{separator: true},
    				//{name: '<?=_("Enable/Disable")?> <b><?=_("Nessus")?></b>', bclass: 'various', onpress : action},
    				],
    			searchitems : [
    				{display: '<?=_('Name')?>', name : 'name', isdefault: true}
    			],
    			sortname: "<?php echo $sortname ?>",
    			sortorder: "<?php echo $sortorder ?>",
    			usepager: true,
    			pagestat: '<?=_("Displaying {from} to {to} of {total} network groups")?>',
    			nomsg: '<?=_('No network groups found in the system')?>',
    			useRp: true,
    			rp: 20,
    			contextMenu: 'myMenu',
    			onContextMenuClick: menu_action,
    			showTableToggleBtn: false,
    			//singleSelect: true,
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


	<div id='av_ng_notif'></div>

	<br><table id="flextable" style="display:none"></table>

    <ul id="myMenu" class="contextMenu" style="width:150px">
        <li class="hostreport">
            <a href="#modify" class="greybox" style="padding:3px"><img src="../pixmaps/tables/table_edit.png" align="absmiddle"> <?=_('Modify')?></a>
        </li>
        <li class="hostreport">
            <a href="#delete" class="greybox" style="padding:3px"><img src="../pixmaps/tables/table_row_delete.png" align="absmiddle"> <?=_('Delete')?></a>
        </li>

        <?php
		if (Session::can_i_create_assets() == TRUE)
		{
			?>
			<li class="hostreport">
            <a href="#new" class="greybox" style="padding:3px"><img src="../pixmaps/tables/table_row_insert.png" align="absmiddle"> <?=_('New Network Group')?></a>
            </li>
			<?php
		}
    	?>
    </ul>
</body>
</html>
