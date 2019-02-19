<?php

/**
 *
 * License:
 *
 * Copyright (c) 2003-2006 ossim.net
 * Copyright (c) 2007-2015 AlienVault
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
$todelete = $av_menu->check_perm("message_center-menu", "MessageCenterDelete");

/********************************
 ****** CHECK USER SESSION ******
 ********************************/

Session::useractive();


/******************************
 ****** GET POST PARAMS  ******
 ******************************/

// Datatables params
$search_str = (POST('sSearch') != '')           ? POST('sSearch')           : '';
$order      = (POST('iSortCol_0') != '')        ? POST('iSortCol_0')        : 0;
$t_order    = (POST('sSortDir_0') != '')        ? POST('sSortDir_0')        : 'desc';
$max_rows   = (POST('iDisplayLength') != '')    ? POST('iDisplayLength')    : 10;
$from       = (POST('iDisplayStart') != '')     ? POST('iDisplayStart')     : 0;
$sec        = POST('sEcho');

// Parse t_order to boolean
$t_order = (!strcasecmp($t_order, 'asc')) ? 'false' : 'true';

// Filters
$view       = POST('nf_view');  // all,  unread
$type       = POST('nf_type');  // deployment, update, information
$level      = POST('nf_level'); // info, warning, error


/******************************
 ****** VALIDATE PARAMS  ******
 ******************************/

// Datatables params
ossim_valid($search_str,    OSS_INPUT, OSS_NULLABLE,             'illegal:' . _('Search String'));
ossim_valid($order,         OSS_ALPHA, OSS_SCORE, OSS_NULLABLE,  'illegal:' . _('Order Param'));
ossim_valid($t_order,       OSS_ALPHA, OSS_NULLABLE,             'illegal:' . _('tOrder Param'));
ossim_valid($sec,           OSS_DIGIT,                           'illegal:' . _('Sec Param'));
ossim_valid($from,          OSS_DIGIT,                           'illegal:' . _('From Param'));
ossim_valid($max_rows,      OSS_DIGIT,                           'illegal:' . _('Max rows Param'));

// Filters
ossim_valid($view,          OSS_LETTER,                          'illegal:' . _('View Param'));
ossim_valid($type,          OSS_LETTER, ',', OSS_NULLABLE,       'illegal:' . _('Type Param'));
ossim_valid($level,         OSS_LETTER, ',', OSS_NULLABLE,       'illegal:' . _('Level Param'));


/***************************
 ****** RESPONSE VARS ******
 ***************************/

// Response array
$response = array();

// Array to store data
$data = array();

$total_messages = 0;

try
{
    // If validation errors throw new exception with error details
    if (ossim_error())
    {
        Av_exception::throw_error(Av_exception::USER_ERROR, ossim_get_error_clean());
    }

    if (strlen($search_str) > 30)
    {
        Av_exception::throw_error(Av_exception::USER_ERROR, 'Search string very long. Max length 30 characters');
    }


    /*********************
     ****** Filters ******
     *********************/

    /**
     * Returns order_by string by column
     *
     * @param  integer  $order
     *
     * @return string
     */
    $order_by = function ($order)
    {
        switch ($order)
        {
            case 0:
                return 'creation_time';
            case 1:
                return 'message_title';
            case 2:
                return 'message_level';
            case 3:
                return 'message_type';
            default:
                return 'creation_time';
        }
    };

    // Fill filter array
    $filters = array(
        'order_by'      => $order_by($order),
        'order_desc'    => $t_order,
        'only_unread'   => ('unread' == $view) ? 'true' : 'false'
    );

    if (!empty($search_str))
    {
        $filters['search'] = $search_str;
    }

    if (!empty($type))
    {
        $filters['message_type'] = $type;
    }

    if (!empty($level))
    {
        $filters['level'] = $level;
    }


    /************************
     ****** Pagination ******
     ************************/

    $pagination = array(
        'page'      => Util::calculate_pagination_page($from, $max_rows),
        'page_rows' => $max_rows
    );


    /**********************
     ****** API Call ******
     **********************/


    // Call API to get status messages
    $status = new System_notifications();
    //check if last update is error
    $status->verify_last_update_notification();

//$status->get_status_message("2A483FC9F04443558CCC51DC8780F94B");
    list($message_list, $total_messages) = $status->get_status_messages($filters, $pagination);

    // Wiki Parser
    $wiki = new Wikiparser();

    // Fill data array
    foreach ($message_list as $message)
    {
        $res                         = array();
        $res['DT_RowId']             = $message['id'];
        $res['viewed']               = $message['viewed'];
        $res['description']          = $wiki->parse($message['message_description']);
        $res['actions']              = $wiki->parse($message['message_actions']);
        $res['alternative_actions']  = $wiki->parse($message['message_alternative_actions']);
        if ($todelete) {
            $res[]                       = '';   // Empty because this is for column actions that is managed in fnRowCallback
        }
        $res[]                       = $message['creation_time'];
        $res[]                       = $message['message_title'].($message['component_ip'] ? ' ('.$message['component_ip'].')' : '');
        $res[]                       = $message['message_level'];
        $res[]                       = $message['message_type'];
        $data[]                      = $res;
    }
}
catch (Exception $e)
{
    Util::response_bad_request($e->getMessage());
}

// Fill response
$response['sEcho']                = $sec;
$response['iTotalRecords']        = $total_messages;
$response['iTotalDisplayRecords'] = $total_messages;
$response['iDisplayStart']        = 0;
$response['data']                 = $data;
$response['status']               = 'OK';

echo json_encode($response);
exit();
