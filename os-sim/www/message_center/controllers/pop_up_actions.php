<?php

/**
 * pop_up_actions.php
 *
 * License:
 *
 * Copyright (c) 2003-2006 ossim.net
 * Copyright (c) 2007-2015 AlienVault
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

Session::useractive();

session_write_close();


// Response array
$data = array();


/************************************
 ********* Validate action **********
 ************************************/

// Get action type
$action = POST('action');

// Validate action type
ossim_valid($action, OSS_LETTER, '_', 'illegal:'._('Action'));

if (ossim_error())
{
    Util::response_bad_request(ossim_get_error_clean());
}


// Database access object
$db   = new Ossim_db();
$conn = $db->connect();


switch ($action)
{
    case 'track_usage_information':

        try
        {
            //Validate Token
            $token = POST('token');

            if (Token::verify('tk_tui', $token) == FALSE)
            {
                $t_error = Token::create_error_message();

                Av_exception::throw_error(Av_exception::USER_ERROR, $t_error);
            }

            if (Session::am_i_admin())
            {
                $tui = intval(POST('tui'));

                $tui_status = ($tui > 0) ? 1 : 0;

                $config = new Config();
                $config->update('track_usage_information', $tui_status);

                $client = new Alienvault_client();

                $tui_status = ($tui > 0) ? TRUE : FALSE;
                $client->system()->set_telemetry($tui_status);

                $data['status'] = 'success';
                $data['data']   = _('Your changes have been saved');
            }
            else
            {
                Av_exception::throw_error(Av_exception::USER_ERROR, _('You do not have the correct permissions to configure this option. Please contact system administrator with any questions'));
            }
        }
        catch (Exception $e)
        {
            $db->close();

            Util::response_bad_request($e->getMessage());
        }

    break;
}


$db->close();

echo json_encode($data);
