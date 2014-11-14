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

Session::logcheck("dashboard-menu", "ControlPanelExecutive");
	
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en">
	<head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">

        <title><?php echo _("Imported Widget")?></title>
        
        <?php
        
        //CSS Files
        $_files = array(
            array('src' => 'av_common.css?only_common=1',       'def_path' => TRUE)
        );
        
        Util::print_include_files($_files, 'css');
        
        ?>


    
	</head>
  
	<body style="overflow:hidden" scroll="no">
		<table class='transparent' style='width:100%; height:155px;'>
			<tr>
				<td align='center' valign='middle' >
					<br>
					<img src="/ossim/pixmaps/top/bubba.png" border="0" align='absmiddle' style='padding:9px 5px 0 0;'><span><b> <?php echo _('It was Impossible to import this widget') ?></b></span>
				</td>
			</tr>
		</table>

	</body>
</html>