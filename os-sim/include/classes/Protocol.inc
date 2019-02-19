<?php
/**
* Protocol.inc
*
* File Protocol.inc is used to:
*   - To manage protocols
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
* @copyright  2007-2014 AlienVault
* @link       https://www.alienvault.com/
*/


/**
 * Protocol Class
 *
 * Class for managing protocols
 *
 * @package    ossim-framework\Various
 * @autor      AlienVault INC
 * @copyright  2007-2014 AlienVault
 * @link       https://www.alienvault.com/ AlienVault web page
 */

set_time_limit(0);

class Protocol
{
    /**
    * This function returns the protocol list from /etc/protocols
    *
    * @param string  $filter  [Optional] Filter by protocol name
    *
    * @return array
    */
    public static function get_list($filter = '')
    {
        $etc_protocols = file("/etc/protocols", FILE_SKIP_EMPTY_LINES);

        $protocols = array();

        foreach($etc_protocols as $p_data)
        {
            //Skip comments and empty lines
            $p_data = trim($p_data);

            if (empty($p_data) || 0 === strpos($p_data, '#'))
            {
                continue;
            }

            $p_data = explode('#', $p_data);
            $desc   = ucfirst(trim($p_data[1]));

            $p_data = explode(' ', preg_replace('/\s+/', ' ', trim($p_data[0])));

            if (FALSE !== getprotobynumber($p_data[1]))
            {
                $cnd_1 = (empty($filter));
                $cnd_2 = (!empty($filter) && preg_match('/'.preg_quote($filter, '/').'/i', $p_data[0]));

                if ($cnd_1 || $cnd_2)
                {
                    $protocols[$p_data[1]] = array(
                        'id'    => $p_data[1],
                        'name'  => $p_data[0],
                        'alias' => $p_data[2],
                        'desc'  => $desc
                    );
                }
            }
        }

        return $protocols;
    }


    /**
    * This function gets protocol name associated with protocol number
    *
    * @param integer  $number     Protocol number
    * @param boolean  $uppercase  Transform protocol name to uppercase
    *
    * @return string
    */
    public static function get_protocol_by_number($number, $uppercase = FALSE)
    {
        $name = getprotobynumber(intval($number));
        $name = (FALSE !== $name && TRUE === $uppercase) ? strtoupper($name) : $name;

        return $name;
    }


    /**
    * This function gets protocol number associated with the protocol name
    *
    * @param string  $name  Protocol name
    *
    * @return string
    */
    public static function get_protocol_by_name($name)
    {
        return getprotobyname(trim($name));
    }
}

/* End of file protocol.inc */
/* Location: ../include/classes/protocol.inc */
