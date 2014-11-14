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

require_once 'classes/menu.inc';

$host_report_menu_flag = TRUE; // To know when is already loaded
?>
<script type="text/javascript" src="/ossim/js/jquery.min.js"></script>

<link href="/ossim/style/jquery.contextMenu.css" rel="stylesheet" type="text/css"/>
<script src="/ossim/js/jquery.contextMenu.js" type="text/javascript"></script>
<?php 
if (!$noready) 
{ 
    ?>
    <script type="text/javascript">
        
        function load_inframe(url) 
        {
            if (typeof(top.av_menu) == 'object')
            {                                    
                top.av_menu.load_content(url);          
            }
            else
            {
                var wnd = top.window.open(url, '','scrollbars=yes,location=no,toolbar=no,status=no,directories=no');
            }

            top.frames['main'].GB_hide();
            
        }
                
        function load_greybox(caption, url, height, width)
        {
            if (!parent.is_lightbox_loaded(window.name))
            {             
                top.frames['main'].GB_show(caption, url, height, width);
            }
        }       
        
        function get_menu_query_string(m_opt, sm_opt, h_opt)
        {
            var qs = '';
                     
            if (m_opt != '' && sm_opt != '' && h_opt != '')
            {            
                qs    =  "m_opt="+encodeURIComponent(m_opt);
                qs   += "&sm_opt="+encodeURIComponent(sm_opt);
                qs   += "&h_opt="+encodeURIComponent(h_opt);

            }
                
            return qs;
        }
        
        function load_contextmenu() 
        {
            $('.HostReportMenu').contextMenu({
    				menu: 'myMenu',

    			    // Execute when showing the context menu
    				pre_action: function (el) {
    				    
        				var aux = $(el).attr('id').split(/;/);

        				// Disable all menu items when not a valid IP
        			    if (aux[0] != '0.0.0.0' && aux[0].match(/^(([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])$/))
        			    {
        			        $('#myMenu').enableContextMenu();
        			        $('#myMenu').disableContextMenuItems('#detail');
        			        $(this).attr('disabled', true);
        			        $("#enableItems").attr('disabled', false);
        			    }
        			    else
        			    {
        			        $('#myMenu').disableContextMenu();
        			        $(this).attr('disabled', true);
        			        $("#disableItems").attr('disabled', false);
        			    }

           			// Disable 'Asset Detail' menu element when no Asset ID is found    
        			    if (typeof aux[2] != 'undefined' && aux[2].match(/^[A-F0-9]{32}$/))
        			    {
        			        $('#myMenu').enableContextMenuItems('#detail');
        			        $(this).attr('disabled', true);
        			        $("#disableItems").attr('disabled', false);
        			    }
        			    else
        			    {
        			        $('#myMenu').disableContextMenuItems('#detail');
        			        $(this).attr('disabled', true);
        			        $("#enableItems").attr('disabled', false);
        			    }
    				}
    			},
				
				function(action, el, pos) 
				{
                    if (action=='filter_rse') 
                    {
                        var aux = $(el).attr('id2');

                        if(typeof aux != 'undefined')
                        {
                            aux   = aux.split(/;/);
                        }
                        else
                        {
                            aux = $(el).attr('id').split(/;/);
                        }                                                
                        
                        var url  = "/ossim/forensics/base_qry_main.php?clear_allcriteria=1&time_range=all&clear_criteria=time&search=1&sensor=&search_str="+aux[0]+"+AND+"+aux[1]+"&submit=Src+or+Dst+IP&ossim_risk_a+=";
                        
                            url += "&"+ get_menu_query_string('analysis', 'security_events', 'security_events');                      
                                                 
                        load_inframe(url);
                    }
                    else if(action=='filter_rsfe') 
                    {
                        var aux = $(el).attr('id2');

                        if(typeof aux != 'undefined')
                        {
                            aux   = aux.split(/;/);
                        }
                        else
                        {
                            aux = $(el).attr('id').split(/;/);
                        } 

                        var url   = "/ossim/forensics/base_qry_main.php?clear_allcriteria=1&time_range=all&clear_criteria=time&search=1&sensor=&search_str="+aux[0]+"+AND+"+aux[1]+"&submit=Src+or+Dst+IP&sip=&plugin=&ossim_risk_a+=&category%5B0%5D=3&category%5B1%5D=";
                        
                             url += "&"+ get_menu_query_string('analysis', 'security_events', 'security_events'); 
                        
                        load_inframe(url);
                    }
					else if (action=='filter')
					{
						var aux  = $(el).attr('id').split(/;/);
						var ip   = aux[0];
						
						var url  = "/ossim/forensics/base_qry_main.php?new=2&num_result_rows=-1&submit=Query+DB&current_view=-1&ip_addr_cnt=1&sort_order=time_d&ip_addr%5B0%5D%5B0%5D+=&ip_addr%5B0%5D%5B1%5D=ip_both&ip_addr%5B0%5D%5B2%5D=%3D&ip_addr%5B0%5D%5B3%5D="+ip+"&ip_addr%5B0%5D%5B8%5D+=";
						;
                        
                            url += "&"+ get_menu_query_string('analysis', 'security_events', 'security_events');
                        
                        load_inframe(url);
					} 
					else if (action=='filter_src') 
					{
						var aux      = $(el).attr('id').split(/;/);
						var hostname = aux[1];
						
						var url  = "/ossim/forensics/base_qry_main.php?new=2&num_result_rows=-1&submit=Query+DB&current_view=-1&ip_addr_cnt=1&sort_order=time_d&search_str="+hostname+"&submit=Src+Host";
                        
                            url += "&"+ get_menu_query_string('analysis', 'security_events', 'security_events');
                        
                        load_inframe(url);
					} 
					else if (action=='filter_dst') 
					{
						var aux      = $(el).attr('id').split(/;/);
						var hostname = aux[1];
						
						var url  = "/ossim/forensics/base_qry_main.php?new=2&num_result_rows=-1&submit=Query+DB&current_view=-1&ip_addr_cnt=1&sort_order=time_d&search_str="+hostname+"&submit=Dst+Host";
                        
                            url += "&"+ get_menu_query_string('analysis', 'security_events', 'security_events');
                        
                        load_inframe(url);
					} 
					else if (action=='edit')
					{					
						var aux = $(el).attr('id').split(/;/);					
						
                        //Asset_ip;hostname;host_id
                        var ip  = aux[0];
                        var id  = aux[2];
                        var ctx = (typeof($(el).attr('ctx')) != "undefined") ? $(el).attr('ctx') : "";
                        
                        if (typeof(id) != 'undefined' && id.match(/[0-9A-Z]{32}/))
                        {
                            var url     = "/ossim/host/host_form.php?id="+id;
                            var caption = '<?php echo _("Modify Asset")?>';
                        }
                        else 
                        {
                            var url     = "/ossim/host/host_form.php?ip="+ip+"&ctx="+ctx;
                            var caption = '<?php echo _("New Asset")?>';
                        }			
                                                                                                                 
                        var height  = '720';
                        var width   = '700';    
                
                        load_greybox(caption, url, height, width);

					} 
					else if (action=='unique') 
					{
						var aux  = $(el).attr('id').split(/;/);
						var ip   = aux[0];
						var name = aux[1];
						
						if (name != '' && name != ip) 
						{
    						var url = "/ossim/forensics/base_stat_alerts.php?clear_criteria=ip_addr&sort_order=occur_d&search_str="+escape(name)+"&submit=Src+or+Dst+Host&search=1";
						} 
						else 
						{
    						var url = "/ossim/forensics/base_stat_alerts.php?clear_allcriteria=1&sort_order=occur_d&ip_addr_cnt=1&ip_addr%5B0%5D%5B0%5D+=&ip_addr%5B0%5D%5B1%5D=ip_both&ip_addr%5B0%5D%5B2%5D=%3D&ip_addr%5B0%5D%5B3%5D="+ip+"&ip_addr%5B0%5D%5B8%5D+=";
						}
                        
                        url += "&"+ get_menu_query_string('analysis', 'security_events', 'security_events');
                        
                        load_inframe(url);
					} 
					else if (action=='info') 
					{
						var aux  = $(el).attr('id').split(/;/);
						var ip   = aux[0];
												
						var url  = "/ossim/forensics/base_stat_ipaddr.php?ip="+ip+"&netmask=32";
						    url += "&"+ get_menu_query_string('analysis', 'security_events', 'security_events');
						
                        load_inframe(url);
					} 
					else if (action=='tickets') 
					{
						var aux  = $(el).attr('id').split(/;/);
						var ip   = aux[0];
						var url  = "/ossim/incidents/index.php?status=Open&with_text="+ip;
                        
                            url += "&"+ get_menu_query_string('analysis', 'tickets', 'tickets');
                        
                        load_inframe(url);
					} 
					else if (action=='alarms') 
					{
						var aux  = $(el).attr('id').split(/;/);
						var ip   = aux[0];
						var url  = "/ossim/alarm/alarm_console.php?hide_closed=1&src_ip="+ip+"&dst_ip="+ip;                        
                                              
                            url += "&"+ get_menu_query_string('analysis', 'alarms', 'alarms');
                        
                        load_inframe(url);
					} 
					else if (action=='sem') 
					{
						var aux = $(el).attr('id').split(/;/);
						var ip  = aux[1];
						
						var date_from   = (typeof($(el).attr('date_from')) != "undefined") ? $(el).attr('date_from') : "";
						var date_to     = (typeof($(el).attr('date_to')) != "undefined")   ? $(el).attr('date_to')   : "";
						var time_filter = (date_from != "") ? "date6" : "";
						
						var url  = "/ossim/sem/index.php?query=ip%3D"+ip+"&current_time_start_aaa="+date_from+"&current_time_end_aaa="+date_to+"&current_time_filter="+time_filter;                  
                        
                            url += "&"+ get_menu_query_string('analysis', 'raw_logs', 'raw_logs');                                
                        
                        load_inframe(url);                                                             
					} 
					else if (action=='detail') 
					{
						var aux       = $(el).attr('id').split(/;/);
						var asset_key = aux[2];
						
						var url  = "/ossim/asset_details/index.php?id="+asset_key;                        
                        
					    url += "&"+ get_menu_query_string('environment', 'assets', 'assets');                
                    
                        load_inframe(url);
					}
					else if (action=='vulns') 
					{
						var aux = $(el).attr('id').split(/;/);
						var ip = aux[0];                     
                        
                        var url  = "/ossim/vulnmeter/index.php?submit=Find&type=net&rvalue="+ip+"&sortby=t1.results_sent+DESC%2C+t1.name+DESC";                      
                        
                            url += "&"+ get_menu_query_string('environment', 'vulnerabilities', 'overview');      
                        
                        load_inframe(url);
					}
					else if (action=='ntop') 
					{
						var aux = $(el).attr('id').split(/;/);
						var ip  = aux[0];
						
						var url = "/ntop/"+ip+".html";                        
                        var wnd = top.window.open(url,'ntop_'+ip,'scrollbars=yes,location=no,toolbar=no,status=no,directories=no');                     		
					} 
					else if (action=='flows_rse') 
					{
						var aux = $(el).attr('id2');
						if (typeof(aux) != 'undefined') 
						{
							var aux2 = aux.split(/;/);
							var ip   = aux2[0]; var ip2 = aux2[1];
							var url  = "/ossim/nfsen/nfsen.php?tab=2&ip="+ip+"&ip2="+ip2;
						} 
						else 
						{
							var aux = $(el).attr('id').split(/;/);
							var ip  = aux[0];							
							var url = "/ossim/nfsen/nfsen.php?tab=2&ip="+ip;
						}	
						
						url += "&"+ get_menu_query_string('environment', 'netflow', 'details'); 					    
																							
                        load_inframe(url);
					} 
					else if (action=='flows') 
					{
						var aux = $(el).attr('id').split(/;/);
						var ip  = aux[0];					                        
                        
                        var url = "/ossim/nfsen/nfsen.php?tab=2&ip="+ip;
                                                
						url += "&"+ get_menu_query_string('environment', 'netflow', 'details');			
														
                        load_inframe(url);
					} 
					else if (action=='nagios') 
					{
						var aux  = $(el).attr('id').split(/;/);
						var ip   = aux[0];
					    var name = (aux[1] != '') ? aux[1] : ip;
						
						var url = "/secured_nagios3/cgi-bin/status.cgi?host="+name;						
						var wnd = top.window.open(url,'nagios_'+name,'scrollbars=yes,location=no,toolbar=no,status=no,directories=no');
					} 
					else if (action=='whois') 
					{
						var aux = $(el).attr('id').split(/;/);
                        var ip  = aux[0];
						
						var url = "http://whois.domaintools.com/"+ip;
                        var wnd = top.window.open(url,'whois_'+ip,'scrollbars=yes,location=no,toolbar=no,status=no,directories=no');
					}
				}
			);
			
    		$('.NetReportMenu').contextMenu({
    				menu: 'myMenuNet'
    			},
				
				function(action, el, pos) 
				{
					// Temporary disabled					
				}
			);
    		    		
    		
    		$('.trlnka').contextMenu({
    			menu: 'myMenuSid'
    		},
    		
			function(action, el, pos) 
			{
				if (action == "addsid") 
				{
                    var aux = $(el).attr('id').split(/;/);
                    var plugin_id  = aux[0];
                    var plugin_sid = aux[1];
                    
                    var url    = "/ossim/policy/insertsid.php?plugin_id="+plugin_id+"&plugin_sid="+plugin_sid;
                    var caption = "<?php echo _("Select a DS Group")?>";
                    					
                    
                    var height  = '650';
                    var width   = '65%';    
        
					load_greybox(caption, url, height, width);
				} 
				else if (action == "lookupsid") 
				{
					var aux        = $(el).attr('id').split(/;/);
					var plugin_id  = aux[0];
					var plugin_sid = aux[1];
					
					var url  = "/ossim/forensics/base_qry_main.php?new=1&submit=Query%20DB&num_result_rows=-1&sig_type=1&sig%5B0%5D=%3D&sig%5B1%5D="+plugin_id+"%3B"+plugin_sid;	    
				        url += "&"+ get_menu_query_string('analysis', 'security_events', 'security_events');
				    					
					load_inframe(url);
				}
				else if (action == "pluginsid") 
				{
					var url     = "/ossim//sem/index.php?query=" + encodeURIComponent( 'plugin_sid='+$(el).attr('id').replace(';','-') ) + "&" + get_menu_query_string('analysis', 'raw_logs', 'raw_logs');
				    					
					load_inframe(url);
				}				
			});				
    	}
	
    	$(document).ready(function(){
    		
    		load_contextmenu();
    		
            if (typeof postload == 'function') 
            {
                postload();
            }  
    	});
    	    	
    	</script>
    	<?php 
	} 
?>

<ul id="myMenu" class="contextMenu">
    <?php 
    if ($ipsearch) 
    { 
        ?>
        <li class="search"><a href="#filter"><?php echo _("All events from this host")?></a></li>
        <li class="search"><a href="#filter_src"><?php echo _("Events as source")?></a></li>
        <li class="search"><a href="#filter_dst"><?php echo _("Events as destination")?></a></li>
        <li class="info"><a href="#info"><?php echo _("Stats and Info")?></a></li>
        <li class="search"><a href="#unique"><?php echo _("Analyze Asset")?></a></li>
        <?php 
    } 
    ?>
    <li class="detail"><a href="#detail"><?php echo _("Asset Detail")?></a></li>
    <li class="edit"><a href="#edit"><?php echo _("Configure Asset")?></a></li>
    <li class="whois"><a href="#whois"><?php echo _("Whois")?></a></li>
    <li class="tickets"><a href="#tickets"><?php echo _("Tickets")?></a></li>
    <li class="alarms"><a href="#alarms"><?php echo _("Alarms")?></a></li>
    <?php

    require_once 'ossim_conf.inc';
    
    $conf       = $GLOBALS["CONF"];
    $version    = $conf->get_conf("ossim_server_version");
    $opensource = (!preg_match("/.*pro.*/i",$version) && !preg_match("/.*demo.*/i",$version)) ? TRUE : FALSE;
    
    if (!$opensource) 
    {             
        ?>
        <li class="sem"><a href="#sem"><?php echo _("Log")?></a></li>
        <?php 
    } 
    
    if (!$ipsearch) 
    {        
        ?>
        <li class="sim"><a href="#filter"><?php echo _("Security Events")?></a></li>
        <li class="sim"><a href="#filter_rse"><?php echo _("Related Security Events")?></a></li>
        <li class="sim"><a href="#filter_rsfe"><?php echo _("Related Security Firewall Events")?></a></li>
        <?php 
    } 
    ?>
    <li class="vulns"><a href="#vulns"><?php echo _("Vulnerabilities")?></a></li>
    <li class="ntop"><a href="#ntop"><?php echo _("Net Profile")?></a></li>
    <li class="flows"><a href="#flows"><?php echo _("Traffic")?></a></li>
    <li class="flows"><a href="#flows_rse"><?php echo _("Related Traffic")?></a></li>
    <li class="nagios"><a href="#nagios"><?php echo _("Availability")?></a></li>
</ul>

<ul id="myMenuNet" class="contextMenu">
    <li class="detail"><a href="#detail"><?php echo _("Network Detail")?></a></li>
</ul>

<ul id="myMenuSid" class="contextMenu">
    <li class="editds"><a href="#addsid"><?php echo _("Add this Event Type to a DS Group")?></a></li>
    <li class="search"><a href="#lookupsid"><?php echo _("Lookup by Signature")?></a></li>
    <li class="search"><a href="#pluginsid"><?php echo _("Logs by Signature")?></a></li>
</ul>
