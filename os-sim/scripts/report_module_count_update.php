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


require_once 'av_init.php';


function get_parameters($sr, $dbconn)
{
    $inpt = array();

    if ($sr["inputs"]!="")
    {
        $input = explode(";",$sr["inputs"]);

        foreach ($input as $inpu)
        {
            $inpus   = explode(":",$inpu);
            $default = ($inpus[2]=="select") ? $inpus[5] : $inpus[4];

            if ($inpus[4]=="CATEGORY" && $default)
            {
                $category = $default; $default = GetPluginCategoryName($default, $dbconn);
            }

            if ($inpus[4]=="SUBCATEGORY" && $default)
            {
                $default = GetPluginSubCategoryName(array($category, $default), $dbconn);
            }

            if ($inpus[2]=="checkbox")
            {
                $default = (!$default) ? "false" : "true";
            }

            $inpt[] = $inpus[0] . ($default ? ": <b>$default</b>" : "");
        }
    }
    return $inpt;
}


function menu_type($type)
{
    $needle = 'Product';
    $pos    = strripos($type, $needle);

    if ($pos === FALSE)
    {
        $needle = 'Category';
        $pos    = strripos($type, $needle);

        if ($pos === FALSE)
        {
            $ret = 1;
        }
        else
        {
            $ret = 2;
        }
    }
    else
    {
        $needle = 'Category';
        $pos    = strripos($type, $needle);

        if ($pos === FALSE)
        {
            $ret = 4;
        }
        else
        {
            $ret = 3;
        }
    }

    return $ret;
}


function GetSourceTypes($db)
{
    $srctypes = array();
    $temp_sql = "select * from alienvault.product_type order by name";

    $tmp_result = $db->Execute($temp_sql);

    while (!$tmp_result->EOF)
    {
        $myrow = $tmp_result->fields;
        $srctypes[$myrow["id"]] = $myrow["name"];
        $tmp_result->MoveNext();
    }

    if ($tmp_result)
    {
        $tmp_result->free();
    }

    return $srctypes;
}


function GetPluginCategories($db, $forced_sql = "")
{
    $categories = array();

    if ($forced_sql != "")
    {
        $sql = "SELECT DISTINCT category.* FROM plugin, product_type, plugin_sid LEFT JOIN category ON category.id=plugin_sid.category_id WHERE category.id IS NOT NULL AND plugin.id=plugin_sid.plugin_id AND product_type.id=plugin.product_type $forced_sql ORDER BY NAME";
    }
    else
    {
        $sql = "SELECT * FROM alienvault.category ORDER BY name";
    }

    $rs = $db->Execute($sql);

    while (!$rs->EOF)
    {
        $categories[$rs->fields["id"]] = str_replace("_", " ", $rs->fields["name"]);
        $rs->MoveNext();
    }

    if ($rs)
    {
        $rs->free();
    }

    return $categories;
}

function GetPluginSubCategory($db,$idcat,$forced_sql="")
{
    $subcategories = array();

    if ($forced_sql != '')
    {
        $temp_sql = "select distinct subcategory.* from plugin, product_type, plugin_sid LEFT JOIN subcategory on subcategory.id=plugin_sid.subcategory_id and subcategory.cat_id=$idcat where subcategory.id is not null AND plugin.id=plugin_sid.plugin_id AND product_type.id=plugin.product_type $forced_sql order by name";
    }
    else
    {
        $temp_sql = "select * from alienvault.subcategory where cat_id=$idcat order by name";
    }

    $tmp_result = $db->Execute($temp_sql);

    while (!$tmp_result->EOF)
    {
        $myrow = $tmp_result->fields;
        $subcategories[$myrow["id"]] = str_replace("_"," ",$myrow["name"]);
        $tmp_result->MoveNext();
    }

    if ($tmp_result)
    {
        $tmp_result->free();
    }

    return $subcategories;
}

function GetPluginCategoryName($idcat, $db)
{
    $name = $idcat;

    $temp_sql   = "SELECT name FROM alienvault.category WHERE id=?";
    $tmp_result = $db->Execute($temp_sql, array($idcat));

    if ($myrow = $tmp_result->fields)
    {
        $name = str_replace("_", " ", $myrow['name']);
    }

    if ($tmp_result)
    {
        $tmp_result->free();
    }

    return $name;
}


function GetPluginSubCategoryName($idcat, $db)
{
    $name     = $idcat[1];
    $temp_sql = "SELECT name FROM alienvault.subcategory WHERE cat_id=? and id=?";

    $tmp_result = $db->Execute($temp_sql, $idcat);

    if ($myrow = $tmp_result->fields)
    {
        $name = str_replace("_", " ", $myrow['name']);
    }

    if ($tmp_result)
    {
        $tmp_result->free();
    }

    return $name;
}


function calculate_combinatory($type, $sql, $dbconn)
{
    $num = 0;

    switch ($type)
    {
        case "1":
            $num = 1;
            break;

        case "2":
            $categories = GetPluginCategories($dbconn, $sql);
            $num += count($categories);
            /*
            foreach ($categories as $k => $categorie)
            {
                $subcategories= GetPluginSubCategory($dbconn,$k, $sql);
                $num += count($subcategories);
                $num++;
            }
            */

            $num++;
            break;

        case "3":
            $sourcetypes = GetSourceTypes($dbconn);
            foreach ($sourcetypes as $sourcetype)
            {
                $sql = " AND product_type.name='".$sourcetype."'";
                $categories = GetPluginCategories($dbconn, $sql );
                //$num+= count($categories);
                $num++;
                /*
                foreach ($categories as $k => $categorie)
                {
                    $subcategories= GetPluginSubCategory($dbconn,$k, $sql);
                    $num += count($subcategories);
                    $num++;
                }
                */
            }
            $num++;
            break;

        case "4":
            $num = count(GetSourceTypes($dbconn)) + 1;
            break;
    }

    return $num;

}

try
{
    $db     = new ossim_db();
    $dbconn = $db->connect();

    $result = $dbconn->Execute("SELECT id, name, type, inputs, `sql`, dr FROM custom_report_types ORDER BY type,id asc");

    if (!$result)
    {
        die();
    }

    while (!$result->EOF)
    {
        $subreports[] = $result->fields;
        $result->MoveNext();
    }

    $modules = array();

    foreach ($subreports as $sr)
    {
        $modules[$sr['type']][] = array(
            'id'         => $sr['id'],
            'name'       => $sr['name'],
            'parameters' => get_parameters($sr, $dbconn),
            'sql'        => $sr['sql'],
            'dr'         => $sr['dr']
        );
    }

    foreach ($modules as $name => $module)
    {
        foreach ($module as $item)
        {
            $parameters = implode(', ',$item['parameters']);

            $type   = menu_type($parameters);
            $res    = calculate_combinatory($type, $item['sql'], $dbconn);

            $query  = "UPDATE custom_report_types SET dr = ? WHERE id=?";
            $params = array(
                $res,
                $item['id']
            );

            $result = $dbconn->Execute($query, $params);
        }

    }

    $dbconn->disconnect();

}
catch(Exception $e){}
