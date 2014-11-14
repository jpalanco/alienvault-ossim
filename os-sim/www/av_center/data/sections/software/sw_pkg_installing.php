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


//Config File
require_once (dirname(__FILE__) . '/../../../config.inc');

?>

<div id='cont_sw_av'>
	<table id='t_inst_pkg'>
		<thead>
			<tr><th class='headerpr_no_bborder' colspan='3'><?php echo _('Update System Information')?></th></tr>
		</thead>
		
		<tbody id='tbody_inst_pkg'>
			<tr>
				<td class='noborder'>
					<div>
						<div id='system_changelog'>
							<div id='soft_update_bar'></div>
							<div id='soft_update_bar_legend'>
    							<?php echo _('Updating System. This process might take several minutes.') ?>
    				        </div>
						</div>
					</div>
				</td>
			</tr>
		</tbody>
		
		<tfoot id='tfoot_inst_pkg'>
			<tr><td class='noborder'></td></tr>
		</tfoot>
	</table>
</div>
  