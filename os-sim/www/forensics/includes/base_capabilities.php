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


// Definitions for capabilities -- Add here as needed.
define(CAPA_MAIL, 1);
define(CAPA_PMAIL, 2);
define(CAPA_PEARDB, 3);
define(CAPA_PGRAPH, 4);
// Capabilities Registry class definition
class CapaRegistry {
    var $CAPAREG; // Registry hash, uses CAPA_ definitions as key.
    // Constructor
    function CapaRegistry() {
        /* Automatically detect capabilities. Future development
        * should be appended here.
        */
        // Mail
        if (function_exists('mail')) {
            $this->CAPAREG[CAPA_MAIL] = true;
        } else {
            $this->CAPAREG[CAPA_MAIL] = false;
        }
        // PEAR::MAIL
        @include "Mail.php";
        if (class_exists("Mail")) {
            $this->CAPAREG[CAPA_PMAIL] = true;
        } else {
            $this->CAPAREG[CAPA_PMAIL] = false;
        }
        // PEAR::DB
        @include "DB.php";
        if (class_exists("DB")) {
            $this->CAPAREG[CAPA_PEARDB] = true;
        } else {
            $this->CAPAREG[CAPA_PEARDB] = false;
        }
        // PEAR::Image_Graph
        @include "Image_Graph.php";
        if (class_exists("Image_Graph")) {
            $this->CAPAREG[CAPA_PGRAPH] = true;
        } else {
            $this->CAPAREG[CAPA_PGRAPH] = false;
        }
        // Add checks here as needed.
        
    }
    // Capability checking function. Pass it the definitions used above.
    function hasCapa($capability) {
        if (array_key_exists($capability, $this->CAPAREG)) {
            return $this->CAPAREG[$capability];
        } else {
            return false;
        }
    }
}
$CAPAREG = new CapaRegistry();
?>
