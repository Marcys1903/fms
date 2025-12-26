<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password | Financial Management System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="../assets/bcpnobg.png">

  <!-- Tailwind -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Tailwind Config (SAME AS LOGIN) -->
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

  <!-- Font -->
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
    body { font-family: 'Inter', sans-serif; }
  </style>
</head>

<body class="min-h-screen bg-gray-50 flex items-center justify-center">

  <!-- Card -->
  <div class="w-full max-w-md bg-white rounded-xl shadow-lg px-10 py-12">

    <!-- Logo -->
    <div class="flex justify-center mb-6">
      <img src="../assets/bcpnobg.png" class="h-14" alt="BCP Logo">
    </div>

    <!-- Header -->
    <h2 class="text-2xl font-semibold text-gray-900 text-center">
      Forgot Password
    </h2>
    <p class="text-sm text-gray-500 text-center mt-2 mb-8">
      Enter your registered email to receive a password reset link.
    </p>

    <!-- Form -->
    <form id="resetForm" class="space-y-4">

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Email Address
        </label>
        <input
          type="email"
          required
          placeholder="you@example.com"
          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg
                 focus:outline-none focus:ring-2 focus:ring-accent"
        >
      </div>

      <button
        type="submit"
        class="w-full bg-accent text-white py-2.5 rounded-lg
               font-semibold hover:bg-green-700 transition"
      >
        Send Reset Link
      </button>
    </form>

    <!-- Success Message -->
    <p
      id="successMsg"
      class="hidden text-sm text-center mt-4 text-success"
    >
      Password reset link sent successfully!
    </p>

    <!-- Footer -->
    <div class="text-center mt-6">
      <a
        href="login.php"
        class="text-sm text-accent hover:underline"
      >
        ← Back to Login
      </a>
    </div>

  </div>

  <!-- Demo Script -->
  <script>
    document.getElementById("resetForm").addEventListener("submit", function(e) {
      e.preventDefault();
      document.getElementById("successMsg").classList.remove("hidden");
    });
  </script>

</body>
</html>
