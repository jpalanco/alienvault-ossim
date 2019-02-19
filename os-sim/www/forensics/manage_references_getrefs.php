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


require_once ('av_init.php');
Session::logcheck("analysis-menu", "EventsForensics");

$plugin_id      = GET('plugin_id');
$plugin_sid     = GET('plugin_sid');
$delete_ref_id  = GET('delete_ref_id');
$newref_type_id = GET('newref_type');
$newref_value   = GET('newref_value');


ossim_valid($plugin_id,      OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("Plugin ID"));
ossim_valid($plugin_sid,     OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("plugin SID"));
ossim_valid($delete_ref_id,  OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("Delete Reference ID"));
ossim_valid($newref_type_id, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("New Reference Type"));
ossim_valid($newref_value,   OSS_NULLABLE, OSS_DIGIT, OSS_ALPHA, OSS_SCORE, OSS_URL, OSS_COLON, OSS_PUNC_EXT, 'illegal:' . _("New Reference value"));


if ( ossim_error() ) {
   echo ossim_error();
   exit();
}
	
	
if ( $plugin_sid == "" || $plugin_id == "" ) {

	echo ossim_error(_("Data Source ID and/or Event Type are empty"));
	exit();
}

$db   = new ossim_db(true);
$conn = $db->connect();

if ( $delete_ref_id != "" ) 
{
	$sql    = "DELETE FROM alienvault_siem.sig_reference WHERE plugin_id = ? AND plugin_sid = ? AND ref_id = ?";
	$params = array($plugin_id, $plugin_sid, $delete_ref_id);
	$rs     = $conn->Execute($sql, $params);
	if (!$rs)
	{
	    Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
	}
}

if ( $newref_type_id != "" && $plugin_id != "" && $plugin_sid != "" && $newref_value != "" ) 
{
	$sql    = "INSERT INTO alienvault_siem.reference (ref_system_id,ref_tag) VALUES (?, ?)";
	$params = array($newref_type_id, $newref_value);
	$rs     = $conn->Execute($sql, $params);
	if (!$rs)
	{
	    Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
	}
	
	$sql    = "INSERT INTO alienvault_siem.sig_reference (plugin_id,plugin_sid,ref_id) VALUES (?, ?, LAST_INSERT_ID())";
	$params = array($plugin_id, $plugin_sid);
	$rs     = $conn->Execute($sql, $params);
	if (!$rs)
	{
	    Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
	}
}

$sql    = "SELECT reference.ref_tag,reference_system.ref_system_id,reference_system.ref_system_name,reference.ref_id 
		   FROM alienvault_siem.reference, alienvault_siem.reference_system, alienvault_siem.sig_reference 
		   WHERE sig_reference.plugin_id = ? 
		   AND sig_reference.plugin_sid = ? 
		   AND sig_reference.ref_id=reference.ref_id 
		   AND reference.ref_system_id=reference_system.ref_system_id";
$params = array($plugin_id, $plugin_sid);
$rs     = $conn->Execute($sql, $params);
if (!$rs)
{
    Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
}

if (!$rs->EOF)
{
	?>
	<table class='table_list'>
		<tr>
			<td class='sec_title' colspan="4"><?php echo _("References found")?></td>
		</tr>
		
		<?php 
		$i = 2; 
		while (!$rs->EOF) 
		{
			$color = ( $i % 2 == 0 )? "odd" :"even";
			?>
			<tr class='<?php echo $color?>'>
				<td style='width:20px; white-space: nowrap;' class="center">
					<img src="manage_references_icon.php?id=<?php echo $rs->fields['ref_system_id']?>" border="0"/>
				</td>
				
				<td class="center"><?php echo $rs->fields['ref_system_name']?></td>
				
				<td class='left'><?php echo $rs->fields['ref_tag']?></td>
				
				<td style='width:20px;' class="center">
					<a href="javascript:;" onclick="ref_delete_plugin(<?php echo $plugin_id?>,<?php echo $plugin_sid?>,<?php echo $rs->fields['ref_id']?>);return false;">
						<img src="../pixmaps/tables/table_row_delete.png" border="0"/>
					</a>
				</td>
			</tr>
			<?php 
			$i++;
			$rs->MoveNext();
		} 
		?>
	</table>
	<?php 
} 
?>
