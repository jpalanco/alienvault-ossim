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

require_once '../widget_common.php';


//Checking if we have permissions to go through this section
Session::logcheck("dashboard-menu", "ControlPanelExecutive");
//End of permissions		
		
		
//Setting DB connection			
$db    = new ossim_db(TRUE);
$conn  = $db->connect();

//Getting the current user
$user  = Session::get_session_user();

//This is the type of security widget.
$type  = GET("type");
//ID of the widget
$id    = GET("id");


//Validation
ossim_valid($type,	OSS_TEXT, 					'illegal:' . _("type"));
ossim_valid($id, 	OSS_DIGIT, OSS_NULLABLE, 	'illegal:' . _("Widget ID"));

if (ossim_error()) 
{
    die(ossim_error());
}
//End of validation

//Array that contains the widget's general info
$winfo		= array();
//Array that contains the info about the widget's representation, this is: chart info, tag cloud info, etc.
$chart_info = array();

//If the ID is empty it means that we are in the wizard previsualization. We get all the info from the GET parameters.
if (!isset($id) || empty($id))
{

	$winfo['height'] = GET("height");					//Height of the widget
	$winfo['wtype']  = GET("wtype");					//Type of widget: chart, tag_cloud, etc.
	$winfo['asset']  = GET("asset");					//Assets implicated in the widget
	$chart_info      = unserialize(GET("value")); 		//Params of the widget representation, this is: type of chart, legend params, etc.

} 
else  //If the ID is not empty, we are in the normal case; loading the widget from the dashboard. In this case we get the info from the DB.
{ 

	//Getting the widget's info from DB
	$winfo      = get_widget_data($conn, $id);		//Check it out in widget_common.php
	$chart_info = $winfo['params'];					//Params of the widget representation, this is: type of chart, legend params, etc.
	
}

//Validation
ossim_valid($winfo['wtype'], 	OSS_TEXT, 								'illegal:' . _("Type"));
ossim_valid($winfo['height'],	OSS_DIGIT, 								'illegal:' . _("Widget ID"));
ossim_valid($winfo['asset'], 	OSS_HEX,OSS_SCORE,OSS_ALPHA,OSS_USER, 	'illegal:' . _("Asset/User/Entity"));

if (is_array($chart_info) && !empty($chart_info))
{
	$validation = get_array_validation();
	
	foreach($chart_info as $key=>$val)
	{
    	if ($validation[$key] == '')
    	{
        	continue;
    	}
    	
		eval("ossim_valid(\"\$val\", ".$validation[$key].", 'illegal:" . _($key)."');");
	}	
}

if (ossim_error()) 
{
	die(ossim_error());
}
//End of validation.
	
	

//Variables to store the chart information
$data  = array();	//The widget's data itself.
$label = array();	//Widget's label such as legend in charts, titles in tag clouds, etc...
$links = array();	//Links of each element of the widget.


/*
*
*	The code below is copied from /panel and will have to be adapted to the new DB structutre of the 4.0 version, that's why it is not commented.
*
*/
session_write_close();

//Now the widget's data will be calculated depending of the widget's type. 
switch ($type)
{
	case 'os':                
						
		$sqlgraph = "select count(*) as num, osname from ocsweb.hardware group by osname order by num desc limit 10;";
		
		if (!$rg = & $conn->CacheExecute($sqlgraph)) 
		{
		    print $conn->ErrorMsg();
		} 
		else 
		{
			$link = ""; //Menu::get_menu_url("/ossim/policy/ocs_index.php", 'environment', 'assets', 'ocs_inventory');

		    while (!$rg->EOF) 
		    {			
                if ($rg->fields["name"]=="")
                {
					$rg->fields["name"] = _("Unknown category");
				}
				
		        $data[]  = $rg->fields["num"];
				$label[] = $rg->fields["osname"];

				$links[] =  "'$link'";
                
				$rg->MoveNext();
		    }
		}
		
		$serie  = 'Operating Systems';
		
		$colors = get_widget_colors(count($data));


	break;
	
	case 'software':	

		$sqlgraph = "select count(*) as num, name from ocsweb.softwares group by name order by num desc limit 10;";
			
			
		if (!$rg = & $conn->CacheExecute($sqlgraph)) 
		{
		    print $conn->ErrorMsg();
		} 
		else 
		{
			$link = ""; //Menu::get_menu_url("/ossim/policy/ocs_index.php", 'environment', 'assets', 'ocs_inventory');
		    while (!$rg->EOF) 
		    {
		    
                if ($rg->fields["name"]=="")
                {
					$rg->fields["name"] = _("Unknown category");
				}
				
		        $data[]  = $rg->fields["num"];
				$label[] = $rg->fields["name"];
				
				$links[] =  "'$link'";

				$rg->MoveNext();
		    }
		}
		
		$serie  = 'Installed Software';
		
		$colors = get_widget_colors(count($data));

			
    break;
			
			
}

$db->close();

//Now the handler is called to draw the proper widget, this is: any kind of chart, tag_cloud, etc...
require 'handler.php';
