<?php
/**
* Autocomplete.inc
*
* File Autocomplete.inc is used to:
*   - To manage autocomplete widget
*
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
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


/**
* Autocomplete Class
*
* Class for managing autocomplete widget
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/

class Autocomplete
{

    public static function get_autocomplete_jquery($conn, $keys, $term = null, $limit = null)
    {
        Ossim_db::check_connection($conn);

        $json = array();

        foreach($keys as $k)
        {
            $_function = "self::autocomplete_$k";
            if(is_callable($_function))
            {
                $_json = call_user_func_array($_function, array($conn, $term, $limit));
		if (!$_json) continue;
		$_json = json_decode("[".$_json."]");
		foreach ($_json as $_jsn) 
		{
                    $json[] = array("label"=>$_jsn->txt,"value"=>$_jsn->ip);
		}
            }
        }
        return  json_encode($json);
    }


    public static function get_autocomplete($conn, $keys, $expire = 0)
    {
        Ossim_db::check_connection($conn);

        $json = array();

        foreach($keys as $k)
        {
            $_function = "self::autocomplete_$k";

            if(is_callable($_function))
            {
		$_json = self::get_cache_file($conn, $k, $expire);
                if(empty($_json))
                {
                    $_json = call_user_func_array($_function, array($conn));
                    
                    if ($expire != -1)
                    {
                        self::set_cache_file($k, $_json);
                    }
                }

                if(!empty($_json))
                {
                    $json[] = $_json;
                }
            }
        }
        return implode(",\n", $json);
    }


    public static function get_cache_file($conn, $type, $expire = 0)
    {
        Ossim_db::check_connection($conn);

        $user     = Session::get_session_user();
        $c_file   = "autocomplete_" . md5("$type-$user");

        return Cache_file::get_asset_data($c_file, $expire);
    }


    public static function set_cache_file($type, $data)
    {
        json_decode($data);

        if (json_last_error() == JSON_ERROR_NONE) // Save only if it's a valid json
        {
            $user  = Session::get_session_user();
            $file  = 'autocomplete_' . md5("$type-$user");

            Cache_file::save_file($file, $data);
        }
    }


    public static function autocomplete_hosts($conn)
    {
        $json = array();

        try
        {
            $host_list = Asset_host::get_list_tree($conn, '', FALSE, FALSE);
        }
        catch(Exception $e)
        {
            return '';
        }

        foreach ($host_list as $_host)
        {
            $_json = array
                    (
                        'txt'    => 'HOST: '. $_host[2] .' ['. utf8_encode($_host[3]) .']',
                        'id'     => $_host[0],
                        'type'   => 'host',
                        'name'   => utf8_encode($_host[3]),
                        'ip'     => $_host[2],
                        'prefix' => 'host'
                    );

            $json[] = json_encode($_json);

        }

        return implode(",\n", $json);
    }


    public static function autocomplete_nets($conn)
    {
        $json = array();

        try
        {
            $net_list = Asset_net::get_list_tree($conn);
        }
        catch(Exception $e)
        {
            return '';
        }

        foreach ($net_list as $_net)
        {
            $_json = array
                    (
                        'txt'    => 'NETWORK: '. utf8_encode($_net[3]) .' ['. $_net[2] .']',
                        'id'     => $_net[0],
                        'type'   => 'net',
                        'name'   => utf8_encode($_net[3]),
                        'ip'     => $_net[2],
                        'prefix' => 'net'
                    );

            $json[] = json_encode($_json);

        }

        return implode(",\n", $json);
    }


    public static function autocomplete_host_groups($conn)
    {
        $json = array();

        try
        {
            $_hg_list = Asset_group::get_list($conn);
            $hg_list = $_hg_list[0];

        }
        catch(Exception $e)
        {
            return '';
        }

        foreach ($hg_list as $_hg)
        {
            $_json = array
                    (
                        'txt'    => 'HOST_GROUP: '.Util::html_entities2utf8(Util::htmlentities($_hg->get_name())) ,
                        'id'     => $_hg->get_id(),
                        'type'   => 'host_group',
                        'name'   => Util::html_entities2utf8(Util::htmlentities($_hg->get_name())),
                        'prefix' => 'hostgroup'
                    );

            $json[] = json_encode($_json);

        }

        return implode(",\n", $json);
    }


    public static function autocomplete_net_groups($conn)
    {
        $json           = array();
        $net_group_list = Net_group::get_list($conn);

        foreach ($net_group_list as $_net_group)
        {
            $_json = array
                    (
                        'txt'    => 'NETGROUP: '.utf8_encode($_net_group->get_name()) ,
                        'id'     => $_net_group->get_id(),
                        'type'   => 'net_group',
                        'name'   => utf8_encode($_net_group->get_name()),
                        'prefix' => 'netgroup'

                    );


            $json[] = json_encode($_json);

        }

        return implode(",\n", $json);
    }


    public static function autocomplete_sensors($conn, $term = null, $limit = null)
    {
        $json   = array();
        $filter = array();

        $filter['order_by'] = 'name';

        try
        {
            if ($term)
            {
                $filter["where"] = " (sensor.name LIKE '%$term%' OR INET6_NTOA(sensor.ip) LIKE '%$term%') ";
            }
            if ($limit) {
                $filter["limit"] = $limit;
            }
            $sensor_list = Av_sensor::get_basic_list($conn, $filter);
        }
        catch(Exception $e)
        {
            return '';
        }

        foreach ($sensor_list as $_sensor)
        {
            $_json = array
                    (
                        'txt'    => 'SENSOR: '. utf8_encode($_sensor['name']) .' ['. $_sensor['ip'] .']' ,
                        'id'     => $_sensor['id'],
                        'type'   => 'sensor',
                        'name'   => utf8_encode($_sensor['name']),
                        'ip'     => $_sensor['ip'],
                        'prefix' => 'sensor'

                    );

            $json[] = json_encode($_json);

        }

        return implode(",\n", $json);
    }


    public static function autocomplete_entities($conn)
    {
        if(!Session::is_pro())
        {
            return '';
        }

        $json     = array();
        $entities = Acl::get_entities_to_assign($conn);

        foreach ($entities as $_id => $_name)
        {
            $_json = array
            (
                'txt'    => 'ENTITY: '. Util::html_entities2utf8(Util::htmlentities($_name)),
                'id'     => $_id,
                'type'   => 'entity',
                'name'   => Util::html_entities2utf8(Util::htmlentities($_name)),
                'prefix' => 'e'
            );

            $json[] = json_encode($_json);

        }

        return implode(",\n", $json);
    }


    public static function autocomplete_users($conn)
    {
        $json  = array();
        $users = Session::get_users_to_assign($conn);

        foreach ($users as $_user)
        {
            $_json = array
                    (
                        'txt'    => utf8_encode($_user->get_name()) .' [User]',
                        'id'     => $_user->get_login(),
                        'type'   => 'user',
                        'name'   => utf8_encode($_user->get_name()),
                        'prefix' => 'u'
                    );

            $json[] = json_encode($_json);

        }

        return implode(",\n", $json);

    }


    public static function autocomplete_hosts_ips($conn, $term = null, $limit = null)
    {
        $json = array();

        try
        {
            $filters = array();
            if ($term)
            {
                $filters["where"] = " (host.hostname LIKE '%$term%' OR INET6_NTOA(hi.ip) LIKE '%$term%') ";
            }
            if ($limit) {
                $filters["limit"] = $limit;
            }
            $host_list = Asset_host::get_list_tree($conn,'',$filters);
        }
        catch(Exception $e)
        {
            return '';
        }

        foreach ($host_list as $_host)
        {
            $_json = array
                    (
                        'txt'    => 'HOST: '. $_host[2] .' ['. $_host[3] .']',
                        'id'     => $_host[0],
                        'type'   => 'host',
                        'name'   => $_host[3],
                        'ip'     => $_host[2],
                        'prefix' => 'host'
                    );

            $json[] = json_encode($_json);

        }
        return implode(",\n", $json);
    }


    public static function autocomplete_nets_cidrs($conn,$term=null,$limit=null)
    {
        $json = array();

        try
        {
            $filters = array();
            if ($term)
            {
                $filters["where"] = " (net.name LIKE '%$term%' OR net.ips LIKE '%$term%') ";
            }
            if ($limit) {
                $filters["limit"] = $limit;
            }

            $net_list = Asset_net::get_list_tree($conn, '', $filters);
        }
        catch(Exception $e)
        {
            return '';
        }

        foreach ($net_list as $_net)
        {
            $_cidrs = explode(',', $_net[2]);

            foreach($_cidrs as $_cidr)
            {
                $_json = array
                        (
                            'txt'    => 'NETWORK: '. $_net[3] .' ['. $_cidr .']',
                            'id'     => $_net[0],
                            'type'   => 'net',
                            'name'   => $_net[3],
                            'ip'     => $_cidr,
                            'prefix' => 'net'
                        );

                $json[] = json_encode($_json);
            }

        }

        return implode(",\n", $json);
    }
    
    public static function autocomplete_pulses()
    {
        $json = array();
        $otx  = new Otx();

        try
        {
            $filters = array(
                'page'      => 0,
                'page_rows' => -1
            );
            
            list($total, $p_list) = $otx->get_pulse_list($filters);
            
            foreach ($p_list as $_pulse)
            {
                $json[] = json_encode(array
                (
                    'txt'    => 'Pulse: '. $_pulse['name'],
                    'id'     => $_pulse['id'],
                    'type'   => 'pulse',
                    'name'   => $_pulse['name'],
                    'prefix' => 'pulse'
                ));
            }
        }
        catch(Exception $e){}

        return implode(",\n", $json);
    }
}

/* End of file autocomplete.inc */
/* Location: ../include/classes/autocomplete.inc */
