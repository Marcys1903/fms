<?php
session_start();
$allowed_role = 'Cashier';
$allowed_level = 3;
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {header("Location: ../login.php?error=unauthorized");exit();}
if ($_SESSION['role'] !== $allowed_role) {header("Location: ../login.php?error=unauthorized");exit();}
if ($_SESSION['level'] !== $allowed_level) {header("Location: ../login.php?error=unauthorized");exit();}
$firstname = $_SESSION['firstname'];
$middlename = $_SESSION['middlename'];
$lastname = $_SESSION['lastname'];
$role = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Cashier Dashboard | Financial Management System</title>
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
            accent: '#9333EA', // Purple for cash/transactions
            success: '#16A34A',
            danger: '#EF4444',
            warning: '#D97706',
            info: '#2563EB',
            navbar: '#7C3AED', // Purple for navbar
            sidebar: '#1E293B',
            'accent-light': '#F3E8FF', // Light purple
            'success-light': '#DCFCE7',
            'danger-light': '#FEE2E2',
            'warning-light': '#FEF3C7',
            'gray-150': '#F3F4F6',
            'cash-green': '#059669',
            'cash-blue': '#0EA5E9',
            'cash-orange': '#F59E0B'
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
    
    /* Payment method indicators */
    .payment-cash { background-color: #DCFCE7; color: #059669; }
    .payment-card { background-color: #DBEAFE; color: #2563EB; }
    .payment-online { background-color: #F3E8FF; color: #9333EA; }
    .payment-check { background-color: #FEF3C7; color: #D97706; }
    
    /* Cash drawer status */
    .drawer-open { 
      background-color: #DCFCE7; 
      color: #059669;
      border-left: 4px solid #059669;
    }
    .drawer-closed { 
      background-color: #FEE2E2; 
      color: #DC2626;
      border-left: 4px solid #DC2626;
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
        <span class="font-bold text-gray-900 text-lg">Cashier Management Hub</span>
        <span class="ml-2 text-xs bg-accent text-white px-2 py-0.5 rounded-full font-semibold">CASHIER</span>
      </div>
    </div>
  </div>
  
  <div class="flex items-center gap-4">
    <!-- Cash Drawer Status -->
    <div class="flex items-center gap-2 px-3 py-1 bg-green-50 rounded-lg border border-green-200">
      <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse-slow"></div>
      <span class="text-xs font-medium text-green-700">Drawer Open</span>
    </div>
    
    <!-- Session Timer -->
    <div class="flex items-center gap-2 px-3 py-1 bg-blue-50 rounded-lg border border-blue-200">
      <svg class="w-3 h-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <span class="text-xs font-medium text-blue-700">04:28:15</span>
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
            <h3 class="font-semibold text-gray-900 text-sm">Cashier Alerts</h3>
            <span class="text-xs bg-accent text-white px-2 py-0.5 rounded-full">3 new</span>
          </div>
        </div>
        
        <div class="max-h-80 overflow-y-auto">
          <!-- Notification items -->
          <a href="#" class="notification-item block px-4 py-3 border-b border-gray-100 hover:bg-gray-50 transition-smooth">
            <div class="flex items-start gap-3">
              <div class="w-8 h-8 bg-warning-light rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg class="w-4 h-4 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">Cash Count Due</p>
                <p class="text-xs text-gray-500 mt-0.5">Mid-day cash count required</p>
                <span class="text-xs text-gray-400 mt-1 block">15 min ago</span>
              </div>
              <div class="w-2 h-2 bg-warning rounded-full flex-shrink-0 mt-1"></div>
            </div>
          </a>
          
          <a href="#" class="notification-item block px-4 py-3 border-b border-gray-100 hover:bg-gray-50 transition-smooth">
            <div class="flex items-start gap-3">
              <div class="w-8 h-8 bg-danger-light rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg class="w-4 h-4 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">Large Cash Transaction</p>
                <p class="text-xs text-gray-500 mt-0.5">₱25,000 cash payment requires manager approval</p>
                <span class="text-xs text-gray-400 mt-1 block">1 hour ago</span>
              </div>
              <div class="w-2 h-2 bg-danger rounded-full flex-shrink-0 mt-1"></div>
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
                <p class="text-sm font-medium text-gray-900 truncate">Shift End Reminder</p>
                <p class="text-xs text-gray-500 mt-0.5">End of shift procedures in 30 minutes</p>
                <span class="text-xs text-gray-400 mt-1 block">2 hours ago</span>
              </div>
            </div>
          </a>
          
          <a href="#" class="notification-item block px-4 py-3 border-b border-gray-100 hover:bg-gray-50 transition-smooth">
            <div class="flex items-start gap-3">
              <div class="w-8 h-8 bg-accent-light rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">Daily Summary Ready</p>
                <p class="text-xs text-gray-500 mt-0.5">Yesterday's cashier summary report</p>
                <span class="text-xs text-gray-400 mt-1 block">Yesterday</span>
              </div>
            </div>
          </a>
          
          <a href="#" class="notification-item block px-4 py-3 hover:bg-gray-50 transition-smooth">
            <div class="flex items-start gap-3">
              <div class="w-8 h-8 bg-cash-blue/10 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg class="w-4 h-4 text-cash-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">Low Change Fund</p>
                <p class="text-xs text-gray-500 mt-0.5">Request additional ₱₱1000 in small bills</p>
                <span class="text-xs text-gray-400 mt-1 block">2 days ago</span>
              </div>
            </div>
          </a>
        </div>
        
        <div class="border-t border-gray-100 pt-2">
          <a href="#" class="block text-center text-sm text-accent font-medium py-2 hover:bg-gray-50 rounded-b-lg transition-smooth">
            View All Cashier Alerts
          </a>
        </div>
      </div>
    </div>
    
    <!-- User Profile with Hover Dropdown -->
    <div class="dropdown-container relative">
      <div class="flex items-center gap-3 border-l border-gray-200 pl-4 cursor-pointer hover:bg-gray-50 px-2 py-1 rounded-lg transition-smooth">
        <div class="text-right">
          <p class="font-medium text-gray-900 text-sm">Sarah Johnson</p>
          <p class="text-xs text-gray-500">Lead Cashier</p>
        </div>
        <div class="relative">
          <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Cashier" class="h-9 w-9 rounded-full border-2 border-accent">
          <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-success rounded-full border-2 border-white"></div>
        </div>
      </div>
      
      <!-- User Profile Dropdown -->
      <div class="dropdown absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 py-2 z-40">
        <div class="px-4 py-2 border-b border-gray-100">
          <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Cashier Account</p>
        </div>
        
        <a href="#" class="dropdown-item flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-smooth">
          <div class="w-5 h-5 text-gray-500">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
          </div>
          <span class="text-sm text-gray-700">Cashier Profile</span>
        </a>
        
        <a href="#" class="dropdown-item flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-smooth">
          <div class="w-5 h-5 text-gray-500">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
          </div>
          <span class="text-sm text-gray-700">Cashier Settings</span>
        </a>
        
        <a href="#" class="dropdown-item flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-smooth">
          <div class="w-5 h-5 text-gray-500">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
          </div>
          <span class="text-sm text-gray-700">Cash Drawer</span>
        </a>
        
        <div class="border-t border-gray-100 pt-2">
          <a href="#" class="flex items-center gap-3 px-4 py-3 hover:bg-warning/10 text-warning transition-smooth">
            <div class="w-5 h-5">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
            </div>
            <span class="text-sm font-medium">End Shift</span>
          </a>
        </div>
        
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
    
    <!-- Cashier Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-accent stat-card">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Today's Sales</p>
            <p class="text-2xl font-bold mt-1">₱48,250</p>
          </div>
          <div class="p-2 bg-accent-light rounded-lg">
            <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
        </div>
        <p class="text-xs text-success mt-2 font-medium">+₱5,250 vs yesterday</p>
      </div>

      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-success stat-card">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Transactions Today</p>
            <p class="text-2xl font-bold mt-1">127</p>
          </div>
          <div class="p-2 bg-success-light rounded-lg">
            <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
          </div>
        </div>
        <p class="text-xs text-gray-500 mt-2">Avg: ₱380 per transaction</p>
      </div>

      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-cash-blue stat-card">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Cash in Drawer</p>
            <p class="text-2xl font-bold mt-1">₱15,780</p>
          </div>
          <div class="p-2 bg-blue-50 rounded-lg">
            <svg class="w-5 h-5 text-cash-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
          </div>
        </div>
        <p class="text-xs text-cash-blue mt-2 font-medium">+₱3,250 from opening</p>
      </div>

      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-warning stat-card">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Pending Voids</p>
            <p class="text-2xl font-bold mt-1">3</p>
          </div>
          <div class="p-2 bg-warning-light rounded-lg">
            <svg class="w-5 h-5 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
            </svg>
          </div>
        </div>
        <p class="text-xs text-warning mt-2 font-medium">Require approval</p>
      </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      
      <!-- Hourly Sales Trend -->
      <div class="bg-white rounded-lg shadow p-5 lg:col-span-2">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-5">
          <div>
            <h3 class="font-semibold text-gray-900 text-base">Today's Hourly Sales</h3>
            <p class="text-sm text-gray-500">Real-time transaction volume</p>
          </div>
          <div class="flex gap-2 mt-2 sm:mt-0">
            <button class="text-xs border border-gray-300 rounded-lg px-3 py-1.5 bg-white hover:bg-gray-50">Today</button>
            <button class="text-xs border border-gray-300 rounded-lg px-3 py-1.5 bg-accent text-white">Yesterday</button>
            <button class="text-xs border border-gray-300 rounded-lg px-3 py-1.5 bg-white hover:bg-gray-50">Week</button>
          </div>
        </div>
        <div class="chart-container">
          <canvas id="hourlySalesChart"></canvas>
        </div>
        <div class="flex items-center justify-center gap-4 mt-4 text-xs">
          <div class="flex items-center gap-1.5">
            <div class="w-3 h-3 rounded-full bg-accent"></div>
            <span class="text-gray-600">Today</span>
          </div>
          <div class="flex items-center gap-1.5">
            <div class="w-3 h-3 rounded-full bg-gray-300"></div>
            <span class="text-gray-600">Yesterday</span>
          </div>
          <div class="flex items-center gap-1.5">
            <div class="w-3 h-3 rounded-full bg-cash-orange"></div>
            <span class="text-gray-600">Average</span>
          </div>
        </div>
      </div>

      <!-- Payment Method Breakdown -->
      <div class="bg-white rounded-lg shadow p-5">
        <h3 class="font-semibold text-gray-900 text-base mb-5">Payment Methods</h3>
        <div class="chart-container">
          <canvas id="paymentMethodChart"></canvas>
        </div>
        <div class="mt-4 space-y-3">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-cash-green"></div>
              <span class="text-xs text-gray-600">Cash</span>
            </div>
            <span class="text-xs font-medium">₱24,125 (50%)</span>
          </div>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-cash-blue"></div>
              <span class="text-xs text-gray-600">Credit Card</span>
            </div>
            <span class="text-xs font-medium">₱14,475 (30%)</span>
          </div>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-accent"></div>
              <span class="text-xs text-gray-600">Digital Wallet</span>
            </div>
            <span class="text-xs font-medium">₱9,650 (20%)</span>
          </div>
        </div>
        <div class="mt-4 p-3 bg-gray-50 rounded-lg">
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-gray-700">Average Transaction:</span>
            <span class="text-xs font-bold text-accent">₱380</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Activities & Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      
      <!-- Recent Transactions -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-5 border-b border-gray-200">
          <h3 class="font-semibold text-gray-900 text-base">Recent Transactions</h3>
          <p class="text-sm text-gray-500">Latest sales and payments</p>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full min-w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Time</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Amount</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Method</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr>
                <td class="py-3 px-4">
                  <div>
                    <p class="text-sm font-medium text-gray-900">14:28</p>
                    <p class="text-xs text-gray-500">TXN-78945</p>
                  </div>
                </td>
                <td class="py-3 px-4">
                  <p class="text-sm font-medium text-gray-900">₱1,250</p>
                </td>
                <td class="py-3 px-4">
                  <span class="px-2 py-1 text-xs payment-card rounded-full">Credit Card</span>
                </td>
                <td class="py-3 px-4">
                  <span class="px-2 py-1 text-xs bg-success-light text-success rounded-full">Completed</span>
                </td>
              </tr>
              <tr>
                <td class="py-3 px-4">
                  <div>
                    <p class="text-sm font-medium text-gray-900">14:15</p>
                    <p class="text-xs text-gray-500">TXN-78944</p>
                  </div>
                </td>
                <td class="py-3 px-4">
                  <p class="text-sm font-medium text-gray-900">₱850</p>
                </td>
                <td class="py-3 px-4">
                  <span class="px-2 py-1 text-xs payment-cash rounded-full">Cash</span>
                </td>
                <td class="py-3 px-4">
                  <span class="px-2 py-1 text-xs bg-success-light text-success rounded-full">Completed</span>
                </td>
              </tr>
              <tr>
                <td class="py-3 px-4">
                  <div>
                    <p class="text-sm font-medium text-gray-900">14:05</p>
                    <p class="text-xs text-gray-500">TXN-78943</p>
                  </div>
                </td>
                <td class="py-3 px-4">
                  <p class="text-sm font-medium text-gray-900">₱2,500</p>
                </td>
                <td class="py-3 px-4">
                  <span class="px-2 py-1 text-xs payment-online rounded-full">Online</span>
                </td>
                <td class="py-3 px-4">
                  <span class="px-2 py-1 text-xs bg-warning-light text-warning rounded-full">Pending</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="p-4 border-t border-gray-200 text-center">
          <a href="#" class="text-sm text-accent font-medium hover:text-purple-700 transition-smooth">View All Transactions →</a>
        </div>
      </div>

      <!-- Cashier Tasks & Actions -->
      <div class="bg-white rounded-lg shadow p-5">
        <h3 class="font-semibold text-gray-900 text-base mb-4">Cashier Tasks</h3>
        <div class="space-y-3">
          <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-smooth">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 bg-warning-light rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">Cash Count Required</p>
                <p class="text-xs text-gray-500">Mid-day cash verification</p>
              </div>
            </div>
            <span class="text-xs font-medium text-warning bg-warning-light px-2 py-1 rounded">Due Now</span>
          </div>
          
          <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-smooth">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 bg-success-light rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">Receipt Paper Check</p>
                <p class="text-xs text-gray-500">Receipt paper level: 65%</p>
              </div>
            </div>
            <span class="text-xs font-medium text-success bg-success-light px-2 py-1 rounded">OK</span>
          </div>
          
          <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-smooth">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 bg-accent-light rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">Shift Report Review</p>
                <p class="text-xs text-gray-500">Yesterday's shift report ready</p>
              </div>
            </div>
            <span class="text-xs font-medium text-accent bg-accent-light px-2 py-1 rounded">Pending</span>
          </div>
        </div>
        
        <!-- Quick Cashier Actions -->
        <div class="mt-6 pt-5 border-t border-gray-200">
          <h4 class="text-sm font-semibold text-gray-900 mb-3">Quick Actions</h4>
          <div class="grid grid-cols-2 gap-3">
            <a href="#" class="bg-accent text-white p-3 rounded-lg text-center hover:bg-purple-700 transition-smooth">
              <p class="text-xs font-medium">New Sale</p>
            </a>
            <a href="#" class="bg-gray-100 text-gray-700 p-3 rounded-lg text-center hover:bg-gray-200 transition-smooth">
              <p class="text-xs font-medium">Cash Count</p>
            </a>
          </div>
          <div class="grid grid-cols-2 gap-3 mt-3">
            <a href="#" class="bg-green-100 text-green-700 p-3 rounded-lg text-center hover:bg-green-200 transition-smooth">
              <p class="text-xs font-medium">Void Transaction</p>
            </a>
            <a href="#" class="bg-blue-100 text-blue-700 p-3 rounded-lg text-center hover:bg-blue-200 transition-smooth">
              <p class="text-xs font-medium">Print Receipt</p>
            </a>
          </div>
        </div>
        
        <!-- Cash Drawer Status -->
        <div class="mt-6 pt-5 border-t border-gray-200">
          <h4 class="text-sm font-semibold text-gray-900 mb-3">Cash Drawer Status</h4>
          <div class="grid grid-cols-4 gap-2 text-center">
            <div class="bg-green-50 p-2 rounded">
              <p class="text-lg font-bold text-green-600">₱5,000</p>
              <p class="text-xs text-gray-600">Opening</p>
            </div>
            <div class="bg-blue-50 p-2 rounded">
              <p class="text-lg font-bold text-blue-600">₱48,250</p>
              <p class="text-xs text-gray-600">Sales</p>
            </div>
            <div class="bg-purple-50 p-2 rounded">
              <p class="text-lg font-bold text-purple-600">₱37,470</p>
              <p class="text-xs text-gray-600">Payments</p>
            </div>
            <div class="bg-gray-100 p-2 rounded">
              <p class="text-lg font-bold text-gray-600">₱15,780</p>
              <p class="text-xs text-gray-600">Current</p>
            </div>
          </div>
          <p class="text-xs text-gray-500 mt-3 text-center">Shift Started: 08:00 AM | Next Count: 03:00 PM</p>
        </div>
      </div>
    </div>
    
    <!-- Bill Breakdown & Change Calculator -->
    <div class="bg-white rounded-lg shadow p-5">
      <h3 class="font-semibold text-gray-900 text-base mb-4">Cash Denomination Calculator</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Bill Breakdown -->
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-3">Current Drawer Breakdown</h4>
          <div class="space-y-2">
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600">₱1,000 Bills</span>
              <span class="text-sm font-medium">8 × ₱1,000 = ₱8,000</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600">₱500 Bills</span>
              <span class="text-sm font-medium">12 × ₱500 = ₱6,000</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600">₱200 Bills</span>
              <span class="text-sm font-medium">5 × ₱200 = ₱1,000</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600">₱100 Bills</span>
              <span class="text-sm font-medium">6 × ₱100 = ₱600</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600">Coins</span>
              <span class="text-sm font-medium">₱180</span>
            </div>
            <div class="pt-2 border-t">
              <div class="flex justify-between items-center">
                <span class="text-sm font-medium text-gray-700">Total Cash</span>
                <span class="text-sm font-bold text-accent">₱15,780</span>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Change Calculator -->
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-3">Change Calculator</h4>
          <div class="space-y-3">
            <div>
              <label class="text-xs text-gray-600">Transaction Amount</label>
              <input type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" value="₱1,250" placeholder="Enter amount">
            </div>
            <div>
              <label class="text-xs text-gray-600">Amount Received</label>
              <input type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" value="₱1,500" placeholder="Enter amount received">
            </div>
            <div class="p-3 bg-green-50 rounded-lg">
              <div class="flex justify-between items-center">
                <span class="text-sm font-medium text-gray-700">Change Due:</span>
                <span class="text-lg font-bold text-green-600">₱250</span>
              </div>
            </div>
            <div class="grid grid-cols-2 gap-2">
              <button class="bg-accent text-white p-2 rounded-lg text-center text-sm hover:bg-purple-700 transition-smooth">
                Calculate
              </button>
              <button class="bg-gray-100 text-gray-700 p-2 rounded-lg text-center text-sm hover:bg-gray-200 transition-smooth">
                Clear
              </button>
            </div>
          </div>
        </div>
      </div>
      <div class="mt-4 pt-4 border-t">
        <p class="text-xs text-gray-500 text-center">Last Cash Count: 08:00 AM | Next Cash Drop: 04:00 PM</p>
      </div>
    </div>
  </main>
</div>

<!-- Footer -->
<footer class="bg-white border-t py-3">
  <div class="px-6 flex flex-col sm:flex-row justify-between items-center text-xs text-gray-500">
    <div class="mb-2 sm:mb-0">
      <span>© 2025 Cashier Management System v2.1.3</span>
      <span class="mx-2 hidden sm:inline">•</span>
      <span class="block sm:inline mt-1 sm:mt-0 text-success font-medium">Shift: Day (08:00-17:00) | Cashier ID: CASH-2024-045</span>
    </div>
    <div class="flex items-center gap-3">
      <a href="#" class="hover:text-accent transition-smooth">Receipt Reprint</a>
      <a href="#" class="hover:text-accent transition-smooth">Void Request</a>
      <a href="#" class="hover:text-accent transition-smooth">Cashier Manual</a>
    </div>
  </div>
</footer>

<!-- JavaScript -->
<script>
  // Hourly Sales Chart
  const hourlySalesCtx = document.getElementById('hourlySalesChart').getContext('2d');
  const hourlySalesChart = new Chart(hourlySalesCtx, {
    type: 'line',
    data: {
      labels: ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00'],
      datasets: [
        {
          label: 'Today',
          data: [1200, 2500, 3200, 2800, 4200, 3800, 3100, 1800],
          borderColor: '#9333EA',
          backgroundColor: 'rgba(147, 51, 234, 0.1)',
          borderWidth: 2,
          pointBackgroundColor: '#9333EA',
          pointBorderColor: '#ffffff',
          pointBorderWidth: 2,
          pointRadius: 4,
          pointHoverRadius: 6,
          tension: 0.3,
          fill: true
        },
        {
          label: 'Yesterday',
          data: [1100, 2300, 3000, 2600, 4000, 3500, 2900, 1600],
          borderColor: '#D1D5DB',
          backgroundColor: 'transparent',
          borderWidth: 1,
          pointBackgroundColor: '#D1D5DB',
          pointBorderColor: '#ffffff',
          pointBorderWidth: 2,
          pointRadius: 3,
          pointHoverRadius: 5,
          tension: 0.3,
          borderDash: [5, 5]
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
          borderColor: '#9333EA',
          borderWidth: 1,
          cornerRadius: 4,
          displayColors: false,
          callbacks: {
            label: function(context) {
              return 'Sales: ₱' + context.parsed.y;
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
            padding: 8,
            callback: function(value) {
              return '₱' + value;
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

  // Payment Method Chart (Doughnut)
  const paymentMethodCtx = document.getElementById('paymentMethodChart').getContext('2d');
  const paymentMethodChart = new Chart(paymentMethodCtx, {
    type: 'doughnut',
    data: {
      labels: ['Cash', 'Credit Card', 'Digital Wallet', 'Check'],
      datasets: [{
        data: [50, 30, 20, 0],
        backgroundColor: ['#059669', '#0EA5E9', '#9333EA', '#D97706'],
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
          borderColor: '#9333EA',
          borderWidth: 1,
          cornerRadius: 4,
          callbacks: {
            label: function(context) {
              let amount = '';
              if(context.label === 'Cash') amount = '₱24,125';
              if(context.label === 'Credit Card') amount = '₱14,475';
              if(context.label === 'Digital Wallet') amount = '₱9,650';
              return context.label + ': ' + context.parsed + '% (' + amount + ')';
            }
          }
        }
      }
    }
  });

  // Change Calculator Functionality
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
    
    // Simple change calculator
    const transactionInput = document.querySelector('input[placeholder="Enter amount"]');
    const receivedInput = document.querySelector('input[placeholder="Enter amount received"]');
    const changeDisplay = document.querySelector('.text-lg.font-bold.text-green-600');
    const calculateBtn = document.querySelector('button:contains("Calculate")');
    const clearBtn = document.querySelector('button:contains("Clear")');
    
    if (calculateBtn) {
      calculateBtn.addEventListener('click', function() {
        let transaction = parseFloat(transactionInput.value.replace('₱', '').replace(',', '')) || 0;
        let received = parseFloat(receivedInput.value.replace('₱', '').replace(',', '')) || 0;
        
        if (received >= transaction && transaction > 0) {
          const change = received - transaction;
          changeDisplay.textContent = '₱' + change.toLocaleString();
        } else if (transaction > 0) {
          changeDisplay.textContent = 'Insufficient';
          changeDisplay.classList.remove('text-green-600');
          changeDisplay.classList.add('text-red-600');
        }
      });
    }
    
    if (clearBtn) {
      clearBtn.addEventListener('click', function() {
        transactionInput.value = '';
        receivedInput.value = '';
        changeDisplay.textContent = '₱0';
        changeDisplay.classList.remove('text-red-600');
        changeDisplay.classList.add('text-green-600');
      });
    }
  });

  // Update charts on window resize
  window.addEventListener('resize', function() {
    hourlySalesChart.resize();
    paymentMethodChart.resize();
  });
</script>

</body>
</html>