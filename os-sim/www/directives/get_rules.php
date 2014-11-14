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

Session::logcheck("configuration-menu", "CorrelationDirectives");


$directive_id = GET('directive_id');
$file         = GET('file');
$engine_id    = GET('engine_id');
$rule         = GET('rule');
$mode         = GET('mode');

ossim_valid($directive_id, OSS_DIGIT, 'illegal:' . _("Directive ID"));
ossim_valid($file, OSS_ALPHA, OSS_DOT, OSS_SCORE, 'illegal:' . _("XML File"));
ossim_valid($engine_id, OSS_HEX, OSS_SCORE, 'illegal:' . _("Engine ID"));
ossim_valid($rule, OSS_DIGIT, '\-', OSS_NULLABLE, 'illegal:' . _("Rule ID"));
ossim_valid($mode, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Mode"));

if ( ossim_error() ) { 
    die(ossim_error());
}

$directive_editor = new Directive_editor($engine_id);
$filepath = (file_exists($directive_editor->engine_path."/".$file)) ? $directive_editor->engine_path."/".$file : $directive_editor->main_path."/".$file;

if (preg_match("/^\d+-\d+-\d+$/", $rule)) {
	if (GET('mode') == "delete") {
		$dom = $directive_editor->get_xml($filepath, "DOMXML");
	    $direct = $directive_editor->getDirectiveFromXML($dom, $directive_id);
	    $tab_rules = $direct->rules;
	    $directive_editor->delrule($rule, &$tab_rules);
	    if (!$directive_editor->save_xml($filepath, $dom, "DOMXML")) {
	    	echo "<!-- ERRORDELETE -->";
	    }
	} elseif (GET('mode') == "copy") {
		$dom = $directive_editor->get_xml($filepath, "DOMXML");
	    $direct = $directive_editor->getDirectiveFromXML($dom, $directive_id);
	    $tab_rules = $direct->rules;
	    list($id_dir, $id_rule, $id_father) = explode("-", $rule);
	    $old_rule = $tab_rules[$id_rule];
	    $new_rule = $old_rule->rule->cloneNode(true); // deep = true
	    $new_rule->setAttribute("name", "Copy of " . $new_rule->getAttribute("name"));
	    // Can not copy the root rule at same level => copy as a child (Button disabled for the moment)
	    $parent = $old_rule->rule->parentNode;
	    $parent->appendChild($new_rule);
	    $directive_editor->save_xml($filepath, $dom, "DOMXML");
	} elseif (GET('mode') == "move") {
		$dom = $directive_editor->get_xml($filepath, "DOMXML");
	    $direct = $directive_editor->getDirectiveFromXML($dom, $directive_id);
	    $tab_rules = $direct->rules;
	    switch (GET('direction')) {
	        case 'left':
	            $directive_editor->left($dom, $rule, &$tab_rules, $direct);
	            break;
	
	        case 'right':
	            $directive_editor->right($dom, $rule, &$tab_rules, $direct);
	            break;
	
	        case 'up':
	            $directive_editor->up($dom, $rule, &$tab_rules, $direct);
	            break;
	
	        case 'down':
	            $directive_editor->down($dom, $rule, &$tab_rules, $direct);
	            break;
	    }
	    $directive_editor->save_xml($filepath, $dom, "DOMXML");
	}
}

// Get columns
$columns = array(
"name" => _("Name"),
"reliability" => _("Reliability"),
"time_out" => _("Timeout"),
"occurrence" => _("Occurrence"),
"from" => _("From"),
"to" => _("To"),
"plugin_id" => _("Data Source"),
"plugin_sid" => _("Event Type"),
"sensor" => _("Sensor"),
"protocol" => _("Protocol"),
"sticky_different" => _("Sticky Dif"),
"username" => _("Username"),
"password" => _("Pass"),
"userdata1" => _("Userdata1"),
"userdata2" => _("Userdata2"),
"userdata3" => _("Userdata3"),
"userdata4" => _("Userdata4"),
"userdata5" => _("Userdata5"),
"userdata6" => _("Userdata6"),
"userdata7" => _("Userdata7"),
"userdata8" => _("Userdata8"),
"userdata9" => _("Userdata9")
);
$db = new ossim_db();
$conn = $db->connect();
$config = new User_config($conn);
$columns_arr = $config->get(Session::get_session_user(), 'directive_editor_cols', 'php', 'directives');
if (count($columns_arr) < 1) {
	$columns_arr = array("name", "reliability", "time_out", "occurrence", "from", "to", "plugin_id", "plugin_sid");
}

$rules = $directive_editor->get_rules($directive_id, $file);

?>
<table width="100%" cellspacing="0">
	<tr><td><table class="transparent" cellpadding="0" cellspacing="0"><tr><td style="padding:3px;text-align:left"><a href="" onclick="toggle_directive_rulelist(<?php echo $directive_id ?>);return false" class='uppercase'><img id="rulelist_arrow_<?php echo $directive_id ?>" src="../pixmaps/arrow_green_down.gif" align="absmiddle" border="0"/> <b><?php echo _("Rules") ?></b></a></td><?php if ($file == "user.xml") { ?><td class="jeditable_msg" style="padding-left:10px;display:none"><i><?php echo "<b>"._("Left-Click")."</b> "._("to edit this field") ?></i></td><td class="jeditable_msg" style="padding-left:5px"><img src="../pixmaps/pencil.png"/></td><?php } ?></tr></table></td></tr>
	<tr>
		<td id="rulelist_<?php echo $directive_id ?>" style="padding:4px">
			<?php if (count($rules) > 0) { ?>
			<table width="100%">
				<tr>
					<?php foreach($columns_arr as $col_label) { ?>
					<th nowrap><?php echo $columns[$col_label] ?></th>
					<?php } ?>
			        <th><a href="" id="customize_link_<?php echo $directive_id ?>" class="info" onclick="GB_show('<?php echo _("Customize Columns") ?>', 'configure_columns.php?xml_file=<?php echo $file ?>&directive_id=<?php echo $directive_id ?>', 500, '900');return false" TITLE="<?php echo gettext("Customize Columns") ?>" class="info">[...]</a></th>
			        <?php if ($file == "user.xml") { ?><th><?php echo gettext("Action"); ?></th><?php } ?>
				</tr>
				<?php foreach ($rules as $rule) { $rule->print_rule($engine_id, $rule->level, $rules, $file); ?>
				
				<?php } ?>
			</table>
			<?php
			} else { ?>
			<table class="transparent"><tr><td align="left"><?php echo _("No rules found into this directive") ?><?php if ($file == "user.xml") { ?> <input type="button" id="button_new_rule" onclick="GB_show('New Rule', 'wizard_rule.php?level=1&directive_id=<?php echo $directive_id ?>&id=<?php echo $directive_id ?>-1-0&xml_file=<?php echo $file ?>&engine_id=<?php echo $engine_id ?>', 600, '90%');return false;" value="<?php echo _("Create new rule") ?>"/><?php } ?></td></tr></table>
			<?php } ?>
		</td>
	</tr>
	<tr><td style="padding:3px;text-align:left"><a href="" onclick="toggle_directive_info(<?php echo $directive_id ?>);return false" class='uppercase'><img id="info_arrow_<?php echo $directive_id ?>" src="../pixmaps/arrow_green.gif" align="absmiddle" border="0"/> <b><?php echo _("Directive info") ?></b></a></td></tr>
	<tr>
		<td id="info_<?php echo $directive_id ?>"></td>
	</tr>
	<?php
	// Get Directive kdb for show or not the tab
	$kdocs = Repository::get_linked_by_directive($directive_editor->conn,$directive_id);
	if (count($kdocs) > 0) {
	?>
	<tr><td style="padding:3px;text-align:left"><a href="" onclick="toggle_directive_kdb(<?php echo $directive_id ?>);return false"><img id="kdb_arrow_<?php echo $directive_id ?>" src="../pixmaps/arrow_green.gif" align="absmiddle" border="0"/> <b><?php echo _("Knowledge DB") ?></b></a></td></tr>
	<tr>
		<td id="kdb_<?php echo $directive_id ?>"></td>
	</tr>
	<?php } ?>
</table>
<?php
$db->close($conn);
?>
