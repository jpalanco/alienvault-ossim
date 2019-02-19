<?php
/**
*
* License:
*
* Copyright (c) 2003-2006 ossim.net
* Copyright (c) 2007-2015 AlienVault
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

Session::logcheck("dashboard-menu", "ControlPanelExecutive");

$error_type = GET('error_type');
$error_type = ($error_type != 'old_key') ? 'token' : 'old_key';

if ($error_type == 'old_key')
{
    $error_msg = _('OTX upgrade available. Please re-authenticate your OTX account to take advantage of the new features!');
}
else
{
    $error_msg = _('Connect your OTX account to get insight into emerging threats in your environment.');
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en">
    
<head>
    <title><?php echo _('Open Threat Exchange - Not Registered') ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>
        
    <?php
        //CSS Files
        $_files = array(
            array('src' => 'av_common.css?only_common=1',       'def_path' => TRUE),
            array('src' => 'dashboard/overview/widget.css',     'def_path' => TRUE)
        );
        
        Util::print_include_files($_files, 'css');
    
        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',     'def_path' => TRUE),
            array('src' => 'utils.js',          'def_path' => TRUE),
        );
        
        Util::print_include_files($_files, 'js');
    ?>
    
    <script class="code" type="text/javascript">
        
        function no_key()
        {
            var b_text = "<?php echo _('Connect Account') ?>";
            $('#connect_otx').html(b_text).show().on('click', function()
            {
                var url = '/otx/index.php?section=config'
                try
                {
                    url = top.av_menu.get_menu_url(url, 'configuration', 'otx', 'otx');
                    top.av_menu.load_content(url);
                }
                catch(Err)
                {
                    document.location.href = url
                }
            });
        }
        
        function old_key()
        {
            var b_text = "<?php echo _('Upgrade Account') ?>";
            $('#connect_otx').html(b_text).show().on('click', function()
            {
                var url = "<?php echo Otx::OTX_URL_UPGRADE_LOGIN ?>";
                av_window_open(url);
            });
        }
        
        $(document).ready(function()
        {
            var perms = <?php echo Session::am_i_admin() ? 'true' : 'false' ?>;
            var type  = "<?php echo $error_type ?>";
            
            if (perms)
            {
                if (type == 'old_key')
                {
                    old_key();
                }
                else
                {
                    no_key();
                }
            }
        });
    </script>
    
    <style>
        #not_registered_wrap
        {
            position: absolute;
            width: 100%;
            text-align: center;
            z-index: 10;
            top: 50%;
            margin-top: -60px;
        }
        
        #not_registered
        {
            font-size: 16px;
        }
        
        #connect_otx
        {
            margin-top: 30px;
            display: none;
        }
        
        #img_not_registered
        {
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 5;
        }
    </style>

</head>

<body style="overflow:hidden" scroll="no">
    
    <div id='not_registered_wrap'>
        
        <div id='not_registered'>
            <?php echo $error_msg ?>
        </div>
        
        <button id='connect_otx'></button>

    </div>
    
    <img id='img_not_registered' src='/ossim/dashboard/pixmaps/otx-register.png'/>
    
</body>

</html>
