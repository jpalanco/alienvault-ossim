<?php
/**
* Downloads.inc
*
* File Downloads.inc is used to:
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
* @package    ossim-framework\Downloads
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/



// This file describes sample downloads available to the user.
// This could be useful for snare, osiris, openvpn and other clients for windows hosts, for example
require_once 'ossim_conf.inc';
$config = $GLOBALS["CONF"];
$use_resolv = $config->get_conf("use_resolv");

$downloads = array(
    array(
        "Name" => "Putty (ssh client) for Windows",
        "Version" => "0.64",
        "URL" => "http://the.earth.li/~sgtatham/putty/latest/x86/putty.exe",
        "Homepage" => "http://the.earth.li/~sgtatham/putty/latest/x86/putty.exe",
        "Description" => "PuTTY is an SSH and telnet client, developed originally by Simon Tatham for the Windows platform. PuTTY is open source software that is available with source code and is developed and supported by a group of volunteers."
    ) ,
    array(
        "Name" => "Snare for Windows",
        "Version" => "4.0.1.2",
        "URL" => "SnareForWindows-4.0.1.2-MultiArch.exe",
        "Homepage" => "http://www.snarealliance.com/snare-open-source-agent-downloads",
        "Description" => "Snare for Windows is a Windows NT, Windows 2000, Windows XP, Vista and Windows 2003 compatible service that interacts with the underlying Windows Eventlog subsystem to facilitate remote, real-time transfer of event log information."
    ) ,
    array(
        "Name" => "Snare Config file (audit service takeover)",
        "Version" => "0.1",
        "URL" => "snare_takeover.reg",
        "Homepage" => "http://www.snarealliance.com/snare-open-source-agent-downloads",
        "Description" => "Import this .reg file into every host running snare. It's configure to log against this host's IP, you may edit it to change it. This file takes over control of the windows audit service, allowing for easy policy specifications via Snare's web interface. This is the recommended way of running it."
    ) ,
    array(
        "Name" => "Snare Config file (no audit service takeover)",
        "Version" => "0.1",
        "URL" => "snare_no_takeover.reg",
        "Homepage" => "http://www.snarealliance.com/snare-open-source-agent-downloads",
        "Description" => "Import this .reg file into every host running snare. It's configure to log against this host's IP, you may edit it to change it. This file leave's the hosts audit service settings untouched."
    )
);

?>
