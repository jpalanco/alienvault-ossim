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



/* Aux function to throw validation errors */
function check_ossim_error()
{
    if (ossim_error())
    {
        $error = ossim_get_error();

    	ossim_clean_error();

    	Av_exception::throw_error(Av_exception::USER_ERROR, $error);
    }
}


/*
* This function activates an OTX account with a given token.
*
* @return array
*
*/
function activate_account()
{
    $data  = POST('data');
    $token = $data['token'];
    
    ossim_valid($token, OSS_ALPHA, 'illegal:' . _("OTX auth-token"));
    
    check_ossim_error();
    
    $otx = new Otx();
    $otx->register_token($token);
    
    return array(
        'msg'           => _("Your OTX account has been connected. The OTX pulses that you have subscribed to will begin downloading shortly. This process may take a few minutes."),
        'token'         => $token,
        'username'      => $otx->get_username(),
        'user_id'       => $otx->get_user_id(),
        'contributing'  => TRUE,
        'key_version'   => $otx->get_key_version(),
        'latest_update' => $otx->get_latest_update()
    ); 
}


/*
* This function removes the OTX account.
*
* @return array
*
*/
function remove_account()
{
    $otx = new Otx();
    $otx->remove_account();
    
    return array(
        'msg' => _('Your OTX account has been disconnected.')
    );    
}


/*
* This function enable/disable the OTX account.
*
* @return array
*
*/
function change_account_contribution()
{
    $data       = POST('data');
    $contribute = intval($data['status']);
    
    
    $otx = new Otx();
    
    if ($contribute)
    {
        $otx->enable_contribution();
        $msg = _('You are now contributing to OTX.');
    }
    else
    {
        $otx->disable_contribution();
        $msg = _('You are not contributing to OTX anymore.');
    }

    return array(
        'msg' => $msg
    );
}



//Checking the action to perform.
$action = POST('action'); 
$result = array();

try
{
    switch($action)
    {
        case 'activate':
            $result = activate_account();
        break;
        
        case 'remove':
            $result = remove_account();
        break;
        
        case 'change_contribution':
            $result = change_account_contribution();
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