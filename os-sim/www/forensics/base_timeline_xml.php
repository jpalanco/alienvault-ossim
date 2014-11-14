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


header("Content-type: application/xml"); 
require_once ('av_init.php');
Session::logcheck("analysis-menu", "EventsForensics");
require_once 'vars_session.php';
require_once 'ossim_db.inc';
require_once 'classes/Util.inc';

$db   = new ossim_db();
$conn = $db->connect();
$xml  = "";

$sql = "SELECT * FROM `datawarehouse`.`report_data` WHERE id_report_data_type = $events_report_type AND USER = ? ";

$user = $_SESSION['_user'];
settype($user, "string");
$params = array(
	$user
);

			
if (!$rs = $conn->Execute($sql, $params)) {
	print 'Error: ' . $conn->ErrorMsg() . '<br/>';
	exit;
}

$format_date = date("M d Y G:i:s")." GMT";
$xml .= "<data>";

if ($rs->EOF) $xml .= "<event start='$format_date' title='"._("No events matching your search criteria have been found")."' link='' icon=''>".Util::htmlentities(_("No events matching your search criteria have been found"))."</event>";

while( !$rs->EOF ) {
	
	$date = explode (" ",  $rs->fields['dataV2']);
	$d = explode("-", $date[0]);
	$t = explode(":", $date[1]);
	
	$timestamp = mktime($t[0], $t[1], $t[2], $d[1], $d[2], $d[0]);
	$format_date = date("M d Y G:i:s", $timestamp)." GMT";
	
		
	$flag = preg_replace("/http\:\/\/(.*?)\//","/",$rs->fields['dataV4']);
			
	$xml .= "<event start='".$format_date."' title='".str_replace("'", "\"", Util::htmlentities($rs->fields['dataV1']))."' link=''";
	$flag = ( $flag =="" ) ? "/ossim/pixmaps/1x1.png" : $flag;
	$xml .= " icon='$flag'>";
	
	
	$inside = "<div class='bubble_desc' style='text-align:center'>".$rs->fields['dataV1']."<br/><br/><div class='txt_desc'>".$rs->fields['dataV3'];
	if ($rs->fields['dataV4']!="") $inside .= " <img src='".$rs->fields['dataV4']."'/>";
	$inside .= " -> ".$rs->fields['dataV5'];
	if ($rs->fields['dataV6']!="") $inside .= " <img src='".$rs->fields['dataV6']."'/>";
	$inside .= "</div><div class='df'>".$format_date."</div>";
	$inside .= "<br/><a href='./base_qry_alert.php?submit=%23".$rs->fields['dataI1']."-".$rs->fields['dataV10']."&amp;sort_order=time_d' target='main'>"._("View event detail")."</a></div>";
	

	$xml .= htmlentities($inside)."</event>"; 
	$rs->MoveNext();
}
			
$xml .= "</data>";
echo $xml;
$db->close($conn);



?>




	
	
	
	
	
		
       
   
   


