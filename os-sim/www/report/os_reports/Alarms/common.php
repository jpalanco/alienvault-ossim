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
require_once 'jgraphs/jgraphs.php';

Session::logcheck('analysis-menu', 'ReportsAlarmReport');

$path = '/usr/share/ossim/www/report/os_reports/';
set_include_path(get_include_path() . PATH_SEPARATOR . $path);

require_once 'Common/functions.php';

if (is_object($security_report)) 
{
	$security_report->close_conns();
	
	unset($security_report);
}

$security_report = new Security_report();

function echo_risk($risk, $return = 0) 
{    
    $width = (20 * $risk) + 1;
    
    $img = "<img style=\"margin-left: 3px\" src=\"../pixmaps/risk_yellow.jpg\" width=\"$width\" height=\"15\" />";
    
    if ($risk > 7) 
    {
        $img  = "<img style=\"margin-left: 3px\" src=\"../pixmaps/risk_red.jpg\" " . "width=\"$width\" height=\"15\" />";
        $echo = "$img $risk";
    } 
    elseif ($risk > 4) 
    {
        $img  = "<img style=\"margin-left: 3px\" src=\"../pixmaps/risk_yellow.jpg\" " . "width=\"$width\" height=\"15\" />";
        $echo = "$img $risk";
    } 
    elseif ($risk > 2) 
    {
        $img  = "<img style=\"margin-left: 3px\" src=\"../pixmaps/risk_green.jpg\" " . "width=\"$width\" height=\"15\" />";
        $echo = "$img $risk";
    } 
    else 
    {
        $img  = "<img style=\"margin-left: 3px\" src=\"../pixmaps/risk_blue.jpg\" " . "width=\"$width\" height=\"15\" />";
        $echo = "$img $risk";
    }
    
    if ($return == 0) 
    {
        echo $echo;
    }
    else
    {
        return $echo;
    }
}
?>