<?php
/**
* scan.inc
*
* File scan.inc is used to:
*   - To manage local Nmap scans
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

define('NMAP_SCAN',        '%s -iL %s -oX %s --no-stylesheet %s --stats-every=1s %s');
define('FTW_SCAN_OPTIONS', '-T3 -n -O --osscan-guess --max-os-tries=1 -p21,22,23,25,53,80,113,135,139,443,445,554,3389');

define('NMAP_PING',        '%s -sn -PE -v %s -oX %s --no-stylesheet %s');
define('FTW_PING_OPTIONS', '-T3');

/**
* Scan Class
*
* Class to manage local Nmap scans
*
* @package    ossim-framework\Asset
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ Alienvault web page
*/

class Scan
{    
    /**
    * Networks and hosts for frameworkd
    *
    * @var string
    * @access private
    */
    private $targets;
        
    /**
    * Nmap binary path
    *
    * @var string
    * @access private
    */
    private $nmap;
    
     /**
    * Nmap options
    *
    * @var string
    * @access private
    */
    private $options;
	
     /**
    * Nmap parser object
    *
    * @var object
    * @access private
    */
	private $nmap_parser;
	
     /**
    * Nmap XML output file
    *
    * @var string
    * @access private
    */
	private $xml_output;
	
     /**
    * Nmap targets file
    *
    * @var string
    * @access private
    */
    private $targets_file;
    
     /**
    * Nmap scan status
    *
    * @var string
    * @access private
    */
    private $status;
    
     /**
    * Number of hosts to be scanned
    *
    * @var integer
    * @access private
    */
    private $num_of_host;
    
     /**
    * Array with hosts_statuses
    *
    * @var array
    * @access private
    */
    private $hosts_statuses;
    
     /**
    * If this variable is TRUE we only will do a ping scan
    *
    * @var boolean
    * @access private
    */
    private $only_ping;
      
     /**
    * Class constructor
    *
    * This function sets up the class
    *
    * @param string  $targets    Networks and hosts to be scanned
    * @param array   $data       Array with scan user and ping_only option
    */
    function __construct($targets = '', $config = array())
    {
        // choose files names
	    
	    $user = ($config['user'] != '') ? $config['user'] : Session::get_session_user();
	    $this->secure_id_md5 = ($user != '') ? md5(Session::get_secure_id($user)) : Util::uuid();      

        $this->xml_output    = '/var/tmp/nmap_scan_' . $this->secure_id_md5 . '.xml';
        $this->targets_file  = '/var/tmp/nmap_scan_' . $this->secure_id_md5 . '_targets_file';
        $this->data_file     = '/var/tmp/nmap_scan_' . $this->secure_id_md5 . '_datafile';
    
        // nmap binary
        
        if (!$GLOBALS['CONF'])
        { 
           $GLOBALS['CONF'] = new Ossim_conf();
        }
	    
	    $conf = $GLOBALS['CONF'];
		
        $this->nmap = $conf->get_conf('nmap_path');
        
        // object to parse Nmap results
        
        $this->nmap_parser = new Nmap_parser();
        
        // load parameter from file if targets is empty
        
        if (empty($targets))
        {
            $this->load_data();
        }
        else
        {
            // Host statuses
            $this->hosts_statuses = array();
        
            // Nmap options
	        $this->options = '';
	        
	        // Nmap targets
            $this->targets = preg_replace('/\s*\,\s*/', ' ', $targets);
            
	        // Scan configuration
	        $this->only_ping = ($config['only_ping'] == TRUE) ? TRUE : FALSE;
            
            $this->save_data();
            
            touch($this->targets_file);
        }
        
        // get scan status
        $this->get_status();
    }

    /**
    * This function adds Nmap scan options
    *
    * @param string  $options  Scanning options
    *
    * @return void     
    */
    public function append_option($options)
    {
        if (!empty($this->options))
        {
            $this->options .= " " . $options;
        }
        else
        {
            $this->options = $options;
        }
        
        $this->save_data();
    }
    
    /**
    * This function returns only_ping variable
    *
    * @return boolean     
    */
    public function get_only_ping()
    {   
        return $this->only_ping;
    }
    
    /**
    * This function load the scan data
    *
    * @return  void
    */ 
    private function load_data()
    {
        //Getting the content of the file
        
        if (file_exists($this->data_file))
        {      
            $file_content  = file_get_contents($this->data_file);
            $data          = unserialize($file_content);
            
            $this->options        = $data['options'];
            $this->targets        = $data['targets'];
            $this->only_ping      = $data['only_ping'];
            $this->num_of_host    = $data['num_of_host'];
            $this->hosts_statuses = $data['hosts_statuses'];
        }
        else
        {   
            $this->options        = '';
            $this->targets        = '';
            $this->only_ping      = FALSE;
            $this->num_of_host    = 0;
            $this->hosts_statuses = array();  
        }
    }
    
    /**
    * This function saves the scan data
    *
    * @return  void
    */ 
    private function save_data()
    {
        $data  = array();
        
        $data['options']        = $this->options;
        $data['targets']        = $this->targets;
        $data['only_ping']      = $this->only_ping;
        $data['num_of_host']    = $this->num_of_host;
        $data['hosts_statuses'] = $this->hosts_statuses;
        
        @file_put_contents($this->data_file, serialize($data));
    }
    
    /**
    * This function searches for hosts to scan
    *
    * @return boolean
    */
    public function search_hosts()
    {
        // kill Nmap processes
        
        $this->stop();
        $this->delete_data(FALSE);
    
        $result = FALSE;
        
	    touch($this->targets_file);
	    touch($this->xml_output);	    
	    
        if (!empty($this->options) && $this->only_ping == TRUE) // It is a custom ping scan
        {  
            $command = sprintf(NMAP_PING, $this->nmap, $this->options, $this->xml_output, $this->targets);
	    }
	    else // this scan is used in FTW and in Asset Discovery when the type of scan isn't ping
	    {
    	    $command = sprintf(NMAP_PING, $this->nmap, FTW_PING_OPTIONS, $this->xml_output, $this->targets);
	    }
	    
	    exec('sudo ' . $command . ' > /dev/null 2>&1 &', $output , $result);
        
        if ($result === 0)
        {
            usleep(500000);
        
            $result       = TRUE;
            $this->status = 'Searching Hosts';
        }
        
        return $result;
    }	


    /**
    * This function scans hosts
    *
    * @return boolean
    */
	public function launch_scan()
	{   
        $result = FALSE;
        
        if ($this->num_of_host > 0)
        {
            if (file_exists($this->xml_output))
            {
                @unlink($this->xml_output);
            }
	    
	        touch($this->xml_output);
	        
	        if ($this->num_of_host >= 1 && $this->num_of_host <= 255)
	        {
    	        $max_hostgroup = '--max-hostgroup=4';
	        }
	        else
	        {
    	        $max_hostgroup = '--max-hostgroup=8';
	        }
            
            if (!empty($this->options)) // It is a custom scan
            {
                $command = sprintf(NMAP_SCAN, $this->nmap, $this->targets_file, $this->xml_output, $max_hostgroup, $this->options);
            }
            else // this scan is used in FTW
            {
                $command = sprintf(NMAP_SCAN, $this->nmap, $this->targets_file, $this->xml_output, $max_hostgroup, FTW_SCAN_OPTIONS);
	        }
	 
            exec('sudo ' . $command . ' > /dev/null 2>&1 &', $output , $result);
        
            if ($result === 0)
            {
                usleep(500000);
                
                $result       = TRUE;
                $this->status = 'Scanning Hosts';
            }
        }
        else
        {
            $this->status  = 'Scan Finished';
        }
    
        return $result;
	}
	
	
    /**
    * This function scans stops the scan
    *
    * @return boolean
    */
	public function stop()
	{ 
        $result = FALSE;

        $status = $this->get_status();

        if ($status == 'Scanning Hosts' || $status == 'Searching Hosts')
        {
	        $action = ($status == 'Scanning Hosts') ? 'stop_scan' : 'stop_search';
	        
	        exec('sudo /usr/share/ossim/scripts/stop_nmap.sh ' . $action . ' ' . $this->secure_id_md5, $output , $result);
        
            if ($result === 0)
            {
                $result = TRUE;
            }
	        
	        if ($status == 'Scanning Hosts')
	        {
                $config = array(
                          'indent'     => TRUE,
                          'input-xml'  => TRUE,
                          'output-xml' => TRUE,
                          'wrap'       => FALSE);
                  
                $tidy = new tidy();
        
                $repaired = $tidy->repairfile($this->xml_output, $config);
                
                $this->status = 'Scan Finished';
        
                file_put_contents($this->xml_output, $repaired);	        
	        }
	        else
	        {   
	            $this->status = 'Idle';
                $this->delete_data();
                $this->num_of_host = 0;
	        }
        }
        
        return $result;
	}
	
	
    /**
    * This function returns the scan results
    *
    * @return array
    */
	public function get_results()
	{
	    $attempts = 0;
	     
	    if ($this->xml_formatted() == FALSE && $attempts <=3)
	    {
	        usleep(500000);
            $attempts++;
	    }
	    
	    $repair_file = ($this->xml_formatted() == FALSE) ? TRUE : FALSE;
	
	    $result      = $this->nmap_parser->parse_xml($this->xml_output, $repair_file);
	
            foreach ($result["scanned_ips"] as $ip => $ip_data)
            {
                if (strcasecmp($ip_data['status'], 'up') != 0)
                {
                    unset($result["scanned_ips"][$ip]);
                }
            }
	    
	    return $result;
	}
	
	
    /**
    * This function returns the number of hosts
    *
    * @return integer
    */
	public function get_num_of_hosts()
	{
        return $this->num_of_host;
	}
	
    /**
    * This function returns the hosts statuses
    *
    * @return integer
    */
	public function get_hosts_statuses()
	{
        return $this->hosts_statuses;
	}
	
	
    /**
    * This function returns the scan progress
    *
    * @return array
    */
	public function get_progress()
	{
	    $result = array('hosts_scanned' => '-1', 'total_hosts' => '-1', 'remaining' => '-1');
        
        if ($this->get_status() == 'Scanning Hosts' && $this->num_of_host > 0 && file_exists($this->xml_output))
        {
            exec('cat '. $this->xml_output . ' | grep endtime | wc -l', $output);
	        
	        $hosts  = array_shift($output);
	        
	        $result['hosts_scanned'] = $hosts;
	        
	        $result['total_hosts']   = $this->num_of_host;

	        if (intval($hosts) > 0)
	        {
    	        exec('cat '. $this->xml_output . ' | grep nmaprun | grep startstr', $output);
    	        
    	        preg_match("/start=\"(\d+)\"/", array_shift($output), $found);
    	        
    	        $start_time = $found[1];     
    	        
    	        if (is_numeric($start_time))
    	        {
    	            exec('cat '. $this->xml_output . ' | grep endtime | perl -npe \'s/.*endtime="(\d+)".*/$1/ if (/endtime/)\' | sort -r | head -1', $output);
    	        
    	            $end_time = trim(array_shift($output));
    	        
        	        $seconds_per_host    = round(($end_time - $start_time) / $hosts);
        	        
        	        $result['remaining'] = ($this->num_of_host - $hosts) * $seconds_per_host;
    	        }
	        }
        }
        
        return $result;
    }
    
	
    /**
    * This function checks if the xml output is valid
    *
    * @return boolean
    */
	private function xml_formatted()
	{
	    $result = FALSE;
	    
	    if (file_exists($this->xml_output))
	    {	
        	exec('cat '. $this->xml_output . ' | grep "</nmaprun>"', $output);
                
            $line = array_shift($output);
                
            if (preg_match("/<\/nmaprun>/", $line))
            {
        	   $result = TRUE;
            }
        }
        
        return $result;
	}
	
	
    /**
    * This function creates the Nmap targets file
    *
    * @param boolean $recover  [Optional] Try to recover output with plugin
    *
    * @return void
    */
	private function get_targets()
	{
	    $attempts = 0;
	     
	    if ($this->xml_formatted() == FALSE && $attempts <=3)
	    {
	        usleep(500000);
            $attempts++;
	    }
	    
        $config = array(
                  'indent'     => TRUE,
                  'input-xml'  => TRUE,
                  'output-xml' => TRUE,
                  'wrap'       => FALSE);
          
        $tidy = new tidy();

        $repaired = $tidy->repairfile($this->xml_output, $config);

        file_put_contents($this->xml_output, $repaired);    	    
	
        if (file_exists($this->targets_file))
        {
            @unlink($this->targets_file);
        }

        $this->num_of_host = 0;
        
        $arr    = array();
        
        $result = $this->nmap_parser->parse_xml($this->xml_output);
        
        if (@is_array($result["scanned_ips"]))
        {
            foreach ($result["scanned_ips"] as $ip => $ip_data)
            {
                if (strcasecmp($ip_data['status'], 'up') == 0) 
                {
                    $this->num_of_host++;
                    $arr[] = $ip;
                }
                
                if (preg_match('/unreach/', $ip_data['status']) || preg_match('/unreach/', $ip_data['reason']))
                {
                    $ip_data['status'] = 'unreachable';
                }
                
                $this->hosts_statuses[$ip_data['status']]++;
            }
        } 
        
        $this->save_data();
        
        file_put_contents($this->targets_file, implode("\n", $arr));    	
	}
	
	
    /**
    * This function returns the Nmap scan status
    *
    * @return string
    */
	public function get_status()
	{    
        // Status default value 

        $this->status = 'Idle';
     
        // Is Nmap scan running?
        
        exec("ps -eo pid,args | grep -w -e 'nmap' | grep nmap_scan_" . $this->secure_id_md5 . " | grep _targets_file | grep -v sudo | awk '{print $1}'", $output);
        
        if (preg_match("/^\d+$/", array_shift($output)))
        {
	        $this->status = 'Scanning Hosts';	        
        }
        
        if ($this->status == 'Idle')
        {	        
            // Is Nmap searching hosts?
            
            exec("ps -eo pid,args | grep -w -e 'nmap' | grep nmap_scan_" . $this->secure_id_md5 . " | grep -v _targets_file | grep -v sudo | awk '{print $1}'", $output);
	        
	        if (preg_match('/^\d+$/', array_shift($output)))
	        {
    	        $this->status  = 'Searching Hosts';
	        }
        }

        if ($this->status == 'Idle')
        {
	        // Search finished or Scan finished
	        
	        if (file_exists($this->xml_output))
	        {
                $config = array(
                  'indent'     => TRUE,
                  'input-xml'  => TRUE,
                  'output-xml' => TRUE,
                  'wrap'       => FALSE);

                $tidy = new tidy();

                $repaired = $tidy->repairfile($this->xml_output, $config);

                file_put_contents($this->xml_output, $repaired);
	        
	            if ($this->only_ping == FALSE)
	            {	        
                    exec('grep -E -o "stats\-every" ' . $this->xml_output . ' | wc -l', $output);
                    
                    $lines = array_shift($output);
    
                    if (intval($lines) > 0)
                    {
                        $this->status  = 'Scan Finished';
                    }
                    else
                    {
                        $this->status  = 'Search Finished';
                        
                        if ($this->num_of_host == 0)
                        {
                            $this->get_targets();
                        }
                    }
                }
                else
                {
                    $this->status  = 'Scan Finished';
                }
	        }
        }

        return $this->status;
	}


    /**
    * This function deletes xml_output, targets_file files and configuration file
    *
    * @return void
    */
	public function delete_data($delete_config = TRUE)
	{	
        if (file_exists($this->xml_output))
    	{
            @unlink($this->xml_output);
        }
        
        if(file_exists($this->targets_file))
        {
            @unlink($this->targets_file);
        }
        
        if ($delete_config == TRUE && file_exists($this->data_file))
    	{
            @unlink($this->data_file);
        }
	}
	
    /**
    * This function checks the running scans with remote_nmap.php script
    *
    * @param string  $ip  Target to search
    *
    * @return boolean      
    */ 
	public static function scanning_now()
	{
	    $user = Session::get_session_user();
	
		$cmd = ($user != '') ? "ps ax | grep remote_nmap.php | grep $user | egrep -v 'grep|vulnscan'" : "ps ax | grep remote_nmap.php | egrep -v 'grep|vulnscan'";
		
		$output = explode("\n",`$cmd`);
		
        return (preg_match('/remote\_nmap/',$output[0])) ? 1 : 0;
		
	}
	
	
    /**
    * This function returns the hosts that are being scanned with the sensor ID
    *
    * @return array      
    */
	public static function scanning_what()
	{
	    $user = Session::get_session_user();
	
		$cmd  = ($user != '') ? "ps ax | grep remote_nmap.php | grep $user | egrep -v 'grep|vulnscan'" : "ps ax | grep remote_nmap.php | egrep -v 'grep|vulnscan'";
		
		$output = explode("\n",`$cmd`);
		
		$ips    = array();
		
		foreach($output as $line) 
		{
			if (preg_match_all('/(\d+\.\d+\.[^ ]+\/\d+) /',$line,$found)) 
			{
                preg_match('/\s([A-Z0-9]{32})\s/', $line, $data); // get sensor ID
				foreach($found as $fnd)
				{
					foreach ($fnd as $cidr)
					{
						$ips[] = trim($data[1].'#'.$cidr);
					}
				}
			}
		}
		
		return array_unique($ips);
	}
    
    /**
    * This function saves scanned hosts into database
    *
    * @param object   $conn            Database access object
    * @param array    $scan_results    Scan results
    * @param array    $host_data       Some host data (Sensors, CTXs, user FQDN as hostname, etc)
    * @param boolean  $report_changes  [Optional] Report changes to other components
    *
    * @throws Exception  If an error occurred
    *
    * @return array      
    */ 
    public static function save_hosts_in_db($conn, $scan_results, $host_data, $report_changes = TRUE)
    {
        Ossim_db::check_connection($conn);        
                
        //Ips to insert/update        
        $s_ips  = $host_data['ips'];
        
        //Number of IPs to insert/update
        $num_ips = count($s_ips);
                      
        //Process status      
        $data = array(
            'general' => array(
                'status'         => '',
                'data'           => '',
                'hosts_in_group' => array(),
                'total_hosts'    => 0
             ),
            'by_host' => array()       
        );
                
        Util::disable_perm_triggers($conn, TRUE);   
        
        //Host information
        $scan = $scan_results['scanned_ips'];
        $ctx  = $scan_results['sensor']['ctx'];       
                             
        for ($i = 0; $i < $num_ips; $i++) 
        {
            if (trim($s_ips[$i]) != '')
            {
                $data['general']['total_hosts']++;
                
                $ip = $s_ips[$i];           
                
                //There is a validation error (Host could not be saved)
                ossim_valid($ip, OSS_IP_ADDR, 'illegal:' . _('IP address'));
                
                if (ossim_error())
                {                    
                    ossim_clean_error();
                    
                    $data['by_host'][$ip]['status']     = 'error';
            		$data['by_host'][$ip]['data']['ip'] = ossim_get_error_clean();
            		 
            		continue;            
                }                             
                
                //Initialize variables
                $host          = NULL;
                $ips           = array();
                $name          = $scan[$ip]['hostname'];
                $fqdn          = $scan[$ip]['fqdn'];
                $descr         = $host_data['descr'];
                $mac           = $scan[$ip]['mac'];                
                $os            = $scan[$ip]['os'];
                $asset_value   = $host_data['asset_value'];
                $external      = $host_data['external'];
                $threshold_c   = $host_data['threshold_c'];
                $threshold_a   = $host_data['threshold_a'];
                $sensors       = $host_data['sboxs'];           
                $nmap_devices  = $scan[$ip]['device_types'];
                $hardware_cpes = $scan[$ip]['hardware_cpes'];
                $software_cpes = $scan[$ip]['software_cpes'];
                $services      = $scan[$ip]['services'];
                $nagios        = $host_data['nagios'];
                                
                //Save host with new data 
                try 
                {                                         
                    $host_ids = Asset_host::get_id_by_ips($conn, $ip, $ctx);
                    $id       = key($host_ids);
                    
                    $is_in_db = Asset_host::is_in_db($conn, $id);
                                                            
                    //Host already exists                            
                                                                              
                    if ($is_in_db == TRUE)
                    {                                           
                        $can_i_create_assets = TRUE;                              
                        
                        //Updating specific attributes (Only name and FQDNs)                                              
                        $host = new Asset_host($conn, $id);
                        $host->load_from_db($conn);                                                
                        
                        $h_fqdn  = $host->get_fqdns();                     
                        $h_descr = $host->get_descr();
                        
                        if (!empty($fqdn))
                        {
                            $host->set_fqdns($fqdn);
                            $h_fqdn  = $host->get_fqdns();
                        }
                        
                        
                        // Use FQDNs as host name (See checkbox on Asset Discovery)
                                            
                        
                        if(!empty($h_fqdn) && !empty($host_data["fqdn_$i"]))
                        {
                            $aux   = explode('.', $fqdn);
                            $name  = $aux[0];
                            $host->set_name($name);
                        }                      
                        
                        
                        //Only if old description is empty
                        if (!empty($descr) && empty($h_descr))
                        {
                            $host->set_descr($descr);
                        }                                      
                        
                        $host_ips = $host->get_ips();
            			$ips      = $host_ips->get_ips();
            			    
                        $ips[$ip] = array(
            			    'ip'   =>  $ip,
            			    'mac'  =>  NULL,
        			    );
        			    
        			    if (!empty($mac))
        			    {
            			    $ips[$ip]['mac'] = $mac;     
        			    }
        			    
        			    $host->set_ips($ips);		        			                                                                            
                    }
                    else
                    {                                                                    
                        $can_i_create_assets = Session::can_i_create_assets();
                                             
                        if ($can_i_create_assets == TRUE)
                        {
                            //Creating new host and updating specific attributes 
                            $id = Util::uuid();
                            
                            $host = new Asset_host($conn, $id);
                            $host->set_ctx($ctx);                            
                            
                            // Use FQDNs as host name (See checkbox on Asset Discovery)
                            if(!empty($fqdn) && !empty($host_data["fqdn_$i"]))
                            {
                                $aux   = explode('.', $fqdn);
                                $name  = $aux[0];
                                $host->set_name($name);
                            }
                            else
                            {
                                $host->set_name($name);
                            }  
                            
                            if (!empty($fqdn))
                            {
                                $host->set_fqdns($fqdn);
                            }                                               
                            
                            $ips[$ip] = array(
                			    'ip'   =>  $ip,
                			    'mac'  =>  NULL,
            			    );
            			    
            			    if (!empty($mac))
            			    {
                			    $ips[$ip]['mac'] = $mac;     
            			    }
            			    
            			    $host->set_ips($ips);
            			  
                            
                            $host->set_descr($descr);
                                                
                            $host->set_sensors($sensors);
                            
                            $host->set_asset_value($asset_value);
                            
                            $host->set_threshold_c($threshold_c);
                            
                            $host->set_threshold_a($threshold_a);
                            
                            $host->set_external($external);                                                                                                           
                        }                                                        
                    }      
                                                                  
                                      
                    $cnd_1 = (is_object($host) && !empty($host));
                    $cnd_2 = ($can_i_create_assets == TRUE);
                    
                    if ($cnd_1 && $cnd_2)
                    {                    
                        //Updating common attributes (Devices, Software and Services)
                                            
                        //Adding new NMAP devices
                        if (is_array($nmap_devices) && !empty($nmap_devices))
                        {
                            foreach($nmap_devices as $nmap_device_id)
                            {
                                $host->get_devices()->add_nmap_device($nmap_device_id);
                            }                              
                        }       
                                                                                               
                        $host->save_in_db($conn, FALSE);                                                                                
                        
                        //Adding host to new asset group
                        $data['general']['hosts_in_group'][$ip] = $id;                            
                        
                        $data['by_host'][$ip]['status'] = 'success';
                        
                        if ($is_in_db == TRUE)
                        {
                            $data['by_host'][$ip]['data'] = _('Host updated');                             
                        }
                        else
                        {
                            $data['by_host'][$ip]['data'] = _('New host inserted');                                
                        }
                             
                        
                        //Initialize warning array
                        $warnings = array();                                                                           
                                                                                    
                        //Insert/Updating Operating System
                       	if (!empty($os))
                       	{
                            try
                            {                               
                                $save_os = TRUE;
                                
                                if ($is_in_db == TRUE)
                                {
                                    $filters = array(
                                        'where' => "host_properties.host_id = UNHEX('$id') 
                                                    AND host_properties.property_ref = 3 
                                                    AND host_properties.source_id = hsr.id 
                                                    AND hsr.relevance > 7"
                                    );
                                    
                                    $_p3_list = Asset_host_properties::get_list($conn, $filters);
                                    $total_p3 = $_p3_list[1]; 
                                    
                                    if ($total_p3 > 0)
                                    {
                                        $save_os = FALSE;                                        
                                       
                                        $warnings['os_error'] = _('OS could not be saved because the host is associated to another with more relevance');
                                    }                                                                  
                                }
                                
                                if ($save_os == TRUE)
                                {
                                    Asset_host_properties::delete_all_from_db($conn, $id);
                                    Asset_host_properties::save_property_in_db($conn, $id, 3, $os, 5);
                                }                            
                           	}
                           	catch(Exception $e)
                           	{
                               	$warnings['os_error'] = _('Operating System could not be saved');
                           	}               
                       	}
                            
                                                                                    
                        //Insert Nagios (Only for new hosts)                           
                                               
                        if ($is_in_db == FALSE && !empty($nagios))
                    	{
                            try
                            {
                                Asset_host_scan::save_plugin_in_db($conn, $id, 2007);
                            }
                            catch(Exception $e)
                           	{
                               	$warnings['ng_error'] = _('Nagios could not be enabled');
                           	}                                                  
                       	}                         
                            
                            
                        //Insert/Updating Hardware CPEs
                        if(is_array($hardware_cpes) && !empty($hardware_cpes)) 
                       	{                           
                            foreach($hardware_cpes as $h_cpe) 
                            {
                                $s_data = array(
                                    'cpe'        => $h_cpe,
                                    'banner'     => '',
                                    'source_id'  => 5,
                                    'extra'      => ''
                                );                                        
                                
                                try
                                {
                                    Asset_host_software::save_software_in_db($conn, $id, $s_data);
                                }
                                catch(Exception $e)
                               	{
                                   	$warnings['hd_error'] = _('Some hardware could not be saved');
                               	}                         
                            }
            			}
                			
                			
            			//Insert/Updating Software CPEs
                        if(is_array($software_cpes) && !empty($software_cpes)) 
                       	{                           
                            foreach($software_cpes as $s_cpe) 
                            {
                                $s_data = array(
                                    'cpe'        => $s_cpe,
                                    'banner'     => '',
                                    'source_id'  => 5,
                                    'extra'      => ''
                                );                                        
                                
                                try
                                {
                                    Asset_host_software::save_software_in_db($conn, $id, $s_data);
                                }
                                catch(Exception $e)
                                {
                                    $warnings['sf_error'] = _('Some software could not be saved');
                                }                           
                            }
            			}
                             
                            
                        //Adding services
                       	if(is_array($services) && !empty($services)) 
                       	{
                            foreach($services as $service) 
                            {
                                $s_data = array(
                                    'ip'        => $ip,
                                    'service'   => $service['service'],
                                    'port'      => $service['port'],
                                    'protocol'  => getprotobyname(trim(strtolower($service['proto']))),
                                    'version'   => $service['version'],
                                    'source_id' => 5,
                                    'nagios'    => $nagios
                                );                                                                                                                                                        
                                
                                try
                                {
                                    Asset_host_services::save_service_in_db($conn, $id, $s_data);
                                }
                                catch(Exception $e)
                                {
                                    $warnings['service_error'] = _('Some services could not be saved');
                                }                                                                   
                            }                   
            			} 
            			                			
            			//Setting host status and general status
            			if (is_array($warnings) && !empty($warnings))
            			{                    			                    			
                			$data['by_host'][$ip]['status'] = 'warning';
                			$data['by_host'][$ip]['data']   = $warnings;                   			
            			}                        
                    }
                    else
                    {                                            
                        $data['by_host'][$ip]['status'] = 'error';
                		$data['by_host'][$ip]['data']   = array ('no_create_asset' => _('No permissions to create assets'));      
                    }                    
                }
                catch(Exception $e)
                {                
                    $data['by_host'][$ip]['status'] = 'error';
            		$data['by_host'][$ip]['data']   = array ('php_exception' => $e->getMessage());                     
                }
            }
        } 
        
        
        if (count($data['general']['hosts_in_group']) > 0 && $report_changes == TRUE)
        {
            try
            {
                Asset_host::report_changes($conn, 'hosts');
            }
            catch(Exception $e)
            {
                Av_exception::write_log(Av_exception::DB_ERROR, $e->getMessage());                    
            }
        } 
        
        Util::disable_perm_triggers($conn, FALSE);

        return $data;  
    } 
}

/* End of file scan.inc */
/* Location: ../include/classes/scan.inc */