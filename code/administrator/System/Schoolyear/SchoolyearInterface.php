<?php

require_once PATH_ADMIN . '/AdminInterface.php';

class SchoolyearInterface extends AdminInterface {

	////////////////////////////////////////////////////////////////////////////////
	//Constructor
	////////////////////////////////////////////////////////////////////////////////
	public function __construct ($modPath, $smarty) {

		parent::__construct($modPath, $smarty);
		$this->parentPath = $this->tplFilePath . 'header.tpl';
		$this->smarty->assign('inh_path', $this->parentPath);
		$this->sectionString = 'System|Schoolyear';
	}

	////////////////////////////////////////////////////////////////////////////////
	//Getters and Setters
	////////////////////////////////////////////////////////////////////////////////

	////////////////////////////////////////////////////////////////////////////////
	//Methods
	////////////////////////////////////////////////////////////////////////////////
	public function displayMainMenu () {
		$this->smarty->display($this->tplFilePath . 'mainMenu.tpl');
	}

	public function displayAddSchoolYear () {

		$inputContainer = array(
			array(
				'name'			 => 'label',
				'displayName'	 => _g('Name of the Schoolyear'),
				'type'			 => 'text',
			),
			array(
				'name'			 => 'active',
				'displayName'	 => _g('Is the Schoolyear active'),
				'type'			 => 'checkbox',
			),
		);

		$headString = _g('Add an Schoolyear');
		$submitString = _g('Add an Schoolyear');
		$actionString = 'addSchoolYear';

		$this->generalForm($headString, $this->sectionString, $actionString, $inputContainer, $submitString);
	}

	public function displayShowSchoolYears ($schoolYears) {

		$this->smarty->assign('schoolYears', $schoolYears);
		$this->smarty->display($this->tplFilePath . 'showSchoolYears.tpl');
	}

	public function displayActivateSchoolYearConfirmation ($schoolYear) {

		$promptMessage = _g('Do you really want to activate the Schoolyear "%1$s"? The other activated Schoolyear will be deactivated',
			$schoolYear['label']);
		$actionString = 'activateSchoolYear&ID=' . $schoolYear['ID'];
		$confirmedString = _g('Yes, I want to activate the Schoolyear');
		$notConfirmedString = _g(
			'No, I do not want to activate the Schoolyear');

		$this->confirmationDialog($promptMessage, $this->sectionString, $actionString, $confirmedString,
			$notConfirmedString);
	}

	public function displayChangeSchoolYear ($schoolYear) {


		$inputContainer = array(
			0 => array(
				'name'			 => 'label',
				'displayName'	 => _g('Name of the Schoolyear'),
				'type'			 => 'text',
				'value'			 => $schoolYear['label'],
			),
			array(
				'name'			 => 'active',
				'displayName'	 => _g('Is the Schoolyear active'),
				'type'			 => 'checkbox',
				'value'			 => $schoolYear['active'],
			),
		);
		if($schoolYear['active']) {
			$inputContainer [1] ['optionString'] = 'checked';
		}
		$actionString = 'changeSchoolYear&ID=' . $schoolYear['ID'];
		$headString = _g('Change the Schoolyear');
		$submitString = _g('Change Schoolyear');

		$this->generalForm($headString, $this->sectionString, $actionString, $inputContainer, $submitString);
	}

	public function displayDeleteSchoolYearConfirmation($schoolyear) {

		$promptMessage = sprintf(_g('Do you really want to delete the Schoolyear "%s"? WARNING: Problems will occur if you do this! Not all parts of the system support this!', $schoolyear['label']));
		$actionString = 'deleteSchoolYear&ID=' . $schoolyear['id'];
		$confirmedString = _g('Yes, I want to break the System and delete the Schoolyear');
		$notConfirmedString = _g('No, I do not want to break the System and delete the Schoolyear');

		$this->confirmationDialog($promptMessage, $this->sectionString, $actionString, $confirmedString, $notConfirmedString);
	}

	////////////////////////////////////////////////////////////////////////////////
	//Implementations
	////////////////////////////////////////////////////////////////////////////////

	////////////////////////////////////////////////////////////////////////////////
	//Attributes
	////////////////////////////////////////////////////////////////////////////////

	private $sectionString;
}

?>
