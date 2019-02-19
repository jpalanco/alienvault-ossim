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
* - get_id()
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
    protected $id_document;


    protected $title;


    protected $text;


    protected $date;


    protected $creator;


    protected $in_charge;


    protected $keywords;


    protected $relavance;


    // List of attached files
    protected $atch = array();


    protected $rel = array();


    public function __construct($id_document, $title, $text, $date, $creator, $in_charge, $keywords, $relavance, $atch, $rel) 
    {
        $this->id_document = intval($id_document);
        $this->title       = $title;
        $this->text        = $text;
        $this->date        = $date;
        $this->creator     = $creator;
        $this->keywords    = $keywords;
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
    
    
    public function get_id()
    {
        return intval($this->id_document);
    }


    public function get_title()
    {
        return Util::htmlentities($this->title);
    }


    public function get_text($entities = TRUE)
    {
        if ($entities == TRUE)
        {
            return Util::htmlentities($this->text);
        }
        else
        {
            return $this->text;
        }

    }


    public function get_date()
    {
        return $this->date;
    }


    public function get_creator()
    {
        return Util::htmlentities($this->creator);
    }
    

    public function get_in_charge()
    {
        return $this->in_charge;
    }
    
    
    public function get_visibility()
    {
        if ($this->in_charge == '0')
        {
            return _("All");
        }
        elseif (valid_hex32($in_charge))
        {
            $db   = new Ossim_db();
            $conn = $db->connect();

            $entity_name = Acl::get_entity_name($conn, $in_charge);

            $db->close();

            return $entity_name;
        }
        else
        {
            return Util::htmlentities($this->in_charge);
        }
    }
    
    
    public function get_attach()
    {
        return $this->atch;
    }
    
    
    public function get_relationship()
    {
        return $this->rel;
    }


    public function get_keywords()
    {
        return Util::htmlentities($this->keywords);
    }


    public function get_relevance()
    {
        return number_format($this->relevance * 100 / MAXREL, 2);
    }


    // Listing and search
    public static function get_list($conn, $from = 0, $maxrows = - 1, $search_str = "", $order = "date", $torder = 0)
    {
        $perms     = self::get_perms($conn);

        $list      = array();
        $limit_str = ($maxrows > 0) ? " LIMIT $from, $maxrows" : "";
        $torder    = ($torder) ? "DESC" : "ASC";

        if ($search_str != '')
        {
            $title    = "title LIKE \"%".escape_sql($search_str, $conn)."%\"";
            $keywords = "keywords LIKE \"%".escape_sql($search_str, $conn)."%\"";
            $text     = "text LIKE \"%".escape_sql($search_str, $conn)."%\""; // FULLTEXT search

            if (preg_match("/AND/", $search_str)) 
            {
                $search_str = preg_replace("/\s*AND\s*/", " AND ", $search_str); // normalize format
                $st         = explode(" AND ", $search_str); // construct where clause
                $title      = $keywords = $text = "";

                foreach($st as $clause)
                {
                    $title    .= ($title == "" ? "" : " AND ") . "title LIKE \"%".escape_sql($clause, $conn)."%\"";
                    $keywords .= ($keywords == "" ? "" : " AND ") . "keywords LIKE \"%".escape_sql($clause, $conn)."%\"";
                    $text     .= ($text == "" ? "" : " AND ") . "text LIKE \"%".escape_sql($clause, $conn)."%\"";
                }

                $title      = "($title)";
                $keywords   = "($keywords)";
                $text       = "($text)";
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
                    $text     .= ($text == "" ? "" : " OR ") . "$text LIKE \"%".escape_sql($clause, $conn)."%\"";
                }

                $title      = "($title)";
                $keywords   = "($keywords)";
                $text       = "($text)";
            }

            $queryall = "(SELECT id,title,text,date,creator,in_charge," . MAXREL . " as relevance FROM repository WHERE $title) UNION (SELECT id,title,text,date,creator,in_charge," . MAXREL . " as relevance FROM repository WHERE $keywords) UNION (SELECT id,title,text,date,creator,in_charge,$text as relevance FROM repository WHERE $text) ORDER BY $order $torder";

            $query  = "(SELECT id,title,text,date,creator,in_charge," . MAXREL . " as relevance FROM repository WHERE $title) UNION (SELECT id,title,text,date,creator,in_charge," . MAXREL . " as relevance FROM repository WHERE $keywords) UNION (SELECT id,title,text,date,creator,in_charge,$text as relevance FROM repository WHERE $text) ORDER BY $order $torder $limit_str";
        }
        else
        {
            $queryall = "SELECT * FROM repository WHERE 1=1 ORDER BY $order $torder";
            $query    = "SELECT * FROM repository WHERE 1=1 ORDER BY $order $torder $limit_str";
        }

        $rs = $conn->Execute($query);

        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());

            return array(array(), 0);
        }

        $resall = $conn->Execute($queryall);
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
            $query = "SELECT * FROM repository_attachments WHERE id_document=" . $rs->fields['id'];

            $rs_files = $conn->Execute($query);
            if ($rs_files)
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
            $query = "SELECT * FROM repository_relationships WHERE id_document=" . $rs->fields['id'];

            $rs_rel = $conn->Execute($query);
            if ($rs_rel)
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

            $list[$rs->fields["id"]] = new self($rs->fields["id"], $rs->fields["title"], $rs->fields["text"], $rs->fields["date"], $rs->fields["creator"], 
                $rs->fields["in_charge"], $rs->fields["keywords"], $rs->fields["relevance"], $filelist, $rellist);

            $rs->MoveNext();
        }

        return array($list, $nrows);
    }
    
    
    // Get list by relationship
    public static function get_list_bylink($conn, $from = 0, $maxrows = - 1, $keyname = "", $type = "")
    {
        $list      = array();
        $limit_str = ($maxrows > 0) ? " LIMIT $from,$maxrows" : "";
        $type_str  = ($type != "") ? " AND r.type='$type'" : "";
        $queryall  = "SELECT d.id,d.title,d.text,d.date,d.creator,d.in_charge,d.keywords FROM repository d, repository_relationships r WHERE d.id=r.id_document AND r.keyname='$keyname' $type_str";
        $query     = "SELECT d.id,d.title,d.text,d.date,d.creator,d.in_charge,d.keywords FROM repository d, repository_relationships r WHERE d.id=r.id_document AND r.keyname='$keyname' $type_str $limit_str";
        
        $perms     = self::get_perms($conn);
        
        $rs = $conn->Execute($query);
        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());

            return array();
        }
        
        $resall = $conn->Execute($queryall);
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
            $query = "SELECT * FROM repository_attachments WHERE id_document=" . $rs->fields['id'];
            
            $rs_files = $conn->Execute($query);            
            if ($rs_files) 
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
            $query = "SELECT * FROM repository_relationships WHERE id_document=" . $rs->fields['id'];
            
            $rs_rel = $conn->Execute($query);
            if ($rs_rel) 
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
            
            $list[$rs->fields["id"]] = new self($rs->fields["id"], $rs->fields["title"], $rs->fields["text"], $rs->fields["date"], $rs->fields["creator"], $rs->fields["in_charge"], $rs->fields["keywords"], "", $filelist, $rellist);
            
            $rs->MoveNext();
        }
        
        return array($list, $nrows);        
    }
    
    
    // Get the repository list linked to a host
    public static function get_repository_linked($conn, $key, $type = 'host')
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
        
            $query  = "SELECT * FROM repository_relationships WHERE keyname IN($key) AND type=? ORDER BY id_document";
            $params = array($type);
        }
        else
        {
            $query  = "SELECT * FROM repository_relationships WHERE keyname=? AND type=?";
            $params = array($key, $type);
        }        

        $rs = $conn->Execute($query, $params);
        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());

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
    public static function get_relationships_by_link($conn, $id_link)
    {
        $rellist = array();
        
        // Get relationships
        $query = "SELECT d.id,d.title,r.id_document FROM repository_relationships r,repository d WHERE r.keyname='$id_link' AND r.id_document=d.id";
        
        $rs_files = $conn->Execute($query);
        if ($rs_files) 
        {
            while (!$rs_files->EOF) 
            {
                $rellist[] = array(
                    "id"          => $rs_files->fields["id"],
                    "title"       => Util::htmlentities($rs_files->fields["title"]),
                    "id_document" => $rs_files->fields["id_document"]
                );
                
                $rs_files->MoveNext();
            }
        }
        
        return $rellist;
    }
    
    
    public static function get_linked_by_directive($conn, $directive)
    {
        $list = array();
        
        // Get relationships
        $query = "SELECT d.* FROM repository_relationships r,repository d WHERE r.keyname='$directive' AND r.type='directive' AND r.id_document=d.id";
       
        $rs = $conn->Execute($query);
        
        if ($rs) 
        {
            while (!$rs->EOF) 
            {
                $list[] = new self($rs->fields["id"], $rs->fields["title"], $rs->fields["text"], $rs->fields["date"], $rs->fields["creator"], $rs->fields["in_charge"], $rs->fields["keywords"], "", array(), array()); 
                               
                $rs->MoveNext();                
            }
        }
        
        return $list;
    }
    
    
    // Get a document by id
    public static function get_document($conn, $id_document)
    {
        $ret = FALSE;      
        
        $params = array($id_document);
        $query  = "SELECT * FROM repository WHERE id = ?";

        $rs = $conn->Execute($query, $params);
        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());

            return array();
        }
        
        if (!$rs->EOF) 
        {
            // Get attachments
            $filelist = array();
            $query    = "SELECT * FROM repository_attachments WHERE id_document=" . $id_document;
            
            $rs_files = $conn->Execute($query);       
                 
            if ($rs_files) 
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
            
            $ret = new self($id_document, $rs->fields["title"], $rs->fields["text"], $rs->fields["date"], $rs->fields["creator"], $rs->fields["in_charge"], $rs->fields["keywords"], "", $filelist, array());
        }
        
        return $ret;
    }
        
    
    // Get the repository list linked to a host
    public static function have_linked_documents($conn, $key, $type = 'host')
    {        
        $query = "SELECT * FROM repository_relationships WHERE keyname='$key' AND type='$type'";

        $rs = $conn->Execute($query);        
        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return FALSE;
        }
        
        if (!$rs->EOF) 
        {
            return $rs->numrows();
        }
        
        return FALSE;
    }


    // Insert new document and returns auto-generated Id
    public static function insert($conn, $title, $text, $keywords, $in_charge)
    {
        $creator = Session::get_session_user();        
        
        $text = preg_replace("/\r/", "", $text);
        $text = preg_replace("/\A(\n)+/", "", $text);
        $text = preg_replace("/(\n){3,}/", "\n\n", $text);
        $text = preg_replace("/(\n)+\z/", "", $text);
        
        $query = "INSERT into repository ( title, text, date, keywords, creator, in_charge ) VALUES   (?,?,?,?,?,?)";
        
        $params = array(
            $title,
            $text,
            date("Y-m-d") ,
            $keywords,
            $creator,
            $in_charge
        );
        

        if ($conn->Execute($query, $params) == FALSE) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
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
    public static function update($conn, $id_document, $title, $text, $keywords)
    {   

        $text = preg_replace("/\r/", "", $text);
        $text = preg_replace("/\A(\n)+/", "", $text);
        $text = preg_replace("/(\n){3,}/", "\n\n", $text);
        $text = preg_replace("/(\n)+\z/", "", $text);

        $query  = "UPDATE repository SET title=?, text=?, date=?, keywords=? WHERE id=$id_document";
        $params = array(
            $title,
            $text,
            date("Y-m-d") ,
            $keywords
        );
        
        if ($conn->Execute($query, $params) == FALSE) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        $infolog = array($id_document);        
        Log_action::log(59, $infolog);
    }
        
    
    // Link a file to a document and returns the file in-disk name (iddocument_idfile.type)
    public static function attach($conn, $id_document, $filename)
    {
        // Check if exists
        $query = "SELECT * FROM repository_attachments WHERE name = ?";
        
        $rs = $conn->Execute($query, array($filename));
        if ($rs)
        {
            if ($rs->numrows() > 0) 
            {
                return array (FALSE, _('Error inserting: File exists in Database'));
            }
        }
        
        $type  = preg_replace("/.*\.(..?.?)$/", "\\1", $filename);
        $query = "INSERT into repository_attachments ( id_document, name, type ) VALUES   (?,?,?)";
        
        $params = array(
            $id_document,
            $filename,
            $type
        );
        
        if ($conn->Execute($query, $params) == FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        $infolog = array($filename, $id_document);
        
        Log_action::log(60, $infolog);
        
        // Get lastid
        $res = $conn->query("SELECT LAST_INSERT_ID() as lastid");
        
        if ($rw = $res->fetchRow()) 
        {
            $id_file = $rw["lastid"];
        }
        
        return array (TRUE, $id_document . "_" . $id_file . "." . $type);
    }

    
    // Get a list with files linked to a document by id
    public static function get_attachments($conn, $id_document)
    {
        $filelist = array();
        
        // Get attachments
        $query = "SELECT * FROM repository_attachments WHERE id_document=$id_document";
        
        $rs_files = $conn->Execute($query);
        if ($rs_files) 
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
    public static function get_attachment($conn, $id_document, $id)
    {
        $file = "";
        $name = "";

        // Get attachments
        $query = "SELECT * FROM repository_attachments WHERE id_document=$id_document AND id=$id";

        $rs_files = $conn->Execute($query);

        if ($rs_files) 
        {            
            $file = $rs_files->fields["id_document"]."_".$rs_files->fields["id"].".".$rs_files->fields["type"];
            $name = $rs_files->fields["name"];            
        }
        
        return array($file, $name);        
    }


    // Get a list with relationships
    public static function get_relationships($conn, $id_document)
    {
        $rellist = array();
        
        // Get relationships
        $query = "SELECT * FROM repository_relationships WHERE id_document=$id_document";
        
        $rs_files = $conn->Execute($query);
        if ($rs_files) 
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
    public static function insert_snort_references($conn, $id_document, $pid, $sid)
    {
        // Check if exists or autoinsert values
        $conn->Execute("INSERT IGNORE INTO alienvault_siem.reference_system VALUES (NULL,'kdb')");
        
        $query = "SELECT ref_system_id FROM alienvault_siem.reference_system WHERE ref_system_name='kdb'";
        
        $rs = $conn->Execute($query);
        if (!$rs) 
        {
            return _('DB Error: ').$conn->ErrorMsg();
        }
        
        $refsystemid = $rs->fields['ref_system_id'];
        $conn->Execute("INSERT IGNORE INTO alienvault_siem.reference VALUES (NULL,$refsystemid,'$id_document')");
        
        $query = "SELECT ref_id FROM alienvault_siem.reference WHERE ref_system_id=$refsystemid and ref_tag='$id_document'";
        
        $rs1 = $conn->Execute($query);
        if (!$rs1)
        { 
            return _('DB Error: ').$conn->ErrorMsg();
        }
        
        $refid = $rs1->fields['ref_id'];
        $conn->Execute("INSERT IGNORE INTO alienvault_siem.sig_reference VALUES ($pid, $sid, $refid)");
        
        return '';
    }
    
        
    // Delete a  alienvault_siem reference
    public static function delete_snort_references($conn, $id_document)
    {
        // Check if exists or autoinsert values
        $query = "SELECT ref_system_id FROM alienvault_siem.reference_system WHERE ref_system_name='kdb'";
        
        $rs = $conn->Execute($query);
        if (!$rs)
        {
            return _('DB Error: ').$conn->ErrorMsg();
        }
        
        $refsystemid = $rs->fields['ref_system_id'];
        
        if ($refsystemid!="") 
        {
            $query = "SELECT ref_id FROM alienvault_siem.reference WHERE ref_system_id=$refsystemid and ref_tag='$id_document'";
            
            $rs1 = $conn->Execute($query);
            if (!$rs1)
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
    public static function insert_relationships($conn, $id_document, $table, $key)
    {
        // Check if exists
        $query = "SELECT * FROM repository_relationships WHERE keyname='$key' AND type='$table' AND id_document=$id_document";
        
        $rs = $conn->Execute($query);
        if ($rs) 
        {
            if ($rs->numrows() > 0)
            {
                return _('Error inserting: File exists in Database').'<BR>';
            }
        }
        
        $query  = "INSERT into repository_relationships ( id_document, type, keyname ) VALUES (?,?,?)";
        $params = array($id_document, $table, $key);
        
        if ($conn->Execute($query, $params) == FALSE) 
        {
            return _('Error inserting: ') . $conn->ErrorMsg() . '<BR>';
        }
        
        $infolog = array($key, $id_document);        
        Log_action::log(62, $infolog);
        
        return '';
    }
    
    
    // Delete a link
    public static function delete_relationships($conn, $id_document, $key)
    {
        // Delete link
        $query  = "DELETE FROM repository_relationships WHERE id_document = ? AND keyname=?";
        $params = array($id_document, $key);
        
        if ($conn->Execute($query, $params) === FALSE)
        {
            return  _('Error deleting: ') . $conn->ErrorMsg() . '<BR>';
        }

        $infolog = array($id_document, $key);

        Log_action::log(63, $infolog);

        return '';
    }


    // Delete a document, and linked files
    public static function delete($conn, $id_document)
    {
        // Delete relationships
        $query  = "DELETE FROM repository_relationships WHERE id_document = ?";
        $params = array($id_document);
        
        if ($conn->Execute($query, $params) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        // Delete attachments
        $query  = "DELETE FROM repository_attachments WHERE id_document = ?";
        $params = array($id_document);

        if ($conn->Execute($query, $params) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
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
                    if ($entry == "." || $entry == "..")
                    {
                        continue;
                    }

                    @unlink("/tmp/ossim/$id_document/$entry");
                }

                @rmdir("/tmp/ossim/$id_document");
            }
        }

        // Delete document
        $query  = "DELETE FROM repository WHERE id = ?";
        $params = array($id_document);

        if ($conn->Execute($query, $params) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $infolog = array($id_document);
        Log_action::log(58, $infolog);
    }
    
    
    // Delete file and row attached to a document by file id
    public static function delete_attachment($conn, $id, $uploads_dir)
    {
        $query = "SELECT * FROM repository_attachments WHERE id=$id";

        $rs = $conn->Execute($query);
        if ($rs)
        {
            if (!$rs->EOF)
            {
                // Del filesystem file
                $disk_file = $rs->fields['id_document'] . "_" . $id . "." . $rs->fields['type'];
                //system ("sudo rm -f $uploads_dir/$disk_file");
                @unlink("$uploads_dir/" . $rs->fields['id_document'] . "/$disk_file");

                // Del database row
                $query  = "DELETE FROM repository_attachments WHERE id = ?";
                $params = array($id);

                if ($conn->Execute($query, $params) === FALSE)
                {
                    Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
                }
            }
        }

        $infolog = array($disk_file);
        Log_action::log(61, $infolog);
    }


    // Get host or host_group or net or net_group List
    public static function get_hostnet($conn, $table)
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
            $query = "SELECT SQL_CALC_FOUND_ROWS * FROM $table";

            $rs = $conn->Execute($query);
            if (!$rs)
            {
                Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());

                return array();
            }

            $resall = $conn->Execute("SELECT FOUND_ROWS() as contador");

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
                            "key"  => $rs->fields["id"],
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

                $ret[] = array("key" => $id, "name" => $name);
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

        return array($ret, $num_rows);
    }


    public static function get_perms($dbconn) //get the editing perms for list
    {
        $conf    = $GLOBALS["CONF"];
        $version = $conf->get_conf("ossim_server_version");

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


    public static function check_doc_availability($in_charge, $creator, $doc_perms)
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

        if (Session::am_i_admin() || $me == $creator || $doc_perms['user_perms'][$creator] > 1)
        {
            return TRUE;
        }
        else
        {
            if ($user_perm != '')
            {
                if ( $user_perm == "0" || $doc_perms['user_perms'][$user_perm] > 1 )
                {
                    return TRUE;
                }
            }
            elseif ($entity_perm != '')
            {
                if ( $doc_perms['entity_perms'][$entity_perm] >= 1 )
                {
                    return TRUE;
                }
            }
        }

        return FALSE;
    }


    public static function can_i_modify($creator, $perms) //using the perms to check if i can modify
    {
        if ($perms['user_perms'][$creator] > 1 || Session::am_i_admin() || $creator == 0)
        {
            return TRUE;
        }

        return FALSE;
    }
}

/* End of file repository.inc */
/* Location: ../include/classes/repository.inc */
