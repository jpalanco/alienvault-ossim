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
        $data = str_replace(array('\'','"'), '', $data);
        $data = filter_var($data, FILTER_SANITIZE_STRING);
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

# Validations

## The start time cannot be set in the past
$form_valid = true;
$validation_errors = [];
#the date should be check always when the schedule type was different from immediately
if (isset ($schedule->parameters["biyear"]) && !empty($schedule->parameters["biyear"]) && $schedule->parameters["schedule_type"] != 'N') {
    //2020-08-18 04:15:47
    $tz = Util::get_timezone();
    $now = $schedule->current_time($tz,"Y-m-d H:i", 0);
    $form_date = $schedule->parameters["biyear"] . "-" . str_pad($schedule->parameters["bimonth"], 2, 0, STR_PAD_LEFT) . "-" . str_pad($schedule->parameters["biday"], 2, 0, STR_PAD_LEFT) . " " . str_pad($schedule->parameters["time_hour"], 2, 0, STR_PAD_LEFT) . ":" . str_pad($schedule->parameters["time_min"], 2, 0, STR_PAD_LEFT);
    if( $form_date < $now){
        $form_valid = false;
        $validation_errors[] ="The 'begin in' and 'time' fields ($form_date) cannot be in the past.  Please, select a new one in the future";
    }
}

## Allowed actions
$action_validation =  ossim_valid($schedule->parameters["action"], 'create_scan', 'save_scan', 'edit_sched', 'delete_scan', 'rerun_scan', OSS_NULLABLE, 'Illegal:'._('Action'));
if( !$action_validation ) {
    $validation_errors[] = ossim_get_error_clean();
}

$form_valid = $form_valid && $action_validation;

## Returned values for validation
if ( !$form_valid ) {
    if (POST('ajax_validation_all') == TRUE)
    {
        $data['status'] = 'error';
        $data['data']   = $validation_errors;
        echo json_encode($data);
        exit();
    }
    else{
        die(_('The following errors occurred.' . serialize($validation_errors)));
    }
}
else{
    if (POST('ajax_validation_all') == TRUE)
    {
        $data['status'] = 'OK';
        $data['data']   = '';
        echo json_encode($data);
        exit();
    }
}
# END validations

$scheduleContext = new ScheduleStrategyContext($schedule);
$scheduleContext->init();
$scheduleContext->execute();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo _('Vulnerabilities'); ?> </title>
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
	<script type="text/javascript" src="/ossim/js/vulnmeter.js.php"></script>
    <script type="text/javascript" src="/ossim/js/notification.js"></script>
    <script type="text/javascript" src="/ossim/js/ajax_validator.js"></script>
    <script type="text/javascript" src="/ossim/js/messages.php"></script>
	<?php require ("../host_report_menu.php") ?>



<?php
$scheduleContext->show();
