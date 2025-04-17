<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-semibold text-gray-800">Income & Expense Management</h1>
    </div>

    <!-- Main Content Area -->
    <div class="bg-white shadow-md rounded-lg" x-data="{
        activeTab: 'form',
        formType: 'income',
        transactionId: null,
        transactions: [],
        formData: {
            type: 'income',
            source: '', // income_from or expense_by
            category: '',
            purpose: '',
            amount: '',
            method: '',
            bank: '',
            account_number: '',
            transaction_number: '',
            notes: '',
            date_created: '',
            attachment_id: null
        },
        filters: {
            type: 'all',
            startDate: '',
            endDate: '',
            category: '',
            status: '',
            search: ''
        },
        pagination: {
            currentPage: 1,
            totalPages: 1,
            itemsPerPage: 10
        },
        formSuccess: '',
        formError: '',
        categories: {
            income: [],
            expense: []
        },
        purposes: [],
        banks: [],
        sourceItems: {
            income: [],
            expense: []
        },
        methods: [
            { id: 'Bank Transfer', text: 'Bank Transfer' },
            { id: 'Bkash', text: 'Bkash' },
            { id: 'Nagad', text: 'Nagad' },
            { id: 'Credit Card', text: 'Credit Card' },
            { id: 'Cash', text: 'Cash' },
            { id: 'Check', text: 'Check' }
        ],
        statuses: [
            { id: 'pending', text: 'Pending' }, 
            { id: 'approved', text: 'Approved' }, 
            { id: 'denied', text: 'Denied' }
        ],
        isEditing: false,
        isLoading: false,
        showNextId: true,
        
        init() {
            this.fetchTransactions();
            this.fetchCategories();
            this.fetchBanks();
            this.fetchPurposes();
            this.fetchSourceItems();
            this.formData.date_created = new Date().toISOString().slice(0, 16);
        },
        
        async fetchTransactions() {
            this.isLoading = true;
            try {
                const endpoint = this.formType === 'income' ? '/api/finance/incomes' : '/api/finance/expenses';
                const queryParams = new URLSearchParams({
                    page: this.pagination.currentPage,
                    itemsPerPage: this.pagination.itemsPerPage,
                    search: this.filters.search,
                    startDate: this.filters.startDate,
                    endDate: this.filters.endDate,
                    category: this.filters.category,
                    status: this.filters.status
                });
                
                const response = await fetch(`${endpoint}?${queryParams.toString()}`);
                const data = await response.json();
                
                this.transactions = data.items;
                this.pagination.totalPages = data.totalPages;
                this.pagination.currentPage = data.currentPage;
            } catch (error) {
                console.error('Error fetching transactions:', error);
            } finally {
                this.isLoading = false;
            }
        },
        
        async fetchCategories() {
            try {
                // Fetch income categories
                const incomeResponse = await fetch('/api/finance/suggestions/incomes/category');
                const incomeData = await incomeResponse.json();
                this.categories.income = incomeData.map(item => item.text);
                
                // Fetch expense categories
                const expenseResponse = await fetch('/api/finance/suggestions/expenses/category');
                const expenseData = await expenseResponse.json();
                this.categories.expense = expenseData.map(item => item.text);
            } catch (error) {
                console.error('Error fetching categories:', error);
                // Provide some defaults in case of error
                this.categories.income = ['Sales', 'Service', 'Investment', 'Other'];
                this.categories.expense = ['Rent', 'Utilities', 'Salary', 'Office Supplies', 'Other'];
            }
        },
        
        async fetchBanks() {
            try {
                // Fetch banks from both income and expense tables
                const incomeResponse = await fetch('/api/finance/suggestions/incomes/bank');
                const expenseResponse = await fetch('/api/finance/suggestions/expenses/bank');
                
                const incomeData = await incomeResponse.json();
                const expenseData = await expenseResponse.json();
                
                // Merge banks and remove duplicates
                const allBanks = [...incomeData, ...expenseData].map(item => item.text);
                this.banks = [...new Set(allBanks)].filter(bank => bank);
                
                if (this.banks.length === 0) {
                    this.banks = ['City Bank', 'Bank of America', 'Chase', 'HSBC', 'Wells Fargo'];
                }
            } catch (error) {
                console.error('Error fetching banks:', error);
                this.banks = ['City Bank', 'Bank of America', 'Chase', 'HSBC', 'Wells Fargo'];
            }
        },
        
        async fetchPurposes() {
            try {
                const response = await fetch('/api/finance/suggestions/expenses/purpose');
                const data = await response.json();
                this.purposes = data.map(item => item.text);
                
                if (this.purposes.length === 0) {
                    this.purposes = ['Office Supplies', 'Rent', 'Utilities', 'Salary', 'Equipment', 'Software', 'Travel'];
                }
            } catch (error) {
                console.error('Error fetching purposes:', error);
                this.purposes = ['Office Supplies', 'Rent', 'Utilities', 'Salary', 'Equipment', 'Software', 'Travel'];
            }
        },
        
        async fetchSourceItems() {
            try {
                // Fetch income sources
                const incomeResponse = await fetch('/api/finance/suggestions/incomes/income_from');
                const incomeData = await incomeResponse.json();
                this.sourceItems.income = incomeData.map(item => item.text);
                
                // Fetch expense sources
                const expenseResponse = await fetch('/api/finance/suggestions/expenses/expense_by');
                const expenseData = await expenseResponse.json();
                this.sourceItems.expense = expenseData.map(item => item.text);
                
                // Set defaults if empty
                if (this.sourceItems.income.length === 0) {
                    this.sourceItems.income = ['Client A', 'Client B', 'Investment', 'Sales'];
                }
                
                if (this.sourceItems.expense.length === 0) {
                    this.sourceItems.expense = ['Vendor A', 'Vendor B', 'Staff', 'Utilities'];
                }
            } catch (error) {
                console.error('Error fetching source items:', error);
                // Provide some defaults in case of error
                this.sourceItems.income = ['Client A', 'Client B', 'Investment', 'Sales'];
                this.sourceItems.expense = ['Vendor A', 'Vendor B', 'Staff', 'Utilities'];
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
            
            if (!this.formData.category) {
                this.formError = 'Category is required';
                return;
            }
            
            if (!this.formData.amount || isNaN(this.formData.amount) || parseFloat(this.formData.amount) <= 0) {
                this.formError = 'Please enter a valid amount';
                return;
            }

            try {
                const endpoint = this.formData.type === 'income' 
                    ? (this.isEditing ? '/api/finance/income/update' : '/api/finance/income/add')
                    : (this.isEditing ? '/api/finance/expense/update' : '/api/finance/expense/add');
                
                // Format the data according to the API expectations
                const payload = this.isEditing ? { id: this.transactionId } : {};
                
                if (this.formData.type === 'income') {
                    payload.income_from = this.formData.source;
                } else {
                    payload.expense_by = this.formData.source;
                    payload.purpose = this.formData.purpose;
                }
                
                payload.category = this.formData.category;
                payload.amount = parseFloat(this.formData.amount);
                payload.method = this.formData.method;
                payload.notes = this.formData.notes;
                payload.date_created = this.formData.date_created;
                
                if (this.formData.method && this.formData.method !== 'Cash') {
                    payload.bank = this.formData.bank;
                    payload.account_number = this.formData.account_number;
                    payload.transaction_number = this.formData.transaction_number;
                }
                
                payload.attachment_id = this.formData.attachment_id;

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
                    // Refresh transactions
                    this.fetchTransactions();
                } else {
                    this.formError = result.error;
                }
            } catch (error) {
                this.formError = 'An error occurred while processing your request';
                console.error(error);
            }
        },
        
        async deleteTransaction(id) {
            if (!confirm('Are you sure you want to delete this transaction?')) return;
            
            try {
                const endpoint = this.formType === 'income' ? '/api/finance/income/delete' : '/api/finance/expense/delete';
                
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id })
                });

                const result = await response.json();
                
                if (result.success) {
                    this.formSuccess = result.message;
                    // Refresh transactions
                    this.fetchTransactions();
                } else {
                    this.formError = result.error;
                }
            } catch (error) {
                this.formError = 'An error occurred while deleting the transaction';
                console.error(error);
            }
        },
        
        async approveTransaction(id) {
            try {
                const endpoint = this.formType === 'income' ? '/api/finance/income/approve' : '/api/finance/expense/approve';
                
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id })
                });

                const result = await response.json();
                
                if (result.success) {
                    this.formSuccess = result.message;
                    // Refresh transactions
                    this.fetchTransactions();
                } else {
                    this.formError = result.error;
                }
            } catch (error) {
                this.formError = 'An error occurred while approving the transaction';
                console.error(error);
            }
        },
        
        editTransaction(transaction) {
            this.isEditing = true;
            this.transactionId = transaction.id;
            this.formType = this.formData.type = transaction.income_from ? 'income' : 'expense';
            this.showNextId = false;
            
            if (transaction.income_from) {
                this.formData.source = transaction.income_from;
            } else {
                this.formData.source = transaction.expense_by;
                this.formData.purpose = transaction.purpose || '';
            }
            
            this.formData.category = transaction.category;
            this.formData.amount = transaction.amount;
            this.formData.method = transaction.method;
            this.formData.bank = transaction.bank;
            this.formData.account_number = transaction.account_number;
            this.formData.transaction_number = transaction.transaction_number;
            this.formData.notes = transaction.notes;
            this.formData.date_created = transaction.date_created ? new Date(transaction.date_created).toISOString().slice(0, 16) : '';
            this.formData.attachment_id = transaction.attachment_id;
            
            this.activeTab = 'form';
        },
        
        resetForm() {
            this.isEditing = false;
            this.transactionId = null;
            this.showNextId = true;
            const currentType = this.formData.type;
            this.formData = {
                type: currentType,
                source: '',
                category: '',
                purpose: '',
                amount: '',
                method: '',
                bank: '',
                account_number: '',
                transaction_number: '',
                notes: '',
                date_created: new Date().toISOString().slice(0, 16),
                attachment_id: null
            };
        },
        
        formatDate(dateString) {
            if (!dateString) return '';
            const options = { year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: 'numeric' };
            return new Date(dateString).toLocaleDateString(undefined, options);
        },
        
        formatCurrency(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(amount);
        },
        
        changeFormType(type) {
            this.formType = type;
            this.formData.type = type;
            this.resetForm();
            
            // Update the table data to match the form type
            this.fetchTransactions();
        },
        
        nextPage() {
            if (this.pagination.currentPage < this.pagination.totalPages) {
                this.pagination.currentPage++;
                this.fetchTransactions();
            }
        },
        
        prevPage() {
            if (this.pagination.currentPage > 1) {
                this.pagination.currentPage--;
                this.fetchTransactions();
            }
        },

        makeAutocomplete(items) {
            return {
                search: '',
                items: items || [],
                showOptions: false,
                selectedIndex: -1,
                get filteredOptions() {
                    if (!this.search) return this.items;
                    return this.items.filter(item => 
                        item && item.toLowerCase().includes(this.search.toLowerCase())
                    );
                }
            }
        }
    }"
    x-init="init()">
        <!-- Tab Navigation -->
        <div class="flex border-b border-gray-200">
            <button 
                @click="activeTab = 'form'" 
                :class="{'text-blue-600 border-b-2 border-blue-600': activeTab === 'form', 'text-gray-500': activeTab !== 'form'}" 
                class="flex-1 py-4 px-6 text-center font-medium">
                Add <span x-text="formType === 'income' ? 'Income' : 'Expense'"></span>
            </button>
            <button 
                @click="activeTab = 'list'; fetchTransactions()" 
                :class="{'text-blue-600 border-b-2 border-blue-600': activeTab === 'list', 'text-gray-500': activeTab !== 'list'}" 
                class="flex-1 py-4 px-6 text-center font-medium">
                View <span x-text="formType === 'income' ? 'Income' : 'Expense'"></span>
            </button>
        </div>

        <!-- Form Tab -->
        <div x-show="activeTab === 'form'" class="p-6">
            <div x-show="formSuccess" x-text="formSuccess" class="mb-4 p-2 bg-green-100 text-green-700 rounded"></div>
            <div x-show="formError" x-text="formError" class="mb-4 p-2 bg-red-100 text-red-700 rounded"></div>
            
            <!-- Transaction Type Selector -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Transaction Type</label>
                <div class="flex space-x-4">
                    <button 
                        @click="changeFormType('income')" 
                        :class="{'bg-green-500 text-white': formType === 'income', 'bg-gray-200 text-gray-800': formType !== 'income'}" 
                        class="px-4 py-2 rounded-lg transition-colors">
                        Income
                    </button>
                    <button 
                        @click="changeFormType('expense')" 
                        :class="{'bg-red-500 text-white': formType === 'expense', 'bg-gray-200 text-gray-800': formType !== 'expense'}" 
                        class="px-4 py-2 rounded-lg transition-colors">
                        Expense
                    </button>
                </div>
            </div>
            
            <!-- Form -->
            <form @submit.prevent="submitForm" class="space-y-4">
                <!-- ID Field -->
                <div x-show="showNextId">
                    <label class="block text-sm font-medium text-gray-700 mb-2">ID</label>
                    <input 
                        type="text" 
                        readonly 
                        :value="formType === 'income' ? '<?= FinanceManager::getNextIncomeId() ?>' : '<?= FinanceManager::getNextExpenseId() ?>'" 
                        class="w-full p-2 border rounded bg-gray-100" 
                        placeholder="ID">
                </div>
                
                <!-- Source Field -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2" x-text="formType === 'income' ? 'Income From' : 'Expense By'"></label>
                    <div class="relative" 
                         x-data="{ 
                             search: '', 
                             open: false, 
                             selectedIndex: -1,
                             init() {
                                this.$watch('search', value => { formData.source = value; }); 
                                this.search = formData.source;
                             },
                             get items() {
                                 return formData.type === 'income' ? sourceItems.income : sourceItems.expense;
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
                            x-model="search"
                            @focus="open = true"
                            @blur="setTimeout(() => { open = false }, 200)"
                            @keydown.arrow-down.prevent="selectedIndex = Math.min(selectedIndex + 1, filteredItems.length - 1)"
                            @keydown.arrow-up.prevent="selectedIndex = Math.max(selectedIndex - 1, 0)"
                            @keydown.enter.prevent="if(selectedIndex >= 0) { search = filteredItems[selectedIndex]; open = false }"
                            class="w-full p-2 border rounded"
                            placeholder="Enter name"
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
                
                <!-- Category Field with Autocomplete -->
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
                            x-model="search"
                            @focus="open = true"
                            @blur="setTimeout(() => { open = false }, 200)"
                            @keydown.arrow-down.prevent="selectedIndex = Math.min(selectedIndex + 1, filteredItems.length - 1)"
                            @keydown.arrow-up.prevent="selectedIndex = Math.max(selectedIndex - 1, 0)"
                            @keydown.enter.prevent="if(selectedIndex >= 0) { search = filteredItems[selectedIndex]; open = false }"
                            class="w-full p-2 border rounded"
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
                    <label class="block text-sm font-medium text-gray-700 mb-2">Purpose</label>
                    <div class="relative"
                         x-data="{ 
                             search: '', 
                             open: false, 
                             selectedIndex: -1,
                             init() { 
                                this.$watch('search', value => { formData.purpose = value; });
                                this.search = formData.purpose; 
                             },
                             get filteredItems() {
                                 if (!this.search) return purposes;
                                 return purposes.filter(item => 
                                    item && item.toLowerCase().includes(this.search.toLowerCase())
                                );
                             }
                         }">
                        <input 
                            type="text"
                            x-model="search"
                            @focus="open = true"
                            @blur="setTimeout(() => { open = false }, 200)"
                            @keydown.arrow-down.prevent="selectedIndex = Math.min(selectedIndex + 1, filteredItems.length - 1)"
                            @keydown.arrow-up.prevent="selectedIndex = Math.max(selectedIndex - 1, 0)"
                            @keydown.enter.prevent="if(selectedIndex >= 0) { search = filteredItems[selectedIndex]; open = false }"
                            class="w-full p-2 border rounded"
                            placeholder="Purpose"
                            :required="formType === 'expense'">
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
                
                <!-- Amount Field -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Amount</label>
                    <input type="number" x-model="formData.amount" step="0.01" min="0" class="w-full p-2 border rounded" required>
                </div>
                
                <!-- Date Field -->
                <div class="flex items-center space-x-2">
                    <div class="flex-grow">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date</label>
                        <input type="datetime-local" x-model="formData.date_created" class="w-full p-2 border rounded" required>
                    </div>
                    <div class="pt-7">
                        <button type="button" class="bg-blue-500 text-white p-2 rounded" @click="formData.date_created = new Date().toISOString().slice(0, 16)">
                            Now
                        </button>
                    </div>
                </div>
                
                <!-- Payment Method -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                    <select x-model="formData.method" class="w-full p-2 border rounded bg-white">
                        <option value="">Select Method</option>
                        <template x-for="method in methods" :key="method.id">
                            <option :value="method.id" x-text="method.text"></option>
                        </template>
                    </select>
                </div>
                
                <!-- Bank Details - Only shown when method is not Cash -->
                <div x-show="formData.method && formData.method !== 'Cash'" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Bank</label>
                        <div class="relative"
                             x-data="{ 
                                 search: '', 
                                 open: false, 
                                 selectedIndex: -1,
                                 init() { 
                                    this.$watch('search', value => { formData.bank = value; });
                                    this.search = formData.bank; 
                                 },
                                 get filteredItems() {
                                     if (!this.search) return banks;
                                     return banks.filter(item => 
                                        item && item.toLowerCase().includes(this.search.toLowerCase())
                                    );
                                 }
                             }">
                            <input 
                                type="text"
                                x-model="search"
                                @focus="open = true"
                                @blur="setTimeout(() => { open = false }, 200)"
                                @keydown.arrow-down.prevent="selectedIndex = Math.min(selectedIndex + 1, filteredItems.length - 1)"
                                @keydown.arrow-up.prevent="selectedIndex = Math.max(selectedIndex - 1, 0)"
                                @keydown.enter.prevent="if(selectedIndex >= 0) { search = filteredItems[selectedIndex]; open = false }"
                                class="w-full p-2 border rounded"
                                placeholder="Bank"
                                :required="formData.method && formData.method !== 'Cash'">
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
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Account Number</label>
                        <input type="text" x-model="formData.account_number" class="w-full p-2 border rounded"
                               :required="formData.method && formData.method !== 'Cash'">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Transaction Number</label>
                        <input type="text" x-model="formData.transaction_number" class="w-full p-2 border rounded"
                               :required="formData.method && formData.method !== 'Cash'">
                    </div>
                </div>
                
                <!-- Notes Field -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                    <textarea x-model="formData.notes" class="w-full p-2 border rounded" rows="3"></textarea>
                </div>
                
                <!-- Submit Button -->
                <div class="flex justify-end space-x-3">
                    <button type="button" @click="resetForm()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
                        Reset
                    </button>
                    <button type="submit" 
                        :class="{'bg-green-500 hover:bg-green-600': formType === 'income', 'bg-red-500 hover:bg-red-600': formType === 'expense'}"
                        class="px-4 py-2 text-white rounded-lg">
                        <span x-text="isEditing ? 'Update' : 'Save'"></span> <span x-text="formType === 'income' ? 'Income' : 'Expense'"></span>
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
                    <!-- Date Range Filters -->
                    <div>
                        <label for="filter-start-date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                        <input type="date" id="filter-start-date" x-model="filters.startDate" @change="fetchTransactions()" class="form-input w-full rounded p-2 border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label for="filter-end-date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                        <input type="date" id="filter-end-date" x-model="filters.endDate" @change="fetchTransactions()" class="form-input w-full rounded p-2 border-gray-300 shadow-sm">
                    </div>
                    
                    <!-- Category Filter -->
                    <div>
                        <label for="filter-category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <select id="filter-category" x-model="filters.category" @change="fetchTransactions()" class="form-select w-full rounded p-2 border-gray-300 shadow-sm">
                            <option value="">All Categories</option>
                            <template x-for="category in formType === 'income' ? categories.income : categories.expense">
                                <option x-text="category" :value="category"></option>
                            </template>
                        </select>
                    </div>
                    
                    <!-- Status Filter -->
                    <div>
                        <label for="filter-status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="filter-status" x-model="filters.status" @change="fetchTransactions()" class="form-select w-full rounded p-2 border-gray-300 shadow-sm">
                            <option value="">All Statuses</option>
                            <template x-for="status in statuses">
                                <option x-text="status.text" :value="status.id"></option>
                            </template>
                        </select>
                    </div>
                </div>
                
                <!-- Search and Reset -->
                <div class="mt-4 flex flex-col md:flex-row space-y-2 md:space-y-0 md:space-x-2">
                    <div class="flex-grow">
                        <input type="text" x-model="filters.search" placeholder="Search..." class="form-input w-full rounded p-2 border-gray-300 shadow-sm">
                    </div>
                    <div class="flex space-x-2">
                        <button @click="fetchTransactions()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                            Search
                        </button>
                        <button @click="filters = {
                            type: 'all',
                            startDate: '',
                            endDate: '',
                            category: '',
                            status: '',
                            search: ''
                        }; fetchTransactions()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
                            Reset
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Transactions Table -->
            <div class="overflow-x-auto">
                <div x-show="isLoading" class="text-center py-4">
                    <div class="inline-block animate-spin rounded-full h-6 w-6 border-t-2 border-b-2 border-blue-500"></div>
                    <span class="ml-2">Loading...</span>
                </div>
                
                <table x-show="!isLoading" class="min-w-full table-auto border-collapse">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" @click="sortField = 'id'; sortDirection = sortDirection === 'asc' ? 'desc' : 'asc'">
                                ID
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <span x-text="formType === 'income' ? 'Income From' : 'Expense By'"></span>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Category
                            </th>
                            <th x-show="formType === 'expense'" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Purpose
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Amount
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Method
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-if="transactions.length === 0">
                            <tr>
                                <td colspan="9" class="px-6 py-4 text-center text-gray-500">
                                    No transactions found.
                                </td>
                            </tr>
                        </template>
                        <template x-for="transaction in transactions" :key="transaction.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap" x-text="transaction.id"></td>
                                <td class="px-6 py-4 whitespace-nowrap" x-text="formType === 'income' ? transaction.income_from : transaction.expense_by"></td>
                                <td class="px-6 py-4 whitespace-nowrap" x-text="transaction.category"></td>
                                <td x-show="formType === 'expense'" class="px-6 py-4 whitespace-nowrap" x-text="transaction.purpose || '-'"></td>
                                <td class="px-6 py-4 whitespace-nowrap font-medium" x-text="formatCurrency(transaction.amount)"></td>
                                <td class="px-6 py-4 whitespace-nowrap" x-text="transaction.method || '-'"></td>
                                <td class="px-6 py-4 whitespace-nowrap" x-text="formatDate(transaction.date_created)"></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span 
                                        :class="{
                                            'bg-yellow-100 text-yellow-800': transaction.status === 'pending',
                                            'bg-green-100 text-green-800': transaction.status === 'approved',
                                            'bg-red-100 text-red-800': transaction.status === 'denied',
                                            'bg-gray-100 text-gray-800': transaction.status === 'deleted'
                                        }" 
                                        class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                                        x-text="transaction.status">
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-2">
                                        <?php if(Users::can('UPDATE')): ?>
                                        <button @click="editTransaction(transaction)" class="text-blue-600 hover:text-blue-900">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        <?php endif; ?>
                                        
                                        <?php if(Users::can('APPROVE')): ?>
                                        <button x-show="transaction.status === 'pending'" @click="approveTransaction(transaction.id)" class="text-green-600 hover:text-green-900">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </button>
                                        <?php endif; ?>
                                        
                                        <?php if(Users::can('DELETE')): ?>
                                        <button @click="deleteTransaction(transaction.id)" class="text-red-600 hover:text-red-900">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                        <?php endif; ?>
                                    </div>
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
