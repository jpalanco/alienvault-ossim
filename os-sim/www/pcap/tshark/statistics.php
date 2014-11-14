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
Session::logcheck("environment-menu", "TrafficCapture");

require_once("includes/tshark.inc");
?>
<script src="js/jquery.min.js" type="text/javascript"></script>
<script src="js/raphael/raphael.js" charset="utf-8"></script>
<script src="js/raphael/popup.js" type="text/javascript"></script>
<script src="js/raphael/drawgrid.js" type="text/javascript"></script>
<script type="text/javascript">
clearselbutton=function(){
    $.each($("buttonlink").context.activeElement.childNodes[0].children, function(index, value) { 
      value.className = 'buttonlink';
    });
};
</script>


<link href="style/sharkvault.css" type="text/css" rel="stylesheet" />
<?php

$tshark = unserialize($_SESSION['TSHARK_tshark']);
$filter = $tshark->get_filter();
?>

<div id="buttom" style='text-align:center; padding-top:25px; margin-left:10px; margin-right:10px;'>
<a class="buttonlink" style="text-decoration:none" onclick="clearselbutton();statistics1('holder')">
    <span>
        <img border="0" align="absmiddle" src="../../pixmaps/theme/route.png">
        <?php echo _("All Traffic")?>
    </span>
</a>
&nbsp;
<?php
/*
<a class="buttonlink" style="text-decoration:none" onclick="clearselbutton();statisticsproto('holder')">
    <span>
        <img border="0" align="absmiddle" src="../../pixmaps/theme/ports.png">
        <?php echo _("Protocol Traffic")?>
    </span>
</a>
*/
?>
<?php
if (($filter!="") && !is_null($filter))
{
?>
&nbsp;
<a class="buttonlink" style="text-decoration:none" onclick="clearselbutton();statistics1f('holder')">
    <span>
        <img border="0" align="absmiddle" src="../../pixmaps/theme/net.png">
        <?php echo _("All Traffic Vs Filter Traffic")?>
    </span>
</a>
<?php
/*
&nbsp;
<a class="buttonlink" style="text-decoration:none" onclick="clearselbutton();statisticsprotofilter('holder')">
    <span>
        <img border="0" align="absmiddle" src="../../pixmaps/theme/net_group.png">
        <?php echo _("Protocol Filter Traffic")?>
    </span>
</a>
*/
?>
<?php 
}
?>
</div>
<div id="holder"><img src='../../pixmaps/loading3.gif' /> <?php echo _("Loading...")?></div>

<?php

$tshark->get_data_for_graphs("All");
//$tshark->get_data_for_graphs("Protocols");
if (($filter!="") && !is_null($filter))
{
    $tshark->get_data_for_graphs("AllFilter");
    //$tshark->get_data_for_graphs("ProtocolsFilter");
}
?>
<script type="text/javascript">
    statistics1('holder');
</script>
