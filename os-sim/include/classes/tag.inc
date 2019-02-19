<?php

/**
 * Tag.inc
 *
 * File Tags.inc is used to:
 *   - Task 1 (TO DO)
 *   - Task 2 (TO DO)
 *   - Task 3 (TO DO)
 *
 *
 * License:
 *
 * Copyright (c) 2003-2006 ossim.net
 * Copyright (c) 2007-2015 AlienVault
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
 * @package    ossim-framework
 * @autor      AlienVault INC
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt
 * @copyright  2003-2006 ossim.net
 * @copyright  2007-2015 AlienVault
 * @link       https://www.alienvault.com/
 */
class Tag
{
    /**
     * ID
     *
     * @var string
     */
    private $id;

    /**
     * Context
     *
     * @var string
     */
    private $ctx;

    /**
     * Name
     *
     * @var string
     */
    private $name;

    /**
     * Type
     *
     * @var string
     */
    private $type;

    /**
     * Class
     *
     * @var string
     */
    private $class;


    /**
     * This function sets up the class object
     *
     * @param string $id
     */
    public function __construct($id)
    {
        $this->set_id($id);
        $this->set_ctx(Util::get_default_ctx());

        $this->name  = '';
        $this->type  = '';
        $this->class = '';
    }


    /**
     * Set ID
     *
     * @param string $id
     */
    public function set_id($id)
    {
        if (!empty($id) && valid_hex32($id))
        {
            $this->id = strtoupper($id);
        }
        else
        {
            $exp_msg = _('Error! Tag ID is not valid');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
    }


    /**
     * Set CTX
     *
     * @param string $ctx
     */
    public function set_ctx($ctx)
    {
        if (!empty($ctx) && valid_hex32($ctx))
        {
            $this->ctx = strtoupper($ctx);
        }
        else
        {
            $exp_msg = _('Error! Tag context is not valid');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
    }


    /**
     * Set name
     *
     * @param string $name
     */
    public function set_name($name)
    {
        if ($name != '')
        {
            $this->name = Util::htmlentities($name);
        }
    }


    /**
     * Set type
     *
     * @param string $type
     */
    public function set_type($type)
    {
        $this->type = $type;
    }


    /**
     * Set class
     *
     * @param string $class
     */
    public function set_class($class)
    {
        $this->class = $class;
    }


    /**
     * Get ID
     *
     * @return string
     */
    public function get_id()
    {
        return $this->id;
    }


    /**
     * Get ctx
     *
     * @return string
     */
    public function get_ctx()
    {
        return $this->ctx;
    }


    /**
     * Get name
     *
     * @return string
     */
    public function get_name()
    {
        return $this->name;
    }


    /**
     * Get type
     *
     * @return string
     */
    public function get_type()
    {
        return $this->type;
    }


    /**
     * Get class
     *
     * @return string
     */
    public function get_class()
    {
        return $this->class;
    }


    /**
     * Function load_from_db
     *
     * This function sets the tag data from database
     *
     * @param object $conn Database access object
     */
    public function load_from_db($conn)
    {
        Ossim_db::check_connection($conn);

        // Get tag data
        $query  = 'SELECT HEX(id) as id, HEX(ctx) AS ctx, name, type, class FROM tag t WHERE t.id = UNHEX(?)';
        $params = array($this->get_id());

        $rs = $conn->Execute($query, $params);

        if (empty($rs->fields['id']))
        {
            $exp_msg = _('Error! Tag not found');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $this->ctx   = $rs->fields['ctx'];
        $this->name  = $rs->fields['name'];
        $this->type  = $rs->fields['type'];
        $this->class = $rs->fields['class'];
    }


    /**
     * Function save_in_db
     *
     * This function saves tag into database
     *
     * @param object $conn Database access object
     *
     * @access public
     * @return bool
     * @throws Av_exception If an error occurred
     */
    public function save_in_db($conn)
    {
        Ossim_db::check_connection($conn);

        $params = array(
            $this->get_id(),
            $this->get_name(),
            $this->get_ctx(),
            $this->get_type(),
            $this->get_class()
        );

        $query = 'REPLACE INTO tag (id, name, ctx, type, class) VALUES (UNHEX(?), ?, UNHEX(?), ?, ?)';
        $rs    = $conn->Execute($query, $params);

        if (!$rs)
        {
            $exp_msg = _('Error! Tag could not be saved');

            Av_exception::throw_error(Av_exception::DB_ERROR, $exp_msg);
        }

        return TRUE;
    }


    /**
     * Function add_component
     *
     * This function links a component with a tag
     *
     * @param object $conn         Database access object
     * @param string $component_id Compoenent ID
     *
     * @access public
     * @return boolean
     * @throws Av_exception If an error occurred
     */
    public function add_component($conn, $component_id)
    {
        Ossim_db::check_connection($conn);

        $params = array($component_id, $this->get_id());

        $query = 'REPLACE INTO component_tags (id_component, id_tag) VALUES (UNHEX(?), UNHEX(?))';
        $rs    = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        return TRUE;
    }


    /**
     * Function remove_component
     *
     * This function unlink a component from a tag
     *
     * @param object $conn         Database access object
     * @param string $component_id Component ID
     *
     * @access  public
     * @return  boolean
     * @throws  Av_exception  If an error occurred
     */
    public function remove_component($conn, $component_id)
    {
        Ossim_db::check_connection($conn);

        $params = array($component_id, $this->get_id());

        $query = 'DELETE FROM component_tags WHERE id_component = UNHEX(?) AND id_tag = UNHEX(?)';
        $rs    = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        return TRUE;
    }


    /**
     * Function save_components_from_filter
     *
     * This function save tags components from search in db
     *
     * @param object $conn Database access object
     * @param array  $tags Tags id list
     *
     * @access public
     * @throws Av_exception If a connection error occurred
     * @return void
     */
    public function add_components_from_filter($conn)
    {
        Ossim_db::check_connection($conn);

        $perms = Asset_host::get_perms_where('h.', TRUE);

        $query = "REPLACE INTO component_tags (
                    SELECT f.asset_id, UNHEX(?)
                        FROM user_component_filter f, host h
                        WHERE f.session_id=? AND f.asset_type='asset' AND h.id=f.asset_id $perms)";

        $params = array(
            $this->get_id(),
            session_id()
        );

        Util::memcacheFlush();

        if ($conn->Execute($query, $params) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }


    /**
     * Function remove_components_from_filter
     *
     * This function delete tags components from search in db
     *
     * @param object $conn Database access object
     * @param array  $tags Tags id list
     *
     * @access public
     * @throws Av_exception If a connection error occurred
     * @return void
     */
    public function remove_components_from_filter($conn)
    {
        Ossim_db::check_connection($conn);

        $perms = Asset_host::get_perms_where('h.', TRUE);

        $query = "DELETE FROM component_tags WHERE id_component IN (
                        SELECT f.asset_id
                            FROM user_component_filter f, host h
                            WHERE f.session_id=? AND f.asset_type='asset' AND h.id=f.asset_id $perms)
                      AND id_tag = (UNHEX(?))";

        $params = array(
            session_id(),
            $this->get_id()
        );

        Util::memcacheFlush();

        if ($conn->Execute($query, $params) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }


    /**
     * Function get_components
     *
     * This function get components from a tag
     *
     * @param $conn  Database access object
     *
     * @access public
     * @return array
     * @throws Av_exception If a connection error occurred
     */
    public function get_components($conn)
    {
        Ossim_db::check_connection($conn);

        $components = array();
        $params     = array($this->get_id());

        $query = 'SELECT hex(id_component) as id FROM component_tags WHERE id_tag = UNHEX(?)';
        $rs    = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $components[] = $rs->fields['id'];
            $rs->MoveNext();
        }

        $rs->Free();

        return $components;
    }


    /**
     * Function is_in_db
     *
     * This function checks if tag exists into database
     *
     * @param object $conn Database access object
     * @param string $id   Tag ID
     *
     * @access public
     * @return bool
     * @throws Av_exception If a connection error occurred
     */
    public static function is_in_db($conn, $id)
    {
        Ossim_db::check_connection($conn);

        $res = FALSE;

        if (!valid_hex32($id))
        {
            return $res;
        }

        $query  = 'SELECT count(*) AS found FROM tag WHERE id = UNHEX(?)';
        $params = array($id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (intval($rs->fields['found']) > 0)
        {
            $res = TRUE;
        }

        return $res;
    }


    /**
     * Function get_object
     *
     * This function returns a tag object by id
     *
     * @param object $conn Database acces object
     * @param string $id   Tag ID
     *
     * @access public
     * @return object
     * @throws Av_exception If a connection error occurred
     */
    public static function get_object($conn, $id)
    {
        Ossim_db::check_connection($conn);

        $tag = NULL;

        $params = array($id);
        $query  = 'SELECT HEX(id) AS id FROM tag WHERE id = UNHEX(?)';

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!$rs->EOF)
        {
            $tag = new self($id);
            $tag->load_from_db($conn);
        }

        return $tag;
    }


    /**
     * Function delete_from_db
     *
     * This function deletes a tag from database
     *
     * @param object $conn Database access object
     * @param string $id   Tag ID
     *
     * @access public
     * @return boolean
     * @throws Av_exception If an error occurred
     */
    public static function delete_from_db($conn, $id)
    {
        Ossim_db::check_connection($conn);

        //Begin transaction
        $conn->StartTrans();

        $queries = array();
        $params  = array($id);

        $queries[] = 'DELETE FROM tag WHERE id = UNHEX(?)';
        $queries[] = 'DELETE FROM component_tags WHERE id_tag = UNHEX(?)';

        foreach ($queries as $query)
        {
            $rs = $conn->Execute($query, $params);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
        }

        // Complete transaction
        if (!$conn->CompleteTrans())
        {
            $exp_msg = _('Error! Tag could not be deleted');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return TRUE;
    }


    /**
     * This function returns the name from tag
     *
     * @param object $conn   Database access object
     * @param string $tag_id Tag ID
     *
     * @throws Exception  If a connection error occurred
     *
     * @return string
     */
    public static function get_name_by_id($conn, $tag_id)
    {
        Ossim_db::check_connection($conn);

        $tag_name = '';

        $query  = 'SELECT `name` FROM tag WHERE id = UNHEX(?)';
        $params = array($tag_id);
        $rs     = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if ($rs->fields['name'] != '')
        {
            $tag_name = $rs->fields['name'];
        }

        return $tag_name;
    }


    /**
     * Function get_list
     *
     * This function returns a filtered tag list
     *
     * @param object $conn    Database access object
     * @param string $tables  [Optional] Database tables separated by comma (Join with main table)
     * @param array  $filters [Optional] SQL statements (WHERE, LIMIT, ORDER BY ...)
     *
     * @return array
     * @throws Exception  If a connection error occurred
     */
    public static function get_list($conn, $tables = '', $filters = array())
    {
        Ossim_db::check_connection($conn);

        $tags = array();

        $q_select = 'tag.*, HEX(id) AS id, HEX(ctx) AS ctx';
        $q_where  = 'WHERE 1 = 1';

        if (!empty($filters['where']))
        {
            $q_where .= ' AND '.$filters['where'];
        }

        if (!empty($filters['order_by']))
        {
            $q_where .= ' ORDER BY '.$filters['order_by'];
        }

        if (!empty($filters['limit']))
        {
            $q_select = 'SQL_CALC_FOUND_ROWS '.$q_select;
            $q_where .= ' LIMIT '.$filters['limit'];
        }

        $conn->SetFetchMode(ADODB_FETCH_ASSOC);

        $query = 'SELECT DISTINCT '.$q_select.' FROM tag '.$tables.' '.$q_where;

        $rs = $conn->Execute($query);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!empty($filters['limit']))
        {
            $total = Ossim_db::get_found_rows($conn, $query);
        }
        else
        {
            $total = $rs->RecordCount();
        }

        while (!$rs->EOF)
        {
            $tag = new self($rs->fields['id']);
            $tag->set_name($rs->fields['name']);
            $tag->set_type($rs->fields['type']);
            $tag->set_class($rs->fields['class']);

            $tags[$tag->get_id()] = $tag;

            $rs->MoveNext();
        }

        $rs->Free();

        return array($total, $tags);
    }


    /**
     * Function get_tags_by_type
     *
     * This function gets a tag list by type
     *
     * @param object $conn Database access object
     * @param string $type Tag type
     *
     * @access public
     * @return array
     */
    public static function get_tags_by_type($conn, $type)
    {
        Ossim_db::check_connection($conn);

        list($total, $tags) = self::get_list($conn, '', array('where' => 'type = \''.$type.'\''));

        return array($total, $tags);
    }


    /**
     * Function get_tags_by_component
     *
     * This function returns a tag list by one component
     *
     * @param object $conn         Database access object
     * @param string $component_id Component ID
     *
     * @access public
     * @return array
     * @throws Av_exception If an error occurs
     */
    public static function get_tags_by_component($conn, $component_id)
    {
        Ossim_db::check_connection($conn);

        $component_tags = array();
        $params         = array($component_id);

        $query = 'SELECT hex(id_tag) AS id FROM component_tags WHERE id_component = UNHEX(?)';
        $rs    = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $tag                               = self::get_object($conn, $rs->fields['id']);
            $component_tags[$rs->fields['id']] = $tag;

            $rs->MoveNext();
        }

        return $component_tags;
    }


    /**
     * Function get_tags_by_component_list
     *
     * This function gets a tag list by component list
     *
     * @param object $conn              Database access object
     * @param array  $component_id_list Component ID list
     *
     * @access public
     * @return array
     */
    public static function get_tags_by_component_list($conn, $component_id_list)
    {
        Ossim_db::check_connection($conn);

        $components_tags = array();

        foreach ($component_id_list as $component_id)
        {
            $components_tags[$component_id] = self::get_tags_by_component($conn, $component_id);
        }

        return $components_tags;
    }
}

/* End of file tag.inc */
/* Location: ../include/classes/tag.inc */
