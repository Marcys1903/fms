<?php
session_start();
require_once '../include/db_config.php';

// Database connection class
class Database {
    private $conn;

    public function __construct() {
        global $db_config;
        try {
            $this->conn = new PDO(
                "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset=utf8mb4",
                $db_config['username'],
                $db_config['password']
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->conn;
    }
}

// User class
class User {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function login($username, $password) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE (username = :username OR email = :email) LIMIT 1");
        $stmt->execute([
            ':username' => $username,
            ':email' => $username
        ]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['password'] === $password) { // Plain-text password
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            return true;
        }
        return false;
    }

    public static function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    public static function logout() {
        session_destroy();
        header('Location: login.php');
        exit();
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    User::logout();
}

// Handle login form submission
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['password'])) {
    $db = new Database();
    $user = new User($db->getConnection());

    if ($user->login($_POST['username'], $_POST['password'])) {
        // Redirect based on role
        switch ($_SESSION['role']) {
            case 'admin':
                header('Location: ../admin/dashboard.php');
                break;
            case 'faculty':
                header('Location: ../faculty/dashboard.php');
                break;
            case 'staff':
                header('Location: ../staff/dashboard.php');
                break;
            case 'student':
                header('Location: ../student/dashboard.php');
                break;
            default:
                header('Location: ../dashboard.php');
        }
        exit();
    } else {
        $login_error = 'Invalid username or password. Please try again.';
    }
}

// Redirect if already logged in
if (User::isLoggedIn()) {
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: ../admin/dashboard.php');
            break;
        case 'faculty':
            header('Location: ../faculty/dashboard.php');
            break;
        case 'staff':
            header('Location: ../staff/dashboard.php');
            break;
        case 'student':
            header('Location: ../student/dashboard.php');
            break;
        default:
            header('Location: ../dashboard.php');
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign In | BCP Financial Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>
<body class="bg-gradient-to-r from-blue-900 to-blue-700 text-white">

<main class="h-screen w-full flex flex-col md:flex-row items-center justify-center px-6 md:px-20 gap-10">

    <!-- LEFT SIDE (INFO) -->
    <section class="flex-1 flex flex-col justify-center max-h-full overflow-hidden">
        <h1 class="text-3xl md:text-4xl font-bold leading-tight mb-4">
            Secure System Access
        </h1>
        <p class="text-blue-100 text-base md:text-lg leading-relaxed mb-6">
            Access the <strong>BCP Financial Management System</strong> using the credentials provided by the institution. This universal login portal supports students, faculty, staff, and administrators.
        </p>
        <ul class="space-y-3 text-blue-100 text-sm md:text-base">
            <li class="flex items-start gap-2"><span class="text-yellow-400 font-bold">✔</span> Centralized and secure authentication</li>
            <li class="flex items-start gap-2"><span class="text-yellow-400 font-bold">✔</span> Role-based access after login</li>
            <li class="flex items-start gap-2"><span class="text-yellow-400 font-bold">✔</span> Protected financial data and audit trails</li>
            <li class="flex items-start gap-2"><span class="text-yellow-400 font-bold">✔</span> AI-powered system monitoring</li>
        </ul>
    </section>

    <!-- RIGHT SIDE (FORM) -->
    <section class="flex-1 flex flex-col justify-center bg-white/95 text-gray-800 rounded-2xl p-8 md:p-12 shadow-2xl max-h-full overflow-y-auto">
        <h2 class="text-2xl font-bold text-blue-800 mb-2">Sign In</h2>
        <p class="text-sm text-gray-600 mb-6">Enter your assigned username and password</p>

        <?php if (!empty($login_error)): ?>
            <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-xl">
                <?php echo htmlspecialchars($login_error); ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-5">
            <div>
                <label class="block text-sm font-medium mb-1">Email or Username</label>
                <input type="text" name="username" required placeholder="e.g. bcp-user01" class="w-full px-4 py-3 border rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-600">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Password</label>
                <input type="password" name="password" required placeholder="••••••••" class="w-full px-4 py-3 border rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-600">
            </div>
            <button type="submit" class="w-full bg-yellow-400 text-black py-3 rounded-xl font-semibold hover:bg-yellow-300 transition shadow-md">Access System</button>
        </form>

        <div class="mt-6 text-sm text-center text-gray-600">
            <p>Unable to sign in?</p>
            <a href="mailto:admin@bcp.edu.ph" class="font-semibold text-blue-700 hover:underline">Contact System Administrator</a>
        </div>

        <div class="mt-6 bg-blue-50 p-3 rounded-xl text-sm text-blue-900">
            <p class="font-semibold mb-1">Important Notice</p>
            <p>User accounts are created and managed by the institution. If you do not have login credentials, please coordinate with your department or system administrator.</p>
        </div>
    </section>

</main>

</body>
</html>
