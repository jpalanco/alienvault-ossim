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

Session::logcheck('configuration-menu', 'ComplianceMapping');


function get_sids ($sids) 
{
	$sids_aux  = explode(",",$sids);
	$sids_keys = array();
	
	foreach ($sids_aux as $sid) 
	{
        $sids_keys[$sid] = true;
	}
	
	return $sids_keys;
}

$ref         = explode ("_", GET('ref'));
$version     = intval(GET('pci_version'));
$compliance  = GET('compliance');


ossim_valid($ref[0],        OSS_DIGIT, OSS_ALPHA, OSS_DOT, ',',     'illegal:' . _("Rule Table"));
ossim_valid($ref[1],        OSS_DIGIT, OSS_ALPHA, OSS_DOT, ',',     'illegal:' . _("Rule Reference"));
ossim_valid($compliance,    OSS_ALPHA, OSS_NULLABLE,                'illegal:' . _("Compliance"));

if (ossim_error()) 
{
	die(ossim_error());
}

$db   = new ossim_db();
$conn = $db->connect();


switch($compliance)
{
	case "PCI":
	    Compliance_pci::set_pci_version($version);
		$groups = Compliance_pci::get_groups($conn);
		break;
	case "ISO27001":
		$groups = Compliance_iso27001::get_groups($conn);
		break;
}

$sids       = $groups[$ref[0]]['subgroups'][$ref[1]]['SIDSS_Ref'];
$title      = $groups[$ref[0]]['subgroups'][$ref[1]]['Security_controls'];
$sids_keys  = get_sids ($sids);
$directives = Plugin_sid::get_list($conn,"WHERE plugin_id=1505 ORDER BY plugin_sid.name");

$db->close();


?>
<html>
<head>
	<title> <?php echo _('AlienVault ' . (Session::is_pro() ? 'USM' : 'OSSIM')); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	
	<?php

        //CSS Files
        $_files = array(
            array('src' => 'av_common.css',                 'def_path' => TRUE),
            array('src' => 'jquery-ui.css',                 'def_path' => TRUE),
            array('src' => 'ui.multiselect.css',            'def_path' => TRUE)
        );
        
        Util::print_include_files($_files, 'css');


        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',                   'def_path' => TRUE),
            array('src' => 'jquery-ui.min.js',                'def_path' => TRUE),
            array('src' => 'utils.js',                        'def_path' => TRUE),
            array('src' => 'notification.js',                 'def_path' => TRUE),
            array('src' => 'token.js',                        'def_path' => TRUE),
            array('src' => 'jquery.tmpl.1.1.1.js',            'def_path' => TRUE),
            array('src' => 'ui.multiselect_report.js',        'def_path' => TRUE)
        );
        
        Util::print_include_files($_files, 'js');

    ?>
    	
	<style type='text/css'>		
		ul
        {
            margin: 5px 10px 7px 25px;   
        }
        
               
        #rule_title
        {
            background: #EFEFEF;
            margin: 0 auto;
            padding: 7px 5px;
            line-height: 16px;
        }
        
        #rule_sids
        {
            margin: 20px;
            position: relative;
        }
        
        
        #rule_actions
        {
            margin: 25px auto;
            text-align: center;
        }
        
        #new_sids
        {
            width:100%;
            height:400px;
        }
        
        #compliance_sid_notif
        {
            margin: 10px auto;
        }
        
	</style>
	

	<script type='text/javascript'>
		$(document).ready(function()
		{
    		$("#new_sids").multiselect(
    		{
				dividerLocation: 0.5,
				searchable: true,
			});
			
			$('#c_cancel').on('click', function()
			{
    			parent.GB_hide();
			});
			
			$('#c_save').on('click', function()
			{
    			var data           = {}
    			data['ref0']       = "<?php echo $ref[0] ?>";
    			data['ref1']       = "<?php echo $ref[1] ?>";
    			data['compliance'] = "<?php echo $compliance ?>";
    			data['version']    = "<?php echo $version ?>";
    			data['sids']       = $('#new_sids').val() ? $('#new_sids').val() : [];
    			                
                var ctoken = Token.get_token("compliance");
                $.ajax(
                {
                	url: "/ossim/compliance/compliance_ajax.php?token="+ctoken,
                	data: {"action": "modify_sids", "data": data},
                	type: "POST",
                	dataType: "json",
                	success: function(data)
                	{
                        if (data.error)
                        {
                            show_notification('compliance_sid_notif', data.msg, 'nf_error', 10000, true);
                            
                            return false;
                        }
                        
                        parent.GB_close();
            
                	},
                	error: function(XMLHttpRequest, textStatus, errorThrown) 
                	{	
                        //Checking expired session
                		var session = new Session(XMLHttpRequest, '');
                        if (session.check_session_expired() == true)
                        {
                            session.redirect();
                            return;
                        }
                        
                        show_notification('compliance_sid_notif', "<?php echo _('Sorry, operation was not completed due to an error when processing the request') ?>", 'nf_error', 10000, true);
                        
                	}
                });

			});
			
		});
	</script>

	
</head>
<body>
    
    <div id = 'compliance_sid_notif'></div>
    <div id = 'rule_title'>
        <?php echo $title ?>
    </div>
    <div id='rule_sids'>
        <select id='new_sids' name="newsid[]" class="multiselect" multiple="multiple">
        <?php
        foreach ($directives as $d) 
        {
            $sel = '';
            
        	if ($sids_keys[$d->get_sid()]) 
        	{
        		$sel = 'selected';
        	}
            
        	echo '<option value='. $d->get_sid() . ' '. $sel .'>'. $d->get_name() .' ['. $d->get_sid() .']</option>';
        }   
        ?>
        </select>
    </div>
    
    <div id='rule_actions'>
        <button id='c_cancel' class='av_b_secondary'><?php echo _('Cancel') ?></button>
        <button id='c_save'><?php echo _('Save') ?></button>
    </div>

</body>
</html>

