<?php
/**
 * general.php
 * 
 * File general.php is used to:
 * - Be included by index.php as module of asset details
 * - Show the general info tab for Asset Details page
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

// Exit if the script is called by URL. It has to be included by index
if ($_SERVER['SCRIPT_NAME'] != "/ossim/asset_details/index.php")
{
	exit();
}

?>
<div id='detail_mmenu_container'>
	<div id='c_module_menu'>
        <div class='mmenu' id='mmenu_components'>
            <div class='c_l_mmenu' id='c_l_mmenu_components'>
                <ul class='l_mmenu' id='l_mmenu_components' style="padding-left:5px">
                
                <li id='li_software'>
                        <a id='ll_opt_software' class='active_general active' href='' onclick="load_section('general', 'software');return false"><?php echo _('Services') ?></a>
                	<div id='arrow_down_software' class='c_arrow_down c_arrow_down_general'><div class='arrow_down'></div></div>
                </li>
                
                <li class='li_sep'>|</li>
                
                <li id='li_users'>
                	<a id='ll_opt_users' class='active_general default' href='' onclick="load_section('general', 'users');return false"><?php echo _('Users') ?></a>
                	<div id='arrow_down_users' style="display:none" class='c_arrow_down c_arrow_down_general'><div class='arrow_down'></div></div>
                </li>
                
                <li class='li_sep'>|</li>
                
                <li id='li_properties'>
                	<a id='ll_opt_properties' class='active_general default' href='' onclick="load_section('general', 'properties');return false"><?php echo _('Properties') ?></a>
                	<div id='arrow_down_properties' style="display:none" class='c_arrow_down c_arrow_down_general'><div class='arrow_down'></div></div>
                </li>

                
                <?php 
                if ($asset_type == 'host') 
                {
                    $hips = $asset_object->get_ips()->get_ips();
                    if (!array_key_exists($system_ip,$hips))
                    {
                        $onclick = "load_section('general', 'plugins')";
                    }
                    else
                    {
                        $onclick = "show_plugin_message()";
                    }
                    ?>
                    <li class='li_sep'>|</li> 
                    
                    <li id='li_plugins'>
                        <a id='ll_opt_plugins' class='active_general default' href='' onclick="<?php echo $onclick ?>;return false"><?php echo _('Plugins') ?></a>
                        <div id='arrow_down_plugins' style="display:none" class='c_arrow_down c_arrow_down_general'><div class='arrow_down'></div></div>
                    </li>
                    <?php
                }
                ?>
                </ul>
            </div>
        </div>
	
    	
    	<?php 
    	if ($asset_type == 'host') 
    	{ 
    		?>    		
    		<!-- Popup to availability tree -->
    		
    		<div id="edit_avail_button" class='general_edit'>
    			<input type="button" class="greybox_availability av_b_secondary" value="<?php echo _('Edit Availability Monitoring')?>">
    		</div>
    		
	
    		<!-- Popup to properties tree -->    	
    		
    		<div id="edit_properties_button" class='general_edit'>
    			<input type="button" class="greybox_inventory av_b_secondary" value="<?php echo _('Edit Properties')?>">
    		</div>
    		
    		
    		<!-- Popup to properties tree -->    	
    		
    		<div id="edit_plugins_button" class='general_edit'>
    			<input type="button" class="greybox_plugins av_b_secondary" value="<?php echo _('Edit Plugins')?>">
    		</div>
    		
    		<?php 
    	} 
    	?> 
		
	</div>

</div>


<!-- SOFTWARE -->
<div id="div_software" class="div_subcontent_general" style="display:none">
	<div class='loading_panel' id='software_loading'>
		<div style='padding: 5px; overflow: hidden;'>
			<?php echo _('Loading software') ?>  <img src='/ossim/pixmaps/loading3.gif' align='absmiddle'/>
		</div>
	</div>
	<div style='display:none' id='software_list'>
    	<table class='table_data table_data_software'>
    		<thead>
    			<tr>
    				<?php 
    				if ($asset_type == 'net' || $asset_type == 'group') 
    				{ 
    					?>
    					<th><?php echo _('Host')?></th>
    					<?php 
    				} 
    				else 
    				{                					
    					?>
    					<th><?php echo _('IP Address')?></th>
    					<?php 
    				} 
    				?>
    				<th><?php echo _('Port')?></th>
    				<th><?php echo _('Name')?></th>
    				<th><?php echo _('Vulnerable')?></th>
    				<th><?php echo _('Monitoring')?></th>
    				<th><?php echo _('Service Status')?></th>
    			</tr>
    		</thead>
    		<tbody>
    		</tbody>
    	</table>
	</div>
</div>
		
<!-- USERS -->
<div id="div_users" class="div_subcontent_general" style="display:none">
	<div class='loading_panel' id='users_loading'>
		<div style='padding: 5px; overflow: hidden;'>
			<?php echo _('Loading users') ?>  <img src='/ossim/pixmaps/loading3.gif' align='absmiddle'/>
		</div>
	</div>
	<div style='display:none' id='users_list'>
    	<table class='table_data table_data_users'>
    		<thead>
    			<tr>
    			    <?php
    				if ($asset_type == "net" || $asset_type == "group")
    				{
                        ?>
    					<th><?php echo _('Asset') ?></th>
    					<?php
                    }
                    ?>
    				<th><?php echo _('Date') ?></th>
    				<th><?php echo _('User') ?></th>
    				<th><?php echo _('Domain') ?></th>
    				</tr>
    		</thead>
    		<tbody>
    		</tbody>
    	</table>
	</div>
</div>

<!-- PROPERTIES -->
<div id="div_properties" class="div_subcontent_general" style="display:none">
	<div class='loading_panel' id='properties_loading'>
		<div style='padding: 5px; overflow: hidden;'>
			<?php echo _('Loading properties') ?>  <img src='/ossim/pixmaps/loading3.gif' align='absmiddle'/>
		</div>
	</div>
	<div style='display:none' id='properties_list'>
    	<table class='table_data table_data_properties'>
    		<thead>
    			<tr>
    				<?php 
    				if ($asset_type == "net" || $asset_type == "group") 
    				{ 
    					?>
    					<th><?php echo _("Host")?></th>
    					<?php 
    				} 
    				else 
    				{ 
        				?>
        				<th><?php echo _("IP Address") ?></th>
        				<?php 
            		} 
            		?>
    				<th><?php echo _('Property')?></th>
    				<th><?php echo _('Value')?></th>
    				<th><?php echo _('Date')?></th>
    				<th><?php echo _('Source')?></th>
    			</tr>
    		</thead>
    		<tbody>
    		</tbody>
    	</table>
	</div>
</div>


<!-- PROPERTIES -->

<?php 
if ($asset_type == 'host') 
{         
    ?>
    <div id="div_plugins" class="div_subcontent_general" style="display:none">
    	<div class='loading_panel' id='plugins_loading'>
    		<div style='padding: 5px; overflow: hidden;'>
    			<?php echo _('Loading plugins') ?>  <img src='/ossim/pixmaps/loading3.gif' align='absmiddle'/>
    		</div>
    	</div>
    	<div style='display:none' id='plugins_list'>
        	<table class='table_data table_data_plugins'>
        		<thead>
                    <tr>
                        <th class='th_vendor'><?php echo _('Vendor')?></th>
                        <th class='th_model'><?php echo _('Model')?></th>
                        <th class='th_version'><?php echo _('Version')?></th>
                        <th class='th_plugin'><?php echo _('Plugin')?></th>
                        <th class='th_sensor'><?php echo _('Sensor')?></th>
                        <th class='th_rd'><?php echo _('Receiving data')?></th>
                    </tr>
        		</thead>
        		<tbody>
        		</tbody>
        	</table>
    	</div>
    </div>
    <?php
}

/* End of file general.php */
/* Location: ./asset_details/modules/general.php */