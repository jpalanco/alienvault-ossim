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

<div data-name="properties" data-bind="av_table_properties">

	<table class="table_data" id="table_data_properties" data-bind="table_data_properties">
	    <thead>
	        <tr>
	            <th class="center"><input type='checkbox' data-bind='chk-all-rows'/></th>
	            <?php            
	            if ($asset_type == 'network' || $asset_type == 'group')
	            {
	                ?>
	                <th><?php echo _('Asset')?></th>
	                <?php
	            }
	            else
	            {
	                ?>
	                <th><?php echo _('IP Address')?></th>
	                <?php
	            }            
	            ?>
	            <th><?php echo _('Type')?></th>
	            <th><?php echo _('Property')?></th>
	            <th><?php echo _('Date')?></th>
	            <th><?php echo _('Source')?></th>
	            <th><?php echo _('Actions')?></th>
	        </tr>
	    </thead>
	    <tbody>
	        <tr>
	            <td colspan='7'></td>
	        </tr>             
	    </tbody>
	</table>

</div>
