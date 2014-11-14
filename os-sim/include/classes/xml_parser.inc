<?php
/**
* Xml_parser.inc
*
* File Xml_parser.inc is used to:
*   - Task 1 (TO DO)
*   - Task 2 (TO DO)
*   - Task 3 (TO DO)
*
*
* Modifications: 
*    - Added node identifier (Francisco Javier Rubio Campos)
*    - Added xml tag if it isn't (Francisco Javier Rubio Campos)
*    - Changed error handler. 
*
* @package    ossim-framework\Ossec
* @version 1.0.0.0
* @autor      Ricardo Alexandre Sismeiro <ricardo@sismeiro.com>
* @license http://www.gnu.org/licenses/gpl.txt GNU GENERAL PUBLIC LICENSE
* @copyright  Copyright (c) 2007, Ricardo Alexandre Sismeiro.
* @link       http://www.sismeiro.com/php/xml.phps
*/
 

class Xml_parser {
	private $doc;
	private $filename;
	public  $result;
	public  $rootname;
	public  $errors;
	public  $_level_key_name;
	
	function __construct($_level_key_name){
		if (!$this->required_functions()){die();}
		$this->result=false;
		$this->errors = array('status' => true, 'msg' => '');
		$this->_level_key_name = $_level_key_name;
	}

	private function required_functions(){
	   $result=true;
	   $fun=array('simplexml_load_file','simplexml_load_string');	   
	   foreach ($fun as $name){
	   	if (!function_exists($name)) {
	   		$result=false;
			$this->errors['status'] = false;
	   		$this->errors['msg'][] = 'XML error - This class need some functions like '.$name;
			return;
	   	}
	   }
	   if (!$result) 
	   {
			$this->errors['status'] = false;
	   		$this->errors['msg'][] = 'XML error - can\'t proceed ';
			return;
	   }	   
	   return $result;
	}
		
	public function load_file($filename){
		if (is_readable($filename))
		{
			$this->filename=$filename;
			$content = @file_get_contents ($filename, false);
			$this->load_string($content); 	
		} 
		else
		{
			$this->errors['status'] = false;
	   		$this->errors['msg'][]  = 'XML error - File '.$filename.' is not readable!';
			return;
		}
	}
	
	public function load_string($string){
		
		$string = ltrim(rtrim($string));
		
		if ( $string !='' )
		{
			if (preg_match ('/\<\?xml/', $string) == false )
				$string = "<?xml version='1.0' encoding='utf8'?><__rootnode>".$string."</__rootnode>";
			else
			{
				$string = preg_replace('/\<\?xml.*\?\>/', '', $string);
				$string = "<?xml version='1.0' encoding='utf8'?><__rootnode>".$string."</__rootnode>";
			}

			
			libxml_use_internal_errors(true);
			$this->doc=simplexml_load_string($string);
									
			if ( !is_object($this->doc) )
			{
				$this->errors['status'] = false;
				$xml = explode("\n", $string);

				$errors = libxml_get_errors();
				$i = 0;
				
				foreach ($errors as $error) {
					$sep = ( $i == 0 ) ? "" : "<br/>";
					$this->errors['msg'][] = $sep.$this->display_xml_error($error, $xml);
					$i++;
				}
								
				libxml_clear_errors();
				return;
			}
					
		}
		else
		{
			$this->errors['status'] = false;
	   		$this->errors['msg'][] = "XML error - Content is empty";
			return;
		}
	}
	
	
	private function convert_simplexml_object2array(&$result,$root,$rootname='root', $rootkey='1'){	 		
	    $n=count($root->children());	
		
	    if ($n>0)
		{
	    						
			if (!isset($result[$rootname]['@attributes'])){
	            $result[$rootname]['@attributes']=array();
	            foreach ($root->attributes() as $atr=>$value)
				{
	               $result[$rootname]['@attributes'][$atr]=(string)$value;
				}
				$result[$rootname]['@attributes'][$this->_level_key_name]=$rootkey;				
	        }
	        $cont = 1;
			foreach ($root->children() as $child){
	             $name=$child->getName();  
				 $child->addAttribute($this->_level_key_name, $rootkey."_".$cont);
				 $this->{__FUNCTION__}($result[$rootname][],$child,$name, $rootkey."_".$cont); 
				 $cont++;
	        }
			
	    } else {        	        
	        $result[$rootname]=(array) $root;	        
	        if (!isset($result[$rootname]['@attributes']))
	            $result[$rootname]['@attributes']=array();	        
	    } 
	}
	
	private function convert_array2simplexml_object($array,$doc=''){		
		
		if (is_array($array)){
			if (!is_object($doc)) $doc=$this->doc;
						
			if ((isset($array['@attributes'])) && (count($array['@attributes'])>0)){				
			   	
				foreach ($array['@attributes'] as $attribute=>$value)
				{
			   		$v_at = rtrim(ltrim($value));
					$v_at = ( $v_at == '' ) ? "_#_void_value_#_" : $v_at;
					$doc->addAttribute($attribute, utf8_encode($v_at));
				}	
			   	unset($array['@attributes']);
			}
			
			foreach ($array as $key=>$value){
				if (is_numeric($key) && is_array($value) && count($value) > 0){
					list($child)=array_keys($value);
					if (is_array($value[$child][0]))
					{	
						$newchild=$doc->addChild($child);
					}
					else 
					{
						$v_child = rtrim(ltrim($value[$child][0]));
						$v_child = ( $v_child == '' ) ? "_#_void_value_#_" : $v_child;
												
						$newchild=$doc->addChild($child,utf8_encode($v_child));
					}
					$this->{__FUNCTION__}($value[$child],$newchild);
				}
			}
		} 		
	}
	
	public function xml2array($save_result=false){		      
		$result=false;		
		if (is_object($this->doc)){
			$result=array(); 
			$this->rootname = $this->doc->getName();            
    		$this->convert_simplexml_object2array($result,$this->doc,$this->rootname);
    		(isset($result[$this->rootname])) ? ($result=$result[$this->rootname]) : ($result=false);         
		}    	    	
    	if ($save_result) $this->result=$result;
    	return $result;   
	}  
	
	public function array2xml($array, $save_result=false){				
		
		$xml_string='<?xml version=\'1.0\' encoding=\'utf8\'?'.'>'.PHP_EOL;
		$this->load_string($xml_string);			
		$this->convert_array2simplexml_object($array);
		$result=$this->doc->asXML();
		if ($save_result) $this->result=$result;
		return $result;					
	}
	
	function display_xml_error($error, $xml)
	{
		$return  = $xml[$error->line - 1] . "<br/>";
		
		switch ($error->level) {
			case LIBXML_ERR_WARNING:
				$return .= _("Warning $error->code: ");
				break;
			 case LIBXML_ERR_ERROR:
				$return .= _("Error $error->code: ");
				break;
			case LIBXML_ERR_FATAL:
				$return .= _("Fatal Error $error->code: ");
				break;
		}

		$return .= trim($error->message) .
				   "<br/>  Line: $error->line" .
				   "<br/>  Column: $error->column";

		if ($error->file) {
			$return .= _("<br/>  File: $error->file");
		}

		return $return;
	}
	
}
?>