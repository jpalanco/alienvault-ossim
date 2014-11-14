<?php
/**
 * get_properties.php
 * 
 * File get_properties.php is used to:
 * - Response ajax call from index.php
 * - Fill the data into asset details Properties tab
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

$asset_id   = GET('asset_id');
$maxrows    = (POST('iDisplayLength') != '') ? POST('iDisplayLength') : 15;
$search_str = (POST('sSearch') != '')        ? POST('sSearch') : '';
$from       = (POST('iDisplayStart') != '')  ? POST('iDisplayStart') : 0;
$sec        = POST('sEcho');

ossim_valid($asset_id, 		OSS_HEX, 				   	'illegal: '._('Asset ID'));
ossim_valid($maxrows, 		OSS_DIGIT, 				   	'illegal: '._('Configuration Parameter 1'));
ossim_valid($search_str, 	OSS_INPUT, OSS_NULLABLE,   	'illegal: '._('Search String'));
ossim_valid($from, 			OSS_DIGIT,         			'illegal: '._('Configuration Parameter 2'));
ossim_valid($sec, 			OSS_DIGIT,  	            'illegal: '._('Configuration Parameter 3'));

if (ossim_error()) 
{
    $response['sEcho']                = intval($sec);
    $response['iTotalRecords']        = 0;
    $response['iTotalDisplayRecords'] = 0;
    $response['aaData']               = array();
    
    echo json_encode($response);
    exit();
}


$db   = new ossim_db();
$conn = $db->connect();


// Get plugins by asset using Alienvault API

$total    = 0;
$data     = array();
$plugins  = array();

try
{    
    $sensors = Asset_host_sensors::get_sensors_by_id($conn, $asset_id);

    //Show column 'Sensor' when there are two sensors or more
    $num_sensors = count($sensors);

    $client = new Alienvault_client();
 
    foreach ($sensors as $sensor_id => $s_data)
    {
        $plugins  = $client->sensor(Util::uuid_format($sensor_id))->get_plugins_by_assets();
        $plugins  = @json_decode($plugins, TRUE);

        if ($plugins['status'] == 'success')
        {
            if (array_key_exists($asset_id, $plugins['data']['plugins']))
            {
                $plugins = $plugins['data']['plugins'][$asset_id];

                foreach ($plugins as $plugin_name => $pdata)
                {
                    $total++;

                    if (!empty($search_str))
                    {
                        $aux_search_str = '/'.strtolower($search_str).'/';
                        $aux_cpe = strtolower($pdata['cpe']);

                        if (!preg_match($aux_search_str, $aux_cpe))
                        {
                            continue;
                        }
                    }

                    $vmv = Software::get_vmv_by_cpe($conn, $pdata['cpe']);

                    if (!empty($s_data))
                    {
                        $aux_data = array(
                            $vmv['vendor'],
                            $vmv['name'],
                            $vmv['version'],
                            $plugin_name,
                            $s_data['ip'].' ['.$s_data['name'].']',
                            ''
                        );

                        //Row ID
                        $row_id = md5($asset_id . $pdata['cpe'] . $sensor_id);
                        $aux_data['DT_RowId'] = $row_id;

                        $data[] = $aux_data;
                    }
                }
            }
        }
    }
}
catch(Exception $e)
{
   //nothing here
}



$response['sEcho']                = $sec;
$response['iTotalRecords']        = $total;
$response['iTotalDisplayRecords'] = $total;
$response['aaData']               = $data;
$response['show_sensors']         = (count($sensors) > 1) ? TRUE : FALSE;


$db->close();

echo json_encode($response);

/* End of file get_plugins.php */
/* Location: ./asset_details/ajax/get_plugins.php */