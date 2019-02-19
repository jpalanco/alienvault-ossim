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
Session::logcheck("analysis-menu", "IncidentsIncidents");

//End of permissions		
		
		
//Setting DB connection			
$db    = new ossim_db();
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
	$chart_info      = json_decode(GET("value"),true);  		//Params of the widget representation, this is: type of chart, legend params, etc.

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

	foreach ($chart_info as $key=>$val)
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
	

$assets_filters = $param_filters = array();
	

if ( preg_match("/u_(.*)/", $winfo['asset'], $fnd) ) 
{
	$param_filters['user'] = $fnd[1];
}
elseif ( preg_match("/e_(.*)/", $winfo['asset'], $fnd) ) 
{
	$param_filters['user'] = $fnd[1];
} 
elseif (!strcasecmp("ALL_ASSETS", $winfo['asset']) || empty($winfo['asset']))
{
	$param_filters['user'] = $user;
}

$param_filters["assets"] = array();

if (empty($param_filters['user']))
{
	$assets_filters = get_asset_filters($conn, $winfo['asset']);

	if (is_array($assets_filters["assets"]['host']))
	{
		foreach ($assets_filters["assets"]['host'] as $k => $v)
		{
			$param_filters["assets"][$k] = $v['ip']; 
		}
	}

	if (is_array($assets_filters["assets"]['net']))
	{
		foreach ($assets_filters["assets"]['net'] as $k => $v)
		{
			$param_filters["assets"][$k] = $v['ip']; 
		}
	}
}
	
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
switch($type)
{
	case 'ticketStatus':    

		$ticket_status = Incident::incidents_by_status($conn, $param_filters["assets"], $param_filters["user"]);

		
		if (is_array($ticket_status) && !empty($ticket_status))
		{
			foreach ($ticket_status as $type => $ocurrences)
			{
				$data[]  = $ocurrences;
				$label[] = _($type);

				$link    = Menu::get_menu_url("/ossim/incidents/index.php?status=$type&hmenu=Tickets&smenu=Tickets", 'analysis', 'tickets');
				$links[] =  $link;
			}					
		}

		$serie  = 'Amount of Tickets';
		
		$colors = get_widget_colors(count($data));

		break;
		
	
	case 'ticketTypes':	

		$ticket_by_type = Incident::incidents_by_type($conn, $param_filters['assets'], $param_filters['user']);

		if (is_array($ticket_by_type) && !empty($ticket_by_type))
		{
			foreach ($ticket_by_type as $type => $ocurrences)
			{
				$type_short = (strlen($type) > 28) ? substr($type, 0, 25)."..." : $type;
				$data[]     = $ocurrences;
				$label[]    = _($type_short);

				$link       = Menu::get_menu_url("/ossim/incidents/index.php?type=$type&status=not_closed&hmenu=Tickets&smenu=Tickets", 'analysis', 'tickets');
				$links[]    =  $link;
			}
		}
		
		$colors = get_widget_colors(count($data));
		
		break;
		
			
	case 'ticketsByClass':                
			
		$ticket_by_class = Incident::incidents_by_class($conn, $param_filters["assets"], $param_filters["user"]);
		
		if (is_array($ticket_by_class) && !empty($ticket_by_class))
		{
			foreach ($ticket_by_class as $class => $ocurrences)
			{
				$data[]     = $ocurrences;
				$label[]    = _($class);
				
				$link       = Menu::get_menu_url("/ossim/incidents/index.php?ref=$class&status=not_closed&hmenu=Tickets&smenu=Tickets", 'analysis', 'tickets');
				$links[]    =  $link;
			}
		}
		
		$colors = get_widget_colors(count($data));

		break;
		
			
	case 'openedTicketsByUser':
			
		$ticket_by_user = Incident::incidents_by_user($conn, true, $param_filters["assets"], $param_filters["user"]);
		$i = 0;
						
		if (is_array($ticket_by_user) && !empty($ticket_by_user))
		{
			foreach ($ticket_by_user as $user => $ocurrences)
			{
				if ($i < 10)
				{
					$user_short = ( strlen($user) > 28 ) ? substr($user, 0, 25)."..." : $user;
					$data[]     = $ocurrences;
					$label[]    = utf8_encode($user_short);

					$link       = Menu::get_menu_url("/ossim/incidents/index.php?in_charge=$user&status=not_closed&hmenu=Tickets&smenu=Tickets", 'analysis', 'tickets');
					$links[]    = $link;
				}
				else
				{
					break;
				}
				
				$i++;
			}
		}
		
		$colors = get_widget_colors(count($data));
							
		break;
		

	case 'ticketResolutionTime':
		
		$ttl_groups = array();
						
		$list       = Incident::incidents_by_resolution_time($conn, $param_filters["assets"], $param_filters["user"]);                
		$ttl_groups = array("1"=>0, "2"=>0, "3"=>0, "4"=>0, "5"=>0, "6"=>0);
		
		$total_days = 0;
		$day_count  = null;
						
		foreach ($list as $incident) 
		{
			$ttl_secs    = $incident->get_life_time('s');
			$days        = round($ttl_secs/60/60/24);
			$total_days += $days;
			$day_count++;
			
			if ($days < 1) 
			{
			    $days = 1;
			}
			
			if ($days > 6) 
			{
			$days = 6;
			}

			@$ttl_groups[$days]++;
		}

		$data  = array_values($ttl_groups);
		
		foreach ($data as $dy)
		{
			$link       = Menu::get_menu_url("/ossim/incidents/index.php?status=Closed&hmenu=Tickets&smenu=Tickets", 'analysis', 'tickets');
			$links[]    = $link;
		}	
		
		$label  = array( Util::html_entities2utf8(_("1 Day")),
		                 Util::html_entities2utf8(_("2 Days")),
		                 Util::html_entities2utf8(_("3 Days")),
		                 Util::html_entities2utf8(_("4 Days")),
		                 Util::html_entities2utf8(_("5 Days")),
		                 Util::html_entities2utf8(_("6+ Days"))
                        );
		
		$serie  = 'Amount of Tickets';
		
		$colors = get_widget_colors(count($data));
		
		break;
		
		
	case 'ticketsByPriority':                
					
		$list = Incident::incidents_by_priority($conn, $param_filters["assets"], $param_filters["user"]);							

		if (is_array($list) && !empty($list)) 
		{
			foreach ($list as $priority => $v) 
			{
				if ($v > 0)
				{
					$data[]  = $v;
					$label[] = _("Priority")." ".$priority;

					$link    = Menu::get_menu_url("/ossim/incidents/index.php?priority=". Incident::get_priority_string($priority) ."&status=not_closed&hmenu=Tickets&smenu=Tickets", 'analysis', 'tickets');
					$links[] =  $link;
				}
			}
		}
		
		$serie  = 'Amount of Tickets';
		
		$colors = get_widget_colors(count($data));

		break;
     
	 
	case 'ticketTags':			
		
		$ticket_by_tags = Incident::incidents_by_tag($conn, $param_filters["assets"], $param_filters["user"]);
		
		if (is_array($ticket_by_tags) && !empty($ticket_by_tags))
		{
			foreach ($ticket_by_tags as $type => $ocurrences)
			{
				$type_short    = ( strlen($type) > 28 ) ? substr($type, 0, 25)."..." : $type;
				$data[]  = $ocurrences;
				$label[] = _($type_short);
				
				$link    = Menu::get_menu_url("incidents/index.php?tag=". Incident::get_id_by_tag($conn, $type) ."&status=not_closed&hmenu=Tickets&smenu=Tickets", 'analysis', 'tickets');
				$links[] =  $link;
			}
		}
		
		$serie  = 'Amount of Tickets';
		
		$colors = get_widget_colors(count($data));
		
		break;

		
	case 'ticketsClosedByMonth':
                            
        $ticket_closed_by_month = Incident::incidents_closed_by_month($conn, $param_filters["assets"], $param_filters["user"]);
                        
        if (is_array($ticket_closed_by_month) && !empty($ticket_closed_by_month))
        {
			foreach ($ticket_closed_by_month as $event_type => $months)
			{
				$label[]           = "{label: '".$event_type."'}";
				$data[$event_type] = implode(",", $months);						
			}
			
			for ($i=0; $i<12; $i++)
			{
				$link       = Menu::get_menu_url("/ossim/incidents/index.php?status=Closed&hmenu=Tickets&smenu=Tickets", 'analysis', 'tickets');
				$links[]    = $link;
			}
			
			//X-axis legend
			$event_types = array_keys($ticket_closed_by_month);
			
			$dates       = array_keys($ticket_closed_by_month[$event_types[0]]);
			$xaxis_text  = array();
			
			foreach ($dates as $date)
			{
    			list($month, $day) = explode('-', $date);
    			$xaxis_text[]      = Util::html_entities2utf8(_("$month"));
			}
			
			$colors      = get_widget_colors(count($data));

        }
                                                        
        break;
        

	case 'ticketsByTypePerMonth':
            
        $data = array();                                                
                        
        $ticket_by_type_per_month = Incident::incidents_by_type_per_month($conn, $param_filters["assets"], $param_filters["user"]);
                        
        if (is_array($ticket_by_type_per_month) && !empty($ticket_by_type_per_month))
        {
			$i = 0; //Solving problem with type names with special characters.
			
			foreach ($ticket_by_type_per_month as $event_type => $months)
			{				
				$label[]            = "{label: '".$event_type."'}";
				$data["incident$i"] = implode(",", $months);	
										
				$link               = Menu::get_menu_url("/ossim/incidents/index.php?type=$event_type&status=not_closed&hmenu=Tickets&smenu=Tickets", 'analysis', 'tickets');
				$links[]            =  $link;

				$i ++;
			}					

			$event_types = array_keys($ticket_by_type_per_month);
			$xaxis_text  = array_keys($ticket_by_type_per_month[$event_types[0]]);
			
			$colors      = get_widget_colors(count($data));

        }
                                
        break;
			
}

$db->close();


//Now the handler is called to draw the proper widget, this is: any kind of chart, tag_cloud, etc...
require 'handler.php';
