<?php
/**
* omp_sqlite.inc
*
* File omp_sqlite.inc is used to:
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
* @package    ossim-framework\Vulnerabilities
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/

class Omp_sqlite {

    private $conn;
    private $dbms_type;
    private $name_db;
    private $debugmode;
    
    public function __construct($name_db, $debug = 0) {
        $this->conn      = NULL;
        $this->dbms_type = "sqlite";
        $this->name_db   = $name_db;
        $this->debugmode = $debug;
    }
    
    public function connect_db() {
        
        try {
            $this->conn = new PDO($this->dbms_type.":".$this->name_db);
        }
        catch(PDOException $e)
        {
            echo '<b>[06] Error:</b> during database connection.';
            if ($this->debugmode == 1) {
                echo '<br/>The DBMS server said: '.$e->getMessage();
            }
        }
    }
    
    public function execute_db($sql) {
        try {
            $results = $this->conn->query($sql);
            //$results->setFetchMode(PDO::FETCH_BOTH);
        }
        catch(PDOException $e){
            echo '<p><b>[08] Error:</b> during query execution.';
            if ($this->debugmode == 1) {
                echo '<br/>The DBMS server said: '.$e->getMessage();
            }
        }
        
        return $results;
    }
    
    public function fetch_row_db(&$rs) {
        return $rs->fetch();
    }
    
    function __destruct(){
        try {
           $this->conn = null;
        }
        catch(PDOException $e) {
            echo $e->getMessage();
        }
    }
}

/* End of file omp_sqlite.inc */
/* Location: ../include/classes/omp_sqlite.inc */
