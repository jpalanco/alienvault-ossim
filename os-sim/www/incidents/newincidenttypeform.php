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

Session::logcheck('analysis-menu', 'IncidentsTypes');

$validate  = array (
	'id'       => array('validation' => 'OSS_ALPHA, OSS_SPACE, OSS_PUNC'                   , 'e_message' => 'illegal:' . _('Id')),
	'descr'    => array('validation' => 'OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_AT, OSS_NL'   , 'e_message' => 'illegal:' . _('Description')),
	'custom'   => array('validation' => 'OSS_DIGIT, OSS_NULLABLE'                          , 'e_message' => 'illegal:' . _('Custom'))
);

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
elseif (POST('ajax_validation_all') == TRUE || (isset($_POST['id']) && !empty($_POST['id'])))
{
	$validation_errors = validate_form_fields('POST', $validate);	
			
	$data['status'] = 'OK';
	$data['data']   = $validation_errors; 
								
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
		if (is_array($validation_errors) && !empty($validation_errors)){
			$info_error = $validation_errors;
		}
		else
		{
			$id     = POST('id');
			$descr  = POST('descr');
			$custom = POST('custom');
		
			$db   = new ossim_db();
			$conn = $db->connect();
		
			$custom_type = ($custom == 1) ? 'custom' : '';
			$res = Incident_type::insert($conn, $id, $descr, $custom_type);
			$db->close($conn);
			
			if ($res !== true){
				$info_error[] = $res;
			}
			else
			{
				if ($custom == 1)
				{ 
					header('Location: modifyincidenttypeform.php?msg=1&id='.urlencode($id));
				}
				else
				{
					header('Location: incidenttype.php?msg=2');
				}
				exit();
			}
		}
	}
}
	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo _("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	
	<script type="text/javascript" src="../js/jquery.min.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.min.js"></script>
	<script type="text/javascript" src="../js/notification.js"></script>
	<script type="text/javascript" src="../js/messages.php"></script>
	<script type="text/javascript" src="../js/ajax_validator.js"></script>
	<script type="text/javascript" src="../js/utils.js"></script>
	<script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
	<script type="text/javascript" src="../js/jquery.tipTip.js"></script>
	
	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<link rel="stylesheet" type="text/css" href="../style/tipTip.css"/>
	
	<script type="text/javascript">
		
		function change_button()
		{
			if ($('#type_custom').is(":checked"))
			{
				$('#send').attr("value", "<?php echo _('Next').' >>'?>");
			}
			else
			{
				$('#send').attr("value", "<?php echo _('Save')?>");
			}
		}		
		
		$(document).ready(function() {
			$('textarea').elastic();
			$('#type_custom').bind('click', function()  { change_button()});
									
			var config = {   
				validation_type: 'complete', // single|complete
				errors:{
					display_errors: 'all', //  all | summary | field-errors
					display_in: 'av_info'
				},
				form : {
					id  : 'f_nit',
					url : 'newincidenttypeform.php'
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
			
			$('#send').click(function() { 
			
				if (ajax_validator.check_form() == true)
				{
					$('#f_nit').submit();
				}
				
				change_button();
			});
			
		});
	</script>
	
	<style type='text/css'>
		
		#type_id, textarea 
		{  
    		margin: 0px; 
    		padding:0px; 
    		width: 99%;
		}
	    
		#type_id 
		{ 
			height:18px; 
			width: 99%;
			padding: 2px 0px;
		}
		
		textarea 
		{
    		height: 40px;   
		}
		
		#cont_new_ticket 
		{
			width: 700px;			
			border: 1px solid #E4E4E4;
			background: none;
		}
		
		#ticket_ok 
		{
			padding: 20px 0px 50px 0px; 
			margin:auto; 
			width: 400px; 
			text-align:center;
		}
		
		#av_info
		{
			width: 80%;
			margin: 20px auto;
		}
		
	</style>
</head>
<body>		
    <div class='c_back_button' style='display:block;'>
        <input type='button' class="av_b_back" onclick="document.location.href='../incidents/index.php';return false;"/>
    </div>

	<div id='av_info'>
		<?php
		if (!empty($info_error))
		{
			$config_nt = array(
				'content' => implode("<br/>", $info_error),
				'options' => array (
					'type'          => 'nf_error',
					'cancel_button' => FALSE
				),
				'style'   => 'width: 80%; margin: 10px auto; padding: 10px 0px; text-align: left;'
			); 
							
			$nt = new Notification('nt_1', $config_nt);
			$nt->show();
		}
		?>
	</div>
	
	<form name='f_nit' id='f_nit' method="POST" action="newincidenttypeform.php">
	
		<table align="center" id='cont_new_ticket'>
    		<tr>
    			<th colspan="2" class="headerpr"><?php echo _('New ticket type');?></th>
    		</tr>
			<tr>
				<th>
				    <label for="type_id"><?php echo _('Type id') . required();?></label>
				</th>
				<td class="left" valign='middle'>
					<input type="text" id="type_id" name="id" class='vfield' value='<?php echo $id;?>'/>
				</td>
			</tr>
			
			<tr>
				<th>
				    <label for="type_descr"><?php echo _('Description') . required();?></label>
				</th>
				<td class="left" valign='middle'>
					<textarea id="type_descr" class='vfield' name="descr"><?php echo $descr;?></textarea>
				</td>
			</tr>
			
			<tr>
				<th> 
				    <label for="type_custom"><?php echo _('Custom') . required();?></label>
				</th>
				<td class="left">
					<?php $checked = ($custom == 1) ? "checked='checked'" : "" ?>
					<input type="checkbox" class='vfield' name="custom" id='type_custom' value="1" <?php echo $checked?>/>
				</td>
			</tr>  
			
			<tr>
				<td colspan="2" align="center" valign="top" class='noborder' style='padding: 10px 0px;'>
					<?php $send_text = ($custom == 1) ? _('Next')." >>" : _('Save') ?>
					<input type="button" id='send' name='send' value="<?php echo $send_text?>"/>
				</td>
			</tr>
		</table>
	</form>

</body>
</html>