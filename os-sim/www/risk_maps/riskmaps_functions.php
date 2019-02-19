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


//Get risk indicator information
function get_risk_indicator($conn, $ri_id)
{
    Ossim_db::check_connection($conn);

    $query  = "SELECT ri.*, HEX(map) AS map FROM risk_indicators ri WHERE id = ?";
    $params = array($ri_id);

    $conn->SetFetchMode(ADODB_FETCH_ASSOC);
    $rs = $conn->Execute($query, $params);

    $indicator = array();

    if ($rs)
    {
        $indicator = format_indicator($conn, $rs->fields);
    }

    return $indicator;
}


//Standardize risk indicator information
function format_indicator($conn, $ri_data)
{
    $id   = $ri_data['id'];
    $type = ($ri_data['name'] == 'rect') ? 'rectangle' : 'indicator';

    $position = array(
        'x' => $ri_data['x'],
        'y' => $ri_data['y']
    );

    if ($type == 'indicator')
    {
        if (mb_detect_encoding($ri_data['name']." ",'UTF-8,ISO-8859-1') == 'UTF-8')
        {
            $name = $ri_data['name'];
        }
        else
        {
            $name = mb_convert_encoding($ri_data['name'], 'UTF-8', 'ISO-8859-1');
        }

        $url        = $ri_data['url'];
        $asset_type = strtolower($ri_data['type']);
        $asset_id   = $ri_data['type_name'];
        $asset_name = get_indicator_asset_name($conn, $ri_data['type'], $ri_data['type_name']);

        $icon       = preg_replace("/\#.*/","", $ri_data['icon']);
        $icon_size  = $ri_data['size'];
        $icon_bg    = (preg_match("/\#(.+)/", $ri_data['icon'], $bg)) ? $bg[1] : 'transparent';

        $w = ($ri_data['w'] > 60) ? 60 : $ri_data['w'];
        $h = ($ri_data['h'] > 60) ? 60 : $ri_data['h'];

        $size = array(
            'w' => $w,
            'h' => $h
        );

        $indicator = array(
            'id'         => $id,
            'type'       => $type,
            'name'       => $name,
            'asset_type' => $asset_type,
            'asset_name' => $asset_name,
            'asset_id'   => $asset_id,
            'url'        => $url,
            'icon'       => $icon,
            'icon_size'  => $icon_size,
            'icon_bg'    => $icon_bg,
            'position'   => $position,
            'size'       => $size,
        );
    }
    elseif ($type == 'rectangle')
    {
        $name = $ri_data['name'];

        $url = Menu::get_menu_url($ri_data['url'], 'dashboard', 'riskmaps', 'overview');

        $size = array(
            'w' => $ri_data['w'],
            'h' => $ri_data['h']
        );

        $indicator = array(
            'id'       => $id,
            'type'     => $type,
            'name'     => $name,
            'url'      => $url,
            'position' => $position,
            'size'     => $size,
        );
    }

    return $indicator;
}


function get_indicators_from_map($conn, $map)
{
    Ossim_db::check_connection($conn);

    $indicators = array();

    $query  = "SELECT * FROM risk_indicators WHERE map = UNHEX(?)";
    $params = array($map);

    $rs = $conn->Execute($query, $params);

    if (!$rs)
    {
        Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
    }

    while (!$rs->EOF)
    {
        if (is_indicator_allowed($conn, $rs->fields['type'], $rs->fields['type_name']))
        {
            $id = $rs->fields['id'];

            $indicators[$id] = format_indicator($conn, $rs->fields);
        }

        $rs->MoveNext();
    }

    return $indicators;
}

//Draw an indicator
function draw_indicator($conn, $ri_data, $edit_mode = FALSE)
{
    $indicator = '';

    if ($ri_data['type'] == 'indicator')
    {
        $style = "z-index:10;
                  cursor:pointer;
                  visibility:hidden;
                  position:absolute;
                  left:".$ri_data['position']['x']."px;
                  top:".$ri_data['position']['y']."px;
                  height:".$ri_data['size']['h']."px;
                  width:".$ri_data['size']['w']."px;";

        $indicator = '<div id="indicator'.$ri_data['id'].'" class="itcanbemoved" style="'.$style.'">'.
                         draw_html_content($conn, $ri_data, $edit_mode).'
                     </div>';
    }
    elseif ($ri_data['type'] == 'rectangle')
    {
        $style = "border:1px solid transparent;
                  cursor:pointer;
                  background:url(../pixmaps/1x1.png);
                  visibility:hidden;
                  position:absolute;
                  left:".$ri_data['position']['x']."px;
                  top:".$ri_data['position']['y']."px;
                  height:".$ri_data['size']['h']."px;
                  width:".$ri_data['size']['w']."px;";

        $link_to_map = ($edit_mode == FALSE) ? "onclick='document.location.href=\"".$ri_data['url']."\";'" : "";

        $indicator = '<div id="rect'.$ri_data['id'].'" class="itcanbemoved" style="'.$style.'" '.$link_to_map.'>'.
            draw_html_content($conn, $ri_data, $edit_mode).'
        </div>';
    }

    return $indicator;
}


function draw_html_content($conn, $ri_data, $edit_mode = FALSE)
{
    $ri_html = '';

    if ($ri_data['type'] == 'indicator')
    {
        //Allowed host types
        $host_types = array('host', 'server', 'sensor');

        //Getting indicator values
        if (preg_match("/view\.php\?map\=([a-fA-F0-9]*)/", $ri_data['url'], $found))
        {
            // Linked to another map: loop by this map indicators

            list ($r_value, $v_value, $a_value, $ri_data['asset_id'], $related_sensor, , $ips, $in_assets) = get_map_values($conn, $found[1], $ri_data['asset_id'], $ri_data['asset_type'], $host_types);
        }
        else
        {
            // Asset Values
            list (, $related_sensor, , $ips, $in_assets) = get_assets($conn, $ri_data['asset_id'], $ri_data['asset_type'], $host_types);
            list ($r_value, $v_value, $a_value)          = get_values($conn, $host_types, $ri_data['asset_type'], $ri_data['asset_id'], FALSE);
        }

        // Getting indacator links
        if ($edit_mode == TRUE)
        {
            $linked_url  = "javascript:void(0);";
            $r_url       = "javascript:void(0);";
            $v_url       = "javascript:void(0);";
            $a_url       = "javascript:void(0);";
        }
        else
        {
            // Risk link
            $alarm_query = '';
            if ($ri_data['asset_type'] == 'host')
            {
                $alarm_query .= "&host_id=".$ri_data['asset_id'];
            }
            elseif ($ri_data['asset_type'] == 'net')
            {
                $alarm_query .= "&net_id=".$ri_data['asset_id'];
            }
            elseif ($ri_data['asset_type'] == 'sensor')
            {
                $alarm_query .= "&sensor_query=".$ri_data['asset_id'];
            }
            elseif ($ri_data['asset_type'] == 'host_group' || $ri_data['asset_type'] == 'hostgroup')
            {
                $alarm_query .= "&asset_group=".$ri_data['asset_id'];
            }
            
            $r_url = Menu::get_menu_url("/ossim/alarm/alarm_console.php?hide_closed=1".$alarm_query, 'analysis', 'alarms', 'alarms');
            
            // Vulnerability link
            if ($ri_data['asset_type'] == 'host_group' || $ri_data['asset_type'] == 'hostgroup')
            {
                $v_data = '';

                if (valid_hex32($ri_data['asset_id']))
                {
                    $_group_object = Asset_group::get_object($conn, $ri_data['asset_id']);

                    if ($_group_object != NULL)
                    {
                        $_assets_aux = $_group_object->get_hosts($conn, '', array(), TRUE);

                        foreach ($_assets_aux[0] as $_host_data)
                        {
                            if ($v_data != '')
                            {
                                $v_data .= ',';
                            }

                            $v_data .= $_host_data[2]; // IP
                        }
                    }
                }
            }
            else
            {
                $v_data = $ips;
            }

            $v_url = Menu::get_menu_url("/ossim/vulnmeter/index.php?value=$v_data&type=hn", 'environment', 'vulnerabilities', 'overview');


            // Availability link

            if (!empty($related_sensor))
            {
                $conf = $GLOBALS['CONF'];
                $conf = (!$conf) ? new Ossim_conf() : $conf;

                $nagios_link    = $conf->get_conf('nagios_link');
                $scheme         = (empty($_SERVER['HTTPS']))        ? 'http://' : 'https://';
                $path           = (!empty($nagios_link))            ? $nagios_link : '/nagios3/';
                $port           = (!empty($_SERVER['SERVER_PORT'])) ? ':'.$_SERVER['SERVER_PORT'] : "";

                $nagios_url     = $scheme.$related_sensor.$port.$path;

                if ($ri_data['asset_type'] == 'host')
                {
                    $hostname = Asset_host::get_name_by_id($conn, $ri_data['asset_id']);

                    if (preg_match('/\,/', $ips))
                    {
                        $hostname .= '_'.preg_replace('/\,.*/', '', $ips);
                    }

                    $a_url = Menu::get_menu_url("/ossim/nagios/index.php?sensor=$related_sensor&nagios_link=".urlencode($nagios_url."cgi-bin/status.cgi?host=".$hostname), 'environment', 'availability');
                }
                else
                {
                    $a_url = Menu::get_menu_url("/ossim/nagios/index.php?sensor=$related_sensor&nagios_link=".urlencode($nagios_url."cgi-bin/status.cgi?hostgroup=all"), 'environment', 'availability');
                }
            }
            else
            {
                $a_url = 'javascript:void(0);';
            }


            //Report link or map link

            if ($ri_data['url'] == 'REPORT')
            {
                $linked_url = "javascript:void(0);";

                if ($ri_data['asset_type'] == 'sensor')
                {
                    try
                    {
                        //Special case 1: Sensors don't have detail view

                        $sensor_ip = Av_sensor::get_ip_by_id($conn, $ri_data['asset_id']);

                        if (Asset_host_ips::valid_ip($sensor_ip))
                        {
                            $filters = array('where' => "host.id = hi.host_id AND hi.ip = INET6_ATON('$sensor_ip')
                                AND hi.host_id = hs.host_id AND hs.sensor_id = UNHEX('".$ri_data['asset_id']."')"
                            );

                            list($hosts, $total) = Asset_host::get_list($conn, ', host_sensor_reference hs, host_ip hi', $filters);

                            if ($total == 1)
                            {
                                $ri_data['asset_id'] = key($hosts);

                                $linked_url = Menu::get_menu_url("/ossim/av_asset/common/views/detail.php?asset_id=".$ri_data['asset_id'], 'environment', 'assets', 'assets');
                            }
                            elseif ($total > 1)
                            {
                                $linked_url = Menu::get_menu_url("/ossim/av_asset/asset/index.php?filter_id=11&filter_value=$sensor_ip", 'environment', 'assets', 'assets');
                            }
                        }
                    }
                    catch(Exception $e)
                    {

                    }
                }
                elseif ($ri_data['asset_type'] == 'net_group' || $ri_data['asset_type'] == 'netgroup')
                {
                    //Special case 2: Net groups don't have detail view

                    $_sm_option = 'assets';
                    $_h_option  = 'network_groups';

                    $linked_url = Menu::get_menu_url("/ossim/netgroup/netgroup_form.php?id=".$ri_data['asset_id'], 'environment', $_sm_option, $_h_option);
                }
                else
                {
                    if ($ri_data['asset_type'] == 'host')
                    {
                        $_sm_option = 'assets';
                        $_h_option  = 'assets';
                    }
                    elseif ($ri_data['asset_type'] == 'host_group' || $ri_data['asset_type'] == 'hostgroup')
                    {
                        $_sm_option = 'assets';
                        $_h_option  = 'asset_groups';
                    }
                    else
                    {
                        $_sm_option = 'assets';
                        $_h_option  = 'networks';
                    }

                    $linked_url = Menu::get_menu_url("/ossim/av_asset/common/views/detail.php?asset_id=".$ri_data['asset_id'], 'environment', $_sm_option, $_h_option);
                }
            }
            else
            {
                $linked_url = ($ri_data['url'] != '') ? Menu::get_menu_url($ri_data['url'], 'dashboard', 'riskmaps', 'overview') : "javascript:void(0);";
            }
        }

        //Special image when linked asset has been removed
        if ($ri_data['asset_type'] != '' && !$in_assets)
        {
            $ri_data['icon']      = "/ossim/pixmaps/marker--exclamation.png";
            $ri_data['icon_size'] = "16";
            $ri_data['icon_bg']   = 'transparent';
        }

        $ri_data['icon_size'] = ($ri_data['icon_size'] >= 0 || $ri_data['icon_size'] == -1) ? $ri_data['icon_size'] : '';


        $ri_html .= "<input type='hidden' name='dataname".$ri_data['id']."' id='dataname".$ri_data['id']."' value='".$ri_data['name']."'/>
                     <input type='hidden' name='datatype".$ri_data['id']."' id='datatype".$ri_data['id']."' value='".$ri_data['asset_type']."'/>
                     <input type='hidden' name='type_name".$ri_data['id']."' id='type_name".$ri_data['id']."' value='".$ri_data['asset_id']."'/>
                     <input type='hidden' name='type_name_show".$ri_data['id']."' id='type_name_show".$ri_data['id']."' value='".$ri_data['asset_name']."'/>
                     <input type='hidden' name='dataurl".$ri_data['id']."' id='dataurl".$ri_data['id']."' value='".$ri_data['url']."'/>
                     <input type='hidden' name='dataicon".$ri_data['id']."' id='dataicon".$ri_data['id']."' value='".$ri_data['icon']."'/>
                     <input type='hidden' name='dataiconsize".$ri_data['id']."' id='dataiconsize".$ri_data['id']."' value='".$ri_data['icon_size']."'/>
                     <input type='hidden' name='dataiconbg".$ri_data['id']."' id='dataiconbg".$ri_data['id']."' value='".$ri_data['icon_bg']."'/>";

        $ri_html .= '<table width="100%" border="0" cellspacing="0" cellpadding="1" style="padding:2px; background-color:'.$ri_data['icon_bg'].'; text-align:center; margin-left:2px; margin-right:2px">';

        if (!preg_match("/#NONAME/", $ri_data['name']))
        {
            $ri_html .= '<tr>
                            <td align="center" nowrap="nowrap">
                                <a href="'.$linked_url.'" class="ne"><i>'.$ri_data['name'].'</i></a>
                            </td>
                        </tr>';
        }

        if ($ri_data['icon_size'] != -1)
        {
            $ri_data['icon_size'] = ($ri_data['icon_size'] > 0) ? 'width="'.$ri_data['icon_size'].'"' : '';

            $ri_html .= '<tr>
                            <td align="center" style="white-space: nowrap;">
                                <a href="'.$linked_url.'" class="ne">
                                    <img src="'.$ri_data['icon'].'" '.$ri_data['icon_size'].' border="0"/>
                                </a>
                            </td>
                        </tr>';
        }

        $ri_html .= '<tr align="center">
                        <td style="margin-left:2px; margin-right:2px">';

        if($ri_data['icon_size'] == -1 && preg_match("/#NONAME/", $ri_data['name']))
        {
            $ri_html .= '<table border="0" cellspacing="0" cellpadding="2" style="text-align:center; margin:auto;">
                            <tr>
                                <td><a class="ne11" href="'.$r_url.'"><img src="images/'.$r_value.'.gif" border="0"/></a></td>
                                <td><a class="ne11" href="'.$v_url.'"><img src="images/'.$v_value.'.gif" border="0"/></a></td>
                                <td><a class="ne11" href="'.$a_url.'"><img src="images/'.$a_value.'.gif" border="0"/></a></td>
                            </tr>
                        </table>';
        }
        else
        {
            $ri_html .= '
                <table border="0" cellspacing="0" cellpadding="2" style="text-align:center; margin:auto;">
                    <tr>
                        <td><a class="ne11" href="'.$r_url.'">R</a></td>
                        <td><a class="ne11" href="'.$v_url.'">V</a></td>
                        <td><a class="ne11" href="'.$a_url.'">A</a></td>
                    </tr>
                    <tr>
                        <td><img src="images/'.$r_value.'.gif" border="0"/></td>
                        <td><img src="images/'.$v_value.'.gif" border="0"/></td>
                        <td><img src="images/'.$a_value.'.gif" border="0"/></td>
                    </tr>
                </table>';
        }

        $ri_html .= '   </td>
                     </tr>';

        if($edit_mode == TRUE)
        {
            $ri_html .= '
                <tr align="center">
                    <td class="noborder">
                        <div id="indicator_edit"  style="float:left;" onclick="load_indicator_info(this);">
                            <img src="images/edit.png" title="'._("Edit Indicator").'" class="ind_help" height="15px" border="0"/>
                        </div>
                        <div id="indicator_trash" style="float:right;" onclick="delete_indicator(this);">
                            <img src="../pixmaps/trash.png" title="'._("Delete Indicator").'" class="ind_help" height="15px" border="0"/>
                        </div>
                    </td>
            </tr>';
        }

        $ri_html .= '</table>';
    }
    elseif ($ri_data['type'] == 'rectangle')
    {
        $ri_html = "<input type='hidden' name='dataname".$ri_data['id']."' id='dataname".$ri_data['id']."' value='".$ri_data['name']."'/>\n
                    <input type='hidden' name='dataurl".$ri_data['id']."' id='dataurl".$ri_data['id']."' value='".$ri_data['url']."'/>\n";

        if ($edit_mode == TRUE)
        {
            $ri_html .= '<div class="itcanberesized" style="position:absolute; bottom:0px; right:0px; cursor:nw-resize;">
                            <img src="../pixmaps/resize.gif" border="0"/>
                         </div>';
        }

        $ri_html .= '<table border="0" cellspacing="0" cellpadding="0" width="100%" height="100%" style="border:0px;">
                         <tr>
                             <td style="border:1px dotted black" valign="bottom">';

        if ($edit_mode == TRUE)
        {
            $ri_html .= '<div id="indicator_edit"  style="float:left;padding:2px;" onclick="load_indicator_info(this);">
                            <img src="images/edit.png" title="'._("Edit Rectangle").'" class="ind_help" height="15px" border="0"/>
                        </div>
                        <div id="indicator_trash" style="float:right;padding:2px;" onclick="delete_indicator(this);">
                            <img src="../pixmaps/trash.png" title="'._("Delete Rectangle").'" class="ind_help" height="15px" border="0"/>
                        </div>';
        }

        $ri_html .= '       </td>
                        </tr>
                    </table>';
    }

    return $ri_html;
}


/**
 * This function checks if a map physically exists in the system
 *
 * @param  string $map  Map ID
 *
 * @return boolean
 */
function map_exists($map)
{
    $filename = "maps/map$map.jpg";

    if(file_exists($filename))
    {
        return TRUE;
    }

    return FALSE;
}


/**
 * This function gets the first allowed map available
 *
 * @param  object $conn  Database access object
 *
 * @return string
 */
function get_first_map_available($conn)
{
    $map = NULL;

    Ossim_db::check_connection($conn);

    $query  = "SELECT HEX(map) AS map, perm, name FROM risk_maps";
    $rs     = $conn->Execute($query);

    while (!$rs->EOF)
    {
        if(file_exists("maps/map" . $rs->fields['map'] . ".jpg") && is_map_allowed($rs->fields['perm']))
        {
            $map = $rs->fields['map'];

            break;
        }

        $rs->MoveNext();
    }

    return $map;
}


//Function to check if an user or entity has permission to see a map.
function is_map_allowed($perm)
{
    //If I am an admin user or permission is 0
    if (Session::am_i_admin() || $perm == '0')
    {
       return TRUE;
    }

    $ret = FALSE;

    if(strlen($perm) > 0)
    {
        // ENTITY
        if (valid_hex32($perm))
        {
            if (Session::is_pro() && $_SESSION['_user_vision']['entity'][$perm])
            {
                $ret = TRUE;
            }
        } // USER
        elseif (Session::get_session_user() == $perm)
        {
            $ret = TRUE;
        }
    }

    return $ret;
}


//Function to check if an user or entity has permission to edit a map.
function can_i_edit_maps($conn, $perm)
{
    //If I am an admin user, return true
    if (Session::am_i_admin() || $perm == '0')
    {
       return TRUE;
    }

    $ret = FALSE;

    if(strlen($perm) > 0)
    {
        // ENTITY
        //If the user is the admin of the entity, then it can edit the map. return true.
        if (valid_hex32($perm) && Session::is_pro())
        {
            $aux = Acl::get_entities_managed_by_user($conn, Session::get_session_user());

            if($aux[0][$perm])
            {
                $ret = TRUE;
            }

        } // USER
        elseif (Session::get_session_user() == $perm)
        {
            $ret = TRUE;
        }
    }

    return $ret;
}


//Function to check if an user or entity has permission to edit a map.
function is_map_editable($conn, $id)
{
    //If I am an admin user, return true
    if (Session::am_i_admin())
    {
        return TRUE;
    }


    $query  = "SELECT perm FROM risk_maps where map = UNHEX(?)";
    $params = array($id);

    $result = $conn->Execute($query, $params);

    if(!$result->EOF)
    {
        $perm = $result->fields['perm'];
    }

    if($perm == '')
    {
        return FALSE;
    }

    $ret = FALSE;

    if(strlen($perm) > 0)
    {
        // ENTITY
        //If the user is the admin of the entity, then it can edit the map. return true.
        if (valid_hex32($perm) && Session::is_pro())
        {
            $aux = Acl::get_entities_managed_by_user($conn,Session::get_session_user());

            if($aux[0][$perm])
            {
                $ret = TRUE;
            }
        } // USER
        elseif (Session::get_session_user() == $perm)
        {
            $ret = TRUE;
        }
    }

    return $ret;
}


function is_indicator_allowed($conn, $type, $asset_id)
{
    $has_perm = 1;

    if (Session::am_i_admin())
    {
        return $has_perm;
    }

    if ($type == 'host')
    {
        $has_perm = Session::hostAllowed($conn,$asset_id);
    }
    elseif ($type == 'sensor' || $type == 'server')
    {
        $has_perm = Session::sensorAllowed($asset_id);
    }
    elseif ($type == 'net')
    {
        $has_perm = Session::netAllowed($conn,$asset_id);
    }
    elseif ($type == 'host_group' || $type == 'hostgroup')
    {
        $has_perm = Session::groupHostAllowed($conn,$asset_id);
    }
    elseif ($type == 'net_group' || $type == 'netgroup')
    {
        $has_perm = Session::groupAllowed($conn,$asset_id);
    }

    return $has_perm;
}


function get_indicator_asset_name($conn, $type, $asset_id)
{
    $name = '';

    switch($type)
    {
        case 'host':
            $name = Asset_host::get_name_by_id($conn, $asset_id);
        break;

        case 'net':
            $name = Asset_net::get_name_by_id($conn, $asset_id);
        break;

        case 'hostgroup':
        case 'host_group':
            $name = Asset_group::get_name_by_id($conn, $asset_id);
        break;

        case 'net_group':
        case 'netgroup':
            $name = Net_group::get_name_by_id($conn, $asset_id);
        break;

        case 'sensor':
            $name = Av_sensor::get_name_by_id($conn, $asset_id);
        break;
     }

     $name = (empty($name)) ? _('Unknown') : $name;

     return $name;
}


// convert risk value into risk semaphore
function get_value_by_digit($digit)
{
    $digit = intval($digit);

    if ($digit > 7)
    {
        return 'r';
    }
    elseif($digit > 3)
    {
        return 'a';
    }
    elseif($digit >= 0)
    {
        return 'v';
    }
    else
    {
        return 'b';
    }
}


// asset value in BBDD?
function is_in_assets($conn, $name, $type)
{
    if ($type == 'host')
    {
        $sql = "SELECT * FROM host WHERE id = UNHEX('$name')";
    }
    elseif ($type == 'sensor')
    {
        $sql = "SELECT * FROM sensor WHERE id = UNHEX('$name')";
    }
    elseif ($type == 'net')
    {
        $sql = "SELECT * FROM net WHERE id = UNHEX('$name')";
    }
    elseif ($type == 'host_group' || $type == 'hostgroup')
    {
        $sql = "SELECT * FROM host_group WHERE id = UNHEX('$name')";
    }
    elseif ($type == 'net_group' || $type == 'netgroup')
    {
        $sql = "SELECT * FROM net_group WHERE id = UNHEX('$name')";
    }

    $result = $conn->Execute($sql);

    return (!$result->EOF) ? 1 : 0;
}


// change type with right bp_asset_member value
function fix_type($type)
{
    if ($type == 'sensor' || $type == 'server')
    {
        $type = 'host';
    }
    elseif ($type == 'netgroup')
    {
        $type = 'net_group';
    }
    elseif ($type == 'hostgroup')
    {
        $type = 'host_group';
    }

    return $type;
}


// get asset name, value and sensor
function get_assets($conn, $id, $type, $host_types)
{
    $filters = array(
        'where'    => 'sensor_properties.has_nagios = 1',
        'order_by' => 'priority desc'
    );

    list($nagios_list, $nagios_total) = Av_sensor::get_list($conn, $filters);


    $sensor  = NULL;

    $sensors = array();
    $type    = strtolower($type);
    $id      = strtoupper($id);

    // in_assets first
    $in_assets = is_in_assets($conn, $id, $type);

    //Host, sensor or server
    if(in_array($type, $host_types))
    {
        $table = $type;

        if($type == 'host')
        {
            $what  = 'host_id';
            $table = 'host_ip';
        }
        else
        {
            $what = 'id';
        }

        $query  = "SELECT INET6_NTOA(ip) AS ip FROM $table WHERE $what = UNHEX(?) LIMIT 1";
        $params = array($id);

        $rs = $conn->Execute($query, $params);

        if ($rs)
        {
            $ips = $rs->fields['ip'];
        }

        // Related sensors
        if ($type == 'host')
        {
            $sensors = Asset_host_sensors::get_sensors_by_id($conn, $id);
        }
        elseif ($type == 'sensor')
        {
            $sensors[$id] = $ips;
        }
        else
        {
            $s_id = Av_sensor::get_id_by_ip($conn, Util::get_default_admin_ip());

            $sensors[$s_id] = Util::get_default_admin_ip();
        }
    }
    elseif ($type == 'net')
    {
        $query  = "SELECT ips FROM net WHERE id = UNHEX(?)";
        $params = array($id);

        $rs = $conn->Execute($query, $params);

        if ($rs)
        {
            $ips = $rs->fields['ips'];
        }

        // Related sensors
        $sensors = Asset_net_sensors::get_sensors_by_id($conn, $id);
    }
    elseif ($type == 'net_group' || $type == 'netgroup')
    {
        $query  = "SELECT hex(ngr.net_id) as net_id, n.ips FROM net_group_reference AS ngr, net AS n
            WHERE ngr.net_group_id = UNHEX(?) AND ngr.net_id = n.id";

        $net_ids = array($id);
        $params  = $net_ids;

        $rs = $conn->Execute($query, $params);

        if ($rs)
        {
            $ipng = array();

            if (!$rs->EOF)
            {
                $net_ids = array();
            }

            while (!$rs->EOF)
            {
                $ipng[]    = $rs->fields['ips'];
                $net_ids[] = $rs->fields['net_id'];

                $rs->MoveNext();
            }

            $ips = (count($ipng) > 0) ? implode(",", $ipng) : "'0.0.0.0/0'";

            if (count($ipng) == 0 )
            {
                $in_assets = 0;
            }
        }
        // Related sensors
        foreach ($net_ids as $net_id)
        {
            $_sensors_aux = Asset_net_sensors::get_sensors_by_id($conn, $net_id);

            foreach ($_sensors_aux as $sensor_id => $sensor_data)
            {
                $sensors[$sensor_id] = $sensor_data['ip'];
            }
        }
    }
    elseif ($type == 'host_group' || $type == 'hostgroup')
    {
        $query = "SELECT hex(hg.host_id) as host_id, INET6_NTOA(hi.ip) AS ip FROM host_group_reference hg, host_ip hi
            WHERE hi.host_id=hg.host_id AND hg.host_group_id = UNHEX(?)";

        $host_ids = array($id);
        $params   = $host_ids;

        $rs = $conn->Execute($query, $params);

        if ($rs)
        {
            $iphg = array();

            if (!$rs->EOF)
            {
                $host_ids = array();
            }

            while (!$rs->EOF)
            {
                $iphg[]     = "'".$rs->fields['ip']."'";
                $host_ids[] = $rs->fields['host_id'];

                $rs->MoveNext();
            }

            $ips = (count($iphg) > 0) ? implode(',', $iphg) : "'0.0.0.0'";

            if (count($iphg) == 0)
            {
                $in_assets = 0;
            }
        }

        // Related sensors
        foreach ($host_ids as $host_id)
        {
            $_sensors_aux = Asset_host_sensors::get_sensors_by_id($conn, $host_id);

            foreach ($_sensors_aux as $sensor_id => $sensor_data)
            {
                $sensors[$sensor_id] = $sensor_data['ip'];
            }
        }
    }

    //Getting first Nagios sensor (By priority)

    if ($nagios_total > 0)
    {
        foreach ($nagios_list as $n_sensor_id => $n_sensor_data)
        {
            if (array_key_exists($n_sensor_id, $sensors))
            {
                $sensor = $n_sensor_data['ip'];
                break;
            }
        }
    }

    return array($id, $sensor, $type, $ips, $in_assets);
}


// Get asset risk values
function get_values($conn, $host_types, $type, $name, $only_values = FALSE)
{
    if ($only_values)
    {
        $r_value = -1;
        $v_value = -1;
        $a_value = -1;
    }
    else
    {
        $r_value = 'b';
        $v_value = 'b';
        $a_value = 'b';
    }

    $params = array($name);

    if (in_array($type, $host_types))
    {
        $query = "SELECT bp_member_status.severity,bp_asset_member.member
            FROM bp_member_status,bp_asset_member
            WHERE bp_member_status.member_id=bp_asset_member.member
            AND bp_asset_member.member = UNHEX(?)
            AND bp_member_status.measure_type = \"host_metric\"";
    }
    elseif ($type == 'host_group' || $type == 'hostgroup')
    {
        $query = "SELECT bp_member_status.severity,bp_asset_member.member
            FROM bp_member_status,bp_asset_member
            WHERE bp_member_status.member_id=bp_asset_member.member
            AND bp_asset_member.member = UNHEX(?)
            AND bp_member_status.measure_type = \"host_group_metric\"";
    }
    elseif ($type == 'net_group' || $type == 'netgroup')
    {
        $query = "SELECT bp_member_status.severity,bp_asset_member.member
            FROM bp_member_status,bp_asset_member
            WHERE bp_member_status.member_id=bp_asset_member.member
            AND bp_asset_member.member = UNHEX(?)
            AND bp_member_status.measure_type = \"net_group_metric\"";
    }
    else
    {
        $query = "SELECT bp_member_status.severity,bp_asset_member.member
            FROM bp_member_status,bp_asset_member
            WHERE bp_member_status.member_id=bp_asset_member.member
            AND bp_asset_member.member = UNHEX(?)
            AND bp_member_status.measure_type = \"net_metric\"";
    }

    $rs = $conn->Execute($query, $params);

    if (!$rs)
    {
        Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
    }
    else
    {
        if ($only_values)
        {
            $r_value = ($rs->fields['severity'] == '') ? -1 : intval($rs->fields['severity']);
        }
        else
        {
            $r_value = get_value_by_digit($rs->fields['severity']);
        }
    }


    if (in_array($type, $host_types))
    {
        $query = "SELECT bp_member_status.severity,bp_asset_member.member
            FROM bp_member_status,bp_asset_member
            WHERE bp_member_status.member_id=bp_asset_member.member
            AND bp_asset_member.member = UNHEX(?)
            AND bp_member_status.measure_type = \"host_vulnerability\"";
    }
    elseif ($type == 'host_group' || $type == 'hostgroup')
    {
        $query = "SELECT bp_member_status.severity,bp_asset_member.member
            FROM bp_member_status,bp_asset_member
            WHERE bp_member_status.member_id=bp_asset_member.member
            AND bp_asset_member.member = UNHEX(?)
            AND bp_member_status.measure_type = \"host_group_vulnerability\"";
    }
    elseif ($type == 'net_group' || $type == 'netgroup')
    {
        $query = "SELECT bp_member_status.severity,bp_asset_member.member
            FROM bp_member_status,bp_asset_member
            WHERE bp_member_status.member_id=bp_asset_member.member
            AND bp_asset_member.member = UNHEX(?)
            AND bp_member_status.measure_type = \"net_group_vulnerability\"";
    }
    else
    {
        $query = "SELECT bp_member_status.severity,bp_asset_member.member
            FROM bp_member_status,bp_asset_member
            WHERE bp_member_status.member_id=bp_asset_member.member
            AND bp_asset_member.member = UNHEX(?)
            AND bp_member_status.measure_type = \"net_vulnerability\"";
    }

    $rs = $conn->Execute($query, $params);

    if (!$rs)
    {
        Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
    }
    else
    {
        if ($only_values)
        {
            $v_value = ($rs->fields['severity'] == '') ? -1 : intval($rs->fields['severity']);
        }
        else
        {
            $v_value = get_value_by_digit($rs->fields['severity']);
        }
    }

    if (in_array($type, $host_types))
    {
        $query = "select bp_member_status.severity,bp_asset_member.member
            FROM bp_member_status,bp_asset_member
            WHERE bp_member_status.member_id=bp_asset_member.member
            AND bp_asset_member.member = UNHEX(?)
            AND bp_member_status.measure_type = \"host_availability\"";
    }
    elseif ($type == 'host_group' || $type == 'hostgroup')
    {
        $query = "select bp_member_status.severity,bp_asset_member.member
            FROM bp_member_status,bp_asset_member
            WHERE bp_member_status.member_id=bp_asset_member.member
            AND bp_asset_member.member = UNHEX(?)
            AND bp_member_status.measure_type = \"host_group_availability\"";
    }
    elseif ($type == 'net_group' || $type == 'netgroup')
    {
        $query = "select bp_member_status.severity,bp_asset_member.member
            FROM bp_member_status,bp_asset_member WHERE bp_member_status.member_id=bp_asset_member.member
            AND bp_asset_member.member = UNHEX(?)
            AND bp_member_status.measure_type = \"net_group_availability\"";
    }
    else
    {
        $query = "select bp_member_status.severity,bp_asset_member.member
            FROM bp_member_status,bp_asset_member
            WHERE bp_member_status.member_id=bp_asset_member.member
            AND bp_asset_member.member = UNHEX(?)
            AND bp_member_status.measure_type = \"net_availability\"";
    }

    $rs = $conn->Execute($query, $params);

    if (!$rs)
    {
        Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
    }
    else
    {
        if ($only_values)
        {
            $a_value = ($rs->fields['severity'] == '') ? -1 : intval($rs->fields['severity']);
        }
        else
        {
            $a_value = get_value_by_digit($rs->fields['severity']);
        }
    }

    return array($r_value, $v_value, $a_value);
}


// Get all mesure objects in recursive maps
function get_map_objects($conn, $map, $map_array = array(), $obj_array = array())
{
    $map_array[$map]++;
    $query = "select * from risk_indicators where name <> 'rect' AND map = UNHEX(?)";

    $rs4 = $conn->Execute($query, array($map));

    if (!$rs4)
    {
        Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
    }
    else
    {
        while (!$rs4->EOF)
        {
            //It's a map
            if (preg_match("/view\.php\?map\=([a-fA-F0-9]*)/", $rs4->fields['url'], $found))
            {
                if (!$map_array[$found[1]]) // Only if not already visited
                {
                    list ($map_array, $obj_array) = get_map_objects($conn, $found[1], $map_array, $obj_array);
                }
            }
            else
            {
                if (!$obj_array[$rs4->fields['id']])
                {
                    $obj_array[$rs4->fields['id']] = $rs4->fields;
                }
            }

            $rs4->MoveNext();
        }
    }

    return array($map_array, $obj_array);
}


// get risk values for linked map (recursive)
function get_map_values($conn, $map, $name, $type, $host_types)
{
    $r_value_max  = -1;
    $v_value_max  = -1;
    $a_value_max  = -1;

    $r_value_aux  = -1;
    $v_value_aux  = -1;
    $a_value_aux  = -1;

    $sensor       = '';
    $ips          = $name;

    $in_assets    = 0;

    list ($map_array, $obj_map) = get_map_objects($conn, $map);

    foreach ($obj_map as $object)
    {
        list ($name, $sensor, $type, $ips, $in_assets) = get_assets($conn, $object['type_name'], $object['type'], $host_types);

        list ($r_value_aux, $v_value_aux, $a_value_aux) = get_values($conn, $host_types, $object['type'], $name, TRUE);

        if ($r_value_aux > $r_value_max)
        {
            $r_value_max = $r_value_aux;
        }

        if ($v_value_aux > $v_value_max)
        {
            $v_value_max = $v_value_aux;
        }

        if ($a_value_aux > $a_value_max)
        {
            $a_value_max = $a_value_aux;
        }
    }

    $r_value = get_value_by_digit($r_value_max);
    $v_value = get_value_by_digit($v_value_max);
    $a_value = get_value_by_digit($a_value_max);

    return array($r_value, $v_value, $a_value, $name, $sensor, $type, $ips, $in_assets);
}


/**
 * This function returns the current map selected by the user
 *
 * @param  object $conn  Database access object
 *
 * @return string
 */
function get_current_map($conn)
{
    $map = '';

    if (GET('back_map') != '')
    {
        $map = GET('back_map');
    }
    elseif (POST('map') != '')
    {
        $map = POST('map');
    }
    elseif (GET('map') != '')
    {
        $map = GET('map');
    }
    elseif ($_SESSION['riskmap'] != '')
    {
        $map = $_SESSION['riskmap'];
    }
    else
    {
        $config = new User_config($conn);
        $user   = Session::get_session_user();
        $map    = $config->get($user, 'riskmap', 'simple', 'main');

        if (empty($map))
        {
            //No default map selected, we get the first available map
            $map = get_first_map_available($conn);
        }
    }

    return $map;
}
