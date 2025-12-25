<?php
session_start();
// Add authentication check here
// if (!isset($_SESSION['procurement_officer'])) {
//     header('Location: login.php');
//     exit();
// }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Procurement Dashboard | Procurement Officer | Financial Management System</title>
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
            accent: '#2563EB', // Blue for procurement
            success: '#059669', // Green for completed/approved
            danger: '#DC2626', // Red for overdue/rejected
            warning: '#D97706', // Amber for pending
            info: '#7C3AED', // Purple for vendors
            navbar: '#1E40AF',
            sidebar: '#1F2937',
            'purchase': '#2563EB',
            'vendor': '#7C3AED',
            'inventory': '#059669',
            'budget': '#D97706',
            'accent-light': '#DBEAFE',
            'success-light': '#D1FAE5',
            'danger-light': '#FEE2E2',
            'warning-light': '#FEF3C7',
            'info-light': '#EDE9FE',
            'procurement-blue': '#2563EB',
            'procurement-green': '#059669',
            'procurement-purple': '#7C3AED',
            'procurement-orange': '#EA580C'
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
    
    /* Procurement status indicators */
    .status-draft { background-color: #F3F4F6; color: #6B7280; }
    .status-pending { background-color: #FEF3C7; color: #D97706; }
    .status-approved { background-color: #D1FAE5; color: #059669; }
    .status-ordered { background-color: #DBEAFE; color: #2563EB; }
    .status-delivered { background-color: #EDE9FE; color: #7C3AED; }
    .status-received { background-color: #D1FAE5; color: #059669; }
    .status-rejected { background-color: #FEE2E2; color: #DC2626; }
    .status-overdue { background-color: #FEE2E2; color: #DC2626; border-left: 4px solid #DC2626; }
    
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
        <span class="font-bold text-gray-900 text-lg">Procurement Dashboard</span>
        <span class="ml-2 text-xs bg-accent text-white px-2 py-0.5 rounded-full font-semibold">PROCUREMENT OFFICER</span>
      </div>
    </div>
  </div>
  
  <div class="flex items-center gap-4">
    <!-- Procurement Period -->
    <div class="flex items-center gap-2 px-3 py-1 bg-blue-50 rounded-lg border border-blue-200">
      <svg class="w-3 h-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
      </svg>
      <span class="text-xs font-medium text-blue-700">Quarter: Q3 2023-2024</span>
    </div>
    
    <!-- Pending Approvals Timer -->
    <div class="flex items-center gap-2 px-3 py-1 bg-yellow-50 rounded-lg border border-yellow-200">
      <svg class="w-3 h-3 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <span class="text-xs font-medium text-yellow-700">Pending Approvals: 7 days max</span>
    </div>
    
    <!-- Procurement Alerts -->
    <div class="relative">
      <button class="p-2 rounded-full hover:bg-gray-100 relative">
        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        <span class="absolute top-1 right-1 w-2 h-2 bg-danger rounded-full"></span>
      </button>
      
      <!-- Procurement Alerts Dropdown -->
      <div class="dropdown absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl border border-gray-200 py-2 z-40">
        <div class="px-4 py-3 border-b border-gray-100">
          <div class="flex items-center justify-between">
            <h3 class="font-semibold text-gray-900 text-sm">Procurement Alerts</h3>
            <span class="text-xs bg-danger text-white px-2 py-0.5 rounded-full">5 urgent</span>
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
                <p class="text-sm font-medium text-gray-900">PO #2023-087 Overdue</p>
                <p class="text-xs text-gray-500 mt-0.5">Computer equipment - 3 days late</p>
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
                <p class="text-sm font-medium text-gray-900">Budget Approval Needed</p>
                <p class="text-xs text-gray-500 mt-0.5">Lab supplies - ₱45,200 pending</p>
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
                <p class="text-sm font-medium text-gray-900">Vendor Payment Due</p>
                <p class="text-xs text-gray-500 mt-0.5">Office Depot - ₱12,450 due tomorrow</p>
                <span class="text-xs text-gray-400 mt-1 block">2 days ago</span>
              </div>
            </div>
          </a>
        </div>
        
        <div class="border-t border-gray-100 pt-2">
          <a href="#" class="block text-center text-sm text-accent font-medium py-2 hover:bg-gray-50 rounded-b-lg transition-smooth">
            View All Procurement Alerts
          </a>
        </div>
      </div>
    </div>
    
    <!-- User Profile -->
    <div class="dropdown-container relative">
      <div class="flex items-center gap-3 border-l border-gray-200 pl-4 cursor-pointer hover:bg-gray-50 px-2 py-1 rounded-lg transition-smooth">
        <div class="text-right">
          <p class="font-medium text-gray-900 text-sm">Carlos Reyes</p>
          <p class="text-xs text-gray-500">Procurement Officer</p>
        </div>
        <div class="relative">
          <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=ProcurementOfficer" class="h-9 w-9 rounded-full border-2 border-accent">
          <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-success rounded-full border-2 border-white"></div>
        </div>
      </div>
      
      <!-- Procurement Officer Menu -->
      <div class="dropdown absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 py-2 z-40">
        <div class="px-4 py-2 border-b border-gray-100">
          <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Procurement</p>
        </div>
        
        <a href="#" class="dropdown-item flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-smooth">
          <div class="w-5 h-5 text-gray-500">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
          </div>
          <span class="text-sm text-gray-700">Purchase Orders</span>
        </a>
        
        <a href="#" class="dropdown-item flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-smooth">
          <div class="w-5 h-5 text-gray-500">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
          </div>
          <span class="text-sm text-gray-700">Vendor Management</span>
        </a>
        
        <a href="#" class="dropdown-item flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-smooth">
          <div class="w-5 h-5 text-gray-500">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
          </div>
          <span class="text-sm text-gray-700">Inventory</span>
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
  <!-- Procurement Sidebar -->
  <?php include 'sidebar.php'; ?>

  <!-- Main Content -->
  <main class="flex-1 overflow-y-auto p-6 space-y-6 bg-gray-50">
    
    <!-- Key Procurement Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-accent stat-card">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Pending POs</p>
            <p class="text-2xl font-bold mt-1">14</p>
          </div>
          <div class="p-2 bg-accent-light rounded-lg">
            <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
          </div>
        </div>
        <p class="text-xs warning font-medium">3 require immediate attention</p>
      </div>

      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-success stat-card">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Monthly Spend</p>
            <p class="text-2xl font-bold mt-1">₱2.45M</p>
          </div>
          <div class="p-2 bg-success-light rounded-lg">
            <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
        </div>
        <p class="text-xs success font-medium trend-up">12% under budget</p>
      </div>

      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-info stat-card">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Active Vendors</p>
            <p class="text-2xl font-bold mt-1">28</p>
          </div>
          <div class="p-2 bg-info-light rounded-lg">
            <svg class="w-5 h-5 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
          </div>
        </div>
        <p class="text-xs success font-medium trend-up">+3 this quarter</p>
      </div>

      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-warning stat-card">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Avg. Lead Time</p>
            <p class="text-2xl font-bold mt-1">7.2 days</p>
          </div>
          <div class="p-2 bg-warning-light rounded-lg">
            <svg class="w-5 h-5 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
        </div>
        <p class="text-xs danger font-medium trend-up">+1.5 days vs target</p>
      </div>
    </div>

    <!-- Procurement Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      
      <!-- Monthly Procurement Spend -->
      <div class="bg-white rounded-lg shadow p-5 lg:col-span-2">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-5">
          <div>
            <h3 class="font-semibold text-gray-900 text-base">Monthly Procurement Spend</h3>
            <p class="text-sm text-gray-500">2023-2024 Fiscal Year (in thousands)</p>
          </div>
          <select class="text-xs border border-gray-300 rounded-lg px-3 py-1.5 bg-white mt-2 sm:mt-0">
            <option selected>Current Fiscal Year</option>
            <option>By Department</option>
            <option>By Category</option>
          </select>
        </div>
        <div class="chart-container">
          <canvas id="procurementSpendChart"></canvas>
        </div>
      </div>

      <!-- PO Status Distribution -->
      <div class="bg-white rounded-lg shadow p-5">
        <h3 class="font-semibold text-gray-900 text-base mb-5">Purchase Order Status</h3>
        <div class="chart-container">
          <canvas id="poStatusChart"></canvas>
        </div>
        <div class="mt-4 space-y-3">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-warning"></div>
              <span class="text-xs text-gray-600">Pending Approval</span>
            </div>
            <span class="text-xs font-medium">8 POs</span>
          </div>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-accent"></div>
              <span class="text-xs text-gray-600">Ordered</span>
            </div>
            <span class="text-xs font-medium">12 POs</span>
          </div>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-success"></div>
              <span class="text-xs text-gray-600">Delivered</span>
            </div>
            <span class="text-xs font-medium">9 POs</span>
          </div>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-danger"></div>
              <span class="text-xs text-gray-600">Overdue</span>
            </div>
            <span class="text-xs font-medium">3 POs</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Pending Purchase Orders & Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      
      <!-- Pending Purchase Orders -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-5 border-b border-gray-200">
          <h3 class="font-semibold text-gray-900 text-base">Pending Purchase Orders</h3>
          <p class="text-sm text-gray-500">Requiring immediate attention</p>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full min-w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">PO Number</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Vendor</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Amount</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Due Date</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr class="status-overdue">
                <td class="py-3 px-4">
                  <p class="text-sm font-medium text-gray-900">PO-2023-087</p>
                  <p class="text-xs text-gray-500">Computer Equipment</p>
                </td>
                <td class="py-3 px-4">
                  <span class="text-sm font-medium text-gray-700">Tech Solutions Inc.</span>
                </td>
                <td class="py-3 px-4">
                  <span class="text-sm font-medium text-gray-700">₱245,800</span>
                </td>
                <td class="py-3 px-4">
                  <span class="px-2 py-1 text-xs bg-red-50 text-red-600 rounded-full">Overdue</span>
                </td>
                <td class="py-3 px-4">
                  <span class="text-sm font-medium text-gray-700">Oct 15</span>
                </td>
              </tr>
              <tr class="status-pending">
                <td class="py-3 px-4">
                  <p class="text-sm font-medium text-gray-900">PO-2023-093</p>
                  <p class="text-xs text-gray-500">Lab Supplies</p>
                </td>
                <td class="py-3 px-4">
                  <span class="text-sm font-medium text-gray-700">Science Supplies Co.</span>
                </td>
                <td class="py-3 px-4">
                  <span class="text-sm font-medium text-gray-700">₱45,200</span>
                </td>
                <td class="py-3 px-4">
                  <span class="px-2 py-1 text-xs bg-yellow-50 text-yellow-600 rounded-full">Pending Approval</span>
                </td>
                <td class="py-3 px-4">
                  <span class="text-sm font-medium text-gray-700">Nov 5</span>
                </td>
              </tr>
              <tr class="status-pending">
                <td class="py-3 px-4">
                  <p class="text-sm font-medium text-gray-900">PO-2023-095</p>
                  <p class="text-xs text-gray-500">Office Furniture</p>
                </td>
                <td class="py-3 px-4">
                  <span class="text-sm font-medium text-gray-700">Office Works Ltd.</span>
                </td>
                <td class="py-3 px-4">
                  <span class="text-sm font-medium text-gray-700">₱78,500</span>
                </td>
                <td class="py-3 px-4">
                  <span class="px-2 py-1 text-xs bg-yellow-50 text-yellow-600 rounded-full">Pending Approval</span>
                </td>
                <td class="py-3 px-4">
                  <span class="text-sm font-medium text-gray-700">Nov 10</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="p-4 border-t border-gray-200 text-center">
          <a href="#" class="text-sm text-accent font-medium hover:text-blue-700 transition-smooth">View All Purchase Orders →</a>
        </div>
      </div>

      <!-- Procurement Quick Actions & Alerts -->
      <div class="bg-white rounded-lg shadow p-5">
        <h3 class="font-semibold text-gray-900 text-base mb-4">Procurement Alerts</h3>
        <div class="space-y-3">
          <div class="p-3 bg-red-50 rounded-lg border-l-4 border-red-500">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-gray-900">Vendor Payment Overdue</p>
                <p class="text-xs text-gray-500 mt-0.5">Office Depot - ₱12,450 due tomorrow</p>
              </div>
              <button class="text-xs text-red-600 font-medium hover:text-red-700">Process</button>
            </div>
          </div>
          
          <div class="p-3 bg-yellow-50 rounded-lg border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-gray-900">New Vendor Registration</p>
                <p class="text-xs text-gray-500 mt-0.5">3 new vendors pending approval</p>
              </div>
              <button class="text-xs text-yellow-600 font-medium hover:text-yellow-700">Review</button>
            </div>
          </div>
          
          <div class="p-3 bg-green-50 rounded-lg border-l-4 border-green-500">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-gray-900">Inventory Reorder Points</p>
                <p class="text-xs text-gray-500 mt-0.5">5 items below minimum stock</p>
              </div>
              <button class="text-xs text-green-600 font-medium hover:text-green-700">Create POs</button>
            </div>
          </div>
        </div>
        
        <!-- Quick Procurement Actions -->
        <div class="mt-6 pt-5 border-t border-gray-200">
          <h4 class="text-sm font-semibold text-gray-900 mb-3">Quick Actions</h4>
          <div class="grid grid-cols-2 gap-3">
            <a href="#" class="bg-accent text-white p-3 rounded-lg text-center hover:bg-blue-700 transition-smooth">
              <p class="text-xs font-medium">Create New PO</p>
            </a>
            <a href="#" class="bg-success text-white p-3 rounded-lg text-center hover:bg-green-700 transition-smooth">
              <p class="text-xs font-medium">Receive Goods</p>
            </a>
          </div>
          <div class="grid grid-cols-2 gap-3 mt-3">
            <a href="#" class="bg-purple-600 text-white p-3 rounded-lg text-center hover:bg-purple-700 transition-smooth">
              <p class="text-xs font-medium">Add Vendor</p>
            </a>
            <a href="#" class="bg-gray-100 text-gray-700 p-3 rounded-lg text-center hover:bg-gray-200 transition-smooth">
              <p class="text-xs font-medium">Inventory Check</p>
            </a>
          </div>
        </div>
        
        <!-- Procurement KPIs -->
        <div class="mt-6 pt-5 border-t border-gray-200">
          <h4 class="text-sm font-semibold text-gray-900 mb-3">Procurement KPIs</h4>
          <div class="grid grid-cols-3 gap-2 text-center">
            <div class="bg-blue-50 p-2 rounded">
              <p class="text-lg font-bold text-blue-600">14</p>
              <p class="text-xs text-gray-600">Pending POs</p>
            </div>
            <div class="bg-green-50 p-2 rounded">
              <p class="text-lg font-bold text-green-600">7.2</p>
              <p class="text-xs text-gray-600">Avg. Lead Days</p>
            </div>
            <div class="bg-purple-50 p-2 rounded">
              <p class="text-lg font-bold text-purple-600">92%</p>
              <p class="text-xs text-gray-600">On-time Delivery</p>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Vendor Performance & Inventory -->
    <div class="bg-white rounded-lg shadow p-5">
      <h3 class="font-semibold text-gray-900 text-base mb-4">Vendor Performance & Inventory Status</h3>
      <div class="overflow-x-auto">
        <table class="w-full min-w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Vendor</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Category</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Rating</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">YTD Spend</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Performance</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr>
              <td class="py-2 px-3">
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center">
                    <span class="text-xs font-medium text-blue-600">TS</span>
                  </div>
                  <div>
                    <p class="text-xs font-medium">Tech Solutions Inc.</p>
                    <p class="text-xs text-gray-500">IT Equipment</p>
                  </div>
                </div>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs font-medium text-gray-700">IT Hardware</span>
              </td>
              <td class="py-2 px-3">
                <div class="flex items-center">
                  <span class="text-xs font-medium text-yellow-600">4.2/5</span>
                  <svg class="w-3 h-3 text-yellow-400 ml-1" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                  </svg>
                </div>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs font-medium text-gray-700">₱1.25M</span>
              </td>
              <td class="py-2 px-3">
                <span class="px-2 py-1 text-xs bg-yellow-50 text-yellow-600 rounded-full">Average</span>
              </td>
            </tr>
            <tr>
              <td class="py-2 px-3">
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center">
                    <span class="text-xs font-medium text-green-600">SS</span>
                  </div>
                  <div>
                    <p class="text-xs font-medium">Science Supplies Co.</p>
                    <p class="text-xs text-gray-500">Laboratory</p>
                  </div>
                </div>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs font-medium text-gray-700">Lab Materials</span>
              </td>
              <td class="py-2 px-3">
                <div class="flex items-center">
                  <span class="text-xs font-medium text-green-600">4.8/5</span>
                  <svg class="w-3 h-3 text-green-400 ml-1" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                  </svg>
                </div>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs font-medium text-gray-700">₱845,000</span>
              </td>
              <td class="py-2 px-3">
                <span class="px-2 py-1 text-xs bg-green-50 text-green-600 rounded-full">Excellent</span>
              </td>
            </tr>
            <tr>
              <td class="py-2 px-3">
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 bg-purple-100 rounded-full flex items-center justify-center">
                    <span class="text-xs font-medium text-purple-600">OW</span>
                  </div>
                  <div>
                    <p class="text-xs font-medium">Office Works Ltd.</p>
                    <p class="text-xs text-gray-500">Furniture & Supplies</p>
                  </div>
                </div>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs font-medium text-gray-700">Office Supplies</span>
              </td>
              <td class="py-2 px-3">
                <div class="flex items-center">
                  <span class="text-xs font-medium text-red-600">3.5/5</span>
                  <svg class="w-3 h-3 text-red-400 ml-1" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                  </svg>
                </div>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs font-medium text-gray-700">₱560,000</span>
              </td>
              <td class="py-2 px-3">
                <span class="px-2 py-1 text-xs bg-red-50 text-red-600 rounded-full">Needs Improvement</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="mt-4 pt-4 border-t">
        <div class="flex flex-col sm:flex-row justify-between items-center gap-3">
          <p class="text-xs text-gray-500">Active Vendors: 28 | Top Category: IT Hardware (₱1.25M) | Avg. Rating: 4.3/5</p>
          <a href="#" class="text-xs text-accent font-medium hover:text-blue-700 transition-smooth">
            View Complete Vendor Directory →
          </a>
        </div>
      </div>
    </div>
    
    <!-- Inventory Management & Procurement Workflow -->
    <div class="bg-white rounded-lg shadow p-5">
      <h3 class="font-semibold text-gray-900 text-base mb-4">Inventory & Procurement Workflow</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Low Stock Items -->
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-3">Low Stock Alerts</h4>
          <div class="space-y-3">
            <div class="p-3 bg-red-50 rounded-lg">
              <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-700">Printer Paper (A4)</span>
                <span class="text-xs font-bold text-red-600">15 units</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                <div class="bg-red-500 h-2 rounded-full" style="width: 30%"></div>
              </div>
              <p class="text-xs text-gray-500 mt-1">Reorder point: 50 units</p>
            </div>
            
            <div class="p-3 bg-yellow-50 rounded-lg">
              <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-700">Lab Beakers</span>
                <span class="text-xs font-bold text-yellow-600">42 units</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                <div class="bg-yellow-500 h-2 rounded-full" style="width: 60%"></div>
              </div>
              <p class="text-xs text-gray-500 mt-1">Reorder point: 70 units</p>
            </div>
            
            <div class="p-3 bg-yellow-50 rounded-lg">
              <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-700">Whiteboard Markers</span>
                <span class="text-xs font-bold text-yellow-600">25 units</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                <div class="bg-yellow-500 h-2 rounded-full" style="width: 40%"></div>
              </div>
              <p class="text-xs text-gray-500 mt-1">Reorder point: 60 units</p>
            </div>
          </div>
        </div>
        
        <!-- Procurement Workflow Status -->
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-3">Workflow Status</h4>
          <div class="space-y-3">
            <div class="p-3 bg-blue-50 rounded-lg">
              <p class="text-xs font-medium text-gray-900">POs Awaiting Approval</p>
              <p class="text-xs text-gray-500 mt-1">8 purchase orders</p>
              <p class="text-xs text-blue-600 mt-1">Total: ₱345,200</p>
            </div>
            
            <div class="p-3 bg-purple-50 rounded-lg">
              <p class="text-xs font-medium text-gray-900">Goods in Transit</p>
              <p class="text-xs text-gray-500 mt-1">5 shipments en route</p>
              <p class="text-xs text-purple-600 mt-1">Est. arrival: 2-5 days</p>
            </div>
            
            <div class="p-3 bg-green-50 rounded-lg">
              <p class="text-xs font-medium text-gray-900">Ready for Payment</p>
              <p class="text-xs text-gray-500 mt-1">3 invoices approved</p>
              <p class="text-xs text-green-600 mt-1">Total: ₱78,900</p>
            </div>
          </div>
        </div>
        
        <!-- Budget vs Actual -->
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-3">Budget vs Actual</h4>
          <div class="space-y-3">
            <div class="p-3 bg-green-50 rounded-lg">
              <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-700">Q3 Budget</span>
                <span class="text-xs font-bold text-green-600">₱2.8M</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                <div class="bg-green-500 h-2 rounded-full" style="width: 88%"></div>
              </div>
              <p class="text-xs text-green-600 mt-1">12% under budget</p>
            </div>
            
            <div class="p-3 bg-red-50 rounded-lg">
              <p class="text-xs font-medium text-gray-900">IT Department</p>
              <p class="text-xs text-gray-500 mt-1">Spent: ₱1.25M of ₱1.1M</p>
              <p class="text-xs text-red-600 mt-1">13.6% over budget</p>
              <button class="text-xs text-red-600 font-medium mt-2 hover:text-red-700">Review</button>
            </div>
            
            <div class="p-3 bg-gray-100 rounded-lg">
              <p class="text-xs font-medium text-gray-900">Next Quarter Planning</p>
              <p class="text-xs text-gray-500 mt-1">Q4 budget due: Nov 30</p>
              <button class="text-xs text-gray-700 font-medium mt-2 hover:text-gray-900">Start Planning</button>
            </div>
          </div>
        </div>
      </div>
      <div class="mt-4 text-center">
        <a href="#" class="text-xs text-accent font-medium hover:text-blue-700 transition-smooth">
          Access Complete Procurement Management Tools →
        </a>
      </div>
    </div>
  </main>
</div>

<!-- Footer -->
<footer class="bg-white border-t py-3">
  <div class="px-6 flex flex-col sm:flex-row justify-between items-center text-xs text-gray-500">
    <div class="mb-2 sm:mb-0">
      <span>© 2024 Procurement Management System v2.1.3</span>
      <span class="mx-2 hidden sm:inline">•</span>
      <span class="block sm:inline mt-1 sm:mt-0 text-success font-medium">Fiscal Quarter: Q3 2023-2024 | Last Updated: <?php echo date('Y-m-d H:i:s'); ?></span>
    </div>
    <div class="flex items-center gap-3">
      <a href="#" class="hover:text-accent transition-smooth">Purchase Orders</a>
      <a href="#" class="hover:text-accent transition-smooth">Vendor Portal</a>
      <a href="#" class="hover:text-accent transition-smooth">Inventory</a>
    </div>
  </div>
</footer>

<!-- JavaScript -->
<script>
  // Monthly Procurement Spend Chart
  const procurementSpendCtx = document.getElementById('procurementSpendChart').getContext('2d');
  const procurementSpendChart = new Chart(procurementSpendCtx, {
    type: 'bar',
    data: {
      labels: ['Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
      datasets: [
        {
          label: 'Actual Spend',
          data: [2.1, 2.3, 2.4, 2.1, 2.45, 0],
          backgroundColor: '#2563EB',
          borderColor: '#2563EB',
          borderWidth: 1,
          borderRadius: 4
        },
        {
          label: 'Budget',
          data: [2.5, 2.5, 2.5, 2.5, 2.8, 2.8],
          backgroundColor: '#D1D5DB',
          borderColor: '#D1D5DB',
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

  // PO Status Chart
  const poStatusCtx = document.getElementById('poStatusChart').getContext('2d');
  const poStatusChart = new Chart(poStatusCtx, {
    type: 'doughnut',
    data: {
      labels: ['Pending Approval', 'Ordered', 'Delivered', 'Overdue'],
      datasets: [{
        data: [8, 12, 9, 3],
        backgroundColor: ['#D97706', '#2563EB', '#059669', '#DC2626'],
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
              const labels = ['Pending Approval', 'Ordered', 'Delivered', 'Overdue'];
              const pos = [8, 12, 9, 3];
              return labels[context.dataIndex] + ': ' + pos[context.dataIndex] + ' POs';
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
    procurementSpendChart.resize();
    poStatusChart.resize();
  });
</script>

</body>
</html>