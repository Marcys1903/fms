<?php
session_start();
// Use your actual database values instead of hardcoding
require_once '../config/config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// Get actual allowed role and level from database
try {
    $stmt = $pdo->prepare("SELECT role, level FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        header("Location: ../login.php?error=unauthorized");
        exit();
    }
    
    // Now check if this user is actually a super admin
    $allowed_role = 'Super Administrator';
    $allowed_level = 1;
    
    if ($user['role'] !== $allowed_role || $user['level'] != $allowed_level) {
        header("Location: ../login.php?error=unauthorized");
        exit();
    }
} catch (Exception $e) {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// Now we can safely use the session variables
$firstname = $_SESSION['firstname'] ?? '';
$middlename = $_SESSION['middlename'] ?? '';
$lastname = $_SESSION['lastname'] ?? '';
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Super Admin Dashboard | Financial Management System</title>
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
            accent: '#2563EB',
            success: '#22C55E',
            danger: '#EF4444',
            warning: '#F59E0B',
            info: '#3B82F6',
            navbar: '#4750DD',
            sidebar: '#1E293B',
            'accent-light': '#E0E7FF',
            'success-light': '#DCFCE7',
            'danger-light': '#FEE2E2',
            'warning-light': '#FEF3C7',
            'gray-150': '#F3F4F6'
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
  </style>
</head>

<body class="bg-gray-50 h-screen overflow-hidden font-inter">

<!-- Header -->
<header class="bg-white shadow-sm h-16 flex items-center justify-between px-6 fixed top-0 left-0 right-0 z-30 border-b border-gray-200">
  <div class="flex items-center gap-3">
    <div class="flex items-center gap-2">
      <img src="../assets/bcpnobg.png" class="h-8 w-8" alt="BCP Logo">
      <div>
        <span class="font-bold text-gray-900 text-lg">BCP Financial Hub</span>
        <span class="ml-2 text-xs bg-accent text-white px-2 py-0.5 rounded-full font-semibold">SUPER ADMIN</span>
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
            <h3 class="font-semibold text-gray-900 text-sm">Notifications</h3>
            <span class="text-xs bg-accent text-white px-2 py-0.5 rounded-full">3 new</span>
          </div>
        </div>
        
        <div class="max-h-80 overflow-y-auto">
          <!-- Notification items -->
          <a href="#" class="notification-item block px-4 py-3 border-b border-gray-100 hover:bg-gray-50 transition-smooth">
            <div class="flex items-start gap-3">
              <div class="w-8 h-8 bg-success-light rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg class="w-4 h-4 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">Budget Approved</p>
                <p class="text-xs text-gray-500 mt-0.5">Department X budget for Q2 has been approved</p>
                <span class="text-xs text-gray-400 mt-1 block">10 min ago</span>
              </div>
              <div class="w-2 h-2 bg-accent rounded-full flex-shrink-0 mt-1"></div>
            </div>
          </a>
          
          <a href="#" class="notification-item block px-4 py-3 border-b border-gray-100 hover:bg-gray-50 transition-smooth">
            <div class="flex items-start gap-3">
              <div class="w-8 h-8 bg-warning-light rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg class="w-4 h-4 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">Over Budget Alert</p>
                <p class="text-xs text-gray-500 mt-0.5">Project Y is 15% over allocated budget</p>
                <span class="text-xs text-gray-400 mt-1 block">2 hours ago</span>
              </div>
              <div class="w-2 h-2 bg-accent rounded-full flex-shrink-0 mt-1"></div>
            </div>
          </a>
          
          <a href="#" class="notification-item block px-4 py-3 border-b border-gray-100 hover:bg-gray-50 transition-smooth">
            <div class="flex items-start gap-3">
              <div class="w-8 h-8 bg-danger-light rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg class="w-4 h-4 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">Security Alert</p>
                <p class="text-xs text-gray-500 mt-0.5">Multiple failed login attempts detected</p>
                <span class="text-xs text-gray-400 mt-1 block">4 hours ago</span>
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
                <p class="text-sm font-medium text-gray-900 truncate">System Update</p>
                <p class="text-xs text-gray-500 mt-0.5">New security patches available</p>
                <span class="text-xs text-gray-400 mt-1 block">1 day ago</span>
              </div>
            </div>
          </a>
          
          <a href="#" class="notification-item block px-4 py-3 hover:bg-gray-50 transition-smooth">
            <div class="flex items-start gap-3">
              <div class="w-8 h-8 bg-success-light rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg class="w-4 h-4 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">Backup Completed</p>
                <p class="text-xs text-gray-500 mt-0.5">Daily system backup successful</p>
                <span class="text-xs text-gray-400 mt-1 block">2 days ago</span>
              </div>
            </div>
          </a>
        </div>
        
        <div class="border-t border-gray-100 pt-2">
          <a href="#" class="block text-center text-sm text-accent font-medium py-2 hover:bg-gray-50 rounded-b-lg transition-smooth">
            View All Notifications
          </a>
        </div>
      </div>
    </div>
    
    <!-- User Profile with Hover Dropdown -->
    <div class="dropdown-container relative">
      <div class="flex items-center gap-3 border-l border-gray-200 pl-4 cursor-pointer hover:bg-gray-50 px-2 py-1 rounded-lg transition-smooth">
        <div class="text-right">
          <p class="font-medium text-gray-900 text-sm"><?php echo htmlspecialchars($firstname . ' ' . $lastname); ?></p>
          <p class="text-xs text-gray-500"><?php echo htmlspecialchars($role); ?></p>
        </div>
        <div class="relative">
          <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo urlencode($firstname); ?>" class="h-9 w-9 rounded-full border-2 border-accent">
          <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-success rounded-full border-2 border-white"></div>
        </div>
      </div>
      
      <!-- User Profile Dropdown -->
      <div class="dropdown absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 py-2 z-40">
        <div class="px-4 py-2 border-b border-gray-100">
          <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Account</p>
        </div>
        
        <a href="#" class="dropdown-item flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-smooth">
          <div class="w-5 h-5 text-gray-500">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
          </div>
          <span class="text-sm text-gray-700">My Profile</span>
        </a>
        
        <a href="#" class="dropdown-item flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-smooth">
          <div class="w-5 h-5 text-gray-500">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
          </div>
          <span class="text-sm text-gray-700">Settings</span>
        </a>
        
        <div class="border-t border-gray-100 pt-2">
          <a href="../auth/logout.php" class="flex items-center gap-3 px-4 py-3 hover:bg-danger/10 text-danger transition-smooth">
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
    
    <!-- System Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-accent stat-card">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">System Users</p>
            <p class="text-2xl font-bold mt-1">1,247</p>
          </div>
          <div class="p-2 bg-accent-light rounded-lg">
            <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5 3.75a6 6 0 00-9.5-4.197"/>
            </svg>
          </div>
        </div>
        <p class="text-xs text-success mt-2 font-medium">+12 this week</p>
      </div>

      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-success stat-card">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">System Uptime</p>
            <p class="text-2xl font-bold mt-1">99.97%</p>
          </div>
          <div class="p-2 bg-success-light rounded-lg">
            <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
        </div>
        <p class="text-xs text-gray-500 mt-2">Last month</p>
      </div>

      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-warning stat-card">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Pending Approvals</p>
            <p class="text-2xl font-bold mt-1">23</p>
          </div>
          <div class="p-2 bg-warning-light rounded-lg">
            <svg class="w-5 h-5 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
        </div>
        <p class="text-xs text-warning mt-2 font-medium">3 critical</p>
      </div>

      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-danger stat-card">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Security Alerts</p>
            <p class="text-2xl font-bold mt-1">5</p>
          </div>
          <div class="p-2 bg-danger-light rounded-lg">
            <svg class="w-5 h-5 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
          </div>
        </div>
        <p class="text-xs text-danger mt-2 font-medium">2 critical</p>
      </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      
      <!-- System Activity Chart -->
      <div class="bg-white rounded-lg shadow p-5 lg:col-span-2">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-5">
          <div>
            <h3 class="font-semibold text-gray-900 text-base">System Activity</h3>
            <p class="text-sm text-gray-500">Daily metrics overview</p>
          </div>
          <select class="text-xs border border-gray-300 rounded-lg px-3 py-1.5 bg-white mt-2 sm:mt-0">
            <option>Last 7 days</option>
            <option selected>Last 30 days</option>
            <option>Last quarter</option>
          </select>
        </div>
        <div class="chart-container">
          <canvas id="activityChart"></canvas>
        </div>
        <div class="flex items-center justify-center gap-4 mt-4 text-xs">
          <div class="flex items-center gap-1.5">
            <div class="w-3 h-3 rounded-full bg-accent"></div>
            <span class="text-gray-600">User Logins</span>
          </div>
          <div class="flex items-center gap-1.5">
            <div class="w-3 h-3 rounded-full bg-success"></div>
            <span class="text-gray-600">Transactions</span>
          </div>
          <div class="flex items-center gap-1.5">
            <div class="w-3 h-3 rounded-full bg-warning"></div>
            <span class="text-gray-600">Audit Events</span>
          </div>
        </div>
      </div>

      <!-- Data Storage Chart -->
      <div class="bg-white rounded-lg shadow p-5">
        <h3 class="font-semibold text-gray-900 text-base mb-5">Data Storage</h3>
        <div class="chart-container">
          <canvas id="storageChart"></canvas>
        </div>
        <div class="mt-4 space-y-3">
          <div>
            <div class="flex justify-between text-xs mb-1">
              <span class="text-gray-600">Financial Data</span>
              <span class="font-medium">78%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-1.5">
              <div class="bg-accent h-1.5 rounded-full" style="width: 78%"></div>
            </div>
          </div>
          <div>
            <div class="flex justify-between text-xs mb-1">
              <span class="text-gray-600">User Logs</span>
              <span class="font-medium">45%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-1.5">
              <div class="bg-warning h-1.5 rounded-full" style="width: 45%"></div>
            </div>
          </div>
          <div>
            <div class="flex justify-between text-xs mb-1">
              <span class="text-gray-600">Audit Trails</span>
              <span class="font-medium">32%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-1.5">
              <div class="bg-success h-1.5 rounded-full" style="width: 32%"></div>
            </div>
          </div>
        </div>
        <p class="text-xs text-gray-500 mt-4 text-center">Total: 2.4TB of 4TB used</p>
      </div>
    </div>

    <!-- Recent Activities & System Health -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      
      <!-- Recent Activities -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-5 border-b border-gray-200">
          <h3 class="font-semibold text-gray-900 text-base">Recent Activities</h3>
          <p class="text-sm text-gray-500">Last 24 hours</p>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full min-w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">User</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Action</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Time</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr>
                <td class="py-3 px-4">
                  <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-full bg-accent-light flex items-center justify-center text-xs font-medium text-accent">JS</div>
                    <div>
                      <p class="text-sm font-medium text-gray-900">John Smith</p>
                      <p class="text-xs text-gray-500">Finance Manager</p>
                    </div>
                  </div>
                </td>
                <td class="py-3 px-4 text-sm text-gray-700">Budget Approval</td>
                <td class="py-3 px-4">
                  <span class="text-xs text-gray-500">10:24 AM</span>
                </td>
              </tr>
              <tr>
                <td class="py-3 px-4">
                  <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-full bg-success-light flex items-center justify-center text-xs font-medium text-success">SJ</div>
                    <div>
                      <p class="text-sm font-medium text-gray-900">Sarah Johnson</p>
                      <p class="text-xs text-gray-500">Auditor</p>
                    </div>
                  </div>
                </td>
                <td class="py-3 px-4 text-sm text-gray-700">Report Generation</td>
                <td class="py-3 px-4">
                  <span class="text-xs text-gray-500">09:45 AM</span>
                </td>
              </tr>
              <tr>
                <td class="py-3 px-4">
                  <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-full bg-warning-light flex items-center justify-center text-xs font-medium text-warning">MR</div>
                    <div>
                      <p class="text-sm font-medium text-gray-900">Mike Rodriguez</p>
                      <p class="text-xs text-gray-500">Admin</p>
                    </div>
                  </div>
                </td>
                <td class="py-3 px-4 text-sm text-gray-700">User Management</td>
                <td class="py-3 px-4">
                  <span class="text-xs text-gray-500">08:30 AM</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="p-4 border-t border-gray-200 text-center">
          <a href="#" class="text-sm text-accent font-medium hover:text-blue-700 transition-smooth">View All Activities →</a>
        </div>
      </div>

      <!-- System Health -->
      <div class="bg-white rounded-lg shadow p-5">
        <h3 class="font-semibold text-gray-900 text-base mb-4">System Health</h3>
        <div class="space-y-3">
          <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-smooth">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">Database</p>
                <p class="text-xs text-gray-500">Connection stable</p>
              </div>
            </div>
            <span class="text-xs font-medium text-green-600 bg-green-100 px-2 py-1 rounded">Healthy</span>
          </div>
          
          <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-smooth">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">Backup Service</p>
                <p class="text-xs text-gray-500">Scheduled in 2 hours</p>
              </div>
            </div>
            <span class="text-xs font-medium text-yellow-600 bg-yellow-100 px-2 py-1 rounded">Pending</span>
          </div>
          
          <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-smooth">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">Security Logs</p>
                <p class="text-xs text-gray-500">2 critical alerts</p>
              </div>
            </div>
            <span class="text-xs font-medium text-red-600 bg-red-100 px-2 py-1 rounded">Attention</span>
          </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="mt-6 pt-5 border-t border-gray-200">
          <h4 class="text-sm font-semibold text-gray-900 mb-3">Quick Stats</h4>
          <div class="grid grid-cols-2 gap-3">
            <div class="bg-gray-50 p-3 rounded-lg">
              <p class="text-xs text-gray-500">API Calls Today</p>
              <p class="text-lg font-bold">2,847</p>
            </div>
            <div class="bg-gray-50 p-3 rounded-lg">
              <p class="text-xs text-gray-500">Avg Response Time</p>
              <p class="text-lg font-bold">142ms</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>

<!-- Footer -->
<footer class="bg-white border-t py-3">
  <div class="px-6 flex flex-col sm:flex-row justify-between items-center text-xs text-gray-500">
    <div class="mb-2 sm:mb-0">
      <span>© 2025 BCP Financial Management System v3.2.1</span>
      <span class="mx-2 hidden sm:inline">•</span>
      <span class="block sm:inline mt-1 sm:mt-0 text-success font-medium">Uptime: 99.97%</span>
    </div>
    <div class="flex items-center gap-3">
      <a href="#" class="hover:text-accent transition-smooth">Support</a>
      <a href="#" class="hover:text-accent transition-smooth">Privacy</a>
      <button class="text-danger hover:text-red-700 font-medium text-xs" onclick="confirm('Initiate system maintenance?')">
        Maintenance
      </button>
    </div>
  </div>
</footer>

<!-- JavaScript -->
<script>
  // System Activity Chart
  const activityCtx = document.getElementById('activityChart').getContext('2d');
  const activityChart = new Chart(activityCtx, {
    type: 'line',
    data: {
      labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
      datasets: [
        {
          label: 'User Logins',
          data: [850, 900, 920, 870, 950, 700, 600],
          borderColor: '#2563EB',
          backgroundColor: 'transparent',
          borderWidth: 2,
          pointBackgroundColor: '#2563EB',
          pointBorderColor: '#ffffff',
          pointBorderWidth: 2,
          pointRadius: 4,
          pointHoverRadius: 6,
          tension: 0.3
        },
        {
          label: 'Transactions',
          data: [320, 380, 350, 400, 420, 300, 280],
          borderColor: '#22C55E',
          backgroundColor: 'transparent',
          borderWidth: 2,
          pointBackgroundColor: '#22C55E',
          pointBorderColor: '#ffffff',
          pointBorderWidth: 2,
          pointRadius: 4,
          pointHoverRadius: 6,
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
          borderColor: '#2563EB',
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
      },
      elements: {
        line: {
          fill: false
        }
      }
    }
  });

  // Data Storage Chart (Doughnut)
  const storageCtx = document.getElementById('storageChart').getContext('2d');
  const storageChart = new Chart(storageCtx, {
    type: 'doughnut',
    data: {
      labels: ['Financial Data', 'User Logs', 'Audit Trails', 'Available'],
      datasets: [{
        data: [40, 23, 17, 20],
        backgroundColor: ['#2563EB', '#F59E0B', '#22C55E', '#E5E7EB'],
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
          borderColor: '#2563EB',
          borderWidth: 1,
          cornerRadius: 4
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
    activityChart.resize();
    storageChart.resize();
  });
</script>

</body>
</html>