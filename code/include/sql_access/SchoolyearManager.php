<?php

require_once PATH_ACCESS . '/TableManager.php';

class SchoolyearManager extends TableManager
{

    public function __construct()
    {
        parent::__construct('SystemSchoolyears');
    }

    public function getActiveSchoolyear(){
        return $this->searchEntry("active = 1");

    }
}