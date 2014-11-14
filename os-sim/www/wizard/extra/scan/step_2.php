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

if ($step != 2)
{
    $msg = _('An unexpected error happened. Try again later');
    
    include 'step_error.php';
    
    die();
}

$total = $wizard->get_step_data('scan_hosts');
?>
        
<div class='wizard_title'>
    <?php echo _('Scanning') ?>
</div>


<div class='wizard_subtitle'>
    <?php echo _('AlienVault is performing a scan to discover the assets on your network.') ?>
</div>


<div id="progressbar" class='av_progressbar'>

    <div class='stripes'></div>
    
    <span class='bar-label'></span>

    <div id='progress_legend'>
        <span id='progress_current'>0</span>/<span id='progress_total'><?php echo intval($total) ?></span> <?php echo _('Hosts') ?> 
        (<span id='progress_remaining'><?php echo _('Calculating Remaining Time') ?></span>)
    </div>
    
</div> 


<div class='box_single_button'>
    <button id='cancel_scan'><?php echo _('Stop Scan') ?></button>
</div>     


 
            