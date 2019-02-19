<?php
/**
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

//Config File
require_once 'av_init.php';

session_write_close();


function add_note($conn, $type)
{
    $validate = array(
        'asset_id' => array('validation' => 'OSS_HEX',                  'e_message'  =>  'illegal:' . _('Asset ID')),
        'txt'      => array('validation' => 'OSS_TEXT, OSS_PUNC_EXT',   'e_message'  =>  'illegal:' . _('Note text'))
    );

    $validation_errors = validate_form_fields('POST', $validate);

    if ((is_array($validation_errors) && !empty($validation_errors)))
    {   
        Av_exception::throw_error(Av_exception::USER_ERROR, _('Error! Note could not be added'));
    }
    
 
    $asset_id = POST('asset_id');
    $txt      = POST('txt');
    
    // Check Asset Type
    $asset_types = array(
        'asset'     => 'asset_host',
        'network'   => 'asset_net',
        'group'     => 'asset_group',
        'net_group' => 'net_group'
    );
    
    // Note type
    $type_tr = array(
        'group'     => 'host_group',
        'network'   => 'net',
        'asset'     => 'host',
        'net_group' => 'net_group'
    );
    
    $class_name = $asset_types[$type];
    $asset_type = $type_tr[$type];
    
    // Check Asset Permission
    if (method_exists($class_name, 'is_allowed') && !$class_name::is_allowed($conn, $asset_id))
    {
        $error = sprintf(_('Error! %s is not allowed'), ucwords($type));
        Av_exception::throw_error(Av_exception::USER_ERROR, $error);
    }
    
    $note_id  = Notes::insert($conn, $asset_type, gmdate('Y-m-d H:i:s'), $asset_id, $txt);
    
    if (intval($note_id) > 0)
    {
        $tz  = Util::get_timezone();
        
        $data['msg']      = _('Note added successfully');
        $data['id']       = $note_id;
        $data['note']     = $txt;
        $data['date']     = gmdate('Y-m-d H:i:s', Util::get_utc_unixtime(gmdate('Y-m-d H:i:s')) + 3600 * $tz);
        $data['user']     = Session::get_session_user();
        $data['editable'] = 1;
    }
    else
    {                    
        Av_exception::throw_error(Av_exception::USER_ERROR, _('Error! Note could not be added'));
    }
    
    
    return $data;
}


function edit_note($conn)
{
    $validate = array(
        'note_id' => array('validation' => 'OSS_DIGIT',               'e_message'  =>  'illegal:' . _('Note ID')),
        'txt'     => array('validation' => 'OSS_TEXT, OSS_PUNC_EXT',  'e_message'  =>  'illegal:' . _('Note text'))
    );

    $validation_errors = validate_form_fields('POST', $validate);

    if ((is_array($validation_errors) && !empty($validation_errors)))
    {   
        Av_exception::throw_error(Av_exception::USER_ERROR, _('Error! Note could not be saved'));
    }

    $note_id = POST('note_id');
    $txt     = POST('txt');
    
    $result = Notes::update($conn, $note_id, gmdate('Y-m-d H:i:s'), $txt);

    if ($result == TRUE)
    {
        $data['msg'] = _('Note saved successfully');
    }
    else
    {   
        Av_exception::throw_error(Av_exception::USER_ERROR, _('Error! Note could not be saved'));
    }
    
    return $data;
}


function delete_note($conn)
{
    $validate = array(
        'note_id'  => array('validation' => 'OSS_DIGIT',  'e_message'  =>  'illegal:' . _('Note ID'))
    );

    $validation_errors = validate_form_fields('POST', $validate);

    if ((is_array($validation_errors) && !empty($validation_errors)))
    {   
        Av_exception::throw_error(Av_exception::USER_ERROR, _('Error! Note could not be deleted'));
    }


    $note_id = POST('note_id');
    
    $result  = Notes::delete($conn, $note_id);

    if ($result == TRUE)
    {
        $data['msg'] = _('Note deleted successfully');
    }
    else
    {   
        Av_exception::throw_error(Av_exception::USER_ERROR, _('Error! Note could not be deleted'));
    }
    
    
    return $data;
}


// validate asset type
$type = POST('asset_type');

if ($type == 'asset' || $type == 'group')
{
    Session::logcheck_ajax('environment-menu', 'PolicyHosts');
}
else if ($type == 'network' || $type == 'net_group')
{
    Session::logcheck_ajax('environment-menu', 'PolicyNetworks');
}
else
{
	Util::response_bad_request(_('Invalid asset type value'));
}


//Validate action type
$action = POST('action');

ossim_valid($action, OSS_LETTER, '_',   'illegal:' . _('Action'));

if (ossim_error())
{
    $error = ossim_get_error_clean();

    Util::response_bad_request($error);
}

//Validate Form token
$token  = POST('token');

$tk_key = 'tk_' . $action;

if (Token::verify($tk_key, $token) == FALSE)
{
    $error = Token::create_error_message();

    Util::response_bad_request($error);
}


$db    = new ossim_db();
$conn  = $db->connect();

try
{
    $response = array();
    
    switch($action)
    {   
        case 'add_note':
            $response = add_note($conn, $type);
        break;
    
        case 'edit_note':
            $response = edit_note($conn);
        break;
        
        case 'delete_note':
            $response = delete_note($conn);
        break;
        
        default:
            Av_exception::throw_error(Av_exception::USER_ERROR, _('Invalid Action.'));
    }
}
catch (Exception $e)
{
    $db->close();
    Util::response_bad_request($e->getMessage());
}

$db->close();

echo json_encode($response);