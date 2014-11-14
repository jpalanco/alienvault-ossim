<?php
/*******************************************************************************
** OSSIM Forensics Console
** Copyright (C) 2009 OSSIM/AlienVault
** Copyright (C) 2004 BASE Project Team
** Copyright (C) 2000 Carnegie Mellon University
**
** (see the file 'base_main.php' for license details)
**
** Built upon work by Roman Danyliw <rdd@cert.org>, <roman@danyliw.com>
** Built upon work by the BASE Project Team <kjohnson@secureideas.net>
*/

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
require_once ('av_init.php');
Session::logcheck("analysis-menu", "EventsForensics");
//
list($ip,$ctx,$net_id) = explode(";",GET('ip'));
ossim_valid($ip, OSS_IP_ADDR_0, 'illegal:' . _("ip"));
ossim_valid($ctx, OSS_HEX, 'illegal:' . _("ctx"));
ossim_valid($net_id, OSS_HEX, OSS_NULLABLE, 'illegal:' . _("net_id"));
if (ossim_error()) {
    die(ossim_error());
}
//
require_once 'ossim_db.inc';
$db = new ossim_db();
if (is_array($_SESSION["server"]) && $_SESSION["server"][0]!="")
	$conn = $db->custom_connect($_SESSION["server"][0],$_SESSION["server"][2],$_SESSION["server"][3]);
else
	$conn = $db->connect();

$net = null;
if ($net_id != "")
{
	if ($net_obj = Asset_net::get_object($conn, $net_id))
	{
    	$net = array(
    		"name"  => $net_obj->get_name(),
    		"ips"   => $net_obj->get_ips(),
    		"icon"  => $net_obj->get_icon()
    	);    	
	}
}
else
{
	$net = array_shift(Asset_host::get_closest_net($conn, $ip, $ctx));
}

if (is_array($net)) 
{
	if ($net["icon"]!="") echo "<img src='data:image/png;base64,".base64_encode($net["icon"])."' border='0'> ";
	echo "<b>".$net["name"]."</b> (".$net["ips"].")";
}
else 
{
	echo "<b>$ip</b> "._("not found in home networks");
}
$db->close($conn);
?>
