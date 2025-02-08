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

function echo_risk($risk, $return = 0)
{

    $width = (12 * $risk["risk"]) + 1;

    #92d100 => green
    #ff8a00 => orange/yellow
    #fd0000 => red

    if(strpos($risk["risk_text"], "low") === 0){
        $img  = "<img src=\"../pixmaps/risk_green.jpg\" " . "width=\"$width\" height=\"15\" align=\"absmiddle\" />";
    }elseif (strpos($risk["risk_text"], "med") === 0){
        $img  = "<img src=\"../pixmaps/risk_yellow.jpg\" " . "width=\"$width\" height=\"15\" align=\"absmiddle\"/>";
    }
    else{
        $img  = "<img src=\"../pixmaps/risk_red.jpg\" " . "width=\"$width\" height=\"15\" align=\"absmiddle\"/>";
    }
    if ($return == 0)
    {
        echo "$img <span style='line-height:15px;'>".$risk["risk_text"]."</span>";
    }
    else
    {
        return "$img <span style='line-height:15px;'>".$risk["risk_text"]."</span>";
    }
}
