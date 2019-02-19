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

$conf     = $GLOBALS['CONF'];  
$avmssp   = TRUE; //intval($conf->get_conf("alienvault_mssp", FALSE));

$m_perms  = array ('environment-menu', 'configuration-menu');
$sm_perms = array ('PolicyHosts', 'PolicyNetworks');


Session::logcheck($m_perms,$sm_perms);


$withusers          = intval(GET('users'));
$withavcomponents   = intval(GET('siem'));
$onlyinventory      = intval(GET('onlyinventory'));


$keytree = ($withavcomponents) ? "assets|alienvaultcomponents|entitiesassets" : "assets|entitiesassets";

if ($withusers) 
{
    $keytree .= "users";
}

/* connect to db */
$db   = new ossim_db();
$conn = $db->connect();

$entities = Acl::get_entities_to_assign($conn);

//New Correlation Context is displayed if you can create contexts
$can_create_ctx = FALSE;

if (isset($_SESSION['_user_vision']['can_create_ctx']))
{
    $can_create_ctx = $_SESSION['_user_vision']['can_create_ctx'];
}
else
{
    $_SESSION['_user_vision'] = Acl::get_user_vision($conn);
    
    $can_create_ctx = $_SESSION['_user_vision']['can_create_ctx'];
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <title> <?php echo _("OSSIM Framework")." - "._("Asset Structure"); ?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
        <meta http-equiv="Pragma" content="no-cache"/>
        <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
        <link type="text/css" rel="stylesheet" href="../style/jquery-ui-1.7.custom.css"/>
        <link rel="stylesheet" type="text/css" href="../style/tree.css" />
        <style type='text/css'>
            
            #t_container
            {
                border-collapse: collapse;
                border: none;
                width: 100%;                
            }
            
            #td_at_title
            {
                padding: 10px 0px;
            }
            
            #td_at_title > table > tbody > tr > td
            {
                padding: 0px;
                vertical-align: middle;
            }
            
            div.btnseparator
            {
                float: left;
                height: 22px;
                border-left: 1px solid #ccc;
                border-right: 1px solid #fff;
                margin: 1px;
            }
            
            .fbutton
            {
                float: left;
                display: block;
                cursor: pointer;
                padding: 1px;
            }

            .fbutton div
            {
                float: left;
                padding: 1px 3px;
            }       

            .fbutton span
            {
                float: left;
                display: block;
                padding: 3px;
            }

            .fbutton:hover
            {
                padding: 0px;
                border: 1px solid #aaa;  background-color:#dddddd;
            }

            .fbutton:hover div
            {
                padding: 0px 2px;
                border: 1px solid #ccc;  background-color:#dddddd;
            }   
            
            .gear
            {
                background: url(../pixmaps/gear.png) no-repeat center left;
            }
            
            .add
            {
                background: url(../pixmaps/tables/table_row_insert.png) no-repeat center left;
            }
            
            .modify
            {
                background: url(../pixmaps/tables/table_edit.png) no-repeat center left;
            }
            
        </style>
        
        <script type="text/javascript" src="../js/jquery.min.js"></script>
        <script type="text/javascript" src="../js/jquery-ui.min.js"></script>
        <script type="text/javascript" src="../js/jquery.cookie.js"></script>
        <script type="text/javascript" src="../js/jquery.tmpl.1.1.1.js"></script>
        <script type="text/javascript" src="../js/jquery.dynatree.js"></script>
        <script type="text/javascript" src="../js/greybox.js"></script>  
        <script type="text/javascript">
            
            var __cfg = <?php echo Asset::get_path_url() ?>;
            
            function addto_tree(item) 
            {
                var rn = $("#aptree").dynatree("getRoot").childList[0];
                
                if (rn.childList) 
                {
                   for (var i=0; i<rn.childList.length; i++) 
                   {
                      var node = rn.childList[i];
                      var rnode = rn.childList[i];
                                
                      if (node.data.key == item.ref) 
                      {
                         // add here
                         var found = false;
                         if (node.childList) 
                         {
                            // search if already exists
                            for (var j=0; j<node.childList.length; j++) 
                            {
                                if (node.childList[j].data.key == item.key) 
                                {
                                    rnode = node.childList[j];
                                    found = true;
                                }
                            }
                         }
                         
                         if (found) 
                         {
                            // found again?
                            found = false;
                            for (var j=0; j<rnode.childList.length; j++) 
                            {
                                if (rnode.childList[j].data.key == item.id) 
                                {
                                    found = true;
                                }
                            }
                            if (!found)
                                rnode.addChild({
                                    title: item.name,
                                    key: item.id,
                                    icon: "../../pixmaps/theme/host.png"
                                });
                         } 
                         else 
                         {
                            //alert("add "+item.key);
                            var childNode = rnode.addChild({
                                title: item.value,
                                tooltip: item.extra,
                                key: item.key,
                                isFolder: true
                            });
                            
                            childNode.addChild({
                                title: item.name,
                                key: item.id,
                                icon: "../../pixmaps/theme/host.png"
                            });
                            
                            rnode = childNode;
                         }
                         var tt = rnode.data.title.replace(/\s\<font.*/,'');
                         rnode.data.title = tt+' <font style="font-weight:normal">('+rnode.childList.length+')</font>';
                      }
                      // all ips
                      if (node.data.key == "all" && item.id) 
                      {
                          var found = false;
                          if (node.childList) 
                          {
                            // search if already exists
                            for (var j=0; j<node.childList.length; j++) 
                            {
                                if (node.childList[j].data.key == item.id) 
                                {
                                    found = true;
                                }
                            }
                          }
                          if (!found) 
                          {
                             if (item.name) 
                             {
                                hostname = item.name;
                                url = __cfg.asset.views + 'asset_form.php?id='+item.id;
                             }
                             else 
                             {
                                hostname = item.id;
                                url = __cfg.asset.views + 'asset_form.php?id='+item.id;
                             }
                             
                             rnode.addChild({
                                title: hostname,
                                key: item.id,
                                url: url,
                                icon: "../../pixmaps/theme/host.png"
                             });
                             
                             var tt = rnode.data.title.replace(/\s\<font.*/,'');
                             
                             rnode.data.title = tt+' <font style="font-weight:normal">('+rnode.childList.length+')</font>';
                          }
                      }
                   }
                }
            }
                        
            GB_TYPE = 'w';
            
            function GB_onclose() 
            {
                document.location.reload();
            }
            
            $(document).ready(function()
            {
                $("#atree").dynatree({
                    initAjax: { url: "../tree.php?key=<?php echo $keytree ?>&section=assets" },
                    clickFolderMode: 2,
                    onActivate: function(dtnode) 
                    {
                        if (typeof(dtnode.data.url) != 'undefined' && dtnode.data.url!= '') 
                        {
                            document.location.href = dtnode.data.url;
                        }
                    },
                    onDeactivate: function(dtnode){},
                    
                    onLazyRead: function(dtnode)
                    {
                        dtnode.appendAjax(
                        {
                            url: "../tree.php",
                            data: {key: dtnode.data.key, page: dtnode.data.page, section: 'assets' }
                        });
                        
                        if (typeof(parent.doIframe2)=="function") 
                        {
                            parent.doIframe2();
                        }
                    }
                });
                    
                $("#aptree").dynatree({
                    initAjax: { 
                        url: '../av_tree.php',
                        data: {
                           key: 'inventory_tree',
                           max_text_length: '75'
                        },
                    },
                    onActivate: function(dtnode) 
                    {
                        if(dtnode.data.url!='' && typeof(dtnode.data.url)!='undefined') 
                        {
                             document.location.href = dtnode.data.url;
                        }
                    },
                    onLazyRead: function(dtnode)
                    {
                        dtnode.appendAjax(
                        {
                            url: "../av_tree.php",
                            data: {
                               key: dtnode.data.key,
                               filters: dtnode.data.filters,
                               page: dtnode.data.page,
                               max_text_length: '75'
                            },                            
                        });
                        
                        if (typeof(parent.doIframe2) == "function")
                        {
                            parent.doIframe2();
                        }                   
                    }
                });

            });
            
        </script>
    </head>

    <body style="margin:0px;">
    
    <?php 
        //Local menu              
        include_once '../local_menu.php';
    ?>
              
    <table id='t_container'>
        <tr>
            <td id="td_at_title">
                <table class="transparent">
                    <tr>
                        <td class="sec_title" style="font-size:16px"><?php echo _('Entities & Asset Structure')?></td>
                        <td class="sec_title capitalize" style="font-size:13px;padding: 4px 0px 0px 10px;">
                            <?php echo _("Found")." <strong>".count($entities)."</strong> "._("entities in the system") ?>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td class='header_band'>
                <table class="transparent">
                    <tr>
                        <td class="noborder">
                            
                            <?php 
                            if (Session::am_i_admin() || Acl::am_i_proadmin()) 
                            {
                                ?>
                                <div class="fbutton" onclick="document.location.href='../acl/entities_edit.php?entity_type=logical'"><div><span class="add" style="padding-left:20px;font-size:12px"><b><?php echo _("New Entity") ?></b></span></div></div>
                                <div class="btnseparator"></div>
                                <?php
                            }
                            
                            if ($can_create_ctx == TRUE && $avmssp) 
                            {
                                ?>
                                <div class="fbutton" onclick="document.location.href='../acl/entities_edit.php?entity_type=context'"><div><span class="gear" style="padding-left:20px;font-size:12px"><b><?php echo _("New Correlation Context") ?></b></span></div></div>
                                <div class="btnseparator"></div>
                                <?php
                            }
                            ?>
                        </td>
                        <td class="noborder">
                            <?php
                                $wusers  = ($withusers == 1)  ? "0" : "1";
                                $link_1  = "document.location.href='entities.php?users=$wusers&siem=$withavcomponents'";
                                $checked = ($withusers == 1)  ? "checked='checked'" : "";
                            ?>
                            <input type="checkbox" onclick="<?php echo $link_1?>" <?php echo $checked;?>/>
                        </td>
                        <td class="noborder" style="text-align:right;font-size:12px">
                            <?php echo _("Show Users")?>
                        </td>
                        <td class="noborder"><div class="btnseparator"></div></td>
                        <td class="noborder">
                            <?php
                                $ws_av_comp  = ( $withavcomponents == 1 ) ? "0" : "1";
                                $link_2        =  "document.location.href='entities.php?users=$withusers&siem=$ws_av_comp'";
                                $checked       = ( $withavcomponents == 1 ) ? "checked='checked'" : "";
                            ?>
                            <input type="checkbox" onclick="<?php echo $link_2?>" <?php echo $checked;?>/>
                        </td>
                        <td class="noborder" style="text-align:right;font-size:12px">
                            <?php echo _("Show AlienVault Components")?>
                        </td>                        
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td class="noborder" style="padding-top:20px">
                <table class="transparent" align="center" width="90%">
                
                <?php 
                if (!$onlyinventory) 
                { 
                    ?>
                    <tr>
                        <td valign="top" class="noborder" width="49%">
                
                            <!-- All Assets -->
                            <table border="0" width="100%" class="noborder" align="center" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td class="headerpr noborder"><?php echo _("Asset Structure")?></td>
                                </tr>
                            </table>
                            
                            <table border="0" width="100%" align="center" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td class="noborder">
                                        <div id="atree" style="text-align:left;width:96%;padding:8px 5px 0px 5px;margin:0 auto;"></div>
                                    </td>
                                </tr>
                            </table>
                            
                        </td>
                        
                        <td width="2%" class="noborder"></td><td valign="top" class="noborder" width="49%">
                
                        <?php 
                } 
                else 
                { 
                    ?>

                    <tr>
                        <td valign="top" class="noborder" align="center">
                    <?php 
                } 
                ?>
                            <!-- Asset by Property -->
                            <table border="0" width="100%" class="noborder" align="center" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td class="headerpr noborder"><?php echo _("Inventory")?></td>
                                </tr>
                            </table>
                    
                            <table border="0" width="100%" align="center" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td class="noborder">
                                        <div id="aptree" style="text-align:left;width:96%;padding:8px 5px 0px 5px;margin:0 auto;"></div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    </body>
</html>
