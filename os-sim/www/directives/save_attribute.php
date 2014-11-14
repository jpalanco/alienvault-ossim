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

require_once ('av_init.php');

Session::logcheck("configuration-menu", "CorrelationDirectives");


$engine_id = POST('engine_id');
$value  = POST('value');
$fields = explode("_-_", base64_decode(str_replace("EQUAL", "=", POST('id'))));

$another_error = ""; // Data consistency errors

// For debug:
//$engine_id = "9b129964-da18-11e1-ab07-82ccaaa003fa";
//$value = "6";
//$fields = explode("_-_", base64_decode("dGltZV9vdXRfLV81MDAwMDEtMi0xXy1fNTAwMDAxXy1fdXNlci54bWxfLV8xMA=="));

$attrib = $fields[0];
$rule   = $fields[1];
$dir_id = $fields[2];
$file   = $fields[3];

ossim_valid($engine_id, OSS_HEX, '\-', 'illegal:' . _("Engine ID"));
ossim_valid($rule, OSS_DIGIT, '\-', 'illegal:' . _("Rule ID"));
ossim_valid($dir_id, OSS_DIGIT, 'illegal:' . _("Directive ID"));
ossim_valid($file, OSS_ALPHA, OSS_PUNC,      'illegal:' . _("File"));
if (ossim_error()) {
    die(ossim_error());
}

$file = "user.xml"; // Force to user.xml as no other can be written

// Get current value
$directive_editor = new Directive_editor($engine_id);
$rule_aux = $directive_editor->get_rule($dir_id, $file, $rule);
$current_value = $rule_aux->$attrib;

// Timeout
if ($attrib == "time_out") {
	ossim_valid($value, OSS_DIGIT, 'noneNONE', OSS_NULLABLE, 'illegal:' . $attrib);
	if (preg_match("/^none$/i", $value)) { $value = ""; } // None is empty
	elseif ($value != "" && !preg_match("/^none$/i", $value) && !preg_match("/^\d+$/", $value)) {
		$another_error = _("Timeout must have a numeric value or None");
	}
	if ($value == "") { $value = "None"; }

// Occurrence
} elseif ($attrib == "occurrence") {
	ossim_valid($value, OSS_DIGIT, 'illegal:' . $attrib);

// Reliabilily
} elseif ($attrib == "reliability") {
	ossim_valid($value, OSS_DIGIT, '\+', '\=', 'illegal:' . $attrib);
	if (preg_replace("/[^\d]*+(\d+)[^\d]*/", "\\1", $value)+0 < 0 || preg_replace("/[^\d]*+(\d+)[^\d]*/", "\\1", $value)+0 > 10) {
		$another_error = _("Reliability must have a value between 0 and 10");
	}

// Name
} elseif ($attrib == "name") {
	ossim_valid($value, OSS_RULE_NAME, 'illegal:' . _("Name"));

// Protocol
} elseif ($attrib == "protocol") {
	ossim_valid($value, OSS_ALPHA, '\,', OSS_COLON, OSS_NULLABLE, 'illegal:' . _("Protocol"));
	if (preg_match("/^any$/i", $value)) { $value = ""; }
	$proto_values = ($value != "") ? explode(",", $value) : array();
	foreach ($proto_values as $proto_value) {
		if (!preg_match("/^\d\:PROTOCOL$/i", $proto_value) &&
			!preg_match("/^tcp$/i", $proto_value) &&
			!preg_match("/^udp$/i", $proto_value) &&
			!preg_match("/^icmp$/i", $proto_value)) {
			$another_error = _("Protocol allowed values are: TCP, UDP, ICMP, or n:PROTOCOL");
		}
	}
	$value = strtoupper($value);

// Sticky Different
} elseif ($attrib == "sticky_different") {
	ossim_valid($value, OSS_ALPHA, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("Sticky Different"));
	if (preg_match("/^none$/i", $value)) { $value = ""; }
	if (!preg_match("/^$/i", $value) &&
			!preg_match("/^plugin\_sid$/i", $value) &&
			!preg_match("/^src\_ip$/i", $value) &&
			!preg_match("/^dst\_ip$/i", $value) &&
			!preg_match("/^src\_port$/i", $value) &&
			!preg_match("/^dst\_port$/i", $value) &&
			!preg_match("/^protocol$/i", $value) &&
			!preg_match("/^sensor$/i", $value)) {
		$another_error = _("Sticky Dif allowed values are: PLUGIN_SID, SRC_IP, DST_IP, SRC_PORT, DST_PORT, PROTOCOL, SENSOR");
	}
	$value = strtoupper($value);
	
} elseif ($attrib == "filename") {
	ossim_valid($value, OSS_NULLABLE, OSS_ALPHA, OSS_SLASH, OSS_DIGIT, OSS_DOT, OSS_COLON, '\!,', 'illegal:' . _("file name"));
} elseif ($attrib == "username") {
	ossim_valid($value, OSS_NULLABLE, OSS_USER, OSS_PUNC_EXT, 'illegal:' . _("user name"));
} elseif ($attrib == "password") {
	ossim_valid($value, OSS_NULLABLE, OSS_PASSWORD, 'illegal:' . _("password"));
} elseif (preg_match("/^userdata\d+$/", $attrib)) {
	ossim_valid($value, OSS_NULLABLE, OSS_ALPHA, OSS_PUNC_EXT, 'illegal:' . _("userdata1"));
} else {
	echo json_encode(array("error" => 1, "msg" => _("Attribute not found"), "current_value" => $current_value, "new_value" => $value));
	exit;
}
if (ossim_error()) {
	echo json_encode(array("error" => 1, "msg" => ossim_get_error(), "current_value" => $current_value, "new_value" => $value));
	exit;
} elseif ($another_error != "") {
	echo json_encode(array("error" => 1, "msg" => $another_error, "current_value" => $current_value, "new_value" => $value));
	exit;
}

if ($directive_editor->save_rule_attrib($rule, $dir_id, $file, $attrib, $value)) {
	if ($attrib == "password") { $value = preg_replace("/./", "*", $value); } // Hide password field
	if ($attrib == "timeout" && $value == "") { $value = "None"; }
	if ($attrib == "protocol" && $value == "") { $value = "ANY"; }
	echo json_encode(array("error" => 0, "msg" => _("File successfully updated"), "current_value" => $current_value, "new_value" => $value));
} else {
	if ($attrib == "password") { $current_value = preg_replace("/./", "*", $current_value); } // Hide password field
	echo json_encode(array("error" => 1, "msg" => _("Error saving XML file"), "current_value" => $current_value, "new_value" => $value));
}
?>