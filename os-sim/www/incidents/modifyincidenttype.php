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

if (!Session::am_i_admin() && !Session::menu_perms("analysis-menu", "IncidentsTypes")){
    die(ossim_error(_("Sorry, you are not allowed to perform this action")));
}


$options = array ("Checkbox", "Select box", "Radio button", "Slider");

if (GET('ajax_validation') == TRUE)
{
	$validate  = array (
		"descr"              => array("validation" => "OSS_TEXT, OSS_SPACE, OSS_AT, OSS_PUNC_EXT",                        "e_message" => 'illegal:' . _("Description")),
		"custom_namef"       => array("validation" => "OSS_ALPHA, OSS_SPACE, OSS_PUNC_EXT, OSS_SCORE",                    "e_message" => 'illegal:' . _("Field name")),
		"custom_typef"       => array("validation" => "OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_SCORE",                        "e_message" => 'illegal:' . _("Field type")),
		"custom_optionsf"    => array("validation" => "OSS_TEXT, OSS_SPACE, OSS_PUNC_EXT, OSS_SCORE, ';', OSS_NULLABLE",  "e_message" => 'illegal:' . _("Field options")),
		"custom_requiredf"   => array("validation" => "OSS_DIGIT, OSS_NULLABLE",                                          "e_message" => 'illegal:' . _("Required Field")),
	);
			
	
	if ($_GET['name'] == 'custom_typef' && in_array($_GET[$_GET['name']], $options))
    {
		$validate['custom_optionsf']['validation'] = 'OSS_TEXT, OSS_SPACE, OSS_PUNC_EXT, OSS_SCORE, ";"';
	}
		
	$data['status'] = 'OK';
	
	$validation_errors = validate_form_fields('GET', $validate);
	if (is_array($validation_errors) && !empty($validation_errors))	
	{
		$data['status'] = 'error';
		$data['data']   = $validation_errors;
	}
	
	echo json_encode($data);	
}
else
{
	if ($_GET['action'] == 'modify')
	{
		$validate  = array (
			"id"       => array("validation" => "OSS_ALPHA, OSS_SPACE, OSS_PUNC"             , "e_message" => 'illegal:' . _("Id")),
			"descr"    => array("validation" => "OSS_TEXT, OSS_SPACE, OSS_AT, OSS_PUNC_EXT"  , "e_message" => 'illegal:' . _("Description")),
			"custom"   => array("validation" => "OSS_DIGIT, OSS_NULLABLE"                    , "e_message" => 'illegal:' . _("Custom"))
		);
	}
	elseif ($_GET['action'] == 'delete')
	{
		$validate  = array (
			"id"           => array("validation" => "OSS_ALPHA, OSS_SPACE, OSS_PUNC"                 , "e_message" => 'illegal:' . _("Id")),
			"custom_namef" => array("validation" => "OSS_ALPHA, OSS_SPACE, OSS_PUNC_EXT, OSS_SCORE"  , "e_message" => 'illegal:' . _("Field name"))
		);
	}
	elseif ($_GET['action'] == 'modify_ct' || $_GET['action'] == 'add' || $_GET['action'] == 'modify_pos')
	{
		$validate  = array (
			"custom_namef"       => array("validation" => "OSS_ALPHA, OSS_SPACE, OSS_PUNC_EXT, OSS_SCORE",                    "e_message" => 'illegal:' . _("Field name")),
			"custom_typef"       => array("validation" => "OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_SCORE",                        "e_message" => 'illegal:' . _("Field type")),
			"custom_optionsf"    => array("validation" => "OSS_TEXT, OSS_SPACE, OSS_PUNC_EXT, OSS_SCORE, ';', OSS_NULLABLE",  "e_message" => 'illegal:' . _("Field options")),
			"custom_requiredf"   => array("validation" => "OSS_DIGIT, OSS_NULLABLE",                                          "e_message" => 'illegal:' . _("Required Field")),
		);
		
		if (in_array($_POST['custom_typef'], $options)){
			$validate['custom_optionsf']['validation'] = "OSS_TEXT, OSS_SPACE, OSS_PUNC_EXT, OSS_SCORE, ';'";
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
					$txt_error = "<div>"._("We Found the following errors").":</div>
						  <div style='padding:10px;'>".implode("<br/>", $validation_errors)."</div>";				
					
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

					Util::make_form("POST", "incidenttype.php");
					?>
				</body>
			</html>
			<?php
		}
		else
		{			
			$db   = new ossim_db();
			$conn = $db->connect();
			
			if ($_GET['action'] == 'modify')
			{
				$inctype_id    = POST('id');
				$inctype_descr = POST('descr');
				$custom        = intval(POST('custom'));
				
				$location = "incidenttype.php?msg=3";
				Incident_type::update($conn, $inctype_id, $inctype_descr,(($custom==1) ? "custom" : ""));
		    }
			elseif ($_GET['action'] == 'delete')
			{
				$inctype_id    = POST('id');
				$custom_name   = POST('custom_namef');
				$custom_name   = Util::htmlentities($custom_name, ENT_QUOTES);
				Incident_custom::delete_custom($conn, $inctype_id, $custom_name);
				$location = "modifyincidenttypeform.php?id=".urlencode($inctype_id);
			}
			elseif ($_GET['action'] == 'modify_ct')
			{
				$inctype_id       = POST('id');
				$custom_name      = POST('custom_namef');
				$custom_name      = Util::htmlentities($custom_name, ENT_QUOTES);
				$custom_old_name  = POST('old_name');
				$custom_old_name  = Util::htmlentities($custom_old_name, ENT_QUOTES);
				$custom_type      = POST('custom_typef');
				$custom_options   = POST('custom_optionsf');
				$custom_required  = POST('custom_requiredf');
							
				Incident_custom::update_custom($conn, $custom_name, $custom_type, $custom_options, $custom_required, $inctype_id, $custom_old_name);
				$location = "modifyincidenttypeform.php?id=".urlencode($inctype_id);
			}
			elseif ($_GET['action'] == 'add')
			{
				$inctype_id       = POST('id');
				$custom_name      = POST('custom_namef');
				$custom_name      = Util::htmlentities($custom_name, ENT_QUOTES);
				$custom_old_name  = POST('old_name');
				$custom_old_name  = Util::htmlentities($custom_old_name, ENT_QUOTES);
				$custom_type      = POST('custom_typef');
				$custom_options   = POST('custom_optionsf');
				$custom_required  = POST('custom_requiredf');
				
				if ((in_array($custom_type, $options) && $custom_options != '') || !in_array($custom_type, $options))
				{
					$next_ord  = Incident_custom::get_next_ord($conn, $inctype_id);
					$params    = array($inctype_id, $custom_name, $custom_type, $custom_options, $custom_required, $next_ord);
												
					Incident_custom::insert_custom($conn, $params);
					$location = "modifyincidenttypeform.php?id=".urlencode($inctype_id);
				}
			}
			elseif ($_GET['action'] == 'modify_pos')
			{
				$inctype_id       = POST('id');
				$custom_old_name  = POST('old_name');
				$custom_name      = Util::htmlentities($custom_old_name, ENT_QUOTES);
				$custom_oldpos    = POST('oldpos');
				$custom_newpos    = POST('newpos');
				
				Incident_custom::update_ord($conn, $custom_oldpos, $custom_newpos, $inctype_id, $custom_old_name);
				$location = "modifyincidenttypeform.php?id=".urlencode($inctype_id);
			}
			
			$db->close($conn);
			header("Location: $location");
			
		}
	}
}
?>