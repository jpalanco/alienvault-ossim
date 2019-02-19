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


set_time_limit(0);

require_once 'av_init.php';

Session::logcheck('environment-menu', 'PolicyHosts');

ob_implicit_flush();
session_write_close();

//Initialize variables
$mode        = 'init';
$num_hosts   = 0;

if (Session::can_i_create_assets() == FALSE)
{
    $e_msg = _("You don't have permission to create assets");
    
    echo ossim_error($e_msg);
    exit();
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="">
<head>    
    
    <?php 
    //CSS Files
    $_files = array(
        array('src' => 'av_common.css',     'def_path' => TRUE)
    );
    
    Util::print_include_files($_files, 'css');
    
    //JS Files
    $_files = array(
        array('src' => 'jquery.min.js',                 'def_path' => TRUE),
        array('src' => 'notification.js',               'def_path' => TRUE),
        array('src' => 'jquery.progressbar.min.js',     'def_path' => TRUE)
    );
    
    Util::print_include_files($_files, 'js');
    
    ?>

    <script type='text/javascript'>		
		
		var __cfg = <?php echo Asset::get_path_url() ?>;
		
		function show_error(error)
		{
            var txt_error = "<div><?php echo _("The following errors occurred")?>:</div>" +
                            "<div style='padding:0px 15px;'><div class='sep'>"+ error +"</div></div>";
                        
            var config_nt = { content: txt_error, 
                              options: {
                                type:'nf_error',
                                cancel_button: false
                              },
                              style: 'width: 90%; margin: 10px auto;'
                            };
            
            nt            = new Notification('nt_1', config_nt);
            notification  = nt.show();
            
            $('#t_import_siem').html(notification);
            
            parent.window.scrollTo(0, 0);
        }
        
        
        function GB_hide()
        {
            top.frames['main'].GB_hide();
        }
        
        function GB_close()
        {
            top.frames['main'].GB_close();
        }		
		        
        $(document).ready(function()
        {       
            $('#ish_form').attr('action', __cfg.asset.controllers + 'import_all_hosts_from_siem_ajax.php');
            setTimeout(function()
            {
                $('#ish_form').submit();
                
            }, 100);            
            
            $('#cancel').click(function()
            {
                GB_close();
            });
            
            $("#pbar").progressBar();           
        });     
    </script>        

    
    <style type="text/css">
        
        #c_import_siem
        {
            width: 500px;
            padding: 20px 0px;
            margin: auto;
        }
        
        #t_import_siem
        {
            margin: auto;
            text-align: center;
            border: none;
            background: transparent;
            width: 100%;                     
        }
        
        #pbar_pbText
        {
            display: block;
            margin: 3px auto;            
            text-align: center;
        }
        
        #av_info
        {
            width: 80%;
            margin: 10px auto;
        }
        
        #iframe_ish
        {
            display: none;
        }
                
    </style>    
</head>
<body>    
    <div id="c_import_siem">
    <?php 
    if ($mode == 'init' || $mode == 'insert') 
    {        
        ?>                
        <form name="ish_form" id="ish_form" method="POST" action='' target='iframe_ish'>
            <input type="hidden" id='mode' name="mode" value="<?php echo $mode?>"/>
            <input type="hidden" id='num_hosts' name="num_hosts" value="<?php echo $num_hosts?>"/>       
        
            <table id='t_import_siem'>
                <tr>
                    <td id="ptext" class="center noborder"><?php echo _('Searching networks').' ...'?></td>
                </tr>
                <tr>
                    <td class="center noborder">
                        <span class="progressBar" id="pbar"></span>
                    </td>
                </tr>
                <tr>
                    <td class="center">                    
                        <div style='padding-top: 20px'>                         
                             <input type="button" id="cancel" name="cancel" class="av_b_secondary small" value="<?php echo _('Cancel');?>"/>
                             <input id="import_button" type="button" class="small" value="<?php echo _('Import')?>" disabled='disabled'/>
                        </div>       
                    </td>
                </tr>
            </table>        
        </form>
        
        <iframe name="iframe_ish" id="iframe_ish"></iframe>                   
        <?php 
    } 
    ?>
    </div>
</body>
</html>
