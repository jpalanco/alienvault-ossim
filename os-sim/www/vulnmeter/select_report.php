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

Session::logcheck("environment-menu", "EventsVulnerabilities");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
<head>
    <title><?php echo gettext("Vulnmeter");?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache">
    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
</head>
<body>
<?php

$freport = GET("freport");
$sreport = GET("sreport");

ossim_valid($freport, OSS_DIGIT,               'illegal:' . _('First report id'));
ossim_valid($sreport, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _('Second report id'));

if (ossim_error()) 
{
    die(ossim_error());
}

$db     = new ossim_db();
$dbconn = $db->connect();

$dbconn->SetFetchMode(ADODB_FETCH_BOTH);

// get ossim server version

$version = $conf->get_conf('ossim_server_version');

$reports = array();

// check permissions

$query_onlyuser = '';

list($arruser, $user) = Vulnerabilities::get_users_and_entities_filter($dbconn);

// select report ids

if(!empty($arruser)) { $query_onlyuser = " AND username in ($user)"; }

if($freport != '' && $sreport != '') 
{
    $query = "SELECT report_id, name, scantime FROM vuln_nessus_reports where 1=1 $query_onlyuser ORDER BY scantime DESC";
}
else 
{
    $query = "SELECT report_id, name, scantime FROM vuln_nessus_reports where report_id!=$freport $query_onlyuser ORDER BY scantime DESC";
}

$result = $dbconn->Execute($query);

$tz = Util::get_timezone();

while (!$result->EOF) 
{
    if($tz==0) 
    {
        $date = preg_replace('/(\d\d\d\d)(\d+\d+)(\d+\d+)(\d+\d+)(\d+\d+)(\d+\d+)/i', '$1-$2-$3 $4:$5:$6', $result->fields["scantime"]);
    }
    else 
    {
        $date = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($result->fields['scantime'])+3600*$tz);
    }
    
    $result->fields['name'] = preg_replace('/\d+\s-\s/', '', $result->fields['name']);
    
    $reports[$result->fields['report_id']] = $date . " - ". $result->fields['name'];
    
    $result->MoveNext();
}

if(count($reports) == 0 && GET("submit") != '') 
{
    ?>
    <script type="text/javascript">
        parent.GB_close();
    </script>
    <?php
}
else if($freport != '' && $sreport != '' && array_key_exists ($freport , $reports) && array_key_exists ($sreport , $reports)) 
{
    ?>
    <script type="text/javascript">
        top.frames['main'].freport = "<?php echo $freport ?>";
        top.frames['main'].sreport = "<?php echo $sreport ?>"; 
        parent.GB_hide();
    </script>
    <?php
}
?>
<form action="select_report.php" method="get">
    <input type="hidden" name="freport" value="<?php echo $freport;?>"/>
    <table width="90%" style="margin:auto" class="transparent">
    <?php
    if(count($reports)) 
    {
        ?>
        <tr height="30">
            <td class="nobborder" style="text-align:right">
                <strong><?php echo gettext("Second report:");?></strong>
            </td>
            <td class="nobborder" style="text-align:left;padding-left:5px;">
                <select name="sreport">
                    <?php
                    foreach($reports as $key => $value) 
                    {
                        ?>
                          <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                        <?php
                    }
                    ?>
                </select>
            </td>
        </tr>
        <?php
    }
    else
    {
        ?>
        <tr height="30">
            <td class="nobborder" style="text-align:center">
                <span style="color:red;font-weight:bold"><?php echo _("There are no more reports.");?></span>
            </td>
        </tr>
        <?php
        }
        ?>
        <tr height="30">
            <td colspan="2" class="nobborder" style="text-align:center">
                <input type="submit" name="submit" value="<?php echo _('Compare');?>" />
            </td>
        </tr>
    </table>
</form>
<?php
$dbconn->close();
?>
</body>
</html>