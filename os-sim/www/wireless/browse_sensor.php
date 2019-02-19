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
Session::logcheck("environment-menu", "ReportsWireless");

require_once 'Wireless.inc';

$sensor = GET('sensor');
$date   = GET('date');
ossim_valid($sensor, OSS_IP_ADDR,           'illegal: sensor');
ossim_valid($date, OSS_DIGIT, OSS_NULLABLE, 'illegal: sensor');

if (ossim_error()) 
{
    die(ossim_error());
}

# sensor list with perms

if (count($_SESSION["_user_vision"]["sensor"]) > 0 && !$_SESSION["_user_vision"]["sensor"][$ip]) 
{
    echo ossim_error($_SESSION["_user"]." have not privileges for $sensor");    
    exit();
}

?>
<br>
<?php
$files = $browse = array();
// dir files
$path   = "/var/ossim/kismet/parsed/$sensor/";
$cmd    = "find ? -name '*xml' -printf '%TY%Tm%Td;%f\n' | sort -r | grep ?";
$params = array($path, $date);
$files  = explode("\n", Util::execute_command($cmd, $params, 'string'));
foreach ($files as $file) if (trim($file)!="") 
{
    $value = explode(";",trim($file));
    if ($date == '') 
    {    
        $date = $value[0];
    }
    
    if ($value[0] != $date) 
    {
        break;
    }
    
    $browse[] = $value[1];
}
?>

<table class="transparent" id='t_browse'>
	<tr>
		<td class="left" valign="top">
			<form>				
				<?php echo _("Browse available dates")?>: 
				<select name="date" id="combodates" onchange="browsexml('<?php echo $sensor?>',$('#combodates').val())">
					<?php
					$cmd    = "find ? -name '*xml' -printf '%TY%Tm%Td\n' | sort -r | uniq";
					$params = array($path);
					$dates  = explode("\n", Util::execute_command($cmd, $params, 'string'));
					
					$c_dates = 0;
					
					if (is_array($dates) && !empty($dates))
					{
    					foreach ($dates as $now) 
    					{
        					if (trim($now) != '') 
        					{
        						$fnow = preg_replace("/(\d\d\d\d)(\d\d)(\d\d)/","\\1-\\2-\\3",$now);
        						echo "<option value='$now'".($now == $date ? " selected='selected'": "").">$fnow</option>";
        						
        						$c_dates++;
        					}
    					}
					}
					
					if ($c_dates == 0)
					{
    					?>
    					<option value=""> -- <?php echo _("No dates")?> -- </option>
    					<?php
					}
					?>
				</select>
			</form>
			
									
			<table>
				<tr>
    				<th><?php echo _("File")?></th>
    				<th><?php echo _("Last Modified Date")?></th>
				</tr>	
				<?php
				$fdate = preg_replace("/(\d\d\d\d)(\d\d)(\d\d)/","\\1-\\2-\\3",$date);
				
				if (is_array($browse) && !empty($browse))
				{
				    foreach ($browse as $file) 
    				{
    					echo "<tr>
    							<td class='left'><a href=\"javascript:;\" onclick=\"viewxml('$file','$sensor')\">$file</a>
    								<a href=\"viewxml.php?file=".urlencode($file)."&sensor=".urlencode($sensor)."\" target='viewxml'><img src='../pixmaps/tables/table_edit.png' border='0' align='absmiddle'/></a>
    							</td>
    							<td>".date("Y-m-d H:i:s",filemtime($path.$file))."</td>
    						 </tr>";
    				}
				}
				else
				{
    				?>
    				<tr>
    				    <td colspan="2">
        				    <div class='center' style='padding: 5px; text-align:center;'>
        				        <?php echo ossim_error(_("No files available"))?>
        				    </div>    
    				    </td>
    				</tr>
    				<?php
				}				
				?>
			</table>
		</td>
		
		<td id="wcontainer" class="noborder left" valign="top"></td>
	</tr>
</table>
