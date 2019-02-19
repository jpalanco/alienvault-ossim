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

// Response array
$response = array();


/**********************
 ****** GET DATA ******
 **********************/

// Database access object
$db   = new ossim_db();
$conn = $db->connect();

try
{
    $query  = 'SELECT hex(asset_id)as id FROM user_component_filter WHERE session_id=?';

    $params = array(
        session_id()
    );

    $rs = $conn->Execute($query, $params);

    if (!$rs)
    {
        Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
    }

    $response['status'] = 'OK';
    $results = array();

    while (!$rs->EOF)
    {
        $results[] = $rs->fields['id'];

        $rs->MoveNext();
    }

    $response['data'] = $results;

    $rs->Free();
}
catch (Exception $e)
{
    $response['status'] = 'error';
    $response['data']  = $e->getMessage();
}

$db->close();

echo json_encode($response);
exit();
