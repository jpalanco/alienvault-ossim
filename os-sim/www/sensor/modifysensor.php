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

Session::logcheck('configuration-menu', 'PolicySensors');

$validate = array (
	'sensor_id'      => array('validation' => 'OSS_HEX',                               'e_message' => 'illegal:' . _('ID')),
	'sname'          => array('validation' => 'OSS_HOST_NAME',                         'e_message' => 'illegal:' . _('Name')),
	'ip'             => array('validation' => 'OSS_IP_ADDR', 		  			       'e_message' => 'illegal:' . _('Ip')),
	'rpass'          => array('validation' => 'OSS_NULLABLE, OSS_PASSWORD', 	       'e_message' => 'illegal:' . _('Password')),
	'priority'       => array('validation' => 'OSS_DIGIT',           			       'e_message' => 'illegal:' . _('Priority')),
	'port'           => array('validation' => 'OSS_PORT',                              'e_message' => 'illegal:' . _('Port number')),
	'tzone'          => array('validation' => "OSS_DIGIT, OSS_SCORE, OSS_DOT, '\+'",   'e_message' => 'illegal:' . _('Timezone')),
	'descr'          => array('validation' => 'OSS_NULLABLE, OSS_AT, OSS_TEXT',        'e_message' => 'illegal:' . _('Description')),
	'isolated'       => array('validation' => 'OSS_NULLABLE, OSS_DIGIT',               'e_message' => 'illegal:' . _('Isolated')),
	'neighborsensor' => array('validation' => 'OSS_NULLABLE, OSS_HEX',                 'e_message' => 'illegal:' . _('Neighbor sensor')),
	'newcontext'     => array('validation' => 'OSS_NULLABLE, OSS_ALPHA, OSS_PUNC_EXT', 'e_message' => 'illegal:' . _('New context')),
	'location'       => array('validation' => 'OSS_NULLABLE, OSS_HEX',                 'e_message' => 'illegal:' . _('Location')),
	'entities[]'     => array('validation' => 'OSS_HEX',                               'e_message' => 'illegal:' . _('Entities'))
);

if (GET('ajax_validation') == TRUE)
{
	$data['status'] = 'OK';

	$validation_errors = validate_form_fields('GET', $validate);
	if ( is_array($validation_errors) && !empty($validation_errors) )
	{
		$data['status'] = 'error';
		$data['data']   = $validation_errors;
	}
	else
	{
	    if ($_GET['name'] == 'neighborsensor' && GET('neighborsensor') == '')
	    {
			$data['status']               = 'error';
			$data['data'][$_GET['name']]  = _('A neighbor sensor is needed');
	    }

	    if ($_GET['name'] == 'rpass' && GET('rpass') == '')
	    {
			$data['status']               = 'error';
			$data['data'][$_GET['name']] = _('The root password of the remote system is needed in order to configure it');
	    }

		if ($_GET['name'] == 'ip')
		{
			$sensor_id = GET('sensor_id');
			$ip        = GET($_GET['name']);

			if (preg_match('/,/', $ip))
			{
				$data['status']              = 'error';
				$data['data'][$_GET['name']] = _("Invalid IP address. Format allowed").": nnn.nnn.nnn.nnn <br/>". _("Entered IP"). ": '<strong>".Util::htmlentities($ip)."</strong>'";
			}
			else
			{
				$db             = new ossim_db();
				$conn           = $db->connect();				
				$new_sensor_id  = Av_sensor::get_id_by_ip($conn, $ip);
				$db->close();

				if (!empty($new_sensor_id) && $new_sensor_id != $sensor_id)
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


$sensor_id   = POST('sensor_id');
$sname       = POST('sname');
$ip          = POST('ip');
$rpass       = POST('rpass');
$priority    = POST('priority');
$descr	     = POST('descr');
$port	     = POST('port');
$tzone	     = POST('tzone');
$location    = POST('location');
$entities    = POST('entities');

$validation_errors = validate_form_fields('POST', $validate);

if (empty($validation_errors['ip']))
{
	if (preg_match('/,/', $ip))
	{
		$validation_errors['ip']  = _("Invalid IP address. Format allowed").": nnn.nnn.nnn.nnn <br/>". _("Entered IP"). ": '<strong>".Util::htmlentities($ip)."</strong>'";
	}
	else
	{
		$db             = new ossim_db();
		$conn           = $db->connect();
		$new_sensor_id  = Av_sensor::get_id_by_ip($conn, $ip);
		$db->close();

		if (!empty($new_sensor_id) && $new_sensor_id != $sensor_id)
		{
			$validation_errors['ip'] = _("Error! IP address associated with another sensor");
		}
	}
}

if (empty($validation_errors['rpass']))
{
	if (array_key_exists('rpass', $_POST) && $rpass == '')
	{
		$validation_errors['rpass']  = _('The root password of the remote system is needed in order to configure it');
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
    if (POST('insert') && empty($data['data']['sensor_id']))
    {
        if ($data['status'] == 'error')
    	{
    		$txt_error = "<div>"._("We Found the following errors").":</div>
    					  <div style='padding: 2px 10px 5px 10px;'>".implode( "<br/>", $validation_errors)."</div>";

    		$config_nt = array(
    			'content' => $txt_error,
    			'options' => array (
    				'type'          => 'nf_error',
    				'cancel_button' => false
    			),
    			'style'   => 'width: 80%; margin: 20px auto; text-align: left;'
    		);

    		$nt = new Notification('nt_1', $config_nt);
    		$nt->show();

    		Util::make_form("POST", "newsensorform.php?id=$sensor_id&ip=$ip&sname=$sname");
    		exit();
    	}

        $db   = new ossim_db();
        $conn = $db->connect();

        $newcontext = (POST('newcontext')!= '') ? POST('newcontext') : $sname;
        if (POST('isolated') == 1 && $newcontext != '')
        {
            // Isolated sensor. Creating a new context first
            $new_context_uuid = Session::clone_default_ctx($conn, $newcontext);
            $entities         = array( $new_context_uuid );

            // Refresh current permissions
            $_SESSION['_user_vision'] = Acl::get_user_vision($conn);
        }
        elseif (POST('isolated') == 0 && preg_match("/[a-f\d]{32}/i", POST('neighborsensor')))
        {
            // Use selected sensor context
            $entities = array_keys(Av_sensor::get_ctx_by_id($conn, POST('neighborsensor')));
        }

        try
        {
            $old = new Av_Sensor($sensor_id);
            $old->load_from_db($conn);
            $cproperties = $old->get_properties();

            $new = new Av_Sensor($sensor_id);
            $new->set_properties($cproperties);
            $new->set_name      ($sname);
            $new->set_ip        ($ip);
            $new->set_priority  ($priority);
            $new->set_port      ($port);
            $new->set_tzone     ($tzone);
            $new->set_descr     ($descr);
            $new->set_rpass     ($rpass);

            foreach ($entities as $ctx)
            {
                $new->add_new_ctx($ctx, $ctx);
            }

            // try to attach a component
            // Only when modifying a remote sensor
            if ($cproperties['version'] != '' && !empty($_POST['rpass']))
            {
                $new->set_component($conn);
            }

            $new->save_in_db($conn);

            if ($location != '')
            {
                Locations::insert_related_sensor($conn, $location, $sensor_id);
            }
        }
        catch(Exception $e)
        {
            if (Session::is_pro() && $new_context_uuid != '' && preg_match("/password/", $e->getMessage()))
            {
                Acl::delete_entities($conn, $new_context_uuid);
                // Refresh current permissions
                $_SESSION['_user_vision'] = Acl::get_user_vision($conn);
            }

            $config_nt = array(
                    'content' => $e->getMessage(),
                    'options' => array (
                        'type'          => 'nf_error',
                        'cancel_button' => FALSE
                    ),
                    'style'   => 'width: 80%; margin: 20px auto; text-align:center;'
                );

            $nt = new Notification('nt_1', $config_nt);
            $nt->show();

            $db->close();

            // Detected sensor not inserted yet, back to rpass mode
            if (!empty($_POST['rpass']))
            {
                Util::make_form("POST", "newsensorform.php?ip=$ip");
            }
            else
            {
                Util::make_form("POST", "newsensorform.php?id=$sensor_id&ip=$ip&sname=$sname");
            }

            exit();
        }

        $db->close();

        unset($_SESSION['_sensor_list']);
        ?>

        <script type='text/javascript'>
            if (!top.is_lightbox_loaded(window.name))
            {
                top.frames['main'].location.href="sensor.php?msg=updated";
            }
            else
            {
                top.frames['main'].location.href="newsensorform.php?id=<?php echo $sensor_id?>&ip=<?php echo $ip?>&sname=<?php echo $sname?>&update=1";
            }
        </script>
        <?php
    }
    else
    {
        ?>
        <script type='text/javascript'>
            if (typeof(top.refresh_notifications) == 'function')
            {
                top.refresh_notifications()
            }

            if (top.is_lightbox_loaded(window.name))
            {
                <?php
                $config_nt = array(
                    'content' => _("Sorry, operation was not completed due to an unknown error"),
                    'options' => array (
                        'type'          => 'nf_error',
                        'cancel_button' => FALSE
                    ),
                    'style'   => 'width: 80%; margin: 20px auto; text-align: left;'
                );

                $nt = new Notification('nt_1', $config_nt);
                $nt->show();
                ?>
            }
            else
            {
                top.frames['main'].location.href="sensor.php?msg=unknown_error";
            }
        </script>
        <?php
    }
    ?>

</body>
</html>