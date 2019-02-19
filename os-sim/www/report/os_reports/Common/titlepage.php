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

Session::logcheck('report-menu', 'ReportsReportServer');

//Get assets from Session
$assets = '<table class="w100" cellspacing="0" cellpadding="0">';

$cnd_1 = $_SESSION['_user_vision']['host_where'] && !Session::only_ff_host();
$cnd_2 = $_SESSION['_user_vision']['net_where'] && !Session::only_ff_net();


if ($cnd_1 || $cnd_2)
{    
    $db   = new ossim_db();
    $conn = $db->connect();
    
    $assets .='<tr>
                    <td style="text-align:left;width:25mm;font-size:10px;color:#535353;" valign="top">'.("Assets Selected:").'</td>
                    <td class="nobborder" style="padding-left:5px;font-size:10px" valign="top">
                        <table class="w100" cellpadding="0" cellspacing="0">';
											
						if ($_SESSION['_user_vision']['host_where'] && !Session::only_ff_host() )
						{
    						$_host_list = Asset_host::get_basic_list($conn);
    						$hosts = $_host_list[1];
    						
							foreach ($hosts as $host) 
    						{
    							$assets .='<tr><td class="nobborder" style="text-align:left;" valign="top">'._('Host').': '.$host['name'].' ['.$host['ips'].']</td></tr>';
    						}
						}

						if ($_SESSION['_user_vision']['net_where'] && !Session::only_ff_net())
						{
    						$nets = Asset_net::get_list($conn);
    						
    						$_net_list = Asset_net::get_list($conn);
    						$nets = $_net_list[0];
    						
							foreach ($nets as $net)
    						{
    							$assets .='<tr><td class="nobborder" style="text-align:left;" valign="top">'._('Net').': '.$net['name'].' ['.$net['ips'].']</td></tr>';
    						}
						}
	
	$assets .='     </table>
                </td>
            </tr>';

	$db->close();					
}
else 
{
    $assets .=  '<tr>
                    <td style="text-align:left;width:25mm;font-size:10px;color:#535353;">'._('Assets Selected:').'</td>
                    <td class="nobborder" style="padding-left:5px;text-align:left;font-size:10px" valign="top">'._('All Assets').'</td> 
                </tr>';
}

$assets .= '</table>';


$it_security    = '';
$address        = '';
$tlfn           = '';
$date_from      = POST($report_data['parameters'][0]['date_from_id']);
$date_to        = POST($report_data['parameters'][0]['date_to_id']);

ossim_valid($date_from, OSS_DATE, OSS_NULLABLE, 'illegal:' . _('Date From'));
ossim_valid($date_to,   OSS_DATE, OSS_NULLABLE, 'illegal:' . _('Date To'));

if (ossim_error())
{
    echo 'error###'.ossim_get_error_clean();
    exit;
}

$tz   = Util::get_timezone();
$date = gmdate('Y-m-d H:i:s', gmdate('U') + 3600 * $tz);

$maintitle = $report_data['report_name'];

// Font size of Title dinamic by text length
if (strlen($maintitle) > 40) 
{
	$font_size1 = '20';
	$font_size2 = '28';
} 
elseif (strlen($maintitle) > 25) 
{
	$font_size1 = '25';
	$font_size2 = '36';
} 
else 
{
	$font_size1 = '30';
	$font_size2 = '42';
}

$report_title = '<table class="w100" style="height:155mm" cellpadding="0" cellspacing="0">
					 <tr>
						<td style="width:180mm;height:165mm;text-align:center;font-size:'.$font_size2.'pt;">'.utf8_encode($maintitle).'</td>
					 </tr>
				 </table>';
				 

$htmlPdfReport->set(
    $report_title.
    '<table class="w100" cellpadding="0" cellspacing="5">
        <tr>
            <th style="width:25%">'._('I.T. Security').'</th>
            <td style="width:75%;background-color:#F2F2F2;">'.$it_security.'</td>
        </tr>
    </table>
    <br>
    <table class="w100" cellpadding="0" cellspacing="5">
        <tr>
            <th style="width:25%">'._('Address').'</th>
            <td style="width:75%;background-color:#F2F2F2;">'.$address.'</td>
        </tr>
    </table>
    <br>
    <table class="w100" cellpadding="0" cellspacing="5">
        <tr>
            <th style="width:25%">'._('Tel.').'</th><td style="width:25%;background-color:#F2F2F2;">'.$tlfn.'</td>
            <th style="width:25%">'._('Report Date').'</th><td style="width:25%;background-color:#F2F2F2;">'.$date.'</td>
        </tr>
    </table>
    <br>
    <table class="w100" cellpadding="0" cellspacing="5">
        <tr>
            <th style="width:25%">'._('Report Filter').'</th>
            <td style="width:75%;background-color:#F2F2F2;font-size:10px;"><span style="color:#535353">'._('Date from').': </span>'.$date_from.' <span style="color:#535353;margin-left:10px;">'._('Date to').': </span>'.$date_to.'</td>
        </tr>
        <tr>
            <td style="width:25%">&nbsp;</td>
            <td style="width:75%;background-color:#F2F2F2;">'.$assets.'</td>
        </tr>
    </table>
    ');    
?>
