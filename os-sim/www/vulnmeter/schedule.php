<?php
define("EXCLUDING_IP",  "^!(([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])$");
define("EXCLUDING_IP2", "!(([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])");

class Schedule {
	public $conn; // database connection
	// get parameters
	public $parameters = array(
        'action' => null,
        'job_name' => null,
        'schedule_type' => null,
        'time_hour' => null,
        'time_min' => null,
        'dayofweek' => null,
        'dayofmonth' => null,
        'timeout' => 57600,
        'SVRid' => null,
        'sid' => null,
        'targets' => null,
        'job_id' => null,
        'sched_id' => null,
        'user' => null,
        'entity' => null,
        'nthweekday' => null,
        'nthdayofweek' => null,
        'time_interval' => null,
        'biyear' => null,
        'bimonth' => null,
        'biday' => null,
        'send_email' => null,
        'ssh_credential' => null,
        'ssh_credential_port' => 22,
        'smb_credential' => null,
        'hosts_alive' => null,
        'scan_locally' => null,
        'not_resolve' => null,
        'net_id' => null,
        'status' => 1,
        'exclude_ports' => "",
        'edit_sched' => null,
        'fk_name' => null
    );

	private $sgr = array();
	private $arr_ctx = array();
	private $all_sensors; //  sensors list
	private $v_profiles; //profiles
	private $users_to_assign = array(); //users
	private $entities_to_assign = array(); //entries
	private $selected_targets = array(); //targets (ips, nets and groups) to scan
	private $total_targets = array(); //targets (plain ips) to scan
    private $ip_ctxs = array(); //IP addresses with their ctxs
    private $validation_errors = array(); //validation errors
	private $s_methods = array(
			'N'   => 'Immediately',
			'O'   => 'Run Once',
			'D'   => 'Daily',
			'W'   => 'Day of the Week',
			'M'   => 'Day of the Month',
			'NW'  => 'N<sup>th</sup> week of the month'
	);
	private $ip_exceptions_list = array();
    private $ssh_credentials = array(); //ssh credentials
    private $smb_credentials = array(); //smb credentials
    private $is_modal = false;

    public function setModal($is_modal) {
    	$this->is_modal = $is_modal;
    }

    public function isModal() {
    	return $this->is_modal;
    }

	public function __construct() {
		$db   = new ossim_db();
		$conn = $db->connect();
		$conn->SetFetchMode(ADODB_FETCH_BOTH);
		$this->conn = $conn;
		if (!$this->pluginsCount()) {
			die ('<h2>No plugins found.  Please contact support for further assistance.</h2>');
		}
	}

	public function pluginsCount() {
		// check the number of plugins
		$query  = 'select count(*) as total_plugins from vuln_nessus_plugins';
		$result = $this->conn->execute($query);
		return $result->fields['total_plugins'];
	}

	public function getTargets() {
		return $this->selected_targets;
	}

	public function getPlainTargets() {
		return $this->total_targets;
	}

	public function getArrCtx() {
		return $this->arr_ctx;
	}

	public function getSgr() {
		return $this->sgr;
	}

	public function ip_exceptions_list() {
		return $this->ip_exceptions_list;
	}

	public function getUsersToAssign() {
		return $this->users_to_assign;
	}

	public function getEntitiesToAssign() {
		return $this->entities_to_assign;
	}

	public function getSshCredentials() {
		return $this->ssh_credentials;
	}

	public function getSmbCredentials() {
		return $this->smb_credentials;
	}

	public function loadSshCredentials() {
		$this->ssh_credentials = $this->load_credentials('ssh');
	}

	public function loadSmbCredentials() {
		$this->smb_credentials = $this->load_credentials('smb');
	}

	public function load_targets ()
	{
	    $conn = $this->conn;
		$net_id = $this->parameters["net_id"];
		// fill targets
		$ip_list = $this->parameters["targets"];

		if(empty($ip_list) &&  preg_match('/^[a-f\d]{32}$/i', $net_id) && Asset_net::is_in_db($conn, $net_id)) {
			// Autofill new scan job from deployment
			$cidrs = explode(',', Asset_net::get_ips_by_id($conn, $net_id));
			foreach ($cidrs as $cidr) {
				$ip_list[] = $net_id . '#' . trim($cidr);
			}
		}

		if (empty($ip_list)){
            return;
        }

		if (!is_array($ip_list)) {
			$ip_list = $this->targetsToArray(trim($ip_list));
		}

		$default_ctx = Session::get_default_ctx();
		foreach ($ip_list as $asset) {
			$asset = trim($asset);
			if (preg_match('/^([a-f\d]{32})#(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\/\d{1,2})$/i', $asset, $found))
			{
			    if (Asset_net::is_in_db($conn, $found[1])){
                    $ctx = Asset_net::get_ctx_by_id($conn, $found[1]);
			        $_asset_name = Asset_net::get_name_by_id($conn, $found[1]);
                } else {
                    $ctx = $default_ctx;
                    $_asset_name = $found[2];
                }

                $ips = Cidr::expand_CIDR($found[2], "FULL", "IP");
                $this->selected_targets[$asset] = $_asset_name;

                foreach ($ips as $ip){
                    if ($ctx != $default_ctx || empty($this->ip_ctxs[$asset][$ip])){
                        $this->ip_ctxs[$asset][$ip] = $ctx."#".$ip;
                    }

                    $this->total_targets[$ctx."#".$ip] = $ip;
                }
        	}
			else if (preg_match('/^([a-f\d]{32})#(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})$/i', $asset, $found))
			{
                if (Asset_host::is_in_db($conn, $found[1])){
                    $ctx = Asset_host::get_ctx_by_id($conn, $found[1]);
                    $_asset_name = Asset_host::get_name_by_id($conn, $found[1]);
                } else {
                    $ctx = $default_ctx;
                    $_asset_name = $found[2];
                }

				$this->selected_targets[$asset] = $_asset_name;

                if ($ctx != $default_ctx || empty($this->ip_ctxs[$asset][$found[2]])){
                    $this->ip_ctxs[$asset][$found[2]] = $ctx."#".$found[2];
                }

                $this->total_targets[$ctx."#".$found[2]] = $found[2];
            }
			else if (preg_match('/^([a-f\d]{32})#hostgroup$/i', $asset, $found))
			{
				$this->selected_targets[$asset] = Asset_group::get_name_by_id($conn, $found[1]);
				$ag = new Asset_group($found[1]);

				$hosts = $ag->get_hosts($conn, '', array(), TRUE);

                foreach ($hosts[0] as $host_id => $host){
                    $host_ips = explode(',', $host[2]);

                    foreach ($host_ips as $ip){
                        if ($host[1] != $default_ctx || empty($this->ip_ctxs[$asset][$ip])){
                            $this->ip_ctxs[$asset][$ip] = $host[1]."#".$ip;
                        }

                        $this->total_targets[$host[1]."#".$ip] = $ip;
                    }
                }
			}
			else if (preg_match('/^([a-f\d]{32})#netgroup$/i', $asset, $found))
			{
				$this->selected_targets[$asset] = Net_group::get_name_by_id($conn, $found[1]);
				$nets = Net_group::get_networks($conn,$found[1]);
				foreach ($nets as $net) {
					$net_ips = Asset_net::get_ips_by_id($conn,$net->net_id);
					$ctx = Asset_net::get_ctx_by_id($conn, $net->net_id);

					foreach ($net_ips as $net_ip) {
						$ips = Cidr::expand_CIDR($net_ip, "FULL", "IP");

                        foreach ($ips as $ip){
                            if ($ctx != $default_ctx || empty($this->ip_ctxs[$asset][$ip])){
                                $this->ip_ctxs[$asset][$ip] = $ctx."#".$ip;
                            }

                            $this->total_targets[$ctx."#".$ip] = $ip;
                        }
					}
				}
			}
			else
			{
				//Asset or networks without IDs
			    $this->selected_targets[$asset] = $asset;
                $ctx = $default_ctx;
                if(preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\/\d{1,2}$/i', $asset)) {
                    $related_nets = array_values(Asset_net::get_closest_nets($this->conn, $asset));
                    $firs_net = $related_nets[0];
                    $closest_net_id = $firs_net['id'];

                    if (valid_hex32($closest_net_id)) {
                        $ctx = Asset_net::get_ctx_by_id($this->conn, $closest_net_id);
                    }

                    $ips = Cidr::expand_CIDR($asset, "FULL", "IP");

                    foreach ($ips as $ip) {
                        $this->ip_ctxs[$asset][$ip] = $default_ctx."#". $ip;
                        $this->total_targets[$default_ctx."#". $ip] = $ip;
                    }
                } elseif(preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/i', $asset)) {
                    $host_ids = Asset_host::get_id_by_ips($this->conn, $asset);
                    $id = key($host_ids);

                    if (!empty($id)){
                        $ctx = Asset_host::get_ctx_by_id($this->conn, $id);
                    }

                    $this->ip_ctxs[$asset][$asset] = $default_ctx."#". $asset;
                    $this->total_targets[$default_ctx."#". $asset] = $asset;
                } elseif(security_class::valid_hostname($asset) || security_class::valid_fqdns($asset)) {
                    $filters   = array('where' => "hostname = '$asset' OR fqdns like '$asset'");
                    $_hosts_data = Asset_host::get_basic_list($this->conn, $filters);
                    $host_list = $_hosts_data[1];
                    if (count($host_list) > 0) {
                        $first_host = array_shift($host_list);
                        $hips = explode(",", $first_host['ips']);
                        $ctx  = $first_host['ctx'];
                        foreach ($hips as $ip) {
                            $ip = trim($ip);
                            $this->ip_ctxs[$asset][$ip] = $ctx."#".$ip;
                            $this->total_targets[$ctx."#".$ip] = $ip;
                        }
                    }
                }
			}
		}

		//Don't remove!! It's necessary to clean the errors after calling to security class functions (valid_hostname or valid_fqdns since)
        //These functions set validation errors globally and this could cause false positives when ossim_valid functions are called from other files.
        security_class::ossim_clean_error();
	}

	//the session variable comes from simulate.php file
	//actually it is bad design, and possible issue
	//but to rewrite it - i need to rewrite all simulate.php file
	//so - for now leave it as is
    public function load_ctx_from_targets($insert_time, $bbiyear, $bbimonth, $bbiday, $requested_run, $fk_name) {
        $username = Session::get_session_user();
        $username = (valid_hex32($this->parameters["entity"])) ? $this->parameters["entity"] : $this->parameters["user"];
        if (empty($username)) {
            $username = Session::get_session_user();
        }

        $fkname = ($fk_name == NULL) ? Session::get_session_user() : $fk_name;

        $params = array(
                "username"		   => $username,
                "fk_name"		   => $fkname,
				"only_alive_hosts" => intval($this->parameters["hosts_alive"]),
				"profile_id"	   => $this->parameters["sid"],
				"send_email"	   => $this->parameters["send_email"],
				"meth_TIMEOUT"	   => $this->parameters["timeout"],
				//invert boolean
				"resolve_names"	   => $this->parameters["not_resolve"] ^ 1,
				"credentials"	   => $this->parameters["ssh_credential"] . '|' . $this->parameters["smb_credential"],
				"ssh_credential_port"	   => $this->parameters["ssh_credential_port"],
				"exclude_ports"	   => $this->parameters["exclude_ports"]
		);

        $plain_targets = $this->getPlainTargets();
		$submit_data = array();
		$arr_ctx = array();

		if (Filter_list::MAX_VULNS_ITEMS < count($plain_targets)) {
  		    $plain_targets = array_chunk($plain_targets,Filter_list::MAX_VULNS_ITEMS, TRUE);
		  	$cnt = count($plain_targets);
			$sensors = array_unique($_SESSION['_vuln_targets']);
			$sensors_cnt = count($sensors);
			$counter = ceil($cnt/$sensors_cnt);
			$sensor = current($sensors);

			for ($i=0,$j=0;$i<$cnt;$i++,$j++) {
				if ($j>$counter) {
					$j=-1;
					$sensor = next($sensors);
				}
				$params["name"] = sprintf(_("%s (part %s of %s)"),$this->parameters["job_name"],$i+1,$cnt);

				$submit_data[] = array(
					"IP_ctx" => array_keys($plain_targets[$i]),
					"params" => $params,
					"targets" => array_values($plain_targets[$i]),
					"notify_sensor" => $sensor
				);
  			}
		} else {
            $sgr = array();
		    foreach($_SESSION['_vuln_targets'] as $target_selected => $server_id ) {
				if (!$sgr[$server_id]) {
					$sgr[$server_id] = array();
                    $sgr[$server_id]['targets'] = array();
                    $sgr[$server_id]['ip_ctxs'] = array();
				}

				$sgr[$server_id]['targets'][] = $target_selected;

				if (!empty($this->ip_ctxs[$target_selected])){
                    $sgr[$server_id]['ip_ctxs'] = array_merge($sgr[$server_id]['ip_ctxs'], array_values($this->ip_ctxs[$target_selected]));
                }
		    }

			$params["name"] = $this->parameters["job_name"];

			if ($this->parameters["schedule_type"] == 'N') {
				foreach ($sgr as $sensor_id => $item) {
   				    $submit_data[] = array(
					    "IP_ctx"        => $item['ip_ctxs'],
                        "targets"       => $item['targets'],
                        "notify_sensor" => $sensor_id,
                        "params"        => $params
                    );
				}
			} else {
                $ip_ctxs = array();
                foreach($this->getTargets() as $host_id => $host) {
                    $ip_ctxs = array_merge($ip_ctxs, array_values($this->ip_ctxs[$host_id]));
                }

			    $submit_data[] = array(
				    "IP_ctx"        => $ip_ctxs,
                    "targets"       => $this->getTargets(),
                    "notify_sensor" => null,
                    "params"        => $params
                );
			}
		}

		unset($_SESSION['_vuln_targets']); // clean scan targets
		$queries = array();
		if ($this->parameters["schedule_type"] == 'N') {
			$this->resetImmediateJob();
			foreach ($submit_data as $item) {
				$queries[] = $this->formatImmediateQuery($item["targets"], $item["params"], $insert_time, $item["IP_ctx"], $item["notify_sensor"]);
			}
		} else {
			foreach ($submit_data as $item) {
				$queries[] = $this->formatScheduledQuery($item["params"], $bbiyear, $bbimonth, $bbiday, $insert_time, $requested_run, $item["IP_ctx"], $item["targets"]);
			}
		}
		return $queries;
	}

	public function formatImmediateQuery($sgr,$params,$insert_time,$IP_ctx,$notify_sensor) {
		$queries = array();
		$target_list = array_merge($sgr,$this->ip_exceptions_list());
		$params["meth_TARGET"]	 = $this->targetsToString($target_list);
		$params["scan_PRIORITY"] = '3';
		$params["status"]		 = 'S';
		$params["notify"]		 = $notify_sensor;
		$params["authorized"]	 = intval($this->parameters["scan_locally"]);
		$params["scan_SUBMIT"]	 = $insert_time;
		$params["IP_ctx"]	     = $this->targetsToString($IP_ctx);
		$params["scan_ASSIGNED"] = $this->parameters["SVRid"];
		$params["scan_next"]	 = date("YmdHis");
		$params["meth_SCHED"]	 = $this->parameters["schedule_type"];
        $params["ssh_credential_port"]= $this->parameters["ssh_credential_port"];
        $params["credentials"]   = mb_convert_encoding($params["credentials"],  'ISO-8859-1', 'UTF-8');
        $keys = implode(",",array_keys($params));

		$queries['query'] = "INSERT INTO vuln_jobs ( $keys )
		VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		$queries['params'] = $params;

		return $queries;
	}

	public function formatScheduledQuery($params, $bbiyear, $bbimonth, $bbiday, $insert_time, $requested_run, $IP_ctx, $targets) {
		$queries = array();
		$params["begin"]		= $bbiyear . sprintf("%02d", $bbimonth) . sprintf("%02d", $bbiday);
		//because in database week starts from 1
		//dozens of gracias to one whom implemented the "architecture"
        $params["day_of_week"]	= $this->parameters["dayofweek"] + 1;
		$params["day_of_month"]	= $this->parameters["dayofmonth"];
		$params["time"]			= "{$this->parameters['time_hour']}:{$this->parameters['time_min']}:00";
		$params["scan_locally"]	= intval($this->parameters["scan_locally"]);
		$params["createdate"]	= $insert_time;
		$params["enabled"]		= strval($this->parameters["status"]);
		$params["time_interval"]= $this->parameters["time_interval"];
		$params["email"]		= $this->parameters["SVRid"];
		$params["next_CHECK"]	= $requested_run;
		$params["schedule_type"]= $this->parameters["schedule_type"];
		$params["ssh_credential_port"]= $this->parameters["ssh_credential_port"];
		$params["IP_ctx"]		= $this->targetsToString($IP_ctx);
		$params["meth_TARGET"]	= $this->targetsToString(array_keys($targets));
        $params["credentials"]  = mb_convert_encoding($params["credentials"],  'ISO-8859-1', 'UTF-8');

		if (isset($this->parameters["sched_id"]) && $this->parameters["sched_id"] >0) {
			$keys = implode(" = ?, ",array_keys($params))." = ? ";
			$queries['query']  = "UPDATE vuln_job_schedule SET $keys WHERE id = ?";
			$params["id"] = $this->parameters["sched_id"];
		} else {
			$keys = implode(",",array_keys($params));
			$queries['query'] = "INSERT INTO vuln_job_schedule ( $keys )
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ";
		}
		$queries['params'] = $params;
		return $queries;
	}


	public function get_sensors() {
		return $this->all_sensors;
	}

	public function get_v_profiles() {
		return $this->v_profiles;
	}

	public function get_s_methods() {
		return $this->s_methods;
	}

	public function set_sensors($all_sensors) {
		return $this->all_sensors = $all_sensors;
	}

	public function set_v_profiles($v_profiles) {
		return $this->v_profiles = $v_profiles;
	}

	public function set_s_methods($s_methods) {
		return $this->s_methods = $s_methods;
	}

	public function setPrimarySettingsFromDB($database) {
		//job name
		$this->parameters["job_name"] = $database['name'];
		$this->parameters["sid"] = $database['profile_id'];
		$this->parameters["hosts_alive"] = $database['only_alive_hosts'];
		$this->parameters["targets"] = $database['meth_TARGET'];
		$this->parameters["not_resolve"] = $database['resolve_names'] ^ 1;
		$this->parameters["timeout"] = $database['meth_TIMEOUT'];
		$this->parameters["user"] = $this->entity = $database['username'];
		$this->parameters["send_email"] = intval($database['send_email']);
		$this->parameters["ssh_credential_port"] = $database['ssh_credential_port'];
		preg_match ('/(.*)\|(.*)/', $database['credentials'], $found);
		$this->parameters["ssh_credential"] = $found[1];
		$this->parameters["smb_credential"] = $found[2];
		$this->parameters["targets"] = $this->targetsToArray($this->parameters["targets"]);
	}

	public function targetsToString($targets) {
		return implode("\n",$targets);
	}

	public function targetsToArray($targets) {
		return explode("\n",$targets);
	}

	public function addError($error) {
		$this->validation_errors[] = $error;
	}

	public function getErrors() {
		return $this->validation_errors;
	}

	public function getAutocomplete() {
		return Autocomplete::get_autocomplete($this->conn, array('hosts', 'nets', 'host_groups'));
	}

	public function load_sensors() {
        $filters = array(
            'where' => "sensor_properties.has_vuln_scanner = '1'"
        );
        list($this->all_sensors) = Av_sensor::get_list($this->conn, $filters);
	}

	public function load_profiles() {
		$conn = $this->conn;
		$args = '';
		if (!Session::am_i_admin())
		{
			list($owners) = Vulnerabilities::get_users_and_entities_filter($conn);
			$owners[] = '0';
			$sql_perms = " OR owner IN('".implode("', '",$owners)."')";
			$args = "AND `default` = 1 ".$sql_perms;
		}
		$query = "SELECT id, name, description, owner FROM vuln_nessus_settings WHERE `deleted` = '0' $args ORDER BY name";
		$conn->SetFetchMode(ADODB_FETCH_BOTH);
		$result = $conn->execute($query);
		$this->v_profiles = array();

		while (!$result->EOF)
		{
			$p_description = ($result->fields['description'] != '') ? ' - ' . $result->fields['description'] : '';
			$this->v_profiles[$result->fields['id']] = $result->fields['name'] . $p_description;
			$result->MoveNext();
		}
	}

	public function load_users() {
		$users           = Session::get_users_to_assign($this->conn);
		$this->users_to_assign = array();
		foreach ($users as $u_value) {
			$this->users_to_assign[$u_value->get_login()] = $u_value->get_login();
		}
	}
	public function load_entities() {
		$this->entities_to_assign = Session::get_entities_to_assign($this->conn);
		if (is_null($this->entities_to_assign))
			$this->entities_to_assign = array();
	}

	public function load_credentials($type) {
		$conn = $this->conn;
		$cred_a = Vulnerabilities::get_credentials($conn, $type);
		$arr  = array();
		foreach ($cred_a as $cred)
		{
			$login_text = $cred['login'];
			if ($login_text == '0' || valid_hex32($login_text))
			{
				$login_text =  ($login_text=='0') ? _('All') : Session::get_entity_name($conn, $cred['login']);
			}

			$cred_key = $cred['name'].'#'.$cred['login'];
			$arr[$cred_key] = $cred['name'] . ' (' . $login_text . ')';
		}
		return $arr;
	}


	/*
	 * $error - string - error text
	 * $check - callable - function to validate schedule parameter, should contain function ossim_valid call
	 * $key - mixed - parameter to verify - must be part of parameters array
	 * $error - (optional) callable - error callback
	 * $success - (optional) callable - success callback
	 */
	private function ossimValidate($error, $check, $key, $errorcb = null, $success = null) {
			$check($key);
			if (ossim_error()) {
				$this->addError($error);
				ossim_set_error(FALSE);
				if ($errorcb)
					$errorcb();
			} else {
				if ($success)
					$success();
			}
	}

	public function validate() {
		if ($this->parameters["timeout"] == '') {
			$this->addError(_('Invalid Timeout'));
		} else {
		    $this->ossimValidate(_('Invalid timeout'),
				function ($key) {
					ossim_valid($key, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _('Timeout'));
				},
				$this->parameters["timeout"]
			);
		}

        $decoded_job_name = htmlspecialchars_decode($this->parameters["job_name"], ENT_QUOTES);

        $this->ossimValidate(_('Invalid Job name'),
            function ($key) {
                ossim_valid($key, OSS_SCORE, OSS_ALPHA, OSS_SPACE, OSS_PUNC, 'illegal:' . _('Job name'));
            },
            $decoded_job_name
        );

        if (mb_strlen($decoded_job_name) > 50) {
            $this->addError(_('Job name cannot exceed the limit (Maximum 50 characters)'));
        }

		$this->ossimValidate(_('Invalid entity'),
				function ($key) {
					ossim_valid($key, OSS_NULLABLE, OSS_HEX, 'illegal:' . _('Entity'));
				},
				$this->parameters["entity"]
				);
		$this->ossimValidate(_('Invalid Net ID'),
				function ($key) {
					ossim_valid($key, OSS_NULLABLE, OSS_HEX, 'illegal:' . _('Net ID'));
				},
				$this->parameters["net_id"]
				);
		$this->ossimValidate(_('Invalid user'),
				function ($key) {
					ossim_valid($key, OSS_SCORE, OSS_NULLABLE, OSS_ALPHA, OSS_SPACE, '\.', 'illegal:' . _('User'));
				},
				$this->parameters["user"]
				);
		$this->ossimValidate(_('Invalid SSH Credential'),
				function ($key) {
 		            ossim_valid($key, OSS_ALPHA, OSS_SCORE, OSS_SPACE, OSS_DOT, OSS_AT, '#', OSS_NULLABLE, 'illegal:' . _("SSH Credential"));
				},
				$this->parameters["ssh_credential"]
				);

		if($this->parameters["ssh_credential"] != "")
            $this->ossimValidate(_('Invalid SSH Credential Port'),
                function ($key) {
                    ossim_valid($key, OSS_PORT_RANGE, 'illegal:' . _("Invalid SSH Credential Port"));
                },
                $this->parameters["ssh_credential_port"]
            );

		$this->ossimValidate(_('Invalid SMB Credential'),
				function ($key) {
	               ossim_valid($key, OSS_ALPHA, OSS_SCORE, OSS_SPACE, OSS_DOT, OSS_AT, '#', OSS_NULLABLE, 'illegal:' . _("SMB Credential"));
				},
				$this->parameters["smb_credential"]
				);
		if ($this->parameters["exclude_ports"] != "" && !preg_match("/^([0-9]{1,5}(,|-))*[0-9]{1,5}$/",$this->parameters["exclude_ports"])) {
			$this->validation_errors[] = _('Invalid Exclude Ports');
		}

		$this->ip_exceptions_list = array();
		$tip_target = array();
		$targets = array_keys($this->selected_targets);
		foreach($targets as $target) {
			$target_error = FALSE;
			$target = trim($target);
			if (preg_match("/^\!\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}(\/\d+)?$/",$target)) {
				$this->ip_exceptions_list[] = $target;
			} elseif(!preg_match("/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}(\/\d+)?|hostgroup|netgroup$/",$target)) {
				$this->ossimValidate(_('Invalid asset id'). ': ' . $target,
						function ($key) {
							ossim_valid($key, OSS_FQDNS , 'illegal: Host name');
						},
						$target,
						function () use ($target_error) {
							$target_error = true;
						},
						function () use ($target) {
							$tip_target[] = $target;
						}
						);
			} elseif(preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}(\/\d{1,2})?$/", $target)){
				$tip_target[] = $target;
			} else {
				list($asset_id, $ip_target) = explode("#", $target);
				$this->ossimValidate(_('Invalid asset id'). ': ' . $asset_id,
						function ($key) {
							ossim_valid($key, OSS_HEX, OSS_NULLABLE , 'illegal: Asset id');
						},
						$asset_id,
						function () use (&$target_error) {
							$target_error = true;
						}
						);
				$this->ossimValidate(_('Invalid target'). ': ' . $ip_target,
						function ($key) {
							ossim_valid($key, OSS_NULLABLE, OSS_DIGIT, OSS_SPACE, OSS_SCORE, OSS_ALPHA, OSS_PUNC, '\.\,\/\!', 'illegal:' . _("Target"));
						},
						$ip_target,
						function () use (&$target_error) {
							$target_error = true;
						}
						);
				if(!$target_error) {
					$tip_target[] = str_replace('!', '', $target);
				}
			}
		}
		if (count($tip_target)==0) {
			$this->validation_errors[] = _('Invalid Targets');
		}
	}

	public function resetImmediateJob() {
		// Delete scheduled jobs if "Immediately" scheduled method is selected
		if ($this->parameters["schedule_type"] == 'N' && isset($this->parameters["sched_id"]) && $this->parameters["sched_id"] >0) {
			$query  = 'DELETE FROM vuln_job_schedule WHERE id = ?';
			if (!$this->conn->Execute($query, array($this->parameters["sched_id"]))) {
				Av_exception::throw_error(Av_exception::DB_ERROR, $this->conn->ErrorMsg());
			}
		}
	}

	public function get_host_alive_attributes() {
	    $result = array();
	    $targets = (is_array($this->parameters["targets"])) ? implode('|', $this->parameters["targets"]) : '';
		$condition1 = (intval($this->parameters["hosts_alive"])==1);
		$condition2 = preg_match('/' . EXCLUDING_IP2 . '/', $targets);
		$result['checked']  = ($condition1 || $condition2) ? ' checked="checked"' : '';
		$result['disabled'] = ($condition2) ? ' disabled="disabled"' : '';
		return $result;
	}

	public function create_key_val_function($title,$name,$data,$id = null,$callback = null) {
		if ($title) {
			echo _($title)."&nbsp";
		}
		if (!$callback) {
			$callback = function($key,$value) {
				return $value;
			};
		}
		?>
		<select class="vfield <?php echo $name ?>" name='<?php echo $name;?>' <?php echo $id ? "id='$id'" : "" ?>>
		<?php
            foreach ($data as $key => $value) {
                ?>
                <option value="<?php echo $key ?>" <?php echo $this->parameters[$name] == $key ? 'selected="selected"' : "" ?>><?php echo $callback($key,$value) ?></option>
                <?php
            }
		?>
		</select>
		<?php
	}

	public function current_time_to_parameters($tz) {
		list(
			$this->parameters["biyear"],
			$this->parameters["bimonth"],
			$this->parameters["biday"],
			$this->parameters["time_hour"]
		//plus one hour to show always future date
		) = explode("-",$this->current_time($tz));
	}

    /**
     * This function will return the current date in the defined timezone
     *
     * @param int $tz           TimeZone to set the datetime
     * @param string $format    Format to see the datetime
     * @param int $offset       offset to the current date. For instance 1 means one hour more

     * @return string
     */

	public function current_time($tz, $format="Y-n-j-G", $offset=1) {
	    #time returns the datetime in UTC
		return date($format,time() + 3600 * ($tz+$offset));
	}
}
