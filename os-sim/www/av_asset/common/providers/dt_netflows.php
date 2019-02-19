<?php
/**
 * dt_netflows.php
 *
 * File dt_netflows.php is used to:
 *  - Build JSON data that will be returned in response to the Ajax request made by DataTables (Netflow list)
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

function ReportLog()
{
    ;
}

$asset_id   =  POST('asset_id');
$asset_type =  POST('asset_type');

$maxrows    = (POST('iDisplayLength') != '') ? POST('iDisplayLength') : 8;

$sec        =  POST('sEcho');

Session::logcheck_by_asset_type($asset_type);
Session::logcheck_ajax('environment-menu', 'MonitorsNetflows');

require      AV_MAIN_ROOT_PATH . '/nfsen/conf.php';
require_once AV_MAIN_ROOT_PATH . '/nfsen/nfsenutil.php';
require_once AV_MAIN_ROOT_PATH . '/sensor/nfsen_functions.php';

// Close session write for real background loading
session_write_close();

ossim_valid($asset_id,      OSS_HEX,                                  'illegal: '._('Asset ID'));
ossim_valid($asset_type,    OSS_LETTER, OSS_SCORE, OSS_NULLABLE,      'illegal: '._('Asset Type'));
ossim_valid($maxrows,       OSS_DIGIT,                                'illegal: iDisplayLength');
ossim_valid($sec,           OSS_DIGIT,                                'illegal: sEcho');


if (ossim_error())
{
    Util::response_bad_request(ossim_get_error_clean());
}


// Check Asset Type
$asset_types = array(
    'asset'   => 'Asset_host',
    'network' => 'Asset_net',
    'group'   => 'Asset_group'
);


// NFSEN Options
$cmd_opts = array(
    'type'    => 'real',
    'profile' => './live'
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

        // Getting sensors
        if ($asset_type == 'group')
        {
            $asset_sensors = $asset_object->get_sensors($conn);
        }
        else
        {
            $asset_sensors = $asset_object->get_sensors()->get_sensors();
        }



        // IP/CIDR filter
        if ($asset_type == 'network')
        {
            $filter_values = $asset_object->get_ips('array');
            $filter_key    = 'net';
        }
        elseif ($asset_type == 'group')
        {
            $_list_data = $asset_object->get_hosts($conn);
            $hosts      = $_list_data[0];
            $filter_values = array();
            $filter_key    = 'ip';

            foreach ($hosts as $host_id => $host_data)
            {
                $ips = explode(',', $host_data['ips']);

                foreach ($ips as $ip)
                {
                    $filter_values[] = $ip;
                }
            }
        }
        else
        {
            $ips           = $asset_object->get_ips();
            $filter_values = array_keys($ips->get_ips('array'));
            $filter_key    = 'ip';
        }

        // Make filter
        foreach ($filter_values as $val)
        {
            if ($cmd_opts['filter'][0] != '')
            {
                $cmd_opts['filter'][0] .= ' or';
            }

            $cmd_opts['filter'][0] .= " $filter_key $val";
        }

        //Getting the sources of the nsfen: We need to check if the sensors of the host are nfsen sources.
        $sources       = get_nfsen_sensors();
        $asset_sensors = (is_array($asset_sensors)) ? $asset_sensors : array();
        $n_src_list    = array();
        foreach($asset_sensors as $_sensor_id => $_sensor_data)
        {
            $sensor_object = Av_sensor::get_object($conn, $_sensor_id);
            $channel_id    = $sensor_object->get_nfsen_channel_id($conn);

            if (array_key_exists($channel_id, $sources))
            {
                $n_src_list[]  = $channel_id;
            }
        }

        $cmd_opts['srcselector'] = implode(':', $n_src_list);

        //Adding the timing window. Only one hour
        $date_from               = date('Y-m-d', strtotime('-1 hour'));
        $date_from_format        = str_replace('-', '', $date_from);

        $date_to                 = date('Y-m-d');
        $date_to_format          = str_replace('-', '', $date_to);

        //This is the same than in the netflow report...
        $hourFile = date('i', time());

        if($hourFile[1]>5)
        {
            $hourFile = $hourFile[0].'0';
        }
        else
        {
            if ($hourFile[0] <= '6' && $hourFile[0] > '1')
            {
                $hourFile[0] = (string)($hourFile[0]-1);
            }
            else
            {
                $hourFile[0] = '1';
            }

            $hourFile = $hourFile[0].'5';
        }

        $hourFrom = '0000';
        $hourTo   = '2359';

        $cmd_opts['args'] = '-T  -R '.$date_from.'/nfcapd.'.$date_from_format.$hourFrom.':'.$date_to.'/nfcapd.'.$date_to_format.$hourTo.' -o extended -m';

        //pagination
        if ($maxrows > 0)
        {
            $cmd_opts['args'] .= " -c $maxrows";
        }

        $cmd_out = nfsend_query('run-nfdump', $cmd_opts);
        //Very important to disconnect!!
        nfsend_disconnect();
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


$list    = (preg_match("/ extended /", $cmd_out['args'])) ? 1 : 0;
$regex   = ($list) ? "/(\d\d\d\d\-.*?\s.*?)\s+(.*?)\s+(.*?)\s+(.*?)\s+->\s+(.*?)\s+(.*?)\s+(.*?)\s+(.*?)\s+(.*?\s*[KMG]?)\s+(.*?)\s+(.*?)\s+(.*?)\s+(.*)/" : "/(\d\d\d\d\-.*?\s.*?)\s+(.*?)\s+(.*?)\s+(.*?)\s+(.*?)\s+(.*?)\s+(.*?\s*[KMGT]?)\s+(.*?)\s+(.*?)\s+(.*)/";

$data  = array();
$total = 0;
$error = '';

// Error
if (count($cmd_out['nfdump']) == 1 && preg_match("/stat\(\) error/", $cmd_out['nfdump'][0]))
{
    $error = $cmd_out['nfdump'][0];

    $db->close();

    Util::response_bad_request($error);
}
elseif (count($cmd_out['nfdump']) > 0) // Has Results
{
    foreach ($cmd_out['nfdump'] as $k => $line)
    {
        if (preg_match($regex, preg_replace('/\s*/', ' ', $line), $found))
        {
            $data[] = array(
                $found[1],
                $found[2],
                $found[3],
                $found[4],
                $found[6],
                $found[7]
            );

            $total++;
        }
    }
}


$response['sEcho']                = $sec;
$response['iTotalRecords']        = $total;
$response['iTotalDisplayRecords'] = $total;
$response['aaData']               = $data;

echo json_encode($response);

$db->close();

/* End of file dt_netflows.php */
/* Location: /av_asset/common/providers/dt_netflows.php */
