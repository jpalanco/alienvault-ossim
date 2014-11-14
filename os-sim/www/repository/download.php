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


Session::logcheck("configuration-menu", "Osvdb");


// DB connect
$db   = new ossim_db();
$conn = $db->connect();


$conf        = $GLOBALS["CONF"];
$uploads_dir = $conf->get_conf("repository_upload_dir");

$fileid      = GET('id');
$aux         = explode("_",$fileid);
$id_document = $aux[0];
$id          = $aux[1];

ossim_valid($fileid,        OSS_DIGIT, '_',     'illegal:' . _("file_id"));
ossim_valid($id_document,   OSS_DIGIT,          'illegal:' . _("id_document"));
ossim_valid($id,            OSS_DIGIT,          'illegal:' . _("id"));

if (ossim_error()) 
{
    die(ossim_error());
}

list($file,$name) = Repository::get_attachment($conn,$id_document,$id);
$file             = $uploads_dir."/$id_document/".$file;

if ($file != "" && $name != "" && file_exists($file)) 
{
	header('Content-Description: File Transfer');
	header('Content-Type: application/force-download');
	header('Content-Length: ' . filesize($file));
	header('Content-Disposition: attachment; filename=' . str_replace(" ", "_", $name));
	readfile($file);
} 
else 
{
	echo "File not found";
}
