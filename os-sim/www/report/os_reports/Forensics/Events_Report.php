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

if (Session::menu_perms("analysis-menu", "EventsForensics"))
{
    $htmlPdfReport->pageBreak();
    $htmlPdfReport->setBookmark($title);

    $htmlPdfReport->set($htmlPdfReport->newTitle($title, $date_from, $date_to, null));

    $htmlPdfReport->set("\n<br/><br/>\n");


    $db   = new Ossim_db();
    $conn = is_array($_SESSION["server"]) && $_SESSION["server"][0]!="" ? $db->custom_connect($_SESSION["server"][0],$_SESSION["server"][2],$_SESSION["server"][3]) : $db->connect();

    $conn->SetFetchMode(ADODB_FETCH_ASSOC);

    $rs = $conn->Execute($query, $params);

    if (!$rs)
    {
        $htmlPdfReport->set("<table style='width: 193mm; margin:auto;' cellpadding='0' cellspacing='2'>
                                <tr><td class='w100' align='center' valign='middle'>"._("No data available")."</td></tr>
                             </table>\n");
    }
    else
    {
        //Headers

        $th_style = 'font-size: 10px;';

        $html_headers = "<th align='center' valign='middle' style='".$th_style." width:42mm;'>"._("Signature")."</th>\n
                         <th align='center' valign='middle' style='".$th_style." width:14mm;'>"._("Date")."</th>\n
                         <th align='center' valign='middle' style='".$th_style." width:35mm;'>"._($var_field)."</th>\n
                         <th align='center' valign='middle' style='".$th_style." width:10mm;'>"._("OTX")."</th>\n
                         <th align='center' valign='middle' style='".$th_style." width:30mm;'>"._("Source")."</th>\n
                         <th align='center' valign='middle' style='".$th_style." width:30mm;'>"._("Destination")."</th>\n
                         <th align='center' valign='middle' style='".$th_style." width:12mm;'>"._("Risk")."</th>\n";

        if ($rs->RecordCount() == 0)
        {
            $htmlPdfReport->set("<table class='w100' style='margin:auto;' cellpadding='0' cellspacing='2'>
                                    <tr>".$html_headers."</tr>
                                    <tr>
                                        <td colspan='6' style='text-align:center; padding: 15px 0px;' class='w100' valign='middle'>"._("No events found for this search criteria")."</td>
                                    </tr>
                                 </table>\n");
        }
        else
        {
            $params = array(34, $user);

            $conn->SetFetchMode(ADODB_FETCH_ASSOC);
            $query  = "SELECT dataV1, dataV2, dataI1 FROM datawarehouse.report_data WHERE id_report_data_type=? AND user=?";

            $rs1    = $conn->Execute($query, $params);

            $data_plot = array();

            while (!$rs1->EOF)
            {
                $data_plot[$rs1->fields['dataV1']] = array(
                    'label' => $rs1->fields['dataV2'],
                    'value' => $rs1->fields['dataI1']
                );

                $rs1->MoveNext();
            }

            $shared_file = $dDB["_shared"]->dbfile();
            $dDB["_shared"]->put("data", $data_plot);

            //Events Trends

            $htmlPdfReport->set("<table class='w100' cellpadding='0' cellspacing='0'>
                                    <tr><th class='w100' align='center'>"._("Events Trend")."</th></tr>
                                    <tr><td class='w100'>");


            $htmlPdfReport->set('<img src="'.$htmlPdfReport->newImage('report/os_reports/Forensics/graph_lines.php?shared='.urlencode($shared_file), 'png').'" />');

            $htmlPdfReport->set("   </td></tr>
                                </table><br/><br/>\n");

            //Events

            $htmlPdfReport->set("<table style='width: 193mm;' cellpadding='0' cellspacing='0'>
                                    <tr><th style='width: 193mm;' align='center'>"._("SIEM Events")."</th></tr>
                                  </table><br/>\n");

            $htmlPdfReport->set("<table style='width: 193mm; margin:auto;' cellpadding='0' cellspacing='2'>");


            $htmlPdfReport->set("<tr>\n".$html_headers."</tr>\n");

            $i = 0;
            while (!$rs->EOF)
            {
                $td_style = 'font-size: 10px; text-align:center;';

                $html_fields = "<td valign='middle' style='".$td_style." width:42mm; text-align: left;'>".Util::wordwrap($rs->fields['dataV1'], 20, "<br/>", true)."</td>\n
                                <td valign='middle' style='".$td_style." width:14mm;'>".$rs->fields['dataV2']."</td>\n
                                <td valign='middle' style='".$td_style." width:35mm;'>".$rs->fields['dataV11']."</td>\n
                                <td valign='middle' style='".$td_style." width:10mm;'>".($rs->fields['dataI1']>0 ? _("Yes") : _("N/A"))."</td>\n
                                <td valign='middle' style='".$td_style." width:30mm;'>".Util::wordwrap($rs->fields['dataV3'], 21, "<br/>", true);

                                if ( $rs->fields['dataV4'] != '' )
                                {
                                    $html_fields .= "<br/><img border='0' align='absmiddle' align='center' src='".$rs->fields['dataV4']."'/>";
                                }

                $html_fields .= "</td>\n
                                 <td valign='middle' style='".$td_style." width:30mm;'>".Util::wordwrap($rs->fields['dataV5'], 21, "<br/>", true);

                                if ( $rs->fields['dataV6'] != '' )
                                {
                                    $html_fields .= "<br/><img border='0' align='absmiddle' align='center' src='".$rs->fields['dataV6']."'/>";
                                }

                $html_fields .= "</td>\n

                                <td valign='middle' style='".$td_style." width:12mm;'>
                                    <img border='0' style='width:12mm;' align='absmiddle' align='center' src='".$rs->fields['dataV10']."'/>
                                </td>\n";


                $bc = ( $i++%2!=0 ) ? "class='par'" : "";
                $htmlPdfReport->set("<tr style='width: 193mm;' $bc>\n".$html_fields."</tr>\n");
                $rs->MoveNext();
            }


            $htmlPdfReport->set("\n</table>\n");

        }

        $db->close();
    }
}
?>
