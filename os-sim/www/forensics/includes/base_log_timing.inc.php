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
* - EventTiming()
* - Mark()
* - PrintTiming()
* - PrintForensicsTiming()
*
* Classes list:
* - EventTiming
*/


defined('_BASE_INC') or die('Accessing this file directly is not allowed.');
require_once ('classes/Util.inc');

class EventTiming {
    var $start_time;
    var $num_events;
    var $event_log;
    var $verbose;
    function EventTiming($verbose) {
        $this->num_events = 0;
        $this->verbose = $verbose;
        $this->start_time = time();
        $this->Mark("Page Load");
    }
    function Mark($desc) {
        $this->event_log[$this->num_events++] = array(
            time() ,
            $desc
        );
    }
    function PrintTiming() {
        /*if ($this->verbose > 0) {
            echo "\n\n<!-- Timing Information -->\n" . "<div class='systemdebug'>[" . gettext("Loaded in") . " " . (time() - ($this->start_time)) . " " . gettext("seconds") . "]</div>\n";
        }
        if ($this->verbose > 1) {
            for ($i = 1; $i < $this->num_events; $i++) echo "<LI>" . Util::htmlentities($this->event_log[$i][1]) . " [" . ($this->event_log[$i][0] - ($this->event_log[$i - 1][0])) . " " . gettext("seconds") . "]\n";
        }*/
    }
    function PrintForensicsTiming() {
       /* echo "\n\n<!-- Timing Information -->\n" . "<script type='text/javascript'>
			//document.getElementById('forensics_time').innerHTML = '[" . gettext("Loaded in") . " " . (time() - ($this->start_time)) . " " . gettext("seconds") . "]'
			</script>\n";*/
    }
}
?>