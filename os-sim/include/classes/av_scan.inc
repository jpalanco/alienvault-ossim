<?php
/**
* av_scan.inc
*
* File av_scan.inc is used to:
*   - To manage asset scans
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
* @package    ossim-framework\Assets
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


/**
* Av_scan Class
*
* Class to manage asset scans
*
* @package    ossim-framework\Asset
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/


class Av_scan
{
    /**#@+
     * Scan status sonstants
     */
    const ST_IDLE = 0;

    const ST_SEARCHING_HOSTS = 1;

    const ST_SEARCH_FINISHED = 2;

    const ST_SCANNING_HOSTS = 3;

    const ST_SCAN_FINISHED = 4;

    const ST_FAILED = 5;


    /**
     * Targets to scan
     *
     * @var array
     * @access private
     */
    private $targets;


    /**
     * Scan options (Scan type, Timing template, ...)
     *
     * @var array
     * @access private
     */
    private $scan_options;


    /**
     * Scanning sensor which will launch a scan
     *
     * @var string
     * @access private
     */
    private $sensor;


    /**
     * Job ID related to asset scan
     *
     * @var string
     * @access private
     */
    private $scan_id;


    /**
     * Class constructor
     *
     * This function sets up the class
     *
     * @throws Exception  If an error occurred
     *
     * @param string  $target_source   Target source (file or input string)
     * @param string  $sensor          [Optional] Sensor which launches the scan (Sensor ID, local or auto)
     * @param array   $scan_options    [Optional] Scan options
     */
    public function __construct($target_source, $sensor = 'local', $scan_options = array())
    {
        $this->_check_user_session();

        $this->set_targets($target_source);

        $this->set_scanning_sensor($sensor);

        $this->set_scan_options($scan_options);

        $this->_set_scan_id(NULL);
    }



    /***********************************************************
     ********************* Public functions ********************
     ***********************************************************/


    /**
     * This function sets targets to scan fron input string or file
     *
     * @param array  $target_input  String with targets to scan
     *
     * @throws Exception  If an error occurred
     *
     * @return void
     */
    public function set_targets($target_source)
    {
        if (empty($target_source))
        {
            $e_msg = _('Error! Target source not found');

            Av_exception::throw_error(Av_exception::USER_ERROR, $e_msg);
        }

        if (preg_match('/^target_file:(.*)/', $target_source, $match))
        {
            $target_source = $match[1];

            self::_set_targets_from_file($target_source);
        }
        else
        {
            self::_set_targets_from_input($target_source);
        }
    }


    /**
     * This function sets up a scanning sensor
     *
     * @param string  $sensor  [Optional] Scanning sensor: Sensor ID, local or auto
     *
     * @throws Exception  If an error occurred
     *
     * @return void
     */
    public function set_scanning_sensor($sensor = 'local')
    {
        $db   = new Ossim_db();
        $conn = $db->connect();

        $scanning_sensor = NULl;

        try
        {
            //Getting all scanning sensors available
            $scanning_sensors = self::get_scanning_sensors();

            if ($sensor == 'local' || valid_hex32($sensor))
            {
                if ($sensor == 'local')
                {
                    $sensor = Av_sensor::get_default_sensor($conn);
                }

                if (array_key_exists($sensor, $scanning_sensors))
                {
                    $scanning_sensor = $sensor;
                }
            }
            elseif ($sensor == 'auto')
            {
                // Getting scanning sensor based on targets (Automatic mode)
                foreach($this->targets['av_format'] as $target)
                {
                    if ($target['type'] == 'net')
                    {
                        if (!empty($target['id']))
                        {
                            //Net with ID
                            $rs_by_asset     = Asset_net_sensors::get_sensors_by_id($conn, $target['id']);
                            $scanning_sensors = array_intersect_key($scanning_sensors, $rs_by_asset);
                        }
                        else
                        {
                            //Net without ID (Getting closest inventoried net)
                            $closest_nets = Asset_net::get_closest_nets($conn, $target['ip_cidr']);

                            if (is_array($closest_nets) && !empty($closest_nets))
                            {
                                $cn_key      = key($closest_nets);
                                $net_id      = $closest_nets[$cn_key]['id'];

                                $rs_by_asset = Asset_net_sensors::get_sensors_by_id($conn, $net_id);
                            }
                            else
                            {
                                $rs_by_asset = array();
                            }

                            $scanning_sensors = array_intersect_key($scanning_sensors, $rs_by_asset);
                        }
                    }
                    elseif ($target['type'] == 'asset')
                    {
                        if (!empty($target['id']))
                        {
                            //Host with ID
                            $rs_by_asset     = Asset_host_sensors::get_sensors_by_id($conn, $target['id']);
                            $scanning_sensors = array_intersect_key($scanning_sensors, $rs_by_asset);
                        }
                        else
                        {
                            //Host without ID
                            $rs_by_asset     = Asset_host_sensors::get_sensors_by_ip($conn, $target['ip_cidr']);
                            $scanning_sensors = array_intersect_key($scanning_sensors, $rs_by_asset);
                        }
                    }
                    else
                    {
                        $scanning_sensors = array();
                    }
                }

                // No sensor found to scan all assets
                if (count($scanning_sensors) < 1)
                {
                    $e_msg = _("Warning! Unable to select the scanning sensor using automatic mode.<br/><br/>
                    It could be because some assets are not associated with a valid scanning sensor or there is no common sensor to scan all assets.<br/>
                    Please, remove these assets from the select box or choose a specific sensor");

                    Av_exception::throw_error(Av_exception::USER_ERROR, $e_msg);
                }

                $local_scanning_sensor = Av_sensor::get_default_sensor($conn);

                if (array_key_exists($local_scanning_sensor, $scanning_sensors))
                {
                    // If local sensor is available, we will use it
                    $scanning_sensor = $local_scanning_sensor;
                }
                else
                {
                    // Otherwise, we will use fist available sensor
                    $scanning_sensor = array_shift(array_keys($scanning_sensors));
                }
            }
            else
            {
                $e_msg = _('Error! Sensor not found');

                Av_exception::throw_error(Av_exception::USER_ERROR, $e_msg);
            }

            //We check whether sensor is inventoried or not
            if(!Av_sensor::is_in_db($conn, $scanning_sensor))
            {
                $e_msg = _('Error! Sensor is not registered yet');

                Av_exception::throw_error(Av_exception::USER_ERROR, $e_msg);
            }
        }
        catch(Exception $e)
        {
            $db->close();

            Av_exception::throw_error(Av_exception::USER_ERROR, $e->getMessage());
        }

        $db->close();

        $this->sensor = $scanning_sensor;
    }


    /**
     * This function sets scan options
     *
     * @param array   $scan_options  Scan options
     *
     * @return void
     */
    public function set_scan_options($scan_options)
    {
        $default_options = array(
            'scan_type'     => 'fast',
            'scan_timing'   => 'T3',
            'autodetect_os' => 'true',
            'reverse_dns'   => 'true',
            'scan_ports'    => '',
            'idm'           => 'true'
        );

        $this->scan_options = $default_options;

        if (is_array($scan_options) && !empty($scan_options))
        {
            $this->scan_options = array_replace_recursive($default_options, $scan_options);
        }
    }


    /**
     * This function gets targets
     *
     * @param string  $format  [Optional] 'scan_format' or 'av_format'
     *
     * @return array
     */
    public function get_targets($format = NULL)
    {
        if (array_key_exists($format, $this->targets))
        {
            $targets = $this->targets[$format];
        }
        else
        {
            $targets = $this->targets;
        }

        return $targets;
    }


    /**
     * This function gets the scan options
     *
     * @param string  $option  [Optional] Scan option
     *
     * @return array | string
     */
    public function get_scan_options($option = NULL)
    {
        if (array_key_exists($option, $this->scan_options))
        {
            $scan_options = $this->scan_options[$option];
        }
        else
        {
            $scan_options = $this->scan_options;
        }

        return $scan_options;
    }


    /**
     * This function gets the scanning_sensor
     *
     * @return string
     */
    public function get_sensor()
    {
        return $this->sensor;
    }


    /**
     * This function downloads a scan results to local system
     *
     * @throws Exception  If an error occurred
     *
     * @return array
     */
    public function download_scan_report()
    {
        $sensor = $this->get_sensor();

        $client = new Alienvault_client();

        $response = $client->nmap()->get_scan($sensor, $this->_get_scan_id());
        $response = @json_decode($response, TRUE);

        if (!$response || ($response['status'] == 'error' && $response['status_code'] >= 500))
        {
            $e_msg = $client->get_error_message($response);
            $e_msg = _('The following error occurred').":<div style='padding-left: 10px;'>".$e_msg."</div>";

            Av_exception::throw_error(Av_exception::USER_ERROR, $e_msg);
        }

        return $response['data'];
    }


    /**
     * This function deletes scan from sensor
     *
     * @throws Exception  If an error occurred
     *
     * @return boolean
     */
    public function delete_scan()
    {
        $sensor = $this->get_sensor();

        $client = new Alienvault_client();

        $response = $client->nmap()->delete_scan($sensor, $this->_get_scan_id());
        $response = @json_decode($response, TRUE);

        if (!$response || ($response['status'] == 'error' && $response['status_code'] >= 500))
        {
            $e_msg = $client->get_error_message($response);
            $e_msg = _('The following error occurred').":<div style='padding-left: 10px;'>".$e_msg."</div>";

            Av_exception::throw_error(Av_exception::USER_ERROR, $e_msg);
        }

        return TRUE;
    }


    /**
     * This function gets scan status
     *
     * @throws Exception  If an error occurred
     *
     * @return array
     */
    public function get_status()
    {
        $allowed_status = array(
            self::ST_IDLE            => _('Idle'),
            self::ST_SEARCHING_HOSTS => _('Searching assets'),
            self::ST_SEARCH_FINISHED => _('Search finished'),
            self::ST_SCANNING_HOSTS  => _('Scanning assets'),
            self::ST_SCAN_FINISHED   => _('Scan finished'),
            self::ST_FAILED          => _('Scan failed')
        );

        //Default status
        $status_code      = self::ST_IDLE;
        $st_desr          = $allowed_status[$status_code];

        $targets          = $this->get_targets('av_format');
        $target_number    = count($targets);
        $scanned_targets  = 0;
        $remaining_time   = -1;
        $elapsed_time     = -1;

        $scan_id = $this->_get_scan_id();

        if(!empty($scan_id))
        {
            $job_status = $this->_get_job_status();

            if (is_array($job_status) && !empty($job_status))
            {
                $target_number   = $job_status['target_number'];
                $scanned_targets = $job_status['scanned_hosts'];
                $remaining_time  = intval($job_status['remaining_time']);
                $elapsed_time    = intval($job_status['end_time']) - intval($job_status['start_time']);

                if ($job_status['status'] == 'In Progress')
                {
                    $status_code    = ($scanned_targets == 0 && $remaining_time == -1) ? self::ST_SEARCHING_HOSTS : self::ST_SCANNING_HOSTS;
                    $st_desr        = $allowed_status[$status_code];
                }
                elseif ($job_status['status'] == 'Finished')
                {
                    $status_code = ($scanned_targets == 0 && $remaining_time == -1) ? self::ST_SEARCH_FINISHED : self::ST_SCAN_FINISHED;
                    $st_desr     = $allowed_status[$status_code];

                    $remaining_time  = 0;
                }
                elseif ($job_status['status'] == 'Fail')
                {
                    $e_msg = _('Scan could not be completed. The following errors occurred').":<div style='padding-left: 10px;'>".$job_status['reason']."</div>";
                    Av_exception::throw_error(Av_exception::USER_ERROR, $e_msg);
                }
            }
        }

        $scan_status = array(
            'status' => array(
                'code'  => $status_code,
                'descr' => $st_desr
            ),
            'number_of_targets' => $target_number,
            'scanned_targets'   => $scanned_targets,
            'remaining_time'    => $remaining_time,
            'elapsed_time'      => $elapsed_time
        );

        return $scan_status;
    }


    /**
     * This function launches a scan
     *
     * @throws Exception  If an error occurred
     *
     * @return string
     */
    public function run()
    {
        $targets = $this->get_targets('scan_format');

        if (empty($targets))
        {
            $e_msg = _('Error! No targets found to scan');

            Av_exception::throw_error(Av_exception::USER_ERROR, $e_msg);
        }

        $sensor       = $this->get_sensor();
        $scan_options = $this->get_scan_options();

        $client = new Alienvault_client();

        $response = $client->nmap()->new_scan($sensor, $targets, $scan_options);
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $e_msg = $client->get_error_message($response);
            $e_msg = _('Scan could not be completed.  The following errors occurred').":<div style='padding-left: 10px;'>".$e_msg."</div>";

            Av_exception::throw_error(Av_exception::USER_ERROR, $e_msg);
        }

        $this->_set_scan_id($response['data']['job_id']);

        return $response['data']['job_id'];
    }


    /**
     * This function stops a scan
     *
     * @throws Exception  If an error occurred
     *
     * @return boolean
     */
    public function stop()
    {
        $client = new Alienvault_client();

        $response = $client->nmap()->stop_scan($this->_get_scan_id());
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $e_msg = $client->get_error_message($response);
            $e_msg = _('The following error occurred').":<div style='padding-left: 10px;'>".$e_msg."</div>";

            Av_exception::throw_error(Av_exception::USER_ERROR, $e_msg);
        }

        return TRUE;
    }


    /**
     * This function gets scan object from a file
     *
     * @param  string  $file  Path where object was stored
     *
     * @throws Exception  If an error occurred
     *
     * @return object
     */
    public static function get_object_from_file($file)
    {
        $av_scan = Cache_file::get_data($file, 0);
        $av_scan = unserialize($av_scan);

        if (!is_object($av_scan) || empty($av_scan))
        {
            $av_scan = NULL;
        }

        return $av_scan;
    }


    /**
     * This function saves the object in a file
     *
     * @param  object  $scan_object  Scan object
     * @param  string  $file         Path where object will be saved
     *
     * @throws Exception  If an error occurred
     *
     * @return string
     */
    public static function set_object_in_file($scan_object, $file)
    {
        $scan_obj = serialize($scan_object);

        $res = Cache_file::save_file($file, $scan_obj);

        return $res;
    }


    /**
     * This function saves scanned assets into database
     *
     * @param object   $conn            Database access object
     * @param array    $scan_results    Scan results
     * @param array    $asset_data      Some host data (Sensors, CTXs, use FQDN as hostname, etc)
     * @param boolean  $report_changes  [Optional] Report changes to other components
     *
     * @throws Exception  If an error occurred
     *
     * @return array
     */
    public static function save_scan_report_in_db($conn, $scan_results, $asset_data, $report_changes = TRUE)
    {
        Ossim_db::check_connection($conn);

        //Ips to insert/update
        $s_ips  = $asset_data['ips'];

        //Number of IPs to insert/update
        $num_ips = count($s_ips);

        //Process status
        $data = array(
            'general' => array(
                'status'         => '',
                'data'           => '',
                'hosts_in_group' => array(),
                'total_hosts'    => 0
             ),
            'by_host' => array()
        );

        Util::disable_perm_triggers($conn, TRUE);

        //Host information
        $scan = $scan_results['scanned_ips'];
        $ctx  = $scan_results['sensor']['ctx'];

        for ($i = 0; $i < $num_ips; $i++)
        {
            if (trim($s_ips[$i]) != '')
            {
                $data['general']['total_hosts']++;

                $ip = $s_ips[$i];

                //There is a validation error (Host could not be saved)
                ossim_valid($ip, OSS_IP_ADDR, 'illegal:' . _('IP Address'));

                if (ossim_error())
                {
                    ossim_clean_error();

                    $data['by_host'][$ip]['status']     = 'error';
                    $data['by_host'][$ip]['data']['ip'] = ossim_get_error_clean();

                    continue;
                }

                //Initialize variables
                $host          = NULL;
                $ips           = array();
                $name          = $scan[$ip]['hostname'];
                $fqdn          = $scan[$ip]['fqdn'];
                $descr         = $asset_data['descr'];
                $mac           = $scan[$ip]['mac'];
                $os            = $scan[$ip]['os'];
                $asset_value   = $asset_data['asset_value'];
                $external      = $asset_data['external'];
                $sensors       = $asset_data['sboxs'];
                $nmap_devices  = $scan[$ip]['device_types'];
                $hardware_cpes = $scan[$ip]['hardware_cpes'];
                $software_cpes = $scan[$ip]['software_cpes'];
                $services      = $scan[$ip]['services'];

                //Save host with new data
                try
                {
                    $host_ids = Asset_host::get_id_by_ips($conn, $ip, $ctx);
                    $id       = key($host_ids);

                    $is_in_db = Asset_host::is_in_db($conn, $id);

                    //Host already exists

                    if ($is_in_db == TRUE)
                    {
                        $can_i_create_assets = TRUE;

                        //Updating specific attributes (Only name and FQDNs)
                        $host = new Asset_host($conn, $id);
                        $host->load_from_db($conn);

                        $h_fqdn  = $host->get_fqdns();
                        $h_descr = $host->get_descr();

                        if (!empty($fqdn))
                        {
                            $host->set_fqdns($fqdn);
                            $h_fqdn = $host->get_fqdns();
                        }


                        // Use FQDNs as host name (See checkbox on Asset Discovery)
                        if(!empty($h_fqdn) && !empty($asset_data["fqdn_$i"]))
                        {
                            $aux  = explode('.', $fqdn);
                            $name = $aux[0];
                            $host->set_name($name);
                        }


                        //Only if old description is empty
                        if (!empty($descr) && empty($h_descr))
                        {
                            $host->set_descr($descr);
                        }

                        $host_ips = $host->get_ips();
                        $ips      = $host_ips->get_ips();

                        $ips[$ip] = array(
                            'ip'  => $ip,
                            'mac' => NULL,
                        );

                        if (!empty($mac))
                        {
                            $ips[$ip]['mac'] = $mac;
                        }

                        $host->set_ips($ips);

                        $host->set_sensors($sensors);

                        $host->set_asset_value($asset_value);

                        $host->set_external($external);
                    }
                    else
                    {
                        $can_i_create_assets = Session::can_i_create_assets();

                        if ($can_i_create_assets == TRUE)
                        {
                            //Creating new host and updating specific attributes
                            $id = Util::uuid();

                            $host = new Asset_host($conn, $id);
                            $host->set_ctx($ctx);

                            // Use FQDNs as host name (See checkbox on Asset Discovery)
                            if(!empty($fqdn) && !empty($asset_data["fqdn_$i"]))
                            {
                                $aux   = explode('.', $fqdn);
                                $name  = $aux[0];
                                $host->set_name($name);
                            }
                            else
                            {
                                $host->set_name($name);
                            }

                            if (!empty($fqdn))
                            {
                                $host->set_fqdns($fqdn);
                            }

                            $ips[$ip] = array(
                                'ip'   => $ip,
                                'mac'  => NULL,
                            );

                            if (!empty($mac))
                            {
                                $ips[$ip]['mac'] = $mac;
                            }

                            $host->set_ips($ips);

                            $host->set_descr($descr);

                            $host->set_sensors($sensors);

                            $host->set_asset_value($asset_value);

                            $host->set_external($external);
                        }
                    }


                    $cnd_1 = (is_object($host) && !empty($host));
                    $cnd_2 = ($can_i_create_assets == TRUE);

                    if ($cnd_1 && $cnd_2)
                    {
                        //Updating common attributes (Devices, Software and Services)

                        //Adding new NMAP devices
                        if (is_array($nmap_devices) && !empty($nmap_devices))
                        {
                            foreach($nmap_devices as $nmap_device_id)
                            {
                                $host->get_devices()->add_nmap_device($nmap_device_id);
                            }
                        }

                        $host->save_in_db($conn, FALSE);

                        //Adding host to new asset group
                        $data['general']['hosts_in_group'][$ip] = $id;

                        $data['by_host'][$ip]['status'] = 'success';

                        if ($is_in_db == TRUE)
                        {
                            $data['by_host'][$ip]['data'] = _('Asset saved');
                        }
                        else
                        {
                            $data['by_host'][$ip]['data'] = _('New asset inserted');
                        }


                        //Initialize warning array
                        $warnings = array();

                        //Insert/Updating Operating System
                        if (!empty($os))
                        {
                            try
                            {
                                $save_os = TRUE;

                                if ($is_in_db == TRUE)
                                {
                                    //Inserting SO with less relevance than current one
                                    $filters = array(
                                        'where' => "host_properties.host_id = UNHEX('$id')
                                                    AND host_properties.property_ref = 3
                                                    AND host_properties.source_id = hsr.id
                                                    AND hsr.relevance > 7"
                                    );

                                    $_p3_list = Asset_host_properties::get_list($conn, $filters);
                                    $total_p3 = $_p3_list[1];

                                    if ($total_p3 > 0)
                                    {
                                        $save_os = FALSE;

                                        $warnings['os_error'] = _('OS could not be saved [More relevant OS was previously inserted]');
                                    }
                                }

                                if ($save_os == TRUE)
                                {
                                    Asset_host_properties::delete_property_from_db($conn, $id, 3);
                                    Asset_host_properties::save_property_in_db($conn, $id, 3, $os, 5);
                                }
                            }
                            catch(Exception $e)
                            {
                                $warnings['os_error'] = _('Operating System could not be saved');
                            }
                        }


                        //Inserting/updating hardware CPEs
                        if(is_array($hardware_cpes) && !empty($hardware_cpes))
                        {
                            //Getting previous hardware
                            $previous_hcpes = array();
                            $total_hcpes    = 0;

                            if ($is_in_db == TRUE)
                            {
                                //Inserting hardware with less relevance than current ones
                                $filters = array(
                                    'where' => "h.id = UNHEX('$id') AND host_source_reference.relevance > 7"
                                );

                                list($previous_hcpes, $total_hcpes) = Asset_host_software::get_list($conn, $filters);
                            }

                            foreach($hardware_cpes as $h_cpe)
                            {
                                if ($total_hcpes == 0 || ($total_hcpes > 0 && empty($previous_hcpes[$id][$h_cpe])))
                                {
                                    $s_data = array(
                                        'cpe'        => $h_cpe,
                                        'banner'     => '',
                                        'source_id'  => 5,
                                        'extra'      => ''
                                    );

                                    try
                                    {
                                        Asset_host_software::save_software_in_db($conn, $id, $s_data);
                                    }
                                    catch(Exception $e)
                                    {
                                        $warnings['hd_error'] = _('Some hardware could not be saved');
                                    }
                                }
                                else
                                {
                                    $warnings['hd_error'] = _('Some hardware CPEs could not be saved [More relevant hardware CPEs were previously inserted]');
                                }
                            }
                        }


                        //Inserting/updating Software CPEs
                        if(is_array($software_cpes) && !empty($software_cpes))
                        {
                            //Getting previous software
                            $previous_scpes = array();
                            $total_scpes    = 0;

                            if ($is_in_db == TRUE)
                            {
                                //Inserting software with less relevance than current ones
                                $filters = array(
                                    'where' => "h.id = UNHEX('$id') AND host_source_reference.relevance > 7"
                                );

                                list($previous_scpes, $total_scpes) = Asset_host_software::get_list($conn, $filters);
                            }

                            foreach($software_cpes as $s_cpe)
                            {
                                if ($total_scpes == 0 || ($total_scpes > 0 && empty($previous_scpes[$id][$s_cpe])))
                                {
                                    $s_data = array(
                                        'cpe'        => $s_cpe,
                                        'banner'     => Asset_host_software::get_software_name_by_cpe($conn, $s_cpe),
                                        'source_id'  => 5,
                                        'extra'      => ''
                                    );

                                    try
                                    {
                                        Asset_host_software::save_software_in_db($conn, $id, $s_data);
                                    }
                                    catch(Exception $e)
                                    {
                                        $warnings['sf_error'] = _('Some software could not be saved');
                                    }
                                }
                                else
                                {
                                    $warnings['sf_error'] = _('Some software CPEs could not be saved [More relevant software CPEs were previously inserted]');
                                }
                            }
                        }

                        //Adding services
                        if(is_array($services) && !empty($services))
                        {
                            //Getting previous services
                            $previous_services = array();
                            $total_services    = 0;

                            if ($is_in_db == TRUE)
                            {
                                //Inserting services with less relevance than current ones
                                $filters = array(
                                    'where' => "h.id = UNHEX('$id') AND host_services.host_ip = inet6_aton('$ip') "
                                );
                                list($previous_services, $total_services) = Asset_host_services::get_list($conn, $filters);
                            }

                            foreach($services as $service)
                            {
                                $p_id  = Protocol::get_protocol_by_name($service['proto']);
                                $s_key = $ip.'-'.$service['port'].'-'.$p_id;
                                $prev = isset($previous_services[$id][$s_key]) ? $previous_services[$id][$s_key] : false;
                                if ($prev && $prev["relevance"] > 7) {
                                    $warnings['service_error'] = _('Some services could not be saved [More relevant services were previously inserted]');
                                    continue;
                                }
                                $s_data = array(
                                    'ip'        => $ip,
                                    'service'   => $service['service'],
                                    'port'      => $service['port'],
                                    'protocol'  => $p_id,
                                    'version'   => $service['version'],
                                    'source_id' => 5,
                                    'nagios'    => $prev ? $prev["nagios"]["enabled"] : 0
                                );
                                try
                                {
                                    Asset_host_services::save_service_in_db($conn, $id, $s_data);
                                }
                                catch(Exception $e)
                                {
                                    $warnings['service_error'] = _('Some services could not be saved');
                                }
                            }
                        }

                        //Setting host status and general status
                        if (is_array($warnings) && !empty($warnings))
                        {
                            $data['by_host'][$ip]['status'] = 'warning';
                            $data['by_host'][$ip]['data']   = $warnings;
                        }
                    }
                    else
                    {
                        $data['by_host'][$ip]['status'] = 'error';
                        $data['by_host'][$ip]['data']   = array ('no_create_asset' => _('You do not have the correct permissions to create assets. Please contact system administrator with any questions'));
                    }
                }
                catch(Exception $e)
                {
                    $data['by_host'][$ip]['status'] = 'error';
                    $data['by_host'][$ip]['data']   = array ('php_exception' => $e->getMessage());
                }
            }
        }


        if (count($data['general']['hosts_in_group']) > 0 && $report_changes == TRUE)
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

        Util::disable_perm_triggers($conn, FALSE);

        return $data;
    }


    /**
     * This function gets all scanning sensors available
     *
     * @return array
     */
    public static function get_scanning_sensors()
    {
        $client = new Alienvault_client();

        $response = $client->sensor()->get_list();
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $e_msg = $client->get_error_message($response);
            $e_msg = _('The following error occurred').":<div style='padding-left: 10px;'>".$e_msg."</div>";

            Av_exception::throw_error(Av_exception::USER_ERROR, $e_msg);
        }

        $sensors      = array();
        $_aux_sensors = $response['data']['sensors'];

        if (is_array($_aux_sensors) && !empty($_aux_sensors))
        {
            $scan_list = self::get_list();

            foreach($_aux_sensors as $s_data)
            {
                $sensor_id = Util::uuid_format_nc($s_data['sensor_id']);
                $reachable = $s_data['reachable'];

                if ($reachable == FALSE)
                {
                    $s_status = array(
                        'code'  => 2,
                        'descr' => _('Down')
                    );
                }
                else
                {
                    if ($scan_list[$sensor_id]['status'] == 'running')
                    {
                        $s_status = array(
                            'code'  => 1,
                            'descr' => _('Running')
                        );
                    }
                    else
                    {
                        $s_status = array(
                            'code'  => 0,
                            'descr' => _('Idle')
                        );
                    }
                }

                $sensors[$sensor_id] = array(
                    'sensor_id' => $s_data['sensor_id'],
                    'admin_ip'  => $s_data['admin_ip'],
                    'ha_ip'     => $s_data['ha_ip'],
                    'vpn_ip'    => $s_data['vpn_ip'],
                    'hostname'  => $s_data['hostname'],
                    'status'    => $s_status
                );
            }
        }

        return $sensors;
    }


    /**
     * This function explains an asset scan
     *
     * @param  object  $conn         DB Connection
     * @param  string  $target_type  [Optional] Asset type
     *
     * @throws Exception  If an error occurred
     *
     * @return  array
     */
    public static function explain_scan($conn, $target_type = 'asset')
    {
        Ossim_db::check_connection($conn);

        $asset_by_sensor = array();
        $added_asset     = array();
        $explain_scan    = array();


        $total_targets = Filter_list::get_total_selection($conn, $target_type);

        if ($total_targets > Filter_list::MAX_NMAP_ITEMS)
        {
            $e_msg = _('Asset scans can only be performed on %s assets at a time. Please select less assets and try again.');
            $e_msg = sprintf($e_msg, Util::number_format_locale(Filter_list::MAX_NMAP_ITEMS));

            Av_exception::throw_error(Av_exception::USER_ERROR, $e_msg);
        }

        if ($target_type == 'asset')
        {
            $host_perms_where = Asset_host::get_perms_where('h.', TRUE);
            $query            = "SELECT hex(hi.host_id) AS asset_id, INET6_NTOA(hi.ip) AS asset_ip, HEX(hsf.sensor_id) AS sensor
                FROM user_component_filter uf, host h, host_ip hi, host_sensor_reference hsf
                WHERE uf.session_id=? AND h.id=hi.host_id AND uf.asset_id=hi.host_id AND hi.host_id=hsf.host_id AND uf.asset_type='asset' $host_perms_where";
        }
        elseif ($target_type == 'group')
        {
            $host_perms_where = Asset_host::get_perms_where('h.', TRUE);
            $query            = "SELECT hex(hi.host_id) AS asset_id, INET6_NTOA(hi.ip) AS asset_ip, HEX(hsf.sensor_id) AS sensor
                FROM user_component_filter uf, host h, host_ip hi, host_sensor_reference hsf, host_group_reference hgr
                WHERE h.id=hi.host_id AND uf.session_id=? AND uf.asset_id=hgr.host_group_id AND hgr.host_id=hi.host_id
                AND hi.host_id=hsf.host_id AND uf.asset_type='group' $host_perms_where";
        }
        elseif ($target_type == 'network')
        {
            $net_perms_where  = Asset_net::get_perms_where('n.', TRUE);
            $query = "SELECT hex(n.id) AS asset_id, nc.cidr AS asset_ip,HEX(nsr.sensor_id) AS sensor
                FROM user_component_filter uf, net n, net_cidrs nc, net_sensor_reference nsr
                WHERE uf.session_id=? AND uf.asset_id=n.id AND nsr.net_id=n.id AND n.id=nc.net_id
                AND uf.asset_type='network' $net_perms_where";
        }


        if (!empty($query))
        {
            $params = array(session_id());
            $rs     = $conn->Execute($query, $params);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }

            while (!$rs->EOF)
            {
                $asset_by_sensor[$rs->fields['sensor']][$rs->fields['asset_ip']] = $rs->fields['asset_id'];

                $rs->MoveNext();
            }

            $rs->free();

            //Getting all scanning sensors available
            $scanning_sensors = self::get_scanning_sensors();

            foreach ($asset_by_sensor as $sensor_id => $a_data)
            {
                $sensor   = Av_sensor::get_object($conn, $sensor_id);
                $s_status = $scanning_sensors[$sensor_id]['status'];

                $explain_scan[$sensor_id] = array(
                    'status'  => $s_status,
                    'name'    => $sensor->get_name(),
                    'ip'      => $sensor->get_ip(),
                    'assets'  => array()
                );

                foreach ($a_data as $ip => $id)
                {
                    if (!$added_asset[$ip][$id])
                    {
                        $explain_scan[$sensor_id]['assets'][] = array(
                            'ip' => $ip,
                            'id' => $id
                        );

                        $added_asset[$ip][$id]++;
                    }
                }

                if (empty($explain_scan[$sensor_id]['assets']))
                {
                    // Remove if there are no assets
                    unset($explain_scan[$sensor_id]);
                }
            }
        }

        return $explain_scan;
    }


    /**
     * This function returns a list of all running scans
     *
     * @return array
     */
    public static function get_list()
    {
        $client = new Alienvault_client();

        $response = $client->nmap()->get_list();
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $e_msg = $client->get_error_message($response);
            $e_msg = _('The following error occurred').":<div style='padding-left: 10px;'>".$e_msg."</div>";

            Av_exception::throw_error(Av_exception::USER_ERROR, $e_msg);
        }

        $scan_list      = array();
        $_aux_scan_list = $response['data']['result'];

        if (is_array($_aux_scan_list) && !empty($_aux_scan_list))
        {
            foreach($_aux_scan_list as $scan_data)
            {
                $sensor_id  = Util::uuid_format_nc($scan_data['sensor_id']);
                $job_status = $scan_data['status'];

                if ($job_status == 'In Progress')
                {
                    $scan_list[$sensor_id]['status'] = 'running';
                }
                elseif (empty($scan_list[$sensor_id]['status']))
                {
                    $scan_list[$sensor_id]['status'] = 'idle';
                }

                $scan_list[$sensor_id]['jobs'][$scan_data['job_id']] = array(
                    'status'      => $job_status,
                    'idm'         => $scan_data['idm'],
                    'scan_params' => $scan_data['scan_params']
                );
            }
        }

        return $scan_list;
    }


    /**
     * This function translates targets to readable string
     *
     * @param  string  $targets   Targets to scan
     *
     * @return string
     */
    public static function targets_to_string($targets)
    {
        if (empty($targets))
        {
            $targets_txt = '';
        }
        else
        {
            $targets = explode(' ', $targets);

            if (count($targets) == 1)
            {
                $targets_txt = $targets[0];
                $targets_txt = explode('#', $targets_txt);
                $targets_txt = (count($targets_txt) == 2) ? $targets_txt[1] : $targets_txt[0];
            }
            else
            {
                $targets_txt_1 = $targets[0];
                $targets_txt_1 = explode('#', $targets_txt_1);
                $targets_txt_1 = (count($targets_txt_1) == 2) ? $targets_txt_1[1] : $targets_txt_1[0];

                $targets_txt_2 = $targets[(count($targets)-1)];
                $targets_txt_2 = explode('#', $targets_txt_2);
                $targets_txt_2 = (count($targets_txt_2) == 2) ? $targets_txt_2[1] : $targets_txt_2[0];

                $targets_txt = $targets_txt_1." ... ".$targets_txt_2;
            }
        }

        return $targets_txt;
    }


    /***********************************************************
     ******************** Private functions ********************
     ***********************************************************/

    /**
     * This function gets Job ID related to asset scan
     *
     * @return string
     */
    private function _get_scan_id()
    {
        return $this->scan_id;
    }

     /**
     * This function sets Job ID related to asset scan
     *
     * @param string  $scan_id  Job ID
     *
     * @return void
     */
    private function _set_scan_id($scan_id)
    {
        return $this->scan_id = $scan_id;
    }


    /**
     * This function sets targets to scan fron input string
     *
     * @param array  $target_input  String with targets to scan
     *
     * @throws Exception  If an error occurred
     *
     * @return void
     */
    private function _set_targets_from_input($target_input)
    {
        if (empty($target_input))
        {
            $e_msg = _('Error! Target input is empty');

            Av_exception::throw_error(Av_exception::USER_ERROR, $e_msg);
        }

        $target_list = explode(' ', $target_input);

        $this->targets = self::_normalize_targets($target_list);
    }


     /**
     * This function sets targets to scan fron input file
     *
     * @param array  $target_file  File that contains targets to scan
     *
     * @throws Exception  If an error occurred
     *
     * @return void
     */
    private function _set_targets_from_file($target_file)
    {
        if (!is_readable($target_file))
        {
            $e_msg = _('Error! File not found or not readable');

            Av_exception::throw_error(Av_exception::USER_ERROR, $e_msg);
        }

        $target_list = file($target_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $this->targets = self::_normalize_targets($target_list);

        @unlink($target_file);
    }


    /**
     * This function gets job status
     *
     * @throws Exception  If an error occurred
     *
     * @return array
     */
    private function _get_job_status()
    {
        $job_status = array();

        $scan_id = $this->_get_scan_id();

        if(!empty($scan_id))
        {
            $client = new Alienvault_client();

            $response = $client->nmap()->get_scan_status($scan_id);
            $response = @json_decode($response, TRUE);

            if (!$response || $response['status'] == 'error')
            {
                $e_msg = $client->get_error_message($response);
                $e_msg = _('The following error occurred').":<div style='padding-left: 10px;'>".$e_msg."</div>";

                Av_exception::throw_error(Av_exception::USER_ERROR, $e_msg);
            }

            $job_status = $response['data']['result'];
        }

        return $job_status;
    }


    /**
     * This function checks if user is logged in the system
     *
     * @throws Exception  If an error occurred
     *
     * @return array
     */
    private static function _check_user_session()
    {
        //User must be logged in the system, otherwise AlienVault API calls could fail
        if (Session::get_session_user() == '')
        {
            $e_msg = _('Error! No user logged in the system');

            Av_exception::throw_error(Av_exception::USER_ERROR, $e_msg);
        }
    }


    /**
     * This function gets targets in a normalized way
     *
     * @param array  $target_list  Target list
     *
     * @return  array
     */
    private static function _normalize_targets($target_list)
    {
        $targets = array(
            'scan_format' => '',
            'av_format'   => array()
        );

        if (is_array($target_list) && !empty($target_list))
        {
            $scan_format = array();
            $av_format   = array();

            foreach ($target_list as $target)
            {
                //Inventoried assets
                if (preg_match('/(.*)#((\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})(\/\d{1,2})?)/', $target, $matches))
                {
                    $target_type = ($matches[4] == '/32' || empty($matches[4])) ? 'asset' : 'net';
                    $id          = trim($matches[1]);
                    $ip_cidr     = ($target_type == 'asset') ? trim($matches[3]) : trim($matches[2]);
                    $scan_asset  = trim($matches[2]);
                }
                elseif(preg_match('/^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})(\/\d{1,2})?$/', $target, $matches))
                {
                    $target_type  = ($matches[2] == '/32' || empty($matches[2])) ? 'asset' : 'net';
                    $id           = NULL;
                    $ip_cidr      = ($target_type == 'asset') ? trim($matches[1]) : trim($matches[0]);
                    $scan_asset   = trim($matches[0]);
                }

                if (!empty($ip_cidr) && (Asset_host_ips::valid_ip($ip_cidr) || Asset_net::valid_cidr($ip_cidr)))
                {
                    $av_format[$target] = array(
                        'type'    => $target_type,
                        'ip_cidr' => $ip_cidr,
                        'id'      => $id
                    );

                    $scan_format[$scan_asset] = $scan_asset;
                }
            }

            $targets = array(
                'scan_format' => implode(' ', $scan_format),
                'av_format'   => $av_format
            );
        }

        return $targets;
    }
}

/* End of file av_scan.inc */
/* Location: ../include/classes/av_scan.inc */
