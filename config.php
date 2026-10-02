<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const APP_NAME = 'Borana University Online Student Registration System';
const BASE_URL = '/borana_student_registration';

date_default_timezone_set('Africa/Addis_Ababa');

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/functions.php';
