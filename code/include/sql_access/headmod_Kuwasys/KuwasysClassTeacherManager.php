<?php

require_once PATH_ACCESS . '/TableManager.php';

class KuwasysClassTeacherManager extends TableManager {
	
		////////////////////////////////////////////////////////////////////////////////
		//Attributes
		////////////////////////////////////////////////////////////////////////////////
		
		////////////////////////////////////////////////////////////////////////////////
		//Constructor
		////////////////////////////////////////////////////////////////////////////////
		public function __construct($interface = NULL) {
			parent::__construct('classTeacher');
		}
		
		////////////////////////////////////////////////////////////////////////////////
		//Getters and Setters
		////////////////////////////////////////////////////////////////////////////////
		
		////////////////////////////////////////////////////////////////////////////////
		//Methods
		////////////////////////////////////////////////////////////////////////////////
		public function addClassTeacher ($name, $forename, $address, $telephone) {
			
			parent::addEntry('name', $name, 'forename', $forename, 'address', $address, 'telephone', $telephone);
		}
		
		public function deleteClassTeacher ($ID) {
			
			parent::delEntry($ID);
		}
		
		public function alterClassTeacher ($ID, $name, $forename, $address, $telephone) {
			
			parent::alterEntry($ID, 'name', $name, 'forename', $forename, 'address', $address, 'telephone', $telephone);
		}
		
		public function getAllClassTeachers () {
			
			$classTeachers = parent::getTableData();
			return $classTeachers;
		}
		
		public function getClassTeacher ($ID) {
			
			$classTeacher = parent::searchEntry('ID=' . $ID);
			return $classTeacher;
		}
		
		public function getLastAddedId () {
			$lastID = parent::getLastInsertedID();
			return $lastID;
		}
		
		public function getClassteachersByClassteacherIdArray ($classteacherIdArray) {
			
			$classteachers = $this->getMultipleEntriesByArray('ID', $classteacherIdArray);
			return $classteachers;
		}
		////////////////////////////////////////////////////////////////////////////////
		//Implementations
		////////////////////////////////////////////////////////////////////////////////
		
}

?>