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

Session::menu_perms('analysis-menu', 'ReportsAlarmReport');

$path = '/usr/share/ossim/www/report/os_reports/';
set_include_path(get_include_path() . PATH_SEPARATOR . $path);

include_once 'Alarms/common.php';

// Initialize var

$report_name    = $report_data['report_name'];
$subreport_name = $report_data['subreports'][$subreport_id]['name'];
   
$date_from      = POST($report_data['parameters'][0]['date_from_id']);
$date_to        = POST($report_data['parameters'][0]['date_to_id']);
$user           = Session::get_session_user();
$num_hosts      = 10;

ossim_valid($date_from, OSS_DATE, OSS_NULLABLE, 'illegal:' . _('Date From'));
ossim_valid($date_to,   OSS_DATE, OSS_NULLABLE, 'illegal:' . _('Date To'));

if (ossim_error())
{
    echo 'error###'.ossim_get_error_clean();
    exit;
}

$report_type    = 'alarm';
$title          = $report_name.' - '.$subreport_name;

//Ossim framework conf
$conf              = $GLOBALS['CONF'];
$acid_link         = $conf->get_conf('acid_link');
$ossim_link        = $conf->get_conf('ossim_link');
$acid_prefix       = $conf->get_conf('event_viewer');
$report_graph_type = $conf->get_conf('report_graph_type');
?>