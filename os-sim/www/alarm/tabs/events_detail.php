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

require_once ('av_init.php');

Session::logcheck("analysis-menu", "ControlPanelAlarms");

/****************/
$backlog_id = GET('backlog_id');
$event_id   = GET('event_id');
$show_all   = GET('show_all');
$hide       = GET('hide');
$box        = GET('box');
$from       = (GET('from') != "") ? GET('from') : 0;


ossim_valid($backlog_id, 	OSS_HEX, OSS_NULLABLE, 		'illegal:' . _("backlog_id"));
ossim_valid($event_id, 		OSS_HEX, OSS_NULLABLE, 		'illegal:' . _("Event_id"));
ossim_valid($show_all, 		OSS_DIGIT, OSS_NULLABLE, 	'illegal:' . _("Show_all"));
ossim_valid($hide, 			OSS_ALPHA, OSS_NULLABLE,    'illegal:' . _("Hide"));
ossim_valid($from, 			OSS_DIGIT, OSS_NULLABLE,    'illegal:' . _("From"));
ossim_valid($box, 			OSS_DIGIT, OSS_NULLABLE,    'illegal:' . _("From"));

if (ossim_error()) {
    die(ossim_error());
}

?>

<style type='text/css'>
	.loading_panel
	{
		border-radius: 5px;
	   -moz-border-radius: 5px;
	   -webkit-border-radius: 5px;
	   -khtml-border-radius: 5px;
		border: solid 5px #CCCCCC !important;
		width: 30%; 
		height: auto; 
		margin: 15px auto; 
		z-index: 200001; 
		background:#F2F2F2; 
		font-size: 11px; 
		color: #222222;
		text-align:center;
		padding: 15 10px;
	}
	
	.ui-widget 
	{
		font-family: Arial;
	}
		
	#tiptip_content 
	{
	   font-weight: normal;
    }
			
	.ajaxgreen #c_th_correlation
	{
        position: relative;
        padding: 0px 3px;
    }
    
    .ajaxgreen #c_th_correlation a, .ajaxgreen #c_th_correlation a:hover
    {
        text-decoration: none;                
    }
    
    .ajaxgreen #sort_asc
    {
        position: relative; 
        left: 0px;
        top: 1px;
    }
        
    .ajaxgreen #sort_desc
    {
        position: relative; 
        right: 0px;
        top: -7px;
    }
    
    .t_white
    {
		background: transparent;
		border: none;
	}
	
	.t_white td
	{
		color: white !important;
		text-align: left;
	}
	
</style>

<script>

	var loading = "	<div class='loading_panel' id='kdb_loading'>" +
						"<div style='padding: 10px; overflow: hidden;'>" +
							"<?php echo _("Loading event details") ?>  <img src='/ossim/pixmaps/loading3.gif' align='absmiddle'/>"+
						"</div>"+
					"</div>";
					
	
	function fill_table(backlog_id, event_id, show_all, hide, from, box)
	{	
		$.ajax({
			data:  {"backlog_id": backlog_id, "event_id": event_id, "show_all": show_all, "hide": hide, "from": from, "box": box},
			type: "POST",
			url: "alarm_events.php", 
			dataType: "html",
			beforeSend: function(){
					
					$('#event_detail').html(loading);
			},
			success: function(data){ 
			
				$('#event_detail').html(data);	
				
				$('.repinfo').tipTip({attribute:"txt", defaultPosition: "left"});
				
				$('.scriptinfo').tipTip({
				   defaultPosition: "top",
				   content: function(e) {
                      
                      var ip_data = $(this).attr('data-title');                    
                      
                      $.ajax({
                          url: 'alarm_netlookup.php?ip=' + ip_data,
                          success: function (response) {
                            e.content.html(response); // the var e is the callback function data (see above)
                          }
                      });
                      return '<?php echo _("Searching")."..."?>'; // We temporary show a Please wait text until the ajax success callback is called.
                   }
			    });
			    
			    $('.td_date').each(function(key, value) 
                {
                    var content = $(this).find('div').html();
                    
                    if (typeof(content) != 'undefined' && content != '' && content != null)
                    {                                                                   
                        $(this).tipTip({content: content, maxWidth:'300px'});                       
                    }    
                });	
                
                if (typeof(load_contextmenu) == 'function')
                {
                    load_contextmenu();
                }				

			},
			error: function(XMLHttpRequest, textStatus, errorThrown) 
				{
					//show_notification(textStatus, 'nf_error');
					//$('#newlinkname').html('');
				}
		});	
	}
	
	$(document).ready(function(){

		GB_TYPE = 'w';
		$(document).on("click", "a.greybox", function(){
			var t = this.title || '<?php echo _('Event Detail') ?>';
			GB_show(t,this.href,490,'90%');
			return false;
		});
		
		fill_table('<?php echo $backlog_id ?>', '<?php echo $event_id ?>', '<?php echo $show_all ?>', '<?php echo $hide ?>', '<?php echo $from ?>', '<?php echo $box ?>');
	});

</script>


<div id='event_detail' style='width:100%; margin:0 auto;'></div>
