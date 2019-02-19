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


//
// $Id: settings.php,v 1.12 2010/03/27 14:15:58 jmalbarracin Exp $
//
/***********************************************************/
/*                    Inprotect                            */
/* --------------------------------------------------------*/
/* Copyright (C) 2006 Inprotect                            */
/*                                                         */
/* This program is free software; you can redistribute it  */
/* and/or modify it under the terms of version 2 of the    */
/* GNU General Public License as published by the Free     */
/* Software Foundation.                                    */
/* This program is distributed in the hope that it will be */
/* useful, but WITHOUT ANY WARRANTY; without even the      */
/* implied warranty of MERCHANTABILITY or FITNESS FOR A    */
/* PARTICULAR PURPOSE. See the GNU General Public License  */
/* for more details.                                       */
/*                                                         */
/* You should have received a copy of the GNU General      */
/* Public License along with this program; if not, write   */
/* to the Free Software Foundation, Inc., 59 Temple Place, */
/* Suite 330, Boston, MA 02111-1307 USA                    */
/*                                                         */
/* Contact Information:                                    */
/* inprotect-devel@lists.sourceforge.net                   */
/* http://inprotect.sourceforge.net/                       */
/***********************************************************/
/* See the README.txt and/or help files for more           */
/* information on how to use & config.                     */
/* See the LICENSE.txt file for more information on the    */
/* License this software is distributed under.             */
/*                                                         */
/* This program is intended for use in an authorized       */
/* manner only, and the author can not be held liable for  */
/* anything done with this program, code, or items         */
/* discovered with this program's use.                     */
/***********************************************************/

require_once 'av_init.php';
require_once 'functions.inc';
require_once 'config.php';
require_once 'ossim_sql.inc';

Session::logcheck("environment-menu", "EventsVulnerabilities");

$conf        = $GLOBALS["CONF"];

$version     = $conf->get_conf("ossim_server_version");
$nessus_path = $conf->get_conf("nessus_path");

$pro         = Session::is_pro();

$db     = new ossim_db();
$dbconn = $db->connect();
$dbconn->SetFetchMode(ADODB_FETCH_BOTH);

$getParams  = array( "disp", "item", "page", "delete", "prefs", "uid", "sid",
           "op", "confirm", "preenable", "bEnable" );

$postParams = array( "disp", "saveplugins", "page", "delete", "prefs", "uid", "sid",
           "op", "sname", "sdescription", "sautoenable", "item",
           "AllPlugins", "NonDoS", "DisableAll", "submit", "fam",
           "cloneid", "stype", "importplugins", "tracker", "preenable", "bEnable", "user", "entity" );


switch ($_SERVER['REQUEST_METHOD'])
{
    case "GET" :
        foreach ($getParams as $gp)
        {
            if (isset($_GET[$gp]))
                $$gp=Util::htmlentities(escape_sql(trim(GET($gp)), $dbconn), ENT_QUOTES);
            else
                $$gp="";
        }

       $submit      = "";
       $AllPlugins  = "";
       $NonDOS      = "";
       $DisableAll  = "";
       $saveplugins = "";
    break;

    case "POST" :
        foreach ($postParams as $pp)
        {
            if (isset($_POST[$pp]))
                $$pp=Util::htmlentities(escape_sql(trim(POST($pp)), $dbconn), ENT_QUOTES);
            else
                $$pp="";

       }
    break;
}


ossim_valid($sid, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Sid"));

if (ossim_error()) {
    die(_("Invalid Parameter Sid"));
}


if( isset($_POST['authorized_users']) )
{
    foreach($_POST['authorized_users'] as $user) {
        $users[] = Util::htmlentities(escape_sql(trim($user), $dbconn), ENT_QUOTES);
    }
}


$sIDs = array();

if ( Vulnerabilities::scanner_type() == 'omp' )
{
    list($sensor_list, $total) = Av_sensor::get_list($dbconn);

    foreach ($sensor_list as $sensor_id => $sensor_data)
    {
        if( intval($sensor_data['properties']['has_vuln_scanner']) == 1)
        {
            $sIDs[] = array( 'name' => $sensor_data['name'], 'id' => $sensor_id );
        }
    }
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title> <?php echo gettext("Vulnmeter"); ?> </title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache">
    <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/vulnmeter.js"></script>
    <script type="text/javascript" src="../js/jquery.tipTip-ajax.js"></script>
    <link rel="stylesheet" type="text/css" href="../style/tipTip.css"/>

    <?php require ("../host_report_menu.php") ?>
    <script type="text/javascript">
        function postload() {
            
            var buttons = new Object();
            
            buttons['AllPlugins'] = 'Enable All';
            buttons['NonDOS']     = 'Enable Non DOS';
            buttons['DisableAll'] = 'Disable All';
            
            <?php
            if ( $disp == "editplugins" )
            {
                ?>
                updateProfilePlugins(<?php echo $sid; ?>);

                $('.updateplugins').on('click', function(event) {

                    $('#AllPlugins,#NonDOS,#DisableAll,#updateplugins').attr('disabled', 'disabled');
                    $('#AllPlugins,#NonDOS,#DisableAll,#updateplugins').addClass('disabled');

                    clean_updates_table();

                    $('#updates_info').hide(); // hide table with update progress

                    var uaction = buttons[$(this).attr('id')];

                    var ids = <?php echo json_encode($sIDs)?>;

                    notifications_changes('Updating Database', 'database', 'loading', '');

                    $.ajax({
                        type: 'POST',
                        url: 'profiles_ajax.php',
                        async: false,
                        data: { type: 'save_database_plugins', sid:'<?php echo $sid ?>', action: uaction },
                        dataType: 'json',
                        success: function(dmsg) {
                            notifications_changes('Updating Database', 'database', dmsg.status, dmsg.message);

                            <?php
                            if(count($sIDs) == 0) {?>
                                $("#cve").val('');
                                $("#family").val('');
                                $("#ptable").hide();
                                $("#tick1").hide();
                                $("#tick2").hide();
                            <?php
                            }
                            ?>
                            var sensor_count = 0;

                            $.each(ids, function(k,v){
                                notifications_changes('Updating ' + v.name + ' Sensor ', v.id, 'loading', '');

                                $.ajax({
                                    type: 'POST',
                                    url: 'profiles_ajax.php',
                                    dataType: 'json',
                                    data: { type: 'save_sensor_plugins', sid:'<?php echo $sid ?>', sensor_id: v.id, action: uaction },
                                    success: function(msg) {
                                        var status;

                                        if (msg == null) {
                                            status = 'error';
                                        }
                                        else {
                                            status = msg.status;
                                        }
                                        notifications_changes('Updating ' + v.name + ' Sensor ', v.id, status, msg.message);

                                        if ($('.done').length == <?php echo (count($sIDs)+1) ?>) {
                                            $("#cve").val('');
                                            $("#family").val('');
                                            $("#ptable").hide();
                                            $("#tick1").hide();
                                            $("#tick2").hide();
                                        }

                                        sensor_count++;

                                        if(sensor_count == ids.length) {
                                            $('#AllPlugins,#NonDOS,#DisableAll,#updateplugins').removeAttr('disabled');
                                            $('#AllPlugins,#NonDOS,#DisableAll,#updateplugins').removeClass('disabled');
                                            
                                            $('#dplugins').html('');
                                        }
                                    }
                                });
                            });
                        }
                    });
                });
                <?php
            }
            else if ($disp == "editprefs")
            {
                ?>
                $('#update_preferences').on('click', function(event) {

                    $('#update_preferences').attr("disabled", "disabled");
                    $('#update_preferences').addClass("disabled");

                    clean_updates_table();

                    window.scrollTo(0, 0);

                    $('#updates_info').hide();

                    var ids = <?php echo json_encode($sIDs)?>;

                    notifications_changes('Updating Database', 'database', 'loading', '');

                    $.ajax({
                        type: 'POST',
                        url: 'profiles_ajax.php',
                        data: $('#pform').serialize(),
                        dataType: 'json',
                        success: function(dmsg) {
                            notifications_changes('Updating Database', 'database', dmsg.status, dmsg.message);
                            var sensor_count = 0;
                            $.each(ids, function(k,v){
                                notifications_changes('Updating ' + v.name + ' Sensor ', v.id, 'loading', '');

                                $.ajax({
                                    type: 'POST',
                                    url: 'profiles_ajax.php',
                                    dataType: 'json',
                                    data: { sensor_id: v.id, type: 'save_prefs', sid:'<?php echo $sid ?>' } ,
                                    success: function(msg) {
                                        var status;

                                        if (msg == null) {
                                            status = 'error';
                                        }
                                        else {
                                            status = msg.status;
                                        }
                                        notifications_changes('Updating ' + v.name + ' Sensor ', v.id, status, msg.message);

                                        sensor_count++;

                                        if(sensor_count == ids.length) {
                                            $('#update_preferences').removeAttr("disabled");
                                            $('#update_preferences').removeClass("disabled");
                                        }
                                    }
                                });
                            });
                        }
                    });
                });
                <?php
            }
            else if ( $disp == "edit" || $disp == "new" )
            {
                ?>
                $('.update_profile').on('click', function(event) {

                    $('#update_button').attr("disabled", "disabled");
                    $('#update_button').addClass("disabled");

                    clean_updates_table();

                    window.scrollTo(0, 0);

                    $('#updates_info').hide();

                    var ids = <?php echo json_encode($sIDs)?>;

                    notifications_changes('Updating Database', 'database', 'loading', '');

                    $.ajax({
                        type: 'POST',
                        url: 'profiles_ajax.php',
                        data: $('<?php echo ( ($disp == "edit") ? "#profile_config" : "#create_config" ) ?>').serialize(),
                        dataType: 'json',
                        success: function(dmsg) {
                            notifications_changes('Updating Database', 'database', dmsg.status, dmsg.message);
                            if(dmsg.status != "error") {
                                var sensor_count = 0;
                                var hidew = true;
                                $.each(ids, function(k,v){
                                    notifications_changes('Updating ' + v.name + ' Sensor ', v.id, 'loading', '');

                                    $.ajax({
                                        type: 'POST',
                                        url: 'profiles_ajax.php',
                                        dataType: 'json',
                                        data:  $('<?php echo ( ($disp == "edit") ? "#profile_config" : "#create_config" ) ?>').serialize() + '&sensor_id='+v.id+'&sid=<?php echo $sid ?>' ,
                                        success: function(msg) {
                                            var status;

                                            if (msg == null) {
                                                status = 'error';
                                                hidew = false;
                                            }
                                            else {
                                                status = msg.status;
                                            }
                                            notifications_changes('Updating ' + v.name + ' Sensor ', v.id, status, msg.message);

                                            sensor_count++;

                                            if(sensor_count == ids.length) {
                                                $('#update_button').removeAttr("disabled");
                                                $('#update_button').removeClass("disabled");
                                                if (hidew && typeof(parent.GB_hide) == 'function')
                                                {
                                                    setTimeout('parent.GB_hide()',200);
                                                }
                                            }
                                        }
                                    });
                                });
                            }
                            else {
                                $('#update_button').removeAttr("disabled");
                                $('#update_button').removeClass("disabled");
                            }
                        }
                    });
                });
                <?php
            }
            ?>
        }

        function clean_updates_table () {
            $("#updates_info .done").remove();  // remove old results
        }

        function showEnableBy(){
            $("#cat1").toggle();
            $("#fam1").toggle();
            $("#cat2").toggle();
            $("#fam2").toggle();
        }
        function showEnableByNew() {
            $("#cat1n").toggle();
            $("#fam1n").toggle();
            $("#cat2n").toggle();
            $("#fam2n").toggle();
        }

        function switch_user(select) {
            if(select=='entity' && $('#entity').val()!='-1'){
                $('#user').val('-1');
            }
            else if (select=='user' && $('#user').val()!='-1'){
                $('#entity').val('-1');
            }

            if($('#entity').val()=='-1' && $('#user').val()=='-1') {
                $('#user').val('0');
            }
        }

        function notifications_changes(text, id, type, message)
        {
            if (text != '')
            {
                if( type == 'error')
                {
                    $('#'+id+'_image').attr('src', 'images/cross.png');
                    $('#'+id).removeClass("running");
                    $('#'+id).addClass("done");
                    $('#'+id+'_image').attr('title', message);
                }
                else if (type == 'OK')
                {
                    $('#'+id+'_image').attr('src', 'images/tick.png');
                    $('#'+id).removeClass("running");
                    $('#'+id).addClass("done");
                    $('#'+id+'_image').attr('title', '<?php echo _("Updated successfully") ?>');
                }
                else
                {
                    $("#"+id).remove();
                    var img = '<img title="<?php echo _("Please, wait a few seconds..."); ?>" id="'+id+'_image" src="../pixmaps/loading3.gif" />';
                    $('#updates_info').append('<tr class="running" id="'+id+'"><td style="padding:0px 10px 0px 0px;text-align:right;width:40%;">' + text + '</td><td style="width:20%;">....................................................................</td><td style="padding:0px 0px 0px 10px;width:40%;text-align:left;">'+ img +'</td></tr>');
                }

                if ($('.done').length == <?php echo (count($sIDs)+1) ?>)
                {
                    updateProfilePlugins (<?php echo $sid ?>);
                }

                $('#'+id+'_image').tipTip({defaultPosition:"right",maxWidth:'400px'});

                $('#updates_info').show();
            }
        }

        function deleteProfile(pid) {

            clean_updates_table();

            var res =confirm('<?php echo _("Are you sure you wish to delete this profile?"); ?>');

            if (res)
            {
                <?php
                if ( Vulnerabilities::scanner_type() == "omp" && count($sIDs)>0 )
                {
                    ?>
                    var ids = <?php echo json_encode($sIDs)?>;

                    $.each(ids, function(k,v)
                    {
                        notifications_changes('Updating ' + v.name + ' Sensor ', v.id, 'loading', '');

                        $.ajax({
                            type: 'POST',
                            url: 'profiles_ajax.php',
                            dataType: 'json',
                            data: { sensor_id: v.id, type: 'delete_sensor_profile', sid:pid } ,
                            success: function(msg) {
                                var status;

                                if (msg == null) {
                                    status = 'error';
                                }
                                else {
                                    status = msg.status;
                                }
                                notifications_changes('Updating ' + v.name + ' Sensor ', v.id, status, msg.message);

                                if ($('.done').length == <?php echo (count($sIDs)) ?>) {

                                    notifications_changes('Updating Database', 'database', 'loading', '');
                                    $.ajax({
                                        type: 'POST',
                                        url: 'profiles_ajax.php',
                                        dataType: 'json',
                                        data: { type: 'delete_db_profile', sid:pid } , // pid = profile id
                                        success: function(msg) {

                                            var status;

                                            if (msg == null) {
                                                status = 'error';
                                            }
                                            else {
                                                status = msg.status;
                                            }

                                            notifications_changes('Updating Database', 'database', status, msg.message);

                                            if(status == 'OK') $('#profile'+pid).remove(); // remove profile in table
                                        }
                                    });
                                }
                            }
                        });
                    });
                    <?php
                }
                else
                {
                    ?>
                    notifications_changes('Updating Database', 'database', 'loading', '');
                    $.ajax({
                        type: 'POST',
                        url: 'profiles_ajax.php',
                        dataType: 'json',
                        data: { type: 'delete_db_profile', sid:pid } , // pid = profile id
                        success: function(msg) {

                            var status;

                            if (msg == null) {
                                status = 'error';
                            }
                            else {
                                status = msg.status;
                            }

                            notifications_changes('Updating Database', 'database', status, msg.message);

                            if(status == 'OK') $('#profile'+pid).hide(); // hide profile in table
                        }
                    });
                    <?php
                }
                ?>
            }
        }

    </script>

    <style type="text/css">
        table.gray_border {
            border: 1px solid #C4C0BB;
        }
        table.gray_border2 {
            border: 1px solid #C4C0BB;
        }
        .disabled {
            filter:alpha(opacity=50);
            -moz-opacity:0.5;
            -khtml-opacity: 0.5;
            opacity: 0.5;
        }
        .c_back_button {
            margin: 5px 0px 0px 20px;
        }
        .hand {
            cursor: pointer !important;
        }
        #updates_info {
            width: 800px;
            margin: 5px auto 0px auto;
            display:none;
        }
        #updateplugins
        {
            margin: 0px 0px 0px 15px;
        }
        #loading {
            color:#018C15;
            margin: 0px 0px 10px 0px;
        }
        #pavailable {
            display:none;
            margin:  0px 0px 10px 0px;
        }
    </style>
</head>

<body>

<table id="updates_info" cellspacing="0" cellpadding="0">
    <tr><td colspan="3" class="headerpr_no_bborder"> <?php echo _("Update Status"); ?></td></tr>
</table>

<?php
$pageTitle = "Scanners";

$query              = "SELECT count(*) AS total FROM vuln_nessus_plugins";
$dbconn->SetFetchMode(ADODB_FETCH_BOTH);
$result             = $dbconn->execute($query);
list($pluginscount) = $result->fields[0];

if ($pluginscount==0) {
   die ("<h2>"._("Please run updateplugins.pl script first before using web interface.")."</h2>");
}

function navbar( $sid ) {
    global $profilename, $dbconn;

    $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

    //<h3>Manage Nessus Scan Profiles</h3>

    echo "<center>";

    if ($sid)
    {
        $query  = "SELECT name FROM vuln_nessus_settings WHERE id='$sid'";
        $result = $dbconn->execute($query);
        list($profilename) = $result->fields;

        echo "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"margin-top:10px;\" width=\"800\" class=\"transparent\">";
        echo "<tr><td class=\"headerpr_no_bborder\">";
        echo "        <div class='c_back_button' style='display:block'>";
        echo "            <input type='button' class='av_b_back' onclick=\"document.location.href='settings.php';return false;\"/>";
        echo "        </div>";
        echo "        <span style=\"font-weight:normal;\">"._("EDIT PROFILE").":</span>"." ".mb_convert_encoding($profilename, 'ISO-8859-1', 'UTF-8');
        echo "</td></tr>";
        echo "<tr><td class=\"nobborder\" style=\"padding:0px;\">";
        echo "       <table width=\"100%\"><tr><td class=\"nobborder\" style=\"text-align:center;padding-top:5px;padding-bottom:5px;\">";
        echo "<form>";
        echo "<input type=button id='autoenableb' onclick=\"document.location.href='settings.php?disp=edit&amp;sid=$sid'\" class=\"".(($_GET['disp']=="editauto"||$_GET['disp']=='edit')? "av_b_secondary": "av_b_main")."\" value=\""._("AUTOENABLE")."\">&nbsp;&nbsp;&nbsp;";
        echo "<input type=button id='pluginsb' onclick=\"document.location.href='settings.php?disp=editplugins&amp;sid=$sid'\" class=\"".(($_GET['disp']=='editplugins')? "av_b_secondary":"av_b_main")."\" value=\""._("PLUGINS")."\">&nbsp;&nbsp;&nbsp;";
        echo "<input type=button onclick=\"document.location.href='settings.php?disp=linkplugins&amp;sid=$sid'\" class=\"".(($_GET['disp']=='linkplugins')? "av_b_secondary":"av_b_main")."\" style=\"display:none;\" value=\""._("ImPLUGINS")."\">";
        echo "<input type=button id='prefsb' onclick=\"document.location.href='settings.php?disp=editprefs&amp;sid=$sid'\" class=\"".(($_GET['disp']=='editprefs')? "av_b_secondary":"av_b_main")."\" value=\""._("PREFS")."\">&nbsp;&nbsp;&nbsp;";
        echo "<input type=button id='configb' onclick=\"document.location.href='settings.php?disp=viewconfig&amp;sid=$sid'\" class=\"".(($_GET['disp']=='viewconfig')? "av_b_secondary":"av_b_main")."\" value=\""._("VIEW CONFIG")."\">&nbsp;&nbsp;&nbsp;";
        echo "</form>";
    }

    echo "</center><br>";
}

function new_profile() {
    global $dbconn,$username,$version;

    $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

    //navbar( $sid );
    echo "<center><table class=\"transparent\" style=\"margin-top:10px\" width=\"800\" cellspacing=\"0\" cellpaddin=\"0\">";
    echo "<tr><td class=\"headerpr_no_bborder\">";
    echo "        <div class='c_back_button' style='display:block'>";
    echo "            <input type='button' class='av_b_back' onclick=\"document.location.href='settings.php';return false;\"/>";
    echo "        </div>";
    echo "        "._("New Profile");
    echo "</td></tr>";
    echo "</table></center>";

    echo "<center>";
    echo "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"800\">";
    echo "<tr><td style=\"padding-top:5px;\" class=\"nobborder\">";
    // build pulldown of existing scan policies/profiles in case user
    // wants to clone an existing policy instead of starting from scratch
    $query  = "SELECT id, name, description FROM vuln_nessus_settings";
    $result = $dbconn->GetArray($query);

    $allpolicies  = "<select name='cloneid'>\n";
    $allpolicies .= "<option value=''>"._("None")."</option>\n";

    if($result)
    {
       foreach($result as $sp) {
          if($sp['description']!="") {
            $allpolicies .= "<option value='".$sp['id']."'>".$sp['name']." - ".$sp['description']."</option>\n";
          }
          else {
            $allpolicies .= "<option value='".$sp['id']."'>".$sp['name']."</option>\n";
          }
       }
    }

    $allpolicies .= "</select>";

    echo <<<EOT
<CENTER>
<form method="post" action="settings.php" id="create_config">
<input type="hidden" name="type" value="new">
<table width="650" class="transparent" cellpadding="4" cellspacing="2">
<tr>
EOT;
?>
<div id="div_createprofile" style="display:none;padding-bottom:8px;">
    <br/>
    <img width="16" align="absmiddle" src="./images/loading.gif" border="0" alt="<?php echo _("Applying changes...")?>" title="<?php echo _("Applying changes...")?>">
    &nbsp;<?php echo _("Creating the profile, please wait few seconds...") ?>
    <br/>
</div>

<?php
    echo "<td class='left'>"._("Name").":</td>";
    echo <<<EOT
<td class="left"><input type="text" name="sname" value=""/></td>
</tr>
<tr>
EOT;
    echo "<td class='left'>"._("Description").":</td>";
    echo <<<EOT
<td class="left"><input type="text" name="sdescription" value=""/></td>
</tr>
<tr>
EOT;
    echo "<td class='left'>"._("Clone existing scan policy").":</td><td class='left'>$allpolicies</td>";
    echo <<<EOT
</tr>
EOT;

$users    = Session::get_users_to_assign($dbconn);
$entities = Session::get_entities_to_assign($dbconn);

?>
    <tr>
        <td class='left'><?php echo _("Make this profile available for");?></td>
        <td class='left'>
            <table cellspacing="0" cellpadding="0" class="transparent">
                <tr>
                    <td class='left nobborder'><span style='margin-right:3px'><?php echo _("User:");?></span></td>
                    <td class='nobborder'>
                        <select name="user" style="width:150px" id="user" onchange="switch_user('user');return false;" >

                            <?php

                            $num_users    = 0;
                            $current_user = Session::get_session_user();

                            if ( ! Session::am_i_admin() )
                                $user = (  $user == "" && $entity == "" ) ? $current_user : $user;

                            foreach( $users as $k => $v )
                            {
                                $login = $v->get_login();

                                $selected = ( $login == $user ) ? "selected='selected'": "";
                                $options .= "<option value='".$login."' $selected>$login</option>\n";
                                $num_users++;
                            }

                            if ($num_users == 0)
                                echo "<option value='-1' style='text-align:center !important;'>- "._("No users found")." -</option>";
                            else
                            {
                                echo "<option value='-1' style='text-align:center !important;'>- "._("Select users")." -</option>";
                                if ( Session::am_i_admin() )
                                {
                                    $default_selected = ( ( $user == "" || intval($user) == 0 ) && $entity == "" ) ? "selected='selected'" : "";
                                    echo "<option value='0' $default_selected>"._("ALL")."</option>\n";
                                }

                                echo $options;
                            }

                            ?>
                        </select>
                    </td>

                    <?php if ( !empty($entities) ) { ?>
                    <td style='text-align:center; border:none; !important'><span style='padding:5px;'><?php echo _("OR")?><span></td>

                    <td class='nobborder'><span style='margin-right:3px'><?php echo _("Entity:");?></span></td>
                    <td class='nobborder'>
                        <select name="entity" style="width:170px" id="entity" onchange="switch_user('entity');return false;">
                            <option value="-1" style='text-align:center !important;'>- <?php echo _("Entity not assigned") ?> -</option>
                            <?php
                            foreach ( $entities as $k => $v )
                            {
                                $selected = ( $k == $user_entity ) ? "selected='selected'": "";
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

<?php

echo "<tr style='display:none'>";
echo "<td class='left'>"._("Link scans run by this profile in Network Hosts")."<br>"._("Purpose so that Network Hosts can be tracking full/perfered audits").".</td>";
echo "<td class='left'><input type='checkbox' name='tracker'/><font color='red'>"._("Update Host Tracker \"Network Hosts\" Status")."</font></input></td>";
echo "</tr>";
echo <<<EOT
<tr>
EOT;
echo "<td class='left'>"._("Autoenable plugins option").":</td>";
    echo <<<EOT
<td class='left'><select name="sautoenable"  onChange="showEnableByNew();return false;">
EOT;

echo "<option value=\"C\" selected>"._("Autoenable by category")."</option>";
echo "<option value=\"F\">"._("Autoenable by family")."</option>";
echo <<<EOT
</select></td>
</tr>
</table><BR>
EOT;

   $query="select * from vuln_nessus_category order by name";

   $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

   $result = $dbconn->execute($query);

   echo <<<EOT

<div id="cat2n">
EOT;
   echo "<B>"._("Autoenable plugins in categories").":</B><BR><BR>";
   echo <<<EOT
<table summary="Category Listing" border="0" cellspacing="0" width="650">
EOT;
echo "<tr><th><b>"._("Category")."</b></th>";
echo "<th><b>"._("Enable All")."</b></th>";
echo "<th><b>"._("Enable New")."</b></th>";
echo "<th><b>"._("Disable New")."</b></th>";
echo "<th><b>"._("Disable All")."</b></th>";
echo "<th><b>"._("Intelligent")."</b></th></tr>";


   while (!$result->EOF) {
      list($cid, $category)=$result->fields;

echo "<tr><td style=\"text-align:left;padding-left:3px;\">".strtoupper($category)."</td>";
      echo <<<EOT
<td><input type="radio" name="c_$cid" value="1" checked></td>
<td><input type="radio" name="c_$cid" value="2"></td>
<td><input type="radio" name="c_$cid" value="3"></td>
<td><input type="radio" name="c_$cid" value="4"></td>
<td><input type="radio" name="c_$cid" value="5"></td>
</tr>
EOT;
      $result->MoveNext();
   }
   echo "</table></div>";

   $query="select * from vuln_nessus_family order by name";

   $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

   $result=$dbconn->execute($query);

   echo <<<EOT

<div id="fam2n" style="display:none;">
EOT;
    echo "<B>"._("Autoenable plugins in Families").":</B><BR><BR>";
   echo <<<EOT
<table summary="Family Listing" border="0" cellspacing="2" cellpadding="0" width="650">
EOT;
echo "<tr><th><b>"._("Family")."</b></th>";
echo "<th><b>"._("Enable All")."</b></th>";
echo "<th><b>"._("Enable New")."</b></th>";
echo "<th><b>"._("Disable New")."</b></th>";
echo "<th><b>"._("Disable All")."</b></th>";
echo "<th><b>"._("Intelligent")."</b></th></tr>";


   while (!$result->EOF) {
      list ($fid, $family)=$result->fields;

echo "<tr><td style=\"text-align:left;padding-left:3px;\">$family</td>";
      echo <<<EOT
<td><input type="radio" name="f_$fid" value="1" checked></td>
<td><input type="radio" name="f_$fid" value="2"></td>
<td><input type="radio" name="f_$fid" value="3"></td>
<td><input type="radio" name="f_$fid" value="4"></td>
<td><input type="radio" name="f_$fid" value="5"></td>
</tr>
EOT;

      $result->MoveNext();
   }
   echo <<<EOT
</table></div>
<br>
EOT;
   echo "<input type='button' id='update_button' class='button update_profile' value='"._("Create")."'><br><br>";
   echo <<<EOT
</form></CENTER>
EOT;
echo "</td></tr>";
echo "</table></center>";
}

function edit_autoenable($sid) {
   global $dbconn, $username, $version;

   navbar($sid);

   $query = "select id, name, description, autoenable, type, owner, update_host_tracker
      FROM vuln_nessus_settings where id=$sid";

   $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

   $result=$dbconn->execute($query);

   echo <<<EOT
<form method="post" action="settings.php" id="profile_config">
<input type="hidden" name="type" value="update">
<input type="hidden" name="sid" value="$sid">
EOT;
   list ($sid, $sname, $sdescription, $sautoenable, $stype, $sowner, $tracker )= $result->fields;

   $sname = mb_convert_encoding($sname, 'ISO-8859-1', 'UTF-8');

   //if($stype=='G') { $stc = "checked"; }  else { $stc = ""; }
   if(valid_hex32($sowner))
   {
      $user_entity = $sowner;
   }
   else
   {
      $user     = $sowner;
   }

   $old_user    = $sowner;

   if($tracker=='1') { $cktracker = "checked"; } else { $cktracker = ""; }
   echo <<<EOT
<input type="hidden" name="old_owner" value="$old_user">
<input type="hidden" name="old_name" value="$sname">
<center>
<table cellspacing="2" cellpadding="4">
<tr>
EOT;
   echo "<th>"._("Name").":</th>";
   echo '
   <td><input type="text" name="sname" value="'.$sname.'" size=50/>
</tr>
<tr>
';
   echo "<th>"._("Description").":</th>";
   echo '
   <td><input type="text" name="sdescription" value="'.$sdescription.'" size=50/></td>
</tr>';

$users    = Session::get_users_to_assign($dbconn);
$entities = ( Session::am_i_admin() || ($pro && Acl::am_i_proadmin())  ) ? Session::get_entities_to_assign($dbconn) : null;
?>
    <tr>
        <th><?php echo _("Make this profile available for");?>:</th>
        <td>
            <table cellspacing="0" cellpadding="0" align='center' class="transparent">
                <tr>
                    <td class='nobborder'><span style='margin-right:3px'><?php echo _("User:");?></span></td>
                    <td class='nobborder'>
                        <select name="user" style="width:150px" id="user" onchange="switch_user('user');return false;" >

                            <?php

                            $num_users    = 0;
                            $current_user = Session::get_session_user();

                            if ( ! Session::am_i_admin() )
                                $user = (  $user == "" && $entity == "" ) ? $current_user : $user;

                            foreach( $users as $k => $v )
                            {
                                $login = $v->get_login();

                                $selected = ( $login == $user ) ? "selected='selected'": "";
                                $options .= "<option value='".$login."' $selected>$login</option>\n";
                                $num_users++;
                            }

                            if ($num_users == 0)
                                echo "<option value='-1' style='text-align:center !important;'>- "._("No users found")." -</option>";
                            else
                            {
                                echo "<option value='-1' style='text-align:center !important;'>- "._("Select users")." -</option>";
                                if ( Session::am_i_admin() )
                                {
                                    $default_selected = ( ( $user == "" || intval($user) == 0 ) && $entity == "" ) ? "selected='selected'" : "";
                                    echo "<option value='0' $default_selected>"._("ALL")."</option>\n";
                                }

                                echo $options;
                            }

                            ?>
                        </select>
                    </td>

                    <?php if ( !empty($entities) ) { ?>
                    <td style='text-align:center; border:none; !important'><span style='padding:5px;'><?php echo _("OR")?><span></td>

                    <td class='nobborder'><span style='margin-right:3px'><?php echo _("Entity:");?></span></td>
                    <td class='nobborder'>
                        <select name="entity" style="width:170px" id="entity" onchange="switch_user('entity');return false;">
                            <option value="-1" style='text-align:center !important;'>- <?php echo _("Entity not assigned") ?> -</option>
                            <?php
                            foreach ( $entities as $k => $v )
                            {
                                $selected = ( $k == $user_entity ) ? "selected='selected'": "";
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

<?php

echo "<tr style='display:none'>";
echo "<th>"._("Link scans run by this profile in Network Hosts")."<br>"._("Purpose so that Network Hosts can be tracking full/perfered audits").".</th>";
echo "<td class='left'><input type='checkbox' name='tracker' $cktracker/><font color='red'>"._("Update Host Tracker \"Network Hosts\" Status")."</font></input></td>";
echo "</tr>";
echo "<tr>
<th valign='top' style='background-position:top center;'>"._("Autoenable options").":</th>
<td class='nobborder' style='text-align:center'><SELECT name=\"sautoenable\" onChange=\"showEnableBy();return false;\">";
//echo "<option value=\"N\"";

//   if ($sautoenable=="N") { echo " selected";}
//   echo ">None";
   echo "<option value=\"C\"";
   if ($sautoenable=="C") { echo " selected";}
   echo ">"._("Autoenable by category")."<option value=\"F\"";
   if ($sautoenable=="F") { echo " selected";}
   echo ">"._("Autoenable by family")."</select>";

   echo "<div id=\"cat2\"".(($sautoenable=="C")? "":"style=\"display:none;\"").">";
   echo "<BR><B>"._("Autoenable plugins in categories").":</B><BR><BR>";
   $query = "SELECT t1.cid, t2.name, t1.status FROM vuln_nessus_settings_category as t1,
   vuln_nessus_category as t2
     where t1.sid=$sid
   and t1.cid=t2.id
     order by t2.name";
    // var_dump($query);

   $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

   $result = $dbconn->execute($query);
   echo <<<EOT
<table bordercolor="#6797BF" border="0" cellspacing="2" cellpadding="0">
EOT;
echo "<tr><th>"._("Name")."</th>";
echo "<th>"._("Enable All")."</th>";
echo "<th>"._("Enable New")."</th>";
echo "<th>"._("Disable New")."</th>";
echo "<th>"._("Disable All")."</th>";
echo "<th>"._("Intelligent")."</th></tr>";

   while (!$result->EOF) {
      list ($cid, $name, $status) = $result->fields;

echo "<tr><td style=\"text-align:left;padding-left:3px;\">".strtoupper($name)."</td>";
echo "<td><input type=\"radio\" name=\"c_$cid\" value=\"1\" ";

      if ($status==1) {echo "checked";}
      echo "></td><td><input type=\"radio\" name=\"c_$cid\" value=\"2\" ";
      if ($status==2) {echo "checked";}
      echo "></td><td><input type=\"radio\" name=\"c_$cid\" value=\"3\" ";
      if ($status==3) {echo "checked";}
      echo "></td>";
      echo "<td><input type=\"radio\" name=\"c_$cid\" value=\"4\" ";
      if ($status==4) {echo "checked";}
      echo "></td>";
      echo "<td><input type=\"radio\" name=\"c_$cid\" value=\"5\" ";
      if ($status==5) {echo "checked";}
      echo "></td></tr>";
      $result->MoveNext();
   }
   echo "</table><BR>";
   echo "</div>";

   echo "<div id=\"fam2\"".(($sautoenable=="F")? "":"style=\"display:none;\"").">";
   $query = "select t1.fid, t2.name, t1.status
     from vuln_nessus_settings_family as t1,
   vuln_nessus_family as t2
     where t1.sid=$sid
   and t1.fid=t2.id
     order by t2.name";

   $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

   $result = $dbconn->execute($query);

echo "<BR><B>"._("Autoenable plugins in families").":<BR><BR></B>";
   echo <<<EOT
<table bordercolor="#6797BF" border="0" cellspacing="2" cellpadding="0">
EOT;
echo "<tr><th>"._("Name")."</th>";
echo "<th>"._("Enable All")."</th>";
echo "<th>"._("Enable New")."</th>";
echo "<th>"._("Disable New")."</th>";
echo "<th>"._("Disable All")."</th>";
echo "<th>"._("Intelligent")."</th></tr>";


   while (!$result->EOF) {
      list ($fid, $name, $status) = $result->fields;
      echo "<tr><td style=\"text-align:left;padding-left:3px;\">$name</td>";
      echo "<td><input type=\"radio\" name=\"f_$fid\" value=\"1\" ";
      if ($status==1) {echo "checked";}
      echo "></td>";
      echo "<td><input type=\"radio\" name=\"f_$fid\" value=\"2\" ";
      if ($status==2) {echo "checked";}
      echo "></td>";
      echo "<td><input type=\"radio\" name=\"f_$fid\" value=\"3\" ";
      if ($status==3) {echo "checked";}
      echo "></td>";
      echo "<td><input type=\"radio\" name=\"f_$fid\" value=\"4\" ";
      if ($status==4) {echo "checked";}
      echo "></td>";
      echo "<td><input type=\"radio\" name=\"f_$fid\" value=\"5\" ";
      if ($status==5) {echo "checked";}
      echo "></td></tr>";
      $result->MoveNext();
   }
    echo "</table></div></td></tr></table></center><br/>";
    echo "<input type='button' id='update_button' value='"._("Update")."' class='button update_profile'><br/><br/></form>";
}

function edit_plugins($dbconn, $sid) {
   global $fam;

   navbar( $sid );
?>
<div id='loading'><img width='16' align='absmiddle' src='../pixmaps/loading3.gif' border='0' alt='<?php echo _("Loading") ?>' title='<?php echo _("Loading") ?>' />&nbsp;&nbsp;<?php echo _("Loading, please wait a few of seconds") ?>...</div>

<div id="pavailable"></div>

<?php
   echo <<<EOT
<center>
<form method="post" action="settings.php">
<input type="hidden" name="disp" value="saveplugins" >
<input type="hidden" name="sid" value="$sid" >
<input type="hidden" name="fam" value="$fam" >
EOT;

echo "<input type=\"button\" id=\"AllPlugins\" name=\"AllPlugins\" value=\""._("Enable All")."\" class=\"av_b_secondary small updateplugins\">&nbsp;&nbsp;&nbsp;";
echo "<input type=\"button\" id=\"NonDOS\" name=\"NonDOS\" value=\""._("Enable Non DOS")."\" class=\"av_b_secondary small updateplugins\">&nbsp;&nbsp;&nbsp;";
echo "<input type=\"button\" id=\"DisableAll\" name=\"DisableAll\" value=\""._("Disable All")."\" class=\"av_b_secondary small updateplugins\">&nbsp;&nbsp;&nbsp;";
echo "<br><br><img src='/ossim/pixmaps/warning_icon.png' class='warning-ico'>";
echo _("You may notice that additional plugins have been activated without being selected. Certain plugins may rely on additional plugins to perform the required function(s) and return accurate results.");
echo "<br>";

echo <<<EOT
</form>
</center>
EOT;
   //get all the plugins group by cve
    $cves = array();
    $i = 0;

    $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

    $resultcve=$dbconn->GetArray("select id, cve_id from vuln_nessus_plugins");
    $cveTabs = "";
    $cveContent = "";
    foreach ($resultcve as $cve) {
        $c = explode(",",$cve['cve_id']);
        foreach ($c as $value) {
            $value = trim($value);
            if ($value!="") {
                $tmp = substr($value,0,8);
                $cves[$tmp] = $i;
                $i++;
            }
        }
    }
    // get all the plugin families, ordered by family

    $result=$dbconn->GetArray("Select id, name from vuln_nessus_family order by name");
    $numFams = count($result) - 1;
    echo "<br>";
    echo "<center><table width='100%' class='transparent'><tr><td class=\"nobborder\" width=\"400\"><center>";
    echo "<table class=\"noborder\"><tr border='0' class=\"nobborder\">";
    echo "<th>"._("Family")."</th><td class='nobborder'>";

    $famSelect = "<select id='family' onChange=\"showPluginsByFamily(document.getElementById('family').options[document.getElementById('family').selectedIndex].value, $sid);return false;\">";

    $i = 0;

    $famTabs = "<option value='0' selected='selected'>"._("Select Family")."</option>";
    $famContent = "";

    foreach ($result as $family) {
        $famTabs .= "<option value=\"".$family['id']."\">" . $family['name'] . "</option>\n";
        $i++;
    }
    echo $famSelect . $famTabs . "</select>";
    echo "</td><td class=\"nobborder\"><img id=\"tick1\" style=\"display:none;\" src=\"./images/tick.png\" border=\"0\" alt=\"Filtered by families\" title=\"Filtered by families\"></td></tr>";
    echo "</table></center>";
    echo "</td><td class=\"nobborder\"><center>";
    echo "<table class=\"noborder\"><tr class='nobborder'>";
    echo "<th>"._("CVE Id")."</th>";
    echo "<td class='nobborder'>";
    $cveTabs = "";
    $cveContent = "";
    ksort($cves);
    $j=1;
    $cveTabs .= "<option value='0' selected='selected'>"._("Select CVE Id")."</option>";
    foreach ($cves as $key=>$value){
        $cveTabs .= "<option value='$j'>" . $key . "</option>";
        $j++;
   }
   $cveSelect = "<select id='cve' onChange=\"showPluginsByCVE(document.getElementById('cve').options[document.getElementById('cve').selectedIndex].text,$sid);return false;\">";
   echo $cveSelect . $cveTabs . "</select>";
   echo "</td><td class=\"nobborder\"><img id=\"tick2\" style=\"display:none;\" src=\"./images/tick.png\" border=\"0\" alt=\"Filtered by CVE\" title=\"Filtered by CVE\"></td>";
   echo "</tr></table></center>";
   echo "</td></tr></table></center>";
   echo "<br>";
   echo "<div id=\"dplugins\"></div>";
}

function edit_serverprefs($dbconn, $sid) {

    navbar( $sid );

    $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

   // get the profile prefs for use later

    $uuid = Util::get_encryption_key();
    $sql  = "SELECT t.nessusgroup, t.nessus_id, t.field, t.type, t.value AS def_value, AES_DECRYPT(t.value,'$uuid') AS def_value_decrypt, n.value, AES_DECRYPT(n.value,'$uuid') AS value_decrypt, t.category
            FROM vuln_nessus_preferences_defaults t
            LEFT JOIN vuln_nessus_settings_preferences n
            ON t.nessus_id = n.nessus_id and n.sid = $sid
            ORDER BY category desc, nessusgroup, nessus_id";

    $result = $dbconn->execute($sql);

    if($result === false)
    {
        // SQL error
        echo _("Error").": "._("There was an error with the DB lookup").": ".
        $dbconn->ErrorMsg() . "<br>";
    }

    $counter = 0;


    // display the settings form
    $lastvalue = "";

echo "<center><form method=\"post\" id=\"pform\" action=\"settings.php\">";
echo "<input type=\"hidden\" name=\"type\" value=\"save_prefs\">";
echo "<input type=\"hidden\" name=\"sid\" value=\"$sid\">";
print "<table cellspacing='2' cellpadding='4'>";

  while(!$result->EOF)
  {
        $counter++;

        $nessusgroup = $result->fields['nessusgroup'];
        $nessus_id   = $result->fields['nessus_id'];
        $field       = $result->fields['field'];
        $type        = $result->fields['type'];
        $default     = ( $result->fields['type'] != 'P' || ( $result->fields['type'] == 'P' && empty($result->fields['def_value_decrypt']) ) ) ? $result->fields['def_value']  : $result->fields['def_value_decrypt'];
        $value       = ( $result->fields['type'] != 'P' || ( $result->fields['type'] == 'P' && empty($result->fields['value_decrypt']) ) ) ? $result->fields['value']  : $result->fields['value_decrypt'];
        $category    = $result->fields['category'];

        if ($nessusgroup != $lastvalue)
        {
            print "<tr><th colspan='2'><strong>$nessusgroup</strong></th></tr>";
            $lastvalue = $nessusgroup;
        }

        $vname = "form".$counter;

        print formprint($nessus_id, $field, $vname, $type, $default, $value, $dbconn);

        $result->MoveNext();
   }

   echo "</table>";

   echo "<br/><input type='button' value='"._("Save preferences")."' id='update_preferences'></form></center><br/>";

}

function edit_profile($sid) {
   global $dbconn;

   navbar( $sid );

   $query  = "SELECT name, description from vuln_nessus_settings WHERE id=$sid";

   $dbconn->SetFetchMode(ADODB_FETCH_NUM);

   $result = $dbconn->execute($query);
   list($sname, $sdescription) = $result->fields;

}

function manage_profile_users($sid) {
   global $dbconn;

   $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

   navbar( $sid );

   $query       = "SELECT name FROM vuln_nessus_settings WHERE id=$sid";

   $result      = $dbconn->execute($query);
   list($nname) = $result->fields;


     echo <<<EOT
<br>
<form action="settings.php" method="POST" onSubmit="selectAllOptions('authorized');">
<CENTER>
<TABLE width=60% border=0>

<TR>
     <TD colSpan=3>
     <h4> "$nname" - User Access:</h4>
     </TD>
</TR>
<TR>
<TD valign=top align='center'>Authorized Users<br>
      <input type="hidden" name="disp" value="updateusers">
      <input type="hidden" name="sid" value="$sid">
      <select name="authorized_users[]" id="authorized" style="WIDTH: 187px; HEIGHT: 200px" multiple="multiple" size=20>
EOT;

   //$query = "SELECT t1.username FROM vuln_nessus_settings_users t1
   //   LEFT JOIN vuln_users t2 ON t1.username = t2.pn_uname
   //   WHERE t1.sid=$sid ORDER BY t1.username";
   //$result = $dbconn->execute($query);

   while( list($uname) = $result->fields ) {
      echo "<option value=\"$uname\">$uname</option>\n";
      $result->MoveNext();
   }


     echo <<<EOT

       </select>
    </td>
    <td>
       <input type='button' value='<< Add' onclick="move2(this.form.unauthorized,this.form.authorized )"/><br/><br>
       <input type='button' value='Remove >>' onclick="move2(this.form.authorized,this.form.unauthorized)"/></td>
       <td valign=top align="left">
          <select name="unauth_users[]" id="unauthorized" style="WIDTH: 187px; HEIGHT: 200px" multiple="multiple" size=20>\n";
EOT;

   //$query = "SELECT t1.pn_uname FROM vuln_users t1
   //   LEFT JOIN vuln_nessus_settings_users t2 ON t1.pn_uname = t2.username
   //   AND t2.sid = '$sid'
   //   WHERE t2.username is Null ORDER BY t1.pn_uname";

   $result = $dbconn->execute($query);

   while( list($nname) = $result->fields ) {
      echo "<option value=\"$nname\" >$nname</option>\n";
      $result->MoveNext();

   }
     echo <<<EOT
</select>
</td></tr>
<tr><td colspan="3"><input type='submit' name='submit' value='Update Access'/></td></tr>
</TABLE></CENTER></form>
EOT;

}

function select_profile(){
    global $sid, $username, $dbconn, $version, $nessus_path;

    $args = "";
    
    if (!Session::am_i_admin())
    {
        list($owners, $sqlowners) = Vulnerabilities::get_users_and_entities_filter($dbconn);
        $owners[]   = '0';
        $sql_perms .= " OR owner IN('".implode("', '",$owners)."')";

        $args = "WHERE name='Default' OR name='Deep' OR name='Ultimate' ".$sql_perms;
    }

    $layouts = array();

    $query = "SELECT id, name, description, owner, type FROM vuln_nessus_settings $args ORDER BY name";

    $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

    $result=$dbconn->execute($query);

echo "<CENTER>";
echo "<table class=\"transparent\"><tr><td class=\"sec_title\">"._("Vulnerability Scan Profiles")."</td></tr></table>";
echo "<p>";
echo _("Please select a profile to edit").":";
echo "</p>";
echo "<table class='table_list'>";
echo "<tr>";
echo "<th>"._("Available for")."</th>";
echo "<th>"._("Profile")."</th>";
echo "<th>"._("Description")."</th>";
echo "<th>"._("Action")."</th>";
echo "</tr>";

   $color = 0;

   while (!$result->EOF) {
      $sid          = $result->fields[0];
      $sname        = $result->fields[1];
      $sdescription = $result->fields[2];
      $sowner       = $result->fields[3];
      $stype        = $result->fields[4];

echo "<tr id='profile$sid'>";
if($sowner=="0"){
    echo "<td>"._("All")."</td>";
}
elseif(valid_hex32($sowner)){
    echo "<td style='padding:0px 2px 0px 2px;'>".Session::get_entity_name($dbconn, $sowner)."</td>";
}
else
    echo "<td>".Util::htmlentities($sowner)."</td>";

echo "<td width='200'>".Util::htmlentities($sname)."</td>";
echo "<td width='450'>".Util::htmlentities($sdescription)."</td>";
echo "<td>";

if ( $sname=="Default" || $sname == "Deep" || $sname == "Ultimate" ) {
    echo "<img src=\"images/pencil.png\" class=\"tip disabled\" title=\""._("$sname profile can't be edited, clone it to make changes")."\" />";
    echo "<img src=\"images/delete.gif\" class=\"tip disabled\" title=\""._("$sname profile can't be deleted")."\" />";
}
else {
    if( Vulnerabilities::can_modify_profile($dbconn, $sname, $sowner) ) {
        echo "<a href='settings.php?disp=edit&amp;sid=$sid'><img class='hand' id='edit_".md5($sname.$sowner)."' src='images/pencil.png' ></a>";
    }
    else {
        echo "<img class='disabled' src='images/pencil.png'>";
    }

    if( Vulnerabilities::can_delete_profile($dbconn, $sname, $sowner) ) {
        echo "<img class='hand' src='images/delete.gif'  id='delete_".md5($sname.$sowner)."' onclick='deleteProfile($sid)'>";
    }
    else {
        echo "<img class='disabled' src=\"images/delete.gif\" >";
    }
}

echo "</td>";
echo "</tr>";

      $result->MoveNext();
      $color++;
   }

echo "</table>";
echo "<center>";
echo "<form>";
echo "<br/>";
echo "<input type='button' onclick=\"document.location.href='settings.php?disp=new'\" id=\"new_profile\" value=\""._("Create New Profile")."\"/>";
echo "</form>";
echo "</p>";
echo "</center>";
   // end else

}

function view_config($sid) {
   global $dbconn;

   $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

   navbar( $sid );

   echo "<CENTER><TEXTAREA rows=15 cols=80 ># "._("This file was automagically created")."\n\n";

   if($_SESSION["scanner"]=="nessus") {
       $query = "SELECT t1.id, t1.enabled FROM vuln_nessus_settings_plugins as t1
          LEFT JOIN vuln_nessus_category t2 on t1.category=t2.id
          WHERE t2.name ='scanner' and t1.sid=$sid order by id";
    }
    else {
        $query = "SELECT t3.oid, t1.enabled FROM vuln_nessus_settings_plugins as t1
                LEFT JOIN vuln_nessus_category t2 on t1.category=t2.id
                LEFT JOIN vuln_nessus_plugins t3 on t1.id=t3.id
                WHERE t2.name ='scanner' and t1.sid=$sid order by oid";
    }
   $result = $dbconn->execute($query);
   echo "begin(SCANNER_SET)\n";

   while (list ($id, $enabled) = $result->fields ) {
      $enabled1="yes";
      if ($enabled=="N") $enabled1="no";
      echo " $id = $enabled1\n";
      $result->MoveNext();
   }

   echo "end(SCANNER_SET)\n\n";

   $query = "Select nessus_id, value from vuln_nessus_settings_preferences
      WHERE category='SERVER_PREFS' and sid=$sid";
   $result = $dbconn->execute($query);

   echo "begin(SERVER_PREFS)\n";

   while (list( $nessus_id, $value) = $result->fields) {
      echo " $nessus_id = $value\n";
      $result->MoveNext();
   }

   echo "end(SERVER_PREFS)\n\n";

   $query = "Select nessus_id, value from vuln_nessus_settings_preferences
      WHERE category='PLUGINS_PREFS' and sid=$sid";
   $result = $dbconn->execute($query);

   echo "begin(PLUGINS_PREFS)\n";

   while (list( $nessus_id, $value) = $result->fields ) {
      echo " $nessus_id = $value\n";
      $result->MoveNext();
   }

   echo "end(PLUGINS_PREFS)\n\n";

   if($_SESSION["scanner"]=="nessus") {
   $query = "SELECT t1.id, t1.enabled FROM vuln_nessus_settings_plugins as t1
      LEFT JOIN vuln_nessus_category t2 on t1.category=t2.id
      WHERE t2.name <>'scanner' and t1.sid=$sid order by id";
   }
   else {
      $query = "SELECT t3.oid, t1.enabled FROM vuln_nessus_settings_plugins as t1
      LEFT JOIN vuln_nessus_category t2 on t1.category=t2.id
      LEFT JOIN vuln_nessus_plugins t3 on t1.id=t3.id
      WHERE t2.name <>'scanner' and t1.sid=$sid order by oid";
   }
   $result = $dbconn->execute($query);
   echo "begin(PLUGIN_SET)\n";

   while (list ($id, $enabled) = $result->fields ) {
      $enabled1="yes";
      if ($enabled=="N") $enabled1="no";
      echo " $id = $enabled1\n";
      $result->MoveNext();
   }

   echo "end(PLUGIN_SET)\n\n";
   echo "</TEXTAREA></CENTER>";

}


function formprint($nessus_id, $field, $vname, $type, $default, $value, $dbconn) {

    $retstr = "";
    if ( is_null($value) || $value=="") {
        if ($type == "R") {
            $value = explode(";", $default);
            $value = $value[0];
        }
        else {
            $value = $default;
        }
    }

    if ($type == "C") {
      # Checkbox code here
        $retstr="<tr><td style='text-align:left;width:65%'>$field</td><td><INPUT type=\"checkbox\" name=\"$vname\" value=\"yes\"";
        if ($value=="yes") {
            $retstr.=" checked";
        }
        $retstr.="></td></tr>";
    }
    elseif ($type == "R") {
      # Radio button code here
        $retstr="<tr><td style='text-align:left;width:65%'>$field</td><td>";
        $array = explode(";", $default);
        foreach($array as $myoption) {
            $retstr.="<INPUT type=\"radio\" name=\"$vname\" value=\"".trim($myoption)."\"";
            if ($value == $myoption) $retstr.=" checked";
            $retstr.="> $myoption </option>&nbsp;";
        }
        $retstr.="</td></tr>";
    }
    elseif ($type == "P") {
      # Password code here
        #$retstr="$nessus_id $field <INPUT type=\"password\" name=\"$vname\" value=\"$value\"><BR>";

        $value  =  Util::fake_pass($value);
        $retstr = "<tr><td style='text-align:left;width:65%'>$field</td><td><input type=\"password\" name=\"$vname\" value=\"$value\" autocomplete=\"off\"></td></tr>";
    }
    else {
        // call to avoid XSS attacks
    
        $value = Util::htmlentities($value);    
    
      # Assume it is a text box
        $sufix = (preg_match("/\[file\]/",$nessus_id)) ? "&nbsp;["._("full file path")."]" : "";
        $retstr="<tr><td style='text-align:left;width:65%'>$field $sufix</td><td><INPUT type=\"text\" name=\"$vname\" value=\"$value\"></td></tr>";
    }
    $retstr .= "\n";
    return $retstr;
}

switch($disp) {

   case "edit":
        edit_autoenable($sid);
        break;

    case "editplugins":
        edit_plugins($dbconn, $sid);
        break;

    case "editprefs":
        edit_serverprefs($dbconn, $sid);
        break;

    case "new":
        new_profile();
        break;

    case "viewconfig":
        view_config( $sid );
        break;

    default:
        select_profile();
        break;

}
echo "   </td></tr>";
echo "   </table>";
echo "</td></tr>";
echo "</table>";
echo "</br></br></br>";
$db->close();

require_once 'footer.php';

?>
