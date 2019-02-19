<?php
/**
* av_plugin.inc
*
* File av_plugin.inc is used to:
*   - To manage plugins and custom plugins
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
* Av_plugin Class
*
* Class for managing plugins
*
* @package    ossim-framework\Alienvault_Components
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/

class Av_plugin
{
    private $api_client;
    public $upload_path = "/usr/share/ossim/www/uploads";
    /**
     * Download Path
     *
     * @var    string
     * @access public
     */
    //public $download_path = '/var/alienvault/backup/downloaded'; TODO


    /**
     * Class constructor
     *
     * This function sets up the class
     *
     */
    public function __construct()
    {
        $this->api_client = new Alienvault_client();
    }


    /**
     * This function gets the system backups
     *
     * @throws Exception  If an error occurred
     *
     * @return array
     */
    public function get_plugin_list()
    {
        $response = $this->api_client->plugin()->get_plugin_list();
        $response = json_decode($response, TRUE);
        
        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $this->api_client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
        
        $data = (is_array($response['data']['plugins'])) ? $response['data']['plugins'] : array();

        return $data;
    }
    
    /**
     * This function download a plugin by its name
     *
     * @param  string     $plugin_name
     *
     * @throws Exception  If an error occurred
     */
    public function download_plugin($plugin_name)
    {
        $response = $this->api_client->plugin()->download_plugin($plugin_name);
		$response = html_entity_decode($response);
        $response = json_decode($response, TRUE);
    
        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $this->api_client->get_error_message($response);
    
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
        
        return ($response['data']['contents'] != '') ? $response['data']['contents'] : '';
    }
    
    
    /**
     * This function deletes a plugin by name
     *
     * @param  string     $plugin_name
     *
     * @throws Exception  If an error occurred
     */
    public function delete_plugin($plugin_name)
    {
        $response = $this->api_client->plugin()->delete_plugin($plugin_name);
        $response = json_decode($response, TRUE);
    
        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $this->api_client->get_error_message($response);
    
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
    }
    
    
    /**
     * This function gets the system backups
     * 
     * @param  string     $file
     *
     * @throws Exception  If an error occurred
     *
     * @return array
     */
    public function check_plugin_file($file)
    {
        $response = $this->api_client->plugin()->check_plugin_file($file);
        $response = json_decode($response, TRUE);
    
        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $this->api_client->get_error_message($response);
    
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
    
        $data = array();
        
        $data['plugin_id']         = $response['data']['header_data']['plugin_id'];
        $data['plugin_name']       = $response['data']['header_data']['plugin_name'];
        $data['last_modification'] = $response['data']['header_data']['plugin_name'];
        
        if (is_array($response['data']['header_data']['vendors_info']))
        {
            $data['vendor'] = $response['data']['header_data']['vendors_info'][0]['vendor'];
            $data['model']  = $response['data']['header_data']['vendors_info'][0]['model'];
            $data['product_type']  = $response['data']['header_data']['vendors_info'][0]['product_type'];
        }
    
        return $data;
    }

    
    /**
     * This function saves the uploaded plugin from wizard
     *
     * @param  boolean     $overwrite
     *
     * @throws Exception  If an error occurred
     */
    public function save_plugin($overwrite = FALSE)
    {
        $file    = self::get_wizard_data('file');
        $vendor  = self::get_wizard_data('vendor');
        $model   = self::get_wizard_data('model');
        $version = self::get_wizard_data('version');
        $product_type = self::get_wizard_data('product_type');
        $response = $this->api_client->plugin()->save_plugin($file, $vendor, $model, $version, $product_type, $overwrite);
        $response = json_decode($response, TRUE);
    
        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $this->api_client->get_error_message($response);
    
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
        
        return (is_array($response['data'])) ? $response['data'] : array();
    }
    
    
    
    
    /********************************
     ******* STATIC FUNCTIONS *******
     *******************************/
    /**
     * This function gets the previously saved field in 'New Plugin' wizard
     * 
     * @param  string  $field     Variable name saving in session
     * 
     * @return string
     */
    public static function get_wizard_data($field)
    {
        return $_SESSION['_wizard_custom_plugin'][$field];
    }
    
    /**
     * This function sets the a input field in 'New Plugin' wizard
     * 
     * @param string $field
     * @param string $value
     */
    public static function set_wizard_data($field, $value)
    {
        $_SESSION['_wizard_custom_plugin'][$field] = $value;
    }


    public static function clear() {
        $_SESSION['_wizard_custom_plugin'] = array();
    }
}

/* End of file av_plugin.inc */
/* Location: ../include/classes/av_plugin.inc */
