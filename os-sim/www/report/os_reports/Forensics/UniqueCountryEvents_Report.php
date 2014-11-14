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


require 'general.php';

if ( Session::menu_perms("analysis-menu", "EventsForensics") ) 
{
	$htmlPdfReport->pageBreak();
    $htmlPdfReport->setBookmark($title);
	
	$htmlPdfReport->set($htmlPdfReport->newTitle($title, $date_from, $date_to, null));
	
	$htmlPdfReport->set("\n<br/><br/>\n");
	
	
	$db     = new ossim_db();
	$conn   = $db->connect();
	
	$conn->SetFetchMode(ADODB_FETCH_ASSOC);	

	if (!$rs = & $conn->Execute($query, $params)){
		$htmlPdfReport->set("<table class='w100' cellpadding='0' cellspacing='0'>
                                <tr><td class='w100' align='center' valign='top'>"._("No data available")."</td></tr>
                             </table>\n");
	}
	else
	{
        // Country Events 
				       		
		$htmlPdfReport->set("<table style='width: 193mm;' cellpadding='0' cellspacing='0'>
                                <tr><th style='width: 193mm;' align='center'>"._("SIEM Unique Country Events")."</th></tr>
                              </table><br/>\n");
        
        $htmlPdfReport->set("<table style='width: 193mm; margin:auto;' cellpadding='0' cellspacing='2'>");
      
		
        //Headers
										
        $th_style = 'font-size: 10px;';
						
        $html_headers = "<th align='center' valign='middle' style='".$th_style." width:58mm;'>"._("Country")."</th>\n
						 <th align='center' valign='middle' style='".$th_style." width:20mm;'>"._("Total")." #</th>\n
						 <th align='center' valign='middle' style='".$th_style." width:20mm;'>"._("Unique Src")." #</th>\n
						 <th align='center' valign='middle' style='".$th_style." width:20mm;'>"._("Unique Dst")." #</th>\n
					     <th align='center' valign='middle' style='".$th_style." width:58mm;'>"._("Events")."</th>\n";
						 
		$htmlPdfReport->set("<tr>\n".$html_headers."</tr>\n");
		
		if ( $rs->RecordCount() == 0 )
		{
			$htmlPdfReport->set("<tr>
									<td colspan='5' style='text-align:center; padding: 15px 0px;' class='w100' valign='middle'>"._("No countries found for this search criteria")."</td>
								</tr>\n");
		}
		else
		{
		
			$i = 0;
							
			while ( !$rs->EOF )
			{
				$td_style = 'font-size: 10px; text-align:center;';
							
				$html_fields = "<td valign='middle' style='".$td_style." width:58mm; text-align:left;'>";
				
				
				if ( preg_match('/base64/',$rs->fields['cell_data']) ){
					$html_fields .= preg_replace("/img src/","img style='margin-left: 2mm; margin-right: 2mm;' align='absmiddle' align='center' src",$rs->fields['cell_data']);
				}								
				elseif ( $rs->fields['cell_data'] != '' ){
					$html_fields .= "<img border='0' style='margin-left: 2mm; margin-right: 2mm;' align='absmiddle' align='center' src='".$rs->fields['cell_data']."'/>";
				}
									
				$html_fields .= $rs->fields['dataV1']."</td>\n
								<td valign='middle' style='".$td_style." width:20mm;'>".$rs->fields['dataI1']."</td>\n
								<td valign='middle' style='".$td_style." width:20mm;'>".$rs->fields['dataI2']."</td>\n
								<td valign='middle' style='".$td_style." width:20mm;'>".$rs->fields['dataI3']."</td>\n";
								
								
				if ( $rs->RecordCount() == ($i+1) ){
					$html_fields .= "<td valign='middle' style='".$td_style." width:58mm; text-align:left;'></td>\n"; 							 
				}
				else
				{
					$percentage = (int)$rs->fields['dataV3'];
					$width = ( 57 * $percentage );
					$width = ( $width == 0 ) ? "0mm" : ($width/100)."mm";	

					$st_container = "position:absolute; width:".$width."; z-index: 200000; height: 15px;";
					$st_container.= ( $percentage > 0 ) ? " background: #84C973;" : " background: #FFFFFF;" ;
					
					$st_text      = "width:56mm; position:relative; z-index: 200001; text-align: center; margin: 2px auto;";				
					
					
					$html_fields .= "<td valign='middle' style='".$td_style." width:58mm; text-align:left;'>
										<div style='border: solid 1px #CCCCCC; margin:auto; width:56mm;'>
											<div style='position: relative; margin: 1px;'>
												<div style='".$st_container."'></div>
												<div style='".$st_text."'>".$percentage."%</div>
											</div>
										</div>			 
									</td>\n"; 
				}
												  
					
				$bc = ( $i++%2!=0 ) ? "class='par'" : "";           
				$htmlPdfReport->set("<tr style='width: 193mm;' $bc>\n".$html_fields."</tr>\n");
										
				$rs->MoveNext();
			}
		}
                       
        $htmlPdfReport->set("\n</table>\n");
        
	}
		
	$db->close($conn);	
}

?>