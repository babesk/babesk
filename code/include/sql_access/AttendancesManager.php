<?php

require_once PATH_ACCESS . '/TableManager.php';

class AttendancesManager extends TableManager
{

    public function __construct()
    {
        parent::__construct('SystemAttendances');
    }

}