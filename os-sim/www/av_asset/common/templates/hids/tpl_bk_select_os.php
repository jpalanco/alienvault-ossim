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

$unable_to_deploy = $total_unknown_os + $total_not_windows;

?>
<div class="grid-container">

    <div class="row" id='c_os_question'>
        <div>
            <?php echo sprintf(_('Warning! Unable to deploy agents to %s of the selected assets because they do not have a Windows operating
            system. If you would like to deploy agents to these %s assets, please update the operating system field.'), $unable_to_deploy, $unable_to_deploy);
            ?>
        </div>

        <br/><br/>

        <div>
            <?php echo _("To view these assets and update the operating system in bulk, <a id='show_assets' href='javascript:;'>click here</a>. To skip the assets and deploy agents to the remaining assets, please click continue.") ?>
        </div>
    </div>

    <div class="row">
        <div id='c_actions'>
            <input type="button" id='cancel' name='cancel' class='av_b_secondary small' value="<?php echo _('Cancel')?>"/>
            <?php
            if($unable_to_deploy < $total_selected)
            {
                ?>
                <input type="button" id='continue' name='continue' disabled='disabled' class='small' value="<?php echo _('Continue')?>"/>
                <?php
            }
            ?>
        </div>
    </div>
</div>
