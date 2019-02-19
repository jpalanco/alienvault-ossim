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

require_once dirname(__FILE__).'/../../conf/config.inc';

Session::logcheck('environment-menu', 'EventsHidsConfig');


$sensor_id = POST('sensor_id');

ossim_valid($sensor_id, OSS_HEX,  'illegal:' . _('Sensor ID'));

if (!ossim_error())
{
    $db    = new ossim_db();
    $conn  = $db->connect();
    
    if (!Ossec_utilities::is_sensor_allowed($conn, $sensor_id))
    {
        ossim_set_error(_('Error! Sensor not allowed'));
    }
    
    $db->close();
}


if (ossim_error())
{
	echo '2###'._('We found the followings errors').": <div style='padding-left: 15px; text-align:left;'>".ossim_get_error_clean().'</div>';
	exit();
}


//Current sensor
$_SESSION['ossec_sensor'] = $sensor_id;
		
echo '1###';

try
{
    $rules = Ossec::get_rule_files($sensor_id, FALSE);
    
    $options_e  .=  "<optgroup label='"._('Editable rule file')."'>\n";
	$options_ne .=  "<optgroup label='"._('Rules files read-only')."'>\n";
		
	foreach ($rules as $rule)
	{						
		if (Ossec::is_editable($rule))
		{
			$options_e .= "<option style='text-align: left;' value='$rule'>$rule</option>\n";
		}
		else		
		{
			$options_ne .= "<option style='text-align: left;' value='$rule'>$rule</option>\n";
		}
	}
	
	$options_e  .= "</optgroup>\n";	
	$options_ne .= "</optgroup>\n";	
	
	$rule_options = $options_e."\n".$options_ne;
}
catch(Exception $e)
{
    $rule_options = "<option value=''>"._('No rule files found')."</option>";
}
    
    
?>
<div id='tree_container_top'>						
    <select id='rules' name='rules'>
        <?php echo $rule_options;?>
    </select>
</div>

<div id='tree_container_bt'></div>
