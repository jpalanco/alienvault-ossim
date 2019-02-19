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


Session::useractive();

$pro        = Session::is_pro();
$am_i_admin = Session::am_i_admin();


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>

    <head>
        <title><?php echo _('AlienVault ' . ($pro ? 'USM' : 'OSSIM' ))?></title>

        <link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>">

        <!-- jQuery and jQuery UI -->
        <script type="text/javascript" src="/ossim/js/jquery.min.js"></script>
        <script type="text/javascript" src="/ossim/js/jquery-ui.min.js"></script>

        <!-- AV tools -->
        <script type="text/javascript" src="/ossim/js/utils.js"></script>
        <script type="text/javascript" src="/ossim/js/notification.js"></script>
        <script type="text/javascript" src="/ossim/js/lightbox.js"></script>
     
       
        <script type="text/javascript" src="/ossim/js/purl.js" ></script>        
        
        
        <style type='text/css'>
                              
            table
            { 
                width: 500px !important;               
                margin: auto;
            }
            
            th
            {
                padding: 7px;
                text-align: left;           
            }
            
            td
            {
                padding: 5px;
                text-align: left;
            }
            
            #av_info
            {
                width: 600px;
                margin: 10px auto;
            }
            
            .no_ri, .no_ri:hover, .no_ri td
            {
                background: white;
            }                   
            
        </style>
        
        <script type="text/javascript">
        
            function show_error(error)
            {          
                var txt_error = "<div><?php echo _('The following errors occurred')?>:</div>" +
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
                
                $('#av_info').html(notification);
            }
            
            
            var n_ri = null;
			
			/* Show remote interface */	
			function show_ri(id, w, h)
			{			    
			    //Getting IP
                var ip = id.replace('ri_', '');
                                                   
                //Getting URL
                                     
                var w_pathname = parent.document.getElementById("main").contentWindow.document.location.pathname;
                var w_search   = parent.document.getElementById("main").contentWindow.document.location.search;
                var w_url      = parent.document.getElementById("main").contentWindow.document.location.href;
                                                                                                                                                        
                var p_url      = $.url(w_url);                
                                
                var loading_id = "loading_"+ip;
                
                if ($('#'+loading_id).length <= 1)
                {
                    $(this).after("<div id='"+loading_id+"' class='loading'></div>");
                    $('#'+loading_id).show();
                }
                else
                {
                    return false;
                }                                
                
                //Parsing url
                
                var params = p_url.param();
                                
                var query_string = "";
                                                                                                                                                                  
                if (typeof(params['m_opt']) == 'undefined' && typeof(params['sm_opt']) == 'undefined')
                {                
                    var aux_opt = parent.av_menu.sm_option.split('-');                    
                    
                    var m_opt   = aux_opt[0];
                    var sm_opt  = aux_opt[1];                         
                    
                    query_string  += 'm_opt='+m_opt;
                    query_string  += '&sm_opt='+sm_opt;                  
                }
                                                        
                if(typeof(params['h_opt']) == 'undefined')
                {                   
                    query_string += '&h_opt='+parent.av_menu.h_option;  
                }
                                    
                
                if(typeof(params['l_opt']) == 'undefined')
                {
                    var l_opt = parent.$('#c_lmenu ul li.active').attr('id');
                    
                    query_string += (typeof(l_opt) == 'undefined' || l_opt  == '') ? '' : '&'+l_opt.replace('ll_opt_', '');
                }                    
                                              
                             
                if (typeof(w_search) != 'undefined' && w_search != '')
                {
                    w_url = w_pathname+w_search+'&'+query_string;
                }
                else
                {
                    w_url = w_pathname+'?'+query_string;
                }
                                            
                
                var url   = 'https://'+ip+'/ossim/index.php';                
                
                var w = 1200;
                var h = 720;                                 
			   		    
			    var l_position = (screen.width) ? (screen.width-w)/2: 300;
			    var t_position = (screen.height)?(screen.height-h)/2: 300;    
			
				var	settings ='width='+w+',height='+h+',top='+t_position+',left='+l_position+',scrollbars=yes,location=no,directories=no,status=no,menubar=no,toolbar=no,resizable=yes';				
				
                $('#url').val(Base64.encode(w_url));
                $('#login').val('<?php echo $_SESSION['_remote_login']?>');				
                																			
                new_ri = window.open('about:blank', ip, settings);
                
                new_ri.focus();                   
                
                $("#ri_form").attr("action", url);
                $("#ri_form").attr("target", ip);
                $("#ri_form").submit();										
			} 
			            
             
            $(document).ready(function() 
            {
                $('table tbody tr:odd').addClass('odd');
                $('table tbody tr:even').addClass('even');
                
                var new_ri = null;
                
                $('.ri').click(function(){           
                    
                    var id = $(this).attr('id')
                    
                    show_ri(id, '1200','720');
                    
                    return false;                
                });                     
            });             
                                            
        </script>
        
    </head>
    <body>
        
        <div id='c_ri'>
        
            <div id='av_info'></div>
                                           
            <table class='table_list'>             
            <?php    
            if ($pro && $am_i_admin) 
            {                        
                $db   = new Ossim_db();
                $conn = $db->connect();
                
                $aux_ri_interfaces = Remote_interface::get_list($conn, "WHERE status = 1");
                
                $ri_list = $aux_ri_interfaces[0];
                $total   = $aux_ri_interfaces[1];
                                              
                                                      
                if ($total > 0)
                {
                    foreach($ri_list as $r_interface)
                    {
                        ?>
                        <tr>
                            <td>
                                <a class='ri' id='ri_<?php echo $r_interface->get_ip()?>' href="javascript:void(0);">
                                    <?php echo $r_interface->get_name()." [".$r_interface->get_ip()."]"?>
                                </a>
                            </td>                                   
                        </tr>
                        <?php
                    }
                }
                else
                {                            
                    ?>
                    <tr class='no_ri'>
                        <td>
                            <?php echo ossim_error(_('Remote interfaces not found'), AV_INFO);?>    
                        </td>
                    </tr>
                    <?php
                }
                
                $db->close();                
            }
            ?>             
            </table>
             
            <form method='POST' name='ri_form' id='ri_form'>
                  <input type='hidden' id='login' name='login'/>
                  <input type='hidden' id='url' name='url'/>                  
            </form>                       
        </div>
    </body>
</html>

<?php
/* End of file /remote_interfaces/launch_ri.php */
/* Location: /remote_interfaces/launch_ri.php */
