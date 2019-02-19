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

require_once dirname(__FILE__) . '/../../conf/config.inc';;

session_write_close();

$m_perms  = array('environment-menu', 'environment-menu');
$sm_perms = array('EventsHids', 'EventsHidsConfig');

if (Session::menu_perms($m_perms, $sm_perms))
{
    $sensor_id    = POST('sensor_id');
    $agent_id     = POST('agent_id');
    $asset_id     = POST('asset_id');
    $agent_name   = POST('agent_name');
    $ip_cidr      = POST('ip_cidr');
    $agent_status = POST('agent_status');

    $validate = array(
        'sensor_id'    => array('validation' => "OSS_HEX",                                                          'e_message' => 'illegal:' . _('Sensor ID')),
        'agent_id'     => array('validation' => "OSS_DIGIT",                                                        'e_message' => 'illegal:' . _('Agent ID')),
        'asset_id'     => array('validation' => "OSS_HEX, OSS_NULLABLE",                                            'e_message' => 'illegal:' . _('Asset ID')),
        'agent_name'   => array('validation' => 'OSS_SCORE, OSS_LETTER, OSS_DIGIT, OSS_DOT, OSS_SPACE, "(", ")"',   'e_message' => 'illegal:' . _('Agent Name')),
        'ip_cidr'      => array('validation' => 'OSS_IP_ADDRCIDR',                                                  'e_message' => 'illegal:' . _('IP/CIDR')),
        'agent_status' => array('validation' => 'OSS_DIGIT',                                                        'e_message' => 'illegal:' . _('Agent Status'))
    );


    if ($ip_cidr == 'any')
    {
        $validate['ip_cidr'] = array('validation' => 'any',  'e_message' => 'illegal:' . _('IP/CIDR'));
    }



    $validation_errors = validate_form_fields('POST', $validate);

    $db   = new ossim_db();
    $conn = $db->connect();

    //Extra validations

    if (empty($validation_errors['sensor_id']) && !Ossec_utilities::is_sensor_allowed($conn, $sensor_id))
    {
        $validation_errors['sensor_id'] = sprintf(_("Sensor %s not allowed. Please check with your account admin for more information"), Av_sensor::get_name_by_id($conn, $sensor_id));
    }


    if (is_array($validation_errors) && !empty($validation_errors))
    {
        $db->close();
        echo "<div style='color:gray; margin:15px; text-align:center;'>"._('Trend chart not available')."</div>";
        exit();
    }


    $tz     = Util::get_timezone();
    $timetz = gmdate("U") + (3600 * $tz); // time to generate dates with timezone correction


    //HIDS trend

    $data = array();

    if ($agent_status > 1)
    {
        if (Asset_host::is_in_db($conn, $asset_id))
        {
            $data = Ossec_utilities::hids_trend_by_id($conn, $asset_id);
        }
        else
        {
            if ($ip_cidr == '127.0.0.1')
            {
                // Getting default sensor IP
                $sensor_ip = Av_sensor::get_ip_by_id($conn, $sensor_id);
                $ip_cidr   = (empty($sensor_ip)) ? $ip_cidr : $sensor_ip;
            }
            else
            {
                try
                {
                    $agent = array(
                        'name'    => $agent_name,
                        'ip_cidr' => $ip_cidr
                    );

                    $ip_cidr = Ossec_agent::get_last_ip($sensor_id, $agent);
                }
                catch(Exception $e)
                {
                    ;
                }
            }

            if(Asset_host_ips::valid_ip($ip_cidr))
            {
                $data = Ossec_utilities::hids_trend_by_ip($conn, $ip_cidr);
            }
        }
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
