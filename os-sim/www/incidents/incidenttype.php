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

if (!Session::menu_perms("analysis-menu", "IncidentsTypes") && !Session::am_i_admin())
{ 
	Session::unallowed_section(null);
}

Session::logcheck("analysis-menu", "IncidentsTypes");
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<script type="text/javascript" src="../js/jquery.min.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.min.js"></script>
	<script type="text/javascript" src="../js/notification.js"></script>
	<script type="text/javascript" src="../js/ajax_validator.js"></script>
	<script type="text/javascript" src="../js/utils.js"></script>
	<script type="text/javascript" src="../js/messages.php"></script>
	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
		
	<style type='text/css'>
		#t_types 
		{ 
			min-width:500px;
			margin: 20px auto;
			text-align: center;
			border: none;
		}
				
		#t_types .odd td, #t_types .even td{
			padding: 3px;
		}
		
		#t_types 
		{           
            margin: 50px auto 20px auto;            
		}
		
		#av_info
		{
			width: 80%;
			margin: 10px auto;
		}
		
		.edit_type 
		{
    		text-decoration: none !important;
		}
		
	</style>
	
	<script type='text/javascript'>
		
		function delete_incident_type(id)
		{						
			$.ajax({
				type: "POST",
				url: "deleteincidenttype.php",
				dataType: "json",
				data: 'inctype_id='+id,
				beforeSend: function(xhr) {
					$('#loading_'+id).append('<img src="../pixmaps/loading.gif" align="top" style="margin-left: 3px;" class="it_loading" width="13" alt="<?php echo _("Loading")?>">');
				},
				error: function(data){
					$('.it_loading').remove();
					
					var config_nt = { content: av_messages['unknown_error'], 
						options: {
							type:'nf_error',
							cancel_button: false
						},
						style: 'width: 80%; text-align:center; margin: 10px auto;'
					};
				
					nt = new Notification('nt_1', config_nt);
					
					$('#av_info').html(nt.show());
				},
				
				success: function(data){
															
					$('.it_loading').remove();
					
					if (typeof(data) != 'undefined' && data != null)
					{
						if (data.status == 'error')
						{
							var nf_type = 'nf_error';
							var content = "<div style='padding-left: 10px;'>"+av_messages['error_header']+"<div><div style='padding-left: 20px;'>"+data.data+"</div>";
						}
						else
						{
							var nf_type = 'nf_success';
							var content = data.data;
							$('#tr_'+id).remove();
						}
						
						var config_nt = { 
							content: content, 
							options: {
								type: nf_type,
								cancel_button: false
							},
							style: 'display:none; width: 100%; margin: 10px auto;'
						};
						
						nt = new Notification('nt_1',config_nt);
											  
						$('#av_info').html(nt.show());
						nt.fade_in(1000);
						
						setTimeout('nt.fade_out(1000)', 10000);
					}
				}
			});
			
		}
				
		$(document).ready(function() 
		{									
			$('.del_type').click(function() { 
				var id = $(this).attr('id');
				delete_incident_type(id);
			});
		});
	
	</script>
	
</head>
<body>

<div class='c_back_button' style='display:block;'>
    <input type='button' class="av_b_back" onclick="document.location.href='../incidents/index.php';return false;"/>
</div>

<?php 

$db   = new ossim_db();
$conn = $db->connect();

$inctype_list = Incident_type::get_list($conn, "");	
?>

<div id='av_info'>
	<?php
	if (!empty($_GET['msg']))
	{
		$var_tmp = "";
		switch($_GET['msg'])
		{
			case 1:
				$var_tmp = _("New Custom Ticket successfully created");
			break;
			
			case 2:
				$var_tmp =_("New Ticket Type successfully created");
			break;
			
			case 3:
				$var_tmp =_("Ticket Type successfully updated");
			break;
		}
		
		if ($var_tmp != "") 
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

<?php

if ($inctype_list) 
{ 
	?>
	<!-- main table -->
	<table class='table_list' id='t_types'>
		<tr>
			<th><?php echo gettext("Ticket type");?></th>
			<th><?php echo gettext("Description");?></th>
			<th><?php echo gettext("Custom");?></th>
			<th><?php echo gettext("Actions");?></th>
		</tr>    

		<?php
		foreach($inctype_list as $inctype)
		{
			$custom        = (preg_match("/custom/",$inctype->get_keywords())) ? "tick.png" : "cross.png";
			$custom_fields = Incident_custom::get_custom_types($conn,$inctype->get_id());
			$alt           = (preg_match("/custom/",$inctype->get_keywords())) ? implode(",",$custom_fields) : "";
			
			$class         = ($i % 2 == 0) ? 'class="odd"' : 'class="even"';
			$id            =  urlencode($inctype->get_id());
			$tr_id         = 'tr_'.$id;
			?>
			
			<tr <?php echo $class?> id='<?php echo $tr_id;?>'>
				
				<td id='loading_<?php echo $id;?>'><?php echo Util::htmlentities($inctype->get_id());?></td>
				
				<td><?php echo ("" == $inctype->get_descr()) ? " -- " :  $inctype->get_descr(); ?></td>
				<?php
				if (!("Generic" == $id) && !("Nessus+Vulnerability" == $id))
				{
					?>
					<td align='center'><img src='../pixmaps/<?php echo $custom?>' title='<?php echo $alt?>' border='0'></td>
					<td>
						<a href="modifyincidenttypeform.php?id=<?php echo $id?>" class="edit_type"> 
							<img src='../vulnmeter/images/pencil.png' border='0' title="<?php echo _("Modify type")?>"/>
						</a>
						<a href="#" class='del_type' id='<?php echo $id?>'>
							<img src='../vulnmeter/images/delete.gif' border='0' title="<?php echo _("Delete type")?>"/>
						</a>
					</td>
					<?php
				} 
				else
				{
					?>
					<td> -- </td>
					<td> -- </td>
					<?php
				}
				?>
			</tr>
			<?php
			$i++;
		}
		?>
	</table>
	
	<div class='center' style='padding: 10px 0px;'>
        <input type='button' onclick="document.location.href='newincidenttypeform.php'" value='<?php echo _("New Custom Ticket Type")?>'/>
    </div>
	<?php		
} 
else 
{	
	$config_nt = array(
		'content' => _("Error to connect to the database.  Please, try again."),
		'options' => array (
			'type'          => 'nf_error',
			'cancel_button' => false
		),
		'style'   => 'width: 80%; margin: 20px auto; text-align: left;'
	); 
					
	$nt = new Notification('nt_1', $config_nt);
	$nt->show();
}

$db->close();
?>
</body>
</html>
