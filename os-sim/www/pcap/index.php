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


ini_set('max_execution_time','300'); 

require_once 'av_init.php';

function display_errors($info_error)
{
    $errors       = implode ("</div><div style='padding-top: 3px;'>", $info_error);
    $error_msg    = "<div>"._("The following errors occurred:")."</div><div style='padding-left: 15px;'><div>$errors</div></div>";

    return ossim_error($error_msg);
}

Session::logcheck('environment-menu', 'TrafficCapture');

$conf = $GLOBALS['CONF'];

$unlimited_traffic_capture = ($conf->get_conf('unlimited_traffic_capture') == 1) ? TRUE : FALSE;

$info_error = array();

$src              = $parameters['src']              = POST('src');
$dst              = $parameters['dst']              = POST('dst');
$timeout          = $parameters['timeout']          = POST('timeout');
$cap_size         = $parameters['cap_size']         = POST('cap_size');
$raw_filter       = $parameters['raw_filter']       = POST('raw_filter');
$sensor_ip        = $parameters['sensor_ip']        = POST('sensor_ip');
$sensor_interface = $parameters['sensor_interface'] = POST('sensor_interface');

$soptions         = intval(POST('soptions'));

$validate  = array (
    'src'              => array('validation' => "OSS_NULLABLE, OSS_DIGIT, OSS_SPACE, OSS_SCORE, OSS_INPUT, OSS_NL, '\.\,\/'", 'e_message' => 'illegal:' . _('Source')),
    'dst'              => array('validation' => "OSS_NULLABLE, OSS_DIGIT, OSS_SPACE, OSS_SCORE, OSS_INPUT, OSS_NL, '\.\,\/'", 'e_message' => 'illegal:' . _('Destination')),
    'timeout'          => array('validation' => 'OSS_DIGIT',                                                                  'e_message' => 'illegal:' . _('Timeout')),
    'cap_size'         => array('validation' => 'OSS_NULLABLE, OSS_DIGIT',                                                    'e_message' => 'illegal:' . _('Cap. size')),
    'raw_filter'       => array('validation' => "OSS_NULLABLE, OSS_ALPHA , '\.\|\&\=\<\>\!\^'",                               'e_message' => 'illegal:' . _('Raw Filter')),
    'sensor_ip'        => array('validation' => 'OSS_IP_ADDR',                                                                'e_message' => 'illegal:' . _('Sensor')),
    'sensor_interface' => array('validation' => 'OSS_INPUT',                                                                  'e_message' => 'illegal:' . _('Interface'))
);


foreach ($parameters as $k => $v )
{
    eval("ossim_valid(\$v, ".$validate[$k]['validation'].", '".$validate[$k]['e_message']."');");

    if ( ossim_error() )
    {
        $info_error[] = ossim_get_error();
        ossim_clean_error();
    }
}


$db     = new ossim_db();
$dbconn = $db->connect();

$keytree = "assets";

$scan = new Traffic_capture();

$states = array(
    '0'  => _('Idle'),
    '1'  => _('A Pending Capture'),
    '2'  => _('Capturing'),
    '-1' => _('Error When Capturing')
);

$scans_by_sensor = $scan->get_scans();
$sensors_status  = $scan->get_status();


if(!$scans_by_sensor)
{ 
    $scans_by_sensor = array();
}

if(!$sensors_status)  
{
    $sensors_status  = array();
}

foreach ($sensors_status as $sensor_ip => $value)
{ 
    if(!Session::sensorAllowed($sensor_ip))
    {
        unset($sensors_status[$sensor_ip]);
    }
}

// get sensors to get scan status
$ips_to_ckeck = array();

foreach($sensors_status as $sensor_ip => $sensor_info)
{
    if( intval($sensor_info[0]) == 2) 
    {
        $ips_to_ckeck[] = $sensor_ip;
    }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title> <?php echo gettext("OSSIM Framework"); ?> - Traffic capture </title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    <link type="text/css" rel="stylesheet" href="../style/jquery-ui.css"/>
    <link rel="stylesheet" type="text/css" href="../style/tree.css" />
    <link rel="stylesheet" type="text/css" href="../style/progress.css"/>
    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/jquery-ui.min.js"></script>
    <script type="text/javascript" src="../js/jquery.tmpl.1.1.1.js"></script>
    <script type="text/javascript" src="../js/jquery.dynatree.js"></script>
    <script type="text/javascript" src="../js/greybox.js"></script>
    <script type="text/javascript" src="../js/utils.js"></script>
    <script type="text/javascript" src="../js/progress.js"></script>
    <script type="text/javascript" src="../js/notification.js"></script>
    
    <style type='text/css'>
        
        .link_no_dec
        {
            text-decoration: none !important;
        }

        body
        {
            margin-bottom: 15px;
        }
    
    </style>
        
    <script type="text/javascript">
        $(document).ready(function(){
            <?php
            if(!empty($ips_to_ckeck)) 
            {
                ?>
                setTimeout (show_status,2000);
                <?php
            }
            ?>
            
            $("#stree").dynatree({
                initAjax: { url: "../tree.php?key=<?php echo $keytree ?>" },
                clickFolderMode: 2,
                onActivate: function(dtnode) {
                    var ln  = ($('#src').val()!='') ? '\n' : '';
                    var text = "";
                    
                    // Host Group
            			if (dtnode.data.key.match(/hostgroup_/))
            			{
            			    $.ajax({
            			        type: 'GET',
            			        url: "../tree.php",
            			        data: 'key=' + dtnode.data.key + ';1000',
            			        dataType: 'json',
            			        success: function(data)
            			        {
            				        if (data.length < 1)
            				        {
            				            var nf_style = 'padding: 3px; width: 90%; margin: auto; text-align: center;';
            				            var msg = '<?php echo _('Unable to fetch the asset group members') ?>';
            				            show_notification('av_info', msg, 'nf_error', 0, 1, nf_style);
            				        }
            				        else
            				        {
                                    // Group reached the 1000 top of page: show warning
                                    var last_element = data[data.length - 1].key;
                        
                                    if (last_element.match(/hostgroup_/))
                                    {
                                        var nf_style = 'padding: 3px; width: 90%; margin: auto; text-align: center;';
                                        var msg = '<?php echo _('This asset group has more than 1000 assets, please try again with a smaller group') ?>';
                                        show_notification('av_info', msg, 'nf_warning', 0, 1, nf_style);
                                    }
                                    else
                                    {
                                        jQuery.each(data, function(i, group_member)
                                        {
                                            Regexp = /(\d+\.\d+\.\d+\.\d+)/;
                                            match  = Regexp.exec(group_member.val);
                                                    
                                            var member_ip  = match[1];
                                            var member_ln  = ($('#src').val()!='') ? '\n' : '';
                                            
                                            $('#src').val($('#src').val() + member_ln + member_ip);
                                        });
                                    }
            				        }
            			        }
            			    });
            			}
            			// Net
                    else if (dtnode.data.val.match(/\d+\.\d+\.\d+\.\d+\/\d+/) !== null) 
                    { 
                        Regexp = /(\d+\.\d+\.\d+\.\d+\/\d+)/;
                        match  = Regexp.exec(dtnode.data.val);
                                
                        text  = match[1];
                    }
                    // Host
                    else if (dtnode.data.val.match(/\d+\.\d+\.\d+\.\d+/) !== null) 
                    { 
                        Regexp = /(\d+\.\d+\.\d+\.\d+)/;
                        match  = Regexp.exec(dtnode.data.val);
                                
                        text  = match[1];
                    }
			        
                    if (text != '') 
                    {
                       $('#src').val($('#src').val() + ln + text);
                    }
                },
                onDeactivate: function(dtnode) {},
                onLazyRead: function(dtnode){
                    dtnode.appendAjax({
                        url: "../tree.php",
                        data: {key: dtnode.data.key, page: dtnode.data.page}
                    });
                }
            });
                
            $("#dtree").dynatree({
                initAjax: { url: "../tree.php?key=<?php echo $keytree ?>" },
                clickFolderMode: 2,
                onActivate: function(dtnode) {
                    var ln  = ($('#dst').val()!='') ? '\n' : '';
                    var text = "";
                    
                    // Host Group
            			if (dtnode.data.key.match(/hostgroup_/))
            			{
            			    $.ajax({
            			        type: 'GET',
            			        url: "../tree.php",
            			        data: 'key=' + dtnode.data.key + ';1000',
            			        dataType: 'json',
            			        success: function(data)
            			        {
            				        if (data.length < 1)
            				        {
            				            var nf_style = 'padding: 3px; width: 90%; margin: auto; text-align: center;';
            				            var msg = '<?php echo _('Unable to fetch the asset group members') ?>';
            				            show_notification('av_info', msg, 'nf_error', 0, 1, nf_style);
            				        }
            				        else
            				        {
                                    // Group reached the 1000 top of page: show warning
                                    var last_element = data[data.length - 1].key;
                        
                                    if (last_element.match(/hostgroup_/))
                                    {
                                        var nf_style = 'padding: 3px; width: 90%; margin: auto; text-align: center;';
                                        var msg = '<?php echo _('This asset group has more than 1000 assets, please try again with a smaller group') ?>';
                                        show_notification('av_info', msg, 'nf_warning', 0, 1, nf_style);
                                    }
                                    else
                                    {
                                        jQuery.each(data, function(i, group_member)
                                        {
                                            Regexp = /(\d+\.\d+\.\d+\.\d+)/;
                                            match  = Regexp.exec(group_member.val);
                                                    
                                            var member_ip  = match[1];
                                            var member_ln  = ($('#dst').val()!='') ? '\n' : '';
                                            
                                            $('#dst').val($('#dst').val() + member_ln + member_ip);
                                        });
                                    }
            				        }
            			        }
            			    });
            			}
                    // Net
                    else if (dtnode.data.val.match(/\d+\.\d+\.\d+\.\d+\/\d+/) !== null)
                    {
                        Regexp = /(\d+\.\d+\.\d+\.\d+\/\d+)/;
                        match  = Regexp.exec(dtnode.data.val);
                                
                        text  = match[1];
                    }
                    // Host
                    else if (dtnode.data.val.match(/\d+\.\d+\.\d+\.\d+/) !== null)
                    { 
                        Regexp = /(\d+\.\d+\.\d+\.\d+)/;
                        match  = Regexp.exec(dtnode.data.val);
                                
                        text  = match[1];
                    }
                    
                    if (text != '') 
                    {
                       $('#dst').val($('#dst').val() + ln + text);
                    }
                },
                onDeactivate: function(dtnode) {},
                onLazyRead: function(dtnode){
                    dtnode.appendAjax({
                        url: "../tree.php",
                        data: {key: dtnode.data.key, page: dtnode.data.page}
                    });
                }
            });
        });
    
    <?php
    if(!empty($ips_to_ckeck)) 
    {    
        ?>  
        // If some sensor is running we get its state
        function show_status() 
        {
            $.ajax({
                type: "POST",
                data: "ips=<?php echo implode("#",$ips_to_ckeck);?>",
                url: "get_status.php",
                success: function(html){
                    var lines = html.split("\n");
                    
                    for (var i=0;i<lines.length;i++) 
                    {
                        if(lines[i] != '') 
                        {
                            var data = lines[i].split("|");
                            
                            if (data[4]!="0.00" && data[7]!="0.00") 
                            {
                                $('#ppbar'+data[0]).height(12);
                                $('#ppbar'+data[0]+' .ui-progress').height(10);
                                $('#ppbar'+data[0]+' .ui-progress').animateProgress(data[4]); // packets
                                $('#pPacketsValue'+data[0]).html(Math.round(data[4]));
                                
                                $('#tpbar'+data[0]).height(12); // time
                                $('#tpbar'+data[0]+' .ui-progress').height(10);
                                $('#tpbar'+data[0]+' .ui-progress').animateProgress(data[7]);
                                $('#pTimeValue'+data[0]).html(Math.round(data[7]));
                                
                                $('#scan_status'+data[0]).show();         // show scan status
                                setTimeout (show_status,5000);
                            } 
                            else 
                            {
                                $('#ppbar'+data[0]+' .ui-progress').animateProgress(100);
                                $('#tpbar'+data[0]+' .ui-progress').animateProgress(100);
                                document.location.reload()
                            }
                        }
                    }
                    
                }
            });
        }
        <?php
    }
    ?>
    
    function stop_capture(ip) 
    {
        $.ajax({
            type: "POST",
            data: "sensor_ip="+ip+"&op=stop",
            url: "manage_scans.php",
            success: function(html){
                document.location.reload();
            }
        });
    }

    function confirmDelete(data)
    {
        var ans = confirm("<?php echo Util::js_entities(_("Are you sure you want to delete this capture?"))?>");
       
        if (ans) 
        {
            $.ajax({
                type: "POST",
                data: data,
                url: "manage_scans.php",
                success: function(html){
                    document.location.reload();
                }
            });
        }
    }
    </script>
</head>
<body>

<div id='av_info'></div>

<?php
if(count($sensors_status) == 0)
{
    ?>
    <table class='t_sensors' cellspacing="0" cellpadding="3">
        <tr><th><?php echo _('Sensors Status')?></th></tr>
        <tr>
            <td class="nobborder" style="text-align:center"><?php echo _('No available sensors')?></td>
        </tr>
    </table>
    <?php
}
else 
{
    ?>
    <div class='traffic_title'><?php echo _('Sensors Status')?></div>
    <table class='t_sensors'>
        <tr>
            <th width="30%"><?php echo _('Sensor Name');?></th>
            <th width="30%"><?php echo _('Sensor IP')?></th>
            <th width="20%"><?php echo _('Total Captures')?></th>
            <th width="20%"><?php echo _('Status')?></th>
        </tr>
            <?php
                $i=1;
                foreach($sensors_status as $sensor_ip => $sensor_info) 
                {
                    // check permissions
                    $users = array();
                    
                    $users_in_perms = Session::get_users_to_assign($dbconn);
                    foreach ($users_in_perms as $user) 
                    {
                        $users[$user->get_login()] = $user->get_login();
                    }
                    
                    $iper = 0;
                    if (is_array($scans_by_sensor[$sensor_ip]))
                    {
                        foreach($scans_by_sensor[$sensor_ip] as $data) 
                        {
                            $scan_info_to_check = explode('_', $data);
                            
                            if($users[$scan_info_to_check[1]] == '') 
                            {
                               unset($scans_by_sensor[$sensor_ip][$iper]);
                            }

                            $iper++;
                        }
                    }
                    
                    // *************
                    // Some IDs to make Selenium tests
                    //
                    $sname_seid   = "sensor_name$i";
                    $sip_seid     = "sensor_ip$i";
                    $tcap_seid    = "total_captures$i";
                    $sstatus_seid = "sensor_status$i";

                    $tdborder     = "";

                    if(count($scans_by_sensor[$sensor_ip]) > 0 || count($sensors_status) == $i)
                    {
                        $tdborder = "nobborder";
                    }
                    
                    $i++;
                    
                    ?>
                    <tr><td style="text-align:center;" id="<?php echo $sname_seid ?>" class="<?php echo $tdborder ?>">
                        <?php echo (Av_sensor::get_name_by_ip($dbconn, $sensor_ip) !='' ) ? Av_sensor::get_name_by_ip($dbconn, $sensor_ip) : _("Not found"); ?></td>
                        <td style="text-align:center;" id="<?php echo $sip_seid ?>" class="<?php echo $tdborder ?>"><?php echo $sensor_ip; ?></td>
                        <td style="text-align:center;" id="<?php echo $tcap_seid ?>" class="<?php echo $tdborder ?>"><?php echo count($scans_by_sensor[$sensor_ip])?></td>
                        <td style="text-align:center;" id="<?php echo $sstatus_seid ?>" class="<?php echo $tdborder ?>"><span class="sensor_status_<?php echo $sensor_ip; ?>"><?php echo $states[$sensor_info[0]] ?></span></td>
                    </tr>
                    <tr id="scan_status<?php echo md5($sensor_ip); ?>" style="display:none">
                        <td colspan="4" class="nobborder" style="text-align:center;">
                            <table align="center" class="transparent">
                                <tr>
                                    <th>
                                    <strong><?php echo _("Current capture");?></strong><br>
                                    <input type='button' class="small" style="margin-top:7px" onclick='this.value="<?php echo _("Stopping...")?>";stop_capture("<?php echo $sensor_ip;?>");' value='<?php echo _("Stop now")?>'/>
                                    </th>
                                    <td width="10" class="nobborder">&nbsp;</td> <!-- space between tds -->
                                    <td class="nobborder" style="text-align:left;width:300px">
                                        <div>
                                            <div id="ppbar<?php echo md5($sensor_ip); ?>" class="ui-progress-bar ui-container" style="width:100px;float:left;margin-bottom:15px;">
                                                <div class="ui-progress stripes"></div>
                                            </div>
                                            <div style="float:left;padding-left:5px;">
                                            <?php echo _("Packets");?> <span id="pPacketsValue<?php echo md5($sensor_ip); ?>">0</span>%
                                            </div>
                                        </div>
                                        
                                        <div style="clear:both">
                                            <div id="tpbar<?php echo md5($sensor_ip); ?>" class="ui-progress-bar ui-container" style="width:100px;float:left;">
                                                <div class="ui-progress stripes"></div>
                                            </div>
                                            <div style="float:left;padding-left:5px;">
                                            <?php echo _("Time");?> <span id="pTimeValue<?php echo md5($sensor_ip); ?>">0</span>%
                                            </div>
                                        </div>
                                    </td>
                            </table>
                        </td>
                    </tr>
                    <?php 
                    if(is_array($scans_by_sensor[$sensor_ip]) && count($scans_by_sensor[$sensor_ip]) > 0)
                    { 
                        ?>
                        <tr><td colspan="4" class="nobborder">
                            <table class='table_list ninety_perc'>
                                <tr>
                                    <th width="30%"><?php echo gettext("Capture Start Time"); ?></th>
                                    <th width="20%"><?php echo gettext("Duration (seconds)"); ?></th>
                                    <th width="30%"><?php echo gettext("User"); ?></th>
                                    <th width="20%"><?php echo gettext("Action"); ?></th>
                                </tr>

                                <?php
                                $j = 1;
                                $exist_tshark        = file_exists("/usr/bin/tshark");
                                $link_tshark_title   = ($exist_tshark) ? _('View Payload'): _('Tshark app not exist');
                                $link_tshark_disable = ($exist_tshark) ? '':"class='disabled'";
                                foreach($scans_by_sensor[$sensor_ip] as $data) 
                                {
                                    $scclass = '';
                                    
                                    if(count($scans_by_sensor[$sensor_ip]) == $j)
                                    {
                                        $scclass = "class=\"nobborder\"";
                                    }
                                    
                                    $j++;
                                    ?>
                                    <tr>
                                        <td style="text-align:center" <?php echo $scclass;?>><?php
                                            $scan_info = explode('_', $data);
                                            
                                            $sensor_ip_from_pcap = $scan_info[4];
                                            $sensor_ip_from_pcap = str_replace(".pcap", "", $sensor_ip_from_pcap);
                                            
                                            echo date("Y-m-d H:i:s", $scan_info[2] );
                                          ?>
                                        </td>
                                        <td style="text-align:center" <?php echo $scclass;?>><?php echo $scan_info[3]?></td>
                                        <td style="text-align:center" <?php echo $scclass;?>><?php echo $scan_info[1]?></td>
                                        <td style="text-align:center" <?php echo $scclass;?>>
                                            <a class='link_no_dec' href="javascript:;" onclick="return confirmDelete('op=delete&scan_name=<?php echo $data?>&sensor_ip=<?php echo $sensor_ip_from_pcap?>');">
                                                <img align="absmiddle" src="../vulnmeter/images/delete.gif" title="<?php echo gettext("Delete")?>" alt="<?php echo gettext("Delete")?>" border="0"/>
                                            </a>
                                            <a class='link_no_dec' href="download.php?scan_name=<?php echo $data?>&sensor_ip=<?php echo $sensor_ip_from_pcap?>">
                                                <img align="absmiddle" src="../pixmaps/theme/mac.png" title="<?php echo gettext("Download")?>" alt="<?php echo gettext("Download")?>" border="0"/>
                                            </a>
                                            <?php
                                                $link_tshark = ($exist_tshark) ? "href='tshark/viewcapture.php?scan_name=".$data."&sensor_ip=".$sensor_ip_from_pcap."'" : "";
                                            ?>
                                            <a class='link_no_dec' <?php echo($link_tshark); ?> target="AnalisysConsole">
                                                <img align="absmiddle" <?php echo($link_tshark_disable); ?> src="../pixmaps/wireshark.png" title="<?php echo $link_tshark_title ?>" alt="<?php echo $link_tshark_title ?>" border="0">
                                            </a>
                                        </td>
                                     </tr>
                                     <?php
                                }
                                ?>
                            </table>
                            </td></tr>
                            <tr><td colspan="4" height="20"> &nbsp;</td></tr>
                        <?php
                    }
                }
            ?>
    </table>
    <?php
}
?>

<br />

<table width="100%" align="center" class="transparent" cellspacing="0" cellpadding="0">
    <tr>
        <td style="text-align:left" class="nobborder">
            <a href="javascript:;" onclick="$('.tscans').toggle();$('#message_show').toggle();$('#message_hide').toggle();" colspan="2"><img src="../pixmaps/arrow_green.gif" align="absmiddle" border="0">
                <span id="message_show" <?php echo ((count($info_error)>0 || $soptions==1 )? "style=\"display:none\"":"")?>><?php echo _('Show Capture Options')?></span>
                <span id="message_hide"<?php echo ((count($info_error)>0 || $soptions==1 )? "":"style=\"display:none\"")?>><?php echo _('Hide Capture Options')?></span>
            </a>
        </td>
    </tr>
</table>

<form method="post" action="manage_scans.php">
    <br />
            
    <table align="center" class="tscans" <?php echo (count($info_error)>0 || $soptions==1 )? "":"style=\"display:none;\""?>>
        <tr>
            <th class="headerpr"><?php echo gettext("Capture Options") ?></th>
        </tr>
        <tr>
           <td class="nobborder">
                <table align="center" class="noborder">
                    <tr><td colspan="9" class="nobborder" height="15">&nbsp;</td></tr>
                    <tr>
                        <th width="30"> <?php echo _("Timeout");?> </th>
                        <td class="noborder" style="padding-left:10px;" width="110">
                            <?php
                            if( !$unlimited_traffic_capture ) {
                            ?>
                                <select name="timeout" style="width:50px;">
                                  <option <?php echo (($timeout=="10") ? "selected=\"selected\"":"") ?>>10</option>
                                  <option <?php echo (($timeout=="20") ? "selected=\"selected\"":"") ?>>20</option>
                                  <option <?php echo (($timeout=="30") ? "selected=\"selected\"":"") ?>>30</option>
                                  <option <?php echo (($timeout=="60") ? "selected=\"selected\"":"") ?>>60</option>
                                  <option <?php echo (($timeout=="90") ? "selected=\"selected\"":"") ?>>90</option>
                                  <option <?php echo (($timeout=="120") ? "selected=\"selected\"":"") ?>>120</option>
                                  <option <?php echo (($timeout=="180") ? "selected=\"selected\"":"") ?>>180</option>
                                </select> <?php echo _("seconds");
                            }
                            else
                            {
                                ?>
                                <input type="text" size="10" name="timeout" value="<?php echo ( (intval($timeout)!=0) ? intval($timeout) : "10" ); ?>"/> <?php echo _("seconds");?>
                                <?php
                            }
                            ?>
                        </td>
                        <td width="50" class="nobborder">&nbsp;
                        </td>
                        <th>
                            <?php echo _("Cap size");?>
                        </th>
                        <?php
                        if(!$unlimited_traffic_capture) 
                        {
                            ?>
                            <td class="nobborder" style="padding-left:10px;">
                                <div id="cap_size" style="width:150px;margin-right:6px;"></div>
                            </td>
                            <td class="nobborder" width="80">
                                <span id="cap_size_value" style="color:#000000; font-weight:bold;"><?php echo ((intval($cap_size) != 0) ? intval($cap_size) : "4000"); ?></span><?php echo " "._("packets");?>
                                <input type="hidden" id="cap_size_input" name="cap_size" value="<?php echo ((intval($cap_size) != 0) ? intval($cap_size) : "4000"); ?>" />
                            </td>
                            <?php
                        }
                        else 
                        {
                            ?>
                            <td class="nobborder" style="padding-left:10px;">
                                <input type="text" size="10" id="cap_size_input" name="cap_size" value="<?php echo ((intval($cap_size)!=0) ? intval($cap_size) : "4000"); ?>" /><?php echo " "._("packets");?>
                            </td>
                            <?php 
                        }
                        ?>
                        <td width="50" class="nobborder">&nbsp;
                        </td>
                        <script type='text/javascript'>
                            $("#cap_size").slider({
                                animate: true,
                                range: "min",
                                value: <?php echo ( (intval($cap_size)!=0) ? intval($cap_size) : "4000" ); ?>,
                                min:   100,
                                max:   8000,
                                step:  100,
                                slide: function(event, ui) {
                                    $("#cap_size_value").html(ui.value);
                                    $("#cap_size_input").val(ui.value);
                                }
                            });
                        </script>
                        <th>
                            <?php echo _("Raw filter");?>
                        </th>
                        <td class="nobborder" style="padding-left:10px;">
                            <input type="text" name="raw_filter" value="<?php echo (($raw_filter!="") ? Util::htmlentities($raw_filter) : ""); ?>" />
                        </td>
                        <tr><td colspan="9" class="nobborder" height="15">&nbsp;</td></tr>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td class="nobborder" width="100%">
                <div class='traffic_title'><?php echo _("Settings")?></div>
                <table class='t_settings'>
                    <tr>
                        <th><?php echo _("Sensor");?></th>
                        <th><?php echo _("Source");?></th>
                        <th><?php echo _("Destination");?></th>
                    </tr>
                    <tr>
                        <td valign="top" style="padding:4px;text-align:center" class="nobborder"  width="30%">
                            <table width="100%" class="transparent">
                                <tr>
                                    <td class="nobborder" style="text-align:center;">
                                    <?php
                                    $sensor_list = $scan->get_sensors();
                                    

                                    
                                    /*
                                    only for debugging 
                                        $sensor_list = array();      //clean array
                                        $sensor_list["5.5.5.5"]      = array("5_5_5_5","");
                                        $sensor_list["192.168.10.4"] = array("no existe","eth0");
                                        $sensor_list["192.168.10.1"] = array("juanma","eth0,eth1" );
                                    */
                                    
                                    foreach ($sensor_list as $s_ip => $s_data)
                                    { 
                                        //Check permissions
                                        if(!Session::sensorAllowed($s_ip))
                                        {
                                            unset($sensor_list[$s_ip]);
                                        }
                                    }
                                    
                                    if(count($sensor_list) == 0)
                                    {
                                        echo _('No available sensors');
                                    }
                                    else 
                                    {
                                        ?>
                                        <select id="sensors" name="sensor" style="width:90%">
                                            <?php
                                            foreach ($sensor_list as $s_ip => $s_data) 
                                            {
                                                if(is_array($s_data['i_faces']) && !empty($s_data['i_faces']))
                                                {  
                                                    foreach($s_data['i_faces'] as $i_face)
                                                    {
                                                        $selected = "";
                                                        
                                                        if($sensor_ip == $s_ip && $i_face == $sensor_interface)
                                                        {
                                                            $selected = "selected=\"selected\"";
                                                        }

                                                        $data_to_select  = $s_ip . ' (' . $s_data['name'] . ' / ' . $i_face . ')';
                                                        $value_to_select = $s_ip . '-' . $i_face;

                                                        ?>
                                                        <option value="<?php echo $value_to_select;?>" <?php echo $selected;?>><?php echo $data_to_select;?></option>
                                                        <?php
                                                    }
                                                }
                                                
                                            }
                                            ?>
                                        </select>
                                        <?php
                                    }
                                    ?>
                                    </td>
                                </tr>
                            </table>
                        </td>
                        
                        <td width="35%" valign="top" style="padding-top:4px;" class="nobborder">
                            <table width="100%" class="transparent">
                                <tr><td class="nobborder" style="text-align:center"><textarea rows="8" cols="32" id="src" name="src"><?php echo Util::htmlentities($src)?></textarea></td></tr>
                                <tr><td class="nobborder"><div id="stree" style="width:300px;margin:auto"></div></td></tr>
                            </table>
                        </td>
                        
                        <td width="35%" valign="top" style="padding-top:4px;" class="nobborder">
                            <table width="100%" class="transparent">
                                <tr><td class="nobborder" style="text-align:center"><textarea rows="8" cols="32" id="dst" name="dst"><?php echo Util::htmlentities($dst)?></textarea></td></tr>
                                <tr><td class="nobborder"><div id="dtree" style="width:300px;margin:auto"></div></td></tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="text-align:right;padding:10px" class="nobborder">
                <input type="submit" name="command" value="<?php echo _("Launch capture");?>" />
            </td>
        </tr>
    </table>
    <br/><br/>

</form>

</body>
</html>
<?php
$db->close();
?>
