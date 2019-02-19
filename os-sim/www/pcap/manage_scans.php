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


ini_set('max_execution_time','300'); 

require_once 'av_init.php';

Session::logcheck('environment-menu', 'TrafficCapture');

$info_error = array();

$jtimeout = 3000;

$db     = new ossim_db();
$dbconn = $db->connect();

$scan   = new Traffic_capture();

$sensors_status = $scan->get_status();

if(!$sensors_status)  
{
    $sensors_status = array();
}

// variables to display notifications
$message_info = '';
$type         = '';
$content      = '';

// Parameters to delete scan

$op           = POST('op');
$scan_name    = POST('scan_name');
$sensor_ip    = POST('sensor_ip');

// Others parameters

$soptions = intval(POST('soptions'));

ossim_valid($op, OSS_NULLABLE, 'delete',                             'illegal:' . _('Option'));
ossim_valid($scan_name, OSS_SCORE, OSS_NULLABLE, OSS_ALPHA, OSS_DOT, 'illegal:' . _('Capture name'));
ossim_valid($sensor_ip, OSS_IP_ADDR, OSS_NULLABLE,                   'illegal:' . _('Sensor ip'));

if(POST('command') == _('Launch capture'))
{
    // Parameters to launch scan
    $timeout  = $parameters['timeout'] = POST('timeout');
    $cap_size = intval(POST('cap_size'));
    
    if($cap_size < 100 || $cap_size > 8000) 
    {
        $cap_size = 4000;
    }
    
    $raw_filter  = POST('raw_filter');
    
    $sensor_data = POST('sensor');
    
    if(!preg_match("/#/",$sensor_data)) 
    {
        $tmp              = explode('-', $sensor_data);
        $sensor_ip        = $parameters['sensor_ip']        = $tmp[0];
        $sensor_interface = $parameters['sensor_interface'] = $tmp[1];
    }
    else 
    {
        $sensor_interface = $parameters['sensor_interface'] = '';
    }
    
    if(!Session::sensorAllowed($sensor_ip))
    {
        $sensor_ip        = '';
        $sensor_interface = '';
    }
    
    $src  = POST('src');
    $dst  = POST('dst');

	// clean ANY
	$src  = trim(preg_replace('/ANY/i', '', $src));
	$dst  = trim(preg_replace('/ANY/i', '', $dst));

    $validate  = array (
        'timeout'          => array('validation' => 'OSS_DIGIT'                     , 'e_message' => 'illegal:' . _('Timeout')),
        'raw_filter'       => array('validation' => "OSS_ALPHA , '\.\|\&\=\<\>\!\^'", 'e_message' => 'illegal:' . _('Raw Filter')),
        'sensor_ip'        => array('validation' => 'OSS_IP_ADDR'                   , 'e_message' => 'illegal:' . _('Sensor')),
        'sensor_interface' => array('validation' => 'OSS_ALPHA, OSS_PUNC'           , 'e_message' => 'illegal:' . _('Interface'))
    );

    foreach ($parameters as $k => $v )
    {
        eval("ossim_valid(\$v, ".$validate[$k]['validation'].", '".$validate[$k]['e_message']."');");

        if (ossim_error())
        {
            $info_error[] = ossim_get_error();
            
            ossim_clean_error();
        }
    }

    // Sources    
    ossim_valid($src, OSS_NULLABLE, OSS_DIGIT, OSS_SPACE, OSS_SCORE, OSS_ALPHA, OSS_PUNC, OSS_NL, '\.\,\/', 'illegal:' . _('Source'));
    
    if(ossim_error())
    {
        $info_error[] = ossim_get_error();
        
        ossim_clean_error();
    }

    if($src != '')
    {
        $all_sources = explode("\n", $src);
        $tsources     = array(); // sources for tshark
        foreach($all_sources as $source)
        {
            $source      = trim($source);
            $source_type = NULL;
            
            if (ossim_error() == FALSE)
            {
                if(!preg_match("/\//", $source)) 
                {
                    if(!preg_match('/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/', $source))
                    {
                        $ips = @array_keys(Asset_host::get_ips_by_name($dbconn, $source));
                        $source = (count($ips)>0) ? $ips[0] : '';
                    } // resolve to ip
                   
                    ossim_valid($source, OSS_IP_ADDR, 'illegal:' . _("Source ip"));
                    
                    $source_type = 'host';
                }
                else 
                {
                    ossim_valid($source, OSS_IP_CIDR, 'illegal:' . _("Source cidr"));
                    
                    $source_type = 'net';
                }
            }
            
            if(ossim_error())  
            {
                $info_error[] = ossim_get_error();
                ossim_clean_error();
            }
            else
            {
                $tsources[] = $source;
            }
        }
    }
    else 
    {
        $tsources = array();
    }
    
    ossim_valid($dst, OSS_NULLABLE, OSS_DIGIT, OSS_SPACE, OSS_SCORE, OSS_ALPHA, OSS_PUNC, OSS_NL, '\.\,\/', 'illegal:' . _('Destination'));
    
    if(ossim_error())  
    {
        $info_error[] = ossim_get_error();
        
        ossim_clean_error();
    }

    // Destinations
    if($dst != '')
    {
        $all_destinations  = explode("\n", $dst);
        $tdestinations     = array(); // sources for tshark
        
        foreach($all_destinations as $destination)
        {
            $destination      = trim($destination);
            $destination_type = NULL;
            
            if (ossim_error() == FALSE)
            {
                if(!preg_match("/\//", $destination)) 
                {
                    if(!preg_match('/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/', $destination))  
                    {  
                        $ips = @array_keys(Asset_host::get_ips_by_name($dbconn, $destination));
                        
                        $destination = (count($ips)>0) ? $ips[0] : '';
                    } // resolve to ip
                    
                    ossim_valid($destination, OSS_IP_ADDR, 'illegal:' . _('Destination ip'));
                    
                    $destination_type = 'host';
                }
                else 
                {
                    ossim_valid($destination, OSS_IP_CIDR, 'illegal:' . _('Destination cidr'));
                    $destination_type = 'net';
                }
            }
            
            if(ossim_error())
            {
                $info_error[] = ossim_get_error();
                ossim_clean_error();
            }
            else
            {
                $tdestinations[] = $destination;
            }
        }
    }
    else 
    {
        $tdestinations = array();
    }
    
    // Launch scan
    
    $info_sensor = $sensors_status[Av_sensor::get_name_by_ip($dbconn, $sensor_ip)];

    if($sensor_ip != '' && $sensor_interface!='' && intval($timeout) > 0 && count($info_error) == 0 && ($info_sensor[0] == 0 || $info_sensor[0] == -1))
    {
        $rlaunch_scan = $scan->launch_scan($tsources, $tdestinations, $sensor_ip, $sensor_interface, $timeout, $cap_size, $raw_filter);
        
        if($rlaunch_scan["status"] === true)
        {
            $content = _('Launching capture... wait a few seconds');
            $type    = 'nf_success';
        }
        else 
        {
            $content = $rlaunch_scan['message'];
            $type    = 'nf_warning';
        }
        
        $jtimeout = 4000;
    }
    else if($info_sensor[0]!= -1 && ($info_sensor[0]== 1 || $info_sensor[0]== 2))
    {
        $content = _('The sensor is busy');
        $type    = 'nf_warning';
    }
}

// Delete scan
if($op == 'delete' && $scan_name != '' && $sensor_ip != '')
{

    $content = _('Deleting capture... wait a few seconds');
    $type    = 'nf_success';
        
    $scan_info = explode('_', $scan_name);
    $users = Session::get_users_to_assign($dbconn);
    
    $my_users = array();
    foreach($users as $k => $v) 
    {  
        $my_users[$v->get_login()] = 1;
    }
    
    if($my_users[$scan_info[1]]==1 || Session::am_i_admin())
    {
        $scan->delete_scan($scan_name,$sensor_ip);
    }
}

// Stop capture
if($op == 'stop' && $sensor_ip != '')
{
 
    if(Session::sensorAllowed($sensor_ip))
    {
        $scan->stop_capture($sensor_ip);
    }
    
    $db->close();
    
    exit();
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php echo gettext("OSSIM Framework  - Traffic capture");?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
</head>
<body>
    <?php
    if (count($info_error) > 0 || $content != '')
    {    
        $config_nt = array(
            'content' => (count($info_error) > 0) ? implode("<br/>", $info_error) : $content,
            'options' => array (
                'type'          => ($type != '')? $type : 'nf_error',
                'cancel_button' => FALSE
            ),
            'style'   => 'width: 80%; margin: 20px auto; text-align: left;'
        ); 

        $nt = new Notification('nt_1', $config_nt);
        $nt->show();
    }
    ?>

    <?php
    $m_url = Menu::get_menu_url('index.php', 'environment', 'traffic_capture', 'traffic_capture');
    ?>
    
    <form id='back_form' method='post' action='<?php echo $m_url ?>'>
        <input type='hidden' name='src' value='<?php echo Util::htmlentities($src) ?>'/>
        <input type='hidden' name='dst' value='<?php echo Util::htmlentities($dst) ?>'/>
        <input type='hidden' name='timeout' value='<?php echo Util::htmlentities($timeout) ?>'/>
        <input type='hidden' name='cap_size' value='<?php echo Util::htmlentities($cap_size) ?>'/>
        <input type='hidden' name='raw_filter' value='<?php echo Util::htmlentities($raw_filter) ?>'/>
        <input type='hidden' name='sensor_ip' value='<?php echo Util::htmlentities($sensor_ip) ?>'/>
        <input type='hidden' name='sensor_interface' value='<?php echo Util::htmlentities($sensor_interface) ?>'/>
        <?php
        if( count($info_error)>0 )
        {
        ?>
        <input type='hidden' name='soptions' value='1'/>
        <?php
        }
        ?>
    </form>
    
    <script type="text/javascript">
        //<![CDATA[
        setTimeout("document.getElementById('back_form').submit()", <?php echo $jtimeout;?>);
        //]]>
    </script>
</body>
</html>

<?php
$db->close();
?>
