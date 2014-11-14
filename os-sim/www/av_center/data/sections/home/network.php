<?php

/**
 *
 * License:
 *
 * Copyright (c) 2003-2006 ossim.net
 * Copyright (c) 2007-2014 AlienVault
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


//Config File
require_once dirname(__FILE__).'/../../../config.inc';

session_write_close();

if ($_SERVER['SCRIPT_NAME'] != '/ossim/av_center/data/sections/home/network.php')
{
    exit();
}

$system_id     = POST('system_id');
$force_request = (1 == POST('force_request')) ? TRUE : FALSE;

ossim_valid($system_id, OSS_DIGIT, OSS_LETTER, '-', 'illegal:'._('System ID'));

$error_msg = NULL;

if (ossim_error())
{
    $error_msg = _('System ID not found. Information not available');

    echo 'error###'.$error_msg;
    exit();
}

try
{
    $st = Av_center::get_system_status($system_id, 'network', $force_request);
}
catch (\Exception $e)
{
    echo 'error###'.$e->getMessage();
    exit();
}


/*************************************************************
***********************  Network Data  ***********************
*************************************************************/

$dns_servers = $st['dns_servers'];
$dns         = (is_array($dns_servers) && !empty($dns_servers)) ? 'tick.png' : 'cross.png';

$firewall_active  = ('yes' === $st['firewall_active'])     ? 'tick.png' : 'cross.png';
$internet         = ('yes' === $st['internet_connection']) ? 'tick.png' : 'cross.png';
$vpn_access       = ('yes' === $st['vpn_access'])          ? 'tick.png' : 'cross.png';


$img_firewall = AVC_PIXMAPS_DIR.'/'.$firewall_active;
$img_internet = AVC_PIXMAPS_DIR.'/'.$internet;
$img_vpn      = AVC_PIXMAPS_DIR.'/'.$vpn_access;
$img_dns      = AVC_PIXMAPS_DIR.'/'.$dns;
$img_rx       = AVC_PIXMAPS_DIR.'/'.'arrow-small-down-green.png';
$img_tx       = AVC_PIXMAPS_DIR.'/'.'arrow-small-up-red.png';

?>

<table id='t_network' cellspacing='0' cellpadding='0'>
    <tr>
        <th class='th_network' colspan='10'><?php echo _('General Information')?></th>
    </tr>

    <tr>
        <td class='_label' valign='middle'><label><?php echo _('Firewall')?></label></td>
        <td class='_data'><img id='firewall' src='<?php echo $img_firewall?>' alt='<?php echo $firewall_active?>' align='absmiddle'/></td>
        <td class='_label' valign='middle'><label><?php echo _('VPN Infrastructure')?></label></td>
        <td class='_data'><img id='vpn_access' src='<?php echo $img_vpn?>' alt='<?php echo $vpn_infrastructure?>' align='absmiddle'/></td>
        <td class='_label' valign='middle'><label><?php echo _('Internet Connection')?></label></td>
        <td class='_data'><img id='inet_conn' src='<?php echo $img_internet?>' alt='<?php echo $internet?>' align='absmiddle'/></td>
        <td class='_label' valign='middle'><label><?php echo _('Default Gateway')?></label></td>
        <td class='_data'><div id='gateway'><?php echo $st['gateway']?></div></td>
        <td class='_label' valign='middle'><label><?php echo _('DNS Servers')?></label></td>
        <td class='_data'>
        <?php
        if (empty($dns_servers))
        {
            ?>
            <div id='dns_servers'><img src='<?php echo $img_dns?>' alt='<?php echo $dns?>' align='absmiddle'/></div>
            <?php
        }
        else
        {
            ?>
            <div id='dns_servers'><div><?php echo str_replace(',', '</div><div>', $dns_servers)?></div></div>
            <?php
        }
        ?>
        </td>
    </tr>

    <tr>
        <th class='th_network' colspan='10'><?php echo _('Interface Information')?></th>
    </tr>

    <?php
    $i_role_names = array(
        'disabled'       => _('Not in Use'),
        'admin'          => _('Management'),
        'monitoring'     => _('Network Monitoring'),
        'log_management' => _('Log Collection & Scanning')
    );


    //Reorder interfaces
    $i_lo = array('lo' => $st['interfaces']['lo']);

    unset($st['interfaces']['lo']);
    $st['interfaces'] = array_merge($i_lo, $st['interfaces']);

    foreach($st['interfaces'] as $i_name => $i_data)
    {                
        if ('up' === $i_data['status'])
        {
             $text_color = 'green';
             $if_image   = 'port_animado.gif';
        }
        else
        {
             $text_color = 'red';
             $if_image   = 'no_animado.gif';
        }

        $i_data['rx_bytes'] = Avc_utilities::bytesToSize($i_data['rx_bytes'], 2);
        $i_data['tx_bytes'] = Avc_utilities::bytesToSize($i_data['tx_bytes'], 2);
        $i_data['role']     = ($i_name == 'lo') ? ' - ' : $i_role_names[$i_data['role']];
        ?>

        <tr>
            <td rowspan='2' class='_label td_iface_name'><?php echo $i_name?></td>
            <td rowspan='2' id='<?php echo $i_name.'_status'?>' class='td_iface_status'>
                <div id='container_iface_st'>
                    <img src="<?php echo AVC_PIXMAPS_DIR.'/'.$if_image?>" width="40"/>
                    <div class='iface_status'><span class='<?php echo $text_color?>'><?php echo strtoupper($i_data['status'])?></span></div>
                </div>
            </td>
            <td class='_label'><img style='margin-right: 3px;' src='<?php echo $img_rx?>' alt='&#8595;'/>Rx</td>
            <td class='_data' id='<?php echo $i_name.'_rx_bytes'?>'><?php echo $i_data['rx_bytes']?></td>
            <td class='_label'>IP</td>
            <td class='_data' colspan="2" id='<?php echo $i_name.'_address'?>'><?php echo $i_data['ipv4']['address']?></td>
            <td class='_label'><?php echo _('Role')?></td>
            <td class='_data' colspan="2"><?php echo $i_data['role']?></td>
        </tr>
        <tr>
            <td class='_label nobborder'><img style='margin-right: 3px;' src='<?php echo $img_tx?>' alt='&#8593;'/>Tx</td>
            <td class='_data' id='<?php echo $i_name.'_tx_bytes'?>'><?php echo $i_data['tx_bytes']?></td>
            <td class='_label'><?php echo _('Netmask')?></td>
            <td class='_data' colspan="2" id='<?php echo $i_name.'_netmask'?>'><?php echo $i_data['ipv4']['netmask']?></td>
            <td class='_label nobborder'><?php echo _('Network')?></td>
            <td class='_data nobborder' colspan="2" id='<?php echo $i_name.'_network'?>'><?php echo $i_data['ipv4']['network']?></td>
        </tr>
        <?php
    }
    ?>
</table>