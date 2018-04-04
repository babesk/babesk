<?php

require_once PATH_INCLUDE . '/Module.php';
require_once PATH_ADMIN . '/System/System.php';

class Religion extends System {

	////////////////////////////////////////////////////////////////////////////////
	//Attributes

	////////////////////////////////////////////////////////////////////////////////
	//Constructor
	public function __construct($name, $display_name, $path) {
		parent::__construct($name, $display_name, $path);
	}

	////////////////////////////////////////////////////////////////////////////////
	//Methods
	public function execute($dataContainer) {

		defined('_AEXEC') or die('Access denied');

		require_once 'AdminReligionInterface.php';
		require_once 'AdminReligionProcessing.php';
        require_once PATH_ACCESS . '/CardManager.php';
        require_once PATH_ACCESS . '/UserManager.php';
        $this->cardManager = new CardManager();
        $this->userManager = new UserManager();

		$ReligionInterface = new AdminReligionInterface($this->relPath);
		$ReligionProcessing = new AdminReligionProcessing($ReligionInterface);


			$action = $_GET['action'];
			switch ($action) {
				case 1: //edit the confession list
					$ReligionProcessing->EditReligions(0);
				break;
				case 2: //save the confession list
					$ReligionProcessing->EditReligions($_POST);
				break;
				case 3: //edit the users
                    $userID = null;
                    if (isset($_POST['search']) && $_POST['search'] != "") {
                        try {
                            $userID = $this->cardManager->getUserID($_POST['search']);
                        } catch (Exception $e) {
                            $userID =  $e->getMessage();
                        }
                        if ($userID == 'MySQL returned no data!') {
                            try {
                                $userID = $this->userManager->getUserID($_POST['search']);
                            } catch (Exception $e) {
                                $ReligionInterface->dieError("Benutzer nicht gefunden!");
                            }

                        }

                        $ReligionProcessing->ShowSingleUser($userID);

                        break;
                    }
                    if (isset($_GET['filter'])) {
                        $ReligionProcessing->ShowUsers($_GET['filter']);
                    } else {
                        $ReligionProcessing->ShowUsers("name");
                    };
				break;
				case 4: //save the users
					$ReligionProcessing->SaveUsers($_POST);
				break;
				case 5: //edit user via cardscan
					$ReligionProcessing->AssignConfessionWithCardscan($_POST);
					break;
				default:
                    $ReligionInterface->ShowSelectionFunctionality();
			}
	}
}

?>
