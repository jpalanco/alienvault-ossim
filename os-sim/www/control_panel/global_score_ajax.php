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

require 'global_score_functions.php';

Session::logcheck('dashboard-menu', 'ControlPanelMetrics');

$group_id = GET('group_id');
$ac       = GET('ac');
$range    = GET('range');

if (!$range)
{
    $range = 'day';
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

ossim_valid($group_id,  OSS_HEX,                    'illegal:' . _("group_name"));
ossim_valid($ac,        OSS_ALPHA,                  'illegal:' . _("ac"));
ossim_valid($range,     OSS_ALPHA, OSS_NULLABLE,    'illegal:' . _("range"));

if (ossim_error()) 
{
    die(ossim_error());
}

$conf = $GLOBALS['CONF'];

$conf_threshold = $conf->get_conf('threshold');

$db   = new ossim_db();
$conn = $db->connect();

//ajax_set_values();

$host_qualification_cache = get_host_qualification($conn);
$net_qualification_cache  = get_net_qualification($conn);

////////////////////////////////////////////////////////////////
// Network Groups
////////////////////////////////////////////////////////////////

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

// We can't join the control_panel table, because new ossim installations
// holds no data there
$sql = "SELECT
            net_group.name as group_name,
            net_group.threshold_c as group_threshold_c,
            net_group.threshold_a as group_threshold_a,
            HEX(net_group.id) as group_id,
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
            net_group_reference.net_group_id = net_group.id $net_group_where AND net_group.id=UNHEX('$group_id')";
            
if (!$rs = & $conn->Execute($sql)) 
{
    die($conn->ErrorMsg());
}

$groups      = array();
$networks    = array();
$group_max_c = $group_max_a = 0;

while (!$rs->EOF) 
{
    $group = $rs->fields['group_id'];
    $groups[$group]['name'] = $rs->fields['group_name'];
    $groups[$group]['has_perms'] = true;
    
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
    $score = get_score($net, 'net');
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
    
    $net_name        = $rs->fields['net_name'];
    $net_threshold_a = $rs->fields['net_threshold_a'] ? $rs->fields['net_threshold_a'] : $group_threshold_a;
    $net_threshold_c = $rs->fields['net_threshold_c'] ? $rs->fields['net_threshold_c'] : $group_threshold_c;
    $groups[$group]['nets'][$net] = array(
        'name' => $net_name,
        'id'   => $net,
        'threshold_a' => $net_threshold_a,
        'threshold_c' => $net_threshold_c,
        'max_a' => $score['max_a'],
        'max_c' => $score['max_c'],
        'max_a_date' => $score['max_a_date'],
        'max_c_date' => $score['max_c_date'],
        'address' => $rs->fields['net_address'],
        'current_a' => $net_current_a,
        'current_c' => $net_current_c,
        'has_perms' => $has_perms,
    	'group' => $group
    );
    
    $rs->MoveNext();
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
        WHERE
            control_panel.time_range = ? AND
            control_panel.rrd_type = 'host'$host_where";

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
    $id   = $rs->fields['id'];
    $name = $rs->fields['hostname'];
    
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

?>
<table width="100%" class="transparent">
	<tr>
        <th colspan="3"><?php echo _("Network") ?></th>
        <th><?php echo _("Max Date") ?></th>
        <th><?php echo _("Max") ?></th>
        <th><?php echo _("Current") ?></th>
    </tr>
<?php

$line = 1;

foreach ($groups[$group_id]['nets'] as $net_id => $net_data) 
{
    $class = (count($groups) == $line) ? ' class="nobborder" ' : '';
    $net++;
    $num_hosts = isset($net_data['hosts']) ? count($net_data['hosts']) : 0;
?>
    <tr id="net_<?php echo $net?>_<?php echo $ac ?>">
        <td width="4%" class="nobborder">&nbsp;</td>
        <td style="text-align: left" <?php echo $class; ?>>
        <?php
        if ($num_hosts) 
        { 
        ?>
            <a id="<?php echo $ac ?>_<?php echo ++$a ?>_<?php echo $ac ?>" href="javascript: toggle('host', <?php echo $host + 1 ?>, <?php echo $num_hosts ?>, '<?php echo $ac ?>_<?php echo $a ?>', '<?php echo $ac ?>');"><img src="../pixmaps/plus-small.png" align="absmiddle" border="0"></a>&nbsp;
        <?php
        } 
        ?>
            <?php echo $net_data["name"] ?>
        </td>
        <?php
        html_set_values($net_id, 'net', $net_data["max_$ac"], $net_data["max_{$ac}_date"], $net_data["current_$ac"], $net_data["threshold_$ac"], $ac);
        ?>
        <td <?php echo $class; ?> style='text-align: center; white-space: nowrap'><?php echo html_rrd() ?> <?php echo html_incident() ?></td>
        <td <?php echo $class; ?> style='text-align: center; white-space: nowrap'><?php echo html_date() ?></td>
        <?php echo html_max($class) ?>
        <?php echo html_current($class) ?>
    </tr>
    
    <?php
    if (isset($net_data['hosts'])) 
    {
        foreach ($net_data['hosts'] as $host_ip => $host_data) 
        {
            $host++;
        ?>
            <tr id="host_<?php echo $host ?>_<?php echo $ac?>" style="display: none">
                <td width="6%" style="border: 0px;">&nbsp;</td>
                <td style="text-align: left;">&nbsp;&nbsp;
                    <?php echo html_host_report($host_data['name']) ?>
                </td>
                <?php
                    html_set_values($host_ip, 'host', $host_data["max_$ac"], $host_data["max_{$ac}_date"], $host_data["current_$ac"], $host_data["threshold_$ac"], $ac);?>
                <td nowrap><?php echo html_rrd() ?> <?php echo html_incident() ?></td>
                <td nowrap><?php echo html_date() ?></td>
                <?php echo html_max() ?>
                <?php echo html_current() ?>
            </tr>   
        <?php
        } 

    } 

    $line++;
}
?>
</table>
