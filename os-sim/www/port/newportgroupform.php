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


$db     = new ossim_db(TRUE);
$conn   = $db->connect();

$update = intval(GET('update'));
$id     = GET('id');
	
	
$descr         = '';
$actives_ports = array();

if ($id != '')
{
	ossim_valid($id, OSS_DIGIT, 'illegal:' . _('Port Group ID'));
	
	if (ossim_error())
	{ 
		die(ossim_error());			
	}	
	
	if ($port_group_list = Port_group::get_list($conn, "WHERE id = $id"))
	{ 
		$port_group = $port_group_list[0];
	}

	$actives_ports = Port_group_reference::in_port_group_reference_for_id($conn, $id);
	
	$pgname = $port_group->get_name();
	$descr  = $port_group->get_descr();
	$ctx    = $port_group->get_ctx();
}

$action = ( GET('id') != '') ? 'modifyportgroup.php' : 'newportgroup.php'; 


if (!Session::show_entities()) 
{
	$ctx = Session::get_default_ctx();
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
	<script type="text/javascript" src="../js/jquery.autocomplete.pack.js"></script>
	<script type="text/javascript" src="../js/combos.js"></script>
	<script type="text/javascript" src="../js/notification.js"></script>
	<script type="text/javascript" src="../js/messages.php"></script>
	<script type="text/javascript" src="../js/ajax_validator.js"></script>
	<script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
	<script type="text/javascript" src="../js/utils.js"></script>
	<script type="text/javascript" src="../js/jquery.dynatree.js"></script>
	<script type="text/javascript" src="../js/token.js"></script>
    <script type="text/javascript" src="../js/jquery.tipTip.js"></script>
    
    <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    <link rel="stylesheet" type="text/css" href="../style/jquery.autocomplete.css">
	<link rel="stylesheet" type="text/css" href="../style/tree.css"/>
	<link rel="stylesheet" type="text/css" href="../style/tipTip.css"/>
    
    
	<script language="javascript">
       
		function change_protocol()
		{
            $('#ports_name').setOptions({
                extraParams: {protocol: $("#ports_protocol").val()}
            });
            $('#ports_name').flushCache()
        }
		
		function portAndProtocol()
		{
			var ports_name     = $("#ports_name").val();
			var ports_protocol = $("#ports_protocol").val();
			
			if(ports_name == '')
            {
				alert('<?php echo Util::js_entities(_("Please: Type here the port"))?>');
				
				return;
			}
			else
			{
				if(!String(ports_name).search(/^\d+$/) != -1)
				{
					var ret = $.ajax({
						url: "ajax_newportform.php",
						global: false,
						type: "GET",
						data: "ports_name=" + ports_name +"&ports_protocol=" + ports_protocol,
						dataType: "text",
						async:false
						}
					).responseText;
				}
				
				switch (ret)
				{
                    case 'XXX':
                        alert('<?php echo Util::js_entities(_("Error: This port is not found"))?>');
                        
                        break;
                    case 'YYY':
                        alert('<?php echo Util::js_entities(_("Error: The initial port must be less than the final"))?>');
                        
                        break;
                    case 'ZZZ':
                        alert('<?php echo Util::js_entities(_("Error: Malformed port is between 0 and 65535"))?>');
                        
                        break;
                    default :
                       var port_range = eval(ret);
                       var port = '';
                       
                       if (port_range.length == 1)
                       {
                           port= port_range[0] + ' - ' + ports_protocol;
                           addto('selected_ports', port, port);
                       }                        
                       else
                       {
                           port = ports_name + ' - ' + ports_protocol;
                           addto('selected_ports', port, port);                  
                       } 
                        break;
				}
			}
			
			return;
		}
		
		$(document).ready(function() {
            
            Token.add_to_forms();
			
			$('textarea').elastic();
			
			$("*").keypress(function(e) {
				if(e.keyCode == 13) 
				{
					return e.keyCode != 13;
				}
			});
            
            // Autocomplete services names
			var url_autocomplete = "ajax_autocomplete.php";
			
			<?php 
			if ($ctx != '') 
			{ 
    			?>
    			if ($("#ports_name").length == 1)	
    			{
                    
    				$("#ports_name").autocomplete(url_autocomplete, {
    					minChars: 0,
    					width: 250,
                        matchContains: "word",
    					max: 100,
    					autoFill: false,
                        extraParams: { protocol: $("#ports_protocol").val(), ctx: '<?php echo $ctx ?>' }
    				});
    			}
    			<?php 
        	} 
        	?>

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
    					
    					if (key != '') 
    					{
    						$('#ctx').val(key);
    						$('#entity_selected').html("<?php echo _("Context selected") ?>: <b>"+dtnode.data.val+"</b>");
    						$('#portsmsg').hide();
    						$('#t_ports').show();
    
    						$("#ports_name").autocomplete(url_autocomplete, {
    							minChars: 0,
    							width: 250,
    		                    matchContains: "word",
    							max: 100,
    							autoFill: false,
    		                    extraParams: { protocol: $("#ports_protocol").val(), ctx: key }
    						});
    					}
    				},
    				onDeactivate: function(dtnode) {}
    			});
    			<?php 
        	} 
        	?>
			
			var config = {   
				validation_type: 'complete', // single|complete
				errors:{
					display_errors: 'all', //  all | summary | field-errors
					display_in: 'av_info'
				},
				form : {
					id  : 'form_pg',
					url : '<?php echo $action?>'
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
				selectall('selected_ports'); 
				ajax_validator.submit_form();
			});
                    			
			//Greybox options			
			
			if (parent.is_lightbox_loaded(window.name))
			{ 			
    			$('#pg_container').css('margin', '10px auto 20px auto');    			
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
		
		textarea 
		{
    		width: 97%; 
    		height: 45px;
		}
		
			
		#selected_ports 
		{ 
            width: 100%; 
            margin-top:5px; 
            height:100px;
		}				
		
		.right 
		{
    		text-align: right; 
    		padding: 3px 0px;    		
		}
		
		#pg_container 
		{		  
		    width: 500px;
		    margin: 40px auto 20px auto;
		    padding-bottom: 10px;
		}
		
		#pg_container #table_form
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
		
		#t_ports
        {           
            width: 98%;    
        }
        
        #t_ports td
        {           
           text-align: left !important;  
        }        
        
        #ports_name
        {
            width: 160px;
        }
        
        #ports_protocol
        {
            width: 70px;
        }
                
        #c_action_ports
        {
           text-align: right;
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
     <input type='button' class="av_b_back" onclick="document.location.href='portgroup.php';return false;"/>
</div>
                                                                        
<div id='av_info'>
	<?php
	if ($update == 1) 
	{
		$config_nt = array(
			'content' => _("Port Group successfully updated"),
			'options' => array (
				'type'          => 'nf_success',
				'cancel_button' => true
			),
			'style'   => 'width: 100%; margin: auto; text-align:center;'
		); 
						
		$nt = new Notification('nt_1', $config_nt);
		$nt->show();
	}
	?>
</div>
    <div id='pg_container'>
        
        <div class='legend'>
            <?php echo _("Values marked with (*) are mandatory");?>
        </div>
        	
    <form method="POST" name='form_pg' id='form_pg' action="<?php echo $action?>">
    	
    	<input type="hidden" name="insert" value="insert"/>
    	
    	<?php 
    	if ($id != '') 
    	{     	
        	?>
        	<input type="hidden" name="id" id="id" class='vfield' value="<?php echo $id?>"/>
        	<?php 
    	} 
    	?>
    
        <table align="center" id='table_form'>
        	<tr>
        		<th>
        			<label for='pgname'><?php echo _('Name') . required(); ?></label>        			
        		</th>
        		<td class="left">
        			<?php 
        			if ( GET('portname') == "" ) 
        			{
        				?>
        				<input type='text' name='pgname' id='pgname' class='vfield' value="<?php echo $pgname?>"/>
        				<?php 
        			} 
        			else 
        			{
        				?>	
        				<input type='hidden' name='pgname' id='pgname' class='vfield' value="<?php echo $pgname?>"/>
        				<div class='bold'><?php echo $pgname?></div>
        				<?php 
        			}  
        			?>
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
    					<span><a href="<?php echo $e_url?>"><?php echo _("Insert new");?>?</a></span>
    				</th>
    				<td class="nobborder">
    					<input type="hidden" name="ctx" id="ctx" value="<?php echo $ctx ?>" class="vfield"/>
    					
    					<table class="transparent">
    						<tr>
        						<td class="nobborder">
        						    <div id="tree"></div>
        						</td>
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
        		<th>
        			<label for='selected_ports'><?php echo _('Ports') . required();?></label>
        		</th>
        		<td class='noborder left'>
        			<?php 
        			if (Session::show_entities() && $ctx == '') 
                    { 
                        ?>
                        <div id="portsmsg"><i><?php echo _('Select first an Entity to select ports') ?></i></div>
                        <?php 
                    } 
                    ?>
                    
        			<table id="t_ports" class="transparent" <?php if (Session::show_entities() && $ctx == '') { ?> style="display:none"<?php } ?>>
        				<tr>
        				    <td class="noborder"><?php echo _("<span class='bold'>Type</span> here the port")?>:</td>
        				</tr>
        				
        				<tr>
                        	<td class="noborder">    					
                        		<input type="text" id="ports_name" name="ports_name" value=""/>
                        		
                        		<select id="ports_protocol" name="ports_protocol" onchange="change_protocol()">
                        			<option value="tcp" selected='selected'>TCP</option>
                        			<option value="udp">UDP</option>
                        		</select>
                        		
                        		<input type="button" id='insert' class="small av_b_secondary" value="<?php echo _("Add")?>" onclick="portAndProtocol();"/>
                        	</td>
                        </tr>
                        
                        <tr>
                            <td class="noborder" style="padding-top:10px">
                            <?php echo _("Selected ports for the group")?>:
                            </td>
                        </tr>
                        
                        <tr>
                        	<td class="noborder">
                        		<select id="selected_ports" name="act_ports[]" class='vfield' multiple="multiple">
                        		<?php
                        			if (is_array($actives_ports))
                        			{
                        			    $actives_ports = Port_group::group_ports($actives_ports);
                        			
                        			    foreach($actives_ports as $v)
                                        {
                                            echo "<option value='$v' selected='selected'>$v</option>";
                        			    }
                        			}
                        		?>
                        		</select>
                        	</td>
                        </tr>
                        <tr>
                        	<td class="noborder">
                        		<div id='c_action_ports'>
                        			<input type="button" value=" [X] " onclick="deletefrom('selected_ports');" class="small av_b_secondary"/> 
                        			<input type="button" value="<?php echo _('Delete all');?>" onclick="selectall('selected_ports');deletefrom('selected_ports');" class="small av_b_secondary"/>
                        		</div>
                        	</td>
                        </tr>                                				    			
        			</table>
        		</td>
            </tr>
      
        	<tr>
        		<th><label for='descr'><?php echo _("Description"); ?></label></th>
        		<td class="left nobborder">
        			<textarea name="descr" id="descr" class='vfield'><?php echo $descr?></textarea>
        		</td>
        	</tr>
      
        	<tr>
        		<td colspan="2" class="nobborder" style="text-align:center;padding-top:10px">
        			<input type="button" id="send" name="send" value="<?php echo _('SAVE')?>"/>
        		</td>
        	</tr>
        </table>
	</div>
    
</form>

</body>
</html>
<?php
$db->close();
?>
