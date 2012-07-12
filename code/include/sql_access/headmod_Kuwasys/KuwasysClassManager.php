<?php

require_once PATH_ACCESS . '/TableManager.php';

class KuwasysClassManager extends TableManager {
	
		////////////////////////////////////////////////////////////////////////////////
		//Attributes
		////////////////////////////////////////////////////////////////////////////////
		
		////////////////////////////////////////////////////////////////////////////////
		//Constructor
		////////////////////////////////////////////////////////////////////////////////
		public function __construct($interface = NULL) {
			parent::__construct('class');
		}
		
		////////////////////////////////////////////////////////////////////////////////
		//Getters and Setters
		////////////////////////////////////////////////////////////////////////////////
		
		////////////////////////////////////////////////////////////////////////////////
		//Methods
		////////////////////////////////////////////////////////////////////////////////
		public function addClass ($label, $maxRegistration) {
			$this->addEntry('label', $label, 'maxRegistration', $maxRegistration);
		}
		
		public function deleteClass ($ID) {
			$this->delEntry($ID);
		}
		
		public function alterClass ($ID, $label, $maxRegistration) {
			$this->alterEntry($ID, 'label', $label, 'maxRegistration', $maxRegistration);
		}
		
		public function getAllClasses () {
			return $this->getTableData();
		}
		
		public function getLabelOfClass ($ID) {
			$label = $this->getEntryValue($ID, 'label');
			return $label;
		}
		
		public function getClass ($ID) {
			$class = $this->searchEntry('ID =' . $ID);
			return $class;
		}
		
		public function getLastClassID () {
			$lastID = $this->getLastInsertedID();
			return $lastID;
		}
		////////////////////////////////////////////////////////////////////////////////
		//Implementations
		////////////////////////////////////////////////////////////////////////////////
		
}
?>