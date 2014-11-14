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

Session::logcheck('environment-menu', 'PolicyHosts');


$id  = GET('id');

ossim_valid($id, OSS_HEX, 'illegal:' . _('Host ID'));

if (ossim_error())
{ 
    echo ossim_error(_('Error! Host not found'));
    
    exit();
}


/****************************************************
 ******************** Host data *********************
 ****************************************************/


//Database connection
$db    = new ossim_db();
$conn  = $db->connect();

$host = new Asset_host($conn, $id);
$host->load_from_db($conn);

//CTX
$ctx = $host->get_ctx();

$is_nagios_enabled = $host->is_nagios_enabled($conn); 

$is_ext_ctx  = FALSE;
$ext_ctxs    = Session::get_external_ctxs($conn);

if (!empty($ext_ctxs[$ctx]))  
{
    $is_ext_ctx = TRUE;
}

//Closing database connection
$db->close();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <title><?php echo _('OSSIM Framework');?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
        <meta name="viewport" content="initial-scale=1.0, user-scalable=no"/>
        <meta http-equiv="Pragma" content="no-cache"/>
        
        <script type="text/javascript" src="../js/jquery.min.js"></script>
        <script type="text/javascript" src="../js/jquery-ui.min.js"></script>
        <script type="text/javascript" src="../js/notification.js"></script>
        <script type="text/javascript" src="../js/messages.php"></script>
        <script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
        <script type="text/javascript" src="../js/utils.js"></script>       
                
        <!-- Dynatree libraries: -->
        <script type="text/javascript" src="../js/jquery.cookie.js"></script>
        <script type="text/javascript" src="../js/jquery.tmpl.1.1.1.js"></script>
        <script type="text/javascript" src="../js/jquery.dynatree.js"></script>
        <script type="text/javascript" src="../js/jquery.tipTip.js"></script>
        <script type="text/javascript" src="../js/token.js"></script>
        <script type="text/javascript" src="../js/jquery.base64.js"></script>    
        <script type="text/javascript" src="../js/jquery.autocomplete.pack.js"></script>       
    
        <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
        <link rel="stylesheet" type="text/css" href="../style/jquery.autocomplete.css" />
        <link rel="stylesheet" type="text/css" href="../style/tree.css"/>
        <link rel="stylesheet" type="text/css" href="../style/tipTip.css"/>
        
        <style type='text/css'>
                       
            table
            { 
                border-collapse: collapse;
                border-spacing: 0px;
                border: none;
                background: none;
                margin: auto;
                width: 100%;
            }
            
            #host_container 
            {
                width: 600px;
                margin: 20px auto;
                padding-bottom: 10px;
                border: none;
                min-height: 200px;
            }
            
            #host_container #t_container
            { 
                border-collapse: collapse;
                border: none;
                background: none;
                margin: auto;
                width: 100%;
            } 
            
            #host_container #t_container
            { 
                border-collapse: collapse;
                border: none;
                background: none;
                margin: auto;
                width: 100%;
            }

            #t_container td 
            {
                text-align: left;
                vertical-align: top;
            }
            
            #t_actions
            {
                border: none !important;
                height: 30px;                
            }
            
            #t_actions .left
            {
                border: none;
                padding: 8px 0px 0px 0px !important;
                text-align: left !important;
                vertical-align: middle;
            }
            
            #t_actions .right
            {
                border: none;
                padding: 8px 0px 0px 0px !important;
                text-align: right !important;
            } 
            
            #toggle_am
            {
                display:none;
            }
                                     
                  
            #av_info 
            {
                width: 80%; 
                margin: 10px auto;
            }   
        
        </style>
        
        <script type='text/javascript'>
                
            <?php
            if ($is_ext_ctx == FALSE)
            {
                ?>                                               
                /****************************************************
                 ****************** Form functions ******************
                 ****************************************************/                
                
                function bind_form_actions()
                {                
                    //Toggle availability monitoring                    
                    $('#toggle_am').on('click', function() {
                        toggle_am();
                    });
                    
                 
                    //Check all services
                    $("#check_all").on('click', function(e) {
                        
                        e.preventDefault();
        				
        				if ($(layer).length >= 1)
        				{
        					$(layer).dynatree("getRoot").visit(function(node){
        						node.select(true);
        					});
        				
        					return false;
        				}
        			});
        											
        			//Uncheck all services
                    $("#uncheck_all").on('click', function(e) {
                        
                        e.preventDefault();
        				
        				if ($(layer).length >= 1)
        				{
        					$(layer).dynatree("getRoot").visit(function(node){
        						node.select(false);
        					});
        				
        					return false;
        				}
        			});
                          
                }              
                              
                    
                /****************************************************
                 ****************** Tree function *******************
                 ****************************************************/
                
                var reloading_text  = '<div class="reload"><img src="../pixmaps/theme/loading2.gif" border="0" align="absmiddle"/>'+messages[6]+'</div>';
                
                var layer    = null;
                var nodetree = null;
                var i        =  1;
                         
                function load_tree()
                {
                    if (nodetree != null)
                    {
                        nodetree.removeChildren();
                        $(layer).remove();
                    }
                    
                    layer = '#srctree1_'+i;
                    
                    $('#tree_container').append('<div id="srctree1_'+i+'" style="width:100%"></div>');
                    
                    $(layer).html(reloading_text);
                    
                    
                    //Dynatree
    
                    $(layer).dynatree({
                        initAjax:{
                            url: '../av_tree.php',
                            data: {
                               key: "service_tree",
                               filters: {host_id: "<?php echo $id?>"},
                               max_text_length: '50'
                            },
                        },
                        minExpandLevel: 2,
                        clickFolderMode: 1,              
                        checkbox: true,           
                        cookieId: "dynatree_sp",                             
                        onSelect: function(select, node) {
                            
                            // Get a list of all selected nodes, and convert to a key array:
                            var selKeys = $.map(node.tree.getSelectedNodes(), function(node){
                                return node.data.key;
                            });                                
                                                           
                            if (selKeys.length > 0)
                            {
                                $("#toggle_am").show();                   
                            }
                            else
                            {
                                $("#toggle_am").hide();          
                            }
                        }   
                    });
    
                    
                    nodetree = $(layer).dynatree("getRoot");
                            
                    i = i + 1;
                } 

                
                /****************************************************
                ****************** Form functions ******************
                ****************************************************/
                
                //Toggle availability monitoring by services
            
                function toggle_am()
                {                 
                    if (!confirm("<?php echo  Util::js_entities(_('Are you sure to toggle availability monitoring in these services'))?>?"))
                    {
                        return false;
                    }                    

                    //Getting services
                    var root = $(layer).dynatree("getRoot");
                            
                    var nodes = $.map(root.tree.getSelectedNodes(), function(node){
                        return node.data.filters;
                    }); 
                    
                    
                    var services = null;
                    
                    if (typeof(nodes) == 'object')
                    {
                        services = Base64.encode(JSON.stringify(nodes));
                    }
                    
                    var token = Token.get_token("host_form");
                    
                    //AJAX data
                    var s_data = {
                            "action"    : "toggle_a_monitoring",
                            "host_id"   : "<?php echo $id?>",
                            "services"  : services,
                            "token"     : token
                    };                                        
                   
                    
                    $.ajax({
                        type: "POST",
                        url: 'host_actions.php',
                        data: s_data,
                        dataType: 'json',
                        beforeSend: function(xhr){
                            
                            $('#av_info').html('');
                            
                            show_loading_box('host_container', '<?php echo _('Toggling Availability Monitoring')?>...', '');                         
                        },
                        error: function(data){
                
                            //Check expired session
                            var session = new Session(data, '');
                                        
                            if (session.check_session_expired() == true)
                            {
                                session.redirect();
                                
                                return;
                            }  
                            
                            hide_loading_box();
                                                
                            var config_nt = { content: av_messages['unknown_error'],
                                options: {
                                    type:'nf_error',
                                    cancel_button: false
                                },
                                style: 'width: 80%; margin: 10px auto; padding: 5px 0px; font-size: 12px; text-align: left;'
                            };
                            
                            var nt = new Notification('nt_error', config_nt);

                            $('#av_info').html(nt.show());
                            
                            window.scrollTo(0,0);
                        },
                        success: function(data){
                            
                            hide_loading_box();

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
                                //Hide toggle Nagios button
                                $('#toggle_am').hide();
                                                                                          
                                var config_nt = { 
                                    content: data.data, 
                                    options: {
                                        type:'nf_success',
                                        cancel_button: false
                                    },
                                    style: 'width: 80%; margin: 10px auto; padding: 5px 0px; font-size: 12px; text-align: left;'
                                };
                            
                                var nt = new Notification('nt_success', config_nt);
                                
                                setTimeout("$('#nt_success').fadeOut(4000);", 10000);
                                
                                window.scrollTo(0,0);  
                                
                                $('#av_info').html(nt.show());

                                if(typeof(top.frames['main'].force_reload) != 'undefined')
                                {
                                    top.frames['main'].force_reload = 'software';
                                }
                            } 
                            else
                            {
                                var config_nt = { 
                                    content: data.data, 
                                    options: {
                                        type:'nf_error',
                                        cancel_button: false
                                    },
                                    style: 'width: 80%; margin: 10px auto; padding: 5px 0px; font-size: 12px; text-align: left;'
                                };
                            
                                var nt = new Notification('nt_error', config_nt);
                                                                
                                $('#av_info').html(nt.show());
                                
                                window.scrollTo(0,0);                                    
                            }
                            
                            
                            //Reload tree
                            if (typeof(data) != 'undefined' && typeof(data.reload_tree) != 'undefined' && data.reload_tree == true)
                            {
                                load_tree();
                            }            
                        }
                    });                
                }
                <?php
            }        
            ?>      
                                             
                        
            $(document).ready(function(){               
           
                
                /**************************************************
                ********************** Forms **********************
                ***************************************************/
                
                <?php
                if ($is_ext_ctx == FALSE)
                {
                   ?>
                   /**************************************************
                    *********************** Tree **********************
                    ***************************************************/
                        
                    load_tree();
                                          
                     
                    /**************************************************
                    ********************** Forms **********************
                    ***************************************************/                                                
                        
                    //Bind actions
                    bind_form_actions();
                    
                    
                    //Local scan
                    $('#local_scan').click(function() {                                            
                        
                        //Local scan                 
                        $('#local_scan').click(function() {                                            
                            
                            top.frames['main'].local_scan();                           
                                                                                                
                        });                    
                    });                         
                    
                    <?php                   
                }
                ?>     
            });

        </script>
    </head>
    
    <body>
        
        <div id="av_info">
            
        
        
        </div>
        
        <div id="host_container">

            <table id="t_container">                
                                              
                <?php
                if ($is_ext_ctx == TRUE) 
                {
                    $config_nt = array(
                        'content' => _('Availabilty monitoring of this asset can only be modified at the USM:').' <strong>'.$external_ctxs[$ctx].'</strong>',
                        'options' => array (
                            'type'          => 'nf_warning',
                            'cancel_button' => TRUE
                        ),
                        'style'   => 'width: 80%; margin: auto; text-align:center;'
                    ); 
                    
                    $nt = new Notification('nt_1', $config_nt);
                    $nt->show(); 
                }
                else
                {
                    $title = _('Availability Monitoring');
                    
                    if (Session::menu_perms('environment-menu', 'ToolsScan'))
                    {
                        $t_local_scan  = _('The scan will run in frameworkd machine');
                        $ls_title      = _('Local Scan now');
                        
                        $title .= ' [ <a class="info" id ="local_scan" title="'.$t_local_scan.'">'.$ls_title.'</a> ]'; 
                    }                   
                    ?>                                    
                    
                    <!-- Title -->
                    <thead>
                        <tr>
                            <th class="headerpr"><?php echo $title?></th>
                        </tr>
                    </thead>
                        
                    <tbody>   
                                                
                        <tr>
                            <td>   
                                <table id="t_actions">
                                    <tr>
                                        <td class='left'>
                                            <a href="javascript:void(0);" id='check_all'><?php echo _('Check All')?></a>
                                            &nbsp;|&nbsp;
                                            <a href="javascript:void(0);" id='uncheck_all'><?php echo _('Uncheck All')?></a>
                                        </td>
                                        <td class='right'>
                                            <input type="button" class="small av_b_secondary" id="toggle_am" value="<?php echo _('Toggle Availability Monitoring')?>"/>
                                        </td>
                                    </tr>
                                </table>                       
                            </td>
                        </tr>
                        
                        <!-- Tree -->
                        <tr>
                            <td>
                                <div id="tree_container"></div>
                            </td>
                        </tr>                        
                                                   
                    </tbody>     
                    <?php
                }                          
                ?>             
            </table>
        </div>
    
    </body>
</html>