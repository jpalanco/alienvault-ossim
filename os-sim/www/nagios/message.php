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


//Checking if we have permissions to go through this section
Session::logcheck('environment-menu', 'MonitorsAvailability');


$msg = GET('msg');

ossim_valid($msg, OSS_DIGIT, 'illegal:' . _('Message ID'));

if (ossim_error()) 
{
    die(ossim_error());
}


$config_nt        = array();
$show_loading_box = FALSE;
$remote_login     = FALSE;

switch($msg)
{
    case 1:     
        //No sensor available option

        $config_nt = array(
            'content' => _('No available Nagios sensors'),
            'options' => array (
                'type' => 'nf_warning'
            ),
            'style'   => 'width: 80%; margin: 30px auto; text-align:center;'
        ); 

    break;

    case 2:     
        //The sensor we are trying to connrect is unreachable

        $config_nt = array(
            'content' => _('Unable to connect to Nagios sensor'),
            'options' => array (
                'type' => 'nf_warning'
            ),
            'style'   => 'width: 80%; margin: 30px auto; text-align:center;'
        ); 

    break;

    case 3:     
        //Loading message

        $show_loading_box = TRUE;

    break;

    case 4:          
        //Loading message and connection to the remote ossim sensor (ONLY OSSIM SENSORS!!)

        $_ip    = $_SESSION['_remote_nagios_credential'][0];    //Remote IP
        $_login = $_SESSION['_remote_nagios_credential'][1];    //Login for the remote sensor

        unset($_SESSION['_remote_nagios_credential']);

        if(!empty($_ip) && !empty($_login))
        {
            $remote_login = TRUE;
        }

    break;
}
?>



<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title><?php echo _('OSSIM Framework');?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>

    <meta http-equiv="Pragma" content="no-cache"/>

    <link rel="stylesheet" type="text/css" href="/ossim/style/jquery-ui-1.7.custom.css"/>
    <link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    
    
    <!-- Jquery and Jquery UI -->
    <script src="/ossim/js/jquery.min.js"></script>
    <script src="/ossim/js/jquery-ui.min.js"></script>

    <script type="text/javascript" src="/ossim/js/notification.js"></script>
    <script type='text/javascript' src='/ossim/js/utils.js'></script>
    
    <script>
        <?php 
        if($remote_login) 
        {     //Javascript functions for loading the remote nagios once we are logged in the system
            ?>
            var count = false;
            function load_nagios() 
            {
                if(count)
                {
                    $('#remote_login').remove();
                    document.location.href='https://<?php echo $_ip ?>/secured_nagios3/cgi-bin/status.cgi?hostgroup=all';
                }
                else
                {
                    count = true;
                    $('#remote_form').submit();
                }
            }
    
            <?php 
        }
        ?>   

        $(document).ready(function()
        {
            <?php 
            if($show_loading_box || $remote_login)  //Showing loading message
            {
                ?>
                show_loading_box('bg_container', '<?php echo Util::js_entities(_('Loading Nagios sensor, please wait...')) ?>', '');
                <?php 
            }
            ?>    
        });

    </script>
    
<body> 

    <?php 
    //If the remote nagios is a ossim sensor, we have to log in the remote machine. We need an iframe and a form to load the remote login into the iframe. Everything hidden
    if($remote_login) 
    {     

        $path = "https://$_ip/ossim/session/remote_login.php";
        ?>
        <div id='remote_login' style='display:none;'>
            <form id='remote_form' target="myIframe" action="<?php echo $path ?>" method="post">
                <input type="hidden" name='login' value="<?php echo $_login ?>" />
            </form>
            <iframe onload="load_nagios();" src="" name="myIframe" id="myIframe"></iframe>
        </div>    
        <?php 
    }
    ?> 

    <div id='bg_container' style='padding:5px;'>

        <?php
        //Showing notifications in case we have
        if(!empty($config_nt))  
        {   
            $nt = new Notification('nt_2', $config_nt);
            $nt->show();   
        }
        ?>
    </div>       

</body>

</html>