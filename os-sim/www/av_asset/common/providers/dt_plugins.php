<?php
/**
 * dt_plugins.php
 *
 * File dt_plugins.php is used to:
 *  - Build JSON data that will be returned in response to the Ajax request made by DataTables (Plugin list)
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

$asset_id   =  POST('asset_id');
$asset_type =  POST('asset_type');
$sensor_id  =  POST('sensor_id');
$edit_mode  =  (POST('edit_mode') != '') ? TRUE : FALSE;

$maxrows    = (POST('iDisplayLength') != '') ? POST('iDisplayLength') : 8;
$from       = (POST('iDisplayStart') != '')  ? POST('iDisplayStart')  : 0;

$order      = (POST('iSortCol_0') != '')     ? POST('iSortCol_0')     : '';
$torder     =  POST('sSortDir_0');
$search_str = (POST('sSearch') != '')        ? POST('sSearch') : '';

$sec        =  POST('sEcho');

Session::logcheck_by_asset_type($asset_type);

// Close session write for real background loading
session_write_close();

ossim_valid($asset_id,      OSS_HEX,                                  'illegal: '._('Asset ID'));
ossim_valid($asset_type,    OSS_LETTER, OSS_SCORE, OSS_NULLABLE,      'illegal: '._('Asset Type'));
ossim_valid($sensor_id,     OSS_HEX, OSS_NULLABLE,                    'illegal: '._('Sensor ID'));
ossim_valid($maxrows,       OSS_DIGIT,                                'illegal: iDisplayLength');
ossim_valid($from,          OSS_DIGIT,                                'illegal: iDisplayStart');
ossim_valid($order,         OSS_ALPHA,                                'illegal: iSortCol_0');
ossim_valid($torder,        OSS_LETTER,                               'illegal: sSortDir_0');
ossim_valid($search_str,    OSS_INPUT, OSS_NULLABLE,                  'illegal: sSearch');
ossim_valid($sec,           OSS_DIGIT,                                'illegal: sEcho');


if (ossim_error())
{
    Util::response_bad_request(ossim_get_error_clean());
}



$db   = new ossim_db();
$conn = $db->connect();


// Check Asset Type
$asset_types = array(
    'asset'   => 'Asset_host',
    'network' => 'Asset_net',
    'group'   => 'Asset_group'
);


// Order by column
$orders_by_columns = array(
    '0' => 'asset',         // Order by Hostname
    '1' => 'vendor',        // Order by Vendor
    '2' => 'model',         // Order by Model
    '3' => 'version',       // Order by Version
    '4' => 'sensor',        // Order by Sensor
    '5' => 'receiving_data' // Order by Activity
);


try
{
    $db   = new Ossim_db();
    $conn = $db->connect();

    if (isset($_POST['asset_id']) && isset($_POST['asset_type']))
    {
        if (!array_key_exists($asset_type, $asset_types))
        {
            Av_exception::throw_error(Av_exception::USER_ERROR, _('Error! Invalid Asset Type'));
        }

        $class_name = $asset_types[$_POST['asset_type']];

        // Check Asset Permission
        if (method_exists($class_name, 'is_allowed') && !$class_name::is_allowed($conn, $asset_id))
        {
            $error = sprintf(_('Error! %s is not allowed'), ucwords($asset_type));

            Av_exception::throw_error(Av_exception::USER_ERROR, $error);
        }

        $asset_object = $class_name::get_object($conn, $asset_id);

        $plugin_data  = $asset_object->get_plugins($conn, $edit_mode, $sensor_id);
        $total        = count($plugin_data);
    }
    else
    {
        Av_exception::throw_error(Av_exception::USER_ERROR, _('Error retrieving information'));
    }
}
catch(Exception $e)
{
    $db->close();

    Util::response_bad_request($e->getMessage());
}


// DATA
$data = array();
$i    = 0;

$sensors = $asset_object->get_sensors($conn);
if (!is_array($sensors)) {
	$sensors = $sensors->get_sensors();
}
$counter = 0;
foreach ($sensors as $key => $sensor) {
	$asset_plugins = Plugin::get_plugins_by_assets($key);
	$cnt = 0;
	foreach ($asset_plugins as $ap) {
		$cnt += count ($ap);
	}
	if ($cnt > $counter) {
		$counter = $cnt;
	}
}


try
{
    if ($edit_mode)
    {

        /**
         * EDIT MODE: No order, no search filter, and looping $plugin_data array is a based asset_id Hash
         */

        $total_filtered = $total;

        foreach ($plugin_data as $asset_id => $asset_plugins)
        {
            // Apply pagination
            if ($i >= $from && $i < $from + $maxrows)
            {
                $_row_data = array();

                foreach ($asset_plugins as $_pdata)
                {
                    $model_list   = array();
                    $version_list = array();

                    if ($_pdata['vendor'] != '')
                    {
                        $model_list = Software::get_models_by_vendor($_pdata['vendor'], $sensor_id);
                    }

                    if ($_pdata['model'] != '')
                    {
                        $version_list = Software::get_versions_by_model($_pdata['vendor'].':'.$_pdata['model'], $sensor_id);
                    }

                    $_row_data[$asset_id][] = array(
                        'asset_id'     => $asset_id,
                        'vendor'       => $_pdata['vendor'],
                        'model'        => $_pdata['vendor'].':'.$_pdata['model'],
                        'version'      => $_pdata['vendor'].':'.$_pdata['model'].':'.$_pdata['version'],
                        'model_list'   => json_encode($model_list),
                        'version_list' => json_encode($version_list)
                    );
                }


                $aux_data   = array();
                $aux_data[] = $_pdata['asset'];
                $aux_data[] = ''; // Empty. Here will be located the vendor/model/version select boxes
                $aux_data['DT_RowData'] = $_row_data;

                //Row ID
                $aux_data['DT_RowId'] = $asset_id;

                $data[] = $aux_data;
            }

            $i++;
        }
    }
    else
    {
        /**
         * READ-ONLY LIST MODE: Order by some columns, search string optional and $plugin_data is an array with no keys
         */

        // Filtering by search string
        if (!empty($search_str))
        {
            $_plugin_data_filtered = array();

            foreach ($plugin_data as $_pdata)
            {
                $aux_search_str = '/'.strtolower($search_str).'/';

                if (preg_match($aux_search_str, $_pdata['name']))
                {
                    $_plugin_data_filtered[] = $_pdata;
                }
            }

            $plugin_data = $_plugin_data_filtered;
        }

        // Possible new total after filtering
        $total_filtered = count($plugin_data);

        if (array_key_exists($order, $orders_by_columns))
        {
            $order = $orders_by_columns[$order];
        }
        else
        {
            $order = ($asset_type == 'asset') ? 'model' : 'asset';
        }


        $order_index    = array(); // Array to apply sort
        $p_data_indexed = array(); // Hash to save the relation p_id => p_data

        foreach ($plugin_data as $_pdata)
        {
            // Plugin Unique ID
            $p_id = md5($_pdata['asset_id'] . $_pdata['plugin_id'] . $_pdata['sensor_id']);

            $order_index[$p_id]    = $_pdata[$order];
            $p_data_indexed[$p_id] = $_pdata;
        }

        if ($torder == 'asc')
        {
            asort($order_index);
        }
        else
        {
            arsort($order_index);
        }

        foreach ($order_index as $row_id => $_order_key)
        {
            // Apply pagination
            if ($i >= $from && $i < $from + $maxrows)
            {
                $_pdata = $p_data_indexed[$row_id];
                

                $aux_data = array(
                    $_pdata['asset'],
                    $_pdata['vendor'],
                    $_pdata['model'],
                    $_pdata['version'],
                    $_pdata['sensor'],
                    ($_pdata['receiving_data'] ? _('Yes') : _('No'))
                );


                //Row ID
                $aux_data['DT_RowId'] = $row_id;

                $data[] = $aux_data;
            }

            $i++;
        }
    }
}
catch(Exception $e)
{
    $db->close();

    Util::response_bad_request($e->getMessage());
}

$response['total_counter']        = $counter;
$response['sEcho']                = $sec;
$response['iTotalRecords']        = $total;
$response['iTotalDisplayRecords'] = $total_filtered;
$response['aaData']               = $data;

echo json_encode($response);

$db->close();

/* End of file dt_plugins.php */
/* Location: /av_asset/common/providers/dt_plugins.php */
