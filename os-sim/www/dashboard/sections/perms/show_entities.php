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

Session::logcheck('dashboard-menu', 'ControlPanelExecutive');

$pro = Session::is_pro();

if(!$pro) 
{
	$config_nt = array(
        'content' 		=> _('Only professional versions can access to this section'),
        'options' 		=> array (
            'type'          => 'nf_error',
            'cancel_button' => FALSE
        ),
        'style' => 'margin: 100px auto; width: 80%; text-align: center;'
	);

	$nt = new Notification('nt_1', $config_nt);
	$nt->show();

	exit();
}


$data = GET('data');

$data = explode(';', ($data));
	
$id   = $data[1];
$from = $data[0];

ossim_valid($id	,  OSS_DIGIT, 	'illegal:' . _('Tab ID'));
ossim_valid($from, OSS_USER, 	'illegal:' . _('User'));


if (ossim_error())
{
    die(ossim_error());
}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
		<meta http-equiv="Pragma" content="no-cache"/>

        <?php
            //CSS Files
            $_files = array(
                array('src' => 'av_common.css?only_common=1',       'def_path' => TRUE),
                array('src' => 'jquery-ui.css',                     'def_path' => TRUE),
                array('src' => 'tree.css',                          'def_path' => TRUE)
            );
            
            Util::print_include_files($_files, 'css');

            //JS Files
            $_files = array(
                array('src' => 'jquery.min.js',            'def_path' => TRUE),
                array('src' => 'jquery-ui.min.js',         'def_path' => TRUE),
                array('src' => 'jquery.dynatree.js',       'def_path' => TRUE),
                array('src' => 'utils.js',                 'def_path' => TRUE),
                array('src' => 'token.js',                 'def_path' => TRUE)
            );
            
            Util::print_include_files($_files, 'js');
        ?>

		<style type='text/css'>		
			
			body
			{
        		background: #333333 !important;
        		opacity: .90 !important;
    		}
			
			.dynatree-container
			{
				background-color: transparent !important;
				color: white;
				border:none !important;
				-moz-border-radius: 5px;
				-khtml-border-radius: 5px; 
				-webkit-border-radius: 5px;
				border-radius: 5px;
				height:213px;
				overflow:auto;
				margin-top:5px;
			}

			.dynatree-title 
			{
				color: white !important;
			}

			ul.dynatree-container a:hover
			{
				background: #444444;
			}

			span.dynatree-focused a:link
			{
				background-color: #444444;
			}

			span 
			{
				font-weight:normal !important;
			}
			
		</style>
		
		<script type='text/javascript'>		

            var layer = '#tree';
    		
    		function load_tree()
            {
                $(layer).dynatree(
                {
                    initAjax: 
                    { 
    					url: "/ossim/tree.php?key=entities"					
    				},
    				selectMode: 1,
    				onActivate: function(node) 
                    {
    					var key = node.data.key;
    					if (key.match(/^e_/)) 
    					{	
    						key        = key.replace("e_","");

                            var ctoken = Token.get_token("dashboard_perms_ajax");
    						$.ajax(
                            {
    							data:  {"action": "clone_tab_entity", "data":  {"from": "<?php echo $from ?>", "to": key, "panel": <?php echo $id ?>}},
    							type: "POST",
    							url: "perms_ajax.php?&token="+ctoken,
    							dataType: "json",
    							success: function(data)
                                { 
                                    var session = new Session(data, '');
    
                                    if (session.check_session_expired() == true)
                                    {
                                        session.redirect();
                                        return;
                                    }


									if(data.error)
                                    {
										if (typeof(parent.show_notification) == 'function')
                                        {
											parent.show_notification(data.msg,'nf_error');	
                                        }

										if (typeof(parent.CB_hide) == 'function')
                                        {
											parent.CB_hide();
                                        }
									} 
                                    else
                                    {
										parent.location.href='index.php';										
									}
    							},
    							error: function(XMLHttpRequest, textStatus, errorThrown) 
                                {
									if (typeof(parent.show_notification) == 'function')
                                    {
										parent.show_notification(textStatus,'nf_error');	
                                    }

									if (typeof(parent.CB_hide) == 'function')
                                    {
										parent.CB_hide();
                                    }
    							}
    						});
    					} 
    				}
                });                     
            }
    		
    		$(document).ready(function()
            {
    			load_tree();					
    		});
		
		</script>
	</head>
	
	<body>
	    <div class="transparent" id="tree" style='margin:0 auto;width:98%;height:100%;text-aling:center;overflow:auto;'></div>
	</body>	
</html>