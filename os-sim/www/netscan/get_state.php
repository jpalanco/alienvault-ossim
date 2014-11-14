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

require_once 'av_init.php';

Session::logcheck('environment-menu', 'ToolsScan');

$db   = new ossim_db();
$conn = $db->connect();

$data = array();

if (Scan::scanning_now())
{
	// Scan Status
	$scanning_assets = Scan::scanning_what();

	if (count($scanning_assets) > 0) 
	{	
		foreach ($scanning_assets as $rsensor_ip) // $rsensor_ip = remote_sensor#ip
		{
            $tdata     = explode('#', $rsensor_ip);
			$assets[]  = $tdata[1];
		
		    $sensor_id = $tdata[0];
		}
		
		$sc_asset = implode(', ', $assets);
		
		
		if ($sensor_id != '' && $sensor_id != 'null') // With remote sensor
		{				
			$sensor_ip = Av_sensor::get_ip_by_id($conn, $sensor_id);
		
			$data['state']      = 'remote_scan_in_progress';
	        $data['message']    = sprintf(_('Scanning network: <strong>%s</strong> with a remote sensor [<strong>%s</strong>], please wait...'), $sc_asset, $sensor_ip);
	        $data['progress']   = NULL;
	        $data['debug_info'] = NULL;
		} 
		else  // With local sensor
		{
		    $obj      = new Scan();
		    
		    if ($obj->get_status() == 'Scanning Hosts')
		    {
    		    $data['state'] = 'local_scan_in_progress';
    		    $task          = 'Scanning hosts';
		    }
		    else if ($obj->get_status() == 'Searching Hosts')
		    {
    		    $data['state'] = 'local_search_in_progress';
    		    $task  =  _('Searching hosts'); 
		    }
		    else if ($obj->get_status() == 'Scan Finished')
		    {
    		    $data['state'] = 'finished';
    		    $task  =  _('Scan Finished');
		    }
		    else if ($obj->get_status() == 'Search Finished')
		    {
		        if ($obj->get_only_ping() == FALSE)
		        {
        		    $data['state'] = 'local_search_in_progress';
        		    $task  =  _('Searching hosts');
    		    }
    		    else
    		    {
    		        $data['state'] = 'finished';
    		        $task  =  _('Scan Finished');
    		    }
		    }
            
            if ($data['state'] != 'launching_local_scan')
            {            
                $data['message']    = sprintf(_('%s: <strong>%s</strong> with local sensor, please wait...'), $task, $sc_asset);
    	        $progress   = $obj->get_progress();
    	        
    	        $data['progress']['percent'] = round(($progress['hosts_scanned'] / $progress['total_hosts']) * 100);
    	        $data['progress']['current'] = $progress['hosts_scanned'];
    	        $data['progress']['total']   = $progress['total_hosts'];
    	        
                if ($progress['remaining'] == -1)
                {
                    $data['progress']['time'] = _('Calculating Remaining Time');
                }
                else
                {   
                    $data['progress']['time'] = Welcome_wizard::format_time($progress['remaining']) . ' ' . _('remaining');
                }
            }
            else
            {
                $data['message']    = NULL;
                $data['progress']   = NULL;
                $data['debug_info'] = NULL;    
            }
	                                                                                
	        $data['debug_info'] = NULL;	
		}
	}
}
else 
{
    $scan     = new Scan();

	if (preg_match('/finished/i', $scan->get_status()))
	{	
	    $lastscan = $scan->get_results();
	
		$debug_info = '';
		
		if(is_array($lastscan['nmap_data']) && !empty($lastscan['nmap_data']) )
		{
			$debug_info = $lastscan['nmap_data']['cmd'] . '|' . $lastscan['nmap_data']['version'] . '|' . $lastscan['nmap_data']['xmloutputversion'];
			
			unset($lastscan['nmap_data']);
		}
		
        $data['state']      = 'finished';
        $data['message']    = NULL;
        $data['progress']   = NULL;
        $data['debug_info'] = $debug_info;
        
        if (is_array($lastscan['scanned_ips']) && count($lastscan['scanned_ips']) == 0)
        {
            $scan->delete_data();
        }
    }
	else
	{
            $data['state']      = 'idle';
            $data['message']    = NULL;
	        $data['progress']   = NULL;
	        $data['debug_info'] = NULL;	
	}
}

echo json_encode($data);

$db->close();

?>