<?php

/**
 * av_routing.inc
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
 * @package    ossim-framework\Various
 * @autor      AlienVault INC
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt
 * @copyright  2003-2006 ossim.net
 * @copyright  2007-2015 AlienVault
 * @link       https://www.alienvault.com/
 */

require_once 'av_init.php';


/**
 * Av_routing class
 *
 * Class for routing
 *
 * @package    ossim-framework\Various
 * @autor      AlienVault INC
 * @copyright  2007-2015 AlienVault
 * @link       https://www.alienvault.com/ AlienVault web page
 *
 *
 * IMPORTANT!!!!
 *
 * IF YOU DO ANY CHANGE IN THIS CLASS CHECK "menu.inc" FILE
 */

class Av_routing
{
    /**
     * Action prefixes array
     *
     * @var array $action_prefixes
     */
    private static $action_prefixes = array('SN', 'EXT');

    /**
     * Action types array
     *
     * @var array $action_types
     */
    private static $action_types = array('menu', 'direct', 'help');

    /**
     * Actions array
     *
     * Prefixes:
     *
     *      SN_ -> System Notifications (Message Center)
     *
     * @var array $actions
     */
    private static $actions = array(
        'SN_MESSAGE_INFO_ASSET_NOT_SENDING_LOGS_1'        => array(
            'type'   => 'direct',
            'url'    => '/av_asset/common/views/edit_plugins.php',
            'params' => array()
        ),
        'SN_MESSAGE_INFO_LOGS_BUT_NOT_PLUGIN_ENABLED_1'   => array(
            'type'   => 'direct',
            'url'    => '/av_asset/common/views/edit_plugins.php',
            'params' => array()
        ),
        'SN_MESSAGE_WARNING_24_HOURS_WITHOUT_EVENTS_1'    => array(
            'type'         => 'menu',
            'menu_options' => array('configuration', 'deployment', 'components', 'sensors'),
            'params'       => array()
        ),
        'SN_MESSAGE_WARNING_24_HOURS_WITHOUT_EVENTS_2'    => array(
            'type'         => 'menu',
            'menu_options' => array('environment', 'assets', 'assets', NULL),
            'params'       => array(
                'action'    => 'custom_scan',
                'sensor'    => 'automatic',
                'scan_type' => 'fast'
            )
        ),
        'SN_MESSAGE_WARNING_24_HOURS_WITHOUT_EVENTS_3'    => array(
            'type'   => 'direct',
            'url'    => '/av_asset/common/views/edit_plugins.php',
            'params' => array()
        ),
        'SN_MESSAGE_WARNING_SATURATION_1'                 => array(
            'type'         => 'menu',
            'menu_options' => array('environment', 'profiles', 'services', NULL),
            'params'       => array(
                'opc' => 'services'
            )
        ),
        'SN_MESSAGE_WARNING_SATURATION_2'                 => array(
            'type'         => 'menu',
            'menu_options' => array('configuration', 'deployment', 'components', NULL),
            'params'       => array()
        ),
        'SN_MESSAGE_WARNING_DROPPED_PACKAGES_1'           => array(
            'type'         => 'menu',
            'menu_options' => array('environment', 'profiles', 'services', NULL),
            'params'       => array(
                'opc' => 'services'
            )
        ),
        'SN_MESSAGE_WARNING_DISK_SPACE_1'                 => array(
            'type'         => 'menu',
            'menu_options' => array('configuration', 'administration', 'main', NULL),
            'params'       => array(
                'open' => 0
            )
        ),
        'SN_MESSAGE_ERROR_DISK_SPACE_1'                   => array(
            'type'         => 'menu',
            'menu_options' => array('configuration', 'administration', 'main', NULL),
            'params'       => array(
                'open' => 0
            )
        ),
        //'SN_MESSAGE_EXTERNAL_DNS_CONFIGURED_1'              => array(),
        'SN_MESSAGE_SYSTEM_UNREACHEABLE_OR_UNAVAILABLE_1' => array(
            'type'   => 'direct',
            'url'    => '/av_center/data/sections/main/add_system.php',
            'params' => array()
        ),
        'SN_MESSAGE_PENDING_UPDATES_1'                    => array(
            'type'         => 'menu',
            'menu_options' => array('configuration', 'deployment', 'components', NULL),
            'params'       => array()
        ),
        'SN_MESSAGE_SENSOR_UNREACHEABLE_OR_UNAVAILABLE_1' => array(
            'type'         => 'menu',
            'menu_options' => array('configuration', 'deployment', 'components', 'sensors'),
            'params'       => array()
        ),
        'SN_MESSAGE_SENSOR_UNREACHEABLE_OR_UNAVAILABLE_2' => array(
            'type'   => 'direct',
            'url'    => '/av_center/data/sections/main/add_system.php',
            'params' => array()
        ),
        //'SN_MESSAGE_PLUGINS_VERSION_1'                      => array(),
        //'SN_MESSAGE_PLUGINS_CHECK_INTEGRITY_1'              => array(),
        //'SN_MESSAGE_PLUGINS_CHECK_INSTALLED_1'              => array(),
        //'SN_MESSAGE_PLUGINS_RSYSLOG_CHECK_INTEGRITY_1'      => array(),
        //'SN_MESSAGE_PLUGINS_RSYSLOG_CHECK_INSTALLED_1'      => array(),
        //'SN_MESSAGE_SYSTEM_CHECK_DB_1'                      => array(),
        //'SN_MESSAGE_CONFIGURATION_BACKUP_FAIL_1'            => array(),
        //'SN_MESSAGE_WIZARD_SHOWN_1                          => array(),
        'SN_MESSAGE_SENSOR_NOT_INSERTED_1'                => array(
            'type'         => 'help',
            'help_options' => array('configuration', 'deployment', 'components', 'sensors'),
            'params'       => array()
        ),
        'SN_MESSAGE_SENSOR_NOT_INSERTED_2'                => array(
            'type'         => 'menu',
            'menu_options' => array('configuration', 'deployment', 'components', 'sensors'),
            'params'       => array()
        ),
        //'SN_MESSAGE_TRIAL_EXPIRED_1'                        => array(),
        //'MESSAGE_TRIAL_EXPIRES_7DAYS_1'                     => array(),
        //'MESSAGE_TRIAL_EXPIRES_2DAYS_1'                     => array(),
        //'MESSAGE_LICENSE_VIOLATION_1'                       => array(),
        'SN_MESSAGE_OTX_CONNECTION_1'                     => array(
            'type'         => 'menu',
            'menu_options' => array('configuration', 'otx', 'otx', NULL),
            'params'       => array()
        ),
        'SN_MESSAGE_HIDS_AGENTS_NOT_LINKED'                     => array(
            'type'         => 'menu',
            'menu_options' => array('environment', 'detection', 'hids', 'agents'),
            'params'       => array()
        ),
        //'SN_MESSAGE_BACKUP_RUNNING_1'                       => array(),
        'EXT_TRACK_USAGE_INFORMATION'                     => array(
            'type'   => 'external',
            'url'    => 'http://www.alienvault.com/help/product/telemerty',
            'params' => array()
        ),
        'LEGAL_FOOTER'                                    => array(
            'type'   => 'external',
            'url'    => 'http://www.alienvault.com/help/legal/',
            'params' => array()
        )
    );


    /**
     * Checks if action exists
     *
     * @param  string $action
     *
     * @return bool
     */
    private static function check_action_exists($action)
    {
        return array_key_exists($action, self::$actions);
    }


    /**
     * Get actions
     *
     * @param string $action_prefix [Optional]
     *
     * @return array
     */
    public static function get_actions($action_prefix = '')
    {
        /**
         * Run a match on the array's keys
         *
         * @param string $pattern
         * @param array  $array
         * @param int    $flags
         *
         * @return array
         */
        $preg_grep_keys = function ($pattern, $array, $flags = 0)
        {
            return array_intersect_key($array, array_flip(preg_grep($pattern, array_keys($array), $flags)));
        };

        return (in_array($action_prefix, self::$action_prefixes))
            ? $preg_grep_keys('/^'.$action_prefix.'/', self::$actions)
            : self::$actions;
    }


    /**
     * Get direct actions
     *
     * @param string $action_type   Action type
     * @param string $action_prefix [Optional]
     *
     * @return array
     */
    public static function get_actions_by_type($action_type, $action_prefix = '')
    {
        $actions = self::get_actions($action_prefix);

        /**
         * Return tru if action is equals to direct
         *
         * @param  $array
         *
         * @return bool
         */
        $filter_actions = function ($array) use ($action_type)
        {
            return ($array['type'] == $action_type);
        };

        return (in_array($action_type, self::$action_types))
            ? array_filter($actions, $filter_actions)
            : $actions;
    }


    /**
     * Get url for action
     *
     * @param  object $conn        Database access object
     * @param  string $action_name Action name
     * @param  array  $params      Params array
     *
     * @return string
     */
    public static function route($conn, $action_name, array $params)
    {
        $url = '';

        if (self::check_action_exists($action_name))
        {
            $action = self::$actions[$action_name];

            $action_type   = $action['type'];
            $action_params = $action['params'];

            $params = array_merge($action_params, $params);

            switch ($action_type)
            {
                case 'menu':

                    $menu = new Menu($conn);

                    $menu_options = $action['menu_options'];

                    $menu->set_menu_option($menu_options[0], $menu_options[1]);
                    $menu->set_hmenu_option($menu_options[2]);
                    $menu->set_lmenu_option($menu_options[3]);

                    $url = AV_MAIN_PATH.Menu::get_menu_url(
                            $menu->get_current_url(), $menu_options[0], $menu_options[1], $menu_options[2], $menu_options[3]);

                    if (!empty($params))
                    {
                        $url .= '&'.http_build_query($params);
                    }

                break;

                case 'direct':

                    $action_url = $action['url'];

                    $url = AV_MAIN_PATH.$action_url;

                    if (!empty($params))
                    {
                        $url .= '?'.http_build_query($params);
                    }

                break;

                case 'external':

                    $url = $action['url'];

                    if (!empty($params))
                    {
                        $url .= '?'.http_build_query($params);
                    }

                break;

                case 'help':

                    $help_options = $action['help_options'];

                    $url = 'https://www.alienvault.com/help/product/'.join('/', $help_options);

                    if (!empty($params))
                    {
                        $url .= '&'.http_build_query($params);
                    }

                break;
            }
        }

        return $url;
    }
}

/* End of file av_routing.inc */
/* Location: ../include/classes/av_routing.inc */
