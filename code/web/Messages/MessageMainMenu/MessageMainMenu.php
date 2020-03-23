<?php

require_once PATH_INCLUDE . '/Module.php';
require_once PATH_WEB . '/Messages/MessageFunctions.php';
require_once PATH_WEB . '/Messages/Messages.php';

/**
 * Entry-Point for the User to display, print and create new messages
 * The User can only create a new Message if he is in the correct group
 *
 * @author Mirek Hancl
 * @author Pascal Ernst <pascal.cc.ernst@gmail.com>
 */
class MessageMainMenu extends Messages {


	///////////////////////////////////////////////////////////////////////
	//Constructor
	///////////////////////////////////////////////////////////////////////
	public function __construct($name, $display_name, $path) {
		parent::__construct($name, $display_name, $path);
		$this->_smartyPath = PATH_SMARTY_TPL . '/web' . $path;
	}

	///////////////////////////////////////////////////////////////////////
	//Methods
	///////////////////////////////////////////////////////////////////////
	public function execute($dataContainer) {

		$this->init($dataContainer);

		if (isset($_GET['action'])) {
			$action=$_GET['action'];
			switch ($action) {
				case 'deleteMessage':
					$this->deleteMessage();
					break;
				case 'showMessage':
					$this->showMessage();
					break;
				case 'showMessageAdmin':
					$this->showMessageAdmin();
					break;
				case 'searchUserAjax':
					$this->searchUserAjax();
					break;
				default:
					die('wrong Action-value');
					break;
			}
		}
		else {
			$this->showMainMenu();
		}
	}

	private function init($dataContainer) {

		defined('_WEXEC') or die("Access denied");
		$this->_smarty = $dataContainer->getSmarty();
		$this->_interface = new WebInterface($this->_smarty);
		require_once PATH_INCLUDE . '/TableMng.php';
		TableMng::init();
		$this->setEditor();
	}

	/**
	 * Sets the variable $_isEditor based on the User
	 */
	private function setEditor() {

		try {
			$contractGID = TableMng::query('SELECT value FROM SystemGlobalSettings
				WHERE name = "messageEditGroupId"');
			$userGID = TableMng::query('SELECT groupId FROM SystemUsersInGroups WHERE userId =
				"'.$_SESSION['uid'].'"');
			if(!count($contractGID)) {
				throw new Exception('Es wurde noch keiner Gruppe erlaubt, Nachrichten zu editieren!');
			}
			$this->_isEditor = $this->searchInMultiDimArray($contractGID[0]['value'],$userGID);
		} catch (MySQLVoidDataException $e) {
			echo 'Konnte die Gruppe nicht überprüfen!';
			$this->_isEditor = false;

		} catch (Exception $e) {
			$this->_interface->DieError('Konnte keine Überprüfung der Gruppe vornehmen!');
		}
	}

	/**
	 * Workaround for the problem that a user can be in more than one group.
	 * If one of the group ids has the right to create messages, return this group id.
	 * @param unknown $search
	 * @param unknown $array
	 * @return unknown|boolean
	 */
	private function searchInMultiDimArray($search, $array)
			{
				foreach($array as $key => $values)
				{
					if(in_array($search, $values))
					{
						return true;
					}
				}
				return false;
			}

	/**
	 * Fetches the messages that the User is allowed to manage
	 *
	 * @return array() an array of array-elements describing the messages
	 */
	private function fetchManagedMessages() {
		$messages = array();
		$query = sprintf(
			'SELECT m.ID AS ID,m.title AS title,m.validFrom AS validFrom,
				m.validTo AS validTo, m.GID as GID
			FROM MessageMessages m
			JOIN MessageManagers mm ON m.ID = mm.messageId AND mm.userId = %s
			', $_SESSION['uid']);
		try {
			$messages = TableMng::query ($query);
		} catch (MySQLVoidDataException $e) {
			$this->_smarty->append('error','Konnte keine selbst-erstellten Nachrichten finden');
		} catch (Exception $e) {
			$this->_smarty->append(
					sprintf ('Konnte die selbst-erstellten Nachrichten nicht abrufen!', $e->getMessage()));
		}
		return $messages;
	}

	/**
	 * Fetches the messages directed towards this user
	 *
	 * @return array() an array of array-elements describing the messages
	 */
	private function fetchReceivedMessages() {
		$messages = array();
		$query = sprintf(
			'SELECT m.id AS ID,m.title AS title,m.validFrom AS validFrom,
			m.validTo AS validTo, mr.return AS "return", m.GID as GID
			FROM MessageMessages m
			JOIN MessageReceivers mr ON mr.userId = %s
				AND m.ID = mr.messageId
			WHERE SYSDATE() BETWEEN m.validFrom AND DATE_ADD(m.validTo, INTERVAL 1 DAY)
			ORDER BY m.validTo
			', $_SESSION['uid']);
		try {
			$messages = TableMng::query ($query);
		} catch (MySQLVoidDataException $e) {
			$this->_smarty->assign('error','Keine Post vorhanden!');
		} catch (Exception $e) {
			$this->_interface->DieError (
					sprintf ('Konnte die Post nicht abrufen! %s', $e->getMessage()));
		}
		return $messages;
	}

	/**
	 * Displays the MainMenu of Messages
	 */
	private function showMainMenu() {
		$createdMsg = $receivedMsg = array();
		if ($this->_isEditor) {
			$this->_smarty->assign('editor',true);
			$createdMsg = $this->fetchManagedMessages();
		}
		$schbasID = MessageFunctions::getMessageGroupID('schbas');
		$receivedMsg = $this->fetchReceivedMessages();
		$this->_smarty->assign('createdMsg', $createdMsg);
		$this->_smarty->assign('receivedMsg', $receivedMsg);
		$this->_smarty->assign('BaBeSkTerminal', $this->checkIsKioskMode());
		$this->_smarty->assign('schbasID',$schbasID);
		$this->_smarty->display($this->_smartyPath . 'menu.tpl');
	}

	/**
	 * Deletes a Message if the User is the creator, else displays error
	 */
	private function deleteMessage() {
		$messageId = TableMng::getDb()->real_escape_string($_GET['ID']);
		if(MessageFunctions::checkIsCreatorOf($messageId, $_SESSION['uid'])) {
			MessageFunctions::deleteMessage($messageId, $_SESSION['uid']);
		}
		else {
			$this->_interface->DieError('Nur der Ersteller der Nachricht kann diese löschen');
		}
		$this->showMainMenu();
	}

	/**
	 * Shows a specific message
	 */
	private function showMessage() {
		$db = TableMng::getDb();
		$messageId = $db->real_escape_string($_GET['ID']);
		if($isManager = MessageFunctions::checkIsManagerOf($messageId, $_SESSION['uid'])){
			$msgText = $msgTitle = $forename = $name = $grade = $msgRecId = $msgReturn = '';
			$query = "SELECT m.title, m.text
				      FROM MessageMessages m
				      WHERE m.ID = ?";
			$stmt = $db->prepare($query);
			if($stmt) {
				$stmt->bind_param('s', $messageId);
				$stmt->bind_result($msgTitle, $msgText);

				$stmt->execute();
				while($stmt->fetch()) {
					// User got multiple messages of the same kind, select only
					// the last one
				}

				$this->createPdf($msgTitle, $msgText, "", "","","");
            }
            else {
                $this->_interface->DieError('Konnte die Nachrichtendaten nicht
					abrufen');
            }
		}
		else if(MessageFunctions::checkHasReceived($messageId, $_SESSION['uid'])) {

			$msgText = $msgTitle = $forename = $name = $grade = $msgRecId = $msgReturn = $cardID = $isRead = '';
			$query = "SELECT m.title, m.text, mr.read, mr.ID, mr.return,
					u.forename, u.name, CONCAT(g.gradelevel, g.label), c.cardnumber
				FROM SystemUsers u
				JOIN MessageReceivers mr ON mr.userId = u.ID
				JOIN MessageMessages m ON mr.messageId = m.ID AND m.ID = ?
				LEFT JOIN SystemAttendances uigs ON
					uigs.userId = u.ID AND
					uigs.schoolyearId = @activeSchoolyear
				LEFT JOIN SystemGrades g ON g.ID = uigs.gradeId
				LEFT JOIN BabeskCards c ON u.ID = c.UID
				WHERE u.ID = ?";
			$stmt = $db->prepare($query);
			if($stmt) {
				$stmt->bind_param('ss', $messageId, $_SESSION['uid']);
				$stmt->bind_result($msgTitle, $msgText, $isRead, $msgRecId, $msgReturn,
					$forename, $name, $grade, $cardID);

				$stmt->execute();
				while($stmt->fetch()) {
					// User got multiple messages of the same kind, select only
					// the last one
				}
				if($isRead == '0') {
					$this->markMsgAsRead($msgRecId);
				}
				$msgText = str_replace("{vorname}", $forename, $msgText);
				$msgText = str_replace("{name}", $name, $msgText);
				$msgText = str_replace("{klasse}", $grade, $msgText);
                $msgText = str_replace("{kartennummer}", $cardID, $msgText);

				$this->createPdf($msgTitle, $msgText, $grade, $msgReturn,$messageId,$_SESSION['uid']);
			}
			else {
				$this->_interface->DieError('Konnte die Nachrichtendaten nicht
					abrufen');
			}
		}
		else {
			$this->_interface->DieError ( 'Kein Zugriff erlaubt!');
		}
	}

	/**
	 * Checks if the Client runs in Kioskmode
	 * We dont want to let the user circumvent the Kioskmode (for example if he
	 * opens PDF-files, another program gets opened up, which can break the
	 * kiosk-mode)
	 */
	private function checkIsKioskMode() {
		return preg_match("/BaBeSK/i", $_SERVER['HTTP_USER_AGENT']);
	}


	private function markMsgAsRead($msgReceiverId) {
		$db = TableMng::getDb();
		$query = sprintf(
			'UPDATE MessageReceivers SET `read` = "1" WHERE ID = "%s";',
			$db->real_escape_string($msgReceiverId));
		if($db->query($query)){
			return;
		}
		else {
			$this->_interface->DieError('Konnte die Nachricht nicht als gelesen markieren' . $db->error);
		}
	}

	private function showMessageAdmin() {
		require_once 'MessageShowAdmin.php';
		MessageShowAdmin::init($this->_smarty, $this->_smartyPath, $this->_interface);
		MessageShowAdmin::execute();
	}

	/**
	 * Creates a PDF for the Participation Confirmation and returns its Path
	 */
	private function createPdf ($title, $text, $class, $msgReturn, $mid, $uid) {

		require_once 'MessageMainMenuPdf.php';

		try {
			$pdfCreator = new MessageMainMenuPdf($title, $text, $class,
				$msgReturn, $mid, $uid);
			$pdfCreator->create();
			$pdfCreator->output();

		} catch (Exception $e) {
			$this->_interface->DieError('Konnte die PDF nicht erstellen!');
		}
	}

	///////////////////////////////////////////////////////////////////////
	//Attributes
	///////////////////////////////////////////////////////////////////////

	/**
	 * The path to the Smarty-templates of this module
	 */
	protected $_smartyPath;

	/**
	 * An Smarty-Object, used to Output data
	 */
	protected $_smarty;

	/**
	 * Stores the Interface of this Module
	 */
	protected $_interface;

	/**
	 * Saves if the User is allowed to send Messages
	 */
	private $_isEditor;


	private $barcodeStyle = array(
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
		'text' => false,
		'font' => 'helvetica',
		'fontsize' => 8,
		'stretchtext' => 4
		);

}
?>
