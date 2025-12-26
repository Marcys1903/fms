<?php
session_start();
$allowed_role = 'Students';
$allowed_level = 6;
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
  <title>Student Dashboard | Financial Management System</title>
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
            accent: '#3B82F6', // Blue for academic/education
            success: '#10B981',
            danger: '#EF4444',
            warning: '#F59E0B',
            info: '#8B5CF6',
            navbar: '#2563EB', // Blue for navbar
            sidebar: '#1E293B',
            'accent-light': '#DBEAFE', // Light blue
            'success-light': '#DCFCE7',
            'danger-light': '#FEE2E2',
            'warning-light': '#FEF3C7',
            'gray-150': '#F3F4F6',
            'academic-green': '#10B981',
            'academic-purple': '#8B5CF6',
            'academic-orange': '#F59E0B',
            'academic-pink': '#EC4899'
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
    
    /* Grade indicators */
    .grade-a { background-color: #DCFCE7; color: #10B981; border-left: 4px solid #10B981; }
    .grade-b { background-color: #DBEAFE; color: #3B82F6; border-left: 4px solid #3B82F6; }
    .grade-c { background-color: #FEF3C7; color: #F59E0B; border-left: 4px solid #F59E0B; }
    .grade-d { background-color: #FEE2E2; color: #EF4444; border-left: 4px solid #EF4444; }
    
    /* Status indicators */
    .status-completed { background-color: #DCFCE7; color: #10B981; }
    .status-pending { background-color: #FEF3C7; color: #F59E0B; }
    .status-overdue { background-color: #FEE2E2; color: #EF4444; }
    .status-upcoming { background-color: #DBEAFE; color: #3B82F6; }
  </style>
</head>

<body class="bg-gray-50 h-screen overflow-hidden font-inter">

<!-- Header -->
<header class="bg-white shadow-sm h-16 flex items-center justify-between px-6 fixed top-0 left-0 right-0 z-30 border-b border-gray-200">
  <div class="flex items-center gap-3">
    <div class="flex items-center gap-2">
      <img src="../assets/bcpnobg.png" class="h-8 w-8" alt="BCP Logo">
      <div>
        <span class="font-bold text-gray-900 text-lg">Student Portal</span>
        <span class="ml-2 text-xs bg-accent text-white px-2 py-0.5 rounded-full font-semibold">STUDENT</span>
      </div>
    </div>
  </div>
  
  <div class="flex items-center gap-4">
    <!-- Current Semester Status -->
    <div class="flex items-center gap-2 px-3 py-1 bg-blue-50 rounded-lg border border-blue-200">
      <svg class="w-3 h-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
      </svg>
      <span class="text-xs font-medium text-blue-700">Semester: 2nd 2023-2024</span>
    </div>
    
    <!-- Class Schedule Timer -->
    <div class="flex items-center gap-2 px-3 py-1 bg-green-50 rounded-lg border border-green-200">
      <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <span class="text-xs font-medium text-green-700">Next Class: 10:30 AM</span>
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
            <h3 class="font-semibold text-gray-900 text-sm">Academic Alerts</h3>
            <span class="text-xs bg-accent text-white px-2 py-0.5 rounded-full">4 new</span>
          </div>
        </div>
        
        <div class="max-h-80 overflow-y-auto">
          <!-- Notification items -->
          <a href="#" class="notification-item block px-4 py-3 border-b border-gray-100 hover:bg-gray-50 transition-smooth">
            <div class="flex items-start gap-3">
              <div class="w-8 h-8 bg-danger-light rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg class="w-4 h-4 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">Assignment Due Tomorrow</p>
                <p class="text-xs text-gray-500 mt-0.5">Financial Accounting Problem Set 3</p>
                <span class="text-xs text-gray-400 mt-1 block">2 hours ago</span>
              </div>
              <div class="w-2 h-2 bg-danger rounded-full flex-shrink-0 mt-1"></div>
            </div>
          </a>
          
          <a href="#" class="notification-item block px-4 py-3 border-b border-gray-100 hover:bg-gray-50 transition-smooth">
            <div class="flex items-start gap-3">
              <div class="w-8 h-8 bg-warning-light rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg class="w-4 h-4 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">Quiz Score Posted</p>
                <p class="text-xs text-gray-500 mt-0.5">Statistics Quiz 2: 92% (A)</p>
                <span class="text-xs text-gray-400 mt-1 block">1 day ago</span>
              </div>
              <div class="w-2 h-2 bg-accent rounded-full flex-shrink-0 mt-1"></div>
            </div>
          </a>
          
          <a href="#" class="notification-item block px-4 py-3 border-b border-gray-100 hover:bg-gray-50 transition-smooth">
            <div class="flex items-start gap-3">
              <div class="w-8 h-8 bg-success-light rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg class="w-4 h-4 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">New Learning Materials</p>
                <p class="text-xs text-gray-500 mt-0.5">Managerial Finance Lecture Slides</p>
                <span class="text-xs text-gray-400 mt-1 block">2 days ago</span>
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
                <p class="text-sm font-medium text-gray-900 truncate">Group Project Meeting</p>
                <p class="text-xs text-gray-500 mt-0.5">Business Case Study - Tomorrow 3 PM</p>
                <span class="text-xs text-gray-400 mt-1 block">3 days ago</span>
              </div>
            </div>
          </a>
          
          <a href="#" class="notification-item block px-4 py-3 hover:bg-gray-50 transition-smooth">
            <div class="flex items-start gap-3">
              <div class="w-8 h-8 bg-academic-purple/10 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg class="w-4 h-4 text-academic-purple" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">Library Book Due</p>
                <p class="text-xs text-gray-500 mt-0.5">"Financial Management" due in 3 days</p>
                <span class="text-xs text-gray-400 mt-1 block">1 week ago</span>
              </div>
            </div>
          </a>
        </div>
        
        <div class="border-t border-gray-100 pt-2">
          <a href="#" class="block text-center text-sm text-accent font-medium py-2 hover:bg-gray-50 rounded-b-lg transition-smooth">
            View All Academic Alerts
          </a>
        </div>
      </div>
    </div>
    
    <!-- User Profile with Hover Dropdown -->
    <div class="dropdown-container relative">
      <div class="flex items-center gap-3 border-l border-gray-200 pl-4 cursor-pointer hover:bg-gray-50 px-2 py-1 rounded-lg transition-smooth">
        <div class="text-right">
          <p class="font-medium text-gray-900 text-sm">Juan Dela Cruz</p>
          <p class="text-xs text-gray-500">BS Accountancy, 3rd Year</p>
        </div>
        <div class="relative">
          <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Student" class="h-9 w-9 rounded-full border-2 border-accent">
          <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-success rounded-full border-2 border-white"></div>
        </div>
      </div>
      
      <!-- User Profile Dropdown -->
      <div class="dropdown absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 py-2 z-40">
        <div class="px-4 py-2 border-b border-gray-100">
          <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Student Account</p>
        </div>
        
        <a href="#" class="dropdown-item flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-smooth">
          <div class="w-5 h-5 text-gray-500">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
          </div>
          <span class="text-sm text-gray-700">Student Profile</span>
        </a>
        
        <a href="#" class="dropdown-item flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-smooth">
          <div class="w-5 h-5 text-gray-500">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
          </div>
          <span class="text-sm text-gray-700">Academic Settings</span>
        </a>
        
        <a href="#" class="dropdown-item flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-smooth">
          <div class="w-5 h-5 text-gray-500">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <span class="text-sm text-gray-700">Grades & Transcript</span>
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
    
    <!-- Academic Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-accent stat-card">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Current GPA</p>
            <p class="text-2xl font-bold mt-1">3.75</p>
          </div>
          <div class="p-2 bg-accent-light rounded-lg">
            <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
          </div>
        </div>
        <p class="text-xs text-success mt-2 font-medium">+0.15 from last sem</p>
      </div>

      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-success stat-card">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Units Enrolled</p>
            <p class="text-2xl font-bold mt-1">18</p>
          </div>
          <div class="p-2 bg-success-light rounded-lg">
            <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
          </div>
        </div>
        <p class="text-xs text-gray-500 mt-2">Maximum load allowed</p>
      </div>

      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-warning stat-card">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Pending Assignments</p>
            <p class="text-2xl font-bold mt-1">7</p>
          </div>
          <div class="p-2 bg-warning-light rounded-lg">
            <svg class="w-5 h-5 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
        </div>
        <p class="text-xs text-danger mt-2 font-medium">2 overdue</p>
      </div>

      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-academic-purple stat-card">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Classes Today</p>
            <p class="text-2xl font-bold mt-1">3</p>
          </div>
          <div class="p-2 bg-purple-50 rounded-lg">
            <svg class="w-5 h-5 text-academic-purple" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"/>
            </svg>
          </div>
        </div>
        <p class="text-xs text-academic-purple mt-2 font-medium">Next: 10:30 AM</p>
      </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      
      <!-- Grade Progress Trend -->
      <div class="bg-white rounded-lg shadow p-5 lg:col-span-2">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-5">
          <div>
            <h3 class="font-semibold text-gray-900 text-base">Grade Progress Trend</h3>
            <p class="text-sm text-gray-500">Semester GPA progression</p>
          </div>
          <select class="text-xs border border-gray-300 rounded-lg px-3 py-1.5 bg-white mt-2 sm:mt-0">
            <option>Current Semester</option>
            <option selected>All Semesters</option>
            <option>Year to date</option>
          </select>
        </div>
        <div class="chart-container">
          <canvas id="gradeTrendChart"></canvas>
        </div>
        <div class="flex items-center justify-center gap-4 mt-4 text-xs">
          <div class="flex items-center gap-1.5">
            <div class="w-3 h-3 rounded-full bg-accent"></div>
            <span class="text-gray-600">Your GPA</span>
          </div>
          <div class="flex items-center gap-1.5">
            <div class="w-3 h-3 rounded-full bg-academic-green"></div>
            <span class="text-gray-600">Course Average</span>
          </div>
          <div class="flex items-center gap-1.5">
            <div class="w-3 h-3 rounded-full bg-academic-orange"></div>
            <span class="text-gray-600">Department Average</span>
          </div>
        </div>
      </div>

      <!-- Course Performance Distribution -->
      <div class="bg-white rounded-lg shadow p-5">
        <h3 class="font-semibold text-gray-900 text-base mb-5">Course Performance</h3>
        <div class="chart-container">
          <canvas id="coursePerformanceChart"></canvas>
        </div>
        <div class="mt-4 space-y-3">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-success"></div>
              <span class="text-xs text-gray-600">A Grades</span>
            </div>
            <span class="text-xs font-medium">4 courses</span>
          </div>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-accent"></div>
              <span class="text-xs text-gray-600">B Grades</span>
            </div>
            <span class="text-xs font-medium">3 courses</span>
          </div>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-warning"></div>
              <span class="text-xs text-gray-600">C Grades</span>
            </div>
            <span class="text-xs font-medium">1 course</span>
          </div>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-gray-400"></div>
              <span class="text-xs text-gray-600">In Progress</span>
            </div>
            <span class="text-xs font-medium">2 courses</span>
          </div>
        </div>
        <p class="text-xs text-gray-500 mt-4 text-center">Current Semester Average: 91.5%</p>
      </div>
    </div>

    <!-- Recent Activities & Assignments -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      
      <!-- Upcoming Assignments -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-5 border-b border-gray-200">
          <h3 class="font-semibold text-gray-900 text-base">Upcoming Assignments</h3>
          <p class="text-sm text-gray-500">Deadlines and submissions</p>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full min-w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Assignment</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Course</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Due Date</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr class="status-overdue">
                <td class="py-3 px-4">
                  <div>
                    <p class="text-sm font-medium text-gray-900">Financial Accounting PS3</p>
                    <p class="text-xs text-gray-500">Problem Set 3 - Chapters 5-7</p>
                  </div>
                </td>
                <td class="py-3 px-4">
                  <span class="text-sm font-medium text-gray-700">ACCTG 101</span>
                </td>
                <td class="py-3 px-4">
                  <span class="text-sm font-medium text-gray-700">Tomorrow</span>
                </td>
                <td class="py-3 px-4">
                  <span class="px-2 py-1 text-xs bg-red-50 text-red-600 rounded-full">Overdue</span>
                </td>
              </tr>
              <tr class="status-pending">
                <td class="py-3 px-4">
                  <div>
                    <p class="text-sm font-medium text-gray-900">Business Case Study</p>
                    <p class="text-xs text-gray-500">Group project report</p>
                  </div>
                </td>
                <td class="py-3 px-4">
                  <span class="text-sm font-medium text-gray-700">MGMT 201</span>
                </td>
                <td class="py-3 px-4">
                  <span class="text-sm font-medium text-gray-700">Jun 30</span>
                </td>
                <td class="py-3 px-4">
                  <span class="px-2 py-1 text-xs bg-yellow-50 text-yellow-600 rounded-full">In Progress</span>
                </td>
              </tr>
              <tr class="status-upcoming">
                <td class="py-3 px-4">
                  <div>
                    <p class="text-sm font-medium text-gray-900">Statistics Midterm</p>
                    <p class="text-xs text-gray-500">Chapters 1-8</p>
                  </div>
                </td>
                <td class="py-3 px-4">
                  <span class="text-sm font-medium text-gray-700">STAT 101</span>
                </td>
                <td class="py-3 px-4">
                  <span class="text-sm font-medium text-gray-700">Jul 5</span>
                </td>
                <td class="py-3 px-4">
                  <span class="px-2 py-1 text-xs bg-blue-50 text-blue-600 rounded-full">Upcoming</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="p-4 border-t border-gray-200 text-center">
          <a href="#" class="text-sm text-accent font-medium hover:text-blue-700 transition-smooth">View All Assignments →</a>
        </div>
      </div>

      <!-- Today's Schedule & Tasks -->
      <div class="bg-white rounded-lg shadow p-5">
        <h3 class="font-semibold text-gray-900 text-base mb-4">Today's Schedule</h3>
        <div class="space-y-3">
          <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-smooth">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 bg-accent-light rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">Financial Accounting</p>
                <p class="text-xs text-gray-500">Room 301 | 8:00 - 9:30 AM</p>
              </div>
            </div>
            <span class="text-xs font-medium text-accent bg-accent-light px-2 py-1 rounded">Completed</span>
          </div>
          
          <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition-smooth">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">Managerial Finance</p>
                <p class="text-xs text-gray-500">Room 205 | 10:30 - 12:00 PM</p>
              </div>
            </div>
            <span class="text-xs font-medium text-blue-600 bg-blue-100 px-2 py-1 rounded">Next</span>
          </div>
          
          <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-smooth">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 bg-purple-50 rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">Business Ethics</p>
                <p class="text-xs text-gray-500">Room 402 | 1:30 - 3:00 PM</p>
              </div>
            </div>
            <span class="text-xs font-medium text-purple-600 bg-purple-50 px-2 py-1 rounded">Later</span>
          </div>
        </div>
        
        <!-- Student Quick Actions -->
        <div class="mt-6 pt-5 border-t border-gray-200">
          <h4 class="text-sm font-semibold text-gray-900 mb-3">Quick Actions</h4>
          <div class="grid grid-cols-2 gap-3">
            <a href="#" class="bg-accent text-white p-3 rounded-lg text-center hover:bg-blue-700 transition-smooth">
              <p class="text-xs font-medium">Submit Assignment</p>
            </a>
            <a href="#" class="bg-gray-100 text-gray-700 p-3 rounded-lg text-center hover:bg-gray-200 transition-smooth">
              <p class="text-xs font-medium">View Grades</p>
            </a>
          </div>
          <div class="grid grid-cols-2 gap-3 mt-3">
            <a href="#" class="bg-green-100 text-green-700 p-3 rounded-lg text-center hover:bg-green-200 transition-smooth">
              <p class="text-xs font-medium">Course Materials</p>
            </a>
            <a href="#" class="bg-purple-100 text-purple-700 p-3 rounded-lg text-center hover:bg-purple-200 transition-smooth">
              <p class="text-xs font-medium">Library Search</p>
            </a>
          </div>
        </div>
        
        <!-- Academic Statistics -->
        <div class="mt-6 pt-5 border-t border-gray-200">
          <h4 class="text-sm font-semibold text-gray-900 mb-3">Academic Statistics</h4>
          <div class="grid grid-cols-3 gap-2 text-center">
            <div class="bg-green-50 p-2 rounded">
              <p class="text-lg font-bold text-green-600">3.75</p>
              <p class="text-xs text-gray-600">GPA</p>
            </div>
            <div class="bg-blue-50 p-2 rounded">
              <p class="text-lg font-bold text-blue-600">18</p>
              <p class="text-xs text-gray-600">Units</p>
            </div>
            <div class="bg-yellow-50 p-2 rounded">
              <p class="text-lg font-bold text-yellow-600">91.5%</p>
              <p class="text-xs text-gray-600">Average</p>
            </div>
          </div>
          <p class="text-xs text-gray-500 mt-3 text-center">Academic Standing: Excellent | Dean's Lister: 2 semesters</p>
        </div>
      </div>
    </div>
    
    <!-- Course Grades & Performance -->
    <div class="bg-white rounded-lg shadow p-5">
      <h3 class="font-semibold text-gray-900 text-base mb-4">Course Grades & Performance</h3>
      <div class="overflow-x-auto">
        <table class="w-full min-w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Course</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Instructor</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Schedule</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Current Grade</th>
              <th class="text-left py-2 px-3 text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr class="grade-a">
              <td class="py-2 px-3">
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center">
                    <span class="text-xs font-medium text-blue-600">FA</span>
                  </div>
                  <div>
                    <p class="text-xs font-medium">Financial Accounting</p>
                    <p class="text-xs text-gray-500">ACCTG 101 | 3 units</p>
                  </div>
                </div>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs font-medium text-gray-700">Prof. Santos</span>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs text-gray-700">MWF 8:00-9:30</span>
                <p class="text-xs text-gray-500">Room 301</p>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs font-bold text-green-600">95% (A)</span>
                <p class="text-xs text-gray-500">Quizzes: 92%</p>
              </td>
              <td class="py-2 px-3">
                <span class="px-2 py-1 text-xs bg-green-50 text-green-600 rounded-full">Excellent</span>
              </td>
            </tr>
            <tr class="grade-b">
              <td class="py-2 px-3">
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center">
                    <span class="text-xs font-medium text-green-600">MF</span>
                  </div>
                  <div>
                    <p class="text-xs font-medium">Managerial Finance</p>
                    <p class="text-xs text-gray-500">FIN 201 | 3 units</p>
                  </div>
                </div>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs font-medium text-gray-700">Prof. Reyes</span>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs text-gray-700">TTh 10:30-12:00</span>
                <p class="text-xs text-gray-500">Room 205</p>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs font-bold text-blue-600">88% (B+)</span>
                <p class="text-xs text-gray-500">Midterm: 85%</p>
              </td>
              <td class="py-2 px-3">
                <span class="px-2 py-1 text-xs bg-blue-50 text-blue-600 rounded-full">Good</span>
              </td>
            </tr>
            <tr class="grade-a">
              <td class="py-2 px-3">
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 bg-purple-100 rounded-full flex items-center justify-center">
                    <span class="text-xs font-medium text-purple-600">ST</span>
                  </div>
                  <div>
                    <p class="text-xs font-medium">Statistics</p>
                    <p class="text-xs text-gray-500">STAT 101 | 3 units</p>
                  </div>
                </div>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs font-medium text-gray-700">Prof. Tan</span>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs text-gray-700">MWF 1:30-3:00</span>
                <p class="text-xs text-gray-500">Room 107</p>
              </td>
              <td class="py-2 px-3">
                <span class="text-xs font-bold text-green-600">92% (A)</span>
                <p class="text-xs text-gray-500">Quizzes: 94%</p>
              </td>
              <td class="py-2 px-3">
                <span class="px-2 py-1 text-xs bg-green-50 text-green-600 rounded-full">Excellent</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="mt-4 pt-4 border-t">
        <div class="flex flex-col sm:flex-row justify-between items-center gap-3">
          <p class="text-xs text-gray-500">Current Semester: 2nd 2023-2024 | Units Enrolled: 18 | GPA: 3.75</p>
          <a href="#" class="text-xs text-accent font-medium hover:text-blue-700 transition-smooth">
            View Complete Transcript & Grades →
          </a>
        </div>
      </div>
    </div>
    
    <!-- Learning Resources & Tools -->
    <div class="bg-white rounded-lg shadow p-5">
      <h3 class="font-semibold text-gray-900 text-base mb-4">Learning Resources & Tools</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Library Resources -->
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-3">Library Resources</h4>
          <div class="space-y-3">
            <div class="p-3 bg-blue-50 rounded-lg">
              <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                <p class="text-xs font-medium text-gray-900">Books Checked Out</p>
              </div>
              <p class="text-xs text-gray-500 mt-1">3 books | 1 due in 3 days</p>
            </div>
            
            <div class="p-3 bg-green-50 rounded-lg">
              <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                </svg>
                <p class="text-xs font-medium text-gray-900">Online Databases</p>
              </div>
              <p class="text-xs text-gray-500 mt-1">12 academic databases available</p>
            </div>
            
            <div class="p-3 bg-purple-50 rounded-lg">
              <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <p class="text-xs font-medium text-gray-900">Research Assistance</p>
              </div>
              <p class="text-xs text-gray-500 mt-1">Librarian consultation available</p>
            </div>
          </div>
        </div>
        
        <!-- Study Tools -->
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-3">Study Tools</h4>
          <div class="space-y-3">
            <div class="p-3 bg-yellow-50 rounded-lg">
              <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-700">Study Planner</span>
                <span class="text-xs font-bold text-yellow-600">65%</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                <div class="bg-yellow-500 h-2 rounded-full" style="width: 65%"></div>
              </div>
            </div>
            
            <div class="p-3 bg-pink-50 rounded-lg">
              <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-700">Flashcards Created</span>
                <span class="text-xs font-bold text-pink-600">42</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                <div class="bg-pink-500 h-2 rounded-full" style="width: 70%"></div>
              </div>
            </div>
            
            <div class="p-3 bg-teal-50 rounded-lg">
              <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-700">Practice Tests Taken</span>
                <span class="text-xs font-bold text-teal-600">8</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                <div class="bg-teal-500 h-2 rounded-full" style="width: 40%"></div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Academic Support -->
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-3">Academic Support</h4>
          <div class="space-y-3">
            <div class="p-3 bg-red-50 rounded-lg">
              <p class="text-xs font-medium text-gray-900">Tutoring Services</p>
              <p class="text-xs text-gray-500 mt-1">Math & Accounting tutors available</p>
              <button class="text-xs text-red-600 font-medium mt-2 hover:text-red-700">Schedule Session</button>
            </div>
            
            <div class="p-3 bg-indigo-50 rounded-lg">
              <p class="text-xs font-medium text-gray-900">Writing Center</p>
              <p class="text-xs text-gray-500 mt-1">Essay & report review assistance</p>
              <button class="text-xs text-indigo-600 font-medium mt-2 hover:text-indigo-700">Book Appointment</button>
            </div>
            
            <div class="p-3 bg-gray-100 rounded-lg">
              <p class="text-xs font-medium text-gray-900">Academic Advising</p>
              <p class="text-xs text-gray-500 mt-1">Next appointment: Jul 15</p>
              <button class="text-xs text-gray-700 font-medium mt-2 hover:text-gray-900">Reschedule</button>
            </div>
          </div>
        </div>
      </div>
      <div class="mt-4 text-center">
        <a href="#" class="text-xs text-accent font-medium hover:text-blue-700 transition-smooth">
          Access All Learning Resources & Academic Support Services →
        </a>
      </div>
    </div>
  </main>
</div>

<!-- Footer -->
<footer class="bg-white border-t py-3">
  <div class="px-6 flex flex-col sm:flex-row justify-between items-center text-xs text-gray-500">
    <div class="mb-2 sm:mb-0">
      <span>© 2024 Student Portal v2.5.1</span>
      <span class="mx-2 hidden sm:inline">•</span>
      <span class="block sm:inline mt-1 sm:mt-0 text-success font-medium">Academic Year: 2023-2024 | Semester: 2nd | Student ID: 2021-12345</span>
    </div>
    <div class="flex items-center gap-3">
      <a href="#" class="hover:text-accent transition-smooth">Course Catalog</a>
      <a href="#" class="hover:text-accent transition-smooth">Academic Calendar</a>
      <a href="#" class="hover:text-accent transition-smooth">Student Handbook</a>
    </div>
  </div>
</footer>

<!-- JavaScript -->
<script>
  // Grade Trend Chart
  const gradeTrendCtx = document.getElementById('gradeTrendChart').getContext('2d');
  const gradeTrendChart = new Chart(gradeTrendCtx, {
    type: 'line',
    data: {
      labels: ['1st Year 1st', '1st Year 2nd', '2nd Year 1st', '2nd Year 2nd', '3rd Year 1st', '3rd Year 2nd'],
      datasets: [
        {
          label: 'Your GPA',
          data: [3.2, 3.4, 3.5, 3.6, 3.7, 3.75],
          borderColor: '#3B82F6',
          backgroundColor: 'rgba(59, 130, 246, 0.1)',
          borderWidth: 2,
          pointBackgroundColor: '#3B82F6',
          pointBorderColor: '#ffffff',
          pointBorderWidth: 2,
          pointRadius: 4,
          pointHoverRadius: 6,
          tension: 0.3,
          fill: true
        },
        {
          label: 'Course Average',
          data: [3.0, 3.1, 3.2, 3.3, 3.4, 3.45],
          borderColor: '#10B981',
          backgroundColor: 'transparent',
          borderWidth: 1,
          pointBackgroundColor: '#10B981',
          pointBorderColor: '#ffffff',
          pointBorderWidth: 2,
          pointRadius: 3,
          pointHoverRadius: 5,
          tension: 0.3
        },
        {
          label: 'Department Average',
          data: [2.9, 3.0, 3.1, 3.2, 3.3, 3.35],
          borderColor: '#F59E0B',
          backgroundColor: 'transparent',
          borderWidth: 1,
          pointBackgroundColor: '#F59E0B',
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
          borderColor: '#3B82F6',
          borderWidth: 1,
          cornerRadius: 4,
          displayColors: false,
          callbacks: {
            label: function(context) {
              return context.dataset.label + ': ' + context.parsed.y.toFixed(2);
            }
          }
        }
      },
      scales: {
        y: {
          min: 2.5,
          max: 4.0,
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
            text: 'GPA',
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

  // Course Performance Chart (Doughnut)
  const coursePerformanceCtx = document.getElementById('coursePerformanceChart').getContext('2d');
  const coursePerformanceChart = new Chart(coursePerformanceCtx, {
    type: 'doughnut',
    data: {
      labels: ['A Grades', 'B Grades', 'C Grades', 'In Progress'],
      datasets: [{
        data: [4, 3, 1, 2],
        backgroundColor: ['#10B981', '#3B82F6', '#F59E0B', '#6B7280'],
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
              const labels = ['A Grades (90-100%)', 'B Grades (80-89%)', 'C Grades (70-79%)', 'In Progress'];
              const courses = [4, 3, 1, 2];
              return labels[context.dataIndex] + ': ' + courses[context.dataIndex] + ' courses';
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
    gradeTrendChart.resize();
    coursePerformanceChart.resize();
  });
</script>

</body>
</html>