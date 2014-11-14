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

Session::logcheck('analysis-menu', 'EventsForensics');

if (!Session::is_pro())
{
	exit();
}

if (!Token::verify('tk_delete_server', GET('token')))
{
    header("Location: server.php?msg=unallowed");
    exit();
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
</head>
<body>

    <?php
    $id = GET('id');
    ossim_valid($id, OSS_HEX, 'illegal:' . _("Server ID"));

    if (ossim_error())
    {
        die(ossim_error());
    }

    // Check if deleting server is the local one

    $conf = $GLOBALS["CONF"];
    $local_id = $conf->get_conf("server_id");

    if ($local_id == Util::uuid_format($id))
    {
    	?>
    	<script type='text/javascript'>document.location.href="server.php?msg=nodelete"</script>
    	<?php
    	exit();
    }

    $db   = new ossim_db();
    $conn = $db->connect();

    // Check hierarchy
    $parent_servers = Server::get_parent_servers($conn, $id);
    foreach ($parent_servers as $p_id => $p_name)
    {
        if (Util::uuid_format($p_id) == $local_id)
        {
    	?>
    	<script type='text/javascript'>document.location.href="server.php?msg=nodeleteremote"</script>
    	<?php
    	exit();
        }
    }    

    Server::delete($conn, $id);

    Util::resend_asset_dump('servers');

    $db->close();
    ?>
	<h1><?php echo gettext("Delete server");?></h1>
    <p><?php echo gettext("Server deleted");?></p>

    <?php

        Util::memcacheFlush();
?>
    <script type='text/javascript'>document.location.href="server.php"</script>
</body>
</html>