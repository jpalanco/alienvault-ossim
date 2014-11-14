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
ossim_valid($id, 		OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Widget ID"));

if (ossim_error()) 
{
    die(ossim_error());
}

//Array that will contain the widget's general info.
$winfo = array();


//If the ID is empty it means that we are in the wizard previsualization. We get all the info from the GET parameters.
if(!isset($id) || empty($id))
{
	$height = GET("height");				//Height of the widget
	$winfo = unserialize(GET("value")); 	//Serialized array with all the widget's info. It is created in the widget's wizard

	if(is_array($winfo) && !empty($winfo))
	{	
		$url   = base64_decode($winfo['content']);		//URL of the RSS
		$feeds = $winfo['feeds'];		//Amount of feeds
	}

} 
else //If the ID is not empty, we are in the normal case; loading the widget from the dashboard. In this case we get the info from the DB.
{  
	//Getting the widget's info from DB
	$winfo  = get_widget_data($conn, $id);	//Check it out in widget_common.php
	$url    = base64_decode($winfo['params']['content']);	//URL of the RSS
	$feeds  = (($winfo['params']['feeds'] != '') ? $winfo['params']['feeds'] : 0);	//Amount of feeds --> 0 means 'ALL' and it is the default value.
	$height = $winfo['height'];				//Height of the widget

}

//Validation
ossim_valid($height,	OSS_DIGIT,			'illegal:' . _("Widget Height"));
ossim_valid($url,		OSS_URL_ADDRESS,	'illegal:' . _("RSS Url"));
ossim_valid($feeds,		OSS_DIGIT,			'illegal:' . _("Number of Feeds"));

if (ossim_error()) 
{
	die(ossim_error());
}
	
//Var which contains the error messages	
$message = '';

//Now we are going to check that the url belongs to an image.
//First at all the url is checked again to see that it is not empty.
if(isset($url) && !empty($url))
{
	$proxy = Util::get_proxy_params($conn);

	if($proxy['type'] == '')
	{
		$header = @get_headers($url, 1);		//Getting the header of the url.

		$header['Content-Type'] = ( !empty($header['Content-Type']) ) ? $header['Content-Type'] : $header['content-type'];

		if (is_array($header['Content-Type']))
		{
			$header['Content-Type'] = array_shift($header['Content-Type']);
		}
		
	}
	else
	{
		$p_opts['only_header']  = true;
		$header_aux             = Util::geturlproxy($url, $proxy, $p_opts);

		@preg_match("/Content-Type:(.*)/i", $header_aux, $found);
		$header['Content-Type'] =  $found[1];
	}
	
	/*If the page doesn't belong to an rss an error will be displayed. 
	  To check this out we get the header to check if it is xml content. */
	if(@preg_match('/xml/', $header['Content-Type']) == 0)
	{ 
		$message = _("This URL does not belong to a RSS Feed.");
    }
	
} 
else 
{	//If the url is empty an error will be displayed.
	$message = _("Not URL found");
	die();
}

$db->close();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<?php
	if(isset($winfo['refresh']) && $winfo['refresh']!=0)
		echo('<meta http-equiv="refresh" content="'.$winfo['refresh'].'">');
	?>
	<title><?php echo _("RSS Widget")?></title>

	
	<link href="../rss/css/styles.css" rel="stylesheet" type="text/css" />

	<script language="javascript" type="text/javascript" src="/ossim/js/jquery.min.js"></script>
	<script language="javascript" type="text/javascript" src="../rss/js/jquery.jfeed.js"></script>
	<script language="javascript" type="text/javascript" src="../rss/js/jquery.aRSSFeed.js"></script>


	<script language="javascript" type="text/javascript">
	$(document).ready( function() {
		$('.RSSAggrCont').aRSSFeed();
	} );
	</script>

</head>
  
<body>
	<table class='transparent' align="center" style='width:100%; height:<?php echo $height -20 ?>px;'>
		<tr>
			
				<?php if($message == '') { //If the error msg is empty the rss will be dosplayed ?>
				<td class='noborder' valign='middle' style='text-align:left;padding-bottom:10px;' >
					<div class='RSSAggrCont' rssnum='<?php echo $feeds ?>' rss_url='<?php echo $url ?>'>
						<div class="loading_rss">
							<img alt='Loading...' src='../rss/images/loading.gif' />
						</div>
					</div>
				</td>
				<?php } else { //If we have had any errors we'll show them here ?>
				<td class='noborder' valign='middle' style='text-align:center;padding-bottom:10px;' >
					<?php echo $message; ?>
				</td>
				<?php } ?>			
		</tr>
	</table>
</body>
</html>
