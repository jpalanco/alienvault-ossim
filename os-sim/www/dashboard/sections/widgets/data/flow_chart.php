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
require_once AV_MAIN_ROOT_PATH . '/nfsen/conf.php';
require_once AV_MAIN_ROOT_PATH . '/sensor/nfsen_functions.php';


//Checking if we have permissions to go through this section
Session::logcheck("dashboard-menu", "ControlPanelExecutive");
Session::logcheck("environment-menu", "MonitorsNetflows");
		
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
	$height    = GET("height");					//Height of the widget
	$flow_info = unserialize(GET("value")); 	//Params of the flow representation.
	$limit     = $flow_info['range'];
	$type      = $flow_info['class'];
	
} 
else  //If the ID is not empty, we are in the normal case; loading the widget from the dashboard. In this case we get the info from the DB.
{ 
	//Getting the widget's info from DB
	$winfo      = get_widget_data($conn, $id);		//Check it out in widget_common.php
	$flow_info  = $winfo['params'];					//Params of the widget representation, this is: type of chart, legend params, etc.
	$height     = $winfo['height'];
	$limit      = $flow_info['range'];
	$type       = $flow_info['class'];
	
}

//Validation
ossim_valid($limit,		OSS_DIGIT,			'illegal:' . _("Time Range"));
ossim_valid($type,		OSS_DIGIT,			'illegal:' . _("Flow Class"));
ossim_valid($height,	OSS_DIGIT,			'illegal:' . _("Widget Height"));

if (ossim_error())
{
	die(ossim_error());
}


switch ($type)
{
	case 1:
			$type = 'TCP';
			break;
			
	case 2:
			$type = 'UDP';
			break;
			
	case 3:
			$type = 'ICMP';
			break;
			
	case 4:
			$type = 'other';
			break;
			
	default:
			die(_('Wrong type'));

}

$limit  = ($limit < 1 || $limit > 7) ? 1 : $limit;
$end    = time();
$begind = $end - ($limit * 86400); //86400 belongs to 24 hours in seconds --> 24*60*60


$nfsen_sensors = get_nfsen_sensors();

$sensor_list   = '';

foreach ($nfsen_sensors as $flow_id => $sdata) 
{
    if (!Av_sensor::is_channel_allowed($conn, $flow_id))
    {
        continue;
    }
	
	$sname        = Av_sensor::get_nfsen_channel_name($conn, $flow_id);
	$sensor_list .= "$flow_id;$sname:";
}

$sensor_list = preg_replace('/:$/i', '', $sensor_list);


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
	<title><?php echo _("Network Flow Widget") ?></title>

	<script type="text/javascript" src="/ossim/js/jquery.min.js"></script>
	
	
	<script>
	
		$(document).ready(function() 
		{
			var width = $('body').css('width').replace(/px/g, '') - 25;
			var url   = '<?php echo "/ossim/nfsen/rrdgraph.php?cmd=get-detailsgraph&profile=./live&arg=$sensor_list+$type+flows+$begind+$begind+$end+$begind+$end+####+$height" ?>';
			
			url = url.replace('####', width);			
			$("#flow").html("<img src='"+url+"' border='0' height='<?php echo $height - 10?>px' width='"+width+"px'align='middle'>");
		
		});
		
	</script>

</head>
  
<body style="overflow:hidden;width:100%;height:100%">
		<div id='flow' style='height:<?php echo $height - 10 ?>px;margin:0 auto 0 auto;overflow:hidden;text-align:center' ></div>
</body>
</html>

<?php 
$db->close();