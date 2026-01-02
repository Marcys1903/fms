<?php
session_start();
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // NOW CORRECT: status column exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user) {
        header("Location: login.php?error=invalid");
        exit();
    }

    // Verify password
    if (!password_verify($password, $user['password'])) {
        header("Location: login.php?error=invalid");
        exit();
    }

    // Set session variables - ALL COLUMNS NOW EXIST
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['level'] = $user['level'];
    $_SESSION['firstname'] = $user['firstname'] ?? '';
    $_SESSION['middlename'] = $user['middlename'] ?? '';
    $_SESSION['lastname'] = $user['lastname'] ?? '';
    $_SESSION['email'] = $user['email'] ?? '';
    $_SESSION['department'] = $user['department'] ?? '';
    
    // Convert role to lowercase and remove spaces for the key
    $roleKey = strtolower(str_replace(' ', '', $user['role']));

    // Role-Based Routing - PERFECTLY MATCHES DATABASE
    $roleRoutes = [
        // LEVEL 1: System Core
        'superadministrator' => '../superadmin/dashboard.php',
        'superadmin' => '../superadmin/dashboard.php',
        
        'itsystemadministrator' => '../admin/dashboard.php',
        
        // LEVEL 2: Financial Oversight
        'financialdirector' => '../financialdirector/dashboard.php',
        
        // LEVEL 3: Operational Management
        'budgetofficer' => '../budgetofficer/dashboard.php',
        
        'accountingofficer' => '../accountant/dashboard.php',
        'accountant' => '../accountant/dashboard.php',
        
        'treasurer' => '../treasurer/dashboard.php',
        
        'procurementofficer' => '../procurementofficer/dashboard.php',
        
        'assetmanagementofficer' => '../assetmanagementofficer/dashboard.php',
        
        // LEVEL 5: Monitoring & Verification
        'auditor' => '../auditor/dashboard.php',
        
        'auditingandcomplianceofficer' => '../complianceofficer/dashboard.php',
        'complianceofficer' => '../complianceofficer/dashboard.php',
        
        // LEVEL 4: Departmental Operations
        'departmenthead' => '../departmenthead/dashboard.php',
        
        // LEVEL 6: Requestors / Staff
        'cashier' => '../cashier/dashboard.php',
        
        'end-user' => '../students/dashboard.php',
        'staff' => '../students/dashboard.php'
    ];

    // Check if role exists in routing table
    if (!isset($roleRoutes[$roleKey])) {
        // If role not found, use level-based fallback
        $userLevel = $user['level'] ?? 6;
        
        // PERFECT level mapping
        switch($userLevel) {
            case 1: // System Core
                if (strpos(strtolower($user['role']), 'super') !== false) {
                    header("Location: ../superadmin/dashboard.php");
                } else {
                    header("Location: ../admin/dashboard.php");
                }
                break;
                
            case 2: // Financial Oversight
                header("Location: ../financialdirector/dashboard.php");
                break;
                
            case 3: // Operational Management
                $roleLower = strtolower($user['role']);
                if (strpos($roleLower, 'budget') !== false) {
                    header("Location: ../budgetofficer/dashboard.php");
                } elseif (strpos($roleLower, 'account') !== false) {
                    header("Location: ../accountant/dashboard.php");
                } elseif (strpos($roleLower, 'treasurer') !== false) {
                    header("Location: ../treasurer/dashboard.php");
                } elseif (strpos($roleLower, 'procure') !== false) {
                    header("Location: ../procurementofficer/dashboard.php");
                } elseif (strpos($roleLower, 'asset') !== false) {
                    header("Location: ../assetmanagementofficer/dashboard.php");
                } else {
                    header("Location: ../accountant/dashboard.php");
                }
                break;
                
            case 4: // Departmental Operations
                header("Location: ../departmenthead/dashboard.php");
                break;
                
            case 5: // Monitoring & Verification
                $roleLower = strtolower($user['role']);
                if (strpos($roleLower, 'audit') !== false) {
                    header("Location: ../auditor/dashboard.php");
                } else {
                    header("Location: ../complianceofficer/dashboard.php");
                }
                break;
                
            case 6: // Requestors / Staff
            default:
                $roleLower = strtolower($user['role']);
                if (strpos($roleLower, 'cashier') !== false) {
                    header("Location: ../cashier/dashboard.php");
                } else {
                    header("Location: ../students/dashboard.php");
                }
                break;
        }
        exit();
    }

    // Redirect to specific role dashboard
    header("Location: " . $roleRoutes[$roleKey]);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | Financial Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="icon" type="image/x-icon" href="../assets/bcpnobg.png">
    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1E293B',
                        accent: '#059669',
                        success: '#22C55E',
                        danger: '#EF4444',
                        warning: '#F59E0B',
                        info: '#3B82F6',
                        'accent-light': '#D1FAE5',
                        'gray-150': '#F3F4F6'
                    },
                    fontFamily: {
                        inter: ['Inter', 'system-ui', 'sans-serif']
                    }
                }
            }
        }
    </script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { 
            font-family: 'Inter', sans-serif;
            background-image:url('../assets/bcplp.jpg');
            background-size:cover;
            background-position: center;
            background-repeat: no-repeat;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center">

<div class="w-full max-w-md bg-white rounded-xl shadow-lg px-10 py-12">
    <div class="flex justify-center mb-6">
        <img src="../assets/bcpnobg.png" class="h-14" alt="BCP Logo">
    </div>

    <h2 class="text-2xl font-semibold text-gray-900 text-center">Login</h2>
    <p class="text-sm text-gray-500 text-center mt-2 mb-8">Enter your credentials to access the system.</p>

    <?php if(isset($_GET['error'])): ?>
        <p class="text-sm text-center text-danger mb-4">
            <?php
                if($_GET['error'] === 'invalid') echo "Invalid username or password.";
                elseif($_GET['error'] === 'unauthorized') echo "You are not authorized to access this system.";
            ?>
        </p>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
            <input type="text" name="username" required placeholder="Enter username"
                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-accent">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
            <input type="password" name="password" required placeholder="Enter password"
                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-accent">
        </div>

        <button type="submit"
                class="w-full bg-info text-white py-2.5 rounded-lg font-semibold hover:bg-blue-700 transition">
            Login
        </button>
    </form>

    <div class="text-center mt-6">
        <a href="forgotpassword.php" class="text-sm text-primary hover:underline">Forgot Password?</a>
    </div>
</div>

</body>
</html>