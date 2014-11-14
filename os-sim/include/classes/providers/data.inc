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
* @copyright  2007-2013 AlienVault
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
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/
class Data_provider extends Provider
{       
    /**
     * This function gets all system messages
     *
     * @param  array  $filters     [Optional] SQL statements (WHERE, LIMIT, ORDER BY ...)
     * @param  array  $pagination  [Optional] Pagination parameters
     *
     * @return object
     */
    public function get_status_messages($filters, $pagination)
    {
        $params = array();

        //Pagination parameters
        $params['page']      = (!empty($pagination['page']))      ? $pagination['page']      : 1;
        $params['page_rows'] = (!empty($pagination['page_rows'])) ? $pagination['page_rows'] : 10;

        //Filters
        if (!empty($filters['order_by']))
        {
            $params['order_by']   = $filters['order_by'];
            $params['order_desc'] = (!empty($filters['order_desc']))? $filters['order_desc'] : 'true';
        }

        $params['level'] = (!empty($filters['level'])) ? $filters['level'] : 'warning,error';

        if (!empty($filters['message_id']))
        {
            $params['message_id'] = $filters['message_id'];
        }

        if (valid_hex32($filters['component_id'], TRUE))
        {
            $params['component_id'] = $filters['component_id'];
        }

        $response = $this->conn->do_request($this->common_url . "/status", $params);

        /* FAKE ANSWER
        $response = '
        {
                "data": {
                    "messages" : [
                        {
                            "message_id": "123456790",
                            "description": "Disk space under 10%",
                            "level": "warning",
                            "component_id": "bf4941fb-16e5-11e3-9e13-d2fa3c8eb023",
                            "component_type": "system",
                            "component_ip" : "5.9.212.25",
                            "component_name": "usm44",
                            "creation_time": "1389284090",
                            "viewed" : false
                        },
                        {
                            "message_id": "123456789",
                            "description": "Disk space under 25%",
                            "level": "warning",
                            "component_id": "bf4941fb-16e5-11e3-9e13-d2fa3c8eb023",
                            "component_type": "system",
                            "component_ip" : "5.9.212.25",
                            "component_name": "usm44",
                            "creation_time": "1389284089",
                            "viewed" : false
                        },
                        {
                            "message_id": "123456788",
                            "description": "Dropped packages over 1%",
                            "level": "error",
                            "component_id": "bf4941fb-16e5-11e3-9e13-d2fa3c8eb023",
                            "component_type": "sensor",
                            "component_ip" : "5.9.212.25",
                            "component_name": "usm44",
                            "creation_time": "1389284088",
                            "viewed" : true
                        }
                    ],
                    "total": 3
                },
                "status": "success"
        }';*/

        return $response;
    }


    /**
     * This function gets full description/remedation for a certain id
     *
     * @param string  $msg_id    Message uuid
     *
     * @return object
     */
    public function get_status_message($msg_id)
    {                              
        $response = $this->conn->do_request($this->common_url . "/status/$msg_id");

        /* FAKE ANSWER
        $response = '
            {
                    "data": {
                        "msg_id" : "'.$msg_id.'",
                        "content" : "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam iaculis sodales leo, a pellentesque massa scelerisque sit amet. Quisque blandit risus nec commodo malesuada. Phasellus placerat vitae erat a ultrices. Nulla placerat a erat ut aliquam. Nulla aliquet nisl ac gravida eleifend. Nam imperdiet aliquet ipsum, vel sagittis odio posuere ut. Duis diam eros, mollis vel tempus sit amet, vulputate ac enim. Aliquam quis lobortis tellus. Etiam sit amet nisi a nisl iaculis porta eget vel ipsum. Ut vehicula mi nec fermentum accumsan. Vestibulum euismod sed ipsum non bibendum. Ut quis mauris rhoncus, aliquam est non, gravida mauris. Proin orci est, egestas et molestie sit amet, vulputate non felis.\n\nAliquam sit amet urna nec urna fermentum volutpat quis in leo. Pellentesque sit amet facilisis erat. Aliquam ac fringilla lectus. Proin libero nibh, porta sed laoreet vitae, varius non ligula. Aliquam nec diam vitae velit placerat malesuada sed id nulla. Aenean iaculis ipsum et nunc vulputate facilisis. Aliquam ultricies, nisi vitae laoreet mollis, mauris felis congue tortor, id iaculis purus nulla eu velit. Nam a diam ligula. Proin at rutrum enim. Nam tristique ligula sed mauris iaculis, at semper libero scelerisque. Etiam in dui eu sem volutpat congue. Nullam scelerisque lacus eu mauris eleifend porta. Cras nec fermentum libero, sit amet ullamcorper neque.",
                        "link" : ""
                },
                    "status": "success"
            }';*/

        return $response;
    }


    /**
     * This function sets viewed flag in a given msg_id/component_id
     *
     * @param string  $msg_id        Message uuid
     * @param string  $component_id  UUID Component
     * @param string  $viewed        True|False
     *
     * @return object
     */
    public function set_status_message_viewed($msg_id, $component_id='', $viewed='true')
    {
        $params   = array("viewed" => $viewed);

        if ($component_id)
        {
            $params['component_id'] = $component_id;
        }

        $response = $this->conn->do_request($this->common_url . "/status/$msg_id", $params, 'PUT');
        /* FAKE ANSWER
        $response = '
        {
        		"status": "success"
        }';*/
        
        return $response;
    }


    /**
     * This function deletes asset type suggestions/messages
     *
     * @param string  $component_id    UUID Component
     * @param string  $component_type  [host|net]
     *
     * @return object
     */
    public function delete_asset($component_id, $component_type='host')
    {
        $component_id = Util::uuid_format($component_id); 
        $response     = $this->conn->do_request($this->common_url . "/$component_type/$component_id", array(), 'DELETE');

        return $response;
    }


    /**
     * This function delete orphans assets with suggestions/messages
     *
     * @param string  $component_type  [host|net]
     *
     * @return object
     */
    public function clean_orphans($component_type='host')
    {
        $response     = $this->conn->do_request($this->common_url . "/$component_type", array(), 'DELETE');

        return $response;
    }
}
?>