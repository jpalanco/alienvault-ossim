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
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

require_once 'av_init.php';

Session::logcheck("analysis-menu", "EventsForensics");

$type   = trim(GET('type'));
$search = trim(GET('q'));
$max    = intval(GET('limit'));
$max    = ($max) ? $max : 50;


ossim_valid($type,      'alarm|event',                                                 'illegal:' . _("Type"));
ossim_valid($search,    OSS_NULLABLE, OSS_NOECHARS, OSS_ALPHA, OSS_SCORE, OSS_PUNC,    'illegal:' . _("Search"));

if (ossim_error())
{
    die();
}

$db = new ossim_db(TRUE);

//Remote DataBases
if ($type == 'event' && is_array($_SESSION['server']) && $_SESSION['server'][0] != '')
{
    $conn = $db->custom_connect($_SESSION["server"][0],$_SESSION["server"][2],$_SESSION["server"][3]);
}
else
{
    $conn = $db->connect();
}


if ($type == 'event')
{
    $unique_pulses = Siem::get_unique_pulses($conn);
}
else if ($type == 'alarm')
{
    $unique_pulses = Alarm::get_unique_pulses($conn);
}
else
{
    $unique_pulses = array();
}

$pulses = array();
if (count($unique_pulses) > 0)
{
    try
    {
        $otx = new Otx();
        list($total, $p_list) = $otx->get_pulse_list(array('page' => 0, 'page_rows' => -1));
        
        foreach ($p_list as $p)
        {
            $p_id   = Util::uuid_format_nc($p['id']);
            $p_name = trim($p['name']);
            
            if ($unique_pulses[$p_id])
            {
                if (!$search || preg_match('/' . preg_quote($search, '/') . '/i', $p_name))
                {
                    $pulses[$p_id] = $p_name;
                }
            }
        }
        // Sort and Top $max pulses
        asort($pulses);
        $pulses = array_slice($pulses, 0, $max);
        foreach ($pulses as $id => $name)
        {
            echo "$id###$name\n";
        } 
    }
    catch(Exception $e) {}
}


$db->close();