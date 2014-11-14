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


session_cache_limiter('private');

$pathtographs = dirname($_SERVER['REQUEST_URI']);
$proto        = "http";

if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") $proto = "https";
$datapath = "$proto://" . Util::get_default_admin_ip() . "$pathtographs/graphs";

function clean_tmp_files() 
{
    if (isset($GLOBALS['tmp_files'])) {
        foreach($GLOBALS['tmp_files'] as $file) {
            if (file_exists($file)) unlink($file);
        }
    }
}

register_shutdown_function('clean_tmp_files');

function create_image($url, $args = array()) 
{
    foreach($args as $k => $v) {
        $_GET[$k] = $v;
    }
    
    ob_start();
    include $url;
    $cont = ob_get_clean();
    $tmp_name = tempnam('/tmp', 'ossim_');
    $GLOBALS['tmp_files'][] = $tmp_name;
    $fd = fopen($tmp_name, 'w');
    fputs($fd, $cont);
    fclose($fd);
    return $tmp_name;
}

function cut_string($text, $limite=50)
{
    $comp = strlen($text);
    if($comp > $limite)
        return substr($text,0,$limite-5).'[...]';
    else
	    return $text;
}
?>