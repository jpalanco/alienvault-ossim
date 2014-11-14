<?php
/**
* Asset_group_scan.inc
*
* File Asset_group_scan.inc is used to:
*   - To manage group plugins (Nagios)
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
* Asset_group_scan Class
*
* Class for managing group plugins
*
* @package    ossim-framework\Asset
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ Alienvault web page
*/

class Asset_group_scan
{    
    /**
    * Group ID
    *
    * @var string
    * @access private
    */ 
    private $group_id;    
        
    /**
    * Plugins associated with the group
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
    * @param string  $group_id  Group ID 
    */  
    public function __construct($group_id)
    {              
        $this->set_group_id($group_id);
        $this->plugins = array();
    }
    
    
    /**
    * This function returns the group ID
    *
    * @return string
    */      
    public function get_group_id()
    {
        return $this->group_id;
    }
        
        
    /**
    * This function returns the plugins associated with the group
    *
    * @return array
    */  
    public function get_plugins() 
    {
        return $this->plugins;
    }

    
    /**
    * This function sets the group ID
    *
    * @param string $group_id  Group ID
    *
    * @throws Exception  If group ID is not valid
    *
    * @return void
    */
    public function set_group_id($group_id)
    {
        if (!empty($group_id) && valid_hex32($group_id))
        {
            $this->group_id = strtoupper($group_id);
        }
        else
        {
            $exp_msg = _('Error! Group ID is not valid');
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
    }
    
    
    /**
    * This function sets group plugins
    *
    * @param array $plugins  Group plugins
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
    * This function sets the group plugins from database
    *
    * @param object  $conn   Database access object     
    * @param boolean $cache  [Optional] Use cached information
    *
    * @throws Exception  If group ID doesn't exists in the System or there is a connection error    
    *
    * @return void
    */      
    public function load_from_db($conn, $cache = FALSE) 
    {
        Ossim_db::check_connection($conn);        
             
        
        $query  = 'SELECT plugin_id, plugin_sid FROM host_group_scan WHERE host_group_id = UNHEX(?)';
        $params = array($this->group_id);
        
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
    * This function adds a new plugin to group
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
    * This function deletes a plugin from the group
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
                        
        $enabled_nagios = self::is_plugin_in_group($conn, $this->group_id, 2007);         
              
        //Begin transaction
        $conn->StartTrans(); 
                           
        self::delete_all_from_db($conn, $this->group_id);        
        
        foreach($this->plugins as $plugin_id => $plugin_sids)
        {       
            foreach($plugin_sids as $plugin_sid => $_aux)
            {
                $query  = 'REPLACE INTO host_group_scan (host_group_id, plugin_id, plugin_sid) VALUES (UNHEX(?), ?, ?)';
                $params = array($this->group_id, $plugin_id, $plugin_sid);
                
                $conn->Execute($query, $params);        
            }
        }
        
        //Finish transaction
        if (!$conn->CompleteTrans())
        {        
            $exp_msg = _('Error! Group plugins could not be saved');
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
        else
        {
            foreach($this->plugins as $plugin_id => $plugin_sids)
            {            
                $infolog = array($this->group_id . ':' . $plugin_id);
                
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
            self::add_group_to_nagios($conn, $this->group_id);
        }
            
        return TRUE;             
    }
    
    
    
    /*************************************************        
     **************** Static functions ***************      
     *************************************************/
    
    
    
    /**
    * This function adds the group to Nagios
    * 
    * @param object  $conn       Database connection
    * @param string  $group_id   Group ID
    *
    * @throws Exception  If an error occurred
    *   
    * @return void    
    */
    private static function add_group_to_nagios($conn, $group_id)
    {
        $q = new Nagios_adm();
        
        $asset_group     = Asset_group::get_object($conn, $group_id);
        $_hosts_data_aux = $asset_group->get_hosts($conn, '', TRUE);
        $hosts           = array_keys($_hosts_data_aux[0]);
        
        $q->addNagiosHostGroup(new Nagios_group($group_id, $hosts));
        $q->close();    
    }

    
    /**
    * This function deletes the group from Nagios
    *   
    * @param string  $group_id   Group ID
    *
    * @throws Exception  If an error occurred
    *   
    * @return void     
    */
    private static function delete_group_from_nagios($group_id)
    {
        $q = new Nagios_adm();
        
        $q->delNagiosHostGroup(new Nagios_group($group_id, array()));
        $q->close(); 
    }
    
        
    /**
    * This function saves one plugin into database
    *
    * @param object   $conn        Database access object
    * @param string   $group_id    Group ID
    * @param integer  $plugin_id   Plugin ID  
    * @param integer  $plugin_sid  [Optional] Datasource     
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean    
    */ 
    public static function save_plugin_in_db($conn, $group_id, $plugin_id, $plugin_sid = 0)
    {
        Ossim_db::check_connection($conn);            
        
        //Check if we can delete the plugin               
        /* COMMENTED: Cloned from asset_host_scan.inc
        if (Asset_host::is_allowed($conn, $host_id) != TRUE)
        {
            $exp_msg = _("Error! Host does not exists or you don't have permissions");
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        } 
        */                        
       
        $group_scan = new self($group_id);
        
        if ($group_scan->add_new($plugin_id, $plugin_sid) == TRUE)
        {        
            $plugins = $group_scan->get_plugins();
            unset($group_scan);                
            
            $plugin_sid = array_keys($plugins[$plugin_id]); 
                            
            $query  = 'REPLACE INTO host_group_scan (host_group_id, plugin_id, plugin_sid) VALUES (UNHEX(?), ?, ?)';        
            $params = array($group_id, $plugin_id, $plugin_sid[0]);           
            
            $rs = $conn->Execute($query, $params);
        
            if (!$rs) 
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }                
                            
            $infolog = array($group_id . ':' . $plugin_id);
            Log_action::log(10, $infolog);
            
            //Enable Nagios
            if ($plugin_id == 2007) 
            {
                self::add_group_to_nagios($conn, $group_id);
            } 
        }                       
                        
        return TRUE;
    }
       
     
    /**
    * This function deletes one plugin from database
    *
    * @param object   $conn        Database access object
    * @param string   $group_id    Group ID
    * @param integer  $plugin_id   Plugin ID  
    * @param integer  $plugin_sid  [Optional] Datasource 
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean     
    */ 
    public static function delete_plugin_from_db($conn, $group_id, $plugin_id, $plugin_sid = 0) 
    {
        Ossim_db::check_connection($conn);
        
        //Check if we can delete the plugin
        /* COMMENTED: Cloned from asset_host_scan.inc
        if (Asset_host::is_allowed($conn, $host_id) != TRUE)
        {
            $exp_msg = _("Error! Host does not exists or you don't have permissions");
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }            
        */
        
        $plugin_id  = intval($plugin_id);
        $plugin_sid = (empty($plugin_sid)) ? 0 : intval($plugin_sid);
        
        
        $query  = 'DELETE FROM host_group_scan WHERE host_group_id = UNHEX(?) 
            AND plugin_id = ? AND plugin_sid = ?';        
        
        $params = array ($group_id, $plugin_id, $plugin_sid);            
        
        $rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        //Disable Nagios
        if ($plugin_id == 2007) 
        {            
            self::delete_group_from_nagios($group_id);
        }
                        
        return TRUE;        
    }
        
        
    /**
    * This function deletes all plugins from database
    *
    * @param object  $conn      Database access object
    * @param string  $group_id  Group ID
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean     
    */    
    public static function delete_all_from_db($conn, $group_id)
    {
        Ossim_db::check_connection($conn);
                  
        //Check if we can delete the plugin               
        /* COMMENTED: Cloned from asset_host_scan.inc
        if (Asset_host::is_allowed($conn, $host_id) != TRUE)
        {
            $exp_msg = _("Error! Host does not exists or you don't have permissions");
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        } 
        */   
                        
        $enabled_nagios = self::is_plugin_in_group($conn, $group_id, 2007);              
                
        //Delete all plugins        
        $query  = 'DELETE FROM host_group_scan WHERE host_group_id = UNHEX(?)';
        $params = array($group_id);
        
        $rs = $conn->Execute($query, $params);      
            
        if (!$rs) 
        {           
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }           
        
        //Disable Nagios        
        if ($enabled_nagios == TRUE)
        {
            self::delete_group_from_nagios($group_id);  
        }       
        
        return TRUE;
    }
           
    
    /**
    * This function checks if plugin ($plugin_id, $plugin_sid) is enabled in the group
    *
    * @param object   $conn         Database access object
    * @param string   $group_id     Group ID
    * @param integer  $plugin_id    Plugin ID  
    * @param integer  $plugin_sid   [Optional] Datasource 
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean     
    */  
    public static function is_plugin_in_group($conn, $group_id, $plugin_id, $plugin_sid = 0) 
    {     
        Ossim_db::check_connection($conn);
        
        $plugin_id  = intval($plugin_id);
        $plugin_sid = (empty($plugin_sid)) ? 0 : intval($plugin_sid);
        
        if ($plugin_id > 0)
        {
            $query  = 'SELECT HEX(host_group_id) as host_group_id FROM host_group_scan 
                WHERE host_group_id = UNHEX(?) 
                AND plugin_id = ? AND plugin_sid = ?';
            
            $params = array($group_id, $plugin_id, $plugin_sid);
            
            $rs = $conn->Execute($query, $params);      
            
            if (!$rs) 
            {           
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            } 
            
            if ($rs->fields['host_group_id'] == $group_id)
            {
                return TRUE;
            }
        }     
            
        return FALSE;
    }
}

/* End of file asset_group_scan.inc */
/* Location: ../include/classes/asset_group_scan.inc */ 