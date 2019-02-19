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

Session::logcheck("configuration-menu", "PolicyPolicy");


require_once 'policy_common.php';

?>

<html>
<head>

  <title> <?php echo _("OSSIM Framework"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  
  <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
  
</head>
<body>
                                                                                
  <h1> <?php echo _("New policy"); ?> </h1>

	<?php
	
	//Version
	$pro = Session::is_pro();
	
	$action      = POST('action');
	
	
	//Time range
	
	//range_type
	$tr_type     = POST('date_type');
	//timezone
	$tzone       = POST('tzone');
	//begin
	$b_month     = POST('begin_month');
	$b_month_day = POST('begin_day_month');
	$b_week_day  = POST('begin_day_week');
	$b_hour      = POST('begin_hour');
	$b_minute    = POST('begin_minute');

	//end 
	$e_month     = POST('end_month');
	$e_month_day = POST('end_day_month');
	$e_week_day  = POST('end_day_week');
	$e_hour      = POST('end_hour');
	$e_minute    = POST('end_minute');

	
	//Context
	$ctx = POST('ctx');


	$priority        = POST('priority');
	$active          = POST('active');
	$group           = POST('group');
	$order           = POST('order');
	$descr           = POST('descr');
	$correlate       = POST('correlate');
	$cross_correlate = POST('cross_correlate');
	$store           = POST('store');
	$qualify         = POST('qualify');
	$resend_alarms   = POST('resend_alarms');
	$resend_events   = ($pro)? POST('resend_events') : 0;
	$sign            = POST('sign');
	$sem             = POST('sem');
	$sim             = POST('sim');
	$rep             = POST('reputation');
	$event_type      = POST('plug_type');
	
	
	$ctx = str_replace("e_", "", $ctx);

	
	ossim_valid($action, 			OSS_ALPHA,													'illegal:' . _("Action"));
	ossim_valid($priority, 			OSS_SCORE, OSS_DIGIT, OSS_NULLABLE,							'illegal:' . _("Priority"));
	ossim_valid($descr, 			OSS_TEXT, OSS_PUNC_EXT, 												 	'illegal:' . _("Policy Name"));
	ossim_valid($store, 			OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_AT, OSS_NULLABLE, 		'illegal:' . _("Store"));
	ossim_valid($group, 			OSS_HEX,													'illegal:' . _("Group"));
	ossim_valid($active, 			OSS_DIGIT, OSS_NULLABLE, 									'illegal:' . _("Active"));
	ossim_valid($order, 			OSS_DIGIT,	 												'illegal:' . _("Order"));
	ossim_valid($correlate, 		OSS_DIGIT, OSS_NULLABLE, 									'illegal:' . _("correlate"));
	ossim_valid($cross_correlate, 	OSS_DIGIT, OSS_NULLABLE, 									'illegal:' . _("cross_correlate"));
	ossim_valid($store,				OSS_DIGIT, OSS_NULLABLE, 									'illegal:' . _("store"));
	ossim_valid($qualify, 			OSS_DIGIT, OSS_NULLABLE, 									'illegal:' . _("qualify"));
	ossim_valid($resend_alarms, 	OSS_DIGIT, OSS_NULLABLE, 									'illegal:' . _("resend_alarms"));
	ossim_valid($resend_events, 	OSS_DIGIT, OSS_NULLABLE, 									'illegal:' . _("resend_events"));
	ossim_valid($sign, 				OSS_DIGIT, OSS_NULLABLE, 									'illegal:' . _("sign"));
	ossim_valid($sem, 				OSS_DIGIT, OSS_NULLABLE, 									'illegal:' . _("sem"));
	ossim_valid($sim, 				OSS_DIGIT, OSS_NULLABLE, 									'illegal:' . _("sim"));
	ossim_valid($rep, 				OSS_DIGIT, OSS_NULLABLE, 									'illegal:' . _("reputation"));
	ossim_valid($ctx, 				OSS_HEX,  													'illegal:' . _("Entity"));
	ossim_valid($tr_type, 			OSS_DIGIT, 													'illegal:' . _("Date Type"));
	ossim_valid($b_month, 			OSS_DIGIT, 													'illegal:' . _("Begin Month"));
	ossim_valid($b_month_day, 		OSS_DIGIT,													'illegal:' . _("Begin Day of the month"));
	ossim_valid($b_week_day, 		OSS_DIGIT, 													'illegal:' . _("Begin Day of the week"));
	ossim_valid($b_hour, 			OSS_DIGIT,													'illegal:' . _("Begin Hour"));
	ossim_valid($b_minute, 			OSS_DIGIT,													'illegal:' . _("Begin Minute"));
	ossim_valid($e_month, 			OSS_DIGIT, 													'illegal:' . _("End Month"));
	ossim_valid($e_month_day, 		OSS_DIGIT,													'illegal:' . _("End Day of the month"));
	ossim_valid($e_week_day, 		OSS_DIGIT, 													'illegal:' . _("End Day of the week"));
	ossim_valid($e_hour, 			OSS_DIGIT,													'illegal:' . _("End Hour"));
	ossim_valid($e_minute, 			OSS_DIGIT,													'illegal:' . _("End Minute"));
	ossim_valid($tzone,				OSS_ALPHA, OSS_DOT, OSS_SCORE, '\/', '\+',					'illegal:' . _("Timezone"));
	ossim_valid($event_type, 		OSS_DIGIT, 													'illegal:' . _("Event Type"));
	
	
	if (ossim_error()) 
	{
		die(ossim_error());
	}

    $db   = new ossim_db();
    $conn = $db->connect();

    $is_engine = is_ctx_engine($conn, $ctx);

    $parse = function($sources) use ($is_engine) {
	$source_ips         = array();
	$source_host_groups = array();
	$source_nets        = array();
	$source_net_groups  = array();

	if ($is_engine) {
		$source_ips[] = "any";
	} else {
		$minsrc = 0;
		foreach($sources as $source) {
			if(check_any($source))
			{
				$source_ips[] = "any";
				$minsrc++;
			} elseif ($source == Policy::getHOMENETKEY() || $source == Policy::getNOTHOMENETKEY()) {
				$source_ips[] = $source;
                                $minsrc++;
			} else 
			{
				$src = explode("_", trim($source), 2);

				ossim_valid($src[1], OSS_HEX,'ANY', 'illegal:' . _($src[1]));
				if (ossim_error()) 
				{
					die(ossim_error());
				}
				$src[0] =  strtoupper($src[0]);
				if ($src[1] == "")
				{
    				continue;
				}
				switch ($src[0]) 
				{
					case "HOST":
						$source_ips[] = $src[1];
						break;

					case "HOSTGROUP":
						$source_host_groups[] = $src[1];
						break;

					case "NET":
						$source_nets[] = $src[1];
						break;

					case "NETGROUP":
						$source_net_groups[] = $src[1];
						break;

				}
				$minsrc++;
			}
		}
		if ($minsrc < 1) 
		{
			die(ossim_error(_("At least one Source IP, Host group,Net or Net group required")));
		}

	}
	return array($source_ips,$source_host_groups,$source_nets,$source_net_groups);
	};

	list($source_ips,$source_host_groups,$source_nets,$source_net_groups) = $parse(POST('sources'));
	list($dest_ips,$dest_host_groups,$dest_nets,$dest_net_groups) = $parse(POST('dests'));

	$parse_ports = function($port) use ($is_engine) {

	if ($is_engine) 
	{
		$portsrc[] = 0;
	}
	else
	{
		foreach($port as $name)
		{
			ossim_valid($name, OSS_DIGIT, 'illegal:' . _("$name"));
			
			if (ossim_error()) 
			{
				die(ossim_error());
			}
			
            if ($name != "") 
            {
                $portsrc[] = $name;
            }
            
		}
		if (!count($portsrc)) 
		{
			die(ossim_error(_("At least one Port required")));
		}
	}
	return $portsrc;
	};


        $portsrc = $parse_ports(POST('portsrc'));
        $portdst = $parse_ports(POST('portdst'));
	
	
	$plug_groups = array();
	$taxonomy    = array();
	
	if($event_type == 0)
	{
		/* plugin groups */		
		$plugins = POST('plugins');
		
		if ($plugins) 
		{
			foreach($plugins as $group_id => $on) 
			{
				ossim_valid($group_id, OSS_HEX, 'illegal:' . _("Plugin Group ID"));
				
				if (ossim_error()) 
    			{
    				die(ossim_error());
    			}
				
				$plug_groups[] = $group_id;
			}
		}
		if (!count($plug_groups)) 
		{
			die(ossim_error(_("At least one plugin group required")));
		}
		
		if (ossim_error()) 
		{
			die(ossim_error());
		}
	
	} 
	else 
	{
		/* Taxonomy */		
		$tax_list      = POST('taxfilters');
		$plug_groups[] = 0;		
		$_tax_aux      = array();
		
		if(is_array($tax_list) && !empty($tax_list))
		{
			foreach($tax_list as $tax_params) 
			{
				if($_tax_aux[$tax_params])
				{
				    continue;
				}
				
				$tax = explode('@', $tax_params);
				
				if(count($tax) != 3)
				{
					die(ossim_error(_("Wrong Taxonomy Condition")));
				}
				
				ossim_valid($tax[0], OSS_DIGIT, 'illegal:' . _("Taxonomy - Product Type ID"));
				ossim_valid($tax[1], OSS_DIGIT, 'illegal:' . _("Taxonomy - Category ID"));
				ossim_valid($tax[2], OSS_DIGIT, 'illegal:' . _("Taxonomy - Subcategory ID"));
				
				if (ossim_error()) 
				{
					die(ossim_error());
				}
				

				$taxonomy[]            = $tax;
				$_tax_aux[$tax_params] = 1;
				
			}
			
			unset($_tax_aux);
		}
	
	}
	
	
	/* sensors */
	$sensors = array();
	$sensor = POST('mboxs');

	if ($is_engine) 
	{
		$sensors[] = '00000000000000000000000000000000';
	}
	else
	{
		foreach($sensor as $name) 
		{
			ossim_valid(POST("$name") , OSS_HEX, OSS_NULLABLE, 'illegal:' . _("$name"));
			
			if (ossim_error()) 
			{
				die(ossim_error());
			}
			
            if ($name != "")
            { 
                $sensors[] = str_replace("sensor_", "",$name);
            }
		}
		
		if (!count($sensors)) 
		{
			die(ossim_error(_("At least one Sensor required")));
		}

	}	
	
	/* targets (servers) */
	$targets_ser = array();
	
	$default_server = Server::get_default_server($conn, FALSE);
	if(!empty($default_server))
	{
		$targets_ser[]  = $default_server;
	}
	
	if (count($targets_ser) < 1) 
	{
		die(ossim_error(_("At least one Target is required")));
	}

	$target = $targets_ser;
	
		
	
	/* Reputation */
	$reputation = array();
	$rep_list   = POST('repfilters');
	
	
	if(is_array($rep_list) && !empty($rep_list))
	{
		foreach($rep_list as $rep) 
		{
			$rep = explode('@', $rep);
			
			if(count($rep) != 4)
			{
				die(ossim_error(_("Wrong Reputation Condition")));
			}

			ossim_valid($rep[0], 	OSS_DIGIT, 		'illegal:' . _("Reputation - Activity ID"));
			ossim_valid($rep[1], 	OSS_DIGIT,		'illegal:' . _("Reputation - Severity Value"));
			ossim_valid($rep[2], 	OSS_DIGIT, 		'illegal:' . _("Reputation - Reliability Value"));
			ossim_valid($rep[3], 	OSS_DIGIT,		'illegal:' . _("Reputation - Direction Value"));
			
			if (ossim_error()) 
			{
				die(ossim_error());
			}

			$cond1 = intval($rep[1]) < 1  || intval($rep[1]) > 10;
			$cond2 = intval($rep[2]) < 1  || intval($rep[2]) > 10;
			$cond3 = intval($rep[3]) != 1 && intval($rep[3]) != 0;
			
			if($cond1 || $cond2 || $cond3)
			{
    			continue;
			} 
			
			$reputation[] = $rep;
		}
	}
	
	
	/* Event Conditions */
	$event_conds = array();
	$event_list  = POST('evfilters');
	
	
	if(is_array($event_list) && !empty($event_list) && $pro)
	{
		foreach($event_list as $event) 
		{
			
			$event = explode('@', $event);
			
			if(count($event) != 2)
			{
				die(ossim_error(_("Wrong Event Condition")));
			}

			ossim_valid($event[0], 	OSS_DIGIT,		'illegal:' . _("Reputation - Priority Value"));
			ossim_valid($event[1], 	OSS_DIGIT, 		'illegal:' . _("Reputation - Reliability Value"));
			
			if (ossim_error()) 
			{
				die(ossim_error());
			}

			$cond1 = intval($event[0]) < 1  || intval($event[0]) > 5;
			$cond2 = intval($event[1]) < 1  || intval($event[1]) > 10;
			
			if($cond1 || $cond2)
			{
    			continue;
			} 

			$event_conds[] = $event;
		}
	}
	
	
	$frw_conds = array();
	$frw_list  = POST('frwfilters');
		
	if(is_array($frw_list) && !empty($frw_list) && $resend_events == 1)
	{
		foreach($frw_list as $server) 
		{
			
			$server = explode('@', $server);
			
			if(count($server) != 2)
			{
				die(ossim_error(_("Wrong Event Condition")));
			}

			ossim_valid($server[0], 	OSS_HEX,		'illegal:' . _("Forwarding - Server"));
			ossim_valid($server[1], 	OSS_DIGIT, 		'illegal:' . _("Forwarding - Priority Value"));
			
			if (ossim_error()) 
			{
				die(ossim_error());
			}

            if(intval($server[1]) < 1  || intval($server[1]) > 99)
            {
                continue;
            }

			$frw_conds[$server[0]] = $server[1];
		}
		
		
	}
	
	if($resend_events == 1 && !count($frw_conds))
	{
		die(ossim_error(_("At least one Server to forward event is required")));	
	}
	
	/* IDM */
	$idm      = array();

	
	//Time
	switch($tr_type)
	{
		case '1':
				$b_month     = 0;
				$b_month_day = 0;
				$b_week_day  = 0;
				
				$e_month     = 0;
				$e_month_day = 0;
				$e_week_day  = 0;
				
				break;
				
		case '2':
				$b_month_day = 0;
				$e_month_day = 0;
				break;
				
		case '3':
				$b_month     = 0;
				$e_month     = 0;
				break;
				
		case '4':
				$b_week_day  = 0;
				$e_week_day  = 0;
				break;
				
		default:
		
			die(ossim_error(_("Wrong Date Type Chosen")));
	
	}

	if ($is_engine) 
	{
		$sem = 0;
	}
	
	/* actions */
	$policy_action = array();
	$actions_list  = POST('actions');
	
	if ($actions_list) 
	{
		foreach($actions_list as $action_id) 
		{
			ossim_valid($action_id, OSS_HEX, 'illegal:' . _("Action ID"));
			
			if (ossim_error()) 
			{
				die(ossim_error());
			}
			
			$policy_action[] = $action_id;
		}		
		
	}
		
	switch($action)
	{
		case 'new':
		
			if ($order == 0) 
			{
				$order = Policy::get_next_order($conn, $ctx, $group);
			}

			$newid = Policy::insert($conn, $ctx, $priority, $active, $group, $order, $tzone, $b_month, $b_month_day, $b_week_day, $b_hour, $b_minute, $e_month, $e_month_day, $e_week_day, $e_hour, $e_minute, $descr, $source_ips, $source_host_groups, $dest_ips, $dest_host_groups, $source_nets, $source_net_groups, $dest_nets, $dest_net_groups, $portsrc, $portdst, $plug_groups, $sensors, $target, $taxonomy, $reputation, $event_conds, $idm, $correlate, $cross_correlate, $store, $rep, $qualify, $resend_alarms, $resend_events, $frw_conds, $sign, $sem, $sim);
			
			// Actions
			if (!empty($newid) && count($policy_action) > 0) 
			{ 
				foreach ($policy_action as $action_id)
				{
					Policy_action::insert($conn,$action_id,$newid);
				}
			}

			break;
				
		case 'edit':
		
			$id = POST('policy_id');
			
            if(!Policy::is_visible($conn, $id))
            {
                die(ossim_error(_("You do not have permission to edit this policy")));
            }
	
			ossim_valid($id, 	OSS_HEX,	'illegal:' . _("Policy ID"));
			
			if (ossim_error()) 
			{
				die(ossim_error());
			}
			

			Policy::update($conn, $id, $ctx, $priority, $active, $group, $order, $tzone, $b_month, $b_month_day, $b_week_day, $b_hour, $b_minute, $e_month, $e_month_day, $e_week_day, $e_hour, $e_minute, $descr, $source_ips, $source_host_groups, $dest_ips, $dest_host_groups, $source_nets, $source_net_groups, $dest_nets, $dest_net_groups, $portsrc, $portdst, $plug_groups, $sensors, $target, $taxonomy, $reputation, $event_conds, $idm, $correlate, $cross_correlate, $store, $rep, $qualify, $resend_alarms, $resend_events, $frw_conds, $sign, $sem, $sim);
			
			// Actions
			if (count($policy_action) > 0) 
			{ 
				Policy_action::delete($conn,$id);
				
				foreach ($policy_action as $action_id)
				{
					Policy_action::insert($conn,$action_id,$id);
				}
			}
	
			break;
				
		case 'clone':


			$order = Policy::get_next_order($conn, $ctx, $group);

			$newid = Policy::insert($conn, $ctx, $priority, $active, $group, $order, $tzone, $b_month, $b_month_day, $b_week_day, $b_hour, $b_minute, $e_month, $e_month_day, $e_week_day, $e_hour, $e_minute, $descr, $source_ips, $source_host_groups, $dest_ips, $dest_host_groups, $source_nets, $source_net_groups, $dest_nets, $dest_net_groups, $portsrc, $portdst, $plug_groups, $sensors, $target, $taxonomy, $reputation, $event_conds, $idm, $correlate, $cross_correlate, $store, $rep, $qualify, $resend_alarms, $resend_events, $frw_conds, $sign, $sem, $sim);
			
			// Actions
			if (!empty($newid) && count($policy_action) > 0) 
			{ 
				foreach ($policy_action as $action_id)
				{
					Policy_action::insert($conn,$action_id,$newid);
				}
			}
			
			break;
				
		default:
				die(_('Wrong option chosen'));
	
	}
		
		
	$db->close();
	
		
?>
    <p> <?php echo _("Policy successfully inserted"); ?> </p>
    
    <script>document.location.href="policy.php"</script>

</body>
</html>

