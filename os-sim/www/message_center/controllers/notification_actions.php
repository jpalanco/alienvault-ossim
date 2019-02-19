<?php

/**
 * notification_actions.php
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
$data = array();


/************************************
 ****** Validate Action Type ********
 ************************************/

// Get action type
$action = POST('action');
$delete_all = POST('delete_all');
$message_total_count = POST('message_total_count');

// Validate action type
ossim_valid($action, OSS_LETTER, '_', 'illegal:'._('Action'));

if (ossim_error())
{
    Util::response_bad_request(ossim_get_error_clean());
}


// Database access object
$db   = new ossim_db();
$conn = $db->connect();


/**************************************
 ****** Validate all form fields ******
 **************************************/

// Validate form params

$validate =  ($delete_all) ? '' : array(
    'status_message_id' => array('validation' => 'OSS_UUID', 'e_message' => 'illegal:'._('Status Message UUID'))
);
$validation_errors = validate_form_fields_new('POST', $validate);
// Validate form token
if (is_array($validation_errors) && empty($validation_errors))
{
    if (Token::verify('tk_notification_form', POST('token')) == FALSE)
    {
        $validations_errors['set_viewed'] = Token::create_error_message();
    }
}

if (is_array($validation_errors) && !empty($validation_errors))
{
    //Formatted message
    $error_msg = '<div>'._('The following errors occurred').":</div>
                          <div style='padding: 5px;'>".implode('<br/>', $validation_errors).'</div>';

    Util::response_bad_request($error_msg);
}
else
{
    // Get form params
    $status_message_id = POST('status_message_id');
    $string = "s";

    if (!is_array($status_message_id)) {
        $status_message_id = array($status_message_id);
        $string = "";
    }
    try
    {
        $params = array();
        switch ($action)
        {
            case 'set_viewed':
                $params = array('viewed' => 'true');
                $data['data'] = _('Notification%s marked as viewed');
                break;
            case 'set_suppressed':
                if (!$av_menu->check_perm("message_center-menu", "MessageCenterDelete")) {
                    Av_exception::throw_error(Av_exception::USER_ERROR, _("You don't have permission to delete this entity"));
                }
                $params = array('suppressed' => 'true');
                $data['data'] = _('Notification%s marked as suppressed');
                break;
            default:
                Av_exception::throw_error(Av_exception::USER_ERROR, _('This action could not be completed. Please try again.'));
        }
        $data['data'] = sprintf($data['data'],$string);
        $status = new System_notifications();

        if($delete_all) {
            $status_message_id = '';
            $get_all_messages ['page_rows'] = $message_total_count;
            list($message_list, $total_messages) = $status->get_status_messages('',$get_all_messages);
            foreach ($message_list as $message)
            {
                $status_message_id [] = $message['id'];
            }

        }

        foreach ($status_message_id as $smi) {
            $status->set_status_message($smi,$params);
        }
        $data['status'] = 'OK';
    }
    catch (\Exception $e)
    {
        /************************
         ****** Catch error *****
         ************************/

        $error_msg = $e->getMessage();

        if (empty($error_msg))
        {
            $error_msg = _('Sorry, operation was not completed due to an error when processing the request. Please try again.');
        }

        Util::response_bad_request($error_msg);
    }
}


$db->close();

echo json_encode($data);
exit();
