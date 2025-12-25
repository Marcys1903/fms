<?php
session_start();
// Add authentication check here
// if (!isset($_SESSION['treasurer'])) {
//     header('Location: login.php');
//     exit();
// }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Treasury Dashboard | Treasurer | Financial Management System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="../assets/bcpnobg.png">
  
  <!-- CDN Links -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/luxon@3.4.4"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@1.3.1"></script>
  <script src="//unpkg.com/alpinejs" defer></script>

  <!-- Tailwind Configuration -->
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: '#1E293B',
            accent: '#0F766E', // Teal for treasury
            success: '#059669', // Green for positive balances
            danger: '#DC2626', // Red for negative indicators
            warning: '#D97706', // Amber for warnings
            info: '#2563EB', // Blue for investments
            navbar: '#0D9488',
            sidebar: '#134E4A',
            'cash': '#059669',
            'investment': '#2563EB',
            'receivable': '#7C3AED',
            'payable': '#D97706',
            'reserve': '#0F766E',
            'accent-light': '#CCFBF1',
            'success-light': '#D1FAE5',
            'danger-light': '#FEE2E2',
            'warning-light': '#FEF3C7',
            'info-light': '#DBEAFE',
            'treasury-teal': '#0F766E',
            'treasury-blue': '#2563EB',
            'treasury-green': '#059669',
            'treasury-amber': '#D97706'
          },
          fontFamily: {
            'inter': ['Inter', 'system-ui', 'sans-serif']
          },
          animation: {
            'fade-in': 'fadeIn 0.2s ease-in-out',
            'slide-up': 'slideUp 0.3s ease-out',
            'pulse-slow': 'pulse 3s infinite'
          },
          keyframes: {
            fadeIn: {
              '0%': { opacity: '0', transform: 'translateY(-10px)' },
              '100%': { opacity: '1', transform: 'translateY(0)' }
            },
            slideUp: {
              '0%': { transform: 'translateY(10px)', opacity: '0' },
              '100%': { transform: 'translateY(0)', opacity: '1' }
            }
          }
        }
      }
    }
  </script>
  
  <!-- Custom Styles -->
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    
    body {
      font-family: 'Inter', sans-serif;
    }
    
    .stat-card {
      transition: all 0.2s ease;
    }
    
    .stat-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
    }
    
    /* Custom scrollbar */
    ::-webkit-scrollbar {
      width: 6px;
    }
    
    ::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 3px;
    }
    
    ::-webkit-scrollbar-thumb {
      background: #c1c1c1;
      border-radius: 3px;
    }
    
    ::-webkit-scrollbar-thumb:hover {
      background: #a1a1a1;
    }
    
    /* Table styles */
    .table-striped tbody tr:nth-child(odd) {
      background-color: #f9fafb;
    }
    
    /* Chart container */
    .chart-container {
      position: relative;
      height: 280px;
      width: 100%;
    }
    
    /* Smooth transitions */
    .transition-smooth {
      transition: all 0.3s ease;
    }
    
    /* Dropdown animations */
    .dropdown {
      opacity: 0;
      visibility: hidden;
      transform: translateY(-10px);
      transition: all 0.2s ease;
    }
    
    .dropdown-container:hover .dropdown {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }
    
    .dropdown-item:hover {
      background-color: #f9fafb;
    }
    
    /* Treasury status indicators */
    .status-positive { background-color: #D1FAE5; color: #059669; }
    .status-negative { background-color: #FEE2E2; color: #DC2626; }
    .status-warning { background-color: #FEF3C7; color: #D97706; }
    .status-neutral { background-color: #E0E7FF; color: #4F46E5; }
    
    /* Metric trends */
    .trend-up::after {
      content: '↗';
      margin-left: 4px;
      color: #059669;
    }
    
    .trend-down::after {
      content: '↘';
      margin-left: 4px;
      color: #DC2626;
    }
    
    .trend-neutral::after {
      content: '→';
      margin-left: 4px;
      color: #6B7280;
    }
    
    /* Fund status colors */
    .fund-operating { border-left-color: #2563EB; }
    .fund-reserve { border-left-color: #0F766E; }
    .fund-endowment { border-left-color: #7C3AED; }
    .fund-capital { border-left-color: #D97706; }
    .fund-special { border-left-color: #EC4899; }
  </style>
</head>

<body class="bg-gray-50 h-screen overflow-hidden font-inter">

<!-- Header -->
<header class="bg-white shadow-sm h-16 flex items-center justify-between px-6 fixed top-0 left-0 right-0 z-30 border-b border-gray-200">
  <div class="flex items-center gap-3">
    <div class="flex items-center gap-2">
      <img src="../assets/bcpnobg.png" class="h-8 w-8" alt="BCP Logo">
      <div>
        <span class="font-bold text-gray-900 text-lg">Treasury Dashboard</span>
        <span class="ml-2 text-xs bg-accent text-white px-2 py-0.5 rounded-full font-semibold">TREASURER</span>
      </div>
    </div>
  </div>
  
  <div class="flex items-center gap-4">
    <!-- Treasury Period -->
    <div class="flex items-center gap-2 px-3 py-1 bg-teal-50 rounded-lg border border-teal-200">
      <svg class="w-3 h-3 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
      </svg>
      <span class="text-xs font-medium text-teal-700">Fiscal Year: 2023-2024 | Month: October</span>
    </div>
    
    <!-- Cash Position Alert -->
    <div class="flex items-center gap-2 px-3 py-1 bg-green-50 rounded-lg border border-green-200">
      <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <span class="text-xs font-medium text-green-700">Cash Reserve: 45 days coverage</span>
    </div>
    
    <!-- Treasury Alerts -->
    <div class="relative">
      <button class="p-2 rounded-full hover:bg-gray-100 relative">
        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        <span class="absolute top-1 right-1 w-2 h-2 bg-danger rounded-full"></span>
      </button>
      
      <!-- Treasury Alerts Dropdown -->
      <div class="dropdown absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl border border-gray-200 py-2 z-40">
        <div class="px-4 py-3 border-b border-gray-100">
          <div class="flex items-center justify-between">
            <h3 class="font-semibold text-gray-900 text-sm">Treasury Alerts</h3>
            <span class="text-xs bg-danger text-white px-2 py-0.5 rounded-full">2 critical</span>
          </div>
        </div>
        
        <div class="max-h-80 overflow-y-auto">
          <a href="#" class="dropdown-item block px-4 py-3 border-b border-gray-100 hover:bg-gray-50 transition-smooth">
            <div class="flex items-start gap-3">
              <div class="w-8 h-8 bg-danger-light rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg class="w-4 h-4 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.252 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900">Large Payment Due</p>
                <p class="text-xs text-gray-500 mt-0.5">₱2.5M vendor payment due in 2 days</p>
                <span class="text-xs text-gray-400 mt-1 block">4 hours ago</span>
              </div>
            </div>
          </a>
          
          <a href="#" class="dropdown-item block px-4 py-3 border-b border-gray-100 hover:bg-gray-50 transition-smooth">
            <div class="flex items-start gap-3">
              <div class="w-8 h-8 bg-warning-light rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg class="w-4 h-4 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900">Investment Maturity</p>
                <p class="text-xs text-gray-500 mt-0.5">₱5M T-bill maturing next week</p>
                <span class="text-xs text-gray-400 mt-1 block">1 day ago</span>
              </div>
            </div>
          </a>
          
          <a href="#" class="dropdown-item block px-4 py-3 border-b border-gray-100 hover:bg-gray-50 transition-smooth">
            <div class="flex items-start gap-3">
              <div class="w-8 h-8 bg-success-light rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg class="w-4 h-4 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900">Tuition Collection Update</p>
                <p class="text-xs text-gray-500 mt-0.5">Q3 collections at 94.5% of target</p>
                <span class="text-xs text-gray-400 mt-1 block">2 days ago</span>
              </div>
            </div>
          </a>
        </div>
        
        <div class="border-t border-gray-100 pt-2">
          <a href="#" class="block text-center text-sm text-accent font-medium py-2 hover:bg-gray-50 rounded-b-lg transition-smooth">
            View All Treasury Reports
          </a>
        </div>
      </div>
    </div>
    
    <!-- User Profile -->
    <div class="dropdown-container relative">
      <div class="flex items-center gap-3 border-l border-gray-200 pl-4 cursor-pointer hover:bg-gray-50 px-2 py-1 rounded-lg transition-smooth">
        <div class="text-right">
          <p class="font-medium text-gray-900 text-sm">Roberto Lim</p>
          <p class="text-xs text-gray-500">Treasurer</p>
        </div>
        <div class="relative">
          <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Treasurer" class="h-9 w-9 rounded-full border-2 border-accent">
          <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-success rounded-full border-2 border-white"></div>
        </div>
      </div>
      
      <!-- Treasurer Menu -->
      <div class="dropdown absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 py-2 z-40">
        <div class="px-4 py-2 border-b border-gray-100">
          <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Treasury Management</p>
        </div>
        
        <a href="#" class="dropdown-item flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-smooth">
          <div class="w-5 h-5 text-gray-500">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
          </div>
          <span class="text-sm text-gray-700">Cash Flow Reports</span>
        </a>
        
        <a href="#" class="dropdown-item flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-smooth">
          <div class="w-5 h-5 text-gray-500">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
            </svg>
          </div>
          <span class="text-sm text-gray-700">Investment Portfolio</span>
        </a>
        
        <a href="#" class="dropdown-item flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-smooth">
          <div class="w-5 h-5 text-gray-500">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
          </div>
          <span class="text-sm text-gray-700">Fund Management</span>
        </a>
        
        <div class="border-t border-gray-100 pt-2">
          <a href="auth/logout.php" class="flex items-center gap-3 px-4 py-3 hover:bg-danger/10 text-danger transition-smooth">
            <div class="w-5 h-5">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
              </svg>
            </div>
            <span class="text-sm font-medium">Sign Out</span>
          </a>
        </div>
      </div>
    </div>
  </div>
</header>

<!-- Main Layout -->
<div class="flex pt-16 h-full">
  <!-- Treasury Sidebar -->
  <?php include 'sidebar.php'; ?>

  <!-- Main Content -->
  <main class="flex-1 overflow-y-auto p-6 space-y-6 bg-gray-50">
    
    <!-- Key Treasury Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-success stat-card">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Cash & Equivalents</p>
            <p class="text-2xl font-bold mt-1">₱45.8M</p>
          </div>
          <div class="p-2 bg-success-light rounded-lg">
            <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
          </div>
        </div>
        <p class="text-xs success font-medium trend-up">+₱2.3M from last month</p>
      </div>

      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-info stat-card">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Investment Portfolio</p>
            <p class="text-2xl font-bold mt-1">₱128.5M</p>
          </div>
          <div class="p-2 bg-info-light rounded-lg">
            <svg class="w-5 h-5 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
            </svg>
          </div>
        </div>
        <p class="text-xs success font-medium trend-up">YTD Return: +8.2%</p>
      </div>

      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-accent stat-card">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Monthly Cash Flow</p>
            <p class="text-2xl font-bold mt-1">+₱3.2M</p>
          </div>
          <div class="p-2 bg-accent-light rounded-lg">
            <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
          </div>
        </div>
        <p class="text-xs success font-medium trend-up">Positive for 8 months</p>
      </div>

      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-warning stat-card">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Days Cash on Hand</p>
            <p class="text-2xl font-bold mt-1">45</p>
          </div>
          <div class="p-2 bg-warning-light rounded-lg">
            <svg class="w-5 h-5 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
        </div>
        <p class="text-xs success font-medium">Above 30-day target</p>
      </div>
    </div>

    <!-- Treasury Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      
      <!-- Cash Flow Analysis -->
      <div class="bg-white rounded-lg shadow p-5 lg:col-span-2">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-5">
          <div>
            <h3 class="font-semibold text-gray-900 text-base">Cash Flow Analysis</h3>
            <p class="text-sm text-gray-500">Monthly inflows vs outflows (in millions)</p>
          </div>
          <select class="text-xs border border-gray-300 rounded-lg px-3 py-1.5 bg-white mt-2 sm:mt-0">
            <option selected>Last 6 Months</option>
            <option>Current Fiscal Year</option>
            <option>Quarterly View</option>
          </select>
        </div>
        <div class="chart-container">
          <canvas id="cashFlowChart"></canvas>
        </div>
      </div>

      <!-- Fund Allocation -->
      <div class="bg-white rounded-lg shadow p-5">
        <h3 class="font-semibold text-gray-900 text-base mb-5">Fund Allocation</h3>
        <div class="chart-container">
          <canvas id="fundAllocationChart"></canvas>
        </div>
        <div class="mt-4 space-y-3">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-blue-600"></div>
              <span class="text-xs text-gray-600">Operating Fund</span>
            </div>
            <span class="text-xs font-medium">₱85.2M (52%)</span>
          </div>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-teal-600"></div>
              <span class="text-xs text-gray-600">Reserve Fund</span>
            </div>
            <span class="text-xs font-medium">₱45.8M (28%)</span>
          </div>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-purple-600"></div>
              <span class="text-xs text-gray-600">Endowment Fund</span>
            </div>
            <span class="text-xs font-medium">₱25.4M (15%)</span>
          </div>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-amber-600"></div>
              <span class="text-xs text-gray-600">Capital Fund</span>
            </div>
            <span class="text-xs font-medium">₱8.1M (5%)</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Treasury Operations & Alerts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      
      <!-- Upcoming Cash Movements -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-5 border-b border-gray-200">
          <h3 class="font-semibold text-gray-900 text-base">Upcoming Cash Movements</h3>
          <p class="text-sm text-gray-500">Next 30 Days</p>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full min-w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Date</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Description</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Type</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Amount</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Fund</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr class="status-negative">
                <td class="py-3 px-4">
                  <span class="text-sm font-medium text-gray-700">Nov 5</span>
                </td>
                <td class="py-3 px-4">
                  <p class="text-sm font-medium text-gray-900">Vendor Payment - Tech Solutions</p>
                  <p class="text-xs text-gray-500">PO-2023-087</p>
                </td>
                <td class="py-3 px-4">
                  <span class="text-sm font-medium text-gray-700">Outflow</span>
                </td>
                <td class="py-3 px-4">
                  <span class="text-sm font-medium text-danger">₱245,800</span>
                </td>
                <td class="py-3 px-4">
                  <span class="px-2 py-1 text-xs bg-blue-50 text-blue-600 rounded-full">Operating</span>
                </td>
              </tr>
              <tr class="status-positive">
                <td class="py-3 px-4">
                  <span class="text-sm font-medium text-gray-700">Nov 8</span>
                </td>
                <td class="py-3 px-4">
                  <p class="text-sm font-medium text-gray-900">Tuition Collection Batch</p>
                  <p class="text-xs text-gray-500">Regular Programs</p>
                </td>
                <td class="py-3 px-4">
                  <span class="text-sm font-medium text-gray-700">Inflow</span>
                </td>
                <td class="py-3 px-4">
                  <span class="text-sm font-medium text-success">₱8.5M</span>
                </td>
                <td class="py-3 px-4">
                  <span class="px-2 py-1 text-xs bg-blue-50 text-blue-600 rounded-full">Operating</span>
                </td>
              </tr>
              <tr class="status-positive">
                <td class="py-3 px-4">
                  <span class="text-sm font-medium text-gray-700">Nov 15</span>
                </td>
                <td class="py-3 px-4">
                  <p class="text-sm font-medium text-gray-900">Investment Maturity</p>
                  <p class="text-xs text-gray-500">T-bills (91-day)</p>
                </td>
                <td class="py-3 px-4">
                  <span class="text-sm font-medium text-gray-700">Inflow</span>
                </td>
                <td class="py-3 px-4">
                  <span class="text-sm font-medium text-success">₱5.0M</span>
                </td>
                <td class="py-3 px-4">
                  <span class="px-2 py-1 text-xs bg-teal-50 text-teal-600 rounded-full">Reserve</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="p-4 border-t border-gray-200 text-center">
          <a href="#" class="text-sm text-accent font-medium hover:text-teal-700 transition-smooth">View Cash Flow Forecast →</a>
        </div>
      </div>

      <!-- Treasury Alerts & Quick Actions -->
      <div class="bg-white rounded-lg shadow p-5">
        <h3 class="font-semibold text-gray-900 text-base mb-4">Treasury Alerts</h3>
        <div class="space-y-3">
          <div class="p-3 bg-red-50 rounded-lg border-l-4 border-red-500">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-gray-900">Large Outflow Alert</p>
                <p class="text-xs text-gray-500 mt-0.5">₱2.5M payment due in 2 days</p>
              </div>
              <button class="text-xs text-red-600 font-medium hover:text-red-700">Authorize</button>
            </div>
          </div>
          
          <div class="p-3 bg-yellow-50 rounded-lg border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-gray-900">Investment Decision</p>
                <p class="text-xs text-gray-500 mt-0.5">₱5M maturing next week - reinvest?</p>
              </div>
              <button class="text-xs text-yellow-600 font-medium hover:text-yellow-700">Review</button>
            </div>
          </div>
          
          <div class="p-3 bg-green-50 rounded-lg border-l-4 border-green-500">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-gray-900">Reserve Fund Status</p>
                <p class="text-xs text-gray-500 mt-0.5">45 days cash coverage achieved</p>
              </div>
              <button class="text-xs text-green-600 font-medium hover:text-green-700">Details</button>
            </div>
          </div>
        </div>
        
        <!-- Quick Treasury Actions -->
        <div class="mt-6 pt-5 border-t border-gray-200">
          <h4 class="text-sm font-semibold text-gray-900 mb-3">Quick Treasury Actions</h4>
          <div class="grid grid-cols-2 gap-3">
            <a href="#" class="bg-accent text-white p-3 rounded-lg text-center hover:bg-teal-700 transition-smooth">
              <p class="text-xs font-medium">Authorize Payment</p>
            </a>
            <a href="#" class="bg-info text-white p-3 rounded-lg text-center hover:bg-blue-700 transition-smooth">
              <p class="text-xs font-medium">Investment Order</p>
            </a>
          </div>
          <div class="grid grid-cols-2 gap-3 mt-3">
            <a href="#" class="bg-success text-white p-3 rounded-lg text-center hover:bg-green-700 transition-smooth">
              <p class="text-xs font-medium">Cash Position Report</p>
            </a>
            <a href="#" class="bg-gray-100 text-gray-700 p-3 rounded-lg text-center hover:bg-gray-200 transition-smooth">
              <p class="text-xs font-medium">Bank Reconciliation</p>
            </a>
          </div>
        </div>
        
        <!-- Treasury KPIs -->
        <div class="mt-6 pt-5 border-t border-gray-200">
          <h4 class="text-sm font-semibold text-gray-900 mb-3">Treasury KPIs</h4>
          <div class="grid grid-cols-3 gap-2 text-center">
            <div class="bg-blue-50 p-2 rounded">
              <p class="text-lg font-bold text-blue-600">45</p>
              <p class="text-xs text-gray-600">Days Cash</p>
            </div>
            <div class="bg-green-50 p-2 rounded">
              <p class="text-lg font-bold text-green-600">+8.2%</p>
              <p class="text-xs text-gray-600">YTD Return</p>
            </div>
            <div class="bg-teal-50 p-2 rounded">
              <p class="text-lg font-bold text-teal-600">₱3.2M</p>
              <p class="text-xs text-gray-600">Monthly Surplus</p>
            </div>
          </div>
          <p class="text-xs text-gray-500 mt-3 text-center">Liquidity Ratio: 1.8 | Debt Service Coverage: 3.2x</p>
        </div>
      </div>
    </div>
    
    <!-- Fund Management & Performance -->
    <div class="bg-white rounded-lg shadow p-5">
      <h3 class="font-semibold text-gray-900 text-base mb-4">Fund Management & Performance</h3>
      <div class="overflow-x-auto">
        <table class="w-full min-w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Fund</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Current Balance</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Monthly Change</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">YTD Return</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr class="fund-operating">
              <td class="py-2 px-3">
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center">
                    <span class="text-xs font-medium text-blue-600">OP</span>
                  </div>
                  <div>
                    <p class="text-xs font-medium">Operating Fund</p>
                    <p class="text-xs text-gray-500">Daily operations & expenses</p>
                  </div>
                </div>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs font-medium text-gray-700">₱85.2M</span>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs font-bold text-success trend-up">+₱3.2M</span>
                <p class="text-xs text-gray-500">Monthly surplus</p>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs font-medium text-gray-700">N/A</span>
              </td>
              <td class="py-2 px-3">
                <span class="px-2 py-1 text-xs bg-green-50 text-green-600 rounded-full">Healthy</span>
              </td>
            </tr>
            <tr class="fund-reserve">
              <td class="py-2 px-3">
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 bg-teal-100 rounded-full flex items-center justify-center">
                    <span class="text-xs font-medium text-teal-600">RS</span>
                  </div>
                  <div>
                    <p class="text-xs font-medium">Reserve Fund</p>
                    <p class="text-xs text-gray-500">Emergency & contingency</p>
                  </div>
                </div>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs font-medium text-gray-700">₱45.8M</span>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs font-bold text-success trend-up">+₱2.3M</span>
                <p class="text-xs text-gray-500">Monthly contribution</p>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs font-bold text-success trend-up">+5.8%</span>
              </td>
              <td class="py-2 px-3">
                <span class="px-2 py-1 text-xs bg-green-50 text-green-600 rounded-full">Target Met</span>
              </td>
            </tr>
            <tr class="fund-endowment">
              <td class="py-2 px-3">
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 bg-purple-100 rounded-full flex items-center justify-center">
                    <span class="text-xs font-medium text-purple-600">EN</span>
                  </div>
                  <div>
                    <p class="text-xs font-medium">Endowment Fund</p>
                    <p class="text-xs text-gray-500">Long-term investments</p>
                  </div>
                </div>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs font-medium text-gray-700">₱128.5M</span>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs font-bold text-success trend-up">+₱8.2M</span>
                <p class="text-xs text-gray-500">Market appreciation</p>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs font-bold text-success trend-up">+8.2%</span>
              </td>
              <td class="py-2 px-3">
                <span class="px-2 py-1 text-xs bg-green-50 text-green-600 rounded-full">Exceeding</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="mt-4 pt-4 border-t">
        <div class="flex flex-col sm:flex-row justify-between items-center gap-3">
          <p class="text-xs text-gray-500">Total Funds Under Management: ₱259.5M | YTD Growth: +7.8% | Avg. Return: +7.0%</p>
          <a href="#" class="text-xs text-accent font-medium hover:text-teal-700 transition-smooth">
            View Complete Fund Analysis →
          </a>
        </div>
      </div>
    </div>
    
    <!-- Investment Portfolio & Banking -->
    <div class="bg-white rounded-lg shadow p-5">
      <h3 class="font-semibold text-gray-900 text-base mb-4">Investment Portfolio & Banking</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Investment Allocation -->
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-3">Investment Allocation</h4>
          <div class="space-y-3">
            <div class="p-3 bg-blue-50 rounded-lg">
              <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-700">Government Securities</span>
                <span class="text-xs font-bold text-blue-600">₱68.5M (53%)</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                <div class="bg-blue-500 h-2 rounded-full" style="width: 53%"></div>
              </div>
              <p class="text-xs text-blue-600 mt-1">Yield: 5.2% p.a.</p>
            </div>
            
            <div class="p-3 bg-teal-50 rounded-lg">
              <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-700">Corporate Bonds</span>
                <span class="text-xs font-bold text-teal-600">₱32.4M (25%)</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                <div class="bg-teal-500 h-2 rounded-full" style="width: 25%"></div>
              </div>
              <p class="text-xs text-teal-600 mt-1">Yield: 6.8% p.a.</p>
            </div>
            
            <div class="p-3 bg-purple-50 rounded-lg">
              <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-700">Money Market</span>
                <span class="text-xs font-bold text-purple-600">₱27.6M (22%)</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                <div class="bg-purple-500 h-2 rounded-full" style="width: 22%"></div>
              </div>
              <p class="text-xs text-purple-600 mt-1">Yield: 4.5% p.a.</p>
            </div>
          </div>
        </div>
        
        <!-- Banking Relationships -->
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-3">Banking Relationships</h4>
          <div class="space-y-3">
            <div class="p-3 bg-green-50 rounded-lg">
              <p class="text-xs font-medium text-gray-900">BPI - Main Operating</p>
              <p class="text-xs text-gray-500 mt-1">Balance: ₱28.5M</p>
              <p class="text-xs text-green-600 mt-1">Interest: 2.5% p.a.</p>
            </div>
            
            <div class="p-3 bg-yellow-50 rounded-lg">
              <p class="text-xs font-medium text-gray-900">Metrobank - Payroll</p>
              <p class="text-xs text-gray-500 mt-1">Balance: ₱12.3M</p>
              <p class="text-xs text-yellow-600 mt-1">Interest: 2.2% p.a.</p>
            </div>
            
            <div class="p-3 bg-blue-50 rounded-lg">
              <p class="text-xs font-medium text-gray-900">Security Bank - Investments</p>
              <p class="text-xs text-gray-500 mt-1">Custody: ₱128.5M</p>
              <button class="text-xs text-blue-600 font-medium mt-2 hover:text-blue-700">View Statement</button>
            </div>
          </div>
        </div>
        
        <!-- Treasury Compliance -->
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-3">Treasury Compliance</h4>
          <div class="space-y-3">
            <div class="p-3 bg-green-50 rounded-lg">
              <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-700">Investment Policy</span>
                <span class="text-xs font-bold text-green-600">100%</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                <div class="bg-green-500 h-2 rounded-full" style="width: 100%"></div>
              </div>
              <p class="text-xs text-green-600 mt-1">All investments compliant</p>
            </div>
            
            <div class="p-3 bg-red-50 rounded-lg">
              <p class="text-xs font-medium text-gray-900">Audit Recommendations</p>
              <p class="text-xs text-gray-500 mt-1">2 pending actions</p>
              <button class="text-xs text-red-600 font-medium mt-2 hover:text-red-700">Review Now</button>
            </div>
            
            <div class="p-3 bg-gray-100 rounded-lg">
              <p class="text-xs font-medium text-gray-900">Board Reporting</p>
              <p class="text-xs text-gray-500 mt-1">Next report due: Nov 30</p>
              <button class="text-xs text-gray-700 font-medium mt-2 hover:text-gray-900">Prepare Draft</button>
            </div>
          </div>
        </div>
      </div>
      <div class="mt-4 text-center">
        <a href="#" class="text-xs text-accent font-medium hover:text-teal-700 transition-smooth">
          Access Complete Treasury Management Tools →
        </a>
      </div>
    </div>
  </main>
</div>

<!-- Footer -->
<footer class="bg-white border-t py-3">
  <div class="px-6 flex flex-col sm:flex-row justify-between items-center text-xs text-gray-500">
    <div class="mb-2 sm:mb-0">
      <span>© 2024 Treasury Management System v3.5.2</span>
      <span class="mx-2 hidden sm:inline">•</span>
      <span class="block sm:inline mt-1 sm:mt-0 text-success font-medium">Fiscal Month: October 2023 | Last Updated: <?php echo date('Y-m-d H:i:s'); ?></span>
    </div>
    <div class="flex items-center gap-3">
      <a href="#" class="hover:text-accent transition-smooth">Cash Reports</a>
      <a href="#" class="hover:text-accent transition-smooth">Investment Reports</a>
      <a href="#" class="hover:text-accent transition-smooth">Board Reports</a>
    </div>
  </div>
</footer>

<!-- JavaScript -->
<script>
  // Cash Flow Chart
  const cashFlowCtx = document.getElementById('cashFlowChart').getContext('2d');
  const cashFlowChart = new Chart(cashFlowCtx, {
    type: 'bar',
    data: {
      labels: ['May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
      datasets: [
        {
          label: 'Cash Inflows',
          data: [12.5, 13.2, 14.8, 15.3, 16.1, 15.8],
          backgroundColor: '#059669',
          borderColor: '#059669',
          borderWidth: 1,
          borderRadius: 4
        },
        {
          label: 'Cash Outflows',
          data: [11.8, 12.5, 13.2, 14.1, 14.9, 12.6],
          backgroundColor: '#DC2626',
          borderColor: '#DC2626',
          borderWidth: 1,
          borderRadius: 4
        },
        {
          label: 'Net Cash Flow',
          data: [0.7, 0.7, 1.6, 1.2, 1.2, 3.2],
          type: 'line',
          borderColor: '#0F766E',
          backgroundColor: 'transparent',
          borderWidth: 2,
          pointBackgroundColor: '#0F766E',
          pointBorderColor: '#ffffff',
          pointBorderWidth: 2,
          pointRadius: 4
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'top',
          labels: {
            font: {
              size: 11
            }
          }
        },
        tooltip: {
          backgroundColor: 'rgba(0, 0, 0, 0.7)',
          titleColor: '#ffffff',
          bodyColor: '#ffffff',
          borderColor: '#0F766E',
          borderWidth: 1,
          cornerRadius: 4,
          callbacks: {
            label: function(context) {
              let label = context.dataset.label || '';
              if (label) {
                label += ': ';
              }
              if (context.parsed.y !== null) {
                label += '₱' + context.parsed.y + 'M';
              }
              return label;
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: {
            color: 'rgba(0, 0, 0, 0.05)',
            drawBorder: false
          },
          ticks: {
            color: '#6B7280',
            font: {
              size: 11
            },
            callback: function(value) {
              return '₱' + value + 'M';
            }
          }
        },
        x: {
          grid: {
            display: false
          },
          ticks: {
            color: '#6B7280',
            font: {
              size: 11
            }
          }
        }
      }
    }
  });

  // Fund Allocation Chart
  const fundAllocationCtx = document.getElementById('fundAllocationChart').getContext('2d');
  const fundAllocationChart = new Chart(fundAllocationCtx, {
    type: 'doughnut',
    data: {
      labels: ['Operating Fund', 'Reserve Fund', 'Endowment Fund', 'Capital Fund'],
      datasets: [{
        data: [85.2, 45.8, 128.5, 8.1],
        backgroundColor: ['#2563EB', '#0F766E', '#7C3AED', '#D97706'],
        borderColor: '#ffffff',
        borderWidth: 2,
        hoverBorderWidth: 3
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '65%',
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          backgroundColor: 'rgba(0, 0, 0, 0.7)',
          titleColor: '#ffffff',
          bodyColor: '#ffffff',
          borderColor: '#0F766E',
          borderWidth: 1,
          cornerRadius: 4,
          callbacks: {
            label: function(context) {
              const labels = ['Operating Fund', 'Reserve Fund', 'Endowment Fund', 'Capital Fund'];
              const amounts = ['₱85.2M', '₱45.8M', '₱128.5M', '₱8.1M'];
              const percentages = ['52%', '28%', '15%', '5%'];
              return labels[context.dataIndex] + ': ' + amounts[context.dataIndex] + ' (' + percentages[context.dataIndex] + ')';
            }
          }
        }
      }
    }
  });

  // Initialize animations
  document.addEventListener('DOMContentLoaded', function() {
    // Add animation to stat cards on load
    document.querySelectorAll('.stat-card').forEach((card, index) => {
      card.style.animationDelay = `${index * 0.1}s`;
      card.classList.add('animate-slide-up');
    });
    
    // Handle dropdown hover behavior
    function setupDropdown(containerSelector, dropdownSelector) {
      const container = document.querySelector(containerSelector);
      const dropdown = document.querySelector(dropdownSelector);
      
      if (container && dropdown) {
        let hideTimeout;
        
        container.addEventListener('mouseenter', function() {
          clearTimeout(hideTimeout);
          dropdown.style.opacity = '1';
          dropdown.style.visibility = 'visible';
          dropdown.style.transform = 'translateY(0)';
        });
        
        container.addEventListener('mouseleave', function() {
          hideTimeout = setTimeout(function() {
            dropdown.style.opacity = '0';
            dropdown.style.visibility = 'hidden';
            dropdown.style.transform = 'translateY(-10px)';
          }, 300);
        });
        
        dropdown.addEventListener('mouseenter', function() {
          clearTimeout(hideTimeout);
        });
        
        dropdown.addEventListener('mouseleave', function() {
          hideTimeout = setTimeout(function() {
            dropdown.style.opacity = '0';
            dropdown.style.visibility = 'hidden';
            dropdown.style.transform = 'translateY(-10px)';
          }, 300);
        });
      }
    }
    
    // Setup dropdowns
    setupDropdown('.dropdown-container', '.dropdown');
  });

  // Update charts on window resize
  window.addEventListener('resize', function() {
    cashFlowChart.resize();
    fundAllocationChart.resize();
  });
</script>

</body>
</html>