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


require_once ('av_init.php');
Session::logcheck("analysis-menu", "EventsForensics");


$id = GET('id');
ossim_valid($id, OSS_DIGIT, 'illegal:' . _("id"));

if ( ossim_error() ) {
   echo ossim_error();
   exit();
}

require ("base_conf.php");
include_once ($BASE_path."includes/base_db.inc.php");
include_once ("$BASE_path/includes/base_state_query.inc.php");
include_once ("$BASE_path/includes/base_state_common.inc.php");

/* Connect to the Alert database */
$db = NewBASEDBConnection($DBlib_path, $DBtype);
$db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password, 1);

$qs     = new QueryState();
$sql    = "SELECT count(sig.plugin_sid) FROM reference r,reference_system s,sig_reference sig WHERE r.ref_system_id=s.ref_system_id AND r.ref_id=sig.ref_id AND r.ref_system_id=$id";
$result = $qs->ExecuteOutputQueryNoCanned($sql, $db);

if ( $myrow = $result->baseFetchRow() ) {
	echo $myrow[0];
} 
else{
	echo "0";
}
