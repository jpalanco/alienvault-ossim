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

require_once dirname(__FILE__).'/../../conf/config.inc';

Session::logcheck('environment-menu', 'EventsHidsConfig');


$lk_name   = $_SESSION['lk_name'];
$file      = $_SESSION['_current_file'];

$editable  = Ossec::is_editable($file);

$node                           = explode ('</span>', strip_tags($_POST['node'], '<span>'));
$node_name                      = preg_replace('/<span>/', '', $node[0]);
$_SESSION['_current_node']      = $node_name;

$lk_value                       = strip_tags($_POST['lk_value']);
$_SESSION['_current_level_key'] = $lk_value;

$tree                           = $_SESSION['_tree'];
$child                          = Ossec::get_child($tree, $lk_name, $lk_value);

$_SESSION['_current_branch']    = $child;
$parents                        = $child['parents'];
$ac_data                        = Ossec::get_ac_type($parents);

echo implode('##__##',$ac_data).'##__##';


$node_type                      = Ossec::get_node_type($node_name, $child);
$_SESSION["_current_node_type"] = $node_type;

$sf_data = array(
    'handler'  => 'modify',
    'lk_value' => $lk_value,
);


/*
 * Types:
 *   [1]  Attribute
 *   [2]  Attributes
 *   [3]  Text Node
 *   [4]  Node with level <=2
 *   [5]  Node with level > 2
 */


switch ($node_type)
{
    case 1:
        $attributes = array ($node_name => $child['tree']['@attributes'][$node_name], $lk_name =>  $child['tree']['@attributes'][$lk_name]);
        $unique_id  = $lk_value.'_at1';
        include AV_MAIN_ROOT_PATH.'/ossec/templates/ossec_rules/tpl_attribute.php';
    break;

    case 2:
        $attributes = $child['tree'];
        include AV_MAIN_ROOT_PATH.'/ossec/templates/ossec_rules/tpl_attributes.php';
    break;

    case 3:
        $attributes = $child['tree']['@attributes'];

        if (count ($attributes) <= 1)
        {
            $attributes = array ('' => '', $lk_name => $child['tree']['@attributes'][$lk_name]);
        }

        $lk_value  = $child['tree']['@attributes'][$lk_name];
        $txt_nodes = $child['tree'];
        include AV_MAIN_ROOT_PATH.'/ossec/templates/ossec_rules/tpl_text_nodes.php';
    break;

    case 4:
        $attributes = $child['tree']['@attributes'];

        unset($child['tree']['@attributes']);

        if (count ($attributes) <= 1)
        {
            $attributes = array ('' => '', $lk_name =>  $child['tree']['@attributes'][$lk_name]);
        }

        $txt_nodes  = $child['tree'];
        include AV_MAIN_ROOT_PATH.'/ossec/templates/ossec_rules/tpl_rule.php';
    break;
    
    case 5:
        $sf_data['handler'] = 'modify_node';
        $attributes         = $child['tree']['@attributes'];

        if (count ($attributes) <= 1)
        {
            $attributes = array ('' => '', $lk_name =>  $child['tree']['@attributes'][$lk_name]);
        }

        unset($child['tree']['@attributes']);
        $children = $child['tree'];
        include AV_MAIN_ROOT_PATH.'/ossec/templates/ossec_rules/tpl_xml.php';
    break;
}
?>
