<?php
header("Content-type: text/javascript");

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


require_once 'av_init.php';
?>

function Av_internet_check()
{
    <?php 

    $conf     = new Config(); 
    $internet = $conf->get_conf('internet_connection');
    
    ?>
    
    var _check_internet = <?php echo intval($internet) ?>;
    var _internet       = true //By default internet is yes.
      
          
    var _check_internet_connection = function()
    {
        var internet = false;
        var url      = "https://www.alienvault.com/product/help/ping.php";
        
        //If browser is IE9, cross domain synchronous won't work so we'll return true.
        if ($.browser.msie && parseInt($.browser.version, 10) < 10)
        {
            return true;
        }

        $.ajax(
        {
        	url : url,
        	type: "HEAD",
            async : false,
            crossDomain: true,
            success: function()
            {
                internet = true;
            }
        });

        return internet;
    }
    
    
    if (_check_internet === 1) //Internet is yes, will do a ping to check connectivity
    {
        _internet = _check_internet_connection()
    }
    else if (_check_internet === 0) //Internet is always no
    {
        _internet = false
    }
    else if (_check_internet === 2) //Internet is always yes
    {
        _internet = true
    }

    
    //Return whether there is or not an internet connection.
    this.is_internet_available = function()
    {
        return _internet;
    };
    
}

