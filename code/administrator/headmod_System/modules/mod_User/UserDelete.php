<?php

class UserDelete {

	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	public function __construct($smarty) {

		$this->_smarty = $smarty;
	}

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	/**
	 * Deletes a user from the Database
	 */
	public function deleteFromDb() {

		try {
			$userId = $_GET['ID'];
			$userToDeleteRes = TableMng::query(
				"SELECT forename, name, credit, birthday,
					CONCAT(g.gradeValue, '-', g.label) AS grade
				FROM users u
				LEFT JOIN jointUsersInGrade uig ON uig.UserID = u.ID
				LEFT JOIN grade g ON uig.GradeID = g.ID
				WHERE u.ID = $userId", true);
			$userToDelete = $userToDeleteRes[0];

			if(count($userToDelete)) {
				TableMng::getDb()->autocommit(false);

				$this->deleteUpload($userId);
				$this->deleteConditionsCheck($userId);

				if(empty($userToDelete['grade'])) {
					$userToDelete['grade'] = '---';
				}
				$this->createPdf($userToDelete, $userId);

				TableMng::getDb()->autocommit(true);
			}
			else {
				die(json_encode(array('value' => 'error', 'message' => 'Der zu löschende Benutzer konnte nicht abgerufen werden')));
			}

		} catch (Exception $e) {
			die(json_encode(array('value' => 'error', 'message' => 'Ein Fehler ist beim Löschen des Benutzers aufgetreten.' . $e->getMessage())));
		}
		//success! yay!
		die(json_encode(array('value' => 'success', 'message' => "Der Benutzer $userToDelete[forename] $userToDelete[name] wurde erfolgreich gelöscht")));
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function deleteConditionsCheck($userId) {

		try {
			$this->deleteConditionSchbas($userId);

		} catch (Exception $e) {
			die(json_encode(array('value' => 'error',
				'message' => 'Ein Fehler ist beim Überprüfen der
				Voraussetzungen aufgetreten')));
		}
	}

	protected function deleteUpload($uid) {

		$additionalQuerys = $this->deleteQuerysCreateAdditional($uid);

		TableMng::query(
			"DELETE FROM users WHERE ID = $uid;
			$additionalQuerys
			", false, true);

		// var_dump($additionalQuerys);
	}

	protected function deleteQuerysCreateAdditional($uid) {

		$querys = '';

		if(count(TableMng::query('SHOW TABLES LIKE "cards";', true))) {
			$querys .= "DELETE FROM cards WHERE UID = $uid;";
		}
		if(count(TableMng::query(
			'SHOW TABLES LIKE "jointUsersInClass";', true))) {
			$querys .= "DELETE FROM jointUsersInClass WHERE UserID = $uid;";
		}
		if(count(TableMng::query(
			'SHOW TABLES LIKE "jointUsersInGrade";', true))) {
			$querys .= "DELETE FROM jointUsersInGrade WHERE UserID = $uid;";
		}
		if(count(TableMng::query(
			'SHOW TABLES LIKE "jointUsersInSchoolYear";', true))) {
			$querys .= "DELETE FROM jointUsersInSchoolYear
				WHERE UserID = $uid;";
		}
		if(count(TableMng::query(
			'SHOW TABLES LIKE "MessageReceivers";', true))) {
			$querys .= "DELETE FROM MessageReceivers WHERE userId = $uid;";
		}
		if(count(TableMng::query(
			"SHOW TABLES LIKE 'MessageManagers';", true))) {
			$querys .= "DELETE FROM MessageManagers WHERE userId = $uid;";
		}
		if(count(TableMng::query(
			'SHOW TABLES LIKE "soli_orders";', true))) {
			$querys .= "DELETE FROM soli_orders WHERE UID = $uid;";
		}
		if(count(TableMng::query(
			'SHOW TABLES LIKE "soli_coupons";', true))) {
			$querys .= "DELETE FROM soli_coupons WHERE UID = $uid;";
		}
		if(count(TableMng::query(
			'SHOW TABLES LIKE "MessageCarbonFootprint";', true))) {
			$querys .= "DELETE FROM MessageCarbonFootprint
				WHERE authorId = $uid;";
		}
		if(count(TableMng::query(
			'SHOW TABLES LIKE "fits";', true))) {
			$querys .= "DELETE FROM fits WHERE ID = $uid;";
		}

		return $querys;
	}

	protected function deleteConditionSchbas($uid) {

		$stillLoaned = TableMng::query(
			"SELECT COUNT(*) AS count FROM schbas_lending
				WHERE user_id = $uid");

		if($stillLoaned[0]['count'] > 0) {
			die(json_encode(array('value' => 'error',
				'message' => 'Es sind immer noch Bücher verliehen!')));
		}
		else {
			return;
		}
	}

	/**
	 * create a PDF for the Message
	 * @todo Refactor this Code
	 */
	protected function createPdf($user,$uid) {

		try {
			require_once  PATH_INCLUDE .('/pdf/tcpdf/config/lang/ger.php');
			require_once PATH_INCLUDE . '/pdf/tcpdf/tcpdf.php';

			// create new PDF document
			$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

			// set document information
			$pdf->SetCreator(PDF_CREATOR);
			$pdf->SetAuthor('LeG Uelzen');

			$pdf->SetKeywords('');

			// set default header data
			$pdf->SetHeaderData('../../../../web/headmod_Messages/modules/mod_MessageMainMenu/logo.jpg', 15, 'LeG Uelzen', "Abmeldung von: ".$user['forename']." ".$user['name']."\nKlasse: ".$user['grade'], array(0,0,0), array(0,0,0));
			$pdf->setFooterData($tc=array(0,0,0), $lc=array(0,0,0));

			// set header and footer fonts
			$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
			$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

			// set default monospaced font
			$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

			//set margins
			$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
			$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
			$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

			//set auto page breaks
			$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

			//set image scale factor
			$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

			//set some language-dependent strings
			$pdf->setLanguageArray($l);

			// ---------------------------------------------------------

			// set default font subsetting mode
			$pdf->setFontSubsetting(true);

			// Set font
			// dejavusans is a UTF-8 Unicode font, if you only need to
			// print standard ASCII chars, you can use core fonts like
			// helvetica or times to reduce file size.
			$pdf->SetFont('helvetica', '', 11, '', true);

			// Add a page
			// This method has several options, check the source code documentation for more information.
			$pdf->AddPage();

			// set text shadow effect
			$pdf->setTextShadow(array('enabled'=>true, 'depth_w'=>0.2, 'depth_h'=>0.2, 'color'=>array(196,196,196), 'opacity'=>1, 'blend_mode'=>'Normal'));

			// Set some content to print
			$html = '<p align="center"><h2>R&uuml;ckgabe der LeG-Card / L&ouml;schung der Benutzerdaten</h2></p><br>'
					.'Hiermit wird best&auml;tigt, dass die Schulb&uuml;cher von '.$user['forename'].' '.$user['name'].' vollst&auml;ndig zur&uuml;ckgegeben wurden. <br/>
	Hiermit wird best&auml;tigt, dass s&auml;mtliche personenbezogenen Daten am '.date("d.m.Y").' aus dem System gel&ouml;scht wurden.<br/>';

			if ($user['credit']=="0.00") $html .= 'Es liegt kein Restguthaben vor.<br/>';
			else $html .= 'Es liegt ein Restguthaben in H&ouml;he von '.$user['credit'].' &euro; vor. Dieses muss beim Caterer abgeholt werden.<br/>';
	 $html .= 'Mit der R&uuml;ckgabe der LeG-Card kann das Pfandgeld in H&ouml;he von 3,50 &euro; zzgl. 0,50 &euro;, je nach Zustand der H&uuml;lle, ausbezahlt werden.<br/>
<hr>
<p align="center"><h3>Auszahlung des Restguthabens</h3></p><br>
Restguthaben in H&ouml;he von '.$user['credit'].' &euro; am ___.___.2013 erhalten.<br><br>
<br>						Unterschrift Caterer
		<br><hr>
<p align="center"><h3>Pfanderstattung</h3></p><br>
Bitte geben Sie diesen Abschnitt im Lessing-Gymnasium ab.<br>
Bitte kreuzen Sie an, ob Sie den Pfandbetrag an die Sch&uuml;lergenossenschaft Gnissel des LeG Uelzen spenden m&ouml;chten
		oder eine &Uuml;berweisung auf ein Bankkonto w&uuml;nschen.<br>

[&nbsp;&nbsp;] Das Pfandgeld m&ouml;chte ich an Gnissel spenden<br>
[&nbsp;&nbsp;] Ich m&ouml;chte das Pfandgeld auf folgendes Konto &uuml;berwiesen haben:<br>
Kontoinhaber:   <br>
Kontonummer:<br>
BLZ:		<br>
Kreditinstitut: <br><br>

Uelzen, den ___.___.2013
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Unterschrift Elternteil bzw. vollj&auml;hriger Sch&uuml;ler<br>


<hr>
<p align="center"><h3>Abschnitt f&uuml;r den Caterer</h3></p><br>
 Restguthaben in H&ouml;he von '.$user['credit'].' &euro; am ___.___.2013 erhalten.<br><br>
		<br><br>Unterschrift Elternteil bzw. vollj&auml;hriger Sch&uuml;ler
			';

			// Print text using writeHTMLCell()
			$pdf->writeHTMLCell($w=0, $h=0, $x='', $y='', $html, $border=0, $ln=1, $fill=0, $reseth=true, $align='', $autopadding=true);

			// ---------------------------------------------------------

			// Close and output PDF document
			// This method has several options, check the source code documentation for more information.
			$pdf->Output('deleted_'.$uid.'.pdf', 'D');
			return true;

		} catch (Exception $e) {

			die(json_encode(array('value' => 'error', 'message' => 'Konnte die Abschieds-PDF-Datei nicht generieren')));
		}
	}

	protected function deletePdf () {

		if (isset ($_GET['ID'])) {
			try {
				unlink (dirname(realpath('')) .
					"/include/pdf/tempPdf/deleted_" . $_GET['ID'].".pdf");
				$this->userInterface->showDeletePdfSuccess ();

			} catch(Exception $e) {
				$this->userInterface->dieError(
					'Fehler beim L&ouml;schen des PDFs.');
			}
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

}

?>