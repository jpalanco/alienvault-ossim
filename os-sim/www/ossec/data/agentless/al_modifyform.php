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


require_once dirname(__FILE__) . '/../../conf/config.inc';

Session::logcheck('environment-menu', 'EventsHidsConfig');
	

$array_types = array ('ssh_integrity_check_bsd'     => 'Integrity Check BSD',
					  'ssh_integrity_check_linux'   => 'Integrity Check Linux',
					  'ssh_generic_diff' 			=> 'Generic Command Diff',
					  'ssh_pixconfig_diff'  		=> 'Cisco Config Check',
					  'ssh_foundry_diff' 			=> 'Foundry Config Check',
					  'ssh_asa-fwsmconfig_diff'     => 'ASA FWSMconfig Check');
						
		
$info_error 	= NULL;
$display        = 'display:none;';

$ip 			= (!empty($_GET['ip'])) 	? GET('ip')     : POST('ip');
$sensor_id		= (!empty($_GET['sensor'])) ? GET('sensor') : POST('sensor');
$al_data		= GET('al_data');


if ($al_data == 'me')
{
	$validate = array (
		'type'        => array('validation' => 'OSS_NOECHARS, OSS_SCORE, OSS_LETTER',  'e_message' => 'illegal:' . _('Type')),
		'frequency'   => array('validation' => 'OSS_DIGIT',                            'e_message' => 'illegal:' . _('Frequency')),
		'state'       => array('validation' => 'OSS_NOECHARS, OSS_SCORE, OSS_LETTER',  'e_message' => 'illegal:' . _('State')),
		'arguments'   => array('validation' => "OSS_NOECHARS, OSS_TEXT, OSS_AT, OSS_NULLABLE, OSS_PUNC_EXT, '\`', '\<', '\>'", 'e_message' => 'illegal:' . _('Arguments')));
}
else
{
	$validate = array (
		'hostname'    => array('validation' => 'OSS_NOECHARS, OSS_SCORE, OSS_LETTER, OSS_DIGIT, OSS_DOT',  	'e_message' => 'illegal:' . _('Hostname')),
		'ip'          => array('validation' => 'OSS_IP_ADDR',                                              	'e_message' => 'illegal:' . _('IP')),
		'sensor'      => array('validation' => 'OSS_HEX',                                             	    'e_message' => 'illegal:' . _('Sensor')),
		'user'        => array('validation' => 'OSS_NOECHARS, OSS_ALPHA, OSS_PUNC_EXT',                    	'e_message' => 'illegal:' . _('User')),
		'descr'       => array('validation' => 'OSS_NOECHARS, OSS_TEXT,  OSS_SPACE, OSS_AT, OSS_NULLABLE', 	'e_message' => 'illegal:' . _('Description')),
		'pass'        => array('validation' => 'OSS_PASSWORD',                    						 	'e_message' => 'illegal:' . _('Password')),
		'passc'       => array('validation' => 'OSS_PASSWORD',              							    'e_message' => 'illegal:' . _('Pass confirm')),
		'ppass'       => array('validation' => 'OSS_PASSWORD, OSS_NULLABLE',      						 	'e_message' => 'illegal:' . _('Privileged Password')),
		'ppassc'      => array('validation' => 'OSS_PASSWORD, OSS_NULLABLE',      							'e_message' => 'illegal:' . _('Privileged Password confirm')),
		'use_su'      => array('validation' => 'OSS_BINARY, OSS_NULLABLE',      						    'e_message' => 'illegal:' . _('Option use_su')));
}

if (GET('ajax_validation') == TRUE)
{
	$data['status'] = 'OK';
	
	$validation_errors = validate_form_fields('GET', $validate);
	if (is_array($validation_errors) && !empty($validation_errors))	
	{
		$data['status'] = 'error';
		$data['data']   = $validation_errors;
	}
		
	echo json_encode($data);	
	exit();
}

if (POST('ajax_validation_all') == TRUE || !empty($_POST['ip']))
{
	$validation_errors = validate_form_fields('POST', $validate);
	$data['status']    = 'OK';
	
		
	if ($validate_step != 1)
	{
		if (POST('pass') != POST('passc'))
		{
			$validation_errors['pass'] = _('Password fields are different');
		}
				
		if (!empty($_POST['ppass']) && (POST('ppass') != POST('ppassc')))
		{
			$validation_errors['ppass'] = _('Privileged Password fields are different');
		}
	}
	
	$data['data'] = $validation_errors;
	
	if (POST('ajax_validation_all') == TRUE)
	{
		if (is_array($validation_errors) && !empty($validation_errors))
		{
			$data['status'] = 'error';
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
			$info_error	= '<div>'._('We Found the following errors').':</div><div style="padding:10px;">'.implode('<br/>', $validation_errors).'</div>';
		}
	}		
}


//Form actions	

ossim_valid($ip, 		OSS_IP_ADDR,  'illegal:' . _('Ip Address'));
ossim_valid($sensor_id, OSS_HEX,  	  'illegal:' . _('Sensor'));


$db   = new ossim_db();
$conn = $db->connect();

if (!ossim_error()) 
{	
    if (!Ossec_utilities::is_sensor_allowed($conn, $sensor_id))
    {
        ossim_set_error(_('Error! Sensor not allowed'));
    } 
}

if (ossim_error()) 
{
	$critical_error = ossim_get_error();
	ossim_clean_error();
}
else
{		
	$agentless = Ossec_agentless::get_object($conn, $sensor_id, $ip);

	if (is_object($agentless) && !empty($agentless))
	{
		$ip 	  = $agentless->get_ip();
		$hostname = $agentless->get_hostname();
		$user 	  = $agentless->get_user();
		$pass     = Util::fake_pass($agentless->get_pass());
		$passc    = $pass;
		$ppass    = Util::fake_pass($agentless->get_ppass());
		$use_su   = $agentless->get_use_su();
		$ppassc   = $ppass;
		$descr 	  = $agentless->get_descr();			
		    	
    	$sensor_name  = Av_sensor::get_name_by_id($conn, $sensor_id);    	
    	
    	$_SESSION['_al_new']['sensor']      = $sensor_id;
       	$_SESSION['_al_new']['sensor_name'] = $sensor_name;  	
		
		$error_m_entries = array();
				
		try
		{		
		    $monitoring_entries = Ossec_agentless::get_list_m_entries($conn, $sensor_id, " AND ip = '$ip'");
		}
		catch(Exception $e)
		{
    		$error_m_entries = $e->getMessage();   
		}
	}
	else
	{
		$critical_error = _('No agentless host found');
	}
}

$db->close();

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php echo _('OSSIM Framework');?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	
	<script type="text/javascript" src="/ossim/js/jquery.min.js"></script>	
	<script type="text/javascript" src="/ossim/js/jquery.elastic.source.js" charset="utf-8"></script>
	<script type="text/javascript" src="/ossim/js/jquery.tipTip-ajax.js"></script>
	
	<!-- Own libraries: -->
	<script type="text/javascript" src="/ossim/js/notification.js"></script>
	<script type="text/javascript" src="/ossim/js/ajax_validator.js"></script>
	<script type="text/javascript" src="/ossim/js/messages.php"></script>
	<script type="text/javascript" src="../../js/common.js"></script>
	<script type="text/javascript" src="../../js/ossec_msg.php"></script>
	<script type="text/javascript" src="/ossim/js/utils.js"></script>
	<script type="text/javascript" src="/ossim/js/token.js"></script>
	
	<link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<link rel="stylesheet" type="text/css" href="/ossim/style/jquery-ui-1.7.custom.css"/>
	<link rel="stylesheet" type="text/css" href="/ossim/style/tipTip.css"/>
	
	<script type="text/javascript">
		
		function add_monitoring()
		{
			//Show load info
			var l_content = '<img src="<?php echo OSSEC_IMG_PATH?>/loading.gif" border="0" align="absmiddle"/><span style="padding-left: 5px;">'+ossec_msg['add_m_entry']+'</span>';										
			
			$("#al_load").html(l_content);
			
			
			var form_id = "al_modify_form_2";
			var token   = Token.get_token('al_entries');	
									
			$.ajax({
				type: "POST",
				url: "ajax/actions.php",
				data: $('#'+form_id).serialize() +"&action=add_monitoring_entry&token="+token,
				dataType: "json",
				error: function(data){
															
					$("#info_error").html(notify_error(ossec_msg['unknown_error']));
					$('#info_error').show();
				},
				success: function(html){
					
					$("#al_load").html('');	
					
					if (typeof(html) != 'undefined' && html != null)
					{												
						if (html.status == 'error')
						{
							if (typeof(html.data.html_errors) != 'undefined' && html.data.html_errors != '')
							{
								$("#info_error").html(notify_error(html.data.html_errors));
								$('#info_error').show();
							}
							else
							{
								$('#info_error').html('');
								$("#al_load").html("<div class='cont_al_message'><div class='al_message'>"+notify_error(html.data)+"</div></div>");
								$("#al_load").fadeIn(2000);
								$("#al_load").fadeOut(4000);
							}
						}
						else
						{
							$("#info_error").html('');
							
							if ($('.al_no_added').length >= 1)
							{
								$('.al_no_added').remove();	
							}
							
							$('#t_body_mt').append(html.data);
							
							$('#t_body_mt tr td').removeClass('odd even');
                			$('#t_body_mt tr:even td').addClass('even');
                			$('#t_body_mt tr:odd td').addClass('odd');
							
							//Add new token
							Token.add_to_forms();	
						}  
					}			                                  
				}
			});
		}
		
	
		function delete_monitoring(id)
		{
			//Show load info
			var l_content = '<img src="<?php echo OSSEC_IMG_PATH?>/loading.gif" border="0" align="absmiddle"/><span style="padding-left: 5px;">'+ossec_msg['delete_m_entry']+'</span>';										
					
            $("#al_load").html(l_content);
			
			var form_id = "al_modify_form_2";
			var token   = Token.get_token('al_entries');	
									
			$.ajax({
				type: "POST",
				url: "ajax/actions.php",
				data: $('#'+form_id).serialize() + "&action=delete_monitoring_entry&id="+id+"&token="+token,
				dataType: "json",
				error: function(data){
					$("#al_load").html('');
					
					$("#info_error").html(notify_error(ossec_msg['unknown_error']));
					$('#info_error').show();
					
					$('.add').off('click');
							
					$('.add').val(labels['add']);
					$('.add').click(function() {
						add_monitoring(id);
					});
				},
				success: function(html){
					
					$("#al_load").html('');
					
					if (typeof(html) != 'undefined' && html != null)
					{
						if (html.status == 'error')
						{
							if (typeof(html.data.html_errors) != 'undefined' && html.data.html_errors != '')
							{
								$("#info_error").html(notify_error(html.data.html_errors));
								$('#info_error').show();
							}
							else
							{
								$("#info_error").html('');
								
								$("#al_load").html("<div class='cont_al_message'><div class='al_message'>"+notify_error(html.data)+"</div></div>");
								$("#al_load").fadeIn(2000);
								$("#al_load").fadeOut(4000);
							}
						}
						else
						{
							$("#info_error").html('');
							
							$('#m_entry_'+id).remove();
							
							//Add new token
							Token.add_to_forms();
													
							if ($('#t_body_mt tr').length == 0)
							{
								var msg="<tr class='al_no_added'><td class='noborder' colspan='5'><div class='al_info_added'><?php echo _("No monitoring entries added")?></div></td></tr>";
								$('#t_body_mt').html(msg);
							}
							else
							{
								$('#t_body_mt tr td').removeClass('odd even');
                    			$('#t_body_mt tr:even td').addClass('even');
                    			$('#t_body_mt tr:odd td').addClass('odd');
							}						
						}  
					}
					
					$('.add').off('click');
							
					$('.add').val(labels['add']);
					$('.add').click(function() {
						add_monitoring(id);
					});
				}
			});
		}
		
		
		function add_values(id)
		{
			var type       = $("#al_type_"+id).text();
			var frequency  = $("#al_frequency_"+id).text();
			var state      = $("#al_state_"+id).text();
			var arguments  = $("#al_arguments_"+id).text();
			
			$('#type option').each(function(index) {
				if ($(this).text() == type){
					$('#type').val($(this).attr('value'));
				}
			});
			
			change_type(type);
			
			$('#frequency').val(frequency);
			$('#state').val(state);
			
			$('#arguments').val(arguments);
			
			$('.add').unbind('click');
			$('.add').val(labels['update']);
			
			$('.add').bind('click', function() {
				modify_monitoring(id);
			});
		}
		
		
		function modify_monitoring(id)
		{
			//Show load info
			var l_content = '<img src="<?php echo OSSEC_IMG_PATH?>/loading.gif" border="0" align="absmiddle"/><span style="padding-left: 5px;">'+ossec_msg['update_m_entry']+'</span>';										
					
            $("#al_load").html(l_content);

			
			var form_id = "al_modify_form_2";
			var token   = Token.get_token('al_entries');	
												
			$.ajax({
				type: "POST",
				url: "ajax/actions.php",
				data: $('#'+form_id).serialize() + "&ip="+$('#ip').val()+ "&action=modify_monitoring_entry&id="+id+"&token="+token,
				dataType: "json",
				error: function(data){
					$("#al_load").html('');
					
					$("#info_error").html(notify_error(ossec_msg['unknown_error']));
					$('#info_error').show();
					
					$('.add').off('click');
							
					$('.add').val(labels['add']);
					$('.add').click(function() {
						add_monitoring(id);
					});
				},
				success: function(html){
					
					$("#al_load").html('');
					
					if (typeof(html) != 'undefined' && html != null)
					{
						if (html.status == 'error')
						{
							if (typeof(html.data.html_errors) != 'undefined' && html.data.html_errors != '')
							{
								$("#info_error").html(notify_error(html.data.html_errors));
								$('#info_error').show();
							}
							else
							{
								$("#info_error").html('');
								
								$("#al_load").html("<div class='cont_al_message'><div class='al_message'>"+notify_error(html.data)+"</div></div>");
								$("#al_load").fadeIn(2000);
								$("#al_load").fadeOut(4000);
							}
						}
						else
						{
							$("#info_error").html('');
							
							$('#m_entry_'+id).html(html.data);
							
							$('#t_body_mt tbody tr td').removeClass('odd even');
								$('#t_body_mt tbody tr:even td').addClass('even');
								$('#t_body_mt tbody tr:odd td').addClass('odd');
							
							
							//Add new token
							Token.add_to_forms();
						}  
					}	

					$('.add').off('click');
							
					$('.add').val(labels['add']);
					$('.add').click(function() {
						add_monitoring(id);
					});
				}
			});
		}
		
		function modify_host_data()
		{
			var form_id = "al_modify_form_1";
			var token   = Token.get_token('al_entries');	
														
			$.ajax({
				type: "POST",
				url: "ajax/actions.php",
				data: $('#'+form_id).serialize() + "&action=modify_host_data&token="+token,
				dataType: "json",
				beforeSend: function(xhr){
				
					$("#info_error").hide();
					$("#info_error").html('');
					$("#al_load").html('');					
				},
				error: function(data){
								
					$("#info_error").html(notify_error(ossec_msg['unknown_error']));
					$('#info_error').show();
				},
				success: function(html){
															
					if (typeof(html) != 'undefined' && html != null)
					{
						if (html.status == 'error')
						{
							if (typeof(html.data.html_errors) != 'undefined' && html.data.html_errors != '')
							{
								$("#info_error").html(notify_error(html.data.html_errors));
								$('#info_error').show();
							}
							else
							{
								$("#info_error").html(notify_error(html.data));
								$('#info_error').show();
							}
						}
						else{
							document.location.href='/ossim/ossec/agentless.php';
						}  
					}	
				}
			});
		}
	
		function change_type(t_value)
		{
			if (t_value != '')
			{
				var type = t_value;
			}
			else
			{
				var type = $('#type').val();
			}
				
			if (type.match("_diff") != null)
			{
				$('#state_txt').text("Periodic_diff");
				$('#state').val("periodic_diff");
			}
			else
			{
				if (type.match("_integrity") != null)
				{
					$('#state_txt').html("Periodic");
					$('#state').val("periodic");
				}
			}
		}
		
		function change_arguments ()
		{
			var type = $('#type').val();            
			
			if (type.match("_diff") != null)
			{
				$('#arguments').text("");
            }
			else if (type.match("_integrity") != null)
			{
				$('#arguments').text("/bin /etc /sbin");
            }
		}	
	
		$(document).ready(function(){
			
			
			$('#ppass').on('blur', function() 
			{
    			var val = $(this).val();
    			
    			if(val == '')
    			{
        			$('#use_su').prop('checked', false);
    			}
    			else
    			{
        			$('#use_su').prop('checked', true);
    			}
			});
			
			//Add token to form
			Token.add_to_forms();
		
			$('textarea').elastic();
			
			$('#t_body_mt tr td').removeClass('odd even');
			$('#t_body_mt tr:even td').addClass('even');
			$('#t_body_mt tr:odd td').addClass('odd');
			
			
			$('.update').bind('click', function() {
				modify_host_data();
			});
			
						
			$('.add').bind('click', function() {
				add_monitoring();
				$('.add').val(labels['add']);
			});
			
			$('#type').bind('change', function() {
				change_type('');
				change_arguments();
			});
			
			var config = {   
				validation_type: 'complete', // single|complete
				errors:{
					display_errors: 'all', //  all | summary | field-errors
					display_in: 'info_error'
				},
				form : {
					id  : 'al_modify_form_1',
					url : 'al_modifyform.php?al_data=hd'
				},
				actions: {
					on_submit:{
						id: 'send',
						success:  $('#send').val(),
						checking: '<?php echo _('Saving')?>'
					}
				}
			};
		
			ajax_validator = new Ajax_validator(config);
			
			$("#arguments").tipTip({maxWidth: 'auto'});
		});

	</script>
</head>

<body>

<?php

    //Local menu
    include_once AV_MAIN_ROOT_PATH.'/local_menu.php';
?>

<div class='c_back_button' style='display:block;'>
     <input type='button' class="av_b_back" onclick="document.location.href='/ossim/ossec/agentless.php';return false;"/>
</div>

<?php

if (!empty($critical_error))
{
	Util::print_error($critical_error);	
	Util::make_form('POST', '/ossim/ossec/agentless.php');	
}
else
{   
    ?>
    <div id='info_error' style="<?php echo $display?>">
    	<?php
        if (!empty($info_error))
        {
        	$config_nt = array(
        			'content' => $info_error,
        			'options' => array (
        				'type'          => 'nf_error',
        				'cancel_button' => FALSE
        			),
        			'style'   => 'width: 80%; margin: 20px auto; text-align: left;'
        		); 
        						
        	$nt = new Notification('nt_1', $config_nt);
        	
        	echo $nt->show();
        }
    	?>
	</div>
	
	<div class="legend">
        <?php echo _('Values marked with (*) are mandatory');?>
    </div>		
	
	<table id='table_form'>
		<tr>
			<td class='subsection_1'>
												
				<form method="POST" name="al_modify_form_1" id="al_modify_form_1" action="al_modifyform.php?al_data=hd">
				<table width='100%'>				
					<tr>
						<td colspan='2' class='headerpr'><span><?php echo _('Modifying Host Data Configuration')?></span></td>
					</tr>
				
					<tr>
						<th>
						    <label for='hostname'><?php echo _('Hostname') . required();?></label>
						</th>
						<td class="left">
							<input type="text" class='vfield' name="hostname" id="hostname" value="<?php echo $hostname;?>"/>							
						</td>
					</tr>	
					
					<tr>
						<th>
						    <label for='ip'><?php echo _('IP');?></label>
						</th>
						<td class="left">
							<div id="ip_back" class='bold'><?php echo $ip;?></div>
							<input type="hidden" class='vfield' name="ip" id="ip" value="<?php echo $ip?>"/>
						</td>
					</tr>

					<tr>
						<th>
						    <label for='ip'><?php echo _('Sensor'); ?></label>
						</th>
						<td class="left">
							<div id="sensor_back" class='bold'><?php echo $sensor_name;?></div>
							<input type="hidden" class='vfield' name="sensor" id="sensor" value="<?php echo $sensor_id?>"/>
						</td>
					</tr>
					  
					<tr>
						<th>
						    <label for='user'><?php echo _('User') . required();?></label>
						</th>
						<td class="left">
							<input type="text" class='vfield' name="user" id="user" value="<?php echo $user;?>"/>							
						</td>
					</tr>
					
					<tr>
						<th>
						    <label for='pass'><?php echo _('Password') . required();?></label>
						</th>
						<td class="left">							
							<input type="password" class='vfield' name="pass" id="pass" value="<?php echo $pass;?>" autocomplete="off"/>							
						</td>
					</tr>

					<tr>
						<th>
						    <label for='passc'><?php echo _('Password confirm') . required();?></label>
						</th>
						<td class="left">
							<input type="password" class='vfield' name="passc" id="passc" value="<?php echo $passc;?>" autocomplete="off"/>							
							<div class='al_advice'>
							    <?php echo _('(*) If you want to use public key authentication instead of passwords, you need to provide NOPASS as Normal Password')?>
							</div>
						</td>
					</tr>
						
					<tr>
						<th>
						    <label for='ppass'><?php echo _('Privileged Password'); ?></label>
						</th>
						<td class="left">
							<input type="password" class='vfield' name="ppass" id="ppass" value="<?php echo $ppass;?>" autocomplete="off"/>
						</td>
					</tr>
					
					<tr>
						<th>
						    <label for='ppassc'><?php echo _('Privileged Password confirm'); ?></label>
						</th>
						<td class="left">
							<input type="password" class='vfield' name="ppassc" id="ppassc" value="<?php echo $ppassc;?>" autocomplete="off"/>
							<div class='al_advice'>
							    <?php echo _("(*) If you want to add support for \"su\", you need to provide Privileged Password")?>
							</div>
						</td>
					</tr>
					
					<tr>
						<th>
						    <label for='use_su'><?php echo _('Enable use_su option'); ?></label>
						</th>
						<td class="left">
							<input type="checkbox" class='vfield' name="use_su" id="use_su" value="1" <?php echo ($use_su)? "checked" : "" ?>/>
						</td>
					</tr>

					<tr>
						<th>
						    <label for='descr'><?php echo _('Description');?></label>
						</th>
						<td class="left nobborder">
							<textarea name="descr" id="descr" class='vfield'><?php echo $descr;?></textarea>
						</td>
					</tr>
					
					<tr>
						<td colspan="2" class="cont_update">
							<input type="button" class="update" id='send' value="<?php echo _('Update')?>"/>
						</td>
					</tr>
				</table>
				</form>
			</td>
		
			<td class='subsection_2'>
				<form method="POST" name="al_modify_form_2" id="al_modify_form_2" action="al_modifyform.php?al_data=me">
				<input type='hidden' name='ip'       value='<?php echo $ip;?>'/>
				<input type='hidden' name='sensor'   value='<?php echo $sensor_id;?>'/>
				<table width='100%'>
					<tr>
						<td colspan='2' class='headerpr'><span><?php echo _("Modifying Monitoring Entries")?></span></td>
					</tr>
				
					<tr>
						<th>
						    <label for='type'><?php echo _('Type'). required();?></label>
						</th>
						<td class="left">
							<select name="type" id="type">
							<?php
								foreach ($array_types as $k => $v)
									echo "<option value='$k'>$v</option>";
							?>
							</select>							
						</td>
					</tr>
		
					<tr>
						<th>
						    <label for='frequency'><?php echo _('Frequency') . required();?></label>
						</th>
						<td class="left">
							<input type="text" name="frequency" id="frequency" value="86400"/>					
						</td>
					</tr>
			
					<tr>
						<th>
						    <label for='state'><?php echo _('State'); ?></label>
						</th>
						<td class="left">
							<div id="state_txt" class='bold'><?php echo _('Periodic')?></div>
							<input type="hidden" class="state" id='state' name='state' value="periodic"/>
						</td>
					</tr>
		
					<tr>
						<th>
							<label for='arguments'><?php echo _('Arguments');?></label>
						</th>
						<td class="ct_mandatory nobborder left">
							<?php
							$arg_info = "<table class='ct_opt_format' border='1'>
											<tbody>
												<tr>
												    <td class='ct_bold noborder center'><span class='ct_title'>"._('Please Note').":</span></td>
												</tr>
												<tr>
													<td class='noborder'>
														<div class='ct_opt_subcont'>
															<img src='".OSSIM_IMG_PATH."/bulb.png' align='absmiddle' alt='Bulb'/>
															<span class='ct_bold'>"._("If type value is Generic Command Diff").":</span>
															<div class='ct_pad5'>
																<span>". _("Ex.: ls -la /etc; cat /etc/passwd")."</span>
															</div>
														</div>
														<br/>
														<div class='ct_opt_subcont'>
															<img src='".OSSIM_IMG_PATH."/bulb.png' align='absmiddle' alt='Bulb'/>
															<span class='ct_bold'>". _("Other cases").":</span>
															<div class='ct_pad5'><span>"._("Ex.: bin /etc /sbin")."</span>
															</div>
														</div>
													</td>
												</tr>
											</tbody>
										</table>";
							?>
							<textarea name="arguments" id="arguments" title="<?php echo $arg_info?>">/bin /etc /sbin</textarea>
						</td>
					</tr>
					
					<tr>
						<td colspan='2' style='padding:5px 5px 5px 0px;' class='right nobborder'>
							<input type="button" class="small av_b_secondary add" name='add' id='send' value="<?php echo _('Add')?>"/>
						</td>
					</tr>
					
					<tr><td class='al_sep' id='al_load' colspan='2'></td></tr>
					
					<tr>
						<td colspan='2'>
							<table id='monitoring_table'>
								<thead class='center'>
									<tr>
									    <th colspan='5' class='headerpr center;' style='padding: 3px 0px;'><?php echo _('Monitoring entries added')?></th>
									</tr>
									<tr>
										<th class="al_type"><?php echo _('Type')?></th>
										<th class="al_frequency"><?php echo _('Frequency')?></th>
										<th class="al_state"><?php echo _('State')?></th>
										<th class="al_arguments"><?php echo _('Arguments')?></th>
										<th class="al_actions"><?php echo _('Actions')?></th>
									</tr>
								</thead>
								<tbody id='t_body_mt'>
									<?php 
									if (count($monitoring_entries) > 0)
									{																
										foreach ($monitoring_entries as $k => $v)
										{
											echo "<tr id='m_entry_".$v['id']."'>
													<td class='center' id='al_type_".$v['id']."'>". $v['type']."</td>
													<td class='center' id='al_frequency_".$v['id']."'>".$v['frequency']."</td>
													<td class='center' id='al_state_".$v['id']."'>".$v['state']."</td>
													<td class='left' id='al_arguments_".$v['id']."'>".$v['arguments']."</td>
													<td class='center nobborder'>
														<a onclick=\"add_values('".$v['id']."')\"><img src='". OSSIM_IMG_PATH."/pencil.png' align='absmiddle' alt='"._('Modify monitoring entry')."' title='"._('Modify monitoring entry')."'/></a>
														<a onclick=\"delete_monitoring('".$v['id']."')\" style='margin-right:5px;'><img src='". OSSIM_IMG_PATH."/delete.gif' align='absmiddle' alt='"._('Delete monitoring entry')."' title='"._('Delete monitoring entry')."'/></a>
													</td>
												</tr>"; 
										}
									}
									else
									{
										$info_entries = ($error_m_entries != null) ? $error_m_entries : _('No monitoring entries added');
										echo "<tr class='al_no_added'><td class='noborder' colspan='5'><div class='al_info_added'>$info_entries</div></td></tr>";
									}
									?>
								</tbody>
							</table>
						</td>
					</tr>
													
					
				</table>
				</form>
			</td>
		</tr>
	</table>	
	<?php 
	} 
?>
</body>
</html>