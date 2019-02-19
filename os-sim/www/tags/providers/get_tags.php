<?php

/**
 * get_tags.php
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


/********************************
 ****** CHECK USER SESSION ******
 ********************************/

Session::useractive();


/*****************************
 ****** VALIDATE PARAMS ******
 *****************************/

//DataTables Pagination and search Params
$order      = (REQUEST('iSortCol_0') != '')     ? REQUEST('iSortCol_0')         : '';
$torder     = REQUEST('sSortDir_0');
$sec        = intval(REQUEST('sEcho'));
$search_str = (REQUEST('sSearch') != '')        ?   REQUEST('sSearch')          : '';
$from       = (REQUEST('iDisplayStart') != '')  ?   REQUEST('iDisplayStart')    : 0;
$limit      = (REQUEST('iDisplayLength') != '') ?   REQUEST('iDisplayLength')   : 10;

$order = (!strcasecmp($torder, 'asc')) ? 0 : 1;

ossim_valid($order,         OSS_DIGIT,                  'illegal: iSortCol_0');
ossim_valid($torder,        OSS_ALPHA,                  'illegal: sSortDir_0');
ossim_valid($sec,           OSS_DIGIT,                  'illegal: sEcho');
ossim_valid($from,          OSS_DIGIT,                  'illegal: iDisplayStart');
ossim_valid($limit,         OSS_DIGIT,                  'illegal: iDisplayLength');
ossim_valid($search_str,    OSS_INPUT, OSS_NULLABLE,    'illegal: ' . _('Search String'));

// Get action type
$tag_type = GET('tag_type');

// Validate action type
ossim_valid($tag_type, OSS_ALPHA, '_', 'illegal:'._('Label type'));

if (ossim_error())
{
    $response['sEcho']                = $sec;
    $response['iTotalRecords']        = 0;
    $response['iTotalDisplayRecords'] = 0;
    $response['aaData']               = '';

    echo json_encode($response);
    
    exit();
}


/**********************
 ****** GET DATA ******
 **********************/

// Database access object
$db   = new ossim_db();
$conn = $db->connect();

// Response array
$response = array();

try
{
    $filters = array();

    $filters['where'] = 'tag.type = "'.$tag_type.'"';

    if ($search_str != '')
    {
        $search_str = escape_sql($search_str, $conn);

        $filters['where'] .= ' AND tag.name LIKE "%'.$search_str.'%"';
    }

    $filters['limit'] = $from.', '.$limit;

    $filters['order_by'] = 'tag.name '.$torder;

    // Tag list
    list($total, $tags) = Tag::get_list($conn, '', $filters);

    // Results array
    $results = array();

    foreach ($tags as $tag_id => $tag)
    {
        $_res             = array();
        $_res['DT_RowId'] = $tag_id;
        $_res[]           = $tag->get_class();
        $_res[]           = $tag->get_name();

        $results[] = $_res;
    }

    $response['sEcho']                = $sec;
    $response['iTotalRecords']        = $total;
    $response['iTotalDisplayRecords'] = $total;
    $response['aaData']               = $results;
}
catch (Exception $e)
{
    $response['sEcho']                = $sec;
    $response['iTotalRecords']        = 0;
    $response['iTotalDisplayRecords'] = 0;
    $response['aaData']               = array();
}

$db->close();

echo json_encode($response);
exit();
