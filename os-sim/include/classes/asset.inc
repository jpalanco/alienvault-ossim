<?php
/**
* asset.inc
*
* File asset.inc is used to:
*   - To manage generic assets
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
* Asset Class
*
* Class for managing generic asset
*
* @package    ossim-framework\Asset
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ Alienvault web page
*/

abstract class Asset
{
    /**
    * Asset ID
    *
    * @var string
    * @access protected
    */ 
    protected $id;
    
    /**
    * Asset context
    *
    * @var string
    * @access protected
    */ 
    protected $ctx;
    
    /**
    * Asset name
    *
    * @var string
    * @access protected
    */ 
    protected $name;
    
    /**
    * Asset IPs (Host IPs and MACs or Net CIDRs)
    *
    * @var string|object
    * @access protected
    */ 
    protected $ips;     
               
    /**
    * Asset description
    *
    * @var string
    * @access protected
    */ 
    protected $descr;
    
    /**
    * Asset icon
    *
    * @var string
    * @access protected
    */ 
    protected $icon;
           
    /**
    * External asset (Public)
    *
    * @var integer
    * @access protected
    */ 
    protected $external;    
           
    /**
    * Asset value
    *
    * @var integer
    * @access protected
    */ 
    protected $asset_value;
    
    /**
    * Threshold C
    *
    * @var integer
    * @access protected
    */ 
    protected $threshold_c;
    
    /**
    * Threshold A
    *
    * @var integer
    * @access protected
    */ 
    protected $threshold_a; 
        
    /**
    * Asset sensors
    *
    * @var object
    * @access protected
    */ 
    protected $sensors;
    
    
    
    /**
    * This function returns the asset ID
    *
    * @return string
    */      
    public function get_id()
    {
        return $this->id;
    }
    
    
    /**
    * This function returns the asset context
    *
    * @return string
    */      
    public function get_ctx()
    {
        return $this->ctx;
    }
    
    
    /**
    * This function returns the asset name
    *
    * @return string
    */      
    public function get_name()
    {
        return $this->name;
    }
    
    
    /**
    * This function returns the IP list associated to asset
    *
    * @return object||string
    */      
    abstract public function get_ips();
    
         
                
    /**
    * This function returns the asset description
    *
    * @return string
    */      
    public function get_descr()
    {
        return $this->descr;
    }
      
        
    /**
    * This function returns the asset icon (BLOB)
    *
    * @return string
    */      
    public function get_icon()
    {
        if ($this->icon == '')
        {
            return '';
        }
        
        return $this->icon;
    }
    
    
    /**
    * This function returns the asset icon (html version)
    *
    * @return string
    */    
    public function get_html_icon() 
    {               
        $html_icon = '';        
                
        if ($this->get_icon() != '')
        {
            $html_icon = "<img id='asset_icon' src='data:image/png;base64,".base64_encode($this->get_icon())."' border='0' align='absbottom'/>";
        }
              
        return $html_icon;   
     }
  
        
    /**
    * This function returns the external property from asset
    *
    * @return integer
    */      
    public function get_external()
    {
        return $this->external;
    }    
       
    
    /**
    * This function returns the asset value
    *
    * @return integer
    */  
    public function get_asset_value() 
    {
        return $this->asset_value;
    }
    
    
    /**
    * This function returns the Threshold C from asset
    *
    * @return integer
    */  
    public function get_threshold_c() 
    {
        return $this->threshold_c;
    }
    
    
    /**
    * This function returns the Threshold A from asset
    *
    * @return integer
    */  
    public function get_threshold_a() 
    {
        return $this->threshold_a;
    }
            
    
    /**
    * This function returns the sensors associated with the asset
    *
    * @return object
    */  
    public function get_sensors() 
    {
        return $this->sensors;
    }
    
        
    /**
    * This function returns the asset type
    *
    * @return string
    */ 
    abstract public function get_asset_type();
    
    
    /**
    * This function sets the asset ID
    *
    * @param string $id  Asset ID
    *
    * @throws Exception  If Asset ID is not valid
    *
    * @return void
    */      
    public function set_id($id)
    {
        if (!empty($id) && valid_hex32($id))
        {
            $this->id = strtoupper($id);
        }
        else
        {                        
            $exp_msg = _('Error! Asset ID is not valid');
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
    }
    
    
    /**
    * This function sets the asset context
    *
    * @param string $ctx  Asset context
    *
    * @return void
    */      
    public function set_ctx($ctx)
    {              
        if (!empty($ctx) && valid_hex32($ctx))
        {
            $this->ctx = strtoupper($ctx);
        }
        else
        {         
            $exp_msg = _('Error! Asset context is not valid');
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
    }
    
    
    /**
    * This function sets the asset name
    *
    * @param string $name  Asset name
    *
    * @return void
    */      
    public function set_name($name)
    {               
        if ($name != '')
        {
            $this->name = Util::htmlentities($name);
        }        
    }
    
    
    /**
    * This function sets IP list associated to asset
    *
    * @param array $ips  Host IPs
    *
    * @return void
    */      
    abstract public function set_ips($ips);
    
              
    /**
    * This function sets the asset description
    *
    * @param string $descr  Asset descripcion
    *
    * @return void
    */      
    public function set_descr($descr)
    {
        $this->descr = $descr;
    }
   
        
    /**
    * This function sets the asset icon
    *
    * @param string $icon  Asset icon
    *
    * @return void
    */      
    public function set_icon($icon)
    {        
        $this->icon = $icon;          
    }


    /**
    * This function sets the external property from asset
    *
    * @param integer $external  External property (0 --> Internal asset and 1 --> External asset)
    *
    * @return void
    */      
    public function set_external($external)
    {
        $external = intval($external);
        $external = ($external == 0) ? 0 : 1;
        
        $this->external = $external;
    }
   
   
    /**
    * This function sets the asset value
    *
    * @param integer $asset_value  Asset value (0 to 5)
    *
    * @return void
    */  
    public function set_asset_value($asset_value) 
    {               
        $this->asset_value = intval($asset_value);
    }
    
    
    /**
    * This function sets the Threshold C from asset
    *
    * @param integer $threshold_c  Threshold C
    *
    * @return void
    */  
    public function set_threshold_c($threshold_c) 
    {
        $this->threshold_c = intval($threshold_c);
    }
    
    
    /**
    * This function sets the Threshold A from asset
     *
    * @param int $threshold_a  Threshold A
    *
    * @return void
    */  
    public function set_threshold_a($threshold_a) 
    {
        $this->threshold_a = intval($threshold_a);
    }
    
    
    /**
    * This function sets the asset sensors
    *
    * @param array $sensors  Asset sensors
    *
    * @return void
    */  
    public function set_sensors($sensors) 
    {
        $this->sensors->set_sensors($sensors);
    }
    
    
    /**
    * This function sets the asset data from database
    *
    * @param object  $conn   Database access object     
    * @param boolean $cache  [Optional] Use cached information
    *
    * @throws Exception  If asset ID doesn't exists in the System or there is a connection error    
    *
    * @return void
    */ 
    abstract public function load_from_db($conn, $cache = FALSE);
    
    
    /**
    * This function saves asset into database
    *
    * @param object   $conn            Database access object
    * @param boolean  $report_changes  [Optional] Report changes to other components
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean      
    */ 
    abstract public function save_in_db($conn, $report_changes = TRUE);
    
    
    
    
    /*************************************************        
     **************** Static functions ***************      
     *************************************************/
     
    /**
    * This function reports asset changes to other components (Server, Sensor, ...)
    *
    * @param object  $conn         Database access object
    * @param string  $asset_type   hosts or nets
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean    
    */
    public static function report_changes($conn, $asset_type)
    {            
        // Refresh cache
        Util::memcacheFlush();
        Util::resend_asset_dump($asset_type);
        
                        
        $conf = $GLOBALS['CONF'];
        
        if (!$conf)
        {
            $conf = new Ossim_conf();
            $GLOBALS['CONF'] = $conf;
        }
        //Getting port and IP address from server
        
        $address = $conf->get_conf('server_address');
        $port    = $conf->get_conf('server_port');
        
        
        /********************************
         ************ Server ************
         ********************************/            
                
        //Creating socket
        $socket = @socket_create(AF_INET, SOCK_STREAM, 0);
        
        if ($socket === FALSE) 
        {        
            $exp_msg = _('Warning! Unable to create a connection with Server');
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
                      
        //Connection        
        socket_set_block($socket);
        socket_set_option($socket,SOL_SOCKET,SO_RCVTIMEO, array('sec' => 10, 'usec' => 0));
        socket_set_option($socket,SOL_SOCKET,SO_SNDTIMEO, array('sec' => 5,  'usec' => 0));
    
        $result = @socket_connect($socket, $address, $port);
                        
        if ($result === FALSE)
        {         
            $last_socket_error = socket_strerror(socket_last_error($socket));
            
            $exp_msg  = _("Warning! Can't connect with Server")." ($address:$port).";
            $exp_msg .= ' '._('Reason').': '.$last_socket_error;
                        
            socket_close($socket);            
                       
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }    
                
        $cmd    = 'connect id="1" type="web"' . "\n";
        $result = @socket_write($socket, $cmd, strlen($cmd));
                
        if ($result === FALSE) 
        {        
            $last_socket_error = socket_strerror(socket_last_error($socket));
            
            $exp_msg  = _('Warning! Failed to send data to Server')." ($address:$port).";
            $exp_msg .= ' '._('Reason').': '.$last_socket_error;
                        
            socket_close($socket);
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
               
        $out = @socket_read($socket, 2048);        
        
        //Testing connection        
        if (strncmp($out, 'ok id="1"', 9) != 0) 
        {                       
            $exp_msg  = _('Error connecting to server').' ...';
            $exp_msg .= ' '._('Socket error').": ". _('Is OSSIM server running at')." $address:$port?";
                  
            socket_close($socket);
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
        
        
        if ($asset_type == 'hosts' || $asset_type == 'nets') 
        {            
            //Reloading assets
            $cmd  = 'reload-' . $asset_type . ' id="2"' . "\n";
        	$out = '';
        
        	@socket_write($socket, $cmd, strlen($cmd));
        	$out = @socket_read($socket, 2048);
        
        	if (strncmp($out, 'ok id="2"', 9) != 0) 
        	{    		
        		$exp_msg  = _('Bad response from Server').' ...';
                $exp_msg .= ' '._('Socket error').": ". _('Is OSSIM server running at')." $address:$port?";
                      
                socket_close($socket);
                
                Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        	}            
        }               
    
    	@socket_close($socket);
         
        /********************************
         *********** Frameworkd *********
         ********************************/
         
        try
        {
            $frcon = new Frameworkd_socket();
            $frcon->write('refresh_asset_list');
        }
        catch(Exception $e)
        {
            $exp_msg = _('An error occurred while updating Agent cache...');
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }   	  	
    }

         
    /**
    * This function returns true if asset IPs (IPs or CIDRs) could be modified
    *
    * @param object  $conn   Database access object
    * @param string  $id     Asset ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean    
    */
    abstract public static function can_i_modify_ips($conn, $id);
    
     
    /**
    * This function checks if an asset could be deleted
    *
    * @param object  $conn   Database access object
    * @param string  $id     Asset ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean      
    */   
    abstract protected static function can_delete($conn, $id); 
     
     
    /**
    * This function inserts/deletes asset into table host_net_reference
    *
    * @param object  $conn    Database access object
    * @param string  $id      Asset ID
    * @param boolean $delete  [Optional] Delete assets
    * @param boolean $insert  [Optional] Insert assets
    *
    * @throws Exception  If a connection error occurred
    *
    * @return void
    */
    abstract protected static function set_host_net_reference($conn, $id, $delete = TRUE, $insert = TRUE);  
    
       
    /**
    * This function returns a SQL condition for filtering assets 
    *
    * @param string  $alias     [Optional] MySQL alias
    * @param string  $with_ctx  [Optional] Use context in filter
    *
    * @return string      
    */ 
    abstract public static function get_perms_where($alias = '', $with_ctx = TRUE);
     
    
    /**
    * This function returns an asset object
    *    
    * @param object  $conn   Database access object
    * @param string  $id     Asset ID
    * @param boolean $cache  [Optional] Use cached information
    *
    * @throws Exception  If a connection error occurred
    *
    * @return object      
    */   
    abstract public static function get_object($conn, $id, $cache = FALSE);
    
    
    /**
    * This function checks if asset exists into database
    *
    * @param object  $conn   Database access object
    * @param string  $id     Asset ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean      
    */ 
    abstract public static function is_in_db($conn, $id);
    
    
    /**
    * This function checks if the assets exists and it is allowed
    *
    * @param object  $conn  Database access object
    * @param string  $id    Asset ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean      
    */
    abstract public static function is_allowed($conn, $id); 
    
 
    /**
    * This function deletes a asset from database
    *
    * @param object   $conn            Database access object
    * @param string   $id              Asset ID
    * @param boolean  $report_changes  [Optional] Report changes to other components
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean      
    */ 
    abstract public static function delete_from_db($conn, $id, $report_changes = TRUE);
    
    
    /**
    * This function returns a filtered asset list (For trees and autocomplete widget)
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
    abstract public static function get_list_tree($conn, $tables = '', $filters = array(), $cache = FALSE);
    
    
    /**
    * This function returns a filtered asset list
    *
    * @param object   $conn     Database access object
    * @param string   $tables   [Optional] Database tables separated by comma (Join with main table)
    * @param array    $filters  SQL statements (WHERE, LIMIT, ORDER BY ...)
    * @param boolean  $cache    [Optional] Use cached information
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array      
    */ 
    abstract public static function get_list($conn, $tables = '', $filters = array(), $cache = FALSE);
    
    
    /**
    * This function format the host ip or cidr to print: Using ', ' instead of just ','
    *
    * @param string  $elems  List of ip or cidrs
    *
    * @return string      
    */ 
    public static function format_to_print($elems)
    {
        return trim(preg_replace('/\s*,\s*/', ', ', $elems));
    }
}

/* End of file asset.inc */
/* Location: ../include/classes/asset.inc */ 