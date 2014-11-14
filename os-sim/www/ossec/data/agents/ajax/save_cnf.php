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


$tab        = POST('tab');
$sensor_id  = POST('sensor_id');    
$token      = POST('token');
$ac_key     = (empty($_POST['ac_key'])) ? 0 : POST('ac_key');

ossim_valid($tab, OSS_LETTER, OSS_DIGIT, OSS_NULLABLE, '#', 'illegal:' . _('Tab'));
ossim_valid($sensor_id, OSS_HEX,                            'illegal:' . _('Sensor ID'));
ossim_valid($ac_key, OSS_DIGIT,                             'illegal:' . _('Agent config key'));

if (ossim_error())
{
   $error_msg = ossim_get_error_clean();
}
else
{
    if (!Token::verify('tk_agent_cnf', $token))
    {
        $error_msg = Token::create_error_message();
    }
    else
    {        
        $db    = new ossim_db();
        $conn  = $db->connect();
        
        if (!Ossec_utilities::is_sensor_allowed($conn, $sensor_id))
        {
            $error_msg = _('Error! Sensor not allowed');
        }
        
        $db->close();
    }
}


if (!empty($error_msg))
{
    $data['status'] = 'error';
    $data['data']   = $error_msg;
    
    echo json_encode($data);
    exit();
}

if($tab == '#tab2')
{
    $info_error  = NULL;
    $directories = array();
    $ignores     = array();
    $wentries    = array();
    $reg_ignores = array();

    $dir_checks_names = array('realtime', 'report_changes', 'check_all', 'check_sum','check_sha1sum', 'check_size', 'check_owner', 'check_group', 'check_perm');

    unset($_POST['tab']);
    unset($_POST['sensor_id']);

    try
    {
        $conf_data = Ossec_agent::get_configuration_file($sensor_id);
    }
    catch(Exception $e)
    {
        $data['status'] = 'error';
        $data['data']   = $e->getMessage();
        
        echo json_encode($data);
        exit();
    }   

    $node_sys  = "<syscheck>";
        
    $parameters ['frequency']       = POST('frequency'); 
    $parameters ['scan_day']        = POST('scan_day'); 
    $parameters ['scan_time']       = (empty($_POST['scan_time_h']) && empty($_POST['scan_time_m'])) ? NULL : POST('scan_time_h').":".POST('scan_time_m'); 
    $parameters ['auto_ignore']     = POST('auto_ignore'); 
    $parameters ['alert_new_files'] = POST('alert_new_files'); 
    $parameters ['scan_on_start']   = POST('scan_on_start'); 
    
    $regex_wd    = "'monday|tuesday|wednesday|thursday|friday|saturday|sunday'";
    $regex_time  = "'regex:([0-1][0-9]|2[0-3]):[0-5][0-9]'";
    $regex_yn    = "'yes|no'";
    
    $validate  = array (
                'frequency'       => array('validation' => 'OSS_DIGIT' , 'e_message' => 'illegal:' . _('Frequency')),
                'scan_day'        => array('validation' => $regex_wd   , 'e_message' => 'illegal:' . _('Scan day')),
                'scan_time'       => array('validation' => $regex_time , 'e_message' => 'illegal:' . _('Scan time')),
                'auto_ignore'     => array('validation' => $regex_yn   , 'e_message' => 'illegal:' . _('Auto ignore')),
                'alert_new_files' => array('validation' => $regex_yn   , 'e_message' => 'illegal:' . _('Alert new files')),
                'scan_on_start'   => array('validation' => $regex_yn   , 'e_message' => 'illegal:' . _('Scan on start')));
    
    foreach ($parameters as $k => $v)
    {
        if (!empty ($v))
        {
            eval("ossim_valid(\$v, ".$validate[$k]['validation'].", '".$validate[$k]['e_message']."');");
        
            if (ossim_error())
            {
                $info_error[] = ossim_get_error();
                ossim_clean_error();
            }
            else
            {
                $node_sys .= "<$k>$v</$k>";
            }       
                
        }
        
        unset($_POST[$k]);          
    }
            
    $dir  = 0;
    $ign  = 0;
    $went = 0;
    $regi = 0;
    
    $regex = array('dir'   =>  '(.*)_value_dir', 
                   'ign'   =>  '(.*)_value_ign', 
                   'went'  =>  '(.*)_value_went', 
                   'regi'  =>  '(.*)_value_regi');
    
    $err_msn = array ('dir'   =>  _('Directory/File monitored'), 
                      'ign'   =>  _('Directory/File ignored'),
                      'went'  =>  _('Windows registry entry'), 
                      'regi'  =>  _('Registry ignore'));
    
    $keys    = array();  
    
    $indexes = array('dir'   =>  0, 
                     'ign'   =>  0,
                     'went'  =>  0, 
                     'regi'  =>  0);
    
    
    foreach ($_POST as $k => $v)
    {
        if ($v == '')
        {
            continue;
        }        

        foreach ($regex as $i => $r)
        {
            if (preg_match("/$r/", $k, $match))
            {
                $indexes[$i]         = $indexes[$i]++;
                $keys[$i][$match[1]] = $v;
                                
                ossim_valid($v, OSS_ALPHA, OSS_PUNC_EXT, OSS_SLASH, OSS_NULLABLE, 'illegal:' . $err_msn[$i]);
                
                if (ossim_error())
                {
                    $info_error[] = ossim_get_error().'. Input num. ' . $indexes[$i]; 
                    ossim_clean_error();
                }

                break;
            }
        }
    }
    
    if (!empty($info_error))
    {
        $data['status'] = 'error';
        $data['data']   = implode('<br/>', $info_error);
        
        echo json_encode($data);
        exit();
    }   
        
    if (is_array($keys['dir']) && !empty($keys['dir']))
    {   
        foreach ($keys['dir'] as $k => $v)
        {
            $node_sys .= '<directories';
            
            for ($i=0; $i<=9; $i++)
            {
                $name = $dir_checks_names[$i]."_".$k."_".($i+1);
                
                if (isset($_POST[$name]))
                {
                    $node_sys .= " ".$dir_checks_names[$i]."=\"yes\""; 
                }
            }

            $node_sys .= ">$v</directories>";
        }
    }
       
    if (is_array($keys['ign']) && !empty($keys['ign']))
    {   
        foreach ($keys['ign'] as $k => $v)
        {
            $node_sys .= '<ignore';
            $name = $k."_type";
            
            if (isset($_POST[$name]))
            {
                $node_sys .= " type=\"sregex\"";
            }       
            
            $node_sys  .= ">$v</ignore>";
        }
    }
          
    if (is_array($keys['went']) && !empty($keys['went']))
    {
        foreach ($keys['went'] as $k => $v)
        {
            $node_sys .= "<windows_registry>$v</windows_registry>";
        }
    }
    
    if (is_array($keys['regi']) && !empty($keys['regi']))
    {
        foreach ($keys['regi'] as $k => $v)
        {
            $node_sys .= "<registry_ignore>$v</registry_ignore>";
        }   
    }
        
    $node_sys .= '</syscheck>';
                  
    $pattern     = '/\s*[\r?\n]+\s*/';
    $conf_file   = preg_replace($pattern, '', $conf_data['data']);
    $copy_cf     = $conf_file;
    
    $pattern     = array('/<\/\s*syscheck\s*>/');
    $replacement = array("</syscheck>\n");
    $conf_file   = preg_replace($pattern, $replacement, $conf_file);
    
    
    preg_match_all('/<\s*syscheck\s*>.*<\/syscheck>/', $conf_file, $match);
        
    $size_m    = count($match[0]);
    $unique_id = uniqid();
    
    if ($size_m > 0)
    {        
        $pattern = trim($match[0][$ac_key]);
        $copy_cf = str_replace($pattern, $unique_id, $copy_cf);
    }
    else
    {
        if (preg_match("/<\s*agent_config\s*>/", $copy_cf))
        {
            $copy_cf = preg_replace("/<\/\s*agent_config\s*>/", "$unique_id</agent_config>", $copy_cf, 1);
        }
        else
        {
            $copy_cf = "<agent_config>$unique_id</agent_config>";
        }
    }
    
        
    $copy_cf = preg_replace("/$unique_id/", $node_sys, $copy_cf);    

    $conf_data = Ossec_utilities::formatXmlString($copy_cf);

    try
    {
        $data = Ossec_agent::set_configuration_file($sensor_id, $conf_data);
    }
    catch(Exception $e)
    {
        $data['status'] = 'error';
        $data['data']   = $e->getMessage();
    }
    
    echo json_encode($data);
}
elseif($tab == '#tab3')
{ 
    try
    {
        $conf_data = html_entity_decode(base64_decode($_POST['data']), ENT_QUOTES, 'UTF-8');
        
        $data = Ossec_agent::set_configuration_file($sensor_id, $conf_data);
    }
    catch(Exception $e)
    {
        $data['status'] = 'error';
        $data['data']   = $e->getMessage();
    }
    
    echo json_encode($data);
}
else
{
    $data['status'] = 'error';
    $data['data']   = _('Error! Illegal action');
    
    echo json_encode($data);
}

?>