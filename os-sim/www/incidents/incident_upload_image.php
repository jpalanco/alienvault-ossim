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


//Check permissions
if ( !Session::menu_perms("analysis-menu", "IncidentsOpen") )
{
	Session::unallowed_section();
	exit();
}

$upload_dir = '/usr/share/ossim/www/uploads/';
$prefix     = POST('prefix');
ossim_valid($prefix , OSS_DIGIT, 'illegal:' . _("Prefix"));

if (ossim_error()) {
	die(ossim_error());
}

if ($_FILES['imageName']['tmp_name'] != "") {
    
    if (!preg_match("/^[a-zA-Z0-9\-\_\s]+\.(gif|jpg|png|jpeg)$/i", $_FILES['imageName']['name'] ) || !preg_match("/image\//", $_FILES["imageName"]["type"]) ) { 
        echo '<div class="error_msg_container"><h3>ERROR: Your image was not one of the accepted formats (gif, jpg, png), please try again.</h3></div>'; 
        unlink($_FILES['imageName']['tmp_name']); 
        exit(); 
    } 
    else
    {
        $figure_name = $prefix."_".preg_replace("/[\s\t\/\'\"]/","_",$_FILES['imageName']['name']);
        $image_url = $upload_dir.$figure_name;
        /*if (file_exists($image_url)) { 
            echo '<div class="error_msg_container"><h3>ERROR: Your image already exists, please change image name an try again.</h3></div>';
            exit();
        }*/
        $place_file = move_uploaded_file( $_FILES['imageName']['tmp_name'], $image_url);
    }
    echo '<div id="image">/ossim/uploads/'.Util::htmlentities($figure_name).'</div>';
    //echo '<div id="feedback_container" style="width:200px; border:1px solid #999;padding:5px;margin:auto;"><img id="image_to_insert" width="200" src="/ossim/uploads/'.$figure_name.'"></div>';
}
else
{ 
    echo '<div id="feedback_container" style="border:1px solid #696969;padding:5px;text-align:center;color:#fba7ae">You can not upload an umplty file!</div>';
} 
?>
