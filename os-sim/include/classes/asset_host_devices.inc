<?php

/**
 * asset_host_devices.inc
 *
 * File asset_host_devices.inc is used to:
 *   - To manage host devices
 *
 *
 * License:
 *
 * Copyright (c) 2003-2006 ossim.net
 * Copyright (c) 2007-2014 AlienVault
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
 * @copyright  2007-2014 AlienVault
 * @link       https://www.alienvault.com/
 */


/**
 * Asset_host_devices Class
 *
 * Class for managing host devices
 *
 * @package    ossim-framework\Asset
 * @autor      AlienVault INC
 * @copyright  2007-2014 AlienVault
 * @link       https://www.alienvault.com/ Alienvault web page
 */
class Asset_host_devices
{
    /**
     * Host ID
     *
     * @var    string $host_id
     * @access private
     */
    private $host_id;


    /**
     * All device types
     *
     * @var    array $device_types
     * @access private
     */
    private $device_types;


    /**
     * Devices associated with the host
     *
     * @var    array $devices
     * @access private
     */
    private $devices;


    /**
     * Function __construct
     *
     * This function sets up the class
     *
     * @param object $conn    Database access object
     * @param string $host_id Host ID
     *
     * @access public
     */
    public function __construct($conn, $host_id)
    {
        $this->set_host_id($host_id);

        $d_obj              = new Devices($conn);
        $this->device_types = $d_obj->get_devices();

        $this->devices = array();
    }


    /**
     * Function get_host_id
     *
     * This function returns the host ID
     *
     * @access public
     * @return string Host ID
     */
    public function get_host_id()
    {
        return $this->host_id;
    }


    /**
     * Function get_devices
     *
     * This function returns the devices associated with the host
     *
     * @access public
     * @return array  Devices list
     */
    public function get_devices()
    {
        return $this->devices;
    }


    /**
     * Function get_device_types
     *
     * This function returns all devices types
     *
     * @access public
     * @return array  Device types list
     */
    public function get_device_types()
    {
        return $this->device_types;
    }


    /**
     * Function get_devices_list
     *
     * This function returns the devices associated with the host
     *
     * @access public
     * @return array  Devices list
     */
    public function get_devices_list()
    {
        $list = array();

        foreach ($this->devices as $t_id => $type)
        {
            foreach ($type as $s_id => $name)
            {
                $l_id = $t_id . ':' . $s_id;

                $list[$l_id] = $name;
            }
        }

        return $list;
    }


    /**
     * Function set_host_id
     *
     * This function sets the host ID
     *
     * @param string $host_id Host ID
     *
     * @access public
     * @return void
     * @throws Av_exception   If host ID is not valid
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
     * Function set_devices
     *
     * This function sets host devices
     *
     * @param array $devices Host devices
     *
     * @access public
     * @return void
     */
    public function set_devices($devices)
    {
        $this->devices = array();

        if (is_array($devices) && !empty($devices))
        {
            foreach ($devices as $d_data)
            {
                $d_data = explode(':', $d_data);

                $this->add_av_device($d_data[0], $d_data[1]);
            }
        }
    }


    /**
     * Function load_from_db
     *
     * This function sets the host devices from database
     *
     * @param object $conn  Database access object
     * @param bool   $cache [Optional] Use cached information
     *
     * @access public
     * @return void
     * @throws Av_exception If host ID doesn't exists in the System or there is a connection error
     */
    public function load_from_db($conn, $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        $query  = 'SELECT type, subtype FROM host_types WHERE host_id = UNHEX(?)';
        $params = array($this->host_id);

        $rs = ($cache == TRUE) ? $conn->CacheExecute($query, $params) : $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $this->add_av_device($rs->fields['type'], $rs->fields['subtype']);

            $rs->MoveNext();
        }
    }


    /**
     * Function add_av_devices
     *
     * This function adds a new device type according Alienvault device classification
     *
     * @param int $type    Device type
     * @param int $subtype [Optional] Device subtype
     *
     * @access public
     * @return bool        TRUE if device is added satisfactorily
     */
    public function add_av_device($type, $subtype = 0)
    {
        $type    = intval($type);
        $subtype = intval($subtype);

        if (array_key_exists($type, $this->device_types))
        {
            $cnd_1 = ($type != 8 && $subtype != 0);
            $cnd_2 = (array_key_exists($subtype, $this->device_types[$type]['subtypes']));

            $subtype = ($cnd_1 && $cnd_2) ? $subtype : 0;

            //Device Name
            $device_name = $this->device_types[$type]['name'];
            $device_name .= ($subtype > 0) ? ':' . $this->device_types[$type]['subtypes'][$subtype] : '';

            $this->devices[$type][$subtype] = $device_name;

            return TRUE;
        }

        return FALSE;
    }


    /**
     * Function delete
     *
     * This function deletes a device from the host
     *
     * @param int $type    Device type
     * @param int $subtype [Optional] Device subtype
     *
     * @access public
     * @return bool        TRUE if correct device deletion
     */
    public function delete($type, $subtype = 0)
    {
        $type    = intval($type);
        $subtype = (empty($subtype)) ? 0 : intval($subtype);

        if (!empty($this->devices[$type][$subtype]))
        {
            unset($this->devices[$type][$subtype]);

            if (!count($this->devices[$type]))
            {
                unset($this->devices[$type]);
            }

            return TRUE;
        }

        return FALSE;
    }


    /**
     * Function add_nmap_device
     *
     * This function adds a new NMAP device
     *
     * @param string $nmap_device_type NMAP device type
     *
     * @access public
     * @return bool                    TRUE if device is added satisfactorily
     */
    public function add_nmap_device($nmap_device_type)
    {
        $av_device = Devices::translate_nmap_device($nmap_device_type);

        $this->add_av_device($av_device['type'], $av_device['subtype']);
    }


    /**
     * Function save_all_in_db
     *
     * This function saves all devices into database
     *
     * @param object $conn Database access object
     *
     * @access public
     * @return bool         TRUE if saved correctly
     * @throws Av_exception If an error occurred
     */
    public function save_all_in_db($conn)
    {
        Ossim_db::check_connection($conn);

        //Begin transaction
        $conn->StartTrans();

        self::delete_all_from_db($conn, $this->host_id);

        foreach ($this->devices as $type => $subtypes)
        {
            foreach ($subtypes as $subtype => $_aux)
            {
                $query  = 'REPLACE INTO host_types (host_id, type, subtype) VALUES (UNHEX(?), ?, ?)';
                $params = array($this->host_id, $type, $subtype);

                $conn->Execute($query, $params);
            }
        }

        //Finish transaction
        if (!$conn->CompleteTrans())
        {
            $exp_msg = _('Error! Host devices could not be saved');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return TRUE;
    }


    /*************************************************
     **************** Static functions ***************
     *************************************************/


    /**
     * Function save_device_in_db
     *
     * This function saves device into database
     *
     * @param object $conn    Database access object
     * @param string $host_id Host ID
     * @param int    $type    Device type
     * @param int    $subtype [Optional] Device subtype
     *
     * @access public
     * @return bool           TRUE if saved correctly
     * @throws Av_exception   If an error occurred
     */
    public static function save_device_in_db($conn, $host_id, $type, $subtype = 0)
    {
        Ossim_db::check_connection($conn);

        //Check if we can add/update the device                                                    
        if (Asset_host::is_allowed($conn, $host_id) != TRUE)
        {
            $exp_msg = _("Error! Host does not exists or you don't have permissions");

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $host_devices = new self($conn, $host_id);

        if ($host_devices->add_av_device($type, $subtype) == TRUE)
        {
            $devices = $host_devices->get_devices();
            unset($host_devices);

            $subtype = array_keys($devices[$type]);

            $query  = 'REPLACE INTO host_types (host_id, type, subtype) VALUES (UNHEX(?), ?, ?)';
            $params = array($host_id, $type, $subtype[0]);

            $rs = $conn->Execute($query, $params);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }

            return TRUE;
        }

        return FALSE;
    }


    /**
     * Function delete_device_from_db
     *
     * This function deletes device from database
     *
     * @param object $conn    Database access object
     * @param string $host_id Host ID
     * @param int    $type    Device type
     * @param int    $subtype [Optional] Device subtype
     *
     * @access public
     * @return bool           TRUE if correct deletion
     * @throws Av_exception   If an error occurred
     */
    public static function delete_device_from_db($conn, $host_id, $type, $subtype = 0)
    {
        Ossim_db::check_connection($conn);

        //Check if we can delete the device               
        if (Asset_host::is_allowed($conn, $host_id) != TRUE)
        {
            $exp_msg = _("Error! Host does not exists or you don't have permissions");

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $type    = intval($type);
        $subtype = (empty($subtype)) ? 0 : intval($subtype);

        $query = 'DELETE FROM host_types WHERE host_id = UNHEX(?)
            AND type = ? AND subtype = ?';

        $params = array($host_id, $type, $subtype);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        return TRUE;
    }


    /**
     * Function delete_all_from_db
     *
     * This function deletes all devices from database
     *
     * @param object $conn    Database access object
     * @param string $host_id Host ID
     *
     * @access public
     * @return bool           TRUE if correct deletion
     * @throws Av_exception   If an error occurred
     */
    public static function delete_all_from_db($conn, $host_id)
    {
        Ossim_db::check_connection($conn);

        //Check if we can delete the device               
        if (Asset_host::is_allowed($conn, $host_id) != TRUE)
        {
            $exp_msg = _("Error! Host does not exists or you don't have permissions");

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        //Delete all devices        
        $query  = 'DELETE FROM host_types WHERE host_id = UNHEX(?)';
        $params = array($host_id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        return TRUE;
    }


    /**
     * Function get_devices_to_string
     *
     * This function returns the device list as string
     *
     * @param object $conn    Database access object
     * @param string $host_id Host ID
     *
     * @access public
     * @return string
     * @throws Av_exception   If DB error
     */
    public static function get_devices_to_string($conn, $host_id)
    {
        Ossim_db::check_connection($conn);

        $d_string = array();

        $query = "SELECT CONCAT_WS('', dt_1.name, IF(dt_2.name IS NULL, '', CONCAT(':', dt_2.name))) AS device
            FROM host_types
            LEFT JOIN device_types dt_1 ON dt_1.id = type
            LEFT JOIN device_types dt_2 ON dt_2.id = subtype
            WHERE host_id = UNHEX(?)";

        $params = array($host_id);

        $conn->SetFetchMode(ADODB_FETCH_ASSOC);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $d_string[] = $rs->fields['device'];

            $rs->MoveNext();
        }

        return implode('<br/>', $d_string);
    }


    /**
     * Function check_device_connectivity
     *
     * This function checks if any network device has events
     *
     * @param object $conn      DB Object Connection
     * @param string $host_id   Host ID
     * @param string $plugin_id Plugin ID
     * @param string $sensor_id [Optional] Sensor ID
     * @param bool   $cache     Cache Flag
     *
     * @return bool TRUE if device has events
     * @throws Av_exception  If there is a connection or query error
     */
    public static function check_device_connectivity($conn, $host_id, $plugin_id, $sensor_id = '', $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        if ($sensor_id == '')
        {
            $sql   = "SELECT device_id FROM alienvault_siem.ac_acid_event WHERE plugin_id=? AND (src_host=UNHEX(?) OR dst_host=UNHEX(?))";
            $param = array(intval($plugin_id), $host_id, $host_id);
        }
        else
        {
            $sql   = "SELECT device_id FROM alienvault_siem.ac_acid_event LEFT JOIN alienvault_siem.device ON device.id=ac_acid_event.device_id WHERE plugin_id=? AND (src_host=UNHEX(?) OR dst_host=UNHEX(?)) AND sensor_id=UNHEX(?)";
            $param = array(intval($plugin_id), $host_id, $host_id, $sensor_id);
        }

        $rs = ($cache) ? $conn->CacheExecute($sql, $param) : $conn->Execute($sql, $param);

        if ($rs === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!$rs->EOF)
        {
            return TRUE;
        }
        else
        {
            return FALSE;
        }
    }
}

/* End of file asset_host_devices.inc */
/* Location: ../include/classes/asset_host_devices.inc */ 
