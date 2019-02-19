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
    case 'delete_asset':

        //Validate Form token

        $token = POST('token');

        if (Token::verify('tk_asset_form', $token) == FALSE)
        {
            $error = Token::create_error_message();

            Util::response_bad_request($error);
        }

        $asset_id = POST('asset_id');

        $db    = new ossim_db();
        $conn  = $db->connect();

        $can_i_modify_ips = Asset_host::can_i_modify_ips($conn, $asset_id);

        $db->close();

        if (!valid_hex32($asset_id) || $can_i_modify_ips == FALSE)
        {
            Util::response_bad_request(_('Error! Asset ID not allowed.  Asset could not be deleted'));
        }
        else
        {
            try
            {
                $db    = new ossim_db();
                $conn  = $db->connect();

                Asset_host::delete_from_db($conn, $asset_id, TRUE);

                $db->close();

                $data['status'] = 'success';
                $data['data']   = sprintf(_('Asset has been permanently deleted from %s'), $app_name);

            }
            catch(Exception $e)
            {
                Util::response_bad_request(_('Error! Asset could not be deleted') . ': ' . $e->getMessage());
            }
        }

    break;


    case 'add_to_groups':

        //Validate Form token

        $token = POST('token');

        if (Token::verify('tk_asset_form', $token) == FALSE)
        {
            $error = Token::create_error_message();

            Util::response_bad_request($error);
        }

        $asset_id = POST('asset_id');

        if (!valid_hex32($asset_id))
        {
            Util::response_bad_request(_('Error! Asset ID not allowed.  Error! Asset could not be added to selected Asset Groups'));
        }
        else
        {
            try
            {
                $db   = new ossim_db();
                $conn = $db->connect();

                $num_groups = Filter_list::get_total_selection($conn, 'group');

                $asset = new Asset_host($conn, $asset_id);
                $asset->add_to_groups($conn);

                $db->close();

                $data['status'] = 'success';
                $data['data']   = sprintf(_("Asset have been added to %s groups"), $num_groups);
            }
            catch(Exception $e)
            {
                Util::response_bad_request(_('Error! Asset could not be added to selected Asset Groups') . ': ' . $e->getMessage());
            }
        }

    break;

    case 'delete_from_groups':

        //Validate Form token

        $token = POST('token');

        if (Token::verify('tk_asset_form', $token) == FALSE)
        {
            $error = Token::create_error_message();

            Util::response_bad_request($error);
        }

        $asset_id = POST('asset_id');

        if (!valid_hex32($asset_id))
        {
            Util::response_bad_request(_('Error! Asset ID not allowed.  Asset could not be deleted from selected Asset Groups'));
        }
        else
        {
            try
            {
                $db   = new ossim_db();
                $conn = $db->connect();

                $num_groups = Filter_list::get_total_selection($conn, 'group');

                $asset = new Asset_host($conn, $asset_id);
                $asset->delete_from_groups($conn);

                $db->close();

                $data['status'] = 'success';
                $data['data']   = sprintf(_("Asset have been deleted from %s groups"), $num_groups);

            }
            catch(Exception $e)
            {
                Util::response_bad_request(_('Error! Asset could not be deleted from selected Asset Groups') . ': ' . $e->getMessage());
            }
        }

    break;

    case 'add_port':

        //Validate Form token

        $token = POST('token');

        if (Token::verify('tk_services_form', $token) == FALSE)
        {
            $error = Token::create_error_message();

            Util::response_bad_request($error);
        }

        $validate = array(
            'asset_id'   =>  array('validation' => 'OSS_HEX',                   'e_message'  =>  'illegal:' . _('Asset ID')),
            's_port'     =>  array('validation' => 'OSS_PORT',                  'e_message'  =>  'illegal:' . _('Port')),
            's_protocol' =>  array('validation' => 'OSS_PROTOCOL_SERVICE',      'e_message'  =>  'illegal:' . _('Protocol')),
            's_name'     =>  array('validation' => 'OSS_ALPHA, OSS_PUNC_EXT',   'e_message'  =>  'illegal:' . _('Service'))
        );


        $data['status'] = 'success';
        $data['data']   = _('Your changes have been saved');


        $validation_errors = validate_form_fields('POST', $validate);

        if (is_array($validation_errors) && !empty($validation_errors))
        {
            //Formatted message
            $error_msg = '<div>'._('The following errors occurred').":</div>
                          <div style='padding: 5px;'>".implode('<br/>', $validation_errors).'</div>';

            Util::response_bad_request($error_msg);
        }
        else
        {
            try
            {
                $db    = new ossim_db();
                $conn  = $db->connect();

                $asset_id = POST('asset_id');
                $protocol = POST('s_protocol');
                $protocol_name = Protocol::get_protocol_by_number($protocol);
                $port     = POST('s_port');
                $service  = POST('s_name');
                $ctx      = Asset_host::get_ctx_by_id($conn, $asset_id);

                $n_ports  = Port::get_list($conn, " AND port_number = $port and protocol_name = '$protocol_name'");

                if(count($n_ports) == 0)
                {
                    Port::insert($conn, $port, $protocol_name, $service, '', $ctx);
                }
                else
                {
                    $data['status']  = 'warning';
                    $data['data']    = _('Warning! This port has already been added');
                }

                $db->close();
            }
            catch(Exception $e)
            {
                Util::response_bad_request(_('Error! Your changes could not be saved'));
            }
        }

    break;

    //Asset properties, MAC address, Software and Services
    case 'new_property':
    case 'new_software':
    case 'new_service':
    case 'edit_property':
    case 'edit_software':
    case 'edit_service':

        //Clean last error
        ossim_clean_error();

        //Response
        $data['status'] = 'success';
        $data['data']   = _('Your changes have been saved');


        //Common parameters
        $asset_id = POST('asset_id');
        $p_id     = POST('property_id');

        try
        {
            switch($p_id)
            {
                //Services
                case '40':

                    $tk_key = 'tk_services_form';

                    $validate = array(
                        'asset_id'     =>  array('validation' => 'OSS_HEX',                   'e_message'  =>  'illegal:' . _('Asset ID')),
                        's_ip'         =>  array('validation' => 'OSS_IP_ADDR',               'e_message'  =>  'illegal:' . _('Asset IP')),
                        's_port'       =>  array('validation' => 'OSS_PORT',                  'e_message'  =>  'illegal:' . _('Port')),
                        's_protocol'   =>  array('validation' => 'OSS_PROTOCOL_SERVICE',      'e_message'  =>  'illegal:' . _('Protocol')),
                        's_name'       =>  array('validation' => 'OSS_ALPHA, OSS_PUNC_EXT',   'e_message'  =>  'illegal:' . _('Service'))
                    );


                    $p_data['save']['asset_id']             = $asset_id;
                    $p_data['save']['s_data']['ip']         = POST('s_ip');
                    $p_data['save']['s_data']['port']       = POST('s_port');
                    $p_data['save']['s_data']['protocol']   = POST('s_protocol');
                    $p_data['save']['s_data']['service']    = POST('s_name');
                    $p_data['save']['s_data']['nagios']     = intval(POST('nagios'));
                    $p_data['save']['s_data']['version']    = POST('version');
                    $p_data['save']['s_data']['source_id']  = 1;

                    if ($action == 'edit_service')
                    {
                        $validate['version']        = array('validation' => 'OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE',    'e_message'  =>  'illegal:' . _('Version'));
                        $validate['nagios']         = array('validation' => 'OSS_BINARY',                               'e_message'  =>  'illegal:' . _('Nagios'));
                        $validate['old_s_ip']       = array('validation' => 'OSS_IP_ADDR',                              'e_message'  =>  'illegal:' . _('Old asset IP'));
                        $validate['old_s_port']     = array('validation' => 'OSS_PORT',                                 'e_message'  =>  'illegal:' . _('Old port'));
                        $validate['old_s_protocol'] = array('validation' => 'OSS_PROTOCOL_SERVICE',                     'e_message'  =>  'illegal:' . _('Old protocol'));


                        $p_data['delete']['asset_id']        = $asset_id;
                        $p_data['delete']['old_s_ip']        = POST('old_s_ip');
                        $p_data['delete']['old_s_port']      = POST('old_s_port');
                        $p_data['delete']['old_s_protocol']  = POST('old_s_protocol');
                    }

                    $p_functions = array(
                        'delete' => 'Asset_host_services::delete_service_from_db',
                        'save'   => 'Asset_host_services::save_service_in_db'
                    );

                break;

                //MAC
                case '50':

                    $tk_key = 'tk_properties_form';

                    $validate = array(
                        'asset_id'  =>  array('validation' => 'OSS_HEX',      'e_message'  =>  'illegal:' . _('Asset ID')),
                        'mac_ip'    =>  array('validation' => 'OSS_IP_ADDR',  'e_message'  =>  'illegal:' . _('Asset IP')),
                        'mac'       =>  array('validation' => 'OSS_MAC',      'e_message'  =>  'illegal:' . _('MAC Address'))
                    );

                    $new_data = $_POST['item']['new_data'];

                    $p_data['save']['asset_id'] = $asset_id;
                    $p_data['save']['ip']       = POST('mac_ip');
                    $p_data['save']['mac']      = POST('mac');

                    if ($action == 'edit_property')
                    {
                        $validate['old_mac_ip'] = array('validation' => 'OSS_IP_ADDR',  'e_message'  =>  'illegal:' . _('Old asset IP'));
                        $validate['old_mac']    = array('validation' => 'OSS_MAC',      'e_message'  =>  'illegal:' . _('Old MAC Address'));

                        $p_data['delete']['asset_id']   = $asset_id;
                        $p_data['delete']['old_mac_ip'] = POST('old_mac_ip');
                        $p_data['delete']['old_mac']    = POST('old_mac');

                    }

                    $p_functions = array(
                        'delete' => 'Asset_host_ips::delete_mac_from_db',
                        'save'   => 'Asset_host_ips::save_mac_in_db'
                    );

                break;

                //Software
                case '60':

                    $tk_key = 'tk_software_form';

                    $validate = array(
                        'asset_id'  =>  array('validation' => 'OSS_HEX',                    'e_message'  =>  'illegal:' . _('Asset ID')),
                        'sw_cpe'    =>  array('validation' => 'OSS_ALPHA, OSS_PUNC_EXT',    'e_message'  =>  'illegal:' . _('Software CPE')),
                        'sw_name'   =>  array('validation' => 'OSS_ALPHA, OSS_PUNC_EXT',    'e_message'  =>  'illegal:' . _('Software Name'))
                    );

                    $new_data = $_POST['item']['new_data'];

                    $p_data['save']['asset_id']            = $asset_id;
                    $p_data['save']['s_data']['cpe'  ]     = POST('sw_cpe');
                    $p_data['save']['s_data']['banner']    = POST('sw_name');
                    $p_data['save']['s_data']['source_id'] = 1;

                    if ($action == 'edit_software')
                    {
                        $validate['old_sw_cpe'] = array('validation' => 'OSS_ALPHA, OSS_PUNC_EXT', 'e_message'  =>  'illegal:' . _('Old Software CPE'));

                        $old_data = $_POST['item']['old_data'];

                        $p_data['delete']['asset_id']   = $asset_id;
                        $p_data['delete']['old_sw_cpe'] = POST('old_sw_cpe');
                    }

                    $p_functions = array(
                        'delete' => 'Asset_host_software::delete_software_from_db',
                        'save'   => 'Asset_host_software::save_software_in_db'
                    );

                break;

                //Asset properties
                default:

                    $tk_key = 'tk_properties_form';

                    $validate = array(
                        'asset_id'    =>  array('validation' => 'OSS_HEX',                   'e_message'  =>  'illegal:' . _('Asset ID')),
                        'p_value'     =>  array('validation' => 'OSS_ALPHA, OSS_PUNC_EXT',   'e_message'  =>  'illegal:' . _('Property value')),
                        'p_locked'    =>  array('validation' => 'OSS_BINARY, OSS_NULLABLE',  'e_message'  =>  'illegal:' . _('Property is locked'))
                    );

                    $new_data = $_POST['item']['new_data'];

                    $p_data['save']['asset_id']    = $asset_id;
                    $p_data['save']['property_id'] = $p_id;
                    $p_data['save']['p_value']     = POST('p_value');
                    $p_data['save']['source_id']   = (intval(POST('p_locked')) == 1) ? 1 : 2;


                    if ($action == 'edit_property')
                    {
                        $validate['old_p_value'] = array('validation' => 'OSS_ALPHA, OSS_PUNC_EXT',   'e_message'  =>  'illegal:' . _('Old Property value'));

                        $p_data['delete']['asset_id']     = $asset_id;
                        $p_data['delete']['property_id']  = $p_id;
                        $p_data['delete']['old_p_value']  = POST('old_p_value');
                    }


                    $p_functions = array(
                        'delete' => 'Asset_host_properties::delete_property_from_db',
                        'save'   => 'Asset_host_properties::save_property_in_db'
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
                $db    = new ossim_db();
                $conn  = $db->connect();

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

    break;


    case 'delete_properties':
    case 'delete_software':
    case 'delete_services':

        $db   = new ossim_db();
        $conn = $db->connect();

        //Flag to delete all properties or services
        $delete_all = FALSE;

        $asset_id         = POST('asset_id');
        $selection_type   = POST('selection_type');
        $selection_filter = POST('selection_filter');
        $p_list           = POST('items');

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

                //Create asset object
                $asset_host = new Asset_host($conn, $asset_id);

                if ($action == 'delete_properties')
                {
                    $filters        = array('where' => 'value LIKE "%'.$selection_filter.'%"');
                    list($p_list, ) = $asset_host->get_properties($conn, $filters);
                }
                elseif ($action == 'delete_software')
                {
                    $filters        = array('where' => '(banner LIKE "%'.$selection_filter.'%"');
                    list($p_list, ) = $asset_host->get_software($conn, $filters);
                }
                else
                {
                    $filters        = array('where' => 'service LIKE "%'.$selection_filter.'%"');
                    list($p_list, ) = $asset_host->get_services($conn, $filters);
                }
            }
        }

        //Validate Form token
        $token = POST('token');

        if ($action == 'delete_properties')
        {
            $tk_key = 'tk_properties_form';
        }
        elseif ($action == 'delete_services')
        {
            $tk_key = 'tk_services_form';
        }
        else
        {
            $tk_key = 'tk_software_form';
        }

        if (Token::verify($tk_key, $token) == FALSE)
        {
            $db->close();

            $error = Token::create_error_message();

            Util::response_bad_request($error);
        }


        $data['status'] = 'success';
        $data['data']   = _('Your changes have been saved');


        if ($delete_all == TRUE)
        {
            if (!valid_hex32($asset_id))
            {
                $db->close();

                Util::response_bad_request(_('Error! Asset ID not allowed. Your changes could not be saved'));
            }
            else
            {
                try
                {
                    if ($action == 'delete_properties')
                    {
                        Asset_host_ips::delete_all_from_db($conn, $asset_id, TRUE);
                        Asset_host_properties::delete_all_from_db($conn, $asset_id);
                    }
                    elseif ($action == 'delete_software')
                    {
                        Asset_host_software::delete_all_from_db($conn, $asset_id);
                    }
                    else
                    {
                        Asset_host_services::delete_all_from_db($conn, $asset_id, TRUE);
                    }
                }
                catch(Exception $e)
                {
                    $db->close();

                    Util::response_bad_request($e->getMessage());
                }
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
                        $p_data['asset_id'] = $asset_id;
                        $p_id               = $p_values['p_id'];

                        switch($p_id)
                        {
                            //Services
                            case '40':

                                $validate = array(
                                    'asset_id'  =>  array('validation' => array(OSS_HEX),               'e_message'  =>  'illegal:' . _('Asset ID')),
                                    'ip'        =>  array('validation' => array(OSS_IP_ADDR),           'e_message'  =>  'illegal:' . _('Asset IP')),
                                    'port'      =>  array('validation' => array(OSS_PORT),              'e_message'  =>  'illegal:' . _('Port')),
                                    'protocol'  =>  array('validation' => array(OSS_PROTOCOL_SERVICE),  'e_message'  =>  'illegal:' . _('Protocol'))
                                );

                                $p_data['ip']       = $p_values['s_ip'];
                                $p_data['port']     = $p_values['s_port'];
                                $p_data['protocol'] = $p_values['s_protocol'];

                                $p_function = 'Asset_host_services::delete_service_from_db';
                            break;

                            //MAC
                            case '50':

                                $validate = array(
                                    'asset_id' =>  array('validation' => array(OSS_HEX),      'e_message'  =>  'illegal:' . _('Asset ID')),
                                    'ip'       =>  array('validation' => array(OSS_IP_ADDR),  'e_message'  =>  'illegal:' . _('Asset IP')),
                                    'mac'      =>  array('validation' => array(OSS_MAC),      'e_message'  =>  'illegal:' . _('MAC Address'))
                                );

                                $p_data['ip']  = $p_values['extra'];
                                $p_data['mac'] = $p_values['p_value'];

                                $p_function = 'Asset_host_ips::delete_mac_from_db';
                            break;

                            //Software
                            case '60':

                                $validate = array(
                                    'asset_id' =>  array('validation' => array(OSS_HEX),                              'e_message'  =>  'illegal:' . _('Asset ID')),
                                    'cpe'      =>  array('validation' => array(OSS_ALPHA, OSS_PUNC_EXT, OSS_BRACKET), 'e_message'  =>  'illegal:' . _('Software CPE'))
                                );

                                $p_data['cpe'] = $p_values['sw_cpe'];

                                $p_function = 'Asset_host_software::delete_software_from_db';
                            break;

                            //Asset properties
                            default:

                                $validate = array(
                                    'asset_id'  =>  array('validation' => array(OSS_HEX),                 'e_message'  =>  'illegal:' . _('Asset ID')),
                                    'p_id'      =>  array('validation' => array(OSS_DIGIT),               'e_message'  =>  'illegal:' . _('Property ID')),
                                    'value'     =>  array('validation' => array(OSS_ALPHA, OSS_PUNC_EXT), 'e_message'  =>  'illegal:' . _('Property value'))
                                );

                                $p_data['p_id']  = $p_id;
                                $p_data['value'] = $p_values['p_value'];

                                $p_function = 'Asset_host_properties::delete_property_from_db';
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


    case 'enable_monitoring':
    case 'disable_monitoring':

        $toggle_all = FALSE;

        if ($action == 'enable_monitoring')
        {
            $action_to_execute = 'enabled';
            $nagios            = 1;
        }
        else
        {
            $action_to_execute = 'disabled';
            $nagios            = 0;
        }

        //Validate Form token
        $token  = POST('token');

        if (Token::verify('tk_services_form', $token) == FALSE)
        {
            $error = Token::create_error_message();

            Util::response_bad_request($error);
        }


        $asset_id         = POST('asset_id');
        $selection_type   = POST('selection_type');
        $selection_filter = POST('selection_filter');
        $s_list           = POST('items');


        $db   = new ossim_db();
        $conn = $db->connect();


        if ($selection_type == 'filter')
        {
            if (empty($selection_filter))
            {
                $toggle_all = TRUE;
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

                //Create asset object
                $asset_host = new Asset_host($conn, $asset_id);

                $filters        = array('where' => 'AND service LIKE "%'.$selection_filter.'%"');
                list($s_list, ) = $asset_host->get_services($conn, $filters);
            }
        }
        if (Asset_host_scan::is_plugin_in_host($conn,$asset_id,2007)) {
            Asset_host_scan::bulk_disable_monitoring($conn);
            //Oh gods of programming I was forced to use this ugly method, 
            //because on lovel levels of socket creationg there is no possibility to wait until socket responce.
            //and to change this behavior I would have to rewrite half of the framework
            sleep(2);
            Asset_host_scan::bulk_enable_monitoring($conn);
        }
        $data['status'] = 'success';
        $data['data']   = _('Your changes have been saved');


        if ($toggle_all == TRUE)
        {
            if (!valid_hex32($asset_id))
            {
                $db->close();

                Util::response_bad_request(_('Error! Asset ID not allowed. Your changes could not be saved'));
            }
            else
            {
                try
                {
                    Asset_host_services::toggle_nagios($conn, $asset_id, $nagios);
                }
                catch(Exception $e)
                {
                    $db->close();

                    Util::response_bad_request($e->getMessage());
                }
            }
        }
        else
        {
            if (is_array($s_list) && !empty($s_list))
            {
                foreach ($s_list as $s_values)
                {
                    try
                    {
                        //Clean last error
                        ossim_clean_error();

                        $validate = array(
                            'asset_id'   =>  array('validation' => array(OSS_HEX),               'e_message'  =>  'illegal:' . _('Asset ID')),
                            'ip'         =>  array('validation' => array(OSS_IP_ADDR),           'e_message'  =>  'illegal:' . _('Asset IP')),
                            'port'       =>  array('validation' => array(OSS_PORT),              'e_message'  =>  'illegal:' . _('Port')),
                            'protocol'   =>  array('validation' => array(OSS_PROTOCOL_SERVICE),  'e_message'  =>  'illegal:' . _('Protocol'))
                        );

                        //Initialize service data
                        $s_data = array();

                        //Asset ID
                        $s_data['asset_id']   = $asset_id;
                        $s_data['nagios']     = $nagios;
                        $s_data['ip']         = $s_values['s_ip'];
                        $s_data['port']       = $s_values['s_port'];
                        $s_data['protocol']   = $s_values['s_protocol'];


                        $p_function = 'Asset_host_services::toggle_nagios';
                        //Validate service values
                        foreach($validate as $v_key => $v_data)
                        {
                            $parameters = $v_data['validation'];

                            array_unshift($parameters, $s_data[$v_key]);
                            array_push($parameters, $v_data['e_message']);

                            call_user_func_array('ossim_valid', $parameters);

                            if (ossim_error())
                            {
                                $exp_msg = ossim_get_error();

                                Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
                            }
                        }

                        //Update Nagios
                        $parameters = array();

                        $parameters = array_values($s_data);

                        //Adding BD connection
                        array_unshift($parameters, $conn);
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


            //Add host to nagios
            Asset_host_scan::save_plugin_in_db($conn, $asset_id, 2007);

            //report changes
            Asset_host::report_changes($conn, 'hosts');
        }

        $db->close();

    break;
}


echo json_encode($data);
