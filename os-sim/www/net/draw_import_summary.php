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

Session::logcheck('environment-menu', 'PolicyNetworks');


$import_type = POST('import_type');
$sm_data     = POST('summary_data');

if ($import_type == 'welcome_wizard_nets')
{
    ?>
    <div>
        <div><?php echo Util::htmlentities($sm_data['general']['statistics']['saved']).'/'.Util::htmlentities($sm_data['general']['statistics']['total']).' '._('nets have been imported'); ?></div>
        <div class='sm_back_button'><input id='new_importation' type='button' value='<?php echo _('New Importation') ?>'></div>
    </div>
    <?php
}
else
{
    /*
    echo '<pre>';
        print_r($sm_data);
    echo '</pre>';
    */
    
    ?>    
    <div id='summary_info'>
        <?php
        $config_nt = array(
			'content'  =>  Util::htmlentities($sm_data['general']['data']),
			'options'  =>  array (
				'type'           =>  'nf_'.Util::htmlentities($sm_data['general']['status']),
				'cancel_button'  =>  FALSE
			),
			'style'    =>  'width: 100%; margin: 20px auto; text-align: left;'
		); 
						
		$nt = new Notification('nt_sm', $config_nt);
		$nt->show();
		?>
    </div>
    
    <div id='c_sm_statistics'> 
        <table class='table_data' id="t_sm_statistics">
            <thead>                   
                <tr>
                    <th><?php echo _('Nets imported')?></th>
                    <th><?php echo _('Errors')?></th>
                    <th><?php echo _('Warnings')?></th>
                </tr>                    
            </thead>
            
            <tbody>
                <tr>
                    <td class='success bold'><?php echo Util::htmlentities($sm_data['general']['statistics']['saved']) ?></td>
                    <td class='error bold'><?php echo Util::htmlentities($sm_data['general']['statistics']['errors']) ?></td>
                    <td class='warning bold'><?php echo Util::htmlentities($sm_data['general']['statistics']['warnings']) ?></td>
                </tr>
            </tbody>        
        </table>
    </div>    
    
    <?php
    if (is_array($sm_data['by_nets']) && !empty($sm_data['by_nets']))
    {
        ?>
        <div id='c_sm_container'>         
            <table class='table_data' id="t_sm_container">
                <thead>                   
                    <tr>
                        <th class='th_line'><?php echo _('Line')?></th>
                        <th class='th_status'><?php echo _('Status')?></th>
                        <th class='th_details'><?php echo _('Details')?></th>
                    </tr>                    
                </thead>
                
                <tbody>
                    <?php
                    foreach($sm_data['by_nets'] as $line => $net_data)
                    {
                        ?>                 
                        <tr>
                            <td class='td_line'>
                                <?php echo Util::htmlentities($line) ?>
                            </td>  
                                                                    
                            <td class='td_status'>
                                <span class="<?php echo Util::htmlentities($net_data['status']) ?>"><?php echo Util::htmlentities(ucfirst($net_data['status'])) ?></span>                        
                            </td>  
                            
                            <td class='td_details'>
                                <?php                            
                                if ($net_data['status'] != 'success')
                                {
                                    ?>
                                    <img src="/ossim/pixmaps/show_details.png"/>
                                                                    
                                    <div class="details_info">                                    
                                        <ul>                                        
                                        <?php
                                        
                                        if ($net_data['status'] == 'error')
                                        {
                                            $detail_data = $net_data['errors'];
                                        }
                                        else
                                        {
                                            $detail_data = $net_data['warnings'];
                                        }                  
                                        
                                        foreach($detail_data as $detail_id => $detail_data)
                                        {
                                            ?>
                                            <li><?php echo Util::htmlentities($detail_id.': '.$detail_data) ?></li>
                                            <?php
                                        }
                                        ?>
                                        </ul>                       
                                    </div>                                
                                    
                                    <?php
                                }
                                else
                                {
                                    echo '-';   
                                }                           
                                ?>
                            </td>                
                        </tr>                                                             
                        <?php             
                    }                   
                    ?>                    
                </tbody>
            </table>
        </div>
        <?php
    }
    ?>
    
    <div id='c_new_importation'>
        <input type='button' id='new_importation' name='new_importation' value="<?php echo _('New Importation')?>"/>
    </div>
        
    <?php
}

 