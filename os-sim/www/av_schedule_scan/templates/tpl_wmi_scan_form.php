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

Session::logcheck('environment-menu', 'AlienVaultInventory');

/****************************************************
********************* Tooltips  *********************
****************************************************/

$title = _('Format example').': wmihost:<i>ip_address</i>;wmiuser:<i>user</i>;wmipass:<i>pass</i>';



/****************************************************
 ***************** WMI Credentials  *****************
 ****************************************************/

if($params != '')
{
    preg_match('/wmipass:(.*)/', $params, $matches);

    if ($matches[1] != '')
    {
        $params               = preg_replace('/wmipass:(.*)/', '', $params);
        $_SESSION['wmi_pass'] = $matches[1];
        $params               = $params . 'wmipass:' . preg_replace('/./', '*', $matches[1]);
    }
}

session_write_close();
?>


<table id='t_avi'>
    <tr>
        <td class="left">
            <label for="task_name"><?php echo _('Name') . required();?></label>
        </td>
    </tr>
    <tr>
        <td class="left">
            <input type='text' name='task_name' id='task_name' class='vfield' value="<?php echo $name?>"/>
        </td>
    </tr>

    <tr>
        <td class="left">
            <label for="task_sensor"><?php echo _('Sensor') . required();?></label>
        </td>
    </tr>
    <tr>
        <td class="left">
            <select name="task_sensor" id="task_sensor" class='vfield'>
                <?php
                foreach ($sensors as $s_id => $s_data)
                {
                    $selected = ($s_id == $sensor_id) ? 'selected="selected"' : '';

                    echo "<option value='$s_id' $selected>".$s_data['name']."</option>";
                }
                ?>
            </select>
        </td>
    </tr>

    <tr>
        <td class="left">
            <label for="task_params"><?php echo _('Credentials') . required()?></label>
        </td>
    </tr>
    <tr>
        <td class="left">
            <input type='text' name='task_params' data-title="<?php echo $title?>" id='task_params' class='vfield info' value="<?php echo $params?>"/>
        </td>
    </tr>

    <tr>
        <td class="left">
            <label for="task_period"><?php echo _('Frequency') . required()?></label>
        </td>
    </tr>
    <tr>
        <td class="left">
            <select name="task_period" id="task_period" class='vfield'>
                <?php
                foreach ($frequencies as $f_seconds => $f_name)
                {
                    $selected = ($period == $f_seconds) ? 'selected="selected"' : '';

                    echo "<option value='$f_seconds' $selected>$f_name</option>";
                }
                ?>
            </select>
        </td>
    </tr>
</table>
