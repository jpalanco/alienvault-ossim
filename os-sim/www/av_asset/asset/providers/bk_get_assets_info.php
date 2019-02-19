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

Session::logcheck_ajax('environment-menu', 'PolicyHosts');

session_write_close();

$validate = array(
    'asset_type'  =>  array('validation' => 'OSS_LETTER',  'e_message'  =>  'illegal:' . _('Asset Type'))
);

$asset_type = POST('asset_type');


$validation_errors = validate_form_fields('POST', $validate);

if (!empty($validation_errors))
{
    Util::response_bad_request(_('Sorry, asset data was not loaded due to a validation error'));
}


$db   = new ossim_db();
$conn = $db->connect();


$ctx = Asset_host::get_common_ctx($conn);

if (!empty($ctx))
{
    $ctx_name = Session::get_entity_name($conn, $ctx);
    $ctx_name = Util::utf8_encode2($ctx_name);
    
    //Check asset context
    $ext_ctxs = Session::get_external_ctxs($conn);
    
    if (!empty($ext_ctxs[$ctx]))
    {
        // CTX is external, this CTX could not be edited
        $ctx = NULL;
    }
    else
    {
        //Server related to CTX
        $server_obj = Server::get_server_by_ctx($conn, $ctx);
        
        $s_name = '';
        $s_ip   = '';
        
        if ($r_server)
        {
            $s_name = $server_obj->get_name();
            $s_ip   = $server_obj->get_ip();
        }        
    }
}


$data['status'] = 'OK';
$data['data']   = array();

if (!empty($ctx))
{
    $data['status'] = 'OK';
    $data['data']   = array(
        'asset_type' => $asset_type,
        'ctx' => array(
            'id'   => $ctx,
            'name' => $ctx_name,
            'related_server' => array(
                'ip'   => $s_ip,
                'name' => $s_name
            )
        )  
    );
}


$db->close();
echo json_encode($data);
