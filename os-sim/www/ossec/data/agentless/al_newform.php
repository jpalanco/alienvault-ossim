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


require_once dirname(__FILE__) . '/../../conf/config.inc';

Session::logcheck('environment-menu', 'EventsHidsConfig');  

$db    = new ossim_db();
$conn  = $db->connect();

$array_types = array (
    'ssh_integrity_check_bsd'     => 'Integrity Check BSD',
    'ssh_integrity_check_linux'   => 'Integrity Check Linux',
    'ssh_generic_diff'            => 'Generic Command Diff',
    'ssh_pixconfig_diff'          => 'Cisco Config Check',
    'ssh_foundry_diff'            => 'Foundry Config Check',
    "ssh_asa-fwsmconfig_diff"     => 'ASA FWSMconfig Check');


$step           = POST('step');
$validate_step  = GET ('step');
$back           = (!empty($_POST['back'])) ? 1 : NULL;
$info_error     = NULL;

/*
Test values

$hostname    = "Host";
$ip          = "192.168.10.15";
$user        = "admin"; 
$pass        = "pass";
$passc       = "pass";
$ppass       = "pass";
$ppassc      = "pass";
$descr       = "hola";

*/

if ($step == 1)
{
    $action_form = 'al_newform.php?step=1';
}
else
{   
    $action_form = 'al_newform.php';
}


//Ajax validation

if ($validate_step == '1')
{
    $validate = array (
        'type'        => array('validation' => 'OSS_NOECHARS, OSS_SCORE, OSS_LETTER',   'e_message' => 'illegal:' . _('Type')),
        'frequency'   => array('validation' => 'OSS_DIGIT',                             'e_message' => 'illegal:' . _('Frequency')),
        'state'       => array('validation' => 'OSS_NOECHARS, OSS_SCORE, OSS_LETTER',   'e_message' => 'illegal:' . _('State')),
        'arguments'   => array('validation' => "OSS_NOECHARS, OSS_TEXT, OSS_AT, OSS_NULLABLE, OSS_PUNC_EXT, '\`', '\<', '\>'", 'e_message' => 'illegal:' . _('Arguments')));
}
else
{
    $validate = array (
        'hostname'    => array('validation' => 'OSS_NOECHARS, OSS_SCORE, OSS_LETTER, OSS_DIGIT, OSS_DOT', 'e_message' => 'illegal:' . _('Hostname')),
        'ip'          => array('validation' => 'OSS_IP_ADDR',                                             'e_message' => 'illegal:' . _('IP')),
        'sensor'      => array('validation' => 'OSS_HEX',                                                 'e_message' => 'illegal:' . _('Sensor')),
        'user'        => array('validation' => 'OSS_NOECHARS, OSS_ALPHA, OSS_PUNC_EXT',                   'e_message' => 'illegal:' . _('User')),
        'descr'       => array('validation' => 'OSS_NOECHARS, OSS_TEXT, OSS_SPACE, OSS_AT, OSS_NULLABLE', 'e_message' => 'illegal:' . _('Description')),
        'pass'        => array('validation' => 'OSS_PASSWORD',                                            'e_message' => 'illegal:' . _('Password')),
        'passc'       => array('validation' => 'OSS_PASSWORD',                                            'e_message' => 'illegal:' . _('Pass confirm')),
        'ppass'       => array('validation' => 'OSS_PASSWORD, OSS_NULLABLE',                              'e_message' => 'illegal:' . _('Privileged Password')),
        'ppassc'      => array('validation' => 'OSS_PASSWORD, OSS_NULLABLE',                              'e_message' => 'illegal:' . _('Privileged Password confirm')),
        'use_su'      => array('validation' => 'OSS_BINARY, OSS_NULLABLE',                                'e_message' => 'illegal:' . _('Option use_su')));
}

if (GET('ajax_validation') == TRUE)
{
    $data['status'] = 'OK';

    $validation_errors = validate_form_fields('GET', $validate);
    if (is_array($validation_errors) && !empty($validation_errors))
    {
        $data['status'] = 'error';
        $data['data']   = $validation_errors;
    }

    echo json_encode($data);
    exit();
}


if (POST('ajax_validation_all') == TRUE || $step == 1)
{
    $validation_errors = validate_form_fields('POST', $validate);
    $data['status']    = 'OK';

    if ($validate_step != 1)
    {
        if (POST('pass') != POST('passc'))
        {
            $validation_errors['pass'] = _('Password fields are different');
        }
                
        if (!empty($_POST['ppass']) && (POST('ppass') != POST('ppassc')))
        {
            $validation_errors['ppass'] = _('Privileged Password fields are different');
        }
    }
    
    $data['data'] = $validation_errors;

    if (POST('ajax_validation_all') == TRUE)
    {
        if (is_array($validation_errors) && !empty($validation_errors))
        {
            $data['status'] = 'error';
            echo json_encode($data);
        }
        else
        {
            $data['status'] = 'OK';
            echo json_encode($data);
        }
        
        exit();
    }
    else
    {
        if (empty($validation_errors))
        {
            if (!Token::verify('tk_al_new_form', $_POST['token']))
            {
               $validation_errors['token'] = _('A Cross-Site Request Forgery attempt has been detected or the token has expired');
            }
        }


        if (is_array($validation_errors) && !empty($validation_errors))
        {
            $info_error = '<div>'._('We Found the following errors').':</div><div style="padding:10px;">'.implode('<br/>', $validation_errors).'</div>';
        }
    }   
}

//Form actions
if(empty($step))
{
    unset($_SESSION['_al_new']);

    $sensor_id = GET('sensor');
    ossim_valid($sensor_id, OSS_HEX, 'illegal:' . _('Sensor'));
    
    if (!ossim_error()) 
    {   
        if (!Ossec_utilities::is_sensor_allowed($conn, $sensor_id))
        {
            ossim_set_error(_('Error! Sensor not allowed'));
        } 
    }
    
    if (ossim_error())
    {
        $info_error = ossim_get_error();
    }
    else
    {
        $sensor_name  = Av_sensor::get_name_by_id($conn, $sensor_id);
        
        $_SESSION['_al_new']['sensor']      = $sensor_id;
        $_SESSION['_al_new']['sensor_name'] = $sensor_name;
    }
}
elseif ($step == 1 || ($step == 2 && !empty($back)))
{
    $hostname    = $_SESSION['_al_new']['hostname'] = POST('hostname');
    $ip          = $_SESSION['_al_new']['ip']       = POST('ip');
    $user        = $_SESSION['_al_new']['user']     = POST('user');
    $pass        = $_SESSION['_al_new']['pass']     = POST('pass');
    $passc       = $_SESSION['_al_new']['passc']    = POST('passc');
    $ppass       = $_SESSION['_al_new']['ppass']    = POST('ppass');
    $ppassc      = $_SESSION['_al_new']['ppassc']   = POST('ppassc');
    $use_su      = $_SESSION['_al_new']['use_su']   = intval(POST('use_su'));
    $descr       = $_SESSION['_al_new']['descr']    = POST('descr');
    $sensor_id   = $_SESSION['_al_new']['sensor'];
    $sensor_name = $_SESSION['_al_new']['sensor_name'];
    
    if ($step == 1)
    {
        if (empty($info_error))
        {   
            try
            {
                $res = Ossec_agentless::save_in_db($conn, $ip, $sensor_id, $hostname, $user, $pass, $ppass, $use_su, $descr);
            }
            catch(Exception $e)
            {
                $info_error = $e->getMessage();
            }
        }
        
        if (!empty($ip))
        {
            try
            {
                $monitoring_entries = Ossec_agentless::get_list_m_entries($conn, $sensor_id, " AND ip = '$ip'");
            }
            catch(Exception $e)
            {
                $monitoring_entries = array();
                $error_m_entries    = $e->getMessage();
            }
        }


        if (!empty($info_error))
        {
            $step        = NULL;
            $display     = 'display: block;';
            $action_form = 'al_newform.php';
        }
    }
}
else if ($step == 2)
{
    if (isset($_POST['finish']))
    {
        header('Location: /ossim/ossec/agentless.php');
    }
}

$db->close();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title><?php echo _('OSSIM Framework');?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>

    <script type="text/javascript" src="/ossim/js/jquery.min.js"></script>  
    <script type="text/javascript" src="/ossim/js/jquery.elastic.source.js" charset="utf-8"></script>
    <script type="text/javascript" src="/ossim/js/jquery.tipTip-ajax.js"></script>

    <!-- Own libraries: -->
    <script type="text/javascript" src="/ossim/js/notification.js"></script>
    <script type="text/javascript" src="/ossim/js/ajax_validator.js"></script>
    <script type="text/javascript" src="/ossim/js/messages.php"></script>
    <script type="text/javascript" src="../../js/common.js"></script>
    <script type="text/javascript" src="../../js/ossec_msg.php"></script>
    <script type="text/javascript" src="/ossim/js/utils.js"></script>
    <script type="text/javascript" src="/ossim/js/token.js"></script>

    <link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    <link rel="stylesheet" type="text/css" href="/ossim/style/jquery-ui-1.7.custom.css"/>
    <link rel="stylesheet" type="text/css" href="/ossim/style/tipTip.css"/>

    <script type="text/javascript">

        function add_monitoring()
        {
            var form_id = $('form[method="post"]').attr("id");
            
            if (ajax_validator.check_form() != true)
            {
                if ($(".invalid").length >= 1)
                {
                    $(".invalid").get(0).focus();
                }
                
                return false;
            }
            
            //Show load info
            var l_content = '<img src="<?php echo OSSEC_IMG_PATH?>/loading.gif" border="0" align="absmiddle"/><span style="padding-left: 5px;">'+ossec_msg['add_m_entry']+'</span>';                                        
            
            $("#al_load").html(l_content);
            
            var token = Token.get_token('al_entries');

            $.ajax({
                type: "POST",
                url: "ajax/actions.php",
                data: $('#'+form_id).serialize() + "&action=add_monitoring_entry&token="+token,
                dataType: "json",
                error: function(data){

                    $("#info_error").html(notify_error(ossec_msg['unknown_error']));
                    $('#info_error').show();
                    
                    $('.add').val(labels['add']);
                },
                success: function(html){
                    
                    $("#al_load").html('');

                    if (typeof(html) != 'undefined' && html != null)
                    {
                        if (html.status == 'error')
                        {
                            if (typeof(html.data.html_errors) != 'undefined' && html.data.html_errors != '')
                            {
                                $("#info_error").html(notify_error(html.data.html_errors));
                                $('#info_error').show();
                            }
                            else
                            {
                                $('#info_error').html('');
                                $("#al_load").html("<div class='cont_al_message'><div class='al_message'>"+notify_error(html.data)+"</div></div>");
                                $("#al_load").fadeIn(2000);
                                $("#al_load").fadeOut(4000);
                            }
                        }
                        else
                        {
                            $('#info_error').html('');

                            if ($('.al_no_added').length >= 1){
                                $('.al_no_added').remove(); 
                            }
                            
                            $('#t_body_mt').append(html.data);
                            
                            $('#t_body_mt tr td').removeClass('odd even');
                            $('#t_body_mt tr:even td').addClass('even');
                            $('#t_body_mt tr:odd td').addClass('odd');
                            
                            //Add new token
                            Token.add_to_forms();
                        }  
                    }

                    $('.add').val(labels['add']);
                }
            });
        }
    
    
        function delete_monitoring(id)
        {           
            //Show load info
            var l_content = '<img src="<?php echo OSSEC_IMG_PATH?>/loading.gif" border="0" align="absmiddle"/><span style="padding-left: 5px;">'+ossec_msg['delete_m_entry']+'</span>';                                     
            
            $("#al_load").html(l_content);

            
            var form_id = $('form[method="post"]').attr("id");
            var token   = Token.get_token('al_entries');

            $.ajax({
                type: "POST",
                url: "ajax/actions.php",
                data: $('#'+form_id).serialize() + "&action=delete_monitoring_entry&id="+id+"&token="+token,
                dataType: "json",
                error: function(data){
                    $("#al_load").html('');

                    $("#info_error").html(notify_error(ossec_msg['unknown_error']));
                    $('#info_error').show();
                    
                    $('.add').off('click');
                            
                    $('.add').val(labels['add']);
                    $('.add').click(function() {
                        add_monitoring(id);
                    });
                },
                success: function(html){
                    
                    $("#al_load").html('');
                    
                    if (typeof(html) != 'undefined' && html != null)
                    {
                        if (html.status == 'error')
                        {
                            if (typeof(html.data.html_errors) != 'undefined' && html.data.html_errors != '')
                            {
                                $("#info_error").html(notify_error(html.data.html_errors));
                                $('#info_error').show();
                            }
                            else
                            {
                                $("#info_error").html('');
                                
                                $("#al_load").html("<div class='cont_al_message'><div class='al_message'>"+notify_error(html.data)+"</div></div>");
                                $("#al_load").fadeIn(2000);
                                $("#al_load").fadeOut(4000);
                            }
                        }
                        else
                        {
                            $("#info_error").html('');
                            
                            $('#m_entry_'+id).remove();

                            if ($('#t_body_mt tr').length == 0)
                            {
                                var msg = "<tr class='al_no_added'><td class='noborder' colspan='5'><div class='al_info_added'><?php echo _("No monitoring entries added")?></div></td></tr>";
                                $('#t_body_mt').html(msg);
                            }
                            else
                            {
                                $('#t_body_mt tr td').removeClass('odd even');
                                $('#t_body_mt tr:even td').addClass('even');
                                $('#t_body_mt tr:odd td').addClass('odd');
                            }
                            
                            //Add new token
                            Token.add_to_forms();
                        }  
                    }

                    $('.add').off('click');

                    $('.add').val(labels['add']);
                    $('.add').click(function() {
                        add_monitoring(id);
                    });
                    
                }
            });
        }
        
        
        function add_values(id)
        {
            var type       = $("#al_type_"+id).text();
            var frequency  = $("#al_frequency_"+id).text();
            var state      = $("#al_state_"+id).text();
            var arguments  = $("#al_arguments_"+id).text();
            
            $('#type option').each(function(index) {
                if ($(this).text() == type)
                {
                    $('#type').val($(this).attr('value'));
                }
            });
            
            change_type(type);
            
            $('#frequency').val(frequency);
            $('#state').val(state);
            $('#arguments').val(arguments);
            
            $('.add').unbind('click');
            $('.add').val(labels['update']);
            
            $('.add').bind('click', function() {
                modify_monitoring(id);
            });
        }
        
        
        function modify_monitoring(id)
        {
            var form_id = $('form[method="post"]').attr("id");
            
            if (ajax_validator.check_form() != true)
            {
                if ($(".invalid").length >= 1)
                {
                    $(".invalid").get(0).focus();
                }
                
                $('.next').val(labels['add']);
                return false;
            }
            
            //Show load info
            var l_content = '<img src="<?php echo OSSEC_IMG_PATH?>/loading.gif" border="0" align="absmiddle"/><span style="padding-left: 5px;">'+ossec_msg['update_m_entry']+'</span>';                                     
            
            $("#al_load").html(l_content);
            
            var token = Token.get_token('al_entries');
                        
            $.ajax({
                type: "POST",
                url: "ajax/actions.php",
                data: $('#'+form_id).serialize() + "&action=modify_monitoring_entry&id="+id+"&token="+token,
                dataType: "json",
                error: function(data){
                    $("#al_load").html('');
                    
                    $("#info_error").html(notify_error(ossec_msg['unknown_error']));
                    $('#info_error').show();
                    
                    $('.add').off('click');
                            
                    $('.add').val(labels['add']);
                    $('.add').click(function() {
                        add_monitoring(id);
                    });
                },
                success: function(html){
                    
                    $("#al_load").html('');
                                        
                    if (typeof(html) != 'undefined' && html != null)
                    {
                        if (html.status == 'error')
                        {
                            if (typeof(html.data.html_errors) != 'undefined' && html.data.html_errors != '')
                            {
                                $("#info_error").html(notify_error(html.data.html_errors));
                                $('#info_error').show();
                            }
                            else
                            {
                                $("#info_error").html('');
                                
                                $("#al_load").html("<div class='cont_al_message'><div class='al_message'>"+notify_error(html.data)+"</div></div>");
                                $("#al_load").fadeIn(2000);
                                $("#al_load").fadeOut(4000);
                            }
                        }
                        else
                        {
                            $("#info_error").html('');
                            
                            $('#m_entry_'+id).html(html.data);
                            
                            $('#t_body_mt tr').removeClass('odd even');
                            $('#t_body_mt tr:even').addClass('even');
                            $('#t_body_mt tr:odd').addClass('odd');
                            
                            //Add new token
                            Token.add_to_forms();
                        }  
                    }
                    
                    $('.add').off('click');
                            
                    $('.add').val(labels['add']);
                    $('.add').click(function() {
                        add_monitoring(id);
                    });
                }
            });
        }
        
        
        function change_type(t_value)
        {
            if (t_value != '')
            {
                var type = t_value;
                $('#type').val(type);
            }
            else
            {
                var type = $('#type').val();
            }
                
            if (type.match("_diff") != null)
            {
                $('#state_txt').text("Periodic_diff");
                $('#state').val("periodic_diff");
            }
            else
            {
                if (type.match("_integrity") != null)
                {
                    $('#state_txt').html("Periodic");
                    $('#state').val("periodic");
                }
            }
        }
        
        function change_arguments()
        {
            var type = $('#type').val();
                                    
            if (type.match("_diff") != null)
            {
                $('#arguments').text("");
            }
            else if (type.match("_integrity") != null)
            {
                $('#arguments').text("/bin /etc /sbin");
            }
        }   

        $(document).ready(function(){
            
            
            $('#ppass').on('blur', function() 
            {
                var val = $(this).val();
                
                if(val == '')
                {
                    $('#use_su').prop('checked', false);
                }
                else
                {
                    $('#use_su').prop('checked', true);
                }
            });
            
            //Add token to form
            Token.add_to_forms();

            $('textarea').elastic();
                
            var config = {   
                validation_type: 'complete', // single|complete
                errors:{
                    display_errors: 'all', //  all | summary | field-errors
                    display_in: 'info_error'
                },
                form : {
                    id  : 'al_new_form',
                    url : "<?php echo $action_form;?>"
                },
                actions: {
                    on_submit:{
                        id: 'send',
                        success:  $('#send').val(),
                        checking: labels['saving']
                    }
                }
            };
        
            ajax_validator = new Ajax_validator(config);
            
            $('.next').bind('click', function() {
                
                if (ajax_validator.check_form() == true)
                {
                    var form_id = $('form[method="post"]').attr("id");
                    
                    $('#'+form_id).submit(); 
                }
                else
                {
                    if ($(".invalid").length >= 1)
                    {
                        $(".invalid").get(0).focus();
                    }
                    
                    $('#info_error').show();
                    $('.next').val("<?php echo _("Next >>")?>");
                }
            });

            $('.add').click(function() {
                add_monitoring();
                $('.add').val(labels['add']);
            });
            
            $('#type').change(function() {
                change_type('');
                change_arguments();
            });
            
            $('#t_body_mt tr').removeClass('odd even');
            $('#t_body_mt tr:even').addClass('even');
            $('#t_body_mt tr:odd').addClass('odd');
            
            $("#arguments").tipTip({maxWidth: 'auto'});
        });

    </script>

    <style type='text/css'>

        #subsection th
        {
            width: 190px;
        }

        .container_st1
        {
            width: 650px !important;
            border: solid 1px #D4D4D4 !important;
            border-collapse: collapse;
        }
        
        .container_st2
        {
            width: 650px !important;
            border: solid 1px #D4D4D4 !important;
            border-collapse: collapse;
        }

        .subsection
        {
            width: 100%;
            background: transparent;
            border: none;
        }

        .headerpr img
        {
            margin-right: 3px;
        }
        
        .cont_next 
        {
            border: none;
            padding: 10px;
        }

        .fleft
        { 
            width: 48%;
            float: left;
            text-align: left !important;
        }

        .fright
        {
            width: 48%;
            float: right;
            text-align: right !important;
        }

        #monitoring_table
        {
            border: solid 1px #D4D4D4 !important;
        }
        
    </style>
</head>

<body>

<?php
    //Local menu
    include_once AV_MAIN_ROOT_PATH.'/local_menu.php';
?>

<div class='c_back_button' style='display:block;'>
     <input type='button' class="av_b_back" onclick="document.location.href='/ossim/ossec/agentless.php';return false;"/>
</div>

<div id='info_error' style="<?php echo $display?>">
    <?php
 
    if (!empty($info_error))
    {
        $config_nt = array(
                'content' => $info_error,
                'options' => array (
                    'type'          => 'nf_error',
                    'cancel_button' => FALSE
                ),
                'style'   => 'width: 80%; margin: 20px auto; text-align: left;'
            ); 

        $nt = new Notification('nt_1', $config_nt);
        
        $nt->show();
    }
    ?>
</div>

<div class="legend">
    <?php echo _('Values marked with (*) are mandatory');?>
</div>  
  
<form method="POST" name="al_new_form" id="al_new_form" action="<?php echo $action_form;?>">

    <?php
         
    if (empty($step) || ($step == 2 && !empty($back)))
    {
        ?>

    <table class='container_st1' id='table_form'>
        <tr>
            <th class='headerpr_no_bborder'>
                <img src='<?php echo OSSIM_IMG_PATH?>/wand.png' alt='Wizard' style='vertical-align:middle; margin-right: 3px;'/>
                <span><?php echo _('Wizard: Step 1 of 2: Creating Host Configuration')?></span>
                <input type='hidden' name='step' id='step' value='1'/>
            </th>
        </tr>
        
        <tr>
            <td>
                <table class='subsection'>
                    <tr>
                        <th>
                            <label for='hostname'><?php echo _('Hostname') . required();?></label>
                        </th>
                        <td class="left">
                            <input type="text" class='vfield' name="hostname" id="hostname" value="<?php echo Util::htmlentities($hostname) ?>"/>
                        </td>
                    </tr>   
                    
                    <tr>
                        <th>
                            <label for='ip'><?php echo _('IP') . required();?></label>
                        </th>
                        
                        <td class="left">
                            <?php 
                            if ($step == 2 && !empty($back))
                            {
                                ?>
                                <div id="ip_back" class='bold'><?php echo $ip;?></div>
                                <input type="hidden" class='vfield' name="ip" id="ip" value="<?php echo Util::htmlentities($ip) ?>"/>
                                <input type="hidden" name="back" id="back" value="1"/>
                                <?php
                            }
                            else
                            {
                                ?>
                                <input type="text" class='vfield' name="ip" id="ip" value="<?php echo Util::htmlentities($ip) ?>"/>
                                <?php
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for='sensor'><?php echo _('Sensor') . required();?></label>
                        </th>
                        <td class="left">
                            <input type="hidden" class='vfield' name="sensor" id="sensor" value="<?php echo Util::htmlentities($sensor_id) ?>"/>
                            <div class='bold'>
                                <?php echo $sensor_name;?>
                            </div>
                        </td>
                    </tr>  
                    <tr>
                        <th>
                            <label for='user'><?php echo _('User') . required();?></label>
                        </th>
                        <td class="left">
                            <input type="text" class='vfield' name="user" id="user" value="<?php echo Util::htmlentities($user) ?>"/>
                        </td>
                    </tr>
                    
                    <tr>
                        <th>
                            <label for='pass'><?php echo _('Password') . required();?></label>
                        </th>
                        <td class="left">
                            <?php $pass = Util::fake_pass($pass); ?>
                            <input type="password" class='vfield' name="pass" id="pass" value="<?php echo $pass;?>" autocomplete="off"/>
                        </td>
                    </tr>

                    <tr>
                        <th>
                            <label for='passc'><?php echo _('Password confirm') . required()?></label>
                        </th>
                        <td class="left">
                            <?php  $passc = Util::fake_pass($passc); ?>
                            <input type="password" class='vfield' name="passc" id="passc" value="<?php echo $passc;?>" autocomplete="off"/>
                            <div class='al_advice'><?php echo _('(*) If you want to use public key authentication instead of passwords, you need to provide NOPASS as Normal Password ') ?></div>
                        </td>
                    </tr>
                        
                    <tr>
                        <th>
                            <label for='ppass'><?php echo _('Privileged Password');?></label>
                        </th>
                        <td class="left">
                            <?php  $ppass = Util::fake_pass($ppass); ?>
                            <input type="password" class='vfield' name="ppass" id="ppass" value="<?php echo $ppass;?>" autocomplete="off"/>
                        </td>
                    </tr>
                    
                    <tr>
                        <th>
                            <label for='ppassc'><?php echo _('Privileged Password confirm');?></label>
                        </th>
                        <td class="left">
                            <?php $ppassc = Util::fake_pass($ppassc); ?>
                            <input type="password" class='vfield' name="ppassc" id="ppassc" value="<?php echo $ppassc;?>" autocomplete="off"/>
                            <div class='al_advice'><?php echo _("(*) If you want to add support for \"su\", you need to provide Privileged Password") ?></div>
                        </td>
                        </td>
                    </tr>
                    
                    <tr>
                        <th>
                            <label for='use_su'><?php echo _('Enable use_su option');?></label>
                        </th>
                        <td class="left">
                            <input type="checkbox" class='vfield' name="use_su" id="use_su" value="1" <?php echo ($use_su)? "checked" : "" ?>/>
                        </td>
                    </tr>

                    <tr>
                        <th>
                            <label for='descr'><?php echo _('Description');?></label>
                        </th>
                        <td class="left noborder">
                            <textarea name="descr" id="descr" class='vfield'><?php echo Util::htmlentities($descr) ?></textarea>
                        </td>
                    </tr>
                    
                    <tr><td class='al_sep' colspan='2'></td></tr>
                    
                    <tr>
                        <td colspan="2" class="cont_next">
                            <div class='fright'><input type="button" class="next" id='send' value="<?php echo _('Next')." >>" ?>"/></div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    
    <?php 
    } 
    elseif ($step == 1)
    {
        ?>
    
    <table class='container_st2' id='table_form'>
    
        <tr>
            <th class='headerpr'>
                <img src='<?php echo OSSIM_IMG_PATH?>/wand.png' alt='Wizard' style='vertical-align:middle; margin-right: 3px;'/>
                <span><?php echo _('Step 2 of 2: Creating Monitoring Configuration')?></span>
                <input type='hidden' name='step'     id='step'     value='2'/>
                <input type='hidden' name='hostname' id='hostname' value='<?php echo Util::htmlentities($hostname)?>'/>
                <input type='hidden' name='ip'       id='ip'       value='<?php echo Util::htmlentities($ip)?>'/>
                <input type='hidden' name='sensor'   id='sensor'   value='<?php echo Util::htmlentities($sensor_id)?>'/>
                <input type='hidden' name='user'     id='user'     value='<?php echo Util::htmlentities($user)?>'/>
                <input type='hidden' name='pass'     id='pass'     value='<?php echo Util::htmlentities($pass)?>'/>
                <input type='hidden' name='passc'    id='passc'    value='<?php echo Util::htmlentities($passc)?>'/>
                <input type='hidden' name='ppass'    id='ppass'    value='<?php echo Util::htmlentities($ppass)?>'/>
                <input type='hidden' name='ppassc'   id='ppassc'   value='<?php echo Util::htmlentities($ppassc)?>'/>
                <input type='hidden' name='use_su'   id='use_su'   value='<?php echo Util::htmlentities($use_su)?>'/>
                <input type='hidden' name='descr'    id='descr'    value='<?php echo Util::htmlentities($descr)?>'/>
            </th>
        </tr>
    
        <tr>
            <td class='noborder'>
                <table class='subsection'>
                    <tr>
                        <th>
                            <label for='type'><?php echo _('Type') . required();?></label>
                        </th>
                        <td class="left">
                            <select name="type" id="type" class='vfield req_field'>
                            <?php
                                foreach ($array_types as $k => $v)
                                    echo "<option value='$k'>$v</option>";
                            ?>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th>
                        <label for='frequency'><?php echo _('Frequency') . required();?></label></th>
                        <td class="left">
                            <input type="text" class='vfield' name="frequency" id="frequency" value="86400"/>
                        </td>
                    </tr>
            
                    <tr>
                        <th>
                            <label for='state'><?php echo _('State');?></label>
                        </th>
                        <td class="left">
                            <div id="state_txt" class='bold'><?php echo _('Periodic')?></div>
                            <input type="hidden" class="state vfield" id='state' name='state' value="periodic"/>
                        </td>
                    </tr>
        
                    <tr>
                        <th>
                            <label for='arguments'><?php echo _('Arguments'); ?></label>
                        </th>
                        <td class="ct_mandatory noborder left">
                            <?php
                            $arg_info = "<table class='ct_opt_format' border='1'>
                                            <tbody>
                                                <tr><td class='ct_bold noborder center'><span class='ct_title'>"._('Please Note').":</span></td></tr>
                                                <tr>
                                                    <td class='noborder'>
                                                        <div class='ct_opt_subcont'>
                                                            <img src='".OSSIM_IMG_PATH."/bulb.png' align='absmiddle' alt='Bulb'/>
                                                            <span class='ct_bold'>"._("If type value is Generic Command Diff").":</span>
                                                            <div class='ct_pad5'>
                                                                <span>". _("Ex.: ls -la /etc; cat /etc/passwd")."</span>
                                                            </div>
                                                        </div>
                                                        <br/>
                                                        <div class='ct_opt_subcont'>
                                                            <img src='". OSSIM_IMG_PATH."/bulb.png' align='absmiddle' alt='Bulb'/>
                                                            <span class='ct_bold'>". _("Other cases").":</span>
                                                            <div class='ct_pad5'><span>"._("Ex.: bin /etc /sbin")."</span>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>";
                            ?>
                            <textarea name="arguments" id="arguments" class='vfield' title="<?php echo $arg_info?>">/bin /etc /sbin</textarea>
                        </td>
                    </tr>
        
                    <tr>
                        <td colspan='2' style='padding:5px 5px 5px 0px;' class='right noborder'>
                            <input type="button" class="small av_b_secondary add" name='add' id='send' value="<?php echo _('Add')?>"/>
                        </td>
                    </tr>
                        
                        
                    </tr>
                    
                    <tr><td class='al_sep' id='al_load' colspan='2'></td></tr>
                    
                    <tr>
                        <td class='noborder' colspan='2'>
                            <table class='subsection noborder' id='monitoring_table'>
                                <thead class='center'>
                                    <tr><th colspan='5' class='headerpr center;' style='padding: 3px 0px;'><?php echo _('Monitoring entries added')?></th></tr>
                                    <tr>
                                        <th class="al_type"><?php echo _('Type')?></th>
                                        <th class="al_frequency"><?php echo _('Frequency')?></th>
                                        <th class="al_state"><?php echo _('State')?></th>
                                        <th class="al_arguments"><?php echo _('Arguments')?></th>
                                        <th class="al_actions"><?php echo _('Actions')?></th>
                                    </tr>
                                </thead>
                                <tbody id='t_body_mt'>
                                    <?php 
                                    if (count($monitoring_entries) > 0)
                                    {
                                        foreach ($monitoring_entries as $k => $v)
                                        {
                                            echo "<tr id='m_entry_".$v['id']."'>
                                                    <td class='noborder center' id='al_type_$id'>". $v['type']."</td>
                                                    <td class='noborder center' id='al_frequency_".$v['id']."'>".$v['frequency']."</td>
                                                    <td class='noborder center' id='al_state_".$v['id']."'>".$v['state']."</td>
                                                    <td class='noborder left' id='al_arguments_".$v['id']."'>".$v['arguments']."</td>
                                                    <td class='center noborder'>
                                                        <a onclick=\"add_values('".$v['id']."')\"><img src='".OSSIM_IMG_PATH."/pencil.png' align='absmiddle' alt='"._('Modify monitoring entry')."' title='"._('Modify monitoring entry')."'/></a>
                                                        <a onclick=\"delete_monitoring('".$v['id']."')\" style='margin-right:5px;'><img src='".OSSIM_IMG_PATH."/delete.gif' align='absmiddle' alt='"._('Delete monitoring entry')."' title='"._('Delete monitoring entry')."'/></a>
                                                    </td>
                                                </tr>"; 
                                        }
                                    }
                                    else
                                    {
                                        $info_entries = ($error_m_entries != NULL) ? $error_m_entries : _('No monitoring entries added');
                                        echo "<tr class='al_no_added'><td class='noborder' colspan='5'><div class='al_info_added'>$info_entries</div></td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    
                    <tr><td class='al_sep' colspan='2'></td></tr>
                    
                    <tr>
                        <td colspan='2' class='cont_next'>
                            <div class='fleft'><input type="submit" class='av_b_secondary' id='back' name='back' value="<?php echo "<< "._('Back') ?>"/></div>
                            <div class='fright'><input type="submit" id='finish' name='finish' value="<?php echo _('Finish') ?>"/></div>
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
    <?php 
    } 
?>  

</form>

</body>
</html>