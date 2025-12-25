<!-- SIDEBAR -->
<aside class="sidebar fixed top-0 left-0 w-64 h-screen bg-blue-900 text-white p-6 shadow-lg flex flex-col">
    <!-- Logo / Brand -->
    <div class="mb-8 flex items-center gap-2">
        <div class="bg-yellow-400 w-10 h-10 rounded-full flex items-center justify-center text-blue-900 font-bold text-lg">
            BCP
        </div>
        <h1 class="text-xl font-bold">FMS Admin</h1>
    </div>

    <!-- Quick Links -->
    <nav class="flex-1 overflow-y-auto">
        <h2 class="text-sm font-semibold uppercase text-blue-300 mb-4 tracking-wide">Dashboard</h2>
        <ul class="space-y-3">
            <li>
                <a href="dashboard.php" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-yellow-400 hover:text-blue-900 transition">
                    <span class="material-icons text-lg">dashboard</span>
                    Dashboard Home
                </a>
            </li>
            <li>
                <a href="financial_requests.php" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-yellow-400 hover:text-blue-900 transition">
                    <span class="material-icons text-lg">request_quote</span>
                    Financial Requests
                </a>
            </li>
            <li>
                <a href="anomaly_reports.php" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-yellow-400 hover:text-blue-900 transition">
                    <span class="material-icons text-lg">report_problem</span>
                    Anomaly Reports
                </a>
            </li>
            <li>
                <a href="user_management.php" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-yellow-400 hover:text-blue-900 transition">
                    <span class="material-icons text-lg">people</span>
                    User Management
                </a>
            </li>
            <li>
                <a href="budget_analytics.php" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-yellow-400 hover:text-blue-900 transition">
                    <span class="material-icons text-lg">analytics</span>
                    Budget Analytics
                </a>
            </li>
            <li>
                <a href="settings.php" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-yellow-400 hover:text-blue-900 transition">
                    <span class="material-icons text-lg">settings</span>
                    Settings
                </a>
            </li>
        </ul>
    </nav>

    <!-- Logout -->
    <div class="mt-10">
        <a href="login.php?logout=true" class="flex items-center gap-3 px-3 py-2 rounded-lg bg-red-600 hover:bg-red-500 transition">
            <span class="material-icons text-lg">logout</span>
            Logout
        </a>
    </div>
</aside>

<!-- MAIN CONTENT -->
<main class="ml-64 p-8">
    <!-- Your dashboard content goes here -->
</main>

<!-- Include Google Material Icons -->
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
