<?php
/**
*
* Copyright (c) 2007-2013 AlienVault
* All rights reserved.
*
* @package    alienvault-wizard\Reports
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/
set_time_limit(0);
ini_set('memory_limit', '2048M');
error_reporting(E_ERROR);

class Report_feed
{
    CONST SQL_LOAD_FILE = "resources/load.sql";
    CONST AVR_DIR = "avr/";
    
    public $report_list;
    public $module_list;
    
    private $module_tr;
    
    
    public function __construct()
    {
        $this->report_list = array();
        $this->module_list = array();
        $this->module_tr   = array();
    }
    
    
    public function load_report_feed()
    {
        $avr_dir   = self::get_avr_dir();
        $avr_files = array();
        
        if ($handle = opendir($avr_dir))
        {
            while ($file = readdir($handle))
            {
                if (preg_match('/\.avr/',$file))
                {
                    $this->load_report_data($avr_dir . $file);
                }
            }
        }
        
        $this->translate_modules();
    }
    
    
    private function load_report_data($file)
    {
        $category = '';
        $password = ''; // Change password if needed
        $user     = 'admin';
        
        if (preg_match('/#/', $file))
        {
            list($category, $_name) = explode('#', basename($file), 2);
            $category = trim(str_replace(' ', '_', $category));
        }
            
        if (file_exists($file) && filesize($file) > 0)
        {
            $content           = file_get_contents($file);
            $decrypted_content = self::decrypt($content, $password);
            $datareport        = @unserialize($decrypted_content);
            $report_name       = $datareport["name"];
            $report_data       = @unserialize($datareport["report"]);
    
            $c1 = $report_data;
            $c2 = self::validate($report_name, 'A-Za-z0-9\s\.,:@_\-\/\?&\=_\-\;\#\|');
            $c3 = self::validate($category, 'A-Za-z0-9\s\.,:@_\-\/\?&\=_\-\;\#\|');
            
            if (!$c1 || !$c2 || !$c3)
            {
                return false;
            }

            // Force some parameters
            $report_data["profile"]  = 'Default';
            $report_data["user"]     = 0;
            $report_data["entity"]   = '-1';
            $report_data["category"] = (!empty($category)) ? str_replace('_',' ',$category) : '';
            $category                = (!empty($category)) ? '_' . $category : '';

            // check subreports ids
            $newds       = array();
            $sub_reports = $datareport["sr"];
            $mod_list    = array();
            
            foreach ($sub_reports as $idr => $info)
            {
                $mod_list[$idr] = $report_data["ds"][$idr];
                
                if ($idr > 2999)
                {
                    // Remove Custom Security Events in type
                    if ($info[1] == 'Custom Security Events')
                    {
                        $info[1] = $info[0];
                    }
                    $this->module_list[$idr] = $info;
                }
            }
            
            $report = array(
                'login'    => $user,
                'name'     => $report_name,
                'category' => 'custom_report' . $category,
                'config'   => $report_data,
                'modules'  => $mod_list,
            );
            
            $this->report_list[] = $report;
        }       
    }
    
    
    public function translate_modules()
    {
        $new_modules = array();
        
        foreach ($this->module_list as $id => $info)
        {
            $m_id = $this->find_module_id($info);
            
            if ($m_id > 500000)
            {
                $this->module_tr[$id] = $m_id;
            }
            else
            {
                $new_modules[$id] = $id;
            }
        }
        
        foreach ($new_modules as $id)
        {
            $new_id = @max($this->module_tr);
            if (!$new_id)
            {
                $new_id = 500000;
            }
            
            $this->module_tr[$id] = ++$new_id;
        }
        
        foreach ($this->report_list as &$report)
        {
            $modules = array();
            
            foreach ($report['modules'] as $m_id => $mod)
            {
                $new_id = ($m_id > 2999) ? $this->module_tr[$m_id] : $m_id;
                $modules[$new_id] = $mod;
            }
            
            $report['config']['ds'] = $modules;
        }
        
    }
    
    
    public function get_feed_sql()
    {
        $sql = '';
        $initial_load = @file_get_contents(self::SQL_LOAD_FILE);
            
        if (!empty($initial_load))
        {
            $sql .= $initial_load;
        
            $sql .= $this->get_module_sql();
            
            $sql .= $this->get_report_sql();
        }
        
        return $sql;
    }
    
    
    private function get_report_sql()
    {
        $sql = '';
        foreach ($this->report_list as $id => $report)
        {
            $login    = self::qstr($report['login']);
            $category = self::qstr($report['category']); 
            $name     = self::qstr($report['name']);
            $config   = base64_encode(serialize($report['config']));
            
            $sql .= "REPLACE INTO alienvault.user_config (login, category, name, value) VALUES ('$login', '$category', '$name', from_base64('$config'));\n";
        }
        
        return $sql; 
    }
    
    private function get_module_sql()
    {
        $query = '';
        foreach ($this->module_list as $id => $module)
        {
            $m_id   = $this->module_tr[$id];
            $name   = self::qstr($module[0]); 
            $type   = self::qstr($module[1]);
            $file   = self::qstr($module[2]);
            $inputs = self::qstr($module[3]);
            $sql    = self::qstr($module[4]);
            $dr     = self::qstr($module[5]);
            
            $query .= "REPLACE INTO alienvault.custom_report_types (`id`, `name`, `type`, `file`, `inputs`, `sql`, `dr`)
                    VALUES ('$m_id', '$name', '$type', '$file', '$inputs', '$sql', '$dr');\n";
        }
        return $query;
    }
    
    
    private static function get_avr_dir()
    {
        return self::AVR_DIR . 'v1/';
    }
    
    
    private static function print_err($msg)
    {
        file_put_contents('php://stderr', "$msg\n");
    }
    
    
    private static function decrypt($encrypted_input_string, $key)
    {
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
        $iv      = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $h_key   = hash('sha256', $key, TRUE);
        
        return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $h_key, base64_decode($encrypted_input_string), MCRYPT_MODE_ECB, $iv));
    }
    
    
    private static function validate($val, $regexp)
    {
        return !preg_match("/[^$regexp]/",$val);
    }


    private function find_module_id($data)
    {
        $query = "SELECT DISTINCT c.id FROM alienvault.custom_report_types c 
                    WHERE c.name='". self::qstr($data[0]) ."' AND c.type='". self::qstr($data[1]) ."' 
                    AND c.file='". self::qstr($data[2]) ."' AND c.inputs='". self::qstr($data[3]) ."' 
                    AND c.sql='". self::qstr($data[4]) ."' LIMIT 1";
        
        $cr_id = intval(exec("echo \"$query\" | ossim-db"));

        return $cr_id;
    } 
    
    
    private static function qstr($unescaped)
    {
        $replacements = array(
            '\x00' => '\x00',
            '\n'   => '\n',
            '\r'   => '\r',
            '\\'   => '\\\\',
            "'"    => "\'",
            '"'    => '\"',
            '\x1a' => '\x1a'
        );
        
        return strtr($unescaped, $replacements);
    }
}

$feed = new Report_feed();
$feed->load_report_feed();
echo $feed->get_feed_sql();
