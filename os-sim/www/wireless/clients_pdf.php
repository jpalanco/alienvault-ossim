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

// PDF
define('FPDF_FONTPATH','../pdf/font/');
require('../pdf/fpdf.php');
//
$location = base64_decode(GET('location'));
$si = intval(GET('index'));
$sensors = (isset($_SESSION['sensors'][$si])) ? $_SESSION['sensors'][$si] : "";
ossim_valid($sensors, OSS_ALPHA, OSS_PUNC,      'illegal: sensors');
ossim_valid($location, OSS_ALPHA, OSS_PUNC_EXT, 'illegal: location');

if (ossim_error()) 
{
    die(ossim_error());
}

$db          = new ossim_db();
$conn        = $db->connect();
$plugin_sids = Wireless::get_plugin_sids($conn);
$clients     = Wireless::get_wireless_clients($conn,"",$sensors,"");

$db->close();
$now  = date("Y-m-d H:i:s");

$pdf = new PDF_Table();
$pdf->AddPage();
$pdf->SetFont('Helvetica', 'B', 13);
$pdf->Image("../pixmaps/logo_siempdf.png",10,11,40);
$pdf->Cell(0, 17, _("Wireless / Suspicious clients                             "), 1, 1, 'R', 0);
$pdf->SetFont('Helvetica', '', 10);
$pdf->Cell(70,6,_("Location").": $location",1,0,'L');
$pdf->Cell(60,6,_("User").": ".$_SESSION["_user"],1,0,'L');
$pdf->Cell(60,6,_("Date").": $now",1,1,'R');
$pdf->Ln();
$pdf->SetWidths(array(28,23,22,15,23,10,15,15,30,9));
$pdf->SetAligns(array('C','C','C','C','C','C','C','C','C','C'));
$pdf->SetFont('Helvetica', '', 8);
$pdf->Cell($pdf->widths[0], 8, _("Client Name"),1,0,'C');
$pdf->Cell($pdf->widths[1], 8, _("MAC"),1,0,'C');
$pdf->Cell($pdf->widths[2], 8, _("IP Addr"),1,0,'C');
$pdf->Cell($pdf->widths[3], 8, _("Type"),1,0,'C');
$pdf->Cell($pdf->widths[4], 8, _("Encryption"),1,0,'C');
$pdf->Cell($pdf->widths[5], 8, _("WEP"),1,0,'C');
$pdf->Cell($pdf->widths[6], 8, _("1st Seen"),1,0,'C');
$pdf->Cell($pdf->widths[7], 8, _("Last Seen"),1,0,'C');
$pdf->Cell($pdf->widths[8], 8, _("Connected To"),1,0,'C');
$pdf->Cell($pdf->widths[9], 8, _("Attack"),1,0,'C');
$pdf->Ln();
$pdf->SetFont('Arial', '', 6);
$pdf->hh = 4;
$i=0;
$sids = array();

foreach ($clients as $arr) 
{
    $view = false;
    foreach ($arr['sids'] as $sid) if ($sid!=0 && $sid!=3 && $sid!=19) $view=true;
    if ($view) 
    {
        if ($i++ % 2 != 0)
            $pdf->SetFillColor(255,255,255);
        else
            $pdf->SetFillColor(242,242,242);
        $dat = array();
        $dat[] = $arr['name'];
        $dat[] = $arr['mac']."\n".$arr["vendor"];
        $dat[] = $arr['ip'];
        $dat[] = $arr['type'];
        $dat[] = str_replace(","," ",$arr['encryption']);
        $dat[] = $arr['encoding'];
        $dat[] = $arr['firsttime']; 
        $dat[] = $arr['lasttime'];
        $dat[] = implode("\n",$arr['connected']);
        $dat[] = $arr['sid'];
        foreach ($arr['sids'] as $sid) if ($sid!=0 && $sid!=3 && $sid!=19) $sids[$sid]++;
        $pdf->Row($dat);
    }
}
$pdf->Ln();
$pdf->SetFont('Helvetica', '', 10);
$pdf->SetFillColor(242,242,242);
$pdf->Cell(28,6,_("Attack"),1,0,'C',1);
$pdf->Cell(162,6," "._("Description"),1,0,'L',1);
$pdf->Ln();
$pdf->SetFillColor(255,255,255);
$pdf->SetFont('Arial', '', 8);
foreach ($sids as $sid => $val) {
    $plg = ($plugin_sids[$sid]!="") ? $plugin_sids[$sid] : $sid;
    $pdf->Cell(28,6,$sid,1,0,'C');
    $pdf->Cell(162,6," $plg",1,0,'L');
    $pdf->Ln();
}
//output the pdf, now we're done$pdf-
header('Content-Type: application/pdf');
header("Cache-Control: public, must-revalidate");
header("Pragma: hack");
$output_name = "Wireless_Suspicious_clients_".$now.".pdf";
//header("Content-disposition:  attachment; filename=$output_name");
$pdf->Output($output_name,"I");
?>
