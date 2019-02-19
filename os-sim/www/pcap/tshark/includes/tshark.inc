<?php
/**
* Tshark.inc
*
* File Tshark.inc is used to:
*   - Task 1 (TO DO)
*   - Task 2 (TO DO)
*   - Task 3 (TO DO)
*
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
* @package    ossim-framework\Traffic_Capture
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


/*
* public function list:
* - Tshark($file)       --> creator
* - get_psml()          --> get data xml_psml
* - get_pdml()          --> get data xml_pdml
* - get_text_bin()      --> get data text_bin
* - print_psml_head()   --> print data head of psml in table 
* - print_psml_body()   --> print data body of psml in table 
* - print_pdml($id)     --> print data packet of pdml in tree with jquery
* - print_text_bin($id) --> print data packet in binary and text
* - htmlentities($text) --> transform html to htmlentities
*/

class Tshark {
    //datas
    public $file_pcap;
    public $filter;
    public $xml_psml ;
    public $xml_pdml ;
    public $text_bin ;
    public $max_len ;
    public $description;
    public $error = "";
    public $critical_error = FALSE;
    
    //methods
    //creator
    public function __construct($file, $filter = '')
    {
        $this->put_file_pcap($file);
        $this->put_filter($filter);
        $cmd    = "capinfos ? 2>&1 1> /dev/null";
        $output = Util::execute_command($cmd, array($file), 'string');
        
        if (!filesize($file)){
            $this->put_error(_("This traffic capture is empty"));
            $this->critical_error = TRUE;
        }elseif (preg_match("/The file isn't a capture file in a known format/",$output)){
            $this->put_error(_("The file isn't a capture file in a known format")." (".$file.")");
            $this->critical_error = TRUE;
        }elseif(preg_match("/capinfos: Can't open .*: Permission denied/",$output,$match)){
            $this->put_error($match[0]);
            $this->critical_error = TRUE;
        }else{
            if(preg_match("/An error occurred after reading ([0-9]*) packets/",$output,$match)){
                //$this->put_error($match[0].". Only show the ".$match[1]." firsts packets");
                $packeterror = $match[1];
                $cmd         = "editcap -r ? ? ?";
                $params      = array($file, $file."_2", "1-".$packeterror);
                $output      = Util::execute_command($cmd, $params, 'string');
                $file        = $file."_2";
                $this->put_file_pcap($file);
            }
            
            $cmd    = "capinfos -aecsu ?";
            $params = array($file);
            $output = Util::execute_command($cmd, $params, 'string');
            $this->put_max_len($output);
            
            $cmd    = "tshark -r ? -R ? -T psml -n -2";
            $params = array($file, $filter); 
            $output = Util::execute_command($cmd, $params, 'string');

            if (!preg_match('<structure>',$output) && !empty($filter)){
                $cmd    = "tshark -r ? -T psml -n -2";
                $params = array($file); 
                $output = Util::execute_command($cmd, $params, 'string');
                $this->put_filter('');
                $this->put_error(_("no data with this filter")."(".$filter.")");
            }
            
            $this->xml_psml = $output;
        }
    }
    //gets
    
    public function get_file_pcap()
    {
        return $this->file_pcap;
    }
    public function get_filter()
    {
        return $this->filter;
    }
    public function get_psml()
    {
        return $this->xml_psml;
    }
    public function get_pdml()
    {
        return $this->xml_pdml;
    }
    public function get_text_bin()
    {
        return $this->text_bin;
    }
    public function get_max_len()
    {
        return $this->max_len;
    }
    public function get_error()
    {
        return $this->error;
    }
    public function put_file_pcap($file)
    {
        $this->file_pcap = $file;
    }
    public function put_filter($filter)
    {
        $this->filter = $filter;
    }
    public function put_max_len($output)
    {
        if(preg_match("/Start time:\s*([a-z]*\s[a-z]*\s*[0-9]*\s*[0-9]*\:[0-9]*\:[0-9]*\s[0-9]*)/i" , $output , $matches ))
            $this->description .= "<b>"._("Start time").":</b>".$matches[1]." ";
        if(preg_match("/End time:\s*([a-z]*\s[a-z]*\s*[0-9]*\s*[0-9]*\:[0-9]*\:[0-9]*\s[0-9]*)/i" , $output , $matches ))
            $this->description .= "<b>"._("End time").":</b>".$matches[1]." ";
        $this->description .= "<br />";
        if(preg_match("/Capture duration:\s*([0-9]*)\sseconds/" , $output , $matches )){
            $this->max_len = (int)$matches[1];
            $this->description .= "<b>"._("Capture duration").":</b>".$matches[1]." "._("seconds")." ";
        }
        if(preg_match("/Number of packets:\s*([0-9]*)/" , $output , $matches ))
            $this->description .= "<b>"._("Number of packets").":</b>".$matches[1]." ";
        if(preg_match("/File size:\s*([0-9]*)\sbytes/" , $output , $matches ))
            $this->description .= "<b>"._("File size").":</b>".$matches[1]." "._("bytes")." ";
    }
    public function put_error($error)
    {
        $this->error = $error;
    }
    
    
    //print datas
    public function print_psml_head()
    {
        echo("
        <thead>
            <tr>
                <th class='psml' style='width: 50px; border-left:1px solid #AAAAAA;' >"._("No.")."</th>
                <th class='psml' style='width: 100px' >"._("Time")."</th>
                <th class='psml' style='width: 220px' >"._("Source")."</th>
                <th class='psml' style='width: 220px' >"._("Destination")."</th>
                <th class='psml' style='width: 100px' >"._("Protocol")."</th>
                <th class='psml' style='width: 60px' >"._("Length")."</th>
                <th class='psml'>"._("Info.")."</th>
            <tr>
        <thead>
        
        ");
        
    }
    
    public function print_psml_body()
    {
        $psml = simplexml_load_string($this->get_psml());
        
        echo "<tbody>";
        
        $i = 0;
        
        $style[0] = "width: 72px;";
        $style[1] = "width: 122px;";
        $style[2] = "width: 242px;";
        $style[3] = "width: 242px;";
        $style[4] = "width: 122px;";
        $style[5] = "width: 82px;";
        $style[6] = "";
        
        foreach ($psml->packet as $packet)
        {
            $i++;
            
            if ($i == 1)
            {
                $class = "row1sel";
            }
            elseif ($i%2)
            {    
                $class = "row1";
            }
            else
            {
                $class = "row2";
            }
                
            echo "<tr class='".$class."' id='".$this->htmlentities($packet->section)."'>";
           
            $j = 0;
            foreach ($packet as $section)
            {
                echo "<td style='text-align:left; ".$style[$j]." '>".$this->htmlentities($section)."</td>";
               
                $j++;
            }
            
            echo "</tr>";    
        }
        
        echo "</tbody>";
        
        return $packet->section[1];        
    }
    
    
    public function print_pdml($id)
    {
        $cmd      = "tshark -r ? -T pdml -n -Y ?"; 
        $params   = array($this->file_pcap, "frame.number==".$id);
        $xml_pdml = Util::execute_command($cmd, $params, 'string');
        
        $pdml   = simplexml_load_string($xml_pdml);
        $pdml   = $pdml->packet;

        echo  "<ul class='packet' id='packet' style='padding-left: 10px;' >";
        
        foreach($pdml->proto as $xml_entry) 
        {
	        $atr_tit = $xml_entry->attributes();
	        
            if ($atr_tit['name']=="geninfo")
            {    
                continue;
            }
            
            echo "<li class='section' id='section'>" . $this->htmlentities(strtoupper($atr_tit['showname'])) . "\n<ul style='background-color: #FFFFFF;'>\n";
            
            foreach($xml_entry as $xml_entry2) 
            {
                $atr = $xml_entry2->attributes();
                
                if (!preg_match("/Checksum/i",$atr['showname'])) 
                {                    
                    $showname = $atr['showname'] . ": " . $atr['show'] . "";
                    
                    echo "<li>" . $this->htmlentities($showname) . "\n";
                    
                    if (isset($xml_entry2->field))
                    {
                        echo "<ul>\n";
                        
                        foreach($xml_entry2->field as $xml_entry3) 
                        {
                            $atr = $xml_entry3->attributes();
                            
                            if (!preg_match("/Checksum/i",$atr['showname'])) 
                            {                                
                                $showname = $atr['showname'] . ": " . $atr['show'] . "";
                                echo "<li>" . $this->htmlentities($showname) . "\n";
                            }
                        }
                        
                        echo "</ul>\n";
                    }
                }
            }
            
            echo "</ul>\n";
	    }
	    
	    echo("</ul>");
    }
    
    public function print_text_bin($id)
    {
        $cmd    = "tshark -r ? -T text -x -n -Y ?";
        $params = array($this->file_pcap, "frame.number==".$id); 
        $salida = "";
        $lines  = explode("\n",Util::execute_command($cmd, $params, 'string'));

        foreach ($lines as $line){
            if ($line==="")
                continue;
            $salida .= $line . "\n";
        }
        echo("<pre style='font-weight: normal !important; background-color:transparent;border: 0px;margin: 15px;font-size: 12px;font-family: Courier New;'>".$this->htmlentities($salida)."</pre>");
    }
    
    public function get_data_for_graphs($type)
    {
        switch ($type){
	    case "All":
                $interval = round($this->get_max_len() / 30, 6);
                $filter   = $this->get_filter() == "" ? "" : ",".$this->get_filter();
                $cmd      = "tshark -r ? -q -z ?";
                $params   = array($this->get_file_pcap(), "io,stat,".$interval);
                $lines    = explode("\n",Util::execute_command($cmd, $params, 'string'));
		$values   = array();
		$i        = 0;
		$max      = 0;
		foreach ($lines as $line){
			if ($line==="") continue;
			if (preg_match("/\|\s*([0-9\.]*)\s*<>\s*([0-9\.]*)\s*\|\s*([0-9]*)\s*\|\s*([0-9]*)\s*\|/",$line,$matches)){
				foreach($matches as $key => $match){
					if ($key == 0) continue;
					$values[$i][$key-1] = $match;
					if (($key==4) && ($max <(int)$match)) $max = (int)$match ;
				}
				$i++;
			}
		}

		foreach($values as $key => $value){
			if ($max==0)
				$values[$key][6]=0;
			else
				$values[$key][6]=round((int)$value[3]*200/$max);
		}

                $nvalues = 'nvalues = [[';
                foreach($values as $value){
                    $nvalues.=$value[6].',';
                }
                $nvalues = preg_replace("/,$/","",$nvalues);
                $nvalues .= ']];';

                $realdata = 'realdata = [[';
                foreach($values as $value){
                    $realdata.=$value[3].',';
                }
                $realdata = preg_replace("/,$/","",$realdata);
                $realdata .= ']];';

                $labelx = 'labelx = ["';
                foreach($values as $value){
                    $labelx.=$value[0].'","';
                }
                $labelx = preg_replace("/,$/","",$labelx);
                $labelx .= '"];';

                echo('<script type="text/javascript">'."\n");
                echo($nvalues."\n");
                echo($labelx."\n");
                echo($realdata."\n");
                echo('</script>');
                
	    break;
            
            case "AllFilter":
                $interval = round($this->get_max_len() / 30, 6);
                $filter = $this->get_filter() == "" ? "" : ",".$this->get_filter();
                $cmd    = "tshark -r ? -q -z ?";
                $params = array($this->get_file_pcap(), "io,stat,".$interval.$filter);
		$lines  = explode("\n", Util::execute_command($cmd, $params, 'string'));
		$values = array();
		$i=0;
		$max=0;
		foreach ($lines as $line){
			if ($line==="")
				continue;
			if (preg_match("/\|\s*([0-9\.]*)\s*<>\s*([0-9\.]*)\s*\|\s*([0-9]*)\s*\|\s*([0-9]*)\s*\|\s*([0-9]*)\s*\|/",$line,$matches)){
				foreach($matches as $key => $match){
							if ($key == 0) continue;
							$values[$i][$key-1] = $match;
							if (($key==4 || $key==6) && ($max <(int)$match)) $max = (int)$match ;
						}
						$i++;
					}
		}

		foreach($values as $key => $value){
			$values[$key][6] = ($max != 0) ? round((int)$value[3]*200/$max) : 0;
			$values[$key][7] = ($max != 0) ? round((int)$value[5]*200/$max) : 0;
		}

                $nvalues = 'nvaluesfilter = [[';
                foreach($values as $value){
                    $nvalues.=$value[6].',';
                }
                $nvalues = preg_replace("/,$/","],[",$nvalues);
                foreach($values as $value){
                    $nvalues.=$value[7].',';
                }
                $nvalues = preg_replace("/,$/","",$nvalues);
                $nvalues .= ']];';

                $realdata = 'realdatafilter = [[';
                foreach($values as $value){
                    $realdata.=$value[3].',';
                }
                $realdata = preg_replace("/,$/","],[",$realdata);
                foreach($values as $value){
                    $realdata.=$value[5].',';
                }
                $realdata = preg_replace("/,$/","",$realdata);
                $realdata .= ']];';

                $labelx = 'labelxfilter = ["';
                foreach($values as $value){
                    $labelx.=$value[0].'","';
                }
                $labelx = preg_replace("/,$/","",$labelx);
                $labelx .= '"];';

                echo('<script type="text/javascript">'."\n");
                echo($nvalues."\n");
                echo($labelx."\n");
                echo($realdata."\n");
                echo('</script>'."\n");
                
	    break;
            
            case "Protocols":
                $interval = round($this->get_max_len() / 30, 6);
                $filter   = $this->get_filter() == "" ? "" : ",".$this->get_filter() ;
                $cmd      = "tshark -r ? -q -z ?";
                $params   = array($this->get_file_pcap(), "io,stat,".$interval.",tcp,udp,not udp && not tcp");
		$lines    = explode("\n", Util::execute_command($cmd, $params, 'string'));
		$values   = array();
		$i        = 0;
		$max      = 0;
		foreach ($lines as $line){
			if ($line==="")
				continue;
			if (preg_match("/\|\s*([0-9\.]*)\s*<>\s*([0-9\.]*)\s*\|\s*([0-9]*)\s*\|\s*([0-9]*)\s*\|\s*([0-9]*)\s*\|\s*([0-9]*)\s*\|\s*([0-9]*)\s*\|\s*([0-9]*)\s*\|/",$line,$matches)){
				foreach($matches as $key => $match){
					if ($key == 0) continue;
					$values[$i][$key-1] = $match;
					if (($key==4 || $key==6 || $key==8) && ($max <(int)$match)) $max = (int)$match ;
				}
				$i++;
			}
		}

		foreach($values as $key => $value){
			$values[$key][9]  = ($max != 0) ? round((int)$value[3]*200/$max) : 0 ;
			$values[$key][10] = ($max != 0) ? round((int)$value[5]*200/$max) : 0 ;
			$values[$key][11] = ($max != 0) ? round((int)$value[7]*200/$max) : 0 ;
		}

                $nvalues = 'nvaluesproto = [[';
                foreach($values as $value){
                    $nvalues.=$value[9].',';
                }
                $nvalues = preg_replace("/,$/","],[",$nvalues);
                foreach($values as $value){
                    $nvalues.=$value[10].',';
                }
                $nvalues = preg_replace("/,$/","],[",$nvalues);
                foreach($values as $value){
                    $nvalues.=$value[11].',';
                }
                $nvalues = preg_replace("/,$/","",$nvalues);
                $nvalues .= ']];';

                $realdata = 'realdataproto = [[';
                foreach($values as $value){
                    $realdata.=$value[3].',';
                }
                $realdata = preg_replace("/,$/","],[",$realdata);
                foreach($values as $value){
                    $realdata.=$value[5].',';
                }
                $realdata = preg_replace("/,$/","],[",$realdata);
                foreach($values as $value){
                    $realdata.=$value[7].',';
                }
                $realdata = preg_replace("/,$/","",$realdata);
                $realdata .= ']];';

                $labelx = 'labelxproto = ["';
                foreach($values as $value){
                    $labelx.=$value[0].'","';
                }
                $labelx = preg_replace("/,$/","",$labelx);
                $labelx .= '"];';

                echo('<script type="text/javascript">'."\n");
                echo($nvalues."\n");
                echo($labelx."\n");
                echo($realdata."\n");
                echo('</script>'."\n");
                
                
            break;
            case "ProtocolsFilter":
                $interval = round($this->get_max_len() / 30, 6);
                $filter = $this->get_filter() ;
                $cmd    = "tshark -r ? -q -z ?";
                $params = array($this->get_file_pcap(), "io,stat,".$interval.",tcp && ".$filter.",udp && ".$filter.",not udp && not tcp && ".$filter);
				$lines  = explode("\n", Util::execute_command($cmd, $params, 'string'));
				$values = array();
				$i=0;
				$max=0;
				foreach ($lines as $line){
					if ($line==="")
						continue;
					if (preg_match("/\|\s*([0-9\.]*)\s*<>\s*([0-9\.]*)\s*\|\s*([0-9]*)\s*\|\s*([0-9]*)\s*\|\s*([0-9]*)\s*\|\s*([0-9]*)\s*\|\s*([0-9]*)\s*\|\s*([0-9]*)\s*\|\s*([0-9]*)\s*\|\s*([0-9]*)\s*\|/",$line,$matches)){
						foreach($matches as $key => $match){
							if ($key == 0) continue;
							$values[$i][$key-1] = $match;
							if (($key==4 || $key==6 || $key==8) && ($max <(int)$match)) $max = (int)$match ;
						}
						$i++;
					}
				}

				foreach($values as $key => $value){
					$values[$key][8]  = ($max != 0) ? round((int)$value[3]*200/$max) : 0 ;
					$values[$key][9]  = ($max != 0) ? round((int)$value[5]*200/$max) : 0 ; 
			                $values[$key][10] = ($max != 0) ? round((int)$value[7]*200/$max) : 0 ;
				}

                $nvalues = 'nvaluesprotofilter = [[';
                foreach($values as $value){
                    $nvalues.=$value[8].',';
                }
                $nvalues = preg_replace("/,$/","],[",$nvalues);
                foreach($values as $value){
                    $nvalues.=$value[9].',';
                }
                $nvalues = preg_replace("/,$/","],[",$nvalues);
                foreach($values as $value){
                    $nvalues.=$value[10].',';
                }
                $nvalues = preg_replace("/,$/","",$nvalues);
                $nvalues .= ']];';

                $realdata = 'realdataprotofilter = [[';
                foreach($values as $value){
                    $realdata.=$value[3].',';
                }
                $realdata = preg_replace("/,$/","],[",$realdata);
                foreach($values as $value){
                    $realdata.=$value[5].',';
                }
                $realdata = preg_replace("/,$/","],[",$realdata);
                foreach($values as $value){
                    $realdata.=$value[7].',';
                }
                $realdata = preg_replace("/,$/","",$realdata);
                $realdata .= ']];';

                $labelx = 'labelxprotofilter = ["';
                foreach($values as $value){
                    $labelx.=$value[0].'","';
                }
                $labelx = preg_replace("/,$/","",$labelx);
                $labelx .= '"];';

                echo('<script type="text/javascript">'."\n");
                echo($nvalues."\n");
                echo($labelx."\n");
                echo($realdata."\n");
                echo('</script>'."\n");
                
                
            break;
            case "sparkline":
                $interval = round($this->get_max_len() / 30, 6);
                $filter   = $this->get_filter() == "" ? "" : ",".$this->get_filter();
                $cmd      = "tshark -r ? -q -z ?";
                $params   = array($this->get_file_pcap(), "io,stat,".$interval);
                $lines    = explode("\n", Util::execute_command($cmd, $params, 'string'));
        		$values   = array();
        		$i        = 0;
        		$max      = 0;
        		foreach ($lines as $line){
        			if ($line==="")
        				continue;
        			if (preg_match("/\|\s*([0-9\.]*)\s*<>\s*([0-9\.]*)\s*\|\s*([0-9]*)\s*\|\s*([0-9]*)\s*\|/",$line,$matches)){
        				foreach($matches as $key => $match){
        					if ($key == 0) continue;
        					$values[$i][$key-1] = $match;
        					if (($key==4) && ($max <(int)$match)) $max = (int)$match ;
        				}
        				$i++;
        			}
        		}
        
        		foreach($values as $key => $value){
        			if ($max == 0)
        				$values[$key][6] = 0;
        			else
        				$values[$key][6] = round((int)$value[3]*200/$max);
                }
        
                $nvalues = '';
                foreach($values as $value){
        			$nvalues.=$value[6].',';
                }
                $nvalues  = preg_replace("/,$/","",$nvalues);
                $nvalues .= '';
                return $nvalues;
        }
        echo('<script src="js/statistics.js" type="text/javascript"></script>'."\n");
        
    }
    
    public function htmlentities($string, $flag=ENT_QUOTES, $charset='')
    {
        $string = mb_convert_encoding($string,"HTML-ENTITIES","UTF-8");
        $string = ( empty($charset) ) ? @htmlentities($string, $flag) : @htmlentities($string, $flag, $charset);

        return preg_replace('/&amp;#(\d{4,5});/', '&#$1;', $string);
    }

}

function checkfilter($filter)
{
    $not_permited='/[^0-9a-zA-Z\.\|\&\=\<\>\!\^]/';
    $filter      =preg_replace($not_permited, "", $filter);
    return $filter;
}
