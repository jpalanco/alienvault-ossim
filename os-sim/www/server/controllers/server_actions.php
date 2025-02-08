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

Session::logcheck('configuration-menu', 'PolicyServers');

session_write_close();

//Action allowed for USM Appliance only
if (Session::is_pro() == FALSE){
    exit();
}

$action = POST('action');

//Validation
ossim_valid($action, OSS_LETTER, '_', 'illegal:' . _('Action'));

if (ossim_error()){
    Util::response_bad_request(ossim_get_error_clean());
}


$kali_statuses = array(
    1 => _("CONNECT TO USM CENTRAL"),
    2 => "",
    3 => _("DISABLE CONNECTION"),
    4 => _("DISABLE CONNECTION"),
    5 => _("DISABLE CONNECTION")
);

$extra_statuses = array(
    1 => _("Connected to "),
    2 => _("Request has been sent"),
    3 => _("Token denied by "),
    4 => _("Connected to "),
    5 => _("Failed to reach ")
);



switch($action)
{
    case 'connect':

        echo Server::kali_connect(POST("token"));

    break;

    case 'disconnect':

        echo Server::kali_disconnect();

    break;

    case 'status' :

        $res = Server::get_kali_status() ;
        $kali_status = $res->status;
        if($kali_status == "error") {
            $data['status'] = "error";
            $data['data'] = "Connection error";
        }else {
            $data['status'] = 'success';
            $kali_url = !empty($res->url) ? $extra_statuses[$kali_status] . $res->url : '' ;
            $data['data'] = ["name"=>"<i>{$kali_url}&nbsp;</i>{$kali_statuses[$kali_status]}", "bclass"=>"kali-status-{$kali_status}"];
        }

        echo json_encode($data);

    break;

    default:

    break;
}

/* End of file server_actions.php */
/* Location: ../../server/controller/server_actions.php */
