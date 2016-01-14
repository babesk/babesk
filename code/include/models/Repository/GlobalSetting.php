<?php

namespace Repository;

class GlobalSetting extends \Doctrine\ORM\EntityRepository {

	public function getSetting($name) {

		try {
			$entry = $this->_em->getRepository('DM:SystemGlobalSettings')
				->findOneByName($name);
			if(!$entry) {
				$this->_em->getRepository('DM:SystemLog')->log(
					'Could not find a Global Setting', 'warning',
					'Repository\GlobalSetting', ['name' => $name]);
			}
			return $entry->getValue();

		} catch (\Doctrine\ORM\NonUniqueResultException $e) {
			$this->_em->getRepository('DM:SystemLog')->log(
				'Duplicated Global Setting!', 'error',
				'Repository\GlobalSetting', ['name' => $name]);
			return false;
		}
	}

	public function setSetting($name, $value) {

		try {
			$entry = $this->_em->getRepository('DM:SystemGlobalSettings')->findOneByName($name);
			if(!$entry) {
				$entry = new \Babesk\ORM\SystemGlobalSettings();
				$entry->setName($name);
			}
			$entry->setValue($value);
			$this->_em->persist($entry);
			$this->_em->flush();
			return true;

		} catch (\Exception $e) {
			$this->_em->getRepository('DM:SystemLog')->log(
				'Error setting a GlobalSetting', 'error',
				'Repository\GlobalSetting', ['name' => $name,
					'value' => $value]);
			return false;
		}
	}
}

?>