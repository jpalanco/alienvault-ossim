<?php
/*******************************************************************************
** OSSIM Forensics Console
** Copyright (C) 2009 OSSIM/AlienVault
** Copyright (C) 2004 BASE Project Team
** Copyright (C) 2000 Carnegie Mellon University
**
** (see the file 'base_main.php' for license details)
**
** Built upon work by Roman Danyliw <rdd@cert.org>, <roman@danyliw.com>
** Built upon work by the BASE Project Team <kjohnson@secureideas.net>
*/


include_once ("base_conf.php");
include_once ("$BASE_path/includes/base_constants.inc.php");
include_once ("$BASE_path/includes/base_include.inc.php");
//
// Generate .pcap
$tmpfile = "/var/tmp/base_packet_" . $eid . ".pcap";
$cmd = "/usr/share/ossim/scripts/snortlogtopcap.py -u ? -p ?";
//file_put_contents("/tmp/pcaps", "$cmd\n", FILE_APPEND);
Util::execute_command("$cmd >> /dev/null 2>&1", array($binary, $tmpfile));
#
?>
<div class='siem_detail_subsection_payload'><?php echo _("pcap File") . "&nbsp;" . PrintPcapDownload($db, $eid) ?></div>
<link rel="stylesheet" type="text/css" href="../style/tree.css" />
<script type="text/javascript" src="../js/jquery-ui.min.js"></script>
<script type="text/javascript" src="../js/jquery.tmpl.1.1.1.js"></script>
<script type="text/javascript" src="../js/jquery.dynatree.js"></script>
<script type="text/javascript">
var loading = '<br/><img src="../pixmaps/loading3.gif" border="0" align="absmiddle"><span style="margin-left:5px"><?php echo _("Loading tree")?>...</span>';
var layer = '#pcapcontainer';
var nodetree = null;
function load_tree(filter) {
    $('#pcaploading').html(loading);
    $.ajax({
        type: "GET",
        url: "base_payload_tshark_tree.php",
        data: "id=<?php echo $eid?>",
        success: function(msg) { 
            //alert (msg);
            $(layer).html(msg);
            $(layer).dynatree({
                clickFolderMode: 2,
                imagePath: "../forensics/styles",
                onActivate: function(dtnode) {
                    //alert(dtnode.data.url);
                },
                onDeactivate: function(dtnode) {}
            });
            nodetree = $(layer).dynatree("getRoot");
            $('#pcaploading').html("");
        }
    });
}
</script>
<style type='text/css'>
    .dynatree-container {
        border:none !important;
        margin-top: 0px;
    }
    span.dynatree-folder a  { font-weight:normal; }
    .container {
        line-height:16px
    }
    
</style>
<div id="pcaploading"></div>
<div id="pcapcontainer" style="padding-left:20px"></div>
