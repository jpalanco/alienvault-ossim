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


require_once ('av_init.php');
require_once ('../widget_common.php');


//Checking if we have permissions to go through this section
Session::logcheck("dashboard-menu", "ControlPanelExecutive");
		
//Setting DB connection			
$db    = new ossim_db();
$conn  = $db->connect();

//Getting the current user
$user  = Session::get_session_user();

//Getting the widget's ID
$id    = GET("id");


//Validation
ossim_valid($id,	OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Widget ID"));

if (ossim_error()) 
{
    die(ossim_error());
}
//End of validation


//Array that will contain the widget's general info.
$winfo = array();


//If the ID is empty it means that we are in the wizard previsualization. We get all the info from the GET parameters.
if (!isset($id) || empty($id))
{

	$height = GET("height");				//Height of the widget
	$winfo  = unserialize(GET("value")); 	//Serialized array with all the widget's info. It is created in the widget's wizard
	
	if (is_array($winfo) && !empty($winfo))
	{	
		$url_id = $winfo['content'];		// AV's Url
	}

} 
else //If the ID is not empty, we are in the normal case; loading the widget from the dashboard. In this case we get the info from the DB.
{	

	//Getting the widget's info
	$winfo  = get_widget_data($conn, $id);	//Check it out in widget_common.php
	$url_id = $winfo['params']['content'];	// AV's Url
	$height = $winfo['height'];				// Widget's Height

}

//Validation
ossim_valid($url_id,	OSS_DIGIT,					'illegal:' . _("AlienVault Url ID"));
ossim_valid($height,	OSS_DIGIT, OSS_NULLABLE, 	'illegal:' . _("Widget Height"));

if (ossim_error()) 
{
	die(ossim_error());
}
//End of validation


$urls_aux = file("../files/internal_urls_list.txt") or exit(_("Unable to get the URL List"));
	
$url = '';	

foreach ($urls_aux as $u)
{		
	if (preg_match("/(^\*)|(^\W)/",$u))
	{
		continue;
	}
	
	$url_values = explode("####", trim($u));
	
	//Validation
	ossim_valid($url_values[2],		OSS_URL_ADDRESS,	'illegal:' . _("Internal Url"));
	ossim_valid($url_values[0],		OSS_DIGIT,			'illegal:' . _("URL ID"));

	if (ossim_error()) 
	{
		continue;
	}
	//End of validation
	
	if ($url_values[0] == $url_id)
	{
		$url = $url_values[2];
		break;
	}
	
}

//Now we are going to check that the url is a valid url.
//First at all the url is checked again to see that it is not empty.
if (isset($url) && !empty($url))
{
	
	//Error flag
	$error = false;
	
	//what to check
	
	//Url to the dashboard are not allowed to avoid cycles...
	if (preg_match("/.*dashboard\/(panel\.php|index\.php)/",$url))
	{
		$error = true;
	}
	
	$url_check = preg_replace("/\.php.*/",".php",$url);
	$url_check = preg_replace("/ossim/","",$url_check);
	$path      = "/usr/share/ossim/www/";

	
	if (!file_exists($path.$url_check)) 
	{
		$error = true;
	}
	
	//If the URL is a valid URL then we redirect to the url.
	if (!$error)
	{
		header("Location: /$url");
	} 
	else //Otherwise an error mesage will be shown.
	{	
		$message = _("Can't access to $url for security reasons");
	}
	
} 
else //Otherwise an error mesage will be shown.
{	
	$message = _("Not URL found");
}


$db->close();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<title><?php echo _("AlienVault URL Widget")?></title>

	</head>
	  
	<body>
		<table class='transparent' align="center" style='width:100%; height:<?php echo $height -20 ?>px;'>
			<tr>
				<td class='noborder' valign='middle' style='text-align:center;padding-bottom:10px;' >
					<span><?php echo $message ?></span>
				</td>		
			</tr>
		</table>
	</body>
</html>