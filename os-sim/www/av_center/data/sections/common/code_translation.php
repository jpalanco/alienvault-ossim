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


$__m_updates = array(
    '0'      => _('System successfully updated.'),
    '300001' => _('Update requested with unknown parameters.'),
    '300002' => _('No /etc/ossim/ossim_setup.conf file was found.'),
    '300003' => _('No profiles in /etc/ossim/ossim_setup.conf were found.'),
    '300004' => _('Could not resynchronize package index files. Please try again in a few minutes.'),
    '300005' => _('apt-get update failed.'),
    '300006' => _('Error while removing software inventory.'),
    '300007' => _('The update process is unavailable for this machine. Are you trying to update a trial installation?'),
    '300008' => _('Error rewriting sources list while executing apt-get update.'),
    '300009' => _('Error while removing libio-socket-inet6-perl.'),
    '300010' => _('Error while installing alienvault-license.'),
    '300011' => _('Error while installing alienvault-professional.'),
    '300012' => _('Error stopping monit.'),
    '300013' => _('Error removing suricata and alienvault-dummy-sensor when trying to remove libhtp1.'),
    '300014' => _('Error reinstalling suricata and alienvault-dummy-sensor after libhtp1 was removed.'),
    '300015' => _('Error removing php5-suhosin.'),
    '300016' => _('Error setting linux-image to "On Hold" state.'),
    '300017' => _('Error installing libmysqlclient16.'),
    '300018' => _('Error: unknown libmysqlclient16 version.'),
    '300019' => _('Error setting libmysqlclient16 to "On Hold".'),
    '300020' => _('Error unsetting apache2 from "On Hold" state.'),
    '300021' => _('Error reinstalling daq.'),
    '300022' => _('Error looking for perconadb password.'),
    '300023' => _('Error while doing buildload preseed conf.'),
    '300024' => _('Error in debconf selections while reconfiguring dash.'),
    '300025' => _('Error installing dash.'),
    '300026' => _('Error installing mailbsdx.'),
    '300027' => _('Error while downloading packages prior to a dist-upgrade operation.'),
    '300028' => _('Error during a dist-upgrade operation.'),
    '300029' => _('Error while updating igb-pfring.'),
    '300030' => _('Error while uninstalling squid2.'),
    '300031' => _('Error while autoremoving unnecessary packages.'),
    '300032' => _('Error while purging files/packages not needed any more.'),
    '300033' => _('Update still not available for this platform'),
    '300034' => _('Error: repository check detected a problem'),
    '300040' => _('Error: Update script not found in USB device'),
    '300050' => _('Error: There is an update instance running. Aborting update.'),
    '300051' => _('Error: Invalid signature for upgrade file (AV3).'),
    '300052' => _('Error: Signature unavailable for upgrade file (AV3).'),
    '300053' => _('Error: Invalid key. Are you trying to use an AV3 key?'),
    '300054' => _('Error: Please enter a valid AV key.'),
    '300055' => _('Error: Invalid signature for upgrade file (AV4).'),
    '300056' => _('Error: Signature unavailable for upgrade file (AV4).'),
    '300057' => _('Error: Invalid signature for upgrade file (feed).'),
    '300058' => _('Error: Signature unavailable for upgrade file (feed).'),
    '300059' => _('Error: System running in HA Mode. Aborting update'),
    '300060' => _('Error: Cannot download upgrade file (AV5)'),
    '300061' => _('Error: Invalid signature for upgrade file (AV5)'),
    '300062' => _('Error: Signature unavailable for upgrade file (AV5)'),
    '300063' => _('Error: Cannot download update file (feed)'),
    '300064' => _('Error: Cannot install package from feed update'),
    '300070' => _('Not enough free disk space to install the latest updates.'),
    '300090' => _('An existing task running.'),
    '300091' => _('Something wrong happened while running the alienvault update.'),
    '300092' => _('Something wrong happened while retrieving the alienvault-update log file.'),
    '300093' => _('Something wrong happened while retrieving the alienvault-update return code.'),
    '300099' => _('An error occurred running alienvault-update.')
);
