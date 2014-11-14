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


/**
* Function list:
* - isSerialized()
* - load_layout()
* - load_layout_from_file()
* - print_layout()
*/


require_once 'av_init.php';

Session::useractive();

function isSerialized($str) {
	if($str == serialize(false) || @unserialize($str) !== false){
		$params = unserialize(stripslashes($str));	
		if(is_array($params)){
			foreach($params as $p){
				$aux = explode(';', $p);
				if($aux[5] == 0){
					return false;
				}
			}
		}
		return true;
	} else{
		return false;
	}

}


// read serialize layout database config
function load_layout($name_layout, $category = 'policy')
{
    return array();

    /*
    $db = new ossim_db();
    $conn = $db->connect();
    $config = new User_config($conn);
    $login = Session::get_session_user();
    $data = $config->get($login, $name_layout, 'php', $category);
    return ($data == null) ? array() : $data;
    */
}


// read serialize layout config from file
function load_layout_from_file($user, $name_layout)
{
    $file = "/tmp/" . $user . "_" . $name_layout;
    if (!file_exists($file)) return array();
    $f = fopen($file, "r");
    $data = trim(fgets($f));
    fclose($f);
    return unserialize($data);
}


// print with flexigrid format column layout
function print_layout($layout, $default, $sortname, $sortorder, $height = 300) {
    // set default read values if exist
    if (is_array($layout)) {
        foreach($layout as $data) {
            // data string with serialize format: width;sort;visible;index;name,height
            $fds = explode(";", $data);
            if (isset($default[$fds[4]])) {
                $default[$fds[4]][1] = str_replace("px", "", $fds[0]); // set width
                $default[$fds[4]][4] = ($fds[2] == "1") ? false : true; // set hide
                //$default[$fds[4]][5] = ($fds[5]!="" && $fds[5]>0) ? $fds[5] : 1; // set colspan
                if ($fds[1] != "none") {
                    $sortname = $fds[4]; // set sortname
                    $sortorder = $fds[1]; // set sortorder
                    
                }
            }
            if (is_numeric($fds[5])) $height = (int)$fds[5];
        }
    }
    $str = "";
    // print format: {display: 'Thr_C', name : 'threshold_c', width : 40, sortable : true, align: 'center', hide: true, colspan:2}
    foreach($default as $column => $data) {
        $str.= "{display: '" . str_replace("'","\'",$data[0]) . "', name : '" . $column . "', width : " . $data[1] . ", sortable : " . $data[2] . ", align: '" . $data[3] . "'";
        if ($data[4] == true) $str.= ", hide: true";
        //if ($data[5]>1) $str .= ", colspan: '".$data[5]."'";
        $str.= "},";
    }
    return array(
        preg_replace("/,$/", "", $str) ,
        $sortname,
        $sortorder,
        $height
    );
}
//
// SAVE
//
$user        = Session::get_session_user();
$name_layout = POST('name');
$layout      = POST('layout');
$category    = POST('category');

if ($category == '') 
{
    $category = 'policy';
}

ossim_valid($name_layout, OSS_ALPHA, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("name_layout"));
ossim_valid($layout, OSS_TEXT, OSS_PUNC_EXT, OSS_NULLABLE,    'illegal:' . _("layout"));
ossim_valid($category, OSS_ALPHA, OSS_NULLABLE,               'illegal:' . _("category"));

if (ossim_error())
{
    die(ossim_error());
}

// $text_layout = unserialize(stripslashes($layout));
// echo "<pre>";
// print_r(($text_layout));
// echo "</pre>";

if ($user != "" && $name_layout != "" && isSerialized($layout)) 
{
    if (POST('type') == 'file') 
    {
        $file = "/tmp/" . $user . "_" . $name_layout;
        $f    = fopen($file, "w");
        
        fputs($f, trim($layout));
        fclose($f);
    } 
    else 
    {
        #$db = new ossim_db();
        #$conn = $db->connect();
        #$config = new User_config($conn);
        #$config->set($user, $name_layout, $layout, 'simple', $category);
    }
    
    echo _("Layout saved!");
}
?>
