<?php

require_once PATH_INCLUDE . '/Module.php';
require_once PATH_ADMIN . '/System/System.php';

class ForeignLanguage extends System {

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {

		$this->entryPoint($dataContainer);

		require_once 'AdminForeignLanguageInterface.php';
		require_once 'AdminForeignLanguageProcessing.php';
        require_once PATH_ACCESS . '/CardManager.php';
        require_once PATH_ACCESS . '/UserManager.php';
        $this->cardManager = new CardManager();
        $this->userManager = new UserManager();

		$ForeignLanguageInterface = new AdminForeignLanguageInterface($this->relPath);
		$ForeignLanguageProcessing = new AdminForeignLanguageProcessing($ForeignLanguageInterface);

		$action = $_GET['action'];
		switch ($action) {
			case 1: //edit the language list
				$this->editDisplay();
				break;
			case 2: //save the language list
				$this->editUpload($_POST);
				// $ForeignLanguageProcessing->EditForeignLanguages($_POST);
				break;
			case 3: //edit the users
				$userID = null;
				if (isset($_POST['search']) && $_POST['search'] != "") {
					try {
						$userID = $this->cardManager->getUserID($_POST['search']);
					} catch (Exception $e) {
						$userID = $e->getMessage();
					}
					if ($userID == 'MySQL returned no data!') {
						try {
							$userID = $this->userManager->getUserID($_POST['search']);
						} catch (Exception $e) {
							$ForeignLanguageInterface->dieError("Benutzer nicht gefunden!");
						}

					}

					$ForeignLanguageProcessing->ShowSingleUser($userID);

					break;
				}
				if (isset($_GET['filter'])) {
					$ForeignLanguageProcessing->ShowUsers($_GET['filter']);
				} else {
					$ForeignLanguageProcessing->ShowUsers("name");
				};
				break;
			case 4: //save the users
				$ForeignLanguageProcessing->SaveUsers($_POST);
				break;
			case 5: //edit user via cardscan
				$ForeignLanguageProcessing->AssignForeignLanguageWithCardscan($_POST);
				break;
			default:
				$ForeignLanguageInterface->ShowSelectionFunctionality();
		}


	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function entryPoint($dataContainer) {

		parent::entryPoint($dataContainer);
		parent::moduleTemplatePathSet();
	}

	private function editDisplay() {

		$languages = array();
		$languagesString = $this->_em->getRepository(
				'DM:SystemGlobalSettings'
			)->findOneByName('foreign_language')->getValue();
		if(!empty($languagesString)) {
			$languages = explode('|', $languagesString);
		}
		$this->_smarty->assign('foreignLanguages', $languages);
		$this->displayTpl('show_foreignLanguages.tpl');
	}

	private function editUpload($data) {

		//Remove not filled out fields
		foreach($data['foreignLanguages'] as $ind => $lan) {
			if(empty($lan)) {
				unset($data['foreignLanguages'][$ind]);
			}
		}
		$string = implode('|', $data['foreignLanguages']);
		//Upload foreign languages
		$setting = $this->_em->getRepository(
				'DM:SystemGlobalSettings'
			)->findOneByName('foreign_language')
			->setValue($string);
		$this->_em->persist($setting);
		$this->_em->flush();
		$this->_interface->dieSuccess(
			'Die Fremdsprachen wurden erfolgreich verändert'
		);
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

}

?>
