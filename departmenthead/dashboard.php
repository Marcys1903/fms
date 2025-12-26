<?php
session_start();
$allowed_role = 'Department Head';
$allowed_level = 4;
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
  <title>Department Head Dashboard | Financial Management System</title>
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
            accent: '#7C3AED', // Purple for leadership/management
            success: '#059669',
            danger: '#DC2626',
            warning: '#D97706',
            info: '#0EA5E9',
            navbar: '#5B21B6', // Dark purple for navbar
            sidebar: '#1E293B',
            'accent-light': '#F3E8FF', // Light purple
            'success-light': '#DCFCE7',
            'danger-light': '#FEE2E2',
            'warning-light': '#FEF3C7',
            'gray-150': '#F3F4F6',
            'dept-blue': '#1D4ED8',
            'dept-green': '#059669',
            'dept-orange': '#EA580C',
            'dept-teal': '#0D9488'
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
    
    /* Performance indicators */
    .performance-excellent { background-color: #DCFCE7; color: #059669; }
    .performance-good { background-color: #DBEAFE; color: #1D4ED8; }
    .performance-fair { background-color: #FEF3C7; color: #D97706; }
    .performance-poor { background-color: #FEE2E2; color: #DC2626; }
    
    /* Status indicators */
    .status-on-track { border-left: 4px solid #059669; }
    .status-at-risk { border-left: 4px solid #D97706; }
    .status-behind { border-left: 4px solid #DC2626; }
    .status-completed { border-left: 4px solid #1D4ED8; }
  </style>
</head>

<body class="bg-gray-50 h-screen overflow-hidden font-inter">

<!-- Header -->
<header class="bg-white shadow-sm h-16 flex items-center justify-between px-6 fixed top-0 left-0 right-0 z-30 border-b border-gray-200">
  <div class="flex items-center gap-3">
    <div class="flex items-center gap-2">
      <img src="../assets/bcpnobg.png" class="h-8 w-8" alt="BCP Logo">
      <div>
        <span class="font-bold text-gray-900 text-lg">Department Management Hub</span>
        <span class="ml-2 text-xs bg-accent text-white px-2 py-0.5 rounded-full font-semibold">DEPARTMENT HEAD</span>
      </div>
    </div>
  </div>
  
  <div class="flex items-center gap-4">
    <!-- Department Status -->
    <div class="flex items-center gap-2 px-3 py-1 bg-green-50 rounded-lg border border-green-200">
      <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse-slow"></div>
      <span class="text-xs font-medium text-green-700">Operations Active</span>
    </div>
    
    <!-- Team Availability -->
    <div class="flex items-center gap-2 px-3 py-1 bg-blue-50 rounded-lg border border-blue-200">
      <svg class="w-3 h-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5 3.75a6 6 0 00-9.5-4.197"/>
      </svg>
      <span class="text-xs font-medium text-blue-700">14/16 Team Members</span>
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
            <h3 class="font-semibold text-gray-900 text-sm">Department Alerts</h3>
            <span class="text-xs bg-accent text-white px-2 py-0.5 rounded-full">5 new</span>
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
                <p class="text-sm font-medium text-gray-900 truncate">Project Deadline</p>
                <p class="text-xs text-gray-500 mt-0.5">Q2 Operations Report due tomorrow</p>
                <span class="text-xs text-gray-400 mt-1 block">2 hours ago</span>
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
                <p class="text-sm font-medium text-gray-900 truncate">Budget Overrun Alert</p>
                <p class="text-xs text-gray-500 mt-0.5">Department 8% over Q2 budget</p>
                <span class="text-xs text-gray-400 mt-1 block">5 hours ago</span>
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
                <p class="text-sm font-medium text-gray-900 truncate">Team Achievement</p>
                <p class="text-xs text-gray-500 mt-0.5">Department exceeded Q2 targets</p>
                <span class="text-xs text-gray-400 mt-1 block">1 day ago</span>
              </div>
            </div>
          </a>
          
          <a href="#" class="notification-item block px-4 py-3 border-b border-gray-100 hover:bg-gray-50 transition-smooth">
            <div class="flex items-start gap-3">
              <div class="w-8 h-8 bg-accent-light rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">Performance Review Due</p>
                <p class="text-xs text-gray-500 mt-0.5">3 team members need Q2 reviews</p>
                <span class="text-xs text-gray-400 mt-1 block">2 days ago</span>
              </div>
            </div>
          </a>
          
          <a href="#" class="notification-item block px-4 py-3 hover:bg-gray-50 transition-smooth">
            <div class="flex items-start gap-3">
              <div class="w-8 h-8 bg-dept-blue/10 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg class="w-4 h-4 text-dept-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">Resource Request Approved</p>
                <p class="text-xs text-gray-500 mt-0.5">Additional ₱150,000 budget approved</p>
                <span class="text-xs text-gray-400 mt-1 block">3 days ago</span>
              </div>
            </div>
          </a>
        </div>
        
        <div class="border-t border-gray-100 pt-2">
          <a href="#" class="block text-center text-sm text-accent font-medium py-2 hover:bg-gray-50 rounded-b-lg transition-smooth">
            View All Department Alerts
          </a>
        </div>
      </div>
    </div>
    
    <!-- User Profile with Hover Dropdown -->
    <div class="dropdown-container relative">
      <div class="flex items-center gap-3 border-l border-gray-200 pl-4 cursor-pointer hover:bg-gray-50 px-2 py-1 rounded-lg transition-smooth">
        <div class="text-right">
          <p class="font-medium text-gray-900 text-sm">Maria Santos</p>
          <p class="text-xs text-gray-500">Head of Operations</p>
        </div>
        <div class="relative">
          <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=DepartmentHead" class="h-9 w-9 rounded-full border-2 border-accent">
          <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-success rounded-full border-2 border-white"></div>
        </div>
      </div>
      
      <!-- User Profile Dropdown -->
      <div class="dropdown absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 py-2 z-40">
        <div class="px-4 py-2 border-b border-gray-100">
          <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Department Account</p>
        </div>
        
        <a href="#" class="dropdown-item flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-smooth">
          <div class="w-5 h-5 text-gray-500">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
          </div>
          <span class="text-sm text-gray-700">Department Profile</span>
        </a>
        
        <a href="#" class="dropdown-item flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-smooth">
          <div class="w-5 h-5 text-gray-500">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
          </div>
          <span class="text-sm text-gray-700">Department Settings</span>
        </a>
        
        <a href="#" class="dropdown-item flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-smooth">
          <div class="w-5 h-5 text-gray-500">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
          </div>
          <span class="text-sm text-gray-700">Performance Reports</span>
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
    
    <!-- Department Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-accent stat-card">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Department Budget</p>
            <p class="text-2xl font-bold mt-1">₱8.5M</p>
          </div>
          <div class="p-2 bg-accent-light rounded-lg">
            <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
        </div>
        <p class="text-xs text-warning mt-2 font-medium">8% over budget</p>
      </div>

      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-success stat-card">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Team Productivity</p>
            <p class="text-2xl font-bold mt-1">92%</p>
          </div>
          <div class="p-2 bg-success-light rounded-lg">
            <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
        </div>
        <p class="text-xs text-success mt-2 font-medium">+5% from last month</p>
      </div>

      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-dept-blue stat-card">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Active Projects</p>
            <p class="text-2xl font-bold mt-1">12</p>
          </div>
          <div class="p-2 bg-blue-50 rounded-lg">
            <svg class="w-5 h-5 text-dept-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
          </div>
        </div>
        <p class="text-xs text-gray-500 mt-2">3 behind schedule</p>
      </div>

      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-warning stat-card">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Pending Approvals</p>
            <p class="text-2xl font-bold mt-1">8</p>
          </div>
          <div class="p-2 bg-warning-light rounded-lg">
            <svg class="w-5 h-5 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 01118 0z"/>
            </svg>
          </div>
        </div>
        <p class="text-xs text-warning mt-2 font-medium">2 urgent</p>
      </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      
      <!-- Department Performance Trend -->
      <div class="bg-white rounded-lg shadow p-5 lg:col-span-2">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-5">
          <div>
            <h3 class="font-semibold text-gray-900 text-base">Department Performance Trend</h3>
            <p class="text-sm text-gray-500">Monthly KPIs vs targets</p>
          </div>
          <select class="text-xs border border-gray-300 rounded-lg px-3 py-1.5 bg-white mt-2 sm:mt-0">
            <option>Last 3 months</option>
            <option selected>Year to date</option>
            <option>Last 12 months</option>
          </select>
        </div>
        <div class="chart-container">
          <canvas id="performanceChart"></canvas>
        </div>
        <div class="flex items-center justify-center gap-4 mt-4 text-xs">
          <div class="flex items-center gap-1.5">
            <div class="w-3 h-3 rounded-full bg-accent"></div>
            <span class="text-gray-600">Actual Performance</span>
          </div>
          <div class="flex items-center gap-1.5">
            <div class="w-3 h-3 rounded-full bg-dept-teal"></div>
            <span class="text-gray-600">Target</span>
          </div>
          <div class="flex items-center gap-1.5">
            <div class="w-3 h-3 rounded-full bg-dept-orange"></div>
            <span class="text-gray-600">Department Average</span>
          </div>
        </div>
      </div>

      <!-- Team Performance Distribution -->
      <div class="bg-white rounded-lg shadow p-5">
        <h3 class="font-semibold text-gray-900 text-base mb-5">Team Performance Distribution</h3>
        <div class="chart-container">
          <canvas id="teamPerformanceChart"></canvas>
        </div>
        <div class="mt-4 space-y-3">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-success"></div>
              <span class="text-xs text-gray-600">Exceeding Expectations</span>
            </div>
            <span class="text-xs font-medium">6 members</span>
          </div>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-dept-blue"></div>
              <span class="text-xs text-gray-600">Meeting Expectations</span>
            </div>
            <span class="text-xs font-medium">7 members</span>
          </div>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-warning"></div>
              <span class="text-xs text-gray-600">Needs Improvement</span>
            </div>
            <span class="text-xs font-medium">2 members</span>
          </div>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-gray-400"></div>
              <span class="text-xs text-gray-600">On Leave</span>
            </div>
            <span class="text-xs font-medium">1 member</span>
          </div>
        </div>
        <p class="text-xs text-gray-500 mt-4 text-center">Overall Team Rating: 4.2/5.0</p>
      </div>
    </div>

    <!-- Recent Activities & Team Management -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      
      <!-- Recent Department Activities -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-5 border-b border-gray-200">
          <h3 class="font-semibold text-gray-900 text-base">Recent Department Activities</h3>
          <p class="text-sm text-gray-500">Latest operations and achievements</p>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full min-w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Activity</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Team Member</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr class="status-on-track">
                <td class="py-3 px-4">
                  <div>
                    <p class="text-sm font-medium text-gray-900">Q2 Operations Report</p>
                    <p class="text-xs text-gray-500">Monthly performance analysis</p>
                  </div>
                </td>
                <td class="py-3 px-4">
                  <div class="flex items-center gap-2">
                    <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center">
                      <span class="text-xs font-medium text-blue-600">JR</span>
                    </div>
                    <span class="text-sm text-gray-700">John Reyes</span>
                  </div>
                </td>
                <td class="py-3 px-4">
                  <span class="px-2 py-1 text-xs bg-green-50 text-green-600 rounded-full">Completed</span>
                </td>
              </tr>
              <tr class="status-at-risk">
                <td class="py-3 px-4">
                  <div>
                    <p class="text-sm font-medium text-gray-900">Process Optimization Project</p>
                    <p class="text-xs text-gray-500">Phase 2 implementation</p>
                  </div>
                </td>
                <td class="py-3 px-4">
                  <div class="flex items-center gap-2">
                    <div class="w-6 h-6 bg-purple-100 rounded-full flex items-center justify-center">
                      <span class="text-xs font-medium text-purple-600">SM</span>
                    </div>
                    <span class="text-sm text-gray-700">Sarah Mendoza</span>
                  </div>
                </td>
                <td class="py-3 px-4">
                  <span class="px-2 py-1 text-xs bg-yellow-50 text-yellow-600 rounded-full">At Risk</span>
                </td>
              </tr>
              <tr class="status-behind">
                <td class="py-3 px-4">
                  <div>
                    <p class="text-sm font-medium text-gray-900">Vendor Contract Renewal</p>
                    <p class="text-xs text-gray-500">5 key vendor agreements</p>
                  </div>
                </td>
                <td class="py-3 px-4">
                  <div class="flex items-center gap-2">
                    <div class="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center">
                      <span class="text-xs font-medium text-green-600">MT</span>
                    </div>
                    <span class="text-sm text-gray-700">Michael Tan</span>
                  </div>
                </td>
                <td class="py-3 px-4">
                  <span class="px-2 py-1 text-xs bg-red-50 text-red-600 rounded-full">Behind</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="p-4 border-t border-gray-200 text-center">
          <a href="#" class="text-sm text-accent font-medium hover:text-purple-700 transition-smooth">View All Department Activities →</a>
        </div>
      </div>

      <!-- Team Management & Tasks -->
      <div class="bg-white rounded-lg shadow p-5">
        <h3 class="font-semibold text-gray-900 text-base mb-4">Team Management</h3>
        <div class="space-y-3">
          <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-smooth">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 bg-warning-light rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">Performance Reviews Due</p>
                <p class="text-xs text-gray-500">3 team members need Q2 reviews</p>
              </div>
            </div>
            <span class="text-xs font-medium text-warning bg-warning-light px-2 py-1 rounded">Due: Jun 30</span>
          </div>
          
          <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-smooth">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 bg-accent-light rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">Team Training Session</p>
                <p class="text-xs text-gray-500">New process training on July 5</p>
              </div>
            </div>
            <span class="text-xs font-medium text-accent bg-accent-light px-2 py-1 rounded">Upcoming</span>
          </div>
          
          <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-smooth">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 bg-success-light rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">Budget Review Meeting</p>
                <p class="text-xs text-gray-500">Q3 budget planning with Finance</p>
              </div>
            </div>
            <span class="text-xs font-medium text-success bg-success-light px-2 py-1 rounded">Scheduled</span>
          </div>
        </div>
        
        <!-- Department Quick Actions -->
        <div class="mt-6 pt-5 border-t border-gray-200">
          <h4 class="text-sm font-semibold text-gray-900 mb-3">Department Quick Actions</h4>
          <div class="grid grid-cols-2 gap-3">
            <a href="#" class="bg-accent text-white p-3 rounded-lg text-center hover:bg-purple-700 transition-smooth">
              <p class="text-xs font-medium">Team Meeting</p>
            </a>
            <a href="#" class="bg-gray-100 text-gray-700 p-3 rounded-lg text-center hover:bg-gray-200 transition-smooth">
              <p class="text-xs font-medium">Performance Review</p>
            </a>
          </div>
          <div class="grid grid-cols-2 gap-3 mt-3">
            <a href="#" class="bg-green-100 text-green-700 p-3 rounded-lg text-center hover:bg-green-200 transition-smooth">
              <p class="text-xs font-medium">Budget Request</p>
            </a>
            <a href="#" class="bg-blue-100 text-blue-700 p-3 rounded-lg text-center hover:bg-blue-200 transition-smooth">
              <p class="text-xs font-medium">Report Generate</p>
            </a>
          </div>
        </div>
        
        <!-- Team Availability -->
        <div class="mt-6 pt-5 border-t border-gray-200">
          <h4 class="text-sm font-semibold text-gray-900 mb-3">Team Availability</h4>
          <div class="grid grid-cols-4 gap-2 text-center">
            <div class="bg-green-50 p-2 rounded">
              <p class="text-lg font-bold text-green-600">12</p>
              <p class="text-xs text-gray-600">Active</p>
            </div>
            <div class="bg-blue-50 p-2 rounded">
              <p class="text-lg font-bold text-blue-600">2</p>
              <p class="text-xs text-gray-600">Remote</p>
            </div>
            <div class="bg-yellow-50 p-2 rounded">
              <p class="text-lg font-bold text-yellow-600">1</p>
              <p class="text-xs text-gray-600">On Leave</p>
            </div>
            <div class="bg-gray-100 p-2 rounded">
              <p class="text-lg font-bold text-gray-600">1</p>
              <p class="text-xs text-gray-600">Training</p>
            </div>
          </div>
          <p class="text-xs text-gray-500 mt-3 text-center">Total Team Members: 16 | Utilization: 92%</p>
        </div>
      </div>
    </div>
    
    <!-- Project Portfolio & Resource Allocation -->
    <div class="bg-white rounded-lg shadow p-5">
      <h3 class="font-semibold text-gray-900 text-base mb-4">Project Portfolio Status</h3>
      <div class="overflow-x-auto">
        <table class="w-full min-w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Project</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Lead</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Timeline</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Budget</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr>
              <td class="py-2 px-3">
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center">
                    <span class="text-xs font-medium text-blue-600">PO</span>
                  </div>
                  <div>
                    <p class="text-xs font-medium">Process Optimization</p>
                    <p class="text-xs text-gray-500">Streamline operational workflows</p>
                  </div>
                </div>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs font-medium text-gray-700">Sarah Mendoza</span>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs text-gray-700">Jun 1 - Aug 30</span>
                <p class="text-xs text-gray-500">65% complete</p>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs font-medium text-gray-700">₱850,000</span>
                <p class="text-xs text-green-600">On budget</p>
              </td>
              <td class="py-2 px-3">
                <span class="px-2 py-1 text-xs bg-yellow-50 text-yellow-600 rounded-full">At Risk</span>
              </td>
            </tr>
            <tr>
              <td class="py-2 px-3">
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center">
                    <span class="text-xs font-medium text-green-600">TA</span>
                  </div>
                  <div>
                    <p class="text-xs font-medium">Technology Upgrade</p>
                    <p class="text-xs text-gray-500">Department software systems</p>
                  </div>
                </div>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs font-medium text-gray-700">John Reyes</span>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs text-gray-700">May 15 - Jul 30</span>
                <p class="text-xs text-gray-500">80% complete</p>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs font-medium text-gray-700">₱1,200,000</span>
                <p class="text-xs text-red-600">5% over</p>
              </td>
              <td class="py-2 px-3">
                <span class="px-2 py-1 text-xs bg-green-50 text-green-600 rounded-full">On Track</span>
              </td>
            </tr>
            <tr>
              <td class="py-2 px-3">
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 bg-purple-100 rounded-full flex items-center justify-center">
                    <span class="text-xs font-medium text-purple-600">VC</span>
                  </div>
                  <div>
                    <p class="text-xs font-medium">Vendor Consolidation</p>
                    <p class="text-xs text-gray-500">Supplier rationalization</p>
                  </div>
                </div>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs font-medium text-gray-700">Michael Tan</span>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs text-gray-700">Apr 1 - Jun 30</span>
                <p class="text-xs text-gray-500">40% complete</p>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs font-medium text-gray-700">₱450,000</span>
                <p class="text-xs text-green-600">On budget</p>
              </td>
              <td class="py-2 px-3">
                <span class="px-2 py-1 text-xs bg-red-50 text-red-600 rounded-full">Behind</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="mt-4 pt-4 border-t">
        <div class="flex flex-col sm:flex-row justify-between items-center gap-3">
          <p class="text-xs text-gray-500">Active Projects: 12 | Behind Schedule: 3 | Over Budget: 2</p>
          <a href="#" class="text-xs text-accent font-medium hover:text-purple-700 transition-smooth">
            View Complete Project Portfolio →
          </a>
        </div>
      </div>
    </div>
    
    <!-- Budget & Resource Management -->
    <div class="bg-white rounded-lg shadow p-5">
      <h3 class="font-semibold text-gray-900 text-base mb-4">Budget & Resource Management</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Budget Breakdown -->
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-3">Q2 Budget Allocation</h4>
          <div class="space-y-3">
            <div>
              <div class="flex justify-between text-xs mb-1">
                <span class="text-gray-600">Personnel Costs</span>
                <span class="font-medium">₱4,250,000 (50%)</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-accent h-2 rounded-full" style="width: 50%"></div>
              </div>
            </div>
            <div>
              <div class="flex justify-between text-xs mb-1">
                <span class="text-gray-600">Operations & Supplies</span>
                <span class="font-medium">₱2,550,000 (30%)</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-dept-blue h-2 rounded-full" style="width: 30%"></div>
              </div>
            </div>
            <div>
              <div class="flex justify-between text-xs mb-1">
                <span class="text-gray-600">Technology & Equipment</span>
                <span class="font-medium">₱1,275,000 (15%)</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-dept-green h-2 rounded-full" style="width: 15%"></div>
              </div>
            </div>
            <div>
              <div class="flex justify-between text-xs mb-1">
                <span class="text-gray-600">Training & Development</span>
                <span class="font-medium">₱425,000 (5%)</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-dept-orange h-2 rounded-full" style="width: 5%"></div>
              </div>
            </div>
          </div>
          <div class="mt-4 p-3 bg-gray-50 rounded-lg">
            <div class="flex justify-between items-center">
              <span class="text-xs font-medium text-gray-700">Total Q2 Budget:</span>
              <span class="text-sm font-bold text-accent">₱8,500,000</span>
            </div>
            <div class="flex justify-between items-center mt-1">
              <span class="text-xs font-medium text-gray-700">YTD Spent:</span>
              <span class="text-xs font-bold text-red-600">₱9,180,000 (108%)</span>
            </div>
          </div>
        </div>
        
        <!-- Resource Allocation -->
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-3">Resource Allocation</h4>
          <div class="space-y-3">
            <div class="p-3 bg-blue-50 rounded-lg">
              <div class="flex justify-between items-center">
                <span class="text-xs font-medium text-gray-700">Team Member Utilization</span>
                <span class="text-xs font-bold text-blue-600">92%</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                <div class="bg-blue-500 h-2 rounded-full" style="width: 92%"></div>
              </div>
            </div>
            
            <div class="p-3 bg-green-50 rounded-lg">
              <div class="flex justify-between items-center">
                <span class="text-xs font-medium text-gray-700">Equipment Utilization</span>
                <span class="text-xs font-bold text-green-600">78%</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                <div class="bg-green-500 h-2 rounded-full" style="width: 78%"></div>
              </div>
            </div>
            
            <div class="p-3 bg-yellow-50 rounded-lg">
              <div class="flex justify-between items-center">
                <span class="text-xs font-medium text-gray-700">Meeting Room Utilization</span>
                <span class="text-xs font-bold text-yellow-600">65%</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                <div class="bg-yellow-500 h-2 rounded-full" style="width: 65%"></div>
              </div>
            </div>
            
            <div class="grid grid-cols-2 gap-3 mt-4">
              <button class="bg-accent text-white p-2 rounded-lg text-center text-sm hover:bg-purple-700 transition-smooth">
                Request Resources
              </button>
              <button class="bg-gray-100 text-gray-700 p-2 rounded-lg text-center text-sm hover:bg-gray-200 transition-smooth">
                Budget Report
              </button>
            </div>
          </div>
        </div>
      </div>
      <div class="mt-4 text-center">
        <a href="#" class="text-xs text-accent font-medium hover:text-purple-700 transition-smooth">
          View Detailed Budget Analysis & Resource Planning →
        </a>
      </div>
    </div>
  </main>
</div>

<!-- Footer -->
<footer class="bg-white border-t py-3">
  <div class="px-6 flex flex-col sm:flex-row justify-between items-center text-xs text-gray-500">
    <div class="mb-2 sm:mb-0">
      <span>© 2025 Department Management System v4.2.1</span>
      <span class="mx-2 hidden sm:inline">•</span>
      <span class="block sm:inline mt-1 sm:mt-0 text-success font-medium">Department: Operations | Head: Maria Santos | Fiscal Year: 2024</span>
    </div>
    <div class="flex items-center gap-3">
      <a href="#" class="hover:text-accent transition-smooth">Team Performance</a>
      <a href="#" class="hover:text-accent transition-smooth">Resource Planning</a>
      <a href="#" class="hover:text-accent transition-smooth">Department Manual</a>
    </div>
  </div>
</footer>

<!-- JavaScript -->
<script>
  // Performance Trend Chart
  const performanceCtx = document.getElementById('performanceChart').getContext('2d');
  const performanceChart = new Chart(performanceCtx, {
    type: 'line',
    data: {
      labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
      datasets: [
        {
          label: 'Actual Performance',
          data: [85, 88, 90, 92, 94, 92],
          borderColor: '#7C3AED',
          backgroundColor: 'rgba(124, 58, 237, 0.1)',
          borderWidth: 2,
          pointBackgroundColor: '#7C3AED',
          pointBorderColor: '#ffffff',
          pointBorderWidth: 2,
          pointRadius: 4,
          pointHoverRadius: 6,
          tension: 0.3,
          fill: true
        },
        {
          label: 'Target',
          data: [90, 90, 90, 90, 90, 90],
          borderColor: '#0D9488',
          backgroundColor: 'transparent',
          borderWidth: 1,
          pointBackgroundColor: '#0D9488',
          pointBorderColor: '#ffffff',
          pointBorderWidth: 2,
          pointRadius: 3,
          pointHoverRadius: 5,
          tension: 0.3,
          borderDash: [5, 5]
        },
        {
          label: 'Department Average',
          data: [82, 84, 86, 88, 89, 88],
          borderColor: '#EA580C',
          backgroundColor: 'transparent',
          borderWidth: 1,
          pointBackgroundColor: '#EA580C',
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
          borderColor: '#7C3AED',
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
          min: 75,
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
            text: 'Performance (%)',
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

  // Team Performance Chart (Doughnut)
  const teamPerformanceCtx = document.getElementById('teamPerformanceChart').getContext('2d');
  const teamPerformanceChart = new Chart(teamPerformanceCtx, {
    type: 'doughnut',
    data: {
      labels: ['Exceeding', 'Meeting', 'Needs Improvement', 'On Leave'],
      datasets: [{
        data: [6, 7, 2, 1],
        backgroundColor: ['#059669', '#1D4ED8', '#D97706', '#6B7280'],
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
          borderColor: '#7C3AED',
          borderWidth: 1,
          cornerRadius: 4,
          callbacks: {
            label: function(context) {
              const labels = ['Exceeding Expectations', 'Meeting Expectations', 'Needs Improvement', 'On Leave'];
              const members = [6, 7, 2, 1];
              return labels[context.dataIndex] + ': ' + members[context.dataIndex] + ' members';
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
    performanceChart.resize();
    teamPerformanceChart.resize();
  });
</script>

</body>
</html>