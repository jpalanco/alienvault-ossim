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
require_once 'alarm_common.php';

Session::logcheck("analysis-menu", "ControlPanelAlarms");


function notify_and_die($msg, $db)
{
    $config_nt = array(
        'content' => $msg,
        'options' => array (
            'type'          => 'nf_error',
            'cancel_button' => ''
        ),
        'style'   => ' margin:25px auto 0 auto;text-align:center;padding:3px 30px;'
    );
    
    $nt = new Notification('nt_panel', $config_nt);
    $nt->show();
    
    if (is_object($db))
    {
        $db->close();
    }

    die(); 
}


/* connect to db */
$db   = new ossim_db(TRUE);
$conn = $db->connect();

if (isset($_GET["event"]) && !isset($_GET["backlog"])) {
    $alarm = Alarm::get_alarm_detail_by_event($conn,GET('event'));
    header("Location: /ossim/#analysis/alarms/alarms-".$alarm->get_backlog_id());
    die;
}

$backlog_id =  GET('backlog');
list($alarm, $event) = Alarm::get_alarm_detail($conn, GET('backlog'));

ossim_valid($backlog_id,    OSS_HEX,    'illegal:' . _("Backlog ID"));
if ( ossim_error() )
{
    die(ossim_error());
}



if(!is_object($alarm))
{
    $msg = _('Unable to retrieve the alarm information.');
    notify_and_die($msg, $db);
}

$stats = $alarm->get_stats();
if ( count($stats['src']['ip']) < 1 || count($stats['dst']['ip']) < 1)
{
    $msg = _('Unable to retrieve the alarm information.');
    notify_and_die($msg, $db);
}


$gl    = new Geolocation("/usr/share/geoip/GeoLiteCity.dat");
$tz    = Util::get_timezone();

//This is to force the alarms to remember the position of the datatables
$_SESSION["_alarm_keep_pagination"] = TRUE;
$_SESSION["_alarm_stats"]           = $stats;

//Getting event info
$event_info = Alarm::get_event($conn, $alarm->get_event_id());

//alarm source and detination
$src  = $stats['src'];
$dst  = $stats['dst'];

$alarm_name   = Util::translate_alarm($conn, $alarm->get_sid_name(), $alarm, 'array');
if (!empty($alarm_name['subcategory']))
{
    $alarm_tr                  = Util::translate_alarm($conn, $alarm_name['subcategory'], $alarm, 'array');
    $alarm_name['subcategory'] = $alarm_tr['name'];
}
$event_number = $stats['events'];
$alarm_time   = get_alarm_life($alarm->get_since(), $alarm->get_last());
$alarm_life   = get_alarm_life($alarm->get_last(), gmdate("Y-m-d H:i:s"), 'ago');

/* Source */
$_home_src      = Asset_host::get_extended_name($conn, $gl, $alarm->get_src_ip(), $ctx, $event_info["src_host"], $event_info["src_net"]);
/* Destination */
$_home_dst      = Asset_host::get_extended_name($conn, $gl, $alarm->get_dst_ip(), $ctx, $event_info["dst_host"], $event_info["dst_net"]);

//Alarm Attack Pattern
$attack_pattern = _(is_promiscous(count($src['ip']), count($dst['ip']), $_home_src['is_internal'], $_home_dst['is_internal']));

//Getting the tags
$_tags    = Tag::get_tags_by_component($conn, $backlog_id);
$tag_list = array();
foreach ($_tags as $tag_id => $tag)
{
    $tag_list[$tag_id] = array(
        'id'    => $tag_id,
        'name'  => $tag->get_name(),
        'class' => $tag->get_class()
    );
}

//Alarm Status
if ($alarm->get_removable() === 0)
{
    $status = 'correlating';
}
else
{
    $status = $alarm->get_status();
}

$risk = $alarm->get_risk();
$risk_text = Util::get_risk_rext($risk);
//Alarm JSON Info
$alarm = array(
    'backlog_id'     => $backlog_id,
    'plugin_id'      => $alarm->get_plugin_id(),
    'plugin_sid'     => $alarm->get_plugin_sid(),
    'event_id'       => $alarm->get_event_id(),
    'engine'         => Util::uuid_format($alarm->get_ctx()),
    'agent_ctx'      => $event_info["agent_ctx"],
    'sid_name'       => $alarm_name['name'],
    'status'         => $status,
    'risk'           => $risk,
    'risk_text'      => "<span class='risk-bar $risk_text'>"._($risk_text)."</span>",
    'attack_pattern' => $attack_pattern,
    'created'        => $alarm_life,
    'duration'       => $alarm_time,
    'events'         => $event_number,
    'otx_icon'       => $alarm->get_otx_icon(),
    'iocs'           => $alarm->get_iocs($conn, TRUE),
    'event_start'    => $alarm->get_since(),
    'event_end'      => $alarm->get_last(),
    'src_ips'        => $alarm->get_src_ip(),
    'dst_ips'        => $alarm->get_dst_ip(),
    'src_ports'      => $alarm->get_src_port(),
    'dst_ports'      => $alarm->get_dst_port(),
    'sources'        => $src['ip'],
    'destinations'   => $dst['ip'],
    'tags'           => $tag_list,
    'taxonomy'       => array(
        'id'          => $alarm_name['id'],
        'kingdom'     => $alarm_name['kingdom'],
        'category'    => $alarm_name['category'],
        'subcategory' => $alarm_name['subcategory']
    )
);

//Alarm Perms
$perms = array(
    'admin' => Session::am_i_admin(),
    'pro'   => Session::is_pro()
);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title><?php echo _('AlienVault ' . (Session::is_pro() ? 'USM' : 'OSSIM')) ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>

    <?php
        //CSS Files
        $_files = array(
            array('src' => 'jquery-ui.css',             'def_path' => TRUE),
            array('src' => 'tipTip.css',                'def_path' => TRUE),
            array('src' => 'jquery.dropdown.css',       'def_path' => TRUE),
            array('src' => 'jquery.select.css',         'def_path' => TRUE),
            array('src' => 'av_show_more.css',          'def_path' => TRUE),
            array('src' => 'jquery.dataTables.css',     'def_path' => TRUE),
            array('src' => 'av_table.css',              'def_path' => TRUE),
            array('src' => 'av_dropdown_tag.css',       'def_path' => TRUE),
            array('src' => 'av_common.css',             'def_path' => TRUE),
            array('src' => 'av_tags.css',               'def_path' => TRUE),
            array('src' => 'alarm/detail.css',          'def_path' => TRUE),
        );

        Util::print_include_files($_files, 'css');

        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',                     'def_path' => TRUE),
            array('src' => 'jquery-ui.min.js',                  'def_path' => TRUE),
            array('src' => 'jquery.number.js.php',              'def_path' => TRUE),
            array('src' => 'notification.js',                   'def_path' => TRUE),
            array('src' => 'greybox.js',                        'def_path' => TRUE),
            array('src' => 'utils.js',                          'def_path' => TRUE),
            array('src' => 'token.js',                          'def_path' => TRUE),
            array('src' => 'jquery.tipTip-ajax.js',             'def_path' => TRUE),
            array('src' => 'av_show_more.js',                   'def_path' => TRUE),
            array('src' => 'jquery.dataTables.js',              'def_path' => TRUE),
            array('src' => 'av_table.js.php',                   'def_path' => TRUE),
            array('src' => 'av_storage.js.php',                 'def_path' => TRUE),
            array('src' => 'av_tabs.js.php',                    'def_path' => TRUE),
            array('src' => 'jquery.dropdown.js',                'def_path' => TRUE),
            array('src' => 'av_dropdown_tag.js',                'def_path' => TRUE),
            array('src' => 'av_tags.js.php',                    'def_path' => TRUE),
            array('src' => 'jquery.select.js',                  'def_path' => TRUE),
            array('src' => 'av_breadcrumb.js.php',              'def_path' => TRUE),
            array('src' => '/alarm/js/alarm_detail.js.php',     'def_path' => FALSE)
        );

        Util::print_include_files($_files, 'js');  
        
        
        require '../host_report_menu.php';
        
    ?>

    <script type='text/javascript'>
        
        $(document).ready(function()
        {
            new alarm_detail(<?php echo json_encode($alarm) ?>, <?php echo json_encode($perms) ?>);
        });

    </script>
</head>

<body>

<div id='alarm_wrapper' data-alarm="main-wrapper">

    <div id="bread_crumb_alarm" data-alarm="breadcrumb"></div>
    
    <button id='button_action' class='small' data-dropdown="#dropdown-actions">
        <?php echo _('Actions') ?> &nbsp;&#x25be;
    </button>

    <div id='alarm_notification'></div>

    <div id='alarm_name' data-alarm="name"></div>        
    
    <div class='clear_layer'></div>
    
    <div id="detail_labels" data-alarm="labels">
        <img data-alarm="label-selection" class="img_label" src="/ossim/pixmaps/label.png"/>
        <div id="label_container" data-alarm="label-container"></div>
    </div>
    
    <div class='clear_layer'></div>
    
    <div id='alarm_summary' data-alarm='summary' class='alarm_section'>
        <div id='alarm_status' class='item'>
            <div class='alarm_header center'><?php echo _('Status') ?></div>
            <div data-alarm='status' class='alarm_content'></div>
        </div>
        
        <div id='alarm_risk' class='item'>
            <div class='alarm_header center'><?php echo _('Risk') ?></div>
            <div data-alarm='risk_text' class='alarm_content'></div>
        </div>
        
        <div id='alarm_attack_pattern' class='item'>
            <div class='alarm_header center'><?php echo _('Attack Pattern') ?></div>
            <div data-alarm='attack_pattern' class='alarm_content'></div>
        </div>
                    
        <div id='alarm_created' class='item'>
            <div class='alarm_header center'><?php echo _('Created') ?></div>
            <div data-alarm='created' class='alarm_content'></div>
        </div>
        
        <div id='alarm_duration' class='item'>
            <div class='alarm_header center'><?php echo _('Duration') ?></div>
            <div data-alarm='duration' class='alarm_content'></div>
        </div>
        
        <div id='alarm_events' class='item'>
            <div class='alarm_header center'><?php echo _('# Events') ?></div>
            <div data-alarm='events' class='alarm_content'></div>
        </div>
        <div id='alarm_id' class='item'>
            <div class='alarm_header center'><?php echo _('Alarm ID') ?></div>
            <div class='alarm_content'><?php echo $alarm["backlog_id"]?></div>
        </div>

        <div id='alarm_otx' class='item'>
            <div class='alarm_header center'>
                <img data-alarm="otx-icon" class="otx_icon" style="display:none;"></img>
                <?php echo _('OTX Indicators') ?>
            </div>
            <div data-alarm='otx' class='alarm_content'></div>
        </div>
        
        <div class='clear_layer'></div>
        
    </div>

    
    <div id='alarm_boxes' class='alarm_section'>

        <div class='box fleft'>
            <div class='box_wrapper'>
                <div class='alarm_header'>
                    <?php echo _('Source') ?> 
                    (<span data-alarm="total-src"></span>)
                    <div class="box_ip_selector" data-alarm="select-src"></div>
                </div>
                <div class='alarm_content' data-alarm="box-src"></div>
            </div>
        </div>

        <div class='box fright'>
            <div class='box_wrapper'>
                <div class='alarm_header'>
                    <?php echo _('Destination') ?> 
                    (<span data-alarm="total-dst"></span>)
                    <div class="box_ip_selector" data-alarm="select-dst"></div>
                </div>
                <div class='alarm_content' data-alarm="box-dst"></div>
            </div>
        </div>
        
        <div class='clear_layer'></div>
        
    </div>
    
    <div id='alarm_tabs' class='alarm_section'>
        <ul></ul>
    </div>

</div>


<div id="alarm_box_template" data-alarm="box-template">
    
    <div class='box_section'>
        <div class='alarm_box_name'>
            <div class="alarm_asset_name" data-alarm="asset-name"></div>
            <div class="alarm_asset_loc" data-alarm="asset-location">
                <strong><?php echo _('Location:') ?> </strong>
                <span data-bind='val'></span>
            </div>
            <div class="clear_layer"></div>
        </div>
        
        
        <div class='alarm_box_sec' data-alarm="group-list">
            <div class='el_header'><?php echo _('Asset Groups:') ?> </div>
            <div class='el_val' data-bind='val'></div>
            <div class='clear_layer'></div>
        </div>
        
        <div class='alarm_box_sec' data-alarm="network-list">
            <div class='el_header'><?php echo _('Networks:') ?> </div>
            <div class='el_val' data-bind='val'></div>
            <div class='clear_layer'></div>
        </div>
        
        <div class='alarm_box_sec' data-alarm="ip-reputation">
            <span class='el_header'><?php echo _('OTX IP Reputation:') ?> </span>
            <span class='el_val' data-bind='val'></span>
            <div class='clear_layer'></div>
        </div>
        
    </div>
    
    <hr>
    
    <div data-alarm='asset-tab' class='section_tabs box_section'>
        <ul></ul>
    </div>
    
    <hr>
    
    <div class='box_section'>
        <div class='alarm_box_title'><?php echo _('Other Details:') ?></div>
        
        <div class="extra_url">
            <a data-alarm='extra-siem'   href="javascript:;"><?php echo _('SIEM Events') ?></a>,
            <a data-alarm='extra-logger' href="javascript:;"><?php echo _('Raw Logs') ?></a>
        </div>
        <div class="extra_url">
            <a data-alarm='extra-url' href="http://www.projecthoneypot.org/ip_###IP###" target="_blank">Honey-Pot</a>, 
            <a data-alarm='extra-url' href="http://lacnic.net/cgi-bin/lacnic/whois?lg=EN&query=###IP###" target="_blank">Whois</a>, 
            <a data-alarm='extra-url' href="http://www.dnswatch.info/dns/ip-location?ip=###IP###&submit=Locate+IP" target="_blank">Reverse-DNS</a>
        </div>
    </div>
    
</div>


<div id="dropdown-actions"  class="dropdown dropdown-close dropdown-tip dropdown-anchor-right">
    <ul class="dropdown-menu" data-alarm="dropdown-actions"></ul>
</div>

</body>
</html>

<?php
$db->close();
$gl->close();
