<?php
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

if (!Session::am_i_admin())
{
    $error = _("You do not have permission to see this section");
    
    Util::response_bad_request($error);
}
Av_plugin::clear();
?>

<script>
    /**
     * This function shows or hides elements in upload form
     *
     * @param string what   To know which element must be shown
     */
    function toggle_file_input(what)
    {
        if (what == 'file')
        {
            $('#input_container').css('display', 'none');
            $('#uploaded_file_name').css('display', 'block');
        }
        else
        {
            $('#uploaded_file_name').css('display', 'none');
            $('#input_container').css('display', 'block');
        }
    }


    /**
    * This function is called by av_wizard.js.php jQuery plugin when the content is loaded
    */
    function load_js_step()
    {
        <?php
        if (Av_plugin::get_wizard_data('file') != '')
        {
        ?>
        $('#uploaded_file_name').html("<?php echo Av_plugin::get_wizard_data('file') ?>");
        $('#uploaded_file_name').addClass('selected');
        toggle_file_input('file');
        <?php
        }
        else
        {
        ?>
        $('#next_step').prop('disabled', true);
	var sstep = $('#wizard_path_container:not(.current_step)');
	sstep.find('.step_name.av_link').addClass('av_l_disabled');
        sstep.find('.wizard_number').removeClass('s_visited');
        <?php
        }
        ?>
        
        $('#plugin_file').on('change', function()
        {
            if ($(this).val() != '')
            {
                upload_file();
            }
            else
            {
                $('#uploaded_file_name').removeClass('selected');
                $('#uploaded_file_name').html('');
                toggle_file_input('input');
            }
        });
        

        $('[data-bind="browse"], #upload_file_input').click(function()
        {
            $('#plugin_file').click();
        });
    }


    /**
    * This function submits the file upload form via Ajax call
    * And changes the form aspect when success
    */
    function upload_file()
    {
        var file_data = $('#plugin_file').prop('files')[0];   
        var form_data = new FormData();

        var _path     = $('#plugin_file').val();
        var file_name = _path.match(/[^\/\\]+$/)[0];
	var blob = new Blob([file_data], {type: "text/plain"});
        form_data.append('qqfile',    blob, file_name);
	var error = function(XMLHttpRequest, textStatus, errorThrown) {
                //Checking expired session
                var session = new Session(XMLHttpRequest, '');
                if (session.check_session_expired() == true)
                {
                    session.redirect();
                    return;
                }

                var error = XMLHttpRequest.responseText;
                show_notification('validate_notif', error, 'nf_error', 5000);

                $('#uploaded_file_name').removeClass('selected');
                $('#uploaded_file_name').html('');
                toggle_file_input('input');
                $('#plugin_file').val('');

        };

        $.ajax({
            url: '/ossim/asec/data/sections/training/actions.php?action=upload&filename='+file_name,
            dataType: 'json',
            cache: false,
            contentType: false,
            processData: false,
            data: form_data,
            type: 'post',
            success: function(data)
            {
	        $.ajax({
        	    url: '/ossim/asec/data/sections/training/actions.php',
        	    cache: false,
	            data: {'filename': data.filename, 'action' : 'train'},
        	    type: 'post',
	            success: function(data) {
			var data = $.parseJSON(data);
                        var filename = data.filename;
                        $.ajax({
                           url: "/ossim/session/token.php",
                           cache: false,
                           data: {'f_name': 'plugin_actions'},
                           type: 'post',
                           success: function(data) {
                               var token = $.parseJSON(data);
                               $.ajax({
                                   url: '<?php echo AV_MAIN_PATH . "/av_plugin/controllers/plugin_actions.php" ?>',
                                   cache: false,
                                   data: {'file': file_name, 'fbase': filename, 'action' : 'upload_plugin_file', 'token': token.data},
                                   type: 'post',
                                   success: function(data) {
                                       $('#next_step').prop('disabled', false);
                                       $('#uploaded_file_name').addClass('selected');
                                       $('#uploaded_file_name').html(file_name);
                                       toggle_file_input('file');
                                  },
                                  error: error
                               });
                           },
                           error: error
		       });
                    },
                    error: error
                });
            },
            error: error
        });
    }
    
</script>

<div class='step_container'>

    <div class='wizard_title'>
        <?php echo _('Upload Log File') ?>
    </div>
    
    <div class='wizard_subtitle'>
        <?php echo _('Step 1: Upload a log file in plain text format to get started. The Plugin Builder will parse the file to help you create the plugin.') ?>
    </div>
    
    <div class='upload_container'>
        <input type='file' name='plugin_file' id='plugin_file' value='' />
        <div id='input_container'>
            <input type='text' id='upload_file_input' />
        </div>
        
        <div id='uploaded_file_name'>
            
        </div>
        
        <div id='browse_container'>
            <input type='button' data-bind='browse' id='browse_button' class='av_b_secondary' value='<?php echo _('Browse') ?>' />
        </div>
        
        <div class='clear_layer'></div>
        
    </div>
    
    <div id='validate_notif'></div>

</div>
