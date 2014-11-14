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


if ( $complete ){
	$nports = 20;  
} 
else{
	$nports = 10;
}


$ports = $data['port'];
arsort($ports );
$ports = array_slice($ports, 0 ,$nports, true);
ksort($ports);

if($data['rep'] > 0)
{
$otx   = round(($data['rep'] / count($data['ip']))*100);
}
else
{
$otx   = 0;
}

$flags = $data['country'];


?>

<script type='text/javascript'>
	
	$(document).ready(function(){


		//Tag Cloud Configuration
		$.fn.tagcloud.defaults = {
		  size: {start: 11, end: 22, unit: 'px'},
		  color: {start: '#B3C489', end: '#8DC740'}
		};


		//Carrousel
		var elems = <?php echo (count($data['country']) > 5) ? 5 : count($data['country']) ?>;
		carrousel_lite('<?php echo $prefix ?>',elems);

		$('#cloud_port<?php echo $prefix ?> a').tagcloud();
		
		//Knob
		$(".circle<?php echo $prefix ?>").easyPieChart({
			barColor: '#87CEEB',
			trackColor: '#DDDDDD',
			scaleColor: false,
			lineCap: 'butt',
			lineWidth: 10,
			animate: 1500,
			size:60
		});	

		
	});
	
</script>

<div style='position:absolute;top:5px; right:15px;'>
	<div class='percentage circle<?php echo $prefix ?>' data-percent="<?php echo $otx ?>"><span></span><?php echo $otx ?> %</div>
	<div style='padding-top:5px;'><a href='javascript:void(0);' onclick='show_otx();'><?php echo "<b>" .$otx . "</b>% " . _('in OTX') ?></a></div>
</div>

<div class='midbig line'>	
	<?php echo count($data['ip'])?> IPs
</div>

<div class='medium line'>	
	<img src='/ossim/alarm/style/img/location.png' height='17' align='absmiddle' style='padding-bottom:4px;'/> <?php echo _('Location') ?>
</div>

<div class='medium line'>	
	<div id='jCButton<?php echo $prefix ?>' class='carousel'>											
		<button id='prev<?php echo $prefix ?>' class='bprev av_b_transparent small'><<</button>
		<div id='jCarouselLite<?php echo $prefix ?>' class='jCarouselLite' >
			<ul>
				<?php
				foreach($flags as $icon => $f) 
				{
					$icon    = strtolower($icon);
					if(file_exists("/usr/share/ossim/www/pixmaps/flags/$icon.png"))
					{
						$flag = "/ossim/pixmaps/flags/$icon.png";
					}
					else
					{
						$flag = "/ossim/alarm/style/img/unknown.png";
					}
					$tooltip = "$f " . (($f == 1) ? _('Occurrence') : _('Occurrences') );
				?>
					<li>
						<div style='padding:5px;' title='<?php echo $tooltip ?>' class='flag_counter<?php echo $prefix ?>'>
							<a href="#"> <img src='<?php echo $flag ?>' width='16px' height='11px' /> </a>
						</div>
					</li>
				<? 
				} ?>
			</ul>
		</div>	
		<button id='next<?php echo $prefix ?>' class='bnext av_b_transparent small'>>></button>
	</div> 
	
</div>

<br><br>

<div class='medium line'>	
	<table class='transparent' width='100%'>
	<tr>
		<td class='medium' style='vertical-align:top;white-space:nowrap;text-align:left;width:1%;'>
			<img src='/ossim/alarm/style/img/port.png' height='15' align='absmiddle' style='padding-bottom:3px;'/> <?php echo _('Ports') ?>
		</td>
		<td>
			<div id="cloud_port<?php echo $prefix ?>" style='width:99%;text-align:center'>
				<?php
				foreach($ports as $port => $np) 
				{
					$tooltip = _('Port') . " $port - $np " . (($np == 1) ? _('Occurrence') : _('Occurrences') );
					$port    = ($port == 0) ? _('Unknown') : strtoupper(Port::port2service($conn, $port));
					
				?>
					<a href="#" class='alarm-help' style='text-decoration:none' title='<?php echo $tooltip ?>' rel="<?php echo $np ?>"><?php echo $port ?></a>
				<?php
				}
				?>
			</div>
		</td>
	</tr>
	</table>
</div>