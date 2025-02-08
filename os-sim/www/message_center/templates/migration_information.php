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
        <?php echo _('Business technology isn’t just on-premises anymore, organizations of all sizes have moved critical infrastructure and applications to the cloud.
            Today’s threat landscape requires businesses to monitor and protect all environments. 
            Migrating to USM Anywhere&trade; allows you to detect and respond to threats in Any environment, and offers native integration with cloud services like Office 365 and G Suite.
            <br><br>Click ');?>
        <a href="https://cybersecurity.att.com/products/alienapps?utm_medium=InProduct&utm_source=USM&utm_campaign=AlienApps&utm_content=200629" target="_blank"><?php echo _('here to get more information');?></a>
        <?php echo _('about USM Anywhere Integrations!');?>
    </div>

    <div class="pop_up_actions">
        <a href="https://cybersecurity.att.com/products/usm-anywhere?utm_medium=InProduct&utm_source=USM&utm_campaign=USMAnywhere&utm_content=200629" target="_blank">
            <input type="button" data-bind="send_tui" id="send" name="send" value='<?php echo _('More information')?>' />
        </a>
        <input type="button" data-bind="cancel_login_migration_pop_up" class='av_b_secondary' id="cancel" name="cancel" value='<?php echo _('Don´t ask again')?>'/>
    </div>

</div>