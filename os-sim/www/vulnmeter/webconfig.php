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

ob_implicit_flush(true);

require_once 'av_init.php';
require_once 'classes/Util.inc';
require_once 'ossim_conf.inc';

Session::logcheck("environment-menu", "EventsVulnerabilitiesScan");

$db   = new ossim_db();
$conn = $db->connect();

$users     = Session::get_users_to_assign($conn);
$entities  = Session::get_entities_to_assign($conn);


//Get credential filter
$perms = "";
if( !Session::am_i_admin() ) 
{
    foreach($users as $k) {
        $c_perms[$k->get_login()] = $k->get_login();
    }
	
	foreach($users as $k => $v) {
        $c_perms[$k] = $k;
    }
    
    $perms = "AND login IN ('".implode("', '", $c_perms)."')";
}


$action           = (POST("action")!="") ? POST("action") : GET("action");

$name             = POST("name");
$credential_login = POST("credential_login");

$user             = POST("user");
$entity           = POST("entity");

$lsc_password     = POST("lsc_password");
$base             = POST("base");
$passphrase       = trim(POST("passphrase"));
$id               = GET("id");
$siteLogo         = POST("siteLogo");
$siteBranding     = POST("siteBranding");
$vit              = intval(POST("vulnerability_incident_threshold"));

$error_message    = "";

$uuid              = Util::get_encryption_key();
$show_notification = false;

ossim_valid($action, OSS_ALPHA, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("Action"));
if (ossim_error())
{
	die(ossim_error());
}

if( $action == "delete" && preg_match("/^[0-9a-f]{32}$/i", $id) ) // Delete credentials
{
    $query = "DELETE FROM user_config WHERE MD5(CONCAT(login, name)) = '$id' AND category= 'credentials' $perms";
    $conn->Execute($query);
}
else if( $action == "create" ) // Create credentials
{ 
    if ( !ossim_valid($name, OSS_ALPHA, OSS_SCORE, OSS_SPACE, OSS_DOT, OSS_AT, 'illegal:' . _("Name")) )
    {
        $error_message .= ossim_get_error_clean()."<br/>";
        ossim_clean_error();
    }
    
	if ( !ossim_valid($credential_login, OSS_CLOGIN, 'illegal:' . _("Login")) ) 
	{
        $error_message .= ossim_get_error_clean()."<br/>";
        ossim_clean_error();
    }
    
	if ( !ossim_valid($base, "key", "pass", 'illegal:' . _("Credential Type")) ) {
        $error_message .= ossim_get_error_clean()."<br/>";
        ossim_clean_error();
    }
	
	
	if ( $user != '0' && $user != '-1' )
	{
		if ( !ossim_valid($user, OSS_USER_2, 'illegal:' . _("User")) ) 
		{
			$error_message .= ossim_get_error_clean()."<br/>";
			ossim_clean_error();
		}
	}
			
	if ( $entity != '-1' )
	{
		if ( !ossim_valid($entity, OSS_HEX, 'illegal:' . _("Entity")) ) 
		{
			$error_message .= ossim_get_error_clean()."<br/>";
			ossim_clean_error();
		}
	}

	if ( $user == '-1' && $entity == '-1' )	{
		$error_message .= _("Error in the 'Available for' field (missing required field)")."<br/>";
	}
	    
	if( $base == "key" ) 
	{
        if ( !ossim_valid($passphrase, OSS_NULLABLE, OSS_TEXT, 'illegal:' . _("Passphrase")) ) 
		{
            $error_message .= ossim_get_error_clean()."<br/>";
            ossim_clean_error();
        }

        //Validate Private Key
        
        if($_FILES['private_key']['tmp_name'] == "") {
            $error_message .=_("Error in the 'Private key' field (missing required field)")."<br/>";
        }
        else 
		{
            $tmp_private_key = "/usr/share/ossim/uploads/".md5(microtime());
            						
			if ( @move_uploaded_file($_FILES['private_key']['tmp_name'], $tmp_private_key) !== false )
			{
                $_private_key_error = FALSE;
                
                if (@filesize($tmp_private_key)==0)
                {
                    $_private_key_error = TRUE;
                }
                else
                {
                    $file_arr = @file($tmp_private_key);
                    
                    foreach ($file_arr as $line)
                    {
                        if (!preg_match("/^\-+begin\s[a-zA-Z]+\sprivate\skey\-+$/i", $line) // BEGIN
                         && !preg_match("/^\-+end\s[a-zA-Z]+\sprivate\skey\-+$/i", $line) // END
                         && !preg_match("/^[a-zA-Z0-9\+\/\=]+$/", $line)) // Base64 string
                        {
                            $_private_key_error = TRUE;
                        }
                    }
                }
                
                if ($_private_key_error)
                {
                    $error_message .= _("A valid private key is required")."<br/>";
                    unlink($tmp_private_key);
                }
			}
			else{
				$error_message .= _("Private key was not uploaded. Check upload path and/or permissions")."<br/>";
			}
		}
    }
    else if ( $base == "pass" ) 
	{
        if ( !ossim_valid($lsc_password, OSS_PASSWORD, 'illegal:' . _("Password")) ) 
		{
            $error_message .= ossim_get_error_clean()."<br/>";
            ossim_clean_error();
        }
    }
    
    // Insert credential
    if( $error_message == "" ) 
	{
        $command = "";
        
        $doc = new DOMDocument('1.0', 'utf-8');
        // we want a nice output
        $doc->preserveWhiteSpace = FALSE;
        $doc->formatOutput = TRUE;
        
        $root = $doc->createElement('create_lsc_credential');
        $root = $doc->appendChild($root);
        
        $node = $doc->createElement('name', $name);
        $root->appendChild($node);
        
        $node = $doc->createElement('login', $credential_login);
        $root->appendChild($node);

        if ($base == 'key')
        {
            $private = '';

            $arr = @file($tmp_private_key);
            foreach ($arr as $line)
            {
                $private .= $line;
            }

            $key = $doc->createElement('key');
            $root->appendChild($key);

            if ($passphrase != '')
            {
                $node = $doc->createElement('phrase', $passphrase);
                $key->appendChild($node);
            }

            $node = $doc->createElement('private', trim($private));
            $key->appendChild($node);

            unlink($tmp_private_key);
        }
        else 
		{                        
            $node = $doc->createElement('password', str_replace('&', '&amp;', $lsc_password));
            $root->appendChild($node);
        }
        
        $command = $doc->saveXML();    
        $command = preg_replace('/<\?xml version=.*\?>/', '', $command);

		$c_user = ( $entity != '-1' ) ? $entity : $user;
		$c_user = ( $c_user == '-1' || ($c_user == '0' && !Session::am_i_admin()) )  ? Session::get_session_user() : $c_user;
		
		$params = array($c_user, "credentials", $name, $command);
        $sql    = "REPLACE INTO user_config (login, category, name, value) VALUES (?, ?, ?, HEX(AES_ENCRYPT(?,'".$uuid."')))";

               
        //error_log("\n".Session::get_session_user()." $name, $command\n", 3, "/tmp/debug.log");
		        
        if($conn->Execute ( $sql, $params ) === false) {
            $error_message = _('Error inserting credential: ') . $conn->ErrorMsg() . '<br/>';
        }
    }
}
else if ($action == "save_configuration") {
    
    if ( !ossim_valid($siteBranding, OSS_ALPHA, OSS_SCORE, OSS_SPACE, OSS_DOT, OSS_AT, 'illegal:' . _("Portal Branding")) )
    {
        $error_message .= ossim_get_error_clean()."<br/>";
        ossim_clean_error();
    }
    else 
	{
        $sql = "UPDATE vuln_settings SET settingValue=? WHERE settingName=?";

        $params = array($siteBranding, "siteBranding");
        
        if($conn->Execute ( $sql, $params ) === false) {
            $error_message = _('Error updating settings: ') . $conn->ErrorMsg() . '<br/>';
        }
    }
    
    // Allowed Site logo filename
    if ( !ossim_valid($siteLogo, OSS_FILENAME, 'illegal:' . _("Site header logo")) ) 
	{
        $error_message .= ossim_get_error_clean()."<br/>";
        ossim_clean_error();
    }
    // Allowed Site logo paths
    elseif (!preg_match('/www\/pixmaps/', realpath($siteLogo)) 
            && !preg_match('/www\/tmp\/headers/', realpath($siteLogo))
            && !preg_match('/ossim\/uploads/', realpath($siteLogo)))
    {
        $error_message .= _('Error updating settings: Site header logo not found');
    }
    else 
	{
	    // Site logo must be a valid image
	    $image_data = getimagesize($siteLogo);
	    list($file_type, $image_type) = explode("/", $image_data['mime']);
	    
	    if ($file_type == 'image')
	    {
            $sql = "UPDATE vuln_settings SET settingValue=? WHERE settingName=?";
    
            $params = array($siteLogo, "siteLogo");
            
            if($conn->Execute ( $sql, $params ) === false) {
                $error_message = _('Error updating settings: ') . $conn->ErrorMsg() . '<br/>';
            }
	    }
	    else
	    {
	        $error_message .= _('Error updating settings: Site header logo is not a valid image');
	    }
    }

    $sql = "UPDATE config SET value=? WHERE conf='vulnerability_incident_threshold'";

    $params = array($vit, "vulnerability_incident_threshold");
        
    if($conn->Execute ( $sql, $params ) === false) {
        $error_message = _('Error updating settings: ') . $conn->ErrorMsg() . '<br/>';
    }

}

$list = Vulnerabilities::get_credentials($conn);

$_SESSION['openvas_update_last_lines'] = array();

//Load Settings Values
$siteBranding = $conn->GetOne("SELECT settingValue FROM vuln_settings WHERE settingName='siteBranding'");
$siteLogo     = $conn->GetOne("SELECT settingValue FROM vuln_settings WHERE settingName='siteLogo'");
$vit          = $conn->GetOne("SELECT value FROM config WHERE conf='vulnerability_incident_threshold'");

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title> <?php echo gettext("Vulnmeter"); ?> </title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    <script type="text/javascript" src="../js/jquery.min.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.min.js"></script>
    <script type="text/javascript" src="../js/greybox.js"></script>
    <script type="text/javascript" src="../js/notification.js"></script>
	<script type="text/javascript" src="../js/utils.js"></script>
    <script type="text/javascript" src="../js/token.js"></script>
    <script type="text/javascript">        
        function confirmDelete(key){
            var ans = confirm("Are you sure you want to delete this credential?");
            if (ans) document.location.href='webconfig.php?action=delete&id='+key;
        }
		
        function checking() {
            $('#loading_image').show();
            $('#loading_message').html('<?=_("Checking Scanner...")?>');
        }
		
		function switch_user(select) {
			
			if(select=='entity' && $('#entity').val()!='-1'){
				$('#user').val('-1');
			}
			else if (select=='user' && $('#user').val()!='-1'){
				$('#entity').val('-1');
			}
			
			if($('#entity').val()=='-1' && $('#user').val()=='-1') { 
				$('#user').val('0'); 
			}
		}
		
		$(document).ready(function(){
    		
            GB_TYPE = 'w';
            
			$("a.greybox").click(function(){
                dest = $(this).attr('href');
                GB_show_nohide("<?php echo _("Check credential")?>", dest, 400, 850);
                return false;
            });
			
			$('.base').change(function(){
				var s_value = $('.base:checked').val();
							
				if ( s_value == 'pass' ) 
				{
					$('#private_key').addClass('disabled').attr('disabled', 'disabled');
					$('#public_key').addClass('disabled').attr('disabled', 'disabled');
					$('#lsc_password').removeClass('disabled').removeAttr('disabled');
					$('.l_lsc_password').removeClass('opacity_6');
					$('.l_key').addClass('opacity_6');
				}
				else
				{
					$('#lsc_password').addClass('disabled').attr('disabled', 'disabled');
					$('#lsc_password').val('');
					$('#private_key').removeClass('disabled').removeAttr('disabled');
					$('#public_key').removeClass('disabled').removeAttr('disabled');
					$('.l_key').removeClass('opacity_6');
					$('.l_lsc_password').addClass('opacity_6');
				}
			});
			
			$('.base').trigger('change');
		});
		
		
    </script>
    
	<style type='text/css'>
        
        body {
            overflow-y: auto !important;
        }
        		
		#maintable {
            margin: 9px auto 0px auto;
        }
		
		#t_n_credentials{
			margin: auto;
			width: 100%;
		}
		
		.m_top
		{
    		margin: 15px 0px 0px 0px;
		}
		
		.itd {
            text-align: left;
            padding: 8px 0px 10px 0px;
        }
        
		.tinput {
            height: 20px !important;
            padding: 0px 0px 0px 5px !important;
        }
        
		.thheight {
            height: 22px !important;
        }
        
        .gborder {
            border-top:0px;
        } 
		
        pre {
            white-space: pre-wrap;       /* css-3 */
            white-space: -moz-pre-wrap;  /* Mozilla, since 1999 */
            white-space: -pre-wrap;      /* Opera 4-6 */
            white-space: -o-pre-wrap;    /* Opera 7 */
            word-wrap: break-word;       /* Internet Explorer 5.5+ */
            font-family: sans-serif,courier,arial;
            font-size:9px;
            margin:10px 10px 20px 10px;
            background-color:#f2f2f2;
            border:1px dashed gray;
            padding:5px 15px;
            border-radius: 10px;
            -moz-border-radius: 10px;
            -webkit-border-radius: 10px;
        }
        
        #update_notification
        {
            text-align: center;
            width:90%;
            margin:0 auto;
        }
        
    </style>
</head>
<body>
    <?php
        
    if( $action == "create" && $error_message == "") 
	{
        $show_notification = true;
        $message           = _("Credential created successfully");
        $message_type      = "nf_success";
        
        $name             = "";
        $credential_login = "";
        $passphrase       = "";
        $base             = "";
    }
    else if( $action == "save_configuration" && $error_message == "")
	{
        $show_notification = true;
        $message           = _("Configuration successfully updated");
        $message_type      = "nf_success";
        
        $name             = "";
        $credential_login = "";
        $passphrase       = "";
        $base             = "";
    }
    else if( $error_message != "" ) 
	{
		$show_notification = true;
        $message          .= "<div style='text-align: left;'>". _("The following errors occurred:")."</div>
							  <div style='padding-left: 10px; text-align: left;'>".$error_message."</div>";
        $message_type      = 'nf_error';
    }
    
    if( $show_notification )
	{
        $config_nt = array(
            'content' => $message,
            'options' => array (
                'type'          => $message_type,
                'cancel_button' => false
            ),
            'style'   => 'width: 50%; margin: 20px auto; text-align: center;'
        ); 
                        
        $nt = new Notification('nt_1', $config_nt);
        $nt->show();
    }
    
    ?>
    <div id='update_notification'></div>
    
    <table width="<?php echo ( (Vulnerabilities::scanner_type() == "omp") ? "90" : "50" ); ?>%" class="transparent" cellspacing="0" cellpadding="0" id="maintable">
        <tr>
        <?php
        if( Vulnerabilities::scanner_type() == "omp" ) 
		{
			?>
            <td width="40%">
                				                
				<table class="transparent w100" cellspacing="0" cellpadding="0">
                    <tr><td class="headerpr_no_bborder"><?php echo _("Credentials");?></td></tr>
                </table>
				<table class="table_list">
					<?php
					if ( count($list) == 0 ) 
					{
						?>
						<tr><td><?php echo _("Credentials not found");?></td></tr>
						<?php
					}
					else 
					{
						?>
						<tr>
							<th><?php echo _("Name");?></th>
							<th><?php echo _("Type"); ?></th>
							<th><?php echo _("Available for"); ?></th>
							<th><?php echo _("Action"); ?></th>
						</tr>
						
						<?php								
						
						foreach($list as $item) 
						{ 																				
							if ( $item["login"] == '0' ){
								$available_for = _("All");
							} 
							elseif ( valid_hex32($item["login"]) ){
								$available_for = Session::get_entity_name($conn, $item["login"]);
							}
							else{
								$available_for = $item["login"];
							}
							
							$credential_id = md5(trim($item["login"]).trim($item["name"]));
							
							?>
							<tr>
								<td id="credential_name_<?php echo $credential_id ?>"><?php echo $item["name"];?></td>
								<td id="credential_type_<?php echo $credential_id ?>"><?php echo $item["type"];?></td>
								<td id="credential_available_for_<?php echo $credential_id ?>"><?php echo $available_for;?></td>
								<td>
									<a  id="check_credential_<?php echo $credential_id ?>" class="greybox" style="text-decoration:none" href="check_credential.php?credential=<?php echo urlencode($item["name"].";".$item["login"]) ?>">
										<img src="./images/checklist_pencil.gif" alt="<?php echo _("Check Credential");?>" title="<?php echo _("Check Credential"); ?>" />
									</a>
									<?php
									if ( $item["login"] != '0' || ($item["login"] == '0' && Session::am_i_admin()) )
									{
										?>
										<a  id="delete_credential_<?php echo $credential_id ?>" href="javascript:;" style="margin-left:5px;text-decoration:none" onclick="confirmDelete('<?php echo $credential_id ?>')">
											<img src="./images/delete.gif" alt="<?php echo _("Delete Credential");?>" title="<?php echo _("Delete Credential");?>" />
										</a>
										<?php
									}
									else
									{
										?>
										<img style="margin-left:5px;text-decoration:none" src="./images/delete.gif" class='disabled' alt="<?php echo _("Delete Credential");?>" title="<?php echo _("Delete Credential");?>" />
										<?php
									}
									?>
								</td>
							</tr>
							<?php 
						}
					}
					?>
                </table>
    
                <table class="w100 transparent m_top" cellpadding="0" cellspacing="0">
                    <tr><td class="headerpr_no_bborder"><?php echo _("New credential");?></td></tr>
                </table>
				
                <form method="post" enctype="multipart/form-data">
                    <input name="action" type="hidden" value="create">
                    
					<table id="t_n_credentials" cellspacing='5' class="w100">
                        												
						<tr>
							<th><?php echo _("Name"); ?></th>
							<td style="text-align:left;">
								<input type="text" class="tinput" name="name" value="<?php echo Util::htmlentities($name) ?>"/>
							</td>
						</tr>
                        
						<tr>
							<th><?php echo _("Available for")?></th>
							<td style="text-align:left;">
								<table class="noborder">
									<tr>
										<td class="nobborder"><?php echo _("User:");?></td>
										
										<td class="nobborder">
											<select name="user" id="user" onchange="switch_user('user');return false;" style="width:150px">
												<option value='-1'>- <?php echo _("Select one user")?> -</option>
												
												<?php
												if ( Session::am_i_admin() ){
													?>
													<option value='0'><?php echo _("All")?></option>
													<?php
												} 
												
												$s_user = ( $user == '' ) ? Session::get_session_user() : $user;
																																																	
												foreach($users as $k => $v )
												{
													$login    = $v->get_login();
													$selected = ( $login == $s_user ) ? 'selected="selected"' : "";
													?>
													<option value='<?php echo $login?>' <?php echo $selected?>><?php echo $login?></option>
													<?php
												}
												?>
											</select>
										</td>
																													
										<td class="nobborder"><?php echo _("OR");?></td>
										<td class="nobborder"><?php echo _("Entity:");?></td>
										
										<td class="nobborder">
											<select name="entity" id="entity" onchange="switch_user('entity');return false;" style="width:160px">
												<option value='-1'>- <?php echo _("Select one entity")?> -</option>
												<?php																																								
												foreach($entities as $k => $v )
												{
													$selected = ( $k == $entity ) ? 'selected="selected"' : "";
													?>
													<option value='<?php echo $k?>' <?php echo $selected?>><?php echo $v?></option>
													<?php
												}
												?>
											</select>
										</td>
									</tr>
								</table>
							</td>
						</tr>
						
						<tr>
							<th><?php echo _("Login"); ?></th>
							<td style="text-align:left;">
								<input type="text" class="tinput" name="credential_login" value="<?php echo Util::htmlentities($credential_login); ?>"/>
							</td>
						</tr>
																
						<tr>
							<td>&nbsp;</td>
                            <td>
                                <table class="transparent" cellspacing="5">
                                    <tr>
                                        <td><input type="radio" <?php echo (($base=="" || $base=="pass") ? "checked=\"checked\"" : "");?> value="pass" class='base' name="base"/></td>
										<th class='l_lsc_password'><?php echo _("Password"); ?></th>
										<td style="text-align:left;"><input class="tinput" type="password" autocomplete="off" id="lsc_password" name="lsc_password"/></td>
                                    </tr>
                                    
									<tr style="margin-top:15px;">
                                        <td><input type="radio" <?php echo (($base=="key") ? "checked=\"checked\"" : "");?> value="key" class='base' name="base"/></td>
                                        <th class='l_key'><?php echo _("Key pair"); ?></th>
                                        <td style="text-align:left;">&nbsp;</td>
                                    </tr>
                                    
									<tr>
                                        <td width="45"></td>
                                        <th class=" l_key"><?php echo _("Private key");?></th>
                                        <td><input type="file" id='private_key' size="35" name="private_key"/></td>
                                    </tr>
                                    
									<!--<tr>
                                        <td width="45"></td>
                                        <th class="thheight"><strong><?php echo _("Passphrase"); ?></th>
                                        <td style="text-align:left;"><input  class="tinput" type="password" autocomplete="off" name="passphrase"/></td>
                                    </tr>-->
                                    <tr>
                                        <td colspan="4" style="text-align:left;padding:5px 0px 0px 48px;"> <?php echo _("* The Passphrase must be empty"); ?></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="padding:15px 0px 10px 0px;"> <input type="submit" value="<?php echo _("Create Credential") ?>"></td>
                        </tr>
                    </table>
                </form>
            </td>
            
			<td width="10%">&nbsp;</td>
            <?php
        }
        // Only admin
        if( Session::am_i_admin() )
        {        
        ?>
            <td <?php echo ( (Vulnerabilities::scanner_type() == "omp") ? "width=\"55%\"" : "" ); ?> valign="top">
                				                
				<form method='post' action='webconfig.php'>
					<input type='hidden' name='action' value='save_configuration'>
					
					<table class='w100 transparent' cellspacing="0" cellpadding="0">
						<tr><td class="headerpr_no_bborder"><?php echo _("Settings");?></td></tr>
				    </table>
					<table class="w100">	 
						<tr>
							<td><?php echo _("Site header logo:") ?></td>
							<td class='itd'><input class="tinput" type="text" size="30" value="<?php echo $siteLogo; ?>" name="siteLogo"/></td>
						</tr>
						
						<tr>
							<td><?php echo _("Portal Branding:") ?></td>
							<td class='itd'><input class="tinput" type="text" size="30" value="<?php echo $siteBranding; ?>" name="siteBranding"/></td>
						</tr>
						
						<tr>
							<td><?php echo _("Vulnerability Ticket Threshold:") ?></td>
							
							<td class='itd'>
								<?php $threshold_values = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10); ?>
								<select name="vulnerability_incident_threshold">
									<?php 
									foreach ($threshold_values as $value) 
									{
										?>
										<option <?php echo (($value==$vit) ? " selected='selected'": "")?>><?php echo $value ?></option>
										<?php
									}
									?>
								</select>
							</td>
						</tr>
						
						<tr>
							<td colspan="2" style="padding:10px 0px 10px 0px;">
								<input type='submit' name='submit' value='<?php echo _("Update") ?>'/>
							</td>
						</tr>
					</table>
                </form>
            </td>
            <?php                
			}
		   ?>
        </tr>
    </table>
    <br/><br/><br/>
</body>
</html>
<?php
$db->close($conn);

?>
