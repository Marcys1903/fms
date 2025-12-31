<?php
session_start();
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        $role = strtolower(str_replace(' ', '', $user['role']));

        switch ($role) {
            case 'superadmin':
                $_SESSION['level'] = 1;
                header("Location: superadmin/dashboard.php");
                exit();
            case 'financialdirector':
                $_SESSION['level'] = 2;
                header("Location: financialdirector/dashboard.php");
                exit();
            case 'budgetofficer':
                $_SESSION['level'] = 3;
                header("Location: budgetofficer/dashboard.php");
                exit();
            case 'treasurer':
                $_SESSION['level'] = 3;
                header("Location: treasurer/dashboard.php");
                exit();
            case 'accountingofficer':
            case 'cashier':
                $_SESSION['level'] = 3;
                header("Location: cashier/dashboard.php");
                exit();
            case 'departmenthead':
                $_SESSION['level'] = 4;
                header("Location: departmenthead/dashboard.php");
                exit();
            case 'procurementofficer':
                $_SESSION['level'] = 4;
                header("Location: procurementofficer/dashboard.php");
                exit();
            case 'assetmanagementofficer':
                $_SESSION['level'] = 4;
                header("Location: assetmanagementofficer/dashboard.php");
                exit();
            case 'auditor':
                $_SESSION['level'] = 5;
                header("Location: auditor/dashboard.php");
                exit();
            case 'complianceofficer':
                $_SESSION['level'] = 5;
                header("Location: complianceofficer/dashboard.php");
                exit();
            case 'students':
            case 'enduser':
            case 'staff':
                $_SESSION['level'] = 6;
                header("Location: students/dashboard.php");
                exit();
            default:
                session_destroy();
                header("Location: login.php?error=unauthorized");
                exit();
        }

    } else {
        header("Location: login.php?error=invalid");
        exit();
    }
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
        body { font-family: 'Inter', sans-serif;
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
