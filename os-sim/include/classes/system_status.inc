<?php
/**
* system_status.inc
*
* File system_status.inc is used to:
*   - Dashboard -> Deployment Status -> Warnings & Errors tab
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

require_once 'av_init.php';


/**
* System Status Class
*
* Class for managing the System Status messages
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ Alienvault web page
*/

class System_status
{
    /**
     * API pointer
     * @var object
     *
     */
    private $api;


    /**
     * Class constructor
     *
     * This function sets up the class
     *
     */
    public function __construct()
    {
        $this->api = new Alienvault_client();
    }


    /**
     * This function gets all system messages
     *
     * @param  array  $filters     [Optional] SQL statements (WHERE, LIMIT, ORDER BY ...)
     * @param  array  $pagination  [Optional] Pagination parameters
     *
     * @throws Exception  If an error occurred
     *
     * @return array
     */
    public function get_status_messages($filters = array(), $pagination = array())
    {
        $response  = $this->api->data('')->get_status_messages($filters, $pagination);
        $response  = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $this->api->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return array($response['data']['messages'], $response['data']['total']);
    }


    /**
     * This function get detail for a given message id
     *
     * @param  string  $msg_id  Message id
     *
     * @throws Exception  If an error occurred
     *
     * @return  array
     */
    public function get_message_detail($msg_id)
    {
        $response  = $this->api->data('')->get_status_message($msg_id);
        $response  = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $this->api->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return array($response['data']);
    }


    /**
     * This function set a view flag for a message id
     *
     * @param  string  $msg_id  Message id
     *
     * @throws Exception  If an error occurred
     *
     * @return boolean
     */
    public function set_viewed($msg_id, $component_id)
    {
        $response = $this->api->data('')->set_status_message_viewed($msg_id, $component_id);
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $this->api->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return TRUE;
    }


    /**
     * This function return a formatted action message with link
     *
     * @param  array  $action    Action data
     * @param  string $asset_id  [Optional] Asset UUID
     *
     * @return  string
     */
    public function format_action_link($action, $asset_id = '')
    {
        $content = nl2br(str_replace( array('\n'), array('<br>'), $action["content"]));
        if ($asset_id)
        {
            $asset_id = strtoupper(str_replace("-","",$asset_id));
            $link     = '<a class="uppercase" href="'.str_replace( array("AV_PATH","ASSET_ID"), array(AV_MAIN_PATH, $asset_id) , $action["link"] ).'">$1</a>'; 
            
            if ($action["link"] == 'none')
            {
                return "<span class='red'>$content</span>";
            }
            elseif ($action["link"])
            {
                return preg_replace('/<<(.*?)>>/', $link, $content);
            }

            return $content;
        }
        else
        {
            return preg_replace('/<<(.*?)>>/', '$1', $content); 
        }
    }


    /**
     * This function return a formatted message
     *
     * @param  string  $detail  Detail
     * @param  string  $msg     Message
     *
     * @return  string
     */
    public function format_message($detail, $msg)
    {
        $date  = gmdate("Y-m-d H:i:s",strtotime($msg['creation_time']) + (3600*Util::get_timezone()));

        return str_replace( array("TIMESTAMP"), array($date), $detail["content"] );
    }   
}

/* End of file system_status.inc */
/* Location: ../include/classes/system_status.inc */