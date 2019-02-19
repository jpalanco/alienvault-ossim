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

Session::logcheck('analysis-menu', 'EventsForensics');


function get_tags($idm = FALSE)
{
    $tags = array(
        'ENTITY'                 => _('Context'),
        'DATE'                   => _('Date Received'),
        'PLUGIN_ID'              => _('Data Source'),
        'PLUGIN_SID'             => _('Event Type'),
        'PLUGIN_NAME'            => _('Data Source name'),
        'PLUGIN_DESC'            => _('Data Source description'),
        'PLUGIN_SOURCE_TYPE'     => _('Data Source type'),
        'PLUGIN_SID_CATEGORY'    => _('Event Type category'),
        'PLUGIN_SID_SUBCATEGORY' => _('Event Type subcategory'),
        'SIGNATURE'              => _('Event Name'),
        'DEVICE'                 => _('Device IP'),
        'FILENAME'               => _('ExtraData Filename'),
        'USERNAME'               => _('ExtraData Username'),
        'PASSWORD'               => _('ExtraData Password'),
        'USERDATA1'              => _('UserData 1'),
        'USERDATA2'              => _('UserData 2'),
        'USERDATA3'              => _('UserData 3'),
        'USERDATA4'              => _('UserData 4'),
        'USERDATA5'              => _('UserData 5'),
        'USERDATA6'              => _('UserData 6'),
        'USERDATA7'              => _('UserData 7'),
        'USERDATA8'              => _('UserData 8'),
        'USERDATA9'              => _('UserData 9'),
        'PAYLOAD'                => _('Payload'),
        'SENSOR'                 => _('Sensor'),
        'OTX'                    => _('OTX'),
        'IP_SRC'                 => _('Source IP'),
        'IP_SRC_FQDN'            => _('Resolved source IP FQDN'),
        'IP_DST'                 => _('Destination IP'),
        'IP_DST_FQDN'            => _('Resolved destination IP FQDN'),
        'IP_PROTO'               => _('IP protocol'),
        'PORT_SRC'               => _('Source port'),
        'PORT_DST'               => _('Destination port'),
        'IP_PORTSRC'             => ($idm) ? _('IDM Source Username@domain or IP:port format') : _('Source IP and port with IP:port format'),
        'IP_PORTDST'             => ($idm) ? _('IDM Destination Username@domain or IP:port format') : _('Destination IP and port with IP:port format'),
        'RELIABILITY'            => _('Reliability'),
        'PRIORITY'               => _('Priority'),
        'ASSET'                  => _('Asset Value (Source and Destination)'),
        'RISK'                   => _('Event Risk (Source and Destination)'),
        'SRC_USERDOMAIN'         => _('IDM: Username@Domain Source IP'),
        'DST_USERDOMAIN'         => _('IDM: Username@Domain Destination IP'),
        'SRC_HOSTNAME'           => _('IDM: Hostname Source IP'),
        'DST_HOSTNAME'           => _('IDM: Hostname Destination IP'),
        'SRC_MAC'                => _('IDM: MAC Source IP'),
        'DST_MAC'                => _('IDM: MAC Destination IP'),
        'REP_PRIO_SRC'           => _('Reputation: Source IP Priority'),
        'REP_PRIO_DST'           => _('Reputation: Destination IP Priority'),
        'REP_REL_SRC'            => _('Reputation: Source IP Reliability'),
        'REP_REL_DST'            => _('Reputation: Destination IP Reliability'),
        'REP_ACT_SRC'            => _('Reputation: Source IP Activity'),
        'REP_ACT_DST'            => _('Reputation: Destination IP Activity')
    );

    // Order by ascending column titles
    asort($tags);

    return $tags;
}



$conf        = $GLOBALS['CONF'];
$version     = $conf->get_conf('ossim_server_version');
$opensource  = (!preg_match('/pro|demo/i',$version)) ? TRUE : FALSE;
$idm_enabled = ($conf->get_conf('enable_idm', FALSE)) ? TRUE : FALSE;

$msg      = '';
$msg_type = '';

$edit                   = GET('edit');
$save                   = GET('save');
$forcesave              = GET('forcesave');
$name                   = trim(GET('name'));
$savereport_custom_name = GET('savereport_custom_name');
$oldname                = GET('oldname');
$columns                = GET('selected_cols');
$save_criteria          = (GET('save_criteria') != '') ? 1 : 0;


ossim_valid($edit, OSS_NULLABLE, OSS_DIGIT,                                        "Invalid: edit");
ossim_valid($save, OSS_NULLABLE, OSS_ALPHA,                                        "Invalid: save");
ossim_valid($forcesave, OSS_NULLABLE, OSS_DIGIT,                                   "Invalid: forcesave");
ossim_valid($name, OSS_NULLABLE, OSS_ALPHA, OSS_SPACE, OSS_PUNC,                   "Invalid: name");
ossim_valid($savereport_custom_name, OSS_NULLABLE, OSS_ALPHA, OSS_SPACE, OSS_PUNC, "Invalid: custom report name");
ossim_valid($oldname, OSS_NULLABLE, OSS_ALPHA, OSS_SPACE, OSS_PUNC,                "Invalid: oldname");
ossim_valid($columns, OSS_NULLABLE, OSS_ALPHA, OSS_PUNC,                           "Invalid: columns");
ossim_valid($save_criteria, OSS_NULLABLE, OSS_DIGIT,                               "Invalid: save criteria");

$columns_arr = explode(',', $columns);

if (ossim_error())
{
    die(ossim_error());
}

// New View
if ($save == 'insert')
{
    if ($name == '')
    {
        $msg      = _("Please, insert a name for the view.");
        $msg_type = 'nf_error';
    }
    elseif ($columns == '')
    {
        $msg      = _("You must select one column at least.");
        $msg_type = 'nf_error';
    }
    elseif ($_SESSION['views'][$name] != '' && !empty($_SESSION['views'][$name]))
    {
        $msg      = "<b>$name</b> "._("already exists, try another view name.");
        $msg_type = 'nf_error';
    }
    elseif($opensource && (in_array("PLUGIN_SOURCE_TYPE",$columns_arr) || in_array("PLUGIN_SID_CATEGORY",$columns_arr) || in_array("PLUGIN_SID_SUBCATEGORY",$columns_arr)))
    {
        $msg      = _("You can only select taxonomy columns in Pro version.");
        $msg_type = 'nf_error';
    }
    else
    {
        $login = Session::get_session_user();

        $db   = new ossim_db(true);
        $conn = $db->connect();

        $config = new User_config($conn);
        // Columns
        $_SESSION['views'][$name]['cols'] = $columns_arr;
        // Filters
        if ($save_criteria)
        {
            $session_data = $_SESSION;
            foreach ($_SESSION as $k => $v)
            {
                if (preg_match("/^(_|alarms_|back_list|current_cview|views|ports_cache|acid_|report_|graph_radar|siem_event|siem_current_query|siem_current_query_graph|deletetask).*/",$k))
                {
                    unset($session_data[$k]);
                }
            }

            $_SESSION['views'][$name]['data'] = $session_data;
        }
        else
        {
            $_SESSION['views'][$name]['data'] = array();
        }
        $config->set($login, 'custom_views', $_SESSION['views'], 'php', 'siem');
        $db->close();

        $created = 1;
    }
// Edit the Current View
}
elseif ($save == 'modify')
{
    if ($name == '')
    {
        $msg      = _("Please, insert a name for the view.");
        $msg_type = 'nf_error';
    }
    elseif($columns == '')
    {
        $msg      = _("You must select one column at least.");
        $msg_type = 'nf_error';
    }
    else
    {
        $login = Session::get_session_user();
        $db = new ossim_db(true);
        $conn = $db->connect();
        $config = new User_config($conn);
        if ($name != $oldname)
        {
            // Check if the new name already exists
            if (!empty($_SESSION['views'][$name]))
            {
                $msg      = "<b>$name</b> "._("already exists, try another view name.");
                $msg_type = 'nf_error';
            }
            else
            {
                $_SESSION['views'][$name]['data'] = $_SESSION['views'][$_SESSION['current_cview']]['data'];
                $_SESSION['current_cview'] = $name;
                unset($_SESSION['views'][$oldname]);
                $_SESSION['view_name_changed'] = $name; // Uses when closes greybox
            }
        }

        if ($msg == '')
        {
            $_SESSION['views'][$name]['cols'] = $columns_arr;

            if (!$save_criteria)
            {
                $_SESSION['views'][$name]['data'] = array();
            }
            if (is_array($_SESSION['views']['default']))       unset($_SESSION['views']['default']['data']);
            if (is_array($_SESSION['views']['Taxonomy']))      unset($_SESSION['views']['Taxonomy']['data']);
            if (is_array($_SESSION['views']['Reputation']))    unset($_SESSION['views']['Reputation']['data']);
            if (is_array($_SESSION['views']['Detail']))        unset($_SESSION['views']['Detail']['data']);
            if (is_array($_SESSION['views']['Risk Analysis'])) unset($_SESSION['views']['Risk Analysis']['data']);
            if (is_array($_SESSION['views']['IDM']))           unset($_SESSION['views']['IDM']['data']);

            $config->set($login, 'custom_views', $_SESSION['views'], 'php', 'siem');

            $edit = 1;
            $msg      = _("The view has been successfully updated.");
            $msg_type = 'nf_success';
        }

        $db->close();
    }
}
elseif ($save == _('Default view'))
{
    $login = Session::get_session_user();

    $db   = new ossim_db(true);
    $conn = $db->connect();
    $config = new User_config($conn);

    $_SESSION['views'][$name]['cols'] = array('SIGNATURE','DATE','IP_PORTSRC','IP_PORTDST','ASSET','PRIORITY','RELIABILITY','RISK','IP_PROTO');
    $config->set($login, 'custom_views', $_SESSION['views'], 'php', 'siem');
    $db->close();

    $edit = 1;
    $msg      = _("The view has been successfully updated.");
    $msg_type = 'nf_success';
}
elseif ($save == 'delete')
{
    if ($_SESSION['current_cview'] == "default")
    {
        $msg      = _("You cannot delete 'default' view.");
        $msg_type = 'nf_error';
    }
    else
    {
        $login = Session::get_session_user();

        $db   = new ossim_db(true);
        $conn = $db->connect();

        $config = new User_config($conn);
        $_SESSION['views'][$_SESSION['current_cview']] = [] ;
        $config->set($login, 'custom_views', $_SESSION['views'], 'php', 'siem');

        $db->close();

        $_SESSION['current_cview'] = ($idm_enabled) ? 'IDM' : 'default';
        $deleted = 1;
    }
}
elseif ($save == 'report')
{
    if ($msg == '')
    {
        $columns = implode(",",$columns_arr);
        $query1 = $_SESSION['siem_current_query'];
        $query1 = preg_replace("/AND \( timestamp \>\='[^\']+'\s*AND timestamp \<\='[^\']+' \) /i",'',$query1);
        $query1 = preg_replace("/AND \( timestamp \>\=\'[^\']+\' \)\s*/",'',$query1);
        $query2 = $_SESSION['siem_current_query_graph'];
        $query2 = preg_replace("/AND \( timestamp \>\='[^\']+'\s*AND timestamp \<\='[^\']+' \) /i",'',$query2);
        $query2 = preg_replace("/AND \( timestamp \>\=\'[^\']+\' \)\s*/",'',$query2);

        // Diferent name than the view name
        if ($savereport_custom_name != '')
        {
            $name = $savereport_custom_name;
        }

        if ($query1 != '' && $query2 != '' && $columns != '')
        {
            $db = new ossim_db(true);
            $conn = $db->connect();

            $curid = 0;
            $name  = str_replace('"','',$name);
            $query = "SELECT id FROM custom_report_types WHERE name=\"$name\" and file='SIEM/CustomList.php'";

            $rs = $conn->Execute($query);
            if (!$rs)
            {
                Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
            else
            {
                if (!$rs->EOF)
                {
                    $curid = $rs->fields['id'];
                }
            }

            $id = Av_report::get_new_report_module_id($conn);

            if ($curid > 0)
            {
                $sql    = "UPDATE custom_report_types SET name=?,type='Custom Security Events',file='SIEM/CustomList.php',inputs='Number of Events:top:text:OSS_DIGIT:25:1000',custom_report_types.sql=? WHERE id=?";
                $params = array($name,"$query1;$query2;$columns",$curid);
            }
            else
            {
                $sql    = "INSERT INTO custom_report_types (id,name,type,file,inputs,custom_report_types.sql) VALUES (?,?,'Custom Security Events','SIEM/CustomList.php','Number of Events:top:text:OSS_DIGIT:25:1000',?)";
                $params = array($id,$name,"$query1;$query2;$columns");
            }
            if ($conn->Execute($sql,$params))
            {
                $msg      = ($curid > 0) ? _("Report Module")." <b>'Custom Security Events - $name'</b> "._("successfully updated") : _("Report Module successfully created as")." <b>'Custom Security Events - $name'</b>";
                $msg_type = 'nf_success';
            }
            else
            {
                Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
                $msg      = _("Error creating a new report type.");
                $msg_type = 'nf_error';
            }

            $db->close();
        }
        else
        {
            $msg      = _("Error creating a new report type.");
            $msg_type = 'nf_error';
        }
    }
}
$tags = get_tags($idm_enabled);

if ($opensource)
{
    unset($tags['PLUGIN_SOURCE_TYPE']);
    unset($tags['PLUGIN_SID_CATEGORY']);
    unset($tags['PLUGIN_SID_SUBCATEGORY']);
}
//print_r($tags);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title> <?php echo _('SIEM Custom View'); ?> </title>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
    <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    <link type="text/css" rel="stylesheet" href="../style/jquery-ui-1.7.custom.css" />
    <link type="text/css" rel="stylesheet" href="../style/ui.multiselect.css" rel="stylesheet" />
    <style type='text/css'>
        /*Multiselect loading styles*/
        #ms_body
        {
          height: 297px;
        }

        #load_ms
        {
            margin:auto;
            padding-top: 105px;
            text-align:center;
        }

        #t_container
        {
            background: transparent;
            text-align: center;
            margin: 0px auto 20px auto;
            border: none;
        }

    </style>
    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/jquery-ui.min.js"></script>
    <script type="text/javascript" src="../js/jquery.tmpl.1.1.1.js"></script>
    <script type="text/javascript" src="../js/ui.multiselect.js"></script>
    <script type="text/javascript" src="../js/combos.js"></script>
    <script type="text/javascript" src="../js/notification.js"></script>
    <script type="text/javascript">
        $(document).ready(function(){
            $(".multiselect").multiselect({
                searchable: false,
                nodeComparator: function (node1,node2){ return 1 },
                dividerLocation: 0.5,
            });
            <?php
            if ($forcesave)
            {
                ?>
                document.fcols.save.value='report';document.fcols.selected_cols.value=getselectedcombovalue('cols');document.fcols.submit();
                <?php
            }
            ?>

            <?php
            // Show success or error message
            if ($msg != '')
            {
                ?>
            var nf_style = 'padding: 3px; width: 90%; margin: auto; text-align: center;';
            show_notification('custom_view_info', "<?php echo $msg ?>", '<?php echo $msg_type ?>', <?php echo ($msg_type == 'nf_error') ? 0 : 2000 ?>, 1, nf_style);
                <?php
            }
                ?>

        });
    </script>
</head>
<body>
<div id='custom_view_info'></div>
<table id='t_container'>
<?php
if ($created)
{
    ?>
    <tr><td class="center nobborder"><?=_("The custom view has been successfully created.")?></td></tr>
    <script type="text/javascript">
    var params            = new Array();
    params['change_view'] = '<?php echo $name ?>';
    parent.GB_hide(params);
    //top.frames['main'].change_view('<?=$name?>');parent.GB_hide()
    </script>
    <?php
}
elseif ($deleted)
{
    ?>
    <tr><td class="center nobborder"><?=_("The custom view has been deleted.")?></td></tr>
    <script type="text/javascript">
    var params            = new Array();
    params['change_view'] = 'default';
    parent.GB_hide(params);
    //top.frames['main'].change_view('default');parent.GB_hide()
    </script>
    <?php
}
else
{
    ?>
    <form method="get" name="fcols">
        <input type="hidden" name="edit" value="<?=$edit?>">
        <input type="hidden" id="action" name="save" value="<?=($edit) ? 'modify' : 'insert'?>">
        <input type="hidden" name="selected_cols" value=''>
        <input type="hidden" name="oldname" value="<?=$_SESSION['current_cview']?>">
        <input type="hidden" name="savereport_custom_name" value="<?=$savereport_custom_name?>">
        <tr><td class="center nobborder"><?=_("Select the <b>columns</b> to show in Security events listing")?></td></tr>
        <tr><td class="nobborder">
            <div id='ms_body'>
                <div id='load_ms'><img src='../pixmaps/loading.gif'/></div>
                <select id="cols" class="multiselect" multiple="multiple" name="columns[]">
                <?php
                if ($edit)
                {
                    $rel = 0;
                    foreach ($_SESSION['views'][$_SESSION['current_cview']]['cols'] as $label)
                    {
                        ?>
                        <option value="<?=$label?>" selected="selected"><?=($tags[$label] != '') ? $tags[$label] : $label?></option>
                        <?php
                    }

                    foreach ($tags as $label => $descr)
                    {
                        if (!in_array($label,$_SESSION['views'][$_SESSION['current_cview']]['cols']))
                        {
                            ?>
                            <option value="<?=$label?>"><?=$descr?></option>
                            <?php
                        }
                    }
                }
                else
                {
                    foreach($tags as $label => $descr)
                    {
                        ?>
                        <option value="<?=$label?>"><?=$descr?></option>
                        <?php
                    }
                }
                ?>
                </select>
            </div>
            </td></tr>

            <?php
            if (!$opensource)
            {
            ?>
            <tr><td class="center nobborder"><input type="checkbox" name="save_criteria" value="1" checked='checked'></input> <?php echo _("Include custom search criteria in this predefined view") ?></td></tr>
            <?php
            }
            ?>
            <tr><td class="center nobborder">
            <?php
            if ($_SESSION['current_cview'] == 'default' && $edit)
            {
                ?>
                <?=_("View Name")?>: <input type="text" value="default" style="color:gray;width:100px" disabled='disabled'><input type="hidden" name="name" value="default">
                <?php
            }
            else
            {
                ?>
                <?=_("View Name")?>: <input type="text" name="name" style="width:100px" value="<? if ($edit) echo $_SESSION['current_cview'] ?>" <? if ($edit) { ?>onkeyup="$('#saveasbutton').removeAttr('disabled');"<?php }?>>
                <?php }?>&nbsp;
                <?php
                if ($_SESSION['current_cview'] == 'default')
                {
                    ?>
                    <input type="button" class="small av_b_secondary" onclick="$('#action').val('<?=_("Default view")?>');document.fcols.submit()" value="<?=_("Restore Default")?>">
                    <?php
                }

                if ($edit && $_SESSION['current_cview'] != 'default')
                {
                    ?>
                    <input type="button" class="small av_b_secondary" onclick="document.fcols.save.value='insert';document.fcols.selected_cols.value=getselectedcombovalue('cols');document.fcols.submit()" value="<?php echo _("Save As")?>" id="saveasbutton" disabled='disabled'>
                    <input type="button" class="small av_b_secondary" onclick="if(confirm('<?php echo  Util::js_entities(_("Are you sure?"))?>')) { document.fcols.save.value='delete';document.fcols.submit() }" value="<?=_("Delete")?>">
                    <?php
                }

                if ($edit && !$opensource)
                {
                    ?>
                    <input type="button"  class="small av_b_secondary" onclick="document.fcols.save.value='report';document.fcols.selected_cols.value=getselectedcombovalue('cols');document.fcols.submit()" value="<?=_("Save as Report Module")?>">
                    <?php
                }
            ?>
            <input type="button" class="small av_b_secondary" onclick="parent.GB_hide()" value="<?=_("Cancel")?>">
            <input type="button" class="small" onclick="document.fcols.selected_cols.value=getselectedcombovalue('cols');document.fcols.submit()" value="<?=($edit) ? _("Save") : _("Create")?>">
        </td></tr>
    </form>
    <?php
}
?>
</table>
<br>
</body>
</html>
