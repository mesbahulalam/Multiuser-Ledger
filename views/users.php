                <h1 class="text-3xl font-bold mb-8">Users</h1>

                <script>
                    // Shared data table component
                    document.addEventListener('alpine:init', () => {
                        Alpine.data('dataTable', (config) => ({
                            search: '',
                            currentPage: 1,
                            itemsPerPage: 10,
                            sortField: 'name',
                            sortDirection: 'asc',
                            selectedRows: [],
                            users: [],
                            items: [],
                            showEditModal: false,
                            editingUser: null,
                            roles: config.roles || [],
                            showCreateModal: false,
                            newUser: {
                                username: '',
                                email: '',
                                password: '',
                                role_name: ''
                            },

                            async init() {
                                await this.fetchUsers();
                            },

                            async fetchUsers() {
                                try {
                                    this.showLoading();
                                    const response = await fetch('/api/users');
                                    if (!response.ok) {
                                        const error = await response.json();
                                        throw new Error(error.error || 'Failed to fetch users');
                                    }
                                    const data = await response.json();
                                    this.items = data.map(user => ({
                                        id: user.user_id,
                                        username: user.username,
                                        email: user.email,
                                        role_name: user.role_name,
                                        is_active: user.is_active
                                    }));
                                } catch (error) {
                                    alert(error.message);
                                } finally {
                                    this.hideLoading();
                                }
                            },
                            async createUser() {
                                <?php if(!Users::can('DELETE')): ?>
                                    alert('Permission denied');
                                <?php else: ?>
                                try {
                                    this.showLoading();
                                    const response = await fetch('/api/users/new', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                        },
                                        body: JSON.stringify(this.newUser)
                                    });
                                    
                                    const data = await response.json();
                                    if (!response.ok) {
                                        throw new Error(data.error || 'Failed to create user');
                                    }
                                    
                                    // Refresh the users list
                                    await this.fetchUsers();
                                    this.showCreateModal = false;
                                    this.newUser = { username: '', email: '', password: '', role_name: '' };
                                    alert(data.message);
                                } catch (error) {
                                    alert(error.message);
                                } finally {
                                    this.hideLoading();
                                }
                                <?php endif; ?>
                            },
                            editItem(item) {
                                this.editingUser = { ...item };
                                this.showEditModal = true;
                            },
                            async updateUser() {
                                try {
                                    this.showLoading();
                                    const response = await fetch('/api/users/update', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                        },
                                        body: JSON.stringify(this.editingUser)
                                    });
                                    
                                    const data = await response.json();
                                    if (!response.ok) {
                                        throw new Error(data.error || 'Update failed');
                                    }
                                    
                                    // Update local data on success
                                    const index = this.items.findIndex(u => u.id === this.editingUser.id);
                                    if (index !== -1) {
                                        this.items[index] = { ...this.editingUser };
                                    }
                                    
                                    this.showEditModal = false;
                                    this.editingUser = null;
                                    alert(data.message); // Show success message
                                } catch (error) {
                                    alert(error.message);
                                } finally {
                                    this.hideLoading();
                                }
                            },
                            async deleteItem(id) {
                            <?php if(Users::can('DELETE')): ?>
                                if (confirm('Are you sure you want to delete this item?')) {
                                    try {
                                        this.showLoading();
                                        const response = await fetch('/api/users/delete', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                            },
                                            body: JSON.stringify({ user_id: id })
                                        });
                                        
                                        const data = await response.json();
                                        if (!response.ok) {
                                            throw new Error(data.error || 'Delete failed');
                                        }
                                        
                                        // Remove item from local array on success
                                        this.items = this.items.filter(u => u.id !== id);
                                        alert(data.message); // Show success message
                                    } catch (error) {
                                        alert(error.message);
                                    } finally {
                                        this.hideLoading();
                                    }
                                }
                            <?php else: ?>
                                alert('Permission denied');
                            <?php endif; ?>
                            },
                            async bulkDelete() {
                            <?php if(Users::can('DELETE')): ?>
                                if (confirm(`Are you sure you want to delete ${this.selectedRows.length} selected items?`)) {
                                    try {
                                        this.showLoading();
                                        console.log(this.selectedRows);
                                        const promises = this.selectedRows.map(async (id) => {
                                            const response = await fetch('/api/users/delete', {
                                                method: 'POST',
                                                headers: {
                                                    'Content-Type': 'application/json',
                                                },
                                                body: JSON.stringify({ user_id: id })
                                            });
                                            
                                            const data = await response.json();
                                            if (!response.ok) {
                                                throw new Error(data.error || 'Delete failed');
                                            }
                                            
                                            return data;
                                        });
                                        
                                        const results = await Promise.all(promises);
                                        this.items = this.items.filter(item => !this.selectedRows.includes(item.id));
                                        this.selectedRows = [];
                                        alert('Selected items deleted successfully');
                                    } catch (error) {
                                        alert(error.message);
                                    } finally {
                                        this.hideLoading();
                                    }
                                }
                            <?php else: ?>
                                alert('Permission denied');
                            <?php endif; ?>
                            },
                            showLoading() {
                                document.getElementById('loadingOverlay').classList.remove('hidden');
                            },
                            hideLoading() {
                                document.getElementById('loadingOverlay').classList.add('hidden');
                            },
                            get filteredItems() {
                                return this.items
                                    .filter(item => 
                                        Object.values(item).some(value => 
                                            value.toString().toLowerCase().includes(this.search.toLowerCase())
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
                                return this.itemsPerPage === 'all' ? 1 : Math.ceil(this.filteredItems.length / this.itemsPerPage);
                            },
                            get paginatedItems() {
                                if (this.itemsPerPage === 'all') {
                                    return this.filteredItems;
                                }
                                const start = (this.currentPage - 1) * this.itemsPerPage;
                                const end = start + this.itemsPerPage;
                                return this.filteredItems.slice(start, end);
                            },
                            get allSelected() {
                                return this.paginatedItems.length === this.selectedRows.length;
                            },
                            toggleSort(field) {
                                if (this.sortField === field) {
                                    this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
                                } else {
                                    this.sortField = field;
                                    this.sortDirection = 'asc';
                                }
                            },
                            toggleAll() {
                                if (this.allSelected) {
                                    this.selectedRows = [];
                                } else {
                                    this.selectedRows = this.paginatedItems.map(item => item.id);
                                }
                            },
                            toggleRow(id) {
                                const index = this.selectedRows.indexOf(id);
                                if (index === -1) {
                                    this.selectedRows.push(id);
                                } else {
                                    this.selectedRows.splice(index, 1);
                                }
                            },
                            getSortIcon(field) {
                                if (this.sortField !== field) return '↕️';
                                return this.sortDirection === 'asc' ? '↑' : '↓';
                            },
                            createPageButton(page, isActive = false) {
                                return `<button 
                                    @click='currentPage = ${page}'
                                    class='px-3 py-1 border rounded-md hover:bg-gray-100 ${isActive ? 'bg-blue-500 text-white' : ''}'
                                    >${page}</button>`;
                            },
                            createEllipsis() {
                                return '<span class\'=px-3 py-1\'>...</span>';
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
                            }
                        }))
                    })
                </script>

                <!-- Users Table -->
                <div class="bg-white p-6 rounded-lg shadow-md" x-data="dataTable({
                    roles: <?= str_replace('"', "'", json_encode(Users::getAllRoles())) ?>
                    
                })" x-init="init">
                    <!-- Add Create User Button -->
                    <div class="mb-4">
                        <button 
                            @click="showCreateModal = true"
                            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700"
                        >
                            Create New User
                        </button>
                    </div>

                    <!-- Search and Bulk Actions -->
                    <div class="mb-4 space-y-4">
                        <div class="flex justify-between items-center">
                            <div class="relative">
                                <input 
                                    type="text" 
                                    x-model="search"
                                    placeholder="Search..." 
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
                                    <option value="all">All</option>
                                </select>
                                <span class="text-gray-600">entries</span>
                            </div>
                        </div>
                        <div x-show="selectedRows.length > 0" class="bg-white shadow rounded-lg p-4 flex items-center justify-between">
                            <span class="text-sm text-gray-700">
                                <span x-text="selectedRows.length"></span> items selected
                            </span>
                            <button 
                                @click="bulkDelete"
                                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
                            >
                                Delete Selected
                            </button>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full table-auto">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 w-12">
                                        <input 
                                            type="checkbox" 
                                            class="rounded border-gray-300"
                                            :checked="allSelected"
                                            @click="toggleAll"
                                        >
                                    </th>
                                    <th 
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                                        @click="toggleSort('name')"
                                    >
                                        Name <span x-text="getSortIcon('name')"></span>
                                    </th>
                                    <th 
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                                        @click="toggleSort('email')"
                                    >
                                        Email <span x-text="getSortIcon('email')"></span>
                                    </th>
                                    <th 
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                                        @click="toggleSort('role')"
                                    >
                                        Role <span x-text="getSortIcon('role')"></span>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="item in paginatedItems" :key="item.id">
                                    <tr :class="{'bg-blue-50': selectedRows.includes(item.id)}">
                                        <td class="px-6 py-4">
                                            <input 
                                                type="checkbox" 
                                                class="rounded border-gray-300"
                                                :checked="selectedRows.includes(item.id)"
                                                @click="toggleRow(item.id)"
                                            >
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap" x-text="item.username"></td>
                                        <td class="px-6 py-4 whitespace-nowrap" x-text="item.email"></td>
                                        <td class="px-6 py-4 whitespace-nowrap" x-text="item.role_name"></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex space-x-2">
                                                <button @click="editItem(item)" class="text-blue-500 hover:text-blue-700">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                    </svg>
                                                </button>
                                                <button @click="window.location.href = `/dashboard?activeSection=user-profile&user_id=${item.id}`" class="text-yellow-500 hover:text-yellow-700">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 19.121A1.5 1.5 0 016.5 18H17.5a1.5 1.5 0 011.379 1.121l1.5 6A1.5 1.5 0 0118.5 27H5.5a1.5 1.5 0 01-1.379-1.879l1.5-6zM12 3a4 4 0 110 8 4 4 0 010-8z" />
                                                    </svg>
                                                </button>
                                                <template x-if="item.username !== 'admin'">
                                                    <button @click="deleteItem(item.id)" class="text-red-500 hover:text-red-700">
                                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                        </svg>
                                                    </button>
                                                </template>
                                                <!-- login as button -->
                                                <?php if(Users::can('DELETE')): ?>
                                                <button @click="window.location.href = `/login-as?user_id=${item.id}`" class="text-green hover:text-green-700">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
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
                            Showing <span x-text="((currentPage - 1) * (itemsPerPage === 'all' ? filteredItems.length : itemsPerPage)) + 1"></span> to 
                            <span x-text="itemsPerPage === 'all' ? filteredItems.length : Math.min(currentPage * itemsPerPage, filteredItems.length)"></span> of 
                            <span x-text="filteredItems.length"></span> entries
                        </div>
                        <div class="flex space-x-2" x-show="itemsPerPage !== 'all'">
                            <button 
                                @click="currentPage--" 
                                :disabled="currentPage === 1"
                                class="px-3 py-1 border rounded-md hover:bg-gray-100" 
                                :class="{'opacity-50 cursor-not-allowed': currentPage === 1}">
                                Previous
                            </button>
                            <div class="flex space-x-2" x-html="getPaginationArray()"></div>
                            <button 
                                @click="currentPage++" 
                                :disabled="currentPage >= totalPages"
                                class="px-3 py-1 border rounded-md hover:bg-gray-100"
                                :class="{'opacity-50 cursor-not-allowed': currentPage >= totalPages}">
                                Next
                            </button>
                        </div>
                    </div>
                    
                    <!-- Edit Modal -->
                    <div x-show="showEditModal" 
                        class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center"
                        @click.self="showEditModal = false">
                        <div class="bg-white rounded-lg p-6 w-full max-w-md" x-show="editingUser">
                            <h2 class="text-xl font-bold mb-4">Edit User</h2>
                            <form @submit.prevent="updateUser">
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Username</label>
                                        <input type="text" 
                                            :value="editingUser?.username"
                                            @input="editingUser.username = $event.target.value"
                                            class="mt-1 block p-2 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Email</label>
                                        <input type="email" 
                                            :value="editingUser?.email"
                                            @input="editingUser.email = $event.target.value"
                                            class="mt-1 block p-2 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Role</label>
                                        <!-- User Role -->
                                        <select :value="editingUser?.role_name"
                                                @change="editingUser.role_name = $event.target.value"
                                                class="mt-1 block p-2 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <template x-for="role in roles" :key="role.role_id">
                                                <option :value="role.role_name" 
                                                        :selected="editingUser?.role_name === role.role_name" 
                                                        x-text="role.role_name">
                                                </option>
                                            </template>
                                        </select>
                                    </div>
                                </div>
                                <div class="mt-6 flex justify-end space-x-3">
                                    <button type="button" 
                                            @click="showEditModal = false; editingUser = null"
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

                    <!-- Create User Modal -->
                    <div x-show="showCreateModal" 
                        class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center"
                        @click.self="showCreateModal = false">
                        <div class="bg-white rounded-lg p-6 w-full max-w-md">
                            <h2 class="text-xl font-bold mb-4">Create New User</h2>
                            <form @submit.prevent="createUser">
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Username</label>
                                        <input type="text" 
                                            x-model="newUser.username"
                                            required
                                            class="mt-1 block p-2 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Email</label>
                                        <input type="email" 
                                            x-model="newUser.email"
                                            required
                                            class="mt-1 block p-2 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Password</label>
                                        <input type="password" 
                                            x-model="newUser.password"
                                            required
                                            class="mt-1 block p-2 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Role</label>
                                        <select x-model="newUser.role_name"
                                                required
                                                class="mt-1 block p-2 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <option value="">Select a role</option>
                                            <template x-for="role in roles" :key="role.role_id">
                                                <option :value="role.role_id" x-text="role.role_name"></option>
                                            </template>
                                        </select>
                                    </div>
                                </div>
                                <div class="mt-6 flex justify-end space-x-3">
                                    <button type="button" 
                                            @click="showCreateModal = false"
                                            class="px-4 py-2 border rounded-md hover:bg-gray-100">
                                        Cancel
                                    </button>
                                    <button type="submit"
                                            class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600">
                                        Create User
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>