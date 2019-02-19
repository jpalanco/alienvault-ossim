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

Session::logcheck("analysis-menu", "EventsRT");


require_once '../widget_common.php';


function get_idm_data($conn, $id)
{
    $idm_data = array('','','','',0);

    $query = "SELECT rep_prio_src, rep_prio_dst, rep_act_src, rep_act_dst FROM alienvault_siem.reputation_data WHERE event_id = UNHEX(?)";

    $params = array($id);

    $rs = $conn->Execute($query, $params);

    if ($rs)
    {
       $idm_data[0] = $rs->fields['rep_prio_src'];
       $idm_data[1] = $rs->fields['rep_act_src'];
       $idm_data[2] = $rs->fields['rep_prio_dst'];
       $idm_data[3] = $rs->fields['rep_act_dst'];
    }

    $query = "SELECT count(distinct pulse_id) as pulses FROM alienvault_siem.otx_data WHERE event_id = UNHEX(?)";

    $params = array($id);

    $rs = $conn->Execute($query, $params);

    if ($rs)
    {
       $idm_data[4] = intval($rs->fields['pulses']);
    }

    return $idm_data;
}


header('Cache-Control: no-cache');

$db         = new ossim_db();
$conn       = $db->connect();

$geoloc     = new Geolocation('/usr/share/geoip/GeoLiteCity.dat');

//CONFIG
$conf       = $GLOBALS['CONF'];
$acid_table = 'alienvault_siem.acid_event';
$key_index  = ($conf->get_conf('copy_siem_events') == 'no') ? 'force index(IND)' : '';
$from_snort = true;
$max_rows   = 7;
$delay      = (preg_match("/MSIE /", $_SERVER['HTTP_USER_AGENT'])) ? 150 : 800; // do not modify

if (!isset($_SESSION['id']))
{
    $_SESSION['id'] = '0';
}

if (!isset($row_num))
{
    global $row_num;
    $row_num = 0;
}

if (!isset($_SESSION['plugins_to_show']))
{
    $_SESSION['plugins_to_show'] = array();
}
// responder js



//ID of the widget
$id    = GET("id");

//Validation
ossim_valid($id,    OSS_DIGIT, OSS_NULLABLE,    'illegal:' . _("Widget ID"));

if (ossim_error())
{
    die(ossim_error());
}
//End of validation

//Array that contains the widget's general info
$winfo      = array();

//If the ID is empty it means that we are in the wizard previsualization. We get all the info from the GET parameters.
if(!isset($id) || empty($id))
{
    $assets = GET("asset");                 //Assets implicated in the widget
    $speed  = GET("speed");
    $speed  = (empty($speed)) ? 2000 : $speed;
}
else  //If the ID is not empty, we are in the normal case; loading the widget from the dashboard. In this case we get the info from the DB.
{
    //Getting the widget's info from DB
    $winfo  = get_widget_data($conn, $id);      //Check it out in widget_common.php
    $assets = $winfo['asset'];                  //Params of the widget representation, this is: type of chart, legend params, etc.
    $speed  = $winfo['refresh'];
    $speed  = (empty($speed)) ? 2000 : $speed*1000;
}


//Validation

ossim_valid($assets ,   OSS_HEX,OSS_SCORE,OSS_ALPHA,OSS_USER,   'illegal:' . _('Asset/User/Entity'));
ossim_valid($speed ,    OSS_DIGIT,                              'illegal:' . _('Refresh period'));

if (ossim_error())
{
    die(ossim_error());
}
//End of validation.


$assets_filters = array();
$assets_filters = get_asset_filters($conn, $assets);

//session_write_close();

if (GET('modo') == "responder")
{
    // Timezone correction
    $tz  = Util::get_timezone();
    $tzc = Util::get_tzc($tz);

    //Plugins
    $plugins = "";
    $plgs    = explode(",", GET('plugins'));
    foreach ($plgs as $encoded)
    {
        $p_id = base64_decode($encoded);
            ossim_valid($p_id, OSS_DIGIT, 'illegal:' . _('Plugin ID'));

            if (!ossim_error())
            {
                $plugins .= ','.$p_id;
            }
    }

    $plugins = preg_replace("/^,/","",$plugins);

    //Risk
    $risk    = 0;

    //Filters
    $src_ip   = bin2hex(inet_pton(long2ip(GET('f_src_ip'))));
    $dst_ip   = bin2hex(inet_pton(long2ip(GET('f_dst_ip'))));
    $src_port = intval(GET('f_src_port'));
    $dst_port = intval(GET('f_dst_port'));
    $protocol = intval(GET('f_protocol'));

    if ($from_snort)
    {
        session_write_close();

        include_once 'sensor_filter.php';

        $query_where = Security_report::make_where($conn, '', '', array(), $assets_filters);
        $query_where = preg_replace('/AND \(timestamp.*/', '', $query_where);

        // Read from acid_event
        $where .= ($plugins != '')                          ? " AND plugin.id in ($plugins)" : "";
        $where .= (GET('f_src_ip') != '' && $src_ip != '' ) ? " AND $acid_table.ip_src=unhex('$src_ip')" : '';
        $where .= (GET('f_dst_ip') != '' && $dst_ip != '' ) ? " AND $acid_table.ip_dst=unhex('$dst_ip')" : '';
        $where .= ($src_port != 0)                          ? " AND $acid_table.layer4_sport=$src_port"  : '';
        $where .= ($dst_port != 0)                          ? " AND $acid_table.layer4_dport=$dst_port"  : '';
        $where .= ($protocol != 0)                          ? " AND $acid_table.ip_proto=$protocol"      : '';

        // Limit in second select when sensor is specified (OJO)
        $key_index  = ($plugins != '') ? '' : str_replace("IND", "timestamp", $key_index);

        $sql = "select $acid_table.plugin_id, $acid_table.plugin_sid,
        TO_SECONDS(timestamp)-62167219200+TO_SECONDS(UTC_TIMESTAMP())-TO_SECONDS(NOW()) as id,
        hex($acid_table.id) as event_id,
        plugin_sid.name as plugin_sid_name,
        ip_src, ip_dst,
        HEX(src_host) AS src_host, HEX(dst_host) AS dst_host, HEX(src_net) AS src_net, HEX(dst_net) AS dst_net,
        HEX($acid_table.ctx) AS ctx,
        convert_tz(timestamp,'+00:00','$tzc') as timestamp1,
        ossim_risk_a as risk_a, ossim_risk_c as risk_c,
        layer4_sport as src_port, layer4_dport as dst_port,
        ossim_priority as priority, ossim_reliability as reliability,
        ossim_asset_src as asset_src, ossim_asset_dst as asset_dst,
        ip_proto as protocol, device.interface, device.id as device_id
        FROM alienvault_siem.device, $acid_table $key_index LEFT JOIN alienvault.plugin_sid ON plugin_sid.plugin_id=$acid_table.plugin_id AND plugin_sid.sid=$acid_table.plugin_sid LEFT JOIN alienvault.plugin ON plugin.id=$acid_table.plugin_id WHERE device.id = $acid_table.device_id " . $where . " order by timestamp desc limit $max_rows";

        // QUERY DEBUG:

        $rs = $conn->Execute($sql);

        if (!$rs)
        {
            echo "// Query error: $sql\n// " . $conn->ErrorMsg() . "\n";
            return;
        }
    }
    else
    {
        // read from event_tmp
        return;
    }
    $i = 0;
    //echo "// $sql\n";

    while (!$rs->EOF)
    {
        $risk = ($rs->fields["risk_a"] > $rs->fields["risk_c"]) ? $rs->fields["risk_a"] : $rs->fields["risk_c"];
        echo "edata[$i][0] = '" . $rs->fields["id"] . "';\n";
        echo "edata[$i][1] = '" . $rs->fields["timestamp1"] . "';\n";
        echo "edata[$i][2] = '" . str_replace("'", "\'", $rs->fields["plugin_sid_name"]) . "';\n";

        if ($risk > 7)
        {
            $rst="style=\"padding:2px 5px 2px 5px;background-color:red;color:white\"";
        }
        elseif ($risk > 4)
        {
            $rst="style=\"padding:2px 5px 2px 5px;background-color:orange;color:black\"";
        }
        elseif ($risk > 2)
        {
            $rst="style=\"padding:2px 5px 2px 5px;background-color:green;color:white\"";
        }
        else
        {
            $rst="style=\"padding:2px 5px 2px 5px;color:black\"";
        }

        echo "edata[$i][3] = '<span $rst>" . $risk . "</span>';\n";
        echo "var pid = '" . $rs->fields["plugin_id"] . "';\n";
        echo "edata[$i][4]  = pid;\n";
        echo "edata[$i][5]  = '" . $rs->fields["plugin_sid"] . "';\n";
        echo "edata[$i][6]  = devices['ip_" . $rs->fields["device_id"] . "'];\n";

        // Assets
        $src_output = Asset_host::get_extended_name($conn, $geoloc, inet_ntop($rs->fields["ip_src"]), $rs->fields["ctx"], $rs->fields["src_host"], $rs->fields["src_net"]);
        $src_field  = ($src_output['is_internal']) ? $src_output['html_icon'].' <b>'.$src_output['name'].'</b>' : $src_output['html_icon'].' '.$src_output['name'];
        $dst_output = Asset_host::get_extended_name($conn, $geoloc, inet_ntop($rs->fields["ip_dst"]), $rs->fields["ctx"], $rs->fields["dst_host"], $rs->fields["dst_net"]);
        $dst_field  = ($dst_output['is_internal']) ? $dst_output['html_icon'].' <b>'.$dst_output['name'].'</b>' : $dst_output['html_icon'].' '.$dst_output['name'];
        echo "edata[$i][7]  = \"". $src_field ."\";\n";
        echo "edata[$i][8]  = '" . $rs->fields["src_port"] . "';\n";
        echo "edata[$i][9]  = \"". $dst_field . "\";\n";
        echo "edata[$i][10] = '" . $rs->fields["dst_port"] . "';\n";

        // more detail
        echo "edata[$i][11] = '" . $rs->fields["priority"] . "';\n";
        echo "edata[$i][12] = '" . $rs->fields["reliability"] . "';\n";
        echo "edata[$i][13] = '" . $rs->fields["interface"] . "';\n";
        echo "edata[$i][14] = '" . $rs->fields["protocol"] . "';\n";
        echo "edata[$i][15] = '" . $rs->fields["asset_src"] . "';\n";
        echo "edata[$i][16] = '" . $rs->fields["asset_dst"] . "';\n";
        echo "edata[$i][17] = '" . $rs->fields["alarm"] . "';\n";
        echo "edata[$i][18] = '" . $rs->fields["event_id"] . "';\n";

        if (GET('idm') == 1)
        {
            $idm_data = get_idm_data($conn, $rs->fields["event_id"]);
            echo "edata[$i][19] = '".$idm_data[4]."';";
            echo "edata[$i][20] = \"" . Reputation::getreponlyimg($idm_data[0],$idm_data[1]) . "\";\n";
            echo "edata[$i][21] = '"  . Reputation::getrepbgcolor($idm_data[0],2) . "';\n";
            echo "edata[$i][22] = \"" . Reputation::getreponlyimg($idm_data[2],$idm_data[3]) . "\";\n";
            echo "edata[$i][23] = '"  . Reputation::getrepbgcolor($idm_data[2],2) . "';\n";
        }
        else
        {
            echo "edata[$i][19] = '0';\n";
            echo "edata[$i][20] = '';\n";
            echo "edata[$i][21] = '';\n";
            echo "edata[$i][22] = '';\n";
            echo "edata[$i][23] = '';\n";
        }

        // Resolve auxiliaries
        echo "edata[$i][24] = devices['name_" . $rs->fields["device_id"] . "'];\n";
        echo "edata[$i][25] = '".(($src_output['name'] != inet_ntop($rs->fields["ip_src"])) ? inet_ntop($rs->fields["ip_src"]) : '')."';\n";
        echo "edata[$i][26] = '".(($dst_output['name'] != inet_ntop($rs->fields["ip_dst"])) ? inet_ntop($rs->fields["ip_dst"]) : '')."';\n";


        $i++;

        $rs->MoveNext();

    }

    // fill rest
    while ($i < $max_rows)
    {
        for ($k = 0; $k <= 25; $k++)
        {
            $val = ($k==19) ? '0' : '';
            echo "edata[$i][$k] = '$val';\n";
        }

        $i++;
    }

    echo "draw_edata();\n";
}
else
{
    ?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title><?php echo _("Event Tail Viewer")?></title>

        <?php
            //CSS Files
            $_files = array(
                array('src' => 'av_common.css?only_common=1',       'def_path' => TRUE),
                array('src' => 'common/structures.css',             'def_path' => TRUE),
                array('src' => 'jquery.autocomplete.css',           'def_path' => TRUE),
                array('src' => 'jquery-ui.css',                     'def_path' => TRUE)
            );

            Util::print_include_files($_files, 'css');

            //JS Files
            $_files = array(
                array('src' => 'jquery.min.js',                     'def_path' => TRUE),
                array('src' => 'jquery-ui.min.js',                  'def_path' => TRUE),
                array('src' => 'jquery.autocomplete.pack.js',       'def_path' => TRUE)
            );

            Util::print_include_files($_files, 'js');

        ?>

        <style type="text/css">

            body {
                font-size:11px;
                overflow-y: auto !important;
            }

            a { cursor:pointer;}

            #viewer_container {
                width: 100%;
                position: relative;
                margin: 0px auto 0px auto;
                height: auto;
            }

            #menu_viewer {
                width: 100%;        /* To change width */
                margin: auto;
                padding: 0px 0px 30px 0px;
            }
            #viewer {
                padding-top: 10px;
            }

            #viewer table {
                width: 100%;        /* To change width */
                margin: auto;
                border: none;
            }

            #viewer th {
               font-weight: bold;
            }

            .semiopaque {
                opacity:0.9;
                -moz-opacity:0.9;
                -khtml-opacity:0.9;
                filter:alpha(opacity=90);
                background-color:#B5C3CF
            }

            .little { font-size:8px }

            #message_filter{
                width: 100%;        /* To change width */
                margin: auto;
                padding-top: 10px;
            }

            #filter_panel{
                width: 85%;     /* To change width */
                margin: auto;
                padding-top: 10px;
                display:none;
            }

            #filter_panel table{
                width: 100%;
                margin: auto;
            }

            .sep {
                height: 5px;
                border: none;
            }

            #filter_panel ._label{
                font-weight: bold;
                width: 125px;
                border: none;
                padding-left: 10px;
                text-align: left;
                height: 25px;
                vertical-align: middle;
            }

            #filter_panel .data{
                border: none;
                padding-left: 5px;
                text-align: left;
                height: 25px;
                vertical-align: middle;
                white-space: nowrap;
            }

            #filter_panel input[type='text']{
                width: 100px;
                height: 17px;
            }

            #filter_chk{
                border: none;
                margin: 0px !important;
                padding: 0px;
            }

            #filter_chk ._label{
                padding-left: 7px !important;
            }

            #cont_tplugin { display: none;}

            #table_plugin {
                width:98% !important;
                height: 15px
                padding-left: 10px;
                text-align: left;
                border: none;
            }

            #numeroDiv{
                position:absolute;
                z-index:999;
                left:0px;
                top:0px;
                visibility:hidden;
                display:none;
            }

            img.otx {
                width: 22px;
                height: 22px;
            }

        </style>

        <script type='text/javascript'>

            var ajaxObj   = null;
            var pause     = false;
            var url       = '<?php echo $SCRIPT_NAME ?>?modo=responder&speed=<?php echo $speed ?>&asset=<?php echo $assets ?>';

            var idr = null

            var speed      = <?php echo $speed ?>;
            var fadescount = 0;
            var mutex      = false;

            function GB_onhide(url, params)
            {
                if (typeof(params) == 'object' && typeof params['url_detail'] != 'undefined')
                {
                    if (typeof(parent.show_overlay_spinner)=='function') parent.show_overlay_spinner(true);

                    document.location.href = params['url_detail'];

                    return false
                }
            }

            function toogle_filters(){
                if($('#filter_panel').css('display') == 'none'){
                    $('#filter_panel').fadeIn(700);
                    $('#message_filter').find('a').html('<?php echo _("Hide Filters") ?>' );
                } else {
                    $('#filter_panel').fadeOut(700);
                    $('#message_filter').find('a').html( '<?php echo _("Show Filters") ?>');
                }
            }

            function ticketon(i, pagex, pagey) {

                if (document.getElementById)
                {
                    $('#bcontrol').attr('disabled', 'disabled');

                    pause = true;

                    if ( $('#footer').text() != '<?php echo _("Stopped")?>.' )
                        $('#footer').html('<?php echo _("Paused") ?>.');

                    // Generating detail info
                    var txt1 = '<table border="0" cellpadding="8" cellspacing="0" class="semiopaque">'
                                + '<tr><td class="nobborder" style="line-height:18px" nowrap="nowrap">'
                                         + 'Date: <b>' + edata[i][1] + '</b><br>'
                                         + 'Event: <b>' + edata[i][2] + '</b><br>'
                                         + 'Risk: <b>' + edata[i][3] + '</b><br>'

                    var plugin = (plugins['id_'+edata[i][4]] != undefined) ? plugins['id_'+edata[i][4]] : edata[i][4];
                    txt1 = txt1 + 'Plugin: <b>' + plugin + '</b>' + ',&nbsp; Plugin_sid: <b>' + edata[i][5] + '</b><br>'

                    var sensor = edata[i][24];

                    txt1 = txt1 + 'Sensor: <b>' + sensor + '</b> <i>[' + edata[i][6] + ']</i><br>'

                    var host = edata[i][7];

                    if (host!='N/A' && edata[i][8]!="0")
                    {
                        host = host + ":" + edata[i][8];
                    }

                    if (edata[i][25] != '')
                    {
                        host += ' ['+edata[i][25]+']';
                    }

                    txt1 = txt1 + 'Source IP: <b>' + host + '</b><br>'

                    host = edata[i][9];

                    if (host!='N/A' && edata[i][10] != "0")
                    {
                        host = host + ":" + edata[i][10];
                    }

                    if (edata[i][26] != '')
                    {
                        host += ' ['+edata[i][26]+']';
                    }

                    txt1  = txt1 + 'Dest IP: <b>' + host + '</b><br>';
                    txt1  = txt1 + 'Priority: <b>' + edata[i][11] + '</b>' + ',&nbsp; Reliability: <b>' + edata[i][12] + '</b><br>'
                    proto = (protocols['proto_'+edata[i][14]] != undefined) ? protocols['proto_'+edata[i][14]] : edata[i][14]
                    txt1  = txt1 + 'Interface: <b>' + edata[i][13] + '</b>' + ',&nbsp; Protocol: <b>' + proto + '</b><br>'
                    txt1  = txt1 + 'Asset Src: <b>'+ edata[i][15] + '</b>' + ',&nbsp; Asset Dst: <b>' + edata[i][16] + '</b><br>'
                    if (edata[i][19] != '0')
                    {
                        txt1  = txt1 + 'OTX: <b>'+ edata[i][19] + '</b> Pulses matched<br>'
                    }
                    else
                    {
                        txt1  = txt1 + 'OTX: <b>No Pulses found</b><br>'
                    }


                    if (edata[i][17]!="") txt1 = txt1 + 'Alarm: <b>' + edata[i][17] + '</b><br>'

                    $('#numeroDiv').html(txt1);

                    $('#numeroDiv').css('left', pagex);
                    $('#numeroDiv').css('top',  pagey);

                    $('#numeroDiv').show();
                    $('#numeroDiv').css('visibility', 'visible');

                }
            }

            function ticketoff()
            {
                $('#bcontrol').removeAttr('disabled');

                if ( $('#numeroDiv').length >= 1 )
                {
                    $('#numeroDiv').css('visibility', 'hidden');
                    $('#numeroDiv').hide();
                    $('#numeroDiv').html('');

                    if ($('#bcontrol').val() == '<?php echo _("Resume") ?>') {
                        $('#footer').html('<?php echo _("Stopped.")?>');
                    }
                    else {
                        $('#footer').html('<?php echo _("Continue... Awaiting next refresh")?>');
                    }

                    pause = false;
                }

            }

            // Combo filter functions
            function newcheckbox (elName,val) {
                var el = document.createElement('input');
                el.type = 'checkbox';
                el.name = elName;
                el.id = elName;
                el.value = val;
                el.className = 'little'
                el.addEventListener("click", reload, true);
                return el;
            }

            function addtocombofilter (text,value) {
                var fo=document.getElementById('filter')
                if (notfound(fo,value)) {
                    fo.appendChild(newcheckbox(text,value))
                    fo.appendChild(document.createTextNode(text))
                    fo.appendChild(document.createElement('br'))
                }
            }

            function notfound (fo,value) {
                var inputs = fo.getElementsByTagName("input");
                for (var i=0; i<inputs.length; i++)
                    if (inputs[i].getAttribute('type')=='checkbox') {
                        if (inputs[i]["value"]==value) {
                            return false
                        }
                    }
                return true
            }

            function getdatafromcombo(h) {
                var value = '';
                var myselect=document.getElementById(h)
                for (var i=0; i<myselect.options.length; i++) {
                        if (myselect.options[i].selected==true) {
                                value = value + ((value=='') ? '' : ',') + myselect.options[i].value
                        }
                }
                return value;
            }


            function getdatafromcheckbox() {
                var inp_chk = '';

                $('#table_plugin input[type="checkbox"]:checked').each(function(index) {
                    inp_chk += ( inp_chk == '' ) ? $(this).val() : ", "+$(this).val();
                });

                return inp_chk;
            }

            function ip2long (IP) {
                var i = 0;
                IP = IP.match(/^([1-9]\d*|0[0-7]*|0x[\da-f]+)(?:\.([1-9]\d*|0[0-7]*|0x[\da-f]+))?(?:\.([1-9]\d*|0[0-7]*|0x[\da-f]+))?(?:\.([1-9]\d*|0[0-7]*|0x[\da-f]+))?$/i); // Verify IP format.
                if (!IP) {
                    return false; // Invalid format.
                }
                IP[0] = 0;
                for (i = 1; i < 5; i += 1) {
                    IP[0] += !! ((IP[i] || '').length);
                    IP[i] = parseInt(IP[i]) || 0;
                }
                IP.push(256, 256, 256, 256);
                // Recalculate overflow of last component supplied to make up for missing components.
                IP[4 + IP[0]] *= Math.pow(256, 4 - IP[0]);
                if (IP[1] >= IP[5] || IP[2] >= IP[6] || IP[3] >= IP[7] || IP[4] >= IP[8]) {
                    return false;
                }
                return IP[1] * (IP[0] === 1 || 16777216) + IP[2] * (IP[0] <= 2 || 65536) + IP[3] * (IP[0] <= 3 || 256) + IP[4] * 1;
            }

            // Hosts autocomplete
            <?php
            $hosts = array();

            try
            {
                $_hosts_data = Asset_host::get_basic_list($conn);
                $hosts       = $_hosts_data[1];
            }
            catch(Exception $e)
            {
                $hosts   = array();
            }

            foreach ($hosts as $host)
            {
                $_ip       = $host['ips'];
                $_hostname = $host['name'];

                if (Session::hostAllowed($conn, $_ip))
                {
                    //Load available hosts (Autocompleted)
                    if ($_hostname != $_ip)
                    {
                        $h_list .= '{ txt:"'.$_hostname.' [Host:'.$_ip.']", id: "'.Asset_host_ips::ip2ulong($_ip).'" },';
                    }
                    else
                    {
                        $h_list .= '{ txt:"'.$_ip.'", id: "'.Asset_host_ips::ip2ulong($_ip).'" },';
                    }
                }
            }

            // Protocol list

            if ($protocol_list = Protocol::get_list())
            {
                echo "var protocols = new Array(" . count($protocol_list) . ")\n";

                foreach($protocol_list as $proto)
                {
                    //$_SESSION[$id] = $plugin->get_name();
                    echo "protocols['proto_" . $proto['id'] . "'] = '" . $proto['name'] . "'\n";

                    //Load available protocols (Autocompleted)
                    $p_list .= '{ txt: "Protocol:'.$proto['name'].'", id: "'.$proto['id'].'" },';
                }
            }

            //Port list (Autocompleted)

            if ($port_list = Port::get_list($conn," AND protocol_name='tcp'") )
            {
                foreach($port_list as $port)
                    $prt_list .= '{ txt:"'.$port->get_port_number()." - ".$port->get_service().'", id: "'.$port->get_port_number().'" },';
            }


            // Plugin list
            $sids = array();
            if ($plugin_list = Plugin::get_list($conn, ""))
            {
                echo "var plugins = new Array(" . count($plugin_list) . ")\n";
                foreach($plugin_list as $plugin)
                {
                    $sids[$plugin->get_name()] = $plugin->get_id();
                    //$_SESSION[$id] = $plugin->get_name();
                    echo "plugins['id_" . $plugin->get_id() . "'] = '" . $plugin->get_name() . "';\n";
                    echo "plugins['id_" . $plugin->get_name() . "'] = '" . $plugin->get_name() . "';\n";
                }
            }

            // Device list
            $devices = array();

            $query = "SELECT name,inet6_ntoa(ip) as ip,device.id FROM sensor,alienvault_siem.device WHERE device.sensor_id=sensor.id";

            $rs = $conn->Execute($query);

            while (!$rs->EOF)
            {
                $devices[] = $rs->fields;
                $rs->MoveNext();
            }

            if (!empty($devices))
            {
                echo "var devices = new Array(" . count($devices) . ")\n";
                echo "devices['name_0'] = 'N/A';\n";
                echo "devices['ip_0'] = 'N/A';\n";

                foreach ($devices as $device)
                {
                    echo "devices['name_" . $device['id'] . "'] = '" . $device['name'] . "';\n";
                    echo "devices['ip_" . $device['id'] . "'] = '" . $device['ip'] . "';\n";
                }

            }
            ?>


            function create_script(url) {
                // make script element

                var ajaxObject     = document.createElement('script');
                ajaxObject.src     = url;
                ajaxObject.type    = "text/javascript";
                ajaxObject.charset = "utf-8";
                try {
                    return ajaxObject;
                } finally {
                    ajaxObject = null;
                }
            }

            function refresh() {
                // ajax responder
                if ( pause == false && mutex == false )
                {
                    mutex = true;
                    $('#footer').html('<?php echo _("Refreshing") ?>...')
                    // load extra parameters from select filter
                    var idm    = ( $('#idm:checked').length > 0 ) ? 1 : 0;
                    var urlr = url + "&idm=" + idm + "&" + $('#form_filters').serialize();
                    var idf  = getdatafromcheckbox();

                    if ( idf != '')
                        urlr = urlr + '&plugins=' + idf

                    $.ajax({
                        type: "GET",
                        url: urlr,
                        success: function(msg){
                            eval(msg);
                            mutex = false;
                        },
                        error: function(msg){
                            mutex = false;
                        }
                    });
                }
            }

            var edata = new Array(<?php echo $max_rows ?>);
            var eprev = new Array(<?php echo $max_rows ?>);
            var efade = new Array(<?php echo $max_rows ?>);


            <?php
            for ($i = 0; $i < $max_rows; $i++)
            {
                ?>
                edata[<?php echo $i?>] = new Array(24);
                eprev[<?php echo $i?>] = 0;
                efade[<?php echo $i?>] = 0;
                <?php
            }

            ?>

            var forensic_url = "<?php echo Menu::get_menu_url('/ossim/forensics/base_qry_alert.php', 'analysis', 'security_events') ?>";
            function draw_edata() {
                if (pause == false)
                {
                    fadescount = 0;

                    for (var i=0; i<<?php echo $max_rows?>; i++)
                    {
                        // Calculate different rows
                        efade[i] = ( eprev[i] == edata[i][0] ) ? 0 : 1;

                        if ( efade[i] == 1 )
                        {
                            $('#row'+i+' td').css({ 'opacity':'0', '-moz-opacity':'0', '-khtml-opacity':'0', 'filter':'alpha(opacity=0)'});
                            fadescount++;
                        }

                        eprev[i] = edata[i][0];


                        // change content
                        $('#date'+i).html(edata[i][1]);
                        var url_aux = forensic_url + "&submit=%230-"+encodeURIComponent(edata[i][18]);

                        urle = "<a class='tooltip' id='link_"+i+"' href=\""+url_aux+"\" target='main' style='text-decoration:underline'>" + edata[i][2] + "</a>"

                        $('#event'+i).html(urle);
                        $('#trevent'+i).html(edata[i][2]);
                        $('#risk'+i).html(edata[i][3]);


                        plugin = (plugins['id_'+edata[i][4]] != undefined) ? plugins['id_'+edata[i][4]] : edata[i][4];
                        $('#plugin_id'+i).html(plugin);

                        <?php
                        if ( !$from_snort )
                            echo "addtocombofilter (plugin,edata[i][4]);\n"; ?>

                        $('#sensor'+i).html(edata[i][24]);

                        var otximg = '';
                        var repinfo = (edata[i][20] != '' || edata[i][22] != '') ? true : false;
                        if (edata[i][19] != '0' && repinfo)
                        {
                            otximg = '<img class="otx" src="/ossim/pixmaps/otxrep_icon.png"/>';
                        } else if (edata[i][19] != '0' && !repinfo)
                        {
                            otximg = '<img class="otx" src="/ossim/pixmaps/otx_icon.png"/>';
                        } else if (edata[i][19] == '0' && repinfo)
                        {
                            otximg = '<img class="otx" src="/ossim/pixmaps/rep_icon.png"/>';
                        } else if (edata[i][1] != '') {
                            otximg = 'N/A';
                        }
                        $('#otx'+i).html(otximg);

                        //Source IP

                        var host = (edata[i][7]=='0.0.0.0') ? 'N/A' : edata[i][7];

                        if (host!='N/A' && edata[i][8]!="0" && edata[i][8]!="")
                            host = host + ":" + edata[i][8];

                        //host = edata[i][20] + host;
                        // Background color must be the odd/even
                        //$('#srcip'+i).css('background-color', edata[i][21]);

                        $('#srcip'+i).html(host);

                        //Destination IP
                        host = (edata[i][9]=='0.0.0.0') ? 'N/A' : edata[i][9];
                        if (edata[i][10]!="0" && edata[i][10]!="") host = host + ":" + edata[i][10];

                        //host = edata[i][22] + host;
                        // Background color must be the odd/even
                        //$('#dstip'+i).css('background-color', edata[i][23]);

                        $('#dstip'+i).html(host);
                    }

                    $('.tooltip').bind('mouseover', function(event) {
                        var id = $(this).attr('id').replace("link_", "")
                        ticketon(id, event.pageX, event.pageY);
                    });

                    $('.tooltip').bind('mouseout', function() {
                        ticketoff();
                    });


                    //Effects
                    for (var i=0;i<<?php echo $max_rows?>;i++)
                    {
                        if (efade[i] == 1)
                            $('#row'+i+' td').fadeTo(1000, 1, function() { });

                        //$('#row'+i+' td').css('border-bottom', 'solid 1px #CBCBCB');
                    }


                    $('#footer').html('<?php echo _("Done") ?>. [<b>' + fadescount + '</b> <?php echo _("new rows") ?>]');

                }
            }


            function play() {
                refresh();

                $('#bcontrol').val('<?php echo Util::html_entities2utf8(_("Pause")) ?>');

                if (idr == null)
                    idr = setInterval("refresh()",speed);
            }

            function stop() {
                 clearInterval(idr);
                 idr = null;
                 $('#bcontrol').val('<?php echo Util::html_entities2utf8(_("Resume")) ?>');
                 $('#footer').html('<?php echo Util::html_entities2utf8(_("Stopped")) ?>.') }

            function reload() { stop(); play() }

            function pausecontinue() {
                if ( idr==null )
                    play();
                else
                    stop();
            }

            function toogle_pfilter(){

                if ( $('#pf_name').hasClass('show') )
                {
                    $('#pf_name').removeClass('show');
                    $('#pf_name').addClass('hide');

                    $('#p_filter').find('span').html("<?php echo _("Show Plugin filter")?>");

                    $("#cont_tplugin").slideUp(600);
                }
                else
                {
                    $('#pf_name').removeClass('hide');
                    $('#pf_name').addClass('show');

                    $('#p_filter').find('span').html("<?php echo _("Hide Plugin filter")?>");

                    $("#cont_tplugin").slideDown(600);
                }
            }


            function clean_filter_data(id)
            {
                var h_id = 'f_'+id;

                if ( $('#'+id).val() == '' )
                    $('#'+h_id).val('');
            }

            $(document).ready(function(){

                // Autocomplete hosts
                var hosts_ac     = [ <?php echo  preg_replace("/,$/", "", $h_list) ?> ];
                var protocols_ac = [ <?php echo  preg_replace("/,$/", "", $p_list) ?> ];
                var ports_ac     = [ <?php echo  preg_replace("/,$/", "", $prt_list) ?>];

                if (hosts_ac.length > 0)
                {
                    $("#src_ip").autocomplete(hosts_ac, {
                        minChars: 0,
                        width: 220,
                        max: 100,
                        matchContains: true,
                        autoFill: false,
                        formatItem: function(row, i, max) {
                            return row.txt;
                        }
                    }).result(function(event, item) { $('#f_src_ip').val(item.id); });

                     $("#dst_ip").autocomplete(hosts_ac, {
                         minChars: 0,
                         width: 220,
                         max: 100,
                         matchContains: true,
                         autoFill: false,
                         formatItem: function(row, i, max) {
                             return row.txt;
                         }
                     }).result(function(event, item) { $('#f_dst_ip').val(item.id); });
                }

                $("#src_ip").change(function() {
                    if ( $('#f_src_ip').val() == '' )
                    {
                        var ip_num = ip2long($("#src_ip").val());
                        if ( ip_num == false )
                        {
                            $('#f_src_ip').val('');
                            $('#src_ip').val('');
                        }
                        else
                            $('#f_src_ip').val(ip_num);
                    }
                });

                $("#dst_ip").change(function() {
                    if ( $('#f_dst_ip').val() == '' )
                    {
                        var ip_num = ip2long($("#dst_ip").val());
                        if ( ip_num == false )
                        {
                            $('#f_dst_ip').val('');
                            $('#dst_ip').val('');
                        }
                        else
                            $('#f_dst_ip').val(ip_num);
                    }
                });

                if ( protocols_ac.length > 0 )
                {
                    $("#protocol").autocomplete(protocols_ac, {
                        minChars: 0,
                        width: 220,
                        max: 100,
                        matchContains: true,
                        autoFill: false,
                        formatItem: function(row, i, max) {
                            return row.txt;
                        }
                    }).result(function(event, item) { $('#f_protocol').val(item.id); });
                }

                $("#protocol").change(function() {
                    if ($('#f_protocol').val()=='') $('#f_protocol').val($("#protocol").val());
                });

                if ( ports_ac.length > 0 )
                {
                    $("#src_port").autocomplete(ports_ac, {
                        minChars: 0,
                        width: 220,
                        matchContains: "word",
                        autoFill: false,
                        formatItem: function(row, i, max) {
                            return row.txt;
                        }
                    }).result(function(event, item) { $('#f_src_port').val(item.id); });

                    $("#dst_port").autocomplete(ports_ac, {
                        minChars: 0,
                        width: 220,
                        matchContains: "word",
                        autoFill: false,
                        formatItem: function(row, i, max) {
                            return row.txt;
                        }
                    }).result(function(event, item) { $('#f_dst_port').val(item.id); });
                }

                $("#src_port").change(function() {
                    if ($('#f_src_port').val()=='')
                        $('#f_src_port').val($("#src_port").val());
                });

                $("#dst_port").change(function() {
                    if ($('#f_dst_port').val()=='')
                        $('#f_dst_port').val($("#dst_port").val());
                });

                $('.inp_filter').bind('blur', function() { clean_filter_data($(this).attr('id')); });

                $('.clean').bind('click', function() {
                    var id = $(this).attr('id').replace("clean_", "");

                    $('#'+id).val('');
                    clean_filter_data(id)
                });

                $('#p_filter').bind('click', function() {
                   toogle_pfilter();
                });


                play();

            });

        </script>
    </head>

    <body>


        <div id='viewer_container'>

            <div id='menu_viewer'>
                <form name="controls" onsubmit="return false">
                    <table class="container" width="100%" align="left">
                        <tr>
                            <td class="nobborder" valign='middle' width='100px'>
                                <input id="bcontrol" type='button' class='small av_b_secondary' onclick="pausecontinue()" value="<?php echo _("Pause");?>"/>
                            </td>

                            <td id="footer" class="nobborder" valign='middle'></td>
                        </tr>
                    </table>
                </form>
            </div>

            <div id='viewer'>
                <table class='table_data'>
                    <thead>
                    <tr>
                        <th width="140"><?php echo _("Date"); ?></th>
                        <th width="290" class='left'><?php echo _("Event Name"); ?></th>
                        <th width="40"><?php echo _("Risk"); ?></th>
                        <!--<th width="150"><?php echo _("Generator"); ?></th>-->
                        <th width="100"><?php echo _("Sensor"); ?></th>
                        <th width="40"><?php echo _("OTX"); ?></th>
                        <th width="140"><?php echo _("Source IP"); ?></th>
                        <th width="140"><?php echo _("Dest IP"); ?></th>
                    </tr>
                    </thead>

                    <tbody id='viewer_tbody'>
                        <?php
                        for ($i = 0; $i < $max_rows; $i++)
                        {
                            ?>
                            <tr id='row<?php echo $i?>'>
                                <td width="140" id="date<?php echo $i?>"></td>
                                <td width="290" id="event<?php echo $i?>" class='left' style="color:blue;"></td>
                                <td width="40"  id="risk<?php echo $i?>"></td>
                                <!--<td width="150" id="plugin_id<?php echo $i?>"></td>-->
                                <td width="100" id="sensor<?php echo $i?>"></td>
                                <td width="40" id="otx<?php echo $i?>" nowrap></td>
                                <td width="140" id="srcip<?php echo $i?>" nowrap></td>
                                <td width="140" id="dstip<?php echo $i?>" nowrap></td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>

            </div>

            <div id='message_filter' style='<?php echo ($_SESSION['_db_show_edit']) ? 'display:none' : '' ?>'>
                <span><img align="absmiddle" border="0" src="/ossim/pixmaps/arrow_green.gif"><b><a href='javascript:;' class='uppercase' onclick='javascript:toogle_filters();'>Show Filters</a></b></span>
            </div>

            <div id='filter_panel'>
                <table>
                    <tr>
                        <th colspan='4'><?php echo _("Filters")?></th>
                    </tr>

                    <tr>
                        <td class='_label'><?php echo _("Source IP")?>:</td>
                        <td class='data'>
                            <input type='text' class='inp_filter' name='src_ip' id='src_ip'/>
                            <span style='margin-left: 3px;'><a class='clean' id='clean_src_ip' title='<?php echo _("Clean filter")?>'><img src='/ossim/pixmaps/delete.gif' align='absmiddle'/></a></span>
                        </td>
                        <td class='_label'><?php echo _("Destination IP")?>:</td>
                        <td class='data'>
                            <input type='text' class='inp_filter' name='dst_ip' id='dst_ip'/>
                            <span style='margin-left: 3px;'><a class='clean' id='clean_dst_ip' title='<?php echo _("Clean filter")?>'><img src='/ossim/pixmaps/delete.gif' align='absmiddle'/></a></span>
                        </td>
                    </tr>

                    <tr>
                        <td class='_label'><?php echo _("Source Port")?>:</td>
                        <td class='data'>
                            <input type='text' class='inp_filter' name='src_port' id='src_port'/>
                            <span style='margin-left: 3px;'><a class='clean' id='clean_src_port' title='<?php echo _("Clean filter")?>'><img src='/ossim/pixmaps/delete.gif' align='absmiddle'/></a></span>
                        </td>
                        <td class='_label'><?php echo _("Destination Port")?>:</td>
                        <td class='data'>
                            <input type='text' class='inp_filter' name='dst_port' id='dst_port'/>
                            <span style='margin-left: 3px;'><a class='clean' id='clean_dst_port' title='<?php echo _("Clean filter")?>'><img src='/ossim/pixmaps/delete.gif' align='absmiddle'/></a></span>
                        </td>
                    </tr>

                    <tr>
                        <td class='_label' valign='top'><?php echo _("Protocol")?>:</td>
                        <td class='data'>
                            <input type='text' class='inp_filter' name='protocol' id='protocol'/>
                            <span style='margin-left: 3px;'><a class='clean' id='clean_protocol' title='<?php echo _("Clean filter")?>'><img src='/ossim/pixmaps/delete.gif' align='absmiddle'/></a></span>
                        </td>
                        <td class='noborder' colspan='2'>
                            <table id='filter_chk'>
                                <tr>
                                    <?php
                                    $Reputation = new Reputation();
                                    if ( $Reputation->existReputation() )
                                    {
                                        ?>
                                        <td class='_label'><span><?php echo _("Include OTX Info");?>:</span></td>
                                        <td class='data' style='width:62px !important;'><input type='checkbox' name='idm' id='idm' checked='checked' value="1"/></td>
                                        <?php
                                    }
                                    ?>
                                    <td></td>
                                </tr>
                            </table>
                        </td>

                    </tr>

                    <?php


                    if ($from_snort)
                    {
                        ksort($sids);
                        $cont      = 0;
                        $sids_cols = 3;
                        $num_sids  = count($sids);
                        $sids_rows = ceil($num_sids/$sids_cols);
                        $sids_keys = array_keys($sids);

                        ?>

                        <tr>
                            <td colspan='4' class='_label'>
                                <div>
                                    <span id='pf_name' class='hide'><?php echo _("Plugins")?>:
                                        <span id='cont_pfilter' style='margin-left: 5px'>
                                            <a id='p_filter' class='uppercase'><img align="absmiddle" border="0" src="/ossim/pixmaps/arrow_green.gif"><span><?php echo _("Show Plugin filter")?></span></a>
                                        </span>
                                    </span>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td class='noborder' colspan='4'>
                                <div id='cont_tplugin'>
                                    <table id='table_plugin'>
                                        <?php
                                        for ($i=0; $i<$sids_rows; $i++)
                                        {
                                            echo "<tr>";
                                                for ($j=0; $j<$sids_cols; $j++)
                                                {
                                                    if ($cont < $num_sids)
                                                    {
                                                        $plugin_key = $sids_keys[$cont];
                                                        $val        = $sids[$plugin_key];

                                                        echo "<td class='noborder left'><input type='checkbox' class='little' value='".base64_encode($val)."'/>$plugin_key</td>";

                                                        $cont++;
                                                    }
                                                    else
                                                        echo "<td class='noborder'>&nbsp;</td>";
                                                }
                                            echo "</tr>";
                                        }

                                        ?>
                                    </table>
                                </div>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </table>

                <form id='form_filters' name='form_filters'>
                    <input type='hidden' name='f_src_ip' id='f_src_ip'/>
                    <input type='hidden' name='f_dst_ip' id='f_dst_ip'/>
                    <input type='hidden' name='f_src_port' id='f_src_port'/>
                    <input type='hidden' name='f_dst_port' id='f_dst_port'/>
                    <input type='hidden' name='f_protocol' id='f_protocol'/>
                </form>

            </div>
        </div>

        <div id="numeroDiv"></div>

    </body>
    </html>
    <?php
}

$db->close();
$geoloc->close();
