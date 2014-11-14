<?php
/**
*
* License:
*
* Copyright (c) 2003-2006 ossim.net
* Copyright (c) 2007-2014 AlienVault
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

$action      = POST('action');
$server_id   = POST('server_id');
$remoteadmin = POST('remoteadmin');
$remotepass  = POST('remotepass');
$remoteurl   = POST('remoteurl');

ossim_valid($action,      OSS_ALPHA,                         'illegal:' . _('Action'));
ossim_valid($server_id,   OSS_HEX,                           'illegal:' . _('Server ID'));
ossim_valid($remoteadmin, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _('Remote admin'));
ossim_valid($remotepass,  OSS_PASSWORD, OSS_NULLABLE,        'illegal:' . _('Remote password'));
ossim_valid($remoteurl,   OSS_ALPHA, OSS_PUNC, OSS_NULLABLE, 'illegal:' . _('Remote URL'));

if (ossim_error())
{
    $response['error'] = TRUE;
    $response['msg']   = ossim_get_error();
    
    echo json_encode($response);
    exit;
}

$db   = new ossim_db();
$conn = $db->connect();

// Set SSH Key and update Logger role to 'yes'
if ($action == 'set')
{
    ob_start();
    
    $success = Server::set_remote_sshkey($remoteadmin, $remotepass, $remoteurl);
    
    // Get error message
    $output = ob_get_contents();
    
    // Clean links
    $output = preg_replace('/\<a .+\>.+\<\/a\>/', '', $output);
    
    ob_end_clean();
    
    if ($success)
    {
        Server::set_role_sem($conn, $server_id, 1);
        
        $response['error'] = FALSE;
        $response['msg']   = _('Remote logger has been successfully configured');
    }
    else
    {
        $response['error'] = TRUE;
        $response['msg']   = $output;
    }
}

// Update Logger role to 'no'
elseif ($action == 'remove')
{
    Server::set_role_sem($conn, $server_id, 0);
    
    $response['error'] = FALSE;
    $response['msg']   = _('Remote logger has been successfully removed');
}

$db->close();

echo json_encode($response);

/* End of file set_remote_key.php */
/* Location: ../../server/ajax/set_remote_key.php */