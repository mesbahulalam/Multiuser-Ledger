<style>
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        .positive {
            color: #10b981;
        }
        .negative {
            color: #ef4444;
        }
    </style>
    <div class="container mx-auto px-4 mt-4">
        <h1 class="text-2xl font-bold mb-4">Financial Dashboard</h1>
        
        <!-- Today & This Week Summary -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div class="bg-white rounded-lg shadow-md">
                <div class="bg-gray-100 px-4 py-2 rounded-t-lg font-medium">Today's Summary</div>
                <div class="p-4">
                    <div class="grid grid-cols-3">
                        <div class="text-center p-3">
                            <div class="text-2xl font-bold" id="todayIncome">$0</div>
                            <div class="text-sm text-gray-500">Income</div>
                        </div>
                        <div class="text-center p-3">
                            <div class="text-2xl font-bold" id="todayExpense">$0</div>
                            <div class="text-sm text-gray-500">Expense</div>
                        </div>
                        <div class="text-center p-3">
                            <div class="text-2xl font-bold" id="todayNet">$0</div>
                            <div class="text-sm text-gray-500">Net</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md">
                <div class="bg-gray-100 px-4 py-2 rounded-t-lg font-medium">This Week's Summary</div>
                <div class="p-4">
                    <div class="grid grid-cols-3">
                        <div class="text-center p-3">
                            <div class="text-2xl font-bold" id="weekIncome">$0</div>
                            <div class="text-sm text-gray-500">Income</div>
                        </div>
                        <div class="text-center p-3">
                            <div class="text-2xl font-bold" id="weekExpense">$0</div>
                            <div class="text-sm text-gray-500">Expense</div>
                        </div>
                        <div class="text-center p-3">
                            <div class="text-2xl font-bold" id="weekNet">$0</div>
                            <div class="text-sm text-gray-500">Net</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Monthly Comparison -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div class="bg-white rounded-lg shadow-md">
                <div class="bg-gray-100 px-4 py-2 rounded-t-lg font-medium">This Month vs. Projected</div>
                <div class="p-4">
                    <div class="chart-container">
                        <canvas id="monthlyComparisonChart"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md">
                <div class="bg-gray-100 px-4 py-2 rounded-t-lg font-medium">This Month vs. Last Month</div>
                <div class="p-4">
                    <div class="chart-container">
                        <canvas id="monthlyComparisonLastChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Six Month Trend -->
        <div class="grid grid-cols-1 gap-4 mb-4">
            <div class="bg-white rounded-lg shadow-md">
                <div class="bg-gray-100 px-4 py-2 rounded-t-lg font-medium">Last 6 Months Trend</div>
                <div class="p-4">
                    <div class="chart-container">
                        <canvas id="sixMonthChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Income vs Expense Comparison Chart -->
        <div class="grid grid-cols-1 gap-4 mb-4">
            <div class="bg-white rounded-lg shadow-md">
                <div class="bg-gray-100 px-4 py-2 rounded-t-lg font-medium">Income vs Expense Comparison</div>
                <div class="p-4">
                    <div class="chart-container">
                        <canvas id="incomeExpenseComparisonChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Year to Date and Categories -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div class="bg-white rounded-lg shadow-md">
                <div class="bg-gray-100 px-4 py-2 rounded-t-lg font-medium">This Year vs. Last Year</div>
                <div class="p-4">
                    <div class="chart-container">
                        <canvas id="yearComparisonChart"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md">
                <div class="bg-gray-100 px-4 py-2 rounded-t-lg font-medium">Annual Monthly Trends</div>
                <div class="p-4">
                    <div class="chart-container">
                        <canvas id="monthlyTrendsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Category Analysis -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div class="bg-white rounded-lg shadow-md">
                <div class="bg-gray-100 px-4 py-2 rounded-t-lg font-medium">Income by Category (This Month)</div>
                <div class="p-4">
                    <div class="chart-container">
                        <canvas id="incomeCategoryChart"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md">
                <div class="bg-gray-100 px-4 py-2 rounded-t-lg font-medium">Expense by Category (This Month)</div>
                <div class="p-4">
                    <div class="chart-container">
                        <canvas id="expenseCategoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Money Distribution -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div class="bg-white rounded-lg shadow-md">
                <div class="bg-gray-100 px-4 py-2 rounded-t-lg font-medium">Money Distribution (Bank Balances)</div>
                <div class="p-4">
                    <div class="chart-container">
                        <canvas id="moneyDistributionChart"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md">
                <div class="bg-gray-100 px-4 py-2 rounded-t-lg font-medium">Cash Position</div>
                <div class="p-4">
                    <div class="grid grid-cols-3">
                        <div class="text-center p-3">
                            <div class="text-2xl font-bold" id="totalNet">$0</div>
                            <div class="text-sm text-gray-500">Net Position</div>
                        </div>
                        <div class="text-center p-3">
                            <div class="text-2xl font-bold" id="pendingIncome">$0</div>
                            <div class="text-sm text-gray-500">Pending Income</div>
                        </div>
                        <div class="text-center p-3">
                            <div class="text-2xl font-bold" id="pendingExpense">$0</div>
                            <div class="text-sm text-gray-500">Pending Expense</div>
                        </div>
                    </div>
                    <div class="chart-container mt-3">
                        <canvas id="cashPositionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Utility function to format currency
        function formatCurrency(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(amount);
        }
        
        // Utility function to get color schemes
        function getColors(count) {
            const baseColors = [
                '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
                '#fd7e14', '#6f42c1', '#20c9a6', '#5a5c69', '#858796'
            ];
            
            let colors = [];
            for (let i = 0; i < count; i++) {
                colors.push(baseColors[i % baseColors.length]);
            }
            return colors;
        }

        // Fetch data and render dashboard
        document.addEventListener('DOMContentLoaded', function() {
            fetch('/api/summary')
                .then(response => response.json())
                .then(data => {
                    renderDashboard(data);
                })
                .catch(error => console.error('Error loading data:', error));
        });

        function renderDashboard(data) {
            // Today's metrics
            document.getElementById('todayIncome').textContent = formatCurrency(data.today.income);
            document.getElementById('todayExpense').textContent = formatCurrency(data.today.expense);
            document.getElementById('todayNet').textContent = formatCurrency(data.today.net);
            document.getElementById('todayNet').classList.add(data.today.net >= 0 ? 'positive' : 'negative');
            
            // This week's metrics
            document.getElementById('weekIncome').textContent = formatCurrency(data.thisWeek.income);
            document.getElementById('weekExpense').textContent = formatCurrency(data.thisWeek.expense);
            document.getElementById('weekNet').textContent = formatCurrency(data.thisWeek.net);
            document.getElementById('weekNet').classList.add(data.thisWeek.net >= 0 ? 'positive' : 'negative');
            
            // Cash position metrics
            document.getElementById('totalNet').textContent = formatCurrency(data.cashPosition.netPosition);
            document.getElementById('pendingIncome').textContent = formatCurrency(data.cashPosition.pendingIncome);
            document.getElementById('pendingExpense').textContent = formatCurrency(data.cashPosition.pendingExpense);
            document.getElementById('totalNet').classList.add(data.cashPosition.netPosition >= 0 ? 'positive' : 'negative');
            
            // Create charts
            createMonthlyComparisonChart(data);
            createMonthlyComparisonLastChart(data);
            createSixMonthChart(data);
            createYearComparisonChart(data);
            createMonthlyTrendsChart(data);
            createIncomeCategoryChart(data);
            createExpenseCategoryChart(data);
            createMoneyDistributionChart(data);
            createCashPositionChart(data);
            createIncomeExpenseComparisonChart(data);
        }
        
        function createMonthlyComparisonChart(data) {
            const ctx = document.getElementById('monthlyComparisonChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Income', 'Expense'],
                    datasets: [
                        {
                            label: 'Actual',
                            data: [data.thisMonth.income, data.thisMonth.expense],
                            backgroundColor: ['rgba(78, 115, 223, 0.8)', 'rgba(231, 74, 59, 0.8)']
                        },
                        {
                            label: 'Projected',
                            data: [data.thisMonth.projectedIncome, data.thisMonth.projectedExpense],
                            backgroundColor: ['rgba(78, 115, 223, 0.3)', 'rgba(231, 74, 59, 0.3)']
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return formatCurrency(value).replace('.00', '');
                                }
                            }
                        }
                    }
                }
            });
        }
        
        function createMonthlyComparisonLastChart(data) {
            const ctx = document.getElementById('monthlyComparisonLastChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Income', 'Expense', 'Net'],
                    datasets: [
                        {
                            label: 'This Month',
                            data: [data.thisMonth.income, data.thisMonth.expense, data.thisMonth.net],
                            backgroundColor: 'rgba(78, 115, 223, 0.7)'
                        },
                        {
                            label: 'Last Month',
                            data: [data.lastMonth.income, data.lastMonth.expense, data.lastMonth.net],
                            backgroundColor: 'rgba(54, 185, 204, 0.7)'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return formatCurrency(value).replace('.00', '');
                                }
                            }
                        }
                    }
                }
            });
        }
        
        function createSixMonthChart(data) {
            const ctx = document.getElementById('sixMonthChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.lastSixMonths.labels,
                    datasets: [
                        {
                            label: 'Income',
                            data: data.lastSixMonths.income,
                            borderColor: 'rgba(78, 115, 223, 1)',
                            backgroundColor: 'rgba(78, 115, 223, 0.1)',
                            fill: true,
                            tension: 0.3
                        },
                        {
                            label: 'Expense',
                            data: data.lastSixMonths.expense,
                            borderColor: 'rgba(231, 74, 59, 1)',
                            backgroundColor: 'rgba(231, 74, 59, 0.1)',
                            fill: true,
                            tension: 0.3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return formatCurrency(value).replace('.00', '');
                                }
                            }
                        }
                    }
                }
            });
        }
        
        function createYearComparisonChart(data) {
            const ctx = document.getElementById('yearComparisonChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Income', 'Expense', 'Net'],
                    datasets: [
                        {
                            label: 'This Year',
                            data: [data.thisYear.income, data.thisYear.expense, data.thisYear.net],
                            backgroundColor: 'rgba(28, 200, 138, 0.7)'
                        },
                        {
                            label: 'Last Year',
                            data: [data.lastYear.income, data.lastYear.expense, data.lastYear.net],
                            backgroundColor: 'rgba(246, 194, 62, 0.7)'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return formatCurrency(value).replace('.00', '');
                                }
                            }
                        }
                    }
                }
            });
        }
        
        function createMonthlyTrendsChart(data) {
            const months = data.monthlyTrends.map(item => item.month);
            const income = data.monthlyTrends.map(item => item.income);
            const expense = data.monthlyTrends.map(item => item.expense);
            const profit = data.monthlyTrends.map(item => item.profit);
            
            const ctx = document.getElementById('monthlyTrendsChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [
                        {
                            label: 'Income',
                            data: income,
                            borderColor: 'rgba(78, 115, 223, 1)',
                            backgroundColor: 'transparent',
                            tension: 0.3
                        },
                        {
                            label: 'Expense',
                            data: expense,
                            borderColor: 'rgba(231, 74, 59, 1)',
                            backgroundColor: 'transparent',
                            tension: 0.3
                        },
                        {
                            label: 'Profit',
                            data: profit,
                            borderColor: 'rgba(28, 200, 138, 1)',
                            backgroundColor: 'transparent',
                            tension: 0.3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            ticks: {
                                callback: function(value) {
                                    return formatCurrency(value).replace('.00', '');
                                }
                            }
                        }
                    }
                }
            });
        }
        
        function createIncomeCategoryChart(data) {
            const ctx = document.getElementById('incomeCategoryChart').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: data.incomeByCategory.labels,
                    datasets: [{
                        data: data.incomeByCategory.amounts,
                        backgroundColor: getColors(data.incomeByCategory.labels.length)
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = formatCurrency(context.raw);
                                    return `${label}: ${value}`;
                                }
                            }
                        }
                    }
                }
            });
        }
        
        function createExpenseCategoryChart(data) {
            const ctx = document.getElementById('expenseCategoryChart').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: data.expenseByCategory.labels,
                    datasets: [{
                        data: data.expenseByCategory.amounts,
                        backgroundColor: getColors(data.expenseByCategory.labels.length)
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = formatCurrency(context.raw);
                                    return `${label}: ${value}`;
                                }
                            }
                        }
                    }
                }
            });
        }
        
        function createMoneyDistributionChart(data) {
            const ctx = document.getElementById('moneyDistributionChart').getContext('2d');
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: data.moneyDistribution.labels,
                    datasets: [{
                        data: data.moneyDistribution.amounts,
                        backgroundColor: getColors(data.moneyDistribution.labels.length)
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = formatCurrency(context.raw);
                                    return `${label}: ${value}`;
                                }
                            }
                        }
                    }
                }
            });
        }
        
        function createCashPositionChart(data) {
            const ctx = document.getElementById('cashPositionChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Total Position', 'Pending'],
                    datasets: [
                        {
                            label: 'Income',
                            data: [data.cashPosition.totalIncome, data.cashPosition.pendingIncome],
                            backgroundColor: 'rgba(78, 115, 223, 0.7)'
                        },
                        {
                            label: 'Expense',
                            data: [data.cashPosition.totalExpense, data.cashPosition.pendingExpense],
                            backgroundColor: 'rgba(231, 74, 59, 0.7)'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return formatCurrency(value).replace('.00', '');
                                }
                            }
                        }
                    }
                }
            });
        }
        
        function createIncomeExpenseComparisonChart(data) {
            const ctx = document.getElementById('incomeExpenseComparisonChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.incomeExpenseComparison.labels,
                    datasets: [
                        {
                            label: 'Income',
                            data: data.incomeExpenseComparison.income,
                            backgroundColor: 'rgba(78, 115, 223, 0.7)'
                        },
                        {
                            label: 'Expense',
                            data: data.incomeExpenseComparison.expense,
                            backgroundColor: 'rgba(231, 74, 59, 0.7)'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return formatCurrency(value).replace('.00', '');
                                }
                            }
                        }
                    }
                }
            });
        }
    </script>