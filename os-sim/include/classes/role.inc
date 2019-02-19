<?php
/**
* role.inc
*
* File role.inc is used to:
*   - Manage the info in server_role database table
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


class Role
{
    /**
     * Server ID
     * @var string
     */
    public $server_id;


    /**
     * Correlate
     * @var boolean
     */
    public $correlate;


    /**
     * Cross correlate
     * @var boolean
     */
    public $cross_correlate;


    /**
     * Store
     * @var boolean
     */
    public $store;


    /**
     * Qualify
     * @var boolean
     */
    public $qualify;


    /**
     * Resend alarms
     * @var boolean
     */
    public $resend_alarm;


    /**
     * Resend events
     * @var boolean
     */
    public $resend_event;


    /**
     * Reputation
     * @var boolean
     */
    public $reputation;


    /**
     * Sign
     * @var boolean
     */
    public $sign;


    /**
     * Sem
     * @var boolean
     */
    public $sem;


    /**
     * Sim
     * @var boolean
     */
    public $sim;


    /**
     * Alarm to syslog
     * @var boolean
     */
    public $alarms_to_syslog;


    /**
     * This function sets up the class object
     * 
     * @param string  $server_id
     * @param array   $properties
     */
    public function __construct($server_id, $properties)
    {
        $this->server_id        = $server_id;
        $this->correlate        = $properties['correlate'];
        $this->cross_correlate  = $properties['cross_correlate'];
        $this->store            = $properties['store'];
        $this->qualify          = $properties['qualify'];
        $this->resend_alarm     = $properties['resend_alarm'];
        $this->resend_event     = $properties['resend_event'];
        $this->sign             = $properties['sign'];
        $this->sem              = $properties['sem'];
        $this->sim              = $properties['sim'];
        $this->reputation       = $properties['reputation'];
        $this->alarms_to_syslog = $properties['alarms_to_syslog'];
    }


    /**
     * This function returns the server ID
     * 
     * @return string
     */
    public function get_server_id()
    {
        return $this->server_id;
    }


    /**
     * This function returns the correlate property
     * 
     * @return boolean
     */
    public function get_correlate()
    {
        return $this->correlate;
    }


    /**
     * This function returns the cross correlate property
     * 
     * @return boolean
     */
    public function get_cross_correlate()
    {
        return $this->cross_correlate;
    }


    /**
     * This function returns the store property
     * 
     * @return boolean
     */
    public function get_store()
    {
        return $this->store;
    }


    /**
     * This function returns the qualify property
     * 
     * @return boolean
     */
    public function get_qualify()
    {
        return $this->qualify;
    }


    /**
     * This function returns the resend alarm property
     * 
     * @return boolean
     */
    public function get_resend_alarm()
    {
        return $this->resend_alarm;
    }


    /**
     * This function returns the resend event property
     * 
     * @return boolean
     */
    public function get_resend_event()
    {
        return $this->resend_event;
    }


    /**
     * This function returns the sign property
     * 
     * @return boolean
     */
    public function get_sign()
    {
        return $this->sign;
    }


    /**
     * This function returns the sem property
     * 
     * @return boolean
     */
    public function get_sem()
    {
        return $this->sem;
    }


    /**
     * This function returns the sim property
     * 
     * @return boolean
     */
    public function get_sim()
    {
        return $this->sim;
    }


    /**
     * This function returns the reputation property
     * 
     * @return boolean
     */
    public function get_reputation()
    {
        return $this->reputation;
    }


    /**
     * This function returns the alarm to syslog property
     * 
     * @return boolean
     */
    public function get_alarms_to_syslog()
    {
        return $this->alarms_to_syslog;
    }
    

    /**
     * This function returns the list of role objects
     * 
     * @param object $conn
     * @param string $server_id
     * 
     * @return array
     */
    public static function get_list($conn, $server_id)
    {
        Ossim_db::check_connection($conn);

        $list  = array();
        $query = ossim_query("SELECT *, HEX(server_id) AS server_id FROM server_role 
                    WHERE server_id = UNHEX(?)");

        $rs = $conn->Execute($query, array($server_id));

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            while (!$rs->EOF)
            {
                $properties = array(
                    'correlate'        => $rs->fields["correlate"],
                    'cross_correlate'  => $rs->fields["cross_correlate"],
                    'store'            => $rs->fields["store"],
                    'qualify'          => $rs->fields["qualify"],
                    'resend_alarm'     => $rs->fields["resend_alarm"],
                    'resend_event'     => $rs->fields["resend_event"],
                    'sign'             => $rs->fields["sign"],
                    'sem'              => $rs->fields["sem"],
                    'sim'              => $rs->fields["sim"],
                    'reputation'       => $rs->fields["reputation"],
                    'alarms_to_syslog' => $rs->fields["alarms_to_syslog"]
                );

                $list[] = new self($rs->fields["server_id"], $properties);

                $rs->MoveNext();
            }
        }
        return $list;
    }
}

/* End of file role.inc */
/* Location: ../include/classes/role.inc */
