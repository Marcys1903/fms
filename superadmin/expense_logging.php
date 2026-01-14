<?php
session_start();
require_once '../config/config.php';

// ==================== AUTHENTICATION ====================
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// Check permissions for Expense Logging
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
        case 'create_expense':
            handleCreateExpense($pdo);
            break;
        case 'update_expense':
            handleUpdateExpense($pdo);
            break;
        case 'delete_expense':
            handleDeleteExpense($pdo);
            break;
        case 'record_bulk_expenses':
            handleBulkExpenses($pdo);
            break;
        case 'update_expense_status':
            handleUpdateExpenseStatus($pdo);
            break;
        case 'attach_receipt':
            handleAttachReceipt($pdo);
            break;
        case 'create_category':
            handleCreateCategory($pdo);
            break;
        case 'update_category':
            handleUpdateCategory($pdo);
            break;
        case 'delete_category':
            handleDeleteCategory($pdo);
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            exit();
    }
}

// ==================== MAIN ROUTING ====================
$page = $_GET['page'] ?? 'expenses';
$currentTab = $_GET['tab'] ?? 'all';

// Session variables for display
$firstname = $_SESSION['firstname'] ?? '';
$lastname = $_SESSION['lastname'] ?? '';
$role = $_SESSION['role'] ?? '';
$user_id = $_SESSION['user_id'] ?? '';

// Render the page
renderPage($page, $pdo, $currentTab);

// ==================== HANDLER FUNCTIONS ====================
function handleCreateExpense($pdo) {
    global $user_id;
    
    try {
        $pdo->beginTransaction();
        
        // Validate required fields
        $required = ['expense_date', 'category_id', 'description', 'amount', 'payment_method'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("$field is required");
            }
        }
        
        // Generate expense number
        $expense_number = 'EXP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        
        // Insert expense
        $stmt = $pdo->prepare("
            INSERT INTO expenses (
                expense_number, expense_date, category_id, description, 
                amount, payment_method, reference_number, vendor_name,
                project_id, department, receipt_attachment, notes, 
                status, created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW())
        ");
        
        $stmt->execute([
            $expense_number,
            $_POST['expense_date'],
            $_POST['category_id'],
            $_POST['description'],
            $_POST['amount'],
            $_POST['payment_method'],
            $_POST['reference_number'] ?? null,
            $_POST['vendor_name'] ?? null,
            $_POST['project_id'] ?? null,
            $_POST['department'] ?? null,
            $_POST['receipt_attachment'] ?? null,
            $_POST['notes'] ?? null,
            $user_id
        ]);
        
        $expense_id = $pdo->lastInsertId();
        
        // If there's a receipt attachment, handle it
        if (isset($_FILES['receipt_file']) && $_FILES['receipt_file']['error'] == 0) {
            $receipt_path = handleFileUpload($expense_id);
            if ($receipt_path) {
                $stmt = $pdo->prepare("UPDATE expenses SET receipt_attachment = ? WHERE expense_id = ?");
                $stmt->execute([$receipt_path, $expense_id]);
            }
        }
        
        // Log activity
        logActivity($pdo, 'create_expense', 
            "Created expense #{$expense_number} - ₱" . number_format($_POST['amount'], 2));
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Expense recorded successfully',
            'expense_id' => $expense_id,
            'expense_number' => $expense_number
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Create Expense Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit();
}

function handleUpdateExpense($pdo) {
    global $user_id;
    
    try {
        $pdo->beginTransaction();
        
        if (empty($_POST['expense_id'])) {
            throw new Exception("Expense ID is required");
        }
        
        // Check if expense exists and user has permission
        $stmt = $pdo->prepare("SELECT * FROM expenses WHERE expense_id = ?");
        $stmt->execute([$_POST['expense_id']]);
        $expense = $stmt->fetch();
        
        if (!$expense) {
            throw new Exception("Expense not found");
        }
        
        // Update expense
        $stmt = $pdo->prepare("
            UPDATE expenses SET
                expense_date = ?,
                category_id = ?,
                description = ?,
                amount = ?,
                payment_method = ?,
                reference_number = ?,
                vendor_name = ?,
                project_id = ?,
                department = ?,
                notes = ?,
                updated_by = ?,
                updated_at = NOW()
            WHERE expense_id = ?
        ");
        
        $stmt->execute([
            $_POST['expense_date'] ?? $expense['expense_date'],
            $_POST['category_id'] ?? $expense['category_id'],
            $_POST['description'] ?? $expense['description'],
            $_POST['amount'] ?? $expense['amount'],
            $_POST['payment_method'] ?? $expense['payment_method'],
            $_POST['reference_number'] ?? $expense['reference_number'],
            $_POST['vendor_name'] ?? $expense['vendor_name'],
            $_POST['project_id'] ?? $expense['project_id'],
            $_POST['department'] ?? $expense['department'],
            $_POST['notes'] ?? $expense['notes'],
            $user_id,
            $_POST['expense_id']
        ]);
        
        // Handle receipt upload if provided
        if (isset($_FILES['receipt_file']) && $_FILES['receipt_file']['error'] == 0) {
            $receipt_path = handleFileUpload($_POST['expense_id']);
            if ($receipt_path) {
                // Delete old receipt if exists
                if ($expense['receipt_attachment']) {
                    $old_file = '../' . $expense['receipt_attachment'];
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }
                
                $stmt = $pdo->prepare("UPDATE expenses SET receipt_attachment = ? WHERE expense_id = ?");
                $stmt->execute([$receipt_path, $_POST['expense_id']]);
            }
        }
        
        // Log activity
        logActivity($pdo, 'update_expense', 
            "Updated expense #{$expense['expense_number']}");
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Expense updated successfully'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Update Expense Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit();
}

function handleDeleteExpense($pdo) {
    try {
        if (empty($_POST['expense_id'])) {
            throw new Exception("Expense ID is required");
        }
        
        // Check if expense exists
        $stmt = $pdo->prepare("SELECT * FROM expenses WHERE expense_id = ?");
        $stmt->execute([$_POST['expense_id']]);
        $expense = $stmt->fetch();
        
        if (!$expense) {
            throw new Exception("Expense not found");
        }
        
        // Delete receipt file if exists
        if ($expense['receipt_attachment']) {
            $file_path = '../' . $expense['receipt_attachment'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        // Soft delete (update status to deleted)
        $stmt = $pdo->prepare("UPDATE expenses SET status = 'deleted', deleted_at = NOW() WHERE expense_id = ?");
        $stmt->execute([$_POST['expense_id']]);
        
        // Log activity
        logActivity($pdo, 'delete_expense', 
            "Deleted expense #{$expense['expense_number']}");
        
        echo json_encode([
            'success' => true,
            'message' => 'Expense deleted successfully'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit();
}

function handleBulkExpenses($pdo) {
    global $user_id;
    
    try {
        $pdo->beginTransaction();
        
        if (empty($_POST['expenses']) || !is_array($_POST['expenses'])) {
            throw new Exception("No expenses provided");
        }
        
        $success_count = 0;
        $errors = [];
        
        foreach ($_POST['expenses'] as $index => $expense_data) {
            try {
                // Validate each expense
                $required = ['expense_date', 'category_id', 'description', 'amount'];
                foreach ($required as $field) {
                    if (empty($expense_data[$field])) {
                        throw new Exception("Row " . ($index + 1) . ": $field is required");
                    }
                }
                
                // Generate expense number
                $expense_number = 'BULK-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6)) . '-' . ($index + 1);
                
                // Insert expense
                $stmt = $pdo->prepare("
                    INSERT INTO expenses (
                        expense_number, expense_date, category_id, description, 
                        amount, payment_method, vendor_name, project_id, 
                        department, notes, status, created_by, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW())
                ");
                
                $stmt->execute([
                    $expense_number,
                    $expense_data['expense_date'],
                    $expense_data['category_id'],
                    $expense_data['description'],
                    $expense_data['amount'],
                    $expense_data['payment_method'] ?? 'Cash',
                    $expense_data['vendor_name'] ?? null,
                    $expense_data['project_id'] ?? null,
                    $expense_data['department'] ?? null,
                    $expense_data['notes'] ?? null,
                    $user_id
                ]);
                
                $success_count++;
                
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
        
        // Log activity
        logActivity($pdo, 'bulk_expenses', 
            "Recorded {$success_count} bulk expenses");
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => "Successfully recorded {$success_count} expenses",
            'success_count' => $success_count,
            'errors' => $errors
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Bulk Expenses Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit();
}

function handleUpdateExpenseStatus($pdo) {
    try {
        if (empty($_POST['expense_id']) || empty($_POST['status'])) {
            throw new Exception("Expense ID and status are required");
        }
        
        $allowed_statuses = ['pending', 'approved', 'rejected', 'paid', 'cancelled'];
        if (!in_array($_POST['status'], $allowed_statuses)) {
            throw new Exception("Invalid status");
        }
        
        $stmt = $pdo->prepare("UPDATE expenses SET status = ?, status_updated_at = NOW() WHERE expense_id = ?");
        $stmt->execute([$_POST['status'], $_POST['expense_id']]);
        
        // Log activity
        $stmt = $pdo->prepare("SELECT expense_number FROM expenses WHERE expense_id = ?");
        $stmt->execute([$_POST['expense_id']]);
        $expense = $stmt->fetch();
        
        logActivity($pdo, 'update_expense_status', 
            "Updated expense #{$expense['expense_number']} status to {$_POST['status']}");
        
        echo json_encode([
            'success' => true,
            'message' => 'Expense status updated'
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

function handleAttachReceipt($pdo) {
    try {
        if (empty($_POST['expense_id'])) {
            throw new Exception("Expense ID is required");
        }
        
        if (!isset($_FILES['receipt_file']) || $_FILES['receipt_file']['error'] != 0) {
            throw new Exception("No file uploaded or upload error");
        }
        
        $receipt_path = handleFileUpload($_POST['expense_id']);
        
        if (!$receipt_path) {
            throw new Exception("Failed to upload file");
        }
        
        $stmt = $pdo->prepare("UPDATE expenses SET receipt_attachment = ? WHERE expense_id = ?");
        $stmt->execute([$receipt_path, $_POST['expense_id']]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Receipt attached successfully',
            'file_path' => $receipt_path
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit();
}

function handleFileUpload($expense_id) {
    $upload_dir = '../uploads/expense_receipts/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file = $_FILES['receipt_file'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Allowed file types
    $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf', 'gif'];
    
    if (!in_array($file_ext, $allowed_ext)) {
        throw new Exception("File type not allowed. Allowed: " . implode(', ', $allowed_ext));
    }
    
    // File size limit (5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception("File size exceeds 5MB limit");
    }
    
    // Generate unique filename
    $filename = 'expense_' . $expense_id . '_' . time() . '.' . $file_ext;
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return 'uploads/expense_receipts/' . $filename;
    }
    
    return false;
}

function logActivity($pdo, $action, $details) {
    global $user_id;
    
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, action, details, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$user_id, $action, $details]);
}

function handleCreateCategory($pdo) {
    global $user_id;
    
    try {
        $required = ['category_name', 'category_code'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("$field is required");
            }
        }
        
        // Check if category code already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM expense_categories WHERE category_code = ?");
        $stmt->execute([$_POST['category_code']]);
        $count = $stmt->fetch()['count'];
        
        if ($count > 0) {
            throw new Exception("Category code already exists");
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO expense_categories (category_name, category_code, description, is_active, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $_POST['category_name'],
            $_POST['category_code'],
            $_POST['description'] ?? null,
            $_POST['is_active'] ?? 1,
            $user_id
        ]);
        
        // Log activity
        logActivity($pdo, 'create_category', 
            "Created expense category: {$_POST['category_name']}");
        
        echo json_encode([
            'success' => true,
            'message' => 'Category created successfully'
        ]);
        
    } catch (Exception $e) {
        error_log("Create Category Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit();
}

function handleUpdateCategory($pdo) {
    global $user_id;
    
    try {
        if (empty($_POST['category_id'])) {
            throw new Exception("Category ID is required");
        }
        
        $required = ['category_name', 'category_code'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("$field is required");
            }
        }
        
        // Check if category code already exists (excluding current category)
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM expense_categories WHERE category_code = ? AND category_id != ?");
        $stmt->execute([$_POST['category_code'], $_POST['category_id']]);
        $count = $stmt->fetch()['count'];
        
        if ($count > 0) {
            throw new Exception("Category code already exists");
        }
        
        $stmt = $pdo->prepare("
            UPDATE expense_categories SET
                category_name = ?,
                category_code = ?,
                description = ?,
                is_active = ?,
                updated_by = ?,
                updated_at = NOW()
            WHERE category_id = ?
        ");
        
        $stmt->execute([
            $_POST['category_name'],
            $_POST['category_code'],
            $_POST['description'] ?? null,
            $_POST['is_active'] ?? 1,
            $user_id,
            $_POST['category_id']
        ]);
        
        // Log activity
        logActivity($pdo, 'update_category', 
            "Updated expense category: {$_POST['category_name']}");
        
        echo json_encode([
            'success' => true,
            'message' => 'Category updated successfully'
        ]);
        
    } catch (Exception $e) {
        error_log("Update Category Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit();
}

function handleDeleteCategory($pdo) {
    try {
        if (empty($_POST['category_id'])) {
            throw new Exception("Category ID is required");
        }
        
        // Check if category has expenses
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM expenses WHERE category_id = ? AND status != 'deleted'");
        $stmt->execute([$_POST['category_id']]);
        $count = $stmt->fetch()['count'];
        
        if ($count > 0) {
            throw new Exception("Cannot delete category with existing expenses");
        }
        
        $stmt = $pdo->prepare("DELETE FROM expense_categories WHERE category_id = ?");
        $stmt->execute([$_POST['category_id']]);
        
        // Log activity
        logActivity($pdo, 'delete_category', 
            "Deleted expense category ID: {$_POST['category_id']}");
        
        echo json_encode([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);
        
    } catch (Exception $e) {
        error_log("Delete Category Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit();
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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- Tailwind Configuration -->
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: '#1E293B',
            accent: '#10B981',
            success: '#22C55E',
            danger: '#EF4444',
            warning: '#F59E0B',
            info: '#3B82F6',
            navbar: '#4750DD',
            sidebar: '#1E293B',
            'accent-light': '#D1FAE5',
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
    
    ::-webkit-scbar-thumb:hover {
      background: #a1a1a1;
    }
    
    /* Smooth transitions */
    .transition-smooth {
      transition: all 0.3s ease;
    }
    
    /* Tab styling */
    .tab-active {
      border-bottom-color: #10B981;
      color: #10B981;
    }
    
    /* Status badges */
    .status-pending {
      background-color: #FEF3C7;
      color: #92400E;
    }
    
    .status-approved {
      background-color: #DCFCE7;
      color: #166534;
    }
    
    .status-rejected {
      background-color: #FEE2E2;
      color: #991B1B;
    }
    
    .status-paid {
      background-color: #DBEAFE;
      color: #1E40AF;
    }
    
    .status-cancelled {
      background-color: #F3F4F6;
      color: #6B7280;
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
      border-top: 3px solid #10B981;
      border-radius: 50%;
      width: 50px;
      height: 50px;
      animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    /* File upload styling */
    .file-upload-area {
      border: 2px dashed #D1D5DB;
      border-radius: 0.5rem;
      padding: 2rem;
      text-align: center;
      background-color: #F9FAFB;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .file-upload-area:hover {
      border-color: #10B981;
      background-color: #F0FDF4;
    }
    
    .file-upload-area.dragover {
      border-color: #10B981;
      background-color: #F0FDF4;
    }
    
    /* Table row hover effect */
    .table-row-hover:hover {
      background-color: #F9FAFB;
      transform: translateY(-1px);
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }
  </style>
</head>

<body class="bg-gray-50 h-screen overflow-hidden font-inter">

<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay">
  <div class="loading-spinner"></div>
</div>

<!-- Header -->
<header class="bg-white shadow-sm h-16 flex items-center justify-between px-6 fixed top-0 left-0 right-0 z-30 border-b border-gray-200">
  <div class="flex items-center gap-3">
    <div class="flex items-center gap-2">
      <img src="../assets/bcpnobg.png" class="h-8 w-8" alt="BCP Logo">
      <div>
        <span class="font-bold text-gray-900 text-lg">BCP Financial Hub</span>
        <span class="ml-2 text-xs bg-accent text-white px-2 py-0.5 rounded-full font-semibold">EXPENSE LOGGING</span>
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
  <!-- Include Sidebar -->
  <?php include 'sidebar.php'; ?>

  <!-- Main Content -->
  <main class="flex-1 overflow-y-auto p-6">
    <?php
}

// ==================== PAGE RENDER FUNCTIONS ====================
function renderPage($page, $pdo, $currentTab) {
    switch ($page) {
        case 'expenses':
            renderExpensesPage($pdo, $currentTab);
            break;
        case 'create-expense':
            renderCreateExpensePage($pdo);
            break;
        case 'bulk-expense':
            renderBulkExpensePage($pdo);
            break;
        case 'categories':
            renderCategoriesPage($pdo);
            break;
        case 'reports':
            renderReportsPage($pdo);
            break;
        case 'view-expense':
            if (isset($_GET['id'])) {
                renderViewExpensePage($pdo, $_GET['id']);
            } else {
                header("Location: ?page=expenses");
                exit();
            }
            break;
        case 'edit-expense':
            if (isset($_GET['id'])) {
                renderEditExpensePage($pdo, $_GET['id']);
            } else {
                header("Location: ?page=expenses");
                exit();
            }
            break;
        default:
            renderExpensesPage($pdo, 'all');
    }
}

function renderExpensesPage($pdo, $currentTab) {
    // Get filters
    $status = $_GET['status'] ?? 'all';
    $search = $_GET['search'] ?? '';
    $date_from = $_GET['date_from'] ?? date('Y-m-01');
    $date_to = $_GET['date_to'] ?? date('Y-m-d');
    $category_id = $_GET['category_id'] ?? 'all';
    $department = $_GET['department'] ?? 'all';
    
    // Adjust status based on current tab
    if ($currentTab == 'pending') {
        $status = 'pending';
    } elseif ($currentTab == 'approved') {
        $status = 'approved';
    }
    
    // Get categories for filter
    $stmt = $pdo->prepare("SELECT * FROM expense_categories ORDER BY category_name");
    $stmt->execute();
    $categories = $stmt->fetchAll();
    
    // Build query for expenses
    $sql = "SELECT e.*, c.category_name, c.category_code,
                   u.firstname as created_by_name
            FROM expenses e
            LEFT JOIN expense_categories c ON e.category_id = c.category_id
            LEFT JOIN users u ON e.created_by = u.id
            WHERE e.expense_date BETWEEN ? AND ? 
            AND e.status != 'deleted'";
    
    $params = [$date_from . ' 00:00:00', $date_to . ' 23:59:59'];
    
    if ($status !== 'all') {
        $sql .= " AND e.status = ?";
        $params[] = $status;
    }
    
    if ($category_id !== 'all') {
        $sql .= " AND e.category_id = ?";
        $params[] = $category_id;
    }
    
    if ($department !== 'all') {
        $sql .= " AND e.department = ?";
        $params[] = $department;
    }
    
    if ($search) {
        $sql .= " AND (e.expense_number LIKE ? OR e.description LIKE ? OR e.vendor_name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $sql .= " ORDER BY e.expense_date DESC, e.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $expenses = $stmt->fetchAll();
    
    // Get summary counts
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
            COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_count,
            COUNT(*) as total_count
        FROM expenses 
        WHERE expense_date BETWEEN ? AND ? 
        AND status != 'deleted'
    ");
    $stmt->execute([$date_from . ' 00:00:00', $date_to . ' 23:59:59']);
    $counts = $stmt->fetch();
    
    echoHeader('Expenses', 'expenses', $pdo);
    ?>
    
    <!-- Page Header -->
    <div class="mb-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Expense Logging</h1>
          <p class="text-gray-600 mt-1">Records all operational and project-related expenses</p>
        </div>
        <div class="flex items-center gap-3">
          <span class="text-sm text-gray-600">
            Showing <?php echo count($expenses); ?> expenses
          </span>
          <a href="?page=create-expense" class="bg-accent text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-smooth flex items-center gap-2">
            <i class="fas fa-plus"></i> Record Expense
          </a>
        </div>
      </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="border-b border-gray-200 mb-6">
      <nav class="flex space-x-8">
        <a href="?page=expenses&tab=all" class="py-3 px-1 font-medium text-sm border-b-2 <?php echo $currentTab == 'all' ? 'border-accent text-accent tab-active' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
          <i class="fas fa-receipt mr-2"></i> All Expenses
          <span class="ml-2 bg-blue-100 text-blue-800 text-xs px-2 py-0.5 rounded-full"><?php echo $counts['total_count'] ?? 0; ?></span>
        </a>
        <a href="?page=expenses&tab=pending" class="py-3 px-1 font-medium text-sm border-b-2 <?php echo $currentTab == 'pending' ? 'border-accent text-accent tab-active' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
          <i class="fas fa-clock mr-2"></i> Pending
          <span class="ml-2 bg-yellow-100 text-yellow-800 text-xs px-2 py-0.5 rounded-full"><?php echo $counts['pending_count'] ?? 0; ?></span>
        </a>
        <a href="?page=expenses&tab=approved" class="py-3 px-1 font-medium text-sm border-b-2 <?php echo $currentTab == 'approved' ? 'border-accent text-accent tab-active' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
          <i class="fas fa-check-circle mr-2"></i> Approved
          <span class="ml-2 bg-green-100 text-green-800 text-xs px-2 py-0.5 rounded-full"><?php echo $counts['approved_count'] ?? 0; ?></span>
        </a>
      </nav>
    </div>

    <!-- Filters Card -->
    <div class="bg-white rounded-lg shadow mb-6">
      <div class="p-5 border-b border-gray-200">
        <h3 class="font-semibold text-gray-900 text-base">Filter Expenses</h3>
        <p class="text-sm text-gray-500">Search and filter expense records</p>
      </div>
      <form method="GET" class="p-5">
        <input type="hidden" name="page" value="expenses">
        <input type="hidden" name="tab" value="<?php echo $currentTab; ?>">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Date Range -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
            <input type="date" name="date_from" value="<?php echo $date_from; ?>" 
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
            <input type="date" name="date_to" value="<?php echo $date_to; ?>" 
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
          </div>

          <!-- Status Filter (only show if on "All Expenses" tab) -->
          <?php if ($currentTab == 'all'): ?>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
              <option value="all" <?php echo $status == 'all' ? 'selected' : ''; ?>>All Status</option>
              <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
              <option value="approved" <?php echo $status == 'approved' ? 'selected' : ''; ?>>Approved</option>
              <option value="rejected" <?php echo $status == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
              <option value="paid" <?php echo $status == 'paid' ? 'selected' : ''; ?>>Paid</option>
              <option value="cancelled" <?php echo $status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
          </div>
          <?php else: ?>
          <input type="hidden" name="status" value="<?php echo $status; ?>">
          <?php endif; ?>

          <!-- Category Filter -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
            <select name="category_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
              <option value="all">All Categories</option>
              <?php foreach ($categories as $cat): ?>
              <option value="<?php echo $cat['category_id']; ?>" <?php echo $category_id == $cat['category_id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($cat['category_name']); ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
          <!-- Search -->
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
            <div class="flex gap-2">
              <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                     placeholder="Search by expense #, description, vendor..." 
                     class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
              <button type="submit" class="bg-accent text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-smooth">
                <i class="fas fa-search"></i> Search
              </button>
            </div>
          </div>

          <!-- Department Filter -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
            <select name="department" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
              <option value="all">All Departments</option>
              <option value="Administration" <?php echo $department == 'Administration' ? 'selected' : ''; ?>>Administration</option>
              <option value="Finance" <?php echo $department == 'Finance' ? 'selected' : ''; ?>>Finance</option>
              <option value="IT" <?php echo $department == 'IT' ? 'selected' : ''; ?>>IT</option>
              <option value="Operations" <?php echo $department == 'Operations' ? 'selected' : ''; ?>>Operations</option>
              <option value="Marketing" <?php echo $department == 'Marketing' ? 'selected' : ''; ?>>Marketing</option>
              <option value="HR" <?php echo $department == 'HR' ? 'selected' : ''; ?>>HR</option>
            </select>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-between items-center mt-6">
          <div class="text-sm text-gray-500">
            Showing <?php echo count($expenses); ?> expenses
          </div>
          <div class="flex gap-2">
            <button type="reset" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50 transition-smooth">
              Clear Filters
            </button>
            <a href="?page=bulk-expense" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition-smooth flex items-center gap-2">
              <i class="fas fa-file-import"></i> Bulk Import
            </a>
          </div>
        </div>
      </form>
    </div>

    <!-- Expenses Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Expense Details
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Category
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Amount
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Date
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <?php if (empty($expenses)): ?>
            <tr>
              <td colspan="6" class="px-6 py-12 text-center">
                <div class="text-gray-400">
                  <i class="fas fa-receipt text-4xl mb-3"></i>
                  <p class="text-lg">No expenses found</p>
                  <p class="text-sm mt-1">Try adjusting your filters or add a new expense</p>
                </div>
              </td>
            </tr>
            <?php else: 
            foreach ($expenses as $expense): ?>
            <tr class="table-row-hover transition-smooth">
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-10 w-10 bg-accent-light rounded-lg flex items-center justify-center">
                    <i class="fas fa-receipt text-accent"></i>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">
                      <?php echo htmlspecialchars($expense['expense_number']); ?>
                    </div>
                    <div class="text-sm text-gray-500 truncate max-w-xs">
                      <?php echo htmlspecialchars($expense['description']); ?>
                    </div>
                    <?php if ($expense['vendor_name']): ?>
                    <div class="text-xs text-gray-400 mt-1">
                      <i class="fas fa-building mr-1"></i><?php echo htmlspecialchars($expense['vendor_name']); ?>
                    </div>
                    <?php endif; ?>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($expense['category_name']); ?></div>
                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($expense['category_code']); ?></div>
              </td>
              <td class="px-6 py-4">
                <div class="text-lg font-bold text-danger">
                  ₱<?php echo number_format($expense['amount'], 2); ?>
                </div>
                <div class="text-xs text-gray-500">
                  <?php echo htmlspecialchars($expense['payment_method']); ?>
                </div>
              </td>
              <td class="px-6 py-4">
                <?php
                $status_classes = [
                  'pending' => 'status-pending',
                  'approved' => 'status-approved',
                  'rejected' => 'status-rejected',
                  'paid' => 'status-paid',
                  'cancelled' => 'status-cancelled'
                ];
                $status_class = $status_classes[$expense['status']] ?? 'status-pending';
                ?>
                <span class="px-3 py-1 text-xs font-medium rounded-full <?php echo $status_class; ?>">
                  <?php echo ucfirst($expense['status']); ?>
                </span>
                <?php if ($expense['receipt_attachment']): ?>
                <div class="mt-1">
                  <a href="../<?php echo $expense['receipt_attachment']; ?>" target="_blank" class="text-xs text-info hover:text-blue-700">
                    <i class="fas fa-paperclip mr-1"></i> Receipt
                  </a>
                </div>
                <?php endif; ?>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm text-gray-900">
                  <?php echo date('M d, Y', strtotime($expense['expense_date'])); ?>
                </div>
                <div class="text-xs text-gray-500">
                  By: <?php echo htmlspecialchars($expense['created_by_name']); ?>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <a href="?page=view-expense&id=<?php echo $expense['expense_id']; ?>" 
                     class="text-info hover:text-blue-700 transition-smooth" title="View">
                    <i class="fas fa-eye"></i>
                  </a>
                  <a href="?page=edit-expense&id=<?php echo $expense['expense_id']; ?>" 
                     class="text-warning hover:text-yellow-700 transition-smooth" title="Edit">
                    <i class="fas fa-edit"></i>
                  </a>
                  <button onclick="deleteExpense(<?php echo $expense['expense_id']; ?>)" 
                          class="text-danger hover:text-red-700 transition-smooth" title="Delete">
                    <i class="fas fa-trash"></i>
                  </button>
                  <?php if ($expense['status'] == 'pending'): ?>
                  <button onclick="updateStatus(<?php echo $expense['expense_id']; ?>, 'approved')" 
                          class="text-success hover:text-green-700 transition-smooth" title="Approve">
                    <i class="fas fa-check"></i>
                  </button>
                  <button onclick="updateStatus(<?php echo $expense['expense_id']; ?>, 'rejected')" 
                          class="text-danger hover:text-red-700 transition-smooth" title="Reject">
                    <i class="fas fa-times"></i>
                  </button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      
      <!-- Pagination (if needed) -->
      <?php if (count($expenses) > 0): ?>
      <div class="px-6 py-3 border-t border-gray-200">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-500">
            Showing <?php echo count($expenses); ?> of <?php echo count($expenses); ?> expenses
          </div>
          <div class="flex gap-2">
            <button class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50">
              Previous
            </button>
            <button class="px-3 py-1 border border-gray-300 rounded text-sm bg-accent text-white">
              1
            </button>
            <button class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50">
              Next
            </button>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <script>
    // Show loading overlay
    function showLoading() {
      document.getElementById('loadingOverlay').style.display = 'flex';
    }

    // Hide loading overlay
    function hideLoading() {
      document.getElementById('loadingOverlay').style.display = 'none';
    }

    // Delete expense
    function deleteExpense(expenseId) {
      if (confirm('Are you sure you want to delete this expense? This action cannot be undone.')) {
        showLoading();
        fetch('', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: new URLSearchParams({
            action: 'delete_expense',
            expense_id: expenseId
          })
        })
        .then(response => response.json())
        .then(data => {
          hideLoading();
          if (data.success) {
            alert(data.message);
            window.location.reload();
          } else {
            alert('Error: ' + data.error);
          }
        })
        .catch(error => {
          hideLoading();
          console.error('Error:', error);
          alert('An error occurred. Please try again.');
        });
      }
    }

    // Update expense status
    function updateStatus(expenseId, status) {
      showLoading();
      fetch('', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
          action: 'update_expense_status',
          expense_id: expenseId,
          status: status
        })
      })
      .then(response => response.json())
      .then(data => {
        hideLoading();
        if (data.success) {
          alert(data.message);
          window.location.reload();
        } else {
          alert('Error: ' + data.error);
        }
      })
      .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
      });
    }
    </script>
    <?php
}

function renderCreateExpensePage($pdo) {
    // Get categories for dropdown
    $stmt = $pdo->prepare("SELECT * FROM expense_categories ORDER BY category_name");
    $stmt->execute();
    $categories = $stmt->fetchAll();
    
    // Get projects for dropdown (if applicable)
    $projects = [];
    if ($pdo->query("SHOW TABLES LIKE 'projects'")->rowCount() > 0) {
        $stmt = $pdo->prepare("SELECT project_id, project_name FROM projects WHERE status = 'active'");
        $stmt->execute();
        $projects = $stmt->fetchAll();
    }
    
    echoHeader('Record Expense', 'create-expense', $pdo);
    ?>
    
    <!-- Page Header -->
    <div class="mb-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Record New Expense</h1>
          <p class="text-gray-600 mt-1">Log operational and project-related expenses</p>
        </div>
        <div>
          <a href="?page=expenses" class="text-sm text-gray-600 hover:text-accent">
            <i class="fas fa-arrow-left mr-1"></i> Back to Expenses
          </a>
        </div>
      </div>
    </div>

    <!-- Create Expense Form -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Left Column - Main Form -->
      <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow">
          <div class="p-5 border-b border-gray-200">
            <h3 class="font-semibold text-gray-900 text-base">Expense Details</h3>
            <p class="text-sm text-gray-500">Fill in the expense information</p>
          </div>
          
          <form id="expenseForm" method="POST" enctype="multipart/form-data" class="p-5">
            <input type="hidden" name="action" value="create_expense">
            
            <div class="space-y-6">
              <!-- Basic Information -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Expense Date *</label>
                  <input type="date" name="expense_date" required 
                         value="<?php echo date('Y-m-d'); ?>" 
                         class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                  <select name="category_id" required 
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['category_id']; ?>">
                      <?php echo htmlspecialchars($cat['category_name']); ?>
                    </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <!-- Description -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
                <textarea name="description" required rows="3" 
                          placeholder="Brief description of the expense..." 
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent"></textarea>
              </div>

              <!-- Amount & Payment -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Amount (₱) *</label>
                  <input type="number" name="amount" required step="0.01" min="0" 
                         placeholder="0.00" 
                         class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method *</label>
                  <select name="payment_method" required 
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
                    <option value="Cash">Cash</option>
                    <option value="Check">Check</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="Credit Card">Credit Card</option>
                    <option value="Online Payment">Online Payment</option>
                    <option value="Other">Other</option>
                  </select>
                </div>
              </div>

              <!-- Reference & Vendor -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Reference Number</label>
                  <input type="text" name="reference_number" 
                         placeholder="Check #, Transaction ID, etc." 
                         class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Vendor/Supplier Name</label>
                  <input type="text" name="vendor_name" 
                         placeholder="Company or individual name" 
                         class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
                </div>
              </div>

              <!-- Project & Department -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php if (!empty($projects)): ?>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Project (Optional)</label>
                  <select name="project_id" 
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
                    <option value="">Not assigned to project</option>
                    <?php foreach ($projects as $proj): ?>
                    <option value="<?php echo $proj['project_id']; ?>">
                      <?php echo htmlspecialchars($proj['project_name']); ?>
                    </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <?php endif; ?>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                  <select name="department" 
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
                    <option value="">Not specified</option>
                    <option value="Administration">Administration</option>
                    <option value="Finance">Finance</option>
                    <option value="IT">IT</option>
                    <option value="Operations">Operations</option>
                    <option value="Marketing">Marketing</option>
                    <option value="HR">HR</option>
                    <option value="Academic">Academic</option>
                    <option value="Facilities">Facilities</option>
                  </select>
                </div>
              </div>

              <!-- Receipt Upload -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Receipt/Attachment</label>
                <div class="file-upload-area" onclick="document.getElementById('receiptFile').click()">
                  <input type="file" id="receiptFile" name="receipt_file" 
                         accept=".jpg,.jpeg,.png,.pdf,.gif" class="hidden">
                  <div class="text-center">
                    <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                    <p class="text-sm text-gray-600">Click to upload receipt</p>
                    <p class="text-xs text-gray-400 mt-1">Supports JPG, PNG, PDF, GIF (Max 5MB)</p>
                  </div>
                </div>
                <div id="fileName" class="text-sm text-gray-500 mt-2"></div>
              </div>

              <!-- Notes -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Additional Notes</label>
                <textarea name="notes" rows="2" 
                          placeholder="Any additional information or context..." 
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent"></textarea>
              </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-gray-200">
              <a href="?page=expenses" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50 transition-smooth">
                Cancel
              </a>
              <button type="submit" class="px-4 py-2 bg-accent text-white rounded-lg text-sm hover:bg-green-600 transition-smooth flex items-center gap-2">
                <i class="fas fa-save"></i> Save Expense
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Right Column - Instructions & Preview -->
      <div class="lg:col-span-1">
        <!-- Instructions Card -->
        <div class="bg-white rounded-lg shadow p-5 mb-6">
          <h3 class="font-semibold text-gray-900 mb-4">Instructions</h3>
          <ul class="space-y-3 text-sm text-gray-600">
            <li class="flex items-start gap-2">
              <i class="fas fa-asterisk text-xs text-danger mt-1"></i>
              <span>Fields marked with * are required</span>
            </li>
            <li class="flex items-start gap-2">
              <i class="fas fa-receipt text-xs text-accent mt-1"></i>
              <span>Upload receipts for proper documentation</span>
            </li>
            <li class="flex items-start gap-2">
              <i class="fas fa-tags text-xs text-warning mt-1"></i>
              <span>Select appropriate category for accurate reporting</span>
            </li>
            <li class="flex items-start gap-2">
              <i class="fas fa-project-diagram text-xs text-info mt-1"></i>
              <span>Assign to projects for project cost tracking</span>
            </li>
            <li class="flex items-start gap-2">
              <i class="fas fa-building text-xs text-gray-500 mt-1"></i>
              <span>Specify department for budget monitoring</span>
            </li>
          </ul>
        </div>

        <!-- Recent Expenses Card -->
        <div class="bg-white rounded-lg shadow p-5">
          <h3 class="font-semibold text-gray-900 mb-4">Recent Expenses</h3>
          <?php
          $stmt = $pdo->prepare("
            SELECT e.*, c.category_name 
            FROM expenses e 
            LEFT JOIN expense_categories c ON e.category_id = c.category_id 
            WHERE e.status != 'deleted' 
            ORDER BY e.created_at DESC 
            LIMIT 5
          ");
          $stmt->execute();
          $recent_expenses = $stmt->fetchAll();
          ?>
          
          <?php if (empty($recent_expenses)): ?>
          <p class="text-sm text-gray-500">No recent expenses</p>
          <?php else: ?>
          <div class="space-y-3">
            <?php foreach ($recent_expenses as $expense): ?>
            <div class="border-l-4 border-accent pl-3 py-2">
              <div class="flex justify-between items-start">
                <div>
                  <p class="text-sm font-medium text-gray-900 truncate">
                    <?php echo htmlspecialchars($expense['description']); ?>
                  </p>
                  <p class="text-xs text-gray-500">
                    <?php echo htmlspecialchars($expense['category_name']); ?>
                  </p>
                </div>
                <span class="text-sm font-bold text-danger whitespace-nowrap">
                  ₱<?php echo number_format($expense['amount'], 2); ?>
                </span>
              </div>
              <div class="text-xs text-gray-400 mt-1">
                <?php echo date('M d, Y', strtotime($expense['expense_date'])); ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <script>
    // File upload display
    document.getElementById('receiptFile').addEventListener('change', function(e) {
      const fileName = e.target.files[0]?.name;
      const fileDisplay = document.getElementById('fileName');
      
      if (fileName) {
        fileDisplay.innerHTML = `
          <div class="flex items-center gap-2 bg-green-50 p-2 rounded">
            <i class="fas fa-file text-green-600"></i>
            <span class="text-green-700">${fileName}</span>
          </div>
        `;
      } else {
        fileDisplay.innerHTML = '';
      }
    });

    // Form submission with loading
    document.getElementById('expenseForm').addEventListener('submit', function(e) {
      e.preventDefault();
      
      // Validate amount
      const amount = document.querySelector('input[name="amount"]').value;
      if (parseFloat(amount) <= 0) {
        alert('Please enter a valid amount greater than 0.');
        return;
      }
      
      // Show loading
      showLoading();
      
      // Submit form
      const formData = new FormData(this);
      
      fetch('', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        hideLoading();
        if (data.success) {
          alert('Expense recorded successfully! Expense #: ' + data.expense_number);
          window.location.href = '?page=view-expense&id=' + data.expense_id;
        } else {
          alert('Error: ' + data.error);
        }
      })
      .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
      });
    });

    // Auto-focus first field
    document.querySelector('input[name="expense_date"]').focus();
    </script>
    <?php
}

function renderBulkExpensePage($pdo) {
    // Get categories for template
    $stmt = $pdo->prepare("SELECT * FROM expense_categories ORDER BY category_name");
    $stmt->execute();
    $categories = $stmt->fetchAll();
    
    echoHeader('Bulk Import', 'bulk-expense', $pdo);
    ?>
    
    <!-- Page Header -->
    <div class="mb-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Bulk Expense Import</h1>
          <p class="text-gray-600 mt-1">Import multiple expenses from Excel/CSV file</p>
        </div>
        <div>
          <a href="?page=expenses" class="text-sm text-gray-600 hover:text-accent">
            <i class="fas fa-arrow-left mr-1"></i> Back to Expenses
          </a>
        </div>
      </div>
    </div>

    <!-- Bulk Import Interface -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Left Column - Upload & Instructions -->
      <div>
        <div class="bg-white rounded-lg shadow p-5 mb-6">
          <h3 class="font-semibold text-gray-900 mb-4">Upload File</h3>
          
          <!-- File Upload Area -->
          <div class="file-upload-area" 
               onclick="document.getElementById('bulkFile').click()"
               ondrop="handleDrop(event)"
               ondragover="handleDragOver(event)"
               ondragleave="handleDragLeave(event)">
            <input type="file" id="bulkFile" accept=".csv,.xlsx,.xls" class="hidden">
            <div class="text-center py-8">
              <i class="fas fa-file-excel text-4xl text-green-500 mb-3"></i>
              <p class="text-lg font-medium text-gray-700">Drop your file here</p>
              <p class="text-sm text-gray-500 mt-1">or click to browse</p>
              <p class="text-xs text-gray-400 mt-2">Supports CSV, XLSX, XLS formats</p>
            </div>
          </div>
          <div id="bulkFileName" class="text-sm text-gray-500 mt-2"></div>
          
          <!-- Template Download -->
          <div class="mt-6">
            <a href="javascript:void(0)" onclick="downloadTemplate()" 
               class="inline-flex items-center gap-2 text-sm text-accent hover:text-green-700">
              <i class="fas fa-download"></i> Download Template File
            </a>
            <p class="text-xs text-gray-500 mt-1">Use our template for proper formatting</p>
          </div>
        </div>

        <!-- Instructions -->
        <div class="bg-white rounded-lg shadow p-5">
          <h3 class="font-semibold text-gray-900 mb-4">Import Instructions</h3>
          <div class="space-y-3 text-sm text-gray-600">
            <div class="flex items-start gap-2">
              <div class="bg-accent-light text-accent rounded-full w-5 h-5 flex items-center justify-center text-xs flex-shrink-0 mt-0.5">1</div>
              <p>Download the template file or prepare your CSV/Excel file with required columns</p>
            </div>
            <div class="flex items-start gap-2">
              <div class="bg-accent-light text-accent rounded-full w-5 h-5 flex items-center justify-center text-xs flex-shrink-0 mt-0.5">2</div>
              <p>Required columns: <code>expense_date</code>, <code>category_id</code>, <code>description</code>, <code>amount</code></p>
            </div>
            <div class="flex items-start gap-2">
              <div class="bg-accent-light text-accent rounded-full w-5 h-5 flex items-center justify-center text-xs flex-shrink-0 mt-0.5">3</div>
              <p>Optional columns: <code>payment_method</code>, <code>vendor_name</code>, <code>project_id</code>, <code>department</code>, <code>notes</code></p>
            </div>
            <div class="flex items-start gap-2">
              <div class="bg-accent-light text-accent rounded-full w-5 h-5 flex items-center justify-center text-xs flex-shrink-0 mt-0.5">4</div>
              <p>Upload your file using the upload area above</p>
            </div>
            <div class="flex items-start gap-2">
              <div class="bg-accent-light text-accent rounded-full w-5 h-5 flex items-center justify-center text-xs flex-shrink-0 mt-0.5">5</div>
              <p>Review the preview and submit to create all expenses</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column - Preview & Categories -->
      <div>
        <!-- Categories Reference -->
        <div class="bg-white rounded-lg shadow p-5 mb-6">
          <h3 class="font-semibold text-gray-900 mb-4">Category Reference</h3>
          <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-3 py-2 text-left font-medium text-gray-700">ID</th>
                  <th class="px-3 py-2 text-left font-medium text-gray-700">Category Name</th>
                  <th class="px-3 py-2 text-left font-medium text-gray-700">Code</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <?php foreach ($categories as $cat): ?>
                <tr>
                  <td class="px-3 py-2 font-medium"><?php echo $cat['category_id']; ?></td>
                  <td class="px-3 py-2"><?php echo htmlspecialchars($cat['category_name']); ?></td>
                  <td class="px-3 py-2 text-gray-500"><?php echo htmlspecialchars($cat['category_code']); ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Preview Area -->
        <div class="bg-white rounded-lg shadow p-5">
          <h3 class="font-semibold text-gray-900 mb-4">Preview</h3>
          <div id="previewArea" class="border border-gray-200 rounded-lg p-4 min-h-[200px]">
            <div class="text-center text-gray-400 py-8">
              <i class="fas fa-table text-3xl mb-2"></i>
              <p>Upload a file to preview expenses</p>
            </div>
          </div>
          
          <!-- Action Buttons -->
          <div class="flex justify-end gap-3 mt-6">
            <button onclick="clearUpload()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">
              Clear
            </button>
            <button onclick="submitBulk()" id="submitBtn" disabled 
                    class="px-4 py-2 bg-accent text-white rounded-lg text-sm hover:bg-green-600 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
              <i class="fas fa-upload"></i> Import Expenses
            </button>
          </div>
        </div>
      </div>
    </div>

    <script>
    let expensesData = [];

    // Handle file upload
    document.getElementById('bulkFile').addEventListener('change', function(e) {
      handleFile(e.target.files[0]);
    });

    // Drag and drop handlers
    function handleDragOver(e) {
      e.preventDefault();
      e.currentTarget.classList.add('dragover');
    }

    function handleDragLeave(e) {
      e.preventDefault();
      e.currentTarget.classList.remove('dragover');
    }

    function handleDrop(e) {
      e.preventDefault();
      e.currentTarget.classList.remove('dragover');
      
      const files = e.dataTransfer.files;
      if (files.length > 0) {
        handleFile(files[0]);
      }
    }

    function handleFile(file) {
      if (!file) return;
      
      const validTypes = ['text/csv', 'application/vnd.ms-excel', 
                         'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
      
      if (!validTypes.some(type => file.type.includes(type.replace('application/', ''))) &&
          !file.name.match(/\.(csv|xlsx|xls)$/i)) {
        alert('Please upload a CSV or Excel file.');
        return;
      }
      
      // Display file name
      document.getElementById('bulkFileName').innerHTML = `
        <div class="flex items-center gap-2 bg-blue-50 p-2 rounded">
          <i class="fas fa-file-excel text-blue-600"></i>
          <span class="text-blue-700 font-medium">${file.name}</span>
          <span class="text-xs text-gray-500">(${(file.size / 1024).toFixed(1)} KB)</span>
        </div>
      `;
      
      // Parse file and preview
      parseFile(file);
    }

    function parseFile(file) {
      showLoading();
      
      const formData = new FormData();
      formData.append('file', file);
      formData.append('parse_bulk', 'true');
      
      fetch('', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        hideLoading();
        if (data.success) {
          expensesData = data.expenses;
          displayPreview(data.expenses);
          document.getElementById('submitBtn').disabled = false;
        } else {
          alert('Error parsing file: ' + data.error);
        }
      })
      .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('Error parsing file. Please check format.');
      });
    }

    function displayPreview(expenses) {
      const previewArea = document.getElementById('previewArea');
      
      if (expenses.length === 0) {
        previewArea.innerHTML = `
          <div class="text-center text-gray-400 py-8">
            <i class="fas fa-exclamation-triangle text-3xl mb-2"></i>
            <p>No valid expenses found in file</p>
          </div>
        `;
        return;
      }
      
      let html = `
        <div class="text-sm mb-3 text-gray-600">
          Found ${expenses.length} expenses to import
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full text-xs">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-2 py-1 text-left font-medium">Date</th>
                <th class="px-2 py-1 text-left font-medium">Description</th>
                <th class="px-2 py-1 text-left font-medium">Amount</th>
                <th class="px-2 py-1 text-left font-medium">Category</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
      `;
      
      expenses.slice(0, 10).forEach(exp => {
        html += `
          <tr>
            <td class="px-2 py-1">${exp.expense_date}</td>
            <td class="px-2 py-1 truncate max-w-xs">${exp.description}</td>
            <td class="px-2 py-1 font-medium">₱${parseFloat(exp.amount).toFixed(2)}</td>
            <td class="px-2 py-1 text-gray-500">${exp.category_name}</td>
          </tr>
        `;
      });
      
      if (expenses.length > 10) {
        html += `
          <tr>
            <td colspan="4" class="px-2 py-2 text-center text-gray-500">
              ... and ${expenses.length - 10} more expenses
            </td>
          </tr>
        `;
      }
      
      html += `
            </tbody>
          </table>
        </div>
      `;
      
      previewArea.innerHTML = html;
    }

    function submitBulk() {
      if (expensesData.length === 0) {
        alert('No expenses to import');
        return;
      }
      
      if (!confirm(`Are you sure you want to import ${expensesData.length} expenses?`)) {
        return;
      }
      
      showLoading();
      
      fetch('', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          action: 'record_bulk_expenses',
          expenses: expensesData
        })
      })
      .then(response => response.json())
      .then(data => {
        hideLoading();
        if (data.success) {
          alert(`Successfully imported ${data.success_count} expenses!`);
          if (data.errors && data.errors.length > 0) {
            console.log('Errors:', data.errors);
          }
          window.location.href = '?page=expenses';
        } else {
          alert('Error: ' + data.error);
        }
      })
      .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('An error occurred during import.');
      });
    }

    function downloadTemplate() {
      // Create template data
      const template = [
        ['expense_date', 'category_id', 'description', 'amount', 'payment_method', 'vendor_name', 'project_id', 'department', 'notes'],
        ['2024-01-15', '1', 'Office Supplies Purchase', '1500.00', 'Cash', 'Office Depot', '', 'Administration', 'Monthly office supplies'],
        ['2024-01-16', '2', 'Internet Bill - January', '2500.00', 'Bank Transfer', 'PLDT', '', 'IT', 'Monthly internet service'],
        ['2024-01-17', '3', 'Employee Training', '5000.00', 'Check', 'Training Center', '', 'HR', 'Soft skills training']
      ];
      
      // Convert to CSV
      const csvContent = template.map(row => 
        row.map(cell => `"${cell}"`).join(',')
      ).join('\n');
      
      // Create download link
      const blob = new Blob([csvContent], { type: 'text/csv' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'expense_import_template.csv';
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    }

    function clearUpload() {
      document.getElementById('bulkFile').value = '';
      document.getElementById('bulkFileName').innerHTML = '';
      document.getElementById('previewArea').innerHTML = `
        <div class="text-center text-gray-400 py-8">
          <i class="fas fa-table text-3xl mb-2"></i>
          <p>Upload a file to preview expenses</p>
        </div>
      `;
      document.getElementById('submitBtn').disabled = true;
      expensesData = [];
    }
    </script>
    <?php
}

function renderCategoriesPage($pdo) {
    // Get all categories
    $stmt = $pdo->prepare("SELECT * FROM expense_categories ORDER BY category_name");
    $stmt->execute();
    $categories = $stmt->fetchAll();
    
    echoHeader('Expense Categories', 'categories', $pdo);
    ?>
    
    <!-- Page Header -->
    <div class="mb-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Expense Categories</h1>
          <p class="text-gray-600 mt-1">Manage expense categories for classification</p>
        </div>
        <div>
          <button onclick="openCreateModal()" class="bg-accent text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-smooth flex items-center gap-2">
            <i class="fas fa-plus"></i> New Category
          </button>
        </div>
      </div>
    </div>

    <!-- Categories Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <?php foreach ($categories as $category): ?>
      <div class="bg-white rounded-lg shadow p-5 hover:shadow-md transition-shadow">
        <div class="flex justify-between items-start mb-4">
          <div>
            <h3 class="font-semibold text-gray-900 text-lg"><?php echo htmlspecialchars($category['category_name']); ?></h3>
            <div class="flex items-center gap-2 mt-1">
              <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">
                <?php echo htmlspecialchars($category['category_code']); ?>
              </span>
              <?php if ($category['is_active']): ?>
              <span class="text-xs bg-green-100 text-green-800 px-2 py-0.5 rounded">Active</span>
              <?php else: ?>
              <span class="text-xs bg-red-100 text-red-800 px-2 py-0.5 rounded">Inactive</span>
              <?php endif; ?>
            </div>
          </div>
          <div class="flex items-center gap-1">
            <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($category)); ?>)" 
                    class="text-warning hover:text-yellow-700 p-1">
              <i class="fas fa-edit"></i>
            </button>
            <button onclick="deleteCategory(<?php echo $category['category_id']; ?>)" 
                    class="text-danger hover:text-red-700 p-1">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>
        
        <p class="text-sm text-gray-600 mb-4"><?php echo htmlspecialchars($category['description']); ?></p>
        
        <div class="pt-4 border-t border-gray-200">
          <div class="flex justify-between text-sm text-gray-500">
            <div>
              <i class="fas fa-layer-group mr-1"></i>
              <?php 
              $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM expenses WHERE category_id = ? AND status != 'deleted'");
              $stmt->execute([$category['category_id']]);
              $count = $stmt->fetch()['count'];
              echo $count . ' expense' . ($count != 1 ? 's' : '');
              ?>
            </div>
            <div>
              <?php 
              $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE category_id = ? AND status != 'deleted'");
              $stmt->execute([$category['category_id']]);
              $total = $stmt->fetch()['total'];
              ?>
              ₱<?php echo number_format($total, 2); ?>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      
      <?php if (empty($categories)): ?>
      <div class="md:col-span-2 lg:col-span-3">
        <div class="bg-white rounded-lg shadow p-8 text-center">
          <i class="fas fa-folder-open text-4xl text-gray-300 mb-3"></i>
          <h3 class="text-lg font-medium text-gray-900 mb-2">No categories found</h3>
          <p class="text-gray-500 mb-4">Create your first expense category to get started</p>
          <button onclick="openCreateModal()" class="bg-accent text-white px-4 py-2 rounded-lg hover:bg-green-600">
            Create Category
          </button>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Create/Edit Category Modal -->
    <div id="categoryModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 hidden items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full max-h-[90vh] overflow-y-auto animate-fade-in">
        <div class="p-5 border-b border-gray-200">
          <h3 id="modalTitle" class="font-semibold text-gray-900 text-lg"></h3>
        </div>
        
        <form id="categoryForm" class="p-5">
          <input type="hidden" id="categoryId" name="category_id">
          <input type="hidden" id="actionType" name="action">
          
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Category Name *</label>
              <input type="text" id="categoryName" name="category_name" required 
                     class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Category Code *</label>
              <input type="text" id="categoryCode" name="category_code" required 
                     class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
              <textarea id="categoryDescription" name="description" rows="3" 
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent"></textarea>
            </div>
            
            <div>
              <label class="flex items-center gap-2">
                <input type="checkbox" id="isActive" name="is_active" value="1" checked 
                       class="rounded border-gray-300 text-accent focus:ring-accent">
                <span class="text-sm text-gray-700">Active Category</span>
              </label>
            </div>
          </div>
          
          <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-gray-200">
            <button type="button" onclick="closeModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">
              Cancel
            </button>
            <button type="submit" class="px-4 py-2 bg-accent text-white rounded-lg text-sm hover:bg-green-600">
              Save Category
            </button>
          </div>
        </form>
      </div>
    </div>

    <script>
    function openCreateModal() {
      document.getElementById('modalTitle').textContent = 'Create New Category';
      document.getElementById('categoryId').value = '';
      document.getElementById('actionType').value = 'create_category';
      document.getElementById('categoryName').value = '';
      document.getElementById('categoryCode').value = '';
      document.getElementById('categoryDescription').value = '';
      document.getElementById('isActive').checked = true;
      document.getElementById('categoryModal').classList.remove('hidden');
      document.getElementById('categoryModal').classList.add('flex');
      document.getElementById('categoryName').focus();
    }

    function openEditModal(category) {
      document.getElementById('modalTitle').textContent = 'Edit Category';
      document.getElementById('categoryId').value = category.category_id;
      document.getElementById('actionType').value = 'update_category';
      document.getElementById('categoryName').value = category.category_name;
      document.getElementById('categoryCode').value = category.category_code;
      document.getElementById('categoryDescription').value = category.description || '';
      document.getElementById('isActive').checked = category.is_active == 1;
      document.getElementById('categoryModal').classList.remove('hidden');
      document.getElementById('categoryModal').classList.add('flex');
    }

    function closeModal() {
      document.getElementById('categoryModal').classList.add('hidden');
      document.getElementById('categoryModal').classList.remove('flex');
    }

    // Handle form submission
    document.getElementById('categoryForm').addEventListener('submit', function(e) {
      e.preventDefault();
      
      showLoading();
      
      fetch('', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(new FormData(this))
      })
      .then(response => response.json())
      .then(data => {
        hideLoading();
        if (data.success) {
          alert(data.message);
          closeModal();
          window.location.reload();
        } else {
          alert('Error: ' + data.error);
        }
      })
      .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
      });
    });

    function deleteCategory(categoryId) {
      if (!confirm('Are you sure you want to delete this category? This will not delete existing expenses in this category.')) {
        return;
      }
      
      showLoading();
      
      fetch('', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
          action: 'delete_category',
          category_id: categoryId
        })
      })
      .then(response => response.json())
      .then(data => {
        hideLoading();
        if (data.success) {
          alert(data.message);
          window.location.reload();
        } else {
          alert('Error: ' + data.error);
        }
      })
      .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
      });
    }
    </script>
    <?php
}

function renderReportsPage($pdo) {
    // Get report parameters
    $report_type = $_GET['type'] ?? 'summary';
    $year = $_GET['year'] ?? date('Y');
    $month = $_GET['month'] ?? date('m');
    $category_id = $_GET['category_id'] ?? 'all';
    $department = $_GET['department'] ?? 'all';
    
    // Get years with expenses
    $stmt = $pdo->prepare("SELECT DISTINCT YEAR(expense_date) as year FROM expenses ORDER BY year DESC");
    $stmt->execute();
    $years = $stmt->fetchAll();
    
    // Get categories for filter
    $stmt = $pdo->prepare("SELECT * FROM expense_categories ORDER BY category_name");
    $stmt->execute();
    $categories = $stmt->fetchAll();
    
    // Get report data based on type
    $report_data = [];
    $total_amount = 0;
    
    if ($report_type === 'summary') {
        // Summary by category
        $sql = "SELECT 
                    c.category_name,
                    c.category_code,
                    COUNT(e.expense_id) as expense_count,
                    COALESCE(SUM(e.amount), 0) as total_amount
                FROM expense_categories c
                LEFT JOIN expenses e ON c.category_id = e.category_id 
                    AND YEAR(e.expense_date) = ? 
                    AND MONTH(e.expense_date) = ?
                    AND e.status != 'deleted'
                ";
        
        if ($department !== 'all') {
            $sql .= " AND e.department = ?";
        }
        
        $sql .= " GROUP BY c.category_id, c.category_name, c.category_code
                  ORDER BY total_amount DESC";
        
        $stmt = $pdo->prepare($sql);
        $params = [$year, $month];
        if ($department !== 'all') {
            $params[] = $department;
        }
        $stmt->execute($params);
        $report_data = $stmt->fetchAll();
        
        // Calculate total
        $total_stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) as total 
            FROM expenses 
            WHERE YEAR(expense_date) = ? 
            AND MONTH(expense_date) = ?
            AND status != 'deleted'
        ");
        $total_stmt->execute([$year, $month]);
        $total_amount = $total_stmt->fetch()['total'];
        
    } elseif ($report_type === 'monthly') {
        // Monthly trend
        $sql = "SELECT 
                    MONTH(expense_date) as month,
                    DATE_FORMAT(expense_date, '%M') as month_name,
                    COUNT(expense_id) as expense_count,
                    COALESCE(SUM(amount), 0) as total_amount
                FROM expenses 
                WHERE YEAR(expense_date) = ?
                AND status != 'deleted'";
        
        if ($category_id !== 'all') {
            $sql .= " AND category_id = ?";
        }
        if ($department !== 'all') {
            $sql .= " AND department = ?";
        }
        
        $sql .= " GROUP BY MONTH(expense_date), DATE_FORMAT(expense_date, '%M')
                  ORDER BY month";
        
        $stmt = $pdo->prepare($sql);
        $params = [$year];
        if ($category_id !== 'all') {
            $params[] = $category_id;
        }
        if ($department !== 'all') {
            $params[] = $department;
        }
        $stmt->execute($params);
        $report_data = $stmt->fetchAll();
        
        // Calculate yearly total
        $total_stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) as total 
            FROM expenses 
            WHERE YEAR(expense_date) = ?
            AND status != 'deleted'
        ");
        $total_stmt->execute([$year]);
        $total_amount = $total_stmt->fetch()['total'];
        
    } elseif ($report_type === 'department') {
        // By department
        $sql = "SELECT 
                    department,
                    COUNT(expense_id) as expense_count,
                    COALESCE(SUM(amount), 0) as total_amount
                FROM expenses 
                WHERE YEAR(expense_date) = ?
                AND status != 'deleted'";
        
        if ($category_id !== 'all') {
            $sql .= " AND category_id = ?";
        }
        
        $sql .= " GROUP BY department
                  ORDER BY total_amount DESC";
        
        $stmt = $pdo->prepare($sql);
        $params = [$year];
        if ($category_id !== 'all') {
            $params[] = $category_id;
        }
        $stmt->execute($params);
        $report_data = $stmt->fetchAll();
    }
    
    echoHeader('Expense Reports', 'reports', $pdo);
    ?>
    
    <!-- Page Header -->
    <div class="mb-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Expense Reports</h1>
          <p class="text-gray-600 mt-1">Analytics and insights on expense patterns</p>
        </div>
        <div class="flex items-center gap-2">
          <button onclick="exportToExcel()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-smooth flex items-center gap-2">
            <i class="fas fa-file-excel"></i> Export
          </button>
          <button onclick="window.print()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-smooth flex items-center gap-2">
            <i class="fas fa-print"></i> Print
          </button>
        </div>
      </div>
    </div>

    <!-- Report Filters -->
    <div class="bg-white rounded-lg shadow p-5 mb-6">
      <h3 class="font-semibold text-gray-900 mb-4">Report Parameters</h3>
      <form method="GET" class="space-y-4">
        <input type="hidden" name="page" value="reports">
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <!-- Report Type -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Report Type</label>
            <select name="type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
              <option value="summary" <?php echo $report_type == 'summary' ? 'selected' : ''; ?>>Category Summary</option>
              <option value="monthly" <?php echo $report_type == 'monthly' ? 'selected' : ''; ?>>Monthly Trend</option>
              <option value="department" <?php echo $report_type == 'department' ? 'selected' : ''; ?>>By Department</option>
            </select>
          </div>

          <!-- Year -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
            <select name="year" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
              <?php foreach ($years as $y): ?>
              <option value="<?php echo $y['year']; ?>" <?php echo $year == $y['year'] ? 'selected' : ''; ?>>
                <?php echo $y['year']; ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Month (for summary report) -->
          <?php if ($report_type === 'summary'): ?>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Month</label>
            <select name="month" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
              <?php for ($m = 1; $m <= 12; $m++): ?>
              <option value="<?php echo sprintf('%02d', $m); ?>" <?php echo $month == sprintf('%02d', $m) ? 'selected' : ''; ?>>
                <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
              </option>
              <?php endfor; ?>
            </select>
          </div>
          <?php endif; ?>

          <!-- Category Filter -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
            <select name="category_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
              <option value="all">All Categories</option>
              <?php foreach ($categories as $cat): ?>
              <option value="<?php echo $cat['category_id']; ?>" <?php echo $category_id == $cat['category_id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($cat['category_name']); ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Department Filter -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
            <select name="department" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
              <option value="all">All Departments</option>
              <option value="Administration" <?php echo $department == 'Administration' ? 'selected' : ''; ?>>Administration</option>
              <option value="Finance" <?php echo $department == 'Finance' ? 'selected' : ''; ?>>Finance</option>
              <option value="IT" <?php echo $department == 'IT' ? 'selected' : ''; ?>>IT</option>
              <option value="Operations" <?php echo $department == 'Operations' ? 'selected' : ''; ?>>Operations</option>
              <option value="Marketing" <?php echo $department == 'Marketing' ? 'selected' : ''; ?>>Marketing</option>
              <option value="HR" <?php echo $department == 'HR' ? 'selected' : ''; ?>>HR</option>
            </select>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-end gap-2 pt-4">
          <button type="reset" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
            Reset
          </button>
          <button type="submit" class="px-4 py-2 bg-accent text-white rounded-lg hover:bg-green-600">
            Generate Report
          </button>
        </div>
      </form>
    </div>

    <!-- Report Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">Total Expenses</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">₱<?php echo number_format($total_amount, 2); ?></p>
          </div>
          <div class="bg-blue-100 p-3 rounded-lg">
            <i class="fas fa-chart-pie text-blue-600 text-xl"></i>
          </div>
        </div>
      </div>
      
      <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">Number of Expenses</p>
            <p class="text-2xl font-bold text-gray-900 mt-1"><?php echo count($report_data); ?></p>
          </div>
          <div class="bg-green-100 p-3 rounded-lg">
            <i class="fas fa-file-invoice-dollar text-green-600 text-xl"></i>
          </div>
        </div>
      </div>
      
      <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">Average per Expense</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">
              ₱<?php echo count($report_data) > 0 ? number_format($total_amount / count($report_data), 2) : '0.00'; ?>
            </p>
          </div>
          <div class="bg-purple-100 p-3 rounded-lg">
            <i class="fas fa-calculator text-purple-600 text-xl"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Report Results -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <div class="p-5 border-b border-gray-200">
        <h3 class="font-semibold text-gray-900">
          <?php
          $report_titles = [
              'summary' => "Category Summary for " . date('F Y', strtotime($year . '-' . $month . '-01')),
              'monthly' => "Monthly Expense Trend for " . $year,
              'department' => "Department-wise Expenses for " . $year
          ];
          echo $report_titles[$report_type];
          ?>
        </h3>
      </div>
      
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <?php if ($report_type === 'summary'): ?>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Category
              </th>
              <?php elseif ($report_type === 'monthly'): ?>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Month
              </th>
              <?php elseif ($report_type === 'department'): ?>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Department
              </th>
              <?php endif; ?>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Number of Expenses
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Total Amount
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Percentage
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <?php if (empty($report_data)): ?>
            <tr>
              <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                No data found for the selected criteria
              </td>
            </tr>
            <?php else: 
            foreach ($report_data as $row): 
              $percentage = $total_amount > 0 ? ($row['total_amount'] / $total_amount) * 100 : 0;
            ?>
            <tr>
              <?php if ($report_type === 'summary'): ?>
              <td class="px-6 py-4">
                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['category_name']); ?></div>
                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($row['category_code']); ?></div>
              </td>
              <?php elseif ($report_type === 'monthly'): ?>
              <td class="px-6 py-4">
                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['month_name']); ?></div>
              </td>
              <?php elseif ($report_type === 'department'): ?>
              <td class="px-6 py-4">
                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['department']); ?></div>
              </td>
              <?php endif; ?>
              <td class="px-6 py-4">
                <div class="text-sm text-gray-900"><?php echo $row['expense_count']; ?></div>
              </td>
              <td class="px-6 py-4">
                <div class="text-lg font-bold text-danger">₱<?php echo number_format($row['total_amount'], 2); ?></div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-accent h-2 rounded-full" style="width: <?php echo min($percentage, 100); ?>%"></div>
                  </div>
                  <span class="text-sm text-gray-600"><?php echo number_format($percentage, 1); ?>%</span>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
          
          <?php if (!empty($report_data)): ?>
          <tfoot class="bg-gray-50">
            <tr>
              <td class="px-6 py-3 text-sm font-medium text-gray-900">
                Total
              </td>
              <td class="px-6 py-3 text-sm font-medium text-gray-900">
                <?php 
                $total_count = array_sum(array_column($report_data, 'expense_count'));
                echo $total_count;
                ?>
              </td>
              <td class="px-6 py-3 text-lg font-bold text-danger">
                ₱<?php echo number_format($total_amount, 2); ?>
              </td>
              <td class="px-6 py-3 text-sm text-gray-600">
                100%
              </td>
            </tr>
          </tfoot>
          <?php endif; ?>
        </table>
      </div>
    </div>

    <script>
    function exportToExcel() {
      // Get table data
      const table = document.querySelector('table');
      const rows = Array.from(table.querySelectorAll('tr')).map(row => 
        Array.from(row.querySelectorAll('th, td')).map(cell => cell.textContent.trim())
      );
      
      // Convert to CSV
      const csvContent = rows.map(row => 
        row.map(cell => `"${cell.replace(/"/g, '""')}"`).join(',')
      ).join('\n');
      
      // Create download link
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      const url = URL.createObjectURL(blob);
      link.setAttribute('href', url);
      link.setAttribute('download', `expense_report_${new Date().toISOString().slice(0,10)}.csv`);
      link.style.visibility = 'hidden';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }
    </script>
    <?php
}

function renderViewExpensePage($pdo, $expense_id) {
    if (!$expense_id) {
        header("Location: ?page=expenses");
        exit();
    }
    
    // Get expense details
    $stmt = $pdo->prepare("
        SELECT e.*, 
               c.category_name, c.category_code,
               u.firstname as created_by_name, u.lastname as created_by_lastname,
               u2.firstname as updated_by_name, u2.lastname as updated_by_lastname
        FROM expenses e
        LEFT JOIN expense_categories c ON e.category_id = c.category_id
        LEFT JOIN users u ON e.created_by = u.id
        LEFT JOIN users u2 ON e.updated_by = u2.id
        WHERE e.expense_id = ? AND e.status != 'deleted'
    ");
    $stmt->execute([$expense_id]);
    $expense = $stmt->fetch();
    
    if (!$expense) {
        header("Location: ?page=expenses&error=not_found");
        exit();
    }
    
    // Get activity log for this expense
    $stmt = $pdo->prepare("
        SELECT al.*, u.firstname, u.lastname
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        WHERE al.details LIKE ?
        ORDER BY al.created_at DESC
        LIMIT 10
    ");
    $stmt->execute(["%{$expense['expense_number']}%"]);
    $activities = $stmt->fetchAll();
    
    echoHeader('View Expense', 'view-expense', $pdo);
    ?>
    
    <!-- Page Header -->
    <div class="mb-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Expense Details</h1>
          <p class="text-gray-600 mt-1">View complete expense information</p>
        </div>
        <div class="flex items-center gap-2">
          <a href="?page=expenses" class="text-sm text-gray-600 hover:text-accent">
            <i class="fas fa-arrow-left mr-1"></i> Back to Expenses
          </a>
          <a href="?page=edit-expense&id=<?php echo $expense_id; ?>" 
             class="px-4 py-2 bg-warning text-white rounded-lg hover:bg-yellow-600 transition-smooth flex items-center gap-2">
            <i class="fas fa-edit"></i> Edit
          </a>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Left Column - Expense Details -->
      <div class="lg:col-span-2">
        <!-- Expense Header Card -->
        <div class="bg-white rounded-lg shadow mb-6">
          <div class="p-5 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <div>
                <h2 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($expense['expense_number']); ?></h2>
                <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($expense['description']); ?></p>
              </div>
              <div class="flex items-center gap-3">
                <?php
                $status_classes = [
                  'pending' => 'status-pending',
                  'approved' => 'status-approved',
                  'rejected' => 'status-rejected',
                  'paid' => 'status-paid',
                  'cancelled' => 'status-cancelled'
                ];
                $status_class = $status_classes[$expense['status']] ?? 'status-pending';
                ?>
                <span class="px-4 py-2 text-sm font-medium rounded-full <?php echo $status_class; ?>">
                  <?php echo ucfirst($expense['status']); ?>
                </span>
                <span class="text-2xl font-bold text-danger">
                  ₱<?php echo number_format($expense['amount'], 2); ?>
                </span>
              </div>
            </div>
          </div>
          
          <!-- Details Grid -->
          <div class="p-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Basic Information -->
              <div>
                <h3 class="font-semibold text-gray-900 mb-4">Basic Information</h3>
                <dl class="space-y-3">
                  <div>
                    <dt class="text-sm text-gray-500">Expense Date</dt>
                    <dd class="text-sm font-medium text-gray-900">
                      <?php echo date('F d, Y', strtotime($expense['expense_date'])); ?>
                    </dd>
                  </div>
                  <div>
                    <dt class="text-sm text-gray-500">Category</dt>
                    <dd class="text-sm font-medium text-gray-900">
                      <?php echo htmlspecialchars($expense['category_name']); ?>
                      <span class="text-gray-500 ml-2">(<?php echo htmlspecialchars($expense['category_code']); ?>)</span>
                    </dd>
                  </div>
                  <div>
                    <dt class="text-sm text-gray-500">Payment Method</dt>
                    <dd class="text-sm font-medium text-gray-900">
                      <?php echo htmlspecialchars($expense['payment_method']); ?>
                    </dd>
                  </div>
                  <div>
                    <dt class="text-sm text-gray-500">Reference Number</dt>
                    <dd class="text-sm font-medium text-gray-900">
                      <?php echo $expense['reference_number'] ? htmlspecialchars($expense['reference_number']) : '<span class="text-gray-400">Not provided</span>'; ?>
                    </dd>
                  </div>
                </dl>
              </div>
              
              <!-- Assignment & Vendor -->
              <div>
                <h3 class="font-semibold text-gray-900 mb-4">Assignment & Vendor</h3>
                <dl class="space-y-3">
                  <div>
                    <dt class="text-sm text-gray-500">Vendor/Supplier</dt>
                    <dd class="text-sm font-medium text-gray-900">
                      <?php echo $expense['vendor_name'] ? htmlspecialchars($expense['vendor_name']) : '<span class="text-gray-400">Not specified</span>'; ?>
                    </dd>
                  </div>
                  <div>
                    <dt class="text-sm text-gray-500">Department</dt>
                    <dd class="text-sm font-medium text-gray-900">
                      <?php echo $expense['department'] ? htmlspecialchars($expense['department']) : '<span class="text-gray-400">Not specified</span>'; ?>
                    </dd>
                  </div>
                  <div>
                    <dt class="text-sm text-gray-500">Project</dt>
                    <dd class="text-sm font-medium text-gray-900">
                      <?php echo $expense['project_id'] ? 'Project #' . htmlspecialchars($expense['project_id']) : '<span class="text-gray-400">Not assigned</span>'; ?>
                    </dd>
                  </div>
                  <div>
                    <dt class="text-sm text-gray-500">Receipt Attachment</dt>
                    <dd class="text-sm font-medium text-gray-900">
                      <?php if ($expense['receipt_attachment']): ?>
                      <a href="../<?php echo $expense['receipt_attachment']; ?>" target="_blank" 
                         class="text-accent hover:text-green-700 flex items-center gap-2">
                        <i class="fas fa-paperclip"></i> View Receipt
                      </a>
                      <?php else: ?>
                      <span class="text-gray-400">No receipt attached</span>
                      <?php endif; ?>
                    </dd>
                  </div>
                </dl>
              </div>
            </div>
            
            <!-- Notes Section -->
            <?php if ($expense['notes']): ?>
            <div class="mt-6 pt-6 border-t border-gray-200">
              <h3 class="font-semibold text-gray-900 mb-3">Additional Notes</h3>
              <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-sm text-gray-700"><?php echo nl2br(htmlspecialchars($expense['notes'])); ?></p>
              </div>
            </div>
            <?php endif; ?>
            
            <!-- Audit Trail -->
            <div class="mt-6 pt-6 border-t border-gray-200">
              <h3 class="font-semibold text-gray-900 mb-3">Audit Trail</h3>
              <dl class="space-y-2">
                <div class="flex justify-between">
                  <dt class="text-sm text-gray-500">Created By</dt>
                  <dd class="text-sm font-medium text-gray-900">
                    <?php echo htmlspecialchars($expense['created_by_name'] . ' ' . $expense['created_by_lastname']); ?>
                    <span class="text-gray-500 ml-2">on <?php echo date('M d, Y H:i', strtotime($expense['created_at'])); ?></span>
                  </dd>
                </div>
                <?php if ($expense['updated_by']): ?>
                <div class="flex justify-between">
                  <dt class="text-sm text-gray-500">Last Updated By</dt>
                  <dd class="text-sm font-medium text-gray-900">
                    <?php echo htmlspecialchars($expense['updated_by_name'] . ' ' . $expense['updated_by_lastname']); ?>
                    <span class="text-gray-500 ml-2">on <?php echo date('M d, Y H:i', strtotime($expense['updated_at'])); ?></span>
                  </dd>
                </div>
                <?php endif; ?>
              </dl>
            </div>
          </div>
        </div>
        
        <!-- Activity Log -->
        <?php if (!empty($activities)): ?>
        <div class="bg-white rounded-lg shadow">
          <div class="p-5 border-b border-gray-200">
            <h3 class="font-semibold text-gray-900">Recent Activity</h3>
          </div>
          <div class="p-5">
            <div class="space-y-4">
              <?php foreach ($activities as $activity): ?>
              <div class="flex items-start gap-3">
                <div class="flex-shrink-0">
                  <div class="w-8 h-8 rounded-full bg-accent-light flex items-center justify-center">
                    <i class="fas fa-history text-accent text-sm"></i>
                  </div>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm text-gray-900"><?php echo htmlspecialchars($activity['details']); ?></p>
                  <div class="flex items-center gap-2 mt-1">
                    <span class="text-xs text-gray-500">
                      <?php echo htmlspecialchars($activity['firstname'] . ' ' . $activity['lastname']); ?>
                    </span>
                    <span class="text-xs text-gray-400">•</span>
                    <span class="text-xs text-gray-500">
                      <?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?>
                    </span>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>
      
      <!-- Right Column - Actions & Quick Stats -->
      <div class="lg:col-span-1">
        <!-- Action Buttons -->
        <div class="bg-white rounded-lg shadow p-5 mb-6">
          <h3 class="font-semibold text-gray-900 mb-4">Actions</h3>
          <div class="space-y-3">
            <?php if ($expense['status'] == 'pending'): ?>
            <button onclick="updateExpenseStatus(<?php echo $expense_id; ?>, 'approved')" 
                    class="w-full bg-success text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-smooth flex items-center justify-center gap-2">
              <i class="fas fa-check"></i> Approve Expense
            </button>
            <button onclick="updateExpenseStatus(<?php echo $expense_id; ?>, 'rejected')" 
                    class="w-full bg-danger text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-smooth flex items-center justify-center gap-2">
              <i class="fas fa-times"></i> Reject Expense
            </button>
            <?php endif; ?>
            
            <?php if ($expense['status'] == 'approved'): ?>
            <button onclick="updateExpenseStatus(<?php echo $expense_id; ?>, 'paid')" 
                    class="w-full bg-info text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-smooth flex items-center justify-center gap-2">
              <i class="fas fa-money-check"></i> Mark as Paid
            </button>
            <?php endif; ?>
            
            <button onclick="deleteExpense(<?php echo $expense_id; ?>)" 
                    class="w-full bg-danger text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-smooth flex items-center justify-center gap-2">
              <i class="fas fa-trash"></i> Delete Expense
            </button>
            
            <!-- Attach Receipt Button -->
            <button onclick="showAttachReceiptModal()" 
                    class="w-full border border-accent text-accent px-4 py-2 rounded-lg hover:bg-accent-light transition-smooth flex items-center justify-center gap-2">
              <i class="fas fa-paperclip"></i> Attach Receipt
            </button>
            
            <!-- Print Button -->
            <button onclick="window.print()" 
                    class="w-full border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition-smooth flex items-center justify-center gap-2">
              <i class="fas fa-print"></i> Print Details
            </button>
          </div>
        </div>
        
        <!-- Status History -->
        <div class="bg-white rounded-lg shadow p-5">
          <h3 class="font-semibold text-gray-900 mb-4">Status History</h3>
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Created</span>
              <span class="text-xs bg-gray-100 text-gray-800 px-2 py-1 rounded">pending</span>
              <span class="text-xs text-gray-500">
                <?php echo date('M d', strtotime($expense['created_at'])); ?>
              </span>
            </div>
            
            <?php if ($expense['status_updated_at']): ?>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Updated</span>
              <?php
              $status_bg = [
                'approved' => 'bg-green-100 text-green-800',
                'rejected' => 'bg-red-100 text-red-800',
                'paid' => 'bg-blue-100 text-blue-800',
                'cancelled' => 'bg-gray-100 text-gray-800'
              ];
              $status_class = $status_bg[$expense['status']] ?? 'bg-gray-100 text-gray-800';
              ?>
              <span class="text-xs <?php echo $status_class; ?> px-2 py-1 rounded">
                <?php echo $expense['status']; ?>
              </span>
              <span class="text-xs text-gray-500">
                <?php echo date('M d', strtotime($expense['status_updated_at'])); ?>
              </span>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Attach Receipt Modal -->
    <div id="attachReceiptModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 hidden items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full animate-fade-in">
        <div class="p-5 border-b border-gray-200">
          <h3 class="font-semibold text-gray-900 text-lg">Attach Receipt</h3>
        </div>
        
        <form id="receiptForm" class="p-5">
          <input type="hidden" name="action" value="attach_receipt">
          <input type="hidden" name="expense_id" value="<?php echo $expense_id; ?>">
          
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Select Receipt File</label>
            <div class="file-upload-area" onclick="document.getElementById('receiptInput').click()">
              <input type="file" id="receiptInput" name="receipt_file" 
                     accept=".jpg,.jpeg,.png,.pdf,.gif" class="hidden">
              <div class="text-center py-6">
                <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                <p class="text-sm text-gray-600">Click to upload receipt</p>
                <p class="text-xs text-gray-400 mt-1">Supports JPG, PNG, PDF, GIF (Max 5MB)</p>
              </div>
            </div>
            <div id="selectedFileName" class="text-sm text-gray-500 mt-2"></div>
          </div>
          
          <div class="flex justify-end gap-3 pt-4">
            <button type="button" onclick="hideAttachReceiptModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">
              Cancel
            </button>
            <button type="submit" class="px-4 py-2 bg-accent text-white rounded-lg text-sm hover:bg-green-600">
              Upload Receipt
            </button>
          </div>
        </form>
      </div>
    </div>

    <script>
    function showAttachReceiptModal() {
      document.getElementById('attachReceiptModal').classList.remove('hidden');
      document.getElementById('attachReceiptModal').classList.add('flex');
    }

    function hideAttachReceiptModal() {
      document.getElementById('attachReceiptModal').classList.add('hidden');
      document.getElementById('attachReceiptModal').classList.remove('flex');
      document.getElementById('selectedFileName').innerHTML = '';
      document.getElementById('receiptInput').value = '';
    }

    // File selection handler
    document.getElementById('receiptInput').addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (file) {
        document.getElementById('selectedFileName').innerHTML = `
          <div class="flex items-center gap-2 bg-green-50 p-2 rounded">
            <i class="fas fa-file text-green-600"></i>
            <span class="text-green-700">${file.name}</span>
            <span class="text-xs text-gray-500">(${(file.size / 1024).toFixed(1)} KB)</span>
          </div>
        `;
      }
    });

    // Receipt form submission
    document.getElementById('receiptForm').addEventListener('submit', function(e) {
      e.preventDefault();
      
      showLoading();
      
      const formData = new FormData(this);
      
      fetch('', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        hideLoading();
        if (data.success) {
          alert('Receipt attached successfully!');
          hideAttachReceiptModal();
          window.location.reload();
        } else {
          alert('Error: ' + data.error);
        }
      })
      .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
      });
    });

    function updateExpenseStatus(expenseId, status) {
      if (!confirm(`Are you sure you want to mark this expense as ${status}?`)) {
        return;
      }
      
      showLoading();
      
      fetch('', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
          action: 'update_expense_status',
          expense_id: expenseId,
          status: status
        })
      })
      .then(response => response.json())
      .then(data => {
        hideLoading();
        if (data.success) {
          alert('Expense status updated successfully!');
          window.location.reload();
        } else {
          alert('Error: ' + data.error);
        }
      })
      .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
      });
    }

    function deleteExpense(expenseId) {
      if (!confirm('Are you sure you want to delete this expense? This action cannot be undone.')) {
        return;
      }
      
      showLoading();
      
      fetch('', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
          action: 'delete_expense',
          expense_id: expenseId
        })
      })
      .then(response => response.json())
      .then(data => {
        hideLoading();
        if (data.success) {
          alert('Expense deleted successfully!');
          window.location.href = '?page=expenses';
        } else {
          alert('Error: ' + data.error);
        }
      })
      .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
      });
    }
    </script>
    <?php
}

function renderEditExpensePage($pdo, $expense_id) {
    if (!$expense_id) {
        header("Location: ?page=expenses");
        exit();
    }
    
    // Get expense details for editing
    $stmt = $pdo->prepare("
        SELECT e.*, c.category_name
        FROM expenses e
        LEFT JOIN expense_categories c ON e.category_id = c.category_id
        WHERE e.expense_id = ? AND e.status != 'deleted'
    ");
    $stmt->execute([$expense_id]);
    $expense = $stmt->fetch();
    
    if (!$expense) {
        header("Location: ?page=expenses&error=not_found");
        exit();
    }
    
    // Get categories for dropdown
    $stmt = $pdo->prepare("SELECT * FROM expense_categories ORDER BY category_name");
    $stmt->execute();
    $categories = $stmt->fetchAll();
    
    echoHeader('Edit Expense', 'edit-expense', $pdo);
    ?>
    
    <!-- Page Header -->
    <div class="mb-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Edit Expense</h1>
          <p class="text-gray-600 mt-1">Update expense information</p>
        </div>
        <div>
          <a href="?page=view-expense&id=<?php echo $expense_id; ?>" class="text-sm text-gray-600 hover:text-accent">
            <i class="fas fa-eye mr-1"></i> View Expense
          </a>
        </div>
      </div>
    </div>

    <!-- Edit Form -->
    <div class="bg-white rounded-lg shadow">
      <div class="p-5 border-b border-gray-200">
        <h3 class="font-semibold text-gray-900 text-base">Edit Expense Details</h3>
        <p class="text-sm text-gray-500">Update the information for expense #<?php echo htmlspecialchars($expense['expense_number']); ?></p>
      </div>
      
      <form id="editExpenseForm" method="POST" enctype="multipart/form-data" class="p-5">
        <input type="hidden" name="action" value="update_expense">
        <input type="hidden" name="expense_id" value="<?php echo $expense_id; ?>">
        
        <div class="space-y-6">
          <!-- Basic Information -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Expense Date *</label>
              <input type="date" name="expense_date" required 
                     value="<?php echo date('Y-m-d', strtotime($expense['expense_date'])); ?>" 
                     class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
              <select name="category_id" required 
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
                <option value="">Select Category</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['category_id']; ?>" 
                        <?php echo $expense['category_id'] == $cat['category_id'] ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($cat['category_name']); ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <!-- Description -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
            <textarea name="description" required rows="3" 
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent"><?php echo htmlspecialchars($expense['description']); ?></textarea>
          </div>

          <!-- Amount & Payment -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Amount (₱) *</label>
              <input type="number" name="amount" required step="0.01" min="0" 
                     value="<?php echo $expense['amount']; ?>" 
                     class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method *</label>
              <select name="payment_method" required 
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
                <option value="Cash" <?php echo $expense['payment_method'] == 'Cash' ? 'selected' : ''; ?>>Cash</option>
                <option value="Check" <?php echo $expense['payment_method'] == 'Check' ? 'selected' : ''; ?>>Check</option>
                <option value="Bank Transfer" <?php echo $expense['payment_method'] == 'Bank Transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                <option value="Credit Card" <?php echo $expense['payment_method'] == 'Credit Card' ? 'selected' : ''; ?>>Credit Card</option>
                <option value="Online Payment" <?php echo $expense['payment_method'] == 'Online Payment' ? 'selected' : ''; ?>>Online Payment</option>
                <option value="Other" <?php echo $expense['payment_method'] == 'Other' ? 'selected' : ''; ?>>Other</option>
              </select>
            </div>
          </div>

          <!-- Reference & Vendor -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Reference Number</label>
              <input type="text" name="reference_number" 
                     value="<?php echo htmlspecialchars($expense['reference_number'] ?? ''); ?>" 
                     placeholder="Check #, Transaction ID, etc." 
                     class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Vendor/Supplier Name</label>
              <input type="text" name="vendor_name" 
                     value="<?php echo htmlspecialchars($expense['vendor_name'] ?? ''); ?>" 
                     placeholder="Company or individual name" 
                     class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
            </div>
          </div>

          <!-- Department -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
            <select name="department" 
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent">
              <option value="">Not specified</option>
              <option value="Administration" <?php echo $expense['department'] == 'Administration' ? 'selected' : ''; ?>>Administration</option>
              <option value="Finance" <?php echo $expense['department'] == 'Finance' ? 'selected' : ''; ?>>Finance</option>
              <option value="IT" <?php echo $expense['department'] == 'IT' ? 'selected' : ''; ?>>IT</option>
              <option value="Operations" <?php echo $expense['department'] == 'Operations' ? 'selected' : ''; ?>>Operations</option>
              <option value="Marketing" <?php echo $expense['department'] == 'Marketing' ? 'selected' : ''; ?>>Marketing</option>
              <option value="HR" <?php echo $expense['department'] == 'HR' ? 'selected' : ''; ?>>HR</option>
            </select>
          </div>

          <!-- Current Receipt -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Current Receipt</label>
            <?php if ($expense['receipt_attachment']): ?>
            <div class="flex items-center gap-3 bg-gray-50 p-3 rounded-lg">
              <i class="fas fa-paperclip text-gray-400"></i>
              <div>
                <a href="../<?php echo $expense['receipt_attachment']; ?>" target="_blank" 
                   class="text-accent hover:text-green-700 text-sm">
                  View Current Receipt
                </a>
                <p class="text-xs text-gray-500 mt-1">
                  Upload new file to replace existing receipt
                </p>
              </div>
            </div>
            <?php else: ?>
            <p class="text-sm text-gray-500 italic">No receipt currently attached</p>
            <?php endif; ?>
          </div>

          <!-- New Receipt Upload -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Upload New Receipt (Optional)</label>
            <div class="file-upload-area" onclick="document.getElementById('newReceiptFile').click()">
              <input type="file" id="newReceiptFile" name="receipt_file" 
                     accept=".jpg,.jpeg,.png,.pdf,.gif" class="hidden">
              <div class="text-center py-4">
                <i class="fas fa-cloud-upload-alt text-2xl text-gray-400 mb-2"></i>
                <p class="text-sm text-gray-600">Click to upload new receipt</p>
                <p class="text-xs text-gray-400 mt-1">Leave empty to keep current receipt</p>
              </div>
            </div>
            <div id="newFileName" class="text-sm text-gray-500 mt-2"></div>
          </div>

          <!-- Notes -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Additional Notes</label>
            <textarea name="notes" rows="3" 
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent/20 focus:border-accent"><?php echo htmlspecialchars($expense['notes'] ?? ''); ?></textarea>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-gray-200">
          <a href="?page=view-expense&id=<?php echo $expense_id; ?>" 
             class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50 transition-smooth">
            Cancel
          </a>
          <button type="submit" class="px-4 py-2 bg-accent text-white rounded-lg text-sm hover:bg-green-600 transition-smooth flex items-center gap-2">
            <i class="fas fa-save"></i> Save Changes
          </button>
        </div>
      </form>
    </div>

    <script>
    // File upload display
    document.getElementById('newReceiptFile').addEventListener('change', function(e) {
      const fileName = e.target.files[0]?.name;
      const fileDisplay = document.getElementById('newFileName');
      
      if (fileName) {
        fileDisplay.innerHTML = `
          <div class="flex items-center gap-2 bg-green-50 p-2 rounded">
            <i class="fas fa-file text-green-600"></i>
            <span class="text-green-700">${fileName}</span>
          </div>
        `;
      } else {
        fileDisplay.innerHTML = '';
      }
    });

    // Form submission with validation
    document.getElementById('editExpenseForm').addEventListener('submit', function(e) {
      e.preventDefault();
      
      // Validate amount
      const amount = document.querySelector('input[name="amount"]').value;
      if (parseFloat(amount) <= 0) {
        alert('Please enter a valid amount greater than 0.');
        return;
      }
      
      // Show loading
      showLoading();
      
      // Submit form
      const formData = new FormData(this);
      
      fetch('', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        hideLoading();
        if (data.success) {
          alert('Expense updated successfully!');
          window.location.href = '?page=view-expense&id=<?php echo $expense_id; ?>';
        } else {
          alert('Error: ' + data.error);
        }
      })
      .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
      });
    });
    </script>
    <?php
}
?>
</body>
</html>