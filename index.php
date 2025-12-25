<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Innovative Financial Management System</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: '#0F172A',
            accent: '#2563EB'
          }
        }
      }
    }
  </script>
  <style>
    .dropdown-menu {
      opacity: 0;
      visibility: hidden;
      transition: opacity 0.3s ease-in-out;
      z-index: 50;
    }

    .group:hover .dropdown-menu,
    .dropdown-menu.show {
      opacity: 1;
      visibility: visible;
    }
  </style>
</head>
<body class="bg-gray-50 text-gray-800">

  <nav class="bg-white shadow-lg">
    <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
      <div class="flex items-center space-x-4">
        <img src="assets/bcpnobg.png" alt="School Logo" class="h-10 w-auto">
        <span class="text-xl font-bold text-primary">BCP</span>
      </div>
      <ul class="hidden md:flex space-x-8 text-sm font-medium">
        <li><a href="#" class="hover:text-accent">Home</a></li>
        <li class="relative group">
          <a href="#features" class="hover:text-accent">Features</a>
          <div class="absolute left-0 mt-2 w-56 bg-white text-gray-800 rounded-xl shadow-lg dropdown-menu">
            <a href="#" class="block px-4 py-3 hover:bg-gray-100">Smart Budget Planning</a>
            <a href="#" class="block px-4 py-3 hover:bg-gray-100">Real-Time Financial Tracking</a>
            <a href="#" class="block px-4 py-3 hover:bg-gray-100">Automated Approvals</a>
            <a href="#" class="block px-4 py-3 hover:bg-gray-100">Audit & Compliance Tools</a>
          </div>
        </li>
        <li class="relative group">
          <a href="#services" class="hover:text-accent">Services</a>
          <div class="absolute left-0 mt-2 w-56 bg-white text-gray-800 rounded-xl shadow-lg dropdown-menu">
            <a href="#" class="block px-4 py-3 hover:bg-gray-100">Financial Oversight</a>
            <a href="#" class="block px-4 py-3 hover:bg-gray-100">Departmental Budgeting</a>
            <a href="#" class="block px-4 py-3 hover:bg-gray-100">Procurement & Disbursement</a>
            <a href="#" class="block px-4 py-3 hover:bg-gray-100">Asset & Inventory Control</a>
          </div>
        </li>
        <li><a href="#compliance" class="hover:text-accent">Compliance</a></li>
        <li><a href="#" class="hover:text-accent">Contact</a></li>
      </ul>
      <div class="space-x-4">
        <a href="auth/login.php" class="text-sm hover:text-accent">Login</a>
      </div>
    </div>
  </nav>

  <header class="relative h-screen">
    <img src="assets/bcplp.jpg" alt="Campus Background" class="absolute inset-0 w-full h-full object-cover brightness-75">
    <div class="relative z-10 max-w-7xl mx-auto px-6 py-32 text-center text-white">
      <h1 class="text-4xl md:text-5xl font-bold mb-6">Innovative Financial Management System</h1>
      <p class="text-lg md:text-xl mb-8">A secure, transparent, and intelligent platform designed for modern School Finance Departments</p>
      <a href="#" class="bg-accent hover:bg-blue-700 px-8 py-3 rounded-lg font-semibold">Get Started</a>
    </div>
  </header>

  <section id="features" class="max-w-7xl mx-auto px-6 py-20">
    <h2 class="text-3xl font-bold text-center mb-12">Features</h2>
    <div class="grid md:grid-cols-3 gap-10">
      <div class="bg-white p-8 rounded-2xl shadow">
        <h3 class="text-xl font-semibold mb-3">Strategic Budget Planning</h3>
        <p>Prepare, review, and approve institutional budgets with multi-level approvals and real-time fund allocation tracking.</p>
      </div>
      <div class="bg-white p-8 rounded-2xl shadow">
        <h3 class="text-xl font-semibold mb-3">Revenue & Expense Transparency</h3>
        <p>Track tuition, grants, expenses, payables, and receivables with complete financial visibility and accuracy.</p>
      </div>
      <div class="bg-white p-8 rounded-2xl shadow">
        <h3 class="text-xl font-semibold mb-3">Audit & Compliance Ready</h3>
        <p>Built-in audit trails and compliance reports aligned with CHED, COA, and institutional standards.</p>
      </div>
    </div>
  </section>

  <footer class="bg-primary text-white">
    <div class="max-w-7xl mx-auto px-6 py-10 text-center">
      <p class="text-sm">© 2025 Financial Management System for School Management Systems</p>
      <p class="text-xs mt-2">Secure • Transparent • Innovative</p>
    </div>
  </footer>

  <script>
    document.querySelectorAll('.group').forEach(item => {
      const dropdown = item.querySelector('.dropdown-menu');
      let timeout;

      item.addEventListener('mouseenter', () => {
        clearTimeout(timeout);
        dropdown.classList.add('show');
      });

      item.addEventListener('mouseleave', () => {
        timeout = setTimeout(() => {
          dropdown.classList.remove('show');
        }, 600); // keeps dropdown visible for 0.6 seconds
      });
    });
  </script>

</body>
</html>
