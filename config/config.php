<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'financial_system');
define('DB_USER', '');
define('DB_PASS', '');

$connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($connection->connect_error) {
    die('Database connection failed');
}

date_default_timezone_set('Asia/Manila');
