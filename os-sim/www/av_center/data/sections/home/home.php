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


//Config File
require_once dirname(__FILE__) . '/../../../config.inc';

session_write_close();

//Profiles enabled
$profiles = (empty($_POST['profiles'])) ? array() : array_flip(explode(',', $_POST['profiles']));
?>

<div id='t_home'>

    <div id='t_menu'>
        <a href='#' id='ld_su'><?php echo _('Software Updates')?></a>
        <span style='margin: 0px 3px;'>|</span>
        <a href='#' id='ld_gc'><?php echo _('General Configuration')?></a>
        <span style='margin: 0px 3px;'>|</span>
        <a href='#' id='ld_nc'><?php echo _('Network Configuration')?></a>
        <span style='margin: 0px 3px;'>|</span>
        <?php
        if (array_key_exists('sensor', array_change_key_case($profiles, CASE_LOWER)))
        {
            ?>
            <a href='#' id='ld_sc'><?php echo _('Sensor Configuration')?></a>
            <span style='margin: 0px 3px;'>|</span>
            <?php
        }
        ?>
        <a href='#' id='ld_logs'><?php echo _('Logs')?></a>
    </div>


    <!-- System Status -->
    <div class='panel' id='p_system_status'>
        <div class='panel_header'>
            <div class='l_ph'><img id='tg_system_status' class='show' src='<?php echo AVC_PIXMAPS_DIR."/b_home_arrow.png"?>' align='absmiddle'/></div>
            <div class='c_ph'><span><?php echo _('System Status')?></span></div>
            <div class='r_ph'><img src='<?php echo AVC_PIXMAPS_DIR."/refresh.png"?>' id='h_system_status_refresh' title='<?php echo _('Refresh')?>' alt='refresh.png'/></div>
        </div>
        
        <div class='panel_body' id='h_system_status'></div>
    </div>

    <!-- Network -->
    <div class='panel' id='p_network'>
        <div class='panel_header'>
            <div class='l_ph'><img id='tg_network' class='show' src='<?php echo AVC_PIXMAPS_DIR."/b_home_arrow.png"?>' align='absmiddle'/></div>
            <div class='c_ph'><span><?php echo _('Network')?></span></div>
            <div class='r_ph'><img src='<?php echo AVC_PIXMAPS_DIR."/refresh.png"?>' id='h_network_refresh' title='<?php echo _('Refresh')?>' alt='refresh.png'/></div>
        </div>
        
        <div class='panel_body' id='h_network'></div>
    </div>

    <!-- Software -->
    <div class='panel' id='p_software'>
        <div class='panel_header'>
            <div class='l_ph'><img id='tg_software' class='show' src='<?php echo AVC_PIXMAPS_DIR."/b_home_arrow.png"?>' align='absmiddle'/></div>
            <div class='c_ph'><span><?php echo _('Software')?></span></div>
            <div class='r_ph'><img src='<?php echo AVC_PIXMAPS_DIR."/refresh.png"?>' id='h_software_refresh' title='<?php echo _('Refresh')?>' alt='refresh.png'/></div>
        </div>
        
        <div class='panel_body' id='h_software'></div>
    </div>

    <!-- Alienvault Status -->
    <div class='panel' id='p_alienvault_status'>
        <div class='panel_header'>
            <div class='l_ph'><img id='tg_alienvault_status' class='show' src='<?php echo AVC_PIXMAPS_DIR."/b_home_arrow.png"?>' align='absmiddle'/></div>
            <div class='c_ph'><span><?php echo _('Alienvault Status')?></span></div>
            <div class='r_ph'><img src='<?php echo AVC_PIXMAPS_DIR."/refresh.png"?>' id='h_alienvault_status_refresh' title='<?php echo _('Refresh')?>' alt='refresh.png'/></div>
        </div>
        
         <div class='panel_body' id='h_alienvault_status'></div>
    </div>
</div>   

<script type='text/javascript'>

    var panels    = ["system_status", "alienvault_status", "software", "network"];
    var f_request = [0, 0, 0, 0];
    var size = panels.length;

    for (var i=0; i<size; i++)
    {
        Home.load_panel(panels[i], f_request[i]);
    }

    //Top Menu
    $('#t_menu a').off('click');
    $('#ld_su').on('click', function(event)   { event.preventDefault(); section.load_section('sw_pkg_pending'); });
    $('#ld_gc').on('click', function(event)   { event.preventDefault(); section.load_section('cnf_general'); });
    $('#ld_nc').on('click', function(event)   { event.preventDefault(); section.load_section('cnf_network'); });
    $('#ld_sc').on('click', function(event)   { event.preventDefault(); section.load_section('cnf_sensor'); });
    $('#ld_logs').on('click', function(event) { event.preventDefault(); section.load_section('logs'); });

</script>