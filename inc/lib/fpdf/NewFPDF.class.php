<?php
set_include_path(get_include_path().";".$_SERVER["DOCUMENT_ROOT"]."\ModulosSAO\inc\lib");
include_once 'fpdf.php';

class NewFPDF extends FPDF {

	protected $widths;
	protected $aligns;
	protected $_currentAlign = "L";

	public function SetWidths( $w ) {
	    //Set the array of column widths
	    $this->widths=$w;
	}

	public function setAligns( $a ) {
	    //Set the array of column alignments
	    $this->aligns=$a;
	}

	public function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='') {

		parent::Cell($w, $h, utf8_decode($txt), $border, $ln, $align, $fill, $link);
	}

	public function Row( $data ) {
	    //Calculate the height of the row
	    $nb = 0;
	    for ( $i=0; $i < count($data); $i++ ) {
	        $nb=max($nb,$this->NbLines($this->widths[$i],$data[$i]));
	    }
	    
	    $h = $this->lasth * $nb;
	    //Issue a page break first if needed
	    $this->CheckPageBreak($h);
	    //Draw the cells of the row
	    for ( $i=0; $i < count($data); $i++ ) {

	        $w = $this->widths[$i];

	        // $a = isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
	        $this->_currentAlign = isset($this->aligns[$i]) ? $this->aligns[$i] : $this->_currentAlign;
	        $a = isset($this->aligns[$i]) ? $this->aligns[$i] : $this->_currentAlign;
	        
	        //Save the current position
	        $x = $this->GetX();
	        $y = $this->GetY();
	        //Draw the border
	        $this->Rect($x,$y,$w,$h);
	        //Print the text
	        $this->MultiCell($w,$this->lasth,$data[$i],0,$a);
	        //Put the position to the right of the cell
	        $this->SetXY($x+$w,$y);
	    }
	    //Go to the next line
	    $this->Ln($h);
	}

	protected function CheckPageBreak($h) {
	    //If the height h would cause an overflow, add a new page immediately
	    if ( $this->GetY() + $h > $this->PageBreakTrigger )
	        $this->AddPage( $this->CurOrientation );
	}

	protected function NbLines( $w, $txt ) {

	    //Computes the number of lines a MultiCell of width w will take
	    $cw = &$this->CurrentFont['cw'];
	    
	    if ( $w == 0 ) {
	        $w=$this->w-$this->rMargin-$this->x;
		}
	    
	    $wmax = ($w-2 * $this->cMargin) * 1000 / $this->FontSize;
	    $s = str_replace("\r",'',$txt);
	    $nb = strlen($s);
	    
	    if ( $nb > 0 and $s[$nb-1] == "\n") {
	        $nb--;
	    }
	    
	    $sep = -1;
	    $i = 0;
	    $j = 0;
	    $l = 0;
	    $nl = 1;

	    while ( $i < $nb ) {

	        $c = $s[$i];
	        
	        if ( $c == "\n" ) {
	            $i++;
	            $sep=-1;
	            $j=$i;
	            $l=0;
	            $nl++;
	            continue;
	        }

	        if( $c == ' ')
	            $sep = $i;
	        
	        $l += $cw[$c];
	        
	        if ( $l > $wmax ) {

	            if ( $sep == -1 ) {

	                if ( $i == $j )
	                    $i++;
	            }
	            else
	                $i = $sep + 1;

	            $sep = -1;
	            $j = $i;
	            $l = 0;
	            $nl++;
	        }
	        else
	            $i++;
	    }

	    return $nl;
	}

	// Round Rectangles Methods
	public function RoundedRect($x, $y, $w, $h, $r, $style = '')
    {
        $k = $this->k;
        $hp = $this->h;
        if($style=='F')
            $op='f';
        elseif($style=='FD' || $style=='DF')
            $op='B';
        else
            $op='S';
        $MyArc = 4/3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2F %.2F m',($x+$r)*$k,($hp-$y)*$k ));
        $xc = $x+$w-$r ;
        $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l', $xc*$k,($hp-$y)*$k ));

        $this->_Arc($xc + $r*$MyArc, $yc - $r, $xc + $r, $yc - $r*$MyArc, $xc + $r, $yc);
        $xc = $x+$w-$r ;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l',($x+$w)*$k,($hp-$yc)*$k));
        $this->_Arc($xc + $r, $yc + $r*$MyArc, $xc + $r*$MyArc, $yc + $r, $xc, $yc + $r);
        $xc = $x+$r ;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l',$xc*$k,($hp-($y+$h))*$k));
        $this->_Arc($xc - $r*$MyArc, $yc + $r, $xc - $r, $yc + $r*$MyArc, $xc - $r, $yc);
        $xc = $x+$r ;
        $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l',($x)*$k,($hp-$yc)*$k ));
        $this->_Arc($xc - $r, $yc - $r*$MyArc, $xc - $r*$MyArc, $yc - $r, $xc, $yc - $r);
        $this->_out($op);
    }

    protected function _Arc($x1, $y1, $x2, $y2, $x3, $y3) {
        $h = $this->h;
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c ', $x1*$this->k, ($h-$y1)*$this->k,
            $x2*$this->k, ($h-$y2)*$this->k, $x3*$this->k, ($h-$y3)*$this->k));
    }

    protected function setCellHeight( $height ) {
    	$this->lasth = $height;
    }

    public function setFontStyle( $style ) {

		$this->SetFont($this->FontFamily, $style, $this->FontSizePt);
	}

	public function resetFontStyle() {
		$this->SetFont($this->FontFamily, '', $this->FontSizePt);	
	}

	// public function setAlign( $align ) {
	// 	$this->_currentAlign = $align;
	// }

	public function setTextAlignRight() {
		$this->setAligns(array('R'));
	}

	public function setTextAlignLeft() {
		$this->setAligns(array('L'));	
	}

	public function setTextAlignCenter() {
		$this->setAligns(array('C'));
	}
}
?>