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
$budgets = [];
$approvers = [];
$approvalHistory = [];

try {
    // Check if approval_status column exists in budgets table
    $stmt = $pdo->query("SHOW COLUMNS FROM budgets LIKE 'approval_status'");
    $hasApprovalStatus = $stmt->rowCount() > 0;
    
    // Fetch budgets for approval - check both status types
    if ($hasApprovalStatus) {
        $stmt = $pdo->query("
            SELECT b.*, 
                   b.approval_status,
                   d.name as department_name, 
                   d.abbreviation as dept_abbr,
                   CONCAT(u.firstname, ' ', u.lastname) as prepared_by,
                   CASE 
                     WHEN b.approval_status = 'pending' THEN 'Pending Approval'
                     WHEN b.approval_status = 'under_review' THEN 'Under Review'
                     WHEN b.approval_status = 'approved' THEN 'Approved'
                     WHEN b.approval_status = 'rejected' THEN 'Rejected'
                     WHEN b.approval_status = 'revision_requested' THEN 'Revision Requested'
                     ELSE 'Pending Approval'
                   END as approval_status_label
            FROM budgets b
            LEFT JOIN departments d ON b.department_id = d.id
            LEFT JOIN users u ON b.created_by = u.id
            WHERE b.approval_status IN ('pending', 'under_review', 'revision_requested')
            ORDER BY b.created_at DESC
        ");
    } else {
        // If no approval_status column, treat all budgets as pending
        $stmt = $pdo->query("
            SELECT b.*, 
                   'pending' as approval_status,
                   d.name as department_name, 
                   d.abbreviation as dept_abbr,
                   CONCAT(u.firstname, ' ', u.lastname) as prepared_by,
                   'Pending Approval' as approval_status_label
            FROM budgets b
            LEFT JOIN departments d ON b.department_id = d.id
            LEFT JOIN users u ON b.created_by = u.id
            ORDER BY b.created_at DESC
        ");
    }
    $budgets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch approvers (users with appropriate roles)
    $stmt = $pdo->query("
        SELECT id, CONCAT(firstname, ' ', lastname) as full_name, role, email, department
        FROM users 
        WHERE role IN ('Finance Manager', 'Department Head', 'Super Administrator', 'Administrator')
        AND status = 'active'
        ORDER BY role, firstname
    ");
    $approvers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if approval_history table exists and fetch history
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'approval_history'");
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->query("
                SELECT ah.*, b.budget_name, 
                       CONCAT(u.firstname, ' ', u.lastname) as approver_name,
                       d.name as department_name
                FROM approval_history ah
                LEFT JOIN budgets b ON ah.budget_id = b.id
                LEFT JOIN users u ON ah.approver_id = u.id
                LEFT JOIN departments d ON b.department_id = d.id
                ORDER BY ah.action_date DESC
                LIMIT 20
            ");
            $approvalHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        // Table doesn't exist or has issues
        $approvalHistory = [];
    }
    
} catch (Exception $e) {
    $error = "Error fetching data: " . $e->getMessage();
}

// Handle approval actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Route budget for approval
    if (isset($_POST['route_for_approval'])) {
        try {
            $budgetId = $_POST['budget_id'];
            $approverId = $_POST['approver_id'];
            $comments = $_POST['comments'] ?? '';
            
            // Check if approval_status column exists
            $stmt = $pdo->query("SHOW COLUMNS FROM budgets LIKE 'approval_status'");
            if ($stmt->rowCount() > 0) {
                // Update budget approval status
                $stmt = $pdo->prepare("UPDATE budgets SET approval_status = 'under_review', current_approver = ? WHERE id = ?");
                $stmt->execute([$approverId, $budgetId]);
            } else {
                // Create approval_status column if it doesn't exist
                $pdo->query("ALTER TABLE budgets ADD COLUMN approval_status VARCHAR(20) DEFAULT 'pending'");
                $pdo->query("ALTER TABLE budgets ADD COLUMN current_approver INT UNSIGNED DEFAULT NULL");
                $stmt = $pdo->prepare("UPDATE budgets SET approval_status = 'under_review', current_approver = ? WHERE id = ?");
                $stmt->execute([$approverId, $budgetId]);
            }
            
            // Check if approval_history table exists
            $stmt = $pdo->query("SHOW TABLES LIKE 'approval_history'");
            if ($stmt->rowCount() > 0) {
                // Record in approval history
                $stmt = $pdo->prepare("
                    INSERT INTO approval_history (budget_id, approver_id, action, comments, action_date)
                    VALUES (?, ?, 'routed_for_approval', ?, NOW())
                ");
                $stmt->execute([$budgetId, $approverId, $comments]);
            }
            
            $_SESSION['success'] = "Budget routed for approval successfully!";
            header("Location: approvalmanagement.php");
            exit();
            
        } catch (Exception $e) {
            $_SESSION['error'] = "Error routing budget: " . $e->getMessage();
        }
    }
    
    // Approve budget
    if (isset($_POST['approve_budget'])) {
        try {
            $budgetId = $_POST['budget_id'];
            $comments = $_POST['comments'] ?? '';
            
            // Check and update approval status columns
            $stmt = $pdo->query("SHOW COLUMNS FROM budgets LIKE 'approval_status'");
            if ($stmt->rowCount() > 0) {
                // Update budget status
                $stmt = $pdo->prepare("UPDATE budgets SET approval_status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
                $stmt->execute([$_SESSION['user_id'], $budgetId]);
            } else {
                // Create columns if they don't exist
                $pdo->query("ALTER TABLE budgets ADD COLUMN approval_status VARCHAR(20) DEFAULT 'pending'");
                $pdo->query("ALTER TABLE budgets ADD COLUMN approved_by INT UNSIGNED DEFAULT NULL");
                $pdo->query("ALTER TABLE budgets ADD COLUMN approved_at DATETIME DEFAULT NULL");
                $stmt = $pdo->prepare("UPDATE budgets SET approval_status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
                $stmt->execute([$_SESSION['user_id'], $budgetId]);
            }
            
            // Record in approval history if table exists
            $stmt = $pdo->query("SHOW TABLES LIKE 'approval_history'");
            if ($stmt->rowCount() > 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO approval_history (budget_id, approver_id, action, comments, action_date)
                    VALUES (?, ?, 'approved', ?, NOW())
                ");
                $stmt->execute([$budgetId, $_SESSION['user_id'], $comments]);
            }
            
            $_SESSION['success'] = "Budget approved successfully!";
            header("Location: approvalmanagement.php");
            exit();
            
        } catch (Exception $e) {
            $_SESSION['error'] = "Error approving budget: " . $e->getMessage();
        }
    }
    
    // Reject budget
    if (isset($_POST['reject_budget'])) {
        try {
            $budgetId = $_POST['budget_id'];
            $comments = $_POST['comments'] ?? '';
            
            // Check and update approval status columns
            $stmt = $pdo->query("SHOW COLUMNS FROM budgets LIKE 'approval_status'");
            if ($stmt->rowCount() > 0) {
                // Update budget status
                $stmt = $pdo->prepare("UPDATE budgets SET approval_status = 'rejected', rejected_by = ?, rejected_at = NOW() WHERE id = ?");
                $stmt->execute([$_SESSION['user_id'], $budgetId]);
            } else {
                // Create columns if they don't exist
                $pdo->query("ALTER TABLE budgets ADD COLUMN approval_status VARCHAR(20) DEFAULT 'pending'");
                $pdo->query("ALTER TABLE budgets ADD COLUMN rejected_by INT UNSIGNED DEFAULT NULL");
                $pdo->query("ALTER TABLE budgets ADD COLUMN rejected_at DATETIME DEFAULT NULL");
                $stmt = $pdo->prepare("UPDATE budgets SET approval_status = 'rejected', rejected_by = ?, rejected_at = NOW() WHERE id = ?");
                $stmt->execute([$_SESSION['user_id'], $budgetId]);
            }
            
            // Record in approval history if table exists
            $stmt = $pdo->query("SHOW TABLES LIKE 'approval_history'");
            if ($stmt->rowCount() > 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO approval_history (budget_id, approver_id, action, comments, action_date)
                    VALUES (?, ?, 'rejected', ?, NOW())
                ");
                $stmt->execute([$budgetId, $_SESSION['user_id'], $comments]);
            }
            
            $_SESSION['success'] = "Budget rejected successfully!";
            header("Location: approvalmanagement.php");
            exit();
            
        } catch (Exception $e) {
            $_SESSION['error'] = "Error rejecting budget: " . $e->getMessage();
        }
    }
    
    // Request revision
    if (isset($_POST['request_revision'])) {
        try {
            $budgetId = $_POST['budget_id'];
            $comments = $_POST['comments'] ?? '';
            
            // Check and update approval status columns
            $stmt = $pdo->query("SHOW COLUMNS FROM budgets LIKE 'approval_status'");
            if ($stmt->rowCount() > 0) {
                // Update budget status
                $stmt = $pdo->prepare("UPDATE budgets SET approval_status = 'revision_requested', revision_requested_at = NOW() WHERE id = ?");
                $stmt->execute([$budgetId]);
            } else {
                // Create columns if they don't exist
                $pdo->query("ALTER TABLE budgets ADD COLUMN approval_status VARCHAR(20) DEFAULT 'pending'");
                $pdo->query("ALTER TABLE budgets ADD COLUMN revision_requested_at DATETIME DEFAULT NULL");
                $stmt = $pdo->prepare("UPDATE budgets SET approval_status = 'revision_requested', revision_requested_at = NOW() WHERE id = ?");
                $stmt->execute([$budgetId]);
            }
            
            // Record in approval history if table exists
            $stmt = $pdo->query("SHOW TABLES LIKE 'approval_history'");
            if ($stmt->rowCount() > 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO approval_history (budget_id, approver_id, action, comments, action_date)
                    VALUES (?, ?, 'revision_requested', ?, NOW())
                ");
                $stmt->execute([$budgetId, $_SESSION['user_id'], $comments]);
            }
            
            $_SESSION['success'] = "Revision requested successfully!";
            header("Location: approvalmanagement.php");
            exit();
            
        } catch (Exception $e) {
            $_SESSION['error'] = "Error requesting revision: " . $e->getMessage();
        }
    }
}

// Session variables for display
$firstname = $_SESSION['firstname'] ?? '';
$middlename = $_SESSION['middlename'] ?? '';
$lastname = $_SESSION['lastname'] ?? '';
$role = $_SESSION['role'];

// Current tab
$currentTab = $_GET['tab'] ?? 'pending';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Approval Management | Financial Management System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="../assets/bcpnobg.png">
  
  <!-- CDN Links -->
  <script src="https://cdn.tailwindcss.com"></script>
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
    
    /* Smooth transitions */
    .transition-smooth {
      transition: all 0.3s ease;
    }
    
    /* Tab styling */
    .tab-active {
      border-bottom-color: #2563EB;
      color: #2563EB;
    }
    
    /* Status badges */
    .status-pending {
      background-color: #FEF3C7;
      color: #92400E;
    }
    
    .status-under-review {
      background-color: #DBEAFE;
      color: #1E40AF;
    }
    
    .status-approved {
      background-color: #DCFCE7;
      color: #166534;
    }
    
    .status-rejected {
      background-color: #FEE2E2;
      color: #991B1B;
    }
    
    .status-revision-requested {
      background-color: #FEF3C7;
      color: #92400E;
    }
    
    /* Timeline styling */
    .timeline-item {
      position: relative;
      padding-left: 30px;
      margin-bottom: 20px;
    }
    
    .timeline-item:before {
      content: '';
      position: absolute;
      left: 0;
      top: 5px;
      width: 12px;
      height: 12px;
      border-radius: 50%;
      background-color: #2563EB;
    }
    
    .timeline-item:after {
      content: '';
      position: absolute;
      left: 5px;
      top: 17px;
      width: 2px;
      height: calc(100% + 3px);
      background-color: #E5E7EB;
    }
    
    .timeline-item:last-child:after {
      display: none;
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
          <h1 class="text-2xl font-bold text-gray-900">Approval Management</h1>
          <p class="text-gray-600 mt-1">Route budget proposals for approval and monitor their status</p>
        </div>
        <div class="flex items-center gap-3">
          <span class="text-sm text-gray-600">
            <span class="font-medium"><?php 
              $pendingCount = count(array_filter($budgets, fn($b) => $b['approval_status'] === 'pending'));
              echo $pendingCount;
            ?></span> pending approval
          </span>
        </div>
      </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="border-b border-gray-200 mb-6">
      <nav class="flex space-x-8">
        <a href="?tab=pending" class="py-3 px-1 font-medium text-sm border-b-2 <?php echo $currentTab == 'pending' ? 'border-accent text-accent tab-active' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
          Pending
          <span class="ml-2 bg-yellow-100 text-yellow-800 text-xs px-2 py-0.5 rounded-full"><?php echo count(array_filter($budgets, fn($b) => $b['approval_status'] === 'pending')); ?></span>
        </a>
        <a href="?tab=under-review" class="py-3 px-1 font-medium text-sm border-b-2 <?php echo $currentTab == 'under-review' ? 'border-accent text-accent tab-active' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
          Under Review
          <span class="ml-2 bg-blue-100 text-blue-800 text-xs px-2 py-0.5 rounded-full"><?php echo count(array_filter($budgets, fn($b) => $b['approval_status'] === 'under_review')); ?></span>
        </a>
        <a href="?tab=revision-requested" class="py-3 px-1 font-medium text-sm border-b-2 <?php echo $currentTab == 'revision-requested' ? 'border-accent text-accent tab-active' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
          Revision Needed
          <span class="ml-2 bg-orange-100 text-orange-800 text-xs px-2 py-0.5 rounded-full"><?php echo count(array_filter($budgets, fn($b) => $b['approval_status'] === 'revision_requested')); ?></span>
        </a>
        <a href="?tab=history" class="py-3 px-1 font-medium text-sm border-b-2 <?php echo $currentTab == 'history' ? 'border-accent text-accent tab-active' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
          Approval History
        </a>
        <a href="?tab=approvers" class="py-3 px-1 font-medium text-sm border-b-2 <?php echo $currentTab == 'approvers' ? 'border-accent text-accent tab-active' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
          Approvers
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
    <?php if (in_array($currentTab, ['pending', 'under-review', 'revision-requested'])): ?>
    
      <?php 
      $statusMap = [
        'pending' => 'pending',
        'under-review' => 'under_review', 
        'revision-requested' => 'revision_requested'
      ];
      $currentStatus = $statusMap[$currentTab];
      $filteredBudgets = array_filter($budgets, fn($b) => $b['approval_status'] === $currentStatus);
      ?>
      
      <!-- Budgets for Approval -->
      <div class="bg-white rounded-lg shadow">
        <div class="p-5 border-b border-gray-200">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
              <h3 class="font-semibold text-gray-900 text-base">
                <?php 
                  $titles = [
                    'pending' => 'Pending Approval',
                    'under-review' => 'Under Review',
                    'revision-requested' => 'Revision Requested'
                  ];
                  echo $titles[$currentTab];
                ?>
              </h3>
              <p class="text-sm text-gray-500">
                <?php echo count($filteredBudgets); ?> budget<?php echo count($filteredBudgets) != 1 ? 's' : ''; ?> requiring action
              </p>
            </div>
            <div class="flex items-center gap-3">
              <div class="relative">
                <input type="text" placeholder="Search budgets..." class="text-sm border border-gray-300 rounded-lg pl-10 pr-4 py-2 w-full sm:w-64 focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
                <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
              </div>
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
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Prepared By</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Submitted</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <?php if (empty($filteredBudgets)): ?>
              <tr>
                <td colspan="6" class="py-8 px-4 text-center">
                  <div class="text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <p class="font-medium text-gray-900">No budgets <?php echo $currentTab; ?></p>
                    <p class="text-sm mt-1">All budgets are currently processed</p>
                  </div>
                </td>
              </tr>
              <?php else: ?>
                <?php foreach ($filteredBudgets as $budget): ?>
                <tr>
                  <td class="py-3 px-4">
                    <div>
                      <p class="font-medium text-gray-900"><?php echo htmlspecialchars($budget['budget_name']); ?></p>
                      <p class="text-sm text-gray-500 mt-0.5"><?php echo htmlspecialchars($budget['fiscal_year']); ?></p>
                      <div class="flex items-center gap-2 mt-1">
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium status-<?php echo str_replace('_', '-', $budget['approval_status']); ?>">
                          <?php echo htmlspecialchars($budget['approval_status_label']); ?>
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
                    <p class="text-sm text-gray-900"><?php echo htmlspecialchars($budget['prepared_by'] ?? 'System'); ?></p>
                  </td>
                  <td class="py-3 px-4">
                    <p class="text-xs text-gray-500"><?php echo date('M d, Y', strtotime($budget['created_at'])); ?></p>
                    <p class="text-xs text-gray-400"><?php echo date('h:i A', strtotime($budget['created_at'])); ?></p>
                  </td>
                  <td class="py-3 px-4">
                    <div class="flex flex-col gap-2">
                      <?php if ($currentTab == 'pending'): ?>
                      <button onclick="openRouteModal(<?php echo $budget['id']; ?>)" class="text-sm text-accent hover:text-blue-700 font-medium p-2 hover:bg-accent-light rounded transition-smooth text-left">
                        Route for Approval
                      </button>
                      <?php elseif ($currentTab == 'under-review'): ?>
                      <button onclick="openApproveModal(<?php echo $budget['id']; ?>)" class="text-sm text-success hover:text-green-700 font-medium p-2 hover:bg-success-light rounded transition-smooth text-left">
                        Approve
                      </button>
                      <button onclick="openRejectModal(<?php echo $budget['id']; ?>)" class="text-sm text-danger hover:text-red-700 font-medium p-2 hover:bg-danger-light rounded transition-smooth text-left">
                        Reject
                      </button>
                      <button onclick="openRevisionModal(<?php echo $budget['id']; ?>)" class="text-sm text-warning hover:text-amber-700 font-medium p-2 hover:bg-warning-light rounded transition-smooth text-left">
                        Request Revision
                      </button>
                      <?php elseif ($currentTab == 'revision-requested'): ?>
                      <button onclick="openReviewModal(<?php echo $budget['id']; ?>)" class="text-sm text-accent hover:text-blue-700 font-medium p-2 hover:bg-accent-light rounded transition-smooth text-left">
                        Review Changes
                      </button>
                      <?php endif; ?>
                      <button onclick="viewBudgetDetails(<?php echo $budget['id']; ?>)" class="text-sm text-gray-600 hover:text-gray-800 font-medium p-2 hover:bg-gray-100 rounded transition-smooth text-left">
                        View Details
                      </button>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    <?php elseif ($currentTab == 'history'): ?>
    
      <!-- Approval History -->
      <div class="bg-white rounded-lg shadow">
        <div class="p-5 border-b border-gray-200">
          <h3 class="font-semibold text-gray-900 text-base">Approval History</h3>
          <p class="text-sm text-gray-500">Track all budget approval activities</p>
        </div>
        
        <div class="p-6">
          <div class="space-y-6">
            <?php if (empty($approvalHistory)): ?>
            <div class="text-center py-8">
              <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              <p class="font-medium text-gray-900">No approval history yet</p>
              <p class="text-sm text-gray-500 mt-1">Approval activities will appear here once you start approving budgets</p>
            </div>
            <?php else: ?>
              <?php foreach ($approvalHistory as $history): ?>
              <div class="timeline-item">
                <div class="bg-gray-50 rounded-lg p-4">
                  <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                    <div>
                      <div class="flex items-center gap-2 mb-1">
                        <span class="font-medium text-gray-900"><?php echo htmlspecialchars($history['approver_name']); ?></span>
                        <span class="text-xs px-2 py-0.5 rounded-full 
                          <?php 
                          $actionColors = [
                            'routed_for_approval' => 'bg-blue-100 text-blue-800',
                            'approved' => 'bg-green-100 text-green-800',
                            'rejected' => 'bg-red-100 text-red-800',
                            'revision_requested' => 'bg-yellow-100 text-yellow-800'
                          ];
                          echo $actionColors[$history['action']] ?? 'bg-gray-100 text-gray-800';
                          ?>">
                          <?php 
                          $actionLabels = [
                            'routed_for_approval' => 'Routed for Approval',
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                            'revision_requested' => 'Revision Requested'
                          ];
                          echo $actionLabels[$history['action']] ?? ucfirst(str_replace('_', ' ', $history['action']));
                          ?>
                        </span>
                      </div>
                      <p class="text-sm text-gray-700"><?php echo htmlspecialchars($history['budget_name']); ?></p>
                      <?php if (!empty($history['comments'])): ?>
                      <div class="mt-2 p-2 bg-white border border-gray-200 rounded">
                        <p class="text-xs text-gray-600"><?php echo htmlspecialchars($history['comments']); ?></p>
                      </div>
                      <?php endif; ?>
                    </div>
                    <div class="text-right">
                      <p class="text-xs text-gray-500"><?php echo date('M d, Y', strtotime($history['action_date'])); ?></p>
                      <p class="text-xs text-gray-400"><?php echo date('h:i A', strtotime($history['action_date'])); ?></p>
                    </div>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

    <?php elseif ($currentTab == 'approvers'): ?>
    
      <!-- Approvers Management -->
      <div class="bg-white rounded-lg shadow">
        <div class="p-5 border-b border-gray-200">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
              <h3 class="font-semibold text-gray-900 text-base">Approval Authorities</h3>
              <p class="text-sm text-gray-500">Manage users with budget approval rights</p>
            </div>
          </div>
        </div>
        
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Approver</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Role</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Email</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Department</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Approval Level</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <?php if (empty($approvers)): ?>
              <tr>
                <td colspan="6" class="py-8 px-4 text-center">
                  <div class="text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5 3.75a6 6 0 00-9.5-4.197"/>
                    </svg>
                    <p class="font-medium text-gray-900">No approvers found</p>
                    <p class="text-sm mt-1">Users with appropriate roles will appear here</p>
                  </div>
                </td>
              </tr>
              <?php else: ?>
                <?php foreach ($approvers as $approver): ?>
                <tr>
                  <td class="py-3 px-4">
                    <div class="flex items-center gap-3">
                      <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo urlencode($approver['full_name']); ?>" class="w-8 h-8 rounded-full border">
                      <div>
                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($approver['full_name']); ?></p>
                      </div>
                    </div>
                  </td>
                  <td class="py-3 px-4">
                    <span class="text-sm text-gray-700"><?php echo htmlspecialchars($approver['role']); ?></span>
                  </td>
                  <td class="py-3 px-4">
                    <p class="text-sm text-gray-700"><?php echo htmlspecialchars($approver['email']); ?></p>
                  </td>
                  <td class="py-3 px-4">
                    <p class="text-sm text-gray-700"><?php echo htmlspecialchars($approver['department'] ?? 'N/A'); ?></p>
                  </td>
                  <td class="py-3 px-4">
                    <?php
                    $level = '';
                    $levelColor = '';
                    if ($approver['role'] == 'Super Administrator') {
                      $level = 'Full Authority';
                      $levelColor = 'bg-purple-100 text-purple-800';
                    } elseif ($approver['role'] == 'Finance Manager') {
                      $level = 'Financial Approval';
                      $levelColor = 'bg-blue-100 text-blue-800';
                    } elseif ($approver['role'] == 'Department Head') {
                      $level = 'Departmental';
                      $levelColor = 'bg-green-100 text-green-800';
                    } else {
                      $level = 'Standard';
                      $levelColor = 'bg-gray-100 text-gray-800';
                    }
                    ?>
                    <span class="text-xs px-2 py-1 rounded-full font-medium <?php echo $levelColor; ?>">
                      <?php echo $level; ?>
                    </span>
                  </td>
                  <td class="py-3 px-4">
                    <span class="text-xs px-2 py-1 rounded-full font-medium bg-success-light text-success">
                      Active
                    </span>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
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
      <span class="block sm:inline mt-1 sm:mt-0">Approval Management Module</span>
    </div>
    <div class="flex items-center gap-3">
      <span class="text-success font-medium">Last updated: <?php echo date('M d, Y H:i'); ?></span>
    </div>
  </div>
</footer>

<!-- Route for Approval Modal -->
<div id="routeModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 hidden">
  <div class="bg-white rounded-lg shadow-xl w-full max-w-lg max-h-[90vh] overflow-hidden">
    <div class="p-6 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900">Route Budget for Approval</h3>
        <button onclick="closeRouteModal()" class="text-gray-400 hover:text-gray-500">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
      <p class="text-sm text-gray-500 mt-1">Assign this budget to an approver</p>
    </div>
    
    <form method="POST" action="" id="routeForm">
      <div class="p-6 space-y-4">
        <input type="hidden" name="budget_id" id="routeBudgetId">
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Select Approver *</label>
          <select name="approver_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
            <option value="">Choose an approver...</option>
            <?php foreach ($approvers as $approver): ?>
            <option value="<?php echo $approver['id']; ?>"><?php echo htmlspecialchars($approver['full_name']); ?> (<?php echo $approver['role']; ?>)</option>
            <?php endforeach; ?>
          </select>
          <p class="text-xs text-gray-500 mt-1">This person will review and approve/reject the budget</p>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Routing Instructions/Comments</label>
          <textarea name="comments" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent" placeholder="Add any specific instructions for the approver..."></textarea>
        </div>
      </div>
      
      <div class="p-6 border-t border-gray-200 bg-gray-50">
        <div class="flex items-center justify-between">
          <button type="button" onclick="closeRouteModal()" class="px-5 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-smooth">
            Cancel
          </button>
          <button type="submit" name="route_for_approval" value="1" class="px-5 py-2.5 bg-accent hover:bg-blue-700 text-white font-medium rounded-lg transition-smooth">
            Route for Approval
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Approve Budget Modal -->
<div id="approveModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 hidden">
  <div class="bg-white rounded-lg shadow-xl w-full max-w-lg max-h-[90vh] overflow-hidden">
    <div class="p-6 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900">Approve Budget</h3>
        <button onclick="closeApproveModal()" class="text-gray-400 hover:text-gray-500">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
      <p class="text-sm text-gray-500 mt-1">Approve this budget proposal</p>
    </div>
    
    <form method="POST" action="" id="approveForm">
      <div class="p-6 space-y-4">
        <input type="hidden" name="budget_id" id="approveBudgetId">
        
        <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-sm font-medium text-green-800">You are about to approve this budget.</p>
          </div>
          <p class="text-xs text-green-700 mt-1">Once approved, the budget will be finalized and can no longer be edited.</p>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Approval Comments (Optional)</label>
          <textarea name="comments" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent" placeholder="Add any comments about your approval..."></textarea>
        </div>
      </div>
      
      <div class="p-6 border-t border-gray-200 bg-gray-50">
        <div class="flex items-center justify-between">
          <button type="button" onclick="closeApproveModal()" class="px-5 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-smooth">
            Cancel
          </button>
          <button type="submit" name="approve_budget" value="1" class="px-5 py-2.5 bg-success hover:bg-green-700 text-white font-medium rounded-lg transition-smooth">
            Approve Budget
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Reject Budget Modal -->
<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 hidden">
  <div class="bg-white rounded-lg shadow-xl w-full max-w-lg max-h-[90vh] overflow-hidden">
    <div class="p-6 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900">Reject Budget</h3>
        <button onclick="closeRejectModal()" class="text-gray-400 hover:text-gray-500">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
      <p class="text-sm text-gray-500 mt-1">Reject this budget proposal</p>
    </div>
    
    <form method="POST" action="" id="rejectForm">
      <div class="p-6 space-y-4">
        <input type="hidden" name="budget_id" id="rejectBudgetId">
        
        <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
            <p class="text-sm font-medium text-red-800">You are about to reject this budget.</p>
          </div>
          <p class="text-xs text-red-700 mt-1">Please provide a reason for rejection to help the preparer understand.</p>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Rejection Reason *</label>
          <textarea name="comments" required rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent" placeholder="Explain why this budget is being rejected..."></textarea>
          <p class="text-xs text-gray-500 mt-1">This feedback will be sent to the budget preparer</p>
        </div>
      </div>
      
      <div class="p-6 border-t border-gray-200 bg-gray-50">
        <div class="flex items-center justify-between">
          <button type="button" onclick="closeRejectModal()" class="px-5 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-smooth">
            Cancel
          </button>
          <button type="submit" name="reject_budget" value="1" class="px-5 py-2.5 bg-danger hover:bg-red-700 text-white font-medium rounded-lg transition-smooth">
            Reject Budget
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Request Revision Modal -->
<div id="revisionModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 hidden">
  <div class="bg-white rounded-lg shadow-xl w-full max-w-lg max-h-[90vh] overflow-hidden">
    <div class="p-6 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900">Request Revision</h3>
        <button onclick="closeRevisionModal()" class="text-gray-400 hover:text-gray-500">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
      <p class="text-sm text-gray-500 mt-1">Request changes to this budget proposal</p>
    </div>
    
    <form method="POST" action="" id="revisionForm">
      <div class="p-6 space-y-4">
        <input type="hidden" name="budget_id" id="revisionBudgetId">
        
        <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
            <p class="text-sm font-medium text-yellow-800">Request specific revisions to this budget.</p>
          </div>
          <p class="text-xs text-yellow-700 mt-1">The budget will be returned to the preparer for changes.</p>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Revision Instructions *</label>
          <textarea name="comments" required rows="4" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent" placeholder="Specify what changes are needed..."></textarea>
          <p class="text-xs text-gray-500 mt-1">Be specific about what needs to be revised</p>
        </div>
      </div>
      
      <div class="p-6 border-t border-gray-200 bg-gray-50">
        <div class="flex items-center justify-between">
          <button type="button" onclick="closeRevisionModal()" class="px-5 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-smooth">
            Cancel
          </button>
          <button type="submit" name="request_revision" value="1" class="px-5 py-2.5 bg-warning hover:bg-amber-700 text-white font-medium rounded-lg transition-smooth">
            Request Revision
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

  // Modal Functions for Routing
  function openRouteModal(budgetId) {
    document.getElementById('routeBudgetId').value = budgetId;
    document.getElementById('routeModal').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
  }

  function closeRouteModal() {
    document.getElementById('routeModal').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
  }

  // Modal Functions for Approval
  function openApproveModal(budgetId) {
    document.getElementById('approveBudgetId').value = budgetId;
    document.getElementById('approveModal').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
  }

  function closeApproveModal() {
    document.getElementById('approveModal').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
  }

  // Modal Functions for Rejection
  function openRejectModal(budgetId) {
    document.getElementById('rejectBudgetId').value = budgetId;
    document.getElementById('rejectModal').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
  }

  function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
  }

  // Modal Functions for Revision
  function openRevisionModal(budgetId) {
    document.getElementById('revisionBudgetId').value = budgetId;
    document.getElementById('revisionModal').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
  }

  function closeRevisionModal() {
    document.getElementById('revisionModal').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
  }

  // View Budget Details
  function viewBudgetDetails(budgetId) {
    showLoading();
    setTimeout(() => {
      hideLoading();
      alert('Viewing budget details for ID: ' + budgetId + '\n\nThis would show complete budget information including line items, department details, and approval history.');
    }, 500);
  }

  // Review Changes (for revision requested budgets)
  function openReviewModal(budgetId) {
    showLoading();
    setTimeout(() => {
      hideLoading();
      alert('Review changes for budget ID: ' + budgetId + '\n\nThis would show a comparison between the original budget and the revised version.');
    }, 500);
  }

  // Loading Functions
  function showLoading() {
    document.getElementById('loadingOverlay').style.display = 'flex';
  }
  
  function hideLoading() {
    document.getElementById('loadingOverlay').style.display = 'none';
  }

  // Form validation
  document.getElementById('routeForm')?.addEventListener('submit', function(e) {
    const approverSelect = document.querySelector('#routeForm select[name="approver_id"]');
    if (!approverSelect.value) {
      e.preventDefault();
      alert('Please select an approver.');
      return false;
    }
    showLoading();
    return true;
  });

  document.getElementById('rejectForm')?.addEventListener('submit', function(e) {
    const comments = document.querySelector('#rejectForm textarea[name="comments"]');
    if (!comments.value.trim()) {
      e.preventDefault();
      alert('Please provide a reason for rejection.');
      return false;
    }
    showLoading();
    return true;
  });

  document.getElementById('revisionForm')?.addEventListener('submit', function(e) {
    const comments = document.querySelector('#revisionForm textarea[name="comments"]');
    if (!comments.value.trim()) {
      e.preventDefault();
      alert('Please provide revision instructions.');
      return false;
    }
    showLoading();
    return true;
  });

  // Close modals on ESC key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      closeRouteModal();
      closeApproveModal();
      closeRejectModal();
      closeRevisionModal();
    }
  });
</script>

</body>
</html>