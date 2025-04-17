<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-semibold text-gray-800">Financial Projections</h1>
    </div>

    <!-- Main Content Area -->
    <div x-data="{
        activeTab: 'form',
        showForm: true,
        formType: 'income',
        projectionId: null,
        projections: [],
        formData: {
            type: 'income',
            source: '',
            category: '',
            purpose: '',
            amount: '',
            notes: '',
            date_created: ''
        },
        filters: {
            type: 'all',
            startDate: '',
            endDate: '',
            category: '',
            search: ''
        },
        pagination: {
            currentPage: 1,
            totalPages: 1,
            itemsPerPage: 10
        },
        summary: {
            total_income: 0,
            total_expenses: 0,
            net_amount: 0,
            period: {
                start: '',
                end: ''
            }
        },
        formSuccess: '',
        formError: '',
        categories: {
            income: [],
            expense: []
        },
        isEditing: false,
        isLoading: false,
        async fetchSummary() {
            try {
                const response = await fetch('/api/projection/summary');
                const data = await response.json();
                this.summary = data;
            } catch (error) {
                console.error('Error fetching projection summary:', error);
            }
        },
        async fetchProjections() {
            this.isLoading = true;
            try {
                const response = await fetch(`/api/projection/list?type=${this.filters.type}&page=${this.pagination.currentPage}&itemsPerPage=${this.pagination.itemsPerPage}&search=${this.filters.search}&startDate=${this.filters.startDate}&endDate=${this.filters.endDate}&category=${this.filters.category}`);
                const data = await response.json();
                this.projections = data.items;
                this.pagination.totalPages = data.totalPages;
                this.pagination.currentPage = data.currentPage;
            } catch (error) {
                console.error('Error fetching projections:', error);
            } finally {
                this.isLoading = false;
            }
        },
        async fetchCategories() {
            try {
                const response = await fetch(`/api/projection/categories?type=${this.formType}`);
                const data = await response.json();
                this.categories = data;
            } catch (error) {
                console.error('Error fetching categories:', error);
            }
        },
        async submitForm() {
            this.formSuccess = '';
            this.formError = '';

            // Basic validation
            if (!this.formData.source) {
                this.formError = this.formData.type === 'income' ? 'Income source is required' : 'Expense source is required';
                return;
            }
            if (!this.formData.amount || isNaN(this.formData.amount) || parseFloat(this.formData.amount) <= 0) {
                this.formError = 'Please enter a valid amount';
                return;
            }

            try {
                const endpoint = this.isEditing ? '/api/projection/update' : '/api/projection/add';
                const payload = {
                    id: this.projectionId,
                    type: this.formData.type,
                    source: this.formData.source,
                    category: this.formData.category,
                    purpose: this.formData.purpose,
                    amount: parseFloat(this.formData.amount),
                    notes: this.formData.notes,
                    date_created: this.formData.date_created || new Date().toISOString().split('T')[0]
                };

                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();
                
                if (result.success) {
                    this.formSuccess = result.message;
                    // Reset form
                    this.resetForm();
                    // Refresh projections
                    this.fetchProjections();
                } else {
                    this.formError = result.error;
                }
            } catch (error) {
                this.formError = 'An error occurred while processing your request';
                console.error(error);
            }
        },
        async deleteProjection(id, type) {
            if (!confirm('Are you sure you want to delete this projection?')) return;
            
            try {
                const response = await fetch('/api/projection/delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id, type })
                });

                const result = await response.json();
                
                if (result.success) {
                    this.formSuccess = result.message;
                    // Refresh projections
                    this.fetchProjections();
                } else {
                    this.formError = result.error;
                }
            } catch (error) {
                this.formError = 'An error occurred while deleting the projection';
                console.error(error);
            }
        },
        editProjection(projection) {
            this.isEditing = true;
            this.projectionId = projection.id;
            this.formData.type = projection.type;
            this.formType = projection.type;
            this.formData.source = projection.source || (projection.type === 'income' ? projection.income_from : projection.expense_by);
            this.formData.category = projection.category;
            this.formData.purpose = projection.purpose || '';
            this.formData.amount = projection.amount;
            this.formData.notes = projection.notes;
            this.formData.date_created = new Date(projection.date_created || projection.date_realized).toISOString().split('T')[0];
            
            this.showForm = true;
            this.activeTab = 'form';
            this.fetchCategories();
        },
        resetForm() {
            this.isEditing = false;
            this.projectionId = null;
            this.formData = {
                type: this.formType,
                source: '',
                category: '',
                purpose: '',
                amount: '',
                notes: '',
                date_created: new Date().toISOString().split('T')[0]
            };
        },
        formatDate(dateString) {
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            return new Date(dateString).toLocaleDateString(undefined, options);
        },
        formatCurrency(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(amount);
        },
        changeType(type) {
            this.formType = type;
            this.formData.type = type;
            this.fetchCategories();
        },
        toggleForm() {
            this.showForm = !this.showForm;
        },
        nextPage() {
            if (this.pagination.currentPage < this.pagination.totalPages) {
                this.pagination.currentPage++;
                this.fetchProjections();
            }
        },
        prevPage() {
            if (this.pagination.currentPage > 1) {
                this.pagination.currentPage--;
                this.fetchProjections();
            }
        }
    }" 
    x-init="() => {
        fetchProjections();
        fetchCategories();
        fetchSummary();
        formData.date_created = new Date().toISOString().split('T')[0];
    }"
    @reset-filters="filters = {
        type: 'all',
        startDate: '',
        endDate: '',
        category: '',
        search: ''
    }; fetchProjections();"
    >

        <div class="bg-white shadow-md rounded-lg">
            <!-- Summary Card -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-lg font-medium text-gray-800 mb-4">This Month's Projections</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-green-50 rounded-lg p-4">
                        <div class="text-sm text-green-600 font-medium">Projected Income</div>
                        <div class="text-2xl font-bold text-green-700" x-text="formatCurrency(summary.total_income)"></div>
                    </div>
                    
                    <div class="bg-red-50 rounded-lg p-4">
                        <div class="text-sm text-red-600 font-medium">Projected Expenses</div>
                        <div class="text-2xl font-bold text-red-700" x-text="formatCurrency(summary.total_expenses)"></div>
                    </div>
                    
                    <div class="bg-blue-50 rounded-lg p-4">
                        <div class="text-sm text-blue-600 font-medium">Net Projection</div>
                        <div class="text-2xl font-bold" 
                            :class="{'text-green-700': summary.net_amount >= 0, 'text-red-700': summary.net_amount < 0}"
                            x-text="formatCurrency(summary.net_amount)"></div>
                    </div>
                </div>
                
                <div class="text-xs text-gray-500 mt-2">
                    Period: <span x-text="summary.period.start"></span> to <span x-text="summary.period.end"></span>
                </div>
            </div>
        </div>

        <div class="bg-white shadow-md rounded-lg">
            <!-- Tab Navigation -->
            <div class="flex border-b border-gray-200">
                <button 
                    @click="activeTab = 'form'; showForm = true" 
                    :class="{'text-blue-600 border-b-2 border-blue-600': activeTab === 'form', 'text-gray-500': activeTab !== 'form'}" 
                    class="flex-1 py-4 px-6 text-center font-medium">
                    Add Projection
                </button>
                <button 
                    @click="activeTab = 'list'; fetchProjections()" 
                    :class="{'text-blue-600 border-b-2 border-blue-600': activeTab === 'list', 'text-gray-500': activeTab !== 'list'}" 
                    class="flex-1 py-4 px-6 text-center font-medium">
                    View Projections
                </button>
            </div>

            <!-- Form Tab -->
            <div x-show="activeTab === 'form'" class="p-6">
                <div x-show="formSuccess" x-text="formSuccess" class="mb-4 p-2 bg-green-100 text-green-700 rounded"></div>
                <div x-show="formError" x-text="formError" class="mb-4 p-2 bg-red-100 text-red-700 rounded"></div>
                
                <!-- Projection Type Selector -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Projection Type</label>
                    <div class="flex space-x-4">
                        <button 
                            @click="changeType('income')" 
                            :class="{'bg-blue-500 text-white': formType === 'income', 'bg-gray-200 text-gray-800': formType !== 'income'}" 
                            class="px-4 py-2 rounded-lg transition-colors">
                            Income
                        </button>
                        <button 
                            @click="changeType('expense')" 
                            :class="{'bg-blue-500 text-white': formType === 'expense', 'bg-gray-200 text-gray-800': formType !== 'expense'}" 
                            class="px-4 py-2 rounded-lg transition-colors">
                            Expense
                        </button>
                    </div>
                </div>
                
                <!-- Unified Form -->
                <form @submit.prevent="submitForm" class="space-y-4">
                    <!-- Source Field - Different label based on type -->
                    <div>
                        <label for="source" class="block text-sm font-medium text-gray-700 mb-2" x-text="formType === 'income' ? 'Income Source' : 'Expense Source'"></label>
                        <input type="text" id="source" x-model="formData.source" class="form-input w-full rounded p-2 border-gray-300 shadow-sm" required>
                    </div>
                    
                    <!-- Category Field -->
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <div class="relative" 
                            x-data="{ 
                                search: '', 
                                open: false, 
                                selectedIndex: -1,
                                init() {
                                    this.$watch('search', value => { formData.category = value; }); 
                                    this.search = formData.category;
                                },
                                get items() { 
                                    return formType === 'income' ? categories.income : categories.expense; 
                                },
                                get filteredItems() {
                                    if (!this.search) return this.items;
                                    return this.items.filter(item => 
                                        item && item.toLowerCase().includes(this.search.toLowerCase())
                                    );
                                }
                            }">
                            <input 
                                type="text"
                                id="category"
                                x-model="search"
                                @focus="open = true"
                                @blur="setTimeout(() => { open = false }, 200)"
                                @keydown.arrow-down.prevent="selectedIndex = Math.min(selectedIndex + 1, filteredItems.length - 1)"
                                @keydown.arrow-up.prevent="selectedIndex = Math.max(selectedIndex - 1, 0)"
                                @keydown.enter.prevent="if(selectedIndex >= 0) { search = filteredItems[selectedIndex]; open = false }"
                                class="form-input w-full rounded p-2 border-gray-300 shadow-sm"
                                placeholder="Category"
                                required>
                            <div x-show="open && filteredItems.length > 0" 
                                class="absolute mt-1 w-full bg-white border rounded shadow-lg z-10">
                                <ul class="max-h-60 overflow-auto">
                                    <template x-for="(item, idx) in filteredItems" :key="idx">
                                        <li 
                                            x-text="item"
                                            @click="search = item; open = false"
                                            :class="{'bg-blue-100': selectedIndex === idx}"
                                            class="p-2 cursor-pointer hover:bg-gray-100">
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Purpose Field - Only shown for expense -->
                    <div x-show="formType === 'expense'">
                        <label for="purpose" class="block text-sm font-medium text-gray-700 mb-2">Purpose</label>
                        <input type="text" id="purpose" x-model="formData.purpose" class="form-input w-full rounded p-2 border-gray-300 shadow-sm">
                    </div>
                    
                    <!-- Amount Field -->
                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">Amount</label>
                        <input type="number" id="amount" x-model="formData.amount" step="0.01" min="0" class="form-input w-full rounded p-2 border-gray-300 shadow-sm" required>
                    </div>
                    
                    <!-- Date Field -->
                    <div>
                        <label for="date_created" class="block text-sm font-medium text-gray-700 mb-2">Date</label>
                        <input type="date" id="date_created" x-model="formData.date_created" class="form-input w-full rounded p-2 border-gray-300 shadow-sm" required>
                    </div>
                    
                    <!-- Notes Field -->
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                        <textarea id="notes" x-model="formData.notes" class="form-textarea w-full rounded p-2 border-gray-300 shadow-sm" rows="3"></textarea>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="flex justify-end space-x-3">
                        <button type="button" @click="resetForm" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
                            Reset
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                            <span x-text="isEditing ? 'Update' : 'Save'"></span> Projection
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- List Tab -->
            <div x-show="activeTab === 'list'" class="p-6">
                <div x-show="formSuccess" x-text="formSuccess" class="mb-4 p-2 bg-green-100 text-green-700 rounded"></div>
                <div x-show="formError" x-text="formError" class="mb-4 p-2 bg-red-100 text-red-700 rounded"></div>
                
                <!-- Filters -->
                <div class="mb-6 bg-gray-50 p-4 rounded-lg">
                    <h3 class="text-lg font-medium text-gray-700 mb-3">Filters</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Type Filter -->
                        <div>
                            <label for="filter-type" class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                            <select id="filter-type" x-model="filters.type" @change="fetchProjections()" class="form-select w-full rounded p-2 border-gray-300 shadow-sm">
                                <option value="all">All</option>
                                <option value="income">Income</option>
                                <option value="expense">Expense</option>
                            </select>
                        </div>
                        
                        <!-- Date Range Filters -->
                        <div>
                            <label for="filter-start-date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                            <input type="date" id="filter-start-date" x-model="filters.startDate" @change="fetchProjections()" class="form-input w-full rounded p-2 border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label for="filter-end-date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                            <input type="date" id="filter-end-date" x-model="filters.endDate" @change="fetchProjections()" class="form-input w-full rounded p-2 border-gray-300 shadow-sm">
                        </div>
                        
                        <!-- Category Filter -->
                        <div>
                            <label for="filter-category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                            <select id="filter-category" x-model="filters.category" @change="fetchProjections()" class="form-select w-full rounded p-2 border-gray-300 shadow-sm">
                                <option value="">All Categories</option>
                                <template x-for="category in categories.income.concat(categories.expense)">
                                    <option x-text="category" :value="category"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Search and Reset -->
                    <div class="mt-4 flex flex-col md:flex-row space-y-2 md:space-y-0 md:space-x-2">
                        <div class="flex-grow">
                            <input type="text" x-model="filters.search" placeholder="Search projections..." class="form-input w-full rounded p-2 border-gray-300 shadow-sm">
                        </div>
                        <div class="flex space-x-2">
                            <button @click="fetchProjections()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                                Search
                            </button>
                            <button @click="$dispatch('reset-filters')" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
                                Reset
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Projections Table -->
                <div class="overflow-x-auto">
                    <div x-show="isLoading" class="text-center py-4">
                        <div class="inline-block animate-spin rounded-full h-6 w-6 border-t-2 border-b-2 border-blue-500"></div>
                        <span class="ml-2">Loading...</span>
                    </div>
                    
                    <table x-show="!isLoading" class="min-w-full bg-white">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                                <th class="py-3 px-4 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <template x-if="projections.length === 0">
                                <tr>
                                    <td colspan="7" class="px-4 py-5 text-center text-sm text-gray-500">
                                        No projections found.
                                    </td>
                                </tr>
                            </template>
                            <template x-for="projection in projections" :key="projection.id + (projection.type || '')">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span 
                                            :class="projection.type === 'income' || projection.income_from ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" 
                                            class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                                            x-text="projection.type === 'income' || projection.income_from ? 'Income' : 'Expense'">
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap" x-text="projection.source || projection.income_from || projection.expense_by"></td>
                                    <td class="px-4 py-3 whitespace-nowrap" x-text="projection.category"></td>
                                    <td class="px-4 py-3 whitespace-nowrap font-medium" x-text="formatCurrency(projection.amount)"></td>
                                    <td class="px-4 py-3 whitespace-nowrap" x-text="formatDate(projection.date_created || projection.date_realized)"></td>
                                    <td class="px-4 py-3" x-text="projection.notes"></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="editProjection(projection)" class="text-blue-600 hover:text-blue-900">Edit</button>
                                        <button @click="deleteProjection(projection.id, projection.type || (projection.income_from ? 'income' : 'expense'))" class="ml-3 text-red-600 hover:text-red-900">Delete</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="mt-4 flex items-center justify-between">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <button @click="prevPage()" :disabled="pagination.currentPage === 1" :class="{'opacity-50': pagination.currentPage === 1}" class="relative inline-flex items-center px-4 py-2 text-sm font-medium rounded-md text-gray-700 bg-white border border-gray-300 hover:bg-gray-50">
                            Previous
                        </button>
                        <button @click="nextPage()" :disabled="pagination.currentPage === pagination.totalPages" :class="{'opacity-50': pagination.currentPage === pagination.totalPages}" class="ml-3 relative inline-flex items-center px-4 py-2 text-sm font-medium rounded-md text-gray-700 bg-white border border-gray-300 hover:bg-gray-50">
                            Next
                        </button>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing page <span class="font-medium" x-text="pagination.currentPage"></span> of <span class="font-medium" x-text="pagination.totalPages"></span>
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <button @click="prevPage()" :disabled="pagination.currentPage === 1" :class="{'opacity-50': pagination.currentPage === 1}" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Previous</span>
                                    &laquo;
                                </button>
                                <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                                    <span x-text="pagination.currentPage"></span> / <span x-text="pagination.totalPages"></span>
                                </span>
                                <button @click="nextPage()" :disabled="pagination.currentPage === pagination.totalPages" :class="{'opacity-50': pagination.currentPage === pagination.totalPages}" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Next</span>
                                    &raquo;
                                </button>
                            </nav>
                        </div>
                    </div>
                </div>
            </div> 
        </div>

    </div>
</div>