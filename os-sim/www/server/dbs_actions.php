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

Session::logcheck('analysis-menu', 'EventsForensics');

session_write_close();

//Validate action type

$action  = POST('action');

ossim_valid($action, OSS_LETTER, '_',   'illegal:' . _('Action'));

if (ossim_error())
{
    $data['status']  = 'error';
	$data['data']    = ossim_get_error_clean();

	echo json_encode($data);
    exit();
}


//Validate Form token

$token = POST('token');

if (Token::verify('tk_db_form', POST('token')) == FALSE)
{
	$data['status']  = 'error';
	$data['data']    = Token::create_error_message();

	echo json_encode($data);
    exit();
}

switch($action)
{
    case 'remove_icon':

        $validate = array(
            'asset_id'  =>  array('validation' => 'OSS_DIGIT',  'e_message'  =>  'illegal:' . _('Database ID'))
        );

        $id = POST('asset_id');

        $validation_errors = validate_form_fields('POST', $validate);


        if (is_array($validation_errors) && !empty($validation_errors))
        {
            $data['status']  = 'error';
            $data['data']    = _('Error! Database ID not allowed.  Icon could not be removed');
        }
        else
        {
            try
            {
                $db    = new ossim_db();
                $conn  = $db->connect();

                Databases::delete_icon($conn, $id);

                $db->close();

                $data['status']  = 'OK';
                $data['data']    = _('Database icon removed successfully');
            }
            catch(Exception $e)
            {
                $data['status']  = 'error';
                $data['data']    = _('Error! Database icon could not be removed');
            }
        }

    break;

    case 'delete_db':

        $validate = array(
            'id'  =>  array('validation' => 'OSS_DIGIT',  'e_message'  =>  'illegal:' . _('Database ID'))
        );

        $id = POST('id');

        $validation_errors = validate_form_fields('POST', $validate);


        if (is_array($validation_errors) && !empty($validation_errors))
        {
            $data['status']  = 'error';
            $data['data']    = _('Error! Database ID not allowed.  Database could not be removed');
        }
        else
        {
            try
            {
                $db    = new ossim_db();
                $conn  = $db->connect();

                Databases::delete($conn, $id);

                $db->close();

                $data['status']  = 'OK';
                $data['data']    = _('Database removed successfully');

            }
            catch(Exception $e)
            {
                $data['status']  = 'error';
                $data['data']    = _('Error! Database could not be removed');
            }
        }

    break;
}

echo json_encode($data);
exit();
?>