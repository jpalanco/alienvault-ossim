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

Session::logcheck('configuration-menu', 'PolicyServers');


$db    = new ossim_db();
$conn  = $db->connect();

$ri_id  = GET('id');
$action = (GET('id') != '') ? 'modify_ri.php' : 'new_ri.php';

$ip      = '';
$name    = '';
$status  = 1;
	
if ($ri_id != '')
{
	ossim_valid($ri_id, OSS_DIGIT, 'illegal:' . _('Remote Interface ID'));

	if (ossim_error())
	{ 
		echo ossim_error();
		exit();
	}
        
    $r_interface = Remote_interface::get_object($conn, $ri_id);
      	
	if (is_object($r_interface) && !empty($r_interface))
	{
		$ri_id        = $r_interface->get_id();
		$ip           = $r_interface->get_ip();
		$name         = $r_interface->get_name();
		$status       = $r_interface->get_status();
	}
	else
	{
	    $error_msg = _('Error! Remote Interface not found');
    	echo ossim_error($error_msg);
		exit();    	
	}
}
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
	<script type="text/javascript" src="../js/messages.php"></script>
	<script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
	<script type="text/javascript" src="../js/utils.js"></script>
	<script type="text/javascript" src="../js/token.js"></script>
	<script type="text/javascript" src="../js/jquery.tipTip.js"></script>
	
	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<link rel="stylesheet" type="text/css" href="../style/tipTip.css"/>
  
	<script type="text/javascript">
		$(document).ready(function(){
			
			Token.add_to_forms();
			
			$('textarea').elastic();
			
			var config = {   
				validation_type: 'complete', // single|complete
				errors:{
					display_errors: 'all', //  all | summary | field-errors
					display_in: 'av_info'
				},
				form : {
					id  : 'form_ri',
					url : '<?php echo $action?>'
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
				ajax_validator.submit_form();
			});
			
			//Greybox options			
			
			if (parent.is_lightbox_loaded(window.name))
			{ 			
    			$('#ri_container').css({'margin':'10px auto 20px auto', 'width':'400px'});
    			$('#table_form th').css("width", "150px");
    			$('#av_info').css({"width": "480px", "margin" : "10px auto"});    			
			}
			else
			{
    			$('.c_back_button').show();
    		}			
		});
	</script>
  
	<style type='text/css'>			
				
		input[type='text'], input[type='hidden'], select 
		{
            width: 98%; 
            height: 18px;
        }
        
        input[type='file'] 
        {
            width: 90%; 
            border: solid 1px #CCCCCC;
        }
		
		textarea 
		{
            width: 97%; 
            height: 45px;
        }
        
        .val_error 
		{ 
		    width: 270px;
		}
		
		.text_ri
		{
			cursor: default !important;
			font-style: italic !important;
			opacity: 0.5 !important;
		}
		
        .legend 
		{ 
    		font-style: italic;
    		text-align: center; 
    		padding: 0px 0px 5px 0px;
    		margin: auto;
    		width: 400px;
		}
		
        #ri_container 
		{		  
		    width: 600px;
		    padding-bottom: 10px;
		    margin: 40px auto 20px auto;
		}
		
		#ri_container #table_form 
        {
            margin: auto;
            width: 100%;
        }
        
        #table_form th 
        {
            width: 150px;            
        }				
		        
        #av_info 
        {
            width: 580px; 
            margin: 10px auto;
        }

	</style>
  
</head>
<body>

<div class='c_back_button'>
     <input type='button' class="av_b_back" onclick="document.location.href='index.php';return false;"/>
</div>
                                                                                
<div id='av_info'></div>

<div id='ri_container'>
	<div class='legend'>
	     <?php echo _('Values marked with (*) are mandatory');?>
	</div>	

    <form name='form_ri' id='form_ri' method="POST" action="<?php echo $action?>" enctype="multipart/form-data">
    
    	<input type="hidden" name="insert" value="insert"/>
    	
    	<table align="center" id='table_form'>
    		  
    		<?php 
    		if (GET('id') != '') 
    		{
    		?>
    			<tr>
    				<th><label for='ri_id'><?php echo _("ID");?></label></th>
    				<td class="left">
    					<input type="text" class='text_ri' name="text_ri" id="text_ri" value="<?php echo $ri_id?>" readonly='readonly' disabled='disabled'/>
    					<input type="hidden" class='vfield' name="ri_id" id="ri_id" value="<?php echo $ri_id?>"/>
    				</td>
    			</tr>
    		<?php 
    		}
    		?>
      
    		<tr>
    			<th>
    				<label for='ip'><?php echo _('IP') . required();?></label>
    			</th>
    			<td class="left">
    				<input type="text" class='vfield' name="ip" id="ip" value="<?php echo $ip?>"/>
    			</td>
    		</tr>
    		
    		<tr>
    			<th>
    				<label for='ri_name'><?php echo _('Name') . required();?></label>
    			</th>
    			<td class="left">
    				<input type="text" class='vfield' name="ri_name" id="ri_name" value="<?php echo $name;?>"/>
    			</td>
    		</tr>
    		
    		<tr>
    			<th>
    				<label for='status'><?php echo _('Status') . required();?></label>
    			</th>
    			<td class="left">
    				<?php $checked = ($status == 1) ? " checked='checked' " : ""; ?>
    				<input type="checkbox" value="1" <?php echo($checked); ?> name="status"/>
    			</td>
    		</tr>
    	  
    		<tr>
    			<td colspan="2" align="center" style="padding: 10px;">
    				<input type="button" id='send' name='send' value="<?php echo _('Save')?>"/>			
    			</td>
    		</tr>				
    	</table>
    </form>
</div>

</body>
</html>
