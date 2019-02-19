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
Session::logcheck("environment-menu", "PolicyNetworks");


$validate = array (
	'ngname'      => array('validation'=>'OSS_NOECHARS, OSS_ALPHA, OSS_PUNC',      'e_message' => 'illegal:' . _('Group Name')),
	'descr'       => array('validation'=>'OSS_NULLABLE, OSS_ALL',                  'e_message' => 'illegal:' . _('Description')),
	'ctx'    	  => array('validation'=>'OSS_HEX',                                'e_message' => 'illegal:' . _('Entity')),
	'nets[]'      => array('validation'=>'OSS_ALPHA',                              'e_message' => 'illegal:' . _('Networks')),
	'rrd_profile' => array('validation'=>'OSS_ALPHA, OSS_NULLABLE, OSS_PUNC',      'e_message' => 'illegal:' . _('RRD Profile')),
	'nagios'      => array('validation'=>'OSS_NULLABLE, OSS_DIGIT',                'e_message' => 'illegal:' . _('Availability Monitoring')));

	
if (GET('ajax_validation') == TRUE)
{
	$data['status'] = 'OK';
	
	$validation_errors = validate_form_fields('GET', $validate);
	if ( is_array($validation_errors) && !empty($validation_errors) )	
	{
		$data['status'] = 'error';
		$data['data']   = $validation_errors;
	}
	
	echo json_encode($data);	
	exit();
}

if (!isset($_POST['ajax_validation_all']) || POST('ajax_validation_all') == FALSE)
{
	if ( !Token::verify('tk_ng_form', POST('token')) )
	{
		Token::show_error();
		
		exit();
	}
}

$ctx		  = POST('ctx');
$descr        = POST('descr');
$ngname       = POST('ngname');
$rrd_profile  = POST('rrd_profile');

$networks     = (isset($_POST['nets'] ) && !empty ( $_POST['nets'])) ? Util::clean_array(POST('nets')) : array();

$validation_errors = validate_form_fields('POST', $validate);

$data['status']    = 'OK';
$data['data']      = $validation_errors;
	
if (POST('ajax_validation_all') == TRUE)
{
	if (is_array($validation_errors) && !empty($validation_errors))
	{
		$data['status'] = 'error';
		echo json_encode($data);
	}
	else
	{		
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
	<meta http-equiv="Pragma" content="no-cache">
	<link type="text/css" rel="stylesheet" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
</head>

<body>
    <?php
    if (POST('insert'))
    {
        if ($data['status'] == 'error')
    	{
    		$txt_error = "<div>"._("The following errors occurred").":</div>
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
    		    
    		exit();
    	}
    	
       
        $db   = new ossim_db();
        $conn = $db->connect();
    	
        $new_id = Net_group::insert($conn, $ctx, $ngname, $rrd_profile, $networks, $descr);
            	        
    	$db->close();
        
    	Util::memcacheFlush();
    }
    
    ?>
    <script type='text/javascript'>
        if (!parent.is_lightbox_loaded(window.name))
        {
            document.location.href="netgroup.php?msg=saved";
        }
        else
        {
            document.location.href="netgroup_form.php?id=<?php echo $new_id?>&msg=saved";
        }      
    </script>
	</body>
</html>
