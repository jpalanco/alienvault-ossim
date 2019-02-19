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


$m_perms  = array('environment-menu', 'environment-menu');
$sm_perms = array('PolicyHosts',      'PolicyNetworks');


Session::logcheck_ajax($m_perms, $sm_perms);


$data['status'] = 'success';
$data['data']   = _('Your changes have been saved');


$message_id = POST('message_id');

if (!valid_hex32($message_id, TRUE))
{
    Util::response_bad_request(_('Error! Message ID not allowed.  Action could not be completed'));
}


try
{
    $status = new System_notifications();

    $flags = array(
        'viewed' => 'true'
    );

    $status->set_status_message($message_id, $flags);
}
catch(Exception $e)
{
    Util::response_bad_request($e->getMessage());
}


echo json_encode($data); 
