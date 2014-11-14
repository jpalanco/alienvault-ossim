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

if ($_SERVER['SCRIPT_NAME'] != '/ossim/av_center/data/sections/home/software.php')
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
    
    echo "error###$error_msg";
    exit();
}


try
{
    $st = Av_center::get_system_status($system_id, 'software', $force_request);
}
catch (\Exception $e)
{
    echo 'error###'.$e->getMessage();
    exit();
}




/*************************************************************
***********************  Software Data  **********************
**************************************************************/

$current_version     = _('Unknown');
$packages_installed  = _('Unknown');
$last_update         = '--';

if (is_array($st) && !empty($st))
{
    //Packages installed
    $packages_installed  = $st['packages']['total'];

    //Last update
    if ($st['last_update'] != '' && $st['last_update'] != 'unknown')
    {
        $last_update = gmdate('Y-m-d H:i:s', strtotime($st['last_update'].' GMT') + (3600 * Util::get_timezone()));
    }

    //Current version
    $current_version  = "<span>".$st['current_version']."</span>";

    if ($st['packages']['pending_updates'] == FALSE)
    {
        $current_version .= "<span class='green_pkg'>"._('UPDATED')."</span>";
    }
    else
    {
        $current_version  .= "<span class='red_pkg'>"._('UPDATE')."</span>";
    }
}
?>


<table id='t_software'>
    <thead>
        <tr>
            <th id='title_pi' class='th_software' colspan="2"><?php echo _('Package Information')?></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class='_label'><?php echo _('Current version')?></td>
            <td class='_data cv_link'><a href='javascript:void(0)' class="sw_pkg_pending"><?php echo $current_version;?></a></td>
        </tr>
        <tr>
            <td class='_label'><?php echo _('Last update')?></td>
            <td class='_data'><?php echo $last_update;?></td>
        </tr>
        <tr>
            <td class='_label pkg_installed_r'><?php echo _('Packages installed')?></td>
            <td class='_data pkg_installed_l'><a href='#' class="sw_pkg_installed"><?php echo $packages_installed;?></a></td>
        </tr>
    </tbody>
</table>


<script type="text/javascript">

    //Package Information

    $('.sw_pkg_pending').bind('click', function(event) {
        event.preventDefault(); 
        section.load_section('sw_pkg_pending');
    });
    
    $('.sw_pkg_installed').bind('click', function(event) {
        event.preventDefault(); 
        section.load_section('sw_pkg_installed');
    });


    var config_t = {content: labels['view_details']};
    Js_tooltip.show('.sw_pkg_pending, .sw_pkg_installed', config_t);

</script>