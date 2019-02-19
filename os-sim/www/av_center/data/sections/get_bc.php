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


//Config File
require_once (dirname(__FILE__) . '/../../config.inc');
require_once 'data/breadcrumb.php';

session_write_close();

$system_id  = POST('system_id');
$id_section = POST("section");
$host       = POST("host");


ossim_valid($id_section,  OSS_ALPHA, OSS_SCORE, OSS_BRACKET,                     'illegal:' . _('Section'));
ossim_valid($host,        OSS_ALPHA, OSS_SCORE, OSS_BRACKET, OSS_DOT, OSS_SPACE, 'illegal:' . _('Host'));
ossim_valid($system_id,   OSS_DIGIT, OSS_LETTER, '-',                            'illegal:' . _('System ID'));

if (ossim_error()) 
{
    $data['status']  = 'error';
	$data['data']    = ossim_get_error_clean();
	
	echo json_encode($data);
    exit();
}

$bc_sections = explode("###", $sections[$id_section]['bc']);

$size = count($bc_sections);
$cont = 0;

//Go back section
if ($pro)
{
	$back = ($size >= 2) ? TRUE : FALSE;
}
else
{
	$back = ($size >= 3) ? TRUE : FALSE;
}


$data['status']   = 'success';
$data['section']  =  $id_section;
$data['data']     = "<ul class='xbreadcrumbs' id='breadcrumbs'>";


foreach ($bc_sections as $section)
{
    $cont++;
    
    $data['data'] .= ($size == $cont) ? "<li class='current'>" : "<li>";
	$go_back       = ($back == TRUE && ($size-1 == $cont)) ? 'go_back' : '';
	
	switch($section){
		case 'alienvault_center':
			$class = (!empty($go_back)) ? "class='home go_back'" : "class='home'";
			$data['data'] .= "<a href='#' id='avc_".$section."' $class onclick=\"document.location.href='".$sections[$section]['path']."'\">".$sections[$section]['name']."</a>";
		break;
		
		case 'home':
			$class = (!empty($go_back)) ? "class='go_back'" : "";
			$data['data'] .= "<a href='#' id='avc_".$section."' $class onclick=\"section.load_section('".$section."')\">".$host."</a>";
		break;
		
		default:
			$class = (!empty($go_back)) ? "class='go_back'" : "";
			$data['data'] .= "<a href='#' id='avc_".$section."' $class onclick=\"section.load_section('".$section."')\">".$sections[$section]['name']."</a>";
	}
		
    $data['data'] .= "</li>";
}

$data['data'] .= "</ul>";

echo json_encode($data);
?>
