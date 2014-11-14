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

Session::logcheck("report-menu", "ReportsReportServer");

$host = GET('ip');
ossim_valid($host, OSS_IP_ADDR, 'illegal:' . _("Host"));	

if (ossim_error()) 
{
    die(ossim_error());
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title> <?php echo $title ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
</head>
<body>
<table class="noborder" style="background-color:transparent" width="250">
<tr>
	<td style="text-align:center">
	<a href="javascript:;" onclick="window.open('http://www.ripe.net/perl/whois?query=<?=$host?>');return false;">RIPE</a> | 
	<a href="javascript:;" onclick="window.open('http://whois.arin.net/rest/nets;q=<?=$host?>?showDetails=true&showARIN=false&ext=netref2');return false;">ARIN WHOIS-RWS</a> |
	<a href="javascript:;" onclick="window.open('http://lacnic.net/cgi-bin/lacnic/whois?lg=EN&query=<?=$host?>');return false;">LACNIC</a><br>
	<!--<a href="javascript:;" onclick="window.open('http://www.dnsstuff.com/tools/ipall/?ip=<?=$host?>');return false;">DNS</a> | 
	<a href="javascript:;" onclick="window.open('http://www.dnsstuff.com/tools/whois/?ip=<?=$host?>');return false;">Whois</a> | -->
	<a href="javascript:;" onclick="window.open('http://www.whois.sc/<?=$host?>');return false;">Extended whois</a> | 
	<a href="javascript:;" onclick="window.open('http://www.dshield.org/ipinfo.php?ip=<?=$host?>&amp;Submit=Submit');return false;">DShield.org IP Info</a> | <br>
	<a href="javascript:;" onclick="window.open('http://www.trustedsource.org/query.php?q=<?=$host?>');return false;">TrustedSource.org IP Info</a> | 
	<a href="javascript:;" onclick="window.open('http://www.projecthoneypot.org/ip_<?=$host?>');return false;">Project Honey Pot</a> | 
	<a href="javascript:;" onclick="window.open('http://www.spamhaus.org/query/bl?ip=<?=$host?>');return false;">Spamhaus.org IP Info</a> | 
	<a href="javascript:;" onclick="window.open('http://www.spamcop.net/w3m?action=checkblock&amp;ip=<?=$host?>');return false;">Spamcop.net IP Info</a> | <br>
	<a href="javascript:;" onclick="window.open('http://www.senderbase.org/senderbase_queries/detailip?search_string=<?=$host?>');return false;">Senderbase.org IP Info</a> | 
	<a href="javascript:;" onclick="window.open('http://isc.sans.edu/ipinfo.html?ip=<?=$host?>');return false;">ISC Source/Subnet Report</a> | 
	<a href="javascript:;" onclick="window.open('http://www.mywot.com/en/scorecard/<?=$host?>');return false;">WOT Security Scorecard</a> | <br>
	<a href="javascript:;" onclick="window.open('http://www.malwareurl.com/ns_listing.php?ip=<?=$host?>');return false;">MalwareURL</a> | 
	<a href="javascript:;" onclick="window.open('http://www.google.com/search?q=<?=$host?>');return false;">Google</a>
	</td>
</tr>
</table>
</body>
</html>
