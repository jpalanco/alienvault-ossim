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


Session::logcheck('environment-menu', 'PolicyHosts');


/****************************************************
 ******************** Host Data *********************
 ****************************************************/

//Database connection
$db    = new ossim_db();
$conn  = $db->connect();


$id  = GET('id');
$msg = GET('msg');

ossim_valid($id, OSS_HEX, 'illegal:' . _('Asset group ID'));

if (ossim_error())
{
    echo ossim_error(_('Error! Asset group not found'));

    exit();
}


$asset_group = new Asset_group($id);

$asset_group->can_i_edit($conn);

$asset_group->load_from_db($conn);

//Getting group data
$id          = $asset_group->get_id();
$name        = $asset_group->get_name();
$owner       = $asset_group->get_owner();
$descr       = $asset_group->get_descr();
$nagios      = Asset_group_scan::is_plugin_in_group($conn, $id, 2007);


//Closing database connection
$db->close();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title><?php echo _('AlienVault ' . (Session::is_pro() ? 'USM' : 'OSSIM')) ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    
    <?php
        //CSS Files
        $_files = array(
            array('src' => 'av_common.css',                 'def_path' => TRUE),
            array('src' => 'jquery-ui-1.7.custom.css',      'def_path' => TRUE),
            array('src' => 'assets/asset_group_form.css',   'def_path' => TRUE)
        );
        
        Util::print_include_files($_files, 'css');


        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',                 'def_path' => TRUE),
            array('src' => 'jquery-ui.min.js',              'def_path' => TRUE),
            array('src' => 'notification.js',               'def_path' => TRUE),
            array('src' => 'ajax_validator.js',             'def_path' => TRUE),
            array('src' => 'messages.php',                  'def_path' => TRUE),
            array('src' => 'jquery.elastic.source.js',      'def_path' => TRUE),
            array('src' => 'utils.js',                      'def_path' => TRUE),
            array('src' => 'token.js',                      'def_path' => TRUE),
            array('src' => 'greybox.js',                    'def_path' => TRUE),
        );
        
        Util::print_include_files($_files, 'js');
    ?>

    <script type="text/javascript">

        var __cfg = <?php echo Asset::get_path_url() ?>;
        
        $(document).ready(function(){

            $('#ag_form').attr('action', __cfg.group.controllers + "save_group.php");
            /***************************************************
             *********************** Token *********************
             ***************************************************/

            Token.add_to_forms();



            /****************************************************
             ************ Ajax Validator Configuration **********
             ****************************************************/


            var config = {
                validation_type: 'complete', // single|complete
                errors:{
                    display_errors: 'all', //  all | summary | field-errors
                    display_in: 'av_info'
                },
                form : {
                    id  : 'ag_form',
                    url : __cfg.group.controllers + "save_group.php"
                },
                actions: {
                    on_submit:{
                        id: 'send',
                        success: '<?php echo _('Save')?>',
                        checking: '<?php echo _('Saving')?>'
                    }
                }
            };

            ajax_validator = new Ajax_validator(config);

            $('#send').click(function() 
            {
                setTimeout(ajax_validator.submit_form, 200);
            });

            $('#cancel').click(function() 
            {
                parent.GB_close();
            });



            /****************************************************
             ***************** Elastic Textarea *****************
             ****************************************************/

            $('textarea').elastic();



            /****************************************************
             ******************* Greybox Options ****************
             ****************************************************/

            if (!parent.is_lightbox_loaded(window.name))
            {
                $('.c_back_button').show();
            }
            else
            {
                $('#ag_container').css('margin', '10px auto 20px auto');

                // Loaded from details and some data changed
                <?php
                if ($msg == 'saved')
                {
                    $_message = _('Your changes have been saved.');
                ?>
                    if (typeof(parent) != 'undefined')
                    {
                        //Try - Catch to avoid if this launch an error, the lightbox must be closed.
                        try
                        {
                            top.frames['main'].show_notification('asset_notif', "<?php echo $_message ?>", 'nf_success', 15000, true);
                        }
                        catch(Err){}
                        
                        var params =
                        {
                            'id': "<?php echo $id ?>"
                        }
    
                        parent.GB_hide(params);
                    }
                <?php
                }
                ?>
            }
        });
    </script>
</head>

<body>

    <div class="c_back_button">
        <input type='button' class="av_b_back" onclick="javascript:history.go(-1);"/>
    </div>

    <div id="av_info"></div>


<div id='ag_container'>

    <div class='legend'>
        <?php echo _('Values marked with (*) are mandatory');?>
    </div>

    <form method="POST" name="ag_form" id="ag_form" action="">

        <input type="hidden" class='vfield' name="id" id="id" value="<?php echo $id?>"/>

        <table id='t_container'>
            <tr>
                <th>
                    <label for="ag_name"><?php echo _('Name') . required();?></label>
                </th>

                <td>
                    <input type='text' name='ag_name' id='ag_name' class='vfield' value="<?php echo $name?>"/>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="owner"><?php echo _('Owner')?></label>
                </th>

                <td>
                    <input type='text' name='owner' id='owner' class='vfield' value="<?php echo $owner?>"/>
                </td>
            </tr>

            <tr>
                <th>
                   <label for='descr'><?php echo _('Description');?></label>
                </th>
                <td>
                    <textarea name="descr" id='descr' class='vfield'><?php echo $descr;?></textarea>
                </td>
            </tr>

            <!-- Save and Cancel buttons -->
            <tr>
                <td colspan="2" style="text-align: center; padding-top: 10px;">
                    <input type="button" name="cancel" class="av_b_secondary" id="cancel" value="<?php echo _('Cancel')?>"/>
                    <input type="button" name="send" id="send" value="<?php echo _('Save')?>"/>
                </td>
            </tr>
        </table>
    </form>
</div>
</body>
</html>
