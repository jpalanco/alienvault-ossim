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
//Session::logcheck("analysis-menu", "EventsForensics");

//header("Content-Type: image/png");

$bg_gray = "../pixmaps/bg_bar_gray.png";
$height = 16;
$width = 59;

$value = (is_numeric($_GET["value"])) ? $_GET["value"] : 0;
$value2 = (is_numeric($_GET["value2"])) ? $_GET["value2"] : -1;
$max = (is_numeric($_GET["max"])) ? $_GET["max"] : 10;
$grrange = ($_GET["range"] == "1") ? true : false;

$bluerange = array(
    "B5CDD7",
    "B5CDD7",
    "B0C8D3",
    "A9C2CE",
    "A1BAC6",
    "98B0BF",
    "8EA8B7",
    "849EAF",
    "7B95A8",
    "738DA0",
    "6C879B"
);
$greenredrange = array(
    "94CF05",
    "94CF05",
    "94CF05",
    "94CF05",
    "94CF05",
    "FF8A00",
    "FF8A00",
    "FF8A00",
    "FA0000",
    "FA0000",
    "FA0000"
);

$w = round(($value / $max) * ($width - 2) , 0);

if ($w > ($width - 2)) $w = $width - 2;
$index = ($value > 10) ? 10 : $value;
$color = ($grrange) ? $greenredrange[$index] : $bluerange[$index];

$red = hexdec(substr($color, 0, 2));
$green = hexdec(substr($color, 2, 2));
$blue = hexdec(substr($color, 4, 2));
/*
$fill = imagecolorallocate($im, $red, $green, $blue);

//$im = imagecreatefrompng($bg_gray);
*/
if ($value2<0) {
	//imagefilledrectangle($im, 1, 1, $w, $height - 2, $fill);
	//imagefilledrectangle($im, 1, 1, $w, imagesy($im)-3, $fill);
	
} else {
	$h = ($height - 2)/2;
	//imagefilledrectangle($im, 1, 1, $w, $h, $fill);
	//imagefilledrectangle($im, 1, 1, $w, imagesy($im)-3, $fill);
	// second bar
	$w2 = round(($value2 / $max) * ($width - 2) , 0);
	if ($w2 > ($width - 2)) $w2 = $width - 2;
	$index2 = ($value2 > 10) ? 10 : $value2;
	$color2 = ($grrange) ? $greenredrange[$index] : $bluerange[$index];
	$red2 = hexdec(substr($color2, 0, 2));
	$green2 = hexdec(substr($color2, 2, 2));
	$blue2 = hexdec(substr($color2, 4, 2));
	//$fill2 = imagecolorallocate($im, $red2, $green, $blue);
	//imagefilledrectangle($im, 1, $h+1, $w, $height - 2, $fill2);
	//imagefilledrectangle($im, 1, $h+1, $w, imagesy($im)-3, $fill2);
	//echo "w:$w color:$color w2:$w2 color2:$color2";
	//exit;
}

//imagepng($im);
//imagedestroy($im);

header("Content-Type: image/png");
$im = imagecreatefrompng($bg_gray);
if (empty($_GET['alpha'])) { $_GET['alpha'] = 10; }
$color = imagecolorallocatealpha($im, $red, $green, $blue, $_GET['alpha']);
if ($value2<0) {
	imagefillalpha($im, $color, $w);
} else {
	imagefillalpha($im, $color, $w, 1);
	$color2 = imagecolorallocatealpha($im, $red2, $green2, $blue2, $_GET['alpha']);
	imagefillalpha($im, $color2, $w2, 2);
}

$font = imageloadfont("proggyclean.gdf");
if ($value2<0) {
	$fontcolor = ($value > 5) ? $white : $black;
	$xx = ($value > 9) ? 10 : 3;
	imagestring($im, $font, ($width / 2) - $xx, 2, $value, $fontcolor);
	//imagestring($im, $font, 10, 2, $value."/".$max, $fontcolor);
} else {
	$txt = $value."->".$value2;
	$fontcolor = $black;
	$xx = ($value > 9) ? 20 : 15;
	imagestring($im, $font, ($width / 2) - $xx, 2, $txt, $fontcolor);
}

imagepng($im);
imagedestroy($im);

function ImageFillAlpha($image, $color, $w, $div = 0)
{
	if ($div == 1)
		imagefilledrectangle($image, 1, 1, $w, (imagesy($image)/2)-3, $color);
	elseif ($div == 2)
		imagefilledrectangle($image, 1, (imagesy($image)/2)-2, $w, imagesy($image)-3, $color);
	else
		imagefilledrectangle($image, 1, 1, $w, imagesy($image)-3, $color);
}




/*
$im = imagecreate($width, $height);
$white = imagecolorallocate($im, 255, 255, 255);
$black = imagecolorallocate($im, 0, 0, 0);
$gray = imagecolorallocate($im, 140, 140, 140);
// Create initial image w/borders
imagefilledrectangle($im, 0, 0, $width, $height, $white);
imagerectangle($im, 0, 0, $width - 1, $height - 1, $gray);
// bar
$w = round(($value / $max) * ($width - 2) , 0);
if ($w > ($width - 2)) $w = $width - 2;
$index = ($value > 10) ? 10 : $value;
$color = ($grrange) ? $greenredrange[$index] : $bluerange[$index];
$red = hexdec(substr($color, 0, 2));
$green = hexdec(substr($color, 2, 2));
$blue = hexdec(substr($color, 4, 2));
$fill = imagecolorallocate($im, $red, $green, $blue);
if ($value2<0) {
	imagefilledrectangle($im, 1, 1, $w, $height - 2, $fill);
} else {
	$h = ($height - 2)/2;
	imagefilledrectangle($im, 1, 1, $w, $h, $fill);
	// second bar
	$w = round(($value2 / $max) * ($width - 2) , 0);
	if ($w > ($width - 2)) $w = $width - 2;
	$index = ($value2 > 10) ? 10 : $value2;
	$color = ($grrange) ? $greenredrange[$index] : $bluerange[$index];
	$red = hexdec(substr($color, 0, 2));
	$green = hexdec(substr($color, 2, 2));
	$blue = hexdec(substr($color, 4, 2));
	$fill2 = imagecolorallocate($im, $red, $green, $blue);
	imagefilledrectangle($im, 1, $h+1, $w, $height - 2, $fill2);
}
// value
$font = imageloadfont("proggyclean.gdf");
if ($value2<0) {
	$fontcolor = ($value > 5) ? $white : $black;
	$xx = ($value > 9) ? 10 : 3;
	imagestring($im, $font, ($width / 2) - $xx, 2, $value, $fontcolor);
} else {
	$txt = $value."->".$value2;
	$fontcolor = $black;
	$xx = ($value > 9) ? 20 : 15;
	imagestring($im, $font, ($width / 2) - $xx, 2, $txt, $fontcolor);
}
*/
/*
header("Content-type: image/png");
imagepng($im);
imagedestroy($im);
imagedestroy($pattern);
*/
?>