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


/**
* Function list:
* - RemoveExtension()
*/
require_once 'av_init.php';

require_once AV_MAIN_ROOT_PATH . '/av_center/config.inc';


function RemoveExtension($strName, $strExt) 
{
    if (substr($strName, strlen($strName) - strlen($strExt)) == $strExt ) 
    {
		return substr($strName, 0, strlen($strName) - strlen($strExt));
	}
    else
    {
		return $strName;
	}
}

$range = GET('range');

ossim_valid($range, "day", "week", "month", "year", OSS_NULLABLE, 'illegal:' . _("range"));

$valid_range = array(
    'day',
    'week',
    'month',
    'year'
);

if (!$range) 
{
    $range = 'day';
} 
elseif (!in_array($range, $valid_range)) 
{
    die(ossim_error('Invalid range'));
}

$end = gmdate("U");

if ($range == 'day') 
{
    $start = gmdate("U")-86400;
} 
elseif ($range == 'week') 
{
    $start = gmdate("U")-(86400*7);
}
elseif ($range == 'month') 
{
    $start = gmdate("U")-(86400*30);
}
elseif ($range == 'year') 
{
    $start = gmdate("U")-(86400*365);
}

$start_acid = date("Y-m-d H:i:s", $start);
$end_acid   = date("Y-m-d H:i:s", $end);

// Get conf
$conf        = $GLOBALS['CONF'];
$rrdtool_bin = $conf->get_conf('rrdtool_path') . "/rrdtool";
$rrdpath     = "/var/lib/ossim/rrd/event_stats/";


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>

<head>
    <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    <style type="text/css">
        
        html, body
        {
            min-width: 650px !important;
        }
        
        .bold   { font-weight: bold }
        
        .normal { font-weight: normal }
    </style>
</head>

<body>

	<table style="margin-top:10px auto;" border="0" width="600" align="center">
		<tr height="30">
			<th>
			<?php echo _("EPS Trend")?> &nbsp;&nbsp;&nbsp;&nbsp;
			[
			<a href="?range=day"<?php echo ($range=="day") ? ' class="bold"' : ' class="normal"'?>>Last day</a> | 
			<a href="?range=week"<?php echo ($range=="week") ? ' class="bold"' : ' class="normal"'?>>Last week</a> |  
			<a href="?range=month"<?php echo ($range=="month") ? ' class="bold"' : ' class="normal"'?>>Last month</a> | 
			<a href="?range=year"<?php echo ($range=="year") ? ' class="bold"' : ' class="normal"'?>>Last year</a>
			]
			</th>
		</tr>
		<tr>
			<td class="noborder" style="text-align:center;">
				<?php echo _("From")?>: <b><?php echo $start_acid?></b> - <?php echo _("To")?>: <b><?php echo $end_acid?></b>
			</td>
		</tr>
	</table>

	<table style="margin:10px auto 20px auto;" border="0" width="600" align="center">
	<?php
	// Open dir and get files list
	if (is_dir($rrdpath)) 
	{
		$db      = new ossim_db();
		$conn    = $db->connect();
				
		if ($gestordir = opendir($rrdpath)) 
		{
			$i = 0;
			$nrrds = 0;
			$rrds = array();
			
			while (($rrdfile = readdir($gestordir)) !== false) 
			{
				if (strcmp($rrdfile, "..") == 0 || strcmp($rrdfile, ".") == 0) 
				{
					continue;
				}
				
				$file_date = @filemtime($rrdpath . DIRECTORY_SEPARATOR . $rrdfile);
				// Get files list modified after start date
				if (isset($start) && ($file_date !== false) && ($file_date > $start)) 
				{
					// Draw graph
					$id          = RemoveExtension($rrdfile, ".rrd");
					$entity_type = Session::get_entity_type($conn, $id);
										
					if ($entity_type == 'context') 
					{
						// Ignore engines and Logical entities
						?>
						<tr>
							<td style='padding-bottom:10px' align='center'>
							<center>
								<h4><i><?php echo Session::get_entity_name($conn, $id);?></i></h4>
								<img src="<?php echo "../report/graphs/draw_rrd.php?id=$id&what=eps&start=$start&end=$end&type=eps"; ?>" border='0'/>
							</center>
							</td>
						</tr>
						<?php
			        }
				}
			}
			closedir($gestordir);
		}
		
		$db->close();
	}
	?>
	</table>
</body>

</html>
