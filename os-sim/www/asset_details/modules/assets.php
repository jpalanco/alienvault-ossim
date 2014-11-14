<?php
/**
 * assets.php
 * 
 * File assets.php is used to:
 * - Be included by index.php as module of asset details
 * - Show Assets dataTable of a Network or a Group
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

<div class='loading_panel' id='hosts_loading'>
	<div style='padding: 5px; overflow: hidden;'>
		<?php echo _('Loading asset hosts') ?>  <img src='/ossim/pixmaps/loading3.gif' align='absmiddle'/>
	</div>
</div>
<!-- HOSTS -->
<div style='display:none' id='hosts_list'>

    <table class='table_data table_data_hosts' width='100%'>
    	<thead>
    		<tr>
    			<th>
    				<?php echo _('Host Name'); ?>
    			</th>
    			
    			<th>
    				<?php echo _('IP'); ?>
    			</th>
    			
    			<th>
    				<?php echo _('FQDN'); ?>
    			</th>
    			
    	        <th>
    				<?php echo _('Device Type'); ?>
    			</th>
    										
    			<th>
    				<?php echo _('Description');?>
    			</th>
    			<?php
    			// Delete link for Groups
    			if ($asset_type == 'group')
    			{
    			?>
        			<th></th>
        			<?php
    			}
    			?>
    		</tr>
    	</thead>
    	<tbody>
    	</tbody>
    </table>
</div>

<?php
/* End of file assets.php */
/* Location: ./asset_details/modules/assets.php */