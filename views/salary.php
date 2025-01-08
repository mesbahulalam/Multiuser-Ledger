<div class="max-w-7xl mx-auto">
    <h1 class="text-3xl font-bold mb-8">Salary Management</h1>

    <!-- Shared autocomplete component -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('autocomplete', (config) => ({
                open: false,
                search: '',
                selected: null,
                loading: false,
                items: [],
                selectedIndex: -1,
                label: config.label || 'Select an option',
                placeholder: config.placeholder || 'Search...',
                async loadItems() {
                    this.loading = true;
                    if (config.apiUrl) {
                        try {
                            const response = await fetch(config.apiUrl);
                            if (!response.ok) throw new Error('Network response was not ok');
                            this.items = await response.json();
                        } catch (error) {
                            console.error('Error fetching data:', error);
                            this.items = [];
                        }
                    } else {
                        // Use static data if no API URL provided
                        this.items = config.staticData || [];
                    }
                    this.loading = false;
                },
                addNewItem() {
                    if (!this.search.trim()) return;
                    const newItem = {
                        id: this.items.length + 1,
                        text: this.search.trim(),
                        balance: '$0'
                    };
                    this.items.push(newItem);
                    this.selected = newItem;
                    this.search = '';
                    this.open = false;
                },
                get filteredItems() {
                    return this.items.filter(i => i.text.toLowerCase().includes(this.search.toLowerCase()))
                },
                selectItem(index) {
                    if (this.filteredItems.length > 0) {
                        this.selected = this.filteredItems[index];
                        this.open = false;
                        this.search = '';
                        this.selectedIndex = -1;
                    }
                },
                navigateList(direction) {
                    if (this.filteredItems.length > 0) {
                        this.selectedIndex = direction === 'up' 
                            ? (this.selectedIndex - 1 + this.filteredItems.length) % this.filteredItems.length
                            : (this.selectedIndex + 1) % this.filteredItems.length;
                    }
                }
            }))
        })
    </script>

    <!-- Salary Form -->
    <div class="bg-white p-6 rounded-lg shadow mb-8">
        <h2 class="text-xl font-bold mb-4">Add Salary Entry</h2>
        <form id="salaryForm" method="POST" @submit.prevent="submitSalaryForm" x-data="{
            formError: '',
            formSuccess: '',
            submitting: false,
            basicSalary: 0,
            allowances: 0,
            deductions: 0,
            getNetSalary() {
                return (parseFloat(this.basicSalary) + parseFloat(this.allowances) - parseFloat(this.deductions)).toFixed(2);
            },
            async submitSalaryForm() {
                this.submitting = true;
                this.formError = '';
                this.formSuccess = '';
                
                const formData = new FormData(this.$el);
                const data = Object.fromEntries(formData);
                
                // Add the selected user's id
                if (this.$refs.userAutocomplete && this.$refs.userAutocomplete.__x.$data.selected) {
                    data.user_id = this.$refs.userAutocomplete.__x.$data.selected.id;
                }
                
                // Add current user as approver
                data.approved_by = <?= $_SESSION['user_id'] ?>;
                
                try {
                    const response = await fetch('/api/salary/add', {
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
                        // Reset user selection
                        if (this.$refs.userAutocomplete) {
                            this.$refs.userAutocomplete.__x.$data.selected = null;
                            this.$refs.userAutocomplete.__x.$data.search = '';
                        }
                        // Dispatch event to refresh table data
                        window.dispatchEvent(new CustomEvent('refresh-salary-data'));
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

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- User Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Employee</label>
                    <div x-data="autocomplete({
                        apiUrl: '/api/users/namelist',
                        placeholder: 'Search users...',
                        label: 'Select Employee'
                    })" 
                    x-init="loadItems()"
                    class="relative" x-ref="userAutocomplete">
                        <!-- Hidden input to store the selected user ID -->
                        <input type="hidden" name="user_id" :value="selected ? selected.id : ''">
                        
                        <button 
                            @click.prevent="open = !open" 
                            type="button"
                            class="w-full flex items-center justify-between p-2 border rounded-md">
                            <span x-text="selected ? selected.text : label"></span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <!-- Dropdown -->
                        <div 
                            x-show="open"
                            @click.away="open = false"
                            class="absolute w-full mt-1 bg-white border rounded-md shadow-lg z-50">
                            <input
                                x-model="search"
                                @keydown.escape.prevent="open = false"
                                @keydown.enter.prevent="selectedIndex >= 0 ? selectItem(selectedIndex) : null"
                                @keydown.arrow-down.prevent="navigateList('down')"
                                @keydown.arrow-up.prevent="navigateList('up')"
                                class="w-full p-2 border-b"
                                :placeholder="placeholder">

                            <ul class="max-h-60 overflow-y-auto">
                                <template x-for="(item, index) in filteredItems" :key="item.id">
                                    <li
                                        @click="selected = item; open = false"
                                        :class="{'bg-blue-50': selectedIndex === index}"
                                        class="p-2 cursor-pointer hover:bg-gray-100">
                                        <span x-text="item.text"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Month -->
                <div>
                    <label for="salary-month" class="block text-sm font-medium text-gray-700 mb-2">Salary Month</label>
                    <input type="text" 
                        id="salary-month"
                        name="month" 
                        value="<?= date('F, Y', strtotime('last month')) ?>"
                        readonly
                        class="w-full p-2 border rounded bg-gray-50 focus:outline-none">
                </div>


                <!-- Basic Salary -->
                <div>
                    <label for="basic-salary" class="block text-sm font-medium text-gray-700 mb-2">Basic Salary</label>
                    <input type="number" 
                        id="basic-salary"
                        step="0.01" 
                        name="basic_salary" 
                        x-model="basicSalary"
                        placeholder="Enter basic salary" 
                        required
                        class="w-full p-2 border rounded">
                </div>

                <!-- Allowances -->
                <div>
                    <label for="allowances" class="block text-sm font-medium text-gray-700 mb-2">Allowances</label>
                    <input type="number" 
                        id="allowances"
                        step="0.01" 
                        name="allowances" 
                        x-model="allowances"
                        placeholder="Enter allowances" 
                        class="w-full p-2 border rounded">
                </div>

                <!-- Deductions -->
                <div>
                    <label for="deductions" class="block text-sm font-medium text-gray-700 mb-2">Deductions</label>
                    <input type="number" 
                        id="deductions"
                        step="0.01" 
                        name="deductions" 
                        x-model="deductions"
                        placeholder="Enter deductions" 
                        class="w-full p-2 border rounded">
                </div>

                <!-- Net Salary (Calculated) -->
                <div>
                    <label for="net-salary" class="block text-sm font-medium text-gray-700 mb-2">Net Salary</label>
                    <input type="number" 
                        id="net-salary"
                        step="0.01" 
                        name="net_salary" 
                        :value="getNetSalary()"
                        placeholder="Calculated net salary" 
                        readonly
                        class="w-full p-2 border rounded bg-gray-50">
                </div>

                <!-- Payment Details -->
                <div class="md:col-span-2">
                    <label for="payment-details" class="block text-sm font-medium text-gray-700 mb-2">Payment Details</label>
                    <textarea 
                        id="payment-details"
                        name="payment_details" 
                        placeholder="Enter payment details" 
                        rows="3"
                        class="w-full p-2 border rounded"></textarea>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="mt-6">
                <button 
                    type="submit" 
                    class="w-full bg-blue-500 text-white p-2 rounded hover:bg-blue-600 disabled:opacity-50"
                    :disabled="submitting">
                    <span x-show="!submitting">Save Salary Entry</span>
                    <span x-show="submitting">Saving...</span>
                </button>
            </div>
        </form>
    </div>
    <!-- Salary Records Table -->
    <div class="bg-white p-6 rounded-lg shadow" x-data="{
        records: [],
        currentPage: 1,
        itemsPerPage: 10,
        totalPages: 1,
        loading: true,
        error: null,
        searchQuery: '',

        async loadRecords() {
            this.loading = true;
            this.error = null;
            try {
                const response = await fetch('/api/salary/summary');
                if (!response.ok) throw new Error('Failed to load data');
                const data = await response.json();
                this.records = Array.isArray(data) ? data : [];
                this.totalPages = Math.ceil(this.filteredRecords.length / this.itemsPerPage);
            } catch (error) {
                console.error('Error loading records:', error);
                this.error = error.message;
                this.records = [];
            } finally {
                this.loading = false;
            }
        },

        get filteredRecords() {
            if (!Array.isArray(this.records)) return [];
            return this.records.filter(record => 
                record.username.toLowerCase().includes(this.searchQuery.toLowerCase())
            );
        },

        get paginatedRecords() {
            const start = (this.currentPage - 1) * this.itemsPerPage;
            const end = start + this.itemsPerPage;
            return this.filteredRecords.slice(start, end);
        },

        nextPage() {
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
            }
        },

        prevPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
            }
        }
    }" x-init="loadRecords()">
        <h2 class="text-xl font-bold mb-4">Salary Summary</h2>
        
        <!-- Error Message -->
        <div x-show="error" class="text-red-600 mb-4 p-2 bg-red-50 rounded" x-text="error"></div>

        <!-- Loading spinner -->
        <div x-show="loading" class="text-center py-4">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
        </div>

        <div x-show="!loading && !error" class="overflow-x-auto">
            <!-- Search box -->
            <div class="mb-4">
                <input 
                    type="text" 
                    placeholder="Search by employee name..." 
                    x-model="searchQuery"
                    class="w-full p-2 border rounded"
                >
            </div>

            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Net Salary</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Accumulated Salary</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paid Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payable Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-if="filteredRecords.length === 0">
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">No salary records found</td>
                        </tr>
                    </template>
                    <template x-for="record in paginatedRecords" :key="record.user_id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap" x-text="record.user_id"></td>
                            <td class="px-6 py-4 whitespace-nowrap" x-text="record.username"></td>
                            <td class="px-6 py-4 whitespace-nowrap" x-text="'$' + Number(record.latest_salary).toFixed(2)"></td>
                            <td class="px-6 py-4 whitespace-nowrap" x-text="'$' + Number(record.accumulated_salary).toFixed(2)"></td>
                            <td class="px-6 py-4 whitespace-nowrap" x-text="'$' + Number(record.paid_amount).toFixed(2)"></td>
                            <td class="px-6 py-4 whitespace-nowrap" 
                                :class="record.payable_amount > 0 ? 'text-red-600 font-medium' : ''"
                                x-text="'$' + Number(record.payable_amount).toFixed(2)"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button 
                                    class="text-blue-600 hover:text-blue-900"
                                    @click="$dispatch('open-salary-details', { 
                                        user_id: record.user_id,
                                        username: record.username
                                    })">
                                    View History
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>

            <!-- Pagination Controls -->
            <div class="mt-4 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <button 
                        class="px-3 py-1 border rounded hover:bg-gray-100"
                        @click="prevPage"
                        :disabled="currentPage === 1">
                        Previous
                    </button>
                    <span class="text-sm text-gray-700">
                        Page <span x-text="currentPage"></span> of <span x-text="totalPages"></span>
                    </span>
                    <button 
                        class="px-3 py-1 border rounded hover:bg-gray-100"
                        @click="nextPage"
                        :disabled="currentPage === totalPages">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Salary Details Modal -->
    <div x-data="{ 
        showModal: false,
        userId: null,
        username: '',
        payments: [],
        page: 1,
        loading: false,
        hasMore: true,
        async loadPayments() {
            if (this.loading || !this.hasMore) return;
            
            this.loading = true;
            try {
                const response = await fetch(`/api/salary/history/${this.userId}?page=${this.page}`);
                const data = await response.json();
                
                if (data.payments.length < 10) {
                    this.hasMore = false;
                }
                
                this.payments = [...this.payments, ...data.payments];
                this.page++;
            } catch (error) {
                console.error('Error loading payments:', error);
            } finally {
                this.loading = false;
            }
        }
    }" 
    @open-salary-details.window="
        showModal = true;
        userId = $event.detail.user_id;
        username = $event.detail.username;
        payments = [];
        page = 1;
        hasMore = true;
        await loadPayments();
    "
    x-init="
        $watch('showModal', value => {
            if (!value) {
                payments = [];
                page = 1;
                hasMore = true;
            }
        })
    ">
        <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showModal" 
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 transition-opacity" 
                    @click="showModal = false">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>

                <div x-show="showModal" 
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    Salary History - <span x-text="username"></span>
                                </h3>
                                
                                <div class="max-h-96 overflow-y-auto" 
                                    @scroll.debounce.250ms="
                                        const el = $event.target;
                                        if (el.scrollHeight - el.scrollTop <= el.clientHeight + 100) {
                                            loadPayments();
                                        }
                                    ">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50 sticky top-0">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <template x-if="payments.length === 0">
                                                <tr>
                                                    <td colspan="3" class="px-6 py-4 text-center text-gray-500">
                                                        No salary payments found
                                                    </td>
                                                </tr>
                                            </template>
                                            <template x-for="payment in payments" :key="payment.id">
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap" x-text="new Date(payment.date).toLocaleDateString()"></td>
                                                    <td class="px-6 py-4 whitespace-nowrap" x-text="'$' + parseFloat(payment.amount).toFixed(2)"></td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span x-text="payment.status"
                                                            :class="{
                                                                'px-2 py-1 text-xs font-medium rounded-full': true,
                                                                'bg-green-100 text-green-800': payment.status === 'approved',
                                                                'bg-yellow-100 text-yellow-800': payment.status === 'pending',
                                                                'bg-red-100 text-red-800': payment.status === 'rejected'
                                                            }">
                                                        </span>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                    <!-- Loading indicator -->
                                    <div x-show="loading" class="text-center py-4">
                                        <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-gray-900"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" 
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-gray-600 text-base font-medium text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:ml-3 sm:w-auto sm:text-sm"
                            @click="showModal = false">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
