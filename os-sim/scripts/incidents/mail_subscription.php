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
set_include_path('/usr/share/ossim/include');

require_once 'av_init.php';

$conf      = $GLOBALS["CONF"];

$mdays     = $conf->get_conf("tickets_max_days");

$db   = new ossim_db();
$conn = $db->connect();
$user = 'admin';

// login the user
$session = new Session($user, '', '');
$session->login(TRUE);

$dbpass = $conn->GetOne('SELECT pass FROM users WHERE login = ?', array($user));
$client = new Alienvault_client($user);
$client->auth()->login($user,$dbpass);

if ($result = $conn->execute("SELECT * FROM incident_tmp_email"))
{
    while (!$result->EOF)
    {
        $incident_id = $result->fields["incident_id"];
        $ticket_id   = $result->fields["ticket_id"];
        $type        = $result->fields["type"];

        list($incident) = Incident::search($conn, array('incident_id' => $incident_id));

        if(!empty($incident)) {
            Incident_ticket::mail_notification($conn, $incident_id, $ticket_id, $type);
        }

        if (ossim_error())
        {
            echo ossim_error()."\n";
        }
        ossim_set_error(FALSE);

        $conn->Execute('DELETE FROM incident_tmp_email WHERE incident_id = ?', array($incident_id));

        $result->MoveNext();
    }
}

$db->close($conn);
?>
