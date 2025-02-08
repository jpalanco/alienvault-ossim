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

Session::logcheck_ajax('environment-menu', 'PolicyHosts');

session_write_close();


//Validate action type
$action = POST('action');

ossim_valid($action, OSS_LETTER, '_',   'illegal:' . _('Action'));

if (ossim_error())
{
    Util::response_bad_request(ossim_get_error_clean());
}


//Validate Form token

$token = POST('token');

if (Token::verify('tk_ag_form', $token) == FALSE)
{
	$error = Token::create_error_message();

    Util::response_bad_request($error);
}


$app_name = (Session::is_pro()) ? 'AlienVault' : 'OSSIM';

switch($action)
{
    case 'create_group':

        $name  = Util::utf8entities(POST('name'));
        $name  = mb_convert_encoding($name, 'ISO-8859-1', 'UTF-8');
        $descr = Util::utf8entities(POST('descr'));
        $descr = mb_convert_encoding($descr, 'ISO-8859-1', 'UTF-8');

        $empty = (POST('empty') != '') ? TRUE : FALSE;

        ossim_valid($name,  OSS_GROUP_NAME,        'illegal:' . _('Asset Group Name'));
        ossim_valid($descr, OSS_ALL, OSS_NULLABLE, 'illegal:' . _('Asset Group Description'));

        if (ossim_error())
        {
            Util::response_bad_request(ossim_get_error_clean());
        }

        try
        {
            $db   = new ossim_db();
            $conn = $db->connect();

            $num_assets = Filter_list::get_total_selection($conn, 'asset');

            $id  = Util::uuid();
            $ctx = Session::get_default_ctx();

            $asset_group = new Asset_group($id);
            $asset_group->set_name($name);
            $asset_group->set_descr($descr);
            $asset_group->set_ctx($ctx);

            $asset_group->save_in_db($conn);

            if (!$empty)
            {
                $asset_group->save_assets_from_search($conn, FALSE);
            }

            $db->close();

            $data['status'] = 'success';
            $data['data']   = sprintf(_('Asset group has been created in %s'), $app_name);
            $data['id']     = $id;

        }
        catch(Exception $e)
        {
            Util::response_bad_request(_('Error! Asset group could not be created') . ': ' . $e->getMessage());
        }

    break;


    case 'delete_group':

        $group_id = POST('asset_id');

        if (!valid_hex32($group_id))
        {
            Util::response_bad_request(_('Error! Asset group ID not allowed. Asset group could not be deleted'));
        }
        else
        {
            try
            {
                $db   = new ossim_db();
                $conn = $db->connect();

                $asset_group = new Asset_group($group_id);
                $asset_group->delete($conn);

                $db->close();

                $data['status']  = 'success';
                $data['data']   = sprintf(_('Asset group has been permanently deleted from %s'), $app_name);
            }
            catch(Exception $e)
            {
                Util::response_bad_request(_('Error! Asset group could not be deleted') . ': ' . $e->getMessage());
            }
        }

    break;


    case 'add_new_assets':

        $group_id = POST('asset_id');

        if (!valid_hex32($group_id))
        {
            Util::response_bad_request(_('Error! Asset group ID not allowed. Selected assets could not be added'));
        }
        else
        {
            try
            {
                $db   = new ossim_db();
                $conn = $db->connect();

                $num_assets = Filter_list::get_total_selection($conn, 'asset');

                $asset_group = new Asset_group($group_id);
                $asset_group->save_assets_from_search($conn);

                $db->close();

                $data['status'] = 'success';
                $data['data']   = sprintf(_("%s assets have been added to group"), $num_assets);

            }
            catch(Exception $e)
            {
                Util::response_bad_request(_('Error! Selected assets could not be added') . ': ' . $e->getMessage());
            }
        }

    break;

    case 'delete_assets':

        $group_id = POST('asset_id');

        if (!valid_hex32($group_id))
        {
            Util::response_bad_request(_('Error! Asset group ID not allowed. Selected assets could not be removed'));
        }
        else
        {
            try
            {
                $db   = new ossim_db();
                $conn = $db->connect();

                $num_assets = Filter_list::get_total_selection($conn, 'asset');

                $asset_group = new Asset_group($group_id);
                $asset_group->delete_selected_assets($conn);

                $db->close();

                $data['status'] = 'success';
                $data['data']   = sprintf(_("%s assets have been deleted from group"), $num_assets);

            }
            catch(Exception $e)
            {
                Util::response_bad_request(_('Error! Selected assets could not be deleted') . ': ' . $e->getMessage());
            }
        }

    break;

    case 'is_unique_group':

        try
        {
            $db   = new ossim_db();
            $conn = $db->connect();


            $result =  Asset_group_scan::is_group_has_unique_assets($conn);

            $db->close();

            $data['status'] = 'success';
            $data['unique']   = $result;

        }
        catch(Exception $e)
        {
            Util::response_bad_request(_('Error! Selected assets could not be deleted') . ': ' . $e->getMessage());
        }

        break;

}

echo json_encode($data);
