<?php
require_once "schedule_strategy_main.php";
require_once "schedule_strategy_helper.php";

trait ScheduleTraitMain {
	public $nt_margin = 80;
	public $type = "single";
	public $action = "sched.php";
	public $colspan = 1;

	public function injectJS() {
		?>

		$("#vtree").dynatree(
		{
			initAjax: { url: "../tree.php?key=<?php echo (Session::is_pro()) ? 'assets|entitiesassets' : 'assets' ?>" },
			clickFolderMode: 2,
			onActivate: function(dtnode)
			{
				if(dtnode.data.url!='' && typeof(dtnode.data.url)!='undefined')
				{
					if (dtnode.data.key.match(/hostgroup_[a-f0-9]{32}/i) !== null) // asset group
					{
						Regexp = /([a-f0-9]{32})/i;
						match  = Regexp.exec(dtnode.data.key);

						value = match[1] + '#hostgroup';
						text  = dtnode.data.title;

						addto ("targets", text, value);
					}
					else
					{
						var Regexp     = /.*_(\w+)/;
						var match      = Regexp.exec(dtnode.data.key);
						var id         = "";
						var asset_name = "";

						id = match[1];

						Regexp = /^(.*)\s*\(/;
						match  = Regexp.exec(dtnode.data.val);

						asset_name = match[1];

						// Split for multiple IP/CIDR
						var keys = dtnode.data.val.split(",");

						for (var i = 0; i < keys.length; i++)
						{
							var item   = keys[i];
							var value  = "";
							var text   = "";

							if (item.match(/\d+\.\d+\.\d+\.\d+\/\d+/) !== null) // net
							{
								Regexp = /(\d+\.\d+\.\d+\.\d+\/\d+)/;
								match  = Regexp.exec(item);

								value = id + "#" + match[1];
								text  = asset_name + " (" + match[1] + ")";
							}
							else if (item.match(/\d+\.\d+\.\d+\.\d+/) !== null) // host
							{
								Regexp = /(\d+\.\d+\.\d+\.\d+)/;
								match  = Regexp.exec(item);

								value = id + "#" + match[1];
								text  = asset_name + " (" + match[1] + ")";
							}

							if(value != '' && text != '' && !exists_in_combo('targets', text, value, true))
							{
								addto ("targets", text, value);
							}
						}
					}

					simulation();

					dtnode.deactivate()

				}
			},
			onDeactivate: function(dtnode) {},
			onLazyRead: function(dtnode)
			{
				dtnode.appendAjax(
				{
					url: "../tree.php",
					data: {key: dtnode.data.key, page: dtnode.data.page}
				});
			}
		});
	        $("#delete_all").on( "click", function() {
	            $("#hosts_alive").attr('disabled', false);

	            $("#scan_locally").attr('disabled', false);

	            selectall('targets');

	            deletefrom('targets');

	            disable_button();

	            $("#sresult").hide();

	            $('#v_info').hide();

	        });

	        $("#delete_target").on( "click", function() {

	            deletefrom('targets');

	            // check targets to enable host_alive check

	            var all_targets = getcombotext('targets');

	            if (all_targets.length == 0)
	            {
	                $('#v_info').hide();

	                $("#hosts_alive").attr('disabled', false);

	                $("#scan_locally").attr('disabled', false);

	                disable_button();

	                $("#sresult").hide();
	            }
	            else
	            {
	                var found = false;

	                var i = 0;

	                var num_targets = 0;

	                while (i < all_targets.length && found == false)
	                {
	                    if (all_targets[i].match( _excluding_ip ))
	                    {
	                        found = true;
	                    }
	                    else
	                    {
	                        num_targets++;
	                    }

	                    i++;
	                }

	                if (found == false)
	                {
	                    $("#hosts_alive").attr('disabled', false);

	                    $("#scan_locally").attr('disabled', false);
	                }

	                if ( num_targets > 0 )
	                {
	                    simulation();
	                }
	                else
	                {
	                    disable_button();

	                    $("#sresult").hide();
	                }
	            }
	        });

	        // Autocomplete assets
	        var assets = [ <?php echo $this->schedule->getAutocomplete(); ?> ];

	        $("#searchBox").autocomplete(assets, {
	            minChars: 0,
	            width: 300,
	            max: 100,
	            matchContains: true,
	            autoFill: false,
	            selectFirst: false,
	            formatItem: function(row, i, max) {
	                return row.txt;
	            }

	        }).result(function(event, item) {

	        	var value = '';
	        	var text  = '';

	            if (item.type == 'host_group' || item.type == 'net_group')
	            {
	                value = item.id + "#" + item.prefix;
	                text  = item.name;

	                addto ("targets", text, value);
				}
	            else
	            {
	            	var keys  = item.ip.split(",");
	            	var ip    = '';

					for (var i = 0; i < keys.length; i++)
					{
	                    ip   = keys[i].replace(/[\s\t\n\r ]+/g,"");

					    value  = item.id + "#" + ip;
					    text   = item.name + " (" +ip + ")";

						if(!exists_in_combo('targets', text, value, true))
						{
						    addto ("targets", text, value);
						}
					}
				}
				simulation();
	            $('#searchBox').val('');

	        });
	        $("#searchBox").click(function() {
	            $("#searchBox").removeClass('greyfont');
	            $("#searchBox").val('');
	            });

	        $("#searchBox").blur(function() {
	            $("#searchBox").addClass('greyfont');
	            $("#searchBox").val('<?php echo _("Type here to search assets")?>');
	        });

	        $('#searchBox').keydown(function(event) {
	            if (event.which == 13)
	            {
	               var target = $('#searchBox').val().replace(/^\s+|\s+$/g, '');

	               targetRegex   = /\[.*\]/;

	               if( target != '' && !target.match( targetRegex ) && !exists_in_combo('targets', target , target, true) ) // is a valid target?
	               {
	                    addto ("targets", target , target );

	                    // force pre-scan

	                    if (target.match( _excluding_ip ) )
	                    {
	                        show_notification('v_info', '<?php echo _('We need to know all network IPs to exclude one of them, so the "Only hosts that are alive" option must be enabled.')?>' , 'nf_info', false, true, 'padding: 3px; width: 80%; margin: 12px auto 12px auto; text-align: center;');

	                        $("#hosts_alive").attr('checked', true);
	                        $("#hosts_alive").attr('disabled', true);
	                        $("#scan_locally").attr('disabled', false);
	                    }

	                    $("#searchBox").val("");

	                    simulation();
	                }
	           }
	        });
	        toggle_scan_locally(false);
	        simulation();
		<?php
		}
		public function injectCSS() {
			?>
			.job_option {
				text-align:left;
				padding: 0px 0px 0px 70px;
			}

            .job_option-label {
				margin: 5px auto;
				padding: 5px auto;
			}

            .madvanced {
				text-align:left;
				padding: 0px 0px 4px 59px;
			}

            #user, #entity {
			    width: 159px;
			}

            #user option:first-child, #entity option:first-child {
			    text-align:center !important;
			}

			.bottom-buttons {
			    margin:0px auto;
			    text-align: center
			}
			<?php
		}
		public function injectHTML() {
		?>
		<td class="noborder" valign="top">
			<table width="100%" class="transparent" cellspacing="0" cellpadding="0">
				<tr>
					<td class="nobborder" style="vertical-align: top;text-align:left;padding:10px 0px 0px 0px;">
						<table class="transparent" cellspacing="4">
							<tr>
								<td class="nobborder" style="text-align:left;">
									<input class="greyfont" type="text" id="searchBox" value="<?php echo _("Type here to search assets")?>" />
								</td>
							</tr>
							<tr>
								<td class="nobborder">
									<select id="targets" name="targets[]" multiple="multiple">
									<?php
									if ($selected_targets = $this->schedule->getTargets()) {
										foreach ($selected_targets as $t_id => $t_name) {
											echo "<option value='$t_id'>$t_name</option>";
										}
									}
									?>
									</select>
		                        </td>
		                    </tr>
		                    <tr>
		                        <td class="nobborder" style="text-align:right">
		                        	<input type="button" value=" [X] " id="delete_target" class="av_b_secondary small"/>
		                            <input type="button" style="margin-right:0px;"value="Delete all" id="delete_all" class="av_b_secondary small"/>
		                        </td>
		                    </tr>
		                </table>
		            </td>
		            <td class="nobborder" width="450px;" style="vertical-align: top;padding:0px 0px 0px 5px;">
		                <div id="vtree" style="text-align:left;width:100%;"></div>
		            </td>
		        </tr>
		    </table>
		</td>
		<?php
		}

}

trait ScheduleTraitModal {
	public $nt_margin = 90;
	public $type = "group";
	public $action = "new_scan.php";
	public $colspan = 2;

	public function injectJS() {}
	public function injectCSS() {
		?>
			.job_option {
				text-align:left;
				padding: 0px 0px 0px 30px;
			}
			.madvanced {
				text-align:left;
				padding: 0px 0px 4px 30px;
			}
			#user, #entity {
				width: 140px;
			}
			#close_button {
				margin-right: 10px;
			}
			.bottom-buttons {
				margin:0px auto 10px auto;
				text-align: center
			}
			<?php
		}
		public function injectHTML() {
			$selected_targets = $this->schedule->getTargets();
			?>
			<select id="targets" name="targets[]" multiple="multiple" style="display: none">
			<?php foreach ($selected_targets as $t_id => $t_name) {?>
				<option value='<?php echo $t_id ?>'><?php echo $t_name ?></option>
			<?php } ?>
			</select>

			<span id="thosts" class="hidden">
			<?php
			//This one is used to pass total hosts from BE to the FE, via common algorithm
			//It cannot be done on current realization in a proper way
			//because in main form data is passed via separate ajax request but not submit as it should be
			echo count($this->schedule->getPlainTargets());
			?>
			</span>
			<?php
		}
}



class ScheduleStrategyEdit extends ScheduleStrategyHelper implements ScheduleStrategyInterface {
	use ScheduleTraitMain;
	private $daysW = array("Su","Mo","Tu","We","Th","Fr","Sa");
	public function init() {
		// read the configuration from database
		$query    = 'SELECT * FROM vuln_job_schedule WHERE id = ?';
		$params   = array($this->schedule->parameters["sched_id"]);
		$result   = $this->schedule->conn->execute($query, $params);
		$database = $result->fields;
        $database['credentials'] = mb_convert_encoding($database['credentials'],  'UTF-8', 'ISO-8859-1');

		# when editing if the creation date is in the past. The current date will be shown
		$date_begin = date_format(date_create($database["begin"]." ".$database['time']),"Y-m-d-H:i");
		# in order to compare the same minute will be used
        $now = $this->schedule->current_time($this->tz, "Y-m-d-H").substr($database['time'],2,3);

        #we no longer accept date time in past so for improving user experience if the begin is in the past it will be set to the current one.
        if($date_begin > $now)
            $date = $date_begin;
        else
            $date = $now;

        list($biyear, $bimonth, $biday, $time) = explode("-", $date);

		$this->loadData();
		//job name
		$this->schedule->parameters["SVRid"] = $database['email'];
		$this->schedule->parameters["exclude_ports"] = $database['exclude_ports'];
		$this->schedule->parameters["scan_locally"]  = intval($database['scan_locally']);
        $this->schedule->parameters["entity"]  = $database['username'];
		$this->schedule->setPrimarySettingsFromDB($database);

		$this->schedule->parameters["biyear"]      = $biyear;
		$this->schedule->parameters["bimonth"]     = $bimonth;
		$this->schedule->parameters["biday"]       = $biday;
		$this->schedule->parameters["time_hour"]   = substr($time,0,2);
		$this->schedule->parameters["time_min"]    = substr($time,3,2);
		$this->schedule->parameters["not_resolve"]		= (!$database["resolve_names"])*1;
		$this->schedule->parameters["time_interval"] = $database['time_interval'];
		$this->schedule->parameters["dayofmonth"] = $database['day_of_month'];
		$days = array_flip($this->daysW);
		$this->schedule->parameters["nthdayofweek"] = $this->schedule->parameters["dayofweek"] = $days[$database['day_of_week']];
		$this->schedule->parameters["schedule_type"] = $database['schedule_type'];
		$this->schedule->parameters["nthweekday"] = $database['day_of_month'];
		$this->schedule->parameters["edit_sched"] = TRUE;
        $this->schedule->parameters["fk_name"] = $database['fk_name'];
		$this->schedule->load_targets();
	}
}




class ScheduleStrategyRerun extends ScheduleStrategyHelper implements ScheduleStrategyInterface {
	use ScheduleTraitMain;
	public function init() {
		$query    = 'SELECT * FROM vuln_jobs WHERE id = ?';
		$params   = array($this->schedule->parameters["job_id"]);
		$result   = $this->schedule->conn->execute($query, $params);
		$database = $result->fields;
        $database['credentials'] = mb_convert_encoding($database['credentials'],  'UTF-8', 'ISO-8859-1');
		$this->schedule->parameters["SVRid"] = $database['notify'];
		$this->schedule->parameters["exclude_ports"] = $database['exclude_ports'];
		$this->schedule->parameters["scan_locally"] = intval($database['authorized']);
		$this->schedule->setPrimarySettingsFromDB($database);
		$this->loadData();
		$this->schedule->load_targets();
	}

	public function persetDefaults() {
		$this->current_time_to_parameters();
	}
}


class ScheduleStrategyDelete extends ScheduleStrategyHelper implements ScheduleStrategyInterface {
	use ScheduleTraitMain;
	public function execute() {
		$conn = $this->schedule->conn;
		$query = 'SELECT username, name, id, report_id FROM vuln_jobs WHERE id=?';
		$params = array($this->schedule->parameters["job_id"]);
		$result = $conn->execute($query, $params);
		$username   = $result->fields['username'];
		$job_name   = $result->fields['name'];
		$kill_id    = $result->fields['id'];
		$report_id  = $result->fields['report_id'];

			$can_i_delete = FALSE;

			if (Session::am_i_admin() || Session::get_session_user() == $username)
			{
				$can_i_delete = TRUE;
			}
			else if (Session::is_pro() && Acl::am_i_proadmin())
			{
				$user_vision = (!isset($_SESSION['_user_vision'])) ? Acl::get_user_vision($conn) : $_SESSION['_user_vision'];

				$my_entities_admin = array_keys($user_vision['entity_admin']);

				if (in_array($username, $my_entities_admin))
				{
					$can_i_delete = TRUE;
				}
			}

			if ($can_i_delete)
			{
				$query  = 'DELETE FROM vuln_jobs WHERE id=?';
				$params = array($kill_id);
				$result = $conn->execute($query, $params);

				$query  = 'DELETE FROM vuln_nessus_reports WHERE report_id=?';
				$params = array($report_id);
				$result = $conn->execute($query, $params);

				$query  = 'DELETE FROM vuln_nessus_report_stats WHERE report_id=?';
				$params = array($report_id);
				$result = $conn->execute($query, $params);

				$query  = 'DELETE FROM vuln_nessus_results WHERE report_id=?';
				$params = array($report_id);
				$result = $conn->execute($query, $params);

				$infolog = array($job_name);
				Log_action::log(65, $infolog);
			}
			$this->redirect();
	}
}



trait ScheduleCreate {
	public function init() {
		$this->loadData();
		$conf = $GLOBALS['CONF'];
		$this->schedule->parameters["scan_locally"] = $this->schedule->parameters["authorized"] = $conf->get_conf('gvm_pre_scan_locally');
		$this->schedule->parameters["hosts_alive"]  = 1;
	}
	public function persetDefaults() {
		$this->current_time_to_parameters();
		if (!$this->schedule->parameters["sid"]) {
			foreach ($this->schedule->get_v_profiles() as $key => $value) {
				if (preg_match("/^Default\s-\s.*/",$value )) {
					$this->schedule->parameters["sid"] = $key;
				}
			}
		}
		$this->schedule->load_targets();
	}
}

class ScheduleStrategyCreate extends ScheduleStrategyHelper implements ScheduleStrategyInterface {
	use ScheduleTraitMain,ScheduleCreate;
}

class ScheduleStrategyCreateModal extends ScheduleStrategyHelper implements ScheduleStrategyInterface {
	use ScheduleTraitModal,ScheduleCreate;
	public function redirect() {
		return false;
	}
}

trait ScheduleSave {
	public function persetDefaults() {
		$this->schedule->load_targets();

	}

	public function execute() {
		$this->schedule->validate();
		if($this->schedule->getErrors()) {
			$this->loadData();
		} else {
			// save the scan data
			$this->submit_scan();
			$this->redirect();
		}
	}

    public function submit_scan() {
        $parameters = $this->schedule->parameters;
        $year		= $parameters["biyear"];
        $month  	= $parameters["bimonth"];
        $day	    = $parameters["biday"];
        $dof		= $parameters["dayofmonth"];
        $hour		= $parameters['time_hour'];
        $min		= $parameters['time_min'];
        $insert_time = gmdate('YmdHis');
        $fk_name	= $parameters['fk_name'];
        $hourandmin = str_pad($hour, 2, "0", STR_PAD_LEFT).str_pad($min, 2, "0", STR_PAD_LEFT);
        $tztimediff = $this->tz * 3600;

        //Transform current time to local time
        $current_time = time() + $tztimediff;
        //Put seconds to 00
        $current_time = strtotime(date("Y-m-d H:i:00", $current_time));

        $begin_time = mktime($hour,$min,0,$month,$day,$year);
        $ndays = dates::$daysMap;

        $datevs = date("Hi", $current_time);
        $datedvs = date("dHi",$current_time);

        if ($begin_time < $current_time){
            $e_message = _('Error! Scan job was scheduled to start in the past')." (".date("Y-m-d H:i", $begin_time).").";
            Av_exception::throw_error(Av_exception::USER_ERROR, $e_message);
        } else {
            //Begin_time is equal or greater than the current_time
            if (in_array($parameters["schedule_type"], array("D", "W", "M", "NW"))){
                switch ($parameters["schedule_type"]){
                    case "D":
                        if ($begin_time == $current_time) {
                            list($year, $month, $day) = explode("-",date("Y-m-d", strtotime("+1 day", $begin_time)));
                        }
                    break;

                    case "W":
                        if (($begin_time > $current_time && date("w", $begin_time) != $parameters["dayofweek"]) || $begin_time == $current_time) {
                            list($year, $month, $day) = explode("-",date("Y-m-d", strtotime("next ".$ndays[$parameters["dayofweek"]], $begin_time)));
                        }
                    break;

                    case "M":
                        $day = $dof;
                        if ($begin_time == $current_time) {
                            list($year, $month, $day) = explode("-",date("Y-m-d", strtotime("+1 month", $begin_time)));
                        }
                    break;

                    case "NW":
                        $this->schedule->parameters['dayofmonth'] = $parameters['nthweekday'];
                        $this->schedule->parameters['dayofweek'] = $parameters['nthdayofweek'];

                        # Move to the previous month so the re-scheduler script can calculate the right next_run date
                        list($year, $month, $day) = explode("-", date("Y-m-d", strtotime("-1 day", $begin_time)));
                    break;
                }
            }
        }

        //Transform start time to UTC
        $requested_run = date("YmdHi00", mktime($hour, $min,0,$month, $day, $year) - $tztimediff);

        ossim_clean_error();
        $queries = $this->schedule->load_ctx_from_targets($insert_time, $parameters["biyear"], $parameters["bimonth"], $parameters["biday"], $requested_run, $fk_name);
        $execute_errors = array();

        foreach ($queries as $sql_data)
        {
            $rs = $this->schedule->conn->execute($sql_data['query'], $sql_data['params']);

            if ($rs === FALSE)
            {
                $execute_errors[] = $this->schedule->conn->ErrorMsg();
            }
        }

        if (!empty($execute_errors)) {
            Av_exception::throw_error(Av_exception::DB_ERROR, implode("; ",$execute_errors));
        }

        // If the ID is not set then the query was an insertion, get the last inserted ID
        if (intval($parameters["sched_id"]) == 0) {
            $query = ossim_query('SELECT LAST_INSERT_ID() as sched_id');
            $rs = $this->schedule->conn->Execute($query);

            if (!$rs) {
                Av_exception::throw_error(Av_exception::DB_ERROR, $this->schedule->conn->ErrorMsg());
            } else {
                $parameters["sched_id"] = $rs->fields['sched_id'];
            }
        }

        if ($parameters["schedule_type"] != 'N') {
            // We have to update the vuln_job_assets
            Vulnerabilities::update_vuln_job_assets($this->schedule->conn, 'insert', $parameters["sched_id"], 0);
        }
        if ($parameters["schedule_type"] == 'NW')
        {
            // This type of scan requires the next scan to be recalculated straight away
            // nessus_job_reschedule.pl takes care of re-scheduling so it is not done in PHP and Perl (ENG-110470)
            shell_exec( "/usr/share/ossim/scripts/vulnmeter/nessus_job_reschedule.pl " . $parameters["sched_id"] );
        }

        //User activity log
        $infolog = array($parameters["job_name"]);
        if($parameters["schedule_type"] == 'N')  //Immediate scan job
            $log_code = 66;
        elseif ($parameters["edit_sched"] == NULL) //Schedule scan job
            $log_code = 67;
        else  //Edit Schedule scan job
            $log_code = 109;

        Log_action::log($log_code, $infolog);

        $this->saveSuccess();
    }

	private function saveSuccess() {
	    $config_nt = array(
				'content' => '',
				'options' => array (
						'type'          => 'nf_success',
						'cancel_button' => FALSE),
				'style'   => 'width: 40%; margin: 20px auto; text-align: center;'
		);

	    if (empty($execute_errors)){
            $config_nt['content'] = _('Successfully Submitted Job');
        } else {
            $config_nt['content'] = _('Error creating scan job:') . implode('<br>', $execute_errors);
            $config_nt['options']['type'] = 'nf_error';
        }


		$nt = new Notification('nt_1', $config_nt);
		$nt->show();
		$this->schedule->conn->close($this->schedule->conn);
	}

	private function weekday_month($day, $nth, $current_year,$current_month, $current_hour, $current_minute, $tztimediff) {
		$inc = $current_hour * 3600 + $current_minute * 60;
		$today  = time();
		$initial_time = mktime(0, 0, 0, $current_month, 1, $current_year);
		$time = strtotime("-1 day", $initial_time);
		$time = $this->calculate_day_in_week_in_month($nth, $day, $time);
		while ( $time + $inc - $tztimediff <= $today ) {
			$initial_time = strtotime("next month", $initial_time);
			$time = $this->calculate_day_in_week_in_month($nth, $day, $initial_time);
		}
		return explode("-",date('Y-m-d', $time));
	}

	private function calculate_day_in_week_in_month($nth, $day, $time) {
		//Search date
		for ($i=0; $i<$nth; $i++){
			$time = strtotime("next $day", $time);
		}
		return $time;
	}
}


class ScheduleStrategySave extends ScheduleStrategyHelper implements ScheduleStrategyInterface {
	use ScheduleTraitMain,ScheduleSave;
}

class ScheduleStrategySaveModal extends ScheduleStrategyHelper implements ScheduleStrategyInterface {
	use ScheduleTraitModal, ScheduleSave;
	public function redirect() {
		if ($this->schedule->getErrors()) {
			$this->loadData();
			return;
		}
		$cnt = count($this->schedule->getPlainTargets());
		$message = $this->schedule->parameters["schedule_type"] == 'N'
				? sprintf(_('Vulnerability scan in progress for (%s) assets'), $cnt)
				: sprintf(_('Vulnerability scan has been scheduled on (%s) assets'), $cnt);
				?>
			<script>
		    	top.frames['main'].show_notification('asset_notif', "<?php echo Util::js_entities($message) ?>", 'nf_success', 15000, true);
		        parent.GB_hide();
		    </script>
		<?php
		die();
	}

	public function saveSuccess() {
	}
}
