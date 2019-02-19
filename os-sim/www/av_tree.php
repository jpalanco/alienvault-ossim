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

require_once 'av_init.php';

Session::useractive();

// Parameters
$key               = GET('key');
$page              = GET('page');
$filters           = GET('filters');
$aux_filters       = @json_encode($filters);
$max_text_length   = GET('max_text_length');
$extra_options     = GET('extra_options');


// Validate
ossim_valid($key,                OSS_NULLABLE, OSS_ALPHA, OSS_PUNC_EXT,  'illegal:' . _('Key'));
ossim_valid($page,               OSS_NULLABLE, OSS_DIGIT,                'illegal:' . _('Page'));
ossim_valid($max_text_length,    OSS_NULLABLE, OSS_DIGIT,                'illegal:' . _('Max text length'));
ossim_valid($extra_options,      OSS_NULLABLE, OSS_LETTER, '_',          'illegal:' . _('Extra Options'));
ossim_valid($aux_filters,        OSS_ALPHA, OSS_PUNC_EXT, OSS_BRACKET,   'illegal:' . _('Filters'));


if (ossim_error())
{
    echo ossim_error();
    
    exit();
}


//Cache exceptions (Trees will never be cached)
$cache_exp['asec_pg']  = 1;
$cache_exp['contexts'] = 1;


// Getting cached filename
$file = $key;

if ($aux_filters != 'null')
{
    $file .= " $aux_filters";
}

$file .= " $page $extra_options";


$json_tree = NULL;


$db   = new ossim_db(TRUE);
$conn = $db->connect();


if (empty($cache_exp[$key]))
{ 
    $json_tree = Av_tree::get_from_cache($conn, $file);
}


if (empty($json_tree))
{    
    $config  = array(
        'max_text_length' => $max_text_length,
        'extra_options'   => $extra_options
    );
        
    $tree = new Av_tree($key, $page, $filters, $config);
    
    /*
    echo '<pre>';
        print_r($tree);
    echo '</pre>';
    */        
        
    $json_tree = $tree->draw($conn);      
    
    Av_tree::save_in_cache($file, $json_tree);
}

$db->close();

echo $json_tree;

/* End of file av_tree.php */
/* Location: /av_tree.php */ 
