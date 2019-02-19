<?php
//FPDF EXTENSION FOR TABLE CREATION

//require('fpdf.php');

// The path has been changed to read our modified version of fpdf.php for PHP 5.4
require('/usr/share/ossim/www/vulnmeter/inc/fpdf.php');

class PDF_Table extends FPDF
{
var $tb_columns; 		//number of columns of the table
var $tb_header_type; 	//array which contains the header characteristics and texts
var $tb_data_type; 		//array which contains the data characteristics (only the characteristics)
var $tb_table_type; 	//array which contains the table charactersitics
var $table_startx, $table_starty;	//the X and Y position where the table starts

//returns the width of the page in user units
function PageWidth(){
	return (int) $this->w-$this->rMargin-$this->lMargin;
}

//initialize all the variables that we use
function Table_Init($col_no = 0){
	$this->tb_columns = $col_no;
	$this->tb_header_type = Array();
	$this->tb_data_type = Array();
	$this->tb_type = Array();
	$this->table_startx = 0;
	$this->table_starty = 0;
}

//Sets the number of columns of the table
function Set_Table_Columns($nr){
	$this->tb_columns = $nr;
}

/*
Characteristics constants for Header Type:
EVERY CELL FROM THE TABLE IS A MULTICELL

	WIDTH - header width
	TEXT - header text
	T_COLOR - text color = array(r,g,b);
	T_SIZE - text size
	T_FONT - text font - font type = "Arial", "Times"
	T_ALIGN - text align - "RLCJ"
	T_TYPE - text type (Bold Italic etc)
	LN_SPACE - space between lines
	BG_COLOR - background color = array(r,g,b);
	BRD_COLOR - border color = array(r,g,b);
	BRD_SIZE - border size
	BRD_TYPE - border type - up down, with border, without

	all these setting conform to the settings from the MultiCell function
*/

/*
Function: Set_Header_Type($type_arr) -- sets the array for the header type

type array =
	 array(
		0=>array(
				"WIDTH" => 10,
				"T_COLOR" => array(120,120,120),
				"T_SIZE" => 5,
				...
				"TEXT" => "Header text 1"
			),
		1=>array(
				...
			),
	 );
where 0,1... are the column number
*/

function Set_Header_Type($type_arr){
	$this->tb_header_type = $type_arr;
}


/*
Characteristics constants for Data Type:
EVERY CELL FROM THE TABLE IS A MULTICELL

	T_COLOR - text color = array(r,g,b);
	T_SIZE - text size
	T_FONT - text font - font type = "Arial", "Times"
	T_ALIGN - text align - "RLCJ"
	T_TYPE - text type (Bold Italic etc)
	LN_SPACE - space between lines
	BG_COLOR - background color = array(r,g,b);
	BRD_COLOR - border color = array(r,g,b);
	BRD_SIZE - border size
	BRD_TYPE - border type - up down, with border, without

	all these settings conform to the settings from the MultiCell function
*/

/*
Function: Set_data_Type($type_arr) -- sets the array for the header type

type array =
	 array(
		0=>array(
				"T_COLOR" => array(120,120,120),
				"T_SIZE" => 5,
				...
				"BRD_TYPE" => 1
			),
		1=>array(
				...
			),
	 );
where 0,1... are the column number
*/

function Set_Data_Type($type_arr){
	$this->tb_data_type = $type_arr;
}


/*
Function Set_Table_Type

$type_arr = array(
				"BRD_COLOR"=> array (120,120,120), //border color
				"BRD_SIZE"=>5), //border line width
				"TB_COLUMNS"=>5), //the number of columns
				"TB_ALIGN"=>"L"), //the align of the table, possible values = L, R, C equivalent to Left, Right, Center
				)
*/
function Set_Table_Type($type_arr){
	$this->tb_table_type = $type_arr;
	if (isset($type_arr['TB_COLUMNS']))
		$this->tb_columns = $type_arr['TB_COLUMNS'];
}

//this function draws the exterior table border
function Draw_Table_Border(){
/*				"BRD_COLOR"=> array (120,120,120), //border color
				"BRD_SIZE"=>5), //border line width
				"TB_COLUMNS"=>5), //the number of columns
				"TB_ALIGN"=>"L"), //the align of the table, possible values = L, R, C equivalent to Left, Right, Center
*/
	//set the colors
	list($r, $g, $b) = $this->tb_table_type['BRD_COLOR'];
	$this->SetDrawColor($r, $g, $b);

	//set the line width
	$this->SetLineWidth($this->tb_table_type['BRD_SIZE']);

	//draw the border
	$this->Rect(
		$this->table_startx,
		$this->table_starty,
		$this->Get_Table_Width(),
		$this->GetY()-$this->table_starty);

}

//returns the table width in user units
function Get_Table_Width()
{
	//calculate the table width
	$tb_width = 0;
	for ($i=0; $i < $this->tb_columns; $i++){
		$tb_width += $this->tb_header_type[$i]['WIDTH'];
	}
	return $tb_width;
}

//aligns the table to C, L or R (default is L)
function Table_Align(){
	//check if the table is aligned
	if (isset($this->tb_table_type['TB_ALIGN']))
		$tb_align = $this->tb_table_type['TB_ALIGN'];
	else
		$tb_align='';

	//set the table align
	switch($tb_align){
		case 'C':
			$this->SetX($this->lMargin + ($this->PageWidth() - $this->Get_Table_Width())/2);
			break;
		case 'R':
			$this->SetX($this->lMargin + ($this->PageWidth() - $this->Get_Table_Width()));
			break;
		default:
			$this->SetX($this->lMargin);
			break;
	}
}

//Draws the Header
function Draw_Header(){

	$this->Table_Align();

	$this->table_startx = $this->GetX();
	$this->table_starty = $this->GetY();

	$nb = 0;
	$ln = 0;
	$xx = Array();

	//calculate the maximum height of the cells
	for($i=0;$i<$this->tb_columns;$i++)
	{
		#print_r($this->tb_header_type[$i]);
		$this->SetFont(	$this->tb_header_type[$i]['T_FONT'],
						$this->tb_header_type[$i]['T_TYPE'],
						$this->tb_header_type[$i]['T_SIZE']);
		$xx[$i] = $this->NbLines($this->tb_header_type[$i]['WIDTH'],$this->tb_header_type[$i]['TEXT']);
		$ln = max($ln, $this->tb_header_type[$i]['LN_SIZE']);
		$nb = max($nb,$xx[$i]);
	}

	//this is the maximum cell height
	$h = $ln * $nb;

	//Draw the cells of the row
	for($i=0;$i<$this->tb_columns;$i++)
	{
		//border size BRD_SIZE
		$this->SetLineWidth($this->tb_header_type[$i]['BRD_SIZE']);

		//fill color = BG_COLOR
		list($r, $g, $b) = $this->tb_header_type[$i]['BG_COLOR'];
		$this->SetFillColor($r, $g, $b);

		//Draw Color = BRD_COLOR
		list($r, $g, $b) = $this->tb_header_type[$i]['BRD_COLOR'];
		$this->SetDrawColor($r, $g, $b);

		//Text Color = T_COLOR
		list($r, $g, $b) = $this->tb_header_type[$i]['T_COLOR'];
		$this->SetTextColor($r, $g, $b);

		//Set the font, font type and size
		$this->SetFont(	$this->tb_header_type[$i]['T_FONT'],
						$this->tb_header_type[$i]['T_TYPE'],
						$this->tb_header_type[$i]['T_SIZE']);

		$w=$this->tb_header_type[$i]['WIDTH'];

		//Save the current position
		$x=$this->GetX();
		$y=$this->GetY();

		//Print the text
		$this->MultiCell(
				$w, $h / $xx[$i],
				$this->tb_header_type[$i]['TEXT'],
				$this->tb_header_type[$i]['BRD_TYPE'],
				$this->tb_header_type[$i]['T_ALIGN'],
				1);
		//Put the position to the right of the cell
		$this->SetXY($x+$w,$y);
	}

	//Go to the next line
	$this->Ln($ln*$nb);
}

//this function Draws the data's from the table
//have to call this function after the table initialization, after the table, header and data
//types are set and after the header is drawn
function Draw_Data($data){

	$nb = 0;
	$ln = 0;
	$xx = Array();
	$tt = Array();

	$this->SetX($this->table_startx);

	//calculate the maximum height of the cells
	for($i=0;$i<$this->tb_columns;$i++)
	{
		if (!isset($data[$i]['T_FONT']))
			$data[$i]['T_FONT'] = $this->tb_data_type[$i]['T_FONT'];
		if (!isset($data[$i]['T_TYPE']))
			$data[$i]['T_TYPE'] = $this->tb_data_type[$i]['T_TYPE'];
		if (!isset($data[$i]['T_SIZE']))
			$data[$i]['T_SIZE'] = $this->tb_data_type[$i]['T_SIZE'];
		if (!isset($data[$i]['T_COLOR']))
			$data[$i]['T_COLOR'] = $this->tb_data_type[$i]['T_COLOR'];
		if (!isset($data[$i]['T_ALIGN']))
			$data[$i]['T_ALIGN'] = $this->tb_data_type[$i]['T_ALIGN'];
		if (!isset($data[$i]['LN_SIZE']))
			$data[$i]['LN_SIZE'] = $this->tb_data_type[$i]['LN_SIZE'];
		if (!isset($data[$i]['BRD_SIZE']))
			$data[$i]['BRD_SIZE'] = $this->tb_data_type[$i]['BRD_SIZE'];
		if (!isset($data[$i]['BRD_COLOR']))
			$data[$i]['BRD_COLOR'] = $this->tb_data_type[$i]['BRD_COLOR'];
		if (!isset($data[$i]['BRD_TYPE']))
			$data[$i]['BRD_TYPE'] = $this->tb_data_type[$i]['BRD_TYPE'];
		if (!isset($data[$i]['BG_COLOR']))
			$data[$i]['BG_COLOR'] = $this->tb_data_type[$i]['BG_COLOR'];

		$this->SetFont(	$data[$i]['T_FONT'],
						$data[$i]['T_TYPE'],
						$data[$i]['T_SIZE']);
		$xx[$i] = $this->NbLines($this->tb_header_type[$i]['WIDTH'],$data[$i]['TEXT']);
		$ln = max($ln, $data[$i]['LN_SIZE']);
		$nb = max($nb,$xx[$i]);
	}

	//this is the maximum cell height
	$h = $ln * $nb;

	$this->CheckPageBreak($h);

	//Draw the cells of the row
	for($i=0;$i<$this->tb_columns;$i++)
	{
		//border size = BRD_SIZE
		$this->SetLineWidth($data[$i]['BRD_SIZE']);

		//fill color = BG_COLOR
		list($r, $g, $b) = $data[$i]['BG_COLOR'];
		$this->SetFillColor($r, $g, $b);

		//Draw Color = BRD_COLOR
		list($r, $g, $b) = $data[$i]['BRD_COLOR'];
		$this->SetDrawColor($r, $g, $b);

		//Text Color = T_COLOR
		list($r, $g, $b) = $data[$i]['T_COLOR'];
		$this->SetTextColor($r, $g, $b);

		//Set the font, font type and size
		$this->SetFont(	$data[$i]['T_FONT'],
						$data[$i]['T_TYPE'],
						$data[$i]['T_SIZE']);

		$w=$this->tb_header_type[$i]['WIDTH'];

		//Save the current position
		$x=$this->GetX();
		$y=$this->GetY();

		//Print the text
		$this->MultiCell(
				$w, $h / $xx[$i],
				$data[$i]['TEXT'],
				$data[$i]['BRD_TYPE'],
				$data[$i]['T_ALIGN'],
				1);
		//Put the position to the right of the cell
		$this->SetXY($x+$w,$y);
	}

	//Go to the next line
	$this->Ln($ln*$nb);
}

//if the table is bigger than a page then it jumps to next page and draws the header
function CheckPageBreak($h)
{
	//If the height h would cause an overflow, add a new page immediately
	if($this->GetY()+$h>$this->PageBreakTrigger){
		$this->Draw_Table_Border();
		$this->AddPage($this->CurOrientation);
		$table_startx = $this->GetX();
		$table_starty = $this->GetY();
		$this->Draw_Header();
	}

	//align the table
	$this->Table_Align();
}

function NbLines($w,$txt)
{
	//Computes the number of lines a MultiCell of width w will take
	$cw=&$this->CurrentFont['cw'];
	if($w==0)
		$w=$this->w-$this->rMargin-$this->x;
	$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
	$s=str_replace("\r",'',$txt);
	$nb=strlen($s);
	if($nb>0 and $s[$nb-1]=="\n")
		$nb--;
	$sep=-1;
	$i=0;
	$j=0;
	$l=0;
	$nl=1;
	while($i<$nb)
	{
		$c=$s[$i];
		if($c=="\n")
		{
			$i++;
			$sep=-1;
			$j=$i;
			$l=0;
			$nl++;
			continue;
		}
		if($c==' ')
			$sep=$i;
		$l+=$cw[$c];
		if($l>$wmax)
		{
			if($sep==-1)
			{
				if($i==$j)
					$i++;
			}
			else
				$i=$sep+1;
			$sep=-1;
			$j=$i;
			$l=0;
			$nl++;
		}
		else
			$i++;
	}
	return $nl;
}

}//end of PDF_Table class

?>
