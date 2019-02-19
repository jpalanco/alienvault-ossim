<?php
/**
* asset_net.inc
*
* File asset_net.inc is used to:
*   - To manage nets
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


require_once 'av_config.php';


/**
* Asset_net Class
*
* Class for managing nets
*
* @package    ossim-framework\Asset
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/

class Asset_net extends Asset
{
    /**
    * Net Owner
    *
    * @var string
    * @access protected
    */
    protected $owner;


    /**
    * This function returns the owner of the net
    *
    * @return string
    */
    public function get_owner()
    {
        return $this->owner;
    }


    /**
    * This function sets the owner of the net
    *
    * @param string  $owner  Net Owner
    *
    * @return void
    */
    public function set_owner($owner)
    {
        $this->owner = stripslashes($owner);
    }


    /**
    * Class constructor
    *
    * This function sets up the class
    *
    * @param string  $id   Net ID
    */
    public function __construct($id)
    {
        $this->set_id($id);

        $conf = $GLOBALS['CONF'];

        if (!$conf)
        {
            $conf = new Ossim_conf();
            $GLOBALS['CONF'] = $conf;
        }

        $asset_value = $conf->get_conf('def_asset');

        if ($asset_value == '')
        {
            $asset_value = 2;
        }


        $this->ctx         = Session::get_default_ctx();
        $this->name        = '';
        $this->ips         = '';
        $this->descr       = '';
        $this->icon        = NULL;
        $this->external    = 0;
        $this->asset_value = $asset_value;
        $this->owner       = NULL;
        $this->sensors     = new Asset_net_sensors($id);
    }


    /**
    * This function returns the CIDRs
    *
    * @param string $format  [Optional] Output format (array or string)
    *
    * @return array|string
    */
    public function get_ips($format = 'string')
    {
        $ips = NULL;

        if ($format == 'string')
        {
            $ips = $this->ips;
        }
        else
        {
            $ips = explode(',', $this->ips);
        }

        return $ips;
    }


    /**
    * This function returns the asset type
    *
    * @return string
    */
    public function get_asset_type()
    {
        return 'net';
    }


    /**
    * This function returns the hosts related to a Network listed by ID
    *
    * @param object   $conn     Database access object
    * @param string   $tables   [Optional] Database tables separated by comma (Join with main table)
    * @param array    $filters  [Optional] SQL statements (Only LIMIT and ORDER BY)
    * @param array    $basic    [Optional] Show basic data or detail data
    * @param boolean  $cache    [Optional] Use cached information
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array List of host IDs
    */
    public function get_hosts($conn, $tables = '', $filters = array(), $basic = TRUE, $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        $hosts = array();
        $total = 0;

        if ($basic == TRUE)
        {
            $perms_where = Asset_host::get_perms_where('h.', TRUE);

            $q_select = 'HEX(h.id) AS h_id, INET6_NTOA(hi.ip) AS h_ip, h.hostname';

            $q_where  =  "WHERE hnr.net_id = UNHEX(?)
                AND hnr.host_id = h.id
                AND hnr.net_id = n.id
                AND h.id = hi.host_id
                AND h.ctx = n.ctx $perms_where";

            if (!empty($filters['where']))
            {
                $q_where = $q_where . ' AND ' . $filters['where'];
            }

            if (!empty($filters['order_by']))
            {
                $q_where  .= ' ORDER BY '.$filters['order_by'];
            }

            if (!empty($filters['limit']))
            {
                $q_select  = 'SQL_CALC_FOUND_ROWS '.$q_select;
                $q_where  .= ' LIMIT '.$filters['limit'];
            }

            $query = "SELECT DISTINCT $q_select
                FROM host_net_reference hnr, host h, net n, host_ip hi $tables $q_where";

            $params = array($this->get_id());

            $rs = ($cache) ? $conn->CacheExecute($query, $params) : $conn->Execute($query, $params);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }

            $rs = $conn->Execute($query, $params);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }

            while (!$rs->EOF)
            {
                if (empty($hosts[$rs->fields['h_id']]))
                {
                    $total++;

                    $hosts[$rs->fields['h_id']]['hostname'] = $rs->fields['hostname'];
                    $hosts[$rs->fields['h_id']]['ips']      = $rs->fields['h_ip'];
                }
                else
                {
                    $hosts[$rs->fields['h_id']]['ips'] .= ','.$rs->fields['h_ip'];
                }

                $rs->MoveNext();
            }
        }
        else
        {
            $tables .= ', host_net_reference hr, net n';

            $n_filters = array(
                'where'    => "hr.net_id = n.id AND n.ctx = host.ctx AND hr.host_id = host.id AND hr.net_id = UNHEX('". $this->id ."') ",
                'order_by' => $filters['order_by'],
                'limit'    => $filters['limit']
            );

            if (!empty($filters['where']))
            {
                $n_filters['where'] .=  ' AND '.$filters['where'];
            }

            list($hosts, $total) = Asset_host::get_full_list($conn, $tables, $n_filters, $cache);
        }

        return array($hosts, $total);
    }


    /**
     * Function get_hids_status
     *
     * This function returns the status of HIDS agents than belongs to network
     *
     *   0 --> GRAY:   Not HIDS agents deployed
     *   1 --> RED:    Some HIDS agents deployed but not connected  (Never Connected)
     *   2 --> YELLOW: Some HIDS agents deployed but not all active
     *   3 --> GREEN:  All HIDS agents deployed and active
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

        $number_of_assets = $this->get_num_host($conn);

        $query = "SELECT agent_status
                  FROM hids_agents INNER JOIN host_net_reference ON hids_agents.host_id=host_net_reference.host_id
                  WHERE net_id = UNHEX(?)";

        $params = array($this->get_id());

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }


        if (!$rs->EOF)
        {
            $active_agents       = 0;
            $disconnected_agents = 0;
            $no_connected_agents = 0;

            while (!$rs->EOF)
            {
                switch($rs->fields['agent_status'])
                {
                    case 0:
                    case 1:
                        $no_connected_agents++;
                    break;

                    case 2:
                        $disconnected_agents++;
                    break;

                    case 3:
                    case 4:
                        $active_agents++;
                    break;

                    default:
                        $no_connected_agents++;
                }

                //It doesn't make sense to iterate until the end
                if ($active_agents > 0 && ($no_connected_agents > 0 || $disconnected_agents > 0))
                {
                    break;
                }

                $rs->MoveNext();
            }

            $number_of_agents = $no_connected_agents + $disconnected_agents + $active_agents;

            if ($number_of_agents == $number_of_assets && $active_agents == $number_of_assets)
            {
                $hids_status = 3;
            }
            else
            {
                if ($number_of_agents == $no_connected_agents)
                {
                    $hids_status = 1;
                }
                else
                {
                    $hids_status = 2;
                }
            }
        }

        return $hids_status;
    }


    /**
     * Function get_num_host
     *
     * This function gets the num of hosts from the network
     *
     * @param object  $conn  DB connection object
     * @param boolean $total Flag to indicate if we want the total with or without permissions.
     *
     * @access public
     *
     * @throws Av_exception If a connection error occurred
     *
     * @return integer  Number of host in network
     */
    public function get_num_host($conn, $total = FALSE)
    {
        Ossim_db::check_connection($conn);

        $perms = '';

        if (!$total)
        {
            $perms = Asset_host::get_perms_where('h.', TRUE);
        }

        $query = "SELECT count(hr.host_id) as num
        FROM host h, host_net_reference hr
        WHERE hr.host_id=h.id AND hr.net_id = UNHEX(?) $perms";

        $params = array($this->id);

        if (!$rs = $conn->Execute($query, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        return $rs->fields['num'];
    }

    /**
    * This function returns true if current network associated hosts have alarms
    *
    * @param object   $conn  Database access object
    * @param string   $id    Net ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean
    */
    public static function has_alarms($conn, $id)
    {
        Ossim_db::check_connection($conn);

        return Alarm::has_alarms($conn, 'net', $id);
    }


    /**
    * This function returns true if current host id has events
    *
    * @param object   $conn   Database access object
    * @param string   $id     Net ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean
    */
    public static function has_events($conn, $id)
    {
        Ossim_db::check_connection($conn);

        return SIEM::has_events($conn, 'net', $id);
    }


    /**
     * Function get_vulnerabilities
     *
     * This function returns the net vulnerabilities
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
        $n_tables = ', host_net_reference hnr';

        if (!empty($tables))
        {
            $n_tables .= $tables;
        }

        $where = " host_ip.host_id = hnr.host_id AND hnr.net_id = UNHEX('" . $this->id . "')";

        if (!empty($filters['where']))
        {
            $filters['where'] = $where . ' AND ' . $filters['where'];
        }
        else
        {
            $filters['where'] = $where;
        }

        return Vulnerabilities::get_vulnerabilities($conn, $n_tables, $filters, $cache);
    }


    /**
    * This function returns the Machine State property (ID = 7) sum of each related host
    *
    * @param object  $conn   Database access object
    *
    * @throws Exception  If a connection error occurred
    *
    * @return string up / down / unknown
    */
    public function get_status($conn)
    {
        Ossim_db::check_connection($conn);

        $status = array(
            'up'      => 0,
            'down'    => 0,
            'unknown' => 0
        );

        $_host_data = $this->get_hosts($conn);
        $hosts      = $_host_data[0];

        foreach ($hosts as $host_id => $_data)
        {
            $st = Asset_host_properties::get_status_by_host($conn, $host_id);

            $status[$st]++;
        }

        $status_str = '';

        foreach ($status as $key => $val)
        {
            if ($status_str != '')
            {
                $status_str .= ' / ';
            }

            $status_str .= "$val $key";
        }

        return $status_str;
    }


    /**
    * This function returns the user property (ID = 8) sum of each related host
    *
    * @param object  $conn   Database access object
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public function get_users($conn, $filters = array())
    {
        Ossim_db::check_connection($conn);

        return Asset_host_properties::get_users_by_net($conn, $this->id, $filters);
    }


    /**
    * This function returns the alarms related to the net
    *
    * @param object   $conn       Database access object
    * @param string   $id         Net ID
    * @param integer  $from       From offset
    * @param integer  $max        [Optional] Maximum elements per page
    * @param string   $date_from  [Optional] Date from filter
    * @param string   $date_to    [Optional] Date to filter
    * @param string   $filter     [Optional] SQL query
    * @param string   $order      [Optional] SQL order statement
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array Alarms list and counter
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
            'host'          => '',
            'net'           => $id,
            'host_group'    => ''
        );

        return Alarm::get_list($conn, $criteria);
    }

    /**
     * Function get_highest_risk_alarms
     *
     * This function returns the highest alarm risk from the given network
     *
     * @param object $conn Database access object
     * @param string $id   Network ID
     *
     * @return Hash
     */
    public static function get_highest_risk_alarms($conn, $id)
    {
        $criteria = array('net' => $id);

        return Alarm::get_highest_risk_by_asset($conn, $criteria);
    }

    /**
    * This function returns the properties related to host
    *
    * @param object   $conn  Database access object
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array List of properties
    */
    public function get_properties($conn, $filters = array())
    {
        if (empty($filters['where']))
        {
            $filters['where'] = "h_id IN (SELECT host_id FROM host_net_reference WHERE net_id = UNHEX('".$this->id."'))";
        }
        else
        {
            $filters['where'] .= " AND h_id IN (SELECT host_id FROM host_net_reference WHERE net_id = UNHEX('".$this->id."'))";
        }

        $properties = Asset_host_properties::get_all($conn, $filters);

        return $properties;
    }


    /**
    * This function returns the software related to net
    *
    * @param object  $conn     Database access object
    * @param array   $filters  [Optional] SQL statements (WHERE, LIMIT, ORDER BY ...)
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array List of software
    */
    public function get_software($conn, $filters = array())
    {
        $_host_data = $this->get_hosts($conn);
        $hosts      = $_host_data[0];

        $hosts = array_keys($hosts);

        $software = array();

        if (is_array($hosts) && !empty($hosts))
        {
            $host_where = implode("'), UNHEX('", $hosts);

            if (empty($filters['where']))
            {
                $filters['where']  = "h.id IN (UNHEX('".$host_where. "'))";
            }
            else
            {
                $filters['where'] .= " AND h.id IN (UNHEX('".$host_where. "'))";
            }

            $software = Asset_host_software::get_list($conn, $filters);
        }

        return $software;
    }


    /**
    * This function returns the services related to host
    *
    * @param object   $conn  Database access object
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array List of services
    */
    public function get_services($conn, $filters = array())
    {
        $serv_where = "SELECT host_id FROM host_net_reference WHERE net_id = UNHEX('". $this->id ."')";

        if (empty($filters['where']))
        {
            $filters['where']  = "h.id IN (".$serv_where. ")";
        }
        else
        {
            $filters['where'] .= " AND h.id IN (".$serv_where. ")";
        }

        $services   = Asset_host_services::get_list($conn, $filters);

        return $services;
    }


    /**
    * This function returns the vulnerabilities found in vuln_nessus_latest_results table
    * related to service/port pair
    *
    * @param object   $conn      Database access object
    * @param string   $service   Service
    * @param integer  $port      Service port
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public function get_vulns_by_service($conn, $service, $port)
    {
        $ips = $this->get_ips('array');

        return Asset_host_services::get_vulns_by_service($conn, $ips, $this->ctx, $service, $port);
    }


    /**
     * Function get_plugins
     *
     * This function returns the plugins related to network
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
        $tables  = '';
        $filters = array();
        $sensors = $this->get_sensors()->get_sensors();

        // Get only plugins from one specified sensor
        if (valid_hex32($sensor_id))
        {
            $tables           = ', host_sensor_reference hsr';
            $filters['where'] = "hsr.host_id = h.id AND hsr.sensor_id = UNHEX('$sensor_id')";

            $_sensor_aux = $sensors[$sensor_id];
            $sensors     = array($sensor_id => $_sensor_aux);
        }

        $_asset_data = $this->get_hosts($conn, $tables, $filters);
        $assets      = $_asset_data[0];

        return Asset_host::get_plugins_by_sensor($conn, $sensors, $assets, $edit_mode);
    }


    /**
     * Function get_events
     *
     * This function returns the events related to network
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
        $siem       = new Siem(TRUE); // Perms byPass TRUE

        // Create temporary table with inner assets
        $_tmp_table = Util::create_tmp_table($siem->conn);
        $_subnets   = self::get_my_subnets($conn, $this->id);
        $_net_ids   = array_keys($_subnets);
        $_net_ids[] = $this->id;

        $rs = $siem->conn->Execute('REPLACE INTO '.$_tmp_table.' (id) VALUES (UNHEX("'.implode('")), (UNHEX("', $_net_ids).'"))');

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $siem->conn->ErrorMsg());
        }

        if ($search_str != '')
        {
            $search_str = escape_sql($search_str, $conn);
        }

        return $siem->get_events_sp($_tmp_table, 'network', $from, $maxrows, "$order $torder", $search_str);

    }


    /**
     * Function get_events_status
     *
     * This function returns the event count and level related to asset network
     *
     *
     * @param object $conn Database access object
     *
     *
     * @throws Exception  If a connection error occurred
     *
     * @return array Event Count and Event Level values to details info
     */
    public function get_events_status($conn)
    {

        $siem       = new Siem(TRUE); // Perms byPass TRUE

        // Create temporary table with inner assets
        $_tmp_table = Util::create_tmp_table($siem->conn);
        $_subnets   = self::get_my_subnets($conn, $this->id);
        $_net_ids   = array_keys($_subnets);
        $_net_ids[] = $this->id;

        $rs = $siem->conn->Execute('REPLACE INTO '.$_tmp_table.' (id) VALUES (UNHEX("'.implode('")), (UNHEX("', $_net_ids).'"))');

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $siem->conn->ErrorMsg());
        }

        // Get Total
        $siem->add_criteria('src_net', $_tmp_table.'.id');
        $event_count_src = $siem->get_events_total();

        $siem->clear_criteria();
        $siem->add_criteria('dst_net', $_tmp_table.'.id');
        $event_count_dst = $siem->get_events_total();

        $siem->clear_criteria();
        $siem->add_criteria('src_net', $_tmp_table.'.id');
        $siem->add_criteria('dst_net', $_tmp_table.'.id');
        $event_count = $event_count_src + $event_count_dst - $siem->get_events_total();

        $event_level = ($event_count > 0) ? 1 : 0;

        // Total with risk = 1
        $siem->clear_criteria();
        $siem->add_criteria(array('src_net', 'dst_net'), $_tmp_table.'.id');
        $siem->add_criteria('ossim_risk_a', 1);

        $event_count_medium = $siem->get_events_total();

        // Total with risk > 1
        $siem->clear_criteria();
        $siem->add_criteria(array('src_net', 'dst_net'), $_tmp_table.'.id');
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
     * This function returns the suggestions messages related to the network
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
    * This function sets CIDR list
    *
    * @param array $ips  Net CIDRs
    *
    * @return void
    */
    public function set_ips($ips)
    {
        $ips = $this->delete_duplicated_cidrs($ips);

        $this->ips = $ips;
    }


    /**
    * This function sets the net data from database
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

        //Getting net information
        $query  = 'SELECT n.* , HEX(id) AS id, HEX(ctx) AS ctx FROM net n WHERE n.id = UNHEX(?)';
        $params = array($this->get_id());

        $rs = ($cache == TRUE) ? $conn->CacheExecute($query, $params) : $conn->Execute($query, $params);

        if (empty($rs->fields['id']))
        {
            $exp_msg = _('Error! Net not found');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $this->ctx         = $rs->fields['ctx'];
        $this->name        = $rs->fields['name'];
        $this->ips         = $rs->fields['ips'];
        $this->descr       = $rs->fields['descr'];
        $this->icon        = $rs->fields['icon'];
        $this->external    = $rs->fields['external_net'];
        $this->asset_value = $rs->fields['asset'];
        $this->owner       = $rs->fields['owner'];

        $this->sensors->load_from_db($conn, $cache);
    }


    /**
    * This function saves net into database
    *
    * @param object   $conn            Database access object
    * @param boolean  $report_changes  [Optional] Report changes to other components
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean
    */
    public function save_in_db($conn, $report_changes = TRUE)
    {
        Ossim_db::check_connection($conn);

        $id   = $this->get_id();
        $ips  = $this->get_ips();
        $ctx  = $this->get_ctx();
        $name = $this->get_name();


        if (Session::get_net_where() != '')
        {
            if (!self::is_cidr_in_my_nets($conn, $ips, $ctx))
            {
                $exp_msg = _('Error! CIDR not allowed.  Check your asset filter');

                Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
            }
        }

        $existing_netname = self::cidr_exists($conn, $id, $ips, $ctx);

        if (!empty($existing_netname))
        {
            $exp_msg = sprintf(_("Error! CIDR not allowed. <b>%s</b> already contains the same CIDR"), $existing_netname);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $is_in_db = self::is_in_db($conn, $id);

        Util::disable_perm_triggers($conn, TRUE);

        //Begin transaction
        $conn->StartTrans();

        $query = 'REPLACE INTO net (
                    id,
                    ctx,
                    name,
                    ips,
                    external_net,
                    asset,
                    rrd_profile,
                    alert,
                    persistence,
                    descr,
                    icon,
                    owner)
                VALUES (UNHEX(?), UNHEX(?), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';


       $params = array(
            $id,
            $ctx,
            $name,
            $ips,
            $this->get_external(),
            $this->get_asset_value(),
            0,
            0,
            0,
            $this->get_descr(),
            $this->get_icon(),
            $this->get_owner()
        );

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        //Save sensors
        $this->sensors->save_all_in_db($conn);


        if ($is_in_db == TRUE)
        {
            $this->delete_cidrs_from_cache($conn, $id);
        }


        $this->add_cidrs_to_cache($conn, $id, $ips);


        //Host-Network reference
        self::set_host_net_reference($conn, $id);

        //Finish transaction
        if ($conn->CompleteTrans())
        {
            Util::disable_perm_triggers($conn, FALSE);

            $infolog = array($name);

            if ($is_in_db == TRUE)
            {
                Log_action::log(28, $infolog);
            }
            else
            {
                Log_action::log(27, $infolog);
            }

            if ($report_changes == TRUE)
            {
                try
                {
                    self::report_changes($conn, 'nets');
                }
                catch(Exception $e)
                {
                    Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
                }
            }
        }
        else
        {
            $exp_msg = _('Error! Net could not be saved');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return TRUE;
    }


    /**
    * This function returns true if network is monitored with Nagios
    *
    * @param object  $conn   Database access object
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean
    */
    public function is_nagios_enabled($conn)
    {
        return Asset_net_scan::is_plugin_in_net($conn, $this->get_id(), 2007);
    }

    /**
     * Function get_availability
     *
     * This function returns the Nagios status based on the UP status percent of the group
     *
     * @param object $conn Database access object
     *
     * @return Array
     */
    public function get_availability($conn)
    {
        $total         = 0;
        $monitored_up  = 0;
        $not_monitored = 0;

        $perms_where = Asset_host::get_perms_where('h.', TRUE);
        $q_where     =  "WHERE hnr.net_id = UNHEX(?)
                        AND hnr.host_id = h.id
                        AND hnr.net_id = n.id
                        AND h.id = hi.host_id
                        AND h.ctx = n.ctx $perms_where";

        $query_hosts = "SELECT DISTINCT h.id AS id
                        FROM host_net_reference hnr, host h, net n, host_ip hi $q_where";

        $sql = "SELECT ha.status FROM alienvault.host h
                LEFT JOIN alienvault.host_scan ha ON ha.host_id=h.id AND ha.plugin_id=2007 and ha.plugin_sid=0
                WHERE h.id IN ($query_hosts)";

        $rs = $conn->Execute($sql, array($this->get_id()));

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            while (!$rs->EOF)
            {
                if ($rs->fields['status'] == '2')
                {
                    $monitored_up++;
                }

                if ($rs->fields['status'] == '0' || $rs->fields['status'] == NULL)
                {
                    $not_monitored++;
                }

                $total++;

                $rs->MoveNext();
            }
        }

        $percent            = ($total > 0) ? $monitored_up * 100 / $total : 0;
        $not_monitored_perc = ($total > 0) ? $not_monitored * 100 / $total : 0;


        if ($total == 0 || $not_monitored_perc == 100)
        {
            // Grey
            $availability_level = 0;
        }
        elseif ($percent >= 95)
        {
            // Green
            $availability_level = 1;
        }
        elseif ($percent >= 75)
        {
            // Yellow
            $availability_level = 2;
        }
        else
        {
            // Red
            $availability_level = 3;
        }

        $availability_value = floor($percent) . '%';

        return array($availability_value, $availability_level);
    }

    /**
    * This function returns Automatic Asset Discovery configuration.
    * Net is scanned with NMAP scheduled task
    *
    * @param object  $conn   Database access object
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean
    */
    public function is_autodetected($conn)
    {
        Ossim_db::check_connection($conn);

        $networks = array();

        //Getting host sensors
        $sensor_obj = $this->get_sensors();
        $sensors    = $sensor_obj->get_sensors();
        $sensors    = array_keys($sensors);

        $q_sensors  = implode("'), UNHEX('", $sensors);
        $q_sensors  = "UNHEX('".$q_sensors."')";


        //Getting Inventory Tasks
        $target_param = " AND task_sensor IN($q_sensors) AND task_enable = 1";
        $task_list    = Inventory::get_list($conn, '', 5, $target_param);

        if (count($task_list) > 0)
        {
            //Getting networks from inventory tasks
            foreach ($task_list as $task_data)
            {
                list($_nets) = Util::nmap_without_excludes($task_data['task_params']);

                foreach ($_nets as $net)
                {
                    if (self::valid_cidr($net))
                    {
                        $networks[$net] = $net;
                    }
                }
            }


            if (is_array($networks) && !empty($networks))
            {
                $all_are_autodetected  = TRUE;  // All CIDRs are autodetected
                $any_is_autodetected   = FALSE; // True if some CIDR is autodetected

                // Compare CIDRs
                $cidrs = $this->get_ips();

                //Getting closest nets
                $closest_nets = self::get_closest_nets($conn, $cidrs);

                $cidrs = explode(',', $cidrs);

                foreach ($cidrs as $cidr)
                {
                    $is_autodetected = FALSE;

                    // 1-. Is Net CIDR autodetected?
                    if (array_key_exists($cidr, $networks))
                    {
                        $is_autodetected = TRUE;
                    }
                    else
                    {
                        foreach($closest_nets as $cn_data)
                        {
                            if (array_key_exists($cn_data['ips'], $networks))
                            {
                                $is_autodetected = TRUE;

                                break;
                            }
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

        return 0;  // RED
    }



    /*************************************************
     *************** Private functions ***************
     *************************************************/



    /**
    * This function adds CIDRs to table net_cidrs
    *
    * @param object  $conn   Database access object
    * @param string  $id     Net ID
    * @param string  $ips    Comma-separated CIDRs
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean
    */
    private function add_cidrs_to_cache($conn, $id, $ips)
    {
        Ossim_db::check_connection($conn);

        $cidrs = explode(',', $ips);

        foreach ($cidrs as $cidr)
        {
            $cidr = trim($cidr);

            $exp = self::expand_cidr($cidr, 'SHORT', 'IP');

            $query  = 'REPLACE INTO net_cidrs (`net_id`,`cidr`,`begin`,`end`) VALUES (UNHEX(?), ?, INET6_ATON(?), INET6_ATON(?))';
            $params = array(
                $id,
                $cidr,
                $exp[$cidr][0],
                $exp[$cidr][1]
            );

            $rs = $conn->Execute($query, $params);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
        }

        return TRUE;
    }


    /**
    * This function deletes CIDRs to table net_cidrs
    *
    * @param object  $conn   Database access object
    * @param string  $id     Net ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean
    */
    private function delete_cidrs_from_cache($conn, $id)
    {
        Ossim_db::check_connection($conn);

        $query  = 'DELETE FROM net_cidrs WHERE net_id = UNHEX(?)';
        $params = array($id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        return TRUE;
    }


    /**
    * This function deletes duplicated CIDRs from CIDR list
    *
    * @param string  $cidrs  Comma-separated CIDRs
    *
    * @return string
    */
    private static function delete_duplicated_cidrs($ips)
    {
        $cidrs = explode(',', $ips);

        $cidrs = array_map('trim', $cidrs);
        $cidrs = array_unique($cidrs);

        return implode(',', $cidrs);
    }



    /*************************************************
     *************** Static functions ****************
     *************************************************/



    /**
    * This function checks if net could be deleted
    *
    * @param object  $conn   Database access object
    * @param string  $id     Net ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean
    */
    public static function can_delete($conn, $id)
    {
        Ossim_db::check_connection($conn);

        $query  = 'SELECT count(*) AS num FROM policy_net_reference WHERE net_id = UNHEX(?)';
        $params = array($id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if ($rs->fields['num'] > 0)
        {
            return FALSE;
        }

        return TRUE;
    }


    /**
    * This function inserts/deletes nets into table host_net_reference
    *
    * @param object  $conn    Database access object
    * @param string  $id      Net ID
    * @param boolean $delete  [Optional] Delete nets
    * @param boolean $insert  [Optional] Insert nets
    *
    * @throws Exception  If a connection error occurred
    *
    * @return void
    */
    protected static function set_host_net_reference($conn, $id, $delete = TRUE, $insert = TRUE)
    {
        Ossim_db::check_connection($conn);

        $params = array($id);

        if ($delete == TRUE)
        {
            $query  = 'DELETE FROM alienvault.host_net_reference WHERE net_id = UNHEX(?)';

            $rs = $conn->Execute($query, $params);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
        }

        if ($insert == TRUE)
        {
            $query  = 'REPLACE INTO host_net_reference SELECT host.id, net_id
                FROM host, host_ip, net_cidrs WHERE host.id = host_ip.host_id
                AND host_ip.ip >= net_cidrs.begin
                AND host_ip.ip <= net_cidrs.end
                AND net_id = UNHEX(?)';

            $rs = $conn->Execute($query, $params);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
        }
    }


    /**
    * This function returns an expanded CIDRs
    *
    * @param string  $ips            Comma-separated CIDRs
    * @param string  $output_type    [Optional] Output type (SHORT (IP min and IP max) or FULL (All ips))
    * @param string  $output_format  [Optional] Output format (IP or LONG (numeric))
    *
    * @return array
    */
    public static function expand_cidr($ips, $output_type = 'FULL', $output_format = 'IP')
    {
        $ip_range = array();

        $ips = explode(',', $ips);

        if (!is_array($ips) || empty($ips))
        {
            return $ip_range;
        }

        foreach($ips as $ip_cidr)
        {
            $ip_cidr = trim($ip_cidr);
            $cidr_range = Cidr::expand_CIDR($ip_cidr, $output_type, $output_format);

            if (is_array($cidr_range) && !empty($cidr_range))
            {

                if ($output_type == 'SHORT')
                {
                    if ($output_format == 'LONG')
                    {
                        $ip_range[$ip_cidr][0] = ($cidr_range[0] < $ip_range[$ip_cidr][0] || $ip_range[$ip_cidr][0] < 1) ? $cidr_range[0] : $ip_range[$ip_cidr][0];
                        $ip_range[$ip_cidr][1] = ($cidr_range[1] >= $ip_range[$ip_cidr][1]) ? $cidr_range[1] : $ip_range[$ip_cidr][1];
                    }
                    else
                    {
                        $ip_range[$ip_cidr] = $cidr_range;
                    }

                }
                else
                {
                    $ip_range[$ip_cidr] = array_flip($cidr_range);
                }
            }
        }

        return $ip_range;
    }


    /**
    * This function returns a SQL condition for filtering nets
    *
    * @param string  $alias     [Optional] MySQL alias
    * @param string  $with_ctx  [Optional] Use context in filter
    *
    * @return string
    */
    public static function get_perms_where($alias = '', $with_ctx = TRUE)
    {
        $query      = '';

        $ctx_where  = Session::get_ctx_where();
        $net_where  = Session::get_net_where();


        if ($with_ctx == TRUE && $ctx_where != '')
        {
            $query .= ' AND '.$alias.'ctx IN ('.$ctx_where.')';
        }

        if ($net_where != '')
        {
            $query .= ' AND '.$alias.'id IN ('.$net_where.')';
        }

        return $query;
    }


    /**
    * This function checks if $ips is a valid CIDR
    *
    * @param string $ips  CIDR
    *
    * @return boolean
    */
    public static function valid_cidr($ips)
    {
        if (preg_match('/^(([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\/([1-9]|[1-2][0-9]|3[0-2])$/', $ips))
        {
            return TRUE;
        }

        return FALSE;
    }


    /**
    * This function returns an net object
    *
    * @param object  $conn   Database access object
    * @param string  $id     Net ID
    * @param boolean $cache  [Optional] Use cached information
    *
    * @throws Exception  If a connection error occurred
    *
    * @return object
    */
    public static function get_object($conn, $id, $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        $net = NULL;

        $params = array($id);
        $query  = 'SELECT HEX(id) AS id FROM net WHERE id = UNHEX(?)';

        $rs = ($cache) ? $conn->CacheExecute($query, $params) : $conn->Execute ($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!$rs->EOF)
        {
            $net = new self($id);
            $net->load_from_db($conn, $cache);
        }

        return $net;
    }


    /**
    * This function checks if net exists
    *
    * @param object  $conn   Database access object
    * @param string  $id     Net ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean
    */
    public static function is_in_db($conn, $id)
    {
        Ossim_db::check_connection($conn);

        $res = FALSE;

        if(!valid_hex32($id))
        {
            return $res;
        }

        $params = array($id);
        $query  = 'SELECT count(*) AS found FROM net WHERE id = UNHEX(?)';

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
    * This function checks if net exists and it is allowed
    *
    * @param object  $conn   Database access object
    * @param string  $id     Net ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean
    */
    public static function is_allowed($conn, $id)
    {
        Ossim_db::check_connection($conn);

        return Session::netAllowed($conn, $id);
    }


    /**
    * This function deletes the net from database
    *
    * @param object   $conn            Database access object
    * @param string   $id              Net ID
    * @param boolean  $report_changes  [Optional] Report changes to other components
    *
    * @throws Exception  If an error occurred
    *
    * @return array
    */
    public static function delete_from_db($conn, $id, $report_changes = TRUE)
    {
        Ossim_db::check_connection($conn);

        if (!self::can_delete($conn, $id))
        {
            $exp_msg = _('Network belongs to one or more policies');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }


        //Begin transaction
        $conn->StartTrans();

        //Sensors
        Asset_net_sensors::delete_all_from_db($conn, $id);

        //Cached CIDRs
        self::delete_cidrs_from_cache($conn, $id);

        //Scan
        Asset_net_scan::delete_all_from_db($conn, $id);

        //Net network reference
        self::set_host_net_reference($conn, $id, TRUE, FALSE);


        $queries = array();
        $params  = array($id);


        //Riskmaps
        $queries[] = 'DELETE s FROM bp_asset_member a,bp_member_status s WHERE s.member_id = a.member AND a.member = UNHEX(?)';
        $queries[] = "DELETE FROM bp_asset_member WHERE member = UNHEX(?) AND type='net'";

        //KDB
        $queries[] = 'DELETE FROM repository_relationships WHERE keyname = ?';

        //Qualification (Compromise and attack)
        $queries[] = 'DELETE FROM net_qualification WHERE net_id = UNHEX(?)';

        //Net groups
        $queries[] = 'DELETE FROM net_group_reference WHERE net_id = UNHEX(?)';

        //Net vulnerabilities
        $queries[] = 'DELETE FROM net_vulnerability WHERE net_id = UNHEX(?)';

        //Net
        $queries[] = 'DELETE FROM net WHERE id = UNHEX(?)';


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
            Log_action::log(29, $infolog);

            if ($report_changes == TRUE)
            {
                try
                {
                    self::report_changes($conn, 'nets', TRUE);
                }
                catch(Exception $e)
                {
                    Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
                }
            }
        }
        else
        {
            $exp_msg = _('Error! Net could not be deleted');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return TRUE;
    }


    /**
    * This function deletes net icon from database
    *
    * @param object  $conn   Database access object
    * @param string  $id     Net ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean
    */
    public static function delete_icon($conn, $id)
    {
        Ossim_db::check_connection($conn);

        $query  = 'UPDATE net SET icon = NULL WHERE id = UNHEX(?)';
        $params = array($id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        return TRUE;
    }


    /**
    * This function returns closest nets to the net
    *
    * @param object  $conn   Database access object
    * @param string  $ips    CIDRs
    * @param string  $ctx    [Optional] Net context
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_closest_nets($conn, $ips, $ctx = '')
    {
        Ossim_db::check_connection($conn);

        $nets = array();

        $cidrs = explode(',', $ips);

        if (!is_array($cidrs) || empty($cidrs))
        {
            return $nets;
        }

        // Permissions
        $perms_where = self::get_perms_where('net.', TRUE);

        $ctxs = (preg_match('/unhex/i', $ctx) == TRUE) ? $ctx : "UNHEX('".str_replace(',', "'), UNHEX('",$ctx)."')";

        foreach ($cidrs as $cidr)
        {
            if ($ctx !=  '')
            {
                $query = "SELECT HEX(net_id) AS net_id, net_cidrs.cidr, net.ips, CONV(HEX(begin),16,10) AS begind, CONV(HEX(end), 16, 10) AS endd
                    FROM net_cidrs, net
                    WHERE net.id = net_cidrs.net_id $perms_where
                    AND net.ctx IN ($ctxs)
                    AND INET6_ATON(?) >= net_cidrs.begin
                    AND INET6_ATON(?) <= net_cidrs.end
                    ORDER BY endd-begind";
            }
            else
            {
                $query = "SELECT HEX(net_id) AS net_id, net_cidrs.cidr, net.ips, CONV(HEX(begin),16,10) AS begind, CONV(HEX(end), 16, 10) AS endd
                    FROM net_cidrs, net
                    WHERE net.id = net_cidrs.net_id $perms_where
                    AND INET6_ATON(?) >= net_cidrs.begin
                    AND INET6_ATON(?) <= net_cidrs.end
                    ORDER BY endd-begind";
            }


            $ip_range = self::expand_cidr(trim($cidr), 'SHORT', 'IP');

            $params = array($ip_range[$cidr][0], $ip_range[$cidr][1]);

            $rs = $conn->Execute($query, $params);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }

            while (!$rs->EOF)
            {
                $n_key = $rs->fields['net_id'].'-'.$rs->fields['cidr'];

                $nets[$n_key] = array(
                  'id'    => $rs->fields['net_id'],
                  'cidr'  => $rs->fields['cidr'],
                  'ips'   => $rs->fields['ips'],
                  'begin' => $rs->fields['begind'],
                  'end'   => $rs->fields['endd']
                );

                $rs->MoveNext();
            }
        }

        return $nets;
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

        $locations   = array();
        $perms_where = '';

        $host_where = Session::get_host_where();

        if ($host_where != '')
        {
            $perms_where = " AND hnr.host_id in ($host_where)";
        }

        ;
        $query = "SELECT HEX(id) as id, h.hostname, h.lat, h.lon FROM host h, host_net_reference hnr
        			WHERE h.id=hnr.host_id AND hnr.net_id=UNHEX(?) AND h.lat IS NOT NULL AND h.lon IS NOT NULL $perms_where";

        $params = array($this->get_id());
        $rs     = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $lon = explode(';', $rs->fields['lon']);
            $locations[$rs->fields['id']] = array(
                'name' => $rs->fields['hostname'],
                'lat'  => $rs->fields['lat'],
                'lon'  => $lon[0],
                'zoom' => $lon[1]
            );

            $rs->MoveNext();
        }

        return $locations;

    }


    /************************************************************************************************/
    /************************************************************************************************/
    /************************************    STATIC FUNCTIONS    ************************************/
    /************************************************************************************************/
    /************************************************************************************************/


    /**
    * This function deletes all networks in database filtered by optional query search
    *
    * @param object   $conn             Database access object
    * @param object   $filters          [Optional] SQL statements (WHERE, LIMIT, ORDER BY ...)
    * @param boolean  $report_changes   [Optional] Report changes to other components
    *
    * @return boolean
    */
    public static function bulk_delete($conn, $filters = array(), $report_changes = TRUE)
    {
        Ossim_db::check_connection($conn);

        Util::disable_perm_triggers($conn, TRUE);

        // Create tmp table
        $tmp_table = Util::create_tmp_table($conn,"net_id binary(16) NOT NULL, PRIMARY KEY (net_id)");
        $session   = session_id();

        //Populate tmp table adding filtered networks which are not included in policies
        $join    = 'LEFT JOIN policy_net_reference pnr ON pnr.net_id = net.id';
        $q_where = 'pnr.net_id IS NULL';

        //Network Selected
        $join    .= ', user_component_filter uc';
        $q_where .= " AND uc.asset_id = net.id AND uc.asset_type='network' AND uc.session_id = '$session'";


        $query = ossim_query("INSERT INTO $tmp_table (net_id) SELECT id
                FROM net $join WHERE $q_where");

        $conn->SetFetchMode(ADODB_FETCH_ASSOC);

        $rs = $conn->Execute($query);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        //Check if there are networks belong some policies
        $tables = ', policy_net_reference pnr';

        $_filters = array(
                'where' => 'pnr.net_id = net.id',
                'limit' => 1
        );

        if ($filters['where'] != '')
        {
            $_filters['where'] .= ' AND ('.$filters['where'].')';
        }

        $_net_list            = self::get_list($conn, $tables, $_filters);

        $nets_belong_policies = ($_net_list[1] > 0) ? TRUE : FALSE;

        // Get IDs to delete
        $tmp_ids = array();
        $query   = "SELECT HEX(net_id) AS net_id FROM $tmp_table";
        $rs      = $conn->Execute($query);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $tmp_ids[] = $rs->fields['net_id'];
            $rs->MoveNext();
        }

        // Delete sequence
        // Begin transaction
        $conn->StartTrans();

        // Delete statements (Method callings to delete)
        foreach ($tmp_ids as $_net_id)
        {
            //Sensors
            Asset_net_sensors::delete_all_from_db($conn, $_net_id);

            //Cached CIDRs
            self::delete_cidrs_from_cache($conn, $_net_id);

            //Scan
            Asset_net_scan::delete_all_from_db($conn, $_net_id);

            //Net network reference
            self::set_host_net_reference($conn, $_net_id, TRUE, FALSE);
        }

        // Delete statements (Queries to delete)
        $queries   = array();
        $queries[] = "DELETE n.* FROM bp_member_status n,         $tmp_table f WHERE f.net_id = n.member_id";
        $queries[] = "DELETE n.* FROM bp_asset_member n,          $tmp_table f WHERE f.net_id = n.member AND n.type = 'net'";
        $queries[] = "DELETE n.* FROM repository_relationships n, $tmp_table f WHERE f.net_id = UNHEX(n.keyname)";
        $queries[] = "DELETE n.* FROM net_qualification n,        $tmp_table f WHERE f.net_id = n.net_id";
        $queries[] = "DELETE n.* FROM net_group_reference n,      $tmp_table f WHERE f.net_id = n.net_id";
        $queries[] = "DELETE n.* FROM net_vulnerability n,        $tmp_table f WHERE f.net_id = n.net_id";
        $queries[] = "DELETE n.* FROM net n,                      $tmp_table f WHERE f.net_id = n.id";

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
            $infolog = array(_('Policy - Networks deleted massively'));
            Log_action::log(92, $infolog);

            Util::disable_perm_triggers($conn, FALSE);

            Filter_list::clean_selection($conn, 'network');

            if ($report_changes == TRUE)
            {
                try
                {
                    self::report_changes($conn, 'nets', TRUE);
                }
                catch (Exception $e)
                {
                    Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
                }
            }

            // Networks belong a policy
            if ($nets_belong_policies == TRUE)
            {
                $exp_msg = _('Some networks belongs to one or more policies');

                Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
            }
        }
        else
        {
            $exp_msg = _('Error! Networks could not be deleted');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return TRUE;
    }

    /**
    * This function returns the network name if exists with the same cidr(s) or empty
    *
    * @param object  $conn   Database access object
    * @param string  $id     Network uuid
    * @param string  $ips    Comma-separated CIDRs
    * @param string  $ctx    [Optional] Net context
    *
    * @throws Exception  If a connection error occurred
    *
    * @return string
    */
    public static function cidr_exists($conn, $id, $ips, $ctx = '')
    {
        Ossim_db::check_connection($conn);

        if (!empty($ips))
        {
            $perms_where = self::get_perms_where('net.', TRUE);
            $ctxs        = (preg_match('/unhex/i', $ctx) == TRUE) ? $ctx : "UNHEX('".str_replace(',', "'), UNHEX('",$ctx)."')";
            $ips_list    = explode(',', $ips);

            foreach ($ips_list as $cidr)
            {
                $ip_range = self::expand_cidr(trim($cidr), 'SHORT', 'IP');

                if ($ctx !=  '')
                {
                    $query = "SELECT net.name FROM net_cidrs, net WHERE net.id = net_cidrs.net_id $perms_where
                        AND net.ctx IN ($ctxs) AND net.id<>UNHEX(?) AND INET6_ATON(?) = net_cidrs.begin AND INET6_ATON(?) = net_cidrs.end";
                }
                else
                {
                    $query = "SELECT net.name FROM net_cidrs, net WHERE net.id = net_cidrs.net_id $perms_where
                         AND net.id<>UNHEX(?) AND INET6_ATON(?) = net_cidrs.begin AND INET6_ATON(?) = net_cidrs.end";
                }

                $params = array($id, $ip_range[$cidr][0], $ip_range[$cidr][1]);

                $rs = $conn->Execute ($query, $params);

                if (!$rs)
                {
                    Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
                }

                if (!$rs->EOF)
                {
                    return $rs->fields['name'];
                }

                $rs->free();
            }

        }

        return '';
    }

    /**
    * This function checks if CIDR belongs to my nets
    *
    * @param object  $conn   Database access object
    * @param string  $ips    Comma-separated CIDRs
    * @param string  $ctx    [Optional] Net Context
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean
    */
    public static function is_cidr_in_my_nets($conn, $ips, $ctx = '')
    {
        $nets = self::get_closest_nets($conn, $ips, $ctx);

        return (count($nets) > 0) ? TRUE : FALSE;
    }


    /**
    * This function returns true if CIDR could be modified
    *
    * @param object  $conn   Database access object
    * @param string  $id     Net ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean
    */
    public static function can_i_modify_ips($conn, $id)
    {
        Ossim_db::check_connection($conn);

        if (Session::am_i_admin() || Session::get_net_where() == '')
        {
            return TRUE;
        }


        $cidrs = self::get_ips_by_id($conn, $id);

        if (!empty($cidrs))
        {
            $cidrs     = explode(',', $cidrs);
            $num_cidrs = count($cidrs);
        }
        else
        {
            return FALSE;
        }

        $perms_where = self::get_perms_where('c3.', TRUE);

        $query = "SELECT DISTINCT HEX(c1.net_id), c1.cidr
            FROM net_cidrs c1, net_cidrs c2, net c3
            WHERE c1.net_id=UNHEX(?)
            AND c1.begin >= c2.begin AND c1.end <= c2.end AND c1.net_id <> c2.net_id
            AND c2.net_id = c3.id $perms_where";

        $params = array($id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }


        if ($rs->RecordCount() >= $num_cidrs)
        {
            return TRUE;
        }

        return FALSE;
    }


    /**
    * This function returns the subnets that belongs to network
    *
    * @param object  $conn   Database access object
    * @param string  $id     Net ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_my_subnets($conn, $id)
    {
        Ossim_db::check_connection($conn);

        $nets = array();

        $query = 'SELECT HEX(n1.net_id) AS net_id, n1.cidr, INET6_NTOA(n1.end) as end, INET6_NTOA(n1.begin) as begin
            FROM net ne, net_cidrs n, net ne1, net_cidrs n1
            WHERE ne.id = n.net_id AND ne1.id = n1.net_id
            AND n.begin <= n1.begin
            AND n1.end <= n.end AND ne.ctx = ne1.ctx
            AND n.net_id = UNHEX(?) AND n1.net_id != UNHEX(?);';

        $params = array($id, $id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $nets[$rs->fields['net_id']] = array(
              'id'    => $rs->fields['net_id'],
              'ips'   => $rs->fields['cidr'],
              'begin' => $rs->fields['begin'],
              'end'   => $rs->fields['end']
            );

            $rs->MoveNext();
        }

        return $nets;
    }


    /**
    * This function returns the subnets ids that belongs to network, including itself
    *
    * @param object  $conn   Database access object
    * @param array   $nets   Net Array
    *
    * @throws Exception  If a connection error occurred
    *
    * @return string
    */
    public static function get_subnets($conn, $nets)
    {
        Ossim_db::check_connection($conn);

        $subnets = array();

        if (!is_array($nets))
        {
            $nets = array($nets);
        }

        foreach ($nets as $id)
        {
            $subnets[$id] = $id;
            $snets        = self::get_my_subnets($conn, $id);

            foreach ($snets as $nid => $sns)
            {
                $subnets[$nid] = $nid;
            }
        }

        return implode(',',array_keys($subnets));
    }


    /**
    * This function returns the name from net
    *
    * @param object  $conn   Database access object
    * @param string  $id     Net ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return string
    */
    public static function get_name_by_id($conn, $id)
    {
        Ossim_db::check_connection($conn);

        $name = NULL;

        $query  = 'SELECT name FROM net WHERE id = UNHEX(?)';
        $params = array($id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if ($rs->fields['name'] != '')
        {
            $name = $rs->fields['name'];
        }

        return $name;
    }


    /**
    * This function returns the Net ID from net name
    *
    * @param object  $conn   Database access object
    * @param string  $name   Net name
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_id_by_name($conn, $name)
    {
        Ossim_db::check_connection($conn);


        $net_ids = array();

        $query = 'SELECT HEX(id) AS id, HEX(ctx) AS ctx FROM net WHERE name = ?';

        $params = array($name);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $net_ids[$rs->fields['id']] = $rs->fields['ctx'];

            $rs->MoveNext();
        }

        return $net_ids;
    }


    /**
    * This function returns the context from allowed net
    *
    * @param object  $conn   Database access object
    * @param string  $id     Net ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return string
    */
    public static function get_ctx_by_id($conn, $id)
    {
        Ossim_db::check_connection($conn);

        $ctx = '';

        $query  = 'SELECT HEX(ctx) AS ctx FROM net WHERE id = UNHEX(?)';
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
    * This function returns the Net ID from CIDRs and context
    *
    * @param object  $conn   Database access object
    * @param string  $ips    Comma-separated CIDRs
    * @param string  $ctx    [Optional] Net context
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_id_by_ips($conn, $ips, $ctx = '')
    {
        Ossim_db::check_connection($conn);

        $net_ids = array();

        if (!empty($ips))
        {
            $ips_list  = explode(',', $ips);
            sort($ips_list);
            $ips_list  = array_map('trim', $ips_list);

            $ips_where = "'".implode("','", $ips_list)."'";
        }
        else
        {
            return $net_ids;
        }

        $perms_where = self::get_perms_where('net.', TRUE);

        $params = array();

        $query = "SELECT DISTINCT HEX(net_id) AS id, HEX(net.ctx) AS ctx, net.ips,
            CONV(HEX(begin),16,10) AS begind, CONV(HEX(end), 16, 10) AS endd
            FROM net_cidrs,net
            WHERE net.id = net_cidrs.net_id $perms_where AND cidr IN ($ips_where)";

        if ($ctx != '')
        {
            $query .= ' AND ctx = UNHEX(?)';
            $params[]   = $ctx;
        }

        $query .= ' ORDER BY endd-begind';

        $rs = $conn->Execute ($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $ips_2 = explode(',', $rs->fields['ips']);
            sort($ips_2);

            if ($ips_list === $ips_2)
            {
                $net_ids[$rs->fields['id']][$rs->fields['ctx']][$rs->fields['ips']] = $rs->fields['ips'];
            }

            $rs->MoveNext();
        }

        return $net_ids;
    }


    /**
    * This function returns the netname/s from CIDR/Ip
    *
    * @param object  $conn   Database access object
    * @param string  $ips    CIDRs
    * @param string  $ctx    [Optional] Net context
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_name_by_ips($conn, $ips, $ctx = '')
    {
        Ossim_db::check_connection($conn);

        $names   = array();

        if (!empty($ips))
        {
            $ips = explode(',', $ips);
            sort($ips);
            $ips  = array_map('trim', $ips);
        }
        else
        {
            return $names;
        }

        $first_ip = $ips[0];

        $query   = 'SELECT name, ips, HEX(id) AS id FROM net WHERE (ips LIKE ? OR ips LIKE ? OR ips LIKE ? OR ips = ?)';

        $params  = array($first_ip . ',%', '%,' . $first_ip . ',%', '%,' . $first_ip, $first_ip);

        if (!empty($ctx))
        {
            $query    .= ' AND ctx = UNHEX(?)';
            $params[]  = $ctx;
        }

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $ips_2 = explode(',', $rs->fields['ips']);
            sort($ips_2);

            if ($ips === $ips_2)
            {
                $names[$rs->fields['id']] = $rs->fields['name'];
            }

            $rs->MoveNext();
        }

        return $names;
    }

    /**
    * This function returns the CIDRs from net
    *
    * @param object  $conn   Database access object
    * @param string  $id     Net ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return string
    */
    public static function get_ips_by_id($conn, $id)
    {
        Ossim_db::check_connection($conn);

        $ips = NULL;

        $query  = 'SELECT ips FROM net WHERE id = UNHEX(?)';
        $params = array($id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if ($rs->fields['ips'] != '')
        {
            $ips = preg_replace("/[\r\n\t]+/", '', $rs->fields['ips']);
        }

        return $ips;
    }


    /**
    * This function returns true if CIDRs could be scanned using sensor $sensor_id
    *
    * @param object  $conn       Database access object
    * @param string  $ips        Comma-separated CIDRs
    * @param string  $sensor_id  Sensor ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean
    */
    public static function check_cidr_by_sensor($conn, $ips, $sensor_id)
    {
        Ossim_db::check_connection($conn);

        $cidrs = explode(',', $ips);

        // Permissions
        $perms_where = self::get_perms_where('net.', TRUE);

        if (is_array($cidrs) && !empty($cidrs))
        {
            foreach ($cidrs as $cidr)
            {
                $query = "SELECT HEX(net_cidrs.net_id) AS id, CONV(HEX(begin),16,10) AS begind, CONV(HEX(end), 16, 10) AS endd
                    FROM net_cidrs, net, net_sensor_reference
                    WHERE net.id = net_sensor_reference.net_id
                    AND net.id = net_cidrs.net_id
                    $perms_where
                    AND INET6_ATON(?) >= net_cidrs.begin
                    AND INET6_ATON(?) <= net_cidrs.end
                    AND net_sensor_reference.sensor_id = UNHEX(?)
                    ORDER BY endd-begind";


                $ip_range = self::expand_cidr(trim($cidr), 'SHORT', 'IP');

                $params = array($ip_range[$cidr][0], $ip_range[$cidr][1], $sensor_id);

                $rs = $conn->Execute($query, $params);

                if (!$rs)
                {
                    Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
                }

                if ($rs->RecordCount() < 1)
                {
                    return FALSE;
                }
            }
        }

        return TRUE;
    }


    /**
    * This function returns list with nets associated to sensor $sensor_id
    *
    * @param object  $conn       Database access object
    * @param string  $sensor_id  Sensor ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_nets_by_sensor($conn, $sensor_id)
    {
        Ossim_db::check_connection($conn);

        $nets = array();

        // Permissions
        $perms_where = self::get_perms_where('net.', TRUE);

        $query = "SELECT DISTINCT net.*, HEX(net.id) AS id, HEX(net.ctx) AS ctx
            FROM net, net_sensor_reference
            WHERE net.id = net_sensor_reference.net_id
            AND net_sensor_reference.sensor_id = UNHEX(?) $perms_where";

        $params = array($sensor_id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $nets[$rs->fields['id']] = array (
                'ctx'         => $rs->fields['ctx'],
                'name'        => $rs->fields['name'],
                'ips'         => $rs->fields['ips'],
                'descr'       => $rs->fields['descr'],
                'icon'        => $rs->fields['icon'],
                'external'    => $rs->fields['external_net'],
                'asset_value' => $rs->fields['asset']
            );

            $rs->MoveNext();
        }

        return $nets;
    }


    /**
    * This function returns a filtered net list (For trees and autocomplete widget)
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
    public static function get_list_tree($conn, $tables = '', $filters = array(), $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        $nets  = array();

        //Build SQL

        $perms_where = self::get_perms_where('net.', TRUE);

        $q_where  = 'WHERE 1=1 '.$perms_where;


        if (!empty($filters['where']))
        {
            $q_where  .= 'AND '.$filters['where'];
        }

        if (!empty($filters['order_by']))
        {
            $q_where  .= ' ORDER BY '.$filters['order_by'];
        }

        if (!empty($filters['limit']))
        {
            $offset = isset($filters['offset']) && $filters['offset'] ? $filters['offset'] : 0;
            $q_where  .= "LIMIT $offset,{$filters['limit']}";
        }

        $query = ossim_query("SELECT DISTINCT net.name, net.ips, HEX(net.id) AS n_id, HEX(net.ctx) AS n_ctx
                FROM net $tables
                $q_where");

        $rs = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $nets[] = array (
                $rs->fields['n_id'],
                $rs->fields['n_ctx'],
                $rs->fields['ips'],
                $rs->fields['name']
            );

            $rs->MoveNext();
        }

        return $nets;
    }


    /**
    * This function returns all nets
    *
    * @param object  $conn   Database access object
    * @param boolean $cache  [Optional] Use cached information
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_all($conn, $cache = FALSE)
    {
        $nets  = array();

        // First count to do block requests
        $query = 'SELECT count(id) AS total FROM net';

        $conn->SetFetchMode(ADODB_FETCH_ASSOC);

        $rf    = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);

        if (!$rf)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }


        $foundrows = $rf->fields['total'];

        $block = 2000;
        $rf->Free();


        for ($i = 0; $i <= $foundrows; $i += $block)
        {
            $query = "SELECT *, HEX(ctx) AS n_ctx, HEX(id) AS n_id
                FROM net
                ORDER BY name ASC
                LIMIT $i, $block";

            $rs = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }

            while (!$rs->EOF)
            {
                $nets[$rs->fields['n_id']] = array (
                    'ctx'         => $rs->fields['n_ctx'],
                    'name'        => $rs->fields['name'],
                    'ips'         => $rs->fields['ips'],
                    'descr'       => $rs->fields['descr'],
                    'icon'        => $rs->fields['icon'],
                    'external'    => $rs->fields['external_net'],
                    'asset_value' => $rs->fields['asset'],
                    'owner'       => $rs->fields['owner']
                );

                $rs->MoveNext();
            }

           $rs->Free();
        }

        return $nets;
    }


    /**
    * This function returns a filtered net list
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

        $nets  = array();

        $total = 0;


        //Build SQL

        $perms_where = self::get_perms_where('net.', TRUE);

        $q_select = 'net.*';
        $q_where  = 'WHERE 1=1 '.$perms_where;


        if (!empty($filters['where']))
        {
            $q_where  .= 'AND '.$filters['where'];
        }

        if (!empty($filters['order_by']))
        {
            $q_where  .= ' ORDER BY '.$filters['order_by'];
        }

        if (!empty($filters['limit']))
        {
            $q_select  = 'SQL_CALC_FOUND_ROWS net.*';
            $q_where  .= ' LIMIT '.$filters['limit'];
        }

        $conn->SetFetchMode(ADODB_FETCH_ASSOC);

        // Has LIMIT
        if (!empty($filters['limit']))
        {
            $query = "SELECT DISTINCT $q_select, HEX(net.id) AS n_id, HEX(net.ctx) AS n_ctx
                FROM net $tables $q_where";

            //echo $query;

            $rs = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }

            $total = Ossim_db::get_found_rows($conn, $query, $cache);

            while (!$rs->EOF)
            {
                $id = $rs->fields['n_id'];

                $nets[$id] = array(
                    'id'          => $id,
                    'ctx'         => $rs->fields['n_ctx'],
                    'name'        => $rs->fields['name'],
                    'ips'         => $rs->fields['ips'],
                    'descr'       => $rs->fields['descr'],
                    'icon'        => $rs->fields['icon'],
                    'external'    => $rs->fields['external_net'],
                    'asset_value' => $rs->fields['asset'],
                    'owner'       => $rs->fields['owner']
                );

                $rs->MoveNext();
            }
        }
        else
        {
            $counter_name = ($cache) ? 'total_'.md5($query) : 'total';

            // First count to do block requests
            $query = ossim_query("SELECT count(DISTINCT net.id) AS $counter_name FROM net $tables $q_where");

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
                $query = ossim_query("SELECT DISTINCT $q_select, HEX(net.id) AS n_id, HEX(net.ctx) AS n_ctx FROM net $tables $q_where LIMIT $i, $block");

                $rs = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);

                if (!$rs)
                {
                    Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
                }

                while (!$rs->EOF)
                {
                    $id = $rs->fields['n_id'];

                    $nets[$id] = array(
                        'id'          => $id,
                        'ctx'         => $rs->fields['n_ctx'],
                        'name'        => $rs->fields['name'],
                        'ips'         => $rs->fields['ips'],
                        'descr'       => $rs->fields['descr'],
                        'icon'        => $rs->fields['icon'],
                        'external'    => $rs->fields['external_net'],
                        'asset_value' => $rs->fields['asset'],
                        'owner'       => $rs->fields['owner']
                    );

                    $rs->MoveNext();
                }

                $rs->Free();
            }
        }

        return array($nets, $total);
    }
}

/* End of file asset_net.inc */
/* Location: ../include/classes/asset_net.inc */
