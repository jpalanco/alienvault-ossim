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


function tmp_insert($db, $table) 
{
	$user = $_SESSION['_user'];
	
	$db->query("CREATE TABLE IF NOT EXISTS datawarehouse.tmp_user (user VARCHAR( 64 ) NOT NULL,section VARCHAR(32) NOT NULL,req varchar(10) NOT NULL, sid INT( 11 ) NOT NULL, PRIMARY KEY ( user,section,sid ))");
	
	$section = str_replace('PCI.', '', $table);
	
	$db->query("DELETE FROM datawarehouse.tmp_user WHERE user='$user' and section='$section'");
	$resSIDs = $db->query("SELECT x1, x2, x3, SIDSS_Ref from $table");
	
	foreach ($resSIDs as $res) 
	{
		$req  = $res['x1'].'.'.$res['x2'].'.'.$res['x3'];
		$sids = explode(',', $res['SIDSS_Ref']);
		
		if ($sids[0] != '') 
		{
			foreach ($sids as $sid)
			{
				$db->query("INSERT IGNORE INTO datawarehouse.tmp_user values ('$user','$section','$req',$sid)");
			}
		}
	}
}

?>