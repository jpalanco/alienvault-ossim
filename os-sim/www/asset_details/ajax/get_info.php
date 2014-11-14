<?php
/**
 * get_info.php
 * 
 * File get_info.php is used to:
 * - Response ajax call from index.php
 * - Fill the data into asset details General Info section
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
 * @package    ossim-framework\Assets
 * @autor      AlienVault INC
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt
 * @copyright  2003-2006 ossim.net
 * @copyright  2007-2013 AlienVault
 * @link       https://www.alienvault.com/
 */

require_once 'av_init.php';

Session::logcheck('environment-menu', 'PolicyHosts');

// Close session write for real background loading
session_write_close();


$asset_id    = GET('asset_id');
$asset_type  = GET('asset_type');

$response    = array();

ossim_valid($asset_id, 		OSS_HEX, 	'illegal: Asset ID');
ossim_valid($asset_type, 	OSS_ALPHA, 	'illegal: Asset Type');

if (ossim_error()) 
{    
	echo json_encode($response);
	
	exit();
}


// Get object from session
$asset_object = unserialize($_SESSION['asset_detail'][$asset_id]);

if (!is_object($asset_object))
{
    throw new Exception(_('Error retrieving the asset data from memory'));
}


$db   = new ossim_db();
$conn = $db->connect();


$class_name = get_class($asset_object);

// DATA
$response['icon']        = ($asset_type != 'group') ? $asset_object->get_html_icon() : '';

$response['title']       = $asset_object->get_name();

$response['subtitle']    = '';

$response['description'] = ($asset_object->get_descr() != '') ? nl2br(Util::htmlentities($asset_object->get_descr())) : '<i>'._('none').'</i>';

$response['networks']    = '';

$response['os']          = '';

$response['sensors']     = '';

$response['asset_value'] = '';

$response['asset_type']  = '';

$response['owner']       = '';

$response['cidr']        = '';

$response['lat']         = '';

$response['lng']         = '';

$response['zoom']        = '';

// Availability monitoring led
$led_classes       = array('led_red', 'led_green', 'led_yellow');
$is_nagios_enabled = intval($asset_object->is_nagios_enabled($conn));
$nagios_class      = 'led_gray';

if (array_key_exists($is_nagios_enabled, $led_classes))
{
    $nagios_class = $led_classes[$is_nagios_enabled];
}
$response['nagios_class'] = $nagios_class;


if ($asset_type == 'host')
{
    $response['subtitle'] = $asset_object->get_ips()->get_ips('string');
    if ($asset_object->get_fqdns() != '')
    {
         $response['subtitle'] .= '<br/>'.$asset_object->get_fqdns();
    }
    
    $nets = $asset_object->get_nets($conn);
    if (count($nets) > 0)
    {
        $flag = FALSE;
        foreach ($nets as $net_id => $net)
        {
            if ($flag)
            {
                $response['networks'] .= ", ";
            }
             
            $response['networks'] .= "<strong>".$net['name']."</strong> (".$net['ips'].")";
    
            $flag = TRUE;
        }
    }
    else
    {
        $response['networks'] = _('Not found in home networks');
    }
    
    $response['os']          = $asset_object->get_os($conn);
    
    $response['asset_value'] = $asset_object->get_asset_value();
    
    $response['asset_type']  = ($asset_object->get_external()) ? _('External') : _('Internal');
    
    $coordinates             = $asset_object->get_location();
    $response['lat']         = $coordinates['lat'];
    $response['lng']         = $coordinates['lon'];
    $response['zoom']        = $coordinates['zoom'];
}
else
{
    $response['subtitle'] = ($asset_type == 'group') ? _('Static') : _('Network');
    
    $response['owner']    = ($asset_object->get_owner() != '' ) ? $asset_object->get_owner() : '<i>'._('unknown').'</i>';
    
    if ($asset_type == 'net')
    {
        $response['cidr'] = $asset_object->get_ips('string');
    }
}

// Sensors not available for groups
if ($asset_type == 'host' || $asset_type == 'net')
{
    $asset_sensors_obj = $asset_object->get_sensors();
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
    
    $response['sensors']     = $sensors_string;
}

echo json_encode($response);

$db->close();

/* End of file get_info.php */
/* Location: ./asset_details/ajax/get_info.php */