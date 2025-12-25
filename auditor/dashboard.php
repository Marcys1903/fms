<?php
session_start();
// Add authentication check here
// if (!isset($_SESSION['auditor'])) {
//     header('Location: login.php');
//     exit();
// }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Auditor Dashboard | Financial Management System</title>
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
            accent: '#DC2626', // Red for audit/risk/compliance
            success: '#16A34A', // Darker green
            danger: '#EF4444',
            warning: '#D97706', // Amber for warnings
            info: '#2563EB',
            navbar: '#B91C1C', // Dark red for navbar
            sidebar: '#1E293B',
            'accent-light': '#FEE2E2', // Light red
            'success-light': '#DCFCE7',
            'danger-light': '#FEE2E2',
            'warning-light': '#FEF3C7',
            'gray-150': '#F3F4F6',
            'audit-blue': '#0EA5E9',
            'audit-purple': '#7C3AED',
            'compliance-green': '#059669'
          },
          fontFamily: {
            'inter': ['Inter', 'system-ui', 'sans-serif']
          },
          animation: {
            'fade-in': 'fadeIn 0.2s ease-in-out',
            'slide-up': 'slideUp 0.3s ease-out'
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
    
    /* Risk level indicators */
    .risk-high { background-color: #FEE2E2; color: #DC2626; border-left: 4px solid #DC2626; }
    .risk-medium { background-color: #FEF3C7; color: #D97706; border-left: 4px solid #D97706; }
    .risk-low { background-color: #DCFCE7; color: #16A34A; border-left: 4px solid #16A34A; }
    .risk-info { background-color: #DBEAFE; color: #2563EB; border-left: 4px solid #2563EB; }
  </style>
</head>

<body class="bg-gray-50 h-screen overflow-hidden font-inter">

<!-- Header -->
<header class="bg-white shadow-sm h-16 flex items-center justify-between px-6 fixed top-0 left-0 right-0 z-30 border-b border-gray-200">
  <div class="flex items-center gap-3">
    <div class="flex items-center gap-2">
      <img src="../assets/bcpnobg.png" class="h-8 w-8" alt="BCP Logo">
      <div>
        <span class="font-bold text-gray-900 text-lg">Audit & Compliance Hub</span>
        <span class="ml-2 text-xs bg-accent text-white px-2 py-0.5 rounded-full font-semibold">AUDITOR</span>
      </div>
    </div>
  </div>
  
  <div class="flex items-center gap-4">
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
            <h3 class="font-semibold text-gray-900 text-sm">Audit Alerts</h3>
            <span class="text-xs bg-accent text-white px-2 py-0.5 rounded-full">6 new</span>
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
                <p class="text-sm font-medium text-gray-900 truncate">High Risk Transaction</p>
                <p class="text-xs text-gray-500 mt-0.5">Large payment without approval trail</p>
                <span class="text-xs text-gray-400 mt-1 block">1 hour ago</span>
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
                <p class="text-sm font-medium text-gray-900 truncate">SOX Compliance Due</p>
                <p class="text-xs text-gray-500 mt-0.5">Quarter 2 controls testing deadline</p>
                <span class="text-xs text-gray-400 mt-1 block">3 days ago</span>
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
                <p class="text-sm font-medium text-gray-900 truncate">Audit Report Ready</p>
                <p class="text-xs text-gray-500 mt-0.5">Internal audit Q2 2024 completed</p>
                <span class="text-xs text-gray-400 mt-1 block">5 days ago</span>
              </div>
            </div>
          </a>
          
          <a href="#" class="notification-item block px-4 py-3 border-b border-gray-100 hover:bg-gray-50 transition-smooth">
            <div class="flex items-start gap-3">
              <div class="w-8 h-8 bg-accent-light rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">Exception Found</p>
                <p class="text-xs text-gray-500 mt-0.5">3 journal entries lack supporting docs</p>
                <span class="text-xs text-gray-400 mt-1 block">1 week ago</span>
              </div>
            </div>
          </a>
          
          <a href="#" class="notification-item block px-4 py-3 hover:bg-gray-50 transition-smooth">
            <div class="flex items-start gap-3">
              <div class="w-8 h-8 bg-audit-blue/10 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg class="w-4 h-4 text-audit-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">Follow-up Required</p>
                <p class="text-xs text-gray-500 mt-0.5">Management response pending for 2 findings</p>
                <span class="text-xs text-gray-400 mt-1 block">2 weeks ago</span>
              </div>
            </div>
          </a>
        </div>
        
        <div class="border-t border-gray-100 pt-2">
          <a href="#" class="block text-center text-sm text-accent font-medium py-2 hover:bg-gray-50 rounded-b-lg transition-smooth">
            View All Audit Alerts
          </a>
        </div>
      </div>
    </div>
    
    <!-- User Profile with Hover Dropdown -->
    <div class="dropdown-container relative">
      <div class="flex items-center gap-3 border-l border-gray-200 pl-4 cursor-pointer hover:bg-gray-50 px-2 py-1 rounded-lg transition-smooth">
        <div class="text-right">
          <p class="font-medium text-gray-900 text-sm">Elizabeth Walker</p>
          <p class="text-xs text-gray-500">Senior Auditor</p>
        </div>
        <div class="relative">
          <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Auditor" class="h-9 w-9 rounded-full border-2 border-accent">
          <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-success rounded-full border-2 border-white"></div>
        </div>
      </div>
      
      <!-- User Profile Dropdown -->
      <div class="dropdown absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 py-2 z-40">
        <div class="px-4 py-2 border-b border-gray-100">
          <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Audit Account</p>
        </div>
        
        <a href="#" class="dropdown-item flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-smooth">
          <div class="w-5 h-5 text-gray-500">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
          </div>
          <span class="text-sm text-gray-700">Auditor Profile</span>
        </a>
        
        <a href="#" class="dropdown-item flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-smooth">
          <div class="w-5 h-5 text-gray-500">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
          </div>
          <span class="text-sm text-gray-700">Audit Settings</span>
        </a>
        
        <a href="#" class="dropdown-item flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-smooth">
          <div class="w-5 h-5 text-gray-500">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <span class="text-sm text-gray-700">Compliance Dashboard</span>
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
    
    <!-- Audit Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-accent stat-card">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Open Findings</p>
            <p class="text-2xl font-bold mt-1">18</p>
          </div>
          <div class="p-2 bg-accent-light rounded-lg">
            <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
          </div>
        </div>
        <p class="text-xs text-danger mt-2 font-medium">5 high risk</p>
      </div>

      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-warning stat-card">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Audits in Progress</p>
            <p class="text-2xl font-bold mt-1">6</p>
          </div>
          <div class="p-2 bg-warning-light rounded-lg">
            <svg class="w-5 h-5 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
        </div>
        <p class="text-xs text-warning mt-2">2 overdue</p>
      </div>

      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-success stat-card">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Compliance Rate</p>
            <p class="text-2xl font-bold mt-1">92%</p>
          </div>
          <div class="p-2 bg-success-light rounded-lg">
            <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
        </div>
        <p class="text-xs text-gray-500 mt-2">+3% this quarter</p>
      </div>

      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-audit-blue stat-card">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Samples Tested</p>
            <p class="text-2xl font-bold mt-1">245</p>
          </div>
          <div class="p-2 bg-blue-50 rounded-lg">
            <svg class="w-5 h-5 text-audit-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
          </div>
        </div>
        <p class="text-xs text-audit-blue mt-2 font-medium">This quarter</p>
      </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      
      <!-- Risk Assessment Trend -->
      <div class="bg-white rounded-lg shadow p-5 lg:col-span-2">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-5">
          <div>
            <h3 class="font-semibold text-gray-900 text-base">Risk Assessment Trend</h3>
            <p class="text-sm text-gray-500">Quarterly risk findings by category</p>
          </div>
          <select class="text-xs border border-gray-300 rounded-lg px-3 py-1.5 bg-white mt-2 sm:mt-0">
            <option>Last 4 quarters</option>
            <option selected>Year to date</option>
            <option>Last 2 years</option>
          </select>
        </div>
        <div class="chart-container">
          <canvas id="riskChart"></canvas>
        </div>
        <div class="flex items-center justify-center gap-4 mt-4 text-xs">
          <div class="flex items-center gap-1.5">
            <div class="w-3 h-3 rounded-full bg-danger"></div>
            <span class="text-gray-600">High Risk</span>
          </div>
          <div class="flex items-center gap-1.5">
            <div class="w-3 h-3 rounded-full bg-warning"></div>
            <span class="text-gray-600">Medium Risk</span>
          </div>
          <div class="flex items-center gap-1.5">
            <div class="w-3 h-3 rounded-full bg-success"></div>
            <span class="text-gray-600">Low Risk</span>
          </div>
        </div>
      </div>

      <!-- Compliance Status -->
      <div class="bg-white rounded-lg shadow p-5">
        <h3 class="font-semibold text-gray-900 text-base mb-5">Compliance Status</h3>
        <div class="chart-container">
          <canvas id="complianceChart"></canvas>
        </div>
        <div class="mt-4 space-y-3">
          <div>
            <div class="flex justify-between text-xs mb-1">
              <span class="text-gray-600">SOX Controls</span>
              <span class="font-medium">95%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-1.5">
              <div class="bg-compliance-green h-1.5 rounded-full" style="width: 95%"></div>
            </div>
          </div>
          <div>
            <div class="flex justify-between text-xs mb-1">
              <span class="text-gray-600">Internal Policies</span>
              <span class="font-medium">88%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-1.5">
              <div class="bg-warning h-1.5 rounded-full" style="width: 88%"></div>
            </div>
          </div>
          <div>
            <div class="flex justify-between text-xs mb-1">
              <span class="text-gray-600">Regulatory Requirements</span>
              <span class="font-medium">92%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-1.5">
              <div class="bg-success h-1.5 rounded-full" style="width: 92%"></div>
            </div>
          </div>
        </div>
        <p class="text-xs text-gray-500 mt-4 text-center">Overall Compliance: 92%</p>
      </div>
    </div>

    <!-- Recent Activities & Audit Findings -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      
      <!-- Recent Audit Exceptions -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-5 border-b border-gray-200">
          <h3 class="font-semibold text-gray-900 text-base">Recent Audit Exceptions</h3>
          <p class="text-sm text-gray-500">Latest findings requiring attention</p>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full min-w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Exception</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Risk Level</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr class="risk-high">
                <td class="py-3 px-4">
                  <div>
                    <p class="text-sm font-medium text-gray-900">Segregation of Duties Violation</p>
                    <p class="text-xs text-gray-500">User has both approver and processor roles</p>
                  </div>
                </td>
                <td class="py-3 px-4">
                  <span class="px-2 py-1 text-xs bg-danger-light text-danger rounded-full font-medium">High</span>
                </td>
                <td class="py-3 px-4">
                  <span class="text-xs font-medium text-gray-700">Open</span>
                </td>
              </tr>
              <tr class="risk-medium">
                <td class="py-3 px-4">
                  <div>
                    <p class="text-sm font-medium text-gray-900">Missing Documentation</p>
                    <p class="text-xs text-gray-500">3 invoices lack supporting contracts</p>
                  </div>
                </td>
                <td class="py-3 px-4">
                  <span class="px-2 py-1 text-xs bg-warning-light text-warning rounded-full font-medium">Medium</span>
                </td>
                <td class="py-3 px-4">
                  <span class="text-xs font-medium text-gray-700">In Progress</span>
                </td>
              </tr>
              <tr class="risk-low">
                <td class="py-3 px-4">
                  <div>
                    <p class="text-sm font-medium text-gray-900">Late Reconciliation</p>
                    <p class="text-xs text-gray-500">Bank rec completed 5 days past deadline</p>
                  </div>
                </td>
                <td class="py-3 px-4">
                  <span class="px-2 py-1 text-xs bg-success-light text-success rounded-full font-medium">Low</span>
                </td>
                <td class="py-3 px-4">
                  <span class="text-xs font-medium text-success">Resolved</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="p-4 border-t border-gray-200 text-center">
          <a href="#" class="text-sm text-accent font-medium hover:text-red-700 transition-smooth">View All Audit Findings →</a>
        </div>
      </div>

      <!-- Upcoming Audits & Tasks -->
      <div class="bg-white rounded-lg shadow p-5">
        <h3 class="font-semibold text-gray-900 text-base mb-4">Upcoming Audits</h3>
        <div class="space-y-3">
          <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-smooth">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 bg-danger-light rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">Financial Statement Audit</p>
                <p class="text-xs text-gray-500">Annual external audit - Big 4 firm</p>
              </div>
            </div>
            <span class="text-xs font-medium text-danger bg-danger-light px-2 py-1 rounded">Starts: Jun 15</span>
          </div>
          
          <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-smooth">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 bg-warning-light rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">IT Controls Review</p>
                <p class="text-xs text-gray-500">Cybersecurity and access controls</p>
              </div>
            </div>
            <span class="text-xs font-medium text-warning bg-warning-light px-2 py-1 rounded">Starts: Jun 25</span>
          </div>
          
          <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-smooth">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 bg-audit-blue/10 rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-audit-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">Vendor Compliance Audit</p>
                <p class="text-xs text-gray-500">Top 5 vendor contract compliance</p>
              </div>
            </div>
            <span class="text-xs font-medium text-audit-blue bg-blue-50 px-2 py-1 rounded">Starts: Jul 5</span>
          </div>
        </div>
        
        <!-- Audit Quick Actions -->
        <div class="mt-6 pt-5 border-t border-gray-200">
          <h4 class="text-sm font-semibold text-gray-900 mb-3">Audit Quick Actions</h4>
          <div class="grid grid-cols-2 gap-3">
            <a href="#" class="bg-accent text-white p-3 rounded-lg text-center hover:bg-red-700 transition-smooth">
              <p class="text-xs font-medium">Create Audit Plan</p>
            </a>
            <a href="#" class="bg-gray-100 text-gray-700 p-3 rounded-lg text-center hover:bg-gray-200 transition-smooth">
              <p class="text-xs font-medium">Risk Assessment</p>
            </a>
          </div>
        </div>
        
        <!-- Audit Statistics -->
        <div class="mt-6 pt-5 border-t border-gray-200">
          <h4 class="text-sm font-semibold text-gray-900 mb-3">Audit Statistics</h4>
          <div class="grid grid-cols-3 gap-2 text-center">
            <div class="bg-red-50 p-2 rounded">
              <p class="text-lg font-bold text-red-600">5</p>
              <p class="text-xs text-gray-600">High Risk</p>
            </div>
            <div class="bg-yellow-50 p-2 rounded">
              <p class="text-lg font-bold text-yellow-600">8</p>
              <p class="text-xs text-gray-600">Medium Risk</p>
            </div>
            <div class="bg-green-50 p-2 rounded">
              <p class="text-lg font-bold text-green-600">5</p>
              <p class="text-xs text-gray-600">Low Risk</p>
            </div>
          </div>
          <p class="text-xs text-gray-500 mt-3 text-center">Total Findings: 18 | Avg Resolution: 14 days</p>
        </div>
      </div>
    </div>
    
    <!-- Audit Trail Section -->
    <div class="bg-white rounded-lg shadow p-5">
      <h3 class="font-semibold text-gray-900 text-base mb-4">Recent Audit Trail Activity</h3>
      <div class="overflow-x-auto">
        <table class="w-full min-w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">User</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Action</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Entity</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Timestamp</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">IP Address</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr>
              <td class="py-2 px-3">
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center">
                    <span class="text-xs font-medium text-blue-600">JS</span>
                  </div>
                  <span class="text-xs font-medium">John Smith</span>
                </div>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs bg-blue-50 text-blue-600 px-2 py-0.5 rounded">Modified</span>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs text-gray-700">Invoice #INV-2024-189</span>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs text-gray-500">06/15/24 14:30</span>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs text-gray-500">192.168.1.105</span>
              </td>
            </tr>
            <tr>
              <td class="py-2 px-3">
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 bg-purple-100 rounded-full flex items-center justify-center">
                    <span class="text-xs font-medium text-purple-600">MW</span>
                  </div>
                  <span class="text-xs font-medium">Maria Wilson</span>
                </div>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs bg-green-50 text-green-600 px-2 py-0.5 rounded">Approved</span>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs text-gray-700">Expense Report #EXP-045</span>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs text-gray-500">06/15/24 11:15</span>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs text-gray-500">192.168.1.112</span>
              </td>
            </tr>
            <tr>
              <td class="py-2 px-3">
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 bg-red-100 rounded-full flex items-center justify-center">
                    <span class="text-xs font-medium text-red-600">RT</span>
                  </div>
                  <span class="text-xs font-medium">Robert Taylor</span>
                </div>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs bg-red-50 text-red-600 px-2 py-0.5 rounded">Deleted</span>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs text-gray-700">Journal Entry #JE-782</span>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs text-gray-500">06/14/24 16:45</span>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs text-gray-500">192.168.1.108</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="mt-4 text-center">
        <a href="#" class="text-xs text-accent font-medium hover:text-red-700 transition-smooth">View Full Audit Trail →</a>
      </div>
    </div>
  </main>
</div>

<!-- Footer -->
<footer class="bg-white border-t py-3">
  <div class="px-6 flex flex-col sm:flex-row justify-between items-center text-xs text-gray-500">
    <div class="mb-2 sm:mb-0">
      <span>© 2025 Audit & Compliance System v4.0.1</span>
      <span class="mx-2 hidden sm:inline">•</span>
      <span class="block sm:inline mt-1 sm:mt-0 text-success font-medium">Audit Cycle: Q2 2024 | Next Review: Q3 2024</span>
    </div>
    <div class="flex items-center gap-3">
      <a href="#" class="hover:text-accent transition-smooth">SOX Compliance</a>
      <a href="#" class="hover:text-accent transition-smooth">Risk Framework</a>
      <a href="#" class="hover:text-accent transition-smooth">Audit Manual</a>
    </div>
  </div>
</footer>

<!-- JavaScript -->
<script>
  // Risk Assessment Chart
  const riskCtx = document.getElementById('riskChart').getContext('2d');
  const riskChart = new Chart(riskCtx, {
    type: 'bar',
    data: {
      labels: ['Q1 2023', 'Q2 2023', 'Q3 2023', 'Q4 2023', 'Q1 2024', 'Q2 2024'],
      datasets: [
        {
          label: 'High Risk',
          data: [8, 6, 7, 5, 6, 5],
          backgroundColor: '#DC2626',
          borderColor: '#DC2626',
          borderWidth: 1
        },
        {
          label: 'Medium Risk',
          data: [12, 10, 11, 9, 10, 8],
          backgroundColor: '#D97706',
          borderColor: '#D97706',
          borderWidth: 1
        },
        {
          label: 'Low Risk',
          data: [15, 14, 16, 13, 14, 12],
          backgroundColor: '#16A34A',
          borderColor: '#16A34A',
          borderWidth: 1
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
          borderColor: '#DC2626',
          borderWidth: 1,
          cornerRadius: 4,
          displayColors: false
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
            padding: 8
          },
          title: {
            display: true,
            text: 'Number of Findings',
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

  // Compliance Status Chart (Doughnut)
  const complianceCtx = document.getElementById('complianceChart').getContext('2d');
  const complianceChart = new Chart(complianceCtx, {
    type: 'doughnut',
    data: {
      labels: ['Compliant', 'Non-Compliant', 'In Progress'],
      datasets: [{
        data: [92, 5, 3],
        backgroundColor: ['#059669', '#DC2626', '#D97706'],
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
          borderColor: '#DC2626',
          borderWidth: 1,
          cornerRadius: 4,
          callbacks: {
            label: function(context) {
              return context.label + ': ' + context.parsed + '%';
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
    riskChart.resize();
    complianceChart.resize();
  });
</script>

</body>
</html>