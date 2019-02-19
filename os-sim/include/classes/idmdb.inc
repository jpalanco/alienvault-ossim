<?php
/**
* idmdb.inc
*
* File idmdb.inc is used to:
*   - MongoDB connection and queries
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
* @package    ossim-framework\Assets
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


class Idmdb 
{ 
    public $mdb;
   
    public $err;
        
    
    public function __construct() 
    {
        // Connect to Mongo
        $this->err = "";
        
        if (!class_exists('Mongo')) 
        {
            $this->err = _("Mongo class not installed");
        } 
        else 
        {
            try 
            {
                $this->mdb = new Mongo();
            }
            catch (Exception $e) 
            {
                $this->err = _("Cannot connect to MongoDB"). " ".$e->getMessage();
            }        
            
            if (!$this->mdb) 
            {
                $this->err = _("Cannot connect to MongoDB");
            }
        }
    }
    
    
    public function available() 
    {
        return $this->err;
    }
    
    
    // Get available collections
    public function get_collections() 
    {
        $db    = $this->mdb->selectDB("inventory");
        $list  = $db->listCollections();
        $colls = array();
        
        foreach ($list  as $collection) 
        {
        	if (preg_match("/historic(\d\d\d\d\d\d\d\d)/", $collection, $fnd)) 
        	{
        		$colls[$fnd[1]] = $collection;
        	}
        }
        
        krsort($colls);
        
        return $colls;        
    }
    
    
    public function get_properties ($id, $timestamp) 
    {
        if (empty($id)) 
        {
            return FALSE;
        }        
        
        $empty    = array("date" => '', "hostname" => '', "mac" => '', "ip" => '', "ctx" => '', "service" => array(), "username" => array());
        $results  = array("date" => '', "hostname" => '', "mac" => '', "ip" => '', "ctx" => '', "service" => array(), "username" => array());
        $criteria = array("host_id" => new MongoBinData(pack("H*",$id),3)); // saerch by host id
        
        $db   = $this->mdb->selectDB("inventory");
        $from = -7;
        $data = array();
        $end  = FALSE;
        
        while (!$end && $from<=0)
        {
            $date       = gmdate("Ymd",strtotime("$timestamp $from day GMT"));
            $collection = "historic".$date;     
            $inventory  = $db->$collection; // historicYYYYMMDD 
            
            try 
            {
                $cursor = $inventory->find($criteria);
                
                if ($cursor) 
                {
                    //$match = $cursor->count();
                    $cursor->sort(array('date' => 1));
                    while($document = $cursor->getNext()) 
                    {
                         $data[] = $document;
                    }
                }
            } 
            catch (Exception $e) 
            {
                $end = TRUE;
            }
            
            $from++;
        }
        
        // consolidate results
        foreach ($data as $rs) 
        {
            if (!empty($rs["date"]))     
            {
                $results["date"] = gmdate("Y-m-d H:i:s",$rs["date"]->sec);
            }
            
            if (!empty($rs["hostname"])) 
            {
                $results["hostname"] = $rs["hostname"];
            }
            
            if (!empty($rs["mac"]))      
            {
                $results["mac"] = preg_replace("/(..)(..)(..)(..)(..)(..)/","$1:$2:$3:$4:$5:$6",bin2hex($rs["mac"]->bin));
            }
            
            if (!empty($rs["ip"]))       
            {
                $results["ip"] = @inet_ntop($rs["ip"]->bin);
            }
            
            if (!empty($rs["ctx"]))      
            {
                $results["ctx"] = strtoupper(bin2hex($rs["ctx"]->bin));
            }
            
            if (!empty($rs["service"]))  
            {
                $results["service"] = json_decode($rs["service"]);
            }
            
            if (!empty($rs["username"])) 
            {
                $results["username"] = json_decode($rs["username"]);
            }
        }
        
        return ($results == $empty) ? FALSE : $results;
    }
}

/* End of file idmdb.inc */
/* Location: ../include/classes/idmdb.inc */
