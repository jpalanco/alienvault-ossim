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


/**
* Function list:
* - get_policy_entities
* - message_ok()
* - email_form()
* - exec_form()
* - ticket_form()
* - submit()
*/

require_once 'av_init.php';


Session::logcheck('configuration-menu', 'PolicyActions');


function get_policy_entities($conn)
{
	$entities = $entities_all = array();
	
	$entities_all = Acl::get_entities_to_assign($conn);	
	
	foreach ($entities_all as $k => $v) 
	{	
		if (Acl::is_logical_entity($conn, $k))
		{
			$parent_id   = Acl::get_logical_ctx_id($conn, $k);
			$parent_id   = $parent_id[0]; // first
			$parent_name = Acl::get_entity_name($conn,$parent_id);
			
			$entities[$parent_id] = $parent_name;
				
		} 
		else
		{
			$entities[$k] = $v;
		}
		
	}
	
	asort($entities);
	
	return $entities;
}

//Version
$pro = Session::is_pro();


function submit() 
{
?>
    <tr>
        <td align="center" style="border-bottom: medium none; padding: 10px;" colspan="2">           
            <input type="button" id='send' value="<?php echo _('Save');?>"/> 
        </td>
    </tr>
<?php
}


function email_form($action) 
{
?>
    <tr class="temail"><td colspan="2" class="nobborder">&nbsp;</td></tr>
    <tr class="temail">
        <th><label for='email_from'><?php echo _('From:') . required(); ?></label></th> 
        <td class="left nobborder">
            <input onfocus='set_focus(this);' value="<?php echo ((is_null($action)) ? "" : $action->get_from()); ?>" class="vfield" name="email_from" id="email_from" type="text" size="60"/>
        </td>
    <tr class="temail">
    </tr>
        <th><label for='email_to'><?php echo _('To:') . required(); ?></label></th>
        <td class="left nobborder">
            <input onfocus='set_focus(this);' <?php echo ((is_null($action)) ? "style=\"color: #C0C0C0\"" : ""); ?> value="<?php echo ((is_null($action)) ? _("email;email;email"):$action->get_to());?>" class="vfield" name="email_to" id="email_to" type="text" size="60"/>
        </td>
    </tr>
    <tr class="temail">
        <th><label for='email_subject'><?php echo _('Subject:') . required(); ?></label></th> 
        <td class="left nobborder">
            <input onfocus='set_focus(this);' value="<?php echo ((is_null($action)) ? "" : $action->get_subject()); ?>" name="email_subject" id="email_subject" class="vfield" type="text" size="60" />
        </td>
    </tr>
    <tr class="temail">
        <th class="lth"><label for='email_message'><?php echo _('Message:') . required(); ?></label></th>
        <td class="left nobborder">
        <textarea onfocus='set_focus(this);' name="email_message" id="email_message" class="vfield"><?php echo ((is_null($action)) ? "" : $action->get_message()); ?></textarea>
    </tr>
    <tr class="temail">
        <th class="lth"><label for='email_message_suffix'><?php echo _('Include all available event fields at the end of the message') ?>:</label></th>
        <td class="left nobborder">
        <input type="checkbox" name="email_message_suffix" id="email_message_suffix" <?php echo !is_null($action) && $action->get_message_suffix() ? 'checked="checked"' : '' ?>/>
    </tr>
<?php
}

function exec_form($action) 
{
?>
    <tr class="texec"><td colspan="2" class="nobborder">&nbsp;</td></tr>
    <tr class="texec">
        <th><label for="exec_command"><?php echo _('Command:') . required(); ?></label></th>
        <td class="nobborder left">
            <input onfocus='set_focus(this);' value="<?php echo ((is_null($action)) ? "" : $action->get_command()); ?>" class="vfield" name="exec_command" id="exec_command" type="text" size="60" />
        </td>
    </tr>
<?php
}


function ticket_form($action) 
{
	GLOBAL $conn;
	$users = Session::get_users_to_assign($conn);
	
	if(Session::is_pro())
	{
		$entities = Acl::get_entities_to_assign($conn);
    }
    
    ?>
	<tr class="tticket">
	   <td colspan="2" class="nobborder">&nbsp;</td>
	</tr>
	<tr class="tticket">
		<th><label for="in_charge"><?php echo _('In Charge:') . required(); ?></label></th>
		<td class="nobborder left">
			<table cellspacing="0" cellpadding="0" class="transparent">
                <tr>
                    <td class="nobborder">
                        <label for="transferred_user"><?php echo _('User:');?></label>
                    </td>
                    <td class="nobborder left">
                        <select onfocus='set_focus(this);' name="transferred_user" id="transferred_user" class="vfield" onchange="switch_user('user');return false;">
                        <?php
                        
                            $num_users = 0;
                                                                                    
                            foreach ($users as $k => $v)
                            {
                                $login    = $v->get_login();
                                
                                $options .= "<option value='$login'".($action==$login ? " selected": "").">$login</option>\n";
                                
                                $num_users++;
                            }
                            
                            if ($num_users == 0)
                            {
                                echo "<option value='' style='text-align:center !important;'>- "._("No users found")."- </option>";
                            }
                            else
                            {
                                echo "<option value='' style='text-align:center !important;' selected='selected'>- "._("Select one user")." -</option>\n";
                                
                                echo $options;
                            }
                            
                        ?>
                        </select>
                    </td>
                
                <?php 
                if (!empty($entities)) 
                { 
                ?>
                    <td class="nobborder" nowrap='nowrap'>
                        <label for="transferred_entity" style='margin-right: 3px;'><?php echo _('OR').' '._('Entity:');?></label>
                    </td>
                    <td class="nobborder">
                        <select onfocus='set_focus(this);' name="transferred_entity" id="transferred_entity" class="vfield" onchange="switch_user('entity');return false;">
                        <?php
                        			
                            if (count($entities) == 0)
                            {
                                echo "<option value='' style='text-align:center !important;'>- "._('No entities found')." -</option>";
                            }
                            else
                            {
                                echo "<option value='' style='text-align:center !important;'>- "._('Select one entity')." -</option>\n";
                            }
                            
                            foreach ($entities as $k => $v) 
                            {    
                                echo "<option value='$k'".($action==$k ? " selected" : "").">$v</option>";
                            }
                            
                        ?>
                        </select>
                    </td>
                <?php 
                } 
                ?>
                </tr>
			</table>
		</td>
	</tr>
    <?php
}


if (isset($_SESSION['_actions']))
{
    $action_id      = $_SESSION['_actions']['action_id'];
    $action_type    = $_SESSION['_actions']['action_type'];
    $descr          = $_SESSION['_actions']['descr'];
	$name           = $_SESSION['_actions']['name'];
    $cond           = $_SESSION['_actions']['cond'];
    $on_risk        = $_SESSION['_actions']['on_risk'];
    $email_from     = $_SESSION['_actions']['email_from'];
    $email_to       = $_SESSION['_actions']['email_to'];
    $email_subject  = $_SESSION['_actions']['email_subject'];
    $email_message  = $_SESSION['_actions']['email_message'];
    $exec_command   = $_SESSION['_actions']['exec_command'];
    
    unset($_SESSION['_actions']);
}
else 
{
    $action_id = REQUEST('id');
    
    ossim_valid($action_id, OSS_HEX, OSS_NULLABLE, 'illegal:' . _('Action ID'));

    if (ossim_error()) 
    {
        die(ossim_error());
    }

    list($db, $conn) = Ossim_db::get_conn_db();


    $action_list     = Action::get_list($conn, " AND id = UNHEX('$action_id')");
    
    if (is_array($action_list)) 
    {
        $action = $action_list[0];
    }

    if (!is_null($action)) 
    {
        $action_type = $action->get_action_type();
		$ctx         = $action->get_ctx();
        $cond        = Util::htmlentities($action->get_cond());
        $on_risk     = $action->is_on_risk();
		$name        = $action->get_name();
		
        if (REQUEST('descr')) 
        {
            $description = $descr;
        }
        else 
        {
            $description = $action->get_descr();
		}
    }
    else 
    {
        $action_type = "";
        $cond        = "True";
        $on_risk     = 0;
        $description = "";
		$name        = "";
    }
}

$update  = intval(GET('update'));

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title><?php echo _('OSSIM Framework')?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    
    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/notification.js"></script>
	<script type="text/javascript" src="../js/ajax_validator.js"></script>
	<script type="text/javascript" src="../js/messages.php"></script>
    <script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
    <script type="text/javascript" src="../js/utils.js"></script>
    <script type="text/javascript" src="../js/jquery.tipTip.js"></script>
    
    <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<link rel="stylesheet" type="text/css" href="../style/tipTip.css"/>
    
    
    <script type='text/javascript'>
       
        <?php $defaultcond = Util::htmlentities("RISK>=1");?>
		
		var item_focused = '';
				    
        function changecond(type) 
        {
            $('#condition').hide();
            if (type==1) 
			{
                $('#cond').val("True");
                $('#on_risk').attr('checked', false);
            } 
			else if (type==2)
			{
                $('#cond').val("<?=$defaultcond?>");
                $('#on_risk').attr('checked', false);
            }
			else if (type==3)
			{
                $('#condition').show();
				$('#only').attr('checked',false);
				$('#cond').val("True");
				$('#on_risk').attr('checked', false);
				}
        }
		
		function set_focus(id)
		{
			item_focused = $(id).attr('id');		
		}
		
        
        function changeType()
        {
            if($('#action_type').val() == '2') 
            {
                $('.texec').show('');
                $('.tticket').hide();
                $('.temail').hide('');
            }
            else if ($('#action_type').val() == '1') 
            {
                $('.temail').show('');
                $('.tticket').hide();
                $('.texec').hide('');
            }
            else if ($('#action_type').val() == '3') 
            {
                $('.temail').hide('');
                $('.tticket').show();
                $('.texec').hide('');
            }        
            else 
            {
                $('.temail').hide('');
                $('.tticket').hide();
                $('.texec').hide('');
            }
											
			config.form.url = 'modifyactions.php?action_type='+$('#action_type').val();
			
			ajax_validator.set_config(config);
        }
        
        function switch_user(select)
        {
            if( select=='entity' && $('#transferred_entity').val() != '')
            {
                $('#transferred_user').val('');
            }
            else if (select=='user' && $('#transferred_user').val() != '')
            {
                $('#transferred_entity').val('');
            }
        }  
        
        
        $(document).ready(function() 
        {
            config = {   
				validation_type: 'complete', // single|complete
				errors:{
					display_errors: 'all', //  all | summary | field-errors
					display_in: 'av_info'
				},
				form : {
					id  : 'new_action',
					url : 'modifyactions.php?action_type='+$('#action_type').val()
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
				
			$('li span').click(function() {
				
				if(item_focused == 'descr')
				{
					var val = $('#descr').val() + ' ' + $(this).text();
					
					$('#descr').val(val);
					$('#descr').trigger('blur');
				} 
				else if(item_focused == 'cond')
				{
					$('#cond').val(($('#cond').val()) + ' ' + ($(this).text()));
				} 
				else if (item_focused == 'email_message') 
				{
					$('#email_message').val(($('#email_message').val()) + ' ' + ($(this).text()));
				}
				
				return false;				
            });
            
            $('textarea').elastic();
            			
            <?php
            if($action_type == '2') 
            {                
                ?>
                $('.temail').hide();
                $('.tticket').hide();
                $('.texec').show();
                <?php
            }
            else if ($action_type == '1') 
            {
                ?>
                $('.texec').hide();
                $('.tticket').hide();
                $('.temail').show();
                <?php
            }
            else if ($action_type == '3') 
            {
                ?>
                $('.texec').hide();
                $('.tticket').show();
                $('.temail').hide();
                <?php
            }
            else 
            {
                ?>
                $('.temail').hide();
                $('.tticket').hide();
                $('.texec').hide();
                <?php
            }?>
            
			$('#email_to').focus(function() {
				if($('#email_to').val() == '<?php echo _('email;email;email')?>' )
				{
					$('#email_to').val('');
					$('#email_to').css("color", "#222222");
				}
			});
			
					
		    $('#send').click(function() { 
				ajax_validator.submit_form();
			});
            
        });
        
    </script>
  
    <style type='text/css'>
        
        #action_container
        {
            margin: 15px auto;    
        }
        
        a 
        {
            cursor:pointer;            
        }
        
        input[type='text'], input[type='hidden'], input[type='password'], select, textarea 
		{
		    width: 95% !important; 
		    height: 18px;    		
		}
        
        textarea 
        { 
            height: 95px; 
        }
		
		select 
		{ 
		    height: 20px;    		
		}
        
        #table_form 
        {
            width: 750px;
        }
        
		#table_form th 
		{
            width: 250px !important;
            padding: 5px 0px 5px 0px;
        }
        
		#table_form th.lth 
		{
            background-position:top;
        }
        
        #table_form .s_label 
        {
            font-size: 13px;              		
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
		        
        .bold 
        {
            font-weight: bold;            
        }
        
		div.bold 
		{
		    line-height: 18px;    		
		}
        
        
		li span
		{ 
    		color:#17457C; 
    		cursor:pointer;    		
		}
		
		#av_info 
		{
    		width: 830px; 
    		margin: 10px auto;
		}
		
		.only 
		{
			margin-left:20px;
		}
		
    </style>
</head>
<body>

<div id='action_container'>

    <form id="new_action" name="new_action" method="POST" action="modifyactions.php">	
	<input type="hidden" name="id" value="<?php echo ((is_null($action)) ? '' : $action->get_id()); ?>" />
	<input type="hidden" class='vfield' id='action' name="action" value="<?php echo (($action_id=="") ? 'new' : 'edit'); ?>" />
	
	<div class="legend">
        <?php echo _('Values marked with (*) are mandatory');?>
    </div>	
	
	<table align="center" id="table_form">
		<tr>
			<td colspan="2" style="text-align: left" class="nobborder">
				<?php echo _("You can use the following keywords within any field which will be substituted by its matching value upon action execution") . ":";?>    
				<table width="90%" align="center" style="border-width: 0px">
					<tr>
						<td style="text-align: left" valign="top" class="nobborder">
							<ul> 
								<li><span>DATE</span></li>
								<li><span>PLUGIN_ID</span></li>
								<li><span>PLUGIN_SID</span></li>
								<li><span>RISK</span></li>
								<li><span>PRIORITY</span></li>
								<li><span>RELIABILITY</span></li>
								<li><span>SRC_IP_HOSTNAME</span></li>
								<li><span>DST_IP_HOSTNAME</span></li>
								<li><span>SRC_IP</span></li>
								<li><span>DST_IP</span></li>
								<li><span>SRC_PORT</span></li>
								<li><span>DST_PORT</span></li>
								<li><span>PROTOCOL</span></li>
								<li><span>SENSOR</span></li>
								<li><span>BACKLOG_ID</span></li>
							</ul>
						</td>
						
						<td style="text-align: left" valign="top" class="nobborder">
							<ul> 
								<li><span>EVENT_ID</span></li>
								<li><span>PLUGIN_NAME</span></li>
								<li><span>SID_NAME</span></li>
								<li><span>USERNAME</span></li>
								<li><span>PASSWORD</span></li>
								<li><span>FILENAME</span></li>
								<li><span>USERDATA1</span></li>
								<li><span>USERDATA2</span></li>
								<li><span>USERDATA3</span></li>
								<li><span>USERDATA4</span></li>
								<li><span>USERDATA5</span></li>
								<li><span>USERDATA6</span></li>
								<li><span>USERDATA7</span></li>
								<li><span>USERDATA8</span></li>
								<li><span>USERDATA9</span></li>
							</ul>
						</td>
					</tr>
				</table>
			</td>
		</tr>

		
		<div id='av_info'>
			<?php
			if ($update == 1) 
			{
				$config_nt = array(
					'content' => _('Action saved successfully'),
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
		
		<tr>
			<th><label for='action_name'><?php echo _('Name') . required(); ?></label></th>
			<td class="left nobborder">
				<input type="hidden" name="old_name" id="old_name" class="vfield" value="<?php echo $name ?>"/>
				<input type="text" name="action_name" id="action_name" class="vfield" onfocus='set_focus(this);' value="<?php echo $name ?>"/>
			</td>
		</tr>
		<?php 
		if($pro) 
		{ 
			?>
			<tr>
				<th><label for='ctx'><?php echo _('Context') . required(); ?></label></th>
				<td class="left nobborder">
					<select name="ctx" id="ctx" class='vfield' onfocus='set_focus(this);'>
						<?php		
						$contexts = get_policy_entities($conn);

						if (count($contexts) == 0)
						{
							echo "<option value='' style='text-align:center !important;'>- "._("No contexts found")." -</option>";
						}
						else
						{
							echo "<option value='' style='text-align:center !important;'>- "._("Select one context")." -</option>\n";
						}
						
						foreach ($contexts as $k => $v)
						{ 
							echo "<option value='$k'".(($ctx==$k) ? " selected": "").">$v</option>";
						}
						?>
					</select>
				</td>
			</tr>
			<?php 
		}
		
		?>
		<tr>
			<th><label for='descr'><?php echo _('Description') . required(); ?></label></th>
			<td class="left nobborder">
				<input type="hidden" name="old_descr" id="old_descr" class="vfield" value="<?php echo $description ?>"/>
				<textarea name="descr" id="descr" class="vfield" onfocus='set_focus(this);'><?php echo $description ?></textarea>
			</td>
		</tr>
		
		<tr>
			<th><label for='action_type'><?php echo _('Type') . required() ?></label></th> 
			<td class="left nobborder">
				<select name="action_type" class="vfield" id="action_type" onChange="changeType()" onfocus='set_focus(this);'>
					<option value="" selected='selected'> -- <?php echo _("Select an action type"); ?> -- </option>
					<?php
					
					$action_type_list = Action_type::get_list($conn);
					
					if (is_array($action_type_list)) 
					{
						foreach($action_type_list as $action_type_aux) 
						{
							$selected = ( $action_type == $action_type_aux->get_type() ) ? "selected='selected'" : "";
							echo "<option value='".$action_type_aux->get_type()."' $selected>"._($action_type_aux->get_descr())."</option>";
						}
					}
					?>
				</select>
			</td>
		</tr>

		<tr>
            <th>
                <span class="s_label" id='only'><?php echo _('Condition')?></span>
            </th>
			<td style="text-align:center;padding-left:14px;" class="nobborder">
				<input type="radio" name="only" class="only" id="only_1" onfocus='set_focus(this);' onchange="changecond(1)" <?php echo ($cond != $defaultcond) ? "checked" : ""?>/>
				<label for="only_1"><?php echo _('Any')?></label>
				
				<input type="radio" name="only" class="only" id="only_2" onfocus='set_focus(this);' onchange="changecond(2)" <?php echo ($cond == $defaultcond && !$on_risk) ? "checked" : ""?>/>
				<label for="only_1"><?php echo _('Only if it is an alarm')?></label>
				
				<input type="radio" name="only" class="only" id="only_3" onfocus='set_focus(this);' onchange="changecond(3)" <?php echo (!in_array($cond,array($defaultcond, '', 'True')) || $on_risk) ? "checked" : ""?>/>
				<label for="only_3"><?php echo _('Define logical condition')?></label>
			</td>
		</tr>
		

		<tr id="condition" <?php echo (in_array($cond,array($defaultcond, '', 'True')) && !$on_risk ) ? "style='display:none'" : ""?> >
			<td class="nobborder">&nbsp;</td>
			<td style="text-align: center" class="noborder">
				<table class="noborder">
					<tr>
						<td class="noborder left" width="115">
							<?php echo _('Python boolean expression') ?>:&nbsp;
						</td>
						
						<td class="left noborder">
							<input onfocus='set_focus(this);' type="text" id="cond" name="cond" size="50" maxlength="255" class="vfield" value="<?php echo $cond ?>"> <span class="gray"><?php echo "(*) "._("Up to 255 characters") ?></span>
						</td>
					</tr>
					
					<tr>
						<td class="noborder left">
							<label for="on_risk"><?php echo _('Only on risk increase') ?>:&nbsp;</label>
						</td>
						<td class="noborder" style="text-align: left">
							<input onfocus='set_focus(this);' type="checkbox" id="on_risk" name="on_risk" <?php if ($on_risk) echo "checked='checked'" ?>>
						</td>
					</tr>
				</table>
			</td>  
		</tr>
	  
		<?php

		if(!is_null($action)) 
		{
			if ($action_type == '1')
			{
				email_form($action->get_action($conn));
				exec_form(NULL);
				ticket_form(NULL);
				
			} 
			else if($action_type == '2')
			{
				exec_form($action->get_action($conn));
				email_form(NULL);       
				ticket_form(NULL);
				
			}
			if($action_type == '3')
			{
				ticket_form($action->get_action($conn));
				email_form(NULL);
				exec_form(NULL);

			}
		}
		else
		{
			email_form(NULL);
			exec_form(NULL);
			ticket_form(NULL);
		}

		submit();

		?>
	</table>

	</form>
	
</div>

</body>
</html>

<?php 

$db->close();

