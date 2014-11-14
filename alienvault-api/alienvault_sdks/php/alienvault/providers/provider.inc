<?php
/**
* provider.inc
*
* File provider.inc is used to:
*   - manage the Alienvault REST API providers
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
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/

require_once 'config.inc';
require_once 'apps.inc';

/**
* The goal of this class is to define the providers common functions and attributes
* 
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/
class Provider
{
    /**
    * Connection object
    * @access protected
    * @var object
    *
    */  
    protected $conn;
    
    /**
    * Providers objects
    * @access protected
    * @var object
    *
    */
    protected $sub_providers;
    
    /**
    * common url to do the request
    * @access private
    * @var string
    *
    */ 
    protected $common_url;
    
    /**
    * The class constructor
    *
    * This sets up the providers classes
    *
    * @param object  $conn        Connection object
    * @param string  $common_url  Common url to do the request
    * 
    */
    public function __construct($conn, $common_url)
    {
        $this->conn          = $conn;
       
        $this->sub_providers = new Provider_registry();

        $this->common_url    = $common_url;
    }
}

/**
* The goal of this class is to registry the AlienVault API providers
* 
* <br/>
* <pre>
* <code>
* Example usage:
* 
* $providers = new Provider_registry();
* 
* </code>
* </pre>
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/
class Provider_registry  
{  
    /** 
     * Registry of variables and objects
     * @access private 
     * @var array 
     */  
    private $registry;
  
    /**
    * The class constructor
    *
    * This sets up the class
    * 
    */
    public function __construct()
    {        
        $this->registry = array();
    }
  
    /** 
     * Adds an item to the registry 
     *
     * @access static public
     * @param string item's unique name 
     * @param mixed item
     *
     * @return boolean 
     */  
    public function add($conn, $name, $section)  
    {  
        $result = FALSE;
    
        if (!$this->exists($name))
        {    
            $object = Provider_factory::Create($conn, $name, $section);
                    
            if($result!==NULL)
            {
                $this->registry[$name] = $object;  
                
                $result = TRUE;
            }
        }
    
        return $result;  
    }  
  
    /** 
     * Returns true if item is registered
     *
     * @access static public
     * @param string item's name 
     *
     * @return boolean 
     */  
    public function exists($name)  
    {  
        if (is_string($name)) {  
            return array_key_exists($name, $this->registry);  
        } else {  
            throw new Exception('Registry item\'s name must be a string');  
        }  
    }  
  
    /** 
     * Returns registered item
     *
     * @access static public
     * @param string item's name
     *
     * @return mixed (null if name is not in registry) 
     */  
    public function get($name)  
    {  
        if (array_key_exists($name, $this->registry)) {  
            $return = $this->registry[$name];  
        } else {  
            $return = null;  
        }
          
        return $return;  
    }  
  
    /** 
     * Removes a registry entry
     *
     * @access static public 
     * @param string item's name
     *
     * @return boolean 
     */  
    public function remove($name)  
    {  
        if (array_key_exists($name, $this->registry)) {  
            unset($this->registry[$name]);  
        }  
        return true;  
    }  
  
    /** 
     * Clears the entire registry
     *
     * @access static public
     * 
     * @return boolean 
     */  
    public function clear()  
    {  
        $this->registry = array();  
    }  
}

/**
* The goal of this class is to create the AlienVault API providers
* 
* <br/>
* <pre>
* <code>
* Example usage:
* 
* $object = Provider_factory::Create($conn, "Config", "/av/api/1.0")
* 
* 
* </code>
* </pre>
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/

class Provider_factory
{
    /** 
     * Returns the created provider object
     *
     * @access static  public
     * @param  object  $conn        Connection object
     * @param  string  $name        Provider name 
     * @param  string  $common_url  Common url to do the request
     *
     * @return object 
     */ 
    public static function Create($conn, $name, $common_url)
    {
        switch ($name) {
        
            case "Config":
            
                $result = new Configuration($conn, $common_url);
                    
                break;
                    
            case "sensor":
            
                $result = new Config_sensor($conn, $common_url);
                    
                break;
                
            case "Apps":
            
                $result = new Apps($conn, $common_url);
                    
                break;
                
            case "doctor":
                
                $result = new Apps_doctor($conn, $common_url);
                
                break;

            default:
                    
                $result = NULL;
        }
        
        return $result;
    }
}
  
?>  
