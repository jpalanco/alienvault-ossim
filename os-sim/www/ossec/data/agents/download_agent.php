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

$ossec_key = base64_encode('_download_oc_###'.Session::get_session_user());

if (isset($_SESSION[$ossec_key]) && $_GET['download_data'] == '1')
{    
    $agent_path  = base64_decode($_SESSION[$ossec_key]);
    $d_file_name = basename($agent_path);

    unset($_SESSION[$ossec_key]);

    $content_length = filesize($agent_path);

    if (file_exists($agent_path))
    {
        header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: public");
        header("Content-Disposition: attachment; filename=\"$d_file_name\";");
        header('Content-Type: application/force-download');
        header("Content-Transfer-Encoding: binary");
        header("Content-length: $content_length");

        ob_clean();
        flush();
        readfile($agent_path);
    }
    else
    {
        ?>
        <script type='text/javascript'>
            parent.$("#c_info").html(parent.notify_error('<?php echo _('Error! File has been deleted.  Please, try again')?>'));
            parent.$("#c_info").fadeIn(4000);
            parent.window.scrollTo(0,0);
        </script>
        <?php
    }

    exit();
}


$agent_id   = POST('agent_id');
$agent_type = POST('os_type');
$sensor_id  = POST('sensor_id');
$token      = POST('token');
            
$validate = array (
    'sensor_id'   => array('validation' => "OSS_HEX",              'e_message' => 'illegal:' . _('Sensor ID')),
    'agent_id'    => array('validation' => 'OSS_DIGIT',            'e_message' => 'illegal:' . _('Agent ID')),
    'os_type'     => array('validation' => "'regex:unix|windows'", 'e_message' => 'illegal:' . _('OS Type'))
);


$validation_errors = validate_form_fields('POST', $validate);

if (empty($validation_errors))
{
    if (!Token::verify('tk_f_ossec_agent', $token))
    {
        ?>
        <script type='text/javascript'>
            parent.hide_loading_box();
            parent.$("#c_info").html(parent.notify_error('<?php echo Token::create_error_message();?>'));
            parent.$("#c_info").fadeIn(4000);
            parent.window.scrollTo(0,0);
            parent.$('#c_ossec_agent').remove();
        </script>
        <?php
        exit();
    }
    
    $db    = new ossim_db();
    $conn  = $db->connect();
    $sensor_allowed = Ossec_utilities::is_sensor_allowed($conn, $sensor_id);
    $db->close();

    if (!$sensor_allowed)
    {
        ?>
        <script type='text/javascript'>
            parent.hide_loading_box();
            parent.$("#c_info").html(parent.notify_error('<?php echo _('Error! Sensor not allowed')?>'));
            parent.$("#c_info").fadeIn(4000);
            parent.window.scrollTo(0,0);
            parent.$('#c_ossec_agent').remove();
        </script>
        <?php
        exit();
    }


    try
    {
        $agent_path = Ossec_agent::download_agent($sensor_id, $agent_id, $agent_type);
    }
    catch(Exception $e)
    {
        $e_data = $e->getMessage();
        
        $errors = (preg_match('/Error!/',$e_data)) ? $e_data : _('Error!').'<br/>'.$e_data;
        ?>
        <script type='text/javascript'>

            var content = "<div style='padding-left:5px; text-align: left;'><?php echo $errors?></div>";

            parent.hide_loading_box();

            parent.$("#c_info").html(parent.notify_error(content));
            parent.$("#c_info").fadeIn(4000);
            parent.window.scrollTo(0,0);
            parent.$('#c_ossec_agent').remove();
        </script>  
        <?php
        exit();
    }



    $os_txt = ($type == 'windows') ? 'Windows' : 'UNIX';
    ?>
    <script type='text/javascript'>
         parent.$('.r_lp').html('<?php echo _("Downloading preconfigured agent for $os_txt")?> ...');
    </script>
    <?php


    $ossec_key            = base64_encode('_download_oc_###'.Session::get_session_user());
    $_SESSION[$ossec_key] = base64_encode($agent_path);

    ?>
    <script type='text/javascript'>
        parent.hide_loading_box();
        document.location.href = 'download_agent.php?download_data=1';
    </script>
    <?php
    exit();
}
else
{    
    $errors = implode('<br/>', $validation_errors);
    $errors = str_replace('"', '\"', $errors);
    ?>
    <script type='text/javascript'>

        var content = "<div style='text-align: left; padding-left:5px;'><?php echo _('We found the following errors')?>:</div>" +
                      "<div style='padding-left:15px; text-align: left;'><?php echo $errors?></div>";

        parent.hide_loading_box();

        parent.$("#c_info").html(parent.notify_error(content));
        parent.$("#c_info").fadeIn(4000);
        parent.window.scrollTo(0,0);
        parent.$('#c_ossec_agent').remove();
    </script>
    <?php
}
?>