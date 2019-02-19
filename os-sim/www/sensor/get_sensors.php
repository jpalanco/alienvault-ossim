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


// Get info from server about sensor plugins
function server_get_sensors()
{

    if (file_exists("/usr/share/ossim/scripts/av_web_steward.py"))
    {
    	return server_get_sensors_script();
    }
    else
    {
    	return server_get_sensors_socket();
    }
}


// Get info from server about sensor plugins
function server_get_sensors_script()
{
	$ossim_conf = $GLOBALS['CONF'];

	if (!$ossim_conf)
	{
    	$ossim_conf      = new Ossim_conf();
    	$GLOBALS['CONF'] = $ossim_conf;
	}

	$allowed_sensors = explode (',', Session::allowedSensors());

    /* Get port and IP address */
    $address = '127.0.0.1';
    $port    = $ossim_conf->get_conf('server_port');

    $list = array();
    $tmp  = '/var/tmp/sensor_plugins';

    if (file_exists($tmp))
    {
        @unlink($tmp);
    }

    session_write_close();
        
    $cmd    = '/usr/share/ossim/scripts/av_web_steward.py -r "server-get-sensor-plugins id=\"2\"" -t ?  -s ? -p ?  > /dev/null 2>&1';
    $params = array($tmp, $address, $port);
    Util::execute_command($cmd, $params);
        

    $file = @file($tmp);

    if (empty($file))
    {
        $file = array();
    }

    if (preg_match("/^AVWEBSTEWARD_ERROR:(.*)/", $file[0], $fnd))
    {
    	// Error
        return array($list, '<strong>'._('Communication Failed').'<br/> '._('Reason: ').'</strong>'. $fnd[1]);
    }

    // parse results
    $pattern = '/sensor="([^"]*)" plugin_id="([^"]*)" state="([^"]*)" enabled="([^"]*)"/ ';

    foreach ($file as $line)
    {
    	if (preg_match($pattern, $line, $regs))
        {
			if (in_array($regs[1],$allowed_sensors) || Session::allowedSensors() == '')
            {
                $list[$regs[1]][$regs[2]]['enabled'] = $regs[4];
                $list[$regs[1]][$regs[2]]['state'] = $regs[3];
            }
        }
    }

    return array($list, '');
}


// Get info from server about sensor plugins
function server_get_sensors_socket()
{
	$allowed_sensors = explode(',', Session::allowedSensors());

    $ossim_conf = $GLOBALS['CONF'];

	if (!$ossim_conf)
	{
    	$ossim_conf      = new Ossim_conf();
    	$GLOBALS['CONF'] = $ossim_conf;
	}

    /* get the port and IP address of the server */
    $address = '127.0.0.1';
    $port    = $ossim_conf->get_conf('server_port');

    /* create socket */
    $socket = socket_create(AF_INET, SOCK_STREAM, 0);
    if ($socket < 0)
    {
        return array($list, '<strong>'._('socket_create() failed').'<br/> '._('Reason: ').'</strong>'. socket_strerror($socket));
    }

    $list = array();
    /* connect */
    socket_set_block($socket);
    socket_set_option($socket,SOL_SOCKET,SO_RCVTIMEO, array('sec' => 4, 'usec' => 0));
	socket_set_option($socket,SOL_SOCKET,SO_SNDTIMEO, array('sec' => 4, 'usec' => 0));

    $result = @socket_connect($socket, $address, $port);

    if (!$result)
    {
        $errmsg = sprintf(_("Unable to connect to %s server. Please, wait until it's available again or check if it's running at %s"), Session::is_pro() ? "USM" : "OSSIM", "$address:$port");
        return array($list, $errmsg);
    }

    /* first send a connect message to server */
    $in  = 'connect id="1" type="web"' . "\n";
    $out = '';
    socket_write($socket, $in, strlen($in));
    $out = @socket_read($socket, 2048, PHP_BINARY_READ);

    if (strncmp($out, 'ok id=', 4))
    {
        $errmsg = sprintf(_("Bad response from %s server. Please, wait until it's available again or check if it's running at %s"), Session::is_pro() ? "USM" : "OSSIM", "$address:$port");
        return array($list, $errmsg);
    }

    /* get sensors from server */
    $in     = 'server-get-sensor-plugins id="2"' . "\n";
    $output = '';
    socket_write($socket, $in, strlen($in));

    $pattern = '/sensor="([^"]*)" plugin_id="([^"]*)" state="([^"]*)" enabled="([^"]*)"/ ';
    // parse results
    while ($output = socket_read($socket, 2048, PHP_BINARY_READ))
    {
        $lines = explode("\n", $output);

        foreach ($lines as $out)
        {
	    	if (preg_match($pattern, $out, $regs))
            {
	            //if (Session::hostAllowed($conn, $regs[1])) {
				if (in_array($regs[1],$allowed_sensors) || Session::allowedSensors() == "")
                {
	                $list[$regs[1]][$regs[2]]['enabled'] = $regs[4];
	                $list[$regs[1]][$regs[2]]['state'] = $regs[3];
	            }
	        }
            elseif (!strncmp($out, 'ok id=', 4))
            {
	            break;
	        }
        }
    }
    socket_close($socket);

    return array($list, '');
}


// Deprecated
function server_get_name_byip($ip) 
{
	$ossim_conf = $GLOBALS['CONF'];

	if (!$ossim_conf)
	{
    	$ossim_conf      = new Ossim_conf();
    	$GLOBALS['CONF'] = $ossim_conf;
	}

	$sname = '';

	$frameworkd_address = '127.0.0.1';

	$cmd    = 'echo "control action=\"getconnectedagents\"" | nc '.$frameworkd_address.' 40003 -w1';
	$params = array($frameworkd_address);
	$output = Util::execute_command($cmd, $params, 'array');	

	if (preg_match("/ names\=\"([^\"]+)\"/", $output[0], $found))
	{

		$names = explode('|', $found[1]);

		foreach ($names as $name)
		{
			$aux = explode("=", $name);

			if ($aux[1] == $ip)
			{
				$sname = $aux[0];
			}
		}
	}

	return $sname;
}


function server_get_sensor_plugins($sensor_ip = "")
{
    $ossim_conf = $GLOBALS['CONF'];

	if (!$ossim_conf)
	{
    	$ossim_conf      = new Ossim_conf();
    	$GLOBALS['CONF'] = $ossim_conf;
	}

    /* get the port and IP address of the server */

    $address = '127.0.0.1';
    $port    = $ossim_conf->get_conf('server_port');

    /* create socket */
    $socket = socket_create(AF_INET, SOCK_STREAM, 0);

    if ($socket < 0)
    {
        echo _("socket_create() failed: reason: ") . socket_strerror($socket) . "\n";
    }

    $list = array();

    /* connect */
    socket_set_block($socket);
    socket_set_option( $socket,SOL_SOCKET,SO_RCVTIMEO, array('sec' => 5, 'usec' => 0) );
	socket_set_option( $socket,SOL_SOCKET,SO_SNDTIMEO, array('sec' => 5, 'usec' => 0) );

	$result = @socket_connect($socket, $address, $port);

    if (!$result)
    {
        echo sprintf(_("Unable to connect to %s server. Please, wait until it's available again or check if it's running at %s"), Session::is_pro() ? "USM" : "OSSIM", "$address:$port");
        return $list;
    }

	/* first send a connect message to server */
    $in  = 'connect id="1" type="web"' . "\n";
    $out = '';

    socket_write($socket, $in, strlen($in));
    $out = @socket_read($socket, 2048, PHP_BINARY_READ);

    if (strncmp($out, "ok id=", 4))
    {
        echo sprintf(_("Bad response from %s server. Please, wait until it's available again or check if it's running at %s"), Session::is_pro() ? "USM" : "OSSIM", "$address:$port");
        return $list;
    }

    /* get sensor plugins from server */
    $in  = 'server-get-sensor-plugins id="2"' . "\n";
    $out = '';

    socket_write($socket, $in, strlen($in));

    $pattern = '/sensor="('.str_replace(".","\\.",$sensor_ip).')" plugin_id="([^"]*)" ' . 'state="([^"]*)" enabled="([^"]*)"/';

    while ($output = socket_read($socket, 2048, PHP_BINARY_READ))
	{
		$lines = explode("\n",$output);

        foreach ($lines as $out)
        {
	        if (preg_match($pattern, $out, $regs))
	        {
	            $s['sensor']    = $regs[1];
	            $s['plugin_id'] = $regs[2];
	            $s['state']     = $regs[3];
	            $s['enabled']   = $regs[4];

	            if (!in_array($s, $list))
	            {
	               $list[] = $s;
	            }
	        }
			elseif (!strncmp($out, "ok id=", 4))
			{
	            break;
	        }
        }
    }
    socket_close($socket);

    return $list;
}
?>
