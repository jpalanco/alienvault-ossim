<?php
/**
* Config.inc
*
* File Config.inc is used to:
*   - To manage user configuration
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
* @package    ossim-framework\Configuration
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/

require_once 'classes/Security.inc';


/**
* Config Class
*
* Class for getting and setting Framework settings from database 
*
* @package    ossim-framework\Configuration
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ Alienvault web page
*/
class Config 
{    
    /**
    * @var string  User UUID (Unique ID for user)
    * @access private 
    */
    private $uuid;
    
     
    /**
    * @var array Configuration array
    * @access private 
    *
    */
    private $confs;
    
    /**
    * @var object Database object
    * @access private 
    *
    */
    private $db;    
    
    
    /**
    * Class constructor
    *
    * This function sets up the class
    */
    public function __construct()
    {
        $this->confs = array();
        
        $this->db    = new ossim_db();
        $conn        = $this->db->connect();
        
        $this->uuid  = Util::get_encryption_key();
		$query       = "SELECT *, AES_DECRYPT(value,'".$this->uuid."') AS value_decrypt FROM config";
               
        if (!$rs = &$conn->Execute($query)) 
        {
            $this->db->close();
                        
            Av_exception::throw_error(Av_exception::USER_ERROR, $conn->ErrorMsg());
        } 
		
        while (!$rs->EOF) 
        {
            $key   = $rs->fields["conf"];
            if (preg_match("/_pass$/", $rs->fields['conf']) || preg_match("/_key$/", $rs->fields['conf'])) 
            {
                $value = (empty($rs->fields['value_decrypt'])) ? $rs->fields['value'] : $rs->fields['value_decrypt'];
            } 
            else 
            {
                $value = $rs->fields['value'];
            }
            
            $this->confs[$key] = $value;
            
            $rs->MoveNext();
        }
                
        $this->db->close();        
    }
    
    
    /**
    * This function returns TRUE if configuration key exists
    *
    * @param string $key  Key to search
    *
    * @return boolean
    */
    public function has_conf($key) 
    {
        return array_key_exists($key, $this->confs);
    }
    
    
	/**
    * This function returns the value for key $key
    *
    * @param string $key  Key to search
    *
    * @return string
    */
	public function get_conf($key) 
	{
        if ($this->has_conf($key))
        {
            return $this->confs[$key];
        }
        else
        {
            return NULL;
        }
    }
    
    
    /**
    * This function returns all configuration values available
    *
    * @return array
    */	
	public function get_list() 
	{
        return $this->confs;
    }
    
    
    /**
    * This function inserts a new configuration value (Pair key-value)
    *
    * @param string $key    New key 
    * @param string $value  Value of key
    *
    * @throws Exception  If a connection error occurred
    */    
    public function insert($key, $value) 
    {             
        ossim_valid($key,    OSS_LETTER, OSS_SCORE, OSS_DIGIT, 'illegal:' . _($key));
        ossim_valid($value,  OSS_NULLABLE,                     'illegal:' . _($value));
                
		if (ossim_error()) 
		{
            $exp_msg = ossim_get_error_clean();

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
		}
				   
		if (preg_match('/_pass$/', $key) || preg_match('/_key$/', $key)) 
		{
			$q_pass  = (!empty($value)) ? "AES_ENCRYPT(?,'".$this->uuid."')" : '?';
			$query = 'INSERT INTO config (conf, value) VALUES (?, '.$q_pass.')';			
		}
		else
		{
			$query    = "INSERT INTO config (conf, value) VALUES (?, ?)";
		}
		
		
		$params = array($key, $value);
		
		$conn   = $this->db->connect();
		
		if (!$conn->Execute($query, $params))
		{
    		Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
		} 
				
		$this->db->close();
    }
	
	
	/**
    * This function updates a configuration key 
    *
    * @param string $key    key to search 
    * @param string $value  New value
    *
    * @throws Exception  If a connection error occurred
    */ 
    public function update($key, $value) 
    {                
        ossim_valid($key, OSS_LETTER, OSS_SCORE, OSS_DIGIT, 'illegal:' . _($key));
        
        if (ossim_error()) 
		{
    		$exp_msg = ossim_get_error_clean();

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
		}
                
        $query  = "DELETE FROM config WHERE conf = ?";
        $params = array($key);        
        
        $conn   = $this->db->connect();
		
		if (!$conn->Execute($query, $params))
		{
    		Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
		} 
        				
        $this->db->close();
        
		$this->insert($key, $value);
    }
    
    
    /**
    * This function resets default values
    *
    * @throws Exception  If a connection error occurred
    */ 
    public function reset() 
    {
        Log_action::log(8, NULL);
        
        $query = "DELETE FROM config";        
        $conn  = $this->db->connect();
		
		if (!$conn->Execute($query))
		{
    		Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
		} 
			
		$this->db->close();		
		
		$this->common_reset();
		$this->debian_reset();
        
        $this->db->close();
    }
	
    
    /* Common values */
    public function common_reset() 
    {        
        $default_common_values = array(
            'snort_path'          => '/etc/snort/',  // Snort: Snort database and paths configuration
            'snort_rules_path'    => '/etc/snort/rules/',
            'snort_type'          => 'mysql',
            'snort_base'          => 'alienvault_siem',
            'snort_user'          => 'root',
            'snort_pass'          => 'ossim',
            'snort_host'          => 'localhost',
            'snort_port'          => '3306',
            
            'osvdb_type'          => 'mysql',  // Osvdb: Osvdb database configuration
            'osvdb_base'          => 'osvdb',
            'osvdb_user'          => 'root',
            'osvdb_pass'          => 'ossim',
            'osvdb_host'          => 'localhost',        
             
            'server_address'      => 'localhost', // Server: Configure where the server is listening to
            'server_port'         => '40001',            
            
            'recovery'            => '1',  // Metrics: Configure metric settings 
            'threshold'           => '30',            
            
            'phpgacl_path'        => '/usr/share/phpgacl/', // PHP: PHP Configuration (graphs, acls, database api) 
            'fpdf_path'           => '/usr/share/fpdf/',
            'report_graph_type'   => 'images',
            'use_svg_graphics'    => '0',
            'use_resolv'          => '0',
            'jpgraph_path'        => '/usr/share/jpgraph/',            
            
            'graph_link'          => '../report/graphs/draw_rrd.php', // RRD: RRD Configuration (graphing) 
            'rrdtool_lib_path'    => '/usr/lib/perl5/',
            'rrdtool_path'        => '/usr/bin/',
            'mrtg_path'           => '/usr/bin/',
            'mrtg_rrd_files_path' => '/var/lib/ossim/rrd/',
            'rrdpath_host'        => '/var/lib/ossim/rrd/host_qualification/',
            'rrdpath_net'         => '/var/lib/ossim/rrd/net_qualification/',
            'rrdpath_global'      => '/var/lib/ossim/rrd/global_qualification/',
            'rrdpath_level'       => '/var/lib/ossim/rrd/level_qualification/',            
             
            'ntop_link'           => 'http://localhost/ntop', // Links: Links to other applications
            'use_ntop_rewrite'    => '0',
            'ossim_link'          => '/ossim/',
            'nagios_link'         => '/nagios/',
            'use_munin'           => '0',
            'munin_link'          => '/munin/',
                                
            'acid_user'           => 'admin', // ACID: Acid cache configuration
            'acid_pass'           => 'admin',
            'ossim_web_user'      => 'admin',
            'ossim_web_pass'      => 'admin',            
             
            'backup_type'         => 'mysql', // Backup: Backup configuration: backup database, directory, interval
            'backup_base'         => 'alienvault_siem',
            'backup_user'         => 'root',
            'backup_pass'         => 'ossim',
            'backup_host'         => 'localhost',
            'backup_port'         => '3306',
            'backup_dir'          => '/var/lib/ossim/backup',
            'backup_day'          => '5',            
             
            'nessus_user'         => 'ossim', // Nessus: Nessus client configuration
            'nessus_pass'         => 'ossim',
            'nessus_host'         => 'localhost',
            'nessus_port'         => '1241',
            'nessus_distributed'  => '0',
            'nessus_path'         => '/usr/bin/nessus',
            'nessus_rpt_path'     => '/usr/share/ossim/www/vulnmeter/',                    
            
            'nmap_path'           => '/usr/bin/nmap', // External applications: Path to other applications
            'p0f_path'            => '/usr/sbin/p0f',
            'arpwatch_path'       => '/usr/sbin/arpwatch',
            'mail_path'           => '/usr/bin/mail',
            'touch_path'          => '/bin/tail',
            'wget_path'           => '/usr/bin/wget',
            'have_scanmap3d'      => '0',            
            
            'language'            => 'en_GB', // Language
            'locale_dir'          => '/usr/share/locale',            
            
            'user_action_log'     => '1', // User action logging
            'log_syslog'          => '1',
            
            'max_event_tmp'       => '10000', // Real time event viewer
            
            'login_enforce_existing_user' => 'yes', // Login option
            'login_enable_ldap'           => 'no',
            'login_ldap_server'           => '127.0.0.1',
            'login_ldap_o'                => 'o=company',
            'login_ldap_ou'               => 'ou=people',
            'login_ldap_dns_domain'       => 'domain.local'        
        ); 
                
        $values[] = array();
                
        foreach ($default_common_values as $key => $value)
        {
            ossim_valid($key,    OSS_LETTER, OSS_SCORE, OSS_DIGIT, 'illegal:' . _($key));
            ossim_valid($value,  OSS_NULLABLE,                     'illegal:' . _($value));
                    
    		if (ossim_error()) 
    		{
                $exp_msg = ossim_get_error_clean();
                
                ossim_clean_error();          

                Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
    		}
                        
            if ( preg_match('/_pass$/', $key) || preg_match('/_key$/', $key)) 
    		{
    			$q_pass     = (!empty($value)) ? "AES_ENCRYPT(?,'".$this->uuid."')" : '?';
    			$values[] = "?, ".$q_pass."";			
    		}
    		else
    		{
    			$values[] = '?, ?';
    		}
    	
    		$params[] = $key;
    		$params[] = $value;    				
    	}			
		
		if (is_array($values) && !empty($values) )
		{
    		$conn = $this->db->connect();
		
    		$query  = "INSERT INTO config (conf, value) VALUES (".implode("),(", $values).");";
    				
    		if (!$conn->Execute($query, $params))
    		{
        		Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
    		} 
                            			
    		$this->db->close();    		
		}
    }
    
    
    /* Debian specific values */
    public function debian_reset() 
    {
        $default_debian_values = array(
            'adodb_path',   '/usr/share/php/adodb/',  // PHP: PHP Configuration (graphs, acls, database api)
            
            'rrdpath_ntop', '/var/lib/ntop/rrd',   // RRD: RRD Configuration (graphing)
            'font_path',    '/usr/share/fonts/truetype/ttf-bitstream-vera/Vera.ttf',
            
            'event_viewer', 'base', // ACID: Acid cache configuration 
            'acid_link',    '/ossim/forensics/',
            'acid_path',    '/usr/share/ossim/www/forensics/'
        );
        
        $values[] = array();
                
        foreach ($default_debian_values as $key => $value)
        {
            ossim_valid($key,    OSS_LETTER, OSS_SCORE, OSS_DIGIT, 'illegal:' . _($key));
            ossim_valid($value,  OSS_NULLABLE,                     'illegal:' . _($value));
                    
    		if (ossim_error()) 
    		{
        		$exp_msg = ossim_get_error_clean();

                Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
    		}
                        
            if ( preg_match('/_pass$/', $key) || preg_match('/_key$/', $key)) 
    		{
    			$q_pass = ( !empty($value) ) ? "AES_ENCRYPT(?,'".$this->uuid."')" : '?';
    			$values[] = '?, '.$q_pass.'';			
    		}
    		else
    		{
    			$values[] = '?, ?';
    		}
    	
    		$params[] = $key;
    		$params[] = $value;
    				
    	}			
		
		if (is_array($values) && !empty($values))
		{
    		$conn = $this->db->connect();
		
    		$query  = 'INSERT INTO config (conf, value) VALUES ('.implode('),(', $values).');';
    				
    		if (!$conn->Execute($query, $params))
    		{
        		Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
    		} 
                            			
    		$this->db->close();    		
		}
    }
        
    
    /* default values */
    public function default_reset() 
    {
        $this->debian_reset();
    }

}

/* End of file config.inc */
/* Location: ../include/classes/config.inc */
