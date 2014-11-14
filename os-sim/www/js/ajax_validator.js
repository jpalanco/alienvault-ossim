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


String.prototype.trim = function() { return this.replace(/^\s+|\s+$/g, ""); }
String.prototype.stripTags = function() { return this.replace(/<[^>]+>/g,'');} 

//AJAX validator
var ajax_validator = null;

function Ajax_validator(new_config)
{    
    //Private Attributes 
    
    var config;
    
    var errors;
    
    var form_elements;
    
    _initialize(new_config);
    
        
    /******************************************************
    *****************  Public Functions  ******************
    *******************************************************/
        
    
    this.get_config = function() {
        return config;
    };
    
    
    this.get_errors = function() {
        return errors;
    };
    
    
    this.set_config = function (new_config) {
        _set_config(new_config);
    };
    
    
    this.validate_field = function(name)
    {
        return _validate_field(name);
    };
    
        
    this.check_form = function()
    {
        return _check_form();
    };
    
    this.validate_all_fields = function()
    {
        return _validate_all_fields();
    };
        
    this.reset = function(){
        _clear_array_errors();
        _remove_all_html_errors();
    }
        
    this.submit_form = function()
    {
        if (_check_form() == true)
        {
            $('#'+config.form.id).submit(); 
            
            return true;
        }
        else
        {
            if ($(".invalid").length >= 1) 
            {
                try
                {
                    $(".invalid").get(0).focus();
                }
                catch(err) 
                {
                    ;
                }
            }
            
            return false;
        }
    };
        
    
    /******************************************************
    *****************  Private Functions  *****************
    *******************************************************/
    
    function _e2unicode(str) 
    {
        return str.replace(/&#(\d+);/g, function(match,content) {
            code_pt = Number(content); 
            if (code_pt > 0xFFFF) 
            {
              code_pt -= 0x10000;
              
              return String.fromCharCode(0xD800 + (code_pt >> 10), 0xDC00 + (code_pt & 0x3FF));
            }
            
            return String.fromCharCode(code_pt);   
        });
    }
    
    function _initialize(new_config)
    {
        _set_config(new_config);
                
        form_elements = new Array();
        
        _clear_array_errors();
        
        if (config.errors.display_errors == 'all' || config.errors.display_errors == 'field-errors')
        {
            $('#'+config.form.id +' .vfield').each(function(index, value) {
                
                var tag_name = value.tagName.toUpperCase();
                var tag_type = value.type.toUpperCase();

                form_elements[value.name] = {'tag_name' : tag_name, 'tag_type' : tag_type};
                                
                if(tag_name == 'INPUT' && (tag_type == 'HIDDEN' || tag_type == 'CHECKBOX' || tag_type == 'RADIO'))
                {
                    $(this).off('change', _validate_field);
                    
                    if (config.validation_type == 'complete' && typeof(value.name) != 'undefined')
                    {
                        $(this).change(function() { _validate_field(value.name); }); 
                    
                    }
                }
                else if (tag_name == 'SELECT')
                {
                    $(this).off('change', _validate_field);
                    
                    if (config.validation_type == 'complete' && typeof(value.name) != 'undefined')
                    {
                        $(this).change(function() { _validate_field(value.name); }); 
                    }
                }
                
                $(this).off('blur', _validate_field);
                if (config.validation_type == 'complete' && typeof(value.name) != 'undefined')
                {
                    $(this).blur(function() { _validate_field(value.name); }); 
                }
            });         
        }
                        
        
        Ajax_validator.instance = this;
        
        if (typeof(Ajax_validator.instance) != 'undefined')
        {
            return Ajax_validator.instance;
        }
    };
        
    function _check_config()
    {
        if (typeof(config.validation_type) == 'undefined' || config.validation_type == '')
        {
            var error = new Error();
                error.name    = 'TypeError';
                error.message = "Configuration error: Option 'validation_type' is empty or undefined";
            
            throw error;
        }
        
        if (typeof(config.errors.display_errors) == 'undefined' || config.errors.display_errors == '')
        {
            var error = new Error();
                error.name    = 'TypeError';  
                error.message = "Configuration error: Option 'errors.display_errors' is empty or undefined";
            
            throw error;
        }
        
        if ((typeof(config.errors.display_in) == 'undefined' || config.errors.display_in == '') && display_errors != 'field-errors')
        {
            var error = new Error();
                error.name    = 'TypeError';  
                error.message = "Configuration error: Option 'errors.display_in' is empty or undefined";
            
            throw error;
        }
        
        if (typeof(config.form.id) == 'undefined' || config.form.id == '')
        {
            var error = new Error();
                error.name    = 'TypeError';  
                error.message = "Configuration error: Option 'form.id' is empty or undefined";
            
            throw error;
        }
        
        if (typeof(config.form.url) == 'undefined' || config.form.url == '')
        {
                                    
            var error = new Error();
                error.name    = 'TypeError';  
                error.message = "Configuration error: Option 'form.url' is empty or undefined";
            
            throw error;
        }
                    
        if (typeof(config.actions) != 'undefined')
        {
            if (typeof(config.actions.on_submit) != 'undefined')
            {
                if (typeof(config.actions.on_submit.id) == 'undefined' || config.actions.on_submit.id == '')
                {
                    var error = new Error();
                    error.name    = 'TypeError';  
                    error.message = "Configuration error: Option 'actions.on_submit.id' is empty or undefined";
                
                    throw error;
                }
                
                if (typeof(config.actions.on_submit.success) == 'undefined' || config.actions.on_submit.success == '')
                {
                    var error = new Error();
                    error.name    = 'TypeError';  
                    error.message = "Configuration error: Option 'actions.on_submit.success' is empty or undefined";
                
                    throw error;
                }
                
                if (typeof(config.actions.on_submit.checking) == 'undefined' || config.actions.on_submit.checking == '')
                {
                    var error = new Error();
                    error.name    = 'TypeError';  
                    error.message = "Configuration error: Option 'actions.on_submit.checking' is empty or undefined";
                
                    throw error;
                }
            }
        }
    }
    
    function _set_config(new_config)
    {        
        //Set default configuration
        if (typeof(new_config) != 'object' || new_config == '')
        {                   
            var default_config = {   
                validation_type: 'complete', // single|complete
                errors:{
                    display_errors: 'all', //  all | summary | field-errors
                    display_in: 'info_error'
                },
                form : {
                    id: $('form[method="post"]').attr("id"),
                    url : $('form[method="post"]').attr("action")
                },
                actions: {
                    on_submit:{
                        id: 'send',
                        success:  av_messages['submit_text'],
                        checking: av_messages['submit_checking']
                    }
                }
            };
            
            config = default_config;
        }
        else
        {
            config = new_config;
        }
        
        _check_config();
    };
    
    function _clear_array_errors() 
    {
        counter = 0;
        errors  = new Array();
        errors['form_validation']   = new Array();
        errors['single_validation'] = new Array();
    };
        
    function _get_values(name)
    {
        var elem = '';
        
        if (typeof(name) != 'undefined' && name != '')
        {
            var jq_name  = _get_jq_name(name);
            
            return $('[name="'+jq_name+'"]').serialize();
        }
        else
        {       
            return $('#'+config.form.id +' .vfield').serialize();
        }
    };
    
    function _validate_all_fields()
    {
        var data = _get_values();
        
        var ret  = false;
        
        $.ajax({
            url: config.form.url,
            global: false,
            type: "POST",
            data: data + "&ajax_validation_all=true",
            dataType: "json",
            async:false,
            error: function(data){
                                    
                if (typeof(data) != 'undefined' && data != null)
                {
                    var error_data = data.responseText;
                    
                    if (typeof(error_data) == 'string' && error_data.match('c_nt_oss_error'))
                    {
                        var msg_error = error_data;
                    }
                    else
                    {
                        var msg_error = av_messages['unknown_error'];
                    }
                    
                    ret = new Object();
                    
                    ret = { "status": "unknown_error", "data" : msg_error};
                }
            },
            success: function(data){
                ret = data;
            }
       });
        
       return ret;
    };
    
    
    function _check_expired_session(data)
    {
        if (typeof(data) == 'string' && data != null && data.match(/\<meta /i))
        {
            return true;
        }
        else
        {
            return false;
        }
    };
    
    
    function _get_jq_name(name)
    {
        var jq_name  = name.replace(/\[/g, "\\[");
            jq_name  = jq_name.replace(/\]/g, "\\]");
            jq_name  = jq_name.replace(/=/g, "\\=");
        
        return jq_name;
    }
    
    
    function _show_single_html_error(name, error)
    {               
        var elem = document.getElementsByName(name);
                
        if (typeof(elem[0]) != 'undefined')
        {
            var e_length = elem.length;
            
            var jq_name  = _get_jq_name(name);
                                
            for (var i=0; i<e_length; i++)
            {
                var id = elem[i].id;
                                
                $("#"+id).addClass("invalid");
                
                $("label[for='"+id+"']").addClass('av_label_error');
                
                $('#sl_'+jq_name).addClass('av_label_error');
                
                $('#'+id).addClass('av_error');
            }           
            
            //Show error in Summary error
            if (config.errors.display_errors == 'all' || config.errors.display_errors == 'summary')
            {                   
                if ($('#'+config.errors.display_in+' .error_'+jq_name).length >= 1)
                {
                    $('#'+config.errors.display_in+' .error_'+jq_name).html(error);
                }
                else if ($('#'+config.errors.display_in+' .error_summary').length >= 1)
                {
                    $('#av_summary #c_error_summary').append("<div class='error_"+name+" error_summary'>" +error +"</div>")
                }
            }
            
            
            //Show error in field
            
            var tooltip_id = 'av_tooltip_'+jq_name;
                
            error = error.stripTags();
            error = htmlentities(error,"ENT_QUOTES");
               
                 
            if ($('#'+ tooltip_id).length > 0)
            {
                $('#'+ tooltip_id).attr('title', error);
            }
            else
            {
                var img_src    = '/ossim/pixmaps/v_error.png';
                
                var tooltip_id = 'av_tooltip_'+name; 
                var tooltip    = '<span class="c_av_tooltip" id="'+tooltip_id+'"><img class="av_tooltip" src="'+img_src+'" title="'+error+'"/></span>';
                                                
                if ($('#sl_'+jq_name).length > 0)
                {
                    $('#sl_'+jq_name).after(tooltip);
                }
                else
                {
                    var id = elem[0].id;
                    
                    $("label[for='"+id+"']").after(tooltip);
                }
            }
            
            if(typeof($.fn.tipTip) != 'undefined')
            {
                $('.av_tooltip').tipTip({
                    'tipclass': 'av_red_tip'
                }); 
            }
        }
    };
    
    function _show_all_html_errors()
    {        
        var msg       = '';
        var txt_error = '';
                
        for (var i in errors['form_validation'])
        {                       
            if(typeof(errors['form_validation'][i]) == 'string')
            {
                msg += "<div class='error_"+i+" error_summary'>" + errors['form_validation'][i] +"</div>";
            }
        }
        
        txt_error = "<div style='padding-left: 10px;'>"+av_messages['error_header']+"<div id='av_summary'><div id='c_error_summary' style='padding-left: 20px;'>"+msg+"</div>";
            
        
        if ($('#'+config.errors.display_in + '.nf_error').length < 1)
        {
            var config_nt = { 
                content: txt_error, 
                options: {
                    type:'nf_error',
                    cancel_button: true
                },
                style: 'display:none; width: 100%; margin: auto;'
            };
            
            nt = new Notification('nt_1',config_nt);
            
            $('#'+config.errors.display_in).html(nt.show());
            $('#'+config.errors.display_in).show();
            
            nt.fade_in(1000);
        }
        else
        {
            //Update error message
            $('#'+config.errors.display_in + '.nf_error').html(txt_error);
        }
    };
    
        
    function _remove_all_html_errors()
    {               
        $('#'+config.errors.display_in).html("");
        
        _remove_all_single_html_errors();
    };
    
    
    function _remove_single_html_error(name)
    {
        //Previous error
        var previous_errors = $('.invalid').length;
        
        var elem = document.getElementsByName(name);
                
        var jq_name  = _get_jq_name(name);
        
        if (typeof(elem[0]) != 'undefined')
        {
            var e_length = elem.length;
                                
            for (var i=0; i<e_length; i++)
            {
                var id = elem[i].id;
                
                $("label[for='"+id+"']").removeClass('av_label_error');
                $("#sl_"+jq_name).removeClass('av_label_error');
                
                $("#"+id).removeClass("invalid");
                $('#'+id).removeClass('av_error');
            }
            
            if (config.errors.display_errors == 'all' || config.errors.display_errors == 'summary')
            {                   
                if ($('#'+config.errors.display_in+' .error_'+jq_name).length >= 1)
                {
                    $('#'+config.errors.display_in+' .error_'+jq_name).html(error);
                }
                else if ($('#'+config.errors.display_in+' .error_summary').length >= 1)
                {
                    $('#av_summary #c_error_summary').append("<div class='error_"+name+" error_summary'>" +error +"</div>")
                }
            }
            
            
            //Remove tooltip
            var tooltip_id = 'av_tooltip_'+jq_name;
            
            $('#'+ tooltip_id).remove();
        }
        
        //Delete error from ajax_validator
        delete errors['single_validation'][name];
        
        //Delete error from HTML form
        $(".error_"+jq_name).remove();
        
        
        var current_errors = $('.invalid').length;
        
        
        if (previous_errors > 0 && current_errors == 0)
        {
            $('#'+config.errors.display_in).html("");
        }
    };
    
    function _remove_all_single_html_errors()
    {
        $('*').removeClass('av_label_error');
        $('*').removeClass('av_error');
        $('.c_av_tooltip').remove();
        $('*').removeClass('invalid');
    };
    
    
    function _validate_field(name)
    {        
        var query_string = _get_values(name);
            
        $.ajax({
            type: "GET",
            url:config.form.url,
            dataType: "json",
            cache: false,
            data: query_string + "&name=" + name + "&ajax_validation=true",
            error: function(data){
                
                if (typeof(data) != 'undefined' && data != null)
                {                       
                    var error_data = data.responseText;
                    
                    if (_check_expired_session(error_data) == true)
                    { 
                        document.location.href='/ossim/session/login.php?action=logout';
                        return false;
                    }
                    
                    if (typeof(error_data) == 'string' && error_data != '')
                    {
                        if (error_data.match('c_nt_oss_error'))
                        {
                            $('body').html(error_data);
                        }
                        else 
                        {
                            var config_nt = { 
                                content: av_messages['unknown_error'], 
                                options: {
                                    type:'nf_error',
                                    cancel_button: false
                                },
                                style: 'width: 90%; margin: 30px auto;'
                            };
                                
                            nt = new Notification('nt_1',config_nt);
                            
                            $('body').html(nt.show());
                        }
                    }
                }

                return false;
            },
            success: function(data){
                
                // Errors 
                if (typeof(data) != 'undefined' && data != null)
                {
                    if (data.status == 'error')
                    {
                        if (typeof(data.data[name]) != 'undefined')
                        {
                            if (config.errors.display_errors == 'all' || config.errors.display_errors == 'field-errors')
                            {
                                _show_single_html_error(name, data.data[name]);
                            }
                            
                            errors['single_validation'][name] = name;
                        }
                        
                        //Invalid Send Method
                        if (typeof(data.data.invalid_sm) != 'undefined') 
                        {
                            errors['form_validation']['invalid_sm'] = data.data.invalid_sm;
                            
                            if (config.errors.display_errors == 'all' || config.errors.display_errors == 'summary')
                            {
                                _show_all_html_errors();
                            }
                        }
                    }
                    else if (data.status == 'OK')
                    {
                        _remove_single_html_error(name);
                    }
                }
            }
        });
    };

    function _check_form()
    {
        _clear_array_errors();
                        
        if (typeof(config.actions) != 'undefined')
        {
            if (typeof(config.actions.on_submit) != 'undefined')
            {
                if (config.actions.on_submit.success != '' && config.actions.on_submit.checking != '')
                {
                    var label = _e2unicode(config.actions.on_submit.checking);
                    
                    $('#'+config.actions.on_submit.id).addClass('av_b_processing');
                    $('#'+config.actions.on_submit.id).val(label);
                }
            }
        }
                    
        var data = _validate_all_fields();

        if (data == false)
        {
            document.location.href='/ossim/session/login.php?action=logout';
            
            return false;
        }

        if (data.status == 'unknown_error')
        {
            if (data.data.match('c_nt_oss_error'))
            {
                $('body').html(data.data);
            }
            else
            {
                var config_nt = { 
                    content: data.data, 
                    options: {
                        type:'nf_error',
                        cancel_button: false
                    },
                    style: 'width: 90%; margin: 30px auto;'
                };
                
                nt = new Notification('nt_1',config_nt);
                                      
                $('body').html(nt.show());
            }
        
            return false;
        }               
            
            
        //There are errors, show single errors
        if (data.status == 'error')
        {
            _remove_all_single_html_errors();
            
            error = true;
                                    
            $.each(data.data, function(name, error_txt) {
                                                
                errors['form_validation'][name] = error_txt;
                                                
                if (config.errors.display_errors == 'all' || config.errors.display_errors == 'field-errors')
                {
                    _show_single_html_error(name, error_txt);
                }
            });
        }
        
        //Button actions
        if (typeof(config.actions) != 'undefined')
        {
            if (typeof(config.actions.on_submit) != 'undefined')
            {
                if (config.actions.on_submit.success != '' && config.actions.on_submit.checking != '')
                {
                    var label = _e2unicode(config.actions.on_submit.success);
                    
                    $('#'+config.actions.on_submit.id).removeClass('av_b_processing');
                    $('#'+config.actions.on_submit.id).val(label);
                }
            }
        }
            
        //There are errors, show summary errors
        if (data.status == 'error')
        {
            if (config.errors.display_errors == 'all' || config.errors.display_errors == 'summary')
            {
                _show_all_html_errors();
            }
            
            parent.window.scrollTo(0,0);
            
            return errors;
        }
        else
        {            
            _remove_all_html_errors();
            
            return true;
        }
    };
}