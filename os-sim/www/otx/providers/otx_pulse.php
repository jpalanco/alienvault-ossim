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

Session::logcheck_ajax("dashboard-menu", "IPReputation");
session_write_close();

/*
* This function gets the pulse detail.
*
* @return array
*
*/
function get_pulse_detail()
{
    $data  = POST('data');

    ossim_valid($data['pulse_id'],  OSS_HEX, 'illegal: Pulse ID');

    if (ossim_error())
    {
        return array();
    }

    $otx   = new Otx();
    $pulse = $otx->get_pulse_detail($data['pulse_id']);

    //Converting indicator hash to array to use it in the datatables.
    $pulse['indicators'] = array_values($pulse['indicators']);

    return $pulse;
}


//Checking the action to perform.
$action = POST('action'); 
$result = array();

try
{
    switch($action)
    {
        case 'detail':
            $result = get_pulse_detail();
        break;
        
        default:
            Av_exception::throw_error(Av_exception::USER_ERROR, _('Invalid Action.'));
    }
}
catch (Exception $e)
{
    Util::response_bad_request($e->getMessage());
}


echo json_encode($result);