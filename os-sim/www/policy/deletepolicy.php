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

Session::logcheck("configuration-menu", "PolicyPolicy");


if (!Token::verify('tk_delete_policy', GET('token')))
{
	echo "Action not allowed";
    exit();
}

?>

<html>
<head>
  <title> <?php echo _("OSSIM Framework") ?> </title>
  
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
  
</head>
<body>

  <h1> <?php echo _("Delete policy") ?> </h1>

    <?php
    $id = GET('id');
    
    ossim_valid($id, OSS_HEX . OSS_PUNC, 'illegal:' . _("Policy id"));
    
    if (ossim_error()) 
    {
        die(ossim_error());
    }
    		
    if (!GET('confirm')) 
    {
    ?>
        <p> <?php echo _("Are you sure") ?> ?</p>
        <p>
            <a href="<?php echo $_SERVER["SCRIPT_NAME"] . "?id=$id&confirm=yes"; ?>">
                <?php echo _("Yes") ?> 
            </a>
            &nbsp;&nbsp;&nbsp;
            
            <a href="policy.php">
                <?php echo _("No") ?> 
            </a>
        </p>
        <?php
        
        if (GET('activate') == "change") 
        {
            $db   = new ossim_db();
            $conn = $db->connect();
            $ids  = explode(",", $id);
            
            foreach($ids as $id)
            {
                if ($id != "")
                {
                    Policy::activate($conn, $id);
                }
            } 
            
            $db->close();
        }
        
        exit();
    }
    
    $db   = new ossim_db();
    $conn = $db->connect();
    
    $ids  = explode(",", $id);
    
    foreach ($ids as $id) if ($id != "") 
    {
    	if (!Policy::is_visible($conn, $id))
    	{
    		die(ossim_error(_("You do not have permission to edit this policy")));
    	} 
    	else
    	{
    		Policy::delete($conn, $id);    
    	}
    }
    
    $db->close();
    ?>
    
        <p> <?php echo _("Policy deleted") ?> </p>
        <script>document.location.href="policy.php"</script>


</body>
</html>

