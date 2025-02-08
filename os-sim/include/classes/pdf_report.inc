<?php
/**
* pdf_report.inc
*
* File pdf_report.inc is used to:
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
define("PDF_PATH","/usr/share/ossim/www/tmp/");
require_once  __DIR__ . "/simple_html_dom.php";
require_once  __DIR__ . "/PHPExcel.php";

class Pdf_report
{
    private $path;


    private $pathHtml;


    private $pathHtmlAux;


    private $pathDirResourcesHtml;


    private $namePdf;


    private $originalName;


    private $orientation;


    private $paperformat;


    private $name;


    private $errors;


    private $extra_data;


    private $font;


    public function __construct($name, $orientation = "P", $paperformat = "A4", $user = NULL, $data = array())
    {
        $db          = new ossim_db();
        $dbconn      = $db->connect();
        $this->path   = PDF_PATH;

        $this->errors = array();

        $this->font   = 'Helvetica';

        if (!empty($name))
        {
            $this->setName($name);
            $this->setOriginalName($name);

            $this->setNamePdf($this->getName());
            $this->setNameXls($this->getName());

            $user = (empty($user)) ? Session::get_session_user() : $user;

            $this->path .= Session::get_secure_id($user)."/".$this->getName()."/";

            if (!is_dir($this->path))
            {
                mkdir($this->path, 0755, TRUE);
            }

            if (!empty($data) && is_array($data))
            {
                if (preg_match("/ENTITY\:(\d+)/", $data['assets'], $fnd))
                {
                    $entity_name         = Acl::get_entity_name($dbconn, $fnd[1]);
                    $data['assets_text'] = "ENTITY: ".$entity_name;
                }
                else
                {
                    $data['assets_text'] = $data['assets'];
                }
                $data_s           = serialize($data);
                $this->extra_data = $data;
                $res              = file_put_contents($this->path."/data_".$this->getName().".ini", $data_s);

                if ($res === FALSE)
                {
                    $this->errors['file_error'] = _("Error! PDF data could not be created");
                }
                else
                {
                    $this->setNamePdf($this->getName().'_'.$this->sanitize($this->extra_data['assets_text']));
                }
            }
            else
            {
                if ($data == TRUE)
                {
                    $path = $this->path."/data_".$this->getName().".ini";

                    if (@file_exists($path))
                    {
                        $data_s = @file_get_contents($path);
                        $data   = @unserialize($data_s);
                        $this->extra_data = (is_array($data)) ? $data : NULL;
                    }
                }
                if (empty($data))
                {
                    $this->extra_data = NULL;
                }
                else
                {
                    $this->setNamePdf($this->getName().'_'.$this->sanitize($this->extra_data['assets_text']));
                }
            }


            $this->set_html_path($this->path.'/'.$this->getName().'.html');
            $this->set_resource_path($this->path);

            $this->orientation = $orientation;
            $this->paperformat = $paperformat;
        }
        else
        {
            $this->errors["empty_name"] = _('Error! Name is empty');
        }
    }


    public function getName()
    {
        return $this->name;
    }


    public function setName($name)
    {
        $this->name = $this->sanitize($name);
    }


    public function getOriginalName()
    {
        return $this->originalName;
    }


    public function setOriginalName($name)
    {
        $this->originalName = $name;
    }


    public function setNamePdf($name)
    {
        $this->namePdf = $this->get_current_date().'-'.$name.'.pdf';
    }

    public function setNameXls($name)
    {
        $this->nameXls = $this->get_current_date().'-'.$name.'.xlsx';
    }


    public function getNamePdf()
    {
        return $this->namePdf;
    }

    public function getNameXls()
    {
        return $this->nameXls;
    }



    public function getpath()
    {
        return $this->path;
    }


    public function getpathHtml()
    {
        return $this->pathHtml;
    }


    public function getExtraData($key)
    {
        return ($key == '') ? $this->extra_data : $this->extra_data[$key];
    }


    public function setExtraData($data)
    {
        return $this->extra_data = $data;
    }


    public function setHtml($html)
    {
        file_put_contents($this->pathHtml, $html);
    }




    public function getHTML() {
        if($this->html_exists()) {
            header('Content-type: text/html');
            echo $this->formathtml();
            exit();
        } else {
            Av_exception::throw_error(Av_exception::USER_ERROR, _('No data available'));
        }
    }

    private function getBackgroundUrl($html) {
        preg_match('|backimg="([^"]*)"|',$html,$match);
        return $match[1];
    }

    public function formathtml() {
            $html = file_get_contents($this->pathHtml);
            $url = $this->getBackgroundUrl($html);
            $html = "<style>
                body{
                    margin: 0 auto;
                    width:900px;
                }
                .w100:nth-child(3) {
                    text-align: center;
                    background-image: url({$url});
                    background-repeat: no-repeat;
                    background-position: center top;
                }
            </style>".$html;
            return str_replace(
                array(
                    "Page [[page_cu]] / [[page_nb]]",
                    "[[date_y]]-[[date_m]]-[[date_d]] [[date_h]]:[[date_i]]:[[date_s]]",
                ),
                array(
                    "",
                    date("Y-m-d H:i:s"),
                ),$html);
    }


    private function buildXLSRecursive($table,&$row,&$maxcols,$myWorkSheet,$index,$style,&$tomerge) {
	if ($table->tag == 'table') {
		$children = $table->children();
		$trs = array();
		foreach ($children as $child) {
			if ($child->tag != 'tr') {
				$trs = array_merge($child->children(),$trs);
			} else {
				$trs[] = $child;
			}
		}
		$startindex = $index;
		foreach($trs as $tr) {
			$tds = $tr->children();
			$startrow = $row;
			$maxstartrow = $row + 1;
			$index = $startindex;
			$tds = $tr->children();
			if (count($tds) === 1) {
				$tomerge[] = $row;
			}
			foreach ($tds as $td) {
				$startrow = $row;
				$tbl = $td->children();
				$col = PHPExcel_Cell::stringFromColumnIndex($index);
				$cell = $col.$row;
				if (count($tbl) === 1) {
					$tbl = $tbl[0];
					if ($tbl->tag == "table") {
						$temp = array();
						$index = $this->buildXLSRecursive($tbl,$startrow,$maxcols,$myWorkSheet,$index,$style,$temp);
						continue;
					}
					if ($tbl->tag == "img") {
						$path = $tbl->src;
						if (strpos($path,"data:image") === 0) {
								$path = "/usr/share/ossim/www/tmp/".microtime(true).".".(strpos($path,"data:image/png") === 0 ? "png" : "jpg");
								$data = explode(",",$tbl->src,2);
								file_put_contents($path,base64_decode($data[1]));
						} else {
								$path = str_replace("include/classes..","www",__DIR__.$path);
						}
						
						$size = getimagesize($path);
						$im = imagecreatefrompng($path);
						
						if ($thestyle = $tbl->style) {
							if (preg_match_all("/(width|height)\s*:\s*([0-9]+)([a-z]{2})/",$thestyle,$thestyle)) {
								$width = $size[0];
								$height = $size[1];
								$keys = array_keys($thestyle[0]);
								foreach ($keys as $k) {
									$multiplier = $thestyle[3][$k] == "mm" ? 3.75 : 1;
									$sizeX = ceil($thestyle[2][$k] * $multiplier);
									if ($thestyle[1][$k] === "width") {
										$size[0] = $sizeX;
									} else {
										$size[1] = $sizeX;
									}
								}
								$dst = imagecreatetruecolor($size[0], $size[1]);
								$white = imagecolorallocate($dst, 255, 255, 255);
								imagefill($dst, 0, 0, $white);
								imagecopyresampled($dst, $im, 0, 0, 0, 0, $size[0], $size[1], $width, $height);
								$im = $dst;
							}
						}
						$height = ceil($size[1] / 16);
						if ($height > 1) {
							$startrow += $height;
						}
						$width = ceil($size[0] / 16);
						if ($width > 1) {
							$index += $width;
						}
						
						$objDrawing = new PHPExcel_Worksheet_MemoryDrawing();
						$objDrawing->setImageResource($im);
						$objDrawing->setRenderingFunction(PHPExcel_Worksheet_MemoryDrawing::RENDERING_JPEG);
						$objDrawing->setMimeType(PHPExcel_Worksheet_MemoryDrawing::MIMETYPE_DEFAULT);
						$objDrawing->setCoordinates($cell);
						$objDrawing->setWorksheet($myWorkSheet);
						$objDrawing->setResizeProportional(false);
						$objDrawing->setOffsetY(4);
						$myWorkSheet->getStyle($cell)->getAlignment()->setIndent(2)->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);

					}
				}
				if ($td->bgcolor) {
					$myWorkSheet->getStyle($cell)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB(ltrim($td->bgcolor,"#"));
				}
				if ($td->tag == "th") {
					$styleArray = array(
						'font'  => array(
						'bold'  => true,
						'color' => array('rgb' => 'FFFFFF'),
					));
					$stylex = $myWorkSheet->getStyle($cell);
					$stylex->applyFromArray($styleArray);
					$stylex->applyFromArray($style);
					$stylex->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB("7A7A7A");
				}
				$text = html_entity_decode($td->plaintext);
			   	$myWorkSheet->setCellValue($cell,$text);
				++$index;
				$maxcols = max($index,$maxcols);
			}
			$row = max($maxstartrow,$startrow+1);
		}
	}
	return $index;
	}


	private function sheetFinalize($myWorkSheet,$maxcols,$style,$tomerge) {
        $maxcol = PHPExcel_Cell::stringFromColumnIndex($maxcols-1);
        foreach ($tomerge as $i) {
		$item = "A$i:{$maxcol}$i";
                $myWorkSheet->mergeCells($item);
                $myWorkSheet->getStyle($item)->applyFromArray($style);
        }
        //autosize columns
        for ($i=0;$i<$maxcols;$i++) {
        	$col = PHPExcel_Cell::stringFromColumnIndex($i);
        	$myWorkSheet->getColumnDimension($col)->setAutoSize(TRUE);
        }
    }
    
    private function newSheet($objPHPExcel, $title,&$wsindex,&$maxcols,&$row,&$index,&$tomerge) {
        $title = substr(str_replace(array("[","]","/"),"",$title),0,31);
        //Some invalids characters must be removed for the title
        $invalidCharacters = array('*', ':', '/', '\\', '?', '[', ']');
        $title = str_replace($invalidCharacters, '', $title);
        $myWorkSheet = new PHPExcel_Worksheet($objPHPExcel, $title);
        $objPHPExcel->addSheet($myWorkSheet, $wsindex);
        ++$wsindex;
        $row = 1;
        $maxcols = 1;
        $index = 0;
        $tomerge = array();
        return $myWorkSheet;
    }


    public function getXLS() {
	if(!$this->html_exists()) {
		return;
	}
        $name = $this->getNameXls();
        $objWriter = $this->formatXls();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$name.'"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        $objWriter->save('php://output');
        exit();
    }

    public function formatXls() {
	$wsindex = 0;
	$row = 0;
	$maxcols = 1;
	$index = 0;
	$tomerge = array();
	$title = _("Title");
	$template = "[[date_y]]-[[date_m]]-[[date_d]] [[date_h]]:[[date_i]]:[[date_s]]";
	$html = file_get_html($this->pathHtml);

	$objPHPExcel = new PHPExcel();
	$objPHPExcel->removeSheetByIndex($objPHPExcel->getActiveSheetIndex());
	$myWorkSheet = $this->newSheet($objPHPExcel, $title,$wsindex,$maxcols,$row,$index,$tomerge);
	$style = array(
	    'alignment' => array(
	       'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
	    )
	);
	$pages = $html->find('page');
	$page = current($pages);
	$tables = $page->children();
	//get title
	$table = current($tables);
	$text = trim($table->plaintext);
	$objPHPExcel->getProperties()->setTitle($text);
	$myWorkSheet->setCellValue('A1',$text);
	//get date/user
	$table = next($tables);
	$text = $table->find('td',0)->plaintext;
	$text = str_replace($template,date("Y-m-d H:i:s"),$text);
	$myWorkSheet->setCellValue('A2',$text);
	//skip report title duplicate
	next($tables);
	//get table step by step
	$row=3;
	$tomerge = array(1,2);
	while ($table = next($tables)) {
        	$this->buildXLSRecursive($table,$row,$maxcols,$myWorkSheet,$index,$style,$tomerge);
	}
	while ($page = next($pages)) {
	        $tables = $page->children();
        	foreach ($tables as $table) {
	                if ($table->tag == "bookmark") {
				$text = $table->title;
				$bookmark = explode("-",$text,2);
				if ($title != $bookmark[0]) {
                                        $this->sheetFinalize($myWorkSheet,$maxcols,$style,$tomerge);
                                        $myWorkSheet = $this->newSheet($objPHPExcel, $bookmark[0],$wsindex,$maxcols,$row,$index,$tomerge);
				}
				$tomerge[] = $row;
				$cell = "A$row";
       	                        $styleArray = array(
               	                	'font'  => array(
                       	                'bold'  => true
                               	));
                                $stylex = $myWorkSheet->getStyle($cell);
       	                        $stylex->applyFromArray($styleArray);
               	                $stylex->applyFromArray($style);
                       	        $stylex->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB("8CC221");
                               	$text = html_entity_decode($text);
                                $myWorkSheet->setCellValue($cell,$text);
				++$row;
				$title = $bookmark[0];
	                }
        	        if ($table->tag != "table") {
                	        continue;
	                }
        	        $this->buildXLSRecursive($table,$row,$maxcols,$myWorkSheet,$index,$style,$tomerge);
	        }
	}
	$this->sheetFinalize($myWorkSheet,$maxcols,$style,$tomerge);
	return PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    }

    public function getPdf($output='navigator')
    {
        if($this->html_exists())
        {
            session_write_close();
            $this->convert_entities();
            require_once "classes/html2pdf/html2pdf.class.php";

            $html2pdf = new HTML2PDF($this->orientation, $this->paperformat, 'en', TRUE, 'UTF-8', array(0,0,0,0));
            $html2pdf->pdf->SetDisplayMode('real');
            $html2pdf->setTestTdInOnePage(FALSE);
            $html2pdf->setTestIsImage(FALSE);
            $pdf_content = file_get_contents($this->pathHtmlAux);
            $html2pdf->setDefaultFont($this->font);

            //$html2pdf->setModeDebug();
            $html2pdf->WriteHTML($pdf_content);

            if($output == 'navigator')
            {
                $html2pdf->Output($this->getNamePdf(),'D');
            }
            elseif($output == 'server')
            {
                $html2pdf->Output($this->pathDirResourcesHtml.$this->getNamePdf(), 'F');

                return $this->pathDirResourcesHtml.$this->getNamePdf();
            }
        }
        else
        {
            Av_exception::throw_error(Av_exception::USER_ERROR, _('No data available'));
        }
    }

   public function sendPdfEmail($title, $email, $pdfName = NULL, $dirUser = NULL, $body = '') {
       $path = '/usr/share/ossim/www/tmp/scheduler/'.$dirUser.'/'.$pdfName.'.pdf';
       $filename = $this->getNamePdf();
       return $this->pdfEmail($title, $email, $pdfName, $body, $path, $filename);
   }

   public function sendXlsEmail($title, $email, $pdfName = NULL, $dirUser = NULL, $body = '', $override = true) {
       $path = '/usr/share/ossim/www/tmp/scheduler/'.$dirUser.'/'.$pdfName.'.xlsx';
       $filename = $this->getNameXls();
       if ($override) {
           $objWriter = $this->formatXls();
           $objWriter->save($path);
       }
       return $this->pdfEmail($title, $email, true, $body, $path, $filename);
   }

   public function sendHTMLEmail($title, $email) {
       $arr = explode("/",$this->pathHtml);
       $filename = array_pop($arr);
       $new = str_replace(".html",".html__",$this->pathHtml);
       $html = $this->formathtml();
       $url = $this->getBackgroundUrl($html);
       $url = 'data:image/png;base64,' . base64_encode(file_get_contents($url));
       preg_match_all("|src=['\"]([\.][^\"']+)['\"]|",$html,$images);
       $images = array_unique($images[1]);
       $encoded = array();
       foreach ($images as $value) {
           preg_match("/\.[a-zA-Z]+$/",$value,$res);
           $encoded[] = 'data:image/'.$res[0].';base64,' . base64_encode(file_get_contents($value));
       }
       $html = str_replace($images,$encoded,$html);
       $html = "
           <style>
               .left.w50 {
                   display: none;
               }
           </style>
           <table style='margin: 0 auto; width:900px;text-align: center;background-image: url({$url});background-repeat: no-repeat;background-position: center top;'><tr><td>
               {$html}
           </td></tr></table>";
       file_put_contents($new,$html);
       return $this->pdfEmail($title, $email, true, '', $new, $filename);
       unlink($new);
   }

    public function pdfEmail($title, $email, $pdfName, $body, $path, $file_name)
    {
        if($title == '')
        {
            $subject = _('Report (')._('No title')._(') - OSSIM');
        }
        else
        {
            $subject = $title;
        }

        if($body == "")
        {
            $body ='<html>
                        <head>
                    <title>'.$subject.'</title>
                    </head>
                    <body>
                    </body>
                </html>';
        }

        $attachments = array();

        if($pdfName == NULL)
        {
            try
            {
                // Get PDF
                $attachments[] = array(
                    "path" => $this->getPdf('server'),
                    "name" => $file_name
               );
            }
            catch (Exception $e)
            {
                echo $e->getMessage(); //Boring error messages from anything else!
            }
        }
        else
        {
            //It exists
            $attachments[] = array(
                "path" => $path,
                "name" => $file_name
           );
        }


        $db   = new ossim_db();
        $conn = $db->connect();

        $res = Util::send_email($conn, $email, $subject, $body, $attachments);
        $db->close();

        return $res;
    }


    public function getLinkPdf($extra_data = FALSE, $overhead = false)
    {
        $current_url = $_SERVER[SCRIPT_NAME].'?pdf=true&extra_data=true&'.$_SERVER[QUERY_STRING];
	$format = "email";
        $download_pdf = "";
        if ($overhead) {
            $format = "emailhtml";
            $download_pdf = "&html=true";
            $overhead_fn = '
		var loading = $("#loading_overhead");
                if (!loading.length) {
			$(\'<div id="loading_overhead">
                            <div class="overhead_content">
               	                <div class="overhead_header">'._("The PDF generation process for this report may take some time, regarding the number of assets selected. Please, choose one of the following options:").'</div>
                                    <div>
                                        <ul>
                                            <li><input type="radio" value="1" id="overhead1" checked="checked" name="overhead" /><label for="overhead1">'._('Continue report with HTML output only').'</label></li>
                                            <li><input type="radio" value="2" id="overhead2" name="overhead" /><label for="overhead2">'._('Select smaller number of assets and try again').'</label></li>
                                            <li><input type="radio" value="3" id="overhead3" name="overhead" /><label for="overhead3">'._('Schedule report to run later today (Viewable in Report history)').'</label></li>
                                        </ul>
                                   </div>
                                <div class="overhead_footer"><input id="overhead-continue" type="button" value="Continue"/> <input type="button" name="" value="Quit" onclick="overhead_quit();"/></div>
                            </div>
	                 </div>\').insertAfter("#loading");
                } else {
			loading.show();
                }
                $("body").off("click","#overhead-continue").on("click","#overhead-continue",function() {callback(); overhead_quit();});
	    ';
            $overhead_fn = str_replace("\n","",$overhead_fn);
        } else {
            $overhead_fn = 'callback(); return true;';
	}
        $html='
            <script language="javascript">
		$(document).ready(function() {
			$("#iemail .vsbutton").click(function(e) {
				e.preventDefault();
				$.post("/ossim/session/token.php",{"f_name": "report_list_actions"},function(data) {
					var token = $.parseJSON(data).data;
					var iemail = $("#iemail");
					var filetype = iemail.find("input[name=filetype]:checked").val();
					var method = iemail.find("input[name=method]:checked").val();
					if (method == "download") {
						if (filetype == "pdf") {
							if (!prelaunch(function() {window.open("'.$current_url.$download_pdf.'&token="+token);}));
						} else if (filetype == "xls") {
        	                         	       var   myWindow = window.open("'.$current_url.'&xls=true&token="+token);
						}
                        	        } else if (method == "send") {
						emailValidate(filetype,token);
					}
					return false;
				});
			});
		});
		function prelaunch(callback) {
                    '.$overhead_fn.'
		}
		function overhead_next(callback) {
			var val = $("#loading_overhead [name=overhead]:checked").val();
			if (val == 1) {
				callback();
			} else if (val == 2) {
				overhead_quit();
				$("[name=wr_options]").find("a:nth-child(2)").click();
			} else if (val == 3) {
				top.location.href = "/ossim#reports/usm_reports/scheduler";
			}
		}

		function overhead_quit() {
			$("#loading_overhead").hide();
		}

                function emailValidate(type,token)
                {
                    var email = $("#email").val();
                    if(/([\w+\.-]*@[\w+\.-]*[\.\w{2,3}])+/.test(email))
                    {
			prelaunch(function() {
				GB_show("'._("Send").' "+(type == "xls" ? "XLSX" : "PDF")+" '._("by e-mail").'", "wizard_email.php?extra_data='.$extra_data.'&name='.base64_encode($this->getName()).'&format='.$format.'&"+type+"=true&email="+email+"&token="+token, "350", "600", false, false);
			});
                    }
                    else
                    {
                        alert("'._("Please, add a valid e-mail").'");
                        $("#email").focus();
                    }

                    return false;
                }

                function toggleDivEmail(){
                    $("#iemail").toggle();
                }
		function showEmailField() {
			$("[name=method]:checked").val() == "send" ? $(".efield").show() : $(".efield").hide()
		}

             </script>
               <a class="sbutton" href="#" onclick="toggleDivEmail(); return false;" title="'._("Export").'">
                   <img src="../pixmaps/disk-black.png" align="absmiddle"> '._("Export").'
               </a>
               <div id="iemail">
			<table class="noborder">
				<tr>
					<td>'._("File Type").':</td>
					<td><input type="radio" name="filetype" value="pdf" checked="checked"/><span class="radio-ico pdf-ico">'._("PDF").'</span></td>
				</tr>
                                <tr>
                                        <td></td>
                                        <td><input type="radio" name="filetype" value="xls"/><span class="radio-ico xls-ico">'._("XLSX").'</span></td>
                                </tr>
                                <tr>
                                        <td>'._("Method").':</td>
                                        <td><input type="radio" name="method" value="download" checked="checked" onchange="showEmailField();"/><span class="radio-ico download-ico">'._("Download").'</span></td>
                                </tr>
                                <tr>
                                        <td></td>
                                        <td><input type="radio" name="method" value="send" onchange="showEmailField();"/><span class="radio-ico email-ico">'._("Email").'</span></td>
                                </tr>
                                <tr>
                                        <td colspan="2"><span class="efield">'._("Email").'</span></td>
                                </tr>
				<tr>
					<td colspan="2"><input id="email" name="email" class="efield" type="text"></td>
				</tr>
				<tr>
					<td></td>
					<td><input type="button" class="vsbutton medium" value="'._("Export").'"/></td>
				</tr>
			</table>
		</div>
		';
        return $html;
    }



    /*************************************************
     ************** Private functions ****************
     *************************************************/

    private function set_html_path($url)
    {
        $this->pathHtml    = $url;
        $this->pathHtmlAux = $url."_";
    }


    private function set_resource_path($url)
    {
        $this->pathDirResourcesHtml = $url;
    }


    private function html_exists()
    {
        if (file_exists($this->pathHtml))
        {
            return TRUE;
        }

        return FALSE;
    }


    private function get_current_date()
    {
        $date = getdate();
        $tz = date_default_timezone_get();

        return $date['year'].$date['yday'].$date['hours'].$date['minutes'].$date['seconds'].$tz;
    }


    private function sanitize($data)
    {
        $str_to_replace = array(" ", ":", ".", "#", "&", "/");

        return str_replace($str_to_replace, "_", $data);
    }


    private function convert_entities()
    {
        $file = @file($this->pathHtml);

        for ($i = 0; $i < count($file); $i++)
        {
            $file[$i]   = mb_convert_encoding($file[$i],'HTML-ENTITIES','UTF-8');
            if (preg_match('/&#(\d{4,5});/',$file[$i]) && !preg_match('/\/image/',$file[$i]))
            {
                $file[$i]   = mb_convert_encoding($file[$i],'UTF-8','HTML-ENTITIES');
                $this->font = 'ArialUnicodeMS';
            }

            $file[$i] = str_replace("##br##","<br/>",$file[$i]);
        }

        file_put_contents($this->pathHtmlAux, $file);

        unset($file);
    }
}

/* End of file pdf_report.inc */
/* Location: ../include/classes/pdf_report.inc */
