<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - Financial Management System</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: '#0F172A',
            accent: '#2563EB',
            secondary: '#A78BFA'
          }
        }
      }
    }
  </script>
</head>
<body class="min-h-screen flex">

  <!-- Left Panel -->
  <div class="hidden lg:flex w-1/2 bg-cover bg-center relative" style="background-image: url('../assets/bcplp.jpg');">
    <div class="absolute inset-0 bg-black/50 flex items-center justify-center">
      <div class="text-center text-white px-10">
        <h1 class="text-4xl font-bold mb-4">Welcome to BCP Financial Management</h1>
        <p class="text-lg">Secure, transparent, and intelligent platform designed for modern School Finance Departments</p>
      </div>
    </div>
  </div>

  <!-- Right Panel - Login Form -->
  <div class="w-full lg:w-1/2 flex items-center justify-center bg-gray-50">
    <div class="w-full max-w-md p-10 bg-white rounded-2xl shadow-2xl">
      <!-- Logo -->
      <div class="flex justify-center mb-6">
        <img src="../assets/bcpnobg.png" alt="School Logo" class="h-14 w-auto">
      </div>
      <h2 class="text-2xl font-bold text-primary text-center mb-6">Login</h2>

      <!-- Form -->
      <form id="loginForm" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
          <input type="text" required
                 class="w-full px-4 py-2 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-accent">
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
          <input type="password" required
                 class="w-full px-4 py-2 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-accent">
        </div>

        <div class="flex items-center justify-between text-sm text-gray-700">
          <label class="flex items-center">
            <input type="checkbox" class="mr-2 accent-accent"> Remember me
          </label>
          <a href="#" class="text-accent hover:underline">Forgot password?</a>
        </div>

        <button type="submit" class="w-full py-2 bg-accent text-white rounded-xl hover:bg-blue-700 transition">
          Login
        </button>
      </form>

      <p class="text-center text-sm text-gray-500 mt-4">
        Don't have an account? <a href="#" class="text-accent hover:underline">Create Account</a>
      </p>
    </div>
  </div>

  <script>
    document.getElementById("loginForm").addEventListener("submit", function(e) {
      e.preventDefault();
      alert("This is a demo login.");
    });
  </script>

</body>
</html>
