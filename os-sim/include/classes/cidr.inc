<?php
/**
* cidr.inc
*
* File cidr.inc is used to:
*   - Convertions between IP Addresses and CIDR ranges
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
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


class Cidr 
{
    // Expands a CIDR notation address into ranges.
    //
    // Thanks to M. Garkier for information and code bits on some important issues with this.
    //
    // example usage:
    // $ip_range=expand_CIDR("111.111.111.0/24","SHORT","IP");
    // print_r($ip_range);
    //
    // Author: mkr@binarywerks.dk
    // 2-Sep-2003
    //
    // Fixed flow control bug 12-Mar-2004, that resulted in excessive memory consumption, if invalid ranges was given.
    // Added parameters for controlling output - cleaned code.
    // Fixed precedence bug in IP validation - Thanks to selt!
    public static function expand_cidr($ip, $return_type = 'SHORT', $return_format = 'LONG') 
    {
        // validate IP address
        $num   = "(1?\d\d|2[0-4]\d|25[0-5]|[0-9])";
        $range = "(1\d|2\d|3[0-2]|[1-9])";
        
        if (!preg_match("/^$num\.$num\.$num\.$num\/$range$/", $ip)) 
        {
            return "IP wrong format\n";
        }
        // Separate CIDR structure into network-IP and netmask
        $ip_arr    = explode('/', $ip);
        
        // Calculate number of hosts in the subnet
        $mask_bits = $ip_arr[1];
        
		if ($mask_bits > 31 || $mask_bits < 0) 
		{
            if ($return_format == 'LONG')
            {
                return array(Asset_host_ips::ip2ulong($ip_arr[0]) + 0, Asset_host_ips::ip2ulong($ip_arr[0]) + 0);
            }
			else
			{
    			return array($ip_arr[0], $ip_arr[0]);
			}								
        }
		
        $host_bits = 32 - $mask_bits;
        $num_hosts = pow(2, $host_bits) - 1;
        
        // Netmask in decimal for use later: (hack around PHP always using signed ints)
        $netmask   = Asset_host_ips::ip2ulong('255.255.255.255') - $num_hosts;
        
        // Calculate start and end
        // Store IP-addresses internally as longs, to ease compare of two
        // addresses in a sorted structure.
        $ip_start  = Asset_host_ips::ip2ulong($ip_arr[0]) + 0;
        
        /*
        if ($ip_start != ( $ip_start & $netmask ))
        {
        return "WARNING: Address $ip not on network boundary\n";
        }
        */
        $ip_start = $ip_start & $netmask;
        $ip_end   = $ip_start + $num_hosts;
        
        switch ($return_type) 
        {
            case 'FULL':
            
                switch ($return_format) 
                {
                    case 'LONG':
                    
                        for ($i = 0; $i <= $num_hosts; $i++) 
                        {
                            $ip_range[] = $ip_start + $i;
                        }
                        
                        break;

                    case 'IP':
                    
                        for ($i = 0; $i <= $num_hosts; $i++) 
                        {
                            $ip_range[] = long2ip($ip_start + $i);
                        }
                        
                        break;

                    default:
                    
                        return "Unknown return format: $return_format\n";
                }
                
                break;

            case 'SHORT':
            
                switch ($return_format) 
                {
                    case 'LONG':
                    
                        if ($ip_start < 0)
                        {
                            $ip_start = 4294967296 - abs($ip_start);
                        }
                        
                        if ($ip_end < 0) 
                        {
                            $ip_end = 4294967296 - abs($ip_end);
                        }
                        
                        $ip_range[] = $ip_start;
                        $ip_range[] = $ip_end;
                        
                        break;

                    case 'IP':
                    
                        $ip_range[] = long2ip($ip_start);
                        $ip_range[] = long2ip($ip_end);
                        
                        break;

                    default:
                    
                        return "Unknown return format: $return_format\n";
                }
                break;

            default:
            
                return "Unknown return type: $return_type\n";
        }
        return $ip_range;
    }	
    
    /**
    * This function converts a netmask into cidr
    *
    * @param  string   $netmask     Netmask to convert
    *
    * @return integer      
    */
    public static function netmask2cidr($netmask)
    {
        
        $bits    = 0;
        $netmask = explode(".", $netmask);
        
        foreach($netmask as $octect)
        {
            $bits += strlen(str_replace("0", "", decbin($octect)));
        }
        
        return $bits;
    }
    
    
    /**
    * This function gets the network address from cidr & subnet
    *
    * @param  string    $ip     IP Address
    * @param  integer   $cidr   Subnet
    *
    * @return integer      
    */
    public static function cidr2network($ip, $cidr)
    {
        $network = long2ip((ip2long($ip)) & ((-1 << (32 - (int)$cidr))));
        
        return $network;
    }
    	
}

/* End of file cidr.inc */
/* Location: ../include/classes/cidr.inc */