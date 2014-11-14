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

Session::logcheck('configuration-menu', 'ConfigurationUsers');

require_once 'languages.inc';

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>";

$db     = new ossim_db();
$conn   = $db->connect();

$myself = Session::get_session_user();

$order = GET('sortname');
$order = (empty($order)) ? POST('sortname') : $order;


$search = GET('query');
if (empty($search))
{ 
	$search = POST('query');
}
	
$field = POST('qtype');


$page = (!empty($_POST['page'])) ? POST('page') : 1;
$rp   = (!empty($_POST['rp'])  ) ? POST('rp')   : 20;
	
ossim_valid($page, OSS_DIGIT, OSS_NULLABLE,                          'illegal:' . _('Page'));
ossim_valid($rp, OSS_DIGIT, OSS_NULLABLE,                            'illegal:' . _('Rp'));
ossim_valid($field, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE,               'illegal:' . _('Field'));
ossim_valid($order, OSS_NULLABLE, OSS_SCORE, OSS_ALPHA, OSS_DIGIT,   'illegal:' . _('Order'));


if (!empty($search))
{
	$search = (mb_detect_encoding($search." ",'UTF-8,ISO-8859-1') == 'UTF-8') ? Util::utf8entities($search) : $search;
	
	if ($field == 'login')
	{
		ossim_valid($search, OSS_USER, 'illegal:' . _("Login"));
		
		$search = escape_sql($search, $conn);
		$where  = "WHERE $field like '%$search%'";
	}
	else
	{	
		ossim_set_error(_("Error in the 'Quick Search Field' field (missing required field)"));
	}
}


if (ossim_error()) 
{
	$db->close();
	echo "<rows>\n<page>1</page>\n<total>0</total>\n</rows>\n";
	exit();
}

if (!empty($order)) 
{
	$order .= (POST('sortorder') == 'asc') ? '' : ' desc';
}
else
{
	$order = 'name';
}


$start = (($page - 1) * $rp);
$limit = "LIMIT $start, $rp";


$xml  = "";

$user_list = Session::get_list($conn, $where, "ORDER BY $order $limit");

if ($user_list[0]) 
{
    $total = $user_list[0]->get_foundrows();
    if ($total == 0) 
    {
		$total = count($user_list);
	}
} 
else
{ 
	$total = 0;
}
	

$xml.= "<rows>\n";
$xml.= "<page>$page</page>\n";
$xml.= "<total>$total</total>\n";

foreach($user_list as $user) 
{
    $login = $user->get_login();
    
    if ($login == AV_DEFAULT_ADMIN && $myself != AV_DEFAULT_ADMIN)
    {
        continue;
    }
    
    $icon = '';
	if ($user->get_is_admin() || $login == AV_DEFAULT_ADMIN) 
	{
		$icon = ' <img src="../pixmaps/user-business.png" align="absmiddle" alt="'._('Admin').'"/>';
	}
	elseif (Session::is_pro() && Acl::is_proadmin($conn, $login))
	{
		$icon = ' <img src="../pixmaps/user-gadmin.png" align="absmiddle" alt="'._('Entity admin').'"/>';
	}
		
    $name  = "&nbsp;<a style='font-weight:bold;' href=\"./user_form.php?login=".$login."\">".$user->get_name()."</a>&nbsp;";
    $email = $user->get_email();
    
	if (Session::is_pro()) 
	{
    	$entities  = $user->get_ctx();
    	$companies = array();
    	foreach ($entities as $entity_id)
		{
			$parents   = Acl::get_entity_parents($conn, $entity_id);
			$parents   = array_reverse($parents);
			$parents[] = $entity_id;

			$ent_name  = array();
			foreach ($parents as $eid) 
			{
				$ent_name[] = Acl::get_entity_name($conn, $eid);
			}
			
            $ent_name    = implode('<strong> / </strong>', $ent_name);
            $companies[] = $ent_name;
    	}

    	$companies = implode(',<br>', $companies);
    } 
	else 
	{
	    $company    = utf8_encode($user->get_company());
	    $department = utf8_encode($user->get_department());
	    $companies  = ($department != '' && $company != '') ? $company.' - '.$department : $company;
    }
	
	$last_logon_try = $user->get_last_logon();
	
	if (empty($last_logon_try) || $last_logon_try == '0000-00-00 00:00:00') 
	{
		$last_logon_try = '-';
	}
	$creation_date = $conn->GetOne("SELECT date FROM log_action WHERE info = 'Configuration - User ".$login." created' ORDER BY date DESC");
	
	if (empty($creation_date) || !preg_match("/\d+\-\d+\-\d+\s\d+:\d+:\d+/",$creation_date)) 
	{
		$creation_date = '-';
	}
    
    $xml .= "<row id='".$login."'>";
	$xml .= "<cell><![CDATA[" . $login ."&nbsp;". $icon . "]]></cell>";
	$xml .= "<cell><![CDATA[" . $name . "]]></cell>";
	$xml .= "<cell><![CDATA[" . $email . "]]></cell>";
	$xml .= "<cell><![CDATA[" . $companies . "]]></cell>";
	
	if (Session::am_i_admin() && $login != AV_DEFAULT_ADMIN) 
	{
		$enabled  = $user->get_enabled();
		$img      = ($enabled > 0) ? 'tick.png' : 'cross.png';
		$img_text = ($enabled > 0) ? _('Click to disable') : _('Click to enable');		
		
		$xml .= '<cell><![CDATA[<form id="f_users_cs_'.$login.'" name="f_users_cs_'.$login.'" action="users.php" method="POST">
		    <input type="hidden" name="action" value="change_status"/>
		    <input type="hidden" name="user_id" value="'.$login.'"/>
		     <a class="lnk_cs" id="lnk_cs_'.$login.'" href="javascript:void(0);"><img src="../pixmaps/'.$img.'" border="0" alt="'.$img_text.'" title="'.$img_text.'"/></a>
		</form>]]></cell>';
	} 
	elseif (Session::am_i_admin()) 
	{
		$xml .= "<cell><![CDATA[]]></cell>";
	}
	
	
	$language = $user->get_language();
	$xml .= "<cell><![CDATA[<form id='f_users_cl_$login' name='f_users_cl_$login' action='users.php' method='POST'>";
	
	$xml .= "<select class='s_cl' name='language' id='s_cl_$login'>";
	
	foreach($languages['type'] as $option_value => $option_text) 
	{
		$xml .= "<option ";
		
		if ($language == $option_value) 
		{
			$xml .= " selected = 'selected'";
		}
		
		$xml .= "value=\"$option_value\">$option_text</option>";
	}
	
	$xml .= "</select>";
	$xml .= "<input type='hidden' name='action' value='change_language'/>";
	$xml .= "<input type='hidden' name='user_id' value='$login'/>";
	$xml .= "</form>]]></cell>";
	
	
	$xml .= "<cell><![CDATA[" . $creation_date . "]]></cell>";
	$xml .= "<cell><![CDATA[" . $last_logon_try. "]]></cell>";
			
    $xml.= "</row>\n";
}

$xml.= "</rows>\n";
echo $xml;
$db->close();
?>