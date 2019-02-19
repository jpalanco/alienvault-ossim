<?php
/**
* nagios_group.inc
*
* File nagios_group.inc is used to:
*   - To manage groups with nagios enabled
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
* @package    ossim-framework\Availability
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/

class Nagios_group
{
    public $group_id;


    public $hosts;


    public function __construct($host_group_id, $hosts)
    {
        $this->group_id = $host_group_id;
        $this->hosts    = $hosts;
    }


    public function toAdd()
    {
        return 'nagios action="add" type="hostgroup"' . " id=\"" . $this->group_id . "\" list=\"" . implode($this->hosts, ",") . "\"";
    }


    public function toDel()
    {
        return 'nagios action="del" type="hostgroup"' . " id=\"" . $this->group_id . "\" ";
    }
}

/* End of file nagios_group.inc */
/* Location: ../classes/nagios_group.inc */
