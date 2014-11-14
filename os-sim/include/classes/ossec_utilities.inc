<?php
/**
* ossec_utitilities.inc
*
* File ossec_utilities.inc:
*   - Utilities for OSSEC section
*
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
* @package    ossim-framework\Ossec
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


/**
* Class and Function List:
*
*   - get_sensors($conn, $sensor_id = '')
*   - is_sensor_allowed($conn, $sensor_id)
*   - get_default_sensor_id()
*   - parse_array2json($array_xml, $lk_name)
*   - print_subheader($type, $editable, $show_actions = TRUE)
*   - print_subfooter($sf_data, $editable)
*   - print_attributes($at_data)
*   - print_txt_nodes($n_data)
*   - print_children($c_data)
*   - formatXmlString($xml)
*   - formatOutput($input, $lk_name)
*   - clean_string($string)
*   - SIEM_trends_hids($conn, $agent_ip)
*   - get_idm_data_from_agent($agent)
*   - thousands_locale()
*   - plot_graphic($id, $height, $width, $xaxis, $yaxis, $xticks, $xlabel, $display = FALSE)
*/


class Ossec_utilities
{
    /**
     * This function returns the OSSEC sensor list
     *
     * @param object   $conn        Database access object
     * @param string   $sensor_id   [Optional] Sensor ID (Table alienvault.sensor)
     *
     * @throws Exception  If an error occurred
     *
     * @return array
     */
    public static function get_sensors($conn, $sensor_id = '')
    {
        Ossim_db::check_connection($conn);
        
        $res['sensor_opt'] = "<option value=''>"._('No sensors found')."</option>\n";
        $res['sensors']    = array();

        $sensor_id = (empty($sensor_id)) ? self::get_default_sensor_id() : $sensor_id;
        $sensor_id = str_replace('-', '', $sensor_id);
        $sensor_id = strtoupper($sensor_id);

        $system_list = Av_center::get_avc_list($conn);
        $sensor_list = Av_sensor::get_basic_list($conn);
        
        $cnd_1 = (!$system_list || $system_list['status'] == 'error');
        $cnd_2 = (!is_array($sensor_list) || empty($sensor_list));

        if ($cnd_1 || $cnd_2)
        {
            return $res;
        }

        $res['sensor_opt'] = '';

        foreach ($system_list['data'] as $s_data)
        {
            $s_id = strtoupper($s_data['sensor_id']);
            
            if ($s_id != NULL && array_key_exists($s_id, $sensor_list))
            {
                $selected    = ($s_id == $sensor_id) ? ' selected="selected"' : '';
                $sensor_name = $sensor_list[$s_id]['name'];
                $sensor_ip   = $sensor_list[$s_id]['ip'];
            
                $res['sensor_opt'] .= "<option value='".$s_id."'$selected>".$sensor_name." [".$sensor_ip."]"."</option>";
                $res['sensors'][$s_id] = $sensor_name." [".$sensor_ip."]";
            }
        }

        return $res;
    }


    /**
     * This function checks whether sensor is an OSSEC sensor and it is allowed
     *
     * @param object  $conn        Database access object
     * @param string  $sensor_id   [Optional] Sensor ID (Table alienvault.sensor)
     *
     * @throws Exception  If a connection error occurred
     *
     * @return boolean
     */
    public static function is_sensor_allowed($conn, $sensor_id)
    {
        Ossim_db::check_connection($conn);

        require_once 'classes/Security.inc';

        $sensor_id = str_replace('-', '', $sensor_id);

        if (!valid_hex32($sensor_id))
        {
            return FALSE;
        }

        $system_ids = Av_center::get_system_id_by_component($conn, $sensor_id);

        $cnd_1 = Av_sensor::is_allowed($conn, $sensor_id);
        $cnd_2 = (is_array($system_ids) && !empty($system_ids));

        if ($cnd_1 && $cnd_2)
        {
            return TRUE;
        } 

        return FALSE;
    }


    /**
     * This function returns the default sensor ID
     *
     * @throws Exception  If a connection error occurred
     *
     * @return string
     */
    public static function get_default_sensor_id()
    {
        $sensor_id = '';
        
        $db     = new ossim_db();
        $conn   = $db->connect();
        
        $admin_ip = Util::get_default_admin_ip();
        $res      = Av_center::get_system_info_by_ip($conn, $admin_ip);
        
        $db->close();
        
        if ($res['status'] == 'success')
        {
            $sensor_id = $res['data']['sensor_id'];
        }
        
        return $sensor_id;
    }


    /**
     * This function returns the real path from any config file
     *
     * @param string  $sensor_id   Sensor ID (Table alienvault.sensor)
     * @param object  $file_path   File path
     *
     * @return string
     */
    public static function get_real_path($sensor_id, $file_path)
    {
        $path = '/var/alienvault/';

        $db     = new ossim_db();
        $conn   = $db->connect();

        $system_ids = Av_center::get_system_id_by_component($conn, $sensor_id);

        $db->close();

        if (!$system_ids || !is_array($system_ids))
        {
            $exp_msg = _('Error! System ID not found');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $path .= $system_ids['canonical'].'/'.$file_path;

        return $path;
    }


    public static function array2json($array_xml, $filename)
    {
        $lk_name = Ossec::get_key_name($filename);
        $image_url = "../../pixmaps/theme/";

        $json .= "{";
        $at = $array_xml['@attributes'];
        $at = preg_replace("/,$/",'',$at);

        $icon = $image_url.'any.png';
        $json .= "title: '<span>".self::clean_string($filename)."</span>', addClass:'size12', key:'".$at[$lk_name]."', isFolder:'true', icon:'$icon', children:  \n[\n";

        unset ($array_xml['@attributes']);

        $json .= self::parse_array2json($array_xml, $lk_name);
        $json  = preg_replace('/,$/', '', $json);
        $json .= "]";
        $json .= "}";
        $json  = preg_replace('/,\\n]/', ']', $json);
        
        return $json;
    }


    public static function parse_array2json($array_xml, $lk_name)
    {
        $image_url = "../../pixmaps/theme/";

        foreach($array_xml as $key => $value)
        {
            if (is_array($value) && !isset($value['@attributes']) && $key !== '@attributes')
            {
                $json .= self::parse_array2json($value, $lk_name);
                
                continue;
            }
            elseif (is_array($value) && isset($value['@attributes']))
            {
                $icon  = $image_url.Ossec::get_icon($key, $value, $lk_name);
                $title = Ossec::get_title($key, $value, $lk_name);
                $json .= "{title: '$title', addClass: 'size10', key:'".$value['@attributes'][$lk_name]."', isFolder:'true', icon:'$icon'";
                $aux   =  self::parse_array2json($value, $lk_name);
                $json .= (!empty($aux)) ? ", children:  [". $aux."]" : '' ;
                $json .= "},\n";
            }
            elseif (is_array($value) && $key === '@attributes')
            {
                $keys = array_keys ($value);
                
                if (!(count($keys) == 1 && $keys[0] == $lk_name))
                {
                    $json .= "{title: '<span>"._("Attributes")."</span>', addClass: 'size10', key:'attr_".$value[$lk_name]."', icon:'".$image_url."gear-small.png', children: [";
                    foreach ($value as $k => $v)
                    {
                        if ($k !== $lk_name)
                        {
                            $json .= "{title: '<span>".self::clean_string($k)."</span>=".self::clean_string($v)."', addClass: 'size10', key:'".$value[$lk_name]."', icon:'".$image_url."ticket-small.png'},\n";
                        }
                    }
                    
                    $json = preg_replace('/,$/', '', $json);
                    $json .= "]";
                    $json .= "},\n";
                }
            }
        }

        return  $json;
    }


    //Print functions

    public static function print_subheader($type, $editable, $show_actions = TRUE)
    {
        if ($type == 'attributes')
        {
            $actions = ($editable == TRUE) ? "<th class='r_subheader actions_at'>"._('Actions')."</th>" : '';
            $colspan = ($editable == TRUE) ? "colspan='3'" : "colspan='2'";
            
            $subheader = "<tr><th class='at_header headerpr' $colspan><span>"._('Attribute(s)')."</span></th></tr>
                            <tr id='subheader1'>
                                <th class='r_subheader'>"._('Name')."</th>
                                <th class='r_subheader'>"._('Value')."</th>";
                        
            $subheader .= ($show_actions == TRUE) ? $actions : '';
            $subheader .= "</tr>";
        }
        elseif ($type == 'txt_nodes')
        {
            $subheader  = "<tr><th class='txt_node_header headerpr' colspan='3'><span>"._('Text Node(s)')."</span></th></tr>
                           <tr id='subheader2'>
                                <th class='r_subheader'>"._('Name')."</th>
                                <th class='r_subheader'>"._('Value')."</th>";
                        
            $subheader .= ($show_actions == TRUE) ? "<th class='r_subheader'>"._('Actions')."</th>" : '';
            $subheader .= "</tr>";
        }
        elseif ($type == 'rules')
        {
            $class = ($editable == TRUE) ? "class='r_subheader actions_tn'" : "class='r_subheader' style='width:60px;'";
            $subheader  = "<tr><th class='txt_node_header headerpr' colspan='2'><span>"._('Rule(s)')."</span></th></tr>
                            <tr id='subheader2'>
                                <th class='r_subheader'>"._('Name')."</th>";
                            
            $subheader .= ($show_actions == TRUE) ? "<th $class>"._('Actions')."</th>" : '';
            $subheader .= "</tr>";
        }
        else
        {
            $actions = ($editable == TRUE) ? "<th class='r_subheader actions_node'>"._('Actions')."</th>" : '';
            $colspan = ($editable == TRUE) ? "colspan='2'" : '';
            
            $subheader = "<tr><th class='txt_node_header headerpr' $colspan><span>"._('Children')."</span></th></tr>
                            <tr id='subheader2'>
                                <th class='r_subheader' $colspan'>"._('Node')."</th>";
            
            $subheader .= ($show_actions == TRUE) ? $actions : '';
            $subheader .= "</tr>";
        }

        return $subheader;
    }


    public static function print_subfooter($sf_data, $editable)
    {
        $footer = '';

        if ($editable == TRUE)
        {
            $j_function = Util::htmlentities($sf_data['handler'])."('".Util::htmlentities($sf_data['lk_value']);
            
            $footer .= "<div class='button_box'>
                            <div>
                                <input type='button' id='send' class='save_edit' onclick=\"javascript: ".$j_function."');\" value='"._('Save')."'/>
                            </div>
                        </div>";
        }

        return $footer;
    }


    public static function print_attributes($at_data)
    {
        $attributes = $at_data['data'];
        $path       = $at_data['img_path'];
        $editable   = $at_data['is_editable'];
        $lk_name    = $at_data['lk_name'];
        $lk_value   = $attributes[$lk_name];

        $cont = 1;

        foreach ($attributes as $k => $value)
        {
            if ($k !== $lk_name)
            {
                $unique_id = $lk_value.'_at'.$cont;
                
                $tr .= "<tr id='$unique_id'>";
                
                if ($editable == TRUE)
                {
                    $tr .= "<td class='n_name' id='cont_n_label-$unique_id'>
                                <input type='text' class='n_input auto_c' name='tn_label-$unique_id' id='tn_label-$unique_id' value='$k'/>
                                <input type='hidden' name='n_label-$unique_id' id='n_label-$unique_id' value='$k'/>
                            </td>
                        <td class='n_value' id='cont_n_txt-$unique_id'><textarea name='n_txt-$unique_id' id='n_txt-$unique_id'>$value</textarea></td>
                        <td class='actions_bt_at'>
                            <a onclick=\"add_at('$unique_id', 'ats', '$path');\"><img src='$path/add.png' alt='"._('Add')."' title='"._('Add Attribute')."'/></a>
                            <a onclick=\"delete_at('$unique_id','ats', '$path');\"><img src='$path/delete.gif' alt='"._('Delete')."' title='"._('Delete Attribute')."'/></a>
                            <a onclick=\"clone_at('$unique_id');\"><img src='$path/clone.png' alt='"._('Clone')."' title='"._('Clone Attribute')."'/></a>
                        </td>";
                }
                else
                {
                    $tr .= "<td class='n_name read_only'>$k</td>
                            <td class='n_value read_only'>$value</td>";
                }
                
                $tr .= "</tr>\n";
                $cont++;
            }
        }
        
        return $tr;
    }


    public static function print_txt_nodes($n_data)
    {
        $txt_nodes  = $n_data['data'];
        $path       = $n_data['img_path'];
        $editable   = $n_data['is_editable'];
        $lk_name    = $n_data['lk_name'];

        foreach ($txt_nodes as $value)
        {
            $key  = array_keys ($value);
            $name = $key[0];
            
            $attributes  = $value[$name]['@attributes'];
            $lk_value    = $attributes[$lk_name];
            
            $v = $value[$name][0];
            
            $colspan = ($editable == TRUE) ? "colspan='3'" : "colspan='2'";
            $tr .= "<tr id='$lk_value'>";
                    
                if ($editable == TRUE)
                {
                    $tr .= "<td class='n_name' id='cont_n_label-$lk_value'>
                                <input type='text' class='n_input auto_c' name='tn_label-$lk_value' id='tn_label-$lk_value' value='$name'/>
                                <input type='hidden' name='n_label-$lk_value' id='n_label-$lk_value' value='$name'/>
                            </td>
                            <td class='n_value' id='cont_n_txt-$lk_value'><textarea name='n_txt-$lk_value' id='n_txt-$lk_value'>$v</textarea></td>
                            <td class='actions_bt_tn' style='width:95px;'>
                                <a onclick=\"add_node('$lk_value', 'txt_nodes', '$path');\"><img src='$path/add.png' alt='"._('Add')."' title='"._('Add Text Node')."'/></a>
                                <a onclick=\"delete_at('$lk_value','txt_nodes', '$path');\"><img src='$path/delete.gif' alt='"._('Delete')."' title='"._('Delete Text Node')."'/></a>
                                <a onclick=\"clone_node('$lk_value');\"><img src='$path/clone.png' alt='"._('Clone')."' title='"._('Clone Text Node')."'/></a>
                                <a onclick=\"show_at('ats_$lk_value');\"><img src='$path/show.png' alt='"._('Show Attributes')."' title='"._('Show Attributes')."'/></a>
                            </td>";
                }
                else
                {
                    $tr .= "<td class='n_name read_only' id='cont_n_label-$lk_value'>$name</td>
                            <td class='n_value read_only' id='cont_n_txt-$lk_value'>$v</td>
                            <td class='actions_bt_tn' style='width:60px;'>";
                        
                    // With attribute lk_value -> count($attributes) > 1
                    if (count($attributes) > 1)
                    {
                        $tr .= "<a onclick=\"show_at('ats_$lk_value');\"><img src='$path/show.png' alt='"._('Show Attributes')."' title='"._('Show Attributes')."'/></a>";
                    }
                    else
                    {
                        $tr .= "<img src='$path/show.png' class='dis_icon' alt='"._('Show Attributes')."' title='"._('Show Attributes')."'/>";
                    }
                    
                    $tr .= "</td>";
                
                }
            
            $tr .= "</tr>";
            
            if ($editable == TRUE || ($editable != TRUE && count($attributes) > 1))
            {
                $tr .= "<tr id='ats_$lk_value' style='display: none;'>
                    <td colspan='3'>
                        <div class='cont_ats_txt_node'>
                            <table class='er_container'>
                                <tbody id='erb_$lk_value'>
                                    <tr id='subheader_$lk_value'>
                                        <th class='txt_node_header headerpr' $colspan>
                                            <div class='fleft'><span>"._('Text Node Attributes')."</span></div>
                                            <div class='fright'><a style='float: right' onclick=\"hide_at('ats_$lk_value');\"><img src='images/arrow-up.png' alt='arrow' title='"._('Hide Attributes')."' align='absmiddle'/></a></div>
                                        </th>
                                    </tr>";

                $actions = ($editable == TRUE) ? "<th class='actions_bt_tn' style='width:60px;'>"._('Actions')."</th>" : '';

                $tr .= "    <tr id='subheader2_$lk_value'>
                                <th class='r_subheader'>"._('Name')."</th>
                                <th class='r_subheader'>"._('Value')."</th>
                                $actions
                            </tr>";

                if (count($attributes) <= 1)
                {  
                    $attributes = array ($lk_name => $lk_value, '' => '');
                }

                $at_data = array(
                    'data'        => $attributes,
                    'img_path'    => 'images',
                    'is_editable' => $editable,
                    'lk_name'     => $lk_name
                );

                $tr .= self::print_attributes($at_data);


            $tr .="</tbody>
                   </table>
                </div>
            </td>
            </tr>";

            }
        }
        
        return $tr;
    }


    public static function print_children($c_data)
    {
        $children  = $c_data['data'];
        $path      = $c_data['img_path'];
        $editable  = $c_data['is_editable'];
        $lk_name   = $c_data['lk_name'];

        foreach ($children as $value)
        {
            $key         = array_keys($value);
            $name        = $key[0];
            $attributes  = $value[$name]['@attributes'];
            $lk_value    = $attributes[$lk_name];
            $name_at     = '';
            
            foreach ($attributes as $k_at => $v_at)
            {
                if ($k_at != $lk_name)
                {
                    $name_at .= $k_at."=\"$v_at\" ";
                }
            }
            
            $name = trim($name." ".$name_at);
            
            $tr .= "<tr id='$lk_value' class='__lk-###'>";
            
            $class = (count($children) <= 1) ? "class='delete_c unbind'" : "class='delete_c'";
            
            if ($editable == TRUE)
            {
                $tr .= "<td class='n_name n_node' id='cont_n_label-$lk_value'>$name</td>
                        <td class='actions_bt_node'>
                            <a class='edit_c' onclick=\"edit_child('$lk_value');\"><img src='$path/edit.png' alt='"._('Edit Rule')."' title='"._('Edit Rule')."'/></a>
                            <a $class onclick=\"delete_child('$lk_value','$path');\"><img src='$path/delete.gif' alt='"._('Delete')."' title='"._('Delete Rule')."'/></a>
                            <a class='clone_c' onclick=\"clone_child('$lk_value', '$path');\"><img src='$path/clone.png' alt='"._('Clone')."' title='"._('Clone Rule')."'/></a>
                            <a class='show_c' onclick=\"show_node_xml('$lk_value');\"><img src='$path/xml.png' alt='"._('Show XML node')."' title='"._('Show XML node')."'/></a>
                        </td>";
            }
            else
            {
                $tr .= "<td class='n_name n_node' id='cont_n_label-$lk_value'>$name</td>
                        <td class='actions_bt_node'>
                            <a class='edit_c' onclick=\"edit_child('$lk_value');\"><img src='$path/show.png' alt='"._('Show Rule')."' title='"._('Show Rule')."'/></a>
                            <a class='show_c' onclick=\"show_node_xml('$lk_value');\"><img src='$path/xml.png' alt='"._('Show XML node')."' title='"._('Show XML node')."'/></a>
                        </td>";
            }
            
            $tr .= "</tr>";
            $tr .= "<tr id='node_xml-$lk_value' class='oss_hide'>
                        <td colspan='2'>
                            <div class='header_node_xml'>"._('XML Node').":</div>
                            <div class='cont_node_xml' id='cont_node_xml-$lk_value'>
                                <textarea id='txt_rule-$lk_value' class='txt_rule_format'></textarea>
                            </div>
                        </td>
                    </tr>";
        }

        return $tr;
    }


    public static function formatXmlString($xml_data)
    {
        $pattern     = array ('/\s*>/', '/^    /', '/\n    /');
        $replacement = array ('>', '', "\n");

        $char_list = "\t\n\r\0\x0B";
        $xml_data  = trim($xml_data, $char_list);

        $xml_data = preg_replace($pattern, $replacement, $xml_data);

        $xml = new DOMDocument();

        $xml->preserveWhiteSpace = FALSE;
        $xml->formatOutput = TRUE;
        
        $xml_data = "<av_doc_root>".$xml_data."</av_doc_root>";

        $xml->loadXML($xml_data);

        $xml_data = $xml->saveXML($xml->documentElement, LIBXML_NOEMPTYTAG);

        $pattern     = array('/<\?xml version=.*\?>/', '/<\/?av_doc_root>/', '/\-\-\&gt;/');
        $replacement = array('', '', '-->');
       
        $xml_data    = preg_replace($pattern, $replacement, $xml_data);

        return trim($xml_data);
    }


    public static function formatOutput($input, $lk_name)
    {
        // Patch regex DoS possible vuln
        $lk_name = Util::regex($lk_name);

        $pattern = array("/ $lk_name=\".*?\"/", "/<__rootnode>/", "/\<\/__rootnode\>/", "/\<\?xml.*\?\>/");

        $output = preg_replace($pattern, '', $input);

        $output = self::formatXmlString($output);

        $output = preg_replace('/_#_void_value_#_/', '', $output);

        return $output;
    }


    public static function clean_string($string)
    {
        $char_list = "\t\n\r\0\x0B";
        $string  = trim($string, $char_list);
        $string  = Util::htmlentities($string, ENT_QUOTES, 'UTF-8');
        
        return $string;
    }


    // Get hids events from agent
    public static function SIEM_trends_hids($conn, $agent_ip)
    {
        include_once 'panel/sensor_filter.php';

        $data = array();

        $agent_where  = make_sid_filter($conn , $agent_ip);

        if ($agent_where != '')
        {
            $query = "SELECT SUM(a.cnt) AS num_events, day(`day`) AS intervalo, monthname(`day`) AS suf
                FROM alienvault_siem.ac_acid_event a WHERE a.device_id IN ($agent_where)
                AND day BETWEEN '".gmdate("Y-m-d",gmdate("U")-604800)."' AND '".gmdate("Y-m-d")."'
                AND a.plugin_id between 7000 and 7999
                GROUP BY day
                ORDER BY day";

            $rs = $conn->Execute($query);

            if ($rs)
            {
                while (!$rs->EOF)
                {
                    $hours = $rs->fields['intervalo'].' '.substr($rs->fields['suf'], 0, 3);

                    $data[$hours] = $rs->fields['num_events'];

                    $rs->MoveNext();
                }
            }
        }

        return $data;
    }


    public static function thousands_locale()
    {
        $locale    = (isset($_COOKIE['locale']) ? $_COOKIE['locale'] : $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        $languages = explode(',', $locale);
        
        switch($languages[0]) 
        {
            case 'es-es':
            case 'de-de':
            case 'es-mx':
                $thousands = '.';
            break;
            
            default:
                $thousands = ',';
        }
        
        return $thousands;
    }


    public static function plot_graphic($id, $height, $width, $xaxis, $yaxis, $xticks, $xlabel, $display = FALSE) 
    {    
        $min_h = min($yaxis);
        $max_h = max($yaxis);
        
        $max_h = ($min_h == $max_h && $max_h === 0) ? $max_h+1 : $max_h;
            
        $plot = '<script language="javascript" type="text/javascript">
                    $(document).ready(function() {
                        var options = { 
                                lines: { show:true, labelHeight:0, lineWidth: 0.7},
                                points: { show:false, radius: 2 }, legend: { show: false },
                                yaxis: { ticks:[], min:'.$min_h.', max:'.$max_h.' }, 
                                xaxis: { tickDecimals:0, ticks: [';
        
                                    if (sizeof($xticks) > 0) 
                                    {
                                        foreach($xticks as $k => $v) 
                                        {
                                            $plot.= '[' . $v . ',"' . $xlabel[$k] . '"],';
                                        }
                                        
                                        $plot = preg_replace("/\,$/", '', $plot);
                                    }
        
        $plot.= '               ]},
                                grid: { color: "#8E8E8E", labelMargin:0, backgroundColor: "#EDEDED", tickColor: "#D2D2D2", hoverable:true, clickable:true},
                                shadowSize:1 };
        var data = [{
            color: "rgb(170,170,170)", label: "Events", 
            lines: { show: true, fill: true},
            data:[';
        
            foreach($xaxis as $k => $v) 
            {
                $plot.= '[' . $v . ',' . $yaxis[$k] . '],';
            }
        
        $plot = preg_replace("/\,$/", "]", $plot);
        
        $plot.= ' }];';
        $plot.= 'var plotarea = $("#' . $id . '");';
        
        if ($display == TRUE) 
        {
            $plot.= 'plotarea.css("display", "");';
            $width = '((window.innerWidth || document.body.clientWidth)/2)';
        }
        
        $plot.= 'plotarea.css("height", ' . $height . ');
                 plotarea.css("width", ' . $width . ');
                 plotarea.css("margin", "auto");
                 $.plot(plotarea , data, options);';
        
        
        $plot.= 'var previousPoint = null;
                $("#' . $id . '").bind("plothover", function (event, pos, item) {
                    if (item) {
                        if (previousPoint != item.datapoint) {
                            previousPoint = item.datapoint;
                            $("#tooltip").remove();
                            var x = item.datapoint[0].toFixed(0), y = formatNmb(item.datapoint[1].toFixed(0));
                            showTooltip(item.pageX, item.pageY, y + " " + item.series.label);
                        }
                    }
                    else 
                    {
                        $("#tooltip").remove();
                        previousPoint = null;
                    }
                });';
            
        $plot.= "});\n";
        $plot.= '</script>';
        
        return $plot;
    }
}

/* End of file ossec_utilities.inc */
/* Location: ../include/classes/ossec_utilities.inc */