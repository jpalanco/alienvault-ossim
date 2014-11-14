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
 * @link       https://www.alienvault.com/ Alienvault web page
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

        $threshold = $conf->get_conf('threshold');

        if ($threshold == '')
        {
            $threshold = 30;
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
            'zoom' => 0
        );
        $this->asset_value = $asset_value;
        $this->threshold_c = $threshold;
        $this->threshold_a = $threshold;
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
    * Function is_hids_enabled
    *
    * This function returns HIDS configuration
    *
    *   - 0 No IPs are configured (red)
    *   - 1 All IPs are configured (green)
    *   - 2 Some IPs are configured (yellow)
    *
    * @param object $conn Database access object
    *
    * @access public
    * @return int
    * @throws Av_exception If a connection error occurred
    */
    public function is_hids_enabled($conn)
    {
        Ossim_db::check_connection($conn);

        $agents      = array();
        $agent_ips   = array();
        $agent_cidrs = array();

        $sensors = $this->sensors->get_sensors();

        // Get the agents IPs and CIDRs (For each related sensor)
        foreach ($sensors as $s_id => $s_data)
        {
            $agents = Ossec_agent::get_list($s_id);

            foreach ($agents as $a_data)
            {
                $ip_cidr = trim($a_data['ip']);

                if (preg_match("/any|0\.0\.0\.0\/0/", $ip_cidr) == TRUE)
                {
                    //Special Case: IP/CIDR is any or 0.0.0.0/0

                    return 1;
                }
                elseif (preg_match("/^\d+\.\d+\.\d+\.\d+$/", $ip_cidr) == TRUE)
                {
                    //Special case:: Default admin IP is always monitored
                    if (preg_match("/^127.0.0.1$/", $ip_cidr) == TRUE)
                    {
                        $default_admin_ip             = Util::get_default_admin_ip();
                        $agent_ips[$default_admin_ip] = $default_admin_ip;
                    }

                    $agent_ips[$ip_cidr] = $ip_cidr;
                }
                elseif (preg_match("/^\d+\.\d+\.\d+\.\d+\/\d+$/", $ip_cidr) == TRUE)
                {
                    $agent_cidrs[$ip_cidr] = $ip_cidr;
                }
            }
        }

        if (!empty($agent_ips) || !empty($agent_cidrs))
        {
            $all_are_hids = TRUE; // All IPs have HIDS enabled
            $any_is_hids  = FALSE; // True if some IP has HIDS enabled

            // Compare Ips and CIDRs
            $host_ips = $this->get_ips();
            $ips      = $host_ips->get_ips();

            foreach ($ips as $ip => $h_data)
            {
                $is_in_hids = FALSE;

                // 1-. Is Host IP an Agent IP?
                if (!empty($agent_ips[$ip]))
                {
                    $is_in_hids = TRUE;
                }
                else
                {
                    // 2-. Do Host IP belong to agent CIDR?
                    $ip_long = Asset_host_ips::ip2ulong($ip);
                    foreach ($agent_cidrs as $cidr)
                    {
                        $ranges = Cidr::expand_cidr($cidr);
                        if ($ip_long >= $ranges[0] && $ip_long <= $ranges[1])
                        {
                            $is_in_hids = TRUE;

                            break;
                        }
                    }
                }

                if ($is_in_hids == TRUE)
                {
                    $any_is_hids = TRUE;
                }
                else
                {
                    $all_are_hids = FALSE;
                }
            }

            if ($any_is_hids == TRUE)
            {
                return ($all_are_hids) ? 1 : 2; // GREEN / YELLOW
            }
        }

        return 0; // RED
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
           AND h.ctx = lr.ctx AND lr.hostIP = INET6_NTOP(hi.ip)";

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
     * Function get_os
     *
     * This function returns the operating system property (ID = 3)
     *
     * @param object $conn Database access object
     *
     * @access public
     * @return string The operating systems names in text
     */
    public function get_os($conn)
    {
        $_os_list = Asset_host_properties::get_property_from_db($conn, $this->get_id(), 3);

        return (count($_os_list) > 0) ? implode(", ", $_os_list) : _('Unknown');
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
            $filters['where'] = "h.id = UNHEX('" . $this->id . "')";
        }
        else
        {
            $filters['where'] .= " AND h.id = UNHEX('" . $this->id . "')";
        }

        $properties = Asset_host_properties::get_list($conn, $filters);

        return $properties;
    }


    /**
     * Function get_software
     *
     * This function returns the software related to host
     *
     * @param object $conn Database access object
     *
     * @access public
     * @return array  List of software
     */
    public function get_software($conn)
    {
        $filters = array(
            'where' => "h.id = UNHEX('" . $this->id . "')"
        );

        $aux_software = Asset_host_software::get_list($conn, $filters);
        $software     = $aux_software[0];

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
                'zoom' => NULL
            );
        }
        elseif (is_numeric($latitude) && is_numeric($longitude))
        {
            $zoom = ($zoom != '') ? intval($zoom) : NULL;

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
            $exp_msg = _('Error! Host not found');

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
            $zoom      = (empty($lon[1])) ? NULL : $lon[1];
        }
        else
        {
            $longitude = NULL;
            $zoom      = NULL;
        }

        $this->location = array(
            'lat'  => $latitude,
            'lon'  => $longitude,
            'zoom' => $zoom
        );

        $this->asset_value = $rs->fields['asset'];
        $this->threshold_c = $rs->fields['threshold_c'];
        $this->threshold_a = $rs->fields['threshold_a'];

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

        //Begin transaction
        $conn->StartTrans();

        $query = 'REPLACE INTO host (
                    id,
                    ctx,
                    hostname,
                    external_host,
                    fqdns,
                    asset,
                    threshold_c,
                    threshold_a,
                    rrd_profile,
                    alert,
                    persistence,
                    nat,
                    descr,
                    lat,
                    lon,
                    country,
                    icon)
                VALUES (UNHEX(?), UNHEX(?), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        //Host Location
        $location = $this->get_location();

        $latitude  = $location['lat'];
        $longitude = $location['lon'];
        $longitude .= (!empty($location['zoom'])) ? ';' . $location['zoom'] : NULL;

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
            $this->get_threshold_c(),
            $this->get_threshold_a(),
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

        //Save devices
        $this->devices->save_all_in_db($conn);

        //Save sensors
        $this->sensors->save_all_in_db($conn);

        if ($is_in_db == TRUE)
        {
            //Host services
            $host_services = new Asset_host_services($conn, $id);
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
                $ip_where  = "INET6_PTON('" . implode("'), INET6_PTON('", $ips) . "')";
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
                try
                {
                    self::report_changes($conn, 'hosts');
                }
                catch (Exception $e)
                {
                    Av_exception::write_log(Av_exception::DB_ERROR, $e->getMessage());
                }
            }
        }
        else
        {
            $exp_msg = _('Error! Host could not be saved');

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
                $_aux  = explode('#', $task_data['task_params']);
                $_nets = explode(' ', $_aux[0]);

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
    protected static function can_delete($conn, $id)
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
            $exp_msg = _('This host can not be deleted because it belongs to a policy');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }


        //It's necessary to delete host plugins and vulnerability reports
        $ctx = self::get_ctx_by_id($conn, $id);

        $host_ips = new Asset_host_ips($id);
        $host_ips->load_from_db($conn);
        $ips = array_keys($host_ips->get_ips());

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
            $ip_where = "INET6_PTON('" . implode("'), INET6_PTON('", $ips) . "')";

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

        foreach ($queries as $query)
        {
            $rs = $conn->Execute($query, $params);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
        }

        //Finish transaction
        if ($conn->CompleteTrans())
        {
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
                    self::report_changes($conn, 'hosts');
                }
                catch (Exception $e)
                {
                    Av_exception::write_log(Av_exception::DB_ERROR, $e->getMessage());
                }
            }
        }
        else
        {
            $exp_msg = _('Error! Host could not be deleted');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return TRUE;
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
    * @param object   $filter          Filter object
    * @param boolean  $report_changes  [Optional] Report changes to other components
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean
    */
    public static function delete_all_by_filter_from_db($conn, $filter, $report_changes = TRUE)
    {

        Ossim_db::check_connection($conn);

        Util::disable_perm_triggers($conn, TRUE);
        
        //Create tmp table

        $tmp_table = Util::create_tmp_table($conn,"host_id binary(16) NOT NULL, ctx binary(16) NOT NULL, PRIMARY KEY (host_id)");


        //Populate tmp table adding filtered hosts which are not included in policies

        $join    = 'LEFT JOIN policy_host_reference phr ON phr.host_id=h.id';
        $q_where = 'phr.host_id IS NULL';

        $user    = Session::get_session_user();

        if ($filter->get_num_filter_added() > 0)
        {
            $join    .= ', user_host_filter hf';
            $q_where .= " AND hf.asset_id = h.id AND hf.login = '$user'";
        }

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

        //Check if there ire hosts belong some policies
        $hosts_belong_policies = FALSE;

        $tables = ', policy_host_reference phr';

        $filters = array(
            'where' => 'phr.host_id=host.id',
            'limit' => 1
        );

        if ($filter->get_num_filter_added() > 0)
        {
            $tables           .= ', user_host_filter hf';
            $filters['where'] .= " AND hf.asset_id = host.id AND hf.login = '$user'";
        }

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
            LEFT JOIN (SELECT ip, ctx FROM host_ip hi, $tmp_table t WHERE hi.host_id = t.host_id) AS f ON h.host_ip = f.ip AND f.ctx = h.ctx";
        $queries[] = "DELETE h.* FROM vuln_nessus_latest_reports h
            LEFT JOIN (SELECT INET6_NTOP(ip) AS ip, ctx FROM host_ip hi, $tmp_table t WHERE hi.host_id = t.host_id) AS f ON h.hostIP = f.ip AND f.ctx = h.ctx";

        foreach ($queries as $query)
        {
            $rs = $conn->Execute($query);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
        }

        //Finish transaction
        if ($conn->CompleteTrans())
        {
            //Log action
            $infolog = array(_('Policy - Hosts deleted massively'));
            Log_action::log(92, $infolog);

            Util::disable_perm_triggers($conn, FALSE);
            
            if ($report_changes == TRUE)
            {
                // Clean Suggestions
                $client = new Alienvault_client();
                $client->data('')->clean_orphans('host');

                try
                {
                    self::report_changes($conn, 'hosts');
                }
                catch(Exception $e)
                {
                    Av_exception::write_log(Av_exception::DB_ERROR, $e->getMessage());
                }
            }

            // Hosts belong a policy
            if ($hosts_belong_policies == TRUE)
            {
                $exp_msg = _('Some of the hosts could not be deleted because they belong to a policy');

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
                AND host_ip.ip = INET6_PTON(?) $perms_where";

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
            AND host_ip.ip = inet6_pton(?)";

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

            list($net, $mask) = split('/', $n);

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


        $query = "SELECT COUNT(h.id) AS ips
            FROM host h $q_join, host_ip hi
            WHERE h.id = hi.host_id
            $q_where";

        $conn->SetFetchMode(ADODB_FETCH_ASSOC);

        $rs = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!$rs->EOF)
        {
            $total = $rs->fields['ips'];
            $block = 2000;
            $rs->Free();

            for ($i=0; $i<=$total; $i+=$block)
            {
                //By blocks
                $query = "SELECT HEX(h.id) AS id, inet6_ntop(hi.ip) AS ip,
                    HEX(h.ctx) AS ctx, h.hostname, h.external_host
                    FROM host h $q_join, host_ip hi
                    WHERE h.id = hi.host_id
                    $q_where
                    LIMIT $i, $block";

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
            }
        }

        return array($hosts_by_ip, $hosts_by_id);
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
            $q_where  .= ' ORDER BY '.$filters['order_by'];
        }
        else
        {
            //Order by default
            $q_where  .= ' ORDER BY hi.ip';
        }


        $query = ossim_query("SELECT count(DISTINCT host.id) AS total
           FROM host $tables, host_ip hi
           WHERE hi.host_id = host.id $q_where");

        //echo $query."<br>";

        $conn->SetFetchMode(ADODB_FETCH_ASSOC);

        $rf = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);

        if (!$rf)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $foundrows = $rf->fields['total'];

        $block = 2000;
        $rf->Free();

        for ($i=0; $i<=$foundrows; $i+=$block)
        {
            $query = ossim_query("SELECT DISTINCT host.hostname, HEX(host.id) AS h_id, HEX(host.ctx) AS h_ctx, INET6_NTOP(hi.ip) AS ip
                FROM host $tables, host_ip hi
                WHERE hi.host_id = host.id
                AND hi.ip > 0x0
                $q_where
                LIMIT $i, $block");

            //echo $query."<br>";

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

            $rs->Free();
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
                    $zoom      = (empty($lon[1])) ? 0 : $lon[1];
                }
                else
                {
                    $longitude = 0;
                    $zoom      = 0;
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
                    'asset_value' => $rs->fields['asset'],
                    'threshold_c' => $rs->fields['threshold_c'],
                    'threshold_a' => $rs->fields['threshold_a']
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
                        $zoom      = (empty($lon[1])) ? 0 : $lon[1];
                    }
                    else
                    {
                        $longitude = 0;
                        $zoom      = 0;
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
                        'asset_value' => $rs->fields['asset'],
                        'threshold_c' => $rs->fields['threshold_c'],
                        'threshold_a' => $rs->fields['threshold_a'],
                    );

                    $rs->MoveNext();
                }

                $rs->Free();
            }
        }

        return array($hosts, $total);
    }

}

/* End of file asset_host.inc */
/* Location: ../include/classes/asset_host.inc */
