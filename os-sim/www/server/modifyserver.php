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

Session::logcheck("configuration-menu", "PolicyServers");

$validate = array (
	"id"        	  => array("validation"=>"OSS_HEX",                                          "e_message" => 'illegal:' . _("ID")),
	"sname"  	      => array("validation"=>"OSS_ALPHA, OSS_PUNC",                              "e_message" => 'illegal:' . _("Name")),
	"ip"        	  => array("validation"=>"OSS_IP_ADDR",                                      "e_message" => 'illegal:' . _("Ip")),
	"port"      	  => array("validation"=>"OSS_PORT",                                         "e_message" => 'illegal:' . _("Port number")),
	"descr"     	  => array("validation"=>"OSS_TEXT, OSS_NULLABLE, OSS_AT",                   "e_message" => 'illegal:' . _("Description")),
	"correlate"       => array("validation"=>"OSS_ALPHA, OSS_NULLABLE",                          "e_message" => 'illegal:' . _("Correlation")),
	"cross_correlate" => array("validation"=>"OSS_ALPHA, OSS_NULLABLE",                          "e_message" => 'illegal:' . _("Cross Correlation")),
	"store" 		  => array("validation"=>"OSS_ALPHA, OSS_NULLABLE",                          "e_message" => 'illegal:' . _("Store")),
	"reputation"	  => array("validation"=>"OSS_ALPHA, OSS_NULLABLE",                          "e_message" => 'illegal:' . _("Reputation")),
	"qualify" 		  => array("validation"=>"OSS_ALPHA, OSS_NULLABLE",                          "e_message" => 'illegal:' . _("Qualify")),
	"resend_alarms"   => array("validation"=>"OSS_ALPHA, OSS_NULLABLE",                          "e_message" => 'illegal:' . _("Resend Alarms")),
	"resend_events"   => array("validation"=>"OSS_ALPHA, OSS_NULLABLE",                          "e_message" => 'illegal:' . _("Resend Events")),
	"sign" 			  => array("validation"=>"OSS_ALPHA, OSS_NULLABLE",                          "e_message" => 'illegal:' . _("Sign")),
	"multi"			  => array("validation"=>"OSS_ALPHA, OSS_NULLABLE",                          "e_message" => 'illegal:' . _("Multilevel")),
	"sem" 			  => array("validation"=>"OSS_ALPHA, OSS_NULLABLE",                          "e_message" => 'illegal:' . _("Log")),
	"sim"			  => array("validation"=>"OSS_ALPHA, OSS_NULLABLE",                          "e_message" => 'illegal:' . _("Security Events")),
	"alarm_to_syslog" => array("validation"=>"OSS_ALPHA, OSS_NULLABLE",                          "e_message" => 'illegal:' . _("Alarm to Syslog")),
	"remoteadmin"     => array("validation"=>"OSS_ALPHA, OSS_PUNC, OSS_NULLABLE",                "e_message" => 'illegal:' . _("Remote Admin")),
	"remotepass"      => array("validation"=>"OSS_PASSWORD, OSS_NULLABLE",                       "e_message" => 'illegal:' . _("Remote Password")),
	"remoteurl"       => array("validation"=>"OSS_ALPHA, OSS_PUNC, OSS_NULLABLE",                "e_message" => 'illegal:' . _("Remote URL")),
	"setssh"		  => array("validation"=>"OSS_DIGIT, OSS_NULLABLE",                          "e_message" => 'illegal:' . _("setssh")));
	
	
if (GET('ajax_validation') == TRUE)
{
    $data['status']    = 'OK';
	$validation_errors = array();
					
	if (GET('name') == 'rservers[]' && !empty($_GET['rservers']))
	{
		$rservers = $_GET['rservers'];
								
		foreach($rservers as $rserver)
		{
			$rserver  = explode('@', $rserver);
			$fwr_ser  = $rserver[0];
			$fwr_prio = $rserver[1];
			
			ossim_valid($fwr_ser, 	OSS_HEX, 	'illegal:' . _("Forward Servers"));
			ossim_valid($fwr_prio, 	OSS_DIGIT, 	'illegal:' . _("Forward Priority"));

			if (ossim_error()) 
			{
				$validation_errors['rservers[]'] = ossim_get_error_clean();
				ossim_clean_error();
			}
		}
	}
	else
	{
		$validation_errors = validate_form_fields('GET', $validate);
	}
	
	if (is_array($validation_errors) && !empty($validation_errors))	
	{
		$data['status'] = 'error';
		$data['data']   = $validation_errors;
	}
			
	echo json_encode($data);	
	exit();
}


//Check Token
if (!isset($_POST['ajax_validation_all']) || POST('ajax_validation_all') == FALSE)
{
	if (!Token::verify('tk_form_server', POST('token')))
	{
		Token::show_error(_("Action not allowed"));
		exit();
	}
}

    
$id              =  POST('id');
$sname           =  POST('sname');
$ip              =  POST('ip');
$port            =  POST('port');
$descr           =  POST('descr');
$correlate       = (POST('correlate')) ? 1 : 0;
$cross_correlate = (POST('cross_correlate')) ? 1 : 0;
$store           = (POST('store')) ? 1 : 0;
$rep             = (POST('reputation')) ? 1 : 0;
$qualify         = (POST('qualify')) ? 1 : 0;
$resend_events   = (POST('resend_events')) ? 1 : 0;
$resend_alarms   = (POST('resend_alarms')) ? 1 : 0;
$sign            = (POST('sign')) ? 1 : 0;
$multi           = (POST('multi')) ? 1 : 0;
$sem             = (POST('sem')) ? 1 : 0;
$sim             = (POST('sim')) ? 1 : 0;
$alarm_to_syslog = (POST('alarm_to_syslog')) ? 1 : 0;
$remoteadmin     =  POST('remoteadmin');
$remotepass      =  POST('remotepass');
$remoteurl       =  POST('remoteurl');
$setssh          = (POST('setssh')) ? 1 : 0;
$rservers        =  POST('rservers');

$rservers = $_POST['rservers'];
unset($_POST['rservers']);

$validation_errors = validate_form_fields('POST', $validate);
$fwrd_server       = array();
				
if (is_array($rservers) && !empty($rservers))
{
	foreach($rservers as $rserver)
	{
		$rserver  = explode('@', $rserver);
		$fwr_ser  = $rserver[0];
		$fwr_prio = $rserver[1];
		
		ossim_valid($fwr_ser, 	OSS_HEX, 	'illegal:' . _("Forward Servers"));
		ossim_valid($fwr_prio, 	OSS_DIGIT, 	'illegal:' . _("Forward Priority"));

		if (ossim_error()) 
		{
			$validation_errors['rservers[]'] = ossim_get_error_clean();
			ossim_clean_error();
		}
		else
		{
			$fwrd_server[$fwr_ser] = $fwr_prio;
		}
	}
}		

$data['status'] = 'OK';
$data['data']   = $validation_errors;	
	
if (POST('ajax_validation_all') == TRUE)
{
	if (is_array($validation_errors) && !empty($validation_errors))
	{
		$data['status'] = 'error';
		echo json_encode($data);
	}
	else
	{
		$data['status'] = 'OK';
		echo json_encode($data);
	}
	
	exit();
}
else
{
	if (is_array($validation_errors) && !empty($validation_errors))
	{
		$data['status'] = 'error';
		$data['data']   = $validation_errors;
	}
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link type="text/css" rel="stylesheet" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
</head>

<body>

<?php

if (POST('insert') && empty($data['data']['id']))
{
	if ($data['status'] == 'error')
	{
		$txt_error = "<div>"._("We Found the following errors").":</div>
					  <div style='padding: 2px 10px 5px 10px;'>".implode("<br/>", $validation_errors)."</div>";				
				
		$config_nt = array(
			'content' => $txt_error,
			'options' => array (
				'type'          => 'nf_error',
				'cancel_button' => FALSE
			),
			'style'   => 'width: 80%; margin: 20px auto; text-align: left;'
		); 
						
		$nt = new Notification('nt_1', $config_nt);
		$nt->show();
		
		Util::make_form("POST", "newserverform.php?id=$id");
		exit();
	}
		
    $db   = new ossim_db();
    $conn = $db->connect();
	
    if(!isset($resend_alarms)) 
    {
        $resend_alarms = 0;
    }
    
    if(!isset($resend_events))
    { 
        $resend_events = 0;
    }
    
    Server::update($conn, $id, $sname, $ip, $port, $descr, $correlate, $cross_correlate, $store, $rep, $qualify, $resend_alarms, $resend_events, $sign, $sem, $sim, $alarm_to_syslog, $remoteadmin, $remotepass, $remoteurl, $setssh, $fwrd_server);

    Util::resend_asset_dump('servers');
	
	Util::memcacheFlush();
    
	$db->close();
	
	?>
	<script type='text/javascript'>
        if (!parent.is_lightbox_loaded(window.name))
        {
            document.location.href="server.php?msg=updated";
        }
        else
        {
            document.location.href="newserverform.php?id=<?php echo $id?>&update=1";
        }      
    </script>
	<?php
}
else
{
	?>
    <script type='text/javascript'>
    	if (parent.is_lightbox_loaded(window.name))
        {
            <?php
            $config_nt = array(
    			'content' => _("Sorry, operation was not completed due to an unknown error"),
    			'options' => array (
    				'type'          => 'nf_error',
    				'cancel_button' => false
    			),
    			'style'   => 'width: 80%; margin: 20px auto; text-align: left;'
    		); 
    						
    		$nt = new Notification('nt_1', $config_nt);
    		$nt->show();
            ?>
        }
        else
        {
            document.location.href="server.php?msg=unknown_error";
        }  
    </script>
    <?php
}
?>
	
	</body>
</html>