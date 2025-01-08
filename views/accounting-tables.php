<!-- Tables Row -->
<div class="grid gap-6">
    <?php if(!Users::hasPermission(resolve($_SESSION['user_id'], $_COOKIE['user_id']), 'WRITE')): ?>
        <!-- Income Table -->
        <div class="bg-white p-6 rounded-lg shadow overflow-x-auto" x-data="{
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
                    const currentDate = new Date();
                    const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1).toISOString().split('T')[0];
                    const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0).toISOString().split('T')[0];
                    this.filters.startDate = firstDay;
                    this.filters.endDate = lastDay;
                }
                try {
                    const params = new URLSearchParams({
                        page: this.currentPage,
                        itemsPerPage: this.itemsPerPage,
                        search: this.search,
                        ...this.filters
                    });
                    const response = await fetch(`/api/finance/incomes?${params}`);
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
            <h3 class="text-lg font-semibold mb-4">Income Records</h3>
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
                    <?php foreach(FinanceManager::getIncomeCategories() as $category): ?>
                        <option value="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></option>
                    <?php endforeach; ?>
                </select>
                <select x-model="filters.status" @change="currentPage = 1; loadData()" 
                    class="border p-2 rounded bg-white">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="denied">Denied</option>
                    <?php if(Users::hasPermission(resolve($_SESSION['user_id'], $_COOKIE['user_id']), 'DELETE')): ?>
                        <option value="deleted">Deleted</option>
                    <?php endif; ?>
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
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Created</th>
                            <!-- <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Modified</th> -->
                            <?php if(Users::hasPermission(resolve($_SESSION['user_id'], $_COOKIE['user_id']), 'APPROVE')): ?>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Entry By</th>
                            <?php endif; ?>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Income From</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                            <!-- <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bank</th> -->
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-if="loading">
                            <tr>
                                <td colspan="10" class="text-center py-4">Loading...</td>
                            </tr>
                        </template>
                        <template x-for="item in items" :key="item.id">
                            <tr class="hover:bg-gray-100">
                                <td class="px-4 py-2" x-text="item.id"></td>
                                <td class="px-4 py-2" x-text="new Date(item.date_created).toLocaleString()"></td>
                                <!-- <td class="px-4 py-2" x-text="new Date(item.date_modified).toLocaleString()"></td> -->
                                <?php if(Users::hasPermission(resolve($_SESSION['user_id'], $_COOKIE['user_id']), 'APPROVE')): ?>
                                    <td class="px-4 py-2" x-text="item.entry_by_name"></td>
                                <?php endif; ?>
                                <!-- <td class="px-4 py-2" x-text="item.entry_by_name || '-'"></td> -->
                                <td class="px-4 py-2" x-text="item.income_from"></td>
                                <td class="px-4 py-2" x-text="item.category"></td>
                                <td class="px-4 py-2" x-text="item.amount"></td>
                                <td class="px-4 py-2" x-text="item.method"></td>
                                <!-- <td class="px-4 py-2" x-text="item.bank"></td> -->
                                <td class="px-4 py-2" x-text="item.notes"></td>
                                <td class="px-4 py-2">
                                    <template x-if="item.status == 'pending'">
                                        <?php if(Users::hasPermission(resolve($_SESSION['user_id'], $_COOKIE['user_id']), 'APPROVE')): ?>
                                            <a href="#" @click.prevent="async function() { 
                                                try {
                                                    const response = await fetch('/api/finance/income/approve', {
                                                        method: 'POST',
                                                        headers: { 'Content-Type': 'application/json' },
                                                        body: JSON.stringify({ id: item.id })
                                                    });
                                                    if (response.ok) {
                                                        loadData();
                                                    }
                                                } catch (error) {
                                                    console.error('Error:', error);
                                                }
                                            }()" class="text-blue-500 hover:text-blue-700">Pending</a>
                                        <?php else: ?>
                                            <span>Pending</span>
                                        <?php endif; ?>
                                    </template>
                                    <template x-if="item.status != 'pending'">
                                        <span x-text="item.status"></span>
                                    </template>
                                </td>
                                <td class="px-4 py-2">
                                    <button @click="modalData = item; showModal = true" class="text-blue-500 hover:text-blue-700 mr-2">View</button>
                                    <?php if(Users::hasPermission(resolve($_SESSION['user_id'], $_COOKIE['user_id']), 'DELETE')): ?>
                                        <button 
                                            @click="if(confirm('Are you sure you want to delete this income record?')) {
                                                fetch('/api/finance/income/delete', {
                                                    method: 'POST',
                                                    headers: { 'Content-Type': 'application/json' },
                                                    body: JSON.stringify({ id: item.id })
                                                }).then(response => response.json())
                                                  .then(data => {
                                                      if(data.success) {
                                                          loadData();
                                                      } else {
                                                          alert(data.error);
                                                      }
                                                  });
                                            }" 
                                            class="text-red-500 hover:text-red-700"
                                        >Delete</button>
                                    <?php endif; ?>
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
            <div x-show="showModal" x-cloak class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
                <div class="bg-white p-6 rounded-lg shadow-lg w-3/4 max-h-[90vh] overflow-y-auto">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold">Entry Details</h2>
                        <button @click="showModal = false" class="text-gray-500 hover:text-gray-700">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Left Column -->
                        <div class="space-y-4">
                            <div class="bg-gray-50 p-4 rounded">
                                <h3 class="font-semibold mb-2">Basic Information</h3>
                                <table class="w-full">
                                    <tr>
                                        <td class="font-medium pr-4">ID:</td>
                                        <td x-text="modalData.id"></td>
                                    </tr>
                                    <tr>
                                        <td class="font-medium pr-4">Status:</td>
                                        <td>
                                            <span x-text="modalData.status" 
                                                :class="{
                                                    'px-2 py-1 rounded text-sm font-medium': true,
                                                    'bg-yellow-100 text-yellow-800': modalData.status === 'pending',
                                                    'bg-green-100 text-green-800': modalData.status === 'approved'
                                                }">
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="font-medium pr-4">Amount:</td>
                                        <td>
                                            <template x-if="modalData.status === 'pending' && (modalData.entry_by == <?= $_SESSION['user_id'] ?> || <?= Users::hasPermission($_SESSION['user_id'], 'DELETE') ? 'true' : 'false' ?>)">
                                                <input type="number" step="0.01" 
                                                    x-model="modalData.amount"
                                                    class="border p-1 rounded w-full"
                                                    @change="updateField('amount', $event.target.value)">
                                            </template>
                                            <template x-if="!(modalData.status === 'pending' && (modalData.entry_by == <?= $_SESSION['user_id'] ?> || <?= Users::hasPermission($_SESSION['user_id'], 'DELETE') ? 'true' : 'false' ?>))">
                                                <span x-text="modalData.amount"></span>
                                            </template>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <div class="bg-gray-50 p-4 rounded">
                                <h3 class="font-semibold mb-2">Category & Method</h3>
                                <table class="w-full">
                                    <tr>
                                        <td class="font-medium pr-4">Category:</td>
                                        <td>
                                            <template x-if="modalData.status === 'pending' && (modalData.entry_by == <?= $_SESSION['user_id'] ?> || <?= Users::hasPermission($_SESSION['user_id'], 'DELETE') ? 'true' : 'false' ?>)">
                                                <input type="text" 
                                                    x-model="modalData.category"
                                                    class="border p-1 rounded w-full"
                                                    @change="updateField('category', $event.target.value)">
                                            </template>
                                            <template x-if="!(modalData.status === 'pending' && (modalData.entry_by == <?= $_SESSION['user_id'] ?> || <?= Users::hasPermission($_SESSION['user_id'], 'DELETE') ? 'true' : 'false' ?>))">
                                                <span x-text="modalData.category"></span>
                                            </template>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="font-medium pr-4">Method:</td>
                                        <td>
                                            <template x-if="modalData.status === 'pending' && (modalData.entry_by == <?= $_SESSION['user_id'] ?> || <?= Users::hasPermission($_SESSION['user_id'], 'DELETE') ? 'true' : 'false' ?>)">
                                                <select x-model="modalData.method" 
                                                    class="border p-1 rounded w-full"
                                                    @change="updateField('method', $event.target.value)">
                                                    <option value="Bank Transfer">Bank Transfer</option>
                                                    <option value="Bkash">Bkash</option>
                                                    <option value="Nagad">Nagad</option>
                                                    <option value="Credit Card">Credit Card</option>
                                                    <option value="Cash">Cash</option>
                                                    <option value="Check">Check</option>
                                                </select>
                                            </template>
                                            <template x-if="!(modalData.status === 'pending' && (modalData.entry_by == <?= $_SESSION['user_id'] ?> || <?= Users::hasPermission($_SESSION['user_id'], 'DELETE') ? 'true' : 'false' ?>))">
                                                <span x-text="modalData.method"></span>
                                            </template>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="font-medium pr-4">Bank:</td>
                                        <td>
                                            <template x-if="modalData.status === 'pending' && (modalData.entry_by == <?= $_SESSION['user_id'] ?> || <?= Users::hasPermission($_SESSION['user_id'], 'DELETE') ? 'true' : 'false' ?>)">
                                                <input type="text" 
                                                    x-model="modalData.bank"
                                                    class="border p-1 rounded w-full"
                                                    @change="updateField('bank', $event.target.value)">
                                            </template>
                                            <template x-if="!(modalData.status === 'pending' && (modalData.entry_by == <?= $_SESSION['user_id'] ?> || <?= Users::hasPermission($_SESSION['user_id'], 'DELETE') ? 'true' : 'false' ?>))">
                                                <span x-text="modalData.bank"></span>
                                            </template>
                                        </td>
                                    </tr>
                                    <!-- <tr>
                                        <td class="font-medium pr-4">Account Number:</td>
                                        <td x-text="modalData.account_number || '-'"></td>
                                    </tr>
                                    <tr>
                                        <td class="font-medium pr-4">Transaction Number:</td>
                                        <td x-text="modalData.transaction_number || '-'"></td>
                                    </tr> -->
                                    <tr>
                                        <td class="font-medium pr-4">Account Number:</td>
                                        <td>
                                            <template x-if="modalData.status === 'pending' && (modalData.entry_by == <?= $_SESSION['user_id'] ?> || <?= Users::hasPermission($_SESSION['user_id'], 'DELETE') ? 'true' : 'false' ?>)">
                                                <input type="text" 
                                                    x-model="modalData.account_number"
                                                    class="border p-1 rounded w-full"
                                                    @change="updateField('account_number', $event.target.value)">
                                            </template>
                                            <template x-if="!(modalData.status === 'pending' && (modalData.entry_by == <?= $_SESSION['user_id'] ?> || <?= Users::hasPermission($_SESSION['user_id'], 'DELETE') ? 'true' : 'false' ?>))">
                                                <span x-text="modalData.account_number || '-'"></span>
                                            </template>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="font-medium pr-4">Transaction Number:</td>
                                        <td>
                                            <template x-if="modalData.status === 'pending' && (modalData.entry_by == <?= $_SESSION['user_id'] ?> || <?= Users::hasPermission($_SESSION['user_id'], 'DELETE') ? 'true' : 'false' ?>)">
                                                <input type="text" 
                                                    x-model="modalData.transaction_number"
                                                    class="border p-1 rounded w-full"
                                                    @change="updateField('transaction_number', $event.target.value)">
                                            </template>
                                            <template x-if="!(modalData.status === 'pending' && (modalData.entry_by == <?= $_SESSION['user_id'] ?> || <?= Users::hasPermission($_SESSION['user_id'], 'DELETE') ? 'true' : 'false' ?>))">
                                                <span x-text="modalData.transaction_number || '-'"></span>
                                            </template>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-4">
                            <div class="bg-gray-50 p-4 rounded">
                                <h3 class="font-semibold mb-2">Timestamps</h3>
                                <table class="w-full">
                                    <tr>
                                        <td class="font-medium pr-4">Created:</td>
                                        <td x-text="new Date(modalData.date_created).toLocaleString()"></td>
                                    </tr>
                                    <tr>
                                        <td class="font-medium pr-4">Modified:</td>
                                        <td x-text="new Date(modalData.date_modified).toLocaleString()"></td>
                                    </tr>
                                    <tr>
                                        <td class="font-medium pr-4">Approved:</td>
                                        <td x-text="modalData.date_approved ? new Date(modalData.date_approved).toLocaleString() : '-'"></td>
                                    </tr>
                                </table>
                            </div>

                            <div class="bg-gray-50 p-4 rounded">
                                <h3 class="font-semibold mb-2">Additional Information</h3>
                                <table class="w-full">
                                    <tr>
                                        <td class="font-medium pr-4">Entry By:</td>
                                        <td x-text="modalData.entry_by_name"></td>
                                    </tr>
                                    <tr>
                                        <td class="font-medium pr-4">Approved By:</td>
                                        <td x-text="modalData.approved_by_name || '-'"></td>
                                    </tr>
                                    <tr>
                                        <td class="font-medium pr-4">Notes:</td>
                                        <td>
                                            <template x-if="modalData.status === 'pending' && (modalData.entry_by == <?= $_SESSION['user_id'] ?> || <?= Users::hasPermission($_SESSION['user_id'], 'DELETE') ? 'true' : 'false' ?>)">
                                                <textarea 
                                                    x-model="modalData.notes"
                                                    class="border p-1 rounded w-full"
                                                    @change="updateField('notes', $event.target.value)"></textarea>
                                            </template>
                                            <template x-if="!(modalData.status === 'pending' && (modalData.entry_by == <?= $_SESSION['user_id'] ?> || <?= Users::hasPermission($_SESSION['user_id'], 'DELETE') ? 'true' : 'false' ?>))">
                                                <span x-text="modalData.notes"></span>
                                            </template>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-2">
                        <template x-if="modalData.status === 'pending' && (modalData.entry_by == <?= $_SESSION['user_id'] ?> || <?= Users::hasPermission($_SESSION['user_id'], 'DELETE') ? 'true' : 'false' ?>)">
                            <button 
                                @click="saveChanges()"
                                class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">
                                Save Changes
                            </button>
                        </template>
                        <button 
                            @click="showModal = false" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Expense Table -->
        <div class="bg-white p-6 rounded-lg shadow overflow-x-auto" x-data="{
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
                    const currentDate = new Date();
                    const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1).toISOString().split('T')[0];
                    const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0).toISOString().split('T')[0];
                    this.filters.startDate = firstDay;
                    this.filters.endDate = lastDay;
                }
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
            },
            async approveExpense(id) {
                try {
                    const response = await fetch(`/api/finance/expense/approve`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ id })
                    });
                    if (response.ok) {
                        this.loadData();
                    } else {
                        console.error('Failed to approve expense');
                    }
                } catch (error) {
                    console.error('Error:', error);
                }
            }
        }" x-init="loadData()" @refresh-data.window="loadData()">
            <h3 class="text-lg font-semibold mb-4">Expense Records</h3>
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
                    <?php foreach(FinanceManager::getExpenseCategories() as $category): ?>
                        <option value="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></option>
                    <?php endforeach; ?>
                </select>
                <select x-model="filters.status" @change="currentPage = 1; loadData()" 
                    class="border p-2 rounded bg-white">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="denied">Denied</option>
                    <?php if(Users::hasPermission(resolve($_SESSION['user_id'], $_COOKIE['user_id']), 'DELETE')): ?>
                        <option value="deleted">Deleted</option>
                    <?php endif; ?>
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
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Created</th>
                            <!-- <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Modified</th> -->
                            <!-- <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Approved</th> -->
                            <?php if(Users::hasPermission(resolve($_SESSION['user_id'], $_COOKIE['user_id']), 'APPROVE')): ?>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Entry By</th>
                            <?php endif; ?>
                            <!-- <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Approved By</th> -->
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expense By</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                            <!-- <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bank</th> -->
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-if="loading">
                            <tr>
                                <td colspan="11" class="text-center py-4">Loading...</td>
                            </tr>
                        </template>
                        <template x-for="item in items" :key="item.id">
                            <tr class="hover:bg-gray-100">
                                <td class="px-4 py-2" x-text="item.id"></td>
                                <td class="px-4 py-2" x-text="new Date(item.date_created).toLocaleString()"></td>
                                <!-- <td class="px-4 py-2" x-text="new Date(item.date_modified).toLocaleString()"></td> -->
                                <!-- <td class="px-4 py-2" x-text="item.date_approved ? new Date(item.date_approved).toLocaleString() : '-'"></td> -->
                                <td class="px-4 py-2" x-text="item.entry_by_name || '-'"></td>
                                <!-- <td class="px-4 py-2" x-text="item.approved_by_name || '-'"></td> -->
                                <td class="px-4 py-2" x-text="item.expense_by"></td>
                                <td class="px-4 py-2" x-text="item.category"></td>
                                <td class="px-4 py-2" x-text="item.purpose"></td>
                                <td class="px-4 py-2" x-text="item.amount"></td>
                                <td class="px-4 py-2" x-text="item.method"></td>
                                <!-- <td class="px-4 py-2" x-text="item.bank"></td> -->
                                <td class="px-4 py-2" x-text="item.notes"></td>
                                <td class="px-4 py-2">
                                    <template x-if="item.status == 'pending'">
                                        <?php if(Users::hasPermission(resolve($_SESSION['user_id'], $_COOKIE['user_id']), 'APPROVE')): ?>
                                            <a href="#" @click.prevent="approveExpense(item.id)" class="text-blue-500 hover:text-blue-700">Pending</a>
                                        <?php else: ?>
                                            <span>Pending</span>
                                        <?php endif; ?>
                                    </template>
                                    <template x-if="item.status != 'pending'">
                                        <span x-text="item.status"></span>
                                    </template>
                                </td>
                                <td class="px-4 py-2">
                                    <button @click="modalData = item; showModal = true" class="text-blue-500 hover:text-blue-700 mr-2">View</button>
                                    <?php if(Users::hasPermission(resolve($_SESSION['user_id'], $_COOKIE['user_id']), 'DELETE')): ?>
                                        <button 
                                            @click="if(confirm('Are you sure you want to delete this expense record?')) {
                                                fetch('/api/finance/expense/delete', {
                                                    method: 'POST',
                                                    headers: { 'Content-Type': 'application/json' },
                                                    body: JSON.stringify({ id: item.id })
                                                }).then(response => response.json())
                                                  .then(data => {
                                                      if(data.success) {
                                                          loadData();
                                                      } else {
                                                          alert(data.error);
                                                      }
                                                  });
                                            }" 
                                            class="text-red-500 hover:text-red-700"
                                        >Delete</button>
                                    <?php endif; ?>
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
            <div x-show="showModal" x-cloak class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
                <div class="bg-white p-6 rounded-lg shadow-lg w-3/4 max-h-[90vh] overflow-y-auto">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold">Entry Details</h2>
                        <button @click="showModal = false" class="text-gray-500 hover:text-gray-700">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Left Column -->
                        <div class="space-y-4">
                            <div class="bg-gray-50 p-4 rounded">
                                <h3 class="font-semibold mb-2">Basic Information</h3>
                                <table class="w-full">
                                    <tr>
                                        <td class="font-medium pr-4">ID:</td>
                                        <td x-text="modalData.id"></td>
                                    </tr>
                                    <tr>
                                        <td class="font-medium pr-4">Status:</td>
                                        <td>
                                            <span x-text="modalData.status" 
                                                :class="{
                                                    'px-2 py-1 rounded text-sm font-medium': true,
                                                    'bg-yellow-100 text-yellow-800': modalData.status === 'pending',
                                                    'bg-green-100 text-green-800': modalData.status === 'approved'
                                                }">
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="font-medium pr-4">Amount:</td>
                                        <td>
                                            <template x-if="modalData.status === 'pending' && (modalData.entry_by == <?= $_SESSION['user_id'] ?> || <?= Users::hasPermission($_SESSION['user_id'], 'DELETE') ? 'true' : 'false' ?>)">
                                                <input type="number" step="0.01" 
                                                    x-model="modalData.amount"
                                                    class="border p-1 rounded w-full"
                                                    @change="updateField('amount', $event.target.value)">
                                            </template>
                                            <template x-if="!(modalData.status === 'pending' && (modalData.entry_by == <?= $_SESSION['user_id'] ?> || <?= Users::hasPermission($_SESSION['user_id'], 'DELETE') ? 'true' : 'false' ?>))">
                                                <span x-text="modalData.amount"></span>
                                            </template>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <div class="bg-gray-50 p-4 rounded">
                                <h3 class="font-semibold mb-2">Category & Method</h3>
                                <table class="w-full">
                                    <tr>
                                        <td class="font-medium pr-4">Category:</td>
                                        <td>
                                            <template x-if="modalData.status === 'pending' && (modalData.entry_by == <?= $_SESSION['user_id'] ?> || <?= Users::hasPermission($_SESSION['user_id'], 'DELETE') ? 'true' : 'false' ?>)">
                                                <input type="text" 
                                                    x-model="modalData.category"
                                                    class="border p-1 rounded w-full"
                                                    @change="updateField('category', $event.target.value)">
                                            </template>
                                            <template x-if="!(modalData.status === 'pending' && (modalData.entry_by == <?= $_SESSION['user_id'] ?> || <?= Users::hasPermission($_SESSION['user_id'], 'DELETE') ? 'true' : 'false' ?>))">
                                                <span x-text="modalData.category"></span>
                                            </template>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="font-medium pr-4">Method:</td>
                                        <td>
                                            <template x-if="modalData.status === 'pending' && (modalData.entry_by == <?= $_SESSION['user_id'] ?> || <?= Users::hasPermission($_SESSION['user_id'], 'DELETE') ? 'true' : 'false' ?>)">
                                                <select x-model="modalData.method" 
                                                    class="border p-1 rounded w-full"
                                                    @change="updateField('method', $event.target.value)">
                                                    <option value="Bank Transfer">Bank Transfer</option>
                                                    <option value="Bkash">Bkash</option>
                                                    <option value="Nagad">Nagad</option>
                                                    <option value="Credit Card">Credit Card</option>
                                                    <option value="Cash">Cash</option>
                                                    <option value="Check">Check</option>
                                                </select>
                                            </template>
                                            <template x-if="!(modalData.status === 'pending' && (modalData.entry_by == <?= $_SESSION['user_id'] ?> || <?= Users::hasPermission($_SESSION['user_id'], 'DELETE') ? 'true' : 'false' ?>))">
                                                <span x-text="modalData.method"></span>
                                            </template>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="font-medium pr-4">Bank:</td>
                                        <td>
                                            <template x-if="modalData.status === 'pending' && (modalData.entry_by == <?= $_SESSION['user_id'] ?> || <?= Users::hasPermission($_SESSION['user_id'], 'DELETE') ? 'true' : 'false' ?>)">
                                                <input type="text" 
                                                    x-model="modalData.bank"
                                                    class="border p-1 rounded w-full"
                                                    @change="updateField('bank', $event.target.value)">
                                            </template>
                                            <template x-if="!(modalData.status === 'pending' && (modalData.entry_by == <?= $_SESSION['user_id'] ?> || <?= Users::hasPermission($_SESSION['user_id'], 'DELETE') ? 'true' : 'false' ?>))">
                                                <span x-text="modalData.bank"></span>
                                            </template>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="font-medium pr-4">Account Number:</td>
                                        <td x-text="modalData.account_number || '-'"></td>
                                    </tr>
                                    <tr>
                                        <td class="font-medium pr-4">Transaction Number:</td>
                                        <td x-text="modalData.transaction_number || '-'"></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-4">
                            <div class="bg-gray-50 p-4 rounded">
                                <h3 class="font-semibold mb-2">Timestamps</h3>
                                <table class="w-full">
                                    <tr>
                                        <td class="font-medium pr-4">Created:</td>
                                        <td x-text="new Date(modalData.date_created).toLocaleString()"></td>
                                    </tr>
                                    <tr>
                                        <td class="font-medium pr-4">Modified:</td>
                                        <td x-text="new Date(modalData.date_modified).toLocaleString()"></td>
                                    </tr>
                                    <tr>
                                        <td class="font-medium pr-4">Approved:</td>
                                        <td x-text="modalData.date_approved ? new Date(modalData.date_approved).toLocaleString() : '-'"></td>
                                    </tr>
                                </table>
                            </div>

                            <div class="bg-gray-50 p-4 rounded">
                                <h3 class="font-semibold mb-2">Additional Information</h3>
                                <table class="w-full">
                                    <tr>
                                        <td class="font-medium pr-4">Entry By:</td>
                                        <td x-text="modalData.entry_by_name"></td>
                                    </tr>
                                    <tr>
                                        <td class="font-medium pr-4">Approved By:</td>
                                        <td x-text="modalData.approved_by_name || '-'"></td>
                                    </tr>
                                    <tr>
                                        <td class="font-medium pr-4">Notes:</td>
                                        <td>
                                            <template x-if="modalData.status === 'pending' && (modalData.entry_by == <?= $_SESSION['user_id'] ?> || <?= Users::hasPermission($_SESSION['user_id'], 'DELETE') ? 'true' : 'false' ?>)">
                                                <textarea 
                                                    x-model="modalData.notes"
                                                    class="border p-1 rounded w-full"
                                                    @change="updateField('notes', $event.target.value)"></textarea>
                                            </template>
                                            <template x-if="!(modalData.status === 'pending' && (modalData.entry_by == <?= $_SESSION['user_id'] ?> || <?= Users::hasPermission($_SESSION['user_id'], 'DELETE') ? 'true' : 'false' ?>))">
                                                <span x-text="modalData.notes"></span>
                                            </template>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-2">
                        <template x-if="modalData.status === 'pending' && (modalData.entry_by == <?= $_SESSION['user_id'] ?> || <?= Users::hasPermission($_SESSION['user_id'], 'DELETE') ? 'true' : 'false' ?>)">
                            <button 
                                @click="saveChanges()"
                                class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">
                                Save Changes
                            </button>
                        </template>
                        <button 
                            @click="showModal = false" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>