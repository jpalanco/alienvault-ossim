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


require_once 'av_init.php';
require_once 'nfsen_functions.php';
require_once '../nfsen/conf.php';

Session::logcheck('configuration-menu', 'PolicySensors');


$action  = POST('action');

ossim_valid($action, OSS_LETTER, '_',   'illegal:' . _('Action'));

if (ossim_error())
{
    $data['status']  = 'error';
	$data['data']    = ossim_get_error_clean();

	echo json_encode($data);
    exit();
}
      

$data['status']  = 'error';
$data['data']    = _('Action not allowed');

switch ($action) 
{ 
    case 'nfsen_status':
        
        $sensor_id = POST('sensor_id');
        
        ossim_valid($sensor_id,  OSS_HEX, 'illegal:' . _('Sensor ID'));
        
        if (ossim_error())
        {
            $data['status']  = 'error';
        	$data['data']    = _('Error! Sensor ID not allowed');     
        }
        else
        {
            $sensor_name = strtoupper($sensor_id);
            $data        = is_running($sensor_name);
        }        
        
    break;
    
    case 'delete':        
        
        $sensor_id = POST('sensor_id');
        
        ossim_valid($sensor_id,  OSS_HEX, 'illegal:' . _('Sensor ID'));
        
        if (ossim_error())
        {
            $data['status']  = 'error';
        	$data['data']    = _('Error! Sensor ID not allowed');     
        }
        else
        {
            $sensor_name   = strtoupper($sensor_id);
            $nfsen_sensors = get_nfsen_sensors();              
            
            if ($nfsen_sensors[$sensor_name] != '')
            {
                unset($nfsen_sensors[$sensor_name]);
                set_nfsen_sensors($nfsen_sensors);
                nfsen_reset($nfsen_dir);
    
                // Talk to frameworkd
                try
                {
                    $s = new Frameworkd_socket();
                    $s->write('nfsen action="delsensor" sensorname="'.$sensor_name.'"');
                    
                    $db     = new ossim_db();
                    $conn   = $db->connect();
                    
                    $sensor_ip = Av_sensor::get_ip_by_id($conn, $sensor_id);
                    
                    $db->close();             
                    
                    $data['status']  = 'success';
                	$data['data']    = Util::js_entities(str_replace('IP', $sensor_ip, _('IP now is not configured as a Flow collector')));                
                }
                catch(Exception $e)
                {                
                    $data['status'] = 'error';
                    $data['data']   = Util::js_entities($e->getMessage()); 
                }            
            }
        }
    break;
    
    case 'restart':
        $data = nfsen_start();
    break;
    
    case 'reconfig':
        $data = reconfig_system();
    break;
    
    case 'configure':
        
        $sensor_id  = POST('sensor_id');
        $port       = POST('port');
        $color      = '#'.POST('color');
        $type       = POST('type');
        
        ossim_valid($sensor_id,  OSS_HEX,      'illegal:' . _('Sensor ID'));     
        ossim_valid($port,       OSS_DIGIT,    'illegal:' . _('Port'));
        ossim_valid($color,      OSS_HEXCOLOR, 'illegal:' . _('Color'));
        ossim_valid($type,       OSS_ALPHA,    'illegal:' . _('Type'));
     
        
        if (ossim_error())
        {
            $data['status']  = 'error';
        	$data['data']    = ossim_get_error_clean();     
        }
        else
        {         
            $sensor_name   = strtoupper($sensor_id);
            $nfsen_sensors = get_nfsen_sensors();
            
            $used_ports = array();
            foreach ($nfsen_sensors as $sensor=>$data)
            {
                $used_ports[$data['port']]++; // load used ports in configuration file
            }
            
            if ($used_ports[$port] == '')
            {
                $nfsen_sensors[$sensor_name]['port']  = $port;
                $nfsen_sensors[$sensor_name]['color'] = $color;
                $nfsen_sensors[$sensor_name]['type']  = $type;
    
                set_nfsen_sensors($nfsen_sensors);
                nfsen_reset();
    
                // Talk to frameworkd
                try
                {
                    $s = new Frameworkd_socket();
                    $s->write('nfsen action="addsensor" sensorname="'.$sensor_name.'" port="'.$port.'" type="netflow" color="'.$color.'"');
                    
                    $admin_ip = Util::get_default_admin_ip();
                    
                    $data['status'] = 'success';
                    $data['data']   = Util::js_entities(str_replace('IP', $admin_ip, str_replace('PORT', $port, _('You should now configure your Flows generator to send Flows to IP port PORT'))));                
                }
                catch(Exception $e)
                {
                    $data['status'] = 'error';
                    $data['data']   = Util::js_entities($e->getMessage()); 
                }
            }
            else
            {
                $data['status'] = 'error';
                $data['data']   = Util::js_entities(_('The selected port is used by another sensor')); 
            }
        }
    break;
}

echo json_encode($data);
