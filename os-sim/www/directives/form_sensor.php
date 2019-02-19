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

$rule_id = GET("rule_id");
$directive_id = GET("directive_id");
$xml_file = GET('xml_file');
$engine_id = GET('engine_id');
ossim_valid($rule_id, OSS_DIGIT, '\-', 'illegal:' . _("rule ID"));
ossim_valid($directive_id, OSS_DIGIT, 'illegal:' . _("Directive ID"));
ossim_valid($xml_file, OSS_ALPHA, OSS_DOT, OSS_SCORE, 'illegal:' . _("xml_file"));
ossim_valid($engine_id, OSS_HEX, OSS_SCORE, 'illegal:' . _("Engine ID"));
if (ossim_error()) {
    die(ossim_error());
}

if (GET('mode') != "") {
	if (GET("sensor") == "LIST") $_GET["sensor"] = GET("sensor_list");
	ossim_valid(GET("sensor_list"), OSS_HEX, ',', '-', OSS_NULLABLE, 'illegal:' . _("sensor list"));
    ossim_valid(GET("sensor"), OSS_SENSOR, OSS_NULLABLE, 'illegal:' . _("sensor"));
	if (ossim_error()) {
        die(ossim_error());
    }
    
    $directive_editor = new Directive_editor($engine_id);
	$directive_editor->save_rule_attrib($rule_id, $directive_id, $xml_file, "sensor", GET('sensor'));
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

$directive_editor = new Directive_editor($engine_id);
$rule = $directive_editor->get_rule($directive_id, $xml_file, $rule_id);

$db = new ossim_db();
$conn = $db->connect();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<link type="text/css" rel="stylesheet" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>" />
<link type="text/css" rel="stylesheet" href="../style/jquery-ui-1.7.custom.css" />
<link type="text/css" rel="stylesheet" href="../style/ui.multiselect.css" />
<script type="text/javascript" src="../js/jquery.min.js"></script>
<script type="text/javascript" src="../js/jquery-ui.min.js"></script>
<script type="text/javascript" src="../js/jquery.tmpl.1.1.1.js"></script>
<script type="text/javascript" src="../js/ui.multiselect_search.js"></script>
<script type="text/javascript" src="../js/combos.js"></script>

<script type="text/javascript">
$(document).ready(function(){
	$(".multiselect_sensor").multiselect({
		searchDelay: 700,
		dividerLocation: 0.5,
		nodeComparator: function (node1,node2){ return 1 }
	});
});

function save_sensor() {
	var sensor_list = getselectedcombovalue('sensorselect');
	var ret = true;
	//var entities = get_entities_string();
	var entities = "";
	var entities_arr = entities.split(",");
	$("#sensorselect option:selected").each(function(){
	   	sensor_allowed = false;
	   	for (var i in entities_arr) {
			if ($(this).attr('ctx').match(entities_arr[i])) { sensor_allowed = true; }
		}
		if (!sensor_allowed) {
			$('#sensor_msg').html("Sensor "+$(this).text()+" is not allowed for the selected entities");
			wizard_goto(7);
			ret = false;
		}
	});
	
	// from parent rule
	if (document.getElementById('sensor').value.match(/SENSOR/)) {
		document.getElementById('sensor_list').value = "";
	// from select list
	} else if (sensor_list != "") {
		document.getElementById('sensor').value = "LIST";
		document.getElementById('sensor_list').value = sensor_list;
	// empty
	} else {
		document.getElementById('sensor').value = "ANY";
		document.getElementById('sensor_list').value = "";
	}

	return ret;
}

function save_form() {
	save_sensor();
	document.sensorform.submit();
}
</script>
</head>
<body>
<form name="sensorform" method="get">
<input type="hidden" name="mode" value="save"></input>
<input type="hidden" name="rule_id" value="<?php echo $rule_id; ?>" />
<input type="hidden" name="directive_id" value="<?php echo $directive_id; ?>" />
<input type="hidden" name="xml_file" value="<?php echo $xml_file; ?>" />
<input type="hidden" name="engine_id" value="<?php echo $engine_id; ?>" />
<table class="transparent" align="center">
	<tr>
		<td class="container">
			<input type="hidden" name="sensor" id="sensor" value=""></input>
			<input type="hidden" name="sensor_list" id="sensor_list" value=""></input>
			<table class="transparent">
				<tr>
					<th style="white-space: nowrap; padding: 5px;font-size:12px">
						<?php echo gettext("Sensor"); ?>
					</th>
				</tr>
				<tr><td class="nobborder">&middot; <i><?php echo _("Empty selection means ANY sensor") ?></i></td></tr>
				<tr><td class="nobborder" id="sensor_msg" style="color:red"></td></tr>
				<tr>
					<td class="nobborder">
						<div id='ms_body'>
							<select id="sensorselect" class="multiselect_sensor" multiple="multiple" name="sensorselect[]" style="display:none;width:600px;height:300px">
							<?php
							$sensor_list = $rule->sensor;
							$_list_data  = Av_sensor::get_list($conn);
							$s_list      = $_list_data[0];
							foreach($s_list as $s_id => $s) {
								$sensor_name = $s['name'];
								$sensor_id = Util::uuid_format($s_id);
								$sensor_entities_arr = $s['ctx'];
								$sensor_entities = "";
								foreach ($sensor_entities_arr as $e_id => $e_name) {
									$sensor_entities .= " $e_id";
								}
								if ($sensor_list != "ANY" && $sensor_list != "" && in_array($sensor_id, preg_split('/,/', $sensor_list))) {
									echo "<option value='$sensor_id' ctx='$sensor_entities' selected='selected'>$sensor_name</option>\n";
								} else {
									echo "<option value='$sensor_id' ctx='$sensor_entities'>$sensor_name</option>\n";
								}
							}
							?>
							</select>
						</div>
					</td>
				</tr>
				<?php 
				for ($i = 1; $i <= $rule->level - 1; $i++) 
				{
					$sublevel = $i . ":SENSOR";
					//echo "<option value=\"$sublevel\">$sublevel</option>";
					?>
					<tr>
					    <td class="center nobborder">					
                            <input type="button" value="<?php echo _("Sensor from rule of level")." $i" ?>" onclick="document.getElementById('sensor').value='<?php echo $sublevel ?>';save_form()">
					    </td>
					</tr>
					<?php
					$sublevel = "!" . $i . ":SENSOR";
					?>
					<tr>
					    <td class="center nobborder">
					
					<input type="button" value="<?php echo "!"._("Sensor from rule of level")." $i" ?>" onclick="document.getElementById('sensor').value='<?php echo $sublevel ?>';save_form()">
					    </td>
					</tr>			
					<?php 
				} 
				?>
				<tr>
					<td class="center nobborder" style="padding-top:10px">
						<input type="button" class="av_b_secondary" value="<?php echo _("Cancel") ?>" onclick="parent.GB_close()"/>
						<input type="button" onclick="save_form()" value="<?php echo _("Modify")?>"/>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</form>
</body>
</html>
<?php
$db->close();
?>
