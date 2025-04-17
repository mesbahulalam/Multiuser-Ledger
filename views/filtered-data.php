<div class="container mx-auto px-4 mt-4">
    <h1 class="text-2xl font-bold mb-4">Financial Data Analysis</h1>
    
    <!-- Month Selection Dropdown -->
    <div class="mb-6 bg-white rounded-lg shadow-md p-4">
        <div class="flex items-center">
            <label class="mr-2 font-medium">Select Period:</label>
            <select id="monthSelector" class="border rounded px-3 py-2">
                <option value="all-time">All Time</option>
                <!-- Other options will be populated dynamically -->
            </select>
        </div>
    </div>
    
    <!-- Category Analysis -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div class="bg-white rounded-lg shadow-md">
            <div class="bg-gray-100 px-4 py-2 rounded-t-lg font-medium">Income by Category</div>
            <div class="p-4">
                <div class="chart-container" style="position: relative; height: 300px; width: 100%;">
                    <canvas id="incomeCategoryChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md">
            <div class="bg-gray-100 px-4 py-2 rounded-t-lg font-medium">Expense by Category</div>
            <div class="p-4">
                <div class="chart-container" style="position: relative; height: 300px; width: 100%;">
                    <canvas id="expenseCategoryChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-2 gap-4 mb-4">

        <!-- Bank Distribution -->
        <div class="bg-white rounded-lg shadow-md">
            <div class="bg-gray-100 px-4 py-2 rounded-t-lg font-medium">Money Distribution by Bank</div>
            <div class="p-4">
                <div class="chart-container" style="position: relative; height: 450px; width: 100%;">
                    <canvas id="bankDistributionChart"></canvas>
                </div>
            </div>
        </div>
    
        <!-- Payment Methods Distribution -->
        <div class="bg-white rounded-lg shadow-md">
            <div class="bg-gray-100 px-4 py-2 rounded-t-lg font-medium">Payment Methods Distribution</div>
            <div class="p-4">
                <div class="chart-container" style="position: relative; height: 450px; width: 100%;">
                    <canvas id="paymentMethodsChart"></canvas>
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
    
    // Generate month options for the last 12 months
    function populateMonthOptions() {
        const select = document.getElementById('monthSelector');
        const today = new Date();
        
        // "All Time" option is already added in HTML
        
        // Add options for the last 12 months
        for (let i = 0; i < 12; i++) {
            const d = new Date();
            d.setMonth(today.getMonth() - i);
            
            const monthYear = d.toLocaleString('default', { month: 'long', year: 'numeric' });
            const value = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
            
            const option = document.createElement('option');
            option.value = value;
            option.textContent = monthYear;
            
            select.appendChild(option);
        }
    }
    
    // Initialize charts and load data
    document.addEventListener('DOMContentLoaded', function() {
        // Populate month dropdown
        populateMonthOptions();
        
        // Load initial data
        loadChartData();
        
        // Add event listener for month selection
        document.getElementById('monthSelector').addEventListener('change', loadChartData);
    });
    
    // Load chart data from API based on selected month
    function loadChartData() {
        const selectedValue = document.getElementById('monthSelector').value;
        let apiUrl;
        
        if (selectedValue === 'all-time') {
            // For "All Time" option, don't specify dates and let the API use defaults
            apiUrl = `/api/finance/filtered-data?comparisonType=category&allTime=true`;
        } else {
            // For specific month selection
            const [year, month] = selectedValue.split('-');
            
            // Calculate start and end dates for the selected month
            const startDate = `${year}-${month}-01`;
            const lastDayOfMonth = new Date(year, month, 0).getDate();
            const endDate = `${year}-${month}-${lastDayOfMonth}`;
            
            apiUrl = `/api/finance/filtered-data?startDate=${startDate}&endDate=${endDate}&comparisonType=category`;
        }
        
        // Make API request
        fetch(apiUrl)
            .then(response => response.json())
            .then(data => {
                renderCharts(data);
            })
            .catch(error => {
                console.error('Error loading data:', error);
            });
    }
    
    // Render all charts with the retrieved data
    function renderCharts(data) {
        createIncomeCategoryChart(data.incomeByCategory);
        createExpenseCategoryChart(data.expenseByCategory);
        createBankDistributionChart(data.bankDistribution);
        createPaymentMethodsChart(data.paymentMethods);
    }
    
    // Create Income Category Chart
    function createIncomeCategoryChart(data) {
        const ctx = document.getElementById('incomeCategoryChart').getContext('2d');
        
        // Destroy previous chart if it exists
        if (window.incomeCategoryChart instanceof Chart) {
            window.incomeCategoryChart.destroy();
        }
        
        window.incomeCategoryChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.amounts,
                    backgroundColor: getColors(data.labels.length)
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
    
    // Create Expense Category Chart
    function createExpenseCategoryChart(data) {
        const ctx = document.getElementById('expenseCategoryChart').getContext('2d');
        
        // Destroy previous chart if it exists
        if (window.expenseCategoryChart instanceof Chart) {
            window.expenseCategoryChart.destroy();
        }
        
        window.expenseCategoryChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.amounts,
                    backgroundColor: getColors(data.labels.length)
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
    
    // Create Bank Distribution Chart
    function createBankDistributionChart(data) {
        const ctx = document.getElementById('bankDistributionChart').getContext('2d');
        
        // Destroy previous chart if it exists
        if (window.bankDistributionChart instanceof Chart) {
            window.bankDistributionChart.destroy();
        }
        
        window.bankDistributionChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.amounts,
                    backgroundColor: getColors(data.labels.length)
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
    
    // Create Payment Methods Chart
    function createPaymentMethodsChart(data) {
        const ctx = document.getElementById('paymentMethodsChart').getContext('2d');
        
        // Destroy previous chart if it exists
        if (window.paymentMethodsChart instanceof Chart) {
            window.paymentMethodsChart.destroy();
        }
        
        window.paymentMethodsChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Transaction Amount',
                    data: data.amounts,
                    backgroundColor: getColors(data.labels.length)
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',  // Horizontal bar chart
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return formatCurrency(value).replace('.00', '');
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.dataset.label || '';
                                const value = formatCurrency(context.raw);
                                return `${label}: ${value}`;
                            }
                        }
                    }
                }
            }
        });
    }
</script>
