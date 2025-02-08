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

//Checking if we have permissions to go through this section
Session::logcheck('environment-menu', 'MonitorsAvailability');
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title><?php echo _('Environment - Availability')?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <meta http-equiv="Pragma" content="no-cache"/>

    <link rel="stylesheet" type="text/css" href="/ossim/style/jquery-ui-1.7.custom.css"/>
    <link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>

    <!-- Jquery and Jquery UI -->
    <script src="/ossim/js/jquery.min.js"></script>
    <script src="/ossim/js/jquery-ui.min.js"></script>

    <script type="text/javascript" src="/ossim/js/notification.js"></script>
    <script type='text/javascript' src='/ossim/js/utils.js'></script>
    
    <script type="text/javascript">

        $(document).ready(function()
        {
            show_loading_box('bg_container', '<?php echo Util::js_entities(_('Loading Availability Monitoring, please wait...')) ?>', '');
        });

    </script>
    
<body> 
    <div id='bg_container' style='padding:5px;'></div>
</body>

</html>
