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

Session::logcheck('environment-menu', 'PolicyHosts');

session_write_close();
?>

<div id="tsw_container">

    <div id='tsw_av_info'></div>

    <div class='swf_container'>
        <form method="POST" name="software_form" id="software_form" action="" enctype="multipart/form-data">

            <fieldset>
                <legend><?php echo _('Add New Software')?></legend>

                <input type="hidden" name="action" id="tsw_current_action"/>                
                <input type="hidden" name="property_id" id="tsw_property_id" class="vfield"/>
                <input type="hidden" name="item_id" id="tsw_item_id"/>                    
                
                <div id='tsw_fe_container'>

                    <div class='tsw_e_container'>                    
                        <input type="text" name="sw_name" id="sw_name" placeholder="<?php echo _('Enter Software')?>"/>
                        <input type="hidden" name="sw_cpe" id="sw_cpe"/>
                        <div class='cpe_results' id='sw_name_sr'><div></div></div>                            
                    </div>
                        
                    <div class='tsw_e_container'>
                        <input type="button" id="tsw_save" name="tsw_save" class="small" value="<?php echo _('Save')?>"/>
                        <input type="button" id="tsw_cancel" name="tsw_cancel" class="small" value="<?php echo _('Cancel')?>"/>
                    </div>
                </div>

            </fieldset>
        </form>
    </div>
       
    <div class='swf_container swf_container_td'>   
        <div class="action_buttons">
            <img id="tsw_delete_selection" class="delete_selection img_action disabled" src="/ossim/pixmaps/delete-big.png"/>
        </div>

        <div class='msg_selection' id='tsw_msg_selection' data-bind='tsw_msg-selection'>
            <span></span>
            <a href='javascript:;' class='av_l_main' data-bind='tsw_chk-all-filter'></a>
        </div>
            
        <table class='table_data' id='table_data_software'>
            <thead>
                <tr>
                    <th class="center"><input type='checkbox' data-bind='chk-all-rows'/></th>                    
                    <th><?php echo _('Name')?></th>
                    <th><?php echo _('Source')?></th>
                    <th><?php echo _('Actions')?></th>
                </tr>
            </thead>
            <tbody>            
            </tbody>
        </table>
    </div>

</div>       
