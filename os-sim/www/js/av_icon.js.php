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
    
//Asset error messages
$a_error_msg['size_not_allowed']  = _('Error in the icon field (Image size not allowed)');
$a_error_msg['file_not_allowed']  = _('Error in the icon field (Invalid image)');
$a_error_msg['file_not_uploaded'] = _('Error in the icon field (Image not uploaded)');
$a_error_msg['unknown_error']     = _('Error in the icon field (Operation was not completed due to an unknown error)');
             
?>


/****************************************************
 ***************** Icon functions *******************
 ****************************************************/

//Show formatted error if a preview icon can not be showed  
function show_preview_error(config, msg)
{    
    var msg = '<div class="error_'+config.icon.input_file_id +' error_summary">'+ msg +'</div>';    
        
    //Information container is empty or there is no infomation from AJAX Validator
    var cnd_1 = $('#'+config.errors.display_in).html() == '';
    var cnd_2 = $('#'+config.errors.display_in +' #av_summary').length == 0;
    
    if (cnd_1 || cnd_2)
    {
        var config_nt = { 
            content: msg, 
            options: {
                type:'nf_error',
                cancel_button: false
            },
            style: 'width: 100%; margin: 10px auto; padding: 5px 0px; font-size: 12px; text-align: left;'
        };

        nt = new Notification('nt_icon', config_nt);
        
        $('#'+config.errors.display_in).html(nt.show());
    }
    else
    {                
        if ($('#'+config.errors.display_in +' #av_summary').length > 0)
        {
            if ($('#'+config.errors.display_in +' .error_'+config.icon.input_file_id).length > 0)
            {
                $('#'+config.errors.display_in +' .error_'+config.icon.input_file_id).html(msg);
            }
            else
            {                
                $('#'+config.errors.display_in +' #c_error_summary').append(msg);
            }        
        }    
    }
}


//Delete all errors related to icons
function delete_preview_error(config)
{    
    if ($('#'+config.errors.display_in + ' #nt_icon').length > 0)
    {
        $('#'+config.errors.display_in + ' #nt_icon').remove();
    }
    else
    {
        $('#'+config.errors.display_in +' .error_'+config.icon.input_file_id).remove();
        
        if ($('#'+config.errors.display_in + ' .error_summary').length == 0)
        {
            $('#'+config.errors.display_in).empty();
        }
    }
}


//Show a preview from icon               
function show_icon_preview(config, input) 
{            
    var file  = input.files[0];
    var error = '';
        
    //Empty preview image
    $('#'+config.icon.container).empty();                     
        
    if (typeof(input) == 'object' && typeof(file) == 'object')
    {
        //Checking FileReader support
        if (window.FileReader) 
        {
            if (file.type.match('image.*'))
            {
                var reader = new FileReader();
                
                reader.onload = function(e) {
                                    
                    var image = new Image();
                        image.src = e.target.result;
                    
                    image.onload = function(){
                        
                        var width  = config.icon.restrictions.width;
                        var height = config.icon.restrictions.height;                       
                        
                        var img_w = this.width;
                        var img_h = this.height;
                        
                        if(img_w <= width && img_h <= height)
                        {                             
                            delete_preview_error(config);
                            
                            $('#'+config.icon.container).html('<img id="asset_icon" src="'+e.target.result+'" align="absbottom"/>');
                            
                            
                            var html_actions = "<a id='remove_preview' href=\"javascript:void(0)\"><?php echo _('Remove icon')?></a>" + 
                                               "<span> <?php echo _('or')?> </span>";
                            
                            
                            $('#'+config.actions.container).html(html_actions);
                            
                            
                            //Bind action
                            $('#remove_preview').off();
                            $('#remove_preview').click(function(){
                                remove_html_data(config);   
                            }); 
                        }
                        else
                        {
                            error = '<?php echo $a_error_msg['size_not_allowed']?>';
                            
                            show_preview_error(config, error);
                        }                        
                    };
                }
                
                reader.readAsDataURL(file);
            }
            else
            {
                error = '<?php echo $a_error_msg['file_not_allowed']?>';
                
                show_preview_error(config, error);
            }                
        }
        else
        {
            // No FileReader support, no show preview image

            if (input.value.match(/(png|jpeg|jpg|gif)$/i))
            {                        
                delete_preview_error(config);
                $('#'.config.icon.container).html('<div id="asset_icon">'+input.value+'<div/>');  
            }
            else
            {
                error = '<?php echo $a_error_msg['file_not_allowed']?>';
                
                show_preview_error(config, error);
            }
        }
    }
    else
    {
        error = '<?php echo $a_error_msg['file_not_uploaded']?>';
        
        show_preview_error(config, error);
    }
}
    


//Remove HTML data from form
function remove_html_data(config)
{
    //Remove Icon preview
    $('#'+config.icon.container).empty();
    
    //Remove action
    $('#'+config.actions.container).empty();
    
    //Remove data from input file
    $('#'+config.icon.input_file_id).val('');
}


//Remove icon (Database and HTML information)
function remove_icon(config)
{                           
    //Getting Form token
    var token  = Token.get_token(config.token_id);
    
    //AJAX data 
    var i_data = {
        "action"   : "remove_icon",
        "asset_id" : config.asset_id,
        "token"    : token
    };  
    
    $.ajax({
        type: "POST",
        url:  config.actions.url,
        data: i_data,
        dataType: 'json',
        beforeSend: function(xhr){
            $('.r_loading').html('<img src="../pixmaps/loading.gif" align="absmiddle" width="13" alt="<?php echo _('Loading')?>">');
        },
        error: function(data){

            //Check expired session
            var session = new Session(data, '');
                        
            if (session.check_session_expired() == true)
            {
                session.redirect();
                
                return;
            }  
            
            $('.r_loading').html('');
            
            var config_nt = { 
                content: "<?php echo $a_error_msg['unknown_error']?>",
                options: {
                    type:'nf_error',
                    cancel_button: false
                },
                style: 'width: 90%; margin: 10px auto; padding: 5px 0px; font-size: 12px; text-align: left;'
            };
        
            nt = new Notification('nt_icon', config_nt);
            
            $('#'+config.errors.display_in).html(nt.show());
        },
        success: function(data){
            
            //Check expired session                
            var session = new Session(data, '');
            
            if (session.check_session_expired() == true)
            {
                session.redirect();
                
                return;
            } 
                                                        
            var cnd_1  = (typeof(data) == 'undefined' || data == null);
            var cnd_2  = (typeof(data) != 'undefined' && data != null && data.status != 'OK');
                                
            if (!cnd_1 && !cnd_2)
            {
                remove_html_data(config);
            }
            else
            {
                var config_nt = { 
                    content: data.data, 
                    options: {
                        type:'nf_error',
                        cancel_button: false
                    },
                    style: 'width: 90%; margin: 10px auto; padding: 5px 0px; font-size: 12px; text-align: left;'
                };
            
                nt = new Notification('nt_icon', config_nt);
                
                $('#'+config.errors.display_in).html(nt.show());
            }
            
            $('.r_loading').html('');
        }
    });
}


//Bind form action (Remove icon, show icon preview, ...)
function bind_icon_actions(config)
{
    var input_file_id  = '#'+ config.icon.input_file_id;
    var custom_link_id = '#'+ $(input_file_id).parent().attr('id') + ' a';
                
    $(custom_link_id).on("click", function() {
        $(this).next('input[type="file"]').click();
    });
    
    $("#icon").change(function(){
        show_icon_preview(config, this);
    });
    
    $('#remove_icon').click(function(){
        remove_icon(config);   
    }); 
}
