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


$app_name = (Session::is_pro()) ? 'AlienVault' : 'OSSIM';


switch($action)
{
    //Asset properties and Software
    case 'new_property':
    case 'new_software':
    case 'edit_property':
    case 'edit_software':

        //Clean last error
        ossim_clean_error();

        //Common parameters
        $p_id = POST('property_id');

        $db   = new ossim_db();
        $conn = $db->connect();

        $num_assets = Filter_list::get_total_selection($conn, 'asset');

        $db->close();

        //Response
        $data['status'] = 'success';
        $data['data']   = sprintf(_('Your changes have been applied to %s assets'), $num_assets);

        try
        {   switch($p_id)
            {
                //Software
                case '60':

                    $tk_key = 'tk_software_form';

                    $validate = array(
                        'sw_cpe'  => array('validation' => 'OSS_ALPHA, OSS_PUNC_EXT', 'e_message' => 'illegal:' . _('Software CPE')),
                        'sw_name' => array('validation' => 'OSS_ALPHA, OSS_PUNC_EXT', 'e_message' => 'illegal:' . _('Software Name'))
                    );

                    $new_data = $_POST['item']['new_data'];

                    $p_data['save']['s_data']['cpe'  ]     = POST('sw_cpe');
                    $p_data['save']['s_data']['banner']    = POST('sw_name');
                    $p_data['save']['s_data']['source_id'] = 1;

                    if ($action == 'edit_software')
                    {
                        $validate['old_sw_cpe'] = array('validation' => 'OSS_ALPHA, OSS_PUNC_EXT', 'e_message'  =>  'illegal:' . _('Old Software CPE'));

                        $old_data = $_POST['item']['old_data'];

                        $p_data['delete']['old_sw_cpe'] = POST('old_sw_cpe');
                    }

                    $p_functions = array(
                        'delete' => 'Asset_host_software::bulk_delete_software_from_db',
                        'save'   => 'Asset_host_software::bulk_save_software_in_db'
                    );

                break;

                //Asset properties
                default:

                    $tk_key = 'tk_properties_form';

                    $validate = array(
                        'p_value'  =>  array('validation' => 'OSS_ALPHA, OSS_PUNC_EXT',   'e_message'  =>  'illegal:' . _('Property value')),
                        'p_locked' =>  array('validation' => 'OSS_BINARY, OSS_NULLABLE',  'e_message'  =>  'illegal:' . _('Property is locked'))
                    );

                    $new_data = $_POST['item']['new_data'];

                    $p_data['save']['property_id'] = $p_id;
                    $p_data['save']['p_value']     = POST('p_value');
                    $p_data['save']['source_id']   = (intval(POST('p_locked')) == 1) ? 1 : 2;


                    if ($action == 'edit_property')
                    {
                        $validate['old_p_value'] = array('validation' => 'OSS_ALPHA, OSS_PUNC_EXT',   'e_message'  =>  'illegal:' . _('Old Property value'));

                        $p_data['delete']['property_id']  = $p_id;
                        $p_data['delete']['old_p_value']  = POST('old_p_value');
                    }


                    $p_functions = array(
                        'delete' => 'Asset_host_properties::bulk_delete_property_from_db',
                        'save'   => 'Asset_host_properties::bulk_save_property_in_db'
                    );

                break;
            }


            //Validate Form token

            $token = POST('token');

            if (Token::verify($tk_key, $token) == FALSE)
            {
                $error = Token::create_error_message();

                Util::response_bad_request($error);
            }

            //General validation
            $validation_errors = validate_form_fields('POST', $validate);

            if (is_array($validation_errors) && !empty($validation_errors))
            {
                //Formatted message
                $error_msg = '<div style="text-align:left; padding-left: 10px;">
                                  <div>'._('The following errors occurred').":</div>
                                  <div style='padding: 5px;'>".implode('<br/>', $validation_errors).'</div>
                              </div>';

                Util::response_bad_request($error_msg);
            }
            else
            {

                $db   = new ossim_db();
                $conn = $db->connect();

                $parameters = array();

                if (preg_match('/^edit/', $action))
                {
                    $parameters = array_values($p_data['delete']);

                    //Adding BD connection
                    array_unshift($parameters, $conn);

                    call_user_func_array($p_functions['delete'], $parameters);
                }


                $parameters = array_values($p_data['save']);

                //Adding BD connection
                array_unshift($parameters, $conn);

                //Report changes
                $parameters[] = TRUE;

                call_user_func_array($p_functions['save'], $parameters);

                $db->close();
            }
        }
        catch(Exception $e)
        {
            Util::response_bad_request(_('Your changes could not be saved'));
        }

        $db->close();

    break;


    case 'delete_properties':
    case 'delete_software':

        //Flag to delete all properties or services
        $delete_all = FALSE;

        $selection_type   = POST('selection_type');
        $selection_filter = POST('selection_filter');

        $p_list           = POST('items');


        $db   = new ossim_db();
        $conn = $db->connect();

        $num_assets = Filter_list::get_total_selection($conn, 'asset');


        if ($action == 'delete_properties')
        {
            $tk_key = 'tk_properties_form';
        }
        else
        {
            $tk_key = 'tk_software_form';
        }

        //Response
        $data['status'] = 'success';
        $data['data']   = sprintf(_('Your changes have been applied to %s assets'), $num_assets);


        if ($selection_type == 'filter')
        {
            if (empty($selection_filter))
            {
                $delete_all = TRUE;
            }
            else
            {
                ossim_valid($selection_filter, OSS_INPUT,  'illegal: '._('Selection filter'));

                if (ossim_error())
                {
                    $db->close();

                    Util::response_bad_request(ossim_get_error_clean());
                }

                //Getting properties
                $selection_filter = escape_sql($selection_filter, $conn);

                if ($action == 'delete_properties')
                {
                    $filters        = array('where' => 'value LIKE "%'.$selection_filter.'%"');
                    list($p_list, ) = Asset_host_properties::bulk_get_list($conn, $filters);
                }
                else
                {
                    $filters        = array('where' => 'banner LIKE "%'.$selection_filter.'%"');
                    list($p_list, ) = Asset_host_software::bulk_get_list($conn, $filters);
                }
            }
        }


        //Validate Form token
        $token = POST('token');

        if (Token::verify($tk_key, $token) == FALSE)
        {
            $db->close();

            $error = Token::create_error_message();

            Util::response_bad_request($error);
        }


        if ($delete_all == TRUE)
        {
            try
            {
                if ($action == 'delete_properties')
                {
                    Asset_host_properties::bulk_delete_all_from_db($conn);
                }
                else
                {
                    Asset_host_software::bulk_delete_all_from_db($conn);
                }
            }
            catch(Exception $e)
            {
                $db->close();

                Util::response_bad_request($e->getMessage());
            }
        }
        else
        {
            if (is_array($p_list) && !empty($p_list))
            {
                foreach ($p_list as $p_values)
                {
                    try
                    {
                        //Clean last error
                        ossim_clean_error();

                        //Initialize property data
                        $p_data = array();


                        //Common parameters
                        $p_id = $p_values['p_id'];

                        switch($p_id)
                        {
                            //Software
                            case '60':

                                $validate = array(
                                    'cpe' => array('validation' => array(OSS_ALPHA, OSS_PUNC_EXT, OSS_BRACKET), 'e_message'  =>  'illegal:' . _('Software CPE'))
                                );

                                $p_data['cpe'] = $p_values['sw_cpe'];

                                $p_function = 'Asset_host_software::bulk_delete_software_from_db';
                            break;

                            //Asset properties
                            default:

                                $validate = array(
                                    'p_id'      =>  array('validation' => array(OSS_DIGIT),               'e_message'  =>  'illegal:' . _('Property ID')),
                                    'value'     =>  array('validation' => array(OSS_ALPHA, OSS_PUNC_EXT), 'e_message'  =>  'illegal:' . _('Property value'))
                                );

                                $p_data['p_id']  = $p_id;
                                $p_data['value'] = $p_values['p_value'];

                                $p_function = 'Asset_host_properties::bulk_delete_property_from_db';
                            break;
                        }


                        //Validate property values
                        foreach($validate as $v_key => $v_data)
                        {
                            $parameters = $v_data['validation'];

                            array_unshift($parameters, $p_data[$v_key]);
                            array_push($parameters, $v_data['e_message']);

                            call_user_func_array('ossim_valid', $parameters);

                            if (ossim_error())
                            {
                                $exp_msg = ossim_get_error();

                                Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
                            }
                        }

                        //Delete property
                        $parameters = array_values($p_data);

                        //Adding BD connection
                        array_unshift($parameters, $conn);

                        //Report changes
                        $parameters[] = TRUE;

                        call_user_func_array($p_function, $parameters);
                    }
                    catch(Exception $e)
                    {
                        $data['status'] = 'error';
                    }
                }
            }

            if ($data['status'] == 'error')
            {
                $db->close();

                Util::response_bad_request(_('Some of your changes could not be saved'));
            }
        }

        $db->close();

    break;
}


echo json_encode($data);
