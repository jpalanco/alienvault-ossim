<?php
/**
* asset_net_sensors.inc
*
* File asset_net_sensors.inc is used to:
*   - To manage net sensors
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
* Asset_net_sensors Class
*
* Class for managing net sensors
*
* @package    ossim-framework\Asset
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/

class Asset_net_sensors
{
    /**
    * Net ID
    *
    * @var string
    * @access private
    */
    private $net_id;

    /**
    * Sensors associated with the net
    *
    * @var array
    * @access private
    */
    private $sensors;


    /**
    * Class constructor
    *
    * This function sets up the class
    *
    * @throws Exception  If net ID is not valid
    *
    * @param string  $net_id  Net ID
    */
    public function __construct($net_id)
    {
        $this->set_net_id($net_id);
        $this->sensors = array();
    }

    /**
     * This function creates an object instance
     *
     * @param string  $net_id  Host ID
     *
     * @return Asset_net_sensors
     * @throws Exception  If a connection error occurred
     */
    public static function factory($net_id){
        return new Asset_net_sensors($net_id);
    }


    /**
    * This function returns the net ID
    *
    * @return string
    */
    public function get_net_id()
    {
        return $this->net_id;
    }

    /**
    * This function returns the sensors associated to net
    *
    * @return array
    */
    public function get_sensors()
    {
        return $this->sensors;
    }


    /**
    * This function sets the net ID
    *
    * @param string $net_id  Net ID
    *
    * @throws Exception  If net ID is not valid
    *
    * @return void
    */
    public function set_net_id($net_id)
    {
        if (!empty($net_id) && security_class::valid_hex32($net_id))
        {
            $this->net_id = strtoupper($net_id);
        }
        else
        {
            $exp_msg = _('Error! Net ID is not valid');
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
    }


    /**
    * This function sets the net sensors
    *
    * @param array  $sensors  Net sensors
    *
    * @return void
    */
    public function set_sensors($sensors)
    {
        $this->sensors = array();

        if (is_array($sensors) && !empty($sensors))
        {
            $db   = new ossim_db();
            $conn = $db->connect();

            foreach($sensors as $sensor_id)
            {
                $this->add_new($conn, $sensor_id);
            }

            $db->close();
        }
    }


    /**
    * This function gets sensor list associated with the net from database
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

        $query = 'SELECT HEX(ns.sensor_id) AS sensor_id, s.name, inet6_ntoa(s.ip) AS sensor_ip
            FROM sensor s, net_sensor_reference ns
            WHERE ns.net_id = UNHEX(?) AND s.id = ns.sensor_id
            ORDER BY s.priority DESC, s.name ASC';


        $params = array($this->net_id);

        $rs = ($cache == TRUE) ? $conn->CacheExecute($query, $params) : $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $ctxs = Av_sensor::get_ctx_by_id($conn, $rs->fields['sensor_id']);

            $this->sensors[$rs->fields['sensor_id']] = array(
                 'name'  => $rs->fields['name'],
                 'ip'    => $rs->fields['sensor_ip'],
                 'ctxs'  => $ctxs
            );

            $rs->MoveNext();
        }
    }


    /**
    * This function adds a new sensor to the net
    *
    * @param object  $conn       Database access object
    * @param string  $sensor_id  Sensor ID
    *
    * @throws Exception  If mysql error
    *
    * @return boolean
    */
    public function add_new($conn, $sensor_id)
    {
        $sensor_id  = strtoupper($sensor_id);
        $sensor     = Av_sensor::get_object($conn, $sensor_id);                           
        
        if (is_object($sensor) && !empty($sensor))
        {
            $sensor_ctxs = $sensor->get_ctx();
            
            $this->sensors[$sensor_id] = array(
                 'name' => $sensor->get_name(),
                 'ip'   => $sensor->get_ip(),
                 'ctxs' => $sensor_ctxs
            );

            return TRUE;            
        }        

        return FALSE;
    }


    /**
    * This function deletes a sensor from the net
    *
    * @param string  $sensor_id  Sensor ID
    *
    * @return boolean
    */
    public function delete($sensor_id)
    {
        if (array_key_exists($sensor_id, $this->sensors))
        {
            unset($this->sensors[$sensor_id]);

            return TRUE;
        }

        return FALSE;
    }


    /**
    * This function saves all sensors into database
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
        
        //Begin transaction
        $conn->StartTrans();

        $net_id = $this->get_net_id();

        $this->obj_delete_all_from_db($conn);
        
        $net_ctx = Asset_net::get_ctx_by_id($conn, $net_id);

        foreach($this->sensors as $sensor_id => $s_data)
        {            
            if (array_key_exists($net_ctx, $s_data['ctxs']))
            {            
                $query  = 'REPLACE INTO net_sensor_reference (net_id, sensor_id) VALUES (UNHEX(?), UNHEX(?))';
                $params = array($net_id, $sensor_id);
    
                $conn->Execute($query, $params);
            }
        }

        //Finish transaction
        if (!$conn->CompleteTrans())
        {    
            $exp_msg = _('Error! Net sensors could not be saved');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return TRUE;
    }



    /*************************************************
     **************** Static functions ***************
     *************************************************/


    /**
    * This function returns the sensors related to net with ID $net_id
    *
    * @param object  $conn     Database access object
    * @param string  $net_id   Net ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_sensors_by_id($conn, $net_id)
    {
        Ossim_db::check_connection($conn);

        $sensors = array();

        $query = 'SELECT HEX(ns.sensor_id) AS sensor_id, s.name, INET6_NTOA(s.ip) AS sensor_ip
            FROM sensor s, net_sensor_reference ns
            WHERE ns.net_id = UNHEX(?) AND s.id = ns.sensor_id
            ORDER BY s.priority DESC, s.name ASC';


        $params = array($net_id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $sensors[$rs->fields['sensor_id']] = array(
                 'name'  => $rs->fields['name'],
                 'ip'    => $rs->fields['sensor_ip']
            );

            $rs->MoveNext();
        }

        return $sensors;
    }


    /**
    * This function deletes all sensors from database
    *
    * @param object  $conn     Database access object
    * @param string  $net_id   Net ID
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean
    */
    public static function delete_all_from_db($conn, $net_id)
    {
        $obj = new Asset_net_sensors($net_id);
        return $obj->obj_delete_all_from_db($conn);
    }


    /**
    * This function deletes all sensors from database
    *
    * @param object  $conn     Database access object
    * @param string  $net_id   Net ID
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean
    */
    public function obj_delete_all_from_db($conn)
    {
        Ossim_db::check_connection($conn);

        //Check if we can delete all sensors
        $net = Asset_net::get_object($conn, $this->net_id);

        if (!is_object($net))
        {
            $exp_msg = _("Error! Network does not exists or you do not have the correct permissions");

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        //Delete all sensors

        $query  = 'DELETE FROM net_sensor_reference WHERE net_id = UNHEX(?)';
        $params = array($this->net_id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        return TRUE;
    }

}

/* End of file asset_net_sensors.inc */
/* Location: ../include/classes/asset_net_sensors.inc */
