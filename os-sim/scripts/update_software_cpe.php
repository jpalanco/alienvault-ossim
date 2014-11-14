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


ini_set('include_path', '/usr/share/ossim/include/');

error_reporting(0);
ini_set("display_errors", "0");
ini_set('memory_limit', '2048M');
set_time_limit(0);

require_once 'av_handlers.php';

function write_log($exp_type, $log_message = '')
{
    echo '['.gmdate('D M d h:i:s Y').'] ['.$exp_type.'] '.$log_message."\n";
}

// 

$db = new Ossim_db();

if(!@$db->test_connect())
{
	echo "[ERROR] Updating Software CPE: Unable to connect to DB";
	exit -1;
}

$conn = $db->connect();

$conn->Execute('DROP TABLE IF EXISTS `alienvault`.`software_cpe_aux`');
$query = "CREATE TABLE `alienvault`.`software_cpe_aux` (
			`cpe` VARCHAR( 255 ) NOT NULL,
			`name` VARCHAR( 255 ) NOT NULL,
			`version` VARCHAR( 255 ) NOT NULL,
			`line` VARCHAR( 255 ) NOT NULL,
			`vendor` VARCHAR( 255 ) NOT NULL,
		    `plugin` VARCHAR(255) NOT NULL,
			PRIMARY KEY (  `cpe`  ),
			INDEX `line` (`line` ASC),
			INDEX `search` (`vendor` ASC, `name` ASC, `version` ASC)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

$conn->Execute($query);

$file = "/usr/share/ossim-cd-tools/cpe/official-cpe-dictionary.xml";

if(file_exists($file))
{
    write_log('INFO',"Reading XML data from $file");

	$cpe_list = @simplexml_load_file($file);
	
	if (!$cpe_list)
	{
    	write_log('ERROR', 'Unable to open '.$file.' or invalid XML format');
    	$db->close();
    	die();
	}
		
	$data     = get_object_vars($cpe_list);

    write_log('INFO','Updating software_cpe_aux table');

	foreach($data['cpe-item'] as $item)
	{
		$line     = (string)$item->title;

		// Do not insert entries with utf8 characters
		//if (!preg_match('%(?:[\xC2-\xDF][\x80-\xBF]|\xE0[\xA0-\xBF][\x80-\xBF]|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}|\xED[\x80-\x9F][\x80-\xBF]|\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2})+%xs', $line))
		//{

    		$cpe      = (string) $item->attributes()->name;

    		$attrs    = explode(':', $cpe);
    		//$date     = $attrs[4];
    		$date     = ($attrs[4]) ? $attrs[4] : '';
    		$vendor   = ucwords($attrs[2]);

    		$title    = trim(str_ireplace($vendor,'',str_ireplace($date, '', $line)));

    		$query   = "REPLACE INTO  `alienvault`.`software_cpe_aux` (`cpe`, `name`, `version`, `line`, `vendor`, `plugin`) VALUES (?, ?, ?, ?, ?, '')";

    		$params   = array(
    					$cpe,
    					$title,
    					$date,
    					$line,
    					$vendor
    				);

    		$conn->Execute($query, $params);

		//}

	}

}

// Read from  dir and fill plugin field
$plugins_dir = '/etc/ossim/agent/plugins';
$files = array();
if (is_dir($plugins_dir))
{
    if ($handle = opendir($plugins_dir))
    {
        while (($file = readdir($handle)) !== FALSE)
        {
            if ($file != "." && $file != "..")
            {
                $files[] = $file;
            }
        }
        closedir($handle);
        
        sort($files);
        
        foreach ($files as $file)
        {        
            $plugin_name = str_replace('.cfg','',$file);
            $file        = $plugins_dir.'/'.$file;

            // Plugin ID
            $supported   = explode("\n",`awk '/Last/{p=0};p;/Author/{p=1}' '$file'`);
            array_pop($supported); // Removing latest \n
            
            $plugin_id = 0;
            if (count($supported))
            {
                foreach ($supported as $line)
                {
                    // matching plugin id # Plugin cisco-asa id:1636 version
                    if (preg_match("/#\s+Plugin .* id:(\d+) ver/",trim($line),$matches))
                    {
                        $plugin_id = $matches[1];
                    }
                }
            }
            
            // Supported CPEs
            $supported   = explode("\n",`awk '/Description/{p=0};p;/Accepted products/{p=1}' '$file'`);
            array_pop($supported); // Removing latest \n

            if (count($supported))
            {
                foreach ($supported as $line)
                {
                    // matching # honeynet - nepenthes 0.0.0
                    if (preg_match("/# (.*?) - (.*) (.*)/",trim($line),$matches) && $matches[3]!='-')
                    {
                        write_log('INFO', "Updating plugin=$plugin_name:$plugin_id for '".$matches[1]."' and '".$matches[2]."' and '".$matches[3]."'");

                        $query   = "UPDATE `alienvault`.`software_cpe_aux` SET plugin=? WHERE vendor LIKE ? AND (name LIKE ? OR cpe like ?) AND version like ?";
                		$params   = array(
                					"$plugin_name:$plugin_id",
                					$matches[1],
                					$matches[2],
                					"cpe:/_:".$matches[1].":".$matches[2]."%",
                					$matches[3]
                				);
                		$conn->Execute($query, $params);
                    }
                    elseif (preg_match("/# (.*?) - (.*) -/",trim($line),$matches))
                    // matching # cisco - 2000_wireless_lan_controller
                    {
                        // $matches[2] = str_replace(' -','',$matches[2]);
                        write_log('INFO', "Updating plugin=$plugin_name:$plugin_id for '".$matches[1]."' and '".$matches[2]."'");

                        $query   = "UPDATE `alienvault`.`software_cpe_aux` SET plugin=? WHERE vendor LIKE ? AND (name LIKE ? OR cpe like ?)";
                		$params   = array(
                					"$plugin_name:$plugin_id",
                					$matches[1],
                					$matches[2],
                					"cpe:/_:".$matches[1].":".$matches[2]."%"
                				);
                		
                		$rs = $conn->Execute($query, $params);
                    }
                }
            }
        }
    }
}

// Special Insert
//
// $conn->Execute("REPLACE INTO `software_cpe_aux` VALUES (
// 'cpe',
// 'modelo',
// 'version',
// 'nombre largo con vendor model y version',
// 'vendor',
// 'plugin_name:plugin_id'
// )");
//
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:cyberguard:sg565:1.0.0','SG565','1.0.0','CyberGuard SG565 1.0.0','CyberGuard','cyberguard:1575')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:bro:bro-nsm:-','Bro-NSM','-','Bro Bro-NSM -','Bro','bro-ids:1568')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:dionaea:dionaea:-','Dionaea','-','Dionaea Dionaea -','Dionaea','dionaea:1669')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:mcafee:antivirus_engine:-','Antivirus','-','Mcafee Antivirus -','Mcafee','mcafee:1571')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:broadweb:netkeeper:-','NetKeeper','-','BroadWeb NetKeeper -','BroadWeb','netkeeper-nids:1647')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:microsoft:ntsyslog:-','NTsyslog','-','MicroSoft NTSyslog -','Microsoft','ntsyslog:1517')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:optenet:mailsecure:-','Mailsecure','-','Optenet Mailsecure -','Optenet','optenet:1563')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:stonesoft:stonegate:-','Stonegate','-','StoneSoft StoneGate -','StoneSoft','stonegate:1526')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:stonesoft:ips:-','Stonegate IPS','-','StoneSoft IPS -','StoneSoft','stonegate_ips:1643')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:envault:airlock:-','Airlock','-','Envault Airlock -','Envault','airlock:1641')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:safenet:esafe-web:-','eSafe Web Security Gateway','-','SafeNet eSafe Web Security Gateway -','SafeNet','aladdin:1566')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:allot:netenforcer_ac500:-','Netenforcer AC-500','-','Allot Netenforcer AC-500 -','Allot','allot:1608')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:nortel:alteonos:0.0.1','AlteonOS','0.0.1','Nortel AlteonOS 0.0.1','Nortel','alteonos:1684')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:nortel:alteonos:0.0.1','AlteonOS','0.0.1','Nortel AlteonOS 0.0.1','Nortel','alteonos:1684')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:amun:amunhoney:0.1.9','AmunHoney','0.1.9','Amun AmunHoney 0.1.9','Amun','amun-honeypot:1662')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:arpalert:arpalert:2.0.11','ArpAlert','2.0.11','ArpAlert ArpAlert 2.0.11','ArpAlert','arpalert:1512')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:artemisa:artemisa:1.0.91','Artemisa','1.0.91','Artemisa Artemisa 1.0.91','Artemisa','artemisa:1668')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:arubanetworks:wireless_6:6.1.3.5','Wireless 6.x','6.1.3.5','Aruba Networks Wireless 6.x 6.1.3.5','Aruba Networks','aruba-6:1624')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:arubanetworks:s2500:1.0.0','S2500','1.0.0','Aruba Networks S2500 1.0.0','Aruba Networks','aruba:1623')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:xtera:ascenlink:1.0.0','AscenLink','1.0.0','Xtera AscenLink 1.0.0','Xtera','ascenlink:1660')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:bit9:security_platform:1.0.0','Security Platform','1.0.0','Bit9 Security Platform 1.0.0','Bit9','bit9:1630')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:cisco:meraki:-','Meraki','-','Cisco Meraki -','Cisco','cisco-meraki:1695')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:redhat:rgmanager:-','Resource Group Manager Daemon','-','Red Hat Resource Group Manager Daemon -','Red Hat','clurgmgr:1528')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:couriermta:courier_mail_server:0.0.1','Courier Mail Server','0.0.1','CourierMTA Courier Mail Server 0.0.1','CourierMTA','courier:1617')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:immunityinc:eljefe:1.0','El Jefe','1.0','Immunity El Jefe 1.0','Immunity','eljefe:1633')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:ibm:fidelis:-','Fidelis','-','IBM Fidelis -','IBM','fidelis:1592')");
#$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:alienvault:plugins:1.0.0','Plugins','1.0.0','AlienVault Plugins 1.0.0','AlienVault','forensics-db-1:1801')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:fortiguard:fortiguard:1.0','Fortiguard','1.0','Fortiguard Fortiguard 1.0','Fortiguard','fortiguard:1621')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:fortinet:fortimail:-','Fortimail','-','Fortinet Fortimail -','Fortinet','fortimail:1692')");
#$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:alienvault:plugins:1.0.0','Plugins','1.0.0','AlienVault Plugins 1.0.0','AlienVault','glastopng:1667')");
#$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:alienvault:plugins:1.0.0','Plugins','1.0.0','AlienVault Plugins 1.0.0','AlienVault','honeyd:1570')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:freebsd:ipfw:1.0.0','IPFW','1.0.0','FreeBSD IPFW 1.0.0','FreeBSD','ipfw:1529')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:cisco:ironport:1.0.0','Ironport','1.0.0','Cisco Ironport 1.0.0','Cisco','ironport:1591')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:kismet:kismet:2.9.1','Kismet','2.9.1','Kismet Kismet 2.9.1','Kismet','kismet:1596')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:linux:dhcp:1.0.0','DHCP','1.0.0','Linux DHCP 1.0.0','Linux','linuxdhcp:1607')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:m0n0:m0n0wall:1.0.0','m0n0wall','1.0.0','m0n0 m0n0wall 1.0.0','m0n0','m0n0wall:1559')");
#$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:alienvault:monitor-malwaredomainlist:1.0.0','Monitor Malware Domain List','1.0.0','AlienVault Monitor Malware Domain List 1.0.0','AlienVault','malwaredomainlist-monitor:2011')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:mcafee:antispam:-','Antispam','-','Mcafee Antispam -','Mcafee','mcafee-antispam:1618')");
#$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:alienvault:monit:5.5','Monit','5.5','AlienVault Monit 5.5','AlienVault','monit:1687')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:motion:motion:-','Motion','-','Motion Motion -','Motion','motion:1613')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:motorola:rfs-5001:-','RFS-5001','-','Motorola RFS-5001 -','Motorola','motorola-firewall:1633')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:mwcollect:mwcollect:-','Mwcollect','-','Mwcollect Mwcollect -','Mwcollect','mwcollect:1521')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:honeynet:nepenthes:-','Nepenthes','-','Honeynet Nepenthes -','Honeynet','nepenthes:1564')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:broadweb:netkeeper_firewall:-','NetKeeper Firewall','-','BroadWeb NetKeeper Firewall -','BroadWeb','netkeeper-fw:1646')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:netscreen:firewall:-','Firewall','-','Netscreen Firewall -','Netscreen','netscreen-firewall:1522')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:linux:nfs:-','NFS','-','Linux NFS -','Linux','nfs:1631')");
#$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:alienvault:monitor-nmap:0.0.1','monitor-nmap','0.0.1','AlienVault monitor-Nmap 0.0.1','AlienVault','nmap-monitor:2008')");
#$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:alienvault:monitor-ntop:0.0.1','monitor-ntop','0.0.1','AlienVault monitor-Ntop 0.0.1','AlienVault','ntop-monitor:2005')");
#$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:alienvault:monitor-ocs:0.0.1','monitor-ocs','0.0.1','AlienVault monitor-Ocs 0.0.1','AlienVault','ocs-monitor:2013')");
#$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:alienvault:monitor-opennms:0.0.1','monitor-opennms','0.0.1','AlienVault monitor-Opennms 0.0.1','AlienVault','opennms-monitor:2004')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:osiris:osiris:-','Osiris','-','Osiris Osiris -','Osiris','osiris:4001')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:ossec:ossec:2.6','OSSEC','2.6','OSSEC OSSEC 2.6','OSSEC','ossec:7007')");
#$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:alienvault:idm-ossec:0.0.1','IDM-OSSEC','0.0.1','AlienVault IDM-OSSEC 0.0.1','AlienVault','ossec-idm:50003')");
#$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:alienvault:ossim-agent:4.0.0','Ossim-Agent','4.0.0','AlienVault Ossim-Agent 4.0.0','AlienVault','ossim-agent:6001')");
#$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:alienvault:ossim-monitor:0.0.1','Ossim-Monitor','0.0.1','AlienVault Ossim-Monitor 0.0.1','AlienVault','ossim-monitor:2001')");
#$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:alienvault:idm-p0f:0.0.1','IDM-p0f','0.0.1','AlienVault IDM-p0f 0.0.1','AlienVault','p0f:1511')");
#$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:alienvault:idm-pads:0.0.1','IDM-pads','0.0.1','AlienVault IDM-pads 0.0.1','AlienVault','pads:1516')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:paloaltonetworks:pa-5000:-','PA-5000','-','PaloAltoNetworks PA-5000 -','PaloAltoNetworks','paloalto:1615')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:pandasecurity:adminsecure:-','AdminSecure','-','PandaSecurity AdminSecure -','PandaSecurity','panda-as:1578')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:pandasecurity:securityforenterprise:-','SecurityForEnterprise','-','PandaSecurity SecurityForEnterprise -','PandaSecurity','panda-se:1605')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:openbsd:pf:-','PacketFilter','-','OpenBSD PacketFilter -','OpenBSD','pf:1560')");
#$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:alienvault:monitor-ping:0.0.1','Monitor-Ping','0.0.1','AlienVault Monitor-Ping 0.0.1','AlienVault','ping-monitor:2009')");
#$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:alienvault:plugins:1.0.0','Plugins','1.0.0','AlienVault Plugins 1.0.0','AlienVault','post_correlation:12001')");
#$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:alienvault:idm-prads:0.0.1','IDM-prads','0.0.1','AlienVault IDM-prads 0.0.1','AlienVault','prads:1683')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:proxim:ap-700:-','AP-700','-','Proxim AP-700 -','Proxim','proxim-orinoco:1682')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:osc:radiator:-','Radiator','-','OSC Radiator -','OSC','osc-radiator:1589')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:radware:defensepro:-','DefensePro','-','Radware DefensePro -','Radware','radware-defensepro:1645')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:ibm:raslog:-','RASlog','-','IBM RASlog -','IBM','ibm-raslog:1695')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:rrdtool:rrdtool:-','RRDtool','-','RRDtool RRDtool -','RRDtool','rrd:1507')");
#$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:alienvault:monitor-session:0.0.1','monitor-session','0.0.1','AlienVault monitor-session 0.0.1','AlienVault','session-monitor:2005')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:shrubbery:tacacs:-','TACACS+','-','Shrubbery TACACS+ -','Shrubbery','shrubbery-tacacs:1676')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:intersectalliance:snare:-','Snare','-','InterSect Alliance Snare -','InterSect Alliance','snare:1518')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:intersectalliance:snare:-','Snare','-','InterSect Alliance Snare -','InterSect Alliance','snare-mssql:1654')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:intersectalliance:snare:-','Snare','-','InterSect Alliance Snare -','InterSect Alliance','snare-msssis:1655')");
#$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:alienvault:idm-snare:0.0.1','IDM-snare','0.0.1','AlienVault IDM-snare 0.0.1','AlienVault','snare-idm:50003')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:squidguard:squidguard:-','SquidGuard','-','SquidGuard SquidGuard -','SquidGuard','squidguard:1587')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:ibm:storwize_v7000:-','Storwize V7000','-','IBM Storwize V7000 -','IBM','storewize-V7000:33002')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:syslog:syslog:-','Syslog','-','Syslog Syslog -','Syslog','syslog:4007')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:shrubbery:tacacs:-','TACACS+','-','Shrubbery TACACS+ -','Shrubbery','tacacs-plus:1665')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:tarantella:tarantella:-','Tarantella','-','Tarantella Tarantella -','Tarantella','tarantella:1552')");
#$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:alienvault:monitor-tcptrack:0.0.1','monitor-tcptrack','0.0.1','AlienVault monitor-Tcptrack 0.0.1','AlienVault','tcptrack-monitor:2006')");
#$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:alienvault:plugin-usbudev:-','plugin-usbudev','-','AlienVault plugin-usbudev -','AlienVault','usbudev:1640')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:vmware:vcenter_converter:-','Vcenter Converter','-','Vmware Vcenter Converter -','Vmware','vmware-vcenter-sql:90007')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:ibmmainframe:visionplus:-','Vision Plus','-','IBM Mainframe Vision Plus -','IBM Mainframe','vplus:1650')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:vsftpd:vsftpd:-','Vsftpd','-','Vsftpd Vsftpd -','Vsftpd','vsftpd:1576')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:brocade:vyatta:-','Vyatta','-','Brocade Vyatta -','Brocade','vyatta:1610')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:watchguard:xtm:11.6.1','XTM','11.6.1','WatchGuard XTM 11.6.1','WatchGuard','watchguard:1691')");
#$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:alienvault:monitor-whois:0.0.1','monitor-whois','0.0.1','AlienVault monitor-Whois 0.0.1','AlienVault','whois-monitor:2010')");
#$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:alienvault:monitor-wmi:0.0.1','monitor-wmi','0.0.1','AlienVault monitor-wmi 0.0.1','AlienVault','wmi-monitor:2012')");
#$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:alienvault:plugin-wmi:-','plugin-wmi','-','AlienVault plugin-wmi -','AlienVault','wmi-application-logger:1518')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:wu-ftp:wu-ftp:-','Wu-ftp','-','Wu-ftp Wu-ftp -','Wu-ftp','wuftp:1632')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:openinfosecfoundation:suricata:1.4.6','Suricata','1.4.6','Open Information Security Foundation (OISF) Suricata 1.4.6','Open Information Security Foundation (OISF)','suricata-http:8001')");
#$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:alienvault:monitor-nessus:0.0.1','monitor-nessus','0.0.1','AlienVault monitor-Nessus 0.0.1','AlienVault','nessus-monitor:3001')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:tenable:nessus:-','Nessus','-','Tenable Nessus -','Tenable','tenable-nessus:90003')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:rsa:securid:6.1','SecurID','6.1','RSA SecurID 6.1','RSA','rsa6.1secureid:1593')");
$conn->Execute("REPLACE INTO `software_cpe_aux` VALUES ('cpe:/a:microsoft:server:2003','Server','2003','MicroSoft Server 2003','MicroSoft','W2003DNS:1689')");


$query   = 'SELECT count(*) AS total FROM `alienvault`.`software_cpe_aux`';
$total   = $conn->GetOne($query);

if ($total > 50000)
{
    $conn->Execute('DROP TABLE IF EXISTS `alienvault`.`software_cpe`');

    // Sanity check
    if ( is_writable('/var/lib/mysql/alienvault/software_cpe.MYD') )
    {
        @unlink('/var/lib/mysql/alienvault/software_cpe.MYD');
    }
    if ( is_writable('/var/lib/mysql/alienvault/software_cpe.MYI') )
    {
        @unlink('/var/lib/mysql/alienvault/software_cpe.MYI');
    }
    if ( is_writable('/var/lib/mysql/alienvault/software_cpe.frm') )
    {
        @unlink('/var/lib/mysql/alienvault/software_cpe.frm');
    }

    $conn->Execute('RENAME TABLE `alienvault`.`software_cpe_aux` TO `alienvault`.`software_cpe`');
    
    $query   = 'SELECT count(*) AS total FROM `alienvault`.`software_cpe`';
    $total   = $conn->GetOne($query);
    
    $query   = 'SELECT count(*) AS total FROM `alienvault`.`software_cpe` WHERE plugin!=""';
    $with    = $conn->GetOne($query);
    
    write_log('INFO', "$total CPEs en database. $with matching with a plugin. Update successful");
}
else
{
    write_log('ERROR', 'An error has occurred. Update incomplete');
}

$db->close();
?>
