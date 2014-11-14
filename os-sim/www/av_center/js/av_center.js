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


/********************************************************
***************** Abort AJAX Requests *******************
*********************************************************/

function Ajax_Requests(size){
    this.requests   = new Array(size);
    this.size       = size;
    this.position   = 0;
    
    this.abort = function(){
        
        for (var i=0; i<this.size; i++)
        {
            if (typeof(this.requests[i]) == 'object' && this.requests[i] != 'null')
            {    
                this.requests[i].abort();
            }
        }
        
        this.requests = new Array(this.size);
        this.position = 0;
    };
    
    this.add_request = function(xhr){
        var position = this.position;
        this.requests[position] = xhr;
        this.position++;
        
        if ((this.position + 1) >= this.size)
        {
            this.position = 0;
        }
    };
}


/*******************************************************
*****************      Main Page        ****************
********************************************************/

function Main(){}

Main.pre_search_avc = function(){
    
    if ($('#search').val() == labels['search'])
    {
        $('#search').val('');
    }
        
    $('#search').css('color', '#555555');
    $('#search').css('font-style', 'normal');
};


Main.autocomplete_avc = function(av_components){
    
    $("#search").autocomplete(av_components, {
        minChars: 0,
        width: 300,
        max: 100,
        matchContains: true,
        mustMatch: true,
        autoFill: false,
        extraParams: { action: 'autocomplete' },
        formatItem: function(row, i, max) {
            return row.txt;
        }
    }).result(function(event, item) {
        
        if (typeof(item) != 'undefined' && item != null)
        {
            $('#search_results div').empty();
            
            $('#h_search').val(item.id);
        }
        else
        {
            $('#h_search').val('');
        }       
    });

};

Main.delete_system = function(id){
    
    var keys      = { "yes": labels['delete_yes'], "no": labels['delete_no'] };
    var system_id = id.replace('row_', '');

    av_confirm(labels['delete_msg'], keys).done(function(){

        $.ajax({
            type: "POST",
            url: "data/sections/main/delete_system.php",
            cache: false,
            dataType: "json",
            data: "system_id="+system_id,
            beforeSend: function(xhr) {

                $('.w_overlay').remove();

                if ($('.w_overlay').length < 1)
                {
                    var height = $.getDocHeight();
                    $('body').append('<div class="w_overlay" style="height:'+height+'px;"></div>');
                }

                if ($('.l_box').length < 1)
                {
                    var config  = {
                        content: labels['deleting'],
                        style: 'width: 400px; top: 35%; padding: 5px 0px; left: 50%; margin-left: -175px;',
                        cancel_button: false
                    };  
                    
                    var loading_box = Message.show_loading_box('s_box', config);

                    $('body').append('<div class="l_box" style="display:none;">'+loading_box+'</div>');
                }
                else
                {
                    $('.l_box .r_lp').html(labels['deleting']);
                }
                
                $('.l_box').show();
                
            },
            error: function (data){

                $('.l_box').remove();
                $('.w_overlay').remove();

                //Check expired session
                var session = new Session(data, '');
                
                if (session.check_session_expired() == true)
                {
                    session.redirect();
                    return;
                }
            },
            success: function(data){

                var cnd_1  = (typeof(data) == 'undefined' || data == null);
                var cnd_2  = (typeof(data) != 'undefined' && data != null && data.status != 'success');

                if (cnd_1 || cnd_2)
                {
                    $('.l_box').remove();
                    $('.w_overlay').remove();

                    var error_msg = (cnd_1 == true) ? labels['unknown_error'] : data.data;
                    error_msg = "<div style='padding-left: 10px;'>"+error_msg+"</div>";

                    var config_nt = { content: error_msg,
                                      options: {
                                        type: 'nf_error',
                                        cancel_button: true
                                      },
                                      style: 'width: 500px; text-align:center; padding: 1px; margin:auto; z-index:10000;'
                                    };
                    nt            = new Notification('nt_1', config_nt);
                    notification  = nt.show();

                    $('#c_hal #c_hal_content').html(notification);
                    nt.fade_in(2000, '', '');

                    setTimeout('nt.fade_out(4000, "", "");', 10000);
                }
                else
                {
                    document.location.reload();
                }
            }
        });
    });
};

Main.search = function(){
    
    var system_id = $('#h_search').val();
    
    if (system_id == '')
    {        
        var notification = '<span class="small" style="color: #D8000C">'+labels['host_no_found']+'</span>';

        $('#search_results div').html(notification);

        return false;
    }
    else
    {
        $('#search_results div').empty();
    }
    
    var res = get_system_info(system_id);
    
    if (res == false)
    {
        session.redirect();
        return;
    }
    
    if (res.status == 'error')
    {
        var config_nt = { content: labels['error_search'], 
                              options: {
                                type:'nf_error',
                                cancel_button: false
                              },
                              style: 'width: 90%; margin: auto; text-align:center;'
                            };
            
        nt            = new Notification('nt_1', config_nt);
        notification  = nt.show();
        
        $('#r_sc').html(notification);
        
        nt.fade_in(2000, '', '');
        setTimeout('nt.fade_out(4000, "", "");', 10000);
    }
    else
    {
        var data = {
            system_id: system_id,
            profiles:  res.data.profile,
            host:      res.data.name+ " ["+ res.data.admin_ip+"]"
        };
        
        section = new Section(data, 'home', 1);
        section.load_section('home'); 
    }
};

Main.go_section = function(system_id, id_section){

    var system_id = system_id.replace("row_", "");

    var res = get_system_info(system_id);

    if (res == false)
    {
        //Check expired session
        var session = new Session('', '');
        session.redirect();
        return;
    }

    if (res.status == 'error')
    {
        var config_nt = { content: labels['error_section'], 
                          options: {
                            type:'nf_error',
                            cancel_button: false
                          },
                          style: 'width: 280px; text-align:center; padding: 1px; margin:auto; z-index:10000;'
                        };
                        
        nt            = new Notification('nt_1', config_nt);
        notification  = nt.show();
                
        $('#c_hal #c_hal_content').html(notification);
        nt.fade_in(2000, '', '');
        setTimeout('nt.fade_out(4000, "", "");', 10000);
    }
    else
    {
        var data = {
            system_id: system_id,
            profiles:  res.data.profile,
            host:      res.data.name+ " ["+ res.data.admin_ip+"]"
        };

        var id_sec = (id_section == '') ? 'home' : id_section;

        section = new Section(data, id_sec, 1);
        section.load_section(id_sec); 
    }
};


Main.external_access = function(data, id_section, show_loading){

    if (show_loading == true)
    {
        var config  = {
            content: labels['ret_info'] + " ...",
            style: 'width: 350px; top: 35%; padding: 5px 0px; left: 50%; margin-left: -175px;',
            cancel_button: false
        };

        var height = $.getDocHeight();

        var loading_box = Message.show_loading_box('s_box', config);

        if ($('.w_overlay').length < 1)
        {
            $('body').append('<div class="w_overlay" style="height:'+height+'px;"></div>');
        }
        
        if ($('.l_box').length < 1)
        {
            $('body').append('<div class="l_box" style="display:none;">'+loading_box+'</div>');
        }

        parent.window.scrollTo(0, 0);

        $('.l_box').show();
    }
    
    var data = {
        system_id: data.system_id, 
        profiles:  data.profiles,
        host:      data.name+ " ["+ data.admin_ip+"]"
    };
    
    section = new Section(data, id_section, 1);
    section.load_section(id_section); 
}

Main.display_avc_info = function(show_loading){
    var xhr = $.ajax({
        type: "POST",
        data: "action=display_avc",
        url: "data/sections/main/main.php",
        cache: false,
        beforeSend: function(xhr) {

            if (show_loading == true)
            {
                var config  = {
                    content: labels['loading'] + " ...",
                    style: 'width: 200px; top: 30%; padding: 5px 0px; left: 50%; margin-left: -100px;',
                    cancel_button: false
                };  

                var loading_box = Message.show_loading_box('s_box', config);

                var height = $('#load_avc_data').height();
                
                $('#avc_data').css('height', height);  
                $('#avc_data').html("<div style='height:"+height+";'>"+loading_box+ "</div>");
            }
        },
        success: function(data){

            //Check expired session
            var session = new Session(data, '');
            
            if (session.check_session_expired() == true)
            {
                session.redirect();
                return;
            }

            $("#avc_data").html('');
            $('#avc_data').css('height', 'auto');
            var status = data.split("###");

            if (status[0] == 'error')
            {
                display_sec_errors(status[1]);

                var time = 60000;
                timer = window.setInterval(function(){Main.display_avc_info(true);}, time);
            }
            else
            {
                $("#avc_data").html(status[1]);

                var time = 60000;
                timer = window.setInterval(function(){Main.real_time();}, time);
            }
        }
    }); 

    ajax_requests.add_request(xhr);
};


Main.real_time = function(){

    if ($('.td_no_av_components').length == 0)
    {
        $('#tbody_avcl tr').each(function(index) {

            var system_id = $(this).attr('id').replace('row_', '');

            if (system_id != null)
            {
                Main.update_system_information(system_id);
            }
        });
    }
}


Main.update_system_information = function(system_id){

    var system_data = {
        "system_id" : system_id,
        "bypassexpirationupdate" : "1"
    };

    var xhr = $.ajax({
        type: "POST",
        url: "data/sections/main/real_time.php",
        data: system_data,
        dataType: "json",
        cache: false,
        beforeSend: function(xhr){},
        error: function(data){
            
            //Check expired session
            var session = new Session(data, '');
            
            if (session.check_session_expired() == true)
            {
                session.redirect();
                return;
            }
        },
        success: function(data){

            var row_id = '#row_' + system_id;

            //Remove load image
            $(row_id + ' .loading_status').remove();

            //Default values
            var status_class = 'st_unknown';
            var status_text  = labels['st_not_available'];
            var text_color   = '';

            var tr_type = 'tr_unknown';
            var td_type = 'td_unknown';

            var ha_status = '';

            var mem_used      = '0.00';
            var mem_vp_class  = 'progress-grey';

            var swap_used     = '0.00';
            var swap_vp_class = 'progress-grey';

            var cpu_load      = '0.00';
            var cpu_vp_class  = 'progress-grey';

            var su_info = '  --  ';

            //There is information about the system
            if (typeof(data) != 'undefined' && data != null && typeof(data.status) != 'undefined')
            {
                if (data.data['status'] == 'down')
                {
                    //System is down
                    status_class = 'st_down'
                    status_text  = labels['st_down'];
                    text_color   = 'red';

                    tr_type = 'tr_down';
                    td_type = 'td_down';
                }
                else if (data.data['status'] == 'up')
                {
                    //System is up
                    status_class = 'st_up';
                    status_text  = labels['st_up'];
                    text_color   = '';

                    tr_type = 'tr_up';
                    td_type = 'td_up';

                    ha_status = '';

                    if (typeof(data.data['ha_status']) != 'undefined' && data.data['ha_status'] != null)
                    {
                        if (data.data['ha_status'] == 'up')
                        {
                            ha_status = '<span class="green">'+labels['active_ha']+'</span>';
                        }
                        else
                        {
                            ha_status = '<span class="red">'+labels['passive_ha']+'</span>';
                        }
                    }

                    var mem_used      = Number(data.data['mem_used']).toFixed(2);
                    var mem_vp_class  = 'progress-blue';

                    var swap_used     = Number(data.data['swap_used']).toFixed(2);
                    var swap_vp_class = 'progress-orange';

                    var cpu_load      = Number(data.data['cpu_load']).toFixed(2);
                    var cpu_vp_class  = 'progress-green';
                }

                //System status

                //Update current status (table row)
                $(row_id).removeClass('tr_down tr_unknown tr_up').addClass(tr_type);
                $(row_id + ' td').removeClass('td_down td_unknown td_up').addClass(td_type);

                var status= "<div class='data_left'>\n" +
                                "<div class='" + status_class + "'></div>\n" +
                            "</div>\n" +
                            "<div class='data_right " + text_color + "'>\n" +
                                status_text +
                            "</div>\n" +
                            "<div class='data_clear'>\n" +
                                ha_status +
                            "</div>\n";

                $(row_id + ' .td_status').html(status);


                //Memory and CPU used

                $("#mem_used_vpbar_" + system_id +" .ui-vprogress").removeClass('progress-grey progress-blue').addClass(mem_vp_class);
                $("#swap_used_vpbar_" + system_id + " .ui-vprogress").removeClass('progress-grey progress-orange').addClass(swap_vp_class);
                $("#cpu_vpbar_"+system_id+" .ui-vprogress").removeClass('progress-grey progress-green').addClass(cpu_vp_class);


                VProgress_bar.update("mem_used_vpbar_" + system_id, mem_used, 500);
                VProgress_bar.update("swap_used_vpbar_" + system_id, swap_used, 500);
                VProgress_bar.update("cpu_vpbar_"+ system_id, cpu_load, 500);


                //Software updates

                if (data.data['update']['any_pending'] == true)
                {
                    var release_info = '';

                    if (data.data['update']['release_type'] != null && data.data['update']['release_version'] != null)
                    {
                        var color = (data.data['update']['release_type'] == labels['upgrade'] ) ? '#D8000C' : '#9F6000';

                        release_info = "<div id='lnk_ri' style='color: " + color + "; margin-bottom: 5px;'>" +
                                            data.data['update']['release_type'] + " " + data.data['update']['release_version'] +
                                       "</div>";
                    }

                    if (data.data['status'] == 'up')
                    {
                        su_info = "<a class='sw_pkg_pending'>" +
                                      release_info +
                                      "<img style='margin-right: 3px;' src='" + AV_PIXMAPS_DIR +"/down_arrow.png' alt='" + labels['new_updates'] +"')/>" +
                                  "</a>";

                        $(row_id + ' .td_su').html(su_info);
                    }
                    else
                    {
                        su_info = release_info + "<img style='margin-right: 3px;' src='" + AV_PIXMAPS_DIR +"/down_arrow.png' alt='" + labels['new_updates'] +"')/>";
                        
                        $(row_id + ' .td_su').html(su_info);
                    }
                }


                //Bind handler if the system is up

                if (data.data['status'] == 'up')
                {
                    $(row_id).css('cursor', 'pointer');
                    $(row_id).dblclick(function() {
                        Main.go_section(system_id, 'home');
                    });


                    $(row_id + ' .more_info').removeClass('disabled');

                    $(row_id + ' .more_info').off('click').click(function(){
                        Main.go_section(system_id, 'home');
                    });


                    $(row_id + ' .sw_pkg_pending').off('click').click(function(){
                        var config  = {
                            content: labels['ret_info'] + " ...",
                            style: 'width: 350px; top: 35%; padding: 5px 0px; left: 50%; margin-left: -175px;',
                            cancel_button: true
                        };

                        var height = $.getDocHeight();

                        var loading_box = Message.show_loading_box('s_box', config);

                        $('body').append('<div class="w_overlay" style="height:'+height+'px;"></div>');
                        $('body').append('<div class="l_box" style="display:none;">'+loading_box+'</div>');


                        parent.window.scrollTo(0, 0);
                        $('.l_box').show();
                        $('.l_box #cancel_loading').hide();

                        Main.go_section(system_id, 'sw_pkg_pending');
                    });
                }
                else
                {
                    $(row_id).css('cursor', 'default');
                    $(row_id).off('dblclick');
                    $(row_id + ' .more_info').addClass('disabled').off('click');
                }
            }
        }
    }); 

    ajax_requests.add_request(xhr);
}

/******************************************
*************** Section *******************
*******************************************/

function Section(data, id_section, rt)
{
    this.system_id          = data.system_id;
    this.host               = data.host;
    this.profiles           = data.profiles;
    this.current_section    = id_section;
    this.last_section       = id_section;
    this.request_cancelled  = false;
    this.active_r_time      = rt;

    //Enable Real Time
    this.real_time = function(){
        
        this.active_r_time = 1;
        
        switch (section.current_section)
        {
            case 'home':
                setTimeout(Home.real_time, 10000);
            break;

            case 'sw_pkg_installing':
                $('#soft_update_bar').AVactivity_bar();
                setTimeout(Software.install_updates_rt, 3000);
            break;

            default:
                section.stop_real_time();
        }
    };
    
    
    //Real Time
    //-1 -> Stopped beceause there is an error
    // 1 -> Active
    // 2 -> Stopped
    
    
    //Stop Real Time
    this.stop_real_time = function()
    {
        window.clearInterval(timer);
        window.clearTimeout(timer);
        section.active_r_time = 0;
    };
    

    //Stop Real Time because there is an error
    this.stop_real_time_by_error = function(code_error)
    {
        window.clearInterval(timer);
        window.clearTimeout(timer);
        section.active_r_time = code_error;
    };      
    
    
    //Actions executed before loading a section
    this.before_loading = function(id_section)
    {
        section.request_cancelled = false;
        window.clearInterval(timer);
        window.clearTimeout(timer);
                
        if (id_section == 'cnf_general' || id_section == 'cnf_network' || id_section == 'cnf_sensor')
        {
            Configuration.before_loading(id_section);
        }
        else if (id_section == 'sw_pkg_checking')
        {    
            Software.before_loading(id_section);
            $('#check_updates').removeClass();
            $('#check_updates').addClass('small');
            $('#check_updates').val(labels['check']);
        }
        else if (id_section == 'logs')
        {
            Log.before_loading(id_section);
        }
        else
        {
            $('.w_overlay').remove();
            
            if ($('.w_overlay').length < 1)
            {
                var height = $.getDocHeight();
                $('body').append('<div class="w_overlay" style="height:'+height+'px;"></div>');               
            }
                                                
            
            if ($('.l_box').length < 1)
            {
                var config  = {
                    content: avc_messages['retrieve_data'],
                    style: 'width: 350px; top: 35%; padding: 5px 0px; left: 50%; margin-left: -175px;',
                    cancel_button: false
                };  
                
                var loading_box = Message.show_loading_box('s_box', config);                    
                                
                $('body').append('<div class="l_box" style="display:none;">'+loading_box+'</div>');             
            }   
            else{
                $('.l_box .r_lp').html(avc_messages['retrieve_data']);
            }
            
                                                                        
            parent.window.scrollTo(0, 0);
            $('.l_box').show();
        }
    };  
    
    
    //Actions executed when section load request is success (HTML content not loaded yet)
    this.after_loading = function(id_section)
    {               
        $('#avc_actions').html('');  
        Js_tooltip.remove_all();                
        
        if (id_section == 'cnf_general' || id_section == 'cnf_network' || id_section == 'cnf_sensor')
        {
            Configuration.after_loading(id_section);
        }       
        else if (id_section == 'sw_pkg_pending' || id_section == 'sw_pkg_checking')
        {
            Software.after_loading(id_section);
        }
        else if (id_section == 'sw_pkg_installing')
        {
            Software.after_loading(id_section);
        }
        else if (id_section == 'logs')
        {
            Log.after_loading(id_section);
        }
        else
        {
            $('.l_box').remove();
            $('.w_overlay').remove();
        }
    };
    
    
    //Cancel section load request
    this.cancel_load = function(id_section)
    {
        $('#avc_actions').html('');
        section.request_cancelled = true;
        
        if (id_section == 'sw_pkg_pending' ||  id_section == 'sw_pkg_checking')
        {
            Software.cancel_load(id_section);
        }       
            
        //Retrieve last section because the current section is cancelled
        section.current_section = section.last_section;
        
        this.real_time(section.current_section);
    }; 
    
    
    //Load section
    this.load_section = function(id_section)
    { 
        //Change Control
        before_unload();
        
        section.last_section    = section.current_section;
        section.current_section = id_section;
                        
        //Special case: Check system update progress    
        if (section.current_section == 'sw_pkg_pending' || section.current_section == 'sw_pkg_installing')
        {
            if ($('.w_overlay').length < 1)
            {
                var height   = $.getDocHeight();
                  
                var config  = {
                    content: labels['sw_pending'] + " ...",
                    style: 'width: 350px; top: 35%; padding: 5px 0px; left: 50%; margin-left: -175px;',
                    cancel_button: false
                };  
                
                var loading_box = Message.show_loading_box('s_box', config);                    
                
                $('body').append('<div class="w_overlay" style="height:'+height+'px;"></div>');
                $('body').append('<div class="l_box" style="display:none;">'+loading_box+'</div>');
                                
                parent.window.scrollTo(0, 0);
                $('.l_box').show();
            }
            else
            {
                $('.l_box .r_lp').html(labels['sw_pending'] + " ...");
            }
                                    
            $.ajax({
                url: "data/sections/software/software_actions.php",
                global: false,
                type: "POST",
                data: "action=check_update_status" + "&system_id=" + section.system_id,
                dataType: "json",
                success: function(data){
                    
                    if (typeof(data) != 'undefined' && data != null)
                    {
                        section.current_section = data.data;            
                    }
                    
                    section.set_bc();
                    
                    }
                }
           );
        }
        else
        {
            section.set_bc();
        }
    };
    
    //Add extra parameters to url
    this.get_extra_data = function(id_section){
        return '';
    };
    
    //Create or update breadcrumb and load section
    this.set_bc = function(){
        
        //console.log(section);
        
        var query_string  = "host="+section.host+"&section="+section.current_section+"&system_id="+section.system_id;
        
        //Abort others requests
        ajax_requests.abort();
            
        var xhr = $.ajax({
            type: "POST",
            url: "data/sections/get_bc.php",
            data: query_string,
            dataType: 'json',
            beforeSend: function(xhr) {
                section.before_loading(section.current_section);
            },
            error: function(bc_data){

                //Check expired session
                var session = new Session(bc_data, '');
                if (session.check_session_expired() == true)
                {
                    session.redirect();
                    return;
                } 
                
                $('#avc_actions').html('');
                section.after_loading(section.current_section);
                $('.avc_hmenu').remove();
                                                
                var config_nt = { content: labels['unknown_error'], 
                    options: {
                        type:'nf_error',
                        cancel_button: false
                    },
                    style: 'width: 80%; text-align:center; padding: 5px 5px 5px 22px; margin: 100px auto;'
                };
                
                nt            = new Notification('nt_1', config_nt);
                notification  = nt.show();
                
                section.load_content(notification);
            },
            success: function(bc_data){
                                                
                if (typeof(bc_data) != 'undefined' && bc_data != null)
                {
                    if (bc_data.status == 'error')
                    {
                        section.after_loading(section.current_section);
                                                
                        var config_nt = { content: bc_data.data, 
                            options: {
                                type:'nf_error',
                                cancel_button: false
                            },
                            style: 'width: 80%; text-align:center; padding: 5px 5px 5px 22px; margin: 100px auto;'
                        };
                        
                        nt            = new Notification('nt_1', config_nt);
                        notification  = nt.show();
                        
                        section.load_content(notification);
                    }
                                        
                    //Breadcrumb request success --> Load section
                    if (bc_data.status == 'success')
                    {
                        section.current_section = bc_data.section;
                        
                        var parameters  = "system_id="+section.system_id+"&profiles="+ section.profiles+"&section="+section.current_section;
                        
                        var extra_data  = section.get_extra_data(section.current_section); 
                        
                        if (extra_data != '')
                            parameters +=  '&extra_data='+extra_data;
                        
                        var xhr_2 = $.ajax({
                            type: "POST",
                            url: "data/section.php",
                            data: parameters,                           
                            success: function(section_html){
                                                                
                                //Check expired session                
                                var session = new Session(section_html, '');                                                                                
                                
                                if (session.check_session_expired() == true)
                                {
                                    session.redirect();
                                    return;
                                }                                
                                
                                section.after_loading(section.current_section);
                                                                                        
                                //Section load request is not cancelled
                                if (section.request_cancelled == false)
                                {
                                    //Load Breadcrumb
                                    $('#bc_data').html(bc_data.data);
                                    $('#breadcrumbs').xBreadcrumbs({ collapsible: false }); 

                                    //Show Go Back link
                                    if ($('.go_back').length >= 1)
                                    {                                                                             
                                        $('.c_back_button').show();
                                                                          
                                        $('#lnk_go_back').off('click');
                                        
                                        $('#lnk_go_back').click(function(){
                                            $('.go_back').trigger('click');
                                        });
                                    }
                                    else
                                    {
                                        $('#lnk_go_back').off('click');
                                        $('.c_back_button').hide();
                                    }
                                    
                                    //Load section content
                                    section.load_content(section_html);
                                }
                            }
                        });
                    }
                    
                    ajax_requests.add_request(xhr_2);       
                }
            }
        });
        
        ajax_requests.add_request(xhr);
    };
    
    this.load_content = function(section_html){
                 
        var status = section_html.split("###");
              
        if (status[0] == "error")
        {
            var height = $('#avc_data').outerHeight();
            $('#avc_data').css('height', height);  
            section_html = status[1];
        }
                        
        $('#avc_data').html(section_html);  
        
        section.post_load();
    };
    
    this.post_load = function(){
        
        //Hide Tree
        if ($("#avc_cmcontainer img").hasClass('show'))
        {
            toggle_tree();
        }
                        
        section.real_time(); 
    };  
} 

/*******************************************************
*****************         Home          ****************
********************************************************/

function Home(){}

Home.after_loading = function(id_section){
    Home.hide_loading_box();
};

Home.hide_loading_box = function(){
    $('.l_box').remove();
    $('.w_overlay').remove();
}

Home.real_time = function(){

    if (section.current_section == 'home' && section.active_r_time == 1)
    {
        var system_data = {
            "system_id" : section.system_id,
            "id_section" : "home",
            "bypassexpirationupdate" : "1"
        };

        var xhr = $.ajax({
            type: "POST",
            data: system_data,
            url: "data/sections/common/real_time.php",
            dataType: "json",
            timeout: 50000,
            cache: false,
            error: function(data){
                //Check expired session
                var session = new Session(data, '');
                
                if (session.check_session_expired() == true)
                {
                    session.redirect();
                    return;
                }
            },
            success: function(data){

                //There is information about the system
                if (typeof(data) != 'undefined' && data != null && typeof(data.status) != 'undefined')
                {
                    if (data['status'] == 'error')
                    {
                        //System is down

                        if ($(".w_overlay").length < 1)
                        {
                            show_system_down();
                        }

                        //There is no a previous error
                        if ($('#t_status').length >= 1)
                        {
                            timer = setTimeout(function(){Home.real_time();}, 20000);

                            return false;
                        }
                        else
                        {
                            timer = setTimeout(function(){section.load_section('home');}, 30000);

                            return false;
                        }
                    }
                    else
                    {
                        //System is up

                        //Refresh general data
                        if (typeof(data.data['general_status']) != 'undefined' && data.data['general_status'] != null)
                        {
                            //There is no a previous error
                            if ($('#t_status').length >= 1)
                            {
                                //Memory RAM - Progress bar
                                var mr_percent_used = Number(data.data['general_status']['memory']['ram']['percent_used']).toFixed(2);
                                var mr_total        = bytes_to_size(data.data['general_status']['memory']['ram']['total']);
                                var mr_free         = bytes_to_size(data.data['general_status']['memory']['ram']['free']);
                                var mr_used         = bytes_to_size(data.data['general_status']['memory']['ram']['used']);

                                var ram_data = [mr_percent_used, mr_total, mr_free, mr_used];

                                System_status.set_pb_mem('r_memory_pbar', 'r_mem_data', ram_data);

                                //Memory RAM - Spark Line
                                r_memory_usage.push(mr_percent_used);
                                if (r_memory_usage.length >= 20)
                                {
                                   r_memory_usage.splice(0, 1);
                                }

                                $('#r_memory_spark_line').sparkline(r_memory_usage, { lineColor: '#444444', fillColor: '#6DC8E6', width:'160px', height: '18px', chartRangeMin: '0', chartRangeMax: '100'});

                                //Swap - Progress bar

                                var ms_percent_used = Number(data.data['general_status']['memory']['swap']['percent_used']).toFixed(2);
                                var ms_total        = bytes_to_size(data.data['general_status']['memory']['swap']['total']);
                                var ms_free         = bytes_to_size(data.data['general_status']['memory']['swap']['free']);
                                var ms_used         = bytes_to_size(data.data['general_status']['memory']['swap']['used']);

                                var ms_swap_data = [ms_percent_used, ms_total, ms_free, ms_used];

                                System_status.set_pb_mem('s_memory_pbar', 's_mem_data', ms_swap_data);

                                //Swap - Spark Line
                                s_memory_usage.push(ms_percent_used);
                                if (s_memory_usage.length >= 20)
                                {
                                    s_memory_usage.splice(0, 1);
                                }

                                $('#s_memory_spark_line').sparkline(s_memory_usage, { lineColor: '#444444', fillColor: '#E9B07A', width:'160px', height: '18px', chartRangeMin: '0', chartRangeMax: '100'});

                                //CPU - Progress bar

                                var cpu_data = [Number(data.data['general_status']['cpu']['load_average']).toFixed(2)];

                                System_status.set_pb_cpu('cpu_pbar', cpu_data);

                                //CPU - Spark Line
                                cpu_usage.push(data.data['general_status']['cpu']['load_average']);

                                if (cpu_usage.length >= 20)
                                {
                                    cpu_usage.splice(0, 1);
                                }

                                $('#cpu_spark_line').sparkline(cpu_usage, { lineColor: '#444444', fillColor: '#A3DB4E', width:'160px', height: '18px', chartRangeMin: '0', chartRangeMax: '100'});

                                //Others data
                                $('#la_data').html(data.data['general_status']['load_average']);
                                $('#rp_data').html(data.data['general_status']['process']['total']);
                                $('#cs_data').html(data.data['general_status']['sessions']['total']);
                            }
                        }

                        //Refresh network data
                        if (typeof(data.data['network_status']) != 'undefined' && data.data['network_status'] != null)
                        {
                            if ($('#t_network').length >= 1)
                            {
                                var img_tick  =  AVC_PIXMAPS_DIR+'/tick.png';
                                var img_cross =  AVC_PIXMAPS_DIR+'/cross.png'

                                var firewall_src   = (data.data['network_status']['firewall_active']== "yes")      ? img_tick : img_cross;
                                var vpn_access_src = (data.data['network_status']['vpn_access']== "yes")           ? img_tick : img_cross;
                                var inet_conn_src  = (data.data['network_status']['internet_connection'] == "yes") ? img_tick : img_cross;

                                $('#firewall').attr('src', firewall_src);
                                $('#vpn_access').attr('src', vpn_access_src);
                                $('#inet_conn').attr('src', inet_conn_src);

                                if (data.data['network_status']['dns_servers'] == '')
                                {
                                    $('#dns_servers').html("<img src='" + img_cross + "' alt='cross.png' align='absmiddle'/>")
                                }
                                else
                                {
                                    var dns_s_html = '<div>' + data.data['network_status']['dns_servers'].replace(',', '</div><div>') + '</div>';
                                    $('#dns_servers').html(dns_s_html);
                                }

                                $('#gateway').html(data.data['network_status']['gateway']);


                                //Interfaces
                                var ifaces = data.data['network_status']['interfaces'];

                                $.each(ifaces, function(iface_name, iface_data){

                                    var i_status = iface_data['status'];

                                    if (i_status == 'up')
                                    {
                                        $('#'+iface_name+'_status .iface_status').addClass('green');
                                        $('#'+iface_name+'_status img').attr('src', AVC_PIXMAPS_DIR+'/port_animado.gif');
                                    }
                                    else
                                    {
                                        $('#'+iface_name+'_status .iface_status').addClass('red');
                                        $('#'+iface_name+'_status img').attr('src', AVC_PIXMAPS_DIR+'/no_animado.gif');
                                    }

                                    $('#'+iface_name+'_status span').removeClass();
                                    $('#'+iface_name+'_tx_bytes').html(bytes_to_size(iface_data['tx_bytes']));
                                    $('#'+iface_name+'_rx_bytes').html(bytes_to_size(iface_data['rx_bytes']));
                                    $('#'+iface_name+'_status .iface_status').html(i_status.toUpperCase());

                                    if(typeof(iface_data['ipv4']) != 'undefined')
                                    {
                                        $('#'+iface_name+'_network').html(iface_data['ipv4']['network']);
                                        $('#'+iface_name+'_address').html(iface_data['ipv4']['address']);
                                        $('#'+iface_name+'_netmask').html(iface_data['ipv4']['netmask']);
                                    }
                                    else
                                    {
                                        $('#'+iface_name+'_network').html(' - ');
                                        $('#'+iface_name+'_address').html(' - ');
                                        $('#'+iface_name+'_netmask').html(' - ');
                                    }
                                });
                            }
                        }

                        if ($('#t_status').length >= 1 && $('#t_network').length >= 1)
                        {
                            show_system_up();

                            timer = setTimeout(function(){Home.real_time();}, 10000);
                        }
                        else
                        {
                            timer = setTimeout(function(){section.load_section('home');}, 15000);
                        }
                    }
                } 
            }
        }); 

        ajax_requests.add_request(xhr);

    }
};

Home.toggle_panel = function(id){
        
    var img_show = AVC_PIXMAPS_DIR+'/b_home_arrow.png';
    var img_hide = AVC_PIXMAPS_DIR+'/l_home_arrow.png';
    
    var p_container  = '#p_'+id;
    var pb_container = '#h_'+id;
        
    if ($(p_container + " .l_ph img").hasClass('show'))
    {
        $(pb_container).hide();
        $(p_container + " .l_ph img").attr('src', img_hide);
        $(p_container + " .l_ph img").removeClass();
        $(p_container + " .l_ph img").addClass('hide');
        $(p_container).css('border-bottom', 'solid 1px #BBBBBB');
    }
    else
    {
        $(pb_container).show();
        $(p_container + " .l_ph img").attr('src', img_show);
        $(p_container + " .l_ph img").removeClass();
        $(p_container + " .l_ph img").addClass('show');
        $(p_container).css('border-bottom', 'none');
    }
}

Home.load_panel = function(panel, force_request){
    
    var container       = '#h_'+panel;
    var p_container     = '#p_'+panel;
    var refresh         = '#h_'+panel+'_refresh';
    
    //Toggle panel
    var tg_container = '#tg_'+panel;
    $(tg_container).off();
    $(tg_container).click(function() { Home.toggle_panel(panel); });
                 
    var xhr = $.ajax({
        type: "POST",
        data: "system_id="+section.system_id +"&force_request="+force_request,
        url: "data/sections/home/"+panel+".php",
        cache: false,
        beforeSend: function(xhr) {
            
            //Showing panel, if it's hidden
            if ($(tg_container).hasClass('hide'))
            {
                Home.toggle_panel(panel);
            }

            var content = (force_request == 1) ? labels['ret_info_sec'] : labels['loading'];
            var width   = (force_request == 1) ? 'width: 280px; margin-left: -140px; ' : 'width: 250px; margin-left: -125px; ';
            
            var config  = {
                content: content + " ...",
                style: width + 'top: 40%; padding: 5px 0px; left: 50%;',
                cancel_button: false
            };

            $('.panel_body').css('border', 'solid 1px #C4C0BB');

            var loading_box = Message.show_loading_box(panel+'_box', config);
            
            var height = $(container).outerHeight();
                height = (height < 70) ? '150px' : height+'px';

            $(container).html("<div style='height:"+height+";'>"+loading_box+ "</div>");

            $(refresh).removeClass();
            $(refresh).addClass("disabled");
            $(refresh).off('click');
        },
        success: function(data){

            //Check expired session
            var session = new Session(data, '');
            
            if (session.check_session_expired() == true)
            {
                session.redirect();
                return;
            } 
            
            var status = data.split('###');
            
            if (status[0] == 'error')
            {
                var config_nt = { content: status[1], 
                                      options: {
                                        type:'nf_error',
                                        cancel_button: false
                                      },
                                      style: 'width: 40%; margin: 50px auto 50px auto;text-align:center;'
                                    };
                
                var id_nt     = 'nt_'+panel;
                nt            = new Notification(id_nt, config_nt);
                notification  = nt.show();
                $(container).html(notification);
                
                //System is down, Information is cached
                if (panel == 'system_status' && status[2] == 'system_down')
                {
                    show_system_down()
                }
            }
            else
            {                
                $(container).html(data);
                $('.panel_body').css('border', 'none');
            }

            GB_TYPE = 'w';
            $("a.grbox").off('click');
            $("a.grbox").click(function(event){
                event.preventDefault();
                var t = this.title || $(this).text() || this.href;
                parent.LB_show(t,this.href, 500, 700);
                return false;
            });         
                        
            $(refresh).removeClass("disabled");
            $(refresh).addClass("h_refresh");
            $(refresh).click(function() { Home.load_panel(panel, 1); });
        }
    }); 
        
    ajax_requests.add_request(xhr);
};


/*******************************************************
*********         Home - System Status          ********
********************************************************/

function System_status(){}

System_status.set_pb_mem = function(id_bar, id_data, data){
    Progress_bar.update(id_bar, data[0], 500);

    $('#'+id_data +' .total').html(data[1]);
    $('#'+id_data +' .free').html(data[2]);
    $('#'+id_data +' .used').html(data[3]);
};

System_status.set_pb_cpu = function(id_bar, data){
    Progress_bar.update(id_bar, data[0], 500);
};

System_status.get_last_percentage = function(id){
    var percentage = $('#'+id+' .value').text();
        percentage = percentage.replace(" %", "");

    return percentage;
};

System_status.show_pie = function(id, data){
    
    $.jqplot(id, data, {
        grid: {
            drawBorder: false, 
            drawGridlines: false,
            background: 'rgba(255,255,255,0)',
            shadow:false
        },
        seriesColors: ["#4BB2C5","#E9967A"],
        seriesDefaults:{
            renderer:$.jqplot.PieRenderer,
            rendererOptions: {
                diameter: '50',
                showDataLabels: true
                
            }
        },
        legend:{
            show:true,
            placement: 'outside',
            rendererOptions: {
                numberRows: 1
            },
            location:'s',
            marginTop:'-5px',
            marginBottom:'-3px'
        }
    }); 
};



/*******************************************************
***********         Home - Software          *********** 
********************************************************/
function Software(){}

//Reload Header (Number of updates)
Software.refresh_updates = function(){
   
    if (typeof(top.refresh_notifications) == 'function')
    {
        top.refresh_notifications();
    }
};

Software.before_loading = function(id_section){
    
    if ($('.w_overlay').length < 1)
    {
        var height   = $.getDocHeight();
          
        var config  = {
            content: labels['sw_update'] + " ...",
            style: 'width: 350px; top: 35%; padding: 5px 0px; left: 50%; margin-left: -175px;',
            cancel_button: true
        };  
        
        var loading_box = Message.show_loading_box('s_box', config);
        
        $('body').append('<div class="w_overlay" style="height:'+height+'px;"></div>');
        $('body').append('<div class="l_box" style="display:none;">'+loading_box+'</div>');
                
        $('.l_box #cancel_loading').off('click');
        $('.l_box #cancel_loading').click(function() { section.cancel_load(id_section); });
        
        parent.window.scrollTo(0, 0);
        $('.l_box').show();
    }
    else
    {
        $('.l_box .r_lp').html(labels['sw_update'] + " ...")
        $('.l_box #cancel_loading').show();
        $('.l_box #cancel_loading').off('click');
        $('.l_box #cancel_loading').click(function() { section.cancel_load(id_section); });
    }
};


Software.after_loading = function(id_section){
    Software.hide_loading_box();
};


Software.cancel_load = function(id_section){
        
    section.request_cancelled = true;
    
    $('#install_updates_1, #check_updates, #install_rules').off('click');
    
    if ($('#check_updates').length >= 1)
    {
        $('#check_updates').removeClass();
        $('#check_updates').addClass('small');
        $('#check_updates').val(labels['check']);
        $('#check_updates').click(function() {
            Software.check_updates();
        });   
    }
    
    if ($('#install_updates_1').length >= 1)
    {
        $('#install_updates_1').removeClass();
        $('#install_updates_1').addClass('big');
        $('#install_updates_1').val(labels['upgrade']);
        $('#install_updates_1').click(function() {
            Software.install_updates('update_system', 'install_updates_1');
        });   
    }
        
    if ($('#install_rules').length >= 1)
    {
        $('#install_rules').css('cursor', 'pointer');
        $('#install_rules').removeClass('opacity_2');
        $('.load_lnk').remove();
        $('#install_rules').click(function() {
            Software.install_updates('update_system_feed', 'install_rules');
        });   
    }
    
    Software.hide_loading_box();
}

Software.hide_loading_box = function(){
    $('.l_box').remove();
    $('.w_overlay').remove();
}


Software.install_updates = function(action, id){
    
    if (action == 'update_system')
    {
        var current_id = '#'+id;
        
        if ($('#c_release_info').length >= 1)
        {
            if (!confirm(labels['upgrade_system'])){
                return false;
            }
        }
    }
    else if (action == 'update_system_feed')
    {
        var current_id = '#install_rules';
    }
    else
    {
        var config_nt = { content: labels['invalid_action'], 
                          options: {
                            type:'nf_error',
                            cancel_button: false
                          },
                          style: 'width: 500px; text-align:center; margin:auto;'
                        };
                
        
        nt                = new Notification('nt_error', config_nt);
        var notification  = nt.show();
        
        $('.info_update').html(notification);
        $('.info_update').fadeIn(2000);
        setTimeout('$(".info_update").fadeOut(4000);', 30000);
                
        return;
    }   
    
    $.ajax({
        type: "POST",
        url: "data/sections/software/software_actions.php",
        cache: false,
        data: "action="+action+ "&system_id=" + section.system_id,
        dataType: 'json',
        beforeSend: function(xhr) {
            
            if (action == 'update_system')
            {
                $(current_id).val(labels['upgrading']);
                
                if (id == 'install_updates_1')
                {
                    $(current_id).removeClass();
                    $(current_id).addClass('av_b_processing');
                }
            }
            else
            {
                var content  = "<span class='load_lnk' style='margin-right: 5px;'><img src='"+AV_PIXMAPS_DIR+"/loading3.gif' title='"+labels['loading']+"' alt='"+labels['loading']+"'/></span>"
                    content +=  $(current_id).html();
                
                $(current_id).html(content);
                $(current_id).css('cursor', 'default');
                $(current_id).addClass('opacity_2');
            }
           
            $('#install_updates_1, #install_rules').off('click');
            
            var height = $.getDocHeight();
                                
            var config  = {
                content: labels['update_progress'] + " ...",
                style: 'width: 350px; top: 35%; padding: 5px 0px; left: 50%; margin-left: -175px;',
                cancel_button: false
            };  
    
            var loading_box = Message.show_loading_box('iu_box', config);
                                    
            if ($('.w_overlay').length < 1){        
                $('body').append('<div class="w_overlay" style="height:'+height+'px;"></div>');
            }
            
            $('body').append('<div class="l_box" style="display:none;">'+loading_box+'</div>');
                                    
            parent.window.scrollTo(0, 0);
            $('.l_box').show();
        },
        error: function(data){
            
            //Check expired session
                            
            var session = new Session(data, '');
            
            if (session.check_session_expired() == true)
            {
                session.redirect();
                return;
            } 
            
            var config_nt = { content: labels['unknown_error'], 
                              options: {
                                type:'nf_error',
                                cancel_button: false
                              },
                              style: 'width: 500px; text-align:center; margin:auto;'
                            };
                
            var id_nt         = 'nt_error';
            
            nt                = new Notification(id_nt, config_nt);
            var notification  = nt.show();
            
            $('.info_update').html(notification);
            $('.info_update').fadeIn(2000);
            setTimeout('$(".info_update").fadeOut(4000);', 30000);
            
            if (action == 'update_system')
            {               
                $(current_id).val(labels['upgrade']);
                
                if (id == 'install_updates_1')
                {
                    $(current_id).removeClass();
                    $(current_id).addClass('big');
                }
            }
            else
            {
                $(current_id).css('cursor', 'pointer');
                $(current_id).removeClass('opacity_2');
                $('.load_lnk').remove();
            }               
            
            $('#install_updates_1, #install_rules').off('click');
            
            $('#install_updates_1').click(function() {
                Software.install_updates('update_system', 'install_updates_1');
            }); 
                        
            $('#install_rules').click(function() {
                Software.install_updates('update_system_feed', 'install_rules');
            }); 

            Software.hide_loading_box();
        },
        success: function(data){
           
            if (data.status != 'success')
            {
                var nf_type   = 'nf_'+data.status;
                
                var config_nt = { content: data.data, 
                                  options: {
                                    type: nf_type,
                                    cancel_button: false
                                  },
                                  style: 'width: 500px; text-align:center; margin:auto;'
                                };
                
                var id_nt         = 'nt_error';
                nt                = new Notification(id_nt, config_nt);
                var notification  = nt.show();
                
                $('.info_update').html(notification);
                $('.info_update').fadeIn(2000);
                setTimeout('$(".info_update").fadeOut(4000);', 30000);
                
                if (action == 'update_system')
                {               
                    $(current_id).val(labels['upgrade']);
                    
                    if (id == 'install_updates_1')
                    {
                        $(current_id).removeClass();
                        $(current_id).addClass('big');
                    }
                }
                else
                {
                    $(current_id).css('cursor', 'pointer');
                    $(current_id).removeClass('opacity_2');
                    $('.load_lnk').remove();
                }

                $('#install_updates_1, #install_rules').off('click');

                $('#install_updates_1').click(function() {
                    Software.install_updates('update_system', 'install_updates_1');
                }); 

                $('#install_rules').click(function() {
                    Software.install_updates('update_system_feed', 'install_rules');
                }); 
                
                Software.hide_loading_box();
            }
            else
            {
                //Reload Header (Number of updates)
                Software.refresh_updates();
                section.load_section('sw_pkg_installing'); 
            }
        }
    });
};


Software.check_updates = function(){
    $('#check_updates').val(labels['checking']);
    $('#check_updates').removeClass();
    $('#check_updates').addClass('av_b_processing');
    $('#check_updates').off('click');
    section.load_section('sw_pkg_checking');
};



Software.install_updates_rt = function(){
    var xhr = $.ajax({
        type: "POST",
        data: "system_id="+section.system_id+"&id_section=sw_pkg_installing",
        url: "data/sections/common/real_time.php",
        dataType: "json",
        cache: false,
        error: function(data){
            
            //Check expired session
            var session = new Session(data, '');
                        
            if (session.check_session_expired() == true)
            {
                session.redirect();
                return;
            }    

            // Check apache void responses
            if (typeof(data) == 'object' && data.status == 0)
            {
                setTimeout('Software.install_updates_rt();', 5000);
            }
        },
        success: function(data){

            if (typeof(data) == 'undefined' || data == null)
            {
                // Considering data = null as a error response and re-schedule instead of stop
                setTimeout('Software.install_updates_rt();', 5000); //section.stop_real_time();
                return;
            }
            
            if (data.status == 'finished' || data.status == 'error')
            {
                ajax_requests.abort();
                section.stop_real_time();
                
                //Reload Header (Number of updates)
                Software.refresh_updates();
            }
            
            if (data.data != 'null' && data.data != null && data.data != '')
            {
                if (data.status == 'finished')
                {
                    
                    $('#soft_update_bar').remove();
                    
                    var content =  "<div id='s_upd_result'>" +
                                    "<div class='l_data_progress'><img class='bulb_green' src='"+AVC_PIXMAPS_DIR+"/light-bulb_green.png'/></div>" +
                                    "<div class='r_data_progress'><span>"+data.data+"</span></div>" +
                               "</div>";
                    
                    $('#system_changelog').html(content);

                }
                else if (data.status == 'error')
                {
                    
                    $('#soft_update_bar').remove();
                    
                    var content =  "<div id='s_upd_result'>" +
                                    "<div class='l_data_progress'><img class='bulb_red' src='"+AVC_PIXMAPS_DIR+"/light-bulb_red.png'/></div>" +
                                    "<div class='r_data_progress'><span>"+data.data+"</span></div>" +
                               "</div>";
                    
                    $('#system_changelog').html(content);

                }
                
            }
            
            if (section.current_section == 'sw_pkg_installing' && section.active_r_time == 1)
            {
                setTimeout('Software.install_updates_rt();', 5000);
            }
        }
    }); 

    ajax_requests.add_request(xhr);
};


/*******************************************************
*******         Home - Configuration          ********** 
********************************************************/

function Configuration(){}

Configuration.before_loading = function(id_section){
    
    if ($('.w_overlay').length < 1 && $('.w_overlay').length < 1)
    {
        var height   = $.getDocHeight();
          
        var config  = {
            content: labels['ret_info'] + " ...",
            style: 'width: 350px; top: 35%; padding: 5px 0px; left: 50%; margin-left: -175px;',
            cancel_button: false
        };  
        
        var loading_box = Message.show_loading_box('s_box', config);
        
        $('body').append('<div class="w_overlay" style="height:'+height+'px;"></div>');
        $('body').append('<div class="l_box" style="display:none;">'+loading_box+'</div>');
        
        parent.window.scrollTo(0, 0);
        $('.l_box').show();
    }
    else
    {
        $('.l_box .r_lp').html(labels['ret_info'] + " ...")
    }
};


Configuration.after_loading = function(id_section){
    Configuration.hide_loading_box();
};

Configuration.hide_loading_box = function(){
    $('.l_box').remove();
    $('.w_overlay').remove();
}

Configuration.check_reconfig_sync = function(){
    var ret = $.ajax({
        url: "data/sections/configuration/common_actions.php",
        global: false,
        type: "POST",
        data: "action=check_reconfig_status" +"&system_id="+section.system_id,
        dataType: "text",
        async:false
        }
   ).responseText;
    
    return ret;
};

//Check System Status (Reconfig in progress)
Configuration.check_status = function(){
    action_in_progress = true;
    
    $.ajax({
        type: "POST",
        url: "data/sections/configuration/common_actions.php",
        cache: false,
        data: "action=check_reconfig_status" +"&system_id="+section.system_id,
        beforeSend: function(xhr) {

            if ($('.w_overlay').length < 1)
            {
                $('.cnf_header').before('<div class="w_overlay opacity_7" style="height:100%"></div>');
            }

            if ($('.l_box').length >= 1)
            {
                $('.l_box .r_lp').html(labels['ret_info'] + " ...");
            }
            else
            {
                var config  = {
                    content: labels['check_status'] + " ...",
                    style: 'width: 320px; top: 34%; padding: 5px 0px; left: 50%; margin-left: -160px;',
                    cancel_button: false
                };

                var loading_box = Message.show_loading_box('s_box', config);

                            
                $(".w_overlay").before('<div class="l_box" style="display:none;">'+loading_box+'</div>');
                $('.l_box').show();
            }
        },
        error: function (data){
            
            action_in_progress = false;
            
            //Check expired session      
            var session = new Session(data, '');
            
            if (session.check_session_expired() == true)
            {
                session.redirect();
                return;
            }   
            
            timer = setTimeout(Configuration.check_status, 20000);
        },
        success: function(data){
                                    
            action_in_progress = false;
                                    
            $('.l_box').remove();
                        
            //No reconfig in progress
                            
            if (data == 0)
            {
                // Delete previous information
                if ($('#cnf_data').length >= 1) 
                {
                    $('#reconfig_content').remove();
                    $('#nt_rc').remove();
                    
                    $('#cnf_data_changed').show();
                    $('#cnf_data').show();
                }
                else
                {
                    $('#nt_rc').remove();
                    $(".w_overlay").remove();
                }
            }
            else
            {                                
                if ($('#reconfig_content').length < 1)
                {
                    $('#cnf_data').hide();
                    $('#nt_1').remove();
                    
                    var content = "<div id='reconfig_content' style='width: 500px;'>\n" + 
                                    "<div style='float: left; width: 35px;'><img src='"+AVC_PIXMAPS_DIR+"/reconfig_loader.gif' title='"+labels['loading']+"' alt='loading.gif'/></div>\n" + 
                                    "<div style='float: left; padding-top: 8px'>"+labels['rcnfg_executed']+"</div>\n" + 
                                  "</div>";
                                  
                    var config_nt = { 
                        content: content, 
                        options: {
                            type: 'nf_warning',
                            cancel_button: false
                        },
                        style: 'width: 100%; margin: auto;'
                    };
                
                    nt = new Notification('nt_rc',config_nt);
                    
                    
                    $('.c_info').append(nt.show());
                }
                else
                {
                    $('#nt_rc').show();
                }              
                
                timer = setTimeout(Configuration.check_status, 20000);
            }
        }
    });
}

/*******************************************************
*******     Home - General Configuration      ********** 
********************************************************/

function General_cnf(){}

General_cnf.check_reconfig_status = function(){
        
    var rs = Configuration.check_reconfig_sync();
    
    if (rs == 0)
    {
        $('#reconfig_content').remove();
        $('#cnf_data_changed').show();
        window.clearInterval(timer);
    }
};

General_cnf.apply_sc = function(msg, time, url){
    
    if ($('#nt_ch_ip').length < 1)
    {
        var content =   "<table id='t_apply_sc'>\n" +
                            "<tr>\n" +
                                "<td style='width: 30px;'><img src='"+AVC_PIXMAPS_DIR+"/reconfig_loader.gif'/></td>\n" +
                                "<td style='text-align: left;'>"+msg+"</td>\n" +
                            "</tr>\n" +
                        "</table>";
        
        var config_nt = { 
            content: content, 
            options: {
                type: 'nf_warning',
                cancel_button: false
            },
            style: 'display:none; width: 95%; margin: auto; padding-left: 5px;'
        };

        nt = new Notification('nt_ch_ip',config_nt);
            
        $('#gc_info').html(nt.show());
        nt.fade_in(1000);
        
        $('#link_new_ip').attr('href', url);
    }
    
    $('#c_time').html(time);
    
    var t = time - 1;
        
    if (t > 0)
    {       
        if (t == 3)
        {
            $('#c_new_ip').show();
        }
        
        timer = setTimeout(function(){General_cnf.apply_sc(msg, t, url)}, 1000);
    }
    else
    {
        if (typeof(window.parent) != 'undefined' && window.parent != null)
        { 
            window.parent.document.location.href = url;
        }
        else
        {
            document.location.href = url;
        }
        
        window.parent.document.location.href = url;
    }
};

General_cnf.save_cnf = function(form_id){    
    
    //Before sending
    
    $('#'+form_id).before('<div class="w_overlay opacity_7" style="height:100%;"></div>');
                                    
    var config  = {
        content: labels['apply_cnf'] + " ...",
        style: 'width: 280px; top: 34%; padding: 5px 0px; left: 50%; margin-left: -140px;',
        cancel_button: false
    };  

    var loading_box = Message.show_loading_box('s_box', config);    
    
    $(".w_overlay").before('<div class="l_box" style="display:none;">'+loading_box+'</div>');
    $('.l_box').show();
    
    
    //Saving data
    action_in_progress = true;
    
    var query_string = $('#'+form_id).serialize();
    
    //Special Case: Framework IP and/or Framework Hostname has changed
        
    var old_ip       = $('#h_admin_ip').val();
    var old_hostname = $('#h_hostname').val()   
        
    var new_ip       = $('#admin_ip').val();
    var new_hostname = $('#hostname').val()
            
    //New Framework IP
    var condition_1 = (section.profiles.match(/framework/gi) && new_ip != '' && new_ip != old_ip && old_ip == $('#server_addr').val());
    
    //New Framework hostname
    var condition_2 = (section.profiles.match(/framework/gi) && new_hostname != '' && old_hostname != new_hostname && old_ip == $('#server_addr').val());
    
    if (condition_1 || condition_2)   
    {
        var reconfig = Configuration.check_reconfig_sync();
        
        if (reconfig != 1)
        {
            var url = $('#server_url').val();
                url = url.replace('SERVER_IP', new_ip);
            
            General_cnf.apply_sc(labels['special_changes'], 45, url);
        }
    }   
        
    $.ajax({
        type: "POST",
        url: "data/sections/configuration/general/save_changes.php",
        cache: false,
        data: query_string + "&action=save_changes" + "&system_id=" + section.system_id,
        dataType: 'json',
        error: function (data){
            
            action_in_progress = false;
            
            //Check expired session      
            var session = new Session(data, '');            
            
            if (session.check_session_expired() == true)
            {
                session.redirect();
                return;
            }
                        
            var config_nt = { 
                content: labels['unknown_error'], 
                options: {
                    type: 'nf_error',
                    cancel_button: true
                },
                style: 'display: none; width: 90%; margin: 10px auto'
            };
        
            var nt = new Notification('nt_1',config_nt);
            var notification = nt.show();
                                           
            $('#gc_info').html(notification);             
                                   
            $('.l_box').remove();
            $(".w_overlay").remove();      
        },
        success: function(data){
                
            action_in_progress = false;
            
            //Special Case: Framework hostname or Framesork IP has changed
            if (!condition_1 && !condition_2) 
            {
                var nf_class = '';
                var nf_id    = '';
                                        
                switch (data.status)
                {
                    case 'error':
                        nf_class = 'nf_error';
                        nf_id    = 'nt_1'; 
                    break;
                    
                    case 'executing_reconfig':
                        nf_class = 'nf_warning';
                        nf_id    = 'nt_rc'; 
                    break;
                    
                    case 'success':
                        nf_class = 'nf_success';
                        nf_id    = 'nt_1'; 

                        //Set new values to check changes again
                        change_control.reset();

                        //Update Tree
                        if (typeof(tree) == 'object' && tree != null)
                        {
                            var order = $('#tree_ordenation').val();
                            tree.change_tree(order);
                        }
                            
                        //Update Breadcrumb
                        section.host = $('#hostname').val()+ " ["+$('#admin_ip').val()+"]";
                        $('#avc_home').text(section.host);
                        
                    break;
                }
            
                var config_nt = { 
                    content: data.data, 
                    options: {
                        type: nf_class,
                        cancel_button: true
                    },
                    style: 'display:none; width: 95%; margin: auto; padding-left: 5px;'
                };
    
                var nt = new Notification(nf_id, config_nt);
                var notification = nt.show();
                    
                $('#gc_info').html(notification);                
                
                $('#'+nf_id).fadeIn(1000);                    
                                                                                
                setTimeout("$('#nt_1').remove()", 5000);
                    
                $('.l_box').remove();
                $(".w_overlay").remove();
                
                if (data.status == 'success')
                {
                    Configuration.check_status();   
                }
            }
        }
    });
};

General_cnf.save_cnf_sync = function(form_id){
    
    //Special Case: Framework IP and/or Framework Hostname has changed (You can't change admin_ip or hostname synchronously)
        
    var old_ip       = $('#h_admin_ip').val();
    var old_hostname = $('#h_hostname').val()   
        
    var new_ip       = $('#admin_ip').val();
    var new_hostname = $('#hostname').val()
            
    //New Framework IP
    var condition_1 = (section.profiles.match(/framework/gi) && new_ip != '' && new_ip != old_ip && old_ip == $('#server_addr').val());
    
    //New Framework hostname
    var condition_2 = (section.profiles.match(/framework/gi) && new_hostname != '' && old_hostname != new_hostname && old_ip == $('#server_addr').val());
        
    if (condition_1 || condition_2)
    {
        return;
    }
    
    if (!confirm(labels['save_changes']+"\n\n"+labels['save']))
    {
        return;
    }
        
    //Show Loading Box
    $('#section_container').before('<div class="w_overlay opacity_7" style="height:100%;"></div>');
    
    if ($('.l_box').length >= 1)
    {
        $('.l_box .r_lp').html(labels['apply_cnf'] + " ...");
    }
    else
    {
        var config  = {
            content: labels['apply_cnf'] + " ...",
            style: 'width: 280px; top: 38%; padding: 5px 0px; left: 50%; margin-left: -140px;',
            cancel_button: false
        };  

        var loading_box = Message.show_loading_box('s_box', config);    
                            
        $(".w_overlay").before('<div class="l_box" style="display:none;">'+loading_box+'</div>');
        $('.l_box').show();
    }
        
    
    var ret = false;
    var query_string = $('#'+form_id).serialize();

    $.ajax({
            url: "data/sections/configuration/general/save_changes.php",
            global: false,
            type: "POST",
            data: query_string + "&action=save_changes&system_id=" + section.system_id,
            dataType: "json",
            async:false,
            success: function(data){
                ret = data;
            }
        }
   );
            
    if (ret == false)
    {
        //Check expired session
        var session = new Session(ret, ''); 
        
        if (session.check_session_expired() == true)
        {
            session.redirect();
            return;
        }   
    }
    else
    {
        var nf_class = '';
        var nf_id    = '';
        
        switch (ret.status)
        {
            case 'error':
                nf_class = 'nf_error';
                nf_id    = 'nt_1';
            break;
            
            case 'executing_reconfig':
                nf_class = 'nf_warning';
                nf_id    = 'nt_rc';
            break;
            
            case 'success':
                nf_class = 'nf_success';
                nf_id    = 'nt_1';
                
                //Update Tree
                if (typeof(tree) == 'object' && tree != null)
                {
                    var order = $('#tree_ordenation').val();
                    tree.change_tree(order);
                }
                
                //Update Breadcrumb
                section.host = $('#hostname').val()+ " ["+$('#admin_ip').val()+"]";
                $('#avc_home').text(section.host);
            
            break;
        }

        var config_nt = { 
                content: ret.data, 
                options: {
                    type: nf_class,
                    cancel_button: true
                },
                style: 'width: 95%; margin: auto; padding-left: 5px;'
            };
    
             
        var nt = new Notification(nf_id, config_nt);
        var notification = nt.show();
            
        $('#gc_info').html(notification);        
                                             
        $('.l_box').remove();
        $(".w_overlay").remove();
        
        sleep(1000);
    }   
};



/*******************************************************
*******     Home - Network Configuration      ********** 
********************************************************/

function Network_cnf(){}

Network_cnf.check_reconfig_status = function(){
        
    var rs = Configuration.check_reconfig_sync();
    
    if (rs == 0)
    {
        $('#reconfig_content').remove();
        $('#cnf_data_changed').show();
        window.clearInterval(timer);
    }
};

Network_cnf.apply_sc = function(msg, time, url){
    
    if ($('#nt_ch_ip').length < 1)
    {
        var content =   "<table id='t_apply_sc'>\n" +
                            "<tr>\n" +
                                "<td style='width: 30px;'><img src='"+AVC_PIXMAPS_DIR+"/reconfig_loader.gif'/></td>\n" +
                                "<td style='text-align: left;'>"+msg+"</td>\n" +
                            "</tr>\n" +
                        "</table>";
        
        var config_nt = { 
            content: content, 
            options: {
                type: 'nf_warning',
                cancel_button: false
            },
            style: 'display:none; width: 95%; margin: auto; padding-left: 5px;'
        };

        var nt = new Notification('nt_ch_ip',config_nt);
        var notification = nt.show();
        
            
        $('#nc_info').html(notification);
        nt.fade_in(1000);
        
        $('#link_new_ip').attr('href', url);
    }
    
    $('#c_time').html(time);
    
    var t = time - 1;
        
    if (t > 0){
        
        if (t == 3){
            $('#c_new_ip').show();
        }
        
        timer = setTimeout(function(){General_cnf.apply_sc(msg, t, url)}, 1000);
    }
    else
    {
        if (typeof(window.parent) != 'undefined' && window.parent != null)
        { 
            window.parent.document.location.href = url;
        }
        else
        {
            document.location.href = url;
        }
    }
};


Network_cnf.save_cnf = function(form_id){
    
    //Before sending
    
    $('#'+form_id).before('<div class="w_overlay opacity_7" style="height:100%;"></div>');
                                
    var config  = {
        content: labels['apply_cnf'] + " ...",
        style: 'width: 280px; top: 38%; padding: 5px 0px; left: 50%; margin-left: -140px;',
        cancel_button: false
    };  

    var loading_box = Message.show_loading_box('s_box', config);
            
    $(".w_overlay").before('<div class="l_box" style="display:none;">'+loading_box+'</div>');
    $('.l_box').show();
    
    
    //Saving data
    action_in_progress = true;
    
    var query_string = $('#'+form_id).serialize();
    
    //Special Case: Framework IP has changed
    var old_ip = $('#h_admin_ip').val();
    var new_ip = $('#admin_ip').val();
                
    //New Framework IP
    var condition_1 = (section.profiles.match(/framework/gi) && new_ip != '' && new_ip != old_ip && old_ip == $('#server_addr').val());
        
    if (condition_1)  
    {
        var reconfig = Configuration.check_reconfig_sync();
        
        if (reconfig != 1)
        {
            var url = $('#server_url').val();
                url = url.replace('SERVER_IP', new_ip);
            
            Network_cnf.apply_sc(labels['special_changes'], 45, url);
        }
    }   
    
    
    $.ajax({
        type: "POST",
        url: "data/sections/configuration/network/save_changes.php",
        cache: false,
        data: query_string + "&action=save_changes" + "&system_id=" + section.system_id,
        dataType: 'json',
        error: function (data){
            
            action_in_progress = false;
            
            //Check expired session      
            var session = new Session(data, '');            
            
            if (session.check_session_expired() == true)
            {
                session.redirect();
                return;
            }  
            
            var config_nt = { 
                content: labels['unknown_error'], 
                options: {
                    type: 'nf_error',
                    cancel_button: true
                },
                style: 'display: none; width: 90%; margin: 10px auto'
            };
        
            var nt = new Notification('nt_1',config_nt);
            var notification = nt.show();
                                                                
            $('#nc_info').html(notification);             
                                   
            $('.l_box').remove();
            $(".w_overlay").remove();
        },
        success: function(data){
            
            action_in_progress = false;
                                
            if (!condition_1) 
            {
                var nf_class = '';
                var nf_id    = '';

                switch (data.status)
                {
                    case 'error':
                        nf_class = 'nf_error';
                        nf_id    = 'nt_1';
                    break;
                    
                    case 'executing_reconfig':
                        nf_class = 'nf_warning';
                        nf_id    = 'nt_rc';
                    break;
                    
                    case 'success':
                        nf_class = 'nf_success';
                        nf_id    = 'nt_1';

                        //Set new values to check changes again
                        change_control.reset();

                         //Update Tree
                        if (typeof(tree) == 'object' && tree != null)
                        {
                            var order = $('#tree_ordenation').val();
                            tree.change_tree(order);
                        }
                            
                        //Update Breadcrumb
                        section.host = $('#hostname').val()+ " ["+$('#admin_ip').val()+"]";
                        $('#avc_home').text(section.host);

                    break;
                }
            
                var config_nt = { 
                        content: data.data, 
                        options: {
                            type: nf_class,
                            cancel_button: true
                        },
                        style: 'display:none; width: 95%; margin: auto; padding-left: 5px;'
                    };
            
                var nt = new Notification(nf_id, config_nt);
                var notification = nt.show();
                                    
                $('#nc_info').html(notification);
                
                $('#'+nf_id).fadeIn(1000);
                setTimeout("$('#nt_1').remove()", 5000);
                    
                $('.l_box').remove();
                $(".w_overlay").remove();
                
                if (data.status == 'success')
                {
                    Configuration.check_status();   
                }
            }
        }
    });
};

Network_cnf.save_cnf_sync = function(form_id){
    
    //Special Case: Framework IP has changed (You can't change admin_ip synchronously)
    var old_ip = $('#h_admin_ip').val();
    var new_ip = $('#admin_ip').val();
                
    //New Framework IP
    var condition_1 = (section.profiles.match(/framework/gi) && new_ip != '' && new_ip != old_ip && old_ip == $('#server_addr').val());
    
    if (condition_1)
    {
        return;
    }
            
    if (!confirm(labels['save_changes']+"\n\n"+labels['save']))
    {
        return;
    }
        
    //Show Loading Box
    $('#section_container').before('<div class="w_overlay opacity_7" style="height:100%;"></div>');
    
    if ($('.l_box').length >= 1)
    {
        $('.l_box .r_lp').html(labels['apply_cnf'] + " ...");
    }
    else
    {
        var config  = {
            content: labels['apply_cnf'] + " ...",
            style: 'width: 280px; top: 38%; padding: 5px 0px; left: 50%; margin-left: -140px;',
            cancel_button: false
        };  

        var loading_box = Message.show_loading_box('s_box', config);    
                            
        $(".w_overlay").before('<div class="l_box" style="display:none;">'+loading_box+'</div>');
        $('.l_box').show();
    }
            
    
    var ret  = false;
    var query_string = $('#'+form_id).serialize();

    $.ajax({
            url: "data/sections/configuration/network/save_changes.php",
            global: false,
            type: "POST",
            data: query_string + "&action=save_changes&system_id=" + section.system_id,
            dataType: "json",
            async:false,
            success: function(data){
                ret = data;
            }
        }
   );
    
    if (ret == false)
    {
        //Check expired session
        var session = new Session(ret, ''); 
        
        if (session.check_session_expired() == true)
        {
            session.redirect();
            return;
        }   
    }
    else
    {
        var nf_class = '';
        var nf_id    = '';

        switch (ret.status)
        {
            case 'error':
               nf_class = 'nf_error';
               nf_id    = 'nt_1';
            break;
            
            case 'executing_reconfig':
               nf_class = 'nf_warning';
               nf_id    = 'nt_rc';
            break;
            
            case 'success':
               nf_class = 'nf_success';
               nf_id    = 'nt_1';
               
               
            //Update Tree
            if (typeof(tree) == 'object' && tree != null)
            {
                var order = $('#tree_ordenation').val();
                tree.change_tree(order);
            }
            
            //Update Breadcrumb
            section.host = $('#hostname').val()+ " ["+$('#admin_ip').val()+"]";
            $('#avc_home').text(section.host);              
               
            break;
        }
    
        var config_nt = { 
                content: ret.data, 
                options: {
                    type: nf_class,
                    cancel_button: true
                },
                style: 'display:none; width: 95%; margin: auto; padding-left: 5px;'
            };
    
        var nt = new Notification(nf_id, config_nt);
        var notification = nt.show();
        
        $('#nc_info').html(notification);
                
        nt.fade_in(1000);
        setTimeout('nt.remove();', 5000);
        
        $('.l_box').remove();
        $(".w_overlay").remove();
        
        sleep(1000);
    }   
};


/*******************************************************
*******     Home - Sensor Configuration      ********** 
********************************************************/

function Sensor_cnf(){}

Sensor_cnf.check_reconfig_status = function(){
        
    var rs = Configuration.check_reconfig_sync();
    
    if (rs == 0)
    {
        $('#reconfig_content').remove();
        $('#cnf_data_changed').show();
        window.clearInterval(timer);
    }
};


//Save Sensor Configuration (Apply Changes)
Sensor_cnf.save_cnf = function(form_id){
    
    //Before sending
    
    $('.cnf_header').before('<div class="w_overlay opacity_7" style="height:100%;"></div>');
                                
    var config  = {
        content: labels['apply_cnf'] + " ...",
        style: 'width: 280px; top: 38%; padding: 5px 0px; left: 50%; margin-left: -140px;',
        cancel_button: false
    };  

    var loading_box = Message.show_loading_box('s_box', config);    
                    
    $(".w_overlay").before('<div class="l_box" style="display:none;">'+loading_box+'</div>');
    $('.l_box').show();    
    
    //Saving data
    action_in_progress = true;
    
    var query_string = $('#'+form_id).serialize();
    
    $.ajax({
        type: "POST",
        url: "data/sections/configuration/sensor/save_changes.php",
        cache: false,
        data: query_string + "&action=save_changes" + "&system_id=" + section.system_id,
        dataType: 'json',
        
        error: function (data){
            
            action_in_progress = false;
            
            //Check expired session      
            var session = new Session(data, '');            
            
            if (session.check_session_expired() == true)
            {
                session.redirect();
                return;
            }
            
            var config_nt = { 
                content: labels['unknown_error'], 
                options: {
                    type: 'nf_error',
                    cancel_button: true
                },
                style: 'display: none; width: 90%; margin: 10px auto'
            };
        
            var nt = new Notification('nt_1',config_nt);
            var notification = nt.show();
                                                      
            $('#sc_info').html(notification);             
                                   
            $('.l_box').remove();
            $(".w_overlay").remove(); 
        },
        success: function(data){
            
            action_in_progress = false;
            
            var nf_class = '';
            var nf_id    = '';

            switch (data.status)
            {
                case 'error':
                    nf_class = 'nf_error';
                    nf_id    = 'nt_1';
                break;
                
                case 'executing_reconfig':
                    nf_class = 'nf_warning';
                    nf_id    = 'nt_rc';
                break;
                
                case 'success':
                    nf_class = 'nf_success';
                    nf_id    = 'nt_1';

                    //Set new values to check changes again
                    change_control.reset();

                break;
            }
            
            var config_nt = { 
                    content: data.data,
                    options: {
                        type: nf_class,
                        cancel_button: true
                    },
                    style: 'display:none; width: 95%; margin: auto; padding-left: 5px;'
                };
        
            var nt = new Notification('nt_1',config_nt);
            
            var notification = nt.show();

            $('#sc_info').html(notification);
                        
            $('#'+nf_id).fadeIn(1000);
            
            setTimeout("$('#nt_1').remove()", 5000);
            
            $('.l_box').remove();
            $(".w_overlay").remove();
            
            if (data.status == 'success')
            {
                Configuration.check_status();   
            }            
        }
    });
};


Sensor_cnf.save_cnf_sync = function(form_id){
        
    if (!confirm(labels['save_changes']+"\n\n"+labels['save']))
    {
        return;
    }
    
    //Show Loading Box
    $('#section_container').before('<div class="w_overlay opacity_7" style="height:100%;"></div>');
    
    if ($('.l_box').length >= 1)
    {
        $('.l_box .r_lp').html(labels['apply_cnf'] + " ...");
    }
    else
    {
        var config  = {
            content: labels['apply_cnf'] + " ...",
            style: 'width: 280px; top: 38%; padding: 5px 0px; left: 50%; margin-left: -140px;',
            cancel_button: false
        };  

        var loading_box = Message.show_loading_box('s_box', config);    
                            
        $(".w_overlay").before('<div class="l_box" style="display:none;">'+loading_box+'</div>');
        $('.l_box').show();
    }
            
    var ret  = false;
    var query_string = $('#'+form_id).serialize();

    
    $.ajax({
        url: "data/sections/configuration/sensor/save_changes.php",
        global: false,
        type: "POST",
        data: query_string + "&action=save_changes&system_id=" + section.system_id,
        dataType: "json",
        async:false,
        success: function(data){
            ret = data;
        }
    });
       
    
    if (ret == false)
    {
        //Check expired session
        var session = new Session(ret, '');
            
        if (session.check_session_expired() == true)
        {
            session.redirect();
            return;
        }   
    }
    else
    {
        var nf_class = '';
        var nf_id    = '';

        switch (ret.status)
        {
            case 'error':
               nf_class = 'nf_error';
               nf_id    = 'nt_1';
            break;
            
            case 'executing_reconfig':
               nf_class = 'nf_warning';
               nf_id    = 'nt_rc';
            break;
            
            case 'success':
               nf_class = 'nf_success';
               nf_id    = 'nt_1';
            break;
        }
    
        var config_nt = { 
                content: ret.data, 
                options: {
                    type: nf_class,
                    cancel_button: true
                },
                style: 'display:none; width: 95%; margin: auto; padding-left: 5px;'
            };
    
        var nt = new Notification(nf_id, config_nt);
        var notification = nt.show();
                                                      
        $('#sc_info').html(notification);        
       
        nt.fade_in(1000);
        setTimeout('nt.remove();', 5000);
        
        $('.l_box').remove();
        $(".w_overlay").remove();
                        
        sleep(1000);
    }   
};


/*******************************************************
**********            Home - Logs             ********** 
********************************************************/

function Log(){}

Log.before_loading = function(id_section){
    
    if ($('.w_overlay').length < 1)
    {
        var height   = $.getDocHeight();
          
        var config  = {
            content: labels['ret_info'] + " ...",
            style: 'width: 350px; top: 34%; padding: 5px 0px; left: 50%; margin-left: -175px;',
            cancel_button: false
        };  
        
        var loading_box = Message.show_loading_box('s_box', config);                    
        
        $('body').append('<div class="w_overlay" style="height:'+height+'px;"></div>');
        $('body').append('<div class="l_box" style="display:none;">'+loading_box+'</div>');
        
        parent.window.scrollTo(0, 0);
        $('.l_box').show();
    }
    else
    {
        $('.l_box .r_lp').html(labels['ret_info'] + " ...");
    }
};


Log.after_loading = function(id_section){
    Log.hide_loading_box();
};


Log.hide_loading_box = function(){
    $('.l_box').remove();
    $('.w_overlay').remove();
};


Log.create_viewer = function(id){
        
    editor = CodeMirror.fromTextArea(document.getElementById(id), {
        mode: {name: "properties"},
        lineNumbers: true,
        lineWrapping: true,
        readOnly: true,
        keyMap: 'log_viewer',
        onCursorActivity: function() {
            editor.setLineClass(hlLine, null);
            hlLine = editor.setLineClass(editor.getCursor().line, "activeline");
            }
        });
};


Log.view_logs = function(log_id){
    
    var num_rows = $('#num_rows').val();
    
    $.ajax({
        type: "POST",
        data: "action=view_log&log_id="+log_id+"&num_rows="+num_rows + "&system_id=" + section.system_id+"&profiles=" + section.profiles,
        dataType: 'json',
        url: "data/sections/logs/log_actions.php",
        cache: false,
        beforeSend: function(xhr) {
                        
            $('.log_name').removeClass('bold');  
            $('#'+log_id).addClass('bold');  
                        
            var config  = {
                content: labels['ret_log'] + " ...",
                style: 'width: 300px; top: 38%; padding: 5px 0px; left: 50%; margin-left: -150px;',
                cancel_button: false
            };  

            var loading_box = Message.show_loading_box('s_box', config);    
            
            $('#log_viewer').append('<div class="w_overlay opacity_7" style="height:100%;"></div>');
            $("#log_viewer").append('<div class="l_box" style="display:none;">'+loading_box+'</div>');
            $('.l_box').show();
        },
        error: function (log_data){
            
            //Check expired session      
            var session = new Session(log_data, '');
                        
            if (session.check_session_expired() == true)
            {
                session.redirect();
                return;
            } 
                        
            var config_nt = { 
                content: labels['unknown_error'], 
                options: {
                    type: 'nf_error',
                    cancel_button: true
                },
                style: 'display: none; width: 90%; margin: 10px auto'
            };
        
            var nt = new Notification('nt_1',config_nt);
            
            var info = "<div id='log_info'>"+nt.show()+"</div>";
            
            $('#container_li').html(info);
            nt.fade_in(1000);
            setTimeout('$("#nt_1").fadeOut(3000); $("#log_info").remove();', 10000);
            
            $('.l_box').remove();
            $('.w_overlay').remove();
        },
        success: function(log_data){
                        
            if (log_data.status == 'success')
            {
                if (typeof(log_data.data) != 'undefined')
                {
                    editor.setValue(log_data.data);
                    hlLine = editor.setLineClass(0, "activeline");
                }
                                
                //Adding action buttons
                $("#l_viewer li").css("cursor", "pointer");
                $("#l_viewer img").removeClass("disabled");
                
                //Adding tooltip button 
                var search_content =    "<div id='log_info_search'>" +                  
                                        "<div style='list-style-type: none; margin-left: 10px; font-size: 10px; width: 250px;'>" +
                                            "<div>Ctrl-F / Cmd-F: "+ labels['start_searching'] +"</div>" +
                                            "<div>Ctrl-G / Cmd-G: "+ labels['find_next'] +"</div>" +
                                            "<div>Shift-Ctrl-G / Shift-Cmd-G: "+ labels['find_previous'] +"</div>" +
                                        "</div>" +
                                    "</div>";
                
                var config_t = '';
                
                config_t = { content: search_content};
                Js_tooltip.show('#li_search', config_t);
                
                config_t = { content: labels['find_previous']};
                Js_tooltip.show('#li_find_previous', config_t);
                
                config_t = { content: labels['find_next']};
                Js_tooltip.show('#li_find_next', config_t);
                
                config_t = { content: labels['clear_search']};
                Js_tooltip.show('#li_clear_search', config_t);
                
                
                $('#li_search').click(function(){ CodeMirror.commands["find"](editor); });
                $('#li_find_previous').click(function(){ CodeMirror.commands["findPrev"](editor); });
                $('#li_find_next').click(function(){ CodeMirror.commands["findNext"](editor); });
                $('#li_clear_search').click(function(){ CodeMirror.commands["clearSearch"](editor); });
            }
            else
            {
                var config_nt = { 
                    content: log_data.data, 
                    options: {
                        type: 'nf_error',
                        cancel_button: true
                    },
                    style: 'display: none; width: 90%; margin: 10px auto;'
                };
        
                var nt = new Notification('nt_1',config_nt);
                
                var info = "<div id='log_info'>"+nt.show()+"</div>";
                
                $('#container_li').html(info);
                nt.fade_in(1000);
                setTimeout('$("#nt_1").fadeOut(3000); $("#log_info").remove();', 10000);
            }
            
            $('.l_box').remove();
            $('.w_overlay').remove();           
        }
    });
};

