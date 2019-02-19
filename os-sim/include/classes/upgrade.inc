<?php
/**
* Upgrade.inc
*
* File Upgrade.inc is used to:
*   - Task 1 (TO DO)
*   - Task 2 (TO DO)
*   - Task 3 (TO DO)
*
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
* @package    ossim-framework\Upgrade
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


/**
* Function list:
* - Upgrade()
* - needs_upgrade()
* - get_all()
* - get_needed()
* - sort_by_version()
* - execute_sql()
* - create_php_upgrade_object()
* - destroy_php_upgrade_object()
* - apply_needed()
*/

require_once 'classes/Security.inc';

class Upgrade
{
    public $upgrades_path;

    
    public $conn;


    public $conf; // $GLOBALS["CONF"] from ossim_conf.inc


    public $about;


    public $ossim_dbtype;
    /*
    * Hardcoded inside include/classes/About.inc
    */


    public $ossim_current_version;


    /*
    * This property is stored in DB (table "conf"). Quering the
    * schema version we can detect if Ossim packages were updated
    * but not the dabase
    */
    public $ossim_schema_version;
    
    public function __construct() 
    {
        $ds = DIRECTORY_SEPARATOR;
        $path = dirname(__FILE__) . $ds . '..' . $ds . 'upgrades';
        
        if (is_dir($path)) 
        {
            // realpath() returns null if no path and we need to show the error
            $this->upgrades_path = realpath($path);
        } 
        else 
        {
            $this->upgrades_path = $path;
        }
        
        //$db = new Ossim_db();
        $this->conn = null; //$db->connect();
        $this->conf = $GLOBALS["CONF"];
        $this->ossim_dbtype = $this->conf->get_conf("ossim_type"); // ej: mysql
        $this->about = new About();
        $this->ossim_current_version = $this->about->get_version(); // ej: 0.9.8
        $this->ossim_schema_version  = $this->conf->get_conf("ossim_schema_version");
    }
    
    /*
    * Print information about Upgrade Status 
    */
    
    public static function info_upgrade ($required, $file)
    {
        $path_log = '/var/log/ossim/';

        $file = $path_log.$file.".err";

        if ($required == TRUE)
        {
            if ( file_exists($file) )
            {
                if ( filesize($file) > 0 )
                {
                    echo '<span style="color: #FF3333; padding-left: 5px;">Upgrade failure</span>';
                    echo '<br/><br/><span>';
                    $output = Util::execute_command("cat ?", array($file), 'string');
                    echo $output;
                    echo "</span>";
                }
            }
            else
            {
                echo '<span style="color: #11829D; padding-left: 5px;">Upgrade pending</span>';
            }
        } 
    }


    /*
    * Detects if a database needs upgrade
    */
    public function needs_upgrade()
    {
        // Detected old version without schema version tracking
        // we need at least schema version 0.9.8
        if (!$this->ossim_schema_version) 
        {
            // try to guess the version quering properties added in 0.9.8
            if (!$this->conf->get_conf("ossim_link")) 
            {
                ossim_set_error(_("Sorry, before run this script you need to " . "upgrade the database to version >= 2.1"));
            } 
            elseif ($this->conf->get_conf("nagios_link")) 
            {
                ossim_set_error(_("Detected uncomplete schema (CVS version?). " . "Please complete manually the upgrade to at least 2.1"));
            } 
            else 
            {
                $this->ossim_schema_version = '2.1';
            }
            
            return TRUE;
        }
        
        $oss_ver = $this->ossim_current_version;
        $oss_schm = $this->ossim_schema_version;
        /* The schema is greater than the ossim version, just wierd
        if (version_compare($oss_schm, $oss_ver, 'gt')) 
        {
            ossim_set_error(_("Detected schema version greater than ossim version, " . "please upgrade Ossim"));
            return TRUE;
        }*/
        
        // Normal case found when just upgraded ossim packages but not the DB
        if (version_compare($oss_schm, $oss_ver, 'lt'))
        {
            return TRUE;
        }
        
        return FALSE;
    }
    
    
    /*
    * Array
    (
    [0.9.9] => Array
    (
    [php] => Array
    (
    [file] => /home/tvvcox/os-sim/include/upgrades/0.9.9.php
    [dbtype] =>
    )
    )
    )
    */
    public function get_all() 
    {
        static $upgrades = array();
        
        if (count($upgrades)) 
        {
            return $upgrades;
        }
        
        if (!$dir = dir($this->upgrades_path))
        {
            die("Could not open upgrades path: '{$this->upgrades_path}'");
        }
        
        while (($file = $dir->read()) !== FALSE) 
        {
            if ($file == '.' || $file == '..') 
            {
                continue;
            }
            //$file = '0.9.9_mysql.sql.gz'; // for testing
            /*
            * Formats allowed:
            *  - <version>.php
            *  - <version>_<db_type>.sql
            *  - <version>_<db_type>.sql.gz
            */
            $file_match = preg_replace("/\.gz$/", "", $file); // Clean gz
            if (!preg_match('/(.+)\.([^.]+)$/', $file_match, $m)) 
            {
                continue;
            }
            
            $name = $m[1];
            $type = $m[2];
            
            if ($type == 'sql')
            {
                list($version, $dbtype) = explode('_', $name);
            } 
            elseif ($type == 'php') 
            {
                $version = $name;
                $dbtype = null;
            } 
            else 
            {
                continue;
            }
            
            $file = $this->upgrades_path . DIRECTORY_SEPARATOR . $file;
            
            $upgrades[$version][$type] = array(
                'file' => $file,
                'dbtype' => $dbtype
            );
        }
        
        // sort the array using version_compare()
        uksort($upgrades, array(
            $this,
            'sort_by_version'
        ));
        
        //print_r($upgrades);
        return $upgrades;
    }


    public function get_needed() 
    {
        static $upgrades = array();
        if (count($upgrades)) 
        {
            return $upgrades;
        }
        
        $oss_ver        = $this->ossim_current_version;
        $oss_schm       = $this->ossim_schema_version;
        $oss_dbtype     = $this->ossim_dbtype;
        $db_req_upgrade = array();
        
        foreach($this->get_all() as $version => $data) 
        {
            foreach($data as $type => $options) 
            {
                $file = $options['file'];
                $dbtype = $options['dbtype'];
                
                if ($type == 'sql') 
                {
                    $db_req_upgrade[] = $version;

                    if ($dbtype != $oss_dbtype)
                    {
                        continue;
                    }
                }
                if (version_compare($version, $oss_schm, 'gt') && version_compare($version, $oss_ver, 'le')) 
                {
                    $upgrades[$version][$type] = array(
                        'file' => $file,
                        'dbtype' => $dbtype
                    );
                }
            }
        }
        
        // User case: there is a 0.9.9_mysql.sql file, but the user's
        // database is pgsql. Maybe the developers haven't created a .sql
        // file for that engine. Detect this case.
        foreach($db_req_upgrade as $req) 
        {
            if (version_compare($req, $oss_schm, 'gt') && version_compare($req, $oss_ver, 'le')) 
            {
                $found = FALSE;
                do 
                {
                    if (!isset($upgrades[$req])) 
                    {
                        break;
                    }
                    
                    foreach($upgrades[$req] as $type => $data) 
                    {
                        if ($type == 'sql' && $data['dbtype'] == $oss_dbtype) 
                        {
                            $found = TRUE;
                            break 2;
                        }
                    }
                }

                while (FALSE);
                if (!$found) 
                {
                    $upgrades[$version]['error'] = array(
                        'file' => _("No sql file found for database: ") . $oss_dbtype,
                        'dbtype' => null
                    );
                }
            }
        }
        //printr($upgrades);
        return $upgrades;
    }


    public function sort_by_version($a, $b) 
    {
        if (version_compare($a, $b, 'eq')) 
        {
            return 0;
        }
        
        return version_compare($a, $b, 'gt') ? 1 : -1;
    }


    public function execute_sql($sql_file, $force = FALSE) 
    {
        $database = escapeshellarg($this->conf->get_conf('ossim_base'));
        $user     = escapeshellarg($this->conf->get_conf('ossim_user'));
        $pass     = escapeshellarg($this->conf->get_conf('ossim_pass'));
        $host     = escapeshellarg($this->conf->get_conf('ossim_host'));
        $port     = escapeshellarg($this->conf->get_conf('ossim_port'));
        
        switch ($this->ossim_dbtype) 
        {
            case 'mysql':
                $force = $force ? '-f' : '';
                $pass = $pass ? "-p{$pass}" : '';
                $cmd = "zcat -f $sql_file | mysql $force -v -u$user $pass -h$host -P$port $database | strings";
                break;

            default:
                die("Database engine not supported yet");
        }
        
        echo '<pre>';
            ob_implicit_flush(TRUE);
            passthru("$cmd 2>&1", $ret);
        echo '</pre>';
        
        if ($ret !== 0) 
        {
            echo "Failed to apply SQL schema upgrade file '$sql_file'. Command failed:<br><b>$cmd</b><br>";
            exit;
        }
    }


    public function create_php_upgrade_object($php_file, $version) 
    {
        // Create the new upgrade object
        include "$php_file";
        
        $class = 'upgrade_' . str_replace('.', '', $version);
        if (!class_exists($class)) 
        {
            die("Invalid class: '$class' for PHP upgrade script");
        }
        
        $this->php = new $class;
        // Make some useful properties available to it
        
        $this->php->conn = & $this->conn;
        $this->php->conf = & $this->conf;
        $this->php->about = & $this->about;
        $this->php->ossim_dbtype = $this->ossim_dbtype;
        $this->php->ossim_current_version = $this->ossim_current_version;
        $this->php->ossim_schema_version = $this->ossim_schema_version;
    }


    public function destroy_php_upgrade_object() 
    {
        unset($this->php);
    }


    public function apply_needed() 
    {
        $needed = $this->get_needed();
        
        foreach($needed as $version => $actions) 
        {
            $sql_exec = isset($actions['sql']) ? $actions['sql']['file'] : FALSE;
            $php_exec = isset($actions['php']) ? $actions['php']['file'] : FALSE;
           
            if ($php_exec) 
            {
                $this->create_php_upgrade_object($php_exec, $version);
                // Apply pre upgrade actions
                echo "<pre>" . _("Starting PHP PRE script") . "...\n";
                $this->php->start_upgrade();
                echo "\n" . _("PHP PRE script ended") . "</pre>";
            }
            
            if ($sql_exec) 
            {
                $this->execute_sql($sql_exec);
            }
            
            if ($php_exec) 
            {
                // Apply post upgrade actions
                echo "<pre>" . _("Starting PHP POST script") . "...\n";
                $this->php->end_upgrade();
                echo "\n" . _("PHP POST script ended") . "</pre>";
                $this->destroy_php_upgrade_object();
            }
        }
    }

    public function update_module_dr()
    {
        try
        {
            Util::execute_command('php /usr/share/ossim/scripts/report_module_count_update.php > /dev/null 2>&1 &');
        }
        catch (Exception $e) {}
    }
}

/* End of file Upgrade.inc */
/* Location: ../include/classes/Upgrade.inc */
