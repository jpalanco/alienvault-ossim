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

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

require_once 'av_init.php';

$net_id = GET('id');

ossim_valid($net_id, OSS_HEX, 'illegal:' . _("Net UUID"));

if (ossim_error()) 
{
    die(ossim_error());
}

$db   = new ossim_db(TRUE);
$conn = $db->connect();

try
{
    $asset = new Asset_net($net_id);
    $asset->load_from_db($conn);
}
catch(Exception $e)
{    
    echo _('Impossible to load the network info');
    die();
}

?>
<div id='tray_container'>

    <div class="tray_triangle"></div>
    
    <div id='tray_host_owner' class='tray_section'>
        
        <div>
            <div class='tray_title'>
                <?php echo _('Owner') ?>
            </div>
            <div class='tray_content'>
                <?php
                $owner = ($asset->get_owner() != '' ) ? $asset->get_owner() : '<i>'._('unknown').'</i>';
                echo Util::utf8_encode2($owner);
                ?>
            </div>
        </div>
        
        <div>
            <div class='tray_title'>
                <?php echo _('CIDR')?>
            </div>
            
            <div class='tray_content'>
                <?php echo $asset->get_ips('string') ?>
            </div>
        </div>
        
        <div>
            <div class='tray_title'>
                <?php echo _('Sensors')?>
            </div>
            
            <div class='tray_content'>
                <?php
                $asset_sensors_obj = $asset->get_sensors();
                $asset_sensors     = $asset_sensors_obj->get_sensors();
                $sensors_string    = '';
                foreach ($asset_sensors as $sensor_id => $sensor_data)
                {
                    if ($sensors_string != '')
                    {
                        $sensors_string .= ', ';
                    }
                    
                    $sensors_string .= $sensor_data['ip'].' ('.$sensor_data['name'].')';
                }
                
                echo $sensors_string;
                
                ?>
            </div>
        </div>
        
    </div>
    
    <div id='tray_host_description' class='tray_section'>
        <div class='tray_title'>
            <?php echo _('Description') ?>
        </div>
        <div class='tray_content'>
            <?php
            $descr = ($asset->get_descr() != "") ? $asset->get_descr() : '<i>'._('none').'</i>'; 
            echo Util::utf8_encode2($descr);
            ?>
        </div>
    </div>
    
    <div class='tray_button_list'>
        <input type='button' class='tray_button' onclick='link_to("<?php echo $net_id ?>");' value="<?php echo _('Details') ?>">
    </div>
    
    <div style='width:100%;clear:both'></div>
</div>

<?php

$db->close();

?>
