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

require_once 'ossim_db.inc';


$category_id = POST('category_id');

ossim_valid($category_id, OSS_DIGIT, 'illegal:' . _("Category"));

if ( ossim_error() ) 
{
    ?>
	<select class='vfield' name="subcategory" id="subcategory">
		<option value='' selected='selected'>-- <?php echo _("Select a category")?> -- </option>
	</select>
	<?php
	exit();
}


$db   = new ossim_db();
$conn = $db->connect();
?>

<select class='vfield' name="subcategory" id="subcategory">
	<?php 
	// Subcategory
	require_once 'classes/Subcategory.inc';

	$list_subcategories=Subcategory::get_list($conn,'WHERE cat_id='.$category_id.' ORDER BY name');
	foreach ($list_subcategories as $subcategory) 
	{
		$selected = ( $category_id == $subcategory->get_id() ) ? ' selected="selected"' : '';
		?>
		<option value='<?php echo $subcategory->get_id();?>'<?php echo $selected?>><?php echo str_replace('_', ' ', $subcategory->get_name());?></option>
		<?php
	}
	$db->close($conn);
	?>
</select>
