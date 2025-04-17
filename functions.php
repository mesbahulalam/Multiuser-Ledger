<?php
function resolve($var, $default = null) {
    return $var ?? $default;
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}
// Helper function to generate month options (add this at the top after <?php)
function getMonthOptions() {
    $options = [];
    $currentMonth = new DateTime();
    
    // Past 6 months
    for ($i = 6; $i > 0; $i--) {
        $date = clone $currentMonth;
        $date->modify("-$i month");
        $options[] = [
            'value' => $date->format('Y-m'),
            'display' => $date->format('F Y'), // Full month name and year
            'label' => $date->format('F Y'),
            'timestamp' => $date->format('Y-m-d H:i:s')
        ];
    }
    
    // Current month
    $options[] = [
        'value' => $currentMonth->format('Y-m'),
        'display' => $currentMonth->format('F Y'),
        'label' => $currentMonth->format('F Y'),
        'timestamp' => $currentMonth->format('Y-m-d H:i:s'),
        'selected' => true
    ];
    
    // Next 6 months
    for ($i = 1; $i <= 6; $i++) {
        $date = clone $currentMonth;
        $date->modify("+$i month");
        $options[] = [
            'value' => $date->format('Y-m'),
            'display' => $date->format('F Y'),
            'label' => $date->format('F Y'),
            'timestamp' => $date->format('Y-m-d H:i:s')
        ];
    }
    
    return $options;
}

// Helper functions for filtered data API

/**
 * Get monthly comparison data for charts
 * 
 * @param array $filters Filter parameters
 * @param bool $includeProjections Whether to include projection data
 * @return array Formatted data for charts
 */
function getMonthlyComparisonData($filters, $includeProjections = true) {
    // Generate monthly labels between start and end date
    $startDate = new DateTime($filters['start_date']);
    $endDate = new DateTime($filters['end_date']);
    $labels = [];
    $income = [];
    $expense = [];
    $projectedIncome = [];
    $projectedExpense = [];
    $net = [];
    $projectedNet = [];
    
    // Clone startDate to avoid modifying the original
    $currentDate = clone $startDate;
    
    // Generate months between start and end date
    while ($currentDate <= $endDate) {
        $monthStart = $currentDate->format('Y-m-01');
        $monthEnd = $currentDate->format('Y-m-t');
        $monthLabel = $currentDate->format('M Y');
        
        // Get actual income for this month
        $monthlyFilters = array_merge($filters, [
            'start_date' => $monthStart,
            'end_date' => $monthEnd
        ]);
        
        $monthlyIncome = DB::fetchOne("SELECT SUM(amount) as total FROM incomes 
                                       WHERE status = ? AND DATE(date_created) BETWEEN ? AND ?
                                       " . (!empty($filters['category']) ? " AND category = ?" : ""),
                                      array_filter([
                                          $filters['status'],
                                          $monthStart,
                                          $monthEnd,
                                          !empty($filters['category']) ? $filters['category'] : null
                                      ]));
        
        // Get actual expense for this month
        $monthlyExpense = DB::fetchOne("SELECT SUM(amount) as total FROM expenses 
                                        WHERE status = ? AND DATE(date_created) BETWEEN ? AND ?
                                        " . (!empty($filters['category']) ? " AND category = ?" : ""),
                                       array_filter([
                                           $filters['status'],
                                           $monthStart,
                                           $monthEnd,
                                           !empty($filters['category']) ? $filters['category'] : null
                                       ]));
        
        $monthlyIncomeAmount = $monthlyIncome['total'] ?? 0;
        $monthlyExpenseAmount = $monthlyExpense['total'] ?? 0;
        $monthlyNetAmount = $monthlyIncomeAmount - $monthlyExpenseAmount;
        
        // Get projected data if requested
        if ($includeProjections) {
            $monthlyProjectedIncome = ProjectionManager::getProjectedIncomeTotal($monthStart, $monthEnd);
            $monthlyProjectedExpense = ProjectionManager::getProjectedExpenseTotal($monthStart, $monthEnd);
            $monthlyProjectedNet = $monthlyProjectedIncome - $monthlyProjectedExpense;
            
            $projectedIncome[] = $monthlyProjectedIncome;
            $projectedExpense[] = $monthlyProjectedExpense;
            $projectedNet[] = $monthlyProjectedNet;
        }
        
        // Add data to arrays
        $labels[] = $monthLabel;
        $income[] = $monthlyIncomeAmount;
        $expense[] = $monthlyExpenseAmount;
        $net[] = $monthlyNetAmount;
        
        // Move to next month
        $currentDate->modify('+1 month');
    }
    
    // Calculate category distributions
    $categoryFilters = array_merge($filters, []);
    $incomeCategoryData = getCategoryDistributionData('income', $categoryFilters);
    $expenseCategoryData = getCategoryDistributionData('expense', $categoryFilters);
    
    // Bank distribution
    $bankDistributionData = getBankDistributionData($filters);
    
    // Payment methods distribution
    $paymentMethodsData = getPaymentMethodDistributionData($filters);
    
    // Calculate overall totals
    $totalIncome = array_sum($income);
    $totalExpense = array_sum($expense);
    $totalNet = $totalIncome - $totalExpense;
    
    $totalProjectedIncome = array_sum($projectedIncome);
    $totalProjectedExpense = array_sum($projectedExpense);
    $totalProjectedNet = $totalProjectedIncome - $totalProjectedExpense;
    
    return [
        'labels' => $labels,
        'income' => $income,
        'expense' => $expense,
        'net' => $net,
        'projectedIncome' => $projectedIncome,
        'projectedExpense' => $projectedExpense,
        'projectedNet' => $projectedNet,
        'totalIncome' => $totalIncome,
        'totalExpense' => $totalExpense,
        'totalNet' => $totalNet,
        'totalProjectedIncome' => $totalProjectedIncome,
        'totalProjectedExpense' => $totalProjectedExpense,
        'totalProjectedNet' => $totalProjectedNet,
        'incomeByCategory' => $incomeCategoryData,
        'expenseByCategory' => $expenseCategoryData,
        'bankDistribution' => $bankDistributionData,
        'paymentMethods' => $paymentMethodsData
    ];
}

/**
 * Get quarterly comparison data for charts
 * 
 * @param array $filters Filter parameters
 * @param bool $includeProjections Whether to include projection data
 * @return array Formatted data for charts
 */
function getQuarterlyComparisonData($filters, $includeProjections = true) {
    // Get quarterly data between start and end date
    $startDate = new DateTime($filters['start_date']);
    $endDate = new DateTime($filters['end_date']);
    
    $labels = [];
    $income = [];
    $expense = [];
    $projectedIncome = [];
    $projectedExpense = [];
    $net = [];
    $projectedNet = [];
    
    // Calculate the first quarter start
    $year = $startDate->format('Y');
    $quarter = ceil($startDate->format('n') / 3);
    $currentQuarterStart = new DateTime($year.'-'.((($quarter-1) * 3) + 1).'-01');
    
    // Generate quarters between start and end date
    while ($currentQuarterStart <= $endDate) {
        $year = $currentQuarterStart->format('Y');
        $quarter = ceil($currentQuarterStart->format('n') / 3);
        
        $quarterStart = clone $currentQuarterStart;
        $quarterEnd = clone $currentQuarterStart;
        $quarterEnd->modify('+2 month')->modify('last day of this month');
        
        // Skip if quarter ends before the filter start date
        if ($quarterEnd < $startDate) {
            $currentQuarterStart->modify('+3 month');
            continue;
        }
        
        // Adjust to respect filter date range
        $effectiveStart = max($quarterStart, $startDate);
        $effectiveEnd = min($quarterEnd, $endDate);
        
        // Get actual income for this quarter
        $quarterlyIncome = DB::fetchOne("SELECT SUM(amount) as total FROM incomes 
                                        WHERE status = ? AND DATE(date_created) BETWEEN ? AND ?
                                        " . (!empty($filters['category']) ? " AND category = ?" : ""),
                                       array_filter([
                                           $filters['status'],
                                           $effectiveStart->format('Y-m-d'),
                                           $effectiveEnd->format('Y-m-d'),
                                           !empty($filters['category']) ? $filters['category'] : null
                                       ]));
        
        // Get actual expense for this quarter
        $quarterlyExpense = DB::fetchOne("SELECT SUM(amount) as total FROM expenses 
                                         WHERE status = ? AND DATE(date_created) BETWEEN ? AND ?
                                         " . (!empty($filters['category']) ? " AND category = ?" : ""),
                                        array_filter([
                                            $filters['status'],
                                            $effectiveStart->format('Y-m-d'),
                                            $effectiveEnd->format('Y-m-d'),
                                            !empty($filters['category']) ? $filters['category'] : null
                                        ]));
        
        $quarterlyIncomeAmount = $quarterlyIncome['total'] ?? 0;
        $quarterlyExpenseAmount = $quarterlyExpense['total'] ?? 0;
        $quarterlyNetAmount = $quarterlyIncomeAmount - $quarterlyExpenseAmount;
        
        // Get projected data if requested
        if ($includeProjections) {
            $quarterlyProjectedIncome = ProjectionManager::getProjectedIncomeTotal(
                $effectiveStart->format('Y-m-d'),
                $effectiveEnd->format('Y-m-d')
            );
            
            $quarterlyProjectedExpense = ProjectionManager::getProjectedExpenseTotal(
                $effectiveStart->format('Y-m-d'),
                $effectiveEnd->format('Y-m-d')
            );
            
            $quarterlyProjectedNet = $quarterlyProjectedIncome - $quarterlyProjectedExpense;
            
            $projectedIncome[] = $quarterlyProjectedIncome;
            $projectedExpense[] = $quarterlyProjectedExpense;
            $projectedNet[] = $quarterlyProjectedNet;
        }
        
        // Add data to arrays
        $labels[] = "Q$quarter $year";
        $income[] = $quarterlyIncomeAmount;
        $expense[] = $quarterlyExpenseAmount;
        $net[] = $quarterlyNetAmount;
        
        // Move to next quarter
        $currentQuarterStart->modify('+3 month');
    }
    
    // Calculate category distributions
    $categoryFilters = array_merge($filters, []);
    $incomeCategoryData = getCategoryDistributionData('income', $categoryFilters);
    $expenseCategoryData = getCategoryDistributionData('expense', $categoryFilters);
    
    // Bank distribution
    $bankDistributionData = getBankDistributionData($filters);
    
    // Payment methods distribution
    $paymentMethodsData = getPaymentMethodDistributionData($filters);
    
    // Calculate overall totals
    $totalIncome = array_sum($income);
    $totalExpense = array_sum($expense);
    $totalNet = $totalIncome - $totalExpense;
    
    $totalProjectedIncome = array_sum($projectedIncome);
    $totalProjectedExpense = array_sum($projectedExpense);
    $totalProjectedNet = $totalProjectedIncome - $totalProjectedExpense;
    
    return [
        'labels' => $labels,
        'income' => $income,
        'expense' => $expense,
        'net' => $net,
        'projectedIncome' => $projectedIncome,
        'projectedExpense' => $projectedExpense,
        'projectedNet' => $projectedNet,
        'totalIncome' => $totalIncome,
        'totalExpense' => $totalExpense,
        'totalNet' => $totalNet,
        'totalProjectedIncome' => $totalProjectedIncome,
        'totalProjectedExpense' => $totalProjectedExpense,
        'totalProjectedNet' => $totalProjectedNet,
        'incomeByCategory' => $incomeCategoryData,
        'expenseByCategory' => $expenseCategoryData,
        'bankDistribution' => $bankDistributionData,
        'paymentMethods' => $paymentMethodsData
    ];
}

/**
 * Get yearly comparison data for charts
 * 
 * @param array $filters Filter parameters
 * @param bool $includeProjections Whether to include projection data
 * @return array Formatted data for charts
 */
function getYearlyComparisonData($filters, $includeProjections = true) {
    $startDate = new DateTime($filters['start_date']);
    $endDate = new DateTime($filters['end_date']);
    
    $labels = [];
    $income = [];
    $expense = [];
    $projectedIncome = [];
    $projectedExpense = [];
    $net = [];
    $projectedNet = [];
    
    // Start with the first year
    $currentYear = (int)$startDate->format('Y');
    $endYear = (int)$endDate->format('Y');
    
    // Generate data for each year
    for ($year = $currentYear; $year <= $endYear; $year++) {
        $yearStart = new DateTime($year.'-01-01');
        $yearEnd = new DateTime($year.'-12-31');
        
        // Adjust to respect filter date range
        $effectiveStart = max($yearStart, $startDate);
        $effectiveEnd = min($yearEnd, $endDate);
        
        // Get actual income for this year
        $yearlyIncome = DB::fetchOne("SELECT SUM(amount) as total FROM incomes 
                                     WHERE status = ? AND DATE(date_created) BETWEEN ? AND ?
                                     " . (!empty($filters['category']) ? " AND category = ?" : ""),
                                    array_filter([
                                        $filters['status'],
                                        $effectiveStart->format('Y-m-d'),
                                        $effectiveEnd->format('Y-m-d'),
                                        !empty($filters['category']) ? $filters['category'] : null
                                    ]));
        
        // Get actual expense for this year
        $yearlyExpense = DB::fetchOne("SELECT SUM(amount) as total FROM expenses 
                                      WHERE status = ? AND DATE(date_created) BETWEEN ? AND ?
                                      " . (!empty($filters['category']) ? " AND category = ?" : ""),
                                     array_filter([
                                         $filters['status'],
                                         $effectiveStart->format('Y-m-d'),
                                         $effectiveEnd->format('Y-m-d'),
                                         !empty($filters['category']) ? $filters['category'] : null
                                     ]));
        
        $yearlyIncomeAmount = $yearlyIncome['total'] ?? 0;
        $yearlyExpenseAmount = $yearlyExpense['total'] ?? 0;
        $yearlyNetAmount = $yearlyIncomeAmount - $yearlyExpenseAmount;
        
        // Get projected data if requested
        if ($includeProjections) {
            $yearlyProjectedIncome = ProjectionManager::getProjectedIncomeTotal(
                $effectiveStart->format('Y-m-d'),
                $effectiveEnd->format('Y-m-d')
            );
            
            $yearlyProjectedExpense = ProjectionManager::getProjectedExpenseTotal(
                $effectiveStart->format('Y-m-d'),
                $effectiveEnd->format('Y-m-d')
            );
            
            $yearlyProjectedNet = $yearlyProjectedIncome - $yearlyProjectedExpense;
            
            $projectedIncome[] = $yearlyProjectedIncome;
            $projectedExpense[] = $yearlyProjectedExpense;
            $projectedNet[] = $yearlyProjectedNet;
        }
        
        // Add data to arrays
        $labels[] = $year;
        $income[] = $yearlyIncomeAmount;
        $expense[] = $yearlyExpenseAmount;
        $net[] = $yearlyNetAmount;
    }
    
    // Calculate category distributions
    $categoryFilters = array_merge($filters, []);
    $incomeCategoryData = getCategoryDistributionData('income', $categoryFilters);
    $expenseCategoryData = getCategoryDistributionData('expense', $categoryFilters);
    
    // Bank distribution
    $bankDistributionData = getBankDistributionData($filters);
    
    // Payment methods distribution
    $paymentMethodsData = getPaymentMethodDistributionData($filters);
    
    // Calculate overall totals
    $totalIncome = array_sum($income);
    $totalExpense = array_sum($expense);
    $totalNet = $totalIncome - $totalExpense;
    
    $totalProjectedIncome = array_sum($projectedIncome);
    $totalProjectedExpense = array_sum($projectedExpense);
    $totalProjectedNet = $totalProjectedIncome - $totalProjectedExpense;
    
    return [
        'labels' => $labels,
        'income' => $income,
        'expense' => $expense,
        'net' => $net,
        'projectedIncome' => $projectedIncome,
        'projectedExpense' => $projectedExpense,
        'projectedNet' => $projectedNet,
        'totalIncome' => $totalIncome,
        'totalExpense' => $totalExpense,
        'totalNet' => $totalNet,
        'totalProjectedIncome' => $totalProjectedIncome,
        'totalProjectedExpense' => $totalProjectedExpense,
        'totalProjectedNet' => $totalProjectedNet,
        'incomeByCategory' => $incomeCategoryData,
        'expenseByCategory' => $expenseCategoryData,
        'bankDistribution' => $bankDistributionData,
        'paymentMethods' => $paymentMethodsData
    ];
}

/**
 * Get category comparison data for charts
 * 
 * @param array $filters Filter parameters
 * @param bool $includeProjections Whether to include projection data
 * @return array Formatted data for charts
 */
function getCategoryComparisonData($filters, $includeProjections = true) {
    // Get categories first to use as labels
    $incomeCategories = FinanceManager::getIncomeCategories();
    $expenseCategories = FinanceManager::getExpenseCategories();
    
    // Merge unique categories to use as labels
    $allCategories = array_unique(array_merge($incomeCategories, $expenseCategories));
    sort($allCategories);
    
    $labels = $allCategories;
    $income = [];
    $expense = [];
    $net = [];
    $projectedIncome = [];
    $projectedExpense = [];
    $projectedNet = [];
    
    // Calculate income and expense for each category
    foreach ($allCategories as $category) {
        // Create category-specific filter
        $categoryFilter = array_merge($filters, ['category' => $category]);
        
        // Get actual income for this category
        $categoryIncome = DB::fetchOne("SELECT SUM(amount) as total FROM incomes 
                                       WHERE status = ? AND DATE(date_created) BETWEEN ? AND ? AND category = ?",
                                      [
                                          $filters['status'],
                                          $filters['start_date'],
                                          $filters['end_date'],
                                          $category
                                      ]);
        
        // Get actual expense for this category
        $categoryExpense = DB::fetchOne("SELECT SUM(amount) as total FROM expenses 
                                        WHERE status = ? AND DATE(date_created) BETWEEN ? AND ? AND category = ?",
                                       [
                                           $filters['status'],
                                           $filters['start_date'],
                                           $filters['end_date'],
                                           $category
                                       ]);
        
        $categoryIncomeAmount = $categoryIncome['total'] ?? 0;
        $categoryExpenseAmount = $categoryExpense['total'] ?? 0;
        $categoryNetAmount = $categoryIncomeAmount - $categoryExpenseAmount;
        
        // Get projected data if requested
        if ($includeProjections) {
            // Get projected income for this category
            $projectedCategoryIncome = DB::fetchOne("SELECT SUM(amount) as total FROM income_projections 
                                                   WHERE DATE(date_realized) BETWEEN ? AND ? AND category = ?",
                                                  [
                                                      $filters['start_date'],
                                                      $filters['end_date'],
                                                      $category
                                                  ]);
            
            // Get projected expense for this category
            $projectedCategoryExpense = DB::fetchOne("SELECT SUM(amount) as total FROM expense_projections 
                                                    WHERE DATE(date_realized) BETWEEN ? AND ? AND category = ?",
                                                   [
                                                       $filters['start_date'],
                                                       $filters['end_date'],
                                                       $category
                                                   ]);
            
            $projectedCategoryIncomeAmount = $projectedCategoryIncome['total'] ?? 0;
            $projectedCategoryExpenseAmount = $projectedCategoryExpense['total'] ?? 0;
            $projectedCategoryNetAmount = $projectedCategoryIncomeAmount - $projectedCategoryExpenseAmount;
            
            $projectedIncome[] = $projectedCategoryIncomeAmount;
            $projectedExpense[] = $projectedCategoryExpenseAmount;
            $projectedNet[] = $projectedCategoryNetAmount;
        }
        
        // Add data to arrays
        $income[] = $categoryIncomeAmount;
        $expense[] = $categoryExpenseAmount;
        $net[] = $categoryNetAmount;
    }
    
    // Calculate category distributions
    $incomeCategoryData = getCategoryDistributionData('income', $filters);
    $expenseCategoryData = getCategoryDistributionData('expense', $filters);
    
    // Bank distribution
    $bankDistributionData = getBankDistributionData($filters);
    
    // Payment methods distribution
    $paymentMethodsData = getPaymentMethodDistributionData($filters);
    
    // Calculate overall totals
    $totalIncome = array_sum($income);
    $totalExpense = array_sum($expense);
    $totalNet = $totalIncome - $totalExpense;
    
    $totalProjectedIncome = array_sum($projectedIncome);
    $totalProjectedExpense = array_sum($projectedExpense);
    $totalProjectedNet = $totalProjectedIncome - $totalProjectedExpense;
    
    return [
        'labels' => $labels,
        'income' => $income,
        'expense' => $expense,
        'net' => $net,
        'projectedIncome' => $projectedIncome,
        'projectedExpense' => $projectedExpense,
        'projectedNet' => $projectedNet,
        'totalIncome' => $totalIncome,
        'totalExpense' => $totalExpense,
        'totalNet' => $totalNet,
        'totalProjectedIncome' => $totalProjectedIncome,
        'totalProjectedExpense' => $totalProjectedExpense,
        'totalProjectedNet' => $totalProjectedNet,
        'incomeByCategory' => $incomeCategoryData,
        'expenseByCategory' => $expenseCategoryData,
        'bankDistribution' => $bankDistributionData,
        'paymentMethods' => $paymentMethodsData
    ];
}

/**
 * Get bank comparison data for charts
 * 
 * @param array $filters Filter parameters
 * @param bool $includeProjections Whether to include projection data
 * @return array Formatted data for charts
 */
function getBankComparisonData($filters, $includeProjections = true) {
    // Get all banks to use as labels
    $bankQuery = "SELECT DISTINCT bank FROM incomes WHERE bank IS NOT NULL AND bank != ''
                 UNION
                 SELECT DISTINCT bank FROM expenses WHERE bank IS NOT NULL AND bank != ''";
    
    $banksResult = DB::fetchAll($bankQuery);
    $banks = array_column($banksResult, 'bank');
    sort($banks);
    
    $labels = $banks;
    $income = [];
    $expense = [];
    $net = [];
    
    // Calculate income and expense for each bank
    foreach ($banks as $bank) {
        // Get actual income for this bank
        $bankIncome = DB::fetchOne("SELECT SUM(amount) as total FROM incomes 
                                   WHERE status = ? AND DATE(date_created) BETWEEN ? AND ? AND bank = ?",
                                  [
                                      $filters['status'],
                                      $filters['start_date'],
                                      $filters['end_date'],
                                      $bank
                                  ]);
        
        // Get actual expense for this bank
        $bankExpense = DB::fetchOne("SELECT SUM(amount) as total FROM expenses 
                                    WHERE status = ? AND DATE(date_created) BETWEEN ? AND ? AND bank = ?",
                                   [
                                       $filters['status'],
                                       $filters['start_date'],
                                       $filters['end_date'],
                                       $bank
                                   ]);
        
        $bankIncomeAmount = $bankIncome['total'] ?? 0;
        $bankExpenseAmount = $bankExpense['total'] ?? 0;
        $bankNetAmount = $bankIncomeAmount - $bankExpenseAmount;
        
        // Add data to arrays
        $income[] = $bankIncomeAmount;
        $expense[] = $bankExpenseAmount;
        $net[] = $bankNetAmount;
    }
    
    // Calculate category distributions
    $incomeCategoryData = getCategoryDistributionData('income', $filters);
    $expenseCategoryData = getCategoryDistributionData('expense', $filters);
    
    // Bank distribution
    $bankDistributionData = getBankDistributionData($filters);
    
    // Payment methods distribution
    $paymentMethodsData = getPaymentMethodDistributionData($filters);
    
    // Calculate overall totals
    $totalIncome = array_sum($income);
    $totalExpense = array_sum($expense);
    $totalNet = $totalIncome - $totalExpense;
    
    // We don't include projections in bank view since they don't always specify bank
    $totalProjectedIncome = 0;
    $totalProjectedExpense = 0;
    $totalProjectedNet = 0;
    
    if ($includeProjections) {
        $totalProjectedIncome = ProjectionManager::getProjectedIncomeTotal(
            $filters['start_date'],
            $filters['end_date']
        );
        
        $totalProjectedExpense = ProjectionManager::getProjectedExpenseTotal(
            $filters['start_date'],
            $filters['end_date']
        );
        
        $totalProjectedNet = $totalProjectedIncome - $totalProjectedExpense;
    }
    
    return [
        'labels' => $labels,
        'income' => $income,
        'expense' => $expense,
        'net' => $net,
        'projectedIncome' => [],
        'projectedExpense' => [],
        'projectedNet' => [],
        'totalIncome' => $totalIncome,
        'totalExpense' => $totalExpense,
        'totalNet' => $totalNet,
        'totalProjectedIncome' => $totalProjectedIncome,
        'totalProjectedExpense' => $totalProjectedExpense,
        'totalProjectedNet' => $totalProjectedNet,
        'incomeByCategory' => $incomeCategoryData,
        'expenseByCategory' => $expenseCategoryData,
        'bankDistribution' => $bankDistributionData,
        'paymentMethods' => $paymentMethodsData
    ];
}

/**
 * Get category distribution data
 * 
 * @param string $type Either 'income' or 'expense'
 * @param array $filters Filter parameters
 * @return array Category distribution data for charts
 */
function getCategoryDistributionData($type, $filters) {
    $table = $type === 'income' ? 'incomes' : 'expenses';
    $sourceField = $type === 'income' ? 'income_from' : 'expense_by';
    
    // Ensure we have valid status parameter
    $statusCondition = !empty($filters['status']) ? "status = ? AND " : "";
    $categoryCondition = !empty($filters['category']) ? " AND category = ?" : "";
    
    $query = "SELECT category, SUM(amount) as total
              FROM $table
              WHERE 
              ". $statusCondition .
              " DATE(date_created) BETWEEN ? AND ?
              " . $categoryCondition . "
              GROUP BY category
              ORDER BY total DESC";
    
    $params = [];
    if (!empty($filters['status'])) {
        $params[] = $filters['status'];
    }
    $params[] = $filters['start_date'];
    $params[] = $filters['end_date'];
    if (!empty($filters['category'])) {
        $params[] = $filters['category'];
    }
    
    $results = DB::fetchAll($query, $params);

    $labels = [];
    $amounts = [];
    
    if (!empty($results)) {
        foreach ($results as $row) {
            if (!empty($row['category'])) {
                $labels[] = $row['category'];
                $amounts[] = (float) $row['total'];
            } else {
                $labels[] = 'Uncategorized';
                $amounts[] = (float) $row['total'];
            }
        }
    }
    
    return [
        'labels' => $labels,
        'amounts' => $amounts
    ];
}

/**
 * Get bank distribution data
 * 
 * @param array $filters Filter parameters
 * @return array Bank distribution data for pie chart
 */
function getBankDistributionData($filters) {
    // Ensure we have valid status parameter
    $statusCondition = !empty($filters['status']) ? "status = ? AND " : "";
    $categoryCondition = !empty($filters['category']) ? " AND category = ?" : "";
    
    $query = "SELECT COALESCE(bank, 'Unspecified') as bank, SUM(amount) as total
              FROM incomes
              WHERE 
              ". $statusCondition .
              " DATE(date_created) BETWEEN ? AND ? AND bank IS NOT NULL
              " . $categoryCondition . "
              GROUP BY bank
              ORDER BY total DESC";
    
    $params = [];
    if (!empty($filters['status'])) {
        $params[] = $filters['status'];
    }
    $params[] = $filters['start_date'];
    $params[] = $filters['end_date'];
    if (!empty($filters['category'])) {
        $params[] = $filters['category'];
    }
    
    $results = DB::fetchAll($query, $params);
    
    $labels = [];
    $amounts = [];

    if (!empty($results)) {
        foreach ($results as $row) {
            $labels[] = $row['bank'];
            $amounts[] = (float) $row['total'];
        }
    }
    
    return [
        'labels' => $labels,
        'amounts' => $amounts
    ];
}

/**
 * Get payment method distribution data
 * 
 * @param array $filters Filter parameters
 * @return array Payment method distribution data for charts
 */
function getPaymentMethodDistributionData($filters) {
    // Ensure we have valid status parameter
    $statusCondition = !empty($filters['status']) ? "status = ? AND " : "";
    $categoryCondition = !empty($filters['category']) ? " AND category = ?" : "";
    
    // Get payment methods from incomes
    $incomeQuery = "SELECT COALESCE(method, 'Unspecified') as method, SUM(amount) as total
                   FROM incomes
                   WHERE 
                   ". $statusCondition .
                   " DATE(date_created) BETWEEN ? AND ? AND method IS NOT NULL
                   " . $categoryCondition . "
                   GROUP BY method";
    
    $params = [];
    if (!empty($filters['status'])) {
        $params[] = $filters['status'];
    }
    $params[] = $filters['start_date'];
    $params[] = $filters['end_date'];
    if (!empty($filters['category'])) {
        $params[] = $filters['category'];
    }
    
    $incomeResults = DB::fetchAll($incomeQuery, $params);
    
    // Get payment methods from expenses
    $expenseQuery = "SELECT COALESCE(method, 'Unspecified') as method, SUM(amount) as total
                    FROM expenses
                    WHERE 
                    ". $statusCondition .
                    " DATE(date_created) BETWEEN ? AND ? AND method IS NOT NULL
                    " . $categoryCondition . "
                    GROUP BY method";
    
    $expenseResults = DB::fetchAll($expenseQuery, $params);
    
    // Combine and aggregate results
    $methodTotals = [];
    
    // Process income methods
    foreach ($incomeResults as $row) {
        $method = $row['method'];
        $methodTotals[$method] = isset($methodTotals[$method]) ? 
            $methodTotals[$method] + floatval($row['total']) : 
            floatval($row['total']);
    }
    
    // Process expense methods
    foreach ($expenseResults as $row) {
        $method = $row['method'];
        $methodTotals[$method] = isset($methodTotals[$method]) ? 
            $methodTotals[$method] + floatval($row['total']) : 
            floatval($row['total']);
    }
    
    // Sort by amount descending
    arsort($methodTotals);
    
    $labels = [];
    $amounts = [];

    foreach ($methodTotals as $method => $total) {
        $labels[] = $method;
        $amounts[] = $total;
    }
    
    return [
        'labels' => $labels,
        'amounts' => $amounts
    ];
}