<?php
/**
* KDB_Parser.inc
*
* File KDB_Parser.inc is used to:
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
* @package    ossim-framework\KDB
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/
require_once 'av_init.php';


class KDB_Parser{

	private $db;
	private $conn;

	private $_line;
	
	private $_stack;
	private $_stack_p1;
	private $_stack_p2;
	
	private $_top_stack;
	private $_flag_cond;
	private $_flag_print;
	
	private $_sintax;
	
	public $_error;
	public $_buffer;
	public $_fns;
	
	//Mark time stack and top
	private $_time_mark;
	private $_time_stack;
	
	//Section stack
	private $_sec_flag;
	private $_sec_name;
	private $_sec_stack;
	private $_sec_current;
	

	/******* Public Functions ******/
	
	function __construct() 
	{		
		/* Connect to db */
		$this->db         = new ossim_db();
		$this->conn       = $this->db->connect();
		
		$this->_fns       = new KDB_Functions();
		$this->_sintax    = new KDB_Sintax();
		
	}
	
	function __destruct()
    {		
		$this->db->close($this->conn);	

    }
	
		
	
	function proccess_file($file, $id=0, $sec='')
	{	
		$this->init_vars();
				
		if($id != 0)
		{
			$_SESSION['_kdb']['_includes'][$id]++;
		}
		
		if($sec != '')
		{
			$this->_sec_name = $sec;
		}
		
		$file = preg_replace("/<br>/", "\n", $file);
		$text = explode("\n", $file);
		

		foreach($text as $line)
		{
			$this->_line++;
			
			$txt = $line;

			if(!$line = $this->is_control_line($line))
			{
				if($this->can_i_print())
				{
					$txt = $this->process_text($txt);
					$this->_buffer[] = $txt;
				}
				else
				{
					continue;
				}
			}
			else
			{	
				$this->process_control_line($line);
			}	
			
		}
		
		$this->process_stack_end();	
		
		if($id != 0)
		{
			$_SESSION['_kdb']['_includes'][$id]--;
		}
		else
		{
			unset($_SESSION['_kdb']['_includes']);
		}
		
	}
	
	function is_valid()
	{
		return $this->_error;
	}
	
	function print_text()
	{
		$text = implode("\n", $this->_buffer);
		
		if(!empty($text))
		{
			$wiki = new Wikiparser();
			$text = $wiki->parse($text);
		}
		
		return trim($text);
		
	}
	
	function get_text()
	{
		$text = implode("\n", $this->_buffer);

		return $text;
		
	}
	
	

	/******* Private Functions ******/
	
	private function init_vars()
	{
		$this->_stack       = array();
		$this->_error       = array();
		$this->_buffer      = array();
		
		$this->_top_stack   = 0;
		$this->_line        = 0;
		$this->_flag_print  = 1;
		$this->_flag_cond   = 1;
		
		$this->_stack_p1    = array();
		$this->_stack_p2    = array();
		
		$this->_time_mark   = 0;
		$this->_time_stack  = array();
		
		$this->_sec_flag    = 0;
		$this->_sec_name    = '';
		$this->_sec_current = '';
		$this->_sec_stack   = array();
		
	}
	
	
	/*private function _trim($txt)
	{
		$txt = preg_replace("/\s+/"," ",$txt);

		return trim($txt);
	}*/
	
	
	private function process_error($code, $extra = '')
	{
		$er_msg = _("Sintax Error found in line ") . $this->_line . ": " . $this->_sintax->get_sintax_error($code);
		
		if(!empty($extra))
		{	
			$er_msg .= " [".$extra."]";
		}
		
		$this->_error[] = $er_msg;
	}
	
	
	private function process_text($txt)
	{
		
		preg_match_all("/" . $this->_sintax->_inline_variable . "/", $txt, $match);
		
		$replaces = array_unique($match[1]);
		
		foreach($replaces as $var){
		
			if($this->check_var(trim($var)) == false)
			{
				continue;
			}
		
			$var = str_replace('$', '', $var);
			
			$rep = $_SESSION['_kdb']['_'.$var];
			
			$rep = (empty($rep)) ? strtoupper($var) : $rep ;
		
			$txt = str_replace('(($'.$var.'))', $rep, $txt);
		
		}
		
		preg_match_all("/" . $this->_sintax->_inline_link . "/", $txt, $match);
		
		$replaces = array_unique($match[1]);
		
		foreach($replaces as $var){
		
			$rep = $_SESSION['_kdb']['_'.$var];
			
			$rep = (empty($rep)) ? strtoupper($var) : $rep ;
		
			$txt = str_replace('[[$'.$var.']]', "<a href='javascript:;'>$rep</a>", $txt);
		
		}
			
		return $txt;	
		
	}
	
	
	private function is_control_line($line)
	{

		$line = strip_tags($line);
		$line = Util::htmlentities($line);

		$line = str_replace('&nbsp;'," ",html_entity_decode($line)); 
		
		$line = trim($line);

		preg_match("/".$this->_sintax->_control_line."/", $line, $match);
		
		
		$line = $match[1];
		
		$line = mb_convert_encoding($line, 'UTF-8', 'HTML-ENTITIES');
		
		return trim($line);
	
	}
	
	
	private function process_stack_end()
	{
		foreach($this->_stack as $tag)
		{
			$this->process_error('cond5', $tag[0]);		
		}

		foreach($this->_sec_stack as $sec)
		{
			$this->process_error('sec4', $sec);		
		}			
	}
	
	
	private function can_i_print()
	{
		if(!empty($this->_error))
		{
			return false;
		}
		
		
		$aux1 = $this->_flag_print || empty($this->_stack_p2);
		$aux2 = $this->_flag_cond  || empty($this->_stack_p1);
		
		$aux3 = true;
		
		if($this->_sec_name != '' && ($this->_sec_name != $this->_sec_current ))
		{
			$aux3 = false;
		}
		
		if($aux1 && $aux2 && $aux3)
		{
			return true;
		}
		
		return false;
	}
	
	
	
	private function process_control_line($line)
	{
	
		$action = $this->check_actions($line);
		if($action)
		{
			return false;
		}
		
		list($cond, $body) = $this->check_conditional_sintax($line);
		if(!$cond)
		{
			return false;
		}
		

		if($cond == 'IF')
		{
			$params = $this->check_params_sintax($body);
			if(!$params)
			{
				return false;
			}
		}
		
		$this->process_condition($cond, $params);
		
	}
	

	private function check_actions($line){
		
		$regex  = "/(";
		$regex .= $this->_sintax->build_regexp_from_array($this->_sintax->_action_elements);
		$regex .= ")/";
		
		
		preg_match($regex, $line, $match);
		

		if(empty($match[1]))
		{
			return false;
		}
		
		$this->process_actions($match[1], $line);
		
		return true;
		
	}
	
	
	private function process_actions($ac, $data){
		
		
		switch($ac)
		{
			case '$INCLUDE':
				
				$this->process_include($data);
				
				break;
			
			case 'SECTION':
			case 'ENDSECTION':
				$this->process_section($ac, $data);			
				break;
								
		}
		
	}
	
	
	private function process_condition($cond, $params){
		
		switch($cond)
		{
			case 'IF':

				$function = $this->_sintax->get_function($params[0]);
				$var      = $params[1];
				$val      = $params[2];
							

				$response = call_user_func(array($this->_fns, $function), $var, $val);
				
				$this->_stack_p1[]  = $this->_flag_cond;
								
				if($this->_flag_cond == 1)
				{
					$this->_flag_print = intval($response);
					$this->_flag_cond  = $this->_flag_print;
				}

				$this->_stack_p2[]  = $this->_flag_print;

				break;
				
			
			
			case 'ELSE':
								
				$this->_stack_p1[]  = $this->_flag_cond;
								
				if($this->_flag_cond == 1)
				{
					$this->_flag_print = 1 - $this->_flag_print;
					$this->_flag_cond  = $this->_flag_print;
				}


				$this->_stack_p2[]  = $this->_flag_print;
				
				break;
					
			
			case 'ENDELSE':
			case 'ENDIF':
				
				$this->_flag_cond  = array_pop($this->_stack_p1);
				$this->_flag_print = array_pop($this->_stack_p2);
	
				break;
		
		}
		
	}
	
			
	
	private function check_conditional_sintax($line)
	{
		
		$regex = "/^\b(";
		$regex .= $this->_sintax->build_regexp_from_array($this->_sintax->_condition_elements);
		$regex .= ")\b/";
		
		preg_match($regex, $line, $match);
			
		
		$token = $match[0];
		

		if(empty($token))
		{
			$this->process_error('cond1');
			return false;
		}

		switch($token)
		{
			case 'IF':
								
					$this->_stack[]   = array('IF', $this->_line, $show_cond);
					$this->_top_stack = 0;
					
					$regex = "/NOW$/";					
					preg_match($regex, $line, $match);
						

					$this->_time_stack[] = $this->_time_mark;
					
					$this->_time_mark = 0;
					
					if(!empty($match[0]))
					{
						$line = str_replace('NOW', '', $line);
						$this->_time_mark = 1;
					}
					
					break;
			
			
			case 'ENDIF':
			
					$top = array_pop($this->_stack);
					if($top[0] != 'IF')
					{
						$this->_stack[] = $top;
						$this->process_error('cond2');
						return false;
					}

					$this->_top_stack = 1;
					
					$this->_time_mark = array_pop($this->_time_stack);
					
					break;
			
			
			case 'ELSE':
			
					if($this->_top_stack == 1)
					{
						$this->_stack[] = array('ELSE', $this->_line, $show_cond);
						$this->_top_stack = 0;
					}
					else
					{
						$this->process_error('cond3');
						return false;

					}
					
					break;
			
			
			case 'ENDELSE':
			
					
					$top = array_pop($this->_stack);
					if($top[0] != 'ELSE')
					{
						$this->_stack[] = $top;
						$this->process_error('cond4');
						return false;
					}

					$this->_top_stack = 0;
					
					break;
			
			
			default:
				$this->process_error('cond1');
				return false;
	
		}
		
		
		$line = str_replace($token, '', $line);
		
		return array($token, trim($line));		
		
	}
	
	
	private function check_params_sintax($line)
	{
		$regex = "/";
		$regex .= $this->_sintax->build_regexp_from_array($this->_sintax->_operations_elements);
		$regex .= "/";
		

			
		preg_match($regex, $line, $match);

		if(empty($match[0]))
		{
			$this->process_error('act1', $line);
			return false;
		}
		
		$op       = $match[0];
		$type     = $this->_sintax->get_type_operator($op);
		$operands = trim(str_replace($op,'',$line));
		
		
		switch($type)
		{
			case 2:
					$response = $this->check_binary_operand($operands);
					break;
			
			case 3:
					$response = $this->check_ternary_operand($operands);
					break;			
		
		}
		
		if(!$response)
		{
			return false;
		}
		
		return array($op, $response[0], $response[1]);
		
	}
	
	
	private function check_binary_operand($operands)
	{
		if(empty($operands))
		{
			$this->process_error('oper1');
			return false;
		}
		
		$var = $this->check_var(trim($operands));
		
		if($var)
		{
			return array($var, '');
		}
		else
		{
			return false;
		}
		
	}
	
	
	private function check_ternary_operand($operands)
	{

		$regex = "/^\s*" . $this->_sintax->_generic_variable . "\s+(" . $this->_sintax->_generic_value . ")\s*$/";
		
		preg_match($regex, $operands, $match);

		if(empty($match[0]))
		{
			$this->process_error('oper2');
			return false;
		}
		
		$var   = $this->check_var(trim($match[1]));
		$value = $this->check_value(trim($match[2]));
		
		if($var && $value)
		{
			return array($var, $value);
		}
		else
		{
			return false;
		}
	}
	
	
	
	private function check_var($var)
	{
		$regex = "/^(";
		$regex .= $this->_sintax->build_regexp_from_array($this->_sintax->_variable_list);
		$regex .= ")$/";

		preg_match($regex, $var, $match);
			
		if(empty($match[0]))
		{
			$this->process_error('var1', $var);
			return false;
		}
		
		return $match[1];
	}
	
	private function check_value($val)
	{

		$regex = "/^" . $this->_sintax->_generic_value . "$/";

		preg_match($regex, $val, $match);

		if(empty($match[0]))
		{
			$this->process_error('val1', $val);
			return false;
		}
		
		return $match[1];
		
	}
	
	private function process_include($data)
	{

		$data  = trim(str_replace('$INCLUDE', '', $data));
		

		$regex = "/^(\d+)\s*(\s+";
		$regex .= $this->_sintax->build_regexp_from_array($this->_sintax->_sections);
		$regex .= ")?$/";
		

		preg_match($regex, $data, $match);

		$kdb_id  = trim($match[1]);
		$kdb_sec = trim($match[2]);
		
		if(empty($kdb_id))
		{
			$this->process_error('act2', $match[1]);
			return false;
		}
		
		$data  = trim(str_replace($kdb_id,  '', $data));
		$data  = trim(str_replace($kdb_sec, '', $data));
		
		if(!empty($data))
		{
			$this->process_error('act4', $data);
			return false;
		}
		
		if($this->can_i_print())
		{
			if($_SESSION['_kdb']['_includes'][$kdb_id] < 1)
			{
			
				$document = Repository::get_document($this->conn, $kdb_id);
				
				if(!is_array($document) || empty($document))
				{
					$this->process_error('act3', $kdb_id);
					return false;
				
				}
				
				$text = $document['text'];
				
				$parser_aux = new KDB_Parser();
				$parser_aux->proccess_file($text, $kdb_id, $kdb_sec);
				
				$this->_buffer[] = $parser_aux->get_text();
			}
		}
		return false;
		
	}
	
	
	private function process_section($sec, $data)
	{

		$data = trim(str_replace($sec, '', $data));
		
		if($sec == 'SECTION')
		{
			if(!$this->_sintax->_sections[$data])
			{
				$this->process_error('sec1', $data);
				return false;
				
			}
			
			if(count($this->_sec_stack) > 0)
			{
				$this->process_error('sec2', $data);
				return false;
				
			}
			
			$this->_sec_current = $data;
			$this->_sec_stack[] = $data;
			
		}		
		elseif($sec == 'ENDSECTION')
		{
		
			if(count($this->_sec_stack) < 1)
			{
				$this->process_error('sec3', $data);
				return false;
				
			}
			
			array_pop($this->_sec_stack);
			$this->_sec_current = '';
			
		}
		
		return false;
		
	}
	
	public function load_session_vars($vars)
	{
	
		unset($_SESSION['_kdb']);
		
		$_SESSION['_kdb']['_SENSOR']            = $vars['_SENSOR'];  
		$_SESSION['_kdb']['_SRCIP']             = $vars['_SRCIP'];     
		$_SESSION['_kdb']['_SRCMAC']            = $vars['_SRCMAC'];           
		$_SESSION['_kdb']['_DSTIP']             = $vars['_DSTIP'];             
		$_SESSION['_kdb']['_DSTMAC']            = $vars['_DSTMAC'];            
		$_SESSION['_kdb']['_SRCPORT']           = $vars['_SRCPORT'];            
		$_SESSION['_kdb']['_DSTPORT']           = $vars['_DSTPORT'];           
		$_SESSION['_kdb']['_SRCCRITICALITY']    = $vars['_SRCCRITICALITY'];     
		$_SESSION['_kdb']['_DSTCRITICALITY']    = $vars['_DSTCRITICALITY'];     
		$_SESSION['_kdb']['_SRCUSER']           = $vars['_SRCUSER'];            
		$_SESSION['_kdb']['_FILENAME']          = $vars['_FILENAME'];          
		$_SESSION['_kdb']['_USERDATA1']         = $vars['_USERDATA1'];         
		$_SESSION['_kdb']['_USERDATA2']         = $vars['_USERDATA2'];         
		$_SESSION['_kdb']['_USERDATA3']         = $vars['_USERDATA3'];          
		$_SESSION['_kdb']['_USERDATA4']         = $vars['_USERDATA4']; 
		$_SESSION['_kdb']['_USERDATA5']         = $vars['_USERDATA5']; 
		$_SESSION['_kdb']['_USERDATA6']         = $vars['_USERDATA6']; 
		$_SESSION['_kdb']['_USERDATA7']         = $vars['_USERDATA7']; 
		$_SESSION['_kdb']['_USERDATA8']         = $vars['_USERDATA8']; 
		$_SESSION['_kdb']['_USERDATA9']         = $vars['_USERDATA9']; 
		$_SESSION['_kdb']['_ALARMRISKSCORE']    = $vars['_ALARMRISKSCORE']; 
		$_SESSION['_kdb']['_ALARMRELIABILITY']  = $vars['_ALARMRELIABILITY']; 
		$_SESSION['_kdb']['_SRCREPACTIVITY']    = $vars['_SRCREPACTIVITY']; 
		$_SESSION['_kdb']['_DSTREPACTIVITY']    = $vars['_DSTREPACTIVITY']; 
		$_SESSION['_kdb']['_SRCREPRELIABILITY'] = $vars['_SRCREPRELIABILITY']; 
		$_SESSION['_kdb']['_DSTREPRELIABILITY'] = $vars['_DSTREPRELIABILITY']; 
		$_SESSION['_kdb']['_HOST_NAME']         = $vars['_HOST_NAME']; 
		$_SESSION['_kdb']['_HOST_IP']           = $vars['_HOST_IP']; 
		$_SESSION['_kdb']['_HOST_FQDN']         = $vars['_HOST_FQDN']; 
		$_SESSION['_kdb']['_HOST_DESC']         = $vars['_HOST_DESC']; 
		$_SESSION['_kdb']['_NET_CIDR']          = $vars['_NET_CIDR']; 
		$_SESSION['_kdb']['_NET_NAME']          = $vars['_NET_NAME']; 
		$_SESSION['_kdb']['_HG_NAME']           = $vars['_HG_NAME']; 
		$_SESSION['_kdb']['_NG_NAME']           = $vars['_NG_NAME']; 
	
	}
	
}
?>