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

Session::logcheck('dashboard-menu', 'ControlPanelMetrics');

function bgcolor($value, $max) 
{
    if ($value / 5 > $max) 
    {
        return 'red';
    }
    elseif ($value / 3 > $max) 
    {
        return 'orange';
    }
    elseif ($value / 1 > $max) 
    {
        return 'green';
    }
    else 
    {
        return 'white';
    }
}

function fontcolor($value, $max) 
{    
    if ($value / 5 > $max) 
    {
        return 'white';
    }
    elseif ($value / 3 > $max) 
    {
        return 'black';
    }
    elseif ($value / 1 > $max) 
    {
        return 'white';
    }
    else 
    {
        return 'black';
    }
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title><?php echo _("OSSIM Framework");?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache">
    <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
</head>
<body>

<?php

$host_id = GET('host_id');

ossim_valid($host_id, OSS_HEX, 'illegal:' . _('Host ID'));

if (ossim_error()) 
{
    die(ossim_error());
}

$conf       = $GLOBALS['CONF'];
$graph_link = $conf->get_conf('graph_link');

$image1 = "$graph_link?id=$host_id&what=compromise&start=N-24h&end=N&type=host&zoom=1";
$image2 = "$graph_link?id=$host_id&what=compromise&start=N-7D&end=N&type=host&zoom=1";
$image3 = "$graph_link?id=$host_id&what=compromise&start=N-1M&end=N&type=host&zoom=1";
$image4 = "$graph_link?id=$host_id&what=compromise&start=N-1Y&end=N&type=host&zoom=1";

/* Connect to DB */
$db   = new ossim_db();
$conn = $db->connect();


/* Get thresholds */
$host = Asset_host::get_object($conn, $host_id);

if (is_object($host) && !empty($host)) 
{
    $threshold_c = $host->get_threshold_c();
    $threshold_a = $host->get_threshold_a();
} 
else 
{
    $threshold_c = $conf->get_conf('threshold');
    $threshold_a = $conf->get_conf('threshold');
}

/* Max C */
$list = Control_panel_host::get_list($conn, "WHERE id = '$host_id' ORDER BY time_range", 3);

if (isset($list[0])) 
{
    $max_c['day']      = $list[0]->get_max_c();
    $max_c_date['day'] = $list[0]->get_max_c_date();
}
if (isset($list[1])) 
{
    $max_c['month']      = $list[1]->get_max_c();
    $max_c_date['month'] = $list[1]->get_max_c_date();
}
if (isset($list[2])) 
{
    $max_c['year']      = $list[2]->get_max_c();
    $max_c_date['year'] = $list[2]->get_max_c_date();
}

/* max A */
$list = Control_panel_host::get_list($conn, "WHERE id = '$host_id' ORDER BY time_range", 3);

if (isset($list[0])) 
{
    $max_a['day']      = $list[0]->get_max_a();
    $max_a_date['day'] = $list[0]->get_max_a_date();
}
if (isset($list[1])) 
{
    $max_a['month']      = $list[1]->get_max_a();
    $max_a_date['month'] = $list[1]->get_max_a_date();
}
if (isset($list[2])) 
{
    $max_a['year']      = $list[2]->get_max_a();
    $max_a_date['year'] = $list[2]->get_max_a_date();
}

/* Current C */
$current_c = Asset_host_qualification::get_ip_compromise($conn, $host_id);

/* Current A */
$current_a = Asset_host_qualification::get_ip_attack($conn, $host_id);
?>

    <table align="center">
        <tr>
            <th><?php echo _('Current C Level');?> &nbsp;</th>
            <td bgcolor="<?php echo bgcolor($current_c, $threshold_c)?>">
                <font color="<?php echo fontcolor($current_c, $threshold_c)?>"><b><?php echo $current_c?></b></font>
            </td>
        </tr>
        
        <tr>
            <th> <?php echo _('Current A Level'); ?> &nbsp;</th>
            <td bgcolor="<?php echo bgcolor($current_a, $threshold_a) ?>">
                <font color="<?php echo fontcolor($current_a, $threshold_a)?>"><b><?php echo $current_a ?></b></font>
            </td>
        </tr>
    </table>
    
    <br/>
    
    <table align="center">
        <?php
        if (isset($max_c['day'])) 
        {
            ?>
            <tr>
                <th><?php echo _('Max C Level (last day)');?></th>
                <td bgcolor="<?php echo bgcolor($max_c['day'], $threshold_c)?>">
                    <font color="<?php echo fontcolor($max_c['day'], $threshold_c)?>"><b><?php echo $max_c['day']?></b></font>
                </td>
                <td><?php echo $max_c_date['day']?></td>
            </tr>
            <?php
        }
        
        if (isset($max_a['day'])) 
        {
            ?>    
            <tr>
                <th><?php echo _('Max A Level (last day)');?></th>
                <td bgcolor="<?php echo bgcolor($max_a['day'], $threshold_a)?>">
                    <font color="<?php echo fontcolor($max_a['day'], $threshold_a) ?>"><b><?php echo $max_a['day']?></b></font>
                </td>
                <td><?php echo $max_a_date['day'] ?></td>
            </tr>
            <tr><td colspan="2"></td></tr>
            <?php
        }
        
        if (isset($max_c['month'])) 
        {
            ?>
            <tr>
                <th><?php echo _('Max C Level (last month)'); ?> </th>
                <td bgcolor="<?php echo bgcolor($max_c['month'], $threshold_c) ?>">
                    <font color="<?php echo fontcolor($max_c['month'], $threshold_c) ?>"><b><?php echo $max_c['month']?></b></font>
                </td>
                <td><?php echo $max_c_date['month'] ?></td>
            </tr>
            <?php
        }
        
        if (isset($max_a['month'])) 
        {
            ?>
            <tr>
                <th> <?php echo _('Max A Level (last month)');?></th>
                <td bgcolor="<?php echo bgcolor($max_a['month'], $threshold_a)?>">
                    <font color="<?php echo fontcolor($max_a['month'], $threshold_a)?>"><b><?php echo $max_a['month']?></b></font>
                </td>
                <td><?php echo $max_a_date['month']?></td>
            </tr>
            <tr><td colspan="2"></td></tr>
            <?php
        }
        
        if (isset($max_c['year'])) 
        {
            ?>
            <tr>
                <th><?php echo _('Max C Level (last year)');?></th>
                <td bgcolor="<?php echo bgcolor($max_c['year'], $threshold_c)?>">
                    <font color="<?php echo fontcolor($max_c['year'], $threshold_c)?>"><b><?php echo $max_c['year'] ?></b></font>
                </td>
                <td><?php echo $max_c_date['year']?></td>
            </tr>
            <?php
        }
        
        if (isset($max_a['year'])) 
        {
            ?>
            <tr>
                <th><?php echo _('Max A Level (last year)');?></th>
                <td bgcolor="<?php echo bgcolor($max_a['year'], $threshold_a)?>">
                    <font color="<?php echo fontcolor($max_a['year'], $threshold_a)?>"><b><?php echo $max_a['year'] ?></b></font>
                </td>
                <td><?php echo $max_a_date['year'] ?></td>
            </tr>
            <?php
        }
        ?>
    </table>

    <p align="center">
        <b><?php echo _('Last day');?></b><br/>
        <img src="<?php echo $image1 ?>"/><br/><br/>
        
        <b> <?php echo _('Last week');?></b><br/>
        <img src="<?php echo $image2 ?>"/><br/><br/>
        
        <b> <?php echo _('Last month');?></b><br/>
        <img src="<?php echo $image3 ?>"/><br/><br/>
        
        <b> <?php echo _('Last year');?></b><br/>
        <img src="<?php echo $image4 ?>"/><br/><br/>
    </p>
</body>
</html>

<?php
$db->close();