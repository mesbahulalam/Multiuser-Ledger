<?php
require_once 'classes/DB.php';
require_once 'classes/Router.php';
require_once 'classes/Users.php';
require_once 'classes/FinanceManager.php';

define('DEFAULT_DATA_ORDER', 'DESC');

function resolve($var, $default = null) {
    return isset($var) ? $var : $default;
}

function saveToFile($array = [], $filename = null) {
    if(!$filename) return;
    $phpCode = "<?php\n\n";
    $phpCode .= "// This file is auto-generated. Do not edit directly.\n\n";
    $phpCode .= "return " . var_export($array, true) . ";\n";
    $phpCode .= "?>";
    if (file_put_contents($filename, $phpCode) !== false) {
        return true;
    } else {
        return false;
    }
}

Users::init();
$router = new Router();

// Redirect to login page if not logged in
if (!isset($_SESSION['user_id'])) {
    $router->before('/(dashboard|api).*', function()  {
        header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit();
    });
}

$router->get('/', function () {
    include 'views/index.php';
});

$router->get('/dashboard', function () {
    if(!isset($_SESSION['user_id'])) die(header('Location: /login'));
    
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
    require_once('views/login.php');
});


$router->mount('/api', function () use ($router) {
    $router->get('/', function () {
        echo 'Welcome to the API';
    });
    
    $router->mount('/users', function () use ($router) {

        $router->get('/', function () use ($app) {
            if (!Users::hasPermission(resolve($_SESSION['user_id'], $_COOKIE['user_id']), 'READ')) {
                http_response_code(403);
                echo json_encode(['error' => 'Permission denied']);
                return;
            }
    
            $users = Users::getAllUsers();
            header('Content-Type: application/json');
            echo json_encode($users);
        });
    
        $router->get('/namelist', function () use ($app) {
            if (!Users::hasPermission(resolve($_SESSION['user_id'], $_COOKIE['user_id']), 'READ')) {
                http_response_code(403);
                echo json_encode(['error' => 'Permission denied']);
                return;
            }
    
            $users = Users::getAllUserNames();
            $users = array_map(function($user) {
                return ['id' => $user['user_id'], 'text' => $user['username'], 'balance' => ''];
            }, $users);
            header('Content-Type: application/json');
            echo json_encode($users);
        });
    
        $router->post('/new', function () use ($app) {
            if (!Users::hasPermission(resolve($_SESSION['user_id'], $_COOKIE['user_id']), 'CREATE')) {
                http_response_code(403);
                echo json_encode(['error' => 'Permission denied']);
                return;
            }
    
            $data = json_decode(file_get_contents('php://input'), true);
            
            $newUserId = Users::createUser(
                $data['username'], 
                $data['email'], 
                $data['password'],
                $data['role_name']
            );
    
            header('Content-Type: application/json');
            if ($newUserId) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'User created successfully', 
                    'user_id' => $newUserId
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create user']);
            }
        });
    
        $router->post('/delete', function () use ($app) {
            $data = json_decode(file_get_contents('php://input'), true);
    
            if (!Users::hasPermission(resolve($_SESSION['user_id'], $_COOKIE['user_id']), 'DELETE')) {
                http_response_code(403);
                echo json_encode(['error' => 'Permission denied']);
                return;
            }
    
            $deleted = Users::deleteUser($data['user_id']);
            header('Content-Type: application/json');
            if ($deleted) {
                echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to delete user']);
            }
        });
    
        $router->post('/disable', function () use ($app) {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!Users::hasPermission(resolve($_SESSION['user_id'], $_COOKIE['user_id']), 'UPDATE')) {
                http_response_code(403);
                echo json_encode(['error' => 'Permission denied']);
                return;
            }
            
            $deleted = Users::disableUser($data['user_id']);
            header('Content-Type: application/json');
            if ($deleted) {
                echo json_encode(['success' => true, 'message' => 'User disabled successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to disable user']);
            }
        });
    
        $router->post('/update', function () use ($app) {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!Users::hasPermission(resolve($_SESSION['user_id'], $_COOKIE['user_id']), 'UPDATE')) {
                http_response_code(403);
                echo json_encode(['error' => 'Permission denied']);
                return;
            }
    
            $updated = Users::updateUser($data['id'], [
                'username' => $data['username'],
                'email' => $data['email'],
                'role_name' => $data['role_name']
            ]);
    
            header('Content-Type: application/json');
            if ($updated) {
                echo json_encode(['success' => true, 'message' => 'User updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update user']);
            }
        });
    
    });
    
    $router->mount('/roles', function () use ($router) {

        $router->post('/', function () {
            // Check for UPDATE permission
            if (!Users::hasPermission(resolve($_SESSION['user_id'], $_COOKIE['user_id']), 'UPDATE')) {
                http_response_code(403);
                echo json_encode(['error' => 'Permission denied']);
                return;
            }
    
            // Get and validate input data
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['roles']) || !is_array($data['roles'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid input format']);
                return;
            }
    
            try {
                // Start transaction
                DB::getInstance()->beginTransaction();
    
                // Clear existing permissions for the role
                foreach ($data['roles'] as $role) {
                    $roleId = $role['role_id'];
                    $permissions = $role['permissions'];
    
                    // Delete existing permissions
                    $sql = "DELETE FROM role_permissions WHERE role_id = ?";
                    DB::query($sql, [$roleId]);
    
                    // Insert new permissions
                    $sql = "INSERT INTO role_permissions (role_id, permission_id) 
                            SELECT ?, permission_id 
                            FROM permissions 
                            WHERE permission_name IN (" . str_repeat('?,', count($permissions) - 1) . "?)";
                    
                    $params = array_merge([$roleId], $permissions);
                    DB::query($sql, $params);
                }
    
                // Commit transaction
                DB::getInstance()->commit();
    
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Role permissions updated successfully']);
    
            } catch (Exception $e) {
                // Rollback on error
                DB::getInstance()->rollBack();
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update role permissions: ' . $e->getMessage()]);
            }
        });
    
        $router->post('/new', function () {
            // Check for CREATE permission
            if (!Users::hasPermission(resolve($_SESSION['user_id'], $_COOKIE['user_id']), 'CREATE')) {
                http_response_code(403);
                echo json_encode(['error' => 'Permission denied']);
                return;
            }
    
            // Get and validate input data
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['role_name']) || !is_string($data['role_name'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid input format']);
                return;
            }
    
            // Create the new role
            $roleId = Users::createRole($data['role_name']);
    
            header('Content-Type: application/json');
            if ($roleId) {
                echo json_encode(['success' => true, 'message' => 'Role created successfully', 'role_id' => $roleId]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create role']);
            }
        });
    
    });
    
    $router->mount('/finance', function () use ($router) {
        
        $router->get('/suggestions/{table}/{col}', function ($table, $col) use ($app) {
            // if user doesn't have read permission
            if (!Users::hasPermission($_SESSION['user_id'], 'READ')) {
                http_response_code(403);
                echo json_encode(['error' => 'Permission denied']);
                return;
            }

            header('Content-Type: application/json');
            $results = FinanceManager::getDistinctColumn($table, $col);
            $formatted = array_map(function($item, $index) {
                return [
                    'id' => $index,
                    'text' => $item
                ];
            }, $results, array_keys($results));
            echo json_encode($formatted);
        });

        // Add new income - reordered columns to match schema
        $router->post('/income/add', function () use ($app) {
            if (!Users::hasPermission($_SESSION['user_id'], 'CREATE')) {
                http_response_code(403);
                echo json_encode(['error' => 'Permission denied']);
                return;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            $status = Users::hasPermission($_SESSION['user_id'], 'APPROVE') ? 'approved' : 'pending';
            $result = FinanceManager::addIncome([
                'entry_by' => $_SESSION['user_id'],
                'approved_by' => null,
                'income_from' => $data['income_from'],
                'category' => $data['category'],
                'amount' => $data['amount'],
                'method' => $data['method'],
                'bank' => $data['bank'],
                'account_number' => $data['account_number'] ?? null,
                'transaction_number' => $data['transaction_number'] ?? null,
                'notes' => $data['notes'],
                'attachment_id' => $data['attachment_id'] ?? null,
                'status' => $status
            ]);

            header('Content-Type: application/json');
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Income added successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to add income']);
            }
        });

        // Add new expense - reordered columns to match schema
        $router->post('/expense/add', function () use ($app) {
            if (!Users::hasPermission($_SESSION['user_id'], 'CREATE')) {
                http_response_code(403);
                echo json_encode(['error' => 'Permission denied']);
                return;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            $status = Users::hasPermission($_SESSION['user_id'], 'APPROVE') ? 'approved' : 'pending';
            
            // If user doesn't have APPROVE permission, they can only create expenses for themselves
            if (!Users::hasPermission($_SESSION['user_id'], 'APPROVE')) {
                $data['expense_by'] = $_SESSION['user_id'];
            }

            $result = FinanceManager::addExpense([
                'entry_by' => $_SESSION['user_id'],
                'approved_by' => null,
                'expense_by' => $data['expense_by'],
                'category' => $data['category'],
                'purpose' => $data['purpose'],
                'amount' => $data['amount'],
                'method' => $data['method'],
                'bank' => $data['bank'],
                'account_number' => $data['account_number'] ?? null,
                'transaction_number' => $data['transaction_number'] ?? null,
                'notes' => $data['notes'],
                'attachment_id' => $data['attachment_id'] ?? null,
                'status' => $status
            ]);

            header('Content-Type: application/json');
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Expense added successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to add expense']);
            }
        });

        $router->post('/expense/approve', function () use ($app) {
            if (!Users::hasPermission($_SESSION['user_id'], 'APPROVE')) {
                http_response_code(403);
                echo json_encode(['error' => 'Permission denied']);
                return;
            }
        
            $data = json_decode(file_get_contents('php://input'), true);
            $result = FinanceManager::approveExpense($data['id']);
        
            header('Content-Type: application/json');
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Expense approved successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to approve expense']);
            }
        });

        // Add income approval endpoint
        $router->post('/income/approve', function () use ($app) {
            if (!Users::hasPermission($_SESSION['user_id'], 'APPROVE')) {
                http_response_code(403);
                echo json_encode(['error' => 'Permission denied']);
                return;
            }
        
            $data = json_decode(file_get_contents('php://input'), true);
            $result = FinanceManager::approveIncome($data['id']);
        
            header('Content-Type: application/json');
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Income approved successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to approve income']);
            }
        });

        // Delete income
        $router->post('/income/delete', function () use ($app) {
            if (!Users::hasPermission($_SESSION['user_id'], 'DELETE')) {
                http_response_code(403);
                echo json_encode(['error' => 'Permission denied']);
                return;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            $result = FinanceManager::deleteIncome($data['id']);

            header('Content-Type: application/json');
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Income record deleted successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to delete income record']);
            }
        });

        // Delete expense
        $router->post('/expense/delete', function () use ($app) {
            if (!Users::hasPermission($_SESSION['user_id'], 'DELETE')) {
                http_response_code(403);
                echo json_encode(['error' => 'Permission denied']);
                return;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            $result = FinanceManager::deleteExpense($data['id']);

            header('Content-Type: application/json');
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Expense record deleted successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to delete expense record']);
            }
        });

        // Get incomes with pagination and filters
        $router->get('/incomes', function () use ($app) {
            if (!Users::hasPermission($_SESSION['user_id'], 'READ')) {
                http_response_code(403);
                echo json_encode(['error' => 'Permission denied']);
                return;
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

            $items = FinanceManager::getIncomes($filters, $page, $itemsPerPage, $search);
            $total = FinanceManager::getTotalIncomes($filters, $search);
            $totalPages = ceil($total / $itemsPerPage);

            header('Content-Type: application/json');
            echo json_encode([
                'items' => $items,
                'totalPages' => $totalPages,
                'currentPage' => $page
            ]);
        });

        // Get expenses with pagination and filters
        $router->get('/expenses', function () use ($app) {
            if (!Users::hasPermission($_SESSION['user_id'], 'READ')) {
                http_response_code(403);
                echo json_encode(['error' => 'Permission denied']);
                return;
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

            $items = FinanceManager::getExpenses($filters, $page, $itemsPerPage, $search);
            $total = FinanceManager::getTotalExpenses($filters, $search);
            $totalPages = ceil($total / $itemsPerPage);

            header('Content-Type: application/json');
            echo json_encode([
                'items' => $items,
                'totalPages' => $totalPages,
                'currentPage' => $page
            ]);
        });

        // Add new endpoint for getting statuses
        $router->get('/statuses', function () {
            if (!Users::hasPermission($_SESSION['user_id'], 'READ')) {
                http_response_code(403);
                echo json_encode(['error' => 'Permission denied']);
                return;
            }

            $includeDeleted = Users::hasPermission($_SESSION['user_id'], 'DELETE');
            $statuses = FinanceManager::getAvailableStatuses($includeDeleted);
            
            header('Content-Type: application/json');
            echo json_encode($statuses);
        });

    });

    $router->mount('/salary', function () use ($router) {
        $router->post('/add', function () {
            if (!Users::hasPermission(resolve($_SESSION['user_id'], $_COOKIE['user_id']), 'CREATE')) {
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied']);
            return;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            // Convert month string to date format (first day of the month)
            $monthDate = date('Y-m-d', strtotime('first day of ' . $data['month']));

            try {
            $db = DB::getInstance();
            
            // Check if salary record already exists for this user and month
            $checkStmt = $db->prepare("
                SELECT COUNT(*) FROM salaries 
                WHERE user_id = :user_id AND month = :month
            ");
            $checkStmt->execute([
                'user_id' => $data['user_id'],
                'month' => $monthDate
            ]);
            
            if ($checkStmt->fetchColumn() > 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Salary record already exists for this month']);
                return;
            }

            $salaryData = [
                'user_id' => $data['user_id'],
                'approved_by' => $data['approved_by'],
                'basic_salary' => $data['basic_salary'],
                'allowances' => $data['allowances'],
                'deductions' => $data['deductions'],
                'net_salary' => $data['net_salary'],
                'month' => $monthDate,
                'payment_details' => $data['payment_details']
            ];

            $stmt = $db->prepare("
                INSERT INTO salaries 
                (user_id, approved_by, basic_salary, allowances, deductions, net_salary, month, payment_details)
                VALUES 
                (:user_id, :approved_by, :basic_salary, :allowances, :deductions, :net_salary, :month, :payment_details)
            ");
            
            if ($stmt->execute($salaryData)) {
                echo json_encode(['success' => true, 'message' => 'Salary entry added successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to add salary entry']);
            }
            } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            }
        });

        $router->get('/history/{id}', function($id) {
            if (!Users::hasPermission(resolve($_SESSION['user_id'], $_COOKIE['user_id']), 'READ')) {
                http_response_code(403);
                echo json_encode(['error' => 'Permission denied']);
                return;
            }
        
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = 10;
            $offset = ($page - 1) * $limit;
        
            try {
                $db = DB::getInstance();
                
                $stmt = $db->prepare("
                    SELECT 
                        id,
                        amount,
                        date_created as date,
                        status
                    FROM expenses 
                    WHERE expense_by = :user_id 
                    AND purpose LIKE '%salary%'
                    ORDER BY date_created DESC
                    LIMIT :limit OFFSET :offset
                ");
                
                $stmt->bindValue(':user_id', $id, PDO::PARAM_INT);
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                $stmt->execute();
                
                $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                header('Content-Type: application/json');
                echo json_encode(['payments' => $payments]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            }
        });


        $router->get('/summary', function() {
            try {                
                $query = "
                WITH LastSalary AS (
                    SELECT 
                        user_id,
                        net_salary,
                        ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY date_created DESC) as rn
                    FROM salaries
                ),
                AccumulatedSalary AS (
                    SELECT 
                        user_id,
                        SUM(net_salary) as total_salary
                    FROM salaries
                    GROUP BY user_id
                ),
                PaidAmount AS (
                    SELECT 
                        expense_by as user_id,
                        SUM(amount) as paid_amount
                    FROM expenses 
                    WHERE purpose LIKE '%salary%'
                    AND status = 'approved'
                    GROUP BY expense_by
                )
                SELECT 
                    u.user_id,
                    u.username,
                    COALESCE(ls.net_salary, 0) as latest_salary,
                    COALESCE(acs.total_salary, 0) as accumulated_salary,
                    COALESCE(pa.paid_amount, 0) as paid_amount,
                    COALESCE(acs.total_salary, 0) - COALESCE(pa.paid_amount, 0) as payable_amount
                FROM users u
                LEFT JOIN LastSalary ls ON u.user_id = ls.user_id AND ls.rn = 1
                LEFT JOIN AccumulatedSalary acs ON u.user_id = acs.user_id
                LEFT JOIN PaidAmount pa ON u.user_id = pa.user_id
                WHERE u.is_active = 1 AND COALESCE(ls.net_salary, 0) != 0
                ORDER BY u.username
                ";

                $results = DB::fetchAll($query);

                header('Content-Type: application/json');
                echo json_encode($results);
                
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            }
        });

    });
        
    $router->get('/chart', function () {
        $today = date('Y-m-d');
        $startOfWeek = date('Y-m-d', strtotime('monday this week'));
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
        $thisWeekIncome = DB::fetchAll("SELECT DATE(date_created) as date, SUM(amount) as total FROM incomes WHERE status = 'approved' AND DATE(date_created) BETWEEN ? AND ? GROUP BY DATE(date_created)", [$startOfWeek, $today]);
        $thisWeekExpense = DB::fetchAll("SELECT DATE(date_created) as date, SUM(amount) as total FROM expenses WHERE status = 'approved' AND DATE(date_created) BETWEEN ? AND ? GROUP BY DATE(date_created)", [$startOfWeek, $today]);

        // This month vs last month
        $thisMonthIncome = DB::fetchColumn("SELECT SUM(amount) FROM incomes WHERE status = 'approved' AND DATE(date_created) BETWEEN ? AND ?", [$startOfMonth, $today]);
        $lastMonthIncome = DB::fetchColumn("SELECT SUM(amount) FROM incomes WHERE status = 'approved' AND DATE(date_created) BETWEEN ? AND ?", [$startOfLastMonth, $endOfLastMonth]);
        $thisMonthExpense = DB::fetchColumn("SELECT SUM(amount) FROM expenses WHERE status = 'approved' AND DATE(date_created) BETWEEN ? AND ?", [$startOfMonth, $today]);
        $lastMonthExpense = DB::fetchColumn("SELECT SUM(amount) FROM expenses WHERE status = 'approved' AND DATE(date_created) BETWEEN ? AND ?", [$startOfLastMonth, $endOfLastMonth]);

        // Last 6 months
        $lastSixMonthsIncome = DB::fetchAll("SELECT DATE_FORMAT(date_created, '%Y-%m') as month, SUM(amount) as total FROM incomes WHERE status = 'approved' AND DATE(date_created) BETWEEN DATE_SUB(?, INTERVAL 6 MONTH) AND ? GROUP BY DATE_FORMAT(date_created, '%Y-%m')", [$today, $today]);
        $lastSixMonthsExpense = DB::fetchAll("SELECT DATE_FORMAT(date_created, '%Y-%m') as month, SUM(amount) as total FROM expenses WHERE status = 'approved' AND DATE(date_created) BETWEEN DATE_SUB(?, INTERVAL 6 MONTH) AND ? GROUP BY DATE_FORMAT(date_created, '%Y-%m')", [$today, $today]);

        // This year vs last year
        $thisYearIncome = DB::fetchColumn("SELECT SUM(amount) FROM incomes WHERE status = 'approved' AND DATE(date_created) BETWEEN ? AND ?", [$startOfYear, $today]);
        $lastYearIncome = DB::fetchColumn("SELECT SUM(amount) FROM incomes WHERE status = 'approved' AND DATE(date_created) BETWEEN ? AND ?", [$startOfLastYear, $endOfLastYear]);
        $thisYearExpense = DB::fetchColumn("SELECT SUM(amount) FROM expenses WHERE status = 'approved' AND DATE(date_created) BETWEEN ? AND ?", [$startOfYear, $today]);
        $lastYearExpense = DB::fetchColumn("SELECT SUM(amount) FROM expenses WHERE status = 'approved' AND DATE(date_created) BETWEEN ? AND ?", [$startOfLastYear, $endOfLastYear]);

        // Money distribution
        $moneyDistribution = DB::fetchAll("SELECT bank, SUM(amount) as total FROM incomes WHERE status = 'approved' GROUP BY bank");

        // Spending by category (this month)
        $spendingByCategory = DB::fetchAll("SELECT category, SUM(amount) as total FROM expenses WHERE status = 'approved' AND DATE(date_created) BETWEEN ? AND ? GROUP BY category", [$startOfMonth, $today]);

        // Income by category (this month)
        $incomeByCategory = DB::fetchAll("SELECT category, SUM(amount) as total FROM incomes WHERE status = 'approved' AND DATE(date_created) BETWEEN ? AND ? GROUP BY category", [$startOfMonth, $today]);

        header('Content-Type: application/json');
        echo json_encode([
            'today' => [
                'income' => $todayIncome,
                'expense' => $todayExpense
            ],
            'thisWeek' => [
                'labels' => array_column($thisWeekIncome, 'date'),
                'income' => array_column($thisWeekIncome, 'total'),
                'expense' => array_column($thisWeekExpense, 'total')
            ],
            'thisMonth' => [
                'income' => $thisMonthIncome,
                'expense' => $thisMonthExpense
            ],
            'lastMonth' => [
                'income' => $lastMonthIncome,
                'expense' => $lastMonthExpense
            ],
            'lastSixMonths' => [
                'labels' => array_column($lastSixMonthsIncome, 'month'),
                'income' => array_column($lastSixMonthsIncome, 'total'),
                'expense' => array_column($lastSixMonthsExpense, 'total')
            ],
            'thisYear' => [
                'income' => $thisYearIncome,
                'expense' => $thisYearExpense
            ],
            'previousYear' => [
                'income' => $lastYearIncome,
                'expense' => $lastYearExpense
            ],
            'moneyDistribution' => [
                'labels' => array_column($moneyDistribution, 'bank'),
                'amounts' => array_column($moneyDistribution, 'total')
            ],
            'spendingByCategory' => [
                'labels' => array_column($spendingByCategory, 'category'),
                'amounts' => array_column($spendingByCategory, 'total')
            ],
            'incomeByCategory' => [
                'labels' => array_column($incomeByCategory, 'category'),
                'amounts' => array_column($incomeByCategory, 'total')
            ]
        ]);
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