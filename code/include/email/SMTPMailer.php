<?php

require_once PATH_3RD_PARTY . '/PHPMailer/class.phpmailer.php';
require_once PATH_ACCESS . '/GlobalSettingsManager.php';

/** This class is a wrapper for PHPMailer, for easy sending of mails
 * It uses the SMTP-technology to send the mails
 */
class SMTPMailer extends PHPMailer {
	public function __construct ($interface) {
		parent::__construct ();
		$this->IsSMTP (); //says PHPMailer that we want SMTP
		///@todo: export SMTPAuth-Configuration as a setting
		$this->SMTPAuth = true;
		$this->_interface = $interface;
		$this->_globalSettingsManager = new GlobalSettingsManager ();
	}

	/**
	 * Loads an Email by the Xml-data of the given path of the Xml
	 * @param path the full path to the XML-File
	 * @param utf8Decode if the content of the XMl-File should be decoded from utf-8 to ISO_8859-1 (Latin-1)
	 */
	public function emailFromXmlLoad ($path, $utf8Decode = true) {
		$this->emailFromXmlInit ($path);
		$this->Subject = $this->emailFromXmlGetObj (SMTPMailer::SMTP_SUBJECT);
		$this->Body = $this->emailFromXmlGetObj (SMTPMailer::SMTP_BODY);
		if($utf8Decode) {
			$this->Subject = utf8_decode($this->Subject);
			$this->Body = utf8_decode($this->Body);
		}
	}

	/**
	 * This function loads information for the Email-SMTP-Protocol from the Database
	 * Looks for the smtp-Host, the Username and the Password for the Smtp-Server
	 * as well as the sender and the sendername.
	 */
	public function smtpDataInDatabaseLoad () {
		try {
			$this->Host = $this->_globalSettingsManager->valueGet (GlobalSettings::SMTP_HOST);
			$this->Username = $this->_globalSettingsManager->valueGet (GlobalSettings::SMTP_USERNAME);
			$this->Password = $this->_globalSettingsManager->valueGet (GlobalSettings::SMTP_PASSWORD);
			$this->FromName = $this->_globalSettingsManager->valueGet (GlobalSettings::SMTP_FROMNAME);
			$this->From = $this->_globalSettingsManager->valueGet (GlobalSettings::SMTP_FROM);
		} catch (Exception $e) {
			$this->_interface->dieError("Could not fetch the SMTP-Configuration from the Database");
		}
	}

	/**
	 * Changes / Adds the SMTP-data for the Database
	 * @param host The Host of the SMTP-Server, like smtp.gmx.net
	 * @param username The Username to allow to Login to the smtp-server
	 * @param password The Password needed to Login to the Server
	 * @param fromName The Name of the addressor
	 * @param from The EmailAddress of the addressor
	 *
	 */
	public function smtpDataInDatabaseSet ($host, $username, $password, $fromName, $from) {
		try {
			$this->_globalSettingsManager->valueSet (GlobalSettings::SMTP_HOST, $host);
			$this->_globalSettingsManager->valueSet (GlobalSettings::SMTP_USERNAME, $username);
			$this->_globalSettingsManager->valueSet (GlobalSettings::SMTP_PASSWORD, $password);
			$this->_globalSettingsManager->valueSet (GlobalSettings::SMTP_FROMNAME, $fromName);
			$this->_globalSettingsManager->valueSet (GlobalSettings::SMTP_FROM, $from);
		} catch (Exception $e) {
			$this->_interface->dieError ("Could not change the Smtp-Data in the Database");
		}
	}

	/**
	 * Loads the xml in the given Path into this class
	 */
	private function emailFromXmlInit ($path) {
		$this->_xmlEmail = simplexml_load_file($path);
		if (!$this->_xmlEmail) {
			$this->_interface->dieError ("Could not load the Xml of the Email!"
				. " Path:" . $path);
		}
	}

	/**
	 * Tries to fetch an Object in the Email-Xml with the name $objName and returns it
	 * @return string
	 */
	private function emailFromXmlGetObj ($objName) {
		if(!is_object($this->_xmlEmail->$objName)) {
			$this->_interface->dieError ("Could not load an object of the email-Xml!" . " ObjectName:" . $objName);
		}
		return $this->_xmlEmail->$objName;
	}

	private $_xmlEmail;
	private $_interface;
	private $_globalSettingsManager;
	const SMTP_SUBJECT = 'subject';
	const SMTP_BODY = 'body';
}

?>