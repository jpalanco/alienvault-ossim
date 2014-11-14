<?php
/**
* properties.inc
*
* File properties.inc is used to:
*   - To manage properties
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


class Properties
{     
    /**
    * Properties
    *
    * @var array
    * @access private
    */ 
    private $properties;
         
    
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
    * This function returns the properties
    *
    * @return array
    */  
    public function get_properties() 
    {
        return $this->properties;
    }
    
    
    /**
    * This function returns all images associated with the properties
    *
    * @return array
    */ 
    public static function get_pixmaps()
    {        
        $icons = array (
            'software'         => 'software.png',
            'operating-system' => 'host_os.png',
            'cpu'              => 'cpu.png',
            'service'          => 'ports.png',
            'memory'           => 'ram.png',
            'video'            => 'video.png',
            'department'       => 'host_group.png',
            'macaddress'       => 'mac.png',
            'workgroup'        => 'net_group.png',
            'role'             => 'server_role.png',
            'acl'              => 'acl.png',
            'storage'          => 'storage.png',
            'route'            => 'route.png',
            'state'            => 'state.png',
            'username'         => 'username.png'
       );
       
       return $icons;
    }
    
    
    /**
    * This function returns the image associated with the operating system $os
    *
    * @param string   $os          Operating System
    * @param string   $pixmap_dir  Image path
    * @param boolean  $img_only    [Optional] Return image path o html image
    *
    * @throws Exception  If a connection error occurred
    *
    * @return string      
    */
    public static function get_os_pixmap($os, $pixmap_dir = '../pixmaps/', $img_only = FALSE)
    {
        $os_image = '';
                
        if (!empty($os)) 
        {                  
            if (preg_match('/win/i', $os)) 
            {
                $os_image = 'win.png';
                $alt      = 'Windows';
            }
            elseif (preg_match('/linux/i', $os)) 
            {
                $os_image = 'linux.png';
                $alt      = 'Linux';
            }
            elseif (preg_match('/openbsd/i', $os)) 
            {
                $os_image = 'openbsd.png';
                $alt      = 'Open BSD';
            }        
            elseif (preg_match('/netbsd/i', $os)) 
            {
                $os_image = 'netbsd.png';
                $alt      = 'Net BSD';
            }            
            elseif (preg_match('/bsd/i', $os)) 
            {
                $os_image = 'bsd.png';
                $alt      = 'BSD';
            }            
            elseif (preg_match('/mac/i', $os)) 
            {
                $os_image = 'mac.png';
                $alt      = 'Mac OS';
            } 
            elseif (preg_match('/sun/i', $os)) 
            {
                $os_image = 'sunos.png';
                $alt      = 'Sun OS';
            }
            elseif (preg_match('/solaris/i', $os)) 
            {
                $os_image = 'solaris.png';
                $alt      = 'Solaris';
            }
            elseif (preg_match('/cisco|ios\/?11|ios\/?12|ios\/?13|ios\/?14|ios\/?15/i', $os)) 
            {
                $os_image = 'cisco.png';
                $alt      = 'Cisco';
            } 
            elseif (preg_match('/ios/i', $os)) 
            {
                $os_image = 'mobile.png';
                $alt      = 'iOS';
            } 
            elseif (preg_match('/android/i', $os)) 
            {
                $os_image = 'mobile.png';
                $alt      = 'Android';
            } 
            elseif (preg_match('/hp-ux/i', $os)) 
            {
                $os_image = 'hp-ux.png';
                $alt      = 'HP-UX';
            } 
            elseif (preg_match('/aix/i', $os)) 
            {
                $os_image = 'ibm-aix.png';
                $alt      = 'IBM AIX';
            }  
            elseif (preg_match('/irix/i', $os)) 
            {
                $os_image = 'irix.png';
                $alt      = 'SGI IRIX';
            }  
            else
            {
                $os_image = 'unknown.png';
                $alt      = 'Unknown';
            }
        }
                        
        // Matching with nothing
        if (empty($os_image))
        {
            return '';
        }
        
        if ($img_only == TRUE)
        {
            return $pixmap_dir.'/os/'.$os_image;
        }
        else
        {
            return "<img src='$pixmap_dir/os/$os_image' alt='$alt' align='absbottom'/>";
        }
    }
    
    
    /**
    * This function returns all OS available
    *
    * @return array
    */ 
    public static function get_all_os()
    {
        $array_os = array(
            'Unknown'           => '',
            'Microsoft Windows' => 'Microsoft Windows',
            'Linux'             => 'Linux',
            'FreeBSD'           => 'FreeBSD',
            'NetBSD'            => 'NetBSD',
            'OpenBSD'           => 'OpenBSD',
            'Apple MacOSX'      => 'Apple MacOSX',
            'SUN Solaris'       => 'SUN Solaris',
            'Cisco IOS'         => 'Cisco IOS',
            'IBM AIX'           => 'IBM AIX',
            'HP-UX'             => 'HP-UX',
            'Compaq Tru64'      => 'Compaq Tru64',
            'SGI IRIX'          => 'SGI IRIX',
            'BSD\/OS'           => 'BSD/OS',
            'SunOS'             => 'SunOS',
            'Plan9'             => 'Plan9',
            'iOS'               => 'iOS',
            'Android'           => 'Android'
        );
        
        return $array_os;
    }
         
    
    /**
    * This function sets the properties from database
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
        
        $query  = 'SELECT * FROM host_property_reference ORDER BY ord';
                
        $rs     = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);
        
        if (!$rs) 
        {           
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }             
    
        while (!$rs->EOF)
        {           
            $this->properties[$rs->fields['id']] = array(
                "name"        => $rs->fields['name'],
                'description' => $rs->fields['description'],
                'ord'         => $rs->fields['ord']
            );
        
            $rs->MoveNext();
        }
    }
}

/* End of file properties.inc */
/* Location: ../include/classes/properties.inc */