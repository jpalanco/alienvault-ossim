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

Session::logcheck('environment-menu', 'MonitorsNetwork');
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<title><?php echo _('OSSIM');?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
		<meta http-equiv="Pragma" content="no-cache"/>
		<link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
		<link rel="stylesheet" type="text/css" href="/ossim/style/environment/profiles/common.css"/>
		<script src="/ossim/js/jquery.min.js"></script>
		
		<script type='text/javascript'>
    	       	    
    	    function resize() 
    	    {
                var content_h = $('#fr_down').contents().find('body').outerHeight(true);
                var iframe_h  = $('#fr_down').height();

                if(iframe_h != content_h)
                {
                    $('#fr_down').height(content_h);
                }

                setTimeout(function()
                {
                    resize();
                }, 1000);
    	    }
    	    
    	    $(document).ready(function() 
            {
    		    $('iframe').load(function() 
                {
                    $('#fr_down').height('');
                    
    				resize();
    		    });

    	    });
                
	    </script>
	</head>
<body>
	<?php
	
	$sensor  = GET('sensor');
	$opc     = GET('opc');
	$link_ip = GET('link_ip');   
    
	ossim_valid($sensor, OSS_ALPHA, OSS_NULLABLE, OSS_PUNC, OSS_SPACE, 'illegal:' . _('Sensor'));
    ossim_valid($link_ip, OSS_IP_ADDR, OSS_NULLABLE,                   'illegal:' . _('Link IP'));
	ossim_valid($opc, OSS_ALPHA, OSS_NULLABLE,                         'illegal:' . _('Default option'));
   
    if (ossim_error()) 
    {
        die(ossim_error());
    }
    
    $url   = array();
    $query = '';
    
    $url[] = ($sensor != '')  ? 'sensor='.$sensor   : '';
    $url[] = ($opc != '')     ? 'opc='.$opc         : '';
    $url[] = ($link_ip != '') ? 'link_ip='.$link_ip : '';
       
    if(count($url) > 0) 
    {
        $query = '?'.implode('&', $url);
    }

	if (ossim_error()) 
	{
		die(ossim_error());
	}	
	
	// Who sets the $ntop url variable
	require_once 'menu.php';
	?>
	<iframe id="fr_down" src="<?php echo $ntop ?>" name="ntop" style="width:100%" frameborder="0" scrolling="no"></iframe>
</body>
</html>