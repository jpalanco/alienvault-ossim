<?php
/**
 * asset_host.inc
 *
 * File asset_host.inc is used to:
 *   - To manage hosts
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


require_once 'av_config.php';


/**
 * Asset_host Class
 *
 * Class for managing hosts
 *
 * @package    ossim-framework\Asset
 * @autor      AlienVault INC
 * @copyright  2007-2014 AlienVault
 * @link       https://www.alienvault.com/ AlienVault web page
 */
class Asset_host extends Asset
{
    /**
     * Fully qualified domain name
     *
     * @var    string $fqdns
     * @access protected
     */
    protected $fqdns;


    /**
     * Host operating system
     *
     * @var    array $os
     * @access protected
     */
    protected $os;


    /**
     * Host model
     *
     * @var    string $model
     * @access protected
     */
    protected $model;

    /**
     * Host location (Latitude, longitude and zoom)
     *
     * @var    array $location
     * @access protected
     */
    protected $location;


    /**
     * Host devices
     *
     * @var    Asset_host_devices $devices
     * @access protected
     */
    protected $devices;


    /**
     * Function __construct
     *
     * This function sets up the class
     *
     * @param object $conn Database access object
     * @param string $id   Host ID
     *
     * @access public
     */
    public function __construct($conn, $id)
    {
        $this->set_id($id);

        $conf = $GLOBALS['CONF'];

        if (!$conf)
        {
            $conf            = new Ossim_conf();
            $GLOBALS['CONF'] = $conf;
        }

        $asset_value = $conf->get_conf('def_asset');

        if ($asset_value == '')
        {
            $asset_value = 2;
        }


        $this->ctx         = Session::get_default_ctx();
        $this->name        = '';
        $this->ips         = new Asset_host_ips($id);
        $this->descr       = '';
        $this->icon        = NULL;
        $this->fqdns       = '';
        $this->external    = 0;
        $this->location    = array(
            'lat'  => NULL,
            'lon'  => NULL,
            'zoom' => 4
        );
        $this->asset_value = $asset_value;
        $this->os          = array();
        $this->model       = '';
        $this->devices     = new Asset_host_devices($conn, $id);
        $this->sensors     = new Asset_host_sensors($id);
    }


    /**
     * Function get_ips
     *
     * This function returns the host IP list
     *
     * @access public
     * @return Asset_host_ips
     */
    public function get_ips()
    {
        return $this->ips;
    }


    /**
     * Function get_fqdns
     *
     * This function returns the FQDNs from host
     *
     * @access public
     * @return string
     */
    public function get_fqdns()
    {
        return $this->fqdns;
    }


    /**
     * Function get_location
     *
     * This function returns host location
     *
     * @access public
     * @return array  (keys: lat, lon, zoom)
     */
    public function get_location()
    {
        return $this->location;
    }


    /**
     * Function get_os
     *
     * This function returns host Operating System data (Property with ID = 3)
     *
     * @access public
     * @return array
     */
    public function get_os()
    {
        return $this->os;
    }


    /**
     * Function get_model
     *
     * This function returns host model
     *
     * @access public
     * @return string
     */
    public function get_model()
    {
        return $this->model;
    }


    /**
     * Function get_devices
     *
     * This function returns the devices associated with the host
     *
     * @access public
     * @return Asset_host_devices
     */
    public function get_devices()
    {
        return $this->devices;
    }


    /**
     * Function get_asset_type
     *
     * This function returns the asset type
     *
     * @access public
     * @return string
     */
    public function get_asset_type()
    {
        return 'host';
    }


    /**
     * Function get_hids_status
     *
     * This function returns the status of HIDS agent
     *
     *   0 --> GRAY:   HIDS agent is not deployed
     *   1 --> RED:    HIDS agent is deployed with status 'Never Connected'
     *   2 --> YELLOW: HIDS agent is deployed with status 'Disconnected'
     *   3 --> GREEN:  HIDS agent is deployed with status 'Active' or 'Active/Local'
     *
     * @param object $conn Database access object
     *
     * @throws Av_exception If a connection error occurred
     *
     * @access public
     *
     * @return integer
     */
    public function get_hids_status($conn)
    {
        Ossim_db::check_connection($conn);

        $hids_status = 0;

        $query = "SELECT agent_status FROM hids_agents WHERE host_id = UNHEX(?) ORDER BY agent_status DESC LIMIT 1";

        $params = array($this->get_id());
        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!$rs->EOF)
        {
            $hids_status = intval($rs->fields['agent_status']);
            $hids_status = ($hids_status > 3) ? 3 : $hids_status;
        }

        return $hids_status;
    }


    /**
     * Function get_related_hids_agents
     *
     * This function returns HIDS agents related to asset
     *
     * @param object  $conn        Database access object
     * @param string  $asset_id    Asset ID
     * @param string  $sensor_id   [Optional] Sensor ID (Table alienvault.sensor)
     *
     * @param object $conn Database access object
     *
     * @throws Av_exception If a connection error occurred
     *
     * @access public
     *
     * @return array
     */
    public static function get_related_hids_agents($conn, $asset_id, $sensor_id = NULL)
    {
        Ossim_db::check_connection($conn);

        $agents = array();

        $query = "SELECT HEX(sensor_id) AS sensor_id, agent_id, agent_name, agent_ip, agent_status FROM hids_agents WHERE host_id = UNHEX(?)";

        if (!empty($sensor_id))
        {
            $query .= " AND sensor_id = UNHEX(?)";
        }

        $params = array($asset_id, $sensor_id);
        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $agent_key = md5($rs->fields['sensor_id'].'#'.$rs->fields['agent_id']);

            $agents[$agent_key] = array(
                'sensor_id' => $rs->fields['sensor_id'],
                'agent_id'  => $rs->fields['agent_id'],
                'name'      => $rs->fields['agent_name'],
                'ip_cidr'   => $rs->fields['agent_ip'],
                'status'    => intval($rs->fields['agent_status'])
            );

            $rs->MoveNext();
        }

        return $agents;
    }

    //returns total number of assets
    public static function get_assets_count($conn) {
        Ossim_db::check_connection($conn);
        $query = "SELECT COUNT(*) as counter FROM alienvault.host";
        $rs = $conn->Execute($query);
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            $count = $rs->fields['counter'];
        }
        return $count;
    }


    /**
     * Function get_vulnerability_number
     *
     * This function returns the number of host vulnerabilities
     *
     * @param object $conn Database access object
     * @param string $id   Host ID
     *
     * @access public
     * @return int          Number of vulnerabilities
     * @throws Av_exception If a connection error occurred
     */
    public static function get_vulnerability_number($conn, $id)
    {
        Ossim_db::check_connection($conn);

        $count = 0;

        $query = "SELECT COUNT(lr.hostIP) AS counter
           FROM alienvault.host h, alienvault.host_ip hi, alienvault.vuln_nessus_latest_results lr
           WHERE hi.host_id = h.id AND h.id = UNHEX(?)
           AND h.ctx = lr.ctx AND lr.hostIP = INET6_NTOA(hi.ip)";

        $params = array($id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            $count = $rs->fields['counter'];
        }

        return $count;
    }


    /**
     * Function get_vulnerabilities
     *
     * This function returns the host vulnerabilities
     *
     * @param object   $conn     Database access object
     * @param string   $tables   [Optional] Database tables separated by comma (Join with main table)
     * @param array    $filters  [Optional] SQL statements (WHERE, LIMIT, ORDER BY ...)
     * @param boolean  $cache    [Optional] Use cached information
     *
     * @access public
     * @return array          List of vulnerabilities
     * @throws Av_exception If a connection error occurred
     */
    public function get_vulnerabilities($conn, $tables = '', $filters = array(), $cache = FALSE)
    {
        $where = " host.id = UNHEX('" . $this->id . "')";

        if (!empty($filters['where']))
        {
            $filters['where'] = $where . ' AND ' . $filters['where'];
        }
        else
        {
            $filters['where'] = $where;
        }

        return Vulnerabilities::get_vulnerabilities($conn, $tables, $filters, $cache);
    }


    /**
     * Function get_status
     *
     * This function returns the machine state property (ID = 7)
     *
     * @param object $conn Database access object
     *
     * @cccess public
     * @return string up, down or unknown if not found
     */
    public function get_status($conn)
    {
        return Asset_host_properties::get_status_by_host($conn, $this->get_id());
    }


    /**
     * Function get_users
     *
     * This function returns the user property (ID = 8)
     *
     * @param object $conn    Database access object
     * @param array  $filters [Optional] Sql query options
     *
     * @access public
     * @return array
     */
    public function get_users($conn, $filters = array())
    {
        return Asset_host_properties::get_users_by_host($conn, $this->get_id(), $filters);
    }


    /**
     * This function returns the alarms related to the host
     *
     * @param object  $conn      Database access object
     * @param string  $id        Host ID
     * @param integer $from      From offset
     * @param integer $max       [Optional] Maximum elements per page
     * @param string  $date_from [Optional] Date from filter
     * @param string  $date_to   [Optional] Date to filter
     * @param string  $filter    [Optional] SQL query
     * @param string  $order     [Optional] SQL order statement
     *
     * @access public
     * @return array        Alarms list and counter
     * @throws Av_exception If a connection error occurred
     */
    public static function get_alarms($conn, $id, $from, $max, $date_from = '', $date_to = '', $filter = '', $order = 'a.timestamp DESC')
    {
        Ossim_db::check_connection($conn);

        $criteria = array(
            'src_ip'        => '',
            'dst_ip'        => '',
            'hide_closed'   => 1,
            'order'         => "ORDER BY $order",
            'inf'           => $from,
            'sup'           => $from + $max,
            'date_from'     => $date_from,
            'date_to'       => $date_to,
            'query'         => $filter,
            'directive_id'  => '',
            'intent'        => 0,
            'sensor'        => '',
            'tag'           => '',
            'num_events'    => '',
            'num_events_op' => 0,
            'plugin_id'     => '',
            'plugin_sid'    => '',
            'ctx'           => '',
            'host'          => $id,
            'net'           => '',
            'host_group'    => ''
        );

        return Alarm::get_list($conn, $criteria);
    }


    /**
     * Function has_alarms
     *
     * This function returns true if current host id has alarms
     *
     * @param object $conn Database access object
     * @param string $id   Host ID
     *
     * @access public
     * @return bool         True if host has alarms
     * @throws Av_exception If a connection error occurred
     */
    public static function has_alarms($conn, $id)
    {
        Ossim_db::check_connection($conn);

        return Alarm::has_alarms($conn, 'host', $id);
    }

    /**
     * Function get_highest_risk_alarms
     *
     * This function returns the highest alarm risk from the given asset
     *
     * @param object $conn Database access object
     * @param string $id   Asset ID
     *
     * @return Hash
     */
    public static function get_highest_risk_alarms($conn, $id)
    {
        $criteria = array('host' => $id);

        return Alarm::get_highest_risk_by_asset($conn, $criteria);
    }

    /**
     * Function has_events
     *
     * This function returns true if current host id has events
     *
     * @param object $conn Database access object
     * @param string $id   Host ID
     *
     * @access public
     * @return bool         If host has events
     * @throws Av_exception If a connection error occurred
     */
    public static function has_events($conn, $id)
    {
        Ossim_db::check_connection($conn);

        return SIEM::has_events($conn, 'host', $id);
    }


    /**
     * Function get_properties
     *
     * This function returns the properties related to host
     *
     * @param object $conn    Database access object
     * @param array  $filters Sql query options
     *
     * @access public
     * @return array List of properties
     */
    public function get_properties($conn, $filters = array())
    {
        if (empty($filters['where']))
        {
            $filters['where'] = "h_id = UNHEX('" . $this->id . "')";
        }
        else
        {
            $filters['where'] .= " AND h_id = UNHEX('" . $this->id . "')";
        }

        $properties = Asset_host_properties::get_all($conn, $filters);

        return $properties;
    }


    /**
     * Function get_software
     *
     * This function returns the software related to host
     *
     * @param object  $conn     Database access object
     * @param array   $filters  [Optional] SQL statements (WHERE, LIMIT, ORDER BY ...)
     *
     * @access public
     * @return array  List of software
     */
    public function get_software($conn, $filters = array())
    {
        if (empty($filters['where']))
        {
            $filters['where']  = "h.id = UNHEX('".$this->id."')";
        }
        else
        {
            $filters['where'] .= " AND h.id = UNHEX('".$this->id."')";
        }

        $software = Asset_host_software::get_list($conn, $filters);

        return $software;
    }


    /**
     * Function get_servies
     *
     * This function returns the services related to host
     *
     * @param object $conn    Database access object
     * @param array  $filters Sql query options
     *
     * @access public
     * @return array  List of services
     */
    public function get_services($conn, $filters = array())
    {
        if (empty($filters['where']))
        {
            $filters['where'] = "h.id = UNHEX('" . $this->id . "')";
        }
        else
        {
            $filters['where'] .= " AND h.id = UNHEX('" . $this->id . "')";
        }

        return Asset_host_services::get_list($conn, $filters);
    }


    /**
     * Function get_vulns_by_service
     *
     * This function returns the vulnerabilities found in vuln_nessus_latest_results table
     * related to service/port pair
     *
     * @param object $conn    Database access object
     * @param string $service Service
     * @param int    $port    Service port
     *
     * @access public
     * @return array
     */
    public function get_vulns_by_service($conn, $service, $port)
    {
        $host_ips = $this->get_ips();
        $ips      = array_keys($host_ips->get_ips());

        return Asset_host_services::get_vulns_by_service($conn, $ips, $this->ctx, $service, $port);
    }


    /**
     * Function get_plugins
     *
     * This function returns the plugins related to asset
     *
     * @param object   $conn       Database access object
     * @param bool     $edit_mode  [Optional] Flag to know if return empty assets as well
     * @param string   $sensor     [Optional] Show only the plugins from this sensor
     *
     * @throws Exception  If a connection error occurred
     *
     * @return array List of plugins
     */
    public function get_plugins($conn, $edit_mode = FALSE, $sensor_id = '')
    {
        $sensors = $this->get_sensors()->get_sensors();

        // Get only plugins from one specified sensor
        if (valid_hex32($sensor_id))
        {
            $_sensor_aux = $sensors[$sensor_id];
            $sensors     = array($sensor_id => $_sensor_aux);
        }

        $assets = array($this->id => array('name' => $this->name));

        return self::get_plugins_by_sensor($conn, $sensors, $assets, $edit_mode);
    }


    /**
     * Function get_events
     *
     * This function returns the events related to asset
     *
     * @param object $conn Database access object
     * @param number $from       [Optional]
     * @param number $maxrows    [Optional]
     * @param string $order      [Optional]
     * @param string $torder     [Optional]
     * @param string $search_str [Optional]
     *
     * @throws Exception  If a connection error occurred
     *
     * @return array List of events
     */
    public function get_events($conn, $from = 0, $maxrows = 50, $order = 'timestamp', $torder = 'DESC', $search_str = '')
    {
        $siem  = new Siem(TRUE);

        if ($search_str != '')
        {
            $search_str = escape_sql($search_str, $conn);
        }

        return $siem->get_events_sp($this->id, 'host', $from, $maxrows, "$order $torder", $search_str);
    }


    /**
     * Function get_events_status
     *
     * This function returns the event count and level related to asset
     *
     *
     * @param object $conn Database access object [Necessary only in asset_net]
     *
     *
     * @throws Exception  If a connection error occurred
     *
     * @return array Event Count and Event Level values to details info
     */
    public function get_events_status($conn)
    {
        $siem = new Siem(TRUE); // Perms byPass TRUE

        // Get Total
        $siem->add_criteria('src_host', $this->id);
        $event_count_src = $siem->get_events_total();

        $siem->clear_criteria();
        $siem->add_criteria('dst_host', $this->id);
        $event_count_dst = $siem->get_events_total();

        $siem->clear_criteria();
        $siem->add_criteria('src_host', $this->id);
        $siem->add_criteria('dst_host', $this->id);
        $event_count = $event_count_src + $event_count_dst - $siem->get_events_total();

        $event_level = ($event_count > 0) ? 1 : 0;

        // Total with risk = 1
        $siem->clear_criteria();
        $siem->add_criteria(array('src_host', 'dst_host'), $this->id);
        $siem->add_criteria('ossim_risk_a', 1);

        $event_count_medium = $siem->get_events_total();

        // Total with risk > 1
        $siem->clear_criteria();
        $siem->add_criteria(array('src_host', 'dst_host'), $this->id);
        $siem->add_criteria('ossim_risk_a', 1, '>');

        $event_count_high = $siem->get_events_total();

        // Calculate level
        if ($event_count_high > 0)
        {
            $event_level = 3;
        }
        elseif ($event_count_medium > 0)
        {
            $event_level = 2;
        }

        return array($event_count, $event_level);
    }


    /**
     * Function get_suggestions
     *
     * This function returns the suggestions messages related to the asset
     *
     * @param array   $filters   Filters to get the messages from the API
     *
     * @throws Exception  If a connection error occurred
     *
     * @return array List of messages and total
     */
    public function get_suggestions($filters = array(), $pagination = array())
    {
        $status = new System_notifications();

        $filters['component_id'] = Util::uuid_format($this->id);

        if (empty($filters['level']))
        {
            $filters['level'] = 'info,warning,error';
        }

        if (empty($filters['order_by']))
        {
            $filters['order_by'] = 'creation_time';
        }

        if (empty($filters['order_desc']))
        {
            $filters['order_desc'] = 'false';
        }

        return $status->get_status_messages($filters, $pagination);
    }


    /**
     * Function set_name
     *
     * This function sets the host name
     *
     * @param string $name Host name
     *
     * @access public
     * @return void
     */
    public function set_name($name)
    {
        if ($name != '')
        {
            $name = self::create_valid_name($name);

            $this->name = Util::htmlentities($name);
        }
    }


    /**
     * Function set_ips
     *
     * This function sets IP list
     *
     * @param array $ips Host IPs
     *
     * @access public
     * @return void
     */
    public function set_ips($ips)
    {
        $this->ips->set_ips_macs($ips);
    }


    /**
     * Function set_macs
     *
     * This function sets MACs list
     *
     * @param array $macs Host MACS
     *
     * @access public
     * @return void
     */
    public function set_macs($macs)
    {
        $this->ips->set_ips_macs($macs);
    }


    /**
     * Function set_fqdns
     *
     * This function sets the FQDNs from host
     *
     * @param string $fqdns Host FQDNs
     *
     * @access public
     * @return void
     */
    public function set_fqdns($fqdns)
    {
        $fqdns = preg_replace('/[\r\n\t]+/', '', $fqdns);

        $this->fqdns = Util::htmlentities($fqdns);
    }


    /**
     * Function set_location
     *
     * This function sets host location
     *
     * @param double     $latitude  Host latitude
     * @param double     $longitude Host longitude
     * @param string|int $zoom      [Optional] Zoom
     *
     * @access public
     * @return void
     * @throws Av_exception If location format is not correct
     */
    public function set_location($latitude, $longitude, $zoom = '')
    {
        if ($latitude == '' && $longitude == '')
        {
            $this->location = array(
                'lat'  => NULL,
                'lon'  => NULL,
                'zoom' => 4
            );
        }
        elseif (is_numeric($latitude) && is_numeric($longitude))
        {
            $zoom = ($zoom != '') ? intval($zoom) : 4;

            $this->location = array(
                'lat'  => $latitude,
                'lon'  => $longitude,
                'zoom' => $zoom
            );
        }
        else
        {
            $exp_msg = _('Location format is not allowed');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
    }


    /**
     * Function set_os
     *
     * This function sets host Operating System data
     *
     * @param array $os  Host Operating System data
     *
     * @access public
     * @return void
     */
    public function set_os($os_data)
    {
        $this->os = $os_data;
    }


    /**
     * Function set_model
     *
     * This function sets host model
     *
     * @param string $model  Host model
     *
     * @access public
     * @return void
     */
    public function set_model($model)
    {
        $this->model = $model;
    }


    /**
     * Function set_devices
     *
     * This function sets the host devices
     *
     * @param array $devices Host devices
     *
     * @access public
     * @return void
     */
    public function set_devices($devices)
    {
        $this->devices->set_devices($devices);
    }


    /**
     * Function load_from_db
     *
     * This function sets the host data from database
     *
     * @param object $conn  Database access object
     * @param bool   $cache [Optional] Use cached information
     *
     * @access public
     * @return void
     * @throws Av_exception  If host ID doesn't exists in the System or there is a connection error
     */
    public function load_from_db($conn, $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        //Getting host information
        $query  = 'SELECT h.*, HEX(id) AS id, HEX(ctx) AS ctx FROM host h WHERE h.id = UNHEX(?)';
        $params = array($this->get_id());

        $rs = ($cache == TRUE) ? $conn->CacheExecute($query, $params) : $conn->Execute($query, $params);

        if (empty($rs->fields['id']))
        {
            $exp_msg = _('Error! Asset not found');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $this->ctx  = $rs->fields['ctx'];
        $this->name = $rs->fields['hostname'];

        $this->ips->load_from_db($conn, $cache);

        $this->descr    = $rs->fields['descr'];
        $this->icon     = $rs->fields['icon'];
        $this->fqdns    = $rs->fields['fqdns'];
        $this->external = $rs->fields['external_host'];

        $latitude = (empty($rs->fields['lat'])) ? NULL : $rs->fields['lat'];

        $lon = explode(';', $rs->fields['lon']);

        if (is_array($lon) && !empty($lon[0]))
        {
            $longitude = $lon[0];
            $zoom      = (empty($lon[1])) ? 4 : $lon[1];
        }
        else
        {
            $longitude = NULL;
            $zoom      = 4;
        }

        $this->location = array(
            'lat'  => $latitude,
            'lon'  => $longitude,
            'zoom' => $zoom
        );

        $this->asset_value = $rs->fields['asset'];


        $filters = array(
            'where'    => "h.id = UNHEX('".$this->get_id()."') AND host_properties.property_ref IN (3, 14)",
            'order_by' => 'hsr.relevance DESC'
        );

        list($properties ,) = Asset_host_properties::get_list($conn, $filters);
        $properties = array_pop($properties);

        $this->os    = $properties[3][0];
        $this->model = $properties[14][0]['value'];


        $this->devices->load_from_db($conn, $cache);
        $this->sensors->load_from_db($conn, $cache);
    }


    /**
     * Function save_in_db
     *
     * This function saves host into database
     *
     * @param object $conn           Database access object
     * @param bool   $report_changes [Optional] Report changes to other components
     *
     * @access public
     * @return bool
     * @throws Av_exception If an error occurred
     */
    public function save_in_db($conn, $report_changes = TRUE)
    {
        Ossim_db::check_connection($conn);

        $id       = $this->get_id();
        $host_ips = $this->get_ips();

        $ips_string = $host_ips->get_ips('string');
        $ips        = array_keys($host_ips->get_ips());

        $ctx     = $this->get_ctx();
        $old_ctx = self::get_ctx_by_id($conn, $id);

        $name = $this->get_name();

        foreach ($ips as $ip)
        {
            $host_ids = self::get_id_by_ips($conn, $ip, $ctx);

            unset($host_ids[$id]);

            if (!empty($host_ids))
            {
                $exp_msg = _('Error! IP not allowed.') . " IP $ip " . _('already exists for this entity');

                Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
            }

            $cnd_1 = Session::get_net_where() != '' && !Session::only_ff_net();
            $cnd_2 = self::is_ip_in_cache_cidr($conn, $ip, $ctx, TRUE);

            if ($cnd_1 && !$cnd_2)
            {
                $exp_msg = _("Error! IP $ip not allowed.  Check your asset filter");

                Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
            }
        }

        $is_in_db = self::is_in_db($conn, $id);

        Util::disable_perm_triggers($conn, TRUE);

        self::disable_host_update($conn, TRUE);

        //Begin transaction
        $conn->StartTrans();

        $query = 'REPLACE INTO host (
                    id,
                    ctx,
                    hostname,
                    external_host,
                    fqdns,
                    asset,
                    rrd_profile,
                    alert,
                    persistence,
                    nat,
                    descr,
                    lat,
                    lon,
                    country,
                    icon)
                VALUES (UNHEX(?), UNHEX(?), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        //Host Location
        $location = $this->get_location();

        $latitude  = $location['lat'];
        $longitude = $location['lon'];
        $longitude .= (!empty($location['zoom'])) ? ';' . $location['zoom'] : 4;

        $country = ';';

        if ($location['lat'] != '' && $location['lon'] != '')
        {
            $geoloc = new Geolocation();

            list ($country_code, $country_name) = $geoloc->get_country_by_coordinates($location['lat'], $location['lon']);
            $geoloc->close();

            $country = $country_code . ';' . $country_name;
        }


        $params = array(
            $id,
            $ctx,
            $name,
            $this->get_external(),
            $this->get_fqdns(),
            $this->get_asset_value(),
            0,
            0,
            0,
            0,
            $this->get_descr(),
            $latitude,
            $longitude,
            $country,
            $this->get_icon()
        );

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }


        //Save Ips
        $this->ips->save_all_in_db($conn);

        //Host-Network reference
        self::set_host_net_reference($conn, $id);


        //Save Operating System and Model
        $os    = $this->get_os();
        $model = $this->get_model();

        Asset_host_properties::delete_property_from_db($conn, $id, 3);
        if (!empty($os['value']))
        {
            Asset_host_properties::save_property_in_db($conn, $id, 3, $os['value'], $os['source']['id']);
        }

        Asset_host_properties::delete_property_from_db($conn, $id, 14);
        if (!empty($model))
        {
            Asset_host_properties::save_property_in_db($conn, $id, 14, $model, 1);
        }


        //Save devices
        $this->devices->save_all_in_db($conn);

        //Save sensors
        $this->sensors->save_all_in_db($conn);

        if ($is_in_db == TRUE)
        {
            //Host services
            $host_services = new Asset_host_services($id);
            $host_services->load_from_db($conn);
            $services = $host_services->get_services();

            $new_ips   = FALSE;
            $_host_ips = $host_ips->get_ips();

            foreach ($services as $ip => $s_data)
            {
                if (!array_key_exists($ip, $_host_ips))
                {
                    $host_services->delete($ip);
                    $new_ips = TRUE;
                }
            }

            if ($new_ips == TRUE)
            {
                $host_services->save_all_in_db($conn);
            }

            //Context has changed, we update Plugin Host and Vulnerability Reports
            if ($ctx != $old_ctx)
            {
                $queries = array();

                //Plugin Host
                $ip_where  = "INET6_ATON('" . implode("'), INET6_ATON('", $ips) . "')";
                $queries[] = "UPDATE host_plugin_sid SET ctx = UNHEX(?) WHERE host_ip IN ($ip_where) AND ctx = UNHEX(?)";

                //Vulnerability Reports
                $ip_where  = "'" . implode("', '", $ips) . "'";
                $queries[] = "UPDATE vuln_nessus_latest_reports SET ctx = UNHEX(?) WHERE hostIP IN ($ip_where) AND ctx = UNHEX(?)";
                $queries[] = "UPDATE vuln_nessus_latest_results SET ctx = UNHEX(?) WHERE hostIP IN ($ip_where) AND ctx = UNHEX(?)";

                $params = array($ctx, $old_ctx);

                foreach ($queries as $query)
                {
                    $rs = $conn->Execute($query, $params);

                    if (!$rs)
                    {
                        Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
                    }
                }
            }
        }

        //Finish transaction
        if ($conn->CompleteTrans())
        {

            self::disable_host_update($conn, FALSE);

            $infolog = array($name, '[' . $ips_string . ']');

            if ($is_in_db == TRUE)
            {
                Log_action::log(26, $infolog);
            }
            else
            {
                Log_action::log(24, $infolog);
            }

            if ($report_changes == TRUE)
            {

                Util::disable_perm_triggers($conn, FALSE);

                try
                {
                    self::report_changes($conn, 'hosts');
                }
                catch (Exception $e)
                {
                    Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
                }
            }
        }
        else
        {
            $exp_msg = _('Error! Asset could not be saved');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return TRUE;
    }


    /**
    * This function save all host data which match with current filter database
    *
    * @param object   $conn            Database access object
    * @param array    $asset_data      Bulk form data
    * @param bool     $report_changes  [Optional] Report changes to other components
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean
    */
    public static function bulk_save_in_db($conn, $asset_data, $report_changes = TRUE)
    {
        Ossim_db::check_connection($conn);

        //Form field => Table field

        $host_fields = array(
            'asset_value' => 'asset',
            'descr'       => 'descr',
            'external'    => 'external_host',
            'icon'        => 'icon',
            'latitude'    => 'lat',
            'longitude'   => 'lon'
        );

        $query_data = array();

        foreach ($host_fields as $form_field => $table_field)
        {
            if (!empty($asset_data[$form_field]))
            {
                $query_data_key = $table_field . ' = ?';

                if ($form_field == 'longitude')
                {
                    $asset_data[$form_field] .= (!empty($asset_data['zoom'])) ? ';' . $asset_data['zoom'] : ';' . 4;
                }

                $query_data[$query_data_key] = $asset_data[$form_field];
            }
        }

        if (!empty($asset_data['latitude']) && !empty($asset_data['longitude']))
        {
            $geoloc = new Geolocation();

            list ($country_code, $country_name) = $geoloc->get_country_by_coordinates($asset_data['latitude'], $asset_data['longitude']);
            $geoloc->close();

            $country = $country_code . ';' . $country_name;

            $query_data['country = ?'] = $country;
        }

        Util::disable_perm_triggers($conn, TRUE);

        //Begin transaction
        $conn->StartTrans();

        $host_perms_where = self::get_perms_where('h.', TRUE);

        if (!empty($query_data))
        {
            // host table update

            $query = "UPDATE host h, user_component_filter uf SET " . implode(', ', array_keys($query_data)) .
                     " WHERE uf.session_id=? AND uf.asset_type='asset' AND h.id=uf.asset_id $host_perms_where";

            $query_data[] = session_id();

            $rs = $conn->Execute($query, array_values($query_data));

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
        }

        if (!empty($asset_data['devices']))
        {
            Asset_host_devices::bulk_save_devices_in_db($conn, $asset_data['devices']);
        }

        if (!empty($asset_data['sensors']))
        {
            Asset_host_sensors::bulk_save_sensors_in_db($conn, $asset_data['sensors']);
        }

        if (!empty($asset_data['os']))
        {
            Asset_host_properties::bulk_delete_property_from_db($conn, 3);
            Asset_host_properties::bulk_save_property_in_db($conn, 3, $asset_data['os'], 1);
        }

        if (!empty($asset_data['model']))
        {
            Asset_host_properties::bulk_delete_property_from_db($conn, 14);
            Asset_host_properties::bulk_save_property_in_db($conn, 14, $asset_data['model'], 1);
        }

        //Finish transaction
        if ($conn->CompleteTrans())
        {
            if ($report_changes == TRUE)
            {
                Util::disable_perm_triggers($conn, FALSE);

                try
                {
                    self::report_changes($conn, 'hosts');
                }
                catch (Exception $e)
                {
                    Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
                }
            }
        }
        else
        {
            $exp_msg = _('Error! Asset data could not be saved');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return TRUE;
    }


    /**
     * Function get_nets
     *
     * This function returns the networks related to the host
     *
     * @param object $conn Database access object
     *
     * @access public
     * @return array
     * @throws Av_exception If a connection error occurred
     */
    public function get_nets($conn)
    {
        Ossim_db::check_connection($conn);

        $nets        = array();
        $perms_where = '';

        $net_where = Session::get_net_where();

        if ($net_where != '')
        {
            $perms_where = "AND h.net_id in ($net_where)";
        }

        $query = "SELECT HEX(n.id) AS net_id, HEX(n.ctx) AS ctx, n.name, n.ips
            FROM host_net_reference h, net n
            WHERE h.net_id = n.id $perms_where
            AND h.host_id = UNHEX(?) AND n.ctx = UNHEX(?)";

        $params = array($this->get_id(), $this->get_ctx());
        $rs     = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $nets[$rs->fields['net_id']] = array(
                'ctx'  => $rs->fields['ctx'],
                'name' => $rs->fields['name'],
                'ips'  => $rs->fields['ips']
            );

            $rs->MoveNext();
        }

        return $nets;
    }




    /**
     * Function is_nagios_enabled
     *
     * This function returns true if host is monitored with Nagios
     *
     * @param object $conn Database access object
     *
     * @access public
     * @return bool
     */
    public function is_nagios_enabled($conn)
    {
        return Asset_host_scan::is_plugin_in_host($conn, $this->get_id(), 2007);
    }

    /**
     * Function get_availability
     *
     * This function returns the Nagios status of the host (Up, Down, ...)
     *
     * @param object $conn Database access object
     *
     * @return Array
     */
    public function get_availability($conn)
    {
        Ossim_db::check_connection($conn);

        $nagios_status = '';

        $sql = "SELECT ha.status FROM alienvault.host h
                LEFT JOIN alienvault.host_scan ha ON ha.host_id=h.id AND ha.plugin_id=2007 and ha.plugin_sid=0
                WHERE h.id = UNHEX(?)";

        $rs = $conn->Execute($sql, array($this->get_id()));

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            $nagios_status = $rs->fields['status'];
        }

        if ($nagios_status == '2')
        {
            // Up -> Green
            $availability_value = _('UP');
            $availability_level = 1;
        }
        elseif ($nagios_status == '1')
        {
            // Down -> Red
            $availability_value = _('DOWN');
            $availability_level = 3;
        }
        elseif ($nagios_status == '0')
        {
            // Pending -> Gray
            $availability_value = _('Pending');
            $availability_level = 0;
        }
        else
        {
            // Not Enabled -> Gray
            $availability_value = _('N/A');
            $availability_level = 0;
        }

        return array($availability_value, $availability_level);
    }

    /**
     * Function is_autodetected
     *
     * This function returns Automatic Asset Discovery configuration.
     * Host is scanned with NMAP scheduled task
     *
     *   - 0 No IPs are monitored (red)
     *   - 1 All IPs are monitored (green)
     *   - 2 Some IPs are monitored (yellow)
     *
     * @param object $conn Database access object
     *
     * @access public
     * @return int
     * @throws Av_exception If a connection error occurred
     */
    public function is_autodetected($conn)
    {
        Ossim_db::check_connection($conn);

        $networks = array();

        //Getting host sensors
        $sensor_obj = $this->get_sensors();
        $sensors    = $sensor_obj->get_sensors();
        $sensors    = array_keys($sensors);

        $q_sensors = implode("'), UNHEX('", $sensors);
        $q_sensors = "UNHEX('" . $q_sensors . "')";


        //Getting Inventory Tasks
        $target_param = " AND task_sensor IN($q_sensors) AND task_enable = 1";
        $task_list    = Inventory::get_list($conn, '', 5, $target_param);

        if (count($task_list) > 0)
        {
            //Getting networks from inventory tasks
            foreach ($task_list as $task_data)
            {
                list($_nets)  = Util::nmap_without_excludes($task_data['task_params']);

                foreach ($_nets as $net)
                {
                    if (Asset_net::valid_cidr($net))
                    {
                        $net_range = Asset_net::expand_cidr($net, 'SHORT', 'LONG');

                        $networks[$net] = $net_range[$net];
                    }
                }
            }

            if (is_array($networks) && !empty($networks))
            {
                $all_are_autodetected = TRUE; // All IPs are autodetected
                $any_is_autodetected  = FALSE; // True if some IP is autodetected

                $host_ips = $this->get_ips();
                $ips      = $host_ips->get_ips();

                foreach ($ips as $ip)
                {
                    $ip = $ip['ip'];

                    $is_autodetected = FALSE;
                    $ip_long         = Asset_host_ips::ip2ulong($ip);

                    foreach ($networks as $net)
                    {
                        if ($ip_long >= $net[0] && $ip_long <= $net[1])
                        {
                            $is_autodetected = TRUE;

                            break;
                        }
                    }

                    if ($is_autodetected == TRUE)
                    {
                        $any_is_autodetected = TRUE;
                    }
                    else
                    {
                        $all_are_autodetected = FALSE;
                    }
                }

                if ($any_is_autodetected == TRUE)
                {
                    return ($all_are_autodetected) ? 1 : 2; // GREEN / YELLOW
                }
            }
        }

        return 0; // RED
    }


    /**
    * Function get_num_group
    *
    * This function gets the num of groupd from the host
    *
    * @param object  $conn  DB connection object
    *
    * @access public
    *
    * @throws Av_exception If a connection error occurred
    *
    * @return integer  Number of group from the host
    */
    public function get_num_group($conn)
    {
        Ossim_db::check_connection($conn);

        $query = "SELECT count(hr.host_group_id) as num
                  FROM host_group_reference hr
                  WHERE hr.host_id= UNHEX(?)";

        $params = array($this->id);

        if (!$rs = $conn->Execute($query, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        return $rs->fields['num'];
    }


    /**
     * Function get_related_group
     *
     * This function gets the groups related to the host
     *
     * @param object  $conn  DB connection object
     *
     * @access public
     *
     * @throws Av_exception If a connection error occurred
     *
     * @return array  Groups for the host and total
     */
    public function get_related_groups($conn, $tables = ', host_group_reference hr', $filters = array('where' => 'hr.host_group_id = g.id'))
    {
        Ossim_db::check_connection($conn);

        $filters['where'] .= ' AND hr.host_id = UNHEX("'.$this->id.'")';

        return Asset_group::get_list($conn, $tables, $filters);
    }


    /**
    * Function add_to_groups
    *
    * This function adds the asset to selected groups (saved in db)
    *
    * @param object $conn Database access object
    *
    * @access public
    *
    * @throws Av_exception If a connection error occurred
    *
    * @return void
    */
    public function add_to_groups($conn)
    {
        Ossim_db::check_connection($conn);


        $query = "REPLACE INTO host_group_reference (
                    SELECT f.asset_id, UNHEX(?)
                        FROM user_component_filter f, host h
                        WHERE f.session_id=? AND f.asset_type='group')";

        $params = array(
            $this->id,
            session_id()
        );

        Util::memcacheFlush();

        if ($conn->Execute($query, $params) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        // Get affected groups to update history
        $group_ids = array();

        $query = "SELECT HEX(f.asset_id) AS group_id
                      FROM user_component_filter f
                      WHERE f.session_id=? AND f.asset_type='group'";

        $params = array(session_id());

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $group_ids[] = $rs->fields['group_id'];

            $rs->MoveNext();
        }

        foreach ($group_ids as $group_id)
        {
            $_group_obj = new Asset_group($group_id);

            $_group_obj->save_history($conn, 'add', array($this->id));
        }
    }


    /**
    * Function delete_from_groups
    *
    * This function deletes the asset from selected groups (saved in db)
    *
    * @param object $conn Database access object
    *
    * @access public
    *
    * @throws Av_exception If a connection error occurred
    *
    * @return void
    */
    public function delete_from_groups($conn)
    {
        Ossim_db::check_connection($conn);

        $query = "DELETE hgr FROM host_group_reference hgr, user_component_filter f, host h
            WHERE hgr.host_id = UNHEX(?) AND hgr.host_group_id=f.asset_id AND f.session_id=? AND f.asset_type='group'";

        $params = array(
            $this->id,
            session_id()
        );

        Util::memcacheFlush();

        $rs = $conn->Execute($query, $params);

        if ($rs === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        // Get affected groups to update history
        $group_ids = array();

        $query = "SELECT HEX(f.asset_id) AS group_id
                      FROM user_component_filter f
                      WHERE f.session_id=? AND f.asset_type='group'";

        $params = array(session_id());

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $group_ids[] = $rs->fields['group_id'];

            $rs->MoveNext();
        }

        foreach ($group_ids as $group_id)
        {
            $_group_obj = new Asset_group($group_id);

            $_group_obj->save_history($conn, 'remove', array($this->id));
        }
    }



    /*************************************************
     *************** Static functions ****************
     *************************************************/


    /**
     * Function can_delete
     *
     * This function checks if host could be deleted
     *
     * @param object $conn Database access object
     * @param string $id   Host ID
     *
     * @access protected
     * @return bool
     * @throws Av_exception If a connection error occurred
     */
    public static function can_delete($conn, $id)
    {
        Ossim_db::check_connection($conn);

        $query  = 'SELECT HEX(host_id) AS host_id FROM policy_host_reference WHERE host_id = UNHEX(?) LIMIT 1';
        $params = array($id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if ($rs->fields['host_id'] != '')
        {
            return FALSE;
        }

        return TRUE;
    }


    /**
     * Function set_host_net_reference
     *
     * This function inserts/deletes hosts into table host_net_reference
     *
     * @param object  $conn   Database access object
     * @param string  $id     Host ID
     * @param boolean $delete [Optional] Delete hosts
     * @param boolean $insert [Optional] Insert hosts
     *
     * @access protected
     * @return void
     * @throws Av_exception If a connection error occurred
     */
    protected static function set_host_net_reference($conn, $id, $delete = TRUE, $insert = TRUE)
    {
        Ossim_db::check_connection($conn);

        $params = array($id);

        if ($delete == TRUE)
        {
            $query = 'DELETE FROM alienvault.host_net_reference WHERE host_id = UNHEX(?)';

            $rs = $conn->Execute($query, $params);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
        }

        if ($insert == TRUE)
        {
            $query = 'REPLACE INTO alienvault.host_net_reference SELECT host.id,net_id
                FROM alienvault.host, alienvault.host_ip, alienvault.net_cidrs
                WHERE host.id = host_ip.host_id
                AND host_ip.ip >= net_cidrs.begin
                AND host_ip.ip <= net_cidrs.end
                AND host_id = UNHEX(?)';

            $rs = $conn->Execute($query, $params);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
        }
    }


    /**
     * Function get_perms_where
     *
     * This function returns a SQL query for filtering host
     *
     * @param string $alias    [Optional] MySQL alias
     * @param bool   $with_ctx [Optional] Use context in filter
     *
     * @access public
     * @return string
     */
    public static function get_perms_where($alias = '', $with_ctx = TRUE)
    {
        $query      = '';
        $host_query = '';
        $net_query  = '';

        $ctx_where  = Session::get_ctx_where();
        $host_where = Session::get_host_where();
        $net_where  = Session::get_net_where();


        if ($with_ctx == TRUE && $ctx_where != '')
        {
            $query .= ' AND ' . $alias . 'ctx IN (' . $ctx_where . ')';
        }

        if ($host_where != '')
        {
            $host_query = ' ' . $alias . 'id IN (' . $host_where . ')';
        }

        if ($net_where != '')
        {
            $net_query = ' ' . $alias . 'id IN (SELECT host_id FROM host_net_reference WHERE net_id in (' . $net_where . '))';
        }

        if ($host_query != '')
        {
            if ($net_query != '')
            {
                $query .= " AND ($host_query OR $net_query)";
            }
            else
            {
                $query .= " AND $host_query";
            }
        }
        elseif ($net_query != '')
        {
            $query .= " AND $net_query";
        }

        return $query;
    }


    /**
     * Function get_object
     *
     * This function returns an host object
     *
     * @param object $conn  Database access object
     * @param string $id    Host ID
     * @param bool   $cache [Optional] Use cached information
     *
     * @access public
     * @return object
     * @throws Av_exception If a connection error occurred
     */
    public static function get_object($conn, $id, $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        $host = NULL;

        $params = array($id);
        $query  = 'SELECT HEX(id) AS id FROM host WHERE id = UNHEX(?)';

        $rs = ($cache) ? $conn->CacheExecute($query, $params) : $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!$rs->EOF)
        {
            $host = new self($conn, $id);
            $host->load_from_db($conn, $cache);
        }

        return $host;
    }


    /**
     * Function is_in_db
     *
     * This function checks if host exists into database
     *
     * @param object $conn Database access object
     * @param string $id   Host ID
     *
     * @access public
     * @return bool
     * @throws Av_exception If a connection error occurred
     */
    public static function is_in_db($conn, $id)
    {
        Ossim_db::check_connection($conn);

        $res = FALSE;

        if (!valid_hex32($id))
        {
            return $res;
        }

        $query  = 'SELECT count(*) AS found FROM host WHERE id = UNHEX(?)';
        $params = array($id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (intval($rs->fields['found']) > 0)
        {
            $res = TRUE;
        }

        return $res;
    }


    /**
     * Function is_allowed
     *
     * This function checks if the host exists and it is allowed
     *
     * @param object $conn Database access object
     * @param string $id   Host ID
     *
     * @access public
     * @return bool
     * @throws Av_exception If a connection error occurred
     */
    public static function is_allowed($conn, $id)
    {
        Ossim_db::check_connection($conn);

        return Session::hostAllowed($conn, $id);
    }


    /**
     * Function get_asset_by_system
     *
     * This function returns the assets that represents a system.
     *
     * @param object $conn        Database access object
     * @param string $system_id   System ID
     *
     * @access public
     * @return Array of Asset IDs
     * @throws Av_exception If a connection error occurred
     */
    public static function get_asset_by_system($conn, $system_id)
    {
        Ossim_db::check_connection($conn);


        $host_ids = array();

        $query  = 'SELECT DISTINCT HEX(hi.host_id) AS system_host
                    FROM system s, host_sensor_reference hs, host_ip hi
                    WHERE hs.host_id=hi.host_id AND hs.sensor_id=s.sensor_id
                    AND (hi.ip=s.vpn_ip OR hi.ip=s.ha_ip OR hi.ip=s.admin_ip) AND s.id=UNHEX(?)';

        $params = array(Util::uuid_format_nc($system_id));

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $host_ids[$rs->fields['system_host']] = $rs->fields['system_host'];

            $rs->MoveNext();
        }

        return $host_ids;
    }


    /**
     * Function delete_from_db
     *
     * This function deletes the host from database
     *
     * @param object  $conn           Database access object
     * @param string  $id             Host ID
     * @param boolean $report_changes [Optional] Report changes to other components
     *
     * @acces public
     * @return bool
     * @throws Av_exception If an error occurred
     */
    public static function delete_from_db($conn, $id, $report_changes = TRUE)
    {
        Ossim_db::check_connection($conn);

        if (!self::can_delete($conn, $id))
        {
            $exp_msg = _('Error! Asset belongs to one or more policies');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }


        //It's necessary to delete host plugins and vulnerability reports
        $ctx = self::get_ctx_by_id($conn, $id);

        $host_ips = new Asset_host_ips($id);
        $host_ips->load_from_db($conn);
        $ips = array_keys($host_ips->get_ips());

        // Only admins will use sensors to clean plugins related
        if (Session::am_i_admin())
        {
            $host_sensors = new Asset_host_sensors($id);
            $host_sensors->load_from_db($conn);
            $sensors = array_keys($host_sensors->get_sensors());
        }

        //Begin transaction
        $conn->StartTrans();

        //IPs and MACs
        Asset_host_ips::delete_all_from_db($conn, $id);

        //Sensors
        Asset_host_sensors::delete_all_from_db($conn, $id);

        //Devices
        Asset_host_devices::delete_all_from_db($conn, $id);

        //Properties
        Asset_host_properties::delete_all_from_db($conn, $id);

        //Services
        Asset_host_services::delete_all_from_db($conn, $id);

        //Software
        Asset_host_software::delete_all_from_db($conn, $id);

        //Scan
        Asset_host_scan::delete_all_from_db($conn, $id);

        //Host network reference
        self::set_host_net_reference($conn, $id, TRUE, FALSE);

        if (!empty($ips) && !empty($ctx))
        {
            $queries = array();
            $params  = array($ctx);

            //Plugin Host
            $ip_where = "INET6_ATON('" . implode("'), INET6_ATON('", $ips) . "')";

            $queries[] = "DELETE FROM host_plugin_sid WHERE host_ip IN ($ip_where) AND ctx = UNHEX(?)";

            //Vulnerability Reports
            $ip_where = "'" . implode("', '", $ips) . "'";

            $queries[] = "DELETE FROM vuln_nessus_latest_reports WHERE hostIP IN ($ip_where) AND ctx = UNHEX(?)";
            $queries[] = "DELETE FROM vuln_nessus_latest_results WHERE hostIP IN ($ip_where) AND ctx = UNHEX(?)";

            foreach ($queries as $query)
            {
                $rs = $conn->Execute($query, $params);

                if (!$rs)
                {
                    Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
                }
            }
        }

        $queries = array();
        $params  = array($id);

        //Risk Maps
        $queries[] = 'DELETE FROM bp_member_status WHERE member_id = UNHEX(?)';
        $queries[] = "DELETE FROM bp_asset_member WHERE member = UNHEX(?) AND type='host'";

        //Vulnerabilities (Risk Maps)
        $queries[] = 'DELETE FROM host_vulnerability WHERE host_id = UNHEX(?)';

        //KDB
        $queries[] = 'DELETE FROM repository_relationships WHERE keyname = ?';

        //Qualification (Compromise and attack)
        $queries[] = 'DELETE FROM host_qualification WHERE host_id = UNHEX(?)';

        //Host Group
        $queries[] = 'DELETE FROM host_group_reference WHERE host_id = UNHEX(?)';

        //Host
        $queries[] = 'DELETE FROM host WHERE id = UNHEX(?)';

        //HIDS agents
        $queries[] = 'UPDATE hids_agents SET host_id = NULL WHERE host_id = UNHEX(?)';


        foreach ($queries as $query)
        {
            $rs = $conn->Execute($query, $params);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
        }
        
        
        // Clean related plugins
        if (Session::am_i_admin())
        {
            $plugins[$id] = array();
        
            foreach ($sensors as $_sensor_id)
            {
                try
                {
                    Plugin::set_plugins_by_assets($plugins, Util::uuid_format($_sensor_id));
                }
                catch (Exception $e)
                {
                    Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
                }
            }
        }
        
        
        //Finish transaction
        if ($conn->CompleteTrans())
        {
            //Reload Nagios
            try
            {
                $nagios_adm = new Nagios_adm();
                $nagios_adm->reload();
                $nagios_adm->close();
            }
            catch(Exception $e)
            {
                Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
            }

            //Log action
            $infolog = array($id);
            Log_action::log(25, $infolog);

            if ($report_changes == TRUE)
            {
                // Clean Suggestions
                $client = new Alienvault_client();
                $client->data('')->delete_asset($id);

                try
                {
                    self::report_changes($conn, 'hosts', TRUE);
                }
                catch (Exception $e)
                {
                    Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
                }
            }
        }
        else
        {
            $exp_msg = _('Error! Asset could not be deleted');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return TRUE;
    }


    /**
     * Function get_all_locations
     *
     * This function returns the location of all the members
     *
     * @param object $conn    Database access object
     *
     * @access public
     * @return array
     */
    public function get_all_locations($conn)
    {
	    Ossim_db::check_connection($conn);

        $locations = array();

        if ($this->location['lat'] != '' && $this->location['lon'] != '')
        {
            $locations[$this->id] = array(
                'name' => $this->name,
                'lat'  => $this->location['lat'],
                'lon'  => $this->location['lon'],
                'zoom' => $this->location['zoom']
            );
        }

        return $locations;
    }


    /************************************************************************************************/
    /************************************************************************************************/
    /************************************    STATIC FUNCTIONS    ************************************/
    /************************************************************************************************/
    /************************************************************************************************/


    /**
    * This function deletes all hosts which match with current filter database
    *
    * @param object   $conn            Database access object
    * @param boolean  $report_changes  [Optional] Report changes to other components
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean
    */
    public static function bulk_delete($conn, $report_changes = TRUE)
    {
        Ossim_db::check_connection($conn);

        Util::disable_perm_triggers($conn, TRUE);

        //Create tmp table

        $tmp_table = Util::create_tmp_table($conn, "host_id binary(16) NOT NULL, ctx binary(16) NOT NULL, PRIMARY KEY (host_id)");
        $session   = session_id();

        //Populate tmp table adding filtered hosts which are not included in policies
        $join    = 'LEFT JOIN policy_host_reference phr ON phr.host_id=h.id';
        $q_where = 'phr.host_id IS NULL';

        //Asset Selected
        $join    .= ', user_component_filter uc';
        $q_where .= " AND uc.asset_id = h.id AND uc.asset_type='asset' AND uc.session_id = '$session'";

        //Adding Permissions
        $perms_where = self::get_perms_where('h.', TRUE);

        if(!empty($perms_where))
        {
            $q_where .= $perms_where;
        }

        $query = ossim_query("INSERT INTO $tmp_table (host_id, ctx) SELECT id, ctx
            FROM host h $join WHERE $q_where");

        $conn->SetFetchMode(ADODB_FETCH_ASSOC);

        $rs = $conn->Execute($query);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        
        //Get related sensors to plugins clean below
        //Only admins will use sensors to clean plugins related
        if (Session::am_i_admin())
        {
            $sensors = array();
            $query   = "SELECT DISTINCT HEX(sensor_id) AS sensor_id FROM host_sensor_reference h, $tmp_table f WHERE f.host_id = h.host_id";
            $rs      = $conn->Execute($query);
            
            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
            else
            {
                while (!$rs->EOF)
                {
                    $sensors[] = $rs->fields['sensor_id'];
                    $rs->MoveNext();
                }
            }
        }
        
        //Check if there ire hosts belong some policies
        $hosts_belong_policies = FALSE;

        $tables = ', policy_host_reference phr';

        $filters = array(
            'where' => 'phr.host_id=host.id',
            'limit' => 1
        );


        $tables           .= ', user_component_filter hc';
        $filters['where'] .= " AND hc.asset_id = host.id AND hc.asset_type='asset'  AND hc.session_id = '$session'";

        $_host_list            = self::get_list($conn, $tables, $filters);

        $hosts_belong_policies = ($_host_list[1] > 0) ? TRUE : FALSE;

        // Delete sequence
        // Begin transaction
        $conn->StartTrans();


        //Delete statements (Queries to delete)
        $queries   = array();
        $queries[] = "DELETE h.* FROM host h,                     $tmp_table f WHERE f.host_id = h.id";
        $queries[] = "DELETE h.* FROM host_ip h,                  $tmp_table f WHERE f.host_id = h.host_id";
        $queries[] = "DELETE h.* FROM host_sensor_reference h,    $tmp_table f WHERE f.host_id = h.host_id";
        $queries[] = "DELETE h.* FROM host_types h,               $tmp_table f WHERE f.host_id = h.host_id";
        $queries[] = "DELETE h.* FROM host_group_reference h,     $tmp_table f WHERE f.host_id = h.host_id";
        $queries[] = "DELETE h.* FROM host_net_reference h,       $tmp_table f WHERE f.host_id = h.host_id";
        $queries[] = "DELETE h.* FROM host_properties h,          $tmp_table f WHERE f.host_id = h.host_id";
        $queries[] = "DELETE h.* FROM host_qualification h,       $tmp_table f WHERE f.host_id = h.host_id";
        $queries[] = "DELETE h.* FROM host_scan h,                $tmp_table f WHERE f.host_id = h.host_id";
        $queries[] = "DELETE h.* FROM host_services h,            $tmp_table f WHERE f.host_id = h.host_id";
        $queries[] = "DELETE h.* FROM host_software h,            $tmp_table f WHERE f.host_id = h.host_id";
        $queries[] = "DELETE h.* FROM host_vulnerability h,       $tmp_table f WHERE f.host_id = h.host_id";
        $queries[] = "DELETE h.* FROM bp_member_status h,         $tmp_table f WHERE f.host_id = h.member_id";
        $queries[] = "DELETE h.* FROM bp_asset_member h,          $tmp_table f WHERE f.host_id = h.member";
        $queries[] = "DELETE h.* FROM repository_relationships h, $tmp_table f WHERE f.host_id = UNHEX(h.keyname)";
        $queries[] = "DELETE h.* FROM host_plugin_sid h
            JOIN (SELECT ip, ctx FROM host_ip hi, $tmp_table t WHERE hi.host_id = t.host_id) AS f ON h.host_ip = f.ip AND f.ctx = h.ctx";
        $queries[] = "DELETE h.* FROM vuln_nessus_latest_reports h
            JOIN (SELECT INET6_NTOA(ip) AS ip, ctx FROM host_ip hi, $tmp_table t WHERE hi.host_id = t.host_id) AS f ON h.hostIP = f.ip AND f.ctx = h.ctx";
        $queries[] = "DELETE h.* FROM vuln_nessus_latest_results h
            JOIN (SELECT INET6_NTOA(ip) AS ip, ctx FROM host_ip hi, $tmp_table t WHERE hi.host_id = t.host_id) AS f ON h.hostIP = f.ip AND f.ctx = h.ctx";

        //Update statements
        $queries[] = "UPDATE hids_agents ha INNER JOIN $tmp_table f ON f.host_id=ha.host_id SET ha.host_id = NULL";


        foreach ($queries as $query)
        {
            $rs = $conn->Execute($query);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
        }
        
        
        // Clean plugins related to deleted hosts
        if (Session::am_i_admin())
        {
            $plugins = array();
            $query   = "SELECT HEX(host_id) AS host_id FROM $tmp_table";
            $rs      = $conn->Execute($query);
        
            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
        
            while (!$rs->EOF)
            {
                $plugins[$rs->fields['host_id']] = array();
                $rs->MoveNext();
            }
        
            foreach ($sensors as $_sensor_id)
            {
                try
                {
                    Plugin::set_plugins_by_assets($plugins, Util::uuid_format($_sensor_id));
                }
                catch(Exception $e)
                {
                    Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
                }
            }
        }
        

        //Finish transaction
        if ($conn->CompleteTrans())
        {
            //Reload Nagios
            try
            {
                $nagios_adm = new Nagios_adm();
                $nagios_adm->reload();
                $nagios_adm->close();
            }
            catch(Exception $e)
            {
                Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
            }

            //Log action
            $infolog = array(_('Policy - Asset deleted massively'));
            Log_action::log(92, $infolog);

            Filter_list::clean_selection($conn, 'asset');

            if ($report_changes == TRUE)
            {
                Util::disable_perm_triggers($conn, FALSE);

                // Clean Suggestions
                $client = new Alienvault_client();
                $client->data('')->clean_orphans('host');

                try
                {
                    self::report_changes($conn, 'hosts', TRUE);
                }
                catch(Exception $e)
                {
                    Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
                }
            }
            

            // Hosts belong a policy
            if ($hosts_belong_policies == TRUE)
            {
                $exp_msg = _('Some assets belongs to one or more policies');

                Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
            }
        }
        else
        {
            $exp_msg = _('Error! Assets could not be deleted');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return TRUE;
    }


    /**
    * This function deletes host icon from database
    *
    * @param object  $conn   Database access object
    * @param string  $id     Host ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean
    */
    public static function delete_icon($conn, $id)
    {
        Ossim_db::check_connection($conn);

        $query  = 'UPDATE host SET icon = NULL WHERE id = UNHEX(?)';
        $params = array($id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        return TRUE;
    }


    /**
    * This function returns the name from host
    *
    * @param object  $conn   Database access object
    * @param string  $id     Host ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return string
    */
    public static function get_name_by_id($conn, $id)
    {
        Ossim_db::check_connection($conn);

        $name = '';

        $query = 'SELECT hostname FROM host WHERE id = UNHEX(?)';
        $params = array($id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if ($rs->fields['hostname'] != '')
        {
            $name = $rs->fields['hostname'];
        }

        return $name;
    }


    /**
    * This function returns the Host ID from hostname
    *
    * @param object  $conn   Database access object
    * @param string  $name   Host name
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_id_by_name($conn, $name)
    {
        Ossim_db::check_connection($conn);

        $name = self::create_valid_name($name);

        $host_ids = array();

        //Only my contexts
        $ctx_where = Session::get_ctx_where();

        $query = 'SELECT HEX(id) AS id, HEX(ctx) AS ctx FROM host
            WHERE (hostname = ? OR fqdns LIKE ?)';

        if (!empty($ctx_where))
        {
            $query .= " AND ctx IN ($ctx_where)";
        }

        $params = array($name, $name);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $host_ids[$rs->fields['id']] = $rs->fields['ctx'];

            $rs->MoveNext();
        }

        return $host_ids;
    }


    /**
    * This function returns the context from allowed host
    *
    * @param object  $conn   Database access object
    * @param string  $id     Host ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return string
    */
    public static function get_ctx_by_id($conn, $id)
    {
        Ossim_db::check_connection($conn);

        $ctx = '';

        $query  = 'SELECT HEX(ctx) AS ctx FROM host WHERE id = UNHEX(?)';
        $params = array($id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }


        if (valid_hex32($rs->fields['ctx']))
        {
            $ctx = $rs->fields['ctx'];
        }

        return $ctx;
    }


    /**
    * This function returns the common context for all hosts which match with current filter database
    *
    * @param object  $conn   Database access object
    *
    * @throws Exception      If a connection error occurred
    *
    * @return mixed          Valid values are NULL or the common context
    */
    public static function get_common_ctx($conn)
    {
        Ossim_db::check_connection($conn);

        $host_perms_where = self::get_perms_where('h.', TRUE);

        $params = array(session_id());

        $query  = "SELECT DISTINCT SQL_CALC_FOUND_ROWS HEX(h.ctx) AS ctx FROM user_component_filter uf, host h
                        WHERE uf.session_id = ? AND uf.asset_type = 'asset' AND h.id=uf.asset_id $host_perms_where LIMIT 2";

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $total  = Ossim_db::get_found_rows($conn, $query);

        $result = ($total == 1) ? $rs->fields['ctx'] : NULL;

        return $result;
    }


    /**
    * This function returns the Host IDs from IP list and context
    *
    * @param object  $conn   Database access object
    * @param string  $ips    Comma-separated IPs
    * @param string  $ctx    [Optional] Host context
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_id_by_ips($conn, $ips, $ctx = '')
    {
        Ossim_db::check_connection($conn);

        $host_ids = array();

        $ip_list = explode(',', $ips);

        // Permissions
        $perms_where = self::get_perms_where('host.', TRUE);

        foreach($ip_list as $ip)
        {
            $query = "SELECT HEX(id) AS id, HEX(ctx) AS ctx FROM host, host_ip
                WHERE host.id = host_ip.host_id
                AND host_ip.ip = INET6_ATON(?) $perms_where";

            $params = array(trim($ip));

            if ($ctx != '')
            {
                $query   .= ' AND host.ctx = UNHEX(?)';
                $params[] = $ctx;
            }


            $rs = $conn->Execute($query, $params);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }

            if (valid_hex32($rs->fields['id']))
            {
                $host_ids[$rs->fields['id']][$rs->fields['ctx']][$ip] = $ip;
            }
        }

        return $host_ids;
    }


    /**
    * This function returns the hostname/s from IP
    *
    * @param object  $conn   Database access object
    * @param string  $ip     Host IP
    * @param string  $ctx    [Optional] Host context
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_name_by_ip($conn, $ip, $ctx = '')
    {
        Ossim_db::check_connection($conn);

        $names = array();

        $query  = 'SELECT h.hostname, HEX(h.id) AS id FROM host_ip hi, host h
            WHERE h.id=hi.host_id
            AND hi.ip = UNHEX(?)';

        $params = array(bin2hex(inet_pton($ip)));

        if (!empty($ctx))
        {
            $query    .= ' AND h.ctx = UNHEX(?)';
            $params[]  = $ctx;
        }
        else
        {
            //Only hosts from my contexts
            $ctx_where = Session::get_ctx_where();

            if (!empty($ctx_where))
            {
                $query .= ' AND h.ctx IN ('.Session::get_ctx_where().')';
            }
        }

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $names[$rs->fields['id']] = $rs->fields['hostname'];

            $rs->MoveNext();
        }

        return $names;
    }


    /**
    * This function returns the IPs from hostname
    *
    * @param object  $conn   Database access object
    * @param string  $name   Hostname
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_ips_by_name($conn, $name)
    {
        Ossim_db::check_connection($conn);

        $name = self::create_valid_name($name);

        $host_ips = array();

        $query  = 'SELECT hi.ip, HEX(h.id) AS id
            FROM host h, host_ip hi
            WHERE (h.hostname = ? OR h.fqdns LIKE ?)
            AND h.id = hi.host_id';


        //Only hosts from my contexts
        $ctx_where = Session::get_ctx_where();

        if (!empty($ctx_where))
        {
            $query .= ' AND h.ctx IN ('.Session::get_ctx_where().')';
        }

        $params = array($name, $name);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $host_ips[inet_ntop($rs->fields['ip'])] = $rs->fields['id'];
            $rs->MoveNext();
        }

        return $host_ips;
    }


    /**
    * This function returns the closest network context for unregistered IP
    *
    * @param object  $conn   Database access object
    * @param string  $ip     Host IP
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_closest_net_ctxs($conn, $ip)
    {
        Ossim_db::check_connection($conn);

        $nets = array();

        $perms_where = Asset_net::get_perms_where('net.', TRUE);

        $query = "SELECT hex(net.ctx) AS ctx, net.ips FROM host_ip, host_net_reference, net
            WHERE host_ip.host_id = host_net_reference.host_id
            AND net.id = host_net_reference.net_id $perms_where
            AND host_ip.ip = inet6_aton(?)";

        $params = array($ip);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }


        while (!$rs->EOF)
        {
            $nets[$rs->fields['ips']] = $rs->fields['ctx'];

            $rs->MoveNext();
        }

        return $nets;
    }


    /**
    * This function returns extended information about host location
    *
    * @param object  $conn     Database access object
    * @param object  $geoloc   Geolocation object
    * @param string  $ip       Host IP
    * @param string  $id       [Optional] Host ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_extended_location($conn, $geoloc, $ip, $id = '')
    {
        Ossim_db::check_connection($conn);

        if(!is_object($geoloc))
        {
            $exp_msg = _('Error! Unable to connect to geolocation Database');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $geo_info = array(
            'html_icon'    => NULL,
            'base64_icon'  => NULL,
            'country'      => array('code' => NULL, 'name' => NULL)
        );


        $country = $geoloc->get_country_by_host($conn, $ip, $id);

        $geo_info['country']['code'] = strtolower($country[0]);
        $geo_info['country']['name'] = $country[1];

        if ($geo_info['country']['code'] != '')
        {
            $icon_path = AV_MAIN_ROOT_PATH.'/pixmaps/flags/'.$geo_info['country']['code'].'.png';

            $geo_info['base64_icon'] = base64_encode(file_get_contents($icon_path));
            $geo_info['html_icon']   = "<img src='data:image/png;base64,".$geo_info['base64_icon']."' data-title = '".$geo_info['country']['name']."'/>";
        }

        return $geo_info;
    }


    /**
    * This function returns the host name extended (name, icon and external attribute)
    *
    * @param object  $conn    Database access object
    * @param object  $geoloc  Geolocation object
    * @param string  $ip      Host IP
    * @param string  $ctx     Host context
    * @param string  $id      Host ID
    * @param string  $net_id  [Optional] Net ID in event/alarm
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array  Keys: name, base64_icon, html_icon and is_internal
    */
    public static function get_extended_name($conn, $geoloc, $ip, $ctx, $id, $net_id = '')
    {
        Ossim_db::check_connection($conn);

        if(!is_object($geoloc))
        {
            $exp_msg = _('Error! Unable to connect to Geolocation Database');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $data = array(
            'name'        => $ip,
            'base64_icon' => NULL,
            'html_icon'   => NULL,
            'is_internal' => FALSE
        );


        $host = self::get_object($conn, $id);

        //Host ID is into database, we resolve name and external attribute
        if (is_object($host) && !empty($host))
        {
            $data['name']        = $host->get_name();
            $data['is_internal'] = !$host->get_external();

            if ($host->get_icon() != '')
            {
                $data['base64_icon'] = $host->get_icon();
                $data['html_icon']   = "<a href='javascript:;' class='scriptinfo' style='text-decoration:none' data-title='$ip-$ctx'>".$host->get_html_icon()."</a>";
            }
        }

        //Host doesn't have icon, we search its net icon
        if ($net_id != '' && $data['base64_icon'] == NULL)
        {
            $net = Asset_net::get_object($conn, $net_id);

            if (is_object($net) && !empty($net))
            {
                //Set external attribute from net, if we don't have host information
                if (!is_object($host)|| empty($host))
                {
                    $data['is_internal'] = !$net->get_external();
                }

                $data['base64_icon'] = $net->get_icon();
                $data['html_icon']   = "<a href='javascript:;' class='scriptinfo' style='text-decoration:none' data-title='$ip-$ctx'>".$net->get_html_icon().'</a>';
            }
        }


        //Host is external and it doesn't have icon, we try to geolocate the host
        if ($data['base64_icon'] == NULL && $data['is_internal'] == FALSE)
        {
            $geo_info            = self::get_extended_location($conn, $geoloc, $ip);
            $data['base64_icon'] = $geo_info['base64_icon'];
            $data['html_icon']   = $geo_info['html_icon'];
        }

        return $data;
    }


    /**
    * This function returns true if host IP is editable for logged user
    *
    * @param object $conn   Database access object
    * @param string $id     Host ID (Hexadecimal ID)
    *
    * @return boolean
    */
    public static function can_i_modify_ips($conn, $id)
    {
        if (!Session::get_host_where() && !Session::get_net_where())
        {
            return TRUE;
        }

        if (Session::is_in_host_where($conn, $id))
        {
            return FALSE;
        }

        return self::is_in_allowed_nets($conn, $id);
    }


    /**
    * This function returns true if $ip is contained in some nets
    *
    * @param string  $ip    Host IP
    * @param string  $nets  Comma-separated CIDRs
    *
    * @return boolean
    */
    public static function is_ip_in_nets($ip, $nets)
    {
        $net_list = explode(',', $nets);

        foreach($net_list as $n)
        {
            $cached = $_SESSION['_ip_in_net'][$n][$ip];

            if ($cached > 0 )
            {
                return TRUE;
            }

            list($net, $mask) = preg_split('/\//', $n);

            $val1 = Asset_host_ips::ip2ulong($ip);
            $val2 = Asset_host_ips::ip2ulong($net);

            if (($val1 >> (32 - $mask)) == ($val2 >> (32 - $mask)))
            {
                $_SESSION['_ip_in_net'][$n][$ip] = 1;

                return TRUE;
            }
        }

        return FALSE;
    }


    /**
    * This function searches $ip in table net_cidrs
    *
    * @param object   $conn      Database access object
    * @param string   $ips       Host IP
    * @param string   $ctx       [Optional] Host Context
    * @param boolean  $all_nets  [Optional] Search only in my networks
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean
    */
    public static function is_ip_in_cache_cidr($conn, $ip, $ctx = '', $in_my_nets = FALSE)
    {
        Ossim_db::check_connection($conn);

        if ($ctx != '')
        {
            $query = "SELECT net_id
                FROM net_cidrs, net
                WHERE net.id = net_cidrs.net_id
                AND net.ctx in (UNHEX('".str_replace(',',"'),UNHEX('",$ctx)."'))
                AND UNHEX(?) >= net_cidrs.begin AND UNHEX(?) <= net_cidrs.end";
        }
        else
        {
            $query = 'SELECT net_id FROM net_cidrs WHERE UNHEX(?) >= begin AND UNHEX(?) <= end';
        }


        $net_where = Session::get_net_where();

        if ($in_my_nets == TRUE && $net_where != '')
        {
            $query .= " AND net_cidrs.net_id IN ($net_where)";
        }

        $params = array(bin2hex(inet_pton($ip)), bin2hex(inet_pton($ip)));
        $rs     = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!$rs->EOF)
        {
            return TRUE;
        }

        return FALSE;
    }


    /**
    * This function returns true if host belongs to allowed net
    *
    * @param object  $conn  Database access object
    * @param string  $id    Host ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean
    */
    public static function is_in_allowed_nets($conn, $id)
    {
        Ossim_db::check_connection($conn);

        $net_where = Session::get_net_where();

        if ($net_where == '')
        {
           return TRUE;
        }

        $params = array($id);

        $query = "SELECT HEX(h.host_id) FROM host_net_reference h
            WHERE h.net_id IN ($net_where) AND h.host_id = UNHEX(?)";

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!$rs->EOF)
        {
            return TRUE;
        }

        return FALSE;
    }


    /**
    * This function returns a valid hostname for autodetected IP
    *
    * @param string  $ip  Host IP
    *
    * @return string
    */
    public static function get_autodetected_name($ip)
    {
        $ip = (empty($ip)) ? 'unknown' : $ip;

        return 'Host-'.preg_replace('/\./', '-', $ip);
    }


    /**
    * This function creates a valid hostname
    *
    * @param string  $name  Hostname
    *
    * @return string
    */
    public static function create_valid_name($name)
    {
        if(strlen($name) > 63)
        {
            $name = substr($name , 0, 63);
        }

        //Clear invalid characters
        $name = preg_replace('/[^a-z0-9\-]/i', '-',$name);
        $name = preg_replace('/^[^a-z0-9]+/i', '',$name);
        $name = preg_replace('/[^a-z0-9]+$/i','',$name);
        $name = (empty($name)) ? 'Host-'.substr(md5(rand()),20) : $name;

        return $name;
    }


    /**
    * This function returns a filtered host list (Only basic data)
    *
    * @param object  $conn     Database access object
    * @param array   $filters  [Optional] SQL statements (WHERE, LIMIT, ORDER BY ...)
    * @param boolean $cache    [Optional] Use cached information
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_basic_list($conn, $filters = array(), $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        $hosts_by_ip = array();
        $hosts_by_id = array();

        //Build SQL

        $q_join      = '';
        $perms_where = self::get_perms_where('h.', TRUE);
        $q_where     = $perms_where;
	$q_select = '';

        if (!empty($filters['where']))
        {
            $q_where  .= 'AND '.$filters['where'];
        }

        if (!empty($filters['join']))
        {
            $q_join    = $filters['join'];
        }

        if (!empty($filters['order_by']))
        {
            $q_where  .= ' ORDER BY '.$filters['order_by'];
        }
        if (!empty($filters['limit']))
        {
            $q_where  .= ' LIMIT '.$filters['limit'];
            if (!$cache) {
		 $q_select = " SQL_CALC_FOUND_ROWS ";
            }
        }

        $conn->SetFetchMode(ADODB_FETCH_ASSOC);
        $query = "SELECT $q_select HEX(h.id) AS id, inet6_ntoa(hi.ip) AS ip,
                HEX(h.ctx) AS ctx, h.hostname, h.external_host
		FROM host h 
		JOIN host_ip hi ON h.id = hi.host_id
		$q_join
                    WHERE 1
                    $q_where";
        $rs = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        while (!$rs->EOF)
        {
            $hosts_by_ip[$rs->fields['ip']][$rs->fields['ctx']] = array(
                        'id'          => $rs->fields['id'],
                        'name'        => $rs->fields['hostname'],
                        'is_internal' => !$rs->fields['external_host']
            );
            if (!array_key_exists($rs->fields['id'], $hosts_by_id))
            {
                $hosts_by_id[$rs->fields['id']] = array(
                            'id'          => $rs->fields['id'],
                            'name'        => $rs->fields['hostname'],
                            'ips'         => $rs->fields['ip'],
                            'ctx'         => $rs->fields['ctx'],
                            'is_internal' => !$rs->fields['external_host']
                        );
            }
            else
            {
                $hosts_by_id[$rs->fields['id']]['ips'] .= ','.$rs->fields['ip'];
            }
            $rs->MoveNext();
        }
        $rs->Free();
        $counter = !$cache && !empty($filters['limit']) ? Ossim_db::get_found_rows($conn, $query) : count($hosts_by_id);
        return array($hosts_by_ip, $hosts_by_id, $counter);
    }


    /**
    * This function returns closest net to the host
    *
    * @param object  $conn   Database access object
    * @param string  $ip     Host IP
    * @param string  $ctx   [Optional] Host context
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_closest_net($conn, $ip, $ctx = '')
    {
        Ossim_db::check_connection($conn);

        $nets = array();

        // Permissions
        $perms_where = Asset_net::get_perms_where('net.', TRUE);

        if ($ip == '')
        {
            return $nets;
        }

        $query = "SELECT HEX(net_id) AS id, HEX(net.ctx) AS ctx, net.name, net_cidrs.cidr, net.ips, net.icon,
            CONV(HEX(begin),16,10) AS begind, CONV(HEX(end), 16, 10) AS endd
            FROM net_cidrs, net
            WHERE net.id = net_cidrs.net_id $perms_where
            AND UNHEX(?) >= net_cidrs.begin
            AND UNHEX(?) <= net_cidrs.end";


        if ($ctx != '')
        {
           $ctxs = (preg_match('/unhex/i',$ctx) == TRUE) ? $ctx : "UNHEX('".str_replace(',', "'), UNHEX('",$ctx)."')";

           $query .= " AND net.ctx in ($ctxs)";
        }

        $query .= ' ORDER BY endd-begind ASC LIMIT 1';


        $params = array(bin2hex(inet_pton($ip)), bin2hex(inet_pton($ip)));

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $nets[$rs->fields['id']] = array(
                'id'   => $rs->fields['id'],
                'ctx'  => $rs->fields['ctx'],
                'name' => $rs->fields['name'],
                'icon' => $rs->fields['icon'],
                'cidr' => $rs->fields['cidr'],
                'ips'  => $rs->fields['ips']
            );

            $rs->MoveNext();
        }

        return $nets;
    }


    /**
    * This function returns a filtered host list (Used in trees and autocomplete widget)
    *
    * @param object   $conn     Database access object
    * @param string   $tables   [Optional] Database tables separated by comma (Join with main table)
    * @param array    $filters  [Optional] SQL statements (WHERE, LIMIT, ORDER BY ...)
    * @param boolean  $cache    [Optional] Use cached information
    * @param boolean  $by_ip    [Optional] If is true, it returns list by IP
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_list_tree($conn, $tables = '', $filters = array(), $cache = FALSE, $by_ip = TRUE)
    {
        Ossim_db::check_connection($conn);

        $hosts  = array();

        //Build SQL

        $perms_where = self::get_perms_where('host.', TRUE);

        $q_where  = $perms_where;

        if (!empty($filters['where']))
        {
            $q_where  .= 'AND '.$filters['where'];
        }

        if (!empty($filters['order_by']))
        {
            $q_order  .= ' ORDER BY '.$filters['order_by'];
        }
        else
        {
            //Order by default
            $q_order  .= ' ORDER BY hi.ip';
        }

        if (!empty($filters['limit']))
        {
            $offset = isset($filters['offset']) && $filters['offset'] ? $filters['offset'] : 0;
            $q_limit  = "LIMIT $offset,{$filters['limit']}";
        }

        $conn->SetFetchMode(ADODB_FETCH_ASSOC);
        $query = ossim_query("SELECT host.hostname, HEX(host.id) AS h_id, HEX(host.ctx) AS h_ctx, INET6_NTOA(hi.ip) AS ip
                FROM host $tables, host_ip hi
                WHERE hi.host_id = host.id
                AND hi.ip > 0x0
                $q_where
		GROUP BY host.hostname
		$q_order
		$q_limit
                ");
        $rs = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            if($by_ip == TRUE)
            {
                $hosts[$rs->fields['h_id']] = array(
                    $rs->fields['h_id'],
                    $rs->fields['h_ctx'],
                    $rs->fields['ip'],
                    $rs->fields['hostname']
                );
            }
            else
            {
                if (!array_key_exists($rs->fields['h_id'], $hosts))
                {
                    $hosts[$rs->fields['h_id']] = array(
                        $rs->fields['h_id'],
                        $rs->fields['h_ctx'],
                        $rs->fields['ip'],
                        $rs->fields['hostname']
                    );
                }
                else
                {
                    //Host has several IPs
                    $hosts[$rs->fields['h_id']][2] .= ', '.$rs->fields['ip'];
                }
            }
            $rs->MoveNext();
        }
        return $hosts;
    }


    /**
    * This function returns a filtered host list
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
    public static function get_list($conn, $tables = '', $filters = array(), $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        $hosts = array();

        $total = 0;

        //Build SQL

        $perms_where = self::get_perms_where('host.', TRUE);

        $q_select = 'host.*';
        $q_where  = 'WHERE 1=1 '.$perms_where;


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
            $q_select  = 'SQL_CALC_FOUND_ROWS host.*';
            $q_where  .= ' LIMIT '.$filters['limit'];
        }

        $conn->SetFetchMode(ADODB_FETCH_ASSOC);

        // Has LIMIT
        if (!empty($filters['limit']))
        {
            $query = "SELECT DISTINCT $q_select, HEX(host.id) AS h_id, HEX(host.ctx) AS h_ctx
                FROM host $tables $q_where";

            $rs = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }

            $total = Ossim_db::get_found_rows($conn, $query, $cache);

            while (!$rs->EOF)
            {
                //Location

                $latitude = ($rs->fields['lat'] == '') ? 0 : $rs->fields['lat'];

                $lon = explode(';', $rs->fields['lon']);

                if (is_array($lon) && !empty($lon[0]))
                {
                    $longitude = $lon[0];
                    $zoom      = (empty($lon[1])) ? 4 : $lon[1];
                }
                else
                {
                    $longitude = 0;
                    $zoom      = 4;
                }

                $location = array(
                    'lat'  => $latitude,
                    'lon'  => $longitude,
                    'zoom' => $zoom
                );

                $id = $rs->fields['h_id'];

                //Ips
                $ips = Asset_host_ips::get_ips_to_string($conn, $id);

                $hosts[$id] = array(
                    'id'          => $id,
                    'ctx'         => $rs->fields['h_ctx'],
                    'name'        => $rs->fields['hostname'],
                    'ips'         => $ips,
                    'descr'       => $rs->fields['descr'],
                    'icon'        => $rs->fields['icon'],
                    'fqdns'       => $rs->fields['fqdns'],
                    'external'    => $rs->fields['external_host'],
                    'location'    => $location,
                    'asset_value' => $rs->fields['asset']
                );

                $rs->MoveNext();
            }
        }
        else
        {
            $counter_name = ($cache) ? 'total_'.md5($query) : 'total';

            // First count to do block requests
            $query = ossim_query("SELECT count(DISTINCT host.id) AS $counter_name FROM host $tables $q_where");

            $rf    = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);

            if (!$rf)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }

            $total = $rf->fields[$counter_name];

            $block = 2000;
            $rf->Free();

            for ($i = 0; $i <= $total; $i += $block)
            {
                $query = ossim_query("SELECT DISTINCT $q_select, HEX(host.id) AS h_id, HEX(host.ctx) AS h_ctx
                    FROM host $tables $q_where LIMIT $i, $block");

                $rs = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);

                if (!$rs)
                {
                    Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
                }

                while (!$rs->EOF)
                {
                    //Location
                    $latitude = ($rs->fields['lat'] == '') ? 0 : $rs->fields['lat'];

                    $lon = explode(';', $rs->fields['lon']);

                    if (is_array($lon) && !empty($lon[0]))
                    {
                        $longitude = $lon[0];
                        $zoom      = (empty($lon[1])) ? 4 : $lon[1];
                    }
                    else
                    {
                        $longitude = 0;
                        $zoom      = 4;
                    }

                    $location = array(
                        'lat'  => $latitude,
                        'lon'  => $longitude,
                        'zoom' => $zoom
                    );

                    $id = $rs->fields['h_id'];

                    //Ips
                    $ips = Asset_host_ips::get_ips_to_string($conn, $id);

                    $hosts[$id] = array(
                        'id'          => $id,
                        'ctx'         => $rs->fields['h_ctx'],
                        'name'        => $rs->fields['hostname'],
                        'ips'         => $ips,
                        'descr'       => $rs->fields['descr'],
                        'icon'        => $rs->fields['icon'],
                        'fqdns'       => $rs->fields['fqdns'],
                        'external'    => $rs->fields['external_host'],
                        'location'    => $location,
                        'asset_value' => $rs->fields['asset']
                    );

                    $rs->MoveNext();
                }

                $rs->Free();
            }
        }

        return array($hosts, $total);
    }


    /**
    * This function returns a filtered host list with all needed fields for asset list
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
    public static function get_full_list($conn, $tables = '', $filters = array(), $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        $hosts = array();

        $total = 0;

        //Build SQL

        $perms_where = self::get_perms_where('host.', TRUE);

        $q_select  = 'host.*';
        $q_where   = 'WHERE 1=1 '.$perms_where;
        $leftjoins = 'LEFT JOIN host_properties hp ON hp.host_id=host.id AND hp.property_ref=3 LEFT JOIN vuln_job_assets vj ON vj.asset_id=host.id LEFT JOIN hids_agents ha ON ha.host_id=host.id';

        if (!empty($filters['where']))
        {
            $q_where  .= ' AND '.$filters['where'];
        }

        if (!empty($filters['order_by']))
        {
            $q_where  .= ' ORDER BY '.$filters['order_by'];

            if (preg_match("/ip/",$filters['order_by']))
            {
                $leftjoins .= ' LEFT JOIN host_ip hi ON hi.host_id=host.id';
            }
        }

        if (!empty($filters['limit']))
        {
            $q_select  = 'SQL_CALC_FOUND_ROWS host.*';
            $q_where  .= ' LIMIT '.$filters['limit'];
        }

        $conn->SetFetchMode(ADODB_FETCH_ASSOC);


        // Has LIMIT
        if (!empty($filters['limit']))
        {
            $query = "SELECT DISTINCT $q_select, HEX(host.id) AS h_id, HEX(host.ctx) AS h_ctx, hp.value as os,
                if(vj.job_id is not null,'Yes','No') AS vuln,
                if(ha.agent_status IN(3,4), 'Connected', if(ha.agent_status > 0,'Disconnected', 'Not Deployed')) AS hids
                FROM host $leftjoins $tables $q_where";

            $rs = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }

            $total = Ossim_db::get_found_rows($conn, $query, $cache);

            while (!$rs->EOF)
            {
                //Location

                $latitude = ($rs->fields['lat'] == '') ? 0 : $rs->fields['lat'];

                $lon = explode(';', $rs->fields['lon']);

                if (is_array($lon) && !empty($lon[0]))
                {
                    $longitude = $lon[0];
                    $zoom      = (empty($lon[1])) ? 4 : $lon[1];
                }
                else
                {
                    $longitude = 0;
                    $zoom      = 4;
                }

                $location = array(
                    'lat'  => $latitude,
                    'lon'  => $longitude,
                    'zoom' => $zoom
                );

                $id = $rs->fields['h_id'];

                //Ips
                $ips = Asset_host_ips::get_ips_to_string($conn, $id);

                $hosts[$id] = array(
                    'id'           => $id,
                    'ctx'          => $rs->fields['h_ctx'],
                    'name'         => $rs->fields['hostname'],
                    'ips'          => $ips,
                    'descr'        => $rs->fields['descr'],
                    'icon'         => $rs->fields['icon'],
                    'fqdns'        => $rs->fields['fqdns'],
                    'external'     => $rs->fields['external_host'],
                    'location'     => $location,
                    'asset_value'  => $rs->fields['asset'],
                    'os'           => $rs->fields['os'],
                    'vuln_scan'    => $rs->fields['vuln'],
                    'hids'         => $rs->fields['hids']
                );

                $rs->MoveNext();
            }
        }
        else
        {
            $counter_name = ($cache) ? 'total_'.md5($query) : 'total';

            // First count to do block requests
            $query = ossim_query("SELECT count(DISTINCT host.id) AS $counter_name FROM host $leftjoins $tables $q_where");

            $rf    = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);

            if (!$rf)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }

            $total = $rf->fields[$counter_name];

            $block = 2000;
            $rf->Free();

            for ($i = 0; $i <= $total; $i += $block)
            {
                $query = ossim_query("SELECT DISTINCT $q_select, HEX(host.id) AS h_id, HEX(host.ctx) AS h_ctx, hp.value as os,
                    if(vj.job_id is not null,'Yes','No') as vuln,
                    if(ha.agent_status IN(3,4), 'Connected', if(ha.agent_status > 0, 'Disconnected', 'Not Deployed')) AS hids
                    FROM host $leftjoins $tables $q_where LIMIT $i, $block");

                $rs = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);

                if (!$rs)
                {
                    Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
                }

                while (!$rs->EOF)
                {
                    //Location
                    $latitude = ($rs->fields['lat'] == '') ? 0 : $rs->fields['lat'];

                    $lon = explode(';', $rs->fields['lon']);

                    if (is_array($lon) && !empty($lon[0]))
                    {
                        $longitude = $lon[0];
                        $zoom      = (empty($lon[1])) ? 4 : $lon[1];
                    }
                    else
                    {
                        $longitude = 0;
                        $zoom      = 4;
                    }

                    $location = array(
                        'lat'  => $latitude,
                        'lon'  => $longitude,
                        'zoom' => $zoom
                    );

                    $id = $rs->fields['h_id'];

                    //Ips
                    $ips = Asset_host_ips::get_ips_to_string($conn, $id);

                    $hosts[$id] = array(
                        'id'           => $id,
                        'ctx'          => $rs->fields['h_ctx'],
                        'name'         => $rs->fields['hostname'],
                        'ips'          => $ips,
                        'descr'        => $rs->fields['descr'],
                        'icon'         => $rs->fields['icon'],
                        'fqdns'        => $rs->fields['fqdns'],
                        'external'     => $rs->fields['external_host'],
                        'location'     => $location,
                        'asset_value'  => $rs->fields['asset'],
                        'os'           => $rs->fields['os'],
                        'vuln_scan'    => $rs->fields['vuln'],
                        'hids'         => $rs->fields['hids']
                    );

                    $rs->MoveNext();
                }

                $rs->Free();
            }
        }

        return array($hosts, $total);
    }


    /**
     * Function get_plugins_by_sensor
     *
     * This function gets the plugins related to a collection of sensors and assets
     *
     * @param object   $conn       Database access object
     * @param hash     $sensors    Sensors to get the related plugins
     * @param hash     $assets     Assets to match with the provided plugins by sensor
     * @param bool     $edit_mode  Flag to know if return empty assets as well
     *
     * @return array   List of plugins
     */
    public static function get_plugins_by_sensor($conn, $sensors, $assets, $edit_mode = FALSE)
    {
        $plugin_data = array();

        $client      = new Alienvault_client();

        // Get system_ip
        $system_list = Av_center::get_avc_list($conn);
        $system_ip   = @$system_list['data'][strtolower(Util::get_system_uuid())]['admin_ip'];

        foreach ($sensors as $sensor_id => $s_data)
        {
            // Get all plugins related to the sensor (Skip the local host)
            try
            {
                $plugins  = $client->sensor(Util::uuid_format($sensor_id))->get_plugins_by_assets($assets);
                $plugins  = @json_decode($plugins, TRUE);
            }
            catch(Exception $e)
            {
                Av_exception::throw_error(Av_exception::USER_ERROR, $e->getMessage());
            }

            if ($plugins['status'] == 'success')
            {
                // Match with the provided assets
                foreach ($assets as $asset_id => $asset_data)
                {
                    // Warning: 'name'/'hostname' [Incongruent output from Asset_net::get_hosts and Asset_group::get_hosts]
                    $_asset_name = ($asset_data['hostname'] != '') ? $asset_data['hostname'] : $asset_data['name'];
                    $_asset_ips  = explode(',', $asset_data['ips']);

                    if (in_array($system_ip, $_asset_ips))
                    {
                        continue;
                    }
                    
                    $asset_id_canonical = Util::uuid_format($asset_id);
                    
                    if (array_key_exists($asset_id_canonical, $plugins['data']['plugins']))
                    {
                        $asset_plugins = $plugins['data']['plugins'][$asset_id_canonical];

                        foreach ($asset_plugins as $pdata)
                        {
                            $active = Asset_host_devices::check_device_connectivity($conn, $asset_id, $pdata['plugin_id'], $sensor_id, TRUE);

                            $aux_data = array(
                                    'asset'          => $_asset_name,
                                    'asset_id'       => $asset_id,
                                    'vendor'         => $pdata['vendor'],
                                    'model'          => $pdata['model'],
                                    'version'        => $pdata['version'],
                                    'plugin_id'      => $pdata['plugin_id'],
                                    'plugin'         => $pdata['name'],
                                    'sensor'         => $s_data['name'].' ['.$s_data['ip'].']',
                                    'sensor_id'      => $sensor_id,
                                    'receiving_data' => $active
                            );

                            if ($edit_mode)
                            {
                                $plugin_data[$asset_id][] = $aux_data;
                            }
                            else
                            {
                                $plugin_data[] = $aux_data;
                            }
                        }
                    }
                    elseif ($edit_mode)
                    {
                        $plugin_data[$asset_id][] = array('asset' => $_asset_name);
                    }
                }
            }
        }

        return $plugin_data;
    }

    /**
     * This function set @disable_host_update variable to enable/disable trigger calc
     *
     * @param object   $conn     Database access object
     * @param boolean  $enable   TRUE|FALSE
     *
     * @return boolean
     */
    public function disable_host_update($conn, $enable=TRUE)
    {
        if ($enable)
        {
            $conn->Execute("SET @disable_host_update=1");
        }
        else
        {
            $conn->Execute("SET @disable_host_update=NULL");
            $conn->Execute("UPDATE host SET updated=utc_timestamp() WHERE id=UNHEX(?)", array($this->get_id()));
        }

        return $enable;
    }
}

/* End of file asset_host.inc */
/* Location: ../include/classes/asset_host.inc */
