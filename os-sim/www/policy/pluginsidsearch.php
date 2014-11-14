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

Session::logcheck("configuration-menu", "PluginGroups");


$q = GET('q');


ossim_valid($q, OSS_TEXT, 'illegal:' . _("Query"));

if (ossim_error()) 
{
    die(ossim_error());
}

$db   = new ossim_db();
$conn = $db->connect();
?>

<table id='event_type_source' class='noborder' width='100%' align="center">
	<?
	$plugin_list = Plugin_sid::search_sids($conn, $q);
	$pa = 0;
	foreach($plugin_list as $plugin) {
	    if ($pa!=$plugin["plugin_id"]) {
	        $color = ($color=="#eeeeee") ? "" : "#eeeeee";
	        $pa = $plugin["plugin_id"];
	    }
	?>
	<tr bgcolor="<?=$color?>">
	    <td width="20%"><input type="checkbox" name="psid<?=$plugin["plugin_id"]?>_<?=$plugin["sid"]?>" value="1"><?=$plugin["plugin_id"]?></td>
	    <td width="15%"><?=$plugin["plugin_name"]?></td>
	    <td width="15%"><?=$plugin["sid"]?></td>
	    <td width="50%" style="text-align:left"><?=utf8_encode($plugin["name"])?></td>
	</tr>
	
	<?
	}
	$db->close();
	?>
</table>

