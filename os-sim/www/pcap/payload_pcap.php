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


ini_set('max_execution_time','300'); 

require_once 'av_init.php';

Session::logcheck('environment-menu', 'TrafficCapture');

$scan_name  = GET('scan_name');
$sensor_ip  = GET('sensor_ip');

ossim_valid($scan_name, OSS_SCORE, OSS_NULLABLE, OSS_ALPHA, OSS_DOT, 'illegal:' . _('Capture name'));
ossim_valid($sensor_ip, OSS_IP_ADDR, 'illegal:' . _('Sensor ip'));

if (ossim_error()) 
{
    die(ossim_error());
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <title><?php echo _('Payload pcap') ?> </title>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
        <meta http-equiv="Pragma" content="no-cache"/>
        <link rel="stylesheet" type="text/css" href="../style/tree.css" />
        <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
        <script type="text/javascript" src="../js/jquery.min.js"></script>
        <script type="text/javascript" src="../js/jquery-ui.min.js"></script>
        <script type="text/javascript" src="../js/jquery.tmpl.1.1.1.js"></script>
        <script type="text/javascript" src="../js/jquery.dynatree.js"></script>
        <script type="text/javascript">
            $(document).ready(function() {
                var loading = '<br/><img src="../pixmaps/theme/loading2.gif" border="0" align="absmiddle"><span style="margin-left:5px"><?php echo _("Downloading pcap and tshark pdml tree...")?></span>';
                
                load_tree('');
                
                $('#loading').html(loading);
            });
            
            function load_tree(filter) {
                var layer = '#container';
                var nodetree = null;
                $.ajax({
                    type: "GET",
                    url: "payload_tshark_tree.php",
                    data: "scan_name=<?php echo $scan_name?>&sensor_ip=<?php echo $sensor_ip?>",
                    success: function(msg) {
                        if(msg == "<?php echo _("Empty file");?>") 
                        {
                            var cssObj = {
                              'border' : '0',
                              'text-align' : 'center'
                            }
                            
                            $(layer).css(cssObj);
                        }
                        $(layer).html(msg);
                        $(layer).show();
                        $("#details").show();
                        $(layer).dynatree({
                            clickFolderMode: 2,
                            imagePath: "../forensics/styles",
                            onActivate: function(dtnode) {
                                //alert(dtnode.data.url);
                            },
                            onDeactivate: function(dtnode) {}
                        });
                        nodetree = $(layer).dynatree("getRoot");
                        $('#loading').html("");
                    }
                });
            }
        </script>
        <style type='text/css'>
            .dynatree-container{ border:none !important;}
        </style>
    </head>
    <body>
        <div id="loading" style="width:350px;margin:auto;text-align:center"></div>
        <table width="550" style="margin:10px auto;display:none;" id="details">
            <tr>
                <th width="30%"><?php echo _('Capture Start Time'); ?></th>
                <th width="20%"><?php echo _('Duration (seconds)'); ?></th>
                <th width="30%"><?php echo _('User'); ?></th>
            </tr>
            <tr>
                <td style="text-align:center" class="nobborder"><?php 
                    $scan_info = explode("_",$scan_name);
                    echo date("Y-m-d H:i:s", $scan_info[2] );
                  ?>
                </td>
                <td style="text-align:center" class="nobborder"><?php echo $scan_info[3]?></td>
                <td style="text-align:center" class="nobborder"><?php echo $scan_info[1]?></td>
            </tr>
        </table>
        <div id="container" style="width:550px;line-height:16px;margin:auto;border-width: 1px; border-style: dotted;display:none;border-color: grey;"></div>
    </body>
</html>
