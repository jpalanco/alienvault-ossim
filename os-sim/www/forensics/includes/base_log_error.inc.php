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
**/


/**
* Function list:
* - ErrorMessage()
* - FatalError()
 */


defined('_BASE_INC') or die('Accessing this file directly is not allowed.');
require_once ('classes/Util.inc');

function ErrorMessage($message, $color = "#FF0000") {
    $message = Util::htmlentities($message);
    $message = str_ireplace("&lt;BR&gt;", "<br>", $message);
    $message = str_ireplace("&lt;B&gt;", "<b>", $message);
    $message = str_ireplace("&lt;/B&gt;", "</b>", $message);

    echo '<p style="color:' . $color . '">' . $message . '</p><br/>';
}

function FatalError($message) {
    echo '<p style="color:#FF0000; font-weight: bold;">' . _("BASE FATAL ERROR:") . $message . '</p>';
    die();
}



