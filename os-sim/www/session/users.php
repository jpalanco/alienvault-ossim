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

Session::logcheck('configuration-menu', 'ConfigurationUsers');

// Load column layout
require_once '../conf/layout.php';
$category    = 'policy';
$name_layout = 'host_layout';

$layout      = load_layout($name_layout, $category);

$db   = new ossim_db();
$conn = $db->connect();

$action   = REQUEST('action');
$user_id  = REQUEST('user_id');
$language = POST('language');


if (ossim_error()) 
{
    die(ossim_error());
}

$proadmin = Session::am_i_admin() || (Session::is_pro() && Acl::am_i_proadmin()); // admin user or pro admin


/* Allowed actions:
    
    - Enable/disable user
    - Expire session
    - Change language
*/

if ($action != "" && $user_id != '')
{    
    $myself = Session::get_session_user();    
    
    ossim_valid($user_id, OSS_USER,             'illegal:' . _('User ID'));
    ossim_valid($action,  OSS_ALPHA, OSS_SCORE, 'illegal:' . _('Action'));
    
    if (ossim_error()) 
    {
        echo ossim_error();
        exit();
    }
    
            
    if (!Token::verify('tk_f_users', GET('token')))
	{
		Token::show_error();
		exit();
	}  
     
    // Enable/disable user
    if ($action == 'change_status') 
    { 	
    	if (Session::userAllowed($user_id) > 1 && $user_id != AV_DEFAULT_ADMIN && $user_id != $myself)
    	{
    	   Session::toggle_enabled_user($conn, $user_id);
    	}
    }
    
    // Expire session
    if ($action == 'expire_session')  
    { 
        if (Session::userAllowed($user_id) > 1)
        {
            Session_activity::expire_my_others_sessions($conn, $user_id);
        }  	
    }

    // Change language
    if ($action == 'change_language') 
    {
        if (Session::userAllowed($user_id) > 1)
        {
            Session::change_user_language($conn, $user_id, $language);
        	if ($user_id == $myself) 
        	{
        		$_SESSION['_user_language'] = $language;
        		ossim_set_lang($language);
        		        		 
                $av_menu = new Menu($conn);
                $av_menu->set_menu_option('configuration', 'administration');
                $av_menu->set_hmenu_option('users');
                                
                $_SESSION['av_menu'] = serialize($av_menu);     
                         
                ?>		
        		<script type="text/javascript">                 		
            		top.parent.document.location.href = '/ossim/home/index.php';
                </script>		
        		<?php
        		exit();
        	}
        }
    }
} 



?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title><?php echo _('OSSIM Framework');?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache">
	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<link rel="stylesheet" type="text/css" href="../style/flexigrid.css"/>
	<script type="text/javascript" src="../js/jquery.min.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.min.js"></script>
	<script type="text/javascript" src="../js/jquery.flexigrid.js"></script>
	<script type="text/javascript" src="../js/notification.js"></script>
	<script type="text/javascript" src="../js/token.js"></script>
	<?php 
	if (Session::is_pro()) 
	{ 
		?>
		<link rel="stylesheet" type="text/css" href="../style/tree.css" />
		<script type="text/javascript" src="../js/jquery.cookie.js"></script>
		<script type="text/javascript" src="../js/jquery.dynatree.js"></script>
		<?php 
	} 
	?>
	<style type='text/css'>
		
		input, select 
		{
			border: 1px solid #8F8FC6;
			font-size:12px; 
			font-family:arial; 
			vertical-align:middle;
			padding:0px; 
			margin:0px;
		}
		
		#multilevel_tree
		{
    		display:none;
    		position:absolute;
    		top:97px;
    		right:0px;
    		z-index:500;
    		background-color:#E4E4E4;
    		padding:10px;
    		border:1px solid #ccc;
    		min-width: 300px;    
		}		
		
	</style>
	
	<script type='text/javascript'>
			
		<?php 
		if (Session::is_pro()) 
		{ 
			?>
			var nodetree;
			var layer;
			var i;
		
			function load_tree()
			{
				if (nodetree!=null) 
				{
					nodetree.removeChildren();
					$(layer).remove();
				}
				
				layer = '#srctree'+i;
				$('#tree').append('<div id="srctree'+i+'" style="width:100%;"></div>');
				
				$(layer).dynatree({
					initAjax: { url: "../tree.php?key=entitiesusers" },
					clickFolderMode: 2,
					onActivate: function(dtnode) {
						var key = dtnode.data.key;
						
						dtnode.deactivate();
						// Entity
						if (key.match(/^e_/)) 
						{
							key       = key.replace("e_","");
							var url   = "../acl/entities_edit.php?id="+key; // id (modify entity)
							var title = "<?php echo _("Modify Entity")?>";
							$('#multilevel_tree').hide();
							GB_show(title,url,'500','70%');
						}
						// User
						else if (key.match(/^u_/)) 
						{
							key       = key.replace("u_","");
							var url   = "user_form.php?greybox=1&login="+key; // login (modify user)
							var title = "<?php echo _("Modify User")?>";
							$('#multilevel_tree').hide();
							GB_show(title,url,'500','70%');
						}
					},
					onDeactivate: function(dtnode) {},
					onLazyRead: function(dtnode){
						dtnode.appendAjax({
							url: "../tree.php",
							data: {key: dtnode.data.key, page: dtnode.data.page}
						});
						if (typeof(parent.doIframe2)=="function") parent.doIframe2();
					}
				});
				
				nodetree = $(layer).dynatree("getRoot");
				i = i + 1;
							
			}
			<?php 
		} 
		?>
	
		function action(com,grid) 
		{
			var items = $('.trSelected', grid);
			
			if (com == '<?=_('Delete selected')?>') 
			{
				//Delete user by AJAX
				if (typeof(items[0]) != 'undefined') 
				{					
					if (confirm('<?php echo Util::js_entities(_('Are you sure you want to delete this user?'))?>'))
					{
						$("#flextable").changeStatus('<?php echo _('Deleting user')?>...', false);
						var dtoken = Token.get_token("delete_user");
						$.ajax({
							type: "GET",
							url: "deleteuser.php?user="+items[0].id.substr(3)+"&token="+dtoken,
							data: "",
							dataType: "json",
							cache: false,
							error: function(msg){
								var msg = '<?php echo _('Permission error').' - '._('You can not remove users')?>';
								notify(msg, 'nf_error', true);
								$("#flextable").changeStatus('',false);
								
							},
							success: function(msg) {
								if (typeof(msg) != 'undefined' && msg != null)
								{
									var msg_text = msg.data;
									var msg_type = (msg.status == 'OK') ? 'nf_success' : 'nf_error';
									
									$("#flextable").changeStatus('', false);
                                    notify(msg_text, msg_type, true);
									$("#flextable").flexReload();
								}
							}
						});
					}
				}
				else
				{
					alert('<?php echo Util::js_entities(_('You must select a user'))?>');
				}
			}
			else if (com == '<?=_('Modify')?>') 
			{
				if (typeof(items[0]) != 'undefined') 
				{
				    document.location.href = 'user_form.php?login='+items[0].id.substr(3);
				}
				else
				{ 
				    alert('<?php echo Util::js_entities(_('You must select a user'))?>');
				}
			}
			else if (com == '<?=_('Duplicate selected')?>') 
			{
				if (typeof(items[0]) != 'undefined') 
				{
                    if(items[0].id.substr(3) != 'admin') 
                    {
                        document.location.href = 'user_form.php?duplicate=1&login='+items[0].id.substr(3);
                    }
                    else 
                    {
                        alert('<?php echo Util::js_entities(_('The user admin can not be duplicated'))?>');
                    }
                }
				else
				{ 
				    alert('<?php echo Util::js_entities(_('You must select a user'))?>');
				}
			}
			else if (com == '<?=_('New')?>') 
			{
				document.location.href = 'user_form.php';
			}
			else if (com == '<?=_('Select all')?>') 
			{
				var rows = $("#flextable").find("tr").get();
				
				if(rows.length > 0) 
				{
					$.each(rows,function(i,n) {
						$(n).addClass("trSelected");
					});
				}
			}
			else if (com == '<?php echo _('Multilevel Tree') ?>') 
			{
				$('#multilevel_tree').toggle();
			}
		}
	
		function save_layout(clayout) 
		{
			$("#flextable").changeStatus('<?=_('Saving column layout')?>...', false);
			
			$.ajax({
				type: "POST",
				url: "../conf/layout.php",
				data: { name:"<?php echo $name_layout ?>", category:"<?php echo $category ?>", layout:serialize(clayout) },
				success: function(msg) {
					$("#flextable").changeStatus(msg, true);
				}
			});
		}
		
		function linked_to(rowid) 
		{
			document.location.href = 'user_form.php?login='+rowid;
		}
		
		
		function bind_handlers()
		{			
			$('.s_cl').change(function(){
    			
    			var login   = $(this).attr('id').replace('s_cl_', '');
    			var s_token = Token.get_token("f_users");
    		    var action  = $('#f_users_cl_'+login).attr('action')+"?token="+s_token;		    			
    			
    			$('#f_users_cl_'+login).attr('action', action);
    			$('#f_users_cl_'+login).submit();    			
			});
			
			$('.lnk_cs').click(function(){
    			
    			var login = $(this).attr('id').replace('lnk_cs_', '');
    			var s_token = Token.get_token("f_users");
    		    var action  = $('#f_users_cl_'+login).attr('action')+"?token="+s_token;		
    		   		
    		   	$('#f_users_cs_'+login).attr('action', action);		
    			$('#f_users_cs_'+login).submit();    			
			});
		}
			

		$(document).ready(function(){

			<?php			
			$msg = (GET('msg') != '') ? GET('msg') : $_SESSION['msg'];
			unset($_SESSION['msg']);			
			 
			if ($msg == 'created') 
			{ 
				?>
				notify('<?php echo _('The user has been created successfully')?>', 'nf_success', true);
				<?php 
			} 
			elseif ($msg == 'updated') 
			{ 
				?>
				notify('<?php echo _('The user has been updated successfully')?>', 'nf_success', true);
				<?php 
			}
			elseif ($msg == 'unknown_error') 
			{ 
				?>
				notify('<?php echo _('Invalid action - Operation cannot be completed')?>', 'nf_error', true);
				<?php 
			} 
			
			if (Session::is_pro()) 
			{ 
				?>
				load_tree();
				<?php
			}
			
			?>
			
		$("#flextable").flexigrid({
			url: 'getusers.php',
			dataType: 'xml',
			colModel : [
			<?php
				if (Session::am_i_admin()) 
				{
					$default = array(
						'login' => array(
							_('Login'),
							110,
							'true',
							'left',
							FALSE
						) ,
						'name' => array(
							_('Name'),
							150,
							'true',
							'center',
							FALSE
						),
						'email' => array(
							_('Email'),
							180,
							'false',
							'center',
							FALSE
						) ,
						'company' => array(
							_('Visibility'),
							150,
							'false',
							'center',
							FALSE
						) ,
						'active' => array(
							_('Status'),
							50,
							'false',
							'center',
							FALSE
						) ,
						'language' => array(
							_('Language'),
							180,
							'false',
							'center',
							FALSE
						) ,
						'creation_date' => array(
							_('Creation date'),
							150,
							'false',
							'center',
							FALSE
						) ,
						'last_login_date' => array(
							_('Last login date'),
							166,
							'false',
							'center',
							FALSE
						)
					);
				} 
				else 
				{
					$default = array(
						'login' => array(
							_('Login'),
							110,
							'true',
							'left',
							FALSE
						) ,
						'name' => array(
							_('Name'),
							170,
							'true',
							'center',
							FALSE
						),
						'email' => array(
							_('Email'),
							190,
							'false',
							'center',
							FALSE
						) ,
						'company' => array(
							_('Visibility'),
							160,
							'false',
							'center',
							FALSE
						) ,
						'language' => array(
							_('Language'),
							180,
							'false',
							'center',
							FALSE
						) ,
						'creation_date' => array(
							_('Creation date'),
							160,
							'false',
							'center',
							FALSE
						) ,
						'last_login_date' => array(
							_('Last login date'),
							174,
							'false',
							'center',
							FALSE
						)
					);
				}
	
				list($colModel, $sortname, $sortorder, $height) = print_layout($layout, $default, 'name', 'asc', 0);
				echo "$colModel\n";
				?>
				],
				buttons : [
					<?php 
					if ($proadmin) 
					{ 
						?>
						{name: '<?=_('New')?>', bclass: 'add', onpress : action},
						{separator: true},
						<?php 
					} 
					?>
					{name: '<?=_('Modify')?>', bclass: 'modify', onpress : action}	
					<?php 
					if ($proadmin) 
					{ 
						?>,
						{separator: true},
						{name: '<?=_('Delete selected')?>', bclass: 'delete', onpress : action},
						{separator: true},
						{name: '<?=_('Duplicate selected')?>', bclass: 'duplicate', onpress : action}
						<?php 
					}
					
					if ($proadmin && Session::is_pro()) 
					{ 
						?>
						,{separator: true},
						{name: "<?=_('Multilevel Tree')?>", bclass: 'duplicate', onpress : action}
						<?php 
					} 
					?>
				],
				searchitems : [
					{display: "<?=_('Login')?>", name : 'login'}
				],
				sortname: "<?php echo $sortname ?>",
				sortorder: "<?php echo $sortorder ?>",
				usepager: true,
				pagestat: '<?=_("Displaying <b>{from}</b> to <b>{to}</b> of <b>{total}</b> users")?>',
				nomsg: '<?php echo _("No users found in the system")?>',
				useRp: true,
				rp: 20,
				showTableToggleBtn: false,
				singleSelect: true,
				width: get_flexi_width(),
				height: 'auto',
				onColumnChange: save_layout,
				onDblClick: linked_to,
				onEndResize: save_layout,
				onSuccess: bind_handlers
			});						   
		});
	</script>
</head>

<body>
    
	<?php 
        //Local menu		      
        include_once '../local_menu.php';
    ?>

	<div style="margin: auto; position: relative; height: 1px; width: 560px;"> 
        <div id="av_msg_info" style="position:absolute; z-index:999; left: 0px; right: 0px; top: -10px; width:100%;"> 
        </div>
    </div>

	<?php 
	if (Session::is_pro())
	{ 
		?>
		<div id="multilevel_tree" class="tree" valign="top">
			<div id="tree" style="padding-top:0px"></div>
		</div>
		<?php 
	} 
	?>

	<table id="flextable" style="display:none"></table>

</body>
</html>

<?php $db->close(); ?>
