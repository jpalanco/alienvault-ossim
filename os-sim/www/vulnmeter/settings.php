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

$db     = new ossim_db();
$dbconn = $db->connect();
$dbconn->SetFetchMode(ADODB_FETCH_BOTH);

$getParams  = array(
    "disp",
    "item",
    "page",
    "delete",
    "prefs",
    "uid",
    "sid",
    "op",
    "confirm",
    "bEnable"
);

$postParams = array(
    "disp",
    "saveplugins",
    "page",
    "delete",
    "prefs",
    "uid",
    "sid",
    "op",
    "sname",
    "sdescription",
    "item",
    "submit",
    "fam",
    "cloneid",
    "stype",
    "importplugins",
    "bEnable",
    "user",
    "entity"
);


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


ossim_valid($sid, OSS_SHA1, OSS_NULLABLE, 'illegal:' . _("Sid"));

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

list($sensor_list, $total) = Av_sensor::get_list($dbconn);

foreach ($sensor_list as $sensor_id => $sensor_data)
{
    if(intval($sensor_data['properties']['has_vuln_scanner']) == 1)
    {
        $sIDs[] = array( 'name' => $sensor_data['name'], 'id' => $sensor_id );
    }
}


function navbar($dbconn, $sid) {
    global $profilename;

    $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

    if ($sid)
    {
        $query  = "SELECT name FROM vuln_nessus_settings WHERE id='$sid'";
        $result = $dbconn->execute($query);
        list($profilename) = $result->fields;

        echo "<table id='t_nav_bar' class='transparent'>";
        echo "<tr><td class=\"headerpr_no_bborder\">";
        echo "        <div class='c_back_button' style='display:block'>";
        echo "            <input type='button' class='av_b_back' onclick=\"document.location.href='settings.php';return false;\"/>";
        echo "        </div>";
        echo "        <span style=\"font-weight:normal; text-transform:none;\">"._("EDIT PROFILE").":</span>"." ".$profilename;
        echo "</td></tr>";
        echo "<tr><td class=\"nobborder\">";
        echo "<table id='t_edit_profiles'><tr><td class=\"nobborder\">";
        echo "<div id='c_edit_profiles'>";
            echo "<input type='button' class='nav_tab av_b_secondary' name='autoenableb' id='autoenableb' value='edit'/>";
            echo "<input type='button' class='nav_tab av_b_secondary' name='pluginsb' id='pluginsb' value='edit plugins'/>";
            echo "<input type='button' class='nav_tab av_b_secondary' name='prefsb' id='prefsb' value='edit prefs'/>";
            echo "<input type='button' class='nav_tab av_b_secondary' name='configb' id='configb' value='view config'/>";
        echo "</div>";

    }

    echo "<br/>";
}

function new_profile($dbconn) {
    $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

    echo "<table id='t_np_header' class='transparent'>";
    echo "<tr><td class='headerpr_no_bborder'>";
    echo "        <div class='c_back_button' style='display:block'>";
    echo "            <input type='button' class='av_b_back' onclick=\"document.location.href='settings.php';return false;\"/>";
    echo "        </div>";
    echo "        "._("New Profile");
    echo "</td></tr>";
    echo "</table>";

    echo "<table id='t_np_body'>";
    echo "<tr><td style=\"padding-top:5px;\" class=\"nobborder\">";

    $query  = "SELECT id, name, description FROM vuln_nessus_settings WHERE `deleted` = '0'";
    $result = $dbconn->GetArray($query);

    $allpolicies  = "<select id='cloneid' name='cloneid'>\n";
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
<form method="post" action="settings.php" id="create_config">
<input type="hidden" name="type" value="new">
<table id='t_create_profile' class="transparent" cellpadding="4" cellspacing="2">
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
                    <td class='td_user'><span><?php echo _("User:");?></span></td>
                    <td class='nobborder'>
                        <select name="user" style="width:150px" id="user" onchange="switch_user('user');return false;" >
                            <?php
                            $num_users    = 0;
                            $current_user = Session::get_session_user();
                            $options = '';

                            foreach($users as $k => $v)
                            {
                                $login = $v->get_login();

                                $selected = ($login == $current_user && !Session::am_i_admin()) ? "selected='selected'": "";
                                $options .= "<option value='".$login."' $selected>$login</option>\n";
                                $num_users++;
                            }

                            if ($num_users == 0)
                                echo "<option value='-1' style='text-align:center !important;'>- "._("No users found")." -</option>";
                            else
                            {
                                echo "<option value='-1' style='text-align:center !important;'>- "._("Select users")." -</option>";
                                if (Session::am_i_admin())
                                {
                                    echo "<option value='0' selected='selected'>"._("ALL")."</option>\n";
                                }

                                echo $options;
                            }

                            ?>
                        </select>
                    </td>

                    <?php if (!empty($entities)) { ?>
                        <td style='text-align:center; border:none; !important'><span style='padding:5px;'><?php echo _("OR")?><span></td>

                        <td class='nobborder'><span style='margin-right:3px'><?php echo _("Entity:");?></span></td>
                        <td class='nobborder'>
                            <select name="entity" style="width:170px" id="entity" onchange="switch_user('entity');return false;">
                                <option value="-1" style='text-align:center !important;'>- <?php echo _("Entity not assigned") ?> -</option>
                                <?php
                                foreach ($entities as $k => $v)
                                {
                                    echo "<option value='$k'>$v</option>";
                                }
                                ?>
                            </select>
                        </td>
                    <?php } ?>
                </tr>
            </table>
        </td>
    </tr>

    <tr class='family_info'>
        <td colspan="2" class="left"><?php echo _("Autoenable plugins by family")?>:</td>
    </tr>
    </table>
    <br/>

    <?php
    $query = "select * from vuln_nessus_family order by name";

    $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

    $result=$dbconn->execute($query);

    $dt_enable_all = "<div style='text-align: center;'>"._("The current ones will be enabled but new ones WILL NOT be enabled by default")."</div>";
    $dt_enable_new = "<div style='text-align: center;'>"._("Current ones and new ones will be enabled by default")."</div>";
    $dt_disable_all = "<div style='text-align: center;'>"._("All plugins will be disabled")."</div>";

    echo <<<EOT

<div id="fam2n">
EOT;
    echo <<<EOT
<table summary="Family Listing" class='family_info' id='t_families' cellspacing="2" cellpadding="0">
EOT;
    echo "<tr><th><div>"._("Family")."</div></th>";
    echo "<th>
              <input type='checkbox' class='chk-fam-all-columns' id='chk-fam-enable-all' name='chk-fam-enable-all'/>
              <div class='autoenable_info' data-title=\"".$dt_enable_all."\">"._("Enable All")."</div>
          </th>";
    echo "<th>
              <input type='checkbox' class='chk-fam-all-columns' id='chk-fam-enable-new' name='chk-fam-enable-new'/>
              <div class='autoenable_info' data-title=\"".$dt_enable_new."\">"._("Enable New")."</div>
          </th>";
    echo "<th>
              <input type='checkbox' class='chk-fam-all-columns' id='chk-fam-disable-all' name='chk-fam-disable-all'/>
              <div class='autoenable_info' data-title=\"".$dt_disable_all."\">"._("Disable All")."</div>
          </th>";
    echo "</tr>";


    while (!$result->EOF) {
        $fid =$result->fields["id"];
        $family =$result->fields["name"];

        echo "<tr><td style=\"text-align:left;padding-left:3px;\">$family</td>";
        echo <<<EOT
<td><input type="radio" name="f_$fid" class='radio-fam radio-fam-enable-all' value="1" checked="checked"></td>
<td><input type="radio" name="f_$fid" class='radio-fam radio-fam-enable-new' value="2"></td>
<td><input type="radio" name="f_$fid" class='radio-fam radio-fam-disable-all' value="3"></td>
</tr>
EOT;

        $result->MoveNext();
    }
    echo <<<EOT
</table></div>
<br/>
EOT;
    echo "<div id='c_new_profile'><input type='button' id='update_button' class='button update_profile' value='"._("Create")."'></div>";
    echo <<<EOT
</form>
EOT;
    echo "</td></tr>";
    echo "</table>";
}

function edit_autoenable($dbconn, $sid) {
    navbar($dbconn, $sid);

    $query = "select id, name, description, owner FROM vuln_nessus_settings where id='$sid'";

    $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

    $result=$dbconn->execute($query);

    echo <<<EOT
<form method="post" action="settings.php" id="profile_config">
<input type="hidden" name="type" id="type" value="update"/>
<input type="hidden" name="sid" id="sid" value="$sid"/>
EOT;

    list ($sid, $sname, $sdescription, $sowner) = $result->fields;

    $user_entity = '';
    $user = '';

    if(security_class::valid_hex32($sowner))
    {
        $user_entity = $sowner;
    }
    else
    {
        $user = $sowner;
    }

    $old_user = $sowner;

    echo <<<EOT
<input type="hidden" name="old_owner" value="$old_user">
<input type="hidden" name="old_name" value="$sname">
<table style='width: 100%' cellspacing="2" cellpadding="4">
<tr>
EOT;
    echo "<th>"._("Name").":</th>";
    echo '
   <td class="left"><input type="text" name="sname" value="'.$sname.'" size=50/>
</tr>
<tr>
';
    echo "<th>"._("Description").":</th>";
    echo '
   <td class="left"><input type="text" name="sdescription" value="'.$sdescription.'" size=50/></td>
</tr>';

    $pro      = Session::is_pro();
    $users    = Session::get_users_to_assign($dbconn);
    $entities = (Session::am_i_admin() || ($pro && Acl::am_i_proadmin())) ? Session::get_entities_to_assign($dbconn) : null;

    ?>
    <tr>
        <th><?php echo _("Make this profile available for");?>:</th>
        <td class="left">
            <table cellspacing="0" cellpadding="0" class="transparent">
                <tr>
                    <td class='td_user'><span><?php echo _("User:");?></span></td>
                    <td class='nobborder'>
                        <select name="user" style="width:150px" id="user" onchange="switch_user('user');return false;" >

                            <?php
                            $num_users    = 0;
                            $current_user = Session::get_session_user();

                            if (!Session::am_i_admin()) {
                                $user = ($user == "" && $user_entity == "") ? $current_user : $user;
                            }

                            $options = '';
                            foreach($users as $k => $v)
                            {
                                $login = $v->get_login();

                                $selected = ($login == $user)  ? "selected='selected'": "";
                                $options .= "<option value='".$login."' $selected>$login</option>\n";
                                $num_users++;
                            }

                            if ($num_users == 0)
                                echo "<option value='-1' style='text-align:center !important;'>- "._("No users found")." -</option>";
                            else
                            {
                                echo "<option value='-1' style='text-align:center !important;'>- "._("Select users")." -</option>";
                                if (Session::am_i_admin())
                                {
                                    $default_selected = (($user == "" || intval($user) == 0 ) && $user_entity == "") ? "selected='selected'" : "";
                                    echo "<option value='0' $default_selected>"._("ALL")."</option>\n";
                                }

                                echo $options;
                            }
                            ?>
                        </select>
                    </td>

                    <?php
                    if (!empty($entities)) {
                        ?>
                        <td style='text-align:center; border:none; !important'><span style='padding:5px;'><?php echo _("OR")?><span></td>

                        <td class='nobborder'><span style='margin-right:3px'><?php echo _("Entity:");?></span></td>
                        <td class='nobborder'>
                            <select name="entity" style="width:170px" id="entity" onchange="switch_user('entity');return false;">
                                <option value="-1" style='text-align:center !important;'>- <?php echo _("Entity not assigned") ?> -</option>
                                <?php
                                foreach ($entities as $k => $v)
                                {
                                    $selected = ($k == $user_entity) ? "selected='selected'": "";
                                    echo "<option value='$k' $selected>$v</option>";
                                }
                                ?>
                            </select>
                        </td>
                        <?php
                    }
                    ?>
                </tr>
            </table>
        </td>
    </tr>

    <?php

    echo "<tr>
        <th valign='top' style='background-position:top center;'>"._("Autoenable options by family").":</th>
        <td class='nobborder left'>
            <div id='fam2'>";

    $query = "select t1.fid, t2.name, t1.status
             from vuln_nessus_settings_family as t1, vuln_nessus_family as t2
             where t1.sid='$sid' and t1.fid=t2.id
             order by t2.name";

    $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

    $result = $dbconn->execute($query);

    $dt_enable_all = "<div style='text-align: center;'>"._("The current ones will be enabled but new ones WILL NOT be enabled by default")."</div>";
    $dt_enable_new = "<div style='text-align: center;'>"._("Current ones and new ones will be enabled by default")."</div>";
    $dt_disable_all = "<div style='text-align: center;'>"._("All plugins will be disabled")."</div>";

    echo <<<EOT
<table style="width: 100%;" cellspacing="2" cellpadding="0">
EOT;
    echo "<tr><th><div>"._("Family")."</div></th>";
    echo "<th>
              <input type='checkbox' class='chk-fam-all-columns' id='chk-fam-enable-all' name='chk-fam-enable-all'/>
              <div class='autoenable_info' data-title=\"".$dt_enable_all."\">"._("Enable All")."</div>
          </th>";
    echo "<th>
              <input type='checkbox' class='chk-fam-all-columns' id='chk-fam-enable-new' name='chk-fam-enable-new'/>
              <div class='autoenable_info' data-title=\"".$dt_enable_new."\">"._("Enable New")."</div>
          </th>";
    echo "<th>
              <input type='checkbox' class='chk-fam-all-columns' id='chk-fam-disable-all' name='chk-fam-disable-all'/>
              <div class='autoenable_info' data-title=\"".$dt_disable_all."\">"._("Disable All")."</div>
          </th>";
    echo "</tr>";


    while (!$result->EOF) {
        $fid = $result->fields["fid"];
        $name = $result->fields["name"];
        $status = $result->fields["status"];

        $checked_fam_enable_all = ($status == 1) ? " checked='checked'" : "";
        $checked_fam_enable_new = ($status == 2) ? " checked='checked'" : "";
        $checked_fam_disable_all = ($status == 3) ? " checked='checked'" : "";

        echo "<tr>
                <td style=\"text-align:left;padding-left:3px;\">$name</td>
                <td><input type='radio' class='radio-fam radio-fam-enable-all' name='f_$fid' value='1' $checked_fam_enable_all/></td>
                <td><input type='radio' class='radio-fam radio-fam-enable-new' name='f_$fid' value='2' $checked_fam_enable_new/></td>
                <td><input type='radio' class='radio-fam radio-fam-disable-all' name='f_$fid' value='3' $checked_fam_disable_all/></td>
              </tr>";

        $result->MoveNext();
    }
    echo "</table></div></td></tr></table>";
    echo "<div id='c_update'><input type='button' id='update_button' value='"._("Update")."' class='button update_profile'></div></form>";
}

function edit_plugins($dbconn, $sid) {
    global $fam;

    navbar($dbconn, $sid);

    $config_nt = array(
        'content' => _("You may notice that additional plugins have been activated without being selected. Certain plugins may rely on additional plugins to perform the required function(s) and return accurate results."),
        'options' => array (
            'type'          => 'nf_warning',
            'cancel_button' => FALSE
        ),
        'style'   => 'width: 70%; margin: 30px auto; text-align: center;'
    );

    $nt = new Notification('nt_1', $config_nt);


    echo "<div id='p_info'></div>";

    echo "<div class='warning-message'>".$nt->show()."</div>";

    echo "<table id='t_search'>
            <tr>
                <th>"._("Family")."</th>
                <td class='nobborder'>
                    <input id='ac_family' class='ac_input' name='ac_family' type='text' placeholder='"._("Type here to search by family ...")."'/>
                    <input type='hidden' name='family' id='family' value=''/>
                </td>
                <th>"._("Category")."</th>
                <td class='nobborder'>
                    <input id='ac_category' class='ac_input' name='ac_category' type='text' placeholder='"._("Type here to search by category ...")."'/>
                    <input type='hidden' name='category' id='category' value=''/>
                </td>
            </tr>
            <tr>
                <th>"._("Vulnerability name")."</th>
                <td class='nobborder'>
                    <input id='ac_plugin' class='ac_input' name='ac_plugin' type='text' placeholder='"._("Type here to search by vulnerability ...")."'/>
                    <input type='hidden' name='plugin' id='plugin' value=''/>
                </td>
                <th>"._("CVE")."</th>
                <td class='nobborder'>
                    <input id='ac_cve' class='ac_input' name='ac_cve' type='text' placeholder='"._("Type here to search by CVE ...")."'/>
                    <input type='hidden' name='cve' id='cve' value=''/>
                </td>
            </tr>
        </table>";

    echo "<div id='c_search'><input type='button' id='search_plugins' name='search_plugins' class='small' value='"._("Search")."'/></div>";


    echo "<div id='c_dd_actions'>
              <button id='dd_actions' class='small av_b_secondary' data-dropdown='#dropdown-actions'>"._('Search Actions')."&nbsp;&#x25be;</button>
              <div id='dropdown-actions' data-bind='dropdown-actions' class='dropdown dropdown-close dropdown-tip dropdown-anchor-right dropdown-relative hidden'>
                  <ul class='dropdown-menu'>
                      <li><a href='javascript:void(0)' id='act-enable-all'>"._("Enable All")."</a></li>
                      <li><a href='javascript:void(0)' id='act-disable-all'>"._("Disable All")."</a></li>
                  </ul>
              </div>
          </div>";

    echo "<div id='dplugins'>
            
            <table id='table_data_plugins' class='table_data'>
                <thead>
                    <tr>
                        <th id='th_all_plugins'><input type='checkbox' id='select-all-plugins' name='select-all-plugins' disabled='disabled'/></th>
                        <th id='th_vuln_id'>"._('Vulnerability ID')."</th>
                        <th>"._('Vulnerability Name')."</th>
                        <th id='th_cve'>"._('CVE ID')."</th>
                        <th>"._('Plugin Family')."</th>
                        <th id='th_category'>"._('Plugin Category')."</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan='6'>"._("No plugins found")."</td></tr>
                </tbody>
            </table>
            
            <div id='c_save_plugins'>
                <input type='button' name='save_plugins' id='save_plugins' class='button' value='"._('Save')."'/>
            </div>
        </div>";
}

function edit_serverprefs($dbconn, $sid) {

    navbar($dbconn, $sid);

    $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

    // get the profile prefs for use later

    $uuid = Util::get_encryption_key();
    $sql  = "SELECT t.nessusgroup, t.nessus_id, t.field, t.type, t.value AS def_value, AES_DECRYPT(t.value,'$uuid') AS def_value_decrypt, n.value, AES_DECRYPT(n.value,'$uuid') AS value_decrypt, t.category
            FROM vuln_nessus_preferences_defaults t
            LEFT JOIN vuln_nessus_settings_preferences n
            ON t.nessus_id = n.nessus_id and n.sid = '$sid'
            ORDER BY category desc, nessusgroup, nessus_id";

    $result = $dbconn->execute($sql);

    if($result === false)
    {
        // SQL error
        echo _("Error").": "._("There was an error with the DB lookup").": ".
            $dbconn->ErrorMsg() . "<br/>";
    }

    $counter = 0;


    // display the settings form
    $lastvalue = "";

    echo "<form method='post' id='pform' action='settings.php'>";
    echo "<input type='hidden' name='type' value='save_prefs'>";
    echo "<input type='hidden' name='sid' value='$sid'>";
    print "<table id='t_form_settings'>";

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
            print "<tr><th colspan='2'><strong>".Util::htmlentities($nessusgroup)."</strong></th></tr>";
            $lastvalue = $nessusgroup;
        }

        $vname = "form".$counter;

        print formprint($nessus_id, $field, $vname, $type, $default, $value, $dbconn);

        $result->MoveNext();
    }

    echo "</table>";

    echo "<div id='c_save_preferences'><input type='button' value='"._("Save Preferences")."' id='update_preferences'></div></form>";

}

function select_profile(){
    global $sid, $username, $dbconn;

    $args = "";
    $sql_perms = '';

    if (!Session::am_i_admin())
    {
        list($owners, $sqlowners) = Vulnerabilities::get_users_and_entities_filter($dbconn);
        $owners[] = '0';
        $args = "AND `default` = 1 OR owner IN('".implode("', '",$owners)."')";
    }

    $layouts = array();

    $query = "SELECT id, `name`, description, owner, `default` FROM vuln_nessus_settings WHERE `deleted` = '0' $args ORDER BY name";

    $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

    $result=$dbconn->execute($query);

    echo "<table id='t_dsp' class='table_list'>";
    echo "<tr><td colspan='4' class=\"sec_title\">"._("Vulnerability Scan Profiles")."</td></tr>";
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
        $sdefault     = $result->fields[4];

        echo "<tr id='profile$sid'>";

        if($sowner=="0"){
            echo "<td>"._("All")."</td>";
        }
        elseif(security_class::valid_hex32($sowner)){
            echo "<td style='padding:0px 2px 0px 2px;'>".Session::get_entity_name($dbconn, $sowner)."</td>";
        }
        else {
            echo "<td>" . Util::htmlentities($sowner) . "</td>";
        }

        echo "<td width='200'>".Util::htmlentities($sname)."</td>";
        echo "<td width='450'>".Util::htmlentities($sdescription)."</td>";
        echo "<td>";

        if ($sdefault) {
            echo "<img src=\"images/pencil.png\" class=\"tip disabled\" data-title=\""._("$sname profile can't be edited, clone it to make changes")."\" />";
            echo "<img src=\"images/delete.gif\" class=\"tip disabled\" data-title=\""._("$sname profile can't be deleted")."\" />";
        }
        else {
            if( Vulnerabilities::can_modify_profile($dbconn, $sname, $sowner) ) {
                echo "<a href='settings.php?disp=edit&amp;sid=$sid'><img class='hand' id='edit_".md5($sname.$sowner)."' src='images/pencil.png' ></a>";
            }
            else {
                echo "<img class='disabled' src='images/pencil.png'>";
            }

            if(Vulnerabilities::can_delete_profile($dbconn, $sname, $sowner)) {
                echo "<img class='hand' src='images/delete.gif'  id='delete_".md5($sname.$sowner)."' onclick='delete_profile(\"$sid\")'>";
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
    echo "<form>";
    echo "<div id='c_new_profile'><input type='button' onclick=\"document.location.href='settings.php?disp=new'\" id=\"new_profile\" value=\""._("Create New Profile")."\"/></div>";
    echo "</form>";
}

function view_config($dbconn, $sid) {
    $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

    navbar($dbconn, $sid);

    echo "<textarea rows=45 cols=120 ># "._("This file was automatically created")."\n\n";

    $query = "SELECT t3.oid, t1.enabled FROM vuln_nessus_settings_plugins as t1
                INNER JOIN vuln_nessus_category t2 on t1.category=t2.id
                INNER JOIN vuln_nessus_plugins t3 on t1.id=t3.id
                WHERE t2.name ='scanner' and t1.sid='$sid'
                ORDER BY oid";

    $result = $dbconn->execute($query);
    echo "begin(SCANNER_SET)\n";

    while (list ($id, $enabled) = $result->fields ) {
        $enabled1="yes";

        if ($enabled=="N") {
            $enabled1="no";
        }

        echo " $id = $enabled1\n";
        $result->MoveNext();
    }

    echo "end(SCANNER_SET)\n\n";

    $query = "Select nessus_id, value from vuln_nessus_settings_preferences
              WHERE category='SERVER_PREFS' and sid='$sid'";

    $result = $dbconn->execute($query);

    echo "begin(SERVER_PREFS)\n";

    while (list( $nessus_id, $value) = $result->fields) {
        echo " $nessus_id = $value\n";
        $result->MoveNext();
    }

    echo "end(SERVER_PREFS)\n\n";

    $query = "Select nessus_id, value from vuln_nessus_settings_preferences
              WHERE category='PLUGINS_PREFS' and sid='$sid'";

    $result = $dbconn->execute($query);

    echo "begin(PLUGINS_PREFS)\n";

    while (list( $nessus_id, $value) = $result->fields ) {
        echo " $nessus_id = $value\n";
        $result->MoveNext();
    }

    echo "end(PLUGINS_PREFS)\n\n";

    $query = "SELECT t3.oid, t1.enabled FROM vuln_nessus_settings_plugins as t1
              INNER JOIN vuln_nessus_category t2 on t1.category=t2.id
              INNER JOIN vuln_nessus_plugins t3 on t1.id=t3.id
              WHERE t2.name <> 'scanner' and t1.sid='$sid' order by oid";

    $result = $dbconn->execute($query);
    echo "begin(PLUGIN_SET)\n";

    while (list ($id, $enabled) = $result->fields ) {
        $enabled1="yes";
        if ($enabled=="N") {
            $enabled1="no";
        }

        echo " $id = $enabled1\n";
        $result->MoveNext();
    }

    echo "end(PLUGIN_SET)\n\n";
    echo "</TEXTAREA>";
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
    
    $l_css_styles = 'text-align:left;width:45%';
    $r_css_styles = 'text-align:left;width:55%';

    if ($type == "C") {
        //Checkbox code here
        $retstr="<tr><td style='$l_css_styles'>$field</td><td style='$r_css_styles'><input type=\"checkbox\" name=\"$vname\" value=\"yes\"";
        if ($value=="yes") {
            $retstr.=" checked";
        }
        $retstr.="></td></tr>";
    }
    elseif ($type == "R") {
        //Radio button code here
        $retstr="<tr><td style='$l_css_styles'>$field</td><td style='$r_css_styles'>";
        $array = explode(";", $default);
        foreach($array as $myoption) {
            $checked = ($value == $myoption) ? "checked='checked'" : '';
            $retstr.="<input type='radio' name='$vname' id='$vname' value='".trim($myoption)."' $checked/>";
            $retstr.= "<label for='$vname'>$myoption</label><br/>";
        }
        $retstr.="</td></tr>";
    }
    elseif ($type == "P") {
        //Password code here
        $value  =  Util::fake_pass($value);
        $retstr = "<tr><td style='$l_css_styles'>$field</td><td style='$r_css_styles'><input type=\"password\" name=\"$vname\" value=\"$value\" autocomplete=\"off\"></td></tr>";
    }
    elseif ($type == "F") {
        $value = Util::htmlentities($value);
        $retstr = "<tr><td style='$l_css_styles'>$field</td><td style='$r_css_styles'><textarea cols='50' rows='5' name=\"$vname\" autocomplete=\"off\">$value</textarea></td></tr>";
    }
    else {
        $value = Util::htmlentities($value);
        $retstr = "<tr><td style='$l_css_styles'>$field</td><td style='$r_css_styles'><input style='width:370px;' type=\"text\" name=\"$vname\" value=\"$value\"></td></tr>";
    }
    $retstr .= "\n";
    return $retstr;
}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title> <?php echo _("Vulnmeter"); ?> </title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache">

    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/jquery-ui.min.js"></script>
    <script type="text/javascript" src="../js/vulnmeter.js.php"></script>
    <script type="text/javascript" src="../js/jquery.tipTip-ajax.js"></script>
    <script type="text/javascript" src="../js/jquery.autocomplete.pack.js"></script>
    <script type="text/javascript" src="../js/jquery.dataTables.js"></script>
    <script type="text/javascript" src="../js/jquery.dataTables.plugins.js"></script>
    <script type="text/javascript" src="../js/utils.js"></script>
    <script type="text/javascript" src="../js/av_gvm_plugins.js.php"></script>
    <script type="text/javascript" src="/ossim/js/jquery.dropdown.js" charset="utf-8"></script>

    <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    <link rel="stylesheet" type="text/css" href="../style/jquery.autocomplete.css"/>
    <link rel="stylesheet" type="text/css" href="../style/tipTip.css"/>
    <link rel="stylesheet" type="text/css" href="../style/jquery.dataTables.css"/>
    <link rel="stylesheet" type="text/css" href="../style/jquery-ui.css"/>
    <link rel="stylesheet" type="text/css" href="/ossim/style/jquery.dropdown.css"/>


    <?php require ("../host_report_menu.php") ?>
    <script type="text/javascript">

        var sid = '<?php echo $sid;?>';
        var num_of_sids = <?php echo count($sIDs)?>;
        var ids = <?php echo json_encode($sIDs)?>;

        var gvm_plugins_db = new gvm_plugins_db(sid);

        function clean_updates_table () {
            $("#updates_info .done").remove();  // remove old results
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

        function notifications_changes(text, id, type, message) {
            if (text != '')
            {
                if( type == 'error')
                {
                    $('#'+id+'_image').attr('src', 'images/cross.png');
                    $('#'+id).removeClass("running");
                    $('#'+id).addClass("done");
                    $('#'+id+'_image').attr('title', message);
                }
                else if (type == 'OK' || type == 'success')
                {
                    $('#'+id+'_image').attr('src', 'images/tick.png');
                    $('#'+id).removeClass("running");
                    $('#'+id).addClass("done");
                    $('#'+id+'_image').attr('title', '<?php echo _("Updated successfully") ?>');
                }
                else
                {
                    $("#"+id).remove();
                    var img = '<img title="<?php echo _("Please, wait a few seconds ..."); ?>" id="'+id+'_image" src="../pixmaps/loading3.gif" />';
                    $('#updates_info').append('<tr class="running" id="'+id+'"><td style="padding:0px 10px 0px 0px;text-align:right;width:40%;">' + text + '</td><td style="width:20%;">....................................................................</td><td style="padding:0px 0px 0px 10px;width:40%;text-align:left;">'+ img +'</td></tr>');
                }

                $('#'+id+'_image').tipTip({
                    defaultPosition:"right",
                    maxWidth:'400px'}
                );

                $('#updates_info').show();
            }
        }


        function delete_profile(pid) {
           
            var msg  = "<?php echo Util::js_entities(_('Are you sure you wish to delete this profile?')) ?>";
            var opts = {"yes": "<?php echo _('Yes') ?>", "no": "<?php echo _('No') ?>"};

            av_confirm(msg, opts).done(function(){
                window.scrollTo(0, 0);
                clean_updates_table();
                show_loading_box('#c_settings_content', '<?php echo _("Deleting profile ...")?>');
                
                if (num_of_sids > 0) {
                    $.each(ids, function(k,v) {
                        notifications_changes('Updating ' + v.name + ' Sensor ', v.id, 'loading', '');

                        $.ajax({
                            type: 'POST',
                            url: 'profiles_ajax.php',
                            dataType: 'json',
                            data: {
                                sensor_id:
                                v.id,
                                type: 'delete_sensor_profile',
                                sid:pid
                            },
                            success: function(data) {
                                var status = (data == null) ? 'error' : data.status;

                                notifications_changes('Updating ' + v.name + ' Sensor ', v.id, status, data.message);

                                if ($('.done').length == num_of_sids) {

                                    notifications_changes('Updating Database', 'database', 'loading', '');
                                    $.ajax({
                                        type: 'POST',
                                        url: 'profiles_ajax.php',
                                        dataType: 'json',
                                        data: { type: 'delete_db_profile', sid:pid } , // pid = profile id
                                        success: function(data) {

                                            status = (data == null) ? 'error' : data.status;

                                            notifications_changes('Updating Database', 'database', status, data.message);

                                            // Remove profile in table
                                            if(status == 'OK') {
                                                $('#profile'+pid).remove();
                                            }
                                        },
                                        complete: function(){
                                            remove_loading_box();
                                        }
                                    });
                                }
                            },
                            error: function(){
                                remove_loading_box();
                            }
                        });
                    });

                } else {
                    notifications_changes('Updating Database', 'database', 'loading', '');
                    $.ajax({
                        type: 'POST',
                        url: 'profiles_ajax.php',
                        dataType: 'json',
                        data: {
                            type: 'delete_db_profile',
                            sid: pid
                        },
                        success: function(data) {
                            var status = (data == null) ? 'error' : data.status;

                            notifications_changes('Updating Database', 'database', status, data.message);

                            // Hide profile in table
                            if(status == 'OK') {
                                $('#profile'+pid).hide();
                            }
                        },
                        complete: function(){
                            remove_loading_box();
                        }
                    });
                }
            });
        }

        function remove_loading_box(){
            $('.l_box').remove();
            $('.w_overlay').remove();
        }
        
        function show_loading_box(id, message){
            $('.w_overlay').remove();

            if ($('.w_overlay').length < 1)
            {
                var height = (id == 'body') ? $.getDocHeight() : $(id).height();
                $(id).append('<div class="w_overlay" style="height:'+height+'px;"></div>');
            }

            if ($('.l_box').length < 1)
            {
                var config  = {
                    content: message,
                    style: 'width: 300px; top: 38%; padding: 5px 0px; left: 50%; margin-left: -175px;',
                    cancel_button: false
                };

                var loading_box = Message.show_loading_box('s_box', config);

                $(id).append('<div class="l_box" style="display:none;">'+loading_box+'</div>');
            }
            else
            {
                $('.l_box .r_lp').html(message);
            }

            $('.l_box').show();
        }


        function set_chk_fam_status(chk_id){
            var total = $('.radio-fam').length;
            total = parseInt(total/4);

            var radio_class = '.radio-'+ chk_id.replace('chk-', '');
            var total_by_family = parseInt($(radio_class + ':checked').length);

            if (total_by_family == total){
                $('#' + chk_id).prop('disabled', true);
                $('#' + chk_id).prop('checked', true);
            } else {
                $('#' + chk_id).prop('disabled', false);
                $('#' + chk_id).prop('checked', false);
            }
        }

        function postload() {
            <?php
            if ($disp == "edit plugins")
            {
                ?>
                //Show datatable with all plugins
                load_plugins();

                //Create autocomplete search boxes
                create_autocomplete("family");
                create_autocomplete("category");
                create_autocomplete("plugin");
                create_autocomplete("cve");

                $('#search_plugins').on('click', function(){
                    var enabled_plugins = gvm_plugins_db.get_enabled_plugins();
                    var disabled_plugins = gvm_plugins_db.get_disabled_plugins();
                    var s_actions = gvm_plugins_db.get_actions();

                    var plugins_modified = Object.keys(enabled_plugins).length > 0 || Object.keys(disabled_plugins).length > 0;
                    var all_plugins_modified = (s_actions['enable_all'] == 1 || s_actions['disable_all'] == 1);

                    if (all_plugins_modified || plugins_modified){
                        var msg  = "<?php echo Util::js_entities(_('Changes have not been saved. Do you want to continue?')) ?>";
                        var opts = {"yes": "<?php echo _('Yes') ?>", "no": "<?php echo _('No') ?>"};

                        av_confirm(msg, opts).done(function(){
                            reload_plugins();
                        });
                    } else {
                        reload_plugins();
                    }
                });

                $('#save_plugins').on('click', function() {
                    var enabled_plugins = Object.keys(gvm_plugins_db.get_enabled_plugins()).join(',');
                    var disabled_plugins = Object.keys(gvm_plugins_db.get_disabled_plugins()).join(',');

                    var s_filters = gvm_plugins_db.get_filters();
                    var s_actions = gvm_plugins_db.get_actions();

                    var sp_data = {
                        type: 'save_database_plugins',
                        sid: sid,
                        family_id: s_filters['family_id'],
                        category_id: s_filters['category_id'],
                        cve: s_filters['cve'],
                        plugin: s_filters['plugin'],
                        enable_all: s_actions['enable_all'],
                        disable_all: s_actions['disable_all'],
                        enabled_plugins: enabled_plugins,
                        disabled_plugins: disabled_plugins
                    };

                    $.ajax({
                        type: 'POST',
                        url: 'profiles_ajax.php',
                        data: sp_data,
                        dataType: 'json',
                        beforeSend: function(){
                            $('#updates_info').hide();
                            clean_updates_table();
                            window.scrollTo(0, 0);

                            notifications_changes('Updating Database', 'database', 'loading', '');
                            
                            $('#save_plugins').addClass("av_b_f_processing").prop("disabled", true);
                            $('#search_plugins').prop("disabled", true);
                            $('#dd_actions').prop("disabled", true);
                        },
                        success: function(data) {
                            var status = (data == null) ? 'error' : data.status;

                            notifications_changes('Updating Database', 'database', status, data.message);

                            if(status != "error") {
                                var sensor_count = 0;
                                var families = Object.keys(data.message);

                                var sp_data = {
                                    type: 'save_sensor_plugins',
                                    sid: sid,
                                    families: families.join(','),
                                };
                
                                $.each(ids, function(k, v){
                                    notifications_changes('Updating ' + v.name + ' Sensor ', v.id, 'loading', '');
                                    
                                    if (families.length > 0){
                                        sp_data['sensor_id'] = v.id;
                                        
                                        $.ajax({
                                            type: 'POST',
                                            url: 'profiles_ajax.php',
                                            dataType: 'json',
                                            data: sp_data,
                                            success: function(data) {
                                                notifications_changes(v.name + ' Sensor update', v.id, data.status, data.message);

                                                sensor_count++;

                                                if(sensor_count == ids.length) {
                                                    bind_save_plugins_actions();
                                                    reload_plugins();
                                                }
                                            }
                                        });
                                    } else {
                                        notifications_changes(v.name + ' Sensor update', v.id, 'OK', '');
                                        bind_save_plugins_actions();
                                    }
                                });
                                
                              
                            } else {
                                bind_save_plugins_actions();
                            }
                        }
                    });
                });
                <?php
            }
            else if ($disp == "edit prefs")
            {
                ?>
                $('#update_preferences').on('click', function(event) {
                    $.ajax({
                        type: 'POST',
                        url: 'profiles_ajax.php',
                        data: $('#pform').serialize(),
                        dataType: 'json',
                        beforeSend: function(){
                            $(this).addClass("av_b_f_processing");
                            $(this).prop("disabled", true);

                            clean_updates_table();
                            window.scrollTo(0, 0);
                            $('#updates_info').hide();
                            
                            notifications_changes('Updating Database', 'database', 'loading', '');
                        },
                        success: function(data) {
                            var status = (data == null) ? 'error' : data.status;

                            notifications_changes('Updating Database', 'database', status, data.message);
                            var sensor_count = 0;

                            $.each(ids, function(k,v){
                                notifications_changes('Updating ' + v.name + ' Sensor ', v.id, 'loading', '');

                                $.ajax({
                                    type: 'POST',
                                    url: 'profiles_ajax.php',
                                    dataType: 'json',
                                    data: {
                                        sensor_id: v.id,
                                        type: 'save_prefs',
                                        sid: sid
                                    },
                                    success: function(data) {
                                        var status = (data == null) ? 'error' : data.status;

                                        notifications_changes('Updating ' + v.name + ' Sensor ', v.id, status, data.message);

                                        sensor_count++;

                                        if(sensor_count == ids.length) {
                                            $('#update_preferences').removeClass("av_b_f_processing");
                                            $('#update_preferences').prop("disabled", false);
                                        }
                                    }
                                });
                            });
                        }
                    });
                });
                <?php
            }
            else if ($disp == "edit" || $disp == "new")
            {
                ?>
                $('.update_profile').on('click', function(event) {
                    $.ajax({
                        type: 'POST',
                        url: 'profiles_ajax.php',
                        data: $('<?php echo ( ($disp == "edit") ? "#profile_config" : "#create_config" ) ?>').serialize(),
                        dataType: 'json',
                        beforeSend: function(){
                            $('#update_button').addClass("av_b_f_processing");
                            $('#update_button').prop("disabled", true);

                            clean_updates_table();
                            window.scrollTo(0, 0);
                            $('#updates_info').hide();

                            notifications_changes('Updating Database', 'database', 'loading', '');
                        },
                        success: function(data) {

                           var status = (data == null) ? 'error' : data.status;

                            notifications_changes('Updating Database', 'database', status, data.message);

                            if(data.status != "error") {
                                var sensor_count = 0;
                                var hidew = true;

                                $.each(ids, function(k,v){
                                    notifications_changes('Updating ' + v.name + ' Sensor ', v.id, 'loading', '');

                                    $.ajax({
                                        type: 'POST',
                                        url: 'profiles_ajax.php',
                                        dataType: 'json',
                                        data: $('<?php echo ( ($disp == "edit") ? "#profile_config" : "#create_config" ) ?>').serialize() + '&sensor_id='+v.id+'&sid=' + sid,
                                        success: function(data) {
                                            var status = (data == null) ? 'error' : data.status;

                                            if (status == 'error'){
                                                hidew = false;
                                            }

                                            notifications_changes('Updating ' + v.name + ' Sensor ', v.id, status, data.message);

                                            sensor_count++;

                                            if(sensor_count == ids.length) {
                                                $('#update_button').prop('disabled', false);
                                                $('#update_button').removeClass("av_b_f_processing");

                                                if (hidew == true && typeof(parent.GB_hide) == 'function') {
                                                    setTimeout('parent.GB_hide()',200);
                                                }
                                            }
                                        }
                                    });
                                });
                            }
                            else {
                                 $('#update_button').prop('disabled', false);
                                 $('#update_button').removeClass("av_b_f_processing");
                            }
                        }
                    });
                });

                $('#cloneid').on('change', function(){
                    var current_profile = $('#cloneid').val();
                    if (current_profile != ''){
                        $('.family_info').hide();
                    } else {
                        $('.family_info').show();
                    }
                });

                $('.chk-fam-all-columns').each(function() {
                    var chk_id = $(this).attr('id');
                    set_chk_fam_status(chk_id);
                });

                $('.chk-fam-all-columns').on('click', function() {

                    $('.chk-fam-all-columns').prop('disabled', false);
                    $('.chk-fam-all-columns').prop('checked', false);

                    $(this).prop('disabled', true);
                    $(this).prop('checked', true);

                    var chk_id = $(this).attr('id');
                    var radio_class = '.radio-'+ chk_id.replace('chk-', '');

                    $(radio_class).prop('checked', true);
                });

                $('.radio-fam').on('click', function() {
                    $('.chk-fam-all-columns').each(function() {
                        var chk_id = $(this).attr('id');
                        set_chk_fam_status(chk_id);
                    });
                });

                $(".autoenable_info").tipTip({
                    maxWidth: '250px',
                    attribute: 'data-title',
                    defaultPosition:'top'
                });
                <?php
            }
            ?>

            $(document).ready(function(){
                var active_tab = '';

                <?php
                if ($_GET['disp'] == 'edit')
                {
                    ?>
                    active_tab = 'autoenableb';
                    <?php
                }
                elseif ($_GET['disp'] == 'edit plugins')
                {
                    ?>
                    active_tab = 'pluginsb';
                    <?php
                }
                elseif ($_GET['disp'] == 'edit prefs')
                {
                    ?>
                    active_tab = 'prefsb';
                    <?php
                }
                elseif ($_GET['disp'] == 'view config')
                {
                    ?>
                    active_tab = 'configb';
                    <?php
                }
                ?>

                if (typeof(active_tab) != '') {
                    $('#' + active_tab).removeClass('av_b_secondary');
                    $('#' + active_tab).addClass('av_b_main');
                }

                $('.nav_tab').on('click', function(event) {
                    var action = $('#' + event.srcElement.id).val();
                    var url = 'settings.php?disp=' + action + '&sid=' + sid;

                    if (action != '') {
                        $('.nav_tab').prop('disabled', true);
                        $('.nav_tab').addClass('disabled', 'av_disabled');

                        show_loading_box('body', '<?php echo _("Loading section ...")?>');

                        setTimeout(function(){
                            document.location.href = url;
                        }, 200);
                    }
                });
            });
        }

    </script>

    <style type="text/css">
        #c_settings {
            margin-bottom: 30px;
           
        }
        
        #c_settings_content {
            position: relative;
        }

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

        .td_user{
            padding-left: 0px;
            border-bottom: 0px;
            text-align: left;
        }
        
        
        .c_back_button {
            margin: 5px 0px 0px 20px;
        }

        #t_nav_bar{
            margin: 10px auto;
            width: 850px;
        }

        #updates_info {
            width: 850px;
            margin: 5px auto 0px auto;
            display:none;
        }
        

        .hand {
            cursor: pointer !important;
        }

        #loading_plugins{
            margin-right: 8px;
        }

        #c_update {
            text-align: center;
            margin: 20px auto;
        }

        #p_info {
            margin: 5px auto;
            text-align: center;
        }

        #p_info.error {
            color: red;
        }

        #c_total_plugins_enabled, #c_total_plugins{
            font-weight: bold;
        }

        #cloneid {
            max-width: 300px;
        }

        #t_np_header{
            margin: 10px auto;
            width: 850px;
        }

        #t_np_body{
            width: 850px;
            margin: auto;
        }

        #t_dsp{
            margin: 10px auto;
            width: 90%;
        }

        #t_dsp img{
            margin-left: 3px;
            margin-right: 3px;
        }

        #t_create_profile {
            width: 100%;
            margin: auto;
        }

        #c_new_profile {
            margin: 10px auto 0 auto;
            width: 90%;
            text-align: center;
        }
        
        #t_edit_profiles {
            text-align:center;
            padding-top:5px;
            padding-bottom:5px;
            width: 100%;
        }
        
        #c_edit_profiles {
            margin: auto;
            text-align: center;
            padding: 5px 0;
        }
        
        #c_save_preferences{
            text-align: center;
            margin: 20px auto;
        }

        #t_form_settings{
            width: 100%;
        }

        #t_form_settings th{
           white-space: break-spaces;
        }

        #t_families {
            width: 100%;
        }

        #t_families tr th div {
            display: block;
            margin-top: 2px;
            font-weight: bold;
        }

        #fam2 {
            margin-top: 10px;
        }

        #t_search{
            margin: 20px 0;
        }

        #t_search th, #t_search td{
            padding: 5px;
        }

        #ac_family, #ac_category, #ac_cve, #ac_plugin {
            width: 300px;
        }

        #c_search {
            padding-bottom: 20px;
            margin: auto;
            text-align: center;
        }

        #c_dd_actions{
            position: relative;
        }

        #dd_actions{
            position: absolute;
            top: 3px;
            right: 0;
            z-index: 1000;
            font-size: 11px;
        }

        #dplugins {
            min-height: 300px;
        }

        .nav_tab {
            margin: 8px 10px;
        }

        .w_overlay
        {
            position: absolute;
            width: 100%;
            height: auto;
            margin: auto;
            text-align: center;
            top: 0px;
            left: 0px;
            z-index: 250000;
            filter: alpha(opacity=40);
            -moz-opacity: 0.4;
            -khtml-opacity: 0.4;
            opacity: 0.4;
            background: #FFFFFF;
        }

        #table_data_plugins #th_all_plugins {
            width: 50px;
        }

        #table_data_plugins #th_vuln_id {
            width: 100px;
        }

        #table_data_plugins #th_cve {
            width: 100px;
        }

        #table_data_plugins #th_category {
            width: 100px;
        }

        #c_save_plugins{
            margin: 20px auto;
            text-align: center;
        }

        .plugin_info:hover {
            cursor: pointer;
        }
     </style>
</head>

<body>

<div id="c_settings">

    <table id="updates_info" cellspacing="0" cellpadding="0">
        <tr><td colspan="3" class="headerpr_no_bborder"> <?php echo _("Update Status"); ?></td></tr>
    </table>

    <div id="c_settings_content">
    
<?php
$query = "SELECT count(*) AS total FROM vuln_nessus_plugins";
$dbconn->SetFetchMode(ADODB_FETCH_BOTH);
$result = $dbconn->execute($query);
$plugins_count = $result->fields[0];

if ($plugins_count == 0) {
    $config_nt = array(
        'content' => _("No plugins found.  Please contact support for further assistance."),
        'options' => array (
            'type' => 'nf_error',
            'cancel_button' => FALSE
        ),
        'style' => 'width: 98%; margin: 50px auto; text-align: center;'
    );

    $nt = new Notification('nt_1', $config_nt);
    $nt->show();
    die();
}

switch($disp) {

   case "edit":
        edit_autoenable($dbconn, $sid);
        break;

    case "edit plugins":
        edit_plugins($dbconn, $sid);
        break;

    case "edit prefs":
        edit_serverprefs($dbconn, $sid);
        break;

    case "new":
        new_profile($dbconn);
        break;

    case "view config":
        view_config($dbconn, $sid);
        break;

    default:
        select_profile();
        break;

}

echo "   </td></tr>";
echo "   </table>";
echo "</td></tr>";
echo "</table>";
?>

    </div>
</div>

<?php
$db->close();

require_once 'footer.php';
