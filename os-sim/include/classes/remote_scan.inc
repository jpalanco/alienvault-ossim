<?php
/**
* remote_scan.inc
*
* File remote_scan.inc is used to:
*   - To manage remote Nmap scans
*
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


/**
* Remote_scan Class
*
* Class to manage remote Nmap scans
*
* @package    ossim-framework\Asset
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ Alienvault web page
*/

class Remote_scan 
{   
    
    /**
    * Networks and hosts for frameworkd
    *
    * @var string
    * @access private
    */
    private $targets;

    /**
    * Array to resolve IPs and IDs
    *
    * @var string
    * @access private
    */
    private $sensor_cache;
    
    /**
    * Nmap scan type
    *
    * @var string
    * @access private
    */
    private $type;
    
    /**
    * Scan sensor IP
    *
    * @var string
    * @access private
    */
    private $id;
    
    /**
    * Frameworkd error
    *
    * @var string
    * @access private
    */
    private $errmsg;
    
    /**
    * Frameworkd IP
    *
    * @var string
    * @access private
    */
    private $address;
    
    /**
    * Frameworkd Port
    *
    * @var string
    * @access private
    */
    private $port;
    
    /**
    * Scan results
    *
    * @var string
    * @access private
    */
    private $scan;
    
    /**
    * Available sensors
    *
    * @var string
    * @access private
    */
    private $allids;    
       
    /**
    * Socket configuration
    *
    * @var array
    * @access private
    */
    private $timeout;
    
    /**
    * Timing template option
    *
    * @var string
    * @access private
    */
	private $timing_template;
	
    /**
    * OS detection and version detection option
    *
    * @var string
    * @access private
    */
	private $autodetect;
	
    /**
    * Reverse DNS option
    *
    * @var string
    * @access private
    */
	private $rdns;

    /**
    * Ports to scan
    *
    * @var string
    * @access private
    */
	private $ports;
	
    /**
    * Prefix to select last report
    *
    * @var string
    * @access private
    */
	private $prefix;
	
    /**
    * Notification configuration
    *
    * @var array
    * @access private
    */
    private $config_nt;
    
    /**
    * Nmap parser object
    *
    * @var object
    * @access private
    */
    private $nmap_parser;
    
    /**
    * File with Nmap results
    *
    * @var string
    * @access public
    */
    public  $nmap_completed_scan;
    
    
    /**
    * Class constructor
    *
    * This function sets up the class
    *
    * @param string  $targets          Networks and hosts to be scanned
    * @param string  $type             Scan type
    * @param string  $id               Sensor ID or Sensor IP
    * @param string  $scan_path        File with Nmap results
    * @param string  $timing_template  Timing template option
    * @param string  $autodetect       OS detection and version detection option
    * @param string  $rdns             Reverse DNS option
    * @param string  $ports            Ports to scan
    */
    public function __construct($targets, $type, $id = '', $user = '', $timing_template = 'T3', $autodetect = 'enable', $rdns = '1', $ports = '')
    {
    
        /* network to scan */
        $this->targets         = preg_replace('/\s*\,\s*/', ' ', $targets);
        $this->type            = $type;
        $this->autodetect      = $autodetect;
        $this->rdns            = $rdns;
        $this->ports           = $ports;
        $this->timing_template = str_replace('-',' ', $timing_template);
        $this->prefix          = md5(time());        
        
        $db   = new ossim_db();
        $conn = $db->connect();
        
        $all_sensors = Av_sensor::get_list($conn);
        
        $db->close();
        
        // create hash to resolve IPs and IDs
        foreach ($all_sensors[0] as $sensor_id => $sensor_data)
        {
            $this->sensor_cache[$sensor_data['ip']] = $sensor_id; 
            $this->sensor_cache[$sensor_id]         = $sensor_data['ip'];
        }
        
        // the id variable must be a IP
        if ($id != '')
        {
            $id = strtoupper($id);
            
            if (!preg_match('/^\d+\.\d+\.\d+\.\d+$/', $id) && isset($this->sensor_cache[$id]))
            {
                $this->id = $this->sensor_cache[$id];
            }
            else
            {
                $this->id = $id;
            }
        }
        
        $this->nmap_parser = new Nmap_parser();

        $this->errmsg = '';

        $this->config_nt = array(
            'options' => array (
                'type'          => 'nf_warning',
                'cancel_button' => FALSE),
                'style'         => 'width: 80%; margin: 20px auto; text-align: left;'); 

        if (!$GLOBALS['CONF'])
        {
           $GLOBALS['CONF'] = new Ossim_conf();
        }
	    
	    $ossim_conf = $GLOBALS['CONF'];
	    
	    /* get the port and IP address of the frameworkd */
	   
	    $this->address = $ossim_conf->get_conf('frameworkd_address');
	    
	    $this->port    = $ossim_conf->get_conf('frameworkd_port');
	    
	    $this->timeout = array('sec' => 5, 'usec' => 0);
	    
	    $this->secure_id_md5 = ($user != '') ? md5(Session::get_secure_id($user)) : Util::uuid();
        $this->xml_output    = '/var/tmp/nmap_scan_' . $this->secure_id_md5 . '.xml';
    }
    
    
    /**
    * This function returns the available sensor
    *
    * @param string $sensor_id  [Optional] Sensor ID
    *
    * @return string
    */
    public function available_scan($sensor_id = '')
    {
    	$max_agents = 50;
        $this->allids = array();
    	// try to connect with frameworkd and "getconnectedagents"
	    $socket = socket_create(AF_INET, SOCK_STREAM, 0);
	    if ($socket < 0)
	    {
            $this->config_nt['content'] = _("Can't connect with frameworkd") . ": " . socket_strerror(socket_last_error($socket));
        	$nt = new Notification('nt_1', $this->config_nt);
            $nt->show();
	        $this->errmsg = _("Can't connect with frameworkd") . ": " . socket_strerror(socket_last_error($socket));
	        return '';
	    }
	    /* connect */
	    socket_set_block($socket);	    
		socket_set_option($socket,SOL_SOCKET,SO_RCVTIMEO,$this->timeout);
		socket_set_option($socket,SOL_SOCKET,SO_SNDTIMEO,$this->timeout);
	    $result = @socket_connect($socket, $this->address, $this->port);	    
	    if (!$result)
	    {
            $this->config_nt['content'] = _("Can't connect with frameworkd") . " ($this->address:$this->port)";
        	$nt = new Notification('nt_1', $this->config_nt);
            $nt->show();
	        $this->errmsg = _("Can't connect with frameworkd") ." ($this->address:$this->port)";
	        return '';
	    }
	    /* get total agents */
    	$in = 'control action="gettotalagentsconnected"' . "\n";
	    $out = '';
	    socket_write($socket, $in, strlen($in));
	    $out = @socket_read($socket, 5120, PHP_BINARY_READ);
	    if (!preg_match("/ack(end)?$/",$out))
	    {
	        echo _("Bad response from frameworkd").": $out";
	    	$this->errmsg = _("Bad response from frameworkd").": $out";
	        return '';
	    }
		preg_match('/gettotalagentsconnected count\=\"(\d+)\"/',$out,$found);
		if (count($found)<1)
		{
	        $this->errmsg = _("Bad response from frameworkd") . ": $out";
	        return '';
	    }
	    if ($found[1] < 1)
	    {
	    	$this->errmsg = _("No agents found from frameworkd") . ": $out";
	        return '';
	    }
	    $total = $found[1];
	    $first = TRUE;
	    $agents = array();
	    /* get agent lists with pagination */
	    for($begin = 0; $begin < $total-1 || $first; $begin += $max_agents)
	    {
	    	$in = 'control action="getconnectedagents_p" begin_index="'.(($begin > 0) ? $begin + 1 : $begin).'" end_index="'.($begin + $max_agents).'"' . "\n";
	    	$out = '';
	    	// Save all names and check $network perms
	    	$names = array();	    	
		    socket_write($socket, $in, strlen($in));
		    $out = @socket_read($socket, 5120, PHP_BINARY_READ);
		    if (!preg_match("/ack(end)?$/",$out))
		    {
		        $this->errmsg = _("Bad response from frameworkd").": $out";
		        return '';
		    }
			preg_match('/names=\"(.*?)\"/',$out,$found);
			if (count($found) < 2)
			{
		        $this->errmsg = _("Bad response from frameworkd").": $out";
		        return '';
		    }
		    $found[1] = explode('|', $found[1]);
	    	foreach($found[1] as $a)
	    	{
	    	    $data = explode('=', $a);

	    		$agents[] = $data[0];                              //sensor_name=sensor_ip
	    		$names[$this->sensor_cache[$data[1]]] = $data[0];  // the keys are the ids
	    	}
		    $first = FALSE;
	    }
	    socket_close($socket);
		
		$db   = new ossim_db();
		$conn = $db->connect();
		
		
		// Check if this network sensor is available for remote scan
		if ($this->targets == '')
		{ // no asset selected
			if (Session::am_i_admin())
			{
				$this->allids = $names;
			}
			else
			{
				if( !is_array($_SESSION['_user_vision']) || !array_key_exists('sensor',$_SESSION['_user_vision']) )
				{
					$this->allids = array();
					return '';
				}
				else 
				{
					foreach($names as $sk => $sv)
					{
						if(!array_key_exists($sk, array_flip(array_keys($_SESSION['_user_vision']['sensor']))))
						{
							unset($names[$sk]);
						}
					}
					
					$this->allids = $names;
					return '';
				}
			}
		}

        $sa = array();
        
        if (Session::allowedSensors() == '')
        {
            $sa = array_keys($names);
        }
        else
        {
            $sa = explode(',', Session::allowedSensors());
        }
        
        //Assets are separated by spaces
        $assets = explode(' ', $this->targets);
        
        $related_sensors = array();

        foreach($assets as $asset)
        {   //get related sensors
        
            if (preg_match('/(.*)#\d+\.\d+\.\d+\.\d+\/(\d+)/', $asset, $fnd) && $fnd[2] != '32')
            {   
                // NET_ID#DIDR
                
                $related_sensors = array_merge($related_sensors, Asset_net_sensors::get_sensors_by_id($conn, $fnd[1]));
            }
            else if (preg_match('/(.*)#\d+\.\d+\.\d+\.\d+\/(\d+)/', $asset, $fnd) && $fnd[2] == '32')
            {   
                // HOST_ID#DIDR
                
                $related_sensors = array_merge($related_sensors, Asset_host_sensors::get_sensors_by_id($conn, $fnd[1]));
            }
            else if (preg_match('/^(\d+\.\d+\.\d+\.\d+)(\/\d+)?$/', $asset, $fnd) && ($fnd[2] == '/32' || $fnd[2] ==''))
            {  
                // only IP
                
                $related_sensors = array_merge($related_sensors, Asset_host_sensors::get_sensors_by_ip($conn, $fnd[1]));
            } 
        }

        if (empty($related_sensors))
        {
            $related_sensors = $sa;
        }
        else
        {
            $related_sensors = array_keys($related_sensors);
        }
        
        $related_sensors = array_unique($related_sensors);
	
		$sensors = array_intersect($related_sensors, array_keys($names));
		
		if (count($sensors) < 1)
		{
	    	$db->close();
	    	
	        $this->errmsg = _('Network sensors are not available for remote scan');
	        
	        return '';
	    }
		
	    // If sensor passed by argument check this
	    if ($sensor_id != '' && $sensor_id != 'auto') 
		{
	    	$arg_sensor_is_available = FALSE;
	    	
			foreach ($sensors as $sensor)
			{
	    		if ($sensor == $sensor_id) 
	    		{
	    			$this->id = $sensor;
	    			$arg_sensor_is_available = TRUE;
	    		}
	    	}
	    	
			if (!$arg_sensor_is_available)
			{
	    		$db->close();
	    		
	        	$this->errmsg = _('The selected sensor is not available for remote scan');
	        	
	        	return '';
	    	}	
	    } 
		else
		{
		    // use the first available agent id to scan
		    
	    	$this->id = array_shift(array_values($sensors));
	    }
		
	    $this->allids = $names;

	    $db->close();
	    
		return $this->id;
    }


    /**
    * This function returns the available sensors
    *
    * @return array
    */ 
    public function get_sensors()
    { 
        return $this->allids;
    }
    
    
    /**
    * This function launches a scan and retrieves the results
    *
    * @param boolean $quiet  [Optional] Show results on screen
    *
    * @return void
    */
    public function do_scan($quiet = TRUE)
    {
    	session_write_close();
    	
    	$this->scan = '';
    	
    	// prepare to launch nmap
		$socket = socket_create(AF_INET, SOCK_STREAM, 0);
		
	    if ($socket < 0)
	    {
	        $this->errmsg = _("Can't connect with frameworkd").": ". socket_strerror(socket_last_error($socket));
	        return FALSE;
	    }
	    
	    /* connect */
	    socket_set_block($socket);
		socket_set_option($socket,SOL_SOCKET,SO_RCVTIMEO,$this->timeout);
		socket_set_option($socket,SOL_SOCKET,SO_SNDTIMEO,$this->timeout);
	    $result = @socket_connect($socket, $this->address, $this->port);
	    
	    if (!$result)
	    {
	        $this->errmsg = _("Can't connect with frameworkd")." ($this->address:$this->port)";
	        
	        return FALSE;
	    }
	    //
	    // launch nmap
	    // control action="nmap_scan" id="192.168.12.160" type="full" target="192.168.12.160/32" timming="T2" autodetect="enable"
        
	    $in = 'control action="nmap_scan" report_prefix="'.$this->prefix.'" id="'.$this->id.'" type="'.$this->type.'" target="'.$this->targets.'"';
        
        if ($this->timing_template != '') 
        {
            $in .= ' timming="'.$this->timing_template.'"';
        }
	    
	    $in .= ($this->autodetect) ? ' autodetect="enabled"' : ' autodetect="disabled"';
	    $in .= ($this->rdns) ? ' rdns="enable"' : ' rdns="disable"';
    	
    	if ($this->type == "custom" && $this->ports != '') 
    	{
	    	$in .= ' scan_ports="'.$this->ports.'"';
	    }
	    $in .= "\n";
	    	   	    
	    $out = '';
	    socket_write($socket, $in, strlen($in));
	    $out = @socket_read($socket, 2048, PHP_BINARY_READ);

	    if (!preg_match("/ack(end)?$/",$out))
	    {
	        $this->errmsg = _("Bad response from frameworkd").": $in >> $out";
	        
	        return FALSE;
	    }
	    
	    // Query until finish nmap job
	    $in  = 'control action="nmap_status" id="'.$this->id.'"' . "\n";
	    $out = '';
	   
	    $status = 1;
	    
	    if (!$quiet)
	    {
            echo _("Waiting remote scan status\n");
	    }
	    
		while ($status > 0) 
		{
	    	if (!$quiet)
	    	{
				echo " .";
			}
			
		    socket_write($socket, $in, strlen($in));
		    $out = socket_read($socket, 2048, PHP_BINARY_READ);
						
		    if ( !preg_match('/ack(end)?$/',$out) ) 
			{
		        $this->errmsg = _('Bad response from frameworkd').": $out";
		        return FALSE;
		    }
			
		    preg_match('/status=\"(.*?)\"/', $out, $found);
		    
		    $status=$found[1];
		    
		    sleep(10); // 10 seconds between requests
		}
		
		if (!$quiet)
		{ 
			echo '<br/><br/>';
		}
			
		// get scans with nmap_report_list
	    $in  = 'control action="nmap_report_list" id="'.$this->id.'"' . "\n";
	    $out = '';
	    socket_write($socket, $in, strlen($in));
        
        $exit = FALSE;
	    while ( !$exit && ($out = @socket_read($socket, 2048, PHP_BINARY_READ)) !== FALSE ) 
		{
	        $report_list .= $out;
            if (preg_match('/ackend$/',rtrim($out))) 
            {
				$exit = TRUE;
			}
        }
        
	    if (!preg_match('/ack(end)?$/',$report_list))
	    {
	        $this->errmsg = _("Bad response from frameworkd").": $in >> $report_list";
	        
	        return FALSE;
	    }

	    // select last report
		preg_match_all('/report=\"(.*?)\"/',$report_list,$found);
		
		foreach ($found[1] as $rpt) 
		{
			if ( preg_match("/".$this->prefix."/", $rpt) )
			{
				$report = $rpt;
			}
		}
				
		//
		// request report results
		//
        //echo 'control action="nmap_report_raw_get" id="'.$this->id.'" path="'.$report.'"' . "\n"; // DEBUG report_prefix="'.$this->prefix.'" 
	    $in         = 'control action="nmap_report_raw_get"  id="'.$this->id.'" path="'.$report.'"' . "\n";
		
						
	    $out        = '';
        $xml_output = '';
        $output     = '';
        $exit       = FALSE;
        
	    socket_write($socket, $in, strlen($in));
        
	    while (!$exit && ($out = @socket_read($socket, 2048, PHP_BINARY_READ)) !== FALSE) 
		{
	        $output .= $out;
            if ( preg_match('/ackend$/',rtrim($out)) )
            {
                $exit=TRUE;
            }
        }
        
        //delete remote scan
        $this->delete_scan($report);
        
        $output = trim($output);

        if (!preg_match('/ack(end)?$/',$output))
        {
            $this->errmsg = _("Bad response from frameworkd").": $output";

            return FALSE;
        }

        $lines = explode("\n", $output);

        foreach($lines as $line)
        {
            if(preg_match('/line=\"(.*)\"/',$line,$found))
            {
                $data     = $found[1];
                $data_bin = Util::hex2bin($data);

                $xml_output .= trim(gzuncompress($data_bin))."\n";
            }
        }

        /* Add sensor information to scan result (Necessary for setting correct cxt to insert new hosts)*/
        if (!empty($xml_output))
        {
            $xml = @simplexml_load_string($xml_output);
            $xml->addAttribute('av_sensor', $this->sensor_cache[$this->id]);
            $xml_output = $xml->asXML();
        }

        $this->scan = $xml_output;

        //Show results

        if (!$quiet)
        {
            $this->print_scan();
            $this->save_scan();
        }

        socket_close($socket);
    }


    /**
    * This function returns the scan reports
    *
    * @return array
    */
    public function get_scans()
    {
    	// prepare to launch nmap
		$socket = socket_create(AF_INET, SOCK_STREAM, 0);
	    if ($socket < 0)
	    {
	        $this->errmsg = _("Can't connect with frameworkd").": ". socket_strerror(socket_last_error($socket));
	        
	        return FALSE;
	    }
	    
	    /* connect */
		socket_set_block($socket);
		socket_set_option($socket,SOL_SOCKET,SO_RCVTIMEO,$this->timeout);
		socket_set_option($socket,SOL_SOCKET,SO_SNDTIMEO,$this->timeout);
	    $result = @socket_connect($socket, $this->address, $this->port);
	    
	    if (!$result)
	    {
	        $this->errmsg = _("Can't connect with frameworkd")." ($this->address:$this->port)";
	        
	        return FALSE;
	    }
	    
	    /* send nmap_report_list for each id */
	    $reports = array();
	    
	    foreach($this->allids as $idip => $id) 
	    {
    	    if (trim($id) != '')
    	    {
    		    $idip = $this->sensor_cache[$idip]; // from Sensor id to Sensor ip
                $in = 'control action="nmap_report_list" id="'.$idip.'"' . "\n";
    
    		    $out    = '';
                $output = '';
    		    socket_write($socket, $in, strlen($in));
    		    
                $exit   = FALSE;
    
                while (!$exit && ($out = @socket_read($socket, 2048, PHP_BINARY_READ)) !== FALSE)
                {
                    $output .= $out;
                    if (preg_match('/ackend$/',rtrim($out)))
                    {
                        $exit = TRUE;
                    }
                }
                
    		    if (!preg_match('/ack(end)?$/', $output))
    		    {
    		        $this->errmsg = _("Bad response from frameworkd").": $output";
    		        
    		        return FALSE;
    		    }
    		    preg_match_all('/report=\"(.*?)\"/', $output,$found);
    		    $idip = $this->sensor_cache[$idip]; // from Sensor ip to Sensor id
    		    $reports[$id . '|' . $idip] = $found[1];
    		}
		}
		
		socket_close($socket);
	    
	    return $reports;
    }


    /**
    * This function imports a report
    *
    * @param string  $report  Report path
    *
    * @return void
    */
	public function import_scan($report)
	{
    	$this->scan = '';

		$socket = socket_create(AF_INET, SOCK_STREAM, 0);
	    if ($socket < 0)
	    {
	        $this->errmsg = _("Can't connect with frameworkd").": ". socket_strerror(socket_last_error($socket));
	        
	        return FALSE;
	    }
	    
	    /* connect */
	    socket_set_block($socket);
		socket_set_option($socket,SOL_SOCKET,SO_RCVTIMEO,$this->timeout);
		socket_set_option($socket,SOL_SOCKET,SO_SNDTIMEO,$this->timeout);
	    $result = @socket_connect($socket, $this->address, $this->port);
	    
	    if (!$result)
	    {
	        $this->errmsg = _("Can't connect with frameworkd")." ($this->address:$this->port)";
	        
	        return FALSE;
	    }
		
		//
		// request report results
		//
	    $in         = 'control action="nmap_report_raw_get" id="'.$this->id.'" path="'.$report.'"' . "\n";
	    $out        = '';
        $xml_output = '';
        $output     = '';
        $exit       = FALSE;
        
	    socket_write($socket, $in, strlen($in));
        
	    while (!$exit && ($out = @socket_read($socket, 2048, PHP_BINARY_READ)) !== FALSE)
	    {
	        $output .= $out;
            if (preg_match('/ackend$/',rtrim($out)))
            {
                $exit = TRUE;
            }
        }
        
        $output = trim($output);
        
        if (!preg_match('/ack(end)?$/', $output))
        {
            $this->errmsg = _("Bad response from frameworkd").": $output";
            return FALSE;
        }

        $lines = explode("\n", $output);
        
        foreach($lines as $line)
        {
            if(preg_match('/line=\"(.*)\"/',  $line, $found))
            {
                $data     = $found[1];
                $data_bin = Util::hex2bin($data);

                $xml_output .= trim(gzuncompress($data_bin))."\n";
            }
        }

        $this->scan = $xml_output;
		        
		// show results
		$this->print_scan();
		
		socket_close($socket);
    }
    

    /**
    * This function deletes a report
    *
    * @param string  $report  Report path
    *
    * @return void
    */
    public function delete_scan($report)
    {
    	// prepare to launch nmap
		$socket = socket_create(AF_INET, SOCK_STREAM, 0);
	    if ($socket < 0)
	    {
	        $this->errmsg = _("Can't connect with frameworkd").": ". socket_strerror(socket_last_error($socket));
	        
	        return FALSE;
	    }
	    
	    /* connect */
		socket_set_block($socket);	    
		socket_set_option($socket,SOL_SOCKET,SO_RCVTIMEO,$this->timeout);
		socket_set_option($socket,SOL_SOCKET,SO_SNDTIMEO,$this->timeout);
	    $result = @socket_connect($socket, $this->address, $this->port);
	    if (!$result)
	    {
	        $this->errmsg = _("Can't connect with frameworkd")." ($this->address:$this->port)";
	        
	        return FALSE;
	    }
        
	    $in  = 'control action="nmap_report_delete" id="'.$this->id.'" path="'.$report.'"' . "\n";
	    $out = '';
	    socket_write($socket, $in, strlen($in));
	    $out = @socket_read($socket, 2048, PHP_BINARY_READ);
	    
	    if (!preg_match('/ack(end)?$/', $out))
	    {
	        $this->errmsg = _("Bad response from frameworkd").": $out";
	        
	        return FALSE;
	    }
	    
		socket_close($socket);
    }
    
    
    /**
    * This function prints the scan results
    *
    * @return void
    */
	public function print_scan()
	{      
	    $results = $this->nmap_parser->parse_xml($this->scan);
 
       foreach($results['scanned_ips'] as $host)
        {         
            $ip = $host['ip'];
            
            if (trim($ip) == '')
            {
                continue;
            }
            
            $resolv_ip = gethostbyaddr($ip);
            
            echo '<img src="../pixmaps/arrow-315-small.png" align="absmiddle"/>';
            echo "<b>$ip";
            
            if ($ip != $resolv_ip) 
            {
                echo " ($resolv_ip)";
            }
            
            echo '</b>.. <br/>\n';
	        
	        if (isset($host['os']))
	        {
	            echo '&nbsp;&nbsp;&nbsp;&nbsp;';
	            echo 'OS: ' . $host['os'];
	            echo '<br/>';
	        }
	
	        if (isset($host['mac']))
	        {
	            echo '&nbsp;&nbsp;&nbsp;&nbsp;';
	            echo 'MAC: ' . $host['mac'].' '.($host['mac_vendor']);
	            echo '<br/>';
	        }
	        if (isset($host['services']))
	        {
	            echo '&nbsp;&nbsp;&nbsp;&nbsp;';
	            echo 'Services: ';
	            foreach ($host['services'] as $k => $service) 
	            {
	                echo $k . '&nbsp;';
	            }
	            echo '<br/>';
	        }
	        
	        echo '<hr/>';
        }
	}
	
	
    /**
    * This function returns the last Frameworkd error
    *
    * @return string
    */
    public function err()
    { 
    	return $this->errmsg;
    }
    
	
    /**
    * This function returns the last Frameworkd error
    *
    * @return string
    */
    public function save_scan() 
	{
        $_SESSION['_scan'] = $this->scan;                    
          	
    	file_put_contents($this->xml_output, $this->scan); 
    }
    
	
	/**
	* This function returns the scan results
	*
    * @return array
	*/
    public function get_scan()
    {
        if (isset($_SESSION['_scan']))
        {
            $data = $_SESSION['_scan'];
        }
		else
		{
            $data = $this->scan;
        }
        
        return $this->nmap_parser->parse_xml($data);
    }
    
    
	/**
	* This function deletes the scan results
	*
	* @return void
	*/
	public function del_scan()
	{
        if (isset($_SESSION['_scan']))
        {
            unset($_SESSION['_scan']);
        }
        
        if (file_exists($this->xml_output))
    	{
            @unlink($this->xml_output);
        }
    }
}

/* End of file remote_scan.inc */
/* Location: ../include/classes/remote_scan.inc */