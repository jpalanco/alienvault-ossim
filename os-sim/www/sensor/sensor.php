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
require_once 'get_sensors.php';


Session::logcheck('configuration-menu', 'PolicySensors');

$av_menu = @unserialize($_SESSION['av_menu']);

// Load column layout
require_once '../conf/layout.php';

$category    = 'policy';
$name_layout = 'sensors_layout';
$layout      = load_layout($name_layout, $category);

$active_sensors = "<div id=\"c_active_sensor\"><a href=\"sensor.php?onlyactive=1\"><div id=\"active_sensors\" class=\"bold\" style=\"color: green;\"> - </div></a></div>";
$total_sensors  = "<a href=\"sensor.php\"><div id=\"total_sensors\" class=\"bold\" style=\"color: black;\"> - </div></a>";

$db   = new ossim_db();
$conn = $db->connect();

$unregistered_sensors = Av_sensor::get_unregistered($conn);

$db->close();

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title><?php echo _('OSSIM Framework');?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    <meta http-equiv="X-UA-Compatible" content="IE=7" />
    <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    <link rel="stylesheet" type="text/css" href="../style/flexigrid.css"/>
    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/jquery.flexigrid.js"></script>
    <script type="text/javascript" src="../js/urlencode.js"></script>
    <script type="text/javascript" src="../js/notification.js"></script>
    <script type="text/javascript" src="../js/token.js"></script>
    <script type="text/javascript" src="../js/utils.js"></script>
    <script type='text/javascript'>       

        function delete_sensor(sensor_id, check_policies)
        {
            var dtoken = Token.get_token("sensor_form");

            $.ajax(
            {
                type: "POST",
                url: "sensor_actions.php",
                data: {
                    "action"         : "delete_sensor",
                    "id"             : sensor_id,                
                    "check_policies" : check_policies,
                    "token"          : dtoken
                },
                dataType: "json",
                beforeSend: function()
                {
                    $("#flextable").changeStatus('<?=_('Deleting sensor')?>...', false);
                    $('#av_msg_info').remove();                                            
                },
                error: function(data)
                {
                    //Check expired session
                    var session = new Session(data, '');

                    if (session.check_session_expired() == true)
                    {
                        session.redirect();
                        return;
                    }                
                    
                    var _msg = "<?php echo _("Sorry, operation was not completed due to an error when processing the request")?>";

                    notify(_msg, 'nf_error');
                },
                success: function(data)
                {                
                    if (typeof(data) != 'undefined' && data != null && data.status != '')
                    {
                        if (data.status == 'error')
                        {
                            notify(data.data, 'nf_error');
                        }
                        else if (data.status == 'warning')
                        {
                            var msg_confirm = '<?php echo Util::js_entities(_("This sensor belongs to a policy. Are you sure you would like to delete this sensor?"))?>';                            
                            var keys        = {"yes": "<?php echo _('Yes') ?>","no": "<?php echo _('No') ?>"};
                            
                            av_confirm(msg_confirm, keys).done(function(){
                                delete_sensor(sensor_id, 0); 
                            }); 
                        }
                        else
                        {
                            notify(data.data, 'nf_success');
                            
                            $('.flexigrid .reload').addClass('reload_red').removeClass('reload');
                            $("#flextable").flexReload();

                            //Delete sensor whether it is an unregistered sensor
                            if ($('#us_'+sensor_id).length > 0)
                            {
                                if ($('.tr_sensor_nc').length == 1)
                                {
                                    $('.t_sensor_nc').remove();
                                    $('#av_info').empty();
                                }
                                else
                                {
                                    $('#us_'+sensor_id).remove();
                                }                             
                            }
                            
                            calculate_stats();     
                        }                        
                    }
                    else
                    {
                        var _msg = "<?php echo _("Sorry, operation was not completed due to an error when processing the request")?>";

                        notify(_msg, 'nf_error');
                    }
                }
            });
        }
        
        
        function calculate_stats()
        {
            var dtoken = Token.get_token("sensor_form");

            $.ajax(
            {
                type: "POST",
                url: "sensor_actions.php",
                data: {
                    "action": "stats",                    
                    "token" : dtoken
                },
                dataType: "json",
                beforeSend: function()
                {
                    var load_img = '<div class="c_loading"><div><img src="<?php echo AV_PIXMAPS_DIR?>/loading3.gif" border="0" align="absmiddle"/></div></div>';
                    
                    $('#total_sensors').html(load_img); 
                    $('#active_sensors').html(load_img);                                                        
                },
                error: function(data)
                {
                    //Check expired session
                    var session = new Session(data, '');

                    if (session.check_session_expired() == true)
                    {
                        session.redirect();
                        return;
                    }
                    
                    $('#total_sensors').html('-'); 
                    $('#active_sensors').html('-');                
                },
                success: function(data)
                {                
                    var cnd_1  = (typeof(data) == 'undefined' || data == null);
                    var cnd_2  = (typeof(data) != 'undefined' && data != null && data.status != 'success');

                    if (!cnd_1 && !cnd_2)
                    {
                        //Update total number of sensors                    
                        $('#total_sensors').html(data.data.total);                            
                                   
                        //Update active sensors                        
                                                         
                        var a_actives = parseInt(data.data.actives);                                                                           
                                               
                        if (a_actives > 0)
                        {                        
                            var lnk = "<a href=\"sensor.php?onlyactive=1\"><div id=\"active_sensors\" class=\"bold\" style=\"color: green;\">" + a_actives + "</div></a>"
                            $('#c_active_sensor').html(lnk);  
                        }
                        else
                        {
                            $('#c_active_sensor').html("<div id=\"active_sensors\" class=\"bold\" style=\"color:red;\">0</div>");
                        }                                    
                    }
                    else
                    {
                        $('#total_sensors').html('-'); 
                        $('#active_sensors').html('-');    
                    }                   
                }
            });                
        }
        
        
        function save_layout(clayout) 
        {
            $("#flextable").changeStatus('<?=_("Saving column layout")?>...', false);
            
            $.ajax({
                type: "POST",
                url: "../conf/layout.php",
                data: { name:"<?php echo $name_layout ?>", category:"<?php echo $category ?>", layout:serialize(clayout) },
                success: function(msg) {
                    $("#flextable").changeStatus(msg,true);
                }
            });
        }
        
        
        function action(com,grid) 
        {
            var items = $('.trSelected', grid);
            
            if (com == '<?php echo _('Delete selected')?>') 
            {
                if (typeof(items[0]) != 'undefined') 
                {
                    var msg_confirm = '<?php echo Util::js_entities(_("Do you want to delete this sensor?"))?>';                            
                    var keys        = {"yes": "<?php echo _('Yes') ?>","no": "<?php echo _('No') ?>"};                  
                                    
                    av_confirm(msg_confirm, keys).done(function(){
                        delete_sensor(items[0].id.substr(3), 1); 
                    });               
                }
                else
                { 
                    av_alert('<?php echo Util::js_entities(_('You must select a sensor'))?>');
                }
            }
            else if (com == '<?php echo _('Modify')?>') 
            {
                if (typeof(items[0]) != 'undefined') 
                {
                    document.location.href = 'interfaces.php?sensor_id='+items[0].id.substr(3);
                }
                else
                { 
                    av_alert('<?php echo Util::js_entities(_("You must select a sensor"))?>');
                }
            }
            else if (com == '<?php echo _('New')?>') 
            {
                document.location.href = 'newsensorform.php';
            }
        }    
        

        function linked_to(rowid) 
        {
            document.location.href = 'interfaces.php?sensor_id='+rowid;
        }
        
        
        function menu_action(com,id,fg,fp) 
        {         
            if (com == 'delete') 
            {
                if (typeof(id) != 'undefined')  
                {                 
                    var msg_confirm = '<?php echo Util::js_entities(_("Do you want to delete this sensor?"))?>';                            
                    var keys        = {"yes": "<?php echo _('Yes') ?>","no": "<?php echo _('No') ?>"};
                    
                    av_confirm(msg_confirm, keys).done(function(){
                        delete_sensor(id, 1); 
                    });           
                }
                else
                { 
                    av_alert('<?php echo Util::js_entities(_('Sensor unselected'))?>');
                }
            }

            if (com == 'modify') 
            {
                if (typeof(id) != 'undefined') 
                {
                    document.location.href = 'interfaces.php?sensor_id='+id;
                }
                else 
                {
                    av_alert('<?php echo Util::js_entities(_("Sensor unselected"))?>');
                }
            }

            if (com == 'new') 
            {
                document.location.href = 'newsensorform.php'
            }
        }
        
    
        $(document).ready(function(){
            
            $("#flextable").flexigrid({
                url: 'getsensor.php?onlyactive=<?php echo (intval(GET('onlyactive')) > 0 ) ? 1 : 0 ?>',
                dataType: 'xml',
                colModel : [
                <?php
                if (Session::show_entities()) 
                {
                    $default = array(
                        "ip" => array(
                            _('IP'),
                            130,
                            'true',
                            'center',
                            FALSE
                        ),
                        "name" => array(
                            _('Name'),
                            200,
                            'true',
                            'center',
                            FALSE
                        ),
                        "entities" => array(
                            _('Contexts'),
                            230,
                            'false',
                            'center',
                            FALSE
                        ),
                        "priority" => array(
                            _('Priority'),
                            60,
                            'true',
                            'center',
                            FALSE
                        ),
                        "port" => array(
                            _('Port'),
                            50,
                            'true',
                            'center',
                            TRUE
                        ),
                        "version" => array(
                            _('Version'),
                            190,
                            'false',
                            'center',
                            FALSE
                        ),
                        "connect" => array(
                            _('Status'),
                            50,
                            'true',
                            'center',
                            FALSE
                        ),               
                        "desc" => array(
                            _('Description'),
                            284,
                            'false',
                            'left',
                            FALSE
                        )
                    );
                }
                else
                {
                    $default = array(
                        "ip" => array(
                            _('IP'),
                            150,
                            'true',
                            'center',
                            FALSE
                        ),
                        "name" => array(
                            _('Name'),
                            182,
                            'true',
                            'center',
                            FALSE
                        ),
                        "priority" => array(
                            _('Priority'),
                            60,
                            'true',
                            'center',
                            FALSE
                        ),
                        "port" => array(
                            _('Port'),
                            40,
                            'true',
                            'center',
                            FALSE
                        ),
                        "version" => array(
                            _('Version'),
                            190,
                            'false',
                            'center',
                            FALSE
                        ),
                        "connect" => array(
                            _('Status'),
                            50,
                            'true',
                            'center',
                            FALSE
                        ),                
                        "desc" => array(
                            _('Description'),
                            470,
                            'false',
                            'left',
                            FALSE
                        )
                    );
                }
                
                list($colModel, $sortname, $sortorder, $height) = print_layout($layout, $default, 'name', 'asc', 300);
                
                echo "$colModel\n";
                ?>
                ],
                buttons : [
                    {name: '<?=_("New")?>', bclass: 'add', onpress : action},
                    {separator: true},
                    {name: '<?=_("Modify")?>', bclass: 'modify', onpress : action},
                    {separator: true},
                    {name: '<?=_("Delete selected")?>', bclass: 'delete', onpress : action},
                    {separator: true},
                    {name: '<a href=\"sensor_plugins.php\"><?=_("Sensor Status")?></a>', bclass: 'stats', iclass: 'ibutton'},
                    {name: '<a href=\"sensor.php?onlyactive=1\"><?=_("Active Sensors")?></a>: <?php echo $active_sensors ?>', bclass: 'info', iclass: 'ibutton'},
                    {name: '<a href=\"sensor.php\"><?=_("Total Sensors")?></a>: <?php echo $total_sensors ?>', bclass: 'info', iclass: 'ibutton'}
                ],
                searchitems : [
                    {display: "<?=_("IP")?>",   name : 'ip', isdefault: true},
                    {display: "<?=_("Name")?>", name : 'name'}
                ],
                sortname: "<?php echo $sortname ?>",
                sortorder: "<?php echo $sortorder ?>",
                usepager: true,
                pagestat: '<?=_("Displaying")?> {from} <?=_("to")?> {to} <?=_("of")?> {total} <?=_("sensors")?>',
                nomsg: '<?=_("No sensors found in the system")?>',
                useRp: true,
                rp: 20,
                contextMenu: 'myMenu',
                onContextMenuClick: menu_action,
                onSuccess: calculate_stats,
                showTableToggleBtn: false,
                singleSelect: true,
                width: get_flexi_width(),
                height: 'auto',
                onColumnChange: save_layout,
                onDblClick: linked_to,
                onEndResize: save_layout
            });
        });
        
        
        <?php         
        if (GET('msg') == 'created') 
        { 
            ?>
            notify('<?php echo _('The Sensor has been created successfully')?>', 'nf_success');
            <?php 
        } 
        elseif (GET('msg') == 'updated') 
        { 
            ?>
            notify('<?php echo _('The Sensor has been updated successfully')?>', 'nf_success');
            <?php 
        }           
        elseif (GET('msg') == 'unknown_error') 
        { 
            ?>
            notify('<?php echo _('Invalid action - Operation cannot be completed')?>', 'nf_error');
            <?php 
        } 
        ?>
        
               
    </script>
    
</head>
<body>
    
    <?php 
    //Local menu
    include_once '../local_menu.php';
    
    if (count($unregistered_sensors) > 0) 
    {
        $msg = "<table class='t_sensor_nc'>
                    <tr>
                        <td>
                            <strong>"._("Warning")."</strong>: "._("The following sensor(s) are being reported as enabled by the server but aren't configured").".
                        </td>
                    </tr>
                </table>
                
                <table class='t_sensor_nc'>";
                            
                foreach($unregistered_sensors as $s_data) 
                {                     
                    $sensor_ip = $s_data['ip'];
                    $sensor_id = $s_data['id'];
                    
                    $msg .= "
                    <tr class='tr_sensor_nc' id='us_".$sensor_id."'>
                        <td class='td_ip_sensor'/>
                            <img src='../pixmaps/theme/server.png' align='absmiddle' border='0' align='top'/>
                            <a href='newsensorform.php?ip=".$sensor_ip."'><strong>".$sensor_ip."</strong></a>
                        </td>
                        <td class='td_i_sensor'/>
                            <a href='newsensorform.php?ip=".$sensor_ip."'>
                            <img src='../pixmaps/tables/table_row_insert.png' align='absmiddle' border='0' align='top'/>"._("Insert")."</a>
                        </td>
                                            
                        <td class='td_d_sensor'/>
                            <a class='discard_sensor' href=\"javascript:delete_sensor('".$sensor_id."', 0);\">
                            <img src='../pixmaps/tables/table_row_delete.png' align='absmiddle' border='0' align='top'/>"._("Discard")."</a>
                        </td>               
                    </tr>
                    <tr><td colspan='2'></td></tr>";
                } 
                
        $msg .= "</table>";     
    }    
    ?>    
       
    
    <div id='av_info'>        
        <?php        
        if ($msg != '') 
        {
            echo ossim_error($msg, AV_WARNING, 'width: 100%; margin: 0px auto 10px auto;');
        }        
        ?>             
    </div>
            
    <table id="flextable" style="display:none"></table>      
    
    <!-- Right Click Menu -->
    <ul id="myMenu" class="contextMenu">
        <li class="hostreport"><a href="#modify" class="greybox" style="padding:3px"><img src="../pixmaps/tables/table_edit.png" align="absmiddle"/> <?=_('Modify')?></a></li>
        <li class="hostreport"><a href="#delete" class="greybox" style="padding:3px"><img src="../pixmaps/tables/table_row_delete.png" align="absmiddle"/> <?=_('Delete')?></a></li>
        <li class="hostreport"><a href="#new" class="greybox" style="padding:3px"><img src="../pixmaps/tables/table_row_insert.png" align="absmiddle"/> <?=_('New Sensor')?></a></li>
    </ul>
</body>
</html>
