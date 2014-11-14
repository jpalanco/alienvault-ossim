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


require_once ('av_init.php');
Session::logcheck("analysis-menu", "EventsForensics");

if (!isset($noDisplayMenu)) {
    /*echo "<div class='mainheadermenu'>
    <table width='90%' style='border:0'>
    <tr>
    <td class='menuitem'>
    <a class='menuitem' href='". $BASE_urlpath ."/base_ag_main.php?ag_action=list'>". gettext("Event Group Maintenance")."</a>&nbsp;&nbsp;|&nbsp;&nbsp;
    <a class='menuitem' href='". $BASE_urlpath ."/base_maintenance.php'>". gettext("Cache  _CACHE. Status")."</a>&nbsp;&nbsp;|&nbsp;&nbsp;";*/
    // Commented in (20/02/2009 Granada)
    echo "<div>
        <table width='100%' style='border:0'>
        <tr>
            <td class='administration'>
                <a href='" . $BASE_urlpath . "/base_maintenance.php'>Administration</a>";
    if ($Use_Auth_System == 1) {
        echo ("<a class='menuitem' href='" . $BASE_urlpath . "/base_user.php'>" . gettext("User Preferences") . "</a>&nbsp;&nbsp;|&nbsp;&nbsp;");
        echo ("<a class='menuitem' href='" . $BASE_urlpath . "/base_logout.php'>" . gettext("Logout") . "</a>&nbsp;&nbsp;|&nbsp;&nbsp;");
    }
    //echo "<a class='menuitem' href='". $BASE_urlpath ."/admin/index.php'>". gettext("Administration") ."</a>
    echo "   </td>
        </tr>
    </table>
    </div>";
}
?>


<div class="mainfootertext">
    <a class="largemenuitem" href="http://base.secureideas.net" target="_new">BASE</a> <?php
echo $BASE_VERSION . gettext(" (by <A class='largemenuitem' href='mailto:base@secureideas.net'>Kevin Johnson</A> and the <A class='largemenuitem' href='http://sourceforge.net/project/memberlist.php?group_id=103348'>BASE Project Team</A><BR>Built on ACID by Roman Danyliw )"); ?>
</div>

