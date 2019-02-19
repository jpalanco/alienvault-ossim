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

$asset_type = (POST('type') == 'group') ? 'group' : ((POST('type') == 'network') ? 'network' : 'asset');
$note       = POST('note');

Session::logcheck_by_asset_type($asset_type);

$validate = array(
    'type' =>  array('validation' => 'OSS_LETTER',              'e_message'  =>  'illegal:' . _('Asset Type')),
    'note' =>  array('validation' => 'OSS_TEXT, OSS_PUNC_EXT',  'e_message'  =>  'illegal:' . _('Note'))
);


/****************************************************
**************** Checking all fields ****************
*****************************************************/

//Checking form token

if (!isset($_POST['ajax_validation_all']) || POST('ajax_validation_all') == FALSE)
{
    if (Token::verify('tk_save_bulk_note', POST('token')) == FALSE)
    {
        $error = Token::create_error_message();

        Util::response_bad_request($error);
    }
}




$validation_errors = validate_form_fields('POST', $validate);


$data['status'] = 'OK';
$data['data']   = $validation_errors;

if (POST('ajax_validation_all') == TRUE)
{
    if (is_array($validation_errors) && !empty($validation_errors))
    {
        $data['status'] = 'error';
    }

    echo json_encode($data);
    exit();
}
else
{
    if (is_array($validation_errors) && !empty($validation_errors))
    {
        $data['status'] = 'error';
        $data['data']   = $validation_errors;
    }
}


if ($data['status'] != 'error')
{
    try
    {
        $db   = new ossim_db();
        $conn = $db->connect();

        Notes::bulk_insert($conn, $asset_type, gmdate("Y-m-d H:i:s"), $note);

        $num_assets = Filter_list::get_total_selection($conn, $asset_type);

        $data['status'] = 'OK';
        $data['data']   = sprintf(_('Your note has been added to (%s) assets'), $num_assets);

        $db->close();
    }
    catch(Exception $e)
    {
        Util::response_bad_request($e->getMessage());
    }
}
else
{
    //Formatted message
    $error_msg = '<div>'._('The following errors occurred').":</div>
                  <div style='padding: 5px;'>".implode('<br/>', $data['data']).'</div>';

    Util::response_bad_request($error_msg);
}
