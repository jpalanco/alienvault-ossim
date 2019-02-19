<?php
/**
* pdf.inc
*
* File pdf.inc is used to:
*   - To manage PDF reports
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
* @package    ossim-framework\Reports
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


/**
* Function list:
* - PDF()
* - Header()
* - Footer()
* - __WriteBold()
* - __WriteTitle()
* - __WriteTable()
* - __AttackHosts()
* - AttackedHosts()
* - AttackerHosts()
* - Events()
* - Ports()
* - EventsByRisk()
* - __get_ids()
* - get_metric_ids()
* - get_alarm_ids()
* - get_event_ids()
* - get_vulnerability_ids()
* - IncidentGeneralData()
* - IncidentSummary()
* - Incident()
* - _readstr()
* - _readstr_int()
* - _parsemempng()
* - MemImage()
* - GDImage()
*/

/*$path_conf = $GLOBALS['CONF'];
$fpdf_path = $path_conf->get_conf('fpdf_path');

if (!is_dir($fpdf_path))
{
    $fpdf_path=' /usr/share/fpdf' ;
}*/

// The path has been changed to read our modified version of fpdf.php for PHP 5.4
$fpdf_path = '/usr/share/ossim/www/pdf';

define('FPDF_FONTPATH', 'font/');
define('DEFAULT_LINE_HEIGTH', 6);
include_once($fpdf_path.'/fpdf.php');

class Pdf extends FPDF 
{
    public $ossim_conn;


    public $snort_conn;


    public $conf;


    public $security_report;


    /*
    Function PDF
    $title = Title given to the PDF report
    $orientation = Can be P (portrait) or L (Landscape)
    $unit = User measure unit:
    * pt: point
    * mm: millimeter
    * cm: centimeter
    * in: inch
    
    $format = The format used for pages:
    * A3
    * A4
    * A5
    * Letter
    * Legal
    */
    public function __construct($title = 'OSSIM Report' , $orientation = 'P', $unit = 'mm', $format = 'A5') 
    {
        $this->FPDF($orientation, $unit, $format);
        $this->title = $title;
        
        /* ossim framework conf */
        $this->conf = $GLOBALS['CONF'];
        
        /* connection to ossim/snort database */
        $db = new ossim_db();
        $this->ossim_conn = $db->connect();
        $this->snort_conn = $db->snort_connect();
        
        /* security report */        
        $this->security_report = new Security_report();
        
        /* pdf properties */
        $this->AliasNbPages();
        $this->SetTitle($title);
        $this->SetCreator('OSSIM framework');
        $this->AddPage();
        
        /* Log pdf created */
        Log_action::log(19, NULL);
    }


    public function Header() 
    {
        $www_base = $this->conf->get_conf('base_dir');
        $this->Image($www_base . '/pixmaps/logo_siempdf.png', 0, 0, 30);
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(20);
        $this->Cell(0, 6, $this->title, 'B', 0, 'R');
        $this->Ln(20);
    }


    public function Footer() 
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, strftime("%d-%b-%Y", time()) , 0, 0, 'L');
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'R');
    }


    public function __WriteBold($text, $color = '') 
    {
        $this->SetFont('Arial', 'B', 12);
        
        if ($color == 'red') 
        {
            $this->SetTextColor(130, 15, 15);
        }
        elseif ($color == 'orange') 
        {
            $this->SetTextColor(200, 80, 15);
        }
        elseif ($color == 'green') 
        {
            $this->SetTextColor(4, 100, 4);
        }
        
        $this->Write(DEFAULT_LINE_HEIGTH, $text);
        $this->SetFont('Arial', '', 12);
        $this->SetTextColor(0);
    }


    public function __WriteTitle($title, $color = 'blue') 
    {
        $this->SetFont('Arial', 'B', 14);
        
        if ($color == 'blue') 
        {
            $this->SetTextColor(102, 102, 153);
        }
        elseif ($color == 'red') 
        {
            $this->SetTextColor(130, 15, 15);
        }
        elseif ($color == 'orange') 
        {
            $this->SetTextColor(200, 80, 15);
        }
        
        $this->MultiCell(125, 6, $title);
        $this->SetTextColor(0);
        $this->SetFont('', '', 10);
        $this->Ln();
    }


    /*
    __WriteTable()
    
    example of use:
    
    $title   = "TITLE";
    $length  = array (15, 15);
    $content = array (
    array ("host", "occurrences"),
    array ("192.168.0.25", "6"),
    array ("192.168.0.26", "9")
    );
    
    TITLE
    +---------------+---------------+
    | host          | occurrences   |
    +---------------+---------------+
    | 192.168.0.25  | 6             |
    |---------------+---------------|
    | 192.168.0.26  | 9             |
    +---------------+---------------+
    */
    public function __WriteTable($title, $length, $content, $adjust, $color = 'blue') 
    {
        $fill = 1;
        $count = 0;
        $this->__WriteTitle($title, $color);
        
        if ($color == 'blue') 
        {
            $this->SetFillColor(102, 102, 153);
        }
        elseif ($color == 'red') 
        {
            $this->SetFillColor(130, 15, 15);
        }
        elseif ($color == 'orange') 
        {
            $this->SetFillColor(200, 80, 15);
        }
        
        $this->SetTextColor(255);
        $this->SetFont('', 'B', 11);
        $MaxLength = array();
        $count = 0;
        
        foreach ($content as $row) 
        {
            foreach ($row as $element) 
            {
                if (!array_key_exists($count, $MaxLength)) 
                {
                    $MaxLength[$count] = 0;
                }
                
                $MaxLength[$count] = max($this->GetStringWidth($element) , $MaxLength[$count]);
                $count++;
            }
            
            $count = 0;
        }
        
        foreach ($MaxLength as $width) 
        {
            if ($adjust) 
            {
                $length[$count] = min($length[$count], ($width + 2));
            } 
            else 
            {
                $length[$count] = max($length[$count], ($width + 2));
            }
            
            $count++;
        }
        
        $count = 0;
        foreach ($content as $row) 
        {
            foreach ($row as $element) 
            {
                $this->Cell($length[$count], 5, $element, 1, 0, 'C', $fill);
                
                $count++;
            }
            $this->Ln();
            
            if ($color == 'blue')
            {
                $this->SetFillColor(195, 195, 215);
            }
            elseif ($color == 'red') 
            {
                $this->SetFillColor(255, 235, 215);
            }
            elseif ($color == 'orange')
            {
                $this->SetFillColor(255, 236, 168);
            }
            
            $this->SetTextColor(0);
            $this->SetFont('', '', 9);
            $fill = !$fill;
            $count = 0;
        }
        
        $this->Ln(5);
    }


    public function __AttackHosts($title, $ip_target, $limit, $color, $type, $date_from, $date_to) 
    {
    	if ($limit == '')
    	{
    	    $limit = 10;
        }
    	if ($color == '')
    	{
    	    $color = 'blue';
        }
    	if ($type == '')
    	{
    	    $type = 'event';
        }
        
        $content[] = array('Host', 'Occurrences');        
        $length    = array(80, 25);
        
        $attack = $this->security_report->AttackHost($ip_target, $limit, $type, $date_from, $date_to);
        
        /* Resolve IPs */
        for ($i = 0; $i < count($attack); $i++) 
        {            
            $hostnames = Asset_host::get_name_by_ip($this->ossim_conn, $attack[$i][0]);
            
            if (count($hostnames) == 1)
            {
                $attack[$i][0] = array_pop($hostnames);
            }
        }
        
        $content = array_merge($content, $attack);
        
        
        $this->__WriteTable($title, $length, $content, 1, $color);
    }


    public function AttackedHosts($limit, $type, $date_from, $date_to) 
    {    
    	if ($limit == '')
    	{
    	    $limit = 10;
        }
    	if ($type == '')
    	{
    	    $type = 'event';
        }
        $this->__AttackHosts("Top $limit Attacked Hosts", 'ip_dst', $limit, 'red', $type, $date_from, $date_to);
    }


    public function AttackerHosts($limit, $type, $date_from, $date_to) 
    {
    	if ($limit == '')
    	{
    	    $limit = 10;
        }
        
    	if ($type == '')
    	{
    	    $type = 'event';
        }
        
        $this->__AttackHosts("Top $limit Attacker Hosts", "ip_src", $limit, "blue", $type, $date_from, $date_to);
    }


    public function Events($limit, $type, $date_from, $date_to) 
    {
    	if ($limit == '')
    	{
    	    $limit = 10;
        }
    	if ($type == '')
    	{
    	    $type = 'event';
        }    
        
        $content[] = array('Event', 'Occurrences');        
        $length    = array(100, 25);

      
        $content = array_merge($content, $this->security_report->Events($limit, $type, $date_from, $date_to));
        
        $this->__WriteTable("Top $limit Events", $length, $content, 1);
    }


    public function Ports($limit, $type, $date_from, $date_to) 
    {
    	if ($limit == '')
    	{
    	    $limit = 10;
        }
    	if ($type == '')
    	{
    	    $type = 'event';
        }    
        
        $content[] = array('Port', 'Service', 'Ocurrences');     
        $length    = array(35, 40, 25);       
        
        $content = array_merge($content, $this->security_report->Ports($limit, $type, $date_from, $date_to));
        
        $this->__WriteTable("Top $limit Destination Ports", $length, $content, 1, 'orange');
    }


    public function EventsByRisk($limit, $type, $date_from, $date_to) 
    {
    	if ($limit == '')
    	{
    	    $limit = 10;
        }
    	if ($type == '')
    	{
    	    $type = 'event';
        }
        
        $content[] = array('Event', 'Risk');     
        $length    = array(100, 25);       
        
        $content = array_merge($content, $this->security_report->EventsByRisk($limit, $type, $date_from, $date_to));
        
        $this->__WriteTable("Top $limit Events by risk", $length, $content, 1);
    }


    /*
    * INCIDENTS
    */
    private function __get_ids($ref, $priority, $fil, $user = NULL, $host = NULL, $conn = NULL) 
    {        
		$ids = array();
		
		if ($ref != 'Metric' && $ref != 'Alarm' && $ref != 'Event' && $ref != 'Anomaly' && $ref != 'Vulnerability')
		{
			return NULL;
        }
        
		$args = array(
			"where" => " AND incident.ref = '$ref' AND  $priority $fil ",
			"order" => " ORDER BY incident.priority DESC"
		);
		
		$incident_list = Incident::get_list_filtered($conn, $host, $args, $user);
        		
		foreach ($incident_list as $incident)
		{
		    $ids[$incident->get_id()] = $incident->get_id();
        }
        
		return $ids;
    }
	
	
    public function get_metric_ids($priority, $fil, $user = NULL, $host = NULL, $conn = NULL) 
    {
        return $this->__get_ids('Metric', $priority, $fil, $user = NULL, $host, $conn);
    }
    
	
    public function get_alarm_ids($priority, $fil, $user = NULL, $host = NULL, $conn = NULL) 
    {
        return $this->__get_ids('Alarm', $priority, $fil, $user = NULL, $host, $conn);
    }
    
	
    public function get_event_ids($priority, $fil, $user = NULL, $host = NULL, $conn = NULL) 
    {
        return $this->__get_ids('Event', $priority, $fil, $user = NULL, $host, $conn);
    }
    
	
    public function get_anomaly_ids($priority, $fil, $user = NULL, $host = NULL, $conn = NULL) 
    {
        return $this->__get_ids('Anomaly', $priority, $fil, $user = NULL, $host, $conn);
    }
    
	
    function get_vulnerability_ids($priority, $fil, $user = NULL, $host = NULL, $conn = NULL) 
    {
        return $this->__get_ids('Vulnerability', $priority, $fil, $user = NULL, $host, $conn);
    }
	
    
    public function IncidentGeneralData($title, $date) 
    {
        $this->__WriteTitle(_('GENERAL DATA'));
        $this->SetFont('Arial', '', 12);
        $this->__WriteBold(_('Title: '));
        $this->Write(DEFAULT_LINE_HEIGTH, $title);
        $this->Ln(DEFAULT_LINE_HEIGTH);
        
        $this->__WriteBold(_('Date: '));
        $this->Write(DEFAULT_LINE_HEIGTH, $date);
        $this->Ln(DEFAULT_LINE_HEIGTH * 2);
    }
	
    
    public function IncidentSummary($title, $ref, $summary, $priority, $fil) 
    {
        $ids = array();
        
        $this->__WriteTitle($title);
        $this->SetFont('Arial', '', 12);
        
        /* Summary */
        $this->Write(DEFAULT_LINE_HEIGTH, $summary);
        $this->Ln(DEFAULT_LINE_HEIGTH * 2);
        
        $this->Write(DEFAULT_LINE_HEIGTH, _("List of $ref" . "s:"));
        $this->Ln(DEFAULT_LINE_HEIGTH);
        $args = array(
		"where" =>  " AND ref = '$ref' and $priority $fil ",
		"order" => " ORDER BY priority DESC"
	);
        $incident_list = Incident::get_list($this->ossim_conn, $args);
        if (is_array($incident_list)) 
        {
            foreach ($incident_list as $incident) 
            {
                $priority = $incident->get_priority($this->ossim_conn);
                $color = $incident->get_priority_bgcolor($priority);
                
                /* date */
                $this->Write(DEFAULT_LINE_HEIGTH, $incident->get_date() . '  ');
                
                /* title */
                $this->__WriteBold($incident->get_title() , $color);
                $this->Ln();
                
                $ids[] = $incident->get_id();
            }
        }
        
        $this->Ln(10);
        
        return $ids;
    }


	public function IncidentSummaryNoPDF($title, $ref, $summary, $priority, $fil, $assets = NULL, $conn = NULL, $user = NULL) 
	{
        $ids = array();
        
		$returnContent = array();
       
        $this->__WriteTitle($title);
        $this->SetFont('Arial', '', 12);
        
        /* Summary */
        $this->Write(DEFAULT_LINE_HEIGTH, $summary);
        $this->Ln(DEFAULT_LINE_HEIGTH * 2);    
        $this->Ln(DEFAULT_LINE_HEIGTH);
				       
		$incident_list = Incident::get_list_filtered($conn, $assets, array("where" => " AND ref = '$ref' AND $priority $fil"), $user);
		
		
		foreach ($incident_list as $incident)
		{
        	$content = array();
			
			$priority = $incident->get_priority($this->ossim_conn);
			$color    = $incident->get_priority_bgcolor($priority);
			
			
			$content[] = $incident->get_date() . '  ';
		
			$content[] = $incident->get_title();
			$content[] = $color;
			$this->Ln();
									
			$ids[] = $incident->get_id();			
			
			$returnContent[] = $content;
        }
                            
        $this->Ln(10);
    
        $returnContent['TipoIncidente'] = 'ListSummaryIncident';

        return $returnContent;
    }


    public function Incident($incident_id) 
    {
        $incident_list = Incident::get_list($this->ossim_conn, array("where" => " AND incident.id = $incident_id"));
        $incident = $incident_list[0];
        
        /* title */        
        $this->__WriteTitle($incident->get_title());
        
        /* Alarm or Metric specific data */        
        if ($incident->get_ref() == 'Alarm' or $incident->get_ref() == 'Event') 
        {
            if ($alarm_list = $incident->get_alarms($this->ossim_conn)) 
            {
                foreach ($alarm_list as $alarm) 
                {
                    $this->__WriteBold('Source ips: ');
                    $this->Write(DEFAULT_LINE_HEIGTH, $alarm->get_src_ips());
                    $this->Ln();
                    
                    $this->__WriteBold('Destination ips: ');
                    $this->Write(DEFAULT_LINE_HEIGTH, $alarm->get_dst_ips());
                    $this->Ln();
                    
                    $this->__WriteBold('Source ports: ');
                    $this->Write(DEFAULT_LINE_HEIGTH, $alarm->get_src_ports());
                    $this->Ln();
                    
                    $this->__WriteBold('Destination ports: ');
                    $this->Write(DEFAULT_LINE_HEIGTH, $alarm->get_src_ports());
                    $this->Ln();
                } 
            } 
        } 
        elseif ($incident->get_ref() == 'Metric') 
        {
            if ($metric_list = $incident->get_metrics($this->ossim_conn)) 
            {
                foreach ($metric_list as $metric) 
                {
                    $this->__WriteBold('Target: ');
                    $this->Write(DEFAULT_LINE_HEIGTH, $metric->get_target());
                    $this->Ln();
                    
                    $this->__WriteBold('Metric type: ');
                    $this->Write(DEFAULT_LINE_HEIGTH, $metric->get_metric_type());
                    $this->Ln();
                    
                    $this->__WriteBold('Metric value: ');
                    $this->Write(DEFAULT_LINE_HEIGTH, $metric->get_metric_value());
                    $this->Ln();
                } 
            } 
        }
         elseif ($incident->get_ref() == 'Anomaly') 
         {
            if ($anomaly_list = $incident->get_anomalies($this->ossim_conn)) 
            {
                foreach ($anomaly_list as $anomaly) 
                {
                    $this->__WriteBold('IP: ');
                    $this->Write(DEFAULT_LINE_HEIGTH, $anomaly->get_ip() . '     ');
                    
                    $this->__WriteBold('Anomaly type: ');                    
                    $this->Write(DEFAULT_LINE_HEIGTH, $anomaly->get_anom_type() . '     ');
                    
                    $this->__WriteBold('Original data: ');                    
                    $this->Write(DEFAULT_LINE_HEIGTH, $anomaly->get_data_orig() . '     ');
                    
                    $this->__WriteBold('New data: ');                    
                    $this->Write(DEFAULT_LINE_HEIGTH, $anomaly->get_data_new() . '     ');
                    $this->Ln();
                } 
            } 
        } 
        elseif ($incident->get_ref() == 'Vulnerability') 
        {
            if ($vulnerability_list = $incident->get_vulnerabilities($this->ossim_conn)) 
            {
                foreach ($vulnerability_list as $vulnerability) 
                {
                    $this->__WriteBold('IP: ');
                    $this->Write(DEFAULT_LINE_HEIGTH, $vulnerability->get_ip() . '     ');
                    
                    $this->__WriteBold('Port: ');
                    $this->Write(DEFAULT_LINE_HEIGTH, $vulnerability->get_port() . '     ');
                    
                    $this->__WriteBold('Plugin ID: ');
                    $this->Write(DEFAULT_LINE_HEIGTH, $vulnerability->get_nessus_id() . '     ');
                    $this->Ln();
                } 
            } 
        } 
        /* In charge */
        
        $this->Ln();
        $this->__WriteBold('In charge: ');
        $this->Write(DEFAULT_LINE_HEIGTH, $incident->get_in_charge($this->ossim_conn) . '     ');
        /* status */
        $this->__WriteBold('Status: ');
        $status = $incident->get_status($this->ossim_conn);
        
        $color = '';
        if ($status == 'Open') 
        {
            $color = 'red';
        }
        
        $this->__WriteBold($status, $color);
        $this->__WriteBold('     ');
        
        /* Priority */
        $this->__WriteBold('Priority: ');
        $priority = $incident->get_priority($this->ossim_conn);
        $color = $incident->get_priority_bgcolor($priority);
        $this->__WriteBold($priority, $color);
        $this->Ln(DEFAULT_LINE_HEIGTH * 3);
        
        /* tickets */
        if ($tickets = $incident->get_tickets($this->ossim_conn)) 
        {
            foreach ($tickets as $ticket) 
            {
                $this->__WriteBold('[' . $ticket->get_date() . ' revision] ');
                $this->Ln();
                
                $this->__WriteBold('User: ');
                $this->Write(DEFAULT_LINE_HEIGTH, $ticket->get_user());
                $this->Ln();
                
                $this->__WriteBold('Description: ');
                $this->Write(DEFAULT_LINE_HEIGTH, $ticket->get_description());
                $this->Ln();
                
                if ($ticket->get_action()) 
                {
                    $this->__WriteBold('Action: ');
                    $this->Write(DEFAULT_LINE_HEIGTH, $ticket->get_action());
                    $this->Ln();
                }
                
                $this->__WriteBold('Priority: ');
                $priority = $ticket->get_priority();
                $color = $incident->get_priority_bgcolor($priority);
                $this->__WriteBold($priority, $color);
                $this->Ln();
                
                $this->__WriteBold('Status: ');
                $status = $incident->get_status($this->ossim_conn);
                
                $color = '';
                if ($status == 'Open') 
                {
                    $color = 'red';
                }
                
                $this->__WriteBold($status, $color);
                $this->Ln();
                
                $this->__WriteBold('In charge: ');
                $this->Write(DEFAULT_LINE_HEIGTH, $ticket->get_in_charge());
                $this->Ln();
                
                if ($ticket->get_transferred) 
                {
                    $this->__WriteBold('Transferred: ');
                    $this->Write(DEFAULT_LINE_HEIGTH, $ticket->get_transferred());
                    $this->Ln();
                }
                
                $this->Ln(DEFAULT_LINE_HEIGTH * 2);
            } 
        }
    }


    public function IncidentNoPDF($incident_id) 
    {
        $incident_list = Incident::get_list($this->ossim_conn, array("where" => " AND incident.id = $incident_id"));
        $incident = $incident_list[0];
        
        $content = array();
        
                
        $content['Title']         = $incident->get_title();
        $content['TipoIncidente'] = $incident->get_ref();
        
        if ($incident->get_ref() == 'Alarm' or $incident->get_ref() == 'Event') 
        {
            if ($alarm_list = $incident->get_alarms($this->ossim_conn)) 
            {
                foreach ($alarm_list as $alarm) 
                {                                        
                    $content['SourceIps']        = $alarm->get_src_ips();
                    $content['DestinationIps']   = $alarm->get_dst_ips();
                    $content['SourcePorts']      = $alarm->get_src_ports();
                    $content['DestinationPorts'] = $alarm->get_dst_ports();                     
                }
            } 
        } 
        elseif ($incident->get_ref() == 'Metric') 
        {
            if ($metric_list = $incident->get_metrics($this->ossim_conn)) 
            {
                foreach ($metric_list as $metric) 
                {                   
                    $content['Target']      = $metric->get_target();
                    $content['MetricType']  = $metric->get_metric_type();
                    $content['MetricValue'] = $metric->get_metric_value();
                } 
            } 
        } 
        elseif ($incident->get_ref() == 'Anomaly') 
        {
            if ($anomaly_list = $incident->get_anomalies($this->ossim_conn)) 
            {
                foreach ($anomaly_list as $anomaly) 
                {
                    $content['IP']           = $anomaly->get_ip();
                    $content['AnomalyType']  = $anomaly->get_anom_type();
                    $content['OriginalData'] = $anomaly->get_data_orig();
                    $content['NewData']      = $anomaly->get_data_new();
                } 
            } 
        } 
        elseif ($incident->get_ref() == 'Vulnerability') 
        {
            if ($vulnerability_list = $incident->get_vulnerabilities($this->ossim_conn)) 
            {
                foreach ($vulnerability_list as $vulnerability) 
                {
                    $content['IP']       = $vulnerability->get_ip();
                    $content['Port']     = $vulnerability->get_port();
                    $content['NessusID'] = $vulnerability->get_nessus_id();
                } 
            } 
        } 
        
        /* In charge */
        $content['InCharge'] = $incident->get_in_charge($this->ossim_conn);
         
        /* Status */        
        $content['Status'] = $incident->get_status($this->ossim_conn);
        $color = '';
        if ($content['Status'] == 'Open') 
        {
            $color = 'red';
        }
        
        /* Priority */
        
        $content['Priority'] = $incident->get_priority($this->ossim_conn);
        
        $color = $incident->get_priority_bgcolor($content['Priority']);
        
        return $content;
    }

	
	
    /* END JASPER*/
    /* ---------------------------------------------------------------- */
    /*                                                                  */
    /*                         Print images                             */
    /*                                                                  */
    /* From code by Xavier Nicolay                                      */
    /* Script 25 on www.fpdf.org                                        */
    /* (c) Xavier Nicolay                                               */
    /* V1.0 : 2004-01-17                                                */
    /*                                                                  */
    /* ---------------------------------------------------------------- */
    
    public function _readstr($var, &$pos, $n) 
    {
        //Read some bytes from string
        $string = substr($var, $pos, $n);
        $pos+= $n;
        
        return $string;
    }
    
    
    public function _readstr_int($var, &$pos) 
    {
        //Read a 4-byte integer from string
        $i = ord($this->_readstr($var, $pos, 1)) << 24;
        $i+= ord($this->_readstr($var, $pos, 1)) << 16;
        $i+= ord($this->_readstr($var, $pos, 1)) << 8;
        $i+= ord($this->_readstr($var, $pos, 1));
        
        return $i;
    }
    
    
    public function _parsemempng($var) 
    {
        $pos = 0;
        $sig = $this->_readstr($var, $pos, 8);
        if ($sig != chr(137) . 'PNG' . chr(13) . chr(10) . chr(26) . chr(10)) 
        {
            $this->Error('Not a PNG image');
        }
        
        //Read header chunk
        $this->_readstr($var, $pos, 4);
        $ihdr = $this->_readstr($var, $pos, 4);
        if ($ihdr != 'IHDR') 
        {
            $this->Error('Incorrect PNG Image');
        }
        
        $w = $this->_readstr_int($var, $pos);
        $h = $this->_readstr_int($var, $pos);
        $bpc = ord($this->_readstr($var, $pos, 1));
        
        if ($bpc > 8) 
        {
            $this->Error('16-bit depth not supported: ' . $file);
        }
        
        $ct = ord($this->_readstr($var, $pos, 1));        
        if ($ct == 0) 
        {
            $colspace = 'DeviceGray';
        }
        elseif ($ct == 2) 
        {
            $colspace = 'DeviceRGB';
        }
        elseif ($ct == 3) 
        {
            $colspace = 'Indexed';
        }
        else 
        {
            $this->Error('Alpha channel not supported: ' . $file);
        }
        
        if (ord($this->_readstr($var, $pos, 1)) != 0) 
        {
            $this->Error('Unknown compression method: ' . $file);
        }
        
        if (ord($this->_readstr($var, $pos, 1)) != 0) 
        {
            $this->Error('Unknown filter method: ' . $file);
        }
        
        if (ord($this->_readstr($var, $pos, 1)) != 0) 
        {
            $this->Error('Interlacing not supported: ' . $file);
        }
        
        $this->_readstr($var, $pos, 4);        
        
        $parms = '/DecodeParms <</Predictor 15 /Colors ' . ($ct == 2 ? 3 : 1) . ' /BitsPerComponent ' . $bpc . ' /Columns ' . $w . '>>';
        //Scan chunks looking for palette, transparency and image data
        $pal  = '';
        $trns = '';
        $data = '';
        
        do 
        {
            $n = $this->_readstr_int($var, $pos);
            $type = $this->_readstr($var, $pos, 4);
            
            if ($type == 'PLTE') 
            {
                //Read palette
                $pal = $this->_readstr($var, $pos, $n);
                $this->_readstr($var, $pos, 4);
            } 
            elseif ($type == 'tRNS') 
            {
                //Read transparency info
                $t = $this->_readstr($var, $pos, $n);
                
                if ($ct == 0) 
                {
                    $trns = array(
                        ord(substr($t, 1, 1))
                    );
                }
                elseif ($ct == 2) 
                {
                    $trns = array(
                        ord(substr($t, 1, 1)),
                        ord(substr($t, 3, 1)),
                        ord(substr($t, 5, 1))
                    );
                }
                else 
                {
                    $pos = strpos($t, chr(0));
                    
                    if (is_int($pos))
                    {
                        $trns = array(
                            $pos
                        );
                    }
                }
                
                $this->_readstr($var, $pos, 4);
            } 
            elseif ($type == 'IDAT') 
            {
                //Read image data block
                $data.= $this->_readstr($var, $pos, $n);
                $this->_readstr($var, $pos, 4);
            } 
            elseif ($type == 'IEND') 
            {
                break;
            }
            else 
            {
                $this->_readstr($var, $pos, $n + 4);
            }
        }
        while ($n);
        
        if ($colspace == 'Indexed' and empty($pal)) 
        {
            $this->Error('Missing palette in ' . $file);
        }
        
        return array(
            'w'     => $w,
            'h'     => $h,
            'cs'    => $colspace,
            'bpc'   => $bpc,
            'f'     => 'FlateDecode',
            'parms' => $parms,
            'pal'   => $pal,
            'trns'  => $trns,
            'data'  => $data
        );
    }
    
    
    public function MemImage($data, $x, $y, $width = 0, $height = 0) 
    {
        $id = md5($data);
        
        if (!isset($this->images[$id])) 
        {
            $info = $this->_parsemempng($data);
            $info['i'] = count($this->images) + 1;
            $this->images[$id] = $info;
        } 
        else 
        {
            $info = $this->images[$id];
        }
        
        // Automatic width and height calculation if needed
        if ($width == 0 and $height == 0) 
        {
            // image at 72 dpi
            $width  = $info['w'] / $this->k;
            $height = $info['h'] / $this->k;
        }
        
        if ($width == 0) 
        {
            $width = $height * $info['w'] / $info['h'];
        }
        
        if ($height == 0) 
        {
            $height = $width * $info['h'] / $info['w'];
        }
        
        $this->_out(sprintf('q %.2f 0 0 %.2f %.2f %.2f cm /I%d Do Q', $width * $this->k, $height * $this->k, $x * $this->k, ($this->h - ($y + $info['h'])) * $this->k, $info['i']));
    }
    
    
    public function GDImage($image, $x, $y, $width = 0, $height = 0) 
    {
        ob_start();
        imagepng($image);
        $data = ob_get_contents();
        ob_end_clean();        
        $this->MemImage($data, $x, $y, $width, $height);
    }
}

/* End of file pdf.inc */
/* Location: ../include/classes/pdf.inc */
