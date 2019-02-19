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

$columns = array(
"name" => _("Name"),
"reliability" => _("Reliability"),
"time_out" => _("Timeout"),
"occurrence" => _("Occurrence"),
"from" => _("From"),
"to" => _("To"),
"plugin_id" => _("Data Source"),
"plugin_sid" => _("Event Type"),
"sensor" => _("Sensor"),
"protocol" => _("Protocol"),
"sticky_different" => _("Sticky Dif"),
"username" => _("Username"),
"password" => _("Pass"),
"userdata1" => _("Userdata1"),
"userdata2" => _("Userdata2"),
"userdata3" => _("Userdata3"),
"userdata4" => _("Userdata4"),
"userdata5" => _("Userdata5"),
"userdata6" => _("Userdata6"),
"userdata7" => _("Userdata7"),
"userdata8" => _("Userdata8"),
"userdata9" => _("Userdata9")
);

$selected_columns = GET('selected_cols');
$directive_id     = GET("directive_id");
$xml_file         = GET('xml_file');
$save             = (GET('save') != "") ? 1 : 0;
ossim_valid($directive_id, OSS_DIGIT, 'illegal:' . _("Directive ID"));
ossim_valid($xml_file, OSS_ALPHA, OSS_DOT, OSS_SCORE, 'illegal:' . _("xml_file"));
ossim_valid($selected_columns, OSS_NULLABLE, OSS_ALPHA, OSS_PUNC, "Invalid: columns");
if (ossim_error()) {
    die(ossim_error());
}
$columns_arr = explode(",",$selected_columns);

$db = new ossim_db();
$conn = $db->connect();
$config = new User_config($conn);

// Save
if ($save) {
	if ($selected_columns == "") {
		$msg = "<font style='color:red'>"._("You must select one column at least.")."</font>";
	} else {
		$config->set(Session::get_session_user(), 'directive_editor_cols', $columns_arr, 'php', 'directives');
		?>
		<script type="text/javascript">
        var params          = new Array();
        params['xml']       = "<?php echo $xml_file ?>";
        params['directive'] = "<?php echo $directive_id ?>";
        params['reload']    = true;
        parent.GB_hide(params);
		</script>
		<?php
		exit;
	}
}

$columns_arr = $config->get(Session::get_session_user(), 'directive_editor_cols', 'php', 'directives');
if (count($columns_arr) < 1) {
	$columns_arr = array("name", "reliability", "time_out", "occurrence", "from", "to", "plugin_id", "plugin_sid");
}

$db->close($conn);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title> <?php echo gettext("Directive Editor"); ?> </title>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
    <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    <link type="text/css" rel="stylesheet" href="../style/jquery-ui-1.7.custom.css" />
    <link type="text/css" rel="stylesheet" href="../style/ui.multiselect.css" rel="stylesheet" />
	<style>
		/*Multiselect loading styles*/        
		#ms_body {height: 297px;}
		
		#load_ms {
			margin:auto; 
			padding-top: 105px; 
			text-align:center;
		}
	</style>
	<script type="text/javascript" src="../js/jquery.min.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.min.js"></script>
    <script type="text/javascript" src="../js/jquery.tmpl.1.1.1.js"></script>
    <script type="text/javascript" src="../js/ui.multiselect.js"></script>
    <script type="text/javascript" src="../js/combos.js"></script>    
    <script type="text/javascript">
		$(document).ready(function(){
			$(".multiselect").multiselect({
				searchable: false,
				nodeComparator: function (node1,node2){ return 1 },
				dividerLocation: 0.5,
			});
        });
    </script>
</head>
<body>
<form method="get" name="fcols">
<input type="hidden" name="directive_id" value="<?php echo $directive_id; ?>" />
<input type="hidden" name="xml_file" value="<?php echo $xml_file; ?>" />
<input type="hidden" name="save" value="1" />
<input type="hidden" name="selected_cols" value="" />
<table class="transparent" align="center">
	<tr><td class="center nobborder"><?php echo _("Select the <b>columns</b> to show in the rules, the rest will be in the '<b>More</b>' tab")?></td></tr>
	<tr><td class="nobborder">
	<div id='ms_body'>
        <div id='load_ms'><img src='../pixmaps/loading.gif'/></div>
		<select id="cols" class="multiselect" multiple="multiple" name="columns[]">
		
		<?php foreach($columns_arr as $label) { ?>
				<option value="<?php echo $label ?>" selected><?php echo $columns[$label] ?></option>
		<?php } ?>
		<?php foreach($columns as $label => $descr) if (!in_array($label, $columns_arr)) { ?>
				<option value="<?php echo $label ?>"><?php echo $descr ?></option>
		<?php } ?>
		</select>
	</div>
	</td></tr>
	<tr><td class="center nobborder" id="msg">&nbsp;<?php echo $msg ?></td></tr>
    <tr><td class="center nobborder">
		<input type="button" onclick="document.fcols.selected_cols.value=getselectedcombovalue('cols');document.fcols.submit()" value="<?php echo _("Save") ?>"/>&nbsp;
		<input type="button" class="av_b_secondary" onclick="parent.GB_hide()" value="<?php echo _("Cancel")?>"/>
	</td></tr>
</table>
</form>
</body>
</html>
