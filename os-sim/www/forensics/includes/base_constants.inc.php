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


/* IP encapsulated layer4 proto */
define("UDP", 17);
define("TCP", 6);
define("ICMP", 1);
define("SOURCE_PORT", 1);
define("DEST_PORT", 2);
define("SOURCE_IP", 1);
define("DEST_IP", 2);
/* Page ID */
define("PAGE_QRY_ALERTS", 1);
define("PAGE_STAT_ALERTS", 2);
define("PAGE_STAT_SENSOR", 3);
define("PAGE_QRY_AG", 4);
define("PAGE_ALERT_DISPLAY", 5);
define("PAGE_STAT_IPLINK", 6);
define("PAGE_STAT_CLASS", 7);
define("PAGE_STAT_UADDR", 8);
define("PAGE_STAT_PORTS", 9);
define("NULL_IP", "256.256.256.256");
/* Criteria Field count */
define("IPADDR_CFCNT", 11);
define("TIME_CFCNT", 10);
define("PROTO_CFCNT", 6);
define("TCPFLAGS_CFCNT", 7);
define("PAYLOAD_CFCNT", 5);
/* Database connection method */
define("DB_CONNECT", 2);
define("DB_PCONNECT", 1);
/* */
define("VAR_DIGIT", 1);
define("VAR_LETTER", 2);
define("VAR_ULETTER", 4);
define("VAR_LLETTER", 8);
define("VAR_ALPHA", 16);
define("VAR_PUNC", 32);
define("VAR_SPACE", 64);
define("VAR_FSLASH", 128);
define("VAR_PERIOD", 256);
define("VAR_OPERATOR", 512);
define("VAR_OPAREN", 1024); /*  (   */
define("VAR_CPAREN", 2048); /*  )   */
define("VAR_USCORE", 4096);
define("VAR_AT", 8192);
define("VAR_SCORE", 16384);
define("VAR_BOOLEAN", 32768);
define("VAR_HEX", 65536);
?>
