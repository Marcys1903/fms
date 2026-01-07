<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// Check if user is Super Admin
try {
    $stmt = $pdo->prepare("SELECT role, level FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        header("Location: ../login.php?error=unauthorized");
        exit();
    }
    
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

// Fetch data from database
$departments = [];
$budgets = [];
$budgetTypes = ['department' => 'Department Budget', 'project' => 'Project Budget', 'event' => 'Event Budget'];

try {
    // Fetch departments
    $stmt = $pdo->query("SELECT id, name, abbreviation, annual_budget, ytd_spent, remaining_budget FROM departments ORDER BY name");
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch existing budgets
    $stmt = $pdo->query("
        SELECT b.*, d.name as department_name, d.abbreviation as dept_abbr,
               CONCAT(u.firstname, ' ', u.lastname) as prepared_by,
               CASE 
                 WHEN b.status = 'Under Budget' THEN 'Under Budget'
                 WHEN b.status = 'Over Budget' THEN 'Over Budget'
                 WHEN b.status = 'On Track' THEN 'On Track'
                 ELSE b.status
               END as status_label
        FROM budgets b
        LEFT JOIN departments d ON b.department_id = d.id
        LEFT JOIN users u ON b.created_by = u.id
        ORDER BY b.created_at DESC
        LIMIT 50
    ");
    $budgets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Error fetching data: " . $e->getMessage();
}

// Handle budget creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_budget'])) {
        try {
            $budgetData = [
                'department_id' => $_POST['department_id'],
                'budget_name' => $_POST['budget_name'],
                'amount' => $_POST['amount'],
                'fiscal_year' => $_POST['fiscal_year'],
                'budget_type' => $_POST['budget_type'],
                'description' => $_POST['description'] ?? '',
                'status' => 'On Track',
                'created_by' => $_SESSION['user_id']
            ];
            
            $sql = "INSERT INTO budgets (department_id, budget_name, amount, fiscal_year, budget_type, description, status, created_by) 
                    VALUES (:department_id, :budget_name, :amount, :fiscal_year, :budget_type, :description, :status, :created_by)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($budgetData);
            
            // Update department's YTD spent
            if (isset($_POST['update_dept_budget']) && $_POST['update_dept_budget'] == '1') {
                $stmt = $pdo->prepare("UPDATE departments SET ytd_spent = ytd_spent + :amount WHERE id = :department_id");
                $stmt->execute(['amount' => $_POST['amount'], 'department_id' => $_POST['department_id']]);
            }
            
            $_SESSION['success'] = "Budget created successfully!";
            header("Location: budgetplanning.php?tab=drafts");
            exit();
            
        } catch (Exception $e) {
            $_SESSION['error'] = "Error creating budget: " . $e->getMessage();
        }
    }
    
    // Handle budget status update
    if (isset($_POST['update_status'])) {
        try {
            $stmt = $pdo->prepare("UPDATE budgets SET status = ? WHERE id = ?");
            $stmt->execute([$_POST['status'], $_POST['budget_id']]);
            
            $_SESSION['success'] = "Budget status updated successfully!";
            header("Location: budgetplanning.php");
            exit();
            
        } catch (Exception $e) {
            $_SESSION['error'] = "Error updating budget status: " . $e->getMessage();
        }
    }
    
    // Handle budget deletion
    if (isset($_POST['delete_budget'])) {
        try {
            // First get the budget amount for department update
            $stmt = $pdo->prepare("SELECT department_id, amount FROM budgets WHERE id = ?");
            $stmt->execute([$_POST['budget_id']]);
            $budget = $stmt->fetch();
            
            if ($budget) {
                // Update department's YTD spent (subtract the amount)
                $stmt = $pdo->prepare("UPDATE departments SET ytd_spent = ytd_spent - :amount WHERE id = :department_id");
                $stmt->execute(['amount' => $budget['amount'], 'department_id' => $budget['department_id']]);
                
                // Delete the budget
                $stmt = $pdo->prepare("DELETE FROM budgets WHERE id = ?");
                $stmt->execute([$_POST['budget_id']]);
                
                $_SESSION['success'] = "Budget deleted successfully!";
            }
            
            header("Location: budgetplanning.php");
            exit();
            
        } catch (Exception $e) {
            $_SESSION['error'] = "Error deleting budget: " . $e->getMessage();
        }
    }
}

// Session variables for display
$firstname = $_SESSION['firstname'] ?? '';
$middlename = $_SESSION['middlename'] ?? '';
$lastname = $_SESSION['lastname'] ?? '';
$role = $_SESSION['role'];

// Current tab
$currentTab = $_GET['tab'] ?? 'overview';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Budget Planning | Financial Management System</title>
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
    
    /* Smooth transitions */
    .transition-smooth {
      transition: all 0.3s ease;
    }
    
    /* Tab styling */
    .tab-active {
      border-bottom-color: #2563EB;
      color: #2563EB;
    }
    
    /* Budget status badges */
    .status-under-budget {
      background-color: #DCFCE7;
      color: #166534;
    }
    
    .status-over-budget {
      background-color: #FEE2E2;
      color: #991B1B;
    }
    
    .status-on-track {
      background-color: #DBEAFE;
      color: #1E40AF;
    }
    
    /* Loading overlay */
    .loading-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(255, 255, 255, 0.8);
      z-index: 1000;
      align-items: center;
      justify-content: center;
    }
    
    .loading-spinner {
      border: 3px solid #f3f3f3;
      border-top: 3px solid #2563EB;
      border-radius: 50%;
      width: 50px;
      height: 50px;
      animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    /* Progress bar styles */
    .progress-bar {
      height: 8px;
      border-radius: 4px;
      overflow: hidden;
      background-color: #E5E7EB;
    }
    
    .progress-fill {
      height: 100%;
      transition: width 0.3s ease;
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
    <!-- Notifications -->
    <div class="notification-container relative">
      <button class="p-2 rounded-full hover:bg-gray-100 relative">
        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        <span class="absolute top-1 right-1 w-2 h-2 bg-danger rounded-full"></span>
      </button>
    </div>
    
    <!-- User Profile -->
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
    </div>
  </div>
</header>

<!-- Main Layout -->
<div class="flex pt-16 h-full">
  <!-- Sidebar -->
  <?php include 'sidebar.php'; ?>

  <!-- Main Content -->
  <main class="flex-1 overflow-y-auto p-6">
    
    <!-- Page Header -->
    <div class="mb-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Budget Planning</h1>
          <p class="text-gray-600 mt-1">Draft institutional budgets for departments, projects, and events</p>
        </div>
        <button onclick="openCreateModal()" class="inline-flex items-center gap-2 bg-accent hover:bg-blue-700 text-white font-medium py-2.5 px-4 rounded-lg transition-smooth">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Create New Budget
        </button>
      </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="border-b border-gray-200 mb-6">
      <nav class="flex space-x-8">
        <a href="?tab=overview" class="py-3 px-1 font-medium text-sm border-b-2 <?php echo $currentTab == 'overview' ? 'border-accent text-accent tab-active' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
          Overview
        </a>
        <a href="?tab=all" class="py-3 px-1 font-medium text-sm border-b-2 <?php echo $currentTab == 'all' ? 'border-accent text-accent tab-active' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
          All Budgets
          <span class="ml-2 bg-gray-100 text-gray-800 text-xs px-2 py-0.5 rounded-full"><?php echo count($budgets); ?></span>
        </a>
        <a href="?tab=under-budget" class="py-3 px-1 font-medium text-sm border-b-2 <?php echo $currentTab == 'under-budget' ? 'border-accent text-accent tab-active' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
          Under Budget
          <span class="ml-2 bg-green-100 text-green-800 text-xs px-2 py-0.5 rounded-full"><?php echo count(array_filter($budgets, fn($b) => $b['status'] === 'Under Budget')); ?></span>
        </a>
        <a href="?tab=over-budget" class="py-3 px-1 font-medium text-sm border-b-2 <?php echo $currentTab == 'over-budget' ? 'border-accent text-accent tab-active' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
          Over Budget
          <span class="ml-2 bg-red-100 text-red-800 text-xs px-2 py-0.5 rounded-full"><?php echo count(array_filter($budgets, fn($b) => $b['status'] === 'Over Budget')); ?></span>
        </a>
        <a href="?tab=departments" class="py-3 px-1 font-medium text-sm border-b-2 <?php echo $currentTab == 'departments' ? 'border-accent text-accent tab-active' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
          Departments
        </a>
      </nav>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
      <div class="mb-6 p-4 bg-success-light border border-success/20 rounded-lg">
        <div class="flex items-center gap-3">
          <div class="w-5 h-5 text-success">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
          </div>
          <p class="text-sm text-gray-900"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
        </div>
      </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
      <div class="mb-6 p-4 bg-danger-light border border-danger/20 rounded-lg">
        <div class="flex items-center gap-3">
          <div class="w-5 h-5 text-danger">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
          </div>
          <p class="text-sm text-gray-900"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
        </div>
      </div>
    <?php endif; ?>

    <!-- Content based on current tab -->
    <?php if ($currentTab == 'overview'): ?>
    
      <!-- Overview Content -->
      <div class="space-y-6">
        <!-- Recent Budgets & Department Overview -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Recent Budgets -->
          <div class="bg-white rounded-lg shadow lg:col-span-2">
            <div class="p-5 border-b border-gray-200">
              <h3 class="font-semibold text-gray-900 text-base">Recent Budget Plans</h3>
              <p class="text-sm text-gray-500">Latest budget submissions</p>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Budget Name</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Department</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Amount</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Created</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <?php foreach (array_slice($budgets, 0, 5) as $budget): ?>
                  <tr>
                    <td class="py-3 px-4">
                      <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($budget['budget_name']); ?></p>
                      <p class="text-xs text-gray-500"><?php echo htmlspecialchars($budget['fiscal_year']); ?></p>
                    </td>
                    <td class="py-3 px-4 text-sm text-gray-700">
                      <?php echo htmlspecialchars($budget['department_name'] ?? 'N/A'); ?>
                      <?php if ($budget['dept_abbr']): ?>
                      <span class="text-xs text-gray-500">(<?php echo $budget['dept_abbr']; ?>)</span>
                      <?php endif; ?>
                    </td>
                    <td class="py-3 px-4">
                      <span class="font-medium text-gray-900">₱<?php echo number_format($budget['amount'], 2); ?></span>
                    </td>
                    <td class="py-3 px-4">
                      <span class="text-xs px-2 py-1 rounded-full font-medium status-<?php echo strtolower(str_replace(' ', '-', $budget['status'])); ?>">
                        <?php echo htmlspecialchars($budget['status_label']); ?>
                      </span>
                    </td>
                    <td class="py-3 px-4 text-xs text-gray-500">
                      <?php echo date('M d, Y', strtotime($budget['created_at'])); ?>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <div class="p-4 border-t border-gray-200 text-center">
              <a href="?tab=all" class="text-sm text-accent font-medium hover:text-blue-700 transition-smooth">View All Budgets →</a>
            </div>
          </div>

          <!-- Department Budget Utilization -->
          <div class="bg-white rounded-lg shadow p-5">
            <h3 class="font-semibold text-gray-900 text-base mb-5">Department Budget Status</h3>
            <div class="space-y-4">
              <?php foreach (array_slice($departments, 0, 4) as $dept): ?>
              <div>
                <div class="flex justify-between text-sm mb-1">
                  <span class="font-medium text-gray-700"><?php echo htmlspecialchars($dept['name']); ?></span>
                  <span class="text-gray-600">₱<?php echo number_format($dept['ytd_spent'], 2); ?> / ₱<?php echo number_format($dept['annual_budget'], 2); ?></span>
                </div>
                <div class="progress-bar">
                  <?php
                  $percentage = $dept['annual_budget'] > 0 ? ($dept['ytd_spent'] / $dept['annual_budget']) * 100 : 0;
                  $color = $percentage > 90 ? 'bg-danger' : ($percentage > 70 ? 'bg-warning' : 'bg-success');
                  ?>
                  <div class="progress-fill <?php echo $color; ?>" style="width: <?php echo min($percentage, 100); ?>%"></div>
                </div>
                <div class="flex justify-between text-xs text-gray-500 mt-1">
                  <span>Spent: <?php echo number_format($percentage, 1); ?>%</span>
                  <span>Remaining: ₱<?php echo number_format($dept['remaining_budget'], 2); ?></span>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
            <div class="mt-6 pt-4 border-t border-gray-200">
              <a href="?tab=departments" class="text-sm text-accent font-medium hover:text-blue-700 transition-smooth">View All Departments →</a>
            </div>
          </div>
        </div>
      </div>

    <?php elseif (in_array($currentTab, ['all', 'under-budget', 'over-budget'])): ?>
    
      <!-- Budgets List -->
      <div class="bg-white rounded-lg shadow">
        <div class="p-5 border-b border-gray-200">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
              <h3 class="font-semibold text-gray-900 text-base">
                <?php 
                  echo $currentTab == 'all' ? 'All Budgets' : ucfirst(str_replace('-', ' ', $currentTab)) . ' Budgets';
                ?>
              </h3>
              <p class="text-sm text-gray-500">
                <?php 
                  $filteredBudgets = $currentTab == 'all' ? $budgets : 
                                   array_filter($budgets, fn($b) => strtolower(str_replace(' ', '-', $b['status'])) === $currentTab);
                  echo count($filteredBudgets) . ' budget' . (count($filteredBudgets) != 1 ? 's' : '') . ' found';
                ?>
              </p>
            </div>
            <div class="flex items-center gap-3">
              <div class="relative">
                <input type="text" placeholder="Search budgets..." class="text-sm border border-gray-300 rounded-lg pl-10 pr-4 py-2 w-full sm:w-64 focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
                <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
              </div>
              <select class="text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white">
                <option>Filter by Department</option>
                <?php foreach ($departments as $dept): ?>
                <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>
        
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Budget Details</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Department</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Amount</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Fiscal Year</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Prepared By</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <?php 
              $filteredBudgets = $currentTab == 'all' ? $budgets : 
                               array_filter($budgets, fn($b) => strtolower(str_replace(' ', '-', $b['status'])) === $currentTab);
              
              if (empty($filteredBudgets)): ?>
              <tr>
                <td colspan="6" class="py-8 px-4 text-center">
                  <div class="text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p class="font-medium text-gray-900">No <?php echo str_replace('-', ' ', $currentTab); ?> budgets found</p>
                    <p class="text-sm mt-1">Create a new budget or check other tabs</p>
                  </div>
                </td>
              </tr>
              <?php else: ?>
                <?php foreach ($filteredBudgets as $budget): ?>
                <tr>
                  <td class="py-3 px-4">
                    <div>
                      <p class="font-medium text-gray-900"><?php echo htmlspecialchars($budget['budget_name']); ?></p>
                      <p class="text-sm text-gray-500 mt-0.5">
                        <?php echo isset($budget['budget_type']) ? htmlspecialchars($budgetTypes[$budget['budget_type']] ?? ucfirst($budget['budget_type'])) : 'N/A'; ?>
                      </p>
                      <div class="flex items-center gap-2 mt-1">
                        <span class="text-xs px-2 py-0.5 rounded-full status-<?php echo strtolower(str_replace(' ', '-', $budget['status'])); ?>">
                          <?php echo htmlspecialchars($budget['status_label']); ?>
                        </span>
                      </div>
                    </div>
                  </td>
                  <td class="py-3 px-4">
                    <p class="text-sm text-gray-900"><?php echo htmlspecialchars($budget['department_name'] ?? 'N/A'); ?></p>
                    <?php if ($budget['dept_abbr']): ?>
                    <p class="text-xs text-gray-500"><?php echo $budget['dept_abbr']; ?></p>
                    <?php endif; ?>
                  </td>
                  <td class="py-3 px-4">
                    <p class="font-medium text-gray-900">₱<?php echo number_format($budget['amount'], 2); ?></p>
                  </td>
                  <td class="py-3 px-4">
                    <span class="text-sm text-gray-700"><?php echo htmlspecialchars($budget['fiscal_year']); ?></span>
                  </td>
                  <td class="py-3 px-4">
                    <p class="text-sm text-gray-900"><?php echo htmlspecialchars($budget['prepared_by'] ?? 'System'); ?></p>
                    <p class="text-xs text-gray-500"><?php echo date('M d, Y', strtotime($budget['created_at'])); ?></p>
                  </td>
                  <td class="py-3 px-4">
                    <div class="flex items-center gap-2">
                      <button onclick="viewBudget(<?php echo $budget['id']; ?>)" class="text-sm text-accent hover:text-blue-700 font-medium p-2 hover:bg-accent-light rounded transition-smooth">
                        View
                      </button>
                      <button onclick="editBudget(<?php echo $budget['id']; ?>)" class="text-sm text-warning hover:text-amber-700 font-medium p-2 hover:bg-warning-light rounded transition-smooth">
                        Edit
                      </button>
                      <button onclick="deleteBudget(<?php echo $budget['id']; ?>)" class="text-sm text-danger hover:text-red-700 font-medium p-2 hover:bg-danger-light rounded transition-smooth">
                        Delete
                      </button>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        
        <?php if (!empty($filteredBudgets)): ?>
        <div class="p-4 border-t border-gray-200">
          <div class="flex items-center justify-between">
            <p class="text-sm text-gray-500">
              Showing <?php echo count($filteredBudgets); ?> of <?php echo count($filteredBudgets); ?> budgets
            </p>
            <div class="flex items-center gap-2">
              <button class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">Previous</button>
              <span class="px-3 py-1.5 text-sm text-gray-700">Page 1 of 1</span>
              <button class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">Next</button>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>

    <?php elseif ($currentTab == 'departments'): ?>
    
      <!-- Departments Content -->
      <div class="bg-white rounded-lg shadow">
        <div class="p-5 border-b border-gray-200">
          <h3 class="font-semibold text-gray-900 text-base">Department Budget Overview</h3>
          <p class="text-sm text-gray-500">Annual budget allocation and utilization</p>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Department</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Annual Budget</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">YTD Spent</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Remaining</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Utilization</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <?php foreach ($departments as $dept): ?>
              <tr>
                <td class="py-3 px-4">
                  <div>
                    <p class="font-medium text-gray-900"><?php echo htmlspecialchars($dept['name']); ?></p>
                    <?php if ($dept['abbreviation']): ?>
                    <p class="text-sm text-gray-500"><?php echo $dept['abbreviation']; ?></p>
                    <?php endif; ?>
                  </div>
                </td>
                <td class="py-3 px-4">
                  <p class="font-medium text-gray-900">₱<?php echo number_format($dept['annual_budget'], 2); ?></p>
                </td>
                <td class="py-3 px-4">
                  <p class="text-gray-700">₱<?php echo number_format($dept['ytd_spent'], 2); ?></p>
                </td>
                <td class="py-3 px-4">
                  <p class="font-medium <?php echo $dept['remaining_budget'] < 0 ? 'text-danger' : 'text-success'; ?>">
                    ₱<?php echo number_format($dept['remaining_budget'], 2); ?>
                  </p>
                </td>
                <td class="py-3 px-4">
                  <div class="w-32">
                    <div class="flex justify-between text-xs mb-1">
                      <span><?php echo number_format($dept['annual_budget'] > 0 ? ($dept['ytd_spent'] / $dept['annual_budget']) * 100 : 0, 1); ?>%</span>
                    </div>
                    <div class="progress-bar">
                      <?php
                      $percentage = $dept['annual_budget'] > 0 ? ($dept['ytd_spent'] / $dept['annual_budget']) * 100 : 0;
                      $color = $percentage > 90 ? 'bg-danger' : ($percentage > 70 ? 'bg-warning' : 'bg-success');
                      ?>
                      <div class="progress-fill <?php echo $color; ?>" style="width: <?php echo min($percentage, 100); ?>%"></div>
                    </div>
                  </div>
                </td>
                <td class="py-3 px-4">
                  <?php
                  $status = $dept['remaining_budget'] < 0 ? 'Over Budget' : 
                           ($percentage > 90 ? 'Near Limit' : ($percentage > 70 ? 'Moderate' : 'Healthy'));
                  $statusClass = $dept['remaining_budget'] < 0 ? 'status-over-budget' : 
                               ($percentage > 90 ? 'status-over-budget' : ($percentage > 70 ? 'status-on-track' : 'status-under-budget'));
                  ?>
                  <span class="text-xs px-2 py-1 rounded-full font-medium <?php echo $statusClass; ?>">
                    <?php echo $status; ?>
                  </span>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

    <?php endif; ?>
  </main>
</div>

<!-- Footer -->
<footer class="bg-white border-t py-3">
  <div class="px-6 flex flex-col sm:flex-row justify-between items-center text-xs text-gray-500">
    <div class="mb-2 sm:mb-0">
      <span>© 2025 BCP Financial Management System v3.2.1</span>
      <span class="mx-2 hidden sm:inline">•</span>
      <span class="block sm:inline mt-1 sm:mt-0">Budget Planning Module</span>
    </div>
    <div class="flex items-center gap-3">
      <span class="text-success font-medium">Last updated: <?php echo date('M d, Y H:i'); ?></span>
    </div>
  </div>
</footer>

<!-- Create Budget Modal -->
<div id="createModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 hidden">
  <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-hidden">
    <div class="p-6 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900">Create New Budget Plan</h3>
        <button onclick="closeCreateModal()" class="text-gray-400 hover:text-gray-500">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
      <p class="text-sm text-gray-500 mt-1">Draft institutional budgets for approval consideration</p>
    </div>
    
    <form method="POST" action="" id="budgetForm" class="overflow-y-auto max-h-[calc(90vh-200px)]">
      <div class="p-6 space-y-6">
        <!-- Basic Information -->
        <div>
          <h4 class="font-medium text-gray-900 mb-4">Basic Information</h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Budget Name *</label>
              <input type="text" name="budget_name" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent" placeholder="e.g., Q3 Operational Budget">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Department *</label>
              <select name="department_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent" onchange="updateDepartmentInfo(this.value)">
                <option value="">Select Department</option>
                <?php foreach ($departments as $dept): ?>
                <option value="<?php echo $dept['id']; ?>" data-annual="<?php echo $dept['annual_budget']; ?>" data-spent="<?php echo $dept['ytd_spent']; ?>" data-remaining="<?php echo $dept['remaining_budget']; ?>">
                  <?php echo htmlspecialchars($dept['name']); ?> (<?php echo $dept['abbreviation'] ?? 'N/A'; ?>)
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Budget Type *</label>
              <select name="budget_type" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
                <option value="">Select Type</option>
                <option value="department">Department Budget</option>
                <option value="project">Project Budget</option>
                <option value="event">Event Budget</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Fiscal Year *</label>
              <select name="fiscal_year" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
                <?php 
                $currentYear = date('Y');
                for ($year = $currentYear; $year >= 2020; $year--): 
                ?>
                <option value="<?php echo $year . '-' . ($year + 1); ?>" <?php echo $year == $currentYear ? 'selected' : ''; ?>>
                  <?php echo $year . '-' . ($year + 1); ?>
                </option>
                <?php endfor; ?>
              </select>
            </div>
          </div>
          
          <!-- Department Budget Info -->
          <div id="deptInfo" class="mt-4 p-4 bg-gray-50 rounded-lg hidden">
            <div class="grid grid-cols-3 gap-4 text-sm">
              <div>
                <p class="text-gray-500">Annual Budget</p>
                <p class="font-medium" id="deptAnnual">₱0.00</p>
              </div>
              <div>
                <p class="text-gray-500">YTD Spent</p>
                <p class="font-medium" id="deptSpent">₱0.00</p>
              </div>
              <div>
                <p class="text-gray-500">Remaining</p>
                <p class="font-medium" id="deptRemaining">₱0.00</p>
              </div>
            </div>
          </div>
          
          <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
            <textarea name="description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent" placeholder="Describe the purpose and scope of this budget..."></textarea>
          </div>
        </div>

        <!-- Budget Amount -->
        <div>
          <div class="mb-4">
            <h4 class="font-medium text-gray-900">Budget Amount</h4>
            <p class="text-sm text-gray-500">Set the total budget amount</p>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Amount (₱) *</label>
            <input type="number" name="amount" required step="0.01" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent" placeholder="0.00" onchange="checkBudgetLimit()">
            <p class="text-xs text-gray-500 mt-1">Enter the total budget amount in Philippine Pesos</p>
          </div>
          
          <div id="budgetWarning" class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg hidden">
            <div class="flex items-start gap-2">
              <svg class="w-5 h-5 text-yellow-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
              </svg>
              <div>
                <p class="text-sm font-medium text-yellow-800" id="warningText"></p>
                <p class="text-xs text-yellow-700 mt-1">Consider adjusting the amount or requesting additional funds.</p>
              </div>
            </div>
          </div>
          
          <div class="mt-4">
            <label class="flex items-center gap-2">
              <input type="checkbox" name="update_dept_budget" value="1" class="rounded border-gray-300 text-accent focus:ring-accent">
              <span class="text-sm text-gray-700">Update department's YTD spent with this amount</span>
            </label>
            <p class="text-xs text-gray-500 mt-1">If checked, the department's YTD spent will be increased by this budget amount</p>
          </div>
        </div>
      </div>
      
      <div class="p-6 border-t border-gray-200 bg-gray-50">
        <div class="flex items-center justify-between">
          <button type="button" onclick="closeCreateModal()" class="px-5 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-smooth">
            Cancel
          </button>
          <button type="submit" name="create_budget" value="create" class="px-5 py-2.5 bg-accent hover:bg-blue-700 text-white font-medium rounded-lg transition-smooth">
            Create Budget
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay">
  <div class="loading-spinner"></div>
</div>

<!-- JavaScript -->
<script>
  // Initialize on page load
  document.addEventListener('DOMContentLoaded', function() {
    // Add animation to tables
    document.querySelectorAll('table').forEach((table, index) => {
      table.style.animationDelay = `${index * 0.1}s`;
      table.classList.add('animate-fade-in');
    });
  });

  // Budget Modal Functions
  function openCreateModal() {
    document.getElementById('createModal').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
  }

  function closeCreateModal() {
    document.getElementById('createModal').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    resetForm();
  }

  // Update department info when department is selected
  function updateDepartmentInfo(deptId) {
    const deptInfo = document.getElementById('deptInfo');
    const select = document.querySelector('select[name="department_id"]');
    const selectedOption = select.options[select.selectedIndex];
    
    if (deptId && selectedOption.dataset.annual) {
      const annual = parseFloat(selectedOption.dataset.annual) || 0;
      const spent = parseFloat(selectedOption.dataset.spent) || 0;
      const remaining = parseFloat(selectedOption.dataset.remaining) || 0;
      
      document.getElementById('deptAnnual').textContent = '₱' + annual.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
      document.getElementById('deptSpent').textContent = '₱' + spent.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
      document.getElementById('deptRemaining').textContent = '₱' + remaining.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
      
      deptInfo.classList.remove('hidden');
    } else {
      deptInfo.classList.add('hidden');
    }
    
    checkBudgetLimit();
  }

  // Check if budget exceeds department remaining budget
  function checkBudgetLimit() {
    const deptSelect = document.querySelector('select[name="department_id"]');
    const amountInput = document.querySelector('input[name="amount"]');
    const warningDiv = document.getElementById('budgetWarning');
    const warningText = document.getElementById('warningText');
    
    if (!deptSelect.value || !amountInput.value) {
      warningDiv.classList.add('hidden');
      return;
    }
    
    const selectedOption = deptSelect.options[deptSelect.selectedIndex];
    const remaining = parseFloat(selectedOption.dataset.remaining) || 0;
    const amount = parseFloat(amountInput.value) || 0;
    
    if (amount > remaining) {
      const excess = amount - remaining;
      warningText.textContent = `Warning: This budget exceeds department's remaining budget by ₱${excess.toFixed(2)}`;
      warningDiv.classList.remove('hidden');
    } else if (amount > (remaining * 0.8)) {
      warningText.textContent = `Note: This budget uses ${((amount / remaining) * 100).toFixed(1)}% of department's remaining budget`;
      warningDiv.classList.remove('hidden');
    } else {
      warningDiv.classList.add('hidden');
    }
  }

  // Reset form
  function resetForm() {
    document.getElementById('budgetForm').reset();
    document.getElementById('deptInfo').classList.add('hidden');
    document.getElementById('budgetWarning').classList.add('hidden');
  }

  // Budget Actions
  function viewBudget(budgetId) {
    showLoading();
    // In a real application, this would fetch and display budget details
    setTimeout(() => {
      hideLoading();
      alert('View budget details for ID: ' + budgetId + '\n\nThis would show a detailed view of the budget including department information, amount, and status.');
    }, 500);
  }
  
  function editBudget(budgetId) {
    showLoading();
    setTimeout(() => {
      hideLoading();
      alert('Edit budget with ID: ' + budgetId + '\n\nThis would open an edit form pre-filled with the budget data.');
      // In real app: window.location.href = 'edit_budget.php?id=' + budgetId;
    }, 500);
  }
  
  function deleteBudget(budgetId) {
    if (confirm('Are you sure you want to delete this budget?\n\nThis action will also update the department\'s YTD spent by subtracting the budget amount.')) {
      showLoading();
      // Create a form and submit it
      const form = document.createElement('form');
      form.method = 'POST';
      form.innerHTML = `
        <input type="hidden" name="budget_id" value="${budgetId}">
        <input type="hidden" name="delete_budget" value="1">
      `;
      document.body.appendChild(form);
      form.submit();
    }
  }

  // Update budget status
  function updateBudgetStatus(budgetId, status) {
    if (confirm(`Change budget status to "${status}"?`)) {
      const form = document.createElement('form');
      form.method = 'POST';
      form.innerHTML = `
        <input type="hidden" name="budget_id" value="${budgetId}">
        <input type="hidden" name="status" value="${status}">
        <input type="hidden" name="update_status" value="1">
      `;
      document.body.appendChild(form);
      form.submit();
    }
  }

  // Loading Functions
  function showLoading() {
    document.getElementById('loadingOverlay').style.display = 'flex';
  }
  
  function hideLoading() {
    document.getElementById('loadingOverlay').style.display = 'none';
  }

  // Form validation
  document.getElementById('budgetForm').addEventListener('submit', function(e) {
    const amount = parseFloat(document.querySelector('input[name="amount"]').value);
    if (amount <= 0) {
      e.preventDefault();
      alert('Please enter a valid budget amount greater than 0.');
      return false;
    }
    
    const deptSelect = document.querySelector('select[name="department_id"]');
    if (!deptSelect.value) {
      e.preventDefault();
      alert('Please select a department.');
      return false;
    }
    
    showLoading();
    return true;
  });

  // Close modal on ESC key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      closeCreateModal();
    }
  });
</script>

</body>
</html>