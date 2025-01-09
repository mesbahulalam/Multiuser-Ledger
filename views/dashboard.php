<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" />
    <!-- <script src="https://cdn.tailwindcss.com/"></script> -->
    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/2.3.0/alpine.js"></script> -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.13.3/cdn.js" defer></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-100" x-data="{ 
    sidebarOpen: window.innerWidth >= 1024,
    activeSection: '<?php echo $activeSection; ?>',
    checkScreenSize() {
        this.sidebarOpen = window.innerWidth >= 1024;
    }
}" x-init="
    checkScreenSize();
    window.addEventListener('resize', checkScreenSize);
">


    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white p-5 rounded-lg flex items-center space-x-3">
            <svg class="spinner w-8 h-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-gray-700">Loading...</span>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 z-30 w-64 bg-gray-300 transition duration-300 transform"
         :class="{'translate-x-0': sidebarOpen, '-translate-x-full': !sidebarOpen}">
         <div class="flex items-center justify-center h-16 bg-gray-900">
            <span class="text-white text-xl font-bold">Dashboard</span>
        </div>
        <nav class="mt-8">
            <a href="/dashboard" class="flex items-center px-6 py-3 text-gray-900 hover:bg-gray-200" :class="{'bg-gray-200': activeSection === 'dashboard'}">
                <svg class="h-6 w-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Dashboard
            </a>
            <a href="/dashboard?activeSection=accounting" class="flex items-center px-6 py-3 text-gray-900 hover:bg-gray-200" :class="{'bg-gray-200': activeSection === 'accounting'}">
                <svg class="h-6 w-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Accounting
            </a>
            <a href="/dashboard?activeSection=user-profile" class="flex items-center px-6 py-3 text-gray-900 hover:bg-gray-200" :class="{'bg-gray-200': activeSection === 'user-profile'}">
                <svg class="h-6 w-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Profile
            </a>
            <?php if(Users::hasPermission($_SESSION['user_id'], 'DELETE')): ?>
            <a href="/dashboard?activeSection=salary" class="flex items-center px-6 py-3 text-gray-900 hover:bg-gray-200" :class="{'bg-gray-200': activeSection === 'salary'}">
                <svg class="h-6 w-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Salary
            </a>
            <a href="/dashboard?activeSection=salary-history" class="flex items-center px-6 py-3 text-gray-900 hover:bg-gray-200" :class="{'bg-gray-200': activeSection === 'salary-history'}">
                <svg class="h-6 w-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
                Salary History
            </a>
            <a href="/dashboard?activeSection=test" class="flex items-center px-6 py-3 text-gray-900 hover:bg-gray-200" :class="{'bg-gray-200': activeSection === 'filtered'}">
                <svg class="h-6 w-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414v6.586a1 1 0 01-1.414.707l-4-2A1 1 0 018 18v-4.586l-6.293-6.293A1 1 0 013 6.586V4z"/>
                </svg>
                Filtered Data
            </a>
            <a href="/dashboard?activeSection=users" class="flex items-center px-6 py-3 text-gray-900 hover:bg-gray-200" :class="{'bg-gray-200': activeSection === 'users'}">
                <svg class="h-6 w-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                Users
            </a>
            <?php endif; ?>
            <?php if(Users::hasPermission($_SESSION['user_id'], 'DELETE')): ?>
            <a href="/dashboard?activeSection=settings" class="flex items-center px-6 py-3 text-gray-900 hover:bg-gray-200" :class="{'bg-gray-200': activeSection === 'settings'}">
                <svg class="h-6 w-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Settings
            </a>
            <?php endif; ?>
            
            <!-- logout link -->
            <a href="/logout" class="flex items-center px-6 py-3 text-gray-900 hover:bg-gray-200">                
                <svg class="h-6 w-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="min-h-screen transition-all duration-300"
         :class="{'pl-64': sidebarOpen, 'pl-0': !sidebarOpen}">
        <!-- Navigation -->
        <nav class="bg-white shadow-lg">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex justify-between h-16">
                    <div class="flex items-center space-x-4">
                        <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 hover:text-gray-700">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>
                        <span class="text-gray-700">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Content Sections -->
        <div class="max-w-7xl mx-auto px-4 py-8">
            <?php
            switch($activeSection) {
                case 'dashboard':
                    // include 'views/transaction_form.php';
                    include 'views/summary.php';
                    break;
                case 'accounting':
                    include 'views/accounting.php';
                    break;
                case 'salary':
                    include 'views/salary.php';
                    break;
                case 'salary-history':
                    include 'views/salary-history.php';
                    break;
                case 'users':
                    include 'views/users.php';
                    break;
                case 'user-profile':
                    include 'views/user-profile.php';
                    break;
                case 'settings':
                    include 'views/settings.php';
                    break;
            }
            ?>
        </div>
    </div>
</body>
</html>
