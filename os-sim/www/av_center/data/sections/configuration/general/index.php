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


//Config File
require_once (dirname(__FILE__) . '/../../../../config.inc');
require_once 'data/sections/configuration/utilities.php';

session_write_close();

$system_id = POST('system_id');
ossim_valid($system_id, OSS_DIGIT, OSS_LETTER, '-', 'illegal:' . _('System ID'));


if (ossim_error())
{
    $config_nt = array(
            'content' => ossim_get_error(),
            'options' => array (
                'type'          => 'nf_error',
                'cancel_button' => FALSE
            ),
            'style'   => 'margin: auto; width: 90%; text-align: center;'
        ); 


    $nt = new Notification('nt_1', $config_nt);
    $nt->show();
    exit();
}

//Framework URL
$url  = (empty($_SERVER["HTTPS"])) ?  'http://' : 'https://';
$url .= 'SERVER_IP/ossim/session/login.php?action=logout';


/**************************************************************
*****************  General Configuraton Data  *****************
***************************************************************/

$general_cnf  = Av_center::get_general_configuration($system_id);


if ($general_cnf['status'] == 'error')
{
    $config_nt = array(
            'content' => _('Error retrieving information. Please, try again'),
            'options' => array (
                'type'          => 'nf_error',
                'cancel_button' => FALSE
            ),
            'style'   => 'margin: 100px auto; width: 550px; text-align: center;'
        ); 


    $nt = new Notification('nt_1', $config_nt);
    $nt->show();

}
else
{
    $cnf_data = $general_cnf['data'];

    $yes_no = array ('no' => _('No'), 'yes' => _('Yes'));

    //Get all admin IPs
    try
    {
        $admin_ips[$cnf_data['admin_ip']['value']] = $cnf_data['admin_ip']['value'];

        $st = Av_center::get_system_status($system_id, 'network');

        foreach ($st['interfaces'] as $i_name => $i_data)
        {
            if ($i_name != 'lo' && $i_data['ipv4']['address'] != '')
            {
                $admin_ips[$i_data['ipv4']['address']] = $i_data['ipv4']['address'];
            }
        }
    }
    catch(Exception $e)
    {
        ;
    }
    ?>
    <div id='gc_notification'>
        <div id='gc_info' class='c_info'></div>
    </div>

    <div id='gc_container'>

        <div class="w_overlay" style="height:100%;"></div>

        <div class='cnf_header'><div class='cnf_h_title'><?php echo _('General Configuration')?></div></div>
        
        <div class='cnf_body'>
            <form id='f_gc' method='POST'>
                <input type='hidden' id='system_id' class='vfield' name='system_id' value='<?php echo $system_id?>'/>
                <input type='hidden' id='server_addr' class='vfield' name='server_addr' value='<?php echo Util::get_default_admin_ip()?>'/>
                <input type='hidden' id='server_url'  class='vfield' name='server_url'  value='<?php echo $url?>'/>

                <table id='t_gc'>
                    <tr>
                        <th class='_label'><?php display_label($cnf_data['hostname'])?></th>
                        <td class='_data'>
                            <input type='hidden' id='h_<?php echo $cnf_data['hostname']['id']?>' name='h_<?php echo $cnf_data['hostname']['id']?>' class='vfield' value='<?php echo $cnf_data['hostname']['value']?>'/>
                            <input type='text' id='<?php echo $cnf_data['hostname']['id']?>' name='<?php echo $cnf_data['hostname']['id']?>' class='vfield' value='<?php echo $cnf_data['hostname']['value']?>'/>
                        </td>
                    </tr>

                    <tr>
                        <th class='_label'><?php display_label($cnf_data['admin_ip'])?></th>
                        <td class='_data'>
                            <input type='hidden' id='h_<?php echo $cnf_data['admin_ip']['id']?>' name='h_<?php echo $cnf_data['admin_ip']['id']?>' class='vfield' value='<?php echo $cnf_data['admin_ip']['value']?>'/>
                            <select id='<?php echo $cnf_data['admin_ip']['id']?>' name='<?php echo $cnf_data['admin_ip']['id']?>' class='vfield'>
                            <?php
                            if (count($admin_ips) > 0)
                            {
                                foreach($admin_ips as $ip)
                                {
                                    if (!empty($ip))
                                    {
                                        echo "<option value='$ip'".(($cnf_data['admin_ip']['value'] == $ip) ? " selected='selected'": "").">$ip</option>\n";
                                    }
                                }
                            }
                            else
                            {
                                echo "<option value='' selected='selected'>"._('No IPs found')."</option>";
                            }
                            ?>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th class='_label'><?php display_label($cnf_data['yn_ntp_server'])?></th>
                        <td class='_data'>
                            <select id='<?php echo $cnf_data['yn_ntp_server']['id']?>' name='<?php echo $cnf_data['yn_ntp_server']['id']?>' class='vfield'>
                                <?php
                                foreach ($yes_no as $key => $value)
                                {
                                    echo "<option value='$key'".(($key == $cnf_data['yn_ntp_server']['value']) ? " selected='selected'": "").">$value</option>\n";
                                }
                                ?>
                            </select>
                        </td>
                    </tr>

                    <?php $cns_style = ($cnf_data['yn_ntp_server']['value'] == 'no') ? "style='display:none;'" : ""; ?>

                    <tr class='cns_options' <?php echo $cns_style?>>
                        <th class='_label pleft_20'><?php display_label($cnf_data['ntp_server']);?></th>
                        <td class='_data'>
                            <input type='text' id='<?php echo $cnf_data['ntp_server']['id']?>' name='<?php echo $cnf_data['ntp_server']['id']?>' class='vfield' value='<?php echo $cnf_data['ntp_server']['value']?>'/>
                        </td>
                    </tr>

                    <tr>
                        <th class='_label'><?php display_label($cnf_data['yn_mailserver_relay'])?></th>

                        <td class='_data'>
                            <select id='<?php echo $cnf_data['yn_mailserver_relay']['id']?>' name='<?php echo $cnf_data['yn_mailserver_relay']['id']?>' class='vfield'>
                                <?php
                                foreach ($yes_no as $key => $value)
                                {
                                    echo "<option value='$key'".(($cnf_data['yn_mailserver_relay']['value'] == $key) ? " selected='selected'": "").">$value</option>\n";
                                }
                                ?>
                            </select>
                        </td>
                    </tr>

                    <?php $cms_style = ($cnf_data['yn_mailserver_relay']['value'] == 'no') ? "style='display:none;'" : ''; ?>

                    <tr class='cms_options' <?php echo $cms_style?>>
                        <th class='_label pleft_20'><?php display_label($cnf_data['mailserver_relay'])?></th>
                        <td class='_data'><input type='text' id='<?php echo $cnf_data['mailserver_relay']['id']?>' name='<?php echo $cnf_data['mailserver_relay']['id']?>' class='vfield' value='<?php echo $cnf_data['mailserver_relay']['value']?>'/></td>
                    </tr>

                    <tr class='cms_options' <?php echo $cms_style?>>
                        <th class='_label pleft_20'><?php display_label($cnf_data['mailserver_relay_user'])?></th>
                        <td class='_data'><input type='text' id='<?php echo $cnf_data['mailserver_relay_user']['id']?>' name='<?php echo $cnf_data['mailserver_relay_user']['id']?>' class='vfield' value='<?php echo $cnf_data['mailserver_relay_user']['value']?>'/></td>
                    </tr>

                    <tr class='cms_options' <?php echo $cms_style?>>
                        <th class='_label pleft_20'><?php display_label($cnf_data['mailserver_relay_passwd'])?></th>
                        <td class='_data'><input type='password' id='<?php echo $cnf_data['mailserver_relay_passwd']['id']?>' name='<?php echo $cnf_data['mailserver_relay_passwd']['id']?>' class='vfield' value='<?php echo Util::fake_pass($cnf_data['mailserver_relay_passwd']['value'])?>'/></td>
                    </tr>

                    <tr class='cms_options' <?php echo $cms_style?>>
                        <th class='_label pleft_20'><?php display_label($cnf_data['mailserver_relay_passwd2'])?></th>
                        <td class='_data'><input type='password' id='<?php echo $cnf_data['mailserver_relay_passwd2']['id']?>' name='<?php echo $cnf_data['mailserver_relay_passwd2']['id']?>' class='vfield' value='<?php echo Util::fake_pass($cnf_data['mailserver_relay_passwd']['value'])?>'/></td>
                    </tr>

                    <tr class='cms_options' <?php echo $cms_style?>>
                        <th class='_label pleft_20'><?php display_label($cnf_data['mailserver_relay_port'])?></th>
                        <td class='_data'><input type='text' id='<?php echo $cnf_data['mailserver_relay_port']['id']?>' name='<?php echo $cnf_data['mailserver_relay_port']['id']?>' class='vfield' value='<?php echo $cnf_data['mailserver_relay_port']['value']?>'/></td>
                    </tr>

                    <tr>
                        <td colspan='2' id='buttonpad' class='noborder'>
                            <input type='button' name='apply_changes' id='apply_changes' value='<?php echo _('Apply Changes')?>'/>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
    </div>


    <script type='text/javascript'>

        var config = {
            validation_type: 'complete', // single|complete
            errors:{
                display_errors: 'all', //  all | summary | field-errors
                display_in: 'gc_info'
            },
            form : {
                id  : 'f_gc',
                url : "data/sections/configuration/general/save_changes.php"
            },
            actions: {
                on_submit:{
                    id: 'apply_changes',
                    success: '<?php echo _('Apply Changes')?>',
                    checking: '<?php echo _('Applying Changes')?>'
                }
            }
        };

        ajax_validator = new Ajax_validator(config);

        // Some help to fill smtp server
        var config_t = { content: '<?php echo _('You can type one IP address or server name or one IP list separated by comma. <br/>For example: 173.194.66.108, smtp.gmail.com, 173.194.66.109')?>'};


        Js_tooltip.show('#<?php echo $cnf_data['mailserver_relay']['id'] ?>', config_t);


        // Some help to fill ntp server

        //One IP address or server name is allowed (Patch temporary)
        var config_t = { content: '<?php echo _('You can type one IP address or server name. <br/>For example: 0.es.pool.ntp.org or 173.194.66.108')?>'};

        Js_tooltip.show('#<?php echo $cnf_data['ntp_server']['id'] ?>', config_t);

        // Redefine submit_form function
        ajax_validator.submit_form = function (){
            
            if (ajax_validator.check_form() == true)
            {
                General_cnf.save_cnf('f_gc');
            }
            else
            {
                if ($(".invalid").length >= 1)
                {
                    $(".invalid").get(0).focus();
                }
                
                return false;
            }
        }


        $('#apply_changes').click(function() {
            ajax_validator.submit_form();
        });
        
        $('#<?php echo $cnf_data['yn_mailserver_relay']['id']?>').change(function() { 
            if ($('#<?php echo $cnf_data['yn_mailserver_relay']['id']?>').val() != 'no')
            {
                
                if ($('#<?php echo $cnf_data['mailserver_relay']['id']?>').val() == 'no')
                {
                    $('#<?php echo $cnf_data['mailserver_relay']['id']?>').val('');
                }

                $('.cms_options').show();
            }
            else
            {
                $('#<?php echo $cnf_data['mailserver_relay']['id']?>').val('no');
                $('.cms_options').hide();
            }
        }); 

        $('#<?php echo $cnf_data['yn_ntp_server']['id']?>').change(function() { 
            if ($('#<?php echo $cnf_data['yn_ntp_server']['id']?>').val() != 'no')
            {
                
                if ($('#<?php echo $cnf_data['ntp_server']['id']?>').val() == 'no')
                {
                    $('#<?php echo $cnf_data['ntp_server']['id']?>').val('');
                }

                $('.cns_options').show();
            }
            else
            {
                $('#<?php echo $cnf_data['ntp_server']['id']?>').val('no');
                $('.cns_options').hide();
            }
        });

        var cc_config = {
            elem : {
                form_id: 'f_gc',
                submit_id : 'apply_changes'
            },
            changes : {
                display_in: 'gc_info',
                message: "<?php echo _("You have made changes, click <i>Apply Changes</i> to save")?>"
            }
        };
        

        change_control = new Change_control(cc_config);
        change_control.change_control();

        $(window).bind('unload', before_unload);

        //Check System Status (Reconfig in progress)
        Configuration.check_status();

    </script>
    <?php
}
?>