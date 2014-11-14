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
require_once dirname(__FILE__) . '/conf/config.inc';

require_once '../conf/layout.php';

Session::logcheck('environment-menu', 'EventsHidsConfig');

//Current sensor
$sensor_id = $_SESSION['ossec_sensor'];

$db     = new ossim_db();
$conn   = $db->connect();

$s_data = Ossec_utilities::get_sensors($conn, $sensor_id);
$sensor_opt = $s_data['sensor_opt'];

$db->close();


//Check available sensors
if (!is_array($s_data['sensors']) || empty($s_data['sensors']))
{
    $styles = 'width: 90%; text-align:left; margin: 50px auto;';
    
    echo ossim_error(_('There is no sensor available'), AV_INFO, $styles);
    exit();
}


/* Layout Load */
$layout  = load_layout('agentless_layout', 'agentless');
$default = array(
    'hostname'  => array(_('Hostname'),      220, 'true', 'left',   FALSE),
    'ip'        => array(_('IP'),            140, 'true', 'center', FALSE),
    'user'      => array(_('User'),          180, 'true', 'center', FALSE),
    'status'    => array(_('Status'),        50,  'true', 'center', FALSE),
    'descr'     => array(_('Description'),   570, 'true', 'left',   FALSE)
);

list($colmodel, $sortname, $sortorder, $height) = print_layout($layout, $default, 'hostname', 'asc', 300);

$db->close();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title> <?php echo _('OSSIM Framework'); ?> </title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    
    <script type="text/javascript" src="/ossim/js/jquery.min.js"></script>
    <script type="text/javascript" src="/ossim/js/jquery.flexigrid.js"></script>
    <script type="text/javascript" src="/ossim/js/urlencode.js"></script>
    <script type='text/javascript' src="/ossim/js/notification.js"></script>
    <script type="text/javascript" src="/ossim/js/token.js"></script>
    <script type="text/javascript" src="/ossim/js/greybox.js"></script>
    <script type='text/javascript' src="/ossim/js/utils.js"></script>
    <script type="text/javascript" src="js/common.js"></script>
    
    <link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    <link rel="stylesheet" type="text/css" href="/ossim/style/flexigrid.css"/>
    
    <style type='text/css'>
        #headerh1
        {
            width:100%;
            height:1px;
        }
        
        #c_info
        {
            top: 10px !important;
        }

        .contextMenu a
        {
            padding-left: 10px !important;
        }

        .ibold
        {
            font-weight: bold;
            font-style:italic;
        }

        .not_configured
        {
            color:#504D4D;
        }

        .not_running
        {
            color:#E54D4D;
        }

        .running
        {
            color:#4F8A10;
        }

        #al_status
        {
            border: 0px;
            cursor:pointer;
        }

        .al_button
        {
            position:absolute;
            top:0px;
            right:10px;
        }

        #av_b_ac
        {
            float: right;
            margin-top: 15px;
        }

    </style>
    
    <script type='text/javascript'>

        var _sensor_id = '';

        /* Reload the flexigrind content with the agentless from sensor selected */
        function reload_flexigrid()
        {
            var p = {url: 'data/agentless/get_agentless.php?sensor=' + _sensor_id + '&sortname=hostname&sortorder=asc'};

            $("#flextable").flexOptions(p);
            $("#flextable").flexReload();
        }

        /* Get the agentless status of the sensor and checks if an apply opration is needed */
        function get_status()
        {
            $.ajax({
                type: "POST",
                dataType: "json",
                url: "data/agentless/ajax/actions.php",
                data: {"action": "get_agentless_status", "sensor": _sensor_id},
                beforeSend: function(){
                    $('#al_status').parent('span').removeClass().addClass('not_configured');
                    $('#al_status').html('<?php echo _('Agentless Status') ?> : <img src="../pixmaps/loading3.gif" border="0" align="absmiddle">');
                    $('#av_b_ac').remove();
                    $('#c_info').html('');
                },
                success: function(data){

                    if(typeof(data.data.log) != 'undefined' && data.data.log != '')
                    {
                        $('#c_info').html(notify_warning(data.data.log));
                        $('#c_info').fadeIn(2000);
                    }

                    if(typeof(data.data.reload) != 'undefined' && typeof(data.data.status) != 'undefined')
                    {
                        var _reload = data.data.reload;
                        var _status = data.data.status.toLowerCase();
                        var _class  = 'not_configured';

                        if(_reload == 'reload_red')
                        {
                            if ($('#av_b_ac').length == 0)
                            {
                                $('.pDiv').after("<button class='button' id='av_b_ac' onclick='apply_changes()'><?php echo _('Apply Changes')?></button>");
                            }
                        }
                        else
                        {
                            $('#av_b_ac').remove();
                        }

                        if(_status == 'up')
                        {
                            _status = '<?php echo Util::js_entities(_('Running')) ?>';
                            _class  = 'running';
                        }
                        else if(_status == 'down')
                        {
                            _status = '<?php echo Util::js_entities(_('Not running')) ?>';
                            _class  = 'not_running';
                        }
                        else
                        {
                            _status = '<?php echo Util::js_entities(_('Not configured')) ?>';
                            _class  = 'not_configured';
                        }

                        $('#al_status').text('<?php echo _('Agentless Status')?>: '+_status);
                        $('#al_status').parent('span').removeClass().addClass(_class);
                    }
                    else
                    {
                        $('#al_status').text('<?php echo _('Agentless Status') .': ' . _('Unknown')?>');
                        $('#al_status').parent('span').removeClass().addClass('not_configured');
                        $('#av_b_ac').remove();
                    }
                },
                error: function(data)
                {
                    $('#al_status').text('<?php echo _('Agentless Status') .': ' . _('Unknown')?>');
                    $('#al_status').parent('span').removeClass().addClass('not_configured');
                    $('#av_b_ac').remove();
                }
            });
        }

        /****************************************************************************************************/
        /***********************************       Flexigrid Options       **********************************/
        /****************************************************************************************************/

        function apply_changes()
        {
            var token = Token.get_token('al_apply_conf');

            //AJAX data
            var a_data = {
                "sensor"  : _sensor_id,
                "token"   : token
            };

            $.ajax({
                type: "POST",
                url: "data/agentless/al_applyconf.php",
                data: a_data,
                dataType: "json",
                beforeSend: function(xhr){

                    show_loading_box('ag_container', "<?php echo _('Applying configuration')?> ...", '');
                },
                error: function(data){

                    //Check expired session
                    var session = new Session(data, '');
                    
                    if (session.check_session_expired() == true)
                    {
                        session.redirect();
                        return;
                    } 
                    
                    hide_loading_box();
                    
                    $("#c_info").html(notify_error(ossec_msg['unknown_error']));
                            
                    $("#c_info").fadeIn(4000);

                    window.scrollTo(0,0);
                },
                success: function(data){

                    var cnd_1  = (typeof(data) == 'undefined' || data == null);
                    var cnd_2  = (typeof(data) != 'undefined' && data != null && data.status != 'success');
                        
                    if (cnd_1 || cnd_2)
                    {
                        hide_loading_box();
                        
                        var error_msg = (cnd_1 == true) ? ossec_msg['unknown_error'] : data.data;

                        $("#c_info").html(notify_error(error_msg));
                        $("#c_info").fadeIn(4000);

                        window.scrollTo(0,0);
                    }
                    else
                    {
                        document.location.href = '/ossim/ossec/agentless.php?applied=yes';
                    }
                }
            });
        }

        function save_layout(clayout)
        {
            $("#flextable").changeStatus('<?php echo _('Saving column layout')?>...', false);
            $.ajax({
                type: "POST",
                url: "../conf/layout.php",
                data: { name:"agentless_layout", category:"agentless", layout:serialize(clayout) },
                success: function(msg) {
                    $("#flextable").changeStatus(msg,true);
                }
            });
        }

        function action(com,grid, f, g)
        { 
            if(_sensor_id == '')
            {
                alert('<?php echo Util::js_entities(_('Sensor unselected'))?>');
                return false;
            }

            var items = $('.trSelected', grid);

            if (com == '<?php echo _('Delete selected')?>')
            {
                if (typeof(items[0]) != 'undefined')
                {
                    if(confirm('<?php echo  Util::js_entities(_('Are you sure?'))?>'))
                    {
                        $("#flextable").changeStatus('<?php echo _('Deleting agentless')?>...', false);
                        
                        var sdata = items[0].id.substr(3);
                        var token = Token.get_token('al_delete');

                        document.location.href = 'data/agentless/al_delete.php?sensor='+_sensor_id +'&ip='+urlencode(sdata)+"&token="+token;
                    }
                }
                else
                { 
                    alert('<?php echo Util::js_entities(_('You must select an Agentless'))?>');
                }
            }
            else if (com == '<?php echo _('Modify')?>')
            {
                if (typeof(items[0]) != 'undefined')
                {
                    var sdata = items[0].id.substr(3);
                    document.location.href = 'data/agentless/al_modifyform.php?sensor='+_sensor_id +'&ip='+urlencode(sdata);
                }
                else
                {
                    alert('<?php echo Util::js_entities(_('You must select an Agentless'))?>');
                }
            }
            else if (com == '<?php echo _('New')?>')
            {
                document.location.href = 'data/agentless/al_newform.php?sensor='+_sensor_id;
            }
            else if (com == '<?php echo _('Enable/Disabled')?>')
            {
                if (typeof(items[0]) != 'undefined')
                {   
                    $("#flextable").changeStatus('<?php echo _('Changing status')?>...', false);
                    
                    var token = Token.get_token('al_enable');
                    var sdata = items[0].id.substr(3);

                    document.location.href = 'data/agentless/al_enable.php?sensor='+_sensor_id +'&ip='+urlencode(sdata)+"&token="+token;
                }
                else
                {
                    alert('<?php echo Util::js_entities(_('You must select an Agentless'))?>');
                }
            }
            else if(com.match(/<?php echo _('Agentless Status')?>/))
            {
                <?php $status_url = Menu::get_menu_url('ossec_control.php?sensor='.$sensor_id, 'environment', 'detection', 'hids', 'ossec_control'); ?>
                document.location.href = "<?php echo $status_url;?>";
            }
        }
        
        function menu_action(com,id,fg,fp)
        {
            if(_sensor_id == '')
            {
                alert('<?php echo Util::js_entities(_('Sensor unselected'))?>');

                return false;
            }

            var ip = id;

            if (com == 'delete')
            {
                if (typeof(ip) != 'undefined')
                {   
                    if(confirm('<?php echo  Util::js_entities(_('Are you sure?'))?>'))
                    {
                        $("#flextable").changeStatus('<?php echo _('Deleting agentless')?>...', false);

                        var token = Token.get_token('al_delete');

                        document.location.href = 'data/agentless/al_delete.php?sensor='+_sensor_id +'&ip='+urlencode(ip) + "&token=" + token ;
                    }
                }
                else
                {
                    alert('<?php echo Util::js_entities(_('Agentless unselected'))?>');
                }
            }

            if (com == 'modify')
            {
                if (typeof(ip) != 'undefined')
                { 
                    document.location.href = 'data/agentless/al_modifyform.php?sensor='+_sensor_id +'&ip='+urlencode(ip);
                }
                else
                {
                    alert('<?php echo Util::js_entities(_('Agentless unselected'))?>');
                }
            }

            if (com == 'enable')
            {
                if (typeof(ip) != 'undefined')
                {
                    $("#flextable").changeStatus('<?php echo _('Changing status')?>...', false);

                    var token = Token.get_token('al_enable');

                    document.location.href = 'data/agentless/al_enable.php?sensor='+_sensor_id +'&ip='+urlencode(ip)+"&token="+token;
                }
                else
                {
                    alert('<?php echo Util::js_entities(_('Agentless unselected'))?>');
                }
            }
            
            if (com == 'new')
            {
                document.location.href = 'data/agentless/al_newform.php?sensor='+_sensor_id;
            }
        }
        
        
        function linked_to(rowid)
        {
            document.location.href = 'data/agentless/al_modifyform.php?sensor='+_sensor_id +'&ip='+urlencode(rowid);
        }


        $(document).ready(function() {

            show_select();

            /* Detec if we change the sensor */
            $('#sensors').on('change', function(){

                var s = $(this).val();
                
                if(s != '')
                {
                    _sensor_id   = s;
                    reload_flexigrid();
                    get_status();
                }
            });

            /* Setting the seonsor to the selected sensor from the combo */
            _sensor_id = $('#sensors').val();

            /* If there is a sensor selected, the flexigrid is displayed, otherwise a warning message is shown */
            if(_sensor_id != '')
            {
                $("#flextable").flexigrid(
                {
                    url: 'data/agentless/get_agentless.php?sensor=' + _sensor_id + '&sortname=hostname&sortorder=asc',
                    dataType: 'xml',

                    colModel : [<?php echo $colmodel ?>],

                    searchitems : [
                        {display: "<?php echo _('IP')?>", name : 'ip', isdefault: true},
                        {display: "<?php echo _('Hostname')?>", name : 'hostname'},
                        {display: "<?php echo _('User')?>", name : 'user'}
                    ],

                    buttons : [
                        <?php 
                        if (Session::menu_perms('environment-menu', 'EventsHidsConfig')) 
                        { 
                            ?>
                            {name: '<?php echo _('New')?>', bclass: 'add', onpress : action},
                            {separator: true},
                            {name: '<?php echo _('Modify')?>',    bclass: 'modify', onpress : action},
                            {separator: true},
                            {name: '<?php echo _('Delete selected')?>',     bclass: 'delete', onpress : action},
                            {separator: true},
                            {name: '<?php echo _('Enable/Disabled')?>',     bclass: 'enable', onpress : action},
                            {separator: true},
                            {name: '<div id="al_status"><?php echo _("Agentless Status")?>:  <img src="../pixmaps/loading3.gif" border="0" align="absmiddle"></div>', bclass: 'not_configured', iclass: 'al_button', onpress : action}                          
                            <?php 
                        } 
                        else 
                        { 
                            ?>
                            {separator: true},
                            {name: '<div id="al_status" ><?php echo _('Loading...')?>:  <img src="../pixmaps/loading3.gif" border="0" align="absmiddle"></div>', bclass: 'not_configured', iclass: 'al_button'}
                            <?php 
                        } 
                    ?>
                    ],
                    
                    sortname: "<?php echo $sortname ?>",
                    sortorder: "<?php echo $sortorder ?>",
                    usepager: true,
                    pagestat: '<?php echo _("Displaying")?> {from} <?php echo _("to")?> {to} <?php echo _("of")?> {total} <?php echo _("hosts")?>',
                    nomsg: '<?php echo _("No Agentless found in the system")?>',
                    useRp: true,
                    rp: 20,
                    singleSelect: true,
                    <?php if (Session::menu_perms('environment-menu', 'EventsHidsConfig')) 
                    { 
                        ?>
                        contextMenu: 'myMenu',
                        onContextMenuClick: menu_action,
                        <?php 
                    } 
                    ?>
                    showTableToggleBtn: false,
                    width: get_flexi_width(),
                    height: 'auto',
                    onColumnChange: save_layout,
                    <?php 
                    if (Session::menu_perms('environment-menu', 'EventsHidsConfig')) 
                    { 
                        ?>
                        onDblClick: linked_to,
                        <?php
                    } 
                    ?>
                    onEndResize: save_layout

                });

                get_status();
            }
            else
            {
                $('#sensors_filter').hide();

                $('#c_info').html(notify_warning("<?php echo _('No sensors available')?>"));
                $('#c_info').fadeIn(2000);
            }
            
            
            <?php
            //Show message when the user has applied the new configuration
            if (GET('applied'))
            {
                unset($_GET['applied']);
                ?>
                $("#c_info").html(notify_success('<?php echo _('Configuration applied successfully')?>'));

                $("#c_info").fadeIn(4000);

                setTimeout('$("#c_info").fadeOut(4000);', 5000);
                <?php
            }
            ?>
        });
    
    </script>
</head>

<body style="margin:0px">

    <?php require_once '../local_menu.php'; ?>
    
    <div id = 'ag_container'>
        <?php $s_class = (Session::is_pro() && count($s_data['sensors']) > 1) ? 's_show' : 's_hide';?>

        <div id='sensors_filter' class='c_filter_and_actions'>
            <div class='c_filter'>
                <label for='sensors'><?php echo _('Select sensor')?>:</label>
                <select id='sensors' name='sensors' class='vfield <?php echo $s_class?>'>
                    <?php echo $sensor_opt?>
                </select>
            </div>
        </div>

        <div id='container_c_info'>
            <div id='c_info'></div>
        </div>


        <table id="flextable" style="display:none"></table>

        <!-- Right Click Menu -->
        <ul id="myMenu" class="contextMenu">
            <li class="hostreport">
                <a href="#new"><img src="../pixmaps/tables/table_row_insert.png" align="absmiddle"/> <?php echo _('New')?></a>
            </li>
            <li class="hostreport">
                <a href="#modify"><img src="../pixmaps/tables/table_edit.png" align="absmiddle"/> <?php echo _('Modify')?></a>
            </li>
            <li class="hostreport">
                <a href="#delete"><img src="../pixmaps/tables/table_row_delete.png" align="absmiddle"/> <?php echo _('Delete')?></a>
            </li>
            <li class="hostreport">
                <a href="#enable"><img src="../pixmaps/tables/enable.png" align="absmiddle"/> <?php echo _('Enable/disabled')?></a>
            </li>
        </ul>
    </div>
    
</body>
</html>
