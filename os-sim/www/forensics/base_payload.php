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


include_once 'base_conf.php';
include_once "$BASE_path/includes/base_constants.inc.php";
include_once "$BASE_path/includes/base_include.inc.php";

// Check role out and redirect if needed -- Kevin
$roleneeded = 10000;
#$BUser = new BaseUser();
#if (($BUser->hasRole($roleneeded) == 0) && ($Use_Auth_System == 1)) {
#    base_header("Location: " . $BASE_urlpath . "/index.php");
#    exit();
#}
$id       = ImportHTTPVar("id", VAR_DIGIT | VAR_LETTER);
$download = ImportHTTPVar("download", VAR_DIGIT);
if ($download == 1) 
{
    /* Connect to the Alert database */
    $db = NewBASEDBConnection($DBlib_path, $DBtype);
    $db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);
    /* Get the Payload from the database: */
    $sql2    = "SELECT data_payload,binary_data FROM alienvault_siem.extra_data WHERE event_id=unhex('$id')";
    $result2 = $db->baseExecute($sql2);
    $myrow2  = $result2->baseFetchRow();
    $result2->baseFreeRows();
    
    if (empty($myrow2)) 
    {
        $sql2 = "SELECT data_payload,binary_data FROM alienvault.extra_data WHERE event_id=unhex('$id')";
        $result2 = $db->baseExecute($sql2);
        $myrow2 = $result2->baseFetchRow();
        $result2->baseFreeRows();
    }
    
    $encoding = 2; # ascii=2; hex=0; base64=1
    
    if ($myrow2["data_payload"]) 
    {
        /****** database contains hexadecimal *******************/
        if ($encoding == 0) 
        {
            header('HTTP/1.0 200');
            header("Content-type: application/download");
            header("Content-Disposition: attachment; filename=payload_" . $id . ".bin");
            header("Content-Transfer-Encoding: binary");
            ob_start();
            $payload = str_replace("\n", "", $myrow2[0]);
            $len = strlen($payload);
            $half = ($len / 2);
            header("Content-Length: $half");
            
            $counter = 0;
            for ($i = 0; $i < ($len + 32); $i+= 2) 
            {
                $counter++;
                if ($counter > ($len / 2)) {
                    break;
                }
                $byte_hex_representation = ($payload[$i] . $payload[$i + 1]);
                echo chr(hexdec($byte_hex_representation));
            }
            
            ob_end_flush();
            // nothing should come AFTER ob_end_flush().
            /********database contains base64 *******************/
        } 
        elseif ($encoding == 1) 
        {
            header('HTTP/1.0 200');
            header("Content-type: application/octet-stream");
            header("Content-Disposition: attachment; filename=payload" . $id . ".bin");
            header("Content-Transfer-Encoding: binary");
            ob_start();
            $pre_payload = str_replace("\n", "", $myrow2[0]);
            $payload = base64_decode($pre_payload);
            $len = strlen($payload);
            header("Content-Length: $len");
            $counter = 0;
            for ($i = 0; $i < ($len + 16); $i++) {
                $counter++;
                if ($counter > $len) {
                    break;
                }
                $byte = $payload[$i];
                print $byte;
            }
            ob_end_flush();
            // nothing should come AFTER ob_end_flush().
            /********** database contains ASCII ***************/
        } 
        elseif ($encoding == 2) 
        {
            ?>
        	<meta http-equiv="Pragma" content="no-cache"/>
        	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>    
            <?php            
            $msg = _("Output of binary data with storage method ASCII is NOT supported, because this method looses data.<br>
                So you can not definitely rebuild the binary,as one ASCII character may represent different binary values.");            
            echo ossim_error($msg, AV_INFO);            
            ?>
            
            <div style='text-align: center; margin: auto;'>
                <input type='button' onclick="javascript:history.go(-1)" value='<?php echo _("Back")?>'/><br/>
            </div>
            <?php        
        } 
        else 
        {
            ?>
        	<meta http-equiv="Pragma" content="no-cache"/>
        	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>    
            <?php            
            $msg = _("Encoding type not implemented in base_payload.php");            
            echo ossim_error($msg, AV_INFO);            
            ?>
            
            <div style='text-align: center; margin: auto;'>
                <input type='button' onclick="javascript:history.go(-1)" value='<?php echo _("Back")?>'/><br/>
            </div>
            <?php                 
        }
    } 
    else 
    {
        ?>
    	<meta http-equiv="Pragma" content="no-cache"/>
    	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    	<?php            
        $msg = _("No payload data found, that could be downloaded or stored");        
        echo ossim_error($msg, AV_INFO);        
        ?>
        
        <div style='text-align: center; margin: auto;'>
            <input type='button' onclick="javascript:history.go(-1)" value='<?php echo _("Back")?>'/><br/>
        </div>
        <?php    
    }
} 
else if ($download == 2 || $download == 3) 
{
    /*
    * If we have FLoP extended database schema then we can rebuild alert
    * in pcap format which can be used to analyze it via tcpdump or
    * ethereal to use their protocol analyzing features.
    */
    /* Connect to the Alert database. */
    $db = NewBASEDBConnection($DBlib_path, $DBtype);
    $db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);

    /* Get the Payload from the database: */
    $sql2 = "SELECT binary_data,data_payload FROM alienvault_siem.extra_data WHERE event_id=unhex('$id')";
    $result2 = $db->baseExecute($sql2);
    $myrow2 = $result2->baseFetchRow();
    $result2->baseFreeRows(); 
    
    if (empty($myrow2)) 
    {
        $sql2 = "SELECT data_payload,binary_data FROM alienvault.extra_data WHERE event_id=unhex('$id')";
        $result2 = $db->baseExecute($sql2);
        $myrow2 = $result2->baseFetchRow();
        $result2->baseFreeRows();
    }

    if (empty($myrow2["binary_data"])) 
    {
        ?>
    	<meta http-equiv="Pragma" content="no-cache"/>
    	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    	<?php                
        $msg = _("Data not found:<br/>No binary data needed to download pcap.");        
        echo ossim_error($msg, AV_INFO);        
        ?>
        
        <div style='text-align: center; margin: auto;'>
            <input type='button' onclick="javascript:history.go(-1)" value='<?php echo _("Back")?>'/><br/>
        </div>
        <?php 
    	exit();
    }
    $binary = bin2hex($myrow2["binary_data"]);

    header('HTTP/1.0 200');
    header("Content-type: application/octet-stream");
    header("Content-Disposition: attachment; filename=packet_" . $id . ".pcap");
    header("Content-Transfer-Encoding: binary");
    $data = Util::format_payload_extermnal($binary);
    header("Content-Length: ".strlen($data));
    echo $data;
}
else 
{
    ?>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>    
    <?php            
    $msg = _("This page is only intended for downloading purposes; it has no content");    
    echo ossim_error($msg, AV_INFO);    
    ?>
    
    <div style='text-align: center; margin: auto;'>
        <input type='button' onclick="javascript:history.go(-1)" value='<?php echo _("Back")?>'/><br/>
    </div>
    <?php
}
