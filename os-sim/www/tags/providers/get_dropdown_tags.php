<?php

/**
 * get_tags.php
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
 */

require_once 'av_init.php';


/********************************
 ****** CHECK USER SESSION ******
 ********************************/

Session::useractive();

// Response array
$response = array();


/*****************************
 ****** VALIDATE PARAMS ******
 *****************************/

// Get params
$tag_type           = POST('tag_type');
$search_str         = POST('search_str');
$select_from_filter = POST('select_from_filter');

// Validate action type
ossim_valid($tag_type,              OSS_ALPHA, '_',                             'illegal: '._('Label type'));
ossim_valid($search_str,            OSS_ALPHA, OSS_SCORE, OSS_NULLABLE, '_',    'illegal: '._('Search string'));
ossim_valid($select_from_filter,    OSS_LETTER,                                 'illegal: '._('Filter value'));

if (ossim_error())
{
    $response['status'] = 'error';
    $response['data']   = ossim_get_error_clean();

    echo json_encode($response);
    exit();
}


/**********************
 ****** GET DATA ******
 **********************/

// Database access object
$db   = new ossim_db();
$conn = $db->connect();

try
{
    $filters = array();

    $filters['where'] = 'tag.type = "'.$tag_type.'"';

    if ($search_str != '')
    {
        $search_str = escape_sql($search_str, $conn);

        $filters['where'] .= ' AND tag.name LIKE "%'.$search_str.'%"';
    }

    $filters['order_by'] = 'tag.name ASC';

    // Tag list
    list($total, $tags) = Tag::get_list($conn, '', $filters);

    // Results array
    $results = array();

    foreach ($tags as $tag_id => $tag)
    {
        $_res               = array();
        $_res['id']         = $tag_id;
        $_res['class']      = $tag->get_class();
        $_res['name']       = $tag->get_name();
        $_res['components'] = $tag->get_components($conn);
        $_res['mark_state'] = 0;

        $results[$tag_id] = $_res;
    }

    if (empty($results))
    {
        Av_exception::throw_error(Av_exception::USER_ERROR, _('No labels found'));
    }

    // If selected_from_filter, calculate tags marked
    if ($select_from_filter == 'true')
    {
        // Get tag with some selected component
        $query  = 'SELECT hex(c.id_tag) AS id, count(c.id_tag) AS total FROM component_tags c, user_component_filter u, tag t
                    WHERE t.id = c.id_tag AND c.id_component = u.asset_id AND t.type = ? AND u.session_id = ? GROUP BY c.id_tag;';

        $params = array
        (
            $tag_type,
            session_id()
        );
        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $tag_with_selected_components = array();

        while (!$rs->EOF)
        {
            $tag_with_selected_components[$rs->fields['id']] = $rs->fields['total'];

            $rs->MoveNext();
        }

        // Get total selected components by tag
        $query  = 'SELECT * from user_component_filter WHERE asset_type = ? AND session_id = ?';

        $params = array
        (
            $tag_type,
            session_id()
        );
        
        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $total_selected_components = Ossim_db::get_found_rows($conn, $query);

        foreach ($tag_with_selected_components as $tag_id => $tag_total)
        {
            $results[$tag_id]['mark_state'] = ($tag_total < $total_selected_components) ? 2 : 1;
        }
    }

    $response['status'] = 'OK';
    $response['data']  = $results;
}
catch (Exception $e)
{
    $response['status'] = 'error';
    $response['data']  = $e->getMessage();
}

$db->close();

echo json_encode($response);
exit();
