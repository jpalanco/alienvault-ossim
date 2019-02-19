<?php
/**
* host_sources.inc
*
* File host_sources.inc is used to:
*   - To manage host source references
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
* @package    ossim-framework\Asset
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


class Host_sources
{     
    /**
    * Host source references
    *
    * @var array
    * @access private
    */ 
    private $host_sources;
     
    
    /**
    * Class constructor
    *
    * @param object  $conn    Database access object
    * @param boolean $cache   [Optional] Use cached information 
    *
    * @throws Exception  If there is a connection error
    *
    * This function sets up the class
    *
    */  
    public function __construct($conn, $cache = TRUE)
    {
        $this->load_from_db($conn, $cache);
    }
    
    
    /**
    * This function returns the host sources
    *
    * @return array
    */  
    public function get_host_sources()
    {
        return $this->host_sources;
    }

    
    /**
    * This function sets the host sources from database
    *
    * @param object  $conn   Database access object
    * @param boolean $cache  [Optional] Use cached information
    *
    * @throws Exception  If there is a connection error
    *
    * @return void
    */ 
    private function load_from_db($conn, $cache = TRUE)
    {
        Ossim_db::check_connection($conn);
                
        $query  = 'SELECT * FROM host_source_reference ORDER BY id';

        $rs     = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    
        while (!$rs->EOF)
        {
            $this->host_sources[$rs->fields['id']] = array(
                'name'      => $rs->fields['name'], 
                'relevance' => $rs->fields['relevance']
            );
        
            $rs->MoveNext();
        }
    }
}

/* End of file host_sources.inc */
/* Location: ../include/classes/host_sources.inc */
