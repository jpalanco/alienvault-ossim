<?php
/**
 * rule_tpl.php
 * 
 * File rule_tpl.php is used to:
 * - Print the rule in GUI directive editor, included from Directive_editor::print_rule
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

// This code comes from directive_editor.inc: public function print_rule($engine_id, $level, &$rules, $xml_file = "")

?>
        <tr bgcolor="#ffffff">
        
        <?php
        // Print Row Cells
        foreach($columns_arr as $col_label) {
            $this->print_rule_cell($conn, $col_label, $this, $level, $editable, $engine_id, $id_dir, $xml_file);
        }
        ?>
        
    <td nowrap>
        <a href="" onclick="toggle_directive_rulemore('<?php echo $this->id ?>');return false"><img id="rulemore_arrow_<?php echo $this->id ?>" class="rulemore_arrow" src="../pixmaps/arrow_green.gif" align="absmiddle" border="0"/> <b><?php echo _("More") ?></b></a>
        <div style="position:relative;display:none" class="rulemore" id="rulemore_<?php echo $this->id ?>">
        <div style="position:absolute;right:0px;overflow:auto;max-width:800px;padding:4px;padding-bottom:8px;background-color:white;border:1px solid #CCC">
            <table>
                <tr>
                    <?php foreach ($columns as $col_label => $col_name) if (!in_array($col_label, $columns_arr)) { ?>
                    <th><?php echo $col_name ?></th>
                    <?php } ?>
                </tr>
                <tr>
                    <?php
                    // Print the rest of cells
                    foreach ($columns as $col_label => $col_name) if (!in_array($col_label, $columns_arr)) {
                        $this->print_rule_cell($conn, $col_label, $this, $level, $editable, $engine_id, $id_dir, $xml_file);
                    }
                    ?>
                </tr>
            </table>
        </div>
        </div>
    </td>
    <?php if (preg_match("/(\/|^)user\.xml$/", $xml_file)) { ?>
    <td>
        <table class="transparent">
            <tr>
                <td width="16">
                    <a href="" class="info" id="new_rule_<?php echo $newid ?>" onclick="GB_show('New Rule', 'wizard_rule.php?xml_file=<?php echo $xml_file ?>&id=<?php echo $newid ?>&directive_id=<?php echo $id_dir ?>&engine_id=<?php echo $engine_id ?>&level=<?php echo $level + 1 ?>', 600, '90%');return false" TITLE="<?php echo gettext("Create a rule inside")." ("._("Level")." ".($level+1).")" ?>"><img src='../pixmaps/plus<?php if ($level > 1) { ?>-small<?php } ?>.png' border='0'></img></a>
                </td>
                <td width="16">
                    <?php if ($this->level > 1) { ?>
                    <a href="" class="info" id="delete_rule_<?php echo $this->id ?>" onclick="if (confirm('<?php echo Util::js_entities(_("Are you sure you want to delete this rule ?")) ?>')) { delete_rule('<?php echo $this->id ?>', <?php echo $id_dir ?>, '<?php echo $xml_file ?>'); } return false;" style="marging-left:20px; cursor:pointer" TITLE="<?php echo gettext("Delete this rule") ?>"><img src='../pixmaps/delete-small.gif' border='0'></img></a>
                    <?php } ?>
                </td>
                <td width="16">
                    <?php if ($this->level > 1) { ?><a href="" class="info" id="copy_rule_button_<?php echo $this->id ?>" onclick="copy_rule('<?php echo $this->id ?>', <?php echo $id_dir ?>, '<?php echo $xml_file ?>');return false" TITLE="<?php echo gettext("Copy this rule") ?>"><img src='../pixmaps/copy-small.png' border='0'></img></a><?php } ?>
                </td>
                <td width="16">
                    <?php if ($level > 2) { ?><a href="" class="info" id="move_rule_left_<?php echo $this->id ?>" onclick="move_rule('<?php echo $this->id ?>', <?php echo $id_dir ?>, '<?php echo $xml_file ?>', 'left');return false;" TITLE="<?php echo gettext("Move rule left (previous correlation level)") ?>"><img src='../pixmaps/arrow-180-small.png' border='0'></img></a>
                    <?php } elseif($level > 1) { ?>
                    <img src='../pixmaps/arrow-180-small.png' style="opacity:0.3" />
                    <?php } ?>
                </td>
                <td width="16">
                    <?php if ($level > 1 && $level_count > 1 && !$is_first) { ?><a href="" class="info" id="move_rule_right_<?php echo $this->id ?>" onclick="move_rule('<?php echo $this->id ?>', <?php echo $id_dir ?>, '<?php echo $xml_file ?>', 'right');return false;" TITLE="<?php echo gettext("Move rule right (next correlation level)") ?>"><img src='../pixmaps/arrow-000-small.png' border='0'></img></a>
                    <?php } elseif($level > 1) { ?>
                    <img src='../pixmaps/arrow-000-small.png' style="opacity:0.3" />
                    <?php } ?>
                </td>
                <td width="16">
                    <?php if ($level > 1 && $level_count > 1 && !$is_first) { ?><a href="" class="info" id="move_rule_up_<?php echo $this->id ?>" onclick="move_rule('<?php echo $this->id ?>', <?php echo $id_dir ?>, '<?php echo $xml_file ?>', 'up');return false;" TITLE="<?php echo gettext("Move rule up (same correlation level)") ?>"><img src='../pixmaps/arrow-090-small.png' border='0'></img></a>
                    <?php } elseif($level > 1) { ?>
                    <img src='../pixmaps/arrow-090-small.png' style="opacity:0.3" />
                    <?php } ?>
                </td>
                <td width="16">
                    <?php if ($level > 1 && $level_count > 1 && !$is_last) { ?><a href="" class="info" id="move_rule_down_<?php echo $this->id ?>" onclick="move_rule('<?php echo $this->id ?>', <?php echo $id_dir ?>, '<?php echo $xml_file ?>', 'down');return false;" TITLE="<?php echo gettext("Move rule down (same correlation level)") ?>"><img src='../pixmaps/arrow-270-small.png' border='0'></img></a>
                    <?php } elseif($level > 1) { ?>
                    <img src='../pixmaps/arrow-270-small.png' style="opacity:0.3" />
                    <?php } ?>
                </td>
            </tr>
        </table>
        </td>
        <?php } ?>
      </tr>
                
<?php       

/* End of file rule_tpl.php */
/* Location: ./directives/templates/rule_tpl.php */
