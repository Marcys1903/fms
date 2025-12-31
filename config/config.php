<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "financial_system";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Database connection failed");
}
