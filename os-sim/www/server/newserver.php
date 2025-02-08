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

if (!Session::is_pro())
{ 
	exit();
}


$validate = array (
	"ip"           => array("validation"=>"OSS_IP_ADDR",       "e_message" => 'illegal:' . _("Server IP Addresss")),
	"password"     => array("validation"=>"OSS_PASSWORD",      "e_message" => 'illegal:' . _("Password")),
);

$db   = new ossim_db();
$conn = $db->connect();
	
	
if (GET('ajax_validation') == TRUE)
{       
	$data['status']    = 'OK';
	$validation_errors = array();

	$validation_errors = validate_form_fields('GET', $validate);

	
	if (is_array($validation_errors) && !empty($validation_errors))	
	{
		$data['status'] = 'error';
		$data['data']   = $validation_errors;
	}
	
	if ($data['status'] != 'error' && GET('ip') != '')
	{
    	if (Server::server_ip_exists($conn, GET('ip')))
    	{
        	$data['status']     = 'error';
        	$data['data']['ip'] = _('The IP address already exists');
    	}
	}
	
	$db->close();
	
	echo json_encode($data);	
	exit();
}


//Check Token
if (!isset($_POST['ajax_validation_all']) || POST('ajax_validation_all') == FALSE)
{
	if (!Token::verify('tk_form_new_server', POST('token')))
	{
		Token::show_error(_("Action not allowed"));
		
		$db->close();
		
		exit();
	}
}

$ip       =  POST('ip');
$port     =  40001;
$password_decoded = base64_decode(POST('password'));
$password =  str_replace(POST('token'),'',$password_decoded);

$validation_errors = validate_form_fields('POST', $validate);

$data['status'] = 'OK';	
$data['data']   = $validation_errors;


//If there is no validation error on the IP, then we check that the IP is not already in use
if (empty($data['data']['ip']))
{
	if (Server::server_ip_exists($conn, $ip))
	{
    	$data['data']['ip'] = _('The IP address already exists');
	}
}

//If we have any kind of error, the status will switch into error
if (is_array($data['data']) && !empty($data['data']))
{
	$data['status'] = 'error';
}
	
//If we ara in the post validation, we return data and then exit	
if (POST('ajax_validation_all') == TRUE)
{
    echo json_encode($data);

	$db->close();
	
	exit();
}


?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo _('AlienVault ' . (Session::is_pro() ? 'USM' : 'OSSIM')); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	
	<?php
        //CSS Files
        $_files = array(
            array('src' => 'av_common.css',     'def_path' => TRUE)
        );

        Util::print_include_files($_files, 'css');

    ?>
</head>

<body>
    <?php

	if ($data['status'] == 'error')
	{
		$txt_error = "<div>"._("The following errors occurred").":</div>
					  <div style='padding: 2px 10px 5px 10px;'>".implode("<br/>", $data['data'])."</div>";				
				
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
		
		Util::make_form("POST", "newserverform.php");
		
		$db->close();
		
		exit();
	}   	        
    
	if (!Session::hostAllowed_by_ip_ctx($conn, $ip, Session::get_default_ctx())) 
	{
	    $db->close();
	    
		die(ossim_error(_("You don't have permission to create a new server with this IP Address")));
	}

    // Try to attach a new server
    $alienvault_conn = new Alienvault_conn();
    $provider_registry = new Provider_registry();
    $client = new Alienvault_client($alienvault_conn, $provider_registry);
    $response    = $client->system()->set_component($ip, $password, 'password');
    $return      = @json_decode($response, TRUE);

    if (!$return || $return['status'] == 'error')
    {
        
   		$config_nt = array(
            'content' => $return['message'],
            'options' => array (
                'type'          => 'nf_error',
                'cancel_button' => FALSE
            ),
            'style'   => 'width: 80%; margin: 20px auto; text-align:center;'
        );

        $nt = new Notification('nt_1', $config_nt);
        $nt->show();

		Util::make_form("POST", "newserverform.php");
		
		$db->close();
		
		exit();
    }
    else
    {
        $new_id = strtoupper(str_replace('-','',$return['data']['server_id']));

        if ($return['data']['hostname'] != '')
        {
            $sname = $return['data']['hostname'];
        }
        else
        {
            $sname = 'USM-Server';
        }
    }
    
    $new_id = Server::insert($conn, $new_id, $sname, $ip, $port);
    
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
            document.location.href="newserverform.php?id=<?php echo $new_id?>&update=1";
        } 
             
    </script>
    
</body>
</html>
