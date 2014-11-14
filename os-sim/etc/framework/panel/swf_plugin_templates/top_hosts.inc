<?php
/*****************************************************************************
*
*    License:
*
*   Copyright (c) 2003-2006 ossim.net
*   Copyright (c) 2007-2009 AlienVault
*   All rights reserved.
*
*   This package is free software; you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation; version 2 dated June, 1991.
*   You may not use, modify or distribute this program under any other version
*   of the GNU General Public License.
*
*   This package is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*   along with this package; if not, write to the Free Software
*   Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
*   MA  02110-1301  USA
*
*
* On Debian GNU/Linux systems, the complete text of the GNU General
* Public License can be found in `/usr/share/common-licenses/GPL-2'.
*
* Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
****************************************************************************/
/**
* Class and Function List:
* Function list:
* Classes list:
*/
/** Top C&A Hosts template **
 *
 * Strings: Name, Revision, URL, Homepage, Description.
 * Array: Parameters.
 *
 *  Parameters items:
 *     Strings: Name, Value, Description.
 * The Parameters width and height should be in all the plugins (but there's no need to fill them).
 *
 *
 */
$item = array(
    "Name" => "Top C&A hosts",
    "Revision" => "1",
    "Category" => "Risk",
    "URL" => "top_hosts.php",
    "Homepage" => "http://www.ossim.com/",
    "Description" => " Displays the top N hosts ordered by Compromise and Attack. The refresh interval can be customized.",
    "Parameters" => array(
        array(
            "Name" => "refresh",
            "Value" => "5",
            "Description" => "Seconds to wait before refreshing the graph"
        ) ,
        array(
            "Name" => "numhosts",
            "Value" => "10",
            "Description" => "Number of hosts to be displayed"
        ) ,
        array(
            "Name" => "width",
            "Value" => "470",
            "Description" => "Window width"
        ) ,
        array(
            "Name" => "height",
            "Value" => "350",
            "Description" => "Window height"
        )
    )
);
?>
