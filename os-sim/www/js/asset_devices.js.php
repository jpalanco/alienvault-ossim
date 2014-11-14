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
    

$db   = new ossim_db();
$conn = $db->connect();

$device_obj = new Devices($conn);
$devices    = $device_obj->get_devices();

$db->close();
?>


/****************************************************
 **************** Device functions ******************
 ****************************************************/
 

var device_types = new Array();
var device_subtypes = new Array();
            
<?php
foreach ($devices as $dt_id => $dt_data)
{
    ?>
    device_types[<?php echo $dt_id?>]    = "<?php echo $dt_data['name']?>";
    device_subtypes[<?php echo $dt_id?>] = new Array();
    <?php
        
    foreach ($dt_data['subtypes'] as $dst_id => $dst_name)
    {
        ?>
        device_subtypes[<?php echo $dt_id?>][<?php echo $dst_id?>] = "<?php echo $dst_name?>";
        <?php
    } 
}
?>
    		



function add_device() 
{
    var val = $('#device_type').val();
    var txt = $('#device_type option:selected').text();
    
    var device_subtype = $('#device_subtype').val();    
    
    if (device_subtype != '' && device_subtype != '0') 
    {
    	val += ":" + device_subtype
    	txt += ":" + $('#device_subtype option:selected').text();
    }
    
    $('#devices').append('<option value="'+val+'">'+txt+'</option>');
}


function fill_device_types() 
{
    $('#device_type').empty();
	$('#device_type').append('<option value="0">-</option>');
		
	for (var i in device_types) 
	{
		$('#device_type').append('<option value="'+i+'">'+device_types[i]+'</option>');
	}	
}


function fill_device_subtypes() 
{
	var dt_id = $('#device_type').val();
	            	            	            	
	if (dt_id > 0) 
	{
		$('#device_subtype').empty();
		$('#device_subtype').append('<option value="0">-</option>');
		
		if (typeof(device_subtypes[dt_id]) != "undefined") 
		{
			for (var i in device_subtypes[dt_id]) 
			{
				$('#device_subtype').append('<option value="'+i+'">'+device_subtypes[dt_id][i]+'</option>');
			}
		}
	}
}


function bind_device_actions()
{
    $('#device_type').change(function(){
        fill_device_subtypes();
    });
    
    $('#add_device').click(function(){
        add_device();
    });
    
    $('#delete_device').click(function(){
        deletefrom('devices');
    });
    
    //Fill type and subtype devices at first
    fill_device_types();
    
    fill_device_subtypes();
}

 