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
require_once 'config.php';

Session::logcheck("environment-menu", "EventsVulnerabilities");

$getParams = array('sortby', 'sortdir', 'viewall', 'enabled', 'job_id', 'rs_page', 'page');

switch ($_SERVER['REQUEST_METHOD']) {
    case "GET" :
        foreach($getParams as $gp)
        {
            if (isset($_GET[$gp])) {
                $$gp=Util::htmlentities(escape_sql(trim($_GET[$gp]), $dbconn));
            } else {
                $$gp="";
            }
        }

        $range_start = "";
        $range_end = "";

        break;
}

$rs_page = intval($rs_page);
$page    = intval($page);

# Handle $disp var separate due to an invalid return value with htmlentities
$disp = GET('disp');
ossim_valid($disp, 'resumeTask', 'stopTask', 'deleteTask', 'setStatus', 'deleteSchedule', OSS_NULLABLE, 'Illegal:'._('Action'));
if (ossim_error())
{
    die(_('Action not allowed'));
}


// GVM commands
$commands = array('stopTask', 'deleteTask', 'resumeTask');

// Get server info to manage tasks
if (in_array($disp, $commands)) {
    $uuid = Util::get_system_uuid();
    $rs = $dbconn->Execute("SELECT notify FROM vuln_jobs WHERE id = ?", array($job_id));
    
    $sensor_ip = '';
    if (!empty($rs)){
        $sensor_id = $rs->fields['notify'];
        $sensor = Av_sensor::get_object($dbconn, $sensor_id);
        $sensor_ip = $sensor->get_ip();
    
        $gvm = new Gvm($sensor_ip);
    
        switch($disp) {
            case "stopTask":
                $gvm->stop_task($job_id);
                break;
        
            case "deleteTask":
                $gvm->delete_task($job_id);
                break;
        
            case "resumeTask":
                $gvm->resume_task($job_id);
                break;
        }
    
        if (!empty($gvm->get_error_msg())){
            $data['status'] = 'error';
            $data['data'] = $gvm->get_error_msg();
        } else {
            $data['status'] = 'success';
            $data['data'] = '';
        }
    }
    else {
        $data['status'] = 'error';
        $data['data'] = _("Sensor not found");
    }

    echo json_encode($data);
    exit();
}

?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html>
    <head>
        <title> <?php echo gettext("Vulnmeter"); ?> </title>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
        <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
        <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
        <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
        <link rel="stylesheet" type="text/css" href="../style/tipTip.css"/>
        <link rel="stylesheet" type="text/css" href="../style/jquery.dataTables.css"/>
        <link rel="stylesheet" type="text/css" href="../style/jquery-ui.css"/>
        <link rel="stylesheet" type="text/css" href="../style/vulnmeter/scan_jobs.css"/>

        <script type="text/javascript" src="../js/jquery.min.js"></script>
        <script type="text/javascript" src="../js/jquery.tipTip.js"></script>
        <script type="text/javascript" src="../js/greybox.js"></script>
        <script type="text/javascript" src="../js/vulnmeter.js.php"></script>
        <script type="text/javascript" src="../js/notification.js"></script>
        <script type="text/javascript" src="../js/jquery.sparkline.js"></script>
        <script type="text/javascript" src="../js/jquery.cookie.js"></script>
        <script type="text/javascript" src="../js/jquery.json-2.2.js"></script>
        <script type="text/javascript" src="../js/utils.js"></script>
        <?php require ("../host_report_menu.php") ?>

        <script type='text/javascript'>
            var rname;
            var refresh = true;
            var rto     = null;
            var params  = "&rs_page=<?php echo $rs_page ?>&page=<?php echo $page ?>";

            function postload() {
                rto = setTimeout(refresh_state, 100);

                $('.tip').tipTip({defaultPosition:"right",maxWidth:'400px'});

                $(".pn_buttons").click(function(event){
                    refresh = false;
                });

                $(".manageJob").click(function(event){
                    if (window.event && jQuery.browser.msie){
                        window.event.cancelBubble=true;
                    }
                    else {
                        event.stopPropagation();
                    }

                    var image_id = $(this).attr("id");
                    var tmp     = image_id.split('_');
                    var command = tmp[0];
                    var id      = tmp[1];
                    var action  = '';

                    var msg = '';
                    var opts = {"yes": "<?php echo _('Yes') ?>", "no": "<?php echo _('No') ?>"};

                    if(command == 'resumeTask') {
                        action = '<?php echo _('Resuming job')?> ...';
                        msg  = "<?php echo Util::js_entities(_('Are you sure you want to resume this job ?')) ?>";
                    }
                    else {
                        action = '<?php echo _('Stopping job')?> ...';
                        msg  = "<?php echo Util::js_entities(_('Are you sure you want to stop this job ?')) ?>";
                    }

                    av_confirm(msg, opts).done(function()
                    {
                        $('#changing_task_status_' + id).toggle();

                        $('#' + image_id).off();
                        $('#' + image_id).addClass('img_disabled');

                        $.ajax({
                            type: "GET",
                            url: "manage_jobs.php",
                            dataType: 'json',
                            data: { disp: command, job_id: id },
                            beforeSend: function() {
                                show_loading_box(action);
                            },
                            success: function(data) {
                                if (data.status == 'success'){
                                    setTimeout (function() {
                                        refresh = true;
                                        refresh_page();
                                    }, 100);
                                } else {
                                    show_notification('c_action_error', data.data, 'nf_error', 5000);
                                }
                            },
                            error: function(data)
                            {
                                //Checking expired session
                                var session = new Session(data, '');
                                if (session.check_session_expired() == true)
                                {
                                    session.redirect();
                                    return;
                                }

                                $('#changing_task_status_' + id).toggle();
                                $('#' + image_id).on();
                                $('#' + image_id).removeClass('img_disabled');
                                remove_loading_box();
                            }
                        });
                    });
                });

                setTimeout(refresh_page, 120000);
            }


            function show_details(id) {
                $('.'+id).toggle();
            }


            function get_task_progress (percentage) {
                var host_progress = "<div style='float:left;width:55%;text-align:right;padding:3px 0px 0px 0px'>";

                if (percentage <=25) {
                    host_progress += "<img src='./images/light_yellow_lit.png' border='0'/>";
                    host_progress += "<img src='./images/light_gray_lit.png' border='0'/>";
                    host_progress += "<img src='./images/light_gray_lit.png' border='0'/>";
                    host_progress += "<img style='padding-right:7px;' src='./images/light_gray_lit.png' border='0'/>";
                }
                else if (percentage <=50) {
                    host_progress += "<img src='./images/light_green_lit.png' border='0'/>";
                    host_progress += "<img src='./images/light_yellow_lit.png' border='0'/>";
                    host_progress += "<img src='./images/light_gray_lit.png' border='0'/>";
                    host_progress += "<img style='padding-right:7px;' src='./images/light_gray_lit.png' border='0'/>";
                }
                else if (percentage <=75) {
                    host_progress += "<img src='./images/light_green_lit.png' border='0'/>";
                    host_progress += "<img src='./images/light_green_lit.png' border='0'/>";
                    host_progress += "<img src='./images/light_yellow_lit.png' border='0'/>";
                    host_progress += "<img style='padding-right:7px;' src='./images/light_gray_lit.png' border='0'/>";
                }
                else if (percentage <=99) {
                    host_progress += "<img src='./images/light_green_lit.png' border='0'/>";
                    host_progress += "<img src='./images/light_green_lit.png' border='0'/>";
                    host_progress += "<img src='./images/light_green_lit.png' border='0'/>";
                    host_progress += "<img style='padding-right:7px;' src='./images/light_yellow_lit.png' border='0'/>";}
                else {
                    host_progress += "<img src='./images/light_green_lit.png' border='0'/>";
                    host_progress += "<img src='./images/light_green_lit.png' border='0'/>";
                    host_progress += "<img src='./images/light_green_lit.png' border='0'/>";
                    host_progress += "<img style='padding-right:7px;' src='./images/light_green_lit.png' border='0'/>";
                }
                host_progress += "</div><div style='float:right;width:43%;text-align:left'>" + percentage + "%</div>";

                return host_progress;
            }


            function refresh_page() {
                if(refresh) {
                    clearTimeout(rto);
                    rto = null;
                    document.location.href="<?php echo Menu::get_menu_url('manage_jobs.php', 'environment', 'vulnerabilities', 'scan_jobs') ?>" + params;
                }
            }


            function show_loading_box(message){
                $('.w_overlay').remove();

                if ($('.w_overlay').length < 1)
                {
                    var height = $.getDocHeight();
                    $('body').append('<div class="w_overlay" style="height:'+height+'px;"></div>');
                }

                if ($('.l_box').length < 1)
                {
                    var config  = {
                        content: message,
                        style: 'width: 400px; top: 20%; padding: 5px 0px; left: 50%; margin-left: -175px;',
                        cancel_button: false
                    };

                    var loading_box = Message.show_loading_box('s_box', config);

                    $('body').append('<div class="l_box" style="display:none;">'+loading_box+'</div>');
                }
                else
                {
                    $('.l_box .r_lp').html(message);
                }

                $('.l_box').show();
            }


            function remove_loading_box(){
                $('.l_box').remove();
                $('.w_overlay').remove();
            }


            function deleteTask(id) {
                var msg  = "<?php echo Util::js_entities(_('Are you sure you want to delete this entry?')) ?>";
                var opts = {"yes": "<?php echo _('Yes') ?>", "no": "<?php echo _('No') ?>"};

                av_confirm(msg, opts).done(function(){
                    $.ajax({
                        type: "GET",
                        url: "manage_jobs.php",
                        data: { disp: 'deleteTask', job_id: id },
                        beforeSend: function() {
                            show_loading_box('<?php echo _('Deleting task')?> ...');
                        },
                        success: function() {
                            $.ajax({
                                type: "GET",
                                url: "sched.php",
                                data: { action: 'delete_scan', job_id: id },
                                success: function() {
                                    setTimeout (function() {
                                        refresh = true;
                                        refresh_page();
                                    }, 100);
                                },
                                error: function(){
                                    remove_loading_box();

                                    //Check expired session
                                    var session = new Session(data, '');

                                    if (session.check_session_expired() == true)
                                    {
                                        session.redirect();
                                        return;
                                    }
                                }
                            });
                        },
                        error: function (data){

                            remove_loading_box();

                            //Check expired session
                            var session = new Session(data, '');

                            if (session.check_session_expired() == true)
                            {
                                session.redirect();
                                return;
                            }
                        }
                    });
                });
            }


            function refresh_state() {
                var tasks = [];
                var param = "";

                $('.cstatus').each(function() {
                    tasks.push(this.id);
                });

                if( tasks.length>0 ) {
                    param = "&tasks=" + tasks.join("%23");

                    $.ajax({
                        type: "GET",
                        dataType: "json",
                        url: "get_state.php?bypassexpirationupdate=1"+param,
                        success: function(data) {

                            if (data.status == 'success'){
                                var progress = '0%';

                                if (data.hasOwnProperty("data")){
                                    $.each(data['data'], function (index, task)
                                    {
                                        progress = parseInt(task['data']['progress']);

                                        //TasK has finished
                                        if (progress == '-1' && task['data']['status'] == 'Done'){
                                            progress = 100;
                                        }

                                        if (progress >= 0 && progress <= 100){
                                            progress = progress + '%';

                                            $('#nessus_threads_'+task['data']['job_id']+' .stripes').css('width', progress);
                                            $('#nessus_threads_'+task['data']['job_id']+' .text-percents').text(progress);
                                        }

                                        if (task['data'].hasOwnProperty("host_progress")){
                                            $.each(task['data']['host_progress'], function (index, h_data) {
                                                $('#' + task['data']['job_id'] + '-' + h_data['md5_ip'] + '-per').html(get_task_progress(h_data['progress']));
                                            });
                                        }
                                    });
                                }
                            }

                            rto = setTimeout (refresh_state, 10000);
                        }
                    });
                }
            }


            function GB_onclose(url) {
                var url_matches = new Array();

                if(typeof(url) != "undefined") {
                    url_matches = url.match(/import_nbe/g);
                }

                if(url_matches == null) {
                    document.location.href = '<?php echo AV_MAIN_PATH ?>/vulnmeter/manage_jobs.php';
                }
                else if(url_matches[0] == "import_nbe" && typeof(rname) != "undefined" && rname !="undefined" ) {
                    document.location.href = '<?php echo AV_MAIN_PATH ?>/vulnmeter/index.php?rvalue='+rname+'&type=jobname&m_opt=environment&sm_opt=vulnerabilities';
                }
            }

            $(document).ready(function(){
                GB_TYPE = 'w';
                $("a.greybox").click(function(){
                    var title  = $(this).attr('title');
                    var width  = 400;
                    var height = 190;
                    var dest   = $(this).attr('href');

                    if(typeof(title) == "undefined") {
                        title = '<?php echo _("Make this scan job visible for:")?>';
                    }

                    if(title == '<?php echo _("Import AlienVault Scan") ?>') {
                        width  = '80%';
                        height = 320;
                    }

                    refresh = false;

                    GB_show(title, dest, height, width);
                    return false;
                });
            });
        </script>
    </head>

<body>
<?php

//Local menu
include_once '../local_menu.php';

$pageTitle = _("Manage Jobs");

require_once 'functions.inc';
require_once 'ossim_sql.inc';

$version = $conf->get_conf("ossim_server_version");

list($arruser, $user) = Vulnerabilities::get_users_and_entities_filter($dbconn);

$query  = "select count(*) as total from vuln_nessus_plugins";
$result = $dbconn->execute($query);
$pluginscount = $result->fields['total'];

if ($pluginscount == 0) {
    die ("<h2>"._("No plugins found.  Please contact support for further assistance").".</h2>");
}


function delete_schedule($job_id) {
    global $viewall, $sortby, $sortdir, $uroles, $username, $dbconn;

    $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

    $sql_require = "";
    if (!$uroles['admin']) {
        $sql_require = "AND username='$username'";
    }

    $query = "SELECT id, name FROM vuln_job_schedule WHERE id = ? $sql_require";
    $params = array ($job_id);

    $result = $dbconn->Execute($query, $params);

    list($jid, $nname) = $result->fields;

    if ($jid > 0) {
        $query = "DELETE FROM vuln_job_schedule WHERE id = '$job_id' $sql_require";
        $result = $dbconn->Execute($query);

        Vulnerabilities::update_vuln_job_assets($dbconn, 'delete', $job_id, 0);

        $infolog = array($nname);
        Log_action::log(68, $infolog);
    }

    main_page ($viewall, $sortby, $sortdir);
}


function set_status ($job_id, $enabled) {
    global $viewall, $sortby, $sortdir, $uroles, $username, $dbconn;

    $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

    $sql_require = "";
    if (!$uroles['admin']) {
        $sql_require = "AND username='$username'";
    }

    $query = "SELECT id, name FROM vuln_job_schedule WHERE id = '$job_id' $sql_require";
    $result=$dbconn->Execute($query);
    list($jid, $nname) = $result->fields;

    if ($jid > 0) {

        $action = (intval($enabled) == 1) ? 'insert' : 'delete';

        Vulnerabilities::update_vuln_job_assets($dbconn, $action, $job_id, 0);

        $query = "UPDATE vuln_job_schedule SET enabled ='$enabled' WHERE id = '$job_id' $sql_require";

        $result = $dbconn->Execute($query);

    } else {
        echo _("Unable to Change Status for recurring schedule")." <i>\"$nname\"</i>";
    }

    main_page ($viewall, $sortby, $sortdir);
}



function main_page ($viewall, $sortby, $sortdir)
{
    global $uroles, $username, $dbconn;
    global $arruser, $user, $rs_page;

    $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

    $tz = Util::get_timezone();

    if ($sortby == "") {
        $sortby = "next_CHECK";
    }

    if ($sortdir == "") {
        $sortdir = "ASC";
    }

    //When sorting by next_CHECK the system protect the _ with \ and the mysql is not able to understand it
    $sortby = str_replace("\\","", $sortby);

    //Schedule Type is ordered in a special way
    if ($sortby == "schedule_type") {
        $sql_order = "ORDER BY FIELD(schedule_type, 'O', 'D', 'W', 'M', 'NW') $sortdir";
    } else {
        $sql_order = "ORDER BY $sortby $sortdir";
    }

    if (Session::menu_perms("environment-menu", "EventsVulnerabilitiesScan"))
    {
        ?>
        <div id='c_action'><div id='c_action_error'></div></div>

        <div style="width:50%; position: relative; height: 5px; float:left">

			<div style="width:100%; position: absolute; top: -41px;left:0px;">
    			<div style="float:left; height:28px; margin:5px 5px 0px 0px;">
    				<a class="button" href="<?php echo Menu::get_menu_url(AV_MAIN_PATH . '/vulnmeter/sched.php?action=create_scan&hosts_alive=1&scan_locally=1', 'environment', 'vulnerabilities', 'scan_jobs');?>">
                            <?php echo _("New Scan Job");?>
    				</a>
    			</div>

    			<div style="float:left;height:28px;margin:5px 5px 0px -2px;">
    				<a class="greybox button av_b_secondary" href="import_nbe.php" title="<?php echo _("Import AlienVault Scan") ?>">
    				        <?php echo _("Import AlienVault Scan");?>
    				</a>
    			</div>
			</div>
        </div>
        <?php
    }

    if (intval($_GET['page']) != 0) {
        $page = intval($_GET['page']);
    }
    else {
        $page = 1;
    }

    $pagesize = 10;

    if($username=="admin") {
        $query = "SELECT count(id) as num FROM vuln_jobs WHERE status !='R'";
    }
    else {
        $query = "SELECT count(id) as num FROM vuln_jobs where username='$username' WHERE status !='R'";
    }

    $result = $dbconn->Execute($query);
    $jobCount =$result->fields["num"];

    $num_pages = ceil($jobCount/$pagesize);

    echo Vulnerabilities::get_running_gvm_scans($dbconn, $rs_page);

    $schedulejobs = _("Scheduled Jobs");
    echo <<<EOT

   <table style='margin-top:20px;' class='w100 transparent'><tr><td class='sec_title'>$schedulejobs</td></tr></table>
   <table summary="Job Schedules" class='w100 table_list'>
EOT;

    $n_sortdir = ($sortdir == "ASC") ? "DESC" : "ASC";

    $arr = array(
        "name"          => "Name",
        "schedule_type" => "Schedule Type",
        "time"          => "Time",
        "next_CHECK"    => "Next Scan",
        "enabled"       => "Status"
    );

    if (empty($arruser)){
        $query = "SELECT t2.name as profile, t1.meth_TARGET, t1.id, t1.name, t1.schedule_type, t1.profile_id, t1.meth_TIMEOUT, t1.username, t1.enabled, t1.next_CHECK, t1.email
                  FROM vuln_job_schedule t1 LEFT JOIN vuln_nessus_settings t2 ON t1.profile_id=t2.id ";
    }
    else {
        $query = "SELECT t2.name as profile, t1.meth_TARGET, t1.id, t1.name, t1.schedule_type, t1.profile_id, t1.meth_TIMEOUT, t1.username, t1.enabled, t1.next_CHECK, t1.email
                  FROM vuln_job_schedule t1 LEFT JOIN vuln_nessus_settings t2 ON t1.profile_id=t2.id WHERE username in ($user) ";
    }

    $query .= $sql_order;

    $result = $dbconn->execute($query);

    if ($result->EOF){
        echo "<tr><td class='empty_results' height='20' style='text-align:center;'>"._("No Scheduled Jobs")."</td></tr>";
    }
    else {
        echo "<tr>";
        foreach ($arr as $order_by => $value) {
            $sort_class = 'ui-icon-carat-2-n-s';

            if ($sortby == $order_by){
                $sort_class = ($sortdir == 'DESC') ? 'ui-icon-triangle-1-s' : 'ui-icon-triangle-1-n';
            }

            echo "<th onclick='document.location.href=\"manage_jobs.php?sortby=$order_by&sortdir=$n_sortdir\"'>
                    "._($value)."
                    <div class='DataTables_sort_wrapper'>
                    <span class='DataTables_sort_icon css_right ui-icon $sort_class'></span>
                    </div>
                </th>";
        }

        if (Session::menu_perms("environment-menu", "EventsVulnerabilitiesScan")) {
            echo "<th>"._("Action")."</th></tr>";
        }
    }

    $colors  = array("#FFFFFF", "#EEEEEE");
    $color   = 0;

    while (!$result->EOF) {
        list ($profile, $targets, $job_id, $schedname, $schedtype, $sid, $timeout, $user, $schedstatus, $nextscan, $servers )=$result->fields;

        $name = Av_sensor::get_name_by_id($dbconn, $servers);

        $servers = ($name != '') ? $name : _('First Available Sensor');

        $targets_to_resolve = explode("\n", $targets);
        $ttargets = array();

        foreach($targets_to_resolve as $id_ip)
        {
            if( preg_match("/^([a-f\d]{32})#\d+\.\d+\.\d+\.\d+\/\d{1,2}/i", $id_ip, $found) && Asset_net::is_in_db($dbconn, $found[1])) {
                $ttargets[] = preg_replace("/^([a-f\d]{32})#/i", "", $id_ip)." (".Asset_net::get_name_by_id($dbconn, $found[1]).")";
            }
            else if( preg_match("/^([a-f\d]{32})#\d+\.\d+\.\d+\.\d+/i", $id_ip, $found) &&  Asset_host::is_in_db($dbconn, $found[1])) {
                $ttargets[] = preg_replace("/^([a-f\d]{32})#/i", "", $id_ip)." (".Asset_host::get_name_by_id($dbconn, $found[1]).")";
            }
            else if( preg_match("/^([a-f\d]{32})#hostgroup/i", $id_ip, $found)) {
                $hostgroup_name = Asset_group::get_name_by_id($dbconn, $found[1]);
                $ttargets[] = ($hostgroup_name == _('Unknown')) ? _('Unknown hostgroup') : $hostgroup_name;
            }
            else if( preg_match("/^([a-f\d]{32})#netgroup/i", $id_ip, $found)) {
                $netgroup_name  = Net_group::get_name_by_id($dbconn, $found[1]);
                $ttargets[] = ($netgroup_name == _('Unknown')) ? _('Unknown netgroup') : $netgroup_name;
            }
            else {
                $ttargets[] = preg_replace("/[a-f\d]{32}/i","",$id_ip);
            }
        }

        $targets = implode("<BR/>", $ttargets);

        $nextscan = gmdate("Y-m-d H:i:s", Util::get_utc_unixtime($nextscan)+(3600*$tz));

        preg_match("/\d+\-\d+\-\d+\s(\d+:\d+:\d+)/",$nextscan,$found);
        $time = $found[1];

        switch ($schedtype) {
            case "O":
                $stt = _("Once");
                break;
            case "D":
                $stt = _("Daily");
                break;
            case "W":
                $stt = _("Weekly");
                break;
            case "M":
                $stt = _("Monthly");
                break;
            case "NW":
                $stt = _("N<sup>th</sup> week of the month");
                break;
            default:
                $stt="&nbsp;";
                break;
        }

        switch ($schedstatus) {
            case "1":
                $itext = _("Disable Scheduled Job");
                $isrc = "images/stop_task.png";
                $ilink = "manage_jobs.php?disp=setStatus&job_id=$job_id&enabled=0";
                break;
            default:
                $itext = _("Enable Scheduled Job");
                $isrc = "images/play_task.png";
                $ilink = "manage_jobs.php?disp=setStatus&job_id=$job_id&enabled=1";
                break;
        }

        if (!Session::menu_perms("environment-menu", "EventsVulnerabilitiesScan")) {
            $ilink = "javascript:return false;";
        }

        if ($schedstatus) {
            $txt_enabled = "<td><a href=\"$ilink\"><span style='color:green'>"._("Enabled")."</span></a></td>";
        } else {
            $txt_enabled = "<td><a href=\"$ilink\"><span style='color:red'>"._("Disabled")."</span></a></td>";
        }

        require_once ('classes/Security.inc');

        if(valid_hex32($user))
        {
            $user = Session::get_entity_name($dbconn, $user);
        }

        if ($profile == "") {
            $profile=_("Default");
        }


        echo "<tr bgcolor=\"".$colors[$color%2]."\">";

        echo "<td><span class=\"tip\" title=\"<b>"._("Owner").":</b> $user<br><b>"._("Sensor").":</b> $servers<br /><b>"._("Scheduled Job ID").":</b> $job_id<br><b>"._("Profile").":</b> $profile<br><b>"._("Targets").":</b><br>".$targets."\">$schedname</span></td>";
        ?>
        <td><?php echo $stt ?></td>
        <td><?php echo $time ?></td>
        <td><?php echo $nextscan ?></td>
        <?php
        echo <<<EOT
    $txt_enabled
    <td style="padding-top:2px;"><a href="$ilink"><img alt="$itext" src="$isrc" border=0 title="$itext"></a>&nbsp;
EOT;
        if (Session::menu_perms("environment-menu", "EventsVulnerabilitiesScan")) {
            echo "<a href='".Menu::get_menu_url(AV_MAIN_PATH . '/vulnmeter/sched.php?action=edit_sched&sched_id='.$job_id.'&status='.intval($schedstatus), 'environment', 'vulnerabilities', 'scan_jobs')."'><img src='images/pencil.png' title='"._("Edit Scheduled")."'></a>&nbsp;";
            echo "<a href='javascript:void(0);' onclick=\"return confirm_delete('deleteSchedule', '".$job_id."');\"><img src='images/delete.gif' title='".gettext("Delete Scheduled")."'></a>";
        }
        echo "</td>";
        echo <<<EOT
</tr>
EOT;
        $result->MoveNext();
        $color++;
    }
    echo <<<EOT
</table>
EOT;

    ?>
    <br />
    <?php
    $out = get_finished_gvm_scans(($page-1)*$pagesize, $pagesize);
    ?>
    <table width="100%" align="center" class="transparent" cellspacing="0" cellpadding="0">
        <tr>
            <td class="nobborder" valign="top" style="padding-top:5px;">
                <div class="fright">
                    <?
                    if ($out!=0 && $num_pages!=1)
                    {
                        $page_url = "manage_jobs.php";

                        if ($page==1 && $page==$num_pages){
                            echo '<a href="" class="link_paginate_disabled" onclick="return false">< '._("PREVIOUS").'</a>';
                            echo '<a class="lmargin link_paginate_disabled" href="" onclick="return false">'._("NEXT").' ></a>';
                        }
                        elseif ($page==1){
                            echo '<a href="" class="link_paginate_disabled" onclick="return false">< ' . _("PREVIOUS") . '</a>';
                            echo '<a class="lmargin" href="'.$page_url.'?page='.($page+1).'&rs_page='.$rs_page.'">'._("NEXT").' ></a>&nbsp;';
                        }
                        elseif($page == $num_pages){
                            echo '<a href="'.$page_url.'?page='.($page-1).'&rs_page='.$rs_page.'">< '._("PREVIOUS").'</a>';
                            echo '<a class="lmargin link_paginate_disabled" href="" onclick="return false">' . _("NEXT").' ></a>';
                        }
                        else {
                            echo '<a href="'.$page_url.'?page='.($page-1).'&rs_page='.$rs_page.'">< '._("PREVIOUS").'</a><a class="lmargin" href="'.$page_url.'?page='.($page+1).'&rs_page='.$rs_page.'">'._("NEXT").' ></a>';
                        }
                    }
                    ?>
                </div>
            </td>
        </tr>
    </table>
    <?
}

switch($disp) {
    case "deleteSchedule":
        delete_schedule($job_id);
        break;

    case "setStatus":
        set_status ($job_id, $enabled);
        break;

    default:
        main_page( 1, $sortby, $sortdir);
        break;
}

require_once("footer.php");

