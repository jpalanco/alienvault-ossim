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


class Pdf_report{
    private $path='../tmp/';
    private $pathHtml;
	private $pathHtmlAux;
    private $pathDirResourcesHtml;
    private $namePdf;
    private $nameTemp;
    private $orientation;
    private $paperformat;
    private $name;
	private $errors;
	private $extra_data;
	private $font;

    public function __construct($name,$orientation="P",$paperformat="A4", $user = null, $data=array()) {
		
		$db          = new ossim_db();
		$dbconn      = $db->connect();
		
		$this->errors = array();
		$this->font   = 'Helvetica';
		
		if (!empty($name))
		{
			$this->setName($name);
			$this->setNameTemp($name);  
			$this->setNamePdf($this->getNameTemp());
			
			$user   = (empty($user)) ? Session::get_session_user() : $user;
        
			$this->path = "../tmp/".Session::get_secure_id($user)."/".$this->getName()."/";
			
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
				$res              = file_put_contents($this->path."/data_".$name.".ini", $data_s);
				
				if ($res === FALSE)
				{
					$this->errors["file_error"] = _("File Error: Could not create file ").$this->path;
				}
				else
				{
					$str_to_replace = array(" ", ":", ".", "#", "&");
					$this->setNamePdf(str_replace($str_to_replace, "_", $this->getNameTemp())."_".str_replace($str_to_replace, "_", $this->extra_data['assets_text']));
				}
			}
			else
			{
				if ($data == TRUE)
				{
					$path = $this->path."/data_".$name.".ini";
					if (@file_exists($path))
					{
						$data_s = @file_get_contents($path);
						$data   = @unserialize($data_s);
						$this->extra_data = (is_array($data)) ? $data : null;
					}
				}
				
				
				if ($data == FALSE || empty($data))
				{
					$this->extra_data = NULL;
				}
				else
				{
					$str_to_replace = array(" ", ":", ".", "#", "&");
					$this->setNamePdf(str_replace($str_to_replace, "_", $this->getNameTemp())."_".str_replace($str_to_replace, "_", $this->extra_data['assets_text']));
				}
			}
					
			
			$this->setpathHtml($this->path."/".$this->getName().".html");
			$this->setpathDirResourcesHtml($this->path);
			
			$this->orientation = $orientation;
			$this->paperformat = $paperformat;
		}
		else
		{
			$this->errors["empty_name"] = _("File Error: Name is empty");
		}
	}

    public function setName($name)
    {
        $this->name=$name;
    }

    public function setNameTemp($name)
    {
        $this->nameTemp=$name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getNameTemp()
    {
        return $this->nameTemp;
    }
	
	public function setNamePdf($name)
	{
        $this->namePdf=$this->getActualDate().'-'.$name.'.pdf';
    }
	
	public function getNamePdf()
	{
        return $this->namePdf;
    }
    
    
    private function setpathHtml($url)
    {
        $this->pathHtml=$url;
		$this->pathHtmlAux=$url."_";
    }

    private function setpathDirResourcesHtml($url)
    {
        $this->pathDirResourcesHtml=$url;
    }
    
    public function getpath()
    {
        return $this->path;
    }
    
    public function getpathHtml()
    {
        return $this->pathHtml;
    }

    private function existHtml(){
		
		if (file_exists($this->pathHtml))
		{
			return TRUE;
		}
		
		return FALSE;
	}

    private function getActualDate()
    {
        $date=getdate();

        return $date['year'].$date['yday'].$date['hours'].$date['minutes'].$date['seconds'];
    }
	
	public function getExtraData($key)
	{
		return ($key == '') ? $this->extra_data : $this->extra_data[$key];
	}
	
	public function setExtraData($data)
	{
		return $this->extra_data = $data;
	}

    private function convertEntities()
    {
    	$file = @file($this->pathHtml);
    	for ($i=0; $i<count($file); $i++) 
		{
    		if (preg_match('/&#(\d{4,5});/',$file[$i]) && !preg_match('/\/image/',$file[$i]))
			{
    			$file[$i]   = mb_convert_encoding($file[$i],'UTF-8','HTML-ENTITIES');
				$this->font = 'ArialUnicodeMS';
			}
    		else
    		{
    			$file[$i] = mb_convert_encoding($file[$i],'UTF-8','ISO-8859-1');
            }    
            
            $file[$i] = str_replace("##br##","<br/>",$file[$i]);
    	}
		
		file_put_contents($this->pathHtmlAux,$file);
		unset($file);
    }
    
    public function getPdf($output='navigator')
    {     
		if($this->existHtml())
		{
        	session_write_close();
			$this->convertEntities();
            require_once("classes/html2pdf/html2pdf.class.php");
            $html2pdf = new HTML2PDF($this->orientation,$this->paperformat,'en', true, 'UTF-8',array(0,0,0,0));
            $html2pdf->pdf->SetDisplayMode('real');
            $html2pdf->setTestTdInOnePage(FALSE);
            $html2pdf->setTestIsImage(FALSE);
            $pdf_content = file_get_contents($this->pathHtmlAux);
			$html2pdf->setDefaultFont($this->font);
            
			//$html2pdf->setModeDebug();
            $html2pdf->WriteHTML($pdf_content);
            
			if($output == 'navigator') 
			{
			    $html2pdf->Output($this->namePdf,'D');
            }
            elseif($output == 'server')
			{
                $html2pdf->Output($this->pathDirResourcesHtml.$this->namePdf, 'F');
                return $this->pathDirResourcesHtml.$this->namePdf;
            }
        }
		else
		{
            echo "<div class='ossim_error' style='width:80%; text-align:center;'>"._('No data available')."</div>";
		}
    }

    public function sendPdfEmail($title, $email, $pdfName=null, $dirUser=null, $body="") 
    {
       
        if($title == "") 
        {
            $subject = _('Report (')._("No title")._(') - OSSIM');
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
        $file_name   = $this->getNamePdf();       
		
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
                "path" => '/usr/share/ossim/www/tmp/scheduler/'.$dirUser.'/'.$pdfName.'.pdf',
                "name" => $file_name
           );            
		}
                          
        
        $db   = new ossim_db();
        $conn = $db->connect();
        
        $res = Util::send_email ($conn, $email, $subject, $body, $attachments); 
        $db->close();
        
        return $res;        
    }

    
    public function getLinkPdf($extra_data=FALSE){
        
		$current_url = $_SERVER[SCRIPT_NAME].'?pdf=true&extra_data=true&'.$_SERVER[QUERY_STRING];
		
		$html='
            <script language="javascript">
                             
                function emailValidate(id)
                {
                    if(/([\w+\.-]*@[\w+\.-]*[\.\w{2,3}])+/.test($("#email").val()))
                    {                                           
                        var id_form="#email_form";
                        
                        $(id_form).attr("action","wizard_email.php?extra_data='.$extra_data.'&name='.base64_encode($this->getName()).'&format=email");
                     	
                        GB_show_post("'.Util::js_entities(("Send PDF by e-mail")).'", id_form, "350", "600");
                    }
                    else
                    {
                        alert("'._("Please, add a valid e-mail").'");
                        $(id).focus();                        
                    }
                    
                    return false;
                }
                                                
                function toggleDivEmail(){
                    $("#iemail").toggle();
                }

             </script>
               <table width="100%" class="transparent" align="center" cellspacing="0" cellpadding="0">
                    <tr>
                        <td width="50%" class="nobborder" style="text-align:right;" nowrap>
                        <a class="sbutton" href="'.$current_url.'" target="_blank" title="'._("Generate PDF").'">
                            <img src="../pixmaps/pdf_button.png" align="absmiddle"> '._("Download PDF").'
                        </a>
                        </td>
                        <td  width="50%" class="nobborder" style="text-align:left;padding-left:10px;" nowrap>
                        <a class="sbutton" href="javascript:;" onclick="toggleDivEmail();" title="'._("Send PDF by e-mail").'"><img src="../pixmaps/emailButton.png" align="absmiddle"> '._("Send by e-mail").'</a>
                        </td>
                   </tr>
               </table>
               <div id="iemail" style="display:none;">
               <form method="POST" action="#" id="email_form">
                    <div style="margin-top:5px">
                        <input id="email" name="email" type="text" style="width:120px">
                        <input type="button" class="vsbutton small" onclick="emailValidate(\'#email\');" value="'._("Send").'"/>
					</div>
                </form>
                </div>
            ';

        return $html;
    }

    public function setHtml($html)
    {       	
		file_put_contents($this->pathHtml, $html);
    }
}

/* End of file pdf_report.inc */
/* Location: ../include/classes/pdf_report.inc */