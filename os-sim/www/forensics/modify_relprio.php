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

include_once('av_init.php');
Session::logcheck("analysis-menu", "EventsForensics");

if ( !Session::am_i_admin() ) 
{
	echo ossim_error(_("You don't have permission to see this page"));
	exit();
}

/* connect to db */
$db   = new ossim_db();
$conn = $db->connect();

$plugin_id   = GET('id');
$plugin_sid  = GET('sid');
$prio        = GET('prio');
$rel         = GET('rel');
$category    = GET('category');
$subcategory = GET('subcategory');

ossim_valid($plugin_id, OSS_DIGIT,                 'illegal:' . _("plugin_id"));
ossim_valid($plugin_sid, OSS_DIGIT,                'illegal:' . _("plugin_sid"));
ossim_valid($prio, OSS_DIGIT, OSS_NULLABLE,        'illegal:' . _("prio"));
ossim_valid($rel, OSS_DIGIT, OSS_NULLABLE,         'illegal:' . _("rel"));
ossim_valid($category, OSS_DIGIT, OSS_NULLABLE,    'illegal:' . _("category"));
ossim_valid($subcategory, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("subcategory"));

if (ossim_error()) {
    die(ossim_error());
}

if (GET('modify') != "") {
	Plugin_sid::update($conn,$plugin_id,$plugin_sid,$prio,$rel,$category,$subcategory);
	Util::memcacheFlush();
	?><script type="text/javascript">parent.GB_close();</script><?
}
// Category
$list_categories = Category::get_list($conn);

// Plugin sid data
$plugins = Plugin_sid::get_list($conn,"WHERE plugin_id=$plugin_id AND sid=$plugin_sid");
$plugin = $plugins[0];

$error_message = "";

if(!isset($plugins[0])){
    $error_message = _("Plugin id or plugin sid doesn't exist");
}
else {
    $rel = $plugin->get_reliability();
    $prio = $plugin->get_priority();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <link rel="stylesheet" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
  <script type="text/javascript" src="../js/jquery.min.js"></script>
  <script type="text/javascript">
	function load_subcategory(category_id) {
			$.ajax({
				type: "POST",
				url: "../conf/modifypluginsid_ajax.php",
				data: { category_id:category_id },
				beforeSend: function( xhr ) {
					$('#ajaxSubCategory').html('<img src="../pixmaps/loading.gif" width="12" align="top" alt="<?php echo _("Loading")?>"><span style="margin-left: 3px;"><?php echo _("Loading")?> ...</span>');
				},
				success: function(msg) {
					$("#ajaxSubCategory").html(msg);
				}
			});
		}
  </script>  
</head>
<body>
<?php
if ($error_message!=""){
    ?>
    <table class="transparent" align="center">
        <tr height="10">
            <td class="nobborder">&nbsp;
            </td>
        </tr>
        <tr>
            <td class="nobborder"><?php echo $error_message ?>
            </td>
        </tr>
        <tr height="10">
            <td class="nobborder">&nbsp;
            </td>
        </tr>
        <tr>
            <td class="nobborder" style="text-align:center">
                <form>
                    <input type="button" value="<?php echo _("Back")?>" onclick="parent.GB_hide();" />
                </form>
            </td>
        </tr>
    </table>
<?php
}
else {
?>
    <form method="get">
    <input type="hidden" name="modify" value="1">
    <input type="hidden" name="id" value="<?=$plugin_id?>">
    <input type="hidden" name="sid" value="<?=$plugin_sid?>">
    <table class="transparent" align="center">
		<tr>
		    <td colspan="2" class="center nobborder" style="padding:10px"><b><?=$plugin->get_name()?></b></td>
		</tr>
		<tr>
		    <td class="nobborder"> <?=_("Priority")?>: </td>
		    <td class="nobborder left">
		        <select name="prio">
		        <? for ($i = 0; $i <= 5; $i++) { ?>
		        <option value="<?=$i?>" <? if ($prio == $i) echo "selected"?>><?=$i?>
		        <? } ?>
		        </select>
		    </td>
		</tr>
		<tr>
		    <td class="nobborder"> <?=_("Reliability")?>:</td>
		    <td class="nobborder left">
		        <select name="rel">
		        <? for ($i = 0; $i <= 10; $i++) { ?>
		        <option value="<?=$i?>" <? if ($rel == $i) echo "selected"?>><?=$i?>
		        <? } ?>
		        </select>
		    </td>
		</tr>
		<tr>
		  <td class="nobborder"> <?php echo gettext("Category"); ?>: </td>
		  <td class="nobborder left">
		        <select name="category" onchange="load_subcategory(this.value);">
					<option value='NULL'<?php if ($plugin->get_category_id()=='') { echo ' SELECTED'; } ?>>&nbsp;</option>
				<?php foreach ($list_categories as $category) { ?>
					<option value='<?php echo $category->get_id(); ?>'<?php if ($plugin->get_category_id()==$category->get_id()) { echo ' SELECTED'; } ?>><?php echo  str_replace('_', ' ', $category->get_name()); ?></option>
				<?php } ?>
		        </select>
		  </td>
		</tr>
		<tr>
		    <td class="nobborder"> <?php echo gettext("Subcategory"); ?>: </td>
		    <td class="nobborder left">
			<div id="ajaxSubCategory">
				<select name="subcategory">
				<?php if ($plugin->get_subcategory_id()=='') { ?>
					<option value='NULL' SELECTED>&nbsp;</option>
				<?php
				}else{
				// Subcategory
				require_once 'classes/Subcategory.inc';
		
				$list_subcategories=Subcategory::get_list($conn,'WHERE cat_id='.$plugin->get_category_id().' ORDER BY name');
				foreach ($list_subcategories as $subcategory) {
				?>
					<option value='<?php echo $subcategory->get_id(); ?>'<?php if ($plugin->get_subcategory_id()==$subcategory->get_id()) { echo ' SELECTED'; } ?>><?php echo  str_replace('_', ' ', $subcategory->get_name()); ?></option>
				<?php
					}
				}
				?>
				</select>
			</div>
		  </td>
		</tr>      
        <tr>
            <td colspan="2" class="center nobborder" style="padding:10px"><input type="submit" value="<?=_("Update")?>"/></td>
        </tr>
    </table>
    </form>
<?php
}
?>
</body>
</html>
