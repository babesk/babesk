<?php

require_once PATH_ACCESS . '/TableManager.php';

class GradeManager extends TableManager
{

    public function __construct()
    {
        parent::__construct('SystemGrades');
    }

}