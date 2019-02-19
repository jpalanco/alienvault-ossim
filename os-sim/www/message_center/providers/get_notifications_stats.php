<?php

/**
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
 */

require_once 'av_init.php';


/********************************
 ****** CHECK USER SESSION ******
 ********************************/

Session::useractive();


/*****************************
 ****** GET POST PARAMS ******
 *****************************/

$search      = (POST('search') != '')   ? POST('search')        : '';
$only_unread = (POST('only_unread'))    ? POST('only_unread')   : '';


/**********************************
 ****** VALIDATE POST PARAMS ******
 **********************************/

ossim_valid($search,        OSS_INPUT, OSS_NULLABLE,  'illegal:'._('Search String'));
ossim_valid($only_unread,   OSS_LETTER, OSS_NULLABLE, 'illegal:'._('Only Unread Param'));


/***************************
 ****** RESPONSE VARS ******
 ***************************/

// Response array
$response = array();

// Array to store data
$data = array();

try
{
    if (ossim_error())
    {
        Av_exception::throw_error(Av_exception::USER_ERROR, ossim_get_error_clean());
    }

    /**********************
     ****** FILTERS *******
     **********************/

    $filters = array();

    if (!empty($search))
    {
        $filters['search'] = $search;
    }

    if (!empty($only_unread))
    {
        $filters['only_unread'] = 'true';
    }


    /**********************
     ****** GET DATA ******
     **********************/

    $status = new System_notifications();

    list($messages_stats, $stats_count) = $status->get_status_messages_stats($filters);

    $data = $messages_stats;
}
catch (Exception $e)
{
    Util::response_bad_request($e->getMessage());
}

$response['status'] = 'OK';
$response['data'] = $data;

echo json_encode($response);
exit();
