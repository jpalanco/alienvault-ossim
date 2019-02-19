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

$action = POST('action');   //Action to perform.
$data   = POST('data');     //Data related to the action.

ossim_valid($action,	OSS_INPUT,	'illegal:' . _("Action"));
check_ossim_error(FALSE);

Session::logcheck_by_asset_type($data['asset_type']);   

function check_ossim_error($throw_excep = TRUE)
{
    if (ossim_error())
    {
        $error = ossim_get_error();

    	ossim_clean_error();
        
        if ($throw_excep)
        {
            Av_exception::throw_error(Av_exception::USER_ERROR, $error);
    	}
    	else
    	{
        	Util::response_bad_request($error);
    	}
    }
}


function save_list_selection($conn, $data)
{
    $asset_type = $data['asset_type'];
    $all        = $data['all'];
    $assets     = $data['assets'];
    $members    = $data['save_members'];
    
    ossim_valid($asset_type,    'asset', 'network', 'group',    'illegal:' . _('Type'));
    ossim_valid($assets,        OSS_HEX, OSS_NULLABLE,          'illegal:' . _('Assets'));
    ossim_valid($all,           OSS_BINARY,                     'illegal:' . _('Asset Selection'));
    ossim_valid($members,       OSS_BINARY, OSS_NULLABLE,       'illegal:' . _('Member Selection Option'));
    
    check_ossim_error();
    
    //Common error message when the selection is empty
    $empty_msg = sprintf(_('You need to select at least one %s.'), $asset_type);
    
    //If we filter by manual selection and the asset array is empty
    if (!$all && !is_array($assets))
    {
        Av_exception::throw_error(Av_exception::USER_ERROR, $empty_msg);
    }
    
    $total = Filter_list::save_list_selection($conn, $asset_type, $all, $assets);
    
    //If the selection is 0
    if ($total < 1)
    {
        Av_exception::throw_error(Av_exception::USER_ERROR, $empty_msg);
    }
    
    if ($members)
    {
        $total = Filter_list::save_members_from_selection($conn, $asset_type);
    }

    return $total;
    
}


function save_member_selection($conn, $data)
{
    $asset_id    = $data['asset_id'];
    $asset_type  = $data['asset_type'];
    $member_type = $data['member_type'];
    $all         = $data['all'];
    $assets      = $data['assets'];
    $search      = $data['search'];
    
    ossim_valid($asset_id,      OSS_HEX,                        'illegal:' . _('Asset UUID'));
    ossim_valid($asset_type,    'asset','network', 'group',     'illegal:' . _('Asset Type'));
    ossim_valid($member_type,   'asset','network', 'group',     'illegal:' . _('Asset Type'));
    ossim_valid($search,        OSS_INPUT, OSS_NULLABLE,        'illegal:' . _('Search Filter'));
    ossim_valid($assets,        OSS_HEX, OSS_NULLABLE,          'illegal:' . _('Assets'));
    ossim_valid($all,           OSS_BINARY,                     'illegal:' . _('Asset Selection'));
    
    check_ossim_error();
    
    
    if ($all)
    {
        $total = Filter_list::save_members_from_asset($conn, $asset_id, $asset_type, $search);
    }
    else
    {
        $total = Filter_list::save_items($conn, $member_type, $assets);
    }
        
    
    if ($total < 1)
    {
        Av_exception::throw_error(Av_exception::USER_ERROR, _('You need at least one asset to perform any action.'));
    }
    
    return $total;

}


//Checking token
$token  = POST("token");

if (Token::verify('tk_save_selection', $token) == FALSE)
{
    $error = Token::create_error_message();
    Util::response_bad_request($error);
}


$db     = new ossim_db();
$conn   = $db->connect();

try
{
    switch($action)
    {        
        case 'save_list_selection':
            $response = save_list_selection($conn, $data);
            break; 
            
        case 'save_member_selection':
            $response = save_member_selection($conn, $data);
            break;    
        
        default:
            $error = _('Wrong Option Chosen');
            Util::response_bad_request($error); 
    }
    
    echo json_encode($response);
    
}
catch(Exception $e)
{
    $db->close();
    
    $error = $e->getMessage();
    Util::response_bad_request($error);
}

$db->close();
