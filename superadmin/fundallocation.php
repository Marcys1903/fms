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
$approvedBudgets = [];
$allocations = [];
$allocationTypes = ['department' => 'Department', 'project' => 'Project', 'event' => 'Event'];

try {
    // Fetch departments with their budget information
    $stmt = $pdo->query("
        SELECT d.*, 
               COUNT(b.id) as total_budgets,
               SUM(CASE WHEN b.approval_status = 'approved' THEN b.amount ELSE 0 END) as approved_amount,
               SUM(CASE WHEN b.approval_status = 'approved' THEN 1 ELSE 0 END) as approved_count
        FROM departments d
        LEFT JOIN budgets b ON d.id = b.department_id
        GROUP BY d.id
        ORDER BY d.name
    ");
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch approved budgets
    $stmt = $pdo->query("
        SELECT b.*, d.name as department_name, d.abbreviation as dept_abbr,
               CONCAT(u.firstname, ' ', u.lastname) as prepared_by
        FROM budgets b
        LEFT JOIN departments d ON b.department_id = d.id
        LEFT JOIN users u ON b.created_by = u.id
        WHERE b.approval_status = 'approved'
        ORDER BY b.created_at DESC
    ");
    $approvedBudgets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if fund_allocations table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'fund_allocations'");
    if ($stmt->rowCount() > 0) {
        // Fetch existing allocations
        $stmt = $pdo->query("
            SELECT fa.*, b.budget_name, d.name as department_name,
                   CONCAT(u.firstname, ' ', u.lastname) as allocated_by_name
            FROM fund_allocations fa
            LEFT JOIN budgets b ON fa.budget_id = b.id
            LEFT JOIN departments d ON fa.department_id = d.id
            LEFT JOIN users u ON fa.allocated_by = u.id
            ORDER BY fa.allocation_date DESC
            LIMIT 20
        ");
        $allocations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (Exception $e) {
    $error = "Error fetching data: " . $e->getMessage();
}

// Handle fund allocation actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Allocate funds
    if (isset($_POST['allocate_funds'])) {
        try {
            $budgetId = $_POST['budget_id'];
            $departmentId = $_POST['department_id'];
            $amount = $_POST['amount'];
            $allocationType = $_POST['allocation_type'];
            $description = $_POST['description'] ?? '';
            
            // Check if fund_allocations table exists
            $stmt = $pdo->query("SHOW TABLES LIKE 'fund_allocations'");
            if ($stmt->rowCount() == 0) {
                // Create fund_allocations table if it doesn't exist
                $pdo->query("
                    CREATE TABLE fund_allocations (
                        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                        budget_id INT UNSIGNED NOT NULL,
                        department_id INT UNSIGNED NOT NULL,
                        allocation_type VARCHAR(50) NOT NULL,
                        amount DECIMAL(15,2) NOT NULL,
                        description TEXT,
                        allocated_by INT UNSIGNED NOT NULL,
                        allocation_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        status VARCHAR(20) DEFAULT 'active',
                        PRIMARY KEY (id),
                        KEY idx_budget_id (budget_id),
                        KEY idx_department_id (department_id),
                        KEY idx_allocation_date (allocation_date)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
                ");
            }
            
            // Insert allocation record
            $stmt = $pdo->prepare("
                INSERT INTO fund_allocations (budget_id, department_id, allocation_type, amount, description, allocated_by, allocation_date)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$budgetId, $departmentId, $allocationType, $amount, $description, $_SESSION['user_id']]);
            
            // Update department's allocated amount
            $stmt = $pdo->prepare("UPDATE departments SET allocated_amount = COALESCE(allocated_amount, 0) + ? WHERE id = ?");
            $stmt->execute([$amount, $departmentId]);
            
            $_SESSION['success'] = "Funds allocated successfully!";
            header("Location: fundallocation.php");
            exit();
            
        } catch (Exception $e) {
            $_SESSION['error'] = "Error allocating funds: " . $e->getMessage();
        }
    }
    
    // Adjust allocation
    if (isset($_POST['adjust_allocation'])) {
        try {
            $allocationId = $_POST['allocation_id'];
            $newAmount = $_POST['new_amount'];
            $reason = $_POST['reason'] ?? '';
            
            // Get current allocation details
            $stmt = $pdo->prepare("SELECT amount, department_id FROM fund_allocations WHERE id = ?");
            $stmt->execute([$allocationId]);
            $allocation = $stmt->fetch();
            
            if ($allocation) {
                $oldAmount = $allocation['amount'];
                $departmentId = $allocation['department_id'];
                $difference = $newAmount - $oldAmount;
                
                // Update allocation
                $stmt = $pdo->prepare("UPDATE fund_allocations SET amount = ?, adjusted_at = NOW(), adjustment_reason = ? WHERE id = ?");
                $stmt->execute([$newAmount, $reason, $allocationId]);
                
                // Update department's allocated amount
                $stmt = $pdo->prepare("UPDATE departments SET allocated_amount = allocated_amount + ? WHERE id = ?");
                $stmt->execute([$difference, $departmentId]);
                
                $_SESSION['success'] = "Allocation adjusted successfully!";
            }
            
            header("Location: fundallocation.php");
            exit();
            
        } catch (Exception $e) {
            $_SESSION['error'] = "Error adjusting allocation: " . $e->getMessage();
        }
    }
    
    // Release funds
    if (isset($_POST['release_funds'])) {
        try {
            $allocationId = $_POST['allocation_id'];
            $releaseAmount = $_POST['release_amount'];
            $releaseNotes = $_POST['release_notes'] ?? '';
            
            // Get allocation details
            $stmt = $pdo->prepare("SELECT amount, department_id, released_amount FROM fund_allocations WHERE id = ?");
            $stmt->execute([$allocationId]);
            $allocation = $stmt->fetch();
            
            if ($allocation) {
                $totalAmount = $allocation['amount'];
                $previouslyReleased = $allocation['released_amount'] ?? 0;
                $newReleasedAmount = $previouslyReleased + $releaseAmount;
                
                if ($newReleasedAmount > $totalAmount) {
                    $_SESSION['error'] = "Release amount exceeds allocated amount!";
                } else {
                    // Update allocation with release information
                    $stmt = $pdo->prepare("
                        UPDATE fund_allocations 
                        SET released_amount = ?, 
                            last_release_date = NOW(),
                            release_notes = CONCAT(COALESCE(release_notes, ''), '\n', ?)
                        WHERE id = ?
                    ");
                    $stmt->execute([$newReleasedAmount, $releaseNotes . ' (' . date('Y-m-d H:i:s') . ')', $allocationId]);
                    
                    // Add to release history
                    $stmt = $pdo->prepare("
                        INSERT INTO fund_releases (allocation_id, amount, release_notes, released_by, release_date)
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$allocationId, $releaseAmount, $releaseNotes, $_SESSION['user_id']]);
                    
                    $_SESSION['success'] = "Funds released successfully!";
                }
            }
            
            header("Location: fundallocation.php");
            exit();
            
        } catch (Exception $e) {
            $_SESSION['error'] = "Error releasing funds: " . $e->getMessage();
        }
    }
}

// Session variables for display
$firstname = $_SESSION['firstname'] ?? '';
$middlename = $_SESSION['middlename'] ?? '';
$lastname = $_SESSION['lastname'] ?? '';
$role = $_SESSION['role'];

// Current tab
$currentTab = $_GET['tab'] ?? 'allocate';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Fund Allocation Management | Financial Management System</title>
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
    .status-active {
      background-color: #DCFCE7;
      color: #166534;
    }
    
    .status-completed {
      background-color: #DBEAFE;
      color: #1E40AF;
    }
    
    .status-partial {
      background-color: #FEF3C7;
      color: #92400E;
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
    
    /* Allocation card styles */
    .allocation-card {
      border-left: 4px solid;
      transition: all 0.2s ease;
    }
    
    .allocation-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
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
          <h1 class="text-2xl font-bold text-gray-900">Fund Allocation Management</h1>
          <p class="text-gray-600 mt-1">Distribute approved funds to departments, projects, and events</p>
        </div>
        <div class="flex items-center gap-3">
          <?php
          $totalApproved = array_sum(array_column($approvedBudgets, 'amount'));
          $totalAllocated = 0;
          foreach ($allocations as $alloc) {
            $totalAllocated += $alloc['amount'];
          }
          ?>
          <span class="text-sm text-gray-600">
            <span class="font-medium">₱<?php echo number_format($totalAllocated, 2); ?></span> allocated of 
            <span class="font-medium">₱<?php echo number_format($totalApproved, 2); ?></span> approved
          </span>
        </div>
      </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="border-b border-gray-200 mb-6">
      <nav class="flex space-x-8">
        <a href="?tab=allocate" class="py-3 px-1 font-medium text-sm border-b-2 <?php echo $currentTab == 'allocate' ? 'border-accent text-accent tab-active' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
          Allocate Funds
        </a>
        <a href="?tab=allocations" class="py-3 px-1 font-medium text-sm border-b-2 <?php echo $currentTab == 'allocations' ? 'border-accent text-accent tab-active' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
          Allocations
          <span class="ml-2 bg-blue-100 text-blue-800 text-xs px-2 py-0.5 rounded-full"><?php echo count($allocations); ?></span>
        </a>
        <a href="?tab=departments" class="py-3 px-1 font-medium text-sm border-b-2 <?php echo $currentTab == 'departments' ? 'border-accent text-accent tab-active' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
          Departments
        </a>
        <a href="?tab=approved" class="py-3 px-1 font-medium text-sm border-b-2 <?php echo $currentTab == 'approved' ? 'border-accent text-accent tab-active' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
          Approved Budgets
          <span class="ml-2 bg-green-100 text-green-800 text-xs px-2 py-0.5 rounded-full"><?php echo count($approvedBudgets); ?></span>
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
    <?php if ($currentTab == 'allocate'): ?>
    
      <!-- Allocate Funds Section -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Allocation Form -->
        <div class="bg-white rounded-lg shadow lg:col-span-2">
          <div class="p-5 border-b border-gray-200">
            <h3 class="font-semibold text-gray-900 text-base">Allocate Funds</h3>
            <p class="text-sm text-gray-500">Distribute approved budget to departments, projects, or events</p>
          </div>
          
          <form method="POST" action="" class="p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Budget *</label>
                <select name="budget_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent" onchange="updateBudgetInfo(this.value)">
                  <option value="">Choose an approved budget...</option>
                  <?php foreach ($approvedBudgets as $budget): ?>
                  <option value="<?php echo $budget['id']; ?>" data-amount="<?php echo $budget['amount']; ?>" data-department="<?php echo $budget['department_name']; ?>">
                    <?php echo htmlspecialchars($budget['budget_name']); ?> - ₱<?php echo number_format($budget['amount'], 2); ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Allocation Type *</label>
                <select name="allocation_type" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
                  <option value="">Select type...</option>
                  <option value="department">Department Allocation</option>
                  <option value="project">Project Funding</option>
                  <option value="event">Event Sponsorship</option>
                  <option value="operational">Operational Expenses</option>
                  <option value="capital">Capital Expenditure</option>
                </select>
              </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Recipient Department *</label>
                <select name="department_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent" onchange="updateDepartmentInfo(this.value)">
                  <option value="">Select department...</option>
                  <?php foreach ($departments as $dept): ?>
                  <option value="<?php echo $dept['id']; ?>" data-annual="<?php echo $dept['annual_budget']; ?>" data-allocated="<?php echo $dept['allocated_amount'] ?? 0; ?>">
                    <?php echo htmlspecialchars($dept['name']); ?> (<?php echo $dept['abbreviation'] ?? 'N/A'; ?>)
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Allocation Amount (₱) *</label>
                <input type="number" name="amount" required step="0.01" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent" placeholder="0.00" onchange="checkAllocationLimit()">
                <p class="text-xs text-gray-500 mt-1">Enter the amount to allocate</p>
              </div>
            </div>
            
            <!-- Budget Information -->
            <div id="budgetInfo" class="p-4 bg-blue-50 border border-blue-200 rounded-lg hidden">
              <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                  <p class="text-gray-500">Budget Amount</p>
                  <p class="font-medium" id="budgetAmount">₱0.00</p>
                </div>
                <div>
                  <p class="text-gray-500">Department</p>
                  <p class="font-medium" id="budgetDepartment">N/A</p>
                </div>
              </div>
            </div>
            
            <!-- Department Information -->
            <div id="deptInfo" class="p-4 bg-green-50 border border-green-200 rounded-lg hidden">
              <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                  <p class="text-gray-500">Annual Budget</p>
                  <p class="font-medium" id="deptAnnual">₱0.00</p>
                </div>
                <div>
                  <p class="text-gray-500">Already Allocated</p>
                  <p class="font-medium" id="deptAllocated">₱0.00</p>
                </div>
              </div>
            </div>
            
            <!-- Warning Message -->
            <div id="allocationWarning" class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg hidden">
              <div class="flex items-start gap-2">
                <svg class="w-5 h-5 text-yellow-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                <div>
                  <p class="text-sm font-medium text-yellow-800" id="warningText"></p>
                  <p class="text-xs text-yellow-700 mt-1">Please review the allocation amount.</p>
                </div>
              </div>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Allocation Description</label>
              <textarea name="description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent" placeholder="Describe the purpose of this allocation..."></textarea>
              <p class="text-xs text-gray-500 mt-1">This helps track how the funds will be used</p>
            </div>
            
            <div class="pt-4 border-t border-gray-200">
              <button type="submit" name="allocate_funds" value="1" class="w-full bg-accent hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition-smooth">
                Allocate Funds
              </button>
            </div>
          </form>
        </div>
        
        <!-- Quick Stats -->
        <div class="space-y-6">
          <div class="bg-white rounded-lg shadow p-5">
            <h3 class="font-semibold text-gray-900 text-base mb-4">Allocation Summary</h3>
            <div class="space-y-4">
              <div>
                <p class="text-sm text-gray-500">Total Approved Budgets</p>
                <p class="text-2xl font-bold text-gray-900">₱<?php echo number_format($totalApproved, 2); ?></p>
              </div>
              <div>
                <p class="text-sm text-gray-500">Total Allocated</p>
                <p class="text-2xl font-bold text-accent">₱<?php echo number_format($totalAllocated, 2); ?></p>
              </div>
              <div>
                <p class="text-sm text-gray-500">Remaining for Allocation</p>
                <p class="text-2xl font-bold text-success">₱<?php echo number_format(max(0, $totalApproved - $totalAllocated), 2); ?></p>
              </div>
            </div>
          </div>
          
          <div class="bg-white rounded-lg shadow p-5">
            <h3 class="font-semibold text-gray-900 text-base mb-4">Recent Allocations</h3>
            <div class="space-y-3">
              <?php foreach (array_slice($allocations, 0, 3) as $alloc): ?>
              <div class="p-3 bg-gray-50 rounded-lg">
                <div class="flex justify-between items-start">
                  <div>
                    <p class="text-sm font-medium text-gray-900 truncate"><?php echo htmlspecialchars($alloc['budget_name']); ?></p>
                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($alloc['department_name']); ?></p>
                  </div>
                  <p class="text-sm font-medium text-gray-900">₱<?php echo number_format($alloc['amount'], 2); ?></p>
                </div>
                <p class="text-xs text-gray-500 mt-1"><?php echo date('M d, Y', strtotime($alloc['allocation_date'])); ?></p>
              </div>
              <?php endforeach; ?>
              
              <?php if (empty($allocations)): ?>
              <div class="text-center py-3">
                <p class="text-sm text-gray-500">No allocations yet</p>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

    <?php elseif ($currentTab == 'allocations'): ?>
    
      <!-- Allocations List -->
      <div class="bg-white rounded-lg shadow">
        <div class="p-5 border-b border-gray-200">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
              <h3 class="font-semibold text-gray-900 text-base">Fund Allocations</h3>
              <p class="text-sm text-gray-500">Track all fund distributions and releases</p>
            </div>
            <div class="flex items-center gap-3">
              <div class="relative">
                <input type="text" placeholder="Search allocations..." class="text-sm border border-gray-300 rounded-lg pl-10 pr-4 py-2 w-full sm:w-64 focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
                <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
              </div>
              <select class="text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white">
                <option>Filter by Type</option>
                <option value="department">Department</option>
                <option value="project">Project</option>
                <option value="event">Event</option>
              </select>
            </div>
          </div>
        </div>
        
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Allocation Details</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Department</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Budget</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Amount</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Allocated</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <?php if (empty($allocations)): ?>
              <tr>
                <td colspan="7" class="py-8 px-4 text-center">
                  <div class="text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p class="font-medium text-gray-900">No allocations found</p>
                    <p class="text-sm mt-1">Allocate funds from approved budgets to get started</p>
                  </div>
                </td>
              </tr>
              <?php else: ?>
                <?php foreach ($allocations as $alloc): ?>
                <tr>
                  <td class="py-3 px-4">
                    <div>
                      <p class="font-medium text-gray-900"><?php echo htmlspecialchars($alloc['budget_name']); ?></p>
                      <p class="text-sm text-gray-500 mt-0.5">
                        <?php echo htmlspecialchars($allocationTypes[$alloc['allocation_type']] ?? ucfirst($alloc['allocation_type'])); ?>
                      </p>
                      <?php if (!empty($alloc['description'])): ?>
                      <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars(substr($alloc['description'], 0, 100)); ?>...</p>
                      <?php endif; ?>
                    </div>
                  </td>
                  <td class="py-3 px-4">
                    <p class="text-sm text-gray-900"><?php echo htmlspecialchars($alloc['department_name'] ?? 'N/A'); ?></p>
                  </td>
                  <td class="py-3 px-4">
                    <p class="text-sm text-gray-700">₱<?php echo number_format($alloc['amount'], 2); ?></p>
                  </td>
                  <td class="py-3 px-4">
                    <p class="font-medium text-gray-900">₱<?php echo number_format($alloc['amount'], 2); ?></p>
                  </td>
                  <td class="py-3 px-4">
                    <?php
                    $released = $alloc['released_amount'] ?? 0;
                    $total = $alloc['amount'];
                    $percentage = $total > 0 ? ($released / $total) * 100 : 0;
                    
                    if ($percentage >= 100) {
                      $status = 'Completed';
                      $statusClass = 'status-completed';
                    } elseif ($percentage > 0) {
                      $status = 'Partially Released';
                      $statusClass = 'status-partial';
                    } else {
                      $status = 'Pending Release';
                      $statusClass = 'status-active';
                    }
                    ?>
                    <span class="text-xs px-2 py-1 rounded-full font-medium <?php echo $statusClass; ?>">
                      <?php echo $status; ?>
                    </span>
                    <?php if ($percentage > 0): ?>
                    <div class="mt-1 text-xs text-gray-500">
                      <?php echo number_format($percentage, 1); ?>% released
                    </div>
                    <?php endif; ?>
                  </td>
                  <td class="py-3 px-4">
                    <p class="text-xs text-gray-500"><?php echo date('M d, Y', strtotime($alloc['allocation_date'])); ?></p>
                    <p class="text-xs text-gray-400">by <?php echo htmlspecialchars($alloc['allocated_by_name'] ?? 'System'); ?></p>
                  </td>
                  <td class="py-3 px-4">
                    <div class="flex flex-col gap-1">
                      <button onclick="openReleaseModal(<?php echo $alloc['id']; ?>, <?php echo $alloc['amount']; ?>, <?php echo $released; ?>)" class="text-sm text-success hover:text-green-700 font-medium p-2 hover:bg-success-light rounded transition-smooth text-left">
                        Release Funds
                      </button>
                      <button onclick="openAdjustModal(<?php echo $alloc['id']; ?>, <?php echo $alloc['amount']; ?>)" class="text-sm text-warning hover:text-amber-700 font-medium p-2 hover:bg-warning-light rounded transition-smooth text-left">
                        Adjust
                      </button>
                      <button onclick="viewAllocationDetails(<?php echo $alloc['id']; ?>)" class="text-sm text-accent hover:text-blue-700 font-medium p-2 hover:bg-accent-light rounded transition-smooth text-left">
                        Details
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

    <?php elseif ($currentTab == 'departments'): ?>
    
      <!-- Department Allocation Overview -->
      <div class="bg-white rounded-lg shadow">
        <div class="p-5 border-b border-gray-200">
          <h3 class="font-semibold text-gray-900 text-base">Department Allocation Overview</h3>
          <p class="text-sm text-gray-500">View fund allocation by department</p>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Department</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Annual Budget</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Approved Amount</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Allocated</th>
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
                    <p class="text-xs text-gray-500 mt-1"><?php echo $dept['approved_count']; ?> approved budget<?php echo $dept['approved_count'] != 1 ? 's' : ''; ?></p>
                  </div>
                </td>
                <td class="py-3 px-4">
                  <p class="font-medium text-gray-900">₱<?php echo number_format($dept['annual_budget'], 2); ?></p>
                </td>
                <td class="py-3 px-4">
                  <p class="text-gray-700">₱<?php echo number_format($dept['approved_amount'] ?? 0, 2); ?></p>
                </td>
                <td class="py-3 px-4">
                  <p class="text-accent font-medium">₱<?php echo number_format($dept['allocated_amount'] ?? 0, 2); ?></p>
                </td>
                <td class="py-3 px-4">
                  <?php
                  $remaining = ($dept['approved_amount'] ?? 0) - ($dept['allocated_amount'] ?? 0);
                  $remainingColor = $remaining < 0 ? 'text-danger' : ($remaining == 0 ? 'text-gray-600' : 'text-success');
                  ?>
                  <p class="font-medium <?php echo $remainingColor; ?>">
                    ₱<?php echo number_format($remaining, 2); ?>
                  </p>
                </td>
                <td class="py-3 px-4">
                  <div class="w-40">
                    <?php
                    $approved = $dept['approved_amount'] ?? 0;
                    $allocated = $dept['allocated_amount'] ?? 0;
                    $percentage = $approved > 0 ? ($allocated / $approved) * 100 : 0;
                    $color = $percentage >= 100 ? 'bg-danger' : ($percentage >= 80 ? 'bg-warning' : 'bg-success');
                    ?>
                    <div class="flex justify-between text-xs mb-1">
                      <span><?php echo number_format($percentage, 1); ?>%</span>
                      <span>₱<?php echo number_format($allocated, 2); ?> / ₱<?php echo number_format($approved, 2); ?></span>
                    </div>
                    <div class="progress-bar">
                      <div class="progress-fill <?php echo $color; ?>" style="width: <?php echo min($percentage, 100); ?>%"></div>
                    </div>
                  </div>
                </td>
                <td class="py-3 px-4">
                  <?php
                  if ($percentage >= 100) {
                    $status = 'Fully Allocated';
                    $statusClass = 'status-completed';
                  } elseif ($percentage >= 80) {
                    $status = 'Nearly Full';
                    $statusClass = 'status-partial';
                  } elseif ($percentage > 0) {
                    $status = 'Partially Allocated';
                    $statusClass = 'status-active';
                  } else {
                    $status = 'Not Allocated';
                    $statusClass = 'bg-gray-100 text-gray-800';
                  }
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

    <?php elseif ($currentTab == 'approved'): ?>
    
      <!-- Approved Budgets -->
      <div class="bg-white rounded-lg shadow">
        <div class="p-5 border-b border-gray-200">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
              <h3 class="font-semibold text-gray-900 text-base">Approved Budgets</h3>
              <p class="text-sm text-gray-500">Budgets available for fund allocation</p>
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
        
        <div class="p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($approvedBudgets as $budget): ?>
            <div class="allocation-card border-accent bg-white rounded-lg shadow-sm border p-5">
              <div class="flex justify-between items-start mb-3">
                <div>
                  <h4 class="font-semibold text-gray-900 text-sm"><?php echo htmlspecialchars($budget['budget_name']); ?></h4>
                  <p class="text-xs text-gray-500 mt-0.5"><?php echo htmlspecialchars($budget['fiscal_year']); ?></p>
                </div>
                <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-800 font-medium">
                  Approved
                </span>
              </div>
              
              <div class="space-y-2 mb-4">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Department:</span>
                  <span class="font-medium"><?php echo htmlspecialchars($budget['department_name']); ?></span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Amount:</span>
                  <span class="font-bold text-gray-900">₱<?php echo number_format($budget['amount'], 2); ?></span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Prepared by:</span>
                  <span class="text-gray-700"><?php echo htmlspecialchars($budget['prepared_by']); ?></span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Created:</span>
                  <span class="text-gray-700"><?php echo date('M d, Y', strtotime($budget['created_at'])); ?></span>
                </div>
              </div>
              
              <div class="pt-3 border-t border-gray-100">
                <button onclick="allocateFromBudget(<?php echo $budget['id']; ?>)" class="w-full bg-accent hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-smooth text-sm">
                  Allocate from this Budget
                </button>
              </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($approvedBudgets)): ?>
            <div class="col-span-3 text-center py-8">
              <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
              </svg>
              <p class="font-medium text-gray-900">No approved budgets available</p>
              <p class="text-sm text-gray-500 mt-1">Approve budgets first to allocate funds</p>
            </div>
            <?php endif; ?>
          </div>
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
      <span class="block sm:inline mt-1 sm:mt-0">Fund Allocation Management Module</span>
    </div>
    <div class="flex items-center gap-3">
      <span class="text-success font-medium">Last updated: <?php echo date('M d, Y H:i'); ?></span>
    </div>
  </div>
</footer>

<!-- Release Funds Modal -->
<div id="releaseModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 hidden">
  <div class="bg-white rounded-lg shadow-xl w-full max-w-md max-h-[90vh] overflow-hidden">
    <div class="p-6 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900">Release Funds</h3>
        <button onclick="closeReleaseModal()" class="text-gray-400 hover:text-gray-500">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
      <p class="text-sm text-gray-500 mt-1">Release allocated funds to department</p>
    </div>
    
    <form method="POST" action="" id="releaseForm">
      <div class="p-6 space-y-4">
        <input type="hidden" name="allocation_id" id="releaseAllocationId">
        
        <div>
          <div class="flex justify-between text-sm mb-2">
            <span class="text-gray-600">Total Allocated:</span>
            <span class="font-medium" id="totalAllocated">₱0.00</span>
          </div>
          <div class="flex justify-between text-sm mb-2">
            <span class="text-gray-600">Already Released:</span>
            <span class="font-medium" id="alreadyReleased">₱0.00</span>
          </div>
          <div class="flex justify-between text-sm mb-4">
            <span class="text-gray-600">Available for Release:</span>
            <span class="font-medium text-success" id="availableRelease">₱0.00</span>
          </div>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Release Amount (₱) *</label>
          <input type="number" name="release_amount" required step="0.01" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent" placeholder="0.00" onchange="checkReleaseLimit()">
          <p class="text-xs text-gray-500 mt-1">Enter the amount to release</p>
        </div>
        
        <div id="releaseWarning" class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg hidden">
          <div class="flex items-start gap-2">
            <svg class="w-5 h-5 text-yellow-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
            <div>
              <p class="text-sm font-medium text-yellow-800" id="releaseWarningText"></p>
            </div>
          </div>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Release Notes</label>
          <textarea name="release_notes" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent" placeholder="Add notes about this release..."></textarea>
          <p class="text-xs text-gray-500 mt-1">Describe the purpose or conditions of this release</p>
        </div>
      </div>
      
      <div class="p-6 border-t border-gray-200 bg-gray-50">
        <div class="flex items-center justify-between">
          <button type="button" onclick="closeReleaseModal()" class="px-5 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-smooth">
            Cancel
          </button>
          <button type="submit" name="release_funds" value="1" class="px-5 py-2.5 bg-success hover:bg-green-700 text-white font-medium rounded-lg transition-smooth">
            Release Funds
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Adjust Allocation Modal -->
<div id="adjustModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 hidden">
  <div class="bg-white rounded-lg shadow-xl w-full max-w-md max-h-[90vh] overflow-hidden">
    <div class="p-6 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900">Adjust Allocation</h3>
        <button onclick="closeAdjustModal()" class="text-gray-400 hover:text-gray-500">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
      <p class="text-sm text-gray-500 mt-1">Modify allocation amount</p>
    </div>
    
    <form method="POST" action="" id="adjustForm">
      <div class="p-6 space-y-4">
        <input type="hidden" name="allocation_id" id="adjustAllocationId">
        
        <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
            <p class="text-sm font-medium text-yellow-800">Adjust allocation amount carefully.</p>
          </div>
          <p class="text-xs text-yellow-700 mt-1">Changes will affect department's allocated balance.</p>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Current Amount</label>
          <input type="text" id="currentAmount" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-gray-50" readonly>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">New Amount (₱) *</label>
          <input type="number" name="new_amount" required step="0.01" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent" placeholder="0.00">
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Adjustment Reason *</label>
          <textarea name="reason" required rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent" placeholder="Explain why this adjustment is needed..."></textarea>
          <p class="text-xs text-gray-500 mt-1">Required for audit purposes</p>
        </div>
      </div>
      
      <div class="p-6 border-t border-gray-200 bg-gray-50">
        <div class="flex items-center justify-between">
          <button type="button" onclick="closeAdjustModal()" class="px-5 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-smooth">
            Cancel
          </button>
          <button type="submit" name="adjust_allocation" value="1" class="px-5 py-2.5 bg-warning hover:bg-amber-700 text-white font-medium rounded-lg transition-smooth">
            Adjust Allocation
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
    
    // Add animation to allocation cards
    document.querySelectorAll('.allocation-card').forEach((card, index) => {
      card.style.animationDelay = `${index * 0.05}s`;
      card.classList.add('animate-slide-up');
    });
  });

  // Update budget info when budget is selected
  function updateBudgetInfo(budgetId) {
    const budgetInfo = document.getElementById('budgetInfo');
    const select = document.querySelector('select[name="budget_id"]');
    const selectedOption = select.options[select.selectedIndex];
    
    if (budgetId && selectedOption.dataset.amount) {
      const amount = parseFloat(selectedOption.dataset.amount) || 0;
      const department = selectedOption.dataset.department || 'N/A';
      
      document.getElementById('budgetAmount').textContent = '₱' + amount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
      document.getElementById('budgetDepartment').textContent = department;
      
      budgetInfo.classList.remove('hidden');
    } else {
      budgetInfo.classList.add('hidden');
    }
    
    checkAllocationLimit();
  }

  // Update department info when department is selected
  function updateDepartmentInfo(deptId) {
    const deptInfo = document.getElementById('deptInfo');
    const select = document.querySelector('select[name="department_id"]');
    const selectedOption = select.options[select.selectedIndex];
    
    if (deptId && selectedOption.dataset.annual) {
      const annual = parseFloat(selectedOption.dataset.annual) || 0;
      const allocated = parseFloat(selectedOption.dataset.allocated) || 0;
      
      document.getElementById('deptAnnual').textContent = '₱' + annual.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
      document.getElementById('deptAllocated').textContent = '₱' + allocated.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
      
      deptInfo.classList.remove('hidden');
    } else {
      deptInfo.classList.add('hidden');
    }
    
    checkAllocationLimit();
  }

  // Check allocation limits
  function checkAllocationLimit() {
    const budgetSelect = document.querySelector('select[name="budget_id"]');
    const deptSelect = document.querySelector('select[name="department_id"]');
    const amountInput = document.querySelector('input[name="amount"]');
    const warningDiv = document.getElementById('allocationWarning');
    const warningText = document.getElementById('warningText');
    
    if (!budgetSelect.value || !deptSelect.value || !amountInput.value) {
      warningDiv.classList.add('hidden');
      return;
    }
    
    const budgetOption = budgetSelect.options[budgetSelect.selectedIndex];
    const deptOption = deptSelect.options[deptSelect.selectedIndex];
    const budgetAmount = parseFloat(budgetOption.dataset.amount) || 0;
    const deptAnnual = parseFloat(deptOption.dataset.annual) || 0;
    const deptAllocated = parseFloat(deptOption.dataset.allocated) || 0;
    const amount = parseFloat(amountInput.value) || 0;
    
    let warnings = [];
    
    if (amount > budgetAmount) {
      warnings.push(`Amount exceeds budget limit (₱${budgetAmount.toFixed(2)})`);
    }
    
    const newTotalAllocated = deptAllocated + amount;
    if (newTotalAllocated > deptAnnual) {
      warnings.push(`Department allocation would exceed annual budget`);
    }
    
    if (warnings.length > 0) {
      warningText.textContent = warnings.join('. ') + '.';
      warningDiv.classList.remove('hidden');
    } else {
      warningDiv.classList.add('hidden');
    }
  }

  // Allocate from specific budget
  function allocateFromBudget(budgetId) {
    const budgetSelect = document.querySelector('select[name="budget_id"]');
    budgetSelect.value = budgetId;
    updateBudgetInfo(budgetId);
    
    // Scroll to allocation form
    document.querySelector('form').scrollIntoView({ behavior: 'smooth' });
  }

  // Modal Functions for Release
  function openReleaseModal(allocationId, totalAmount, releasedAmount) {
    document.getElementById('releaseAllocationId').value = allocationId;
    document.getElementById('totalAllocated').textContent = '₱' + totalAmount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    document.getElementById('alreadyReleased').textContent = '₱' + releasedAmount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    
    const available = totalAmount - releasedAmount;
    document.getElementById('availableRelease').textContent = '₱' + available.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    
    document.getElementById('releaseModal').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
  }

  function closeReleaseModal() {
    document.getElementById('releaseModal').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
  }

  function checkReleaseLimit() {
    const releaseInput = document.querySelector('#releaseForm input[name="release_amount"]');
    const totalAllocated = parseFloat(document.getElementById('totalAllocated').textContent.replace(/[^0-9.-]+/g, ""));
    const alreadyReleased = parseFloat(document.getElementById('alreadyReleased').textContent.replace(/[^0-9.-]+/g, ""));
    const releaseAmount = parseFloat(releaseInput.value) || 0;
    const warningDiv = document.getElementById('releaseWarning');
    const warningText = document.getElementById('releaseWarningText');
    
    const available = totalAllocated - alreadyReleased;
    
    if (releaseAmount > available) {
      warningText.textContent = `Cannot release more than ₱${available.toFixed(2)}`;
      warningDiv.classList.remove('hidden');
    } else {
      warningDiv.classList.add('hidden');
    }
  }

  // Modal Functions for Adjustment
  function openAdjustModal(allocationId, currentAmount) {
    document.getElementById('adjustAllocationId').value = allocationId;
    document.getElementById('currentAmount').value = '₱' + currentAmount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    
    document.getElementById('adjustModal').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
  }

  function closeAdjustModal() {
    document.getElementById('adjustModal').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
  }

  // View Allocation Details
  function viewAllocationDetails(allocationId) {
    showLoading();
    setTimeout(() => {
      hideLoading();
      alert('Viewing allocation details for ID: ' + allocationId + '\n\nThis would show complete allocation information including releases, adjustments, and audit trail.');
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
  document.querySelector('form')?.addEventListener('submit', function(e) {
    if (e.submitter?.name === 'allocate_funds') {
      const amount = parseFloat(document.querySelector('input[name="amount"]').value);
      if (amount <= 0) {
        e.preventDefault();
        alert('Please enter a valid allocation amount greater than 0.');
        return false;
      }
      
      const budgetSelect = document.querySelector('select[name="budget_id"]');
      const deptSelect = document.querySelector('select[name="department_id"]');
      const typeSelect = document.querySelector('select[name="allocation_type"]');
      
      if (!budgetSelect.value) {
        e.preventDefault();
        alert('Please select a budget.');
        return false;
      }
      
      if (!deptSelect.value) {
        e.preventDefault();
        alert('Please select a department.');
        return false;
      }
      
      if (!typeSelect.value) {
        e.preventDefault();
        alert('Please select an allocation type.');
        return false;
      }
    }
    
    showLoading();
    return true;
  });

  document.getElementById('releaseForm')?.addEventListener('submit', function(e) {
    const amount = parseFloat(document.querySelector('#releaseForm input[name="release_amount"]').value);
    if (amount <= 0) {
      e.preventDefault();
      alert('Please enter a valid release amount greater than 0.');
      return false;
    }
    showLoading();
    return true;
  });

  document.getElementById('adjustForm')?.addEventListener('submit', function(e) {
    const amount = parseFloat(document.querySelector('#adjustForm input[name="new_amount"]').value);
    if (amount <= 0) {
      e.preventDefault();
      alert('Please enter a valid amount greater than 0.');
      return false;
    }
    
    const reason = document.querySelector('#adjustForm textarea[name="reason"]');
    if (!reason.value.trim()) {
      e.preventDefault();
      alert('Please provide an adjustment reason.');
      return false;
    }
    showLoading();
    return true;
  });

  // Close modals on ESC key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      closeReleaseModal();
      closeAdjustModal();
    }
  });
</script>

</body>
</html>