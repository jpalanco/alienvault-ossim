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


/****************************************************
 ************** Configuration Options ***************
 ****************************************************/

$scan_types = array(
    'nmap' => 5,
    'wmi'  => 4
);


$frequencies = array(
    '3600'    => 'Hourly',
    '86400'   => 'Daily',
    '604800'  => 'Weekly',
    '2419200' => 'Monthly'
);


$s_type = REQUEST('s_type');
$s_type = (empty($s_type)) ? $_SESSION['av_inventory_type'] : $s_type;


if (!array_key_exists($s_type, $scan_types))
{
    header("Location: ".AV_MAIN_ROOT_PATH."/404.php");
    exit();
}

Session::logcheck('environment-menu', 'AlienVaultInventory');

//Getting data
$task_id  = intval(REQUEST('task_id'));

ossim_valid($id, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _('Task ID'));


if (ossim_error())
{
    ossim_error();
    exit();
}


$db   = new ossim_db();
$conn = $db->connect();

if (!empty($task_id))
{
    try
    {
        $task_obj = Inventory::get_object($conn, $task_id);

        $name      = $task_obj['task_name'];
        $sensor_id = $task_obj['task_sensor'];
        $params    = $task_obj['task_params'];
        $period    = $task_obj['task_period'];
    }
    catch(Exception $e)
    {
        echo ossim_error($e->getMessage());
        exit();
    }
}


//Sensors
$sensors = Av_sensor::get_basic_list($conn);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title><?php echo _('OSSIM Framework');?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache">

    <?php
        //CSS Files
        $_files = array(
            array('src' => 'av_common.css?t='.Util::get_css_id(),           'def_path' => TRUE),
            array('src' => 'jquery-ui.css',                                 'def_path' => TRUE),
            array('src' => 'tipTip.css',                                    'def_path' => TRUE)
        );

        if ($s_type == 'nmap')
        {
            $_files[] = array('src' => 'jquery.autocomplete.css',          'def_path' => TRUE);
        }


        Util::print_include_files($_files, 'css');

        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',                                 'def_path' => TRUE),
            array('src' => 'jquery-ui.min.js',                              'def_path' => TRUE),
            array('src' => 'jquery.elastic.source.js',                      'def_path' => TRUE),
            array('src' => 'notification.js',                               'def_path' => TRUE),
            array('src' => 'ajax_validator.js',                             'def_path' => TRUE),
            array('src' => 'messages.php',                                  'def_path' => TRUE),
            array('src' => 'utils.js',                                      'def_path' => TRUE),
            array('src' => 'token.js',                                      'def_path' => TRUE),
            array('src' => 'jquery.tipTip.js',                              'def_path' => TRUE),
            array('src' => '/av_schedule_scan/js/av_schedule_scan.js.php',  'def_path' => FALSE)
        );

        if ($s_type == 'nmap')
        {
            $_files[] = array('src' => 'jquery.autocomplete.pack.js',                     'def_path' => TRUE);
            $_files[] = array('src' => '/av_scan.js.php',                                 'def_path' => TRUE);
            $_files[] = array('src' => '/av_schedule_scan/js/asset_scan_helpers.js.php',  'def_path' => FALSE);
        }


        Util::print_include_files($_files, 'js');
    ?>

    <script type="text/javascript">

        /****************************************************
         ********************* Greybox **********************
         ****************************************************/

        $(document).ready(function()
        {
            <?php
            if (isset($_GET['msg']) && $_GET['msg'] == 'saved')
            {
                $_message = _('Your changes have been saved.');

                unset($_GET['msg']);

                ?>
                parent.GB_close({"msg": "<?php echo $_message?>"});
                <?php
            }
            ?>


            /****************************************************
             ****************** AJAX Validator ******************
             ****************************************************/

            var config = {
                validation_type: 'complete', // single|complete
                errors:{
                    display_errors: 'all', //  all | summary | field-errors
                    display_in: 'avi_info'
                },
                form : {
                    id  : 'ss_form',
                    url : "../controllers/save_schedule.php?s_type=<?php echo $s_type?>"
                },
                actions: {
                    on_submit:{
                        id: 'send',
                        success:  '<?php echo _('Save')?>',
                        checking: '<?php echo _('Saving')?>'
                    }
                }
            };

            ajax_validator = new Ajax_validator(config);

            $('#send').click(function() {

                <?php
                if ($s_type == 'nmap')
                {
                    ?>

                    if (ajax_validator.check_form() == true)
                    {
                        var target_counter = get_target_number();

                        if (target_counter > 256)
                        {
                            var msg_confirm = '<?php echo Util::js_entities(_("You are about to schedule a target with a big number of assets (#ASSETS# assets). This scan could take a long time depending on your network and the number of assets that are up, are you sure you want to continue?"))?>';

                            msg_confirm = msg_confirm.replace("#ASSETS#", target_counter);

                            var keys = {"yes": "<?php echo _('Yes') ?>","no": "<?php echo _('No') ?>"};

                            av_confirm(msg_confirm, keys).fail(function(){
                                return false;
                            }).done(function(){
                                ajax_validator.submit_form()
                            });
                        }
                        else
                        {
                            ajax_validator.submit_form();
                        }
                    }
                    <?php
                }
                else
                {
                    ?>
                    ajax_validator.submit_form();
                    <?php
                }
                ?>
            });



            /****************************************************
             ********************* Tooltips *********************
             ****************************************************/

            $(".info").tipTip({attribute: "data-title", maxWidth: "350px", defaultPosition: "bottom", edgeOffset: 5});



            /****************************************************
             ********************** Token ***********************
             ****************************************************/

            Token.add_to_forms();


            /****************************************************
             **************** Autocomplete Nets *****************
             ****************************************************/

            <?php
            if ($s_type == 'nmap')
            {
                ?>
                bind_nmap_actions();

                $("#task_sensor").change(function()
                {
                    $('#task_params').flushCache();
                    $('#task_params').val('');

                    var sid = $("#task_sensor").val();

                    get_sensor_by_nets(sid, '<?php echo $s_type?>');
                });

                var sid = $("#task_sensor").val();

                get_sensor_by_nets(sid, '<?php echo $s_type?>');
                <?php
            }
            ?>
        });
    </script>
</head>

<body>

    <div id='avi_info'></div>

    <div id='avi_container'>

        <div class="legend">
            <?php echo _('Values marked with (*) are mandatory');?>
        </div>

        <form name="ss_form" id="ss_form" action="../controllers/save_schedule.php?s_type=<?php echo $s_type?>" method='POST'>
            <input type="hidden" name="s_type" value="<?php echo $s_type?>" class='vfield'/>
            <input type="hidden" name="task_id" id="task_id" value="<?php echo $task_id?>"/>

            <?php
            switch($s_type)
            {
                case 'nmap':
                    include AV_MAIN_ROOT_PATH.'/av_schedule_scan/templates/tpl_asset_scan_form.php';
                break;

                case 'wmi':
                    include AV_MAIN_ROOT_PATH.'/av_schedule_scan/templates/tpl_wmi_scan_form.php';
                break;
            }
            ?>

            <div id='ss_actions'>
                <input type="button" name='send' id='send' value="<?php echo _('Save')?>"/>
            </div>
        </form>
    </div>

</body>
</html>

<?php $db->close();?>
