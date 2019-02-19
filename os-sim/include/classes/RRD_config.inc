<?php
/**
* RRD_config.inc
*
* File RRD_config.inc is used to:
*   - Task 1 (TO DO)
*   - Task 2 (TO DO)
*   - Task 3 (TO DO)
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
* @package    ossim-framework\Metrics
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


class RRD_config
{
    public $profile;


    public $rrd_attrib;


    public $threshold;


    public $priority;


    public $alpha;


    public $beta;


    public $persistence;


    public $enable;


    public $description;


    public function __construct($profile, $rrd_attrib, $threshold, $priority, $alpha, $beta, $persistence, $enable, $description)
    {
        $this->profile     = $profile;
        $this->rrd_attrib  = $rrd_attrib;
        $this->threshold   = $threshold;
        $this->priority    = $priority;
        $this->alpha       = $alpha;
        $this->beta        = $beta;
        $this->persistence = $persistence;
        $this->enable      = $enable;
        $this->description = $description;
    }


    public function get_profile()
    {
        return $this->profile;
    }


    public function get_rrd_attrib()
    {
        return $this->rrd_attrib;
    }


    public function get_threshold()
    {
        return $this->threshold;
    }


    public function get_priority()
    {
        return $this->priority;
    }


    public function get_alpha()
    {
        return $this->alpha;
    }


    public function get_beta()
    {
        return $this->beta;
    }


    public function get_persistence()
    {
        return $this->persistence;
    }


    public function get_enable()
    {
        return $this->enable;
    }


    public function get_description()
    {
        return $this->description;
    }


    public static function get_list($conn, $args = "")
    {
        $list  = array();
        $query = ossim_query("SELECT * FROM rrd_config $args");

        $rs = $conn->Execute($query);
        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            while (!$rs->EOF)
            {
                $list[] = new self($rs->fields["profile"], $rs->fields["rrd_attrib"], $rs->fields["threshold"], $rs->fields["priority"], $rs->fields["alpha"], $rs->fields["beta"],
                    $rs->fields["persistence"], $rs->fields["enable"], $rs->fields["description"]);

                $rs->MoveNext();
            }
        }

        return $list;
    }


    public static function get_profile_list($conn)
    {
        $query = "SELECT DISTINCT profile FROM rrd_config";
        $rs  = $conn->Execute($query);

        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            $list = array();

            while (!$rs->EOF)
            {
                $list[] = $rs->fields["profile"];

                $rs->MoveNext();
            }
        }

        return $list;
    }


    public static function insert($conn, $profile, $rrd_attrib, $threshold, $priority, $alpha, $beta, $persistence, $enable, $description = '')
    {
        $profile     = Util::htmlentities($profile, ENT_COMPAT, "UTF-8");
        $rrd_attrib  = Util::htmlentities($rrd_attrib, ENT_COMPAT, "UTF-8");
        $description = Util::htmlentities($description, ENT_COMPAT, "UTF-8");
        $enable      = Util::htmlentities($enable, ENT_COMPAT, "UTF-8");
        $threshold   = intval($threshold);
        $priority    = intval($priority);
        $alpha       = intval($alpha);
        $beta        = intval($beta);
        $persistence = intval($persistence);

        $query = "INSERT INTO rrd_config (profile, rrd_attrib, threshold, priority, alpha, beta, persistence, enable, description)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $params = array(
            $profile,
            $rrd_attrib,
            $threshold,
            $priority,
            $alpha,
            $beta,
            $persistence,
            $enable,
            $description
        );

        if ($conn->Execute($query, $params) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        Log_action::log(9, NULL);
    }


    public static function update($conn, $profile, $rrd_attrib, $threshold, $priority, $alpha, $beta, $persistence, $enable)
    {
        $query = "UPDATE rrd_config SET
                      threshold     = ?,
                      priority      = ?,
                      alpha         = ?,
                      beta          = ?,
                      persistence   = ?,
                      enable        = ?
                  WHERE profile = ? AND rrd_attrib = ?";

        $params = array(
            $threshold,
            $priority,
            $alpha,
            $beta,
            $persistence,
            $enable,
            $profile,
            $rrd_attrib
        );

        if ($conn->Execute($query, $params) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }


    public static function delete($conn, $profile)
    {
        $query  = "DELETE FROM rrd_config WHERE profile = ?";
        $params = array($profile);

        if ($conn->Execute($query, $params) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }
}

/* End of file rrd_config.inc */
/* Location: ../include/classes/rrd_config.inc */
