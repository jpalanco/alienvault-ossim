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

require_once ('av_init.php');

Session::logcheck("environment-menu", "PolicyHosts");

$id = GET('id');

ossim_valid($id, OSS_HEX, 'illegal:' . _("Host ID"));

if (ossim_error())
{ 
    die(ossim_error());
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo _("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
	<meta http-equiv="Pragma" content="no-cache"/>
	
	<script type="text/javascript" src="/ossim/js/jquery.min.js"></script>
	<script type="text/javascript" src="/ossim/js/utils.js"></script>
	<script type="text/javascript" src="/ossim/js/messages.php"></script>
	<!-- Dynatree libraries: -->
	<script type="text/javascript" src="/ossim/js/jquery.cookie.js"></script>
	<script type="text/javascript" src="/ossim/js/jquery-ui.min.js"></script>
	<script type="text/javascript" src="/ossim/js/jquery.tmpl.1.1.1.js"></script>
	<script type="text/javascript" src="/ossim/js/jquery.dynatree.js"></script>
	<script type="text/javascript" src="/ossim/js/combos.js"></script>
																	
	<link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<link rel="stylesheet" type="text/css" href="/ossim/style/tree.css"/>

    <style>
       
        #tree_container_prop
        {
            margin:15px; 
        }
        
        ul.dynatree-container
        {
            border: none;
        }
       
    </style>
	
	<script type="text/javascript">

		function load_tree(container, host_id)
		{
			var layer = '#tree_prop';

            if ($(layer).length < 1)
            {
                $('#'+container).append('<div id="tree_prop" style="width:100%"></div>');
            }
            else
            {
                $(layer).dynatree("destroy");
            }   
            
			$(layer).dynatree(
			{
				initAjax: 
				{
    				url: '/ossim/av_tree.php',
    				data: 
    				{
                       "key": "property_tree",
                       "filters": {"host_id": host_id},
                       "max_text_length": "50"
                    }
				},
				minExpandLevel: 2,
				clickFolderMode: 1,
				cookieId: "dynatree_1",
				onClick: function(node, event) {},
				onSelect: function(select, node) {}	
			});
		}
		
		$(document).ready(function()
		{
			load_tree('tree_container_prop', '<?php echo $id ?>');
		});
		

	</script>
		
</head>

<body id='body_scroll'>

    <div>	
        <div id='tree_container_prop'></div>
    </div>

</body>
</html>

