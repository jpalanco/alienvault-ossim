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

$version = $conf->get_conf('ossim_server_version');

Session::logcheck('configuration-menu', 'PolicySensors');

$validate = array (
	'sname'       => array('validation' => 'OSS_HOST_NAME',                        'e_message' => 'illegal:' . _('Name')),
	'ip'          => array('validation' => 'OSS_IP_ADDR', 		  				   'e_message' => 'illegal:' . _('Ip')),
	'priority'    => array('validation' => 'OSS_DIGIT',           				   'e_message' => 'illegal:' . _('Priority')),
	'port'        => array('validation' => 'OSS_PORT',                             'e_message' => 'illegal:' . _('Port number')),
	'tzone'       => array('validation' => "OSS_DIGIT, OSS_SCORE, OSS_DOT, '\+'",  'e_message' => 'illegal:' . _('Timezone')),
	'descr'       => array('validation' => 'OSS_NULLABLE, OSS_ALL',                'e_message' => 'illegal:' . _('Description')),
	'location'    => array('validation' => 'OSS_NULLABLE, OSS_HEX',                'e_message' => 'illegal:' . _('Location')),
	'entities[]'  => array('validation' => 'OSS_HEX',                              'e_message' => 'illegal:' . _('Entities')));
	

if (GET('ajax_validation') == TRUE)
{
	$data['status'] = 'OK';
	
	$validation_errors = validate_form_fields('GET', $validate);
	
	if (is_array($validation_errors) && !empty($validation_errors))	
	{
		$data['status'] = 'error';
		$data['data']   = $validation_errors;
	}
	else
	{
		if ($_GET['name'] == 'ip')
		{
			$ip = GET($_GET['name']);
			
			if (preg_match('/,/', $ip))
			{
				$data['status']              = 'error';
				$data['data'][$_GET['name']] = _('Invalid IP address. Format allowed').": nnn.nnn.nnn.nnn <br/>". _('Entered IP'). ": '<strong>".Util::htmlentities($ip)."</strong>'";
			}
			else
			{
				$db     = new ossim_db();
				$conn   = $db->connect();	
				$aux_id = Av_sensor::get_id_by_ip($conn, $ip);
        		$db->close();
        									
        		if (!empty($aux_id)) 
        		{
        			$data['status']              = 'error';
					$data['data'][$_GET['name']] = _('Error! IP address associated with another sensor');
        		}
			}
		}
	}
	
	echo json_encode($data);	
	exit();
}


//Check Token
if (!isset($_POST['ajax_validation_all']) || POST('ajax_validation_all') == FALSE)
{
	if (!Token::verify('tk_form_s', POST('token')))
	{
		Token::show_error(_('Action not allowed'));
		
		exit();
	}
}


$sname       = POST('sname');
$ip          = POST('ip');
$priority    = POST('priority');
$port	     = POST('port');
$tzone       = POST('tzone');
$descr	     = POST('descr');
$location    = POST('location');
$entities    = POST('entities');


$validation_errors = validate_form_fields('POST', $validate);

if (empty($validation_errors['ip']))
{
	if (preg_match('/,/', $ip))
	{
		$validation_errors['ip']  = _('Invalid IP address. Format allowed').": nnn.nnn.nnn.nnn <br/>". _('Entered IP'). ": '<strong>".Util::htmlentities($ip)."</strong>'";
	}
	else
	{
		$db     = new ossim_db();
		$conn   = $db->connect();
		$aux_id = Av_sensor::get_id_by_ip($conn, $ip);
		$db->close();
									
		if (!empty($aux_id)) 
		{
			$validation_errors['ip'] = _('Error! IP address associated with another sensor');
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
    if (POST('insert'))
    {
    	if ($data['status'] == 'error')
    	{
    		$txt_error = "<div>"._('The following errors occurred').":</div>
    					  <div style='padding: 2px 10px 5px 10px;'>".implode( "<br/>", $validation_errors)."</div>";				
    				
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
    		
    		Util::make_form('POST', 'newsensorform.php');
    		exit();
    	}
    		
        $db     = new ossim_db();
        $conn   = $db->connect();

        $new_id = Util::uuid();
        
        try
        {
    	    $new = new Av_Sensor($new_id);    	    
    	    
    	    $new->set_properties(array(
    	        'version'          => '',
    	        'has_nagios'       => 0,
    	        'has_ntop'         => 1,
    	        'has_vuln_scanner' => 1,
    	        'has_kismet'       => 0
    	    ));    	    
    	    $new->set_name($sname);
    	    $new->set_ip($ip);
    	    $new->set_priority($priority);
    	    $new->set_port($port);
    	    $new->set_tzone($tzone);
    	    $new->set_descr($descr);	    
    	    
    	    foreach ($entities as $ctx)
    	    {
        	    $new->add_new_ctx($ctx, $ctx);
    	    }
    	    
    	    $new->save_in_db($conn);    	    
    	        	        	    
    	    if ($location != '') 
    	    {
        	    Locations::insert_related_sensor($conn, $location, $new_id);
    	    }    	    
        }
        catch(Exception $e)
        {
       		$config_nt = array(
                    'content' => $e->getMessage(),
                    'options' => array (
                        'type'          => 'nf_error',
                        'cancel_button' => false
                    ),
                    'style'   => 'width: 80%; margin: 20px auto; text-align:center;'
                ); 

            $nt = new Notification('nt_1', $config_nt);
            $nt->show();
    		
    		$db->close();
    		exit();
        }
	    
    	$db->close();    	
 
        unset($_SESSION['_sensor_list']);
    }
    ?>
    
    <script type='text/javascript'>
        if (!top.is_lightbox_loaded(window.name))
        {
            top.frames['main'].location.href="sensor.php?msg=updated";
        }
        else
        {               
            top.frames['main'].location.href="modifysensorform.php?id=<?php echo $sensor_id?>&ip=<?php echo $ip?>&sname=<?php echo $sname?>&update=1";       
        }
    </script>	

    </body>
</html>
