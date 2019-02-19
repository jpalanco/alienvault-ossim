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
require_once 'classes/Security.inc';
require_once 'classes/Util.inc';
require_once __DIR__.'/pie_helper.php';
Session::logcheck("analysis-menu", "EventsForensics");

$shared = new DBA_shared(GET('shared'));
$runorder = intval(GET('runorder')); if ($runorder==0) $runorder="";
$ips = $shared->get("geoips".$runorder);
if (!is_array($ips)) $ips = array();

$data_pie = array();
$legend = $data = array();
foreach($ips as $country => $val) {
    $cou = explode(":",$country); $val = round($val,1);
    $legend[] = $cou[1];
    $data[] = $val;
}
pieHelper::draw_plot($data,Util::get_chart_colors(),$legend,3);
