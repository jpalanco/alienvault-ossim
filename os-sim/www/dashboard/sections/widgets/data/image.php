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


require_once '../widget_common.php';

//Checking if we have permissions to go through this section
Session::logcheck("dashboard-menu", "ControlPanelExecutive");
		
//Setting DB connection		
$db    = new ossim_db();
$conn  = $db->connect();

//Getting the current user
$user  = Session::get_session_user();

//Getting the widget's ID
$id    = GET("id");

//ID could be either numeric or empty. (More info below...)
ossim_valid($id,	OSS_DIGIT, OSS_NULLABLE,	'illegal:' . _("Widget ID"));

if (ossim_error())
{
	die(ossim_error());
}

//Array that will contain the widget's general info.
$winfo = array();

session_write_close();
//If the ID is empty it means that we are in the wizard previsualization. We get all the info from the GET parameters.
if (!isset($id) || empty($id))
{
	$height = GET("height");				//Height of the widget
	$winfo  = json_decode(GET("value"),true); 	//Serialized array with all the widget's info. It is created in the widget's wizard	

	if (is_array($winfo) && !empty($winfo))
	{	
		$content = base64_decode($winfo['content']);

		if (preg_match('/AV@@@/', $content))
		{
			$content = str_replace('AV@@@', '', $content);
			$url     = "tmp/".$content;		//URL of the image

			ossim_valid($url,	OSS_TEXT,	'illegal:' . _("Image Url"));
		}
		else
		{
			$url = $content;		//URL of the image

			ossim_valid($url,	OSS_URL_ADDRESS,	'illegal:' . _("Image Url"));
		}
		
		$adj = $winfo['adjustment'];				//Adjustment of the image.
	}
}
else  //If the ID is not empty, we are in the normal case; loading the widget from the dashboard. In this case we get the info from the DB.
{ 

	//Getting the widget's info from DB
	$winfo  = get_widget_data($conn, $id);		//Check it out in widget_common.php
	$url    = base64_decode($winfo['params']['content']);		//URL of the image
	$media  = base64_encode($winfo['media']);					//Media data

	$height = $winfo['height'];					//Height of the widget
	$adj    = $winfo['params']['adjustment'];	//Adjustment of the image.
	
	if (empty($media))
	{
		ossim_valid($url,		OSS_URL_ADDRESS,	'illegal:' . _("Image Url"));
	}
	else 
	{
		ossim_valid($media,		OSS_BASE64,			'illegal:' . _("Image Url"));
	}
}

//Validation
ossim_valid($adj,		OSS_TEXT,			'illegal:' . _("Image Adjustment"));
ossim_valid($height,	OSS_DIGIT,			'illegal:' . _("Widget Height"));

if (ossim_error())
{
	die(ossim_error());
}

//This offset is used to adjust the image to the widget. The image's size has to be smaller than the widget's height bcz of the widget's header. Right now the offset is 20, the header's height
$offset = 20; // ((intval($height) == 200)? 20 : 20);

//Getting the final height and width of the image depending of the image's adjustment.
switch ($adj)
{
	//Adjust to weight: Both, widht and height are 100%
	case 'adjust_w':
		$img_width = '100%';
		$img_height= '100%';
		break;
	
	//Adjust to height:	The height of the widget (Minus the offset) and width 100%
	case 'adjust_h':
		$img_width = '100%';
		$img_height= ($height-$offset).'px';
		break;
	
	//Original size and other cases: The original parameters of the image.
	case 'original':
	default:
		$img_width = '';
		$img_height= '';

}

//Now we are going to check that the url belongs to an image.
//Before using the function we check again that the url is not empty.
if (empty($media))
{
	if (isset($url) && !empty($url))
	{
		if (!preg_match('/http/', $url))
		{
			$pre_url = "/usr/share/ossim/www/$url";
			$url     = "/ossim/$url";
			$tam     = @getimagesize($pre_url);		//Checking the url belongs to an image: It is checked the image itself, not the extension!!
			
		}
		else
		{
			$tam = @getimagesize($url);		//Checking the url belongs to an image: It is checked the image itself, not the extension!!
		}
		
		if (!$tam)
		{
			$message = _("Wrong Content");	//If the url does not belong to an image, an error is showed.
		}
		
	} 
	else 
	{
		$message = _("No URL found"); 	//If it is empty an error is showed
	}
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<?php
	//If the refresh is enabled, we set it up in the meta.
    if (isset($winfo['refresh']) && $winfo['refresh']!=0)
    {	
        echo('<meta http-equiv="refresh" content="'.$winfo['refresh'].'">');
    }
	?>
	<title><?php echo _("Image Widget")?></title>

</head>
  
<body>
	<table class='transparent' align="center" style='width:100%; height:<?php echo $height - $offset ?>px;'>
		<tr>	
			<?php 
			if ($message == '')  //If the error msg is empty we display the image
			{ 
    			if (empty($media))
    			{ 
    			?>
					<td class='noborder' valign='middle' style='text-align:center;'>
						<img src='<?php echo $url ?>' width='<?php echo $img_width ?>' height='<?php echo $img_height ?>' border='0' align='middle'>
					</td>				
				<?php 
				} 
				else 
				{
				?>
					<td class='noborder' valign='middle' style='text-align:center;'>
						<img src='data:Image/jpeg;base64, <?php echo $media ?>' border='0' width='<?php echo $img_width ?>' height='<?php echo $img_height ?>' border='0' align='middle'>
					</td>						
				<?php 
				} 
				
			} 
			else //Otherwise the error message is displayed.
			{ 
			?>
			
			<td class='noborder' valign='middle' style='text-align:center;padding-bottom:10px;' >
				<?php echo $message ?>
			</td>
			
			<?php 
			} 
			?>		
		</tr>
	</table>
</body>
</html>
<?php 
$db->close();
