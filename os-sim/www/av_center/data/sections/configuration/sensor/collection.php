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


//Get all plugins and enabled plugins

$plugins   = array();
$s_plugins = array();

try
{
    $plugins   = Plugin::get_detector_list($system_id);
    $s_plugins = $cnf_data['sensor_detectors']['value'];

    $s_plugins = array_flip($s_plugins);
}
catch(Exception $e)
{
    ;
}


//Check deprecated plugins
$deprecated_plugins = FALSE;

//OSSEC
if (array_key_exists('ossec_av_format', $s_plugins))
{
    //Set collection section by default
    $sw['collection'] = TRUE;

    //There is a deprecated plugin
    $deprecated_plugins = TRUE;
}


$id = $cnf_data['sensor_detectors']['id'];

?>

<table class='t_sc'>
    <tr>
        <td id='td_plugins' class='noborder'>
            
            <div id='c_plugins_number'><?php echo _('Total number of plugins').": <span class='bold'>".count($plugins)."</span>"?></div>

            <div id='c_plugins'>
                <div id='c_plugins_title'>
                    <div id='l_c_plugins_title'><?php echo _('Plugins enabled')?></div>
                    <div id='r_c_plugins_title'><?php echo _('Plugins available')?></div>
                </div>

                <select class='vfield multiselect' id='<?php echo $id?>' name='<?php echo $id."[]"?>' multiple='multiple'>
                    <?php
                    foreach ($plugins as $plugin)
                    {
                        //Skip deprecated plugins
                        if ($plugin == 'ossec_av_format')
                        {
                            continue;
                        }

                        $id_plugin = md5($plugin);

                        if (array_key_exists($plugin, $s_plugins) == TRUE)
                        {
                            echo "<option id='sm_".$id_plugin."' selected='selected' value='$plugin'>$plugin</option>";
                        }
                        else
                        {
                            echo "<option id='sm_".$id_plugin."' value='$plugin'>$plugin</option>";
                        }
                    }
                    ?>
                </select>
                
            </div>

        </td>
    </tr>

</table>


<script type='text/javascript'>

    <?php
    //Error retrieving plugin list

    if (!is_array($plugins) || empty($plugins))
    {
        ?>
        var content   = '<?php echo _('Error retrieving plugin list. Please, try again')?>';
            
        var config_nt = { content: content, 
                          options: {
                              type:'nf_error',
                              cancel_button: true
                          },
                          style: 'width: 80%; margin: 30px auto; text-align:center;'
                        };

        nt            = new Notification('nt_pl', config_nt);
        notification  = nt.show();
        
        $('#td_plugins').html(notification);
        <?php
    }
    else
    {
        //Special case - Deprecated plugins

        //Ossec_av_format
        if ($deprecated_plugins == TRUE)
        {
            ?>
            var content   = '<?php echo _('Warning! Plugin ossec_av_format has been deprecated and ossec-single-line should be used instead')?>';

            var config_nt = { content: content,
                              options: {
                                  type:'nf_warning',
                                  cancel_button: true
                              },
                              style: 'width: 100%; margin: 10px auto; text-align:center;'
                            };

            nt            = new Notification('nt_opd', config_nt);
            notification  = nt.show();
            
            if ($('#nt_opd'))
            {
                $('#sc_info').append(notification);
            }
            else
            {
                $('#sc_info').show();
            }
            <?php 
        }

        ?>
        //Select/unselect plugins
        
        $("#c_plugins .multiselect").multiselect({
            dividerLocation: 0.5,
            remove_all: false,
            add_all: false
        });
        

        // Trigger Change Event (Change Control)
        $('#sensor_detectors').on('multiselectdeselected', function(event, ui) {
            $('#<?php echo $id?>').trigger('change');
        });
        
        $('#sensor_detectors').on('multiselectselected', function(event, ui) {
            $('#<?php echo $id?>').trigger('change');
        });
        <?php
    }
    ?>
</script>
