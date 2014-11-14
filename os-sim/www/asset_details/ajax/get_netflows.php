<?php
/**
 * get_netflows.php
 * 
 * File get_netflows.php is used to:
 * - Response ajax call from index.php by dataTable jquery plugin
 * - Fill the data into asset details Netflows section
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

function ReportLog()
{
    ;
}

require      AV_MAIN_ROOT_PATH . '/nfsen/conf.php';
require_once AV_MAIN_ROOT_PATH . '/nfsen/nfsenutil.php';
require_once AV_MAIN_ROOT_PATH . '/sensor/nfsen_functions.php';


$asset_id   = GET('asset_id');
$asset_type = GET('asset_type');
$maxrows    = (POST('iDisplayLength') != '') ? POST('iDisplayLength') : 15;
$search_str = (POST('sSearch') != '') ? POST('sSearch') : '';
$from       = (POST('iDisplayStart') != '') ? POST('iDisplayStart') : 0;
$order      = (POST('iSortCol_0') != '') ? POST('iSortCol_0') : '';
$torder     = POST('sSortDir_0');
$sec        = POST('sEcho');


ossim_valid($asset_id,      OSS_HEX,                                            'illegal: ' . _('Asset ID'));
ossim_valid($asset_type,    OSS_ALPHA,                                          'illegal: ' . _('Asset Type'));
ossim_valid($maxrows,       OSS_DIGIT, OSS_SCORE,                               'illegal: ' . _('Configuration Parameter 1'));
ossim_valid($search_str,    OSS_INPUT, OSS_NULLABLE,                            'illegal: ' . _('Search String'));
ossim_valid($from,          OSS_DIGIT,                                          'illegal: ' . _('Configuration Parameter 2'));
ossim_valid($order,         OSS_ALPHA, OSS_DOT, OSS_SCORE, OSS_SPACE,   '\,',   'illegal: ' . _('Configuration Parameter 3'));
ossim_valid($torder,        OSS_ALPHA,                                          'illegal: ' . _('Configuration Parameter 4'));
ossim_valid($sec,           OSS_DIGIT,                                          'illegal: ' . _('Configuration Parameter 5'));

if (ossim_error()) 
{
    $response['sEcho']                = intval($sec);
    $response['iTotalRecords']        = 0;
    $response['iTotalDisplayRecords'] = 0;
    $response['aaData']               = array();    
     
    echo json_encode($response);
    exit();
}

// Get object from session
$asset_object = unserialize($_SESSION['asset_detail'][$asset_id]);

if (!is_object($asset_object))
{
    throw new Exception(_("Error retrieving the asset data from memory"));
}


/* connect to db */
$db   = new ossim_db(TRUE);
$conn = $db->connect();


// Group get_sensors method is different
if ($asset_type == 'group')
{
    $asset_sensors = $asset_object->get_sensors($conn);
}
else
{
    $asset_sensors = $asset_object->get_sensors()->get_sensors();
}


// NFSEN Options
$cmd_opts = array(
    'type'    => 'real',
    'profile' => './live'
);

// IP/CIDR filter
if ($asset_type == 'net')
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

$hourFrom = date('H',strtotime('-1 hour')).$hourFile;
$hourTo   = date('H',time()).$hourFile;

$cmd_opts['args'] = '-T  -R '.$date_from.'/nfcapd.'.$date_from_format.$hourFrom.':'.$date_to.'/nfcapd.'.$date_to_format.$hourTo.' -o extended -m';
if ($maxrows > 0)
{
    $cmd_opts['args'] .= " -c $maxrows";
}

$cmd_out = nfsend_query('run-nfdump', $cmd_opts);
//Very important to disconnect!!
nfsend_disconnect();
    
$list    = (preg_match("/ extended /",$cmd_out['args'])) ? 1 : 0;
$regex   = ($list) ? "/(\d\d\d\d\-.*?\s.*?)\s+(.*?)\s+(.*?)\s+(.*?)\s+->\s+(.*?)\s+(.*?)\s+(.*?)\s+(.*?)\s+(.*?\s*[KMG]?)\s+(.*?)\s+(.*?)\s+(.*?)\s+(.*)/" : "/(\d\d\d\d\-.*?\s.*?)\s+(.*?)\s+(.*?)\s+(.*?)\s+(.*?)\s+(.*?)\s+(.*?\s*[KMGT]?)\s+(.*?)\s+(.*?)\s+(.*)/";

$data  = array();
$total = 0;
$error = ''; 

// Error
if (count($cmd_out['nfdump']) == 1 && preg_match("/stat\(\) error/", $cmd_out['nfdump'][0]))
{
    $error = $cmd_out['nfdump'][0];  
}
elseif (count($cmd_out['nfdump']) > 0) // Has Results
{
    foreach ($cmd_out['nfdump'] as $k => $line)
    {
        if (preg_match($regex,preg_replace('/\s*/', ' ', $line), $found))
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
$response['Error']                = $error;

echo json_encode($response);


$db->close();

/* End of file get_netflows.php */
/* Location: ./asset_details/get_netflows.php */