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

Session::admin_logcheck_ajax();
session_write_close();


/*
* This function retrieves the OTX config information.
*
* @return array
*
*/
function get_otx_info()
{
    $otx = new Otx();
    $otx->load();
    
    return array(
        'token'         => $otx->get_token(),
        'username'      => $otx->get_username(),
        'user_id'       => $otx->get_user_id(),
        'contributing'  => $otx->is_contributing(),
        'key_version'   => $otx->get_key_version(),
        'latest_update' => $otx->get_latest_update()
    );
}



//Checking the action to perform.
$action = POST('action'); 
$result = array();

try
{
    switch($action)
    {
        case 'info':
            $result = get_otx_info();
        break;
        
        default:
            Av_exception::throw_error(Av_exception::USER_ERROR, _('Invalid Action.'));
    }
}
catch (Exception $e)
{
    Util::response_bad_request($e->getMessage());
}


echo json_encode($result);