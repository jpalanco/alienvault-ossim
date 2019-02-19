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

$conf       = $GLOBALS["CONF"];

/* Version */

$version     = $conf->get_conf("ossim_server_version");

$opensource  = (!preg_match("/pro|demo/i",$version))  ? TRUE : FALSE;
$demo        = (preg_match("/.*demo.*/i",$version))   ? TRUE : FALSE;
$pro         = (preg_match("/.*pro.*/i",$version))    ? TRUE : FALSE;


/* Logo */

$logo_type = '';

if ($pro)
{    
    $logo_type .= '_siem';
}
elseif ($demo)
{    
    $logo_type .= '_siemdemo';                                                
}

$logo   = "ossim".$logo_type.'.png';


?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">
<html>
<head>
	<title> <?php echo _("AlienVault ".($opensource ? "OSSIM" : "USM"));?></title>
	<link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<style type='text/css'>
		
		body
		{
    		font-size: 120%;
    		background: #3C3C3C;
		}
		
		/* Links */
        a 
        {
            background: transparent;
            color:#16a7c9;
            text-decoration: none;
            font-weight: normal;
        }
        
        table
        {
            border:none;
        }
        
        a:hover
        {
            text-decoration: none;
        }
		
		#t_content
		{
			margin: 50px auto 0px auto; 
			padding:2px; 
        }
        
        .grey, span
        {
			color:#888 !important;
		}
		
		hr
		{
			height:1px;
			border:none;
			background-color:#888;
			color:#888;
		}
		
		span
		{
    		font-size: 120%;
		}
		
		ul
		{
    		margin: 10px 0px 20px 10px;
    		padding: 0px;
    		list-style-type: none;
    		list-style-position: inside;
        }
		
		ul li
		{
    		text-align: left;
    		content: 'a';
    		padding: 2px; 
    		font-size: 110%;
    	}
    	
    	li:before 
    	{ 
        	content: '\2192'; 
        	padding-right: 4px;
        	font-size: 120%;       	
    	}
    	
    	.p_help
    	{
        	text-align: center;
        	padding-top: 10px;
        	font-size: 110%;
        	margin: auto;
    	}
		
	</style>
</head>
<body>

<table id='t_content'>
    <tr>
        <td>
            <table cellpadding='0' cellspacing='0' border='0' align="center">
                <tr>
                    <td style="padding:20px 10px 0px 10px; text-align:center;">
                        <hr><br>
                        <img src="/ossim/pixmaps/<?php echo $logo?>" alt="<?php echo _("Logo")?>" border="0"/>						
                    </td>
                </tr>
                <tr>
                    <td class='left grey' style="padding: 10px;">            
                        <span style='font-size:170%'><?php echo _("We apologize")?>,</span>
                        <br/><br/>
                        <div style='margin: 5px 0px 0px 10px;'>
                            <span><?php echo _("An error occurred that prevented this page from being displayed.  This could be due to one of the following reasons")?>:</span>
                            <br/>
                            <ul>
                                <li><?php echo _("An internal error occurred")?></li>
                                <li><?php echo _("A URL was incorrectly entered")?></li>
                                <li><?php echo _("The file no longer exists")?></li>						      
                            </ul>
                        </div>
                        <hr>
                    </td>		
                </tr>
            </table>
        </td>
    </tr>
</table>

</body>
</html>

