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
        "Version" => "0.62",
        "URL" => "putty.exe",
        "Homepage" => "http://the.earth.li/~sgtatham/putty/latest/x86/putty.exe",
        "Description" => "PuTTY is an SSH and telnet client, developed originally by Simon Tatham for the Windows platform. PuTTY is open source software that is available with source code and is developed and supported by a group of volunteers."
    ) ,
    // array(
        // "Name" => "OSSIM Agent installer for Windows",
        // "Version" => "2.1",
        // "URL" => "ossim-install.exe",
        // "Homepage" => "http://www.ossim.net/",
        // "Description" => "OSSIM Agent intaller for windows hosts, server ip is already preconfigured. Run the installer and afterwards go to \ossim\ and run 'ossim.bat'."
    // ) ,
    //array(
    //    "Name" => "Python for Windows",
    //    "Version" => "2.5.1",
    //   "URL" => "python-2.5.1.msi",
    //    "Homepage" => "http://www.python.org/",
    //    "Description" => "Python is a remarkably powerful dynamic programming language that is used in a wide variety of application domains. Python is often compared to Tcl, Perl, Ruby, Scheme or Java."
    //) ,
    array(
        "Name" => "OCS for Windows",
        "Version" => "1.0.2",
        "URL" => "OCSNG_WIN32_AGENT_1.02_repack.zip",
        "Homepage" => "http://www.ocsinventory-ng.org/",
        "Description" => "Open Computer and Software Inventory Next Generation is an application designed to help a network or system administrator keep track of the computers configuration and software that are installed on the network."
    ) ,
    array(
        "Name" => "OCS for Linux",
        "Version" => "1.0.2",
        "URL" => "OCSNG_LINUX_AGENT_1.02_with_require.tar.gz",
        "Homepage" => "http://www.ocsinventory-ng.org/",
        "Description" => "Open Computer and Software Inventory Next Generation is an application designed to help a network or system administrator keep track of the computers configuration and software that are installed on the network."
    ) ,
    //array(
    //    "Name" => "OCS for other systems",
    //    "Version" => "n/a",
    //    "URL" => "http://www.ocsinventory-ng.org/index.php?page=Unofficial_agents",
    //    "Homepage" => "http://www.ocsinventory-ng.org/index.php?page=Unofficial_agents",
    //    "Description" => "Open Computer and Software Inventory Next Generation is an application designed to help a network or system administrator keep track of the computers configuration and software that are installed on the network. Third party contributed agents for MacosX, BSD, Solaris, AIX, HP-UX"
    //) ,
    //array(
    //    "Name" => "OSSEC Agent for Windows",
    //    "Version" => "2.7",
    //    "URL" => "ossec-win32-agent_avmod_20130218085020.exe",
    //    "Homepage" => "http://www.ossec.net",
    //    "Description" => "OSSEC is an Open Source Host-based Intrusion Detection System. It performs log analysis, integrity checking, Windows registry monitoring, rootkit detection, real-time alerting and active response."
    //) ,
    array(
        "Name" => "Snare for Windows",
        "Version" => "4.0.1.2",
        "URL" => "SnareForWindows-4.0.1.2-MultiArch.exe",
        "Homepage" => "http://www.intersectalliance.com/projects/SnareWindows/",
        "Description" => "Snare for Windows is a Windows NT, Windows 2000, Windows XP, Vista and Windows 2003 compatible service that interacts with the underlying Windows Eventlog subsystem to facilitate remote, real-time transfer of event log information."
    ) ,
    array(
        "Name" => "Snare Config file (audit service takeover)",
        "Version" => "0.1",
        "URL" => "snare_takeover.reg",
        "Homepage" => "http://www.intersectalliance.com/projects/SnareWindows/",
        "Description" => "Import this .reg file into every host running snare. It's configure to log against this host's IP, you may edit it to change it. This file takes over control of the windows audit service, allowing for easy policy specifications via Snare's web interface. This is the recommended way of running it."
    ) ,
    array(
        "Name" => "Snare Config file (no audit service takeover)",
        "Version" => "0.1",
        "URL" => "snare_no_takeover.reg",
        "Homepage" => "http://www.intersectalliance.com/projects/SnareWindows/",
        "Description" => "Import this .reg file into every host running snare. It's configure to log against this host's IP, you may edit it to change it. This file leave's the hosts audit service settings untouched."
    ) ,
    array(
        "Name" => "FW1 Loggrabber for Linux",
        "Version" => "1.11.1",
        "URL" => "fw1-loggrabber-1.11.1-linux.tar.gz",
        "Homepage" => "http://sourceforge.net/projects/fw1-loggrabber/",
        "Description" => "FW1-Loggrabber is a command-line tool to grab logfiles from Checkpoint FW-1 remotely using Checkpoints LEA (Log Export Api), which is one part of Checkpoints OPSEC API."
    ) ,
    array(
        "Name" => "FW1 Loggrabber for Windows",
        "Version" => "1.11.1",
        "URL" => "fw1-loggrabber-1.11.1-win32.exe",
        "Homepage" => "http://sourceforge.net/projects/fw1-loggrabber/",
        "Description" => "FW1-Loggrabber is a command-line tool to grab logfiles from Checkpoint FW-1 remotely using Checkpoints LEA (Log Export Api), which is one part of Checkpoints OPSEC API."
    )
    // ,
    //array(
    //    "Name" => "OpenVPN Windows",
    //    "Version" => "2.0.9/1.0.3",
    //    "URL" => "openvpn-2.0.9-gui-1.0.3-install.exe",
    //    "Homepage" => "http://www.openvpn.se/",
    //    "Description" => "OpenVPN is a full-featured SSL VPN solution which can accomodate a wide range of configurations, including remote access, site-to-site VPNs, WiFi security, and enterprise-scale remote access solutions with load balancing, failover, and fine-grained access-controls."
    //) ,
    //array(
    //    "Name" => "Osiris Windows",
    //    "Version" => "4.2.3",
    //    "URL" => "osiris-4.2.3-win32.exe",
    //    "Homepage" => "http://osiris.shmoo.com",
    //    "Description" => "Osiris is a Host Integrity Monitoring System that periodically monitors one or more hosts for change. It maintains detailed logs of changes to the file system, user and group lists, resident kernel modules, and more."
    //)
);

?>
