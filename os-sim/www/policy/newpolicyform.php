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


$conf        = $GLOBALS["CONF"];
$open_source = (!preg_match("/pro|demo/i",$conf->get_conf("ossim_server_version"))) ? true : false;
$admin       = Session::Am_i_admin();

$db      = new ossim_db();
$conn    = $db->connect();
$login   = Session::get_session_user();

$ctx     = GET("ctx");
$id      = GET('id');
$group   = GET('group');
$order   = GET('order');
$insert  = (GET('insertafter') != "") ? GET('insertafter') : GET('insertbefore');
$clone   = GET('clone');



if ($open_source && empty($ctx))
{
	$ctx = Session::get_default_ctx();
}

ossim_valid($id, 	OSS_HEX, OSS_NULLABLE,		'illegal:' . _("id"));
ossim_valid($ctx, 	OSS_HEX,					'illegal:' . _("ctx"));
ossim_valid($group, OSS_HEX, OSS_NULLABLE, 		'illegal:' . _("group"));
ossim_valid($order, OSS_DIGIT, OSS_NULLABLE, 	'illegal:' . _("order"));
ossim_valid($clone, OSS_DIGIT, OSS_NULLABLE, 	'illegal:' . _("clone"));
ossim_valid($insert, OSS_HEX, OSS_NULLABLE, 		'illegal:' . _("insert before/after"));

if (ossim_error()) 
{
    die(ossim_error());
}

$is_engine = is_ctx_engine($conn, $ctx);


// default vars
$priority        = -1;
$correlate       = 1;
$cross_correlate = 1;
$store  		 = 1;
$qualify 		 = 1;
$active 		 = 1;
$order 			 = 0;
$resend_event 	 = 0;
$sign 			 = 0;
$sem 			 = ($open_source) ? 0 : 1;
$sim 			 = 1;
$rep 			 = 0;


if ($group == '') 
{
    $group = '00000000000000000000000000000000';
}
	
$desc = "";

$flag_events     = true;
$flag_sensors    = true;
$flag_reputation = true;
$flag_event_prio = true;
$flag_time       = true;

$user_list = Session::get_list($conn, "WHERE login='$login'");
$user      = $user_list[0];

//Getting timezone
$utz = ($login != "") ? $user->get_tzone() : "";

if ($utz == "0" || $utz == "")
{
	$utz = 'UTC';
}

if (preg_match("/Localtime/", $utz))
{
    $utz = trim(Util::execute_command('head -1 /etc/timezone', FALSE, 'string'));
}
	
//This is the default timezone, It's needed to save in case u delete the time range condition
$default_tz = $utz;

$sources       = $dests = $ports_source = $ports_destiny = $plugingroups = $sensors = $actions = array();
$rep_filters   = $tax_filters = $event_filters = $server_fwd_filters =array();
$filter        = get_filters_names($conn);		
								

if ($id != "") 
{
    if ($policies = Policy::get_list($conn, "AND id=UNHEX('$id')")) 
    {
        $policy   = $policies[0];
		$ctx      = $policy->get_ctx();
        $priority = $policy->get_priority();
        $active   = $policy->get_active();
        $group    = $policy->get_group();
        $order    = $policy->get_order();
	$decorator = function($text,$vars) {
		if (in_array($text,array(Policy::getHOMENET(),Policy::getNOTHOMENET(),Policy::getANY()))) {
			return $text;
		}
		return "{$vars[0]}: $text";
	};
	$decorator_vars = array(_("HOST"),_("NETWORK"),_("HOST_GROUP"),_("NETWORK_GROUP"));
	$sources = $policy->get_srcdst_cell("source",$conn,$decorator,$decorator_vars);
        $dests = $policy->get_srcdst_cell("dest",$conn,$decorator,$decorator_vars);

        //PORTS
		//source
        if ($port_list = $policy->get_ports($conn, 'source')) 
        {
			foreach ($port_list as $port_group) 
			{
				$ports_source[$port_group->get_port_id()] = check_any($port_group->get_port_id()) ? _("ANY") : Port_group::get_name_by_id($conn, $port_group->get_port_id());
			}
		}
		

		//destiny
		if ($port_list = $policy->get_ports($conn, 'dest'))
		{
			foreach ($port_list as $port_group) 
			{
				$ports_destiny[$port_group->get_port_id()] =  check_any($port_group->get_port_id()) ? _("ANY") : Port_group::get_name_by_id($conn, $port_group->get_port_id());

			}
		}
				
				
		$flag_events   =  true;
		//PLUGIN GROUPS
		if ($policy_pgroup = $policy->get_plugingroups($conn, $policy->get_id()))
		{
            foreach ($policy_pgroup as $pgroup) 
            {
                $plugingroups[] = $pgroup['id'];
    
            }
        }
			
		if (!$is_engine)
		{
			//TAXONOMY
			if ($taxonomy_list = $policy->get_taxonomy_conditions($conn))
			{
				foreach ($taxonomy_list as $tax) 
				{				
					$tax_id  = $tax->get_product_type_id() . "@" . $tax->get_category_id() . "@" . $tax->get_subcategory_id();
					$tax_val = $filter['ptype'][$tax->get_product_type_id()] . " | " . $filter['cat'][$tax->get_category_id()] . " | " . $filter['subcat'][$tax->get_subcategory_id()];
					$tax_filters[$tax_id] = $tax_val;
					$flag_events   = false;
				}
			}
		}


		//SENSOR	
		$sensor_exist = $policy->exist_sensors($conn);
		if ($sensor_list = $policy->get_sensors($conn)) 
		{
			foreach ($sensor_list as $sensor) 
			{
				if (!check_any($sensor->get_sensor_id()))
				{
					if ($sensor_exist[$sensor->get_sensor_id()]!='false')
					{
						$sensors['sensor_'.$sensor->get_sensor_id()] = Av_sensor::get_name_by_id($conn, $sensor->get_sensor_id());
						$flag_sensors                      = false;
					}
				} 
				else 
				{
					$sensors[$sensor->get_sensor_id()] = _('ANY');
				}
			}
		}
		else
		{
    		$flag_sensors = FALSE;
		}

				
		//Time Filters
		if ($policy_time = $policy->get_time($conn))
		{
    		$time_begin[0] = $policy_time->get_month_start();
    		$time_begin[1] = $policy_time->get_month_day_start();
    		$time_begin[2] = $policy_time->get_week_day_start();
    		$time_begin[3] = $policy_time->get_hour_start();
    		$time_begin[4] = $policy_time->get_minute_start();
    		
    		$time_end[0]   = $policy_time->get_month_end();
    		$time_end[1]   = $policy_time->get_month_day_end();
    		$time_end[2]   = $policy_time->get_week_day_end();
    		$time_end[3]   = $policy_time->get_hour_end();
    		$time_end[4]   = $policy_time->get_minute_end();	
    		
    		$flag_time = false;
    		
    		/*
    		Getting the data type:
    			data_type = 1 ---> Daily
    				If month, day of the month and day of the week have the default value
    			
    			data_type = 2 ---> Weekly
    				If the day of the month has the default value
    			
    			data_type = 3 ---> Monthly
    				If the month has the default value
    			
    			data_type = 4 ---> Custom Range
    				If the day of the week has the default value
    				
    			otherwise:
    				Daily --> data_type = 1
    		
    		*/
    		
    		if (($time_begin[0] + $time_begin[1] + $time_begin[2] + $time_end[0] + $time_end[1] + $time_end[2]) == 0)
    		{
    			$date_type     = 1;
    			
    			//setting the others field to the default values
    			$time_begin[0] = 1;
    			$time_begin[1] = 1;
    			$time_begin[2] = 1;			
    			$time_end[0]   = 12;
    			$time_end[1]   = 31;
    			$time_end[2]   = 7;
    			
    			if (!$flag_time)
    			{
    				if (($utz == $policy_time->get_timezone()) && $time_begin[3] == 0 && $time_begin[4] == 0 && $time_end[3]== 23 && $time_end[4] == 59)
    				{
    					$flag_time = true;
    				}
    			}
    			
    		} 
    		elseif (($time_begin[1] + $time_end[1]) == 0)
    		{
    			$date_type     = 2;
    			
    			//setting the others field to the default values
    			$time_begin[1] = 1;
    			$time_end[1]   = 31;
    			
    		} 
    		elseif (($time_begin[0] + $time_end[0]) == 0)
    		{
    			$date_type     = 3;
    			
    			//setting the others field to the default values
    			$time_begin[0] = 1;			
    			$time_end[0]   = 12;
    			
    		} 
    		elseif (($time_begin[2] + $time_end[2]) == 0)
    		{
    			$date_type     = 4;
    			
    			//setting the others field to the default values
    			$time_begin[2] = 1;		
    			$time_end[2]   = 7;
    			
    		} 
    		else 
    		{
    			$date_type     = 1;
    			
    			//setting the others field to the default values
    			$time_begin[0] = 1;
    			$time_begin[1] = 1;
    			$time_begin[2] = 1;	
    			$time_begin[3] = 0;	
    			$time_begin[4] = 0;	
    			$time_end[0]   = 12;
    			$time_end[1]   = 31;
    			$time_end[2]   = 7;
    			$time_end[3]   = 23;
    			$time_end[4]   = 56;
    		
    		}
    		
    		//timezone
    		$utz = $policy_time->get_timezone();
		
		}
		else
		{
    		$flag_time = false;
    		
    		//Default time values
    		$time_begin    = array(1,1,1,0,0);
        	$time_end      = array(12,31,7,23, 59);
        	$date_type     = 1;
		}
		
		
		//REPUTATION
		if ($reputation_list = $policy->get_reputation_conditions($conn))
		{
			foreach ($reputation_list as $rep) 
			{				
				$rep_id  = $rep->get_activity_id() . "@" . $rep->get_priority() . "@" . $rep->get_reliability() . "@" . $rep->get_direction();
				$rep_val = $filter['act'][$rep->get_activity_id()] . " | " . $rep->get_priority() . " | " . $rep->get_reliability() . " | " . (($rep->get_direction() == 0) ? _('Src').'.' : _('Dest').'.');
				$rep_filters[$rep_id] = $rep_val;
				$flag_reputation = false;
			}
			
        }
		
		
		//Event Risk
		if ($event_prio_list = $policy->get_event_conditions($conn))
		{
			foreach ($event_prio_list as $event) 
			{				
				$ev_id  = $event->get_priority() . "@" . $event->get_reliability();
				$ev_val = "Prio: " . $event->get_priority() . " | Rel: " . $event->get_reliability();
				
				$event_filters[$ev_id] = $ev_val;
				$flag_event_prio       = FALSE;
			}
			
        }
		
		
		//Event Risk
		if ($server_fwd_list = $policy->get_forward_conditions($conn))
		{
			foreach ($server_fwd_list as $fwd) {				
				$frw_id  = $fwd->get_parent_id() . "@" . $fwd->get_priority();
				$frw_val = Server::get_name_by_id($conn, $fwd->get_parent_id()) . ": " . $fwd->get_priority();
				
				$server_fwd_filters[$frw_id] = $frw_val;

			}
			
        }

		//Others
        $desc = html_entity_decode($policy->get_descr());
        
        if ($role_list = $policy->get_role($conn))
        {
            foreach ($role_list as $role) 
            {
                $correlate       = ($role->get_correlate()) ? 1 : 0;
                $cross_correlate = ($role->get_cross_correlate()) ? 1 : 0;
                $store           = ($role->get_store()) ? 1 : 0;
                $qualify         = ($role->get_qualify()) ? 1 : 0;
                $resend_alarm    = ($role->get_resend_alarm()) ? 1 : 0;
                $resend_event    = ($role->get_resend_event()) ? 1 : 0;
                $sign            = ($role->get_sign()) ? 1 : 0;
                $sem             = ($role->get_sem()) ? 1 : 0;
                $sim             = ($role->get_sim()) ? 1 : 0;
                
                break;
            }
        }
		
		

    }
} 
else 
{
	//Time Filters
	$time_begin    = array(1,1,1,0,0);
	$time_end      = array(12,31,7,23, 59);
	$date_type     = 1;

	//Assets Filters
    $ports_source []  							 = "ANY";
	$ports_destiny[]							 = "ANY";
    $sensors['00000000000000000000000000000000'] = "ANY";	
	$plugingroups[]                              = '00000000000000000000000000000000';
	
                                                                                  
}

if ( $utz == "0" || $utz == "" )
{
	$utz = 'UTC';
}

if ( preg_match("/Localtime/", $utz) )
{
    $utz = trim(Util::execute_command('head -1 /etc/timezone', FALSE, 'string'));
}		

if ($insert != "") 
{
    if ($policies = Policy::get_list($conn, " AND id=UNHEX('$insert')")) 
    {
        $order = $policies[0]->get_order();
        $group = $policies[0]->get_group();
        
        if (GET('insertafter') != "")
        {
             $order++; // insert after
        }
        
    }
}

$sign_line = FALSE;

if (!$open_source)
{
	$def_server = Server::get_default_server($conn, FALSE);
	
	$server_h   = Server::get_my_hierarchy($conn, $def_server);
	
	$sign_line  = Policy::is_allowed_sign_line($conn, $def_server);
	
}

$tooltip_sing_line = _('This policy cannot use Log Line Sign because the AlienVault Server only allows Log Block Sign. In order to use this option, you can modify the Log Sign method in Deployment -> Servers.');



$paths = Asset::get_path_url(FALSE);

$asset_form_url = $paths['asset']['views'] . 'asset_form.php';
$net_form_url   = $paths['network']['views'] . 'net_form.php';

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?=_("OSSIM Framework")?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>


    <?php
    //CSS Files
    $_files = array();

    $_files[] = array('src' => 'av_common.css',       'def_path' => true);
    $_files[] = array('src' => 'jquery-ui.css',       'def_path' => true);
    $_files[] = array('src' => 'tree.css',            'def_path' => true);
    $_files[] = array('src' => 'tipTip.css',          'def_path' => true);
    $_files[] = array('src' => 'coolbox.css',         'def_path' => true);    
    $_files[] = array('src' => 'ui.multiselect.css',  'def_path' => true);
    $_files[] = array('src' => 'liteaccordion.css',   'def_path' => true);

    Util::print_include_files($_files, 'css');

    //JS Files
    $_files = array();

    $_files[] = array('src' => 'jquery.min.js',                             'def_path' => true);
    $_files[] = array('src' => 'jquery-ui.min.js',                          'def_path' => true);
    $_files[] = array('src' => 'jquery.tmpl.1.1.1.js',                      'def_path' => true);
    $_files[] = array('src' => 'jquery.cookie.js',                          'def_path' => true);
    $_files[] = array('src' => 'jquery.dynatree.js',                        'def_path' => true);
    $_files[] = array('src' => 'combos.js',                                 'def_path' => true);
    $_files[] = array('src' => 'greybox.js',                                'def_path' => true);
    $_files[] = array('src' => 'coolbox.js',                                'def_path' => true);
    $_files[] = array('src' => 'ui.multiselect.js',                         'def_path' => true);
    $_files[] = array('src' => 'jquery.elastic.source.js',                 'def_path' => true);
    $_files[] = array('src' => 'liteaccordion/liteaccordion.jquery.js',     'def_path' => true);
    $_files[] = array('src' => 'jquery.tipTip-ajax.js',                     'def_path' => true);

    Util::print_include_files($_files, 'js');
    ?>
	
	
	<style>

        .size10 
        {
            font-size:10px;
        }
        
        .tab_table 
        {
            margin: auto;
            border:none;
            width: 100%;
            height: 100%;
            text-align: center;
            vertical-align: middle;
        }
        
        #p_conseq 
        {
         width: 350px;
        }
        
        #p_conseq th 
        {
            width: 130px;
        }
        
        .cont_elem
        { 
            width: 90%; 
            float: left;
        }
        
        .red 
        { 
            color:red;
            font-weight:bold 
        }
		
		.container_tree_button
		{
			width:300px; 
			height:240px;
			margin-top:5px;
			overflow:auto
		}
		
		.container_tree 
		{
			width:300px; 
			height:254px;
			margin-top:16px;
			overflow:auto
		}

		
		.wrap_acc
		{
			overflow:auto;
			width:100%;
			height:100%;
			margin:0 auto;		
		}
		

		.resumep 
		{
			text-align: left;
			font-size: 10px;
			border:none;
			vertical-align:top;
		}
		
		.small{
			white-space:nowrap;
			vertical-align:top;
		}
		
		#policy_resume
		{
    	   overflow:auto; 
    	   margin:10px auto 5px auto;	
		}
		
		.resume
		{
    		margin-top: 5px;
		}
		
		.resume_c, .resume_c td
		{
    		padding: 0;
    		border-spacing: 0px;
    		border-collapse: separate;
		}
		
		.policy_header
		{
			background-color:#E5E5E5;
		}
	
		.td_p_container
		{
    		text-align:center; 
    		width:45%;
    		padding-top: 25px;
    		vertical-align: top;
		}
	
		/*Multiselect loading styles*/   
		
		#ms_body {height: 200px;}
		
		#load_ms {
			margin:0 auto; 
			padding-top: 110px; 
			text-align:center;
		}	
		
		.div_resume{
			height: 200px;
			overflow: auto;
			padding: 0 5px;
			text-align: center;
		}
		
		.img_resume{
			height:14px;
			width:14px;

		}
		
		.bgred {
			background: none repeat scroll 0 0 #F9F9C2;
		}


		/* Styles for the accordion */ 
		
		.img_rotate{
			-webkit-transform: rotate(90deg);
			-moz-transform: rotate(90deg);
			-o-transform: rotate(90deg);
			writing-mode: tb-rl;
			padding-left:2px;
			height:18px;
			width:18px;
		}
		
		.div_left{		
			float:left !important;
			padding-left:20px !important;
			width:auto !important;
			background:transparent !important;
		} 
		
		.div_right{
			float:right !important;
			padding-right:10px !important;
			width:auto !important;
			background:transparent !important;		
		}
		
		#conditions 
        {
			visibility:hidden;
		}
		
		#connsequences 
        {
			visibility:hidden;
		}
		
		/* Coolbox styles */
		
		#CB_window{
			 /*background: none repeat scroll 0 0 #F9F9C2 !important;*/
			 border: 1px solid #A3A3A3;
		}
		
		.ui-icon { width: 16px; height: 16px; background-image: url(/ossim/pixmaps/theme/ui-icons-widgets.png) !important; }

        #dg_locked {
            height: 15px;
            vertical-align: bottom;
            cursor: pointer;
        }
        
        #fed_only
        {
            position: absolute;
            top: 14px; 
            left:40px; 
            right: 40px;
            text-align:center;
            font-style:italic
        }
        
        .tip_sign_line
        {
            cursor: help;
        }

	</style>
	
	<script type="text/javascript">
		
		var acc_width 	   = 1000;
		var condition_op   = 'cond-1';
		var consequence_op = 'conseq-1';
		var accordion_gb   = 1;
		var ctx            = '<?php echo $ctx ?>';
		var reloading      = '<img src="../pixmaps/theme/loading2.gif" border="0" align="absmiddle"><span style="margin-left:5px"><?php echo _("Re-loading data...")?></span>';
		var layer          = null;
		var nodetree       = null;
		var suf            = "c";
		var i              = 1;

		function load_tree(filter)
		{
			combo = (suf=="c") ? 'sources' : 'dests';
			
			var tree_key = <?php echo (($open_source) ? "'assets|any|home'" : "'ae_'+ctx+'|any|home'") ?>;
			
			layer = '#asset_tree_'+suf;

            if ($(layer).length < 1)
            {
                $('#container'+suf).html('<div id="asset_tree_'+suf+'" style="width:100%"></div>');
            }
            else
            {
                $(layer).dynatree("destroy");
            }

			$(layer).dynatree(
			{
				initAjax: { url: "../tree.php?key="+tree_key, data: {filter: filter} },
				clickFolderMode: 2,
				onActivate: function(dtnode) 
				{
					if (dtnode.data.key != '')
					{
						if (dtnode.data.key == 'ANY' || dtnode.data.key == 'key1')
						{
							deleteall(combo);
							addto(combo,'ANY','00000000000000000000000000000000', true);
							dtnode.deactivate();
						} 
						else 
						{
							deletevaluefrom(combo,'ANY','00000000000000000000000000000000');
							if (dtnode.data.key == '02000000000000000000000000000000') {
								deletevaluefrom(combo,'HOME_NET','01000000000000000000000000000000');
							} else if (dtnode.data.key == '01000000000000000000000000000000') {
								deletevaluefrom(combo,'!HOME_NET','02000000000000000000000000000000');
							}
							var key = dtnode.data.key.replace(/;.*/,"");
							addto(combo, dtnode.data.val, key, true);
						}
						
						drawpolicy();
					}
				},
				onDeactivate: function(dtnode) {},
				onLazyRead: function(dtnode)
				{
					dtnode.appendAjax(
					{
						url: "../tree.php",
						data: {key: dtnode.data.key, filter: filter, page: dtnode.data.page}
					});
				}
			});
		}
		
		
		function load_ports_tree()
		{
			combo = (suf=="c") ? 'ports_src' : 'ports_dst';
			
			layer = '#port_tree_'+suf;

            if ($(layer).length < 1)
            {
                $('#containerp'+suf).html('<div id="port_tree_'+suf+'" style="width:100%"></div>');
            }
            else
            {
                $(layer).dynatree("destroy");
            }

			$(layer).dynatree({
				initAjax: { url: "../tree.php?key=ports" },
				clickFolderMode: 2,
				onActivate: function(dtnode) 
				{
					if (dtnode.data.key != '')
					{
						if (dtnode.data.key == 'ANY') 
						{
							deleteall(combo);
							addto(combo,'ANY',0, true);
							dtnode.deactivate();
						} 
						else 
						{
							deletevaluefrom(combo,'ANY',0);
							key = dtnode.data.key.replace("pg_","");
							addto(combo,dtnode.data.url,key, true);
						}
						
						drawpolicy();
					}
				},
				onDeactivate: function(dtnode) {},
				onLazyRead: function(dtnode){
					dtnode.appendAjax({
						url: "../tree.php",
						data: {key: dtnode.data.key}
					});
				}
			});
		}
		
		function load_sensors_tree()
		{
			var tree_key = <?php echo (($open_source) ? "'sensors|any'" : "'se_'+ctx+'|any'") ?>;
			
			layer = '#sensor_tree';

            if ($(layer).length < 1)
            {
                $('#containerse').append('<div id="sensor_tree" style="width:100%"></div>');
            }
            else
            {
                $(layer).dynatree("destroy");
            }

			$(layer).dynatree(
			{
				initAjax: { url: "../tree.php?key="+tree_key },
				clickFolderMode: 2,
				onActivate: function(dtnode) 
				{
					if (dtnode.data.key != '')
					{
						if (dtnode.data.key == 'ANY' || dtnode.data.key == 'key1')
						{
							deleteall('sensors');
							addto('sensors','ANY','00000000000000000000000000000000', true);
							dtnode.deactivate();
						} 
						else 
						{
							deletevaluefrom('sensors','ANY','00000000000000000000000000000000');
							addto('sensors',dtnode.data.val,dtnode.data.key, true);
						}
						
						drawpolicy();
					}
				},
				onDeactivate: function(dtnode) {}
			});
		}
		

		function load_multiselect()
		{
			$.ajax({
				data:  {"action": 2, "data":  {"ctx": ctx, "id": "<?php echo $id ?>"}},
				type: "POST",
				url: "policy_ajax.php", 
				dataType: "json",
				async: false,
				success: function(data)
				{ 
						if (!data.error)
						{
							if (data.data != '')
							{	
								$("#actions").multiselect('destroy');							
								$('#actions').html(data.data);
							}
						} 
					}
			});		
			
			$("#actions").multiselect({
				dividerLocation: 0.5,
				searchable: false,
				selected: function(){
					drawpolicy();
				},
				deselected: function(){
					drawpolicy();
				}
			});

		}
		
		function reload_child_conds(){
		
			if (condition_op=='cond-1' || condition_op=='cond-2') {
				suf = (condition_op=='cond-1') ? 'c' : 'd';
				load_tree($('filter'+suf).val());						
			}
			// default load tree for ports
			if (condition_op=='cond-3' || condition_op=='cond-4'){
				suf = (condition_op=='cond-3') ? 'c' : 'd';
				load_ports_tree();
			}
			// default load tree for sensors
			if (condition_op=='cond-6'){
				load_sensors_tree();
			}
					
			return false;
		}
			
		function GB_onclose()
		{
		
			if (accordion_gb == 1){
				if (condition_op=='cond-1' || condition_op=='cond-2') {
					suf = (condition_op=='cond-1') ? 'c' : 'd';
					load_tree($('filter'+suf).val());						
				}
				// default load tree for ports
				if (condition_op=='cond-3' || condition_op=='cond-4'){
					suf = (condition_op=='cond-3') ? 'c' : 'd';
					load_ports_tree();
				}
				// default load tree for sensors
				if (condition_op=='cond-6'){
					load_sensors_tree();
				}

				if (condition_op=='cond-7') {
					load_targets();
				}
				if (condition_op=='cond-5') {
					load_categories();
					load_plugin_groups();
					drawpolicy();
				}
			} else {
				if (consequence_op=='conseq-1') {
					load_multiselect();
				}
				
			}
			accordion_gb = 1;
		}
		
		
		function disen(val,element,text)
		{
			if (val == 1) {
				element.removeAttr('disabled');
				text.removeClass("thgray");
			} else {
				element.attr('disabled', 'disabled');
				text.addClass("thgray");
			}
		}
		
		function dis(element,text) {
			element.attr('disabled', 'disabled');
			text.addClass("thgray");
		}
		
		function en(element,text) {
			element.removeAttr('disabled');
			text.removeClass("thgray");
		}
	
		// show/hide some options
		function tsim(val)
		{
			disen(val, $('input[name=correlate]'),$('#correlate_text'));
			disen(val, $('input[name=cross_correlate]'),$('#cross_correlate_text'));
			disen(val, $('input[name=store]'),$('#store_text'));
			disen(val, $('input[name=qualify]'),$('#qualify_text'));
			var tooltip = $("#sim_tt");
			val ? tooltip.hide() : tooltip.show();
		}
	
		function tsem(val)
		{
			disen(val, $('input[name=sign]'),$('#sign_text'));
			
			<?php
			if (!$sign_line)
			{
    			echo "$('#sign_line').prop('disabled', true);";
			}
			?>
		}
		
		function tmulti(val) 
		{
			if (val == 1) {
				en($('input[name=resend_alarms]'),$('#ralarms_text'));
				en($('input[name=resend_events]'),$('#revents_text'));
			} else {
				dis($('input[name=resend_alarms]'),$('#ralarms_text'));
				dis($('input[name=resend_events]'),$('#revents_text'));
			}
		}
		
		function submit_form(form)
		{
			if (!$('input[type="button"].sok').prop("disabled"))
			{
				selectall('sources');
				selectall('dests');
				selectall('ports_src');
				selectall('ports_dst');
				selectall('sensors');
				selectall('taxonomy_filters');
				selectall('reputation_filters');
				selectall('event_filters');
				
                <?php if (!$open_source && !empty($server_h))
                {
                ?>
					selectall('frw_filters');
                <?php 
                } 
                ?>
                
                $('#sign_line').prop('disabled', false);
                
				//selectall('idm_filters');
				//selectall('actions');
				form.submit();
			}
		}
		
		function putit(id,txt, elem)
		{
			if (elem.length < 1) {
				$(id).removeClass('bgred').removeClass('bggreen').addClass('bgred');
				$(".img"+id.substr(3)).attr("src","../pixmaps/tables/warning.png");
				$(id).html(txt);
			} else {
				$(id).removeClass('bgred').removeClass('bggreen').addClass('bggreen');
				$(".img"+id.substr(3)).attr("src","../pixmaps/tables/tick.png");
				$(id).html(txt);
			}
		}
		
		function iscomplete()
		{
            var src      = <?php echo ($is_engine)? 'true' : '$(".imgsource").attr("src").match(/tick/);' ?>;
            var dst      = <?php echo ($is_engine)? 'true' : '$(".imgdest").attr("src").match(/tick/);' ?>;
            var port_src = <?php echo ($is_engine)? 'true' : '$(".imgportsrc").attr("src").match(/tick/);' ?>;
            var port_dst = <?php echo ($is_engine)? 'true' : '$(".imgportdst").attr("src").match(/tick/);' ?>;
            var targets  = <?php echo ($open_source)? 'true' : '$(".imgtargets").attr("src").match(/tick/);' ?>;
            var plugins  = $(".imgplugins").attr("src").match(/tick/);
            var forward  = $(".imgforward").attr("src").match(/tick/);
            var descr    = $('#descr').val() != '';
            var time     = $(".imgtime").attr("src").match(/tick/);

			if (src && dst && port_src && port_dst && targets && plugins && forward && descr && time)
            {
				return true;
            }

			return false;
		}
		
		function drawpolicy()
		{
				
			var descr = trim($('#descr').val());
			if (descr.length == 0){
				$('#descr').css('background-color', '#F9F9C2');
				$(".imgdescr").attr("src","../pixmaps/tables/warning.png");

			} else {
				$('#descr').css('background-color', '');
				$(".imgdescr").attr("src","../pixmaps/tables/tick.png");
			}
			
		
			<?php 
            if (!$is_engine) 
            {
            ?>
			var elems = getcombotext('sources');
			txt = "<div class='div_resume'>";
			for (var i=0; i<elems.length; i++) txt = txt + elems[i] + "<br>";
			txt += "</div>";
			putit("#tdsource",txt, elems);
			
			//
			var elems = getcombotext('dests');
			txt = "<div class='div_resume'>";
			for (var i=0; i<elems.length; i++) txt = txt + elems[i] + "<br>";
			txt += "</div>";
			putit("#tddest",txt, elems);
			
			var elems = getcombotext('ports_src');
			txt = "<div class='div_resume'>";
			for (var i=0; i<elems.length; i++) txt = txt + elems[i] + "<br>";
			txt += "</div>";
			putit("#tdportsrc",txt, elems);
			
			var elems = getcombotext('ports_dst');
			txt = "<div class='div_resume'>";
			for (var i=0; i<elems.length; i++) txt = txt + elems[i] + "<br>";
			txt += "</div>";
			putit("#tdportdst",txt, elems);
			//
						
			var elems = getcombotext('sensors');
			txt = "<div class='div_resume'>";
			for (var i=0; i<elems.length; i++) txt = txt + elems[i] + "<br>";
			txt += "</div>";
			putit("#tdsensors",txt, elems);

            <?php } ?>
			
			txt = '';

			$(':checkbox:checked').each(function(i){ 
				if ($(this).attr('id').match(/^target/)) txt = txt + $(this).attr('id').substr(7) + "<br>";
			});

			putit("#tdtargets",txt, $(':checkbox:checked'));
			
			
			//Event Types:
			var event_t  = $("input:radio[name='plug_type']:checked").val();
			
			txt = "<div class='div_resume'>";
			if (event_t == 1){			
				//Taxonomy
				txt += '<b>Taxonomy</b>: <br>';

				var elems = getcombotext('taxonomy_filters');
				
				if (elems.length > 0)
				{					
					for (var i=0; i<elems.length; i++) 
					{
						txt += elems[i] + "<br>";
					}
					
				} 
				else 
				{
					txt += "<b><?php echo _('No Filters')?></b><br>";
				}
			
				elems = txt;
						
			} 
            else
            {
				//DS Groups:
				txt += '<b>DS Groups</b>: <br>';
				
				if ($('#plugin_ANY').is(":checked")) 
                {
					$(':checkbox').each(function(i)
                    { 
						if ($(this).attr('id').match(/^plugin/) && !$(this).attr('id').match(/^plugin_ANY/))
                        {
							$(this).attr("disabled", "disabled").attr('checked',false);
                        }
					});

				} 
                else 
                {
					$(':checkbox').each(function(i)
                    { 
                        if (!$(this).hasClass('disabled'))
                        {
                            if ($(this).attr('id').match(/^plugin/)) 
                            {
                                $(this).removeAttr("disabled");
                            }
                        }
						
					});
				}
				//
				$(':checkbox:checked').each(function(i)
                { 
					txt += $(this).attr('pname') + "<br>";
				});
				
				elems = $(':checkbox:checked');
			
			}
			txt += "</div>";
			putit("#tdplugins",txt, elems);
			
			
			//Event Priority
			txt = "<div class='div_resume'>";
			
			var elems = getcombotext('event_filters');
			
			if (elems.length > 0)
			{
				for (var i=0; i<elems.length; i++) 
				{
					txt += elems[i] + "<br>";
				}
			} 
			else 
			{
				txt += "<b><?php echo _('No Filters')?></b><br>";
			}
			
			txt += "</div>";
			putit("#tdeventprio",txt, txt);
			

			//reputation
			txt = "<div class='div_resume'>";			
			var elems = getcombotext('reputation_filters');
			
			if (elems.length > 0)
			{				
				for (var i=0; i<elems.length; i++)
				{
					txt += elems[i] + "<br>";
				}
			} 
			else 
			{
				txt += "<b><?php echo _('No Filters')?></b><br>";
			}
			txt += "</div>";
			putit("#tdrep",txt, txt);
			
			//Time
			txt           = "<b><?php echo _('Timezone')?>:</b><br>";
			txt           += document.fop.tzone.options[document.fop.tzone.selectedIndex].text+"<br>";
			txt           += "<b><?php echo _('Time Range Type')?>:</b><br>";			
			var tr_type   = $("input:radio[name='date_type']:checked").val();
			
			var tr_hour_b = format_date(document.fop.begin_hour.options[document.fop.begin_hour.selectedIndex].text);
			var tr_min_b  = format_date(document.fop.begin_minute.options[document.fop.begin_minute.selectedIndex].text);
			var tr_hour_e = format_date(document.fop.end_hour.options[document.fop.end_hour.selectedIndex].text);
			var tr_min_e  = format_date(document.fop.end_minute.options[document.fop.end_minute.selectedIndex].text);
			
			if (tr_type==1)
			{
				txt += '<?php echo _('Daily') ?><br>';
				txt += "<br><b><?php echo _('Begin')?>:</b><br>";
				txt += "<?php echo _('Time')?>: <b>" + tr_hour_b + " h : " + tr_min_b + " min</b><br>";
				txt += "<br><b><?php echo _('End')?></b><br>";
				txt += "<?php echo _('Time')?>: <b>" + tr_hour_e + " h : " + tr_min_e + " min</b><br>";
		
			} 
			else if (tr_type==2)
			{
				txt += '<?php echo _('Weekly') ?><br>';
				txt += "<br><b><?php echo _('Begin')?>:</b><br>";
				txt += "<?php echo _('Time')?>: <b>" + tr_hour_b + " h : " + tr_min_b + " min</b><br>";
				txt += "<?php echo _('Week Day')?>: <b>" + document.fop.begin_day_week.options[document.fop.begin_day_week.selectedIndex].text + "</b><br>";
				txt += "<?php echo _('Month')?>: <b>" + document.fop.begin_month.options[document.fop.begin_month.selectedIndex].text + "</b><br>";
				txt += "<br><b><?php echo _('End')?></b><br>";
				txt += "<?php echo _('Time')?>: <b>" + tr_hour_e + " h : " + tr_min_e + " min</b><br>";
				txt += "<?php echo _('Week Day')?>: <b>" + document.fop.end_day_week.options[document.fop.end_day_week.selectedIndex].text + "</b><br>";
				txt += "<?php echo _('Month')?>: <b>" + document.fop.end_month.options[document.fop.end_month.selectedIndex].text + "</b><br>";
				
			} 
			else if (tr_type==3)
			{
				txt += '<?php echo _('Monthly') ?><br>';
				txt += "<br><b><?php echo _('Begin')?>:</b><br>";
				txt += "<?php echo _('Time')?>: <b>" + tr_hour_b + " h : " + tr_min_b + " min</b><br>";
				txt += "<?php echo _('Week Day')?>: <b>" + document.fop.begin_day_week.options[document.fop.begin_day_week.selectedIndex].text + "</b><br>";
				txt += "<?php echo _('Month Day')?>: <b>" + document.fop.begin_day_month.options[document.fop.begin_day_month.selectedIndex].text + "</b><br>";
				txt += "<br><b><?php echo _('End')?></b><br>";
				txt += "<?php echo _('Time')?>: <b>" + tr_hour_e + " h : " + tr_min_e + " min</b><br>";
				txt += "<?php echo _('Week Day')?>: <b>" + document.fop.end_day_week.options[document.fop.end_day_week.selectedIndex].text + "</b><br>";
				txt += "<?php echo _('Month Day')?>: <b>" + document.fop.end_day_month.options[document.fop.end_day_month.selectedIndex].text + "</b><br>";
					
			} 
			else if (tr_type==4)
			{
				txt += '<?php echo _('Custom Range') ?><br>';
				txt += "<br><b><?php echo _('Begin')?>:</b><br>";
				txt += "<?php echo _('Time')?>: <b>" + tr_hour_b + " h : " + tr_min_b + " min</b><br>";
				txt += "<?php echo _('Month Day')?>: <b>" + document.fop.begin_day_month.options[document.fop.begin_day_month.selectedIndex].text + "</b><br>";
				txt += "<?php echo _('Month')?>: <b>" + document.fop.begin_month.options[document.fop.begin_month.selectedIndex].text + "</b><br>";
				txt += "<br><b><?php echo _('End')?></b><br>";
				txt += "<?php echo _('Time')?>: <b>" + tr_hour_e + " h : " + tr_min_e + " min</b><br>";
				txt += "<?php echo _('Month Day')?>: <b>" + document.fop.end_day_month.options[document.fop.end_day_month.selectedIndex].text + "</b><br>";
				txt += "<?php echo _('Month')?>: <b>" + document.fop.end_month.options[document.fop.end_month.selectedIndex].text + "</b><br>";
					
			}			
			putit("#tdtime",txt, txt);

			//Policy consequences
			
			//Actions
			txt = '';
	
			txt += "<div class='div_resume'>";
			
			if ($('#actions option:selected').length <1)
			{
				txt += "<?php echo ('No Actions') ?>";
			} 
			else
			{
				
				$('#actions option:selected').each(function()
				{
					txt += $(this).text() +"<br>";
				});
				
			}
			txt += "</div>";
			
			putit("#tdactions",txt, txt);
						
			
			//SIEM
			txt = '';

			txt +="				<table width='95%' class='transparent' cellSpacing=0 cellPadding=0>";
			txt +="					<tr><td><b><?=_('SIEM')?> </b>(" + ($("input[name='sim']:checked").val()==1 ? "<?=_('Yes')?>" : "<?=_('No')?>") + ")</td></tr>";
			txt +="					<tr><td class='resumep'><br><?=_('Set Event Priority')?>: <b>" + document.fop.priority.options[document.fop.priority.selectedIndex].text + "</b></td></tr>";
			txt +="					<tr><td class='resumep'><?=_('Risk Assessment')?>: <b> " + ($("input[name='qualify']:checked").val()==1 ? "<?=_('Yes')?>" : "<?=_('No')?>") + "</b></td></tr>";
			txt +="					<tr><td class='resumep'><?=_('Logical Correlation')?>: <b> " + ($("input[name='correlate']:checked").val()==1 ? "<?=_('Yes')?>" : "<?=_('No')?>") + "</b></td></tr>";
			txt +="					<tr><td class='resumep'><?=_('Cross-correlation')?>: <b> " + ($("input[name='cross_correlate']:checked").val()==1 ? "<?=_('Yes')?>" : "<?=_('No')?>") + "</b></td></tr>";
			txt +="					<tr><td class='resumep'><?=_('SQL Storage')?>: <b> " + ($("input[name='store']:checked").val()==1 ? "<?=_('Yes')?>" : "<?=_('No')?>") + "</b></td></tr>";
			txt +="				</table>";		
			
			putit("#tdsiem",txt, txt);
			
			//Logger
			txt = '';

			txt +="				<table width='95%' class='transparent' cellSpacing=0 cellPadding=0>";
			txt +="					<tr><td><b><?=_('Logger')?></b> (" + ($("input[name='sem']:checked").val()==1 ? "<?=_('Yes')?>" : "<?=_('No')?>") + ")</td></tr>";
			txt +="					<tr><td class='resumep' <?php echo ($open_source) ? "style='color:gray'" : "" ?>><br><?=_('Sign')?>: <b> " + ($("input[name='sign']:checked").val()==1 ? "<?php echo _('Line')?>" : "<?php echo _('Block')?>") + "</b></td></tr>";
			txt +="				</table>";	
			
			putit("#tdlogger",txt, txt);
			
			
			//Forwarding
			txt = "<div class='div_resume'>";
			txt +="				<table width='95%' class='transparent' cellSpacing=0 cellPadding=0>";
			txt +="					<tr><td><b><?=_('Forward Events')?> </b>(" + ($("input[name='resend_events']:checked").val()==1 ? "<?=_('Yes')?>" : "<?=_('No')?>") + ")</td></tr>";
			txt +="				</table>";
			
			
			<?php 
            if ($open_source) 
            { 
            ?>
				txt += "</div>";
				putit("#tdforward",txt, txt);
			
			<?php 
            }
            else 
            { 
            ?>
				if ($("input[name='resend_events']:checked").val()==1)
				{			
					
					<?php if (is_array($server_h) && !empty($server_h)){  ?>
					var elems = getcombotext('frw_filters');
					<?php } else { ?>
					var elems = '';
					<?php } ?>
					
					if (elems.length > 0){				
						for (var i=0; i<elems.length; i++) 
							txt += elems[i] + "<br>";
						
					} else {
						txt += "<b><?php echo _('No Filters')?></b><br>";
					}
					
					txt += "</div>";
					putit("#tdforward",txt, elems);
				}else{
					txt += "</div>";
					putit("#tdforward",txt, txt);
				}
			
			<?php } ?>
			
			if (iscomplete()) {

				$('input[type="button"].sok').removeAttr('disabled')
			} else {

				$('input[type="button"].sok').attr('disabled', 'disabled');
			}
		}
	
	
		function format_date(val){
		
			if (val < 10)
				return '0'+val;
			else
				return val;
		
		}
		function manual_addto (what,val)
		{
			if (fnValidateIPAddress(val)) {
				if (confirm('<?php echo  Util::js_entities(_("Do you want to add it to the Asset Database?"))?>')) {
					document.getElementById('inventory_loading_'+what).innerHTML = "<img src='../pixmaps/loading.gif' width='20'>";
					$.ajax({
						type: "GET",
						url: "newhost_response.php?host="+val+"&ctx="+ctx,
						data: "",
						dataType:'json',
						success: function(data) {
							document.getElementById('inventory_loading_'+what).innerHTML = "";
							if (data.error){
								alert(data.msg);								
							}else{
								deletevaluefrom(what,'ANY','00000000000000000000000000000000');
								addto(what, data.txt, 'host_'+data.id, true);
								if (condition_op=='cond-1' || condition_op=='cond-2') {
									suf = (condition_op=='cond-1') ? 'c' : 'd';
									load_tree($('filter'+suf).val());						
								}
								drawpolicy();
								alert(data.msg);					
							}							
						}
					});
				}
			} else {
				alert("<?=_("Type a correct IPv4 address")?>");
			}
		}
	
		function fnValidateIPAddress(ipaddr)
		{
			//Remember, this function will validate only Class C IP.
			//change to other IP Classes as you need
			ipaddr = ipaddr.replace( /\s/g, "") //remove spaces for checking

			var re = /^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/; //regex. check for digits and in
												  //all 4 quadrants of the IP
			if (re.test(ipaddr)) {
				//split into units with dots "."

				var parts = ipaddr.split(".");
				//if the first unit/quadrant of the IP is zero
				//if (parseInt(parseFloat(parts[0])) == 0) {
				//	return false;
				//}
				//if the fourth unit/quadrant of the IP is zero

				//if (parseInt(parseFloat(parts[3])) == 0) {
				//	return false; 
				//}
				
                //if any part is less than 0
                if (parseInt(parseFloat(parts[0])) < 0 || parseInt(parseFloat(parts[1])) < 0 ||
                    parseInt(parseFloat(parts[2])) < 0 || parseInt(parseFloat(parts[3])) < 0) {
                    return false;
                }
                //if any part is greater than 255
				for (var i=0; i<parts.length; i++) {
					if (parseInt(parseFloat(parts[i])) > 255){
						return false;
					}
				}
				return true;
			} else {
				return false;
			}
		}
		
		
		function clean_condition(cond_id){
		
			if (cond_id == 6){
				deleteall('sensors');
				addto('sensors','ANY','00000000000000000000000000000000', true);
			} else if (cond_id == 9){
				deleteall('reputation_filters');
			} else if (cond_id == 11){

				$("#tzone option[value='<?php echo $default_tz ?>']").attr("selected","selected") ;
				$("input:radio[name='date_type']").filter('[value=1]').attr('checked', true);
				
				document.fop.begin_hour.selectedIndex      = 0;
				document.fop.end_hour.selectedIndex        = 23;
				
				document.fop.begin_minute.selectedIndex    = 0;				
				document.fop.end_minute.selectedIndex      = 59;						
			
				document.fop.begin_day_week.selectedIndex  = 0;
				document.fop.end_day_week.selectedIndex    = 0;
				
				document.fop.begin_day_month.selectedIndex = 0;
				document.fop.end_day_month.selectedIndex   = 0;
				
				document.fop.begin_month.selectedIndex     = 0;		
				document.fop.end_month.selectedIndex       = 0;
			
			} else if (cond_id == 12){
				deleteall('sensor_filters');
			}
			
			drawpolicy();
			
			return false;		
		
		}
		
		
		function load_targets(){
			$('#targets').html(reloading);
			$.ajax({
				data:  {"action": 3, "data":  {"id": "<?php echo $id ?>"}},
				type: "POST",
				url: "policy_ajax.php", 
				dataType: "json",
				async: false,
				success: function(data){ 
						if (!data.error){
							if (data.data != ''){								
								$('#targets').html(data.data);
							}
						} 
					}
			});
			
			return true;
		}
		
		
		function load_plugin_groups()
        {
			elems = $(':checkbox:checked');
			
			$.ajax(
            {
				data:  {"action": 4, "data":  {"ctx": ctx, "id": "<?php echo $id ?>"}},
				type: "POST",
				url: "policy_ajax.php", 
				dataType: "json",
				async: false,
				success: function(data)
                { 
					if (!data.error)
                    {
						if (data.data != '')
                        {								
							$('#plugins').html(data.data);
							$("#plugins a.greybox").click(function(){
								var t = this.title || $(this).text() || this.href;
								GB_show(t,this.href,490,"80%");
								return false
							});

                            $('.tiptip_dg').tipTip();
						}
					} 
				}
			});
			
			elems.each(function()
            {
				var id = $(this).attr("id");
				$('#'+id).attr("checked","checked");
			});
					
			return true;
		}
		 		
		function load_categories(){
			$('#tax_cat').html('<option>'+reloading+'</option>');
			$.ajax({
				data:  {"action": 5, "data":  {"ctx": ctx}},
				type: "POST",
				url: "policy_ajax.php", 
				dataType: "json",
				async: false,
				success: function(data){ 
						if (!data.error){
							if (data.data != ''){								
								$('#tax_cat').html(data.data);
							}
						} 
					}
			});
			load_subcategories();
			return true;
		}
		
		
		function load_subcategories(cat_id){
			$('#tax_subc').html('<option>'+reloading+'</option>');
			$.ajax({
				data:  {"action": 6, "data":  {"id": cat_id, "ctx": ctx}},
				type: "POST",
				url: "policy_ajax.php", 
				dataType: "json",
				async: false,
				success: function(data){ 
						if (!data.error){
							if (data.data != ''){								
								$('#tax_subc').html(data.data);
							}
						} 
					}
			});
			
			return true;
		}
		
		
		function load_policy_groups(){
			
			<?php $selected = ($group == "0") ? "selected" : ""; ?>
						
			$.ajax({
				data:  {"action": 1, "data":  {"ctx": ctx, "id": "<?php echo $group ?>"}},
				type: "POST",
				url: "policy_ajax.php", 
				dataType: "json",
				success: function(data){ 
						if (!data.error){
							if (data.data != ''){
								$('#groups').html(data.data);
								drawpolicy();
							}
						} 
					}
			});
		
		}
		
		
		
		function trim(myString)
		{
			return myString.replace(/^\s+/g,'').replace(/\s+$/g,'').replace(/@/g,'');
		}
		
		
		function add_tax_filter(){
		
			var ptype_id   = trim($('#tax_pt').val());
			var ptype_txt  = $('#tax_pt option:selected').text();
			var cat_id     = trim($('#tax_cat').val());
			var cat_txt    = $('#tax_cat option:selected').text();
			var subcat_id  = trim($('#tax_subc').val());
			var subcat_txt = $('#tax_subc option:selected').text();
			


			var filter_id  = ptype_id+'@'+cat_id+'@'+subcat_id;
			var filter_txt = ptype_txt+' | '+cat_txt+' | '+subcat_txt;
			addto('taxonomy_filters',filter_txt,filter_id, true);
			
			$('#tax_pt').val(0);
			$('#tax_cat').val(0);
			$('#tax_cat').trigger('change');
			
			drawpolicy();
			
			return false;

		}
		
		function add_rep_filter(){
		
			var act_id  = trim($('#rep_act').val());
			var act_txt = $('#rep_act option:selected').text();
			var sev_id  = trim($('#rep_sev').val());
			var rel_id  = trim($('#rep_rel').val());
			var dir_id  = trim($("input:radio[name='rep_dir']:checked").val());
			var dir_txt = (dir_id == 1) ? '<?php echo _("Dest")?>.' : '<?php echo _("Src")?>.';

			var rep_sev_lem = $('#rep_sev_lem').val();
                        var rep_rel_lem = $('#rep_rel_lem').val();
			sev_counter = more_less_switcher(sev_id,rep_sev_lem);
			rel_counter = more_less_switcher(rel_id,rep_rel_lem);
			for (var i = sev_counter[0]; i<=sev_counter[1]; i++) {
				for (var j = rel_counter[0]; j<=rel_counter[1]; j++) {
		                        var filter_id  = act_id + '@' + i + '@' + j + '@' + dir_id;
        				var filter_txt = act_txt + ' | ' + i + ' | ' + j + ' | ' + dir_txt;
					addto('reputation_filters',filter_txt,filter_id, true);
				}
			}
			$('#rep_act,#rep_sev,#rep_rel').val(0);
			$('#rep_sev_lem,#rep_rel_lem').val("equal");
			$("input:radio[name='rep_dir']").filter('[value=0]').attr('checked', true);
			
			drawpolicy();
			
			return false;
			
		}

		function more_less_switcher(id,flag,max,min) {
			if (!max) max = 10;
                        if (!min) min = 1;
			var start = end = id;
                        if (flag == "less") {
                                start = min;
                                end--;
                        } else if (flag == "more") {
                                end = max;
                                start++;
                        }
			return [start,end];
		}
		
		function add_event_filter(){
		

			var sev_id  = trim($('#ev_sev').val());
			var rel_id  = trim($('#ev_rel').val());

                        var sev_lem = $('#ev_sev_lem').val();
                        var rel_lem = $('#ev_rel_lem').val();
                        sev_counter = more_less_switcher(sev_id,sev_lem,5);
                        rel_counter = more_less_switcher(rel_id,rel_lem);
                        for (var i = sev_counter[0]; i<=sev_counter[1]; i++) {
                                for (var j = rel_counter[0]; j<=rel_counter[1]; j++) {
                                        var filter_id  = i + '@' + j;
                                        var filter_txt = 'Prio: ' + i + ' | Rel: ' + j;
		                        addto('event_filters',filter_txt,filter_id, true);
                                }
                        }
			$('#ev_sev,#ev_rel').val(0);
			$('#ev_sev_lem,#ev_rel_lem').val("equal");
			drawpolicy();
			return false;
		}
		
		function check_exist(selector, sid){
			var exist = false;
			$('#'+selector + ' option').each(function(){  
				var id = $(this).val().split('@');
				id = id[0];
				if (id == sid)
					exist = true;
			});  
			return exist;
		}
		
		function add_frw_filter(){
		

			var server_id  = trim($('#frw_ser').val());
			var server_txt = trim($('#frw_ser option:selected').text());
			var prio	   = trim($('#frw_prio').val());

			if (!check_exist('frw_filters', server_id)){
				var filter_id  = server_id + '@' + prio;
				var filter_txt = server_txt + ': ' + prio;
				addto('frw_filters',filter_txt,filter_id, true);

				$('#frw_ser').val(0);
				$('#frw_prio').val(0);
				
				drawpolicy();
			}
			return false;
			
		}
		
		function toggle_accordions(conds){
		
			if (conds){
				$("#consequences").hide();
				$("#conditions").show();
			
			} else {
				$("#conditions").hide();
				$("#consequences").show();
			}
			
			return false;
		
		}
		
		function open_accordion(name, item){
			if (name == 'cond'){
				toggle_accordions(true);
				$('li.accond-'+item).find('h2').trigger('click');
			} else {
				toggle_accordions(false);
				$('li.accons-'+item).find('h2').trigger('click');
			}
		
			return false;
		
		}
		
		function draw_conditions_accordion(first){

			if (typeof(first) == 'undefined')
            {
				first = 1;
			} 
            else 
            {
				first = get_accordion_child(first);
			}
		
            
			$('#conditions').liteAccordion(
            { 
				theme: 'avtheme', 
				containerWidth: acc_width,
				containerHeight: 300,
				headerWidth: 30,
				slideSpeed: 500,
				rounded: false, 
				firstSlide: first, 
				onSlideAnimComplete: function() 
                {
					condition_op = $(this).attr('id');
					
					reload_child_conds();

					drawpolicy();
				},
				onLadAccordion: function()
                {
					$('#conditions').css('visibility', 'visible');
				}
			});
			
			$('#conditions').css('margin','5px auto 0 auto');


			return false;
		
		}
		
		function draw_consequences_accordion(){
			$('#consequences').liteAccordion({ 
				theme : 'avtheme', 
				containerWidth : acc_width,
				containerHeight : 300,
				headerWidth: 30,
				slideSpeed : 500,
				rounded : false, 
				firstSlide : 1, 
				onSlideAnimComplete  : function() {
					consequence_op = $(this).attr('id');
					// default load of actions
					//if (consequence_op=='conseq-1') load_multiselect();
					
					drawpolicy();
				},
				onLadAccordion  : function() {
					$('#consequences').css('visibility', 'visible');
				}
			});
			
			$('#consequences').css('margin','5px auto 0 auto');
			
			return true;
		
		}
		
		
		function get_accordion_child(id)
        {	
			var index = 1;
			var i     = 1;
			$('#conditions').children('ol').children('li:visible').each(function()
            {		
				if ($(this).hasClass('accond-'+id))
                {
					index = i;
					return true;
				}

				i ++;
			});

			return index;
		}
		
		function redraw_accordion(id)
        {
			condition_op = 'cond-'+id;
			$('#conditions').liteAccordion('destroy');			
			draw_conditions_accordion(id);	
			setTimeout('reload_child_conds()', 300);
		}
		
		
		function change_date_type(){
		
			var option = $("input:radio[name='date_type']:checked").val();
			
			if (option == 1){			
				$('#beginweekday, #beginmonthday, #endweekday, #endmonthday, #beginmonth, #endmonth').hide();
			
			} else if (option == 2) {
				$('#beginmonthday, #endmonthday').hide();
				$('#beginweekday, #endweekday, #beginmonth, #endmonth').show();
			
			} else if (option == 3) {
				$('#beginweekday, #beginmonthday, #endweekday, #endmonthday').show();
				$('#beginmonth, #endmonth').hide();				
			
			} else if (option == 4) {
				$('#beginmonthday, #endmonthday, #beginmonth, #endmonth').show();
				$('#beginweekday, #endweekday').hide();
			
			}
			
			drawpolicy();
			
			return false;
		
		}
		
		function change_event_type(type){
			
			if (type == 0){
				
				$('#txn').hide();
				$('#dsg').show();
			
			} else{
			
				$('#dsg').hide();
				$('#txn').show();
			
			}
		
			drawpolicy();
		}

		function show_frw_opts(flag){
			
			<?php if (!$open_source){ ?>
			if (flag == 1){
				$('#forw_opts').show();
			}else{
				$('#forw_opts').hide();
			}
			<?php } ?>
			drawpolicy();
		}
		
		$(document).ready(function()
		{
		
			// Textareas
			$('textarea').elastic();
			//initialize tooltips on startup
			$('.av_tooltip').tipTip();
            acc_width = $('body').width() - 4;
						
			load_multiselect();
						
			load_categories();
			
			change_date_type();

			<?php if (!$open_source) { ?>
			load_targets();
			<?php } ?>
			
			load_tree();
			load_policy_groups();
            			
			// graybox
			$("a.greybox").click(function(){
			   var t = this.title || $(this).text() || this.href;
			   GB_show(t,this.href,580,"80%");
			   return false;
			});
			
			$("a.greybox2").click(function(){
			   var t = this.title || $(this).text() || this.href;
			   CB_show(this,t,this.href,95,120);
			   return false;
			});
			
			drawpolicy();
			
			$('.img_delc').click(function(e){
				e.stopPropagation();
				if (confirm("<?php echo ('This option is going to be hidden. Are you sure?') ?>")){
					var aux = $(this).closest('li').attr('class').match(/accond\-[0-9]+/g);
					if (aux.length > 0){
						var id = aux[0].replace('accond-', '');
						$('.accond-'+id).hide();
						redraw_accordion(condition_op.replace('cond-', ''));
						clean_condition(id);
					}
				}
			
			});
			
			$('.tiptip, .tiptip_dg').tipTip();

            setTimeout(function()
            {
                draw_conditions_accordion();            
                draw_consequences_accordion();

                $('#consequences').hide();

            }, 500)

		});
		
		
	
	</script>
	
</head>
<body>
          

<form method="POST" name="fop" action="newpolicy.php">

<div style='margin:25px auto 0 auto;'>
	<table width="100%" class='noborder policy_header'>
		<tr>
			<td width='34%' class="left nobborder">
				<table width='100%' class='noborder policy_header' >
					<tr>
                        <td class="left nobborder">
                            <span><?php echo _("Policy Rule Name")?>: *</span>
                        </td>
                        <td class="left nobborder" nowrap='nowrap'>
                            <input name="descr" onkeyup='drawpolicy();' id='descr' value='<?php echo $desc?>' style="width:200px">  
                            <img src="../pixmaps/tables/warning.png" class="imgdescr" align="top"/>
                        </td>
					</tr>
				</table>

			</td>
			
			<td  width='32%' class="nobborder">
				<table class='noborder policy_header' align='center' width='100%'>
					<tr>
						<td class="center nobborder">
							<span><?php echo _("Enable")?>: *</span>
							<input type="radio" name="active" value="1" <?php echo ($active == 1) ? "checked='checked'" : "" ?>/> <?php echo _("Yes"); ?>
							<input type="radio" name="active" value="0" <?php echo ($active == 0) ? "checked='checked'" : "" ?>/> <?php echo _("No"); ?>
						</td>
					</tr>
				</table>				
			</td>
			
			<td width='34%' class="left nobborder">
				<table class='noborder policy_header' align='right'>
					<tr>
						<td class="left nobborder"><span><?php echo _("Policy Group")?>: *</span></td>
						<td class="left nobborder">
							<select name="group" style="width:200px" id="groups" onchange="">
							<?php
							if ($group == "0")
							{
							?>
								<option value='0' selected="selected"><?php echo _("Default Policy Group") ?></option>
							<?php
							} 
							else 
							{
								$name = Policy_group::get_name_by_id($conn, $group);
								echo "<option value='$group' selected='selected'>$name</option>";
							}					
							?>
							</select>
						</td>
					</tr>
				</table>

							
			</td>
		</tr>
	</table>
</div>

<div id='policy_resume'>

	<table width="100%" class='transparent resume_c'>
		<tr>
			<td class='noborder'>
				<table width="100%" class='transparent resume_c'>
					<tr>
						<th><?php echo _('Conditions') ?></th>
					</tr>
					<tr>
						<td class='noborder'>
							<table width="100%" class='noborder resume'>
								<tr>		
                                    <?php if (!$is_engine) { ?>
									<th nowrap='nowrap' class='accond-1'>
										<?php echo _("Source")?> <img src="../pixmaps/tables/warning.png" class="img_resume imgsource" align="top"/>
									</th>
									<th nowrap='nowrap' class='accond-2'>
										<?php echo _("Dest")?> <img src="../pixmaps/tables/warning.png" class="img_resume imgdest" align="top"/>
									</th>
									<th nowrap='nowrap' class='accond-3'>
										<?php echo _("Src Ports") ?> <img src="../pixmaps/tables/warning.png" class="img_resume imgportsrc" align="top"/>
									</th>
									<th nowrap='nowrap' class='accond-4'>
										<?php echo _("Dest Ports") ?> <img src="../pixmaps/tables/warning.png" class="img_resume imgportdst" align="top"/>
									</th>
                                    <?php } ?>
									<th nowrap='nowrap' class='accond-5'>
										<?php echo _("Event Types")?> <img src="../pixmaps/tables/warning.png" class="img_resume imgplugins" align="top"/>
									</th>
                                    <?php if (!$is_engine) { ?>
									<th nowrap='nowrap' class='accond-6' <?php echo($flag_sensors) ? " style='display:none'" : ""?>>
										<?php echo _("Sensors")?> <img src="../pixmaps/tables/warning.png" class="img_resume imgsensors" align="top"/>
									</th>
                                    <?php } ?>
									<th <?php echo (true) ? " style='display:none'" : "nowrap='nowrap'"?> class='accond-7'>
										<?php echo _("Install on") ?> <img src="../pixmaps/tables/warning.png" class="img_resume imgtargets" align="top"/>
									</th>
									<th nowrap='nowrap' class='accond-9' <?php echo($flag_reputation) ? " style='display:none'" : ""?>>
										<?php echo _("Reputation")?> <img src="../pixmaps/tables/tick-small.png" class="img_resume imgrep" align="top"/>
									</th>	
									<th nowrap='nowrap' class='accond-12' <?php echo($flag_event_prio) ? " style='display:none'" : ""?>>
										<?php echo _("Event Priority")?> <img src="../pixmaps/tables/tick-small.png" class="img_resume imgeventprio" align="top"/>
									</th>										
									<th nowrap='nowrap' class='accond-11' <?php echo($flag_time) ? " style='display:none'" : ""?>>
										<?php echo _("Time Range")?> <img src="../pixmaps/tables/warning.png" class="img_resume imgtime" align="top"/>
									</th>
								</tr>
								<tr>	
                                    <?php 
                                    if (!$is_engine) 
                                    { 
                                    ?>	
    									<td id="tdsource"  class="small accond-1" style="cursor:pointer;width:420px" onclick="open_accordion('cond', 1);" ></td>
    									<td id="tddest"    class="small accond-2" style="cursor:pointer;width:420px" onclick="open_accordion('cond', 2);"></td>
    									<td id="tdportsrc" class="small accond-3" style="cursor:pointer;width:420px;" onclick="open_accordion('cond', 3);"></td>
    									<td id="tdportdst" class="small accond-4" style="cursor:pointer;width:420px;" onclick="open_accordion('cond', 4);"></td>
    								<?php 
									} 
									?>
									
                                    <td id="tdplugins" class="small accond-5" style="cursor:pointer;width:420px;" onclick="open_accordion('cond', 5);"></td>
									
									<?php 
									if (!$is_engine) 
									{ 
									?>
                                        <td id="tdsensors" class="small accond-6" style="cursor:pointer;width:420px;<?php echo ($flag_sensors) ? " display:none;" : ""?>" onclick="open_accordion('cond', 6);"></td>
									<?php 
									}
									?>
                                    <td id="tdtargets" class="small accond-7" 			style="cursor:pointer;width:420px;<?php echo (true) ? " display:none;" : ""?>" onclick="open_accordion('cond', 7);"></td>
									<td id="tdrep"     class="small accond-9" 			style="cursor:pointer;width:420px;<?php echo ($flag_reputation) ? " display:none;" : ""?>" onclick="open_accordion('cond', 9);"></td>
									<td id="tdeventprio"     class="small accond-12" 			style="cursor:pointer;width:420px;<?php echo ($flag_event_prio) ? " display:none;" : ""?>" onclick="open_accordion('cond', 12);"></td>
									<td id="tdtime"    class="small accond-11" 			style="cursor:pointer;width:420px;<?php echo ($flag_time) ? " display:none;" : ""?>" onclick="open_accordion('cond', 11);"></td>
								</tr>			
							</table>
						</td>
					</tr>
				</table>
			</td>
			<td class='noborder'>
				<table width="100%" class='transparent resume' valign='top' >
					<tr>	
						<td class='noborder' style='width:18px;'>
							&nbsp;
						</td>	
					</tr>
					<tr>
						<td class='noborder'>
							<table width="100%" class='transparent resume'>
								<tr>		
									<td style='height:222px;border:none;'>&nbsp;</td>
								</tr>			
							</table>
						</td>
					</tr>
				</table>
			</td>
			<td class='noborder'>
				<table width="100%" class='transparent resume_c'>
					<tr>
						<th><?php echo _('Consequences') ?></th>
					</tr>
					<tr>
						<td class='noborder'>
							<table width="100%" class='noborder resume'>
								<tr>		
									<th>
										<?php echo _("Actions") ?> <img src="../pixmaps/tables/warning.png" class="img_resume imgactions" align="top"/>
									</th>
									<th>
										<?php echo _("SIEM") ?> <img src="../pixmaps/tables/warning.png" class="img_resume imgsiem" align="top"/>
									</th>
									<?php 
									if (!$is_engine) 
									{ 
									?>
    									<th>
    										<?php echo _("Logger") ?> <img src="../pixmaps/tables/warning.png" class="img_resume imglogger" align="top"/>
    									</th>
									<?php 
									} 
									?>
									<th>
										<?php echo _("Forwarding") ?> <img src="../pixmaps/tables/warning.png" class="img_resume imgforward" align="top"/>
									</th>
									
								</tr>
								<tr>		
									<td id="tdactions" class="small" style="cursor:pointer;width:420px" onclick="open_accordion('cons', 1);" nowrap='nowrap'></td>
									<td id="tdsiem"    class="small" style="cursor:pointer;width:420px" onclick="open_accordion('cons', 2);" nowrap='nowrap'></td>
									<?php 
									if (!$is_engine) 
									{ 
									?>
    									<td id="tdlogger"  class="small" style="cursor:pointer;width:420px" onclick="open_accordion('cons', 3);" nowrap='nowrap'></td>
									<?php 
									} 
									?>
									<td id="tdforward" class="small" style="cursor:pointer;width:420px" onclick="open_accordion('cons', 4);" nowrap='nowrap'></td>
								</tr>			
							</table>
						</td>
					</tr>
				</table>
			</td>
			
		</tr>
	</table>
</div>


	<input type="hidden" name="ctx" value="<?php echo $ctx ?>"/>
	<input type="hidden" name="order" value="<?php echo $order ?>"/>
	<?php 
	if ($id != ""){ 
	
		if ($clone == 1){
			echo "<input type='hidden' name='action' value='clone'/>";
		
		} else {
			echo "<input type='hidden' name='action' value='edit'/>";
		}
		
		echo "<input type='hidden' name='policy_id' value='$id'/>";
	
	} else { 
		echo "<input type='hidden' name='action' value='new'/>";
	
	} ?>

		
		
		
<br><br>

<div>
	<div class='fleft'>
	<a href='javascript:;' onclick='toggle_accordions(true);'>
	   <img align="absmiddle" border="0" src="/ossim/pixmaps/arrow_green.gif"/> <?php echo _('POLICY CONDITIONS') ?> 
    </a>
	</div>
	
	<div class='fright'>
		<a href="accordion_options.php" class="greybox2" title='<?php echo _('Conditions') ?>'> 
    		<img align="absmiddle" border="0" src="/ossim/pixmaps/plus-small.png"/> <?php echo _('ADD MORE CONDITIONS') ?>
        </a>
	</div>
</div>

<br>

<div id="conditions">
	<ol>
    <?php if (!$is_engine) { ?>
		<li class="accond-1">
			<h2>
				<div class='finished'>
					<div class='div_left'><?php echo _("SOURCE") ?></div>
					<div class='div_right'><img src="/ossim/pixmaps/tables/warning.png" class="imgsource img_rotate" /></div>
				</div>
			</h2>
			<div id="cond-1">
				<div class='wrap_acc'>
					<table class='tab_table'>
						<tr>
							<td class="nobborder" valign="middle">
								<table align="center" class="noborder">
									<tr>
										<th style="background-position:top center"><?php echo _("Source") . required() ?><br/>
											<span class='size10'>
                                                <a href="<?php echo $asset_form_url ?>" class="greybox">
                                                    <?php echo _("Insert new asset?") ?>
                                                </a>
                                            </span>
                                            </br>
                                            <span class='size10'>
                                                <a href="<?php echo $net_form_url ?>" class="greybox">
                                                    <?php echo _("Insert new net?") ?>
                                                </a>
                                            </span>
                                            </br>
                                            <span class='size10'>
                                                <a href="/ossim/netgroup/netgroup_form.php" class="greybox">
                                                    <?php echo _("Insert new net group?") ?>
                                                </a>
                                            </span>
										</th>
										<td class="left nobborder">
											<select id="sources" name="sources[]" size="18" multiple="multiple" style="width:200px">
                                            <?php 
                                                foreach ($sources as $id => $source) 
                                                {
                                                    echo "<option value='$id'>$source</option>"; 
                                                }
                                            ?>
											</select>
											<input type="button" class="small av_b_secondary" value=" [X] " onclick="deletefrom('sources');drawpolicy()">
										</td>
									</tr>
								</table>
							</td>
							<td valign="top" class="nobborder">
								<table class="noborder" align='center'>
									<tr><td class="left nobborder" id="inventory_loading_sources"></td></tr>
									<tr>
										<td class="left nobborder">
											<?php echo _("Asset")?>: 
											<input type="text" id="filterc" name="filterc" size='20'/>
											
											<input type="button" class="small av_b_secondary" value="<?php echo _("Filter")?>" onclick="load_tree(this.form.filterc.value)"> 
											
											<input type="button" class="small av_b_secondary" value="<?php echo _("Insert")?>" onclick="manual_addto('sources',this.form.filterc.value)">
											
											<div id="containerc" class='container_tree_button'></div>
											
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</li>
		
		<li class="accond-2">
			<h2>
				<div class='finished'>
					<div class='div_left'><?php echo _("DESTINATION") ?></div>
					<div class='div_right'><img src="../pixmaps/tables/warning.png" class="imgdest img_rotate" /></div>
				</div>
			</h2>
			<div id="cond-2">
				<div class='wrap_acc'>
					<table class='tab_table'>
						<tr>
							<td class="nobborder" valign="middle">
								<table align="center" class="noborder">
									<tr>
										<th style="background-position:top center"><?php echo _("Destination") . required() ?><br/>
                                            <span class='size10'>
                                                <a href="<?php echo $asset_form_url ?>" class="greybox">
                                                    <?php echo _("Insert new asset?") ?>
                                                </a>
                                            </span>
                                            </br>
                                            <span class='size10'>
                                                <a href="<?php echo $net_form_url ?>" class="greybox">
                                                    <?php echo _("Insert new net?") ?>
                                                </a>
                                            </span>
                                            </br>
                                            <span class='size10'>
                                                <a href="/ossim/netgroup/netgroup_form.php" class="greybox">
                                                    <?php echo _("Insert new net group?") ?>
                                                </a>
                                            </span>
										</th>
										<td class="left nobborder" valign="top">
											<select id="dests" name="dests[]" size="18" multiple="multiple" style="width:200px">
                                            <?php 
                                                foreach ($dests as $id => $dest) 
                                                {
                                                    echo "<option value='$id'>$dest</option>"; 
                                                }  
                                            ?>
											</select>
											<input type="button" value=" [X] " onclick="deletefrom('dests');drawpolicy()" class="small av_b_secondary">
										</td>
									</tr>
								</table>
							</td>
							
							<td valign="top" class="nobborder">
								<table class="noborder">
									<tr><td class="left nobborder" id="inventory_loading_dests"></td></tr>
									<tr>
										<td class="left nobborder" valign="top">
											<?php echo _("Asset")?>: <input type="text" id="filterd" name="filterd" size='20'/>&nbsp;
											<input type="button" class="small av_b_secondary" value="<?=_("Apply")?>" onclick="load_tree(this.form.filterd.value)" />
											<input type="button" class="small av_b_secondary" value="<?=_("Insert")?>" onclick="manual_addto('dests',this.form.filterd.value)"/>
											<div id="containerd" class='container_tree_button'></div>
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</li>
		
		<li class="accond-3">
			<h2>
				<div class='finished'>
					<div class='div_left'><?php echo _("SOURCE PORTS") ?></div>
					<div class='div_right'><img src="../pixmaps/tables/warning.png" class="imgportsrc img_rotate" /></div>
				</div>
			</h2>
			<div id="cond-3">
				<div class='wrap_acc'>
					<table class='tab_table'>
						<tr>
							<td class="nobborder" valign="middle">
								<table align="center" class="noborder">
									<tr>
										<th style="background-position:top center"><?php echo _("Source Ports") . required() ?><br/>
											<span class='size10'><a href="../port/newportgroupform.php" class="greybox"><?php echo _("Insert new port group?") ?></a></span><br/>
										</th>
										<td class="left nobborder" valign="top">
											<select id="ports_src" name="portsrc[]" size="18" multiple="multiple" style="width:210px">
											<?php 
                                                foreach ($ports_source as $pgkey => $pgrp) 
                                                {
                                                    echo "<option value='$pgkey'>$pgrp</option>"; 
    								            }
											?>
											</select>
											
											<input type="button" value=" [X] " class="small av_b_secondary" onclick="deletefrom('ports_src');drawpolicy();">
										</td>
									</tr>
								</table>
							</td>
							
							<td valign="top" class="nobborder">
								<table class="noborder">
									<tr>							
										<td class="left nobborder" valign="top">
											<div id="containerpc" class='container_tree'></div>
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</li>
		
		<li class="accond-4">
			<h2>
				<div class='finished'>
					<div class='div_left'><?php echo _("DEST PORTS") ?></div>
					<div class='div_right'><img src="../pixmaps/tables/warning.png" class="imgportdst img_rotate" /></div>
				</div>
			</h2>
			<div id="cond-4">
				<div class='wrap_acc'>
					<table class='tab_table'>
						<tr>
							<td class="nobborder" valign="middle">
								<table align="center" class="noborder">
									<tr>
										<th style="background-position:top center"><?php echo _("Destination Ports") . required() ?><br/>
											<span class='size10'><a href="../port/newportgroupform.php" class="greybox"><?php echo _("Insert new port group?") ?></a></span><br/>
										</th>
										<td class="left nobborder" valign="top">
											<select id="ports_dst" name="portdst[]" size="18" multiple="multiple" style="width:200px">
											<?php 
                                            foreach ($ports_destiny as $pgkey => $pgrp)
                                            {
                                                echo "<option value='$pgkey'>$pgrp</option>"; 
                                            }    												
								            ?>
											</select>
											<input type="button" value=" [X] " class="small av_b_secondary" onclick="deletefrom('ports_dst');drawpolicy();">
										</td>
									</tr>
								</table>
							</td>
							
							<td valign="top" class="nobborder">
								<table class="noborder">
									<tr>							
										<td class="left nobborder" valign="top">
											<div id="containerpd" class='container_tree'></div>
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</li>

        <?php } ?>
		
		<li class="accond-5">
			<h2>
				<div class='finished'>
					<div class='div_left'><?php echo _("EVENT TYPES") ?></div>
					<div class='div_right'><img src="../pixmaps/tables/warning.png" class="imgplugins img_rotate" /></div>
				</div>
			</h2>
			<div id="cond-5">
				<div class='wrap_acc'>
					<table class='tab_table'>
						<?php 
                        if (!$is_engine) 
                        { 
                        ?>
						<tr>
							<td style='height:48px;'>
								<div>
									<?php echo _('Choose between DS Groups and Taxonomy') ?>
								</div>
								<div>
									<input type="radio" name="plug_type" value="0" onclick="change_event_type(0);" <?php echo ($flag_events) ? "checked='checked'": "" ?>/> <?php echo _("DS Groups")?>
									<input type="radio" name="plug_type" value="1" onclick="change_event_type(1);" <?php echo (!$flag_events) ? "checked='checked'": "" ?>/> <?php echo _("Taxonomy")?>
								</div>
							</td>
						</tr>
						<?php 
                        } 
                        else 
                        {
							echo "<input type='hidden' name='plug_type' value='0'>";
						} 
				        ?>
						<tr>
							<td class='noborder' valign="top">
								<div id='dsg' style='height:100%;width:100%;<?php echo ($flag_events) ? "": "display:none;" ?>' >
									<table class='tab_table' align='center' valign="middle" height='95%' width='95%' style='padding-top:10px'>
										<tr>
											<td class="nobborder" valign="middle" width='15%'>
												<table align="right" class="noborder" width='100%' height='100%'>
													<tr>
														<th style="background-position:top"> <?php echo _("DS Groups") . required() ?> <br/>
															<span class='size10'><a href="modifyplugingroupsform.php?action=new" class="greybox"> <?php echo gettext("Insert new DS Group?"); ?> </a></span><br/>
															<span class='size10'><a href="plugingroups.php" class="greybox"> <?php echo gettext("View all DS Groups"); ?></a></span><br/>
														</th>
													</tr>
												</table>
											</td>
											
											<td valign="middle" class="nobborder" width='85%'>												
												<table class="noborder" width='100%' height='100%' align='center'>
													<tr>
														<td style="border-bottom: 1px dotted BLACK;padding-bottom:5px; text-align:center;height: 30px">
															<?php if (!$is_engine) 
															{ 
															?>		
																<input type="checkbox" id="plugin_ANY" pname="<?php echo _('ANY') ?>"  onclick="drawpolicy()" name="plugins[0]" <?php echo (in_array('00000000000000000000000000000000' , $plugingroups)) ? "checked='checked'" : "" ?>/> <?php echo _("ANY") . required()?>
															<?php } else {?>
																<span style='color:#A0A0A0'>
																	<?php echo _('Policies generated in servers only allow directive plugin groups') ?>
																</span>										
															<?php } ?>
														</td>
													</tr>
													<tr>
														<td  class='noborder' id="plugins" style="text-align:left;padding-top:5px;">
															<div style='height:<?php echo ($is_engine)? '200px' : '140px'?>;overflow:auto'>
																<table width="100%" class="transparent" cellspacing="0" cellpadding="0">
																	<tr>
																		<?php
																		$iplugin = 1;
																		/* ===== plugin groups ==== */
																		if ($is_engine)
																		{
																			$pgroups = Plugin_group::get_groups_by_plugin($conn, 1505);
																			$excluded = array();
																		}
																		else
																		{
																			$pgroups  = Plugin_group::get_list($conn, "", "name");
																			$excluded = Plugin_group::get_groups_by_plugin($conn, 1505);
																		}
																		
																		foreach ($pgroups as $g) 
                                                                        {
																			
																			if (isset($excluded[$g->get_id()])) continue;
																			
																			echo "<td class='nobborder' style='text-align:left;padding-right:10px'>";

                                                                            $checked = (in_array($g->get_id() , $plugingroups)) ? "checked='checked'" : "";
                                                                            $mixed   = ($is_engine) ? FALSE : $g->contains_directive_plugin($conn);


                                                                            if ($mixed)
                                                                            {
                                                                                $tip = _('This plugin group cannot be applied because contains the plugin 1505');
                                                                                echo "<input type='checkbox' class='disabled' disabled='disabled' id='plugin_" . $g->get_id() ."' pname='". $g->get_name() ."'>";
                                                                                echo "<a href='modifyplugingroupsform.php?action=edit&id=". $g->get_id() ."' class='greybox gray italic ' title='". _('View DS Group') ."'>". Util::htmlentities($g->get_name()) ."</a>";
                                                                                echo " <img src='/ossim/pixmaps/warnin_icon.png' id='dg_locked' class='tiptip_dg' title='". $tip ."'/>";
                                                                            }
                                                                            else
                                                                            {
                                                                                echo "<input type='checkbox' id='plugin_" . $g->get_id() ."' pname='". $g->get_name() ."' onclick='drawpolicy()' name='plugins[". $g->get_id() ."]' $checked/>";
                                                                            
                                                                                echo "<a href='modifyplugingroupsform.php?action=edit&id=". $g->get_id() ."' class='greybox' title='". _('View DS Group') ."'>". Util::htmlentities($g->get_name()) ."</a>";

                                                                            }

																			
																			echo "</td>";
																			if ($iplugin++ % 4==0) { echo "<tr></tr>"; }
																		} 
																		?>
																	</tr>
																</table>
															</div>
														</td>
													</tr>
													<?php 
    												if (!$is_engine) 
													{ 
													?>
													<tr>
														<td  class='noborder'>
															<div style='text-align:center'>
																<span style='color:#A0A0A0'>
																	<?php
																		echo required() . ' '. _('Directive plugin groups are not allowed in this kind of policiy group.');
																	?>
																</span>	
															</div>
														</td>
													</tr>
													<?php 
													}
													?>
														
												</table>												
												
											</td>
										</tr>
									</table>
									
								</div>
								
								<div id='txn' style='height:100%;width:100%;<?php echo (!$flag_events) ? "": "display:none;" ?>'>
									<table class='tab_table' style='width:80%;height:95%;padding-top:10px'>
										<tr>
											<td class="nobborder" valign="top" style='text-align:center; width:45%;' >
												<table align="center" class="noborder" width='100%' >
													<tr>
														<th style="background-position:top center;" valign='middle'>
															<?php echo _("Taxonomy Conditions") ?><br/>
														</th>
													</tr>
													<tr>
														<td class="nobborder" style='text-align:center;'>
															<select id="taxonomy_filters" name="taxfilters[]" size="12" multiple="multiple" style="width:100%">
																<?php 
																foreach ($tax_filters as $taxid => $tax)
																{
																	echo "<option value='$taxid'>$tax"; 
																}	
																?>
															</select>
														</td>
													</tr>
												</table>
											</td>
											<td class="nobborder" style='width:10%;'>
												<input type="button" class="small av_b_secondary" value=" [X] " onclick="deletefrom('taxonomy_filters');drawpolicy();"> 
											</td>
											<td valign="top" class="nobborder" style='text-align:center; width:45%;'>
												<table class="noborder" align='center' width='100%'>
													<tr>
														<th style="background-position:top center;" valign='middle'>
															<?php echo _("Taxonomy Parameters") ?><br/>
														</th>
													</tr>
													<tr>
														<td class="left nobborder">
															<div style='text-align: left; padding:10px 0 15px 10px; clear: both;'>
																<div style='float: left; width:90px;'><?php echo _("Product Type")?>:</div>
																<div style='float: left;'>
																	<select id="tax_pt" name="tax_pt" style='width:165px;'>
																		<?
																		foreach ($filter['ptype']  as $ptid => $ptname)
																		{
																			echo "<option value='$ptid'>$ptname</option>\n";
																		}
																		?>
																	</select>
																</div>
															</div>
														</td>
													</tr>
													<tr>
														<td class="left nobborder">
															<div style='text-align: left; padding:0 0 15px 10px; clear: both;'>
																<div style='float: left; width:90px;'><?php echo _("Category")?>:</div>
																<div style='float: left;'>
																	<select name="tax_cat" id='tax_cat' style='width:165px;' onchange='load_subcategories($(this).val());'>
																		<option value='0' selected='selected'><?php echo _("ANY") ?></option>
																	</select>
																</div>
															</div>
														</td>
													</tr>
													<tr>
														<td class="left nobborder">
															<div style='text-align: left; padding:0 0 15px 10px; clear: both;'>
																<div style='float: left; width:90px;'><?php echo _("Subcategory")?>:</div>
																<div style='float: left;'>
																	<select name="tax_subc" id='tax_subc' style='width:165px;'>
																		<option value='0' selected='selected'><?php echo _("ANY") ?></option>
																	</select>
																</div>
															</div>
														</td>
													</tr>
													<tr>
														<td class="nobborder" style='text-align:center; padding:10px 10px 10px 0;'>
															<input type="button" value="<?php echo _("Add New") ?>" onclick="javascript:add_tax_filter();return false;"/>					
														</td>
													</tr>
												</table>			
											</td>
										</tr>
									</table>
								</div>
					
							</td>
						</tr>
					
					</table>
					

				</div>
			</div>
		</li>
		
        <?php 
        if (!$is_engine)
        { 
        ?>
		<li class="accond-6" <?php echo($flag_sensors) ? " style='display:none'" : ""?>>
			<h2>
				<div class='finished'>
					<div class='div_left'><?php echo _("SENSORS") ?></div>
					<div class='div_right'><img src="../pixmaps/tables/warning.png" class="imgsensors img_rotate" /></div>
					<div class='div_right'><img src="../pixmaps/trash.png" class="img_delc img_rotate" /></div>
				</div>
			</h2>
			<div id="cond-6">
				<div class='wrap_acc'>
					<?php 
    				if (GET('sensorNoExist') == 'true')
    				{ 
    				?>
    					<script type="text/javascript">
        					
                            $(document).ready(function() 
                            {
                                load_sensors_tree();
                            })
                            
    					</script>
					<?php 
    				} 
    				?>					
					<table class='tab_table'>
						<tr>
							<td class="nobborder" valign="middle">
								<table align="center" class="noborder">
									<tr>
										<th style="background-position:top center"><?php echo _("Sensors") . required() ?><br/>
											<span class='size10'><a href="../sensor/newsensorform.php" class="greybox"><?php echo _("Insert new sensor?") ?></a></span><br/>
										</th>
										<td class="left nobborder" valign="top">
											<select id="sensors" name="mboxs[]" size="18" multiple="multiple" style="width:200px">
                                            <?php 
                                            foreach ($sensors as $s => $sensor)
                                            {
                                                echo "<option value='$s'>$sensor</option>"; 
                                            }
                                            ?>
											</select>
											<input type="button" value=" [X] " onclick="deletefrom('sensors');drawpolicy()" class="small av_b_secondary">
										</td>
									</tr>
								</table>
							</td>
							
							<td valign="top" class="nobborder">
								<table class="noborder">
									<tr>							
										<td class="left nobborder" valign="top">
											<div id="containerse" class='container_tree'></div>
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</li>
		<?php 
        } 
        ?>
		
		<li class="accond-9" <?php echo($flag_reputation) ? " style='display:none'" : "" ?>>
			<h2>
				<div class='finished'>
					<div class='div_left'><?php echo _("REPUTATION") ?></div>
					<div class='div_right'><img src="../pixmaps/tables/warning.png" class="imgrep img_rotate" /></div>
					<div class='div_right'><img src="../pixmaps/trash.png" class="img_delc img_rotate" /></div>
				</div>
			</h2>
			<div id="cond-9">

				<table class='tab_table'>
					<tr>
						<td class="td_p_container">
							<table align="center" class="noborder" width='100%'>
								<tr>
									<th style="background-position:top center;" valign='middle'>
										<?php echo _("Reputation Conditions") ?><br/>
									</th>
								</tr>
								<tr>
									<td class="nobborder">
										<select id="reputation_filters" name="repfilters[]" size="15" multiple="multiple" style="width:100%">
											<?php 
											foreach ($rep_filters as $repid => $rep)
											{
												echo "<option value='$repid'>$rep"; 
											}	
											?>
										</select>
									</td>
								</tr>
							</table>
						</td>
						<td class="nobborder" style='width:10%;'>
							<input type="button" class="small av_b_secondary" value=" [X] " onclick="deletefrom('reputation_filters');drawpolicy();"> 
						</td>
						<td class="td_p_container">
							<table class="noborder" align='center' width='100%'>
								<tr>
									<th style="background-position:top center;" valign='middle'>
										<?php echo _("Reputation Parameters") ?><br/>
									</th>
								</tr>
								<tr>
									<td class="left nobborder">
										<div style='text-align: left; padding:10px 0 15px 10px; clear: both;'>
											<div style='float: left; width:90px;'><?php echo _("Activity")?>:</div>
											<div style='float: left;'>
												<select id="rep_act" name="rep_act" style="width:165px">
													<option value='0' selected='selected'><?php echo _("ANY") ?></option>
													<?
													foreach ($filter['act'] as $rep_id => $act)
													{
														echo "<option value='$rep_id'>$act</option>";
													}
													?>
												</select>
											</div>
										</div>
									</td>
								</tr>
								<tr>
									<td class="left nobborder">
										<div style='text-align: left; padding:0 0 15px 10px; clear: both;'>
											<div style='float: left; width:90px;'><?php echo _("Priority")?>:</div>
											<div style='float: left;'>
                                                                                                <select id="rep_sev_lem" name="rep_sev_lem">
                                                                                                       <option value="less"><</option>
                                                                                                       <option value="equal" selected="selected">=</option>
                                                                                                       <option value="more">></option>
                                                                                                </select>
												<select id="rep_sev" name="rep_sev">
												<?php
													for ($i=1; $i <= 10; $i++) 
													{
														echo "<option value=$i>$i</option>";
													}
												?>
												</select>
											</div>
										</div>
									</td>
								</tr>
								<tr>
									<td class="left nobborder">
										<div style='text-align: left; padding:0 0 15px 10px; clear: both;'>
											<div style='float: left; width:90px;'><?php echo _("Reliability")?>:</div>
											<div style='float: left;'>
                                                                                                <select id="rep_rel_lem" name="rep_rel_lem">
                                                                                                       <option value="less"><</option>
                                                                                                       <option value="equal" selected="selected">=</option>
                                                                                                       <option value="more">></option>
                                                                                                </select>
												<select id="rep_rel" name="rep_rel" >
													<?php
													for ($i=1; $i <= 10; $i++) 
													{
														echo "<option value=$i>$i</option>";
													}
													?>
												</select>
											</div>
										</div>
									</td>
								</tr>
								<tr>
									<td class="left nobborder">
										<div style='text-align: left; padding:0 0 15px 10px; clear: both;'>
											<div style='float: left; width:90px;'><?php echo _("Direction")?>:</div>
											<div style='float: left;'>
												<input type="radio" name="rep_dir" value="1" checked /> <?php echo _("Source")?>
												<input type="radio" name="rep_dir" value="0" /> <?php echo _("Destination")?>
											</div>
										</div>
										
									</td>
								</tr>
								<tr>
									<td class="nobborder" style='text-align:center; padding:10px 10px 10px 0;'>
										<input type="button" value="<?php echo _("Add New") ?>" onclick="javascript:add_rep_filter();return false;"/>					
									</td>
								</tr>
							</table>			
						</td>
					</tr>
				</table>
			
			</div>
		</li>
		
		<li class="accond-12" <?php echo($flag_event_prio) ? " style='display:none'" : ""?>>
			<h2>
				<div class='finished'>
					<div class='div_left'><?php echo _("EVENT PRIORITY") ?></div>
					<div class='div_right'><img src="../pixmaps/tables/warning.png" class="imgeventprio img_rotate" /></div>
					<div class='div_right'><img src="../pixmaps/trash.png" class="img_delc img_rotate" /></div>
				</div>
			</h2>
			<div id="cond-12">
                
                <?php
                if (!$is_engine) 
                { 
                ?>
                    <div id='fed_only'>
                        <?php echo _('This filter is only valid for Federated Environments with event forwarding enabled.') ?>
                    </div>
                    <br/>
				<?php
    			}
                ?>
                
				<table class='tab_table'>
					<tr>
						<td class="td_p_container">
							<table align="center" class="noborder" width='100%'>
								<tr>
									<th style="background-position:top center;" valign='middle'>
										<?php echo _("Event Conditions") ?><br/>
									</th>
								</tr>
								<tr>
									<td class="nobborder">
										<select id="event_filters" name="evfilters[]" size="15" multiple="multiple" style="width:100%">
											<?php 
											foreach ($event_filters as $eventpid => $event)
											{
												echo "<option value='$eventpid'>$event</option>"; 
											}	
											?>
										</select>
									</td>
								</tr>
							</table>
						</td>
						<td class="nobborder" style='width:10%;'>
							<input type="button" class="small av_b_secondary" value=" [X] " onclick="deletefrom('event_filters');drawpolicy();"> 
						</td>
						<td class="td_p_container">
							<table class="noborder" align='center' width='100%'>
								<tr>
									<th style="background-position:top center;" valign='middle'>
										<?php echo _("Event Parameters") ?><br/>
									</th>
								</tr>
								<tr>
									<td class="left nobborder">
										<div style='text-align: left; padding:0 0 15px 10px; clear: both;'>
											<div style='float: left; width:90px;'><?php echo _("Priority")?>:</div>
											<div style='float: left;'>
												<select id="ev_sev_lem" name="ev_sev_lem">
                                                                                                       <option value="less"><</option>
                                                                                                       <option value="equal" selected="selected">=</option>
                                                                                                       <option value="more">></option>
												</select>
												<select id="ev_sev" name="ev_sev">
												<?php
													for ($i=1; $i <= 5; $i++) 
													{
														echo "<option value=$i>$i</option>";
													}
												?>
												</select>
											</div>
										</div>
									</td>
								</tr>
								<tr>
									<td class="left nobborder">
										<div style='text-align: left; padding:0 0 15px 10px; clear: both;'>
											<div style='float: left; width:90px;'><?php echo _("Reliability")?>:</div>
											<div style='float: left;'>
                                                                                                <select id="ev_rel_lem" name="ev_rel_lem">
                                                                                                       <option value="less"><</option>
                                                                                                       <option value="equal" selected="selected">=</option>
                                                                                                       <option value="more">></option>
                                                                                                </select>

												<select id="ev_rel" name="ev_rel" >
												<?php
													for ($i=1; $i <= 10; $i++) 
													{
														echo "<option value=$i>$i</option>";
													}
												?>
												</select>
											</div>
										</div>
									</td>
								</tr>
								<tr>
									<td class="nobborder" style='text-align:center; padding:10px 10px 10px 0;'>
										<input type="button" value="<?php echo _("Add New") ?>" onclick="javascript:add_event_filter();return false;"/>					
									</td>
								</tr>
							</table>			
						</td>
					</tr>
				</table>
			
			</div>
		</li>
		
		<li class="accond-11" <?php echo($flag_time) ? " style='display:none'" : ""?>>
			<h2>
				<div class='finished'>
					<div class='div_left'><?php echo _("TIME RANGE") ?></div>
					<div class='div_right'><img src="../pixmaps/tables/warning.png" class="imgtime img_rotate" /></div>
					<div class='div_right'><img src="../pixmaps/trash.png" class="img_delc img_rotate" /></div>
				</div>
			</h2>
			<div id="cond-11">				
				<table class='transparent' width='100%' style='padding-top:50px;' valign="middle">
					
					<tr>
						<td class="nobborder" style='text-align:center;font-weight:bold'>
							<?php echo _("Timezone") ?>
						</td>
						<td class="nobborder" style='text-align:center;'>
                        <?php 
							$tzlist = timezone_identifiers_list(4095);
							sort($tzlist);							
						?>
							<select name="tzone" id="tzone" onchange="drawpolicy()">
							<?php  
								foreach ($tzlist as $tz) 
                                {
                                    if ($tz != "localtime")
                                    {
                                        echo "<option value='$tz'".( ( $utz == $tz ) ? " selected='selected'": "").">$tz</option>\n";
                                    }
                                }
							?>
							</select>

						</td>					
					</tr>
					<tr><td colspan='2' style='height:10px;border:none;'></td></tr>
					
					<tr>
						<th valign="middle" style='width:20%; text-align:left;'>
							<div><input type="radio" name="date_type" onchange='change_date_type();' value="1" <?php echo ($date_type == 1) ? "checked='checked'" : "" ?>/><?php echo _("Daily"); ?></div>
							<div style='padding-top:6px;'><input type="radio" name="date_type" onchange='change_date_type();' value="2" <?php echo ($date_type == 2) ? "checked='checked'" : "" ?>/><?php echo _("Weekly"); ?></div>
							<div style='padding-top:6px;'><input type="radio" name="date_type" onchange='change_date_type();' value="3" <?php echo ($date_type == 3) ? "checked='checked'" : "" ?>/><?php echo _("Monthly"); ?></div>
							<div style='padding-top:6px;'><input type="radio" name="date_type" onchange='change_date_type();' value="4" <?php echo ($date_type == 4) ? "checked='checked'" : "" ?>/><?php echo _("Custom Range"); ?></div>
							
						</th>
						<td class="nobborder" style='height:150px;width:80%;vertical-align:super;'>
							<table class='transparent' width='100%'>
								<tr>
									<th width='50%'> Begin </th>
									<th width='50%'> End </th>
								</tr>
								<tr>
									<td width='50%' class='noborder' valign='middle'>

										<div style='padding:15px 0 10px 0;'>
											<?php echo _("Time") ?>: 
											<select name="begin_hour" onchange="drawpolicy()">
												<?php
													for ($i=0; $i<24; $i++)
													{
														$selected = ( $time_begin[3] == $i ) ? "selected='selected'" : '';
														echo "<option $selected value='$i'>".$i."</option>";
													}
												?>
											</select>
											h : 
											<select name="begin_minute" onchange="drawpolicy()">
												<?php
													for ($i=0; $i<60; $i++)
													{
														$selected = ( $time_begin[4] == $i ) ? "selected='selected'" : '';
														echo "<option $selected value='$i'>".$i."</option>";
													}
												?>
											</select>
											 min
										</div>
										<div id='beginweekday' style='padding:7px 0;display:none'>
											<?php echo _("Day of the Week") ?>:
											<select name="begin_day_week" onchange="drawpolicy()">
												<?php
													for ($i=1; $i<=7; $i++)
													{
														$selected = ( $time_begin[2] == $i ) ? "selected='selected'" : '';
														echo "<option $selected value='$i'>". Util::get_day_name($i, 'short') ."</option>";
													}
												?>
											</select>
										</div>
										<div id='beginmonthday' style='padding:7px 0;display:none'>
											<?php echo _("Day of the Month") ?>:
											<select name="begin_day_month" onchange="drawpolicy()">
												<?php
													for ($i=1; $i<=31; $i++)
													{
														$card     = ($i == 1) ? "st" : (($i==2)? "nd" : "th");
														$selected = ( $time_begin[1] == $i ) ? "selected='selected'" : '';
														echo "<option $selected value='$i'>$i$card</option>";
													}
												?>
											</select>
										</div>	
										<div id='beginmonth' style='padding:7px 0;display:none'>
											<?php echo _("Month") ?>:
											<select name="begin_month" onchange="drawpolicy()">
												<?php
													for ($i=1; $i<=12; $i++)
													{
														$selected = ( $time_begin[0] == $i ) ? "selected='selected'" : '';
														echo "<option $selected value='$i'>". Util::get_month_name($i, 'short') ."</option>";
													}
												?>
											</select>
										</div>									
									
									</td>
									<td width='50%' class='noborder' valign='middle'>
										<div style='padding:15px 0 10px 0;'>
											<?php echo _("Time") ?>: 
											<select name="end_hour" onchange="drawpolicy()">
												<?php
													for ($i=0; $i<24; $i++)
													{
														$selected = ( $time_end[3] == $i ) ? "selected='selected'" : '';
														echo "<option $selected value='$i'>".$i."</option>";
													}
												?>
											</select>
											 h : 
											<select name="end_minute" onchange="drawpolicy()">
												<?php
													for ($i=0; $i<60; $i++)
													{
														$selected = ( $time_end[4] == $i ) ? "selected='selected'" : '';
														echo "<option $selected value='$i'>".$i."</option>";
													}
												?>
											</select>
											 min
										</div>
										<div id='endweekday' style='padding:7px 0;display:none'>
											<?php echo _("Day of the Week") ?>:
											<select name="end_day_week" onchange="drawpolicy()">
												<?php
													for ($i=1; $i<=7; $i++)
													{
														$selected = ( $time_end[2] == $i ) ? "selected='selected'" : '';
														echo "<option $selected value='$i'>". Util::get_day_name($i, 'short') ."</option>";
													}
												?>
											</select>
										</div>
										<div id='endmonthday' style='padding:7px 0;display:none'>
											<?php echo _("Day of the Month") ?>:
											<select name="end_day_month" onchange="drawpolicy()">
												<?php
													for ($i=1; $i<=31; $i++)
													{
														$card     = ($i == 1) ? "st" : (($i==2)? "nd" : "th");
														$selected = ( $time_end[1] == $i ) ? "selected='selected'" : '';
														echo "<option $selected value='$i'>$i$card</option>";
													}
												?>
											</select>
										</div>	
										<div id='endmonth' style='padding:7px 0;display:none'>
											<?php echo _("Month") ?>:
											<select name="end_month" onchange="drawpolicy()">
												<?php
													for ($i=1; $i<=12; $i++)
													{
														$selected = ( $time_end[0] == $i ) ? "selected='selected'" : '';
														echo "<option $selected value='$i'>". Util::get_month_name($i, 'short') ."</option>";
													}
												?>
											</select>
										</div>
										
									</td>
								</tr>
							</table>			
						</td>
					</tr>
				</table>			
			</div>
		</li>
	
	
	
	
	</ol>
</div>		

<br/>	

<div class='fleft'>
	<a href='javascript:;' onclick='toggle_accordions(false);'>
	   <img align="absmiddle" border="0" src="/ossim/pixmaps/arrow_green.gif"/> <?php echo _('POLICY CONSEQUENCES') ?> 
    </a>
</div>

<br/>	

<div id="consequences">
	<ol>
		<li class="accons-1">
			<h2>
				<div class='finished'>
					<div class='div_left'><?php echo _("ACTIONS") ?></div>
					<div class='div_right'><img src="../pixmaps/tables/tick.png" class="imgactions img_rotate" /></div>
				</div>
			</h2>
			<div id="conseq-1">
				<div class='wrap_acc'>
					<table align="center" class="nobborder transparent" style='padding-top:21px;width:65%'>
							<tr>
								<th style="background-position:top center"><?php echo _("Actions") ?> &nbsp;
									<span class="size"><a href="../action/actionform.php" onclick='javascript:accordion_gb=2;'class="greybox"><?php echo _("Insert new action?") ?></a></span><br/>
								</th>
							</tr>
							
							<tr>
								<td class='noborder'>
									
								</td>
							</tr>
							
							<tr>
								<td class="nobborder" valign="top">
									<table class="nobborder" cellpadding=0 cellspacing=0 width='100%' align='center'>
										<tr>
											<th width='50%'><?php echo _('Active Actions') ?></th>
											<th width='50%'><?php echo _('Available Actions') ?></th>
										</tr>
									</table>
									<div id='ms_body'>
										<div id='load_ms'><img src="../pixmaps/loading.gif" width="16px" align="absmiddle"/><?=_("Loading actions, please wait a second...")?></div>
										<select id="actions" name="actions[]" class="multiselect" multiple="multiple" style="width:65%;height:200px;display:none">	
										</select>
									</div>
								</td>
							</tr> 
					</table>
				</div>
			</div>
		</li>
		
		<li class="accons-2">
			<h2>
				<div class='finished'>
					<div class='div_left'><?php echo _("SIEM") ?></div>
					<div class='div_right'><img src="../pixmaps/tables/tick.png" class="imgsiem img_rotate" /></div>
				</div>
			</h2>
			<div id="conseq-2">
				<div class='wrap_acc'>
					<table class='tab_table'>
						<tr>							
							<td valign="middle" class="nobborder" style="width:50;">
								<table align="center" style="width:100%" id='p_conseq1'>
									<tr>
										<th style="text-decoration:underline;text-align:left; padding: 0px 10px"> <?php echo _("SIEM")?> </th>
										<td class="left">
											<div class='cont_elem'>
												<input type="radio" name="sim" onchange="tsim(1);drawpolicy();" value="1" <?php echo ($sim == 1) ? "checked='checked'" : "" ?>/> <?php echo _("Yes"); ?>
												<input type="radio" name="sim" onchange="tsim(0);drawpolicy();" value="0" <?php echo ($sim == 0) ? "checked='checked'" : "" ?>/> <?php echo _("No"); ?>
												<span id="sim_tt" class="c_av_tooltip hidden"><img class="av_tooltip" src="/ossim/pixmaps/warning.png" title="<?php echo _("Note: SIEM must be set to 'yes' in order for the policy action to be executed. If SIEM is set to 'no', no action will take place.")?>" /></span>
											</div>
											<span style="padding-left: 4px;">*</span>
										</td>
									</tr>
									<tr>
										<th style="text-align:left; padding-left:25px"><?php echo _("Set Event Priority")?></th>
										<td class="left">
											<div class='cont_elem'>
												<select name="priority" onchange="drawpolicy();">
													<option <?php echo ($priority == - 1) ? "selected='selected'" : "" ?> value="-1"><?php echo _("Do not change"); ?></option>
													<option <?php echo ($priority == 0) ? "selected='selected'" : "" ?> value="0">0</option>
													<option <?php echo ($priority == 1) ? "selected='selected'" : "" ?> value="1">1</option>
													<option <?php echo ($priority == 2) ? "selected='selected'" : "" ?> value="2">2</option>
													<option <?php echo ($priority == 3) ? "selected='selected'" : "" ?> value="3">3</option>
													<option <?php echo ($priority == 4) ? "selected='selected'" : "" ?> value="4">4</option>
													<option <?php echo ($priority == 5) ? "selected='selected'" : "" ?> value="5">5</option>
												</select>
											</div>
											<span style="padding-left: 4px;">*</span>
										</td>
									</tr>					
									<tr id="qualify">
										<th style="text-align:left; padding-left:25px" id="qualify_text"<?php echo ($sim == 0) ? " class='thgray'" : "" ?>> <?php echo _("Risk Assessment")?> </th>
										<td class="left">
											<div class='cont_elem'>
												<input type="radio" name="qualify" value="1" onclick="drawpolicy();" <?php echo ($qualify == 1) ? "checked='checked'" : "" ?><?php if ($sim == 0) echo " disabled='disabled'" ?>/> <?php echo _("Yes"); ?>
												<input type="radio" name="qualify" value="0" onclick="drawpolicy();" <?php echo ($qualify == 0) ? "checked='checked'" : "" ?><?php if ($sim == 0) echo " disabled='disabled'" ?>/> <?php echo _("No"); ?>
											</div>
											<span style="padding-left: 4px;">*</span>
										</td>
									</tr>
									
									<tr id="correlate">
										<th style="text-align:left; padding-left:25px" id="correlate_text"<?php echo ($sim == 0) ? " class='thgray'" : "" ?>> <?php echo _("Logical Correlation")?> </th>
										<td class="left">
											<div class='cont_elem'>
												<input type="radio" name="correlate" value="1" onclick="drawpolicy();" <?php echo ($correlate == 1) ? "checked='checked'" : "" ?><?php if ($sim == 0) echo " disabled='disabled'" ?>/> <?php echo _("Yes"); ?>
												<input type="radio" name="correlate" value="0" onclick="drawpolicy();" <?php echo ($correlate == 0) ? "checked='checked'" : "" ?><?php if ($sim == 0) echo " disabled='disabled'" ?>/> <?php echo _("No"); ?> <small>1)</small>
											</div>
											<span style="padding-left: 4px;">*</span>
										</td>
									</tr>
									
									<tr id="cross_correlate">
										<th style="text-align:left; padding-left:25px" id="cross_correlate_text"<?php echo ($sim == 0) ? " class='thgray'" : "" ?>> <?php echo _("Cross-correlation")?> </th>
										<td class="left">
											<div class='cont_elem'>
												<input type="radio" name="cross_correlate" value="1" onclick="drawpolicy();" <?php echo ($cross_correlate == 1) ? "checked='checked'" : "" ?><?php if ($sim == 0) echo " disabled='disabled'" ?>/> <?php echo _("Yes"); ?>
												<input type="radio" name="cross_correlate" value="0" onclick="drawpolicy();" <?php echo ($cross_correlate == 0) ? "checked='checked'" : "" ?><?php if ($sim == 0) echo " disabled='disabled'" ?>/> <?php echo _("No"); ?> <small>1)</small>
											</div>
											<span style="padding-left: 4px;">*</span>
										</td>
									</tr>
								 
									<tr id="store">
										<th style="text-align:left; padding-left:25px" id="store_text"<?php echo ($sim == 0) ? " class='thgray'" : "" ?>> <?php echo _("SQL Storage")?> </th>
										<td class="left">
											<div class='cont_elem'>
												<input type="radio" name="store" value="1" onclick="drawpolicy();"  <?php echo ($store == 1) ? "checked='checked'" : "" ?><?php if ($sim == 0) echo " disabled='disabled'" ?>/> <?php echo _("Yes"); ?>
												<input type="radio" name="store" value="0" onclick="drawpolicy();"  <?php echo ($store == 0) ? "checked='checked'" : "" ?><?php if ($sim == 0) echo " disabled='disabled'" ?>/> <?php echo _("No"); ?> <small>1)</small>
											</div>
											<span style="padding-left: 4px;">*</span>
										</td>
									</tr>
									
									<tr>
										<td colspan="2" class="left noborder" style='padding:10px 5px;'>
											1) <?php echo _("Does not apply to targets without associated database.") ?> <?php echo _("Implicit value is always No for them."); ?>	 
										</td>
									</tr>
									
								  </table>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</li>
		<?php if (!$is_engine) { ?>
		<li class="accons-3">
			<h2>
				<div class='finished'>
					<div class='div_left'><?php echo _("LOGGER") ?></div>
					<div class='div_right'><img src="../pixmaps/tables/tick.png" class="imglogger img_rotate" /></div>
				</div>
			</h2>
			<div id="conseq-3">
				<div class='wrap_acc'>
					<table class='tab_table'>
						<tr>							
							<td valign="middle" class="nobborder" style="width:50%;padding-left:10px;">
								<table align="center" style="width:100%;" id='p_conseq2'>			  
								  
									<tr>
										<th style="text-decoration:underline;text-align:left; padding: 0px 10px" <?= ($open_source) ? " class='thgray'" : "" ?>> <?php echo _("Logger")?> </th>
										<td class="left" <?= ($open_source) ? "style='color:gray'" : "" ?>>
											<div class='cont_elem'>
												<input type="radio" name="sem" onchange="tsem(1)" value="1" <?php echo ($sem == 1) ? "checked='checked'" : "" ?> <?= ($open_source) ? "disabled='disabled'" : "" ?> onclick="drawpolicy();" /> <?php echo _("Yes"); ?>
												<input type="radio" name="sem" onchange="tsem(0)" value="0" <?php echo ($sem == 0) ? "checked='checked'" : "" ?> <?= ($open_source) ? "disabled='disabled'" : "" ?> onclick="drawpolicy();" /> <?php echo _("No"); ?> <?php echo ($open_source) ? "<small> 1)</small>" : "" ?> 
											</div>
											<span style="padding-left: 4px;">*</span>
										</td>
									</tr>

									<tr id="sign">
										<th style="text-align:left; padding-left:25px" id="sign_text"<?php echo ($sem == 0) ? " class='thgray'" : "" ?>> <?php echo _("Sign")?> </th>
										<td class="left"  <?= ($open_source) ? 'style="color:gray"' : '' ?>>
											<div class='cont_elem'>
												<input type="radio" name="sign" id='sign_line' value="1" 
												    <?php echo ($sign == 1) ? ' checked="checked" ' : '' ?>
												    <?php echo ($open_source || $sem == 0 || !$sign_line) ? ' disabled ' : '' ?> 
												    onclick="drawpolicy();"
												> 
												<label for='sign_line'
												    class="<?php echo (!$sign_line) ? 'tiptip tip_sign_line' : '' ?>"
												    title="<?php echo $tooltip_sing_line ?>"
												>
												    <?php echo _("Line") ?>
												</label>
												    
												<input type="radio" name="sign" id='sign_block' value="0" 
												    <?php echo ($sign == 0) ? 'checked="checked"' : '' ?>
												    <?php echo ($open_source || $sem == 0) ? ' disabled ' : '' ?> 
												    onclick="drawpolicy();" 
												> 
												<label for='sign_block'>
												    <?php echo _("Block") ?>
												</label>
											</div>
											<span style="padding-left: 4px;">*</span>
										</td>
									</tr>
									
									<tr>
										<td colspan="2" class="left noborder" style='padding:10px 5px;'>
											<?php echo ($open_source) ? "1) <a href='../ossem' style='size:11px;'>"._("Only available in USM Server")."</a>" : "" ?>
										</td>
									</tr>
									
								</table>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</li>
		<?php } ?>
		<li class="accons-4">
			<h2>
				<div class='finished'>
					<div class='div_left'><?php echo _("FORWARDING") ?></div>
					<div class='div_right'><img src="../pixmaps/tables/tick.png" class="imgforward img_rotate" /></div>
				</div>
			</h2>
			<div id="conseq-4">
				<div class='wrap_acc'>
					<table class='tab_table'>
						<tr>							
							<td valign="middle" class="nobborder" style="width:50%;padding-left:10px;">
								<table align="center" style="width:100%" id='p_conseq2'>			  							  
									<tr id="revents" <?php echo ($open_source) ? "class='thgray'" : "" ?>>
										<th style="text-align:left; padding-left:25px" id="revents_text"<?= ($open_source) ? " class='thgray'" : "" ?>> <?php echo _("Forward events")?> </th>
										<td class="left noborder" <?= ($open_source) ? "style='color:gray'" : "" ?>>
											<div class='cont_elem'>	
												<input type="radio" name="resend_events" value="1" <?php echo ($resend_event == 1) ? "checked='checked'" : "" ?> <?= ($open_source) ? "disabled='disabled'" : "" ?> onclick="show_frw_opts(1);" /> <?php echo _("Yes"); ?>
												<input type="radio" name="resend_events" value="0" <?php echo ($resend_event == 0) ? "checked='checked'" : "" ?> <?= ($open_source) ? "disabled='disabled'" : "" ?> onclick="show_frw_opts(0);" /> <?php echo _("No"); ?> <?php echo ($open_source) ? "<small> 1)</small>" : "" ?> 
					
											</div>
											<span style="padding-left: 4px;">*</span>
										</td>
									</tr>
									
									<tr id='forw_opts' <?php if (!$open_source && $resend_event == 0) echo "style='display:none;'" ?>>
										<td colspan="2" class="left noborder" style='padding:5px;'>
											<?php 
											if ($open_source)
											{
												echo "1) <a href='../ossem' style='size:11px;'>"._("Only available in USM Server")."</a>";
											}
											else
											{																			
												if (is_array($server_h) && !empty($server_h))
												{													
											?>
													<table class='tab_table' style='width:95%;padding-top:5px;'>
														<tr>
															<td class="nobborder" valign="top" style='text-align:center; width:45%;' >
																<table align="center" class="noborder" width='100%'>
																	<tr>
																		<th style="background-position:top center;" valign='middle'>
																			<?php echo _("Forwarding Conditions") ?><br/>
																		</th>
																	</tr>
																	<tr>
																		<td class="nobborder">
																			<select id="frw_filters" name="frwfilters[]" size="10" multiple="multiple" style="width:100%">
																				<?php 
																				foreach ($server_fwd_filters as $serverid => $server){
																					echo "<option value='$serverid'>$server"; 
																				}	
																				?>
																			</select>
																		</td>
																	</tr>
																</table>
															</td>
															<td class="nobborder" style='width:10%;'>
																<input type="button" class="small av_b_secondary" value=" [X] " onclick="deletefrom('frw_filters');drawpolicy();"> 
															</td>
															<td valign="top" class="nobborder" style='text-align:center; width:45%;'>
																<table class="noborder" align='center' width='100%'>
																	<tr>
																		<th style="background-position:top center;" valign='middle'>
																			<?php echo _("Forwarding Options") ?><br/>
																		</th>
																	</tr>
																	<tr>
																		<td class="left nobborder">
																			<div style='text-align: left; padding:5px 0 20px 10px; clear: both;'>
																				<div style='float: left; width:90px;'><?php echo _("Server")?>:</div>
																				<div style='float: left;'>
																					<select id="frw_ser" name="frw_ser">
                                                                                    <?php 
																					foreach ($server_h as $sid => $s)
																					{
																						echo "<option value='$sid'>". $s[0] .'</option>'; 
																					}	
																					?>
																					</select>
																				</div>
																			</div>
																		</td>
																	</tr>
																	<tr>
																		<td class="left nobborder">
																			<div style='text-align: left; padding:0 0 20px 10px; clear: both;'>
																				<div style='float: left; width:90px;'><?php echo _("Priority")?>:</div>
																				<div style='float: left;'>
																					<select id="frw_prio" name="frw_prio" >
                                                                                    <?php
																						for ($i=1; $i <= 99; $i++) 
																						{
																							echo "<option value=$i>$i</option>";
																						}
																				    ?>
																					</select>
																				</div>
																			</div>
																		</td>
																	</tr>
																	<tr>
																		<td class="nobborder" style='text-align:center; padding:10px 10px 10px 0;'>
																			<input type="button" value="<?php echo _("Add New") ?>" onclick="javascript:add_frw_filter();return false;"/>					
																		</td>
																	</tr>
																</table>			
															</td>
														</tr>
													</table>										
											<?php
												} 
												else
												{ 
												?>
													<?php 
    												if ($resend_event) 
    												{ 
    												?>
														<script>														
															$('#forw_opts').show();														
														</script>
													<?php 
    												} 
    												?>
													<table class='tab_table' style='width:95%;padding-top:5px;height:100px'>
														<tr>
															<td class="nobborder" valign="middle" style='text-align:center; width:45%;' >
															<?php echo _('There are no servers available for forwarding') ?>
															</td>
														</tr>
													</table>
											<?php
												}
											}												
											?>
										</td>
									</tr>									
								</table>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</li>
		
	</ol>
</div>		
		
<br><br>		
<center style="padding:10px 0">
	<input type="button" value=" <?=_("Update Policy")?> " class="sok" onclick="submit_form(this.form)"/>
</center>
		

</form>

	


</body>
</html>
<?php $db->close(); ?>
