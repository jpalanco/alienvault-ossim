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

Session::useractive();

$default_section = _('Alienvault Wiki');
$default_url     = 'https://www.alienvault.com/help/product/';

$m_opt  = POST('m_opt');
$sm_opt = POST('sm_opt');
$h_opt  = POST('h_opt');
$l_opt  = POST('l_opt');

ossim_valid($m_opt,  OSS_LETTER, OSS_DIGIT, OSS_SCORE,               'illegal:' . _('Menu option'));
ossim_valid($sm_opt, OSS_LETTER, OSS_DIGIT, OSS_SCORE,               'illegal:' . _('Submenu option'));
ossim_valid($h_opt,  OSS_LETTER, OSS_DIGIT, OSS_SCORE,               'illegal:' . _('Hmenu option'));
ossim_valid($l_opt,  OSS_LETTER, OSS_DIGIT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _('Lmenu option'));

//Chenck menu options
if (ossim_error())
{
    $data['section'] = $default_section;
    $data['url']     = $default_url;
    
    echo json_encode($data);
    exit();
}

$av_menu = @unserialize($_SESSION['av_menu']);


//Check menu object
if (!is_object($av_menu) || empty($av_menu))
{
    $db   = new ossim_db();
    $conn = $db->connect();
    
    $av_menu = new Menu($conn);
    
    $db->close();
}


$menu = $av_menu->get_menus();

$data['url'] = $default_url.$m_opt.'/'.$sm_opt.'/'.$h_opt;

if (!empty($l_opt))
{    
    $data['url'] .= '/'.$l_opt;
}


echo json_encode($data);
exit();


/* End of file get_help.php */
/* Location: home/get_help.php */