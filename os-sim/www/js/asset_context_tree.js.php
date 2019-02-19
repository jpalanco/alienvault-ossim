<?php
header("Content-type: text/javascript");

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


/****************************************************
*************** Context and Sensors ****************
****************************************************/

if (Session::is_pro() && Session::show_entities()) 
{ 
    ?>       
	function disable_sensors(entity_id) 
	{
		$('.sensor_check').attr('disabled', 'disabled');
		$('.'+entity_id).removeAttr('disabled');
	}
   
    function check_sensors(entity_id) 
    {
    	$('.sensor_check').removeAttr('checked');
    	$('.'+entity_id).attr('checked', 'checked');
	}       
   
    function load_tree_context(context_type)
	{
		var url = "<?php echo AV_MAIN_PATH?>/tree.php?key=contexts&extra_options="+context_type;
	            	        		
		$("#tree").dynatree({
			initAjax: { url: url },
			clickFolderMode: 2,
			onActivate: function(dtnode) {
				var key = dtnode.data.key.replace(/e_/, "");
				
				if (key != "") 
				{
					$('#ctx').val(key);
					$('#entity_selected').html("<?php echo _('Context selected')?>: <strong>"+dtnode.data.val+"</strong>");
					
					disable_sensors(key);
					
					check_sensors(key);
					
					//Trigger change event for AJAX Validator
					$('input[name^="sboxs"]').trigger('change');
					
					$('#ctx').trigger('change');
				}
			}
        });
	}
    <?php
}          
?>
