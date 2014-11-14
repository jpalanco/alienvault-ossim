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


$conf       = $GLOBALS['CONF'];
$ossim_link = $conf->get_conf('ossim_link');
$version    = $conf->get_conf('ossim_server_version');

$opensource = (Session::is_pro()) ? FALSE : TRUE;
$title      = _('AlienVault '.($opensource ? 'OSSIM' : 'USM'));
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title><?php echo $title;?></title>
	<link rel="Shortcut Icon" type="image/x-icon" href="favicon.ico">
	<link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
		
	<style type='text/css'>
		#t_container
		{
			padding:2px;
			background: #F2F2F2 !important;
			border-color:#AAAAAA;
			margin: 50px auto;
		}
		
		#t_info
		{
    		border-spacing: 2px;
    		background: transparent;
    		margin: auto;
    		border: solid 1px #CCCCCC;
		}
						
		#td_image
		{
			padding-top:20px;
			padding-left:10px;
			text-align:center;
		}
		
		#td_text
		{
			padding-top:20px;
			font-color:darkgray;
		}
		
		hr
		{
			height:1px;
			border:none;
			background-color:#D5D5D5;
			color:#D5D5D5;
		}
		
	</style>
	
	<script type='text/javascript'>
		var h_window;
        
        function show_help()
        {                                     
             var width  = 1024;
             var height = 768;
             
             var left = (screen.width/2)-(width/2);
             var top  = (screen.height/2)-(height/2);
             
             var w_parameters = "left="+left+", top="+top+", height="+height+", width="+width+", location=no, menubar=no, resizable=yes, scrollbars=yes, status=no, titlebar=no";
             
             var w_url  = 'https://www.alienvault.com/help/product/user_management_guide';
             var w_name = 'User Management Guide';
                      
             h_window = window.open(w_url, w_name, w_parameters);
             h_window.focus();            
        }
	</script>
	
</head>
<body>
    <table id='t_container'>
    	<tr>
    		<td class="nobborder">
    			<table id='t_info'>
    				<tr>
    					<td id="td_image" class="noborder">
                            <?php
                            $logo_url   = '/ossim/pixmaps/ossim';
                            $logo_title = _('OSSIM logo');
                            
                            if (Session::is_pro())
                            {
                                $logo_url  .= '_siem';
                                $logo_title = _('Alienvault Logo');
                            }                           
                            
                            $logo_url .= '.png';					       
                            ?>                       
                            <img src="<?php echo $logo_url?>" alt="<?php echo $logo_title?>" border="0"/>
    					</td>
    				</tr>
    				<tr>
    					<td id="td_text" align="center">
    						<span style='font-size:200%'><?php echo _('We apologize')?>:</span>
    						<br/>
    						<span class='bold' style='font-size:120%'><?php echo _('You do not have permission to access the current page')?></span>
    						<br/>
    						<hr/><br/>
    						<p align="justify" style="font-color:darkgray">
        						<?php echo _('You may try to check the user permissions.')?><br/><br/>
        						<?php echo _('If you have any doubts about how to navigate through <strong>AlienVault</strong> visit our')?> 
    							<a href="javascript:show_help();" class='bold'><?php echo _('Help')?></a>
    						</p>
    					</td>		
    				</tr>
    			</table>
    		</td>
    	</tr>
    </table>
</body>
</html>