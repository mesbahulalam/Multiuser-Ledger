<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <header class="bg-white shadow">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="text-lg font-semibold text-gray-800">
                <a href="/" class="hover:text-gray-600">Home</a>
            </div>
            <nav class="space-x-4">
                <?php if(Users::isLoggedIn()): ?>
                    <!-- welcome text -->
                    <span class="text-gray-800">Welcome, <?php echo Users::getCurrentUser(resolve($_SESSION['user_id'], $_COOKIE['user_id']))['username']; ?></span>
                    <a href="/dashboard" class="text-gray-800 hover:text-gray-600">Dashboard</a>
                    <a href="/logout" class="text-gray-800 hover:text-gray-600">Logout</a>
                <?php else: ?>
                    <a href="/login" class="text-gray-800 hover:text-gray-600">Login</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <div class="container mx-auto px-4 py-8">
        <header class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-800">Welcome to Our Application</h1>
            <p class="text-gray-600 mt-2">A fast and modern PHP application</p>
        </header>
        
        <main class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold text-gray-800 mb-2">Features</h2>
                <p class="text-gray-600">Discover all the amazing features our application has to offer.</p>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold text-gray-800 mb-2">Documentation</h2>
                <p class="text-gray-600">Check out our comprehensive documentation to get started.</p>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold text-gray-800 mb-2">Support</h2>
                <p class="text-gray-600">Need help? Our support team is here for you.</p>
            </div>

            <!-- dashboard link -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <a href="/dashboard/">
                    <h2 class="text-xl font-semibold text-gray-800 mb-2">Dashboard</h2>
                </a>
                <p class="text-gray-600">Go to the dashboard to manage your account.</p>
            </div>

            <!-- manage users link -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <a href="/dashboard/?activeSection=users">
                    <h2 class="text-xl font-semibold text-gray-800 mb-2">Manage Users</h2>
                </a>
                <p class="text-gray-600">Manage users and their permissions.</p>
            </div>

            <!-- manage accounting link -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <a href="/dashboard/?activeSection=accounting">
                    <h2 class="text-xl font-semibold text-gray-800 mb-2">Manage Accounting</h2>
                </a>
                <p class="text-gray-600">Manage accounting and financial data.</p>
            </div>
        </main>
    </div>
</body>
</html>