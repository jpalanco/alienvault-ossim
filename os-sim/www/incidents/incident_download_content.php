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

Session::logcheck("analysis-menu", "IncidentsIncidents");


$id          = GET('id');
$incident_id = GET('incident_id');

ossim_valid($id,            OSS_DIGIT,  'illegal:' . _("Id"));
ossim_valid($incident_id,   OSS_DIGIT,  'illegal:' . _("Incident Id"));

if (ossim_error()) 
{
    die(ossim_error());
}

/* database connect */
$db   = new ossim_db();
$conn = $db->connect();

list($output_name,$content) = Incident::get_custom_content($conn,$id,$incident_id);

$conn->disconnect();

header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: private",false);
header("Content-Type: application/octet-stream");
header("Content-Transfer-Encoding:­ binary");
header("Content-Length: " . strlen($content) );
$output_name = "CUS$incident_id-$output_name";
header("Content-disposition:  attachment; filename=$output_name");
echo $content;
?>
