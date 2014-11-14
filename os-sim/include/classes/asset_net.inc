<?php
/**
* asset_net.inc
*
* File asset_net.inc is used to:
*   - To manage nets
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
* @package    ossim-framework\Asset
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


require_once 'av_config.php';


/**
* Asset_net Class
*
* Class for managing nets
*
* @package    ossim-framework\Asset
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ Alienvault web page
*/

class Asset_net extends Asset
{
    /**
    * Net Owner
    *
    * @var string
    * @access protected
    */
    protected $owner;


    /**
    * This function returns the owner of the net
    *
    * @return string
    */  
    public function get_owner() 
    {
        return $this->owner;
    }
    
    
    /**
    * This function sets the owner of the net
    *
    * @param string  $owner  Net Owner  
    *
    * @return void
    */  
    public function set_owner($owner) 
    {
        $this->owner = stripslashes($owner);
    }
    
    
    /**
    * Class constructor
    *
    * This function sets up the class
    *
    * @param string  $id   Net ID 
    */    
    public function __construct($id)
    {
        $this->set_id($id);
        
        $conf = $GLOBALS['CONF'];

        if (!$conf)
        {
            $conf = new Ossim_conf();
            $GLOBALS['CONF'] = $conf;
        }
        
        $asset_value = $conf->get_conf('def_asset');
        
        if ($asset_value == '')
        {
            $asset_value = 2;
        }
        
        $threshold   = $conf->get_conf('threshold');
        
        if ($threshold == '')
        {
            $threshold = 30;
        }
             
             
        $this->ctx         = Session::get_default_ctx();
        $this->name        = '';
        $this->ips         = '';
        $this->descr       = '';
        $this->icon        = NULL;
        $this->external    = 0;
        $this->asset_value = $asset_value;
        $this->threshold_c = $threshold;
        $this->threshold_a = $threshold;
        $this->owner       = NULL;
        $this->sensors     = new Asset_net_sensors($id);
    }
    
    
    /**
    * This function returns the CIDRs
    *
    * @param string $format  [Optional] Output format (array or string)
    *
    * @return array|string
    */      
    public function get_ips($format = 'string')
    {
        $ips = NULL;
        
        if ($format == 'string')
        {
            $ips = $this->ips;
        }
        else
        {           
            $ips = explode(',', $this->ips);
        }
        
        return $ips;
    }
    
    
    /**
    * This function returns the asset type
    *
    * @return string
    */ 
    public function get_asset_type() 
    {
        return 'net';
    }
    
    
    /**
    * This function returns the hosts related to a Network listed by ID
    *
    * @param object   $conn     Database access object
    * @param array    $filters  [Optional] SQL statements (Only LIMIT and ORDER BY)
    * @param array    $basic    [Optional] Show basic data or detail data
    * @param boolean  $cache    [Optional] Use cached information 
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array List of host IDs
    */
    public function get_hosts($conn, $filters = array(), $basic = TRUE, $cache = FALSE)
    {
        Ossim_db::check_connection($conn);        
        
        $hosts = array();
        $total = 0;        
        
        if ($basic == TRUE)
        {
            $perms_where = Asset_host::get_perms_where('h.', TRUE);
            
            $q_select = 'HEX(h.id) AS h_id, INET6_NTOP(hi.ip) AS h_ip, h.hostname';
            
            $q_where  =  "WHERE hnr.net_id = UNHEX(?) 
                AND hnr.host_id = h.id 
                AND hnr.net_id = n.id 
                AND h.id = hi.host_id 
                AND h.ctx = n.ctx $perms_where";    
                
                 
            if (!empty($filters['order_by']))
            {
                $q_where  .= ' ORDER BY '.$filters['order_by'];
            }
            
            if (!empty($filters['limit']))
            {
                $q_select  = 'SQL_CALC_FOUND_ROWS '.$q_select;
                $q_where  .= ' LIMIT '.$filters['limit'];
            }     
            
            $query = "SELECT DISTINCT $q_select 
                FROM host_net_reference hnr, host h, net n, host_ip hi $q_where";        
                                 
            $params = array($this->get_id());
                             
            $rs = ($cache) ? $conn->CacheExecute($query, $params) : $conn->Execute($query, $params);       
            
            if (!$rs) 
            {           
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            } 
            
            $total = Ossim_db::get_found_rows($conn, $query, $cache);
                          
            $rs = $conn->Execute($query, $params);      
            
            if (!$rs) 
            {           
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            } 
    
            while (!$rs->EOF)
            {
                if (empty($hosts[$rs->fields['h_id']]))
                {
                    $hosts[$rs->fields['h_id']]['hostname'] = $rs->fields['hostname'];
                    $hosts[$rs->fields['h_id']]['ips']      = $rs->fields['h_ip'];             
                }
                else
                {
                    $hosts[$rs->fields['h_id']]['ips'] .= ','.$rs->fields['h_ip'];
                }             
                                 
                $rs->MoveNext();
            }
        }
        else
        {            
            $tables = ', host_net_reference hr, net n';
                                        
            $n_filters = array(
                'where'    => "hr.net_id = n.id AND n.ctx = host.ctx AND hr.host_id = host.id AND hr.net_id = UNHEX('". $this->id ."') ",
                'order_by' => $filters['order_by'],
                'limit'    => $filters['limit']
            );

            if (!empty($filters['where']))
            {
                $n_filters['where'] .=  ' AND '.$filters['where'];
            }                       
            
            list($hosts, $total) = Asset_host::get_list($conn, $tables, $n_filters, $cache);
        }        
        
        return array($hosts, $total);
    }
    
    
    /**
    * This function returns HIDS configuration
    *   
    *   - 0 No IPs are configured (red)
    *   - 1 All IPs are configured (green)
    *   - 2 Some IPs configured belongs to net (yellow)
    *
    * @param object  $conn   Database access object
    *
    * @throws Exception  If a connection error occurred
    *
    * @return integer
    */
    public function is_hids_enabled($conn)
    {
        Ossim_db::check_connection($conn);

        $agents      = array();
        $agent_cidrs = array();

        $sensors = $this->sensors->get_sensors();

        // Get the agents IPs and CIDRs (For each related sensor)
        foreach ($sensors as $s_id => $s_data)
        {
            $agents = Ossec_agent::get_list($s_id);

            foreach ($agents as $a_data)
            {
                $ip_cidr = trim($a_data['ip']);

                if(preg_match("/any|0\.0\.0\.0\/0/", $ip_cidr) == TRUE)
                {
                    //Special Case: IP/CIDR is any or 0.0.0.0/0

                    return 1; // GREEN
                }
                elseif (preg_match("/^\d+\.\d+\.\d+\.\d+\/\d+$/", $ip_cidr) == TRUE)
                {
                    $agent_cidrs[$ip_cidr] = $ip_cidr;
                }
            }
        }

        if (!empty($agent_cidrs))
        {
            $all_are_hids  = TRUE;  // All CIDRs have HIDS enabled
            $any_is_hids   = FALSE; // True if some CIDRs has HIDS enabled
            
            // Compare CIDRs
            $cidrs = $this->get_ips();

            //Getting closest nets
            $closest_nets = self::get_closest_nets($conn, $cidrs);

            $cidrs = explode(',', $cidrs);

            foreach ($cidrs as $cidr)
            {
                $is_in_hids = FALSE;

                // 1-. Net CIDR is an Agent CIDR?
                if (array_key_exists($cidr, $agent_cidrs))
                {
                    $is_in_hids = TRUE;
                }
                else
                {
                    foreach($closest_nets as $cn_data)
                    {
                        if (array_key_exists($cn_data['ips'], $agent_cidrs))
                        {
                            $is_in_hids = TRUE;
                            
                            break;
                        }
                    }
                }
                
                if ($is_in_hids == TRUE)
                {
                    $any_is_hids = TRUE;
                }
                else
                {
                    $all_are_hids = FALSE;
                }
            }

            if ($any_is_hids == TRUE)
            {
                return ($all_are_hids) ? 1 : 2; // GREEN / YELLOW
            }
        }

        return 0; // RED
    }

    /**
    * This function returns true if current network associated hosts have alarms
    *
    * @param object   $conn  Database access object
    * @param string   $id    Net ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean
    */
    public static function has_alarms($conn, $id)
    {
        Ossim_db::check_connection($conn);      
   
        return Alarm::has_alarms($conn, 'net', $id);
    }


    /**
    * This function returns true if current host id has events
    *
    * @param object   $conn   Database access object
    * @param string   $id     Net ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean
    */
    public static function has_events($conn, $id)
    {
        Ossim_db::check_connection($conn);      
   
        return SIEM::has_events($conn, 'net', $id);
    }

    
    /**
    * This function returns the number of network vulnerabilities 
    *     
    * @param object  $conn   Database access object
    * @param string  $ctx    Context (Hex value)
    * @param string  $id     Network uuid (Hex value) 
    *
    * @throws Exception  If a connection error occurred
    *
    * @return integer 
    */
    public static function get_vulnerability_number($conn, $id)
    {   
        Ossim_db::check_connection($conn);
                   
        //No info                
        $count = -1;
                            
        $sql = "SELECT COUNT(lr.hostIP) as counter FROM alienvault.host h, alienvault.net n, alienvault.host_net_reference r, alienvault.host_ip hi, alienvault.vuln_nessus_latest_results lr WHERE hi.host_id=r.host_id AND hi.host_id=h.id AND hi.ip=inet6_pton(lr.hostIP) AND h.ctx=lr.ctx AND r.net_id=n.id AND n.ctx=h.ctx AND r.net_id=UNHEX(?)";

        $rs = $conn->Execute($sql, array($id));

        if (!$rs)
        {           
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            $count = $rs->fields['counter'];
        }
                        
        return $count;
    }
    
    
    /**
    * This function returns the Machine State property (ID = 7) sum of each related host
    *
    * @param object  $conn   Database access object   
    *
    * @throws Exception  If a connection error occurred
    *
    * @return string up / down / unknown
    */      
    public function get_status($conn)
    {     
        Ossim_db::check_connection($conn);
        
        $status = array(
            'up'      => 0,
            'down'    => 0,
            'unknown' => 0
        );
        
        $_host_data = $this->get_hosts($conn);
        $hosts      = $_host_data[0];       
               
        foreach ($hosts as $host_id => $_data)
        {
            $st = Asset_host_properties::get_status_by_host($conn, $host_id);
            
            $status[$st]++;
        }
        
        $status_str = '';
        
        foreach ($status as $key => $val)
        {
            if ($status_str != '')
            {
                $status_str .= ' / ';
            }
            
            $status_str .= "$val $key";
        }
                      
        return $status_str;
    }
    
    
    /**
    * This function returns the user property (ID = 8) sum of each related host
    *
    * @param object  $conn   Database access object   
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */      
    public function get_users($conn, $filters = array())
    {     
        Ossim_db::check_connection($conn);
        
        return Asset_host_properties::get_users_by_net($conn, $this->id, $filters);
    }
    
    
    /**
    * This function returns the alarms related to the net
    *
    * @param object   $conn       Database access object
    * @param string   $id         Net ID
    * @param integer  $from       From offset
    * @param integer  $max        [Optional] Maximum elements per page    
    * @param string   $date_from  [Optional] Date from filter
    * @param string   $date_to    [Optional] Date to filter
    * @param string   $filter     [Optional] SQL query
    * @param string   $order      [Optional] SQL order statement
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array Alarms list and counter
    */
   
    public static function get_alarms($conn, $id, $from, $max, $date_from = '', $date_to = '', $filter = '', $order = 'a.timestamp DESC')
    {
        Ossim_db::check_connection($conn);      

        $criteria = array(
            'src_ip'        => '',
            'dst_ip'        => '',
            'hide_closed'   => 1,
            'order'         => "ORDER BY $order",
            'inf'           => $from,
            'sup'           => $from + $max,
            'date_from'     => $date_from,
            'date_to'       => $date_to,
            'query'         => $filter,
            'directive_id'  => '',
            'intent'        => 0,
            'sensor'        => '',
            'tag'           => '',
            'num_events'    => '',
            'num_events_op' => 0,
            'plugin_id'     => '',
            'plugin_sid'    => '',
            'ctx'           => '',
            'host'          => '',
            'net'           => $id,
            'host_group'    => ''
        );
            
        return Alarm::get_list($conn, $criteria);     
    }
    
    
    /**
    * This function returns the properties related to host
    *
    * @param object   $conn  Database access object
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array List of properties
    */    
    public function get_properties($conn, $filters = array())
    {
        if (empty($filters['where']))
        {
            $filters['where'] = "h.id IN (SELECT host_id FROM host_net_reference WHERE net_id = UNHEX('".$this->id."'))";
        }
        else
        {
            $filters['where'] .= " AND h.id IN (SELECT host_id FROM host_net_reference WHERE net_id = UNHEX('".$this->id."'))";
        }
    
        $properties = Asset_host_properties::get_list($conn, $filters);

        return $properties;
    }
    
    
    /**
    * This function returns the software related to host
    *
    * @param object   $conn  Database access object
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array List of software
    */
    public function get_software($conn)
    {
        $_host_data = $this->get_hosts($conn);
        $hosts      = $_host_data[0];
        
        $hosts = array_keys($hosts);

        $software = array();
            
        if (is_array($hosts) && !empty($hosts))
        {
            $host_where = implode("'), UNHEX('", $hosts);
                   
            $filters = array(
                'where' => "h.id IN (UNHEX('".$host_where. "'))"
            );

            $aux_software = Asset_host_software::get_list($conn, $filters);
            $software     = $aux_software[0];
        }

        return $software;
    }
    
    
    /**
    * This function returns the services related to host
    *
    * @param object   $conn  Database access object
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array List of services
    */
    public function get_services($conn, $filters = array())
    {
        $serv_where = "SELECT host_id FROM host_net_reference WHERE net_id = UNHEX('". $this->id ."')";
        
        if (empty($filters['where']))
        {
            $filters['where']  = "h.id IN (".$serv_where. ")";
        }
        else
        {
            $filters['where'] .= " AND h.id IN (".$serv_where. ")";
        }
        
        $services   = Asset_host_services::get_list($conn, $filters);
        
        return $services;
    }
    
    
    /**
    * This function returns the vulnerabilities found in vuln_nessus_latest_results table
    * related to service/port pair
    *
    * @param object   $conn      Database access object
    * @param string   $service   Service
    * @param integer  $port      Service port
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array     
    */      
    public function get_vulns_by_service($conn, $service, $port)
    {
        $ips = $this->get_ips('array');
            
        return Asset_host_services::get_vulns_by_service($conn, $ips, $this->ctx, $service, $port);
    }
    
    
    /**
    * This function sets CIDR list
    *
    * @param array $ips  Net CIDRs
    *
    * @return void
    */      
    public function set_ips($ips)
    {       
        $ips = $this->delete_duplicated_cidrs($ips);
        
        $this->ips = $ips;
    }

    
    /**
    * This function sets the net data from database
    *
    * @param object  $conn   Database access object     
    * @param boolean $cache  [Optional] Use cached information
    *
    * @throws Exception  If net ID doesn't exists in the System or there is a connection error    
    *
    * @return void
    */ 
    public function load_from_db($conn, $cache = FALSE)
    {        
        Ossim_db::check_connection($conn);
               
        //Getting net information
        $query  = 'SELECT n.* , HEX(id) AS id, HEX(ctx) AS ctx FROM net n WHERE n.id = UNHEX(?)';
        $params = array($this->get_id());
        
        $rs = ($cache == TRUE) ? $conn->CacheExecute($query, $params) : $conn->Execute($query, $params);
              
        if (empty($rs->fields['id']))
        {            
            $exp_msg = _('Error! Net not found');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
                
        $this->ctx         = $rs->fields['ctx'];  
        $this->name        = $rs->fields['name'];
        $this->ips         = $rs->fields['ips'];       
        $this->descr       = $rs->fields['descr'];
        $this->icon        = $rs->fields['icon'];
        $this->external    = $rs->fields['external_net']; 
        $this->asset_value = $rs->fields['asset'];    
        $this->threshold_c = $rs->fields['threshold_c'];        
        $this->threshold_a = $rs->fields['threshold_a'];
        $this->owner       = $rs->fields['owner'];
            
        $this->sensors->load_from_db($conn, $cache); 
    }
    
    
    /**
    * This function saves net into database
    *
    * @param object   $conn            Database access object
    * @param boolean  $report_changes  [Optional] Report changes to other components
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean      
    */ 
    public function save_in_db($conn, $report_changes = TRUE)
    {
        Ossim_db::check_connection($conn);
                    
        $id   = $this->get_id();
        $ips  = $this->get_ips();
        $ctx  = $this->get_ctx();
        $name = $this->get_name();
        
        
        if (Session::get_net_where() != '')
        {
            if (!self::is_cidr_in_my_nets($conn, $ips, $ctx))
            {             
                $exp_msg = _('Error! CIDR not allowed.  Check your asset filter');

                Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
            }
        }
                
        $net_ids = self::get_id_by_ips($conn, $ips, $ctx);
        
        unset($net_ids[$id]);       
    
        if (!empty($net_ids))
        {         
            $exp_msg = _("Error! CIDR not allowed.  CIDR $ips already exists for this entity");

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
                
        $is_in_db = self::is_in_db($conn, $id);
        
        //Begin transaction
        $conn->StartTrans();            
        
        $query = 'REPLACE INTO net (
                    id, 
                    ctx, 
                    name, 
                    ips, 
                    external_net, 
                    asset, 
                    threshold_c, 
                    threshold_a, 
                    rrd_profile, 
                    alert, 
                    persistence, 
                    descr, 
                    icon,
                    owner)
                VALUES (UNHEX(?), UNHEX(?), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        
       
       $params = array(
            $id,
            $ctx,
            $name,
            $ips,
            $this->get_external(),
            $this->get_asset_value(),
            $this->get_threshold_c(),
            $this->get_threshold_a(),
            0,
            0,
            0,
            $this->get_descr(),
            $this->get_icon(),
            $this->get_owner()
        ); 
        
        $rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
                
        //Save sensors
        $this->sensors->save_all_in_db($conn);
            
                          
        if ($is_in_db == TRUE)
        {                       
            $this->delete_cidrs_from_cache($conn, $id);         
        }
        else
        {
            //Net Qualification
            Asset_net_qualification::insert($conn, $id, 1, 1);                           
        }  
        
        $this->add_cidrs_to_cache($conn, $id, $ips);    
        
        
        //Host-Network reference
        self::set_host_net_reference($conn, $id);    
         
        //Finish transaction
        if ($conn->CompleteTrans())
        {           
            $infolog = array($name);
            
            if ($is_in_db == TRUE)
            {                 
                Log_action::log(28, $infolog);
            }
            else
            {
                Log_action::log(27, $infolog); 
            }
            
            if ($report_changes == TRUE)
            {
                try
                {
                    self::report_changes($conn, 'nets');
                }
                catch(Exception $e)
                {
                    Av_exception::write_log(Av_exception::DB_ERROR, $e->getMessage());                   
                }    
            }         
        }
        else
        {        
            $exp_msg = _('Error! Net could not be saved');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        } 
        
        return TRUE;
    }
    
    
    /**
    * This function returns true if network is monitored with Nagios
    *
    * @param object  $conn   Database access object
    *
    * @throws Exception  If a connection error occurred  
    *
    * @return boolean
    */
    public function is_nagios_enabled($conn)
    {               
        return Asset_net_scan::is_plugin_in_net($conn, $this->get_id(), 2007); 
    }
    
    
          
    /**
    * This function returns Automatic Asset Discovery configuration.
    * Net is scanned with NMAP scheduled task
    *
    * @param object  $conn   Database access object
    *
    * @throws Exception  If a connection error occurred    
    *
    * @return boolean 
    */
    public function is_autodetected($conn)
    {
        Ossim_db::check_connection($conn);
        
        $networks = array();
        
        //Getting host sensors
        $sensor_obj = $this->get_sensors();
        $sensors    = $sensor_obj->get_sensors();
        $sensors    = array_keys($sensors);
        
        $q_sensors  = implode("'), UNHEX('", $sensors);
        $q_sensors  = "UNHEX('".$q_sensors."')";
        
        
        //Getting Inventory Tasks
        $target_param = " AND task_sensor IN($q_sensors) AND task_enable = 1";     
        $task_list    = Inventory::get_list($conn, '', 5, $target_param);            
                       
        if (count($task_list) > 0)
        {
            //Getting networks from inventory tasks
            foreach ($task_list as $task_data)
            {
                $_aux = explode('#', $task_data['task_params']);                
                $_nets = explode(' ', $_aux[0]);                             
                                                
                foreach ($_nets as $net)
                {                                        
                    if (self::valid_cidr($net))
                    {                                           
                        $networks[$net] = $net;
                    }
                }
            }
            
                   
            if (is_array($networks) && !empty($networks))
            {            
                $all_are_autodetected  = TRUE;  // All CIDRs are autodetected
                $any_is_autodetected   = FALSE; // True if some CIDR is autodetected                                              
                
                // Compare CIDRs
                $cidrs = $this->get_ips();
                                
                //Getting closest nets
                $closest_nets = self::get_closest_nets($conn, $cidrs);                                                
                
                $cidrs = explode(',', $cidrs);                             
                                
                foreach ($cidrs as $cidr)
                {                                  
                    $is_autodetected = FALSE;
                    
                    // 1-. Is Net CIDR autodetected?
                    if (array_key_exists($cidr, $networks))
                    {
                        $is_autodetected = TRUE;
                    }
                    else
                    {                                               
                        foreach($closest_nets as $cn_data)
                        {                                                    
                            if (array_key_exists($cn_data['ips'], $networks)) 
                            {
                                $is_autodetected = TRUE;
                                
                                break;
                            }
                        }                 
                    }              
                    
                    if ($is_autodetected == TRUE)
                    {
                        $any_is_autodetected = TRUE;
                    }
                    else
                    {
                        $all_are_autodetected = FALSE;
                    }   
                }
                
                if ($any_is_autodetected == TRUE)
                {
                    return ($all_are_autodetected) ? 1 : 2; // GREEN / YELLOW
                }                            
            }                
        }
        
        return 0;  // RED
    }
    
        
    
    /*************************************************        
     *************** Private functions ***************      
     *************************************************/
     
     
     
    /**
    * This function adds CIDRs to table net_cidrs
    *
    * @param object  $conn   Database access object
    * @param string  $id     Net ID
    * @param string  $ips    Comma-separated CIDRs
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean      
    */ 
    private function add_cidrs_to_cache($conn, $id, $ips)
    {
        Ossim_db::check_connection($conn);
        
        $cidrs = explode(',', $ips);
    
        foreach ($cidrs as $cidr) 
        {
            $cidr = trim($cidr);
            
            $exp = self::expand_cidr($cidr, 'SHORT', 'IP');           
                      
            $query  = 'REPLACE INTO net_cidrs (`net_id`,`cidr`,`begin`,`end`) VALUES (UNHEX(?), ?, INET6_PTON(?), INET6_PTON(?))';
            $params = array(
                $id,
                $cidr,
                $exp[$cidr][0],
                $exp[$cidr][1]
            );
            
            $rs = $conn->Execute($query, $params); 
                       
            if (!$rs) 
            {           
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
        }
        
        return TRUE;  
    }
            
    
    /**
    * This function deletes CIDRs to table net_cidrs
    *
    * @param object  $conn   Database access object
    * @param string  $id     Net ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean      
    */ 
    private function delete_cidrs_from_cache($conn, $id)
    {
        Ossim_db::check_connection($conn);
        
        $query  = 'DELETE FROM net_cidrs WHERE net_id = UNHEX(?)';
        $params = array($id);
        
        $rs = $conn->Execute($query, $params);      
        
        if (!$rs) 
        {           
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        return TRUE;  
    }

     
    /**
    * This function deletes duplicated CIDRs from CIDR list
    *
    * @param string  $cidrs  Comma-separated CIDRs
    *
    * @return string      
    */ 
    private static function delete_duplicated_cidrs($ips)
    {        
        $cidrs = explode(',', $ips);

        $cidrs = array_map('trim', $cidrs);
        $cidrs = array_unique($cidrs);
        
        return implode(',', $cidrs);
    } 
    
           
    
    /*************************************************        
     *************** Static functions ****************      
     *************************************************/
    
   
        
    /**
    * This function checks if net could be deleted
    *
    * @param object  $conn   Database access object
    * @param string  $id     Net ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean      
    */   
    protected static function can_delete($conn, $id) 
    {
        Ossim_db::check_connection($conn);
        
        $query  = 'SELECT count(*) AS num FROM policy_net_reference WHERE net_id = UNHEX(?)';
        $params = array($id);
        
        $rs = $conn->Execute($query, $params);      
        
        if (!$rs) 
        {           
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        
        if ($rs->fields['num'] > 0)
        {
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    /**
    * This function inserts/deletes nets into table host_net_reference
    *
    * @param object  $conn    Database access object
    * @param string  $id      Net ID
    * @param boolean $delete  [Optional] Delete nets
    * @param boolean $insert  [Optional] Insert nets
    *
    * @throws Exception  If a connection error occurred
    *
    * @return void
    */
    protected static function set_host_net_reference($conn, $id, $delete = TRUE, $insert = TRUE)
    {
        Ossim_db::check_connection($conn);
        
        $params = array($id);
        
        if ($delete == TRUE) 
        {            
            $query  = 'DELETE FROM alienvault.host_net_reference WHERE net_id = UNHEX(?)';
                                    
            $rs = $conn->Execute($query, $params);
            
            if (!$rs) 
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }        
        }
                
        if ($insert == TRUE) 
        {            
            $query  = 'REPLACE INTO host_net_reference SELECT host.id, net_id 
                FROM host, host_ip, net_cidrs WHERE host.id = host_ip.host_id 
                AND host_ip.ip >= net_cidrs.begin 
                AND host_ip.ip <= net_cidrs.end 
                AND net_id = UNHEX(?)';
                                    
            $rs = $conn->Execute($query, $params);         
                                    
            if (!$rs) 
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }         
        }  
    }
    
      
    /**
    * This function returns an expanded CIDRs
    *
    * @param string  $ips            Comma-separated CIDRs
    * @param string  $output_type    [Optional] Output type (SHORT (IP min and IP max) or FULL (All ips))
    * @param string  $output_format  [Optional] Output format (IP or LONG (numeric))
    *
    * @return array      
    */ 
    public static function expand_cidr($ips, $output_type = 'FULL', $output_format = 'IP')
    {            
        $ip_range = array();
        
        $ips = explode(',', $ips);
        
        if (!is_array($ips) || empty($ips))
        {
            return $ip_range;
        }
                               
        foreach($ips as $ip_cidr)
        {
            $ip_cidr = trim($ip_cidr);
            $cidr_range = Cidr::expand_CIDR($ip_cidr, $output_type, $output_format);                                
                                                   
            if (is_array($cidr_range) && !empty($cidr_range))
            {
                
                if ($output_type == 'SHORT')
                {
                    if ($output_format == 'LONG')
                    {
                        $ip_range[$ip_cidr][0] = ($cidr_range[0] < $ip_range[$ip_cidr][0] || $ip_range[$ip_cidr][0] < 1) ? $cidr_range[0] : $ip_range[$ip_cidr][0];
                        $ip_range[$ip_cidr][1] = ($cidr_range[1] >= $ip_range[$ip_cidr][1]) ? $cidr_range[1] : $ip_range[$ip_cidr][1];
                    }
                    else
                    {
                        $ip_range[$ip_cidr] = $cidr_range;
                    }
                    
                }
                else
                {
                    $ip_range[$ip_cidr] = array_flip($cidr_range);
                }     
            }            
        }
        
        return $ip_range;
    }

    
    /**
    * This function returns a SQL condition for filtering nets 
    *
    * @param string  $alias     [Optional] MySQL alias
    * @param string  $with_ctx  [Optional] Use context in filter
    *
    * @return string      
    */ 
    public static function get_perms_where($alias = '', $with_ctx = TRUE) 
    {                
        $query      = '';
                       
        $ctx_where  = Session::get_ctx_where();
        $net_where  = Session::get_net_where();
        
                
        if ($with_ctx == TRUE && $ctx_where != '')
        {
            $query .= ' AND '.$alias.'ctx IN ('.$ctx_where.')';
        }    
                
        if ($net_where != '')
        {                        
            $query .= ' AND '.$alias.'id IN ('.$net_where.')';
        }          
           
        return $query;
    }
    
    
    /**
    * This function checks if $ips is a valid CIDR
    *    
    * @param string $ips  CIDR
    *
    * @return boolean      
    */  
    public static function valid_cidr($ips) 
    {        
        if (preg_match('/^(([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\/([1-9]|[1-2][0-9]|3[0-2])$/', $ips))
        {
            return TRUE;
        }   
            
        return FALSE;
    }   
    
       
    /**
    * This function returns an net object
    *    
    * @param object  $conn   Database access object
    * @param string  $id     Net ID
    * @param boolean $cache  [Optional] Use cached information
    *
    * @throws Exception  If a connection error occurred
    *
    * @return object      
    */   
    public static function get_object($conn, $id, $cache = FALSE)
    {        
        Ossim_db::check_connection($conn);        
        
        $net = NULL;
                
        $params = array($id); 
        $query  = 'SELECT HEX(id) AS id FROM net WHERE id = UNHEX(?)';
        
        $rs = ($cache) ? $conn->CacheExecute($query, $params) : $conn->Execute ($query, $params);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
                
        if (!$rs->EOF) 
        {
            $net = new self($id);
            $net->load_from_db($conn, $cache);          
        }
        
        return $net;
    }
    
    
    /**
    * This function checks if net exists
    *
    * @param object  $conn   Database access object
    * @param string  $id     Net ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean      
    */ 
    public static function is_in_db($conn, $id)
    {       
        Ossim_db::check_connection($conn);
        
        $res = FALSE;
        
        if(!valid_hex32($id))
        {
            return $res;
        }
                
        $params = array($id);
        $query  = 'SELECT count(*) AS found FROM net WHERE id = UNHEX(?)';
                
        $rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        
        if (intval($rs->fields['found']) > 0)
        {
            $res = TRUE;
        }
        
        return $res;    
    }
    
    
    /**
    * This function checks if net exists and it is allowed
    *
    * @param object  $conn   Database access object
    * @param string  $id     Net ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean      
    */
    public static function is_allowed($conn, $id) 
    {        
        Ossim_db::check_connection($conn);
        
        return Session::netAllowed($conn, $id);      
    }
    
        
    /**
    * This function deletes the net from database
    *
    * @param object   $conn            Database access object
    * @param string   $id              Net ID
    * @param boolean  $report_changes  [Optional] Report changes to other components
    *
    * @throws Exception  If an error occurred
    *
    * @return array      
    */ 
    public static function delete_from_db($conn, $id, $report_changes = TRUE)
    {               
        Ossim_db::check_connection($conn);
        
        if (!self::can_delete($conn, $id))
        {        
            $exp_msg = _('Sorry, this net can not be deleted because it belongs to a policy');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
                              
                                
        //Begin transaction
        $conn->StartTrans();     
                             
        //Sensors                      
        Asset_net_sensors::delete_all_from_db($conn, $id);
        
        //Cached CIDRs
        self::delete_cidrs_from_cache($conn, $id);
                     
        //Scan
        Asset_net_scan::delete_all_from_db($conn, $id);          
                   
        //Metrics
        Control_panel_net::delete($conn, $id);
        
        //Net network reference
        self::set_host_net_reference($conn, $id, TRUE, FALSE);

         
        $queries = array();
        $params  = array($id);
        
        
        //Riskmaps        
        $queries[] = 'DELETE s FROM bp_asset_member a,bp_member_status s WHERE s.member_id = a.member AND a.member = UNHEX(?)';
        $queries[] = "DELETE FROM bp_asset_member WHERE member = UNHEX(?) AND type='net'";
        
        //KDB
        $queries[] = 'DELETE FROM repository_relationships WHERE keyname = ?';
        
        //Qualification (Compromise and attack)     
        $queries[] = 'DELETE FROM net_qualification WHERE net_id = UNHEX(?)';
        
        //Net groups
        $queries[] = 'DELETE FROM net_group_reference WHERE net_id = UNHEX(?)'; 

        //Net vulnerabilities
        $queries[] = 'DELETE FROM net_vulnerability WHERE net_id = UNHEX(?)';
        
        //Net
        $queries[] = 'DELETE FROM net WHERE id = UNHEX(?)';      
         
             
        foreach ($queries as $query)
        {            
            $rs = $conn->Execute($query, $params);      
        
            if (!$rs) 
            {           
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
        }
        
        //Finish transaction
        if ($conn->CompleteTrans())
        {
            //Log action
            $infolog = array($id);
            Log_action::log(29, $infolog);
            
            if ($report_changes == TRUE)
            {
                try
                {
                    self::report_changes($conn, 'nets');
                }
                catch(Exception $e)
                {
                    Av_exception::write_log(Av_exception::DB_ERROR, $e->getMessage());
                }
            }                   
        }
        else
        {           
            $exp_msg = _('Error! Net could not be deleted');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }   
                       
        return TRUE;        
    }    
    
    
    /**
    * This function deletes net icon from database
    *
    * @param object  $conn   Database access object
    * @param string  $id     Net ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean      
    */ 
    public static function delete_icon($conn, $id)
    {       
        Ossim_db::check_connection($conn);
                
        $query  = 'UPDATE net SET icon = NULL WHERE id = UNHEX(?)';
        $params = array($id);
        
        $rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
                        
        return TRUE;    
    }
    
                
    /**
    * This function returns closest nets to the net
    *
    * @param object  $conn   Database access object
    * @param string  $ips    CIDRs
    * @param string  $ctx    [Optional] Net context
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array      
    */ 
    public static function get_closest_nets($conn, $ips, $ctx = '')   
    {
        Ossim_db::check_connection($conn);        
        
        $nets = array();
                
        $cidrs = explode(',', $ips);
        
        if (!is_array($cidrs) || empty($cidrs))
        {
            return $nets;
        } 
        
        // Permissions
        $perms_where = self::get_perms_where('net.', TRUE);        
        
        $ctxs = (preg_match('/unhex/i', $ctx) == TRUE) ? $ctx : "UNHEX('".str_replace(',', "'), UNHEX('",$ctx)."')";
            
        foreach ($cidrs as $cidr)
        {
            if ($ctx !=  '') 
            {
                $query = "SELECT HEX(net_id) AS net_id, net_cidrs.cidr, net.ips, CONV(HEX(begin),16,10) AS begind, CONV(HEX(end), 16, 10) AS endd
                    FROM net_cidrs, net 
                    WHERE net.id = net_cidrs.net_id $perms_where 
                    AND net.ctx IN ($ctxs) 
                    AND INET6_PTON(?) >= net_cidrs.begin 
                    AND INET6_PTON(?) <= net_cidrs.end 
                    ORDER BY endd-begind";
            }
            else
            {
                $query = "SELECT HEX(net_id) AS net_id, net_cidrs.cidr, net.ips, CONV(HEX(begin),16,10) AS begind, CONV(HEX(end), 16, 10) AS endd
                    FROM net_cidrs, net 
                    WHERE net.id = net_cidrs.net_id $perms_where 
                    AND INET6_PTON(?) >= net_cidrs.begin 
                    AND INET6_PTON(?) <= net_cidrs.end 
                    ORDER BY endd-begind";
            }
                                                
                            
            $ip_range = self::expand_cidr(trim($cidr), 'SHORT', 'IP');
                                
            $params = array($ip_range[$cidr][0], $ip_range[$cidr][1]);    
            
            $rs = $conn->Execute($query, $params);

            if (!$rs) 
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }         
                     
            while (!$rs->EOF)
            {                                
                $n_key = $rs->fields['net_id'].'-'.$rs->fields['cidr'];
                
                $nets[$n_key] = array(
                  'id'    => $rs->fields['net_id'],
                  'cidr'  => $rs->fields['cidr'],
                  'ips'   => $rs->fields['ips'],
                  'begin' => $rs->fields['begind'],
                  'end'   => $rs->fields['endd']
                );                
                
                $rs->MoveNext();
            }    
        }
        
        return $nets;
    }


    /************************************************************************************************/
    /************************************************************************************************/
    /************************************    STATIC FUNCTIONS    ************************************/
    /************************************************************************************************/
    /************************************************************************************************/


    /**
    * This function deletes all networks in database filtered by optional query search
    *
    * @param object $conn
    * @param array  $filters
    *
    * @return boolean
    */
    public static function delete_all_from_db($conn, $filters = array())
    {
        Ossim_db::check_connection($conn);
        
        Util::disable_perm_triggers($conn, TRUE);
        
        // Create tmp table
        $tmp_table = Util::create_tmp_table($conn,"net_id binary(16) NOT NULL, PRIMARY KEY (net_id)");
    
    
        //Populate tmp table adding filtered networks which are not included in policies
    
        $join    = 'LEFT JOIN policy_net_reference pnr ON pnr.net_id = net.id';
        $q_where = 'pnr.net_id IS NULL';
    
        if ($filters['where'] != '')
        {
            $q_where .= ' AND ('.$filters['where'].')';
        }
    
        $query = ossim_query("INSERT INTO $tmp_table (net_id) SELECT id
                FROM net $join WHERE $q_where");
    
        $conn->SetFetchMode(ADODB_FETCH_ASSOC);
    
        $rs = $conn->Execute($query);
    
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    
        //Check if there are networks belong some policies
        $tables = ', policy_net_reference pnr';
    
        $_filters = array(
                'where' => 'pnr.net_id = net.id',
                'limit' => 1
        );
    
        if ($filters['where'] != '')
        {
            $_filters['where'] .= ' AND ('.$filters['where'].')';
        }
    
        $_net_list            = self::get_list($conn, $tables, $_filters);
    
        $nets_belong_policies = ($_net_list[1] > 0) ? TRUE : FALSE;
    
        // Get IDs to delete
        $tmp_ids = array();
        $query   = "SELECT HEX(net_id) AS net_id FROM $tmp_table";
        $rs      = $conn->Execute($query);
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        while (!$rs->EOF)
        {
            $tmp_ids[] = $rs->fields['net_id'];
            $rs->MoveNext();
        }
        
        // Delete sequence
        // Begin transaction
        $conn->StartTrans();
    
        // Delete statements (Method callings to delete)
        foreach ($tmp_ids as $_net_id)
        {
            //Sensors
            Asset_net_sensors::delete_all_from_db($conn, $_net_id);
            
            //Cached CIDRs
            self::delete_cidrs_from_cache($conn, $_net_id);
             
            //Scan
            Asset_net_scan::delete_all_from_db($conn, $_net_id);
             
            //Metrics
            Control_panel_net::delete($conn, $_net_id);
            
            //Net network reference
            self::set_host_net_reference($conn, $_net_id, TRUE, FALSE);
        }
    
        // Delete statements (Queries to delete)
        $queries   = array();
        $queries[] = "DELETE n.* FROM bp_member_status n,         $tmp_table f WHERE f.net_id = n.member_id";
        $queries[] = "DELETE n.* FROM bp_asset_member n,          $tmp_table f WHERE f.net_id = n.member AND n.type = 'net'";
        $queries[] = "DELETE n.* FROM repository_relationships n, $tmp_table f WHERE f.net_id = UNHEX(n.keyname)";
        $queries[] = "DELETE n.* FROM net_qualification n,        $tmp_table f WHERE f.net_id = n.net_id";
        $queries[] = "DELETE n.* FROM net_group_reference n,      $tmp_table f WHERE f.net_id = n.net_id";
        $queries[] = "DELETE n.* FROM net_vulnerability n,        $tmp_table f WHERE f.net_id = n.net_id";
        $queries[] = "DELETE n.* FROM net n,                      $tmp_table f WHERE f.net_id = n.id";
    
        foreach ($queries as $query)
        {
            $rs = $conn->Execute($query);
    
            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
        }
    
        //Finish transaction
        if ($conn->CompleteTrans())
        {
            //Log action
            $infolog = array(_('Policy - Networks deleted massively'));
            Log_action::log(92, $infolog);
    
            Util::memcacheFlush();

            Util::disable_perm_triggers($conn, FALSE);
    
            // Networks belong a policy
            if ($nets_belong_policies == TRUE)
            {
                $exp_msg = _('Sorry, some networks could not be deleted because they belong to a policy');
    
                Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
            }
            
        }
        else
        {
            $exp_msg = _('Error! Networks could not be deleted');
    
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
    
        return TRUE;    
    }

    
        
    /**
    * This function checks if CIDR belongs to my nets
    *
    * @param object  $conn   Database access object
    * @param string  $ips    Comma-separated CIDRs
    * @param string  $ctx    [Optional] Net Context
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean    
    */ 
    public static function is_cidr_in_my_nets($conn, $ips, $ctx = '') 
    {    
        $nets = self::get_closest_nets($conn, $ips, $ctx);
                     
        return (count($nets) > 0) ? TRUE : FALSE;
    }
     
    
    /**
    * This function returns true if CIDR could be modified
    *
    * @param object  $conn   Database access object
    * @param string  $id     Net ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean    
    */
    public static function can_i_modify_ips($conn, $id) 
    {
        Ossim_db::check_connection($conn); 
        
        if (Session::am_i_admin() || Session::get_net_where() == '')
        {
            return TRUE;
        }
        
        
        $cidrs = self::get_ips_by_id($conn, $id);
                                                    
        if (!empty($cidrs))
        {
            $cidrs     = explode(',', $cidrs);
            $num_cidrs = count($cidrs);
        }
        else
        {
            return FALSE;
        }
        
        $perms_where = self::get_perms_where('c3.', TRUE);  
            
        $query = "SELECT DISTINCT HEX(c1.net_id), c1.cidr   
            FROM net_cidrs c1, net_cidrs c2, net c3
            WHERE c1.net_id=UNHEX(?) 
            AND c1.begin >= c2.begin AND c1.end <= c2.end AND c1.net_id <> c2.net_id 
            AND c2.net_id = c3.id $perms_where";
        
        $params = array($id);       
        
        $rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
                
        
        if ($rs->RecordCount() >= $num_cidrs)
        {
            return TRUE;
        }
                
        return FALSE;
    }
    
    
    /**
    * This function returns the subnets that belongs to network
    *
    * @param object  $conn   Database access object
    * @param string  $id     Net ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array    
    */
    public static function get_my_subnets($conn, $id)
    {       
        Ossim_db::check_connection($conn); 
           
        $nets = array();
        
        $query = 'SELECT HEX(n1.net_id) AS net_id, n1.cidr, INET6_NTOP(n1.end) as end, INET6_NTOP(n1.begin) as begin
            FROM net ne, net_cidrs n, net ne1, net_cidrs n1 
            WHERE ne.id = n.net_id AND ne1.id = n1.net_id 
            AND n.begin <= n1.begin 
            AND n1.end <= n.end AND ne.ctx = ne1.ctx 
            AND n.net_id = UNHEX(?) AND n1.net_id != UNHEX(?);';
        
        $params = array($id, $id);
            
        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
                
        while (!$rs->EOF)
        {
            $nets[$rs->fields['net_id']] = array(
              'id'    => $rs->fields['net_id'],
              'ips'   => $rs->fields['cidr'],
              'begin' => $rs->fields['begin'],
              'end'   => $rs->fields['end']
            );                
            
            $rs->MoveNext();
        }       
        
        return $nets;
    }
          
    
    /**
    * This function returns the name from net
    *
    * @param object  $conn   Database access object
    * @param string  $id     Net ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return string      
    */    
    public static function get_name_by_id($conn, $id)
    {       
        Ossim_db::check_connection($conn);        
            
        $name = NULL;
                        
        $query  = 'SELECT name FROM net WHERE id = UNHEX(?)';
                                      
        $params = array($id);
        
        $rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        
        if ($rs->fields['name'] != '') 
        {
            $name = $rs->fields['name'];
        }
     
        return $name;   
    }
    
    
    /**
    * This function returns the Net ID from net name
    *
    * @param object  $conn   Database access object
    * @param string  $name   Net name
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array      
    */ 
    public static function get_id_by_name($conn, $name) 
    {               
        Ossim_db::check_connection($conn);      
        
                
        $net_ids = array();        
                               
        $query = 'SELECT HEX(id) AS id, HEX(ctx) AS ctx FROM net WHERE name = ?';
        
        $params = array($name); 

        $rs = $conn->Execute($query, $params);        
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
               
        while (!$rs->EOF)
        {               
            $net_ids[$rs->fields['id']] = $rs->fields['ctx']; 
            
            $rs->MoveNext();                            
        }
        
        return $net_ids;   
    }
    
    
    /**
    * This function returns the context from allowed net 
    *
    * @param object  $conn   Database access object
    * @param string  $id     Net ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return string      
    */ 
    public static function get_ctx_by_id($conn, $id)
    {               
        Ossim_db::check_connection($conn);
                       
        $ctx = '';   
        
        $query  = 'SELECT HEX(ctx) AS ctx FROM net WHERE id = UNHEX(?)';
        $params = array($id);
        
        $rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }        
                    
        if (valid_hex32($rs->fields['ctx']))
        {
            $ctx = $rs->fields['ctx'];
        } 
        
        return $ctx;    
    }
    
    
    /**
    * This function returns the Net ID from CIDRs and context
    *
    * @param object  $conn   Database access object
    * @param string  $ips    Comma-separated CIDRs
    * @param string  $ctx    [Optional] Net context
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_id_by_ips($conn, $ips, $ctx = '')
    {
        Ossim_db::check_connection($conn);
        
        $net_ids = array();
        
        if (!empty($ips))
        {        
            $ips_list  = explode(',', $ips);
            sort($ips_list);
            $ips_list  = array_map('trim', $ips_list);
            
            $ips_where = "'".implode("','", $ips_list)."'";
        }
        else
        {
            return $net_ids;
        }
        
        $perms_where = self::get_perms_where('net.', TRUE);
        
        $params = array();                
                              
        $query = "SELECT DISTINCT HEX(net_id) AS id, HEX(net.ctx) AS ctx, net.ips, 
            CONV(HEX(begin),16,10) AS begind, CONV(HEX(end), 16, 10) AS endd
            FROM net_cidrs,net 
            WHERE net.id = net_cidrs.net_id $perms_where AND cidr IN ($ips_where)";
            
        if ($ctx != '')
        {
            $query .= ' AND ctx = UNHEX(?)';
            $params[]   = $ctx;
        }
        
        $query .= ' ORDER BY endd-begind';
                        
        $rs = $conn->Execute ($query, $params);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
                    
        while (!$rs->EOF)
        {
            $ips_2 = explode(',', $rs->fields['ips']);
            sort($ips_2);

            if ($ips_list === $ips_2)
            {          
                $net_ids[$rs->fields['id']][$rs->fields['ctx']][$rs->fields['ips']] = $rs->fields['ips'];  
            }
            
            $rs->MoveNext();
        }       
                
        return $net_ids;
    }
    
    
    /**
    * This function returns the netname/s from CIDR/Ip
    *
    * @param object  $conn   Database access object
    * @param string  $ips    CIDRs
    * @param string  $ctx    [Optional] Net context
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_name_by_ips($conn, $ips, $ctx = '')
    {
        Ossim_db::check_connection($conn);
        
        $names   = array();
        
        if (!empty($ips))
        {
            $ips = explode(',', $ips);
            sort($ips);
            $ips  = array_map('trim', $ips);
        }
        else
        {
            return $names;
        }
        
        $first_ip = $ips[0];
                        
        $query   = 'SELECT name, ips, HEX(id) AS id FROM net WHERE (ips LIKE ? OR ips LIKE ? OR ips LIKE ? OR ips = ?)';
        
        $params  = array($first_ip . ',%', '%,' . $first_ip . ',%', '%,' . $first_ip, $first_ip);
        
        if (!empty($ctx))
        {
            $query    .= ' AND ctx = UNHEX(?)';
            $params[]  = $ctx;
        }
                        
        $rs = $conn->Execute($query, $params);
    
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }               
                
        while (!$rs->EOF)
        {
            $ips_2 = explode(',', $rs->fields['ips']);
            sort($ips_2);
            
            if ($ips === $ips_2)
            {
                $names[$rs->fields['id']] = $rs->fields['name'];
            }
            
            $rs->MoveNext();
        }               
                        
        return $names;      
    }
    
    /**
    * This function returns the CIDRs from net
    *
    * @param object  $conn   Database access object
    * @param string  $id     Net ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return string
    */
    public static function get_ips_by_id($conn, $id) 
    {
        Ossim_db::check_connection($conn);
                       
        $ips = NULL;        
               
        $query  = 'SELECT ips FROM net WHERE id = UNHEX(?)';
        $params = array($id);
        
        $rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }        
                    
        if ($rs->fields['ips'] != '')
        {
            $ips = preg_replace("/[\r\n\t]+/", '', $rs->fields['ips']);
        } 
        
        return $ips;    
    }
    
    
    /**
    * This function returns true if CIDRs could be scanned using sensor $sensor_id 
    *
    * @param object  $conn       Database access object
    * @param string  $ips        Comma-separated CIDRs
    * @param string  $sensor_id  Sensor ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean
    */ 
    public static function check_cidr_by_sensor($conn, $ips, $sensor_id) 
    {               
        Ossim_db::check_connection($conn);
        
        $cidrs = explode(',', $ips);
                        
        // Permissions
        $perms_where = self::get_perms_where('net.', TRUE); 
        
        if (is_array($cidrs) && !empty($cidrs)) 
        {  
            foreach ($cidrs as $cidr)
            {
                $query = "SELECT HEX(net_cidrs.net_id) AS id, CONV(HEX(begin),16,10) AS begind, CONV(HEX(end), 16, 10) AS endd
                    FROM net_cidrs, net, net_sensor_reference
                    WHERE net.id = net_sensor_reference.net_id 
                    AND net.id = net_cidrs.net_id 
                    $perms_where 
                    AND INET6_PTON(?) >= net_cidrs.begin
                    AND net_cidrs.end <= INET6_PTON(?)
                    AND net_sensor_reference.sensor_id = UNHEX(?)
                    ORDER BY endd-begind";

                                                
                $ip_range = self::expand_cidr(trim($cidr), 'SHORT', 'IP');
                                
                $params = array($ip_range[$cidr][0], $ip_range[$cidr][1], $sensor_id);               
                
                $rs = $conn->Execute($query, $params);
        
                if (!$rs) 
                {
                    Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
                } 
                                
                if ($rs->RecordCount() < 1)
                {
                    return FALSE;
                }               
            }
        }
        
        return TRUE;
    }
           
    
    /**
    * This function returns list with nets associated to sensor $sensor_id 
    *
    * @param object  $conn       Database access object
    * @param string  $sensor_id  Sensor ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_nets_by_sensor($conn, $sensor_id)
    {
        Ossim_db::check_connection($conn);
        
        $nets = array();
                                        
        // Permissions
        $perms_where = self::get_perms_where('net.', TRUE);
        
        $query = "SELECT DISTINCT net.*, HEX(net.id) AS id, HEX(net.ctx) AS ctx
            FROM net, net_sensor_reference
            WHERE net.id = net_sensor_reference.net_id 
            AND net_sensor_reference.sensor_id = UNHEX(?) $perms_where";
        
        $params = array($sensor_id);    
               
        $rs = $conn->Execute($query, $params);

        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        while (!$rs->EOF)
        {
            $nets[$rs->fields['id']] = array (
                'ctx'         => $rs->fields['ctx'],
                'name'        => $rs->fields['name'],
                'ips'         => $rs->fields['ips'], 
                'descr'       => $rs->fields['descr'], 
                'icon'        => $rs->fields['icon'], 
                'external'    => $rs->fields['external_net'],
                'asset_value' => $rs->fields['asset'],        
                'threshold_c' => $rs->fields['threshold_c'], 
                'threshold_a' => $rs->fields['threshold_a'] 
            );
            
            $rs->MoveNext();
        }
        
        return $nets;   
    }
    
            
    /**
    * This function returns a filtered net list (For trees and autocomplete widget)
    *
    * @param object   $conn     Database access object
    * @param string   $tables   [Optional] Database tables separated by comma (Join with main table)
    * @param array    $filters  [Optional] SQL statements (WHERE, LIMIT, ORDER BY ...)
    * @param boolean  $cache    [Optional] Use cached information
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array      
    */ 
    public static function get_list_tree($conn, $tables = '', $filters = array(), $cache = FALSE) 
    {       
        Ossim_db::check_connection($conn);
        
        $nets  = array();            
        
        //Build SQL
                
        $perms_where = self::get_perms_where('net.', TRUE);             
       
        $q_where  = 'WHERE 1=1 '.$perms_where;
        

        if (!empty($filters['where']))
        {
            $q_where  .= 'AND '.$filters['where'];
        }
        
        if (!empty($filters['order_by']))
        {
            $q_where  .= ' ORDER BY '.$filters['order_by'];
        }                
         
        // First count to do block requests
        $query = ossim_query("SELECT count(DISTINCT net.id) AS total FROM net $tables $q_where");
        
        $conn->SetFetchMode(ADODB_FETCH_ASSOC);
        
        $rf    = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);
             
        if (!$rf) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        
        $foundrows = $rf->fields['total'];
            
        $block = 2000;
        $rf->Free();   
        
            
        for ($i = 0; $i <= $foundrows; $i += $block) 
        {    
            $query = ossim_query("SELECT DISTINCT net.name, net.ips, HEX(net.id) AS n_id, HEX(net.ctx) AS n_ctx
                FROM net $tables 
                $q_where
                LIMIT $i, $block");
            
            $rs = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);
                                
            if (!$rs) 
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }             
            
            while (!$rs->EOF)
            {
                $nets[] = array (
                    $rs->fields['n_id'],
                    $rs->fields['n_ctx'],
                    $rs->fields['ips'],
                    $rs->fields['name']
                );
            
                $rs->MoveNext();
            }  
            
            $rs->Free();
        }    
        
        return $nets;
    }
    
    
    /**
    * This function returns all nets
    *
    * @param object  $conn   Database access object
    * @param boolean $cache  [Optional] Use cached information
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array      
    */ 
    public static function get_all($conn, $cache = FALSE) 
    {       
        $nets  = array();
                    
        // First count to do block requests
        $query = 'SELECT count(id) AS total FROM net';
        
        $conn->SetFetchMode(ADODB_FETCH_ASSOC);
        
        $rf    = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);
             
        if (!$rf) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        
        $foundrows = $rf->fields['total'];
            
        $block = 2000;
        $rf->Free();   
        
            
        for ($i = 0; $i <= $foundrows; $i += $block) 
        {    
            $query = "SELECT *, HEX(ctx) AS n_ctx, HEX(id) AS n_id 
                FROM net 
                ORDER BY name ASC 
                LIMIT $i, $block";
            
            $rs = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);
                                
            if (!$rs) 
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }             
            
            while (!$rs->EOF)
            {
                $nets[$rs->fields['n_id']] = array (
                    'ctx'         => $rs->fields['n_ctx'],
                    'name'        => $rs->fields['name'],
                    'ips'         => $rs->fields['ips'], 
                    'descr'       => $rs->fields['descr'], 
                    'icon'        => $rs->fields['icon'], 
                    'external'    => $rs->fields['external_net'],
                    'asset_value' => $rs->fields['asset'],        
                    'threshold_c' => $rs->fields['threshold_c'], 
                    'threshold_a' => $rs->fields['threshold_a'],
                    'owner'       => $rs->fields['owner']
                );
            
                $rs->MoveNext();
            }  
            
           $rs->Free();
        }    
        
        return $nets;
    }

    
    /**
    * This function returns a filtered net list
    *
    * @param object   $conn     Database access object
    * @param string   $tables   [Optional] Database tables separated by comma (Join with main table)
    * @param array    $filters  [Optional] SQL statements (WHERE, LIMIT, ORDER BY ...)
    * @param boolean  $cache    [Optional] Use cached information
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array      
    */ 
    public static function get_list($conn, $tables = '', $filters = array(), $cache = FALSE)
    {
        Ossim_db::check_connection($conn);
                      
        $nets  = array();
        
        $total = 0;
        
        
        //Build SQL
                
        $perms_where = self::get_perms_where('net.', TRUE);
               
        $q_select = 'net.*';
        $q_where  = 'WHERE 1=1 '.$perms_where;
        

        if (!empty($filters['where']))
        {
            $q_where  .= 'AND '.$filters['where'];
        }
        
        if (!empty($filters['order_by']))
        {
            $q_where  .= ' ORDER BY '.$filters['order_by'];
        }
        
        if (!empty($filters['limit']))
        {
            $q_select  = 'SQL_CALC_FOUND_ROWS net.*';
            $q_where  .= ' LIMIT '.$filters['limit'];
        }
                        
        $conn->SetFetchMode(ADODB_FETCH_ASSOC);
                         
        // Has LIMIT      
        if (!empty($filters['limit'])) 
        {
            $query = "SELECT DISTINCT $q_select, HEX(net.id) AS n_id, HEX(net.ctx) AS n_ctx 
                FROM net $tables $q_where";
                
            //echo $query;
                                                                        
            $rs = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);
        
            if (!$rs) 
            {           
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            } 
            
            $total = Ossim_db::get_found_rows($conn, $query, $cache);
                       
            while (!$rs->EOF)
            {        
                $id = $rs->fields['n_id'];                                                            
                           
                $nets[$id] = array(
                    'id'          => $id,
                    'ctx'         => $rs->fields['n_ctx'],
                    'name'        => $rs->fields['name'],
                    'ips'         => $rs->fields['ips'],
                    'descr'       => $rs->fields['descr'],
                    'icon'        => $rs->fields['icon'],                        
                    'external'    => $rs->fields['external_net'],
                    'asset_value' => $rs->fields['asset'],
                    'threshold_c' => $rs->fields['threshold_c'],
                    'threshold_a' => $rs->fields['threshold_a'],
                    'owner'       => $rs->fields['owner']                 
                );                 
              
                $rs->MoveNext();
            }
        }
        else
        {            
            $counter_name = ($cache) ? 'total_'.md5($query) : 'total';
             
            // First count to do block requests                                  
            $query = ossim_query("SELECT count(DISTINCT net.id) AS $counter_name FROM net $tables $q_where");
                     
            $rf    = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);
                 
            if (!$rf) 
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
            
            $total = $rf->fields[$counter_name];
                                        
            $block = 2000;
            $rf->Free();   
               
            for ($i = 0; $i <= $total; $i += $block)
            {    
                $query = ossim_query("SELECT DISTINCT $q_select, HEX(net.id) AS n_id, HEX(net.ctx) AS n_ctx FROM net $tables $q_where LIMIT $i, $block");
                                                
                $rs = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);
                                    
                if (!$rs) 
                {
                    Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
                }             
                
                while (!$rs->EOF)
                {                                   
                    $id = $rs->fields['n_id'];                    
                               
                    $nets[$id] = array(
                        'id'          => $id,
                        'ctx'         => $rs->fields['n_ctx'],
                        'name'        => $rs->fields['name'],
                        'ips'         => $rs->fields['ips'],
                        'descr'       => $rs->fields['descr'],
                        'icon'        => $rs->fields['icon'],                        
                        'external'    => $rs->fields['external_net'],
                        'asset_value' => $rs->fields['asset'],
                        'threshold_c' => $rs->fields['threshold_c'],
                        'threshold_a' => $rs->fields['threshold_a'],
                        'owner'       => $rs->fields['owner']         
                    );                 
                
                    $rs->MoveNext();
                }
                
                $rs->Free();
            }  
        }
        
        return array($nets, $total);
    }        
}

/* End of file asset_net.inc */
/* Location: ../include/classes/asset_net.inc */ 