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


/**
* Function list:
* - get_score()
* - get_current_metric()
* - host_get_network_data()
* - check_sensor_perms()
* - check_net_perms()
* - order_by_risk()
* - html_service_level()
* - html_set_values()
* - _html_metric()
* - _html_rrd_link()
* - html_max()
* - html_current()
* - html_rrd()
* - html_incident()
* - html_host_report()
* - html_date()
*/

require_once 'av_init.php';


require 'global_score_functions.php';


Session::logcheck('dashboard-menu', 'ControlPanelMetrics');

$toggle_group = true;

$db   = new ossim_db();
$conn = $db->connect();

if (Session::menu_perms('dashboard-menu', 'ControlPanelEvents')) 
{
    $event_perms = true;
} 
else 
{
    $event_perms = false;
}

$event_perms = true; // ControlPanelEvents temporarily disabled


////////////////////////////////////////////////////////////////
// Param validation
////////////////////////////////////////////////////////////////
$valid_range = array(
    'day',
    'week',
    'month',
    'year'
);

$range = GET('range');
$from  = (GET('from') != "") ? GET('from') : 0;
$max   = 100;

ossim_valid($range, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("range"));
ossim_valid($from, OSS_DIGIT, 'illegal:' . _("from"));

if (ossim_error()) 
{
    die(ossim_error());
}


if (!$range) 
{
    $range = 'day';
}
elseif (!in_array($range, $valid_range)) 
{
    die(ossim_error('Invalid range'));
}

if ($range == 'day') 
{
    $rrd_start = "N-1D";
} 
elseif ($range == 'week') 
{
    $rrd_start = "N-7D";
} 
elseif ($range == 'month') 
{
    $rrd_start = "N-1M";
}
elseif ($range == 'year') 
{
    $rrd_start = "N-1Y";
}

$user = Session::get_session_user();
$conf = $GLOBALS['CONF'];

$conf_threshold = $conf->get_conf('threshold');

////////////////////////////////////////////////////////////////
// Script private functions
////////////////////////////////////////////////////////////////
/*
* @param $name, string with the id of the object (ex: a network name or a host
* ip)
* @param $type, enum ('day', 'month', ...)
*/


// Cache some queries
$host_qualification_cache = get_host_qualification($conn);
$net_qualification_cache  = get_net_qualification($conn);


////////////////////////////////////////////////////////////////
// Network Groups
////////////////////////////////////////////////////////////////

// If allowed_nets === null, then permit all
$net_group_where = "";

// CTX's filter
$ctxs = Session::get_ctx_where();
if ($ctxs != "") 
{
    $net_group_where = " AND net_group.ctx in ($ctxs)";
}

// Asset filter
$nets = Session::get_net_where();

if ($nets != "") 
{
    $net_group_where .= " AND net.id in ($nets)";
}

$net_limit = " LIMIT $from,$max";
// We can't join the control_panel table, because new ossim installations
// holds no data there
$sql = "SELECT
            net_group.name as group_name,
            net_group.threshold_c as group_threshold_c,
            net_group.threshold_a as group_threshold_a,
            net.name as net_name,
            HEX(net.id) as net_id,
            net.threshold_c as net_threshold_c,
            net.threshold_a as net_threshold_a,
            net.ips as net_address,
            HEX(net_group.id) as group_id
        FROM
            net_group,
            net,
            net_group_reference
        WHERE
            net_group_reference.net_id = net.id AND
            net_group_reference.net_group_id = net_group.id $net_group_where";


if (!$rs = & $conn->Execute($sql)) 
{
    die($conn->ErrorMsg());
}

$groups      = array();
$group_max_c = $group_max_a = 0;


while (!$rs->EOF) 
{
    $group = $rs->fields['group_id'];
    $groups[$group]['name'] = $rs->fields['group_name'];

    $groups[$group]['has_perms'] = true;
    $groups[$group]['ID'] = $rs->fields['group_id'];
    // If there is no threshold specified for a group, pick the configured default threshold
    $group_threshold_a = $rs->fields['group_threshold_a'] ? $rs->fields['group_threshold_a'] : $conf_threshold;
    $group_threshold_c = $rs->fields['group_threshold_c'] ? $rs->fields['group_threshold_c'] : $conf_threshold;
    $groups[$group]['threshold_a'] = $group_threshold_a;
    $groups[$group]['threshold_c'] = $group_threshold_c;
    $net = $rs->fields['net_id'];
    
    // current metrics
    list($net_current_a, $net_current_c) = get_current_metric($host_qualification_cache,$net_qualification_cache,$net, 'net');
    
    @$groups[$group]['current_a']+= $net_current_a;
    @$groups[$group]['current_c']+= $net_current_c;
    // scores
    $score = get_score($net, 'net'); // $net is net ID
    @$groups[$group]['max_c']+= $score['max_c'];
    @$groups[$group]['max_a']+= $score['max_a'];
    
    $net_max_c_time = strtotime($score['max_c_date']);
    $net_max_a_time = strtotime($score['max_a_date']);
    
    if (!isset($groups[$group]['max_c_date'])) 
    {
        $groups[$group]['max_c_date'] = $score['max_c_date'];
    } 
    else 
    {
        $group_max_c_time = strtotime($groups[$group]['max_c_date']);
        
        if ($net_max_c_time > $group_max_c_time) 
        {
            $groups[$group]['max_c_date'] = $score['max_c_date'];
        }
        
    }
    
    
    if (!isset($groups[$group]['max_a_date'])) 
    {
        $groups[$group]['max_a_date'] = $score['max_a_date'];
    } 
    else 
    {
        $group_max_a_time = strtotime($groups[$group]['max_a_date']);
        
        if ($net_max_c_time > $group_max_c_time) 
        {
            $groups[$group]['max_a_date'] = $score['max_a_date'];
        }
        
    }
    // If there is no threshold specified for a network, pick the group threshold
    // Changed: get networks by AJAX
    
    $net_threshold_a = $rs->fields['net_threshold_a'] ? $rs->fields['net_threshold_a'] : $group_threshold_a;
    $net_threshold_c = $rs->fields['net_threshold_c'] ? $rs->fields['net_threshold_c'] : $group_threshold_c;
    $groups[$group]['nets'][$net] = array(
        'name' => $net,
        'threshold_a' => $net_threshold_a,
        'threshold_c' => $net_threshold_c,
        'max_a' => $score['max_a'],
        'max_c' => $score['max_c'],
        'max_a_date' => $score['max_a_date'],
        'max_c_date' => $score['max_c_date'],
        'address' => $rs->fields['net_address'],
        'current_a' => $net_current_a,
        'current_c' => $net_current_c,
        'has_perms' => $has_perms
    );
    
    $rs->MoveNext();
}

////////////////////////////////////////////////////////////////
// Networks outside groups
////////////////////////////////////////////////////////////////
$sql = "SELECT hex(net_id) as net_id FROM net_group_reference";

if (!$rs = & $conn->Execute($sql)) 
{
    die($conn->ErrorMsg());
}


$nets_grouped = array();

while (!$rs->EOF) 
{
	$nets_grouped[$rs->fields['net_id']]++;
	
	$rs->MoveNext();
}

$net_where = "";

if ($ctxs != "") 
{
    $net_where = " AND net.ctx in ($ctxs)";
}


// Asset filter
$nets = Session::get_net_where();

if ($nets != "") 
{
    $net_where .= " AND net.id in ($nets)";
}

$sql = "SELECT
            net.name as net_name,
            HEX(net.id) as net_id,
            net.threshold_c as net_threshold_c,
            net.threshold_a as net_threshold_a,
            net.ips as net_address
        FROM
            net
        WHERE
            1=1 $net_where $net_limit";

 
if (!$rs = & $conn->Execute($sql)) 
{
    die($conn->ErrorMsg());
}

$networks = array();
$count    = 1;

while (!$rs->EOF) 
{
	$has_perms = true;
	$net       = $rs->fields['net_id'];
	
	if ($nets_grouped[$net] != "" || $count > $max) 
	{ 
	   $rs->MoveNext(); 
	   continue; 
    }
    
    
	$score = get_score($net, 'net');
    
    // If there is no threshold specified for the network, pick the global configured threshold
    $net_threshold_a = $rs->fields['net_threshold_a'] ? $rs->fields['net_threshold_a'] : $conf_threshold;
    $net_threshold_c = $rs->fields['net_threshold_c'] ? $rs->fields['net_threshold_c'] : $conf_threshold;
    
    list($_attack, $_compromise) = get_current_metric($host_qualification_cache,$net_qualification_cache,$net, 'net');
    
    $networks[$net] = array(
        'name'        => $rs->fields['net_name'],
        'threshold_a' => $net_threshold_a,
        'threshold_c' => $net_threshold_c,
        'max_a'       => $score['max_a'],
        'max_c'       => $score['max_c'],
        'max_a_date'  => $score['max_a_date'],
        'max_c_date'  => $score['max_c_date'],
        'address'     => $rs->fields['net_address'],
        'current_a'   => $_attack,
        'current_c'   => $_compromise,
        'has_perms'   => $has_perms
    );
    
    $rs->MoveNext();
    
    $count++;
}
   
////////////////////////////////////////////////////////////////
// Hosts
////////////////////////////////////////////////////////////////

$host_where = "";

if ($ctxs != "") 
{
    $host_where = " AND host.ctx in ($ctxs)";
}

// Asset filter
$hosts = Session::get_host_where();

if ($hosts != "") 
{
    $host_where .= " AND host.id in ($hosts)";
}
        
$sql = "SELECT
            control_panel.id,
            control_panel.max_c,
            control_panel.max_a,
            control_panel.max_c_date,
            control_panel.max_a_date,
            host.threshold_a,
            host.threshold_c,
            host.hostname
        FROM
            control_panel
        LEFT JOIN host ON UNHEX(control_panel.id) = host.id
        WHERE control_panel.rrd_type = 'host' AND control_panel.time_range=? $host_where";

$params = array(
    $range
);

if (!$rs = & $conn->Execute($sql, $params)) 
{
    die($conn->ErrorMsg());
}

$hosts    = array();
$global_a = $global_c = 0;

while (!$rs->EOF) 
{
    $id      = $rs->fields['id'];
    $name    = $rs->fields['hostname'];
    
    $threshold_a = $rs->fields['threshold_a'] ? $rs->fields['threshold_a'] : $net_threshold_a;
    $threshold_c = $rs->fields['threshold_c'] ? $rs->fields['threshold_c'] : $net_threshold_c;

    // get host & global metrics
    list($current_a, $current_c) = get_current_metric($host_qualification_cache,$net_qualification_cache,$net, 'host');
    
    $global_a+= $current_a;
    $global_c+= $current_c;

    $data = array(
            'name' => $name,
            'threshold_a' => $threshold_a,
            'threshold_c' => $threshold_c,
            'max_c' => $rs->fields['max_c'],
            'max_a' => $rs->fields['max_a'],
            'max_c_date' => $rs->fields['max_c_date'],
            'max_a_date' => $rs->fields['max_a_date'],
            'current_a' => $current_a,
            'current_c' => $current_c,
            'network' => $net_belong,
            'group' => $group_belong
        );
        $hosts[$id] = $data;
    
    $rs->MoveNext();
}


////////////////////////////////////////////////////////////////
// Global score
////////////////////////////////////////////////////////////////

$global = get_score("global_$user", 'global');

list($_current_a, $_current_c) = get_current_metric($host_qualification_cache,$net_qualification_cache,'global', 'global');
 
$global['current_a']   = $_current_a;
$global['current_c']   = $_current_c;
$global['threshold_a'] = $conf_threshold;
$global['threshold_c'] = $conf_threshold;



////////////////////////////////////////////////////////////////
// Permissions & Ordering
////////////////////////////////////////////////////////////////
foreach ($networks as $net => $net_data)
{
    $net_perms = $net_data['has_perms'];
    
    if (!$net_perms) 
    {
        unset($networks[$net]);
    }
}
// Groups

$order_by_risk_type = 'compromise';
uasort($groups, 'order_by_risk');

foreach($groups as $group => $group_data) 
{
    $group_perms = $group_data['has_perms'];
    
    //uasort($groups[$group]['nets'], 'order_by_risk');
    foreach($group_data['nets'] as $net => $net_data) 
    {
        $net_perms = $net_data['has_perms'];
        /*
        if (isset($groups[$group]['nets'][$net]['hosts'])) {
            uasort($groups[$group]['nets'][$net]['hosts'], 'order_by_risk');
        }
        */
        // the user doesn't have perms over the group but only over
        // some networks of it. List that networks as networks outside
        // groups.
        if (!$group_perms && $net_perms) 
        {
			$networks[$net] = $net_data;
        }
        
    }
    
    if (!$group_perms) 
    {
        unset($groups[$group]);
    }
}

// Networks outside groups
uasort($networks, 'order_by_risk');
// Hosts in networks
uasort($hosts, 'order_by_risk');

////////////////////////////////////////////////////////////////
// HTML Code
////////////////////////////////////////////////////////////////

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("Control Panel"); ?> </title>
	<!-- <meta http-equiv="refresh" content="150"> -->
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<?php require ("../host_report_menu.php") ?>
	<script type="text/javascript" src="../js/greybox.js"></script>
	<script language="javascript">
		var reload = true;
		
		function refresh() {
			if (reload == true) document.location.reload();
		}
	
		function postload() 
		{
			GB_TYPE = 'w';
			
			$("a.greybox").click(function()
			{
				reload = false;
				var t  = this.title || $(this).text() || this.href;
				
				GB_show(t,this.href,'80%','75%');
				
				return false;
			});

			setTimeout('refresh()',90000);
		}
    
	function GB_onclose() {
			document.location.reload();
		}
		
		function toggle(type, start_id, end_id, link_id, ac)
		{
			if ($("#"+link_id+'_'+ac).html() == '<img src="../pixmaps/plus-small.png" align="absmiddle" border="0">') {
				for (i=0; i < end_id; i++) {
					id = start_id + i;
					tr_id = type + '_' + id;
					$("#"+tr_id+'_'+ac).show();
				}
				$("#"+link_id+'_'+ac).html('<img src="../pixmaps/minus-small.png" align="absmiddle" border="0">');
			} else {
				for (i=0; i < end_id; i++) {
					id = start_id + i;
					tr_id = type + '_' + id;
					$("#"+tr_id+'_'+ac).hide();
				}
				$("#"+link_id+'_'+ac).html('<img src="../pixmaps/plus-small.png" align="absmiddle" border="0">');
			}
		}
		
		function toggle_group(group_id,link_id,ac) {
			if ($("#g"+link_id+'_'+ac).html() == '<img src="../pixmaps/plus-small.png" align="absmiddle" border="0">') {
				$("#group_"+link_id+'_'+ac).html("<img src='../pixmaps/loading.gif' width='20'>");
				$.ajax({
					type: "GET",
					url: "global_score_ajax.php?group_id="+group_id+"&ac="+ac+"&range=<?php echo $range ?>",
					data: "",
					success: function(msg){
						$("#g"+link_id+'_'+ac).html('<img src="../pixmaps/minus-small.png" align="absmiddle" border="0">');
						$("#group_"+link_id+'_'+ac).html(msg);
					}
				});
			} else {
				$("#group_"+link_id+'_'+ac).html("");
				$("#g"+link_id+'_'+ac).html('<img src="../pixmaps/plus-small.png" align="absmiddle" border="0">');
			}
		}
  </script>
  
  <style type="text/css">

  body.score {
      margin-right: 5px;
      margin-left: 5px;
  }
  </style>
  
  
  
</head>

<body class="score">
	
	<table width="100%" align="center" class='transparent'>
		<tr>
			<td class="noborder" colspan="2">
				<!--
				Page Header (links, riskmeter, rrd)
				-->
				
				<table width="100%" align="center" class='transparent'>
					<tr>
						<td colspan="2" class="noborder" style="padding-bottom:5px">
						<?php
						foreach(array('day' => _("Last day"), 'week' => _("Last week"), 'month' => _("Last month"), 'year' => _("Last year") ) as $r => $text) 
						{
							if ($r == $range) echo '<b>';
							?>
								<a href="<?php echo $_SERVER['SCRIPT_NAME'] ?>?range=<?php echo $r ?>"><?php echo $text ?></a> 
							<?php
							if ($r == $range) echo '</b>';
							if ($r!="year") echo " | ";
						} 
						?>
						</td>
					</tr>
					
					<tr>
						<td class="noborder">
							<?php
							define("GRAPH_ZOOM", "0.85");
							?>

							<img border=0 src="../report/graphs/draw_rrd.php?id=global_<?php echo $user?>&what=compromise&start=<?php echo $rrd_start?>&end=N&type=global&zoom=<?php echo GRAPH_ZOOM?>"/>
						</td>
						
						<td class="noborder">
							<table>
								<tr>
								  <?php if (Session::menu_perms("dashboard-menu", "MonitorsRiskmeter")) { ?>
								  <th><?php echo _("Riskmeter") ?></th>
								  <? } ?>
								  <th><?php echo _("Service Level") ?>&nbsp;</th>
								</tr>
								
								<tr>
									<?php if (Session::menu_perms("dashboard-menu", "MonitorsRiskmeter")) { ?>
									<td class="noborder">
										<a class="" href="../riskmeter/index.php" title='<?=_("Riskmeter")?>'><img border="0" src="../pixmaps/riskmeter.png"/></a>
									</td>
									<? } ?>
									<?php echo html_service_level() ?>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	
		<tr>
		<?php
		foreach(array('compromise', 'attack') as $metric_type) 
		{
			$a = 1;
			$net = $host = 0;
			if ($metric_type == 'compromise') {
				$title = _("C O M P R O M I S E");
				$ac = 'c';
			} else {
				$title = _("A T T A C K");
				$ac = 'a';
			}
			?>
			<td width="50%" class="noborder" valign="top">
				<table width="100%" align="center">
					<tr><td colspan="6"><center><strong><?php echo $title?></strong></center></td></tr>
					<tr>
						<th colspan="6" class="noborder"><?php echo _("Global") ?></th>
					</tr>
					<!--
					Global
					-->
					<tr>
						<th colspan="3"><?php echo _("Global") ?></th>
						<th><?php echo _("Max Date") ?></th>
						<th><?php echo _("Max") ?></th>
						<th><?php echo _("Current") ?></th>
					</tr>
					
					<tr>
						<td colspan="2"><b><?php echo _("GLOBAL SCORE") ?><b></td>
						<?php
						html_set_values("global_$user", 'global', $global["max_$ac"], $global["max_{$ac}_date"], $global["current_$ac"], $global["threshold_$ac"], $ac);
						html_set_values_session("global_$user", 'global', $global["max_$ac"], $global["max_{$ac}_date"], $global["current_$ac"], $global["threshold_$ac"], $ac);
						?>
						<td><?php echo html_rrd() ?> <?php echo html_incident() ?></td>
						<td nowrap='nowrap'><?php echo html_date() ?></td>
						<?php echo html_max() ?>
						<?php echo html_current() ?>
					</tr>
					
					<tr>
						<td colspan="6" class="noborder">&nbsp;</td>
					</tr>
					<!--
					Network Groups
					-->
				<?php
				if (count($groups)) 
				{ 
				?>
					<tr>
						<th colspan="6" class="noborder"><?php echo _("Network Groups") ?></th>
					</tr>
						
					<tr>
						<th colspan="3"><?php echo _("Group") ?></th>
						<th><?php echo _("Max Date") ?></th>
						<th><?php echo _("Max") ?></th>
						<th><?php echo _("Current") ?></th>
					</tr>
					
					<?php
                    $line=1;
					foreach ($groups as $group_id => $group_data) 
					{
                        $class      = (count($groups)==$line) ? ' class="nobborder" ' : '';
                        $group_name = $groups[$group_id]["name"];
						$num_nets   = count($group_data['nets']);
						?>
						<tr>
							<td class="nobborder">
								<?php if ((round($group_data["max_$ac"] / $group_data["threshold_$ac"] * 100) > 100) || $toggle_group) { ?>
								<a id="g<?php echo $ac ?>_<?php echo ++$a ?>_<?php echo $ac ?>" href="javascript: toggle_group('<?php echo $group_id ?>','<?php echo $ac ?>_<?php echo $a ?>','<?php echo $ac ?>');"><img src="../pixmaps/plus-small.png" align="absmiddle" border="0"></img></a>
								<?php } else { ?>
								<img src="../pixmaps/plus-small-gray.png" align="absmiddle" border="0"></img>
								<?php } ?>
							</td>
							
							<td <?php echo $class; ?>><b><?php echo $group_name ?></b></td>
							<?php html_set_values('group_' . $group_name, 'net', $group_data["max_$ac"], $group_data["max_{$ac}_date"], $group_data["current_$ac"], $group_data["threshold_$ac"], $ac); ?>
							
							<td <?php echo $class;?> style='text-align: center; white-space: nowrap'>
								<a href="<?php echo Util::graph_image_link($group_data["ID"], 'net', $metric_type, $rrd_start, "N", 1, $range) ?>"><img src="../pixmaps/graph.gif" border="0"/></a>&nbsp;
								<?php 
                                //New ticket
                                if ( Session::menu_perms("analysis-menu", "IncidentsOpen") )
                                {
                                    ?>
                                    <a title='<?php echo _("New metric ticket")?>' href="../incidents/newincident.php?ref=Metric&title=<?php echo urlencode(_("Metric Threshold: ".strtoupper($ac)." level exceeded")." (Net: group_$group_name)") ?>&priority=<?php echo $group_data["max_$ac"]/$group_data["threshold_$ac"] ?>&target=<?php echo urlencode("Net: group_$group_name") ?>&metric_type=<?php echo $metric_type ?>&metric_value=<?php echo $metric_type ?>&event_start=<?php echo $group_data["max_{$ac}_date"] ?>&event_end=<?php echo $group_data["max_{$ac}_date"] ?>">
                                        <img src="../pixmaps/script--pencil.png" width="12" alt="<?php echo _("New metric ticket")?>" border="0"/>
                                    </a>
                                    <?php
                                }
                                else
                                {
                                    ?>
                                    <span class='disabled'><img src="../pixmaps/script--pencil-gray.png" width="12" alt="<?php echo _("New metric ticket")?>" title="<?php echo _("New metric ticket")?>" border="0"/></span>
                                    <?php
                                }
                                ?>  
                            </td>
							
							<td style='text-align: center; white-space: nowrap' <?php echo $class; ?>><?php echo ($group_data["max_{$ac}_date"] == 0 || strtotime($group_data["max_{$ac}_date"]) == 0) ? _('n/a') : $group_data["max_{$ac}_date"] ?></td>
							<?php
							// Group MAX
							$link_aux = ($group_data["max_{$ac}_date"] == 0) ? "#" : Util::get_acid_date_link($group_data["max_{$ac}_date"]);
							echo _html_metric($group_data["max_$ac"], $group_data["threshold_$ac"], $link_aux, $class);
							
							// Group CURRENT
							echo _html_metric($group_data["current_$ac"], $group_data["threshold_$ac"], $link, $class);
							?>
						</tr>
						<tr>
							<td colspan="6" class="nobborder"><div id="group_<?php echo $ac ?>_<?php echo $a ?>_<?php echo $ac ?>"></div></td>
						</tr>
					<?php
                        $line++;
					} 
				} 	
				?>
					
				<!--
				Network outside groups
				-->

				<?php
				if (count($networks)) 
				{ 
					?>
					<tr>
						<th colspan="6" class="noborder"><?php echo _("Networks outside groups") ?></th>
					</tr>
					<tr>
						<th colspan="3"><?php echo _("Network") ?></th>
						<th><?php echo _("Max Date") ?></th>
						<th><?php echo _("Max") ?></th>
						<th><?php echo _("Current") ?></th>
					</tr>
				
					<?php
					$i = 0;
					foreach ($networks as $net_id => $net_data) 
					{
                        $net_name  = $networks[$net_id]["name"];
						$num_hosts = isset($net_data['hosts']) ? count($net_data['hosts']) : 0;
						?>
						<tr>
							<td colspan="2" style="text-align: left">
							<?php
							if ($num_hosts) 
							{ 
								?>
								<a id="<?php echo $ac ?>_<?php echo ++$a?>_<?php echo $ac?>" href="javascript: toggle('host', <?php echo $host + 1 ?>, <?php echo $num_hosts ?>, '<?php echo $ac ?>_<?php echo $a ?>', '<?php echo $ac ?>');"><img src="../pixmaps/plus-small.png" align="absmiddle" border="0"></a>&nbsp;
								<?php
							}
							
							?>
							<b><?php echo $net_name?></b>
						</td>
						
						<?php html_set_values($net_id, 'net', $net_data["max_$ac"], $net_data["max_{$ac}_date"], $net_data["current_$ac"], $net_data["threshold_$ac"], $ac); ?>
						
						<td nowrap='nowrap'><?php echo html_rrd() ?> <?php echo html_incident() ?></td>
						<td nowrap='nowrap'><?php echo html_date() ?></td>
						<?php echo html_max() ?>
						<?php echo html_current() ?>
						</tr>
						
						<?php
						if ($num_hosts) 
						{
							uasort($net_data['hosts'], 'order_by_risk');
							foreach($net_data['hosts'] as $host_ip => $host_data) 
							{
								$host++;
								?>
								<tr id="host_<?php echo $host?>_<?php echo $ac?>" style="display: none">
									<td width="3%" style="border: 0px;">&nbsp;</td>
									<td style="text-align: left">&nbsp;&nbsp;
										<?php echo html_host_report($host_data['name']) ?>
									</td>
									<?php html_set_values($host_ip, 'host', $host_data["max_$ac"], $host_data["max_{$ac}_date"], $host_data["current_$ac"], $host_data["threshold_$ac"], $ac);?>
									<td nowrap><?php echo html_rrd() ?> <?php echo html_incident() ?></td>
									<td><?php echo html_date() ?></td>
									<?php echo html_max() ?>
									<?php echo html_current() ?>
								</tr>   
								<?php
							} 
						} 
					} 
				} 
			?>
			
			<!--
			Hosts
			-->
			
			<?php
			if (count($hosts)) 
			{ 
			?>
				<tr>
					<th colspan="6" class="noborder"><?php echo _("Hosts") ?></th>
				</tr>
				
				<tr>
					<th colspan="3"><?php echo _("Host Address") ?></th>
					<th><?php echo _("Max Date") ?></th>
					<th><?php echo _("Max") ?></th>
					<th><?php echo _("Current") ?></th>
				</tr>
				<?php
				$i = 0;
				foreach ($hosts as $id => $host_data) 
				{
					?>
					<tr>
						<td nowrap='nowrap' colspan="2" style="text-align: left">
						  <?php echo html_host_report($host_data['name']) ?>
						</td>
						
						<?php html_set_values($id, 'host', $host_data["max_$ac"], $host_data["max_{$ac}_date"], $host_data["current_$ac"], $host_data["threshold_$ac"], $ac);?>
						
						<td><?php echo html_rrd() ?> <?php echo html_incident() ?></td>
						<td nowrap='nowrap'><?php echo html_date() ?></td>
						<?php echo html_max() ?>
						<?php echo html_current() ?>
					</tr>
					<?php
				} 
			} 
		
			?>
			</table>
		</td>
			<?php
		} 
		?>

		</td>
	</tr>
</table>

<div style='padding: 10px 0px 5px 5px; font-weight: bold;'><?php echo _("Legend") ?>:</div>
<table width="30%" align="left" style='margin-left: 10px;'>
	<tr>
		<?php echo _html_metric(0, 100, '#') ?>
		<td><?php echo _("No appreciable risk") ?></td>
	</tr>
	<tr>
		<?php echo _html_metric(101, 100, '#') ?>
		<td><?php echo _("Metric over 100% threshold") ?></td>
	</tr>
	<tr>
		<?php echo _html_metric(301, 100, '#') ?>
		<td><?php echo _("Metric over 300% threshold") ?></td>
	</tr>
	<tr>
		<?php echo _html_metric(501, 100, '#') ?>
		<td class='nobborder center'><?php echo _("Metric over 500% threshold") ?></td>
	</tr>
</table>
<br/>

</body></html>
