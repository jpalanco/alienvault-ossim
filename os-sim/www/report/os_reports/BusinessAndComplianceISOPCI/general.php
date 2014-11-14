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


$date_from   = POST($report_data['parameters'][0]['date_from_id']);
$date_to     = POST($report_data['parameters'][0]['date_to_id']);

$report_name    = $report_data['report_name'];
$subreport_name = $report_data['subreports'][$subreport_id]['name'];

$title       = $report_name.' - '.$subreport_name;
$report_type = "B & C";
$user        = Session::get_session_user();

ossim_valid($date_from, OSS_DATE, OSS_NULLABLE, 'illegal:' . _('Date From'));
ossim_valid($date_to,   OSS_DATE, OSS_NULLABLE, 'illegal:' . _('Date To'));

if (ossim_error())
{
    echo 'error###'.ossim_get_error_clean();
    exit;
}

$db   = new ossim_db();
$conn = $db->connect();
?>