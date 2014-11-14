<?php
header("Content-type: text/javascript");

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
?>

function show_error(error)
{            
    $('#send').removeAttr('disabled');	
    $('#send').off('click');
    $('#send').click(function(){upload_file();});
    
    $('.av_w_overlay').remove(); 
    $('.l_box').remove();               
    
    var txt_error = "<div><?php echo _('We Found the following errors')?>:</div>" +
                    "<div style='padding:0px 15px;'><div class='sep'>"+ error +"</div></div>";
                
    var config_nt = { content: txt_error, 
                      options: {
                        type:'nf_error',
                        cancel_button: false
                      },
                      style: 'width: 90%; margin: 10px auto;'
                    };
    
    nt            = new Notification('nt_1', config_nt);
    notification  = nt.show();
    
    $('#av_info').html(notification);
    
    parent.window.scrollTo(0, 0);
} 


function upload_file()
{			
    $('#av_info').html('');
    $('#c_resume').html('');                
    ;
	$('#send').attr('disabled', 'disabled');
    $('#send').off('click');		
	
	show_loading_box('container', '<?php echo _('Uploading file, please wait')?> ...', '');		
			
	parent.window.scrollTo(0,0);			
	
	$("#form_csv").submit();		
}


function import_assets_csv(import_type)
{        
    if (import_type == 'networks' || import_type == 'welcome_wizard_nets')
    {
        var a_url = 'import_all_nets.php';
        var a_msg = '<?php echo _('Importing networks from CSV ...  <br/>This process can take several minutes, please wait')?> ...';
    }
    else if (import_type == 'hosts' || import_type == 'welcome_wizard_hosts')
    {
        var a_url = 'import_all_hosts.php';
        var a_msg = '<?php echo _('Importing hosts from CSV ...  <br/>This process can take several minutes, please wait')?> ...';
    }
    else
    {
        var error_msg = '<?php echo _('Error! Import Type not found')?>';
    
        show_error(error_msg);
        
        return false;
    }       
    
    
    $.ajax({
        type: "POST",
        url: a_url,
        cache: false,
        data: "import_assets=1&"+$('#form_csv').serialize(),
        dataType: 'json',
        beforeSend: function(xhr){                
                               
            show_loading_box('container', a_msg, '');                             
        },
        error: function (data){
            $('#send').removeAttr('disabled');           
            $('#send').off('click');
            $('#send').click(function(){upload_file();});
            hide_loading_box();
        },
        success: function(data){                    
                   
            $('#send').removeAttr('disabled');
            $('#send').off('click');
            $('#send').click(function(){
                upload_file();
            });
                                
            var cnd_1 = (typeof(data) == 'undefined' || data == null);
            var cnd_2 = (typeof(data) != 'undefined' && data != null && data.status != 'OK');	
                            
            if (cnd_1 || cnd_2)
            {
                hide_loading_box();                        
                
                var error_msg = (cnd_1 == true) ? av_messages['unknown_error'] : data.data;                            
                
                var config_nt = { content: data.data, 
                                  options: {
                                    type:'nf_error',
                                    cancel_button: false
                                  },
                                  style: 'width: 90%; margin: 10px auto;'
                                };

                nt            = new Notification('nt_1', config_nt);
                notification  = nt.show();
                
                $('#av_info').html(notification);
           }
           else
           {
                draw_import_summary(import_type, data.data);                  
           }                              
        }
    });
}


function draw_import_summary(import_type, summary_data)
{    
    var sm_data = {
        "import_type"  : import_type,
        "summary_data" : summary_data  
    };
    
    
    $.ajax({
        type: "POST",
        url: "draw_import_summary.php",
        cache: false,
        data: sm_data,        
        beforeSend: function(xhr){            
            
            var is_msg = '<?php echo _('Drawing summary ...')?>';                   
                               
            show_loading_box('container', is_msg, '');                             
        },
        error: function (data){
           
            hide_loading_box();
            
            //Check expired session
			var session = new Session(data, '');
											
			if (session.check_session_expired() == true)
			{
				session.redirect();
				return;
			}
			
			var is_msg = '<?php echo _('Error! Unable to get summary.  Please try again')?>'; 
			
			show_notification(is_msg, 'av_info', 'nf_error', 'padding: 3px; width: 90%; margin: auto; text-align: center;');
        },
        success: function(data){                    
                                 
            //Check expired session
			var session = new Session(data, '');
											
			if (session.check_session_expired() == true)
			{
				session.redirect();
				return;
			}
                       
            $('#sm_container').html(data);
                                                 
                       
            var dt = $('#t_sm_container').dataTable({                
                "iDisplayLength": 10,
                "bLengthChange": true,
                "sPaginationType": "full_numbers",
                "bFilter": false,
                "bJQueryUI": true,
                "bSort": true,
                "aaSorting": [[ 0, "asc" ]],
                "aoColumns": [
                    { "bSortable": false, "sClass": "center" },
                    { "bSortable": true, "sClass": "center" },                   
                    { "bSortable": false,"sClass": "center" }                         
                ],
                oLanguage : 
                {
                    "sProcessing": "&nbsp;<?php echo _('Loading results') ?> <img src='/ossim/pixmaps/loading3.gif' align='absmiddle'/>",
                    "sLengthMenu": "&nbsp;Show _MENU_ entries",
                    "sZeroRecords": "&nbsp;<?php echo _('No matching records found') ?>",
                    "sEmptyTable": "&nbsp;<?php echo _('No results found in the system') ?>",
                    "sLoadingRecords": "&nbsp;<?php echo _('Loading') ?>...",
                    "sInfo": "&nbsp;<?php echo _('Showing _START_ to _END_ of _TOTAL_ assets') ?>",
                    "sInfoEmpty": "&nbsp;<?php echo _('Showing 0 to 0 of 0 assets') ?>",
                    "sInfoFiltered": "(<?php echo _('filtered from _MAX_ total assets') ?>)",
                    "sInfoPostFix": "",
                    "sInfoThousands": ",",
                    "sSearch": "<?php echo _('Search') ?>:",
                    "sUrl": "",
                    "oPaginate": 
                    {
                        "sFirst":    "<?php echo _('First') ?>",
                        "sPrevious": "<?php echo _('Previous') ?>",
                        "sNext":     "<?php echo _('Next') ?>",
                        "sLast":     "<?php echo _('Last') ?>"
                    }
                },                
                "fnDrawCallback": function(oSettings) {
                         
                    var title = "<div class='dt_title'><?php echo _('Import Summary') ?></div>";
        			$('div.dt_header').prepend(title); 
        			     			        				
        			if ($('#t_sm_container .dataTables_empty').length == 0)
        			{
        				$('#t_sm_container tbody tr .td_details img').each(function(index){				
            				
            				//Handler
            				$(this).click(function(){
                		      				
                                var nTr = this.parentNode.parentNode;
                                
                                if ($(this).hasClass('show'))
                                {
                                    //Close details
                                    $(this).removeClass('show').addClass('hide')                           			
                                    dt.fnClose(nTr);
                                }
                                else
                                {
                                    /* Open this row */ 
                                    $(this).removeClass('hide').addClass('show')
                                    
                                                                            
                                    var details = $(this).next('div').html();                                    
                                    var status  = $(this).parents('tr').find('.td_status span').text();  
                                    
                                    
                                    if (status  == 'Warning')
                                    {
                                        var tt_class = 'tt_warning';
                                        var hd_class = 'asset_details_w';
                                    }
                                    else
                                    {
                                        var tt_class = 'tt_error';
                                        var hd_class = 'asset_details_e';
                                    }
                                    
                                                                                                                      			
                                    var html_details = '<div class="tray_container">' +
                                       '<div class="tray_triangle ' + tt_class +'"></div>' +
                                            details +               
                                       '</div>';           			
                                                                     
                                                                                                                              			
                                    dt.fnOpen(nTr, html_details, hd_class);
                                }                  				            				
            				});            				                								
            			});	
        			}                                     		    
        		}                
            });
            
            
            //Display summary and hide main container
            $('#container').hide();
            $('#sm_container').show();
            
            //Hide loading box
            hide_loading_box();                                
            
            //Bind handlers
            $('#new_importation').off('click');
            $('#new_importation').click(function(){                        
                $('#container').show();
                $('#sm_container').empty();
                $('#form_csv').each(function(){
                    this.reset();
                });
            });                                                      
        }
    });
}
      

function bind_import_actions()
{
    $('#send').click(function(){
        upload_file();
    });
    
    <?php 
	if (Session::show_entities()) 
	{ 
		?>
		//Entities tree
		$("#tree").dynatree({
			initAjax: { url: "../tree.php?key=contexts" },
			clickFolderMode: 2,
			onActivate: function(dtnode) {
				var key = dtnode.data.key.replace(/e_/, "");
				
				if (key != "") 
				{
					$('#ctx').val(key);
					$('#entity_selected').html(dtnode.data.val);
				}
			},
			onDeactivate: function(dtnode) {}
		});
		<?php 
	} 
	?>	
}

 