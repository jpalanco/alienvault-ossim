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

Session::logcheck("configuration-menu", "PolicyPorts");


$ports_name = GET('ports_name');
$protocol   = GET('ports_protocol');
ossim_valid($ports_name, OSS_NOECHARS, OSS_ALPHA, OSS_SCORE, OSS_PUNC, 'illegal:' . _("ports_name"));
ossim_valid($protocol  , OSS_LETTER                                  , 'illegal:' . _("Protocol"));

if (ossim_error()) 
{ 
    die(ossim_error());
}

if (preg_match('/-/',$ports_name))
{
    $ports_name_tmp = explode("-", $ports_name);
    
    if (is_numeric($ports_name_tmp[0]))
    {
        if ($ports_name_tmp[0]>$ports_name_tmp[1])
        {
            die("YYY");
        }
        else
        {
            for ($i=$ports_name_tmp[0]; $i<=$ports_name_tmp[1];$i++)
            {
                $list_ports[] = $i;
            }
        }
    }
    else
    {
        $list_ports[] = $ports_name;
    }
}
else
{
    $list_ports[] = $ports_name;
}
   
$db = new ossim_db();
$conn = $db->connect();

$output_ajax = "[";

foreach($list_ports as $port)
{
    if (is_numeric($port))
    {
        if($port < 0 && $port > 65535)
        {
            $db->close();
            die("ZZZ");
        }
        
        $output_ajax .= "'".$port."',";
        
        continue;
    }

    $port = Port::service2port($conn, $port, $protocol);
    
    if (is_numeric($port))
    {
        if($port<0 && $port>65535)
        {
            $db->close();
            die("ZZZ");
        }
        
        $output_ajax .= "'".$port."',";
        continue;
    }
    else
    {
        $db->close();
        
        die("XXX");
    }
}

$output_ajax  = preg_replace('/,$/','',$output_ajax);
$output_ajax .= "];";

$db->close();
echo($output_ajax);

?>