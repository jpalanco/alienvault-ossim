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

$db   = new ossim_db();
$conn = $db->connect();


$id        = POST('ticket');

ossim_valid($id, OSS_ALPHA, 'illegal:' . _("Incident Id"));

if ( ossim_error() )
{
	$response['status'] = 'error';
	$response['msg']    = ossim_get_error();

	ossim_clean_error();
	
	echo json_encode($response);
	exit;
}
	
$ticket_id = $conn->GetOne('SELECT max(id)+1 FROM incident_ticket');

if(isset($_FILES['inline_upload_file']) && !empty($_FILES['inline_upload_file']['name']))
{
	$filename = $_FILES['inline_upload_file']['tmp_name'];
	$image_val = getimagesize($filename);
	if(!empty($image_val) && in_array($image_val['mime'], array("image/jpeg", "image/gif", "image/png")))
	{
		if(filesize($filename) < 512000)
		{
			$name        = "Incident-$id-$ticket_id-". time() . str_replace("image/", ".", $image_val['mime']);
			$validate_image_content = function() use ($filename,$name,$image_val) {
        	                $upload_path = "../uploads/$name";
	                        switch($image_val['mime']) {
	                                case "image/jpeg"	: return imagejpeg(imagecreatefromjpeg($filename),$upload_path);
        	                        case "image/gif"	: return imagegif(imagecreatefromgif($filename),$upload_path);
                	                case "image/png"	: return imagepng(imagecreatefrompng($filename),$upload_path);
        	                }
				return false;
			};
			if ($validate_image_content())
			{
                $response['status'] = 'success';
                $response['src'] = "/ossim/uploads/$name";
			}
			else
			{
                $response['status'] = 'error';
                $response['msg'] = _("Invalid image");
			}

		} 
		else 
		{
			$response['status'] = 'error';
			$response['msg']    =  _("Invalid Image Size. Maximum size allowed is 500 Kb");
		}
	} 
	else 
	{
		$response['status'] = 'error';
		$response['msg']    =  _("Invalid Image.");
	}
}

echo json_encode($response);

$db->close($conn);
