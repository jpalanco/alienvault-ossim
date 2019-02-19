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

Session::logcheck('environment-menu', 'PolicyHosts');

session_write_close();

//Validate action type

$action  = POST('action');

ossim_valid($action, OSS_LETTER, '_',   'illegal:' . _('Action'));

if (ossim_error())
{
    $data['status']  = 'error';
	$data['data']    = ossim_get_error_clean();

	echo json_encode($data);
    exit();
}


//Validate Form token

$token = POST('token');

if (Token::verify('tk_ng_form', POST('token')) == FALSE)
{
	$data['status']  = 'error';
	$data['data']    = Token::create_error_message();

	echo json_encode($data);
    exit();
}

switch($action)
{
    case 'delete_netgroup':

        $name = explode(";", POST('name'));

		foreach ($name as $netgroup_id)
		{
			ossim_valid($netgroup_id, OSS_HEX, 'illegal:' . _('Network group'));

			if (ossim_error())
			{
			    $data['status']  = 'error';
				$data['data']    = ossim_get_error_clean();

				echo json_encode($data);
			    exit();
			}
		}

        $db    = new ossim_db();
        $conn  = $db->connect();

    	$data['status']  = 'OK';
        $data['data']    = _('Network group removed successfully');

        foreach ($name as $netgroup_id)
        {
            if (Net_group::can_delete($conn, $netgroup_id))
            {
            	Net_group::delete($conn, $netgroup_id);
            	Net_group_scan::delete($conn, $netgroup_id, 3001);
            }
            else
            {
               $data['status']  = 'error';
               $data['data']    = _('Error! Network group could not be removed. This network group belongs to a policy');
            }
        }

        $db->close();

    break;
}

echo json_encode($data);
