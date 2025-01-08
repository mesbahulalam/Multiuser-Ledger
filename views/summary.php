<h1 class="text-3xl font-bold mb-8">Dashboard</h1>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <!-- Total Users Card -->
    <div class="bg-white p-6 rounded-lg shadow-lg">
        <h3 class="text-gray-500 text-sm font-semibold">Total Users</h3>
        <div class="flex items-center mt-2">
            <div class="text-3xl font-bold"><?php echo Users::getTotalUsers(); ?></div>
        </div>
        <h3 class="text-gray-500 text-sm font-semibold">Total Balance</h3>
        <div class="flex items-center mt-2">
            <div class="text-3xl font-bold">$<?php echo number_format(FinanceManager::getBalance(), 2); ?></div>
        </div>
    </div>
    <!-- add chart js graphs from here. -->
    <div class="bg-white p-6 rounded-lg shadow-lg">
        <h3 class="text-gray-500 text-sm font-semibold">Income vs Expense</h3>
        <canvas id="incomeExpenseChart"></canvas>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-lg">
        <h3 class="text-gray-500 text-sm font-semibold">This Week</h3>
        <canvas id="thisWeekChart"></canvas>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-lg">
        <h3 class="text-gray-500 text-sm font-semibold">This Month vs Last Month</h3>
        <canvas id="thisMonthLastMonthChart"></canvas>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-lg">
        <h3 class="text-gray-500 text-sm font-semibold">Last 6 Months</h3>
        <canvas id="lastSixMonthsChart"></canvas>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-lg">
        <h3 class="text-gray-500 text-sm font-semibold">This Year vs Previous Year</h3>
        <canvas id="thisYearPreviousYearChart"></canvas>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-lg">
        <h3 class="text-gray-500 text-sm font-semibold">Money Distribution</h3>
        <canvas id="moneyDistributionChart"></canvas>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-lg">
        <h3 class="text-gray-500 text-sm font-semibold">Spending by Category (This Month)</h3>
        <canvas id="spendingByCategoryChart"></canvas>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-lg">
        <h3 class="text-gray-500 text-sm font-semibold">Income by Category (This Month)</h3>
        <canvas id="incomeByCategoryChart"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Fetch data from the server
        fetch('/api/chart')
            .then(response => response.json())
            .then(data => {
                // Income vs Expense Chart
                new Chart(document.getElementById('incomeExpenseChart'), {
                    type: 'bar',
                    data: {
                        labels: ['Income', 'Expense'],
                        datasets: [{
                            label: 'Amount',
                            data: [data.today.income, data.today.expense],
                            backgroundColor: ['#4CAF50', '#F44336']
                        }]
                    }
                });

                // This Week Chart
                new Chart(document.getElementById('thisWeekChart'), {
                    type: 'line',
                    data: {
                        labels: data.thisWeek.labels,
                        datasets: [{
                            label: 'Income',
                            data: data.thisWeek.income,
                            borderColor: '#4CAF50',
                            fill: false
                        }, {
                            label: 'Expense',
                            data: data.thisWeek.expense,
                            borderColor: '#F44336',
                            fill: false
                        }]
                    }
                });

                // This Month vs Last Month Chart
                new Chart(document.getElementById('thisMonthLastMonthChart'), {
                    type: 'bar',
                    data: {
                        labels: ['This Month', 'Last Month'],
                        datasets: [{
                            label: 'Income',
                            data: [data.thisMonth.income, data.lastMonth.income],
                            backgroundColor: '#4CAF50'
                        }, {
                            label: 'Expense',
                            data: [data.thisMonth.expense, data.lastMonth.expense],
                            backgroundColor: '#F44336'
                        }]
                    }
                });

                // Last 6 Months Chart
                new Chart(document.getElementById('lastSixMonthsChart'), {
                    type: 'line',
                    data: {
                        labels: data.lastSixMonths.labels,
                        datasets: [{
                            label: 'Income',
                            data: data.lastSixMonths.income,
                            borderColor: '#4CAF50',
                            fill: false
                        }, {
                            label: 'Expense',
                            data: data.lastSixMonths.expense,
                            borderColor: '#F44336',
                            fill: false
                        }]
                    }
                });

                // This Year vs Previous Year Chart
                new Chart(document.getElementById('thisYearPreviousYearChart'), {
                    type: 'bar',
                    data: {
                        labels: ['This Year', 'Previous Year'],
                        datasets: [{
                            label: 'Income',
                            data: [data.thisYear.income, data.previousYear.income],
                            backgroundColor: '#4CAF50'
                        }, {
                            label: 'Expense',
                            data: [data.thisYear.expense, data.previousYear.expense],
                            backgroundColor: '#F44336'
                        }]
                    }
                });

                // Money Distribution Chart
                new Chart(document.getElementById('moneyDistributionChart'), {
                    type: 'pie',
                    data: {
                        labels: data.moneyDistribution.labels,
                        datasets: [{
                            label: 'Amount',
                            data: data.moneyDistribution.amounts,
                            backgroundColor: ['#4CAF50', '#F44336', '#FF9800', '#2196F3']
                        }]
                    }
                });

                // Spending by Category (This Month) Chart
                new Chart(document.getElementById('spendingByCategoryChart'), {
                    type: 'doughnut',
                    data: {
                        labels: data.spendingByCategory.labels,
                        datasets: [{
                            label: 'Amount',
                            data: data.spendingByCategory.amounts,
                            backgroundColor: ['#4CAF50', '#F44336', '#FF9800', '#2196F3', '#9C27B0']
                        }]
                    }
                });

                // Income by Category (This Month) Chart
                new Chart(document.getElementById('incomeByCategoryChart'), {
                    type: 'doughnut',
                    data: {
                        labels: data.incomeByCategory.labels,
                        datasets: [{
                            label: 'Amount',
                            data: data.incomeByCategory.amounts,
                            backgroundColor: ['#4CAF50', '#F44336', '#FF9800', '#2196F3', '#9C27B0']
                        }]
                    }
                });
            });
    });
</script>