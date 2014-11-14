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


/**
* Function list:
* - incidents_by_status_table()
* - incidents_by_type_table()
* - incidents_by_user_table()
*/


set_time_limit(180);
require_once 'av_init.php';

Session::logcheck("analysis-menu", "IncidentsReport");

function incidents_table($tickets, $type) 
{		
	if ( count($tickets) > 0 ) 
	{
		?>	
		<table border="0" align="center" width="100%">
			<tr>
				<td class="noborder" style="width:40%;">
					<div style="overflow-y:auto; height:198px;width:100%;vertical-align:middle;">
                        <table class="table_border table_list" width="95%">
                            <tr>
                                <th><?php echo gettext("Ticket Status") ?></th>
                                <th><?php echo gettext("Ocurrences") ?></th>
                            </tr>
                            <?php
                            foreach($tickets as $status => $occurrences)
                            {
                                ?>
                                <tr>				
                                    <td class='td_data'>
                                        <span title="<?php echo $status ?>"><?php echo ( strlen($status) > 28 ) ? substr($status, 0, 25)." [...]" : $status;?></span>
                                    </td>
                                    <td class='td_data'><?php echo $occurrences ?></td>
                                </tr>
                                <?php
                            }
                            ?>
                        </table>
					</div>
				</td>
				<td class="noborder" style="width:59%;">
					<table align="center" width="100%">
						<tr>
							<td class="noborder">								
								<iframe src="../panel/tickets.php?type=<?php echo $type ?>&legend=e&height=215" frameborder="0" style="width:99%;height:215px;"></iframe>
							</td>
						</tr>
					</table>	
				</td>
			</tr>
		</table>	
		<?php	
	} 
	else 
	{
		?>
        <table align='center' width='100%'>
            <tr>
                <td style='border-bottom:none;' valign='middle'><?php echo _("No Data Available")?></td>
            </tr>
        </table>
        <?php
	}
}

$db   =  new ossim_db();
$conn =  $db->connect();

$user =  Session::get_session_user();


$tickets_by_status    = Incident::incidents_by_status($conn, null, $user);
$tickets_by_type      = Incident::incidents_by_type($conn, null, $user);
$tickets_by_user      = Incident::incidents_by_user($conn, true, null, $user); 
$tickets_by_tag       = Incident::incidents_by_tag($conn, null, $user); 

/*echo "<pre>";
	print_r($tickets_by_status);
echo "</pre>";*/

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<!--[if IE]><script language="javascript" type="text/javascript" src="../js/jqplot/excanvas.js"></script><![endif]-->
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<script type="text/javascript" src="../js/jquery.min.js"></script>
	<style type="text/css">
				
		body
		{
			margin: 10px auto;
		}
		
		.container_table 
		{ 
			border: none !important;
			height: 100%;
			margin: auto;
			text-align: center;
			background: transparent; 
			width: 820px;
		}	
		
		.td_container 
		{
			width: 270px;
			height: 100px;
			background-color: transparent;
			border: 1px solid #E4E4E4;
			border-collapse: collapse;
		}
		
		.container_table table 
		{
			border: none;
			border-collapse: collapse;
			background: transparent;
			margin: 10px auto;
		}
		
		.container_table th
		{					
			background: #D3D3D3;
			color: #555555;
			/*border: solid 1px #DFDFDF;*/
		}
						
		.headerpr
		{
    		border: none;
    		border-bottom: solid 1px #E4E4E4;
		}
		
		.container_table .td_data
		{		
			/*border: solid 1px #E4E4E4;*/
			padding: 3px 5px !important;
		}
		
		.table_border 
		{
    		/*border: 1px solid #E4E4E4 !important;*/
		}
		
		.table_list th
		{
    		height: auto;
    		/*border: solid 1px #DFDFDF;*/
    	}
		
	</style>
</head>
<body>

<table class='container_table' cellpadding='0' cellspacing='0'>
	<tr>
		<td align="left" style="padding-bottom:10px">
			<div class='c_back_button' style='display:block;'>
                <input type='button' class="av_b_back" onclick="document.location.href='../incidents/index.php';return false;"/>                    
            </div>
		</td>
   	</tr>
	
	<tr><td class="headerpr"><?php echo _("Tickets by status");?></td></tr>
	<tr><td class='td_container' style="border-top:none"><?php incidents_table($tickets_by_status, "ticketStatus");?></td></tr>	
	<tr><td height="20" class="noborder"></td></tr>	   
	
	<tr><td class="headerpr"><?php echo _("Tickets by user in charge");?></td></tr>
	<tr><td class='td_container' style="border-top:none"><?php incidents_table($tickets_by_user, "openedTicketsByUser");?></td></tr>
	<tr><td height="20" class="noborder"></td></tr>

	<tr><td class="headerpr"><?php echo _("Tickets by type"); ?></td></tr>
	<tr><td class='td_container' style="border-top:none"><?php incidents_table($tickets_by_type, "ticketTypes");?></td></tr>
	<tr><td height="20" class="noborder"></td></tr>
	
	<tr><td class="headerpr"><?php echo _("Tickets by tags"); ?></td></tr>
	<tr><td class='td_container' style="border-top:none"><?php incidents_table($tickets_by_tag, "ticketTags");?></td></tr>
		
	<tr><td  valign='top' class='noborder'>&nbsp;</td></tr>
</table>


<table class="container_table" cellpadding="0" cellspacing="0">
		
	<tr><td class="headerpr"><?php echo _("Closed Tickets by Month") ?></td></tr>		
	<tr>
		<td class="td_container" style="border-top:none"><iframe src="../panel/tickets.php?type=ticketsClosedByMonth" frameborder="0" style="width:96%;height:300px;margin-top:7px;"></iframe></td>
	</tr>
	<tr><td  valign='top' class='noborder'>&nbsp;</td></tr>
	
	<tr><td class="headerpr"><?php echo _("Tickets by Type per Month") ?></td></tr>		
	<tr>
		<td class="td_container" style="border-top:none"><iframe src="../panel/tickets.php?type=ticketsByTypePerMonth&height=280" frameborder="0" style="width:96%;height:300px;margin-top:7px;"></iframe></td>
	</tr>
	<tr><td  valign='top' class='noborder'>&nbsp;</td></tr>
	
	<tr><td class="headerpr"><?php echo _("Ticket Resolution Time"); ?></td></tr>
	
	<tr>
		<td class="td_container" style="border-top:none"><iframe src="../panel/tickets.php?type=ticketResolutionTime" frameborder="0" style="width:96%;height:300px;margin-top:7px;"></iframe></td>
	</tr>
</table>

<br/><br/>

</body>
</html>