<?php
/**
* pdf_html.inc
*
* File pdf_html.inc is used to:
*   - Generate PDF file for USM Reports
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
* @package    ossim-framework\Reports
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/

class Pdf_html
{
    private $html;


    private $path = '../tmp/';


    private $backTop = '30mm';


    private $backBottom = '15mm';


    private $backLeft = '7mm';


    private $backRight = '7mm';


    private $backImg;


    private $backImgX = 'center';


    private $backImgY = 'top';


    private $backImgW = '100%';


    private $page;


    private $linkStylesheet;


    private $name;


    private $user;


    private $resourceId = 1;


    private $imagesArr = array();


    private $dirTemp;


    private $mainUrl;


    private $title;


    private $imgHeader='default.png';


    private $styleCss=array(
        'Title'=>array(
            'Background'=>'#8CC221',
            'Foreground'=>'#000',
       ),
        'Subtitle'=>array(
            'Background'=>'#7B7B7B',
            'Foreground'=>'#fff',
       )
   );

    private $footerContent;


    private $protocol;


    public function __construct($name, $title = '', $imagenHeader = '', $style = '', $styleCss = array(), $footerContent = array())
    {
        $this->setProtocol();
        $this->setMainUrl();
        $this->setFooterContent($footerContent);
        $this->setName($name);
        $this->setUser();
        $uuid = Session::get_secure_id();
        $this->path = "../tmp/$uuid/";
        $this->setDirTemp();
        $this->newDirTemp();
        $this->setImagenHeader($imagenHeader);
        $this->setBackImg();
        $this->setPage($style);
        $this->setLinkStylesheet();
        $this->setStyleCss($styleCss);
        $this->set($this->newStyle());
        $this->set($this->linkStylesheet);
        $this->set($this->page);
        $this->setTitle($title);
    }


    private function sanitize($data)
    {
        $str_to_replace = array(" ", ":", ".", "#", "&", "/");

        return str_replace($str_to_replace, "_", $data);
    }


    private function setMainUrl()
    {
         $this->mainUrl = $this->getProtocol().'//'.Util::get_default_admin_ip().'/ossim/';
    }


    private function getRootUrl()
    {
        return $this->getProtocol().'//'.Util::get_default_admin_ip().'/';
    }


    private function setLinkStylesheet()
    {
        $css = @file_get_contents("/usr/share/ossim/www/style_usm/stylePdf.css");
        $this->linkStylesheet='<style type="text/css">'."\n$css\n</style>\n";
    }


    private function getMainUrl()
    {
        return $this->mainUrl;
    }


    private function setProtocol()
    {
        if(empty($_SERVER["HTTPS"]))
        {
            $this->protocol = 'http:';
        }
        else
        {
            $this->protocol = 'https:';
        }
    }


    private function getProtocol()
    {
        return $this->protocol;
    }


    private function setFooterContent($footerContent)
    {
        $this->footerContent = $footerContent;
    }


    private function setStyleCss($styleCss=array())
    {
        if(count($styleCss) > 1)
        {
            $this->styleCss['Title']['Background']=$styleCss['Title']['Background'];
            $this->styleCss['Title']['Foreground']=$styleCss['Title']['Foreground'];
            $this->styleCss['Subtitle']['Background']=$styleCss['Subtitle']['Background'];
            $this->styleCss['Subtitle']['Foreground']=$styleCss['Subtitle']['Foreground'];
        }
    }


    public function setDirTemp()
    {
        $tmp_dir = $this->path.$this->sanitize($this->name);

        $this->dirTemp = $tmp_dir;
    }


    public function newDirTemp()
    {
        if (!is_dir($this->dirTemp))
        {
            mkdir($this->dirTemp, 0755, TRUE);
        }
        else
        {
            $this->cleanTemp();
        }
    }


    public function cleanTemp()
    {
        if(!strstr($this->dirTemp, '/tmp/'))
        {
            // Delete files
            foreach(scandir($this->dirTemp) as $value)
            {
                if($value != '.' && $value != '..')
                {
                    if (!is_dir($this->dirTemp.'/'.$value))
                    {
                        unlink($this->dirTemp.'/'.$value);
                    }
                }
            }

            if (file_exists($this->dirTemp.'.html'))
            {
                unlink($this->dirTemp.'.html');
            }
        }
    }


    public function set($html)
    {
        $this->html .= $html;
    }


    private function setName($name)
    {
        $this->name = $name;
    }


    public function setTitle($title)
    {
        $this->title = $title;

        $this->set($this->getHeaderFooter());
    }


    private function setUser()
    {
        $this->user=Session::get_session_user();
    }


    private function getUser()
    {
        return $this->user;
    }


    public function setHeader($name)
    {
        $this->header=$name;
    }


    private function setImagenHeader($name)
    {
        if($name != '')
        {
            $this->imgHeader = $name;
        }
    }


    private function setBackImg()
    {
        $this->backImg = $this->path.'../headers/'.$this->imgHeader;
    }


    public function setPage($style)
    {
        $html = '<page backtop="'.$this->backTop.'" backbottom="'.$this->backBottom.'" backleft="'.$this->backLeft.'" backright="'.
        $this->backRight.'" backimg="'.$this->backImg.'" backimgx="'.$this->backImgX.'" backimgy="'.$this->backImgY.'" backimgw="'.
        $this->backImgW.'"'.($style!="" ? ' style="'.$style.'"' : '').'>';

        $this->page = $html;
    }


    private function getPage()
    {
        return $this->page;
    }


    public function setBookmark($name, $level = 0)
    {
        if($name != '&nbsp;')
        {
            $this->set('<bookmark title="'.$name.'" level="'.$level.'" ></bookmark>');
        }
    }


    public function pageBreak()
    {
        $this->set('</page>'.$this->getPage());
        // Footer
        $this->set($this->getHeaderFooter());
    }


    public function getHeaderFooter()
    {
        require_once 'classes/Util.inc';

        if(count($this->footerContent) == 0)
        {
            $this->footerContent=array(
                'left'=>'User: '.$this->getUser().' / [[date_y]]-[[date_m]]-[[date_d]] [[date_h]]:[[date_i]]:[[date_s]]',
                'right'=>'Page [[page_cu]] / [[page_nb]]'
           );
        }

        $html='<page_header>
                    <table cellpadding="0" cellspacing="0" class="w100">
                        <tr>
                            <td class="right w100 title">'.$this->title.'</td>
                        </tr>
                    </table>
                    <br>
                </page_header>
                <page_footer>
                <table cellpadding="0" cellspacing="0" class="w100">
                    <tr>
                        <td class="left w50" style="padding-left:25px">'.$this->footerContent['left'].'</td>
                        <td class="right w50">'.$this->footerContent['right'].'</td>
                    </tr>
                </table><br>
                </page_footer>';

        return $html;
    }


    public function get()
    {
        $this->getImages(); // close session and get all images
        $this->set('</page>');
        $this->html = preg_replace("/(<\/page>)+/","\\1",$this->html); // safe
        return $this->html;
    }


    public function getResourceId()
    {
        return $this->resourceId++;
    }


    public function getImages()
    {
        $file_1x1 = file_get_contents("/usr/share/ossim/www/pixmaps/1x1.png");

        $opts = array(
            'http' => array(
                'header'=> 'Cookie: ' . session_name()."=".session_id()."\r\n"),
            'ssl'  => array(
                'verify_peer'      => FALSE,
                'verify_peer_name' => FALSE,
            )
        );

        $context = stream_context_create($opts);
        session_write_close(); // unlock the file

        foreach ($this->imagesArr as $path => $url)
        {
            $file_temp = @file_get_contents($url, FALSE, $context);

            if ($file_temp != '' && @imagecreatefromstring($file_temp) !== FALSE)
            {
                file_put_contents($path, $file_temp);
            }
            else
            {
                file_put_contents($path, $file_1x1);
            }
        }
    }


    public function newImage($url, $format, $path = '')
    {
        if($path == 'root')
        {
            $url = $this->getRootUrl().$url;
        }
        elseif($path == 'external')
        {
            $url = $url;
        }
        else
        {
            $url = $this->getMainUrl().$url;
        }

        $path = $this->dirTemp.'/image'.$this->getResourceId().'.'.$format;

        $this->imagesArr[$path] = $url;

        return $path;
    }


    public function newTitle($title, $date_from = '', $date_to = '', $notes = '', $host = '')
    {
        if ($host != '')
        {
            $html = '<table class="tableTitleF">
                        <tr>
                            <td style="width:15%">&nbsp;</td>
                            <td style="width:70%"><strong>'.$title.'</strong></td>
                            <td style="width:15%">'.$host.'</td>
                        </tr>
                     </table>';
        }
        elseif ($date_from != '' && $date_to != '')
        {
            $html = '<table class="tableTitleF">
                        <tr>
                            <td style="width:68%;"><strong>'.$title.'</strong></td>
                            <td style="width:16%;font-size:12px;">'._("from").': '.date("Y-m-d",strtotime($date_from)).'</td>
                            <td style="width:16%;font-size:12px;">'._("to").': '.date("Y-m-d",strtotime($date_to)).'</td>
                        </tr>
                    </table>';
        }
        else
        {
            $html='<table class="tableTitle"><tr><td><strong>'.$title.'</strong></td></tr></table>';
        }

        if ($notes != '')
        {
            $html .= '<table class="w100" cellpadding="0" cellspacing="0">
                        <tr>
                            <td style="padding:3px 0px 1px 0px;font-size:12px" valign="top">'.$notes.'</td>
                        </tr>
                      </table>';
        }

        return $html;
    }


    public function newStyle()
    {
        return '<style type="text/css">
                    <!--
                    .tableTitleF, .tableTitle{
                        background: '.$this->styleCss['Title']['Background'].';
                    }

                    .tableTitleF td, .tableTitle td{
                        color: '.$this->styleCss['Title']['Foreground'].';
                    }

                    th {
                        background: '.$this->styleCss['Subtitle']['Background'].';
                        color: '.$this->styleCss['Subtitle']['Foreground'].';
                    }
                    -->
                </style>';
    }
}

/* End of file pdf_html.inc */
/* Location: ../include/classes/pdf_html.inc */