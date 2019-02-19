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

function colorize_risk($risk)
{

	if($risk > 0 && $risk <= 4)
	{
		$risk_color = '#8CC63F';
	}
	elseif($risk > 4 && $risk <= 7)
	{
		$risk_color = '#FFA500';
	}
	elseif($risk > 7 && $risk <= 10)
	{
		$risk_color = '#FF0000';
	}
	else
	{	
		$risk_color = '#000000';
	}
	
	return array($risk, $risk_color);

}

function get_alarm_life($end, $begin, $text = ''){
	$f0 = strtotime($begin);
	$f1 = strtotime($end);

	$diff = ($f0<$f1) ? ($f1 - $f0) : ($f0 - $f1);

	
	if($diff >= 86400) {
		$diff = round($diff/86400);
		$unit = ($diff == 1) ? _('day') : _('days');
	}
	elseif($diff >= 3600) {
		$diff = round($diff/3600);
		$unit = ($diff == 1) ? _('hour') : _('hours');
	}
	elseif($diff >= 60) {
		$diff = round($diff/60);
		$unit = ($diff == 1) ? _('min') : _('mins');
	}
	elseif($diff >= 0) {
		$unit = ($diff == 1) ? _('sec') : _('secs');
	}
	else
	{
		return '-';
	}
	
	return "$diff <span>$unit $text</span>";
	
	
}

function is_promiscous($src_count, $dst_count, $src_home = false, $dst_home = false)
{
	$pattern  = '';
	$scenario = '';

	//Pattern
	if ($src_count == 1 && $dst_count == 1)
	{
	   $pattern = "one-to-one";
	}
	elseif ($src_count > 1 && $dst_count == 1)
	{
       $pattern = "many-to-one";
	}
	elseif ($src_count == 1 && $dst_count > 1)
	{
       $pattern = "one-to-many";
	}
	else
	{
       $pattern = "many-to-many"; 
	}
	//Scenario
	if ($src_home && $dst_home)
	{
	   $scenario = "internal";
	}
	elseif (!$src_home && $dst_home)
	{
       $scenario = "external to internal";
	}
	elseif ($src_home && !$dst_home)
	{
       $scenario = "internal to external";
	}
	else
	{
       $scenario = "external to external"; 
	}

	return ($scenario . ' ' . $pattern);
}

?>

