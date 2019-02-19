<?php
/*******************************************************************************
** OSSIM Forensics Console
** Copyright (C) 2009 OSSIM/AlienVault
** Copyright (C) 2004 BASE Project Team
** Copyright (C) 2000 Carnegie Mellon University
**
** (see the file 'base_main.php' for license details)
**
** Built upon work by Roman Danyliw <rdd@cert.org>, <roman@danyliw.com>
** Built upon work by the BASE Project Team <kjohnson@secureideas.net>
**/


/**
* Class and Function List:
* Function list:
* - GetSignatureName()
* - GetSignaturePriority()
* - GetSignatureID()
* - GetRefSystemName()
* - GetSingleSignatureReference()
* - LoadSignatureReference()
* - GetSignatureReference()
* - BuildSigLookup()
* - BuildSigByID()
* - GetSigClassID()
* - GetSigClassName()
* - GetTagTriger()
*/


require_once 'classes/Util.inc';
defined('_BASE_INC') or die('Accessing this file directly is not allowed.');

function GetRefSystemName($ref_system_id, $db) {
    if ($ref_system_id == "") return "";
    $ref_system_name = "";
    $tmp_sql = "SELECT ref_system_name FROM reference_system WHERE ref_system_id='" . addslashes($ref_system_id) . "'";
    $tmp_result = $db->baseExecute($tmp_sql);
    if ($tmp_result) {
        $myrow = $tmp_result->baseFetchRow();
        $ref_system_name = $myrow[0];
        $tmp_result->baseFreeRows();
    }
    return trim($ref_system_name);
}
function GetSingleSignatureReference($ref_system, $ref_tag, $style) {
    $tmp_ref_system_name = strtolower($ref_system);

	if (in_array($tmp_ref_system_name, array_keys($GLOBALS['external_sig_link']))) {
        if ($style == 1) return "<FONT SIZE=-1>[" . "<A HREF=\"" . $GLOBALS['external_sig_link'][$tmp_ref_system_name][0] . $ref_tag . $GLOBALS['external_sig_link'][$tmp_ref_system_name][1] . "\" " . "TARGET=\"_ACID_ALERT_DESC\">" . $ref_system . "</A>" . "]</FONT> ";
        else if ($style == 2) return "[" . $ref_system . "/$ref_tag] ";
    } else {
        return $ref_system;
    }
}
function LoadSignatureReference($db) {
    /* Cache Sig refs */
    $sig_ref_array = array();
    $temp_sql = "SELECT sig_id, ref_seq, ref_id FROM sig_reference";
    $tmp_sig_ref = $db->baseExecute($temp_sql);
    $num_rows = $tmp_sig_ref->baseRecordCount();
    for ($i = 0; $i < $num_rows; $i++) {
        $myrow = $tmp_sig_ref->baseFetchRow();
        if (!isset($sig_ref_array[$myrow[0]])) {
            $sig_ref_array[$myrow[0]] = array();
        }
        $sig_ref_array[$myrow[0]][$myrow[1]] = $myrow[2];
    }
    if (!isset($_SESSION['acid_sig_refs'])) $_SESSION['acid_sig_refs'] = $sig_ref_array;
}
function BuildSigLookup($signature, $style)
/* - Paul Harrington <paul@pizza.org> : reference URL links
* - Michael Bell <michael.bell@web.de> : links for IP address in spp_portscan alerts
*/ {
    if ($style == 2) return $signature;
    /* create hyperlinks for references */
    $pattern = array(
        "/(IDS)(\d+)/",
        "/(IDS)(0+)(\d+)/",
        "/BUGTRAQ ID (\d+)/",
        "/MCAFEE ID (\d+)/",
        "/(CVE-\d+-\d+)/"
    );
    $replace = array(
        "<A HREF=\"http://www.whitehats.com/\\1/\\2\" TARGET=\"_ACID_ALERT_DESC\">\\1\\2</A>",
        "<A HREF=\"http://www.whitehats.com/\\1/\\3\" TARGET=\"_ACID_ALERT_DESC\">\\1\\2\\3</A>",
        "<A HREF=\"" . $GLOBALS['external_sig_link']['bugtraq'][0] . "\\1\" TARGET=\"_ACID_ALERT_DESC\">BUGTRAQ ID \\1</A>",
        "<A HREF=\"" . $GLOBALS['external_sig_link']['mcafee'][0] . "\\1\" TARGET=\"_ACID_ALERT_DESC\">MCAFEE ID \\1</A>",
        "<A HREF=\"" . $GLOBALS['external_sig_link']['cve'][0] . "\\1\" TARGET=\"_ACID_ALERT_DESC\">\\1</A>"
    );
    $msg = preg_replace($pattern, $replace, $signature);
    /* fixup portscan message strings */
    if (stristr($msg, "spp_portscan")) {
        /* replace "spp_portscan: portscan status" => "spp_portscan"  */
        $msg = preg_replace("/spp_portscan: portscan status/", "spp_portscan", $msg);
        /* replace "spp_portscan: PORTSCAN DETECTED" => "spp_portscan detected" */
        $msg = preg_replace("/spp_portscan: PORTSCAN DETECTED/", "spp_portscan detected", $msg);
        /* create hyperlink for IP addresses in portscan alerts */
        $msg = preg_replace("/([0-9]*\.[0-9]*\.[0-9]*\.[0-9]*)/", "<A HREF=\"base_stat_ipaddr.php?ip=\\1&amp;netmask=32\">\\1</A>", $msg);
    }
    return $msg;
}
function BuildSigByID($sig_id, $sid, $cid, $db, $style = 1)
/*
* sig_id: DB schema dependent
*         - < v100: a text string of the signature
*         - > v100: an ID (key) of a signature
* db    : database handle
* style : how should the signature be returned?
*         - 1: (default) HTML
*         - 2: text
*
* RETURNS: a formatted signature and the associated references
*/ {
	if ($sid=="" || $cid=="") return ""; // GetSignatureName($sig_id, $db);

    if ($db->baseGetDBversion() >= 100) {
        /* Catch the odd circumstance where $sig_id is still an alert text string
        * despite using normalized signature as of DB version 100.
        */
        if (!is_numeric($sig_id)) return $sig_id;
        $sig_name = ""; //$sig_name = GetSignatureName($sig_id, $db);
        if ($sig_name != "") {
            return GetSignatureReferences($sid, $cid, $db) . " " . BuildSigLookup($sig_name, $style);
        } else {
            if ($style == 1) return "($sig_id)<I>" . gettext("SigName unknown") . "</I>";
            else return "($sig_id) " . gettext("SigName unknown");
        }
    } else return BuildSigLookup($sig_id, $style);
}

function GetSignatureReferences($sid, $cid, $db) {
	$external_sig_link = $GLOBALS['external_sig_link'];
	$str = "";
	$temp_sql = "SELECT r.ref_tag,rs.ref_system_name,rs.url,rs.icon,rs.ref_system_id FROM sig_reference s, reference r, reference_system rs,ossim_event o WHERE rs.ref_system_id=r.ref_system_id AND r.ref_id=s.ref_id AND s.plugin_id=o.plugin_id and s.plugin_sid=o.plugin_sid and o.sid=$sid and o.cid=$cid";
	$tmp_result = $db->baseExecute($temp_sql);
	if ($tmp_result) {
		while ($row = $tmp_result->baseFetchRow()) {
			$url_src = $row["url"];
			$link = "";
			$row["ref_tag"] = trim($row["ref_tag"]);
			$row["ref_system_name"] = strtolower(trim($row["ref_system_name"]));
			if ($url_src != "") {
				$url = str_replace("%value%",rawurlencode($row["ref_tag"]),$url_src);
				$target = (preg_match("/^http/",$url)) ? "_blank" : "main";
				$anchor = ($row["icon"] != "") ? "<img src='manage_references_icon.php?id=".$row['ref_system_id']."' alt='".$row['ref_system_name']."' title='".$row['ref_system_name']."' border='0'>" : "[".$row["ref_system_name"]."]";
				$link = "<a href='".urldecode($url)."' target='$target'>".$anchor."</a>";
			}
			if ($link!="") $str .= " ".$link;
		}
		$tmp_result->baseFreeRows();
	}
	return $str."##";
}

function BuildSigByPlugin($plugin_id, $plugin_sid, $db, $ctx) {
    $sig_name = GetOssimSignatureName($plugin_id, $plugin_sid, $db, $ctx);
    if ($sig_name != "") {
        return GetOssimSignatureReferences($plugin_id, $plugin_sid, $db)." ".$sig_name;
    } else {
        $plugin_name = "";
        if (isset($_SESSION["_pname_".$plugin_id])) {
            $plugin_name = $_SESSION["_pname_".$plugin_id];
        } elseif ($plugin_id!="") {
            $temp_sql = "SELECT name FROM alienvault.plugin WHERE id=$plugin_id";
            $tmp_result = $db->baseExecute($temp_sql);
            if ($tmp_result) {
                $myrow = $tmp_result->baseFetchRow();
                $plugin_name = $myrow[0];
                $tmp_result->baseFreeRows();
            }
            $_SESSION["_pname_".$plugin_id] = $plugin_name;
        }
        if ($plugin_name!="" && $plugin_sid==2000000000)
            return "$plugin_name: ".gettext("Generic event");
        elseif ($plugin_name!="" && $plugin_sid!=2000000000)
            return "$plugin_name: ".gettext("Unknown event");
        else
            return gettext("Signame Unknown"); //return "($plugin_id,$plugin_sid) " . gettext("SigName unknown");
    }
}

function TranslateSignature($name, $arr)
{
    $translations = array(
        "/SRC_IP/" => '@inet_ntop($arr["ip_src"])',
        "/DST_IP/" => '@inet_ntop($arr["ip_dst"])',
        "/SRC_PORT/" => '$arr["layer4_sport"]',
        "/DST_PORT/" => '$arr["layer4_dport"]',
        "/PROTOCOL/" => 'Protocol::get_protocol_by_number($arr["ip_proto"], TRUE)',
        "/PULSE/" => 'GetPulseName($arr["pulse"])',
        "/PLUGIN_ID/" => '$arr["plugin_id"]',
        "/PLUGIN_SID/" => '$arr["plugin_sid"]',
        "/FILENAME/" => 'Util::htmlentities($arr["filename"])',
        "/USERNAME/" => 'Util::htmlentities($arr["username"])',
        "/USERDATA1/" => 'Util::htmlentities($arr["userdata1"])',
        "/USERDATA2/" => 'Util::htmlentities($arr["userdata2"])',
        "/USERDATA3/" => 'Util::htmlentities($arr["userdata3"])',
        "/USERDATA4/" => 'Util::htmlentities($arr["userdata4"])',
        "/USERDATA5/" => 'Util::htmlentities($arr["userdata5"])',
        "/USERDATA6/" => 'Util::htmlentities($arr["userdata6"])',
        "/USERDATA7/" => 'Util::htmlentities($arr["userdata7"])',
        "/USERDATA8/" => 'Util::htmlentities($arr["userdata8"])',
        "/USERDATA9/" => 'Util::htmlentities($arr["userdata9"])'
    );

    foreach($translations as $k => $replacement)
    {
        $pattern = '$name = preg_replace("' . $k . '", %s, $name);';
        $str = sprintf($pattern, $replacement);
        eval($str);
    }

    return $name;
}


function GetPluginNameDesc($plugin_id, $db) {
    if (!isset($_SESSION['_plugin_namedesc'])) $_SESSION['_plugin_namedesc'] = array();
    if (!isset($_SESSION['_plugin_namedesc'][$plugin_id])) {
        $name = "";
        $temp_sql = "SELECT name,description FROM alienvault.plugin WHERE id=$plugin_id";
        $tmp_result = $db->baseExecute($temp_sql);
        if ($tmp_result) {
            $myrow = $tmp_result->baseFetchRow();
            $name = $myrow[0];
            $desc = $myrow[1];
            $tmp_result->baseFreeRows();
        }
        $_SESSION['_plugin_namedesc'][$plugin_id] = Util::htmlentities($name.";".$desc, ENT_COMPAT, "UTF-8");
    }
    return explode(";",$_SESSION['_plugin_namedesc'][$plugin_id]);
}

function GetOssimSignatureName($plugin_id, $plugin_sid, $db,$ctx) {
    if (!isset($_SESSION['_sig_names'])) $_SESSION['_sig_names'] = array();
    if (isset($_SESSION['_sig_names'][$plugin_id." ".$plugin_sid])) {
        return $_SESSION['_sig_names'][$plugin_id." ".$plugin_sid];
    }
    if ($plugin_id=="" || $plugin_sid=="") return "";
    $name = "";
    $temp_sql = "SELECT name FROM alienvault.plugin_sid WHERE plugin_id=$plugin_id AND sid=$plugin_sid  AND  hex(plugin_ctx) IN ('$ctx' ,'00000000000000000000000000000000') ";
    $tmp_result = $db->baseExecute($temp_sql);
    if ($tmp_result) {
        $myrow = $tmp_result->baseFetchRow();
        $name = $myrow[0];
        $tmp_result->baseFreeRows();
    }
    $name = Util::htmlentities($name, ENT_COMPAT, "UTF-8");
    $_SESSION['_sig_names'][$plugin_id." ".$plugin_sid] = $name;
    return $name;
}

function GetOssimSignatureReferences($plugin_id, $plugin_sid, $db) {
    $external_sig_link = $GLOBALS['external_sig_link'];
    $str = "";
    $temp_sql = "SELECT r.ref_tag,rs.ref_system_name,rs.url,rs.icon,rs.ref_system_id FROM sig_reference s, reference r, reference_system rs WHERE rs.ref_system_id=r.ref_system_id AND r.ref_id=s.ref_id AND s.plugin_id=$plugin_id and s.plugin_sid=$plugin_sid";
    //print_r($temp_sql);
    $tmp_result = $db->baseExecute($temp_sql);
    if ($tmp_result) {
        while ($row = $tmp_result->baseFetchRow()) {
            $url_src = $row["url"];
            $link = "";
            $row["ref_tag"] = trim($row["ref_tag"]);
            $row["ref_system_name"] = strtolower(trim($row["ref_system_name"]));
            if ($url_src != "") {
            	if (preg_match("/^http/",$row["ref_tag"])) $url_src = str_replace("http://","",$url_src);
                $url = str_replace("%value%",rawurlencode($row["ref_tag"]),$url_src);
                $target = (preg_match("/^http/",$url)) ? "_blank" : "main";
                $anchor = ($row["icon"] != "") ? "<img src='manage_references_icon.php?id=".$row['ref_system_id']."' alt='".$row['ref_system_name']."' title='".$row['ref_system_name']."' border='0'>" : "[".$row["ref_system_name"]."]";
                $link = "<a class='trlnks' href='".urldecode($url)."' target='$target'>".$anchor."</a>";
            }
            if ($link!="") $str .= " ".$link;
        }
        $tmp_result->baseFreeRows();
    }
    return $str."##";
}
function GetSigClassName($class_id, $db) {
    if ($class_id == "") return "<I>unclassified</I>";
    if (!isset($_SESSION['acid_sig_class_name'])) $_SESSION['acid_sig_class_name'] = array();
    if (isset($_SESSION['acid_sig_class_name'][$sig_id])) {
        return $_SESSION['acid_sig_class_name'][$sig_id];
    }
    $sql = "SELECT sig_class_name FROM sig_class " . "WHERE sig_class_id = '$class_id'";
    $result = $db->baseExecute($sql);
    $row = $result->baseFetchRow();
    if ($row == "") {
        $_SESSION['acid_sig_class_name'][$sig_id] = "<I>" . gettext("unclassified") . "</I>";
        return "<I>" . gettext("unclassified") . "</I>";
    } else {
        $_SESSION['acid_sig_class_name'][$sig_id] = Util::htmlentities($row[0], ENT_COMPAT, "UTF-8");
        return $row[0];
    }
}
?>
