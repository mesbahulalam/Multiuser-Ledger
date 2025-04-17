<h1 class="text-3xl font-bold mb-8">Bandwidth Bill Payments</h1>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('billPaymentForm', () => ({
            formError: '',
            formSuccess: '',
            submitting: false,
            vendors: [],
            selectedVendor: null,
            selectedVendorDue: 0,
            selectedVendorPaid: 0,
            selectedVendorTotalBilled: 0,
            method: '',
            loading: false,
            
            async init() {
                await this.fetchVendors();
            },
            
            async fetchVendors() {
                try {
                    this.loading = true;
                    const response = await fetch('/api/bills/vendors-due');
                    if (!response.ok) {
                        throw new Error('Failed to fetch vendors');
                    }
                    this.vendors = await response.json();
                } catch (error) {
                    alert(error.message);
                } finally {
                    this.loading = false;
                }
            },
            
            updateSelectedVendor() {
                if (this.selectedVendor) {
                    const vendor = this.vendors.find(v => v.id == this.selectedVendor);
                    if (vendor) {
                        this.selectedVendorDue = parseFloat(vendor.total_due);
                        this.selectedVendorPaid = parseFloat(vendor.total_paid_amount);
                        this.selectedVendorTotalBilled = parseFloat(vendor.total_billed);
                    }
                } else {
                    this.selectedVendorDue = 0;
                    this.selectedVendorPaid = 0;
                    this.selectedVendorTotalBilled = 0;
                }
            },
            
            async submitPaymentForm() {
                this.submitting = true;
                this.formError = '';
                this.formSuccess = '';
                
                const formData = new FormData(this.$el);
                const data = Object.fromEntries(formData);
                
                if (!this.selectedVendor) {
                    this.formError = 'Please select a vendor';
                    this.submitting = false;
                    return;
                }
                
                try {
                    const response = await fetch('/api/finance/expense/add', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            entry_by: <?= $_SESSION['user_id'] ?>,
                            expense_by: data.expense_by,
                            category: 'Bandwidth Bill',
                            purpose: data.purpose,
                            amount: data.amount,
                            method: data.method,
                            bank: data.bank || null,
                            account_number: data.account_number || null,
                            transaction_number: data.transaction_number || null,
                            date_realized: data.date_realized || new Date().toISOString(),
                            notes: data.notes || ''
                        })
                    });
                    
                    const result = await response.json();
                    if (response.ok) {
                        this.formSuccess = result.message;
                        this.$el.reset();
                        this.selectedVendor = null;
                        this.selectedVendorDue = 0;
                        this.method = '';
                    } else {
                        this.formError = result.error;
                    }
                } catch (error) {
                    this.formError = 'An error occurred while submitting the form';
                } finally {
                    this.submitting = false;
                }
            }
        }));
    });
</script>

<!-- Payment Form -->
<div class="bg-white p-6 rounded-lg shadow">
    <div x-data="billPaymentForm()" x-init="init">
        <form @submit.prevent="submitPaymentForm">
            <!-- Success/Error Messages -->
            <div x-show="formSuccess" x-text="formSuccess" class="mb-4 p-2 bg-green-100 text-green-700 rounded"></div>
            <div x-show="formError" x-text="formError" class="mb-4 p-2 bg-red-100 text-red-700 rounded"></div>
            
            <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Vendor Selection with Due Amount -->
                    <div>
                        <label for="expense_by" class="block text-sm font-medium text-gray-700 mb-2">Vendor <span class="text-red-500">*</span></label>
                        <select 
                            name="expense_by" 
                            id="expense_by" 
                            x-model="selectedVendor" 
                            @change="updateSelectedVendor()"
                            class="w-full p-2 border rounded bg-white"
                            required
                        >
                            <option value="">Select Vendor</option>
                            <template x-for="vendor in vendors" :key="vendor.id">
                                <option 
                                    :value="vendor.vendor_name"
                                    x-text="vendor.vendor_name + ' - $' + parseFloat(vendor.total_due).toFixed(2)"
                                    :class="{'text-green-500': vendor.is_fully_paid}"
                                ></option>
                            </template>
                        </select>
                        <div class="mt-1 text-sm" x-show="selectedVendor">
                            <p class="text-gray-700" x-show="selectedVendorDue > 0">
                                Total due: <span class="font-semibold text-red-600">$<span x-text="selectedVendorDue.toFixed(2)"></span></span>
                            </p>
                            <p class="text-gray-700" x-show="selectedVendorDue <= 0 && selectedVendorTotalBilled > 0">
                                <span class="font-semibold text-green-600">Fully paid!</span> Total billed: $<span x-text="selectedVendorTotalBilled.toFixed(2)"></span>
                            </p>
                            <p class="text-gray-500" x-show="selectedVendorPaid > 0">
                                Already paid: $<span x-text="selectedVendorPaid.toFixed(2)"></span>
                            </p>
                            <p class="text-gray-500" x-show="selectedVendorTotalBilled > 0">
                                Current month billed: $<span x-text="vendors.find(v => v.id == selectedVendor)?.current_month_due.toFixed(2)"></span>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Amount -->
                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">Amount <span class="text-red-500">*</span></label>
                        <div class="flex items-center">
                            <!-- <span class="text-gray-500 mr-2">$</span> -->
                            <input 
                                type="number" 
                                step="0.01" 
                                name="amount" 
                                id="amount" 
                                placeholder="Amount" 
                                class="w-full p-2 border rounded" 
                                required
                                :value="selectedVendorDue > 0 ? selectedVendorDue.toFixed(2) : ''"
                            >
                        </div>
                        <button 
                            type="button"
                            class="mt-1 text-sm text-blue-600 hover:text-blue-800"
                            x-show="selectedVendorDue > 0"
                            @click="document.querySelector('#amount').value = selectedVendorDue.toFixed(2)"
                        >
                            Pay full amount
                        </button>
                    </div>
                </div>
                
                <!-- Purpose -->
                <div>
                    <label for="purpose" class="block text-sm font-medium text-gray-700 mb-2">Purpose <span class="text-red-500">*</span></label>
                    <input 
                        type="text" 
                        name="purpose" 
                        id="purpose" 
                        placeholder="e.g. May 2025 Bandwidth Payment" 
                        class="w-full p-2 border rounded"
                        required
                    >
                </div>
                
                <!-- Date Realized -->
                <div>
                    <label for="date_realized" class="block text-sm font-medium text-gray-700 mb-2">Payment Date</label>
                    <div class="flex items-center space-x-2">
                        <input 
                            type="datetime-local" 
                            name="date_realized" 
                            id="date_realized" 
                            class="w-full p-2 border rounded"
                        >
                        <button 
                            type="button" 
                            class="bg-blue-500 text-white p-2 rounded"
                            @click="document.querySelector('#date_realized').value = new Date().toISOString().slice(0, 16)"
                        >
                            Now
                        </button>
                    </div>
                </div>
                
                <!-- Payment Method -->
                <div>
                    <label for="method" class="block text-sm font-medium text-gray-700 mb-2">Payment Method <span class="text-red-500">*</span></label>
                    <select name="method" id="method" x-model="method" class="w-full p-2 border rounded bg-white" required>
                        <option value="">Select Payment Method</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Bkash">Bkash</option>
                        <option value="Nagad">Nagad</option>
                        <option value="Credit Card">Credit Card</option>
                        <option value="Cash">Cash</option>
                        <option value="Check">Check</option>
                    </select>
                </div>
                
                <!-- Bank Details (conditionally shown) -->
                <div x-show="method && method !== 'Cash'" x-transition class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="bank" class="block text-sm font-medium text-gray-700 mb-2">Bank</label>
                            <input type="text" name="bank" id="bank" placeholder="Bank Name" class="w-full p-2 border rounded">
                        </div>
                        <div>
                            <label for="account_number" class="block text-sm font-medium text-gray-700 mb-2">Account Number</label>
                            <input type="text" name="account_number" id="account_number" placeholder="Account Number" class="w-full p-2 border rounded">
                        </div>
                    </div>
                    <div>
                        <label for="transaction_number" class="block text-sm font-medium text-gray-700 mb-2">Transaction Number</label>
                        <input type="text" name="transaction_number" id="transaction_number" placeholder="Transaction Number" class="w-full p-2 border rounded">
                    </div>
                </div>
                
                <!-- Notes -->
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                    <textarea name="notes" id="notes" rows="3" placeholder="Additional notes" class="w-full p-2 border rounded"></textarea>
                </div>
                
                <!-- Submit Button -->
                <div>
                    <button 
                        type="submit" 
                        class="w-full bg-red-500 text-white p-3 rounded-md font-medium hover:bg-red-600 disabled:opacity-50"
                        :disabled="submitting"
                    >
                        <span x-show="!submitting">Record Payment</span>
                        <span x-show="submitting">Processing...</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Recent Payments Table -->
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
        category: 'Bandwidth Bill'
    },
    showModal: false,
    modalData: {},
    async loadData() {
        this.loading = true;
        try {
            const params = new URLSearchParams({
                page: this.currentPage,
                itemsPerPage: this.itemsPerPage,
                search: this.search,
                ...this.filters
            });
            const response = await fetch(`/api/finance/expenses?${params}`);
            const data = await response.json();
            this.items = data.items;
            this.totalPages = data.totalPages;
        } catch (error) {
            console.error('Error loading data:', error);
        } finally {
            this.loading = false;
        }
    }
}" x-init="loadData()" @refresh-data.window="loadData()">
    <h2 class="text-xl font-semibold mb-4">Recent Bandwidth Payments</h2>
    
    <!-- Search and Filters -->
    <div class="mb-4 flex flex-wrap gap-2">
        <input type="text" x-model="search" @input.debounce.300ms="currentPage = 1; loadData()" 
            placeholder="Search..." class="border p-2 rounded">
        <input type="date" x-model="filters.startDate" @change="currentPage = 1; loadData()" 
            class="border p-2 rounded">
        <input type="date" x-model="filters.endDate" @change="currentPage = 1; loadData()" 
            class="border p-2 rounded">
    </div>
    
    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <template x-if="loading">
                    <tr>
                        <td colspan="7" class="text-center py-4">Loading...</td>
                    </tr>
                </template>
                <template x-if="!loading && items.length === 0">
                    <tr>
                        <td colspan="7" class="text-center py-4">No payment records found</td>
                    </tr>
                </template>
                <template x-for="item in items" :key="item.id">
                    <tr class="hover:bg-gray-100">
                        <td class="px-4 py-2" x-text="new Date(item.date_created).toLocaleString()"></td>
                        <td class="px-4 py-2" x-text="item.expense_by"></td>
                        <td class="px-4 py-2" x-text="item.purpose"></td>
                        <td class="px-4 py-2">$<span x-text="parseFloat(item.amount).toFixed(2)"></span></td>
                        <td class="px-4 py-2" x-text="item.method"></td>
                        <td class="px-4 py-2">
                            <span x-text="item.status" 
                                  :class="{
                                      'px-2 py-1 rounded text-xs font-medium': true,
                                      'bg-yellow-100 text-yellow-800': item.status === 'pending',
                                      'bg-green-100 text-green-800': item.status === 'approved',
                                      'bg-red-100 text-red-800': item.status === 'denied'
                                  }">
                            </span>
                        </td>
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
        <div class="text-sm text-gray-700">
            Showing <span x-text="items.length ? (currentPage - 1) * itemsPerPage + 1 : 0"></span> to 
            <span x-text="Math.min(currentPage * itemsPerPage, items.length)"></span> of 
            <span x-text="items.length"></span> entries
        </div>
        <div class="flex space-x-2">
            <button 
                @click="if(currentPage > 1) { currentPage--; loadData(); }"
                :disabled="currentPage === 1"
                class="px-3 py-1 border rounded" 
                :class="{'opacity-50 cursor-not-allowed': currentPage === 1}">
                Previous
            </button>
            <template x-for="page in Math.min(5, totalPages)" :key="page">
                <button 
                    @click="currentPage = page; loadData();" 
                    class="px-3 py-1 border rounded"
                    :class="{'bg-blue-500 text-white': currentPage === page}">
                    <span x-text="page"></span>
                </button>
            </template>
            <button 
                @click="if(currentPage < totalPages) { currentPage++; loadData(); }"
                :disabled="currentPage >= totalPages"
                class="px-3 py-1 border rounded"
                :class="{'opacity-50 cursor-not-allowed': currentPage >= totalPages}">
                Next
            </button>
        </div>
    </div>
    
    <!-- Payment Detail Modal -->
    <div x-show="showModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50" @click.self="showModal = false">
        <div class="bg-white p-6 rounded-lg shadow-lg w-11/12 max-w-3xl max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Payment Details</h2>
                <button @click="showModal = false" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div class="bg-gray-50 p-4 rounded">
                        <h3 class="font-semibold mb-2">Payment Information</h3>
                        <div class="grid grid-cols-2 gap-2">
                            <div class="font-medium">Vendor:</div>
                            <div x-text="modalData.expense_by"></div>
                            
                            <div class="font-medium">Purpose:</div>
                            <div x-text="modalData.purpose"></div>
                            
                            <div class="font-medium">Amount:</div>
                            <div>$<span x-text="modalData.amount"></span></div>
                            
                            <div class="font-medium">Status:</div>
                            <div>
                                <span x-text="modalData.status" 
                                      :class="{
                                          'px-2 py-1 rounded text-xs font-medium': true,
                                          'bg-yellow-100 text-yellow-800': modalData.status === 'pending',
                                          'bg-green-100 text-green-800': modalData.status === 'approved',
                                          'bg-red-100 text-red-800': modalData.status === 'denied'
                                      }">
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded">
                        <h3 class="font-semibold mb-2">Payment Method</h3>
                        <div class="grid grid-cols-2 gap-2">
                            <div class="font-medium">Method:</div>
                            <div x-text="modalData.method"></div>
                            
                            <div class="font-medium" x-show="modalData.bank">Bank:</div>
                            <div x-show="modalData.bank" x-text="modalData.bank"></div>
                            
                            <div class="font-medium" x-show="modalData.account_number">Account Number:</div>
                            <div x-show="modalData.account_number" x-text="modalData.account_number"></div>
                            
                            <div class="font-medium" x-show="modalData.transaction_number">Transaction Number:</div>
                            <div x-show="modalData.transaction_number" x-text="modalData.transaction_number"></div>
                        </div>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <div class="bg-gray-50 p-4 rounded">
                        <h3 class="font-semibold mb-2">Additional Information</h3>
                        <div class="grid grid-cols-2 gap-2">
                            <div class="font-medium">Notes:</div>
                            <div x-text="modalData.notes || '-'"></div>
                            
                            <div class="font-medium">Entry By:</div>
                            <div x-text="modalData.entry_by_name"></div>
                            
                            <div class="font-medium">Approved By:</div>
                            <div x-text="modalData.approved_by_name || '-'"></div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded">
                        <h3 class="font-semibold mb-2">Timestamps</h3>
                        <div class="grid grid-cols-2 gap-2">
                            <div class="font-medium">Created:</div>
                            <div x-text="new Date(modalData.date_created).toLocaleString()"></div>
                            
                            <div class="font-medium">Last Modified:</div>
                            <div x-text="new Date(modalData.date_modified).toLocaleString()"></div>
                            
                            <div class="font-medium" x-show="modalData.date_approved">Approved:</div>
                            <div x-show="modalData.date_approved" x-text="modalData.date_approved ? new Date(modalData.date_approved).toLocaleString() : '-'"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 text-right">
                <button @click="showModal = false" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>