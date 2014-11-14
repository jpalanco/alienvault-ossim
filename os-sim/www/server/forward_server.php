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

Session::logcheck("configuration-menu", "PolicyServers");


$db = new ossim_db();
$conn = $db->connect();

$source = POST("source");
$dests  = POST("dests");

ossim_valid($source,	OSS_HEX,	'illegal:' . _("Server Source ID"));

if (ossim_error()) 
{
    die(ossim_error());
}

if( !is_array($dests ) || empty($dests) ){
	die('Wrong Server Destiny IDs parameter');	
}

$text_sql = array();

foreach($dests as $dest)
{
	$dest = explode('@', $dest);
	ossim_valid($dest[0],	OSS_HEX,	'illegal:' . _("Server ID"));
	$text_sql[] = "UNHEX('".$dest[0]."')";
}

if (ossim_error()) {
    die(ossim_error());
}


$text_sql = implode(',', $text_sql);
$sql      = ossim_query("SELECT count(*) as total FROM policy_forward_reference WHERE child_id = UNHEX('$source') and parent_id in($text_sql)");
$return   = array();

if (!$rs = & $conn->Execute($sql)) {
	$return['error'] = true ;

} 
else 
{
	if($rs->fields['total'] == 0)
	{
		$return['error'] = false ;
		$return['count'] = 0;
		
	}
	else{
		$return['error'] = false ;
		$return['count'] = 1;
	}
}

echo json_encode($return);

$db->close();
?>
