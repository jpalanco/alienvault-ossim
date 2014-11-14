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

function normalize_date($from_date, $to_date)
{
    // Format correction
    $from_date = preg_replace ("/(\d\d)\/(\d\d)\/(\d\d\d\d)/", "\\3-\\2-\\1", $from_date);
    $to_date   = preg_replace ("/(\d\d)\/(\d\d)\/(\d\d\d\d)/", "\\3-\\2-\\1", $to_date);

    // Timezone correction
    $tz = Util::get_timezone();

    if ($tz != 0)
    {
        $from_date = gmdate("Y-m-d H:i:s", Util::get_utc_unixtime("$from_date 00:00:00") + (-3600 * $tz));
        $to_date   = gmdate("Y-m-d H:i:s", Util::get_utc_unixtime("$to_date 23:59:59") + (-3600 * $tz));
    }

    if (!preg_match("/\d+\:\d+:\d+/", $from_date))
    {
        $from_date .= " 00:00:00";
    }

    if (!preg_match("/\d+\:\d+:\d+/", $to_date))
    {
        $to_date .= " 23:59:59";
    }

    return array($from_date, $to_date);
}

?>