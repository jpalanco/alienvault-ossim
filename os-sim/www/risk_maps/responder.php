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
require_once 'riskmaps_functions.php';

Session::logcheck('dashboard-menu', 'BusinessProcesses');

if (!Session::menu_perms('dashboard-menu', 'BusinessProcessesEdit'))
{
    echo ossim_Error(_("You don't have permissions to edit risk indicators"));
    exit();
}

$db     = new ossim_db();
$conn   = $db->connect();

$map    = GET('map');
$type   = GET('type');
$url    = (empty($_GET['url_data']))       ? ''         : GET("url_data");
$url    = ($url == '' && GET('url') != '') ? GET('url') : $url;

$nolink = intval(GET('nolinks'));

ossim_valid($map,   OSS_HEX,                                'illegal:'._('Map'));
ossim_valid($type,  OSS_NULLABLE, OSS_ALPHA, OSS_SCORE,     'illegal:'._('Type'));

if (!empty($url) && $url != 'REPORT')
{
    ossim_valid($url, OSS_SCORE, OSS_DOT, OSS_ALPHA, OSS_DIGIT,'REPORT','\/=%\.\?', 'illegal:'._('Url'));
}

if ($type != 'rect')
{
    $chosen_icon  = GET("chosen_icon");
    $chosen_icon  = str_replace("url_slash","/", $chosen_icon);
    $chosen_icon  = str_replace("url_quest","?", $chosen_icon);
    $chosen_icon  = str_replace("url_equal","=", $chosen_icon);


    $asset_type   = GET('asset_type');
    $asset_id     = GET("elem");
    $alarm_name   = utf8_decode(GET("alarm_name"));
    $iconbg       = GET('iconbg');
    $iconsize     = (GET('iconsize') != '' ) ? GET('iconsize') : 0;
    $noname       = (GET('noname') != '')    ? "#NONAME"       : '';
    $name_show    = utf8_decode(GET("name_show"));

    ossim_valid($chosen_icon, OSS_NULLABLE, OSS_SCORE, OSS_ALPHA, OSS_DIGIT, OSS_SPACE, ";,.:\/\?=&()%&", 'illegal:'._('Icon'));
    ossim_valid($asset_type , OSS_NULLABLE, OSS_ALPHA, OSS_SCORE,                                         'illegal:'._('Asset Type'));
    ossim_valid($asset_id   , OSS_HEX,OSS_NULLABLE,                                                       'illegal:'._('Asset ID'));
    ossim_valid($alarm_name , OSS_NULLABLE, OSS_ALPHA, OSS_PUNC_EXT, '#',                                 'illegal:'._('Alarm name'));
    ossim_valid($iconbg     , OSS_ALPHA, OSS_NULLABLE,                                                    'illegal:'._('Icon Background'));
    ossim_valid($iconsize   , OSS_DIGIT, '-',                                                             'illegal:'._('Icon size'));
    ossim_valid($name_show  , OSS_NULLABLE, OSS_TEXT, OSS_PUNC_EXT,                                       'illegal:'._('Asset Name'));
    
    $alarm_name = $alarm_name.$noname;
}


if (ossim_error())
{
    echo ossim_get_error_clean();
    exit();
}

if ($type != "rect" && strtolower($alarm_name) == 'rect')
{
    echo _("'Rect' is a reserved word.  Please, use another name");
    exit();
}


if ($type == 'rect')
{
    $sql    = "INSERT INTO risk_indicators (name,map,url,type,type_name,icon,x,y,w,h) VALUES ('rect',UNHEX(?),?,'','','',100,100,50,50)";

    $params = array($map, $url);
    $rs     = $conn->Execute($sql, $params);

    if (!$rs)
    {
        Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());

        exit();
    }

    $sql = "SELECT last_insert_id() AS id";
    $rs  = $conn->Execute($sql);

    if (!$rs)
    {
        Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());

        exit();
    }

    if(!$rs->EOF)
    {
        $id = $rs->fields['id'];

        echo "OK###drawRect('$id','$url',100,100,50,50);\n";
    }
}
else
{
    $icon = ($iconbg != '' && $iconbg != 'transparent') ? $chosen_icon."#".$iconbg : $chosen_icon;

    if (!empty($asset_type))
    {
        $asset_type_aux = fix_type($asset_type);

        $params = array($asset_id, $asset_type_aux);

        $sql = "SELECT HEX(member), type FROM bp_asset_member WHERE member = UNHEX(?) AND type = ?";
        $rs  = $conn->Execute($sql, $params);

        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());

            exit();
        }

        if ($rs->RecordCount() == "0")
        {
            // check if asset exist
            $sql = "INSERT INTO bp_asset_member (id, member, type) VALUES (0, UNHEX(?), ?)";
            $rs  = $conn->Execute($sql, $params);

            if (!$rs)
            {
                Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());

                exit();
            }

            // For net_group insert all related networks
            if ($asset_type == 'net_group' || $asset_type == 'netgroup')
            {
                $networks = Net_group::get_networks($conn, $asset_id);

                foreach($networks as $network)
                {
                    $net_id = $network->get_net_id();
                    $sql    = "INSERT INTO bp_asset_member (id, member, type) VALUES (0, UNHEX(?), ?)";

                    $conn->Execute($sql, array($net_id, "net"));
                }
            }
        }
    }


    // Random position to prevent overlaping
    $x = rand(50, 250);
    $y = rand(50, 150);

    $params = array(
        $alarm_name,
        $map,
        $url,
        $asset_type,
        $asset_id,
        $icon,
        $x,
        $y,
        $iconsize
    );


    $sql = "INSERT INTO risk_indicators (name, map, url, type, type_name, icon, x, y, w, h, size) VALUES (?,UNHEX(?),?,?,?,?,?,?,80,70,?)";
    $rs  = $conn->Execute($sql, $params);

    if (!$rs)
    {
        Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());

        exit();
    }


    $sql = "SELECT last_insert_id() AS id";
    $rs  = $conn->Execute($sql);

    if (!$rs)
    {
        Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());

        exit();
    }

    if(!$rs->EOF)
    {
        $id = $rs->fields['id'];

        echo "OK###drawDiv('$id','".Util::htmlentities($alarm_name)."','','$icon','$url',$x,$y,80,70,'$asset_type','".Util::htmlentities($asset_id)."', $iconsize, '".Util::htmlentities($name_show)."');\n";
    }
}

$db->close();
