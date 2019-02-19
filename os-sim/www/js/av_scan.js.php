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

function show_info(type, subtype)
{
    var info = new Array();
        info["scan_type"]       = new Array()
        info["timing_template"] = new Array();

        info["scan_type"]["fast"] = "<?php echo '<strong>'._('Fast mode').'</strong> '._('will scan fewer ports than the default scan');?>";
        info["scan_type"]["full"] = "<?php echo '<strong>'._('Full mode').'</strong> '._('will be much slower but will include OS, services, service versions and MAC address into the inventory');?>"


        info["timing_template"]["T0"] = "<?php echo '<strong>'._('Paranoid').'</strong> '._('mode is for IDS evasion');?>";
        info["timing_template"]["T1"] = "<?php echo '<strong>'._('Sneaky').'</strong> '._('mode is for IDS evasion');?>";
        info["timing_template"]["T4"] = "<?php echo '<strong>'._('Aggressive').'</strong> '._('mode speed up the scan (fast and reliable networks)');?>";
        info["timing_template"]["T5"] = "<?php echo '<strong>'._('Insane').'</strong> '._('mode speed up the scan (fast and reliable networks)');?>";


    var show_in_tooltip = ($('.img_help_info').length > 0) ? true : false;

    if (typeof(info[type]) != 'undefined' && typeof(info[type][subtype]) != 'undefined')
    {
        if (show_in_tooltip == true)
        {
            $('#'+type+'_info img').show();
            $('#'+type+'_info img').tipTip({content: info[type][subtype], maxWidth: "350px", defaultPosition: "top"});
        }
        else
        {
            $('#'+type+'_info').html('<span class="small">'+info[type][subtype]+'</span>');
        }
    }
    else
    {
        if (show_in_tooltip == true)
        {
            $('#'+type+'_info img').hide();
        }
        else
        {
            $('#'+type+'_info').empty();
        }
    }
}


function change_scan_type()
{
    var value = $('#scan_type').val();

    if (value == "custom")
    {
        $('#tr_cp').show();
    }
    else
    {
        $('#tr_cp').hide();
        $('#custom_ports').val('1-65535');
    }

    if(value == 'ping')
    {
        // Ping scan doesn't work with "Autodetect services and Operating System" option.
        $("#autodetect").prop('checked', false);
    }
}


function bind_nmap_actions()
{
    // Ping scan doesn't work with "Autodetect services and Operating System" option. Force Fast scan
    $("#autodetect").on("click",  function(event)
    {
        if($("#autodetect").is(":checked") && $('#scan_type').val() == 'ping')
        {
            $('#scan_type').val('fast');

            var s_value = $('#scan_type').val();

            show_info('scan_type', s_value);
        }
    });


    $('#timing_template').on('change', function(){

        var t_value = $('#timing_template').val();

        show_info('timing_template', t_value);
    });


    // Show and change scan information
    $('#scan_type').on('change', function(){

        var s_value = $('#scan_type').val();

        show_info('scan_type', s_value);

        change_scan_type();
    });


    //Custom ports
    $("#custom_ports").click(function() {
        $("#custom_ports").removeClass('greyfont');
    });

    $("#custom_ports").blur(function() {
        $("#custom_ports").addClass('greyfont');
    });


    //Tooltips
    $(".info").tipTip({maxWidth: 'auto'});


    $('#scan_type').trigger('change');

    $('#timing_template').trigger('change');
}


//Scan host locally with
function scan_host(id)
{
    var url = '<?php echo Menu::get_menu_url("../netscan/index.php", 'environment', 'assets', 'assets')?>';

    var form = $('<form id="f_scan_host" action="' + url + '" method="POST">' +
        '<input type="hidden" name="action" value="custom_scan"/>' +
        '<input type="hidden" name="host_id" value="'+id+'"/>' +
        '<input type="hidden" name="sensor" value="automatic"/>' +
        '<input type="hidden" name="scan_type" value="fast"/>' +
        '<input type="hidden" name="timing_template" value="-T5"/>' +
        '<input type="hidden" name="autodetected" value="1"/>' +
        '<input type="hidden" name="rdns" value="1"/>' +
        '</form>');

    $('body').append(form);

    $("#f_scan_host").submit();
}

//Show fancybox to diplay the scan state
function show_state_box(status_code, subtitle, progress)
{
    var allowed_status = new Array();
        allowed_status[0] = '<?php echo _('Initializing scanning')?>';
        allowed_status[1] = '<?php echo _('Searching Hosts')?>';
        allowed_status[2] = '<?php echo _('Search finished')?>';
        allowed_status[3] = '<?php echo _('Scanning Hosts')?>';
        allowed_status[4] = '<?php echo _('Scan finished')?>';

    var box_content  = '';
    var box_title    = "<div class='box_title'>" + allowed_status[status_code] + "</div>";
    var box_subtitle = "<div class='box_subtitle'>" + subtitle + "</div>";
    var box_action   = '<div class="box_single_button"><input type="button" id="stop_scan" class="small" onclick="stop_scan()" value="<?php echo _('Stop Scan')?>"></div>';
    var box_bar      = '';

    if (status_code == 0)
    {
        box_bar     = "<div id='activitybar' class='av_activitybar activitybar_1'><div class='stripes'></div></div>";
        box_content = box_title + box_subtitle + box_bar;
    }
    else if (status_code == 1 || status_code == 2)
    {
        box_bar     = "<div id='activitybar' class='av_activitybar activitybar_2'><div class='stripes'></div></div>";
        box_content = box_title + box_subtitle + box_bar + box_action;
    }
    else
    {
        box_bar = "<div id='progressbar' class='av_progressbar'>" +
                       "<div class='stripes'></div>" +
                       "<span class='bar-label'>" + progress.percent + "%</span>" +
                       "<div id='progress_legend'>" +
                            "<span id='progress_current'>" + progress.current + "</span>/<span id='progress_total'>" + progress.total +  "</span> <?php echo _('Hosts') ?>" +
                            " (<span id='progress_remaining'>" + progress.time + "</span>)" +
                       "</div>" +
                    "</div>";

        box_content = box_title + box_subtitle + box_bar + box_action;
    }


    if($('#box-content').length == 0)
    {
        //Create fancybox

        $.fancybox({
            'modal': true,
            'width': 520,
            'height': 220,
            'autoDimensions': false,
            'centerOnScroll': true,
            'content': '<div id="box-content">' + box_content + '</div>',
            'overlayOpacity': 0.07,
            'overlayColor': '#000'
        });


        //Animate activity bar
        if (status_code >= 0 && status_code <= 2)
        {
            activityBar();
        }
        else
        {
            if (typeof(progress.percent) != 'undefined' && progress.percent != null)
            {
                progressBar(progress.percent, $('#progressbar'));
            }
        }
    }
    else
    {
        //Update fancybox

        if ($('.activitybar_1').length > 0 && (status_code == 1 || status_code == 2))
        {
            //From initial activity bar to activity bar
            $('.box_title').html(allowed_status[status_code]);
            $('.box_subtitle').html(subtitle);

            $('#activitybar').removeClass('activitybar_1').addClass('activitybar_2');
            $('#activitybar').after(box_action);
        }
        else if ($('#activitybar').length > 0 && (status_code == 3 || status_code == 4))
        {
            //From activity bar to progress bar
            $('#box-content').html(box_content);
        }

        if (status_code == 3 || status_code == 4)
        {
            //Update progressbar percent
            if (typeof(progress.percent) != 'undefined' && progress.percent != null)
            {
                $('#progress_current').html(progress.current);
                $('#progress_total').html(progress.total);
                $('#progress_remaining').html(progress.time);

                progressBar(progress.percent, $('#progressbar'));
            }
        }
    }
}

var __width = 0;


function activityBar()
{
    var activityBarWidth = 20 * $('#activitybar').width() / 100;
    __width = $('#activitybar').width() - activityBarWidth;
    $('.stripes', $('#activitybar')).animate({ width: activityBarWidth }, 400);
    animate_right($('.stripes', $('#activitybar')));
}


function animate_right(elem)
{
    $(elem).animate({opacity:1},{
        duration: 1000,
        step:function(now, fn)
        {
            fn.start = 1;
            fn.end = __width;

            $(elem).css({'left':now});
        },
        complete: function()
        {
            animate_left(elem);
        }
    });
}


function animate_left(elem)
{
    $(elem).animate({opacity:1},{
        duration: 1000,
        step:function(now, fn)
        {
            fn.start = __width;
            fn.end = 1;

            $(elem).css({'left':now});
        },
        complete: function()
        {
            animate_right(elem);
        }
    });
}


function progressBar(percent, element)
{
    var progressBarWidth = percent * element.width() / 100;

    $('.stripes', element).animate({ width: progressBarWidth }, 400);
    $('.bar-label', element).text(percent + "%");
}
