<?php
/**
* av_backup.inc
*
* File av_backup.inc is used to:
*   - To manage API backups
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
* @package    ossim-framework\Alienvault_Components
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


require_once 'av_init.php';


/**
* Av_backup Class
*
* Class for managing sensors
*
* @package    ossim-framework\Alienvault_Components
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/

class Av_backup
{
    /**
     * System ID
     *
     * @var    string
     * @access private
     */
    private $system_id;

    /**
     * Backup Type ('configuration'|'environment'|...)
     *
     * @var    string
     * @access private
     */
    private $backup_type;

    /**
     * API Connection
     *
     * @var    object
     * @access private
     */
    private $api_client;

    /**
     * Download Path
     *
     * @var    string
     * @access public
     */
    public $download_path = '/var/alienvault/backup/downloaded';


    /**
     * Class constructor
     *
     * This function sets up the class
     *
     * @param string   $system_id    System ID
     * @param string   $backup_type  Backup Type
     *
     */
    public function __construct($system_id, $backup_type)
    {
        $this->system_id   = $system_id;
        $this->backup_type = $backup_type;

        $this->api_client  = new Alienvault_client();
    }


    /**
     * This function gets the system backups
     *
     * @throws Exception  If an error occurred
     *
     * @return array
     */
    public function get_backup_list()
    {
        $response = $this->api_client->system($this->system_id)->get_backup_list($this->system_id, $this->backup_type);
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $this->api_client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $data = (is_array($response['data']['backups'])) ? $response['data']['backups'] : array();

        return $data;
    }

    /**
     * This function runs a system backup
     *
     * @throws Exception  If an error occurred
     *
     * @return string
     */
    public function run_backup()
    {
        $response = $this->api_client->system($this->system_id)->run_backup($this->system_id, $this->backup_type);
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $this->api_client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return $response['data']['job_id'];
    }

    /**
     * This function gets a system backup status by job_id
     *
     * @param string   $job_id    Backup Job ID      [Optional]
     *
     * @throws Exception  If an error occurred
     *
     * @return string
     */
    public function get_backup_status($job_id = '')
    {
        if ($job_id != '')
        {
            $response = $this->api_client->jobs()->get_job_status($job_id);
        }
        else
        {
            $response = $this->api_client->system($this->system_id)->get_pending_jobs();
        }
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $this->api_client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
        elseif ($job_id != '' && $response['data']['job_result'][0] === FALSE)
        {
            $exp_msg = _('An API error occurred - backup could not be launched. Please try again.');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return $response['data'];
    }

    /**
     * This function deletes system backups by backup files
     *
     * @param string   $backup_files  Backup Files
     *
     * @throws Exception  If an error occurred
     *
     * @return string
     */
    public function delete_backup_by_files($backup_files)
    {
        $response = $this->api_client->system($this->system_id)->delete_backup($this->system_id, $this->backup_type, $backup_files);
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $this->api_client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return $response['data'];
    }

    /**
     * This function downloads a backup by its file name
     *
     * @param string   $backup_file  Backup File
     *
     * @throws Exception  If an error occurred
     *
     * @return string
     */
    public function download_backup($backup_file)
    {
        $response = $this->api_client->system($this->system_id)->download_backup($this->system_id, $this->backup_type, $backup_file);
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $this->api_client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return $response['data'];
    }

    /**
     * This function stores the file size of a backup in session
     *
     * @param string    $backup_file
     * @param number    $backup_size
     */
    public function set_session_file_size($backup_file, $backup_size)
    {
        $_SESSION['_av_backup'][$this->system_id][$backup_file] = $backup_size;
    }

    /**
     * This function gets the previously session saved file size of a backup
     *
     * @param string    $backup_file
     *
     * @return number
     */
    public function get_session_file_size($backup_file)
    {
        return $_SESSION['_av_backup'][$this->system_id][$backup_file];
    }

    /**
     * This function stores the last backup date in session
     *
     * @param string    $backup_date
     */
    public function set_session_last_date($backup_date)
    {
        $_SESSION['_av_backup'][$this->system_id]['last_date'] = $backup_date;
    }

    /**
     * This function gets the previously session saved last date of the backups
     *
     * @return number
     */
    public function get_session_last_date()
    {
        if ($_SESSION['_av_backup'][$this->system_id]['last_date'] != '')
        {
            return $_SESSION['_av_backup'][$this->system_id]['last_date'];
        }
        // Store the last backup date
        else
        {
            $_backup_list_arr = $this->get_backup_list();

            $_last_date = strtotime("19700101000000");

            foreach ($_backup_list_arr as $_backup_data)
            {
                if ($_backup_data['date'] > $_last_date)
                {
                    $_last_date = $_backup_data['date'];
                }
            }

            $tz         = Util::get_timezone();
            $_last_date = gmdate('U', $_last_date + (3600*$tz));

            $this->set_session_last_date($_last_date);

            return $_last_date;
        }
    }
}

/* End of file av_backup.inc */
/* Location: ../include/classes/av_backup.inc */
