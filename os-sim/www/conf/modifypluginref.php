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

Session::logcheck('configuration-menu', 'CorrelationCrossCorrelation');


$validate = array(
	'old_plugin_id1'    => array('validation' => 'OSS_DIGIT',  'e_message' => 'illegal:' . _('Old Plugin ID1')),
	'old_plugin_id2'   	=> array('validation' => 'OSS_DIGIT',  'e_message' => 'illegal:' . _('Old Plugin ID2')),
	'old_plugin_sid1'   => array('validation' => 'OSS_DIGIT',  'e_message' => 'illegal:' . _('Old Plugin SID1')),
	'old_plugin_sid2'   => array('validation' => 'OSS_DIGIT',  'e_message' => 'illegal:' . _('Old Plugin SID2')),
	'plugin_id1'        => array('validation' => 'OSS_DIGIT',  'e_message' => 'illegal:' . _('Plugin ID1')),
	'plugin_id2'   	    => array('validation' => 'OSS_DIGIT',  'e_message' => 'illegal:' . _('Plugin ID2')),
	'plugin_sid1'       => array('validation' => 'OSS_DIGIT',  'e_message' => 'illegal:' . _('Plugin SID1')),
	'plugin_sid2'       => array('validation' => 'OSS_DIGIT',  'e_message' => 'illegal:' . _('Plugin SID2'))
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


$old_plugin_id1  = POST('old_plugin_id1');
$old_plugin_id2  = POST('old_plugin_id2');
$old_plugin_sid1 = POST('old_plugin_sid1');
$old_plugin_sid2 = POST('old_plugin_sid2');

$plugin_id1      = POST('plugin_id1');
$plugin_id2      = POST('plugin_id2');
$plugin_sid1     = POST('plugin_sid1');
$plugin_sid2     = POST('plugin_sid2');
	
$validation_errors = validate_form_fields('POST', $validate);
	
$data['status'] = 'OK';
$data['data']   = $validation_errors;

	
if (POST('ajax_validation_all') == TRUE)
{
	if ( is_array($validation_errors) && !empty($validation_errors) )
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
	}
}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title><?php echo gettext('OSSIM Framework');?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache">
	<link type="text/css" rel="stylesheet" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
</head>

<body>
<?php
if ($data['status'] == 'error')
{
	$txt_error = '<div>'._('We Found the following errors').":</div>
				  <div style='padding:2px 10px 5px 10px;'>".implode('<br/>', $validation_errors)."</div>";				
			
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

	Util::make_form('POST', 'pluginref.php');
	exit();
}

$db   = new ossim_db();
$conn = $db->connect();

try
{
    $error = Plugin_reference::change_rule ($conn, $plugin_id1, $plugin_id2, $plugin_sid1, $plugin_sid2, $old_plugin_id1, $old_plugin_id2, $old_plugin_sid1, $old_plugin_sid2);
}
catch(Exception $e)
{
    $error = 1;
}

$msg   = ($error) ? 'unknown_error' : 'updated';

$db->close();
?>
<script type='text/javascript'>document.location.href="pluginref.php?msg=<?php echo $msg?>"</script>  

</body>
</html>