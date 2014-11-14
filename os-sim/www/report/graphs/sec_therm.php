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


/*
* Based on script by: Sairam Suresh sai1138@yahoo.com / www.entropyfarm.org
* Thermbar pic courtesy http://www.rosiehardman.com/
*/


header("Content-Type: image/jpeg");
require_once 'av_init.php';
require_once 'classes/Util.inc';
require_once 'ossim_db.inc';
require_once 'ossim_conf.inc';

if ( !Session::menu_perms("dashboard-menu", "ControlPanelMetrics") ) 
{
  readfile("../../pixmaps/therm/therm_gray.jpg");
  exit();
}

$db = new ossim_db();
$conn = $db->connect();
$user = Session::get_session_user();
$conf = $GLOBALS['CONF'];
$font = $conf->get_conf('font_path');
$range = "day";
$sql = "SELECT c_sec_level, a_sec_level FROM control_panel WHERE id = ? AND time_range = ?";
$params = array(
    "global_$user",
    $range
);
if (!$rs = & $conn->Execute($sql, $params)) {
    die($conn->ErrorMsg());
}
//We want the opposite of the service level, if the service level is 100% the
//thermomether will be 0% (low temperature)
$level = ($rs->fields["c_sec_level"] + $rs->fields["a_sec_level"]) / 2;
$level = 100 - $level;
//$level = 30;
//Round the level
$level = intval($level);
//Select a different background color for the thermometer depending on the sec
//level
if ($level >= 80) {
    $img_ther = "../../pixmaps/therm/therm.jpg";
    $img_therbar = "../../pixmaps/therm/thermbar.jpg";
} elseif ($level >= 60) {
    $img_ther = "../../pixmaps/therm/therm_orange.jpg";
    $img_therbar = "../../pixmaps/therm/thermbar_orange.jpg";
} elseif ($level >= 40) {
    $img_ther = "../../pixmaps/therm/therm_yellow.jpg";
    $img_therbar = "../../pixmaps/therm/thermbar_yellow.jpg";
} elseif ($level >= 20) {
    $img_ther = "../../pixmaps/therm/therm_yellgre.jpg";
    $img_therbar = "../../pixmaps/therm/thermbar_yellgre.jpg";
} else {
    $img_ther = "../../pixmaps/therm/therm_green.jpg";
    $img_therbar = "../../pixmaps/therm/thermbar_green.jpg";
}
$t_unit = 'none';
$t_max = 100;
$t_current = $level;
$finalimagewidth = max(strlen($t_max) , strlen($t_current)) * 25;
$finalimage = imagecreatetruecolor(60 + $finalimagewidth, 405);
$white = imagecolorallocate($finalimage, 255, 255, 255);
$black = imagecolorallocate($finalimage, 0, 0, 0);
$red = imagecolorallocate($finalimage, 255, 0, 0);
$orange = imagecolorallocate($finalimage, 238, 120, 46);
$yellow = imagecolorallocate($finalimage, 252, 194, 0);
$yellgr = imagecolorallocate($finalimage, 179, 174, 8);
$green = imagecolorallocate($finalimage, 51, 142, 5);
imagefill($finalimage, 0, 0, $white);
imagealphablending($finalimage, true);
$thermImage = imagecreatefromjpeg($img_ther);
$tix = imagesx($thermImage);
$tiy = imagesy($thermImage);
//imagecopy($finalimage,$thermImage,0,0,0,0,$tix,$tiy);
imagecopy($finalimage, $thermImage, 17, 0, 0, 0, $tix, $tiy);
$thermbarImage = imagecreatefromjpeg($img_therbar);
$barW = imagesx($thermbarImage);
$barH = imagesy($thermbarImage);
$ybars = "";
$xpos = 22;
//$xpos = 5;
$ypos = 327;
$ydelta = 15;
$fsize = 8;
// Set number of $ybars to use, calculated as a factor of current / max.
if ($t_current > $t_max) {
    $ybars = 22;
} elseif ($t_current > 0) {
    $ybars = $t_max ? round(22 * ($t_current / $t_max)) : 0;
}
// Draw each ybar (filled red bar) in successive shifts of $ydelta.
while ($ybars--) {
    imagecopy($finalimage, $thermbarImage, $xpos, $ypos, 0, 0, $barW, $barH);
    $ypos = $ypos - $ydelta;
}
if ($t_current == $t_max) {
    imagecopy($finalimage, $thermbarImage, $xpos, $ypos, 0, 0, $barW, $barH);
    $ypos-= $ydelta;
}
//Write level indicators
imagettftext($finalimage, $fsize, 0, 70, 105, $black, $font, _("Very High"));
imagettftext($finalimage, $fsize, 0, 70, 165, $black, $font, _("High"));
imagettftext($finalimage, $fsize, 0, 70, 225, $black, $font, _("Elevated"));
imagettftext($finalimage, $fsize, 0, 70, 285, $black, $font, _("Precaution"));
imagettftext($finalimage, $fsize, 0, 70, 350, $black, $font, _("Low"));
//Write the percentage in the bulb
imagettftext($finalimage, 9, 0, 35, 376, $black, $font, $level . "%");
$value = array(
    2,
    19,
    7,
    101,
    27,
    101,
    27,
    19
);
imagefilledpolygon($finalimage, $value, 4, $red);
$value = array(
    7,
    102,
    12,
    162,
    27,
    162,
    27,
    102
);
imagefilledpolygon($finalimage, $value, 4, $orange);
$value = array(
    12,
    163,
    17,
    221,
    27,
    221,
    27,
    163
);
imagefilledpolygon($finalimage, $value, 4, $yellow);
$value = array(
    17,
    222,
    22,
    281,
    27,
    281,
    27,
    222
);
imagefilledpolygon($finalimage, $value, 4, $yellgr);
$value = array(
    22,
    282,
    27,
    348,
    27,
    348,
    27,
    282
);
imagefilledpolygon($finalimage, $value, 4, $green);
imagefilledellipse($finalimage, 65, 101, 6, 6, $red);
imagefilledellipse($finalimage, 65, 161, 6, 6, $orange);
imagefilledellipse($finalimage, 65, 221, 6, 6, $yellow);
imagefilledellipse($finalimage, 65, 281, 6, 6, $yellgr);
imagefilledellipse($finalimage, 65, 346, 6, 6, $green);
if ($t_current > $t_max) {
    $burstImg = imagecreatefromjpeg('burst.jpg');
    $burstW = imagesx($burstImg);
    $burstH = imagesy($burstImg);
    imagecopy($finalimage, $burstImg, 0, 0, 0, 0, $burstW, $burstH);
}
//Create the final image
imagejpeg($finalimage, NULL, 99);
//Destroy de rest of images
imagedestroy($finalimage);
imagedestroy($thermImage);
imagedestroy($thermbarImage);
?> 
