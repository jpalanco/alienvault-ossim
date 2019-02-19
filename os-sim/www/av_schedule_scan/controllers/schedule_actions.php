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

if (Token::verify('tk_ss_form', $token) == FALSE)
{
    $error = Token::create_error_message();
    Util::response_bad_request($error);
}


$task_id = POST('task_id');

try
{
    $validate = array(
        'task_id' =>  array('validation' => 'OSS_DIGIT',  'e_message'  =>  'illegal:' . _('Task ID')),
    );


    $db    = new ossim_db();
    $conn  = $db->connect();

    $data['status'] = 'success';
    $data['data']   = _('Your changes have been saved');

    $e_message  = _('Error! Operation cannot be completed');

    switch($action)
    {
        case 'enable_scan':

            $e_message  = _('Error! Task could not be enabled');
            $parameters = array($conn, $task_id);
            $function   = 'Inventory::toggle_scan';

        case 'disable_scan':

            $e_message  = _('Error! Task could not be disabled');
            $parameters = array($conn, $task_id);
            $function   = 'Inventory::toggle_scan';

        break;
        case 'delete_scan':

            $e_message  = _('Error! Task could not be deleted');
            $parameters = array($conn, $task_id);
            $function   = 'Inventory::delete';

        break;
    }


    $validation_errors = validate_form_fields('POST', $validate);

    if (is_array($validation_errors) && !empty($validation_errors))
    {
        //Formatted message
        $error_msg = '<div>'._('The following errors occurred').":</div>
                      <div style='padding: 5px;'>".implode('<br/>', $validation_errors).'</div>';

        Av_exception::throw_error(Av_exception::USER_ERROR, $error_msg);
    }
    else
    {
        call_user_func_array($function, $parameters);
    }
}
catch(Exception $e)
{
    $db->close();

    Util::response_bad_request($e_message.': '.$e->getMessage());
}


$db->close();

echo json_encode($data);
