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


/**
* Function list:
* - ip_max_occurrences()
* - event_max_occurrences()
* - event_max_risk()
* - port_max_occurrences()
* - less_stable_services()
*/


set_time_limit(900);

require_once 'av_init.php';
Session::logcheck("analysis-menu", "ReportsAlarmReport");


/*
* return the list of host with max occurrences
* as dest or source
* pre: type is "ip_src" or "ip_dst"
*/
function ip_max_occurrences($target, $date_from, $date_to) 
{
    global $NUM_HOSTS;
    global $security_report;
    global $report_type;
    global $geoloc;
    
    /* ossim framework conf */
    $conf              = $GLOBALS['CONF'];    
    $report_graph_type = $conf->get_conf('report_graph_type');
    
    if (!strcmp($target, "ip_src")) 
    {
        if ($report_type == "alarm") 
        {
            $target = "src_ip";
        }
        
        $title = _("Attacker hosts");
    } 
    elseif (!strcmp($target, "ip_dst")) 
    {
        if ($report_type == "alarm") 
        {
            $target = "dst_ip";
        }
        
        $title = _("Attacked hosts");
    }
    
    $list = $security_report->AttackHost($target, $NUM_HOSTS, $report_type, $date_from, $date_to);
    
    if (!is_array($list) || empty($list))
    {
        return 0;
    }    
    
    ?>
    <table class='t_alarms'>
        <thead>
            <tr><td colspan='2' class="headerpr"><?php echo _("Top")?><?php echo " $NUM_HOSTS $title" ?></td></tr>
        </thead>
        
        <tbody>
            <tr>
                <td class='td_container'>
                    <table class="table_data">
                        <thead>                     
                            <tr>
                                <th> <?php echo _("Host"); ?> </th>
                                <th> <?php echo _("Occurrences"); ?> </th>
                            </tr>
                        </thead>
                        
                        <tbody>
                        <?php
                        foreach($list as $l) 
                        {
                            $ip          = $l[0];
                            $occurrences = number_format($l[1], 0, ",", ".");
                            $id          = $l[2];
                            $ctx         = $l[3];
                
                            $host_output = Asset_host::get_extended_name($security_report->ossim_conn, $geoloc, $ip, $ctx, $id);
                            $hostname    = $host_output['name'];
                            $icon        = $host_output['html_icon'];
                            $os          = (valid_hex32($id)) ? Asset_host_properties::get_os_by_host($security_report->ossim_conn, $id) : "";
                            $os_pixmap   = (preg_match("/unknown/", $os)) ? '' : $os;
                            $bold        = $host_output['is_internal'];
                            
                            ?>
                            <tr>
                                <td class='td_data <?php if ($bold) echo 'bold' ?>'>                                
                                    <?php echo $icon.' '.$hostname.' '.$os_pixmap?>
                                </td>
                                <td class='td_data'><?php echo $occurrences ?></td>
                            </tr>
                            <?php
                        }
                        ?>
                        </tbody>
                    </table>
                </td>
                
                <td class='td_container'>
                    <?php
                    if ($report_graph_type == "applets") 
                    {
                        jgraph_attack_graph($target, $NUM_HOSTS);
                    } 
                    else 
                    {
                        ?>
                        <img src="graphs/attack_graph.php?target=<?php echo $target ?>&hosts=<?php echo $NUM_HOSTS ?>&type=<?php echo $report_type ?>&date_from=<?php echo urlencode($date_from)?>&date_to=<?php echo urlencode($date_to)?>" alt="attack_graph"/>
                        <?php
                    }
                    ?>
                </td>                 
            </tr>
        </tbody>
    </table>
    <?php
    
    return 1;
    
}

/*
* return the event with max occurrences
*/
function event_max_occurrences($date_from, $date_to) 
{
    global $NUM_HOSTS;
    global $security_report;
    global $report_type;
    
    /* ossim framework conf */
    $conf              = $GLOBALS['CONF'];   
    $report_graph_type = $conf->get_conf('report_graph_type');
    
    $list = $security_report->Events($NUM_HOSTS, $report_type, $date_from, $date_to);
    
    if (!is_array($list) || empty($list))
    {
        return 0;
    }
    
    ?>
    <table class='t_alarms'>
        <thead>
            <tr>
                <td class="headerpr">
                <?php
                if ($report_type == "alarm")
                { 
                    echo _("Top")." ".$NUM_HOSTS." "._("Alarms");
                } 
                else
                { 
                    echo _("Top")." ".$NUM_HOSTS." "._("Events");
                } 
                ?>
                </td>
            </tr>
        </thead>
        
        <tbody>     
            <tr>
                <td class='td_container'>
                    <table class='table_data'>
                        <thead>
                            <tr>
                                <?php
                                if ($report_type == "alarm") 
                                { 
                                    ?>
                                    <th> <?php echo gettext("Alarm"); ?> </th>
                                    <?php
                                } 
                                else 
                                { 
                                    ?>
                                    <th> <?php echo gettext("Event"); ?> </th>
                                    <?php
                                } 
                                ?>
                                <th><?php echo gettext("Occurrences"); ?> </th>
                            </tr>
                        </thead>
                        
                        <tbody>
                            <?php       
                            foreach($list as $l) 
                            {
                                $event = $l[0];
                                $occurrences = number_format($l[1], 0, ",", ".");
                                ?>
                                <tr>                
                                    <td class='left td_data'><?php echo Util::signaturefilter($event);?></td>
                                    <td class='center td_data'><?php echo $occurrences ?></td>
                                </tr>
                                <?php
                            }
                            ?>
                            
                        </tbody>
                    </table>
             </td>
         </tr>
         <tr>
            <td class='center transparent'>
                
                <?php
                    if ($report_graph_type == "applets") 
                    {
                        jgraph_nbevents_graph();
                    } 
                    else 
                    {
                        ?>
                        <iframe scrolling="no" src="graphs/events_received_graph.php?hosts=<?php echo $NUM_HOSTS?>&type=<?php echo $report_type ?>&date_from=<?php echo urlencode($date_from)?>&date_to=<?php echo urlencode($date_to)?>" 
                        alt="<?php echo _("Events graph")?>" frameborder="0" style="margin:0px;padding:0px;width:430px;height:300px;border: 0px solid rgb(170, 170, 170);text-align:center"> </iframe><?
                    
                    }
                ?>                  
            </td>
        </tr>
     </tbody>
    </table>
    <?php
    
    return 1;
}
/*
* return a list of events ordered by risk
*/
function event_max_risk($date_from,$date_to) 
{
    global $NUM_HOSTS;
    global $security_report;
    global $report_type;
    
    require_once 'sec_util.php';
    
    $list = $security_report->EventsByRisk($NUM_HOSTS, $report_type, $date_from, $date_to);
    
    if (!is_array($list) || empty($list))
    {
        return 0;
    }
    
    ?>
    <table class='t_alarms'>
        <thead>
            <tr>
                <td class="headerpr">
                <?php
                if ($report_type == "alarm")
                { 
                    echo _("Top")." ".$NUM_HOSTS." "._("Alarms by Risk");
                } 
                else
                { 
                    echo _("Top")." ".$NUM_HOSTS." "._("Events by Risk");
                } 
                ?>
                </td>
            </tr>
        </thead>
        
        <tbody>     
            <tr>
                <td class='td_container'>
                    <table class='table_data'>
                        <thead>
                            <tr>
                                <?php
                                if ($report_type == "alarm") 
                                { 
                                    ?>
                                    <th> <?php echo gettext("Alarm"); ?> </th>
                                    <?php
                                } 
                                else 
                                { 
                                    ?>
                                    <th> <?php echo gettext("Event"); ?> </th>
                                    <?php
                                } 
                                ?>
                                <th><?php echo gettext("Risk");?></th>
                            </tr>
                        </thead>
                        
                        <tbody>
                            <?php
                            
                            foreach($list as $l) 
                            {
                                $event = $l[0];
                                $risk  = $l[1];
                                ?>
                                <tr>
                                    <td class='left td_data' valign='middle'><?php echo Util::signaturefilter($event);?></td>
                                    <td class='left td_data' valign='middle'><?php echo_risk($risk);?></td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>
    <br/>
    <?php
    
    return 1;
}

/*
* return the list of ports with max occurrences
*/

function port_max_occurrences($date_from, $date_to) 
{
    global $NUM_HOSTS;
    global $security_report;
    global $report_type;
    
    /* ossim framework conf */
    $conf              = $GLOBALS['CONF'];    
    $report_graph_type = $conf->get_conf('report_graph_type');
    
    $list = $security_report->Ports($NUM_HOSTS, $report_type, $date_from, $date_to);
    
    if (!is_array($list) || empty($list))
    {
        return 0;
    }
    
    ?>
        
    <table class='t_alarms'>
        <thead>
            <tr><td colspan='2' class="headerpr"><?php echo _("Top")?> <?php echo "$NUM_HOSTS"?> <?php echo _("Destination Ports")?></td></tr>
        </thead>
        
        <tbody>     
            <tr>
                <td class='td_container'>                   
                    <table class='table_data'>
                        <thead>                   
                            <tr>
                                <th><?php echo _("Port")?></th>
                                <th><?php echo _("Service")?></th>
                                <th><?php echo _("Occurrences")?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php                   
                            foreach($list as $l) 
                            {
                                $port    = $l[0];
                                $service = $l[1];
                                $occurrences = number_format($l[2], 0, ",", ".");
                                ?>
                                <tr>
                                    <td class='left td_data' valign='middle'><?php echo $port?></td>
                                    <td class='left td_data' valign='middle'><?php echo $service?></td>
                                    <td class='center td_data' valign='middle'><?php echo $occurrences?></td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </td>
                
                <td class='td_container'>
                    <?php
                    if ($report_graph_type == "applets") 
                    {
                        jgraph_ports_graph();
                    } 
                    else
                    {
                        ?>
                        <img src="graphs/ports_graph.php?ports=<?php echo $NUM_HOSTS?>&type=<?php echo $report_type ?>&date_from=<?php echo urlencode($date_from)?>&date_to=<?php echo urlencode($date_to)?>"/>
                        <?php
                    }
                    ?>
                </td>
            </tr>
        </tbody>
    </table>
    <?php
    
    return 1;
}



if (GET('type') == 'alarm') 
{
    $report_type = "alarm";
} 
else 
{
    $report_type = "event";
}

$path_conf    = $GLOBALS["CONF"];
$jpgraph_path = $path_conf->get_conf("jpgraph_path");

if (!is_readable($jpgraph_path)) 
{    
    $error = new Av_error();
    $error->set_message('JPGRAPH_PATH');
    $error->display();    
}

$geoloc = new Geolocation("/usr/share/geoip/GeoLiteCity.dat");

require_once 'jgraphs/jgraphs.php';


$security_report = new Security_report();
$server          = Util::get_default_admin_ip();
$file            = $_SERVER["REQUEST_URI"];

/* database connect */
$db   = new ossim_db();
$conn = $db->connect();

/* Number of hosts to show */
$NUM_HOSTS = 10;

//#############################
// Top attacked hosts
//#############################

$month     = 60 * 60 * 24 * 31; # 1 month
$date_from = (GET('date_from') != "") ? GET('date_from') : strftime("%Y-%m-%d", time()-$month);
$date_to   = (GET('date_to') != "")   ? GET('date_to') : strftime("%Y-%m-%d", time());
$back      = GET('back');

ossim_valid($date_from,     OSS_DIGIT, OSS_SCORE, OSS_NULLABLE,     'illegal:' . _("From date"));
ossim_valid($date_to,       OSS_DIGIT, OSS_SCORE, OSS_NULLABLE,     'illegal:' . _("To date"));
ossim_valid($back,          OSS_TEXT, OSS_NULLABLE,                 'illegal:' . _("Back Option"));

if (ossim_error())
{
    die(ossim_error());
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title> <?php echo _("OSSIM Framework"); ?> </title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    <link rel="stylesheet" type="text/css" href="/ossim/style/jquery-ui.css"/>
    <link rel="stylesheet" type="text/css" href="../style/datepicker.css"/>
    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/jquery-ui.min.js"></script>
   
    <style type='text/css'>
                    
        .t_alarms
        {
            margin: auto;
            border: none;
            border-collapse: collapse;
            border: solid 1px #DFDFDF;
            background: #FAFAFA;
            width: 80%;
        }
        
        .td_container
        {
            border: none;
            vertical-align: top;
            border-collapse: collapse;
            background: #FAFAFA !important
        }
            
        .td_data
        {
            /*border: solid 1px #DFDFDF;*/
            padding: 2px 5px;
        }
        
        .t_alarms table
        {
            /*border: solid 1px #DFDFDF;*/
            border-collapse: collapse;
            background: transparent;
            margin: 10px auto;
        }
        
        .t_alarms th
        {       
            color: #555555;
            /*border: solid 1px #DFDFDF;*/
        }
        
        .headerpr
        {
            border: none;
            border-bottom: solid 1px #E4E4E4 !important;
            padding: 4px !important;
            background: #D3D3D3 !important;
        }
        
        
        .table_data th
        {
            height: auto;
            /*border: solid 1px #DFDFDF;*/
        }
        
    </style>
    
    <script type="text/javascript">
        $(document).ready(function(){
            // CALENDAR

            $('.date_filter').datepicker({
                showOn: "both",
                buttonText: "",
                dateFormat: "yy-mm-dd",
                buttonImage: "/ossim/pixmaps/calendar.png",
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
                    
                    if ($('#date_to').val() != '' && $('#date_from').val() != '') 
                    {
                        $('#dateform').submit();
                    }
                }
            });
        });       
    </script>   
</head>

<body>
    <form method='GET' id="dateform">
        <input type="hidden" name="section" value="<?php echo Util::htmlentities(GET('section')) ?>">
        <input type="hidden" name="type"    value="<?php echo Util::htmlentities(GET('type')) ?>">  
        <input type="hidden" name="back"    value="<?php echo str_replace('"', '%22', $back) ?>">
        
        <div class="datepicker_range" style="margin: 10px auto 0px auto;width:200px;height:30px;">
            <div class='calendar_from'>
                <div class='calendar'>
                    <input name='date_from' id='date_from' class='date_filter' type="input" value="<?php echo $date_from ?>"/>
                </div>
            </div>
            <div class='calendar_separator'>
                -
            </div>
            <div class='calendar_to'>
                <div class='calendar'>
                    <input name='date_to' id='date_to' class='date_filter' type="input" value="<?php echo $date_to ?>"/>
                </div>
            </div>
        </div>
 
    </form>

    <div id='c_a_reports'>
        <?php
        $ret = 1;
        
        if (GET('section') == 'attacked') 
        {
            echo "<br/><br/>";
            $ret = ip_max_occurrences("ip_dst",$date_from,$date_to);
        }
        //#############################
        // Top attacker hosts
        //#############################
        elseif (GET('section') == 'attacker') 
        {
            echo "<br/><br/>";
            $ret = ip_max_occurrences("ip_src",$date_from,$date_to);
        }
        //#############################
        // Top events received
        //#############################
        elseif (GET('section') == 'events_recv') 
        {
            echo "<br/><br/>";
            $ret = event_max_occurrences($date_from,$date_to);
        }
        //#############################
        // Top events risk
        //#############################
        elseif (GET('section') == 'events_risk') 
        {
            echo "<br/><br/>";
            $ret = event_max_risk($date_from,$date_to);
        }
        //#############################
        // Top used destination ports
        //#############################
        elseif (GET('section') == 'dest_ports')
        {
            echo "<br/><br/>";
            $ret = port_max_occurrences($date_from,$date_to);
        }
        //##############################
        // Top less stable services
        //##############################
        /*
        elseif (GET('section') == 'availability') {
            less_stable_services();
        } 
        */
        elseif (GET('section') == 'all') 
        {
            echo "<br/><br/>";
            $ret = ip_max_occurrences("ip_dst",$date_from,$date_to);
            
            if ($ret == 1)
            {   
                echo "<br/><br/>";
                ip_max_occurrences("ip_src",$date_from,$date_to);
                echo "<br/><br/>";
                port_max_occurrences($date_from,$date_to);
                echo "<br/><br/>";
                event_max_occurrences($date_from,$date_to);
                echo "<br/><br/>";
                event_max_risk($date_from,$date_to); 
                echo "<br/><br/>";
            }
        }
        
        
        if ($ret == 0)
        {
            $config_nt = array(
                'content' => _("No data available for that time period"),
                'options' => array (
                    'type'          => 'nf_info',
                    'cancel_button' => false
                ),
                'style'   => 'width: 80%; margin: 20px auto; text-align: left; padding: 5px; z-index:-1;'
            ); 
                            
            $nt = new Notification('nt_1', $config_nt);
            
            $nt->show();
        }
    
        $db->close();
        $geoloc->close();
        ?>
    </div>
</body>
</html>
