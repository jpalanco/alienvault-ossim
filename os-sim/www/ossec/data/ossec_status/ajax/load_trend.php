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

require_once dirname(__FILE__) . '/../../../conf/config.inc';

session_write_close();

$m_perms  = array('environment-menu', 'environment-menu');
$sm_perms = array('EventsHids', 'EventsHidsConfig');

if (Session::menu_perms($m_perms, $sm_perms))
{
    $agent_id  = POST('agent_id');
    $sensor_id = POST('sensor_id');

    ossim_valid($agent_id, OSS_DIGIT, OSS_LETTER, '\=', '\+', '\/',   'illegal:' . _('Agent ID'));
    ossim_valid($sensor_id, OSS_HEX,                                  'illegal:' . _('Sensor ID'));

    if (ossim_error())
    {        
        echo "<div style='color:gray; margin:15px; text-align:center;'>"._('Trend chart not available')."</div>";
        exit();
    }

    $tz     = Util::get_timezone();
    $timetz = gmdate("U") + (3600 * $tz); // time to generate dates with timezone correction

    $agent = $_SESSION['_agent_info'][md5($agent_id)];


    $db   = new ossim_db();
    $conn = $db->connect();

    //Agents trends
    if ($agent['ip'] == '127.0.0.1')
    {
        // Get default system uuid
        $system_id   = Util::get_system_uuid();
        $system_info = Av_center::get_system_info_by_id($conn, $system_id);

        if ($system_info['status'] == 'success')
        {
            $sensor_ip = $system_info['data']['admin_ip'];
        }

        $ip_cidr = (empty($sensor_ip)) ? $agent['ip'] : $sensor_ip;
    }
    else
    {
        $agent_idm_data = Ossec_agent::get_idm_data($sensor_id, $agent['ip']);
        $agent_idm_ip   = $agent_idm_data['ip'];

        if (empty($agent_idm_ip))
        {
           try
           {
                $agent_idm_ip = Ossec_agent::get_last_ip($sensor_id, $agent);
           }
           catch(Exception $e)
           {
               ;
           }
        }

        $ip_cidr = (Asset_host_ips::valid_ip($agent_idm_ip)) ? $agent_idm_ip : $agent['ip'];
    }

    $data = array();

    if (!preg_match('/Never connected/i', $agent['status']) && Asset_host_ips::valid_ip($ip_cidr))
    {
        $data = Ossec_utilities::SIEM_trends_hids($conn, $ip_cidr);
    }

    $trend_plot = "<div style='color:gray; margin:15px; text-align:center;'>"._('Trend chart not available')."</div>";


    if (is_array($data) && !empty($data))
    {
        $trend = '';
        $max   = 7;

        for ($ii=$max-1; $ii>=0; $ii--)
        {
            $d         = gmdate("j M", $timetz-(86400*$ii));
            $trend[$d] = ($data[$d] != '') ? $data[$d] : 0;
        }

        $i = 0;
        foreach ($trend as $k => $v)
        {
            $x[$k] = $i;
            $i++;
        }

        $y      = $trend;
        $xticks = $x;
        
        foreach ($trend as $k => $v)
        {
            $xlabels[$k] = $k;
        }   

        $trend_plot = "<div id='plotarea_".$agent_id."'>".Ossec_utilities::plot_graphic('plotarea_'.$agent_id, 40, 250, $x, $y, $xticks, $xlabels, FALSE).'</div>';
    }


    $db->close();

    echo $trend_plot;
}
?>