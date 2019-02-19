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

$m_perms  = array ('environment-menu', 'environment-menu');
$sm_perms = array ('EventsHids', 'EventsHidsConfig');

$sensor_id  = POST('sensor_id');
$agent_id   = POST('agent_id');
$agent_name = POST('agent_name');

$_POST['agent_ip'] = strtolower(POST('agent_ip'));
$agent_ip = POST('agent_ip');


if (Session::menu_perms($m_perms, $sm_perms))
{
    try
    {
        ossim_valid($agent_id, OSS_DIGIT,                                                         'illegal:' . _('Agent ID'));
        ossim_valid($sensor_id, OSS_HEX,                                                          'illegal:' . _('Sensor ID'));
        ossim_valid($agent_name, OSS_SCORE, OSS_LETTER, OSS_DIGIT, OSS_DOT, OSS_SPACE, "(", ")",  'illegal:' . _('Agent Name'));
        
        if ($agent_ip != 'any')
        {
            ossim_valid($agent_ip, OSS_IP_ADDRCIDR, 'illegal:' . _('Agent IP'));
        }

        if (!ossim_error())
        {
            $db    = new ossim_db();
            $conn  = $db->connect();

            if (!Ossec_utilities::is_sensor_allowed($conn, $sensor_id))
            { 
                Av_exception::throw_error(Av_exception::USER_ERROR, _('Error! Sensor not allowed'));
            }

            $db->close();
        }
        else
        {
            $e_msg = ossim_get_error_clean();
            Av_exception::throw_error(Av_exception::USER_ERROR, $e_msg);
        }

        $more_info       = Ossec_agent::get_info($sensor_id, $agent_id);

        $last_scan_dates = array();
        if ($agent_ip != '127.0.0.1')
        {
            $last_scan_dates = Ossec_agent::get_last_scans($sensor_id, $agent_name);
        }

        if (is_array($more_info) && !empty($more_info))
        {
            $syscheck_date   = (empty($last_scan_dates['syscheck']))  ? $more_info[7] : $last_scan_dates['syscheck'];
            $rootcheck_date  = (empty($last_scan_dates['rootcheck'])) ? $more_info[8] : $last_scan_dates['rootcheck'];
            
            ?>
            <table class='t_agent_mi'>
                <tr><td colspan='2' style='text-align: center;'><?php echo _('Agent information')?></td></tr>
                <tr>
                    <td><?php echo _('Agent ID')?>:</td>
                    <td><?php echo $more_info[0]?></td>
                </tr>
                <tr>
                    <td><?php echo _('Agent Name')?>:</td>
                    <td><?php echo $more_info[1]?></td>
                </tr>
                <tr>
                    <td><?php echo _('IP/CIDR')?>:</td>
                    <td><?php echo $more_info[2]?></td>
                </tr>
                <tr>
                    <td><?php echo _('Status')?>:</td>
                    <td><?php echo $more_info[3]?></td>
                </tr>
                <tr>
                    <td><?php echo _('Operating System')?>:</td>
                    <td><?php echo $more_info[4]?></td>
                </tr>
                <tr>
                    <td><?php echo _('Client version')?>:</td>
                    <td><?php echo $more_info[5]?></td>
                </tr>
                <tr>
                    <td><?php echo _('Last keep alive')?>:</td>
                    <td><?php echo $more_info[6]?></td>
                </tr>
                <tr>
                    <td><?php echo _('Syscheck last started at')?>:</td>
                    <td><?php echo $syscheck_date?></td>
                </tr>
                <tr>
                    <td><?php echo _('Rootcheck last started at')?>:</td>
                    <td><?php echo $rootcheck_date?></td>
                </tr>
            </table>
            <?php
        }
    }
    catch(Exception $e)
    {
        ?>
        <table class='t_agent_mi'>
            <tr>
                <td style='padding:5px; text-align:center;'><?php echo _('Information from agent not available')?></td>
            </tr>
        </table>
        <?php
    }
}
?>
