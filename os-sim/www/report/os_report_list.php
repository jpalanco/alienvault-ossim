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
require_once 'os_report_common.php';

Session::logcheck('report-menu', 'ReportsReportServer');

$_DEBUG = FALSE;

// Connect BD
$db     = new ossim_db();
$conn   = $db->connect();

$d_reports = get_report_data();

//Load available nets, hosts and netgroups

$autocomplete_keys = array('hosts', 'host_groups', 'nets');
$assets            = Autocomplete::get_autocomplete($conn, $autocomplete_keys);




$db->close();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title> <?php echo _('OSSIM Framework'); ?> </title>
    <meta http-equiv="Content-Type" content="text/html;charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>

    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/jquery-ui.min.js"></script>

    <!-- Autocomplete libraries: -->
    <script type="text/javascript" src="../js/jquery.autocomplete.pack.js"></script>
    <link rel="stylesheet" type="text/css" href="../style/jquery.autocomplete.css"/>

    <!-- Greybox: -->
    <script type="text/javascript" src="../js/greybox.js"></script>

    <!-- Elastic textarea: -->
    <script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>

    <!-- Own libraries: -->
    <script type='text/javascript' src='../js/utils.js'></script>
    <script type='text/javascript' src='../js/notification.js'></script>

    <link rel="stylesheet" type="text/css" href="/ossim/style/jquery-ui.css"/>
    <link rel="stylesheet" type="text/css" href="../style/datepicker.css"/>

    <script type='text/javascript'>

        //AJAX Request
        var ajax_request = null;


        <?php
        if (Session::menu_perms('environment-menu', 'PolicyHosts') || Session::menu_perms('environment-menu', 'PolicyNetworks'))
        {
            ?>
            // Autocomplete assets
            var assets = [<?php echo $assets?>];

            function autocomplete_assets(id, data)
            {
               var hid       = '#h_'+id;
               var input_id  = '#'+id;

               $(input_id).autocomplete(data, {
                    minChars: 0,
                    width: 300,
                    max: 100,
                    matchContains: true,
                    autoFill: false,
                    formatItem: function(row, i, max) {
                        return row.txt;
                    }
                }).result(function(event, item) {

                    $(hid).val(item.id);

                    if (id == 'ar_asset')
                    {
                        var link_id  = '#link_'+id;

                        if (item.type == 'host')
                        {
                            var href = top.av_menu.get_menu_url("/ossim/av_asset/common/views/detail.php?asset_id="+item.id, 'environment', 'assets', 'assets');
                        }
                        else if (item.type == 'net')
                        {
                            var href = top.av_menu.get_menu_url("/ossim/av_asset/common/views/detail.php?asset_id="+item.id, 'environment', 'assets', 'networks');
                        }
                        else if (item.type == 'host_group')
                        {
                            var href = top.av_menu.get_menu_url("/ossim/av_asset/common/views/detail.php?asset_id="+item.id, 'environment', 'assets', 'asset_groups');
                        }

                        if (href != '')
                        {
                            $(link_id).attr('href', href);
                        }
                    }
                });
            }
            <?php
        }
        ?>


        function check_date(date)
        {
            var date = date.split("-");

            var m = date[1];
            var d = date[2];
            var y = date[0];

            return m > 0 && m < 13 && y > 0 && y < 3000 && d > 0 && d <= (new Date(y, m, 0)).getDate();
        }


        function set_date(id)
        {
            var date = $(id).val();

            if ( !check_date(date) )
            {
                <?php
                $y = strftime("%Y");
                $m = strftime("%m");
                $d = strftime("%d");
                ?>

                date = '<?php echo date("Y") ?>-<?php echo date('m') ?>-<?php echo date('d')?>';
            }

            return date;
        }

        function check_data(action, data)
        {
            var ret = $.ajax({
                url: "os_report_actions.php",
                global: false,
                type: "POST",
                data: "action="+action+ "&data="+data,
                dataType: "text",
                async:false
                }
            ).responseText;

            return ret;
        }

        function download_pdf(id)
        {
            var r_info_id = '#report_info';
            var link_id = '#dp_'+id;

            $(link_id).click(function() {

                var form_id   = '#form_'+id;
                var data      =  $(form_id).serialize();

                <?php
                if ( $_DEBUG )
                {
                    ?>
                    window.open("os_report_run.php?report_id="+ id + "&section=assets&" + data,'debug','');
                    <?php
                }
                else
                {
                    ?>
                    ajax_request = $.ajax({
                        type: "POST",
                        url: "os_report_run.php",
                        data: "report_id="+ id + "&section=assets&" + data,
                        beforeSend: function( xhr ) {
                            $(r_info_id).html('');
                            var txt =  "<div><img id='report_msg_image' style='margin-right: 5px' src='../pixmaps/loading3.gif'/><span id='report_msg'><?php echo _("Generating report, please wait")?> ...</span></div>";
                            loading_box(txt);
                        },
                        success: function(data){

                            var status    = data.split('###');
                                status[0] = status[0].replace(/(\r\n|\n|\r)/gm, '');

                            if (status[0] != '' && status[0] == 'error')
                            {
                                $('.overlay').remove();
                                $('#report_msg').remove();
                                $('#report_msg_image').remove();
                                $('#cont_lb').css("margin","23px auto 0px auto");
                                $('.loading_box').remove();
                                $(r_info_id).html(osr_notify_error(status[1]));
                                return;
                            }

                            var data   = id+'###'+data
                                data   = Base64.encode(data);

                            var st_chk = check_data('check_file', data);

                            $('.overlay').remove();
                            $('.loading_box').remove();

                            if ( st_chk == 1 )
                            {
                                document.location.target = '_blank';
                                document.location.href   = 'os_report_run.php?data='+data;
                            }
                            else
                            {
                                $(r_info_id).html(osr_notify_error(st_chk));
                            }
                        }
                    });
                    <?php
                }
                ?>

            });
        }

        function draw_email(id, email)
        {
            var cont_email = '#cont_email_'+id;
            var report_id  = Base64.encode(id);

            var height   = $.getDocHeight();

            var content  = "<div class='lb_container'>"+
                                "<div class='lb_title'><?php echo _("Send PDF by e-mail")?></div>" +
                                    "<div>" +
                                         "<div id='email_nt'><div style='position:absolute; width: 100%; top: 7px;'></div></div>" +
                                         "<div id='c_nt'></div>" +
                                         "<div id='cont_lb'>" +
                                            "<div id='cont_email_"+id+"' style='padding: 19px 0px 10px 0px;'>" +
                                                "<input id='email' name='email' type='text' value='"+email+"'/>" +
                                                "<input type='button' class='small' id='send_email' value='<?php echo _('Send')?>'\>" +
                                             "</div>" +
                                         "</div>" +
                                         "<div class='lb_bt'><input type='button' id='stop' class='av_b_secondary small' value='<?php echo _("Cancel")?>'/></div>" +
                                    "</div>" +
                           "</div>";


            $('body').append('<div class="overlay" style="height:'+height+'px;"></div>');
            $('body').append('<div class="loading_box" style="display:none;">'+content+'</div>');
            $('.loading_box').show();

            $('#send_email').click(function() { send_pdf(report_id) });
            $('#stop').click(function() { stop_report(); });

            window.scrollTo(0,0);
        }

        function show_email(id, email)
        {
            var se_id = '#se_'+id;
            $(se_id).click(function() {
                draw_email(id, email);
            });
        }


        function disable_email()
        {
            $('#send_email').attr('disabled', 'disabled')
            $('#send_email').off('click');
            $('#email').addClass('email_disabled')
            $('#email').attr('disabled', 'disabled');
        }


        function enable_email(id)
        {
            $('#send_email').removeAttr('disabled');
            $('#send_email').click(function() { send_pdf(id) });
            $('#email').removeClass('email_disabled')
            $('#email').removeAttr('disabled');
        }

        function send_pdf(id)
        {
            var report_id  = Base64.decode(id);

            var r_info_id  = '#report_info';
            var email_nt   = '#email_nt div';
            var c_nt       = '#c_nt';

            var cont_email = '#cont_email_'+report_id;

            var email      = $(cont_email +' #email').val();

            var form_id   = '#form_'+report_id;
            var data      =  $(form_id).serialize();


            $(r_info_id).html('');
            $(email_nt).html('');

            var txt = "<div><img style='margin-right: 5px' src='../pixmaps/loading3.gif'/><span id='report_msg'><?php echo _('Checking e-mail, please wait')?> ...</span>";
            $(c_nt).html(txt);

            disable_email();

            var se_chk = check_data('check_email', email);

            if (se_chk != 1)
            {
                $(c_nt).html('');
                $(email_nt).html(osr_notify_error(se_chk));
                enable_email(id);
                return;
            }

            ajax_request = $.ajax({
                type: "POST",
                url: "os_report_run.php",
                data: "report_id="+ report_id + "&" + "&section=assets&" +data+"&email="+email,
                beforeSend: function( xhr ) {
                    var txt = "<div><img id='report_msg_image' style='margin-right: 5px' src='../pixmaps/loading3.gif'/><span id='report_msg'><?php echo _("Generating report, please wait")?> ...</span>";
                    $(c_nt).html(txt);
                },
                success: function(data){

                    var status    = data.split('###');
                        status[0] = status[0].replace(/(\r\n|\n|\r)/gm, '');

                    if (status[0] == 'OK')
                    {
                        $('.overlay').remove();
                        $('.loading_box').remove();
                        $(r_info_id).html(osr_notify_success(status[1]));
                    }
                    else
                    {
                        $('#report_msg').remove();
                        $('#report_msg_image').remove();
                        $(email_nt).html(osr_notify_error(status[1]));
                        enable_email(id);
                    }
                }
            });
        }

        <?php

        if (Session::menu_perms('environment-menu', 'MonitorsAvailability'))
        {
            ?>
            function nagios_link(avr_nagios_link, avr_sensor, avr_section)
            {
                var baselink    = $('#'+avr_nagios_link).val();
                var sensor      = $('#'+avr_sensor).val();
                var link        = $('#'+avr_section).val();

                if (typeof(sensor) == 'undefined' || sensor == '' || sensor == null)
                {
                    var msg = osr_notify_error('<?php echo _('You need to select one sensor at least')?>');
                    parent.window.scrollTo(0, 0);

                    $('#report_info').html(msg);

                    return false;
                }

                var fr_down  = baselink+sensor+link;
                var url      ="<?php echo Menu::get_menu_url('../nagios/index.php?opc=reporting', 'environment', 'availability', 'reporting') ?>";
                    url     += "&sensor="+sensor+"&nagios_link="+fr_down;

                document.location.href = url;

            }
            <?php
        }

        if (Session::menu_perms('analysis-menu', 'EventsForensics'))
        {
            ?>
            function showhide(layer,img)
            {
                if ($(img).attr('src').match(/plus/))
                {
                    $(layer).show();
                    $(img).attr('src','../pixmaps/minus.png');
                }
                else
                {
                    $(layer).hide();
                    $(img).attr('src','../pixmaps/plus.png')
                }
            }

            function bind_siem_options()
            {
                var id   = '#rt_siem_report';

                var list_siem_db = [
                    {"url" : "<?php echo Menu::get_menu_url('../forensics/base_stat_alerts.php?sort_order=occur_d', 'analysis', 'security_events', 'security_events')?>", "text" : "<?php echo _("SIEM Unique Events")?>"},
                    {"url" : "<?php echo Menu::get_menu_url('../forensics/base_stat_sensor.php?sort_order=occur_d', 'analysis', 'security_events', 'security_events')?>", "text" : "<?php echo _("SIEM Sensors")?>"},
                    {"url" : "<?php echo Menu::get_menu_url('../forensics/base_stat_uaddr.php?sort_order=occur_d&addr_type=1', 'analysis', 'security_events', 'security_events')?>", "text" : "<?php echo _("SIEM Unique Source Addresses")?>"},
                    {"url" : "<?php echo Menu::get_menu_url('../forensics/base_stat_uaddr.php?sort_order=occur_d&addr_type=2', 'analysis', 'security_events', 'security_events')?>", "text" : "<?php echo _("SIEM Unique Destination Addresses")?>"},
                    {"url" : "<?php echo Menu::get_menu_url('../forensics/base_stat_ports.php?sort_order=occur_d&proto=-1&port_type=1', 'analysis', 'security_events', 'security_events')?>", "text" : "<?php echo _("SIEM Source TCP/UDP Ports")?>"},
                    {"url" : "<?php echo Menu::get_menu_url('../forensics/base_stat_ports.php?sort_order=occur_d&port_type=2&proto=-1', 'analysis', 'security_events', 'security_events')?>", "text" : "<?php echo _("SIEM Destination TCP/UDP Ports")?>"},
                    {"url" : "<?php echo Menu::get_menu_url('../forensics/base_stat_plugins.php?sort_order=occur_d', 'analysis', 'security_events', 'security_events')?>", "text" : "<?php echo _("SIEM Unique Data Sources")?>"},
                    {"url" : "<?php echo Menu::get_menu_url('../forensics/base_stat_iplink.php?sort_order=events_d&fqdn=no', 'analysis', 'security_events', 'security_events')?>", "text" : "<?php echo _("SIEM Unique IP Links")?>"},
                    {"url" : "<?php echo Menu::get_menu_url('../forensics/base_stat_country.php', 'analysis', 'security_events', 'security_events')?>", "text" : "<?php echo _("SIEM Unique Country Events")?>"}
                ];


                var list_siem_html = '<a onclick="showhide(\'#myMenu1\',\'#imgPlusSIEM\')"><img src="../pixmaps/plus.png" border="0" style="margin-left: 5px;" id="imgPlusSIEM"></a>' +
                                        '<div id="myMenu1" style="position:absolute; width: 500px; *width:200px; display:none">' +
                                            '<ul id="myMenu" class="contextMenu" style="-moz-user-select: none; display: block;">';

                for (var i=0; i<list_siem_db.length; i++)
                {
                    list_siem_html +=  '<li><a href="'+list_siem_db[i].url+'"><strong>'+list_siem_db[i].text+'</strong><img src="../pixmaps/arrow-000-small.png" border="0" width="16px" height="16px"/></a></li>';
                }

                list_siem_html +='</ul>' +
                            '</div>';

                $(id).append(list_siem_html);
            }

            <?php
        }
        ?>


        //Notifications

        function osr_notify_error(txt)
        {
            var config_nt = { content: txt,
                              options: {
                                type:'nf_error',
                                cancel_button: false
                              },
                              style: 'width: 90%; margin: 10px auto; text-align:center;'
                            };

            var nt = new Notification('nt_1', config_nt);

            return nt.show();
        }


        function osr_notify_success(txt)
        {
            var config_nt = { content: txt,
                              options: {
                                type:'nf_success',
                                cancel_button: true
                              },
                              style: 'width: 80%; margin: 10px auto; text-align:center;'
                            };

            var nt         = new Notification('nt_1', config_nt);

            return nt.show();
        }


        function loading_box(txt)
        {
            var height   = $.getDocHeight();

            var content  = "<div class='lb_container'>"+
                                "<div class='lb_title'><?php echo _('Download PDF')?></div>" +
                                    "<div>" +
                                         "<div id='c_nt'>"+txt+"</div>" +
                                         "<div id='cont_lb'></div>" +
                                         "<div class='lb_bt'><input type='button' id='stop' class='av_b_secondary small' value='<?php echo _("Cancel")?>'/></div>" +
                                    "</div>" +
                           "</div>";


            $('body').append('<div class="overlay" style="height:'+height+'px;"></div>');
            $('body').append('<div class="loading_box" style="display:none;">'+content+'</div>');
            $('.loading_box').show();

            $('#stop').click(function() { stop_report(); });

            top.scrollTo(0,0);
        }


        function stop_report()
        {
            var txt = "<div><img style='margin-right: 5px' src='../pixmaps/loading3.gif'/><span id='report_msg'><?php echo _('Stopping report, please wait')?> ...</span>";
            $('#c_nt').html(txt);

            if (typeof(ajax_request) == 'object' && ajax_request != null)
            {
                ajax_request.abort();
            }

            $('.overlay').remove();
            $('.loading_box').remove();
        }


        $(document).ready(function(){

            <?php
            if (Session::menu_perms('environment-menu', 'PolicyHosts') || Session::menu_perms('environment-menu', 'PolicyNetworks'))
            {
                ?>
                //Autocomplete assets
                $('.asset').each(function(index) {
                    var id = $(this).attr("id");
                    autocomplete_assets(id, assets);
                });
                <?php
            }

            if (Session::menu_perms('settings-menu', 'ToolsUserLog'))
            {
                ?>
                $('#link_ua').click(function(event){

                    event.preventDefault();

                    var url = $('#link_ua').attr('href') + "&user="+$('#ua_user').val() +"&code="+$('#ua_action').val();

                    document.location.href = url;
                });
                <?php
            }


            if (Session::menu_perms('analysis-menu', 'EventsForensics'))
            {
                ?>
                bind_siem_options();
                <?php
            }
            ?>

            // Date Filter
            $('.date_filter').datepicker({
                showOn: "both",
                buttonText: "",
                dateFormat: "yy-mm-dd",
                buttonImage: "/ossim/pixmaps/calendar.png",
                onClose: function(selectedDate)
                {
                    // End date must be greater than the start date

                    // Alarm Report

                    if ($(this).attr('id') == 'ar_date_from')
                    {
                        $('#ar_date_to').datepicker('option', 'minDate', selectedDate );
                    }
                    else if($(this).attr('id') == 'ar_date_to')
                    {
                        $('#ar_date_from').datepicker('option', 'maxDate', selectedDate );
                    }

                    // Business & Compliance

                    if ($(this).attr('id') == 'bc_pci_date_from')
                    {
                        $('#bc_pci_date_to').datepicker('option', 'minDate', selectedDate );
                    }
                    else if($(this).attr('id') == 'bc_pci_date_to')
                    {
                        $('#bc_pci_date_from').datepicker('option', 'maxDate', selectedDate );
                    }

                    // Geographic Report

                    if ($(this).attr('id') == 'gr_date_from')
                    {
                        $('#gr_date_to').datepicker('option', 'minDate', selectedDate );
                    }
                    else if($(this).attr('id') == 'gr_date_to')
                    {
                        $('#gr_date_from').datepicker('option', 'maxDate', selectedDate );
                    }

                    // SIEM events

                    if ($(this).attr('id') == 'sr_date_from')
                    {
                        $('#sr_date_to').datepicker('option', 'minDate', selectedDate );
                    }
                    else if($(this).attr('id') == 'sr_date_to')
                    {
                        $('#sr_date_from').datepicker('option', 'maxDate', selectedDate );
                    }

                    // Tickets

                    if ($(this).attr('id') == 'tr_date_from')
                    {
                        $('#tr_date_to').datepicker('option', 'minDate', selectedDate );
                    }
                    else if($(this).attr('id') == 'tr_date_to')
                    {
                        $('#tr_date_from').datepicker('option', 'maxDate', selectedDate );
                    }

                }
            });

            //Year Calendar
            $('.year').each(function(index)
            {
                var id = $(this).attr("id");
                year_calendar(id);
            });

            //Month Calendar
            $('.month').each(function(index)
            {
                var id = $(this).attr("id");
                month_calendar(id);
            });

            //Download PDF and Send PDF by e-mail
            $('.td_actions').each(function(index)
            {
                var id = $(this).attr("id");
                    id = id.replace("act_", '');

                download_pdf(id);
                show_email(id, '');
            });

        });
    </script>

</head>

<body>

    <div id='container_center'>
        <div id='report_info'></div>

                    <table class='table_list' id="main_table">
                            <tr>
                                <th class="reportName"><?php echo _('Report Name')?></th>
                                <th class="reportOptions"><?php echo _('Report Options')?></th>
                                <th class="export"><?php echo _('Actions')?></th>
                            </tr>

                        <?php
                        $cont = 0;
                        foreach ($d_reports as $r_key => $r_data)
                        {
                            if ($r_data['access'] == FALSE)
                            {
                                continue;
                            }

                            ?>
                            <tr><td style='display:none'></td></tr>
                            <form name='form_<?php echo $r_data["report_id"]?>' id='form_<?php echo $r_data['report_id']?>' method='POST'>
                                <tr>
                                    <td valign='top'>
                                        <div class='report_title'>
                                            <h3 id='rt_<?php echo $r_data['report_id']?>'><?php echo _($r_data['report_name'])?></h3>
                                        </div>
                                        <div class='report_modules'>
                                            <?php
                                            if (count($r_data['subreports']) > 0)
                                            {
                                                foreach ($r_data['subreports'] as $sr_key => $sr_data)
                                                {
                                                    if ($sr_data['id'] != $r_data['report_id'])
                                                    {
                                                        echo "<input type='checkbox' name='sr_".$sr_data['id']."' id='".$sr_data['id']."' checked='checked'/><span style='margin-left: 3px'>"._($sr_data['name'])."</span><br/>";
                                                    }
                                                }
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class='report_parameters'>
                                            <?php
                                            if (count($r_data['parameters']) > 0)
                                            {
                                                foreach ($r_data['parameters'] as $rp_key => $rp_data)
                                                {
                                                    draw_parameter($rp_data);
                                                    echo '<br/>';
                                                }
                                            }
                                            ?>
                                        </div>
                                    </td>

                                    <td class='td_actions act' id='act_<?php echo $r_data['report_id']?>'>
                                        <table cellspacing="0" cellpadding="0">
                                            <?php
                                            if ($r_data['type'] == 'external')
                                            {
                                                ?>
                                                <tr>
                                                    <td style="text-align:left;">
                                                        <?php
                                                        $link     = (!empty($r_data['link']))   ? ' href="'.$r_data["link"].'"'     : '';
                                                        $target   = (!empty($r_data['target'])) ? ' target="'.$r_data["target"].'"' : '';
                                                        $on_click = (!empty($r_data['click']))  ? ' onclick="'.$r_data["click"].'"' : '';

                                                        $link_options = $link.$target.$on_click;
                                                        ?>

                                                        <a id="<?php echo $r_data['link_id']?>" <?php echo $link_options?> class="gray" title="<?php echo _("View Report")?>">
                                                            <img src="../pixmaps/osr_view_report.png" align="absmiddle"/><span><?php echo _('View Report');?></span>
                                                        </a>
                                                    </td>
                                                    <td class='left'>&nbsp;</td>
                                                </tr>
                                                <?php
                                            }
                                            else
                                            {
                                                ?>
                                                <tr>
                                                    <td class='left'>
                                                        <a id='dp_<?php echo $r_data["report_id"]?>' class="gray download_pdf" title="<?php echo _('Generate PDF')?>">
                                                            <img src="../pixmaps/osr_pdf_button.png" align="absmiddle"/><?php echo _('Download PDF')?>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class='left'style="padding-top: 10px;">
                                                        <?php
                                                        if ( $r_data["send_by_email"] == 1 )
                                                        {
                                                            ?>
                                                            <a id='se_<?php echo $r_data["report_id"]?>' class="gray send_email" title="<?php echo _('Send PDF by e-mail')?>">
                                                                <img src="../pixmaps/osr_email_button.png" align="absmiddle"/><span><?php echo _('Send by e-mail');?></span>
                                                            </a>
                                                            <?php
                                                        }
                                                        else
                                                        {
                                                           ?>
                                                           <img src="../pixmaps/emailButton.png" align="absmiddle" class='disabled'/><span><?php echo _('Send by e-mail');?></span>
                                                           <?php
                                                        }
                                                        ?>
                                                    </td>
                                                </tr>
                                                <?php
                                            }
                                            ?>
                                        </table>
                                    </td>
                            </tr>
                            </form>
                            <?php
                        }
                        ?>

                    </table>

    </div>
</body>
</html>
