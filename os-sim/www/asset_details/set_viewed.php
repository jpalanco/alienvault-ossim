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


// Check permissions
Session::useractive();

$cell_id  = GET('id');

ossim_valid($cell_id, 	    OSS_ALPHA, OSS_DIGIT. OSS_SCORE,      'illegal: Message Id');

if (ossim_error()) {

   die(ossim_error());
}

list ($msg_id, $component_id) = explode("_", $cell_id);

$msg_id = intval($msg_id);
if (!valid_hex32($component_id, true))
{
    die(_("Invalid canonical uuid"));
}

// Call API
try
{
    $status = new System_status();
    $status->set_viewed($msg_id, $component_id);
    list($detail) = $status->get_message_detail($msg_id);
}
catch(Exception $e)
{
    // Do nothing
}
