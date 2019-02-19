<?php
class ScheduleStrategyHelper implements ScheduleStrategyInterface {
	public function init() {}

	protected function loadData() {
		$this->schedule->load_sensors();
		$this->schedule->load_profiles();
		$this->schedule->load_users();
		$this->schedule->load_entities();
		$this->schedule->loadSshCredentials();
		$this->schedule->loadSmbCredentials();
	}

	protected function redirect() {
		$url = Menu::get_menu_url(AV_MAIN_PATH . '/vulnmeter/manage_jobs.php', 'environment', 'vulnerabilities', 'scan_jobs');
		header("Location: $url");
		die();
	}

	public function persetDefaults() {
	}
	public function execute() {
	}
	
	public function show() {
		if (!isset($this->schedule->parameters["exclude_ports"])) {
			$this->schedule->parameters["exclude_ports"]="";
		}
		$daysMap		= dates::$daysMap;
		$nweekday		= dates::$nweekday;
		$hours		= dates::getHours();
		$minutes		= dates::getMinutes();
		$years		= dates::getYears($this->schedule->parameters["biyear"]);
		$months		= dates::getMonths();
		$days		= dates::getDays();
		$frequencies	= dates::getFrequencies();
		//DO NOT MODIFY ARRAY KEYS
		$scan_locally_checked    = $this->schedule->parameters["scan_locally"] == 1 ? 'checked="checked"' : '';
		$resolve_names_checked   = ($this->schedule->parameters["not_resolve"]  == 1) ? 'checked="checked"' : '';
		$hosts_alive_data = $this->schedule->get_host_alive_attributes();
		$email_notification = array(
				'no'  => ($this->schedule->parameters["send_email"] == 0) ? 'checked="checked"' : '',
				'yes' => ($this->schedule->parameters["send_email"] == 1) ? 'checked="checked"' : ''
		);
		?>
	
			<script type="text/javascript">
			var _excluding_ip = /<?php echo EXCLUDING_IP; ?>/;
			$(document).ready(function() {
				display_smethod_div();
				$('#scheduleM').change(function() {
		            display_smethod_div();
				});
				
			
		        $('#scan_locally').on('click', function()
		        {   
		            if ($('#scan_locally').is(':checked') == false)
		            {
		                $('#v_info').hide();
		            }
		        });
		        <?php $this->injectJS(); ?>
				// Confirm new job with a lot of targets
				$('#mjob').on("click", function(event){
					var totalhosts = $('#thosts').text();
					if ((totalhosts > 255 && $("#hosts_alive").is(":checked")) || totalhosts > <?php echo Filter_list::MAX_VULNS_ITEMS?>) {
						if (totalhosts > <?php echo Filter_list::MAX_VULNS_ITEMS?>) {
							var msg_confirm = '<?php echo sprintf(
								_('You are about to scan a large network. AlienVault can only scan %s at a time. This scan will be broken up into #JOBS# jobs. Once the scan is complete, you will see #JOBS# reports listed in the table.')
								,Filter_list::MAX_VULNS_ITEMS); ?>';
						} else {
							var msg_confirm = '<?php echo Util::js_entities(_("You are about to scan a big number of hosts (#HOSTS# hosts). This scan could take a long time depending on your network and the number of assets that are up, are you sure you want to continue?"))?>';
						}
						var jobs = Math.ceil(totalhosts/<?php echo Filter_list::MAX_VULNS_ITEMS?>);
						msg_confirm = msg_confirm.replace("#HOSTS#", totalhosts).replace(/#JOBS#/g, jobs);
						var keys        = {"yes": "<?php echo _('Yes') ?>","no": "<?php echo _('No') ?>"};
						av_confirm(msg_confirm, keys).fail(function(){
							return false; 
						}).done(function(){
							launch_scan(); 
						});
					} else {
						launch_scan();
					}
					});
							
			$('.section').click(function() { 
				var id = $(this).find('img').attr('id');
				
				toggle_section(id);
			});
                $('#close_button').click(function(event)
                {
                    event.preventDefault();
                    close_window(false);
                });

	        $('#SVRid').change(function() {
	            simulation();
	        });
	        
	        $('#scan_locally').click(function() { 
	            simulation();
			});
	        $('#not_resolve').click(function() {
	            simulation();
			});
	        
	    	<?php if($this->schedule->getTargets()) { ?> 
				simulation();
			<?php } ?>
	        
	        $('#ssh_credential, #smb_credential').change(function() {
	
	            var select_name = $(this).attr('name');
	
	            switch_credentials(select_name);
			});
	
	
	
		});

                function close_window(usehide)
                {
                    if (usehide && typeof parent.GB_hide == 'function')
                    {
                        <?php
                            if ($this->schedule->parameters["schedule_type"] == 'N')
                            {
                                $message = sprintf(_('Vulnerability scan in progress for (%s) assets'), $total_assets);
                            }
                            else
                            {
                                $message = sprintf(_('Vulnerability scan has been scheduled on (%s) assets'), $total_assets);
                            }
                        ?>
                        
                        top.frames['main'].show_notification('asset_notif', "<?php echo Util::js_entities($message) ?>", 'nf_success', 15000, true);
                        parent.GB_hide();
                    }
        
                    if (!usehide && typeof parent.GB_close == 'function')
                    {
                        parent.GB_close();
                    }
        
                    return false;
                }

		function toggle_section(id){
			var section_id = id.replace('_arrow', '');
			var section    = '.'+section_id;
	
			if ($(section).is(':visible')){ 
				$('#'+id).attr('src','../pixmaps/arrow_green.gif'); 
			}
			else{ 
				$('#'+id).attr('src','../pixmaps/arrow_green_down.gif');
			}
			$(section).toggle();
		}
		
	
		function switch_user(select) {
			if(select=='entity' && $('#entity').val()!=''){
				$('#user').val('');
			}
			else if (select=='user' && $('#user').val()!=''){
				$('#entity').val('');
			}
		}
	
	    function enable_button() 
	    {            
	        $("#mjob").removeAttr("disabled");
	    }
	
	    function toggle_scan_locally(simulate){
	        if($("#hosts_alive").is(":checked")) {
	            $("#scan_locally").removeAttr("disabled");
	        }
	        else {
	            if($("#scan_locally").is(":checked")) {
	                $('#scan_locally').trigger('click');
	            }
	
	            $("#scan_locally").attr("disabled","disabled");
	        }
	        if (simulate == true)
	        {
	            simulation();
	        }
	    }
	    
	    var flag = 0;
	    
		function simulation() {
		    $('#v_info').hide();
		
		    $('#sresult').html('');
	        
	        selectall('targets');
	        
	        var stargets = getselectedcombovalue('targets');
	        
	        var num_targets = 0;
	        
	        for (var i=0; i<stargets.length; i++)
	        {   
	            if (stargets[i].match( _excluding_ip ) == null)
	            {       
	                num_targets++;  
	            }
	        }
	
		   if( !flag  && num_targets > 0 ) {
	            if (num_targets > 0) 
				{
	                var targets = $('#targets').val().join(',');
	                disable_button();
	                $('#loading').show();
	                flag = 1;
	                $.ajax({
	                    type: "POST",
	                    url: "simulate.php",
	                    data: { 
	                        hosts_alive: $('input[name=hosts_alive]').is(':checked') ? 1 : 0,
	                        scan_locally: $('input[name=scan_locally]').is(':checked') ? 1 : 0,
	                        not_resolve: $('input[name=not_resolve]').is(':checked') ? 1 : 0,
	                        scan_server: $('select[name=SVRid]').val(),
	                        <?php if ($this->type == "group") { ?>
	                        scan_type: 'adhoc',
	                        <?php } ?>
	                        targets: targets
	                    },
	                    success: function(msg) {     
	                        $('#loading').hide();
	                        var data = msg.split("|");
	                        $('#sresult').html("");
	                        $('#sresult').html(data[0]);
	                        $('#sresult').show();
	                        var vttp = $('.vuln_tooltip');
	                        if (vttp.length) {
	                        	vttp.tipTip({
	                           		'tipclass'  : 'av_red_tip',
	                            	'attribute' : 'data-title',
	                            	'maxWidth'  : "500px",
	                        	});
	                        }
	                        if(data[1]=="1") {
	                            enable_button();
	                        }
	                        
	                        // If any sensor is remote the "pre-scan locally" should be unchecked 
	                        
	                        if ($('#scan_locally').is(':checked') && typeof(data[3]) != 'undefined' && data[3] == 'remote')
	                        {                            
	                            $('#v_info').show();
	                            
	                            show_notification('v_info', "<?php echo _("'Pre-Scan locally' option should be disabled, at least one sensor is external.")?>" , 'nf_info', false, true, 'padding: 3px; width: <?php echo $this->nt_margin ?>%; margin: 12px auto 12px auto; text-align: center;');
	                        }
	                        
	                        //var h = document.body.scrollHeight || 1000000;window.scrollTo(0,document.body.scrollHeight);
	                        //window.scrollTo(0,h);
	                        flag = 0;
	                    },
	                    error: function (request, status, error) {
	                        flag = 0;
	                    }
	                });
	            }
				else {
	                alert("<?php echo Util::js_entities(_("At least one target needed!"))?>");
	            }
	        }
		}
	    
		function disable_button() 
		{                       
			$("#mjob").attr("disabled","disabled");
	    }
	    
	    function display_smethod_div()
	    {
	        var type = $('#scheduleM').attr('value');
		    var ids = {
		    	"N"	:1,
		    	"O"	:3,
		    	"D"	:2,
		    	"W"	:4,
		    	"M"	:5,
		    	"NW":6
		    };
			var id = ids[type];
			
		    if(id==1) {
				$("#smethodtr").hide();
			}
			else {
				$("#smethodtr").show();
			}
			showLayer('idSched', id);
	    }
	
	    function switch_credentials(select_name)
	    {
	        var boxes = {
	        	"smb_credential": $('#smb_credential'),
	        	"ssh_credential": $('#ssh_credential')
	        };
	        var box = boxes[select_name];
	        var unbox = select_name == 'ssh_credential' ? boxes.smb_credential : boxes.ssh_credential;
	        if (box.val() != '') {
	        	unbox.val('').prop('disabled', true);
	            box.prop('disabled', false);
	        } else {
	        	unbox.prop('disabled', false);
	        }
	    }
	
	    function launch_scan() {
	    	$("#hosts_alive").attr('disabled', false);
	    	$('#msgform').submit();
		}
		</script>
		
		<style type='text/css'>
	
	
			#user,#entity { width: 220px;}        
	        
	        #SVRid {
	        	width:212px;
	        }
	        
	        .c_back_button {
	            display: block;
	            top:10px;
	            left:10px;
	        }
	       
	        .greyfont{
	            color: #666666;
	        }
	        #title
	        {
	            margin: 10px auto 0px auto;
	        }
	        #main_table{
	            margin:0px auto;
	        }
	        
	        #targets {
	            width:300px;
	            height:200px;
	        }
			#searchBox {
				width:298px;
			}
			.advanced {display: none;}
			<?php $this->injectCSS(); ?>
	
		</style>
	</head>
			<body>
			<div id='v_info'></div>
		<?php
			if (!empty($this->schedule->getErrors())) {
				$config_nt = array(
						'content' => implode('<br/>', $this->schedule->getErrors()),
						'options' => array (
								'type'          => 'nf_error',
								'cancel_button' => TRUE),
						'style'   =>  "width: {$this->nt_margin}%; margin: 20px auto; text-align: center;"
				);
				$nt = new Notification('nt_2', $config_nt);
				$nt->show();
			}
		?>
	
	    <form method="post" action="<?php echo $this->action?>" name="msgform" id='msgform'>
	        <input type="hidden" name="action" value="save_scan">
	        <input type="hidden" name="sched_id" value="<?php echo $this->schedule->parameters["sched_id"]?>">
	        <table id="title" class="transparent" width="<? echo $this->nt_margin?>%" cellspacing="0" cellpadding="0">
	            <tr>
	                <td class="headerpr_no_bborder">
	                	<?php if ($this->type == "single") { ?>
	                    <div class="c_back_button">
	                        <input type="button" class="av_b_back" onclick="document.location.href='manage_jobs.php';return false;">
	                    </div>
	                    <?php 
						}
	                    echo _('Create Scan Job');
	                    ?>
	                </td>
	            </tr>
	        </table>
	        <table id="main_table" width="<? echo $this->nt_margin?>%" cellspacing="4" class="main_tables">
	            <tr>
	                <td width="25%" class='job_option'> <?php echo _('Job Name:') ?></td>
	                <td style="text-align:left;"><input type="text" name="job_name" id="job_name" value="<?php echo $this->schedule->parameters["job_name"] ?>"></td>
	            </tr>         
	    
	            <tr>
	                <td class='job_option'><?php echo _('Select Sensor:')?></td>
	                <td style='text-align:left;'>
                        <?php
                            $sensors = $this->schedule->get_sensors();
                            if (!$sensors) {
                                $sensors = array();
                            } else {
                                array_walk($sensors, function(&$item) {
                                   $item = $item['name'] . '[' . $item['ip'] .']';
                                });
                            }
                            $sensors = array("Null" => _("First Available Sensor-Distributed")) + $sensors;
                            $this->schedule->create_key_val_function(null,'SVRid',$sensors,'SVRid');
                            ?>
	                </td>
	            </tr>
	            <tr>
	                <td class='job_option'><?php echo _('Profile:') ?></td>
	                <td style='text-align:left;'>
	                    <?php $this->schedule->create_key_val_function(null,'sid',$this->schedule->get_v_profiles(),null,function($key,$value) {
	                    	return $value;
	                    }); 
	                 	if ($this->type == "single") {
	                    ?>
	                 	&nbsp;&nbsp;<a href="<?php echo Menu::get_menu_url('settings.php', 'environment', 'vulnerabilities', 'scan_jobs')?>">[ <?php echo _("EDIT PROFILES") ?> ]</a>
	                 	<?php } ?>
	                </td>
	            </tr>
	    	
	            <tr>
	                <td class='job_option' style='vertical-align: top;'><div><?php echo _('Schedule Method:') ?></div></td>
	    		    <td style='text-align:left'>
	    		        <?php $this->schedule->create_key_val_function('','schedule_type',$this->schedule->get_s_methods(),'scheduleM',function($key,$value) {
	                    	return _($value);
	                    }); ?>
	                </td>
	    		</tr>
	
	            <tr $smethodtr_display id='smethodtr'>
	                <td>&nbsp;</td>
	                <td>
	                    <div id="idSched8" class="forminput">
	                        <table cellspacing="2" cellpadding="0" width="100%">
	                            <tr><th width="35%">
	                            	<span id="fl-run-once" class="forminput-label"><?php echo _("Day") ?></span>
	                            	<span id="fl-run-many" class="forminput-label"><?php echo _("Begin in") ?></span>
	                            	</th>
	                                <td class="noborder" nowrap="nowrap">
										<?php $this->schedule->create_key_val_function('Year','biyear',$years); ?>
	                                    &nbsp;&nbsp;&nbsp;
	                                    <?php $this->schedule->create_key_val_function('Month','bimonth',$months); ?>
	                                    &nbsp;&nbsp;&nbsp;
	                                    <?php $this->schedule->create_key_val_function('Day','biday',$days); ?>
	                                </td>
	                            </tr>
	                        </table>
	                    </div>
	                    <div id="idSched4" class="forminput" > 
	                        <table width="100%">
	                            <tr>
	                                <th align="right" width="35%"><?php echo _('Weekly') ?></th>
	                                <td colspan="2" class="noborder">
	                                        <?php $this->schedule->create_key_val_function(null,'dayofweek',$daysMap,null,function($key,$value) {
	                    						return _($value);
	                    					}); ?>
	                                </td>
	                            </tr>
	                        </table>
	                    </div>
	                    <div id="idSched5" class="forminput">
	                        <table width="100%">
	                            <tr>
	                                <th width="35%"><?php echo _('Select Day') ?></td>
	                                <td colspan="2" class="noborder">
	                                    <?php $this->schedule->create_key_val_function(null,'dayofmonth',$days); ?>
	                                </td>
	                            </tr>
	                        </table>
	                    </div>
	                    <div id="idSched6" class="forminput">
	                        <table width="100%">
	                            <tr>
	                                <th width="35%"><?php echo _('Day of week') ?></th>
	                                <td colspan="2" class="noborder">
	                                <?php $this->schedule->create_key_val_function(null,'nthdayofweek',$daysMap,null,function($key,$value) {
	                    				return _($value);
	                    			}); ?>
	                    			</td>
	                            </tr>
	                        </table>
	                        <br>
	                        <table width="100%">
	                            <tr>
	                                <th align="right"><?echo _('N<sup>th</sup> week') ?></th>
	                                <td colspan="2" class="noborder">
	                                    <?php $this->schedule->create_key_val_function(null,'nthweekday',$nweekday,null,function($key,$value) {
	                    				return _($value);
	                    				}); ?>
	                                </td>
	                          </tr>
	                        </table>
	                    </div>
	                    <div id="idSched7" class="forminput" style="margin-bottom:3px;">
	                        <table width='100%'>
	                            <tr>
	                                <th width='35%'><?php echo _('Frequency') ?></th>
	                                <td width='100%' style='text-align:center;' class='nobborder'>
	                                    <span style='margin-right:5px;'><?php echo _('Every') ?></span>
	                                    <?php $this->schedule->create_key_val_function(null,'time_interval',$frequencies); ?>
	                                    <span id="fl-days" class="forminput-label" style='margin-left:5px'><?php echo _('day(s)') ?></span>
	                                    <span id="fl-weeks" class="forminput-label" style='margin-left:5px'><?php echo _('week(s)') ?></span>
	                                </td>
	                            </tr>
	                        </table>
	                    </div>
	                    <div id="idSched2" class="forminput">
	                        <table width="100%">
	                            <tr>
	                                <th rowspan="2" align="right" width="35%"><?php echo _('Time') ?></th>
	                                <td align='right'><?php _("Hour") ?></td>
	                                <td align="left" class="noborder">
	                                    <?php $this->schedule->create_key_val_function(null,'time_hour',$hours); ?>
	                                </td>
	                                <td align='right'><?php echo _("Minutes")?></td>
	                                <td class='noborder' align='left'>
	                                    <?php $this->schedule->create_key_val_function(null,'time_min',$minutes); ?>
	                                </td>
	                            </tr>
	                        </table>
	                    </div>
	                </td>
	            </tr>
	            <tr>
	    	        <td class="madvanced">
	        	       <a class="section"><img id="advanced_arrow" border="0" align="absmiddle" src="../pixmaps/arrow_green.gif"><?php echo _("ADVANCED") ?></a></td>
	    	        <td>&nbsp;</td>
	    	    </tr>
	            <tr class='advanced'>
	                <td class='job_option'><?php echo _("SSH Credential:") ?></td>
	                <td style='text-align:left'>
	                    <?php 
	                    $ssh_arr = array("" => "--") + $this->schedule->getSshCredentials();
	                    $this->schedule->create_key_val_function(null,'ssh_credential',$ssh_arr,'ssh_credential'); ?>  
	  				</td>
	            </tr>
	            <tr class='advanced'>
	                <td class='job_option'><?php echo _("SMB Credential:") ?></td>
	                <td style='text-align:left'>
	                    <?php 
	                    $smb_arr = array("" => "--") + $this->schedule->getSmbCredentials();
	                    $this->schedule->create_key_val_function(null,'smb_credential',$smb_arr,'smb_credential'); ?>  
	                </td>
	            </tr>
	            <tr class="job_option advanced">
	                <td class="job_option"><?php echo _("Timeout:")?></td>
	                <td style="text-align:left;" nowrap>
	                    <input type='text' style='width:80px' name='timeout' value="<?php echo $this->schedule->parameters["timeout"]?>">
	                    <?php echo _("Max scan run time in seconds")?>
	                </td>
	            </tr>
	    	    <tr class='advanced'>
	        	    <td class='job_option'>
	            	    <?echo _("Send an email notification:")?>
	    	        </td>
	    	        <td style="text-align:left;">
	    	            <input type="radio" name="send_email" value="0" <?php echo $email_notification['no'] ?> /><?php echo _("No"); ?>
	    	            <input type="radio" name="send_email" value="1" <?php echo $email_notification['yes'] ?> /><?php echo _("Yes"); ?>
	    	        </td>
	    	    </tr>
	    	    <tr class='advanced'>
	                <td class='job_option'><?php _("Scan job visible for:")?></td>
	    			<td style='text-align: left'>
	    				<table cellspacing='0' cellpadding='0' class='transparent' style='margin: 5px 0px;'>
	    					<tr>
	    						<td class='nobborder'>
	        						<span style='margin-right:3px'><?php echo _('User:') ?></span>
	        				    </td>	
	    						<td class='nobborder'>				
	    							<?php 
	                    			$users_to_assign = array("" => "-"._('Select one user')."-") + $this->schedule->getUsersToAssign();
	                    			$this->schedule->create_key_val_function(null,'user',$users_to_assign,'user'); ?>
	    						</td>
	                            <td style='text-align:center; border:none; !important'>
	                                <span style='padding:5px;'><?php echo _("OR") ?><span>
	                            </td>
	    			            <td class='nobborder'>
	    				            <span style='margin-right:3px'><?php echo _('Entity:')?></span>
	    				        </td>
	    						<td class='nobborder'>	
	    						    <?php 
	                    			$entities_to_assign = array("" => "-"._('Select one entity')."-") + $this->schedule->getEntitiesToAssign();
	                    			$this->schedule->create_key_val_function(null,'entity',$entities_to_assign,'entity'); ?>
	    						</td>
	                        </tr>
	                    </table>
	                </td>
	            </tr>
                    <tr>
                        <td class='job_option'>
                            <?echo _("Exclude Ports:")?>
                        </td>
                        <td style="text-align:left;">
                            <input type="text" name="exclude_ports" value="<?php echo $this->schedule->parameters["exclude_ports"]?>" />
                        </td>
                    </tr>
	    	    <tr>
	        	    <td valign="top" colspan="<?php echo $this->colspan ?>" class="job_option noborder"><br>
	                    <div class="job_option-label"><input onclick="toggle_scan_locally(true)" type="checkbox" id="hosts_alive" name="hosts_alive" value="1" <?php echo $hosts_alive_data['disabled'] . ' ' . $hosts_alive_data['checked'] ?>><?php echo _('Only scan hosts that are alive (greatly speeds up the scanning process)'); ?></div>
	    	            <div class="job_option-label"><input type="checkbox" id="scan_locally" name="scan_locally" value="1" <?php echo $scan_locally_checked ?> /><?php echo _('Pre-Scan locally (do not pre-scan from scanning sensor)');?></div>
	                    <div class="job_option-label"><input type="checkbox" id="not_resolve" name="not_resolve" value="1" <?php echo $resolve_names_checked ?>  /><?php echo _('Do not resolve names');?></div>
	                </td>
					<?php $this->injectHTML(); ?>
	            </tr>
	        </table>
	  
	        <br/>
	        <div class="bottom-buttons">
	        	<?php if ($this->type == "group") { ?>
	        		<input type="button" class="av_b_secondary" id="close_button" value="<?php echo _('Cancel') ?>"/>
	        	<?php } ?>
	            <input type='button' id='mjob' value='<?php echo _("Save") ?>' disabled='disabled' />
	            <span id="loading" style="display:none;margin:0px 0px 0px 10px;" ><?php echo _("Checking Job...") ?></span>
	        
	            <div id='sresult'></div>
	        </div>
	    </form>
	<?php 
	}
	
	public function injectJS() {}
	public function injectCSS() {}
	public function injectHTML() {}

	public function current_time_to_paramaters() {
		$this->schedule->current_time_to_paramaters($this->tz);
	}
}
