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


require_once('av_init.php');

$conf    = $GLOBALS["CONF"];
$version = $conf->get_conf("ossim_server_version", FALSE);
$pro      = ( preg_match("/pro|demo/i",$version) ) ? true : false;

if (!Session::menu_perms("dashboard-menu", "BusinessProcessesEdit") ) 
{
	echo ossim_error(_("You don't have permissions to change the owner"));
	exit();
}


$id_map      = GET("map");
$entity      = GET("entity");
$user        = GET("user");


ossim_valid($id_map,	OSS_HEX,				'illegal:' . _("ID Map"));
ossim_valid($entity,	OSS_HEX, OSS_NULLABLE,	'illegal:' . _("Entity"));
ossim_valid($user,		OSS_USER, OSS_NULLABLE,	'illegal:' . _("User"));
	
if (ossim_error()) 
{
    die(ossim_error());
}

$db     = new ossim_db();
$dbconn = $db->connect();

$flag_close = false;

//If neither the entity, user nor name are modified, nothing will be done.
if($entity != '' || $user != '') 
{
	//Checking if the current map already exist in DB.
	$newuser = ( $entity != "" ) ? $entity : $user;
	$query  = "SELECT count(*) as count FROM risk_maps where map=UNHEX(?)";
	$params = array($id_map);
	$result = $dbconn->Execute($query, $params);
	$result_count = 0;
	if (!$result->EOF) 
	{
		$result_count = $result->fields['count'];
	}	
	//If it exists, Updating the info.
	if($result_count > 0)
	{
		//If we are doing an update and the permission is null, only the name will be updated.
		$query   = "UPDATE risk_maps SET perm=? WHERE map=UNHEX(?)";		
		$params  = array($newuser, $id_map);
		$result  = $dbconn->execute($query, $params);
		
		$_SESSION['map_new']['error'] = false;
		$_SESSION['map_new']['msg']   = _('Permission changed successfully');
	//If it does not exists, Creating the info.	
	} 
	else 
	{
		$_SESSION['map_new']['error'] = true;
		$_SESSION['map_new']['msg']   = _('It was impossible to change the permission');
	}
	
	$flag_close = true;
}

$query  = "SELECT perm FROM risk_maps where map=UNHEX(?)";
$params = array($id_map);
$result = $dbconn->Execute($query, $params);

if(!$result->EOF) 
{
	$user = $result->fields['perm'];
}


$users    = Session::get_users_to_assign($dbconn);
$entities = Session::get_entities_to_assign($dbconn);


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<script type="text/javascript" src="../js/jquery.min.js"></script>
	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	
	<script type="text/javascript">
		function switch_user(select) {
			if(select=='entity' && $('#entity').val()!=''){
				$('#user').val('');
			}
			else if (select=='user' && $('#user').val()!=''){
				$('#entity').val('');
			}
		}
		$(document).ready(function(){
				
			<?php if($flag_close){ ?>
				if(typeof(parent.GB_hide) == 'function'){
					parent.GB_hide();
				}
			<?php } ?>			
			
		});
	</script>
  
  	<style type='text/css'>
	
	table{		
		text-align:center;
	} 
	
		
	#update { 
		padding:20px 0px 0px 0px;
		border: none;
	}
	
	#user, #entity {width: 209px;}
		
	.format_user,.format_entity{
		margin-right: 3px;
		width: 50px;
		text-align: right;
	}
	
	.select_user,.select_entity{
		width: 260px;
	}
	
	
	.format_or{ 
		text-align:center; 
		border-bottom: none;
	}
	
	.owners {
		margin-bottom: 10px;		
	}
	
	.owners .action { width: 20px; text-align: center;}
	
	.owners td { padding-left: 10px;}
	
	.normal {text-align: left;}
	
	.right { 
		text-align: right !important;
		padding-right: 15px;
	}
	
	</style>
  
</head>
<body>

<form action="change_user.php" method="get">
	<input type="hidden" name="map" value="<?php echo $id_map ?>">	
		<table class="transparent" align="center" style="margin: 15px auto; padding-left: 32px;" width="95%">			
			<tr>
				<td class='format_user nobborder'><?php echo _("User:");?></td>	
				<td class='select_user nobborder'>				
					<select name="user" id="user" onchange="switch_user('user');return false;">
						
						<?php
													
						$num_users = 0;
						foreach( $users as $k => $v )
						{
							$login = $v->get_login();
							if($login == $user) {
								$options .= "<option selected='selected' value='".$login."'>$login</option>\n";
							} else {
								$options .= "<option value='".$login."'>$login</option>\n";
							}		
							
							$num_users++;
													
						}
						
						if ($num_users == 0)
							echo "<option value='' style='text-align:center !important;'>- "._("No users found")." -</option>";
						else
						{
							if ( !empty($entities) )
								echo "<option value='' style='text-align:center !important;'>- "._("Entity Selected")." -</option>\n";
							if (Session::am_i_admin()){
								if($user=='0'){
									echo "<option selected='selected' value='0'>All</option>\n";
								} else {
									echo "<option value='0'>All</option>\n";
								}
							
							}
							echo $options;
						}
												
						?>
					</select>
				</td>
			</tr>
					
			
			<?php 
			if (!empty($entities)) 
			{ 
    			
			     ?>
			     <tr><td class="format_or nobborder" colspan='2'><?php echo _("or");?></td></tr>
			
			<tr>
				<td class='format_entity nobborder'><?php echo _("Entity:");?></td>
				<td class='select_entity nobborder'>	
					<select name="entity" id="entity" onchange="switch_user('entity');return false;">
						<option value="" style='text-align:center !important;'>- <?php echo _("User Selected") ?> -</option>
						<?php
						foreach ( $entities as $k => $v ) 
						{					
							if($k == $user) {
								echo "<option value='$k' selected='selected'>$v</option>";
							} else {
								echo "<option value='$k'>$v</option>";
							}
						}
						?>
					</select>
				</td>
				<?php } ?>
			</tr>
								
			<tr><td id='update' colspan='2'><input type='submit' value='<?php echo _("Update")?>'/></td></tr>

		</table>		

</form>

</body>
</html>
<?php
$dbconn->disconnect(); 
?>
