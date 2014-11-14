<?php
/*******************************************************************************
** OSSIM Forensics Console
** Copyright (C) 2009 OSSIM/AlienVault
** Copyright (C) 2004 BASE Project Team
** Copyright (C) 2000 Carnegie Mellon University
**
** (see the file 'base_main.php' for license details)
**
** Built upon work by Roman Danyliw <rdd@cert.org>, <roman@danyliw.com>
** Built upon work by the BASE Project Team <kjohnson@secureideas.net>
**/


/**
* Function list:
* - PrintBASESubHeader()
* - PrintBASESubFooter()
* - PrintFramedBoxHeader()
* - PrintCustomViews()
* - PrintFramedBoxFooter()
* - PrintFreshPage()
* - chk_select()
* - chk_check()
* - dispYearOptions()
* - PrintBASEAdminMenuHeader()
* - PrintBASEAdminMenuFooter()
* - PrintBASEHelpLink()
*/


defined('_BASE_INC') or die('Accessing this file directly is not allowed.');
require_once ('classes/Util.inc');

function PrintBASESubHeader($page_title, $page_name, $back_link, $refresh = 0, $page = "") {
    GLOBAL $db, $timetz, $debug_mode, $BASE_VERSION, $BASE_path, $BASE_urlpath, $html_no_cache, $max_script_runtime, $Use_Auth_System, $stat_page_refresh_time, $refresh_stat_page, $ossim_servers, $sensors, $hosts, $database_servers, $DBlib_path, $DBtype, $db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password;
    if (ini_get("safe_mode") != true) set_time_limit($max_script_runtime);
    ?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=<?php echo gettext("iso-8859-1") ?>"/>
            <?php if ($html_no_cache == 1) { ?><meta http-equiv="pragma" content="no-cache"/><?php } ?>
            <?php if ($refresh == 1 && !$_SESSION['norefresh']) PrintFreshPage($refresh_stat_page, $stat_page_refresh_time); ?>
            
            <!-- Included Styles -->
            <link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
            <link rel="stylesheet" type="text/css" href="/ossim/style/analysis/security_events/security_events.css"/>

            <link rel="stylesheet" type="text/css" href="/ossim/style/jquery-ui.css"/>
            <link rel="stylesheet" type="text/css" href="/ossim/style/jquery.tag-it.css"/>
            <!-- <link rel="stylesheet" type="text/css" href="/ossim/style/flexigrid.css"/> -->
            <link rel="stylesheet" type="text/css" href="/ossim/style/jquery.autocomplete.css"/>
            <link rel="stylesheet" type="text/css" href="/ossim/style/tipTip.css"/>
            <link rel="stylesheet" type="text/css" href="/ossim/style/jslider.css"/>
            <link rel="stylesheet" type="text/css" href="/ossim/style/flipswitch.css"/>
            <link rel="stylesheet" type="text/css" href="/ossim/style/datepicker.css"/>
            
            <!-- Manual Styles -->
            <style type="text/css">
                
                #adv_search_button
                {
                    margin:0px 0px 0px 5px;
                }
                #views table, #taxonomy table, #mfilters table, #report table  {
                    background:none repeat scroll 0 0 #FAFAFA;
                    border:1px solid #BBBBBB;
                    color:black;
                    text-align:center;
                   -moz-border-radius:8px 8px 8px 8px;
                   padding: 2px;
                }
                
                #views table tr td, #taxonomy table tr td, #mfilters table tr td, #report table tr td{
                    padding: 0;
                }
                #views table tr td input, #views table, 
                #taxonomy table tr td input, #taxonomy table,
                #taxonomy table tr td input, #report table,
                #mfilters table tr td input, #mfilters table
                {
                    font-size: 0.9em;
                    line-height: 0.5em;
                }
                
                #views table tr td ul{
                    padding: 0px;
                }
                #views table tr td ul li{
                    padding: 0px 0px 0px 12px;
                    list-style-type: none;
                    text-align: left;
                    margin: 0px;
                    clear:left;
                    position: relative;
                    height: 23px;
                    line-height: 1em;
                }
                .margin0
                {
                    margin: 0px;
                }
                .left_np
                {
                    text-align: left;
                }
                .par{
                    background: #f2f2f2;
                }
                .impar{
                    background: #fff;
                }
                .padding_right_5
                {
                    padding: 0px 5px 0px 0px;
                }
                .padding_top_5
                {
                    padding: 5px 0px 0px 0px;
                }
                .float_left
                {
                    float: left;
                }
                .float_right
                {
                    float: right;
                }
                #views table tr th, #taxonomy table tr th, #mfilters table tr th{
                    white-space:nowrap;
                	padding:1px 10px;
                	border: 1px solid #CCCCCC;
                	font-size: 11px;
                	color: #222222;
                	font-weight: bold;
                	text-align: center;
                	background: #E5E5E5;
                	background: -webkit-linear-gradient(#EFEFEF, #E5E5E5);
                	background: -moz-linear-gradient(#EFEFEF, #E5E5E5);
                	background: -o-linear-gradient(#EFEFEF, #E5E5E5);
                	filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#EFEFEF', endColorstr='#E5E5E5');
                }
                
                
                #viewbox{
                	font-size: 1.5em;
                	margin: 0.5em;
                }
                
                #dhtmltooltip{
                position: absolute;
                width: 150px;
                border: 2px solid black;
                padding: 2px;
                background-color: lightyellow;
                visibility: hidden;
                z-index: 100;
                }
                
                img{
                	vertical-align:middle;
                }
                small {
                	font:12px arial;
                }
                
                #maintable{
                background-color: white;
                }
                #viewtable{
                background-color: white;
                }
                .negrita { font-weight:bold; font-size:14px; }
                .thickbox { color:gray; font-size:10px; }
                .header{
                line-height:28px; height: 28px; background: transparent url(../pixmaps/fondo_col.gif) repeat-x scroll 0% 0%; color: rgb(51, 51, 51); font-size: 12px; font-weight: bold; text-align:center;
                }
                
                .ne { color:black }
                .gr { color:#999999 }
                
                .disabled img {
                    filter:alpha(opacity=50);
                    -moz-opacity:0.5;
                    -khtml-opacity: 0.5;
                    opacity: 0.5;
                }
                
                td.head {
                    border:1px solid #CCCCCC;
                    
                    background: #E5E5E5;
                    background: -webkit-linear-gradient(#EFEFEF, #e5e5e5);
                    background: -moz-linear-gradient(#EFEFEF, #e5e5e5); 
                    background: -o-linear-gradient(#EFEFEF, #e5e5e5);
                    filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#efefef', endColorstr='#e5e5e5');
                    
                    font-size:14px;font-weight:bold;
                    color:#333333;
                }
                
                .left13 {
                	    text-align:left;
                	    font-size:13px;
                }
                
                ul.tagit
                {
                    margin: 0px;
                    border:none;
                }
                
                .separated
                {
                    border-spacing: 0px;
                    border-collapse: separated;
                	    padding: 0px;
                }
                
                .separated td
                {
                	    padding: 4px 4px 4px 0px;
                }
                                
            </style>
            
            <!-- jQuery and Javascript -->
            <!--[if IE]><script language="javascript" type="text/javascript" src="../js/jqplot/excanvas.js"></script><![endif]-->
		    <script type="text/javascript" src="../js/jquery.min.js"></script>
		    <script type="text/javascript" src="/ossim/js/jquery-ui.min.js"></script>
		    <script type="text/javascript" src="../js/greybox.js"></script>
            <script type="text/javascript" src="../js/jquery.flot.pie.js" language="javascript"></script>
            <script type="text/javascript" src="../js/jquery.bgiframe.min.js" language="javascript"></script>
            <script type="text/javascript" src="../js/jquery.autocomplete.pack.js" language="javascript"></script>
            <!-- <script type="text/javascript" src="../js/jquery.simpletip.js"></script> -->
            <script type="text/javascript" src="../js/jquery.tipTip-ajax.js"></script>
            
            <!-- jSlider -->
            <script type="text/javascript" src="../js/jslider/jshashtable-2.1_src.js"></script>
            <script type="text/javascript" src="../js/jslider/jquery.numberformatter-1.2.3.js"></script>
            <script type="text/javascript" src="../js/jslider/tmpl.js"></script>
            <script type="text/javascript" src="../js/jslider/jquery.dependClass-0.1.js"></script>
            <script type="text/javascript" src="../js/jslider/draggable-0.1.js"></script>
            <script type="text/javascript" src="../js/jslider/jquery.slider.js"></script>
            <script type="text/javascript" src="../js/jquery.tag-it.js"></script>
            <script type="text/javascript" src="../js/jquery.placeholder.js"></script>
            
            
            <? $ipsearch=1; include ("../host_report_menu.php") ?>
            
            <!-- Javascript functions -->
            <script type="text/javascript">

            // ***** Variables *****
            
            // Used in tooltips
            var url   = new Array(50);
            
            // For greybox
            var nogb  = false;
            
            // Used in calendar
            var state = false;
            
            // Selected Tab
            var current_section = "<?php echo (preg_match("/base_timeline/", $_SERVER['SCRIPT_NAME'])) ? "timeline" : ((preg_match("/base_stat/", $_SERVER['SCRIPT_NAME']) && $_SERVER['SCRIPT_NAME'] != '/ossim/forensics/base_stat_ipaddr.php') ? "grouped" : "events") ?>";
            
            // ***** Functions *****
            
            // Tooltip used in unique events plots
            function showTooltip(x, y, contents, link) {
            		link = link.replace(".","");
                    link = link.replace(",","");
            		$('<div id="tooltip" class="tooltipLabel" onclick="load_link(\'' + url[link] + '&submit=Query DB\')"><a href="' + url[link] + '&submit=Query DB" style="font-size:10px;">' + contents + '</a></div>').css( {
            			position: 'absolute',
            			display: 'none',
            			top: y - 28,
            			left: x - 10,
            			border: '1px solid #ADDF53',
            			padding: '1px 2px 1px 2px',
            			'background-color': '#CFEF95',
            			opacity: 0.80
            		}).appendTo("body").fadeIn(200);
            	}
            
            	Array.prototype.in_array = function(p_val) {
            		for(var i = 0, l = this.length; i < l; i++) {
            			if(this[i] == p_val) {
            				return true;
            			}
            		}
            		return false;
            	}

            	// Auxiliary function for sensor input autocomplete
            	function mix_sensors(val) {
            		var sval = val.split(',');
            		if ($("#sensor").val() != "") var aval = $("#sensor").val().split(',');
            		else var aval = [];
            		var mixed = [];
            		var ind = 0;
            		for(var i = 0, l = sval.length; i < l; i++) {
            			if (aval.length>=0 || aval.in_array(sval[i])) // Before aval.length==0
            				mixed[ind++] = sval[i];
            		}
            		var str = "";
            		
            		if (mixed.length > 0) {
            			str = mixed[0];
            			for(var i = 1, l = mixed.length; i < l; i++) {
            				str = str + ',' + mixed[i];
            			}
            			//alert($("#sensor").val()+" + "+val+" = "+str);
            		}
            		// return intersection
            		$("#sensor").val(str);
            	}

            	// Used to delete events in background
            	function bgtask() {
            		$.ajax({
            			type: "GET",
            			url: "base_bgtask.php",
            			data: "",
            			success: function(msg) {
                            var redirection = false;
            				if (msg.match(/No pending tasks/)) {
                                if($("#task").is(":visible")) { // check if there was a pending task
                                    var redirection = true;
                                }
            					if ($("#task").is(":visible")) $("#task").toggle();
            					setTimeout("bgtask()",5000);
                                if(redirection) {
                                    load_link('./base_qry_main.php?num_result_rows=-1&submit=Query+DB&current_view=-1');
                                }
            				} else {
            					if ($("#task").is(":hidden")) $("#task").toggle();
            					$("#task").html("<img style='border: none' src='./images/sandglass.png'> Deleting in background...");
            					setTimeout("bgtask()",5000);
            				}
            			}
            		});
            	}

            	// Used in plot response
            	function SetIFrameSource(cid, url) {
                var myframe = document.getElementById(cid);
                if(myframe !== null) {
                    if(myframe.src){
                        myframe.src = url; }
                    else if(myframe.contentWindow !== null && myframe.contentWindow.location !== null){
                        myframe.contentWindow.location = url; }
                    else{ myframe.setAttribute('src', url); }
                }	
            }

            	// Used in top plot toggle
            	function trendgraph() {
                if ($("#iplot").is(":visible") == false) {
                    $('#graph_arrow').attr("src", "../pixmaps/arrow_green_down.png");
                    $('#iplot').toggle();
                    $('#loadingTrend').show();
                    SetIFrameSource('processframe','base_plot.php')
                } else {
                	$('#graph_arrow').attr("src", "../pixmaps/arrow_green.png");
                    $('#iplot').toggle();
                }
            }
            
            function show_search_tooltip()
            {
                var tooltip = 
                {
                    "<?php echo _('Signature') ?>"       : 1,
                    "<?php echo _('Payload') ?>"         : 1,
                    "<?php echo _('Src or Dst IP') ?>"   : 1,
                    "<?php echo _('Src IP') ?>"          : 1,
                    "<?php echo _('Dst IP') ?>"          : 1,
                    "<?php echo _('Src or Dst Host') ?>" : 2,
                    "<?php echo _('Src Host') ?>"        : 2,
                    "<?php echo _('Dst Host') ?>"        : 2
                }
                
                var selected = $(this).val();

                if (selected in tooltip)
                {                   
                    var ul = $('<ul></ul>');
                    
                    if (tooltip[selected] == 1)
                    {
                        $('<li></li>',
                        {
                            text: "<?php echo _('Conjunction: ') ?> 'AND'"
                        }).appendTo(ul)
                        
                        $('<li></li>',
                        {
                            text: "<?php echo _('Disjunction: ') ?> 'OR'"
                        }).appendTo(ul)
                    }
                    
                    $('<li></li>',
                    {
                        text: "<?php echo _('Negation: ') ?> '!'"
                    }).appendTo(ul)
                    
                    var content = $('<div></div>',
                    {
                        id  : "search_opt_tip",
                        text: "<?php echo _('For this search option you can use the following operator(s) to perform complex searches:') ?>"
                    })
                    
                    content.append(ul)
                                        
                    $('#help_tooltip').removeData("tipTip").tipTip( 
                    {
                        maxWidth: "300px",
                        content: content
                    }).show();
                    
                }
                else
                {
                    $('#help_tooltip').hide().tipTip('destroy');
                } 
                
            }

            function show_calendar()
            {
                $('#date_from').trigger('focus');
            }


            	// Button more filters button action
            	function more_filters_toggle()
            	{
            		if ($('#more_filters').is(":visible"))
            		{
            			$('#more_filters').hide();
            			$('#more_filters_button').val("+ <?php echo _("More Filters") ?>");
            		}
            		else
            		{
            			$('#more_filters').show();
            			$('#more_filters_button').val("- <?php echo _("More Filters") ?>");
            		}
            	}

            	// Auxiliary format number for plot hovers
            	function formatNmb(nNmb){
            		var sRes = ""; 
            		for (var j, i = nNmb.length - 1, j = 0; i >= 0; i--, j++)
            			sRes = nNmb.charAt(i) + ((j > 0) && (j % 3 == 0)? "<?=thousands_locale()?>": "") + sRes;
            		return sRes;
            	}

            	// [Events, Grouped, Timeline]
            	function load_section(section)
            	{
            		// Some layer changes when no page reload needed
            	    if (section == "grouped")
            	    {
            	        $('#plot_option').hide();
            	        $('#grouped_option').show();
            	    }

            	    if (section == "events")
            	    {
            	        $('#grouped_option').hide();
            	        $('#plot_option').show();
            	    }

            	    if (section == "timeline")
            	    {
            	        $('#grouped_option').hide();
            	    }

            	    current_section = section;
            	    
            	    $('#criteria_tagit').tagit(
                    {
                        onlyAllowDelete: true,
                        beforeTagRemoved: function(event, ui) 
                        {
                            var url   = $(ui.tag).data('info');
                            
                            if(typeof url != 'undefined' && url != '')
                            {
                                load_link(url);
                            }
                        }
                    });
            	}

                function load_link(url)
                {
                    if (typeof(parent.show_overlay_spinner)=='function') parent.show_overlay_spinner();
                    document.location.href=url;
                }
            	// Custom Views
            	// Get default view
            	<?php
            	require_once("ossim_conf.inc");
            	$conf = $GLOBALS["CONF"];
            	$idm_enabled    = ( $conf->get_conf("enable_idm", FALSE) == 1 && Session::is_pro() )     ? true : false;
            	$login = Session::get_session_user();
            	
            	$config = new User_config($db);
            	
            	$default_view = ($config->get($login, 'custom_view_default', 'php', "siem") != "") ? $config->get($login, 'custom_view_default', 'php', "siem") : (($idm_enabled) ? 'IDM' : 'default');
            	?>
            	var default_view = "<?php echo $default_view ?>";
            	function set_default_view(name) {
            		$('#view_star_'+name).attr('src', '../pixmaps/loading.gif');
            		$.ajax({
            			type: "GET",
            			url: "custom_view_save.php",
            			data: "name="+name+"&set_default=1",
            			success: function(msg) {
            				if (msg != "") {
            					alert(msg);
            				} else {
            					$('.view_star').attr('src', '../pixmaps/star-small-empty.png');
            					$('#view_star_'+name).attr('src', '../pixmaps/star-small.png');
            					default_view = name;
            				}
            			}
            		});
            	}
            	
            	function change_view(view)
            	{
            		var url = "base_qry_main.php?num_result_rows=-1&submit=Query+DB&current_view=-1&custom_view="+view;
            		load_link(url);
            	}
            	
            	function save_view(id_img)
            	{
            		var img = $('#'+id_img).attr('src').split('/');
            	    img = img[img.length-1];
            	    var url = '../pixmaps/';
            		
            		var src1='loading3.gif';
            		var src2='tick.png';
            		
            		$('#'+id_img).attr('src', url+src1);
            						
            		$.ajax({
            			type: "GET",
            			url: "custom_view_save.php",
            			data: "",
            			success: function(msg) {
            				$('#'+id_img).attr('src', url+src2);
            				setTimeout("($('#"+id_img+"').attr('src', '"+url+img+"'))",1000);
            			}
            		});
            		
            		
            	}
            	
            	function delete_view(name)
            	{
            		$.ajax({
            			type: "GET",
            			url: "custom_view_delete.php",
            			data: "name="+name,
            			success: function(msg) {
            				if (msg != "") {
            					alert(msg);
            				} else {
            					var url = "base_qry_main.php?num_result_rows=-1&submit=Query+DB";
            					load_link(url);
            				}
            			}
            		});
            	}

            	// Greybox
            	//function GB_hide() { document.location.reload() }
            //function GB_onclose() { nogb=false; }
            function GB_onclose()
            {
                if (typeof(parent.show_overlay_spinner)=='function') parent.show_overlay_spinner();
                document.location.reload();
            }

            // Triggered by custom_view_edit.php when it creates or deletes
            function GB_onhide(url, params)
            {
            	if (url.match(/newincident/))
        		{
            		document.location.href="../incidents/index.php?m_opt=analysis&sm_opt=tickets&h_opt=tickets"
            		
            		return false
        		}
        		
            	if (typeof(params) == 'object' && typeof params['change_view'] != 'undefined')
            	{
            	    change_view(params['change_view']);
            	    
            	    return false
            	}
            }

            // Solera
            function solera_deepsee (from,to,src_ip,src_port,dst_ip,dst_port,proto)
            {
                $('#solera_form input[name=from]').val(from);
                $('#solera_form input[name=to]').val(to);
                $('#solera_form input[name=src_ip]').val(src_ip);
                $('#solera_form input[name=src_port]').val(src_port);
                $('#solera_form input[name=dst_ip]').val(dst_ip);
                $('#solera_form input[name=dst_port]').val(dst_port);
                $('#solera_form input[name=proto]').val(proto);
                GB_show_post('Solera DeepSee &trade;','#solera_form',300,600);
            }

            // Events grouping button click
            function dsgroup_for_selected()
            {
                	var idlist = "";
                	var sidlist = "";
                	$("input:checkbox:checked").each(function() {
                		if(this.className == "trlnks") {
                			if (idlist != "") idlist += ",";
                			if (sidlist != "") sidlist += ",";
                			idlist += this.getAttribute('pid');
                			sidlist += this.getAttribute('psid');
                		}
                	});
                	if (idlist != "" && sidlist != "") {
                		GB_show("<?php echo _("Insert into existing DS Group") ?>","/policy/insertsid.php?plugin_id="+idlist+"&plugin_sid="+sidlist,'650','65%');
                	}
            }

            // Top refresh link
            function re_load()
            {
                if (typeof(parent.show_overlay_spinner)=='function') parent.show_overlay_spinner();                
                if (typeof(pag_reload)=='function') pag_reload();
            }

            // Select all when DeleteAllOnScreen button click
            function click_all(bt)
            {
                $("input[name^='action_chk_lst']").each(function() { $(this).attr('checked',true); });
                $('#eqbtn'+bt).click()
            }

            // Group By selection
            function group_selected(val)
            {
                // Reset
                $('#group_button').hide();
                $('#group_ip_select').css('display', 'none');
                $('#group_hostname_select').css('display', 'none');
                $('#group_username_select').css('display', 'none');
                $('#group_port_select').css('display', 'none');
                $('#group_proto_select').css('display', 'none');

                // Second level
                if (val.match("^ip"))
                {
                    $('#group_ip_select').css('display', 'inline');
                }
                if (val.match("^hostname"))
                {
                    $('#group_hostname_select').css('display', 'inline');
                }
                if (val.match("^username"))
                {
                    $('#group_username_select').css('display', 'inline');
                }
                if (val.match("^port"))
                {
                    $('#group_port_select').css('display', 'inline');

                    // Third level (Ports)
                    if ($('#group_port_select').find(":selected").val() != "portempty")
                    {
                        if (val.match("port(src|dst)") || val.match("proto") || $('#group_proto_select').find(":selected").val() != "")
                        {
                            $('#group_proto_select').css('display', 'inline');
                        }
                    }
                }

                // Show Group Button (All options are ready to go)
                if (val == "signature"
                || val == "sensor"
                || val == "ptypes"
                || val == "plugins"
                || val == "country"
                || val == "categories"

                || (val.match("^ip")
                        && $('#groupby_ip').find(":selected").val() != "ipempty")
                
                || (val.match("^hostname")
                        && $('#groupby_hostname').find(":selected").val() != "hostnameempty")
                
                || (val.match("^username")
                        && $('#groupby_username').find(":selected").val() != "usernameempty")
                
                || (val.match("^port")
                        && $('#group_port_select').find(":selected").val() != "portempty"
                        && $('#group_proto_select').find(":selected").val() != "portprotoempty")) 
                {
                    $('#group_button').show();
                }
            }

            // Group by go
            function go_stats()
            {
                if ($('#groupby_1').val() == "ip")
                {
                    if ($('#groupby_ip').val() == "iplink")
                    {
                        load_link("base_stat_iplink.php?sort_order=events_d&fqdn=no");
                    }
                    else if ($('#groupby_ip').val() == "iplink_fqdn")
                    {
                        load_link("base_stat_iplink.php?sort_order=events_d&fqdn=yes");
                    }
                    else if ($('#groupby_ip').val() == "ipsrc")
                    {
                        load_link("base_stat_uaddr.php?addr_type=1&sort_order=occur_d");
                    }
                    else if ($('#groupby_ip').val() == "ipdst")
                    {
                        load_link("base_stat_uaddr.php?addr_type=2&sort_order=occur_d");
                    }
                    else if ($('#groupby_ip').val() == "ipboth")
                    {
                        load_link("base_stat_uaddress.php?sort_order=occur_d");
                    }
                }
                else if ($('#groupby_1').val() == "hostname")
                {
                    if ($('#groupby_hostname').val() == "hostnamesrc")
                    {
                        load_link("base_stat_uidmsel.php?addr_type=src_hostname&sort_order=occur_d");
                    }
                    else if ($('#groupby_hostname').val() == "hostnamedst")
                    {
                        load_link("base_stat_uidmsel.php?addr_type=dst_hostname&sort_order=occur_d");
                    }
                    else
                    {
                        load_link("base_stat_uidm.php?addr_type=hostname&sort_order=occur_d");
                    }
                }
                else if ($('#groupby_1').val() == "username")
                {
                    if ($('#groupby_username').val() == "usernamesrc")
                    {
                        load_link("base_stat_uidmsel.php?addr_type=src_userdomain&sort_order=occur_d");
                    }
                    else if ($('#groupby_username').val() == "usernamedst")
                    {
                        load_link("base_stat_uidmsel.php?addr_type=dst_userdomain&sort_order=occur_d");
                    }
                    else
                    {
                        load_link("base_stat_uidm.php?addr_type=userdomain&sort_order=occur_d");
                    }
                }
                else if ($('#groupby_1').val() == "signature")
                {
                    load_link("base_stat_alerts.php?sort_order=occur_d");
                }
                else if ($('#groupby_1').val() == "port")
                {
                    if ($('#groupby_port').val() == "portsrc")
                    {
                        if ($('#groupby_proto').val() == "portprototcp")
                        {
                            load_link("base_stat_ports.php?sort_order=occur_d&port_type=1&proto=6");
                        }
                        else if ($('#groupby_proto').val() == "portprotoudp")
                        {
                            load_link("base_stat_ports.php?sort_order=occur_d&port_type=1&proto=17");
                        }
                        else if ($('#groupby_proto').val() == "portprotoany")
                        {
                            load_link("base_stat_ports.php?sort_order=occur_d&port_type=1&proto=-1");
                        }
                    }
                    else if ($('#groupby_port').val() == "portdst")
                    {
                        if ($('#groupby_proto').val() == "portprototcp")
                        {
                            load_link("base_stat_ports.php?sort_order=occur_d&port_type=2&proto=6");
                        }
                        else if ($('#groupby_proto').val() == "portprotoudp")
                        {
                            load_link("base_stat_ports.php?sort_order=occur_d&port_type=2&proto=17");
                        }
                        else if ($('#groupby_proto').val() == "portprotoany")
                        {
                            load_link("base_stat_ports.php?sort_order=occur_d&port_type=2&proto=-1");
                        }
                    }
                }
                else if ($('#groupby_1').val() == "sensor")
                {
                    load_link("base_stat_sensor.php?sort_order=occur_d");
                }
                else if ($('#groupby_1').val() == "ptypes")
                {
                    load_link("base_stat_ptypes.php?sort_order=occur_d");
                }
                else if ($('#groupby_1').val() == "plugins")
                {
                    load_link("base_stat_plugins.php?sort_order=occur_d");
                }
                else if ($('#groupby_1').val() == "country")
                {
                    load_link("base_stat_country.php");
                }
                else if ($('#groupby_1').val() == "categories")
                {
                    load_link("base_stat_categories.php?sort_order=occur_d");
                }
            }

            // Postload action (call from host_report_menu.php)
            function postload() {
            	   if (typeof(parent.hide_overlay_spinner)=='function' && parent.is_loading_box())
            	   {
            	       parent.hide_overlay_spinner();                
                   }
                   // Show spinner on form submit
                   $('#go_button,#bsf').on('click',function(){
                        if (typeof(parent.show_overlay_spinner)=='function') parent.show_overlay_spinner(); 
                   });
                   
            		// CAPTURE ENTER KEY
            		$("#search_str").bind("keydown", function(event) {
            			// track enter key
            			var keycode = (event.keyCode ? event.keyCode : (event.which ? event.which : event.charCode));
            			if (keycode == 13) { // keycode for enter key
            				$('#submit').val('<?php echo _("Signature") ?>');
            				$('#go_button').click();
            				return false;
            			} else  {
            				return true;
            			}
            		});
            		// TOOLTIPS
            		$('.scriptinfo').tipTip({
            			defaultPosition: "right",
            			content: function (e) {
            				var ip  = $(this).attr('data-title').replace(/\-.*/,'');
            				var ctx = $(this).attr('data-title').replace(/.*\-/,'');
            				$.ajax({
            					url: 'base_netlookup.php?ip=' + ip + ';' + ctx,
            					success: function (response) {
            						e.content.html(response); // the var e is the callback function data (see above)
            					}
            				});
            				return '<?php echo _("Searching")."..."?>'; // We temporary show a Please wait text until the ajax success callback is called.
            			}
            	    });
            	   $('.task_info').tipTip({
                       defaultPosition: "down",
                       delay_load: 100,
                       maxWidth: "auto",
                       edgeOffset: 3,
                       keepAlive:false,
                       content: function (e) {               
                           $.ajax({
                               type: 'GET',
                               url: 'base_bgtask.php',
                               success: function (response) {                                                                                                                    
                                   e.content.html(response); // the var e is the callback function data (see above)
                               }
                           });
                           return '<?php echo _("Waiting status")."..."?>'; // We temporary show a Please wait text until the ajax success callback is called.               
                        }
                     });
            	    $('.riskinfo').tipTip({
            			defaultPosition: "left",
            			content: function (e) {
            				return $(this).attr('txt')
            			}
            	    });
            	    $('.idminfo').tipTip({
            			defaultPosition: "top",
            			content: function (e) {
            				return $(this).attr('txt')
            			}
            	    });
            	    $('.scriptinfoimg').tipTip({
            			defaultPosition: "right",
            			content: function (e) {
            				return $(this).attr('txt')
            			}
            	    });   
            	    $(".tztooltip").tipTip({
                        defaultposition: 'right',
                        content: function (e) {
            				return $(this).attr('txt')
            			}
            	    });	
            	    $('.scriptinf').tipTip({
            			defaultPosition: "bottom",
            			content: function (e) {
            				return $(this).attr('txt')
            			}
            	    });
            	    
            		// AUTOCOMPLETE SEARCH FACILITY FOR SENSOR
            	    <?php
				$snortsensors = GetSensorSids($db);
				$sns = array();
				$sensor_keys = array();
				if (Session::allowedSensors() != "") {
					$user_sensors = explode(",",Session::allowedSensors());
					foreach ($user_sensors as $user_sensor)
						$sensor_keys[$user_sensor]++;
				}
				else $sensor_keys['all'] = 1;
				foreach($snortsensors as $ip => $sids) {
					//$ip = preg_replace ("/^\[.+\]\s*/","",$ip);
					$sid = implode(",", $sids);
					$sname = ($sensors[$ip] != "") ? $sensors[$ip] : $ip;
					$sns[$sname] = array($ip,$sid);
				}
				// sort by sensor name
				$sensor = ($_GET["sensor"] != "") ? $_GET["sensor"] : $_SESSION["sensor"];
				ksort($sns);
				$str = $notstr = $ipsel = $ents = "";
				foreach ($sns as $sname => $ip) {
					if ($sensor_keys['all'] || $sensor_keys[$ip[0]]) {
						$ip[0] = ($sname != "" && $sname != $ip[0]) ? "$sname [" . $ip[0] . "]" : $ip[0];
						$ip[0] = preg_replace ("/^\[(.+)\]\s*(.+)/","\\1 [\\2]",$ip[0]);
						if ($ipsel=="") {
							if     ($ip[1] != "" && $sensor == "!".$ip[1]) $ipsel = "$('#sip').val('!".$ip[0]."');";
							elseif ($ip[1] != "" && $sensor == $ip[1])     $ipsel = "$('#sip').val('".$ip[0]."');";
						}	
						$notstr .= '{ txt:"!'.$ip[0].'", id: "!'.$ip[1].'" },';
						$str .= '{ txt:"'.$ip[0].'", id: "'.$ip[1].'" },';
					}
				}
				
				// IP Selected
				echo $ipsel;
				
				$db_aux = new ossim_db();
				$conn_aux = $db_aux->connect();
				if (Session::is_pro())
                {
				    $my_entities = Acl::get_entities_to_assign($conn_aux);
				    foreach ($my_entities as $e_id => $e_name)
				    {
				        if(Session::get_entity_type($conn_aux, $e_id) != 'context') continue;
				        $ents .= '{ txt:"'. _('Context') .': '.$e_name.'", id: "'.$e_id.'" },';
				    }
				}
				$db_aux->close($conn_aux);
        			?>
            		var sensors = [
            			<?php echo preg_replace("/,$/","",$str.$notstr.$ents); ?>
            		];
            		$("#sip").autocomplete(sensors, {
            			minChars: 0,
            			width: 175,
            			max: 100,
            			matchContains: "word",
            			autoFill: true,
            			formatItem: function(row, i, max) {
            				return row.txt;
            			}
            		}).result(function(event, item) {
            			mix_sensors(item.id);
            			$("#bsf").click();
            		});
            		
            		<?php if (Session::is_pro()) { ?>
            		// AUTOCOMPLETE FOR DEVICE IP
            		<?php
				// Load IPs for autocomplete
				$device_ips      = "";
				$_already        = array();
				$_device_ips_aux = GetDeviceIPs($db);
				foreach ($_device_ips_aux as $_s_id=>$_ip) if (!$_already[$_ip]) {
					if ($device_ips != "") { $device_ips .= ","; }
					$device_ips .= "{ txt:\"$_ip\", id: \"$_ip\" }";
					$_already[$_ip]++;
				}
				?>
            		var device_ips = [<?php echo $device_ips ?>];
            		$("#device_input").autocomplete(device_ips, {
            			minChars: 0,
            			width: 175,
            			max: 100,
            			matchContains: "word",
            			autoFill: true,
            			formatItem: function(row, i, max) {
            				return row.txt;
            			}
            		}).result(function(event, item) {
            			$("#device_input").val(item.id);
            			$("#bsf").click();
            		});
            		<?php } ?>
    
            		var dayswithevents = [ <?php echo GetDatesWithEvents($db) ?> ];

            		/*  CALENDAR PLUGIN  */
            	    $('.date_filter').datepicker(
            	    {
            	        buttonText: "",
            	        showOn: "both",
            	        dateFormat: "yy-mm-dd",
            	        buttonImage: "/ossim/pixmaps/calendar.png",

            	        // Color of the cells
                    beforeShowDay: function ( date )
                    {
                        var classname = '';
                        
                        // With-Events color
                        var withevents = (dayswithevents.in_array(date.getTime())) ? ' evented-date' : ''
                        
                        return [true, classname + withevents];
                    },
            	        onClose: function(selectedDate)
            	        {
            	            // End date must be greater than the start date
                    
                            if ($(this).attr('id') == 'date_from')
                            {
                               $('#date_to').datepicker('option', 'minDate', selectedDate );
                            }
                           else
                            {
                                $('#date_from').datepicker('option', 'maxDate', selectedDate );
                            }
            	        
            	            var from   = $('#date_from').val();
            	            var to     = $('#date_to').val();

            	            if (from != '' && to != '')
            	            {
                            var url = "&time_range=range&time_cnt=2&time%5B0%5D%5B0%5D=+&time%5B0%5D%5B1%5D=%3E%3D&time%5B0%5D%5B8%5D=+&time%5B0%5D%5B9%5D=AND&time%5B1%5D%5B1%5D=%3C%3D"
                            var f1 = from.split(/\-/);
                            url = url + '&time%5B0%5D%5B2%5D=' + f1[1]; // month
                            url = url + '&time%5B0%5D%5B3%5D=' + f1[2]; // day
                            url = url + '&time%5B0%5D%5B4%5D=' + f1[0]; // year
                            url = url + '&time%5B0%5D%5B5%5D=00&time%5B0%5D%5B6%5D=00&time%5B0%5D%5B7%5D=00';
                            var f2 = to.split(/\-/);
                            url = url + '&time%5B1%5D%5B2%5D=' + f2[1]; // month
                            url = url + '&time%5B1%5D%5B3%5D=' + f2[2]; // day
                            url = url + '&time%5B1%5D%5B4%5D=' + f2[0]; // year
                            url = url + '&time%5B1%5D%5B5%5D=23&time%5B1%5D%5B6%5D=59&time%5B1%5D%5B7%5D=59';
                               
                            <?php
                            $uri = Util::htmlentities_url(Util::get_sanitize_request_uri($_SERVER['REQUEST_URI']));
                            $actual_url = str_replace("?clear_allcriteria=1&","?",str_replace("&clear_allcriteria=1","",$uri)).(preg_match("/\?.*/",$uri) ? "&" : "?");
                            ?>
                            // Go
                            load_link('<?php echo $actual_url ?>'+url);
            	            }
            	        }
            	    });
            		
            		$('.ndc').disableTextSelect();
            		// timeline
            		if (typeof load_tree == 'function') load_tree();
            		// timeline
            		if (typeof gen_timeline == 'function') gen_timeline();
            		// report
            		if (typeof parent.launch_form == 'function') parent.launch_form();

            		// Some link handlers
            		$('a.trlnk,a.trlnka').each(function() {
            			$(this).click(function() {
            				nogb=true;
            			});
            		});	
            		$('a.trlnks,input.trlnks').each(function() {
            			$(this).click(function() {
            				nogb=true;
            				setTimeout("nogb=false",1000);
            			});
            		});
                $('.greybox').click(function(){
                    var t = this.title || $(this).text() || this.href;
                    GB_show(t,this.href, 550,'85%');
                    return false; 		
                });

                // Clean search box
                $('#frm').submit(function() {
                    if ($('#search_str').attr('class') == "gr")
                    {
                        $('#search_str').val("");
                    }
              	});

                // Risk slider
                /*
                $("#risk_slider").slider({
                    from: 1,
                    to:   5,
                    smooth: false,
                    callback: function( event, ui ) { alert('yeah'); }
                });
                */

                $('#more_filters_button').click(function(){
                    more_filters_toggle();
                });
                $('#adv_search_button').click(function(){
                    GB_show("<?php echo _("Advanced Search") ?>","/forensics/base_qry_form.php", 550, 900);
                    return false;
                });

                <?php
                if ($_POST['gbhide'] == "1")
                {
                ?>
                var params       = new Array();
                params['nostop'] = 1;
                parent.GB_hide(params);
                <?php
                }
                ?>
                
                // Select Section Tab
                load_section(current_section);

                if (current_section == 'grouped')
                {
                    var selected_tab = 1;
                }
                else if (current_section == 'timeline')
                {
                    var selected_tab = 2;
                }
                else
                {
                    var selected_tab = 0;
                }
                /*  Activating the tab plugin   */
                $("#tab_siem").tabs(
                {
                		selected: selected_tab,
                		select:   function(event, ui)
                		{
                		    var action_id = $(ui.tab).data('action_id');
                		     
                		    switch(action_id)
                		    {
                		    case 0:
                		        load_section('events');
                		        break;
                		    case 1:
                		        load_link('base_qry_main.php?submit=Query+DB');
                		        break;
                		    case 2:
                		        load_link('<?php echo ($_SESSION["siem_default_group"] != "") ? $_SESSION["siem_default_group"] : "base_stat_alerts.php?sort_order=occur_d" ?>');
                		        break;
                		    case 3:
                		        load_section('timeline');
                		        break;
                		    case 4:
                		        load_link('base_timeline.php');
                		        break;
                		    }
                		}
            	    });
            	}
    
            	function report_launcher(data,type) {
            		var url = '<?=urlencode((preg_match("/\?/",$_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : $_SERVER["REQUEST_URI"]."?".$_SERVER["QUERY_STRING"])."&complete=1")?>';
            		var dates = '<?=($y1!="") ? "&date_from=".urlencode("$y1-$m11-$d1") : "&date_from="?><?=($y2!="") ? "&date_to=".urlencode("$y2-$m21-$d2") : "&date_to="?>';
            		GB_show("<?=_("Report options")?>",'/forensics/report_launcher.php?url='+url+'&data='+data+'&type='+type+dates,200,'40%');
            		return false;
            	}
            
            // bgtask check
            <?php if ($_SESSION["deletetask"] != "") echo "bgtask();\n"; else echo "// Not running"; ?>
            
            $('document').ready(function()
            {                                
                $('#search_type_combo').on('change', show_search_tooltip);   
                $('#search_type_combo').trigger('change');             
                
                $('.pholder').placeholder();
            });
            
            </script>
            
        </head>
        <body>
    <?php
    // Include search form, current criteria box, and stats box
    if (!array_key_exists("minimal_view", $_GET) && !array_key_exists("noheader", $_GET))
    {
        include("base_header.php");
    }
}
function PrintBASESubFooter() {
    GLOBAL $BASE_VERSION, $BASE_path, $BASE_urlpath, $Use_Auth_System;
    echo "\n\n<!-- BASE Footer -->\n" . "<P>\n";
    //include("$BASE_path/base_footer.php");
    echo "\n\n";
}
function PrintFramedBoxHeader($title, $fore, $back) {
    echo '
<TABLE class="transparent" WIDTH="100%" CELLSPACING=0 CELLPADDING=0 BORDER=0>
<TR><TD>
  <TABLE class="transparent" WIDTH="100%" CELLSPACING=0 CELLPADDING=0 BORDER=0>
  <TR><TD align="center" class="headerpr" style="font-size:14px">&nbsp;' . $title . '&nbsp;</TD></TR>
    <TR><TD>';
}
function PrintFramedBoxFooter() {
    echo '
  </TD></TR></TABLE>
</TD></TR></TABLE>';
}
function PrintCustomViews() {
	?>
	<table cellpadding=0 cellspacing=0 class="headermenu" style="background-color:white;border:0px solid white" width="100%">
		<tr><td align="center" style="padding:5px;border:1px solid #CCCCCC"><table cellpadding=0 cellspacing=0>
		<tr>
			<td width="30" id="customview_msg"></td>
			<td style="color: black; font-size: 12px; font-weight: bold" nowrap><?=_("Select View")?>:&nbsp;</td>
			<td>
				<select name="customview" onchange="change_view(this.value)">
					<? foreach ($_SESSION['views'] as $name=>$attr) { ?>
					<option value="<?=$name?>" <? if ($_SESSION['current_cview'] == $name) echo "selected"?>><?=$name?>
					<? } ?>
				</select>
			</td>
			<td style="padding:2px"><input type="button" value="<?=_("Modify")?>" onclick="GB_show('<?=_("Edit custom view")?>','/forensics/custom_view_edit.php?edit=1',480,700);"/></td>
			<td style="padding:2px"><input type="button" value="<?=_("Save Current")?>" onclick="save_view()"/></td>
			<td style="padding:2px">|</td>
			<td style="padding:2px"><input type="button" value="<?=_("Create New View")?>" onclick="GB_show('<?=_("Create new custom view")?>','/forensics/custom_view_edit.php',480,700);"/></td>
			<td width="30"></td>
		</tr>
		</table></td></tr>
	</table>
	<?
}

function PrintReportView() {
	?>
	<a style='cursor:pointer; font-weight:bold;' class='ndc' onclick="$('#report').toggle()"><img src="../pixmaps/menu/reports.png" align="absmiddle" border="0"/> <?php echo _("Report this view")?></a>
   	<div style="position:relative">
		<div id="report" style="position:absolute;right:0;top:0;display:none">
			<table cellpadding='0' cellspacing='0' align="center" >
				<tr>
					<th style="padding-right:3px">
						<div style='float:left; width:75%; text-align: right; padding-top: 7px'><?php echo _("Insert report name")?></div>
						<div style='float:right; width:18%; text-align: right;'><a style="cursor:pointer; text-align: right;" onclick="$('#report').toggle()"><img src="../pixmaps/cross-circle-frame.png" alt="<?php echo _("Close"); ?>" title="<?php echo _("Close"); ?>" border="0" align='absmiddle'/></a></div>
					</th>
				</tr>
				<tr>
					<td style='text-align: center; padding: 7px; font-size: 10px;' class="noborder">
						<input type="text" value="" id="savereport_custom_name">&nbsp;
						<input type="button" value="<?=_("Create New Report")?>" onclick="GB_show('<?=_("Create new report")?>','/forensics/custom_view_edit.php?edit=1&forcesave=1&savereport_custom_name='+document.getElementById('savereport_custom_name').value,460,700);"/>
					</td>
				</tr>
			</table>
		</div>
	</div>
<?php
}

function PrintPredefinedViews()
{
    GLOBAL $opensource;
   
    $current_str = ($_SESSION['current_cview'] != "default" && $_SESSION['current_cview'] != "") ? " [<i>".Util::htmlentities($_SESSION['current_cview'])."</i>]" : "";
	
	// Get default view
	require_once("ossim_conf.inc");
	$conf = $GLOBALS["CONF"];
	$idm_enabled    = ($conf->get_conf("enable_idm") == 1 && Session::is_pro()) ? true : false;
	$login        = Session::get_session_user();
	$db_aux       = new ossim_db();
	$conn_aux     = $db_aux->connect();
	$config       = new User_config($conn_aux);
	$default_view = ($config->get($login, 'custom_view_default', 'php', "siem") != "") ? $config->get($login, 'custom_view_default', 'php', "siem") : (($idm_enabled) ? 'IDM' : 'default');
	$db_aux->close($conn_aux);	
?>
   <a style='cursor:pointer' class='ndc riskinfo' txt="<?php echo _("Predefined Views").$current_str ?>" onclick="$('#views').css({top: -1*$('#views').outerHeight(true)}).toggle()"><img src="../pixmaps/forensic_views.png" border="0"/></a>
   <br/>
      
   <div style='position: absolute; height: 1px; width: 1px;'>
       <div id="views" style="position:absolute; right:-5px; top:0px; display:none;">
    		<table cellpadding='0' cellspacing='0' align="center" >
    			<tr>
    				<th style="padding-right:3px">
    					<table class="transparent" style="width:100%;background:none;border:none;height:30px !important">
    						<tr>
    							<td width="10"></td>
    							<td><?php echo _("Select View")?></td>
    							<td width="10"><a style="cursor:pointer; text-align: right;" onclick="$('#views').toggle()"><img src="../pixmaps/cross-circle-frame.png" alt="<?php echo _("Close"); ?>" title="<?php echo _("Close"); ?>" border="0" align='absmiddle'/></a></td>
    						</tr>
    					</table>
    				</th>
    			</tr>
    			<tr class="noborder">
    				<td id="viewsbox" colspan='2'>
        				<table class='container' cellpadding='0' cellspacing='0' style='border: none;'>
        				<? $i=0;
        				foreach ($_SESSION['views'] as $name=>$attr) {
        				$i++;    
        				//$color = ($i%2==0) ? "impar" : "par"; 
        				?>
        					<tr class='noborder'>
        						<?php 
                                if ($_SESSION['current_cview'] == $name)
                                {
                                    $style = 'font-weight: bold;';
                                    $opacidad = '';
                                    $boton0 = (!$opensource && Session::am_i_admin()) ? "<a style='cursor:pointer;' onclick=\"GB_show('"._('Edit custom view')."','/forensics/custom_view_edit.php?edit=1&forcesave=1',480,700);\"><img src='../pixmaps/documents-save.png' alt='"._('Save as report module')."' title='"._('Save as report module')."' border='0'/></a>&nbsp;" : "";
                                    $boton1 = "<a style='cursor:pointer;' onclick=\"save_view('save_".$i."');\"><img id='save_".$i."' src='../pixmaps/disk-gray.png' alt='"._('Update View')."' title='"._('Update View')."' border='0'/></a>&nbsp;";
                                    $boton2 = "<a style='cursor:pointer;' onclick=\"GB_show('"._('Edit custom view')."','/forensics/custom_view_edit.php?edit=1',480,700);\"><img src='../vulnmeter/images/pencil.png' alt='"._('Modify')."' title='"._('Modify')."' border='0'/></a>";
                                }
                                else
                                {
                                    $style='';
                                    $opacidad = 'opacity:0.4;filter:alpha(opacity=40);';
                                    $boton0 = "";
                                    $boton1 = "<img id='save_".$i."' src='../pixmaps/disk-gray.png' alt='"._('Update View')."' title='"._('Update View')."' border='0'/>&nbsp;";
                                    $boton2 = "<img src='../vulnmeter/images/pencil.png' alt='"._('Modify') ."' title='"._('Modify')."' border='0'/>";
        						}
        						
        						$dname = ($name=="default") ? "Default" : $name;
        						?>
        						<td class="noborder" style='height:28px'><a href="" onclick="set_default_view('<?php echo Util::htmlentities($name) ?>');return false" title="<?php echo _("Save as default") ?>" alt="<?php echo _("Save as default") ?>"><img class="view_star" id="view_star_<?php echo Util::htmlentities($name) ?>" src="../pixmaps/star-small<?php if ($name != $default_view) { ?>-empty<?php } ?>.png" onmouseover="this.src = '../pixmaps/star-small.png'" onmouseout="this.src = ('<?php echo Util::htmlentities($name) ?>' == default_view) ? '../pixmaps/star-small.png' : '../pixmaps/star-small-empty.png'" width="16" /></a></td>
        						<td class="noborder" style="height:28px; white-space: nowrap; min-width: 90px; padding: 0px 20px 0px 5px; text-align: left;"><a style="cursor:pointer;<?=$style?>" onclick="change_view('<?php echo Util::htmlentities($name)?>');" id="view_<?php echo Util::htmlentities($name)?>"><span><?php echo Util::htmlentities($dname)?></span></a></td>
        						<td class="noborder" style="<?=$opacidad?> padding-right:5px;text-align:right;height:28px"><?=$boton0.$boton1.$boton2;?></td>
        						<td class="noborder" style="height:28px;<?php if ($name == "default") echo $opacidad ?>"><?php if ($name != "default") { ?><a style="cursor:pointer" onclick="if(confirm('<?php echo  Util::js_entities(_("Are you sure?"))?>')) delete_view('<?php echo Util::htmlentities($name)?>')"><img src="../pixmaps/delete.gif" border="0" alt="<?php echo _("Delete") ?>" title="<?php echo _("Delete") ?>"></img></a><?php } ?></td>
        					</tr>
        					<? } ?>
        				</table>
    				</td>
    			</tr>
    			<tr>
    				<td style='text-align: center; padding: 4px; font-size: 10px;' class="noborder">
    				  <input type="button" value="<?=_("Create New View")?>" onclick="GB_show('<?=_("Create new custom view")?>','/forensics/custom_view_edit.php',480,700);"/>
    				</td>
    			</tr>
    		</table>		
        </div>
    </div>    
	<?php
}





function PrintFreshPage($refresh_stat_page, $stat_page_refresh_time) {
    if ($refresh_stat_page)
    //echo '<META HTTP-EQUIV="REFRESH" CONTENT="'.$stat_page_refresh_time.'; URL='. htmlspecialchars(CleanVariable($_SERVER["REQUEST_URI"], VAR_FSLASH | VAR_PERIOD | VAR_DIGIT | VAR_PUNC | VAR_LETTER), ENT_QUOTES).'">'."\n";
    echo '<META HTTP-EQUIV="REFRESH" CONTENT="' . Util::htmlentities($stat_page_refresh_time) . '">';
}
function chk_select($stored_value, $current_value) {
    if (strnatcmp($stored_value, $current_value) == 0) return " SELECTED";
    else return " ";
}
function chk_check($stored_value, $current_value) {
    if ($stored_value == $current_value) return " CHECKED";
    else return " ";
}
function dispYearOptions($stored_value) {
    // Creates the years for drop down boxes
    $thisyear = date("Y");
    $options = "";
    $options = "<OPTION VALUE=' ' " . chk_select($stored_value, " ") . ">" . gettext("{ year }") . "\n";
    for ($i = 1999; $i <= $thisyear; $i++) {
        $options = $options . "<OPTION VALUE='" . $i . "' " . chk_select($stored_value, $i) . ">" . $i . "\n";
    }
    $options = $options . "</SELECT>";
    return ($options);
}
function PrintBASEAdminMenuHeader() {
    $menu = "<table width='100%' border=0><tr><td width='15%'>";
    $menu = $menu . "<div class='mainheadermenu'>";
    $menu = $menu . "<table border='0' class='mainheadermenu'>";
    $menu = $menu . "<tr><td class='menuitem'>" . gettext("User Management") . "<br>";
    $menu = $menu . "<hr><a href='base_useradmin.php?action=list' class='menuitem'>" . gettext("List users") . "</a><br>";
    $menu = $menu . "<a href='base_useradmin.php?action=create' class='menuitem'>" . gettext("Create a user") . "</a><br>";
    $menu = $menu . "<br>" . gettext("Role Management") . "<br><hr>";
    $menu = $menu . "<a href='base_roleadmin.php?action=list' class='menuitem'>" . gettext("List Roles") . "</a><br>";
    $menu = $menu . "<a href='base_roleadmin.php?action=create' class='menuitem'>" . gettext("Create a Role") . "</a><br>";
    $menu = $menu . "</td></tr></table></div></td><td>";
    echo ($menu);
}
function PrintBASEAdminMenuFooter() {
    $footer = "</td></tr></table>";
    echo ($footer);
}
function PrintBASEHelpLink($target) {
    /*
    This function will accept a target variable which will point to
    an anchor in the base_help.php file.  It will output a help icon
    that will link to that target in a new window.
    */
}
?>
