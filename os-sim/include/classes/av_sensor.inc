<?php
/**
* av_sensor.inc
*
* File av_sensor.inc is used to:
*   - To manage sensors
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
* Av_sensor Class
*
* Class for managing sensors
*
* @package    ossim-framework\Alienvault_Components
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/

class Av_sensor extends Av_component
{
    /**
    * Sensor priority
    *
    * @var integer
    * @access private
    */
    private $priority;

    /**
    * Sensor timezone
    *
    * @var double
    * @access private
    */
    private $tzone;

    /**
    * Sensor properties
    *
    * @var array
    * @access private
    */
    private $properties;

    /**
    * Sensor status
    *
    * @var integer
    * @access private
    */
    private $status;

    /**
    * Sensor remote root pass
    *
    * @var string
    * @access private
    */
    private $rpass;


    /**
    * Class constructor
    *
    * This function sets up the class
    *
    * @param string  $id  Sensor ID
    */
    public function __construct($id)
    {
        $this->set_id($id);
        $this->ctx         = array();
        $this->name        = NULL;
        $this->ip          = NULL;
        $this->descr       = NULL;
        $this->icon        = NULL;
        $this->priority    = 5;
        $this->tzone       = 0;
        $this->port        = 40001;
        $this->properties  = array(
            'version'           => '',
            'has_nagios'        => 0,
            'has_ntop'          => 1,
            'has_vuln_scanner'  => 1,
            'has_kismet'        => 0,
            'ids'               => 0,
            'passive_inventory' => 0,
            'netflows'          => 0            
        );
        $this->status = 0;
        $this->rpass = '';
    }


    /**
    * This function returns the sensor version
    *
    * @return string
    */
    public function get_version()
    {
        return $this->properties['version'];
    }

    /**
    * This function returns the sensor priority
    *
    * @return integer
    */
    public function get_priority()
    {
        return $this->priority;
    }


    /**
    * This function returns the sensor timezone
    *
    * @return double
    */
    public function get_tzone()
    {
        return $this->tzone;
    }


    /**
    * This function returns the sensor properties
    *
    * @return array
    */
    public function get_properties()
    {
        return $this->properties;
    }


    /**
    * This function returns the specific sensor property
    *
    * @param string $property  Sensor property
    *
    * @return string
    */
    public function get_property($property)
    {
        if (array_key_exists($property, $this->properties))
        {
            return $this->properties[$property];
        }

        return FALSE;
    }


    /**
    * This function returns the sensor status
    *
    * @return integer
    */
    public function get_status()
    {
        return $this->status;
    }

    /**
    * This function returns remote root pass
    *
    * @return string
    */
    public function get_rpass()
    {
        return $this->rpass;
    }

    /**
    * This function returns the av_component type
    *
    * @return string
    */
    public function get_av_type()
    {
        return 'sensor';
    }


    /**
    * This function add new sensor context
    *
    * @param string $ctx Sensor context
    * @param string $name Context name
    *
    * @return void
    */
    public function add_new_ctx($ctx, $name)
    {
        $ctx = strtoupper($ctx);

        if (!empty($ctx) && valid_hex32($ctx))
        {
            $this->ctx[$ctx] = $name;
        }
        else
        {
            $exp_msg = _('Error! Sensor context is not valid');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
    }


    /**
    * This function sets sensor contexts
    *
    * @param array $ctxs Sensor contexts
    *
    * @return void
    */
    public function set_ctx($ctxs)
    {
        $this->ctx = array();

        if (is_array($ctxs) && !empty($ctxs))
        {
            foreach($ctxs as $ctx => $name)
            {
                $this->add_new_ctx($ctx, $name);
            }
        }
    }


    /**
    * This function sets the sensor priority
    *
    * @param integer $priority  Sensor priority
    *
    * @return void
    */
    public function set_priority($priority)
    {
        settype($priority, 'integer');

        if (!is_int($priority) || $priority < 1 || $priority > 10)
        {
            $exp_msg = _('Error! Sensor priority is not valid');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $this->priority = $priority;
    }


    /**
    * This function sets the sensor timezone
    *
    * @param double $tzone  Sensor timezone
    *
    * @return void
    */
    public function set_tzone($tzone)
    {
        settype($tzone, 'float');

        if (!is_float($tzone) || $tzone < -12 || $tzone > 14)
        {
            $exp_msg = _('Error! Sensor timezone is not valid');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $this->tzone = $tzone;
    }


    /**
    * This function sets the specific sensor property
    *
    * @param string          $p_type   Sensor property type
    * @param integer|string  $p_value  Sensor property value
    *
    * @return void
    */
    private function add_new_property($p_type, $p_value)
    {
        if (!array_key_exists($p_type, $this->properties))
        {
            $exp_msg = _('Error! Sensor property not allowed');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $cnd_1 = ($p_type == 'version' && $p_value!='' && !preg_match('/\d+\.\d+\.\d+/', $p_value));
        $cnd_2 = ($p_type != 'version' && (!is_numeric($p_value) || ($p_value != 0 && $p_value != 1)));

        if ($cnd_1 || $cnd_2)
        {
            $exp_msg = _('Error! Sensor property value not allowed');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $this->properties[$p_type] = $p_value;
    }


    /**
    * This function sets the sensor properties
    *
    * @param array $propertites  Sensor properties
    *
    * @return void
    */
    public function set_properties($properties)
    {
        $this->properties  = array(
            'version'           => '',
            'has_nagios'        => 0,
            'has_ntop'          => 1,
            'has_vuln_scanner'  => 1,
            'has_kismet'        => 0,
            'ids'               => 0,
            'passive_inventory' => 0,
            'netflows'          => 0            
        );

        if (is_array($properties) && !empty($properties))
        {
            foreach($properties as $p_type => $p_value)
            {
                $this->add_new_property($p_type, $p_value);
            }
        }
    }


    /**
    * This function sets the sensor status
    *
    * @param integer $status  Sensor status
    *
    * @return void
    */
    public function set_status($status)
    {
        if (!is_numeric($status) || ($status != 0 && $status != 1))
        {
            $exp_msg = _('Error! Sensor status is not valid');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $this->status = $status;
    }

    /**
    * This function sets remote root pass
    *
    * @param string $pass  Remote root password
    *
    * @return void
    */
    public function set_rpass($pass)
    {
        $this->rpass = $pass;
    }

    /**
    * This function sets the sensor data from database
    *
    * @param object  $conn   Database access object
    * @param boolean $cache  [Optional] Use cached information
    *
    * @throws Exception  If sensor ID doesn't exists in the System or there is a connection error
    *
    * @return void
    */
    public function load_from_db($conn, $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        //Getting host information

        $query = 'SELECT HEX(s.id) AS id, INET6_NTOA(s.ip) AS sensor_ip, s.*, sp.*
            FROM sensor s, sensor_properties sp
            WHERE s.id = sp.sensor_id
            AND s.id = UNHEX(?)';

        $id = $this->get_id();

        $params = array($id);

        $rs = ($cache == TRUE) ? $conn->CacheExecute($query, $params) : $conn->Execute($query, $params);

        if (empty($rs->fields['id']))
        {
            $exp_msg = _('Error! Sensor not found');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $this->ctx         = self::get_ctx_by_id($conn, $id);
        $this->name        = $rs->fields['name'];
        $this->ip          = $rs->fields['sensor_ip'];
        $this->descr       = $rs->fields['descr'];
        $this->icon        = NULL;
        $this->priority    = $rs->fields['priority'];
        $this->tzone       = $rs->fields['tzone'];
        $this->port        = $rs->fields['port'];
        $this->properties  = array(
            'version'           => $rs->fields['version'],
            'has_nagios'        => $rs->fields['has_nagios'],
            'has_ntop'          => $rs->fields['has_ntop'],
            'has_vuln_scanner'  => $rs->fields['has_vuln_scanner'],
            'has_kismet'        => $rs->fields['has_kismet'],
            'ids'               => $rs->fields['ids'],
            'passive_inventory' => $rs->fields['passive_inventory'],
            'netflows'          => $rs->fields['netflows']            
        );

        $this->status = $rs->fields['connect'];
    }


    /**
    * This function saves sensor into database
    *
    * @param object  $conn   Database access object
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean
    */
    public function save_in_db($conn, $report_changes = TRUE)
    {
        Ossim_db::check_connection($conn);

        $is_in_db = self::is_in_db($conn, $this->get_id());
        
        $query = "CALL sensor_update (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? ,?, ?, ?, ?, ?, ?)";

        $properties   = $this->get_properties();
        $entities     = $this->get_ctx();
        $old_entities = self::get_ctx_by_id($conn, $this->get_id());
        $old_entity   = key($old_entities);
        $new_entity   = key($entities);

        $params = array(
            Session::get_session_user(),
            $this->get_id(),
            $this->get_ip(),
            $this->get_name(),
            $this->get_priority(),
            $this->get_port(),
            $this->get_tzone(),
            $this->get_descr(FALSE),
            implode(',', array_keys($entities)),
            $properties['version'],
            $properties['has_nagios'],
            $properties['has_ntop'],
            $properties['has_vuln_scanner'],
            $properties['has_kismet'],
            $properties['ids'],
            $properties['passive_inventory'],
            $properties['netflows']
        );

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if ($rs->fields['sensor_id'] != $this->get_id())
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $rs->fields['status']);
        }

        $rs->Close();

        // Set ctx (Only for OSSIM sensors)
        if ($properties['version'] != '' && $old_entity != $new_entity)
        {
           $entity_id   = Util::uuid_format($new_entity);
           $sensor_uuid = Util::uuid_format($this->get_id());

           $client      = new Alienvault_client();
           $response    = $client->sensor($sensor_uuid)->set_ctx($entity_id);
           $response    = @json_decode($response, TRUE);

           if (!$response || $response['status'] == 'error')
           {
               $exp_msg = $client->get_error_message($response);
               
               Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
           }
        }

        //Log sensor update
        $infolog = array ($this->get_name(), '['.$this->get_ip().']');

        if ($is_in_db == TRUE)
        {
            Log_action::log(34, $infolog);
        }
        else
        {
            Log_action::log(33, $infolog);
        }

        // Refresh frameworkd
        if ($report_changes == TRUE)
        {
            try
            {
                self::report_changes('sensors');
            }
            catch(Exception $e)
            {
                Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
            }
        }

        return TRUE;
    }



    /*****************************************************
     *
     * Vulnerability Scanner functions (Open VAS)
     *  - check_vs_connection($conn)
     *  - is_vs_configured
     *  - get_vs_credentials
     *  - get_vs_load
     *  - save_vs_credentials
     *  - reorder_vs_by_load($conn, $sensors)
     *
     *****************************************************/



    /**
    * This function checks status from Open VAS service
    *
    * @param object  $conn   Database access object
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public function check_vs_connection($conn)
    {
        Ossim_db::check_connection($conn);

        //Get Open VAS credentials
        $ov_credentials = $this->get_vs_credentials($conn);

        $port     = $ov_credentials['port'];
        $user     = $ov_credentials['user'];
        $password = $ov_credentials['password'];

        $omp    = new Omp($this->get_ip(), $port, $user, $password);

        $status = $omp->check_connection();

        return $status;
    }


    /**
    * This function checks vulnerability scanner is configured
    *
    * @param object  $conn   Database access object
    *
    * @return boolean
    */
    public function is_vs_configured($conn)
    {
        Ossim_db::check_connection($conn);

        $configured = FALSE;

        $query  = 'SELECT id FROM vuln_nessus_servers WHERE hostname = ?';

        $params = array($this->get_id());

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!$rs->EOF)
        {
            $configured = TRUE;
        }

        return $configured;
    }


    /**
    * This function returns vulnerability scanner credentials
    *
    * @param object  $conn   Database access object
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public function get_vs_credentials($conn)
    {
        Ossim_db::check_connection($conn);

        $credentials = array();

        $query  = "SELECT port, user, PASSWORD, AES_DECRYPT(PASSWORD, ?) AS pass_decrypt, max_scans
            FROM vuln_nessus_servers
            WHERE hostname = ?";

        $params = array(Util::get_encryption_key(), $this->get_id());

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!$rs->EOF)
        {
            $credentials = array(
                'port'      => $rs->fields['port'],
                'user'      => $rs->fields['user'],
                'password'  => ((empty($rs->fields['pass_decrypt'])) ? $rs->fields['PASSWORD'] : $rs->fields['pass_decrypt']),
                'max_scans' => $rs->fields['max_scans'],
            );
        }

        return $credentials;
    }


    public function get_ossim_conf()
    {
        $conf = $GLOBALS['CONF'];
        if (!$conf)
        {
            $conf = new Ossim_conf();
            $GLOBALS['CONF'] = $conf;
        }
        return $conf;
    }


    public function get_vs_default_credentials()
    {
        $conf = $this->get_ossim_conf();

        return array(
            'user'     => $conf->get_conf("nessus_user", FALSE),
            'password' => $conf->get_conf("nessus_pass", FALSE),
        );
    }


    public function get_vs_admin_credentials() 
    {
        $conf = $this->get_ossim_conf();

        return array(
            'user'     => $conf->get_conf("nessus_admin_user", FALSE),
            'password' => $conf->get_conf("nessus_admin_pass", FALSE),
        );
    }


    /**
    * This function gets free slots (load) from sensor
    *
    * @param object  $conn  Database access object
    *
    * @throws Exception  If a connection error occurred
    *
    * @return string
    */
    public function get_vs_load($conn)
    {
        Ossim_db::check_connection($conn);

        $res = 'N/A';

        $query  = 'SELECT ( current_scans / max_scans ) AS vs_load
            FROM vuln_nessus_servers
            WHERE hostname = ?';

        $params = array($this->get_id());

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $load = $rs->fields['vs_load'];

        if ($load != '')
        {
            $perc = round($load*100, 1);

            if ($perc >= 60)
            {
                $res = '<font color=#ff0000>'.$perc.'%</font> <img class="vuln_tooltip" data-title="'.sprintf(_("The scan load in your appliance is already %s. Introducing a new concurrent scan job will put the AlienVault sensor under a heavy load. Please make sure you want to create a new scan job."),$perc.'%').'" src="/ossim/pixmaps/v_error.png">';
            }
            else
            {
                $res = $perc.'%';
            }
        }

        return $res;
    }


    /**
    * This function saves default vulnerability scanner configuration
    *
    * @param object  $conn   Database access object
    * @param array   $data   Scanner credentials
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean
    */
    public function configure_vs($conn, $data)
    {
        $conf = $this->get_ossim_conf();

        $params = array(
            $data['name'],
            'Remote Host',
            $this->get_id(),
            intval($conf->get_conf("nessus_port")),
            '',
            '',
            '2.2.10',
            'GPL only',
            '200704181215',
            '5',
            '0',
            'R',
            '',
            'admin',
            NULL ,
            'A',
            $data['enabled']
        );

        $query = 'REPLACE INTO vuln_nessus_servers (name, description, hostname,
            port, user, PASSWORD, server_nversion, server_feedtype, server_feedversion,
            max_scans, current_scans, TYPE, site_code, owner, checkin_time, status , enabled)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        return TRUE;       
    }


    /**
    * This function saves vulnerability scanner credentials
    *
    * @param object  $conn   Database access object
    * @param array   $data   Scanner credentials
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean
    */
    public function save_vs_credentials($conn, $data)
    {
        Ossim_db::check_connection($conn);

        if (!$this->is_vs_configured($conn))
        {
            $this->configure_vs($conn, $data);
        }

        if (count($data) > 4 && count($data) < 7)
        {
            $vs_prev_credentials = $this->get_vs_credentials($conn);

            $new_user = $data['user'];
            $raw_password = $data['password'];
            $new_password = (Util::is_fake_pass($raw_password)) ? $vs_prev_credentials['password'] : $raw_password;
            $restore_defaults = ( empty($new_password) or empty($new_user) );

            if ($restore_defaults) {
                // Special case: restore default credentials;
                $vs_default_credentials = $this->get_vs_default_credentials();
                $new_user = $vs_default_credentials['user'];
                $new_password = $vs_default_credentials['password'];
            }

            // Starts update transaction;
            $conn->Execute('START TRANSACTION;');

            $params = array(
                $data['name'],
                $new_user,
                $data['port'],
                $data['vuln_max_scans'],
                $data['enabled'],
                0,
                $new_password,
            );

            $query = 'UPDATE vuln_nessus_servers
                SET name= ?, user= ?, port = ?, max_scans= ?,
                enabled = ?, current_scans = ?';

            if($restore_defaults)
            {
                $query .= ', PASSWORD = ?'; // Reset to default password.
            }
            else
            {
                $params[] = Util::get_encryption_key();
                $query .= ', PASSWORD = AES_ENCRYPT(?,?)';
            }
            
            $params[] = $this->get_id();

            $query .= ' WHERE hostname = ?';

            $rs = $conn->Execute($query, $params);
            $result = TRUE;

            // Update OpenVAS credentials;
            if ($rs)
            {
                $result = $this->update_openvas_instance($conn, $vs_prev_credentials["port"], $new_user, $new_password);
            }

            if (!$rs or !$result)
            {
                $db_error_msg = $conn->ErrorMsg();
                $conn->Execute('ROLLBACK;');
                Av_exception::throw_error(Av_exception::DB_ERROR, empty($db_error_msg) ? "Failed to update OpenVAS credentials" : $db_error_msg);
            }
            else
            {
                $conn->Execute('COMMIT;');
            }
        }
        
        return TRUE;
    }


    public function update_openvas_instance($conn, $port_connected, $new_user, $new_password)
    {
        $response = TRUE;

        $vs_super_user=$this->get_vs_admin_credentials($conn);

        $omp = new Omp($this->get_ip(), $port_connected, $vs_super_user['user'], $vs_super_user['password']);
        $omp_user = $omp->get_omp_user_by_name($new_user);       
           
        if ($omp_user)
        {
            $response = $omp->update_user($omp_user, $new_user, $new_password);        
        } else
        {
            $response = $omp->create_user($new_user, $new_password);
        }

        if (!$response){
            // Log error if update procedure failed.
            Av_exception::write_log(Av_exception::USER_ERROR, $omp->get_error_msg());
        }

        return $response;
    }


    /**
    * This function reorders vulnerability scanners by load
    *
    * @param object  $conn      Database access object
    * @param array   $sensors   Vulnerability sensors
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function reorder_vs_by_load($conn, $sensors)
    {
        Ossim_db::check_connection($conn);

        $r_sensors = array();

        if(is_array($sensors) && !empty($sensors))
        {
            $sensor_where = "('".implode("','", $sensors)."')";

            $query = "SELECT UPPER(v.hostname) AS sid, ( v.current_scans / v.max_scans ) AS vs_load
                FROM vuln_nessus_servers v, sensor s
                WHERE HEX(s.id) = v.hostname
                AND v.enabled = 1
                AND v.hostname IN $sensor_where
                ORDER BY vs_load ASC, s.priority DESC";


            $rs = $conn->Execute($query);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }

            while (!$rs->EOF)
            {
                $id   = strtoupper($rs->fields['sid']);
                $perc = round($rs->fields['vs_load']*100, 1);

                if ($perc >= 60)
                {
                    $r_sensors[$id] = '<font color=#ff0000>'.$perc.'%</font> <img class="vuln_tooltip" data-title="'.sprintf(_("The scan load in your appliance is already %s. Introducing a new concurrent scan job will put the AlienVault sensor under a heavy load. Please make sure you want to create a new scan job."),$perc.'%').'" src="/ossim/pixmaps/v_error.png">';
                }
                else
                {
                    $r_sensors[$id] = $perc . '%';
                }

                $rs->MoveNext();
            }
        }

        return $r_sensors;
    }



    /*****************************************************
     *
     * Nagios functions
     *  - get_nagios_credentials
     *  - save_nagios_credentials
     *  - get_nagios_url
     *
     *****************************************************/



    /**
    * This function returns nagios credentials
    *
    * @param object  $conn   Database access object
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public function get_nagios_credentials($conn)
    {
        Ossim_db::check_connection($conn);

        $credentials = array();

        $id = $this->get_id();

        $config = new User_config($conn);

        $credentials = array(
            'user'     => $config->get("system", $id, "simple", "nagios_user"),
            'password' => $config->get("system", $id, "simple", "nagios_pass")
        );

        return $credentials;
    }


    /**
    * This function saves nagios credentials
    *
    * @param object  $conn   Database access object
    * @param array   $data   Nagios credentials
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean
    */
    public function save_nagios_credentials($conn, $data)
    {
        Ossim_db::check_connection($conn);

        $res = TRUE;

        $config = new User_config($conn);

        $id = $this->get_id();

        $res = $config->set("system", $id, $data['user'], "simple", "nagios_user");

        if ($res == TRUE && !Util::is_fake_pass($data['password']))
        {
            $res = $config->set("system", $id, $data['password'], "simple", "nagios_pass");
        }

        return $res;
    }


    /**
    * This function returns url and stream context to connect with Nagios CGI
    *
    * @param string  $user   Logged user
    * @param string  $pass   User password
    *
    * @throws Exception  If an error occurred
    *
    * @return array
    */
    public function get_nagios_url($user, $pass)
    {
        $ip = $this->get_ip();

        $url = "https://".$ip."/nagios3/";

        $headers = Util::getheaders($url);

        // Found OSSIM in remote IP:nagios3
        if (preg_match("/\/session\/login\.php/", $headers) == TRUE)
        {
            $ssid = Util::get_session_cookie($ip, $user, $pass);

            if (preg_match("/PHPSESSID/", $ssid) == TRUE)
            {
                $s_context = array('http'  => array('header' => "Cookie: $ssid"));

                $data = array(
                    'url'       => "https://".$ip."/nagios3/",
                    's_context' => $s_context
                );
            }
            else
            {
                $exp_msg = _('Unable to login into remote host: Check credentials and remote key parameter');

                Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
            }
        }
        else // Not found secured_nagios3: Try not secured
        {
            $url = "http://".$ip."/nagios3/";

            $headers = Util::getheaders($url);

            if (preg_match("/401 Authorization Required/i", $headers) == TRUE)
            {
                // Testing Basic Auth
                $headers = Util::getheaders($url, $user, $pass);

                if (preg_match("/200 OK/", $headers) == TRUE && preg_match("/401 Authorization Required/i", $headers) == FALSE)
                {
                    $s_context = array(
                        'http'  => array(
                            'method' => "GET",
                            'header' => "Accept: application/xml\r\n".
                            "Authorization: Basic " . base64_encode("$user:$pass") . "\r\n"
                        )
                    );

                    $data = array(
                        'url'       => $url,
                        's_context' => $s_context
                    );
                }
                else
                {
                    $exp_msg = _('Basic Authentication failed: Check credentials in Availability Monitoring configuration');

                    Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
                }
            }
            else
            {
                $exp_msg = _('Unable to connect with Availability Monitoring');

                Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
            }
        }

        return $data;
    }



    /*****************************************************
     *
     * NFSEN functions
     *  - get_nfsen_channel_id
     *  - get_nfsen_channel_name
     *  - is_channel_allowed
     *  - delete_nfsen_channel
     *
     *****************************************************/



    /**
    * This function gets the NFSEN channel from sensor
    *
    * @param object $conn  Database access object
    *
    * @throws Exception  If a connection error occurred
    *
    * @return string
    */
    public function get_nfsen_channel_id($conn)
    {
        Ossim_db::check_connection($conn);

        $id   = $this->get_id();
        $name = $this->get_name();

        $avc_id = NULL;

        $av_id = Av_center::get_system_id_by_component($conn, $id);

        if (valid_hex32($av_id['non-canonical']) == TRUE)
        {
           $avc_id = $av_id['non-canonical'];
        }

        $source = NULL;

        require_once "/usr/share/ossim/www/sensor/nfsen_functions.php";

        $sources = get_nfsen_sensors();


        // AlienVault Center UUID is used by default
        if (!empty($avc_id) && (array_key_exists($avc_id, $sources) || empty($sources)))
        {
           $source = $avc_id;
        }
        elseif (array_key_exists($id, $sources))
        {
           $source = $id;
        }
        elseif (array_key_exists($name, $sources))
        {
           $source = $name;
        }
        // The sensor is not configured yet, return its ID, never empty
        else
        {
            $source = (!empty($avc_id)) ? $avc_id : $id;
        }

        return $source;
    }


    /**
    * This function returns the NFSEN channel name related to sensor
    *
    * @param object $conn      Database access object
    * @param string $channel   Channel (Sensor ID or AVcenter UUID or sensor name)
    *
    * @throws Exception  If a connection error occurred
    *
    * @return string  'Unknown' in case the name couldn't be resolved.
    */
    public static function get_nfsen_channel_name($conn, $channel)
    {
        Ossim_db::check_connection($conn);

        if(!valid_hex32($channel))
        {
            return (empty($channel) ? _('Unknown') : $channel);
        }

        $name = self::get_name_by_id($conn, $channel);

        if(empty($name))
        {
            $sensor_ids = Av_center::get_component_id_by_system($conn, $channel, 'sensor');

            if (is_array($sensor_ids) && !empty($sensor_ids))
            {
                $id = $sensor_ids['non-canonical'];

                $name = self::get_name_by_id($conn, $id);
            }
        }

        return (empty($name)? _('Unknown') : $name);
    }


    /**
    * This function checks if the current user has permissions to see the flows related to the sensor.
    *
    * @param object $conn      Database access object
    * @param string $channel   Channel (Sensor ID or AVcenter UUID or sensor name)
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean
    */
    public static function is_channel_allowed($conn, $channel)
    {
        Ossim_db::check_connection($conn);

        if($channel == '')
        {
            return FALSE;
        }

        if(Session::am_i_admin())
        {
            return TRUE;
        }

        $id = '';

        //Channel is a sensor name
        if(!valid_hex32($channel))
        {
            $sensors = self::get_id_by_name($conn, $channel);

            if (is_array($sensors) && !empty($sensors))
            {
                 $sensor_ids = array_keys($sensors);

                 $id = $sensor_ids[0];
            }
        }
        else
        {
            if (self::is_in_db($conn, $channel))
            {
                $id = $channel;
            }
            else
            {
                $sensor_ids = Av_center::get_component_id_by_system($conn, $channel, 'sensor');

                if (is_array($sensor_ids) && !empty($sensor_ids))
                {
                    $id = $sensor_ids['non-canonical'];
                }
            }
        }

        return self::is_allowed($conn, $id);
    }


    /**
    * This function deletes a sensor from NFSEN config file (/etc/nfsen.conf)
    *
    * @param object $conn      Database access object
    * @param string $channel   Channel (Sensor ID or AVcenter UUID or sensor name)
    *
    * @throws Exception  If a connection error occurred
    *
    * @return void
    */
    public function delete_nfsen_channel($conn)
    {
        Ossim_db::check_connection($conn);

        $id = $this->get_id();

        //Get all possible channels
        $nfsen_ids[$id] = $id;
        $nfsen_ids[$this->get_name()] = $this->get_name();

        $avc_uuid = Av_center::get_system_id_by_component($conn, $id);

        if (!empty($avc_uuid['non-canonical']))
        {
            $nfsen_ids[$avc_uuid['non-canonical']] = $avc_uuid['non-canonical'];
        }


        require_once '/usr/share/ossim/www/nfsen/conf.php';
        require_once '/usr/share/ossim/www/sensor/nfsen_functions.php';

        $nfsen_sensors = @file($nfsen_conf);

        $sensors  = array();

        if (is_array($nfsen_sensors) && !empty($nfsen_sensors))
        {
            $pattern_1 = '/\s*^\#/';
            $pattern_2 = "/'([^']+)'\s+\=\>\s*\{\s*'port'\s+\=\>\s+'(\d+)',\s+'col'\s+=>\s+'(\#......)',\s+'type'\s+=>\s+'([^']+)'/";

            foreach ($nfsen_sensors as $nfsen_sensor)
            {
                if (preg_match($pattern_1, $nfsen_sensor) == FALSE && preg_match($pattern_2, $nfsen_sensor, $found) == TRUE)
                {
                    if(!array_key_exists($found[1], $nfsen_ids))
                    {
                        $sensors[$found[1]]['port']  = $found[2];
                        $sensors[$found[1]]['color'] = $found[3];
                        $sensors[$found[1]]['type']  = $found[4];
                    }
                }
            }
        }

        // Update the configuration file
        set_nfsen_sensors($sensors);
        nfsen_reset();
    }


    /**
    * This function checks default options for default sensor
    *
    * @param object  $conn    Database access object
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean
    */
    public function check_default_sensor($conn)
    {
        Ossim_db::check_connection($conn);


        //Sensors
        $_query = 'SELECT count(*) FROM sensor';

        $rs = $conn->Execute($_query);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!$rs->EOF && $rs->fields[0] == 1)
        {
            $id = $this->get_id();


            $params = array($id);

            $queries[] = "INSERT IGNORE INTO net_sensor_reference (net_id, sensor_id)
                VALUES ((SELECT id FROM net WHERE name = 'Pvt_192'), UNHEX(?))";

            $queries[] = "INSERT IGNORE INTO net_sensor_reference (net_id, sensor_id)
                VALUES ((SELECT id FROM net WHERE name = 'Pvt_172'), UNHEX(?))";

            $queries[] = "INSERT IGNORE INTO net_sensor_reference (net_id, sensor_id)
                VALUES ((SELECT id FROM net WHERE name = 'Pvt_010'), UNHEX(?))";

            //Risk Maps

            $_query = 'SELECT HEX(member) FROM bp_asset_member WHERE member = UNHEX(?)';

            $rs = $conn->Execute($_query, $params);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }

            if (!$rs->EOF)
            {
                $queries[] = "INSERT IGNORE INTO bp_asset_member (member,type) VALUES (UNHEX(?),'host')";
            }

            $queries[] = "UPDATE risk_indicators SET type_name = UPPER(?) WHERE type = 'sensor' AND type_name = 'ossim'";


            //Sensors
            $_query = 'SELECT HEX(entity_id) FROM acl_sensors WHERE sensor_id = UNHEX(?)';

            $rs = $conn->Execute($_query, $params);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }

            if (!$rs->EOF)
            {
                $queries[] = "INSERT IGNORE INTO alienvault.acl_sensors (entity_id,sensor_id)
                    VALUES ((SELECT UNHEX(REPLACE (value, '-', '')) FROM config WHERE conf = 'default_context_id'), UNHEX(?))";
            }

            //Nessus Config
            $queries[] = "REPLACE INTO alienvault.config VALUES ('nessus_host','".$this->get_ip()."')";

            $uuid = Util::get_encryption_key();
            $queries[] = "REPLACE INTO alienvault.config VALUES ('nessus_pass', AES_ENCRYPT('ossim','$uuid'))";

            foreach ($queries as $query)
            {
                $rs = (preg_match('/\?/', $query)) ? $conn->Execute($query, $params) : $conn->Execute($query);

                if (!$rs)
                {
                    Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
                }
            }
        }

        return TRUE;
    }


    /**
     * This function try to attach a new component setting remote certificates
     *
     * @param object  $conn   Database access object
     *
     * @throws Exception  If a connection error occurred
     *        
     * @return boolean
     */
    public function set_component($conn)
    {
        Ossim_db::check_connection($conn);
        
        $client = new Alienvault_client();

        $response  = $client->system()->set_component($this->get_ip(), $this->get_rpass());
        $response  = @json_decode($response, TRUE);
        
        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $client->get_error_message($response);
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $query = "CALL system_update(?,'','','','','','','',?,'')";

        $params = array($response['data']['system_id'], $this->get_id());

        $rs = $conn->Execute($query, $params);
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
         
        $rs->Close();
        
        //Now we change the sensor name to the system name
        if ($response['data']['hostname'] != '')
        {  
            $this->set_name($response['data']['hostname']);
        }
        
        return TRUE;
    }


    /*************************************************
     *************** Static functions ****************
     *************************************************/


    /**
     * This function returns default sensor ID
     *
     * @param object  $conn   Database access object
     *
     * @throws Exception  If a connection error occurred
     *
     * @return string
     */
    public static function get_default_sensor($conn)
    {
        Ossim_db::check_connection($conn);

        $local_system_id = Util::get_system_uuid();
        $res             = Av_center::get_system_info_by_id($conn, $local_system_id);

        if ($res['status'] == 'success')
        {
            $sensor_id = $res['data']['sensor_id'];

            return $sensor_id;
        }

        $exp_msg = _('Error! Sensor not found');

        Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
    }


    /**
    * This function checks if sensor could be deleted
    *
    * @param object  $conn   Database access object
    * @param string  $id     Sensor ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean
    */
    protected static function can_delete($conn, $id)
    {
        Ossim_db::check_connection($conn);
        
        $unique_related_hosts = array();
        $unique_related_nets  = array();
        
        //Check orphan hosts if sensor is removed
        $query  = 'SELECT host.hostname, HEX(r2.sensor_id)
            FROM host_sensor_reference r1, host_sensor_reference r2, host
            WHERE r1.host_id = r2.host_id
            AND r1.host_id = host.id
            AND r1.sensor_id = UNHEX(?)
            GROUP BY r2.host_id
            HAVING COUNT(r2.sensor_id) = 1;';

        $params = array($id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!$rs->EOF)
        {
            while (!$rs->EOF)
            {
                $unique_related_hosts[] = $rs->fields['hostname'];
                $rs->MoveNext();
            }
        }

        //Check orphan nets if sensor is removed
        $query  = 'SELECT net.name, HEX(r2.sensor_id)
            FROM net_sensor_reference r1, net_sensor_reference r2, net
            WHERE r1.net_id = r2.net_id
            AND r1.net_id = net.id
            AND r1.sensor_id = UNHEX(?)
            GROUP BY r2.net_id
            HAVING COUNT(r2.sensor_id) = 1;';

        $params = array($id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!$rs->EOF)
        {
            while (!$rs->EOF)
            {
                $unique_related_nets[] = $rs->fields['name'];
                $rs->MoveNext();
            }
        }
        
        // Throw exception with error message nets + hosts
        if (count($unique_related_hosts) > 0 || count($unique_related_nets) > 0)
        {
            $msg = _('Sorry, sensor not deleted. If you do that, ');
            
            if (count($unique_related_hosts) > 0)
            {
                // Show the names only when not too many
                $unique_related_hosts_str = (count($unique_related_hosts) < 10) ? ': "'.implode('", "', $unique_related_hosts).'"' : '';
                
                $msg .= (count($unique_related_hosts) > 1)
                        ? _(count($unique_related_hosts).' assets will be misconfigured').$unique_related_hosts_str.'. '
                        : _('One asset will be misconfigured').$unique_related_hosts_str.'. ';
            }
            
            if (count($unique_related_nets) > 0)
            {
                // Show the names only when not too many
                $unique_related_nets_str  = (count($unique_related_nets)  < 10) ? ': "'.implode('", "', $unique_related_nets).'"'  : '';
                
                $msg .= (count($unique_related_nets) > 1)
                        ? _(count($unique_related_nets).' networks will be misconfigured').$unique_related_nets_str.'. '
                        : _('One network will be misconfigured').$unique_related_nets_str.'.';
            }
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $msg);
        }

        return TRUE;
    }


    /**
    * This function returns a sensor object
    *
    * @param object  $conn   Database access object
    * @param string  $id     Sensor ID
    * @param boolean $cache  [Optional] Use cached information
    *
    * @throws Exception  If a connection error occurred
    *
    * @return object
    */
    public static function get_object($conn, $id, $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        $sensor = NULL;

        $params = array($id);
        $query  = 'SELECT HEX(id) AS id FROM sensor WHERE id = UNHEX(?)';

        $rs = ($cache) ? $conn->CacheExecute($query, $params) : $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!$rs->EOF)
        {
            $sensor = new self($id);
            $sensor->load_from_db($conn, $cache);
        }

        return $sensor;
    }


    /**
    * This function returns hosts and networks related to sensor
    *
    * @param object  $conn       Database access object
    * @param string  $id         Sensor ID
    * @param string  $ctx_where  [Optional] Context allowed
    *
    * @throws Exception  If a connection error occurred
    *
    * @return arrays Lists of UUIDs related (hosts, nets)
    */
    public static function get_related_assets($conn, $id, $ctx_where = '')
    {
        Ossim_db::check_connection($conn);

        $hosts = array();
        $nets  = array();

        $params = (!is_array($id)) ? array($id) : $id;
        
        $sensor_list = '';
        foreach ($params as $id)
        {
            $sensor_list .= 'UNHEX(?),';
        }
        $sensor_list = rtrim($sensor_list, ',');

        //Nets
        $q_from  = 'sensor, net_sensor_reference';
        $q_from .= ($ctx_where != '') ? ', net' : '';

        $perms_where  = ($ctx_where != '') ? "AND net.id = net_sensor_reference.net_id AND net.ctx IN (".$ctx_where.")" : '';

        $query = "SELECT HEX(net_sensor_reference.net_id) AS net_id
            FROM $q_from
            WHERE sensor.id in ( $sensor_list ) AND
            sensor.id = net_sensor_reference.sensor_id $perms_where";

        $rs = $conn->Execute($query, $params); 

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $nets[$rs->fields['net_id']] = $rs->fields['net_id'];

            $rs->MoveNext();
        }


        // Hosts
        $q_from  = 'sensor, host_sensor_reference';
        $q_from .= ($ctx_where != '') ? ', host' : '';

        $perms_where  = ($ctx_where != '') ? "AND host.id = host_sensor_reference.host_id AND host.ctx IN (".$ctx_where.")" : '';

        $query = "SELECT HEX(host_sensor_reference.host_id) AS host_id
            FROM $q_from
            WHERE sensor.id in ( $sensor_list ) AND
            sensor.id = host_sensor_reference.sensor_id $perms_where";

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $hosts[$rs->fields['host_id']] = $rs->fields['host_id'];

            $rs->MoveNext();
        }

        return array($hosts, $nets);
    }

    /**
    * This function gets sensor interfaces
    *
    * @param string   $sensor_id          Sensor UUID/IP or local
    * @param boolean  $discard_disabled   Discard disabled interfaces
    *
    * @throws Exception  If an error occurred
    *
    * @return array
    */
    public static function get_interfaces($sensor, $discard_disabled = TRUE)
    {
        $data       = array();

        $system_id  = '';

        // Getting Sensor UUID by IP
        if ($sensor == 'local')
        {
            $system_id = 'local';
        }
        else if (Asset_host_ips::valid_ip($sensor))
        {
            $db   = new ossim_db();
            $conn = $db->connect();
        
            $sensor_id  = self::get_id_by_ip($conn, $sensor);
            
            $system_ids = Av_center::get_system_id_by_component($conn, $sensor_id);
            
            $system_id  = $system_ids['non-canonical'];
            
            $db->close();
        }
        else if (valid_hex32($sensor, TRUE))
        {
            $system_id = $sensor;
        }

        if (empty($system_id))
        {
            $exp_msg = _('Error! Sensor not found');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
        
        
        $client = new Alienvault_client();

        $response = $client->system($system_id)->get_interfaces();

        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        if (is_array($response['data']['interfaces']))
        {
            foreach ($response['data']['interfaces'] as $iface => $i_data)
            {
                //Skipping interface lo, tun, ...
                if (!preg_match('/^eth/', $iface))
                {
                    continue;
                }

                //Skipping disabled interfaces
                if ($discard_disabled == TRUE && $i_data['role'] == 'disabled')
                {
                    continue;
                }

                //Assembling the array with the interface data
                $data[$iface]['name']    = $iface;
                $data[$iface]['role']    = $i_data['role'];
                $data[$iface]['ip']      = empty($i_data['ipv4']['address']) ? '' : $i_data['ipv4']['address'];
                $data[$iface]['netmask'] = empty($i_data['ipv4']['netmask']) ? '' : $i_data['ipv4']['netmask'];
            }
        }

        return $data;
    }

    /**
    * This function checks if sensor exists into database
    *
    * @param object  $conn   Database access object
    * @param string  $id     Sensor ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean
    */
    public static function is_in_db($conn, $id)
    {
        Ossim_db::check_connection($conn);

        $res = FALSE;

        if(!valid_hex32($id))
        {
            return $res;
        }

        $query  = 'SELECT count(*) AS found FROM sensor WHERE id = UNHEX(?)';
        $params = array($id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (intval($rs->fields['found']) > 0)
        {
            $res = TRUE;
        }

        return $res;
    }


    /**
    * This function checks if sensor exists and it is allowed
    *
    * @param object  $conn   Database access object
    * @param string  $id     Sensor ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean
    */
    public static function is_allowed($conn, $id)
    {
        //Note: Parameter $conn is not used now (It is possible a future use: NO remove)

        //Ossim_db::check_connection($conn);

        return Session::sensorAllowed($id);
    }


    /**
    * This function deletes the sensor from database
    *
    * @param object   $conn             Database access object
    * @param boolean  $report_changes  [Optional] Report changes to other components
    *
    * @throws Exception  If an error occurred
    *
    * @return array
    */
    public function delete_from_db($conn, $report_changes = TRUE)
    {
        Ossim_db::check_connection($conn);

        $id = $this->get_id();

        // Check if there will be orphaned assets after the delete, if found throw exception
        self::can_delete($conn, $id);
        
        $params = array($id);

        //Get sensor name
        $_query = 'SELECT INET6_NTOA(ip) AS sensor_ip, name FROM sensor WHERE id = UNHEX(?)';

        $rs = $conn->Execute($_query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $s_name = $id;

        if (!$rs->EOF)
        {
            $s_name = $rs->fields['name'].' ('.$rs->fields['sensor_ip'].')';
        }

        //Begin transaction
        $conn->StartTrans();

        $queries = array();

        //Sensor entities
        $queries[] = 'DELETE FROM acl_sensors WHERE sensor_id = UNHEX(?)';

        //Sensor properties
        $queries[] = "DELETE FROM sensor_properties WHERE sensor_id = UNHEX(?)";

        //Sensor stats
        $queries[] = 'DELETE FROM sensor_stats WHERE sensor_id = UNHEX(?)';

        //Sensor locations
        $queries[] = 'DELETE FROM location_sensor_reference WHERE sensor_id = UNHEX(?)';

        //Vulnerabilities
        $queries[] = 'DELETE FROM vuln_job_schedule WHERE email = ?';
        $queries[] = 'DELETE FROM vuln_nessus_servers WHERE hostname = ?';

        $this->delete_nfsen_channel($conn);

        //Sensor host reference
        $queries[] = 'DELETE FROM host_sensor_reference WHERE sensor_id = UNHEX(?)';

        //Sensor net reference
        $queries[] = 'DELETE FROM net_sensor_reference WHERE sensor_id = UNHEX(?)';

        //Sensor
        $queries[] = 'DELETE FROM sensor WHERE id = UNHEX(?)';


        foreach ($queries as $query)
        {
            $rs = $conn->Execute($query, $params);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
        }

        // Deactivate sensor in Policies
        $sensor_policies = Policy_sensor_reference::get_policy_by_sensor($conn, $id);
        
        foreach($sensor_policies as $ps_data)
        {
            Policy::activate($conn, $ps_data->get_policy_id(), '0');
        }

        //Delete sensor in AlienVault Center
        Av_center::delete_component($conn, $id);

        //Finish transaction
        if ($conn->CompleteTrans())
        {
            //Log action
            $infolog = array($s_name);

            Log_action::log(35, $infolog);

            // Refresh frameworkd
            if ($report_changes == TRUE)
            {
                try
                {
                    self::report_changes('sensors');
                }
                catch(Exception $e)
                {
                    Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
                }
            }

            // Delete orphan systems
            Av_center::delete_orphan_systems($conn);
        }
        else
        {
            $exp_msg = _('Error! Sensor could not be deleted');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return TRUE;
    }
   

    /**
    * This function returns the sensor contexts
    *
    * @param object  $conn   Database access object
    * @param string  $id     Sensor ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_ctx_by_id($conn, $id)
    {
        Ossim_db::check_connection($conn);

        $entities = array();

        $query  = 'SELECT HEX(s.entity_id) AS entity_id, e.name AS entity_name
            FROM acl_entities e, acl_sensors s
            WHERE s.entity_id = e.id AND s.sensor_id = UNHEX(?) and e.entity_type = "context"';

        $params = array($id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $entities[$rs->fields['entity_id']] = $rs->fields['entity_name'];

            $rs->MoveNext();
        }

        if (empty($entities) && $id == '00000000000000000000000000000000') // For local sensor 0x0
        {
            $entities[Session::get_default_ctx()] = 'Default Context';
        }

        return $entities;
    }


    /**
    * This function returns the name from sensor
    *
    * @param object  $conn   Database access object
    * @param string  $id     Sensor ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return string
    */
    public static function get_name_by_id($conn, $id)
    {
        Ossim_db::check_connection($conn);

        $name = '';

        $query  = "SELECT name FROM sensor WHERE id = UNHEX(?)";

        $params = array($id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!$rs->EOF)
        {
            $name = $rs->fields['name'];
        }

        return $name;
    }


    /**
    * This function returns sensor ID from name
    *
    * @param object  $conn   Database access object
    * @param string  $name   Sensor name
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_id_by_name($conn, $name)
    {
        Ossim_db::check_connection($conn);

        $name = Asset_host::create_valid_name($name);

        $sensor_ids = array();

        //Only my contexts
        $ctx_where = Session::get_ctx_where();

        if (!empty($ctx_where))
        {
            $query = "SELECT HEX(s.id) AS id, HEX(acl_s.entity_id) AS ctx
                FROM sensor s, acl_sensors acl_s
                WHERE s.id = acl_s.sensor_id AND acl_s.entity_id IN ($ctx_where)
                AND s.name = ?";
        }
        else
        {
            $query = 'SELECT HEX(s.id) AS id, HEX(acl_s.entity_id) AS ctx
                FROM sensor s, acl_sensors acl_s
                WHERE s.id = acl_s.sensor_id AND s.name = ?';
        }

        $params = array($name);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $sensor_ids[$rs->fields['id']] = $rs->fields['ctx'];

            $rs->MoveNext();
        }

        return $sensor_ids;
    }


    /**
    * This function returns the name from IP
    *
    * @param object  $conn   Database access object
    * @param string  $ip     Sensor IP
    *
    * @throws Exception  If a connection error occurred
    *
    * @return string
    */
    public static function get_name_by_ip($conn, $ip)
    {
        Ossim_db::check_connection($conn);

        $name = '';

        //Only my contexts
        $ctx_where = Session::get_ctx_where();

        if (!empty($ctx_where))
        {
            $query = "SELECT s.name FROM sensor s, acl_sensors acl_s
                WHERE s.id = acl_s.sensor_id AND acl_s.entity_id IN ($ctx_where)
                AND s.ip = inet6_aton(?)";
        }
        else
        {
            $query = 'SELECT name FROM sensor WHERE ip = inet6_aton(?)';
        }

        $params = array($ip);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!$rs->EOF)
        {
            $name = $rs->fields["name"];
        }

        return $name;
    }


    /**
    * This function returns the IP from sensor
    *
    * @param object  $conn   Database access object
    * @param string  $name   Sensor name
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_ip_by_name($conn, $name)
    {
        $name = Asset_host::create_valid_name($name);

        $sensor_ips = array();

        //Only my contexts
        $ctx_where = Session::get_ctx_where();

        if (!empty($ctx_where))
        {
            $query = "SELECT HEX(s.id) AS id, INET6_NTOA(s.ip) AS ip FROM sensor s, acl_sensors acl_s
                WHERE s.id = acl_s.sensor_id AND acl_s.entity_id IN ($ctx_where)
                AND s.name = ?";
        }
        else
        {
            $query  = 'SELECT HEX(id) AS id, INET6_NTOA(ip) AS ip FROM sensor WHERE name = ?';
        }

        $params = array($name);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!$rs->EOF)
        {
            $sensor_ips[$rs->fields['ip']] = $rs->fields['id'];
        }

        return $sensor_ips;
    }


    /**
    * This function returns the sensor ID from sensor
    *
    * @param object  $conn   Database access object
    * @param string  $ip     Sensor IP
    * @param string  $ctx    [Optional] Sensor context
    *
    * @throws Exception  If a connection error occurred
    *
    * @return string
    */
    public static function get_id_by_ip($conn, $ip, $ctx = '')
    {
        Ossim_db::check_connection($conn);

        $id = '';

        //Only my contexts
        $ctx_where = Session::get_ctx_where();

        $params = array($ip);


        $query = 'SELECT HEX(s.id) AS s_id FROM sensor s, acl_sensors acl_s
               WHERE s.id = acl_s.sensor_id AND s.ip = INET6_ATON(?)';

        if (!empty($ctx_where))
        {
            $query .= " AND acl_s.entity_id IN ($ctx_where)";
        }

        if ($ctx != '')
        {
            $query   .= ' AND acl_s.entity_id = UNHEX(?)';
            $params[] = $ctx;
        }

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!$rs->EOF)
        {
             $id = $rs->fields['s_id'];
        }

        return $id;
    }


    /**
    * This function returns the sensor IP from sensor
    *
    * @param object  $conn   Database access object
    * @param string  $id     Sensor ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return string
    */
    public static function get_ip_by_id($conn, $id)
    {
        Ossim_db::check_connection($conn);

        $ip = '';

        $query  = "SELECT INET6_NTOA(ip) AS sensor_ip FROM sensor WHERE id = UNHEX(?)";

        $params = array($id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!$rs->EOF)
        {
            $ip = $rs->fields['sensor_ip'];
        }

        return $ip;
    }



    /**
    * This function returns a SQL condition for filtering sensors
    *
    * @param string  $alias           [Optional] MySQL alias
    * @param string  $with_ctx        [Optional] Use context in filter
    * @param string  $with_ext_ctx    [Optional] Show external sensors
    *
    * @return string
    */
    public static function get_perms_where($alias = '', $with_ctx = TRUE, $with_ext_ctx = FALSE)
    {
        $query         = '';

        $ctx_where     = Session::get_ctx_where();
        $sensor_where  = Session::get_sensor_where();


        if ($with_ctx == TRUE && $ctx_where != '')
        {
            $query .= ' AND '.$alias.'sensor_id = sensor.id AND '.$alias.'entity_id IN ('.$ctx_where.')';
        }

        if ($sensor_where != '')
        {
            $query .= ' AND '.$alias.'sensor_id IN ('.$sensor_where.')';
        }

        if ($with_ext_ctx == FALSE)
        {
            //Database connection
            $db   = new ossim_db();
            $conn = $db->connect();

            $external_ctxs = array_keys(Session::get_external_ctxs($conn));

            $db->close();

            if (is_array($external_ctxs) && !empty($external_ctxs))
            {
                $no_ext_ctx_where = "UNHEX('".implode("'), UNHEX('", $external_ctxs)."')";

                if ($with_ctx == FALSE || $ctx_where == '')
                {
                    $query .= ' AND '.$alias.'sensor_id = sensor.id';
                }

                $query .= ' AND '.$alias.'entity_id NOT IN ('.$no_ext_ctx_where.')';
            }
        }

        return $query;
    }


    /**
    * This function returns a filtered sensor list (Only basic data)
    *
    * @param object   $conn     Database access object
    * @param array    $filters  [Optional] SQL statements (WHERE, LIMIT, ORDER BY ...)
    * @param boolean  $cache    [Optional] Use cached information
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_basic_list($conn, $filters = array(), $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        $sensors  = array();

        //Build SQL

        $perms_where = self::get_perms_where('acl_sensors.', TRUE);

        $q_where  = "WHERE sensor.name != '(null)'".$perms_where;


        if (!empty($filters['where']))
        {
            $q_where  .= ' AND '.$filters['where'];
        }

        if (empty($filters['order_by']))
        {
            $filters['order_by'] = "name";
        }
        $q_where  .= ' ORDER BY '.$filters['order_by'];

        if (!empty($filters['limit']))
        {
            $offset = isset($filters['offset']) && $filters['offset'] ? $filters['offset'] : 0;
            $q_where  .= "LIMIT $offset,{$filters['limit']}";
        }

        $conn->SetFetchMode(ADODB_FETCH_ASSOC);

        $q_from = 'sensor';

        if(preg_match('/acl_sensors/', $q_where) == TRUE)
        {
            $q_from .= ', acl_sensors';
        }


        $query = "SELECT HEX(sensor.id) AS s_id, INET6_NTOA(sensor.ip) AS sensor_ip, sensor.*
            FROM $q_from
            $q_where";

        $conn->SetFetchMode(ADODB_FETCH_ASSOC);

        $rs = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $sensors[$rs->fields['s_id']] = array(
                'id'        => $rs->fields['s_id'],
                'ip'        => $rs->fields['sensor_ip'],
                'name'      => $rs->fields['name'],
                'priority'  => $rs->fields['priority'],
                'port'      => $rs->fields['port'],
                'descr'     => Util::htmlentities($rs->fields['descr']),
                'tzone'     => $rs->fields['tzone']
            );

            $rs->MoveNext();
        }

        return $sensors;
    }


    /**
    * This function returns all unregistered sensors
    *
    * @param object  $conn   Database access object
    * @param boolean $cache  [Optional] Use cached information
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_unregistered($conn, $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        $sensors  = array();

        $perms_where = self::get_perms_where('acl_sensors.', TRUE);

        $q_from = 'sensor, sensor_properties';

        if(preg_match('/acl_sensors/', $perms_where) == TRUE)
        {
            $q_from .= ', acl_sensors';
        }

        $query = "SELECT HEX(sensor.id) AS s_id, INET6_NTOA(sensor.ip) AS sensor_ip, sensor.*, sensor_properties.*
            FROM $q_from
            WHERE sensor.id = sensor_properties.sensor_id AND sensor.name = '(null)'";

        $conn->SetFetchMode(ADODB_FETCH_ASSOC);

        $rs = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $sensors[$rs->fields['s_id']] = array(
                'id'         => $rs->fields['s_id'],
                'ip'         => $rs->fields['sensor_ip'],
                'name'       => $rs->fields['name'],
                'ctx'        => array(),
                'icon'       => NULL,
                'priority'   => $rs->fields['priority'],
                'port'       => $rs->fields['port'],
                'descr'      => Util::htmlentities($rs->fields['descr']),
                'tzone'      => $rs->fields['tzone'],
                'status'     => $rs->fields['connect'],
                'properties' => array(
                    'version'           => $rs->fields['version'],
                    'has_nagios'        => $rs->fields['has_nagios'],
                    'has_ntop'          => $rs->fields['has_ntop'],
                    'has_vuln_scanner'  => $rs->fields['has_vuln_scanner'],
                    'has_kismet'        => $rs->fields['has_kismet'],
                    'ids'               => $rs->fields['ids'],
                    'passive_inventory' => $rs->fields['passive_inventory'],
                    'netflows'          => $rs->fields['netflows']
                )
            );

            $rs->MoveNext();
        }

        return $sensors;
    }


    /**
    * This function returns all sensors
    *
    * @param object  $conn   Database access object
    * @param boolean $cache  [Optional] Use cached information
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_all($conn, $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        $sensors  = array();

        $query = "SELECT HEX(sensor.id) AS s_id, INET6_NTOA(sensor.ip) AS sensor_ip, sensor.*, sensor_properties.*
            FROM sensor, sensor_properties
            WHERE sensor.id = sensor_properties.sensor_id AND sensor.name != '(null)'";

        $conn->SetFetchMode(ADODB_FETCH_ASSOC);

        $rs = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $sensors[$rs->fields['s_id']] = array(
                'id'         => $rs->fields['s_id'],
                'ip'         => $rs->fields['sensor_ip'],
                'name'       => $rs->fields['name'],
                'ctx'        => self::get_ctx_by_id($conn, $rs->fields['s_id']),
                'icon'       => NULL,
                'priority'   => $rs->fields['priority'],
                'port'       => $rs->fields['port'],
                'descr'      => Util::htmlentities($rs->fields['descr']),
                'tzone'      => $rs->fields['tzone'],
                'status'     => $rs->fields['connect'],
                'properties' => array(
                    'version'           => $rs->fields['version'],
                    'has_nagios'        => $rs->fields['has_nagios'],
                    'has_ntop'          => $rs->fields['has_ntop'],
                    'has_vuln_scanner'  => $rs->fields['has_vuln_scanner'],
                    'has_kismet'        => $rs->fields['has_kismet'],
                    'ids'               => $rs->fields['ids'],
                    'passive_inventory' => $rs->fields['passive_inventory'],
                    'netflows'          => $rs->fields['netflows']
                )
            );

            $rs->MoveNext();
        }

        return $sensors;
    }


    /**
    * This function returns a filtered sensor list
    *
    * @param object   $conn              Database access object
    * @param array    $filters           [Optional] SQL statements (WHERE, LIMIT, ORDER BY ...)
    * @param boolean  $cache             [Optional] Use cached information
    * @param boolean  $with_ext_sensors  [Optional] Show external sensors
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public static function get_list($conn, $filters = array(), $cache = FALSE, $with_ext_sensors = FALSE)
    {
        Ossim_db::check_connection($conn);

        $sensors = array();

        $total = 0;

        //Build SQL

        $perms_where = self::get_perms_where('acl_sensors.', TRUE,  $with_ext_sensors);

        $q_select = 'sensor.*';
        $q_where  = $perms_where;


        if (!empty($filters['where']))
        {
            $q_where  .= ' AND '.$filters['where'];
        }
        if (empty($filters['order_by']))
        {
            $filters['order_by'] = "name";
        }
        $q_where  .= ' ORDER BY '.$filters['order_by'];

        if (!empty($filters['limit']))
        {
            $q_select  = 'SQL_CALC_FOUND_ROWS sensor.*';
            $q_where  .= ' LIMIT '.$filters['limit'];
        }

        $conn->SetFetchMode(ADODB_FETCH_ASSOC);


        $q_from = 'sensor, sensor_properties';

        if(preg_match('/acl_sensors/', $q_where) == TRUE)
        {
            $q_from .= ', acl_sensors';
        }


        $query = "SELECT $q_select, HEX(sensor.id) AS s_id, INET6_NTOA(sensor.ip) AS sensor_ip, sensor_properties.*
            FROM $q_from
            WHERE sensor.id = sensor_properties.sensor_id
            AND sensor.name != '(null)' $q_where";        
                           
        $conn->SetFetchMode(ADODB_FETCH_ASSOC);

        $rs = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!empty($filters['limit']))
        {
            $total = Ossim_db::get_found_rows($conn, $query, $cache);
        }
        else
        {
            $total = $rs->RecordCount();
        }

        while (!$rs->EOF)
        {
            $sensors[$rs->fields['s_id']] = array(
                'id'         => $rs->fields['s_id'],
                'ip'         => $rs->fields['sensor_ip'],
                'name'       => $rs->fields['name'],
                'ctx'        => self::get_ctx_by_id($conn, $rs->fields['s_id']),
                'icon'       => NULL,
                'priority'   => $rs->fields['priority'],
                'port'       => $rs->fields['port'],
                'descr'      => Util::htmlentities($rs->fields['descr']),
                'tzone'      => $rs->fields['tzone'],
                'status'     => $rs->fields['connect'],
                'properties' => array(
                    'version'           => $rs->fields['version'],
                    'has_nagios'        => $rs->fields['has_nagios'],
                    'has_ntop'          => $rs->fields['has_ntop'],
                    'has_vuln_scanner'  => $rs->fields['has_vuln_scanner'],
                    'has_kismet'        => $rs->fields['has_kismet'],
                    'ids'               => $rs->fields['ids'],
                    'passive_inventory' => $rs->fields['passive_inventory'],
                    'netflows'          => $rs->fields['netflows']                    
                )
            );


            $rs->MoveNext();
        }

        return array($sensors, $total);
    }
}

/* End of file av_sensor.inc */
/* Location: ../include/classes/av_sensor.inc */
