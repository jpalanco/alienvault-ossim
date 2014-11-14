<?php
/**
* traffic_capture.inc
*
* File traffic_capture.inc is used to:
*   - To manage traffic captures
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

require_once 'av_handlers.php';

/**
* Traffic_scan Class
*
* Class to manage traffic captures
*
* @package    ossim-framework\Traffic_Capture
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ Alienvault web page
*/
class Traffic_capture
{
    /**
    * Frameworkd error
    *
    * @var string
    * @access private
    */
    private $errmsg;
    
    /**
    * Frameworkd IP
    *
    * @var string
    * @access private
    */
    private $address;
    
    /**
    * Frameworkd port
    *
    * @var string
    * @access private
    */
    private $port;
    
    
    /**
    * Available sensors
    *
    * @var array
    * @access private
    */
    private $sensors;
    
    /**
    * Timeout
    *
    * @var string
    * @access private
    */
    private $timeout;
    
    /**
    * Class constructor
    *
    * This function sets up the class
    */
    public function __construct()
    {
        $this->src = array();
        $this->dst = array();
        
        $this->errmsg = '';

        if (!$GLOBALS['CONF'])
        {
           $GLOBALS['CONF'] = new Ossim_conf();
        }

        $ossim_conf = $GLOBALS['CONF'];

        // Get the port and IP address of the frameworkd
        
        $this->address = $ossim_conf->get_conf('frameworkd_address');

        $this->port    = $ossim_conf->get_conf('frameworkd_port');

        $this->timeout = array('sec' => 10, 'usec' => 0);

        $this->get_available_sensors();
    }
    
    /**
    * This function gets the available sensors
    *
    * @return void
    */ 
    private function get_available_sensors()
    {
        $max_agents   = 50;
        
        $this->sensors = array();
        // try to connect with frameworkd and "getconnectedagents"
        $socket = socket_create(AF_INET, SOCK_STREAM, 0);
        if ($socket < 0)
        {
            $this->errmsg = _("Can't connect with frameworkd").": ". socket_strerror(socket_last_error($socket));
            
            return '';
        }

        /* connect */
        socket_set_block($socket);
        socket_set_option($socket,SOL_SOCKET,SO_RCVTIMEO,  $this->timeout);
        socket_set_option($socket,SOL_SOCKET,SO_SNDTIMEO,  $this->timeout);
        $result = @socket_connect($socket, $this->address, $this->port);

        if (!$result)
        {
            $this->errmsg = _("Can't connect with frameworkd")." ($this->address:$this->port)";
            return "";
        }
        
        /* get total agents */
        $in = 'control action="gettotalagentsconnected"' . "\n";
        $out = '';

        socket_write($socket, $in, strlen($in));
        $out = @socket_read($socket, 5120, PHP_BINARY_READ);
        
        if (!preg_match("/ack(end)?$/",$out))
        {
            $this->errmsg = _("Bad response from frameworkd").": $out";
            
            return '';
        }
        
        preg_match('/gettotalagentsconnected count\=\"(\d+)\"/', $out, $found);
        
        if (count($found)<1)
        {
            $this->errmsg = _("Bad response from frameworkd").": $out";

            return '';
        }
        if ($found[1] < 1)
        {
            $this->errmsg = _("No agents found from frameworkd").": $out";

            return '';
        }

        $total  = $found[1];
        $first  = TRUE;
        $agents = array();

        // Get agent lists with pagination 
        for ($begin = 0; $begin < $total-1 || $first; $begin += $max_agents)
        {
            $in = 'control action="getconnectedagents_p" begin_index="'.(($begin > 0) ? $begin + 1 : $begin).'" end_index="'.($begin + $max_agents).'"' . "\n";

            $out = '';
            socket_write($socket, $in, strlen($in));
            $out = @socket_read($socket, 5120, PHP_BINARY_READ);

            if (!preg_match('/ack(end)?$/',$out))
            {
                $this->errmsg = _("Bad response from frameworkd").": $out";
                return '';
            }
            preg_match('/names=\"(.*?)\"/', $out, $found);
            
            if (count($found)<2)
            {
                $this->errmsg = _("Bad response from frameworkd").": $out";

                return '';
            }
            $found[1] = explode('|',$found[1]);
            foreach ($found[1] as $a)
            {
                $data = explode('=', $a); // $a = "sensor_name=IP"

                $agents[$data[1]] = $data[0];
            }
            
            $first = FALSE;
        }
        socket_close($socket);

        // save all names and check $network perms
        $result = array();

        if(!empty($agents))
        {
            foreach ($agents as $sensor_ip => $sensor_name)
            {
                try
                {
                    //Local IP
                    if ($sensor_ip == '127.0.0.1')
                    {
                        $sensor_ip = Util::get_default_admin_ip();
                    }  

                    $result[$sensor_ip] = array(
                        'name'    => $sensor_name,
                        'ip'      => $sensor_ip,
                        'i_faces' => array_keys(Av_sensor::get_interfaces($sensor_ip))
                    );
                }
                catch (Exception $e)
                {
                    ;
                }
            }
        }

        $this->sensors = $result;
    }


    /**
    * This function returns the available sensors
    *
    * @return array
    */ 
    public function get_sensors()
    {
        return $this->sensors;
    }


    /**
    * This function launches scan
    *
    * @param string $srcs        Source filter
    * @param string $dsts        Destination filter
    * @param string $id          Sensor IP
    * @param string $interface   Sensor interface
    * @param string $duration    Scan duration
    * @param string $cap_size    Capture size
    * @param string $raw_filter  Raw filter
    *
    * @return array
    */ 
    public function launch_scan($srcs, $dsts, $id, $interface, $duration, $cap_size, $raw_filter)
    {
        session_write_close();
        
        $this->scan = array();
        
        // Prepare to launch nmap 
        $socket = socket_create(AF_INET, SOCK_STREAM, 0);
        if ($socket < 0)
        {
            $this->errmsg = _("Can't connect with frameworkd").": ". socket_strerror(socket_last_error($socket));
            
            return array('status' => FALSE, 'message' => _('Bad response from frameworkd'));

        }
        // Connect
        socket_set_block($socket);
        socket_set_option($socket,SOL_SOCKET,SO_RCVTIMEO, $this->timeout);
        socket_set_option($socket,SOL_SOCKET,SO_SNDTIMEO, $this->timeout);
        
        $result = @socket_connect($socket, $this->address, $this->port);

        if (!$result)
        {
            $this->errmsg = _("Can't connect with frameworkd")." ($this->address:$this->port)";

            return array('status' => FALSE, 'message' => _('Bad response from frameworkd'));

        }
        //
        // launch scan
        // control action="net_scan" scan_name="net_scan_user_pepito.pcap" eth="eth0" src_hosts="192.168.2.19,192.168.2.130" dst_hosts="192.168.2.19,192.168.2.130" src_nets="192.168.2.0/24" dst_nets="192.168.2.0/24" id="pepito" timeout="60"
        //
        
        $src_host = array();
        $src_nets = array();
        
        foreach ($srcs as $src)
        {
            if (preg_match('/\d+\/\d+/',$src))
            {
                $src_nets[] = $src;
            }
            else
            {
                $src_host[] = $src;
            }
        }

        $dst_host = $dst_nets = array();
        foreach ($dsts as $dst)
        {
            if (preg_match('/\d+\/\d+/',$dst))
            {
                $dst_nets[] = $dst;
            }
            else
            {
                $dst_host[] = $dst;
            }
        }

        $in = 'control action="net_scan" scan_name="netscan_'.Session::get_session_user().'_'.gmdate("U").'_'.$duration.'_'.$id.'.pcap" eth="'.$interface.'" cap_size="'.$cap_size.'" raw_filter="'.$raw_filter.'" src_hosts="'.implode(",",$src_host).'"  src_nets="'.implode(",",$src_nets).'" dst_hosts="'.implode(",",$dst_host).'" dst_nets="'.implode(",",$dst_nets).'" id="'.$id.'" timeout="'.$duration.'"'. "\n";

        $out = '';
        socket_write($socket, $in, strlen($in));
        $out = @socket_read($socket, 2048, PHP_BINARY_READ);

        if (!preg_match('/ack(end)?$/',$out))
        {
            $this->errmsg = _("Bad response from frameworkd").": $in >> $out";

            return array('status' => FALSE, 'message' => _('Bad response from frameworkd'));
        }
        else if(preg_match('/error=\"(.*?)\"/',$out,$found))
        {
            if($found[1] != 'Success.')
            {
                return array('status' => FALSE, 'message' => _($found[1]));
            }
        }
        socket_close($socket);

        return array('status' => TRUE, 'message' => '');
    }


    /**
    * This function returns the reports list
    *
    * @return array|boolean
    */
    public function get_scans()
    {
        $socket = socket_create(AF_INET, SOCK_STREAM, 0);
        if ($socket < 0)
        {
            $this->errmsg = _("Can't connect with frameworkd").": ". socket_strerror(socket_last_error($socket));

            return FALSE;
        }
        // Connect
        socket_set_block($socket);
        socket_set_option($socket,SOL_SOCKET,SO_RCVTIMEO,  $this->timeout);
        socket_set_option($socket,SOL_SOCKET,SO_SNDTIMEO,  $this->timeout);
        $result = @socket_connect($socket, $this->address, $this->port);
        
        if (!$result)
        {
            $this->errmsg = _("Can't connect with frameworkd")." ($this->address:$this->port)";
            
            return FALSE;
        }
        /* send nmap_report_list for each id */
        
        $reports = array();

        foreach ($this->sensors as $s_data)
        {           
            $in = 'control action="net_scan_capture_list" id="'.$s_data['ip'].'"' . "\n";
             // capture="net_scan_eth0_20110428170400.pcap"
            $out = '';
            $exit = FALSE;
            socket_write($socket, $in, strlen($in));
            while (!$exit && ($out1 = @socket_read($socket, 2048, PHP_BINARY_READ)) !== FALSE)
            {
                $out .= $out1;
                if (preg_match('/ackend$/',rtrim($out1)))
                {
                    $exit = TRUE;
                }
            }

            if (preg_match_all('/capture=\"(.*?)\"/', $out, $found))
            {
                $reports[$s_data['ip']] = $found[1];
            }
        }
        
        socket_close($socket);
        
        return $reports;
    }


    /**
    * This function returns the sensors status
    *
    * @return array|boolean
    */
    public function get_status()
    {
        $socket = socket_create(AF_INET, SOCK_STREAM, 0);
        if ($socket < 0)
        {
            $this->errmsg = _("Can't connect with frameworkd").": ". socket_strerror(socket_last_error($socket));
            
            return FALSE;
        }
        
        // Connect
        socket_set_block($socket);
        socket_set_option($socket,SOL_SOCKET,SO_RCVTIMEO,  $this->timeout);
        socket_set_option($socket,SOL_SOCKET,SO_SNDTIMEO,  $this->timeout);
        $result = @socket_connect($socket, $this->address, $this->port);
        
        if (!$result)
        {
            $this->errmsg = _("Can't connect with frameworkd")." ($this->address:$this->port)";
            
            return FALSE;
        }
        
        // Send nmap_report_list for each id
        $status = array();
        
        foreach ($this->sensors as $s_data)
        {
            $in = 'control action="net_scan_status" id="'.$s_data['ip'].'"' . "\n";
             
             // capture="net_scan_eth0_20110428170400.pcap"

            $out = '';
            socket_write($socket, $in, strlen($in));
            $out = @socket_read($socket, 2048, PHP_BINARY_READ);

            if (!preg_match('/ack(end)?$/', $out))
            {
                $this->errmsg = _("Bad response from frameworkd").": $out";
                
                return FALSE;
            }

            if (preg_match_all('/status=\"(.*?)\"/', $out, $found))
            {
                $status[$s_data['ip']] = $found[1]; // 1 - Running, 0 - Stop/Finished
            }
            else
            {
                $status[$s_data['ip']] = 0; // 1 - Running, 0 - Stop/Finished
            }
        }

        socket_close($socket);
        
        return $status;
    }
    
    
    /**
    * This function returns the scan status
    *
    * @param string $ip  Sensor IP
    *
    * @return array|boolean
    */
    public function get_scan_status($ip)
    {
        // prepare to launch nmap
        $socket = socket_create(AF_INET, SOCK_STREAM, 0);
        if ($socket < 0)
        {
            $this->errmsg = _("Can't connect with frameworkd").": ". socket_strerror(socket_last_error($socket));
            
            return FALSE;
        }
        /* connect */
        socket_set_block($socket);      
        socket_set_option($socket,SOL_SOCKET,SO_RCVTIMEO,  $this->timeout);
        socket_set_option($socket,SOL_SOCKET,SO_SNDTIMEO,  $this->timeout);
        $result = @socket_connect($socket, $this->address, $this->port);
        if (!$result)
        {
            $this->errmsg = _("Can't connect with frameworkd")." ($this->address:$this->port)";
            
            return FALSE;
        }
        /* first send nmap_report_list */  
        $in = 'control action="net_scan_status" id="'.$ip.'"' . "\n";
        $out = '';
        socket_write($socket, $in, strlen($in));
        $out = @socket_read($socket, 2048, PHP_BINARY_READ);
        if (!preg_match('/ack(end)?$/',$out))
        {
            $this->errmsg = _("Bad response from frameworkd").": $out";
            
            return FALSE;
        }
        
        socket_close($socket);
        
        if (preg_match('/status=\"(.*?)\" packets=\"(.*?)\" total_packets=\"(.*?)\" packet_percentage=\"(.*?)\" elapsed_time=\"(.*?)\" total_time=\"(.*)\" time_percentage=\"(.*)\" errno=\"(.*)\" error/', $out, $found))
        {
            return array(
                'status'            => $found[1],
                'packets'           => $found[2],
                'total_packets'     => $found[3],
                'packet_percentage' => $found[4],
                'elapsed_time'      => $found[5],
                'total_time'        => $found[6],
                'time_percentage'   => $found[7],
                'errno'             => $found[8]
            );
        }
        else if (preg_match('/status=\"(.*?)\" errno=\"(.*)\" error/', $out, $found))
        {  
            // No status information
            return array(
                'status'            => $found[1],
                'packets'           => '0',
                'total_packets'     => '0',
                'packet_percentage' => '0',
                'elapsed_time'      => '0',
                'total_time'        => '0',
                'time_percentage'   => '0',
                'errno'             => $found[2]
            );
        }
        else
        { 
            // No output
            return array(
                'status'            => '0',
                'packets'           => '0',
                'total_packets'     => '0',
                'packet_percentage' => '0',
                'elapsed_time'      => '0',
                'total_time'        => '0',
                'time_percentage'   => '0',
                'errno'             => '0'
            );
        }
    }


    /**
    * This function stops a scan
    *
    * @param string $ip  Sensor IP
    *
    * @return array|boolean
    */
    public function stop_capture($ip)
    {
        // prepare to launch nmap
        $socket = socket_create(AF_INET, SOCK_STREAM, 0);
        if ($socket < 0)
        {
            $this->errmsg = _("Can't connect with frameworkd").": ". socket_strerror(socket_last_error($socket));
            
            return FALSE;
        }
        
        /* connect */
        socket_set_block($socket);
        socket_set_option($socket,SOL_SOCKET,SO_RCVTIMEO,$this->timeout);
        socket_set_option($socket,SOL_SOCKET,SO_SNDTIMEO,$this->timeout);
        $result = @socket_connect($socket, $this->address, $this->port);
        if (!$result)
        {
            $this->errmsg = _("Can't connect with frameworkd")." ($this->address:$this->port)";
            
            return FALSE;
        }

        // First send nmap_report_list
        $in = 'control action="net_scan_stop" id="'.$ip.'"' . "\n";
        $out = '';
        socket_write($socket, $in, strlen($in));
        $out = @socket_read($socket, 2048, PHP_BINARY_READ);
        if (!preg_match('/ack(end)?$/',$out))
        {
            $this->errmsg = _("Bad response from frameworkd").": $out";
            
            return FALSE;
        }
        
        preg_match('/errno=\"(.*?)\" error=\"(.*?)\" ackend/', $out, $found);
        socket_close($socket);
        
        if (intval($found[1])==0)
        {
            return( array('status' => $found[1], 'error' => $found[2]) );
        }
        else
        {
            return( array('status' => $found[1], 'error' => $found[2]) );
        }
    }
    
    
    /**
    * This function returns pcap file name
    *
    * @param string $name  Path name
    * @param string $id    Sensor IP
    *
    * @return string
    */ 
    public function get_pcap_file($name, $id)
    {
        $error_codes = array(
            '2' => 'E_GETOPT',
            '3' => 'E_INVALID_ARGS',
            '4' => 'E_SOCKET_TIMEOUT',
            '5' => 'E_SOCKET_ERROR',
            '6' => 'E_BAD_RESPONSE');
        
        $path_to_save_file = '/tmp/' . $id . '_' . $name . '_tshark.pcap';
        
        if (file_exists($path_to_save_file))
        {
            unlink($path_to_save_file);
        }
        
        if (file_exists($path_to_save_file . '_2'))
        {
            unlink($path_to_save_file . '_2');
        }
        
        exec ("touch $path_to_save_file");

        $command    = "python /usr/share/ossim/www/pcap/getpcap.py -f '" . $this->address . "' -l '" . $this->port . "' -p '$name' -a '$id' -t '$path_to_save_file'";

        $return_var = '';

        $output     = array();

        exec ( $command , $output , $return_var );

        if(intval($return_var) != 0)
        {
            if($error_codes[$return_var] != '')
            {
                return _('Error code: ').$error_codes[$return_var];
            }
            else
            {
                return _('Error code: ').$return_var;
            }
        }

        return $path_to_save_file;
    }
    
    
    /**
    * This function deletes a scan result in a sensor
    *
    * @param string $name  Path name
    * @param string $id    Sensor IP
    *
    * @return void
    */
    public function delete_scan($name, $id)
    {
        // Prepare to launch nmap
        $socket = socket_create(AF_INET, SOCK_STREAM, 0);
        if ($socket < 0)
        {
            $this->errmsg = _("Can't connect with frameworkd").": ". socket_strerror(socket_last_error($socket));
            return FALSE;
        }
        /* connect */
        socket_set_block($socket);
        socket_set_option($socket,SOL_SOCKET,SO_RCVTIMEO,$this->timeout);
        socket_set_option($socket,SOL_SOCKET,SO_SNDTIMEO,$this->timeout);
        $result = @socket_connect($socket, $this->address, $this->port);
        if (!$result)
        {
            $this->errmsg = _("Can't connect with frameworkd")." ($this->address:$this->port)";
            
            return FALSE;
        }
        
        //* First send nmap_report_list
        $in = 'control action="net_scan_capture_delete" id="'.$id.'" path="'.$name.'"' . "\n";
        $out = '';
        socket_write($socket, $in, strlen($in));
        $out = @socket_read($socket, 2048, PHP_BINARY_READ);
        
        if (!preg_match('/ack(end)?$/',$out))
        {
            $this->errmsg = _("Bad response from frameworkd").": $out";
            
            return FALSE;
        }
        socket_close($socket);
    }
    
    
    /**
    * This function returns the last Frameworkd error
    *
    * @return string
    */
    public function err()
    { 
        return $this->errmsg;
    }

}

/* End of file traffic_capture.inc */
/* Location: ../include/classes/traffic_capture.inc */