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


function get_nfsen_sensors() 
{
    include '/usr/share/ossim/www/nfsen/conf.php';

    $lines   = @file($nfsen_conf);
    $sensors = array();

    if($lines) 
    {
        foreach ($lines as $line) 
        {
            if (preg_match("/\s*^\#/",$line)) continue;

            if (preg_match("/'([^']+)'\s+\=\>\s*\{\s*'port'\s+\=\>\s+'(\d+)',\s+'col'\s+=>\s+'(\#......)',\s+'type'\s+=>\s+'([^']+)'/",$line,$found)) 
            {
                $sensors[$found[1]]['port'] = $found[2];
                $sensors[$found[1]]['color'] = $found[3];
                $sensors[$found[1]]['type'] = $found[4];
            }
        }
    }

    return $sensors;
}


function set_nfsen_sensors($sensors) 
{
    include '/usr/share/ossim/www/nfsen/conf.php';
    
    $lines     = file($nfsen_conf);
    $newlines  = array();
    $insources = FALSE;

    unset($_SESSION['tab']);

	foreach ($lines as $line) 
    {
		if (preg_match("/\s*^\#/",$line)) 
        { 
            $newlines[] = $line; 
            continue; 
        }

		if (!$insources && preg_match("/\%sources \= \(/",$line)) 
        {
			$newlines[] = $line;
			$insources  = TRUE;
		} 
        elseif ($insources && preg_match("/\)\;/",$line)) 
        {
			$coma = "";
			foreach ($sensors as $sensor => $data) 
            {
				$newlines[] = "$coma    '".$sensor."'    => { 'port' => '".$data['port']."', 'col' => '".$data['color']."', 'type' => '".$data['type']."' }\n";
				$coma       = ",";
			}

			$insources  = FALSE;
			$newlines[] = ");\n";
		} 
        elseif (!$insources) 
        {
			$newlines[] = $line;
		}
	}
	
	$f = fopen($nfsen_conf,"w");
	
	foreach ($newlines as $line)
    {
        fputs($f,$line);
    }

	fclose($f);
}


function is_running($sensor_id) 
{
    include '/usr/share/ossim/www/nfsen/conf.php';
    
    $cmd    = "sudo ? status 2>>/dev/null";
    $output = Util::execute_command($cmd, array($nfsen_bin), 'array');
    
    $lines = '';
    
    foreach ($output as $line) 
    {
        if (preg_match("/$sensor_id/", $line)) 
        {            
            $lines .= $line."\n";
        }
    }
	
	$data['status'] = 'success';
	$data['data']   = $lines;
	
	return $data;
}


function reconfig_system()
{
    $uuid = Util::get_default_uuid();
    
    $data['status'] = 'error';
    $data['data']   = _('Error! It was not possible to apply the Netflow configuration.'); 
    
    if ($uuid !== FALSE)
    {
        //If we find a job id, then we try to retrieve the status of the job
        $client = new Alienvault_client();

        $response = $client->server()->nfsen_reconfig();
        $response = @json_decode($response, TRUE);
        
        //Comunication problem with the API. Error
        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $client->get_error_message($response);
            $data['status'] = 'error';
            $data['data']   = _('Error! Netflow Reconfig was not executed due to an API error.') . ' (' . $exp_msg . ')';
        }
        else
        {
            $data['status'] = 'success';
            $data['data']   = '';    
        }
    }

    return $data;
}


function nfsen_start() 
{   
    include '/usr/share/ossim/www/nfsen/conf.php';
    
    $lines = array();
    //Stopping NfSen
	Util::execute_command('sudo ? stop', array($nfsen_bin));
	//Starting NfSen
	try
	{
	    Util::execute_command('sudo ? start 2>&1', array($nfsen_bin), 'array'); // Array mode to check return value of exec()
	    
	    $data['status'] = 'success';
	    $data['data']   = _('Netflow restarted successfully.');
	}
	catch(Exception $e)
	{
	    $data['status'] = 'error';
	    $data['data']   = _('Netflow restart failed.');
	}
	
	return $data;
}


function nfsen_reset() 
{
    include '/usr/share/ossim/www/nfsen/conf.php';
    
    $cmd = 'echo y | sudo ? reconfig > /var/tmp/nfsen.log 2>&1';
	
	Util::execute_command($cmd, array($nfsen_bin));
}


function get_nfsen_baseport($sensors) 
{
    $base_port = 12000;    
    $ports     = array();

    foreach ($sensors as $data) 
    {
        $ports[$data['port']]++; // load used ports in configuration file
    }
    
    ksort($ports);
    $found = FALSE;
    
    while($found === FALSE) // we select the first unused port
    { 
        if($ports[$base_port] == '') 
        {
            $found = TRUE;
        }
        else 
        {
            $base_port++;
        }
    }

    return $base_port;
}


function delete_nfsen($sensor, $nfsen_list = array())
{
    if(empty($nfsen_list))
    {
        $nfsen_list = get_nfsen_sensors();
    }

    if(count($nfsen_list) <= 1)
    {        
        $data['status'] = 'error';
        $data['data']   = _('You cannot delete this source, at least one Netflow source is needed');        
    }    
    elseif ($nfsen_list[$sensor] != '') 
    {
        unset($nfsen_list[$sensor]);
        set_nfsen_sensors($nfsen_list);
        nfsen_reset($nfsen_dir);        
        
        // Talk to frameworkd
        try
        {
            $s = new Frameworkd_socket();
            $s->write('nfsen action="delsensor" sensorname="'.$sensor.'"');
            
            $data['status'] = 'success';
            $data['data']   = _('Netflow sensor deleted successfully');
            
        }
        catch(Exception $e)
        {                     
            $data['status'] = 'error';
            $data['data']   = $e->getMessage();            
        }                   
    }

    return $data;
}
