<?php
/**
 * activity.php
 * 
 * File activity.php is used to:
 * - Be included by index.php as module of asset details
 * - Show Alarms, Events and Netflows sections into asset details page
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
 * @package    ossim-framework\Assets
 * @autor      AlienVault INC
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt
 * @copyright  2003-2006 ossim.net
 * @copyright  2007-2013 AlienVault
 * @link       https://www.alienvault.com/
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
		<!-- SUBMENU TABS -->
			<div class='mmenu' id='mmenu_components'>
			<div class='c_l_mmenu' id='c_l_mmenu_components'>
				<ul class='l_mmenu' id='l_mmenu_components' style="padding-left:5px">
				<li id='li_alarms'>
					<a id='ll_opt_alarms' class='active_activity active' href='' onclick="load_section('activity', 'alarms');return false"><?php echo _("Alarms") ?></a>
					<div id='arrow_down_alarms' class='c_arrow_down c_arrow_down_activity'><div class='arrow_down'></div></div>
				</li>
				<li class='li_sep'>|</li>
				<li id='li_events'>
					<a id='ll_opt_events' class='active_activity default' href='' onclick="load_section('activity', 'events');return false"><?php echo _("Events") ?></a>
					<div id='arrow_down_events' style="display:none" class='c_arrow_down c_arrow_down_activity'><div class='arrow_down'></div></div>
				</li>
				<li class='li_sep'>|</li>
				<li id='li_netflows'>
					<a id='ll_opt_netflows' class='active_activity default' href='' onclick="load_section('activity', 'netflows');return false"><?php echo _("Netflow") ?></a>
					<div id='arrow_down_netflows' style="display:none" class='c_arrow_down c_arrow_down_activity'><div class='arrow_down'></div></div>
				</li>
				</ul>
			</div>
		</div>
		
		<?php 
		if (count($events) > 0 && FALSE) 
		{ 
		    // Temporary disabled ?>
			<div style="position:relative;display:none" id="events_filters">
			<div style="position:absolute;right:5px;text-align:left">
				<b><?php echo _("Data source")?></b><br/>
				<?php
				$_datasources = array();
				foreach ($events as $event)
				{
					$_datasources[$event['datasource']]++;
				}
				?>
				<select name="data_source" onchange="filter_eventlist(this.value)">
					<option value="">-</option>
					<?php 
					foreach ($_datasources as $ds => $num) 
					{ 
    					?>
    					<option value="<?php echo $ds ?>"><?php echo $ds ?></option>
    					<?php 
    					} 
    				?>
				</select>
			</div>
			</div>
			<?php 
		 } 
		 ?>
	</div>

</div>

<!-- ALARMS -->
<div id="div_alarms" class="div_subcontent_activity">
<div class='loading_panel' id='alarms_loading'>
	<div style='padding: 5px; overflow: hidden;'>
		<?php echo _("Loading asset alarms") ?>  <img src='/ossim/pixmaps/loading3.gif' align='absmiddle'/>
	</div>
</div>
<div style='display:none' id='alarms_list'>
<table class='table_data table_data_alarms'>
	<thead>
		<tr>
			<th><?php echo _('Date')?></th>
			<th><?php echo _('Status')?></th>
			<th style="text-align:left"><?php echo _('Intent')." & "._('Strategy')?></th>
			<th style="text-align:left"><?php echo _('Method')?></th>
			<th><?php echo _("Risk")?></th>
			<th style="text-align:left"><?php echo _('Source')?></th>
			<th style="text-align:left"><?php echo _('Destination')?></th>
		</tr>
	</thead>
	<tbody>
	</tbody>
</table>
</div>
</div>

<!-- EVENTS -->
<div id="div_events" class="div_subcontent_activity" style="display:none">
<div class='loading_panel' id='events_loading'>
	<div style='padding: 5px; overflow: hidden;'>
		<?php echo _('Loading asset events')?>  <img src='/ossim/pixmaps/loading3.gif' align='absmiddle'/>
	</div>
</div>
<div style='display:none' id='events_list'>
	<table class='table_data table_data_events'>
		<thead>
			<tr>
				<th style="text-align:left"><?php echo _('Signature')?></th>
				<th><?php echo _('Data Source')?></th>
				<th><?php echo _('Date')?></th>
				<th><?php echo _('Incoming').' / '._('Outgoing')?></th>
				<?php 
				if ($asset_type == 'host' || $asset_type == 'group') 
				{    						
                    ?>
                    <th><?php echo _('Src/Dst')?></th>
                    <?php 
				} 
				else 
				{    						
				    ?>
					<th><?php echo _('Source')?></th>
					<th><?php echo _('Destination')?></th>
					<?php
				}
				?>
				<th><?php echo _('Sensor')?></th>
				<th><?php echo _('Risk')?></th>
			</tr>
		</thead>
		<tbody>
		</tbody>
	</table>
</div>
</div>

<!-- NETFLOWS -->
<div id="div_netflows" class="div_subcontent_activity" style="display:none">
	<div class='loading_panel' id='netflows_loading'>
		<div style='padding: 5px; overflow: hidden;'>
			<?php echo _("Loading asset netflows") ?>  <img src='/ossim/pixmaps/loading3.gif' align='absmiddle'/>
		</div>
	</div>
	<div style='display:none' id='netflows_list'>
	<table class="table_data table_data_netflows">
        <thead>
            <tr>
            <th><?php echo _('Date Flow Start') ?></th>
            <th><?php echo _('Duration') ?></th>
            <th><?php echo _('Protocol') ?></th>
            <th><?php echo _('Src IP') ?>:<?php echo _('Port') ?></th>
            <th><?php echo _('Dst IP') ?>:<?php echo _('Port') ?></th>
            <th><?php echo _('Flags') ?></th>
        </tr>
        </thead>
		<tbody>
		</tbody>
	</table>
	</div>
</div>

<?php
/* End of file activity.php */
/* Location: ./asset_details/modules/activiy.php */