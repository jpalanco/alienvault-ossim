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
require_once dirname(__FILE__) . '/../../../config.inc';

session_write_close();

if ($_SERVER['SCRIPT_NAME'] != '/ossim/av_center/data/sections/home/alienvault_status.php')
{
    exit();
}

$system_id     = POST('system_id');
$force_request = (POST('force_request') == 1) ? TRUE : FALSE;

ossim_valid($system_id, OSS_DIGIT, OSS_LETTER, '-', 'illegal:' . _('System ID'));

$error_msg = NULL;

if (ossim_error())
{
    $error_msg = _('System ID not found. Information not available');
    
    echo 'error###' . $error_msg;
    exit();
}

try
{
    $st = Av_center::get_system_status($system_id, 'alienvault', $force_request);
    $st = $st['profiles'];
}
catch (\Exception $e)
{
    echo 'error###' . $e->getMessage();
    exit();
}

/*************************************************************
******************  Alienvault Status Data *******************
**************************************************************/

$profiles = array();

//Sensor profile
if (is_array($st['sensor']) && !empty($st['sensor'])) 
{
    $plugins_enabled     = $st['sensor']['plugins_enabled'];
    $sniffing_interfaces = (empty($st['sensor']['sniffing_interfaces'])) ? "<img src='".AVC_PIXMAPS_DIR."/cross.png' alt='"._('No')."'/>" : str_replace(',', ', ', $st['sensor']['sniffing_interfaces']);
    $network_monitored   = $st['sensor']['network_monitored'];
    $netflow             = ($st['sensor']['sensor_netflow'] == 'yes') ? 'tick.png' : 'cross.png';
    
    $profiles['Sensor'] = array(
        array('label' => _('Plugins enabled'),     'data' => $plugins_enabled),
        array('label' => _('Sniffing Interfaces'), 'data' => $sniffing_interfaces),
        array('label' => _('Netflow'),             'data' => "<img src='".AVC_PIXMAPS_DIR.'/'.$netflow."' alt='$netflow'/>"),
        array('label' => _('Network monitored'),   'data' => str_replace(',', ', ', $network_monitored))
    );
}


//Database profile
if (is_array($st['database']) && !empty($st['database']))
{
    $profiles['Database'] = array(
        array('label' => 'Alienvault',      'data' => Avc_utilities::bytesToSize($st['database']['alienvault']['size'])),
        array('label' => 'Alienvault SIEM', 'data' => Avc_utilities::bytesToSize($st['database']['alienvault_siem']['size'])),
        array('label' => 'Inventory',       'data' => Avc_utilities::bytesToSize($st['database']['ocsweb']['size']))
    );
}


//Server profile

if (is_array($st['server']) && !empty($st['server']))
{ 
    $entity_total     = $st['server']['directives']['entities']['total'];
    $entity_enabled   = $st['server']['directives']['entities']['enabled'];
    $total_directives = $st['server']['directives']['total'];
    $categories       = $entity_total."<span class='cursive'> (".$entity_enabled."<span class='green'> "._('enabled')."</span>)</span>";
    $ip_reputation    = ('yes' === $st['server']['ip_reputation']) ? 'tick.png' : 'cross.png';
    $img_reputation   = "<img src='".AVC_PIXMAPS_DIR.'/'.$ip_reputation."' alt='$ip_reputation'/>";
    
    $local_system = Util::get_default_uuid(); 
    
    if ($system_id == $local_system)
    {
        // Show with EPS graph
        $profiles['Server'] = array(
            array('label' => _('Total Directives'), 'data' => $total_directives),
            array('label' => _('Categories'),       'data' => $categories),
            array('label' => _('IP Reputation'),    'data' => $img_reputation),
            array('label' => _('EPS'),              'data' => "<a class='grbox' id='lnk_vt' title='"._('EPS Trend')."' href='/ossim/control_panel/eps_trend.php?range=day'>"._('View Trend')."</a>")
        );
    }
    else
    {
        $profiles['Server'] = array(
            array('label' => _('Total Directives'), 'data' => $total_directives),
            array('label' => _('Categories'),       'data' => $categories),
            array('label' => _('IP Reputation'),    'data' => $img_reputation)
        );        
    }    
}

foreach ($profiles as $name => $p_data)
{
    ?>
    <table class='t_av_profile'>
        <thead>
            <tr>
                <th class='th_av' colspan='4'><?php echo _($name)?></th>
            </tr>
        </thead>

        <tbody>
            <?php
            $size = count($p_data);
            $i = 0;

            while ($i < $size)
            {
                ?>
                <tr>
                    <td class='_label'><?php echo $p_data[$i]['label']?></td>
                    <td class='_data'><?php echo $p_data[$i]['data']?></td>
                    <?php

                    $i++;
                    if ($i < $size)
                    {
                        ?>
                        <td class='_label'><?php echo $p_data[$i]['label']?></td>
                        <td class='_data'><?php echo $p_data[$i]['data']?></td>
                        <?php
                    }
                    else
                    {
                        ?>
                        <td class='noborder'></td>
                        <td class='noborder'></td>
                        <?php
                    }
                    ?>
                </tr>
                <?php
                $i++;
            }
            ?>
        </tbody>
    </table>
    <?php
}
?>