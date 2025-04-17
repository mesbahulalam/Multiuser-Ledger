<?php

class FinanceManager {
    // ===== UTILITY METHODS =====
    
    // Common method to build WHERE clause for filtering
    private static function buildFilterWhereClause($filters = []) {
        $where = [];
        $params = [];
        
        // Add user permission check
        if (!Users::can('APPROVE')) {
            $where[] = "entry_by = ?";
            $params[] = $_SESSION['user_id'];
        }

        // Handle status filtering
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'deleted' && Users::can('DELETE')) {
                $where[] = "status = 'deleted'";
            } else {
                $where[] = "status = ?";
                $params[] = $filters['status'];
            }
        } else {
            // If no status filter, exclude deleted items by default
            $where[] = "status != 'deleted'";
        }
        
        if (!empty($filters['start_date'])) {
            $where[] = "date_created >= ?";
            $params[] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $where[] = "date_created <= ?";
            $params[] = $filters['end_date'];
        }
        
        if (!empty($filters['category'])) {
            $where[] = "category = ?";
            $params[] = $filters['category'];
        }
        
        return [
            'where' => ' WHERE ' . implode(' AND ', $where),
            'params' => $params
        ];
    }
    
    private static function buildSearchClause($search, &$params) {
        if (!empty($search)) {
            $params[] = "%$search%";
            $params[] = "%$search%";
            return " AND (income_from LIKE ? OR notes LIKE ?)";
        }
        return '';
    }

    private static function buildPaginationClause($page, $limit) {
        $offset = ($page - 1) * $limit;
        return " LIMIT $limit OFFSET $offset";
    }
    
    // Get column distinct values
    public static function getDistinctColumn($table, $column) {
        $sql = "SELECT DISTINCT $column FROM $table";
        $result = DB::fetchAll($sql);
        return array_column($result, $column);
    }
    
    // Add method to get available statuses
    public static function getAvailableStatuses($includeDeleted = false) {
        $statuses = [
            ['id' => 'pending', 'text' => 'Pending'],
            ['id' => 'approved', 'text' => 'Approved'],
            ['id' => 'denied', 'text' => 'Denied']
        ];
        
        if ($includeDeleted) {
            $statuses[] = ['id' => 'deleted', 'text' => 'Deleted'];
        }
        
        return $statuses;
    }
    
    // ===== INCOME METHODS =====
    
    // Get incomes with filtering and pagination
    public static function getIncomes($filters = [], $page = 1, $limit = 10, $search = '') {
        $whereData = self::buildFilterWhereClause($filters);
        $searchClause = self::buildSearchClause($search, $whereData['params']);
        $paginationClause = self::buildPaginationClause($page, $limit);

        $sql = "SELECT i.*, 
                u1.username as entry_by_name,
                u2.username as approved_by_name 
                FROM incomes i 
                LEFT JOIN users u1 ON i.entry_by = u1.user_id
                LEFT JOIN users u2 ON i.approved_by = u2.user_id" . 
                $whereData['where'] . $searchClause . 
                " ORDER BY id " . DEFAULT_DATA_ORDER . $paginationClause;

        return DB::fetchAll($sql, $whereData['params']);
    }
    
    // Get income record by ID
    public static function REDACTED_getIncomeById($id) {
        return DB::fetchOne("SELECT * FROM incomes WHERE id = ?", [$id]);
    }
    
    // Updated addIncome method to match schema column order
    public static function addIncome($data) {
        $sql = "INSERT INTO incomes (
                entry_by, 
                approved_by,
                income_from, 
                category, 
                amount, 
                method, 
                bank, 
                account_number, 
                transaction_number, 
                notes,
                attachment_id,
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        return DB::query($sql, [
            $data['entry_by'],
            Users::can('APPROVE') ? $_SESSION['user_id'] : null, // approved_by defaults to null
            $data['income_from'],
            $data['category'],
            $data['amount'],
            $data['method'],
            $data['bank'],
            $data['account_number'] ?? null,
            $data['transaction_number'] ?? null,
            $data['notes'],
            $data['attachment_id'] ?? null,
            $data['status']
        ]);
    }
    
    // Updated updateIncome method to match schema column order
    public static function updateIncome($id, $data) {
        $sql = "UPDATE incomes SET 
                income_from = ?,
                category = ?,
                amount = ?,
                method = ?,
                bank = ?,
                account_number = ?,
                transaction_number = ?,
                notes = ?,
                attachment_id = ?,
                status = ?
                WHERE id = ?";
        
        return DB::query($sql, [
            $data['income_from'],
            $data['category'],
            $data['amount'],
            $data['method'],
            $data['bank'],
            $data['account_number'] ?? null,
            $data['transaction_number'] ?? null,
            $data['notes'],
            $data['attachment_id'] ?? null,
            $data['status'],
            $id
        ]);
    }
    
    // Add approve income method
    public static function approveIncome($id) {
        $sql = "UPDATE incomes SET 
                status = 'approved',
                approved_by = ?,
                date_approved = CURRENT_TIMESTAMP
                WHERE id = ?";
        
        return DB::query($sql, [$_SESSION['user_id'], $id]);
    }
    
    // Update delete methods to use soft delete
    public static function deleteIncome($id) {
        $sql = "UPDATE incomes SET status = 'deleted' WHERE id = ?";
        return DB::query($sql, [$id]);
    }
    
    // Get total counts for pagination
    public static function getTotalIncomes($filters = [], $search = '') {
        $whereData = self::buildFilterWhereClause($filters);
        $searchClause = self::buildSearchClause($search, $whereData['params']);

        $result = DB::fetchOne("SELECT COUNT(*) as count FROM incomes" . 
                               $whereData['where'] . $searchClause, 
                               $whereData['params']);
        return $result['count'] ?? 0;
    }
    
    // Get next income ID
    public static function getNextIncomeId() {
        $sql = "SELECT MAX(id) as max_id FROM incomes";
        $result = DB::fetchOne($sql);
        return ($result['max_id'] ?? 0) + 1;
    }
    
    // Update category retrieval to exclude deleted records
    public static function getIncomeCategories() {
        $sql = "SELECT DISTINCT category FROM incomes WHERE status != 'deleted'";
        $categories = DB::fetchAll($sql);
        return array_column($categories, 'category');
    }
    
    // ===== EXPENSE METHODS =====
    
    // Get expenses with filtering and pagination
    public static function getExpenses($filters = [], $page = 1, $limit = 10, $search = '') {
        $whereData = self::buildFilterWhereClause($filters);
        $searchClause = self::buildSearchClause($search, $whereData['params']);
        $paginationClause = self::buildPaginationClause($page, $limit);

        $sql = "SELECT e.*, 
            u1.username as entry_by_name,
            u2.username as approved_by_name,
            CASE 
                WHEN e.expense_by REGEXP '^[0-9]+$' THEN u3.username
                ELSE e.expense_by 
            END as expense_by
            FROM expenses e 
            LEFT JOIN users u1 ON e.entry_by = u1.user_id
            LEFT JOIN users u2 ON e.approved_by = u2.user_id
            LEFT JOIN users u3 ON e.expense_by = u3.user_id" . 
            $whereData['where'] . $searchClause . 
            " ORDER BY id " . DEFAULT_DATA_ORDER . $paginationClause;

        return DB::fetchAll($sql, $whereData['params']);
    }
    
    // Get expense record by ID
    public static function REDACTED_getExpenseById($id) {
        return DB::fetchOne("SELECT * FROM expenses WHERE id = ?", [$id]);
    }
    
    // Updated addExpense method to match schema column order
    public static function addExpense($data) {
        $sql = "INSERT INTO expenses (
                entry_by, 
                approved_by,
                expense_by, 
                category, 
                purpose, 
                amount, 
                method, 
                bank, 
                account_number, 
                transaction_number, 
                notes,
                attachment_id,
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        return DB::query($sql, [
            $data['entry_by'],
            Users::can('APPROVE') ? $_SESSION['user_id'] : null, // approved_by defaults to null
            $data['expense_by'],
            $data['category'],
            $data['purpose'],
            $data['amount'],
            $data['method'],
            $data['bank'],
            $data['account_number'] ?? null,
            $data['transaction_number'] ?? null,
            $data['notes'],
            $data['attachment_id'] ?? null,
            $data['status']
        ]);
    }
    
    // Updated updateExpense method to match schema column order
    public static function updateExpense($id, $data) {
        $sql = "UPDATE expenses SET 
                expense_by = ?,
                category = ?,
                purpose = ?,
                amount = ?,
                method = ?,
                bank = ?,
                account_number = ?,
                transaction_number = ?,
                notes = ?,
                attachment_id = ?,
                status = ?
                WHERE id = ?";
        
        return DB::query($sql, [
            $data['expense_by'],
            $data['category'],
            $data['purpose'],
            $data['amount'],
            $data['method'],
            $data['bank'],
            $data['account_number'] ?? null,
            $data['transaction_number'] ?? null,
            $data['notes'],
            $data['attachment_id'] ?? null,
            $data['status'],
            $id
        ]);
    }
    
    // Approve expense method
    public static function approveExpense($id) {
        $sql = "UPDATE expenses SET 
                status = 'approved'
                WHERE id = ?";
        
        return DB::query($sql, [$id]);
    }
    
    public static function deleteExpense($id) {
        $sql = "UPDATE expenses SET status = 'deleted' WHERE id = ?";
        return DB::query($sql, [$id]);
    }
    
    // Get total counts for pagination
    public static function getTotalExpenses($filters = [], $search = '') {
        $whereData = self::buildFilterWhereClause($filters);
        $searchClause = self::buildSearchClause($search, $whereData['params']);

        $result = DB::fetchOne("SELECT COUNT(*) as count FROM expenses" . 
                               $whereData['where'] . $searchClause, 
                               $whereData['params']);
        return $result['count'] ?? 0;
    }
    
    // Get next expense ID
    public static function getNextExpenseId() {
        $sql = "SELECT MAX(id) as max_id FROM expenses";
        $result = DB::fetchOne($sql);
        return ($result['max_id'] ?? 0) + 1;
    }
    
    // Update category retrieval to exclude deleted records
    public static function getExpenseCategories() {
        $sql = "SELECT DISTINCT category FROM expenses WHERE status != 'deleted'";
        $categories = DB::fetchAll($sql);
        return array_column($categories, 'category');
    }
    
    // ===== FINANCIAL SUMMARY METHODS =====
    
    // Update balance calculation to exclude deleted records
    public static function getBalance() {
        $sql = "SELECT 
            SUM(amount) as total_income
            FROM incomes
            WHERE status = 'approved'";
        $totalIncome = DB::fetchOne($sql);

        $sql = "SELECT 
            SUM(amount) as total_expense
            FROM expenses
            WHERE status = 'approved'";
        $totalExpense = DB::fetchOne($sql);

        return ($totalIncome['total_income'] ?? 0) - ($totalExpense['total_expense'] ?? 0);
    }
    
    // Update month summary to exclude deleted records
    public static function getMonthSummary($year, $month) {
        $startDate = "$year-$month-01";
        $endDate = date('Y-m-t', strtotime($startDate));
        
        // Get total income
        $incomeSql = "SELECT 
            SUM(amount) as total,
            category,
            COUNT(*) as count
            FROM incomes 
            WHERE status != 'deleted' AND DATE(date_created) BETWEEN ? AND ?
            GROUP BY category";
        
        $incomes = DB::fetchAll($incomeSql, [$startDate, $endDate]);
        
        // Get total expenses
        $expenseSql = "SELECT 
            SUM(amount) as total,
            category,
            COUNT(*) as count
            FROM expenses 
            WHERE status = 'approved' AND DATE(date_created) BETWEEN ? AND ?
            GROUP BY category";
        
        $expenses = DB::fetchAll($expenseSql, [$startDate, $endDate]);
        
        // Calculate totals
        $totalIncome = array_sum(array_column($incomes, 'total'));
        $totalExpenses = array_sum(array_column($expenses, 'total'));
        
        return [
            'period' => ['year' => $year, 'month' => $month],
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'net_amount' => $totalIncome - $totalExpenses,
            'income_by_category' => $incomes,
            'expenses_by_category' => $expenses
        ];
    }
    
    // Update six month summary to exclude deleted records
    public static function getSixMonthSummary() {
        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime('-6 months'));
        
        // Get monthly income totals
        $incomeSql = "SELECT 
            DATE_FORMAT(date_created, '%Y-%m') as month,
            SUM(amount) as total,
            category
            FROM incomes 
            WHERE status != 'deleted' AND DATE(date_created) BETWEEN ? AND ?
            GROUP BY DATE_FORMAT(date_created, '%Y-%m'), category
            ORDER BY month DESC";
        
        $incomes = DB::fetchAll($incomeSql, [$startDate, $endDate]);
        
        // Get monthly expense totals
        $expenseSql = "SELECT 
            DATE_FORMAT(date_created, '%Y-%m') as month,
            SUM(amount) as total,
            category
            FROM expenses 
            WHERE status = 'approved' AND DATE(date_created) BETWEEN ? AND ?
            GROUP BY DATE_FORMAT(date_created, '%Y-%m'), category
            ORDER BY month DESC";
        
        $expenses = DB::fetchAll($expenseSql, [$startDate, $endDate]);
        
        // Organize data by month
        $summary = [];
        foreach (range(0, 5) as $i) {
            $monthDate = date('Y-m', strtotime("-$i months"));
            $monthIncomes = array_filter($incomes, fn($inc) => $inc['month'] === $monthDate);
            $monthExpenses = array_filter($expenses, fn($exp) => $exp['month'] === $monthDate);
            
            $totalIncome = array_sum(array_column($monthIncomes, 'total'));
            $totalExpenses = array_sum(array_column($monthExpenses, 'total'));
            
            $summary[] = [
                'month' => $monthDate,
                'total_income' => $totalIncome,
                'total_expenses' => $totalExpenses,
                'net_amount' => $totalIncome - $totalExpenses,
                'income_by_category' => array_values($monthIncomes),
                'expenses_by_category' => array_values($monthExpenses)
            ];
        }
        
        return $summary;
    }
}
