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

session_write_close();
?>

<div id='c_tui'>

    <div class='pop_up_section'>
        <?php echo _('At AlienVault we are continually striving to improve USM. By understanding how our users are interacting with the USM platform through anonymous usage data, 
            we will be able to improve the product and your user experience.');?>
    </div>

    <div class='pop_up_section c_chk_tui'>
        <input type="checkbox" checked="checked" data-bind="chk_tui" name="track_usage_information" id="track_usage_information" value="1"/>
        <?php echo _('Share anonymous usage statistics and system information with AlienVault to help us make USM better.')?>
        <a href="/ossim/av_routing.php?action_type=EXT_TRACK_USAGE_INFORMATION" target="_blank"><?php echo _('Learn More')?></a>
    </div>

    <div class="pop_up_actions">
        <input type="button" data-bind="cancel" class='av_b_secondary' id="cancel" name="cancel" value='<?php echo _('Cancel')?>'/>
        <input type="button" data-bind="send_tui" id="send" name="send" value='<?php echo _('Save')?>'/>
    </div>
</div>