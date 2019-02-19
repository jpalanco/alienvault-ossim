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

?>

<div class="grid-container">

    <div class="row" id='c_os_question'>
        <?php
        echo _("Operating System is unknown. Is this device a Windows System?");
        ?>
    </div>

    <div class="row">
        <div id='c_os_options'>

            <div id='c_os_yes'>
                <input type="radio" name="os_windows" class='vfield' id='os_yes' value='1'/>
                <label for='os_yes'><?php echo _('Yes')?></label>
            </div>

            <div id='c_os_no'>
                <input type="radio" name="os_windows" class='vfield' id='os_no' checked='checked' value='0'/>
                <label for='os_no'><?php echo _('No')?></label>
            </div>
        </div>
    </div>

    <div class="row">
        <div id='c_discover_os'>
            <?php
            $auto_discover_link = "<a href='javascript:parent.GB_close({\"action\": \"discover_os\"});'>auto-discover</a>";

            echo sprintf(_("If you do not know the operating system you can run an asset scan for AlienVault to try to %s the operating system."), $auto_discover_link);
            ?>
        </div>
    </div>

    <div class="row">
        <div id='c_actions'>
            <input type="button" id='cancel' name='cancel' class='av_b_secondary' value="<?php echo _('Cancel')?>"/>
            <input type="button" id='send' name='send' value="<?php echo _('Save')?>"/>
        </div>
    </div>
</div>
