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

Session::useractive();

$vendor   = GET('vendor');
$model    = GET('model');
$version  = GET('version');
$internet = (GET('internet') != '') ? TRUE : FALSE;

ossim_valid($vendor,   OSS_NULLABLE, OSS_TEXT,                  'illegal:' . _("Vendor"));
ossim_valid($model,    OSS_NULLABLE, OSS_TEXT, '\+',            'illegal:' . _("Model"));
ossim_valid($version,  OSS_NULLABLE, OSS_TEXT,                  'illegal:' . _("Version"));

if (ossim_error())
{
    die(ossim_error());
}

// Local PDF
$db   = new ossim_db(TRUE);
$conn = $db->connect();

$link = FALSE;

list($link_pdf, $default) = Software::get_documentation_link($conn, $vendor, $model, TRUE);

if ($default && $internet)
{
    // With internet available and for a default PDF doc we'll redirect to an external URL
    // https:/www.alienvault.com/help/product/data-source-plugin/<vendor>/<model>/<version>
    
    $link = "https://www.alienvault.com/help/product/data-source-plugin";
    
    if ( !empty($vendor) )
    {
        // Vendor
        $link  .= '/' . urlencode(strtolower($vendor));
        if ( !empty($model) )
        {
            // Model
            $link  .= '/' . urlencode(strtolower($model));
            if ( !empty($version) )
            {
                // Version
                $link  .= '/' . urlencode(strtolower($version));
            }
            else
            {
                $link  .= '/-';            
            }
        }
        else
        {
            $link .= '/-/-';
        }

    }
}
elseif (preg_match("/\.pdf$/i", $link_pdf))
{
    $link =  AV_MAIN_PATH . '/doc/plugins/' . $link_pdf;
}

if ($link)
{
    header("Location: $link\n");
}

$db->close($conn);
