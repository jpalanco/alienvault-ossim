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


Session::logcheck("configuration-menu", "PluginGroups");


$db   = new ossim_db();
$conn = $db->connect();

$plugin_list = Plugin::get_list($conn, "ORDER BY name");


function get_checked_plugins($arr) 
{
	$imported_plugins = array();
	// get checkbox values with format psidID_SID
	foreach ($arr as $k => $v) if (preg_match("/psid(\d+)_(\d+)/",$k,$found) && $v==1) 
	{
		$imported_plugins[$found[1]][] = $found[2];
	}
	
	return $imported_plugins;
}
/*
* Validates the POST data: name, description, plugins and SIDs
*
* @return Processed array($name, $description, array(plug_id => sid string))
*/
function validate_post_params($conn, $name, $descr, $sids, $imported_sids) 
{
    $vals = array(
        'name' => array(
            OSS_INPUT,
            'illegal:' . _("Name")
        ) ,
        'descr' => array(
            OSS_TEXT,
            OSS_NULLABLE,
            'illegal:' . _("Description")
        ) ,
    );
    
    
    ossim_valid($name, $vals['name']);
    ossim_valid($descr, $vals['descr']);
    $plugins = array();
    $sids = is_array($sids) ? $sids : array();

    if (intval(POST('pluginid')) > 0) 
    {
        $sids[POST('pluginid')] = "0";
    }
    
    foreach($sids as $plugin => $sids_str) 
    {
        if ($sids_str !== '') 
        {
            list($valid, $data) = Plugin_sid::validate_sids_str($sids_str);
            
            if (!$valid) 
            {
                ossim_set_error(_("Error for data source ") . $plugin . ': ' . $data);
                break;
            }
            
            if ($sids_str == "ANY") 
			{
				$sids_str = "0";
			}
			else
			{
				$aux      = count(explode(',', $sids_str));
				$total    = Plugin_sid::get_sidscount_by_id($conn,$plugin);
				$sids_str = ($aux == $total)? "0" : $sids_str;
			}

            $plugins[$plugin] = $sids_str;			
        }
    }

    if (!count($plugins) && !count($imported_sids)) 
    {
        ossim_set_error(_("No Data Sources or Event Types selected"));
    }
    
    if (ossim_error()) 
    {
        die(ossim_error());
    }
    return array(
        $name,
        $descr,
        $plugins
    );
}

if (GET('interface') && GET('method')) 
{
    if (GET('method') == "deactivate" && GET('pid')) 
    {
        unset($_SESSION["pid" . GET('pid') ]);
        //print "Unset ".GET('pid')."\n";
        
    } 
    else 
    {
        list($valid, $data) = Plugin_sid::validate_sids_str($_GET['sids_str']);
        
        if (!$valid) 
        {
            echo $data;
        } 
        elseif (GET('pid')) 
        {
            $_SESSION["pid" . GET('pid') ] = $_GET['sids_str'];
        }
    }
    exit;
}

$db   = new ossim_db();
$conn = $db->connect();
//
// Insert new
//
if (GET('action') == 'new') 
{
    $imported_plugins = get_checked_plugins($_POST);
    
    list($name, $descr, $plugins) = validate_post_params($conn, POST('name') , POST('descr') , POST('sids'), $imported_plugins);
    // Insert section
    //
    $group_id = Util::uuid();
    
    Plugin_group::insert($conn, $group_id, $name, $descr, $plugins, $imported_plugins);
        
    header("Location: modifyplugingroupsform.php?action=edit&id=$group_id");
    exit;
    //
    // Edit group
    //
    
} 
elseif (GET('action') == 'edit') 
{
    //print_r(POST('sids'));
    //print_r($_SESSION);
    $imported_plugins = get_checked_plugins($_POST);
    list($name, $descr, $plugins) = validate_post_params($conn, POST('name') , POST('descr') , POST('sids'), $imported_plugins);
        
    $group_id = GET('id');
    ossim_valid($group_id, OSS_HEX, 'illegal:ID');
    
    if (ossim_error()) 
    {
        die(ossim_error());
    }
    
    Plugin_group::edit($conn, $group_id, $name, $descr, $plugins, $imported_plugins);
    
    if (intval(POST('pluginid')) > 0) 
    {
        header("Location: modifyplugingroupsform.php?action=edit&id=$group_id");
        exit;
    }
    
    if (intval(POST('redirec')) == 1) 
    {
        header("Location: modifyplugingroupsform.php?action=edit&id=$group_id");
        exit;
    }
    //
    // Delete group
    //
    
} 
elseif (GET('action') == 'delete') 
{
    $group_id = GET('id');
    
    ossim_valid($group_id, OSS_HEX, 'illegal:ID');
    
    if (ossim_error()) 
    {
        die(ossim_error());
    }
    
	if (Plugin_group::can_delete($conn, $group_id)) 
	{
		Plugin_group::delete($conn, $group_id);
	}
	else 
	{ ?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml">
		<head>
		
		  <title> <?php echo _("OSSIM Framework") ?> </title>
		  
		  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
		  <meta HTTP-EQUIV="Pragma" CONTENT="no-cache"/>
		  <meta http-equiv="X-UA-Compatible" content="IE=7"/>
		  
		  <link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
		  
		</head>
		
		<body>
       
    		<table align="center" style='margin-top:60px;'>
                <tr>
                    <td class="nobborder" style="text-align:center">
                        <?php echo _("Sorry, cannot delete this Plugin Group because it belongs to a policy") ?>
                    </td>
                </tr>
                <tr>
                    <td class="nobborder" style="text-align:center;padding-top:30px">
                        <button type="button" onclick="document.location.href='plugingroups.php'"><?php echo _('Back')?></button>
                    </td>
                </tr>
    		</table>
    		
		</body>
		
		</html>
	<?php
	
	   exit; 
	}
}

header('Location: plugingroups.php'.($group_id!="" ? "?id=$group_id" : ""));

