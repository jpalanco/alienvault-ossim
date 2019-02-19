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
Session::logcheck("analysis-menu", "ReportsAlarmReport");

require_once 'classes/Util.inc';
require_once __DIR__.'/pie_helper.php';

$limit = GET('hosts');
$type = GET('type');
$date_from = (GET('date_from') != "") ? GET('date_from') : strftime("%d/%m/%Y %H:%M:%S", time() - (24 * 60 * 60));
$date_to = (GET('date_to') != "") ? GET('date_to') : strftime("%d/%m/%Y %H:%M:%S", time());
ossim_valid($limit, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Limit"));
ossim_valid($type, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Report type"));
ossim_valid($date_from, OSS_DIGIT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("from date"));
ossim_valid($date_to, OSS_DIGIT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("to date"));
$runorder = intval(GET('runorder')); if ($runorder==0) $runorder="";
$multiple_colors = intval(GET('colors'));
if (ossim_error()) {
    die(ossim_error());
}
/* hosts to show */
if (empty($limit) || $limit<=0 || $limit>10) {
    $limit = 10;
}
if (empty($type)) {
    $type = "event";
}
$security_report = new Security_report();
$shared = new DBA_shared(GET('shared'));
$SS_TopEvents = $shared->get("SS_TopEvents$runorder");
$SA_TopAlarms = $shared->get("SA_TopAlarms$runorder");
if ($type == "event" && is_array($SS_TopEvents) && count($SS_TopEvents)>0)
	$list = $SS_TopEvents;
elseif ($type == "alarm" && is_array($SA_TopAlarms) && count($SA_TopAlarms)>0)
	$list = $SA_TopAlarms;
else 
	$list = $security_report->Events($limit, $type, $date_from, $date_to);
$data_pie = array();
$legend = $data = array();
foreach($list as $key => $l) {
    if($key>=10){
        // ponemos un límite de resultados para la gráfica
        break;
    }
    $data_pie[$l[1]] = Security_report::Truncate($l[0], 60);
    $legend[] = Util::signaturefilter(Security_report::Truncate($l[0], 60));
    $data[] = $l[1];
}
$colors = array();
if ($multiple_colors)
{
	$colors = Util::get_chart_colors();
}
else
{
	$colors = array('#ADD8E6','#00BFFF','#4169E1','#4682B4','#0000CD','#483D8B','#00008B','#3636db','#1390fa','#6aafea');
	$legend = null;
}
pieHelper::draw_plot($data,$colors,$legend);
?>
