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

session_write_close();

//Validate action type

$action = POST('action');

ossim_valid($action, OSS_LETTER, '_', 'illegal:' . _('Action'));

if (ossim_error())
{
    Util::response_bad_request(ossim_get_error_clean());
}


//Validate Form token

$token = POST('token');

if (Token::verify('tk_net_form', $token) == FALSE)
{
    $error = Token::create_error_message();

    Util::response_bad_request($error);

}

$app_name = (Session::is_pro()) ? 'AlienVault' : 'OSSIM';

switch ($action)
{
    case 'delete_net':

        $net_id = POST('asset_id');

        if (!valid_hex32($net_id))
        {
            Util::response_bad_request(_('Error! Network ID not allowed.  Network could not be deleted'));
        }
        
        $db   = new ossim_db();
        $conn = $db->connect();

        $can_i_modify_ips = Asset_net::can_i_modify_ips($conn, $net_id);

        $db->close();
        
        if ($can_i_modify_ips == FALSE)
        {
            Util::response_bad_request(_('Error! Network ID not allowed.  Network could not be deleted'));
        }

        try
        {
            $db   = new ossim_db();
            $conn = $db->connect();

            Asset_net::delete_from_db($conn, $net_id, TRUE);

            $db->close();

            $data['status'] = 'success';
            $data['data']   = sprintf(_('Network has been permanently deleted from %s'), $app_name);
        }
        catch (Exception $e)
        {
            Util::response_bad_request(_('Error! Network could not be deleted') . ': ' . $e->getMessage());
        }

    break;
}

echo json_encode($data);
