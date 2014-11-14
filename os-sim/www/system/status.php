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


// Check permissions
if (!Session::am_i_admin())
{
     $config_nt = array(
        'content' => _("You do not have permission to see this section"),
        'options' => array (
            'type'          => 'nf_error',
            'cancel_button' => false
        ),
        'style'   => 'width: 60%; margin: 30px auto; text-align:center;'
    );

    $nt = new Notification('nt_1', $config_nt);
    $nt->show();

    die();
}

$maxrows    = (POST('iDisplayLength') != '') ? POST('iDisplayLength') : 10;
$search_str = (POST('sSearch') != '')        ? POST('sSearch') : '';
$from       = (POST('iDisplayStart') != '')  ? POST('iDisplayStart') : 0;
$order      = (POST('iSortCol_0') != '')     ? POST('iSortCol_0') : -1;
$torder     = POST('sSortDir_0');
$sec        = POST('sEcho');

switch ($order)
{
    case 0:
        $order = 'creation_time';
    break;

    case 1:
        $order = 'level';
    break;

    case 2:
        $order = 'component_type';
    break;

    case 3:
        $order = 'component_name';
    break;

    case 4:
        $order = 'component_ip';
    break;

    default:
        $order = '';
    break;

}

$torder = (!strcasecmp($torder, 'asc')) ? 'false' : 'true';

ossim_valid($maxrows,       OSS_DIGIT,                          'illegal: Max Rows');
ossim_valid($search_str,    OSS_INPUT, OSS_NULLABLE,            'illegal: Search String');
ossim_valid($from,          OSS_DIGIT,                          'illegal: From Param');
ossim_valid($order,         OSS_ALPHA, OSS_SCORE, OSS_NULLABLE, 'illegal: Order Param');
ossim_valid($torder,        OSS_ALPHA, OSS_NULLABLE,            'illegal: tOrder Param');
ossim_valid($sec,           OSS_DIGIT,                          'illegal: Sec Param');


$response = array();

if (ossim_error())
{

    $response['sEcho']                = 1;
    $response['iTotalRecords']        = 1;
    $response['iTotalDisplayRecords'] = 1;
    $response['aaData']               = array(array('','','','',ossim_error()));

    echo json_encode($response);
    exit;
}

$level = intval(GET('level'));
switch($level)
{
    case 0: $level = 'info,warning,error'; break;
    case 1: $level = 'info,warning,error'; break;
    case 2: $level = 'warning,error'; break;
    case 3: $level = 'error'; break;
}

$page = intval($from/$maxrows) + (( $from % $maxrows == 0 ) ? 1 : 0);

// Call API
try
{
    $filters = array(
        'level'        => $level,
        'order_by'     => $order,
        'order_desc'   => $torder
    );

    $pagination = array(
        'page'       => $page,
        'page_rows'  => $maxrows
    );

    $status = new System_status();
    list($message_list, $total) = $status->get_status_messages($filters, $pagination);
}
catch(Exception $e)
{
    $response['sEcho']                = $sec;
    $response['iTotalRecords']        = 0;
    $response['iTotalDisplayRecords'] = 0;
    $response['error']                = $e->getMessage();
    $response['aaData']               = array();

    echo json_encode($response);
    exit;
}


$data = array();
foreach ($message_list as $message)
{
    $res             = array();
    $res['DT_RowId'] = $message['message_id'].'_'.$message['component_id'];
    $res['viewed']   = $message['viewed'];
    $res['ctime']    = gmdate("Y-m-d H:i:s",strtotime($message['creation_time']));
    $date            = gmdate("Y-m-d H:i:s",strtotime(preg_replace('/GMT/', '', $message['creation_time']))+(3600*Util::get_timezone()));
    $res[]           = $date;
    $res[]           = $message['level'];
    $res[]           = $message['component_type'];
    $res[]           = $message['component_name'];
    $res[]           = $message['component_ip'];
    $res[]           = $message['description'];
    $data[]          = $res;
}

$response['sEcho']                = $sec;
$response['iTotalRecords']        = $total;
$response['iTotalDisplayRecords'] = $total;
$response['aaData']               = $data;

echo json_encode($response);