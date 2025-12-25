<?php
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard - Financial Management System</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: '#1E293B',
            accent: '#2563EB',
            success: '#22C55E',
            danger: '#EF4444',
            warning: '#F59E0B',
            navbar: '#4750DD'
          }
        }
      }
    }
  </script>
</head>

<body class="bg-gray-100 h-screen overflow-hidden">

<header class="bg-navbar shadow-sm h-16 flex items-center justify-between px-6 fixed top-0 left-0 right-0 z-20 text-white">
  <div class="flex items-center gap-3">
    <img src="../assets/bcpnobg.png" class="h-8">
    <span class="font-bold text-lg">BCP Financial Management</span>
  </div>
    </button>
    <div class="flex items-center gap-2">
      <img src="https://i.pravatar.cc/40" class="h-8 w-8 rounded-full">
      <span class="font-medium">Administrator</span>
    </div>
  </div>
</header>

<div class="flex pt-16 h-full">

<?php include 'sidebar.php'; ?>

  <main class="flex-1 overflow-y-auto p-6 space-y-6">

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

      <div class="bg-white rounded-xl shadow p-5 border-l-4 border-accent">
        <p class="text-sm text-gray-500">Total Budget</p>
        <p class="text-2xl font-bold">₱12.5M</p>
        <p class="text-sm text-success mt-1">12%</p>
      </div>

      <div class="bg-white rounded-xl shadow p-5 border-l-4 border-success">
        <p class="text-sm text-gray-500">Revenue</p>
        <p class="text-2xl font-bold">₱4.35M</p>
        <p class="text-sm text-success mt-1">8%</p>
      </div>

      <div class="bg-white rounded-xl shadow p-5 border-l-4 border-danger">
        <p class="text-sm text-gray-500">Expenses</p>
        <p class="text-2xl font-bold">₱3.2M</p>
        <p class="text-sm text-danger mt-1">5%</p>
      </div>

      <div class="bg-white rounded-xl shadow p-5 border-l-4 border-warning">
        <p class="text-sm text-gray-500">Pending Approvals</p>
        <p class="text-2xl font-bold">5</p>
        <p class="text-sm text-warning mt-1">Needs review</p>
      </div>

    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

      <div class="bg-white rounded-xl shadow p-6 lg:col-span-2">
        <h3 class="font-semibold mb-4">Financial Overview</h3>
        <canvas id="financeChart" height="120"></canvas>
      </div>

      <div class="bg-white rounded-xl shadow p-6 flex flex-col items-center justify-center">
        <h3 class="font-semibold mb-4">Spending Target</h3>
        <canvas id="donutChart" width="180" height="180"></canvas>
        <p class="mt-3 text-sm text-warning font-semibold">32% Used</p>
      </div>

    </div>

  </main>
</div>

<footer class="bg-white border-t text-center py-4 text-xs text-gray-500">
  © 2025 Financial Management System
</footer>

<script>
  new Chart(document.getElementById('financeChart'), {
    type: 'bar',
    data: {
      labels: ['Jan','Feb','Mar','Apr','May','Jun'],
      datasets: [{
        label: 'Revenue',
        data: [800, 950, 1100, 900, 1200, 1300],
        backgroundColor: '#2563EB'
      },{
        label: 'Expenses',
        data: [600, 700, 750, 650, 800, 850],
        backgroundColor: '#EF4444'
      }]
    }
  });

  new Chart(document.getElementById('donutChart'), {
    type: 'doughnut',
    data: {
      labels: ['Used', 'Remaining'],
      datasets: [{
        data: [32, 68],
        backgroundColor: ['#F59E0B', '#E5E7EB']
      }]
    },
    options: {
      cutout: '70%'
    }
  });
</script>

</body>
</html>
