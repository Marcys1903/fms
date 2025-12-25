<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BCP Financial Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-800">

<!-- NAVBAR -->
<nav class="bg-blue-900/95 backdrop-blur px-10 py-4 flex items-center justify-between shadow-lg sticky top-0 z-50">
    <!-- Logo -->
    <div class="flex items-center text-white font-bold text-xl tracking-wide">
        <!-- <img src="logo.png" class="w-10 mr-2" alt="BCP Logo"> -->
        BCP Financial Management System
    </div>

    <!-- Navigation Links -->
    <ul class="hidden md:flex space-x-8 text-white font-medium">
        <li><a href="#home" class="hover:text-yellow-400 transition">Home</a></li>
        <li><a href="#overview" class="hover:text-yellow-400 transition">Overview</a></li>
        <li><a href="#features" class="hover:text-yellow-400 transition">Features</a></li>
        <li><a href="#objectives" class="hover:text-yellow-400 transition">Objectives</a></li>
        <li><a href="#contact" class="hover:text-yellow-400 transition">Contact</a></li>
    </ul>

    <!-- Sign In Only -->
    <div>
        <a href="auth/login.php"
           class="bg-yellow-400 text-black px-5 py-2 rounded-full text-sm font-semibold hover:bg-yellow-300 transition shadow">
            Sign In
        </a>
    </div>
</nav>

<!-- HERO SECTION -->
<header id="home" class="relative bg-gradient-to-r from-blue-900 to-blue-700 text-white px-10 py-28 overflow-hidden">
    <div class="absolute inset-0 opacity-10 bg-[radial-gradient(circle_at_top_right,white,transparent_60%)]"></div>

    <div class="relative max-w-4xl">
        <h1 class="text-4xl md:text-5xl font-bold mb-6 leading-tight">
            Secure & AI-Powered <br>
            Financial Management System
        </h1>

        <p class="text-lg text-blue-100 leading-relaxed">
            Designed exclusively for <strong>Bestlink College of the Philippines</strong>,
            this system automates financial workflows, enhances transparency,
            and uses artificial intelligence to support data-driven decision-making
            across all departments.
        </p>

        <div class="mt-10 flex flex-wrap gap-4">
            <a href="#overview"
               class="bg-yellow-400 text-black px-8 py-3 rounded-full font-semibold hover:bg-yellow-300 transition shadow">
                Explore System
            </a>
            <a href="auth/login.php"
               class="border border-white px-8 py-3 rounded-full font-semibold hover:bg-white hover:text-blue-900 transition">
                Access Portal
            </a>
        </div>
    </div>
</header>

<!-- OVERVIEW -->
<section id="overview" class="px-10 py-24 bg-white">
    <div class="text-center mb-14">
        <h2 class="text-3xl font-bold text-blue-800">System Overview</h2>
        <p class="text-gray-600 mt-3 max-w-2xl mx-auto">
            A centralized financial platform built to modernize and secure
            institutional financial operations
        </p>
    </div>

    <div class="max-w-5xl mx-auto grid md:grid-cols-2 gap-10">
        <div class="bg-gray-50 p-8 rounded-2xl shadow">
            <h3 class="font-semibold text-blue-800 mb-3">Centralized Financial Control</h3>
            <p class="text-gray-700 leading-relaxed">
                All financial transactions, requests, and reports are consolidated
                into a single secure system to eliminate redundancy, reduce errors,
                and ensure complete transparency.
            </p>
        </div>

        <div class="bg-gray-50 p-8 rounded-2xl shadow">
            <h3 class="font-semibold text-blue-800 mb-3">AI-Driven Intelligence</h3>
            <p class="text-gray-700 leading-relaxed">
                Machine learning algorithms powered by Scikit-learn automatically
                detect anomalies, helping administrators identify irregular or
                suspicious financial activities.
            </p>
        </div>
    </div>
</section>

<!-- FEATURES -->
<section id="features" class="px-10 py-24 bg-gray-100">
    <div class="text-center mb-14">
        <h2 class="text-3xl font-bold text-blue-800">Key Features</h2>
        <p class="text-gray-600 mt-3">
            Advanced tools that support efficiency, accountability, and security
        </p>
    </div>

    <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3 max-w-6xl mx-auto">
        <div class="bg-white p-7 rounded-2xl shadow hover:shadow-xl transition">
            <h3 class="font-semibold text-blue-800 mb-2">Centralized Monitoring</h3>
            <p class="text-sm text-gray-600">
                Unified tracking of departmental budgets, expenses, and allocations.
            </p>
        </div>

        <div class="bg-white p-7 rounded-2xl shadow hover:shadow-xl transition">
            <h3 class="font-semibold text-blue-800 mb-2">Dual-Level Administration</h3>
            <p class="text-sm text-gray-600">
                Structured approval hierarchy with Admin and Super Admin roles.
            </p>
        </div>

        <div class="bg-white p-7 rounded-2xl shadow hover:shadow-xl transition">
            <h3 class="font-semibold text-blue-800 mb-2">AI Anomaly Detection</h3>
            <p class="text-sm text-gray-600">
                Automatic detection of suspicious or irregular transactions.
            </p>
        </div>

        <div class="bg-white p-7 rounded-2xl shadow hover:shadow-xl transition">
            <h3 class="font-semibold text-blue-800 mb-2">Automated Workflows</h3>
            <p class="text-sm text-gray-600">
                Seamless request submission, approval routing, and audit logging.
            </p>
        </div>

        <div class="bg-white p-7 rounded-2xl shadow hover:shadow-xl transition">
            <h3 class="font-semibold text-blue-800 mb-2">Real-Time Analytics</h3>
            <p class="text-sm text-gray-600">
                Dashboards and reports for instant financial insights.
            </p>
        </div>

        <div class="bg-white p-7 rounded-2xl shadow hover:shadow-xl transition">
            <h3 class="font-semibold text-blue-800 mb-2">Audit & Transparency</h3>
            <p class="text-sm text-gray-600">
                Complete transaction logs to support accountability and compliance.
            </p>
        </div>
    </div>
</section>

<!-- OBJECTIVES -->
<section id="objectives" class="px-10 py-24 bg-white">
    <div class="text-center mb-14">
        <h2 class="text-3xl font-bold text-blue-800">Project Objectives</h2>
        <p class="text-gray-600 mt-3">
            Core goals guiding the development of the system
        </p>
    </div>

    <div class="overflow-x-auto max-w-6xl mx-auto">
        <table class="w-full shadow-lg rounded-2xl overflow-hidden">
            <thead>
                <tr class="bg-blue-800 text-white">
                    <th class="p-5 text-left">Objective</th>
                    <th class="p-5 text-left">Description</th>
                </tr>
            </thead>
            <tbody class="text-gray-700">
                <tr class="bg-gray-50">
                    <td class="p-5">Centralized Monitoring</td>
                    <td class="p-5">Ensure transparency and accurate financial reporting.</td>
                </tr>
                <tr>
                    <td class="p-5">Approval Hierarchy</td>
                    <td class="p-5">Implement secure, structured approval workflows.</td>
                </tr>
                <tr class="bg-gray-50">
                    <td class="p-5">AI Security</td>
                    <td class="p-5">Detect anomalies and suspicious transactions.</td>
                </tr>
                <tr>
                    <td class="p-5">Process Automation</td>
                    <td class="p-5">Reduce manual effort and human error.</td>
                </tr>
                <tr class="bg-gray-50">
                    <td class="p-5">Data-Driven Decisions</td>
                    <td class="p-5">Support strategic planning using real-time insights.</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<!-- FOOTER -->
<footer id="contact" class="bg-blue-900 text-white text-center py-10 px-10">
    <p class="text-sm leading-relaxed">
        © 2025 Bestlink College of the Philippines <br>
        Financial Management System <br>
        For account concerns, please contact the System Administrator
    </p>
</footer>

</body>
</html>
