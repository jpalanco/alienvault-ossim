<?php
/**
* plugin.inc
*
* File plugin.inc is used to:
*   - Manage plugin data
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
* @package    ossim-framework\Plugins
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/

class Plugin 
{
    /**
    * Plugin ID
    *
    * @var int
    * @access public
    */
    public $id;
    
    /**
    * Plugin Type
    *
    * @var int
    * @access public
    */
    public $type;
    
    /**
    * Plugin Name
    *
    * @var string
    * @access public
    */
    public $name;
    
    /**
    * Plugin Description
    *
    * @var string
    * @access public
    */
    public $description;
    
    /**
    * Product Type
    *
    * @var int
    * @access public
    */
    public $product_type;
    
    /**
    * Plugin Source Type
    *
    * @var int
    * @access public
    */
    public $sourceType;
    
    
    /**
    * Class constructor
    *
    * This function sets up the class
    *
    * @param object   $conn         Database access object
    * @param integer  $id           Plugin ID
    * @param string   $name         Plugin name
    * @param string   $description  Plugin description
    * @param integer  $product_type Plugin product type
    * @param integer  $source_type  Plugin source type
    * @param integer  $foundrows    [Optional] Found Rows
    */
    function __construct($id, $type, $name, $description, $product_type, $sourceType, $foundrows = 0)
    {
        $this->id           = $id;
        $this->type         = $type;
        $this->name         = $name;
        $this->description  = $description;
        $this->product_type = $product_type;
        $this->sourceType   = $sourceType;
        $this->foundrows    = $foundrows;
    }
    
    
    /**
    * This function returns the plugin id
    *
    * @return int
    */
    public function get_id()
    {
        return $this->id;
    }
    
    
    /**
    * This function returns the plugin type
    *
    * @return int
    */
    public function get_type()
    {
        return $this->type;
    }
    
    
    /**
    * This function returns the plugin name
    *
    * @return string
    */
    public function get_name()
    {
        return $this->name;
    }
    
    /**
    * This function returns the plugin description
    *
    * @return string
    */
    public function get_description()
    {
        return $this->description;
    }
    
    
    /**
    * This function returns the plugin product type
    *
    * @return int
    */
    public function get_product_type()
    {
        return $this->product_type;
    }
    
    
    /**
    * This function returns the plugin source type
    *
    * @return int
    */
    public function get_sourceType()
    {
        return $this->sourceType;
    }
    
    /**
     * This function returns the foundrows field
     *
     * @return int
     */
    public function get_foundrows()
    {
        return $this->foundrows;
    }
    
    
    /**
    * This function return the product types
    * 
    * @param  object $conn
    * 
    * @return array
    */
    public static function get_ptypes($conn)
    {
        Ossim_db::check_connection($conn);
        
        $ptypes = array();
        $query  = ossim_query("SELECT * FROM product_type");

        $rs = $conn->Execute($query);
            
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $ptypes[$rs->fields['id']] = $rs->fields['name'];
            
            $rs->MoveNext();
        }

        return $ptypes;
    }


    /**
    * This function returns the plugin objects list
    * 
    * @param object  $conn   Database access object
    * @param string  $args   [Optional] SQL filter
    * 
    * @return array
    */
    public static function get_list($conn, $args = '')
    {
        Ossim_db::check_connection($conn);

        $ptypes      = Plugin::get_ptypes($conn);
        $iffoundrows = preg_match('/LIMIT/i', $args) ? TRUE : FALSE;
        $select      = ($iffoundrows) ? 'SQL_CALC_FOUND_ROWS *' : '*';
        $query       = ossim_query("SELECT $select FROM plugin $args");
        
        $rs = $conn->Execute($query);
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $foundrows = 0;

        if ($iffoundrows)
        {
            $rf = $conn->Execute('SELECT FOUND_ROWS() AS total');
            
            if (!$rf)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }

            $foundrows = $rf->fields['total'];
        }

        $list = array();

        while (!$rs->EOF)
        {
            $product_type = $ptypes[$rs->fields['product_type']];
            
            $list[] = new self($rs->fields['id'], $rs->fields['type'], $rs->fields['name'], $rs->fields['description'],
                $rs->fields['product_type'], $product_type, $foundrows);
            
            $rs->MoveNext();
        }

        return $list;
    }


    /**
    * This functions returns the plugin list by id and name
    * 
    * @param object  $conn   Database access object
    * @param string  $args   [Optional] SQL filter
    * 
    * @return array
    */
    public static function get_id_and_name($conn, $args = '')
    {
        Ossim_db::check_connection($conn);
        
        $list  = array();
        $query = ossim_query("SELECT id, name FROM plugin $args");
        
        $rs = $conn->Execute($query);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $list[$rs->fields['id']] = $rs->fields['name'];
            
            $rs->MoveNext();
        }

        return $list;
    }


    /**
    * This function returns the name of plugin by id
    * 
    * @param object   $conn   Database access object
    * @param int      $id     Plugin ID
    * @param boolean  $desc   If $desc is TRUE, add plugin description to name
    * 
    * @return string
    */
    public static function get_name_by_id($conn, $id, $desc = FALSE)
    {
        Ossim_db::check_connection($conn);
        
        $query = ossim_query("SELECT name, description FROM plugin WHERE id = ?");
        
        $rs = $conn->Execute($query, array($id));

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        return ($desc && $rs->fields['description'] != '') ? $rs->fields['name'].': '.$rs->fields['description'] : $rs->fields['name'];
    }


    /**
    * This function returns the id of plugin by name
    * 
    * @param object   $conn   Database access object
    * @param string   $name   Plugin name
    * @param boolean $cache  [Optional] Use cached information
    * 
    * @return string
    */
    public static function get_id_by_name($conn, $name, $cache = FALSE)
    {
        Ossim_db::check_connection($conn);
        
        $id  = '';
        
        $query = "SELECT id FROM plugin WHERE name = ?";
        $rs  = ($cache) ? $conn->CacheExecute($query, array($name)) : $conn->Execute($query, array($name));
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $id = $rs->fields['id'];

        return $id;
    }
    
    
    /**
    * This function returns the last five events related to a plugin
    * 
    * @param object  $conn   Database access object
    * @param int     $id     Plugin ID
    * @param string  $ip     Host IP
    * 
    * @return array
    */
    public static function get_latest_SIM_Event_by_plugin_id($conn, $id, $ip)
    {
        Ossim_db::check_connection($conn);

        // get plugin sids
        $list = array();
        $query  = ossim_query("select max(acid_event.timestamp) as event_date, sensor.ip, sensor.name as sensor_name, plugin_sid.name as sig_name from alienvault_siem.acid_event left join alienvault.plugin_sid on plugin_sid.plugin_id=acid_event.plugin_id and plugin_sid.sid=acid_event.plugin_sid, alienvault_siem.device, alienvault.sensor WHERE device.id=acid_event.device_id AND device.sensor_id=sensor.id AND acid_event.plugin_id=? AND sensor.ip=inet6_pton(?) group by plugin_sid.name order by event_date desc limit 5");

        $rs = & $conn->Execute($query, array($id,$ip));
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $rs->fields['ip'] = @inet_ntop($rs->fields['ip']);
            $list[] = $rs->fields;
            
            $rs->MoveNext();
        }

        return $list;
    }
    
    
    /**
    * This function returns all detector plugins (CFG files) from a sensor
    *
    * @param string  $sensor_id   [Optional] Sensor UUID or local
    *
    * @throws Exception  If there is a connection or API error
    *
    * @return array
    */
    public static function get_detector_list($sensor_id = 'local')
    {
        $client = new Alienvault_client();

        if ($sensor_id != 'local')
        {
            $db = new Ossim_db();
            $conn = $db->connect();

            $sensor_ids = Av_center::get_component_id_by_system($conn, $sensor_id, 'sensor');

            $db->close();

            if (is_array($sensor_ids) && !empty($sensor_ids))
            {
                $sensor_id = $sensor_ids['canonical'];
            }
        }

        $response  = $client->sensor($sensor_id)->get_detector_list();
        $response  = @json_decode($response, TRUE);
    
        $plugins = array();
        
        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $plugins = $response['data']['plugins'];

        sort($plugins);

        return $plugins;
    }


    /**
    * This function gets enabled plugins by device
    *
    * @param string  $sensor_id   [Optional] Sensor UUID or local
    *
    * @throws Exception  If there is a connection or API error
    *
    * @return  array
    */
    public static function get_plugins_by_device($sensor_id = 'local')
    {           
        // API CALL
        $client = new Alienvault_client();
        
        $response  = $client->sensor($sensor_id)->get_plugins_by_assets();
        $response  = @json_decode($response, TRUE);
    
        $plugins = array();
        
        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $plugins = $response['data']['plugins'];
        
        return $plugins;
    }


    /**
    * This function sets the selected plugins (If they are not previously on)
    *
    * @param object  $conn           Database access object
    * @param string  $cpe_by_device  CPE
    * @param string  $sensor_id      Sensor UUID or local
    *
    * @throws Exception  If there is a connection or API error
    *
    * @return string uuid
    */
    public static function set_plugins_by_device_cpe($conn, $cpe_by_device, $sensor_id = 'local')
    {
        Ossim_db::check_connection($conn);
        
        $plugins = array();
                
        foreach ($cpe_by_device as $id => $cpes)
        {
            $plugins[$id] = array();
            
            foreach ($cpes as $cpe)
            {
                if ($cpe != '')
                {
                    $plist = self::get_plugin_by_cpe($conn, $cpe, TRUE);
                    
                    if (is_array($plist) && count($plist) > 0)
                    {
                        foreach ($plist as $pid => $pname)
                        {
                            $plugins[$id][$pname] = array("plugin_id" => $pid, "cpe" => $cpe);
                            break; // only the first one
                        }
                    }
                }
            }
        }

        // API CALL
        $client  = new Alienvault_client();
        
        $response = $client->sensor($sensor_id)->set_plugins_by_assets($plugins);
        $response = @json_decode($response, TRUE);
        
        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
        
        return $response['data']['jobid'];
    }


    /**
    * This function returns distinct plugins for current host with related cpes 
    *
    * @param object  $conn   Database access object
    * @param boolean $cache  [Optional] Use cached information
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */     
    public static function get_plugin_by_cpe($conn, $cpe, $cache = FALSE)
    {
        Ossim_db::check_connection($conn);
        
        $plugins = array();
        
        $query     = "SELECT DISTINCT s.plugin FROM software_cpe s WHERE s.cpe LIKE ? AND s.plugin!=''";
        $params  = array($cpe . '%');
                    
        $rs = ($cache) ? $conn->CacheExecute($query, $params) : $conn->Execute($query, $params);
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            list($name, $id) = explode(":", $rs->fields['plugin']);
            
            $plugins[$id]    = $name;

            $rs->MoveNext();
        }

        return $plugins;
    }


    /**
    * This function translates a cpe into a model, vendor and version 
    *
    * @param  string  $cpe  CPE string
    *
    * @return array with model, vendor, version
    */
    public static function translate_cpe_to_software($cpe)
    {
        $vendor = $model = $version = '';
        
        $_cpe   = explode(':', $cpe);
                    
        $vendor = implode(':', array_slice($_cpe, 0, 3));
        
        if (count($_cpe) > 3)
        {
            $model = implode(':', array_slice($_cpe, 0, 4));
        }

        if (count($_cpe) > 4)
        {
            $version = $cpe;
        }

        return array($vendor, $model, $version);
    }
}

/* End of file plugin.inc */
/* Location: ../includes/plugin.inc */