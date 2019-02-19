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

/**************************************************************************/
/****************************  EVENT HANDLERS  ****************************/
/**************************************************************************/


function load_search_handlers(__asset_list)
{
    /**********  Tags Functions **********/

    $('#tags_filters').tagit(
    {
        onlyAllowDelete: true,
        beforeTagRemoved: function(event, ui)
        {
            return false;
        }
    });

    $(document).on('click', '#tags_filters .ui-icon-close', function(e)
    {
        e.preventDefault();
        e.stopImmediatePropagation();

        var info   = $(this).parents('li.tagit-choice').data('info').split('###');

        var type   = info[0];
        var value  = info[1];

        __asset_list.set_filter_value(type, value, 1);

        return false;
    });


    /* Tiptip */

    $('.tiptip').tipTip({attribute: 'data-title'});



    /**********  Filters Functions **********/

    //Lightbox for More Filters
    $('[data-bind="more-filters"]').on('click', function()
    {
        if (__asset_list.action_enabled(this))
        {
            __asset_list.show_more_filters();
        }
    });


    //Restart Filters
    $('[data-bind="restart-search"]').on('click', function()
    {
        __asset_list.restart_search();
    });



    /* ASSET SEARCH FILTER */

    $('[data-bind="search-asset"]').on('keyup', function(e)
    {
        if(e.keyCode == 13)
        {
            var value = $(this).val();

            if (value == '')
            {
                return false;
            }

            var label = '';

            if (__asset_list.is_ip_cidr(value))
            {
                var label = "<?php echo Util::js_entities(_('IP & CIDR:')) ?> " + value;
                __asset_list.set_filter_value(11, value, 0, label);
            }
            else
            {
                var label = "<?php echo Util::js_entities(_('Hostname & FQDN:')) ?> " + value;
                __asset_list.set_filter_value(12, value, 0, label);
            }

            $("#search_filter").val('');

            return false;
        }

    }).placeholder();


    /* GROUP SEARCH FILTER */

    $('[data-bind="search-group"]').on('keyup', function(e)
    {
        if(e.keyCode == 13)
        {
            var value = $(this).val();

            if (value == '')
            {
                return false;
            }

            var label = "<?php echo Util::js_entities(_('Group Name:')) ?> " + value;
            __asset_list.set_filter_value(22, value, 0, label);

            $("#search_filter").val('');

            return false;
        }

    }).placeholder();


    /* NETWORK SEARCH FILTER */

    $('[data-bind="search-network"]').on('keyup', function(e)
    {
        if(e.keyCode == 13)
        {
            var value = $(this).val();

            if (value == '')
            {
                return false;
            }

            var label = '';

            if (__asset_list.is_ip_cidr(value))
            {
                var label = "<?php echo Util::js_entities(_('Network CIDR:')) ?> " + value;
                __asset_list.set_filter_value(24, value, 0, label);
            }
            else
            {
                var label = "<?php echo Util::js_entities(_('Network Name:')) ?> " + value;
                __asset_list.set_filter_value(23, value, 0, label);
            }

            $("#search_filter").val('');

            return false;
        }

    }).placeholder();


    /* ALARMS & EVENTS FILTERS */
    $('.value_filter').on('change', function()
    {

        var del   = $(this).prop('checked') ? 0 : 1;
        var id    = $(this).data('id');
        var label = '';

        if (id == 3)
        {
            label = "<?php echo Util::js_entities(_('Has Alarms')) ?>";
        }
        else if (id == 4)
        {
            label = "<?php echo Util::js_entities(_('Has Events')) ?>";
        }

        __asset_list.set_filter_value(id, id, del, label);

    });


    /* ASSET VALUE FILTER */

    //Slider
    $('#arangeA, #arangeB').selectToUISlider(
    {
        tooltip: false,
        labelSrc: 'text',
        labels: 5,
        sliderOptions:
        {
            stop: function(event, ui)
            {
                var val1  = $('#arangeA').val();
                var val2  = $('#arangeB').val();

                var value = val1 + ';' + val2;

                var label = "<?php echo Util::js_entities(_('Asset Value:')) ?> " + val1 + ' - ' + val2;

                $('#tags_filters li.filter_6').remove();

                __asset_list.set_filter_value(6, value, 0, label);
            }
        }
    });


    //Checkbox to enable/disable slider
    $('#filter_6').on('change', function()
    {
        if ($(this).prop('checked'))
        {
            var v1    = $('#arangeA').val();
            var v2    = $('#arangeB').val();

            var value = v1 + ';' + v2;

            var label = "<?php echo Util::js_entities(_('Asset Value:')) ?> " + v1 + ' - ' + v2;

            $('#asset_value_slider .ui-slider').slider('enable');

            __asset_list.set_filter_value(6, value, 0, label);
        }
        else
        {
            //Removing tag
            $('#tags_filters li.filter_6').remove();

            //Setting filter value in object
            __asset_list.set_filter_value(6, '', 1);
        }
    });




    /* VULNERABILITIES FILTER */

    //Slider
    $('#vrangeA, #vrangeB').selectToUISlider(
    {
        tooltip: false,
        labelSrc: 'text',
        sliderOptions:
        {
            stop: function( event, ui )
            {
                var val1  = $('#vrangeB').val();
                var val2  = $('#vrangeA').val();
                var text1 = '';
                var text2 = '';

                var value = val1 + ';' + val2;

                $('#tags_filters li.filter_5').remove();

                text1 = $('#vrangeA option:selected').text();
                text2 = $('#vrangeB option:selected').text();

                var label = "<?php echo Util::js_entities(_('Vulnerabilities:')) ?> " + text1 + ' - ' + text2;

                __asset_list.set_filter_value(5, value, 0, label);
            }
        }
    });


    //Checkbox to enable/disable slider
    $('#filter_5').on('change', function()
    {
        if ($(this).prop('checked'))
        {
            var v1    = $('#vrangeB').val();
            var v2    = $('#vrangeA').val();
            var t1    = '';
            var t2    = '';

            var value = v1 + ';' + v2;

            $('#vulns_slider .ui-slider').slider('enable');

            t1 = $('#vrangeA option:selected').text();
            t2 = $('#vrangeB option:selected').text();

            var label = "<?php echo Util::js_entities(_('Vulnerabilities:')) ?> " + t1 + ' - ' + t2;

            __asset_list.set_filter_value(5, value, 0, label);

        }
        else
        {
            //Removing tag
            $('#tags_filters li.filter_5').remove();

            //Setting filter value in object
            __asset_list.set_filter_value(5, '', 1);
        }
    });



    /* HIDS FILTER */

    $('.hids_status_input .input_search_filter').on('change', function()
    {
        var id     = $(this).parents(".hids_status_input").data('filter');
        var val    = $(this).val();
        var l_text = $(this).next('span').text();

        var label  = "<?php echo Util::js_entities(_('HIDS Status')) ?>" + ": " + l_text;

        //Remove previous options
        $('#tags_filters li.filter_' + id).remove();

        __asset_list.set_filter_value(id, val, 0, label);
    });


    /* AVAILABILITY FILTER */

    $('.availability_status_input .input_search_filter').on('change', function()
    {
        var id     = $(this).parents(".availability_status_input").data('filter');
        var val    = $(this).val();
        var l_text = $(this).next('span').text();

        var label  = "<?php echo Util::js_entities(_('Availability Status')) ?>" + ": " + l_text;

        //Remove previous options
        $('#tags_filters li.filter_' + id).remove();

        __asset_list.set_filter_value(id, val, 0, label);

    });


    /* DATE FILTERS */

    $('.asset_date_input input[type=radio]').on('change', function()
    {
        var scope  = $(this).parents(".asset_date_input");
        var filter = $(scope).data('filter');
        var type   = $(this).val();
        var label  = '';
        var l_txt  = $(this).next('span').text();

        var value  = '';
        var from   = '';
        var to     = '';
        var del    = 0;

        if (type == 'range')
        {
            $('.asset_date_range', scope).show();

            from  = $('#date_from_'+ filter).val('');
            to    = $('#date_to_'+ filter).val('');

            value = type + ';' + from + ';' + to;

        }
        else
        {
            $('.asset_date_range', scope).hide();

            $('.calendar input', scope).val('');
        }

        value = type;

        if (filter == 1)
        {
            label = "<?php echo Util::js_entities(_('Assets Added:')) ?> " + l_txt;
        }
        else if (filter == 2)
        {
            label = "<?php echo Util::js_entities(_('Last Updated:')) ?> " + l_txt;
        }

        $('#tags_filters li.filter_'+filter).remove();

        __asset_list.set_filter_value(filter, value, del, label);

    });


    /*  CALENDAR PLUGIN  */

    $('.date_filter').datepicker(
    {
        showOn: "both",
        dateFormat: "yy-mm-dd",
        buttonImage: "/ossim/pixmaps/calendar.png",
        onSelect: function(date, ui)
        {
            var that   = ui.input;

            __asset_list.modify_date_filter(that);

        },
        onClose: function(selectedDate, ui)
        {
            var dir    = ui.id.match(/date_from_\d/);
            var filter = $(ui.input).data('filter');

            if (dir)
            {
                var dp = '#date_to_' + filter;

                $(dp).datepicker( "option", "minDate", selectedDate);
            }
            else
            {
                var dp = '#date_from_' + filter;

                $(dp).datepicker( "option", "maxDate", selectedDate);
            }
        }
    });


    $('.date_filter').on('keyup', function(e)
    {
        if (e.which == 13)
        {
            __asset_list.modify_date_filter(this);
        }
    });



    /**********  Asset Actions  **********/

    $('[data-bind="export-selection"]').on('click', function()
    {
        if (!__asset_list.action_enabled(this))
        {
             return false;
        }

        __asset_list.export_selection();
    });

}

