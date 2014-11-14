<?php
/**
* Asset_host_scan.inc
*
* File Asset_host_scan.inc is used to:
*   - To manage host plugins (Nagios)
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
* Asset_host_scan Class
*
* Class for managing host plugins
*
* @package    ossim-framework\Asset
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ Alienvault web page
*/

class Asset_host_scan
{    
    /**
    * Host ID
    *
    * @var string
    * @access private
    */ 
    private $host_id;    
        
    /**
    * Plugins associated with the host
    *
    * @var array
    * @access private
    */ 
    private $plugins;

    
    
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
        $this->plugins = array();  
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
    * This function returns the plugins associated with the host
    *
    * @return array
    */  
    public function get_plugins() 
    {
        return $this->plugins;
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
            $exp_msg = _('Error! Host ID is not valid');
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
    }
    
    
    /**
    * This function sets host plugins
    *
    * @param array $plugins  Host plugins
    *
    * @return void
    */
    public function set_plugins($plugins) 
    {
        $this->plugins = array();

        if (is_array($plugins) && !empty($plugins))
        {                        
            foreach($plugins as $p_data)
            {
                $this->add_new($p_data['plugin_id'], $p_data['plugin_sid']);
            }
        }
    }
 
     
    /**
    * This function sets the host plugins from database
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
             
        
        $query  = 'SELECT plugin_id, plugin_sid FROM host_scan WHERE host_id = UNHEX(?)';
        $params = array($this->host_id);
        
        $rs = ($cache == TRUE) ? $conn->CacheExecute($query, $params) : $conn->Execute($query, $params);
        
        if (!$rs) 
        {           
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }        
        
        while (!$rs->EOF) 
        {
            $this->add_new($rs->fields['plugin_id'], $rs->fields['plugin_sid']);
            
            $rs->MoveNext();
        }
    }
    
    
    /**
    * This function adds a new plugin to host
    *
    * @param integer  $plugin_id    Plugin ID  
    * @param integer  $plugin_sid  [Optional] Datasource 
    *
    * @return boolean      
    */ 
    public function add_new($plugin_id, $plugin_sid = 0)
    {        
        $plugin_id   = intval($plugin_id);
        $plugin_sid  = intval($plugin_sid);
                
        if ($plugin_id > 0 && $plugin_sid >= 0) 
        {
            $this->plugins[$plugin_id][$plugin_sid] = $plugin_id.'-'.$plugin_sid; 
            
            return TRUE;            
        }
                   
        return FALSE; 
    }
    
    
    /**
    * This function deletes a plugin from the host
    *
    * @param integer  $plugin_id   Plugin ID  
    * @param integer  $plugin_sid  [Optional] Datasource 
    *
    * @return boolean
    */ 
    public function delete($plugin_id, $plugin_sid = 0)
    {
        $plugin_id  = intval($plugin_id);
        $plugin_sid = (empty($plugin_sid)) ? 0 : intval($plugin_sid);        
                
        if (!empty($this->plugins[$plugin_id][$plugin_sid]))
        {
             unset($this->plugins[$plugin_id][$plugin_sid]);
             
             if (!count($this->plugins[$plugin_id]))
             {
                 unset($this->plugins[$plugin_id]);
             }

             return TRUE;                  
        }
        
        return FALSE;
    }
    
    
    /**
    * This function saves all plugins into database
    *
    * @param object  $conn   Database access object
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean     
    */ 
    public function save_all_in_db($conn)
    {
        Ossim_db::check_connection($conn);
                        
        $enabled_nagios = self::is_plugin_in_host($conn, $this->host_id, 2007);         
              
        //Begin transaction
        $conn->StartTrans(); 
                           
        self::delete_all_from_db($conn, $this->host_id);        
        
        foreach($this->plugins as $plugin_id => $plugin_sids)
        {       
            foreach($plugin_sids as $plugin_sid => $_aux)
            {
                $query  = 'REPLACE INTO host_scan (host_id, plugin_id, plugin_sid) VALUES (UNHEX(?), ?, ?)';
                $params = array($this->host_id, $plugin_id, $plugin_sid);
                
                $conn->Execute($query, $params);        
            }
        }
        
        //Finish transaction
        if (!$conn->CompleteTrans())
        {        
            $exp_msg = _('Error! Host plugins could not be saved');
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
        else
        {
            foreach($this->plugins as $plugin_id => $plugin_sids)
            {            
                $infolog = array($this->host_id . ':' . $plugin_id);
                
                Log_action::log(10, $infolog);
            }
        }    
        
        /*
        Enable Nagios
            - Case 1: Nagios must be enabled now
            - Case 2: Nagios was enabled and the transaction wasn't completed.
                      We have to enable Nagios again         
        */
        if (array_key_exists(2007, $this->plugins) || $enabled_nagios == TRUE) 
        {
            self::add_host_to_nagios($this->host_id);
        }          
            
        return TRUE;             
    }
    
    
    
    /*************************************************        
     **************** Static functions ***************      
     *************************************************/
    
    
    
    /**
    * This function adds the host to Nagios
    *   
    * @param string  $host_id   Host ID
    *
    * @throws Exception  If an error occurred
    *   
    * @return void    
    */
    private static function add_host_to_nagios($host_id)
    {
        $q = new Nagios_adm();
        
        $q->addHost(new Nagios_host($host_id));
        $q->close();    
    }

    
    /**
    * This function deletes the host from Nagios
    *   
    * @param string  $host_id   Host ID
    *
    * @throws Exception  If an error occurred
    *   
    * @return void     
    */
    private static function delete_host_from_nagios($host_id)
    {
        $q = new Nagios_adm();
        
        $q->delHost(new Nagios_host($host_id));
        $q->close(); 
    }
    
        
    /**
    * This function saves one plugin into database
    *
    * @param object   $conn        Database access object
    * @param string   $host_id     Host ID
    * @param integer  $plugin_id   Plugin ID  
    * @param integer  $plugin_sid  [Optional] Datasource     
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean    
    */ 
    public static function save_plugin_in_db($conn, $host_id, $plugin_id, $plugin_sid = 0)
    {
        Ossim_db::check_connection($conn);            
        
        //Check if we can delete the plugin               
        if (Asset_host::is_allowed($conn, $host_id) != TRUE)
        {
            $exp_msg = _("Error! Host does not exists or you don't have permissions");
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }                         
       
        $host_scan = new self($host_id);
        
        if ($host_scan->add_new($plugin_id, $plugin_sid) == TRUE)
        {        
            $plugins = $host_scan->get_plugins();
            unset($host_scan);                
            
            $plugin_sid = array_keys($plugins[$plugin_id]); 
                            
            $query  = 'REPLACE INTO host_scan (host_id, plugin_id, plugin_sid) VALUES (UNHEX(?), ?, ?)';        
            $params = array($host_id, $plugin_id, $plugin_sid[0]);           
            
            $rs = $conn->Execute($query, $params);
        
            if (!$rs) 
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }                
                            
            $infolog = array($host_id . ':' . $plugin_id);
            Log_action::log(10, $infolog);
            
            //Enable Nagios
            if ($plugin_id == 2007) 
            {
                self::add_host_to_nagios($host_id);
            } 
        }                       
                        
        return TRUE;
    }
       
     
    /**
    * This function deletes one plugin from database
    *
    * @param object   $conn        Database access object
    * @param string   $host_id     Host ID
    * @param integer  $plugin_id   Plugin ID  
    * @param integer  $plugin_sid  [Optional] Datasource 
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean     
    */ 
    public static function delete_plugin_from_db($conn, $host_id, $plugin_id, $plugin_sid = 0) 
    {
        Ossim_db::check_connection($conn);
        
        //Check if we can delete the plugin               
        if (Asset_host::is_allowed($conn, $host_id) != TRUE)
        {
            $exp_msg = _("Error! Host does not exists or you don't have permissions");
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }            
        
        $plugin_id  = intval($plugin_id);
        $plugin_sid = (empty($plugin_sid)) ? 0 : intval($plugin_sid);
        
        
        $query  = 'DELETE FROM host_scan WHERE host_id = UNHEX(?) 
            AND plugin_id = ? AND plugin_sid = ?';        
        
        $params = array ($host_id, $plugin_id, $plugin_sid);            
        
        $rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        //Disable Nagios
        if ($plugin_id == 2007) 
        {            
            self::delete_host_from_nagios($host_id);
        } 
                        
        return TRUE;        
    }
        
        
    /**
    * This function deletes all plugins from database
    *
    * @param object  $conn     Database access object
    * @param string  $host_id  Host ID
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean     
    */    
    public static function delete_all_from_db($conn, $host_id)
    {
        Ossim_db::check_connection($conn);
                  
        //Check if we can delete the plugin               
        if (Asset_host::is_allowed($conn, $host_id) != TRUE)
        {
            $exp_msg = _("Error! Host does not exists or you don't have permissions");
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }    
                        
        $enabled_nagios = self::is_plugin_in_host($conn, $host_id, 2007);              
                
        //Delete all plugins        
        $query  = 'DELETE FROM host_scan WHERE host_id = UNHEX(?)';
        $params = array($host_id);
        
        $rs = $conn->Execute($query, $params);      
            
        if (!$rs) 
        {           
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }           
        
        //Disable Nagios        
        if ($enabled_nagios == TRUE)
        {
            self::delete_host_from_nagios($host_id);  
        }       
        
        return TRUE;
    }
           
    
    /**
    * This function checks if plugin ($plugin_id, $plugin_sid) is enabled in the host
    *
    * @param object   $conn         Database access object
    * @param string   $host_id      Host ID
    * @param integer  $plugin_id    Plugin ID  
    * @param integer  $plugin_sid   [Optional] Datasource 
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean     
    */  
    public static function is_plugin_in_host($conn, $host_id, $plugin_id, $plugin_sid = 0) 
    {     
        Ossim_db::check_connection($conn);
        
        $plugin_id  = intval($plugin_id);
        $plugin_sid = (empty($plugin_sid)) ? 0 : intval($plugin_sid);
        
        if ($plugin_id > 0)
        {
            $query  = 'SELECT HEX(host_id) as host_id FROM host_scan 
                WHERE host_id = UNHEX(?) 
                AND plugin_id = ? AND plugin_sid = ?';
            
            $params = array($host_id, $plugin_id, $plugin_sid);
            
            $rs = $conn->Execute($query, $params);      
            
            if (!$rs) 
            {           
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            } 
            
            if ($rs->fields['host_id'] == $host_id)
            {
                return TRUE;
            }
        }     
            
        return FALSE;
    }    
}

/* End of file asset_host_scan.inc */
/* Location: ../include/classes/asset_host_scan.inc */ 