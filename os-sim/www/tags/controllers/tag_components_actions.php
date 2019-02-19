<?php

/**
 * tags_actions.php
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
$response = array();


/*****************************
 ****** VALIDATE PARAMS ******
 *****************************/

// Get params
$action             = POST('action');
$component_ids      = POST('component_ids');
$tag_id             = POST('tag_id');
$select_from_filter = POST('select_from_filter');
$component_type     = (POST('component_type')) ? POST('component_type') : 'asset';

// Validate action type
ossim_valid($action,                OSS_LETTER, '_',            'illegal: '._('Action'));
ossim_valid($component_ids,         OSS_HEX, OSS_NULLABLE,      'illegal: '._('Component ID'));
ossim_valid($tag_id,                OSS_HEX,                    'illegal: '._('Label ID'));
ossim_valid($select_from_filter,    OSS_LETTER,                 'illegal: '._('Filter value'));
ossim_valid($component_type,        OSS_LETTER,                 'illegal: '._('Component type'));

if (ossim_error())
{
    $response['status'] = 'error';
    $response['data']   = ossim_get_error_clean();

    echo json_encode($response);
    exit();
}

// Validate Token
if (Token::verify('tk_av_dropdown_tag_token', POST('token')) == FALSE)
{
    $response['status'] = 'error';
    $response['data']   = Token::create_error_message();

    echo json_encode($response);
    exit();
}


/************************
 ****** DO ACTIONS ******
 ************************/

// Database access object
$db   = new ossim_db();
$conn = $db->connect();

try
{
    if (Tag::is_in_db($conn, $tag_id))
    {
        $tag = Tag::get_object($conn, $tag_id);


        /****************************
         ****** Without filter ******
         ****************************/

        if ('false' == $select_from_filter)
        {
            foreach ($component_ids as $component_id)
            {
                switch ($action)
                {
                    // Add component
                    case 'add_components':
                        $tag->add_component($conn, $component_id);

                        break;

                    // Delete component
                    case 'delete_components':
                        $tag->remove_component($conn, $component_id);

                        break;

                    default:
                        Av_exception::throw_error(Av_exception::USER_ERROR, _('Invalid action - please try again'));
                }
            }
        }

        /*************************
         ****** With filter ******
         **************************/

        else
        {
            // Number of components in selection
            $num_components = Filter_list::get_total_selection($conn, 'asset');

            switch ($action)
            {
                // Add components
                case 'add_components':
                    $tag->add_components_from_filter($conn);
                    
                    $msg = _('Your label has been added to %d %s(s). You can view asset labels in the asset details');
                    $response['data']['components_added_msg'] = sprintf($msg, $num_components, $component_type);

                    break;

                // Delete components
                case 'delete_components':
                    $tag->remove_components_from_filter($conn);
                    
                    $msg = _('Your label has been deleted from  %d %s(s). You can view asset labels in the asset details');
                    $response['data']['components_deleted_msg'] = sprintf($msg, $num_components, $component_type);

                    break;

                default:
                    Av_exception::throw_error(Av_exception::USER_ERROR, _('Invalid action - please try again'));
            }
        }

        $response['status']        = 'OK';
        $response['data']['id']    = $tag->get_id();
        $response['data']['name']  = $tag->get_name();
        $response['data']['class'] = $tag->get_class();
    }
    else
    {
        Av_exception::throw_error(Av_exception::USER_ERROR, _('Action can not be completed'));
    }
}
catch (\Exception $e)
{
    $error_msg = $e->getMessage();

    if (empty($error_msg))
    {
        $error_msg = _('Sorry, operation was not completed due to an error when processing the request');
    }

    $response['status'] = 'error';
    $response['data']   = $error_msg;
}

$db->close();

echo json_encode($response);
exit();
