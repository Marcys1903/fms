<?php
session_start();
// Add authentication check here
// if (!isset($_SESSION['compliance_officer'])) {
//     header('Location: login.php');
//     exit();
// }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Compliance Dashboard | Financial Management System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="../assets/bcpnobg.png">
  
  <!-- CDN Links -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="//unpkg.com/alpinejs" defer></script>

  <!-- Tailwind Configuration -->
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: '#1E293B',
            accent: '#1D4ED8', // Blue for compliance/regulatory
            success: '#059669',
            danger: '#DC2626',
            warning: '#D97706',
            info: '#0EA5E9',
            navbar: '#1E40AF', // Dark blue for navbar
            sidebar: '#1E293B',
            'accent-light': '#DBEAFE', // Light blue
            'success-light': '#DCFCE7',
            'danger-light': '#FEE2E2',
            'warning-light': '#FEF3C7',
            'gray-150': '#F3F4F6',
            'compliance-green': '#059669',
            'compliance-red': '#DC2626',
            'compliance-yellow': '#D97706',
            'compliance-purple': '#7C3AED'
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
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
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
    
    .notification-dropdown {
      opacity: 0;
      visibility: hidden;
      transform: translateY(-10px);
      transition: all 0.2s ease;
    }
    
    .notification-container:hover .notification-dropdown {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }
    
    .notification-item:hover {
      background-color: #f9fafb;
    }
    
    /* Compliance status indicators */
    .status-compliant { background-color: #DCFCE7; color: #059669; border-left: 4px solid #059669; }
    .status-noncompliant { background-color: #FEE2E2; color: #DC2626; border-left: 4px solid #DC2626; }
    .status-pending { background-color: #FEF3C7; color: #D97706; border-left: 4px solid #D97706; }
    .status-review { background-color: #DBEAFE; color: #1D4ED8; border-left: 4px solid #1D4ED8; }
    
    /* Risk level indicators */
    .risk-critical { background-color: #FEE2E2; color: #DC2626; }
    .risk-high { background-color: #FEF3C7; color: #D97706; }
    .risk-medium { background-color: #DBEAFE; color: #1D4ED8; }
    .risk-low { background-color: #DCFCE7; color: #059669; }
  </style>
</head>

<body class="bg-gray-50 h-screen overflow-hidden font-inter">

<!-- Header -->
<header class="bg-white shadow-sm h-16 flex items-center justify-between px-6 fixed top-0 left-0 right-0 z-30 border-b border-gray-200">
  <div class="flex items-center gap-3">
    <div class="flex items-center gap-2">
      <img src="../assets/bcpnobg.png" class="h-8 w-8" alt="BCP Logo">
      <div>
        <span class="font-bold text-gray-900 text-lg">Compliance Management Hub</span>
        <span class="ml-2 text-xs bg-accent text-white px-2 py-0.5 rounded-full font-semibold">COMPLIANCE OFFICER</span>
      </div>
    </div>
  </div>
  
  <div class="flex items-center gap-4">
    <!-- Regulatory Updates Badge -->
    <div class="flex items-center gap-2 px-3 py-1 bg-accent-light rounded-lg border border-blue-200">
      <svg class="w-3 h-3 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h1m0 0h-1m1 0v4m-4-6h.01M11 10h4m-4 4h4m-5 6h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z"/>
      </svg>
      <span class="text-xs font-medium text-accent">3 New Regulations</span>
    </div>
    
    <!-- Audit Cycle Timer -->
    <div class="flex items-center gap-2 px-3 py-1 bg-green-50 rounded-lg border border-green-200">
      <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
      </svg>
      <span class="text-xs font-medium text-green-700">Q2 Audit: 28 days</span>
    </div>
    
    <!-- Notifications with Hover Dropdown -->
    <div class="notification-container relative">
      <button class="p-2 rounded-full hover:bg-gray-100 relative">
        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        <span class="absolute top-1 right-1 w-2 h-2 bg-danger rounded-full"></span>
      </button>
      
      <!-- Notification Dropdown -->
      <div class="notification-dropdown absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl border border-gray-200 py-2 z-40">
        <div class="px-4 py-3 border-b border-gray-100">
          <div class="flex items-center justify-between">
            <h3 class="font-semibold text-gray-900 text-sm">Compliance Alerts</h3>
            <span class="text-xs bg-accent text-white px-2 py-0.5 rounded-full">7 new</span>
          </div>
        </div>
        
        <div class="max-h-80 overflow-y-auto">
          <!-- Notification items -->
          <a href="#" class="notification-item block px-4 py-3 border-b border-gray-100 hover:bg-gray-50 transition-smooth">
            <div class="flex items-start gap-3">
              <div class="w-8 h-8 bg-danger-light rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg class="w-4 h-4 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">Critical AML Alert</p>
                <p class="text-xs text-gray-500 mt-0.5">Transaction exceeds reporting threshold</p>
                <span class="text-xs text-gray-400 mt-1 block">45 min ago</span>
              </div>
              <div class="w-2 h-2 bg-danger rounded-full flex-shrink-0 mt-1"></div>
            </div>
          </a>
          
          <a href="#" class="notification-item block px-4 py-3 border-b border-gray-100 hover:bg-gray-50 transition-smooth">
            <div class="flex items-start gap-3">
              <div class="w-8 h-8 bg-warning-light rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg class="w-4 h-4 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">SOX Deadline Approaching</p>
                <p class="text-xs text-gray-500 mt-0.5">Quarterly controls testing due in 7 days</p>
                <span class="text-xs text-gray-400 mt-1 block">2 hours ago</span>
              </div>
              <div class="w-2 h-2 bg-accent rounded-full flex-shrink-0 mt-1"></div>
            </div>
          </a>
          
          <a href="#" class="notification-item block px-4 py-3 border-b border-gray-100 hover:bg-gray-50 transition-smooth">
            <div class="flex items-start gap-3">
              <div class="w-8 h-8 bg-success-light rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg class="w-4 h-4 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">Policy Update Required</p>
                <p class="text-xs text-gray-500 mt-0.5">New GDPR requirements effective next month</p>
                <span class="text-xs text-gray-400 mt-1 block">1 day ago</span>
              </div>
            </div>
          </a>
          
          <a href="#" class="notification-item block px-4 py-3 border-b border-gray-100 hover:bg-gray-50 transition-smooth">
            <div class="flex items-start gap-3">
              <div class="w-8 h-8 bg-accent-light rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">Compliance Report Ready</p>
                <p class="text-xs text-gray-500 mt-0.5">Monthly compliance dashboard generated</p>
                <span class="text-xs text-gray-400 mt-1 block">2 days ago</span>
              </div>
            </div>
          </a>
          
          <a href="#" class="notification-item block px-4 py-3 hover:bg-gray-50 transition-smooth">
            <div class="flex items-start gap-3">
              <div class="w-8 h-8 bg-compliance-purple/10 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg class="w-4 h-4 text-compliance-purple" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">Training Certification Due</p>
                <p class="text-xs text-gray-500 mt-0.5">15 employees need compliance training renewal</p>
                <span class="text-xs text-gray-400 mt-1 block">3 days ago</span>
              </div>
            </div>
          </a>
        </div>
        
        <div class="border-t border-gray-100 pt-2">
          <a href="#" class="block text-center text-sm text-accent font-medium py-2 hover:bg-gray-50 rounded-b-lg transition-smooth">
            View All Compliance Alerts
          </a>
        </div>
      </div>
    </div>
    
    <!-- User Profile with Hover Dropdown -->
    <div class="dropdown-container relative">
      <div class="flex items-center gap-3 border-l border-gray-200 pl-4 cursor-pointer hover:bg-gray-50 px-2 py-1 rounded-lg transition-smooth">
        <div class="text-right">
          <p class="font-medium text-gray-900 text-sm">James Wilson</p>
          <p class="text-xs text-gray-500">Chief Compliance Officer</p>
        </div>
        <div class="relative">
          <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=ComplianceOfficer" class="h-9 w-9 rounded-full border-2 border-accent">
          <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-success rounded-full border-2 border-white"></div>
        </div>
      </div>
      
      <!-- User Profile Dropdown -->
      <div class="dropdown absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 py-2 z-40">
        <div class="px-4 py-2 border-b border-gray-100">
          <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Compliance Account</p>
        </div>
        
        <a href="#" class="dropdown-item flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-smooth">
          <div class="w-5 h-5 text-gray-500">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
          </div>
          <span class="text-sm text-gray-700">Compliance Profile</span>
        </a>
        
        <a href="#" class="dropdown-item flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-smooth">
          <div class="w-5 h-5 text-gray-500">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
          </div>
          <span class="text-sm text-gray-700">Compliance Settings</span>
        </a>
        
        <a href="#" class="dropdown-item flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-smooth">
          <div class="w-5 h-5 text-gray-500">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
          </div>
          <span class="text-sm text-gray-700">Regulatory Updates</span>
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
  <!-- Sidebar -->
  <?php include 'sidebar.php'; ?>

  <!-- Main Content -->
  <main class="flex-1 overflow-y-auto p-6 space-y-6 bg-gray-50">
    
    <!-- Compliance Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-accent stat-card">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Overall Compliance Score</p>
            <p class="text-2xl font-bold mt-1">94%</p>
          </div>
          <div class="p-2 bg-accent-light rounded-lg">
            <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
        </div>
        <p class="text-xs text-success mt-2 font-medium">+2% from last quarter</p>
      </div>

      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-danger stat-card">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Open Violations</p>
            <p class="text-2xl font-bold mt-1">18</p>
          </div>
          <div class="p-2 bg-danger-light rounded-lg">
            <svg class="w-5 h-5 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
          </div>
        </div>
        <p class="text-xs text-danger mt-2 font-medium">3 critical</p>
      </div>

      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-warning stat-card">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Pending Reviews</p>
            <p class="text-2xl font-bold mt-1">42</p>
          </div>
          <div class="p-2 bg-warning-light rounded-lg">
            <svg class="w-5 h-5 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
        </div>
        <p class="text-xs text-warning mt-2">Due within 7 days</p>
      </div>

      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-success stat-card">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Policy Updates</p>
            <p class="text-2xl font-bold mt-1">6</p>
          </div>
          <div class="p-2 bg-success-light rounded-lg">
            <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
          </div>
        </div>
        <p class="text-xs text-gray-500 mt-2">Required this month</p>
      </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      
      <!-- Compliance Trend Analysis -->
      <div class="bg-white rounded-lg shadow p-5 lg:col-span-2">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-5">
          <div>
            <h3 class="font-semibold text-gray-900 text-base">Compliance Trend Analysis</h3>
            <p class="text-sm text-gray-500">Quarterly compliance score progression</p>
          </div>
          <select class="text-xs border border-gray-300 rounded-lg px-3 py-1.5 bg-white mt-2 sm:mt-0">
            <option>Last 4 quarters</option>
            <option selected>Last 2 years</option>
            <option>Year to date</option>
          </select>
        </div>
        <div class="chart-container">
          <canvas id="complianceTrendChart"></canvas>
        </div>
        <div class="flex items-center justify-center gap-4 mt-4 text-xs">
          <div class="flex items-center gap-1.5">
            <div class="w-3 h-3 rounded-full bg-accent"></div>
            <span class="text-gray-600">Overall Compliance</span>
          </div>
          <div class="flex items-center gap-1.5">
            <div class="w-3 h-3 rounded-full bg-success"></div>
            <span class="text-gray-600">Regulatory Compliance</span>
          </div>
          <div class="flex items-center gap-1.5">
            <div class="w-3 h-3 rounded-full bg-warning"></div>
            <span class="text-gray-600">Internal Policy</span>
          </div>
        </div>
      </div>

      <!-- Risk Distribution -->
      <div class="bg-white rounded-lg shadow p-5">
        <h3 class="font-semibold text-gray-900 text-base mb-5">Risk Distribution</h3>
        <div class="chart-container">
          <canvas id="riskDistributionChart"></canvas>
        </div>
        <div class="mt-4 space-y-3">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-danger"></div>
              <span class="text-xs text-gray-600">Critical Risks</span>
            </div>
            <span class="text-xs font-medium">3 (5%)</span>
          </div>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-warning"></div>
              <span class="text-xs text-gray-600">High Risks</span>
            </div>
            <span class="text-xs font-medium">12 (20%)</span>
          </div>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-accent"></div>
              <span class="text-xs text-gray-600">Medium Risks</span>
            </div>
            <span class="text-xs font-medium">24 (40%)</span>
          </div>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-success"></div>
              <span class="text-xs text-gray-600">Low Risks</span>
            </div>
            <span class="text-xs font-medium">21 (35%)</span>
          </div>
        </div>
        <p class="text-xs text-gray-500 mt-4 text-center">Total Risks Identified: 60</p>
      </div>
    </div>

    <!-- Recent Activities & Compliance Issues -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      
      <!-- Recent Compliance Violations -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-5 border-b border-gray-200">
          <h3 class="font-semibold text-gray-900 text-base">Recent Compliance Violations</h3>
          <p class="text-sm text-gray-500">Latest compliance issues identified</p>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full min-w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Violation</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Department</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Risk Level</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr class="status-noncompliant">
                <td class="py-3 px-4">
                  <div>
                    <p class="text-sm font-medium text-gray-900">AML Transaction Reporting</p>
                    <p class="text-xs text-gray-500">Late filing of suspicious activity report</p>
                  </div>
                </td>
                <td class="py-3 px-4">
                  <span class="text-sm font-medium text-gray-700">Finance</span>
                </td>
                <td class="py-3 px-4">
                  <span class="px-2 py-1 text-xs risk-critical rounded-full">Critical</span>
                </td>
              </tr>
              <tr class="status-pending">
                <td class="py-3 px-4">
                  <div>
                    <p class="text-sm font-medium text-gray-900">Data Privacy Breach</p>
                    <p class="text-xs text-gray-500">Customer data accessed without authorization</p>
                  </div>
                </td>
                <td class="py-3 px-4">
                  <span class="text-sm font-medium text-gray-700">IT</span>
                </td>
                <td class="py-3 px-4">
                  <span class="px-2 py-1 text-xs risk-high rounded-full">High</span>
                </td>
              </tr>
              <tr class="status-review">
                <td class="py-3 px-4">
                  <div>
                    <p class="text-sm font-medium text-gray-900">SOX Control Failure</p>
                    <p class="text-xs text-gray-500">Segregation of duties violation detected</p>
                  </div>
                </td>
                <td class="py-3 px-4">
                  <span class="text-sm font-medium text-gray-700">Operations</span>
                </td>
                <td class="py-3 px-4">
                  <span class="px-2 py-1 text-xs risk-medium rounded-full">Medium</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="p-4 border-t border-gray-200 text-center">
          <a href="#" class="text-sm text-accent font-medium hover:text-blue-700 transition-smooth">View All Compliance Issues →</a>
        </div>
      </div>

      <!-- Upcoming Compliance Tasks -->
      <div class="bg-white rounded-lg shadow p-5">
        <h3 class="font-semibold text-gray-900 text-base mb-4">Upcoming Compliance Tasks</h3>
        <div class="space-y-3">
          <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-smooth">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 bg-danger-light rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">Quarterly Regulatory Filing</p>
                <p class="text-xs text-gray-500">BSP Quarterly Report submission</p>
              </div>
            </div>
            <span class="text-xs font-medium text-danger bg-danger-light px-2 py-1 rounded">Due: Jun 30</span>
          </div>
          
          <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-smooth">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 bg-warning-light rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">Annual Policy Review</p>
                <p class="text-xs text-gray-500">Update all compliance policies</p>
              </div>
            </div>
            <span class="text-xs font-medium text-warning bg-warning-light px-2 py-1 rounded">Due: Jul 15</span>
          </div>
          
          <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-smooth">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 bg-accent-light rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">Staff Training Sessions</p>
                <p class="text-xs text-gray-500">Compliance training for new hires</p>
              </div>
            </div>
            <span class="text-xs font-medium text-accent bg-accent-light px-2 py-1 rounded">Due: Jul 30</span>
          </div>
        </div>
        
        <!-- Compliance Quick Actions -->
        <div class="mt-6 pt-5 border-t border-gray-200">
          <h4 class="text-sm font-semibold text-gray-900 mb-3">Compliance Quick Actions</h4>
          <div class="grid grid-cols-2 gap-3">
            <a href="#" class="bg-accent text-white p-3 rounded-lg text-center hover:bg-blue-700 transition-smooth">
              <p class="text-xs font-medium">Risk Assessment</p>
            </a>
            <a href="#" class="bg-gray-100 text-gray-700 p-3 rounded-lg text-center hover:bg-gray-200 transition-smooth">
              <p class="text-xs font-medium">Policy Review</p>
            </a>
          </div>
          <div class="grid grid-cols-2 gap-3 mt-3">
            <a href="#" class="bg-green-100 text-green-700 p-3 rounded-lg text-center hover:bg-green-200 transition-smooth">
              <p class="text-xs font-medium">Incident Report</p>
            </a>
            <a href="#" class="bg-purple-100 text-purple-700 p-3 rounded-lg text-center hover:bg-purple-200 transition-smooth">
              <p class="text-xs font-medium">Training Module</p>
            </a>
          </div>
        </div>
        
        <!-- Compliance Statistics -->
        <div class="mt-6 pt-5 border-t border-gray-200">
          <h4 class="text-sm font-semibold text-gray-900 mb-3">Compliance Statistics</h4>
          <div class="grid grid-cols-4 gap-2 text-center">
            <div class="bg-green-50 p-2 rounded">
              <p class="text-lg font-bold text-green-600">94%</p>
              <p class="text-xs text-gray-600">Overall Score</p>
            </div>
            <div class="bg-red-50 p-2 rounded">
              <p class="text-lg font-bold text-red-600">18</p>
              <p class="text-xs text-gray-600">Open Issues</p>
            </div>
            <div class="bg-blue-50 p-2 rounded">
              <p class="text-lg font-bold text-blue-600">42</p>
              <p class="text-xs text-gray-600">Pending Reviews</p>
            </div>
            <div class="bg-yellow-50 p-2 rounded">
              <p class="text-lg font-bold text-yellow-600">6</p>
              <p class="text-xs text-gray-600">Policy Updates</p>
            </div>
          </div>
          <p class="text-xs text-gray-500 mt-3 text-center">Last Audit: May 2024 | Next Audit: Aug 2024</p>
        </div>
      </div>
    </div>
    
    <!-- Regulatory Framework & Policies -->
    <div class="bg-white rounded-lg shadow p-5">
      <h3 class="font-semibold text-gray-900 text-base mb-4">Regulatory Framework Status</h3>
      <div class="overflow-x-auto">
        <table class="w-full min-w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Regulation</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Department</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Last Review</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Next Review</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr>
              <td class="py-2 px-3">
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center">
                    <span class="text-xs font-medium text-blue-600">AML</span>
                  </div>
                  <span class="text-xs font-medium">Anti-Money Laundering</span>
                </div>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs font-medium text-gray-700">Finance, Operations</span>
              </td>
              <td class="py-2 px-3">
                <span class="px-2 py-1 text-xs bg-green-50 text-green-600 rounded-full">Compliant</span>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs text-gray-500">May 15, 2024</span>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs text-gray-500">Aug 15, 2024</span>
              </td>
            </tr>
            <tr>
              <td class="py-2 px-3">
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 bg-purple-100 rounded-full flex items-center justify-center">
                    <span class="text-xs font-medium text-purple-600">GDPR</span>
                  </div>
                  <span class="text-xs font-medium">Data Protection</span>
                </div>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs font-medium text-gray-700">IT, HR, Marketing</span>
              </td>
              <td class="py-2 px-3">
                <span class="px-2 py-1 text-xs bg-yellow-50 text-yellow-600 rounded-full">Needs Update</span>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs text-gray-500">Apr 20, 2024</span>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs text-gray-500">Jul 20, 2024</span>
              </td>
            </tr>
            <tr>
              <td class="py-2 px-3">
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 bg-red-100 rounded-full flex items-center justify-center">
                    <span class="text-xs font-medium text-red-600">SOX</span>
                  </div>
                  <span class="text-xs font-medium">Financial Controls</span>
                </div>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs font-medium text-gray-700">Finance, Audit</span>
              </td>
              <td class="py-2 px-3">
                <span class="px-2 py-1 text-xs bg-green-50 text-green-600 rounded-full">Compliant</span>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs text-gray-500">Jun 1, 2024</span>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs text-gray-500">Sep 1, 2024</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="mt-4 pt-4 border-t">
        <div class="flex flex-col sm:flex-row justify-between items-center gap-3">
          <p class="text-xs text-gray-500">Regulatory Updates: 3 pending | Policy Reviews: 6 required</p>
          <a href="#" class="text-xs text-accent font-medium hover:text-blue-700 transition-smooth">
            View Complete Regulatory Framework →
          </a>
        </div>
      </div>
    </div>
    
    <!-- Training & Certification Status -->
    <div class="bg-white rounded-lg shadow p-5">
      <h3 class="font-semibold text-gray-900 text-base mb-4">Training & Certification Status</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Training Progress -->
        <div class="space-y-3">
          <h4 class="text-sm font-medium text-gray-700">Training Completion</h4>
          <div class="space-y-2">
            <div>
              <div class="flex justify-between text-xs mb-1">
                <span class="text-gray-600">AML Training</span>
                <span class="font-medium">85%</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-accent h-2 rounded-full" style="width: 85%"></div>
              </div>
            </div>
            <div>
              <div class="flex justify-between text-xs mb-1">
                <span class="text-gray-600">Code of Conduct</span>
                <span class="font-medium">92%</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-success h-2 rounded-full" style="width: 92%"></div>
              </div>
            </div>
            <div>
              <div class="flex justify-between text-xs mb-1">
                <span class="text-gray-600">Data Privacy</span>
                <span class="font-medium">78%</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-warning h-2 rounded-full" style="width: 78%"></div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Certification Status -->
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-3">Certification Status</h4>
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-xs text-gray-600">Certified Compliance Officers</span>
              <span class="text-xs font-medium text-green-600">8/10</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-xs text-gray-600">AML Certified Staff</span>
              <span class="text-xs font-medium text-yellow-600">24/30</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-xs text-gray-600">GDPR Certified</span>
              <span class="text-xs font-medium text-red-600">15/25</span>
            </div>
            <div class="pt-2 border-t">
              <div class="flex justify-between items-center">
                <span class="text-xs font-medium text-gray-700">Overall Certification Rate</span>
                <span class="text-xs font-bold text-accent">82%</span>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Upcoming Training -->
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-3">Upcoming Training Sessions</h4>
          <div class="space-y-2">
            <div class="p-2 bg-blue-50 rounded-lg">
              <p class="text-xs font-medium text-gray-900">AML Refresher Course</p>
              <p class="text-xs text-gray-500">Jun 25, 2024 | 25 attendees</p>
            </div>
            <div class="p-2 bg-green-50 rounded-lg">
              <p class="text-xs font-medium text-gray-900">New Hire Compliance</p>
              <p class="text-xs text-gray-500">Jul 5, 2024 | 12 attendees</p>
            </div>
            <div class="p-2 bg-purple-50 rounded-lg">
              <p class="text-xs font-medium text-gray-900">Data Privacy Workshop</p>
              <p class="text-xs text-gray-500">Jul 15, 2024 | 18 attendees</p>
            </div>
          </div>
        </div>
      </div>
      <div class="mt-4 text-center">
        <a href="#" class="text-xs text-accent font-medium hover:text-blue-700 transition-smooth">
          View Training Dashboard & Schedule New Sessions →
        </a>
      </div>
    </div>
  </main>
</div>

<!-- Footer -->
<footer class="bg-white border-t py-3">
  <div class="px-6 flex flex-col sm:flex-row justify-between items-center text-xs text-gray-500">
    <div class="mb-2 sm:mb-0">
      <span>© 2025 Compliance Management System v3.0.1</span>
      <span class="mx-2 hidden sm:inline">•</span>
      <span class="block sm:inline mt-1 sm:mt-0 text-success font-medium">Compliance Framework: ISO 37301 | Next External Audit: Q3 2024</span>
    </div>
    <div class="flex items-center gap-3">
      <a href="#" class="hover:text-accent transition-smooth">Regulatory Library</a>
      <a href="#" class="hover:text-accent transition-smooth">Policy Database</a>
      <a href="#" class="hover:text-accent transition-smooth">Compliance Handbook</a>
    </div>
  </div>
</footer>

<!-- JavaScript -->
<script>
  // Compliance Trend Chart
  const complianceTrendCtx = document.getElementById('complianceTrendChart').getContext('2d');
  const complianceTrendChart = new Chart(complianceTrendCtx, {
    type: 'line',
    data: {
      labels: ['Q1 2023', 'Q2 2023', 'Q3 2023', 'Q4 2023', 'Q1 2024', 'Q2 2024'],
      datasets: [
        {
          label: 'Overall Compliance',
          data: [88, 90, 92, 91, 93, 94],
          borderColor: '#1D4ED8',
          backgroundColor: 'rgba(29, 78, 216, 0.1)',
          borderWidth: 2,
          pointBackgroundColor: '#1D4ED8',
          pointBorderColor: '#ffffff',
          pointBorderWidth: 2,
          pointRadius: 4,
          pointHoverRadius: 6,
          tension: 0.3,
          fill: true
        },
        {
          label: 'Regulatory Compliance',
          data: [85, 87, 90, 89, 91, 92],
          borderColor: '#059669',
          backgroundColor: 'transparent',
          borderWidth: 2,
          pointBackgroundColor: '#059669',
          pointBorderColor: '#ffffff',
          pointBorderWidth: 2,
          pointRadius: 3,
          pointHoverRadius: 5,
          tension: 0.3
        },
        {
          label: 'Internal Policy',
          data: [90, 92, 93, 92, 94, 95],
          borderColor: '#D97706',
          backgroundColor: 'transparent',
          borderWidth: 2,
          pointBackgroundColor: '#D97706',
          pointBorderColor: '#ffffff',
          pointBorderWidth: 2,
          pointRadius: 3,
          pointHoverRadius: 5,
          tension: 0.3
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          backgroundColor: 'rgba(0, 0, 0, 0.7)',
          titleColor: '#ffffff',
          bodyColor: '#ffffff',
          borderColor: '#1D4ED8',
          borderWidth: 1,
          cornerRadius: 4,
          displayColors: false,
          callbacks: {
            label: function(context) {
              return context.dataset.label + ': ' + context.parsed.y + '%';
            }
          }
        }
      },
      scales: {
        y: {
          min: 80,
          max: 100,
          grid: {
            color: 'rgba(0, 0, 0, 0.05)',
            drawBorder: false
          },
          ticks: {
            color: '#6B7280',
            font: {
              size: 11
            },
            padding: 8,
            callback: function(value) {
              return value + '%';
            }
          },
          title: {
            display: true,
            text: 'Compliance Score (%)',
            color: '#6B7280',
            font: {
              size: 11
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
      },
      interaction: {
        intersect: false,
        mode: 'index'
      }
    }
  });

  // Risk Distribution Chart (Doughnut)
  const riskDistributionCtx = document.getElementById('riskDistributionChart').getContext('2d');
  const riskDistributionChart = new Chart(riskDistributionCtx, {
    type: 'doughnut',
    data: {
      labels: ['Critical', 'High', 'Medium', 'Low'],
      datasets: [{
        data: [5, 20, 40, 35],
        backgroundColor: ['#DC2626', '#D97706', '#1D4ED8', '#059669'],
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
          borderColor: '#1D4ED8',
          borderWidth: 1,
          cornerRadius: 4,
          callbacks: {
            label: function(context) {
              const labels = ['Critical', 'High', 'Medium', 'Low'];
              const counts = [3, 12, 24, 21];
              return labels[context.dataIndex] + ': ' + context.parsed + '% (' + counts[context.dataIndex] + ' risks)';
            }
          }
        }
      }
    }
  });

  // Initialize animations and dropdowns
  document.addEventListener('DOMContentLoaded', function() {
    // Add animation to stat cards on load
    document.querySelectorAll('.stat-card').forEach((card, index) => {
      card.style.animationDelay = `${index * 0.1}s`;
      card.classList.add('animate-slide-up');
    });
    
    // Handle dropdown hover behavior for both notifications and profile
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
    
    // Setup both dropdowns
    setupDropdown('.notification-container', '.notification-dropdown');
    setupDropdown('.dropdown-container', '.dropdown');
  });

  // Update charts on window resize
  window.addEventListener('resize', function() {
    complianceTrendChart.resize();
    riskDistributionChart.resize();
  });
</script>

</body>
</html>