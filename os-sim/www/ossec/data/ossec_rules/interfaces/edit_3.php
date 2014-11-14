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


require_once dirname(__FILE__) . '/../../../conf/config.inc';

Session::logcheck('environment-menu', 'EventsHidsConfig');

    
$title = ($editable == TRUE) ? _("Edit node:  $node_name ") : _("Show node:  $node_name ");
?>

<div id='edit_container'>

<form name='form_m' id='form_m'>
    <table id='header_rule'>
        <tbody>
            <tr><td class='sec_title'><?php echo $title;?></td></tr>
        </tbody>
    </table>

    <?php
    if ($editable == TRUE || ($editable != TRUE && count($child['tree']['@attributes']) > 1))
    {
        ?>
        <table class='er_container' id='erc1'>
            <tbody id='erb_c1'>
                <?php
                    echo Ossec_utilities::print_subheader('attributes', $editable);

                    $at_data = array(
                        'data'        => $attributes,
                        'img_path'    => 'images',
                        'is_editable' => $editable,
                        'lk_name'     => $lk_name
                    );

                    echo Ossec_utilities::print_attributes($at_data);
                ?>
            </tbody>
        </table>
        <?php
    }
    ?>
    
    <table class='er_container' id='erc2'>
        <tbody id='erb_c2'>
            <?php
                $show_actions = ($editable == TRUE) ? TRUE : FALSE;

                echo Ossec_utilities::print_subheader('txt_nodes', $editable, $show_actions);

                if ($editable == TRUE)
                {
                    ?>
                    <tr id='<?php echo $lk_value?>'>
                        <td class='n_name'>
                            <input type='text' class='n_input auto_c' name='tn_label-<?=$lk_value?>' id='tn_label_<?=$lk_value?>' value='<?=$child['node']?>'/>
                            <input type='hidden' name='n_label-<?=$lk_value?>' id='n_label_<?=$lk_value?>' value='<?=$child['node']?>'/>
                        </td>
                        <td class='n_value'><textarea name='n_txt_<?=$lk_value?>' id='n_txt-<?=$lk_value?>'><?=$child['tree'][0]?></textarea></td>
                        <td class='actions_bt_at' style='width:80px;'>
                            <a onclick="delete_at('<?=$lk_value?>','txt_node', 'images');"><img src='images/delete.gif' alt='Delete' title='<?php echo _('Delete Text Node')?>'/></a>
                        </td>
                    <?php
                }
                else
                {
                    ?>
                    <td class='n_name read_only'><?php echo $child['node']?></td>
                    <td class='n_value read_only'><?php echo $child['tree'][0]?></td>
                    <?php
                }
                ?>
            </tr>

        </tbody>
    </table>

    <?php echo Ossec_utilities::print_subfooter($sf_data, $editable);?>
    </form>
</div>