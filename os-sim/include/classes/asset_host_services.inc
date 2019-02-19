<?php
/**
* asset_host_services.inc
*
* File asset_host_services.inc is used to:
*   - To manage host services
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
* Asset_host_services Class
*
* Class for managing host services
*
* @package    ossim-framework\Asset
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/

class Asset_host_services
{
    /**
    * Host ID
    *
    * @var string
    * @access private
    */
    private $host_id;

    /**
    * Services associated with the host
    *
    * @var array
    * @access private
    */
    private $services;

    /**
    * All protocols
    *
    * @var array
    * @access private
    */
    private $protocols;


    /**
    * Class constructor
    *
    * This function sets up the class
    *
    * @param string  $host_id   Host ID
    */
    public function __construct($host_id)
    {
        $this->set_host_id($host_id);
        $this->set_protocols();

        $this->services = array();
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
    * This function returns all services associated with the host
    *
    * @return array
    */
    public function get_services()
    {
        return $this->services;
    }


    /**
    * This function returns the service associated with IP, port and protocol from host
    *
    * @param string  $ip        Host IP
    * @param integer $port      [Optional] Port
    * @param integer $protocol  [Optional] Protocol
    *
    * @return array
    */
    public function get_service($ip, $port = '', $protocol = '' )
    {
        $service = array();

        $cnd_1 = @array_key_exists($ip, $this->services);
        $cnd_2 = $port != '' && @array_key_exists($port, $this->services[$ip]);
        $cnd_3 = $protocol != '' && @array_key_exists($protocol, $this->services[$ip][$port]);

        if ($cnd_1 && $cnd_2 && $cnd_3)
        {
            $service = $this->services[$ip][$port][$protocol];
        }
        elseif ($cnd_1 && $cnd_2)
        {
            $service = $this->services[$ip][$port];
        }
        elseif ($cnd_1)
        {
            $service = $this->services[$ip];
        }

        return $service;
    }


    /**
    * This function returns all protocols available
    *
    * @return array
    */
    public function get_protocols()
    {
        return $this->protocols;
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
            $exp_msg = _('Error! Asset ID is not valid');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
    }


    /**
    * This function sets host services
    *
    * @param array $services  Host services
    *
    * @return void
    */
    public function set_services($services)
    {
        $this->services = array();

        if (is_array($services) && !empty($services))
        {
            foreach($services as $s_data)
            {
                $this->add_new($s_data);
            }
        }
    }


    /**
    * This function sets the host services from database
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

        $query = 'SELECT host_services.*, inet6_ntoa(host_services.host_ip) AS ip, host_source_reference.name as source_name,
            HEX(host_services.host_id) AS host_id FROM host_services
            LEFT JOIN host_source_reference ON host_services.source_id = host_source_reference.id
            WHERE host_id = UNHEX(?) ORDER BY last_modified DESC';

        $params = array($this->host_id);

        $rs = ($cache == TRUE) ? $conn->CacheExecute($query, $params) : $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $s_data['ip']            = $rs->fields['ip'];
            $s_data['service']       = $rs->fields['service'];
            $s_data['port']          = $rs->fields['port'];
            $s_data['nagios']        = $rs->fields['nagios'];
            $s_data['nagios_status'] = $rs->fields['nagios_status'];
            $s_data['protocol']      = $rs->fields['protocol'];
            $s_data['version']       = $rs->fields['version'];
            $s_data['source_id']     = $rs->fields['source_id'];
            $s_data['source_name']   = $rs->fields['source_name'];
            $s_data['date']          = $rs->fields['last_modified'];

            $this->add_new($s_data);

            $rs->MoveNext();
        }
    }


    /**
    * This function adds a new service to host
    *
    * @param array  $s_data  Service data
    *
    * @return boolean
    */
    public function add_new($s_data)
    {
        settype($s_data['port'], 'integer');
        settype($s_data['protocol'], 'integer');

        $cnd_1 = Asset_host_ips::valid_ip($s_data['ip']);
        $cnd_2 = $s_data['port'] >= 0 && $s_data['port'] <= 65535;
        $cnd_3 = array_key_exists($s_data['protocol'], $this->protocols);

        if (!$cnd_1 || !$cnd_2 || !$cnd_3)
        {
            return FALSE;
        }

        $this->services[$s_data['ip']][$s_data['port']][$s_data['protocol']] = array(
            'port'          => $s_data['port'],
            'protocol'      => $s_data['protocol'],
            'service'       => $s_data['service'],
            'nagios'        => array(
                'enabled' => $s_data['nagios'],
                'status'  => $s_data['nagios_status']
            ),
            'version'       => $s_data['version'],
            'source' => array(
                'id'   => $s_data['source_id'],
                'name' => $s_data['source_name'],
            ),
            'date' => $s_data['date']
        );

        return TRUE;
    }


    /**
    * This function deletes services from the host
    *
    * @param string    $ip       Host IP
    * @param integer   $port     [Optional] Service port
    * @param integer   $protocol [Optional] Service protocol
    *
    * @return boolean
    */
    public function delete($ip, $port = '', $protocol = '')
    {
        $cnd_1 = @array_key_exists($ip, $this->services);
        $cnd_2 = $port != '' && @array_key_exists($port, $this->services[$ip]);
        $cnd_3 = $protocol != '' && @array_key_exists($protocol, $this->services[$ip][$port]);

        if ($cnd_1 && $cnd_2 && $cnd_3)
        {
            unset($this->services[$ip][$port][$protocol]);

            if (!count($this->services[$ip][$port]))
            {
                unset($this->services[$ip][$port]);
                if (!count($this->services[$ip]))
                {
                    unset($this->services[$ip]);
                }
            }

            return TRUE;
        }
        elseif ($cnd_1 && $cnd_2)
        {
            unset($this->services[$ip][$port]);

            if (!count($this->services[$ip]))
            {
                unset($this->services[$ip]);
            }

            return TRUE;
        }
        elseif ($cnd_1)
        {
            unset($this->services[$ip]);

            return TRUE;
        }

        return FALSE;
    }


    /**
    * This function saves all services into database
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

        self::delete_all_from_db($conn, $this->get_host_id());

        foreach($this->services as $s_ip => $_aux_1)
        {
            foreach($_aux_1 as $s_port => $_aux_2)
            {
                foreach($_aux_2 as $s_protocol => $s_data)
                {
                    $query = 'REPLACE INTO host_services (host_id, host_ip, port, protocol, service, version, last_modified, source_id, nagios)
                        VALUES (UNHEX(?), INET6_ATON(?), ?, ?, ?, ?, ?, ?, ?)';

                    $params = array(
                        $this->host_id,
                        $s_ip,
                        $s_port,
                        $s_protocol,
                        $s_data['service'],
                        $s_data['version'],
                        gmdate('Y-m-d H:i:s'),
                        $s_data['source']['id'],
                        $s_data['nagios']['enabled']
                    );

                    $conn->Execute($query, $params);
                }
            }
        }

        //Finish transaction
        if (!$conn->CompleteTrans())
        {
            $exp_msg = _('Error! Asset services could not be saved');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return TRUE;
    }


    /**
    * This function sets all protocols from database
    *
    * @return void
    */
    private function set_protocols()
    {
        $protocols = Protocol::get_list();

        $this->protocols = array();

        if (is_array($protocols) && !empty($protocols))
        {
            $this->protocols = $protocols;
        }
    }



    /*************************************************
     **************** Static functions ***************
     *************************************************/



    /**
    * This function saves service into database
    *
    * @param object   $conn            Database access object
    * @param string   $host_id         Host ID
    * @param string   $s_data          Service data
    * @param boolean  $report_changes  [Optional] Report changes to other components
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean
    */
    public static function save_service_in_db($conn, $host_id, $s_data, $report_changes = FALSE)
    {
        Ossim_db::check_connection($conn);

        //Check if we can add/update the services
        if (Asset_host::is_allowed($conn, $host_id) != TRUE)
        {
            $exp_msg = _("Error! Asset does not exists or you do not have the correct permissions");

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $host_service = new self($host_id);

        if ($host_service->add_new($s_data) == TRUE)
        {
            unset($host_service);

            $query = 'REPLACE INTO host_services (
                        host_id,
                        host_ip,
                        port,
                        protocol,
                        service,
                        version,
                        last_modified,
                        source_id,
                        nagios)
                      VALUES (UNHEX(?), INET6_ATON(?), ?, ?, ?, ?, ?, ?, ?)';

            $params = array(
                $host_id,
                $s_data['ip'],
                $s_data['port'],
                $s_data['protocol'],
                $s_data['service'],
                $s_data['version'],
                gmdate('Y-m-d H:i:s'),
                $s_data['source_id'],
                $s_data['nagios']
            );

            $rs = $conn->Execute($query, $params);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
        }

        if ($report_changes == TRUE)
        {
            try
            {
                Asset_host::report_changes($conn, 'hosts');
            }
            catch(Exception $e)
            {
                Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
            }
        }

        return TRUE;
    }


    /**
    * This function deletes one service from database
    *
    * @param object   $conn            Database access object
    * @param string   $host_id         Host ID
    * @param string   $ip              [Optional] Host IP
    * @param integer  $port            [Optional] Port service
    * @param boolean  $report_changes  [Optional] Report changes to other components
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean
    */
    public static function delete_service_from_db($conn, $host_id, $ip = '', $port = '', $protocol = '', $report_changes = FALSE)
    {
        Ossim_db::check_connection($conn);

        //Check if we can delete the sensor
        if (Asset_host::is_allowed($conn, $host_id) != TRUE)
        {
            $exp_msg = _("Error! Asset does not exists or you do not have the correct permissions");

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }


        $query = 'DELETE FROM host_services WHERE host_id = UNHEX(?)';
        $params = array($host_id);

        if ($ip != '')
        {
            $query   .= ' AND host_ip = INET6_ATON(?)';
            $params[] = $ip;
        }

        if ($port != '')
        {
            $query   .= ' AND port = ?';
            $params[] = $port;
        }

        if ($protocol != '')
        {
            $query   .= ' AND protocol = ?';
            $params[] = $protocol;
        }

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if ($report_changes == TRUE)
        {
            try
            {
                Asset_host::report_changes($conn, 'hosts');
            }
            catch(Exception $e)
            {
                Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
            }
        }

        return TRUE;
    }


    /**
    * This function deletes all services from database
    *
    * @param object   $conn            Database access object
    * @param string   $host_id         Host ID
    * @param boolean  $report_changes  [Optional] Report changes to other components
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean
    */
    public static function delete_all_from_db($conn, $host_id, $report_changes = FALSE)
    {
        Ossim_db::check_connection($conn);

        //Check if we can delete the sensor
        if (Asset_host::is_allowed($conn, $host_id) != TRUE)
        {
            $exp_msg = _("Error! Asset does not exists or you do not have the correct permissions");

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $query  = 'DELETE FROM host_services WHERE host_id = UNHEX(?)';
        $params = array ($host_id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if ($report_changes == TRUE)
        {
            try
            {
                Asset_host::report_changes($conn, 'hosts');
            }
            catch(Exception $e)
            {
                Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
            }
        }

        return TRUE;
    }


    /**
    * This function returns all services from database (Grouped by service)
    *
    * @param object  $conn   Database access object
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_all($conn)
    {
        Ossim_db::check_connection($conn);

        $services = array();

        // Permissions
        $perms_where = Asset_host::get_perms_where('h.', TRUE);


        $from        = ( $perms_where != '' ) ? 'host h, host_services' : 'host_services';
        $perms_where = ( $perms_where != '' ) ? "WHERE host_services.host_id = h.id $perms_where" : '';


        $query = "SELECT host_services.*, host_source_reference.name AS source_name
            FROM $from
            LEFT JOIN host_source_reference ON host_services.source_id = host_source_reference.id
            $perms_where
            GROUP BY host_services.port, host_services.protocol, host_services.service";


        $rs = $conn->Execute($query);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $p_name = Protocol::get_protocol_by_number($rs->fields['protocol']);

            if (FALSE === $p_name)
            {
                $p_name = 'unknown';
            }

            $services[] = array(
                'port' => $rs->fields['port'],
                'protocol' => array(
                    'id'   => $rs->fields['protocol'],
                    'name' => $p_name
                ),
                'version' => $rs->fields['version'],
                'nagios'  => array(
                    'enabled' => $rs->fields['nagios'],
                    'status'  => $rs->fields['nagios_status']
                ),
                'service' => $rs->fields['service'],
                'source'  => array(
                    'id'   => $rs->fields['source_id'],
                    'name' => $rs->fields['source_name'],
                 ),
                'date' => $rs->fields['last_modified']
            );

            $rs->MoveNext();
        }

        return $services;
    }


    /**
    * This function returns the vulnerabilities found in vuln_nessus_latest_results table
    * related to service/port pair
    *
    * @param object   $conn      Database access object
    * @param array    $ips       Array of hosts or CIDRs
    * @param array    $ctx       Host Context or Net Context
    * @param string   $service   Service
    * @param integer  $port      Service port
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_vulns_by_service($conn, $ips, $ctx, $service, $port)
    {
        Ossim_db::check_connection($conn);

        $vulnerabilities = array();

        if (is_array($ips))
        {
            foreach ($ips as $cidr)
            {
                $cidr = (Asset_host_ips::valid_ip($cidr)) ? $cidr.'/32' : $cidr;

                $ranges = Cidr::expand_cidr(trim($cidr));

                if (!is_array($ranges))
                {
                    $exp_msg = _('Error! IP/CIDR format is not valid');

                    Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
                }

                $query = 'SELECT * FROM vuln_nessus_latest_results
                    WHERE INET_ATON(hostIP) >= ?
                    AND INET_ATON(hostIP) <= ?
                    AND ctx = UNHEX(?)
                    AND service LIKE ?
                    AND port = ?';

                $params = array($ranges[0], $ranges[1], $ctx, "%".$service."%", $port);

                $conn->SetFetchMode(ADODB_FETCH_ASSOC);

                $rs = $conn->Execute($query, $params);

                if (!$rs)
                {
                    Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
                }

                while (!$rs->EOF)
                {
                    $vulnerabilities[] = array(
                        'risk' => $rs->fields['risk'],
                        'msg'  => $rs->fields['msg']
                    );

                    $rs->MoveNext();
                }
            }
        }

        return $vulnerabilities;
    }


    /**
    * This function returns a filtered services list
    *
    * @param object   $conn     Database access object
    * @param array    $filters  [Optional] SQL statements (WHERE, LIMIT, ORDER BY ...)
    * @param boolean  $cache    [Optional] Use cached information
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_list($conn, $filters = array(), $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        $services = array();

        //Build SQL

        $perms_where = Asset_host::get_perms_where('h.', TRUE);

        $q_where  = $perms_where;
        $q_select = '';


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
            $q_where  .= ' LIMIT '.$filters['limit'];
            $q_select  = ' SQL_CALC_FOUND_ROWS ';
        }


        $conn->SetFetchMode(ADODB_FETCH_ASSOC);

        $query = "SELECT $q_select host_services.*, HEX(h.id) AS h_id, inet6_ntoa(host_services.host_ip) AS ip,
            host_source_reference.name AS source_name, host_source_reference.relevance AS source_relevance, software_cpe.line AS cpe
            FROM host h, host_services
            LEFT JOIN host_source_reference ON host_services.source_id = host_source_reference.id
            LEFT JOIN software_cpe ON host_services.version = software_cpe.cpe
            WHERE host_services.host_id = h.id $q_where";


        $rs = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!empty($filters['limit']))
        {
            $total = Ossim_db::get_found_rows($conn, $query, $cache);
        }
        else
        {
            $total = $rs->RecordCount();
        }

        while (!$rs->EOF)
        {
            $p_name = Protocol::get_protocol_by_number($rs->fields['protocol']);

            if (FALSE === $p_name)
            {
                $p_name = 'unknown';
            }

            $s_key = $rs->fields['ip'].'-'.$rs->fields['port'].'-'.$rs->fields['protocol'];
            
            $_service = array(
                'ip'    => $rs->fields['ip'],
                'cpe'   => $rs->fields['cpe'],
                'port'  => $rs->fields['port'],
                'protocol' => array(
                    'id'   => $rs->fields['protocol'],
                    'name' => $p_name
                ),
                'version' => $rs->fields['version'],
                'nagios'  => array(
                    'enabled' => $rs->fields['nagios'],
                    'status'  => $rs->fields['nagios_status']
                ),
                'service' => $rs->fields['service'],
                'source'  => array(
                    'id'   => $rs->fields['source_id'],
                    'name' => $rs->fields['source_name'],
                ),
                'date' => $rs->fields['last_modified'],
                'relevance' => $rs->fields['source_relevance']
            );
            
            if ($filters['response_type'] == 'by_service')
            {
                $services[$s_key][$rs->fields['h_id']] = $_service;
            }
            else
            {
                $services[$rs->fields['h_id']][$s_key] = $_service;
            }

            $rs->MoveNext();
        }

        return array($services, $total);
    }


    /**
    * This function returns a list with the available services in the system
    *
    * @param object   $conn     Database access object
    * @param array    $filters  [Optional] SQL statements (WHERE, LIMIT, ORDER BY ...)
    * @param boolean  $cache    [Optional] Use cached information
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_services_available($conn, $filters = array(), $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        $services = array();
        $total    = 0;

        $q_select = '';
        $q_where  = '';

        if ($filters['where'] != '')
        {
            $q_where = ' AND '.$filters['where'];
        }

        if ($filters['order_by'] != '')
        {
            $q_where .= ' ORDER BY '.$filters['order_by'];
        }

        if ($filters['limit'] != '')
        {
            $q_select = ' SQL_CALC_FOUND_ROWS ';
            $q_where .= ' LIMIT '.$filters['limit'];
        }

        $query = "SELECT DISTINCT $q_select s.port, s.protocol, s.service
                        FROM host_services s
                        WHERE s.port > 0 $q_where;";

        $rs = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!empty($filters['limit']))
        {
            $total = Ossim_db::get_found_rows($conn, $query, $cache);
        }
        else
        {
            $total = $rs->RecordCount();
        }

        //Protocol list
        $protocol_list = Protocol::get_list();

        while (!$rs->EOF)
        {
            $serv = array();
            $protocol_name = $protocol_list[$rs->fields['protocol']]['name'];

            $serv['port']      = $rs->fields['port'];
            $serv['protocol']  = $rs->fields['protocol'];
            $serv['prot_name'] = (!empty($protocol_name)) ? $protocol_name : 'unknown';
            $serv['service']   = $rs->fields['service'];

            $services[] = $serv;

            $rs->MoveNext();
        }

        return array($services, $total);
    }


    /**
    * This function enable/disable Nagios by service
    *
    * @param object   $conn            Database access object
    * @param string   $host_id         Host ID
    * @param integer  $nagios          Enable/disable Nagios (0 or 1)
    * @param string   $ip              [Optional] Host IP
    * @param integer  $port            [Optional] Port service
    * @param boolean  $report_changes  [Optional] Report changes to other components
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean
    */
    public static function toggle_nagios($conn, $host_id, $nagios, $ip = '', $port = '', $protocol = '', $report_changes = FALSE)
    {
        Ossim_db::check_connection($conn);

        //Check if we can delete the sensor
        if (Asset_host::is_allowed($conn, $host_id) != TRUE)
        {
            $exp_msg = _("Error! Asset does not exists or you do not have the correct permissions");

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $query = 'UPDATE host_services SET nagios = ? WHERE host_id = UNHEX(?)';

        $params = array(intval($nagios), $host_id);

        if ($ip != '')
        {
            $query   .= ' AND host_ip = INET6_ATON(?)';
            $params[] = $ip;
        }

        if ($port != '')
        {
            $query   .= ' AND port = ?';
            $params[] = $port;
        }

        if ($protocol != '')
        {
            $query   .= ' AND protocol = ?';
            $params[] = $protocol;
        }

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if ($report_changes == TRUE)
        {
            try
            {
                Asset_host::report_changes($conn, 'hosts');
            }
            catch(Exception $e)
            {
                Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
            }
        }

        return TRUE;
    }


    /**
    * This function translates Nagios code to text
    *
    * @param integer  $nagios_code  Nagios Code
    *
    * @return string
    */
    public static function get_nagios_status_by_code($nagios_code)
    {
        $nagios_code = intval($nagios_code);

        $nagios_codes = array(
            '0' => _('OK'),
            '1' => _('WARNING'),
            '2' => _('CRITICAL')
        );

        $nagios_status = '-';

        if (array_key_exists($nagios_code, $nagios_codes))
        {
            $nagios_status = $nagios_codes[$nagios_code];
        }

        return $nagios_status;
    }
}

/* End of file asset_host_services.inc */
/* Location: ../include/classes/asset_host_services.inc */
