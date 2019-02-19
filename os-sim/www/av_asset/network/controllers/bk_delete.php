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


//Config File
require_once 'av_init.php';

Session::logcheck_ajax('environment-menu', 'PolicyNetworks');

//Validate Form token
$token = POST('token');

if (Token::verify('tk_delete_network_bulk', $token) == FALSE)
{
    $error = Token::create_error_message();
    Util::response_bad_request($error);
}

session_write_close();

/* connect to db */
$db   = new ossim_db(TRUE);
$conn = $db->connect();

try
{
    $perm_add = Session::can_i_create_assets();

    if (!$perm_add)
    {
        $error = _('You do not have the correct permissions to delete networks. Please contact system administrator with any questions');

        Util::response_bad_request($error);
    }

    $app_name   = (Session::is_pro()) ? 'AlienVault' : 'OSSIM';
    $num_assets = Filter_list::get_total_selection($conn, 'network');

    //Delete all filtered nets
    Asset_net::bulk_delete($conn);

    $data['status']  = 'OK';
    $data['data']    = sprintf(_('%s networks have been permanently deleted from %s'), $num_assets, $app_name);
}
catch(Exception $e)
{
    $db->close();

    Util::response_bad_request($e->getMessage());
}

$db->close();

echo json_encode($data);
