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

Session::logcheck("configuration-menu", "PolicyPorts");

if ( !Token::verify('tk_delete_port', GET('token')) )
{
    echo _("Action not allowed");    
    exit();
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" CONTENT="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
</head>

<body>

  <h1> <?php echo gettext("Delete port"); ?> </h1>

<?php
$id = GET('id');
$id = explode('@@', $id);

$port_number   = $id[0];
$protocol_name = $id[1];


ossim_valid($port_number, 		OSS_PORT, 		'illegal:' . _("Port Number"));
ossim_valid($protocol_name, 	OSS_PROTOCOL,	'illegal:' . _("Protocol Name"));

if (ossim_error()) 
{
    die(ossim_error());
}

if (!GET('confirm')) {
?>
    <p> <?php echo gettext("Are you sure"); ?> ?</p>
    <p>
		<a href="<?php echo $_SERVER["SCRIPT_NAME"] . "?id=$id&confirm=yes"; ?>">
		<?php echo gettext("Yes"); ?> </a>&nbsp;&nbsp;&nbsp;<a href="port.php">
		<?php echo gettext("No"); ?> </a>
    </p>
<?php
    exit();
}

$db   = new ossim_db();
$conn = $db->connect();


Port::delete($conn, $port_number, $protocol_name);


$db->close();
?>

    <p> <?php echo gettext("Port group deleted"); ?> </p>
    <p><a href="port.php"><?php echo gettext("Back"); ?> </a></p>
    <?php 
		Util::memcacheFlush();
	?>

</body>
</html>