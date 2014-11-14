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


ob_implicit_flush();

$debug = false;

$id    = $argv[1];  // scheduled report id
$exit  = false;

exec ("sudo ps ax |grep 'AV Report Scheduler \[$id\]'| grep -v grep", $fsearch, $res);

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
        
        exec ("sudo kill -9 $all_pids");
        sleep(2);
        exec ("sudo ps ax |grep 'AV Report Scheduler \[$id\]'| grep -v grep", $fsearch, $res);
    }
    else {
        $exit = true;
    }
}

$exit  = false;
exec ("sudo ps ax |grep 'fetch_remote'| grep -v grep", $fsearch, $res);

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
        
        exec ("sudo kill -9 $all_pids");
        sleep(2);
        exec ("sudo ps ax |grep 'fetch_remote'| grep -v grep", $fsearch, $res);
    }
    else {
        $exit = true;
    }
}

// to fetch_all process

$exit  = false;
exec ("sudo ps ax |grep 'fetch_all'| grep -v grep", $fsearch, $res);

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
        
        exec ("sudo kill -9 $all_pids");
        sleep(2);
        exec ("sudo ps ax |grep 'fetch_all'| grep -v grep", $fsearch, $res);
    }
    else {
        $exit = true;
    }
}

// to alienvault_search process

$exit  = false;
exec ("sudo ps ax |grep 'alienvault_search'| grep -v grep", $fsearch, $res);

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
        
        exec ("sudo kill -9 $all_pids");
        sleep(2);
        exec ("sudo ps ax |grep 'alienvault_search'| grep -v grep", $fsearch, $res);
    }
    else {
        $exit = true;
    }
}

?>