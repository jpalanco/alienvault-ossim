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

if (!Session::menu_perms("dashboard-menu", "BusinessProcessesEdit") ) 
{
    echo ossim_error(_("You don't have permissions to upload maps"));
    exit();
}


$db            = new ossim_db();
$conn          = $db->connect();

$name          = POST('name');
$description   = POST('description');

$flag_close    = FALSE;


ossim_valid($name,          OSS_ALPHA, OSS_NULLABLE, OSS_DIGIT, OSS_SCORE,  ".,%", 'illegal:'._('Name'));
ossim_valid($description,   OSS_INPUT, OSS_NULLABLE,                               'illegal:'._('Wrong Map Name'));

if (ossim_error())
{
    die(ossim_error());
}

$config = new User_config($conn);
$login  = Session::get_session_user();


if (is_uploaded_file($_FILES['ficheromap']['tmp_name']))
{
    $filename = "maps/" . $name . ".jpg";
    $newid = 0;

    if (preg_match("/map(.*)/", $name, $found))
    {
        $newid = $found[1];
    }

    if(getimagesize($_FILES['ficheromap']['tmp_name']))
    {
        move_uploaded_file($_FILES['ficheromap']['tmp_name'], $filename);

        if (!Session::am_i_admin())  //If I am not an admin, I will add, as default, permission to see and edit the map to the current user.
        {
            $sql    = "INSERT IGNORE INTO risk_maps (map,perm,name) VALUES (UNHEX(?),?,?)";
            $params = array($newid, $_SESSION['_user'], $description);
            $conn->Execute($sql, $params);
        }
        else  //If I am an admin user, I will add permission to see the map to everyone and only edit permission to the admin.
        {
            $sql    = "INSERT IGNORE INTO risk_maps (map,perm,name) VALUES (UNHEX(?),'0', ?)";
            $params = array($newid, $description);
            $conn->Execute($sql, $params);
        }

        $_SESSION['map_new']['error'] = FALSE;
        $_SESSION['map_new']['msg']   = _('New Map Uploaded');
    }
    else
    {
        $_SESSION['map_new']['error'] = TRUE;
        $_SESSION['map_new']['msg']   = _('The Map could not be uploaded');
    }

    $flag_close = TRUE;
}


$db->close();


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title><?php echo _("Riskmaps") ?> - <?php echo _("Upload Map") ?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
        <link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
        <script type="text/javascript" src="../js/jquery.min.js"></script>

        <script type="text/javascript">
            $(document).ready(function()
            {
                <?php
                if($flag_close)
                {
                    ?>
                    if(typeof(parent.GB_hide) == 'function')
                    {
                        parent.GB_hide();
                    }
                    <?php
                    }
                ?>
            });
        </script>
    </head>

    <body>
        <form action="upload_map.php" method='POST' name='f1' enctype="multipart/form-data">
        <table align="center" style="border:0px; margin-top: 30px">
            <tr>
                <td class='left'><?php echo _('Name')?>:</td>
                <td class='left'>
                    <input type='text' class='ne1' size='30' id='description' name='description'/>
                </td>
            </tr>
            <tr>
                <td class='left'><?php echo _('Map File')?>:</td>
                <td class='left'>
                    <input type='hidden' name='name' value="map<?php echo strtoupper(Util::uuid())?>">
                    <input type='file'  size='22' id='ficheromap' name='ficheromap'/>
                </td>
            </tr>
            <tr>
                <td colspan="2" style='padding: 20px 0px; text-align:center;'>
                    <input type='submit' value="<?php echo _('Upload')?>"/>
                </td>
            </tr>
        </table>
        </form>
    </body>
</html>