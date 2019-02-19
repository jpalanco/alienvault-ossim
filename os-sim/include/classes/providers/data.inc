<?php

/**
 * data.inc
 *
 * File data.inc is used to:
 *   - define Data_provider methods
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
 * @copyright  2007-2015 AlienVault
 * @link       https://www.alienvault.com/
 */

require_once 'provider.inc';


/**
 * The goal of this class is to add "Data" providers
 *
 *
 * @package    ossim-framework\Various
 * @autor      AlienVault INC
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt
 * @copyright  2003-2006 ossim.net
 * @copyright  2007-2015 AlienVault
 * @link       https://www.alienvault.com/
 */
class Data_provider extends Provider
{
    /**
     * This function gets all status messages
     *
     * @param  array $filters    [Optional] Filter parameters
     * @param  array $pagination [Optional] Pagination parameters
     *
     * @return object
     */
    public function get_status_messages($filters, $pagination)
    {
        $params = array();

        // Pagination
        if (!empty ($pagination['page_rows']))
        {
            $params['page_rows'] = $pagination['page_rows'];
            $params['page']      = (!empty($pagination['page'])) ? $pagination['page'] : 1;
        }

        // Filters
        if (valid_hex32($filters['component_id'], TRUE))
        {
            $params['component_id'] = $filters['component_id'];
        }

        if (!empty($filters['component_type']))
        {
            $params['component_type'] = $filters['component_type'];
        }

        if (!empty($filters['level']))
        {
            $params['level'] = $filters['level'];
        }

        if (!empty($filters['order_by']))
        {
            $params['order_by']   = $filters['order_by'];
            $params['order_desc'] = (!empty($filters['order_desc'])) ? $filters['order_desc'] : 'true';
        }

        if (!empty($filters['message_type']))
        {
            $params['message_type'] = $filters['message_type'];
        }

        if (!empty($filters['message_id']))
        {
            $params['message_id'] = $filters['message_id'];
        }

        if (!empty($filters['search']))
        {
            $params['search'] = $filters['search'];
        }

        if (!empty($filters['only_unread']))
        {
            $params['only_unread'] = $filters['only_unread'];
        }

        $response = $this->conn->do_request($this->common_url.'/status', $params);

        return $response;
    }


    /**
     * This function gets full description/remedation for a certain id
     *
     * @param string $msg_id Message uuid
     *
     * @return object
     */
    public function get_status_message_detail($msg_id)
    {
        $params = array(
            'message_id' => $msg_id
        );

        $response = $this->conn->do_request($this->common_url.'/status', $params);

        return $response;
    }


    public function get_status_message($msg_id)
    {
        $response = $this->conn->do_request($this->common_url.'/status/'.$msg_id, array());
        return $response;
    }


    public function save_status_message($data)
    {
        $response = $this->conn->do_request("$this->common_url/status/", $data, 'POST');
        return $response;
    }


    /**
     * This functions gets status messages stats
     *
     * @return array
     */
    public function get_status_messages_stats($filters)
    {
        $params = array();

        if (!empty($filters['search']))
        {
            $params['search'] = $filters['search'];
        }

        if (!empty($filters['only_unread']))
        {
            $params['only_unread'] = $filters['only_unread'];
        }

        $response = $this->conn->do_request($this->common_url.'/status/stats', $params);

        return $response;
    }


    /**
     * This function sets viewed flag for a given status message UUID
     *
     * @param  string  $current_status_id  Status message UUID
     * @param  array   $flags              Flags params
     *
     * @return object
     */
    public function set_status_message($current_status_id, $flags)
    {
        $params = array();

        if (!empty ($flags['viewed']))
        {
            $params['viewed'] = $flags['viewed'];
        }

        if (!empty ($flags['suppressed']))
        {
            $params['suppressed'] = $flags['suppressed'];
        }

        $response = $this->conn->do_request($this->common_url.'/status/'.$current_status_id, $params, 'PUT');

        return $response;
    }


    /**
     * This function deletes asset type suggestions/messages
     *
     * @param string $component_id   UUID Component
     * @param string $component_type [host|net]
     *
     * @return object
     */
    public function delete_asset($component_id, $component_type = 'host')
    {
        $component_id = Util::uuid_format($component_id);
        $response     = $this->conn->do_request($this->common_url."/$component_type/$component_id", array(), 'DELETE');

        return $response;
    }


    /**
     * This function delete orphans assets with suggestions/messages
     *
     * @param string $component_type [host|net]
     *
     * @return object
     */
    public function clean_orphans($component_type = 'host')
    {
        $response = $this->conn->do_request($this->common_url."/$component_type", array(), 'DELETE');

        return $response;
    }
}
