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
//Session::logcheck("configuration-menu", "ConfigurationPlugins");
Session::logcheck("configuration-menu", "CorrelationCrossCorrelation");

// load column layout
require_once ('../conf/layout.php');
$category    = "conf";
$name_layout = "plugin_layout";
$layout      = load_layout($name_layout, $category);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title> <?php echo gettext("Priority and Reliability configuration"); ?> </title>
    <meta http-equiv="Pragma" content="no-cache"/>
    <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    <link rel="stylesheet" type="text/css" href="../style/flexigrid.css"/>
    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/jquery.flexigrid.js"></script>
    <script type="text/javascript" src="../js/urlencode.js"></script>
    <script type="text/javascript" src="../js/greybox.js"></script>
    <script type="text/javascript" src="../js/notification.js"></script>
    <script type="text/javascript" src="../js/utils.js"></script>
    
    <style type='text/css'>
        table, th, tr, td {
            background:transparent;
            border-radius: 0px;
            -moz-border-radius: 0px;
            -webkit-border-radius: 0px;
            border:none;
            padding:0px; 
            margin:0px;
        }
        
        input, select {
            border-radius: 0px;
            -moz-border-radius: 0px;
            -webkit-border-radius: 0px;
            border: 1px solid #8F8FC6;
            font-size:12px; 
            font-family:arial; 
            vertical-align:middle;
            padding:0px;
            margin:0px;
        }
    </style>
    
    <script type='text/javascript'>

        function action(com,grid) {
            var items = $('.trSelected', grid);
            if ( com == '<?php echo _("Delete selected")?>' ) 
            {
                //Delete host by ajax
                if ( typeof(items[0]) != 'undefined' ) 
                {
                    var msg  = "<?php echo Util::js_entities(_('Are you sure you want to delete the selected rule?')) ?>"
                    var opts = {"yes": "<?php echo _('Yes') ?>", "no": "<?php echo _('No') ?>"}

                    av_confirm(msg, opts).done(function()
                    {
                        var aux    = items[0].id.substr(3);
                        var auxarr = aux.split(/\_/);
                        document.location.href = '/ossim/conf/delete_pluginref.php?plugin_id1='+auxarr[0]+'&plugin_sid1='+auxarr[1]+'&plugin_id2='+auxarr[2]+'&plugin_sid2='+auxarr[3];
                    });
                }
                else{
                    alert('<?php echo Util::js_entities(_('You must select a rule'));?>');
                }
            }
            else if ( com == '<?php echo _("Modify")?>' )
            {
                if (typeof(items[0]) != 'undefined') 
                {
                    var aux    = items[0].id.substr(3);
                    var auxarr = aux.split(/\_/);
                    document.location.href = 'newpluginrefform.php?plugin_id1='+auxarr[0]+'&plugin_sid1='+auxarr[1]+'&plugin_id2='+auxarr[2]+'&plugin_sid2='+auxarr[3];
                }
                else{
                    alert('<?php echo Util::js_entities(_('You must select a rule'));?>');
                }
            }
            else if ( com == '<?php echo _("New")?>') {
                document.location.href = 'newpluginrefform.php';
            }
        }
    
        function save_layout(clayout) {
            
            $.ajax({
                type: "POST",
                url: "../conf/layout.php",
                data: { name:"<?php echo $name_layout?>", category:"<?php echo $category?>", layout:serialize(clayout) },
                beforeSend: function( xhr ) {
                    $("#flextable").changeStatus('<?php echo _('Saving column layout')?>...',false);
                },
                success: function(msg) {
                    $("#flextable").changeStatus(msg,true);
                }
            });
        }
        
        function linked_to(rowid) {
            var auxarr             = rowid.split(/\_/);
            document.location.href = 'newpluginrefform.php?plugin_id1='+auxarr[0]+'&plugin_sid1='+auxarr[1]+'&plugin_id2='+auxarr[2]+'&plugin_sid2='+auxarr[3];
        }
                
        $(document).ready(function() {
            
            <?php 
            if ( GET('msg') == "created" ) 
            { 
                ?>
                notify('<?php echo _("Reference has been created successfully")?>', 'nf_success');
                <?php 
            } 
            elseif ( GET('msg') == "updated" ) 
            { 
                ?>
                notify('<?php echo _("Reference has been updated successfully")?>', 'nf_success');
                <?php 
            }
            elseif ( GET('msg') == "error" ) 
            { 
                ?>
                notify('<?php echo  Util::htmlentities($_SESSION['av_latest_error']) ?>', 'nf_error');
                <?php
                    
                unset($_SESSION['av_latest_error']);
            }           
            ?>
            
            $("a.greybox").click(function(){
                var t = this.title || $(this).text() || this.href;
                GB_show(t,this.href,300,700);
                return false;
            });
            
            $("#flextable").flexigrid({
                url: 'getpluginref.php',
                dataType: 'xml',
                colModel : [
                <?php
                $default = array(
                    "name" => array(
                        _('Data Source Name'),
                        200,
                        'false',
                        'left',
                        false
                    ) ,
                    "sid name" => array(
                        _('Event Type'),
                        380,
                        'false',
                        'left',
                        false
                    ) ,
                    "ref name" => array(
                        _('Ref Name'),
                        200,
                        'false',
                        'left',
                        false
                    ) ,
                    "ref sid name" => array(
                        _('Ref Sid Name'),
                        388,
                        'false',
                        'left',
                        false
                    )
                );
                list($colModel, $sortname, $sortorder) = print_layout($layout, $default, "id", "asc");
                echo "$colModel\n";
                ?>
                    ],
                buttons : [
                    {name: '<?php echo _("New")?>', bclass: 'add', onpress : action},
                    {separator: true},
                    {name: '<?php echo _("Modify")?>', bclass: 'modify', onpress : action},
                    {separator: true},
                    {name: '<?php echo _("Delete selected")?>', bclass: 'delete', onpress : action}
                    ],
                searchitems : [
                    {display: '<?php echo _('Data Source Name')?>', name : 'plugin_id', isdefault: true},
                    {display: '<?php echo _('Event Type')?>', name : 'plugin_sid'},
                    {display: '<?php echo _('Ref Name')?>', name : 'reference_id'},
                    {display: '<?php echo _('Ref Sid Name')?>', name : 'reference_sid'},
                ],
                sortname: "<?php echo $sortname ?>",
                sortorder: "<?php echo $sortorder ?>",
                usepager: true,
                pagestat: '<?php echo _('Displaying {from} to {to} of {total} rules')?>',
                nomsg: '<?php echo _('No Cross-Correlation rules found in the system')?>',
                useRp: true,
                rp: 20,
                singleSelect: true,
                width: get_flexi_width(),
                height: 'auto',
                onColumnChange: save_layout,
                onDblClick: linked_to,
                onEndResize: save_layout
            });   
        });
    </script>
</head>

<body style="margin:0px">
    
    <br><table id="flextable" style="display:none"></table>

</body>
</html>
