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



$av_menu = @unserialize($_SESSION['av_menu']);

//Check menu object
if (!is_object($av_menu) || empty($av_menu))
{
    header("Location: /session/login.php?action=logout"); 
}


$lmenu = $av_menu->render_lmenu();

if (!empty($lmenu))
{
    ?>
    <div id='c_lmenu'>
        <?php echo $lmenu;?>
    </div>
    <?php
    
    
    $_SESSION['av_menu'] = serialize($av_menu);
}




/* End of file local_menu.php */
/* Location: local_menu.php */
