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

require_once dirname(__FILE__) . '/../../../conf/config.inc';

Session::logcheck('environment-menu', 'EventsHidsConfig');


$file      = $_SESSION['_current_file'];
$sensor_id = POST('sensor_id');
$token     = POST('token');


ossim_valid($sensor_id, OSS_HEX,                   'illegal:' . _('Sensor ID'));
ossim_valid($file, OSS_ALPHA, OSS_SCORE, OSS_DOT,  'illegal:' . _('File'));


if (ossim_error())
{
   $data['status'] = 'error';
   $data['data']   = ossim_get_error_clean();
}
else
{
    if (!Token::verify('tk_f_rules', $token))
    {
    	$data['status'] = 'error';
    	$data['data']   = Token::create_error_message();
    }
    else
    {        
        $db    = new ossim_db();
        $conn  = $db->connect();
        
        if (!Ossec_utilities::is_sensor_allowed($conn, $sensor_id))
        {           
            $data['status'] = 'error';
            $data['data']   = _('Error! Sensor not allowed');
        }
        
        $db->close();   
    }
}


if ($data['status'] == 'error')
{	
	$data['status'] = 'error';
	$data['data']   = _('We found the followings errors:')."<div style='padding-left: 15px; text-align:left;'>".$data['data'].'</div>';
	
	echo json_encode($data);
	exit();
}


//Level key
$lk_name = Ossec::get_key_name($file);

//Rule file
$rule_file = Ossec_utilities::get_real_path($sensor_id, Ossec::RULES_PATH.'/'.$file);


if (!Ossec::is_editable($file))
{
	$data['status'] = 'error';
	$data['data']   = _('XML file can not be edited');
	
	echo json_encode($data);
	exit();
}


$file_tmp = uniqid($file).'_tmp.xml';
$path_tmp = "/tmp/tmp_".$file_tmp;


if (copy($rule_file, $path_tmp) == FALSE)
{
	$data['status'] = 'error';
	$data['data']   = _('Failure to create temporary copy from XML file');
	
	echo json_encode($data);
	exit();
}


$node_name = $_SESSION['_current_node'];

$lk_value  = $_SESSION['_current_level_key'];
$lk_value  = preg_replace('/^attr_/', '', $lk_value);

$tree      = $_SESSION['_tree'];
$tree_cp   = $tree;

$child     = $_SESSION['_current_branch'];
$node_type = $_SESSION['_current_node_type'];

$branch    = '['.implode('][', $child['parents']).']';

$ok        = NULL;


//Clean array $_POST
$char_list  = "\t\n\r\0\x0B";
$clean_post = array();

unset($_POST['sensor_id']);
unset($_POST['token']);

foreach ($_POST as $k => $v)
{
	if (preg_match('/^tn_/', $k) == FALSE)
	{
    	$clean_post[$k]  = trim($v, $char_list);
    	$clean_post[$k]  = ltrim(rtrim($v, $char_list));
	}
}

switch ($node_type)
{
	//One attribute
	case 1:
	
		$ac = $branch."['@attributes']['$node_name']";
				
		// Security: code injection
		if (preg_match("/\;/", $ac)) 
		{
			$data['status'] = 'error';
			$data['data']   = _('Invalid ac value');
			
			echo json_encode($data);
		}
		else
		{		
			$ok = @eval ("unset(\$tree$ac);");
			
			if ($clean_post["n_label-".$lk_value."_at1"] != '' && $clean_post["n_txt-".$lk_value."_at1"] != '' && $ok !== FALSE)
			{
				$key   = $clean_post["n_label-".$lk_value."_at1"];
				$value = $clean_post["n_txt-".$lk_value."_at1"];
							
				$ac = $branch."['@attributes']['$key'] = \"$value\"";
				
				if (preg_match("/\;/", $ac))
				{
				    $data['status'] = 'error';
				    $data['data']   = _('Invalid ac value');
				
				    echo json_encode($data);
				}
				else
				{
				    $ok = @eval ("\$tree$ac;");
				}
			}
		}
	break;
	
	//Several Attributes
	case 2:
	
		$ac = $branch."['@attributes']";
		
		// Security: code injection
		if (preg_match("/\;/", $ac)) 
		{
			$data['status'] = 'error';
			$data['data']   = _('Invalid ac value');
						
			echo json_encode($data);
		}
		else
		{
			$attributes = array();
		
			$attributes[$lk_name] = $lk_value;

			$keys = array_keys($clean_post);
			$num  = count($keys);

			for ($i=0; $i<$num; $i=$i+2)
			{	
				$j = $i + 1;
				
				if ($clean_post[$keys[$i]] != '' && $clean_post[$keys[$j]] != '')
				{
					$attributes[$clean_post[$keys[$i]]] = $clean_post[$keys[$j]];
				}
			}

            if (!empty($attributes) && is_array($attributes))
            {
               $ok = @eval ("\$tree$ac= \$attributes;");
            }
		}
	break;	
	
	//Text Nodes
	case 3:
	
		$txt_nodes  = array();
		$attributes = array();

		$attributes[$lk_name] = $lk_value;

		$keys = array_keys($clean_post);
		$num  = count($keys);
						
		if ($clean_post[$keys[$num-2]] != '')
		{
			for ($i=0; $i<$num-3; $i=$i+2)
			{	
				$j = $i + 1;
				
				if ($clean_post[$keys[$i]] != '' && $clean_post[$keys[$j]] != '')
				{
					$attributes[$clean_post[$keys[$i]]] = $clean_post[$keys[$j]];
				}
			}
		    
			$txt_nodes['@attributes'] = $attributes;
			$txt_nodes[0] = $clean_post[$keys[$num-1]];
			
			// Security: code injection
			if (preg_match("/\;/", $branch))
			{
				$data['status'] = 'error';
				$data['data']   = _('Invalid ac value');
					
				echo json_encode($data);
			}
			else
			{
				$ok     = @eval ("unset(\$tree$branch);");
				$child['parents'][count($child['parents'])-1] = $clean_post[$keys[$num-2]];
				$branch = '['.implode("][", $child['parents']).']';
			
			    if (!empty($txt_nodes) && is_array($txt_nodes))
			    {
                   $ok = @eval ("\$tree$branch= \$txt_nodes;");
                }  
            }
		}
		else
		{
			// Security: code injection
			if (preg_match("/\;/", $branch)) 
			{
				$data['status'] = 'error';
				$data['data']   = _('Invalid branch value');
							
				echo json_encode($data);
			}
			else
			{
				$ok = @eval("unset(\$tree$branch);");
			}
		}

	break;
	
	//Rules
	case 4:

		$txt_nodes  = array();
		$attributes = array();
		$node       = array();
		$found      = FALSE;

		$attributes[$lk_name] = $lk_value;

		foreach ($clean_post as $k => $v)
		{
			if ($k == 'sep')
			{
				$found = TRUE;
				
				continue;
			}
			
			if ($found == FALSE)
			{
				$at_keys[] = $k;
			}
			else
			{
				$txt_nodes_keys[] = $k;
			}
		}
		
		
		$num_at  = count($at_keys);
		$num_txt = count($txt_nodes_keys);

		for ($i=0; $i<$num_at; $i=$i+2)
		{
			if ($clean_post[$at_keys[$i]] != '')
			{
				$j = $i + 1;
				$attributes[$clean_post[$at_keys[$i]]] = $clean_post[$at_keys[$j]];
			}
		}
		
		$node['@attributes'] = $attributes;
		
				
		$cont = 0;
		$i    = 0;
		
		while ($i < $num_txt)
		{
			$insert    = TRUE;
			$txt_nodes = array();
			
			$id       = explode('-', $txt_nodes_keys[$i], 2);
			$lk_value = $id[1];
			
			$name_node = $clean_post[$txt_nodes_keys[$i]];
			
			if ($name_node != '')
			{
				$txt_nodes[$name_node]['@attributes'][$lk_name] = $lk_value;
				$txt_nodes[$name_node][0] = $clean_post[$txt_nodes_keys[$i+1]];
			}
			else
			{
				$insert = FALSE;
			}
						
			$i = $i + 2;
			
			$id = explode("-", $txt_nodes_keys[$i], 2);
			
			// Patch regex DoS possible vuln
			$lk_value = Util::regex($lk_value);
			
			while (preg_match("/$lk_value/", $id[1]) != FALSE)
			{
				if ($clean_post[$txt_nodes_keys[$i]] != '' && $name_node != '')
				{
					$txt_nodes[$name_node]['@attributes'][$clean_post[$txt_nodes_keys[$i]]]= $clean_post[$txt_nodes_keys[$i+1]];
				}
				
				$i  = $i + 2;
				$id = explode("-", $txt_nodes_keys[$i], 2);
			}
			
			if ($insert == TRUE)
			{
				$node[$cont] = $txt_nodes;
				$cont++;
			}
		}
		
						
		// Security: code injection
		if (preg_match("/\;/", $branch)) 
		{
			$data['status'] = 'error';
			$data['data']   = _('Invalid branch value');
						
			echo json_encode($data);
		}
		else
		{
		    if (!empty($node) && is_array($node))
		    {
                $ok = @eval ("\$tree$branch= \$node;");
            }  
		}
	
	break;
	
	case 5:

    $nodes      = array();
    $nodes_keys = array();
    $at_keys    = array();

    $nodes['@attributes'][$lk_name] = $lk_value;

    foreach ($clean_post as $k => $v)
    {
        if ($k == 'sep')
        {
            $found = TRUE;

            continue;
        }

        if ($found == FALSE)
        {
            $at_keys[] = $k;
        }
        else
        {
            $nodes_keys[$k] = $v;
        }
    }

    $num_at = count($at_keys);

    for ($i = 0; $i < $num_at; $i = $i+2)
    {
        if ($clean_post[$at_keys[$i]] != '')
        {
            $j = $i + 1;
            $nodes['@attributes'][$clean_post[$at_keys[$i]]] = $clean_post[$at_keys[$j]];
            unset($clean_post[$at_keys[$i]]);
        }
    }

    $cont = 1;

    foreach ($nodes_keys as $k => $v)
    {
        $key = (!preg_match("/^clone/", $v)) ? $v : preg_replace("/clone###/", '', $v);

        $child_node = Ossec::get_child($child, $lk_name, $key);
        $lk_value   = $child_node['tree']['@attributes'][$lk_name];

        $new_lk = Ossec::set_new_lk($child_node['tree'], $lk_name, $lk_value, $lk_value.'_'.$cont);

        $nodes[$cont-1][$child_node['node']] = $new_lk;
        $cont++;
    }

    // Security: code injection
    if (preg_match("/\;/", $branch))
    {
        $data['status'] = 'error';
        $data['data']   = _('Invalid branch value');

        echo json_encode($data);
    }
    else
    {
        if (!empty($nodes) && is_array($nodes))
        {
            $ok = @eval ("\$tree$branch=\$nodes;");
        }
    }

    break;
}


if ($data['status'] != 'error')
{
    if ($ok === FALSE)
    {
        $data['status'] = 'error';
        $data['data']   = _('Error! XML file not updated (1)');

        echo json_encode($data);
    }
    else
    {
        $xml = new Xml_parser($lk_name);
        
        $output = $xml->array2xml($tree);
        $output = Ossec_utilities::formatOutput($output, $lk_name);
        $output = utf8_decode($output);

        try
        {
            Ossec::set_rule_file($sensor_id, $file, $output);

            $tree = Ossec::get_tree($sensor_id, $file);

            $tree_json              = Ossec_utilities::array2json($tree, $file);

            $_SESSION['_tree_json'] = $tree_json;
            $_SESSION['_tree']      = $tree;
        }
        catch(Exception $e)
        {
            $data['status'] = 'error';
            $data['data']   = $e->getMessage();
        }
    }
}


if ($data['status'] == 'error')
{
    //Restore copy
    @copy($path_tmp, $rule_file);

    $_SESSION['_tree']       = $tree_cp;
    $_SESSION['_tree_json']  = Ossec_utilities::array2json($tree_cp, $file);
}
else
{
    $data['status'] = 'success';
    $data['data']   = _('XML file update successfully').'###'.base64_encode($tree_json);

    echo json_encode($data);
}

@unlink($path_tmp);
?>