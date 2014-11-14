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


Session::logcheck('configuration-menu', 'PolicyPorts');

$db   = new ossim_db();
$conn = $db->connect();


$id = GET('id');
$id = explode('@@', $id);


$port_number   = $id[0];
$protocol_name = $id[1];

ossim_valid($port_number, 	 OSS_PORT, 		'illegal:' . _('Port Number'));
ossim_valid($protocol_name,  OSS_PROTOCOL,	'illegal:' . _("Protocol Name"));

if (ossim_error()) 
{
	die(ossim_error());
}

if ($port_list = Port::get_list($conn, " AND port_number='$port_number' and protocol_name='$protocol_name'"))
{
	$port_selected = $port_list[0];
}

$port     = $port_selected->get_port_number();        
$protocol = $port_selected->get_protocol_name();  
$service  = $port_selected->get_service();  
$descr    = $port_selected->get_descr();  
$ctx      = $port_selected->get_ctx();
?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title><?php echo _("OSSIM Framework"); ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	
	<script type="text/javascript" src="../js/jquery.min.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.min.js"></script>
	<script type="text/javascript" src="../js/notification.js"></script>
	<script type="text/javascript" src="../js/messages.php"></script>
	<script type="text/javascript" src="../js/ajax_validator.js"></script>
	<script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
	<script type="text/javascript" src="../js/utils.js"></script>
	<script type="text/javascript" src="../js/token.js"></script>
	<script type="text/javascript" src="../js/jquery.tipTip.js"></script>
	
	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<link rel="stylesheet" type="text/css" href="../style/tree.css"/>
	<link rel="stylesheet" type="text/css" href="../style/tipTip.css"/>
    
	<style type='text/css'>
				
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
                
        .f_disabled 
        {
            margin: 0 3px 0 4px;
            padding:3px;
            background-color: #F0F0F0;
            border: 1px solid #CCCCCC;
        }
        
        #port_container 
		{		  
		    width: 450px;
		    margin: 40px auto 20px auto;
		    padding-bottom: 10px;
		}
		
		#port_container #table_form
		{        
            margin: auto;
            width: 100%;
            background: none;
            border: none;
		}    	
            
        #table_form th 
        {
            width: 150px;
        }
        
        .legend 
		{ 
    		font-style: italic;
    		text-align: center; 
    		padding: 0px 0px 5px 0px;
    		margin: auto;
    		width: 400px;
		}
        
        #av_info 
        {
            width: 530px; 
            margin: 10px auto;
        }
		
	</style>
	
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
					id  : 'form_p',
					url : "modifyport.php"
				},
				actions: {
					on_submit:{
						id: 'send',
						success: '<?php echo _("SAVE")?>',
						checking: '<?php echo _("Updating")?>'
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
    			$('#port_container').css('margin', '10px auto 20px auto');
			}
			else
			{
    			$('.c_back_button').show();
    		}			
		});
	</script>	
</head>

<body>

<div class='c_back_button'>
    <input type='button' class="av_b_back" onclick="document.location.href='port.php';return false;"/>
</div>
                                                                                
<div id='av_info'></div>

<div id='port_container'>
    
    <div class='legend'>
         <?php echo _("Values marked with (*) are mandatory");?>
    </div>

    <form method="POST" id='form_p' name='form_p' action="modifyport.php">
    	
    	<table align="center" id='table_form'>
    		<tr>
    			<th class='thgray'>
    				<label for='port'><?php echo _('Port number') . required();?></label>
    			</th>
    			<td class="nobborder">
    				<input type="hidden" name="port" class='vfield' id='port' value="<?php echo $port?>">
    				<div class='f_disabled'><?php echo $port?></div>
    			</td>
    		</tr>
    		
    		<tr>
    			<th class='thgray'>
    				<label for='protocol'><?php echo _('Protocol') . required(); ?></label>
    			</th>
    			<td class="nobborder">
    				<input type="hidden" name="protocol" class='vfield' id='protocol' value="<?php echo $protocol?>">
    				<div class='f_disabled'><?php echo strtoupper($protocol) ?></div>
    			</td>
    		</tr>
    		
    		<tr>
    			<th>
    				<label for='service'><?php echo _('Service') . required(); ?></label>
    			</th>
    			<td class="left">
    				<input type="text" class='vfield' name="service" id='service' value="<?php echo $service?>">
    			</td>
    		</tr>
    				
    		<?php 
    		if (Session::show_entities()) 
    		{ 
    			$e_url = Menu::get_menu_url('../acl/entities.php', 'environment', 'assets', 'structure');
    			?>
    			<tr>
    				<th class='thgray'> 
    					<label for='ctx'><?php echo _('Context') . required();?></label><br/>
    					<span><a href="<?php echo $e_url?>"><?php echo _('Insert new');?>?</a></span>
    				</th>
    				<td class="nobborder">
    					<input type="hidden" name="ctx" id="ctx" value="<?php echo $ctx ?>" class="vfield"/>
    					<div id="entity_selected" class='f_disabled'><?php echo _('Context selected').": <b>". (empty($ctx) ? _('None') : Session::get_entity_name($conn, $ctx)) ."</b>"; ?></div>
    				</td>
    			</tr>
    			<?php 
    		} 
    		else 
    		{ 
    			?>
    			<input type="hidden" name="ctx" id="ctx" value="<?php echo Session::get_default_ctx() ?>" class="vfield"/>
    			<?php 
    		} 
    		?>
    		
    		<tr>
    			<th><label for='descr'><?php echo _('Description');?></label></th>
    			<td class="left noborder">
    				<textarea name="descr" class='vfield' id="descr"><?php echo $descr?></textarea>
    			</td>
    		</tr>
    		
    		<tr>
    			<td colspan="2" class="nobborder" style="text-align:center;padding-top:10px">    				
    				<input type="button" id="send" name="send" value="<?php echo _('SAVE')?>"/>   				
    			</td>
    		</tr>
    	</table>
    </form>
</div>

</body>
</html>