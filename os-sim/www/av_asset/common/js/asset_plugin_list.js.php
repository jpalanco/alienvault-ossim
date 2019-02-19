<?php
header('Content-type: text/javascript');

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

/* Class Av_plugin_list
 *
 * This class is used to configure the plugins related with assets
 * It creates a dataTable object and uses av_plugin_select object for each row
 *
 * @param   config   Options to initialize de Av_plugin_list instance
 *
 */

function Av_plugin_list(config)
{
    //Public variables
    this.edit_mode     = config.edit_mode; // Note: Always 'true' by now
    this.sensor_id     = config.sensor_id;
    this.maxrows       = config.maxrows || 10;
    this.dt_obj        = {};
    this.av_plugin_obj = new AVplugin_select();

    //Asset data
    var __asset_data   = config.asset_data || {};
    
    
    //Private variables
    this.__plugin_data = {}; // Temporary array to initialize the selectors
    


    //Copy of this
    var __self = this;


    
    /**************************************************************************/
    /***************************  DRAW FUNCTIONS  *****************************/
    /**************************************************************************/

    /*
     * This function creates the instance of the dataTable object and draws the paginated table
     * It creates a plugin selector object for each row (each asset)
     * 
     */
    this.draw = function()
    {
        var dt_parameters  = __get_dt_parameters();
        var aaSorting      = dt_parameters.sort;
        var aoColumns      = dt_parameters.columns;
        var fnServerParams = dt_parameters.server_params;
        var iDisplayLength = dt_parameters.maxrows;
        this.total_counter = 0;
        __self.dt_obj = $('.table_data').dataTable( 
        {
            "bProcessing": true,
            "bServerSide": true,
            "bDeferRender": true,
            "sAjaxSource": "<?php echo AV_MAIN_PATH . "/av_asset/common/providers/dt_plugins.php" ?>",
            "iDisplayLength": iDisplayLength,
            "bLengthChange": false,
            "sPaginationType": "full_numbers",
            "bFilter": false,
            "aLengthMenu": [[10, 20, 50], [10, 20, 50]],
            "bJQueryUI": true,
            "aaSorting": aaSorting,
            "aoColumns": aoColumns,
            oLanguage : 
            {
                "sProcessing": "<?php echo _('Loading')?>...",
                "sLengthMenu": "Show _MENU_ entries",
                "sZeroRecords": "<?php echo _('No plugins found')?>",
                "sEmptyTable": "<?php echo _('No plugins found')?>",
                "sLoadingRecords": "<?php echo _('Loading') ?>...",
                "sInfo": "<?php echo _('Showing _START_ to _END_ of _TOTAL_ assets')?>",
                "sInfoEmpty": "<?php echo _('Showing 0 to 0 of 0 entries')?>",
                "sInfoFiltered": "(<?php echo _('filtered from _MAX_ total entries')?>)",
                "sInfoPostFix": "",
                "sInfoThousands": ",",
                "sSearch": "<?php echo _('Search')?>",
                "sUrl": "",
                "oPaginate": {
                    "sFirst":    "<?php echo _('First') ?>",
                    "sPrevious": "<?php echo _('Previous') ?>",
                    "sNext":     "<?php echo _('Next') ?>",
                    "sLast":     "<?php echo _('Last') ?>"
                }
            },
            "fnRowCallback": function(nRow, aData, iDrawIndex, iDataIndex)
            {
                // Create table containers to load the Plugin Select Boxes inside
                if (__self.edit_mode)
                {
                    var _asset_id = aData['DT_RowId'];
                    $.each(aData['DT_RowData'], function(key, val)
                    {
                        // Create one select container for each plugin the each asset
                        
                        var _asset_plugins_total = (typeof presaved_data[_asset_id] != 'undefined') ? presaved_data[_asset_id].length : val.length;
                        
                        for (var i = 0; i < _asset_plugins_total; i++)
                        {
                            // Get the selects selections from presaved_data or ajax response if not
                            var _vendor   = (typeof presaved_data[_asset_id] != 'undefined') ? presaved_data[_asset_id][i]['vendor']   : val[i].vendor;
                            var _model    = (typeof presaved_data[_asset_id] != 'undefined') ? presaved_data[_asset_id][i]['model']    : val[i].model;
                            var _version  = (typeof presaved_data[_asset_id] != 'undefined') ? presaved_data[_asset_id][i]['version']  : val[i].version;
                            var _mlist    = (typeof presaved_data[_asset_id] != 'undefined') ? presaved_data[_asset_id][i]['mlist']    : val[i].model_list;
                            var _vlist    = (typeof presaved_data[_asset_id] != 'undefined') ? presaved_data[_asset_id][i]['vlist']    : val[i].version_list;
                            
                            // Data into __plugin_data will be used in Select Object constructor
                            if (typeof __self.__plugin_data[_asset_id] == 'undefined')
                            {
                                __self.__plugin_data[_asset_id] = [];
                            }
                            
                            __self.__plugin_data[_asset_id].push({'vendor':       _vendor,
                                                                  'model':        _model,
                                                                  'version':      _version,
                                                                  'model_list':   $.parseJSON(_mlist),
                                                                  'version_list': $.parseJSON(_vlist)});
                            
                        }
                    });
                    var _table = '<table class="plugin_list plugin_select_container" data-asset_id="' + _asset_id + '"></table>';
                    
                    var _aux_container = (__asset_data.asset_type == 'asset') ? $("td:nth-child(1)", nRow) : $("td:nth-child(2)", nRow);
                    $(_table).appendTo(_aux_container);
                    _aux_container.attr('colspan', 4);
                }
                
            },
            "fnServerData": function ( sSource, aoData, fnCallback, oSettings ) 
            {
                oSettings.jqXHR = $.ajax( 
                {
                    "dataType": 'json',
                    "type": "POST",
                    "url": sSource,
                    "data": aoData,
                    "beforeSend": function()
                    {
                        if (__asset_data.asset_type == 'asset')
                        {
                            $('.dt_footer').hide();
                        }
                    },
                    "success": function (json) 
                    {
			__self.total_counter = json.total_counter;
                        fnCallback(json);

                    },
                    "error": function(data)
                    {
                        //Check expired session
                        var session = new Session(data, '');
                        
                        if (session.check_session_expired() == true)
                        {
                            session.redirect();
                            return;
                        }
                        
                        
                        var error = '<?php echo _('Unable to load the plugins info for this asset') ?>';
                        show_notification('plugin_notif', error, 'nf_error', 5000, true);
                        
                        var json = $.parseJSON('{"sEcho": '+aoData[0].value+', "iTotalRecords": 0, "iTotalDisplayRecords": 0, "aaData": "" }');
                        
                        fnCallback( json );
                    },
                    "complete": function()
                    {
                        if (__self.edit_mode)
                        {
                            // Create Plugin Select Object from container data attributes
                            $('.plugin_list').each(function()
                            {
                                var _asset_id = $(this).attr('data-asset_id')
                                __self.av_plugin_obj.create(this, __self.__plugin_data[_asset_id], __self.total_counter);
                            });
                            
                            __self.__plugin_data = {}; // Clean aux data for next requests
                            
                            $('.dataTables_empty').attr('colspan', 5);
                        }
                    }
                });
            },
            "fnServerParams": function (aoData)
            {
                $.each(fnServerParams, function(index, value) {
                    aoData.push(value);
                });
                
                aoData.push( { "name": "edit_mode",  "value": __self.edit_mode } );
                aoData.push( { "name": "sensor_id",  "value": __self.sensor_id } );
            }
        });
    };



    /**************************************************************************/
    /****************************  STORAGE FUNCTIONS  *************************/
    /**************************************************************************/

    /*
     * This function saves the changes made to all plugin selectors in all pages
     *
     * @param  callback    [Optional] Callback function to call when changes are saved
     * @param  url         [Optional] Target URL to save the changes via ajax
     */
    this.apply_changes = function(callback, url)
    {
        __self.av_plugin_obj.apply_changes(callback, url);
    }
    
    
    
    /**************************************************************************/
    /****************************  HELPER FUNCTIONS  **************************/
    /**************************************************************************/

    this.change_sensor = function(new_sensor)
    {
        // Change the sensor for the self plugin (asset_plugin_list.js.php)
        __self.sensor_id = new_sensor;
        
        // Change the sensor in the selectors plugin (av_plugin_select.js.php)
        // The __self parameter is to redraw dataTable there inside
        __self.av_plugin_obj.change_sensor(new_sensor, __self);
    }
    
    /*
     * This function returns the parameters to initialize the dataTable instance
     * The parameters are loaded from the options given in the main constructor
     *
     */
    function __get_dt_parameters()
    {
        var sort          = [];
        var columns       = [];
        var server_params = [];

        
        sort = [[1, "desc"]];


        if (__self.edit_mode == 1)
        {
            columns = [
                { "bSortable": false, "sClass" : "td_asset", "bVisible": (__asset_data.asset_type == 'asset') ? false : true },
                { "bSortable": false, "sClass" : "td_main" }
            ];
        }
        else
        {
            // Not working yet TODO
            columns = [
                
            ];
        }

        server_params = [
            {"name": "asset_id",  "value" : __asset_data.asset_id},
            {"name": "asset_type","value" : __asset_data.asset_type}
        ];


        var dt_parameters = {
            'sort'          : sort,
            'columns'       : columns,
            'server_params' : server_params,
            'maxrows'       : __self.maxrows
        }

        return dt_parameters;
    }
    
};
