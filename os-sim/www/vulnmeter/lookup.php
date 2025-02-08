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


//
// $Id: lookup.php,v 1.2 2009/12/11 18:01:28 jmalbarracin Exp $
//

/***********************************************************/
/*                    Inprotect                            */
/* --------------------------------------------------------*/
/* Copyright (C) 2006 Inprotect                            */
/*                                                         */
/* This program is free software; you can redistribute it  */
/* and/or modify it under the terms of version 2 of the    */
/* GNU General Public License as published by the Free     */
/* Software Foundation.                                    */
/* This program is distributed in the hope that it will be */
/* useful, but WITHOUT ANY WARRANTY; without even the      */
/* implied warranty of MERCHANTABILITY or FITNESS FOR A    */
/* PARTICULAR PURPOSE. See the GNU General Public License  */
/* for more details.                                       */
/*                                                         */
/* You should have received a copy of the GNU General      */
/* Public License along with this program; if not, write   */
/* to the Free Software Foundation, Inc., 59 Temple Place, */
/* Suite 330, Boston, MA 02111-1307 USA                    */
/*                                                         */
/* Contact Information:                                    */
/* inprotect-devel@lists.sourceforge.net                   */
/* http://inprotect.sourceforge.net                        */
/***********************************************************/
/* See the README.txt and/or help files for more           */
/* information on how to use & config.                     */
/* See the LICENSE.txt file for more information on the    */
/* License this software is distributed under.             */
/*                                                         */
/* This program is intended for use in an authorized       */
/* manner only, and the author can not be held liable for  */
/* anything done with this program, code, or items         */
/* discovered with this program's use.                     */
/***********************************************************/

require_once 'av_init.php';
require_once 'config.php';
require_once 'functions.inc';
require_once 'ossim_sql.inc';

Session::logcheck("environment-menu", "EventsVulnerabilities");

function script_details($dbconn, $id) {

    $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

    $result = $dbconn->Execute("SELECT t1.id, t1.name, t2.name, t3.name, t1.summary, t1.created, t1.modified, 
	t1.cve_id, t1.bugtraq_id FROM vuln_nessus_plugins t1
	INNER JOIN vuln_nessus_family t2 on t1.family=t2.id
	INNER JOIN vuln_nessus_category t3 on t1.category=t3.id
	WHERE t1.id='$id'");

    list($pid, $pname, $pfamily, $pcategory, $psummary, $pcreated, $pmodified, $pcve_id, $pbugtraq_id)= $result->fields;

    echo "
    <div style='text-align: center; margin:auto; font-weight: bold;'>Plugin details</div>
    <span style='font-weight: bold;'>ID:</span> $pid<br/>
    <span style='font-weight: bold;'>Name:</span> $pname<br/>
    <span style='font-weight: bold;'>Family:</span> $pfamily<br/>
    <span style='font-weight: bold;'>Category:</span> $pcategory<br/>
    <span style='font-weight: bold;'>Summary:</span> $psummary<br/>
    <span style='font-weight: bold;'>Created:</span> $pcreated<br/>
    <span style='font-weight: bold;'>Modified:</span> $pmodified<br/>
    <span style='font-weight: bold;'>CVE IDs: </span>";
    $cves = preg_split ("/[\s,]+/", $pcve_id);
    foreach($cves as $cve_id){
        $cve_link = Vulnerabilities::get_cve_link($cve_id);

        if ($cve_link){
            echo "<a href=\"".$cve_link."\" target=\"_blank\">$cve_id</a> ";
        }
    }

    $Bugtraqs = preg_split ("/[\s,]+/", $pbugtraq_id);
    echo"<br/><span style='font-weight: bold;'>Bugtraq IDs: </span>";
    foreach($Bugtraqs as $Bugtraq){
        echo "<a href=\"http://www.securityfocus.com/bid/$Bugtraq\">$Bugtraq</a>  ";
    }

    echo ' <br/><br/>';
}

$db     = new ossim_db();
$dbconn = $db->connect();

$id = Util::htmlentities(escape_sql(trim($_GET['id']), $dbconn));

script_details($dbconn, $id);

$db->close();

