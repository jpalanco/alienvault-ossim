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
ini_set('max_execution_time', 300);
require_once 'av_init.php';
require_once 'config.php';
require_once 'functions.inc';
require_once 'dates.php';
require_once 'schedule.php';
require_once 'schedule_strategy_main.php';

Session::logcheck('environment-menu', 'EventsVulnerabilitiesScan');

$schedule = new Schedule();

array_walk($schedule->parameters , function(&$item, $key) {
	if (isset($_REQUEST[$key])) {
		$item = REQUEST($key);
	}
});
ossim_valid($schedule->parameters["action"], 'create_scan', 'save_scan', OSS_NULLABLE, 'Illegal:'._('Action'));

if (ossim_error())
{
    die(_('Invalid Action Parameter'));
}
$schedule->setModal(true);

$type = REQUEST("type");
if (!$schedule->parameters["targets"]) {
	$conn = $schedule->conn;
	// load selected hosts and nets
	if ($type == 'asset' || $type == 'network')
	{
		$params = array(session_id());
		if ($type == 'asset')
		{
			$host_perms_where = Asset_host::get_perms_where('h.', TRUE);
 			$sql = "SELECT hex(hi.host_id) as id, INET6_NTOA(hi.ip) as ip FROM user_component_filter uf, host h, host_ip hi
			WHERE uf.session_id=? AND h.id=hi.host_id AND uf.asset_id=hi.host_id AND uf.asset_type='asset' $host_perms_where";
	
		}
		else
		{
			$net_perms_where  = Asset_net::get_perms_where('n.', TRUE);
			$sql = "SELECT hex(n.id) as id, nc.cidr as ip FROM user_component_filter uf, net n, net_cidrs nc
			WHERE uf.session_id=? AND uf.asset_id=n.id AND n.id=nc.net_id AND uf.asset_type='network' $net_perms_where";
		}
		$rs = $conn->Execute($sql, $params);
		if (!$rs)
		{
			Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
		}
		while (!$rs->EOF)
		{
			$schedule->parameters["targets"][] = $rs->fields['id'] . '#' . $rs->fields['ip'];
			$rs->MoveNext();
		}
	}
	else if ($type == 'group')
	{
		// load assets groups
		$sql = "SELECT hex(uf.asset_id) as gid FROM user_component_filter uf
                    WHERE uf.session_id=? AND uf.asset_type='group'";
		$params = array(session_id());
		$rs = $conn->Execute($sql, $params);
		if (!$rs)
		{
			Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
		}
		while (!$rs->EOF)
		{
			$schedule->parameters["targets"][] = $rs->fields['gid'] . '#hostgroup';
			$rs->MoveNext();
		}
		// load group assets
		$sql = "SELECT hex(hi.host_id) as id, INET6_NTOA(hi.ip) as ip FROM user_component_filter uf, host h, host_ip hi, host_group_reference hgr WHERE h.id=hi.host_id AND uf.session_id=? AND uf.asset_id=hgr.host_group_id AND hgr.host_id=hi.host_id AND uf.asset_type='group' $host_perms_where";
		$params = array(session_id());
		$rs = $conn->Execute($sql, $params);
		if (!$rs)
		{
			Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
		}
	}
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
	<?php
    //CSS Files
    $_files = array(
        array('src' => 'av_common.css',  'def_path' => TRUE),
        array('src' => 'jquery-ui.css',  'def_path' => TRUE),

    );
    
    Util::print_include_files($_files, 'css');

    //JS Files
    $_files = array(
        array('src' => 'jquery.min.js',    'def_path' => TRUE),
        array('src' => 'jquery-ui.min.js', 'def_path' => TRUE),
        array('src' => 'utils.js',         'def_path' => TRUE),
        array('src' => 'notification.js',  'def_path' => TRUE),
        array('src' => 'combos.js',        'def_path' => TRUE),
        array('src' => 'vulnmeter.js',     'def_path' => TRUE),
    );
    
    Util::print_include_files($_files, 'js');
$scheduleContext->show();
