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


require_once 'permission_common.php';


Session::logcheck("dashboard-menu", "ControlPanelExecutive");
Session::logcheck("dashboard-menu", "ControlPanelExecutiveEdit");


if (!Session::am_i_admin() && (Session::is_pro() && !Acl::am_i_proadmin()))
{
	 $config_nt = array(
		'content' => _("You do not have permission to see this section"),
		'options' => array (
			'type'          => 'nf_error',
			'cancel_button' => false
		),
		'style'   => 'width: 60%; margin: 30px auto; text-align:center;'
	); 
					
	$nt = new Notification('nt_1', $config_nt);
	$nt->show();
	
	exit();
}
	

$db        = new ossim_db();
$conn      = $db->connect();

$msg_index = $_SESSION['_db_perms_msg_index'];

unset($_SESSION['_db_perms_msg_index']);

switch($msg_index)
{
	case 1: 
		$msg = _('Tab Cloned Successfully').'.';
		break;
		
	default:
		$msg = "";
}


$users = Session::get_users_to_assign($conn);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo _("Dashboard Permissions"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html;charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<?php

        //CSS Files
        $_files = array(
            array('src' => 'av_common.css?only_common=1',           'def_path' => TRUE),
            array('src' => 'jquery-ui.css',       		            'def_path' => TRUE),
            array('src' => 'jquery.contextMenu.css',	            'def_path' => TRUE),
            array('src' => 'tipTip.css',         		            'def_path' => TRUE),
            array('src' => 'tree.css',            		            'def_path' => TRUE),
            array('src' => 'coolbox.css',         		            'def_path' => TRUE),
            array('src' => 'dashboard/overview/permission.css',     'def_path' => TRUE)
        );
        
        Util::print_include_files($_files, 'css');

        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',                       	'def_path' => TRUE),
            array('src' => 'jquery-ui.min.js',                    	'def_path' => TRUE),
            array('src' => 'utils.js',                            	'def_path' => TRUE),
            array('src' => 'token.js',                            	'def_path' => TRUE),
            array('src' => 'jquery.tipTip.js',                    	'def_path' => TRUE),
            array('src' => 'jquery.dynatree.js', 				  	'def_path' => TRUE),
            array('src' => 'jquery.contextMenu.js', 				'def_path' => TRUE),
            array('src' => 'coolbox.js',                          	'def_path' => TRUE),
            array('src' => 'notification.js',                     	'def_path' => TRUE),
            array('src' => '/dashboard/js/jcarousel.js',          	'def_path' => FALSE),
            array('src' => '/dashboard/js/dashboard_perms.js.php',	'def_path' => FALSE)
        );
        Util::print_include_files($_files, 'js');

    ?>

	<script type='text/javascript'>
	
		$(document).ready(function()
		{
            <?php 
            if(!empty($msg)) 
            {
                echo "show_notification('$msg','nf_success');";
            }       
            ?>

			load_dashboard_perms_scripts();
		});

	</script>

</head>
	
<body>
	
	<div id='tree_container'>
		<div id="tree"></div>
		<div id="tree_buttons">
			<button id='btnDeselectAll' class='fleft av_b_secondary'><?php echo _("Unselect All")?></button>
			<button id='btnSelectAll' class='fright'><?php echo _("Select All")?></button>
		</div>
	</div>

	<div id='tree_button'>
		<button id='button_tree'><?php echo _("Select Users to Display") ?></button>
	</div>
	
	<div id='container_info'></div>

	<table id='perm_container' align='center' valign='middle' class='transparent' width='100%'; height='100%'>
		<tr>
			<td class='noborder' valign='top'>
				<div id='carrousel_container'>		
					<?php 
						if(!empty($users))
						{
							echo draw_carrousel($conn, $users); 
						} 
						else 
						{
							$config_nt = array
							(
								'content' => _("No Users Found"),
								'options' => array (
									'type'          => 'nf_warning',
									'cancel_button' => true
								),
								'style'   => 'width: 60%; margin: 30px auto; text-align:center;'
							); 
											
							$nt = new Notification('nt_1', $config_nt);
							$nt->show();
						}				
					?>
				</div>
			</td>
		</tr>
	</table>

	<!-- Context Menu -->
	<ul id="myMenuTab" class="contextMenu">
		<li class="addUser"><a href="#copyuser"><?php echo _("Copy this Tab to user")?></a></li>
		<li class="addEntity"><a href="#copyentity"><?php echo _("Copy this Tab to entity")?></a></li>
		<li class="addAll"><a href="#copyall"><?php echo _("Copy this Tab to all users")?></a></li>
		<li class="toggle"><a href="#toggle"><?php echo _("Show/Hide Tab")?></a></li>
		<li class="delete"><a href="#delete"><?php echo _("Delete")?></a></li>
	</ul>

	<ul id="myMenuTabProtected" class="contextMenu">
		<li class="addUser"><a href="#copyuser"><?php echo _("Copy this Tab to user")?></a></li>
		<li class="addEntity"><a href="#copyentity"><?php echo _("Copy this Tab to entity")?></a></li>
		<li class="addAll"><a href="#copyall"><?php echo _("Copy this Tab to all users")?></a></li>
		<li class="toggle"><a href="#toggle"><?php echo _("Show/Hide Tab")?></a></li>
	</ul>


</body>

</html>
<?php 
$db->close();
