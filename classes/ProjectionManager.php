<?php

class ProjectionManager {
    // ===== UTILITY METHODS =====
    
    // Common method to build WHERE clause for filtering
    private static function buildFilterWhereClause($filters = [], $table = null) {
        $where = [];
        $params = [];
        
        // Add user permission check
        if (!Users::can('APPROVE')) {
            $where[] = "entry_by = ?";
            $params[] = $_SESSION['user_id'];
        }
        
        if (!empty($filters['start_date'])) {
            $where[] = "date_realized >= ?";
            $params[] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $where[] = "date_realized <= ?";
            $params[] = $filters['end_date'];
        }
        
        if (!empty($filters['category'])) {
            $where[] = "category = ?";
            $params[] = $filters['category'];
        }
        
        return [
            'where' => !empty($where) ? ' WHERE ' . implode(' AND ', $where) : '',
            'params' => $params
        ];
    }
    
    private static function buildSearchClause($search, &$params, $table = 'income') {
        if (!empty($search)) {
            if ($table === 'income') {
                $params[] = "%$search%";
                $params[] = "%$search%";
                return " AND (income_from LIKE ? OR notes LIKE ?)";
            } else {
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
                return " AND (expense_by LIKE ? OR purpose LIKE ? OR notes LIKE ?)";
            }
        }
        return '';
    }

    private static function buildPaginationClause($page, $limit) {
        $offset = ($page - 1) * $limit;
        return " LIMIT $limit OFFSET $offset";
    }
    
    // ===== PROJECTION METHODS =====
    
    // Get projections with filtering and pagination
    public static function getProjections($type = 'all', $filters = [], $page = 1, $limit = 10, $search = '') {
        if ($type === 'income') {
            return self::getIncomeProjections($filters, $page, $limit, $search);
        } elseif ($type === 'expense') {
            return self::getExpenseProjections($filters, $page, $limit, $search);
        } else {
            // Get both income and expense projections and merge them
            $incomes = self::getIncomeProjections($filters, $page, $limit, $search);
            $expenses = self::getExpenseProjections($filters, $page, $limit, $search);
            
            // Add a type field to distinguish between income and expense
            foreach ($incomes as &$income) {
                $income['type'] = 'income';
                $income['source'] = $income['income_from'];
            }
            
            foreach ($expenses as &$expense) {
                $expense['type'] = 'expense';
                $expense['source'] = $expense['expense_by'];
            }
            
            // Merge and sort by date_realized
            $combined = array_merge($incomes, $expenses);
            usort($combined, function($a, $b) {
                return strtotime($b['date_realized']) - strtotime($a['date_realized']);
            });
            
            return $combined;
        }
    }
    
    // Get income projections
    private static function getIncomeProjections($filters = [], $page = 1, $limit = 10, $search = '') {
        $whereData = self::buildFilterWhereClause($filters, 'income_projections');
        $searchClause = self::buildSearchClause($search, $whereData['params'], 'income');
        $paginationClause = self::buildPaginationClause($page, $limit);

        $sql = "SELECT ip.*, u.username as entry_by_name
                FROM income_projections ip
                LEFT JOIN users u ON ip.entry_by = u.user_id" . 
                $whereData['where'] . $searchClause . 
                " ORDER BY date_realized DESC" . $paginationClause;

        return DB::fetchAll($sql, $whereData['params']);
    }
    
    // Get expense projections
    private static function getExpenseProjections($filters = [], $page = 1, $limit = 10, $search = '') {
        $whereData = self::buildFilterWhereClause($filters, 'expense_projections');
        $searchClause = self::buildSearchClause($search, $whereData['params'], 'expense');
        $paginationClause = self::buildPaginationClause($page, $limit);

        $sql = "SELECT ep.*, u.username as entry_by_name
                FROM expense_projections ep
                LEFT JOIN users u ON ep.entry_by = u.user_id" . 
                $whereData['where'] . $searchClause . 
                " ORDER BY date_realized DESC" . $paginationClause;

        return DB::fetchAll($sql, $whereData['params']);
    }
    
    // Get total count of projections for pagination
    public static function getTotalProjections($type = 'all', $filters = [], $search = '') {
        if ($type === 'income') {
            return self::getTotalIncomeProjections($filters, $search);
        } elseif ($type === 'expense') {
            return self::getTotalExpenseProjections($filters, $search);
        } else {
            return self::getTotalIncomeProjections($filters, $search) + self::getTotalExpenseProjections($filters, $search);
        }
    }
    
    // Get total count of income projections
    private static function getTotalIncomeProjections($filters = [], $search = '') {
        $whereData = self::buildFilterWhereClause($filters, 'income_projections');
        $searchClause = self::buildSearchClause($search, $whereData['params'], 'income');

        $sql = "SELECT COUNT(*) as count FROM income_projections" . 
                $whereData['where'] . $searchClause;
                
        $result = DB::fetchOne($sql, $whereData['params']);
        return $result['count'] ?? 0;
    }
    
    // Get total count of expense projections
    private static function getTotalExpenseProjections($filters = [], $search = '') {
        $whereData = self::buildFilterWhereClause($filters, 'expense_projections');
        $searchClause = self::buildSearchClause($search, $whereData['params'], 'expense');

        $sql = "SELECT COUNT(*) as count FROM expense_projections" . 
                $whereData['where'] . $searchClause;
                
        $result = DB::fetchOne($sql, $whereData['params']);
        return $result['count'] ?? 0;
    }
    
    // Add new income projection
    public static function addIncomeProjection($data) {
        $sql = "INSERT INTO income_projections (
                entry_by, 
                income_from,
                category,
                amount,
                notes,
                date_realized
            ) VALUES (?, ?, ?, ?, ?, ?)";
        
        return DB::query($sql, [
            $data['entry_by'],
            $data['income_from'],
            $data['category'],
            $data['amount'],
            $data['notes'],
            $data['date_realized']
        ]);
    }
    
    // Add new expense projection
    public static function addExpenseProjection($data) {
        $sql = "INSERT INTO expense_projections (
                entry_by, 
                expense_by,
                category,
                purpose,
                amount,
                notes,
                date_realized
            ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        return DB::query($sql, [
            $data['entry_by'],
            $data['expense_by'],
            $data['category'],
            $data['purpose'],
            $data['amount'],
            $data['notes'],
            $data['date_realized']
        ]);
    }
    
    // Update income projection
    public static function updateIncomeProjection($id, $data) {
        $sql = "UPDATE income_projections SET 
                income_from = ?,
                category = ?,
                amount = ?,
                notes = ?,
                date_realized = ?,
                date_modified = CURRENT_TIMESTAMP
                WHERE id = ?";
        
        return DB::query($sql, [
            $data['income_from'],
            $data['category'],
            $data['amount'],
            $data['notes'],
            $data['date_realized'],
            $id
        ]);
    }
    
    // Update expense projection
    public static function updateExpenseProjection($id, $data) {
        $sql = "UPDATE expense_projections SET 
                expense_by = ?,
                category = ?,
                purpose = ?,
                amount = ?,
                notes = ?,
                date_realized = ?,
                date_modified = CURRENT_TIMESTAMP
                WHERE id = ?";
        
        return DB::query($sql, [
            $data['expense_by'],
            $data['category'],
            $data['purpose'],
            $data['amount'],
            $data['notes'],
            $data['date_realized'],
            $id
        ]);
    }
    
    // Delete income projection
    public static function deleteIncomeProjection($id) {
        $sql = "DELETE FROM income_projections WHERE id = ?";
        return DB::query($sql, [$id]);
    }
    
    // Delete expense projection
    public static function deleteExpenseProjection($id) {
        $sql = "DELETE FROM expense_projections WHERE id = ?";
        return DB::query($sql, [$id]);
    }
    
    // Get income projection categories
    public static function getIncomeProjectionCategories() {
        $sql = "SELECT DISTINCT category FROM income_projections";
        $categories = DB::fetchAll($sql);
        
        // Get categories from regular incomes as well for suggestions
        $incomeSql = "SELECT DISTINCT category FROM incomes WHERE status != 'deleted'";
        $incomeCategories = DB::fetchAll($incomeSql);
        
        // Merge categories and remove duplicates
        $allCategories = array_column(array_merge($categories, $incomeCategories), 'category');
        return array_unique(array_filter($allCategories));
    }
    
    // Get expense projection categories
    public static function getExpenseProjectionCategories() {
        $sql = "SELECT DISTINCT category FROM expense_projections";
        $categories = DB::fetchAll($sql);
        
        // Get categories from regular expenses as well for suggestions
        $expenseSql = "SELECT DISTINCT category FROM expenses WHERE status != 'deleted'";
        $expenseCategories = DB::fetchAll($expenseSql);
        
        // Merge categories and remove duplicates
        $allCategories = array_column(array_merge($categories, $expenseCategories), 'category');
        return array_unique(array_filter($allCategories));
    }
    
    // Get projection summary
    public static function getProjectionSummary($startDate = null, $endDate = null) {
        if (!$startDate) $startDate = date('Y-m-d', strtotime('first day of this month'));
        if (!$endDate) $endDate = date('Y-m-d', strtotime('last day of this month'));
        
        // Get income projections summary
        $incomeSql = "SELECT 
            SUM(amount) as total,
            category,
            COUNT(*) as count
            FROM income_projections 
            WHERE DATE(date_realized) BETWEEN ? AND ?
            GROUP BY category";
        
        $incomes = DB::fetchAll($incomeSql, [$startDate, $endDate]);
        
        // Get expense projections summary
        $expenseSql = "SELECT 
            SUM(amount) as total,
            category,
            COUNT(*) as count
            FROM expense_projections 
            WHERE DATE(date_realized) BETWEEN ? AND ?
            GROUP BY category";
        
        $expenses = DB::fetchAll($expenseSql, [$startDate, $endDate]);
        
        // Calculate totals
        $totalIncome = array_sum(array_column($incomes, 'total'));
        $totalExpenses = array_sum(array_column($expenses, 'total'));
        
        return [
            'period' => ['start' => $startDate, 'end' => $endDate],
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'net_amount' => $totalIncome - $totalExpenses,
            'income_by_category' => $incomes,
            'expenses_by_category' => $expenses
        ];
    }


    public static function getProjectedIncomeTotal($startDate, $endDate) {
        $sql = "SELECT SUM(amount) as total FROM income_projections WHERE DATE(date_realized) BETWEEN ? AND ?";
        $result = DB::fetchOne($sql, [$startDate, $endDate]);
        return $result['total'] ?? 0;
    }

    // Get projected expense total for a given date range
    public static function getProjectedExpenseTotal($startDate, $endDate) {
        $sql = "SELECT SUM(amount) as total FROM expense_projections WHERE DATE(date_realized) BETWEEN ? AND ?";
        $result = DB::fetchOne($sql, [$startDate, $endDate]);
        return $result['total'] ?? 0;
    }
}