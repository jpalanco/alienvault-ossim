<?php
/**
*
* License:
*
* Copyright (c) 2003-2006 ossim.net
* Copyright (c) 2007-2015 AlienVault
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
// $Id: sched.php,v 1.17 2010/04/21 15:22:39 josedejoses Exp $
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
/* http://inprotect.sourceforge.net/                       */
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

set_time_limit(600);
require_once 'av_init.php';
require_once 'config.php';
require_once 'functions.inc';
require_once 'dates.php';
require_once 'schedule.php';
require_once 'schedule_strategy.php';


Session::logcheck('environment-menu', 'EventsVulnerabilitiesScan');
$stripReflectedXSSRecursive = function($data) use (& $stripReflectedXSSRecursive) {
    if (is_array($data)) {
        foreach ($data as $key => $item) {
            $data[$key] = $stripReflectedXSSRecursive($item);
        }
    } else {
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    return $data;
};

$schedule = new Schedule();
array_walk($schedule->parameters , function(&$item, $key) use ($stripReflectedXSSRecursive) {
	if (isset($_REQUEST[$key])) {
		$item = $stripReflectedXSSRecursive(REQUEST($key));
	}
});
ossim_valid($schedule->parameters["action"], 'create_scan', 'save_scan', 'edit_sched', 'delete_scan', 'rerun_scan', OSS_NULLABLE, 'Illegal:'._('Action'));
if (ossim_error()) {
    die(_('Invalid Action Parameter'));
}


$scheduleContext = new ScheduleStrategyContext($schedule);
$scheduleContext->init();
$scheduleContext->execute();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext('Vulnmeter'); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<link rel="stylesheet" type="text/css" href="/ossim/style/jquery.autocomplete.css"/>
	<link rel="stylesheet" type="text/css" href="/ossim/style/tree.css" />
	<link rel="stylesheet" type="text/css" href="/ossim/style/tipTip.css" />
	<script type="text/javascript" src="/ossim/js/jquery.min.js"></script>
	<script type="text/javascript" src="/ossim/js/jquery-ui.min.js"></script>
    <script type="text/javascript" src="/ossim/js/jquery.autocomplete.pack.js"></script>
	<script type="text/javascript" src="/ossim/js/jquery.cookie.js"></script>
	<script type="text/javascript" src="/ossim/js/jquery.dynatree.js"></script>
	<script type="text/javascript" src="/ossim/js/jquery.tipTip.js"></script>
	<script type="text/javascript" src="/ossim/js/utils.js"></script>
    <script type="text/javascript" src="/ossim/js/combos.js"></script>
	<script type="text/javascript" src="/ossim/js/vulnmeter.js"></script>
    <script type="text/javascript" src="/ossim/js/notification.js"></script>
	<?php require ("../host_report_menu.php") ?>
	


<?php 
$scheduleContext->show();
