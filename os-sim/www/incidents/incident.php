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

Session::logcheck('analysis-menu', 'IncidentsIncidents');

require_once 'incident_common.php';

// Version

$id   = GET('id');
$edit = ($_REQUEST['edit'] == 1) ? 1 : 0;

ossim_valid($id, OSS_ALPHA, 'illegal:' . _('Incident Id'));

if (ossim_error())
{
    echo ossim_error();
    exit();
}


$db     = new ossim_db();
$conn   = $db->connect();

$pro    = Session::is_pro();
$myself = Session::get_session_user();


$incident_list = Incident::search($conn, array('incident_id' => $id));

if (count($incident_list) != 1)
{
    echo ossim_error(_('Invalid ticket ID or insufficient permissions'));
    exit();
}

$incident = $incident_list[0];

//Incident data
$name               = $incident->get_ticket();
$title              = $incident->get_title();
$ref                = $incident->get_ref();
$type               = $incident->get_type();
$created            = $incident->get_date();
$life               = $incident->get_life_time();
$last_updated       = $incident->get_last_modification($conn);
$priority           = $incident->get_priority();
$incident_status    = $incident->get_status();
$incident_in_charge = $incident->get_in_charge();
$in_charge_name     = ($pro && valid_hex32($incident->get_in_charge())) ? Acl::get_entity_name($conn, $incident->get_in_charge()) : $incident->get_in_charge_name($conn);

$submitter          = $incident->get_submitter();
$submitter_data     = explode('/', $submitter);
$submitter_name     = $submitter_data[0];

$users              = Session::get_users_to_assign($conn, 'ORDER BY name ASC');
$entities           = Session::get_entities_to_assign($conn);

//Users to subscribe
if(Session::am_i_admin())
{
    $users_to_subscribe = $users;
}
else
{
    foreach($users as $u)
    {
        $login = $u->get_login();

        if ($login == $myself)
        {
            $users_to_subscribe[] = $u;
        }
        else
        {
            if (!Session::is_admin($conn, $login))
            {
                if ($pro && !Acl::am_i_proadmin() && !Acl::is_proadmin($conn, $login) > 0)
                {
                    $users_to_subscribe[] = $u;
                }
                elseif($pro && Acl::am_i_proadmin())
                {
                    $users_to_subscribe[] = $u;
                }
            }
        }
    }
}

$incident_tags      = $incident->get_tags();
$incident_tag       = new Incident_tag($conn);
$taga               = array();

foreach($incident_tags as $tag_id)
{
    $taga[] = $incident_tag->get_html_tag($tag_id);
}

$taghtm = count($taga) ? implode(' - ', $taga) : _('n/a');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title><?php echo gettext("OSSIM Framework"); ?> </title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>

    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/greybox.js"></script>

    <script type="text/javascript" src="../js/CLeditor/jquery.cleditor.min.js"></script>
    <script type="text/javascript" src="../js/CLeditor/jquery.cleditor.extimage.js"></script>

    <script type="text/javascript" src="../js/utils.js"></script>
    <script type="text/javascript" src="../js/notification.js"></script>
    <script type="text/javascript" src="../js/ajax_validator.js"></script>
    <script type="text/javascript" src="../js/messages.php"></script>
    <script type="text/javascript" src="../js/token.js"></script>
    <script type="text/javascript" src="../js/jquery.tipTip.js"></script>

    <script type="text/javascript" src="../js/av_map.js.php"></script>
    <script type="text/javascript" src="../js/av_breadcrumb.js.php"></script>

    <!-- markItUp!  -->
    <link rel="stylesheet" type="text/css" href="/ossim/js/markitup/skins/simple/style.css">
    <link rel="stylesheet" type="text/css" href="/ossim/js/markitup/sets/tickets/style.css">
    <script type="text/javascript" src="/ossim/js/markitup/jquery.markitup.js"></script>
    <script type="text/javascript" src="/ossim/js/markitup/sets/tickets/set.js.php"></script>
    <script type="text/javascript" src="/ossim/js/jquery.json-2.2.js"></script>

    <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    <link rel="stylesheet" type="text/css" href="../style/jquery.cleditor.css"/>
    <link rel="stylesheet" type="text/css" href="../style/tipTip.css"/>

    <script type="text/javascript">

        $.cleditor.buttons.image.uploadUrl = 'incident_upload_image.php';
        $.cleditor.buttons.image.prefix = '<?php echo $incident->get_id() ?>';

        // GreyBox
        function GB_edit(url)
        {
            GB_show("<?php echo _('Knowledge DB')?>",url,"80%","80%");

            return false;
        }
        
        function GB_onclose()
        {
            var url =  "/ossim/incidents/incident.php?id=<?php echo $id?>";

            document.location.href = url;
        }


        function switch_user(select)
        {
            if(select == 'entity' && $('#transferred_entity').val() != '')
            {
                $('#user').val('');
            }
            else if (select == 'user' && $('#transferred_user').val() != '')
            {
                $('#entity').val('');
            }
        }


        function delete_incident(id)
        {
            var msg = '<?php echo  Util::js_entities(_('This action will erase the Ticket as well as all the comments on this ticket. Do you want to continue?')) ?>';

            var dtoken = Token.get_token("delete_incident");

            if (confirm(msg))
            {
                $.ajax({
                    type: "POST",
                    url: "manageincident.php",
                    data: "action=delincident&incident_id="+id+"&token="+dtoken,
                    dataType: "json",
                    error: function(data){

                        if (typeof(data) != 'undefined' && data != null)
                        {
                            var error_data = data.responseText;

                            if (typeof(error_data) == 'string' && error_data != '' && error_data.match('c_nt_oss_error'))
                            {
                                $('body').html(error_data);
                            }
                        }
                    },
                    success: function(data){

                        if (typeof(data) != 'undefined' && data != null && data.status == 'OK')
                        {
                            document.location.href = 'index.php';
                        }
                        else if (typeof(data) != 'undefined' && data != null && data.status == 'error')
                        {
                            var msg = '';

                            for (var i in data.data)
                            {
                                if(typeof((data.data)[i]) == 'string'){
                                    msg += "<div class='error_"+i+" error_summary'>" + (data.data)[i] +"</div>";
                                }
                            }

                            var config_nt = {content:  msg,
                                options: {
                                    type: 'nf_error',
                                    cancel_button: true
                                },
                                style: 'width: 90%; margin: 10px auto; text-align:left;'
                            };

                            nt            = new Notification('nt_1', config_nt);
                            notification  = nt.show();

                            $('#av_info').html(notification);
                        }
                    }
                });
            }
        }


        function chg_prio_str()
        {
            var priority = $('#priority').val();

            if (priority > 7)
            {
                $('#prio_str').val('High');
            }
            else if (priority > 4)
            {
                $('#prio_str').val('Medium');
            }
            else
            {
                $('#prio_str').val('Low');
            }
        }


        function chg_prio_num()
        {
            var prio_str = $('#prio_str').val();

            if (prio_str == 'High')
            {
                $('#priority').val('8');
            }
            else if (prio_str == 'Medium')
            {
                $('#priority').val('5');
            }
            else
            {
                $('#priority').val('2');
            }
        }


        function email_changes(action)
        {
            if (action == 'subscribe')
            {
                $('#s_action').val('subscribe');
            }
            else if (action == 'unsubscribe')
            {
                $('#s_action').val('unsubscribe');
            }
            
            ajax_validator.submit_form();
        }


        $(document).ready(function()
        {
            <?php

            $items = array(
                'all' => array(
                    'title'  => _('Tickets'),
                    'action' => _('index.php')
                ),
                'ticket' => array(
                    'title'  => $title,
                    'action' => ''
                )
            );
            ?>

            var items = jQuery.parseJSON('<?php echo json_encode($items, JSON_HEX_TAG)?>');

            $('#ticket_bread_crumb').AVbreadcrumb(
            {
                'with_menu': false,
                'items': items
            });
            
            $('#custom_table tr:odd').addClass('odd');
            $('#custom_table tr:even').addClass('even');

            $('#priority').change(function() { chg_prio_str(); });
            $('#prio_str').change(function() { chg_prio_num(); });

            chg_prio_str();

            GB_TYPE = 'w';

            $("a.greybox").click(function()
            {
                var t = this.title || $(this).text() || this.href;

                GB_show(t,this.href,'75%','1100');

                return false;
            });

            $("a.greybox_2").click(function()
            {
                var t = this.title || $(this).text() || this.href;

                GB_show(t, this.href, "460", "700");

                return false;
            });


            $('.markit').markItUp(mySettings);

            $('#description, #action_txt').on('keyup', function(e){
                $(this).val(function(i, val) {
                    return val.replace(/[\t\r\b]/g, '');
                });

            });

            // Subscribe/Unsubscribe form

            var config = {
                validation_type: 'complete', // single|complete
                errors:{
                    display_errors: 'summary', //  all | summary | field-errors
                    display_in: 'av_info'
                },
                form : {
                    id  : 'f_subscribe',
                    url : $('#f_subscribe').attr('action')
                },
                actions: {
                    on_submit:{
                        id: 'send',
                        success: '<?php echo _('Save')?>',
                        checking: '<?php echo _('Saving')?>'
                    }
                }
            };

            ajax_validator = new Ajax_validator(config);


            $('#subscribe').click(function()   { email_changes('subscribe');   });
            $('#unsubscribe').click(function() { email_changes('unsubscribe'); });

            if ( $('#f_del_ticket').length >= 1)
            {
                var config_2 = {
                    validation_type: 'complete', // single|complete
                    errors:{
                        display_errors: 'summary', //  all | summary | field-errors
                        display_in: 'av_info'
                    },
                    form : {
                        id  : 'f_del_ticket',
                        url : $('#f_del_ticket').attr('action')
                    },
                    actions: {
                        on_submit:{
                            id: 'send',
                            success: '<?php echo _('Delete ticket')?>',
                            checking: '<?php echo _('Deleting ticket')?>'
                        }
                    }
                };

                ajax_validator_2 = new Ajax_validator(config_2);

                $('#del_ticket').click(function() { ajax_validator_2.submit_form(); });
            }


            //Ticket form
            var config_3 = {
                validation_type: 'complete', // single|complete
                errors:{
                    display_errors: 'all', //  all | summary | field-errors
                    display_in: 'av_info'
                },
                form : {
                    id  : 'f_new_ticket',
                    url : $('#f_new_ticket').attr('action')
                },
                actions: {
                    on_submit:{
                        id: 'send',
                        success: '<?php echo _('Save ticket')?>',
                        checking: '<?php echo _('Saving ticket')?>'
                    }
                }
            };

            ajax_validator_3 = new Ajax_validator(config_3);

            $('#submit_ticket').click(function() { ajax_validator_3.submit_form(); });

            //Greybox options

            if (!parent.is_lightbox_loaded(window.name))
            {
                $('.c_back_button').show();
            }

            $('a.new_comment').on('click', anchor_link);
            
            
            $('.ticket_body img').on('load', function()
            {
                $(this).on('click', function()
                {
                    var url = $(this).attr('src');
                    window.open(url);
                }).attr('title', "<?php echo _('Click here to view the original image.') ?>").tipTip();
                
            }).on('error', function()
            {
                $(this).off('click').attr('title', '').css('cursor', 'default');
            });
        });
        </script>


        <style type='text/css'>

            #c_tickets
            {
                margin: 15px 4px 10px 4px;
            }

            #t_container
            {
                width: 100%;
                border: none !important;
            }
            
            #t_container > tbody > tr > th
            {
                height: 22px;
                line-height: 22px;
            }
            
            #ticket_section_1
            {
                text-align:left;
                padding-left:10px;
                background-color: #efefef;
            }

            #ticket_section_2
            {
                text-align:left;
                padding-left:10px;
            }

            #in_charge_name
            {
                color: #11829D;
                font-weigth: bold;
            }

            #extra
            {
                text-align:left;
                padding-left:10px;
                background-color: #efefef;
            }

            .documents
            {
                 padding:0px 3px 0px 3px;
                 height: 18px;
             }

            .disabled img
            {
                filter:alpha(opacity=50);
                -moz-opacity:0.5;
                -khtml-opacity: 0.5;
                opacity: 0.5;
            }

            .email_changes
            { 
                padding-left: 20px;
                text-align: left;
            }

            #subscribe_section
            { 
                text-align: right;
                padding-right: 10px;
            }

            .i_ticket_header
            {
                width: 100%;
                white-space: nowrap;
                height: 22px;
            }
            
            #del_button_layer
            {
                margin: 20px auto;
                text-align: center;
                
            }

            #t_c_new_ticket
            {
                width: 100%;
                margin: 10px auto 10px 0; 
                border-collapse: collapse;
                border: none;
            }

            #t_c_new_ticket td
            {
                padding: 0px !important;
            }

            .t_new_ticket
            {
                border: none;
                text-align: left;
            }

            .t_new_ticket th
            {
                min-width: 110px;
            }

            .format_or
            {
                padding:0px 2px 0px 8px;
                text-align:right;
            }

            #user, #entity
            {
                text-align: center !important;
            }

            #user
            {
                width: 150px;
            }

            #entity
            {
                width: 220px;
            }

            #user option, #entity option
            {
                text-align: left;
            }

            #av_info
            {
                margin: 5px auto;
                width: 90%;
            }

            .markItUp
            {
                margin: 5px 0px 0;
            }

            .markit
            {
                width: 550px !important;
                height: 180px !important;
            }
            
            .ticket_body img
            {
                max-width: 90%;
                cursor: pointer;
                margin: 5px 0;
            }
            
            #submit_ticket
            {
                margin: 20px auto 10px auto;
            }
            
            #new_ticket_container
            {
                margin: 60px auto 20px auto; 
            }
            
            #ticket_status_table .t_title
            {
                width: 50%;
                text-align: left;
                font-weight: bold;
                text-transform: uppercase;
            }
            
            #ticket_status_table .t_val
            {
                width: 50%;
                text-align: left;
                padding-left: 10px;
            }
            
            .prio_ticket_container
            {
                float: left;
                height: 20px;
                line-height: 20px;
                margin-right: 15px;
            }
            
            .ticket_section_title
            {
                width: 20%;
                font-weight: bold;
                padding: 3px 0;
                float: left;
                clear:left;
            }
            
            .ticket_section_val
            {
                width: 80%;
                padding: 3px 0;
                float: right;
            }
            
        </style>

        <?php require "../host_report_menu.php" ?>
</head>

<body>


<div id='ticket_bread_crumb'></div>

<div id='av_info'></div>

<div id='c_tickets'>

    <table cellspacing="0" cellpadding="0" id='t_container'>
        <tr>
            <td colspan="6" class="sec_title"><?php echo _('Ticket Details')?></td>
        </tr>
        <tr>
            <th style="width:10%"><?php echo _('Ticket ID') ?> </th>
            <th style="width:50%"><?php echo _('Ticket') ?></th>
            <th style="width:70px"><?php echo _('Status') ?> </th>
            <th style="width:70px"><?php echo _('Priority') ?> </th>
            <th><?php echo _('Knowledge DB') ?> </th>
            <th><?php echo _('Action') ?> </th>
        </tr>

        <tr>
            <td><strong><?php echo $name?></strong></td>

            <td class="left" style='padding: 0'>
                <table width="100%" class="noborder">
                    <tr>
                        <td>
                            <table class="noborder" width="100%">
                                <tr>
                                    <td id='ticket_section_1'>
                                    <?php
                                        print_incident_fields(_('Name'), $title);
                                        print_incident_fields(_('Class'), $ref);
                                        print_incident_fields(_('Type'), $type);
                                        print_incident_fields(_('Created'), $created . ' ('.$life.')');
                                        print_incident_fields(_('Last Update'), $last_updated);
                                        
                                        if ($incident->get_status($conn) == "Closed")
                                        {
                                            print_incident_fields(_('Resolution time'), $incident->get_life_time());
                                        }    
                                    ?>   
                                    </td>
                                </tr>

                                <tr>
                                    <td id='ticket_section_2'>
                                    <?php
                                        print_incident_fields(_('In charge'), $in_charge_name);
                                        print_incident_fields(_('Submitter'), $submitter_name);  
                                    ?>
                                    </td>
                                </tr>

                                <tr>
                                    <td id='extra'>
                                    <?php
                                        print_incident_fields(_('Extra'), $taghtm); 
                                    ?>
                                    </td>
                                </tr>

                                <?php $td_st = ($ref == 'Custom') ? 'text-align:left;' : 'padding-left:10px; text-align:left;'; ?>

                                <tr>
                                    <td style='<?php echo $td_st?>'>
                                    <?php
                                    if ($ref == 'Alarm' or $ref == 'Event')
                                    {
                                        $alarm_list = ($ref == 'Alarm') ? $incident->get_alarms($conn) : $incident->get_events($conn);

                                        foreach($alarm_list as $alarm_data)
                                        {
                                            print_incident_fields(_('Source Ips'), $alarm_data->get_src_ips()); 
                                            print_incident_fields(_('Source Ports'), $alarm_data->get_src_ports()); 
                                            print_incident_fields(_('Dest Ips'), $alarm_data->get_dst_ips()); 
                                            print_incident_fields(_('Dest Ports'), $alarm_data->get_dst_ports()); 
                                        }
                                    }
                                    elseif ($ref == 'Metric')
                                    {
                                        $metric_list = $incident->get_metrics($conn);

                                        foreach($metric_list as $metric_data) 
                                        {
                                            print_incident_fields(_('Target'), $metric_data->get_target()); 
                                            print_incident_fields(_('Metric Type'), $metric_data->get_metric_type()); 
                                            print_incident_fields(_('Metric Value'), $metric_data->get_metric_value()); 
                                        }
                                    }
                                    elseif ($ref == 'Anomaly')
                                    {
                                        $anom_list = $incident->get_anomalies($conn);

                                        foreach($anom_list as $anom_data)
                                        {
                                            $anom_type   = $anom_data->get_anom_type();
                                            $anom_ip     = $anom_data->get_ip();
                                            $anom_info_o = $anom_data->get_data_orig();
                                            $anom_info   = $anom_data->get_data_new();

                                            if ($anom_type == 'mac')
                                            {
                                                list($a_sen, $a_date_o, $a_mac_o, $a_vend_o) = explode(",", $anom_info_o);
                                                list($a_sen, $a_date, $a_mac, $a_vend) = explode(",", $anom_info);
  
                                                print_incident_fields(_('Host'), $anom_ip); 
                                                print_incident_fields(_('Previous Mac'), "$a_mac_o ($a_vend_o)"); 
                                                print_incident_fields(_('New Mac'), "$a_mac ($a_vend)"); 
                                            }
                                            elseif ($anom_type == 'service')
                                            {
                                                list($a_sen, $a_date, $a_port, $a_prot_o, $a_ver_o) = explode(",", $anom_info_o);
                                                list($a_sen, $a_date, $a_port, $a_prot, $a_ver) = explode(",", $anom_info);
                                                
                                                print_incident_fields(_('Host'), $anom_ip);
                                                print_incident_fields(_('Port'), $a_port);
                                                print_incident_fields(_('Previous Protocol [Version]'), "$a_prot_o [$a_ver_o]");
                                                print_incident_fields(_('New Protocol [Version]'), "$a_prot [$a_ver]");
                                            }
                                            elseif ($anom_type == 'os')
                                            {
                                                list($a_sen, $a_date, $a_os_o) = explode(",", $anom_info_o);
                                                list($a_sen, $a_date, $a_os) = explode(",", $anom_info);
                                                
                                                print_incident_fields(_('Host'), $anom_ip);
                                                print_incident_fields(_('Previous OS'), $a_os_o);
                                                print_incident_fields(_('New OS'), $a_os);
                                            }
                                        }
                                    }
                                    elseif ($ref == 'Vulnerability')
                                    {
                                        $vulnerability_list = $incident->get_vulnerabilities($conn);

                                        foreach($vulnerability_list as $vulnerability_data)
                                        {
                                            $nessus_id = $vulnerability_data->get_nessus_id();

                                            $hostname_temp = Asset_host::get_name_by_ip($conn, $vulnerability_data->get_ip());
                                            $hostname_temp = array_shift($hostname_temp);
                                            
                                            print_incident_fields(_('IP'), $vulnerability_data->get_ip() . $hostname_temp);
                                            print_incident_fields(_('Port'), $vulnerability_data->get_port());
                                            print_incident_fields(_('Scanner ID'), $nessus_id);
                                            print_incident_fields(_('Risk'), $vulnerability_data->get_risk());
                                            print_incident_fields(_('Description'), nl2br($vulnerability_data->get_description()));
                                            
                                        }
                                    }
                                    elseif ($ref == 'Custom')
                                    {
                                        $custom_list = $incident->get_custom($conn);
                                        
                                        foreach($custom_list as $custom)
                                        {
                                            $c_val = Incident::format_custom_field($custom[3], $id,$custom[1], $custom[2]);
                                            
                                            print_incident_fields($custom[0], $c_val);
                                        }
                                    }
                                    ?>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
            <!-- End incident data -->

            <td valign='top'><?php Incident::colorize_status($incident->get_status($conn)) ?></td>

            <td valign='top'><?php echo Incident::get_priority_in_html($priority) ?></td>

            <td valign="top">
                <?php

                $has_found_keys = 0;
                $max_rows       = 10;

                list($linked_list, $has_linked) = Repository::get_list_bylink($conn, 0, -1, $incident->get_id(), "incident");

                ?>
                <table width="100%" style="border:none;background-color:#efefef">
                    <tr><th height="18"><?php echo _('Documents')?></th></tr>
                    <?php
                    $i = 0;
                    if (count($linked_list) == 0)
                    {
                        echo "<tr><td height='25'>"._('No linked documents')."</td></tr>";
                    }

                    $new_url  = Menu::get_menu_url("../repository/repository_newdocument.php", 'configuration', 'threat_intelligence', 'knowledgebase');

                    foreach($linked_list as $doc)
                    {
                        $repository_pag = floor($i / $max_rows) + 1;
                        ?>
                        <tr>
                            <td>
                                <a href="../repository/repository_document.php?id_document=<?php echo $doc->get_id() ?>" style="hover{border-bottom:0px}" class="greybox">
                                    <?php echo $doc->get_title() ?>
                                </a>
                            </td>
                        </tr>
                        <?php
                        $i++;
                    }
                    ?>

                    <tr>
                        <th nowrap='nowrap' class='documents'>
                            <img align='absmiddle' src="../repository/images/linked2.gif" border='0'/>
                            <a href="../repository/addrepository.php?id=<?php echo $id?>&id_link=<?php echo $id?>&type_link=incident" class='greybox_2' title='<?php echo _("Link existing document")?>'><?php echo _("Link existing document")?></a>
                        </th>
                    </tr>

                    <tr>
                        <th nowrap='nowrap' class='documents'>
                            <img align='absmiddle' src="../repository/images/editdocu.gif" border='0'/>
                            <a href="<?php echo $new_url ?>"><?php echo _("New document")?></a>
                        </th>
                    </tr>
                </table>
            </td>

            <td valign='top'>
                <table width="100%" class="noborder">
                    <tr>
                        <td style='white-space:nowrap;'>
                            <?php
                            if (Incident::user_incident_perms($conn, $id, 'delincident'))
                            {
                                ?>
                                <a href='newincident.php?action=edit&ref=<?php echo $ref?>&incident_id=<?php echo $id?>&edit=1'>
                                    <img src='../vulnmeter/images/pencil.png' border='0' align='absmiddle' title='<?php echo _("Edit ticket")?>'/>
                                </a>

                                <a onClick="delete_incident('<?php echo $id?>');">
                                    <img src='../pixmaps/delete.gif' border='0' align='absmiddle' title='<?php echo _("Delete ticket")?>'/>
                                </a>
                                <?php
                            }
                            else
                            {
                                ?>
                                <span class='disabled'>
                                    <img src='../vulnmeter/images/pencil.png' border='0' align='absmiddle' title='<?php echo _("Edit ticket")?>'/>
                                </span>

                                <span class='disabled'>
                                    <img src='../pixmaps/delete.gif' border='0' align='absmiddle' title='<?php echo _("Delete ticket")?>'/>
                                </span>
                                <?php
                            }
                            ?>

                            <a href='#new_comment' class='new_comment'>
                                <img src="../pixmaps/tables/table_row_insert.png" border="0" align="absmiddle" title="<?php echo _("New comment")?>"/>
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td nowrap='nowrap'><strong><?php echo _('Email changes to') ?>:</strong></td>

            <td class='email_changes'>
                <?php
                foreach($incident->get_subscribed_users($conn, $id) as $u)
                {
                    echo format_user($u, TRUE, TRUE) . '<br/>';
                }
                ?>
            </td>

            <td id='subscribe_section' nowrap='nowrap' colspan='4'>
                <form method="POST" name='f_subscribe' id='f_subscribe' action="manageincident.php?action=e_subscription&incident_id=<?php echo $id?>">
                    <input type="hidden" class='vfield' id="s_action" name="s_action" value=""/>

                    <select class='vfield' name="login" id='login'>
                        <?php
                        if (count($users_to_subscribe) == 0)
                        {
                            ?>
                            <option value="">- <?php echo _("No users found")?> -</option>
                            <?php
                        }
                        else
                        {
                            foreach($users_to_subscribe as $u)
                            {
                                ?>
                                <option value="<?php echo $u->get_login() ?>"><?php echo format_user($u, false)?></option>
                                <?php
                            }
                        }
                        ?>
                    </select>

                    <input type="button" class="av_b_secondary small" id="subscribe" name="subscribe" value="<?php echo _("Subscribe")?>"/>&nbsp;
                    <input type="button" class="av_b_secondary small" id="unsubscribe" name="unsubscribe" value="<?php echo _("Unsubscribe")?>"/>
                </form>
            </td>
        </tr>
    </table>
    <!-- end incident summary -->

    <br/>
    <!-- incident ticket list-->
    <?php
    $tickets_list = $incident->get_tickets($conn);

    for ($i = 0; $i < count($tickets_list); $i++)
    {
        $ticket        = $tickets_list[$i];
        $ticket_id     = $ticket->get_id();
        $date          = $ticket->get_date();
        $life_time     = Util::date_diff($date, $created);
        $creator       = $ticket->get_user();
        $in_charge     = $ticket->get_in_charge();
        $transferred   = $ticket->get_transferred();
        $creator       = Session::get_list($conn, "WHERE login='$creator'");
        $creator       = count($creator) == 1 ? $creator[0] : FALSE;


        if ($pro && valid_hex32($in_charge))
        {
            $in_charge_name = Acl::get_entity_name($conn, $in_charge);
        }
        else
        {
            $in_charge      = Session::get_list($conn, "WHERE login='$in_charge'");
            $in_charge      = count($in_charge) == 1 ? $in_charge[0] : FALSE;
            $in_charge_name = format_user($in_charge);
        }

        $is_transferred = FALSE;
        if (!empty($transferred))
        {
            $is_transferred = TRUE;
            if ($pro && valid_hex32($transferred))
            {
                $transferred_name = Acl::get_entity_name($conn, $transferred);

                if ($transferred_name == _('Unknown entity'))
                {
                    $is_transferred = FALSE;
                }
            }
            else
            {
                $transferred      = Session::get_list($conn, "WHERE login='$transferred'");
                $transferred      = count($transferred) == 1 ? $transferred[0] : FALSE;
                $transferred_name = format_user($transferred);
            }
        }


        $descrip     = $ticket->get_description();
        $action      = $ticket->get_action();
        $status      = $ticket->get_status();
        $prio        = $ticket->get_priority();
        $prio_str    = Incident::get_priority_string($prio);
        $prio_box    = Incident::get_priority_in_html($prio);

        if ($attach = $ticket->get_attachment($conn))
        {
            $file_id   = $attach->get_id();
            $file_name = $attach->get_name();
            $file_type = $attach->get_type();
        }
    ?>
    
    <br/><br/>
    <table width="100%" cellspacing="2" align="center">
        <!-- ticket head -->
        <tr>
            <th class='i_ticket_header' colspan="2">
                <strong><?php echo format_user($creator) ?></strong> - <?php echo $date?>
            </th>
        </tr>
        <!-- end ticket head -->

        <tr>
            <!-- ticket contents -->
            <td style="width:75%; background:rgb(244, 244, 244);" valign="top">
                <table style="border:none" width="100%" cellspacing="0">
                    <tr>
                        <td style="text-align:left;">
                            <?php
                            if ($attach)
                            {
                                ?>
                                <strong><?php echo _('Attachment') ?>: </strong>
                                <a href="attachment.php?id=<?php echo $file_id ?>"><?php echo htm($file_name) ?></a>
                                &nbsp;<i>(<?php echo $file_type ?>)</i><br/>
                                <?php
                            }
                            ?>

                            <strong><?php echo _('Description') ?></strong><p class="ticket_body"><?php echo $descrip?></p>
                            <?php
                            if ($action)
                            {
                                ?>
                                <strong><?php echo _('Action') ?></strong><p class="ticket_body"><?php echo $action?></p>
                                <?php
                            }
                            ?>
                        </td>
                    </tr>
                </table>
            </td>
            <!-- end ticket contents -->

            <!-- ticket summary -->
            <td style="width: 25%" valign="top">
                            
                <table id='ticket_status_table' class="noborder">
                    <tr>
                        <td class='t_title'><?php echo _('Status')?>:</td>
                        <td class='t_val'><?php Incident::colorize_status($status);?></td>
                    </tr>

                    <tr valign="middle">
                        <td class='t_title'><?php echo _('Priority') ?>:</td>
                        <td class='t_val'>
                            <div class='prio_ticket_container'>
                                <?php echo $prio_box?>
                            </div>
                            <div class='prio_ticket_container'>
                                <?php echo $prio_str?>
                            </div>
                        </td>
                    </tr>

                    <?php
                    if (!$is_transferred)
                    {
                        ?>
                        <tr>
                            <td class='t_title'><?php echo _('In charge') ?>:</td>
                            <td class='t_val'><?php echo $in_charge_name;?></td>
                        </tr>
                        <?php
                    }
                    else
                    {
                        ?>
                        <tr>
                            <td class='t_title'><?php echo _('Transferred To') ?>:</td>
                            <td class='t_val'><?php echo $transferred_name;?></td>
                        </tr>
                        <?php
                    }
                    ?>

                    <tr>
                        <td class='t_title'><?php echo _('Since Creation') ?>:</td>
                        <td class='t_val'><?php echo $life_time ?></td>
                    </tr>
                </table>
                
                <?php
                /* Check permissions to delete a ticket*/
                if (($i == count($tickets_list) - 1) && Incident_ticket::user_tickets_perms($conn, $ticket_id))
                {
                    $del_url = "manageincident.php?action=delete_ticket&ticket_id=$ticket_id&incident_id=$id";
                    ?>
                    <div id='del_button_layer'>
                        <form method="POST" name='f_del_ticket' id='f_del_ticket' action="<?php echo $del_url ?>">
                            <input type="button" name="del_ticket" id="del_ticket" class="av_b_secondary small" value="<?php echo _('Delete note') ?>">
                        </form>
                    </div>
                    <?php
                }
                ?>
                
            </td>
        </tr>
        <!-- end ticket summary -->
    </table>
    <?php
    }
    ?>
    
    <!-- form for new ticket -->
    <div id='new_ticket_container'>
    <form name="f_new_ticket" id="f_new_ticket" method="POST" action="manageincident.php?action=newticket&incident_id=<?php echo $id?>" enctype="multipart/form-data">

        <input type="hidden" name="prev_status" class='vfield' value="<?php echo $incident_status ?>"/>
        <input type="hidden" name="prev_prio"   class='vfield' value="<?php echo $priority ?>"/>
        <input type="hidden" name="edit"        class='vfield' value="<?php echo $edit ?>"/>
        <!-- DO NOT DELETE THIS FIELD. IT IS USED FOR THE UPLOAD IMAGE PROCESS OF THE WIKI EDITOR. -->
        <input type="hidden" name="ticket_id"   id='ticket_id' value="<?php echo $id ?>"/>

        <table id="t_c_new_ticket" class='transparent'>
            <tr>
                <td valign="top" style='width:80%'>
                    <table id="anchor" class='t_new_ticket'>
                        <tr>
                            <th>
                                <label for="status"><?php echo _('Status')?></label>
                            </th>
                            <td style="text-align: left">
                                <?php $ticket_status = array('Open', 'Assigned', 'Studying', 'Waiting', 'Testing', 'Closed');?>
                                <select class='vfield' id="status" name="status">
                                    <?php
                                    foreach ($ticket_status as $st)
                                    {
                                        $selected = ($incident_status == $st) ? "selected='selected'" : "";
                                        echo "<option value='$st' $selected>"._($st)."</option>";
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <th>
                                <label for="priority"><?php echo _('Priority')?></label>
                            </th>
                            <td style="text-align: left">
                                <select class='vfield' id='priority' name="priority">
                                    <?php
                                    for ($i = 1; $i <= 10; $i++)
                                    {
                                        $selected = ($priority == $i) ? "selected='selected'" : '';
                                        echo "<option value='$i' $selected>$i</option>";
                                    }
                                    ?>
                                </select>
                                <img src='../pixmaps/arrow-000-small.png' align='absmiddle' style='margin-right:3px;' title='<?php echo ("Arrow")?>'/>
                                <select id='prio_str' name="prio_str">
                                    <option value="Low"><?php echo _('Low') ?></option>
                                    <option value="Medium"><?php echo _('Medium') ?></option>
                                    <option value="High"><?php echo _('High') ?></option>
                                </select>
                             </td>
                        </tr>
                        
                        <tr>
                            <th>
                                <label for="user"><?php echo _('Transfer To')?></label>
                            </th>
                            <td class='left'>
                                <span><?php echo _('User:');?></span>
                                <select name="transferred_user" id="user" class='vfield' onchange="switch_user('user');return false;">
                                    <?php
                                    $num_users = 0;

                                    foreach($users as $k => $v)
                                    {
                                        $login = $v->get_login();
                                        if ($login != $incident_in_charge)
                                        {
                                            $options .= "<option value='".$login."'>".format_user($v, false)."</option>\n";
                                            $num_users++;
                                        }
                                    }

                                    if ($num_users == 0)
                                    {
                                        echo "<option value='' style='text-align:center !important;'>- "._("No users found")."- </option>";
                                    }
                                    else
                                    {
                                        echo "<option value='' style='text-align:center !important;' selected='selected'>- "._("Select one user")." -</option>\n";
                                        echo $options;
                                    }
                                    ?>
                                </select>

                                <?php
                                if (!empty($entities))
                                {
                                    ?>
                                    <label for="entity" class="format_or"><?php echo _('OR Entity');?>:</label>

                                    <select name="transferred_entity" class='vfield' id="entity" onchange="switch_user('entity');return false;">
                                        <?php
                                        unset($entities[$incident_in_charge]);

                                        if (count($entities) == 0)
                                        {
                                            echo "<option value='' style='text-align:center !important;'>- "._('No entities found')." -</option>";
                                        }
                                        else
                                        {
                                            echo "<option value='' style='text-align:center !important;'>- "._('Select one entity')." -</option>\n";
                                        }

                                        foreach ($entities as $k => $v)
                                        {
                                            echo "<option value='$k'>$v</option>";
                                        }
                                        ?>
                                    </select>
                                    <?php
                                }
                                ?>
                            </td>
                        </tr>

                        <tr>
                            <th>
                                <label for="attachment"><?php echo _('Attachment')?></label>
                            </th>
                            <td style="text-align: left">
                                <input type="file" class='vfield' name="attachment" size='40'/>
                            </td>
                        </tr>

                        <tr>
                            <th>
                                <label for="description"><?php echo _('Description') . required()?></label>
                            </th>
                            <td>
                                <a name="new_comment"></a>
                                <textarea name="description" id="description" class='vfield markit'></textarea>
                            </td>
                        </tr>

                        <tr>
                            <th>
                                <label for="action_txt"><?php echo _('Action')?></label>
                            </th>
                            <td>
                                <textarea name="action_txt" id="action_txt" class='vfield markit' ></textarea>
                            </td>
                        </tr>
                    </table>
                </td>

                <td style='width:20%' valign="top">
                    <table style="text-align: left;border:none">
                        <tr>
                            <th><?php echo _("Tags") ?></th>
                        </tr>
                        <?php
                        $i = 0;
                        foreach($incident_tag->get_list() as $t)
                        {
                            $i++;
                            ?>
                            <tr>
                                <td style="text-align: left" nowrap='nowrap'>
                                    <?php
                                    $checked = in_array($t['id'], $incident_tags) ? "checked='checked'" : '' ?>
                                    <input class='vfield' type="checkbox" id='tag_<?php echo $i?>' name="tags[]" value="<?php echo $t['id'] ?>" <?php echo $checked ?>/>
                                    <label title="<?php echo $t['descr'] ?>"><?php echo $t['name'] ?></label><br/>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </table>
                </td>
            </tr>
            <tr>
                <td class="center" colspan='3'>
                    <button id='submit_ticket' type="button"><?php echo _("Save ticket")?></button>
                </td>
            </tr>
        </table>
    </form>
    </div>
</div>

</body>
</html>
