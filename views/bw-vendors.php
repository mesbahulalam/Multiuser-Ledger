<h1 class="text-3xl font-bold mb-8 pt-6">Bandwidth Vendors</h1>

<script>
    // Shared data table component for bandwidth vendors
    document.addEventListener('alpine:init', () => {
        // Add store for bills management
        Alpine.store('bills', {
            shouldRefresh: false,
            triggerRefresh() {
                this.shouldRefresh = !this.shouldRefresh;
            }
        });

        // Add store for vendors management
        Alpine.store('vendors', {
            shouldRefresh: false,
            triggerRefresh() {
                this.shouldRefresh = !this.shouldRefresh;
            }
        });

        // Vendors table component
        Alpine.data('vendorTable', () => ({
            search: '',
            currentPage: 1,
            itemsPerPage: 10,
            sortField: 'vendor_name',
            sortDirection: 'asc',
            selectedRows: [],
            items: [],
            showEditModal: false,
            editingVendor: {
                id: '',
                vendor_name: '',
                contact_person: '',
                phone_number: '',
                address: ''
            },
            showCreateModal: false,
            newVendor: {
                vendor_name: '',
                contact_person: '',
                phone_number: '',
                address: ''
            },
            loading: false,

            async init() {
                await this.fetchVendors();
            },

            async fetchVendors() {
                try {
                    this.showLoading();
                    const response = await fetch('/api/vendors');
                    if (!response.ok) {
                        const error = await response.json();
                        throw new Error(error.error || 'Failed to fetch vendors');
                    }
                    const data = await response.json();
                    this.items = data.map(vendor => ({
                        id: vendor.id,
                        vendor_name: vendor.vendor_name,
                        contact_person: vendor.contact_person,
                        phone_number: vendor.phone_number,
                        address: vendor.address,
                        created_at: vendor.created_at
                    }));
                } catch (error) {
                    alert(error.message);
                } finally {
                    this.hideLoading();
                }
            },

            async createVendor() {
                <?php if(!Users::can('CREATE')): ?>
                    alert('Permission denied');
                    return;
                <?php else: ?>
                try {
                    this.showLoading();
                    const response = await fetch('/api/vendors/new', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(this.newVendor)
                    });
                    
                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(data.error || 'Failed to create vendor');
                    }
                    
                    // Refresh the vendors list
                    await this.fetchVendors();
                    this.showCreateModal = false;
                    this.newVendor = { 
                        vendor_name: '',
                        contact_person: '',
                        phone_number: '',
                        address: ''
                    };
                    alert(data.message);
                    // Trigger vendors refresh in other components
                    Alpine.store('vendors').triggerRefresh();
                } catch (error) {
                    alert(error.message);
                } finally {
                    this.hideLoading();
                }
                <?php endif; ?>
            },

            editItem(item) {
                this.editingVendor = { ...item };
                this.showEditModal = true;
            },

            async updateVendor() {
                <?php if(!Users::can('UPDATE')): ?>
                    alert('Permission denied');
                    return;
                <?php else: ?>
                try {
                    this.showLoading();
                    const response = await fetch('/api/vendors/update', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(this.editingVendor)
                    });
                    
                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(data.error || 'Update failed');
                    }
                    
                    // Update local data on success
                    const index = this.items.findIndex(v => v.id === this.editingVendor.id);
                    if (index !== -1) {
                        this.items[index] = { ...this.editingVendor };
                    }
                    
                    this.showEditModal = false;
                    this.editingVendor = null;
                    alert(data.message);
                } catch (error) {
                    alert(error.message);
                } finally {
                    this.hideLoading();
                }
                <?php endif; ?>
            },

            async deleteItem(id) {
                <?php if(!Users::can('DELETE')): ?>
                    alert('Permission denied');
                    return;
                <?php else: ?>
                if (confirm('Are you sure you want to delete this vendor?')) {
                    try {
                        this.showLoading();
                        const response = await fetch('/api/vendors/delete', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ id: id })
                        });
                        
                        const data = await response.json();
                        if (!response.ok) {
                            throw new Error(data.error || 'Delete failed');
                        }
                        
                        // Remove item from local array on success
                        this.items = this.items.filter(v => v.id !== id);
                        alert(data.message);
                    } catch (error) {
                        alert(error.message);
                    } finally {
                        this.hideLoading();
                    }
                }
                <?php endif; ?>
            },

            showLoading() {
                this.loading = true;
                document.getElementById('loadingOverlay')?.classList.remove('hidden');
            },

            hideLoading() {
                this.loading = false;
                document.getElementById('loadingOverlay')?.classList.add('hidden');
            },

            get filteredItems() {
                return this.items
                    .filter(item => 
                        Object.values(item).some(value => 
                            value && value.toString().toLowerCase().includes(this.search.toLowerCase())
                        )
                    )
                    .sort((a, b) => {
                        const modifier = this.sortDirection === 'asc' ? 1 : -1;
                        if (a[this.sortField] < b[this.sortField]) return -1 * modifier;
                        if (a[this.sortField] > b[this.sortField]) return 1 * modifier;
                        return 0;
                    });
            },

            get totalPages() {
                return Math.ceil(this.filteredItems.length / this.itemsPerPage);
            },

            get paginatedItems() {
                const start = (this.currentPage - 1) * this.itemsPerPage;
                const end = start + this.itemsPerPage;
                return this.filteredItems.slice(start, end);
            },

            toggleSort(field) {
                if (this.sortField === field) {
                    this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
                } else {
                    this.sortField = field;
                    this.sortDirection = 'asc';
                }
            },

            getSortIcon(field) {
                if (this.sortField !== field) return '↕️';
                return this.sortDirection === 'asc' ? '↑' : '↓';
            }
        }));

        // Bandwidth Bills component
        Alpine.data('billsForm', () => ({
            vendors: [],
            monthOptions: [],
            showCreateBillModal: false,
            loading: false,
            
            newBill: {
                vendor_id: '',
                bill_number: '',
                bill_month: '',
                bill_month_ts: '',
                amount: 0,
                notes: '',
                metadata: [
                    { type: '', quantity: 1, unit_price: 0, total: 0 }
                ]
            },
            
            async init() {
                await this.fetchVendors();
                await this.fetchMonthOptions();
                // Watch store for changes
                Alpine.effect(() => {
                    if (Alpine.store('vendors').shouldRefresh) {
                        this.fetchVendors();
                    }
                });
            },
            
            async fetchVendors() {
                try {
                    this.loading = true;
                    const response = await fetch('/api/vendors');
                    if (!response.ok) {
                        throw new Error('Failed to fetch vendors');
                    }
                    const data = await response.json();
                    this.vendors = data;
                } catch (error) {
                    alert(error.message);
                } finally {
                    this.loading = false;
                }
            },
            
            async fetchMonthOptions() {
                try {
                    const response = await fetch('/api/bills/month-options');
                    if (!response.ok) {
                        throw new Error('Failed to fetch month options');
                    }
                    const data = await response.json();
                    this.monthOptions = data;
                    
                    // Set default selected values
                    const selected = this.monthOptions.find(opt => opt.selected);
                    if (selected) {
                        this.newBill.bill_month = selected.value;
                        this.newBill.bill_month_ts = selected.timestamp;
                    }
                } catch (error) {
                    alert(error.message);
                }
            },

            addMetadataRow() {
                this.newBill.metadata.push({ type: '', quantity: 1, unit_price: 0, total: 0 });
            },
            
            removeMetadataRow(index) {
                if (this.newBill.metadata.length > 1) {
                    this.newBill.metadata.splice(index, 1);
                    this.calculateTotalAmount();
                }
            },
            
            calculateRowTotal(row) {
                row.total = (parseFloat(row.quantity) * parseFloat(row.unit_price)).toFixed(2);
                this.calculateTotalAmount();
            },
            
            calculateTotalAmount() {
                this.newBill.amount = this.newBill.metadata.reduce((sum, item) => {
                    return sum + parseFloat(item.total || 0);
                }, 0).toFixed(2);
            },
            
            async createBill() {
                <?php if(!Users::can('CREATE')): ?>
                    alert('Permission denied');
                    return;
                <?php else: ?>
                
                // Validate the form
                if (!this.newBill.vendor_id) {
                    alert('Please select a vendor.');
                    return;
                }
                
                if (!this.newBill.bill_number) {
                    alert('Please enter a bill number.');
                    return;
                }
                
                if (!this.newBill.bill_month) {
                    alert('Please enter a bill month.');
                    return;
                }
                
                // Validate metadata rows
                let isValid = true;
                this.newBill.metadata.forEach((item, index) => {
                    if (!item.type) {
                        alert(`Please enter a bandwidth type for row ${index + 1}.`);
                        isValid = false;
                    }
                    if (!item.quantity || parseFloat(item.quantity) <= 0) {
                        alert(`Please enter a valid quantity for row ${index + 1}.`);
                        isValid = false;
                    }
                    if (!item.unit_price || parseFloat(item.unit_price) <= 0) {
                        alert(`Please enter a valid unit price for row ${index + 1}.`);
                        isValid = false;
                    }
                });
                
                if (!isValid) return;
                
                try {
                    this.loading = true;
                    const response = await fetch('/api/bills/new', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(this.newBill)
                    });
                    
                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(data.error || 'Failed to create bill');
                    }
                    
                    alert(data.message);
                    this.resetForm();
                    this.showCreateBillModal = false;
                    // Trigger bills table refresh
                    Alpine.store('bills').triggerRefresh();
                } catch (error) {
                    alert(error.message);
                } finally {
                    this.loading = false;
                }
                <?php endif; ?>
            },
            
            resetForm() {
                this.newBill = {
                    vendor_id: '',
                    bill_number: '',
                    bill_month: this.newBill.bill_month, // Keep the current month
                    amount: 0,
                    notes: '',
                    metadata: [
                        { type: '', quantity: 1, unit_price: 0, total: 0 }
                    ]
                };
            }
        }));

        // Bills table component
        Alpine.data('billsTable', () => ({
            search: '',
            currentPage: 1,
            itemsPerPage: 10,
            sortField: 'created_at',
            sortDirection: 'desc',
            items: [],
            showBillDetailsModal: false,
            selectedBill: null,
            loading: false,
            
            async init() {
                await this.fetchBills();
                // Watch store for changes
                Alpine.effect(() => {
                    if (Alpine.store('bills').shouldRefresh) {
                        this.fetchBills();
                    }
                });
            },
            
            async fetchBills() {
                try {
                    this.loading = true;
                    const response = await fetch('/api/bills');
                    if (!response.ok) {
                        const error = await response.json();
                        throw new Error(error.error || 'Failed to fetch bills');
                    }
                    this.items = await response.json();
                } catch (error) {
                    alert(error.message);
                } finally {
                    this.loading = false;
                }
            },
            
            async viewBillDetails(bill) {
                this.selectedBill = bill;
                this.showBillDetailsModal = true;
            },
            
            async editBill(id) {
                // For future implementation
                alert('Edit functionality will be implemented in the future.');
            },
            
            async deleteBill(id) {
                <?php if(!Users::can('DELETE')): ?>
                    alert('Permission denied');
                    return;
                <?php else: ?>
                if (confirm('Are you sure you want to delete this bill? This action cannot be undone.')) {
                    try {
                        this.loading = true;
                        const response = await fetch('/api/bills/delete', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ id: id })
                        });
                        
                        const data = await response.json();
                        if (!response.ok) {
                            throw new Error(data.error || 'Delete failed');
                        }
                        
                        // Remove item from local array on success
                        this.items = this.items.filter(b => b.id !== id);
                        alert(data.message);
                    } catch (error) {
                        alert(error.message);
                    } finally {
                        this.loading = false;
                    }
                }
                <?php endif; ?>
            },
            
            get filteredItems() {
                return this.items
                    .filter(item => 
                        Object.values(item).some(value => 
                            value && typeof value === 'string' && 
                            value.toLowerCase().includes(this.search.toLowerCase())
                        )
                    )
                    .sort((a, b) => {
                        const modifier = this.sortDirection === 'asc' ? 1 : -1;
                        
                        // Handle numeric sorting
                        if (this.sortField === 'amount') {
                            return (parseFloat(a[this.sortField]) - parseFloat(b[this.sortField])) * modifier;
                        }
                        
                        // Default string sorting
                        if (a[this.sortField] < b[this.sortField]) return -1 * modifier;
                        if (a[this.sortField] > b[this.sortField]) return 1 * modifier;
                        return 0;
                    });
            },
            
            get totalPages() {
                return Math.ceil(this.filteredItems.length / this.itemsPerPage);
            },
            
            get paginatedItems() {
                const start = (this.currentPage - 1) * this.itemsPerPage;
                const end = start + this.itemsPerPage;
                return this.filteredItems.slice(start, end);
            },
            
            toggleSort(field) {
                if (this.sortField === field) {
                    this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
                } else {
                    this.sortField = field;
                    this.sortDirection = 'asc';
                }
            },
            
            getSortIcon(field) {
                if (this.sortField !== field) return '↕️';
                return this.sortDirection === 'asc' ? '↑' : '↓';
            }
        }));
    });
</script>

<!-- Vendor Management -->
<div class="bg-white p-6 rounded-lg shadow-md" x-data="vendorTable()" x-init="init">
    <!-- Create Vendor Form and Existing Vendors -->
    <!-- Commented out original vendor form
    <div class="mb-6">
        <h2 class="text-xl font-semibold mb-4">Add New Vendor</h2>
        <?php if(Users::can('CREATE')): ?>
        <form @submit.prevent="createVendor" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-2">
                <label for="vendor_name" class="block text-sm font-medium text-gray-700">Vendor Name <span class="text-red-500">*</span></label>
                <input type="text" id="vendor_name" x-model="newVendor.vendor_name" required
                    class="w-full rounded block p-2 mt-1 border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="space-y-2">
                <label for="contact_person" class="block text-sm font-medium text-gray-700">Contact Person</label>
                <input type="text" id="contact_person" x-model="newVendor.contact_person"
                    class="w-full rounded block p-2 mt-1 border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="space-y-2">
                <label for="phone_number" class="block text-sm font-medium text-gray-700">Phone Number</label>
                <input type="text" id="phone_number" x-model="newVendor.phone_number"
                    class="w-full rounded block p-2 mt-1 border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="space-y-2">
                <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                <textarea id="address" x-model="newVendor.address" rows="2"
                    class="w-full rounded block p-2 mt-1 border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
            </div>
            <div class="md:col-span-2 mt-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Add Vendor
                </button>
            </div>
        </form>
        <?php else: ?>
        <div class="bg-yellow-50 border border-yellow-400 text-yellow-700 p-4 rounded-md">
            You don't have permission to create new vendors.
        </div>
        <?php endif; ?>
    </div>
    -->

    <!-- <div class="border-t pt-6"> -->
    <div class="">
        <!-- Action Buttons -->
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold">Vendor List</h2>
            <div class="flex space-x-3">
                <?php if(Users::can('CREATE')): ?>
                <button 
                    @click="showCreateModal = true"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 flex items-center"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Add Vendor
                </button>
                <button 
                    @click="$dispatch('open-bill-modal')"
                    class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 flex items-center"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Add Bandwidth Bill
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="mb-4 flex flex-wrap gap-4 items-center">
            <div class="relative">
                <input 
                    type="text" 
                    x-model="search"
                    placeholder="Search vendors..." 
                    class="w-64 pl-4 pr-10 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                <div class="absolute top-0 right-0 h-full flex items-center pr-3">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 0 0114 0z" />
                    </svg>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <span class="text-gray-600">Show</span>
                <select x-model="itemsPerPage" class="border rounded-md px-2 py-1">
                    <option>5</option>
                    <option>10</option>
                    <option>25</option>
                    <option>50</option>
                </select>
                <span class="text-gray-600">entries</span>
            </div>
        </div>

        <!-- Vendors Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full table-auto border-collapse">
                <thead class="bg-gray-50">
                    <tr>
                        <th 
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                            @click="toggleSort('vendor_name')"
                        >
                            Vendor Name <span x-text="getSortIcon('vendor_name')"></span>
                        </th>
                        <th 
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                            @click="toggleSort('contact_person')"
                        >
                            Contact Person <span x-text="getSortIcon('contact_person')"></span>
                        </th>
                        <th 
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                        >
                            Phone Number
                        </th>
                        <th 
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                        >
                            Address
                        </th>
                        <th 
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                            @click="toggleSort('created_at')"
                        >
                            Created At <span x-text="getSortIcon('created_at')"></span>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-if="loading">
                        <tr>
                            <td colspan="6" class="text-center py-4">Loading...</td>
                        </tr>
                    </template>
                    <template x-if="!loading && paginatedItems.length === 0">
                        <tr>
                            <td colspan="6" class="text-center py-4">No vendors found</td>
                        </tr>
                    </template>
                    <template x-for="item in paginatedItems" :key="item.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap" x-text="item.vendor_name"></td>
                            <td class="px-6 py-4 whitespace-nowrap" x-text="item.contact_person || '-'"></td>
                            <td class="px-6 py-4 whitespace-nowrap" x-text="item.phone_number || '-'"></td>
                            <td class="px-6 py-4" x-text="item.address || '-'"></td>
                            <td class="px-6 py-4 whitespace-nowrap" x-text="new Date(item.created_at).toLocaleString()"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex space-x-2">
                                    <?php if(Users::can('UPDATE')): ?>
                                    <button @click="editItem(item)" class="text-blue-500 hover:text-blue-700">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <?php endif; ?>
                                    <?php if(Users::can('DELETE')): ?>
                                    <button @click="deleteItem(item.id)" class="text-red-500 hover:text-red-700">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
            <div class="text-gray-600">
                Showing <span x-text="((currentPage - 1) * itemsPerPage) + 1"></span> to 
                <span x-text="Math.min(currentPage * itemsPerPage, filteredItems.length)"></span> of 
                <span x-text="filteredItems.length"></span> entries
            </div>
            <div class="flex space-x-2">
                <button 
                    @click="currentPage--" 
                    :disabled="currentPage === 1"
                    class="px-3 py-1 border rounded-md hover:bg-gray-100" 
                    :class="{'opacity-50 cursor-not-allowed': currentPage === 1}">
                    Previous
                </button>
                <template x-for="page in Math.min(5, totalPages)" :key="page">
                    <button 
                        @click="currentPage = page" 
                        class="px-3 py-1 border rounded-md hover:bg-gray-100"
                        :class="{'bg-blue-500 text-white': currentPage === page}">
                        <span x-text="page"></span>
                    </button>
                </template>
                <button 
                    @click="currentPage++" 
                    :disabled="currentPage >= totalPages"
                    class="px-3 py-1 border rounded-md hover:bg-gray-100"
                    :class="{'opacity-50 cursor-not-allowed': currentPage >= totalPages}">
                    Next
                </button>
            </div>
        </div>
    </div>

    <!-- Create Vendor Modal -->
    <div x-show="showCreateModal" 
        class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center"
        @click.self="showCreateModal = false">
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Add New Vendor</h2>
                <button @click="showCreateModal = false" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <?php if(Users::can('CREATE')): ?>
            <form @submit.prevent="createVendor" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label for="vendor_name" class="block text-sm font-medium text-gray-700">Vendor Name <span class="text-red-500">*</span></label>
                    <input type="text" id="vendor_name" x-model="newVendor.vendor_name" required
                        class="w-full rounded block p-2 mt-1 border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="space-y-2">
                    <label for="contact_person" class="block text-sm font-medium text-gray-700">Contact Person</label>
                    <input type="text" id="contact_person" x-model="newVendor.contact_person"
                        class="w-full rounded block p-2 mt-1 border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="space-y-2">
                    <label for="phone_number" class="block text-sm font-medium text-gray-700">Phone Number</label>
                    <input type="text" id="phone_number" x-model="newVendor.phone_number"
                        class="w-full rounded block p-2 mt-1 border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="space-y-2">
                    <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                    <textarea id="address" x-model="newVendor.address" rows="2"
                        class="w-full rounded block p-2 mt-1 border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                </div>
                <div class="md:col-span-2 flex justify-end space-x-3 mt-4">
                    <button type="button" @click="showCreateModal = false"
                        class="px-4 py-2 border rounded-md hover:bg-gray-100">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Add Vendor
                    </button>
                </div>
            </form>
            <?php else: ?>
            <div class="bg-yellow-50 border border-yellow-400 text-yellow-700 p-4 rounded-md">
                You don't have permission to create new vendors.
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit Vendor Modal -->
    <div x-show="showEditModal" 
        class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center"
        @click.self="showEditModal = false">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h2 class="text-xl font-bold mb-4">Edit Vendor</h2>
            <form @submit.prevent="updateVendor">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Vendor Name</label>
                        <input type="text" 
                            x-model="editingVendor.vendor_name"
                            required
                            class="mt-1 block p-2 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Contact Person</label>
                        <input type="text" 
                            x-model="editingVendor.contact_person"
                            class="mt-1 block p-2 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                        <input type="text" 
                            x-model="editingVendor.phone_number"
                            class="mt-1 block p-2 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Address</label>
                        <textarea
                            x-model="editingVendor.address"
                            rows="3"
                            class="mt-1 block p-2 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" 
                            @click="showEditModal = false; editingVendor = null"
                            class="px-4 py-2 border rounded-md hover:bg-gray-100">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bandwidth Bills Form -->
<div x-data="billsForm()" x-init="init" @open-bill-modal.window="showCreateBillModal = true">
    <!-- Create Bill Modal -->
    <div x-show="showCreateBillModal" 
        class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center overflow-y-auto"
        @click.self="showCreateBillModal = false">
        <div class="bg-white rounded-lg p-6 w-full max-w-4xl my-8" @click.outside="showCreateBillModal = false">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Add New Bandwidth Bill</h2>
                <button @click="showCreateBillModal = false" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form @submit.prevent="createBill" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Vendor -->
                    <div>
                        <label for="vendor_id" class="block text-sm font-medium text-gray-700 mb-1">Vendor <span class="text-red-500">*</span></label>
                        <select 
                            id="vendor_id" 
                            x-model="newBill.vendor_id" 
                            class="mt-1 block w-full rounded p-2 border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            required
                        >
                            <option value="">Select a vendor</option>
                            <template x-for="vendor in vendors" :key="vendor.id">
                                <option :value="vendor.id" x-text="vendor.vendor_name"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Bill Number -->
                    <div>
                        <label for="bill_number" class="block text-sm font-medium text-gray-700 mb-1">Bill Number <span class="text-red-500">*</span></label>
                        <input 
                            type="text" 
                            id="bill_number" 
                            x-model="newBill.bill_number" 
                            class="mt-1 block w-full rounded p-2 border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            required
                        >
                    </div>

                    <!-- Bill Month -->
                    <div>
                        <label for="bill_month" class="block text-sm font-medium text-gray-700 mb-1">Bill Month <span class="text-red-500">*</span></label>
                        <select 
                            id="bill_month" 
                            x-model="newBill.bill_month"
                            @change="newBill.bill_month_ts = $event.target.selectedOptions[0].dataset.timestamp"
                            class="mt-1 block w-full rounded p-2 border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            required
                        >
                            <template x-for="option in monthOptions" :key="option.value">
                                <option 
                                    :value="option.value" 
                                    :selected="option.selected"
                                    :data-timestamp="option.timestamp"
                                    x-text="option.label"
                                ></option>
                            </template>
                        </select>
                        <input 
                            type="hidden" 
                            x-model="newBill.bill_month_ts"
                        >
                    </div>

                    <!-- Total Amount (Calculated) -->
                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">Total Amount</label>
                        <input 
                            type="text" 
                            id="amount" 
                            x-model="newBill.amount" 
                            class="mt-1 block w-full rounded p-2 border-gray-300 bg-gray-100 shadow-sm"
                            readonly
                        >
                    </div>
                </div>

                <!-- Notes -->
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea 
                        id="notes" 
                        x-model="newBill.notes" 
                        rows="2"
                        class="mt-1 block w-full rounded p-2 border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    ></textarea>
                </div>

                <!-- Bandwidth Metadata Section -->
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-medium">Bandwidth Details</h3>
                        <button 
                            type="button"
                            @click="addMetadataRow"
                            class="px-3 py-1 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700"
                        >
                            Add Item
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bandwidth Type</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    <th class="px-4 py-2 text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="(item, index) in newBill.metadata" :key="index">
                                    <tr>
                                        <td class="px-4 py-2">
                                            <input 
                                                type="text" 
                                                x-model="item.type" 
                                                placeholder="e.g. Dedicated IP"
                                                class="block p-2 w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                                required
                                            >
                                        </td>
                                        <td class="px-4 py-2">
                                            <div class="flex items-center">
                                                <input 
                                                    type="number" 
                                                    x-model="item.quantity" 
                                                    min="0.01" 
                                                    step="0.01"
                                                    @input="calculateRowTotal(item)"
                                                    class="block p-2 w-24 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                                    required
                                                >
                                                <span class="ml-2 text-sm text-gray-500">Mbps</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2">
                                            <div class="flex items-center">
                                                <span class="mr-1 text-sm text-gray-500">$</span>
                                                <input 
                                                    type="number" 
                                                    x-model="item.unit_price" 
                                                    min="0.01" 
                                                    step="0.01"
                                                    @input="calculateRowTotal(item)"
                                                    class="block p-2 w-24 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                                    required
                                                >
                                            </div>
                                        </td>
                                        <td class="px-4 py-2">
                                            <div class="flex items-center">
                                                <span class="mr-1 text-sm text-gray-500">$</span>
                                                <input 
                                                    type="text" 
                                                    x-model="item.total" 
                                                    class="block p-2 w-24 border-gray-300 bg-gray-100 rounded-md shadow-sm text-sm"
                                                    readonly
                                                >
                                            </div>
                                        </td>
                                        <td class="px-4 py-2 text-center">
                                            <button 
                                                type="button"
                                                @click="removeMetadataRow(index)"
                                                class="text-red-500 p-2 hover:text-red-700"
                                                :disabled="newBill.metadata.length === 1"
                                                :class="{'opacity-50 cursor-not-allowed': newBill.metadata.length === 1}"
                                            >
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 pt-4 border-t">
                    <button 
                        type="button" 
                        @click="showCreateBillModal = false"
                        class="px-4 py-2 border rounded-md hover:bg-gray-100"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit"
                        class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700"
                        :disabled="loading"
                    >
                        <span x-show="loading" class="inline-block animate-spin mr-2">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                        Save Bill
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bandwidth Bills List -->
<div class="bg-white p-6 rounded-lg shadow-md mt-10" x-data="billsTable()" x-init="init">
    <h2 class="text-xl font-semibold mb-4">Bandwidth Bills</h2>
    
    <!-- Search and Filters -->
    <div class="mb-4 flex flex-wrap gap-4 items-center">
        <div class="relative">
            <input 
                type="text" 
                x-model="search"
                placeholder="Search bills..." 
                class="w-64 pl-4 pr-10 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
            <div class="absolute top-0 right-0 h-full flex items-center pr-3">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 0 0114 0z" />
                </svg>
            </div>
        </div>
        <div class="flex items-center space-x-2">
            <span class="text-gray-600">Show</span>
            <select x-model="itemsPerPage" class="border rounded-md px-2 py-1">
                <option>5</option>
                <option>10</option>
                <option>25</option>
                <option>50</option>
            </select>
            <span class="text-gray-600">entries</span>
        </div>
    </div>

    <!-- Bills Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full table-auto border-collapse">
            <thead class="bg-gray-50">
                <tr>
                    <th 
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                        @click="toggleSort('bill_number')"
                    >
                        Bill # <span x-text="getSortIcon('bill_number')"></span>
                    </th>
                    <th 
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                        @click="toggleSort('vendor_name')"
                    >
                        Vendor <span x-text="getSortIcon('vendor_name')"></span>
                    </th>
                    <th 
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                        @click="toggleSort('bill_month')"
                    >
                        Month <span x-text="getSortIcon('bill_month')"></span>
                    </th>
                    <th 
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                        @click="toggleSort('amount')"
                    >
                        Amount <span x-text="getSortIcon('amount')"></span>
                    </th>
                    <th 
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                        @click="toggleSort('created_at')"
                    >
                        Created At <span x-text="getSortIcon('created_at')"></span>
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <template x-if="loading">
                    <tr>
                        <td colspan="6" class="text-center py-4">Loading...</td>
                    </tr>
                </template>
                <template x-if="!loading && paginatedItems.length === 0">
                    <tr>
                        <td colspan="6" class="text-center py-4">No bills found</td>
                    </tr>
                </template>
                <template x-for="item in paginatedItems" :key="item.id">
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap" x-text="item.bill_number"></td>
                        <td class="px-6 py-4 whitespace-nowrap" x-text="item.vendor_name"></td>
                        <td class="px-6 py-4 whitespace-nowrap" x-text="new Date(item.bill_month_ts).toLocaleDateString('en-US', { year: 'numeric', month: 'long' })"></td>
                        <td class="px-6 py-4 whitespace-nowrap">$<span x-text="parseFloat(item.amount).toFixed(2)"></span></td>
                        <td class="px-6 py-4 whitespace-nowrap" x-text="new Date(item.created_at).toLocaleString()"></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex space-x-2">
                                <button @click="viewBillDetails(item)" class="text-blue-500 hover:text-blue-700">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                                <?php if(Users::can('UPDATE')): ?>
                                <button @click="editBill(item.id)" class="text-blue-500 hover:text-blue-700">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <?php endif; ?>
                                <?php if(Users::can('DELETE')): ?>
                                <button @click="deleteBill(item.id)" class="text-red-500 hover:text-red-700">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
        <div class="text-gray-600">
            Showing <span x-text="((currentPage - 1) * itemsPerPage) + 1"></span> to 
            <span x-text="Math.min(currentPage * itemsPerPage, filteredItems.length)"></span> of 
            <span x-text="filteredItems.length"></span> entries
        </div>
        <div class="flex space-x-2">
            <button 
                @click="currentPage--" 
                :disabled="currentPage === 1"
                class="px-3 py-1 border rounded-md hover:bg-gray-100" 
                :class="{'opacity-50 cursor-not-allowed': currentPage === 1}">
                Previous
            </button>
            <template x-for="page in Math.min(5, totalPages)" :key="page">
                <button 
                    @click="currentPage = page" 
                    class="px-3 py-1 border rounded-md hover:bg-gray-100"
                    :class="{'bg-blue-500 text-white': currentPage === page}">
                    <span x-text="page"></span>
                </button>
            </template>
            <button 
                @click="currentPage++" 
                :disabled="currentPage >= totalPages"
                class="px-3 py-1 border rounded-md hover:bg-gray-100"
                :class="{'opacity-50 cursor-not-allowed': currentPage >= totalPages}">
                Next
            </button>
        </div>
    </div>
    
    <!-- Bill Details Modal -->
    <div x-show="showBillDetailsModal" 
        class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center overflow-y-auto"
        @click.self="showBillDetailsModal = false">
        <div class="bg-white rounded-lg p-6 w-full max-w-4xl my-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold">Bill Details</h2>
                <button @click="showBillDetailsModal = false" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <template x-if="selectedBill">
                <div class="space-y-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <div class="flex">
                                <span class="w-32 font-medium">Bill Number:</span>
                                <span x-text="selectedBill.bill_number"></span>
                            </div>
                            <div class="flex">
                                <span class="w-32 font-medium">Vendor:</span>
                                <span x-text="selectedBill.vendor_name"></span>
                            </div>
                            <div class="flex">
                                <span class="w-32 font-medium">Month:</span>
                                <span x-text="selectedBill.bill_month"></span>
                            </div>
                            <div class="flex">
                                <span class="w-32 font-medium">Created:</span>
                                <span x-text="new Date(selectedBill.created_at).toLocaleString()"></span>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div class="flex">
                                <span class="w-32 font-medium">Amount:</span>
                                <span>$<span x-text="parseFloat(selectedBill.amount).toFixed(2)"></span></span>
                            </div>
                            <div class="flex items-start">
                                <span class="w-32 font-medium">Notes:</span>
                                <span x-text="selectedBill.notes || '-'"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="border-t pt-4">
                        <h3 class="font-medium text-lg mb-2">Bandwidth Details</h3>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity (Mbps)</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="(meta, index) in selectedBill.metadata" :key="index">
                                    <tr>
                                        <td class="px-4 py-2" x-text="meta.type"></td>
                                        <td class="px-4 py-2" x-text="parseFloat(meta.quantity).toFixed(2)"></td>
                                        <td class="px-4 py-2">$<span x-text="parseFloat(meta.unit_price).toFixed(2)"></span></td>
                                        <td class="px-4 py-2">$<span x-text="parseFloat(meta.total).toFixed(2)"></span></td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot class="bg-gray-50 font-medium">
                                <tr>
                                    <td colspan="3" class="px-4 py-2 text-right">Total:</td>
                                    <td class="px-4 py-2">$<span x-text="parseFloat(selectedBill.amount).toFixed(2)"></span></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>