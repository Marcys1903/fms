<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | BCP FMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        /* Scrollbar for sidebar */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        .sidebar::-webkit-scrollbar-thumb {
            background-color: rgba(255,255,255,0.3);
            border-radius: 3px;
        }
    </style>
</head>
<body class="bg-gray-100">

<div class="flex h-[calc(100vh-64px)] overflow-hidden">

    <!-- SIDEBAR -->
    <?php include 'sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="flex-1 p-8 overflow-auto">
        
        <!-- WELCOME -->
        <div class="mb-8">
            <h1 class="text-3xl md:text-4xl font-bold text-blue-900 mb-2">Welcome, Admin!</h1>
            <p class="text-gray-700 text-sm md:text-base">Here’s an overview of the financial system and requests.</p>
        </div>

        <!-- DASHBOARD CARDS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <div class="bg-white rounded-2xl p-6 shadow-md hover:shadow-xl transition duration-300 border-l-4 border-blue-500">
                <h3 class="text-gray-500 font-semibold mb-2">Total Students</h3>
                <p class="text-2xl font-bold text-blue-800">1,250</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-md hover:shadow-xl transition duration-300 border-l-4 border-yellow-400">
                <h3 class="text-gray-500 font-semibold mb-2">Pending Requests</h3>
                <p class="text-2xl font-bold text-blue-800">32</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-md hover:shadow-xl transition duration-300 border-l-4 border-red-500">
                <h3 class="text-gray-500 font-semibold mb-2">Anomalies Detected</h3>
                <p class="text-2xl font-bold text-blue-800">5</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-md hover:shadow-xl transition duration-300 border-l-4 border-green-500">
                <h3 class="text-gray-500 font-semibold mb-2">Processed Requests</h3>
                <p class="text-2xl font-bold text-blue-800">1,180</p>
            </div>
        </div>

        <!-- TABLE SECTION -->
        <div class="bg-white rounded-2xl shadow-md p-6">
            <h2 class="text-xl md:text-2xl font-bold text-blue-900 mb-4">Recent Financial Requests</h2>
            <div class="overflow-x-auto">
                <table class="w-full table-auto border-collapse text-sm md:text-base">
                    <thead>
                        <tr class="bg-blue-800 text-white">
                            <th class="p-3 text-left">Request ID</th>
                            <th class="p-3 text-left">Submitted By</th>
                            <th class="p-3 text-left">Amount</th>
                            <th class="p-3 text-left">Status</th>
                            <th class="p-3 text-left">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="bg-gray-50 hover:bg-gray-100 transition">
                            <td class="p-3 font-medium">REQ-001</td>
                            <td class="p-3">John Doe</td>
                            <td class="p-3">₱15,000</td>
                            <td class="p-3 text-yellow-600 font-semibold">Pending</td>
                            <td class="p-3">2025-12-18</td>
                        </tr>
                        <tr class="bg-white hover:bg-gray-100 transition">
                            <td class="p-3 font-medium">REQ-002</td>
                            <td class="p-3">Jane Smith</td>
                            <td class="p-3">₱8,500</td>
                            <td class="p-3 text-green-600 font-semibold">Approved</td>
                            <td class="p-3">2025-12-17</td>
                        </tr>
                        <tr class="bg-gray-50 hover:bg-gray-100 transition">
                            <td class="p-3 font-medium">REQ-003</td>
                            <td class="p-3">Mark Lee</td>
                            <td class="p-3">₱12,300</td>
                            <td class="p-3 text-red-600 font-semibold">Rejected</td>
                            <td class="p-3">2025-12-16</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

</body>
</html>
