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


Session::logcheck("configuration-menu", "PolicyActions");


function check_existing_name($conn, $name, $old_name, $data)
{

	if(!empty($name) && strcasecmp(trim($name),trim($old_name)))
	{
		if(Action::check_duplicated($conn, $name) == false)
		{
			if($data['status'] == 'OK')
			{
				$data['status'] = 'error';
			} 
			
			$data['data']['name'] =  'Error in Name field, it already exists in DB';		
		}
	}
	
	return $data;
}

$pro  = Session::is_pro();

list($db, $conn) = Ossim_db::get_conn_db();

$_GET["email_to"]  = str_replace (_("email;email;email") , "" , $_GET["email_to"]);
$_POST["email_to"] = str_replace (_("email;email;email") , "" , $_POST["email_to"]);

$action        = POST('action');
$action_id     = POST('id');
$action_type   = REQUEST('action_type');
$cond          = POST('cond');
$on_risk       = (POST('on_risk') == "") ? "0" : "1";
$name          = POST('action_name');
$descr         = POST('descr');
$email_from    = POST('email_from');
$email_to      = POST('email_to');
$email_subject = POST('email_subject');
$email_message = POST('email_message');
$exec_command  = POST('exec_command');
$tuser         = POST('transferred_user');
$tentity       = POST('transferred_entity');
$old_name      = REQUEST('old_name');

if($pro)
{
	$ctx = POST('ctx');
} 
else 
{
	$ctx = Session::get_default_ctx();
}

$v_exec_command = $v_email = "";

if ($action_type=="1") 
{
    $v_exec_commad = ",OSS_NULLABLE";
	$v_ticket_u    = ",OSS_NULLABLE";
	$v_ticket_e    = ",OSS_NULLABLE";
}
else if ($action_type=="2") 
{
    $v_email    = ",OSS_NULLABLE";
	$v_ticket_u = ",OSS_NULLABLE";
	$v_ticket_e = ",OSS_NULLABLE";
}
else 
{
    $v_exec_commad = ",OSS_NULLABLE";
    $v_email = ",OSS_NULLABLE";
    
	if(empty($tuser) && $pro)
	{
		$v_ticket_u = ",OSS_NULLABLE";
	}
	else 
	{
		$v_ticket_e = ",OSS_NULLABLE";
	}
}

if($pro)
{
	$validate = array (
		"action"        		=> array("validation"=>"OSS_ALPHA",				                                           "e_message" => 'illegal:' ._("Action")),
		"action_id"     		=> array("validation"=>"OSS_HEX, OSS_NULLABLE",                                            "e_message" => 'illegal:' ._("Action id")),
		"ctx"           		=> array("validation"=>"OSS_HEX",                                          				   "e_message" => 'illegal:' ._("Entity")),
		"action_name"	   		=> array("validation"=>"OSS_INPUT",         									           "e_message" => 'illegal:' ._("Name")),
		"action_type"   		=> array("validation"=>"OSS_DIGIT",                                                        "e_message" => 'illegal:' ._("Action type")),
		"cond"          		=> array("validation"=>"OSS_PUNC_EXT, OSS_ALPHA, OSS_DIGIT, OSS_NULLABLE, \"\>\<\"",       "e_message" => 'illegal:' ._("Condition")),
		"descr"         		=> array("validation"=>"OSS_ALPHA, OSS_PUNC, OSS_SCORE, OSS_AT, OSS_NL, '#'",              "e_message" => 'illegal:' ._("Description")),
		"email_from"   		 	=> array("validation"=>"OSS_MAIL_ADDR".$v_email,                                           "e_message" => 'illegal:' ._("Email from")),
		"email_to"      		=> array("validation"=>"OSS_MAIL_ADDR".$v_email,                                           "e_message" => 'illegal:' ._("Email to")),
		"email_subject"			=> array("validation"=>"OSS_ALPHA, OSS_PUNC, OSS_SCORE, OSS_AT, \"\>\<\"".$v_email,        "e_message" => 'illegal:' ._("Email subject")),
		"email_message" 		=> array("validation"=>"OSS_MAIL_MESSAGE".$v_email,                                        "e_message" => 'illegal:' ._("Email message")),
		"exec_command"  		=> array("validation"=>"OSS_ALPHA, OSS_PUNC, OSS_SCORE, OSS_AT,'\"\'\>\<'".$v_exec_commad, "e_message" => 'illegal:' ._("Exec command")),
		"transferred_user"  	=> array("validation"=>"OSS_USER".$v_ticket_u, 											   "e_message" => 'illegal:' ._("Ticker User")),
		"transferred_entity"  	=> array("validation"=>"OSS_HEX".$v_ticket_e, 											   "e_message" => 'illegal:' ._("Ticket Entity"))
		);
} 
else 
{
	$validate = array (
		"action"        		=> array("validation"=>"OSS_ALPHA",				                                          "e_message" => 'illegal:' ._("Action")),
		"action_id"     		=> array("validation"=>"OSS_HEX, OSS_NULLABLE",                                           "e_message" => 'illegal:' ._("Action id")),
		"action_type"   		=> array("validation"=>"OSS_DIGIT",                                                       "e_message" => 'illegal:' ._("Action type")),
		"action_name"	        => array("validation"=>"OSS_INPUT",         									          "e_message" => 'illegal:' ._("Name")),
		"cond"          		=> array("validation"=>"OSS_PUNC_EXT, OSS_ALPHA, OSS_DIGIT, OSS_NULLABLE, \"\>\<\"",      "e_message" => 'illegal:' ._("Condition")),
		"descr"         		=> array("validation"=>"OSS_ALPHA, OSS_PUNC, OSS_SCORE, OSS_AT, OSS_NL",                  "e_message" => 'illegal:' ._("Description")),
		"email_from"   		 	=> array("validation"=>"OSS_MAIL_ADDR".$v_email,                                          "e_message" => 'illegal:' ._("Email from")),
		"email_to"      		=> array("validation"=>"OSS_MAIL_ADDR".$v_email,                                          "e_message" => 'illegal:' ._("Email to")),
		"email_subject"			=> array("validation"=>"OSS_ALPHA, OSS_PUNC, OSS_SCORE, OSS_AT, \"\>\<\"".$v_email,       "e_message" => 'illegal:' ._("Email subject")),
		"email_message" 		=> array("validation"=>"OSS_MAIL_MESSAGE".$v_email,                                       "e_message" => 'illegal:' ._("Email message")),
		"exec_command"  		=> array("validation"=>"OSS_ALPHA, OSS_PUNC, OSS_SCORE, OSS_AT,'\"\'\>\<'".$v_exec_commad, "e_message" => 'illegal:' ._("Exec command")),
		"transferred_user"  	=> array("validation"=>"OSS_USER".$v_ticket_u, 											  "e_message" => 'illegal:' ._("Ticker User")),
		);

}

if (GET('ajax_validation') == true)
{
    $data['status'] = 'OK';
	
	$validation_errors = validate_form_fields('GET', $validate);
	
	if (is_array($validation_errors) && !empty($validation_errors))	
	{
		$data['status'] = 'error';
		$data['data']   = $validation_errors;
	}
	
	$data = check_existing_name($conn, $name, $old_name, $data);
	
	echo json_encode($data);	
	
	$db->close();
	exit();
}
else
{
    $validation_errors = validate_form_fields('POST', $validate);
	$data['status']    = 'OK';
	    
	if (POST('ajax_validation_all') == true)
    {
        if (is_array($validation_errors) && !empty($validation_errors))
		{
			$data['status'] = 'error';
			$data['data']   = $validation_errors;
		}
		else
		{
			$data['status'] = 'OK';
			$data['data']   = $validation_errors;
		}
		
		$data = check_existing_name($conn, $name, $old_name, $data);
		echo json_encode($data);
		
		$db->close();  
		exit();
    }
	else
	{
		if (is_array($validation_errors) && !empty($validation_errors))
		{
			$data['status'] = 'error';
			$data['data']   = $validation_errors;
		}
		
		$data = check_existing_name($conn, $name, $old_name, $data);
	}
}


if ($data['status'] == 'error')
{
    if($action_id!="")      $_SESSION['_actions']['action_id']     = $action_id;
    if($action_type!="")    $_SESSION['_actions']['action_type']   = $action_type;
	if($name!="")           $_SESSION['_actions']['name']          = $name;
    if($descr!="")          $_SESSION['_actions']['descr']         = $descr;
    if($cond!="")           $_SESSION['_actions']['cond']          = $cond;
    if($on_risk!="")        $_SESSION['_actions']['on_risk']       = $on_risk;
    if($email_from!="")     $_SESSION['_actions']['email_from']    = $email_from;
    if($email_to!="")       $_SESSION['_actions']['email_to']      = $email_to;
    if($email_subject!="")  $_SESSION['_actions']['email_subject'] = $email_subject;
    if($email_message!="")  $_SESSION['_actions']['email_message'] = $email_message;
    if($exec_command!="")   $_SESSION['_actions']['exec_command']  = $exec_command;
}

$in_charge = ($tuser!="") ? $tuser : $tentity;

if ($in_charge != "")
{ 
	$descr .= "##@##".$in_charge;
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title> <?php echo gettext("OSSIM Framework"); ?> </title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
</head>
<body>

<?php

if ($error == true)
{
    $txt_error = "<div>"._("We Found the following errors").":</div><div style='padding:10px;'>".implode("<br/>", $message_error)."</div>";
    Util::print_error($txt_error);	

    $url = (!empty($action_id)) ? "actionform.php" : "action.php";
	Util::make_form("POST", $url);
	
	$db->close();  
    exit();
}

if ($action == 'new' || $action == 'edit')
{
    $cond = html_entity_decode($cond);

	$txt_end = _("Action successfully updated");
	
	if ($action_type == "exec" && !Session::am_i_admin()) 
	{
		$txt_end = '<b>'._("Only global admins can add or modify 'exec' actions").'</b>';
	} 
	else 
	{
	    if ($action == 'new') // New action
		{ 
            if ($action_type == '1')
            {
                Action::insertEmail($conn, $ctx, $name, $action_type, $cond, $on_risk, $descr, $email_from, $email_to, $email_subject, $email_message);
            }
            else if ($action_type == '2')
            {
                Action::insertExec($conn, $ctx, $name, $action_type, $cond, $on_risk, $descr, $exec_command);
            }
            else
            {
                Action::insert($conn, $ctx, $name, $action_type, $cond, $on_risk, $descr);
            }
	    }
	    else if($action == 'edit') // Update action 
		{ 
	        if ($action_type == '1') 
	        {
	            Action::updateEmail($conn, $action_id, $ctx, $name, $action_type, $cond, $on_risk, $descr, $email_from, $email_to, $email_subject, $email_message);
	        }
	        else if ($action_type == '2')
	        {
	            Action::updateExec($conn, $action_id, $ctx, $name, $action_type, $cond, $on_risk, $descr, $exec_command);
	        }
            else if ($action_type == '3')
            {
                Action::update($conn, $action_id , $ctx, $name, $action_type, $cond, $on_risk, $descr);
            }
	    }
	}
	
    
    if (isset($_SESSION['_actions']))
    {
        unset($_SESSION['_actions']);
	}
	
	echo "<p>".$txt_end."</p>";
	
	?>
	<script type='text/javascript'>
	
    	if (!parent.is_lightbox_loaded(window.name))
        {
            document.location.href="action.php";
        }
        else
        {
            document.location.href="actionform.php?update=1";
        }
        
    </script>

<?php
}

$db->close();    
?>
	
</body>
</html>