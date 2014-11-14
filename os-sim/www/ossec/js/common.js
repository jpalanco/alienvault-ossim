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


/********************************************************
***************** Abort AJAX Requests *******************
*********************************************************/

function Ajax_Requests(size)
{
    this.requests   = new Array(size);
    this.size       = size;
    this.position   = 0;

    this.abort = function(){

        for (var i=0; i<this.size; i++)
        {
            if (typeof(this.requests[i]) == 'object' && this.requests[i] != 'null')
            {
                this.requests[i].abort();
            }
        }

        this.requests = new Array(this.size);
        this.position = 0;
    };

    this.add_request = function(xhr){
        var position = this.position;
        this.requests[position] = xhr;
        this.position++;

        if ((this.position + 1) >= this.size)
        {
            this.position = 0;
        }
    };
}



/********************************************************
******************** Notifications **********************
*********************************************************/

function notify_error(txt)
{						
	var config_nt = { content: txt, 
					  options: {
						type:'nf_error',
						cancel_button: true
					  },
					  style: 'width: 80%; margin: auto; text-align:left; padding-left: 5px;'
					};
	
	var newDate = new Date;
	var id      = 'nt_' + newDate.getTime();
		
	var nt = new Notification(id, config_nt);
	
	return nt.show();
}


function notify_success(txt)
{							
	var config_nt = { content: txt, 
					  options: {
						type:'nf_success',
						cancel_button: true
					  },
					  style: 'width: 80%; margin: auto; text-align:center;'
					};
	
	var newDate = new Date;
	var id      = 'nt_' + newDate.getTime();
		
	var nt = new Notification(id, config_nt);
		
	return nt.show();
}


function notify_info(txt)
{							
	var config_nt = { content: txt, 
					  options: {
						type:'nf_info',
						cancel_button: true
					  },
					  style: 'width: 80%; margin: auto; text-align:center;'
					};
	
	var newDate = new Date;
	var id      = 'nt_' + newDate.getTime();
		
	var nt = new Notification(id, config_nt);
		
	return nt.show();
}


function notify_warning(txt)
{							
	var config_nt = { content: txt, 
					  options: {
						type:'nf_warning',
						cancel_button: true
					  },
					  style: 'width: 80%; margin: auto; text-align:center;'
					};
	
	var newDate = new Date;
	var id      = 'nt_' + newDate.getTime();
		
	var nt = new Notification(id, config_nt);
	
	return nt.show();
}

//Tabs
function show_tab_content(tab)
{	
	$("ul.oss_tabs li").removeClass("active"); //Remove any "active" class
	$(tab).addClass("active"); //Add "active" class to selected tab
	$(".tab_content").hide(); //Hide all tab content
	
	var activeTab = $(tab).find("a").attr("href"); //Find the rel attribute value to identify the active tab + content
	$(activeTab).show(); //Fade in the active content
	
	return false;
}


// Sensor (Select)

function hide_select()
{    
    if ($('#sensors').hasClass('s_show'))
    {
        $('.c_filter_and_actions').hide();
    }			
}


function show_select()
{    
    if ($('#sensors').hasClass('s_show'))
    {
        $('.c_filter_and_actions').show();
    } 
}