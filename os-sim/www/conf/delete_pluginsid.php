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
require_once ('classes/Util.inc');
Session::logcheck("configuration-menu", "ConfigurationPlugins");

$plugin_id  = GET('plugin_id');
$sid        = GET('sid');

ossim_valid($plugin_id, OSS_DIGIT,  'illegal:' . _("Plugin ID"));
ossim_valid($sid, OSS_DIGIT,        'illegal:' . _("Plugin SID"));

if (ossim_error()) {
    die(ossim_error());
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title><?php echo _("Delete Event Type");?></title>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<script type="text/javascript" src="../js/jquery.min.js"></script>
</head>

<body>

	<?php
	$db   = new ossim_db();
	$conn = $db->connect();

	$message = _("Can't delete Event Type");

	if ( $plugin_id != "" && $sid != "" ) 
	{
		$error   = Plugin_sid::delete($conn, $plugin_id, $sid);
		$message = ($error) ? _("Can't delete Event Type (not found)") : _("Event type deleted");
		if (!$error) Util::resend_asset_dump();
	}

	$db->close($conn);
	?>

	<h1><?php echo _("Delete Event Type");?></h1>
	<p style='font-size: 12px;'><?php echo $message;?></p>

	<script type="text/javascript">
		$(document).ready(function(){
			setTimeout("document.location.href='pluginsid.php?plugin_id=<?php echo $plugin_id?>'",1000);
		});
	</script>
</body>
</html>