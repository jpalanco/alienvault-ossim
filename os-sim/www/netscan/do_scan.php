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

ob_implicit_flush();

require_once 'av_init.php';

Session::logcheck('environment-menu', 'ToolsScan');

ini_set('max_execution_time','1200');

$assets          = array();
$info_error      = array();

$assets          = GET('assets');
$scan_mode       = GET('scan_mode');
$timing_template = GET('timing_template');
$custom_ports    = GET('custom_ports');
$sensor          = GET('sensor');
$only_stop       = intval(GET('only_stop'));
$only_status     = intval(GET('only_status'));
$autodetect      = (GET('autodetect') == '1') ? 1 : 0;
$rdns            = (GET('rdns') == '1') ? 1 : 0;
$custom_ports    = str_replace(' ', '', $custom_ports);
        
ossim_valid($scan_mode,       OSS_ALPHA, OSS_SCORE, OSS_NULLABLE,                 'illegal:' . _('Full scan'));
ossim_valid($timing_template, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE,                  'illegal:' . _('Timing_template'));
ossim_valid($custom_ports,    OSS_DIGIT, OSS_SPACE, OSS_SCORE, OSS_NULLABLE, ',', 'illegal:' . _('Custom Ports'));
ossim_valid($sensor,          OSS_HEX, OSS_ALPHA, OSS_NULLABLE,                   'illegal:' . _('Sensor'));
ossim_valid($only_stop,       OSS_DIGIT, OSS_NULLABLE,                            'illegal:' . _('Only stop'));
ossim_valid($only_status,     OSS_DIGIT, OSS_NULLABLE,                            'illegal:' . _('Only status'));

if (ossim_error())
{ 
    $data['status']  = 'error';
	$data['data']    = "<div style='text-align: left; padding: 0px 0px 3px 10px;'>"._('We found the following errors').":</div>
						<div class='error_item'>".ossim_get_error_clean()."</div>";
	
	echo json_encode($data);
	exit();
}


$assets_string = '';

$data['status'] = 'OK';
$data['data']   = NULL;


$error = FALSE;
$aux   = array();

$db    = new ossim_db();
$conn  = $db->connect();


if (is_array($assets) && count($assets) > 0) 
{
    foreach ($assets as $asset)
    {                
        ossim_valid($asset, OSS_IP_ADDRCIDR, 'illegal:' . _('Asset'));
            
        if (ossim_error())
        {
            $data['status']  = 'error';
			$data['data']    = "<div style='text-align: left; padding: 0px 0px 3px 10px;'>"._('We found the following errors').":</div>
						<div class='error_item'>".ossim_get_error_clean()."</div>";
	
			echo json_encode($data);
			exit();
		}
        else
        {
            if (!preg_match('/\/\d{1,2}$/', $asset))
            {
                $aux[] = $asset . '/32';
            }
            else 
            {
                $aux[] = $asset;
            }
        }
    }
}


$assets_string .= implode(' ', $aux);

$db->close();

$assets        = $assets_string;
$scan_path_log = "/tmp/nmap_scanning_".md5(Session::get_secure_id()).'.log';

// Only Stop
if ($only_stop) 
{
	$scan = new Scan();
	$scan->stop();
	
	$data['status'] = 'OK';
	$data['data']   = NULL;
	
	echo json_encode($data);
	exit();
}

// Launch scan
if (!$only_status && !$only_stop) 
{
	// This object is only for checking available sensors
	
    $rscan         = new Remote_scan($assets, ($scan_mode == 'full') ? 'root' : 'ping');
    
    $available     = $rscan->available_scan(preg_match('/^[0-9A-F]{32}$/i', $sensor) ? $sensor : '');
		
    $remote_sensor = "null"; // default runs local scan
    
    unset($_SESSION['_remote_sensor_scan']);

    if (preg_match('/[0-9A-F]{32}/i', $sensor)) //Selected sensor
	{ 
        if ($available == '') // Not available remote scans, runs local
		{ 
            $remote_sensor = 'null';
           			
			$data['status']  = 'warning';
			$data['data']    = _('Warning! The selected sensor is not available for remote scan. Using automatic option...');
		} 
		else //Runs remote
		{   
            $remote_sensor = $sensor;
            $_SESSION['_remote_sensor_scan'] = $sensor;
        }
    }

    if ($sensor == 'auto' && $available != '') // runs auto select
	{ 
        $remote_sensor = $available;
        $_SESSION['_remote_sensor_scan'] = $available;
    }
		
    $scan = new Scan();
	$scan->delete_data();
		
    // Launch scan in background

	$cmd = "/usr/bin/php /usr/share/ossim/scripts/vulnmeter/remote_nmap.php '$assets' '$remote_sensor' '$timing_template' '$scan_mode' '" . Session::get_session_user() . "' '$autodetect' '$rdns' '$custom_ports' > $scan_path_log 2>&1 &";
	
	system($cmd);	
}

session_write_close();

echo json_encode($data);
exit();
?>