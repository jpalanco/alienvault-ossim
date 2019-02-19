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
$send_mail = strtolower($conf->get_conf("tickets_send_mail"));

if ($send_mail == "no")
{
    exit();
}


$db   = new ossim_db();
$conn = $db->connect();

$result = $conn->execute("SET SESSION time_zone='+00:00'");
$result = $conn->execute("SELECT id, title, date, ref, type_id, priority, last_update, in_charge, submitter FROM incident inner join incident_tag on incident_tag.incident_id=incident.id WHERE DATEDIFF(now() , date) > $mdays AND STATUS = 'open'");

while (!$result->EOF)
{
    if (valid_hex32($result->fields["in_charge"]))
    {
        $in_charge = Acl::get_entity_name($conn, $result->fields["in_charge"]);
    }
    else
    {
        $in_charge = $result->fields["in_charge"];
    }


    $subject  = _('Ticket Open: ').$result->fields["title"];

    $body ='<html>
    <head>
        <title>'.$subject.'</title>
    </head>
    <body>'.
    '<table width="100%" cellspacing="0" cellpadding="0" style="border:0px;">'.
        '<tr><td width="75">'._('Id:').'</td><td>'.$result->fields["id"].'</td></tr>'.
        '<tr><td width="75">'._('Title:').'</td><td>'.$result->fields["title"].'</td></tr>'.
        '<tr><td width="75">'._('Date:').'</td><td>'.$result->fields["date"].'</td></tr>'.
        '<tr><td width="75">'._('Ref:').'</td><td>'.$result->fields["ref"].'</td></tr>'.
        '<tr><td width="75">'._('Type id:').'</td><td>'.$result->fields["type_id"].'</td></tr>'.
        '<tr><td width="75">'._('Priority:').'</td><td>'.$result->fields["priority"].'</td></tr>'.
        '<tr><td width="75">'._('Last update:').'</td><td>'.$result->fields["last_update"].'</td></tr>'.
        '<tr><td width="75">'._('In charge:').'</td><td>'.$in_charge.'</td></tr>'.
        '<tr><td width="75">'._('Submitter:').'</td><td>'.$result->fields["submitter"].'</td></tr>'.
    '</table>'.
    '</body>
    </html>';


    if (!valid_hex32($result->fields["in_charge"]))
    {
        $user_data = Session::get_list($conn, "WHERE login='".$result->fields["in_charge"]."'", "", TRUE);

        if (is_object($user_data[0]))
        {
            if ($user_data[0]->get_email() != '')
            {
                Util::send_email($conn, $user_data[0]->get_email(), $subject, $body);
            }
        }
    }
    else
    {
        // In_charge is a entity
        $entity_data = Acl::get_entity($conn,$result->fields["in_charge"], FALSE, FALSE);

        if($entity_data["admin_user"]!="")
        {
            // exists pro admin
            $pro_admin_data = Session::get_list($conn, "WHERE login='".$entity_data["admin_user"]."'", "", TRUE);

            if ($pro_admin_data[0]->get_email() != '')
            {
                Util::send_email($conn, $pro_admin_data[0]->get_email(), $subject, $body);
            }
        }
        else
        {
            // Doesn't exit pro admin
            $users_list = Acl::get_users_by_entity($conn, $result->fields["in_charge"]);

            foreach ($users_list as $user)
            {
                if ($user["email"] != '')
                {
                    Util::send_email($conn, $user['email'], $subject, $body);
                }

            }
        }
    }

    $result->MoveNext();
}

$db->close();
?>
