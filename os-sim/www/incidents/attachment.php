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

Session::logcheck("analysis-menu", "IncidentsIncidents");


$id = intval(GET('id'));
ossim_valid($id, OSS_NULLABLE, OSS_ALPHA, OSS_SCORE, 'illegal:' . _("id"));

if ( ossim_error() ) 
{
    echo ossim_error();
	exit();
}

if ( !empty($id) ) 
{
    $db   = new ossim_db();
    $conn = $db->connect();
    
    $result    = $conn->Execute("SELECT incident_id FROM incident_file WHERE id=$id");
    $ticket_id = $result->fields["incident_id"];
    
    $incident_list = Incident::search($conn, array('incident_id' => $ticket_id));

    if ( count($incident_list) != 1 ) 
	{
        echo ossim_error(_("Invalid ticket ID or insufficient permissions"));
		exit();
	}
    
    if ( $files = Incident_file::get_list($conn, "WHERE id = $id") ) 
	{
        $type  = $files[0]->get_type();
        $fname = $files[0]->get_name();
        header("Content-type: $type");
        header('Content-Disposition: attachment; filename="' . $fname . '"');
        print $files[0]->get_content();
    }
	
    $db->close($conn);
} 
else
{
    echo ossim_error(_("Invalid Incident ID"));
	exit();
}
?>