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



<div id='c_deploy_info'>
    <p>
        <?php
        echo _('Enter the domain admin account and click deploy.');
        ?>
    </p>
</div>



<div class="grid-container">
       
    <div class="row">
        <fieldset>
            <legend><?php echo _('Credentials')?></legend>

            <div class="row">
                <div class='legend'><?php echo _('Values marked with (*) are mandatory')?></div>
            </div>

            <div id='fda_row' class="row">                

                <div class="row">
                    <div class="c_label">
                        <label for='domain'><?php echo _('Domain')?></label>
                    </div>

                    <div class="c_data">
                        <input type='text' name='domain' id='domain' class='vfield'/>
                    </div>
                </div>

                <div class="row">
                    <div class="c_label">
                        <label for='user' class='f_required'><?php echo _('User')?></label>
                    </div>

                    <div class="c_data">
                        <input type='text' name='user' id='user' class='vfield'/>
                    </div>
                </div>

                <div class="row">
                    <div class="c_label">
                        <label for='pass' class='f_required'><?php echo _('Password')?></label>
                    </div>

                    <div class="c_data">
                        <input type='password' name='pass' id='pass' autocomplete='off' class='vfield'/>
                    </div>
                </div>
            </div>

        </fieldset>
    </div>

    <div class="row">
        <div id='c_actions'>
            <input type="button" id='cancel' name='cancel' class='av_b_secondary' value="<?php echo _('Cancel')?>"/>
            <input type="button" id='send' name='send' value="<?php echo _('Deploy')?>"/>
        </div>
    </div>
</div>
