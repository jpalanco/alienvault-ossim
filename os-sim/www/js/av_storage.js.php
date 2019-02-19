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

function av_session_db(db)
{
    this.db_name = db;
    
    this.save_check = function(id)
    {
        var db = sessionStorage.getObj(this.db_name);
     
        if (typeof db != 'object' || db == null)
        {
            db = {};
        }

        db[id] = true;
  
        sessionStorage.setObj(this.db_name, db); 
    }
    
    
    this.remove_check = function(id)
    {
        var db = sessionStorage.getObj(this.db_name);
        
        try
        {
            delete db[id];
        }
        catch(Err){}
        
        sessionStorage.setObj(this.db_name, db); 
    }
    
    
    this.is_checked = function(id)
    {
        //Query first object
        var db = sessionStorage.getObj(this.db_name);
        
        try
        {
            return db[id];
        }
        catch(Err)
        {
            return false;
        }
    }
    
    
    this.clean_checked = function()
    {
        sessionStorage.setObj(this.db_name, {});
    }
    
    
    
    
    Storage.prototype.setObj = function(key, obj) 
    {
        try
        {
            return this.setItem(key, JSON.stringify(obj));
        }
        catch(Err)
        {
            return false;
        }
    };


    Storage.prototype.getObj = function(key) 
    {
        try
        {
            return JSON.parse(this.getItem(key));
        }
        catch(Err)
        {
            return {};
        }
    };

}
