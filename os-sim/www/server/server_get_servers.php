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


function check_server() 
{        
    $ossim_conf = $GLOBALS['CONF'];
    
    if (!$ossim_conf)
    {
        $ossim_conf      = new Ossim_conf();
        $GLOBALS['CONF'] = $ossim_conf;
    }
    
    
    /* get the port and IP address of the server */
    $address = $ossim_conf->get_conf('server_address');
    $port    = $ossim_conf->get_conf('server_port');
	
    /* create socket */
    $socket = socket_create(AF_INET, SOCK_STREAM, 0);
    
    if ($socket < 0) 
    {
        echo _('socket_create() failed: reason: '). socket_strerror($socket) . "\n";
    }
    
    /* connect */
    $result = @socket_connect($socket, $address, $port);
    
    if (!$result) 
    {
        return FALSE;
    }
    
    return TRUE;
}


function server_get_servers($server_list) 
{
	$active_servers = 0;
	
	$total_servers  = 0;
	
	if ($server_list) 
	{
		$total_servers = count($server_list);
		
		foreach($server_list as $server) 
		{
			$ip   = $server->get_ip();
			$port = $server->get_port();
			
			$output = array();
			exec("echo 'connect id=\"1\" type=\"web\"' | nc $ip $port -w1", $output);
			
			if(is_array($output) && strncmp('ok id="1"', $output[0], 9) == FALSE)
			{
				$active_servers++;
			}			
		}		
	}

	return array($total_servers, $active_servers);
}


/* End of file server_get_servers.php */
/* Location: ../server/server_get_servers.php */