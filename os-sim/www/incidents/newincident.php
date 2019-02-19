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
Session::logcheck('analysis-menu', 'IncidentsOpen');

require_once 'incident_common.php';


$edit         = (GET('action') == 'edit') ? TRUE : FALSE;
$ref          =  GET('ref');
$custom_type  =  GET('type');


ossim_valid($ref,           OSS_LETTER,                             'illegal:' . _("Reference"));
ossim_valid($custom_type,   OSS_ALPHA, OSS_SPACE, OSS_NULLABLE,     'illegal:' . _("Custom type"));

if (ossim_error())
{
    echo ossim_error();
    exit();
}


$db   = new ossim_db();
$conn = $db->connect();

//Timezone
$tz     = Util::get_timezone();
$timetz = gmdate("U")+(3600*$tz);


//Edit incident
if ($edit)
{
    $action      = 'editincident';
    $incident_id = GET('incident_id');

    if (!ossim_valid($incident_id , OSS_DIGIT, 'illegal:' . _('Incidend ID')))
    {
        echo ossim_error();
        exit();
    }


    $list = Incident::get_list($conn, array("where" => " AND incident.id=$incident_id "));

    if (count($list) != 1)
    {
        echo ossim_error(_('Error! Incident ID not found in database'));
        exit();
    }

    $incident    = $list[0];
    $title       = $incident->get_title();
    $submitter   = $incident->get_submitter();
    $priority    = $incident->get_priority();

    $type        = $incident->get_type();
    $in_charge   = $incident->get_in_charge();
    $event_start = $incident->get_event_start();
    $event_end   = $incident->get_event_end();

    switch ($ref)
    {
        case 'Alarm':
            list($alarm)    = Incident_alarm::get_list($conn, "WHERE incident_alarm.incident_id=$incident_id");
            $src_ips        = $alarm->get_src_ips();
            $dst_ips        = $alarm->get_dst_ips();
            $src_ports      = $alarm->get_src_ports();
            $dst_ports      = $alarm->get_dst_ports();
            $backlog_id     = $alarm->get_backlog_id();
            $event_id       = $alarm->get_event_id();
            $alarm_group_id = $alarm->get_alarm_group_id();
        break;

        case 'Event':
            list($event)    = Incident_event::get_list($conn, "WHERE incident_event.incident_id=$incident_id");
            $src_ips        = $event->get_src_ips();
            $dst_ips        = $event->get_dst_ips();
            $src_ports      = $event->get_src_ports();
            $dst_ports      = $event->get_dst_ports();
        break;

        case 'Metric':
            list($metric)   = Incident_metric::get_list($conn, "WHERE incident_metric.incident_id=$incident_id");
            $target         = $metric->get_target();
            $metric_type    = $metric->get_metric_type();
            $metric_value   = $metric->get_metric_value();
        break;

        case 'Anomaly':
            list($anomaly)  = Incident_anomaly::get_list($conn, "WHERE incident_anomaly.incident_id=$incident_id");
            $anom_type      = $anomaly->get_anom_type();
            $anom_ip        = $anomaly->get_ip();
            $anom_data_orig = $anomaly->get_data_orig();
            $anom_data_new  = $anomaly->get_data_new();

            if ($anom_type == "mac")
            {
                list($a_sen, $a_date, $a_mac_o, $a_vend_o) = explode(",", $anom_data_orig);
                list($a_sen, $a_date, $a_mac, $a_vend) = explode(",", $anom_data_new);
            }
            elseif ($anom_type == "service") {
                list($a_sen, $a_date, $a_port, $a_prot_o, $a_ver_o) = explode(",", $anom_data_orig);
                list($a_sen, $a_date, $a_port, $a_prot, $a_ver) = explode(",", $anom_data_new);
            }
            elseif ($anom_type == "os") {
                list($a_sen, $a_date, $a_os_o) = explode(",", $anom_data_orig);
                list($a_sen, $a_date, $a_os) = explode(",", $anom_data_new);
            }
        break;

        case 'Vulnerability':
            list($vulnerability)    = Incident_vulnerability::get_list($conn, "WHERE incident_vulns.incident_id=$incident_id");
            $ip                     = $vulnerability->get_ip();
            $port                   = $vulnerability->get_port();
            $nessus_id              = $vulnerability->get_nessus_id();
            $risk                   = $vulnerability->get_risk();
            $description            = $vulnerability->get_description();
        break;

        case 'Custom':
            $custom_values          = Incident_custom::get_list($conn, "WHERE incident_custom.incident_id=$incident_id");
        break;
    }
}
else
{
    $action = 'newincident';

    foreach($_GET as $k => $v)
    {
        $_POST[$k] = $v;
    }

    //It's not necessary escaped single quotes
    $title = POST('title');
    $title = Util::htmlentities($title);

    $submitter    = POST('submitter');
    $priority     = POST('priority');
    $type         = POST('type');

    if ($ref == 'Alarm' || $ref == 'Event')
    {
        $src_ips      = POST('src_ips');
        $dst_ips      = POST('dst_ips');
        $src_ports    = POST('src_ports');
        $dst_ports    = POST('dst_ports');
        $backlog_id   = POST('backlog_id');
        $event_id     = POST('event_id');
        $alarm_gid    = POST('alarm_group_id');
        $event_start  = POST('event_start');
        $event_start  = (empty($event_start)) ? gmdate("Y-m-d H:i:s",$timetz) : $event_start;

        $event_end    = POST('event_end');
        $event_end    = (empty($event_end)) ? gmdate("Y-m-d H:i:s",$timetz) : $event_end;

        $validate = array (
            'src_ips'        => array('validation' => 'OSS_SEVERAL_IP_ADDRCIDR_0, OSS_NULLABLE',                  'e_message' => 'illegal:' . _('Source Ips')),
            'dst_ips'        => array('validation' => 'OSS_SEVERAL_IP_ADDRCIDR_0, OSS_NULLABLE',                  'e_message' => 'illegal:' . _('Dest Ips')),
            'src_ports'      => array('validation' => 'OSS_LETTER, OSS_DIGIT, OSS_PUNC, OSS_SPACE, OSS_NULLABLE', 'e_message' => 'illegal:' . _('Source Ports')),
            'dst_ports'      => array('validation' => 'OSS_LETTER, OSS_DIGIT, OSS_PUNC, OSS_SPACE, OSS_NULLABLE', 'e_message' => 'illegal:' . _('Dest Ports')),
            'backlog_id'     => array('validation' => 'OSS_HEX, OSS_NULLABLE',                                    'e_message' => 'illegal:' . _('Backlog ID')),
            'event_id'       => array('validation' => 'OSS_HEX, OSS_NULLABLE',                                    'e_message' => 'illegal:' . _('Event ID')),
            'alarm_group_id' => array('validation' => 'OSS_HEX, OSS_NULLABLE',                                  'e_message' => 'illegal:' . _('Alarm group ID')),
            'event_start'    => array('validation' => 'OSS_DATETIME, OSS_NULLABLE',                               'e_message' => 'illegal:' . _('Event start')),
            'event_end'      => array('validation' => 'OSS_DATETIME, OSS_NULLABLE',                               'e_message' => 'illegal:' . _('Event end'))
       );
    }
    elseif ($ref == 'Metric')
    {
        $target       = POST('target');
        $metric_type  = POST('metric_type');
        $metric_value = POST('metric_value');
        $event_start  = POST('event_start');
        $event_start  = (empty($event_start)) ? gmdate("Y-m-d H:i:s",$timetz) : $event_start;

        $event_end    = POST('event_end');
        $event_end    = (empty($event_end)) ? gmdate("Y-m-d H:i:s",$timetz) : $event_end;

        $validate = array (
            'target'          => array('validation' => 'OSS_TEXT, OSS_NULLABLE',                                  'e_message' => 'illegal:' . _('Target')),
            'metric_type'     => array('validation' => 'OSS_ALPHA, OSS_SPACE, OSS_NULLABLE',                      'e_message' => 'illegal:' . _('Metric Type')),
            'metric_value'    => array('validation' => 'OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_NULLABLE',            'e_message' => 'illegal:' . _('Metric Value')),
            'event_start'     => array('validation' => 'OSS_DATETIME, OSS_NULLABLE',                              'e_message' => 'illegal:' . _('Event start')),
            'event_end'       => array('validation' => 'OSS_DATETIME, OSS_NULLABLE',                              'e_message' => 'illegal:' . _('Event end'))
       );
    }
    elseif ($ref == 'Anomaly')
    {
        $anom_type    = POST('anom_type');
        $anom_ip      = POST('anom_ip');
        $a_sen        = POST('a_sen');

        preg_match("/\D*(\d+\.\d+\.\d+\.\d+)\D*/",$a_sen,$found);
        $a_sen        = $found[1];
        $port         = POST('port');
        $a_mac        = POST('a_mac');
        $a_mac_o      = POST('a_mac_o');
        $a_os         = POST('a_os');
        $a_os_o       = POST('a_os_o');
        $a_vend       = POST('a_vend');
        $a_vend_o     = POST('a_vend_o');
        $a_ver        = POST('a_ver');
        $a_ver_o      = POST('a_ver_o');
        $a_prot       = POST('a_prot');
        $a_prot_o     = POST('a_prot_o');

        $a_date       = POST('a_date');
        $a_date       = (empty($a_date)) ? gmdate('Y-m-d H:i:s', $timetz) : $a_date;

        $validate = array (
            'anom_ip'     =>  array('validation'  => 'OSS_FQDN_IP, OSS_NULLABLE',              'e_message' => 'illegal:' . _('Host')),
            'a_sen'       =>  array('validation' => 'OSS_FQDN_IP, OSS_NULLABLE',               'e_message' => 'illegal:' . _('Sensor')),
            'port'        =>  array('validation' => 'OSS_PORT, OSS_NULLABLE',                  'e_message' => 'illegal:' . _('Port')),
            'a_mac'       =>  array('validation' => 'OSS_MAC, OSS_NULLABLE',                   'e_message' => 'illegal:' . _('New MAC')),
            'a_mac_o'     =>  array('validation' => 'OSS_MAC, OSS_NULLABLE',                   'e_message' => 'illegal:' . _('Old MAC')),
            'a_os'        =>  array('validation' => 'OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE',   'e_message' => 'illegal:' . _('New OS')),
            'a_os_o'      =>  array('validation' => 'OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE',   'e_message' => 'illegal:' . _('Old OS')),
            'a_vend'      =>  array('validation' => 'OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE',   'e_message' => 'illegal:' . _('New Vendor')),
            'a_vend_o'    =>  array('validation' => 'OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE',   'e_message' => 'illegal:' . _('Old Vendor')),
            'a_ver'       =>  array('validation' => 'OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE',   'e_message' => 'illegal:' . _('New Version')),
            'a_ver_o'     =>  array('validation' => 'OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE',   'e_message' => 'illegal:' . _('Old Version')),
            'a_prot_o'    =>  array('validation' => 'OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE',   'e_message' => 'illegal:' . _('New Protocol')),
            'a_prot'      =>  array('validation' => 'OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE',   'e_message' => 'illegal:' . _('Old Protocol')),
            'a_date'      =>  array('validation' => 'OSS_DATETIME, OSS_NULLABLE',              'e_message' => 'illegal:' . _('When'))
       );
    }
    elseif ($ref == 'Vulnerability')
    {

        $ip           = POST('ip');
        $port         = POST('port');
        $risk         = POST('risk');
        $nessus_id    = POST('nessus_id');
        $description  = POST('description');

        // Validate first nessus_id for queries
        ossim_valid($nessus_id , OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _('Plugin ID'));
        if (ossim_error())
        {
            echo ossim_error();
            exit();
        }

        // new incident from Vulnerabilities section
        if ($nessus_id != '' && $title == '')
        {
            $result = $conn->Execute("SELECT name FROM vuln_nessus_plugins WHERE id = ?", array($nessus_id));
            $title  = ($result->fields["name"]=='') ? _("New Vulnerability ticket") : $result->fields["name"];
        }

        if ($nessus_id != '' && $description == '')
        {
            $result       = $conn->Execute("SELECT description FROM vuln_nessus_plugins WHERE id = ?", array($nessus_id));
            $description  = ($result->fields["description"]=='') ? '' : $result->fields["description"];
            $description  = str_replace(";", "\n",$description);
        }

        $validate = array (
            'ip'           => array('validation' => 'OSS_IP_ADDRCIDR_0, OSS_NULLABLE',                            'e_message' => 'illegal:' . _('Host')),
            'port'         => array('validation' => 'OSS_PORT, OSS_NULLABLE',                                     'e_message' => 'illegal:' . _('Port')),
            'risk'         => array('validation' => 'OSS_LETTER, OSS_DIGIT, OSS_PUNC, OSS_SPACE, OSS_NULLABLE',   'e_message' => 'illegal:' . _('Risk')),
            'nessus_id'    => array('validation' => 'OSS_LETTER, OSS_DIGIT, OSS_PUNC, OSS_SPACE, OSS_NULLABLE',   'e_message' => 'illegal:' . _('Plugin ID')),
            'description'  => array('validation' => 'OSS_NULLABLE, OSS_AT, OSS_TEXT, OSS_PUNC_EXT',               'e_message' => 'illegal:' . _('Description'))
       );
    }

    /* get default submitter info */
    if  (empty($submitter))
    {
        $session_info = Session::get_user_info($conn);
        $submitter    = $session_info->get_name().'/'.$session_info->get_login();
    }

    // Add common parameters validation rules
    $validate['title']     = array('validation' => "OSS_ALPHA, OSS_SPACE, OSS_PUNC_EXT, '\>'",                   'e_message' => 'illegal:' . _('Title'));
    $validate['priority']  = array('validation' => 'OSS_DIGIT',                                                  'e_message' => 'illegal:' . _('Priority'));
    $validate['type']      = array('validation' => 'OSS_ALPHA, OSS_PUNC_EXT, OSS_SPACE, OSS_SCORE, OSS_NULLABLE','e_message' => 'illegal:' . _('Type'));
    $validate['submitter'] = array('validation' => 'OSS_USER, OSS_PUNC, OSS_NULLABLE',                           'e_message' => 'illegal:' . _('Submitter'));

}

// ossim_valid
$validation_errors = validate_form_fields('POST', $validate);

if (is_array($validation_errors) && !empty($validation_errors))
{
    foreach ($validation_errors as $error)
    {
        echo ossim_error($error);
    }
    exit();
}

$users    = Session::get_users_to_assign($conn, 'ORDER BY name ASC');
$entities = Session::get_entities_to_assign($conn);

$form_url = "manageincident.php?action=$action&ref=$ref&incident_id=$incident_id";

if ($ref == 'Custom' && !empty($custom_type))
{
    $form_url .= "&type=$type";
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title> <?php echo _("OSSIM Framework");?> </title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no"/>

    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/jquery-ui.min.js"></script>
    <script type="text/javascript" src="../js/jquery.dynatree.js"></script>
    <script type="text/javascript" src="../js/utils.js"></script>

    <script type="text/javascript" src="../js/av_map.js.php"></script>
    <script type="text/javascript" src="../js/jquery.autocomplete_geomod.js"></script>
    <script type="text/javascript" src="../js/geo_autocomplete.js"></script>

    <script type="text/javascript" src="../js/notification.js"></script>
    <script type="text/javascript" src="../js/token.js"></script>
    <script type="text/javascript" src="../js/ajax_validator.js"></script>
    <script type="text/javascript" src="../js/messages.php"></script>
    <script type="text/javascript" src="../js/jquery.tipTip.js"></script>

    <link rel="stylesheet" type="text/css" href="../style/datepicker.css"/>
    <link rel="stylesheet" type="text/css" href="../style/jquery-ui.css"/>
    <link rel="stylesheet" type="text/css" href="../style/tree.css"/>
    <link rel="stylesheet" type="text/css" href="../style/jquery.autocomplete.css"/>
    <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    <link rel="stylesheet" type="text/css" href="../style/tipTip.css" />

    <script type="text/javascript">

        function switch_user(select)
        {
            if(select=='entity' && $('#transferred_entity').val() != '')
            {
                $('#user').val('');
            }
            else if (select=='user' && $('#transferred_user').val() != '')
            {
                $('#entity').val('');
            }
        }

        function delete_file(id)
        {
            $('#delfile_'+id).remove();
            $('#del_'+id).val("1");
        }

        //Function to filter '<' and '>' in alarm title
        function filterKey(e){

            var keynum = ''
            if(window.event)
            {
                // IE
                keynum = e.keyCode
            }
            else if(e.which)
            {
                // Netscape/Firefox/Opera
                keynum = e.which
            }

            if(keynum == 60 || keynum == 62)
            {
                return false;
            }

            return true;
        }

        $(document).ready(function() {

            Token.add_to_forms();

            $(".info").tipTip({maxWidth: '400px'});

            var config = {
                validation_type: 'complete', // single|complete
                errors:{
                    display_errors: 'all', //  all | summary | field-errors
                    display_in: 'av_info'
                },
                form : {
                    id  : 'f_incident',
                    url : $('#f_incident').attr('action')
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

            $('#send').click(function(){ ajax_validator.submit_form();});

            //Greybox options

            if (!parent.is_lightbox_loaded(window.name))
            {
                $('.c_back_button').show();
            }
        });
    </script>

    <style type='text/css'>

        input[type='text']
        {
            width: 99%;
            height: 18px;
        }

        textarea
        {
            width: 99%;
            height: 45px;
        }

        .legend
        {
            font-size: 10px;
            font-style: italic;
            text-align: center;
            padding: 0px 0px 5px 0px;
            margin: 40px auto 0px auto;
            width: 400px;
        }

        #c_ni_title
        {
            width: 98%;
            margin: 15px auto 10px auto;
        }

        #priority, #type
        {
            min-width: 140px;
        }

        option
        {
            height: 15px;
        }


        .field_fix
        {
            width: 98%;
            height:100%;
        }

        .bold
        {
            font-weight: bold;
        }

        #c_incident
        {
            width: 750px;
            margin: auto;
        }

        #t_incident
        {
            width: 100%;
            margin: 0px auto 20px auto;
        }

        #t_incident .thi
        {
            padding: 10px 5px !important;
            min-width: 150px;
        }

        .ct_slider
        {
            margin: 7px 7px 7px 3px;
            float: left;
            width: 90%;
        }

        a.ui-slider-handle
        {
            height: 0.9em !important;
            width: 0.9em !important;
        }

        .ac_results li img
        {
            float: left;
            margin-right: 5px;
        }

        .format_or
        {
            padding:0px 2px 0px 5px;
            text-align:right;
        }

        #user
        {
            text-align: center !important;
            min-width: 150px;
            max-width: 200px;
        }

        #entity
        {
            text-align: center !important;
            min-width: 180px;
            max-width: 250px;
        }

        #user option
        {
            text-align: left;
        }

        #entity option
        {
            text-align: left;
        }

        #av_info
        {
            margin: 10px auto;
            text-align: left;
            width: 650px;
        }

    </style>
</head>

<body>

    <div class='c_back_button'>
         <input type='button' class="av_b_back" onclick="document.location.href='../incidents/index.php';return false;"/>
    </div>

    <div id='av_info'>
        <?php
        if (is_array($validation_errors) && !empty($validation_errors))
        {
            $txt_error = "<div>"._("The following errors occurred").":</div>
                      <div style='padding:10px;'>".implode("<br/>", $validation_errors)."</div>";

            $config_nt = array(
                'content' => $txt_error,
                'options' => array (
                    'type'          => 'nf_error',
                    'cancel_button' => false
                ),
                'style'   => 'width: 80%; margin: 20px auto; text-align: left;'
            );

            $nt = new Notification('nt_1', $config_nt);
            $nt->show();
        }
        ?>
    </div>

    <div id='c_incident'>

        <div class="legend">
            <?php echo _('Values marked with (*) are mandatory');?>
        </div>


        <form name='f_incident' id='f_incident' method="POST" action="<?php echo $form_url?>" enctype="multipart/form-data">

            <input type="hidden" class='vfield' name="action"      id="action"      value="<?php echo $action?>"/>
            <input type="hidden" class='vfield' name="ref"         id="ref"         value="<?php echo $ref?>"/>
            <input type="hidden" class='vfield' name="incident_id" id="incident_id" value="<?php echo $incident_id?>"/>
            <input type="hidden" class='vfield' name="submitter"   id="submitter"   value="<?php echo $submitter?>"/>

            <table id='t_incident' align="center">

                <thead>
                    <tr>
                        <th class="headerpr" colspan="2"> <?php echo (!$incident_id) ? _('New Ticket') : _('Edit Ticket') ?></th>
                    </tr>
                </thead>


                <!-- Title -->
                <tr>
                    <th class='thi'>
                        <label for="title"><?php echo _('Title'). required()?></label>
                    </th>
                    <td class='left'>
                        <input type="text" class='vfield' id='title' name="title" onkeypress="return filterKey(event)" value="<?php echo $title?>"/>
                    </td>
                </tr>

                <!-- Assign to -->
                <tr>
                    <th class='thi'>
                        <label for="user"><?php echo _('Assign To'). required()?></label>
                    </th>
                    <td class='left'>
                        <span><?php echo _('User:');?></span>
                        <select class='vfield' name="transferred_user" id="user" onchange="switch_user('user');return false;">
                            <?php
                            $num_users = 0;
                            foreach($users as $k => $v)
                            {
                                $login = $v->get_login();

                                $selected = ($login == $in_charge) ? "selected='selected'": '';
                                $options .= "<option value='".$login."' $selected>".format_user($v, false)."</option>\n";
                                $num_users++;
                            }

                            if ($num_users == 0)
                            {
                                echo "<option value='' style='text-align:center !important;'>- "._('No users found')." -</option>";
                            }
                            else
                            {
                                echo "<option value='' style='text-align:center !important;'>- "._('Select one user')." -</option>\n";
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

                                if (count($entities) == 0){
                                    echo "<option value='' style='text-align:center !important;'>- "._('No entities found')." -</option>";
                                }
                                else
                                {
                                    echo "<option value='' style='text-align:center !important;'>- "._('Select one entity')." -</option>\n";
                                }

                                foreach ($entities as $k => $v)
                                {
                                    $selected = ($k == $in_charge) ? "selected='selected'": '';
                                    echo "<option value='$k' $selected>$v</option>";
                                }
                                ?>
                            </select>
                            <?php
                        }
                        ?>
                    </td>
                </tr>

                <!-- Priority -->
                <tr>
                    <th class='thi'>
                        <label for="priority"><?php echo _('Priority'). required()?></label>
                    </th>
                    <td class="left">
                        <select class='vfield' id="priority" name="priority">
                            <?php
                            for ($i = 1; $i <= 10; $i++)
                            {
                                $selected = ($priority == $i) ? "selected='selected'" : '';
                                echo "<option value='$i' $selected>$i</option>";
                            }
                            ?>
                        </select>
                    </td>
                </tr>


                <!-- Type -->
                <tr>
                    <th class='thi'>
                        <label for="type"><?php echo _('Type'). required()?></label>
                    </th>
                    <?php
                    if ($ref == "Custom")
                    {
                        ?>
                        <td class='left'>
                            <span class='bold'><?php echo $type?></span>
                            <input type='hidden' class='vfield' name='type' id='type' value='<?php echo $type?>'/>
                        </td>
                        <?php
                    }
                    else
                    {
                        Incident::print_td_incident_type($conn, $type);
                    }
                    ?>
                </tr>

                <?php

                //Help legends
                $ips_legend  = _('You can type one unique IP Address or an IP list separated by commas: IP1, IP2, IP3...');
                $date_legend = _('Datetime format allowed is').': YYYY-MM-DD HH:MM:SS';

                if ($ref == 'Alarm' || $ref == 'Event')
                {
                    ?>
                    <tr>
                        <th class='thr'><?php echo _('Source Ips')?></th>
                        <td class="left">
                            <input type="hidden" class='vfield' id='backlog_id'     name="backlog_id"     value="<?php echo $backlog_id?>"/>
                            <input type="hidden" class='vfield' id='event_id'       name="event_id"       value="<?php echo $event_id?>"/>
                            <input type="hidden" class='vfield' id='alarm_group_id' name="alarm_group_id" value="<?php echo $alarm_gid?>"/>

                            <input type="text" class='vfield info' id='src_ips' name="src_ips" title='<?php echo $ips_legend?>' value="<?php echo $src_ips?>"/>
                        </td>
                    </tr>

                    <tr>
                        <th class='thr'>
                            <label for="dst_ips" class="format_or"><?php echo _('Dest Ips');?></label>
                        </th>
                        <td class="left">
                            <input type="text" class='vfield info' id='dst_ips' name="dst_ips" title='<?php echo $ips_legend?>' value="<?php echo $dst_ips?>"/>
                        </td>
                    </tr>

                    <tr>
                        <th class='thr'>
                            <label for="src_ports"><?php echo _('Source Ports');?></label>
                        </th>
                        <td class="left">
                            <input type="text" class='vfield' id='src_ports' name="src_ports" value="<?php echo $src_ports?>"/>
                        </td>
                    </tr>

                    <tr>
                        <th class='thr'>
                            <label for="dst_ports"><?php echo _('Dest Ports');?></label>
                        </th>
                        <td class="left">
                            <input type="text" class='vfield' id='dst_ports' name="dst_ports" value="<?php echo $dst_ports?>"/>
                        </td>
                    </tr>

                    <tr>
                        <th class='thr'>
                            <label for="event_start"><?php echo _('Start of related events');?></label>
                        </th>
                        <td class="left">
                            <input type="text" class='vfield info' id='event_start' title='<?php echo $date_legend?>' name="event_start" value="<?php echo $event_start?>"/>
                        </td>
                    </tr>

                    <tr>
                        <th class='thr'>
                            <label for="event_end"><?php echo _('End of related events');?></label>
                        </th>
                        <td class="left">
                            <input type="text" class='vfield info' id='event_end' title='<?php echo $date_legend?>' name="event_end" value="<?php echo $event_end?>"/>
                        </td>
                    </tr>
                    <?php
                }
                elseif ($ref == 'Metric')
                {
                    ?>
                    <tr>
                        <th class='thr'>
                            <label for="target"><?php echo _('Target (net, ip, etc)')?></label>
                        </th>
                        <td class="left">
                            <input type="text" class='vfield' id='target' name="target" value="<?php echo Util::htmlentities($target)?>"/>
                        </td>
                    </tr>

                    <tr>
                        <th class='thr'>
                            <label for="metric_type"><?php echo _('Metric type')?></label>
                        </th>
                        <td class="left">
                            <select class='vfield' id='metric_type' name="metric_type">
                                <?php
                                $metric_types = array("Compromise" => "Compromise", "Attack" => "Attack", "Level" => "Level");
                                foreach ($metric_types as $k => $v)
                                {
                                    $selected = ($metric_type == $k) ? "selected='selected'" : '';
                                    echo "<option value='$k' $selected>$v</option>";
                                }
                                ?>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th class='thr'>
                            <label for="metric_value"><?php echo _('Metric value')?></label>
                        </th>
                        <td class="left">
                            <input type="text" class='vfield' id='metric_value' name="metric_value" value="<?php echo $metric_value?>"/>
                        </td>
                    </tr>

                    <tr>
                        <th class='thr'>
                            <label for="event_start"><?php echo _('Start of related events')?></label>
                        </th>
                        <td class="left">
                            <input type="text" class='vfield info' id='event_start' title='<?php echo $date_legend?>' name="event_start" value="<?php echo $event_start?>"/>
                        </td>
                    </tr>

                    <tr>
                        <th class='thr'>
                            <label for="event_end"><?php echo _('End of related events')?></label>
                        </th>
                        <td class="left">
                            <input type="text" class='vfield info' id='event_end' title='<?php echo $date_legend?>' name="event_end" value="<?php echo $event_end?>"/>
                        </td>
                    </tr>
                    <?php
                }
                elseif ($ref == 'Anomaly')
                {
                    ?>
                    <tr style='display:none;'>
                        <th class='thr'>
                            <label for="anom_type"><?php echo _('Anomaly type')?></label>
                        </th>
                        <td class="left">
                            <input type="hidden" class='vfield' id='anom_type' name="anom_type" value="<?php echo Util::htmlentities($anom_type) ?>"/>
                        </td>
                    </tr>

                    <tr>
                        <th class='thr'>
                            <label for="anom_ip"><?php echo _('Host')?></label>
                        </th>
                        <td class="left">
                            <input type="text" class='vfield' id='anom_ip' name="anom_ip" value="<?php echo $anom_ip?>"/>
                        </td>
                    </tr>

                    <tr>
                        <th class='thr'>
                            <label for="a_sen"><?php echo _('Sensor')?></label>
                        </th>
                        <td class="left">
                            <input type="text" class='vfield' id='a_sen' name="a_sen"  value="<?php echo $a_sen?>"/>
                        </td>
                    </tr>

                    <?php
                    if ($anom_type == 'mac')
                    {
                        ?>
                        <tr>
                            <th class='thr'>
                                <label for="a_mac_o"><?php echo _('Old MAC')?></label>
                            </th>
                            <td class="left">
                                <input type="text" class='vfield' id='a_mac_o' name="a_mac_o" value="<?php echo $a_mac_o?>"/>
                            </td>
                        </tr>

                        <tr>
                            <th class='thr'>
                                <label for="a_mac"><?php echo _('New MAC')?></label>
                            </th>
                            <td class="left">
                                <input type="text" class='vfield' id='a_mac' name="a_mac" value="<?php echo $a_mac?>"/>
                            </td>
                        </tr>

                        <tr>
                            <th class='thr'>
                                <label for="a_vend_o"><?php echo _('Old vendor')?></label>
                            </th>
                            <td class="left">
                                <input type="text" class='vfield' id='a_vend_o' name="a_vend_o" value="<?php echo Util::htmlentities($a_vend_o)?>"/>
                            </td>
                        </tr>

                        <tr>
                            <th class='thr'>
                                <label for="a_vend"><?php echo _('New vendor')?></label>
                            </th>
                            <td class="left">
                                <input type="text" class='vfield' id='a_vend' name="a_vend" value="<?php echo Util::htmlentities($a_vend)?>"/>
                            </td>
                        </tr>
                        <?php
                        }
                    elseif ($anom_type == 'os')
                    {
                        ?>
                        <tr>
                            <th class='thr'>
                                <label for="a_os_o"><?php echo _('Old OS')?></label>
                            </th>
                            <td class="left">
                                <input type="text" class='vfield' id='a_os_o' name="a_os_o" value="<?php echo Util::htmlentities($a_os_o)?>"/>
                            </td>
                        </tr>

                        <tr>
                            <th class='thr'>
                                <label for="a_os"><?php echo _('New OS')?></label>
                            </th>
                            <td class="left">
                                <input type="text" class='vfield' id='a_os' name="a_os" value="<?php echo Util::htmlentities($a_os)?>"/>
                            </td>
                        </tr>
                        <?php
                    }
                    elseif ($anom_type == 'service')
                    {
                        ?>
                        <tr>
                            <th class='thr'>
                                <label for="a_port"><?php echo _('Port')?></label>
                            </th>
                            <td class="left">
                                <input type="text" class='vfield' id='a_port' name="a_port" value="<?php echo Util::htmlentities($a_port)?>"/>
                            </td>
                        </tr>

                        <tr>
                            <th class='thr'>
                                <label for="a_prot_o"><?php echo _('Old Protocol')?></label>
                            </th>
                            <td class="left">
                                <input type="text" class='vfield' id='a_prot_o' name="a_prot_o" value="<?php echo Util::htmlentities($a_prot_o)?>"/>
                            </td>
                        </tr>

                        <tr>
                            <th class='thr'>
                                <label for="a_ver_o"><?php echo _('Old Version')?></label>
                            </th>
                            <td class="left">
                                <input type="text" class='vfield' id='a_ver_o' name="a_ver_o" value="<?php echo Util::htmlentities($a_ver_o)?>"/>
                            </td>
                        </tr>

                        <tr>
                            <th class='thr'>
                                <label for="a_prot"><?php echo _('New Protocol')?></label>
                            </th>
                            <td class="left">
                                <input type="text" class='vfield' id='a_prot' name="a_prot" value="<?php echo Util::htmlentities($a_prot)?>"/>
                            </td>
                        </tr>

                        <tr>
                            <th class='thr'>
                                <label for="a_ver"><?php echo _('New Version')?></label>
                            </th>
                            <td class="left">
                                <input type="text" class='vfield' id='a_ver' name="a_ver" value="<?php echo Util::htmlentities($a_ver)?>"/>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>

                    <tr>
                        <th class='thr'>
                            <label for="a_date"><?php echo _('When')?></label>
                        </th>
                        <td class="left">
                            <input type="text" class='vfield info' id='a_date' name="a_date" title='<?php echo $date_legend?>' value="<?php echo $a_date?>"/>
                        </td>
                    </tr>
                    <?php
                }
                elseif ($ref == 'Vulnerability')
                {
                    ?>
                    <tr>
                        <th class='thr'>
                            <label for="ip"><?php echo _('IP')?></label>
                        </th>
                        <td class="left">
                            <input type="text" class='vfield' id='ip' name="ip" value="<?php echo $ip?>"/>
                        </td>
                    </tr>

                    <tr>
                        <th class='thr'>
                            <label for="port"><?php echo _('Port')?></label>
                        </th>
                        <td class="left">
                            <input type="text" class='vfield' id='port' name="port" value="<?php echo $port?>"/>
                        </td>
                    </tr>

                    <tr>
                        <th class='thr'>
                            <label for="nessus_id"><?php echo _('Plugin ID')?></label>
                        </th>
                        <td class="left">
                            <input type="text" class='vfield' id='nessus_id' name="nessus_id" value="<?php echo $nessus_id?>"/>
                        </td>
                    </tr>

                    <tr>
                        <th class='thr'>
                            <label for="risk"><?php echo _('Risk')?></label>
                        </th>
                        <td class="left">
                            <input type="text" class='vfield' id='risk' name="risk" value="<?php echo $risk?>"/>
                        </td>
                    </tr>

                    <tr>
                        <th class='thr'>
                            <label for="description"><?php echo _('Description')?></label>
                        </th>
                        <td class='left'>
                            <textarea class='vfield' id='description' name="description" rows="10" cols="80"><?php echo $description?></textarea>
                        </td>
                    </tr>
                    <?php
                }
                elseif ($ref == 'Custom')
                {
                    $fields       = Incident_custom::get_custom_types($conn,$type);
                    $form_builder = new Form_builder();
                    $params       = array();
                    $cont         = 1;

                    if (empty($fields))
                    {
                        echo "<tr>
                                <td class='nobborder' colspan='2'>";

                                $config_nt = array(
                                    'content' => _("You don't have added any custom types or your custom types have been deleted"),
                                    'options' => array (
                                        'type'          => 'nf_info',
                                        'cancel_button' => false
                                    ),
                                    'style'   => 'width: 90%; margin: 5px auto; text-center: left; padding:5px;'
                                );

                                $nt = new Notification('c_nt_oss_error', $config_nt);
                                $nt->show();

                        echo "  </td>
                            </tr>";
                    }
                    else
                    {
                        $req_f_inherent = array('Asset', 'Slider');
                        $wf1_types      = array ('Select box', 'Date','Date Range', 'Checkbox', 'Radio button');

                        foreach ($fields as $field)
                        {
                            $conf       = $GLOBALS['CONF'];
                            $map_key    = $conf->get_conf('google_maps_key');
                            $params     = get_params_field($field, $map_key);

                            $elem_name  = utf8_decode($field['name']);
                            $elem_name .= ($field['required'] == 1 && !in_array($field['type'], $req_f_inherent)) ? required() : '';

                            echo "<tr id='item_".$cont."'>
                                    <th id='name_".$cont."' class='thr'><label for='".$params['name']."'>$elem_name</label></th>
                                    <td style='border-width: 0px;text-align:left'>";



                            $form_builder->set_attributes($params);

                            $default_value = null;
                            if (is_object($custom_values[$field['name']]))
                            {
                                $default_value = $custom_values[$field['name']]->get_content();
                                $type          = $custom_values[$field['name']]->get_type();
                                $id            = $custom_values[$field['name']]->get_id();
                            }

                            if (!empty($default_value) && $type == 'File')
                            {
                                echo "<div style='padding-bottom: 3px; text-align: left' id='delfile_".$params['id']."'>";
                                    echo Incident::format_custom_field($id, $incident_id, $default_value, $type);
                                    echo "<span style='margin-left: 3px'>
                                            <a style='cursor:pointer' onclick=\"delete_file('".$params['id']."')\"><img src='../pixmaps/delete.gif' align='absmiddle' title='"._("Delete File")."'/></a>
                                          </span>";
                                echo "</div>";

                                echo "<input type='hidden' class='vfield' name='del_".$params['name']."' id='del_".$params['id']."' value='0'/>";
                            }

                            echo $form_builder->draw_element($field['type'], $default_value);

                            echo "</td>
                            </tr>";

                            $cont++;
                        }

                        if (is_object($form_builder))
                        {
                            ?>
                            <script type='text/javascript'>
                                <?php echo $form_builder->get_def_funcs();?>
                                <?php echo $form_builder->get_scripts();?>
                            </script>
                            <?php
                        }
                    }
                }
                ?>

                <tr>
                    <td colspan="2" class="noborder" style='padding: 10px 0px'>
                        <input type="button" id='send' name='send'  value="<?php echo _('Save')?>"/>
                    </td>
                </tr>

            </table>
        </form>
    </div>
</body>
</html>
