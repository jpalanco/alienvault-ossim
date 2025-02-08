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
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

require_once 'av_init.php';

Session::logcheck("environment-menu", "EventsVulnerabilities");

$type   = trim(GET('type'));
$search = trim(GET('q'));
$max    = intval(GET('limit'));
$max    = ($max) ? $max : 50;


ossim_valid($type,   'cve|family|category|plugin',                                   'illegal:' . _("Type"));
ossim_valid($search, OSS_NULLABLE, OSS_NOECHARS, OSS_ALPHA, OSS_SCORE, OSS_PUNC_EXT, 'illegal:' . _("Search"));

if (ossim_error())
{
    die();
}

$db   = new Ossim_db(TRUE);
$conn = $db->connect();

$data = array(
    'json' => array(),
    'txt' => ''
);

if ($type == 'cve')
{
    $data = Autocomplete::autocomplete_cves($conn, $search, $max);
}
else if ($type == 'family')
{
    $data = Autocomplete::autocomplete_gvm_families($conn, $search, $max);
}
else if ($type == 'category')
{
    $data = Autocomplete::autocomplete_gvm_categories($conn, $search, $max);
}
else if ($type == 'plugin')
{
    $data = Autocomplete::autocomplete_gvm_plugins($conn, $search, $max);
}


if (!empty($data['txt']))
{
    echo $data['txt'];
}

$db->close();
