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

Session::logcheck('configuration-menu', 'CorrelationCrossCorrelation');


$action      = 'insert';
$url_form    = 'newpluginref.php';

$button_text   = Util::js_entities(_("Create rule"));

$plugin_id1  = REQUEST('plugin_id1');
$plugin_id2  = REQUEST('plugin_id2');
$plugin_sid1 = REQUEST('plugin_sid1');
$plugin_sid2 = REQUEST('plugin_sid2');


if ($plugin_id1 != '' || $plugin_id2 != '' || $plugin_sid1 != '' || $plugin_sid2 != '')
{
    $action   = 'modify';
    $url_form = 'modifypluginref.php';
    
    $button_text   = Util::js_entities(_('Save rule'));
    
    ossim_valid($plugin_id1,  OSS_DIGIT, 'illegal:' . _('Plugin ID1'));
    ossim_valid($plugin_id2,  OSS_DIGIT, 'illegal:' . _('Plugin ID2'));
    ossim_valid($plugin_sid1, OSS_DIGIT, 'illegal:' . _('Plugin SID1'));
    ossim_valid($plugin_sid2, OSS_DIGIT, 'illegal:' . _('Plugin SID2'));

    if ( ossim_error() ) 
    {
        echo ossim_error();
        exit();
    }
}

$db   = new ossim_db();
$conn = $db->connect();

$plugin_list = Plugin::get_list($conn, 'ORDER BY name', 0);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title><?php echo _("Cross-Correlation");?></title>
    <meta http-equiv="Pragma" content="no-cache"/>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/notification.js"></script>
    <script type="text/javascript" src="../js/messages.php"></script>
    <script type="text/javascript" src="../js/ajax_validator.js"></script>
    <script type="text/javascript" src="../js/utils.js"></script>
    <script type="text/javascript">
        
        function load_sid(num, id) 
        {                       
            var loading_msg = "<img src='../pixmaps/loading.gif' width='12' alt='<?php echo _("Loading")?>' align='absmiddle'/><span style='margin-left:5px'><?php echo _("Loading")?>...</span>";
            
            $.ajax({
                type: "POST",
                url: "pluginref_ajax.php",
                data: { plugin_id:id, num:num },
                beforeSend: function( xhr ) {
                    $("#sid"+num).html(loading_msg);
                },
                success: function(data) {
                    $("#sid"+num).html(data);
                }
            });
        }
        
        function load_sid_2(num, pid, psid) 
        {                       
            var loading_msg = "<img src='../pixmaps/loading.gif' width='12' alt='<?php echo _("Loading")?>' align='absmiddle'/><span style='margin-left:5px'><?php echo _("Loading")?>...</span>";
            
            $.ajax({
                type: "POST",
                url: "pluginref_ajax.php",
                data: { plugin_id:pid, plugin_sid:psid, num:num },
                beforeSend: function( xhr ) {
                    $("#sid"+num).html(loading_msg);
                },
                success: function(data) {
                    $("#sid"+num).html(data);
                }
            });
        }
        
        function load_ref(num)
        {           
            var inp_id = ( num == 1) ? '#plugin_sid1' : '#plugin_sid2';
            var sel_id = ( num == 1) ? '#sidajax1' : '#sidajax2';
            
            
            var value = $(sel_id).val();
            $(inp_id).val(value);
        }
        
        
        $(document).ready(function(){
        
            $('#plugin_id1').change( function(){
                var val = $('#plugin_id1').val();
                load_sid(1, val);
            });
            
            $('#plugin_id2').change( function(){
                var val = $('#plugin_id2').val();
                load_sid(2, val);
            });
            
            <?php
            if ($action == 'modify')
            {
                ?>
                load_sid_2(1, '<?php echo $plugin_id1?>', '<?php echo $plugin_sid1?>');
                load_sid_2(2, '<?php echo $plugin_id2?>', '<?php echo $plugin_sid2?>');
                <?php
            }
            ?>
        });

        
    </script>
    
    <style type='text/css'>
        a 
        {
            cursor:pointer;
        }
        
        input[type='text'], input[type='hidden'], select 
        {
            width: 98%; 
            height: 18px;           
        }
        
        textarea 
        {
            width: 97%; 
            height: 45px;
        }
        
        select 
        { 
            max-width: 500px;
        }
        
        #t_plugin_ref 
        {
            margin: 20px auto;
            width: 600px;
        }
        
        #av_info
        {
            width: 80%;
            margin: 10px auto;
        }
    </style>

</head>
<body>

    <div id='av_info'></div>
    
    <form method="POST" name="f_plugin_ref" id="f_plugin_ref" action='<?php echo $url_form?>'>
        <input type="hidden" class='vfield' name="plugin_sid1" id="plugin_sid1" value="<?php echo $plugin_sid1?>"/>
        <input type="hidden" class='vfield' name="plugin_sid2" id="plugin_sid2" value="<?php echo $plugin_sid2?>"/>     
        
        <?php
        if ($action == 'modify')
        {
            ?>
            <input type="hidden" class='vfield' name="old_plugin_id1"  id="old_plugin_id1"  value="<?php echo $plugin_id1?>"/>
            <input type="hidden" class='vfield' name="old_plugin_id2"  id="old_plugin_id2"  value="<?php echo $plugin_id2?>"/>
            <input type="hidden" class='vfield' name="old_plugin_sid1" id="old_plugin_sid1" value="<?php echo $plugin_sid1?>"/>
            <input type="hidden" class='vfield' name="old_plugin_sid2" id="old_plugin_sid2" value="<?php echo $plugin_sid2?>"/>
            <?php
        }
        ?>
        
        <table id='t_plugin_ref'>
                
            <tr>
                <th class='headerpr' colspan="2"><?php echo ($action == 'modify') ? _('Modify Cross-Correlation rule') : _('Insert new Cross-Correlation rule')?></th>
            </tr>
        
            <tr>
                <th>
                    <label for="plugin_id1"><?php echo _('Data Source Name')?></label>
                </th>
                
                <td class='left'>
                    <select class='vfield' name="plugin_id1" id="plugin_id1">
                        <option value=''>-- <?php echo _('Select Data Source Name')?> --</option>
                        <?php
                        $selected = '';
                        foreach($plugin_list as $plugin)
                        {
                            $id          = $plugin->get_id();
                            $plugin_name = $plugin->get_name();
                            $selected    = ($plugin_id1 == $id) ? ' selected="selected"' : '';
                            
                            ?>
                            <option value="<?php echo $id?>"<?php echo $selected?>><?php echo $plugin_name?></option>
                            <?php 
                        } 
                        ?>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th>
                    <label for="plugin_id2"><?php echo _('Reference Data Source Name')?></label>
                </th>
                <td class='left'>
                    <select class='vfield' name="plugin_id2" id="plugin_id2">
                        <option value=''>-- <?php echo _('Select Reference Data Source Name')?> --</option>
                        <?php
                        $selected = '';
                        foreach($plugin_list as $plugin) 
                        {
                            $id          = $plugin->get_id();
                            $plugin_name = $plugin->get_name();
                            $selected    = ( $plugin_id2 == $id ) ? ' selected="selected"' : '';
                            ?>
                            <option value="<?php echo $id?>"<?php echo $selected?>><?php echo $plugin_name?></option>
                            <?php 
                        } 
                        ?>
                    </select>
                </td>
            </tr>
              
            <tr>
                <th><?php echo _('Event Type')?></th>
                <td id="sid1" class="left">
                    <span style='font-style:italic;'><?php echo _('Please, select Data Source Name')?></span>
                </td>
            </tr>
            
            <tr>
                <th><?php echo _('Reference SID Name')?></th>
                <td id="sid2" class="left">
                    <span style='font-style:italic;'><?php echo _('Please, select Reference Data Source Name')?></span>
                </td>
            </tr>
            
            <tr>
                <td colspan="2" class="noborder" style="padding: 10px;">
                    <input type="button" id='back' name='back' class='av_b_secondary' onclick="document.location.href='pluginref.php'" value="<?php echo _("Back")?>"/>
                    <input type="submit" id='send' name='send' value="<?php echo $button_text ?>"/>
                </td>
            </tr>
        </table>
    </form> 
</body>
</html>

<?php $db->close();?>
