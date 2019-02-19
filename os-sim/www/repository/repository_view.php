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

/*

    THIS FILE IS INCLUDED IN THE FORENSIC CONSOLE

*/
$kdb_docs = 0;

if(  $plugin_id != '' && $plugin_sid != '' )
{
    $db_kdb    = new ossim_db();
    $conn_kdb  = $db_kdb->connect();

    //Taxonomy
    $ptype   = Product_type::get_product_type_by_plugin($conn_kdb, $plugin_id);
    $cat     = Category::get_category_subcategory_by_plugin($conn_kdb, $plugin_id, $plugin_sid);
    $keyname = (empty($ptype['id'])? 0 : $ptype['id'])."##".(empty($cat['cid'])? 0 : $cat['cid'])."##".(empty($cat['scid'])? 0 : $cat['scid']);

    $repository_list['taxonomy']   = Repository::get_repository_linked($conn_kdb, $keyname, 'taxonomy');


    //Directive
    if($plugin_id == '1505')
    {
        $repository_list['directive'] = Repository::get_linked_by_directive($conn_kdb, $plugin_sid);
    }

    //Plugin SID
    $keyname = "$plugin_sid##$plugin_id";
    $repository_list['plugin_sid'] = Repository::get_repository_linked($conn_kdb, $keyname, 'plugin_sid');


    $kdb_docs = count($repository_list['directive']) + count($repository_list['plugin_sid']) + count($repository_list['taxonomy']);

    $db_kdb->close($conn_kdb);


    if ( $kdb_docs > 0 && empty($kdb_hide) )
    {

        $parser = new KDB_Parser();
        $parser->load_session_vars($vars);

        ?>
        <link rel="stylesheet" type="text/css" href="/ossim/style/jquery-ui.css"/>
        <script type="text/javascript" src="/ossim/js/jquery-ui.min.js"></script>

        <style type='text/css'>

            body { margin: 0px;}

            div.accordion .ui-widget-content, div.accordion .ui-state-default, div.accordion .ui-state-active
            {
                border: none;
            }

            div.accordion .ui-state-default {
                border: none;
                background: #E4E4E4;
            }

            div.accordion .ui-state-active {
                border: none;
                background: #E4E4E4;
            }

            #kdb_container {
                padding: 0px;
                margin:0 auto;
                position:relative;
            }

            .text_container {
                padding:5px;
                font-size: 11px;
            }

            .text_sumary {

                padding: 35px 5px 15px 5px;

            }

            .legend {
                text-align:left;
                width:100px;
            }

            .txt {
                font-weight: bold;
                text-align:left;
            }

        </style>

        <script>

            $(document).ready(function()
            {
                $(".accordion").accordion(
                {
                    collapsible: true,
                    autoHeight: false
                });
            });

        </script>


        <div id='kdb_container'>

            <div class="accordion">

            <?php
            foreach ($repository_list as $type => $repository_type)
            {
                switch ($type)
                {
                    case 'plugin_sid':
                        $type = _('Plugin SID');
                        break;

                    case 'directive':
                        $type = _('Directive');
                        break;

                    case 'taxonomy':
                        $type = _('Taxonomy');
                        break;

                    default:
                        $type = _('Unknown');
                }

                foreach ($repository_type as $doc)
                {
                ?>
                    <h3><a href='#'><strong><?php echo $doc->get_title() ?></strong> [<?php echo $type ?>]</a></h3>

                    <div>

                        <div class='text_container'>
                        <?php
                            $parser->proccess_file($doc->get_text(FALSE));

                            echo $parser->print_text();
                        ?>
                        </div>

                        <div class='text_sumary'>
                            <strong><?php echo _('Document Summary') ?></strong>
                            <br>
                            <table style='margin-top:3px;width:90%;'>
                                <tr>
                                    <td class='legend'>
                                        <?php echo _('Document') . ':' ?>
                                    </td>
                                    <td class='txt'>
                                        <?php echo $doc->get_title() ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class='legend'>
                                        <?php echo _('Visibility') . ':' ?>
                                    </td>
                                    <td class='txt'>
                                        <?php echo $doc->get_visibility() ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class='legend'>
                                        <?php echo _('Date') . ':' ?>
                                    </td>
                                    <td class='txt'>
                                        <?php echo $doc->get_date() ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class='legend'>
                                        <?php echo _('Attachements') . ':' ?>
                                    </td>
                                    <td class='txt'>
                                        <?php
                                            $num_attach = count($doc->get_attach());
                                            echo ($num_attach == 0) ? '-' : $num_attach;
                                        ?>
                                    </td>
                                </tr>
                            </table>
                        </div>


                    </div>
                <?php
                }
            }
            ?>
            </div>

        </div>

<?php

    }
    elseif (empty($kdb_hide))
    {
        echo '<p>'._('No Documents Found').'</p>';
    }

}
else
{
    echo '<p>'._('No Documents Found').'</p>';
}





