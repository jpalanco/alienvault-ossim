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



/*****************************************************************************
* This script apply SQL schema upgrade files automatically
****************************************************************************/
error_reporting(0);

function execute_sql($path_file_log, $sql_file, $upgrade) 
{
    $php_file = str_replace("_mysql.sql",".php",$sql_file);
    $php_file = preg_replace("/\.gz$/", "", $php_file); // Clean .gz
    if (file_exists($php_file)) {
    	execute_php($php_file, $upgrade);
    }
    // Gzipped .sql.gz
    if (preg_match("/\.gz$/", $sql_file)) {
    		$cmd = "zcat $sql_file | ossim-db > $path_file_log 2>&1";
    // Normal .sql
    } else {
    		$cmd = "ossim-db < $sql_file > $path_file_log 2>&1";
    }
    system($cmd, $ret);
    return $ret;
}

function execute_php($php_file, $upgrade) 
{
    preg_match("/\/(\d+\.\d+\.\d+)/",$php_file,$version);
    $upgrade->create_php_upgrade_object($php_file, $version[1]);
    $upgrade->php->end_upgrade();
    $upgrade->destroy_php_upgrade_object();
}

$path_class = '/usr/share/ossim/include/';
$path_log = '/var/log/ossim/';

ini_set('include_path', $path_class);

require_once 'av_init.php';

$upgrade = new Upgrade();
echo "\n\nDate: ". date("j/m/Y")."\n\n";
echo "-------------------------------------------------------------------\n";
echo "Detected Ossim Version:  ". $upgrade->ossim_current_version."\n";
echo "-------------------------------------------------------------------\n";
echo "Detected Schema Version: ". $upgrade->ossim_schema_version."\n"; 
echo "-------------------------------------------------------------------\n";
echo "Detected Database Type:  ". $upgrade->ossim_dbtype."\n"; 
echo "-------------------------------------------------------------------\n";


$ok = $upgrade->needs_upgrade();

if (!$ok) 
{
	echo "\nNo upgrades needed\n\n";
	exit();
}

echo "\nSearching upgrades...\n\n";
$cont = 1;

foreach($upgrade->get_needed() as $act)
{
	$sql_file = $act['sql']['file'];
	echo "Upgrade $cont: ".$sql_file."...";
	$file = basename($sql_file).".err";
	$path_file_log = $path_log.$file;
	
	if ( execute_sql($path_file_log, $sql_file, $upgrade) != 0 )
	{
	    echo "\nFailed to apply SQL schema upgrade file '$file'\n";
		if (file_exists($path_file_log))
		{
		   $cmd="cat $path_file_log";
		   echo "\nError Description: \n";
		   system($cmd);
		}
				
		echo "\n\nStatus: Upgrade Failed\n\n\n"; 
	    exit();
	}
	echo "\t Done\n";
	$cont++;
}

echo "\nStatus: Upgrade Sucessfull\n\n\n";
	

?>
