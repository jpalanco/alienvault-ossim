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

//Ossec Path
$ossec_path = '/usr/share/ossim/www/ossec';
set_include_path(get_include_path() . PATH_SEPARATOR . $ossec_path);

define('OSSEC_PATH', $ossec_path);

//Ossim Path
$ossim_path = '/usr/share/ossim/www';
set_include_path(get_include_path() . PATH_SEPARATOR . $ossim_path);


/*******************************************
************* General Constants ************    
********************************************/

define('OSSIM_PATH', $ossim_path);

//OSSIM Image Path
define('OSSIM_IMG_PATH', '/ossim/pixmaps');

//OSSEC Image Path
define('OSSEC_IMG_PATH', '/ossim/ossec/images');

//Dynatree Image Path
define('DYNATREE_IMG_PATH', '../../../ossim/pixmaps');
?>