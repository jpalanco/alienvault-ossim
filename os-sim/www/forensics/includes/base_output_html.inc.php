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
    GLOBAL $db, $timetz, $debug_mode, $BASE_VERSION, $BASE_path, $BASE_urlpath, $html_no_cache, $max_script_runtime, $Use_Auth_System, $stat_page_refresh_time, $refresh_stat_page, $ossim_servers, $sensors, $hosts, $database_servers, $DBlib_path, $DBtype, $db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password, $entities;
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
            <link rel="stylesheet" type="text/css" href="/ossim/style/jquery.switch.css"/>
            <link rel="stylesheet" type="text/css" href="/ossim/style/datepicker.css"/>
            <link rel="stylesheet" type="text/css" href="/ossim/style/jquery.dropdown.css"/>

            <!-- Manual Styles -->
            <style type="text/css">
                #tiptip_content {
                    border-radius: 5px;
                }
                #adv_search_button
                {
                    margin:5px 0px 0px 0px;
                    width:239px;
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
            <script type="text/javascript" src="../js/utils.js"></script>
            <script type="text/javascript" src="../js/jquery.tipTip-ajax.js"></script>
            <script type="text/javascript" src="../js/notification.js"></script>

            <!-- jSlider -->
            <script type="text/javascript" src="../js/jslider/jshashtable-2.1_src.js"></script>
            <script type="text/javascript" src="../js/jslider/jquery.numberformatter-1.2.3.js"></script>
            <script type="text/javascript" src="../js/jslider/tmpl.js"></script>
            <script type="text/javascript" src="../js/jslider/jquery.dependClass-0.1.js"></script>
            <script type="text/javascript" src="../js/jslider/draggable-0.1.js"></script>
            <script type="text/javascript" src="../js/jslider/jquery.slider.js"></script>
            <script type="text/javascript" src="../js/jquery.tag-it.js"></script>
            <script type="text/javascript" src="../js/jquery.placeholder.js"></script>
            <script type="text/javascript" src="../js/jquery.switch.js"></script>


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
                    success: function(msg)
                    {
                        var redirection = false;
                        if (msg.match(/No pending tasks/))
                        {
                            // check if there was a pending task
                            if($("#task").is(":visible"))
                            {
                                var redirection = true;
                            }

                            if ($("#task").is(":visible")) $("#task").toggle();
                            __timeout = setTimeout("bgtask()",5000);

                            if(redirection)
                            {
                                <?php
                                // Refresh to Grouped by
                                if (preg_match('/base_stat_[^\.]+.php/', $_SERVER['SCRIPT_NAME']))
                                {
                                    $_current_url = ($_SESSION["siem_default_group"] != "") ?
                                                     $_SESSION["siem_default_group"] :
                                                     $_SERVER['SCRIPT_NAME']."?sort_order=occur_d";
                                }
                                // Refresh to Main
                                else
                                {
                                    $_current_url = 'base_qry_main.php?num_result_rows=-1&submit=Query+DB&current_view=-1';
                                }
                                ?>
                                load_link('./<?php echo $_current_url ?>');
                            }
                        }
                        else
                        {
                            if ($("#task").is(":hidden")) $("#task").toggle();
                            $("#task").html("<img style='border: none' src='./images/sandglass.png'> Deleting in background...");
                            __timeout = setTimeout("bgtask()",5000);
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
                if (typeof(parent.show_overlay_spinner)=='function') parent.show_overlay_spinner(true);
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
            function GB_onclose(url)
            {
                if (url.match(/otx|kdb|insertsid|shellcode/))
                {
                    nogb=false;
                    return false;
                }
                if (typeof(parent.show_overlay_spinner)=='function') parent.show_overlay_spinner(true);
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

                if (typeof(params) == 'object' && typeof params['url_detail'] != 'undefined')
                {
                    if (typeof(parent.show_overlay_spinner)=='function') parent.show_overlay_spinner(true);

                    document.location.href = params['url_detail'];

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

            function CheckSensor()
            {
                if ($('#sensor option:selected').val()!='')
                {
                    if ($('#exclude').is(':checked'))
                    {
                        if ($('#sensor option:selected').text().match(/Context/))
                        {
                            $('#exclude').prop('checked',false);
                        }
                        else
                        {
                            $('#sensor option:selected').val('!' + $('#sensor option:selected').val());
                        }
                    }
                }
            }

            function SetSensor(btn,clk)
            {
                $('#ctx').val('');
                if (clk) // change combo box
                {
                    if ($('#sensor option:selected').text().match(/Context/))
                    {
                        $('#exclude').prop('checked',false).prop('disabled',true);
                        $("#lexc").css('color','lightgray');
                    }
                    else
                    {
                        $('#exclude').prop('disabled',false);
                        $("#lexc").css('color','rgb(85,85,85)');
                    }
                    btn.click();
                }
                else // click exclude checkbox
                {
                    if ($('#sensor option:selected').val()!='')
                    {
                        btn.click();
                    }
                }
                DisableContexts();
            }

            function DisableContexts()
            {
                if ($('#exclude').is(':checked'))
                {
                    $('.ents').prop('disabled',true);
                }
                else
                {
                    $('.ents').prop('disabled',false);
                }

                if ($('#sensor option:selected').text().match(/Context/))
                {
                    $('#exclude').prop('checked',false).prop('disabled',true);
                    $("#lexc").css('color','lightgray');
                }
            }

            // Top refresh link
            function re_load()
            {
                if (typeof(parent.show_overlay_spinner)=='function') parent.show_overlay_spinner(true);
                if (typeof(pag_reload)=='function')
                {
                    pag_reload();
                }
                else
                {
                   var href = document.location.href.replace("&nocache=1","");
                   document.location.href = href + "&nocache=1";
                   document.location.reload(false);
                }
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
                if (val.match("^idmusername"))
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
                if ( val != "" &&
                !(val.match("^ip") && $('#groupby_ip').find(":selected").val() == "ipempty")
                && !(val.match("^hostname") && $('#groupby_hostname').find(":selected").val() == "hostnameempty")
                && !(val.match("^idmusername") && $('#groupby_username').find(":selected").val() == "usernameempty")
                && !(val.match("^port") && $('#group_port_select').find(":selected").val() == "portempty" && $('#group_proto_select').find(":selected").val() == "portprotoempty"))
                {
                    $('#group_button').removeAttr("disabled");
                }
                else{
                    $('#group_button').attr("disabled", "disabled");
                }
            }

            // Group by go
            function go_stats()
            {
				var val1 = $('#groupby_1').val();
				switch (val1) {
					case "ip":
						var val2 = $('#groupby_ip').val();
						switch (val2) {
							case "iplink": load_link("base_stat_iplink.php?sort_order=events_d&fqdn=no"); break;
							case "iplink_fqdn": load_link("base_stat_iplink.php?sort_order=events_d&fqdn=yes"); break;
							case "ipsrc": load_link("base_stat_uaddr.php?addr_type=1&sort_order=occur_d"); break;
							case "ipdst": load_link("base_stat_uaddr.php?addr_type=2&sort_order=occur_d"); break;
							case "ipboth": load_link("base_stat_uaddress.php?sort_order=occur_d"); break;
						}
						break;
					case "hostname":
						var val2 = $('#groupby_hostname').val();
						switch (val2) {
							case "hostnamesrc": load_link("base_stat_uidmsel.php?addr_type=src_hostname&sort_order=occur_d"); break;
							case "hostnamedst": load_link("base_stat_uidmsel.php?addr_type=dst_hostname&sort_order=occur_d"); break;
							default : load_link("base_stat_uidm.php?addr_type=hostname&sort_order=occur_d"); break;
						}
						break;
					case "idmusername":
						var val2 = $('#groupby_username').val();
						switch (val2) {
							case "usernamesrc": load_link("base_stat_uidmsel.php?addr_type=src_userdomain&sort_order=occur_d"); break;
							case "usernamedst": load_link("base_stat_uidmsel.php?addr_type=dst_userdomain&sort_order=occur_d"); break;
							default : load_link("base_stat_uidm.php?addr_type=userdomain&sort_order=occur_d"); break;
						}
						break;
					case "signature": load_link("base_stat_alerts.php?sort_order=occur_d"); break;
					case "port":
						var port = $('#groupby_port').val() == "portsrc" ? 1 : 2;
						var val2 = $('#groupby_proto').val();
						switch (val2) {
							case "portprototcp": load_link("base_stat_ports.php?sort_order=occur_d&port_type="+port+"&proto=6"); break;
							case "portprotoudp": load_link("base_stat_ports.php?sort_order=occur_d&port_type="+port+"&proto=17"); break;
							case "portprotoany" : load_link("base_stat_ports.php?sort_order=occur_d&port_type="+port+"&proto=-1"); break;
						}
						break;
					case "sensor": load_link("base_stat_sensor.php?sort_order=occur_d"); break;
					case "otx": load_link("base_stat_otx.php?sort_order=occur_d"); break;
					case "ptypes": load_link("base_stat_ptypes.php?sort_order=occur_d"); break;
					case "plugins": load_link("base_stat_plugins.php?sort_order=occur_d"); break;
					case "country": load_link("base_stat_country.php?sort_order=occur_d"); break;
					case "categories": load_link("base_stat_categories.php?sort_order=occur_d"); break;
					default: load_link("base_stat_extra.php?sort_order=occur_d&addr_type="+val1); break;
				}
            }

            // Postload action (call from host_report_menu.php)
            function postload() {
                   if (typeof(DisableContexts)=='function')
                   {
                       DisableContexts();
                   }
                   if (typeof(parent.hide_overlay_spinner)=='function' && parent.is_loading_box())
                   {
                       parent.hide_overlay_spinner();
                   }
                   // Show spinner on form submit
                   $('#bsf, a.qlink').on('click',function(){
                        if (typeof(parent.show_overlay_spinner)=='function') parent.show_overlay_spinner(true);
                   });
                   $('#go_button').on('click',function()
                   {
                        if (typeof(parent.show_overlay_spinner)=='function') parent.show_overlay_spinner(true);
                        var sstr   = $("#search_str").val();
                        var scombo = $("#search_type_combo").val();
                        if (sstr.match(/\!?\d+\.\d+\.\d+\.\d+/) && scombo == 'Signature')
                        {
                             $("#search_type_combo").val('Src or Dst IP');
                        }
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

                // Top Graph Trend SWITCH
                    $('#trend_checkbox').toggles({
                        "text" : {
                            "on"  : '<?php echo _('On')?>',
                            "off" : '<?php echo _('Off')?>'
                        },
                        "on" : false,
                        "width" : 50,
                        "height" : 18,
                    });

                    $('#trend_checkbox').on('toggle', function (e, status) {

                        if (status == true)
                        {
                            // Display trend
                            $('#iplot').toggle();
                            $('#loadingTrend').show();
                            SetIFrameSource('processframe','base_plot.php')
                        }
                        else
                        {
                            // Hide trend
                        $('#iplot').toggle();
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
                        maxWidth: "265px",
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

                    $(".p_tooltip").tipTip({
                        defaultPosition: 'top',
                        maxWidth: "450px",
                        content: function (e) {
                            try {
                                var json_log = JSON.parse($(this).attr('txt'));
                                return "<pre class='payload_tooltip json_tooltip'>" + JSON.stringify(json_log, undefined, 2) + "</pre>";
                            } catch(e){
                                return "<div class='payload_tooltip'>" + $(this).attr('txt') + "</div>"
                            }
                        }
                    });

                    $(".c_tooltip").tipTip({
                          defaultPosition: 'top',
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

                    $('.selectu').on('change',function(){
                        $('#extradatafield').attr('placeholder',$(this).val().ucwords()+' field');
                    });
                    if (typeof $('.selectu').val() != 'undefined')
                    {
                        $('#extradatafield').attr('placeholder',$('.selectu').val().ucwords()+' field');
                    }

                    $('#views_link').on('click',function(event)
                    {
                        event.stopPropagation();
                        $('#actions_dd').hide();
                        var diff = ($.browser.webkit && !(/chrome/.test(navigator.userAgent.toLowerCase()))) ? -3 : 0;
                        var vl = $('#views_link').offset();
                        var tt = vl.top + $('#views_link').outerHeight(true) + diff;
                        var ll = vl.left - $('#custom_views').outerWidth(true) + $('#views_link').outerWidth(false);
                        $('#custom_views').css({position: 'absolute', left: Math.floor(ll), top: Math.floor(tt)}).toggle();
                        return false;
                    });

                    $('#views_close').on('click',function()
                    {
                        $('#views').hide();
                    });

                    $('#actions_link').on('click',function(event)
                    {
                        event.stopPropagation();
                        $('#custom_views').hide();
                        var diff = ($.browser.webkit && !(/chrome/.test(navigator.userAgent.toLowerCase()))) ? -3 : 0;
                        var vl = $('#actions_link').offset();
                        var tt = vl.top + $('#actions_link').outerHeight(true) + diff;
                        var ll = vl.left - $('#actions_dd').outerWidth(true) + $('#actions_link').outerWidth(true) + diff;
                        $('#actions_dd').css({position: 'absolute', left: Math.floor(ll), top: Math.floor(tt)}).toggle();
                        return false;
                    });

                // AUTOCOMPLETES
                <?php
                $db_aux = new ossim_db(true);
                $conn_aux = $db_aux->connect();

                // Purge or Restore backup action is running
                list($backup_status, $backup_mode, $backup_progress) = Backup::is_running($conn_aux);
                if ($backup_status > 0)
                {
                ?>
                show_backup_status();
                <?php
                }
                $ctx = ($_GET["ctx"] != "") ? $_GET["ctx"] : $_SESSION["ctx"];
                $ents = '';
                if (Session::is_pro())
                {
                    $my_entities = (Session::am_i_admin()) ? $entities : Acl::get_entities_to_assign($conn_aux);
                    foreach ($my_entities as $e_id => $e_name)
                    {
                        if(Session::get_entity_type($conn_aux, $e_id) != 'context') continue;
                        $ents .= '<option class="ents" value="'.$e_id.'"'.(($ctx==$e_id) ? ' selected' : '').'>'. _('Context') .': '.Util::htmlentities($e_name).'</option>';
                    }
                }
                $db_aux->close($conn_aux);

                ?>

                $("#otx_pulse").autocomplete('/ossim/otx/providers/otx_pulse_autocomplete.php?type=event', {
                    minChars: 0,
                    width: 197,
                    max: 50,
                    matchContains: "word",
                    autoFill: false,
                    scroll: true,
                    formatItem: function(row, i, max, value)
                    {
                        return (value.split('###'))[1];
                    },
                    formatResult: function(data, value)
                    {
                        return (value.split('###'))[1];
                    }
                }).result(function(event, item)
                {
                    if (typeof(item) != 'undefined' && item != null)
                    {
                        var _aux_item = item[0].split('###');
                        var pulse_id  = _aux_item[0];
                        $('#otx_activity').prop('checked', false);
                        $("#otx_pulse_value").val(pulse_id);
                        $("#bsf").click();
                    }
                });

                <?php
                // AUTOCOMPLETE DEVICES
                if (Session::is_pro())
                {
                    ?>
                    $("#device_input").autocomplete('base_devices.php', {
                        minChars: 0,
                        width: 197,
                        max: 50,
                        matchContains: "word",
                        autoFill: true,
                        scroll: true,
                        formatItem: function(row, i, max, value) {
                            return value;
                        },
                        formatResult: function(data, value)
                        {
                            return value;
                        }
                    }).result(function(event, item) {
                        if (typeof(item) != 'undefined' && item != null)
                        {
                            $("#device_input").val(item[0]);
                            $("#bsf").click();
                        }

                    });
                <?php
                }
                ?>

                var dayswithevents = [ ];

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
                        var withevents = '';
                        // With-Events color
                        //var withevents = (dayswithevents.in_array(date.getTime())) ? ' evented-date' : ''

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

                // trcellclk single and double click handle
                var timeOut = 250;
                var timeoutID = 0;
                var ignoreSingleClicks = false;
                var clink = null;
                $('.trcellclk').on('click',function(){
                    if (!ignoreSingleClicks)
                    {
                        clink = $(this).data('link')+'&minimal_view=1&noback=1&pag=<?php echo intval($_POST['submit']) ?>';
                        clearTimeout(timeoutID);
                        timeoutID = setTimeout(
                            function(){
                                if (!nogb)
                                {
                                    GB_show_nohide("<?php echo _("Event details") ?>",clink,'65%','85%');
                                }
                            }, timeOut);
                    }
                }).on('dblclick',function(){
                    clearTimeout(timeoutID);
                    ignoreSingleClicks = true;

                    setTimeout(function() {
                      ignoreSingleClicks = false;
                    }, timeOut);

                    load_link('<?php echo AV_MAIN_PATH ?>'+$(this).data('link')+'&noheader=true');
                }).disableTextSelect();

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

            // Check backup status with interval while is running
            function show_backup_status()
            {
                var form_data = 'action=status';

                $.ajax({
                    type: 'GET',
                    url: '<?php echo AV_MAIN_PATH ?>/backup/ajax/backup_actions.php',
                    dataType: 'json',
                    data: form_data,
                    success: function(data)
                    {
                        if (typeof(data) != 'undefined' && typeof(data.message) != 'undefined' && data.message != '')
                        {
                            var url         = "<?php echo Menu::get_menu_url(AV_MAIN_PATH.'/backup/index.php', 'configuration', 'administration', 'backups', 'backups_events'); ?>";
                            var backup_link = '<a href="' + url + '">' + data.message + '</a>';
                            var msg         = 'A background task could be affecting to the performance<br/>' + backup_link;

                            show_notification(msg, 'backup_info', 'nf_warning', 'padding: 2px; width: 100%; margin: auto; text-align: left');
                            setTimeout('show_backup_status()', 10000);
                        }
                        else
                        {
                            $('#backup_info').html('');
                        }
                    }
                });
            }
            function show_notification (msg, container, nf_type, style)
            {
                var nt_error_msg = (msg == '')   ? '<?php echo _('Sorry, operation was not completed due to an error when processing the request')?>' : msg;
                var style        = (style == '' ) ? 'width: 80%; text-align:center; padding: 5px 5px 5px 22px; margin: 20px auto;' : style;

                var config_nt = { content: nt_error_msg,
                        options: {
                            type: nf_type,
                        },
                        style: style
                    };

                var nt_id         = 'nt_ns';
                var nt            = new Notification(nt_id, config_nt);
                var notification  = nt.show();

                $('#'+container).html(notification);
            }

                function report_launcher(data,type) {
                    var url = '<?=urlencode((preg_match("/\?/",$_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : $_SERVER["REQUEST_URI"]."?".$_SERVER["QUERY_STRING"])."&export=1")?>';

                    <?
                        #the datetime range will be save in $_SESSION["time"] so we can use it to load the data
                        $y1 = $_SESSION["time"][0][4];
                        $m1 = $_SESSION["time"][0][2];
                        $d1 = $_SESSION["time"][0][3];

                        $y2 = $_SESSION["time"][1][4];
                        $m2 = $_SESSION["time"][1][2];
                        $d2 = $_SESSION["time"][1][3];
                    ?>
                    var dates = '<?=($y1!="") ? "&date_from=".urlencode("$y1-$m1-$d1") : "&date_from="?><?=($y2!="") ? "&date_to=".urlencode("$y2-$m2-$d2") : "&date_to="?>';
                    GB_show("<?=_("Report options")?>",'/forensics/report_launcher.php?url='+url+'&data='+data+'&type='+type+dates,200,'40%');
                    return false;
                }

            // bgtask check
            <?php if ($_SESSION["deletetask"] != "") echo "if (typeof __timeout == 'undefined' || !__timeout) bgtask();\n"; else echo "// Not running"; ?>

            $(document).ready(function()
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

function PrintBASESubFooter()
{
    GLOBAL $BASE_VERSION, $BASE_path, $BASE_urlpath, $Use_Auth_System;
    echo "\n\n<!-- BASE Footer -->\n";
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

    $current_str = ($_SESSION['current_cview'] != "default" && $_SESSION['current_cview'] != "") ? Util::htmlentities($_SESSION['current_cview']) : _("Default");

    // Get default view
    require_once("ossim_conf.inc");
    $conf = $GLOBALS["CONF"];
    $idm_enabled    = ($conf->get_conf("enable_idm") == 1 && Session::is_pro()) ? true : false;
    $login        = Session::get_session_user();
    $db_aux       = new ossim_db(true);
    $conn_aux     = $db_aux->connect();
    $config       = new User_config($conn_aux);
    $default_view = ($config->get($login, 'custom_view_default', 'php', "siem") != "") ? $config->get($login, 'custom_view_default', 'php', "siem") : (($idm_enabled) ? 'IDM' : 'default');
    $db_aux->close($conn_aux);
?>
    <button id="views_link" class="button av_b_secondary">
        <?php echo _('Change View') ?> &nbsp;&#x25be;
    </button>

    <div id="custom_views" class="dropdown dropdown-secondary dropdown-close dropdown-tip dropdown-anchor-right dropdown-scrolling" style='display:none'>
        <ul id="custom_views_ul" class="dropdown-menu">
            <li><a href="#" onclick="GB_show('<?php echo _("Edit Current View") ?>','/forensics/custom_view_edit.php?edit=1',480,700);$('#custom_views').hide();return false"><?php echo _("Edit Current View") ?>&nbsp;</a></li>
            <li><a href="#" onclick="GB_show('<?php echo _("Create new custom view")?>','/forensics/custom_view_edit.php',480,700);$('#custom_views').hide();return false"><?php echo _("Create New View")?>&nbsp;</a></li>
            <?php
                foreach ($_SESSION['views'] as $name=>$attr)
                {
                    if(empty($_SESSION['views'][$name])) {
                        continue;
                    }
                    $dname     = ($name=="default") ? "Default" : $name;
                    $selected  = ($_SESSION['current_cview'] == $name) ? "&#x25BA;&nbsp;" : "";
            ?>
                <li><a href="#" onclick="change_view('<?php echo Util::htmlentities($name)?>');$('#custom_views').hide()"><?php echo $selected.Util::htmlentities($dname)?>&nbsp;</a></li>
            <?php
                }
            ?>
        </ul>
    </div>

    <?php
}

function PrintFreshPage($refresh_stat_page, $stat_page_refresh_time) {
    if ($refresh_stat_page) {
        $extra = strpos("$_SERVER[REQUEST_URI]", "?") === FALSE ? "?" . http_build_query($_REQUEST) : "";
        echo '<META HTTP-EQUIV="REFRESH" CONTENT="' . Util::htmlentities($stat_page_refresh_time) . '; url=https://' . $_SERVER[HTTP_HOST] . $_SERVER[REQUEST_URI] . $extra . '">';
    }
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
