<?php
/**
 * rule_cell_tpl.php
 * 
 * File rule_cell_tpl.php is used to:
 * - Print the rule cell in GUI directive editor, included from Directive_editor::print_rule_cell
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
 * @package    ossim-framework\Directives
 * @autor      AlienVault INC
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt
 * @copyright  2003-2006 ossim.net
 * @copyright  2007-2013 AlienVault
 * @link       https://www.alienvault.com/
 */

// This code comes from directive_editor.inc: private function print_rule_cell($conn, $col_label, $rule, $level, $editable, $engine_id, $id_dir, $xml_file)

        if ($col_label == "name") { ?>
        <td nowrap style="white-space:nowrap;text-align:left<?php if ($level > 1) { ?>;padding-left:<?php echo ($level-1) * 19 ?>px"<?php } ?>"><?php if ($rule->nb_child > 0) { ?><img src="../pixmaps/flechebf.gif" align="absmiddle" />&nbsp;<?php } ?>
            <?php if ($editable) { ?>
            <div style="display:inline;white-space:nowrap" class="editable" id="<?php echo str_replace("=", "EQUAL", base64_encode("name_-_".$rule->id."_-_".$id_dir."_-_".$xml_file."_-_".$rule->name)) ?>"><?php echo $rule->name ?></div>
            <?php } else { ?>
            <?php echo $rule->name ?>
            <?php } ?>
        </td>
        <?php } ?>
        
        <?php if ($col_label == "reliability") { ?>
        <td<?php if ($editable) { ?> nowrap class="editable"<?php } ?> id="<?php echo str_replace("=", "EQUAL", base64_encode("reliability_-_".$rule->id."_-_".$id_dir."_-_".$xml_file."_-_".$rule->reliability)) ?>"><?php echo $rule->reliability ?></td>
        <?php } ?>
        
        <?php if ($col_label == "time_out") { ?>
        <?php if ($level > 1) { ?>
        <td<?php if ($editable) { ?> nowrap class="editable"<?php } ?> id="<?php echo str_replace("=", "EQUAL", base64_encode("time_out_-_".$rule->id."_-_".$id_dir."_-_".$xml_file."_-_".$rule->time_out)) ?>"><?php echo $rule->time_out ?></td>
        <?php } else { ?>
        <td><?php echo _("None") ?></td>
        <?php } ?>
        <?php } ?>
        
        <?php if ($col_label == "occurrence") { ?>
        <td<?php if ($editable) { ?> nowrap class="editable"<?php } ?> id="<?php echo str_replace("=", "EQUAL", base64_encode("occurrence_-_".$rule->id."_-_".$id_dir."_-_".$xml_file."_-_".$rule->occurrence)) ?>"><?php echo $rule->occurrence ?></td>
        <?php } ?>
        
        <?php if ($col_label == "from") { ?>
        <td>
            <table class="transparent" cellpadding="0" cellspacing="0">
            <tr>
                <?php if ($editable) { ?><td><a href="" class="info" id="rule_from_edit_<?php echo $rule->id ?>" title="<?php echo _("Edit") ?>" onclick="GB_show('Rule Network Configuration', 'form_network.php?engine_id=<?php echo $engine_id ?>&directive_id=<?php echo $id_dir ?>&rule_id=<?php echo $rule->id ?>&xml_file=<?php echo $xml_file ?>', 550, 1150);return false;"><img src="../pixmaps/plus-small.png" border="0"></img></a></td><?php } ?>
                <td class="nobborder">
            <?php
            $port_from_string = ($rule->port_from != "" && $rule->port_from != "ANY") ? ":".$rule->port_from : "";
            
            // Loop by Assets
            $final_list = array();
            $pre_list   = explode(",", $rule->from);
            foreach ($pre_list as $list_element) {
                // Asset ID: Resolve by name
                if (preg_match("/(\!)?([0-9A-Fa-f\-]{36})/", $list_element, $found)) {
                    $uuid_aux = str_replace("-", "", strtoupper($found[2]));
                    $h_obj = Asset_host::get_object($conn, $uuid_aux);
                    if ($h_obj != null) {
                        $final_list[] = $found[1].$h_obj->get_name().$port_from_string;
                    } else {
                        $n_obj = Asset_net::get_object($conn, $uuid_aux);
                        if ($n_obj != null) {
                            $final_list[] = $found[1].$n_obj->get_name().$port_from_string;
                        }
                    }
                // Another one (HOME_NET, 12.12.12.12...)
                } else {
                    $final_list[] = $list_element.$port_from_string;
                }
            }
            
            echo (count($final_list) > 0) ? implode("<br>", $final_list) : "<i>"._("External Assets")."</i>";
            
            ?>
                </td>
            </tr>
            </table>
        </td>
        <?php } ?>
        
        <?php if ($col_label == "to") { ?>
        <td>
            <table class="transparent" cellpadding="0" cellspacing="0">
            <tr>
                <?php if ($editable) { ?><td><a href="" class="info" id="rule_to_edit_<?php echo $rule->id ?>" title="<?php echo _("Edit") ?>" onclick="GB_show('Rule Network Configuration', 'form_network.php?engine_id=<?php echo $engine_id ?>&directive_id=<?php echo $id_dir ?>&rule_id=<?php echo $rule->id ?>&xml_file=<?php echo $xml_file ?>', 550, 1150);return false;"><img src="../pixmaps/plus-small.png" border="0"></img></a></td><?php } ?>
                <td>
            <?php
            $port_to_string = ($rule->port_to != "" && $rule->port_to != "ANY") ? ":".$rule->port_to : "";
            
            // Loop by Assets
            $final_list = array();
            $pre_list   = explode(",", $rule->to);
            foreach ($pre_list as $list_element) {
                // Asset ID: Resolve by name
                if (preg_match("/(\!)?([0-9A-Fa-f\-]{36})/", $list_element, $found)) {
                    $uuid_aux = str_replace("-", "", strtoupper($found[2]));
                    $h_obj = Asset_host::get_object($conn, $uuid_aux);
                    if ($h_obj != null) {
                        $final_list[] = $found[1].$h_obj->get_name().$port_to_string;
                    } else {
                        $n_obj = Asset_net::get_object($conn, $uuid_aux);
                        if ($n_obj != null) {
                            $final_list[] = $found[1].$n_obj->get_name().$port_to_string;
                        }
                    }
                // Another one (HOME_NET, 12.12.12.12...)
                } else {
                    $final_list[] = $list_element.$port_to_string;
                }
            }

            echo (count($final_list) > 0) ? implode("<br>", $final_list) : "<i>"._("External Assets")."</i>";
            
            ?>
                </td>
            </tr>
            </table>
        </td>
        <?php } ?>
        
        <?php if ($col_label == "sensor") { ?>
        <td>
            <table class="transparent" cellpadding="0" cellspacing="0">
            <tr>
                <?php if ($editable) { ?><td><a href="" class="info" id="rule_sensor_edit_<?php echo $rule->id ?>" title="<?php echo _("Edit") ?>" onclick="GB_show('Rule Sensor Configuration', 'form_sensor.php?engine_id=<?php echo $engine_id ?>&directive_id=<?php echo $id_dir ?>&rule_id=<?php echo $rule->id ?>&xml_file=<?php echo $xml_file ?>', 500, 640);return false;"><img src="../pixmaps/plus-small.png" border="0"/></a></td><?php } ?>
                <td class="nobborder">
                <?php
                if ($rule->sensor == "ANY") { echo $rule->sensor; }
                else {
                    $sensor_list = explode(",", $rule->sensor);
                    $final_list = array();
                    foreach ($sensor_list as $sensor) {
                        $sensor = preg_replace("/(........)-(....)-(....)-(....)-(............)/", "\\1\\2\\3\\4\\5", strtoupper($sensor));
                        if (preg_match("/^[A-Z0-9]{32}$/", $sensor)) {
                            $sensor_obj = Av_sensor::get_object($conn, $sensor);
                            if ($sensor_obj) $final_list[] = $sensor_obj->get_name()." ";
                        } else {
                            $final_list[] = $sensor;
                        }
                    }
                    echo (count($final_list) > 0) ? implode("<br>", $final_list) : "<i>"._("External Sensors")."</i>";
                }
                ?>
                </td>
            </tr>
            </table>
        </td>
        <?php } ?>
        
        <?php if ($col_label == "plugin_id") { ?>
        <td>
            <table class="transparent" cellpadding="0" cellspacing="0">
            <tr>
            <?php if ($editable) { ?><td><a href="" class="info" id="rule_plugin_edit_<?php echo $rule->id ?>" title="<?php echo _("Edit") ?>" onclick="GB_show('Rule Data Source Configuration', 'form_datasource.php?engine_id=<?php echo $engine_id ?>&directive_id=<?php echo $id_dir ?>&rule_id=<?php echo $rule->id ?>&xml_file=<?php echo $xml_file ?>', 600, '90%');return false;"><img src="../pixmaps/plus-small.png" border="0"></img></a></td><?php } ?>
            <td>
            <?php
            // Data Source
            if ($rule->plugin_id != "") {
                $plugin_id = $rule->plugin_id;
                if ($plugin_list = Plugin::get_list($conn, "WHERE id = $plugin_id")) {
                    $name = $plugin_list[0]->get_name();
                    echo "<a href=\"../conf/pluginsid.php?plugin_id=$plugin_id&" . "name=$name\" target=\"main\"><b>$name</b></a> ($plugin_id)";
                }
            } elseif ($rule->product) {
                $product_types = Product_type::get_list($conn);
                $rule_types = explode(",", $rule->product);
                echo _("Product Type").": ";
                $flag = false;
                foreach ($product_types as $ptype) {
                    if (in_array($ptype->get_id(), $rule_types)){
                        if ($flag) { echo ", "; }
                        echo "<b>".$ptype->get_name()."</b>";
                        $flag = true;
                    }
                }
            } else {
                echo "ANY";
            }
            ?>
            </td>
            </tr>
            </table>
        </td>
        <?php } ?>
        
        <?php if ($col_label == "plugin_sid") { ?>
        <td>
            <table class="transparent" cellpadding="0" cellspacing="0">
            <tr>
            <?php if ($editable) { ?><td><a href="" class="info" id="rule_pluginsid_edit_<?php echo $rule->id ?>" title="<?php echo _("Edit") ?>" onclick="GB_show('Rule Event Type Configuration', 'form_datasource.php?engine_id=<?php echo $engine_id ?>&directive_id=<?php echo $id_dir ?>&rule_id=<?php echo $rule->id ?>&xml_file=<?php echo $xml_file ?>', 600, '90%');return false;"><img src="../pixmaps/plus-small.png" border="0"></img></a></td><?php } ?>
            <td>
            <?php
            // Event Type
            if ($rule->plugin_sid != "") {
                if ($rule->plugin_sid != "ANY") { echo "SIDs: "; }
                $plugin_sid = $rule->plugin_sid;
                $plugin_sid_list = split(',', $plugin_sid);
                if (count($plugin_sid_list) > 30) {
?>
        <a style="cursor:pointer;" TITLE="<?php
                    echo gettext("To view or hide the list of plugin sid click here"); ?>" onclick="$('#plugsid').toggle()"> <?php
                    echo gettext("Expand / Collapse"); ?> </a>
        <div id="plugsid">
<?php
                }
                
                foreach($plugin_sid_list as $sid_negate) {
                    $sid = $sid_negate;
                    if (!strncmp($sid_negate, "!", 1)) $sid = substr($sid_negate, 1);
                                        
                    /* sid == ANY */
                    if (!strcmp($sid, "ANY")) {
                        echo gettext("ANY");
                    }
                    /* sid == X:PLUGIN_SID */
                    elseif (strpos($sid, "PLUGIN_SID")) {
                        echo gettext("$sid_negate");
                    }
                    /* get name of plugin_sid */
                    elseif (preg_match("/^\d+$/", $rule->plugin_id) && $plugin_list = Plugin_sid::get_list($conn, "WHERE plugin_id = ".$rule->plugin_id." AND sid = $sid")) {
                        $name = $plugin_list[0]->get_name();
                        echo "<a title=\"".str_replace("\"", "'", $name)."\" class=\"info\"><b>$sid_negate</b></a>&nbsp; ";
                    }
                    elseif ($rule->product && $plugin_list = Plugin_sid::get_list($conn, "WHERE plugin_id IN (SELECT id FROM plugin WHERE product_type IN (".$rule->product.")) AND sid = $sid")) {
                        $name = $plugin_list[0]->get_name();
                        echo "<a title=\"".str_replace("\"", "'", $name)."\" class=\"info\"><b>$sid_negate</b></a>&nbsp; ";
                    } else {
                        echo "<a title=\"" . gettext("Invalid plugin sid") . "\" style=\"color:red\" class=\"info\">$sid_negate</a>&nbsp; ";
                    }
                }
                if (count($plugin_sid_list) > 30) {
?>
         </div>
<?php
                }
            } elseif ($rule->category) {
                // Can not redeclare class Category. Must do queries...
                $query = "SELECT name FROM category WHERE id = ".$rule->category;
                if (!$rs = & $conn->Execute($query)) {
                    echo "<i>"._("Category Unknown")."</i>";
                } else {        
                    if (!$rs->EOF) {
                        echo _("Category").": <b>".$rs->fields['name']."</b>";
                    }
                }
                if ($rule->subcategory) {
                    $query = "SELECT name FROM subcategory WHERE id = ".$rule->subcategory;
                    if (!$rs = & $conn->Execute($query)) {
                        echo "/<i>"._("SubCategory Unknown")."</i>";
                    } else {        
                        if (!$rs->EOF) {
                            echo "/<b>".$rs->fields['name']."</b>";
                        }
                    }
                }
            }
            ?>
            </td>
            </tr>
            </table>
        </td>
        <?php } ?>
        
        <?php if ($col_label == "protocol") { ?>
        <td style="white-space:nowrap"<?php if ($editable) { ?> class="editable"<?php } ?> id="<?php echo str_replace("=", "EQUAL", base64_encode("protocol_-_".$rule->id."_-_".$id_dir."_-_".$xml_file."_-_".$rule->protocol)) ?>"><?php echo $rule->protocol ?></td>
        <?php } ?>
        
        <?php if ($col_label == "sticky_different") { ?>
        <?php if ($level > 1) { ?>
        <td style="white-space:nowrap"<?php if ($editable) { ?> class="editable"<?php } ?> id="<?php echo str_replace("=", "EQUAL", base64_encode("sticky_different_-_".$rule->id."_-_".$id_dir."_-_".$xml_file."_-_".$rule->sticky_different)) ?>"><?php echo $rule->sticky_different ?></td>
        <?php } else { ?>
        <td><?php echo _("None") ?></td>
        <?php } ?>
        <?php } ?>
        
        <?php if ($col_label == "username") { ?>
        <td style="white-space:nowrap"<?php if ($editable) { ?> class="editable"<?php } ?> id="<?php echo str_replace("=", "EQUAL", base64_encode("username_-_".$rule->id."_-_".$id_dir."_-_".$xml_file."_-_".$rule->username)) ?>"><?php echo $rule->username ?></td>
        <?php } ?>
        
        <?php if ($col_label == "password") { ?>
        <td style="white-space:nowrap"<?php if ($editable) { ?> class="editablepass"<?php } ?> id="<?php echo str_replace("=", "EQUAL", base64_encode("password_-_".$rule->id."_-_".$id_dir."_-_".$xml_file."_-_".$rule->password)) ?>"><?php echo preg_replace("/./", "*", $rule->password) ?></td>
        <?php } ?>
        
        <?php if (preg_match("/userdata\d+/", $col_label)) { ?>
        <td style="white-space:nowrap"<?php if ($editable) { ?> class="editable"<?php } ?> id="<?php echo str_replace("=", "EQUAL", base64_encode($col_label."_-_".$rule->id."_-_".$id_dir."_-_".$xml_file."_-_".$rule->$col_label)) ?>"><?php echo $rule->$col_label ?></td>
        <?php }

/* End of file rule_cell_tpl.php */
/* Location: ./directives/templates/rule_cell_tpl.php */
