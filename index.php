<?php
require_once 'classes/DB.php';
require_once 'classes/Router.php';
require_once 'classes/Users.php';
require_once 'classes/FinanceManager.php';
require_once 'classes/Attachments.php';
require_once 'classes/ProjectionManager.php';
require_once 'functions.php';
define('DEFAULT_DATA_ORDER', 'DESC');

Users::init();
$router = new Router();

// Redirect to login page if not logged in
if (!isset($_SESSION['user_id'])) {
    $router->before('/(dashboard|api).*', function() {
        header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit();
    });
}

$router->get('/', fn() => include 'views/index.php');

$router->get('/dashboard', function () {
    if (!isset($_SESSION['user_id'])) header('Location: /login');
    $activeSection = resolve($_GET['activeSection'], 'dashboard');
    include 'views/dashboard.php';
});

$router->both('/login', function () {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (Users::login($_POST['username'], $_POST['password'], $_POST['remember'])) {
            header('Location: ' . resolve($_GET['redirect'], '/dashboard'));
        } else {
            $error = 'Invalid username or password';
        }
    }
    require_once 'views/login.php';
});

$router->get('/login-as', function () {
    if (!Users::can('DELETE')) {
        jsonResponse(['error' => 'Permission denied'], 403);
    }

    $userId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
    if (!$userId) {
        jsonResponse(['error' => 'Valid User ID is required'], 400);
    }

    if (!Users::exists($userId)) {
        jsonResponse(['error' => 'User not found'], 404);
    }

    if (Users::loginAs($userId)) {
        header('Location: /dashboard');
        exit();
    } else {
        jsonResponse(['error' => 'Failed to log in as the specified user'], 500);
    }
});

// API Routes
$router->mount('/api', function () use ($router) {

    $router->get('/', fn() => jsonResponse(['message' => 'Welcome to the API']));

    $router->mount('/salary', function () use ($router) {

        $router->post('/add', function () {
            if (!Users::can('CREATE')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }
    
            $data = json_decode(file_get_contents('php://input'), true);
    
            // Validate required fields
            if (empty($data['user_id']) || empty($data['month']) || empty($data['basic_salary']) || empty($data['net_salary'])) {
                jsonResponse(['error' => 'Missing required fields'], 400);
            }
    
            try {
                $db = DB::getInstance();
    
                // Check if salary record already exists for this user and month
                $checkStmt = $db->prepare("
                    SELECT COUNT(*) FROM salaries 
                    WHERE user_id = :user_id AND month = :month
                ");
                $checkStmt->execute([
                    'user_id' => $data['user_id'],
                    'month' => $data['month']
                ]);
    
                if ($checkStmt->fetchColumn() > 0) {
                    jsonResponse(['error' => 'Salary record already exists for this month'], 400);
                }
    
                $salaryData = [
                    'user_id' => $data['user_id'],
                    'approved_by' => $data['approved_by'] ?? null, // Handle null approver
                    'basic_salary' => $data['basic_salary'],
                    'allowances' => $data['allowances'] ?? 0.00,
                    'deductions' => $data['deductions'] ?? 0.00,
                    'net_salary' => $data['net_salary'],
                    'month' => $data['month'],
                    'payment_details' => $data['payment_details'] ?? null
                ];
    
                $stmt = $db->prepare("
                    INSERT INTO salaries 
                    (user_id, approved_by, basic_salary, allowances, deductions, net_salary, month, payment_details)
                    VALUES 
                    (:user_id, :approved_by, :basic_salary, :allowances, :deductions, :net_salary, :month, :payment_details)
                ");
    
                if ($stmt->execute($salaryData)) {
                    jsonResponse(['success' => true, 'message' => 'Salary entry added successfully']);
                } else {
                    jsonResponse(['error' => 'Failed to add salary entry'], 500);
                }
            } catch (PDOException $e) {
                jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
            }
        });
    
        $router->get('/history/{id}', function ($id) {
            if (!Users::can('READ')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }
        
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = 10;
            $offset = ($page - 1) * $limit;
        
            try {
                $db = DB::getInstance();
        
                $stmt = $db->prepare("
                    SELECT 
                        id,
                        month,
                        basic_salary,
                        allowances,
                        deductions,
                        net_salary,
                        payment_details,
                        created_at
                    FROM salaries
                    WHERE user_id = :user_id
                    ORDER BY created_at DESC
                    LIMIT :limit OFFSET :offset
                ");
        
                $stmt->bindValue(':user_id', $id, PDO::PARAM_INT);
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                $stmt->execute();
        
                $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
                jsonResponse(['history' => $history]);
            } catch (Exception $e) {
                jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
            }
        });
    
        $router->get('/summary', function () {
            if (!Users::can('READ')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }
    
            try {
                $db = DB::getInstance();
                
                $stmt = $db->query("
                    SELECT 
                        u.user_id,
                        u.username,
                        COALESCE(
                            (SELECT net_salary 
                                FROM salaries s2 
                                WHERE s2.user_id = u.user_id 
                                ORDER BY created_at DESC 
                                LIMIT 1
                            ), 
                            0
                        ) as latest_salary,
                        COALESCE(SUM(s.net_salary), 0) as accumulated_salary,
                        COALESCE(
                            (SELECT SUM(amount) 
                                FROM expenses 
                                WHERE expense_by = u.user_id AND purpose = 'salary'
                            ), 
                            0
                        ) as paid_amount,
                        COALESCE(SUM(s.net_salary), 0) - COALESCE(
                            (SELECT SUM(amount) 
                                FROM expenses 
                                WHERE expense_by = u.user_id AND purpose = 'salary'
                            ), 
                            0
                        ) as payable_amount
                    FROM users u
                    LEFT JOIN salaries s ON u.user_id = s.user_id
                    GROUP BY u.user_id, u.username
                    HAVING latest_salary > 0 OR accumulated_salary > 0 OR paid_amount > 0 OR payable_amount > 0
                    ORDER BY u.username
                ");
    
                $summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
                jsonResponse($summary);
            } catch (Exception $e) {
                jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
            }
        });
    
        $router->get('/payable', function () {
            if (!Users::can('READ')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }
    
            $userId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
            if (!$userId) {
                jsonResponse(['error' => 'Valid User ID is required'], 400);
            }
    
            try {
                $db = DB::getInstance();
    
                // Calculate total allocated salary from the salaries table
                $allocatedStmt = $db->prepare("
                    SELECT COALESCE(SUM(net_salary), 0) AS total_allocated
                    FROM salaries
                    WHERE user_id = :user_id
                ");
                $allocatedStmt->execute(['user_id' => $userId]);
                $totalAllocated = $allocatedStmt->fetch(PDO::FETCH_ASSOC)['total_allocated'];
    
                // Calculate total paid salary from the expenses table
                $paidStmt = $db->prepare("
                    SELECT COALESCE(SUM(amount), 0) AS total_paid
                    FROM expenses
                    WHERE expense_by = :user_id AND purpose = 'salary'
                ");
                $paidStmt->execute(['user_id' => $userId]);
                $totalPaid = $paidStmt->fetch(PDO::FETCH_ASSOC)['total_paid'];
    
                // Calculate the payable amount
                $payableAmount = $totalAllocated - $totalPaid;
    
                jsonResponse([
                    'user_id' => $userId,
                    'total_allocated' => $totalAllocated,
                    'total_paid' => $totalPaid,
                    'payable_amount' => $payableAmount
                ]);
            } catch (Exception $e) {
                jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
            }
        });
    });
        
    // Finance/Transaction Routes
    $router->mount('/finance', function () use ($router) {
        
        // Get financial filtered data for charts
        $router->get('/filtered-data', function () {
            if (!Users::can('READ')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }
            
            // Check if all-time option is requested
            $allTime = isset($_GET['allTime']) && $_GET['allTime'] === 'true';
            
            if ($allTime) {
                // Get the earliest transaction date from both incomes and expenses
                $earliestIncome = DB::fetchOne("SELECT MIN(date_created) as min_date FROM incomes");
                $earliestExpense = DB::fetchOne("SELECT MIN(date_created) as min_date FROM expenses");
                
                // Determine the earliest date between the two tables
                $incomeDate = $earliestIncome['min_date'] ?? date('Y-m-d');
                $expenseDate = $earliestExpense['min_date'] ?? date('Y-m-d');
                
                // Set the start date to the earlier of the two dates
                $startDate = (strtotime($incomeDate) < strtotime($expenseDate)) ? $incomeDate : $expenseDate;
                
                // If both are null (no records), default to the beginning of the current year
                if (!$earliestIncome['min_date'] && !$earliestExpense['min_date']) {
                    $startDate = date('Y-01-01');
                }
                
                // Format the date to Y-m-d
                $startDate = date('Y-m-d', strtotime($startDate));
                
                // End date is today
                $endDate = date('Y-m-d');
            } else {
                // Regular date range handling
                $startDate = $_GET['startDate'] ?? date('Y-m-01'); // Default to first day of current month
                $endDate = $_GET['endDate'] ?? date('Y-m-t');     // Default to last day of current month
            }
            
            $category = $_GET['category'] ?? '';
            $status = $_GET['status'] ?? 'approved'; // Default to approved records
            $includeProjections = isset($_GET['includeProjections']) ? (bool)$_GET['includeProjections'] : true;
            $comparisonType = $_GET['comparisonType'] ?? 'monthly'; // monthly, quarterly, yearly, category, bank
            
            // Build filters array for FinanceManager methods
            $filters = [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'category' => $category,
                'status' => $status
            ];
            
            // Get formatted data for chart display based on comparison type
            $chartData = [];
            
            switch ($comparisonType) {
                case 'monthly':
                    $chartData = getMonthlyComparisonData($filters, $includeProjections);
                    break;
                case 'quarterly':
                    $chartData = getQuarterlyComparisonData($filters, $includeProjections);
                    break;
                case 'yearly':
                    $chartData = getYearlyComparisonData($filters, $includeProjections);
                    break;
                case 'category':
                    $chartData = getCategoryComparisonData($filters, $includeProjections);
                    break;
                case 'bank':
                    $chartData = getBankComparisonData($filters, $includeProjections);
                    break;
                default:
                    $chartData = getMonthlyComparisonData($filters, $includeProjections);
            }
            
            jsonResponse($chartData);
        });
        
        // Get incomes with filters and pagination
        $router->get('/incomes', function () {
            if (!Users::can('READ')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }
            
            $page = $_GET['page'] ?? 1;
            $itemsPerPage = $_GET['itemsPerPage'] ?? 10;
            $search = $_GET['search'] ?? '';
            $filters = [
                'start_date' => $_GET['startDate'] ?? '',
                'end_date' => $_GET['endDate'] ?? '',
                'category' => $_GET['category'] ?? '',
                'status' => $_GET['status'] ?? ''
            ];
            
            $incomes = FinanceManager::getIncomes($filters, $page, $itemsPerPage, $search);
            $total = FinanceManager::getTotalIncomes($filters, $search);
            
            jsonResponse([
                'items' => $incomes,
                'totalPages' => ceil($total / $itemsPerPage),
                'currentPage' => (int)$page
            ]);
        });
        
        // Get expenses with filters and pagination
        $router->get('/expenses', function () {
            if (!Users::can('READ')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }
            
            $page = $_GET['page'] ?? 1;
            $itemsPerPage = $_GET['itemsPerPage'] ?? 10;
            $search = $_GET['search'] ?? '';
            $filters = [
                'start_date' => $_GET['startDate'] ?? '',
                'end_date' => $_GET['endDate'] ?? '',
                'category' => $_GET['category'] ?? '',
                'status' => $_GET['status'] ?? ''
            ];
            
            $expenses = FinanceManager::getExpenses($filters, $page, $itemsPerPage, $search);
            $total = FinanceManager::getTotalExpenses($filters, $search);
            
            jsonResponse([
                'items' => $expenses,
                'totalPages' => ceil($total / $itemsPerPage),
                'currentPage' => (int)$page
            ]);
        });
        
        // Get suggestion data for autocomplete fields
        $router->get('/suggestions/{table}/{column}', function ($table, $column) {
            if (!Users::can('READ')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }
            
            // Security check to prevent SQL injection
            $allowedTables = ['incomes', 'expenses'];
            $allowedColumns = ['category', 'bank', 'purpose', 'method', 'income_from', 'expense_by'];
            
            if (!in_array($table, $allowedTables) || !in_array($column, $allowedColumns)) {
                jsonResponse(['error' => 'Invalid table or column'], 400);
            }
            
            $suggestions = FinanceManager::getDistinctColumn($table, $column);
            
            // Format data for autocomplete
            $result = [];
            foreach ($suggestions as $value) {
                if ($value) {
                    $result[] = ['id' => $value, 'text' => $value];
                }
            }
            
            jsonResponse($result);
        });
        
        // Add new income
        $router->post('/income/add', function () {
            if (!Users::can('CREATE')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Add user_id and set status
            $data['entry_by'] = $_SESSION['user_id'];
            $data['status'] = Users::can('APPROVE') ? 'approved' : 'pending';
            
            // Set date fields if not provided
            if (empty($data['date_realized'])) {
                $data['date_realized'] = date('Y-m-d H:i:s');
            }
            
            $result = FinanceManager::addIncome($data);
            
            if ($result) {
                jsonResponse(['success' => true, 'message' => 'Income added successfully']);
            } else {
                jsonResponse(['error' => 'Failed to add income'], 500);
            }
        });
        
        // Update existing income
        $router->post('/income/update', function () {
            if (!Users::can('UPDATE')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                jsonResponse(['error' => 'Income ID is required'], 400);
            }
            
            $result = FinanceManager::updateIncome($data['id'], $data);
            
            if ($result) {
                jsonResponse(['success' => true, 'message' => 'Income updated successfully']);
            } else {
                jsonResponse(['error' => 'Failed to update income'], 500);
            }
        });
        
        // Delete (soft delete) income
        $router->post('/income/delete', function () {
            if (!Users::can('DELETE')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                jsonResponse(['error' => 'Income ID is required'], 400);
            }
            
            $result = FinanceManager::deleteIncome($data['id']);
            
            if ($result) {
                jsonResponse(['success' => true, 'message' => 'Income deleted successfully']);
            } else {
                jsonResponse(['error' => 'Failed to delete income'], 500);
            }
        });
        
        // Approve income
        $router->post('/income/approve', function () {
            if (!Users::can('APPROVE')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                jsonResponse(['error' => 'Income ID is required'], 400);
            }
            
            $result = FinanceManager::approveIncome($data['id']);
            
            if ($result) {
                jsonResponse(['success' => true, 'message' => 'Income approved successfully']);
            } else {
                jsonResponse(['error' => 'Failed to approve income'], 500);
            }
        });
        
        // Add new expense
        $router->post('/expense/add', function () {
            if (!Users::can('CREATE')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Add user_id and set status
            $data['entry_by'] = $_SESSION['user_id'];
            $data['status'] = Users::can('APPROVE') ? 'approved' : 'pending';
            
            // Set date fields if not provided
            if (empty($data['date_realized'])) {
                $data['date_realized'] = date('Y-m-d H:i:s');
            }
            
            $result = FinanceManager::addExpense($data);
            
            if ($result) {
                jsonResponse(['success' => true, 'message' => 'Expense added successfully']);
            } else {
                jsonResponse(['error' => 'Failed to add expense'], 500);
            }
        });
        
        // Update existing expense
        $router->post('/expense/update', function () {
            if (!Users::can('UPDATE')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                jsonResponse(['error' => 'Expense ID is required'], 400);
            }
            
            $result = FinanceManager::updateExpense($data['id'], $data);
            
            if ($result) {
                jsonResponse(['success' => true, 'message' => 'Expense updated successfully']);
            } else {
                jsonResponse(['error' => 'Failed to update expense'], 500);
            }
        });
        
        // Delete (soft delete) expense
        $router->post('/expense/delete', function () {
            if (!Users::can('DELETE')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                jsonResponse(['error' => 'Expense ID is required'], 400);
            }
            
            $result = FinanceManager::deleteExpense($data['id']);
            
            if ($result) {
                jsonResponse(['success' => true, 'message' => 'Expense deleted successfully']);
            } else {
                jsonResponse(['error' => 'Failed to delete expense'], 500);
            }
        });
        
        // Approve expense
        $router->post('/expense/approve', function () {
            if (!Users::can('APPROVE')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                jsonResponse(['error' => 'Expense ID is required'], 400);
            }
            
            $result = FinanceManager::approveExpense($data['id']);
            
            if ($result) {
                jsonResponse(['success' => true, 'message' => 'Expense approved successfully']);
            } else {
                jsonResponse(['error' => 'Failed to approve expense'], 500);
            }
        });
    });

    $router->mount('/projection', function () use ($router) {
    
        // Get projections with pagination and filters
        $router->get('/list', function () {
            if (!Users::can('READ')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }
    
            $type = $_GET['type'] ?? 'all'; // 'income', 'expense', or 'all'
            $page = $_GET['page'] ?? 1;
            $itemsPerPage = $_GET['itemsPerPage'] ?? 10;
            $search = $_GET['search'] ?? '';
            $filters = [
                'start_date' => $_GET['startDate'] ?? '',
                'end_date' => $_GET['endDate'] ?? '',
                'category' => $_GET['category'] ?? ''
            ];
    
            $items = ProjectionManager::getProjections($type, $filters, $page, $itemsPerPage, $search);
            $total = ProjectionManager::getTotalProjections($type, $filters, $search);
            
            jsonResponse([
                'items' => $items,
                'totalPages' => ceil($total / $itemsPerPage),
                'currentPage' => $page
            ]);
        });
    
        // Add new projection
        $router->post('/add', function () {
            if (!Users::can('CREATE')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }
    
            $data = json_decode(file_get_contents('php://input'), true);
            
            if ($data['type'] === 'income') {
                $result = ProjectionManager::addIncomeProjection([
                    'entry_by' => $_SESSION['user_id'],
                    'income_from' => $data['source'],
                    'category' => $data['category'],
                    'amount' => $data['amount'],
                    'notes' => $data['notes'] ?? '',
                    'date_realized' => $data['date_realized'] ?? date('Y-m-d H:i:s')
                ]);
            } else {
                $result = ProjectionManager::addExpenseProjection([
                    'entry_by' => $_SESSION['user_id'],
                    'expense_by' => $data['source'],
                    'category' => $data['category'],
                    'purpose' => $data['purpose'] ?? '',
                    'amount' => $data['amount'],
                    'notes' => $data['notes'] ?? '',
                    'date_realized' => $data['date_realized'] ?? date('Y-m-d H:i:s')
                ]);
            }
    
            if ($result) {
                jsonResponse(['success' => true, 'message' => 'Projection added successfully']);
            } else {
                jsonResponse(['error' => 'Failed to add projection'], 500);
            }
        });
    
        // Update projection
        $router->post('/update', function () {
            if (!Users::can('UPDATE')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }
    
            $data = json_decode(file_get_contents('php://input'), true);
            
            if ($data['type'] === 'income') {
                $result = ProjectionManager::updateIncomeProjection($data['id'], [
                    'income_from' => $data['source'],
                    'category' => $data['category'],
                    'amount' => $data['amount'],
                    'notes' => $data['notes'] ?? '',
                    'date_realized' => $data['date_realized'] ?? date('Y-m-d H:i:s')
                ]);
            } else {
                $result = ProjectionManager::updateExpenseProjection($data['id'], [
                    'expense_by' => $data['source'],
                    'category' => $data['category'],
                    'purpose' => $data['purpose'] ?? '',
                    'amount' => $data['amount'],
                    'notes' => $data['notes'] ?? '',
                    'date_realized' => $data['date_realized'] ?? date('Y-m-d H:i:s')
                ]);
            }
    
            if ($result) {
                jsonResponse(['success' => true, 'message' => 'Projection updated successfully']);
            } else {
                jsonResponse(['error' => 'Failed to update projection'], 500);
            }
        });
    
        // Delete projection
        $router->post('/delete', function () {
            if (!Users::can('DELETE')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }
    
            $data = json_decode(file_get_contents('php://input'), true);
            
            if ($data['type'] === 'income') {
                $result = ProjectionManager::deleteIncomeProjection($data['id']);
            } else {
                $result = ProjectionManager::deleteExpenseProjection($data['id']);
            }
    
            if ($result) {
                jsonResponse(['success' => true, 'message' => 'Projection deleted successfully']);
            } else {
                jsonResponse(['error' => 'Failed to delete projection'], 500);
            }
        });
        
        // Get categories for projections
        $router->get('/categories', function () {
            if (!Users::can('READ')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }
    
            $type = $_GET['type'] ?? 'all'; // 'income', 'expense', or 'all'
            
            if ($type === 'income' || $type === 'all') {
                $incomeCategories = ProjectionManager::getIncomeProjectionCategories();
            } else {
                $incomeCategories = [];
            }
            
            if ($type === 'expense' || $type === 'all') {
                $expenseCategories = ProjectionManager::getExpenseProjectionCategories();
            } else {
                $expenseCategories = [];
            }
            
            jsonResponse([
                'income' => $incomeCategories,
                'expense' => $expenseCategories
            ]);
        });
    
        // Get projection summary for the current month
        $router->get('/summary', function () {
            if (!Users::can('READ')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }
            
            // Default to current month
            $startDate = date('Y-m-01'); // First day of current month
            $endDate = date('Y-m-t');    // Last day of current month
            
            $summary = ProjectionManager::getProjectionSummary($startDate, $endDate);
            
            jsonResponse($summary);
        });
    });

    $router->mount('/users', function () use ($router) {

        $router->get('/', function () {
            if (!Users::can('READ')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }
            $users = Users::getAllUsers();
            jsonResponse($users);
        });
    
        $router->get('/namelist', function () {
            if (!Users::can('READ')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }
            $users = DB::fetchAll("
                SELECT user_id, username, salary 
                FROM users 
                WHERE is_active = TRUE 
                ORDER BY username
            ");
            $formatted = array_map(fn($user) => [
                'id' => $user['user_id'],
                'text' => $user['username'],
                'salary' => $user['salary']
            ], $users);
            jsonResponse($formatted);
        });
    
        $router->get('/namelist1', function () {
            if (!Users::can('READ')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }
            $users = array_map(fn($user) => [
                'id' => $user['user_id'],
                'text' => $user['username'],
                'balance' => ''
            ], Users::getAllUserNames());
            jsonResponse($users);
        });
    
        $router->post('/new', function () {
            if (!Users::can('CREATE')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }
            $data = json_decode(file_get_contents('php://input'), true);
            $newUserId = Users::createUser(
                $data['username'], 
                $data['email'], 
                $data['password'], 
                $data['role_name']
            );
            if ($newUserId) {
                jsonResponse(['success' => true, 'message' => 'User created successfully', 'user_id' => $newUserId]);
            } else {
                jsonResponse(['error' => 'Failed to create user'], 500);
            }
        });
    
        $router->post('/delete', function () {
            $data = json_decode(file_get_contents('php://input'), true);
    
            if (!Users::can('DELETE')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }
    
            $deleted = Users::deleteUser($data['user_id']);
            if ($deleted) {
                jsonResponse(['success' => true, 'message' => 'User deleted successfully']);
            } else {
                jsonResponse(['error' => 'Failed to delete user'], 500);
            }
        });
    
        $router->post('/disable', function () {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!Users::can('UPDATE')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }
            
            $deleted = Users::disableUser($data['user_id']);
            if ($deleted) {
                jsonResponse(['success' => true, 'message' => 'User disabled successfully']);
            } else {
                jsonResponse(['error' => 'Failed to disable user'], 500);
            }
        });
    
        $router->post('/update', function () {
            if (!Users::can('UPDATE') && $_POST['user_id'] != $_SESSION['user_id']) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }
    
            $userId = $_POST['user_id'];
            if (!$userId) {
                jsonResponse(['error' => 'User ID is required'], 400);
            }
            $updateData = array_filter([
                'username' => $_POST['username'],
                'email' => $_POST['email'],
                'first_name' => $_POST['first_name'],
                'last_name' => $_POST['last_name'],
                'dob' => $_POST['dob'],
                'phone_number' => $_POST['phone_number'],
                'role_name' => $_POST['role_name'],
                'salary' => $_POST['salary']
            ], function ($value) {
                return $value !== "null";
            });
    
            if (Users::can('UPDATE')) {
                // role, salary, and permissions can only be updated by super admin
                unset($updateData['username']);
                unset($updateData['role_name']);
                // unset($updateData['salary']);
            }
    
            if (Users::updateUser($userId, $updateData)) {
                jsonResponse(['success' => true, 'message' => 'Profile updated successfully']);
            } else {
                jsonResponse(['error' => 'Failed to update profile'], 500);
            }
        });
    
        $router->post('/profile-picture', function () {
            if (!Users::can('UPDATE')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }
    
            if (!isset($_FILES['profile_picture'])) {
                jsonResponse(['error' => 'No file uploaded'], 400);
            }
    
            $userId = $_POST['user_id'];
            if (!$userId) {
                jsonResponse(['error' => 'User ID is required'], 400);
            }
    
            try {
                $attachments = new Attachments();
                $attachmentId = $attachments->uploadFile($_FILES['profile_picture']);
                $attachmentUrl = $attachments->getAttachmentUrl($attachmentId);
    
                if (Users::updateUser($userId, ['profile_picture' => $attachmentId])) {
                    jsonResponse([
                        'success' => true,
                        'message' => 'Profile picture updated successfully',
                        'url' => $attachmentUrl
                    ]);
                } else {
                    throw new Exception('Failed to update profile picture');
                }
            } catch (Exception $e) {
                jsonResponse(['error' => $e->getMessage()], 500);
            }
        });
    
        $router->get('/id-card', function () {
            if (!isset($_GET['user_id'])) {
                jsonResponse(['error' => 'User ID is required'], 400);
            }
    
            $userId = $_GET['user_id'] ?? $_SESSION['user_id'];
            $userMeta = DB::fetchOne(
                "SELECT meta_value FROM user_metadata WHERE user_id = ? AND meta_key = 'id_card'",
                [$userId]
            );
    
            if ($userMeta) {
                $attachments = new Attachments();
                $url = $attachments->getAttachmentUrl($userMeta['meta_value']);
                jsonResponse(['success' => true, 'url' => $url]);
            } else {
                jsonResponse(['success' => true, 'url' => null]);
            }
        });
    
        $router->post('/id-card', function () {
            if (!Users::can('UPDATE')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }
    
            if (!isset($_FILES['id_card'])) {
                jsonResponse(['error' => 'No file uploaded'], 400);
            }
    
            $userId = $_POST['user_id'];
            if (!$userId) {
                jsonResponse(['error' => 'User ID is required'], 400);
            }
    
            try {
                $attachments = new Attachments();
                $attachmentId = $attachments->uploadFile($_FILES['id_card']);
    
                // First, try to update existing record
                $updated = DB::query(
                    "UPDATE user_metadata SET meta_value = ? WHERE user_id = ? AND meta_key = 'id_card'",
                    [$attachmentId, $userId]
                );
    
                // If no record was updated, insert new one
                if (!$updated || DB::affectedRows() === 0) {
                    DB::query(
                        "INSERT INTO user_metadata (user_id, meta_key, meta_value) VALUES (?, 'id_card', ?)",
                        [$userId, $attachmentId]
                    );
                }
    
                $attachmentUrl = $attachments->getAttachmentUrl($attachmentId);
                jsonResponse([
                    'success' => true,
                    'message' => 'ID card uploaded successfully',
                    'url' => $attachmentUrl
                ]);
            } catch (Exception $e) {
                jsonResponse(['error' => $e->getMessage()], 500);
            }
        });
    
    });

    $router->mount('/roles', function () use ($router) {

        $router->post('/', function () {
            if (!Users::can('UPDATE')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['roles']) || !is_array($data['roles'])) {
                jsonResponse(['error' => 'Invalid input format'], 400);
            }

            try {
                DB::getInstance()->beginTransaction();

                foreach ($data['roles'] as $role) {
                    $roleId = $role['role_id'];
                    $permissions = $role['permissions'];

                    DB::query("DELETE FROM role_permissions WHERE role_id = ?", [$roleId]);

                    $sql = "INSERT INTO role_permissions (role_id, permission_id) 
                            SELECT ?, permission_id 
                            FROM permissions 
                            WHERE permission_name IN (" . str_repeat('?,', count($permissions) - 1) . "?)";
                    
                    $params = array_merge([$roleId], $permissions);
                    DB::query($sql, $params);
                }

                DB::getInstance()->commit();

                jsonResponse(['success' => true, 'message' => 'Role permissions updated successfully']);

            } catch (Exception $e) {
                DB::getInstance()->rollBack();
                jsonResponse(['error' => 'Failed to update role permissions: ' . $e->getMessage()], 500);
            }
        });

        $router->post('/new', function () {
            if (!Users::can('CREATE')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['role_name']) || !is_string($data['role_name'])) {
                jsonResponse(['error' => 'Invalid input format'], 400);
            }

            $roleId = Users::createRole($data['role_name']);

            if ($roleId) {
                jsonResponse(['success' => true, 'message' => 'Role created successfully', 'role_id' => $roleId]);
            } else {
                jsonResponse(['error' => 'Failed to create role'], 500);
            }
        });

    });
    
    $router->mount('/vendors', function () use ($router) {

        // Get all vendors
        $router->get('/', function () {
            if (!Users::can('READ')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }
    
            try {
                $db = DB::getInstance();
                $stmt = $db->query("
                    SELECT 
                        id,
                        vendor_name,
                        contact_person,
                        phone_number,
                        address,
                        created_at,
                        updated_at
                    FROM bw_vendors
                    ORDER BY vendor_name ASC
                ");
    
                $vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);
                jsonResponse($vendors);
            } catch (Exception $e) {
                jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
            }
        });
    
        // Create a new vendor
        $router->post('/new', function () {
            if (!Users::can('CREATE')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }
    
            $data = json_decode(file_get_contents('php://input'), true);
    
            // Validate required fields
            if (empty($data['vendor_name'])) {
                jsonResponse(['error' => 'Vendor name is required'], 400);
            }
    
            try {
                $db = DB::getInstance();
    
                // Check if vendor name already exists
                $checkStmt = $db->prepare("
                    SELECT COUNT(*) FROM bw_vendors 
                    WHERE vendor_name = :vendor_name
                ");
                $checkStmt->execute([
                    'vendor_name' => $data['vendor_name']
                ]);
    
                if ($checkStmt->fetchColumn() > 0) {
                    jsonResponse(['error' => 'A vendor with this name already exists'], 400);
                }
    
                $vendorData = [
                    'vendor_name' => $data['vendor_name'],
                    'contact_person' => $data['contact_person'] ?? null,
                    'phone_number' => $data['phone_number'] ?? null,
                    'address' => $data['address'] ?? null
                ];
    
                $stmt = $db->prepare("
                    INSERT INTO bw_vendors 
                    (vendor_name, contact_person, phone_number, address)
                    VALUES 
                    (:vendor_name, :contact_person, :phone_number, :address)
                ");
    
                if ($stmt->execute($vendorData)) {
                    $vendorId = $db->lastInsertId();
                    $newVendor = array_merge(['id' => $vendorId], $vendorData);
                    jsonResponse([
                        'success' => true, 
                        'message' => 'Vendor added successfully',
                        'vendor' => $newVendor
                    ]);
                } else {
                    jsonResponse(['error' => 'Failed to add vendor'], 500);
                }
            } catch (PDOException $e) {
                jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
            }
        });
    
        // Update an existing vendor
        $router->post('/update', function () {
            if (!Users::can('UPDATE')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }
    
            $data = json_decode(file_get_contents('php://input'), true);
    
            // Validate required fields
            if (empty($data['id']) || empty($data['vendor_name'])) {
                jsonResponse(['error' => 'Vendor ID and name are required'], 400);
            }
    
            try {
                $db = DB::getInstance();
    
                // Check if vendor exists
                $checkStmt = $db->prepare("
                    SELECT COUNT(*) FROM bw_vendors 
                    WHERE id = :id
                ");
                $checkStmt->execute([
                    'id' => $data['id']
                ]);
    
                if ($checkStmt->fetchColumn() == 0) {
                    jsonResponse(['error' => 'Vendor not found'], 404);
                }
    
                // Check if the updated vendor name already exists for a different vendor
                $nameCheckStmt = $db->prepare("
                    SELECT COUNT(*) FROM bw_vendors 
                    WHERE vendor_name = :vendor_name AND id != :id
                ");
                $nameCheckStmt->execute([
                    'vendor_name' => $data['vendor_name'],
                    'id' => $data['id']
                ]);
    
                if ($nameCheckStmt->fetchColumn() > 0) {
                    jsonResponse(['error' => 'A different vendor with this name already exists'], 400);
                }
    
                $vendorData = [
                    'id' => $data['id'],
                    'vendor_name' => $data['vendor_name'],
                    'contact_person' => $data['contact_person'] ?? null,
                    'phone_number' => $data['phone_number'] ?? null,
                    'address' => $data['address'] ?? null
                ];
    
                $stmt = $db->prepare("
                    UPDATE bw_vendors 
                    SET 
                        vendor_name = :vendor_name,
                        contact_person = :contact_person,
                        phone_number = :phone_number,
                        address = :address
                    WHERE id = :id
                ");
    
                if ($stmt->execute($vendorData)) {
                    jsonResponse([
                        'success' => true, 
                        'message' => 'Vendor updated successfully',
                        'vendor' => $vendorData
                    ]);
                } else {
                    jsonResponse(['error' => 'Failed to update vendor'], 500);
                }
            } catch (PDOException $e) {
                jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
            }
        });
    
        // Delete a vendor
        $router->post('/delete', function () {
            if (!Users::can('DELETE')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }
    
            $data = json_decode(file_get_contents('php://input'), true);
    
            // Validate required fields
            if (empty($data['id'])) {
                jsonResponse(['error' => 'Vendor ID is required'], 400);
            }
    
            try {
                $db = DB::getInstance();
    
                // Check if vendor exists
                $checkStmt = $db->prepare("
                    SELECT COUNT(*) FROM bw_vendors 
                    WHERE id = :id
                ");
                $checkStmt->execute([
                    'id' => $data['id']
                ]);
    
                if ($checkStmt->fetchColumn() == 0) {
                    jsonResponse(['error' => 'Vendor not found'], 404);
                }
                
                // Check if vendor is referenced in bw_bills
                $billsCheckStmt = $db->prepare("
                    SELECT COUNT(*) FROM bw_bills 
                    WHERE vendor_id = :vendor_id
                ");
                $billsCheckStmt->execute([
                    'vendor_id' => $data['id']
                ]);
                
                if ($billsCheckStmt->fetchColumn() > 0) {
                    jsonResponse(['error' => 'Cannot delete vendor with associated bills'], 400);
                }
    
                $stmt = $db->prepare("
                    DELETE FROM bw_vendors 
                    WHERE id = :id
                ");
    
                if ($stmt->execute(['id' => $data['id']])) {
                    jsonResponse([
                        'success' => true, 
                        'message' => 'Vendor deleted successfully'
                    ]);
                } else {
                    jsonResponse(['error' => 'Failed to delete vendor'], 500);
                }
            } catch (PDOException $e) {
                jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
            }
        });
    
        // Get a single vendor by ID
        $router->get('/{id}', function ($id) {
            if (!Users::can('READ')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }
    
            try {
                $db = DB::getInstance();
                $stmt = $db->prepare("
                    SELECT 
                        id,
                        vendor_name,
                        contact_person,
                        phone_number,
                        address,
                        created_at,
                        updated_at
                    FROM bw_vendors
                    WHERE id = :id
                ");
                $stmt->execute(['id' => $id]);
    
                $vendor = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$vendor) {
                    jsonResponse(['error' => 'Vendor not found'], 404);
                }
                
                jsonResponse($vendor);
            } catch (Exception $e) {
                jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
            }
        });
    });
    
    // Summary Routes
    $router->mount('/summary', function () use ($router) {
    
        // Get financial summary data
        $router->get('/', function () {
            if (!Users::can('READ')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }
            
            $today = date('Y-m-d');
            $startOfWeek = date('Y-m-d', strtotime('saturday this week'));
            $startOfMonth = date('Y-m-01');
            $startOfLastMonth = date('Y-m-01', strtotime('first day of last month'));
            $endOfLastMonth = date('Y-m-t', strtotime('last day of last month'));
            $startOfYear = date('Y-01-01');
            $startOfLastYear = date('Y-01-01', strtotime('first day of last year'));
            $endOfLastYear = date('Y-12-31', strtotime('last day of last year'));
            
            // Today's income and expense
            $todayIncome = DB::fetchColumn("SELECT SUM(amount) FROM incomes WHERE status = 'approved' AND DATE(date_created) = ?", [$today]);
            $todayExpense = DB::fetchColumn("SELECT SUM(amount) FROM expenses WHERE status = 'approved' AND DATE(date_created) = ?", [$today]);

            // This week's income and expense
            $thisWeekIncome = DB::fetchColumn("SELECT SUM(amount) FROM incomes WHERE status = 'approved' AND DATE(date_created) BETWEEN ? AND ?", [$startOfWeek, $today]);
            $thisWeekExpense = DB::fetchColumn("SELECT SUM(amount) FROM expenses WHERE status = 'approved' AND DATE(date_created) BETWEEN ? AND ?", [$startOfWeek, $today]);
            
            // This month vs last month
            $thisMonthIncome = DB::fetchColumn("SELECT SUM(amount) FROM incomes WHERE status = 'approved' AND DATE(date_created) BETWEEN ? AND ?", [$startOfMonth, $today]);
            $lastMonthIncome = DB::fetchColumn("SELECT SUM(amount) FROM incomes WHERE status = 'approved' AND DATE(date_created) BETWEEN ? AND ?", [$startOfLastMonth, $endOfLastMonth]);
            $thisMonthExpense = DB::fetchColumn("SELECT SUM(amount) FROM expenses WHERE status = 'approved' AND DATE(date_created) BETWEEN ? AND ?", [$startOfMonth, $today]);
            $lastMonthExpense = DB::fetchColumn("SELECT SUM(amount) FROM expenses WHERE status = 'approved' AND DATE(date_created) BETWEEN ? AND ?", [$startOfLastMonth, $endOfLastMonth]);

            // Last 6 months income and expenses by month
            $lastSixMonthsIncome = DB::fetchAll("SELECT DATE_FORMAT(date_created, '%Y-%m') as month, SUM(amount) as total FROM incomes WHERE status = 'approved' AND DATE(date_created) BETWEEN DATE_SUB(?, INTERVAL 6 MONTH) AND ? GROUP BY DATE_FORMAT(date_created, '%Y-%m') ORDER BY month", [$today, $today]);
            $lastSixMonthsExpense = DB::fetchAll("SELECT DATE_FORMAT(date_created, '%Y-%m') as month, SUM(amount) as total FROM expenses WHERE status = 'approved' AND DATE(date_created) BETWEEN DATE_SUB(?, INTERVAL 6 MONTH) AND ? GROUP BY DATE_FORMAT(date_created, '%Y-%m') ORDER BY month", [$today, $today]);

            // This year vs last year
            $thisYearIncome = DB::fetchColumn("SELECT SUM(amount) FROM incomes WHERE status = 'approved' AND DATE(date_created) BETWEEN ? AND ?", [$startOfYear, $today]);
            $lastYearIncome = DB::fetchColumn("SELECT SUM(amount) FROM incomes WHERE status = 'approved' AND DATE(date_created) BETWEEN ? AND ?", [$startOfLastYear, $endOfLastYear]);
            $thisYearExpense = DB::fetchColumn("SELECT SUM(amount) FROM expenses WHERE status = 'approved' AND DATE(date_created) BETWEEN ? AND ?", [$startOfYear, $today]);
            $lastYearExpense = DB::fetchColumn("SELECT SUM(amount) FROM expenses WHERE status = 'approved' AND DATE(date_created) BETWEEN ? AND ?", [$startOfLastYear, $endOfLastYear]);

            // Money distribution (bank balances)
            $moneyDistribution = DB::fetchAll("SELECT bank, SUM(amount) as total FROM incomes WHERE status = 'approved' GROUP BY bank");

            // Income by category (this month)
            $incomeByCategory = DB::fetchAll("SELECT category, SUM(amount) as total FROM incomes WHERE status = 'approved' AND DATE(date_created) BETWEEN ? AND ? GROUP BY category ORDER BY total DESC", [$startOfMonth, $today]);

            // Expense by category (this month)
            $expenseByCategory = DB::fetchAll("SELECT category, SUM(amount) as total FROM expenses WHERE status = 'approved' AND DATE(date_created) BETWEEN ? AND ? GROUP BY category ORDER BY total DESC", [$startOfMonth, $today]);
            
            // Get projected incomes and expenses for comparison
            $thisMonthProjectedIncome = ProjectionManager::getProjectedIncomeTotal($startOfMonth, $today);
            $thisMonthProjectedExpense = ProjectionManager::getProjectedExpenseTotal($startOfMonth, $today);
            
            // Calculate variance between actual and projected
            $incomeVariance = $thisMonthIncome - $thisMonthProjectedIncome;
            $expenseVariance = $thisMonthExpense - $thisMonthProjectedExpense;
            
            // Calculate income, expense, and profit trends by month (for the year)
            $monthlyTrends = DB::fetchAll("
                SELECT 
                    months.month,
                    COALESCE(income.total, 0) as income,
                    COALESCE(expense.total, 0) as expense,
                    COALESCE(income.total, 0) - COALESCE(expense.total, 0) as profit
                FROM (
                    SELECT DATE_FORMAT(CONCAT(YEAR(?), '-', LPAD(m.month, 2, '0'), '-01'), '%Y-%m') as month
                    FROM (
                        SELECT 1 as month UNION SELECT 2 UNION SELECT 3 UNION SELECT 4
                        UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8
                        UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12
                    ) m
                    WHERE CONCAT(YEAR(?), '-', LPAD(m.month, 2, '0'), '-01') <= ?
                ) months
                LEFT JOIN (
                    SELECT DATE_FORMAT(date_created, '%Y-%m') as month, SUM(amount) as total
                    FROM incomes
                    WHERE status = 'approved' AND YEAR(date_created) = YEAR(?)
                    GROUP BY DATE_FORMAT(date_created, '%Y-%m')
                ) income ON months.month = income.month
                LEFT JOIN (
                    SELECT DATE_FORMAT(date_created, '%Y-%m') as month, SUM(amount) as total
                    FROM expenses
                    WHERE status = 'approved' AND YEAR(date_created) = YEAR(?)
                    GROUP BY DATE_FORMAT(date_created, '%Y-%m')
                ) expense ON months.month = expense.month
                ORDER BY months.month
            ", [$today, $today, $today, $today, $today]);
            
            // Calculate cash flow (current cash position)
            $totalIncome = DB::fetchColumn("SELECT SUM(amount) FROM incomes WHERE status = 'approved'");
            $totalExpense = DB::fetchColumn("SELECT SUM(amount) FROM expenses WHERE status = 'approved'");
            $cashPosition = $totalIncome - $totalExpense;
            
            // Pending transactions
            $pendingIncome = DB::fetchColumn("SELECT SUM(amount) FROM incomes WHERE status = 'pending'");
            $pendingExpense = DB::fetchColumn("SELECT SUM(amount) FROM expenses WHERE status = 'pending'");
            
            // Income vs Expense Comparison data
            // Get the last 12 months for comparison
            $incomeExpenseComparisonData = DB::fetchAll("
                SELECT 
                    months.month_label,
                    COALESCE(income.total, 0) as income,
                    COALESCE(expense.total, 0) as expense
                FROM (
                    SELECT 
                        DATE_FORMAT(date_sub(?, interval n.n month), '%b %Y') as month_label,
                        DATE_FORMAT(date_sub(?, interval n.n month), '%Y-%m-01') as month_start,
                        LAST_DAY(date_sub(?, interval n.n month)) as month_end
                    FROM (
                        SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3
                        UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7
                        UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11
                    ) n
                    ORDER BY month_start DESC
                ) months
                LEFT JOIN (
                    SELECT 
                        DATE_FORMAT(date_created, '%Y-%m') as month,
                        SUM(amount) as total
                    FROM incomes
                    WHERE status = 'approved' 
                    AND DATE(date_created) >= DATE_SUB(?, INTERVAL 12 MONTH)
                    GROUP BY DATE_FORMAT(date_created, '%Y-%m')
                ) income ON DATE_FORMAT(months.month_start, '%Y-%m') = income.month
                LEFT JOIN (
                    SELECT 
                        DATE_FORMAT(date_created, '%Y-%m') as month,
                        SUM(amount) as total
                    FROM expenses
                    WHERE status = 'approved'
                    AND DATE(date_created) >= DATE_SUB(?, INTERVAL 12 MONTH)
                    GROUP BY DATE_FORMAT(date_created, '%Y-%m')
                ) expense ON DATE_FORMAT(months.month_start, '%Y-%m') = expense.month
                ORDER BY months.month_start
            ", [$today, $today, $today, $today, $today]);
            
            $incomeExpenseLabels = array_column($incomeExpenseComparisonData, 'month_label');
            $incomeExpenseIncome = array_column($incomeExpenseComparisonData, 'income');
            $incomeExpenseExpense = array_column($incomeExpenseComparisonData, 'expense');
            
            jsonResponse([
                'today' => [
                    'income' => (float)$todayIncome,
                    'expense' => (float)$todayExpense,
                    'net' => (float)$todayIncome - (float)$todayExpense
                ],
                'thisWeek' => [
                    'income' => (float)$thisWeekIncome,
                    'expense' => (float)$thisWeekExpense,
                    'net' => (float)$thisWeekIncome - (float)$thisWeekExpense
                ],
                'thisMonth' => [
                    'income' => (float)$thisMonthIncome,
                    'expense' => (float)$thisMonthExpense,
                    'net' => (float)$thisMonthIncome - (float)$thisMonthExpense,
                    'projectedIncome' => (float)$thisMonthProjectedIncome,
                    'projectedExpense' => (float)$thisMonthProjectedExpense,
                    'incomeVariance' => (float)$incomeVariance,
                    'expenseVariance' => (float)$expenseVariance
                ],
                'lastMonth' => [
                    'income' => (float)$lastMonthIncome,
                    'expense' => (float)$lastMonthExpense,
                    'net' => (float)$lastMonthIncome - (float)$lastMonthExpense
                ],
                'lastSixMonths' => [
                    'labels' => array_column($lastSixMonthsIncome, 'month'),
                    'income' => array_column($lastSixMonthsIncome, 'total'),
                    'expense' => array_column($lastSixMonthsExpense, 'total')
                ],
                'thisYear' => [
                    'income' => (float)$thisYearIncome,
                    'expense' => (float)$thisYearExpense,
                    'net' => (float)$thisYearIncome - (float)$thisYearExpense
                ],
                'lastYear' => [
                    'income' => (float)$lastYearIncome,
                    'expense' => (float)$lastYearExpense,
                    'net' => (float)$lastYearIncome - (float)$lastYearExpense
                ],
                'moneyDistribution' => [
                    'labels' => array_column($moneyDistribution, 'bank'),
                    'amounts' => array_column($moneyDistribution, 'total')
                ],
                'incomeByCategory' => [
                    'labels' => array_column($incomeByCategory, 'category'),
                    'amounts' => array_column($incomeByCategory, 'total')
                ],
                'expenseByCategory' => [
                    'labels' => array_column($expenseByCategory, 'category'),
                    'amounts' => array_column($expenseByCategory, 'total')
                ],
                'monthlyTrends' => $monthlyTrends,
                'cashPosition' => [
                    'totalIncome' => (float)$totalIncome,
                    'totalExpense' => (float)$totalExpense,
                    'netPosition' => (float)$cashPosition,
                    'pendingIncome' => (float)$pendingIncome,
                    'pendingExpense' => (float)$pendingExpense
                ],
                'incomeExpenseComparison' => [
                    'labels' => $incomeExpenseLabels,
                    'income' => array_map('floatval', $incomeExpenseIncome),
                    'expense' => array_map('floatval', $incomeExpenseExpense)
                ]
            ]);
        });
    });

    $router->mount('/bills', function () use ($router) {

        // Add new endpoint to get month options
        $router->get('/month-options', function() {
            if (!Users::can('READ')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }
            
            jsonResponse(getMonthOptions());
        });

        // Add endpoint to get vendors with their due amounts
        $router->get('/vendors-due', function() {
            if (!Users::can('READ')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }

            try {
                $db = DB::getInstance();
                
                // Get current month for filtering current dues
                $currentMonth = date('Y-m');
                
                // Updated query to fetch both bills and corresponding expenses
                $stmt = $db->query("
                    SELECT 
                        v.id,
                        v.vendor_name,
                        v.contact_person,
                        v.phone_number,
                        COALESCE(SUM(b.amount), 0) as total_billed,
                        COALESCE(
                            (SELECT SUM(amount) 
                            FROM bw_bills 
                            WHERE vendor_id = v.id AND bill_month = '$currentMonth'), 
                            0
                        ) as current_month_billed,
                        COALESCE(
                            (SELECT COUNT(*) 
                            FROM bw_bills 
                            WHERE vendor_id = v.id), 
                            0
                        ) as bill_count,
                        COALESCE(
                            (SELECT SUM(amount) 
                            FROM expenses 
                            WHERE expense_by = v.vendor_name AND category = 'Bandwidth Bill'),
                            0
                        ) as total_paid
                    FROM bw_vendors v
                    LEFT JOIN bw_bills b ON v.id = b.vendor_id
                    GROUP BY v.id
                    ORDER BY v.vendor_name
                ");
                
                $vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Calculate remaining dues
                foreach ($vendors as &$vendor) {
                    $vendor['total_due'] = $vendor['total_billed'] - $vendor['total_paid'];
                    $vendor['current_month_due'] = $vendor['current_month_billed']; // Keep the current month due as is since we want to show what's billed this month
                    
                    // Add a field to show how much has been paid already (might be useful for frontend)
                    $vendor['total_paid_amount'] = $vendor['total_paid'];
                    
                    // Check if fully paid
                    $vendor['is_fully_paid'] = $vendor['total_due'] <= 0;
                }
                
                jsonResponse($vendors);
            } catch (Exception $e) {
                jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
            }
        });

        // Get all bills with their metadata
        $router->get('/', function () {
            if (!Users::can('READ')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }

            try {
                $db = DB::getInstance();
                $stmt = $db->query("
                    SELECT 
                        b.id,
                        b.vendor_id,
                        v.vendor_name,
                        b.bill_number,
                        b.bill_month,
                        b.bill_month_ts,
                        b.amount,
                        b.notes,
                        b.created_at,
                        b.updated_at
                    FROM bw_bills b
                    LEFT JOIN bw_vendors v ON b.vendor_id = v.id
                    ORDER BY b.created_at DESC
                ");

                $bills = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get metadata for each bill
                foreach ($bills as &$bill) {
                    $metaStmt = $db->prepare("
                        SELECT 
                            id,
                            type,
                            quantity, 
                            unit_price,
                            total
                        FROM bw_metadata
                        WHERE bill_id = :bill_id
                    ");
                    $metaStmt->execute(['bill_id' => $bill['id']]);
                    $bill['metadata'] = $metaStmt->fetchAll(PDO::FETCH_ASSOC);
                }
                
                jsonResponse($bills);
            } catch (Exception $e) {
                jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
            }
        });
        
        // Create a new bill with metadata
        $router->post('/new', function () {
            if (!Users::can('CREATE')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }

            $data = json_decode(file_get_contents('php://input'), true);

            // Validate required fields
            if (empty($data['vendor_id']) || empty($data['bill_number']) || empty($data['bill_month'])) {
                jsonResponse(['error' => 'Vendor, bill number and bill month are required'], 400);
            }

            if (empty($data['metadata']) || !is_array($data['metadata']) || count($data['metadata']) === 0) {
                jsonResponse(['error' => 'At least one bandwidth detail item is required'], 400);
            }

            foreach ($data['metadata'] as $item) {
                if (empty($item['type']) || !isset($item['quantity']) || !isset($item['unit_price'])) {
                    jsonResponse(['error' => 'Each bandwidth item must have type, quantity and unit price'], 400);
                }
            }

            try {
                $db = DB::getInstance();
                
                // Check if bill number already exists
                $checkStmt = $db->prepare("
                    SELECT COUNT(*) FROM bw_bills 
                    WHERE bill_number = :bill_number
                ");
                $checkStmt->execute([
                    'bill_number' => $data['bill_number']
                ]);

                if ($checkStmt->fetchColumn() > 0) {
                    jsonResponse(['error' => 'A bill with this number already exists'], 400);
                }

                // Use the provided timestamp directly
                $bill_month = $data['bill_month'];
                $bill_month_ts = $data['bill_month_ts'];

                $db->beginTransaction();

                // Insert bill record
                $billStmt = $db->prepare("
                    INSERT INTO bw_bills 
                    (vendor_id, bill_number, bill_month, bill_month_ts, amount, notes)
                    VALUES 
                    (:vendor_id, :bill_number, :bill_month, :bill_month_ts, :amount, :notes)
                ");

                $billData = [
                    'vendor_id' => $data['vendor_id'],
                    'bill_number' => $data['bill_number'],
                    'bill_month' => $bill_month,
                    'bill_month_ts' => $bill_month_ts,
                    'amount' => $data['amount'],
                    'notes' => $data['notes'] ?? null
                ];

                $billStmt->execute($billData);
                $billId = $db->lastInsertId();

                // Insert metadata records
                $metadataStmt = $db->prepare("
                    INSERT INTO bw_metadata 
                    (bill_id, type, quantity, unit_price, total)
                    VALUES 
                    (:bill_id, :type, :quantity, :unit_price, :total)
                ");

                foreach ($data['metadata'] as $item) {
                    $metadataData = [
                        'bill_id' => $billId,
                        'type' => $item['type'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total' => $item['total']
                    ];
                    $metadataStmt->execute($metadataData);
                }

                $db->commit();

                jsonResponse([
                    'success' => true, 
                    'message' => 'Bandwidth bill created successfully',
                    'bill_id' => $billId
                ]);
            } catch (PDOException $e) {
                $db->rollBack();
                jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
            }
        });

        // Get a single bill with its metadata
        $router->get('/{id}', function ($id) {
            if (!Users::can('READ')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }

            try {
                $db = DB::getInstance();
                
                // Get bill data
                $billStmt = $db->prepare("
                    SELECT 
                        b.id,
                        b.vendor_id,
                        v.vendor_name,
                        b.bill_number,
                        b.bill_month,
                        b.bill_month_ts,
                        b.amount,
                        b.notes,
                        b.created_at,
                        b.updated_at
                    FROM bw_bills b
                    LEFT JOIN bw_vendors v ON b.vendor_id = v.id
                    WHERE b.id = :id
                ");
                $billStmt->execute(['id' => $id]);
                
                $bill = $billStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$bill) {
                    jsonResponse(['error' => 'Bill not found'], 404);
                }
                
                // Get metadata
                $metaStmt = $db->prepare("
                    SELECT 
                        id,
                        type,
                        quantity, 
                        unit_price,
                        total
                    FROM bw_metadata
                    WHERE bill_id = :bill_id
                ");
                $metaStmt->execute(['bill_id' => $id]);
                $bill['metadata'] = $metaStmt->fetchAll(PDO::FETCH_ASSOC);
                
                jsonResponse($bill);
            } catch (Exception $e) {
                jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
            }
        });

        // Update an existing bill
        $router->post('/update', function () {
            if (!Users::can('UPDATE')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }

            $data = json_decode(file_get_contents('php://input'), true);

            // Validate required fields
            if (empty($data['id']) || empty($data['vendor_id']) || empty($data['bill_number']) || empty($data['bill_month'])) {
                jsonResponse(['error' => 'Bill ID, vendor, bill number and bill month are required'], 400);
            }

            try {
                $db = DB::getInstance();
                
                // Check if bill exists
                $checkStmt = $db->prepare("
                    SELECT COUNT(*) FROM bw_bills 
                    WHERE id = :id
                ");
                $checkStmt->execute([
                    'id' => $data['id']
                ]);

                if ($checkStmt->fetchColumn() == 0) {
                    jsonResponse(['error' => 'Bill not found'], 404);
                }
                
                // Check if the updated bill number already exists for a different bill
                $numberCheckStmt = $db->prepare("
                    SELECT COUNT(*) FROM bw_bills 
                    WHERE bill_number = :bill_number AND id != :id
                ");
                $numberCheckStmt->execute([
                    'bill_number' => $data['bill_number'],
                    'id' => $data['id']
                ]);

                if ($numberCheckStmt->fetchColumn() > 0) {
                    jsonResponse(['error' => 'A different bill with this number already exists'], 400);
                }

                // Format bill_month for storage
                $bill_month = $data['bill_month'];
                $bill_month_ts = date('Y-m-d H:i:s', strtotime($bill_month . '-01'));

                $db->beginTransaction();

                // Update bill record
                $billStmt = $db->prepare("
                    UPDATE bw_bills 
                    SET 
                        vendor_id = :vendor_id,
                        bill_number = :bill_number,
                        bill_month = :bill_month,
                        bill_month_ts = :bill_month_ts,
                        amount = :amount,
                        notes = :notes
                    WHERE id = :id
                ");

                $billData = [
                    'id' => $data['id'],
                    'vendor_id' => $data['vendor_id'],
                    'bill_number' => $data['bill_number'],
                    'bill_month' => $bill_month,
                    'bill_month_ts' => $bill_month_ts,
                    'amount' => $data['amount'],
                    'notes' => $data['notes'] ?? null
                ];

                $billStmt->execute($billData);
                
                // Delete existing metadata
                $deleteStmt = $db->prepare("
                    DELETE FROM bw_metadata 
                    WHERE bill_id = :bill_id
                ");
                $deleteStmt->execute(['bill_id' => $data['id']]);
                
                // Insert new metadata records
                $metadataStmt = $db->prepare("
                    INSERT INTO bw_metadata 
                    (bill_id, type, quantity, unit_price, total)
                    VALUES 
                    (:bill_id, :type, :quantity, :unit_price, :total)
                ");

                foreach ($data['metadata'] as $item) {
                    $metadataData = [
                        'bill_id' => $data['id'],
                        'type' => $item['type'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total' => $item['total']
                    ];
                    $metadataStmt->execute($metadataData);
                }

                $db->commit();

                jsonResponse([
                    'success' => true, 
                    'message' => 'Bandwidth bill updated successfully'
                ]);
            } catch (PDOException $e) {
                $db->rollBack();
                jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
            }
        });

        // Delete a bill
        $router->post('/delete', function () {
            if (!Users::can('DELETE')) {
                jsonResponse(['error' => 'Permission denied'], 403);
            }

            $data = json_decode(file_get_contents('php://input'), true);

            // Validate required fields
            if (empty($data['id'])) {
                jsonResponse(['error' => 'Bill ID is required'], 400);
            }

            try {
                $db = DB::getInstance();
                
                // Check if bill exists
                $checkStmt = $db->prepare("
                    SELECT COUNT(*) FROM bw_bills 
                    WHERE id = :id
                ");
                $checkStmt->execute([
                    'id' => $data['id']
                ]);

                if ($checkStmt->fetchColumn() == 0) {
                    jsonResponse(['error' => 'Bill not found'], 404);
                }

                $db->beginTransaction();
                
                // Delete metadata first (would happen automatically due to ON DELETE CASCADE,
                // but being explicit for clarity)
                $deleteMetaStmt = $db->prepare("
                    DELETE FROM bw_metadata 
                    WHERE bill_id = :bill_id
                ");
                $deleteMetaStmt->execute(['bill_id' => $data['id']]);
                
                // Delete bill
                $deleteBillStmt = $db->prepare("
                    DELETE FROM bw_bills 
                    WHERE id = :id
                ");
                $deleteBillStmt->execute(['id' => $data['id']]);

                $db->commit();

                jsonResponse([
                    'success' => true, 
                    'message' => 'Bandwidth bill deleted successfully'
                ]);
            } catch (PDOException $e) {
                $db->rollBack();
                jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
            }
        });

    });

});

$router->both('/test', function () {
    require_once 'views/test.php';
});

$router->get('/logout', function () {
    Users::logout();
    header('Location: /login');
});

$router->run();