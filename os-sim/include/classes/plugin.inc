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

        $ptypes      = self::get_ptypes($conn);
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
        $query  = ossim_query("select max(acid_event.timestamp) as event_date, sensor.ip, sensor.name as sensor_name, plugin_sid.name as sig_name from alienvault_siem.acid_event left join alienvault.plugin_sid on plugin_sid.plugin_id=acid_event.plugin_id and plugin_sid.sid=acid_event.plugin_sid, alienvault_siem.device, alienvault.sensor WHERE device.id=acid_event.device_id AND device.sensor_id=sensor.id AND acid_event.plugin_id=? AND sensor.ip=inet6_aton(?) group by plugin_sid.name order by event_date desc limit 5");

        $rs = $conn->Execute($query, array($id,$ip));
        
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
     * This function returns all plugins (CFG files) from a sensor
     *
     * @param string  $sensor_id    [Optional] Sensor UUID or local
     * @param array   $filters      [Optional] Filters by OR {'per_asset' => TRUE, ...}
     * @param bool    $only_enabled [Optional] Call to /plugins/detector/{enabled}
     *
     * @throws Exception  If there is a connection or API error
     *
     * @return array
     */
    public static function get_plugins_from_api($sensor_id = 'local', $filters = array(), $only_enabled = FALSE)
    {
        // API CALL
        $client = new Alienvault_client();
        
        $response  = ($only_enabled) ? $client->sensor($sensor_id)->get_plugins_enabled() : $client->sensor($sensor_id)->get_plugins();
        $response  = @json_decode($response, TRUE);
    
        $plugins = array();
    
        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $client->get_error_message($response);
    
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
        
        // If calling to /enabled return now
        if ($only_enabled)
        {
            $_return = $response['data']['plugins']['sensor_detectors'];
            $_return = array_flip($_return);
            
            return $_return;
        }
        
        
        if (is_array($response['data']['plugins']))
        {
            // Apply filters
            if (count($filters) > 0)
            {
                foreach ($response['data']['plugins'] as $plugin_key => $plugin_data)
                {
                    $pass = FALSE;
                    
                    foreach ($filters as $filter_key => $filter_val)
                    {
                        if ($plugin_data[$filter_key] == $filter_val)
                        {
                            $pass = TRUE;
                        }
                    }
                    
                    if ($pass)
                    {
                        $plugins[$plugin_key] = $plugin_data;
                    }
                }
            }
            else
            {
                $plugins = $response['data']['plugins'];
            }
        }
        
        ksort($plugins);
    
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
    public static function get_plugins_by_assets($sensor_id = 'local')
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
    * @param string  $plugins        Plugin list by assets
    * @param string  $sensor_id      Sensor UUID or local
    *
    * @throws Exception  If there is a connection or API error
    *
    * @return string uuid
    */
    public static function set_plugins_by_assets($plugins, $sensor_id = 'local')
    {
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
     * This function resolves Vendor/Model/Version to plugin name against the API
     * 
     * @param  array  $vmv_by_asset  {{asset_id_1} => [{'vendor' => 'v1', 'model' => 'v1:m1', 'version' => 'v1:m1:v1'}, ...], ...}
     * @param  string $sensor_id
     *
     * @return array
     */
    public static function resolve_plugins_by_vmv($vmv_by_asset, $sensor_id = 'local')
    {
        $plugins  = array();
        
        $all_plugins = self::get_plugins_from_api(Util::uuid_format($sensor_id), array('per_asset' => TRUE));
        
        // Resolve Vendor/Model/Version selected to plugin names
        foreach ($vmv_by_asset as $id => $selected_vmv)
        {
            if (valid_hex32($id))
            {
                $selected_vmv = (is_array($selected_vmv)) ? $selected_vmv : array();
            
                $_plugin_list = array();
            
                foreach ($selected_vmv as $p)
                {
                    // Third (version) select box values has the pattern vendor:model:version
                    $selected_data = explode(':', $p['version']);
            
                    foreach ($all_plugins as $plugin_key => $plugin_data)
                    {
                        if ($selected_data[0] == $plugin_data['vendor'] &&
                            $selected_data[1] == $plugin_data['model']  &&
                            $selected_data[2] == $plugin_data['version'])
                        {
                            $_plugin_list[] = $plugin_key;
                        }
                    }
                }
            
                $plugins[$id] = $_plugin_list;
            }
        }
        
        return $plugins;
    }

}

/* End of file plugin.inc */
/* Location: ../includes/plugin.inc */
