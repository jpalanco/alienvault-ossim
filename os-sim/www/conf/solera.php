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

Session::useractive();

if (!Session::menu_perms("configuration-menu", "ConfigurationMain") && !Session::menu_perms("environment-menu", "MonitorsNetflows")) {
	Session::unallowed_section();
	exit();
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <link rel="stylesheet" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    <script src="../js/jquery.min.js" type="text/javascript" ></script>
</head>
<body>
<?php
$from = POST("from");
$to = POST("to");
$src_ip = POST("src_ip");
$src_port = POST("src_port");
$dst_ip = POST("dst_ip");
$dst_port = POST("dst_port");
$proto = POST("proto");
if ($dst_port=="") $dst_port="0";
if ($src_port=="") $src_port="0";
if ($proto=="") $proto="tcp";
ossim_valid($from, OSS_DATETIME, 'illegal:' . _("From date"));
ossim_valid($to, OSS_DATETIME, 'illegal:' . _("To date"));
ossim_valid($src_ip, OSS_IP_ADDR, OSS_NULLABLE, 'illegal:' . _("Src IP"));
ossim_valid($src_port, OSS_DIGIT, 'illegal:' . _("Src port"));
ossim_valid($dst_ip, OSS_IP_ADDR, OSS_NULLABLE, 'illegal:' . _("Dst IP"));
ossim_valid($dst_port, OSS_DIGIT, 'illegal:' . _("Dst port"));
ossim_valid($proto, "tcp|udp", 'illegal:' . _("Protocol")); // tcp/udp only
if (ossim_error()) {
    die(ossim_error());
}
if (POST("action")=="submit") {
	require_once "ossim_conf.inc";
	$conf = $GLOBALS["CONF"];
	$host = $conf->get_conf("solera_host", FALSE);
	$port = $conf->get_conf("solera_port", FALSE);
	$user = $conf->get_conf("solera_user", FALSE);
	$pass = $conf->get_conf("solera_pass", FALSE);
	// Solera DeepSee sample url
	// https://192.168.2.52/deepsee_reports?user=admin&password=Solera#pathString=/timespan/02.02.2011.21.09.11.02.02.2011.21.09.11/tcp_port/57676_and_80/ipv4_address/204.128.123.4_and_10.1.3.5/;reportIndex=0
	// dates
	$from = str_replace(" ",".",str_replace("-",".",str_replace(":",".",$from)));
	$to = str_replace(" ",".",str_replace("-",".",str_replace(":",".",$to)));
	$solera_url = "https://$host:$port/deepsee_reports?user=$user&password=$pass#pathString=/timespan/".$from.".".$to."/";
	// src and dst port
	$values = array(); 
	if ($src_port!="0") $values[] = $src_port;
	if ($dst_port!="0") $values[] = $dst_port;
	if (count($values)>0)
		$solera_url .= $proto."_port/".implode("_and_",$values)."/";
	// src and dst ip
	$values = array(); 
	if ($src_ip!="" && preg_match("/\d+\.\d+\.\d+\.\d+/",$src_ip)) $values[] = $src_ip;
	if ($dst_ip!="" && preg_match("/\d+\.\d+\.\d+\.\d+/",$dst_ip)) $values[] = $dst_ip;
	if (count($values)>0)
		$solera_url .= "ipv4_address/".implode("_and_",$values)."/";
	// end
	$solera_url .= ";reportIndex=0";
	?>
	<script>
		$(document).ready(function() { 
			window.open('<?=$solera_url?>','solera_deepsee','toolbar=0,location=0,menubar=0,modal=yes'); 
			parent.GB_hide();
	 	});
	</script>
	<?
} else {

?>
<center>
<br>
<form action="solera.php" method="post">
<input type="hidden" name="action" value="submit">
<table cellpadding="3">
<th colspan="2">Solera DeepSee &trade; <?=_("Custom submit")?></th>
<tr><td><?=_("Start time")?>:</td><td class="left"><input type="text" name="from" value="<?=$from?>"></td></tr>
<tr><td><?=_("Stop time")?>:</td><td class="left"><input type="text" name="to" value="<?=$to?>"></td></tr>
<tr><td><?=_("IP Protocol")?>:</td><td class="left"><select name="proto">
	<option value="tcp"<? if ($proto=="tcp") echo " selected='selected'";?>>TCP
	<option value="udp"<? if ($proto=="udp") echo " selected='selected'";?>>UDP
</select></td></tr>
<tr><td><?=_("Source")?>:</td><td class="left"><input type="text" name="src_ip" value="<?=$src_ip?>">:<input type="text" name="src_port" size="4" value="<?=$src_port?>"></td></tr>
<tr><td class="noborder"><?=_("Destination")?>:</td><td class="left noborder"><input type="text" name="dst_ip" value="<?=$dst_ip?>">:<input type="text" name="dst_port" size="4" value="<?=$dst_port?>"></td></tr>
</table>
<br><br>
<input type="submit" value="<?=_("Submit")?>">
<form>
</center>
<?
}
?>
</body>
</html>
