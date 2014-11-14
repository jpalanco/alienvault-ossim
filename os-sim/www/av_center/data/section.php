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

//Alienvault Center Path
$avc_path = '/usr/share/ossim/www/av_center';
set_include_path(get_include_path() . PATH_SEPARATOR . $avc_path);

session_start();
session_write_close();

require_once 'classes/av_center.inc';
require_once 'data/breadcrumb.php';


$error_msg  = POST('error_msg');
$profiles   = POST('profiles');
$id_section = POST('section');
$system_id  = POST('system_id');

if ($error_msg != '')
{
    echo "error###<div id='section_error'>".ossim_error(Util::htmlentities($error_msg))."</div>";
}
else
{
    ossim_valid($system_id,   OSS_DIGIT, OSS_LETTER, '-',        'illegal:' . _('System ID'));
    ossim_valid($id_section,  OSS_ALPHA, OSS_SCORE, OSS_BRACKET, 'illegal:' . _('Section'));              
    
    if (ossim_error() || !array_key_exists($id_section, $sections))
	{ 
        if (ossim_error())
        {
			echo "error###<div id='section_error'>".ossim_error()."</div>";
		}
		else
		{
			echo "error###<div id='section_error'>".ossim_error(_('Sorry, this section does not exist'))."</div>";
		}
	}
    elseif ($profiles != '')
    {             
        include($sections[$id_section]['path']);
    }
    else
    {
        echo "error###<div id='section_error'>".ossim_error(_('Sorry, operation was not completed due to an unknown error'))."</div>";        
    }
}
?>