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

$port     = '';        
$protocol = '';  
$service  = '';  
$descr    = '';  
$ctx      = '';  
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
	<script type="text/javascript" src="../js/jquery.dynatree.js"></script>
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
					url : "newport.php"
				},
				actions: {
					on_submit:{
						id: 'send',
						success: '<?php echo _("SAVE")?>',
						checking: '<?php echo _("Updating")?>'
					}
				}
			};

			// Entities tree
			<?php 
			if (Session::show_entities()) 
			{    			
    			?>
    			$("#tree").dynatree({
    				initAjax: { url: "../tree.php?key=contexts" },
    				clickFolderMode: 2,
    				onActivate: function(dtnode) {
    					var key = dtnode.data.key.replace(/e_/, "");
    					
    					if (key != "") 
    					{
    						$('#ctx').val(key);
    						$('#entity_selected').html("<?php echo _("Context selected") ?>: <b>"+dtnode.data.val+"</b>");
    					}
    				},
    				onDeactivate: function(dtnode) {}
    			});
    			<?php 
			} 
			?>
		
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

    <form method="POST" id='form_p' name='form_p' action="newport.php">
    		
    	<input type="hidden" name="insert" value="insert"/>
    
    	<table align="center" id='table_form'>
    		<tr>
    			<th>
    				<label for='port'><?php echo _('Port number') . required();?></label>
    			</th>
    			<td class='left' class="nobborder">
    				<input type="text" name="port" class='vfield' id='port' value="<?php echo $port?>"/>
    			</td>
    		</tr>
    		
    		<tr>
    			<th>
    				<label for='protocol'><?php echo _('Protocol') . required(); ?></label>
    			</th>
    			<td class="left">
    				<select name="protocol" class='vfield' id='protocol'>
    					<option value="tcp"<?=(($protocol=="tcp") ? 'selected="selected"' : '')?>><?php echo _("TCP"); ?> </option>
    					<option value="udp"<?=(($protocol=="udp") ? 'selected="selected"' : '')?>><?php echo _("UDP"); ?> </option>
    				</select>
    			</td>
    		</tr>
    		
    		<tr>
    			<th>
    				<label for='service'><?php echo _('Service') . required(); ?></label>
    			</th>
    			<td class="left">
    				<input type="text" class='vfield' name="service" id='service' value="<?php echo $service?>"/>
    			</td>
    		</tr>
    		
    		<?php 
    		if (Session::show_entities()) 
    		{ 
                $e_url = Menu::get_menu_url('../acl/entities.php', 'configuration', 'administration', 'users', 'structure');
    			?>
    			<tr>
    				<th> 
    					<label for='ctx'><?php echo _('Context') . required();?></label><br/>
    					<span><a href="<?php echo $e_url?>"><?php echo _("Insert new"); ?>?</a></span>
    				</th>
    				<td class="nobborder">
    					<input type="hidden" name="ctx" id="ctx" value="<?php echo $ctx ?>" class="vfield"/>
                        <table class="transparent">
                            <tr>
                                <td class="nobborder"><div id="tree"></div></td>
                            </tr>
                            <tr>
                                <td class="nobborder">
                                    <div id="entity_selected"><?php echo _('Context selected').": <b>". (empty($ctx) ? _('None') : Session::get_entity_name($conn, $ctx)) ."</b>"; ?>
                                    </div>
                                </td>
                            </tr>
                        </table>
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
    				<input type="button" id="send" value="<?php echo _('SAVE')?>"/>
    			</td>
    		</tr>
    	</table>
    </form>
</div>

</body>
</html>
<?php
$db->close();
?>
