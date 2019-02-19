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
require_once 'av_init.php';

Session::logcheck("configuration-menu", "Osvdb");

$type    = GET('type');
$keyname = GET('keyname');
$nosize  = (GET('nosize')) ? GET('nosize') : 0;


ossim_valid($type, 		OSS_INPUT,					'Illegal:' . _("Link Type"));
ossim_valid($keyname,	OSS_HEX,					'Illegal:' . _("Keyname"));
ossim_valid($nosize,	OSS_DIGIT,OSS_NULLABE,		'Illegal:' . _("nosize parameter"));


if (ossim_error()) 
{
    die(ossim_error());
}


$db              = new ossim_db();
$conn            = $db->connect();
$repository_list = Repository::get_repository_linked($conn, $keyname, $type);

$vars = array();
switch($type) 
{

	case 'host':
			
			try
			{
    			$host = Asset_host::get_object($conn, $keyname);
			}
			catch(Exception $e)
			{
    			$host = NULL;
			}
			
			if(is_object($host))
			{
    			$vars['_HOST_NAME'] = $host->get_name();
    			$vars['_HOST_IP']   = $host->get_ips();
    			$vars['_HOST_FQDN'] = $host->get_fqdns();
    			$vars['_HOST_DESC'] = $host->get_descr();	
			}
			
			break;
	
	case 'net':
			
			try
			{
    			$net = Asset_net::get_object($conn, $keyname);
			}
			catch(Exception $e)
			{
    			$net = NULL;
			}
			
			
			if(is_object($net))
			{
    			$vars['_NET_CIDR'] = $net->get_ips();
    			$vars['_NET_NAME'] = $net->get_name();    			
			}
			
			break;
			
	case 'host_group':

			$vars['_HG_NAME'] = Asset_group::get_name_by_id($conn, $keyname);
			
			break;
			
	case 'net_group':
			

            $vars['_NG_NAME'] = Net_group::get_name_by_id($conn, $keyname);
			
			break;

}

if (count($repository_list) > 0) 
{ 
	$parser = new KDB_Parser();
	$parser->load_session_vars($vars);
	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html>
	<head>
		<title> <?php echo gettext("OSSIM Framework"); ?> </title>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
		<meta http-equiv="Pragma" CONTENT="no-cache"/>
		
		<link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
		<link rel="stylesheet" type="text/css" href="/ossim/style/jquery-ui.css"/>

		<script type="text/javascript" src="/ossim/js/jquery.min.js"></script>
		<script type="text/javascript" src="/ossim/js/jquery-ui.min.js"></script>
		
		<style type='text/css'>
		
			body { margin: 0px;}
			
			div.accordion .ui-widget-content, div.accordion .ui-state-default, div.accordion .ui-state-active {
				border: none;
			}
						
			div.accordion .ui-state-default {
				border: none;
				background: #E4E4E4;
			}
			
			div.accordion .ui-state-active {
				border: none;
				background: #E4E4E4;
			}
			
			#container {
				padding: 10px 10px;
				margin:0 auto;
			}
			
			.text_container {
				padding:5px;	
				font-size: 11px;
			}  
			
			.text_sumary {
			
				padding: 25px 5px 15px 5px;
			
			}
			
			.legend {
				text-align:left;
				width:100px;
			}
			
			.txt {
				font-weight: bold;
				text-align:left;
			}
			
		</style>
		
		<script>
		
			$(document).ready(function() 
			{
				$(".accordion").accordion(
				{
					collapsible: true,
					autoHeight: false
				});
				
			});
		
		</script>
	
		
	</head>

	<body>
		<div id='container'>
			<div class="accordion">
			<?php
			foreach($repository_list as $doc) 
			{ 
			?>
				
				<h3><a href='#'><?php echo $doc->get_title() ?></a></h3>

				<div>
				
					<div class='text_container'>
					<?php
						$parser->proccess_file($doc->get_text(FALSE));
						
						echo $parser->print_text();
					?>
					</div>
					
					<div class='text_sumary'>
						<strong><?php echo _('Document Summary') ?></strong>
						<br>
						<table style='margin-top:3px;width:90%;'>
							<tr>
								<td class='legend'>
									<?php echo _('Document') . ':' ?> 
								</td>
								<td class='txt'>
            						<a href="./repository_document.php?go_back=1&id_document=<?php echo $doc->get_id() ?>">
                						<?php echo $doc->get_title() ?>
            						</a>
								</td>
							</tr>
							<tr>
								<td class='legend'>
									<?php echo _('Visibility') . ':' ?> 
								</td>
								<td class='txt'>
									<?php echo $doc->get_visibility() ?>
								</td>
							</tr>
							<tr>
								<td class='legend'>
									<?php echo _('Date') . ':' ?> 
								</td>
								<td class='txt'>
									<?php echo $doc->get_date() ?>
								</td>
							</tr>
							<tr>
								<td class='legend'>
									<?php echo _('Attachements') . ':' ?>  
								</td>
								<td class='txt'>
									<?php 
									$num_atach = count($doc->get_attach());
									if ($num_atach) 
									{
									?>
									
										(<?php echo $num_atach ?>)
										<a href="./repository_document.php?go_back=1&id_document=<?php echo $doc->get_id() ?>">
    										<img src="images/attach.gif" alt="" border="0" align='absmiddle'/>
										</a>
									
									<?php 
									} 
									else 
									{
									?>
										-
									<?php 
									} 
									?>
								</td>
							</tr>
						</table>
					</div>
					
					
				</div>
			<?php
			}	
			?>
			</div>
	
		</div>
	</body>
</html>

<?php
}
$db->close();
?>
