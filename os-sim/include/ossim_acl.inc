<?php
/**
* ossim_acl.inc
*
* File ossim_acl.inc is used to:
*   - Task 1 (TO DO)
*   - Task 2 (TO DO)
*   - Task 3 (TO DO)
*
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
* @package    ossim-framework\Session
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


define("ACL_DEFAULT_USER_SECTION", "users");
define("ACL_DEFAULT_USER_GROUP", "users");
define("ACL_DEFAULT_OSSIM_ADMIN", "admin");
define("ACL_DEFAULT_DOMAIN_SECTION", "DomainAccess");
define("ACL_DEFAULT_DOMAIN_ALL", "All");
define("ACL_DEFAULT_DOMAIN_LOGIN", "Login");
define("ACL_DEFAULT_DOMAIN_NETS", "Nets");
define("ACL_DEFAULT_DOMAIN_SENSORS", "Sensors");

/* get phpgacl config */
require_once ('ossim_conf.inc');
$conf = $GLOBALS["CONF"];
if (!$conf) {
	require_once 'ossim_db.inc';
	require_once 'ossim_conf.inc';
	$conf = new Ossim_conf();
	$GLOBALS["CONF"] = $conf;
}
/* include default $gacl_options */
//require_once ("$phpgacl_path/admin/gacl_admin.inc.php");

$ACL_OPTIONS = array(
    /*
    'debug'                     => $gacl_options['debug'],
    'items_per_page'            => $gacl_options['items_per_page'],
    'max_select_box_items'      => $gacl_options['max_select_box_items'],
    'max_search_return_items'   => $gacl_options['max_search_return_items'],
    */
    'db_type' => $conf->get_conf("ossim_type") ,
    'db_host' => $conf->get_conf("ossim_host") ,
    'db_user' => $conf->get_conf("ossim_user") ,
    'db_password' => $conf->get_conf("ossim_pass") ,
    'db_name' => $conf->get_conf("ossim_base") ,
    /*
    'db_table_prefix'           => $gacl_options['db_table_prefix'],
    'caching'                   => $gacl_options['caching'],
    'force_cache_expire'        => $gacl_options['force_cache_expire'],
    'cache_dir'                 => $gacl_options['cache_dir'],
    'cache_expire_time'         => $gacl_options['cache_expire_time']
    */
);
//$phpgacl_path = $conf->get_conf("phpgacl_path");
/*
* phpgacl on "new gacl()" does a lot of heavy operations like:
* parsing files, including code and opens a new persistant
* connection to the BD. So better make $gacl global
*/
//$phpgacl = $GLOBALS["CONF"]->get_conf("phpgacl_path");
//require_once $phpgacl . DIRECTORY_SEPARATOR . 'gacl.class.php';
//require_once $phpgacl . DIRECTORY_SEPARATOR . 'gacl_api.class.php';

// $Id: gacl.class.php 422 2006-09-03 22:52:20Z ipso $

if ( !defined('ADODB_DIR') ) {
   define('ADODB_DIR', '/usr/share/php/adodb');
}

class gacl {
	private $_debug = FALSE;
	private $_db = '';
	private $_db_host = '';
	private $_db_type = '';
	private $_db_user = '';
	private $_db_password = '';
	private $_db_name = '';

	public function __construct($options = NULL) {

        $this->_db_host = $options['db_host'];
        $this->_db_type = $options['db_type'];
        $this->_db_user = $options['db_user'];
        $this->_db_password = $options['db_password'];
        $this->_db_name = $options['db_name'];

		require_once( ADODB_DIR .'/adodb.inc.php');
		require_once( ADODB_DIR .'/adodb-pager.inc.php');

		if (is_object($this->_db)) {
			$this->db = &$this->_db;
		} else {
			$this->db = ADONewConnection($this->_db_type);
			//Use NUM for slight performance/memory reasons.
			$this->db->SetFetchMode(ADODB_FETCH_NUM);
			$this->db->NConnect($this->_db_host, $this->_db_user, $this->_db_password, $this->_db_name);
		}
		$this->db->debug = $this->_debug;

		return true;
	}

	function __destruct () {
		if (is_object($this->db)) $this->db->Close(); 
	}
	
}

$GLOBALS['ACL'] = new gacl($ACL_OPTIONS);
?>
