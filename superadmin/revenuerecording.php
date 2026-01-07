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
$revenueCategories = [
    'tuition' => 'Tuition Fees',
    'donation' => 'Donations',
    'grant' => 'Grants',
    'registration' => 'Registration Fees',
    'miscellaneous' => 'Miscellaneous Fees',
    'activity' => 'Activity Fees',
    'library' => 'Library Fees',
    'laboratory' => 'Laboratory Fees',
    'uniform' => 'Uniform Sales',
    'book' => 'Book Sales',
    'other' => 'Other Income'
];

$revenueTransactions = [];
$monthlySummary = [];
$categorySummary = [];

try {
    // Check if revenue_transactions table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'revenue_transactions'");
    if ($stmt->rowCount() > 0) {
        // Fetch revenue transactions
        $stmt = $pdo->query("
            SELECT rt.*, 
                   CONCAT(u.firstname, ' ', u.lastname) as recorded_by_name,
                   d.name as department_name
            FROM revenue_transactions rt
            LEFT JOIN users u ON rt.recorded_by = u.id
            LEFT JOIN departments d ON rt.department_id = d.id
            ORDER BY rt.transaction_date DESC, rt.created_at DESC
            LIMIT 50
        ");
        $revenueTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate monthly summary for current year
        $currentYear = date('Y');
        $stmt = $pdo->prepare("
            SELECT 
                MONTH(transaction_date) as month,
                SUM(amount) as total_amount,
                COUNT(*) as transaction_count
            FROM revenue_transactions
            WHERE YEAR(transaction_date) = ?
            GROUP BY MONTH(transaction_date)
            ORDER BY month
        ");
        $stmt->execute([$currentYear]);
        $monthlySummary = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate category summary
        $stmt = $pdo->query("
            SELECT 
                category,
                SUM(amount) as total_amount,
                COUNT(*) as transaction_count
            FROM revenue_transactions
            GROUP BY category
            ORDER BY total_amount DESC
        ");
        $categorySummary = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Fetch departments for filtering
    $stmt = $pdo->query("SELECT id, name, abbreviation FROM departments ORDER BY name");
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Error fetching data: " . $e->getMessage();
}

// Handle revenue recording actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Record new revenue
    if (isset($_POST['record_revenue'])) {
        try {
            $transactionData = [
                'transaction_date' => $_POST['transaction_date'],
                'category' => $_POST['category'],
                'description' => $_POST['description'],
                'amount' => $_POST['amount'],
                'payment_method' => $_POST['payment_method'],
                'reference_number' => $_POST['reference_number'] ?? '',
                'department_id' => $_POST['department_id'] ?? null,
                'payer_name' => $_POST['payer_name'] ?? '',
                'payer_type' => $_POST['payer_type'] ?? '',
                'remarks' => $_POST['remarks'] ?? '',
                'recorded_by' => $_SESSION['user_id']
            ];
            
            // Check if revenue_transactions table exists
            $stmt = $pdo->query("SHOW TABLES LIKE 'revenue_transactions'");
            if ($stmt->rowCount() == 0) {
                // Create revenue_transactions table
                $pdo->query("
                    CREATE TABLE revenue_transactions (
                        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                        transaction_date DATE NOT NULL,
                        category VARCHAR(50) NOT NULL,
                        description VARCHAR(255) NOT NULL,
                        amount DECIMAL(15,2) NOT NULL,
                        payment_method VARCHAR(50) NOT NULL,
                        reference_number VARCHAR(100),
                        department_id INT UNSIGNED DEFAULT NULL,
                        payer_name VARCHAR(150),
                        payer_type VARCHAR(50),
                        remarks TEXT,
                        recorded_by INT UNSIGNED NOT NULL,
                        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        status VARCHAR(20) DEFAULT 'recorded',
                        PRIMARY KEY (id),
                        KEY idx_transaction_date (transaction_date),
                        KEY idx_category (category),
                        KEY idx_department (department_id),
                        KEY idx_payment_method (payment_method)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
                ");
            }
            
            // Insert revenue transaction
            $sql = "INSERT INTO revenue_transactions (transaction_date, category, description, amount, payment_method, reference_number, department_id, payer_name, payer_type, remarks, recorded_by) 
                    VALUES (:transaction_date, :category, :description, :amount, :payment_method, :reference_number, :department_id, :payer_name, :payer_type, :remarks, :recorded_by)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($transactionData);
            
            $_SESSION['success'] = "Revenue recorded successfully!";
            header("Location: revenuerecording.php");
            exit();
            
        } catch (Exception $e) {
            $_SESSION['error'] = "Error recording revenue: " . $e->getMessage();
        }
    }
    
    // Update revenue transaction
    if (isset($_POST['update_revenue'])) {
        try {
            $transactionId = $_POST['transaction_id'];
            $transactionData = [
                'transaction_date' => $_POST['transaction_date'],
                'category' => $_POST['category'],
                'description' => $_POST['description'],
                'amount' => $_POST['amount'],
                'payment_method' => $_POST['payment_method'],
                'reference_number' => $_POST['reference_number'] ?? '',
                'department_id' => $_POST['department_id'] ?? null,
                'payer_name' => $_POST['payer_name'] ?? '',
                'payer_type' => $_POST['payer_type'] ?? '',
                'remarks' => $_POST['remarks'] ?? ''
            ];
            
            // Build update query
            $updateFields = [];
            foreach ($transactionData as $field => $value) {
                $updateFields[] = "$field = :$field";
            }
            $updateFields[] = "updated_at = NOW()";
            
            $transactionData['id'] = $transactionId;
            
            $sql = "UPDATE revenue_transactions SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($transactionData);
            
            $_SESSION['success'] = "Revenue transaction updated successfully!";
            header("Location: revenuerecording.php");
            exit();
            
        } catch (Exception $e) {
            $_SESSION['error'] = "Error updating revenue: " . $e->getMessage();
        }
    }
    
    // Delete revenue transaction
    if (isset($_POST['delete_revenue'])) {
        try {
            $transactionId = $_POST['transaction_id'];
            
            $stmt = $pdo->prepare("DELETE FROM revenue_transactions WHERE id = ?");
            $stmt->execute([$transactionId]);
            
            $_SESSION['success'] = "Revenue transaction deleted successfully!";
            header("Location: revenuerecording.php");
            exit();
            
        } catch (Exception $e) {
            $_SESSION['error'] = "Error deleting revenue: " . $e->getMessage();
        }
    }
    
    // Import revenue transactions (CSV)
    if (isset($_POST['import_revenue']) && isset($_FILES['csv_file'])) {
        try {
            $file = $_FILES['csv_file'];
            
            if ($file['error'] === UPLOAD_ERR_OK) {
                $csvFile = fopen($file['tmp_name'], 'r');
                $importCount = 0;
                $skipHeader = true;
                
                // Check if table exists
                $stmt = $pdo->query("SHOW TABLES LIKE 'revenue_transactions'");
                if ($stmt->rowCount() == 0) {
                    // Create table if it doesn't exist
                    $pdo->query("
                        CREATE TABLE revenue_transactions (
                            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                            transaction_date DATE NOT NULL,
                            category VARCHAR(50) NOT NULL,
                            description VARCHAR(255) NOT NULL,
                            amount DECIMAL(15,2) NOT NULL,
                            payment_method VARCHAR(50) NOT NULL,
                            reference_number VARCHAR(100),
                            department_id INT UNSIGNED DEFAULT NULL,
                            payer_name VARCHAR(150),
                            payer_type VARCHAR(50),
                            remarks TEXT,
                            recorded_by INT UNSIGNED NOT NULL,
                            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            status VARCHAR(20) DEFAULT 'recorded',
                            PRIMARY KEY (id)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
                    ");
                }
                
                while (($row = fgetcsv($csvFile)) !== FALSE) {
                    if ($skipHeader) {
                        $skipHeader = false;
                        continue;
                    }
                    
                    // Validate CSV row (assuming columns: date, category, description, amount, payment_method, reference, department, payer_name, payer_type, remarks)
                    if (count($row) >= 5) {
                        $transactionData = [
                            'transaction_date' => $row[0],
                            'category' => $row[1],
                            'description' => $row[2],
                            'amount' => $row[3],
                            'payment_method' => $row[4],
                            'reference_number' => $row[5] ?? '',
                            'department_id' => $row[6] ?? null,
                            'payer_name' => $row[7] ?? '',
                            'payer_type' => $row[8] ?? '',
                            'remarks' => $row[9] ?? '',
                            'recorded_by' => $_SESSION['user_id']
                        ];
                        
                        $sql = "INSERT INTO revenue_transactions (transaction_date, category, description, amount, payment_method, reference_number, department_id, payer_name, payer_type, remarks, recorded_by) 
                                VALUES (:transaction_date, :category, :description, :amount, :payment_method, :reference_number, :department_id, :payer_name, :payer_type, :remarks, :recorded_by)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($transactionData);
                        
                        $importCount++;
                    }
                }
                
                fclose($csvFile);
                
                $_SESSION['success'] = "Successfully imported $importCount revenue transactions!";
            } else {
                $_SESSION['error'] = "Error uploading file. Please try again.";
            }
            
            header("Location: revenuerecording.php");
            exit();
            
        } catch (Exception $e) {
            $_SESSION['error'] = "Error importing revenue: " . $e->getMessage();
        }
    }
}

// Calculate summary statistics
$totalRevenue = 0;
$todayRevenue = 0;
$monthRevenue = 0;
$yearRevenue = 0;

if (!empty($revenueTransactions)) {
    $totalRevenue = array_sum(array_column($revenueTransactions, 'amount'));
    
    $today = date('Y-m-d');
    $currentMonth = date('Y-m');
    $currentYear = date('Y');
    
    foreach ($revenueTransactions as $transaction) {
        $transactionDate = $transaction['transaction_date'];
        
        if ($transactionDate == $today) {
            $todayRevenue += $transaction['amount'];
        }
        
        if (substr($transactionDate, 0, 7) == $currentMonth) {
            $monthRevenue += $transaction['amount'];
        }
        
        if (substr($transactionDate, 0, 4) == $currentYear) {
            $yearRevenue += $transaction['amount'];
        }
    }
}

// Session variables for display
$firstname = $_SESSION['firstname'] ?? '';
$middlename = $_SESSION['middlename'] ?? '';
$lastname = $_SESSION['lastname'] ?? '';
$role = $_SESSION['role'];

// Current tab
$currentTab = $_GET['tab'] ?? 'record';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Revenue Recording | Financial Management System</title>
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
    .status-recorded {
      background-color: #DCFCE7;
      color: #166534;
    }
    
    .status-pending {
      background-color: #FEF3C7;
      color: #92400E;
    }
    
    .status-verified {
      background-color: #DBEAFE;
      color: #1E40AF;
    }
    
    /* Category colors */
    .category-tuition { background-color: #3B82F6; color: white; }
    .category-donation { background-color: #10B981; color: white; }
    .category-grant { background-color: #8B5CF6; color: white; }
    .category-registration { background-color: #F59E0B; color: white; }
    .category-miscellaneous { background-color: #6B7280; color: white; }
    .category-activity { background-color: #EC4899; color: white; }
    .category-library { background-color: #14B8A6; color: white; }
    .category-laboratory { background-color: #F97316; color: white; }
    .category-uniform { background-color: #84CC16; color: white; }
    .category-book { background-color: #06B6D4; color: white; }
    .category-other { background-color: #64748B; color: white; }
    
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
    
    /* Chart container */
    .chart-container {
      position: relative;
      height: 300px;
      width: 100%;
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
          <h1 class="text-2xl font-bold text-gray-900">Revenue Recording</h1>
          <p class="text-gray-600 mt-1">Record and manage all sources of income (tuition, donations, grants, fees, etc.)</p>
        </div>
        <div class="flex items-center gap-3">
          <span class="text-sm text-gray-600">
            Total Revenue: <span class="font-medium text-success">₱<?php echo number_format($totalRevenue, 2); ?></span>
          </span>
        </div>
      </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="border-b border-gray-200 mb-6">
      <nav class="flex space-x-8">
        <a href="?tab=record" class="py-3 px-1 font-medium text-sm border-b-2 <?php echo $currentTab == 'record' ? 'border-accent text-accent tab-active' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
          Record Revenue
        </a>
        <a href="?tab=transactions" class="py-3 px-1 font-medium text-sm border-b-2 <?php echo $currentTab == 'transactions' ? 'border-accent text-accent tab-active' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
          Transactions
          <span class="ml-2 bg-blue-100 text-blue-800 text-xs px-2 py-0.5 rounded-full"><?php echo count($revenueTransactions); ?></span>
        </a>
        <a href="?tab=reports" class="py-3 px-1 font-medium text-sm border-b-2 <?php echo $currentTab == 'reports' ? 'border-accent text-accent tab-active' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
          Reports
        </a>
        <a href="?tab=import" class="py-3 px-1 font-medium text-sm border-b-2 <?php echo $currentTab == 'import' ? 'border-accent text-accent tab-active' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
          Import
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
    <?php if ($currentTab == 'record'): ?>
    
      <!-- Record Revenue Section -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Revenue Recording Form -->
        <div class="bg-white rounded-lg shadow lg:col-span-2">
          <div class="p-5 border-b border-gray-200">
            <h3 class="font-semibold text-gray-900 text-base">Record New Revenue</h3>
            <p class="text-sm text-gray-500">Enter details for income received</p>
          </div>
          
          <form method="POST" action="" class="p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Transaction Date *</label>
                <input type="date" name="transaction_date" required value="<?php echo date('Y-m-d'); ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                <select name="category" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
                  <option value="">Select category...</option>
                  <?php foreach ($revenueCategories as $value => $label): ?>
                  <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
              <input type="text" name="description" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent" placeholder="e.g., Tuition fee for John Doe, Q1 2024">
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Amount (₱) *</label>
                <input type="number" name="amount" required step="0.01" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent" placeholder="0.00">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method *</label>
                <select name="payment_method" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
                  <option value="">Select method...</option>
                  <option value="cash">Cash</option>
                  <option value="check">Check</option>
                  <option value="bank_transfer">Bank Transfer</option>
                  <option value="online_payment">Online Payment</option>
                  <option value="credit_card">Credit Card</option>
                  <option value="debit_card">Debit Card</option>
                  <option value="other">Other</option>
                </select>
              </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Reference Number</label>
                <input type="text" name="reference_number" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent" placeholder="e.g., Check #1234, Transaction ID">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                <select name="department_id" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
                  <option value="">Select department (if applicable)</option>
                  <?php foreach ($departments as $dept): ?>
                  <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Payer Name</label>
                <input type="text" name="payer_name" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent" placeholder="Name of person/organization who paid">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Payer Type</label>
                <select name="payer_type" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
                  <option value="">Select type...</option>
                  <option value="student">Student</option>
                  <option value="parent">Parent</option>
                  <option value="alumni">Alumni</option>
                  <option value="corporation">Corporation</option>
                  <option value="foundation">Foundation</option>
                  <option value="government">Government</option>
                  <option value="individual">Individual</option>
                  <option value="other">Other</option>
                </select>
              </div>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Remarks</label>
              <textarea name="remarks" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent" placeholder="Additional notes or details..."></textarea>
            </div>
            
            <div class="pt-4 border-t border-gray-200">
              <button type="submit" name="record_revenue" value="1" class="w-full bg-success hover:bg-green-700 text-white font-medium py-3 px-4 rounded-lg transition-smooth">
                Record Revenue
              </button>
            </div>
          </form>
        </div>
        
        <!-- Quick Stats & Recent Transactions -->
        <div class="space-y-6">
          <!-- Revenue Summary -->
          <div class="bg-white rounded-lg shadow p-5">
            <h3 class="font-semibold text-gray-900 text-base mb-4">Revenue Summary</h3>
            <div class="space-y-4">
              <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                <div>
                  <p class="text-sm text-gray-500">Today</p>
                  <p class="text-lg font-bold text-gray-900">₱<?php echo number_format($todayRevenue, 2); ?></p>
                </div>
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                  <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                </div>
              </div>
              
              <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                <div>
                  <p class="text-sm text-gray-500">This Month</p>
                  <p class="text-lg font-bold text-gray-900">₱<?php echo number_format($monthRevenue, 2); ?></p>
                </div>
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                  <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                  </svg>
                </div>
              </div>
              
              <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                <div>
                  <p class="text-sm text-gray-500">This Year</p>
                  <p class="text-lg font-bold text-gray-900">₱<?php echo number_format($yearRevenue, 2); ?></p>
                </div>
                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                  <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                  </svg>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Recent Transactions -->
          <div class="bg-white rounded-lg shadow">
            <div class="p-5 border-b border-gray-200">
              <h3 class="font-semibold text-gray-900 text-base">Recent Transactions</h3>
              <p class="text-sm text-gray-500">Latest 5 revenue entries</p>
            </div>
            <div class="p-4">
              <?php if (!empty($revenueTransactions)): ?>
                <?php $count = 0; ?>
                <?php foreach (array_slice($revenueTransactions, 0, 5) as $transaction): ?>
                  <div class="flex items-center justify-between py-3 <?php echo $count < 4 ? 'border-b border-gray-100' : ''; ?>">
                    <div>
                      <p class="font-medium text-gray-900 text-sm"><?php echo htmlspecialchars($transaction['description']); ?></p>
                      <p class="text-xs text-gray-500"><?php echo date('M d, Y', strtotime($transaction['transaction_date'])); ?></p>
                    </div>
                    <div class="text-right">
                      <p class="font-bold text-green-600">₱<?php echo number_format($transaction['amount'], 2); ?></p>
                      <span class="text-xs px-2 py-1 rounded-full <?php echo 'category-' . $transaction['category']; ?>">
                        <?php echo $revenueCategories[$transaction['category']] ?? ucfirst($transaction['category']); ?>
                      </span>
                    </div>
                  </div>
                  <?php $count++; ?>
                <?php endforeach; ?>
              <?php else: ?>
                <p class="text-gray-500 text-center py-4 text-sm">No recent transactions</p>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

    <?php elseif ($currentTab == 'transactions'): ?>
    
      <!-- Transactions Section -->
      <div class="bg-white rounded-lg shadow">
        <div class="p-5 border-b border-gray-200">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
              <h3 class="font-semibold text-gray-900 text-base">Revenue Transactions</h3>
              <p class="text-sm text-gray-500">View, edit, or delete recorded revenue entries</p>
            </div>
            <div class="flex items-center gap-3">
              <div class="relative">
                <input type="text" id="searchTransactions" placeholder="Search transactions..." class="border border-gray-300 rounded-lg px-4 py-2.5 pl-10 text-sm w-64 focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
                <svg class="absolute left-3 top-3 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
              </div>
              <button onclick="toggleFilters()" class="border border-gray-300 rounded-lg px-4 py-2.5 text-sm hover:bg-gray-50 transition-smooth">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                Filters
              </button>
            </div>
          </div>
          
          <!-- Filters (Hidden by default) -->
          <div id="filterPanel" class="mt-4 p-4 bg-gray-50 rounded-lg hidden">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                <div class="flex gap-2">
                  <input type="date" id="dateFrom" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm">
                  <span class="self-center text-gray-500">to</span>
                  <input type="date" id="dateTo" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                <select id="categoryFilter" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                  <option value="">All Categories</option>
                  <?php foreach ($revenueCategories as $value => $label): ?>
                  <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                <select id="paymentFilter" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                  <option value="">All Methods</option>
                  <option value="cash">Cash</option>
                  <option value="check">Check</option>
                  <option value="bank_transfer">Bank Transfer</option>
                  <option value="online_payment">Online Payment</option>
                  <option value="credit_card">Credit Card</option>
                  <option value="debit_card">Debit Card</option>
                  <option value="other">Other</option>
                </select>
              </div>
            </div>
            <div class="mt-4 flex justify-end gap-2">
              <button onclick="clearFilters()" class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">Clear</button>
              <button onclick="applyFilters()" class="px-4 py-2 text-sm bg-accent text-white rounded-lg hover:bg-blue-700">Apply Filters</button>
            </div>
          </div>
        </div>
        
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-200">
                <th class="px-6 py-3">Date</th>
                <th class="px-6 py-3">Description</th>
                <th class="px-6 py-3">Category</th>
                <th class="px-6 py-3">Payment Method</th>
                <th class="px-6 py-3">Amount</th>
                <th class="px-6 py-3">Department</th>
                <th class="px-6 py-3">Recorded By</th>
                <th class="px-6 py-3">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <?php if (!empty($revenueTransactions)): ?>
                <?php foreach ($revenueTransactions as $transaction): ?>
                <tr class="hover:bg-gray-50 transition-smooth">
                  <td class="px-6 py-4 text-sm text-gray-900">
                    <?php echo date('M d, Y', strtotime($transaction['transaction_date'])); ?>
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-900">
                    <div>
                      <p class="font-medium"><?php echo htmlspecialchars($transaction['description']); ?></p>
                      <?php if (!empty($transaction['payer_name'])): ?>
                      <p class="text-xs text-gray-500">Paid by: <?php echo htmlspecialchars($transaction['payer_name']); ?></p>
                      <?php endif; ?>
                    </div>
                  </td>
                  <td class="px-6 py-4">
                    <span class="text-xs px-3 py-1 rounded-full <?php echo 'category-' . $transaction['category']; ?>">
                      <?php echo $revenueCategories[$transaction['category']] ?? ucfirst($transaction['category']); ?>
                    </span>
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-900">
                    <?php echo ucfirst(str_replace('_', ' ', $transaction['payment_method'])); ?>
                  </td>
                  <td class="px-6 py-4 text-sm font-bold text-green-600">
                    ₱<?php echo number_format($transaction['amount'], 2); ?>
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-900">
                    <?php echo $transaction['department_name'] ?? 'N/A'; ?>
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-900">
                    <?php echo $transaction['recorded_by_name'] ?? 'System'; ?>
                  </td>
                  <td class="px-6 py-4 text-sm">
                    <div class="flex items-center gap-2">
                      <button onclick="editTransaction(<?php echo $transaction['id']; ?>)" class="text-info hover:text-blue-700 transition-smooth" title="Edit">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                      </button>
                      <button onclick="viewTransaction(<?php echo $transaction['id']; ?>)" class="text-gray-600 hover:text-gray-900 transition-smooth" title="View Details">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                      </button>
                      <form method="POST" action="" style="display: inline;">
                        <input type="hidden" name="transaction_id" value="<?php echo $transaction['id']; ?>">
                        <button type="submit" name="delete_revenue" value="1" onclick="return confirm('Are you sure you want to delete this revenue transaction?');" class="text-danger hover:text-red-700 transition-smooth" title="Delete">
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                          </svg>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                    No revenue transactions found. Start by recording some revenue!
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        
        <?php if (!empty($revenueTransactions)): ?>
        <div class="px-6 py-4 border-t border-gray-200">
          <div class="flex items-center justify-between">
            <p class="text-sm text-gray-600">
              Showing <span class="font-medium"><?php echo min(50, count($revenueTransactions)); ?></span> of <span class="font-medium"><?php echo count($revenueTransactions); ?></span> transactions
            </p>
            <button class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 transition-smooth">
              Load More
            </button>
          </div>
        </div>
        <?php endif; ?>
      </div>

    <?php elseif ($currentTab == 'reports'): ?>
    
      <!-- Reports Section -->
      <div class="space-y-6">
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-500">Total Revenue</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">₱<?php echo number_format($totalRevenue, 2); ?></p>
              </div>
              <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
            </div>
          </div>
          
          <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-500">Transactions Count</p>
                <p class="text-2xl font-bold text-gray-900 mt-1"><?php echo count($revenueTransactions); ?></p>
              </div>
              <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
              </div>
            </div>
          </div>
          
          <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-500">Average Transaction</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">
                  ₱<?php echo count($revenueTransactions) > 0 ? number_format($totalRevenue / count($revenueTransactions), 2) : '0.00'; ?>
                </p>
              </div>
              <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
              </div>
            </div>
          </div>
          
          <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-500">Categories</p>
                <p class="text-2xl font-bold text-gray-900 mt-1"><?php echo count($revenueCategories); ?></p>
              </div>
              <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Monthly Revenue Chart -->
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold text-gray-900 text-base mb-4">Monthly Revenue (<?php echo date('Y'); ?>)</h3>
            <div class="chart-container">
              <canvas id="monthlyRevenueChart"></canvas>
            </div>
          </div>
          
          <!-- Category Distribution Chart -->
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold text-gray-900 text-base mb-4">Revenue by Category</h3>
            <div class="chart-container">
              <canvas id="categoryDistributionChart"></canvas>
            </div>
          </div>
        </div>
        
        <!-- Category Summary Table -->
        <div class="bg-white rounded-lg shadow">
          <div class="p-5 border-b border-gray-200">
            <h3 class="font-semibold text-gray-900 text-base">Category Breakdown</h3>
            <p class="text-sm text-gray-500">Revenue distribution across categories</p>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-200">
                  <th class="px-6 py-3">Category</th>
                  <th class="px-6 py-3">Transactions</th>
                  <th class="px-6 py-3">Total Amount</th>
                  <th class="px-6 py-3">Percentage</th>
                  <th class="px-6 py-3">Average</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <?php if (!empty($categorySummary)): ?>
                  <?php foreach ($categorySummary as $category): ?>
                  <tr class="hover:bg-gray-50 transition-smooth">
                    <td class="px-6 py-4">
                      <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full <?php echo 'category-' . $category['category']; ?>"></div>
                        <span class="text-sm font-medium text-gray-900">
                          <?php echo $revenueCategories[$category['category']] ?? ucfirst($category['category']); ?>
                        </span>
                      </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                      <?php echo $category['transaction_count']; ?>
                    </td>
                    <td class="px-6 py-4 text-sm font-bold text-green-600">
                      ₱<?php echo number_format($category['total_amount'], 2); ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                      <?php echo $totalRevenue > 0 ? number_format(($category['total_amount'] / $totalRevenue) * 100, 1) : '0'; ?>%
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                      ₱<?php echo number_format($category['total_amount'] / $category['transaction_count'], 2); ?>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                      No category data available
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
        
        <!-- Export Options -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="font-semibold text-gray-900 text-base mb-4">Export Reports</h3>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <button onclick="exportPDF()" class="p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-smooth text-left">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                  <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                  </svg>
                </div>
                <div>
                  <p class="font-medium text-gray-900">Export as PDF</p>
                  <p class="text-sm text-gray-500">Generate PDF report</p>
                </div>
              </div>
            </button>
            
            <button onclick="exportExcel()" class="p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-smooth text-left">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                  <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                  </svg>
                </div>
                <div>
                  <p class="font-medium text-gray-900">Export as Excel</p>
                  <p class="text-sm text-gray-500">Download Excel spreadsheet</p>
                </div>
              </div>
            </button>
            
            <button onclick="printReport()" class="p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-smooth text-left">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                  <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                  </svg>
                </div>
                <div>
                  <p class="font-medium text-gray-900">Print Report</p>
                  <p class="text-sm text-gray-500">Print summary report</p>
                </div>
              </div>
            </button>
          </div>
        </div>
      </div>

    <?php elseif ($currentTab == 'import'): ?>
    
      <!-- Import Section -->
      <div class="bg-white rounded-lg shadow">
        <div class="p-5 border-b border-gray-200">
          <h3 class="font-semibold text-gray-900 text-base">Import Revenue Transactions</h3>
          <p class="text-sm text-gray-500">Import revenue data from CSV files</p>
        </div>
        
        <div class="p-6">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Import Form -->
            <div>
              <form method="POST" action="" enctype="multipart/form-data" class="space-y-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">CSV File *</label>
                  <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-accent transition-smooth">
                    <input type="file" name="csv_file" accept=".csv" required class="hidden" id="csvFileInput" onchange="previewFileName(this)">
                    <label for="csvFileInput" class="cursor-pointer">
                      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                      </svg>
                      <p class="mt-2 text-sm text-gray-600">Click to upload CSV file</p>
                      <p class="mt-1 text-xs text-gray-500" id="fileNamePreview">No file selected</p>
                    </label>
                  </div>
                  <p class="mt-2 text-xs text-gray-500">Supported format: CSV (Comma Separated Values)</p>
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Import Options</label>
                  <div class="space-y-3">
                    <div class="flex items-center">
                      <input type="checkbox" id="skipFirstRow" name="skip_header" value="1" checked class="h-4 w-4 text-accent focus:ring-accent border-gray-300 rounded">
                      <label for="skipFirstRow" class="ml-2 text-sm text-gray-700">Skip first row (header)</label>
                    </div>
                    <div class="flex items-center">
                      <input type="checkbox" id="updateExisting" name="update_existing" value="1" class="h-4 w-4 text-accent focus:ring-accent border-gray-300 rounded">
                      <label for="updateExisting" class="ml-2 text-sm text-gray-700">Update existing records</label>
                    </div>
                  </div>
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">CSV Format</label>
                  <p class="text-sm text-gray-600">Expected columns in order:</p>
                  <div class="mt-2 p-3 bg-gray-50 rounded-lg">
                    <code class="text-xs text-gray-700">
                      transaction_date, category, description, amount, payment_method, reference_number, department_id, payer_name, payer_type, remarks
                    </code>
                  </div>
                </div>
                
                <div class="pt-4">
                  <button type="submit" name="import_revenue" value="1" class="w-full bg-accent hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition-smooth">
                    Import Revenue Data
                  </button>
                </div>
              </form>
            </div>
            
            <!-- Instructions -->
            <div>
              <div class="bg-gray-50 rounded-lg p-6">
                <h4 class="font-semibold text-gray-900 text-base mb-4">Import Instructions</h4>
                
                <div class="space-y-4">
                  <div>
                    <h5 class="text-sm font-medium text-gray-900 mb-2">Required Columns:</h5>
                    <ul class="text-sm text-gray-600 space-y-1">
                      <li><span class="font-medium">transaction_date:</span> Date in YYYY-MM-DD format</li>
                      <li><span class="font-medium">category:</span> One of: tuition, donation, grant, etc.</li>
                      <li><span class="font-medium">description:</span> Transaction description</li>
                      <li><span class="font-medium">amount:</span> Decimal number (e.g., 1500.00)</li>
                      <li><span class="font-medium">payment_method:</span> cash, check, bank_transfer, etc.</li>
                    </ul>
                  </div>
                  
                  <div>
                    <h5 class="text-sm font-medium text-gray-900 mb-2">Optional Columns:</h5>
                    <ul class="text-sm text-gray-600 space-y-1">
                      <li><span class="font-medium">reference_number:</span> Check number or transaction ID</li>
                      <li><span class="font-medium">department_id:</span> Department ID number</li>
                      <li><span class="font-medium">payer_name:</span> Name of person/organization</li>
                      <li><span class="font-medium">payer_type:</span> student, parent, corporation, etc.</li>
                      <li><span class="font-medium">remarks:</span> Additional notes</li>
                    </ul>
                  </div>
                  
                  <div>
                    <h5 class="text-sm font-medium text-gray-900 mb-2">Sample CSV Data:</h5>
                    <div class="bg-white p-3 rounded border border-gray-200 overflow-x-auto">
                      <pre class="text-xs text-gray-700">
transaction_date,category,description,amount,payment_method,reference_number,department_id,payer_name,payer_type,remarks
2024-01-15,tuition,"Tuition fee - John Doe",15000.00,bank_transfer,TRX001,1,"John Doe",student,"Q1 payment"
2024-01-16,donation,"Alumni donation",5000.00,check,CHK1234,,,"Jane Smith",alumni,"Annual donation"
2024-01-17,registration,"New student registration",2000.00,cash,,2,"Parent Name",parent,"Registration fee"
                      </pre>
                    </div>
                  </div>
                  
                  <div class="mt-6">
                    <a href="javascript:void(0)" onclick="downloadTemplate()" class="text-sm text-accent hover:text-blue-700 font-medium flex items-center gap-1">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                      </svg>
                      Download CSV Template
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

    <?php endif; ?>
  </main>
</div>

<!-- Edit Modal (Hidden by default) -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
  <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
    <div class="p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Edit Revenue Transaction</h3>
      <form id="editForm" method="POST" action="" class="space-y-4">
        <input type="hidden" name="transaction_id" id="editTransactionId">
        <input type="hidden" name="update_revenue" value="1">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Transaction Date</label>
            <input type="date" name="transaction_date" id="editTransactionDate" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
            <select name="category" id="editCategory" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
              <?php foreach ($revenueCategories as $value => $label): ?>
              <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
          <input type="text" name="description" id="editDescription" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Amount</label>
            <input type="number" name="amount" id="editAmount" required step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
            <select name="payment_method" id="editPaymentMethod" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
              <option value="cash">Cash</option>
              <option value="check">Check</option>
              <option value="bank_transfer">Bank Transfer</option>
              <option value="online_payment">Online Payment</option>
              <option value="credit_card">Credit Card</option>
              <option value="debit_card">Debit Card</option>
              <option value="other">Other</option>
            </select>
          </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Reference Number</label>
            <input type="text" name="reference_number" id="editReferenceNumber" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
            <select name="department_id" id="editDepartmentId" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
              <option value="">Select department</option>
              <?php foreach ($departments as $dept): ?>
              <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Payer Name</label>
            <input type="text" name="payer_name" id="editPayerName" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Payer Type</label>
            <select name="payer_type" id="editPayerType" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
              <option value="">Select type</option>
              <option value="student">Student</option>
              <option value="parent">Parent</option>
              <option value="alumni">Alumni</option>
              <option value="corporation">Corporation</option>
              <option value="foundation">Foundation</option>
              <option value="government">Government</option>
              <option value="individual">Individual</option>
              <option value="other">Other</option>
            </select>
          </div>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Remarks</label>
          <textarea name="remarks" id="editRemarks" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
        </div>
        
        <div class="pt-4 border-t border-gray-200 flex justify-end gap-3">
          <button type="button" onclick="closeEditModal()" class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">
            Cancel
          </button>
          <button type="submit" class="px-4 py-2 text-sm bg-success text-white rounded-lg hover:bg-green-700">
            Update Revenue
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay">
  <div class="loading-spinner"></div>
</div>

<script>
// Filter functions
function toggleFilters() {
  const panel = document.getElementById('filterPanel');
  panel.classList.toggle('hidden');
}

function clearFilters() {
  document.getElementById('dateFrom').value = '';
  document.getElementById('dateTo').value = '';
  document.getElementById('categoryFilter').value = '';
  document.getElementById('paymentFilter').value = '';
}

function applyFilters() {
  const dateFrom = document.getElementById('dateFrom').value;
  const dateTo = document.getElementById('dateTo').value;
  const category = document.getElementById('categoryFilter').value;
  const paymentMethod = document.getElementById('paymentFilter').value;
  
  // In a real implementation, this would reload the page with filter parameters
  // For now, just show a notification
  alert('Filters applied. In a real application, this would filter the table data.');
}

// Import functions
function previewFileName(input) {
  const fileName = input.files[0]?.name || 'No file selected';
  document.getElementById('fileNamePreview').textContent = fileName;
}

function downloadTemplate() {
  // Create CSV template
  const csvContent = "transaction_date,category,description,amount,payment_method,reference_number,department_id,payer_name,payer_type,remarks\n" +
                    "2024-01-15,tuition,\"Tuition fee - John Doe\",15000.00,bank_transfer,TRX001,1,\"John Doe\",student,\"Q1 payment\"\n" +
                    "2024-01-16,donation,\"Alumni donation\",5000.00,check,CHK1234,,\"Jane Smith\",alumni,\"Annual donation\"\n" +
                    "2024-01-17,registration,\"New student registration\",2000.00,cash,,2,\"Parent Name\",parent,\"Registration fee\"";
  
  const blob = new Blob([csvContent], { type: 'text/csv' });
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'revenue_template.csv';
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  window.URL.revokeObjectURL(url);
}

// Modal functions
function editTransaction(transactionId) {
  // In a real application, you would fetch transaction data via AJAX
  // For now, we'll just show the modal
  document.getElementById('editModal').classList.remove('hidden');
  document.getElementById('editTransactionId').value = transactionId;
}

function viewTransaction(transactionId) {
  // In a real application, you would show transaction details
  alert('View transaction details for ID: ' + transactionId);
}

function closeEditModal() {
  document.getElementById('editModal').classList.add('hidden');
}

// Report functions
function exportPDF() {
  showLoading();
  setTimeout(() => {
    hideLoading();
    alert('PDF export started. In a real application, this would generate and download a PDF.');
  }, 1000);
}

function exportExcel() {
  showLoading();
  setTimeout(() => {
    hideLoading();
    alert('Excel export started. In a real application, this would generate and download an Excel file.');
  }, 1000);
}

function printReport() {
  window.print();
}

// Loading functions
function showLoading() {
  document.getElementById('loadingOverlay').style.display = 'flex';
}

function hideLoading() {
  document.getElementById('loadingOverlay').style.display = 'none';
}

// Chart initialization
document.addEventListener('DOMContentLoaded', function() {
  <?php if ($currentTab == 'reports'): ?>
    // Monthly Revenue Chart
    const monthlyCtx = document.getElementById('monthlyRevenueChart');
    if (monthlyCtx) {
      const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
      const monthlyData = new Array(12).fill(0);
      
      <?php if (!empty($monthlySummary)): ?>
        <?php foreach ($monthlySummary as $summary): ?>
          monthlyData[<?php echo $summary['month'] - 1; ?>] = <?php echo $summary['total_amount']; ?>;
        <?php endforeach; ?>
      <?php endif; ?>
      
      new Chart(monthlyCtx, {
        type: 'line',
        data: {
          labels: months,
          datasets: [{
            label: 'Revenue',
            data: monthlyData,
            borderColor: '#2563EB',
            backgroundColor: 'rgba(37, 99, 235, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                callback: function(value) {
                  return '₱' + value.toLocaleString();
                }
              }
            }
          }
        }
      });
    }
    
    // Category Distribution Chart
    const categoryCtx = document.getElementById('categoryDistributionChart');
    if (categoryCtx) {
      const categoryLabels = [];
      const categoryData = [];
      const categoryColors = [];
      
      <?php if (!empty($categorySummary)): ?>
        <?php foreach (array_slice($categorySummary, 0, 8) as $category): ?>
          categoryLabels.push('<?php echo $revenueCategories[$category['category']] ?? ucfirst($category['category']); ?>');
          categoryData.push(<?php echo $category['total_amount']; ?>);
          categoryColors.push(getCategoryColor('<?php echo $category['category']; ?>'));
        <?php endforeach; ?>
      <?php endif; ?>
      
      new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
          labels: categoryLabels,
          datasets: [{
            data: categoryData,
            backgroundColor: categoryColors,
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'right'
            }
          }
        }
      });
    }
    
    function getCategoryColor(category) {
      const colors = {
        'tuition': '#3B82F6',
        'donation': '#10B981',
        'grant': '#8B5CF6',
        'registration': '#F59E0B',
        'miscellaneous': '#6B7280',
        'activity': '#EC4899',
        'library': '#14B8A6',
        'laboratory': '#F97316',
        'uniform': '#84CC16',
        'book': '#06B6D4',
        'other': '#64748B'
      };
      return colors[category] || '#64748B';
    }
  <?php endif; ?>
  
  // Search functionality
  const searchInput = document.getElementById('searchTransactions');
  if (searchInput) {
    searchInput.addEventListener('input', function(e) {
      const searchTerm = e.target.value.toLowerCase();
      const rows = document.querySelectorAll('tbody tr');
      
      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
      });
    });
  }
  
  // Close modal on outside click
  const modal = document.getElementById('editModal');
  if (modal) {
    modal.addEventListener('click', function(e) {
      if (e.target === modal) {
        closeEditModal();
      }
    });
  }
});

// Prevent form submission with Enter key in search
document.addEventListener('keydown', function(e) {
  if (e.key === 'Enter' && e.target.id === 'searchTransactions') {
    e.preventDefault();
  }
});
</script>
</body>
</html>