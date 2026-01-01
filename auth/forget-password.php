<?php
session_start();
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Generate unique token
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Store token in database
        $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$user['id'], $token, $expires_at]);
        
        // In a real application, you would send an email here
        // For demo purposes, we'll show a success message
        $_SESSION['reset_token'] = $token;
        $success = true;
    } else {
        $error = "No account found with that email address.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password | Financial Management System</title>
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
    body { font-family: 'Inter', sans-serif; }
  </style>
</head>

<body class="min-h-screen bg-gray-50 flex items-center justify-center">

  <div class="w-full max-w-md bg-white rounded-xl shadow-lg px-10 py-12">

    <div class="flex justify-center mb-6">
      <img src="../assets/bcpnobg.png" class="h-14" alt="BCP Logo">
    </div>

    <h2 class="text-2xl font-semibold text-gray-900 text-center">
      Forgot Password
    </h2>
    <p class="text-sm text-gray-500 text-center mt-2 mb-8">
      Enter your registered email to receive a password reset link.
    </p>

    <?php if(isset($error)): ?>
      <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-lg text-sm">
        <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>

    <?php if(isset($success)): ?>
      <div class="mb-4 p-3 bg-green-50 text-green-700 rounded-lg text-sm">
        Password reset link has been sent to your email!
        <?php if(isset($_SESSION['reset_token'])): ?>
          <div class="mt-2 text-xs">
            <strong>Demo Token:</strong> <?php echo $_SESSION['reset_token']; ?><br>
            <a href="reset_password.php?token=<?php echo $_SESSION['reset_token']; ?>" 
               class="underline">Click here to reset password</a>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <div>
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
          Email Address
        </label>
        <input
          type="email"
          name="email"
          id="email"
          required
          placeholder="you@example.com"
          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-accent"
        >
      </div>

      <button
        type="submit"
        class="w-full bg-accent text-white py-2.5 rounded-lg font-semibold hover:bg-green-700 transition"
      >
        Send Reset Link
      </button>
    </form>

    <div class="text-center mt-6">
      <a
        href="login.php"
        class="text-sm text-accent hover:underline"
      >
        ← Back to Login
      </a>
    </div>

  </div>

</body>
</html>