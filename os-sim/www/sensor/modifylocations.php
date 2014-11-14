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

if (!Session::am_i_admin()) 
{
	Session::unallowed_section(null,'noback');
}

$locations_id =  POST('locations_id');
$name         =  POST('name');
$desc         =  POST('desc');
$location     =  POST('search_location');
$longitude    =  POST('longitude');
$latitude     =  POST('latitude');
$cou          =  POST('country');

$validate = array (
	"locations_id"      => array("validation" => "OSS_HEX"                                  , "e_message" => 'illegal:' . _("ID")),
	"name"              => array("validation" => "OSS_ALPHA, OSS_SPACE, OSS_SCORE, OSS_DOT" , "e_message" => 'illegal:' . _("Name")),
	"desc"              => array("validation" => "OSS_TEXT, OSS_NULLABLE"                   , "e_message" => 'illegal:' . _("Description")),
	"search_location"   => array("validation" => "OSS_TEXT"                                 , "e_message" => 'illegal:' . _("Location")),
	"latitude"          => array("validation" => "OSS_DIGIT, '\.\-', OSS_NULLABLE"          , "e_message" => 'illegal:' . _("Latitude")),
	"longitude"         => array("validation" => "OSS_DIGIT, '\.\-', OSS_NULLABLE"          , "e_message" => 'illegal:' . _("Longitude")),
	"country"           => array("validation" => "OSS_LETTER, OSS_NULLABLE"                 , "e_message" => 'illegal:' . _("Country")),
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
else
{	
	//Check Token
	if (!isset($_POST['ajax_validation_all']) || POST('ajax_validation_all') == FALSE)
	{
		if ( !Token::verify('tk_form_wi', POST('token')) )
		{
			Token::show_error(_("Action not allowed"));
			exit();
		}
	}
	
    $validation_errors = validate_form_fields('POST', $validate);
	
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
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
</head>
<body>

<?php
if (POST('insert') && empty($data['data']['locations_id']))
{
    if ($data['status'] == 'error')
	{
		$txt_error = "<div>"._("We Found the following errors").":</div>
					  <div style='padding:10px;'>".implode( "<br/>", $validation_errors)."</div>";				
				
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
				
		Util::make_form("POST", "newlocationsform.php?id=".urlencode($locations_id));
		exit();
	}
		
    $db   = new ossim_db();
    $conn = $db->connect();
	
    Locations::update($conn, $locations_id, $name, $desc, $location, $latitude, $longitude, $cou);
	
	Util::memcacheFlush();
	$db->close();
	
	?>
	<script type='text/javascript'>
        if (!parent.is_lightbox_loaded(window.name))
        {
            document.location.href="locations.php?msg=updated";
        }
        else
        {
            document.location.href="newlocationsform.php?id=<?php echo urlencode($locations_id)?>&update=1";
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
            document.location.href="locations.php?msg=unknown_error";
        }  
    </script>
    <?php
}
?>
</body>
</html>