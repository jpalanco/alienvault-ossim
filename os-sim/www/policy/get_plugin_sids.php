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


$plugin_id = GET('plugin_id');
$q         = urldecode(GET('q'));

ossim_valid($plugin_id, OSS_DIGIT, 'illegal:' . _("ID"));
ossim_valid($q, OSS_TEXT, OSS_NULLABLE);

if (ossim_error()) 
{
    return false;
}

$q = addslashes($q);

$db   = new ossim_db();
$conn = $db->connect();
$more = "";

if ($q != "") 
{
	$more = (preg_match("/^\d+$/",$q)) ? "AND sid like '$q%'" : "AND name like '%$q%'";
}

#remove the selected from the search
$selected_sids = GET('selected_sids');
$where = "";
if ($selected_sids!="ANY" && $selected_sids!="") {
        $selected_sids = explode(",", $selected_sids);
        $range = "";
        $sin = array();
        foreach ($selected_sids as $sid) {
                if (preg_match("/(\d)-(\d)/", $sid, $found)) {
                        $range .= " OR (sid NOT BETWEEN " . $found[1] . " AND " . $found[2] . ")";
                    } else {
                        $sin[] = $sid;
                    }
    }
    if (count($sin) > 0) {
        $where = "sid NOT IN (" . implode(",", $sin) . ") $range";
    }
    else {
        $where = preg_replace("/^ OR /", "", $range);
    }

    $where = " AND ($where)";
}

#searching non selected sids
$plugin_list = Plugin_sid::get_list($conn, "WHERE plugin_id=$plugin_id $more $where ORDER BY sid LIMIT ".POL_MAX_SIDS_SEARCH);

if ($plugin_list[0]->foundrows > POL_MAX_SIDS_SEARCH)
{
    echo "Total=".$plugin_list[0]->foundrows."=Limit=".POL_MAX_SIDS_SEARCH."\n";
 }

foreach($plugin_list as $plugin) 
{
    $id   = $plugin->get_sid();
    $name = "$id - ".trim($plugin->get_name());
    
    //if (strlen($name)>73) $name=substr($name,0,70)."...";
    echo "$id=$name\n";
}


$db->close();
