<?php

namespace Babesk\Schbas;

require_once PATH_INCLUDE . '/pdf/tcpdf/tcpdf.php';

class DsgvoPDF {

	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	public function __construct($pdfIdent, $gradelevel = Null) {

		$this->_gradelevel = $gradelevel;
		$this->_pdfIdent = $pdfIdent;
	}

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	/**
	 * Returns the TCPDF-Object used by this class
	 *
	 * @return TCPDF the TCPDF-Object
	 */
	public function getPdf() {

		return $this->_pdf;
	}

	/**
	 * Sets the TCPDF-Object used by this class
	 *
	 * @param TCPDF $pdf The TCPDF-Object
	 */
	public function setPdf($pdf) {

		$this->_pdf = $pdf;
		return $this;
	}


	/**
	 * Creates the PDF
	 *
	 * @return void
	 */
	public function create($content, $barcode = null) {

		$this->_barcode = $barcode;

		// create new PDF document
		$this->_pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT,
			PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$this->pdfMetadataSet();
		$this->_pdf->AddPage();
		$this->contentPrint($content);
	}

	/**
	 * Closes the PDF and outputs it to the User, who can download it
	 *
	 * @return void
	 */
	public function output() {
		$pdfName = sprintf('Personendaten_%s.pdf', $this->_pdfIdent);
		$this->_pdf->Output($pdfName, 'D');
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	/**
	 * Sets the Metadata
	 *
	 * @return void
	 */
	protected function pdfMetadataSet() {

		$logoPath = '../../../include/res/images/logo.jpg';
		$headerText = 'Auskunft nach Art. 15 DSGVO';
		if($this->_gradelevel) {
			$headerText .= "\nJahrgang: $this->_gradelevel";
		}

		$this->docInformationSet('LeG Uelzen');
		$this->headerDetailsSet($logoPath, 'LeG Uelzen', $headerText);
		$this->footerDetailsSet();
		$this->pageDetailsSet();
	}

	/**
	 * Sets the Information of the document
	 *
	 * @param  string $author The author of the document
	 * @param  string $keywords
	 * @param  string $creator
	 * @return void
	 */
	protected function docInformationSet($author = '',
		$keywords = '', $creator = PDF_CREATOR) {

		$this->_pdf->SetCreator($creator);
		$this->_pdf->SetAuthor($author);
		$this->_pdf->SetTitle('DSGVO');
		$this->_pdf->SetSubject('');
		$this->_pdf->SetKeywords($keywords);
	}

	/**
	 * Sets the details of the header
	 *
	 * @param  string $headerLogo the Path to the Logo to print onto the header
	 * @param  string $headerHeading A heading right to the logo
	 * @param  string $headerText Text right to the logo under the heading
	 * @return void
	 */
	protected function headerDetailsSet($headerLogo, $headerHeading = '',
		$headerText = '') {

		$this->_pdf->SetHeaderData($headerLogo, 15, $headerHeading,
			$headerText, array(0,0,0), array(0,0,0));
		$this->_pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '',
			PDF_FONT_SIZE_MAIN));
		$this->_pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
	}

	/**
	 * Sets the details of the footer
	 *
	 * @return void
	 */
	protected function footerDetailsSet() {

		$this->_pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '',
			PDF_FONT_SIZE_DATA));
		$this->_pdf->setFooterData(array(0,0,0), array(0,0,0));
		$this->_pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
	}

	/**
	 * Sets the details of the Page and its main-body
	 *
	 * @return void
	 */
	protected function pageDetailsSet() {

		$this->_pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP,
			PDF_MARGIN_RIGHT);
		$this->_pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		$this->_pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		$this->_pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
		$this->_pdf->setFontSubsetting(true);
		$this->_pdf->SetFont('helvetica', '', 10, '', true);
		$this->_pdf->setTextShadow($this->_textShadowStyle);
	}

	/**
	 * Writes the content into the main-Body of the PDF
	 *
	 * @return void
	 */
	protected function contentPrint($content) {


		$this->_pdf->writeHTMLCell(
			0, 0, '', '', $content, 0, 1, 0, true, '', true
		);

		if($this->_barcode) {
			$this->barcodePrint();
		}
		$this->_pdf->Ln();
	}

	/**
	 * Prints the Barcode to the top of the Header
	 *
	 * @return void
	 */
	protected function barcodePrint() {

		$this->_pdf->write1DBarcode(
			$this->_barcode, $this->_barcodeType, 150, 5,
			'', 15, 0.4, $this->_barcodeStyle, 'N'
		);
	}


	/**
	 * Checks if the Barcode should be printed onto the Pdf
	 *
	 * @return true if it should be printed, false if not
	 */
	protected function shouldBarcodePrint() {

		return $this->_msgReturn;
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

	/**
	 * define barcode style
	 *
	 * @var array
	 */
	protected $_barcodeStyle = array(
		'position' => '',
		'align' => 'C',
		'stretch' => false,
		'fitwidth' => true,
		'cellfitalign' => '',
		'border' => true,
		'hpadding' => 'auto',
		'vpadding' => 'auto',
		'fgcolor' => array(0,0,0),
		'bgcolor' => false, //array(255,255,255),
		'text' => true,
		'font' => 'helvetica',
		'fontsize' => 8,
		'stretchtext' => 4
	);
	protected $_barcodeType = 'C128';

	/**
	 * Defines the standard text-Shadow-Style
	 *
	 * @var array
	 */
	protected $_textShadowStyle = array(
		'enabled'=>true,
		'depth_w'=>0.2,
		'depth_h'=>0.2,
		'color'=>array(196,196,196),
		'opacity'=>1,
		'blend_mode'=>'Normal'
		);

	/**
	 * The TCPDF-Object used by this class
	 *
	 * @var TCPDF
	 */
	protected $_pdf;

	protected $_barcode;

}


?>