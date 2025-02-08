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


Session::logcheck_ajax("analysis-menu", "ControlPanelAlarms");

session_write_close();

$backlog_id = POST('backlog_id');
$engine     = POST('engine');
$asset_id   = POST('asset_id');
$asset_ip   = POST('asset_ip');

$asset_id = strtoupper(str_replace('0x', '', $asset_id));
if (!valid_hex32($asset_id))
{
    $asset_id = '';
}

$engine = strtoupper(str_replace('-', '', $engine));


ossim_valid($backlog_id,    OSS_HEX,          'illegal:' . _("Backlog ID"));
ossim_valid($asset_ip,      OSS_IP_ADDR_0,    'illegal:' . _("Asset IP"));

if (ossim_error())
{
    Util::response_bad_request(ossim_get_error_clean());
}


$response = array();
try
{
    $db   = new ossim_db(TRUE);
    $conn = $db->connect();
    $gloc = new Geolocation(Geolocation::$PATH_CITY);

    $asset = Asset_host::get_object($conn, $asset_id);

    // Get asset ID by other ways
    $ctx = NULL;
    if (!$asset){
        //USM Appliance
        if (Session::is_pro() && valid_hex32($engine)) {
            $contexts = Acl::get_contexts_by_engine($conn, $engine);
            //I can figure out the context if I have only one
            if(count($contexts) == 1) {
                $ctx = $contexts[0]['id'];
            }
        }
        else {
            $ctx = Session::get_default_ctx();
        }

        if (valid_hex32($ctx)){
            $asset_ids = Asset_host::get_id_by_ips($conn, $asset_ip, $ctx);

            if (count($asset_ids) == 1) {
                $asset_id = key($asset_ids);
            }

            $asset = Asset_host::get_object($conn, $asset_id);
        }
    }

    if ($asset)
    {
        $groups = $networks = array();

        //Getting Groups
        list($_groups, $_t)  = $asset->get_related_groups($conn);
        foreach ($_groups as $g_id => $g)
        {
            $groups[$g_id] = array(
                'name' => $g->get_name()
            );
        }

        //Getting Nets
        $_networks= $asset->get_nets($conn);
        foreach ($_networks as $n_id => $n)
        {
            $networks[$n_id] = array(
                'name' => $n['name']
            );
        }

        $response['id']     = $asset_id;
        $response['ip']     = $asset_ip;
        $response['name']   = $asset->get_name();
        $response['groups'] = $groups;
        $response['nets']   = $networks;
    }
    else
    {
        //Getting Nets
        $networks = array();
        if (valid_hex32($ctx)){
            $_networks= Asset_host::get_closest_nets($conn, $asset_ip, $ctx);
            foreach ($_networks as $n_id => $n)
            {
                $networks[$n['id']] = array(
                    'name' => $n['name']
                );
            }
        }

        $response['id']     = $asset_id;
        $response['ip']     = $asset_ip;
        $response['name']   = $asset_ip;
        $response['groups'] = array();
        $response['nets']   = $networks;
    }

    $flag_code = strtolower($gloc->get_country_code_from_file($asset_ip));
    if (file_exists("/usr/share/ossim/www/pixmaps/flags/$flag_code.png"))
    {
        $flag = "/ossim/pixmaps/flags/$flag_code.png";
    }

    //Organization has been removed
    $response['location'] = array(
        'flag'         => strval($flag),
        'country'      => strval($gloc->get_country_name_from_file($asset_ip)),
        'organization' => ""
    );

    //Get the stats for check the reputation
    $stats = $_SESSION["_alarm_stats"];

    $c_rep_1 = $stats['src']['ip'][$asset_ip]['rep'] > 0;
    $c_rep_2 = $stats['dst']['ip'][$asset_ip]['rep'] > 0;

    $response['reputation'] = ($c_rep_1 || $c_rep_2);


    $db->close();

} catch (Exception $e)
{
    Util::response_bad_request($e->getMessage());
}

echo json_encode($response);
