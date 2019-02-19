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

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

$id = GET('id');

ossim_valid($id, OSS_DIGIT, 'illegal:' . _("ID"));

if (ossim_error()) {
    die(ossim_error());
}

require ("base_conf.php");
include_once ($BASE_path."includes/base_db.inc.php");
include_once ("$BASE_path/includes/base_state_query.inc.php");
include_once ("$BASE_path/includes/base_state_common.inc.php");

/* Connect to the Alert database */
$db = NewBASEDBConnection($DBlib_path, $DBtype);
$db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password, 1);

$qs     = new QueryState();
$sql    = "SELECT icon FROM reference_system WHERE ref_system_id=$id";
$result = $qs->ExecuteOutputQuery($sql, $db);

if ( $myrow = $result->baseFetchRow() ) 
{
	//echo $myrow[0];
	header("Content-type: image/png");
	
	if ($myrow[0] != "") {
		$image = imagecreatefromstring($myrow[0]);
	}
	else{
		$image = imagecreatefrompng("../forensics/images/server.png");
	}
	
	if ( imageistruecolor($image) ) 
	{
		imagealphablending($image, false);
		imagesavealpha($image, true);
	}
	
	imagepng($image);
	imagedestroy($image);
}

?>
