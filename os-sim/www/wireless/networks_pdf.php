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
$order = GET('order');
$location = base64_decode(GET('location'));
$si = intval(GET('index'));
$sensors = (isset($_SESSION['sensors'][$si])) ? $_SESSION['sensors'][$si] : "";
ossim_valid($order, OSS_ALPHA, OSS_NULLABLE, 'illegal: order');
ossim_valid($sensors, OSS_ALPHA, OSS_PUNC, 'illegal: sensors');
ossim_valid($location, OSS_ALPHA, OSS_PUNC_EXT, 'illegal: location');
if (ossim_error()) 
{
    die(ossim_error());
}


$db   = new ossim_db();
$conn = $db->connect();

$networks = Wireless::get_wireless_networks($conn,$order,$sensors);

$db->close();
$now  = date("Y-m-d H:i:s");
//
$pdf = new PDF_Table();
$pdf->AddPage();
$pdf->SetFont('Helvetica', 'B', 13);
$pdf->Image("../pixmaps/logo_siempdf.png",10,11,40);
$pdf->Cell(0, 17,  _("    Wireless / Networks"), 1, 1, 'C', 0);
$pdf->SetFont('Helvetica', '', 10);
$pdf->Cell(70,6,_("Location").": $location",1,0,'L');
$pdf->Cell(60,6,_("User").": ".$_SESSION["_user"],1,0,'L');
$pdf->Cell(60,6,_("Date").": $now",1,1,'R');
$pdf->Ln();
$pdf->Cell(37, 8, _("Network SSID"),1,0,'C');
$pdf->Cell(15, 8, _("# of APs"),1,0,'C');
$pdf->Cell(15, 8, _("# Clients"),1,0,'C');
$pdf->Cell(18, 8, _("Type"),1,0,'C');
$pdf->Cell(20, 8, _("Encryption"),1,0,'C');
$pdf->Cell(15, 8, _("Cloaked"),1,0,'C');
$pdf->Cell(20, 8, _("1st Seen"),1,0,'C');
$pdf->Cell(20, 8, _("Last Seen"),1,0,'C');
$pdf->Cell(30, 8, _("Description"),1,0,'C');
$pdf->Ln();
$pdf->SetFont('Arial', '', 8);
$i=0;
$pdf->SetWidths(array(37,15,15,18,20,15,20,20,30));
$pdf->SetAligns(array('C','C','C','C','C','C','C','C','L'));

foreach ($networks as $data) 
{
	if ($i++ % 2 == 0)
	{
		$pdf->SetFillColor(255,255,255);
	}
	else
	{
		$pdf->SetFillColor(242,242,242);
	}
	
	$dat = array();
	$dat[] = utf8_encode($data['ssid']);
	$dat[] = $data['aps'];
	$dat[] = $data['clients'];
	$dat[] = $data['type'];
	$dat[] = str_replace(","," ",$data['encryption']);
	$dat[] = $data['cloaked'];
	$dat[] = $data['firsttime'];
	$dat[] = $data['lasttime'];
	$dat[] = utf8_encode($data['description']);
	$pdf->Row($dat);
}
//output the pdf, now we're done$pdf-
header('Content-Type: application/pdf');
header("Cache-Control: public, must-revalidate");
header("Pragma: hack");
$output_name = "Wireless_Networks_".$now.".pdf";
//header("Content-disposition:  attachment; filename=$output_name");
$pdf->Output($output_name,"I");
?>
