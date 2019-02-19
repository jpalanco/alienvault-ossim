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

    $rs = $conn->Execute($query, $params);

    if (!$rs){
        $htmlPdfReport->set("<table class='w100' cellpadding='0' cellspacing='0'>
                                <tr><td class='w100' align='center' valign='top'>"._("No data available")."</td></tr>
                             </table>\n");
    }
    else
    {
        // Source Port

        switch ($type){
            case "0":
                $r_title = _("Source Port (TCP/UDP)");
            break;

            case "1":
                $r_title = _("Source Port (TCP)");
            break;

            case "2":
                $r_title = _("Source Port (UDP)");
            break;
        }

        $htmlPdfReport->set("<table style='width: 193mm;' cellpadding='0' cellspacing='0'>
                                <tr><th style='width: 193mm;' align='center'>".$r_title."</th></tr>
                              </table><br/>\n");

        $htmlPdfReport->set("<table style='width: 193mm; margin:auto;' cellpadding='0' cellspacing='2'>");


        //Headers

        $th_style = 'font-size: 10px;';


        $html_headers = "<th align='center' valign='middle' style='".$th_style." width:60mm;'>"._("Port")."</th>\n
                         <th align='center' valign='middle' style='".$th_style." width:77mm;'>"._($var_field)."</th>\n
                         <th align='center' valign='middle' style='".$th_style." width:22mm;'>"._("Occurrences")." #</th>\n
                         <th align='center' valign='middle' style='".$th_style." width:22mm;'>"._("Unique events")." #</th>\n";

        $htmlPdfReport->set("<tr>\n".$html_headers."</tr>\n");

        if ( $rs->RecordCount() == 0 )
        {
            $htmlPdfReport->set("<tr>
                                    <td colspan='4' style='text-align:center; padding: 15px 0px;' class='w100' valign='middle'>"._("No ports found for this search criteria")."</td>
                                </tr>\n");
        }
        else
        {

            $i = 0;
            while ( !$rs->EOF )
            {
                $td_style = 'font-size: 10px; text-align:center;';

                $html_fields = "<td valign='middle' style='".$td_style." width:60mm; text-align: left;'>".$rs->fields['dataV1']."</td>\n
                                <td valign='middle' style='".$td_style." width:77mm;'>".$rs->fields['dataV11']."</td>\n
                                <td valign='middle' style='".$td_style." width:22mm;'>".$rs->fields['dataI3']."</td>\n
                                <td valign='middle' style='".$td_style." width:22mm;'>".$rs->fields['dataV2']."</td>\n";

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
