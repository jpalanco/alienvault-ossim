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

if (!Session::am_i_admin()) 
{
	Session::unallowed_section(null,'noback');
}


if (!Token::verify('tk_delete_location', GET('token')))
{
    header("Location: locations.php?msg=unallowed"); 
    exit();
}
?>

<html>
<head>
    <title> <?php echo gettext("OSSIM Framework"); ?> </title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <META http-equiv="Pragma" content="no-cache">
    <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
</head>
<body>

  <h1><?php echo gettext("Delete Location"); ?> </h1>

<?php

$id = GET('id');
ossim_valid($id, OSS_HEX, 'illegal:' . _("Location Id"));

if (ossim_error()) 
{
    die(ossim_error());
}

if ( !GET('confirm') ) 
{
    ?>
    <p><?php echo gettext("Are you sure?");?></p>
    <p>
        <a href="<?php echo $_SERVER["SCRIPT_NAME"] . "?id=$id&confirm=yes"; ?>">
            <?php echo gettext("Yes"); ?>
        </a>
        &nbsp;&nbsp;&nbsp;
        <a href="locations.php"><?php echo gettext("No"); ?></a>
    </p>
    <?php
    exit();
}

$db   = new ossim_db();
$conn = $db->connect();
Locations::delete($conn, $id);

Util::memcacheFlush();
$db->close();
?>

    <p><?php echo gettext("Location deleted");?></p>

    <script type="text/javascript">
        document.location.href="locations.php";
    </script>
</body>
</html>