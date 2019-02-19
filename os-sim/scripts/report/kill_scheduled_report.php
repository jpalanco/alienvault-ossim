#!/usr/bin/php
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

ob_implicit_flush();

$debug = false;

$id    = $argv[1];  // scheduled report id
$exit  = false;

$params  = array("AV Report Scheduler \[$id\]");
$fsearch = Util::execute_command("sudo ps ax | grep ? | grep -v grep", $params, 'array');

if( count($fsearch) == 0) {
    exit("The report does not exist.\n");
}

// to AV Report Scheduler [id_report] process

while( count($fsearch) > 0 && !$exit) {

    $pids_to_kill = array();

    foreach ($fsearch as $result) {
        if($debug) {
            echo $result."\n";
        }
        if (preg_match("/^\s*(\d+)\s.*/", $result, $match)) // get process pid
        {
            $pids_to_kill[] = $match[1];
        }
    }
    $fsearch = array();
    
    if(count($pids_to_kill)>0) {
    
        $all_pids = implode(" ", $pids_to_kill);

        if($debug) {
            echo "sudo kill -9 $all_pids\n";
        }
        
        Util::execute_command("sudo kill -9 ?", array($all_pids));
        sleep(2);
        $fsearch = Util::execute_command("sudo ps ax | grep ? | grep -v grep", $params, 'array');
    }
    else {
        $exit = true;
    }
}

$exit  = false;
$fsearch = Util::execute_command("sudo ps ax |grep 'fetch_remote'| grep -v grep", FALSE, 'array');

while( count($fsearch) > 0 && !$exit) {

    $pids_to_kill = array();

    foreach ($fsearch as $result) {
        if($debug) {
            echo $result."\n";
        }
        if (preg_match("/^\s*(\d+)\s.*/", $result, $match)) // get process pid
        {
            $pids_to_kill[] = $match[1];
        }
    }
    $fsearch = array();
    
    if(count($pids_to_kill)>0) {
    
        $all_pids = implode(" ", $pids_to_kill);

        if($debug) {
            echo "sudo kill -9 $all_pids\n";
        }
        
        Util::execute_command("sudo kill -9 ?", array($all_pids));
        sleep(2);
        $fsearch = Util::execute_command("sudo ps ax |grep 'fetch_remote'| grep -v grep", FALSE, 'array');
    }
    else {
        $exit = true;
    }
}

// to fetch_all process

$exit  = false;
$fsearch = Util::execute_command("sudo ps ax |grep 'fetch_all'| grep -v grep", FALSE, 'array');

while( count($fsearch) > 0 && !$exit) {

    $pids_to_kill = array();

    foreach ($fsearch as $result) {
        if($debug) {
            echo $result."\n";
        }
        if (preg_match("/^\s*(\d+)\s.*/", $result, $match)) // get process pid
        {
            $pids_to_kill[] = $match[1];
        }
    }
    $fsearch = array();
    
    if(count($pids_to_kill)>0) {
    
        $all_pids = implode(" ", $pids_to_kill);

        if($debug) {
            echo "sudo kill -9 $all_pids\n";
        }
        
        Util::execute_command("sudo kill -9 ?", array($all_pids));
        sleep(2);
        $fsearch = Util::execute_command("sudo ps ax |grep 'fetch_all'| grep -v grep", FALSE, 'array');
    }
    else {
        $exit = true;
    }
}

// to alienvault_search process

$exit  = false;
$fsearch = Util::execute_command("sudo ps ax |grep 'alienvault_search'| grep -v grep", FALSE, 'array');

while( count($fsearch) > 0 && !$exit) {

    $pids_to_kill = array();

    foreach ($fsearch as $result) {
        if($debug) {
            echo $result."\n";
        }
        if (preg_match("/^\s*(\d+)\s.*/", $result, $match)) // get process pid
        {
            $pids_to_kill[] = $match[1];
        }
    }
    $fsearch = array();
    
    if(count($pids_to_kill)>0) {
    
        $all_pids = implode(" ", $pids_to_kill);

        if($debug) {
            echo "sudo kill -9 $all_pids\n";
        }
        
        Util::execute_command("sudo kill -9 ?", array($all_pids));
        sleep(2);
        $fsearch = Util::execute_command("sudo ps ax |grep 'alienvault_search'| grep -v grep", FALSE, 'array');
    }
    else {
        $exit = true;
    }
}

?>
