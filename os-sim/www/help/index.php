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

Session::useractive("../session/login.php");

//Support contact
$v = Session::is_pro();

$s_c     = NULL;
$t_d     = Session::trial_days_to_expire();
$license = Session::get_system_license();

if ($v)
{
    if ($license !== FALSE)
    {        
        $s_email = 'trialsupport@alienvault.com';
    
        if($t_d > 31)
        {
            $s_email = 'support@alienvault.com';
        }
        
        $s_c = md5($s_email);
    }   
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title><?php echo _("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/notification.js"></script>
    <script type="text/javascript" src="../js/utils.js"></script>		
		 
	<style type='text/css'>
               
        #c_help
        {
            width: 98%;
            margin: 10px auto;
        }
        
        #fr_help
        {
            border: none;
            width: 100%;
            height: 500px;
            overflow: hidden;
            margin: auto;
        }
        
        .lnk_info, .lnk_info:hover
        {
            color: #00529B; 
            font-weight: bold;
        }
                
	</style>
	
	<script type='text/javascript'>
    	
    	 	
    	$(document).ready(function(){
    	    	
    	   //this function is in utils.js   
    	   if (is_internet_available() == false)
           {
                
                var content = "<div><?php echo _("Sorry, this section requires Internet Connection to display the contents")?></div>";
                
                <?php 
                if ($s_c !== NULL)
                {
                    ?>
                    content = "<div> \
                        <span><?php echo _("If you need help")?>, <a class='lnk_info' href='mailto:<?php echo $s_email?>'><?php echo  _("email Alienvault Support")?><a/></span> \
                        </div>";
                    
                    <?php                    
                }    
                ?>            
                
                var config_nt = {
                    content: content,
                    options: {
                        type:'nf_info',
                        cancel_button: false
                    },
                    style: 'width: 80%; margin: 150px auto; padding: 8px 0px; text-align:center; font-size: 13px;'
                };
                
                nt = new Notification('nt_1',config_nt);
                
                $('#c_help').html(nt.show());    
           }
           else
           {                         
                $('#fr_help').attr('src', 'https://www.alienvault.com/product/help/help.php?s=<?php echo $s_c?>');     
           }
                    	
    	});
    	
	</script>
	
</head>

<body> 
    <div id='c_help'>    
        <iframe id='fr_help' name='fr_help'></iframe>          
    </div>    
</body>

</html>