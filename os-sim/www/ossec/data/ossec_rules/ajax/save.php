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


$data['status'] = 'success';
$data['data']   = NULL;


$file           = $_SESSION['_current_file'];
$sensor_id      = POST('sensor_id');
$new_xml_data   = $_POST['data']; 
$token          = POST('token');

ossim_valid($sensor_id, OSS_HEX,                   'illegal:' . _('Sensor ID'));
ossim_valid($file, OSS_ALPHA, OSS_SCORE, OSS_DOT,  'illegal:' . _('File'));


if (ossim_error())
{
   $data['status'] = 'error';
   $data['data']   = ossim_get_error_clean();
}
else
{
    if (!Token::verify('tk_f_rules', $token))
    {
        $data['status'] = 'error';
        $data['data']   = Token::create_error_message();
    }
    else
    {
        $db    = new ossim_db();
        $conn  = $db->connect();
        
        if (!Ossec_utilities::is_sensor_allowed($conn, $sensor_id))
        {
            $data['status'] = 'error';
            $data['data']   = _('Error! Sensor not allowed');
        }
        
        $db->close();
    }
}


if ($data['status'] == 'error')
{   
    $data['status'] = 'error';
    $data['data']   = _('We found the followings errors:')."<div style='padding-left: 15px; text-align:left;'>".$data['data'].'</div>';
    
    echo json_encode($data);
    exit();
}


if (!Ossec::is_editable($file))
{
    $data['status'] = 'error';
    $data['data']   = _('Error! File not editable');
    
    echo json_encode($data);
    exit();
}


$_SESSION['_current_file'] = $file;
$lk_name       = $_SESSION['lk_name'];
$new_xml_data  = html_entity_decode(base64_decode($new_xml_data),ENT_QUOTES, 'UTF-8');


$xml_obj = new Xml_parser($lk_name);
$xml_obj->load_string($new_xml_data);


if($xml_obj->errors['status'] == FALSE)
{
    $data['status'] = 'error';
    $data['data']   = "<div id='parse_errors'>
                        <span style='font-weight: bold;'>"._('Data in XML file with wrong format')."&nbsp;<a onclick=\"$('#msg_errors').toggle();\"> ["._('View errors')."]</a></span>
                        <br/><div id='msg_errors'>".implode('', $xml_obj->errors['msg'])."</div>
                   </div>";
}
else
{
    try
    {
        Ossec::set_rule_file($sensor_id, $file, $new_xml_data);

        $array_xml = $xml_obj->xml2array();
        $tree_json = Ossec_utilities::array2json($array_xml, $file);
        
        $_SESSION['_tree_json'] = $tree_json;
        $_SESSION['_tree']      = $array_xml;

        $data['data'] = _("$file updated successfully").'###'.base64_encode($tree_json);

    }
    catch(Exception $e)
    {
        $data['status'] = 'error';
        $data['data']   = $e->getMessage();
    }
}

echo json_encode($data);
exit();
?>