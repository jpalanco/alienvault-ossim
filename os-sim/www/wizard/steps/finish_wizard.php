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

//Checking active session
Session::useractive();

//Checking permissions
if (!Session::am_i_admin())
{
    echo _('You do not have permissions to see this section');
    
    die();
}


try
{
    $db     = new ossim_db();
    $conn   = $db->connect();
    
    $alarms = Alarm::get_count($conn);
    
    $db->close();

}
catch(Exception $e)
{
    $alarms = 0;
}

$_version = Session::is_pro() ? " USM" : " OSSIM";

if ($alarms > 0)
{
    $msg = _('Data is now coming into AlienVault. AlienVault has generated a few alarms. You can either view the alarms or explore AlienVault') . $_version;
}
else
{
    $msg = _('Data is now coming into AlienVault. So far analysis has not generated any alarms. While you wait for more data to come in, you can continue configuring the system or start exploring AlienVault')  . $_version;
}


?>
<script type='text/javascript'>

    $(document).ready(function()
    {
        $('#exit_wizard').on('click', function()
        {
            exit_wizard(1, false);
        });
        
        $('#see_alarms').on('click', function()
        {
            exit_wizard(1, true);
        });
        
        
        $('#restart_wizard').on('click', function()
        {
            initialize_wizard();
        }); 
         
    });

</script>


<div class='wizard_finish_container'>

    <div class='wizard_title'>
        <?php echo _('Congratulations!') ?>
    </div>
    
    <div id='w_notif'></div>
    
    <div class='wizard_subtitle'>
        <?php echo $msg ?>
    </div>
    
    <div class='wizard_button_list'>
        
        <a href='javascript:;' id='exit_wizard' class='av_l_main w_link_middle'><?php echo  _('Explore AlienVault') . $_version ?></a>
        
        <?php
        if ($alarms > 0)
        {

            echo "<button id='see_alarms' class='fright'>". _('See Alarms') ."</button>";
        }
        else
        {
            echo "<button id='restart_wizard' class='fright'>". _('+ Configure More Data Sources') ."</button>";
        }
        ?>
    
    </div>

</div>

    


