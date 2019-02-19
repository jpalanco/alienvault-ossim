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
require_once dirname(__FILE__) . '/../../conf/config.inc';

Session::logcheck('environment-menu', 'EventsHidsConfig');


$ip 	   = GET('ip');
$sensor_id = GET('sensor');
$token     = GET('token');

$txt_error = NULL;

$db    	   = new ossim_db();
$conn  	   = $db->connect();


ossim_valid($ip, 		 OSS_IP_ADDR,  'illegal:' . _('Ip Address'));
ossim_valid($sensor_id,	 OSS_HEX,      'illegal:' . _('Sensor'));


if (ossim_error())
{
   $txt_error = ossim_get_error_clean();
}
else
{
    if (!Token::verify('tk_al_enable', $token))
    {
    	$txt_error = Token::create_error_message();
    }
    else
    {
        if (!Ossec_utilities::is_sensor_allowed($conn, $sensor_id))
        {
        	$txt_error = _('Error! Sensor not allowed');
        } 
    }
}


if (empty($txt_error))
{   
    try
	{
    	$agentless = Ossec_agentless::get_object($conn, $sensor_id, $ip);
    	
    	if (is_object($agentless) && !empty($agentless))
    	{
    		if ($agentless->get_status() != 0)
    		{
    			Ossec_agentless::delete_from_config($sensor_id, $ip);
    			$agentless->set_status($conn, 0); 			
    		}
    		else
    		{
    			Ossec_agentless::save_in_config($conn, $sensor_id, array($agentless));    			
    		}
    	}
    	else
    	{
    		$txt_error = _('Agentless not found');
    	}  	
	}
	catch(Exception $e)
	{
    	$txt_error = $e->getMessage();
	}
}	
	
$db->close();	
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title> <?php echo _('OSSIM Framework'); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<script type="text/javascript" src="/ossim/js/jquery.min.js"></script>
</head>

<body>
	
	<h1><?php echo gettext('Enable/Disable Agentless Host');?></h1>

    <?php	
    if (!empty($txt_error))
    {
    	Util::print_error($txt_error);	
    	Util::make_form('POST', '/ossim/ossec/views/agentless/agentless.php');
    }
    else
    {    	    	
    	$state = ($agentless->get_status() == 0) ? 'enabled' : 'disabled';
    	echo '<p>'._("Host successfully $state").'</p>';
    	echo "<script type='text/javascript'>document.location.href='/ossim/ossec/views/agentless/agentless.php'</script>";
    }
    ?>
</body>
</html>
