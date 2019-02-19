<?php
/*******************************************************************************
** OSSIM Forensics Console
** Copyright (C) 2009 OSSIM/AlienVault
** Copyright (C) 2004 BASE Project Team
** Copyright (C) 2000 Carnegie Mellon University
**
** (see the file 'base_main.php' for license details)
**
** Built upon work by Roman Danyliw <rdd@cert.org>, <roman@danyliw.com>
** Built upon work by the BASE Project Team <kjohnson@secureideas.net>
*/

require ("base_conf.php");
require ("vars_session.php");
include_once ("$BASE_path/includes/base_db.inc.php");
$msg = _("No pending tasks.") . "<br>" . _("All tasks successfully completed.");

if ($_SESSION["deletetask"] != "")
{
    $perc  = 0;
    $tasks = FALSE;
    $db    = new ossim_db(true);
    $conn  = $db->snort_connect();
    
    // Search for current background purge tasks
    $conn->Execute("CREATE TABLE IF NOT EXISTS `deletetmp` (`id` int(11) NOT NULL,`perc` int(11) NOT NULL, PRIMARY KEY (`id`))");
    $rs = $conn->Execute('SELECT perc,id FROM deletetmp');
    
    if (!$rs)
    {
        echo _('Error in database');
    }
    else
    {
        while (!$rs->EOF)
        {
            $perc = $rs->fields['perc'];
            $id   = $rs->fields['id'];
            
            echo _("Delete") . " &nbsp;&nbsp;<b>$perc</b>%<br>\n";
            
            if ($perc == 100)
            {
                if (file_exists("/var/tmp/del_" . $id))
                {
                    unlink("/var/tmp/del_" . $id);
                }
                
                $conn->Execute('DELETE FROM deletetmp WHERE id = ?', array($id));
                
                if ($id == $_SESSION["deletetask"])
                {
                    unset($_SESSION["deletetask"]);
                }
            }
            elseif (!file_exists("/var/tmp/del_" . $id))
            {
                $conn->Execute('DELETE FROM deletetmp WHERE id = ?', array($id));
            }
            
            $tasks = TRUE;
            
            $rs->MoveNext();
        }
    }
    
    // Something in session but unknown in deletetmp table
    if (!$tasks)
    {
        $tmptable   = "del_".$_SESSION["deletetask"];
        $tmp_result = $conn->Execute("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'alienvault_siem' AND TABLE_NAME = '$tmptable'");
        if (!$tmp_result->EOF)
        {
            echo _("Processing events.")."<br/>"._("Please be patience,<br/>this may take several minutes...");            
        }
        else
        {
            unset($_SESSION["deletetask"]);
        }
    }
    
    $db->close();
    
}
else
{
    echo $msg;
}
?>
