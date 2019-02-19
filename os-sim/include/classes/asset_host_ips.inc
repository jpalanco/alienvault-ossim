<?php
/**
* asset_host_ips.inc
*
* File asset_host_ips.inc is used to:
*   - To manage host IPs and MACs
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


/**
* Asset_host_ips Class
*
* Class for managing host IPs (and MACs)
*
* @package    ossim-framework\Asset
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/

class Asset_host_ips
{    
    /**
    * Host ID
    *
    * @var string
    * @access private
    */ 
    private $host_id;
    
    /**
    * Host IP list
    *
    * @var array
    * @access private
    */ 
    private $ips;
    
    /**
    * Host Mac list
    *
    * @var array
    * @access private
    */ 
    private $macs;
        
      
    /**
    * Class constructor
    *
    * This function sets up the class
    *
    * @param string  $host_id  Host ID  
    */    
    public function __construct($host_id)
    {
        $this->set_host_id($host_id);
        $this->macs = array();
        $this->ips  = array();
    }

    
    /**
    * This function returns the host ID
    *
    * @return string
    */
    public function get_host_id()
    {
        return $this->host_id;
    }
    
    
    /**
    * This function returns the host IP list
    *
    * @param string $format  [Optional] Output format (array or string)
    *
    * @return array|string
    */      
    public function get_ips($format = 'array')
    {
        $ips = $this->ips;
        
        if ($format == 'string')
        {
            $_ips = array_keys($ips);
            
            $ips = '';
            
            if (is_array($_ips) && !empty($_ips))
            {
                $ips = implode (', ', $_ips);
            }
        }
        
        return $ips;
    }
    
    
    /**
    * This function returns the host MAC list
    *
    * @return array
    */      
    public function get_macs($format = 'array')
    {        
        $macs = $this->macs;
        
        if ($format == 'string')
        {
            $_macs = array_keys($macs);
            
            $macs = '';
            
            if (is_array($_macs) && !empty($_macs))
            {
                $macs = implode (', ', $_macs);
            }
        }
        
        return $macs;
    }
               
    
    /**
    * This function sets the host ID
    *
    * @param string $host_id  Host ID
    *
    * @throws Exception  If host ID is not valid
    *
    * @return void
    */
    public function set_host_id($host_id)
    {
        if (!empty($host_id) && valid_hex32($host_id))
        {
            $this->host_id = strtoupper($host_id);
        }
        else
        {
            $exp_msg = _('Error! Asset ID is not valid');
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
    }
    
    
    /**
    * This function sets IP and MAC list
    *
    * @param array $ips  Host IPs
    *
    * @return void
    */      
    public function set_ips_macs($ips_data)
    {
        $this->ips  = array();
        $this->macs = array();
            
        if (is_array($ips_data) && !empty($ips_data))
        {         
            foreach($ips_data as $ip_mac)
            {
                $this->add_new($ip_mac['ip'], $ip_mac['mac']);
            }
        }
    }
      
     
    /**
    * This function sets the host IPs and host MACs from database
    *
    * @param object  $conn   Database access object
    * @param boolean $cache  [Optional] Use cached information
    *
    * @throws Exception  If host ID doesn't exists in the System or there is a connection error
    *
    * @return void
    */       
    public function load_from_db($conn, $cache = FALSE) 
    {
        Ossim_db::check_connection($conn);        
                
        $query  = 'SELECT INET6_NTOA(ip) AS ip, HEX(mac) AS mac FROM host_ip WHERE host_id = UNHEX(?)';
        $params = array($this->host_id);
        
        $rs = ($cache == TRUE) ? $conn->CacheExecute($query, $params) : $conn->Execute($query, $params);
        
        if (!$rs) 
        {           
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }       
        
        while (!$rs->EOF) 
        {   
            $ip  = $rs->fields['ip'];
            $mac = $rs->fields['mac'];
            
            if (!empty($mac))
            {
                $mac = preg_replace("/(..)(..)(..)(..)(..)(..)/", "\\1:\\2:\\3:\\4:\\5:\\6", $mac);
            }   
            
            $this->add_new($ip, $mac);

            $rs->MoveNext();
        }
    }
    
        
    /**
    * This function adds a new host IP with its MACs
    *
    * @param string  $ip   Host IP
    * @param string  $mac  [Optional] Host Mac
    *
    * @return boolean      
    */ 
    public function add_new($ip, $mac = NULL)
    {        
        if (self::valid_ip($ip))
        {                       
            $this->ips[$ip] = array(
                'ip'  => $ip,
                'mac' => NULL,
            );
                        
            if (self::valid_mac($mac))
            {
                $this->ips[$ip]['mac'] = $mac;
                
                $this->macs[$mac] = array(
                    'ip'  => $ip,
                    'mac' => $mac
                );   
            }
            
            return TRUE;    
        }    
                     
        return FALSE;
    }

    
    /**
    * This function deletes the host IP
    *
    * @param string  $ip  Host IP
    *
    * @return boolean      
    */ 
    public function delete_ip($ip)
    {                      
        if (array_key_exists($ip, $this->ips))
        {
            $mac = $this->ips[$ip]['mac'];
            
            unset($this->ips[$ip]);
            
            if (!empty($mac))
            {
                unset($this->macs[$mac]);
            }          
                        
            return TRUE;
        }
        
        return FALSE;
    }
            
    
    /**
    * This function deletes the host MAC
    *
    * @param string  $mac  Host MAC
    *
    * @return boolean      
    */ 
    public function delete_mac($mac)
    {                      
        if (array_key_exists($mac, $this->macs))
        {
            $ip = $this->macs[$mac]['ip'];
            
            unset($this->macs[$mac]);
            $this->ips[$ip]['mac'] = NULL;
                        
            return TRUE;
        }
        
        return FALSE;
    }

    
    /**
    * This function saves all IPs and MACs into database
    *
    * @param object   $conn            Database access object
    * @param boolean  $report_changes  [Optional] Report changes to other components
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean
    */ 
    public function save_all_in_db($conn, $report_changes = FALSE)
    {
        Ossim_db::check_connection($conn);
                        
        //Begin transaction
        $conn->StartTrans(); 
        
        $host_id = $this->get_host_id();
        
        /*
            Special case: We only delete IPs if there are rows in host_ips.
            
            If there are not IPs, we have a problem related to permissions.
            We don't check permissions if we are inserting a new host and 
            IPs haven't been inserted yet.
            See function is_allowed and function set_host_net_reference.
            
        */        
        $last_ips = self::get_ips_to_string($conn, $host_id);
                
        if (!empty($last_ips))
        {
            self::delete_all_from_db($conn, $host_id);
        }            
        
        foreach($this->ips as $ip => $ip_data)
        {       
            if ($ip_data['mac'] != '')
            {
                $query  = 'REPLACE INTO host_ip (host_id, ip, mac) VALUES (UNHEX(?), UNHEX(?), UNHEX(?))';
                $params = array($host_id, bin2hex(inet_pton($ip)), str_replace(':', '', $ip_data['mac']));
            } 
            else
            {
                $query  = 'REPLACE INTO host_ip (host_id, ip) VALUES (UNHEX(?), UNHEX(?))';
                $params = array($host_id, bin2hex(inet_pton($ip)));
            }

            $conn->Execute($query, $params);
        }
        
        //Finish transaction
        if ($conn->CompleteTrans())
        {
            if ($report_changes == TRUE)
            {
                try
                {
                    Asset_host::report_changes($conn, 'hosts');
                }
                catch(Exception $e)
                {                   
                    Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
                }
            }
        }
        else
        {         
            $exp_msg = _('Error! IPs and MACs could not be saved');
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        } 
                
        return TRUE;
    }

        
        
    /*************************************************
     **************** Static functions ***************
     *************************************************/
     
    
    /**
    * This function validates one host IP
    *
    * @param string  $ip  Host IP
    *
    * @return boolean
    */ 
    public static function valid_ip($ip) 
    {
        $cnd_1 = ($ip != '0.0.0.0');
        $cnd_2 = ($ip != '255.255.255.255');
        $cnd_3 = (preg_match('/^(([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])$/', $ip) == TRUE);
              
        if ($cnd_1 && $cnd_2 && $cnd_3)
        {
            return TRUE;
        }   
            
        return FALSE;
    }
    
    
    /**
    * This function validates one host Mac
    *
    * @param string  $mac  Host Mac
    *
    * @return boolean
    */ 
    public static function valid_mac($mac) 
    {
        if (preg_match('/^[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}$/i', $mac) == TRUE)
        {
            return TRUE;
        }       
            
        return FALSE;
    }
    
    
    /**
    * This function returns the IP converted to numeric format
    *
    * @param string  $str_ip  Host IP
    *
    * @return string
    */ 
    public static function ip2ulong($str_ip) 
    {
        $tmp_long = ip2long($str_ip);
        
        if ($tmp_long < 0)
        {
            $tmp_long = 4294967296 - abs($tmp_long);
        }
        
        return $tmp_long;
    }
    
    
    /**
    * This function deletes all IPs and MACs associated with one host from database
    *
    * @param object    $conn            Database access object
    * @param string    $host_id         Host ID
    * @param boolean   $only_mac        [Optional] Delete only MAC Addresses, no IP addresses
    * @param boolean   $report_changes  [Optional] Report changes to other components
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean
    */ 
    public static function delete_all_from_db($conn, $host_id, $only_mac = FALSE, $report_changes = FALSE)
    {        
        Ossim_db::check_connection($conn);
                       
        //Check if we can delete the device
        if (Asset_host::is_allowed($conn, $host_id) != TRUE)
        {                         
            $exp_msg = _("Error! Asset does not exists or you do not have the correct permissions");
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        } 
                     
        if ($only_mac == TRUE)
        {
            //Delete only MAC addresses associated with the host
            $query  = 'UPDATE host_ip SET mac = NULL WHERE host_id = UNHEX(?)';
        }
        else
        {
            //Delete all IPs and MACs associated with the host
            $query  = 'DELETE FROM host_ip WHERE host_id = UNHEX(?)';
        }
        
        
        $params = array($host_id);
        
        $rs = $conn->Execute($query, $params);
            
        if (!$rs) 
        {           
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        if ($report_changes == TRUE)
        {
            try
            {
                Asset_host::report_changes($conn, 'hosts');
            }
            catch(Exception $e)
            {                
                Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
            }
        }
                            
        return TRUE;
    }
    
    
    /**
    * This function saves one MAC into database
    *
    * @param object   $conn            Database access object
    * @param string   $host_id         Host ID
    * @param string   $ip              Host IP
    * @param string   $mac             Host MAC
    * @param boolean  $report_changes  [Optional] Report changes to other components
    *
    * @throws Exception  If an error occurred
    *
    * @return array
    */ 
    public static function save_mac_in_db($conn, $host_id, $ip, $mac, $report_changes = FALSE)
    {             
        Ossim_db::check_connection($conn); 
        
        //Check if we can save the MAC
        if (Asset_host::is_allowed($conn, $host_id) != TRUE)
        {
            $exp_msg = _("Error! Asset does not exists or you do not have the correct permissions");
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
                       
        $host = Asset_host::get_object($conn, $host_id);
        $host_ips = $host->get_ips();
        
        $ips = $host_ips->get_ips();  

        
        $cnd_1 = self::valid_mac($mac);
        $cnd_2 = array_key_exists($ip, $ips);
               
        if ($cnd_1 && $cnd_2)
        {            
            $query  = 'REPLACE INTO host_ip (host_id, ip, mac) VALUES (UNHEX(?), UNHEX(?), UNHEX(?))';
            $params = array ($host_id, bin2hex(inet_pton($ip)), str_replace(':', '', $mac));
            
            $rs = $conn->Execute($query, $params);
            
            if (!$rs ) 
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            } 

            if ($report_changes == TRUE)
            {
                try
                {
                    Asset_host::report_changes($conn, 'hosts');
                }
                catch(Exception $e)
                {
                    Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());                    
                }
            } 
        }
        
        return TRUE;
    }
    
    
    /**
    * This function deletes one IP (and MAC) into database
    *
    * @param object   $conn            Database access object
    * @param string   $host_id         Host ID
    * @param string   $ip              Host IP
    * @param string   $mac             Host MAC
    * @param boolean  $report_changes  [Optional] Report changes to other components
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean
    */ 
    public static function delete_mac_from_db($conn, $host_id, $ip, $mac, $report_changes = FALSE)
    {
        Ossim_db::check_connection($conn);  
        
        //Check if we can save the MAC
        if (Asset_host::is_allowed($conn, $host_id) != TRUE)
        {
            $exp_msg = _("Error! Asset does not exists or you do not have the correct permissions");
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
             
        $host = Asset_host::get_object($conn, $host_id);
        
        $host_ips = $host->get_ips();
        $ips      = $host_ips->get_ips();
               
        $cnd_1 = self::valid_mac($mac);
        $cnd_2 = array_key_exists($ip, $ips);
                 
        if ($cnd_1 && $cnd_2)
        {           
            $query  = "UPDATE host_ip SET mac = UNHEX('NULL') 
                WHERE host_id = UNHEX(?) AND ip = UNHEX(?) AND mac = UNHEX(?)";
            
            $params = array ($host_id, bin2hex(inet_pton($ip)), str_replace(':', '', $mac));
            
            $rs = $conn->Execute($query, $params);      
                  
            if (!$rs) 
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            } 
                        
            if ($report_changes == TRUE)
            {
                try
                {
                    Asset_host::report_changes($conn, 'hosts');
                }
                catch(Exception $e)
                {
                    Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());                    
                }
            }   
        }
        
        return TRUE;
    }

    
    /**
    * This function returns a filtered host IPs and MACs list
    *
    * @param object   $conn     Database access object
    * @param array    $filters  [Optional] SQL statements (WHERE, LIMIT, ORDER BY ...)
    * @param boolean  $cache    [Optional] Use cached information
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */ 
    public static function get_list($conn, $filters = array(), $cache = FALSE)
    {
        Ossim_db::check_connection($conn); 
        
        $ips   = array();
        $macs  = array();   
             
        //Build SQL
                
        $perms_where = Asset_host::get_perms_where('h.', TRUE);       
        
        $q_where  =  $perms_where;
        
        
        if (!empty($filters['where']))
        {
            $q_where  .= ' AND '.$filters['where'];
        }

        if (!empty($filters['order_by']))
        {
            $q_where  .= ' ORDER BY '.$filters['order_by'];
        }
        
        if (!empty($filters['limit']))
        {
            $q_where  .= ' LIMIT '.$filters['limit'];
        }


        
        $query = ossim_query("SELECT HEX(host_ip.host_id) AS h_id, INET6_NTOA(host_ip.ip) AS ip, h.hostname, HEX(mac) AS mac 
           FROM host_ip, host h 
           WHERE host_ip.host_id = h.id $q_where");
        
        $conn->SetFetchMode(ADODB_FETCH_ASSOC);
                
        $rs = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);
                    
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        
        while (!$rs->EOF) 
        {        
            $ips[$rs->fields['h_id']][$rs->fields['ip']] = array(
                'ip'  => $rs->fields['ip'],
                'mac' => ''       
            );
                           
            if (!empty($rs->fields['mac']))
            {
                $mac = preg_replace('/(..)(..)(..)(..)(..)(..)/', '\\1:\\2:\\3:\\4:\\5:\\6', $rs->fields['mac']);
                
                $ips[$rs->fields['h_id']][$rs->fields['ip']]['mac'] = $mac;
                
                $macs[$rs->fields['h_id']][$mac] = array(
                    'ip'  => $rs->fields['ip'],
                    'mac' => $mac
                );
            }

            $rs->MoveNext();
        }

        return array($ips, $macs);
    }
    
    
    /**
    * This function returns the host IP list as string
    *
    * @param object  $conn     Database access object
    * @param string  $host_id  Host ID
    *
    * @return string
    */  
    public static function get_ips_to_string($conn, $host_id)
    {
        Ossim_db::check_connection($conn);
        
        $ips_string = array();
        
        $query  = 'SELECT INET6_NTOA(ip) AS ip FROM host_ip WHERE host_id = UNHEX(?)';
        
        $params = array($host_id);
        
        $conn->SetFetchMode(ADODB_FETCH_ASSOC);
        
        $rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {           
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
                        
        while (!$rs->EOF)
        {
            $ips_string[] = $rs->fields['ip'];
            
            $rs->MoveNext();
        }       
            
        return implode(',', $ips_string);
    }
}

/* End of file asset_host_ips.inc */
/* Location: ../include/classes/asset_host_ips.inc */ 
