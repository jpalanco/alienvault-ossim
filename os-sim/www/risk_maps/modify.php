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

Session::logcheck("dashboard-menu", "BusinessProcesses");

if (!Session::menu_perms("dashboard-menu", "BusinessProcessesEdit")) 
{
    echo ossim_error(_("You don't have permissions to edit risk indicators"));
    exit();
}


$db   = new ossim_db();
$conn = $db->connect();

$map  = GET("map");
$data = GET("data");
$name = GET("name");
$url  = GET("url");
$id   = GET("id");

ossim_valid($map, OSS_HEX,                                                                    'illegal:'._("Map"));
ossim_valid($data, OSS_SCORE, OSS_NULLABLE, OSS_ALPHA, OSS_DIGIT, ";,.",                      'illegal:'._("Data"));
ossim_valid($name, OSS_SCORE, OSS_NULLABLE, OSS_ALPHA,                                        'illegal:'._("Name"));
ossim_valid($url, OSS_NULLABLE, OSS_SCORE, OSS_ALPHA, OSS_DIGIT, OSS_SPACE, ";,.:\/\?=&()%&", 'illegal:'._("Url"));
ossim_valid($id, OSS_DIGIT,                                                                   'illegal:'._("ID"));

if (ossim_error()) 
{
    die(ossim_error());
}
    
$indicators  = array();
$delete_list = array();

$elems       = explode(";",$data);
foreach ($elems as $elem) 
{
    if (trim($elem)!="") 
    {
        $param = explode(",",$elem);
        $id    = str_replace("rect","",str_replace("indicator","",$param[0]));
        
        $indicators[$id]["x"] = str_replace("px","",$param[1]);
        $indicators[$id]["y"] = str_replace("px","",$param[2]);
        $indicators[$id]["w"] = str_replace("px","",$param[3]);
        $indicators[$id]["h"] = str_replace("px","",$param[4]);
    }
}

$active = array_keys($indicators);
$query  = "select id, type, type_name from risk_indicators where map=unhex(?)";
$params = array($map);
    
if (!$rs = &$conn->Execute($query, $params)) 
{
    $log = $conn->ErrorMsg();
} 
else 
{
    while (!$rs->EOF)
    {
        if (in_array($rs->fields["id"],$active)) 
        {
            $pos = $indicators[$rs->fields["id"]];
            $query = "update risk_indicators set x= ?,y= ?, w= ?, h= ? where id= ?";
            $params = array($pos["x"],$pos["y"],$pos["w"],$pos["h"],$rs->fields["id"]);
            $conn->Execute($query, $params);
        } 
        else
        {
            $delete_list[] = array($rs->fields["id"],$rs->fields["type"],$rs->fields["type_name"]);
        }
        
        $rs->MoveNext();
    }
}
        
foreach ($delete_list as $idb)
{
    $host_types = array("host", "server", "sensor");

    list ($name,$sensor,$type,$ips,$in_assets) = get_assets($conn,$idb[2],$idb[1],$host_types);
    
    $type = fix_type($type);
    
    $query  = "delete from bp_asset_member where member=unhex(?) and type=?";
    $params = array($name, $type);
    
    $conn->Execute($query, $params);

    $query = "delete from risk_indicators where id= ?";
    
    $conn->Execute($query, $params);
}

$conn->close();
