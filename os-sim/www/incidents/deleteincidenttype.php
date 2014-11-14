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


Session::logcheck("analysis-menu", "IncidentsTypes");


if ( !Session::am_i_admin() && !Session::menu_perms("analysis-menu", "IncidentsTypes") )
{
    die(ossim_error(_("Sorry, you are not allowed to perform this action")));
}

$inctype_id = POST('inctype_id');

ossim_valid($inctype_id, OSS_ALPHA, OSS_SPACE, OSS_PUNC, 'illegal:' . _("Incident ID"));

if ( ossim_error() ) {
   
	$data['status']  = 'error';
	$data['data']    = ossim_get_error();
	echo json_encode($data);
    exit();
}


$db   = new ossim_db();
$conn = $db->connect();

Incident_type::delete($conn, $inctype_id);
$db->close($conn);

$data['status']  = 'OK';
$data['data']    = _("Ticket Type successfully deleted");


echo json_encode($data);
exit();

?>