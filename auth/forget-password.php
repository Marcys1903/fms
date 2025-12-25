<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-md">
        <h2 class="text-2xl font-bold text-center mb-6">Forgot Password</h2>

        <p class="text-sm text-gray-600 text-center mb-4">
            Enter your email and we'll send you a password reset link.
        </p>

        <form id="resetForm" class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1">Email Address</label>
                <input type="email" required
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <button type="submit"
                class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition">
                Send Reset Link
            </button>
        </form>

        <p id="successMsg" class="text-center text-sm mt-4 text-green-600 hidden">
            Password reset link sent successfully!
        </p>

        <div class="text-center mt-4">
            <a href="login.php" class="text-sm text-blue-600 hover:underline">
                Back to Login
            </a>
        </div>
    </div>

<script>
document.getElementById("resetForm").addEventListener("submit", function(e) {
    e.preventDefault();

    // Demo success message
    document.getElementById("successMsg").classList.remove("hidden");
});
</script>

</body>
</html>
