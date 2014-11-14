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

Session::logcheck("analysis-menu", "ControlPanelAlarms");

$group = GET('group');
ossim_valid($group, OSS_ALPHA, 'illegal:' . _("group"));
if (ossim_error()) {
    die(ossim_error());
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<title><?php echo _("Control Panel")?></title>
		<meta http-equiv="Pragma" content="no-cache"/>
		<link rel="stylesheet" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	</head>

<body>
	<table cellpadding='0' cellspacing='0' border='0' class="noborder">
		<tr>
			<td class="nobborder">
				<?php echo _("Warning, you're going to remove Alarms. That will affect to other parts like visualization. May be that your want to")?>; 
				<a href="alarm_group_console.php?action=close_group&group=<?php echo $group ?>" target="_parent"><strong><?php echo _("CLOSE ALARMS")?></strong></a>
				, <?php echo _("and not to remove	it. Are you sure that you want to remove them?")?>
			</td>
		</tr>
		<tr>
			<td class="nobborder" style="padding-top:30px;text-align:center">
				<?php echo _("DELETE GROUPS")?>: <i><?php echo $group ?></i> <strong>?</strong> 
				<input type="button" value="YES" onclick="parent.location.href='alarm_group_console.php?action=delete_group&group=<?php echo $group?>'"/></td>
		</tr>
	</table>
</body>
