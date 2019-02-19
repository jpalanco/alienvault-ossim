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

Session::logcheck("analysis-menu", "IncidentsTypes");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo _("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<script type="text/javascript" src="../js/jquery.min.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.min.js"></script>
	<script type="text/javascript" src="../js/jquery.tipTip.js"></script>
	<script type="text/javascript" src="../js/notification.js"></script>
	<script type="text/javascript" src="../js/ajax_validator.js"></script>
	<script type="text/javascript" src="../js/utils.js"></script>
	<script type="text/javascript" src="../js/messages.php"></script>
	
	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<link rel="stylesheet" type="text/css" href="../style/tipTip.css" />
	
	<script type='text/javascript'>
		var options = ["Checkbox", "Select box", "Radio button", "Slider"];

		Array.prototype.in_array = function(p_val) {
			for(var i = 0, l = this.length; i < l; i++) 
			{
				if(this[i] == p_val) 
				{
					return true;
				}
			}
			return false;
		}

		function enable_ta(opt)
		{
			var select_opt = $('#custom_typef option:selected').attr('value');
			
			if (options.in_array(select_opt) == true)
			{
				$('#custom_optionsf').removeAttr("disabled");
				$('#custom_optionsf').removeClass();
				$('#custom_optionsf').addClass("custom_optionsf_en info vfield");
				$('#custom_optionsf').val(opt);
				$('#req_fo').show();
			}
			else
			{
				$('#custom_optionsf').attr("disabled", "disabled");
				$('#custom_optionsf').removeClass();
				$('#custom_optionsf').addClass("custom_optionsf_dis info vfield");
				$('#custom_optionsf').val('');
				$('#req_fo').hide();
			}
			
		}

		function add_ticket()
		{
			config.form.url = 'modifyincidenttype.php?action=add';
				
			if ( ajax_validator.check_form() == true )
			{
				$("#crt").attr('action', 'modifyincidenttype.php?action=add');
				$("#crt").submit();
			}					
		}
	

		function modify_ct()
		{
			config.form.url = 'modifyincidenttype.php?action=modify_ct';
				
			if ( ajax_validator.check_form() == true )
			{
				$("#crt").attr('action', 'modifyincidenttype.php?action=modify_ct');
				$("#crt").submit();
			}
		}
		
		function edit_ticket(id)
		{
			var oldid = id;
			var id    = "#"+id;
			
			$("#av_info").hide();
			$("#av_info").html('');
			
			var id_ct     =  $("#id_crt").val();
				
			var name      =  $(id+"_name").text();
			
			var type      =  $(id+"_type").text();
			var options   =  $(id+"_options").text();
			var required  =  $(id+"_required").attr('alt').match(/Tick/);
			
					
			$("#header_nct").html("<?php echo _("Modify Custom Type")?>");
			
			if ($("#id_crt").length > 1){
				$("#id_crt").attr("value", old_names[oldid]);
			}
			else{
				$("#id_crt").after("<input type='hidden' name='old_name' class='vfield' id='old_name' value='"+old_names[oldid]+"'/>")
			}
			
			$('#custom_namef').val(name);
			$('#custom_typef').val(type);
			
			enable_ta(options);
			
			if ( required ) {
				$('#custom_requiredf').attr("checked", "checked");
			}
			
			$('.ct_add').html("<input type='button' id='add_button' value='<?php echo _("Update")?>' class='small' onclick=\"modify_ct();\"/>");
			$("#cancel_cont").html("<input type='button' id='cancel' class='av_b_secondary small' value='<?php echo _("Cancel")?>' onclick=\"cancel_ticket();\"/>"); 
			window.scrollTo(0, 0);	
			$("#custom_namef").focus();
		}

		function move_field(id,oldpos,newid)
		{
			var oldid    = id;
			var load_id  = 'loading_'+id;
			var id       = "#"+id;
			
			$("#"+load_id).html("<img src='../pixmaps/loading.gif' style='width: 12px; height: 12px;' align='absmiddle'/>");

			$("#av_info").hide()
			$("#av_info").html('');

			$('#custom_namef').val($(id+"_name").text());
			$('#custom_typef').val($(id+"_type").text());
			
			$('#custom_optionsf').val($(id+"_options").text());
			enable_ta($('#custom_optionsf').val());
							
			$('#oldpos').val(oldpos);
			$('#newpos').val(positions[newid]);
					
			if ($("#id_crt").length > 1)
			{
				$("#id_crt").attr("value", old_names[oldid]);
			}
			else
			{
				$("#id_crt").after("<input type='hidden' class='vfield' name='old_name' id='old_name' value='"+old_names[oldid]+"'/>");
			}
				
			config.form.url = 'modifyincidenttype.php?action=modify_pos';
				
			if ( ajax_validator.check_form() == true )
			{
				$("#crt").attr('action', 'modifyincidenttype.php?action=modify_pos');
				$("#crt").submit();
			}
			else
			{
				$("#"+load_id).html('');
			}
		}

		function cancel_ticket()
		{
			$("#header_nct").html("<?php echo _("New Custom Type")?>");
			$("#cancel, #old_name").remove();
			
			$('#custom_namef, #custom_typef').attr("value", "");
			$('#custom_optionsf').attr("disabled", "disabled");
			$('#custom_optionsf').removeClass();
			$('#custom_optionsf').addClass("custom_optionsf_dis info vfield");
			$('#custom_optionsf').val('');
			$('#custom_requiredf').removeAttr("checked");
			
			$('.ct_add').html("<input type='button' id='add_button' value='<?php echo _("Add")?>' class='small' onclick=\"add_ticket();\"/>");
			
		}
		
		function delete_ticket(name)
		{
			$('#custom_namef').val(name);
			config.form.url = 'modifyincidenttype.php?action=delete';
				
			if ( ajax_validator.check_form() == true )
			{
				$("#crt").attr('action', 'modifyincidenttype.php?action=delete');
				$("#crt").submit();
			}
		}
		
		var config = {   
			validation_type: 'complete', // single|complete
			errors:{
				display_errors: 'all', //  all | summary | field-errors
				display_in: 'av_info'
			},
			form : {
				id  : 'crt',
				url : 'modifyincidenttype.php'
			},
			actions: {
				on_submit:{
					id: 'send',
					success: '<?php echo _('Save')?>',
					checking: '<?php echo _('Saving')?>'
				}
			}
		};

		$(document).ready(function() {
			$('#custom_typef').bind("change", function() { enable_ta('');});
			
			$(".info").tipTip({maxWidth: "300px"});
												
			ajax_validator = new Ajax_validator(config);
						
			$('#send').click(function() { 
				
				config.form.url = 'modifyincidenttype.php?action=modify';
				
				if ( ajax_validator.check_form() == true )
				{
					$("#crt").attr('action', 'modifyincidenttype.php?action=modify');
					$("#crt").submit();
				}
			});
		});

	</script>
	
	<style type='text/css'>
		#t_ctypes
		{
			margin: 0px auto;
			width: 700px;
			border: 1px solid #E4E4E4;
			background: none;
		}
		
		.thr
		{
    		border: none !important;
		}
						
		#ct_descr 
		{ 
    		height: 40px; 
    		width: 99% !important;    		
		} 
		
		#av_info
		{
			margin: 10px auto !important;
			padding: 10px 40px 0px 40px !important;
			width: 800px;
		}
		
		.ct_title
		{
			color: white !important;
		}
		
		.ct_opt_subcont 
		{
    		font-size: 10px; 
    		font-weight: normal; 
    		color: white !important; 
    		padding-left: 15px;
		}
		
		.ct_opt_subcont span 
		{ 
		    display: inline !important;    		
		}
		
		.ct_opt_format 
		{ 
			width: 300px !important;
			background: none; 
			color: white !important;			
		}
						
	</style>
	
</head>
	
<body>

<div class='c_back_button' style='display:block;'>
     <input type='button' class="av_b_back" onclick="document.location.href='../incidents/index.php';return false;"/>
</div>
    
<?php

$inctype_id = GET('id');

ossim_valid($inctype_id, OSS_ALPHA, OSS_SPACE, OSS_PUNC, 'illegal:' . _('Incident type'));

if (ossim_error()) 
{
    die(ossim_error());
}

$db   = new ossim_db();
$conn = $db->connect();
//
$custom_fields = array();

if ($inctype_list = Incident_type::get_list($conn, "WHERE id = '$inctype_id'")) 
{
    $inctype       = $inctype_list[0];
    $custom        = (preg_match('/custom/',$inctype->get_keywords())) ? 1 : 0;
    $custom_fields = Incident_custom::get_custom_types($conn,$inctype_id);
}

?>

<div id='av_info'>
	<?php
	if ( !empty($_GET['msg']) )
	{
		$var_tmp = "";
		
		switch($_GET['msg'])
		{
			case 1:
				$var_tmp = _('New Custom Ticket Type successfully created');
			break;
			
			case 2:
				$var_tmp = _('New Ticket Type successfully created');
			break;
			
			case 3:
				$var_tmp = _('Ticket Type successfully deleted');
			break;
		}
		
		if ($var_tmp != '')
		{
			$config_nt = array(
				'content' => $var_tmp,
				'options' => array (
					'type'          => 'nf_success',
					'cancel_button' => false
				),
				'style'   => 'width: 80%; margin: auto; text-align: left;'
			); 
									
			$nt = new Notification('nt_1', $config_nt);
			$nt->show();
		}
	}
	?>
</div>


<form id="crt" name="crt" method="POST" action="modifyincidenttype.php">			
    
    <input type="hidden" class='vfield' name="id" id='id_crt' value="<?php echo $inctype->get_id(); ?>" />
	<input type="hidden" class='vfield' id="oldpos" name="oldpos" value="0"/>
	<input type="hidden" class='vfield' id="newpos" name="newpos" value="0"/> 
       
	<table id='t_ctypes'>
		
		<tr>
            <th class="headerpr" colspan="2"><?php echo _('Edit Type');?></th>
        </tr>
		
		<tr>
			<th>
               <?php echo _('Ticket type')?>
			</th>
			<td class="nobborder ct_pad5"><?php echo $inctype->get_id();?></td>
		</tr>
		
		<tr>
			<th>
				<label for="descr"><?php echo _('Description') .required();?></label>
			</th>
			<td class="nobborder ct_pad5">
				<textarea name="descr" id='ct_descr' class='vfield'><?php echo $inctype->get_descr();?></textarea>
			</td>
		</tr>
		
		<?php 
		if ($custom) 
		{
			?>
		
			<tr style='display:none'>
				<th>
				    <label for="custom"><?php echo _('Custom');?></label>
				</th>
				<td class="left">
					<?php $checked = ( $custom  ) ? "checked='checked'" : "" ?>
					<input type="checkbox" class='vfield' name="custom" value="1" <?php echo $checked?>/>
				</td>
			</tr>
				
			<tr id="custom_type">
				<th class='thr'><?php echo _('Custom fields')?></th>
				<td class='transparent'>
					<table class='transparent' width='100%'>
						<tr>
							<td class='transparent'>
								<table width='100%' class='transparent' id='table_form_crt'>
									<tbody>
										<tr><td class='headerpr header_ct' colspan='2' id='header_nct'><?php echo _('New Custom Type')?></td></tr>
										
										<tr>
											<th class='ct'>
												<label for="custom_namef"><?php echo _('Field Name') . required()?></label>								
											</th>
											<td class="noborder left">
												<input type="text" class='vfield' id="custom_namef" name="custom_namef"/>
											</td>
										</tr>
										
										<tr>
											<th class='ct'>										
												<label for="custom_typef"><?php echo _('Field Type') . required()?></label>	
											</th>
											<td class="noborder left">
												<select type="text" class='vfield' id="custom_typef" name="custom_typef">
    												<option  value=''>-- <?php echo _("Select Types")?> --</option>
    												
    												<?php
    												$types = array(
        												'Asset', 
        												'Check Yes/No', 
        												'Check True/False', 
        												'Checkbox', 
        												'Date', 
        												'Date Range', 
        												'Map', 
        												'Radio button', 
        												'Select box', 
        												'Slider', 
        												'Textarea', 
        												'Textbox', 
        												'File'
    												);
    												
    												sort($types);
    												
    												foreach($types as $k => $v)
    												{
    													echo "<option style='text-align: left;' value='"._($v)."'>"._($v)."</option>";
    												}
    												?>
												</select>
											</td>
										</tr>
										
										<tr>
											<th class='ct'>												
												<label for="custom_optionsf"><?php echo _('Field Options')?>
												    <span id='req_fo' style="padding-left: 3px; display:none;">*</span>
												</label>												
											</th>
											
											<?php
											$info = "
												<table class='ct_opt_format' border='1'>
														<tbody>
														<tr><td class='ct_bold noborder'><span class='ct_title'>"._("Options Format Allowed")."</span></td></tr>
														<tr>
															<td class='noborder'>
																<div class='ct_opt_subcont'>
																	<img src='../pixmaps/bulb.png' align='absmiddle' alt='Bulb'/><span class='ct_bold'>"._("Type Radio and Check").":</span>
																	<div class='ct_padl25'>
																		<span>"._("Value1:Name1")."</span><br/>
																		<span>"._("Value2:Name2:Checked")."</span><br/>
																		<span>"._("...")."</span>
																	</div>
																</div>
															</td>
														</tr>
														<tr>
															<td class='noborder'>
																<div class='ct_opt_subcont'>
																	<img src='../pixmaps/bulb.png' align='absmiddle' alt='Bulb'/><span class='ct_bold'>"._("Type Slider").":</span> 
																	<div class='ct_padl25'>
																		<span>"._("Min, Max, Step")."</span><br/>
																	</div>
																</div>
															</td>
														</tr>
														<tr>
															<td class='noborder'>							
																<div class='ct_opt_subcont'>
																	<img src='../pixmaps/bulb.png' align='absmiddle' alt='Bulb'/><span class='ct_bold'>"._("Type Select Box").":</span> 
																	<div class='ct_padl25'>
																		<span>"._("Value1:Text1")."</span><br/>
																		<span>"._("Value2:Text2:Selected")."</span><br/>
																		<span>"._("...")."</span><br/>
																	</div>
																</div>
															</td>
														</tr>
														</tbody>
													</table>";
											?>
											
											<td class="noborder left">
												<textarea id="custom_optionsf" class='custom_optionsf_dis info vfield' title="<?php echo $info?>" name="custom_optionsf" disabled="disabled"></textarea>
											</td>
										</tr>
									
										<tr>
											<th>
											     <label for="custom_requiredf"><?php echo _('Required Field')?></label>
											</th>
											<td class="noborder left">
											     <input type="checkbox" class='vfield' id="custom_requiredf" name="custom_requiredf" value='1'/>
											</td>
										</tr>
										
										<tr><td class='noborder ct_sep' colspan='2'></td></tr>						
										
										<tr>
											<td colspan='2' class='noborder' width='100%'>
												<div id='cancel_cont' style='float: left; width: 150px; text-align: left;'></div>
												<div class='ct_add' style='float: right; width: 150px; text-align: right;'>
													<div><input type="button" id="add_button" value="<?php echo _("Add")?>" class="small" onclick="add_ticket();"/></div>
												</div>
											</td>
										</tr>
										
										<tr><td class='noborder ct_sep' colspan='2'></td></tr>		
									</tbody>
								</table>
							</td>
						</tr>
					
						<?php 
						if (count($custom_fields) > 0) 
						{ 
							?>					
							<tr>
								<td class='noborder'>
									<table width='100%' class='noborder' id='ct_table'>
										<tbody>
											<tr><td class='headerpr header_ct' colspan='5'><?php echo _('Custom Types Added')?></td></tr>
											<tr>
												<th><?php echo _('Field Name')?></th>
												<th style='width: 100px;'><?php echo _('Field Type')?></th>
												<th><?php echo _('Options')?></th>
												<th><?php echo _('Required')?></th>
												<th><?php echo _('Actions')?></th>
											</tr>
											<script type='text/javascript'>
												var old_names = new Array(<?php echo count($custom_fields)?>);
												var positions = new Array(<?php echo count($custom_fields)?>);
											</script>
											<?php 
											foreach ($custom_fields as $cf) 
											{
												$class = ( $c % 2 == 0 ) ? 'odd' : 'even';
												$c++;
												$unique_id = "tr$c";
												
											?>
											
											<tr class='<?php echo $class?>' id='<?php echo $unique_id?>'>
												<td id='<?php echo $unique_id."_name"?>' class="noborder left ct_name"><?php echo $cf["name"]?><span style='margin-left: 3px;' class='loading' id='<?php echo "loading_".$unique_id?>'><span></td>
												<td id='<?php echo $unique_id."_type"?>' class="noborder ct_type"><?php echo $cf["type"]?></td>
												<td id='<?php echo $unique_id."_options"?>' class="noborder left"><?php echo implode("<br/>", explode("\n",$cf["options"]))?></td>
												<td class="noborder ct_required">
													<?php 
														$path_image = '../pixmaps/tables/';
														$image_required = ( $cf["required"] == 1 ) ? 'tick-small-circle.png' : 'cross-small-circle.png';
														$alt_required   = ( $cf["required"] == 1 ) ? 'Tick Circle' : 'Cross Circle';
														echo "<img id='".$unique_id."_required' src='".$path_image.$image_required."' alt='".$alt_required."'/>"; 
													?>
												</td>
												<td class="noborder ct_actions">
													<script type='text/javascript'>
														old_names['<?php echo $unique_id?>'] = "<?php echo $cf["name"]?>";
														positions['<?php echo $unique_id?>'] = "<?php echo $cf["ord"]?>";
													</script>
													<input type="image" src="../vulnmeter/images/delete.gif" class="ct_icon" onclick="delete_ticket('<?php echo $cf["name"]?>');"/>
													<a style='cursor:pointer' class="ct_icon" onclick="edit_ticket('<?php echo $unique_id?>');"><img src="../vulnmeter/images/pencil.png" alt='<?php echo _("Edit")?>' title='<?php echo _("Edit")?>'/></a>

													<?php 
													if ($c<count($custom_fields)) 
													{ 
														?>
														<a style='cursor:pointer' class="ct_icon" onclick="move_field('<?php echo $unique_id?>','<?php echo $cf["ord"]?>','tr<?php echo $c+1?>');"><img src="../pixmaps/theme/arrow-skip-270.png" alt='<?php echo _("Down")?>' title='<?php echo _("Down")?>'/></a>
														<?php
													} 
													else 
													{ 
														?>
														<img src="../pixmaps/theme/arrow-skip-270.png" style="filter: alpha(opacity=30); opacity: .3"/>
														<?php 
													}
													
													if ( $c>1 )  
													{ 
														?>
														<a style='cursor:pointer' class="ct_icon" onclick="move_field('tr<?php echo $c?>','<?php echo $cf["ord"]?>','tr<?php echo $c-1?>');"><img src="../pixmaps/theme/arrow-skip-090.png" alt='<?php echo _("Up")?>' title='<?php echo _("Up")?>'/></a>
														<?php 
													} 
													else 
													{ 
														?>
														<img src="../pixmaps/theme/arrow-skip-090.png" style="filter: alpha(opacity=30); opacity: .3"/>
														<?php
													} 
													?>

												</td>
											</tr>
											<?php 
											} 
											?>
										</tbody>
									</table>
								</td>
							</tr>
							<?php 
						} 
						?>
					
					</table>
				</td>
			</tr>
			<?php 
		} 
		?>
		<tr>
			<?php 
			if ($custom)
			{
				?>
				<td class="nobborder">&nbsp;</td>
				<td style="text-align:center; padding: 10px 0px;" class="nobborder">
					<input type="button" name='send' id='send' value="<?php echo _('Save')?>"/>
				</td>
				<?php
			}
			else
			{
				?>
				<td style="text-align:center; padding: 10px 0px;" class="nobborder" colspan='2'>
					<input type="button" name='send' id='send' value="<?php echo _('Save')?>"/>
				</td>
				<?php
			}
			?>
		</tr>
	</table>

</form>

</body>
</html>
