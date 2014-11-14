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

Session::logcheck("environment-menu", "EventsVulnerabilities");

$tasks = GET("tasks");

if( !preg_match("/^[\d\#]+$/", $tasks) ) {
	die();
}

if (Vulnerabilities::scanner_type()=="omp") {
	$tresult = array();
	
    $omp = new Omp();
	$ids = explode("#", $tasks);
	
	foreach ($ids as $id) {
        $sparkline_count = 0;

        $details   = $omp->get_task_detail_by_id("Running|Paused|Pause Requested|Requested", $id, true);

        if(preg_match("/(\d+)\|(\d+)\|(\d+)\|(\d+)\|(\d+)/", $details["total"], $found)) {
            $sparkline_count = $found[1] + $found[2] + $found[3] + $found[4] + $found[5];
        }
        $tresult[] = $id."|".$sparkline_count."|".str_replace("|", ";", $details["detail"]);
	}
    echo implode("-", $tresult);
}

?>
