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

Session::logcheck('environment-menu', 'MonitorsAvailability');

$sensor      = GET('sensor');
$opc         = GET('opc');
$nagios_link = GET('nagios_link');


ossim_valid($sensor,        OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_NULLABLE,   'illegal:' . _('Sensor'));
ossim_valid($nagios_link,   OSS_TEXT, OSS_NULLABLE, "\/\?\=\.\-\_",         'illegal:' . _('Nagios Link'));
ossim_valid($opc,           OSS_ALPHA, OSS_NULLABLE,                        'illegal:' . _('Default option'));


if (ossim_error()) 
{
    die(ossim_error());
}

?>
<html>
    <head>
        <title><?php echo _('OSSIM');?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
        <meta http-equiv="Pragma" content="no-cache"/>
        <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
        <link rel="stylesheet" type="text/css" href="../style/environment/availability/common.css"/>
        <link rel="stylesheet" type="text/css" href="/ossim/style/jquery-ui-1.7.custom.css"/>
        
        <!-- Jquery and Jquery UI -->
        <script src="/ossim/js/jquery.min.js"></script>
        <script src="/ossim/js/jquery-ui.min.js"></script>

        <script type="text/javascript" src="/ossim/js/notification.js"></script>
        <script type='text/javascript' src='/ossim/js/utils.js'></script>
        <script type='text/javascript'>

        $(document).ready(function() 
        {      
            $('#nagios_fr').load(function() 
            {
                $('#nagios_fr').height('');
                
                var content_h = $('#nagios_fr').contents().find('body').outerHeight(true);

                $('#nagios_fr').height(content_h);
            });
        });

        </script>

    </head>
    <body>
        <?php 
        require_once 'menu.php';             
        ?>
                        
        <iframe src='message.php?msg=3' name='nagios' id='nagios_fr' style='width:100%;min-height:600px' frameborder='0' scrolling="no"></iframe>

    </body>   
</html>

