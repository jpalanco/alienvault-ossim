<?php
/**
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
*/


require_once 'av_init.php';

Session::logcheck("configuration-menu", "PolicyPorts");

$search     = GET('q');
$limit      = GET('limit');
$protocol   = GET('protocol');
$ctx        = GET('ctx');

ossim_valid($search    , OSS_NOECHARS, OSS_ALPHA, OSS_SCORE, OSS_PUNC, 'illegal:' . _("search"));
ossim_valid($limit     , OSS_DIGIT                                   , 'illegal:' . _("limit"));
ossim_valid($protocol  , OSS_LETTER                                  , 'illegal:' . _("protocol"));
ossim_valid($ctx       , OSS_HEX                                     , 'illegal:' . _("entity"));

if (ossim_error()) {
    die();
}

//create filter and order
$where = " AND service like '%" . $search . "%' and protocol_name = '" . $protocol . "' AND ctx = UNHEX('$ctx') ";
$order = "order by service limit " .$limit;

// connect to database
$db        = new ossim_db();
$conn      = $db->connect();

// search ports
$ports = Port::get_list($conn, $where, $order);

$db->close();

foreach($ports as $port)
    echo($port->get_service()."\n");

?>