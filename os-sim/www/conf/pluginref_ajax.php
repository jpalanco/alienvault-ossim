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
Session::logcheck("configuration-menu", "ConfigurationPlugins");

$num        = POST('num');
$plugin_id  = POST('plugin_id');
$plugin_sid = POST('plugin_sid');
$manage     = POST('manage');

ossim_valid($num, OSS_DIGIT,                        'illegal:' . _("Type"));
ossim_valid($plugin_id, OSS_DIGIT,                  'illegal:' . _("Plugin ID"));
ossim_valid($plugin_sid, OSS_DIGIT, OSS_NULLABLE,   'illegal:' . _("Plugin SID"));
ossim_valid($manage, OSS_DIGIT, OSS_NULLABLE,       'illegal:' . _("Manage"));

$name = 'sidajax'.$num;
$id   = 'sidajax'.$num;

if ( ossim_error() ) 
{
    ?>
	<select class='vfield' name="<?php echo 'sidajax'.intval($num) ?>" id="<?php echo 'sidajax'.intval($num) ?>">
		<option value='' selected='selected'>-- <?php echo _("Data not found")?> -- </option>
	</select>
	<?php
	exit();
}



$db   = new ossim_db();
$conn = $db->connect();

$plugin_list = Plugin_sid::get_list($conn, "WHERE plugin_id=$plugin_id ORDER BY name", 0);
$select_msg  = ( $num == 2 ) ? _('Select Reference SID Name') : _('Select Event Type');
$onchange    = ( $manage == 1 ) ? "load_ref('$num'); load_refs()" : "load_ref('$num');";

$db->close($conn);
?>

<select class='vfield' name="<?php echo $name?>" id="<?php echo $id?>" onchange="<?php echo $onchange?>">
	<option value=""><?php echo $select_msg?></option>
	<?php
	
	foreach($plugin_list as $plugin) 
	{
		$selected = ( $plugin_sid  == $plugin->get_sid() ) ? ' selected="selected"' : '';
		?>
		<option value="<?php echo $plugin->get_sid()?>"<?php echo $selected?>><?php echo $plugin->get_name()?></option>
		<?php
	}
	?>
</select>
