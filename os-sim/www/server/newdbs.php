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

Session::logcheck('analysis-menu', 'EventsForensics');

$validate = array (
	'db_name'  => array('validation' => 'OSS_ALPHA, OSS_PUNC',  'e_message' => 'illegal:' . _('Server Name')),
	'ip'       => array('validation' => 'OSS_IP_ADDR',          'e_message' => 'illegal:' . _('Ip address')),
	'port'     => array('validation' => 'OSS_PORT',             'e_message' => 'illegal:' . _('Port number')),
	'user'     => array('validation' => 'OSS_USER',             'e_message' => 'illegal:' . _('User')),
	'pass'     => array('validation' => 'OSS_PASSWORD',         'e_message' => 'illegal:' . _('Password')),
    'pass2'    => array('validation' => 'OSS_PASSWORD',         'e_message' => 'illegal:' . _('Password Confirmation'))
);

if (GET('ajax_validation') == TRUE)
{
    $data['status'] = 'OK';

	$validation_errors = validate_form_fields('GET', $validate);
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
	if (!Token::verify('tk_db_form', POST('token')))
	{
		Token::show_error();
		exit();
	}
}


$db_name  =  POST('db_name');
$ip       =  POST('ip');
$port     =  POST('port');
$user     =  POST('user');
$pass     =  POST('pass');
$pass2    =  POST('pass2');

$validation_errors = validate_form_fields('POST', $validate);

if($pass != $pass2)
{
	$validation_errors['pass'] = _("Password mismatch in fields 'Password'");
}

//Validating icon format and size

$icon = '';
if (is_uploaded_file($_FILES['icon']['tmp_name']))
{
   $icon = file_get_contents($_FILES['icon']['tmp_name']);
}

if ($icon != '')
{
	$image = @imagecreatefromstring($icon);

	if (!$image || imagesx($image) > 32 || imagesy($image) > 32)
	{
		$validation_errors['icon'] = _('Image format is not allowed. Allowed only 32x32 PNG images');
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
	<title> <?php echo gettext('OSSIM Framework'); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
</head>
<body>

<?php

   	if ($data['status'] == 'error')
	{
		$txt_error = '<div>'._('We Found the following errors').":</div>
					  <div style='padding: 2px 10px 5px 10px;'>".implode('<br/>', $validation_errors)."</div>";


		$config_nt = array(
			'content'  =>  $txt_error,
			'options'  =>  array (
				'type'           =>  'nf_error',
				'cancel_button'  =>  FALSE
			),
			'style'    =>  'width: 80%; margin: 20px auto; text-align: left;'
		);

		$nt = new Notification('nt_1', $config_nt);
		$nt->show();


		Util::make_form('POST', 'newdbsform.php');

		exit();
	}

    $db   = new ossim_db();
    $conn = $db->connect();

    Databases::insert($conn, $db_name, $ip, $port, $user, $pass, $icon);

	Util::memcacheFlush();

	$db->close();


    ?>
    <script type='text/javascript'>document.location.href="dbs.php?msg=created";</script>

</body>
</html>