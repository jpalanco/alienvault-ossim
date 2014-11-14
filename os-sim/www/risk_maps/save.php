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

include_once 'riskmaps_functions.php';

Session::logcheck('dashboard-menu', 'BusinessProcesses');

$infolog = array('Indicator Risk Maps');
Log_action::log(49, $infolog);

if (!Session::menu_perms('dashboard-menu', 'BusinessProcessesEdit'))
{
    echo ossim_error(_("You don't have permissions to edit risk indicators"));

    exit();
}


$data = array(
    'status' => 'success',
    'data'   => ''
);


$db   = new ossim_db();
$conn = $db->connect();

$map           = GET('map');
$ri_positions  = GET('data');
$name          = GET('alarm_name');
$icon          = GET('icon');
$url           = GET('url');
$ri_id         = GET('id');
$type          = GET('type');
$type_name     = GET('elem');
$iconbg        = GET('iconbg');
$iconsize      = (GET('iconsize') != '') ? GET('iconsize') : 0;
$noname        = (GET('noname') != '')   ? '#NONAME' : '';

if (!empty($name))
{
    $name = $name.$noname;
}


$icon = str_replace("url_slash","/",$icon);
$icon = str_replace("url_quest","?",$icon);
$icon = str_replace("url_equal","=",$icon);
$url  = str_replace("url_slash","/",$url);
$url  = str_replace("url_quest","?",$url);
$url  = str_replace("url_equal","=",$url);

ossim_valid($map,  OSS_HEX,                                                              'illegal:'._('Map'));
ossim_valid($ri_id,  OSS_DIGIT, OSS_NULLABLE,                                            'illegal:'._('ID'));
ossim_valid($risk_positions, OSS_SCORE, OSS_NULLABLE, OSS_ALPHA,  ";,.",                 'illegal:'._('Risk Indicator Positions'));
ossim_valid($url,  OSS_NULLABLE, OSS_SCORE, OSS_ALPHA, OSS_SPACE, ";,.:\/\?=&()%&",      'illegal:'._('URL'));
ossim_valid($name, OSS_NULLABLE, OSS_SCORE, OSS_ALPHA, OSS_SPACE, ";,.:\/\?=&()%&#",     'illegal:'._('Name'));
ossim_valid($icon, OSS_NULLABLE, OSS_SCORE, OSS_ALPHA, OSS_SPACE, ";,.:\/\?=&()%&",      'illegal:'._('Icon'));
ossim_valid($type, OSS_NULLABLE, OSS_SCORE, OSS_ALPHA, OSS_SPACE, ";,.:\/\?=&()%&",      'illegal:'._('Asset Type'));
ossim_valid($type_name, OSS_NULLABLE, OSS_HEX,                                           'illegal:'._('Asset ID'));
ossim_valid($iconbg, OSS_ALPHA, OSS_NULLABLE,                                            'illegal:'._('Layout Background Color'));
ossim_valid($iconsize, OSS_DIGIT, "-",                                                   'illegal:'._('Icon Size'));



$path = explode("pixmaps", $icon);

if (count($path) > 1)
{
    $icon = "pixmaps".$path[1];
}


if (ossim_error())
{
    $data = array(
        'status' => 'error',
        'data'   => ossim_get_error_clean(),
    );

    echo json_encode($data);
    exit();
}

//Clean bp_asset_member
$query = "DELETE FROM bp_asset_member WHERE member is NULL OR member = 0x0 OR type is NULL OR type =''";

$conn->Execute($query);


$indicators  = array();
$delete_list = array();
$i_enable    = array();

$elems = explode(";", $ri_positions);


//Risk indicator positions
foreach ($elems as $elem)
{
    if (trim($elem) != '')
    {
        $param         = explode(',', $elem);
        $id            = str_replace('rect', '', str_replace('indicator', '', $param[0]));
        $i_enable[$id] = $id;

        $indicators[$id]["x"] = str_replace("px", '', $param[1]);
        $indicators[$id]["y"] = str_replace("px", '', $param[2]);
        $indicators[$id]["w"] = str_replace("px", '', $param[3]);
        $indicators[$id]["h"] = str_replace("px", '', $param[4]);
    }
}


$query  = "SELECT * from risk_indicators WHERE map = UNHEX(?)";
$params = array($map);

$rs = $conn->Execute($query, $params);

if (!$rs)
{
    $data = array(
        'status' => 'error',
        'data'   => _('No Risk Indicators found')
    );

    echo json_encode($data);
    exit();
}

while (!$rs->EOF)
{
    //Update risk indicator positions

    if (array_key_exists($rs->fields['id'], $i_enable) === TRUE)
    {
        $pos = $indicators[$rs->fields['id']];

        $query  = 'UPDATE risk_indicators SET x = ?,y = ?, w = ?, h = ? WHERE id= ?';
        $params = array($pos['x'], $pos['y'], $pos['w'], $pos['h'], $rs->fields['id']);

        $conn->Execute($query, $params);

        // BP ASSET
        if ($rs->fields['type_name'] != '' && $rs->fields['type'] != '')
        {
            $rs->fields['type'] = fix_type($rs->fields['type']);

            $params = array($rs->fields['type_name'],$rs->fields['type']);
            $sql = 'SELECT member, type FROM bp_asset_member WHERE member = UNHEX(?) AND type=?';

            if ($ri = &$conn->Execute($sql, $params))
            {
                if ($ri && $ri->EOF)
                {
                    // check if asset exist
                    $sql = 'INSERT INTO bp_asset_member (member, type) VALUES (UNHEX(?), ?)';
                    $conn->Execute($sql, $params);
                }
            }

            // For net_group insert all related networks
            if ($rs->fields['type'] == 'net_group' || $rs->fields['type'] == 'netgroup')
            {
                $networks = Net_group::get_networks($conn, $rs->fields['type_name']);

                foreach($networks as $network)
                {
                    $sql = 'SELECT member, type FROM bp_asset_member WHERE member = UNHEX(?) AND type=?';

                    $rn = $conn->Execute($sql, array($network->get_net_id(),'net'));

                    if ($rn && $rn->EOF)
                    {
                        $sql = 'INSERT INTO bp_asset_member (member, type) VALUES (UNHEX(?), ?)';

                        $conn->Execute($sql, array($network->get_net_id(),'net'));
                    }
                }
            }
        }
    }
    else
    {
        $delete_list[] = array($rs->fields['id'], $rs->fields['type'], $rs->fields['type_name']);
    }

    $rs->MoveNext();
}




//Update current indicator

$update_status = FALSE;

//Risk indicator has been changed to rectangle
if (empty($name))
{
    $update_status = TRUE;

    $query = "UPDATE risk_indicators set name = ?, url= ?, type = ?, type_name = ?, icon = ?, w = ?, h = ? WHERE id= ?";
    $params = array('rect', $url, '', '', '', 50, 50, $ri_id);

    $conn->Execute($query, $params);

    //Delete old references

    $old_risk_info = get_risk_indicator($conn, $ri_id);

    $query  = "DELETE FROM bp_asset_member WHERE member = UNHEX(?) AND type = ?";
    $params = array($old_risk_info['asset_id'], $old_risk_info['asset_type']);

    $conn->Execute($query, $params);
}
else
{
    $name = (mb_detect_encoding($name." ",'UTF-8,ISO-8859-1') == 'UTF-8') ?  mb_convert_encoding($name, 'ISO-8859-1', 'UTF-8') : $name;

    if($icon != '')
    {
        $icon       = ($iconbg != '' && $iconbg != 'transparent') ? $icon."#".$iconbg : $icon;

        $params     = array($name, $url, $type, $type_name, $iconsize, $icon, $ri_id);
        $icon_query = ", icon= ?";
    }
    else
    {
        $params     = array($name, $url, $type, $type_name, $iconsize, $ri_id);
        $icon_query = '';
    }

    $update_status = FALSE;

    if ($ri_id != '' && $name != '')
    {
        $query = "UPDATE risk_indicators set name= ?, url = ?, type= ?, type_name= ?, size= ? ".$icon_query." WHERE id= ?";
        $conn->Execute($query,$params);

        $update_status = TRUE;
    }
}


foreach ($delete_list as $idb)
{
    $host_types = array("host", "server", "sensor");

    list ($name, $sensor, $type, $ips, $in_assets) = get_assets($conn, $idb[2], $idb[1], $host_types);

    $type = fix_type($type);

    $query  = "DELETE FROM bp_asset_member WHERE member = UNHEX(?) AND type = ?";
    $params = array($name, $type);
    $conn->Execute($query, $params);

    $query = "DELETE FROM risk_indicators WHERE id= ?";
    $conn->Execute($query, array($idb[0]));

    $update_status == TRUE;
}

if($update_status == TRUE)
{
    $data['data'] = 'refresh_indicators();';
}


echo json_encode($data);

shell_exec('/usr/bin/sudo /usr/share/ossim/scripts/framework-restart > /dev/null 2>/dev/null &');

$db->close();