<?php
session_start();

$roleRedirects = [
    'superadministrator' => '../auth/login.php',
    'admin' => '../auth/login.php',
    'auditor' => '../auth/login.php',
    'securityauditor' => '../auth/login.php',
    'financemanager' => '../auth/login.php',
    'procurementofficer' => '../auth/login.php',
    'accountspayable' => '../auth/login.php',
    'assetmanager' => '../auth/login.php',
    'complianceofficer' => '../auth/login.php'
];

$redirect = '../auth/login.php';

if (isset($_SESSION['role']) && isset($roleRedirects[$_SESSION['role']])) {
    $redirect = $roleRedirects[$_SESSION['role']];
}

$_SESSION = [];
session_unset();
session_destroy();

header("Location: $redirect");
exit;
