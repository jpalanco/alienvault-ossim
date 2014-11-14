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

require_once dirname(__FILE__) . '/../../../conf/config.inc';

Session::logcheck('environment-menu', 'EventsHidsConfig');


$validation_errors = array();
$os_type           = $_SESSION['_ossec_os_type'];


if (!preg_match('/status|abort|list|purge/', POST('order')))
{
    
    $data['status'] = 'error';
    $data['data']   = _('Error! Action not allowed');
            
    echo json_encode($data);
    exit();
}


$order = POST('order');

switch ($order)
{
    case 'status':  
    case 'abort':    
        
        $validate = array (
            'sensor_ip'   => array('validation' => 'OSS_IP_CIDR_0',      'e_message' => 'illegal:' . _('Sensor IP')),
            'work_id'     => array('validation' => "OSS_HEXDIGIT, '-'",  'e_message' => 'illegal:' . _('Work ID'))
        );
        
        
        $d_data = array (
            'sensor_ip'   => POST('sensor_ip'),
            'work_id'     => POST('work_id')
        );
            
    break;
   
    
    case 'list':
    case 'purge':
        $validate = array (
            'sensor_ip'   => array('validation' => 'OSS_IP_CIDR_0',      'e_message' => 'illegal:' . _('Sensor IP'))
        );
        
        $d_data = array (
            'sensor_ip'   => POST('sensor_ip'),
        );
        
    break;
}  

$validation_errors = validate_form_fields('POST', $validate);    
    
if (is_array($validation_errors) && !empty($validation_errors))
{        
    $data['status'] = 'warning';
    $data['data']   = _('Error! Action could not be completed');
            
    echo json_encode($data);
    exit();
}    
else
{   
    
    $db    = new ossim_db();
    $conn  = $db->connect();        
    
    $res = Av_center::get_system_info_by_ip($conn, $d_data['sensor_ip']);    
     
    if ($res['status'] == 'success')
    {
        $sensor_id = $res['data']['sensor_id'];
        
        if (!Ossec_utilities::is_sensor_allowed($conn, $sensor_id))
        {     
        	 $data['status'] = 'error';
        	 $data['data']   = _('Error! Sensor not allowed');            
        }        
    }
    else
    {
         $data['status'] = 'error';
         $data['data']   = _('Error! Unable to validate sensor IP');
    }
    
    
    if ($data['status'] == 'error')
    {
        $db->close();
        
        echo json_encode($data);
        exit();
    }

    $db->close();
    
    
    try
    {
        if ($order == 'status')
        {
            $data = Ossec_agent::check_deployment_status($d_data, $os_type);        
        }
        else
        {    
            $data = Ossec_agent::execute_deployment_action($d_data, $order, $os_type);   
        }
    }
    catch(Exception $e)
    {
        $data['status'] = 'warning';
        $data['data']   = $e->getMessage();
    }
            
    echo json_encode($data);
    exit();
}
