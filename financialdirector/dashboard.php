<?php
session_start();
// Add authentication check here
// if (!isset($_SESSION['financial_director'])) {
//     header('Location: login.php');
//     exit();
// }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Financial Dashboard | Financial Director | Financial Management System</title>
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
            accent: '#1D4ED8', // Deep blue for finance
            success: '#059669', // Darker green for positive financials
            danger: '#DC2626', // Darker red for financial alerts
            warning: '#D97706', // Darker amber
            info: '#7C3AED', // Deep purple for investments
            navbar: '#1E40AF',
            sidebar: '#1F2937',
            'revenue': '#059669',
            'expense': '#DC2626',
            'profit': '#2563EB',
            'budget': '#7C3AED',
            'accent-light': '#DBEAFE',
            'success-light': '#D1FAE5',
            'danger-light': '#FEE2E2',
            'warning-light': '#FEF3C7',
            'info-light': '#EDE9FE',
            'financial-green': '#059669',
            'financial-blue': '#1D4ED8',
            'financial-purple': '#7C3AED',
            'financial-orange': '#EA580C'
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
    
    /* Financial status indicators */
    .status-positive { background-color: #D1FAE5; color: #059669; }
    .status-negative { background-color: #FEE2E2; color: #DC2626; }
    .status-neutral { background-color: #E0E7FF; color: #4F46E5; }
    .status-warning { background-color: #FEF3C7; color: #D97706; }
    
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
  </style>
</head>

<body class="bg-gray-50 h-screen overflow-hidden font-inter">

<!-- Header -->
<header class="bg-white shadow-sm h-16 flex items-center justify-between px-6 fixed top-0 left-0 right-0 z-30 border-b border-gray-200">
  <div class="flex items-center gap-3">
    <div class="flex items-center gap-2">
      <img src="../assets/bcpnobg.png" class="h-8 w-8" alt="BCP Logo">
      <div>
        <span class="font-bold text-gray-900 text-lg">Financial Dashboard</span>
        <span class="ml-2 text-xs bg-accent text-white px-2 py-0.5 rounded-full font-semibold">FINANCIAL DIRECTOR</span>
      </div>
    </div>
  </div>
  
  <div class="flex items-center gap-4">
    <!-- Fiscal Period Status -->
    <div class="flex items-center gap-2 px-3 py-1 bg-blue-50 rounded-lg border border-blue-200">
      <svg class="w-3 h-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
      </svg>
      <span class="text-xs font-medium text-blue-700">Fiscal Year: 2023-2024 | Q3</span>
    </div>
    
    <!-- Budget Cycle Timer -->
    <div class="flex items-center gap-2 px-3 py-1 bg-green-50 rounded-lg border border-green-200">
      <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <span class="text-xs font-medium text-green-700">Budget Review: 14 days remaining</span>
    </div>
    
    <!-- Financial Alerts -->
    <div class="relative">
      <button class="p-2 rounded-full hover:bg-gray-100 relative">
        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        <span class="absolute top-1 right-1 w-2 h-2 bg-danger rounded-full"></span>
      </button>
      
      <!-- Financial Alerts Dropdown -->
      <div class="dropdown absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl border border-gray-200 py-2 z-40">
        <div class="px-4 py-3 border-b border-gray-100">
          <div class="flex items-center justify-between">
            <h3 class="font-semibold text-gray-900 text-sm">Financial Alerts</h3>
            <span class="text-xs bg-danger text-white px-2 py-0.5 rounded-full">3 critical</span>
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
                <p class="text-sm font-medium text-gray-900">Budget Overrun - IT Department</p>
                <p class="text-xs text-gray-500 mt-0.5">15% over budget for Q3</p>
                <span class="text-xs text-gray-400 mt-1 block">2 hours ago</span>
              </div>
            </div>
          </a>
          
          <a href="#" class="dropdown-item block px-4 py-3 border-b border-gray-100 hover:bg-gray-50 transition-smooth">
            <div class="flex items-start gap-3">
              <div class="w-8 h-8 bg-warning-light rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg class="w-4 h-4 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900">Tuition Collection Update</p>
                <p class="text-xs text-gray-500 mt-0.5">Q3 collections at 92% of target</p>
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
                <p class="text-sm font-medium text-gray-900">Investment Performance</p>
                <p class="text-xs text-gray-500 mt-0.5">Endowment fund +8.5% YTD</p>
                <span class="text-xs text-gray-400 mt-1 block">2 days ago</span>
              </div>
            </div>
          </a>
        </div>
        
        <div class="border-t border-gray-100 pt-2">
          <a href="#" class="block text-center text-sm text-accent font-medium py-2 hover:bg-gray-50 rounded-b-lg transition-smooth">
            View All Financial Reports
          </a>
        </div>
      </div>
    </div>
    
    <!-- User Profile -->
    <div class="dropdown-container relative">
      <div class="flex items-center gap-3 border-l border-gray-200 pl-4 cursor-pointer hover:bg-gray-50 px-2 py-1 rounded-lg transition-smooth">
        <div class="text-right">
          <p class="font-medium text-gray-900 text-sm">Maria Santos</p>
          <p class="text-xs text-gray-500">Financial Director</p>
        </div>
        <div class="relative">
          <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=FinancialDirector" class="h-9 w-9 rounded-full border-2 border-accent">
          <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-success rounded-full border-2 border-white"></div>
        </div>
      </div>
      
      <!-- Financial Director Menu -->
      <div class="dropdown absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 py-2 z-40">
        <div class="px-4 py-2 border-b border-gray-100">
          <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Financial Management</p>
        </div>
        
        <a href="#" class="dropdown-item flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-smooth">
          <div class="w-5 h-5 text-gray-500">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
          </div>
          <span class="text-sm text-gray-700">Financial Reports</span>
        </a>
        
        <a href="#" class="dropdown-item flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-smooth">
          <div class="w-5 h-5 text-gray-500">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <span class="text-sm text-gray-700">Budget Management</span>
        </a>
        
        <a href="#" class="dropdown-item flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-smooth">
          <div class="w-5 h-5 text-gray-500">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
          </div>
          <span class="text-sm text-gray-700">Revenue Analysis</span>
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
  <!-- Financial Sidebar -->
  <?php include 'sidebar.php'; ?>

  <!-- Main Content -->
  <main class="flex-1 overflow-y-auto p-6 space-y-6 bg-gray-50">
    
    <!-- Key Financial Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-success stat-card">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Total Revenue YTD</p>
            <p class="text-2xl font-bold mt-1">₱128.5M</p>
          </div>
          <div class="p-2 bg-success-light rounded-lg">
            <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
        </div>
        <p class="text-xs success font-medium trend-up">+12.8% vs last year</p>
      </div>

      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-accent stat-card">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Operating Expenses</p>
            <p class="text-2xl font-bold mt-1">₱89.2M</p>
          </div>
          <div class="p-2 bg-accent-light rounded-lg">
            <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
          </div>
        </div>
        <p class="text-xs warning font-medium trend-up">+8.3% vs budget</p>
      </div>

      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-info stat-card">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Net Profit Margin</p>
            <p class="text-2xl font-bold mt-1">24.8%</p>
          </div>
          <div class="p-2 bg-info-light rounded-lg">
            <svg class="w-5 h-5 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
            </svg>
          </div>
        </div>
        <p class="text-xs success font-medium trend-up">+2.3% points improvement</p>
      </div>

      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-warning stat-card">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Tuition Collection Rate</p>
            <p class="text-2xl font-bold mt-1">94.2%</p>
          </div>
          <div class="p-2 bg-warning-light rounded-lg">
            <svg class="w-5 h-5 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
          </div>
        </div>
        <p class="text-xs success font-medium trend-up">+3.1% vs target</p>
      </div>
    </div>

    <!-- Financial Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      
      <!-- Revenue vs Expenses Trend -->
      <div class="bg-white rounded-lg shadow p-5 lg:col-span-2">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-5">
          <div>
            <h3 class="font-semibold text-gray-900 text-base">Revenue vs Expenses Trend</h3>
            <p class="text-sm text-gray-500">Fiscal Year 2023-2024 (in millions)</p>
          </div>
          <select class="text-xs border border-gray-300 rounded-lg px-3 py-1.5 bg-white mt-2 sm:mt-0">
            <option selected>Current Fiscal Year</option>
            <option>Last 3 Years</option>
            <option>Quarterly View</option>
          </select>
        </div>
        <div class="chart-container">
          <canvas id="revenueExpenseChart"></canvas>
        </div>
      </div>

      <!-- Department Budget Utilization -->
      <div class="bg-white rounded-lg shadow p-5">
        <h3 class="font-semibold text-gray-900 text-base mb-5">Department Budget Utilization</h3>
        <div class="chart-container">
          <canvas id="budgetUtilizationChart"></canvas>
        </div>
        <div class="mt-4 space-y-3">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-success"></div>
              <span class="text-xs text-gray-600">Under Budget</span>
            </div>
            <span class="text-xs font-medium">4 depts</span>
          </div>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-warning"></div>
              <span class="text-xs text-gray-600">On Track (±5%)</span>
            </div>
            <span class="text-xs font-medium">3 depts</span>
          </div>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-danger"></div>
              <span class="text-xs text-gray-600">Over Budget</span>
            </div>
            <span class="text-xs font-medium">2 depts</span>
          </div>
        </div>
        <p class="text-xs text-gray-500 mt-4 text-center">Overall Utilization: 102.3% of budget</p>
      </div>
    </div>

    <!-- Financial Overview & Alerts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      
      <!-- Budget Status by Department -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-5 border-b border-gray-200">
          <h3 class="font-semibold text-gray-900 text-base">Department Budget Status</h3>
          <p class="text-sm text-gray-500">Q3 2023-2024 Performance</p>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full min-w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Department</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Budget</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Spent</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Utilization</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr>
                <td class="py-3 px-4">
                  <p class="text-sm font-medium text-gray-900">Academic Affairs</p>
                </td>
                <td class="py-3 px-4">
                  <span class="text-sm font-medium text-gray-700">₱45.2M</span>
                </td>
                <td class="py-3 px-4">
                  <span class="text-sm font-medium text-gray-700">₱43.8M</span>
                </td>
                <td class="py-3 px-4">
                  <span class="text-sm font-medium text-success">96.9%</span>
                </td>
                <td class="py-3 px-4">
                  <span class="px-2 py-1 text-xs bg-green-50 text-green-600 rounded-full">Under Budget</span>
                </td>
              </tr>
              <tr>
                <td class="py-3 px-4">
                  <p class="text-sm font-medium text-gray-900">IT Services</p>
                </td>
                <td class="py-3 px-4">
                  <span class="text-sm font-medium text-gray-700">₱12.5M</span>
                </td>
                <td class="py-3 px-4">
                  <span class="text-sm font-medium text-gray-700">₱14.4M</span>
                </td>
                <td class="py-3 px-4">
                  <span class="text-sm font-medium text-danger">115.2%</span>
                </td>
                <td class="py-3 px-4">
                  <span class="px-2 py-1 text-xs bg-red-50 text-red-600 rounded-full">Over Budget</span>
                </td>
              </tr>
              <tr>
                <td class="py-3 px-4">
                  <p class="text-sm font-medium text-gray-900">Facilities</p>
                </td>
                <td class="py-3 px-4">
                  <span class="text-sm font-medium text-gray-700">₱8.3M</span>
                </td>
                <td class="py-3 px-4">
                  <span class="text-sm font-medium text-gray-700">₱8.1M</span>
                </td>
                <td class="py-3 px-4">
                  <span class="text-sm font-medium text-success">97.6%</span>
                </td>
                <td class="py-3 px-4">
                  <span class="px-2 py-1 text-xs bg-green-50 text-green-600 rounded-full">Under Budget</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="p-4 border-t border-gray-200 text-center">
          <a href="#" class="text-sm text-accent font-medium hover:text-blue-700 transition-smooth">View Detailed Budget Reports →</a>
        </div>
      </div>

      <!-- Financial Quick Actions & Alerts -->
      <div class="bg-white rounded-lg shadow p-5">
        <h3 class="font-semibold text-gray-900 text-base mb-4">Financial Alerts & Actions</h3>
        <div class="space-y-3">
          <div class="p-3 bg-red-50 rounded-lg border-l-4 border-red-500">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-gray-900">IT Department Over Budget</p>
                <p class="text-xs text-gray-500 mt-0.5">15.2% over Q3 budget allocation</p>
              </div>
              <button class="text-xs text-red-600 font-medium hover:text-red-700">Review</button>
            </div>
          </div>
          
          <div class="p-3 bg-yellow-50 rounded-lg border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-gray-900">Q4 Budget Planning</p>
                <p class="text-xs text-gray-500 mt-0.5">Due for approval in 14 days</p>
              </div>
              <button class="text-xs text-yellow-600 font-medium hover:text-yellow-700">Start</button>
            </div>
          </div>
          
          <div class="p-3 bg-green-50 rounded-lg border-l-4 border-green-500">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-gray-900">Tuition Collection</p>
                <p class="text-xs text-gray-500 mt-0.5">94.2% collected, exceeding target</p>
              </div>
              <button class="text-xs text-green-600 font-medium hover:text-green-700">Details</button>
            </div>
          </div>
        </div>
        
        <!-- Quick Financial Actions -->
        <div class="mt-6 pt-5 border-t border-gray-200">
          <h4 class="text-sm font-semibold text-gray-900 mb-3">Quick Financial Actions</h4>
          <div class="grid grid-cols-2 gap-3">
            <a href="#" class="bg-accent text-white p-3 rounded-lg text-center hover:bg-blue-700 transition-smooth">
              <p class="text-xs font-medium">Generate Financial Report</p>
            </a>
            <a href="#" class="bg-success text-white p-3 rounded-lg text-center hover:bg-green-700 transition-smooth">
              <p class="text-xs font-medium">Approve Budget Request</p>
            </a>
          </div>
          <div class="grid grid-cols-2 gap-3 mt-3">
            <a href="#" class="bg-purple-600 text-white p-3 rounded-lg text-center hover:bg-purple-700 transition-smooth">
              <p class="text-xs font-medium">Review Investments</p>
            </a>
            <a href="#" class="bg-gray-100 text-gray-700 p-3 rounded-lg text-center hover:bg-gray-200 transition-smooth">
              <p class="text-xs font-medium">Audit Logs</p>
            </a>
          </div>
        </div>
        
        <!-- Institutional KPIs -->
        <div class="mt-6 pt-5 border-t border-gray-200">
          <h4 class="text-sm font-semibold text-gray-900 mb-3">Institutional KPIs</h4>
          <div class="grid grid-cols-3 gap-2 text-center">
            <div class="bg-blue-50 p-2 rounded">
              <p class="text-lg font-bold text-blue-600">₱128.5M</p>
              <p class="text-xs text-gray-600">YTD Revenue</p>
            </div>
            <div class="bg-green-50 p-2 rounded">
              <p class="text-lg font-bold text-green-600">24.8%</p>
              <p class="text-xs text-gray-600">Profit Margin</p>
            </div>
            <div class="bg-purple-50 p-2 rounded">
              <p class="text-lg font-bold text-purple-600">94.2%</p>
              <p class="text-xs text-gray-600">Collection Rate</p>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Revenue Streams Analysis -->
    <div class="bg-white rounded-lg shadow p-5">
      <h3 class="font-semibold text-gray-900 text-base mb-4">Revenue Streams Analysis</h3>
      <div class="overflow-x-auto">
        <table class="w-full min-w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Revenue Source</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">YTD Amount</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">% of Total</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Growth vs LY</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Forecast</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr>
              <td class="py-2 px-3">
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center">
                    <span class="text-xs font-medium text-blue-600">TU</span>
                  </div>
                  <div>
                    <p class="text-xs font-medium">Tuition Fees</p>
                    <p class="text-xs text-gray-500">Regular Programs</p>
                  </div>
                </div>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs font-medium text-gray-700">₱85.2M</span>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs font-medium text-gray-700">66.3%</span>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs font-bold text-success trend-up">+10.5%</span>
              </td>
              <td class="py-2 px-3">
                <span class="px-2 py-1 text-xs bg-green-50 text-green-600 rounded-full">On Track</span>
              </td>
            </tr>
            <tr>
              <td class="py-2 px-3">
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center">
                    <span class="text-xs font-medium text-green-600">EX</span>
                  </div>
                  <div>
                    <p class="text-xs font-medium">Executive Education</p>
                    <p class="text-xs text-gray-500">Short Courses & Seminars</p>
                  </div>
                </div>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs font-medium text-gray-700">₱18.7M</span>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs font-medium text-gray-700">14.6%</span>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs font-bold text-success trend-up">+22.3%</span>
              </td>
              <td class="py-2 px-3">
                <span class="px-2 py-1 text-xs bg-green-50 text-green-600 rounded-full">Exceeding</span>
              </td>
            </tr>
            <tr>
              <td class="py-2 px-3">
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 bg-purple-100 rounded-full flex items-center justify-center">
                    <span class="text-xs font-medium text-purple-600">GR</span>
                  </div>
                  <div>
                    <p class="text-xs font-medium">Grants & Donations</p>
                    <p class="text-xs text-gray-500">Research & Endowment</p>
                  </div>
                </div>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs font-medium text-gray-700">₱12.4M</span>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs font-medium text-gray-700">9.7%</span>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs font-bold text-danger trend-down">-5.2%</span>
              </td>
              <td class="py-2 px-3">
                <span class="px-2 py-1 text-xs bg-yellow-50 text-yellow-600 rounded-full">Needs Review</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="mt-4 pt-4 border-t">
        <div class="flex flex-col sm:flex-row justify-between items-center gap-3">
          <p class="text-xs text-gray-500">Total YTD Revenue: ₱128.5M | Growth: +12.8% | Forecast Accuracy: 96.3%</p>
          <a href="#" class="text-xs text-accent font-medium hover:text-blue-700 transition-smooth">
            View Complete Revenue Analysis →
          </a>
        </div>
      </div>
    </div>
    
    <!-- Investment & Asset Management -->
    <div class="bg-white rounded-lg shadow p-5">
      <h3 class="font-semibold text-gray-900 text-base mb-4">Investment & Asset Management</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Endowment Fund Performance -->
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-3">Endowment Fund Performance</h4>
          <div class="space-y-3">
            <div class="p-3 bg-blue-50 rounded-lg">
              <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-700">Total Value</span>
                <span class="text-xs font-bold text-blue-600">₱245.8M</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                <div class="bg-blue-500 h-2 rounded-full" style="width: 65%"></div>
              </div>
            </div>
            
            <div class="p-3 bg-green-50 rounded-lg">
              <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-700">YTD Return</span>
                <span class="text-xs font-bold text-green-600">+8.5%</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                <div class="bg-green-500 h-2 rounded-full" style="width: 85%"></div>
              </div>
            </div>
            
            <div class="p-3 bg-purple-50 rounded-lg">
              <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-700">Annual Payout</span>
                <span class="text-xs font-bold text-purple-600">₱12.3M</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                <div class="bg-purple-500 h-2 rounded-full" style="width: 75%"></div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Fixed Assets -->
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-3">Fixed Assets Overview</h4>
          <div class="space-y-3">
            <div class="p-3 bg-yellow-50 rounded-lg">
              <p class="text-xs font-medium text-gray-900">Property & Buildings</p>
              <p class="text-xs text-gray-500 mt-1">Book Value: ₱185.4M</p>
              <p class="text-xs text-green-600 mt-1">Appreciation: +5.2%</p>
            </div>
            
            <div class="p-3 bg-teal-50 rounded-lg">
              <p class="text-xs font-medium text-gray-900">Equipment & Technology</p>
              <p class="text-xs text-gray-500 mt-1">Net Value: ₱42.7M</p>
              <p class="text-xs text-gray-500 mt-1">Depreciation: 15% annually</p>
            </div>
            
            <div class="p-3 bg-pink-50 rounded-lg">
              <p class="text-xs font-medium text-gray-900">Maintenance Reserve</p>
              <p class="text-xs text-gray-500 mt-1">Balance: ₱8.5M</p>
              <button class="text-xs text-pink-600 font-medium mt-2 hover:text-pink-700">Allocate Funds</button>
            </div>
          </div>
        </div>
        
        <!-- Financial Compliance -->
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-3">Financial Compliance</h4>
          <div class="space-y-3">
            <div class="p-3 bg-green-50 rounded-lg">
              <p class="text-xs font-medium text-gray-900">Audit Status</p>
              <p class="text-xs text-gray-500 mt-1">Last audit: Clean opinion</p>
              <p class="text-xs text-green-600 mt-1">Next audit: Q1 2024</p>
            </div>
            
            <div class="p-3 bg-red-50 rounded-lg">
              <p class="text-xs font-medium text-gray-900">Regulatory Compliance</p>
              <p class="text-xs text-gray-500 mt-1">2 pending requirements</p>
              <button class="text-xs text-red-600 font-medium mt-2 hover:text-red-700">Review Now</button>
            </div>
            
            <div class="p-3 bg-gray-100 rounded-lg">
              <p class="text-xs font-medium text-gray-900">Insurance Coverage</p>
              <p class="text-xs text-gray-500 mt-1">Renewal due: Dec 15, 2024</p>
              <button class="text-xs text-gray-700 font-medium mt-2 hover:text-gray-900">Review Policies</button>
            </div>
          </div>
        </div>
      </div>
      <div class="mt-4 text-center">
        <a href="#" class="text-xs text-accent font-medium hover:text-blue-700 transition-smooth">
          Access Complete Financial Management Tools →
        </a>
      </div>
    </div>
  </main>
</div>

<!-- Footer -->
<footer class="bg-white border-t py-3">
  <div class="px-6 flex flex-col sm:flex-row justify-between items-center text-xs text-gray-500">
    <div class="mb-2 sm:mb-0">
      <span>© 2024 Financial Management System v3.2.1</span>
      <span class="mx-2 hidden sm:inline">•</span>
      <span class="block sm:inline mt-1 sm:mt-0 text-success font-medium">Fiscal Year: 2023-2024 | Quarter: Q3 | Last Updated: <?php echo date('Y-m-d H:i:s'); ?></span>
    </div>
    <div class="flex items-center gap-3">
      <a href="#" class="hover:text-accent transition-smooth">Financial Reports</a>
      <a href="#" class="hover:text-accent transition-smooth">Budget Planning</a>
      <a href="#" class="hover:text-accent transition-smooth">Compliance</a>
    </div>
  </div>
</footer>

<!-- JavaScript -->
<script>
  // Revenue vs Expenses Chart
  const revenueExpenseCtx = document.getElementById('revenueExpenseChart').getContext('2d');
  const revenueExpenseChart = new Chart(revenueExpenseCtx, {
    type: 'bar',
    data: {
      labels: ['Q1', 'Q2', 'Q3', 'Q4'],
      datasets: [
        {
          label: 'Revenue',
          data: [28.5, 32.1, 35.8, 32.1],
          backgroundColor: '#059669',
          borderColor: '#059669',
          borderWidth: 1,
          borderRadius: 4
        },
        {
          label: 'Expenses',
          data: [22.1, 24.3, 28.5, 25.3],
          backgroundColor: '#DC2626',
          borderColor: '#DC2626',
          borderWidth: 1,
          borderRadius: 4
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
          borderColor: '#3B82F6',
          borderWidth: 1,
          cornerRadius: 4,
          callbacks: {
            label: function(context) {
              return context.dataset.label + ': ₱' + context.parsed.y + 'M';
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

  // Budget Utilization Chart
  const budgetUtilizationCtx = document.getElementById('budgetUtilizationChart').getContext('2d');
  const budgetUtilizationChart = new Chart(budgetUtilizationCtx, {
    type: 'doughnut',
    data: {
      labels: ['Under Budget', 'On Track', 'Over Budget'],
      datasets: [{
        data: [4, 3, 2],
        backgroundColor: ['#059669', '#D97706', '#DC2626'],
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
          borderColor: '#3B82F6',
          borderWidth: 1,
          cornerRadius: 4,
          callbacks: {
            label: function(context) {
              const labels = ['Under Budget (<95%)', 'On Track (95-105%)', 'Over Budget (>105%)'];
              const depts = [4, 3, 2];
              return labels[context.dataIndex] + ': ' + depts[context.dataIndex] + ' departments';
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
    revenueExpenseChart.resize();
    budgetUtilizationChart.resize();
  });
</script>

</body>
</html>