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
Session::logcheck("configuration-menu", "CorrelationCrossCorrelation");

$plugin_id1  = REQUEST('plugin_id1');
$plugin_id2  = REQUEST('plugin_id2');
$plugin_sid1 = REQUEST('plugin_sid1');
$plugin_sid2 = REQUEST('plugin_sid2');

ossim_valid($plugin_id1,  OSS_DIGIT, 'illegal:' . _("Plugin ID1"));
ossim_valid($plugin_id2,  OSS_DIGIT, 'illegal:' . _("Plugin ID2"));
ossim_valid($plugin_sid1, OSS_DIGIT, 'illegal:' . _("Plugin SID1"));
ossim_valid($plugin_sid2, OSS_DIGIT, 'illegal:' . _("Plugin SID2"));

if ( ossim_error() ) 
{
    echo ossim_error();
	exit();
}

$db  = new ossim_db();
$conn = $db->connect();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title><?php echo _("Delete Cross-Correlation rule");?></title>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<script type="text/javascript" src="../js/jquery.min.js"></script>
</head>

<body>
	<?php
	
	$message = _("Can't delete Cross-Correlation rule");

	if ( $plugin_id1!="" && $plugin_id2!="" && $plugin_sid1!="" && $plugin_sid2!="" ) 
	{
	    try
	    {
		    $error = Plugin_reference::delete_rule($conn, $plugin_id1, $plugin_id2, $plugin_sid1, $plugin_sid2);
	    }
	    catch(Exception $e)
	    {
	        $error = 1;
	    }
	    
		$message = ($error) ? _("Can't delete Cross-Correlation rule (not found)") : _("Cross-Correlation rule deleted");
	}

	$db->close($conn);
	?>

	<h1><?php echo _("Delete Cross-Correlation rule");?></h1>
	<p style='font-size: 12px;'><?php echo $message;?></p>

	<script type="text/javascript">
		$(document).ready(function(){
			setTimeout("document.location.href='pluginref.php?msg=deleted'",100);
		});
	</script>
</body>

</html>
