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

// Checking admin privileges
if (!Session::am_i_admin())
{
    echo ossim_error(_('You do not have permissions to see this section'));
    exit();
}

// Response array
$data = array();


// Get action type
$action = REQUEST('action');

// Validate action type
ossim_valid($action, OSS_LETTER, '_', 'illegal:'._('Action'));

if (ossim_error())
{
    $data['status'] = 'error';
    $data['data']   = ossim_get_error_clean();

    echo json_encode($data);
    exit();
}


// Database access object
$db   = new ossim_db();
$conn = $db->connect();

switch ($action)
{
    /******************************************
     **************** Save Tag ****************
     ******************************************/
    case 'save_tag':

        // Validate form params
        $validate = array(
            'tag_id'    => array('validation' => 'OSS_HEX, OSS_NULLABLE',                       'e_message' => 'illegal:'._('Label ID')),
            'tag_name'  => array('validation' => 'OSS_DIGIT, OSS_ALPHA, OSS_SCORE, OSS_SPACE',  'e_message' => 'illegal:'._('Label name')),
            'tag_type'  => array('validation' => 'OSS_ALPHA',                                   'e_message' => 'illegal:'._('Label type')),
            'tag_class' => array('validation' => 'OSS_ALPHA, OSS_SCORE',                        'e_message' => 'illegal:'._('Label class'))
        );

        /****************************************************
         ************** Checking field to field **************
         *****************************************************/

        if (GET('ajax_validation') == TRUE)
        {
            $data['status'] = 'OK';

            $validation_errors = validate_form_fields('GET', $validate);
            if (is_array($validation_errors) && !empty($validation_errors))
            {
                $data['status'] = 'error';
                $data['data']   = $validation_errors;
            }

            $db->close();

            echo json_encode($data);

            exit();
        }

        /*****************************************************
         **************** Checking all fields ****************
         *****************************************************/

        $tag_id    = POST('tag_id');
        $tag_name  = POST('tag_name');
        $tag_type  = POST('tag_type');
        $tag_class = POST('tag_class');

        
        $validation_errors = validate_form_fields('POST', $validate);
                        
        // Extend validation to check that tag name not exists
        if (is_array($validation_errors) && empty($validation_errors))
        {
            try
            {
                list($count, $tags) = Tag::get_tags_by_type($conn, $tag_type);

                foreach ($tags as $tag)
                {
                    if ($tag_name == $tag->get_name())
                    {
                        if (!empty($tag_id) && $tag_id == $tag->get_id())
                        {
                            break;
                        }

                        $validation_errors['tag_name'] = _('Name')." '$tag_name' "._('already present. Please enter a new name.');

                        break;
                    }
                }
            }
            catch (Exception $e)
            {
                $validation_errors['tag_name'] = $e->getMessage();
            }

            // Extend validation to check max name length
            if (strlen($tag_name) > 30)
            {
                $validation_errors['tag_name'] = _('Name')." '$tag_name' very long. Max length 30 characters";
            }
            
            // Validate form token
            if (empty($validation_errors))
            {
                if (Token::verify('tk_tag_form', POST('token')) == FALSE)
                {
                    $validations_errors['save_tag'] = Token::create_error_message();
                }
            }            
        }

        $data['status'] = 'OK';
        $data['data']   = $validation_errors;


        //I am checking form parameters
        if (POST('ajax_validation_all') == TRUE)
        {
            if (is_array($validation_errors) && !empty($validation_errors))
            {
                $data['status'] = 'error';
            }
        }
        else
        {
            try
            {
                // Update tag
                if (!empty($tag_id) && Tag::is_in_db($conn, $tag_id))
                {
                    $tag         = Tag::get_object($conn, $tag_id);
                    $success_msg = _('Label successfully updated');
                }
                // Create tag
                else
                {
                    $tag_id      = Util::uuid();
                    $tag         = new Tag($tag_id);
                    $success_msg = _('Label successfully created');
                }

                $tag->set_name(trim($tag_name));
                $tag->set_type($tag_type);
                $tag->set_class($tag_class);
                $tag->save_in_db($conn);

                $data['status'] = 'OK';
                $data['data']   = $success_msg;
            }
            catch (\Exception $e)
            {
                $error_msg = $e->getMessage();

                if (empty($error_msg))
                {
                    $error_msg = _('Sorry, operation was not completed due to an error when processing the request');
                }

                $data['status'] = 'error';
                $data['data']   = $error_msg;
            }       
        }        

        break;

    /********************************************
     **************** Delete Tag ****************
     ********************************************/
    case 'delete_tag':

        // Validate form params
        $validate = array(
            'tag_id' => array('validation' => 'OSS_HEX', 'e_message' => 'illegal:'._('Label ID'))
        );

        $validation_errors = validate_form_fields('POST', $validate);

        // Validate form token
        if (is_array($validation_errors) && empty($validation_errors))
        {
            if (Token::verify('tk_tag_form', POST('token')) == FALSE)
            {
                $validations_errors['save_tag'] = Token::create_error_message();
            }
        }

        if (is_array($validation_errors) && !empty($validation_errors))
        {
            $data['status'] = 'error';
            $data['data']   = $validation_errors;
        }
        else
        {
            // Get form params
            $tag_id = POST('tag_id');

            try
            {
                // Delete tag
                Tag::delete_from_db($conn, $tag_id);

                $data['status'] = 'OK';
                $data['data']   = _('Label successfully deleted');
            }
            catch (\Exception $e)
            {
                $error_msg = $e->getMessage();

                if (empty($error_msg))
                {
                    $error_msg = _('Sorry, operation was not completed due to an error when processing the request');
                }

                $data['status'] = 'error';
                $data['data']   = $error_msg;
            }
        }
}

$db->close();

echo json_encode($data);
exit();
