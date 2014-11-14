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

// Check permissions
if (!Session::am_i_admin())
{
	 $config_nt = array(
		'content' => _("You do not have permission to see this section"),
		'options' => array (
			'type'          => 'nf_error',
			'cancel_button' => false
		),
		'style'   => 'width: 60%; margin: 30px auto; text-align:center;'
	);

	$nt = new Notification('nt_1', $config_nt);
	$nt->show();

	die();
}

if ( POST('id') != '' )
{
    $system_id = POST('id');
    $rpass     = POST('rpass');
    
    ossim_valid($rpass, OSS_PASSWORD, 'illegal:' . _('Remote Password'));
}
else
{
    $system_id = GET('id');
}

ossim_valid($system_id,   OSS_HEX, 'illegal:' . _('System ID'));

if (ossim_error())
{
    die(ossim_error());
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo _("OSSIM Framework") ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>

	<link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<link rel="stylesheet" type="text/css" href="/ossim/style/jquery-ui-1.7.custom.css"/>

	<script type="text/javascript" src="/ossim/js/jquery.min.js"></script>
	<script type="text/javascript" src="/ossim/js/greybox.js"></script>
	<script type="text/javascript" src="/ossim/js/urlencode.js"></script>
	<script type="text/javascript" src="/ossim/js/notification.js"></script>

    <script>
        $(document).ready(function() {
            $('#submit').on('click',function(e) {
                $(this).addClass('av_b_processing');
            });
        });
    </script>
    
</head>

<body>

	<div class='content'>

        <div id='w_notif'></div>
        
		<div style="padding:30px">

        <?php
            if ($system_id && $rpass)
            {
                try
                {
                    $data = Av_center::add_system($system_id, $rpass);
                	$config_nt = array(
                		'content' => sprintf(_("<< %s >> successfully authenticated"),"<b>".$data['hostname']."</b>"),
                		'options' => array (
                			'type'          => 'nf_success',
                			'cancel_button' => false
                		),
                		'style'   => 'width: 60%; margin: 10px auto 30px auto; text-align:center;'
                	);
                
                	$nt = new Notification('nt_1', $config_nt);
                	$nt->show();

                    Util::make_form("POST", AV_MAIN_PATH."/#configuration/deployment/components", "_top", "Close");
                    
                }
                catch(Exception $e)
                {
                	$config_nt = array(
                		'content' => $e->getMessage(),
                		'options' => array (
                			'type'          => 'nf_error',
                			'cancel_button' => false
                		),
                		'style'   => 'width: 80%; margin: 10px auto 30px auto; text-align:center;'
                	);
                
                	$nt = new Notification('nt_1', $config_nt);
                	$nt->show();

                    Util::make_form("POST", "add_system.php?id=".urlencode($system_id));
                    
                }                
            }
            else
            {
        ?>
            
            <form action="add_system.php" method="POST">
            <input type="hidden" name="id" value="<?php echo $system_id ?>">
            
                <label for="rpass"><?php echo _('Please enter the root password of the remote system in order to configure it.')?></label>
			    <br>
			    <input type="password" style="margin-top:10px;width:180px" name="rpass" id="rpass">
			    
			    <br/><br/>
			    
			    <input type="submit" id='submit' value="<?php echo _('Submit');?>"/>
            
            </form>
            
        <?php
            }
        ?>
            
		</div>

	</div>

</body>
</html>
