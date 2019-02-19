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

ini_set('max_execution_time','300');

require_once 'av_init.php';

Session::logcheck('environment-menu', 'TrafficCapture');

$scan_name  = GET('scan_name');
$sensor_ip  = GET('sensor_ip');

ossim_valid($scan_name, OSS_SCORE, OSS_NULLABLE, OSS_ALPHA, OSS_DOT, 'illegal:' . _('Scan name'));
ossim_valid($sensor_ip, OSS_IP_ADDR, 'illegal:' . _('Sensor ip'));

if (ossim_error()) 
{
    die(ossim_error());
}

$db     = new ossim_db();
$dbconn = $db->connect();

$scan_info = explode('_', $scan_name);
$users = Session::get_users_to_assign($dbconn);

$my_users = array();
foreach ($users as $k => $v)
{
    $my_users[$v->get_login()] = 1;
}

if ($my_users[$scan_info[1]] != 1 && !Session::am_i_admin())
{
    return;
}

$scan = new Traffic_capture();

$pcapfile = $scan->get_pcap_file($scan_name, $sensor_ip);

if(file_exists($pcapfile)) 
{
    if(filesize($pcapfile)/(1024*1024) >= 2) 
    {
        echo "<div style='margin: auto;width: 100%;text-align: center;'>";
        echo _("The file is too big, you can only")." <a href='download.php?scan_name=$scan_name&sensor_name=$sensor_name'>"._("download it")."</a>";
        echo "</div>"; 
        exit();
    }
    else if(filesize($pcapfile) == 0) 
    {
        echo _("Empty file"); 
        exit(); 
    }
}
else if(preg_match("/^E/i",$pcapfile)) 
{
    echo "<div style='margin: auto;width: 100%;text-align: center;'>".$pcapfile."</div>"; return;
}

$pdmlfile = str_replace("pcap","pdml",$pcapfile);


// TSAHRK: show packet in web page
$cmd    = "tshark -V -r ? -T pdml > ?";
$params = array($pcapfile, $pdmlfile);
//echo $cmd;
Util::execute_command($cmd, $params);

$output1 = Util::execute_command("egrep -i '<pdml .*>' ?", array($pdmlfile), 'string');
$output2 = Util::execute_command("grep -i '</pdml>' ?"   , array($pdmlfile), 'string');

if(trim($output1) != '' && trim($output2) == '') 
{
    Util::execute_command("echo '</pdml>' >> ?", array($pdmlfile));
}

?>
<ul style="display:none"><li id="key1" data="isFolder:true, icon:'../../images/any.png'">
<?php
if (file_exists($pdmlfile) && filesize($pdmlfile) > 0)
{
    $i = 1;
    if ($xml = @simplexml_load_file($pdmlfile))
    {
        if(is_null($xml->packet->proto))
        {
            echo _('No data available');
            
            exit();
        }
        
	    foreach($xml->packet->proto as $key => $xml_entry) 
	    {
	        $atr_tit = $xml_entry->attributes();
	        
	        if ($atr_tit['name'] != 'eth')
	        {
	            if ($atr_tit['name'] == 'geninfo')
	            {
	                $img = 'information.png';
	            }
	            elseif ($atr_tit['name'] == 'tcp' || $atr_tit['name'] == 'udp')
	            {
	                $img = 'proto.png';
	            }
	            elseif ($atr_tit['name'] == 'ip')
	            {
	                $img = 'flow_chart.png';
	            }
	            elseif ($atr_tit['name'] == 'frame')
	            {
	                $img = 'wrench.png';
	            }
	            elseif ($atr_tit['name'] == 'eth')
	            {
	                $img = 'eth.png';
	            }
	            else
	            {
	                $img = 'host_os.png';
	            }
	            
	            echo "<li id=\"key1.$i\"  data=\"isFolder:true, icon:'../../images/$img'\"><b>" . strtoupper($atr_tit['name']) . "</b>\n<ul>\n";
	            
	            $j = 1;
	            
	            foreach($xml_entry as $key2 => $xml_entry2)
	            {
	                $k = 1;
	                $atr = $xml_entry2->attributes();
	                
	                if (!preg_match("/Checksum/i",$atr['showname']))
	                {
		                $showname = ($atr_tit['name'] == "geninfo") ? $atr['showname'] . ": <b>" . $atr['show'] . "</b>" : preg_replace("/(.*?):(.*)/", "\\1: <b>\\2</b>", $atr['showname']);
		                
		                echo "<li id=\"key1.$i.$j\" data=\"isFolder:true, icon:'../../images/host.png'\">" . $showname . "\n";
		                echo "<ul>";
		               
		                foreach($atr as $key3 => $value)
		                {
		                    if ($key3 == "showname")
		                    {
		                        continue;
		                    }
		                    
		                    $value = Util::htmlentities($value);
		                    
		                    echo "<li id=\"key1.$i.$j.$k\" data=\"isFolder:false, icon:'../../images/host.png'\">" . $key3 . ": <b>" . $value . "</b>\n";
		                    
		                    $k++;
		                }
		               
		                echo "</ul>\n";
	                	
	                	$j++;
		            }
	            }
	            
	            echo "</ul>\n";
	            
	            $i++;
	        }
	    }
    }
    else
    {
        echo "<li id=\"key1\"  data=\"isFolder:false, icon:'../../images/information.png'\"><b>" . _('Error in pcap format!') . "</b>\n";
    }
    echo "</ul>";
}

// Clean temp files
if (file_exists($pcapfile))
{
    unlink($pcapfile);
}

if (file_exists($pdmlfile))
{
    unlink($pdmlfile);
} 
?>
</ul>
