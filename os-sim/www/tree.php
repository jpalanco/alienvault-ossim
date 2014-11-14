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
set_time_limit(0);
ini_set('memory_limit', '2048M');


require_once 'av_init.php';


Session::useractive();


// Parameters
$key           = GET('key');
$page          = intval(GET('page'));
$filter        = GET('filter');
$section       = GET('section');
$length_name   = intval(GET('length_name'));
$extra_options = GET('extra_options');

// Validate
ossim_valid($key,           OSS_NULLABLE, OSS_ALPHA, OSS_PUNC_EXT, 'illegal:' . _("Key"));
ossim_valid($page,          OSS_NULLABLE, OSS_DIGIT,               'illegal:' . _("Page"));
ossim_valid($filter,        OSS_NULLABLE, OSS_TEXT,         	   'illegal:' . _("Filter"));
ossim_valid($section,       OSS_NULLABLE, OSS_LETTER, '_',  	   'illegal:' . _("Section"));
ossim_valid($length_name,   OSS_NULLABLE, OSS_DIGIT,        	   'illegal:' . _("Length name"));
ossim_valid($extra_options, OSS_NULLABLE, OSS_LETTER, '_',  	   'illegal:' . _("Extra Options"));


$db   = new ossim_db(TRUE);
$conn = $db->connect();
			
if ($page == "" || $page <= 0) 
{ 
	$page = 1;
}

//Not cached trees never
$cache_exp['asec_pg']  = 1;
$cache_exp['contexts'] = 1;

$user   = Session::get_session_user();
$c_file = 'tree_'. md5("$key $page $filter $section $extra_options $user");
$json   = NULL;


if (empty($cache_exp[$key]))
{ 
    $json = Cache_file::get_asset_data($c_file);
}

if (empty($json))
{        
    $tree = new Tree($key, $page, $filter, $section, $length_name, $extra_options);
    
    ob_start();
    
    $tree->draw();
    
    $json = ob_get_contents();
    
    ob_end_clean();
    
    Cache_file::save_file($c_file, $json);
}

echo $json;

$db->close();
