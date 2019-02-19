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

$si       = intval(GET('index'));
$sensors  = (isset($_SESSION['sensors'][$si])) ? $_SESSION['sensors'][$si] : "";
$type     = GET('type');
$location = base64_decode(GET('location'));

ossim_valid($location, OSS_ALPHA, OSS_PUNC_EXT, 'illegal: location');
ossim_valid($sensors, OSS_ALPHA, OSS_PUNC,      'illegal: sensors');
ossim_valid($type, OSS_DIGIT,                   'illegal: type');

if (ossim_error()) 
{
    die(ossim_error());
}


$db       = new ossim_db();
$conn     = $db->connect();
$networks = array();
$nets     = Wireless::get_wireless_aps_networks($conn,$type,$sensors);

if ($type==1) 
{
    // only networks with cloaked Yes and No
    foreach ($nets as $ne) {
        $yes = $no = 0;
        foreach ($ne['aps'] as $mac => $arr) 
		{
            if ($arr['cloaked']=='No')  $no=1;
            if ($arr['cloaked']=='Yes') $yes=1;
        }
        if ($yes && $no) $networks[] = $ne;
    }
}
elseif ($type==2) {
    // only networks with encryption None & Others
    foreach ($nets as $ne) {
        $yes = $no = 0;
        foreach ($ne['aps'] as $mac => $arr) {
            if ($arr['encryption']=='None') $no=1;
            if ($arr['encryption']!='None') $yes=1;
        }
        if ($yes && $no) $networks[] = $ne;
    }
} else {
    $networks = $nets;
}
$db->close();
$now  = date("Y-m-d H:i:s");
//
$pdf = new PDF_Table();
$pdf->AddPage();
$pdf->SetFont('Helvetica', 'B', 13);
$pdf->Image("../pixmaps/logo_siempdf.png",10,11,40);
if ($type == 1)
{
    $pdf->Cell(0, 17, _("Wireless / Cloaked Networks having uncloaked APs            "), 1, 1, 'R', 0);
}
elseif ($type == 2)
{
    $pdf->Cell(0, 17, _("Wireless / Encrypted networks having unencrypted APs        "), 1, 1, 'R', 0);
}
elseif ($type == 3)
{    
    $pdf->Cell(0, 17, _("Wireless / Networks using weak encryption                   "), 1, 1, 'R', 0);
}

$pdf->SetFont('Helvetica', '', 10);
$pdf->Cell(70,6,_("Location").": $location",1,0,'L');
$pdf->Cell(60,6,_("User").": ".$_SESSION["_user"],1,0,'L');
$pdf->Cell(60,6,_("Date").": $now",1,1,'R');
$pdf->SetWidths(array(38,14,18,14,12,13,25,18,18,20));
$pdf->SetAligns(array('C','C','C','C','C','C','C','C','C','C'));
$pdf->hh = 5;

foreach ($networks as $data) 
{
    $pdf->Ln();
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(190, 8, "  "._("Network SSID"),1,0,'L');
    $pdf->Ln();
    $pdf->SetFillColor(242,242,242);
    $pdf->Cell(190, 8, "  ".$data["ssid"],1,0,'L',1);
    $pdf->Ln();
    $pdf->Cell($pdf->widths[0], 8, _("MAC"),1,0,'C');
    $pdf->Cell($pdf->widths[1], 8, _("# Clients"),1,0,'C');
    $pdf->Cell($pdf->widths[2], 8, _("Type"),1,0,'C');
    $pdf->Cell($pdf->widths[3], 8, _("Channel"),1,0,'C');
    $pdf->Cell($pdf->widths[4], 8, _("Speed"),1,0,'C');
    $pdf->Cell($pdf->widths[5], 8, _("Cloaked"),1,0,'C');
    $pdf->Cell($pdf->widths[6], 8, _("Encryption"),1,0,'C');
    $pdf->Cell($pdf->widths[7], 8, _("1st Seen"),1,0,'C');
    $pdf->Cell($pdf->widths[8], 8, _("Last Seen"),1,0,'C');
    $pdf->Cell($pdf->widths[9], 8, _("Sensor"),1,0,'C');
    $pdf->Ln();
    $pdf->SetFont('Arial', '', 7);
    $i=0;
    
	foreach ($data['aps'] as $mac => $arr) 
	{
        if ($i++ % 2 != 0)
            $pdf->SetFillColor(255,255,255);
        else
            $pdf->SetFillColor(242,242,242);
        if ($type == 1 && $arr['cloaked']=="No") 
            $pdf->SetFillColor(239,224,224);
        elseif ($type == 2 && $arr['encryption']=="None")
            $pdf->SetFillColor(239,224,224);
        elseif ($type == 3 && $arr['encryption']=="WEP")
            $pdf->SetFillColor(239,224,224);
        $dat = array();
        $dat[] = $mac."\n".$arr["vendor"];
        $dat[] = $arr['clients'];
        $dat[] = $arr['nettype'];
        $dat[] = $arr['channel'];
        $dat[] = $arr['maxrate'].($arr['maxrate']!=0 ? " Mbps" : "");
        $dat[] = $arr['cloaked'];
        $dat[] = str_replace(","," ",$arr['encryption']);
        $dat[] = $arr['firsttime'];
        $dat[] = $arr['lasttime'];
        $dat[] = $arr['s_inet6_ntoa'];
        $pdf->Row($dat);
    }

}
//output the pdf, now we're done$pdf-
header('Content-Type: application/pdf');
header("Cache-Control: public, must-revalidate");
header("Pragma: hack");
$output_name = "Wireless_APs_".$type."_".$now.".pdf";
//header("Content-disposition:  attachment; filename=$output_name");
$pdf->Output($output_name,"I");
?>
