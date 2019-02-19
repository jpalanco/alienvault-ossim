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
Session::logcheck('configuration-menu', 'ComplianceMapping');

$validate = array (
		'action'         => array('validation'  =>  'OSS_LETTER',  'e_message' => 'illegal:' . _('Action')),
		'sid'            => array('validation'  =>  'OSS_DIGIT',   'e_message' => 'illegal:' . _('Directive ID')),
		'targeted'       => array('validation'  =>  'OSS_BINARY',  'e_message' => 'illegal:' . _('Targeted')),
		'untargeted'     => array('validation'  =>  'OSS_BINARY',  'e_message' => 'illegal:' . _('UnTargeted')),
		'approach'       => array('validation'  =>  'OSS_BINARY',  'e_message' => 'illegal:' . _('Approach')),
		'exploration'    => array('validation'  =>  'OSS_BINARY',  'e_message' => 'illegal:' . _('Exploration')),
		'penetration'    => array('validation'  =>  'OSS_BINARY',  'e_message' => 'illegal:' . _('Penetration')),
		'generalmalware' => array('validation'  =>  'OSS_BINARY',  'e_message' => 'illegal:' . _('General Malware')),
		'imp_qos'        => array('validation'  =>  'OSS_BINARY',  'e_message' => 'illegal:' . _('Impact: QOS')),
		'imp_infleak'    => array('validation'  =>  'OSS_BINARY',  'e_message' => 'illegal:' . _('Impact: Infleak')),
		'imp_lawful'     => array('validation'  =>  'OSS_BINARY',  'e_message' => 'illegal:' . _('Impact: Lawful')),
		'imp_image'      => array('validation'  =>  'OSS_BINARY',  'e_message' => 'illegal:' . _('Impact: Image')),
		'imp_financial'  => array('validation'  =>  'OSS_BINARY',  'e_message' => 'illegal:' . _('Impact: Financial')),
		'D'   			 => array('validation'  =>  'OSS_BINARY',  'e_message' => 'illegal:' . _('Availability')),
		'I'              => array('validation'  =>  'OSS_BINARY',  'e_message' => 'illegal:' . _('Integrity')),
		'C'              => array('validation'  =>  'OSS_BINARY',  'e_message' => 'illegal:' . _('Confidentiality')),
		'net_anomaly'    => array('validation'  =>  'OSS_BINARY',  'e_message' => 'illegal:' . _('Network Anomaly'))
);


if (GET('ajax_validation') == TRUE)
{
	$data['status'] = 'OK';

	$validation_errors = validate_form_fields('GET', $validate);
	if ( is_array($validation_errors) && !empty($validation_errors) )
	{
		$data['status'] = 'error';
		$data['data']   = $validation_errors;
	}

	echo json_encode($data);
	exit();
}

if (POST('action') == 'modify')
{
	$action                 = POST('action');
	$sid                    = POST('sid');
	$data['sid']            = $sid;
	$data['targeted']       = POST('targeted');
	$data['untargeted']     = POST('untargeted');
	$data['approach']       = POST('approach');
	$data['exploration']    = POST('exploration');
	$data['penetration']    = POST('penetration');
	$data['generalmalware'] = POST('generalmalware');
	$data['imp_qos']        = POST('imp_qos');
	$data['imp_infleak']    = POST('imp_infleak');
	$data['imp_lawful']     = POST('imp_lawful');
	$data['imp_image']      = POST('imp_image');
	$data['imp_financial']  = POST('imp_financial');
	$data['D']              = POST('D');
	$data['I']              = POST('I');
	$data['C']              = POST('C');
	$data['net_anomaly']    = POST('net_anomaly');
	
	$validation_errors = validate_form_fields('POST', $validate);
	
	$data['status'] = 'OK';
	$data['data']   = $validation_errors;
	
	
	if (POST('ajax_validation_all') == TRUE)
	{
		if ( is_array($validation_errors) && !empty($validation_errors) )
		{
			$data['status'] = 'error';
			
			// Prevent xss
			foreach ($data as $data_key => $data_value)
			{
			    if ($data_key != 'data')
			    {
			        $data[$data_key] = Util::htmlentities($data_value);
			    }
			}
			
			echo json_encode($data);
		}
		else
		{
			$data['status'] = 'OK';
			echo json_encode($data);
		}
		exit();
	}
	else
	{
		if (is_array($validation_errors) && !empty($validation_errors))
		{
			$data['status'] = 'error';
		}
	}
	
	if ($data['status'] == 'error')
	{
		$txt_error = '<div>'._('The following errors occurred').":</div>
					  <div style='padding:2px 10px 5px 10px;'>".implode('<br/>', $validation_errors).'</div>';
			
		$config_nt = array(
				'content' => $txt_error,
				'options' => array (
						'type'          => 'nf_error',
						'cancel_button' => FALSE
				),
				'style'   => 'width: 80%; margin: 20px auto; text-align: left;'
		);
			
		$nt = new Notification('nt_1', $config_nt);
		$nt->show();
	
		Util::make_form('POST', 'pluginref.php');
		exit();
	
	}
	else
	{
	
		$db    = new ossim_db();
		$conn  = $db->connect();		
		
		Compliance::update($conn, $data);
		
		$db->close();
		
		?>
		<script type='text/javascript'>
    		var params          = new Array();
        		params['dir_info']  = "1";
        	    params['directive'] = "<?php echo $sid ?>";
        	    params['reload']    = true;
        		parent.GB_hide(params);
		</script>
		<?php
		exit;
	}
}
elseif (GET('only_delete') != '')
{
	$sid = GET('sid');
	ossim_valid($sid, OSS_DIGIT, 'illegal:' . _('Sid'));
	
	if (ossim_error())
	{
		die(ossim_error());
	}
	
	$db   = new ossim_db();
	$conn = $db->connect();
	
	Compliance::delete($conn, $sid);
	$db->close();
	
	?>
	<script type='text/javascript'>
	     document.location.href="index.php?msg_success=1&toggled_dir=<?php echo $sid ?>&dir_info=1"
	</script>
	<?php
	exit;
}

$sid = GET('sid');
ossim_valid($sid, OSS_DIGIT, 'illegal:' . _('Sid'));

if (ossim_error()) 
{
    die(ossim_error());
}

$db   = new ossim_db();
$conn = $db->connect();

list($category_list, $total) = Compliance::get_category($conn, "AND plugin_sid.sid = $sid");

if ($total > 0)
{
    $cat = $category_list[0];
}
else
{
    echo ossim_error(_('Error! Category not found'));
    exit();
}

$db->close();

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo _('OSSIM Framework');?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<script type="text/javascript" src="../js/jquery.min.js"></script>
	<script type="text/javascript" src="../js/notification.js"></script>
	<script type="text/javascript" src="../js/messages.php"></script>
	<script type="text/javascript" src="../js/ajax_validator.js"></script>
	<script type="text/javascript" src="../js/utils.js"></script>
	<script type="text/javascript" src="../js/jquery.tipTip.js"></script>
	
	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<link rel="stylesheet" type="text/css" href="../style/tipTip.css"/>

	<script type="text/javascript">
		
		$(document).ready(function(){
			var config = {   
					validation_type: 'complete', // single|complete
					errors:{
						display_errors: 'all', //  all | summary | field-errors
						display_in: 'av_info'
					},
					form : {
						id  : 'form_properties',
						url : "form_properties.php"
					},
					actions: {
						on_submit:{
							id: 'send',
							success: '<?php echo _('Save')?>',
							checking: '<?php echo _('Saving')?>'
						}
					}
				};
			
			ajax_validator = new Ajax_validator(config);
			
			ajax_validator = new Ajax_validator(config);
		
			$('#send').click(function() { 
				ajax_validator.submit_form();
			});
		});
	
	</script>
	
	<style type='text/css'>
		#t_ngeneral
		{
			margin: 20px auto;
			width: 350px;
		}
		
		a 
		{
		    cursor:pointer;
		}
		
		input[type='text'], input[type='hidden'], select 
		{
    		width: 98%; 
    		height: 18px;
		}
		
		textarea 
		{
    		width: 97%; 
    		height: 45px;
		}
		
		.legend 
		{
		    font-size: 10px;
    		font-style: italic;
    		text-align: center; 
    		padding: 0px 0px 5px 0px;
    		margin: auto;
    		width: 400px;
		}
		
		#av_info
		{
			margin: 20px auto;
			width: 90%;
		}
		
	</style>
</head>

<body>

<div id='av_info'></div>

<div class="legend">
    <?php echo _('Values marked with (*) are mandatory');?>
</div>	
	
<form method='post' id='form_properties' name='form_properties'>
	
	<input type='hidden' class='vfield' name='action' id='action' value='modify'/>
	<input type='hidden' class='vfield' id='sid' name='sid' value='<?php echo $cat->get_sid() ?>'/>
	
	<table id='t_ngeneral' align="center">
		<tr>
			<th>
			    <label for="targeted"><?php echo _('Targeted')?></label>
			</th>
			<td class="left">
				<select class='vfield' name="targeted" id="targeted">
					<option <?php if ($cat->get_targeted() == 1) echo " selected='selected' ";?> value="1"><?php echo _('Yes');?></option>
					<option <?php if ($cat->get_targeted() == 0) echo " selected='selected' ";?> value="0"><?php echo _('No');?></option>
				</select>
			</td>
		</tr>
		
		<tr>			
			<th>
			    <label for="untargeted"><?php echo _('UnTargeted')?></label>
			</th>
			<td class="left">
				<select class='vfield' name="untargeted" id="untargeted">
					<option <?php if ($cat->get_untargeted() == 1) echo " selected='selected' ";?> value="1"><?php echo _('Yes');?></option>
					<option <?php if ($cat->get_untargeted() == 0) echo " selected='selected' ";?> value="0"><?php echo _('No');?></option>
				</select>
			</td>
		</tr>
		
		<tr>			
			<th>
			    <label for="approach"><?php echo _('Approach')?></label>
			</th>
			<td class="left">
				<select class='vfield' name="approach" id="approach">
					<option  <?php if ($cat->get_approach() == 1) echo " selected='selected' ";?> value="1"><?php echo _('Yes');?></option>
					<option <?php if ($cat->get_approach() == 0) echo " selected='selected' ";?> value="0"><?php echo _('No');?></option>
				</select>
			</td>
		</tr>
	
		<tr>			
			<th>
			    <label for="exploration"><?php echo _('Exploration')?></label>
			</th>
			<td class="left">
				<select class='vfield' name="exploration" id='exploration'>
					<option <?php if ($cat->get_exploration() == 1) echo " selected='selected' ";?> value="1"><?php echo _('Yes');?></option>
					<option <?php if ($cat->get_exploration() == 0) echo " selected='selected' ";?> value="0"><?php echo _('No');?></option>
				</select>
			</td>
		</tr>
	
		<tr>			
			<th>
			    <label for="penetration"><?php echo _('Penetration')?></label>
			</th>
			<td class="left">
				<select class='vfield' name="penetration" id='penetration'>
					<option <?php if ($cat->get_penetration() == 1) echo " selected='selected' ";?> value="1"><?php echo _('Yes');?></option>
					<option <?php if ($cat->get_penetration() == 0) echo " selected='selected' ";?> value="0"><?php echo _('No');?></option>
				</select>
			</td>
		</tr>
	  
		<tr>		
			<th>
			    <label for="generalmalware"><?php echo _('General Malware')?></label>
			</th>
			<td class="left">
				<select class='vfield' name="generalmalware" id='generalmalware'>
					<option <?php if ($cat->get_generalmalware() == 1) echo " selected='selected' ";?> value="1"><?php echo _('Yes');?></option>
					<option <?php if ($cat->get_generalmalware() == 0) echo " selected='selected' ";?> value="0"><?php echo _('No');?></option>
				</select>
			</td>
		</tr>
  
		<tr>			
			<th>
			    <label for="imp_qos"><?php echo _('Impact: QOS')?></label>
			</th>
			<td class="left">
				<select class='vfield' name="imp_qos" id='imp_qos'>
					<option <?php if ($cat->get_imp_qos() == 1) echo " selected='selected' ";?> value="1"><?php echo _('Yes');?></option>
					<option <?php if ($cat->get_imp_qos() == 0) echo " selected='selected' ";?> value="0"><?php echo _('No');?></option>
				</select>
			</td>
		</tr>
		
		<tr>			
			<th>
			    <label for="imp_infleak"><?php echo _('Impact: Infleak')?></label>
			</th>
			<td class="left">
				<select class='vfield' name="imp_infleak" id='imp_infleak'>
					<option <?php if ($cat->get_imp_infleak() == 1) echo " selected='selected' ";?> value="1"><?php echo _('Yes');?></option>
					<option <?php if ($cat->get_imp_infleak() == 0) echo " selected='selected' ";?> value="0"><?php echo _('No');?></option>
				</select>
			</td>
		</tr>
		
		<tr>			
			<th>
			    <label for="imp_lawful"><?php echo _('Impact: Lawful')?></label>
			</th>
			<td class="left">
				<select class='vfield' name="imp_lawful" id='imp_lawful'>
					<option <?php if ($cat->get_imp_lawful() == 1) echo " selected='selected' ";?> value="1"><?php echo _('Yes');?></option>
					<option <?php if ($cat->get_imp_lawful() == 0) echo " selected='selected' ";?> value="0"><?php echo _('No');?></option>
				</select>
			</td>
		</tr>
  
		<tr>
			<th>
			    <label for="imp_image"><?php echo _('Impact: Image')?></label>
			</th>
			<td class="left">
				<select class='vfield' name="imp_image">
					<option <?php if ($cat->get_imp_image() == 1) echo " selected='selected' ";?> value="1"><?php echo _('Yes');?></option>
					<option <?php if ($cat->get_imp_image() == 0) echo " selected='selected' ";?> value="0"><?php echo _('No');?></option>
				</select>
			</td>
		</tr>
	  
		<tr>			
			<th>
			    <label for="imp_financial"><?php echo _('Impact: Financial')?></label>
			</th>
			<td class="left">
				<select class='vfield' name="imp_financial" id='imp_financial'>
					<option <?php if ($cat->get_imp_financial() == 1) echo " selected='selected' ";?> value="1"><?php echo _('Yes');?></option>
					<option <?php if ($cat->get_imp_financial() == 0) echo " selected='selected' ";?> value="0"><?php echo _('No');?></option>
				</select>
			</td>
		</tr>
		
		<tr>			
			<th>
			    <label for="D"><?php echo _('Availability')?></label>
			</th>
			<td class="left">
				<select class='vfield' name="D" id='D'>
					<option <?php if ($cat->get_D() == 1) echo " selected='selected' ";?> value="1"><?php echo _('Yes');?></option>
					<option <?php if ($cat->get_D() == 0) echo " selected='selected' ";?> value="0"><?php echo _('No');?></option>
				</select>
			</td>
		</tr>
	
		<tr>
			<th>
			    <label for="I"><?php echo _('Integrity')?></label>
			</th>
			<td class="left">
				<select class='vfield' name="I" id='I'>
					<option <?php if ($cat->get_I() == 1) echo " selected='selected' ";?> value="1"><?php echo _('Yes');?></option>
					<option <?php if ($cat->get_I() == 0) echo " selected='selected' ";?> value="0"><?php echo _('No');?></option>
				</select>
			</td>
		</tr>
		
		<tr>
            <th>
                <label for="C"><?php echo _('Confidentiality')?></label>
            </th>		
			<td class="left">
				<select class='vfield' name="C" id='C'>
					<option <?php if ($cat->get_C() == 1) echo " selected='selected' ";?> value="1"><?php echo _('Yes');?></option>
					<option <?php if ($cat->get_C() == 0) echo " selected='selected' ";?> value="0"><?php echo _('No');?></option>
				</select>
			</td>
		</tr>

		<tr>			
			<th>
                <label for="net_anomaly"><?php echo _('Network Anomaly')?></label>
            </th>
			<td class="left">
				<select class='vfield' name="net_anomaly" id="net_anomaly">
					<option <?php if ($cat->get_net_anomaly() == 1) echo " selected='selected' ";?> value="1"><?php echo _('Yes');?></option>
					<option <?php if ($cat->get_net_anomaly() == 0) echo " selected='selected' ";?> value="0"><?php echo _('No');?></option>
				</select>
			</td>
		</tr>
	  
		<tr>
			<td colspan="2" align="center" style='padding: 10px 0px;'>
				<input type="button" class="button "name='send' id='send' value="<?php echo _('Save')?>"/>				
			</td>
		</tr>    
	</table>
</form>

</body>
</html>
