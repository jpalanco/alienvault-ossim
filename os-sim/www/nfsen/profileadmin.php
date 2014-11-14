<?php

require_once 'av_init.php';
Session::logcheck("environment-menu", "MonitorsNetflows");


function ExpireString($hours) {

	$expire = '';
	if ( $hours == 0 ) {
		$expire = "never";
	} else {
		if ( $hours > 24 ) {
			$days  = (int)($hours/24);
			$hours = $hours % 24;
	
			$expire = "$days Day";
			$expire .= $days > 1 ? "s ": " ";
		} 
		if ( $hours > 0 ) {
			$expire .= "$hours  Hour";
			if ( $hours > 1 ) 
				$expire .= "s";
		}
	}

	return $expire;

} // End of ExpireString

function ProgressBar ( $state ) {

?>
	<tr>
    <th class="STATTABLE" colspan="2">
	<table style="width: 100%; text-align: left;" cellpadding='2' cellspacing='2'>
		<tbody>
			<tr>
<?php if ( $state > 1 ) { ?>
				<td class="progressbar"; style="width:<?php echo intval($state);?>%;"><?php echo intval($state);?>%</td>
				<td bgcolor=#FFFFFF></td>
<?php } else { ?>
				<td colspan="2" bgcolor=#FFFFFF style="font-size:10pt;text-align:left;width:100%"><?php echo intval($state);?>%</td>
<?php } ?>
			</tr>
		</tbody>
	</table>
	</th>
	</tr>
<?php

} # End of ProgressBar

function EditChannel ($is_live_profile, $new_channel, $channelinfo, $liveprofile, $num_pos, $num_neg) {
	global $self;
	global $HTMLDIR;
		
	// compile available source list
	foreach ( $liveprofile['channel'] as $key => $value ) {
		$_tmp[$key] = $key;
	}

	// remove configured sources - remaining sources in $_tmp
	$sourcelist = array();
	if ( !is_null($channelinfo['sourcelist']) ) {
		$sourcelist = explode('|', $channelinfo['sourcelist']);
		foreach ( $sourcelist as $key ) {
			if ( array_key_exists($key, $_tmp) ) {
				unset($_tmp[$key]);
			} 
		}
	}

	$available = array_keys($_tmp);
	sort($available);
	sort($sourcelist);
?>

	<div style="margin-top:20px; margin-left:20px;">
	<form name="edit_channel" id="edit_channel_form" action="<?php  echo $self;?>" method="POST" 
		onSubmit="return ValidateEditForm()" >
<?php	if ( $new_channel ) {  ?>
	<input type="image" border="0" name="add_channel_commit" value="add_channel_commit" src="icons/invisible.png">
<?php } else { ?>
	<input type="image" border="0" name="edit_channel_commit" value="edit_channel_commit" src="icons/invisible.png">
<?php } ?>

	<table class="STATTABLE" cellspacing="4"  cellpadding="4">
		<tbody>
		<tr class="CHANNELLIST">
<?php	if ( $new_channel ) { ?>
			<th class="CHANNELLIST" colspan="2">
				Channel name
			</th>
			<th class="CHANNELLIST" colspan="2">
				<input type="text" name="name" id="name" value="<?php echo Util::htmlentities($channelinfo['name']);?>" 
					style="width:100%">
			</th>
<?php	} else {  ?>
			<th class="CHANNELLIST" colspan="3">
				<?php echo Util::htmlentities($channelinfo['name']);?>
				<input type="hidden" name="name" id="name" value="<?php echo Util::htmlentities($channelinfo['name']);?>">
			</th>
			<th class="CHANNELLIST" style="text-align:right" >
				<input type="image" name="delete_channel_commit" value="<?php echo Util::htmlentities($channelinfo['name']);?>"
					title="Delete channel" src="icons/trash.png" onClick="confirm_delete=1;">
			</th>
<?php 	} ?>
		</tr>

		<tr class="CHANNELLIST">
			<td class="CHANNELLIST">Colour:</td>
			<td class="MYVALUE" id="colour_cell" style="background-color:<?php echo Util::htmlentities($channelinfo['colour']);?>" >Enter new value</td>
			<td class="MYVALUE" colspan="2">
				<input type='text' name='colour' id="colour" value='<?php echo Util::htmlentities($channelinfo['colour']); ?>'  
					SIZE="7" MAXLENGTH="7" style="font-size:12px;">
				or <select name="colour_select" id="colour_selector" onChange="SelectColour()" size="1">
					<option value="0" selected>Select a colour from</option>
					<option value="1">Default Colour Palette</option>
					<option value="2">Colour Picker</option>
            	</select>
			</td>
		</tr>
		<tr>
			<td class="CHANNELLIST">Sign:</td>
			<td>
				<select name="sign" id="sign_selector" onChange="SetOrderSelector(0, 0);">
					<option value="+" <?php if ( $channelinfo['sign'] == "+") print "selected"  ?> >+</option>
					<option value="-" <?php if ( $channelinfo['sign'] == "-") print "selected"  ?> >-</option>
				</select>
			</td>
						
			<td class="CHANNELLIST">Order:</td>
			<td class="MYVALUE">
				<select name="order" id="order_selector" onChange="">
					<option value="0">empty</option>
				</select>
			</td>
		</tr>
		
		<tr class="CHANNELLIST">
			<td class="CHANNELLIST">Filter:</td>
			<td class="MYVALUE" colspan=3>
				<textarea name="filter" rows="3" cols="25" style="width: 100%;" 
					<?php if ( $is_live_profile ) print 'readonly="readonly"' ?> >
<?php 			
				if (array_key_exists('filter', $channelinfo)) 
				{
					foreach ($channelinfo['filter'] as $line) 
					{
						print Util::htmlentities($line)."\n";
					}
				}
?></textarea>
			</td>
      	</tr>
      		
		<tr class="CHANNELLIST">
			<td class="CHANNELLIST">Sources:</td>
			<td class="MYVALUE" colspan=3>
			<table>
				<tr>
					<td class="MYVALUE" >Available Sources</td>
					<td class="MYVALUE" ></td>
					<td class="MYVALUE" >Selected Sources</td>
				</tr>
				<tr>
					<td><select name="available[]" id="available_sources" size="6" style="width: 100%;" 
							multiple="multiple">
<?php 
	foreach ( $available as $key ) {
		print "<option value='$key'>" . $key . "</option>\n";
	}
?>
						</select>
					</td>
					<td align="center" valign="middle">
						<input type="button" onClick="move('channel_sources','available_sources')" 
							value="<<" <?php if ( $is_live_profile ) print 'disabled' ?>>
						<input type="button" onClick="move('available_sources','channel_sources')" 
							value=">>" <?php if ( $is_live_profile ) print 'disabled' ?>>	
					</td>
					<td>
						<select multiple size="6" name="configured[]" id="channel_sources" style="width:100%">
<?php 
				foreach ( $sourcelist as $key ) {
					print "<option value='".Util::htmlentities($key)."'>" . Util::htmlentities($key) . "</option>\n";
				}
?>
						</select>				
					</td>
				</tr>
			</table>
			</td>
		</tr>
		<tr>
			<td colspan="4">
<?php	if ( $new_channel ) { ?>
			<input name="add_channel_cancel" value="Cancel" type="submit" onClick="abort_dialog=1;" align='right'>
			<input name="add_channel_commit" value="Add Channel" type="submit" onClick="abort_dialog=0;" align='right'>
<?php	} else {	?>
			<input name="edit_channel_cancel" value="Cancel" type="submit" onClick="abort_dialog=1;" align='right'>
			<input name="edit_channel_commit" value="Commit Changes" type="submit" onClick="abort_dialog=0;" align='right'>
<?php	}	?>
			</td>
		</tr>
	</table>
	</form>
	</div>

	<div id="dwindow" style="position:absolute;background-color:#AAAAAA;left:0px;top:0px;display:none" >
			<iframe id="colourframe" src="about:blank" width="100%" frameborder="1" height="100%" marginheight="0" marginwidth="0"></iframe>
	</div>

<?php 

	print "<script type='text/javascript'>\n";
	print "num_pos = $num_pos;\n";
	print "num_neg = $num_neg;\n";
	$order = $channelinfo['order'];
	print "SetOrderSelector(1, ".Util::htmlentities($order).");\n";
	if ( $new_channel ) 
		print "document.getElementById('name').select()\n";
	print "</script>\n";

} // End of EditChannel

function PrintChannelStat($i, $channelinfo, $visible) 
{
	global $self;
	
	$chan_id   = "ch$i";
	$chan_name = $channelinfo['name'];

	if ($visible == 1) 
	{
		$display_style = '';
		$arrow_style = 'style="display:none"';
	} 
	else 
	{
		$display_style = 'style="display:none"';
		$arrow_style = '';
	}

?>
	<table class="CHANNELLIST" cellpadding="4" cellspacing="4" id="<?php echo $chan_id;?>" >
		<tbody>
		<tr class="CHANNELLIST">
			<th class="CHANNELLIST" colspan="5">
				<a href="#null" onclick="ShowHide('<?php echo $chan_id;?>')" id="<?php echo $chan_id . 'r';?>" 
					title="show channel" <?php echo $arrow_style?> ><IMG SRC="icons/arrow.blue.right.png" 
					name="<?php echo $chan_id;?> right" border="0" alt="arrow right"></a>
				<a href="#null" onclick="ShowHide('<?php echo $chan_id;?>')" <?php echo $display_style?> id="<?php echo $chan_id . 'd';?>" 
					title="hide channel"><IMG SRC="icons/arrow.blue.down.png" 
					name="<?php echo $chan_id;?> down" border="0" alt="arrow down"></a>	
				<?php echo Util::htmlentities($chan_name);?>
			</th>
			<th class="CHANNELLIST" style="text-align:right" >
				<form style="display: inline;" name="profileform" id="profileform" action="<?php  echo $self;?>" method="POST" >
				<input type="hidden" name="edit_channel" title="Edit channel" value="<?php echo Util::htmlentities($chan_name);?>">
				<input type="image" name="edit_channel" title="Edit channel" value="<?php echo Util::htmlentities($chan_name);?>"
					src="icons/edit.png" align="right">
				</form>
			</th>
		</tr>

		<tr class="CHANNELLIST" <?php echo $display_style?> id="<?php echo $chan_id . '_1';?>">
			<td class="CHANNELLIST">Colour:</td>
			<td class="MYVALUE" style="background-color:<?php echo Util::htmlentities($channelinfo['colour']);?>" >
				<?php echo Util::htmlentities($channelinfo['colour']);?></td>
			<td class="CHANNELLIST">Sign:</td><td class="MYVALUE"><?php echo Util::htmlentities($channelinfo['sign']);?></td>
			<td class="CHANNELLIST">Order:</td><td class="MYVALUE"><?php echo Util::htmlentities($channelinfo['order']);?></td>
		</tr>
		
		<tr class="CHANNELLIST" <?php echo $display_style?> id="<?php echo $chan_id . '_2';?>">
			<td class="CHANNELLIST">Filter:</td>
			<td class="MYVALUE" colspan=5>
				<textarea name="<?php echo $chan_id . '_filter';?>" rows="3" cols="20" style="width: 100%;" 
					readonly="readonly"><?php 			
				if ( array_key_exists('filter', $channelinfo) ) {
					foreach ( $channelinfo['filter'] as $line ) {
						print "$line\n";
					}
				}
?></textarea>
			</td>
      	</tr>
      		
		<tr class="CHANNELLIST" <?php echo $display_style?> id="<?php echo $chan_id . '_3';?>">
			<td class="CHANNELLIST">Sources:</td>
			<td class="MYVALUE" colspan=5>
				<select name="<?php echo $chan_id . '_channels';?>" size="4" style="width: 100%;" disabled="disabled" multiple="multiple">
<?php 
	$sourcelist = explode('|', $channelinfo['sourcelist']);	

	for ( $j=0; $j < count($sourcelist); $j++ ) {
		print "<option>" . Util::htmlentities($sourcelist[$j]) . "</option>\n";
	}
?>
				</select>
			</td>
		</tr>
	</table>
      	
<?php 

} // End of PrintChannelStat

function ProfileDialog ( ) {

	global $self;
	global $desc_expire;

	$is_shadow = ($_SESSION['profileinfo']['type'] & 4) > 0;
	$can_edit = $_SESSION['profileinfo']['type'] == 0 || $_SESSION['profileinfo']['type'] == 2;
	if ( $_SESSION['profileinfo']['type'] == 0 ) {
		$type = 'live';
	} else {
		$type  = ($_SESSION['profileinfo']['type'] & 3 ) == 2 ? "Continous" : "History";
		$type .= ($_SESSION['profileinfo']['type'] & 4) > 0  ? '&nbsp;/&nbsp;shadow' : '';
	}

	$tstart  = UNIX2ISO($_SESSION['profileinfo']['tstart']);
	$tend 	 = UNIX2ISO($_SESSION['profileinfo']['tend']);
	$tupdate = UNIX2ISO($_SESSION['profileinfo']['updated']);
	$pattern = "/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})/";
	$replacement = "$1-$2-$3-$4-$5";
	$tstart  = preg_replace($pattern, $replacement, $tstart);
	$tend	 = preg_replace($pattern, $replacement, $tend);
	$tupdate = preg_replace($pattern, $replacement, $tupdate);
	$size	 = ScaleBytes($_SESSION['profileinfo']['size'], 1, 1024.0);
	$maxsize = $_SESSION['profileinfo']['maxsize'] ? 
					ScaleBytes($_SESSION['profileinfo']['maxsize'], 1, 1024.0) : 'unlimited';
	$description = htmlspecialchars(implode("\n", $_SESSION['profileinfo']['description']), ENT_QUOTES);;
	$status	= $_SESSION['profileinfo']['status'];
	$locked	= $_SESSION['profileinfo']['locked'] == 1 ? " - locked" : "";
	$opt_delete = $status == 'OK'  && ( $_SESSION['profile'] != 'live' );
	$is_live	= $_SESSION['profile'] == 'live' ;
	$can_commit = ($status == 'new' || $status == 'stalled') && (count($_SESSION['profileinfo']['channel']) > 0);
	$can_add_channel = $status == 'new' || $status == 'stalled' || ($status == 'OK' && ($_SESSION['profileinfo']['type'] & 2) > 0 );

	if ( preg_match("/built (\d+\.{0,1}\d*)/", $status, $matches) ) {
		$progress   = $matches[1];
		$opt_cancel = array_key_exists('cancel-inprogress', $_SESSION) ? 0 : 1;
	} else {
		$progress   = NULL;
		$opt_cancel = 0;
		if ( array_key_exists('cancel-inprogress', $_SESSION) ) {
			unset($_SESSION['cancel-inprogress']);
		}
	}

	$expire = ExpireString($_SESSION['profileinfo']['expire']);

	$cmd_out = nfsend_query("get-profilegroups", array(), 0);
	if ( is_array($cmd_out) ) {
		$profilegroups   = $cmd_out['profilegroups'];
	} else {
		$profilegroups   = array();
		$profilegroups[] = '.';
	}
	$profilegroups[] = 'New group ...';

	if ( $_COOKIE['extended_channellist'] == 1) {
		$style_arrow_down = '';
		$style_arrow_right = 'style="display:none"';
	} else {
		$style_arrow_down = 'style="display:none"';
		$style_arrow_right = '';
	}

?>
<div style="margin-top:20px; margin-left:20px;">
<table class="STATTABLE" cellspacing="4"  cellpadding="4">
<tr class="STATTABLE">
    <th class="STATTABLE" colspan='2'>
<?php if ( $opt_delete ) { ?>
	<form style="display:inline;" name="profileform" id="profileform" action="<?php  echo $self;?>" method="POST" onSubmit="return ConfirmDeleteProfile(<?php print '\'' . Util::htmlentities($_SESSION['profile']) . '\', \'' . Util::htmlentities($_SESSION['profilegroup']) . '\''; ?>);">
    	<input type="image" name="deleteprofile" value="<?php echo Util::htmlentities($_SESSION['profileswitch'])?>" title="Delete this profile" src="icons/trash.png" align="right" >
		<input type="hidden" name="switch" value="<?php echo Util::htmlentities($_SESSION['profileswitch'])?>" >
	</form>
<?php } if ( $opt_cancel ) { ?>
	<form style="display:inline;" name="profileform" id="profileform" action="<?php  echo $self;?>" method="POST" onSubmit="return ConfirmCancelBuild(<?php print '\'' . Util::htmlentities($_SESSION['profile']) . '\', \'' . Util::htmlentities($_SESSION['profilegroup']) . '\''; ?>);">
    	<input type="image" name="cancelbuild" value="<?php echo Util::htmlentities($_SESSION['profileswitch'])?>" title="Cancel building profile" src="icons/cancel.png" align="right" >
		<input type="hidden" name="switch" value="<?php echo Util::htmlentities($_SESSION['profileswitch'])?>" >
	</form>
<?php }

	if ( !is_null($progress) ) 
		print "Building ";
?>
		Profile: &nbsp;<?php echo Util::htmlentities($_SESSION['profile']);?>
	</th>
</tr>
<?php if ( !is_null($progress) ) 
	ProgressBar($progress);
?>
<tr class="STATTABLE" id="ed_group_ro" >
    <td class="STATTABLE" >Group:</td> 
    <td class="MYVALUE">
<?php if ( !$is_live ) { ?>
    	<a href="#null" onclick="EnableEdit('ed_group');" title="Change group" ><IMG SRC="icons/edit.png" name="edit_group" border="0" align="right" alt="edit icon"></a>
<?php } 
		print $_SESSION['profilegroup'] == '.' ? "(nogroup)" : Util::htmlentities($_SESSION['profilegroup']);
?>
	</td>
</tr>
<tr class="STATTABLE" id="ed_group_rw" style="display:none">
    <td class="STATTABLE" style="color:red"> Group: </td>
    <td class="MYVALUE">
	<form style="display: inline;" name="profileform" id="profileform" action="<?php  echo $self;?>" method="POST" >
    	<select name="groupselect" id="groupselect" onChange="HandleGroupSelect()" style="width:100%">
<?php
		foreach ( $profilegroups as $group ) {
			$selected = $_SESSION['profilegroup'] == $group ? "selected" : '';
			if ( $group == '.' ) 
				$group = '(nogroup)';
			print "<option value='$group' $selected>$group</option>\n";
		}
?>
    	</select><br>
		<input type="text" name="profilegroup" id="profilegroup" value=""
			SIZE="32" MAXLENGTH="32" style="width:100%;display:none"><br>
		<hr class="hrule">
		<span style="color:red"><input name="regroup" value="Enter new group" type="submit" onclick=""></span>
		<a href="#null" onMouseover="showhint('Select an existing or create a new group', 
this, event, '200px')"><IMG SRC="icons/help.png" border="0" alt="help icon"></a>
	</form>
	</td >
</tr>
<tr class="STATTABLE" id="ed_description_ro">
    <td class="STATTABLE">Description:</td> 
    <td class="MYVALUE">
    	<table style="width: 100%; text-align: left;" cellpadding='0' cellspacing='0'>
		<tr>
			<td>
				<textarea name="description" rows="4" cols="40" style="width:100%" readonly ><?php  echo Util::htmlentities($description); ?></textarea>
			</td>
			<td style="vertical-align:top;">
			<a href="#null" onclick="EnableEdit('ed_description');" title="Change Description" ><IMG SRC="icons/edit.png" name="edit_description" border="0" align="right" alt="edit icon" ></a>
			</td>
		</tr>
		</table>
    </td>
</tr>
<tr class="STATTABLE" id="ed_description_rw" style="display: none">
    <td class="STATTABLE" style="color:red"> Description:</td> 
    <td class="MYVALUE">
	<form style="display: inline;" name="profileform" id="profileform" action="<?php  echo $self;?>" method="POST" >
		<textarea name="description" cols="25" rows="3" style="width:100%"><?php  echo Util::htmlentities($description); ?></textarea>
		<span style="color:red"><input name="edit" value="Enter new description" type="submit" onclick=""></span>
	</form>
    </td>
</tr>


<tr class="STATTABLE" id="ed_type_ro">
    <td class="STATTABLE" >Type:</td> 
    <td class="MYVALUE">
<?php 
		$current_type = $_SESSION['profileinfo']['type'];
		if ( $current_type != 0 ) { ?>
    	<a href="#null" onclick="EnableEdit('ed_type');" title="Change profile type" ><IMG SRC="icons/edit.png" name="edit_profile_type" border="0" align="right" alt="edit icon"></a>
<?php } 
		print $type; 
?>
	</td>
</tr>
</tr>
<tr class="STATTABLE" id="ed_type_rw" style="display:none">
    <td class="STATTABLE" style="color:red"> Type: </td>
    <td class="MYVALUE">
	<form style="display: inline;" name="profiletypeform" action="<?php  echo $self;?>"
		onSubmit="return ConfirmNewType(<?php echo Util::htmlentities($current_type); ?>)" method="POST" >
		<input type="radio" name="profile_type" value="2" <?php echo $current_type == 2 ? 'checked' : ''; ?>> 
		Continous Profile<br>
<?php
		$disable_mode = ( $current_type == 5 || $current_type == 6 ) ? 'disabled ' : '';
?>

		<input type="radio" name="profile_type" value="1" <?php echo $disable_mode; echo $current_type == 1 ? 'checked' : ''; ?>> 
		History Profile<br>
		<hr class="hrule">
		<input type="radio" name="profile_type" value="6" <?php echo $current_type == 6 ? 'checked' : ''; ?>> 
		Continous Profile&nbsp;/&nbsp;shadow<br>
		<input type="radio" name="profile_type" value="5" <?php echo $current_type == 5 ? 'checked' : ''; ?>> 
		History Profile&nbsp;/&nbsp;shadow<br>
		<hr class="hrule">
		<input name="edit" value="Commit new type" type="submit" >
		<a href="#null" onMouseover="showhint('<b>Continous Profile</b><br>\
Switching to a continuous profile starts profiling.<br>\
<b>History Profile</b><br>Switching to a history profile stops profiling.<br> \
<b>Shadow Profile</b><br>Switching to a shadow profile deletes all profiled netflow data to free disk space. \
All graphics data remains available. Processing netflow is done by applying the appropriate channel filters \
to \'live\' netflow data first.<br>When switching back from shadow to a real profile, data collecting starts \
again.', this, event, '200px')"><IMG SRC="icons/help.png" border="0" alt="help icon"></a>
	</form>
	</td >
</tr>
<tr class="STATTABLE">
    <td class="STATTABLE">Start:</td> 
    <td class="MYVALUE">
		<input type="text" name="ro_tstart" value="<?php echo $tstart; ?>" SIZE="17" MAXLENGTH="16" 
			style="font-size:12px;" readonly>
	</td>
</tr>
<tr class="STATTABLE">
    <td class="STATTABLE">End:</td> 
    <td class="MYVALUE">
		<input type="text" name="ro_tend" value="<?php echo $tend; ?>" SIZE="17" MAXLENGTH="16" 
			style="font-size:12px;" readonly>
	</td>
</tr>
<tr class="STATTABLE">
    <td class="STATTABLE">Last Update:</td> 
    <td class="MYVALUE">
		<input type="text" name="ro_tupdate" value="<?php echo $tupdate; ?>" SIZE="17" MAXLENGTH="16" 
			style="font-size:12px;" readonly>
	</td>
</tr>



<tr class="STATTABLE">
    <td class="STATTABLE">Size:</td> 
    <td class="MYVALUE"> <?php  echo Util::htmlentities($size); ?> </td>
</tr>

<tr class="STATTABLE" id="ed_max_ro">
    <td class="STATTABLE">Max. Size:</td> 
    <td class="MYVALUE">
<?php if ( $can_edit ) { ?>
    	<a href="#null" onclick="EnableEdit('ed_max');" title="Change max. size" ><IMG SRC="icons/edit.png" name="edit_maxsize" border="0" align="right" alt="edit icon"></a>
<?php } ?>
		<input type="text" name="profile_maxsize" value="<?php echo Util::htmlentities($maxsize); ?>" SIZE="16" MAXLENGTH="16" 
			style="font-size:12px;" readonly>
    </td>
</tr>

<tr class="STATTABLE" id="ed_max_rw" style="display: none">
    <td class="STATTABLE" style="color:red">Max. Size:</td> 
    <td class="MYVALUE">
	<form style="display: inline;" name="profileform" id="profileform" action="<?php  echo $self;?>" method="POST" >
		<input type='text' name='profile_maxsize' value='<?php echo Util::htmlentities($maxsize); ?>'  SIZE="16" MAXLENGTH="16" style="font-size:12px;">
		<hr class="hrule">
		<span style="color:red"><input name="edit" value="Enter new value" type="submit" onclick=""></span>
		<a href="#null" onMouseover="showhint('Maximum size, this profile may grow.\
Any number is taken as <b>MB</b>, unless another scale is specified such as <b>K, M, G, T</b>\
 or <b>KB, MB, GB, TB</b>. If set to <b>0</b>, no size limit applies.<br>\
Ex. 300, 300M, 2G etc.', this, event, '200px')"><IMG SRC="icons/help.png" border="0" alt="help icon"></a>
	</form>
    </td>
</tr>


<tr class="STATTABLE" id="ed_exp_ro" >
    <td class="STATTABLE">Expire:</td> 
    <td class="MYVALUE">
<?php if ( $can_edit ) { ?>
		<a href="#null" onclick="EnableEdit('ed_exp');" title="Change expire size" ><IMG SRC="icons/edit.png" name="edit_expire" border="0" align="right" alt="edit icon"></a>
<?php } ?>
		<input type="text" name="profile_expire" value="<?php  echo Util::htmlentities($expire); ?>" SIZE="16" MAXLENGTH="16" style="font-size:12px;" readonly >
    </td>
</tr>

<tr class="STATTABLE" id="ed_exp_rw" style="display: none">
    <td class="STATTABLE" style="color:red">Expire:</td> 
    <td class="MYVALUE">
	<form style="display: inline;" name="profileform" id="profileform" action="<?php  echo $self;?>" method="POST" >
		<input type='text' name='profile_expire' value='<?php  echo Util::htmlentities($expire); ?>' SIZE='16' MAXLENGTH='16' style="font-size:12px;" >
		<hr class="hrule">
		<span style="color:red"><input name="edit" value="Enter new value" type="submit"></span>
		<a href="#null" onMouseover="showhint('Expire time. This specifies the maximum lifetime for this profile. \
Data files older than this, will be deleted. Any number is taken as <b>hours</b> unless another scale is specified \
such as <b>d, day, days</b> and/or <b>h, hour, hours</b>. If set to <b>0</b> or <b>never</b>, no time limit applies.<br>\
Ex. 72, 72h, 4d 12h, 14days etc.', this, event, '200px')"><IMG SRC="icons/help.png" border="0" alt="help icon"></a>
	</form>
    </td>
</tr>


<tr class="STATTABLE">
    <td class="STATTABLE">Status:</td> 
    <td class="MYVALUE">
<?php if ( $can_commit ) { ?>
		<form style="display: inline;" name="profileform" id="profileform" action="<?php  echo $self;?>" method="POST" >
		<input type="image" name="commit_profile" value="commit_profile" title="Commit new profile" src="icons/ok.png" align="right">
		</form>
<?php } ?>
		<?php  echo Util::htmlentities($status)." ".Util::htmlentities($locked) ?>
	</td>
</tr>
<tr class="STATTABLE">
	<td class="CHANNELTITLE" colspan="2">
<?php if ( $can_add_channel ) { ?>
		<form style="display: inline;" name="profileform" id="profileform" action="<?php  echo $self;?>" method="POST" >
		<input type="image" name="add_channel" value="add_channel" title="Add new channel" src="icons/plus.png" align="right">
		</form>
<?php } ?>
		<a href="#null" onclick="ToggleAll('show')" id="all_r" title="expand channel list" <?php echo $style_arrow_right;?>><IMG SRC="icons/arrow.yellow.right.png" name="<?php echo $chan_id;?> right" border="0" alt="arrow right"></a>
		<a href="#null" onclick="ToggleAll('hide')" id="all_d" title="collaps channel list"<?php echo $style_arrow_down;?>><IMG SRC="icons/arrow.yellow.down.png" name="<?php echo $chan_id;?> down" border="0" alt="arrow down"></a>	
		Channel List:
	</td>
</tr>
	<?php
	$i = 1;
	$is_live_profile = $_SESSION['profileinfo']['name'] == 'live';
	
	foreach ( $_SESSION['profileinfo']['channel'] as $channel ) 
	{
		$_opts['profile']  = $_SESSION['profileswitch'];
		$_opts['channel']  = $channel['name'];
		$_filter = nfsend_query("get-channelfilter", $_opts, 0);
		if ( !is_array($_filter) ) {
			$channel['filter'] = array('Unable to get channel filter');
		}
		$channel['filter'] = $_filter['filter'];

		print "<tr class='STATTABLE'>\n";
		print "<td style='padding: 0px' colspan='2'>\n";
		PrintChannelStat($i, $channel, $_COOKIE['extended_channellist'] );
		print "</td>\n";
		print "</tr>\n";
		$i++;
	}
?>

</table>
</div>
<script type="text/javascript">Init_ShowHide_profile();</script>
<?php


} // End of ProfileDialog

function NewProfileDialog ($new_profile) {

	global $self;

	$liveprofile = ReadProfile('./live');
	$sources = array_keys($liveprofile['channel']); 

	$live_start  = UNIX2DISPLAY($liveprofile['tstart']);
	$tnow		 = time();
	$tnow		-= $tnow % 300;
	$tnow		 = UNIX2DISPLAY($tnow);

	// prepare some values to display
	if ( is_null($new_profile['description']) ) 
		$new_profile['description'] = array();
	if ( is_null($new_profile['filter']) ) 
		$new_profile['filter'] = array();
	if ( is_null($new_profile['channel']) ) 
		$new_profile['channel'] = array();
	if ( !is_null($new_profile['tstart']) ) 
		$new_profile['tstart'] = UNIX2DISPLAY($new_profile['tstart']);
	if ( !is_null($new_profile['tend']) ) 
		$new_profile['tend'] = UNIX2DISPLAY($new_profile['tend']);
	foreach ( $new_profile['channel'] as $channel ) {
		$selected_channel[$channel] = 1;
	}
	$description = htmlspecialchars(implode("\n", $new_profile['description']), ENT_QUOTES);;
	$filter = htmlspecialchars(implode("\n", $new_profile['filter']), ENT_QUOTES);;

	$cmd_out = nfsend_query("get-profilegroups", array(), 0);
	if ( is_array($cmd_out) ) {
		$profilegroups   = $cmd_out['profilegroups'];
	} else {
		$profilegroups   = array();
		$profilegroups[] = '.';
	}
	$profilegroups[] = 'New group ...';

?>
<div style="margin-top:20px; margin-left:20px;">
<form action="<?php  echo $self;?>" method="POST" onSubmit="return ValidateNewprofileForm()">
<input type="image" border="0" name="new_profile_commit" value="new_profile_commit" src="icons/invisible.png">
<input type="hidden" name="newprofileswitch" id="newprofileswitch" value="">
<table class="STATTABLE" id="new_profile" cellspacing="4"  cellpadding="4">
<tr class="STATTABLE">
    <td class="STATTABLE"> Profile: </td>
    <td class="MYVALUE">
		<input type="text" name="profile" id="profile" value="<?php echo Util::htmlentities($new_profile['profile']); ?>"
			SIZE="32" MAXLENGTH="32" style="width:100%">
	</td>
	<td>
		<a href="#null" style="float:right;" onMouseover="showhint('<b>profile name</b><br>\
A profile name starts with letter A-Z,a-z or number 0-9 followed by up to 31 characters out of \
[A-Za-z0-9\-+_].', this, event, '200px')"><IMG SRC="icons/help.png" border="0" alt="help icon"></a>
	</td>
</tr>
<tr class="STATTABLE">
    <td class="STATTABLE"> Group: </td>
    <td class="MYVALUE">
    	<select name="groupselect" id="groupselect" onChange="HandleGroupSelect()" style="width:100%">
<?php
		foreach ( $profilegroups as $group ) {
			if ( $group == '.' ) 
				$group = '(nogroup)';
			$selected = $new_profile['profilegroup'] == $group ? "selected" : '';
			print "<option value='$group' $selected>$group</option>\n";
		}
?>
    	</select><br>
		<input type="text" name="profilegroup" id="profilegroup" value=""
			SIZE="32" MAXLENGTH="32" style="width:100%;display:none">
	</td>
	<td>
		<a href="#null" style="float:right;" onMouseover="showhint('<b>profile group name</b><br>\
Profiles may be optionally grouped together into profile groups. A profile group name starts with letter \
A-Z,a-z or number 0-9 followed by up to 31 characters out of [A-Za-z0-9\-+_]. \
', this, event, '200px')"><IMG SRC="icons/help.png" border="0" alt="help icon"></a>
	</td>
	</td>
</tr>
<tr class="STATTABLE">
    <td class="STATTABLE">Description:</td> 
    <td class="MYVALUE">
    	<textarea name="description" cols="25" rows="3" 
			style="width:100%" ><?php  echo Util::htmlentities($description); ?></textarea>
    </td>
	<td>
	</td>
</tr>
<tr class="STATTABLE">
    <td class="STATTABLE">Start:</td> 
    <td class="MYVALUE">
		<input type="text" name="tstart" value="<?php echo Util::htmlentities($new_profile['tstart']); ?>"
			SIZE="17" MAXLENGTH="16" style="font-size:12px;">&nbsp;<b>Format: yyyy-mm-dd-HH-MM</b>
	</td>
	<td>
		<a href="#null" style="float:right;" onMouseover="showhint('<b>Start time</b><br>\
Start time of new profile. Any time is accepted from <b>\
<?php echo $live_start; ?></b> ( Start of the <b>live</b> profile ) up to <b>\
<?php echo $tnow; ?></b> If left empty, the profile starts from now: <b>\
<?php echo $tnow; ?></b> (continuous profile).', this, event, '250px')"><IMG SRC="icons/help.png" border="0" alt="help icon"></a>
	</td>
</tr>
<tr class="STATTABLE">
    <td class="STATTABLE">End:</td> 
    <td class="MYVALUE">
		<input type="text" name="tend" value="<?php echo Util::htmlentities($new_profile['tend']); ?>"
			SIZE="17" MAXLENGTH="16" style="font-size:12px;">&nbsp;<b>Format: yyyy-mm-dd-HH-MM</b>
	</td>
	<td>
		<a href="#null" style="float:right;" onMouseover="showhint('<b>End time</b><br>\
End time of new profile.<br> Must be later than start of the profile. Leave empty \
for a continuous profile.',  this, event, '150px')"><IMG SRC="icons/help.png" border="0" alt="help icon"></a>
	</td>
</tr>

<tr class="STATTABLE">
    <td class="STATTABLE">Max. Size:</td> 
    <td class="MYVALUE">
		<input type='text' name='maxsize' value="<?php echo Util::htmlentities($new_profile['maxsize']); ?>" SIZE='16' MAXLENGTH='16' 
			style="font-size:12px;" >
	</td>
	<td>
		<a href="#null" style="float:right;" onMouseover="showhint('<b>Max size</b><br>\
Maximum size, this profile may grow. Any number is taken as <b>MB</b>, unless another \
scale is specified such as <b>K, M, G, T</b> or <b>KB, MB, GB, TB</b>. If set to \
<b>0</b>, no size limit applies.<br>Ex. 300, 300M, 2G etc.', this, event, '200px')"><IMG SRC="icons/help.png" border="0" alt="help icon"></a>
    </td>
</tr>

<tr class="STATTABLE">
    <td class="STATTABLE">Expire:</td> 
    <td class="MYVALUE">
	<?php  $_tmp = ExpireString($new_profile['expire']); ?>
		<input type='text' name='expire' value="<?php  echo Util::htmlentities($_tmp); ?>" SIZE='16' MAXLENGTH='16' 
			style="font-size:12px;" >
	</td>
	<td>
		<a href="#null" style="float:right;" onMouseover="showhint('<b>Expire time</b><br>\
This specifies the maximum life time for this profile. Data files older than this, will be deleted.\
Any number is taken as <b>hours</b> unless another scale is specified such as <b>d, day, days</b> \
and/or <b>h, hour, hours</b>. If set to <b>0</b> or <b>never</b>, no time limit applies.<br>\
Ex. 72, 72h, 4d 12h, 14days etc. ', this, event, '200px')"><IMG SRC="icons/help.png" border="0" alt="help icon"></a>
    </td>
</tr>

<tr class="STATTABLE">
    <td class="STATTABLE">Channels:</td> 
    <td class="MYVALUE">
		<?php $classic_checked = $new_profile['channel_wizard'] == "classic" ? "checked" : ""; ?>
		<input type="radio" name="channel_wizard" value="classic" onClick="ChannelWizard('classic');" <?php echo $classic_checked;?>> 
			<b> 1:1 channels from profile live</b><br>
		<?php $_checked = $new_profile['channel_wizard'] == "individual" ? "checked" : ""; ?>
		<input type="radio" name="channel_wizard" value="individual" onClick="ChannelWizard('individual');" <?php echo $_checked;?>> 
		<b> individual channels</b>
	</td>
	<td>
		<a href="#null" style="float:right;" onMouseover="showhint('<b>1:1 profile</b><br>\
This is the classic NfSen profile where each selected input channel maps to a channel in the new profile. \
All channels in the profile have the same filter.<br>\
<b>individual channels</b><br>A profile may contain any number of independant channels, \
where each channel has it\'s own filter. The source for each channel can be any number of \
input channels.', this, event, '200px')"><IMG SRC="icons/help.png" border="0" alt="help icon"></a>
	</td>
</tr>

<tr class="STATTABLE">
    <td class="STATTABLE">Type:</td> 
    <td class="MYVALUE">
		<?php $_checked = $new_profile['shadow'] == 0 ? "checked" : ""; ?>
		<input type="radio" name="shadow" value="0" <?php echo $_checked;?>> 
			<b> Real Profile</b><br>
		<?php $_checked = $new_profile['shadow'] == 1 ? "checked" : ""; ?>
		<input type="radio" name="shadow" value="1" <?php echo $_checked;?>> 
		<b> Shadow Profile</b>
	</td>
	<td>
		<a href="#null" style="float:right;" onMouseover="showhint('<b>Real Profile</b><br>\
Data for this profile is recorded and maintained separartly. Traditionally NfSen profile type.<br> \
<b>Shadow Profile</b><br>Profile does not record any data. Any processing is based on live profile data \
with the channel filter applied. Only channel related graphic data is stored for this \
profile.', this, event, '200px')"><IMG SRC="icons/help.png" border="0" alt="help icon"></a>
	</td>
</tr>

<tr class="STATTABLE" id="select_sources_element" style="display: none;" >
    <td class="STATTABLE">Sources:</td> 
    <td class="MYVALUE">
    <select name="channel[]" id="channel" size="4" style="width:100%" multiple>
<?php
	foreach ( $sources as $source ) {
		$selected = (is_array($selected_channel) && array_key_exists($source, $selected_channel)) ? "selected" : '';
		print "<option value='$source' $selected>$source</option>\n";
	}
?>
    </select>
    </td>
	<td>
		<a href="#null" style="float:right;" onMouseover="showhint('<b>Sources for 1:1 profile</b><br>\
Select any number of input channel for 1:1 profile.', this, event, '150px')"><IMG SRC="icons/help.png" border="0" alt="help icon"></a>
	</td>
</tr>

<tr class="STATTABLE" id="filter_element" style="display: none">
    <td class="STATTABLE">Filter:</td> 
    <td class="MYVALUE">
    	<textarea name="filter" cols="25" rows="3" style="width:100%" ><?php  echo Util::htmlentities($filter); ?></textarea>
    </td>
	<td>
		<a href="#null" style="float:right;" onMouseover="showhint('<b>Filter for 1:1 profile</b><br>\
Enter common filter for each channel in 1:1 profile.', this, event, '150px')"><IMG SRC="icons/help.png" border="0" alt="help icon"></a>
	</td>
</tr>

<tr class="STATTABLE">
    <td class="STATTABLE" colspan="2">
		<input name='new_profile_cancel' value='Cancel' type='submit' onclick='cancelAction=true'>
		<input name='new_profile_commit' value='Create Profile' type='submit' onclick='cancelAction=false'>
	</td>
</tr>
</table>
</form>
</div>
<script>
	Table_set_toggle("new_profile");
	ChannelWizard('<?php echo Util::htmlentities($new_profile['channel_wizard']);?>');
	document.getElementById('profile').select();
</script>
<?php
} // End of NewProfileDialog

/* Create a new profile. As NfSen knows only individual profiles, 
 * a classic profile is broken up into an individual profile by adding the channels
 * each with the same filter and only a single source selected.
 */
function NewProfileCreate ($profileinfo, $type) {

ob_start();
print "ADD PROFILE, type $type";

	// compile argument options for nfsend
	$cmd_opts['profile'] 	  = $profileinfo['profileswitch'];
	$cmd_opts['description']  = $profileinfo['description'];

	if ( !is_null($profileinfo['tstart'] ))
		$cmd_opts['tstart'] = UNIX2ISO($profileinfo['tstart']);
	if ( !is_null($profileinfo['tend'] ))
		$cmd_opts['tend'] = UNIX2ISO($profileinfo['tend']);

	$cmd_opts['expire']  = $profileinfo['expire'];
	$cmd_opts['maxsize'] = $profileinfo['maxsize'];
	$cmd_opts['shadow'] = $profileinfo['shadow'];

print "Add profile";
print_r($cmd_opts);
	$cmd_out = nfsend_query("add-profile", $cmd_opts, 0);
	if ( is_bool($cmd_out) && $cmd_out == FALSE ) {
		// Add profile failed.
print "Add profile failed.";
ReportLog(ob_get_contents());
ob_clean();
		return FALSE;
	} 
	
	if ( $type == 'individual' ) {
print "Add profile succeeded.";
ReportLog(ob_get_contents());
ob_clean();
		return TRUE;
	}

	// clear argument options to start over for each channel
	unset($cmd_opts);
	$cmd_opts['profile'] 	 = $profileinfo['profileswitch'];
	$cmd_opts['sign']   = '+';
	$cmd_opts['filter'] = $profileinfo['filter'];
	foreach ( $profileinfo['channel'] as $channel ) {
		$cmd_opts['channel']    = $channel;
		$cmd_opts['sourcelist'] = $channel;

print "Add channel '$channel'";
print_r($cmd_opts);
		$cmd_out = nfsend_query("add-channel", $cmd_opts, 0);
		if ( is_bool($cmd_out) && $cmd_out == FALSE ) {
			// Add channel failed. - hmm .. ugly
			unset($cmd_opts);

print "Add channel failed.";
			// we force to delete this profile to clean up any remains
			$cmd_opts['profile'] = $profileinfo['profileswitch'];
			$cmd_opts['force']   = 1;
print "Delete profile";
print_r($cmd_opts);
			$cmd_out = nfsend_query("delete-profile", $cmd_opts, 0);

			if ( is_bool($cmd_out) && $cmd_out == FALSE ) {
				// Delete profile failed. - double failure
print "Add profile failed.";
ReportLog(ob_get_contents());
ob_clean();
			} 
			return FALSE;
		} 
	}

print "All channels added";

	// successfully added all channels => commit the profile
	unset($cmd_opts);
	$cmd_opts['profile']  = $profileinfo['profileswitch'];
print "Commit profile";
print_r($cmd_opts);
	$cmd_out = nfsend_query("commit-profile", $cmd_opts, 0);

	if ( is_bool($cmd_out) && $cmd_out == FALSE ) {
		// Commit profile failed. - strange ..
print "Commit failed";
		$cmd_opts['force']   = 1;
print "Force delete profile";
print_r($cmd_opts);
		$cmd_out = nfsend_query("delete-profile", $cmd_opts, 0);

		if ( is_bool($cmd_out) && $cmd_out == FALSE ) {
			// Delete profile failed. - double failure
print "Force delete profile failed.";
ReportLog(ob_get_contents());
ob_clean();
		} 
		return FALSE;
	} 
print "Add profile succeeded.>";
ReportLog(ob_get_contents());
ob_clean();

	return TRUE;

} // End of NewProfileCreate

function NewProfileCommit ($profileswitch) {

	$cmd_opts['profile'] = $profileswitch;
	$cmd_out = nfsend_query("commit-profile", $cmd_opts, 0);

	if ( is_bool($cmd_out) && $cmd_out == FALSE ) {
		// Commit profile failed. - strange ..
		return FALSE;
	}

	return true;

} // End of NewProfileCommit


function DoReload () {

	global $self;
?>
	<script language="Javascript" type="text/javascript">
	window.location.replace("<?php echo $self; ?>");
	</script>

<?php
} // End of DoReload


function Process_stat_tab ($tab_changed, $profile_changed) {

	// the default display page - the profile stats
	$_SESSION['display'] = 'default';

	// if it's a new profile, only admin tasks make sense
	// no refresh
	if ( $_SESSION['profileinfo']['status'] == 'new' ) {
		$_SESSION['tablock'] = "A new profile needs to be completed first.";
	} else {
		unset($_SESSION['tablock']);
	}

	if ( isset($_COOKIE['extended_channellist']) ) {
		$_POST['extended_channellist'] = $_COOKIE['extended_channellist'];
	}
	$parse_opts = array( 
		"extended_channellist" => array( "required" => 0, 
							"default"  => 1,
							"allow_null" => 0,
							"match" => array( 0, 1 ),
							"validate" => NULL),
	);
	list ($form_data, $has_errors) = ParseForm($parse_opts);
	$_COOKIE['extended_channellist'] = $form_data['extended_channellist'];

	// just display profile status
	if ( $tab_changed || $profile_changed ) {
		unset($_SESSION['form_data']);
		return;
	}

	// Delete this profile - process confirmed action
	if ( array_key_exists('deleteprofile_x', $_POST ) ) {
		$parse_opts = array( 
		"switch" 	=> array( "required" => 1, "default"  => NULL, 
										  "allow_null" => 0,
										  "match" => "/^[A-Za-z0-9\.][A-Za-z0-9\-+_\/]+$/" , 
										  "validate" => "profile_exists_validate",
										  "must_exist" => 1)
		);
		list ($form_data, $has_errors) = ParseForm($parse_opts);

		if ( $has_errors > 0 ) {
			return;
		}
		if ( $form_data['switch'] != $_SESSION['profileswitch'] ) {
			SetMessage('error', "Profile to delete is not current profile");
			return;
		} 
	
		// Do the work
		$cmd_opts['profile'] 	  = $_SESSION['profileswitch'];
		if ( array_key_exists('pid', $_SESSION ) ) {
			$cmd_opts['pid'] 	  = $_SESSION['pid'];
		}
		$cmd_out = nfsend_query("delete-profile", $cmd_opts, 0);
		if ( is_array($cmd_out) ) {
			SetMessage('info', "Profile '" . $cmd_opts['profile'] . "' deleted");
			unset($_SESSION['ProfileList']);
			$profiles = GetProfiles();
			// as current profile no longer exists, switch to 'live'
			$_SESSION['profile'] 	   = 'live';
			$_SESSION['profilegroup']  = '.';
			$_SESSION['profileswitch'] = './live';
			$profileinfo = ReadProfile($_SESSION['profileswitch']);
			$_SESSION['profileinfo'] = $profileinfo;

		} // else errors are displayed anyway - nothing to do
		return;
	}

	// Cancel building the profile
	if ( array_key_exists('cancelbuild_x', $_POST ) ) {
		$parse_opts = array( 
		"switch" 	=> array( "required" => 1, "default"  => NULL, 
										  "allow_null" => 0,
										  "match" => "/^[A-Za-z0-9\.][A-Za-z0-9\-+_\/]+$/" , 
										  "validate" => "profile_exists_validate",
										  "must_exist" => 1)
		);
		list ($form_data, $has_errors) = ParseForm($parse_opts);

		if ( $has_errors > 0 ) {
			return;
		}
		if ( $form_data['switch'] != $_SESSION['profileswitch'] ) {
			SetMessage('error', "Profile to delete is not current profile");
			return;
		} 
	
		// Do the work
		$cmd_opts['profile']  = $_SESSION['profileswitch'];
		$cmd_out = nfsend_query("cancel-profile", $cmd_opts, 0);
		if ( is_array($cmd_out) ) {
			SetMessage('info', "Building profile '" . $cmd_opts['profile'] . "' canceled");
			$profiles = GetProfiles();
			$_SESSION['cancel-inprogress'] = 1;
		} // else errors are displayed anyway - nothing to do

		$_SESSION['refresh'] = 5;
		return;
	}

	// put profile into another group?
	if ( array_key_exists('regroup', $_POST ) ) {
		if ( !array_key_exists('groupselect', $_POST ) || !array_key_exists('profilegroup', $_POST )) {
			SetMessage('error', "Missing parameters");
			return;
		}

		$_group = Util::htmlentities($_POST['groupselect']);
		if ( $_group == '(nogroup)' ) {
			$_group = '.';
		} else if ( $_group == 'New group ...' ) {
			$_group = Util::htmlentities($_POST['profilegroup']);
		}
		if ( $_group != '.' && !preg_match("/^[A-Za-z0-9][A-Za-z0-9\-+_]+$/", $_group) ) {
			SetMessage('error', "Illegal characters in group name '$_group'");
			return;
		} else {
			$cmd_opts['profile'] 	  = $_SESSION['profile'];
			$cmd_opts['profilegroup'] = $_SESSION['profilegroup'];
			$cmd_opts['newgroup'] 	  = $_group;
		}

		if ( $cmd_opts['profilegroup'] == $cmd_opts['newgroup'] ) 
			// nothing changed
			return;

		// Do the work
		$cmd_out = nfsend_query("modify-profile", $cmd_opts, 0);
		if ( is_array($cmd_out) ) {
			// update new info in SESSION
			$_SESSION['profilegroup']  = $cmd_opts['newgroup'];
			$_SESSION['profileswitch'] = $cmd_opts['newgroup'] . '/' . $_SESSION['profile'];
			$profileinfo = ReadProfile($_SESSION['profileswitch']);
			$_SESSION['profileinfo'] = $profileinfo;
			unset($_SESSION['ProfileList']);
			$profiles = GetProfiles();
		} // else errors are displayed anyway - nothing to do
		return;
	}

	// Edit a profile
	if ( array_key_exists('edit', $_POST ) ) {
		$cmd_opts = array();
		if ( array_key_exists('profile_maxsize', $_POST ) ) {
			$_tmp = ParseMaxSize($_POST['profile_maxsize']);
			if ( strlen($_tmp) > 0 )
				$cmd_opts['maxsize'] = $_tmp;
			else
				SetMessage('warning', "Invalid value for maxsize");
		}
		
		if ( array_key_exists('profile_expire', $_POST ) ) {
		$_tmp = ParseExpire($_POST['profile_expire']);
			if ( $_tmp >= 0 )
				$cmd_opts['expire'] = $_tmp;
			else
				SetMessage('warning', "Invalid value for expire");
		}
		
		if ( array_key_exists('description', $_POST ) ) {
			$_tmp = preg_replace("/\r/", '', $_POST['description']);
			if (!get_magic_quotes_gpc()) {
   				$description = addslashes($_tmp);
			} else {
   				$description = $_tmp;
			}
			$cmd_opts['description'] = explode("\n", $description);
		}

		if ( array_key_exists('profile_type', $_POST ) ) {
			$_tmp = $_POST['profile_type'];
			if ( !is_numeric($_tmp) || $_tmp > 6 ) {
				SetMessage('warning', "Invalid value for profile_type");
			} else if ( $_SESSION['profileinfo']['type'] != $_tmp ) {
				$cmd_opts['profile_type'] = $_tmp;
			}
		}
		if ( count(array_keys($cmd_opts)) > 0 ) {
			$cmd_opts['profile']   = $_SESSION['profileswitch'];
			// Do the work
			$cmd_out = nfsend_query("modify-profile", $cmd_opts, 0);
			if ( is_array($cmd_out) ) {
				$profileinfo = ReadProfile($_SESSION['profileswitch']);
				$_SESSION['profileinfo'] = $profileinfo;
			} 
		}
		return;
	}

	// Cancel an edit or add a channel dialog
	if ( array_key_exists('edit_channel_cancel', $_POST ) || array_key_exists('add_channel_cancel', $_POST ) ) {
		// nothing to do - default will do
		return;
	}

	// Add a new channel - provide add dialog
	if ( array_key_exists('add_channel_x', $_POST ) ) {
		$_POST['add_channel'] = $_POST['add_channel_x'];
	}
	if ( array_key_exists('add_channel', $_POST ) ) {
		$_SESSION['display'] = 'add_channel';
		$_SESSION['refresh'] = 0;
		return;
	}

	// edit a channel - provide edit dialog
	if ( array_key_exists('edit_channel', $_POST ) ) {

		$parse_opts = array( 
			// channel name
			"edit_channel" 	=> array( "required" => 1, "default"  => NULL, 
										  "allow_null" => 0,
										  "match" => "/^[A-Za-z0-9][A-Za-z0-9\-+_]+$/" , 
										  "validate" => NULL),
		);

		list ($form_data, $has_errors) = ParseForm($parse_opts);

		$form_data['profileswitch'] = $_SESSION['profileswitch'];

		if ( $has_errors > 0 ) {
			return;
		}

		$_channel = $form_data['edit_channel'];
		if ( !array_key_exists($_channel, $_SESSION['profileinfo']['channel'] )) {
			SetMessage('error', "Channel '$_channel' does not exists in profile '" . $form_data['profile'] . "'");
			return;
		}
		$_SESSION['form_data'] = $form_data;
		$_SESSION['refresh'] = 0;
		$_SESSION['display'] = 'edit_channel';
		return;
	}
		
	// edit or add a channel? - process commited form entries
	if ( array_key_exists('edit_channel_commit', $_POST )   || array_key_exists('add_channel_commit', $_POST )) {

		if ( array_key_exists('edit_channel_commit', $_POST ) )
			$_display = "edit_channel";
		else
			$_display = "add_channel";

		$_SESSION['refresh'] = 0;

		$parse_opts = array( 
			// channel name
			"name" 				=> array( "required" => 1, "default"  => NULL, 
										  "allow_null" => 0,
										  "match" => "/^[A-Za-z0-9][A-Za-z0-9\-+_]*$/" , 
										  "validate" => NULL),

			// channel colour
			"colour" 			=> array( "required" => 1, "default"  => NULL, 
										  "allow_null" => 0,
										  "match" => "/^#[0-9a-f]{6}/i" , 
										  "validate" => NULL),

			// channel sign
			"sign" 				=> array( "required" => 1, "default"  => '+', 
										  "allow_null" => 0,
										  "match" => array( '+', '-' ), 
										  "validate" => NULL),

			// channel order
			"order" 			=> array( "required" => 1, "default"  => 1, 
										  "allow_null" => 0,
										  "match" => "/^[0-9]{1,2}/" , 
										  "validate" => NULL),

			// channel filter
			"filter"			=> array( "required" => 1, "default"  => NULL, 
										  "allow_null" => 0,
								  		  "match" => "/^[\s!-~]+$/", 
										  "validate" => 'filter_validate'),

			// channel checks
			"configured"		=> array( "required" => 1, "default"  => array(), 
										  "allow_null" => 0,
								  		  "match" => NULL, 
										  "validate" => 'channel_validate'),
		);

		list ($form_data, $has_errors) = ParseForm($parse_opts);

		$form_data['profile'] 	   = $_SESSION['profile'];
		$form_data['profilegroup'] = $_SESSION['profilegroup'];

		// additional checks
		if ( !is_null($form_data['name']) ) {
			$_channel = $form_data['name'];
			if ( $_display == "edit_channel" ) {
				// verify channel in existing profile
				if ( !array_key_exists($_channel, $_SESSION['profileinfo']['channel'] )) {
					SetMessage('error', "Channel '$_channel' does not exist in profile '$profile'");
					$has_errors = 1;
				} 
			} else {
				// verify channel name for new channel
				if ( array_key_exists($_channel, $_SESSION['profileinfo']['channel'] )) {
					SetMessage('error', "Channel '$_channel' already exist in profile '" . $_SESSION['profile'] . "'");
					$has_errors = 1;
				} 
			}
		} // else error already reported by ParseForm

		// must not change the sourcelist or the filter of a channel in profile 'live'
		if ( $form_data['profile'] == 'live' ) {
			unset($form_data['sourcelist']);
			unset($form_data['filter']);
		} else {
			$form_data['sourcelist'] = implode('|', $form_data['configured']);
		}

		unset($form_data['configured']);

		$_SESSION['form_data'] = $form_data;
		if ( $has_errors > 0 ) {
			$_SESSION['display'] = $_display;
			return;
		}

		// Do the work
		$command = $_display == 'add_channel' ? 'add-channel' : 'modify-channel';
		// make sure parameters match for nfsend
		$form_data['channel'] = $form_data['name'];
		unset($form_data['name']);

		$cmd_out = nfsend_query($command, $form_data, 0);
		if ( is_array($cmd_out) ) {
			$profileinfo = ReadProfile($_SESSION['profileswitch']);
			$_SESSION['profileinfo'] = $profileinfo;
			unset($_SESSION['form_data']);
		} else {
			// fishy something went wrong
			$_SESSION['display'] = $_display;
		}
		return;
	}
	
	// delete a channel
	if ( array_key_exists('delete_channel_commit_x', $_POST ) ) {
		if ( !array_key_exists('name', $_POST ) ) {
			SetMessage('error', "Missing channel name");
			return;
		} 

		$profile = $_SESSION['profile'];
		$_channelname = $_POST['name'];
		if ( !array_key_exists($_channelname, $_SESSION['profileinfo']['channel'] )) {
			SetMessage('error', "Channel '$_tmp' does not exist in profile '$profile'");
			return;
		} 

		// do the work
		$cmd_opts['profile'] = $_SESSION['profileswitch'];
		$cmd_opts['channel'] = $_channelname;
		$cmd_out = nfsend_query("delete-channel", $cmd_opts, 0);
		if ( is_array($cmd_out) ) {
			$profileinfo = ReadProfile($_SESSION['profileswitch']);
			$_SESSION['profileinfo'] = $profileinfo;
		} 

		return;
	}

	// Cancel a new profile dialog
	if ( array_key_exists('new_profile_cancel', $_POST ) ) {
		if ( array_key_exists("new_profile", $_SESSION) ) {
			unset($_SESSION['new_profile']);
		}
		// default will do
		return;
	}

	// create a new profile - provide the new profile dialog
	// this input comes directly from the profile select menu
	if ( array_key_exists('new_profile', $_SESSION )) {
		unset($_SESSION['new_profile']);
		$_SESSION['display'] = 'new_profile';
		return;
	}

	// create a new profile - process commited form
	if ( array_key_exists('new_profile_commit', $_POST )) {

		$parse_opts = array( 
			// profile name
			"newprofileswitch" 	=> array( "required" => 1, "default"  => NULL, 
										  "allow_null" => 0,
										  "match" => "/^[A-Za-z0-9\.][A-Za-z0-9\-+_\/]+$/" , 
										  "validate" => "profile_exists_validate",
										  "must_exist" => 0),
			// Profile start time
			"tstart" 			=> array( "required" => 1, "default"  => NULL, 
										  "allow_null" => 1,
								  		  "match" => "/^[0-9]+[0-9\-]+[0-9]+$/" , 
										  "validate" => "date_time_validate"),
			// Profile end time
			"tend" 				=> array( "required" => 1, "default"  => NULL, 
										  "allow_null" => 1,
								  		  "match" => "/^[0-9]+[0-9\-]+[0-9]+$/" , 
										  "validate" => "date_time_validate"),
			// channel_wizard
			"channel_wizard"	=> array( "required" => 1, "default"  => NULL, 
										  "allow_null" => 0,
								  		  "match" => array('classic', 'individual'), 
								  		  "validate" => NULL ),

			// Profile expire value
			"expire"			=> array( "required" => 0, "default"  => 0, 
										  "allow_null" => 0,
								  		  "match" => "/^[\s!-~]+$/", 
										  "validate" => 'expire_validate'),

			// Profile maxsize value
			"maxsize"			=> array( "required" => 0, "default"  => 0, 
										  "allow_null" => 0,
								  		  "match" => "/^[\s!-~]+$/", 
										  "validate" => 'maxsize_validate'),

			// Profile description
			"description"		=> array( "required" => 0, "default"  => NULL,
										  "allow_null" => 1,
								  		  "match" => "/^[\s!-~]+$/", 
										  "validate" => 'description_validate'),

			// filter for classic profile
			"filter"			=> array( "required" => 0, "default"  => NULL, 
										  "allow_null" => 1,
								  		  "match" => "/^[\s!-~]+$/", 
										  "validate" => 'filter_validate'),

			// channel checks
			"channel"			=> array( "required" => 0, "default"  => array(), 
										  "allow_null" => 1,
								  		  "match" => NULL, 
										  "validate" => 'channel_validate'),
			// shadow profile
			"shadow"			=> array( "required" => 1, "default"  => 1,
										  "allow_null" => 1,
								  		  "match" => array (0, 1),
										  "validate" => NULL),
			// number of individual channels
			"num_channels"			=> array( "required" => 0, "default"  => 0, 
										  "allow_null" => 1,
								  		  "match" => "/^[0-9]{1,3}$/" , 
										  "validate" => NULL),
		);


		list ($form_data, $has_errors) = ParseForm($parse_opts);
		if ( preg_match("/^(.+)\/(.+)/", $form_data['newprofileswitch'], $matches) ) {
			$_profilegroup = $matches[1];
			$_profilename  = $matches[2];
			$form_data['profileswitch']	= $form_data['newprofileswitch'];
			$form_data['profile'] 		= $_profilename;
			$form_data['profilegroup'] 	= $_profilegroup;
			unset($form_data['newprofileswitch']);
		} else {
			$has_errors = 1;
		}

		// additional checks
		if ( !is_null($form_data['tend']) && !is_null($form_data['tstart']) && 
			( $form_data['tend'] < $form_data['tstart']) ) {
			$ts = UNIX2DISPLAY($form_data['tstart']);
			$te = UNIX2DISPLAY($form_data['tend']);
			$form_data['tstart'] = NULL;
			$form_data['tend'] 	 = NULL;
			SetMessage('error', "Profile end time '$te' earlier then Start time '$ts'");
			$has_errors = 1;
		}
		if ( !is_null($form_data['tend']) && is_null($form_data['tstart']) ) {
			$te = UNIX2DISPLAY($form_data['tend']);
			SetMessage('error', "Profile has end time '$te', but no start time set");
			$has_errors = 1;
		}

		if ( $form_data['channel_wizard'] == 'classic' ) {
			if ( is_null($form_data['filter']) || count($form_data['channel']) == 0 ) {
				SetMessage('error', "A classic profile needs a valid filter and at least one selected channel");
				$has_errors = 1;
			}
		}

		if ( $has_errors > 0 ) {
			$_SESSION['form_data'] = $form_data;
			$_SESSION['display']   = 'new_profile';
			$_SESSION['refresh'] = 0;
			return;
		}

		// do the work
		if ( array_key_exists('channel_wizard', $form_data ) ) {
			$type = $form_data['channel_wizard'];
			if ( NewProfileCreate($form_data, $type) == TRUE ) {
				// update NfSen to include the new profile
				unset($_SESSION['ProfileList']);
				$profiles = GetProfiles();

				// switch to new profile
				$_SESSION['profileswitch'] = $form_data['profileswitch'];
				$_SESSION['profile'] 	   = $form_data['profile'];
				$_SESSION['profilegroup']  = $form_data['profilegroup'];
				$_SESSION['profileinfo']   = ReadProfile($_SESSION['profileswitch']);
				SetMessage('info', "Profile '" . $form_data['profile'] . "' created");
			//	if ( $_SESSION['profileinfo']['type'] == 1 && $_SESSION['profileinfo']['status'] != 'new' ) 
				if ( $_SESSION['profileinfo']['tstart'] < $_SESSION['profileinfo']['tend'] ) 
					$_SESSION['refresh'] = 5;
			} else {
				$_SESSION['form_data'] = $form_data;
				$_SESSION['display'] = 'new_profile';
			}
		}
		return;
	}

	if ( array_key_exists('commit_profile_x', $_POST )) {
		if ( $_SESSION['profileinfo']['status'] != 'new' && $_SESSION['profileinfo']['status'] != 'stalled') {
			SetMessage('error', "Can not commit a profile, not in status 'new or stalled'");
			return;
		} 

		// Do the work
		// if it fails, the default will do
		if ( NewProfileCommit($_SESSION['profileswitch']) ) {
			$profileinfo = ReadProfile($_SESSION['profileswitch']);
			$_SESSION['profileinfo'] = $profileinfo;
			unset($_SESSION['tablock']);
			if ( $_SESSION['profileinfo']['tstart'] < $_SESSION['profileinfo']['tend'] ) 
				$_SESSION['refresh'] = 5;
		}
		return;
	}

	// refresh time if profile building in progress
	if ( preg_match("/built/", $_SESSION['profileinfo']['status']))
		$_SESSION['refresh'] = 5;

} // End of Process_stat_tab

function DisplayAdminPage() {

	// include all required javascript for this page
?>
	<script language="Javascript" src="js/profileadmin.js" type="text/javascript">
	</script>

<?php

	switch ( $_SESSION['display']) {
		case "add_channel":	
			$num_pos = 0;
			$num_neg = 0;
			foreach ( $_SESSION['profileinfo']['channel'] as $_chan ) {
				if ( $_chan['sign'] == '+' ) $num_pos++;
				if ( $_chan['sign'] == '-' ) $num_neg++;
			}
			$liveprofile = ReadProfile('./live');
			$is_live_profile = 0;
			$is_new_channel  = 1;

			// setup channel defaults
			if ( array_key_exists('form_data', $_SESSION ) ) {
				// add channel contained errors - interate ones more
				$channel_defaults = $_SESSION['form_data'];
				unset($_SESSION['form_data']);
				if ( $channel_defaults['sign'] == '+' ) {
					$num_pos++;
				} else if ( $channel_defaults['sign'] == '-' ) {
					$num_neg++;
				}
			} else {	// initial dialog
				$channel_defaults = array();
				$channel_defaults['name']   = '';
				$channel_defaults['sign'] 	= '+'; 
				$num_pos++;
				$channel_defaults['colour'] = '#abcdef';
				$channel_defaults['order']  = $num_pos;
				$channel_defaults['sourcelist']  = NULL;
			}
			EditChannel($is_live_profile, $is_new_channel, $channel_defaults, $liveprofile, $num_pos, $num_neg);

			break;
		case 'edit_channel':
			$channelinfo   = $_SESSION['form_data'];
			$profileswitch = $channelinfo['profileswitch'];

			$num_pos = 0;
			$num_neg = 0;
			foreach ( $_SESSION['profileinfo']['channel'] as $_chan ) {
				if ( $_chan['sign'] == '+' ) $num_pos++;
				if ( $_chan['sign'] == '-' ) $num_neg++;
			}
			$liveprofile = ReadProfile('./live');
			$is_live_profile = $profileswitch == './live';
			$is_new_channel  = 0;

			// if edit icon was clicked, load channel data 
			if ( array_key_exists('edit_channel', $channelinfo )) {
				$channel = $channelinfo['edit_channel'];
				$channelinfo = $_SESSION['profileinfo']['channel'][$channel];

				$_opts['profile'] 	   = $profileswitch;
				$_opts['channel'] 	   = $channel;
				$_filter = nfsend_query("get-channelfilter", $_opts, 0);
				if ( !is_array($_filter) ) {
					$channelinfo['filter'] = array('Unable to get channel filter');
				}
				$channelinfo['filter'] = $_filter['filter'];

			}
			EditChannel($is_live_profile, $is_new_channel, $channelinfo, $liveprofile, $num_pos, $num_neg);
			unset($_SESSION['form_data']);
			break;
		case "new_profile":
			if ( array_key_exists('form_data', $_SESSION) ) {
				$form_data = $_SESSION['form_data'];
				unset($_SESSION['form_data']);
			} else {
				$form_data = array();
				$form_data['profile']	   = NULL;
				$form_data['profilegroup'] = NULL;
				$form_data['tstart']	   = NULL;
				$form_data['tend']		   = NULL;
				$form_data['channel_wizard'] = 'classic';
				$form_data['expire'] 	   = '1440';
				$form_data['maxsize'] 	   = '10G';
				$form_data['shadow'] 	   = 0;
				$form_data['description']  = NULL;
				$form_data['filter'] 	   = NULL;
				$form_data['channel'] 	   = NULL;
				$form_data['num_channels'] = 1;
			}
			NewProfileDialog($form_data);
			break;
		case 'default':
		default:
			ProfileDialog();
	}
	unset($_SESSION['display']);

/*
print "<pre>";
print_r($_SESSION);
print_r($_POST);
print "</pre>";
*/

} // End of DisplayAdminPage
?>
