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

if ($_SERVER['SCRIPT_NAME'] != '/ossim/av_center/data/sections/home/system_status.php')
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
    $st = Av_center::get_system_status($system_id, 'general', $force_request);
}
catch (\Exception $e)
{
    echo 'error###' . $e->getMessage();
    exit();
}


/**************************************************************
********************  System Status Data  ********************
**************************************************************/

$hostname = $st['hostname'].' ['.$st['admin_ip'].']';

$system_time       = $st['system_time'];
$system_uptime     = $st['uptime'];
$running_processes = $st['process']['total'];
$load_average      = $st['load_average'];
$current_sessions  = $st['sessions']['total'];

//CPU
$cpu_data  = $st['cpu'];
$cpu_load  = number_format($cpu_data['load_average'], 2);
$cpu_proc  = $cpu_data['cpu0']['core'];
$num_cores = count($cpu_data) - 1;
      
//Real memory
$rmt = Avc_utilities::bytesToSize($st['memory']['ram']['total']);
$rmu = Avc_utilities::bytesToSize($st['memory']['ram']['used']);
$rmf = Avc_utilities::bytesToSize($st['memory']['ram']['free']);
$rmp = number_format($st['memory']['ram']['percent_used'], 2);
       
//Virtual memory
$vmt = Avc_utilities::bytesToSize($st['memory']['swap']['total']);
$vmu = Avc_utilities::bytesToSize($st['memory']['swap']['used']);
$vmf = Avc_utilities::bytesToSize($st['memory']['swap']['free']);
$vmp = number_format($st['memory']['swap']['percent_used'], 2);

//Disk Usage
$mounted_disks = $st['disk'];

?>

<table id='t_status' cellspacing='0' cellpadding='0'>

    <thead>
        <tr>
            <th class='th_status' colspan='2'><?php echo _('Main Information')?></th>
            <th class='th_status' colspan='3'><?php echo _('System Information')?></th>
        </tr>
    </thead>


    <tbody>
        <tr>
            <td class='_label'><?php echo _('Hostname')?></td>
            <td class='_data'><?php echo $hostname?></td>
            <td class='t_status_header' colspan='2'>
                <span><?php echo _('RAM used')?></span>
                <span style='font-weight: normal; font-size: 9px'>
                    [<span class='free'><?php echo _('Free').': '?><?php echo $rmf?></span>,
                    <span class='used'><?php echo _('Used').': '?><?php echo $rmu?></span>,
                    <span class='total'><?php echo _('Total').': '?><?php echo $rmt?></span>]
                </span>
            </td>
            <td class='t_status_header'><span><?php echo _('Disk usage')?></span></td>
        </tr>
        
        <tr>
            <td class='_label'><?php echo _('Time on system')?></td>
            <td class='_data'><?php echo $system_time;?></td>
            <td class='td_pbar'>
                <?php echo Avc_utilities::create_progress_bar('r_memory_pbar', '', '200px', $rmp, 'progress-blue');?>
            </td>
            <td class='td_spark_line'>
                <div id='r_memory_spark_line' class='div_spark_line' style='position: relative; bottom:0px;'></div>
            </td>
            <td id='td_disk_usage' rowspan='5'>
                <div id='pie_graph'></div>
            </td>
        </tr>
        
        <tr>
            <td class='_label'><?php echo _('System uptime')?></td>
            <td class='_data'><?php echo $system_uptime;?></td>
            <td class='t_status_header' colspan='2'>
                <span><?php echo _('Swap used')?></span>
                <span style='font-weight: normal; font-size: 9px'>
                    [<span class='free'><?php echo _('Free').': '?><?php echo $vmf?></span>,
                    <span class='used'><?php echo _('Used').': '?><?php echo $vmu?></span>,
                    <span class='total'><?php echo _('Total').': '?><?php echo $vmt?></span>]
                </span>
            </td>
        </tr>

        <tr>
            <td class='_label'><?php echo _('Load Average')?></td>
            <td class='_data' id='la_data'><?php echo $load_average;?></td>
            <td class='td_pbar'> 
                <?php echo Avc_utilities::create_progress_bar('s_memory_pbar', '', '200px', $vmp, 'progress-orange');?>
            </td>
            <td class='td_spark_line'>
                <div id='s_memory_spark_line' class='div_spark_line' style='position: relative; bottom:0px;'></div>
            </td>
        </tr>
        
        <tr>
            <td class='_label'><?php echo _('Running processes')?></td>
            <td class='_data' id='rc_data'><?php echo $running_processes;?></td>
            <td class='t_status_header' colspan='2'>
                <span><?php echo _('CPU used')?></span>
                <span style='font-weight: normal; font-size: 9px'>
                    [<span><?php echo $cpu_proc.' - '. $num_cores.' '._('core/s');?></span>]
                </span>
            </td>
        </tr>
        
        <tr>
            <td class='_label'><?php echo _('Current sessions')?></td>
            <td class='_data' id='rc_data'><?php echo $current_sessions;?></td>
            <td class='td_pbar'>  
                <?php echo Avc_utilities::create_progress_bar('cpu_pbar', '', '200px', $cpu_load, 'progress-green');?>
            </td>
            <td class='td_spark_line'>
                <div id='cpu_spark_line' class='div_spark_line' style='position: relative; bottom:0px;'></div>
            </td>
        </tr>
        
    </tbody>
</table>

<script type='text/javascript'>
    
    <?php
    if (!empty($mounted_disks))
    {
        $disk = key($mounted_disks);

        $total_used_ds = $mounted_disks[$disk]['percent_used'];
        $total_free_ds = 100 - $total_used_ds;

        $du = "[['"._('Free')."', ".$total_free_ds."],['"._('Used')."',".$total_used_ds.']]';

        ?>
        System_status.show_pie('pie_graph', [<?php echo $du?>]);
        <?php
    }
    ?>

    r_memory_usage = ['0'];
    s_memory_usage = ['0'];
    cpu_usage      = ['0'];

    r_memory_usage.push('<?php echo $rmp?>');
    s_memory_usage.push('<?php echo $vmp?>');
    cpu_usage.push('<?php echo $cpu_load?>');
        
    $('#r_memory_spark_line').sparkline(r_memory_usage, { lineColor: '#444444', fillColor: '#6DC8E6', width:'160px', height: '18px', chartRangeMin: '0', chartRangeMax: '100'});  
    $('#s_memory_spark_line').sparkline(s_memory_usage, { lineColor: '#444444', fillColor: '#E9B07A', width:'160px', height: '18px', chartRangeMin: '0', chartRangeMax: '100'});
    $('#cpu_spark_line').sparkline(cpu_usage,           { lineColor: '#444444', fillColor: '#A3DB4E', width:'160px', height: '18px', chartRangeMin: '0', chartRangeMax: '100'});
</script>