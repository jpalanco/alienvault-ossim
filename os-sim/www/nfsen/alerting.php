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
require_once ('classes/Util.inc');
Session::logcheck("environment-menu", "MonitorsNetflows");

$SumStat_scale = array ( '-', 'k', 'M', 'G', 'T', '%' );

$Cond_ops = array ( 'and', 'or' );

$SumStat_type_options = array ( 
	'Total flows', 	 'Total packets', 	 'Total bytes',
	'Flows/s', 'Packages/s', 'bits/s'
);

$SumStat_comp = array ( '&gt;', '&lt;', 'outside' );

$SumStat_comp_type = array ( 
	'Absolute value', 		'10 min average value', '30 min average value',
	'1 hour average value', '6 hour average value', '12 hour average value',
	'24 hour average value'
);

$FlowStat_type_options = array ( 
	'Flows', 	 'Packets',  'Bytes',
	'Packages/s', 'Bits/s', 'Bytes/Packet'
);


$FlowStat_scale = array ( '-', 'k', 'M', 'G', 'T' );

$FlowStat_type = array ( 
	'Any IP Address', 'SRC IP Address', 'DST IP Address', 'Any Port', 
	'SRC Port', 'DST Port',  'Any AS',  'SRC AS',   'DST AS',
	'Any interface', 'IN interface', 'OUT interface', 'Proto'
);

$Flow_comp = array ( '&gt;', '&lt;' );

$Trigger_list = array (
	'Each time',
	'Once only',
	'Once only, while condition = true',
);

$ActionList = array (
	'No action',
	'Send email',
	'Execute System command',
	'Call plugin',
);

$num_ConditionList = 6;
$ConditionList = array();
$ConditionList = array();

function alert_name_check(&$new_alert, $opts ) {

	$new_alert = preg_replace("/^\s+/", '', $new_alert);
	$new_alert = preg_replace("/\s+$/", '', $new_alert);
	if ( $new_alert == '' ) {
		SetMessage('error', "Empty alert name");
		return 1;
	}

	foreach ( $_SESSION['alertlist'] as $alert ) {
		if ( $new_alert == $alert ) {
			if ( $opts['must_exists'] ) {
				return 0;
			} else {
				SetMessage('error', "Alert '$alert' already exists");
				return 1;
			}
		}
	}
	return 0;

} // End of alert_name_check

function subject_validate (&$subject, $opts) {

	if (!get_magic_quotes_gpc()) {
   		$subject = addslashes($subject);
	} 
	return 0;

} // End of subject_validate

function channellist_validate (&$channels, $opts) {

	if ( count($channels) == 0 ) {
		SetMessage('error', "At least one channel must be selected");
		$channels = '';
		return 1;
	}

	foreach ( $channels as $channel ) {
		if ( !array_key_exists($channel, $_SESSION['profileinfo']['channel'] ) ) {
			SetMessage('error', "Channel '$channel' does not exist in profile");
			$channels = '';
			return 1;
		}
	}

	$channels = implode('|', $channels);

	return 0;

} // End of channellist_validate

function check_email_address(&$emaillist, $opts) {

	$emaillist = preg_replace("/^\s+/", '', $emaillist);
	$emaillist = preg_replace("/\s+$/", '', $emaillist);
	if ( $emaillist == '' )
		return 0;

	foreach ( explode(',',$emaillist) as $email ) {
		$email = preg_replace("/^\s+/", '', $email);
		$email = preg_replace("/\s+$/", '', $email);
		// Just make a rough check of characters. the backend will check the email address format
		if ( !preg_match('/^[A-Za-z0-9_\.@-]{6,128}$/', $email) ) {
			SetMessage('error', "Error illegal characters in email address '$email'");
			return 1;
		}
		return 0;
	}

} // End of check_email_address

function DisplayFilterTable($alert, $readonly) {

	$disabled = $readonly ? 'disabled' : '';
	$ro_text  = $readonly ? 'readonly' : '';
?>

<table class="ALERTTABLE" style="width:100%">
<tbody>
	<tr class="ALERTTABLE">
		<th class="ALERTTABLE" colspan="2">Filter applied to 'live' profile:</th>
	</tr>
	<tr>
	<td style="width:15%">
		<SELECT name="channellist[]" id="channellist" size="4" style="width:100%" multiple <?php echo $disabled?>>
<?php
		foreach ( explode('|', $alert['channellist']) as $channel ) {
			$_tmp[$channel] = 1;
		}
		foreach ( array_keys($_SESSION['profileinfo']['channel']) as $channel ) {
			$checked = array_key_exists($channel, $_tmp) ? 'selected' : '';
			print "<OPTION value='$channel' $checked>$channel</OPTION>\n";
		}
?>
		</select>
	</td>
	<td>
		<textarea name="filter" id="filter" multiline="true" wrap="phisical" rows="4" cols="70" 
			maxlength="10240" style="width:100%" <?php echo $disabled?>><?php
			foreach ( $alert['filter'] as $line ) {
				print Util::htmlentities($line) . "\n";
			}
?></textarea>
	</td>
	</tr>
</tbody>
</table>

<?php

} // End of DisplayFilterTable

function DisplaySumStatTable($alert, $readonly) {
	global $ConditionList;
	global $num_ConditionList;
	global $SumStat_type_options;
	global $SumStat_comp;
	global $SumStat_comp_type;

	global $SumStat_scale;
	global $Cond_ops;
	
	$checked = $alert['type'] == 0 ? 'checked' : '';
	$disabled = $readonly ? 'disabled' : '';
	$ro_text  = $readonly ? 'readonly' : '';
	$controls_display_style = $readonly ? "style='display:none'" : '';
?>

<table class="ALERTTABLE" width="100%">
<tbody>
	<tr class="ALERTTABLE">
		<th class="ALERTTABLE" colspan="8">
			<input type="radio" name="type" id="type0" value="0" 
				onClick="SetupCondition(<?php echo $num_ConditionList;?>, 0);"
				<?php echo "$checked $disabled";?>> 
			Conditions based on total flow summary:
		</th>
	</tr>

<?php 
	for ($i=0; $i < $num_ConditionList; $i++ ) {
		$display_style = $alert["visible_$i"] ? '' : 'style="display:none"';
		print "<tr class='ALERTTABLE' id='row_$i' $display_style>\n";
?>
	<td class="ALERTLIST"><?php echo $i?></td>
	<td style="width:4em">
<?php 
	if ( $i > 0 ) {
		print "<select name='op_$i' id='op_$i' size='1'$disabled>\n";
		for ($j=0; $j < count($Cond_ops); $j++ ) {
			$selected = $alert["op_$i"] == $j ? 'selected' : '';
			print "	<option value='$j' $selected>" . $Cond_ops[$j] . "</option>\n";
		}
		print "</select>\n";
	} else {
		print "<input type='hidden' name='op_0' value='0'>\n";
	}
?>
	</td>
	<td>
<?php 
	print "<input type='hidden' id='visible_$i' name='visible_$i' title='row state' ";
	print "value='" . Util::htmlentities($alert["visible_$i"]) . "'>\n";

	print "<select name='type_$i' id='type_$i' size='1' $disabled>\n";
		for ($j=0; $j < count($SumStat_type_options); $j++ ) {
			$selected = $alert["type_$i"] == $j ? 'selected' : '';
			print "	<option value='$j' $selected>" . $SumStat_type_options[$j] . "</option>\n";
		}
	print "</select>\n";

	print "<select name='comp_$i' id='comp_$i' onChange='SetConditionRow($i)' size='1' $disabled>\n";
	for ( $j=0; $j < count($SumStat_comp); $j++ ) {
		$selected = $alert["comp_$i"] == $j ? 'selected' : '';
		print "	<option value='$j' $selected>" . $SumStat_comp[$j] . "</option>\n";
	}
	print "</select>\n";

	print "<select name='comp_type_$i' id='comp_type_$i' onChange='SetConditionRow($i)' size='1' $disabled>\n";
	for ( $j=0; $j < count($SumStat_comp_type); $j++ ) {
		$selected = $alert["comp_type_$i"] == $j ? 'selected' : '';
		print "	<option value='$j' $selected>" . $SumStat_comp_type[$j] . "</option>\n";
	}
	print "</select>\n";
?>
	</td>
	<td style="text-align:right;width:3em">
	&nbsp;
	<span id="<?php echo 'plus-label_'; echo $i ?>" style="display:none">+</span>
	<span id="<?php echo 'minus-label_'; echo $i ?>" style="display:none">-</span>
	<span id="<?php echo 'plusminus-label_'; echo $i ?>" style="display:none">+/-</span>
	&nbsp;
	</td>
	<td>
<?php
	print "<input name='comp_value_$i' id='comp_value_$i' type='text' 
		value='" . Util::htmlentities($alert["comp_value_$i"]) . "' size='5' $disabled>\n";
	print "<select name='scale_$i' id='scale_$i' size='1' $disabled>\n";
	for ( $j=0; $j < count($SumStat_scale); $j++ ) {
		$selected = $alert["scale_$i"] == $j ? 'selected' : '';
		print "	<option value='$j' $selected>" . $SumStat_scale[$j] . "</option>\n";
	}
	print "</select>\n";
	print "<input type='hidden' name='stat_type_$i' id='stat_type_$i' value='0'>\n";
?>
	</td>
	<td style="width:4em">
	</td>
	<td style="width:2em">
<?php if ( $i > 0 ) {
   		print "<a href='#null' onclick='DeleteRow($i);' title='Delete row' ><IMG SRC='icons/trash.png' ";
		print "name='delete_row_$i' id='delete_row_$i' border='0' align='right' alt='trash' $controls_display_style></a>\n";
} ?>
	</td>
	<td style="width:2em">
<?php 
	$_i = $i + 1;
	if ( $_i < $num_ConditionList ) { 
   		print "<a href='#null' onclick='EnableRow($_i);' title='Add new row'><IMG SRC='icons/plus.png' ";
		print "name='add_row_$i' id='add_row_$i' border='0' align='right' alt='plus icon' $controls_display_style></a>\n";
	} ?>
	</td>
</tr>

<?php } ?>

</tbody>
</table>

<?php 

} // End of DisplaySumStatTable


function DisplayTopNTable($alert, $readonly) {
	global $num_ConditionList;
	global $ConditionList;
	global $FlowStat_type;
	global $Flow_comp;
	global $FlowStat_type_options;

	global $FlowStat_scale;
	global $Cond_ops;

	$checked = $alert['type'] == 1 ? 'checked' : '';
	$disabled = $readonly ? 'disabled' : '';
	$ro_text  = $readonly ? 'readonly' : '';
	$controls_display_style = $readonly ? "style='display:none'" : '';

?>

<table class="ALERTTABLE" width="100%">
<tbody>
	<tr class="ALERTTABLE">
		<th class="ALERTTABLE" colspan="6">
			<input type="radio" name="type" id="type1" value="1" 
				onClick="SetupCondition(<?php echo $num_ConditionList;?>, 1);"
				<?php echo "$checked $disabled";?>> 
			Conditions based on individual Top 1 statistics:
		</th>
	</tr>

<?php 
	for ($i=$num_ConditionList; $i < (2*$num_ConditionList); $i++ ) {
		$display_style = $alert["visible_$i"] ? '' : 'style="display:none"';
		print "<tr class='ALERTTABLE' id='row_$i' $display_style>\n";
?>
	<td class="ALERTLIST"><?php echo $i?></td>
	<td style="width:4em">
<?php 
	if ( $i > $num_ConditionList ) { 
		print "<select name='op_$i' id='op_$i' size='1' $disabled>\n";
		for ($j=0; $j < count($Cond_ops); $j++ ) {
			$selected = $alert["op_$i"] == $j ? 'selected' : '';
			print "	<option value='$j' $selected>" . $Cond_ops[$j] . "</option>\n";
		}
		print "</select>\n";
	} else {
		print "<input type='hidden' name='op_$num_ConditionList' value='0'>\n";
	}
?>
	</td>
	<td>
<?php 
	print "<input type='hidden' name='visible_$i' id='visible_$i' title='row state' ";
	print "value='" . Util::htmlentities($alert["visible_$i"]) . "'>\n";

	print "<select name='type_$i' id='type_$i' size='1' $disabled>\n";
		for ($j=0; $j < count($FlowStat_type_options); $j++ ) {
			$selected = $alert["type_$i"] == $j ? 'selected' : '';
			print "	<option value='$j' $selected>" . $FlowStat_type_options[$j] . "</option>\n";
		}
	print "</select>\n&nbsp;of Top 1 &nbsp;";

	print "<select name='stat_type_$i' id='stat_type_$i' size='1' $disabled>\n";
		for ($j=0; $j < count($FlowStat_type); $j++ ) {
			$selected = $alert["stat_type_$i"] == $j ? 'selected' : '';
			print "	<option value='$j' $selected>" . $FlowStat_type[$j] . "</option>\n";
		}
	print "</select>\n";

	print "<select name='comp_$i' id='comp_$i' size='1' $disabled>\n";
	for ( $j=0; $j < count($Flow_comp); $j++ ) {
		$selected = $alert["comp_$i"] == $j ? 'selected' : '';
		print "	<option value='$j' $selected>" . $Flow_comp[$j] . "</option>\n";
	}
	print "</select>\n";

	print "<input name='comp_value_$i' id='comp_value_$i' type='text' 
		value='" . Util::htmlentities($alert["comp_value_$i"]) . "' size='5' $disabled>\n";

	print "<select name='scale_$i' id='scale_$i' size='1' $disabled>\n";
	for ( $j=0; $j < count($FlowStat_scale); $j++ ) {
		$selected = $alert["scale_$i"] == $j ? 'selected' : '';
		print "	<option value='$j' $selected>" . $FlowStat_scale[$j] . "</option>\n";
	}
	print "</select>\n";
	print "<input type='hidden' name='comp_type_$i' id='comp_type_$i' value='0'>\n";
?>
	</td>
	<td style="width:4em">
	</td>
	<td style="width:2em">
<?php 
	$_j = $i - $num_ConditionList;
	if ( $i > $num_ConditionList ) {
   		print "<a href='#null' onclick='DeleteRow($i);' title='Delete row' ><IMG SRC='icons/trash.png' ";
		print "name='delete_row_$i' id='delete_row_$i' border='0' align='right' alt='trash' $controls_display_style></a>\n";
} ?>
	</td>
	<td style="width:2em">
<?php 
	$_i = $i+1; $_j = $_i - $num_ConditionList;
	if ( $_i < (2*$num_ConditionList) ) { 
		print "<a href='#null' onclick='EnableRow($_i);' title='Add new row' ><IMG SRC='icons/plus.png' ";
		print "name='add_row_$i' id='add_row_$i' border='0' align='right' alt='plus icon' $controls_display_style></a>\n";
	} ?>
	</td>
</tr>

<?php } ?>

</tbody>
</table>

<?php
} // End of DisplayTopNTable

function DisplayPluginTable($alert, $readonly) {
	global $num_ConditionList;

	$checked = $alert['type'] == 2 ? 'checked' : '';
	$alert_condition_plugin = $_SESSION['alert_condition_plugin'];
	$disabled = $readonly || count($alert_condition_plugin) == 0 ? 'disabled' : '';

?>

<table class="ALERTTABLE" width="100%">
<tbody>
	<tr class="ALERTTABLE">
		<th class="ALERTTABLE" colspan="6">
			<input type="radio" name="type" id="type2" value="2" onClick="SetupCondition(<?php echo $num_ConditionList;?>, 2);"
				<?php echo "$checked $disabled";?>> 
			Conditions based on plugin:
	<span id="plugin_condition_select" style="display:none">
	<select name="plugin_condition" id="plugin_condition" size="1" <?php echo $disabled?>>
<?php
	if ( count($alert_condition_plugin) == 0 ) {
		print "	<option value='-1' >No alert plugins available</option>\n";
	} else {
		$i = 0;
		foreach ( $alert_condition_plugin as $plugin) {
			$selected = $alert['condition'][0] == $plugin ? 'selected' : '';
			print "	<option value='$i' $selected>" . Util::htmlentities($plugin) . "</option>\n";
			$i++;
		}
	}
?>
	</select>
	</span>
		</th>
	</tr>
</tbody>
</table>

<?php

} // End of DisplayPluginTable

function DisplayTriggerTable($alert, $readonly) {
	global $Trigger_list;
	$disabled = $readonly ? 'disabled' : '';
?>

<table class="ALERTTABLE" width="100%">
<tbody>
	<tr class="ALERTTABLE">
		<th class="ALERTTABLE" colspan="6">
			Trigger:
		</th>
	</tr>
	<tr>
	<td>

	<select name="trigger_type" id="trigger_type" size="1" <?php echo $disabled?>>
<?php
	for ( $j=0; $j < count($Trigger_list); $j++ ) {
		$selected = $alert['trigger_type'] == $j ? 'selected' : '';
		print "<option value='$j' $selected>" . $Trigger_list[$j] . "</option>\n";
	}
?>
	</select>
	after 
	<select name="trigger_number" id="trigger_number" size="1" <?php echo $disabled?>>
<?php
	for ( $j=1; $j < 10; $j++ ) {
		$selected = $alert['trigger_number'] == $j ? 'selected' : '';
		print "<option value='$j' $selected>$j</option>\n";
	}
?>
	</select>x condition = true, and block next trigger for 
	<select name="trigger_blocks" id="trigger_blocks" size="1" <?php echo $disabled?>>
<?php
	for ( $j=0; $j < 10; $j++ ) {
		$selected = $alert['trigger_blocks'] == $j ? 'selected' : '';
		print "<option value='$j' $selected>$j</option>\n";
	}
?>
	</select> cycles

	</td>
	</tr>

</tbody>
</table>

<?php

} // End of DisplayTriggerTable

function DisplayActionTable($alert, $readonly) {
	global $ActionList;
	global $AllowsSystemCMD;

	$alert_action_plugin = $_SESSION['alert_action_plugin'];
	$disabled = $readonly  ? 'disabled' : '';
	if ( count($alert_action_plugin) == 0 ) {
		$infotext = "No plugin available";
	} else {
		$infotext = '';
	}
	$display_system_cmd = $AllowsSystemCMD ? '' : "style='display:none'";
?>
<table class="ALERTTABLE" width="100%">
<tbody>
	<tr class="ALERTTABLE">
		<th class="ALERTTABLE" colspan="3">
			Action:
		</th>
	</tr>
	<tr>
		<td colspan="3">
<?php $checked = $alert['action_type'] == 0 ? 'checked' : ''; ?>
			<input type="checkbox" name="action_0" id="action_0" value="0" onClick="SetAction(0);"
				<?php echo "$checked $disabled";?>>&nbsp;No action
		</td>
	</tr>
	<tr>
		<td>
<?php $checked = ($alert['action_type'] & 1) > 0  ? 'checked' : ''; ?>
			<input type="checkbox" name="action_1" id="action_1" value="1" onClick="SetAction(1);"
				<?php echo "$checked $disabled";?>>&nbsp;Send alert email
		</td>
		<td>To:</td>
		<td>
<?php $_disabled = $disabled || $checked == '' ? 'disabled' : '';
		print "<input name='action_email' id='action_email' type='text' style='width:100%' value='";
		print Util::htmlentities($alert['action_email']);
		print "' size='64' $_disabled>\n";
?>
		</td>
	</tr>
	<tr>
		<td></td>
		<td>Subject:</td>
		<td>
<?php
		print " <input name='action_subject' id='action_subject' type='text' style='width:100%' value='";
		print Util::htmlentities($alert['action_subject']);
		print "' size='64' $_disabled>\n";
?>
		</td>
	</tr>
	<tr <?php echo $display_system_cmd?>>
		<td>
<?php $checked = ($alert['action_type'] & 2) > 0  ? 'checked' : ''; ?>
			<input type="checkbox" name="action_2" id="action_2" value="2" onClick="SetAction(2);"
				<?php echo "$checked $disabled";?>>&nbsp;Run system command
		</td>
		<td colspan="2">
<?php $_disabled = $disabled || $checked == '' ? 'disabled' : '';
		print "<input name='action_system' id='action_system' type='text' style='width:100%' value='";
		print Util::htmlentities($alert['action_system']);
		print "' size='48' $_disabled>\n";
?>
		</td>
	</tr>
	<tr>
		<td>
<?php 
		$disabled = $readonly || count($alert_action_plugin) == 0 ? 'disabled' : '';
		$checked = ($alert['action_type'] & 4) > 0  ? 'checked' : ''; 
?>
			<input type="checkbox" name="action_3" id="action_3" value="3" onClick="SetAction(3);"
				<?php echo "$checked $disabled";?>>&nbsp;Call plugin:
		</td>
		<td colspan="2">
<?php
			$_disabled = $disabled || $checked == '' ? 'disabled' : '';
?>
			<select name="action_plugin" id="action_plugin" size="1" <?php echo $_disabled?>>
<?php
	if ( count($alert_action_plugin) == 0 ) {
		print "	<option value='-1' >No alert plugins available</option>\n";
	} else {
		$i = 0;
		foreach ( $alert_action_plugin as $plugin) {
			$selected = $alert['action_plugin'] == $plugin ? 'selected' : '';
			print "	<option value='$i' $selected>" . Util::htmlentities($plugin) . "</option>\n";
			$i++;
		}
	}
?>
			</select>
		</td>
	</tr>

</tbody>
</table>

<?php

} // End of DisplayActionTable

function DisplayEventTable($alert, $readonly) {
	global $self;
	global $num_ConditionList;

	$controls_display_style = $readonly ? "" : "style='display:none'";
	$updated = $alert['updated_str'];
?>

<table class="ALERTTABLE" id="EventTable" width="100%" <?php echo $controls_display_style?>>
<tbody>
	<tr class="ALERTTABLE">
		<th class="ALERTTABLE">
			Alert Infos:
		</th>
	</tr>
	<tr>
		<td>Last cycle: <?php echo Util::htmlentities($updated);?></td>
	</tr>
	<tr>
		<td class="MYVALUE">
<?php
			$arg = implode ( ' ', array( $_SESSION['alert_display']['avg_type'], $alert['updated'] - 86400, $alert['updated']) );
			$arg = urlencode($arg);
			
			$js_arg = implode ( ' ', array( $alert['updated'] - 86400, $alert['updated']) );
			$js_arg = urlencode($js_arg);
			$js_arg = "'" . $alert['name'] . "', '$js_arg'";
?>
			<iframe id="avg_frame" src="rrdgraph.php?cmd=get-alertgraph&amp;alert=<?php echo urlencode($alert['name'])?>&amp;arg=<?php echo $arg?>" 
				align="left" scrolling="no" frameborder="0" marginwidth="0" marginheight="0" width="680" height="280"></iframe>
		</td>
	</tr>
	<tr>
		<td class="MYVALUE">
			<table class="ALERTDETAILS">
			<thead>
			<tr>
				<th></th>
				<th></th>
				<th><b>Last</b></th>
				<th><b>Avg 10m</b></th>
				<th><b>Avg 30m</b></th>
				<th><b>Avg 1h</b></th>
				<th><b>Avg 6h</b></th>
				<th><b>Avg 12h</b></th>
				<th><b>Avg 24h</b></th>
			</tr>
			</thead>
			<tbody>
			<tr>
				<td rowspan="2">
<?php $checked = $_SESSION['alert_display']['avg_type'] == 0 ? 'checked' : ''; ?>
				<input type="radio" name="avg_type" value="0" onChange="SetAlertStatus(0, <?php echo Util::htmlentities($js_arg); ?>);" <?php echo $checked; ?>> 
				</td>
				<td rowspan="2"><b>Flows</b></td>
<?php
			$vec = explode(':', $alert['last_flows']);
			foreach ( $vec as $val ) {
				$val = ScaleValue($val, 1);
				print "<td style='text-align:right'>".Util::htmlentities($val)."</td>\n";
			}
?>
			</tr>
			<tr>
<?php
			foreach ( $vec as $val ) {
				$val = ScaleValue($val, 300);
				print "<td style='text-align:right'>".Util::htmlentities($val)."</td>\n";
			}
?>
			</tr>
			<tr>
				<td rowspan="2">
<?php $checked = $_SESSION['alert_display']['avg_type'] == 1 ? 'checked' : ''; ?>
				<input type="radio" name="avg_type" value="1" onChange="SetAlertStatus(1, <?php echo Util::htmlentities($js_arg); ?>);" <?php echo $checked; ?>> 
				</td>
				<td rowspan="2"><b>Packets</b></td>
<?php
			$vec = explode(':', $alert['last_packets']);
			foreach ( $vec as $val ) {
				$val = ScaleValue($val, 1);
				print "<td style='text-align:right'>".Util::htmlentities($val)."</td>\n";
			}
?>
			</tr>
			<tr>
<?php
			foreach ( $vec as $val ) {
				$val = ScaleValue($val, 300);
				print "<td style='text-align:right'>".Util::htmlentities($val)."</td>\n";
			}
?>
			</tr>
			<tr>
				<td rowspan="2">
<?php $checked = $_SESSION['alert_display']['avg_type'] == 2 ? 'checked' : ''; ?>
				<input type="radio" name="avg_type" value="2" onChange="SetAlertStatus(2, <?php echo Util::htmlentities($js_arg); ?>);" <?php echo $checked; ?>> 
				</td>
				<td rowspan="2"><b>Bytes</b></td>
<?php
			$vec = explode(':', $alert['last_bytes']);
			foreach ( $vec as $val ) {
				$val = ScaleBytes($val, 1, 1000.0);
				print "<td style='text-align:right'>".Util::htmlentities($val)."</td>\n";
			}
?>
			</tr>
			<tr>
<?php
			foreach ( $vec as $val ) {
				$val = ScaleBytes($val, 300, 1000.0);
				print "<td style='text-align:right'>".Util::htmlentities($val)."</td>\n";
			}
?>
			</tr>
			</tbody>
			</table>
		</td>
	</tr>
	<tr>
<?php
		if ( array_key_exists('last_condition', $alert) ) {
			$vec = explode(':', $alert['last_condition']);
			$num = count($vec);
			print "<td><p></td>\n";
		} else {
			$num = 0;
			print "<td><p><b>Last conditions not available</b></td>\n";
		}
?>
	</tr>
	<tr>
		<td>
<?php
		if ( $num ) {
			print "<table class='ALERTDETAILS'>\n";
			print "<tr>\n";
			$offset = $alert['type'] == 1 ? $num_ConditionList : 0;
			print "<td><b>Conditions:</b></td>\n";
			for ( $i=0; $i< $num; $i++ ) {
				print "<td><b>";
				print $i+$offset;
				print "</b></td>\n";
			}
			print "<td><b>Final:</b></td>";
			print "</tr>\n";
			print "<tr>\n";
			print "<td><b>State:</b></td>\n";
			for ( $i=0; $i< $num; $i++ ) {
				print "<td>";
				print $vec[$i] ? "True" : "False";
				print "</td>\n";
			}
			print "<td><b>";
            print $alert['final_condition'] ? "True" : "False";
			print "</b></td>";

			print "</tr>\n";
			print "</table>\n";
		}
?>
		</td>
	</tr>
</tbody>
</table>

<?php

} // End of DisplayEventTable


function DisplayAlertTable($alertstatus) {
	global $self;

?>
<form action="<?php  echo $self;?>" method="POST" onSubmit="return ProcessForm()">
<input type="hidden" name="alert" id="alert" value="">
<div style="margin-top:20px; margin-left:20px;">
<table class="ALERTTABLE" width="800px">
<tbody>
	<tr class="ALERTTABLE">
		<th class="ALERTTABLE" colspan="5">
			<input type="image" name="new_alert" value="new_alert" title="Add new alert" 
				src="icons/plus.png" align="right">
		Alerts overview:
	</th>
	</tr>
<?php if ( count($alertstatus) == 0 ) { ?>
	<tr class="ALERTTABLE">
		<td class="MYVALUE"><b>No Alerts defined.</b></td>
	</tr>
<?php } else { ?>
	<tr class="ALERTTABLE">
		<th class="ALERTTABLE">No.</th>
		<th class="ALERTTABLE">Status</th>
		<th class="ALERTTABLE">Name</th>
		<th colspan="2"class="ALERTTABLE">Last Triggered</th>
	</tr>
<?php 
		$i = 0;
		foreach ( $alertstatus as $alert ) {
			$i++;
			list($alertname, $status, $info, $updated) = explode("|", $alert);
			switch ($status) {
				case 0:
					$background = '#8FFFFF';
					$label = 'inactive';
					break;
				case 1:
					$background = '#bbff55';
					$label = 'armed';
					break;
				case 3:
					$background = '#ffff11';
					$label = "armed $info";
					break;
				case 5:
					$background = '#ff5500';
					$label = "fired $info";
					break;
				case 0xD:
					$background = '#660fff';
					$label = "blocked $info";
					break;
				default:
					$background = '#FFFFFF';
					$label = "Ukn $status";

			}

?>

	<tr class='ALERTTABLE'>
		<td class="ALERTLIST"><?php echo $i;?></td>
		<td class="MYVALUE"style="width:6em;background-color:<?php echo Util::htmlentities($background);?>"><?php echo Util::htmlentities($label);?></td>
		<td class="MYVALUE"><b><?php echo Util::htmlentities($alertname);?></b></td>
		<td class="MYVALUE"><?php echo Util::htmlentities($updated);?></td>
		<td class="MYVALUE" style="width:5em">
			<input type="image" name="view_alert" value="<?php echo Util::htmlentities($alertname);?>" title="View alert" src="icons/spyglas.png" onClick="SetAlertName('<?php echo Util::htmlentities($alertname);?>');" align="left">
			<input type="image" name="delete_alert" value="<?php echo Util::htmlentities($alertname);?>" title="Delete alert" src="icons/trash.png" onClick="ConfirmDeleteAlert('<?php echo Util::htmlentities($alertname);?>');" align="right">
		</td>
	</tr>
<?php 
		}
	} 
?>
	
</tbody>
</table>

</div>
</form>
<?php

} // End of DisplayAlertTable

function DisplayAlert($new_alert, $alert) {
	global $self;

	$readonly = !$new_alert;
	$checked = $alert['status'] == 'enabled'? 'checked' : '';
	$cond_plugins   = count($_SESSION['alert_condition_plugin']);
	$action_plugins = count($_SESSION['alert_action_plugin']);
?>
<form action="<?php  echo $self;?>" method="POST" onSubmit="return ProcessForm()">
<input type="image" name="invisible" value="invisible" src="icons/invisible.png" onClick="NoCRSubmit();">
<table class="ALERTTABLE" id="alert_details" width="800px">
<tbody>
<?php if ( $new_alert ) { ?>
	<tr class="ALERTTABLE" colspan="2">
		<th class="ALERTTABLE" colspan="3">New alert</th>
	</tr>
	<tr class="ALERTTABLE">
		<td class="ALERTLIST" style="width:5em">Name</td>
		<td class="MYVALUE">
		<input type="text" name="alert" value="<?php  echo Util::htmlentities($alert['alert']);?>" size="24">
		</td>
	</tr>
	<tr class="ALERTTABLE">
		<td class="ALERTLIST" style="width:5em">Status</td>
		<td class="MYVALUE">
			<input type="checkbox" name="status" id="status"  value="enabled" <?php echo $checked;?>>enabled
		</td>
	</tr>
<?php } else {
		$status = $alert['trigger_status'];
		switch ($alert['trigger_status']) {
				case 0:
					$background = '#8FFFFF';
					$label = 'inactive';
					break;
				case 1:
					$background = '#bbff55';
					$label = 'armed';
					break;
				case 3:
					$background = '#ffff11';
					$label = 'armed ' . $alert['trigger_info'];
					break;
				case 5:
					$background = '#ff5500';
					$label = 'fired'. $alert['trigger_info'];;
					break;
				case 0xD:
					$background = '#660fff';
					$label = 'blocked '. $alert['trigger_info'];
					break;
				default:
					$background = '#FFFFFF';
					$label = "Ukn $status";
		}
?>
	<tr class="ALERTTABLE">
		<th class="ALERTTABLE" colspan="4">Alerts details: <?php echo Util::htmlentities($alert['name']);?></th>
	</tr>
	<tr class="ALERTTABLE">
		<th class="ALERTTABLE">Trigger</th>
		<th class="ALERTTABLE">Status</th>
		<th class="ALERTTABLE" colspan="2">Last Triggered</th>
	</tr>
	<tr class='ALERTTABLE'>
		<td class="MYVALUE" style="width:6em;background-color:<?php echo Util::htmlentities($background);?>"><?php echo Util::htmlentities($label);?></td>
		<td class="MYVALUE">
			<input type="checkbox" name="status" id="status" onclick="this.form.submit();" 
			value="enabled" <?php echo $checked;?>>enabled
			<input type="checkbox" name="status_hidden" id="status_hidden" 
			value="enabled" style="display:none" <?php echo $checked;?>>
		</td>
		<td class="MYVALUE">
<?php
			if ( $alert['trigger_type'] == 1 ) {
				print "<input type='image' name='arm_trigger' value='" . Util::htmlentities($alert['name']) . 
					"' title='Arm trigger' src='icons/trigger.png' alt='arm trigger' align='right'>\n";
			}
			print Util::htmlentities($alert['last_trigger']);
?>
		</td>
		<td class="MYVALUE" style="width:5em">
			<input type="hidden" name="alert" id='alert' value="<?php  echo Util::htmlentities($alert['name']);?>" >
   			<a href="#null" onclick="" title="Edit alert" ><IMG SRC="icons/edit.png" onClick="EnableEdit(<?php echo "$cond_plugins, $action_plugins"; ?>);"
				name="edit_row" border="0" align="left" alt="trash"></a>
			<input type="image" name="delete_alert" value="" title="Delete alert" src="icons/trash.png" 
				onClick="ConfirmDeleteAlert('<?php echo Util::htmlentities($alert['name']);?>');" align="right">
		</td>
	</tr>
<?php }
	print "<tr><td colspan='4'>\n";
	DisplayFilterTable($alert, $readonly);
	print "</td></tr>\n";
	print "<tr><td colspan='4'>\n";
	DisplaySumStatTable($alert, $readonly);
	print "</td></tr>\n";
	print "<tr><td colspan='4'>\n";
	DisplayTopNTable($alert, $readonly);
	print "</td></tr>\n";
	print "<tr><td colspan='4'>\n";
 	DisplayPluginTable($alert, $readonly);
	print "</td></tr>\n";
	print "<tr><td colspan='4'>\n";
 	DisplayTriggerTable($alert, $readonly);
	print "</td></tr>\n";
	print "<tr><td colspan='4'>\n";
 	DisplayActionTable($alert, $readonly);
	print "</td></tr>\n";

	// for a new alert display the controls to add the alert
	if ( $new_alert ) {
?>
	<tr class='ALERTTABLE'>
    	<td class="ALERTTABLE" colspan="2">
			<input name='new_alert_cancel' value='Cancel' type='submit'>
			<input name='new_alert_commit' value='Create Alert' type='submit'>
		</td>
	</tr>
<?php

	} else {
		print "<tr><td colspan='4'>\n";
		DisplayEventTable($alert, $readonly);
		print "</td></tr>\n";
	}
	$controls_display_style = $new_alert || $readonly ? "style='display:none'" : '';
	print "<tr><td colspan='4' id='row_controls' $controls_display_style>\n"
?>
		<input name="edit_alert_cancel" value="Cancel" type="submit" align='right'>
		<input name="edit_alert_commit" value="Commit Changes" type="submit" align='right'>
	</td></tr>

</tbody>
</table>
</form>
<?php

} // End of DisplayAlert

function UpdateAlertList() {

   	$cmd_out = nfsend_query("get-alertlist", array(), 0);
   	$alertlist   			= array_key_exists('alertlist', $cmd_out) ? 
								$cmd_out['alertlist'] : array();
   	$alertstatus 			= array_key_exists('alertstatus', $cmd_out) ? 
								$cmd_out['alertstatus'] : array();
   	$alert_condition_plugin = array_key_exists('alert_condition_plugin', $cmd_out) ? 
								$cmd_out['alert_condition_plugin'] : array();
   	$alert_action_plugin 	= array_key_exists('alert_action_plugin', $cmd_out) ? 
								$cmd_out['alert_action_plugin'] : array();

	$_SESSION['alertlist']   			= $alertlist;
	$_SESSION['alertstatus'] 			= $alertstatus;
	$_SESSION['alert_condition_plugin'] = $alert_condition_plugin;
	$_SESSION['alert_action_plugin'] 	= $alert_action_plugin;
	unset($_SESSION['alertinfo']);

} // End of UpdateAlertList

function UpdateAlert($alert) {
	global $num_ConditionList;

	$alertinfo = nfsend_query("get-alert", array( 'alert' => $alert), 0);
   	if ( !is_array($alertinfo) ) {
		$_SESSION['action'] = 'list';
		return;
   	} else {
		$_SESSION['action'] = 'details';
   	}

   	$cmdout = nfsend_query("get-alertfilter", array('alert' => $alert ), 0);
   	if ( !is_array($cmdout) ) {
		$_SESSION['action'] = 'list';
		return;
   	} 
	$alertinfo['filter'] = $cmdout['alertfilter'];


	if ( $alertinfo['type'] == 0 ) {
		$i = 0;
	} else {
		$i = $num_ConditionList;
	}

	// preset all possible conditions
	for ( $i=0; $i < (2*$num_ConditionList); $i++ ) {
		$alertinfo["visible_$i"]	= 0;
		$alertinfo["op_$i"]			= 0;
		$alertinfo["type_$i"]		= 0;
		$alertinfo["comp_$i"]		= 0;
		$alertinfo["comp_type_$i"]	= 0;
		$alertinfo["stat_type_$i"]	= 0;
		$alertinfo["comp_value_$i"]	= 0;
		$alertinfo["scale_$i"]		= 0;
	}

	// if it's a type 0/1 type, break up the condition and fill the appropriate variables for displaying
	if ( $alertinfo['type'] != 2 ) {
		$i = $alertinfo['type'] == 1 ? $num_ConditionList : 0;
		// preset all configured conditions
		foreach ( $alertinfo['condition'] as $condition ) {
			list($op,$type,$comp,$comp_type,$stat_type,$comp_value,$scale) = explode(':', $condition);
	
			$alertinfo["visible_$i"]	= 1;
			$alertinfo["op_$i"]			= $op;
			$alertinfo["type_$i"]		= $type;
			$alertinfo["comp_$i"]		= $comp;
			$alertinfo["comp_type_$i"]  = $comp_type;
			$alertinfo["stat_type_$i"]  = $stat_type;
			$alertinfo["comp_value_$i"] = $comp_value;
			$alertinfo["scale_$i"]		= $scale;
			$i++;
		}
	}
	$_SESSION['alertinfo']   = $alertinfo;

	if ( !array_key_exists('avg_type', $_COOKIE) ) {
		$_SESSION['alert_display']['avg_type'] = 0;
	} else {
		$_type = $_COOKIE['avg_type'];
		if ( is_numeric($_type) && ($_type >= 0 && $_type <= 2 ) ) 
			$_SESSION['alert_display']['avg_type'] = $_type;
		else
			$_SESSION['alert_display']['avg_type'] = 0;
	}

} // End of UpdateAlert

function Process_alert_tab ($tab_changed, $profile_changed) {
	global $num_ConditionList;
	global $ConditionList;
	global $num_ConditionList;
	global $ConditionList;
	global $ActionList;
	global $FlowStat_type;
	global $SumStat_type_options;
	global $SumStat_comp_type;
	global $SumStat_scale;


	// register 'get-alertgraph' command for rrdgraph.php
	if ( !array_key_exists('rrdgraph_cmds', $_SESSION) || 
		 !array_key_exists('get-alertgraph', $_SESSION['rrdgraph_cmds']) ) {
		$_SESSION['rrdgraph_cmds']['get-alertgraph'] = 1;
		$_SESSION['rrdgraph_getparams']['alert'] = 1;
	} 

	$_SESSION['action'] = 'list';

	// Delete an alert?
	if ( array_key_exists('delete_alert_x', $_POST )) {
		$parse_opts = array( 
			"alert" 	=> array( 
					"required" => 1, "default"  => NULL, 
					"allow_null" => 0,
					"match" => $_SESSION['alertlist'], 
					"validate" => null,
					"must_exist" => 1),
		);
		list ($form_data, $has_errors) = ParseForm($parse_opts);

		if ( $has_errors )
			return;

   		$cmd_out = nfsend_query("delete-alert", $form_data, 0);
		$_SESSION['action'] 	= 'list';
   		
		UpdateAlertList();
		return;
	} 

	// Arm the alert
	if ( array_key_exists('arm_trigger_x', $_POST )) {
		$parse_opts = array( 
			"alert" 	=> array( 
					"required" => 1, "default"  => NULL, 
					"allow_null" => 0,
					"match" => $_SESSION['alertlist'], 
					"validate" => null,
					"must_exist" => 1),
		);
		list ($form_data, $has_errors) = ParseForm($parse_opts);

		if ( $has_errors )
			return;

   		$cmd_out = nfsend_query("arm-alert", $form_data, 0);

		$_SESSION['action'] 	= 'list';
 		UpdateAlert($_SESSION['alertinfo']['name']);
   		
		return;
	} 


	// cancel a new alert dialog
	if ( array_key_exists('new_alert_cancel', $_POST ) ) {
		$_SESSION['action'] = 'list';
		return;
	}
	// provide the add new alert dialog?
	if ( array_key_exists('new_alert_x', $_POST ) ) {
		$_SESSION['action'] = 'new';
		$_SESSION['refresh'] = 0;

		// preset alert info for new alert
		$alertinfo['alert'] 		 = '';
		$alertinfo['type'] 			 = 0;
		$alertinfo['visible_0'] 	 = 1;
		$alertinfo['status'] 		 = 'disabled';
		$alertinfo['trigger_type']   = 0;
		$alertinfo['trigger_status'] = 0;
		$alertinfo['trigger_number'] = 0;
		$alertinfo['trigger_blocks'] = 0;
		$alertinfo['action_type'] 	 = 0;
		$alertinfo['action_email'] 	 = '';
		$alertinfo['action_subject'] = 'Alert triggered';
		$alertinfo['action_system']  = '';
		$alertinfo['filter'] 	 	 = array();
		$alertinfo['channellist'] 	 = implode('|', array_keys($_SESSION['profileinfo']['channel']) );
		for ( $i=0; $i < (2*$num_ConditionList); $i++ ) {
			$alertinfo["visible_$i"]    = 0;
			$alertinfo["op_$i"] 	    = 0;
			$alertinfo["type_$i"] 	    = 0;
			$alertinfo["comp_$i"] 	    = 0;
			$alertinfo["comp_type_$i"]  = 0;
			$alertinfo["stat_type_$i"]  = 0;
			$alertinfo["comp_value_$i"] = 0;
			$alertinfo["scale_$i"] 	    = 0;
		}

		$_SESSION['alertinfo']   	 = $alertinfo;

		// disable page refresh
		$_SESSION['refresh']		 = 0;

		return;
	}

	// create the new alert
	$ModifyOrNew = NULL;
	if ( array_key_exists('new_alert_commit_x', $_POST ) || array_key_exists('new_alert_commit', $_POST )) {
		$ModifyOrNew = 'new';
	}
	if ( array_key_exists('edit_alert_commit', $_POST ) ) {
		$ModifyOrNew = 'modify';
	}

	if ( $ModifyOrNew != NULL ) {
		$parse_opts = array( 
			"alert" 		=> array( 
							"required" => 1, "default"  => NULL, 
							"allow_null" => 0,
						    "match" => "/^[A-Za-z0-9][A-Za-z0-9\-+_]*$/" , 
							"validate" => 'alert_name_check',
							"must_exists" => $ModifyOrNew == 'modify'),
			"channellist"	=> array( "required" => 0, 
							"default"  => '',
						    "allow_null" => 0,
				  		    "match" 	 => null,
						    "validate" => 'channellist_validate'),
			"filter"		=> array( "required" => 0, 
							"default"  => NULL,
							"allow_null" => 1,
					  		"match" => "/^[\s!-~]*$/", 
							"validate" => 'filter_validate'),
			"type" 			=> array( 
							"required" => 1, "default"  => 0, 
							"allow_null" => 1,
							"match" => array( 0, 1, 2), 
							"validate" => null),
			"status" 		=> array( 
							"required" => 0, "default"  => 'disabled', 
							"allow_null" => 1,
					  		"match" => array('enabled', 'disabled'),
							"validate" => null),
			"trigger_type" 	=> array( 
							"required" => 1, "default"  => 0, 
							"allow_null" => 1,
							"match" => array( 0, 1, 2), 
							"validate" => null),
			"trigger_number" => array( 
							"required" => 1, "default"  => 1, 
							"allow_null" => 0,
							"match" => range( 1, 9), 
							"validate" => null),
			"trigger_blocks" => array( 
							"required" => 1, "default"  => 0, 
							"allow_null" => 1,
							"match" => range( 0, 9), 
							"validate" => null),
			"plugin_condition" => array( 
							"required" => 0, "default"  => -1, 
							"allow_null" => 0,
							"match" => range(-1, count($_SESSION['alert_condition_plugin'])),
							"validate" => null),
			"action_plugin" => array( 
							"required" => 0, "default"  => -1, 
							"allow_null" => 0,
							"match" => range(-1, count($_SESSION['alert_action_plugin'])),
							"validate" => null),
			"action_email" => array( 
							"required" => 0, "default"  => '', 
							"allow_null" => 1,
							"match" => null,
							"validate" => 'check_email_address'),
			"action_subject" => array( 
							"required" => 0, "default"  => 'Alert triggered', 
							"allow_null" => 1,
				  		    "match" => "/^[\s!-~]+$/", 
							"validate" => 'subject_validate'),
			"action_system" => array( 
							"required" => 0, "default"  => null, 
							"allow_null" => 1,
				  		    "match" => "/^[\s!-~]+$/", 
							"validate" => null),
		);
		for ( $i=0; $i < (2*$num_ConditionList); $i++ ) {
			$name = "op_$i";
			$parse_opts[$name] = array( 
							"required" => 0, "default"  => 0, 
							"allow_null" => 1,
							"match" => array(0, 1), 
							"validate" => null);
			$name = "visible_$i";
			$parse_opts[$name] = array( 
							"required" => 0, "default"  => 0, 
							"allow_null" => 1,
							"match" => array(0, 1), 
							"validate" => null);
			$name = "type_$i";
			$parse_opts[$name] = array( 
							"required" => 0, "default"  => 0, 
							"allow_null" => 1,
							"match" => range(0, count($SumStat_type_options)-1), 
							"validate" => null);
			$name = "comp_$i";
			$parse_opts[$name] = array( 
							"required" => 0, "default"  => 0, 
							"allow_null" => 1,
							"match" => array(0, 1, 2), 
							"validate" => null);
			$name = "comp_type_$i";
			$parse_opts[$name] = array( 
							"required" => 0, "default"  => 0, 
							"allow_null" => 1,
							"match" => range(0, count($SumStat_comp_type)-1), 
							"validate" => null);
			$name = "stat_type_$i";
			$parse_opts[$name] = array( 
							"required" => 0, "default"  => 0, 
							"allow_null" => 1,
							"match" => range(0, count($FlowStat_type)-1), 
							"validate" => null);
			$name = "comp_value_$i";
			$parse_opts[$name] = array( 
							"required" => 0, "default"  => 0, 
							"allow_null" => 1,
							"match" => "/^\d+$/",
							"validate" => null);
			$name = "scale_$i";
			$parse_opts[$name] = array( 
							"required" => 0, "default"  => 0, 
							"allow_null" => 1,
							"match" => range(0, count($SumStat_scale)-1), 
							"validate" => null);
		}
		list ($alertinfo, $has_errors) = ParseForm($parse_opts);
		$form_values = $alertinfo;

		if ( $has_errors ) {
			if ( $ModifyOrNew == 'new' ) {
				$_SESSION['action'] 	= 'new';
				$_SESSION['refresh'] 	= 0;
			} else {
				$_SESSION['action'] 	= 'details';
				$_SESSION['refresh'] 	= 0;
			}
			return;
		}

		// process action types
		$action_type = 0;
		for ($i=1; $i < 4; $i++ ) {
			if ( array_key_exists("action_$i", $_POST ) ) {
				$action_type += 1 << ($i-1);
			}
		}
		$alertinfo['action_type'] = $action_type;
		$form_values['action_type'] = $action_type;

		if ( $alertinfo['type'] == 2 ) {
			$lim = 2*$num_ConditionList;
		} else {
			$lim = $alertinfo['type'] == 0 ? 0 : $num_ConditionList;
		}

		if ( $alertinfo['action_plugin'] > -1 ) {
			$alertinfo['action_plugin'] = $_SESSION['alert_action_plugin'][$alertinfo['action_plugin']];
		} else {
			unset($alertinfo['action_plugin']);
		}

		// prepare condition array
		$condition = array();
		if ( $alertinfo['type'] == 2 ) {
			$condition[] = $_SESSION['alert_condition_plugin'][$alertinfo['plugin_condition']];
		} else {
			for ( $i = 0; $i < ( 2*$num_ConditionList); $i++ ) {
				if ( $i >= $lim && $i < ( $lim + $num_ConditionList) &&  $alertinfo["visible_$i"] ) {
					// add to condition array
					$condition[] = implode(':', array( 
						$alertinfo["op_$i"], $alertinfo["type_$i"], $alertinfo["comp_$i"], 
						$alertinfo["comp_type_$i"], $alertinfo["stat_type_$i"], 
						$alertinfo["comp_value_$i"], $alertinfo["scale_$i"])
					);
				} 
			}
		}

		for ( $i = 0; $i < ( 2*$num_ConditionList); $i++ ) {
			// delete other condition values
			unset($alertinfo["visible_$i"]);
			unset($alertinfo["op_$i"]);
			unset($alertinfo["type_$i"]);
			unset($alertinfo["comp_$i"]);
			unset($alertinfo["comp_type_$i"]);
			unset($alertinfo["stat_type_$i"]);
			unset($alertinfo["comp_value_$i"]);
			unset($alertinfo["scale_$i"]);
		}
		unset($alertinfo['plugin_condition']);

		$alertinfo['condition'] = $condition;


ob_start();
print "Add/modify alert - alertinfo\n";
foreach($alertinfo as $aik => $aiv) {
    echo Util::htmlentities($aik)." => ".Util::htmlentities($aiv)."\n";
}
ReportLog(ob_get_contents());
ob_clean();

		if ( $ModifyOrNew == 'new' ) {
   			$cmd_out = nfsend_query("add-alert", $alertinfo, 0);
   			if ( !is_array($cmd_out) ) {
				$_SESSION['action'] 	= 'new';
				$_SESSION['alertinfo']  = $form_values;
				return;
   			} 

			// Update alert list
 			UpdateAlertList();
		} else {
   			$cmd_out = nfsend_query("modify-alert", $alertinfo, 0);
   			if ( !is_array($cmd_out) ) {
				$_SESSION['action'] 	= 'details';
				$_SESSION['refresh'] 	= 0;
				return;
   			} 
		}

		// prepare details view of new alert
 		UpdateAlert($alertinfo['alert']);

		return;
	}

	// status change
	$status = 'none';
	if ( array_key_exists('status', $_POST ) && !array_key_exists('status_hidden', $_POST ) ) {
		// status set to enabled
		$status = 'enabled';
	}
	if ( !array_key_exists('status', $_POST ) && array_key_exists('status_hidden', $_POST ) ) {
		// status set to disabled
		$status = 'disabled';
	}
	if ( $status != 'none' ) {
		// redisplay alert
		$_SESSION['action'] 	= 'details';
		$_SESSION['refresh'] 	= 0;
   		$cmd_out = nfsend_query("modify-alert", array( 
									'alert' => $_SESSION['alertinfo']['name'], 
									'status' => $status
								), 0);
   		if ( !is_array($cmd_out) ) {
			return;
   		} 
 		UpdateAlert($_SESSION['alertinfo']['name']);

		return;
	}

	if ( array_key_exists('view_alert_x', $_POST ) ) {
		$parse_opts = array( 
			"alert" 	=> array( 
					"required" => 1, "default"  => NULL, 
					"allow_null" => 0,
					"match" => $_SESSION['alertlist'], 
					"validate" => null,
					"must_exist" => 1),
		);
		list ($form_data, $has_errors) = ParseForm($parse_opts);

		if ( $has_errors )
			return;

		$_SESSION['refresh'] 	= 0;
		UpdateAlert($form_data['alert']);

		return;
	}

	if ( array_key_exists('edit_alert_cancel', $_POST ) ) {
		// redisplay current alert
		$_SESSION['action']  = 'details';
		$_SESSION['refresh'] = 0;
		return;
	}

	// everything else - show alert list
	UpdateAlertList();

	return;

} // End of Process_alert_tab

function DisplayAlerts() {
	global $num_ConditionList;
?>
    <script language="Javascript" src="js/alerting.js" type="text/javascript">
    </script>
<?php

ReportLog("Alert action eval: " . $_SESSION['action']);
	$setup = 0;
	switch ( $_SESSION['action'] ) {
		case 'list':
			$alertstatus = $_SESSION['alertstatus'];
			DisplayAlertTable($alertstatus);
			break;
		case 'new':
			$alertinfo = $_SESSION['alertinfo'];
			$setup = 1;
			DisplayAlert(1, $alertinfo);
			break;
		case 'details':
			$setup = 1;
			$alertinfo = $_SESSION['alertinfo'];
			DisplayAlert(0, $alertinfo);
			break;
		default:
			print "<h3>ERROR action: " . Util::htmlentities($_SESSION['action']) . "</h3>";
			
			break;
	}
	unset($_SESSION['action']);

	if ( $setup ) { 
?>
    <script language="Javascript" type="text/javascript">
		window.onload=function() {
			for (i=0; i< <?php echo Util::htmlentities($num_ConditionList); ?>; i++ )
				SetConditionRow(i);
			SetupCondition(<?php echo Util::htmlentities($num_ConditionList).", " . Util::htmlentities($alertinfo['type'])?>);
		}
    </script>
<?php 
	}

} // End of DisplayAlerts


