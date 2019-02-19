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


Session::logcheck("configuration-menu", "PolicyPolicy");


require_once '../conf/layout.php';



function get_policy_entities($conn)
{
	$entities = $entities_all = array();
	$ctx_pro  = '';
	
	$entities_all = Acl::get_entities_to_assign($conn);	
		
	foreach ($entities_all as $k => $v) 
	{
		if(Acl::is_logical_entity($conn, $k))
		{
			$parent_id   = Acl::get_logical_ctx_id($conn, $k);
			$parent_id   = $parent_id[0]; // first
			$parent_name = Acl::get_entity_name($conn,$parent_id);
			
			if(!empty($parent_id))
			{
				$entities[$parent_id] = $parent_name;
			}
		} 
		else
		{
			$entities[$k] = $v;
		}
		
	}

	asort($entities);
	
	$ctx_pro  = array_shift(array_keys($entities));

	return array($entities, $ctx_pro);
}


$db       = new ossim_db();
$conn     = $db->connect();

$pro      = Session::is_pro();
$avmssp   = true; //intval($conf->get_conf("alienvault_mssp", FALSE));

$contexts = array();
$engines  = array();

$ctx_prev = '';


if($pro)
{		
	list($entities, $ctx_prev) = get_policy_entities($conn, $ctx_pro);
}
else
{
	$ctx_prev            = Session::get_default_ctx();
	$entities[$ctx_prev] = Session::get_entity_name($conn, $ctx_prev);
}


$ctx     = (GET("ctx") != "") ? GET("ctx") : $_SESSION['policy_ctx'];
$reorder = GET("reorder");

if(empty($ctx))
{
	$ctx = $ctx_prev;
}

ossim_valid($ctx,		OSS_HEX,					'illegal:' . _("ctx"));
ossim_valid($reorder,	OSS_DIGIT, OSS_NULLABLE,	'illegal:' . _("Policy Option"));

if (ossim_error()) 
{
    die(ossim_error());
}


$_SESSION['policy_ctx']    = $ctx;
$conf                      = $GLOBALS["CONF"];
$server_logger_if_priority = (is_null($conf->get_conf("server_logger_if_priority",false))) ? 0 : $conf->get_conf("server_logger_if_priority");
$engines                   = Session::get_engines_by_ctx($conn, $ctx);


if($reorder)
{
	Policy_group::reassing_orders($conn, $ctx);
	Policy::reassing_orders($conn, $ctx);
	
	foreach($engines as $engine)
	{
		Policy::reassing_orders($conn, $engine);
	}
}


//Retrieving the policy groups, for ctx and engines
$groups_ctx    = Policy::get_policy_groups($conn, $ctx);
$groups_engine = array();

foreach($engines as $engine)
{
	$aux_groups = Policy::get_policy_groups($conn, $engine, true);
	
	$groups_engine = array_merge($groups_engine, $aux_groups);

}

$refresh       = "";


$default = array
(
	"active" => array(
		_('Status'),
		30,
		'true',
		'center',
		false
	) ,
	"order" => array(
		_('Ord'),
		30,
		'true',
		'center',
		false
	) ,
	"descr" => array(
		_('Name'),
		130,
		'true',
		'left',
		false
	) ,
	"source" => array(
		' <b>'._('Source').'</b> <img src="../pixmaps/tables/bullet_prev.png" border=0 align=absmiddle>',
		150,
		'false',
		'left',
		false
	) ,
	"dest" => array(
		' <b>'._('Destination').'</b> <img src="../pixmaps/tables/bullet_next.png" border=0 align=absmiddle>',
		150,
		'false',
		'left',
		false
	) ,
	"port_source" => array(
		_('Source Port'),
		75,
		'false',
		'center',
		false
	) ,
	"port_dest" => array(
		_('Dest Port'),
		70,
		'false',
		'center',
		false
	) ,
	"plugin_group" => array(
		_('Event Types'),
		90,
		'false',
		'center',
		false
	) ,
	"sensors" => array(
		_('Sensors'),
		80,
		'false',
		'center',
		false
	) ,
	"time_range" => array(
		_('Time Range'),
		150,
		'false',
		'center',
		false
	) ,
	"targets" => array(
		_('Targets'),
		70,
		'false',
		'center',
		false
	) ,
	"SIM" => array(
		_('SIEM'),
		25,
		'false',
		'center',
		false
	) ,
	"priority" => array(
		_('Set Priority'),
		40,
		'true',
		'center',
		false
	) ,
	"qualify" => array(
		_('Risk Assessment'),
		30,
		'false',
		'center',
		false
	) ,
	"correlate" => array(
		_('Logical Correlation'),
		30,
		'false',
		'center',
		false
	) ,
	"cross correlate" => array(
		_('Cross-correlation'),
		30,
		'false',
		'center',
		false
	) ,
	"store" => array(
		_('SQL Storage'),
		30,
		'false',
		'center',
		false
	) ,
	"SEM" => array(
		_('Logger'),
		25,
		'false',
		'center',
		false
	) ,
	"Sign" => array(
		_('Sign'),
		30,
		'false',
		'center',
		false
	) ,
	"resend_events" => array(
		_('Resend Events'),
		30,
		'false',
		'center',
		false
	)
); 	

$def_eng_layout = $default;
unset($def_eng_layout['source']);
unset($def_eng_layout['dest']);
unset($def_eng_layout['port_source']);
unset($def_eng_layout['port_dest']);
unset($def_eng_layout['sensors']);
unset($def_eng_layout['SEM']);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title> <?php echo _("OSSIM Framework"); ?> </title>
	
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	
	<link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>	
	
	<script type="text/javascript" src="/ossim/js/jquery.min.js"></script>	
	<script type="text/javascript" src="/ossim/js/urlencode.js"></script>
	<script type="text/javascript" src="/ossim/js/greybox.js"></script>
	<script type="text/javascript" src="/ossim/js/notification.js"></script>
	<script type="text/javascript" src="/ossim/js/token.js"></script>
	
	
	<!-- JQuery Flexigrid: -->
	<script type="text/javascript" src="/ossim/js/policy.flexigrid.js"></script>
	<link rel="stylesheet" type="text/css" href="/ossim/style/policy.flexigrid.css"/>
	
	<!-- JQuery TipTip: -->
	<link rel="stylesheet" type="text/css" href="/ossim/style/tipTip.css"/>
	<script type="text/javascript" src="/ossim/js/jquery.tipTip-ajax.js"></script>
	
	<script type="text/javascript" src="js/policy.js.php"></script>
  
  
	<style type='text/css'>
		table, th, tr, td {
			background:transparent;
			border-radius: 0px;
			-moz-border-radius: 0px;
			-webkit-border-radius: 0px;
			border:none;
			padding:0px; 
			margin: 0px;
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
		
		.flexigrid div.ftitle {
			font-size: 15px !important;		
		}
		.flexigrid div.mhDiv div {
			padding: 5px 6px 6px 20px;
		}
		
		.down, .up {
			border: none !important;
		}
		
		.flex_eng div.flexigrid div.mhDiv{
			background: #CFD8C3;
		}

		#entities_list {
			position: absolute;
			top:15px;
			left:20px;
		}
		
		#policy_collision_list
		{
    		padding: 5px 20px;
		}
		
	</style>
	
</head>
<body>

    <?php 
        //Local menu		      
        include_once '../local_menu.php';
    ?>

	<?php if(count($entities) > 1) { ?>
	<div id='entities_list'>
		<table>
			<tr>
				<td class="nobborder" nowrap='nowrap'><span style='margin-right: 3px;font-size:12px;'><?php echo _("Events from");?>: </span></td>
				<td class="nobborder">
					<select name="policy_entity" id="policy_entity" class="vfield" onchange="change_entity();">										
						<?php				
						foreach ($entities as $k => $v) 
						{
							echo "<option value='$k' ".($ctx==$k ? "selected": "").">$v</option>";
						}						
						?>
					</select>
				</td>
			</tr>
		</table>
	</div>
	<?php 
	}
	?>
	<table class="noborder">
		<?php		
		$i = 0;
		foreach($groups_ctx as $group) 
		{
			$refresh.= "$('#flextable_ctx_$i').flexReload();\n"
			?>
			<tr>
				<td valign="top" id="group<?php echo $group->get_group_id() ?>">
					<table id="flextable_ctx_<?php echo $i ?>" style="display:none"></table>
					<br>
				</td>
			</tr>
			<?php
			$i++;
		} 
		
		
		echo "<tr><td><br></td></tr>";
		
		$j = 0;
		foreach($groups_engine as $group) 
		{
			$refresh.= "$('#flextable_eng_$j').flexReload();\n"
			?>
			<tr>
				<td valign="top" class='flex_eng'>
					<table id="flextable_eng_<?php echo $j ?>" style="display:none"></table>
					<br>
				</td>
			</tr>
			<?php
			$j++;
		}		
		
		$url  = Menu::get_menu_url('/ossim/conf/index.php', 'configuration', 'administration', 'main');
		$url .= "&section=metrics#end";
				
		?>
	</table>

	<div style='width:98%;padding-bottom:20px;margin:0 auto;'>
		<div style='float:left;'>
			<a href="<?php echo $url ?>" style="color:gray">
				<?php echo _("Security Events process priority threshold") ?>: <b><?php echo $server_logger_if_priority ?></b>
			</a>
		</div>
		<div style='float:right;'>
			<a href="javascript:;" onclick='reorder_policies();' target='main' style="color:gray">
				<?php echo _("Reorder Policies") ?>
			</a>
		</div>
	</div>

	
	<script type='text/javascript'>
	
    	function GB_onclose()
    	{
            document.location.reload();	
    	}
    	
        $(document).ready(function() 
        {
        			
        	var h1w = get_flexi_width() - 4;
        	
        	<?php
        	$i = 0;
        	foreach($groups_ctx as $group) 
        	{
        		$name_layout = "policy_layout_". $group->get_group_id() . "_" . $group->get_ctx();
        		$layout      = load_layout($name_layout, 'policy');
        	?>
        	
        		function save_layout_ctx_<?php echo $i?>(clayout,fg,stat) 
        		{
        			if(this.total < 1)
        				return false;
        				
        			$.ajax({
        					type: "POST",
        					url: "../conf/layout.php",
        					data: { name: '<?php echo $name_layout ?>', category: 'policy', layout:serialize(clayout) },
        					success: function(msg) {}
        			});
        		}
        		
        		$("#flextable_ctx_<?php echo $i ?>").flexigrid({
        			url: 'getpolicy.php?ctx=<?php echo $group->get_ctx() ?>&group=<?php echo $group->get_group_id() ?>',
        			dataType: 'xml',
        			colModel : [
        				<?php 
        					list($colModel, $sortname, $sortorder, $height) = print_layout($layout, $default, "order", "asc", 150);
        					echo "$colModel\n"; 
        				?>
        				],
        			buttons : [
        				{name: '<?=_("New")?>', bclass: 'add', onpress : action},
        				{separator: true},
        				{name: '<?=_("Modify")?>', bclass: 'modify', onpress : action},
        				{separator: true},
        				{name: '<?=_("Delete selected")?>', bclass: 'delete', onpress : action},
        				{separator: true},
        				{name: '<?=_("Duplicate selected")?>', bclass: 'duplicate', onpress : action},
        				{separator: true},
        				{name: '<?=_("Reload Policies")?>', bclass: '<?php echo (Web_indicator::is_on("Reload_policies")) ? "reload_red" : "reload" ?>', onpress : action},
        				{separator: true},
        				{name: '<?=_("<b>Enable/Disable</b> policy")?>', bclass: 'yesno', onpress : action}
        				],
        			sortname: "<?php echo $sortname ?>",
        			sortorder: "<?php echo $sortorder ?>",
        			usepager: false,
        			title: '<?php echo $group->get_name() . ": <font style=\"font-weight:normal;font-style:italic\">" . $group->get_descr() . "</font>" ?>',
        			idGroup: '<?php echo $group->get_group_id() ?>',
        			nameGroup: '<?php echo $group->get_name() ?>',
        			ctxGroup: '<?php echo $group->get_ctx() ?>',
        			typeGroup: 'ctx',
        			titleClass: 'mhDiv',
        			contextMenu: 'myMenu',
        			contextMenuh: 'myMenuh',
        			onContextMenuClick: menu_action,
        			pagestat: '',
        			nomsg: '',
        			useRp: false,
        			showTableToggleBtn: true,
        			width: h1w,
        			height: <?php echo $height ?>,
        			onToggleRow: swap_rows,
        			onToggleGrid: swap_rows_grid,
        			onTableToggle: save_state,
        			<?php
        			if (count($groups_ctx) > 1) 
        			{ ?>
        				onUpDown: toggle_group_order,
        				uptxt: '<?=_('Prioritize policy group')?>: <?php echo $group->get_name() ?>',
        				downtxt: '<?=_('De-prioritize policy group')?>: <?php echo $group->get_name() ?>',
        				<?php
        			} 
        			?>
        			onColumnChange: save_layout_ctx_<?php echo $i?>,
        			onEndResize: save_layout_ctx_<?php echo $i?>,
        			onDblClick: function(rowid) {
        				document.location.href = 'newpolicyform.php?ctx=<?=$group->get_ctx()?>&id='+urlencode(rowid);
        			},
        			onSuccess: function() {
        				$('.tiptip').tipTip();
        			}
        		});   
        		
        		<?php
        		// load state from user_config
        		$group_layout = "group_layout_". $group->get_group_id() . "_" . $group->get_ctx();
        		$state        = load_layout($group_layout, 'policy');
        		
        		if ($state != "" && !is_array($state)) {
        			if ($state == "close"){
        				echo "$('#flextable_ctx_$i').viewTableToggle();\n";
        			}
        			
        		} 
        		elseif ($i > 0) 
        		{
        			echo "	$('#flextable_ctx_$i').viewTableToggle();\n";
        		}
        		
        		$i++;
        	}
        
        	$i = 0;
        	foreach($groups_engine as $group) 
        	{
        		$name_layout = "policy_layout_". $group->get_group_id() . "_" . $group->get_ctx();
        		$layout      = load_layout($name_layout, 'policy');
        		
        		$engine      = $group->get_ctx();
        		$server      = Server::get_engine_server($conn, $group->get_ctx());
        
        		if (empty($server[$engine]['name']))
        		{
        			$server_name = _('Unknown Server');
        		}
        		else
        		{
        			$server_name = $server[$engine]['name'];
        
        			if (!empty($server[$engine]['ip']))
        			{
        				$server_name .= " (" . $server[$engine]['ip'] . ")";
        			}
        		}
        	?>
        	
        		function save_layout_engine_<?php echo $i?>(clayout,fg,stat) 
        		{
        			if(this.total < 1)
        				return false;
        				
        			$.ajax({
        					type: "POST",
        					url: "../conf/layout.php",
        					data: { name: '<?php echo $name_layout ?>', category: 'policy', layout:serialize(clayout) },
        					success: function(msg) {}
        			});
        		}
        		
        		$("#flextable_eng_<?php echo $i ?>").flexigrid({
        			url: 'getpolicy.php?ctx=<?php echo $group->get_ctx() ?>&group=<?php echo $group->get_group_id() ?>',
        			dataType: 'xml',
        			colModel : [
        				<?php
        				list($colModel, $sortname, $sortorder, $height) = print_layout($layout, $def_eng_layout, "order", "asc", 150);
        				echo "$colModel\n"; 
        				?>
        				],
        			buttons : [
        				{name: '<?=_("New")?>', bclass: 'add', onpress : action},
        				{separator: true},
        				{name: '<?=_("Modify")?>', bclass: 'modify', onpress : action},
        				{separator: true},
        				{name: '<?=_("Delete selected")?>', bclass: 'delete', onpress : action},
        				{separator: true},
        				{name: '<?=_("Duplicate selected")?>', bclass: 'duplicate', onpress : action},
        				{separator: true},
        				{name: '<?=_("Reload Policies")?>', bclass: '<?php echo (Web_indicator::is_on("Reload_policies")) ? "reload_red" : "reload" ?>', onpress : action},
        				{separator: true},
        				{name: '<?=_("<b>Enable/Disable</b> policy")?>', bclass: 'yesno', onpress : action}
        				],
        			sortname: "<?php echo $sortname ?>",
        			sortorder: "<?php echo $sortorder ?>",
        			usepager: false,
        			title: '<?php echo _('Policies for events generated in server') . ": <font style=\"font-weight:normal;font-style:italic\">" . $server_name . "</font>" ?>',
        			idGroup: '<?php echo $group->get_group_id() ?>',
        			nameGroup: '<?php echo $group->get_name() ?>',
        			ctxGroup: '<?php echo $group->get_ctx() ?>',
        			typeGroup: 'engine',
        			titleClass: 'mhDiv',
        			contextMenu: 'myMenu',
        			contextMenuh: 'myMenuh',
        			onContextMenuClick: menu_action,
        			pagestat: '',
        			nomsg: '',
        			useRp: false,
        			showTableToggleBtn: true,
        			width: h1w,
        			height: <?php echo $height ?>,
        			onToggleRow: swap_rows,
        			onTableToggle: save_state,
        			onToggleGrid: swap_rows_grid,
        			onColumnChange: save_layout_engine_<?php echo $i?>,
        			onEndResize: save_layout_engine_<?php echo $i?>,
        			onDblClick: function(rowid) {
        				document.location.href = 'newpolicyform.php?ctx=<?=$group->get_ctx()?>&id='+urlencode(rowid);
        			},
        			onSuccess: function() {
        				$('.tiptip').tipTip();
        			}
        		});   
        		
        		<?php
        		// load state from user_config
        		$group_layout = "group_layout_". $group->get_group_id() . "_" . $group->get_ctx();
        		$state        = load_layout($group_layout, 'policy');
        		
        		if ($state != "" && !is_array($state)) {
        			if ($state == "close"){
        				echo "$('#flextable_eng_$i').viewTableToggle();\n";
        			}
        			
        		} elseif ($i > 0) {
        			echo "	$('#flextable_eng_$i').viewTableToggle();\n";
        		
        		}
        		
        		$i++;
        		
        	}
        
        ?>
        });
        
        function refresh_all() 
        {
        	<?php echo $refresh	?>
        }
		
	</script>

	
	<!-- Right Click Menu -->
	<ul id="myMenuh" class="contextMenu">
		<li class="moveup"><a href="#prioritize"><?=_("Prioritize")?></a></li>
		<li class="movedown"><a href="#deprioritize"><?=_("De-prioritize")?></a></li>
	</ul>
	<ul id="myMenu" class="contextMenu">
	    <li class="insertbefore"><a href="#insertbefore"><?=_("Add Policy Before")?></a></li>
	    <li class="insertafter"><a href="#insertafter"><?=_("Add Policy After")?></a></li>
		<li class="enabledisable"><a href="#enabledisable"><?=_("Enable/Disable")?></a></li>
		<li class="viewgroup"><a href="#viewgroup"><?=_("View DS Groups")?></a></li>
		<li class="modify"><a href="#modify"><?=_("Modify")?></a></li>
		<li class="delete"><a href="#delete"><?=_("Delete")?></a></li>
		<li class="duplicate"><a href="#duplicate"><?=_("Duplicate")?></a></li>
	</ul>
	
</body>


</html>
<?php
$db->close();
?>
