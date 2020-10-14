<?php

require_once PATH_INCLUDE . '/pdf/tcpdf/tcpdf.php';

class GeneralPdf {
	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	public function __construct($pdo) {

		$this->_pdo = $pdo;
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
	public function create($title, $content) {

		$this->_title = $title;
		$this->_content = $content;

		require_once PATH_INCLUDE . '/pdf/tcpdf/tcpdf.php';

		// create new PDF document
		$this->_pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT,
			PDF_PAGE_FORMAT, true, 'UTF-8', false);

		$this->pdfMetadataSet();
		$this->_pdf->AddPage();
		$this->contentPrint();

	}

	/**
	 * Closes the PDF and outputs it to the User, who can download it
	 *
	 * @return void
	 */
	public function output() {
		$pdfName = sprintf('%s_pdf.pdf', $this->_title);
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

		$this->HeaderdataSetDefault();
		$this->docInformationSet($this->_title);
		$this->footerDetailsSet();
		$this->pageDetailsSet();
	}

	/**
	 * Sets the Data of the Header to the default Data given in the Database
	 */
	protected function HeaderdataSetDefault() {

		$relPath = '../../../../images/';
		$logopath = $this->globalSettingValueGet('pdfDefaultLogopath');
		$logopath = ($logopath) ? $relPath . $logopath : false;

		$headerHeading = $this->globalSettingValueGet(
			'pdfDefaultHeaderHeading');
		$headerText = $this->globalSettingValueGet('pdfDefaultHeaderText');

		$this->headerDetailsSet($logopath, $headerHeading, $headerText);
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
		$this->_pdf->SetTitle($this->_title);
		$this->_pdf->SetSubject($this->_title);
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
		$this->_pdf->SetFont('helvetica', '', 14, '', true);
		$this->_pdf->setTextShadow($this->_textShadowStyle);
	}

	/**
	 * Writes the content into the main-Body of the PDF
	 *
	 * @return void
	 */
	protected function contentPrint() {

		$this->_pdf->writeHTMLCell(
			0, 0, '', '', $this->_content, 0, 1, 0, true, '', true
		);

		$this->_pdf->Ln();
	}

	/**
	 * Fetches the Global Setting by the name $settingName
	 *
	 * @param  string $settingName The Name of the Setting
	 * @return string              The Value of the Setting
	 */
	protected function globalSettingValueGet($settingName) {

		if(empty($this->_globalSettingsStmt)) {
			$this->_globalSettingsStmt = $this->_pdo->prepare(
				'SELECT value FROM SystemGlobalSettings WHERE name = :name'
			);
		}

		$this->_globalSettingsStmt->execute(array('name' => $settingName));

		return $this->_globalSettingsStmt->fetchColumn();
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

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

	/**
	 * The Title of the PDF-File
	 * @var string
	 */
	protected $_title;

	/**
	 * The Text of the PDF-File
	 * @var string
	 */
	protected $_content;

	/**
	 * Allows for fetching various data from the Server
	 * @var Pdo
	 */
	protected $_pdo;

	/**
	 * A connection to the Global-Settings-Table
	 * @var PdoStatement
	 */
	protected $_globalSettingsStmt;

	/**
	 * The Path to the Logo
	 * @var string
	 */
	protected $_logopath;

}


?>
