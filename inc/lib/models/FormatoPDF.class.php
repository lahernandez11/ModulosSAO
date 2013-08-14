<?php
require_once 'fpdf/NewFPDF.class.php';

abstract class FormatoPDF extends NewFPDF {
	
	const PDF_PAGE_ORIENTATION = 'P';
	const PDF_UNIT 			   = 'mm';
	const PDF_PAGE_FORMAT 	   = 'A4';
	const PDF_USE_UNICODE 	   = true;
	const PDF_UNICODE_CODEPAGE = 'UTF-8';
	const PDF_DISK_CACHE 	   = false;
	const PDF_PDFA_MODE 	   = false;
	const PDF_DEFAULT_FONT	   = 'helvetica';
	const PDF_DEFAULT_FONTSIZE = 8;

	const PDF_MARGIN_HEADER    = 0;
	const PDF_MARGIN_FOOTER	   = 0;

	const PDF_MARGIN_TOP	   = 10;
	const PDF_MARGIN_LEFT	   = 5;
	const PDF_MARGIN_RIGHT	   = 5;

	private $_fillColorGray = array(200, 200, 200);#95C601
	private $_fillColorGHI = array(149, 198, 1);

	public function __construct() {
		parent::__construct(
			// self::PDF_PAGE_ORIENTATION, self::PDF_UNIT, self::PDF_PAGE_FORMAT,
			// self::PDF_USE_UNICODE, self::PDF_UNICODE_CODEPAGE, self::PDF_PDFA_MODE
		);

		$this->SetMargins(
			self::PDF_MARGIN_LEFT, self::PDF_MARGIN_TOP, self::PDF_MARGIN_RIGHT
		);
	}

	protected function setFillColorGHI() {
		$this->SetFillColor($this->_fillColorGHI[0], $this->_fillColorGHI[1], $this->_fillColorGHI[2]);
	}

	protected function setFillColorDefault() {
		$this->SetFillColor($this->_fillColorGray[0], $this->_fillColorGray[1], $this->_fillColorGray[2]);
	}

	public function resetTextColor() {
		$this->SetTextColor(0, 0, 0);
	}

	public function resetFontSize() {
		$this->SetFontSize(self::PDF_DEFAULT_FONTSIZE);
	}
}