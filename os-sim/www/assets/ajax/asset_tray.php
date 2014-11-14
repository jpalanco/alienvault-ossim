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

header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . 'GMT');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

require_once 'av_init.php';


Session::logcheck('environment-menu', 'PolicyHosts');


$asset_id = GET('id');

ossim_valid($asset_id, OSS_HEX, OSS_NULLABLE, 'illegal:' . _('Asset ID'));

if (ossim_error()) 
{
    die(ossim_error());
}

$db   = new ossim_db(TRUE);
$conn = $db->connect();

try
{
    $asset = new Asset_host($conn, $asset_id);
    $asset->load_from_db($conn);
}
catch(Exception $e) 
{
    echo _('Impossible to load the host info');
    die();
}

?>

<div id='tray_container'>

    <div class="tray_triangle"></div>
    
    <div id='tray_host_networks' class='tray_section'>
    
        <div class='tray_title'>
            <?php echo _('Networks') ?>
        </div>
        
        <div class='tray_content'>
            <?php
            $nets = $asset->get_nets($conn);
            
            if (count($nets) > 0)
            {
                echo '<ul>';
                
                foreach ($nets as $net)
                {
                   echo '<li>'.$net['name'].'</li>';
                }
                
                echo '</ul>';
            }
            else
            {
                echo '-';
            }
            ?>
        </div>
        
    </div>

    <div id='tray_host_device' class='tray_section'>
    
        <div class='tray_title'>
            <?php echo _('Device Type') ?>
        </div>
        
        <div class='tray_content'>
            <?php
            
            $dev_obj = $asset->get_devices();
            $devices = $dev_obj->get_devices_list();
            
            if (count($devices) > 0)
            {
                echo '<ul><li>' . implode('</li><li>', $devices) . '</li></ul>';
            }
            else
            {
                echo '-';
            }
            
            ?>
        </div>
    </div>
        
    <div id='tray_host_description' class='tray_section'>
    
        <div class='tray_title'>
            <?php echo _('Description') ?>
        </div>
        
        <div class='tray_content'>
            <?php
            
            $descr = ($asset->get_descr() != '') ? $asset->get_descr() : '-'; 
            
            ?>
            
            <textarea readonly="readonly"><?php echo Util::utf8_encode2($descr) ?></textarea>
            
        </div>
    </div>
    
    <div id='tray_host_description' class='tray_section'>
        <div id='tray_host_os'>
        
            <div class='tray_title'>
                <?php echo _('Operating System') ?>
            </div>
            
            <div class='tray_content'>
                <?php
                
                $props = $asset->get_properties($conn);
                $props = @array_shift( $props[0] );
                $_op   = $props[3]['values'][0];

                echo ($_op == '') ? _('Unknown') : $_op;
                
                ?>
            </div>
            
        </div>
        
        <div id='tray_host_avalue'>
        
            <div class='tray_title'>
                <?php echo _('Asset Value') ?>
            </div>
            
            <div class='tray_content'>
				<?php 
				
				for ($i = 0; $i <= 5; $i++)
				{	
				    $class = ($asset->get_asset_value() == $i) ? 'tray_selected_value' : '';
				?>
				    <span class="tray_content <?php echo $class ?>"><?php echo $i ?></span>
				    
				<?php
				} 
				?>

            </div>
        </div>
    </div>
    
    <div class='tray_button_list'>
        <input type='button' class='tray_button' onclick='link_to("<?php echo $asset_id ?>");' value="<?php echo _('Details') ?>">
    </div>
    
    <div style='width:100%;clear:both'></div>
</div>

<?php
$db->close();
