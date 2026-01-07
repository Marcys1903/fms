<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// Check if user is authorized (Financial roles and above)
try {
    $stmt = $pdo->prepare("SELECT role, level FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        header("Location: ../login.php?error=unauthorized");
        exit();
    }
    
    // Allowed roles: Super Admin, Financial Director, Treasurer, Cashier, Budget Officer, Accountant
    $allowed_roles = ['Super Administrator', 'Financial Director', 'Treasurer', 'Cashier', 'Budget Officer', 'Accounting Officer'];
    $allowed_level = 6; // End-user level and above
    
    if (!in_array($user['role'], $allowed_roles) || $user['level'] > $allowed_level) {
        header("Location: ../login.php?error=unauthorized");
        exit();
    }
} catch (Exception $e) {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// Get filter parameters
$filterDate = $_GET['date'] ?? date('Y-m-d');
$filterDepartment = $_GET['department'] ?? '';
$filterCategory = $_GET['category'] ?? '';

// Calculate comparison periods
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$lastWeek = date('Y-m-d', strtotime('-7 days'));
$lastMonth = date('Y-m-d', strtotime('-30 days'));

// Initialize data arrays
$dailySummary = [];
$recentTransactions = [];
$revenueChanges = [];
$topReasons = [];

try {
    // 1. TODAY'S REVENUE SUMMARY
    $todayQuery = "
        SELECT 
            COUNT(*) as total_transactions,
            SUM(amount) as total_amount,
            AVG(amount) as avg_amount,
            MAX(amount) as max_amount,
            MIN(amount) as min_amount,
            COUNT(DISTINCT payer_name) as unique_payers
        FROM revenue_transactions 
        WHERE DATE(transaction_date) = ?";
    
    $params = [$today];
    if ($filterDepartment && $filterDepartment != 'all') {
        $todayQuery .= " AND department_id = ?";
        $params[] = $filterDepartment;
    }
    if ($filterCategory && $filterCategory != 'all') {
        $todayQuery .= " AND category = ?";
        $params[] = $filterCategory;
    }
    
    $stmt = $pdo->prepare($todayQuery);
    $stmt->execute($params);
    $dailySummary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 2. YESTERDAY'S REVENUE FOR COMPARISON
    $yesterdayQuery = "
        SELECT 
            SUM(amount) as total_amount,
            COUNT(*) as total_transactions
        FROM revenue_transactions 
        WHERE DATE(transaction_date) = ?";
    
    $yParams = [$yesterday];
    if ($filterDepartment && $filterDepartment != 'all') {
        $yesterdayQuery .= " AND department_id = ?";
        $yParams[] = $filterDepartment;
    }
    if ($filterCategory && $filterCategory != 'all') {
        $yesterdayQuery .= " AND category = ?";
        $yParams[] = $filterCategory;
    }
    
    $stmt = $pdo->prepare($yesterdayQuery);
    $stmt->execute($yParams);
    $yesterdaySummary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 3. LAST WEEK COMPARISON
    $lastWeekQuery = "
        SELECT 
            SUM(amount) as total_amount,
            COUNT(*) as total_transactions
        FROM revenue_transactions 
        WHERE DATE(transaction_date) = ?";
    
    $lwParams = [$lastWeek];
    if ($filterDepartment && $filterDepartment != 'all') {
        $lastWeekQuery .= " AND department_id = ?";
        $lwParams[] = $filterDepartment;
    }
    if ($filterCategory && $filterCategory != 'all') {
        $lastWeekQuery .= " AND category = ?";
        $lwParams[] = $filterCategory;
    }
    
    $stmt = $pdo->prepare($lastWeekQuery);
    $stmt->execute($lwParams);
    $lastWeekSummary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 4. LAST MONTH COMPARISON (same day last month)
    $lastMonthDate = date('Y-m-d', strtotime('-1 month'));
    $lastMonthQuery = "
        SELECT 
            SUM(amount) as total_amount,
            COUNT(*) as total_transactions
        FROM revenue_transactions 
        WHERE DATE(transaction_date) = ?";
    
    $lmParams = [$lastMonthDate];
    if ($filterDepartment && $filterDepartment != 'all') {
        $lastMonthQuery .= " AND department_id = ?";
        $lmParams[] = $filterDepartment;
    }
    if ($filterCategory && $filterCategory != 'all') {
        $lastMonthQuery .= " AND category = ?";
        $lmParams[] = $filterCategory;
    }
    
    $stmt = $pdo->prepare($lastMonthQuery);
    $stmt->execute($lmParams);
    $lastMonthSummary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 5. REVENUE CHANGES ANALYSIS
    $revenueChanges = [
        'vs_yesterday' => [
            'amount' => ($dailySummary['total_amount'] ?? 0) - ($yesterdaySummary['total_amount'] ?? 0),
            'percentage' => ($yesterdaySummary['total_amount'] ?? 0) > 0 ? 
                ((($dailySummary['total_amount'] ?? 0) - ($yesterdaySummary['total_amount'] ?? 0)) / ($yesterdaySummary['total_amount'] ?? 0) * 100) : 0,
            'transactions' => ($dailySummary['total_transactions'] ?? 0) - ($yesterdaySummary['total_transactions'] ?? 0)
        ],
        'vs_last_week' => [
            'amount' => ($dailySummary['total_amount'] ?? 0) - ($lastWeekSummary['total_amount'] ?? 0),
            'percentage' => ($lastWeekSummary['total_amount'] ?? 0) > 0 ? 
                ((($dailySummary['total_amount'] ?? 0) - ($lastWeekSummary['total_amount'] ?? 0)) / ($lastWeekSummary['total_amount'] ?? 0) * 100) : 0,
            'transactions' => ($dailySummary['total_transactions'] ?? 0) - ($lastWeekSummary['total_transactions'] ?? 0)
        ],
        'vs_last_month' => [
            'amount' => ($dailySummary['total_amount'] ?? 0) - ($lastMonthSummary['total_amount'] ?? 0),
            'percentage' => ($lastMonthSummary['total_amount'] ?? 0) > 0 ? 
                ((($dailySummary['total_amount'] ?? 0) - ($lastMonthSummary['total_amount'] ?? 0)) / ($lastMonthSummary['total_amount'] ?? 0) * 100) : 0,
            'transactions' => ($dailySummary['total_transactions'] ?? 0) - ($lastMonthSummary['total_transactions'] ?? 0)
        ]
    ];
    
    // 6. TOP REASONS FOR CHANGES (Analyze transaction patterns)
    $reasonsQuery = "
        SELECT 
            category,
            COUNT(*) as transaction_count,
            SUM(amount) as total_amount,
            GROUP_CONCAT(DISTINCT payer_name ORDER BY amount DESC LIMIT 3) as top_payers
        FROM revenue_transactions 
        WHERE DATE(transaction_date) = ?
        GROUP BY category
        ORDER BY total_amount DESC";
    
    $reasonParams = [$today];
    if ($filterDepartment && $filterDepartment != 'all') {
        $reasonsQuery .= " AND department_id = ?";
        $reasonParams[] = $filterDepartment;
    }
    
    $stmt = $pdo->prepare($reasonsQuery);
    $stmt->execute($reasonParams);
    $categoryAnalysis = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 7. RECENT TRANSACTIONS (Last 20 for monitoring)
    $recentQuery = "
        SELECT rt.*, 
               d.name as department_name,
               CONCAT(u.firstname, ' ', u.lastname) as recorded_by_name,
               TIME(rt.created_at) as transaction_time
        FROM revenue_transactions rt
        LEFT JOIN departments d ON rt.department_id = d.id
        LEFT JOIN users u ON rt.recorded_by = u.id
        WHERE DATE(rt.transaction_date) = ?
        ORDER BY rt.created_at DESC
        LIMIT 20";
    
    $recentParams = [$today];
    if ($filterDepartment && $filterDepartment != 'all') {
        $recentQuery .= " AND rt.department_id = ?";
        $recentParams[] = $filterDepartment;
    }
    if ($filterCategory && $filterCategory != 'all') {
        $recentQuery .= " AND rt.category = ?";
        $recentParams[] = $filterCategory;
    }
    
    $stmt = $pdo->prepare($recentQuery);
    $stmt->execute($recentParams);
    $recentTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 8. TOP REASONS ANALYSIS
    $topReasons = [];
    if (!empty($categoryAnalysis)) {
        foreach ($categoryAnalysis as $cat) {
            $reason = "{$cat['transaction_count']} " . strtolower($cat['category']) . 
                     " transactions totaling ₱" . number_format($cat['total_amount'], 2);
            
            if ($cat['top_payers']) {
                $payers = explode(',', $cat['top_payers']);
                $reason .= " (Top payers: " . implode(', ', array_slice($payers, 0, 2)) . ")";
            }
            
            $topReasons[] = $reason;
        }
    }
    
    // 9. MONITORING ALERTS
    $alertsQuery = "
        SELECT 
            'High Value Transaction' as alert_type,
            'New large transaction recorded' as alert_message,
            rt.id as transaction_id,
            rt.description,
            rt.amount,
            rt.created_at,
            'info' as alert_level
        FROM revenue_transactions rt
        WHERE rt.amount > 50000 AND DATE(rt.created_at) = ?
        
        UNION ALL
        
        SELECT 
            'Unusual Activity' as alert_type,
            CONCAT('Unusually ', 
                CASE WHEN COUNT(*) > 10 THEN 'high' ELSE 'low' END,
                ' transaction volume (', COUNT(*), ' transactions)'
            ) as alert_message,
            NULL as transaction_id,
            'Transaction Volume' as description,
            SUM(rt.amount) as amount,
            CURDATE() as created_at,
            CASE WHEN COUNT(*) > 10 THEN 'warning' ELSE 'info' END as alert_level
        FROM revenue_transactions rt
        WHERE DATE(rt.transaction_date) = ?
        HAVING COUNT(*) > 10 OR COUNT(*) < 3
        
        UNION ALL
        
        SELECT 
            'Category Alert' as alert_type,
            CONCAT(category, ' revenue significantly ', 
                CASE WHEN SUM(amount) > 10000 THEN 'high' ELSE 'low' END
            ) as alert_message,
            NULL as transaction_id,
            category as description,
            SUM(amount) as amount,
            CURDATE() as created_at,
            CASE WHEN SUM(amount) > 10000 THEN 'success' ELSE 'warning' END as alert_level
        FROM revenue_transactions rt
        WHERE DATE(rt.transaction_date) = ?
        GROUP BY category
        HAVING SUM(amount) > 10000 OR SUM(amount) < 1000
        
        ORDER BY created_at DESC
        LIMIT 10";
    
    $stmt = $pdo->prepare($alertsQuery);
    $stmt->execute([$today, $today, $today]);
    $monitoringAlerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch departments for filter dropdown
    $stmt = $pdo->query("SELECT id, name, abbreviation FROM departments ORDER BY name");
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch categories
    $categoriesStmt = $pdo->query("SELECT category_code, category_name FROM revenue_categories WHERE is_active = 1 ORDER BY category_name");
    $categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Error fetching data: " . $e->getMessage();
    error_log($error);
}

// Session variables for display
$firstname = $_SESSION['firstname'] ?? '';
$lastname = $_SESSION['lastname'] ?? '';
$role = $_SESSION['role'];

// Build categories array
$revenueCategories = [];
foreach ($categories as $cat) {
    $revenueCategories[$cat['category_code']] = $cat['category_name'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Revenue Monitoring | Financial Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/bcpnobg.png">
    
    <!-- CDN Links -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="//unpkg.com/alpinejs" defer></script>

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
                    },
                    fontFamily: {
                        'inter': ['Inter', 'system-ui', 'sans-serif']
                    }
                }
            }
        }
    </script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        
        .positive { color: #10B981; }
        .positive-bg { background-color: #DCFCE7; }
        .negative { color: #EF4444; }
        .negative-bg { background-color: #FEE2E2; }
        
        .live-indicator {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .monitoring-row {
            transition: all 0.3s ease;
        }
        
        .monitoring-row:hover {
            background-color: #f9fafb;
        }
        
        .form-control {
            display: block;
            width: 100%;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            line-height: 1.25rem;
            color: #374151;
            background-color: #ffffff;
            background-clip: padding-box;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        
        .form-control:focus {
            border-color: #2563eb;
            outline: 0;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.25rem;
            font-weight: 500;
            color: #374151;
            font-size: 0.875rem;
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
                <span class="font-bold text-gray-900 text-lg">Revenue Monitoring System</span>
                <span class="ml-2 text-xs bg-accent text-white px-2 py-0.5 rounded-full font-semibold">REAL-TIME</span>
            </div>
        </div>
    </div>
    
    <div class="flex items-center gap-4">
        <!-- Real-time Indicator -->
        <div class="flex items-center gap-2">
            <div class="relative">
                <div class="w-2 h-2 bg-green-500 rounded-full live-indicator"></div>
                <div class="w-2 h-2 bg-green-500 rounded-full absolute top-0 left-0 animate-ping"></div>
            </div>
            <span class="text-xs text-gray-600" id="lastUpdateTime">Updating...</span>
        </div>
        
        <!-- User Profile -->
        <div class="flex items-center gap-3 border-l border-gray-200 pl-4">
            <div class="text-right">
                <p class="font-medium text-gray-900 text-sm"><?php echo htmlspecialchars($firstname . ' ' . $lastname); ?></p>
                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($role); ?></p>
            </div>
            <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo urlencode($firstname); ?>" 
                 class="h-9 w-9 rounded-full border-2 border-accent">
        </div>
    </div>
</header>

<!-- Main Layout -->
<div class="flex pt-16 h-full">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto p-6">
        
        <!-- Monitoring Header -->
        <div class="mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Revenue Monitoring</h1>
                    <p class="text-gray-600 mt-1">Real-time tracking of revenue changes with +/- analysis</p>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Date Selector -->
                    <div>
                        <label class="form-label">Monitoring Date</label>
                        <input type="date" id="monitoringDate" value="<?php echo $today; ?>" class="form-control">
                    </div>
                    
                    <!-- Refresh Button -->
                    <div class="pt-6">
                        <button onclick="refreshMonitoring()" class="flex items-center gap-2 px-4 py-2 bg-accent text-white rounded-lg hover:bg-blue-700 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Refresh
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- FILTER FORM -->
        <form method="GET" action="" class="bg-white rounded-lg shadow p-4 mb-6">
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <!-- Department Filter -->
                <div>
                    <label class="form-label">Department</label>
                    <select name="department" class="form-control">
                        <option value="all">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo $dept['id']; ?>" <?php echo $filterDepartment == $dept['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Category Filter -->
                <div>
                    <label class="form-label">Category</label>
                    <select name="category" class="form-control">
                        <option value="all">All Categories</option>
                        <?php foreach ($revenueCategories as $value => $label): ?>
                        <option value="<?php echo $value; ?>" <?php echo $filterCategory == $value ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Date Filter -->
                <div>
                    <label class="form-label">Date</label>
                    <input type="date" name="date" value="<?php echo $filterDate; ?>" class="form-control">
                </div>
                
                <!-- Submit Button -->
                <div class="pt-6">
                    <button type="submit" class="w-full bg-accent hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg">
                        Apply Filters
                    </button>
                </div>
            </div>
        </form>

        <!-- TODAY'S SUMMARY TABLE -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Today's Revenue Summary</h2>
                <p class="text-sm text-gray-500"><?php echo date('F d, Y', strtotime($today)); ?></p>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Metric</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Today</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Yesterday</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Change</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Analysis</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <!-- Total Revenue Row -->
                        <tr class="monitoring-row">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span class="text-sm font-medium text-gray-900">Total Revenue</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-lg font-bold text-gray-900">
                                    ₱<?php echo number_format($dailySummary['total_amount'] ?? 0, 2); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-600">
                                    ₱<?php echo number_format($yesterdaySummary['total_amount'] ?? 0, 2); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if (($revenueChanges['vs_yesterday']['amount'] ?? 0) >= 0): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-green-800" fill="currentColor" viewBox="0 0 8 8">
                                            <path d="M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z"/>
                                        </svg>
                                        +₱<?php echo number_format(abs($revenueChanges['vs_yesterday']['amount'] ?? 0), 2); ?>
                                        (<?php echo number_format(abs($revenueChanges['vs_yesterday']['percentage'] ?? 0), 1); ?>%)
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-red-800" fill="currentColor" viewBox="0 0 8 8">
                                            <path d="M4 4V1.5l-2 2L.5 3 3 .5 4 1.5V-.5h1V1.5L7.5.5 8 3l-1.5 1.5-2-2V4H4z"/>
                                        </svg>
                                        -₱<?php echo number_format(abs($revenueChanges['vs_yesterday']['amount'] ?? 0), 2); ?>
                                        (<?php echo number_format(abs($revenueChanges['vs_yesterday']['percentage'] ?? 0), 1); ?>%)
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs text-gray-600">
                                    <?php echo $dailySummary['total_transactions'] ?? 0; ?> transactions 
                                    (<?php echo $dailySummary['unique_payers'] ?? 0; ?> unique payers)
                                </span>
                            </td>
                        </tr>
                        
                        <!-- Transaction Count Row -->
                        <tr class="monitoring-row">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-purple-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span class="text-sm font-medium text-gray-900">Transaction Count</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-lg font-bold text-gray-900">
                                    <?php echo $dailySummary['total_transactions'] ?? 0; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-600">
                                    <?php echo $yesterdaySummary['total_transactions'] ?? 0; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if (($revenueChanges['vs_yesterday']['transactions'] ?? 0) >= 0): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-green-800" fill="currentColor" viewBox="0 0 8 8">
                                            <path d="M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z"/>
                                        </svg>
                                        +<?php echo abs($revenueChanges['vs_yesterday']['transactions'] ?? 0); ?> transactions
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-red-800" fill="currentColor" viewBox="0 0 8 8">
                                            <path d="M4 4V1.5l-2 2L.5 3 3 .5 4 1.5V-.5h1V1.5L7.5.5 8 3l-1.5 1.5-2-2V4H4z"/>
                                        </svg>
                                        -<?php echo abs($revenueChanges['vs_yesterday']['transactions'] ?? 0); ?> transactions
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs text-gray-600">
                                    <?php echo number_format(($dailySummary['total_transactions'] ?? 0) / 24, 1); ?> avg per hour
                                </span>
                            </td>
                        </tr>
                        
                        <!-- Average Transaction Row -->
                        <tr class="monitoring-row">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                    <span class="text-sm font-medium text-gray-900">Average Transaction</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-lg font-bold text-gray-900">
                                    ₱<?php echo number_format($dailySummary['avg_amount'] ?? 0, 2); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-600">
                                    ₱<?php echo number_format(($yesterdaySummary['total_amount'] ?? 0) / (($yesterdaySummary['total_transactions'] ?? 1) ?: 1), 2); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php 
                                    $todayAvg = $dailySummary['avg_amount'] ?? 0;
                                    $yesterdayAvg = ($yesterdaySummary['total_amount'] ?? 0) / (($yesterdaySummary['total_transactions'] ?? 1) ?: 1);
                                    $avgChange = $todayAvg - $yesterdayAvg;
                                ?>
                                <?php if ($avgChange >= 0): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-green-800" fill="currentColor" viewBox="0 0 8 8">
                                            <path d="M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z"/>
                                        </svg>
                                        +₱<?php echo number_format(abs($avgChange), 2); ?> per transaction
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-red-800" fill="currentColor" viewBox="0 0 8 8">
                                            <path d="M4 4V1.5l-2 2L.5 3 3 .5 4 1.5V-.5h1V1.5L7.5.5 8 3l-1.5 1.5-2-2V4H4z"/>
                                        </svg>
                                        -₱<?php echo number_format(abs($avgChange), 2); ?> per transaction
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs text-gray-600">
                                    Range: ₱<?php echo number_format($dailySummary['min_amount'] ?? 0, 2); ?> - 
                                    ₱<?php echo number_format($dailySummary['max_amount'] ?? 0, 2); ?>
                                </span>
                            </td>
                        </tr>
                        
                        <!-- Unique Payers Row -->
                        <tr class="monitoring-row">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-orange-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    <span class="text-sm font-medium text-gray-900">Unique Payers</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-lg font-bold text-gray-900">
                                    <?php echo $dailySummary['unique_payers'] ?? 0; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-600">
                                    N/A
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-xs text-gray-500">New metric</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs text-gray-600">
                                    <?php echo ($dailySummary['total_transactions'] ?? 0) > 0 ? 
                                        round(($dailySummary['total_transactions'] ?? 0) / (($dailySummary['unique_payers'] ?: 1) ?: 1), 1) : 0; ?> 
                                    transactions per payer
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- REVENUE COMPARISON TABLE -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Revenue Comparison Analysis</h2>
                <p class="text-sm text-gray-500">Performance compared to previous periods</p>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comparison Period</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount Change</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Percentage Change</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transaction Change</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Analysis</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <!-- Yesterday Comparison -->
                        <tr class="monitoring-row">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 rounded-full bg-blue-500 mr-2"></div>
                                    <span class="text-sm font-medium text-gray-900">vs Yesterday</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if (($revenueChanges['vs_yesterday']['amount'] ?? 0) >= 0): ?>
                                    <span class="text-lg font-bold text-green-600">
                                        +₱<?php echo number_format(abs($revenueChanges['vs_yesterday']['amount'] ?? 0), 2); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-lg font-bold text-red-600">
                                        -₱<?php echo number_format(abs($revenueChanges['vs_yesterday']['amount'] ?? 0), 2); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if (($revenueChanges['vs_yesterday']['percentage'] ?? 0) >= 0): ?>
                                    <span class="text-sm font-medium text-green-600">
                                        +<?php echo number_format(abs($revenueChanges['vs_yesterday']['percentage'] ?? 0), 1); ?>%
                                    </span>
                                <?php else: ?>
                                    <span class="text-sm font-medium text-red-600">
                                        -<?php echo number_format(abs($revenueChanges['vs_yesterday']['percentage'] ?? 0), 1); ?>%
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if (($revenueChanges['vs_yesterday']['transactions'] ?? 0) >= 0): ?>
                                    <span class="text-sm text-green-600">
                                        +<?php echo abs($revenueChanges['vs_yesterday']['transactions'] ?? 0); ?> transactions
                                    </span>
                                <?php else: ?>
                                    <span class="text-sm text-red-600">
                                        -<?php echo abs($revenueChanges['vs_yesterday']['transactions'] ?? 0); ?> transactions
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs text-gray-600">
                                    <?php if (($revenueChanges['vs_yesterday']['amount'] ?? 0) >= 0): ?>
                                        ↑ Revenue increased from yesterday
                                    <?php else: ?>
                                        ↓ Revenue decreased from yesterday
                                    <?php endif; ?>
                                </span>
                            </td>
                        </tr>
                        
                        <!-- Last Week Comparison -->
                        <tr class="monitoring-row">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 rounded-full bg-purple-500 mr-2"></div>
                                    <span class="text-sm font-medium text-gray-900">vs Last Week</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if (($revenueChanges['vs_last_week']['amount'] ?? 0) >= 0): ?>
                                    <span class="text-lg font-bold text-green-600">
                                        +₱<?php echo number_format(abs($revenueChanges['vs_last_week']['amount'] ?? 0), 2); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-lg font-bold text-red-600">
                                        -₱<?php echo number_format(abs($revenueChanges['vs_last_week']['amount'] ?? 0), 2); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if (($revenueChanges['vs_last_week']['percentage'] ?? 0) >= 0): ?>
                                    <span class="text-sm font-medium text-green-600">
                                        +<?php echo number_format(abs($revenueChanges['vs_last_week']['percentage'] ?? 0), 1); ?>%
                                    </span>
                                <?php else: ?>
                                    <span class="text-sm font-medium text-red-600">
                                        -<?php echo number_format(abs($revenueChanges['vs_last_week']['percentage'] ?? 0), 1); ?>%
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if (($revenueChanges['vs_last_week']['transactions'] ?? 0) >= 0): ?>
                                    <span class="text-sm text-green-600">
                                        +<?php echo abs($revenueChanges['vs_last_week']['transactions'] ?? 0); ?> transactions
                                    </span>
                                <?php else: ?>
                                    <span class="text-sm text-red-600">
                                        -<?php echo abs($revenueChanges['vs_last_week']['transactions'] ?? 0); ?> transactions
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs text-gray-600">
                                    <?php if (($revenueChanges['vs_last_week']['amount'] ?? 0) >= 0): ?>
                                        ↑ Performing better than last week
                                    <?php else: ?>
                                        ↓ Performing worse than last week
                                    <?php endif; ?>
                                </span>
                            </td>
                        </tr>
                        
                        <!-- Last Month Comparison -->
                        <tr class="monitoring-row">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 rounded-full bg-green-500 mr-2"></div>
                                    <span class="text-sm font-medium text-gray-900">vs Last Month</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if (($revenueChanges['vs_last_month']['amount'] ?? 0) >= 0): ?>
                                    <span class="text-lg font-bold text-green-600">
                                        +₱<?php echo number_format(abs($revenueChanges['vs_last_month']['amount'] ?? 0), 2); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-lg font-bold text-red-600">
                                        -₱<?php echo number_format(abs($revenueChanges['vs_last_month']['amount'] ?? 0), 2); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if (($revenueChanges['vs_last_month']['percentage'] ?? 0) >= 0): ?>
                                    <span class="text-sm font-medium text-green-600">
                                        +<?php echo number_format(abs($revenueChanges['vs_last_month']['percentage'] ?? 0), 1); ?>%
                                    </span>
                                <?php else: ?>
                                    <span class="text-sm font-medium text-red-600">
                                        -<?php echo number_format(abs($revenueChanges['vs_last_month']['percentage'] ?? 0), 1); ?>%
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if (($revenueChanges['vs_last_month']['transactions'] ?? 0) >= 0): ?>
                                    <span class="text-sm text-green-600">
                                        +<?php echo abs($revenueChanges['vs_last_month']['transactions'] ?? 0); ?> transactions
                                    </span>
                                <?php else: ?>
                                    <span class="text-sm text-red-600">
                                        -<?php echo abs($revenueChanges['vs_last_month']['transactions'] ?? 0); ?> transactions
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs text-gray-600">
                                    <?php if (($revenueChanges['vs_last_month']['amount'] ?? 0) >= 0): ?>
                                        ↑ Monthly growth trend positive
                                    <?php else: ?>
                                        ↓ Monthly growth trend negative
                                    <?php endif; ?>
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TOP REASONS TABLE -->
        <?php if (!empty($topReasons)): ?>
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Top Reasons for Revenue Changes</h2>
                <p class="text-sm text-gray-500">Key factors driving today's performance</p>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason / Factor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Impact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($topReasons as $index => $reason): ?>
                        <tr class="monitoring-row">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full 
                                    <?php echo ($revenueChanges['vs_yesterday']['amount'] ?? 0) >= 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo $index + 1; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm text-gray-900"><?php echo $reason; ?></p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if (($revenueChanges['vs_yesterday']['amount'] ?? 0) >= 0): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-green-800" fill="currentColor" viewBox="0 0 8 8">
                                            <path d="M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z"/>
                                        </svg>
                                        Positive Impact
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-red-800" fill="currentColor" viewBox="0 0 8 8">
                                            <path d="M4 4V1.5l-2 2L.5 3 3 .5 4 1.5V-.5h1V1.5L7.5.5 8 3l-1.5 1.5-2-2V4H4z"/>
                                        </svg>
                                        Negative Impact
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs text-gray-600">
                                    <?php echo ($revenueChanges['vs_yesterday']['amount'] ?? 0) >= 0 ? 
                                        'Contributing to revenue increase' : 'Contributing to revenue decrease'; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- RECENT TRANSACTIONS TABLE -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Recent Transactions</h2>
                    <p class="text-sm text-gray-500">Last 20 transactions (updated in real-time)</p>
                </div>
                <span class="text-xs px-2 py-1 bg-blue-100 text-blue-800 rounded-full">
                    <?php echo count($recentTransactions); ?> transactions
                </span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Impact</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (!empty($recentTransactions)): ?>
                            <?php foreach ($recentTransactions as $transaction): ?>
                                <tr class="monitoring-row">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $transaction['transaction_time'] ?? ''; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php echo htmlspecialchars($transaction['description'] ?? ''); ?>
                                        <?php if (!empty($transaction['reference_number'])): ?>
                                            <br><span class="text-xs text-gray-500">Ref: <?php echo $transaction['reference_number']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-xs px-2 py-1 bg-gray-100 text-gray-800 rounded-full">
                                            <?php echo $revenueCategories[$transaction['category']] ?? ucfirst($transaction['category'] ?? ''); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($transaction['payer_name'] ?? 'N/A'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-bold text-green-600">
                                            ₱<?php echo number_format($transaction['amount'] ?? 0, 2); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php 
                                            $amount = $transaction['amount'] ?? 0;
                                            if ($amount >= 10000) {
                                                echo '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">High</span>';
                                            } elseif ($amount >= 5000) {
                                                echo '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Medium</span>';
                                            } else {
                                                echo '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Low</span>';
                                            }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    No transactions recorded for today.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MONITORING ALERTS TABLE -->
        <?php if (!empty($monitoringAlerts)): ?>
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Monitoring Alerts</h2>
                <span class="text-xs px-2 py-1 bg-red-100 text-red-800 rounded-full">
                    <?php echo count($monitoringAlerts); ?> alerts
                </span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Severity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($monitoringAlerts as $alert): ?>
                            <tr class="monitoring-row">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-900">
                                        <?php echo $alert['alert_type'] ?? ''; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-gray-900"><?php echo $alert['alert_message'] ?? ''; ?></p>
                                    <?php if (!empty($alert['amount'])): ?>
                                        <p class="text-xs text-gray-500 mt-1">
                                            Amount: ₱<?php echo number_format($alert['amount'], 2); ?>
                                        </p>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php 
                                        $severityClass = ($alert['alert_level'] ?? '') == 'danger' ? 'bg-red-100 text-red-800' : 
                                                        (($alert['alert_level'] ?? '') == 'warning' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800');
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $severityClass; ?>">
                                        <?php echo ucfirst($alert['alert_level'] ?? 'info'); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('H:i', strtotime($alert['created_at'] ?? '')); ?>
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

<script>
// Auto-refresh monitoring data every 30 seconds
let refreshInterval;
let lastUpdate = new Date();

function startAutoRefresh() {
    refreshInterval = setInterval(() => {
        if (!document.hidden) {
            updateLastUpdateTime();
            // Check if we should refresh (every 30 seconds)
            const now = new Date();
            const secondsSinceUpdate = Math.floor((now - lastUpdate) / 1000);
            
            if (secondsSinceUpdate >= 30) {
                refreshMonitoring();
            }
        }
    }, 5000); // Check every 5 seconds
}

function updateLastUpdateTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', second:'2-digit'});
    document.getElementById('lastUpdateTime').textContent = `Updated: ${timeString}`;
}

function refreshMonitoring() {
    const date = document.getElementById('monitoringDate').value;
    const department = document.querySelector('select[name="department"]').value;
    const category = document.querySelector('select[name="category"]').value;
    
    // Show loading state
    const refreshBtn = document.querySelector('button[onclick="refreshMonitoring()"]');
    const originalText = refreshBtn.innerHTML;
    refreshBtn.innerHTML = '<span class="animate-spin">↻</span> Refreshing...';
    refreshBtn.disabled = true;
    
    // Build URL with filters
    let url = `revenuetracking.php?date=${date}`;
    if (department && department !== 'all') url += `&department=${department}`;
    if (category && category !== 'all') url += `&category=${category}`;
    
    // Reload the page with filters
    window.location.href = url;
    
    lastUpdate = new Date();
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    startAutoRefresh();
    updateLastUpdateTime();
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});
</script>
</body>
</html>