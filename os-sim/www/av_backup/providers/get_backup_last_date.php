<?php
/**
 * get_backup_last_date.php
 * 
 * File get_backup_last_date.php is used to:
 * - Response ajax call from backup to check for last backup date made
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
 * @package    ossim-framework\Assets
 * @autor      AlienVault INC
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt
 * @copyright  2003-2006 ossim.net
 * @copyright  2007-2013 AlienVault
 * @link       https://www.alienvault.com/
 */

require_once 'av_init.php';


if (!Session::am_i_admin())
{
    $error = _("You do not have permission to see this section");
    
    Util::response_bad_request($error);
}

// Close session write for real background loading
session_write_close();


$system_id = POST('system_id');


ossim_valid($system_id, OSS_UUID, 'illegal: System ID');


if (ossim_error())
{
    Util::response_bad_request(ossim_get_error_clean());
}


try
{
    $backup_object = new Av_backup($system_id, 'configuration');
    $last_date     = $backup_object->get_session_last_date();
}
catch (Exception $e)
{
    $exp_msg = $e->getMessage();
    Util::response_bad_request($exp_msg);
}


$response['status']    = 'success';
$response['data']      = $last_date;

echo json_encode($response);



/* End of file get_backup_last_date.php */
/* Location: /av_backup/providers/get_backup_last_date.php */
