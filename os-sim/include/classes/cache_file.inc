<?php
/**
* cache_file.inc
*
* File cache_file.inc is used to:
*   - To save a file in cache memory
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


/**
* Cache_file Class
*
* Class for caching files
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/

class Cache_file
{ 
    /**
     * This function gets file content from cache
     *
     * @param string    $file    File to retrieve content
     * @param integer   $expire  [Optional] Max cache time
     *
     * @return string
     */
    public static function get_data($file, $expire = 300)
    {
        $data = '';

        if (empty($file))
        {
            return $data;
        }

        $file_path = AV_CACHE_DIR.'/'.$file;

        if(file_exists($file_path) && $expire != -1)
        {
            $current_time  = gmdate('U');
            $file_time     = gmdate('U', filemtime($file_path));

            if($expire == 0 || ($current_time - $file_time <= $expire))
            {
                $data = file_get_contents($file_path);
                $data = unserialize($data);
            }
        }

        return $data;
    }


    /**
     * This function gets asset content (Tree, SIEM, ...) from cache
     *
     * @param string    $file    File to retrieve content
     * @param integer   $expire  [Optional] Max cache time
     *
     * @return string
     */
    public static function get_asset_data($file, $expire = 0)
    {
        $data = '';

        $db   = new ossim_db();
        $conn = $db->connect();

        $rs = $conn->Execute("SELECT value FROM config WHERE conf ='latest_asset_change'");
        
        $db->close();

        if (!$rs || empty($file))
        {
            return $data;
        }

        $file_path = AV_CACHE_DIR.'/'.$file;

        $cache_time = strtotime($rs->fields['value'] . ' GMT');

        if (file_exists($file_path) && !empty($cache_time) && $expire != -1)
        {
            $file_time    = gmdate('U', filemtime($file_path));
            $current_time = gmdate('U');
            
            $cache_cond   = $file_time > $cache_time;
            $expire_cond  = $expire == 0 || ($current_time - $file_time <= $expire);

            if ($cache_cond && $expire_cond)
            {
                $data = file_get_contents($file_path);
                $data = unserialize($data);
            }
        }

        return $data;
    }
    
    
    /**
     * This function saves the content in cache
     *
     * @param string   $file   File to save
     * @param string   $data   Data to save
     *
     * @return boolean
     */
    public static function save_file($file, $data)
    {
        $file_path = AV_CACHE_DIR.'/'.$file;

        $res = @file_put_contents($file_path, serialize($data));

        if ($res !== FALSE)
        {
            $res = TRUE;
        }

        return $res;
    }


    /**
     * This function removes the cache file 
     *
     * @param string   $file   File to remove
     *
     * @return boolean
     */
    public static function remove_file($file)
    {
        $file_path = AV_CACHE_DIR.'/'.$file;

        $res = @unlink($file_path);

        return $res;
    }
}


/* End of file cache_file.inc */
/* Location: ../include/classes/cache_file.inc */
