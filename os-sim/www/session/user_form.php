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
require_once 'languages.inc';


Session::useractive('../session/login.php');

$conf = $GLOBALS['CONF'];

/* Connect to db */
$db   = new ossim_db();
$conn = $db->connect();


// Expire session
$action   = REQUEST('action');

if ($action == 'expire_session')  
{    
    if (!Token::verify('tk_f_users', GET('token')))
	{
		Token::show_error();
		exit();
	} 
    
    if (Session::userAllowed($user_id) > 1)
    {
        Session_activity::expire_my_others_sessions($conn, $user_id);
    }  	
}


/* Version */
$pro  = Session::is_pro();


//Timezone
$tzlist = timezone_identifiers_list(4095);
sort($tzlist);

//Login method list
$lm_list = array('ldap' => _('LDAP'), 'pass' => _('PASSWORD'));
					

//Entities and Templates

$noentities  = 0;
$notemplates = 0; 
 
if ($pro)
{
	$entity_list = Session::get_entities_to_assign($conn);
	
	if (count($entity_list) < 1){ 
		$noentities = 1; 
	}
	
	list($entities_all,$num_entities_all) = Acl::get_entities($conn, '', '', FALSE, FALSE);
	
			
	$templates = array();
	list($templates, $num_templates) = Session::get_templates($conn);

	if (count($templates) < 1) 
	{ 
        $templates[0] = array(
            'id'   => '',
            'name' => ' -- '._('No templates found').' -- '
        ); 
		
		$notemplates  = 1; 
	}
} 
else
{
	list($menu_perms, $perms_check) = Session::get_menu_perms($conn);
}


//Initialize variables
$_SESSION['user_in_db'] = NULL;

$login             = '';
$uuid              = '';

$user_name         = '';
$email 	           = '';
$language          = 'en_GB';

$tzone    	  	   = date("e");

$login_enable_ldap = ($conf->get_conf('login_enable_ldap') == 'yes') ? TRUE : FALSE;

$login_method      = ($login_enable_ldap == TRUE) ? 'ldap' : 'pass';

$last_pass_change  = gmdate('Y-m-d H:i:s');

$first_login  	   = 0;
$is_admin     	   = 0;
$sel_assets        = array();
$sel_sensors       = array();
$template_id       = '';

if ($pro)
{ 
	$entities    = array();
}
else
{
	$company      = '';
	$departament  = '';
}


//Parameters
$greybox      = REQUEST('greybox');
$duplicate    = (GET('duplicate') != '') ? TRUE : FALSE;
$login        = REQUEST('login');
$msg          = GET('msg');
$load_cookies =  $_GET['load_cookies'];


//Check login
if ($login != '')
{
	ossim_valid($login, OSS_USER, 'illegal:' . _('User name'));
}

// Session parameters
$myself             = Session::get_session_user();
$am_i_admin         = Session::am_i_admin();
$is_default_admin   = ($login == AV_DEFAULT_ADMIN)       ? TRUE : FALSE;
$am_i_proadmin      = ($pro && Acl::am_i_proadmin())     ? TRUE : FALSE;
$is_my_profile      = ($login == $myself && !$duplicate) ? TRUE : FALSE;


ossim_valid($greybox,  OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _('Greybox'));

if (ossim_error())
{
	echo ossim_error();
	exit();
}

if($is_default_admin && $duplicate == TRUE) 
{
    echo ossim_error(_('The user admin can not be duplicated'));
	exit();
}

if ($login != '')
{
	if($login == AV_DEFAULT_ADMIN && $myself != AV_DEFAULT_ADMIN)
	{
        $user = '';
	}
	else 
	{
        $s_login   = escape_sql($login, $conn, FALSE);
	    $user_list = Session::get_list($conn, "WHERE login='$s_login'",  '', FALSE, TRUE);
	    $user      = $user_list[0];
	}
					
	if (is_object($user) && !empty($user))
	{
		$user         = $user_list[0];
		$uuid         = $user->get_uuid();
		$login        = ($duplicate == TRUE) ? $login.'_duplicated' : $login;
		$user_name    = $user->get_name();
		$email        = $user->get_email();
		$language     = $user->get_language();
		$tzone        = $user->get_tzone();
		$template_id  = $user->get_template_id();
		$login_method = $user->get_login_method();
		$login_method = ($login_method == 'ldap') ? 'ldap' : 'pass';
		$last_pass_change = $user->last_pass_change();
					
		$is_admin     = $user->get_is_admin();
		$first_login  = 0;
		
		$sel_assets   = $user->get_assets();
		$sel_sensors  = $user->get_sensors();
		
		if ($pro)
		{ 
			if (is_array($user->get_ctx()))
			{
				$entities = array_flip($user->get_ctx());
			}
		}
		else
		{
			$company      = $user->get_company();
			$department   = $user->get_department();
			
			//Allowed Menus
			if ($template_id != '') 
			{
				$template = Session::get_template_by_id($conn, $template_id);
			}
		}
		
		//If we don't do this, in user-edit.php it thinks that we are editing instead of creating a new user.
		if(!$duplicate)
		{
			$_SESSION['user_in_db'] = $login;
		}
	}
	else
	{
		if ($_GET['load_cookies'] != '1')
		{
    		echo ossim_error(_('Permission error - You can not edit this user'), AV_ERROR, 'margin: 40px auto; width: 80%; text-align:left;');
    		exit();
		}
	}
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo _('OSSIM Framework'); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	
	<script type="text/javascript" src="../js/jquery.min.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.min.js"></script>
	<script type="text/javascript" src="../js/greybox.js"></script>
	<script type="text/javascript" src="../js/combos.js"></script>
	<script type="text/javascript" src="../js/jquery.cookie.js"></script>
	<script type="text/javascript" src="../js/jquery.json-2.2.js"></script>
	<script type="text/javascript" src="../js/jquery.pstrength.js"></script>
	<script type="text/javascript" src="../js/jquery.tipTip.js"></script>
	
	<script type="text/javascript" src="../js/jquery.dynatree.js"></script>
	<script type="text/javascript" src="../js/jquery.checkboxes.js"></script>
	
	<script type="text/javascript" src="../js/messages.php"></script>
	<script type="text/javascript" src="../js/notification.js"></script>
	<script type="text/javascript" src="../js/ajax_validator.js"></script>
	<script type="text/javascript" src="../js/utils.js"></script>
	<script type="text/javascript" src="../js/token.js"></script>
	
	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<link rel="stylesheet" type="text/css" href="../style/tipTip.css"/>
	<link rel="stylesheet" type="text/css" href="../style/tree.css"/>

  
	<script type="text/javascript">
		
		// Exist txt,val in combo mysel
		function exists_in_combo(mysel,txt,val) 
		{
			var myselect=document.getElementById(mysel)
			
			for (var i=0; i<myselect.options.length; i++)
			{
				if (myselect.options[i].value==val)
				{
					return true;
				}
			}
			
			return false;
		}
		
		//Add element to a combo
		function addto(mysel,txt,val) 
		{			
			if (val == null)
			{ 
				val = txt;
			}
			
			add_select_item(mysel,val,txt);
		}
		
		//Select all elements of a multiselect combo
		function selectall(mysel) 
		{
			var myselect=document.getElementById(mysel)
			
			for (var i=0; i<myselect.options.length; i++)
			{
				myselect.options[i].selected = true;
			}	
		}
		
		function deletefrom(mysel) 
		{
			var delems = [];
			var myselect=document.getElementById(mysel);
			
			for (var i=0; i<myselect.options.length; i++)
			{
				if (myselect.options[i].selected == true && myselect.options[i].className != "noremove") 
				{
					delems.push(i);
					myselect.options[i].selected = false;
				}
			}
			
			for (var i=delems.length-1; i>=0; i--) 
			{
				myselect.remove(delems[i]);
			}
		}


		function add_entity(value, text)
		{
			addto('entities',text,value);
			load_tree('');
			deleteall('assets');
			deleteall('sensors');
			selectall('entities');
		}


		function add_select_item(select_id,val,text)
		{
			if (!exists_in_combo(select_id,text,val))
			{
				$('#'+select_id).append('<option value="'+val+'">'+text+'</option>');
			}
		}
		
		var checks = 1;
	
		function checkall() 
		{
			if (checks) 
			{
				$("#fuser").unCheckCheckboxes(".i_perms", true);
				checks = 0;
			} 
			else 
			{
				$("#fuser").checkCheckboxes(".i_perms", true);
				checks = 1;
			}
		}
		
		<?php
		if (Session::am_i_admin() && Session::is_pro()) 
		{ 
			?>
	
			function save_inputs() 
			{
				var data = new Array();
				
				data[0] = $('#login').val();
				data[1] = $('#user_name').val();
				data[2] = $('#email').val();
				data[3] = $('#language').val();
				data[4] = $('#tzone').val();
					
				if ($('#fl_yes').length >= 1)
				{
					data[5] = ($('#fl_yes:checked').length == 1) ? 'fl_yes' : 'fl_no';
				}
				
				if ($('#ia_yes').length >= 1)
				{
					data[6] = ($('#ia_yes:checked').length == 1) ? 'ia_yes' : 'ia_no';
				}
				
				if ($('#template_id').length >= 1)
				{
					data[7] = $('#template_id').val();
				}
				
						
				if ($('#entities option').length >= 1)
				{
					data[8]  = new Array();
					var cont = 0;
					$("#entities option").each(function () {
						data[8][cont]= new Array($(this).text(), $(this).val());
						cont++;
					});
				}
				
				
				if ($('#assets option').length >= 1)
				{
					data[9]  = new Array();
					var cont = 0;
					$("#assets option").each(function () {
						data[9][cont]= new Array($(this).text(), $(this).val());
						cont++;
					});
				}
				
							
				var date2m = (1 / 24 / 60) * 4; // Expire in 4 min
						
				$.cookie('data', $.toJSON(data), { expires: date2m });
			}
	
			function go_template(action) {
							
				if (action == "edit" || action == "new") 
				{		
					save_inputs();
					
					if (action == "edit") 
					{
						var template_id = $('#template_id').val();
						
						if (template_id == '')
						{
							alert("<?php echo Util::js_entities(_("No template selected"))?>");
							return;
						}
						
						var url   =  '../acl/template_form.php?id='+template_id+'&goback=1&callback=users';
						    url  += '&m_opt=configuration&sm_opt=administration&h_opt=users&l_opt=templates';				  

					}
					else if (action == "new")
					{ 
						var url  = '../acl/template_form.php?goback=1&callback=users';
						    url += '&m_opt=configuration&sm_opt=administration&h_opt=users&l_opt=templates';
					}

					var text_login  = $('#text_login').val();
					
					if (text_login != '')
					{
						url += '&login='+ $('#login').val();
					}
					
					<?php
					if ($greybox)
					{ 
						?>
						parent.document.location.href = url;
						<?php 
					} 
					else
					{ 
						?>
						document.location.href = url;
						<?php 
					} 
					?>
				}
			}		
			<?php 
		} 
		
		?>
			
		function load_inputs() 
		{
			var data = $.evalJSON($.cookie('data'));
			
			//console.log(data);
						
			if (data != null && typeof(data) == 'object')
			{
				$('#login').val(data[0]);
				$('#user_name').val(data[1]);
				$('#email').val(data[2]);
				$('#language').val(data[3]);
						
				if (typeof(data[5]) != 'undefined' && data[5] != null)
				{
					$('#'+data[5]).attr('checked', 'checked');
				}
				
				if (typeof(data[6]) != 'undefined' && data[6] != null)
				{
					$('#'+data[6]).attr('checked', 'checked');
				}
				
				if (typeof(data[7]) != 'undefined' && data[7] != null)
				{
					$('#template_id').val(data[7])
				}
							
				if (typeof(data[8]) != 'undefined' && data[8] != null)
				{
					$.each(data[8], function(index, data){
						addto('entities', data[0], data[1]);
					});

					load_tree('');
					deleteall('assets');
					deleteall('sensors');
				}
				
				if (typeof(data[9]) != 'undefined' && data[9] != null)
				{
					$.each(data[9], function(index, data){
						addto('assets', data[0], data[1]);
					});
				}
				
				$('#tzone').val(data[4]);
			}
			
			
			//Delete cookie		
			$.cookie("data", null);
		}
			
				
		//Asset tree
		
		function load_tree(filter)
		{
			combo = 'assets';
			
			<?php 
			if ($pro) 
			{ 
				?>
				var entity = '';
				$("#entities option").each(function() {
					if (entity != '') {
						entity += ",";
					}
					
					entity += $(this).val();
				});
				<?php 
			} 
			else 
			{
				?>
				var entity  = "<?php echo Session::get_default_ctx()?>";
				<?php
			} 
			?>
			
			$("#assets_tree").remove();
			
			$('#td_assets').css('vertical-align', 'top');
			$('#td_assets').append('<div id="assets_tree" style="width:100%"></div>');

			if (entity != '')
			{
				var key = <?php echo '"e_"+entity+"_assets|' . (($pro) ? 'se_"+entity' : 'sensors"') ?>;

				$("#assets_tree").dynatree({
					initAjax: { url: "../tree.php?key="+key },
					clickFolderMode: 2,
					minExpandLevel:  2,
					onActivate: function(dtnode) {
						if (dtnode.data.key.match(/net_/) || dtnode.data.key.match(/host_/))
						{
							k = dtnode.data.key.replace(/(host|net)_/, '');
							addto(combo,dtnode.data.val,k);
						} 
						else if (dtnode.data.key.match(/sensor_/)) 
						{
							k = dtnode.data.key.replace(/(sensor)_/, '');
							addto("sensors",dtnode.data.val+" ("+dtnode.data.ip+")",k);
						}

						// Click on asset group, fill box with its members
						else if (dtnode.data.key.match(/hostgroup_/))
						{
						    $.ajax({
						        type: 'GET',
						        url: "../tree.php",
						        data: 'key=' + dtnode.data.key + ';1000',
						        dataType: 'json',
						        success: function(data)
						        {
							        if (data.length < 1)
							        {
							            var nf_style = 'padding: 3px; width: 90%; margin: auto; text-align: center;';
							            var msg = '<?php echo _('Unable to fetch the asset group members') ?>';
							            show_notification('av_info_assets', msg, 'nf_error', 0, 1, nf_style);
							        }
							        else
							        {
                                        // Group reached the 1000 top of page: show warning
	                                    var last_element = data[data.length - 1].key;

	                                    if (last_element.match(/hostgroup_/))
	                                    {
	                                        var nf_style = 'padding: 3px; width: 90%; margin: auto; text-align: center;';
	                                        var msg = '<?php echo _('This asset group has more than 1000 assets, please try again with a smaller group') ?>';
	                                        show_notification('av_info_assets', msg, 'nf_warning', 0, 1, nf_style);
	                                    }
	                                    else
	                                    {
        	                                    jQuery.each(data, function(i, group_member)
        		                                {
    	                                            var k = group_member.key.replace("host_","");
    	                                            addto(combo, group_member.val, k);
        	                                    });
	                                    }
							        }
						        }
						      });
						}
					},
					onDeactivate: function(dtnode) {},
					onLazyRead: function(dtnode){
						dtnode.appendAjax({
							url: "../tree.php",
							data: {key: dtnode.data.key, page: dtnode.data.page}
						});
					}
				});
			} 
			else
			{
				var config_nt = { content: '<?php echo _('To assign assets, select first an entity context')?>', 
							      options: {
									type: 'nf_info', 
									cancel_button: false
								  },
								  style: 'width: 80%; margin: 40px auto; text-align:center; font-size: 11px; white-space: normal;'
							    };
			
				var nt        = new Notification('nt_1', config_nt);
				notification  = nt.show();
				
				$('#td_assets').css('vertical-align', 'middle');			
				$('#assets_tree').html(notification);
			}
		}
		
		
		//Remove entities
		function remove_entity()
		{		
			deletefrom('entities');
			load_tree('');
			deleteall('assets');
			deleteall('sensors');
		}
		
		
		//Toggle menus
		function toggle_menu(id)
		{				
			var menu_id = '#'+id+'_arrow';
			
			$('.'+id).toggle();
			
			if($('.'+id).is(':visible'))
			{
				$(menu_id).attr('src','../pixmaps/arrow_green_down.gif'); 
			}
			else
			{ 
				$(menu_id).attr('src','../pixmaps/arrow_green.gif');
			}

			return false;
		}
		
		
		//Change login method
		function change_login_method() 
		{			
			var login_method = $('#login_method').val();
			
			if (login_method == "ldap") 
			{
				$('.pass_container').hide();
				$('.pass_option').removeClass('vfield');
			} 
			else 
			{
				$('.pass_container').show();
				$('.pass_option').addClass('vfield');
			}
		}
		
		function submit_form()
		{		
			if ($('#entities').length >= 1)
			{
				selectall('entities');
			}
			
			if ($('#assets').length >= 1)
			{
				selectall('assets');
			}

			if ($('#sensors').length >= 1)
			{
				selectall('sensors');
			}
			
			ajax_validator.submit_form();
	    }
	    
	    
	    function bind_perms_dependencies()
		{
			var perms = { "3"  : "1", "47" : "46",  //Dashboards
						  "70" : "60", "77" : "22", "23" : "22", "25" : "22", "40" : "22", "76" : "22", 
						  "71" : "48", "51" : "48", //Analysis
						  "74" : "65", "75" : "65", //Reports 
						  "72" : "49", "73" : "49", "82" : "79", 
						  "42" : "10,11", "54" : "10,11", "64" : "10,11", //Environment
						  "33" : "31", "39" : "35", "29" : "12", "17" : "36" //Configuration	
				};	
			
			for (id in perms)
			{
				$('#'+id).bind('click', {perms:perms[id], id:id}, function(event)  { check_perm(event.data.perms, event.data.id) });
			}
		}
		
		
		function check_perm(perms, id)
		{
			var my_perms = perms.split(",");
			
			if ($('#'+id).length == 1 && $('#'+id+':checked').length == 1)
			{
				for (var i=0; i<my_perms.length; i++)
				{
					if ($('#'+my_perms[i]).length == 1 && $('#'+my_perms[i]+':disabled').length == 0)
					{ 
						$('#'+my_perms[i]).attr("checked", "checked");
					}
				}
			}
		}
        
            
		$(document).ready(function(){
			$('[name="is_admin"]').change(function() {
				var prop = $(this).val() == 1;
                                $("#assets").prop( "disabled", prop );
                                $("#sensors").prop( "disabled", prop );
			});
			 $('[name="is_admin"]:checked').change();
			bind_perms_dependencies();
			
			Token.add_to_forms();
			
			<?php 
			if ($_GET['load_cookies'] != '') 
			{
				?>
				load_inputs(); $('#pass1');
				<?php
			}
			?>
			
			$('#pass1').pstrength();
						
			// Assets Tree
			load_tree('');
		
			$(".info").tipTip();
			
			
			$("a.greybox").click(function(){
				var t = this.title || $(this).text() || this.href;
				GB_show(t,this.href,340,"70%");
				
				return false;
			});
			
			
			//Entities

			<?php 
			if($pro) 
			{
			    ?>
				var key = 'entities';
				
				$("#entities_tree").dynatree({
					initAjax: { url: "../tree.php?key="+key },
					clickFolderMode: 2,
					minExpandLevel:  2,
					onActivate: function(dtnode) {
						
						var _key = dtnode.data.key.replace('e_', '');
						var _val = dtnode.data.val;

						if(_key != '' && _val != '')
						{
							add_entity(_key, _val);
						}
						
						dtnode.deactivate();
						
						return false;

					},
					onDeactivate: function(dtnode) {},
					onLazyRead: function(dtnode){
						dtnode.appendAjax({
							url: "../tree.php",
							data: {key: dtnode.data.key}
						});
					}
				});
				<?php
			}
			?>
		
				
			$('#remove_entity').click(function(){
				remove_entity();
			});
			
			$('#link_show_tmp').click(function(){
				toggle_menu('menus');
			});
			
			$('#link_show_assets').click(function(){
				toggle_menu('perms');
			});

			$('#link_show_sensors').click(function(){
				toggle_menu('sperms');
			});
			
			$('#login_method').change(function(){
				change_login_method();
			});
			
			$('#cancel').click(function(){
				top.frames['main'].GB_hide();
			});
			
			
			var config = {   
				validation_type: 'complete', // single|complete
				errors:{
					display_errors: 'all', //  all | summary | field-errors
					display_in: 'av_info'
				},
				form : {
					id  : 'fuser',
					url : "users_edit.php"
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
				submit_form();
			});			
		});
	</script>

	<style type='text/css'>
        #container_center
        {
            margin: 20px auto;
            width: 640px;
        }

        #container_center table
        {
            width: 100%;
            margin: auto;
        }

        #container_center td
        {
            vertical-align:middle;
            white-space: nowrap;
        }

        #container_center .s_label, #container_center .label
        {
            font-size: 12px;
        }

        #container_center label.y_n
        {
            font-size: 11px !important;
        }

        input[type="text"], input[type="password"], select
        {
            width: 99% !important;
            height: 20px !important;
        }

        #template_id
        {
            width: 250px !important;
        }

        #e_container
        {
            padding: 5px 0px 3px 0px;

        }

        #entity_to_add
        {
            width: 250px !important;
        }

        #entities
        {
            height: 100px !important;
        }

        #c_tree
        {
            vertical-align: top !important;
            padding-top: 0px !important;
        }

        ul.dynatree-container
        {
            padding-top: 5px !important;
            margin-top: 5px !important;
        }

        #entities_tree
        {
            text-align: left;
        }

        #assets
        {
            height: 100px !important;
        }
        #sensors
        {
            height: 50px !important;
        }

        #td_assets
        {
            vertical-align: top !important;
        }

        #sel_assets
        {
            padding-top: 12px;
            vertical-align: top !important;
        }

        .text_login
        {
            filter:alpha(opacity=50);
            -moz-opacity:0.5;
            -khtml-opacity: 0.5;
            opacity: 0.5;
            font-style: italic;
            cursor: default;
        }

        .padding_bottom
        {
            padding-bottom: 10px;
        }

        #pass1_bar
        {
            max-width: 100% !important;
        }

        #av_info
        {
            margin: 20px auto;
        }

        #av_info_assets
        {
            margin: 10px auto;
        }

        #send
        {
            margin: 0px 8px 0px 0px;
        }

	</style>
</head>

<body>
		
	<div id='container_center'>
	
		<div id='av_info'>
			<?php
									
			if (!empty($msg))
			{
				$txt_msg = ($msg == 'created') ? _('The user has been created successfully') : _('The user has been saved successfully');
				
				$config_nt = array(
					'content' => $txt_msg,
					'options' => array (
						'type'          => 'nf_success',
						'cancel_button' => FALSE
					),
					'style'   => 'width:100%; margin: 20px auto; text-align: center;'
				); 
						
				$nt = new Notification('nt_new_user', $config_nt);
				$nt->show();
			}
			
			
			if ($login != '' && $template_id == "0" && ($am_i_admin || $am_i_proadmin) && !$is_default_admin) 
			{
				$txt_error = '<div>'._('We found the following warning').":</div>
					  <div style='padding:2px 0px 5px 10px;'>"._('The user has <strong>no menu template</strong> defined').'.</div>';				
				
				$config_nt = array(
					'content' => $txt_error,
					'options' => array (
						'type'          => 'nf_warning',
						'cancel_button' => FALSE
					),
					'style'   => 'width:100%; margin: 20px auto; text-align: left;'
				); 
						
				$nt = new Notification('nt_template', $config_nt);
				$nt->show();
			}
			?>
		</div>
			
	
		<!-- User form -->
		<form name="fuser" id="fuser" method="POST" action='users_edit.php'>		
			
			<input type="hidden" name="uuid" id="uuid" class='vfield' value="<?php echo $uuid?>"/>
			<input type="hidden" name="greybox" id="greybox" class='vfield' value="<?php echo $greybox?>"/>
			
			<?php 
			if ((!$pro && $login != '') || ($pro && $is_my_profile)) 
			{ 
				?>
				<input type="hidden" name="template_id" id="template_id" class='vfield' value="<?php echo $template_id?>"/>
				<?php 
			} 
			?>
			
			<table>
			         
				<!-- Login -->
				<tr>
					<th><label for="login"><?php echo _('User login') . required(); ?></label></th>
					<td class="nobborder">
						<?php
                        if ($login != '' && (!$duplicate && $_GET['load_cookies'] != '1'))
						{
							?>
							<input type="text" name="text_login" id="text_login" class='text_login' maxlength="64" disabled='disabled' readonly='readonly' autocomplete="off" value="<?php echo $login?>"/>
							<input type="hidden" class='vfield' maxlength="64" name="login" id="login" value="<?php echo $login ?>"/>
							<?php 
						}
						else
						{
							?>
							<input type="text" name="login" id="login" class='vfield' maxlength="64" autocomplete="off" value="<?php echo $login?>"/>
							<?php 
						}
						?>
					</td>
				</tr>

				<!-- User name -->
				<tr>
					<th><label for="user_name"><?php echo _('User name'). required();?></label></th>
					<td class="nobborder">
						<input type="text" autocomplete="off" maxlength="128" class='vfield' name="user_name" id="user_name" value="<?php echo $user_name?>"/>
					</td>
				</tr>
			

				<!-- User email -->
				<tr>
					<th><label for="email"><?php echo _('User email');?><img style='margin-left: 3px;' src="../pixmaps/email_icon.gif"/></label></th>
					<td class="nobborder">
						<input type="text" autocomplete="off" class='vfield' name="email" id="email" maxlength="255" value="<?php echo $email?>"/>
					</td>
				</tr>
	
				<!-- User language -->
				<tr>
					<th><label for="language"><?php echo _('User language'). required();?></label></th>
					<td class="nobborder">
						<select id='language' name='language' class='vfield'>
							<?php
							foreach($languages['type'] as $l_value => $l_text)
							{
								$selected = ($language == $l_value) ? "selected='selected'" : '';
								?>
								<option <?php echo $selected?> value='<?php echo $l_value?>'><?php echo $l_text?></option>
								<?php
							}
							?>
						</select>
					</td>
				</tr>
				
				<!-- Timezone -->
				<tr>
					<th><label for="tzone"><?php echo _('Timezone'). required();?></label></th>
					<td class="nobborder">
						<?php $tzone = (preg_match("/Localtime/", $tzone)) ? trim(Util::execute_command('head -1 /etc/timezone', FALSE, 'string')) : $tzone;?>
						<select name="tzone" id="tzone" class='vfield'>
							<?php  
							foreach($tzlist as $tz) 
							{
								if ($tz == 'localtime')
								{
									continue;
								}
								
								$selected = ($tz == $tzone) ? "selected='selected'" : '';
								?>
								<option value='<?php echo $tz?>' <?php echo $selected?>><?php echo $tz?></option>
								<?php
							}
							?>
						</select>
					</td>
				</tr>
				
				<?php 
				if (!$pro) 
				{ 
					?>
					<!-- Company -->
					<tr>
						<th><label for="company"><?php echo _('Company');?></label></th>
						<td class="nobborder"><input type="text" name="company" maxlength="128" class='vfield' value="<?php echo $company?>"/></td>
					</tr>
					
					<!-- Department -->
					<tr>
						<th><label for="department"><?php echo _('Department');?></label></th>
						<td class="nobborder"><input type="text" name="department" maxlength="128" class='vfield' value="<?php echo $department?>"/></td>
					</tr>
					<?php 
				} 
				
				?>
				<tr><td class="nobborder" colspan='2' style='height: 10px;'></td></tr>
				
				<!-- Current Password -->
				<tr>
					<th>
					    <label for="c_pass"><?php echo _('Enter your current password') . required();?></label>
					</th>
					<td class="nobborder">
						<input type="password" name="c_pass" id="c_pass" autocomplete="off" class='vfield'/>
						<input type="hidden" name="last_pass_change" id="last_pass_change" class='vfield' value="<?php echo $last_pass_change;?>"/>
					</td>
				</tr>
				
				<tr><td class="nobborder" colspan='2' style='height: 10px;'></td></tr>
				
				<?php
				
				if ($login_enable_ldap == TRUE && !$is_default_admin) 
				{ 
					?>
					<!-- Login method -->
					<tr>
						<th><label for="login_method"><?php echo _('Login method')?></label></th>
						<td class="nobborder">
							<select name="login_method" id="login_method" class='vfield'>
								<?php  
								foreach($lm_list as $lm_value => $lm_text) 
								{
									$selected = ($lm_value == $login_method) ? "selected='selected'" : '';
									?>
									<option value='<?php echo $lm_value?>' <?php echo $selected?>><?php echo $lm_text?></option>
									<?php
								}
								?>
							</select>
						</td>
					</tr>
					<?php 
				} 
				else 
				{ 
					?>
					<input type="hidden" name="login_method" class='vfield' value="pass"/>
					<?php
				}
				
				$p_class      = ($login_method == 'pass') ? "class='pass_container'" : "class='pass_container' style='display:none;'";
				$pass1_text   = ($login != '' && $duplicate == FALSE) ? _('Enter new user password') : _('Enter user password');
				$pass2_text   = ($login != '' && $duplicate == FALSE) ? _('Retype new user password') : _('Re-enter user password');
				?>
				
				<!-- Passwords -->
				<tr <?php echo $p_class?>>
					<th><label for="pass1"><?php echo $pass1_text . required()?></label></th>
					<td class="nobborder"><input type="password" name="pass1" id="pass1" autocomplete="off" class='pass_option vfield'/></td>
				</tr>
								
				
				<tr <?php echo $p_class?>>
					<th><label for="pass2"><?php echo $pass2_text . required()?></label></th>
					<td class="nobborder"><input type="password" name="pass2" id="pass2" autocomplete="off" class='pass_option vfield'/></td>
				</tr>
				
				<tr><td class="nobborder" colspan='2' style='height: 10px;'></td></tr>
					
				<?php
				if (($am_i_admin || $am_i_proadmin) && (!$is_my_profile && !$is_default_admin))
				{
					?>
					<!-- First Login -->
					<tr <?php echo $p_class?>>
						<th><span class='s_label' id="first_login"><?php echo _('Ask to change password at next login')?></span></th>
						<td class="nobborder" align='center'>
						   <input type="radio" id='fl_yes' name="first_login" class='pass_option vfield' value="1"/>
						   <label class="y_n" for="fl_yes"><?php echo _('Yes');?></label>
						   <input type="radio" id='fl_no'  name="first_login" class='pass_option vfield' value="0" checked='checked'/>
						   <label class="y_n" for="fl_no"><?php echo _('No');?></label>
						</td>
					</tr>
				
					<?php
					if ($am_i_admin)
					{ 
						$s_ia_no  = "checked='checked'";
						$s_ia_yes = '';
						
						if ($is_admin == 1)
						{
							$s_ia_no  = '';
							$s_ia_yes = "checked='checked'";
						}
						?>
						
						<!-- Global admin -->
						<tr>							
							<th><span class='s_label' id="first_login"><?php echo _('Make this user a global admin')?></span></th>
							<td class="nobborder" align='center'>
								<input type="radio" id='ia_yes' name="is_admin" class='vfield' value="1" <?php echo $s_ia_yes?>/><span>
								<label class="y_n" for="ia_yes"><?php echo _('Yes');?></label>								
								<input type="radio" id='ia_no'  name="is_admin" class='vfield' value="0" <?php echo $s_ia_no?>/><span>
								<label class="y_n" for="ia_no"><?php echo _('No');?></label>
							</td>
						</tr>
						<?php 
					} 
					
					if ($pro) 
					{ 
						?>
						<!-- Menu Template -->
						<tr>
							<th><label for="template_id"><?php echo _('Menu Template'). required();?></label></th>
							<td class="nobborder">
								<select id="template_id" name="template_id" class='select_tpl vfield'>
									<?php 
									foreach ($templates as $template) 
									{ 
										$selected = ($template_id == $template['id']) ? "selected='selected'" : '';
										?>
										<option value="<?php echo $template['id']?>" <?php echo $selected?>><?php echo $template['name']?></option>
										<?php 
									} 
									?>
								</select>
								
								<?php 
								if ($am_i_admin)
								{
									if ($notemplates == 0 && valid_hex32($templates[0]['id'])) 
									{ 
										?>
										<a href="javascript:;" onclick="go_template('edit')">
											<img align="absmiddle" src="../pixmaps/tables/table_edit.png" border="0" class="info" alt="<?php echo _('View template')?>" title="<?php echo _('View template')?>"/>
										</a>
										<?php 
									} 
									?>
									<a href="javascript:;" onclick="go_template('new')">
										<img src="../pixmaps/tables/table_row_insert.png" align="absmiddle" alt="<?php echo _('Insert new template')?>" class="info" title="<?php echo _('Insert new template')?>"/>
									</a>
									<?php 
								} 
								?>
							</td>
						</tr>
						
						<!-- Entities -->
						<tr>
							<td id='c_tree'>
								<div id="entities_tree" style="width:100%"></div>
							</td>												
						
							<td class="nobborder" valign='top'>
								<table class="transparent">
									<tr>
										<th style="padding:1px !important">
											<label for="entities"><?php echo _('Visibility')?></label>
										</th>
									</tr>
									<tr>
										<td>
											<select id="entities" name="entities[]" multiple="multiple" class='vfield'>
												<?php 
												foreach ($entities_all as $e_key => $e_data)
												{
													if (array_key_exists($e_key, $entities))
													{
														$style = (empty($entity_list[$e_key])) ? "style='color:gray' class='noremove'" : ''; 
														?>
														<option value="<?php echo $e_key?>" <?php echo $style?>><?php echo $entity_list[$e_key]?></option>
														<?php
													}
												}
												?>
											</select>
										</td>
									</tr>
								
									<tr>
										<td align="right">
											<input type="button" class="small av_b_secondary" id='remove_entity' name='remove_entity' value="[X]"/>
										</td>
									</tr>									
								</table>
							</td>
						</tr>
						<?php 
					} 
										
					?>
					<tr><td class="nobborder" colspan='2' style='height: 10px;'></td></tr>
					<?php
					
					if (!$pro && $am_i_admin) 
					{							
						?>
						<!-- Templates -->
						<tr>
							<td class="nobborder">
								<span id="menu_perms[]">
    								<a href="javascript:;" id="link_show_tmp" class="uppercase">
    								    <img id="menus_arrow" border="0" align="absmiddle" src="../pixmaps/arrow_green.gif"/><?php echo ('Allowed Menus')?>
    								</a>
								</span>
							</td>
						</tr>
					
						<tr class="menus" style="display:none">
							
							<td class="nobborder" valign="top" colspan="2">
								<div class="padding_bottom"> <a href="javascript:;" onclick="checkall();return false;"><?php echo _('SELECT').' / '._('UNSELECT ALL');?></a> </div>
								<table class="table_list" width='100%'>					
									<?php
									$i = 0;
									foreach($menu_perms as $mainmenu => $menus) 
									{
										$flag = FALSE;
										foreach($menus as $key => $menu) 
										{
											$color    = ($i++ % 2 != 0) ? "class='odd'" : "class='even'";
											$selected = ($template['perms'][$key] || $login == '') ? "checked='checked'" : '';
											?>
											<tr <?php echo $color?>>
												<td class="nobborder" nowrap="nowrap">
													<input class="i_perms" type="checkbox" name="menu_perm<?php echo $key ?>" id='<?php echo $key?>' <?php echo $selected?>>
													<?php echo Util::get_gettext_from_string('->', $menu); ?>
												</td>
											</tr>
											
											<?php												
																						
											$flag = TRUE;
										}
										
										if ($flag == TRUE) 
										{
											?>
											<tr><td colspan='2' class='transparent'></td></tr>
											<tr><td colspan='2' class='hidden'></td></tr>
											<?php
										}
									}
									?>
								</table>
							</td>
						</tr>
						<?php 
					}
					
					
					$asset_title = ($pro) ? _('Allowed Assets') : _('Asset Filters');
					
					$asset_img  = '../pixmaps/';					
					if (count($sel_assets) > 0)
					{
						$asset_img     .= 'arrow_green_down.gif';
						$display_assets = '';
					
					}
					else
					{
						$asset_img     .= 'arrow_green.gif';
						$display_assets = 'none';
					}
					
					?>
					<!-- Assets -->					
					<tr>
						<td class="nobborder">
							<a href="javascript:;" id="link_show_assets" class='uppercase'>
								<img id="perms_arrow" border="0" align="absmiddle" src="<?php echo $asset_img?>"/><?php echo $asset_title?>
							</a>
						</td>
					</tr>
					
					<tr class="perms" style="display:<?php echo $display_assets?>">
						<th colspan="2"><?php echo $asset_title?></th>
					</tr>
					
					<tr class="perms" style="display:<?php echo $display_assets?>">
						<td class="nobborder" id="td_assets"></td>
						
						<td class="nobborder" id='sel_assets'>
							<table class="transparent">
								<tr>
								    <th style="padding:1px !important">
								        <label for="assets"><?php echo _('Assets')?></label>
								    </th>
								</tr>
								<tr>
									<td>
									    <div id='av_info_assets'></div>
										<select name="assets[]" id="assets" class='vfield' multiple="multiple">
											<?php 
											if (is_array($sel_assets) && !empty($sel_assets))
											{
												foreach ($sel_assets as $asset) 
												{ 
													$asset_id   = $asset->get_id();													
													
													if ($asset->get_asset_type() == 'net')
													{
    													$asset_name = $asset->get_name();
    													$asset_ips  = $asset->get_ips();
													}
													else
													{
    													$asset_name = $asset->get_name();
    													
    													$host_ips   = $asset->get_ips();
    													$asset_ips  = $host_ips->get_ips('string');    											
													}
													
													$asset_txt = $asset_name.' ('.$asset_ips.')';
												
													?>
													<option value="<?php echo $asset_id?>"><?php echo $asset_txt?></option>
													<?php 
												}
											}
											?>
										</select>
									</td>
								</tr>
							
								<tr><td align="right"><input type="button" class="small av_b_secondary" onclick="deletefrom('assets');" value=" [X] "/></td></tr>
								
								<tr>
								    <th style="padding:1px !important">
								        <label for="sensors"><?php echo _('Sensors')?></label>								    
								    </th>
								</tr>
								<tr>
									<td>
										<select name="sensors[]" id="sensors" class='vfield' multiple="multiple">
											<?php 
											if (is_array($sel_sensors) && !empty($sel_sensors))
											{
												foreach ($sel_sensors as $sensor) 
												{ 
													$sensor_id   = $sensor->get_id();
													$sensor_name = $sensor->get_name().' ('.$sensor->get_ip().')';
													?>
													<option value="<?php echo $sensor_id?>"><?php echo $sensor_name?></option>
													<?php 
												}
											}
											?>
										</select>
									</td>
								</tr>
								
								<tr><td align="right"><input type="button" class="small av_b_secondary" onclick="deletefrom('sensors');" value=" [X] "/></td></tr>
							</table>
						</td>
					</tr>
					<tr><td class="nobborder" colspan='2' style='height: 10px;'></td></tr>
					<?php
				}
				?>
				<!-- Actions -->
				<tr>
					<td class="center noborder" colspan="2">
						<input type="button" id="send"  name='send' value="<?php echo _('Save')?>"/>						
						<?php 
						if ($greybox == TRUE) 
						{ 
							?>
							<input type="button" name='cancel' class="av_b_secondary" id="cancel" value="<?php echo _('Cancel');?>"/>
							<?php 
						} 
						?>
					</td>
				</tr>

				<tr><td class="nobborder" colspan='2' style='height: 10px;'></td></tr>

			</table>
						
		</form>
		<!-- End user form -->
		
	</div>
</body>
</html>

<?php $db->close(); ?>
