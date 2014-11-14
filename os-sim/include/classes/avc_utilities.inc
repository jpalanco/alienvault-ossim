<?php
/**
* avc_utilities.inc
*
* File avc_utilities.inc is used to:
*   - To manage Alienvault Systems using Alienvault API (Utilities)
*
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
* @package    ossim-framework\Alienvaut_Center
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


class Avc_utilities
{
    /**
     * This method converts bytes to corresponding size unit
     *
     * @param float    $bytes      Bytes to convert
     * @param integer  $precision  [Optional] Number of decimals to show
     *
     * @return string
     */
    public static function bytesToSize($bytes, $precision = 2)
    {
        if (0 == $bytes)
        {
            return '0 B';
        }

        $sizes = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');

        $k = 1024;
        $i = floor(log($bytes) / log($k));

        return number_format(($bytes / pow($k, $i)), $precision).' '.$sizes[$i];
    }


    public static function create_progress_bar($id, $title, $width, $percentage, $class = '')
    {
        $title      = (empty($title))      ? '' : _($title);
        $percentage = (empty($percentage)) ? 0 : $percentage;

        return "
                <div id='$id' style='width: $width;' style='text-align: left;'>
                    <div class='ui-progress-bar'>
                        <div class='ui-title' style='text-align:center;'><span class='ui-label'>".$title." <strong class='value'>$percentage %</strong></span></div>
                        <div class='ui-progress $class' style='width:$percentage%'></div>
                    </div>
                </div>";
    }


    public static function create_vprogress_bar($id, $title, $width, $height, $percentage, $class = '')
    {
        $title        = (empty($title)) ? '' : _($title);
        $aux_height   = trim(str_replace('px' ,'', $height));
        
        $height       = ($aux_height < 10) ? "20px" : $aux_height."px";
        $vp_title_pos = (floor(($aux_height-5)/2))."px";
        $percentage   = (empty($percentage)) ? 0 : $percentage;

        return "
            <div id='$id'>
                <div style='float:left; width: $width; height: $height;'>
                    <div style='width: 100%; height: 100%;'>
                        <div class='ui-vprogress-bar'>
                            <div class='ui-vprogress-container' style='height:$percentage%'>
                                <div class='ui-vprogress $class'></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div style='float: left; width: 45px; padding-left: 5px; padding-top: $vp_title_pos'>
                    <span class='vp-ui-title'>$percentage %</span>
                </div>
            </div>";
    }


    public static function check_access($msg = '', $url = '')
    {
        Session::external_login();

        if (!Session::am_i_admin())
        {
            if (!empty($url) || $url !== FALSE)
            {
                $url = (!empty($url)) ? $url : '/ossim/session/login.php?action=logout';
                header("Location: $url");
                exit();
            }

            $error_text = (!empty($msg)) ? $msg : _("You don't have permission to access to Alienvault Center");

            $config_nt = array(
                'content' => "<div>".$error_text."</div>",
                'options' => array (
                    'type'          => 'nf_info',
                    'cancel_button' => FALSE
                ),
                'style'   => 'width: 100%; margin:auto; text-align: center;'
            );

            $nt = new Notification('nt_1', $config_nt);

            echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
            echo "<div style='width: 80%; margin:auto; padding: 100px 0px 100px 0px;'>";
                $nt->show();
            echo "</div>";

            exit();
        }
    }
}

/* End of file avc_utilities.inc */
/* Location: ../include/classes/avc_utilities.inc */