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
?>

<table class='t_sc'>
    <tr>
        <th class='_label'><?php display_label($cnf_data['sensor_interfaces'])?></th>
        <td class='_data'>
            <div id='c_ifaces'>
                <?php
                    $id    = $cnf_data['sensor_interfaces']['id'];

                    $s_ifaces = $cnf_data['sensor_interfaces']['value'];
                    $s_ifaces = array_flip($s_ifaces);

                    //Interfaces
                    $interfaces = array();

                    try
                    {
                        $interfaces = Av_sensor::get_interfaces($system_id);
                    }
                    catch(Exception $e)
                    {
                        ;
                    }
                ?>

                <select class='vfield multiselect' id='<?php echo $id?>' name='<?php echo $id."[]"?>' multiple='multiple'>
                    <?php
                    foreach ($interfaces as $iface)
                    {
                        $i_name    = $iface['name'];
                        $id_iface  = md5($i_name);

                        if (array_key_exists($i_name, $s_ifaces))
                        {
                            echo "<option id='sm_".$id_iface."' selected='selected' value='$i_name'>$i_name</option>";
                        }
                        else
                        {
                            echo "<option id='sm_".$id_iface."' value='$i_name'>$i_name</option>";
                        }
                    }
                    ?>
                </select>
            </div>
        </td>
    </tr>

    <tr>
        <th class='_label'><?php display_label($cnf_data['sensor_networks'])?></th>
        <td class='_data' valign='top'>
            <div>
                <div id='l_n_actions'>
                    <div style="position:relative; width:350px;"><div class="n_loading"></div></div>
                    <input type='text' id='new_cidr' name='new_cidr' value=''/>
                    <input type='button' id='add_cidr' class='small' name='add_cidr' value='<?php echo _('Add')?>'/>
                </div>
                
                <div id='r_n_actions'><div id='info_cidr'></div></div>
            </div>
            
            <div id='c_network'>
                <?php

                    $id       = $cnf_data['sensor_networks']['id'];
                    $value    = $cnf_data['sensor_networks']['value'];
                    
                    $networks = array();
                    if (!empty($value))
                    {
                        $networks = explode(',', $value);
                    } 
                ?>

                <select style='display: none' class='vfield' id='<?php echo $id?>' name='<?php echo $id."[]"?>' multiple='multiple'>
                    <?php
                    foreach ($networks as $net)
                    {   
                        $net     = trim($net);
                        $pattern = preg_quote($net, '/');
                        $id_net  = md5($net);
                        
                        echo "<option id='sm_".$id_net."' selected='selected' value='$net'>$net</option>";
                    }
                    ?>
                </select>

                <ul class='ul_sm' id='ul_s_nets'>
                    <?php
                    foreach ($networks as $net)
                    {
                        $net      = trim($net);
                        $pattern  = preg_quote($net, '/');
                        $id_net   = md5($net);

                        echo "<li id='li_".$id_net."' class='selected'>
                                <span>$net</span>
                                <div style='float:right;'><img align='absmiddle' id='img_".$id_net."' src='".AVC_PIXMAPS_DIR."/delete.png' alt='"._('Delete')."' title='"._("Delete")."'/></div>
                              </li>";
                    }
                    ?>
                </ul>
            </div>
        </td>
    </tr>

    <tr>
        <th class='_label'><?php display_label($cnf_data['sensor_detectors'])?></th>
        <td class='_data' valign='top'>
            <div id='c_detectors'>
                <table id='t_detectors'>

                </table>
            </div>
        </td>
    </tr>
</table>


<script type='text/javascript'>

    // Container for detectors
    var $detectors = $("#t_detectors");

    // Create detector row
    function create_detector_row(detector, st_text, noborder, td_class, st_class, st_class_2)
    {
        $("#row_" + detector).remove();

        var row =
            "<tr id='row_" + detector + "'>" +
            "<td class='d_name" + noborder + "'>" + detector + "<span></span></td>" +
            "<td class='d_status " + td_class + " " + noborder + " ' style='border-right:none'>" +
            "<div class='data_left'><div class='" + st_class + "'></div></div>" +
            "<div class='" + st_class_2 + "'>" + st_text + "</div>" +
            "</td>" +
            "</tr>";

        $detectors.append(row);
    }

    //Detector status
    function get_detectors_status()
    {
        $.ajax({
            type:     "POST",
            url:      AVC_PATH + "/data/sections/configuration/sensor/sensor_actions.php",
            cache:    false,
            data:     "action=detectors" + "&system_id=" + section.system_id,
            dataType: "json",
            beforeSend: function (xhr)
            {

            },
            error: function (data){

                //Check expired session
                var session = new Session(data, '');

                if (session.check_session_expired() == true)
                {
                    session.redirect();
                    return;
                }
            },
            success: function(data){

                var cnd_1  = (typeof(data) == 'undefined' || data == null);
                var cnd_2  = (typeof(data) != 'undefined' && data != null && data.status != 'success');

                if (cnd_1 || cnd_2)
                {
                    var error_msg = (cnd_1 == true) ? labels['unknown_error'] : data.data;

                    var config_nt = {
                        content: error_msg,
                        options: {
                            type: "nf_error",
                            cancel_button: false
                        },
                        style: "margin:10px auto;width:90%;text-align:center;padding:0 10px;z-index:999"
                    };

                    var nt           = new Notification("nt_notif", config_nt);
                    var notification = nt.show();

                    // Show error notification
                    $detectors.html(notification);
                }
                else
                {
                    $("#nt_notif").remove();

                    var i          = 0;
                    var st_class   = "";
                    var st_text    = "";
                    var st_class_2 = "";
                    var td_class   = "";
                    var noborder   = "";
                    var data_size  = Object.keys(data.data).length;

                    // Create detectors rows
                    $.each(data.data, function(service, status) {

                        i += 1;

                        noborder = "";
                        status   = status.toUpperCase();

                        if (i == data_size)
                        {
                            noborder = " nobborder";
                        }

                        if ("UP" === status)
                        {
                            st_class   = "st_up";
                            st_text    = labels["st_up"];
                            st_class_2 = "data_right";
                            td_class   = "";
                        }
                        else if ("DOWN" === status)
                        {
                            st_class   = "st_down";
                            st_text    = labels["st_down"];
                            st_class_2 = "data_right red bold";
                            td_class   = " td_down ";
                        }
                        else
                        {
                            st_class   =  "st_unknown";
                            st_text    =  labels["st_unknown"];
                            st_class_2 =  "data_right";
                            td_class   =  " td_unknown ";
                        }

                        // Update status
                        create_detector_row(service, st_text, noborder, td_class, st_class, st_class_2);
                    });
                }
            }
        });

        setTimeout(function ()
        {
            get_detectors_status();
        }, 20000);
    }

    get_detectors_status();

    <?php
    if (!is_array($interfaces) || empty($interfaces))
    {        
        ?>
        var content   = '<?php echo _('Error retrieving sensor interfaces. Please, try again')?>';
            
        var config_nt = { content: content, 
                          options: {
                              type:'nf_error',
                              cancel_button: true
                          },
                          style: 'width: 80%; margin: 30px auto; text-align:center;'
                        };

        nt            = new Notification('nt_si', config_nt);
        notification  = nt.show();
        
        $('#c_ifaces').html(notification);
        <?php
    }
    else
    {
        ?>
        //Select/unselect interfaces
        $("#sensor_interfaces").multiselect({
            dividerLocation: 0.5
        });
        
        //Trigger Change Event (Change Control)
        $('#sensor_interfaces').on('multiselectdeselected', function(event, ui) {
            $('#sensor_interfaces').trigger('change');
        });
        
        $('#sensor_interfaces').on('multiselectselected', function(event, ui) {
            $('#sensor_interfaces').trigger('change');
        });
        <?php
    }
    ?>


    //Add monitored network
    $('#add_cidr').click(function() {
        
        var cidr = $('#new_cidr').val();
        
        if (typeof(cidr) != 'string' || cidr == '')
        {
            return false;
        }

        $.ajax({
            type: "POST",
            url: AVC_PATH+"/data/sections/configuration/sensor/sensor_actions.php",
            cache: false,
            data: "action=check_cidr" + "&system_id=" + section.system_id + "&cidr=" + cidr,
            dataType: 'json',
            beforeSend: function(xhr) {
                $('.n_loading').html('<img src="'+AV_PIXMAPS_DIR+'/loading.gif" align="absmiddle" width="13" alt="'+labels['loading']+'">');
            },
            error: function (data){
                
                var session = new Session(data, '');
                
                session.check_session_expired();
                if (session.expired == true)
                {
                    session.redirect();
                    return;
                }
            },
            success: function(data){

                $('.n_loading').html('');
                
                if (typeof(data) != 'undefined' && data.status == 'error')
                {
                    var content = '<div>'+labels['error_found']+'</div><div style="padding-left: 10px;">'+data.data+'</div>';
                    
                    var config_nt = { 
                        content: content, 
                        options: {
                            type: 'nf_error',
                            cancel_button: false
                        },
                        style: 'width: auto; white-space:nowrap;'
                    };

                    nt = new Notification('nt_1',config_nt);
                    
                    window.scrollTo(0,0);
                    $('#sc_info').html(nt.show());
                    setTimeout('nt.fade_out(4000)', 5000); 
                }
                else if (typeof(data) != 'undefined' && data.status == 'success')
                {
                    if ($('#li_'+data.data).length < 1)
                    {
                        var new_li = "<li id='li_"+data.data+"' class='selected'>" +
                                    "<span>"+cidr+"</span> " +
                                    "<div style='float:right;'><img id='img_"+data.data+"' src='"+AVC_PIXMAPS_DIR+"/delete.png' alt='"+labels['delete']+"' title='"+labels['delete']+"'/></div>" +
                                 "</li>";

                        $('#new_cidr').val('');
                        
                        if ($('#ul_s_nets li').length < 1)
                        {
                            $('#ul_s_nets').html(new_li);
                        }
                        else
                        {
                            $('#ul_s_nets li:first').before(new_li);
                        }
                    }

                    if ($('#sm_'+data.data).length < 1)
                    {
                        $('#sensor_networks').append("<option id='sm_"+data.data+"' selected='selected' value='"+cidr+"'>"+cidr+"</option>");
                    }

                    $('#sm_'+data.data).attr('selected', 'selected');

                    //Trigger Change Event (Change Control)
                    $('#sensor_networks').trigger('change');
                }
            }
        });
    });

    //Delete monitored network
    $(document).on("click", "#ul_s_nets img", function(){ 
        var iface    = $(this).attr('id');
        var sm_iface = iface.replace('img_', '#sm_');
        var li_iface = iface.replace('img_', '#li_');

        $(sm_iface).removeAttr('selected');
        $('#ul_s_nets ' + li_iface).remove();
        
        //Trigger Change Event (Change Control)
        $('#sensor_networks').trigger('change');
    });
</script>
