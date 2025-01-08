                <h1 class="text-3xl font-bold mb-8">Add Transaction</h1>

                <div class="bg-white p-6 rounded-lg shadow">
                    <form id="transactionForm" method="POST" @submit.prevent="submitTransactionForm" x-data="{
                        formError: '',
                        formSuccess: '',
                        submitting: false,
                        transactionType: '',
                        async submitTransactionForm() {
                            this.submitting = true;
                            this.formError = '';
                            this.formSuccess = '';

                            const formData = new FormData(this.$el);
                            const data = Object.fromEntries(formData);

                            try {
                                const response = await fetch('/api/transactions/add', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                    },
                                    body: JSON.stringify(data)
                                });

                                const result = await response.json();
                                if (response.ok) {
                                    this.formSuccess = result.message;
                                    this.$el.reset();
                                } else {
                                    this.formError = result.error;
                                }
                            } catch (error) {
                                this.formError = 'An error occurred while submitting the form';
                            } finally {
                                this.submitting = false;
                            }
                        }
                    }">
                        <!-- Success/Error Messages -->
                        <div x-show="formSuccess" x-text="formSuccess" class="mb-4 p-2 bg-green-100 text-green-700 rounded"></div>
                        <div x-show="formError" x-text="formError" class="mb-4 p-2 bg-red-100 text-red-700 rounded"></div>

                        <div class="space-y-4">
                            <select name="type" x-model="transactionType" class="w-full p-2 border rounded bg-white">
                                <option value="">Select Transaction Type</option>
                                <option value="income">Income</option>
                                <option value="expense">Expense</option>
                            </select>

                            <template x-if="transactionType === 'income'">
                                <input type="text" name="income_from" placeholder="Income From" class="w-full p-2 border rounded">
                            </template>

                            <template x-if="transactionType === 'expense'">
                                <input type="text" name="expense_by" placeholder="Expense By" class="w-full p-2 border rounded">
                            </template>

                            <input type="text" name="category" placeholder="Category" class="w-full p-2 border rounded">
                            <input type="text" name="purpose" placeholder="Purpose" class="w-full p-2 border rounded">
                            <select name="method" class="w-full p-2 border rounded bg-white">
                                <option value="">Payment Method</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Credit Card">Credit Card</option>
                                <option value="Cash">Cash</option>
                                <option value="Check">Check</option>
                            </select>
                            
                            <div x-data="{
                                makeAutocomplete(items) {
                                    return {
                                        search: '',
                                        items: items,
                                        showOptions: false,
                                        selectedIndex: -1,
                                        get filteredOptions() {
                                            return this.items.filter(item => 
                                                item.toLowerCase().includes(this.search.toLowerCase())
                                            );
                                        },
                                    }
                                }
                            }">
                                <div x-data="makeAutocomplete(<?=str_replace('"', "'", json_encode(array_column(DB::fetchAll('SELECT DISTINCT bank FROM transactions'), 'bank')))?>)" 
                                    class="relative">
                                    <input 
                                        type="text" 
                                        name="bank"
                                        autocomplete="off"
                                        x-model="search"
                                        @focus="showOptions = true"
                                        @blur="setTimeout(() => showOptions = false, 200)"
                                        @keydown.arrow-down.prevent="selectedIndex = Math.min(selectedIndex + 1, filteredOptions.length - 1)"
                                        @keydown.arrow-up.prevent="selectedIndex = Math.max(selectedIndex - 1, 0)"
                                        @keydown.enter.prevent="if(selectedIndex >= 0) { search = filteredOptions[selectedIndex]; showOptions = false }"
                                        placeholder="Bank"
                                        class="w-full p-2 border rounded"
                                    >
                                    <div x-show="showOptions && filteredOptions.length > 0" 
                                        class="absolute mt-1 w-full bg-white border rounded shadow-lg z-10">
                                        <ul class="max-h-60 overflow-auto">
                                            <template x-for="(option, index) in filteredOptions" :key="option">
                                                <li 
                                                    x-text="option"
                                                    @click="search = option; showOptions = false"
                                                    :class="{'bg-blue-100': selectedIndex === index}"
                                                    class="p-2 cursor-pointer hover:bg-gray-100">
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <input type="number" step="0.01" name="amount" placeholder="Amount" class="w-full p-2 border rounded">
                            <textarea name="notes" placeholder="Notes" class="w-full p-2 border rounded"></textarea>

                            <button 
                                type="submit" 
                                class="w-full bg-green-500 text-white p-2 rounded disabled:opacity-50"
                                :disabled="submitting"
                            >
                                <span x-show="!submitting">Add Transaction</span>
                                <span x-show="submitting">Saving...</span>
                            </button>
                        </div>
                    </form>
                </div>




                <!-- Transactions Table -->
                <div class="bg-white p-6 rounded-lg shadow mt-8" x-data="{
                    items: [],
                    currentPage: 1,
                    totalPages: 1,
                    itemsPerPage: 10,
                    loading: false,
                    search: '',
                    filters: {
                        startDate: '',
                        endDate: '',
                        category: '',
                        status: ''
                    },
                    showModal: false,
                    modalData: {},
                    async loadData() {
                        this.loading = true;
                        if (!this.filters.startDate || !this.filters.endDate) {
                            const currentDate = new Date(); // Current date
                            const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1).toISOString().split('T')[0]; // First day of the current month
                            const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0).toISOString().split('T')[0]; // Last day of the current month
                            this.filters.startDate = firstDay; // Default to first day of the current month
                            this.filters.endDate = lastDay; // Default to last day of the current month
                        }
                        try {
                            const params = new URLSearchParams({
                                page: this.currentPage,
                                itemsPerPage: this.itemsPerPage,
                                search: this.search,
                                ...this.filters
                            });
                            const response = await fetch(`/api/transactions?${params}`);
                            const data = await response.json();
                            this.items = data.items;
                            this.totalPages = data.totalPages;
                        } catch (error) {
                            console.error('Error loading data:', error);
                        } finally {
                            this.loading = false;
                        }
                    },
                    getPaginationArray() {
                        let pages = [];
                        if (this.totalPages <= 5) {
                            for (let i = 1; i <= this.totalPages; i++) {
                                pages.push(this.createPageButton(i, i === this.currentPage));
                            }
                        } else {
                            pages.push(this.createPageButton(1, this.currentPage === 1));
                            
                            if (this.currentPage > 3) {
                                pages.push(this.createEllipsis());
                            }
                            
                            let start = Math.max(2, this.currentPage - 1);
                            let end = Math.min(this.totalPages - 1, this.currentPage + 1);
                            
                            if (this.currentPage <= 3) {
                                end = 4;
                            }
                            if (this.currentPage >= this.totalPages - 2) {
                                start = this.totalPages - 3;
                            }
                            
                            for (let i = start; i <= end; i++) {
                                pages.push(this.createPageButton(i, i === this.currentPage));
                            }
                            
                            if (this.currentPage < this.totalPages - 2) {
                                pages.push(this.createEllipsis());
                            }
                            
                            pages.push(this.createPageButton(this.totalPages, this.currentPage === this.totalPages));
                        }
                        return pages.join('');
                    },
                    createPageButton(page, isActive = false) {
                        return `<button 
                            @click='currentPage = ${page}; loadData();'
                            class='px-3 py-1 border rounded-md hover:bg-gray-100 ${isActive ? 'bg-blue-500 text-white' : ''}'
                            >${page}</button>`;
                    },
                    createEllipsis() {
                        return '<span class=\'px-3 py-1\'>...</span>';
                    }
                }" x-init="loadData()" @refresh-data.window="loadData()">
                    <h3 class="text-lg font-semibold mb-4">Transaction Records</h3>
                    
                    <!-- Search and Filters -->
                    <div class="mb-4 flex gap-2">
                        <input type="text" x-model="search" @input.debounce.300ms="currentPage = 1; loadData()" 
                            placeholder="Search..." class="border p-2 rounded">
                        <input type="date" x-model="filters.startDate" @change="currentPage = 1; loadData()" 
                            class="border p-2 rounded">
                        <input type="date" x-model="filters.endDate" @change="currentPage = 1; loadData()" 
                            class="border p-2 rounded">
                        <select x-model="filters.category" @change="currentPage = 1; loadData()" 
                            class="border p-2 rounded bg-white">
                            <option value="">All Categories</option>
                            <?php foreach(array_column(DB::fetchAll('SELECT DISTINCT category FROM transactions'), 'category') as $category): ?>
                                <option value="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select x-model="itemsPerPage" @change="currentPage = 1; loadData()" 
                            class="border p-2 rounded bg-white">
                            <option value="10">10 per page</option>
                            <option value="20">20 per page</option>
                            <option value="50">50 per page</option>
                            <option value="100">100 per page</option>
                        </select>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-if="loading">
                                    <tr>
                                        <td colspan="5" class="text-center py-4">Loading...</td>
                                    </tr>
                                </template>
                                <template x-for="item in items" :key="item.id">
                                    <tr class="hover:bg-gray-100">
                                        <td class="px-4 py-2">
                                            <span x-text="new Date(item.date_created).toLocaleDateString()"></span>
                                            <br>
                                            <span x-text="new Date(item.date_created).toLocaleTimeString()"></span>
                                        </td>
                                        <td class="px-4 py-2" 
                                            :class="{'text-green-500': item.type === 'income', 'text-red-500': item.type === 'expense'}" 
                                            x-text="item.type"></td>
                                        <td class="px-4 py-2" x-text="item.category"></td>
                                        <td class="px-4 py-2" x-text="item.amount"></td>
                                        <td class="px-4 py-2">
                                            <button @click="modalData = item; showModal = true" class="text-blue-500 hover:text-blue-700">View</button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4 flex justify-between items-center">
                        <button 
                            @click="if(currentPage > 1) { currentPage--; loadData(); }"
                            :disabled="currentPage === 1"
                            class="px-4 py-2 bg-gray-200 rounded disabled:opacity-50">Previous</button>
                        <div class="flex space-x-2" x-html="getPaginationArray()"></div>
                        <button 
                            @click="if(currentPage < totalPages) { currentPage++; loadData(); }"
                            :disabled="currentPage === totalPages"
                            class="px-4 py-2 bg-gray-200 rounded disabled:opacity-50">Next</button>
                    </div>
                    
                    <div x-show="showModal" x-cloak class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
                        <div class="bg-white p-6 rounded-lg shadow-lg w-1/2">
                            <h2 class="text-xl font-bold mb-4">Transaction Details</h2>
                            <div class="mb-4">
                                <strong>Date:</strong> <span x-text="modalData.date_created"></span>
                            </div>
                            <div class="mb-4">
                                <strong>Type:</strong> <span x-text="modalData.type"></span>
                            </div>
                            <div class="mb-4">
                                <strong>Category:</strong> <span x-text="modalData.category"></span>
                            </div>
                            <div class="mb-4">
                                <strong>Purpose:</strong> <span x-text="modalData.purpose || 'N/A'"></span>
                            </div>
                            <div class="mb-4">
                                <strong>Amount:</strong> <span x-text="modalData.amount"></span>
                            </div>
                            <div class="mb-4">
                                <strong>Status:</strong> <span x-text="modalData.status"></span>
                            </div>
                            <div class="mb-4">
                                <strong>Notes:</strong> <span x-text="modalData.notes"></span>
                            </div>
                            <button @click="showModal = false" class="px-4 py-2 bg-blue-500 text-white rounded">Close</button>
                        </div>
                    </div>

                </div>