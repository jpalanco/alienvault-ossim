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

$directive_id = GET('directive_id');

ossim_valid($directive_id, OSS_DIGIT, 'illegal:' . _('Directive ID'));

if (ossim_error() === TRUE)
{
    die(ossim_error());
}

$db    = new ossim_db();
$conn  = $db->connect();

// Get Directive info
$kdocs = Repository::get_linked_by_directive($conn, $directive_id);
?>
	
<table class="transparent" height="100%" width="100%">
	<tr>
		<td class="nobborder" valign="top">
			<table class='noborder table_data_<?php echo $directive_id ?>' width='100%' align="center">
				<thead>
					<tr>
						<th>
							<?php echo _('Date') ?>
						</th>
						<th>
							<?php echo _('Title') ?>
						</th>
					</tr>
				</thead>
				<tbody>
				
				<?php  
				foreach ($kdocs as $doc) 
				{ 
				?>
				
					<tr>
						<td>
							<?php echo $doc['date'] ?>
						</td>
						<td>
							<a href="../repository/repository_document.php?id_document=<?php echo $doc['id'] ?>&maximized=1" class="greybox_kdb"><?php echo $doc['title'] ?></a>
						</td>
					</tr>
					
				<?php 
				} 
				?>
				
				</tbody>
			</table>
		</td>
	</tr>
</table>
<?php

/* End of file get_directive_kdb.php */
/* Location: ./directives/get_directive_kdb.php */
