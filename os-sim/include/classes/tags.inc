<?php
/**
* Tags.inc
*
* File Tags.inc is used to:
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
* @package    ossim-framework\Alarms
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/

/**
* Function list:
* - insert()
* - delete()
* - get_list()
*/

class Tags
{
    /**
     * ID
     * @var number
     */
	public $id;

	/**
	 * Context
	 * @var string
	 */
	public $ctx;

	/**
	 * Name
	 * @var string
	 */
	public $name;

	/**
	 * Background color
	 * @var string
	 */
    public $bgcolor;

    /**
     * Foreground color
     * @var string
     */
    public $fgcolor;

    /**
     * Italic
     * @var boolean
     */
    public $italic;

    /**
     * Bold
     * @var boolean
     */
    public $bold;

    /**
     * This function returns the ID
     * 
     * @return number
     */
	public function get_id()
	{
        return $this->id;
    }

    /**
     * This function returns the Context
     * 
     * @return string
     */
	public function get_ctx()
	{
        return $this->ctx;
    }

    /**
     * This function returns the Name
     * 
     * @return string
     */
    public function get_name()
    {
        return $this->name;
    }

    /**
     * This function returns the Background color
     * 
     * @return string
     */
	public function get_bgcolor()
	{
        return $this->bgcolor;
    }

    /**
     * This function returns the Foreground color
     * 
     * @return string
     */
	public function get_fgcolor()
	{
        return $this->fgcolor;
    }

    /**
     * This function returns the Italic
     * 
     * @return boolean
     */
	public function get_italic()
	{
        return $this->italic;
    }

    /**
     * This function returns the Bold
     * 
     * @return boolean
     */
	public function get_bold()
	{
        return $this->bold;
    }

    /**
     * This function sets up the class object
     * 
     * @param number $id
     * @param string $ctx
     * @param string $name
     * @param string $bgcolor
     * @param string $fgcolor
     * @param boolean $italic
     * @param boolean $bold
     */
    public function __construct($id, $ctx, $name, $bgcolor, $fgcolor, $italic, $bold)
    {
        $this->id      = $id;
        $this->ctx     = $ctx;
        $this->name    = $name;
        $this->bgcolor = $bgcolor;
        $this->fgcolor = $fgcolor;
        $this->italic  = $italic;
        $this->bold    = $bold;
    }

    /**
     * This function returns the tags objects list
     * 
     * @param object $conn
     * @param string $args
     * 
     * @return multitype:Tags
     */
    public static function get_list($conn, $args = '')
    {
        Ossim_db::check_connection($conn);

        $list  = array();
        $query = ossim_query("SELECT *,hex(ctx) as ctx FROM tags_alarm $args");

		$rs = $conn->Execute($query);

	    if (!$rs)
		{
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            while (!$rs->EOF)
            {
                $list[$rs->fields['id']] = new self($rs->fields['id'], $rs->fields['ctx'], $rs->fields["name"], $rs->fields["bgcolor"],
                    $rs->fields["fgcolor"], $rs->fields["italic"], $rs->fields["bold"]);

                $rs->MoveNext();
            }
        }

        return $list;
    }

    /**
     * This function returns the tags objects list in html format
     * 
     * @param object $conn
     * @param string $args
     * @param boolean $delete
     * 
     * @return multitype:string
     */
	public static function get_list_html($conn, $args = '', $delete = TRUE)
	{
        Ossim_db::check_connection($conn);

        $list  = array();
        $query = ossim_query("SELECT * FROM tags_alarm $args");

		$rs = $conn->Execute($query);

	    if (!$rs)
		{
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            while (!$rs->EOF)
            {
                $list[$rs->fields['id']] = '<div class="tag_'. $rs->fields['id'] .' transparent" style="display:inline;clear:both;padding:4px 6px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;border:0px;text-transform:none !important;background-color:#'.$rs->fields["bgcolor"].' !important;color:#'.$rs->fields["fgcolor"].' !important;font-weight:'.(($rs->fields["bold"]) ? "bold" : "normal").';font-style:'.(($rs->fields["italic"]) ? "italic" : "none").';white-space:nowrap">'.$rs->fields["name"];

                if ($delete)
                {
                    $list[$rs->fields['id']] .= ' <a href="javascript:;" title="'._("Remove label").'" class="remove_tag stop" data-tag="'. $rs->fields['id'] .'" style="font-weight:bold;color:#'.$rs->fields["fgcolor"].'">x</a>';
                }

                $list[$rs->fields['id']] .= '</div>';

                $rs->MoveNext();
            }
        }

        return $list;
    }

    /**
     * This function inserts a new row in tags_alarm table
     * 
     * @param object $conn
     * @param string $name
     * @param string $bgcolor
     * @param string $fgcolor
     * @param boolean $italic
     * @param boolean $bold
     * 
     * @return number
     */
    public static function insert($conn, $name, $bgcolor, $fgcolor, $italic, $bold)
    {
        	Ossim_db::check_connection($conn);
    
        	$id = -1;
    
        	$params = array(
        		$name,
        		$bgcolor,
        		$fgcolor,
        		$italic,
        		$bold
        	);
    
        	$query = "INSERT INTO tags_alarm (name, bgcolor, fgcolor, italic, bold) VALUES (?,?,?,?,?)";
    
        	$rs = $conn->Execute($query, $params);

	    if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            $res = $conn->query("SELECT LAST_INSERT_ID() as lastid");

            $rw  = $res->fetchRow();
            
            if ($rw)
            {
                $id = $rw["lastid"];
            }
        }

        return $id;
    }

    /**
     * This function updates a row in tags_alarm table
     * 
     * @param object $conn
     * @param number $id
     * @param string $name
     * @param string $bgcolor
     * @param string $fgcolor
     * @param boolean $italic
     * @param boolean $bold
     */
	public static function update($conn, $id, $name, $bgcolor, $fgcolor, $italic, $bold)
	{
        	Ossim_db::check_connection($conn);
    
        	$params = array(
        		$name,
        		$bgcolor,
        		$fgcolor,
        		$italic,
        		$bold,
        		$id
        	);
    
        	$query = "UPDATE tags_alarm SET name = ?, bgcolor = ?, fgcolor = ?,italic = ?,bold = ? WHERE id = ?";
    
        	$rs = $conn->Execute($query, $params);

	    if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }

    /**
     * This function removes a row in tags_alarm table
     * 
     * @param object $conn
     * @param number $id
     */
	public static function delete($conn, $id)
	{
        	Ossim_db::check_connection($conn);
    
        	$params = array($id);
    
        	$query = "DELETE FROM tags_alarm WHERE id = ? AND ctx=unhex('00000000000000000000000000000000')";
    
        	$rs = $conn->Execute($query, $params);

	    if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $query = "DELETE a FROM alarm_tags a LEFT JOIN tags_alarm t ON t.id=a.id_tag WHERE t.id IS NULL";
        $rs = $conn->Execute($query);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }

    /**
     * This function links an alarm with a tag
     * 
     * @param object $conn
     * @param string $alarm_id
     * @param number $tag_id
     */
    public static function set_alarm_tag($conn, $alarm_id, $tag_id)
    {
        	Ossim_db::check_connection($conn);
    
        	$params = array($alarm_id, $tag_id);

        $query = "REPLACE INTO alarm_tags (id_alarm, id_tag) VALUES (UNHEX(?), ?)";

        $rs = $conn->Execute($query, $params);

	    if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }

    /**
     * This function unlinks a tag from an alarm
     * 
     * @param object $conn
     * @param string $alarm_id
     * @param number $tag_id
     */
	public static function del_alarm_tag($conn, $alarm_id, $tag_id = 0)
	{
        	Ossim_db::check_connection($conn);
    
        	if ($tag_id > 0)
        	{
        	   $params = array($alarm_id, abs($tag_id));
        	   $query  = "DELETE FROM alarm_tags WHERE id_alarm=unhex(?) AND id_tag = ?";
    
        	}
        else
        {
            $params = array($alarm_id);
            $query  = "DELETE FROM alarm_tags WHERE id_alarm = UNHEX(?)";
        }

        $rs = $conn->Execute($query, $params);

	    if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }

    /**
     * This function gets a tag list by alarm list
     * 
     * @param object $conn
     * @param array $alarm_list
     * 
     * @return array
     */
    public static function get_tags_from_list($conn, $alarm_list)
    {
        	Ossim_db::check_connection($conn);
    
        	$list = array();
        	$ids  = '';
        	foreach($alarm_list as $alarm)
        	{
        		$ids .= ($ids != '') ? ",UNHEX('".$alarm->get_backlog_id()."')" : "UNHEX('".$alarm->get_backlog_id()."')";
        	}
    
        	if ($ids != '')
        	{
        	    	$query = "SELECT * FROM alarm_tags WHERE id_alarm IN ($ids)";
        
        	    	$rs = $conn->Execute($query);
        
        	    	if (!$rs)
        	    	{
	            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
	        }
	        else
	        {
		        while (!$rs->EOF)
		        {
	                $list[strtoupper(bin2hex($rs->fields['id_alarm']))] = $rs->fields['id_tag'];

	                $rs->MoveNext();
	            }
	        }
        }

        return $list;
    }

    /**
     * This function returns a tag list by one alarm
     * 
     * @param object $conn
     * @param string $alarm_id
     * 
     * @return array
     */
    public static function get_tags_from_alarm($conn, $alarm_id)
    {
        	Ossim_db::check_connection($conn);
    
        	$list   = array();
    
        	$query  = "SELECT id_tag FROM alarm_tags WHERE id_alarm = unhex('$alarm_id')";
    
        	$rs = $conn->Execute($query);

	    if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
	        while (!$rs->EOF)
	        {
                $list[] = $rs->fields['id_tag'];

                $rs->MoveNext();
            }
        }

        return $list;
    }
}

/* End of file tags.inc */
/* Location: ../include/classes/tags.inc */