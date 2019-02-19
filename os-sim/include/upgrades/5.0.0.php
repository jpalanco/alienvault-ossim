<?php
/* 5.0.0 mysql upgrade */
ini_set('include_path', '/usr/share/ossim/include/');
require_once 'classes/upgrade.inc';


class upgrade_500 extends Upgrade
{

    function start_upgrade(){
       echo  "<br/>";
       echo  "<br/>";
       echo  "<br/>";
       echo  _("Due to this upgrade being quite big, your browser might not show the 'end of upgrade' message. If you see the browser has stopped loading the page, reload and your system should be upgraded.");
       echo  "<br/>";
       echo  "<br/>";
       echo  "<br/>";    
    }

    function end_upgrade($logfile)
    {
        $conn = new ossim_db();
        $db   = $conn->connect();
        
        //
        // PROPERTIES
        //
        $properties = array();

        $db->StartTrans();
        
        $rs = $db->Execute("SELECT hex(host_id) as id,property_ref,last_modified,source_id,value,extra,tzone FROM alienvault.host_properties WHERE property_ref>0");
        while (!$rs->EOF) 
        {
            $properties[] = $rs->fields;
            $rs->MoveNext();
        }

        $db->Execute("DELETE FROM alienvault.host_properties");
        @file_put_contents($logfile, $db->ErrorMsg(), FILE_APPEND);

        foreach ($properties as $prop) 
        {
            $values = json_decode($prop['value'],true);
            $sql    = "INSERT IGNORE INTO alienvault.host_properties (host_id, property_ref, last_modified, source_id, value, extra, tzone) VALUES (UNHEX(?), ? ,? ,? ,? ,? ,?)";

            if (json_last_error() === JSON_ERROR_NONE && is_array($values))
            {
                foreach ($values as $value)
                {
                    if ($prop['property_ref']==3)
                    {
                        $value = preg_replace("/\b(\w+)\s+\\1\b/i", "$1", preg_replace("/(.*?):(.*)/","$1 $2",$value));
                    }
                    elseif ($prop['property_ref']==8)
                    {
                        $value = preg_replace("/\|/","@",$value);
                    }
                    $params = array (
                        $prop['id'],
                        $prop['property_ref'],
                        $prop['last_modified'],
                        $prop['source_id'],
                        $value,
                        $prop['extra'],
                        $prop['tzone']
                    );
                    $db->Execute($sql, $params);
                    @file_put_contents($logfile, $db->ErrorMsg(), FILE_APPEND);
                    
                    if ($prop['property_ref']==3)
                    {
                        break; // Only the first OS
                    }
                }
            }
            else
            {
                if ($prop['property_ref']==3)
                {
                    $prop['value'] = preg_replace("/\b(\w+)\s+\\1\b/i", "$1", preg_replace("/(.*?):(.*)/","$1 $2",$prop['value']));
                }
                elseif ($prop['property_ref']==8)
                {
                    $prop['value'] = preg_replace("/\|/","@",$prop['value']);
                }
                $params = array (
                    $prop['id'],
                    $prop['property_ref'],
                    $prop['last_modified'],
                    $prop['source_id'],
                    $prop['value'],
                    $prop['extra'],
                    $prop['tzone']
                );
                $db->Execute($sql, $params);
                @file_put_contents($logfile, $db->ErrorMsg(), FILE_APPEND);
            }
        }
        
        if (!$db->CompleteTrans())
        {
            @file_put_contents($logfile, $db->ErrorMsg(), FILE_APPEND);
            return 1;
        }

        $db->Execute("DELETE FROM alienvault.host_properties WHERE value like 'unknown%'");
        @file_put_contents($logfile, $db->ErrorMsg(), FILE_APPEND);


        // HOST SOFTWARE CPE
        $cpes = array();

        $db->StartTrans();
        
        $rs = $db->Execute("SELECT DISTINCT cpe FROM host_software");
        while (!$rs->EOF) 
        {
            $cpes[] = $rs->fields['cpe'];
            $rs->MoveNext();
        }

        foreach ($cpes as $cpe) 
        {
            $params = array(
                Asset_host_software::get_software_name_by_cpe($db, $cpe),
                $cpe
            );
            $db->Execute("UPDATE host_software SET banner=? WHERE cpe=?", $params);
            @file_put_contents($logfile, $db->ErrorMsg(), FILE_APPEND);
        }
        
        if (!$db->CompleteTrans())
        {
            @file_put_contents($logfile, $db->ErrorMsg(), FILE_APPEND);
            return 1;
        }
        
        //
        // VULN_JOB_ASSET
        //
        $jobs = array();

        $db->StartTrans();
        
        $rs = $db->Execute("SELECT id,meth_TARGET FROM alienvault.vuln_job_schedule");
        while (!$rs->EOF) 
        {
            $jobs[] = array(
                        'id'      => $rs->fields['id'],
                        'targets' => explode("\n",$rs->fields['meth_TARGET'])
                    );
            $rs->MoveNext();
        }
        
        foreach ($jobs as $job) 
        {

            $db->Execute("DELETE FROM alienvault.vuln_job_assets WHERE job_id=? AND job_type=0", array($job['id']));
            @file_put_contents($logfile, $db->ErrorMsg(), FILE_APPEND);

            
            foreach ($job['targets'] as $target)
            {
                if (preg_match("/(.*)#(.*)/",$target,$matches)) 
                {
                    // ADD ASSET_ID
                    $sql    = "INSERT IGNORE INTO alienvault.vuln_job_assets (job_id, job_type, asset_id) VALUES (?, 0, UNHEX(?))";
                    $params = array (
                        $job['id'],
                        $matches[1]
                    );
                    $db->Execute($sql, $params);
                    @file_put_contents($logfile, $db->ErrorMsg(), FILE_APPEND);

                    if (preg_match("/\/\d+/",$matches[2]))
                    {
                        // NETWORK MEMBERS
                        $sql    = "INSERT IGNORE INTO alienvault.vuln_job_assets (job_id, job_type, asset_id) SELECT ?, 0, host_id FROM host_net_reference WHERE net_id=UNHEX(?)";
                        $params = array (
                            $job['id'],
                            $matches[1]
                        );
                        $db->Execute($sql, $params);
                        @file_put_contents($logfile, $db->ErrorMsg(), FILE_APPEND);
                    }
                }
            }
        }
        
        if (!$db->CompleteTrans())
        {
            @file_put_contents($logfile, $db->ErrorMsg(), FILE_APPEND);
            return 1;
        }

        $conn->close();
        
        return 0;
    }
}
?>
