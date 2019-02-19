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

$only_close      = intval(POST('only_close'));
$background      = intval(POST('background'));

if (ossim_error()) 
{
    die(ossim_error());
}

// check required permissions
if (!$only_close && !Session::menu_perms("analysis-menu", "ControlPanelAlarmsDelete"))
{
    die(ossim_error("You don't have required permissions to delete Alarms"));
}

if ($only_close && !Session::menu_perms("analysis-menu", "ControlPanelAlarmsClose"))
{
    die(ossim_error("You don't have required permissions to close Alarms"));
}
/* connect to db */
$db   = new ossim_db();
$conn = $db->connect();

foreach($_POST as $key => $value) 
{
    if (preg_match("/check_([0-9a-fA-F]+)_([0-9a-fA-F]+)/", $key, $found)) 
	{
        if ($only_close)
        {
			Alarm::close($conn, $found[1]);
		}
        else 
        {
           $result = Alarm::delete_backlog($conn, $found[1]);
           
           if (!$result)
           {
               $_SESSION["_delete_msg"] = _("You do not have enough permissions to delete this alarm as it contains events that you are not allowed to see.");
           }
        }
    }
}

$db->close();