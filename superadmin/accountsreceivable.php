<?php
session_start();
require_once '../config/config.php';

// ==================== AUTHENTICATION ====================
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// Check AR permissions
$allowed_roles = ['Super Administrator', 'Financial Director', 'Treasurer', 'Accounting Officer', 'Cashier'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT role, level FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        header("Location: ../login.php?error=unauthorized");
        exit();
    }
} catch (Exception $e) {
    header("Location: ../login.php?error=unauthorized");
    exit();
}


// ==================== HANDLE POST ACTIONS ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_invoice':
            handleCreateInvoice($pdo);
            break;
        case 'receive_payment':
            handleReceivePayment($pdo);
            break;
        case 'send_reminder':
            handleSendReminder($pdo);
            break;
        case 'update_invoice_status':
            handleUpdateInvoiceStatus($pdo);
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            exit();
    }
}

// ==================== MAIN ROUTING ====================
$page = $_GET['page'] ?? 'invoices';
$currentTab = $_GET['tab'] ?? 'overview';

// Session variables for display
$firstname = $_SESSION['firstname'] ?? '';
$lastname = $_SESSION['lastname'] ?? '';
$role = $_SESSION['role'] ?? '';
$user_id = $_SESSION['user_id'] ?? '';

// Render the page
renderPage($page, $pdo, $currentTab);

// ==================== HANDLER FUNCTIONS ====================
function handleCreateInvoice($pdo) {
    global $user_id;
    
    try {
        $pdo->beginTransaction();
        
        // Validate required fields
        $required = ['student_id', 'invoice_number', 'description', 'amount', 'due_date'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("$field is required");
            }
        }
        
        // Get student name
        $stmt = $pdo->prepare("SELECT first_name, last_name FROM students WHERE student_id = ?");
        $stmt->execute([$_POST['student_id']]);
        $student = $stmt->fetch();
        
        if (!$student) {
            throw new Exception("Student not found");
        }
        
        $customer_name = $student['first_name'] . ' ' . $student['last_name'];
        
        // Insert invoice
        $stmt = $pdo->prepare("
            INSERT INTO invoices (
                invoice_number, customer_name, description, amount, 
                due_date, academic_year, semester, category, 
                notes, status, created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW())
        ");
        
        $stmt->execute([
            $_POST['invoice_number'],
            $customer_name,
            $_POST['description'],
            $_POST['amount'],
            $_POST['due_date'],
            $_POST['academic_year'] ?? date('Y') . '-' . (date('Y') + 1),
            $_POST['semester'] ?? '1st',
            $_POST['category'] ?? 'tuition',
            $_POST['notes'] ?? null,
            $user_id
        ]);
        
        $invoice_id = $pdo->lastInsertId();
        
        // Log activity
        logActivity($pdo, 'create_invoice', 
            "Created invoice #{$_POST['invoice_number']} for {$customer_name}");
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Invoice created successfully',
            'invoice_id' => $invoice_id
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Create Invoice Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit();
}

function handleReceivePayment($pdo) {
    global $user_id;
    
    try {
        $pdo->beginTransaction();
        
        // Validate
        if (empty($_POST['invoice_id']) || empty($_POST['amount'])) {
            throw new Exception("Invoice ID and amount are required");
        }
        
        // Get invoice details
        $stmt = $pdo->prepare("
            SELECT i.*, 
                   (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.invoice_id = i.invoice_id) as total_paid
            FROM invoices i 
            WHERE i.invoice_id = ?
        ");
        $stmt->execute([$_POST['invoice_id']]);
        $invoice = $stmt->fetch();
        
        if (!$invoice) {
            throw new Exception("Invoice not found");
        }
        
        // Calculate
        $new_total_paid = $invoice['total_paid'] + $_POST['amount'];
        $balance = $invoice['amount'] - $new_total_paid;
        
        // Determine status
        $new_status = 'partial';
        if ($balance <= 0) {
            $new_status = 'paid';
        } elseif (strtotime($invoice['due_date']) < time() && $balance > 0) {
            $new_status = 'overdue';
        }
        
        // Insert payment
        $stmt = $pdo->prepare("
            INSERT INTO payments (
                payment_number, invoice_id, amount, payment_date, payment_method,
                reference_number, notes, received_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $_POST['payment_number'] ?? 'PMT-' . date('Ymd-His'),
            $_POST['invoice_id'],
            $_POST['amount'],
            $_POST['payment_date'] ?? date('Y-m-d'),
            $_POST['payment_method'] ?? 'Cash',
            $_POST['reference_number'] ?? null,
            $_POST['notes'] ?? null,
            $user_id
        ]);
        
        // Update invoice
        $stmt = $pdo->prepare("UPDATE invoices SET status = ? WHERE invoice_id = ?");
        $stmt->execute([$new_status, $_POST['invoice_id']]);
        
        // Log
        logActivity($pdo, 'receive_payment', 
            "Received payment of ₱" . number_format($_POST['amount'], 2) . " for invoice #{$invoice['invoice_number']}");
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment recorded successfully',
            'invoice_id' => $_POST['invoice_id'],
            'new_balance' => $balance,
            'new_status' => $new_status
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Payment Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit();
}

function handleSendReminder($pdo) {
    global $user_id;
    
    try {
        if (empty($_POST['invoice_id'])) {
            throw new Exception("Invoice ID is required");
        }
        
        // Get invoice details
        $stmt = $pdo->prepare("
            SELECT i.*, s.email, s.first_name, s.last_name
            FROM invoices i
            LEFT JOIN students s ON i.customer_name = CONCAT(s.first_name, ' ', s.last_name)
            WHERE i.invoice_id = ?
        ");
        $stmt->execute([$_POST['invoice_id']]);
        $invoice = $stmt->fetch();
        
        if (!$invoice) {
            throw new Exception("Invoice not found");
        }
        
        // Record reminder
        $stmt = $pdo->prepare("
            INSERT INTO payment_reminders (invoice_id, sent_to, sent_by, sent_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([
            $_POST['invoice_id'],
            $invoice['email'],
            $user_id
        ]);
        
        // Log
        logActivity($pdo, 'send_reminder', 
            "Sent payment reminder for invoice #{$invoice['invoice_number']}");
        
        echo json_encode([
            'success' => true,
            'message' => 'Reminder sent successfully'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit();
}

function handleUpdateInvoiceStatus($pdo) {
    try {
        if (empty($_POST['invoice_id']) || empty($_POST['status'])) {
            throw new Exception("Invoice ID and status are required");
        }
        
        $stmt = $pdo->prepare("UPDATE invoices SET status = ? WHERE invoice_id = ?");
        $stmt->execute([$_POST['status'], $_POST['invoice_id']]);
        
        echo json_encode(['success' => true, 'message' => 'Status updated']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

function logActivity($pdo, $action, $details) {
    global $user_id;
    
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, action, details, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$user_id, $action, $details]);
}

// ==================== HTML HEADER ====================
function echoHeader($title, $currentTab, $pdo) {
    global $firstname, $lastname, $role;
    ?>
    <!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo $title; ?> | Financial Management System</title>
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
    
    .status-paid {
      background-color: #DCFCE7;
      color: #166534;
    }
    
    .status-overdue {
      background-color: #FEE2E2;
      color: #991B1B;
    }
    
    .status-partial {
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
        <span class="ml-2 text-xs bg-accent text-white px-2 py-0.5 rounded-full font-semibold">ACCOUNTS RECEIVABLE</span>
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
  <?php 
  // Include sidebar if exists, otherwise render basic sidebar
  if (file_exists('sidebar.php')) {
    include 'sidebar.php';
  } else {
    echo '<div class="w-64 bg-sidebar text-white">';
    echo '<div class="p-6">';
    echo '<h1 class="text-2xl font-bold">AR System</h1>';
    echo '<p class="text-gray-400 text-sm mt-1">Accounts Receivable</p>';
    echo '</div>';
    echo '<nav class="mt-6">';
    echo '<a href="?page=invoices" class="block py-3 px-6 ' . ($currentTab == 'invoices' ? 'bg-accent' : 'hover:bg-gray-800') . '">Invoices</a>';
    echo '<a href="?page=create-invoice" class="block py-3 px-6 hover:bg-gray-800">Create Invoice</a>';
    echo '<a href="?page=receive-payment" class="block py-3 px-6 hover:bg-gray-800">Receive Payment</a>';
    echo '<a href="?page=students" class="block py-3 px-6 hover:bg-gray-800">Students</a>';
    echo '<a href="?page=reports" class="block py-3 px-6 hover:bg-gray-800">Reports</a>';
    echo '</nav>';
    echo '</div>';
  }
  ?>

  <!-- Main Content -->
  <main class="flex-1 overflow-y-auto p-6">
    <?php
}

// ==================== PAGE RENDER FUNCTIONS ====================
function renderPage($page, $pdo, $currentTab) {
    switch ($page) {
        case 'invoices':
            renderInvoicesPage($pdo, $currentTab);
            break;
        case 'create-invoice':
            renderCreateInvoicePage($pdo);
            break;
        case 'receive-payment':
            renderReceivePaymentPage($pdo);
            break;
        case 'students':
            renderStudentsPage($pdo);
            break;
        case 'reports':
            renderReportsPage($pdo);
            break;
        case 'view-invoice':
            renderViewInvoicePage($pdo, $_GET['id']);
            break;
        case 'view-student':
            renderViewStudentPage($pdo, $_GET['id']);
            break;
        default:
            renderInvoicesPage($pdo, 'overview');
    }
}

function renderInvoicesPage($pdo, $currentTab) {
    // Get filters
    $status = $_GET['status'] ?? 'all';
    $search = $_GET['search'] ?? '';
    $date_from = $_GET['date_from'] ?? date('Y-m-01');
    $date_to = $_GET['date_to'] ?? date('Y-m-d');
    $overdue = $_GET['overdue'] ?? '0';
    
    // Get summary data
    $summary = getARSummary($pdo, $date_from, $date_to);
    
    // Build query for invoices
    $sql = "SELECT i.*, 
                   (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.invoice_id = i.invoice_id) as paid_amount,
                   DATEDIFF(NOW(), i.due_date) as days_overdue
            FROM invoices i 
            WHERE i.created_at BETWEEN ? AND ?";
    
    $params = [$date_from . ' 00:00:00', $date_to . ' 23:59:59'];
    
    if ($status !== 'all') {
        $sql .= " AND i.status = ?";
        $params[] = $status;
    }
    
    if ($search) {
        $sql .= " AND (i.invoice_number LIKE ? OR i.customer_name LIKE ? OR i.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($overdue == '1') {
        $sql .= " AND i.due_date < CURDATE() AND i.status IN ('pending', 'partial')";
    }
    
    $sql .= " ORDER BY i.due_date ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $invoices = $stmt->fetchAll();
    
    echoHeader('Invoices', 'invoices', $pdo);
    ?>
    
    <!-- Page Header -->
    <div class="mb-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Accounts Receivable</h1>
          <p class="text-gray-600 mt-1">Manage student invoices and payments</p>
        </div>
        <div class="flex items-center gap-3">
          <span class="text-sm text-gray-600">
            Total Receivables: <span class="font-medium text-danger">₱<?php echo number_format($summary['total_amount'] ?? 0, 2); ?></span>
          </span>
        </div>
      </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="border-b border-gray-200 mb-6">
      <nav class="flex space-x-8">
        <a href="?page=invoices&tab=overview" class="py-3 px-1 font-medium text-sm border-b-2 <?php echo $currentTab == 'overview' ? 'border-accent text-accent tab-active' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
          Overview
        </a>
        <a href="?page=invoices&tab=all" class="py-3 px-1 font-medium text-sm border-b-2 <?php echo $currentTab == 'all' ? 'border-accent text-accent tab-active' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
          All Invoices
          <span class="ml-2 bg-blue-100 text-blue-800 text-xs px-2 py-0.5 rounded-full"><?php echo $summary['total_invoices'] ?? 0; ?></span>
        </a>
        <a href="?page=invoices&tab=overdue" class="py-3 px-1 font-medium text-sm border-b-2 <?php echo $currentTab == 'overdue' ? 'border-accent text-accent tab-active' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
          Overdue
          <span class="ml-2 bg-red-100 text-red-800 text-xs px-2 py-0.5 rounded-full"><?php echo $summary['overdue_count'] ?? 0; ?></span>
        </a>
        <a href="?page=invoices&tab=unpaid" class="py-3 px-1 font-medium text-sm border-b-2 <?php echo $currentTab == 'unpaid' ? 'border-accent text-accent tab-active' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
          Unpaid
          <span class="ml-2 bg-yellow-100 text-yellow-800 text-xs px-2 py-0.5 rounded-full"><?php echo $summary['pending_count'] ?? 0; ?></span>
        </a>
      </nav>
    </div>

    <!-- Overview Cards - REMOVED -->
    <?php if ($currentTab == 'overview'): ?>
    <!-- The four summary cards have been removed as requested -->
    <?php endif; ?>

    <!-- Filters Card -->
    <div class="bg-white rounded-lg shadow mb-6">
      <div class="p-5 border-b border-gray-200">
        <h3 class="font-semibold text-gray-900 text-base">Filter Invoices</h3>
        <p class="text-sm text-gray-500">Search and filter invoice records</p>
      </div>
      <form method="GET" class="p-5">
        <input type="hidden" name="page" value="invoices">
        <input type="hidden" name="tab" value="<?php echo $currentTab; ?>">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Status Filter -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
              <option value="all" <?php echo $status == 'all' ? 'selected' : ''; ?>>All Status</option>
              <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
              <option value="paid" <?php echo $status == 'paid' ? 'selected' : ''; ?>>Paid</option>
              <option value="overdue" <?php echo $status == 'overdue' ? 'selected' : ''; ?>>Overdue</option>
              <option value="partial" <?php echo $status == 'partial' ? 'selected' : ''; ?>>Partial</option>
            </select>
          </div>

          <!-- Date Range -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
            <input type="date" name="date_from" value="<?php echo $date_from; ?>"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
            <input type="date" name="date_to" value="<?php echo $date_to; ?>"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
          </div>

          <!-- Overdue Only -->
          <div class="flex items-center pt-6">
            <label class="inline-flex items-center">
              <input type="checkbox" name="overdue" value="1" <?php echo $overdue == '1' ? 'checked' : ''; ?>
                     class="rounded border-gray-300 text-accent focus:ring-accent">
              <span class="ml-2 text-sm text-gray-700">Show overdue only</span>
            </label>
          </div>
        </div>

        <!-- Additional Filters -->
        <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
          <!-- Search -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="Search invoices..." 
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent focus:border-accent">
          </div>

          <!-- Action Buttons -->
          <div class="flex items-center gap-3 pt-6">
            <button type="submit" class="btn btn-primary flex-1 px-6 py-2 bg-accent text-white rounded-lg hover:bg-blue-700">
              Apply Filters
            </button>
            <a href="?page=invoices" class="btn btn-outline px-6 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
              Reset
            </a>
          </div>
        </div>
      </form>
    </div>

    <!-- Action Bar -->
    <div class="flex justify-between items-center mb-6">
      <div class="text-sm text-gray-600">
        Showing <?php echo count($invoices); ?> invoices
      </div>
      <div class="flex gap-3">
        <a href="?page=create-invoice" class="px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-smooth flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          New Invoice
        </a>
        <button onclick="exportInvoices()" class="px-5 py-2.5 bg-gray-800 hover:bg-gray-900 text-white font-medium rounded-lg transition-smooth flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          Export
        </button>
      </div>
    </div>

    <!-- Invoices Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Invoice Details</th>
              <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Student</th>
              <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Amount</th>
              <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Due Date</th>
              <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
              <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Balance</th>
              <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <?php if (empty($invoices)): ?>
            <tr>
              <td colspan="7" class="py-8 px-4 text-center">
                <div class="text-gray-400">
                  <svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                  </svg>
                  <p class="font-medium text-gray-900">No invoices found</p>
                  <p class="text-sm mt-1">Try adjusting your filters or create a new invoice</p>
                </div>
              </td>
            </tr>
            <?php else: ?>
              <?php foreach ($invoices as $invoice): ?>
              <?php
              $balance = $invoice['amount'] - $invoice['paid_amount'];
              $statusClass = 'status-' . $invoice['status'];
              ?>
              <tr>
                <td class="py-3 px-4">
                  <div>
                    <p class="font-medium text-gray-900"><?php echo $invoice['invoice_number']; ?></p>
                    <p class="text-sm text-gray-500 mt-0.5"><?php echo htmlspecialchars($invoice['description']); ?></p>
                    <p class="text-xs text-gray-500"><?php echo date('M d, Y', strtotime($invoice['created_at'])); ?></p>
                  </div>
                </td>
                <td class="py-3 px-4">
                  <p class="text-sm text-gray-900"><?php echo htmlspecialchars($invoice['customer_name']); ?></p>
                </td>
                <td class="py-3 px-4">
                  <p class="font-medium text-gray-900">₱<?php echo number_format($invoice['amount'], 2); ?></p>
                  <?php if ($invoice['paid_amount'] > 0): ?>
                    <p class="text-xs text-green-600 mt-0.5">Paid: ₱<?php echo number_format($invoice['paid_amount'], 2); ?></p>
                  <?php endif; ?>
                </td>
                <td class="py-3 px-4">
                  <p class="text-sm <?php echo $invoice['days_overdue'] > 0 && $balance > 0 ? 'text-red-600 font-medium' : 'text-gray-900'; ?>">
                    <?php echo date('M d, Y', strtotime($invoice['due_date'])); ?>
                    <?php if ($invoice['days_overdue'] > 0 && $balance > 0): ?>
                      <span class="text-xs block">(<?php echo $invoice['days_overdue']; ?> days overdue)</span>
                    <?php endif; ?>
                  </p>
                </td>
                <td class="py-3 px-4">
                  <span class="text-xs px-2 py-1 rounded-full font-medium <?php echo $statusClass; ?>">
                    <?php echo ucfirst($invoice['status']); ?>
                  </span>
                </td>
                <td class="py-3 px-4">
                  <p class="font-medium <?php echo $balance > 0 ? 'text-red-600' : 'text-green-600'; ?>">
                    ₱<?php echo number_format($balance, 2); ?>
                  </p>
                </td>
                <td class="py-3 px-4">
                  <div class="flex gap-2">
                    <a href="?page=view-invoice&id=<?php echo $invoice['invoice_id']; ?>" 
                       class="text-accent hover:text-blue-700 px-2 py-1 rounded hover:bg-accent-light transition-smooth">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                      </svg>
                    </a>
                    <?php if ($balance > 0): ?>
                      <a href="?page=receive-payment&invoice_id=<?php echo $invoice['invoice_id']; ?>" 
                         class="text-success hover:text-green-700 px-2 py-1 rounded hover:bg-success-light transition-smooth">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                      </a>
                    <?php endif; ?>
                    <button onclick="sendReminder(<?php echo $invoice['invoice_id']; ?>)" 
                            class="text-warning hover:text-amber-700 px-2 py-1 rounded hover:bg-warning-light transition-smooth">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                      </svg>
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
    
    <?php
    echoFooter();
}

function renderCreateInvoicePage($pdo) {
    // Get students and categories
    $students = $pdo->query("SELECT student_id, first_name, last_name, student_number FROM students ORDER BY last_name")->fetchAll();
    $categories = $pdo->query("SELECT category_code, category_name FROM revenue_categories WHERE is_active = 1 ORDER BY category_name")->fetchAll();
    
    echoHeader('Create Invoice', 'create-invoice', $pdo);
    ?>
    
    <!-- Page Header -->
    <div class="mb-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Create New Invoice</h1>
          <p class="text-gray-600 mt-1">Generate invoice for student fees</p>
        </div>
      </div>
    </div>

    <!-- Invoice Form -->
    <div class="bg-white rounded-lg shadow">
      <div class="p-5 border-b border-gray-200">
        <h3 class="font-semibold text-gray-900 text-base">Invoice Information</h3>
        <p class="text-sm text-gray-500">Fill in all required fields to create invoice</p>
      </div>
      
      <form id="invoiceForm" class="p-6">
        <div class="space-y-6">
          <!-- Student Selection -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Select Student *</label>
            <select name="student_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
              <option value="">-- Choose Student --</option>
              <?php foreach ($students as $student): ?>
                <option value="<?php echo $student['student_id']; ?>">
                  <?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name'] . ' (' . $student['student_number'] . ')'); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <!-- Invoice Details -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Invoice Number *</label>
              <input type="text" name="invoice_number" required 
                     value="INV-<?php echo date('Ymd-His'); ?>"
                     class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Fee Category *</label>
              <select name="category" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
                <option value="">-- Select Category --</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?php echo $cat['category_code']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          
          <!-- Description -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
            <textarea name="description" rows="3" required 
                      class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent"
                      placeholder="Enter fee description (e.g., Tuition Fee - 1st Semester 2024)"></textarea>
          </div>
          
          <!-- Amount and Dates -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Amount (₱) *</label>
              <input type="number" name="amount" step="0.01" min="0.01" required 
                     placeholder="0.00"
                     class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Issue Date *</label>
              <input type="date" name="issue_date" required 
                     value="<?php echo date('Y-m-d'); ?>"
                     class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Due Date *</label>
              <input type="date" name="due_date" required 
                     min="<?php echo date('Y-m-d'); ?>"
                     class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
            </div>
          </div>
          
          <!-- Academic Details -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Academic Year *</label>
              <select name="academic_year" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
                <?php
                $currentYear = date('Y');
                for ($i = -1; $i <= 1; $i++) {
                  $year = $currentYear + $i;
                  $academicYear = $year . '-' . ($year + 1);
                  echo '<option value="' . $academicYear . '" ' . ($i == 0 ? 'selected' : '') . '>' . $academicYear . '</option>';
                }
                ?>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Semester *</label>
              <select name="semester" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
                <option value="1st">1st Semester</option>
                <option value="2nd">2nd Semester</option>
                <option value="Summer">Summer</option>
                <option value="Special">Special Term</option>
              </select>
            </div>
          </div>
          
          <!-- Notes -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
            <textarea name="notes" rows="2"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent"
                      placeholder="Additional notes or instructions"></textarea>
          </div>
        </div>
        
        <!-- Form Actions -->
        <div class="mt-8 pt-6 border-t border-gray-200 bg-gray-50">
          <div class="flex items-center justify-between">
            <div class="text-sm text-gray-500">
              <p>Invoice will be created with status: <span class="font-medium text-yellow-600">Pending</span></p>
            </div>
            <div class="flex items-center gap-3">
              <a href="?page=invoices" class="px-5 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-smooth">
                Cancel
              </a>
              <button type="submit" class="px-5 py-2.5 bg-accent hover:bg-blue-700 text-white font-medium rounded-lg transition-smooth flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
                Create Invoice
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>
    
    <script>
    document.getElementById('invoiceForm').addEventListener('submit', async function(e) {
      e.preventDefault();
      showLoading();
      
      const formData = new FormData(this);
      formData.append('action', 'create_invoice');
      
      try {
        const response = await fetch('', {
          method: 'POST',
          body: formData
        });
        const data = await response.json();
        
        if (data.success) {
          showAlert('Invoice created successfully!', 'success');
          setTimeout(() => {
            window.location.href = '?page=view-invoice&id=' + data.invoice_id;
          }, 1500);
        } else {
          showAlert('Error: ' + data.error, 'error');
        }
      } catch (error) {
        showAlert('Network error', 'error');
      } finally {
        hideLoading();
      }
    });
    </script>
    
    <?php
    echoFooter();
}

function renderReceivePaymentPage($pdo) {
    $invoice_id = $_GET['invoice_id'] ?? 0;
    $invoice = null;
    
    if ($invoice_id > 0) {
        $stmt = $pdo->prepare("
            SELECT i.*, 
                   (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.invoice_id = i.invoice_id) as paid_amount
            FROM invoices i 
            WHERE i.invoice_id = ?
        ");
        $stmt->execute([$invoice_id]);
        $invoice = $stmt->fetch();
    }
    
    echoHeader('Receive Payment', 'receive-payment', $pdo);
    ?>
    
    <!-- Page Header -->
    <div class="mb-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Receive Payment</h1>
          <p class="text-gray-600 mt-1">Record payment against invoice</p>
        </div>
      </div>
    </div>

    <!-- Payment Form -->
    <div class="bg-white rounded-lg shadow">
      <div class="p-5 border-b border-gray-200">
        <h3 class="font-semibold text-gray-900 text-base">Payment Information</h3>
        <p class="text-sm text-gray-500">Fill in payment details</p>
      </div>
      
      <form id="paymentForm" class="p-6">
        <?php if ($invoice): ?>
          <input type="hidden" name="invoice_id" value="<?php echo $invoice_id; ?>">
          <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <h3 class="font-medium text-blue-900 mb-3">Invoice Details</h3>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-gray-600">Invoice:</p>
                <p class="font-medium"><?php echo $invoice['invoice_number']; ?></p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Student:</p>
                <p class="font-medium"><?php echo htmlspecialchars($invoice['customer_name']); ?></p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Balance Due:</p>
                <p class="font-medium text-red-600">
                  ₱<?php echo number_format($invoice['amount'] - $invoice['paid_amount'], 2); ?>
                </p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Due Date:</p>
                <p class="font-medium <?php echo strtotime($invoice['due_date']) < time() ? 'text-red-600' : ''; ?>">
                  <?php echo date('M d, Y', strtotime($invoice['due_date'])); ?>
                </p>
              </div>
            </div>
          </div>
        <?php else: ?>
          <!-- Invoice Selection -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Select Invoice</label>
            <select name="invoice_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
              <option value="">-- Select Invoice --</option>
              <?php
              $invoices = $pdo->query("
                SELECT i.invoice_id, i.invoice_number, i.customer_name, i.amount,
                       (i.amount - COALESCE(SUM(p.amount), 0)) as balance_due
                FROM invoices i
                LEFT JOIN payments p ON i.invoice_id = p.invoice_id
                WHERE i.status IN ('pending', 'overdue', 'partial')
                GROUP BY i.invoice_id
                HAVING balance_due > 0
                ORDER BY i.due_date ASC
              ")->fetchAll();
              foreach ($invoices as $inv) {
                echo '<option value="' . $inv['invoice_id'] . '">';
                echo htmlspecialchars($inv['invoice_number'] . ' - ' . $inv['customer_name'] . ' (₱' . number_format($inv['balance_due'], 2) . ' due)');
                echo '</option>';
              }
              ?>
            </select>
          </div>
        <?php endif; ?>
        
        <div class="space-y-6">
          <!-- Payment Details -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Payment Number *</label>
              <input type="text" name="payment_number" required 
                     value="PMT-<?php echo date('Ymd-His'); ?>"
                     class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Payment Date *</label>
              <input type="date" name="payment_date" required 
                     value="<?php echo date('Y-m-d'); ?>"
                     max="<?php echo date('Y-m-d'); ?>"
                     class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
            </div>
          </div>
          
          <!-- Amount and Method -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Amount Paid (₱) *</label>
              <input type="number" name="amount" step="0.01" min="0.01" required 
                     placeholder="0.00" id="paymentAmount"
                     oninput="updatePaymentPreview()"
                     class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
              <p class="text-xs text-gray-500 mt-1" id="balanceWarning"></p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method *</label>
              <select name="payment_method" required 
                      onchange="toggleCheckDetails(this.value)"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
                <option value="Cash">Cash</option>
                <option value="Check">Check</option>
                <option value="Bank Transfer">Bank Transfer</option>
                <option value="Credit Card">Credit Card</option>
                <option value="Online Payment">Online Payment</option>
              </select>
            </div>
          </div>
          
          <!-- Check Details -->
          <div id="checkDetails" class="hidden space-y-4 p-4 bg-gray-50 border rounded-lg">
            <h4 class="font-medium text-gray-900">Check Information</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Check Number</label>
                <input type="text" name="check_number"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Bank Name</label>
                <input type="text" name="bank_name"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Check Date</label>
                <input type="date" name="check_date"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
              </div>
            </div>
          </div>
          
          <!-- Reference and Notes -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Reference Number</label>
              <input type="text" name="reference_number"
                     placeholder="Receipt or transaction number"
                     class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Received By</label>
              <input type="text" value="<?php echo htmlspecialchars($firstname . ' ' . $lastname); ?>" 
                     readonly class="w-full border border-gray-300 bg-gray-50 rounded-lg px-3 py-2.5 text-sm">
              <input type="hidden" name="received_by" value="<?php echo $user_id; ?>">
            </div>
          </div>
          
          <!-- Notes -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
            <textarea name="notes" rows="2"
                      placeholder="Payment remarks or additional information"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent"></textarea>
          </div>
          
          <!-- Payment Preview -->
          <div id="paymentPreview" class="hidden p-4 bg-green-50 border border-green-200 rounded-lg">
            <h4 class="font-medium text-green-900 mb-3">Payment Summary</h4>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              <div>
                <p class="text-sm text-green-600">Invoice Balance</p>
                <p id="previewBalance" class="font-medium">₱0.00</p>
              </div>
              <div>
                <p class="text-sm text-green-600">Payment Amount</p>
                <p id="previewAmount" class="font-medium">₱0.00</p>
              </div>
              <div>
                <p class="text-sm text-green-600">New Balance</p>
                <p id="previewNewBalance" class="font-medium">₱0.00</p>
              </div>
              <div>
                <p class="text-sm text-green-600">New Status</p>
                <p id="previewStatus" class="font-medium">Pending</p>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Form Actions -->
        <div class="mt-8 pt-6 border-t border-gray-200 bg-gray-50">
          <div class="flex items-center justify-between">
            <a href="?page=invoices" class="px-5 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-smooth">
              Cancel
            </a>
            <button type="submit" class="px-5 py-2.5 bg-success hover:bg-green-700 text-white font-medium rounded-lg transition-smooth flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              Record Payment
            </button>
          </div>
        </div>
      </form>
    </div>
    
    <script>
    function toggleCheckDetails(method) {
      const checkDetails = document.getElementById('checkDetails');
      if (method === 'Check') {
        checkDetails.classList.remove('hidden');
      } else {
        checkDetails.classList.add('hidden');
      }
    }
    
    function updatePaymentPreview() {
      const paymentAmount = parseFloat(document.getElementById('paymentAmount').value) || 0;
      const balance = <?php echo $invoice ? $invoice['amount'] - $invoice['paid_amount'] : 0; ?>;
      
      document.getElementById('previewAmount').textContent = '₱' + paymentAmount.toFixed(2);
      
      const newBalance = Math.max(0, balance - paymentAmount);
      document.getElementById('previewNewBalance').textContent = '₱' + newBalance.toFixed(2);
      
      // Determine new status
      let newStatus = 'Partial';
      if (newBalance === 0) {
        newStatus = 'Paid';
      } else if (paymentAmount === 0) {
        newStatus = 'Unchanged';
      }
      document.getElementById('previewStatus').textContent = newStatus;
      
      // Show warning if overpayment
      const warning = document.getElementById('balanceWarning');
      if (paymentAmount > balance) {
        warning.textContent = `Warning: Overpayment of ₱${(paymentAmount - balance).toFixed(2)}`;
        warning.className = 'text-xs text-red-600 mt-1';
      } else if (paymentAmount === balance) {
        warning.textContent = 'Invoice will be marked as fully paid';
        warning.className = 'text-xs text-green-600 mt-1';
      } else {
        warning.textContent = `Balance remaining: ₱${newBalance.toFixed(2)}`;
        warning.className = 'text-xs text-gray-500 mt-1';
      }
    }
    
    document.getElementById('paymentForm').addEventListener('submit', async function(e) {
      e.preventDefault();
      showLoading();
      
      const formData = new FormData(this);
      formData.append('action', 'receive_payment');
      
      try {
        const response = await fetch('', {
          method: 'POST',
          body: formData
        });
        const data = await response.json();
        
        if (data.success) {
          showAlert('Payment recorded successfully!', 'success');
          setTimeout(() => {
            window.location.href = '?page=view-invoice&id=' + data.invoice_id;
          }, 1500);
        } else {
          showAlert('Error: ' + data.error, 'error');
        }
      } catch (error) {
        showAlert('Network error', 'error');
      } finally {
        hideLoading();
      }
    });
    
    // Initialize if invoice is preselected
    <?php if ($invoice): ?>
    document.getElementById('paymentPreview').classList.remove('hidden');
    updatePaymentPreview();
    <?php endif; ?>
    </script>
    
    <?php
    echoFooter();
}

function renderStudentsPage($pdo) {
    $search = $_GET['search'] ?? '';
    
    $sql = "SELECT s.*, 
                   (SELECT COALESCE(SUM(i.amount), 0) 
                    FROM invoices i 
                    WHERE i.customer_name = CONCAT(s.first_name, ' ', s.last_name)
                    AND i.status IN ('pending', 'overdue', 'partial')) as total_owed,
                   (SELECT COALESCE(SUM(p.amount), 0)
                    FROM payments p
                    JOIN invoices i ON p.invoice_id = i.invoice_id
                    WHERE i.customer_name = CONCAT(s.first_name, ' ', s.last_name)) as total_paid
            FROM students s";
    
    $params = [];
    if ($search) {
        $sql .= " WHERE s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_number LIKE ?";
        $params = ["%$search%", "%$search%", "%$search%"];
    }
    
    $sql .= " ORDER BY s.last_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll();
    
    echoHeader('Students', 'students', $pdo);
    ?>
    
    <!-- Page Header -->
    <div class="mb-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Students</h1>
          <p class="text-gray-600 mt-1">Manage student accounts and balances</p>
        </div>
      </div>
    </div>

    <!-- Search Bar -->
    <div class="bg-white rounded-lg shadow p-5 mb-6">
      <form method="GET" class="flex flex-col md:flex-row gap-4">
        <input type="hidden" name="page" value="students">
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
               placeholder="Search by name or student number..." 
               class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-accent">
        <button type="submit" class="px-6 py-2 bg-accent text-white rounded-lg hover:bg-blue-700">
          Search
        </button>
        <?php if ($search): ?>
          <a href="?page=students" class="px-6 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 text-center">
            Clear
          </a>
        <?php endif; ?>
      </form>
    </div>

    <!-- Students Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <div class="p-5 border-b border-gray-200">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <h3 class="font-semibold text-gray-900 text-base">Student Accounts</h3>
            <p class="text-sm text-gray-500">View student balances and history</p>
          </div>
          <div class="flex items-center gap-3">
            <div class="relative">
              <input type="text" placeholder="Quick search..." class="text-sm border border-gray-300 rounded-lg pl-10 pr-4 py-2 w-full sm:w-64 focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
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
              <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Student</th>
              <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Student ID</th>
              <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Department</th>
              <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Total Owed</th>
              <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Paid</th>
              <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Balance</th>
              <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <?php if (empty($students)): ?>
            <tr>
              <td colspan="7" class="py-8 px-4 text-center">
                <div class="text-gray-400">
                  <svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5 3.75a6 6 0 00-9.5-4.197"/>
                  </svg>
                  <p class="font-medium text-gray-900">No students found</p>
                  <p class="text-sm mt-1"><?php echo $search ? 'Try a different search' : 'No students in the system'; ?></p>
                </div>
              </td>
            </tr>
            <?php else: ?>
              <?php foreach ($students as $student): ?>
              <?php
              $balance = $student['total_owed'] - $student['total_paid'];
              ?>
              <tr>
                <td class="py-3 px-4">
                  <div class="flex items-center gap-3">
                    <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo urlencode($student['first_name']); ?>" class="w-8 h-8 rounded-full border">
                    <div>
                      <p class="font-medium text-gray-900"><?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name']); ?></p>
                      <p class="text-xs text-gray-500"><?php echo $student['email'] ?? ''; ?></p>
                    </div>
                  </div>
                </td>
                <td class="py-3 px-4">
                  <p class="font-medium"><?php echo $student['student_number']; ?></p>
                </td>
                <td class="py-3 px-4">
                  <p class="text-sm text-gray-900"><?php echo $student['department'] ?? '—'; ?></p>
                  <p class="text-xs text-gray-500"><?php echo $student['year_level'] ?? ''; ?></p>
                </td>
                <td class="py-3 px-4">
                  <p class="font-medium">₱<?php echo number_format($student['total_owed'], 2); ?></p>
                </td>
                <td class="py-3 px-4">
                  <p class="text-green-600">₱<?php echo number_format($student['total_paid'], 2); ?></p>
                </td>
                <td class="py-3 px-4">
                  <p class="font-medium <?php echo $balance > 0 ? 'text-red-600' : 'text-green-600'; ?>">
                    ₱<?php echo number_format($balance, 2); ?>
                  </p>
                </td>
                <td class="py-3 px-4">
                  <div class="flex gap-2">
                    <a href="?page=view-student&id=<?php echo $student['student_id']; ?>" 
                       class="text-accent hover:text-blue-700 px-2 py-1 rounded hover:bg-accent-light transition-smooth">
                      View
                    </a>
                    <a href="?page=create-invoice&student_id=<?php echo $student['student_id']; ?>" 
                       class="text-success hover:text-green-700 px-2 py-1 rounded hover:bg-success-light transition-smooth">
                      Invoice
                    </a>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    
    <?php
    echoFooter();
}

function renderReportsPage($pdo) {
    echoHeader('Reports', 'reports', $pdo);
    ?>
    
    <!-- Page Header -->
    <div class="mb-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Reports</h1>
          <p class="text-gray-600 mt-1">Generate accounts receivable reports</p>
        </div>
      </div>
    </div>

    <!-- Report Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <!-- Collection Report -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-start mb-4">
          <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div>
            <h3 class="font-semibold text-gray-900">Collection Report</h3>
            <p class="text-sm text-gray-600 mt-1">Daily, weekly, monthly collections</p>
          </div>
        </div>
        <form method="GET" action="export_collection.php" class="space-y-4">
          <div>
            <select name="period" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
              <option value="daily">Daily</option>
              <option value="weekly">Weekly</option>
              <option value="monthly">Monthly</option>
              <option value="custom">Custom Range</option>
            </select>
          </div>
          <div id="customDates" class="hidden space-y-2">
            <input type="date" name="start_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <input type="date" name="end_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
          </div>
          <button type="submit" class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-smooth">
            Generate Report
          </button>
        </form>
      </div>
      
      <!-- Student Ledger -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-start mb-4">
          <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
          </div>
          <div>
            <h3 class="font-semibold text-gray-900">Student Ledger</h3>
            <p class="text-sm text-gray-600 mt-1">Individual student account statement</p>
          </div>
        </div>
        <form method="GET" action="export_ledger.php" class="space-y-4">
          <div>
            <select name="student_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
              <option value="">Select Student</option>
              <?php
              $students = $pdo->query("SELECT student_id, first_name, last_name FROM students ORDER BY last_name")->fetchAll();
              foreach ($students as $student) {
                echo '<option value="' . $student['student_id'] . '">';
                echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name']);
                echo '</option>';
              }
              ?>
            </select>
          </div>
          <div class="grid grid-cols-2 gap-2">
            <input type="date" name="start_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <input type="date" name="end_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
          </div>
          <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-smooth">
            Generate Ledger
          </button>
        </form>
      </div>
      
      <!-- Aging Report -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-start mb-4">
          <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
          </div>
          <div>
            <h3 class="font-semibold text-gray-900">Aging Report</h3>
            <p class="text-sm text-gray-600 mt-1">Receivables by days overdue</p>
          </div>
        </div>
        <form method="GET" action="export_aging.php" class="space-y-4">
          <div class="grid grid-cols-2 gap-2">
            <input type="date" name="start_date" value="<?php echo date('Y-m-01'); ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <input type="date" name="end_date" value="<?php echo date('Y-m-d'); ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
          </div>
          <div>
            <select name="format" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
              <option value="pdf">PDF</option>
              <option value="excel">Excel</option>
              <option value="csv">CSV</option>
            </select>
          </div>
          <button type="submit" class="w-full px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition-smooth">
            Generate Report
          </button>
        </form>
      </div>
    </div>
    
    <script>
    // Show/hide custom dates based on period selection
    document.querySelector('select[name="period"]')?.addEventListener('change', function() {
      const customDates = document.getElementById('customDates');
      if (this.value === 'custom') {
        customDates.classList.remove('hidden');
      } else {
        customDates.classList.add('hidden');
      }
    });
    </script>
    
    <?php
    echoFooter();
}

function renderViewInvoicePage($pdo, $id) {
    if (!$id) {
        header("Location: ?page=invoices");
        exit();
    }
    
    // Get invoice
    $stmt = $pdo->prepare("
        SELECT i.*, 
               (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.invoice_id = i.invoice_id) as paid_amount,
               DATEDIFF(NOW(), i.due_date) as days_overdue
        FROM invoices i 
        WHERE i.invoice_id = ?
    ");
    $stmt->execute([$id]);
    $invoice = $stmt->fetch();
    
    if (!$invoice) {
        die("Invoice not found");
    }
    
    // Get payments
    $payments = $pdo->prepare("
        SELECT p.*, u.firstname, u.lastname
        FROM payments p
        LEFT JOIN users u ON p.received_by = u.id
        WHERE p.invoice_id = ?
        ORDER BY p.payment_date DESC
    ")->execute([$id])->fetchAll();
    
    $balance = $invoice['amount'] - $invoice['paid_amount'];
    
    echoHeader('Invoice Details', 'view-invoice', $pdo);
    ?>
    
    <!-- Page Header -->
    <div class="mb-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Invoice Details</h1>
          <p class="text-gray-600 mt-1"><?php echo $invoice['invoice_number']; ?></p>
        </div>
        <div class="flex items-center gap-3">
          <a href="?page=invoices" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
            Back to Invoices
          </a>
          <?php if ($balance > 0): ?>
            <a href="?page=receive-payment&invoice_id=<?php echo $id; ?>" 
               class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
              Receive Payment
            </a>
          <?php endif; ?>
          <button onclick="printInvoice()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            Print Invoice
          </button>
        </div>
      </div>
    </div>

    <!-- Invoice Summary -->
    <div class="bg-white rounded-lg shadow mb-6">
      <div class="p-5 border-b border-gray-200">
        <div class="flex justify-between items-start">
          <div>
            <h2 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($invoice['description']); ?></h2>
            <p class="text-gray-600 mt-1">Issued: <?php echo date('F j, Y', strtotime($invoice['created_at'])); ?></p>
          </div>
          <div class="text-right">
            <div class="text-2xl font-bold text-gray-900">₱<?php echo number_format($invoice['amount'], 2); ?></div>
            <span class="text-xs px-2 py-1 rounded-full font-medium status-<?php echo $invoice['status']; ?> mt-2 inline-block">
              <?php echo ucfirst($invoice['status']); ?>
            </span>
          </div>
        </div>
      </div>
      
      <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Invoice Information -->
        <div>
          <h3 class="font-medium text-gray-900 mb-4">Invoice Information</h3>
          <div class="space-y-3">
            <div class="flex justify-between">
              <span class="text-gray-600">Invoice Number:</span>
              <span class="font-medium"><?php echo $invoice['invoice_number']; ?></span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Student:</span>
              <span class="font-medium"><?php echo htmlspecialchars($invoice['customer_name']); ?></span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Due Date:</span>
              <span class="font-medium <?php echo $invoice['days_overdue'] > 0 ? 'text-red-600' : ''; ?>">
                <?php echo date('F j, Y', strtotime($invoice['due_date'])); ?>
                <?php if ($invoice['days_overdue'] > 0): ?>
                  <span class="text-sm">(<?php echo $invoice['days_overdue']; ?> days overdue)</span>
                <?php endif; ?>
              </span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Academic Year:</span>
              <span class="font-medium"><?php echo $invoice['academic_year']; ?></span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Semester:</span>
              <span class="font-medium"><?php echo $invoice['semester']; ?></span>
            </div>
          </div>
          
          <?php if ($invoice['notes']): ?>
            <div class="mt-6">
              <h4 class="font-medium text-gray-900 mb-2">Notes</h4>
              <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-sm text-gray-700"><?php echo nl2br(htmlspecialchars($invoice['notes'])); ?></p>
              </div>
            </div>
          <?php endif; ?>
        </div>
        
        <!-- Payment Summary -->
        <div>
          <h3 class="font-medium text-gray-900 mb-4">Payment Summary</h3>
          <div class="bg-blue-50 rounded-lg p-6 mb-6">
            <div class="space-y-4">
              <div class="flex justify-between items-center">
                <span class="text-gray-700">Invoice Amount:</span>
                <span class="font-bold text-lg">₱<?php echo number_format($invoice['amount'], 2); ?></span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-gray-700">Amount Paid:</span>
                <span class="font-bold text-green-600 text-lg">₱<?php echo number_format($invoice['paid_amount'], 2); ?></span>
              </div>
              <div class="border-t pt-4 flex justify-between items-center">
                <span class="text-gray-800 font-semibold">Balance Due:</span>
                <span class="font-bold text-xl <?php echo $balance > 0 ? 'text-red-600' : 'text-green-600'; ?>">
                  ₱<?php echo number_format($balance, 2); ?>
                </span>
              </div>
            </div>
          </div>
          
          <?php if ($balance > 0): ?>
            <div class="mt-6">
              <a href="?page=receive-payment&invoice_id=<?php echo $id; ?>" 
                 class="block w-full py-3 bg-green-600 hover:bg-green-700 text-white text-center rounded-lg font-medium transition-smooth">
                Record Payment
              </a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    
    <!-- Payment History -->
    <div class="bg-white rounded-lg shadow">
      <div class="p-5 border-b border-gray-200">
        <h3 class="font-semibold text-gray-900 text-base">Payment History</h3>
        <p class="text-sm text-gray-500">All payments received for this invoice</p>
      </div>
      
      <?php if (empty($payments)): ?>
        <div class="p-8 text-center text-gray-500">
          No payments recorded yet
        </div>
      <?php else: ?>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Date</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Payment #</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Method</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Amount</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Reference</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Received By</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <?php foreach ($payments as $payment): ?>
                <tr>
                  <td class="py-3 px-4">
                    <div class="text-sm text-gray-900">
                      <?php echo date('M d, Y', strtotime($payment['payment_date'])); ?>
                    </div>
                  </td>
                  <td class="py-3 px-4">
                    <div class="font-medium"><?php echo $payment['payment_number']; ?></div>
                  </td>
                  <td class="py-3 px-4">
                    <span class="text-sm"><?php echo $payment['payment_method']; ?></span>
                  </td>
                  <td class="py-3 px-4">
                    <div class="font-medium text-green-600">
                      ₱<?php echo number_format($payment['amount'], 2); ?>
                    </div>
                  </td>
                  <td class="py-3 px-4">
                    <div class="text-sm text-gray-900"><?php echo $payment['reference_number'] ?: '—'; ?></div>
                  </td>
                  <td class="py-3 px-4">
                    <div class="text-sm text-gray-900">
                      <?php echo $payment['firstname'] . ' ' . $payment['lastname']; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
    
    <script>
    function printInvoice() {
      window.open('print_invoice.php?id=<?php echo $id; ?>', '_blank');
    }
    </script>
    
    <?php
    echoFooter();
}

function renderViewStudentPage($pdo, $id) {
    if (!$id) {
        header("Location: ?page=students");
        exit();
    }
    
    // Get student
    $student = $pdo->prepare("SELECT * FROM students WHERE student_id = ?")->execute([$id])->fetch();
    
    if (!$student) {
        die("Student not found");
    }
    
    // Get invoices
    $invoices = $pdo->prepare("
        SELECT i.*, 
               (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.invoice_id = i.invoice_id) as paid_amount,
               DATEDIFF(NOW(), i.due_date) as days_overdue
        FROM invoices i
        WHERE i.customer_name = CONCAT(:first_name, ' ', :last_name)
        ORDER BY i.due_date DESC
    ")->execute([
        ':first_name' => $student['first_name'],
        ':last_name' => $student['last_name']
    ])->fetchAll();
    
    // Calculate totals
    $total_invoiced = 0;
    $total_paid = 0;
    foreach ($invoices as $invoice) {
        $total_invoiced += $invoice['amount'];
        $total_paid += $invoice['paid_amount'];
    }
    $balance = $total_invoiced - $total_paid;
    
    echoHeader('Student Account', 'view-student', $pdo);
    ?>
    
    <!-- Page Header -->
    <div class="mb-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Student Account</h1>
          <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></p>
        </div>
        <div class="flex items-center gap-3">
          <a href="?page=students" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
            Back to Students
          </a>
          <a href="?page=create-invoice&student_id=<?php echo $id; ?>" 
             class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
            Create Invoice
          </a>
          <button onclick="printLedger()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            Print Ledger
          </button>
        </div>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="font-medium text-gray-900 mb-2">Current Balance</h3>
        <div class="text-3xl font-bold <?php echo $balance > 0 ? 'text-red-600' : 'text-green-600'; ?>">
          ₱<?php echo number_format($balance, 2); ?>
        </div>
      </div>
      
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="font-medium text-gray-900 mb-2">Total Invoiced</h3>
        <div class="text-3xl font-bold text-gray-900">
          ₱<?php echo number_format($total_invoiced, 2); ?>
        </div>
        <p class="text-gray-600 text-sm mt-2"><?php echo count($invoices); ?> invoices</p>
      </div>
      
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="font-medium text-gray-900 mb-2">Total Paid</h3>
        <div class="text-3xl font-bold text-green-600">
          ₱<?php echo number_format($total_paid, 2); ?>
        </div>
      </div>
    </div>

    <!-- Student Information -->
    <div class="bg-white rounded-lg shadow mb-8">
      <div class="p-5 border-b border-gray-200">
        <h3 class="font-semibold text-gray-900 text-base">Student Information</h3>
      </div>
      <div class="p-6 grid grid-cols-2 md:grid-cols-4 gap-6">
        <div>
          <p class="text-sm text-gray-600">Student ID</p>
          <p class="font-medium"><?php echo $student['student_number']; ?></p>
        </div>
        <div>
          <p class="text-sm text-gray-600">Email</p>
          <p class="font-medium"><?php echo $student['email'] ?? '—'; ?></p>
        </div>
        <div>
          <p class="text-sm text-gray-600">Department</p>
          <p class="font-medium"><?php echo $student['department'] ?? '—'; ?></p>
        </div>
        <div>
          <p class="text-sm text-gray-600">Year Level</p>
          <p class="font-medium"><?php echo $student['year_level'] ?? '—'; ?></p>
        </div>
      </div>
    </div>

    <!-- Invoice History -->
    <div class="bg-white rounded-lg shadow">
      <div class="p-5 border-b border-gray-200">
        <h3 class="font-semibold text-gray-900 text-base">Invoice History</h3>
        <p class="text-sm text-gray-500">All invoices for this student</p>
      </div>
      
      <?php if (empty($invoices)): ?>
        <div class="p-8 text-center text-gray-500">
          No invoices found for this student
        </div>
      <?php else: ?>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Invoice #</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Description</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Due Date</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Amount</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Balance</th>
                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <?php foreach ($invoices as $invoice): ?>
              <?php
              $invoice_balance = $invoice['amount'] - $invoice['paid_amount'];
              $statusClass = 'status-' . $invoice['status'];
              if ($invoice['days_overdue'] > 0 && $invoice_balance > 0) {
                $statusClass = 'status-overdue';
              }
              ?>
              <tr>
                <td class="py-3 px-4">
                  <a href="?page=view-invoice&id=<?php echo $invoice['invoice_id']; ?>" 
                     class="font-medium text-accent hover:text-blue-700">
                    <?php echo $invoice['invoice_number']; ?>
                  </a>
                </td>
                <td class="py-3 px-4">
                  <div class="text-sm text-gray-900"><?php echo htmlspecialchars($invoice['description']); ?></div>
                  <div class="text-xs text-gray-500">
                    <?php echo $invoice['academic_year']; ?> - <?php echo $invoice['semester']; ?>
                  </div>
                </td>
                <td class="py-3 px-4">
                  <div class="text-sm <?php echo $invoice['days_overdue'] > 0 ? 'text-red-600' : 'text-gray-900'; ?>">
                    <?php echo date('M d, Y', strtotime($invoice['due_date'])); ?>
                  </div>
                </td>
                <td class="py-3 px-4">
                  <div class="font-medium">₱<?php echo number_format($invoice['amount'], 2); ?></div>
                </td>
                <td class="py-3 px-4">
                  <span class="text-xs px-2 py-1 rounded-full font-medium <?php echo $statusClass; ?>">
                    <?php echo ucfirst($invoice['status']); ?>
                  </span>
                </td>
                <td class="py-3 px-4">
                  <div class="font-medium <?php echo $invoice_balance > 0 ? 'text-red-600' : 'text-green-600'; ?>">
                    ₱<?php echo number_format($invoice_balance, 2); ?>
                  </div>
                </td>
                <td class="py-3 px-4">
                  <div class="flex gap-2">
                    <a href="?page=view-invoice&id=<?php echo $invoice['invoice_id']; ?>" 
                       class="text-accent hover:text-blue-700 px-2 py-1 rounded hover:bg-accent-light transition-smooth">
                      View
                    </a>
                    <?php if ($invoice_balance > 0): ?>
                      <a href="?page=receive-payment&invoice_id=<?php echo $invoice['invoice_id']; ?>" 
                         class="text-success hover:text-green-700 px-2 py-1 rounded hover:bg-success-light transition-smooth">
                        Pay
                      </a>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
    
    <script>
    function printLedger() {
      window.open('print_ledger.php?student_id=<?php echo $id; ?>', '_blank');
    }
    </script>
    
    <?php
    echoFooter();
}

// ==================== HELPER FUNCTIONS ====================
function getARSummary($pdo, $date_from, $date_to) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_invoices,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count,
                SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue_count,
                SUM(CASE WHEN status = 'partial' THEN 1 ELSE 0 END) as partial_count,
                COALESCE(SUM(amount), 0) as total_amount,
                COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) as pending_amount,
                COALESCE(SUM(CASE WHEN status = 'overdue' THEN amount ELSE 0 END), 0) as overdue_amount,
                COALESCE(SUM(CASE WHEN status = 'partial' THEN amount ELSE 0 END), 0) as partial_amount,
                (
                    SELECT COALESCE(SUM(p.amount), 0)
                    FROM payments p
                    JOIN invoices i ON p.invoice_id = i.invoice_id
                    WHERE p.payment_date BETWEEN ? AND ?
                ) as total_collected,
                (
                    SELECT COUNT(DISTINCT customer_name)
                    FROM invoices 
                    WHERE status IN ('pending', 'overdue', 'partial')
                    AND due_date BETWEEN ? AND ?
                ) as active_customers
            FROM invoices
            WHERE created_at BETWEEN ? AND ?
        ");
        
        $stmt->execute([$date_from, $date_to, $date_from, $date_to, $date_from . ' 00:00:00', $date_to . ' 23:59:59']);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

// ==================== HTML FOOTER ====================
function echoFooter() {
    ?>
      </main>
    </div>
    
    <!-- Footer -->
    <footer class="bg-white border-t py-3">
      <div class="px-6 flex flex-col sm:flex-row justify-between items-center text-xs text-gray-500">
        <div class="mb-2 sm:mb-0">
          <span>© 2025 BCP Financial Management System v3.2.1</span>
          <span class="mx-2 hidden sm:inline">•</span>
          <span class="block sm:inline mt-1 sm:mt-0">Accounts Receivable Module</span>
        </div>
        <div class="flex items-center gap-3">
          <span class="text-success font-medium">Last updated: <?php echo date('M d, Y H:i'); ?></span>
        </div>
      </div>
    </footer>
    
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
      <div class="loading-spinner"></div>
    </div>
    
    <!-- JavaScript -->
    <script>
      // Utility functions
      function showLoading() {
        document.getElementById('loadingOverlay').style.display = 'flex';
      }
      
      function hideLoading() {
        document.getElementById('loadingOverlay').style.display = 'none';
      }
      
      function showAlert(message, type = 'success') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white font-medium transform transition-transform duration-300 translate-x-full`;
        
        if (type === 'success') {
          alertDiv.classList.add('bg-green-600');
        } else if (type === 'error') {
          alertDiv.classList.add('bg-red-600');
        }
        
        alertDiv.textContent = message;
        document.body.appendChild(alertDiv);
        
        setTimeout(() => {
          alertDiv.style.transform = 'translateX(0)';
        }, 10);
        
        setTimeout(() => {
          alertDiv.style.transform = 'translateX(100%)';
          setTimeout(() => {
            document.body.removeChild(alertDiv);
          }, 300);
        }, 3000);
      }
      
      // Send reminder function
      async function sendReminder(invoiceId) {
        if (!confirm('Send payment reminder for this invoice?')) {
          return;
        }
        
        showLoading();
        try {
          const response = await fetch('', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=send_reminder&invoice_id=' + invoiceId
          });
          const data = await response.json();
          
          if (data.success) {
            showAlert('Reminder sent successfully!');
          } else {
            showAlert('Error: ' + data.error, 'error');
          }
        } catch (error) {
          showAlert('Network error', 'error');
        } finally {
          hideLoading();
        }
      }
      
      // Export invoices
      function exportInvoices() {
        showLoading();
        const params = new URLSearchParams(window.location.search);
        window.location.href = 'export_invoices.php?' + params.toString();
        setTimeout(hideLoading, 1000);
      }
    </script>
    
    </body>
    </html>
    <?php
}
?>