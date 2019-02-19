<?php
/**
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
*/
ini_set('include_path', '/usr/share/ossim/include');

require_once 'av_init.php';

$option = $argv[1];
$data_1 = $argv[2];
$data_2 = $argv[3];
$data_3 = $argv[4];

$result = '';

$db     = new ossim_db();
$dbconn = $db->connect();

$_SESSION['_user'] = 'admin';

switch ($option) {

    case 'get_ctx':
    
        if(preg_match("/^([a-f\d]{32})#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/i", $data_1, $found))
        {   
            // host_id#IP

            $result = Asset_host::get_ctx_by_id($dbconn, $found[1]); 
        }
        else if(preg_match("/^([a-f\d]{32})#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\/\d{1,2}$/i", $data_1, $found))
        {
            // net_id#CIDR
            
            $result = Asset_net::get_ctx_by_id($dbconn, $found[1]);
        }
        else
        {
            $filters = array( 'where' => 'hostname = "' . $data_1 . '" OR fqdns LIKE "%' . $data_1 . '%"' );
        
            $_hosts_data = Asset_host::get_basic_list($dbconn, $filters);
            $hosts_list  = $_hosts_data[1];
            $total       = count($hosts_list);
            
            if ( $total > 0 )
            {
                $host_id = key($hosts_list);
                
                $result  = $hosts_list[$host_id]['ctx'];
            }
            else
            {
                $result = '';
            }
            
        }
        
        break;
    
    case 'get_sensor_ip':
            
        $result    = Av_sensor::get_ip_by_id($dbconn, $data_1);

        break;
        
    case 'update_vuln_jobs_assets':
        
        $action   = $data_1;
        $job_id   = $data_2;
        $job_type = $data_3;
        
        $result   = Vulnerabilities::update_vuln_job_assets($dbconn, $action, $job_id, $job_type);

        break;
   
    case 'get_system_uuid':
    
        $result    = Util::get_encryption_key();
        
        break;
    
    case 'get_varhex':
    
        $result = bin2hex ( inet_pton ( $data_1 ) );
        
        break;
    
    case 'insert_host':
        
        list($hostip, $ctx, $hostname, $aliases) = explode('|', base64_decode($data_1));
        
        $hostid = key(Asset_host::get_id_by_ips($dbconn, $hostip, $ctx));
        
        if( !Asset_host::is_in_db($dbconn, $hostid) )
        {         
            list($sensor_list, $total) = Av_sensor::get_list($dbconn, array('where' => "acl_sensors.entity_id=UNHEX('$ctx')"));
            
            $sensors     =  array_keys($sensor_list);
            
            try
            {
                $hostid = Util::uuid();
                
                $host   = new Asset_host($dbconn, $hostid);
                
                $host->set_name($hostname);
                
                $host->set_ctx($ctx);
                
                $host_ip = array();
                
                $ips[$hostip] = array(
                   'ip'   =>  $hostip,
                   'mac'  =>  NULL,
                );
                
                $host->set_ips($ips);
                
                $host->set_sensors($sensors);
                
                $host->set_fqdns($aliases);
                
                $host->save_in_db($dbconn);
                
                $result = 'Host ID: ' . $hostid;
            }
            catch(Exception $e)
            {
                $result = 'Impossible to save the host';
            }
        }
        else
        {
	        $result = 'The host already exists. Host ID: ' . $hostid;
        }
        
        break;
        
    case 'update_aliases':
    
        list($hostip, $ctx, $aliases) = explode('|', base64_decode($data_1));
        
        $hostid = key(Asset_host::get_id_by_ips($dbconn, $hostip, $ctx));
        
        if( Asset_host::is_in_db($dbconn, $hostid) )
        {
            try
            {
                $h_object = new Asset_host($dbconn, $hostid);
            
                $h_object->load_from_db($dbconn);
            
                $h_object->set_fqdns($aliases);
            
                $h_object->save_in_db($dbconn);
            
                $result = 'Host aliases updated';
            }
            catch(Exception $e)
            {
                $result = 'Impossible to save the host aliases';
            }
            
        }

        break;
        
    case "get_host_id":
        
        list($hostip, $ctx) = explode('|', base64_decode($data_1));
        
        $result = key(Asset_host::get_id_by_ips($dbconn, $hostip, $ctx));
                
        break;
        
    default:
    
        $db->close();
        
        die("The option isn't correct\n");
}

$db->close();

echo "$result\n";

?>
