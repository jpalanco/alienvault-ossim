<?php
/**
* asset_host_sensors.inc
*
* File asset_host_sensors.inc is used to:
*   - To manage host sensors
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
* Asset_host_sensors Class
*
* Class for managing host sensors
*
* @package    ossim-framework\Asset
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ Alienvault web page
*/

class Asset_host_sensors
{
    /**
    * Host ID
    *
    * @var string
    * @access private
    */
    private $host_id;

    /**
    * Sensors associated with the host
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
    * @param string  $host_id  Host ID
    */
    public function __construct($host_id)
    {
        $this->set_host_id($host_id);
        $this->sensors = array();
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
    * This function returns the sensors associated to host
    *
    * @return array
    */
    public function get_sensors()
    {
        return $this->sensors;
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
    * This function sets the host sensors
    *
    * @param array $sensors  Host sensors    
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
    * This function gets sensor list associated with the host from database
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

        $query = 'SELECT HEX(hs.sensor_id) AS sensor_id, s.name, inet6_ntop(s.ip) AS sensor_ip
                  FROM sensor s, host_sensor_reference hs
                  WHERE hs.host_id = UNHEX(?) AND s.id = hs.sensor_id
                  ORDER BY s.priority DESC, s.name ASC';


        $params = array($this->host_id);

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
    * This function adds a new sensor to the host
    *
    * @param object  $conn        Database access object
    * @param string  $sensor_id   Sensor ID    
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
    * This function deletes host sensor
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

        $host_id = $this->get_host_id();

        //Begin transaction
        $conn->StartTrans();

        self::delete_all_from_db($conn, $host_id);
        
        $host_ctx = Asset_host::get_ctx_by_id($conn, $host_id);
        
        foreach($this->sensors as $sensor_id => $s_data)
        {         
            if (array_key_exists($host_ctx, $s_data['ctxs']))
            { 
                $query  = 'REPLACE INTO host_sensor_reference (host_id, sensor_id) VALUES (UNHEX(?), UNHEX(?))';
                $params = array($host_id, $sensor_id);
    
                $conn->Execute($query, $params);
            }
        }

        //Finish transaction
        if (!$conn->CompleteTrans())
        {        
            $exp_msg = _('Error! Host sensors could not be saved');
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return TRUE;
    }



    /*************************************************
     **************** Static functions ***************
     *************************************************/


    /**
    * This function returns the sensors related to host with ID $host_id
    *
    * @param object  $conn     Database access object
    * @param string  $host_id  Host ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_sensors_by_id($conn, $host_id)
    {
        Ossim_db::check_connection($conn);

        $sensors = array();

        $query = 'SELECT hs.*, HEX(hs.host_id) as host_id, HEX(hs.sensor_id) as sensor_id, s.name, inet6_ntop(s.ip) AS sensor_ip
                  FROM sensor s, host_sensor_reference hs
                  WHERE hs.host_id = UNHEX(?) AND s.id = hs.sensor_id
                  ORDER BY s.priority DESC, s.name ASC';


        $params = array($host_id);

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
    * This function returns the sensors related to host with IP $ip
    *
    * @param object  $conn  Database access object
    * @param string  $ip    Host IP
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_sensors_by_ip($conn, $ip)
    {
        Ossim_db::check_connection($conn);

        $sensors = array();

        $perms_where = Asset_net::get_perms_where('n.', TRUE);

        $query = "SELECT HEX(nsr.sensor_id) AS sensor_id, inet6_ntop(s.ip) AS sensor_ip, s.name
                    FROM net_cidrs nc, net n, net_sensor_reference nsr, sensor s
                    WHERE nsr.net_id = n.id
                    AND n.id = nc.net_id
                    $perms_where
                    AND inet6_pton(?) >= nc.begin
                    AND inet6_pton(?) <= nc.end
                    AND s.id = nsr.sensor_id
                    ORDER BY nc.end-nc.begin";

        $params = array($ip, $ip);

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
    * This function saves one sensor into database
    *
    * @param object  $conn       Database access object
    * @param string  $host_id    Host ID
    * @param string  $sensor_id  Sensor ID
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean
    */
    public static function save_sensor_in_db($conn, $host_id, $sensor_id)
    {
        Ossim_db::check_connection($conn);

        //Check if we can add/update the sensor
        $host = Asset_host::get_object($conn, $host_id);

        if (!is_object($host))
        {
            $exp_msg = _("Error! Host does not exists or you don't have permissions");
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $host_ctx = $host->get_ctx();

        $host_sensors = new self($host_id);

        if ($host_sensors->add_new($conn, $sensor_id, $host->get_ctx()) == TRUE)
        {
            $sensor = $host_sensors->get_sensors();

            if (array_key_exists($host_ctx, $sensor[$sensor_id]['ctxs']))
            {
                unset($host_sensors);
    
                $query  = 'REPLACE INTO host_sensor_reference VALUES (UNHEX(?), UNHEX(?))';
                $params = array($host_id, $sensor_id);
    
                $rs = $conn->Execute($query, $params);
    
                if (!$rs)
                {
                    Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
                }                                   
            }
        }

        return TRUE;
    }


    /**
    * This function deletes sensor from database
    *
    * @param object  $conn       Database access object
    * @param string  $host_id    Host ID
    * @param string  $sensor_id  Sensor ID
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean
    */
    public static function delete_sensor_from_db($conn, $host_id, $sensor_id)
    {
        Ossim_db::check_connection($conn);

        //Check if we can delete the sensor
        if (Asset_host::is_allowed($conn, $host_id) != TRUE)
        {
            $exp_msg = _("Error! Host does not exists or you don't have permissions");
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $query  = 'DELETE FROM host_sensor_reference WHERE host_id = UNHEX(?) AND sensor_id = UNHEX(?)';
        $params = array ($host_id, $sensor_id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        return TRUE;
    }


    /**
    * This function deletes all sensors from database
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
        
        //Check if we can delete the sensor
        if (Asset_host::is_allowed($conn, $host_id) != TRUE)
        {
            $exp_msg = _("Error! Host does not exists or you don't have permissions");
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        //Delete all sensors

        $query  = 'DELETE FROM host_sensor_reference WHERE host_id = UNHEX(?)';
        $params = array($host_id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        return TRUE;
    }


    /**
    * This function returns the sensor list as string
    *
    * @param object  $conn     Database access object
    * @param string  $host_id  Host ID
    *
    * @return string
    */
    public static function get_sensors_to_string($conn, $host_id)
    {
        Ossim_db::check_connection($conn);

        $s_string = array();

        $query  = 'SELECT name FROM host_sensor_reference, sensor
           WHERE sensor_id = id AND host_id = UNHEX(?)';

        $params = array($host_id);

        $conn->SetFetchMode(ADODB_FETCH_ASSOC);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $s_string[] = $rs->fields['name'];

            $rs->MoveNext();
        }

        return implode(',', $s_string);
    }
}

/* End of file asset_host_sensors.inc */
/* Location: ../include/classes/asset_host_sensors.inc */