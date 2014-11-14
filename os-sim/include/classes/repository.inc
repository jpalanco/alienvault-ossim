<?php
/**
* Repository.inc
*
* File Repository.inc is used to:
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


/**
* Function list:
* - Repository()
* - get_id_document()
* - get_title()
* - get_text()
* - get_date()
* - get_user()
* - get_keywords()
* - get_relevance()
* - get_list()
* - get_repository_linked()
* - have_linked_documents()
* - get_document()
* - insert()
* - update()
* - attach()
* - get_attachments()
* - get_relationships()
* - get_relationships_by_link()
* - insert_relationships()
* - delete_relationships()
* - delete()
* - delete_attachment()
* - get_hostnet()
* - get_list_bylink()
*/

// Repository class made in Granada (26 january 2009)

define("MAXREL", 4);

class Repository 
{

    public $id_document;
    
    public $title;
    
    public $text;
    public $date;
    
    public $creator;
	
	public $in_charge;
    
    public $keywords;
    
    public $relavance;
    
    // List of attached files
    public $atch = array();
    
    public $rel = array();
	
		
    function __construct($id_document, $title, $text, $date, $creator, $in_charge, $keywords, $relavance, $atch, $rel) 
	{
        $this->id_document = intval($id_document);
        $this->title       = Util::htmlentities($title);
        $this->text        = $text;
        $this->date        = $date;
        $this->creator     = $creator;
        $this->keywords    = Util::htmlentities($keywords);
        $this->relevance   = $relavance;
       
	   
		if (valid_hex32($in_charge))
		{
			$this->in_charge = strtoupper($in_charge);
		}
		else
		{
			$this->in_charge = $in_charge;
		}
		
	   
        if (is_array($atch))
		{		
			$this->atch = $atch;
		}
        else 
		{
			$this->atch = array($atch);
		}
		
		
        if (is_array($rel))
		{		
			$this->rel = $rel;        
		}
		else 
		{
			$this->rel = array($rel);
		}
    }
	
	
    function get_id_document() 
	{
        return $this->id_document;
    }
	
	
    function get_title() 
	{
        return $this->title;
    }
	
	
    function get_text() 
	{
        return $this->text;
    }
	
	
    function get_date() 
	{
        return $this->date;
    }
	
	
    function get_creator() 
	{
        return $this->creator;
    }
	
	
	function get_in_charge() 
	{
        return $this->in_charge;
    }
	
	
    function get_keywords() 
	{
        return $this->keywords;
    }
	
	
    function get_relevance() 
	{
        return number_format($this->relevance * 100 / MAXREL, 2);
    }
	
	
    // Listing and search
    function get_list($conn, $from = 0, $maxrows = - 1, $search_str = "", $order = "date", $torder = 0) 
	{       
		$perms     = self::get_perms($conn);		
		
		$list      = array();
        $limit_str = ($maxrows > 0) ? " LIMIT $from, $maxrows" : "";
		$torder    = ($torder) ? "DESC" : "ASC";
		
        if ($search_str != "") 
		{
            $title    = "title LIKE \"%".escape_sql($search_str, $conn)."%\"";
            $keywords = "keywords LIKE \"%".escape_sql($search_str, $conn)."%\"";
            $text     = "MATCH(text) AGAINST(\"".escape_sql($search_str, $conn)."\")"; // FULLTEXT search
			
            if (preg_match("/AND/", $search_str)) 
			{
                $search_str = preg_replace("/\s*AND\s*/", " AND ", $search_str); // normalize format
                $st         = explode(" AND ", $search_str); // construct where clause
                $title      = $keywords = $text = "";
				
                foreach($st as $clause) 
				{
                    $title    .= ($title == "" ? "" : " AND ") . "title LIKE \"%".escape_sql($clause, $conn)."%\"";
                    $keywords .= ($keywords == "" ? "" : " AND ") . "keywords LIKE \"%".escape_sql($clause, $conn)."%\"";
                }
				
                $title      = "($title)";
                $keywords   = "($keywords)";
                $search_str = preg_replace("/ AND /", " ", $search_str);
                $search_str = preg_replace("/(\S+)/", "+\\1", $search_str);
                $text       = "MATCH(text) AGAINST(\"".escape_sql($search_str, $conn)."\" IN BOOLEAN MODE)";
            }
			elseif (preg_match("/OR/", $search_str)) 
			{
                $search_str = preg_replace("/\s*OR\s*/", " OR ", $search_str); // normalize format
                $st         = explode(" OR ", $search_str); // construct where clause
                $title      = $keywords = $text = "";
				
                foreach($st as $clause) 
				{
                    $title    .= ($title == "" ? "" : " OR ") . "title LIKE \"%".escape_sql($clause, $conn)."%\"";
                    $keywords .= ($keywords == "" ? "" : " OR ") . "keywords LIKE \"%".escape_sql($clause, $conn)."%\"";
                }
				
                $title      = "($title)";
                $keywords   = "($keywords)";
                $search_str = preg_replace("/ OR /", " ", $search_str);
                $text       = "MATCH(text) AGAINST(\"".escape_sql($search_str, $conn)."\")";
            }
			
            $sqlall = "(SELECT id,title,text,date,creator,in_charge," . MAXREL . " as relevance FROM repository WHERE $title) UNION (SELECT id,title,text,date,creator,in_charge," . MAXREL . " as relevance FROM repository WHERE $keywords) UNION (SELECT id,title,text,date,creator,in_charge,$text as relevance FROM repository WHERE $text) ORDER BY $order $torder";
            $sql    = "(SELECT id,title,text,date,creator,in_charge," . MAXREL . " as relevance FROM repository WHERE $title) UNION (SELECT id,title,text,date,creator,in_charge," . MAXREL . " as relevance FROM repository WHERE $keywords) UNION (SELECT id,title,text,date,creator,in_charge,$text as relevance FROM repository WHERE $text) ORDER BY $order $torder $limit_str";
        } 
		else 
		{
            $sqlall = "SELECT * FROM repository WHERE 1=1 ORDER BY $order $torder";
            $sql    = "SELECT * FROM repository WHERE 1=1 ORDER BY $order $torder $limit_str";
        }
		
        //echo "<br><br><br>$sqlall<br>$sql<br>";
        if (!$rs = & $conn->Execute($sql)) 
		{
            print $conn->ErrorMsg();
            return array(array(), 0);
        }
		
        $resall = & $conn->Execute($sqlall);
        $nrows  = $resall->numrows();
		
        //echo "NUMROWS para $search_str: $nrows<br>";
        while (!$rs->EOF) 
		{
            $filelist = array();
			
			 // No mostrar repetidos
            if ($rs->fields["id"] != "" && $list[$rs->fields["id"]]) 
			{
                $rs->MoveNext();
                $nrows--;
                continue;
            }
			
			// Perms
            if (!self::check_doc_availability($rs->fields['in_charge'],$rs->fields['creator'], $perms)) 
			{
                $rs->MoveNext();
                $nrows--;
                continue;
            }
			
			
            // Get attachments
            $sql = "SELECT * FROM repository_attachments WHERE id_document=" . $rs->fields['id'];
            if ($rs_files = & $conn->Execute($sql)) 
			{
                while (!$rs_files->EOF) 
				{
                    $filelist[] = array(
                        "id"          => $rs_files->fields["id"],
                        "name"        => $rs_files->fields["name"],
                        "id_document" => $rs_files->fields["id_document"],
                        "type"        => $rs_files->fields["type"]
                    );
					
                    $rs_files->MoveNext();
                }
            }
            $rellist = array();
            // Get relationships
            $sql = "SELECT * FROM repository_relationships WHERE id_document=" . $rs->fields['id'];
            if ($rs_rel = & $conn->Execute($sql)) 
			{
                while (!$rs_rel->EOF) 
				{
                    $rellist[] = array(
                        "keyname"     => $rs_rel->fields["keyname"],
                        "id_document" => $rs_rel->fields["id_document"],
                        "type"         => $rs_rel->fields["type"]
                    );
					
                    $rs_rel->MoveNext();
                }
            }
            $list[$rs->fields["id"]] = new Repository($rs->fields["id"], $rs->fields["title"], $rs->fields["text"], $rs->fields["date"], $rs->fields["creator"], $rs->fields["in_charge"], $rs->fields["keywords"], $rs->fields["relevance"], $filelist, $rellist);
            $rs->MoveNext();
        }
		
        return array(
            $list,
            $nrows
        );
		
    }
	
	
	
	// Get list by relationship
    function get_list_bylink($conn, $from = 0, $maxrows = - 1, $keyname = "", $type = "") 
	{
        $list      = array();
        $limit_str = ($maxrows > 0) ? " LIMIT $from,$maxrows" : "";
        $type_str  = ($type != "") ? " AND r.type='$type'" : "";
        $sqlall    = "SELECT d.id,d.title,d.text,d.date,d.creator,d.in_charge,d.keywords FROM repository d, repository_relationships r WHERE d.id=r.id_document AND r.keyname='$keyname' $type_str";
        $sql       = "SELECT d.id,d.title,d.text,d.date,d.creator,d.in_charge,d.keywords FROM repository d, repository_relationships r WHERE d.id=r.id_document AND r.keyname='$keyname' $type_str $limit_str";
        
		$perms     = self::get_perms($conn);
		
		//echo "$sqlall<br>$sql<br>";
        if (!$rs = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
            return array();
        }
		
        $resall = & $conn->Execute($sqlall);
        $nrows  = $resall->numrows();
        //echo "NUMROWS para $search_str: $nrows<br>";
		
        while (!$rs->EOF) 
		{
            $filelist = array();
			
			// No mostrar repetidos
            if ($rs->fields["id"] != "" && $list[$rs->fields["id"]]) 
			{ 
                $rs->MoveNext();
                $nrows--;
                continue;
            }
			
			// Perms
            if (!self::check_doc_availability($rs->fields['in_charge'],$rs->fields['creator'], $perms)) 
			{
                $rs->MoveNext();
                $nrows--;
                continue;
            }
			
            // Get attachments
            $sql = "SELECT * FROM repository_attachments WHERE id_document=" . $rs->fields['id'];
			
            if ($rs_files = & $conn->Execute($sql)) 
			{
                while (!$rs_files->EOF) 
				{
                    $filelist[] = array(
                        "id"          => $rs_files->fields["id"],
                        "name"        => $rs_files->fields["name"],
                        "id_document" => $rs_files->fields["id_document"],
                        "type"        => $rs_files->fields["type"]
                    );
					
                    $rs_files->MoveNext();
                }
            }
			
            $rellist = array();
			
            // Get relationships
            $sql = "SELECT * FROM repository_relationships WHERE id_document=" . $rs->fields['id'];
			
            if ($rs_rel = & $conn->Execute($sql)) 
			{
                while (!$rs_rel->EOF) 
				{
                    $rellist[] = array(
                        "keyname"     => $rs_rel->fields["keyname"],
                        "id_document" => $rs_rel->fields["id_document"],
                        "type"        => $rs_rel->fields["type"]
                    );
					
                    $rs_rel->MoveNext();
                }
            }
			
            $list[$rs->fields["id"]] = new Repository($rs->fields["id"], $rs->fields["title"], $rs->fields["text"], $rs->fields["date"], $rs->fields["creator"], $rs->fields["in_charge"], $rs->fields["keywords"], "", $filelist, $rellist);
            
			$rs->MoveNext();
        }
		
        return array(
            $list,
            $nrows
        );
		
    }
	
	
    // Get the repository list linked to a host
    function get_repository_linked($conn, $key, $type = 'host') 
	{
        $ret    = array();
		
		if ($type == 'taxonomy')
		{
			$tax = explode('##', $key);			
		
			$keys["'0##0##0'"]                     = 1;
			$keys["'".$tax[0]."##0##0'"]           = 1;
			$keys["'0##".$tax[1]."##0'"]           = 1;
			$keys["'0##".$tax[1]."##".$tax[2]."'"] = 1;
			$keys["'".$tax[0]."##".$tax[1]."##0'"] = 1;
			$keys["'$key'"]                        = 1;
			
			$keys = array_keys($keys);
			$key  = implode(',',$keys);
		
			$sql    = "SELECT * FROM repository_relationships WHERE keyname IN($key) AND type=? ORDER BY id_document";
			$params = array(
						$type
					);
		}
		else
		{
			$sql    = "SELECT * FROM repository_relationships WHERE keyname=? AND type=?";
			$params = array(
						$key,
						$type
					);
		}
		
        
				
        if (!$rs = & $conn->Execute($sql, $params)) 
		{
            print $conn->ErrorMsg();
            return array();
        }
		
        while (!$rs->EOF) 
		{
            $ret[] = self::get_document($conn, $rs->fields['id_document']);
            $rs->MoveNext();
        }
		
        return $ret;
    }
	
	
	
	// Get a list with relationships by link id
    function get_relationships_by_link($conn, $id_link) 
	{
        $rellist = array();
		
        // Get relationships
        $sql = "SELECT d.id,d.title,r.id_document FROM repository_relationships r,repository d WHERE r.keyname='$id_link' AND r.id_document=d.id";
        
		if ($rs_files = & $conn->Execute($sql)) 
		{
            while (!$rs_files->EOF) 
			{
                $rellist[] = array(
                    "id"          => $rs_files->fields["id"],
                    "title"       => $rs_files->fields["title"],
                    "id_document" => $rs_files->fields["id_document"]
                );
				
                $rs_files->MoveNext();
            }
        }
		
        return $rellist;
    }
	
	
	
    function get_linked_by_directive($conn, $directive) 
	{
        $rellist = array();
		
        // Get relationships
        $sql = "SELECT d.id,d.title,r.id_document,d.date,d.text FROM repository_relationships r,repository d WHERE r.keyname='$directive' AND r.type='directive' AND r.id_document=d.id";
       
	   if ($rs_files = & $conn->Execute($sql)) 
	   {
            while (!$rs_files->EOF) 
			{
                $rellist[] = array(
                    "id"          => $rs_files->fields["id"],
                    "title"       => $rs_files->fields["title"],
                    "text"        => $rs_files->fields["text"],
                    "date"        => $rs_files->fields["date"],
                    "id_document" => $rs_files->fields["id_document"]
                );
				
                $rs_files->MoveNext();
				
            }
        }
		
        return $rellist;
    }
	
	
	// Get a document by id
    function get_document($conn, $id_document) 
	{
        $ret = array();      
        
        $sql = "SELECT * FROM repository WHERE id=$id_document";
		
        if (!$rs = & $conn->Execute($sql)) 
		{
            print $conn->ErrorMsg();
            
            return array();
        }
        
        if (!$rs->EOF) 
		{
            // Get attachments
            $filelist = array();
            $sql      = "SELECT * FROM repository_attachments WHERE id_document=" . $id_document;
			
            if ($rs_files = & $conn->Execute($sql)) 
			{
                while (!$rs_files->EOF) 
				{
                    $filelist[] = array(
                        "id"          => $rs_files->fields["id"],
                        "name"        => $rs_files->fields["name"],
                        "id_document" => $rs_files->fields["id_document"],
                        "type"        => $rs_files->fields["type"]
                    );
					
                    $rs_files->MoveNext();
                }
            }
			
            $ret['title']    = $rs->fields["title"];
            $ret['text']     = $rs->fields["text"];
            $ret['keywords'] = $rs->fields["keywords"];
            $ret['date']     = $rs->fields["date"];
            $ret['id']       = $id_document;
            $ret['num_atch'] = count($filelist);
			$ret['creator']  = $rs->fields["creator"];
			
			$in_charge = $rs->fields["in_charge"];
			
            if ($in_charge == '0')
			{
                $ret['in_charge'] = _("All");
            }
			elseif (valid_hex32($in_charge))
			{
				$entity           = Acl::get_entity_name($conn,$in_charge);
				$ret['in_charge'] = $entity;
            }
			else
			{
                $ret['in_charge'] = $in_charge;
            }
        }
		
        return $ret;
    }
	
	
	
    // Get the repository list linked to a host
    function have_linked_documents($conn, $key, $type = 'host')
	{
		
        $sql = "SELECT * FROM repository_relationships WHERE keyname='$key' AND type='$type'";
		//echo $sql;
		//echo "\n";
        if (!$rs = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
            return false;
        }
		
        if (!$rs->EOF) return $rs->numrows();
		
		
        return false;
    }
	
	

	
    // Insert new document and returns auto-generated Id
    function insert($conn, $title, $text, $keywords, $in_charge) 
	{
		$creator = Session::get_session_user();
		
		
		$text = preg_replace("/\r/", "", $text);
		$text = preg_replace("/\A(\n)+/", "", $text);
		$text = preg_replace("/(\n){3,}/", "\n\n", $text);
		$text = preg_replace("/(\n)+\z/", "", $text);
		
		$sql = "INSERT into repository ( title, text, date, keywords, creator, in_charge ) VALUES   (?,?,?,?,?,?)";

		
        $params = array(
            $title,
            $text,
            date("Y-m-d") ,
            $keywords,
			$creator,
            $in_charge
        );
        

		if ($conn->Execute($sql, $params) == false) 
		{
            print _('Error inserting: ') . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        
		// get lastid
        $res = $conn->query("SELECT LAST_INSERT_ID() as lastid");
		
        if ($rw = $res->fetchRow())
		{
			$id_document = $rw["lastid"];
		}
		
        return $id_document;
    }
	
	
	
    // Edit a document
    function update($conn, $id_document, $title, $text, $keywords) 
	{	

		$text = preg_replace("/\r/", "", $text);
		$text = preg_replace("/\A(\n)+/", "", $text);
		$text = preg_replace("/(\n){3,}/", "\n\n", $text);
		$text = preg_replace("/(\n)+\z/", "", $text);

        $sql    = "UPDATE repository SET title=?, text=?, date=?, keywords=? WHERE id=$id_document";
        $params = array(
            $title,
            $text,
            date("Y-m-d") ,
            $keywords
        );
		
        if ($conn->Execute($sql, $params) == false) 
		{
            print _('Error Updating: ') . $conn->ErrorMsg() . '<BR>';
            exit;
        }
		
        $infolog = array(
            $id_document
        );
		
        Log_action::log(59, $infolog);
    }
	
	
	
    // Link a file to a document and returns the file in-disk name (iddocument_idfile.type)
    function attach($conn, $id_document, $filename) 
	{
        // Check if exists
        $sql = "SELECT * FROM repository_attachments WHERE name='$filename'";
		
        if ($rs = & $conn->Execute($sql)) 
		{
            if ($rs->numrows() > 0) 
			{
				return array (false, _('Error inserting: File exists in Database'));
			}
        }
        
		$type = preg_replace("/.*\.(..?.?)$/", "\\1", $filename);
        $sql  = "INSERT into repository_attachments ( id_document, name, type ) VALUES   (?,?,?)";
        
		$params = array(
            $id_document,
            $filename,
            $type
        );
        
		if ($conn->Execute($sql, $params) == false)
		{
            return array (false, _('Error inserting: ') . $conn->ErrorMsg());
		}
        
        $infolog = array(
            $filename,
            $id_document
        );
        
		Log_action::log(60, $infolog);
        
        // Get lastid
        $res = $conn->query("SELECT LAST_INSERT_ID() as lastid");
        
		if ($rw = $res->fetchRow()) 
		{
			$id_file = $rw["lastid"];
		}
		
		return array (true, $id_document . "_" . $id_file . "." . $type);
    }
	
    // Get a list with files linked to a document by id
    function get_attachments($conn, $id_document) 
	{
        $filelist = array();
		
        // Get attachments
        $sql = "SELECT * FROM repository_attachments WHERE id_document=$id_document";
		
        if ($rs_files = & $conn->Execute($sql)) 
		{
            while (!$rs_files->EOF) 
			{
                $filelist[] = array(
                    "id"          => $rs_files->fields["id"],
                    "name"        => $rs_files->fields["name"],
                    "id_document" => $id_document,
                    "type"        => $rs_files->fields["type"]
                );
				
                $rs_files->MoveNext();
            }
        }
		
        return $filelist;
		
    }
	
	
	
	// Get a unique file linked to a document by id
    function get_attachment($conn, $id_document, $id) 
	{
        $file = "";
		$name = "";
		
        // Get attachments
        $sql = "SELECT * FROM repository_attachments WHERE id_document=$id_document AND id=$id";
		
        if ($rs_files = & $conn->Execute($sql)) 
		{
            if (!$rs_files->EOF) 
			{
                $file = $rs_files->fields["id_document"]."_".$rs_files->fields["id"].".".$rs_files->fields["type"];
				$name = $rs_files->fields["name"];
            }
        }
		
        return array($file,$name);
		
    }
	
	
	
    // Get a list with relationships
    function get_relationships($conn, $id_document) 
	{
        $rellist = array();
		
        // Get relationships
        $sql = "SELECT * FROM repository_relationships WHERE id_document=$id_document";
		
        if ($rs_files = & $conn->Execute($sql)) 
		{
            while (!$rs_files->EOF) 
			{
                $rellist[] = array(
                    "id"   => $rs_files->fields["id"],
                    "key"  => $rs_files->fields["keyname"],
                    "type" => $rs_files->fields["type"]
                );
				
                $rs_files->MoveNext();
            }
        }
		
        return $rellist;
    }
	
	
	
	
    // Insert a  alienvault_siem reference
    function insert_snort_references($conn, $id_document, $pid, $sid) 
	{
        // Check if exists or autoinsert values
        $conn->Execute("INSERT IGNORE INTO alienvault_siem.reference_system VALUES (NULL,'kdb')");
		
        $sql = "SELECT ref_system_id FROM alienvault_siem.reference_system WHERE ref_system_name='kdb'";
		
        if (!$rs = &$conn->Execute($sql)) 
		{
            return _('DB Error: ').$conn->ErrorMsg();
        }
		
        $refsystemid = $rs->fields['ref_system_id'];
        $conn->Execute("INSERT IGNORE INTO alienvault_siem.reference VALUES (NULL,$refsystemid,'$id_document')");
		
        $sql = "SELECT ref_id FROM alienvault_siem.reference WHERE ref_system_id=$refsystemid and ref_tag='$id_document'";
		
        if (!$rs1 = &$conn->Execute($sql)) 
		{
            return _('DB Error: ').$conn->ErrorMsg();
        }
		
        $refid = $rs1->fields['ref_id'];
        $conn->Execute("INSERT IGNORE INTO alienvault_siem.sig_reference VALUES ($pid, $sid, $refid)");
		
		return '';
    }
	
	
	
    // Delete a  alienvault_siem reference
    function delete_snort_references($conn, $id_document) 
	{
        // Check if exists or autoinsert values
        $sql = "SELECT ref_system_id FROM alienvault_siem.reference_system WHERE ref_system_name='kdb'";
		
        if (!$rs = &$conn->Execute($sql)) 
		{
            return _('DB Error: ').$conn->ErrorMsg();
        }
		
        $refsystemid = $rs->fields['ref_system_id'];
		
        if ($refsystemid!="") 
		{
            $sql = "SELECT ref_id FROM alienvault_siem.reference WHERE ref_system_id=$refsystemid and ref_tag='$id_document'";
			
            if (!$rs1 = &$conn->Execute($sql)) 
			{
                return _('DB Error: ').$conn->ErrorMsg();

            }
			
            $refid = $rs1->fields['ref_id'];
            $conn->Execute("DELETE FROM alienvault_siem.reference WHERE ref_id=$refid");
            $conn->Execute("DELETE FROM alienvault_siem.sig_reference WHERE ref_id=$refid");
        }
		
		return '';
    }
	
    // Insert a new link
    function insert_relationships($conn, $id_document, $table, $key) 
	{
        // Check if exists
        $sql = "SELECT * FROM repository_relationships WHERE keyname='$key' AND type='$table' AND id_document=$id_document";
		
        if ($rs = & $conn->Execute($sql)) 
		{
            if ($rs->numrows() > 0) {
                return _('Error inserting: File exists in Database').'<BR>';
            }
        }
		
        $sql    = "INSERT into repository_relationships ( id_document, type, keyname ) VALUES (?,?,?)";
        $params = array(
            $id_document,
            $table,
            $key
        );
		
        if ($conn->Execute($sql, $params) == false) 
		{
            return _('Error inserting: ') . $conn->ErrorMsg() . '<BR>';
        }
		
        $infolog = array(
            $key,
            $id_document
        );
		
        Log_action::log(62, $infolog);
		
		return '';
    }
	
	
    // Delete a link
    function delete_relationships($conn, $id_document, $key) 
	{
        // Delete link
        $sql    = "DELETE FROM repository_relationships WHERE id_document = ? AND keyname=?";
        $params = array(
            $id_document,
            $key
        );
		
        if ($conn->Execute($sql, $params) === false) 
		{
			return  _('Error deleting: ') . $conn->ErrorMsg() . '<BR>';
        }
		
        $infolog = array(
            $id_document,
            $key
        );
		
        Log_action::log(63, $infolog);
		
		return '';
    }
	
	
    // Delete a document, and linked files
    function delete($conn, $id_document) 
	{
        // Delete relationships
        $sql    = "DELETE FROM repository_relationships WHERE id_document = ?";
        $params = array(
            $id_document
        );
		
        if ($conn->Execute($sql, $params) === false) 
		{
            print _('error deleting: ') . $conn->ErrorMsg() . '<BR>';
            exit;
        }
		
        // Delete attachments
        $sql    = "DELETE FROM repository_attachments WHERE id_document = ?";
        $params = array(
            $id_document
        );
		
        if ($conn->Execute($sql, $params) === false) {
            print _('error deleting: ') . $conn->ErrorMsg() . '<BR>';
            exit;
        }
		
        if (is_dir("/tmp/ossim/$id_document")) 
		{
            // List the contents of the directory table
            $dir_content = scandir("/tmp/ossim/$id_document");
			
            // Is it a directory?
            if ($dir_content != FALSE) 
			{
                // For each directory entry
                foreach($dir_content as $entry) 
				{
                    if ($entry == "." || $entry == "..") continue;
                    @unlink("/tmp/ossim/$id_document/$entry");
                }
				
                @rmdir("/tmp/ossim/$id_document");
            }
        }
		
        // Delete document
        $sql    = "DELETE FROM repository WHERE id = ?";
        $params = array(
            $id_document
        );
		
        if ($conn->Execute($sql, $params) === false) 
		{
            print _('error deleting: ') . $conn->ErrorMsg() . '<BR>';
            exit;
        }
		
        $infolog = array(
            $id_document
        );
		
        Log_action::log(58, $infolog);
    }
	
	
    // Delete file and row attached to a document by file id
    function delete_attachment($conn, $id, $uploads_dir) 
	{
        $sql = "SELECT * FROM repository_attachments WHERE id=$id";
		
        if ($rs = & $conn->Execute($sql)) 
		{
            if (!$rs->EOF) 
			{
                // Del filesystem file
                $disk_file = $rs->fields['id_document'] . "_" . $id . "." . $rs->fields['type'];
                //system ("sudo rm -f $uploads_dir/$disk_file");
                @unlink("$uploads_dir/" . $rs->fields['id_document'] . "/$disk_file");
               
			   // Del database row
                $sql    = "DELETE FROM repository_attachments WHERE id = ?";
                $params = array(
                    $id
                );
				
                if ($conn->Execute($sql, $params) === false) 
				{
                    print _('error deleting: ') . $conn->ErrorMsg() . '<BR>';
                    exit;
                }
            }
        }
		
        $infolog = array(
            $disk_file
        );
		
        Log_action::log(61, $infolog);
    }
	
	
	
    // Get host or host_group or net or net_group List
    function get_hostnet($conn, $table) 
	{
        $ret = array();
		
		if ($table == 'host') 
		{
			$_assets_data = Asset_host::get_basic_list($conn);
			$list         = $_assets_data[1];
			$total        = count($list);
		} 
		elseif ($table == 'host_group') 
		{
			list($list, $total) = Asset_group::get_list($conn);
		} 
		elseif ($table == 'net') 
		{
			list($list, $total) = Asset_net::get_list($conn);
		} 
		elseif ($table == 'net_group') 
		{
			$list = Net_group::get_list($conn);
		} 
		else 
		{
			$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM $table";
			
			if (!$rs = & $conn->Execute($sql)) 
			{
	            print $conn->ErrorMsg();
	            return array();
	        }
	        
			$resall = & $conn->Execute("SELECT FOUND_ROWS() as contador");
	        
			if ($rw = $resall->fetchRow()) 
			{
				$num_rows = $rw["contador"];
			}
			
			while (!$rs->EOF) 
			{
				if ($table == "incident") 
				{	
					$user    = Session::get_session_user();
					
					$allowed = Incident::get_users_list($conn, $rs->fields["submitter"], $rs->fields["in_charge"], $rs->fields["id"], $user);
					
					if (in_array($user, $allowed)) 
					{
						$name_temp=strtoupper(substr($rs->fields["ref"], 0, 3));
						
						if ($rs->fields["id"]>=10)
						{
							$name_temp .= $rs->fields["id"];
						}
						else
						{
							$name_temp .= '0' . $rs->fields["id"];
						}
											
						$ret[] = array(
							"key" => $rs->fields["id"],
							"name" => $name_temp.' - '.$rs->fields["title"]
						);
					}
	            // Others
	            } 
				else
				{
    				 $ret[] = array(
    	                "key"  => $rs->fields["name"],
    	                "name" => $rs->fields["name"]
    	            );
	            }
				
	            $rs->MoveNext();
	        }
		}
				
		if ($table == "host" || $table == "host_group" || $table == "net") 
		{
			$num_rows = $total;
			
			foreach ($list as $asset) 
			{
    			if ($table == 'host_group')
    			{
        			$name = $asset->get_name();
        			$id   = $asset->get_id();
    			}
    			else
    			{
        			$name = $asset['name'] ." (". $asset['ips'] .")";
        			$id   = $asset['id'];
    			}
    			
    			
				$ret[] = array(
					"key" => $id,
					"name" => $name
				);
			}
		}		
		elseif ($table == "net_group") 
		{
			$num_rows = (count($list) > 0) ? $list[0]->get_foundrows() : 0;
			
			foreach ($list as $asset) 
			{
				$name_temp = $asset->get_name();
				
				$ret[]     = array(
					"key" => $asset->get_id(),
					"name" => $name_temp
				);
			}
		}
		elseif ($table == "incident") 
		{
			$tmp = Array();
			
			foreach($ret as $ma)
			{
				$tmp[] = $ma["name"];
			}
			
			array_multisort($tmp, $ret);
		}
		
        return array(
            $ret,
            $num_rows
        );
        
    }
	 
	
	
    function get_perms($dbconn) //get the editing perms for list
	{       
        $conf        = $GLOBALS["CONF"];
        $version     = $conf->get_conf("ossim_server_version", FALSE);                   

        if (preg_match("/pro|demo/i", $version)) 
		{
            define("NO_PERMS", 0);
            define("VISION_PERMS", 1);
            define("EDITING_PERMS", 2);
                        
            $perms       = array( "entity_perms" => array(), "user_perms" => array() );
            $user_vision = (!isset($_SESSION['_user_vision'])) ? Acl::get_user_vision($dbconn) : $_SESSION['_user_vision'];
            
                
            //User permission
            $perms['user_perms'] = $user_vision['user'];
                           
            
            //Entity permissions	
            if (Session::am_i_admin())
            {
                $perms['entity_perms'] = $user_vision['entity'];
            }
            else
            {                    
                foreach ($user_vision['entity'] as $entity_id => $perm) 
                {
                    $perms['entity_perms'][$entity_id] = NO_PERMS; //Initial permissions
                }		
                
                $my_entities       = Acl::get_my_entities($dbconn, '', FALSE);
                $my_entities_admin = $user_vision['entity_admin'];
                            
                foreach ($my_entities as $entity_id => $entity) 
                {
                    if (!empty($my_entities_admin[$entity_id]))
                    {
                        $perms['entity_perms'][$entity_id] = EDITING_PERMS;
                    }
                    else
                    {
                        if ($perms['entity_perms'][$entity_id] < EDITING_PERMS)
                        {
                            $perms['entity_perms'][$entity_id] = VISION_PERMS;
                        }
                    }
                    
                    foreach ($entity['children'] as $entity_child_id)
                    {
                        if (!empty($my_entities_admin[$entity_id]) || !empty($my_entities_admin[$entity_child_id]))
                        {
                            $perms['entity_perms'][$entity_child_id] = EDITING_PERMS;
                        }
                        else
                        {
                            if ($perms['entity_perms'][$entity_child_id] < EDITING_PERMS)
                            {
                                $perms['entity_perms'][$entity_child_id] = VISION_PERMS;
                            }
                        }
                    }
                }
            }
        }
		else
		{ 
			//opensource
            $perms['user_perms'][Session::get_session_user()] = 2;
        }
		
		return $perms;
	}
	
	
	function check_doc_availability($in_charge, $creator, $doc_perms)
	{
		$me = Session::get_session_user();
		
		if (valid_hex32($in_charge))
		{
			$user_perm   = '';
			$entity_perm = strtoupper($in_charge);
		}
		else
		{
			$user_perm   = $in_charge;
			$entity_perm = '';
		}
		
		
		if ( Session::am_i_admin() || $me == $creator || $doc_perms['user_perms'][$creator] > 1 )
		{
			return true;
		}
		else
		{
			if ($user_perm != '')
			{
				if ( $user_perm == "0" || $doc_perms['user_perms'][$user_perm] > 1 )
				{
					return true;
				}
			}
			elseif ($entity_perm != '')
			{
				if ( $doc_perms['entity_perms'][$entity_perm] >= 1 )
				{
					return true;
				}
			}
		}
		
		return false;
		
	}
	

	function can_i_modify($creator, $perms) //using the perms to check if i can modify 
	{
	
		if ( $perms['user_perms'][$creator] > 1 || Session::am_i_admin() || $creator == 0 )
			return true;
		else
			return false;
	}
	
}
?>
