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

function gvm_plugins_db(profile_id)
{
    /*
    *
    * JavaScript object to manage a GVM profile (Plugins that a user has enabled/disabled by hand and filters applied)
    *
    * */

    this.profile_id = profile_id;

    this.filters = {
        'family_id' : '',
        'category_id' : '',
        'cve' : '',
        'plugin' : ''
    };

    this.actions = {
        'enable_all' : 0,
        'disable_all' : 0
    }

    this.enabled_plugins = {};

    this.disabled_plugins = {};

    this.set_filters = function (filters) {
        var self = this;

        if (typeof filters === 'object' && Object.keys(filters).length > 0){
            $.each(filters, function(id, value) {
                self.set_filter(id, value);
            });

            return true;
        }

        return false;
    }

    this.set_filter = function (id, value) {
        if (this.filters.hasOwnProperty(id)){
            this.filters[id] = value;
            return true;
        }

        return false;
    }

    this.set_action = function (id, value) {
        if (this.actions.hasOwnProperty(id)) {
            this.actions[id] = value;
        } else {
            return null;
        }
        
        return true;
    }

    this.get_profile = function () {
        return this.profile_id;
    }

    this.get_action = function (id) {
        if (this.actions.hasOwnProperty(id)) {
            return this.actions[id];
        } else {
            return null;
        }
    }

    this.get_actions = function () {
        return this.actions;
    }

    this.get_filter = function (id) {
        if (this.filters.hasOwnProperty(id)) {
            return this.filters[id];
        } else {
            return null;
        }
    }

    this.get_filters = function () {
        return this.filters;
    }
   
    this.get_enabled_plugins = function(){
        return this.enabled_plugins;
    }

    this.get_disabled_plugins = function(){
        return this.disabled_plugins;
    }

    this.enable_plugin = function (id) {
        if (this.is_disabled(id)){
            delete this.disabled_plugins[id]
        }

        if (!this.enabled_plugins.hasOwnProperty(id)){
            this.enabled_plugins[id] = true;
        }

        return true;
    }

    this.disable_plugin = function (id) {
        if (this.is_enabled(id)){
            delete this.enabled_plugins[id];
        }

        if (!this.disabled_plugins.hasOwnProperty(id)){
            this.disabled_plugins[id] = true;
        }

        return true;
    }

    this.is_enabled = function(id) {
        try
        {
            return this.enabled_plugins[id];
        }
        catch(Err)
        {
            return false;
        }
    }

    this.is_disabled = function(id) {
        try
        {
            return this.disabled_plugins[id];
        }
        catch(Err)
        {
            return false;
        }
    }
  
    this.remove_filters = function() {
        this.filters = {
            'family_id' : '',
            'category_id' : '',
            'cve' : '',
            'plugin' : ''
        };
    }

    this.remove_plugins = function() {
        this.enabled_plugins = {};

        this.disabled_plugins = {};
    }

    this.remove_actions = function() {
        this.actions = {
          'enable_all': 0,
          'disable_all': 0
        }
    }
}
