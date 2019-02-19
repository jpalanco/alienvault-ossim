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

ini_set('max_execution_time', '360');
require_once 'av_init.php';
require_once 'functions.inc';

Session::logcheck('environment-menu', 'EventsVulnerabilitiesScan');

$db = new ossim_db();
$conn = $db->connect();

if (POST('action') == 'save')
{
    $validate = array (
    'report_name'        => array('validation' => 'OSS_SCORE, OSS_LETTER, OSS_DIGIT, OSS_SPACE',    'e_message' => 'illegal:' . _('Report Name')),
    'transferred_entity' => array('validation' => "OSS_DIGIT, OSS_NULLABLE, OSS_USER,'\-\.'",       'e_message' => 'illegal:' . _('Transferred entity')),
    'transferred_user'   => array('validation' => "OSS_DIGIT, OSS_NULLABLE, OSS_USER,'\-\.'",       'e_message' => 'illegal:' . _('Transferred user')),
    'nbe_source'         => array('validation' => 'OSS_DIGIT',                                      'e_message' => 'illegal:' . _('Source')));

    $validation_errors = validate_form_fields('POST', $validate);

    if ($_FILES['nbe_file']['tmp_name']== '')
    {
        $validation_errors['file'] = _('File not uploaded');
    }
    else if ($_FILES['nbe_file']['size'] == 0)
    {
        $validation_errors['file'] = _('Empty File');
    }

    $report_name        = (array_key_exists('report_name', $validation_errors)) ? '' : POST('report_name');
    $assignto           = (POST('transferred_user') != '') ? POST('transferred_user') : POST('transferred_entity');
    $transferred_entity = POST('transferred_entity');
    $nbe_source         = (intval(POST('nbe_source')) == 1) ? 1 : 0;

    //Imported nessus files
    if ($_FILES['nbe_file']['tmp_name'] != '' && $_FILES['nbe_file']['size'] > 0 && empty($validation_errors))
    {
        if( $assignto == '' || $assignto == -1)
        {
            $assignto = Session::get_session_user();
        }

        if (strtoupper(substr($_FILES['nbe_file']['name'],-3)) != "NBE")
        {
            $status = 1;
            $error_importing =_("Error importing file") . ".  " . _("Uploaded file extension has to be .NBE") ;
        }
        else
        {
            $dest = $conf->get_conf("nessus_rpt_path")."tmp/import".md5($report_name).".nbe";

            if(!copy($_FILES['nbe_file']['tmp_name'], $dest)) {
                $status = 1;
                $error_importing =_("Error importing file");
            }
            else
            {
                $ctx = $transferred_entity;

                if(!Session::is_pro() || empty($ctx)) {
                    $ctx = Session::get_default_ctx();
                }
                else {
                    if( Session::get_entity_type($conn,$ctx) == 'logical' ) {
                        $ctx = Acl::get_logical_ctx_id($conn, $ctx);
                        $ctx = $ctx[0]; // first
                    }
                }

                $tz = Util::get_timezone();

                $db->close($conn);

                $_mode  = intval(POST('create_host'));

                $params = array($dest, base64_encode($report_name.";".$assignto), $_mode, $tz, $ctx, $nbe_source);
                $cmd    = "/usr/share/ossim/scripts/vulnmeter/import_nbe.pl ? ? ? ? ? ?";

                //error_log("/usr/share/ossim/scripts/vulnmeter/import_nbe.pl $dest ".base64_encode($report_name.";".$assignto)." 0 $tz $ctx", 3, "/tmp/debug.log");
                try
                {
                    $output_arr = Util::execute_command($cmd, $params, 'array');

                    $db   = new ossim_db();
                    $conn = $db->connect();

                    foreach($output_arr as $line)
                    {
                        if(preg_match("/report id: (\d+)/i", trim($line), $found))
                        {
                            $rid = $found[1];
                            ?>
                            <script type='text/javascript'> top.frames['main'].rname = "<?php echo $report_name ?>"; </script>
                            <?php
                        }
                    }

                    if(intval($rid)>0) { // check the report id
                        if (!is_dir("/usr/share/ossim/uploads/nbe")) {
                            mkdir("/usr/share/ossim/uploads/nbe", 0777, true);
                        }
                        copy($dest, "/usr/share/ossim/uploads/nbe/".$rid.".nbe");
                    }
                    preg_match_all("/skipping\shost\s\[(.*)\]/i", implode("\n", $output_arr), $n_founds);
                    $status = 2;
                    Util::memcacheFlush();
                }
                catch(Exception $e)
                {
                    $status = 1;
                    $error_importing =_("No valid results found in the uploaded file. Please check the NBE file syntax.");
                }
                unlink($dest);
            }
        }
    }
    else
    {
        $status = 1;
        $error_importing = implode('<br/>', $validation_errors);
    }
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title> <?php echo gettext("Vulnmeter"); ?> </title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
    <link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    <script type="text/javascript" src="/ossim/js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/notification.js"></script>
    <script>
        $(document).ready(function() {
            $('.save').off('click').on('click', function()
            {
                var error = '';
                var report_name = $('#report_name').val();
                var nf_style = 'width: 99%;text-align:left;margin:0 auto;padding:0px 5px;';
                var patt = /^[\da-z\-_\s]+$/i;

                if (report_name == '')
                {
                    error = '<?php echo _("\'Report name\' field (missing required field)"); ?>';
                }
                else if (patt.test(report_name) == false)
                {
                    var invalid_chars = report_name.replace(/[\da-z\-_\s]/gi, '');

                    error = '<?php echo _("Error in the \'Report name\' field (CHAR not allowed)."); ?>';
                    error = error.replace(/CHAR/g, invalid_chars.charAt(0));
                }

                if (error != '')
                {
                    show_notification('av_info', error, 'nf_error', 0, 0, nf_style);
                }
                else
                {
                    $('#create_host').val($(this).data('create'));
                    $('#nt_1').hide();
                    $('#import_form').submit();
                }
            });
        });

    function switch_user(select) {
        if(select=='entity' && $('#transferred_entity').val()!=''){
            $('#user').val('');
        }
        else if (select=='user' && $('#transferred_user').val()!=''){
            $('#entity').val('');
        }
    }
    </script>
    <style type="text/css">
    table.gray_border {
       border: 1px solid #C4C0BB;
    }
    </style>
<head>
<body>
    <table style="margin: 20px auto 30px auto;" cellspacing="0" cellpadding="0" width="90%" class="transparent">
    <tr>
        <td colspan="3">
            <div id='av_info'>
                <?php
                if($status !=0) {
                    switch($status)
                    {
                    case 1:
                        $type     = "nf_error";
                        $message  = $error_importing;
                        break;
                    case 2:
                        $import_details = (count($n_founds[1]) > 0 ) ? _(", but the following host names couldn't be resolved:")."<br/><br/>".implode("<br/>", $n_founds[1]) : "";
                        $type           = "nf_success";
                        $message        = _("The file has been imported successfully") . $import_details;
                        break;
                    }
                    $config_nt = array(
                    'content' => $message,
                    'options' => array (
                    'type'          => $type,
                    'cancel_button' => false
                    ),
                    'style'   => 'width: 100%; margin: 0px auto; text-align: left;'
                    );

                    $nt = new Notification('nt_1', $config_nt);
                    $nt->show();
                }
                ?>
            </div>
        </td>
    </tr>
    <tr>
        <td colspan="3" style="padding:10px 0px 0px 0px">
        <form id="import_form" method="post" action="import_nbe.php" enctype="multipart/form-data">
            <input name="action" type="hidden" value="save">
            <input id="create_host" name="create_host" type="hidden" value="0">
            <table border="0" cellpadding="2" cellspacing="2" width="100%" class="gray_border">
            <tr >
            <th width="100" ><?=_("Report Name")?></th> 
            <td width="785" class="nobborder" style="text-align:left;padding-left:5px;"><input id="report_name" name="report_name" type="text" value="<?php echo $report_name ?>" style="width: 146px;"></td>
            </tr>
            <tr>
            <th width="100"><?=_("File")?></th>
            <td width="785" class="nobborder" style="text-align:left;padding-left:5px;"><input name="nbe_file" type="file" size="25"></td>
            </tr>
            <tr>
            <th width="100"><?=_("Source")?></th>
            <td width="785" class="nobborder" style="text-align:left;padding-left:5px;">
                <input name="nbe_source" id="src_nessus" type="radio" value="1" <?php echo ($nbe_source == 1 || is_null($nbe_source)) ? 'checked=checked' : ''; ?>><label for="src_nessus">Nessus .nbe file</label>
                &nbsp;&nbsp;
                <input name="nbe_source" id="src_openvas" type="radio" value="0" <?php echo ($nbe_source == 0 && !is_null($nbe_source)) ? 'checked=checked' : ''; ?>><label for="src_openvas">AlienVault Vulnerability Assessment .nbe file</label>
                </td>
            </tr>
            <tr>
            <th><?php echo _("Assign To") ?></th>
            <td style="text-align:left;padding-left:5px;" class="nobborder">
                <table width="400" cellspacing="0" cellpadding="0" class="transparent">
                <tr>
                    <td class="nobborder"><?php echo _("User:");?></td>
                    <td class="nobborder" style="padding-left:5px;">
                    <select name="transferred_user" id="user" onchange="switch_user('user');return false;" style="width:150px">
                        <?php

                        $users    = Session::get_users_to_assign($conn);
                        $entities = Session::get_entities_to_assign($conn);

                        $num_users = 0;

                        foreach( $users as $k => $v )
                        {
                            $login = $v->get_login();
                            
                            $selected = ($login == $assignto) ? 'selected="selected"' : '';

                            $options .= "<option value='$login' $selected>$login</option>\n";
                            $num_users++;
                        }

                        if ($num_users == 0)
                        {
                            echo "<option value='' style='text-align:center !important;'>- "._("No users found")." -</option>";
                        }
                        else
                        {
                            echo "<option value='' style='text-align:center !important;'>- "._("Select one user")." -</option>\n";
                            echo $options;
                        }
                        ?>
                    </select>
                    </td>
                    <?php if ( !empty($entities) ) { ?>
                    <td  style="padding-left:20px;"><?php echo _("OR");?></td>
                    <td class="nobborder" style="padding-left:20px;"><?php echo _("Entity:");?></td>
                    <td class="nobborder" style="padding-left:5px;">
                    <select name="transferred_entity" id="entity" onchange="switch_user('entity');return false;" style="width:160px">
                        <option value="" style='text-align:center !important;'>- <?php echo _("Select one entity") ?> -</option>
                        <?php
                        foreach ( $entities as $k => $v )
                        {
                            $selected = ($k == $assignto) ? 'selected="selected"' : '';
                            echo "<option value='$k' $selected>$v</option>";
                        }
                        ?>
                    </select>
                    </td>
                    <?php } ?>
                </tr>
                </table>
            </td>
            </tr>
            <tr>
            <td colspan="5" style="text-align:center;padding:15px 0px 5px 0px;" class="nobborder">
                <input type="button" class="save av_b_secondary" data-create="1" value="<?=_("Import & asset insertion")?>"/>&nbsp;&nbsp;
                <input type="button" class="save" data-create="0" value="<?=_("Import")?>"/>
            </td>
            </tr>
        </table>
        </form>
        </td>
    </tr>
    </table>
<?php
$db->close($conn);
require_once 'footer.php';
?>
