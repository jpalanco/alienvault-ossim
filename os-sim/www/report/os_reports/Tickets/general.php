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

Session::logcheck('analysis-menu', 'EventsForensics');

include_once 'common.php';

// Initialize var

$report_name    = $report_data['report_name'];
$subreport_name = $report_data['subreports'][$subreport_id]['name'];
   
$date_from      = POST($report_data['parameters'][0]['date_from_id']).' 00:00:00';
$date_to        = POST($report_data['parameters'][0]['date_to_id']).' 23:59:59';
$status         = POST($report_data['parameters'][1]['id']);
$status         = (!empty($status)) ? $status : 'All';
$type           = POST($report_data['parameters'][2]['id']);
$user           = Session::get_session_user();


ossim_valid($date_from, OSS_DATETIME, OSS_NULLABLE, 'illegal:' . _('Date From'));
ossim_valid($date_to,   OSS_DATETIME, OSS_NULLABLE, 'illegal:' . _('Date To'));
ossim_valid($status,    OSS_ALPHA,    OSS_NULLABLE, 'illegal:' . _('Status'));
ossim_valid($type,      OSS_ALPHA, OSS_SPACE, OSS_NULLABLE, 'illegal:' . _('Type'));

if (ossim_error())
{
    echo 'error###'.ossim_get_error_clean();
    exit;
}


$fil = array();

$priority_id = $report_data['parameters'][3]['id'];
$priority_values = array();

if ($_POST[$priority_id.'_Low'] == 'on')
{
    $priority_values[] = '1,2,3,4';
}
if ($_POST[$priority_id.'_Medium'] == 'on')
{    
    $priority_values[] = '5,6,7';
}
if ($_POST[$priority_id.'_High'] == 'on')
{    
    $priority_values[] = '8,9,10';
}    
if (count($priority_values) > 0 && count($priority_values) < 2)
{    
    $priority = 'incident.priority IN ('.implode(',', $priority_values).')';
}
else
{    
    $priority = NULL;
}

if (!empty($priority))
{    
    $fil[] =  $priority; 
}

$tit_temp = '';

if($status != 'All' && !empty($status))
{
    $fil[]     = 'incident.status="'.$status.'"';
    $tit_temp  = ' (Status: '.$status.')';
}


if ($type != 'ALL')
{
    $fil[] = "incident.type_id = '$type'";
}    

$title = $report_name.' - '.$subreport_name.$tit_temp;    

if ($date_from != '' && $date_to != '')
{
    $tzc   = Util::get_tzc();
    $fil[] = "(convert_tz(incident.date,'+00:00','$tzc') BETWEEN  '$date_from' AND '$date_to')";
}

$args = implode(' AND ',  $fil);

$date = date('d-m-y');
$pdf  = new Pdf('OSSIM Tickets Report', 'P', 'mm', 'A4');

$pdf->IncidentGeneralData($title, $date);
?>