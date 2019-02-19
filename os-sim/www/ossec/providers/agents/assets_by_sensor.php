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

$sensor_id     = GET('sensor_id');
$asset_filter  = GET('q');


ossim_valid($asset_filter, OSS_NULLABLE, OSS_NOECHARS, OSS_ALPHA, OSS_SCORE, OSS_PUNC, '()',  'illegal:' . _('Asset filter'));
ossim_valid($sensor_id,    OSS_HEX                                                         ,  'illegal:' . _('Sensor ID'));

if(!ossim_error())
{
    $_assets = array();

    $db   = new ossim_db();
    $conn = $db->connect();

    $q_where = "hsr.host_id = host.id AND hsr.sensor_id=UNHEX('$sensor_id')
        AND NOT exists (select 1 FROM hids_agents ha WHERE ha.host_id = host.id)";

    if (!empty($asset_filter))
    {
        $pos = strpos($asset_filter, ' ');

        if ($pos === FALSE)
        {
            $asset_filter = escape_sql($asset_filter, $conn, TRUE);

            $asset_name = $asset_filter;
            $asset_ip   = $asset_filter;

            $q_where .= " AND (host.hostname LIKE '%$asset_name%' OR INET6_NTOA(hi.ip) LIKE '%$asset_ip%')";
        }
        else
        {
            $aux_asset_filter = explode(' ', $asset_filter, 2);

            $asset_name = $aux_asset_filter[0];
            $asset_ip   = str_replace(array('(', ')'), '', $aux_asset_filter[1]);

            $asset_name = escape_sql($asset_name, $conn, TRUE);
            $asset_ip = escape_sql($asset_ip, $conn, TRUE);

            $q_where .= " AND (host.hostname LIKE '%$asset_name%' AND INET6_NTOA(hi.ip) LIKE '%$asset_ip%')";
        }
    }

    $q_filters = array(
        'where' => $q_where,
        'limit' => 20
    );

    $_assets = Asset_host::get_list_tree($conn, ', host_sensor_reference hsr', $q_filters);

    $db->close();


    $assets = array();

    foreach ($_assets as $asset_id => $asset_data)
    {
        echo $asset_id.'###'.$asset_data[2].'###'.$asset_data[3].'###'.$asset_data[3].' ('.$asset_data[2].")\n";
    }
}
