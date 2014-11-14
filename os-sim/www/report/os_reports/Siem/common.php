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

Session::useractive();

require_once 'jgraphs/jgraphs.php';


$path = '/usr/share/ossim/www/report/os_reports/';
set_include_path(get_include_path() . PATH_SEPARATOR . $path);

require_once 'Common/functions.php';

if (is_object($security_report))
{
	$security_report->close_conns();
	unset($security_report);
}

$security_report = new Security_report();


function getFontSizeSIEM($list) 
{
    if(count($list) <= 20)
    {
        $font_size = 12;
    }
    else if(count($list) <= 30)
    {
        $font_size = 10;
    }
    else
    {
        $font_size = 8;
    }

    return $font_size;
}


function getFlagSIEM($geoloc, $ip)
{
    // get country flag
    $s_country = strtolower($geoloc->get_country_code_from_file($ip));
    if($s_country == '')
    {
        $flag = '';
    }
    else
    {
        $flag = '/ossim/pixmaps/flags/'.$s_country.'.png';
    }

    return $flag;
}


function getFlagSIEMHtml($url)
{
    if($url!='')
    {
        return '<img src="'.$url.'" />';
    }
    else
    {
        return '';
    }
}


function getFlagSIEMPDF($url)
{
    if($url != '')
    {
        return '<img src="'.getProtocol().'//'.Util::get_default_admin_ip().$url.'" />';
    }
    else
    {
        return '';
    }
}


function baseIP2long($IP_str) 
{
    $tmp_long = ip2long($IP_str);
    
    if ($tmp_long < 0)
    {
        $tmp_long = 4294967296 - abs($tmp_long);
    }
    
    return $tmp_long;
}


function baseGetHostByAddr($ipaddr, $db, $cache_lifetime) 
{
    $ip32 = baseIP2long($ipaddr);
    $current_unixtime = time();

    $sql = "SELECT ipc_ip,ipc_fqdn,ipc_dns_timestamp" . " FROM acid_ip_cache " . " WHERE ipc_ip = '$ip32' ";
    
    $result   = $db->Execute($sql);
    $ip_cache = $result->FetchRow();
    
    /* cache miss */
    if ($ip_cache == '')
    {
        $tmp = gethostbyaddr($ipaddr);
    }
    else
    {
        /* cache hit */
        
        if ($ip_cache[2] != '' && (((strtotime($ip_cache[2]) / 60) + $cache_lifetime) >= ($current_unixtime / 60)))
        {
            /* valid entry */
            if ($ip_cache[2] != '' && $ip_cache[2] != 0)
            {
                $tmp = $ip_cache[1];
            } 
            else
            {
                /* name could not be resolved */
                $tmp = $ipaddr;
            }
            
        } 
        else
        /* cache expired */ 
        {
            $tmp = gethostbyaddr($ipaddr);
        }
    }
    if ($tmp == $ipaddr)
    {
        return "&nbsp;<I>" . _('Unable to resolve address') . "</I>&nbsp;";
    }
    else
    { 
        return $tmp;
    }
}


function IPProto2str($ipproto_code)
{
    switch ($ipproto_code) 
    {
        case 0:
            return 'IP';
        case 1:
            return 'ICMP';
        case 2:
            return 'IGMP';
        case 4:
            return 'IPIP tunnels';
        case 6:
            return 'TCP';
        case 8:
            return 'EGP';
        case 12:
            return 'PUP';
        case 17:
            return 'UDP';
        case 22:
            return 'XNS UDP';
        case 29:
            return 'SO TP Class 4';
        case 41:
            return 'IPv6 header';
        case 43:
            return 'IPv6 routing header';
        case 44:
            return 'IPv6 fragmentation header';
        case 46:
            return 'RSVP';
        case 47:
            return 'GRE';
        case 50:
            return 'IPSec ESP';
        case 51:
            return 'IPSec AH';
        case 58:
            return 'ICMPv6';
        case 59:
            return 'IPv6 no next header';
        case 60:
            return 'IPv6 destination options';
        case 92:
            return 'MTP';
        case 98:
            return 'Encapsulation header';
        case 103:
            return 'PIM';
        case 108:
            return 'COMP';
        case 255:
            return 'Raw IP';
        default:
            return $ipproto_code;
    }
}


function BuildSigByPlugin($plugin_id, $plugin_sid, $db) 
{
    $sig_name = GetOssimSignatureName($plugin_id, $plugin_sid, $db);
    
    if ($sig_name != '') 
    {
        return GetOssimSignatureReferences($plugin_id, $plugin_sid, $db)." ".$sig_name;
    } 
    else 
    {
        return "($plugin_id,$plugin_sid) " . _ERRSIGNAMEUNK;
    }
}


function GetOssimSignatureName($plugin_id, $plugin_sid, $db) 
{
    if (!isset($_SESSION['acid_sig_names'])) 
    {
        $_SESSION['acid_sig_names'] = array();
    }
    
    if (isset($_SESSION['acid_sig_names'][$plugin_id.' '.$plugin_sid])) 
    {
        return $_SESSION['acid_sig_names'][$plugin_id.' '.$plugin_sid];
    }
    
    $name = '';
    $temp_sql = "SELECT name FROM ossim.plugin_sid WHERE plugin_id=$plugin_id AND sid=$plugin_sid";
    $tmp_result = $db->Execute($temp_sql);
    
    if ($tmp_result) 
    {
        $myrow = $tmp_result->FetchRow();
        $name  = $myrow[0];
        
    }
    
    $_SESSION['acid_sig_names'][$plugin_id." ".$plugin_sid] = Util::htmlentities($name, ENT_COMPAT, "UTF-8");
    
    return $name;
}


function GetOssimSignatureReferences($plugin_id, $plugin_sid, $db) 
{   
    $str = '';

    $temp_sql = "SELECT r.ref_tag,rs.ref_system_name,rs.url,rs.icon,rs.ref_system_id 
        FROM sig_reference s, reference r, reference_system rs 
        WHERE rs.ref_system_id = r.ref_system_id AND r.ref_id = s.ref_id AND s.plugin_id = $plugin_id and s.plugin_sid = $plugin_sid";

    $tmp_result = $db->Execute($temp_sql);

    if ($tmp_result)
    {
        while ($row = $tmp_result->FetchRow())
        {
            $url_src                = $row['url'];
            $link                   = '';
            $row["ref_tag"]         = trim($row["ref_tag"]);
            $row["ref_system_name"] = strtolower(trim($row["ref_system_name"]));
            
            if ($url_src != '')
            {
                $url = str_replace("%value%", rawurlencode($row["ref_tag"]),$url_src);
                
                $target = (preg_match("/^http/", $url)) ? "_blank" : "main";
                
                if($row['icon'] != '')
                {
                    $anchor = "<img src='manage_references_icon.php?id=".$row['ref_system_id']."' alt='".$row['ref_system_name']."' title='".$row['ref_system_name']."' border='0'/>";
                }
                else
                {
                    $anchor = '['.$row['ref_system_name'].']';
                }

                $link = "<a href='".urldecode($url)."' target='$target'>".$anchor."</a>";
            }
            
            if ($link != '')
            {
                $str .= ' '.$link;
            }
        }
    }

    return $str.'##';
}


function GetPluginNameDesc($plugin_id, $db) 
{
    if (!isset($_SESSION['acid_plugin_namedesc'])) 
    {
        $_SESSION['acid_plugin_namedesc'] = array();
    }
    
    if (!isset($_SESSION['acid_plugin_namedesc'][$plugin_id])) 
    {       
        $name = '';
        $temp_sql = "SELECT name,description FROM ossim.plugin WHERE id=$plugin_id";
        $tmp_result = $db->Execute($temp_sql);
        
        if ($tmp_result) 
        {
            $myrow = $tmp_result->FetchRow();
            $name = $myrow[0];
            $desc = $myrow[1];
            //$tmp_result->baseFreeRows();
        }
        
        $_SESSION['acid_plugin_namedesc'][$plugin_id] = Util::htmlentities($name.";".$desc, ENT_COMPAT, "UTF-8");
    }
    
    return explode(';', $_SESSION['acid_plugin_namedesc'][$plugin_id]);
}


function extractWord_Siem($string, $MaxString, $delimiter = "<br>")
{
   $word1 = substr($string, 0, $MaxString);
   $word2 = substr($string, $MaxString);

   if(strlen($word2)>$MaxString)
   {
       $word2=extractWord_Siem($word2,$MaxString, $delimiter);
   }

   return $word1.$delimiter.$word2;
}


function GetSensorSidsNames($dbobj) 
{
	$db = $dbobj->snort_connect();
    $sensors = array();
    
    $temp_sql = "SELECT * FROM sensor";
    //echo $temp_sql;
    $tmp_result = $db->Execute($temp_sql);
    
    while ($myrow = $tmp_result->FetchRow()) 
    {
    	$name = ($myrow['sensor'] != '') ? $myrow['sensor'] : preg_replace("/-.*/", '', preg_replace("/.*\]\s*/", '', $myrow["hostname"]));
    	
    	$sensors[$myrow["sid"]]= $name;
    }
    
    return $sensors;
}


// rep color char
function getrepimg($prio,$act) 
{
	if (empty($prio)) 
	{
	   return '';
	}
	
	$lnk = "<img style='margin:0px 2px 2px 0px' align='absmiddle' border='0' alt='".trim($act)."' title='".trim($act)."'";
	if ($prio<=2)     
	{
	   $lnk .= " src='../reputation/images/green.png'";
	}
	else if ($prio<=6) 
	{
	   $lnk .= " src='../reputation/images/yellow.png'";
	}
	else
	{            
	   $lnk .= " src='../reputation/images/red.png'";
	}
	
	return $lnk."/>";
}

function getrepbgcolor($prio,$style = 0) 
{
	if (empty($prio)) 
	{
	   return '';
	}
	
	if ($prio<=2)     
	{
	   return ($style) ? "style='background-color:#fcefcc'" : "bgcolor='#fcefcc'";
	}
	else if ($prio<=6) 
	{
	   return ($style) ? "style='background-color:#fde5d6'" : "bgcolor='#fde5d6'";
	}
	else              
	{
	   return ($style) ? "style='background-color:#fccece'" : "bgcolor='#fccece'";
	}
}

$current_cols_titles = array(
    "SIGNATURE"              => _("Signature"),
    "DATE"                   => _("Date"),
    "IP_PORTSRC"             => _("Source"),
    "IP_PORTDST"             => _("Dest."),
    "IP_SRC"                 => _("Src IP"),
    "IP_DST"                 => _("Dst IP"),   
    "IP_SRC_FQDN"            => _("Src IP FQDN"),
    "IP_DST_FQDN"            => _("Dst IP FQDN"),     
    "PORT_SRC"               => _("Src Port"),
    "PORT_DST"               => _("Dst Port"),
    "ASSET"                  => _("Asset &nbsp;<br>S<img src='../pixmaps/arrow-000-small.png' border=0 align=absmiddle>D"),
    "PRIORITY"               => _("Prio"),
    "RELIABILITY"            => _("Rel"),
    "RISK"                   => _("Risk"),
    "IP_PROTO"               => _("L4-proto"),
    "USERDATA1"              => _("Userdata1"),
    "USERDATA2"              => _("Userdata2"),
    "USERDATA3"              => _("Userdata3"),
    "USERDATA4"              => _("Userdata4"),
    "USERDATA5"              => _("Userdata5"),
    "USERDATA6"              => _("Userdata6"),
    "USERDATA7"              => _("Userdata7"),
    "USERDATA8"              => _("Userdata8"),
    "USERDATA9"              => _("Userdata9"),
    "USERNAME"               => _("Username"),
    "FILENAME"               => _("Filename"),
    "PASSWORD"               => _("Password"),
    "SID"                    => _("SID"),
    "CID"                    => _("CID"),
	"SENSOR"                 => _("Sensor"),
    "PLUGIN_ID"              => _("Plugin ID"),
    "PLUGIN_SID"             => _("Plugin SID"),
    "PLUGIN_DESC"            => _("Plugin Description"),
    "PLUGIN_NAME"            => _("Plugin Name"),
    "PLUGIN_SOURCE_TYPE"     => _("Source Type"),
    "PLUGIN_SID_CATEGORY"    => _("Category"),
    "PLUGIN_SID_SUBCATEGORY" => _("SubCategory"),
	"PAYLOAD"                => _("Payload"),
	"CONTEXT"                => _("Cxt"),
    'SRC_USERNAME'           => _("IDM Username Src"),
    'DST_USERNAME'           => _("IDM Username Dst"),
    'SRC_DOMAIN'             => _("IDM Domain Src"),
    'DST_DOMAIN'             => _("IDM Domain Dst"),
    'SRC_HOSTNAME'           => _("IDM Source"),
    'DST_HOSTNAME'           => _("IDM Destination"),
    'SRC_MAC'                => _("IDM MAC Src"),
    'DST_MAC'                => _("IDM MAC Dst"),
    'REP_PRIO_SRC'           => _("Rep Src Prio"),
    'REP_PRIO_DST'           => _("Rep Dst Prio"),
    'REP_REL_SRC'            => _("Rep Src Rel"),
    'REP_REL_DST'            => _("Rep Dst Rel"),
    'REP_ACT_SRC'            => _("Rep Src Act"),
    'REP_ACT_DST'            => _("Rep Dst Act")	
);

$fixed_widths = array(
    "DATE"        => "15mm",
	"IP_PORTSRC"  => "20mm",
    "IP_PORTDST"  => "20mm",
	"IP_SRC"      => "20mm",
    "IP_DST"      => "20mm",
	"IP_PROTO"    => "12mm",
    "ASSET"       => "12mm",
    "PRIORITY"    => "12mm",
    "RELIABILITY" => "12mm",
    "RISK"        => "12mm",
    "IP_PROTO"    => "15mm",
	"SID"         => "8mm",
	"CID"         => "10mm",
	"PLUGIN_ID"   => "8mm",
    "PLUGIN_SID"  => "10mm",
	"CONTEXT"     => "10mm"
);


$fixed_pix = array(
    "DATE"        => "50px",
	"IP_PORTSRC"  => "60px",
    "IP_PORTDST"  => "60px",
	"IP_SRC"      => "60px",
    "IP_DST"      => "60px",
	"IP_PROTO"    => "30px",
    "ASSET"       => "30px",
    "PRIORITY"    => "30px",
    "RELIABILITY" => "30px",
    "RISK"        => "30px",
    "IP_PROTO"    => "20px",
	"SID"         => "20px",
	"CID"         => "30px",
	"PLUGIN_ID"   => "20px",
    "PLUGIN_SID"  => "30px",
);


function echo_risk($risk, $return = 0) 
{
    
    $width = (20 * $risk) + 1;
    
    $img = "<img style=\"margin-left: 3px\" src=\"../pixmaps/risk_yellow.jpg\" width=\"$width\" height=\"15\" />";
    if ($risk > 7) 
    {
        $img = "<img style=\"margin-left: 3px\" src=\"../pixmaps/risk_red.jpg\" " . "width=\"$width\" height=\"15\" />";
        $echo= "$img $risk";
    } 
    elseif ($risk > 4) 
    {
        $img = "<img style=\"margin-left: 3px\" src=\"../pixmaps/risk_yellow.jpg\" " . "width=\"$width\" height=\"15\" />";
        $echo= "$img $risk";
    } 
    elseif ($risk > 2) 
    {
        $img = "<img style=\"margin-left: 3px\" src=\"../pixmaps/risk_green.jpg\" " . "width=\"$width\" height=\"15\" />";
        $echo= "$img $risk";
    } 
    else 
    {
        $img = "<img style=\"margin-left: 3px\" src=\"../pixmaps/risk_blue.jpg\" " . "width=\"$width\" height=\"15\" />";
        $echo= "$img $risk";
    }
    
    if ($return == 0) 
    {
        echo $echo;
    }
    else
    {
        return $echo;
    }
}
?>