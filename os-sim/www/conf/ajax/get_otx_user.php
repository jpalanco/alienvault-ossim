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

// Security token
if (!Token::verify('tk_configuration_main', GET('token')))
{
    $response['error'] = TRUE ;
    $response['msg']   = _('Invalid Action');
    
    echo json_encode($response);
    exit;
}

$response     = array();
$otx_username = '';
$token        = POST('token');

/* VALIDATION */
ossim_valid($token, OSS_ALPHA, 'illegal:' . _("OTX auth-token"));

if (ossim_error())
{
    $response['error'] = TRUE ;
    $response['msg']   = ossim_get_error();
    
    echo json_encode($response);
    exit;
}

$response['error'] = FALSE;

$response['msg']   = Util::get_otx_username($token);

// Some error fetching the username
if ($response['msg'])
{
    $response['error'] = TRUE;
    $response['msg']   = _('Unable to activate user or Invalid OTX auth-token');
}
// Success: saved in db conf, now response it
else
{
    $conf         = new Config();
    $otx_username = $conf->get_conf('open_threat_exchange_username');
    
    // If username is still empty there was an error
    if ($otx_username == '')
    {
        $response['error'] = TRUE ;
        $response['msg']   = _('Unable to activate user or Invalid OTX auth-token');
    }
    else
    {
        $response['msg'] = $otx_username;
    }
}

echo json_encode($response);

