<?php

namespace administrator\System\Users;

class PatchUser {

	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	public function __construct($dataContainer) {
		$this->_dataContainer = $dataContainer;
	}

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function patch($user, $data) {

		$this->_user = $user;
		// {activeGroups: [<groupIds>]}
		// oder
		// {activeGroups: false} if user should not have any groups
		if(isset($data['activeGroups'])) {
			$this->patchGroups($data['activeGroups']);
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function patchGroups($groups) {

		if($groups) {
			$groupIds = array_map(function($groupId) {
				return filter_var($groupId, FILTER_VALIDATE_INT);
			}, $groups);
		}
		else {
			$groupIds = [];
		}
		$query = $this->_em->createQuery(
			'SELECT g FROM DM:SystemGroups g
			INNER JOIN g.users u WITH u = :user
		');
		$query->setParameter('user', $this->_user);
		$existingGroups = $query->getResult();
		if(count($existingGroups)) {
			// Delete groups-assignments that are not in the given data
			foreach($existingGroups as $existingGroup) {
				if(!in_array($existingGroup->getId(), $groupIds)) {
					$this->_user->removeGroup($existingGroup);
				}
			}
		}
		if(count($groupIds)) {
			// Add groups-assignments that are not existing
			foreach($groupIds as $groupId) {
				if(count($existingGroups)) {
					foreach($existingGroups as $existingGroup) {
						if($existingGroup->getId() == $groupId) {
							continue 2;
						}
					}
				}
				$newGroup = $this->_em->getReference(
					'DM:SystemGroups', $groupId
				);
				$this->_user->addGroup($newGroup);
			}
		}
		$this->_em->persist($this->_user);
		$this->_em->flush();
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

	protected $_dataContainer;

	protected $_user;
}