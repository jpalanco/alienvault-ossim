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


Session::logcheck("configuration-menu", "Osvdb");


$user       = Session::get_session_user();

$db         = new ossim_db();
$conn       = $db->connect();

$vuser      = POST('user');
$ventity    = POST('entity');
$title      = POST('title');
$doctext    = POST('doctext');
$keywords   = POST('keywords');

$info_error = NULL;
$error      = FALSE;


if (isset($title) || isset($doctext)) 
{
	ossim_valid($vuser, 		OSS_USER, OSS_NULLABLE, 	'illegal:' . _("User"));
	ossim_valid($ventity, 		OSS_HEX, OSS_NULLABLE,		'illegal:' . _("Entity"));
	ossim_valid($title, 		OSS_TEXT,					'illegal:' . _("Tittle"));
	ossim_valid($keywords, 		OSS_TEXT, OSS_NULLABLE,    	'illegal:' . _("Keywords"));
	
	if (ossim_error())
	{
		$info_error[] = ossim_get_error();
		ossim_clean_error();
		$error = TRUE;
	}
		
	if ($doctext == "") 
	{
		$info_error[] = _("Error in the 'text' field (missing required field)");
		$error        = TRUE;
	}
	
	if ($error == FALSE) 
	{
		$parser = new KDB_Parser();
		$parser->proccess_file($doctext);
	
		$info_error = $parser->is_valid();
				
		if(count($info_error) > 0)
		{
			$error = TRUE;
		}
	}
	
	if ($error == FALSE) 
	{
		$ctx = "";
		
		if($vuser != ""){
			$in_charge = $vuser;
		}
		elseif($ventity != "") 
		{
			$in_charge = $ventity;
		}
		else
		{
			$in_charge = 0;
		}		
				   
		$id_inserted = Repository::insert($conn, $title , $doctext , $keywords, $in_charge);		
	}
}

$sintax           = new KDB_Sintax();
$labels_condition = $sintax->_labels_condition;
$labels_actions   = $sintax->_labels_actions;
$labels_operators = $sintax->_labels_operators;
$labels_variables = $sintax->_labels_variables;

$help_msgs        = array();

$users            = Session::get_users_to_assign($conn);
$entities         = Session::get_entities_to_assign($conn);


$db->close();

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	
	<link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	
	<!-- JQuery -->
	<script type="text/javascript" src="/ossim/js/jquery.min.js"></script>
	<script type="text/javascript" src="/ossim/js/jquery-ui.min.js"></script>
	
	<!-- LightBox -->
	<script type="text/javascript" src="/ossim/js/greybox.js"></script>
	
	<!-- markItUp!  -->
	<link rel="stylesheet" type="text/css" href="/ossim/js/markitup/skins/simple/style.css">
	<link rel="stylesheet" type="text/css" href="/ossim/js/markitup/sets/kdb/style.css">
	<script type="text/javascript" src="/ossim/js/markitup/jquery.markitup.js"></script>
	<script type="text/javascript" src="/ossim/js/markitup/sets/kdb/set.js.php"></script>
	

	<script type="text/javascript">
		var help = new Array;
		var exam = new Array;
				
		
		function switch_user(select) 
		{
			if(select=='entity' && $('#entity').val()!='')
			{
				$('#user').val('');
			}
			else if (select=='user' && $('#user').val()!='')
			{
				$('#entity').val('');
			}
		}

		$(document).ready(function(){
			
			$('#markItUp').markItUp(mySettings);
			
			$('.view_doc').on('click', function()
            {
            	var title = '<?php echo _('Full Reference') ?>';
            	
				GB_show(title, this.href, 600, 850);

				return false;
            });
			
			
			$('.odd, .even').on('click', function()
			{
			
				var index = $(this).data('id');
				
				$('.nf_info').text(help[index-1]);
			
			});
			
			$('.c_back_button').show();
			
		});
			
	</script>

	<style type='text/css'>


		/*#style_body {background: #E3E3E3;}*/
		
		select{
			width:140px;
		}
		
		.cont_delete {
			width: 80%;
			text-align:center;
			margin: 10px auto;
		}
		
		html, body { margin: 0px; padding: 0px; }
		
		
		.error_item {
			padding:2px 0px 0px 20px; 
			text-align:left;
		}
		
		.ossim_success {
			width: auto;			
		}
		
		.ossim_error {
			width: auto;
			padding: 10px 10px 10px 40px;
			font-size: 12px;
		}
						
		
		table { 
			margin: auto; 
			background: transparent;
			border:none !important;
		}
		
		.rep_section{
			width: 90%;
			margin:auto;
			padding: 10px 0 3px 0;
		}
		
		.rep_label {
			text-align: left;
			font-weight: bold;
			padding-bottom: 5px; 
		}
		
		#back_icon {
			position:absolute;
			top:10px;
			left:15px;
		}

		.instruction{
			padding: 7px 0 4px 0;
			text-align:center;
			font-weight:bold;
		}
		
		#instruction_table{
			width:100%;
			height:100%;
			border:1px solid #CCC !important;
			margin-top:1px;
		}
		
		.ins_cont{
			height:110px;
			overflow:auto;
			border:1px solid #CCC;
			text-align:left;
			width:75%;
			margin:0 auto;
		}
		
		.odd, .even{
			cursor:pointer;
			padding:2px;
		}
		
		.c_back_button{
    		top: 10px;
    		left: 5px;
		}
		
	</style>

</head>
<body>

<div class="c_back_button">         
    <input type='button' class="av_b_back" onclick="document.location.href='index.php';return false;"/> 
</div>

<?php

if ((isset($title) || isset($doctext)) && $error == false)
{
	?>
	<table cellpadding='0' cellspacing='2' border='0' class="transparent">
		<tr>
			<td class="center">
				<?php
				$config_nt = array(
					'content' => _("Document inserted with id").": $id_inserted",
					'options' => array (
						'type'          => 'nf_success',
						'cancel_button' => false
					),
					'style'   => 'width: 90%; margin: 20px auto; text-align: left;'
				); 
								
				$nt = new Notification('nt_1', $config_nt);
				$nt->show();
				?>
			</td>
		</tr>
		
		<tr>
			<td class="center" style='padding-top: 30px;'><?php echo _("Do you want to attach a document file?")?> 
				<input type="button" onclick="document.location.href='repository_attachment.php?id_document=<?php echo $id_inserted ?>'" value="<?php echo _("YES")?>">&nbsp;
				<input type="button" onclick="document.location.href='index.php'" value="<?php echo _("NO")?>"/>
			</td>
		</tr>
	</table>
	<?php
	exit();
}


if ($error == TRUE) 
{ 
	$error_msg = "<div class='error_item' style='padding-left: 5px;'>"._("The following errors occurred").":</div>
			  <div class='error_item'>".implode($info_error, "</div><div class='error_item'>")."</div>";
	?>
	<div style='width:50%;margin:0 auto;'>
		<?php
		$config_nt = array(
			'content' => $error_msg,
			'options' => array (
				'type'          => 'nf_error',
				'cancel_button' => TRUE
			),
			'style'   => 'width: 90%; margin: 15px auto 0 auto; text-align: left;'
		); 
						
		$nt = new Notification('nt_1', $config_nt);
		$nt->show();
		?>
	</div>
	<?php
}
?>



<div style='width:95%;margin:25px auto 10px auto; clear: both;'>
    
	<form name="repository_insert_form" method="post" action="<?php echo $_SERVER['SCRIPT_NAME'] ?>" enctype="multipart/form-data">
	<table width="95%" style="border: solid 1px red;">	
		<tr valign="top">		
			<td class='nobborder left' style='width:55%;'>
				<table style='border:none;' width="100%">
					<tr>
						<th width="10%"><?php echo _("Title") ?></th>
						<td class='left nobborder'>
							<input type="text" name="title" value="<?php echo Util::htmlentities($title) ?>" style="padding:2px 0;width:476px;"/>
						</td>
					</tr>
					
					<tr>
						<th id='style_body' valign="top" width="10%"><?php echo _("Text") ?></th>
						<td style="text-align: left;padding-left:5px;" class='nobborder'>
                            <textarea id="markItUp" name="doctext" style="width:468px;"><?php echo Util::htmlentities($text, ENT_NOQUOTES) ?></textarea>
						</td>
					</tr> 

					<tr>
						<th width="10%"><?php echo _("Keywords") ?></th>
						<td class="left nobborder">
							<textarea name="keywords" style="width:476px;" id='keywords'><?php echo Util::htmlentities($keywords) ?></textarea>
						</td>
					</tr>
				</table>
				
				<div class='rep_section'>
					<div class='rep_label'><?php echo _("Make this document visible for")?>:</div>
					<div>
						<table cellspacing="0" cellpadding="0" class="transparent">
							<tr>
								<td class='nobborder'><span style='margin-right:3px'><?php echo _("User:");?></span></td>
								<td class='nobborder'>				
									<select name="user" id="user" onchange="switch_user('user');return false;">
									<?php
									$num_users = 0;
									
									if (Session::am_i_admin())
									{
									
										if ($vuser == 0) 
										{
											$selected =  "selected='selected'";
											$num_users++;
										}	
											
										$options = "<option value='0' $selected>"._("All")."</option>\n";
									}
									
									foreach ($users as $k => $v)
									{
										$login    = $v->get_login();
										$selected = ($vuser == $login) ? "selected='selected'" : "";
										$options .= "<option value='".$login."' $selected>$login</option>\n";
										
										$num_users++;
									}
									
									if ($num_users == 0)
									{
										echo "<option value='' style='text-align:center !important;'>- "._("No users found")." -</option>";
									}
									else
									{
										echo "<option value='' style='text-align:center !important;'>- "._("Select one user")." -</option>\n";
										echo $options;
									}
															
									?>
									</select>
								</td>
											
										
								<?php 
								if (!empty($entities)) 
								{ 
									?>
									<td style='text-align:center; border:none; !important'><span style='padding:5px;'><?php echo _("OR")?><span></td>
						
									<td class='nobborder'><span style='margin-right:3px'><?php echo _("Entity:");?></span></td>
									<td class='select_entity noborder'>	
										<select name="entity" id="entity" onchange="switch_user('entity');return false;">
											<option value="" style='text-align:center !important;'>- <?php echo _("Select one entity") ?> -</option>
											<?php
											foreach ($entities as $k => $v) 
											{
												$selected = ($ventity == $k) ? "selected='selected'" : "";
												echo "<option value='$k' $selected>$v</option>";
											}
											?>
										</select>
									</td>
									<?php 
								} 
								?>
							</tr>
						</table>
					</div>
				</div>
			</td>
			
			<td class='nobborder center' style='width:42%;'>
				
				<table id='instruction_table'>
					<tr>
						<th colspan=2>
							<div><strong><?php echo _('Language Definitions Help') ?></strong></div>
						</th>
					</tr>
					
					<tr>
						<td class='nobborder center' width='50%'>
						
							<div class='instruction'><?php echo _("Conditions") ?></div>
							<div class='ins_cont'>							
							<?php
							$i=0;
							foreach($labels_condition as $label => $data) 
							{
								$help_msgs[$i++] = array(addslashes($data['help']), addslashes($data['sample']));
								$class = ($i%2) ? 'odd' : 'even' ;
							?>
								<div data-id='<?php echo $i ?>' class='<?php echo $class ?>' ><?php echo Util::htmlentities($label)?></div>
							<?php
							} 
							?>
							</div>
							
						</td>	
						
						<td class='nobborder center' width='50%'>
						
							<div class='instruction'><?php echo _("Actions") ?></div>
							<div class='ins_cont'>							
							<?php
							foreach($labels_actions as $label => $data) 
							{
								$help_msgs[$i++] = array(addslashes($data['help']), addslashes($data['sample']));
								$class = ($i%2) ? 'odd' : 'even' ;
							?>
								<div data-id='<?php echo $i ?>' class='<?php echo $class ?>' ><?php echo Util::htmlentities($label)?></div>
							<?php
							} 
							?>
							</div>
							
						</td>	
					</tr>	
					
					<tr>	
						<td class='nobborder center' width='50%'>
						
							<div class='instruction'><?php echo _("Operators") ?></div>
							<div class='ins_cont' style='height:135px'>							
							<?php
							foreach($labels_operators as $label => $data) 
							{
								$help_msgs[$i++] = array(addslashes($data['help']), addslashes($data['sample']));
								$class = ($i%2) ? 'odd' : 'even' ;
							?>
								<div data-id='<?php echo $i ?>' class='<?php echo $class ?>' ><?php echo Util::htmlentities($label)?></div>
							<?php
							} 
							?>
							</div>
							
						</td>
						
						<td class='nobborder center' width='50%'>
							
							<div class='instruction'><?php echo _("Variables") ?></div>
							<div class='ins_cont' style='height:135px'>							
							<?php
							foreach($labels_variables as $label => $data) 
							{
								$help_msgs[$i++] = array(addslashes($data['help']), addslashes($data['sample']));
								$class = ($i%2) ? 'odd' : 'even' ;
							?>
								<div data-id='<?php echo $i ?>' class='<?php echo $class ?>' ><?php echo Util::htmlentities($label)?></div>
							<?php
							} 
							?>
							</div>							
						</td>
					</tr>
					
					<tr>
						<td class='nobborder center' colspan='2' height='100px'>
							<div id="help">
								<?php
								$config_nt = array(
									'content' => _("Select a language element from the lists to see its meaning. Click on full references in case of need a more detailed description"),
									'options' => array (
										'type'          => 'nf_info',
										'cancel_button' => false
									),
									'style'   => 'width: 90%; margin: 25px auto 10px auto; padding: 10px 0px; text-align: left; font-style: italic'
								); 
													
								$nt = new Notification('nt_1', $config_nt);
								$nt->show();
								?>
							</div>
						</td>
					</tr>
				</table>
				<div style='float:right;'>
					<a class='view_doc' href='repository_doc.php' ><?php echo _('Full References') ?></a>
				</div>
							
			</td>
		</tr>
	</table>
	
	<br>					
	<div style='text-align:center;padding:10px 0 7px 0;'>
		<input type="submit" name="save" value="<?php echo _('Save Document') ?>"/>
	</div>
	
	</form>
</div>


<script type='text/javascript'>		
	<?php 
	foreach($help_msgs as $key => $text) 
	{
		echo "help[$key] = '".$text[0]."';";
		echo "exam[$key] = '".$text[1]."';";
	} 
	?>
</script>

</body>
</html>
