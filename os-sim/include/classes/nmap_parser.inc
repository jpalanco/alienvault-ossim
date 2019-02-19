<?php
/**
* nmap_parser.inc
*
* File nmap_parser.inc is used to manage Nmap XML output.
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
* The goal of this class is to retrieve the information contained in the Nmap XML:
* IP, ports, services, ...
*
* This information can be linked to some context, it depends on the parameters passed to the constructor
*
* <br/>
* <pre>
* <code>
* Example usage:
*
* $nmap_parser = new Nmap_parser('AAA003FA49ED11E2B15D82CCDD6F7E50#192.168.10.1 192.168.10.2');
*
* Constructor method accepts IPs with a ID field: Host_ID#Host_IP, for CIDR is the same format: Net_ID#Net_CIDR.
*
* </code>
* </pre>
*
* @package    ossim-framework\Assets
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/



class Nmap_parser
{
    /**
     * Variable that stores the Nmap results
     *
     * @access private
     * @var array
     */
    private $result;


    /**
     * The class constructor
     *
     * This sets up the class
     *
     */
    public function __construct()
    {
        $this->result = array();
    }

    /**
    * This function set sensor data
    *
    * @param string  $sensor_id  Sensor UUID
    *
    * @return void
    */
    public function set_sensor($sensor_id)
    {
        //Sensor Information
        $db   = new ossim_db();
        $conn = $db->connect();

        try
        {
            if (valid_hex32($sensor_id))
            {
                $sensor_obj = Av_sensor::get_object($conn, $sensor_id);

                $sensor_ip   = $sensor_obj->get_ip();
                $sensor_ctxs = $sensor_obj->get_ctx();
                $sensor_ctx  = key($sensor_ctxs);

                $this->result['sensor']['ip']  = $sensor_ip;
                $this->result['sensor']['ctx'] = $sensor_ctx;
            }
        }
        catch(Exception $e)
        {
            ;
        }

        $db->close();
    }

    /**
     * This function extracts useful information from Nmap xml
     *
     * @param object|string  $data  Nmap XML or XML file path $data Value of private attribute {@link http://nmap.org/book/nmap-dtd.html}
     *
     * @return array
     */
    public function parse_xml($data, $repair_file = FALSE)
    {
        $xml = NULL;

        if(is_object($data) && method_exists($data, 'attributes') && is_object($data->attributes()->scanner)  && (string)$data->attributes()->scanner == 'nmap')
        {
            $xml = $data;
        }
        else if(is_string($data) && is_file($data))
        {
            if ($repair_file == TRUE)
            {
                $config = array(
                          'indent'     => TRUE,
                          'input-xml'  => TRUE,
                          'output-xml' => TRUE,
                          'wrap'       => FALSE);

                $tidy     = new tidy();
                $repaired = $tidy->repairfile($data, $config);

                file_put_contents($data, $repaired);
            }

            $xml = @simplexml_load_file($data);
        }
        else if(is_string($data))
        {
            $xml = @simplexml_load_string($data);
        }


        $this->result = array(
            'sensor'      => array(),
            'nmap_data'   => array(),
            'scanned_ips' => array()
        );

        if(is_object($xml) && !empty($xml))
        {

            self::set_sensor( (string)$xml->attributes()->av_sensor );

            // Debug info for Selenium Test

            if(is_object($xml->attributes()->args) && !empty($xml->attributes()->args))
            {
            	$this->result['nmap_data']['cmd'] = (string)$xml->attributes()->args;
            }

            if(is_object($xml->attributes()->version) && !empty($xml->attributes()->version))
            {
            	$this->result['nmap_data']['version'] = (string)$xml->attributes()->version;
            }

            if(is_object($xml->attributes()->xmloutputversion) && !empty($xml->attributes()->xmloutputversion))
            {
            	$this->result['nmap_data']['xmloutputversion'] = (string)$xml->attributes()->xmloutputversion;
            }

            if(is_object($xml->attributes()->startstr) && !empty($xml->attributes()->startstr))
            {
            	$this->result['nmap_data']['start'] = (string)$xml->attributes()->startstr;
            }


            $stats = array_shift($xml->xpath('//runstats'));

            if(is_object($stats->finished) && !empty($stats->finished->attributes()->timestr))
            {
            	$this->result['nmap_data']['end'] = (string)$stats->finished->attributes()->timestr;
            }

            if(is_object($stats->finished) && !empty($stats->finished->attributes()->elapsed))
            {
            	$this->result['nmap_data']['elapsed'] = (string)$stats->finished->attributes()->elapsed;
            }


            /****************************************************
             **************** Getting Nmap Info *****************
             ****************************************************/

            foreach ($xml->xpath('//host') as $host)
            {
                /*****************************************************
                 ****************** IP and Hostname ******************
                 ****************************************************/

                $ip     = '';
                $status = 'unknown';
                $reason = 'unknown';

                if (is_object($host->status))
                {
                    $status = (string)$host->status->attributes()->state;
                    $reason = (string)$host->status->attributes()->reason;

                    if (is_object($host->address))
                    {
                        foreach($host->address as $address)
                        {
                            $addrtype = (string)$address->attributes()->addrtype;

                            if( preg_match("/ipv4/i", $addrtype) )
                            {
                                $ip = (string)$address->attributes()->addr;
                            }
                        }
                    }
                }

                $asset_key = $ip;
                $this->result['scanned_ips'][$asset_key]['status']   = $status;
                $this->result['scanned_ips'][$asset_key]['reason']   = $reason;
                $this->result['scanned_ips'][$asset_key]['ip']       = $ip;
                $this->result['scanned_ips'][$asset_key]['hostname'] = Asset_host::get_autodetected_name($ip);


                /*****************************************************
                 *********************** FQDN ************************
                 ****************************************************/

                if (is_object($host->hostnames->hostname) && count($host->hostnames->hostname) > 0)
                {
                    $host_attrs = $host->hostnames->hostname->attributes();

                    if(is_object($host_attrs) && (string)$host->hostnames->hostname->attributes()->name != '')
                    {
                        $fqdn = (string)$host->hostnames->hostname->attributes()->name;

                        if (valid_fqdns($fqdn) == TRUE)
                        {
                            $this->result['scanned_ips'][$asset_key]['fqdn'] = $fqdn;
                        }
                        else
                        {
                            ossim_clean_error();
                        }
                    }
                }


                /*****************************************************
                 *********************** MAC *************************
                 ****************************************************/

                if (is_object($host->address))
                {
                    foreach($host->address as $address)
                    {
                        $addrtype = (string)$address->attributes()->addrtype;

                        if(preg_match('/mac/i', $addrtype))
                        {
                            $this->result['scanned_ips'][$asset_key]['mac']        = (string)$address->attributes()->addr;
                            $this->result['scanned_ips'][$asset_key]['mac_vendor'] = (string)$address->attributes()->vendor;
                        }
                    }
                }


               /*****************************************************
                *********** OS, Devices and Hardware CPEs ***********
                ****************************************************/

                $max_osclass_accuracy = 0;
                $max_osmatch_accuracy = 0;
                $hardware_cpe         = FALSE;

                $this->result['scanned_ips'][$asset_key]['os']            = '';
                $this->result['scanned_ips'][$asset_key]['hardware_cpes'] = array();
                $this->result['scanned_ips'][$asset_key]['device_types']  = array();


                if(!empty($host->os->osmatch) && is_object($host->os->osmatch))
                {
                    foreach ($host->os->osmatch as $osmatch)
                    {
                        if (!empty($osmatch) && is_object($osmatch))
                        {
                            if(intval($osmatch->attributes()->accuracy) >= $max_osmatch_accuracy)
                            {
                                $max_osmatch_accuracy  = intval($osmatch->attributes()->accuracy);
                                $max_osclass_accuracy  = 0;
                            }

                            foreach($osmatch->osclass as $osclass)
                            {
                                 if(intval($osclass->attributes()->accuracy) >= $max_osclass_accuracy)
                                 {
                                    $max_osclass_accuracy = intval($osclass->attributes()->accuracy);

                                    //Getting Operating System
                                    $os = (string)$osclass->attributes()->osfamily;

                                    if (!empty($os))
                                    {
                                        $os_gen = (string)$osclass->attributes()->osgen;
                                        $os    .= (!empty($os_gen)) ? ' '.$os_gen : '';
                                    }

                                    //Setting Operating System -> Add first valid
                                    if ($this->result['scanned_ips'][$asset_key]['os'] == '')
                                    {
                                        $this->result['scanned_ips'][$asset_key]['os'] = $os;
                                    }

                                    //Setting Device type -> Add all
                                    $device_type = (string)$osclass->attributes()->type;
                                    if (!is_array($this->result['scanned_ips'][$asset_key]['device_types']))
                                    {
                                        $this->result['scanned_ips'][$asset_key]['device_types'] = array();
                                    }
                                    $this->result['scanned_ips'][$asset_key]['device_types'][$device_type] = $device_type;


                                    //Setting Hardware CPEs -> Add all
                                    if (!empty($osclass->cpe) && is_object($osclass->cpe))
                                    {
                                        //Select all hardware
                                        if (!is_array($this->result['scanned_ips'][$asset_key]['hardware_cpes']))
                                        {
                                            $this->result['scanned_ips'][$asset_key]['hardware_cpes'] = array();
                                        }
                                        foreach ($osclass->cpe as $cpe)
                                        {
                                            $cpe = (string)$cpe;

                                            if(preg_match('/^cpe:\/h/', $cpe))
                                            {
                                                $this->result['scanned_ips'][$asset_key]['hardware_cpes'][$cpe] = $cpe;
                                                $hardware_cpe                                                   = TRUE;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                /*****************************************************
                 *********** Services and Software CPEs **************
                 ****************************************************/


                $this->result['scanned_ips'][$asset_key]['software_cpes'] = array();
                $this->result['scanned_ips'][$asset_key]['services']      = array();

                if (!empty($host->ports) && is_object($host->ports->port))
                {
                    foreach($host->ports->port as $port)
                    {
                        $state  = (string)$port->state->attributes()->state;

                        //Checking service state
                        if($state != 'open' && $state != '')
                        {
                            continue;
                        }

                        $s_data = array(
                            'port'    => (string)$port->attributes()->portid,
                            'proto'   => (string)$port->attributes()->protocol,
                            'state'   => $state,
                            'service' => 'unknown',
                            'version' => 'unknown',
                        );

                        if (is_object($port->service))
                        {
                            $s_data['service'] = (string)(!@is_null($port->service->attributes()->name) ? $port->service->attributes()->name : 'unknown');

                            if(!@is_null($port->service->attributes()->tunnel))
                            {
                                $s_data['service'] .= '-'.(string)($port->service->attributes()->tunnel);
                            }

                            if(is_object($port->service->cpe))
                            {
                                foreach ($port->service->cpe as $scpe)
                                {
                                    $cpe = (string)$scpe;;

                                    if(preg_match('/^cpe:\/a/', $cpe) && $s_data['version']  == 'unknown')
                                    {
                                        $s_data['version'] = $cpe;
                                    }

                                   $this->result['scanned_ips'][$asset_key]['software_cpes'][$cpe] = $cpe;

                                   if(preg_match('/^cpe:\/h/', $cpe))
                                   {
                                        $hardware_cpe = TRUE;
                                   }
                                }
                            }

                            //Setting service version if cpe:a not found
                            if ($s_data['version'] == 'unknown')
                            {
                                $product     = (string)(!@is_null($port->service->attributes()->product) ? $port->service->attributes()->product : '');
                                $aux_version = (string)(!@is_null($port->service->attributes()->version) ? $port->service->attributes()->version : '');
                                $extra_info  = (string)(!@is_null($port->service->attributes()->extrainfo) ? $port->service->attributes()->extrainfo : '');

                                $version = array($product, $aux_version, $extra_info);
                                $version = implode(' ', $version);
                                $version = trim($version);

                                if (!empty($version))
                                {
                                    $s_data['version'] = $version;
                                }
                            }
                        }

                        $s_key = $s_data['port'].'/'.$s_data['proto'];
                        $this->result['scanned_ips'][$asset_key]['services'][$s_key] = $s_data;
                    }
                }

                // Add also network device type if necessary
                if ($hardware_cpe == TRUE && $this->result['scanned_ips'][$asset_key]['device_types']['general purpose'] == 'general purpose')
                {
                    $this->result['scanned_ips'][$asset_key]['device_types']['network device'] = 'network device';
                }

            } // foreach end
        }

        return $this->result;
    }


    /**
     * This function extracts useful information from Nmap json result
     *
     * @param string  $data       Nmap JSON string or Nmap array
     * @param string  $sensor_id  Sensor UUID
     *
     * @return array
     */
    public function parse_json($data, $sensor_id)
    {
        $json = (is_array($data)) ? $data : @json_decode($data, TRUE);

        $this->result = array(
            'sensor'      => array(),
            'nmap_data'   => array(),
            'scanned_ips' => array()
        );


        if(json_last_error() == JSON_ERROR_NONE && !empty($json['result']))
        {
            self::set_sensor($sensor_id);

            $json = $json['result'];

            foreach ($json as $ip => $host)
            {
                /*****************************************************
                 ****************** IP and Hostname ******************
                 ****************************************************/

                $status = 'unknown';
                $reason = 'unknown';

                if (!empty($host['status']))
                {
                    $status = $host['status']['state'];
                    $reason = $host['status']['reason'];
                }

                $asset_key = $ip;
                $this->result['scanned_ips'][$asset_key]['status']   = $status;
                $this->result['scanned_ips'][$asset_key]['reason']   = $reason;
                $this->result['scanned_ips'][$asset_key]['ip']       = $ip;
                $this->result['scanned_ips'][$asset_key]['hostname'] = Asset_host::get_autodetected_name($ip);

                if (!empty($host['host_ipv6']))
                {
                    $this->result['scanned_ips'][$asset_key]['ipv6'] = $host['host_ipv6'];
                }

                /*****************************************************
                 *********************** FQDN ************************
                 ****************************************************/

                if (!empty($host['hostname']))
                {
                    if (valid_fqdns($host['hostname']) == TRUE)
                    {
                        $this->result['scanned_ips'][$asset_key]['fqdn'] = $host['hostname'];
                    }
                    else
                    {
                        ossim_clean_error();
                    }
                }

                /*****************************************************
                 *********************** MAC *************************
                 ****************************************************/

                if (!empty($host['host_mac']))
                {
                    $this->result['scanned_ips'][$asset_key]['mac']        = $host['host_mac'];
                }

                if (!empty($host['host_mac_vendor']))
                {
                    $this->result['scanned_ips'][$asset_key]['mac_vendor'] = $host['host_mac_vendor'];
                }

               /*****************************************************
                *********** OS, Devices and Hardware CPEs ***********
                ****************************************************/

                $max_osclass_accuracy = 0;
                $hardware_cpe         = FALSE;

                $this->result['scanned_ips'][$asset_key]['os']            = '';
                $this->result['scanned_ips'][$asset_key]['hardware_cpes'] = array();
                $this->result['scanned_ips'][$asset_key]['device_types']  = array();


                if(is_array($host['osclass']) && !empty($host['osclass']))
                {
                    foreach($host['osclass'] as $osclass)
                    {
                         if(is_array($osclass) && intval($osclass['accuracy']) >= $max_osclass_accuracy)
                         {
                            $max_osclass_accuracy = intval($osclass['accuracy']);

                            //Getting Operating System
                            $os = $osclass['osfamily'];

                            if (!empty($os))
                            {
                                $os_gen = $osclass['osgen'];
                                $os    .= (!empty($os_gen)) ? ' '.$os_gen : '';
                            }

                            //Setting Operating System -> Add first valid
                            if ($this->result['scanned_ips'][$asset_key]['os'] == '')
                            {
                                $this->result['scanned_ips'][$asset_key]['os'] = $os;
                            }

                            //Setting Device type -> Add all
                            $device_type = $osclass['type'];
                            if (!is_array($this->result['scanned_ips'][$asset_key]['device_types']))
                            {
                                $this->result['scanned_ips'][$asset_key]['device_types'] = array();
                            }
                            $this->result['scanned_ips'][$asset_key]['device_types'][$device_type] = $device_type;


                            //Setting Hardware CPEs -> Add all
                            if (is_array($osclass['cpe']) && !empty($osclass['cpe']))
                            {
                                //Select all hardware
                                if (!is_array($this->result['scanned_ips'][$asset_key]['hardware_cpes']))
                                {
                                    $this->result['scanned_ips'][$asset_key]['hardware_cpes'] = array();
                                }
                                foreach ($osclass['cpe'] as $cpe)
                                {
                                    if(preg_match('/^cpe:\/h/', $cpe))
                                    {
                                        $this->result['scanned_ips'][$asset_key]['hardware_cpes'][$cpe] = $cpe;
                                        $hardware_cpe                                                   = TRUE;
                                    }
                                }
                            }
                        }
                    }
                }

                /*****************************************************
                 *********** Services and Software CPEs **************
                 ****************************************************/


                $this->result['scanned_ips'][$asset_key]['software_cpes'] = array();
                $this->result['scanned_ips'][$asset_key]['services']      = array();

                $protocols = array("tcp","udp","sctp");
                foreach ($protocols as $proto)
                {
                    if (is_array($host[$proto]) && !empty($host[$proto]))
                    {
                        foreach($host[$proto] as $portid => $port)
                        {
                            $state  = $port['state'];

                            //Checking service state
                            if($state != 'open' && $state != '')
                            {
                                continue;
                            }

                            $s_data = array(
                                'port'    => $portid,
                                'proto'   => $proto,
                                'state'   => $state,
                                'service' => 'unknown',
                                'version' => 'unknown',
                            );

                            $s_data['service'] = (!empty($port['name'])) ? $port['name'] : 'unknown';

                            if(is_array($port['cpe']) && !empty($port['cpe']))
                            {
                                foreach ($port['cpe'] as $cpe)
                                {
                                    if(preg_match('/^cpe:\/a/', $cpe) && $s_data['version'] == 'unknown')
                                    {
                                        $s_data['version'] = $cpe;
                                    }

                                    $this->result['scanned_ips'][$asset_key]['software_cpes'][$cpe] = $cpe;

                                    if(preg_match('/^cpe:\/h/', $cpe))
                                    {
                                        $hardware_cpe = TRUE;
                                    }
                                }
                            }

                            //Setting service version if cpe:a not found
                            if ($s_data['version'] == 'unknown')
                            {
                                $product     = (string)(!empty($port['product']) ? $port['product'] : '');
                                $aux_version = (string)(!empty($port['version']) ? $port['version'] : '');
                                $extra_info  = (string)(!empty($port['extrainfo']) ? $port['extrainfo'] : '');

                                $version = array($product, $aux_version, $extra_info);
                                $version = implode(' ', $version);
                                $version = trim($version);

                                if (!empty($version))
                                {
                                    $s_data['version'] = $version;
                                }
                            }

                            $s_key = $s_data['port'].'/'.$s_data['proto'];
                            $this->result['scanned_ips'][$asset_key]['services'][$s_key] = $s_data;
                        }
                    }

                    // Add also network device type if necessary
                    if ($hardware_cpe == TRUE && $this->result['scanned_ips'][$asset_key]['device_types']['general purpose'] == 'general purpose')
                    {
                        $this->result['scanned_ips'][$asset_key]['device_types']['network device'] = 'network device';
                    }

                } // foreach end
            }
        }

        return $this->result;
    }


    /**
     * This function extracts host list from a NMAP json result
     *
     * @param string  $data  Nmap JSON string or Nmap array
     *
     * @return array
     */
    public function get_alive_host_list($data)
    {
        $json = (is_array($data)) ? $data : @json_decode($data, TRUE);

        $this->result = array();

        if(json_last_error() == JSON_ERROR_NONE && !empty($json['result']))
        {
            $json = $json['result'];
            if (is_array($json) && !empty($json))
            {
                foreach ($json as $ip => $host)
                {
                    if (is_array($host['status']) && $host['status']['state'] == 'up')
                    {
                        $this->result[] = $ip;
                    }
                }
            }
        }

        return $this->result;
    }
}

/* End of file nmap_parser.inc */
/* Location: ../include/classes/nmap_parser.inc */
