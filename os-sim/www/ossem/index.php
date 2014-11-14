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

Session::useractive();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="">
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>">
	<link type="text/css" rel="stylesheet" href="../style/zoomy.css" />
	<style type='text/css'>
		#c_ossem{
			margin: 10px auto;
			width: 100%;
		}
		
		#c_ossem th{
			border-bottom: none !important;
			height: 28px !important;
			padding: 4px;
			font-size: 12px;
		}
		
		
	</style>
	<script type="text/javascript" src="../js/jquery.min.js"></script>
	<script type="text/javascript" src="../js/jquery.zoomy1.2.min.js"></script>
	<script type="text/javascript">
		
		$(function(){    
			$('.zoom').zoomy({zoomText: 'click it'})
		});
	</script>
</head>
<body>

<br/>	
	<div id='c_ossem'>
		<table cellpadding='0' cellspacing='0' class="noborder" style="width: 100%; background: transparent;">
			<tr>
				<td class="noborder" width="100%" >
					<table cellpadding='0' cellspacing='0' class="noborder" style="width: 100%; background: transparent;">
						<tr><th>AlienVault Logger: Secure Reliable Storage</th></tr>
						<tr>
							<td class="noborder" style="padding:0px;">
								<table width="100%" height="230" cellspacing="0" cellpadding="0">
									<tr>
										<td class="noborder" style="vertical-align: top; padding:5px 0px 0px 25px; text-align:left; max-width:500px;">
											<p>AlienVault Logger is a digitally secure forensic logging solution</p>
											<ul>
												<li>Digitally Signed and Encrypted Storage of Raw Data</li>
												<li>SAN/NAS Interoperability for Unlimited Scalability</li>
												<li>Encrypted Log Transport</li>
												<li>High Performance Data Capture</li>
											</ul>
											<p>
												<a href='http://alienvault.com/about/contact' target='_blank' title='Profesional SIEM'>
													This feature is only available in AlienVault USM Server. If you want to try it, please click here
												</a>
											</p>
										</td>
										<td class="noborder" style="padding:10px;">
											<a class="zoom" href="../pixmaps/sem/logger_open_1_zoom.png"><img src="../pixmaps/sem/logger_open_1.png" /></a>
										</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr><td class="noborder" style="background: transparent;">&nbsp;</td></tr>
						<tr><th>AlienVault Logger Features</th></tr>
						<tr>
							<td class="noborder" style="padding:0px">
								<table width="100%" height="230" cellspacing="0" cellpadding="0" >
									<tr>
										<td class="noborder" style="vertical-align: top; padding:5px 0px 0px 25px; text-align:left; max-width:500px;">
											<p>The AlienVault Logger is a forensically-secure solution to long term storage of raw log data. Log addresses security, legal and compliances needs through:</p>
											<ul>
												<li>Digital Signatures ensures data integrity</li>
												<li>Encrypted Transport ensures Chain-of-Custody</li>
												<li>10:1 Compression saves valuable space</li>
												<li>SAN/NAS Interoperability allows for limitless scalability<br />
												When regulatory requirements or legal liability demand a forensically-secure record, the AlienVault Logger provides the verifiable solution.</li>
											</ul>
											<p>
												<a href='http://alienvault.com/about/contact' target='_blank' title='Profesional SIEM'>
													This feature is only available in AlienVault USM Server. If you want to try it, please click here
												</a>
											</p>
										</td>
										<td class="noborder" style="padding:10px;">
											<a class="zoom" href="../pixmaps/sem/logger_open_2_zoom.png"><img src="../pixmaps/sem/logger_open_2.png"/></a>
										</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr><td class="noborder" style="background: transparent;">&nbsp;</td></tr>
						<tr><th>Highlights</th></tr>
						<tr>
							<td class="noborder" style="padding:0px">
								<table width="100%" height="230" cellspacing="0" cellpadding="0">
									<tr>
										<td class="noborder" style="vertical-align: top; padding:5px 0px 0px 25px; text-align:left; max-width:500px;">
											<ul>
												<li>Full Forensic Lifecycle Management:<br/>from Collection to Storage to Destruction</li>
												<li>Digitally Signed and Time-Stamped</li>
												<li>Forensic Auditing & Analysis Tools</li>
												<li>Military-Grade Data Destruction</li>
												<li>High-Performance Architecture</li>
												<li>Unlimited Scalability</li>
												<li>Professional Services Provided by AlienVault and its Global Network of Partners</li>
											</ul>
											<p>
												<a href='http://alienvault.com/about/contact' target='_blank' title='Profesional SIEM'>
													This feature is only available in AlienVault USM Server. If you want to try it, please click here
												</a>
											</p>
										</td>
										<td class="noborder" style="padding:10px;">
											<a class="zoom" href="../pixmaps/sem/logger_open_3_zoom.png"><img src="../pixmaps/sem/logger_open_3.png"/></a>
										</td>
									</tr>
								</table>
							</td>
						</tr>
						<tr><td class="noborder" style="background: transparent;">&nbsp;</td></tr>
						<tr><th>Forensic Lifecycle Management</th></tr>
						<tr>
							<td class="noborder" style="padding:0px">
								<table width="100%" height="230" cellspacing="0" cellpadding="0">
									<tr>
										<td class="noborder" style="vertical-align: top; padding-top:5px; text-align:left; max-width:500px;">
											<p style="text-align:justify;">The increasing risk of being compelled to produce legally accurate records and the rising tide of regulatory compliance have driven the need for reliable, forensically secure log storage. Industry and government mandates dictate that certain types of data be stored intact for specified periods of time. Corporate governance dictates that given types of records be verifiably destroyed after their storage period is complete.</p>
											<p style="text-align:justify;">AlienVault Logger is a turnkey solution that works seamlessly with AlienVault SIEM to provide secure storage and full lifecycle management of event data. AlienVault Logger’s cryptographic storage and military-grade data purging provide the stability of knowing that you are keeping records precisely as dictated by policy.</p>
											<p style="text-align:justify;">With massive internal storage and compatibility with SAN and NAS storage systems AlienVault Logger can manage any volume of data over any span of time.</p>
											<p style="text-align:justify;">AlienVault Logger supports encrypted transport to ensure that the data stored remains unchanged from creation to destruction.</p>
											<p>
												<a href='http://alienvault.com/about/contact' target='_blank' title='Profesional SIEM'>
													This feature is only available in AlienVault USM Server. If you want to try it, please click here
												</a>
											</p>
										</td>
										<td class="noborder" style="padding:10px;">
											<a class="zoom" href="../pixmaps/sem/logger_open_4_zoom.png"><img src="../pixmaps/sem/logger_open_4.png"/></a>
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</td>
				<td class="noborder" width="15">&nbsp;</td>
			</tr>
		</table>
	</div>
</body>
</html>