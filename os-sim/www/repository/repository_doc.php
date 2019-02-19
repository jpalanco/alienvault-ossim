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

$sintax = new KDB_Sintax();

$labels_condition = $sintax->_labels_condition;
$labels_actions   = $sintax->_labels_actions;
$labels_operators = $sintax->_labels_operators;
$labels_variables = $sintax->_labels_variables;
$labels_sections  = $sintax->_labels_sections;


$title_desc       = _('Description');
$title_example    = _('Example');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title> <?php echo gettext("OSSIM Framework"); ?> </title>
	
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	
	<link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	<link rel="stylesheet" type="text/css" href="/ossim/style/jquery-ui.css"/>



	<script type="text/javascript" src="/ossim/js/jquery.min.js"></script>
	<script type="text/javascript" src="/ossim/js/jquery-ui.min.js"></script>

	<style>
	
    	body
    	{
        	min-height: 500px;
    	}
						
		div.help_title
		{
			padding: 10px;
			font-weight: bold;
			clear: both;
		}
		
		div.help_container
		{
			padding: 10px 10px 10px 30px;
			clear: both;
		}
		
	</style>
	
	<script>
	
	
	$(document).ready(function() 
	{
	    $( ".accordion" ).accordion(
		{
			collapsible: true,
			active:false
		
		});
		
		$( "#tabs" ).tabs();
		
	});
	</script>
	
</head>

<body>
	
	<div id="tabs">
	
		<ul>
			<li><a href="#tabs-1"><?php echo _('Conditions') ?></a></li>
			<li><a href="#tabs-2"><?php echo _('Actions') ?></a></li>
			<li><a href="#tabs-3"><?php echo _('Operators') ?></a></li>
			<li><a href="#tabs-4"><?php echo _('Variables') ?></a></li>
			<li><a href="#tabs-5"><?php echo _('Sections') ?></a></li>
		</ul>
		
		
		<div id="tabs-1">

			<div class="accordion">
				
				<?php 
				$labels_condition = (is_array($labels_condition))? $labels_condition : array();
				
				foreach($labels_condition as $ins => $label)
				{
					echo "<h3><a href='#'>".Util::htmlentities($ins)."</a></h3>";
					echo "<div>";
					
					//Description
					echo "<div class='help_title'>$title_desc</div>";
					echo "<div class='help_container'>".$label['help']."</div>";
					
					//Example
					echo "<div class='help_title'>$title_example</div>";
					echo "<div class='help_container'>".$label['sample']."</div>";
					

					echo "</div>";
				
				}
				?>
				
			</div>
					
		</div>
		
		
		<div id="tabs-2">

			<div class="accordion">
				
				<?php 
				$labels_actions = (is_array($labels_actions))? $labels_actions : array();
				
				foreach($labels_actions as $ins => $label)
				{
					echo "<h3><a href='#'>".Util::htmlentities($ins)."</a></h3>";
					echo "<div>";
					
					//Description
					echo "<div class='help_title'>$title_desc</div>";
					echo "<div class='help_container'>".$label['help']."</div>";
					
					//Example
					echo "<div class='help_title'>$title_example</div>";
					echo "<div class='help_container'>".$label['sample']."</div>";
					

					echo "</div>";
				
				}
				?>
				
			</div>
			
		
		</div>
		
		
		<div id="tabs-3">

			<div class="accordion">
				
				<?php 
				$labels_operators = (is_array($labels_operators))? $labels_operators : array();
				
				foreach($labels_operators as $ins => $label)
				{
					echo "<h3><a href='#'>".Util::htmlentities($ins)."</a></h3>";
					echo "<div>";
					
					//Description
					echo "<div class='help_title'>$title_desc</div>";
					echo "<div class='help_container'>".$label['help']."</div>";
					
					//Example
					echo "<div class='help_title'>$title_example</div>";
					echo "<div class='help_container'>".$label['sample']."</div>";
					

					echo "</div>";
				
				}
				?>
				
			</div>
		
		</div>
		
		
		<div id="tabs-4">

			<div class="accordion">
				
				<?php 
				$labels_variables = (is_array($labels_variables))? $labels_variables : array();
				
				foreach($labels_variables as $ins => $label)
				{
					echo "<h3><a href='#'>".Util::htmlentities($ins)."</a></h3>";
					echo "<div>";
					
					//Description
					echo "<div class='help_title'>$title_desc</div>";
					echo "<div class='help_container'>".$label['help']."</div>";
					
					//Example
					echo "<div class='help_title'>$title_example</div>";
					echo "<div class='help_container'>".$label['sample']."</div>";
					

					echo "</div>";
				
				}
				?>
				
			</div>
	
		</div>
		
		<div id="tabs-5">

			<div class="accordion">
				
				<?php 
				$labels_sections = (is_array($labels_sections))? $labels_sections : array();
				
				foreach($labels_sections as $ins => $label)
				{
					echo "<h3><a href='#'>".Util::htmlentities($ins)."</a></h3>";
					echo "<div>";
					
					//Description
					echo "<div class='help_title'>$title_desc</div>";
					echo "<div class='help_container'>".$label['help']."</div>";
					
					//Example
					echo "<div class='help_title'>$title_example</div>";
					echo "<div class='help_container'>".$label['sample']."</div>";
					

					echo "</div>";
				
				}
				?>
				
			</div>
	
		</div>
		
	</div>

</body>

</html>


