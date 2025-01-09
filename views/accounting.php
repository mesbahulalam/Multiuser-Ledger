<h1 class="text-3xl font-bold mb-8">Accounting</h1>
        
        <?php if(!Users::hasPermission($_SESSION['user_id'], 'WRITE')): ?>
    
        <!-- Forms Row -->
        <div class="grid md:grid-cols-2 gap-6 mb-8">
            <!-- Income Form -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-xl font-bold mb-4">Add Income</h2>
                <form id="incomeForm" method="POST" @submit.prevent="submitIncomeForm" x-data="{ 
                    formError: '',
                    formSuccess: '',
                    submitting: false,
                    async submitIncomeForm() {
                        this.submitting = true;
                        this.formError = '';
                        this.formSuccess = '';
                        
                        const formData = new FormData(this.$el);
                        const data = Object.fromEntries(formData);
                        
                        try {
                            const response = await fetch('/api/finance/income/add', {
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
                                // Dispatch event to refresh table data
                                window.dispatchEvent(new CustomEvent('refresh-data'));
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
                        <input type="text" readonly value="<?= FinanceManager::getNextIncomeId() ?>" 
                            class="w-full p-2 border rounded bg-gray-100" placeholder="ID">
                        <input type="text" name="income_from" placeholder="Income From" class="w-full p-2 border rounded">
                        
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
                                    }
                                }
                            }
                        }">

                            <!-- Category input -->
                            <div x-data="makeAutocomplete(<?=str_replace('"', "'", json_encode(FinanceManager::getDistinctColumn('incomes', 'category')))?>)" 
                                class="relative mb-4">
                                <input 
                                    type="text" 
                                    name="category"
                                    autocomplete="off"
                                    x-model="search"
                                    @focus="showOptions = true"
                                    @blur="setTimeout(() => showOptions = false, 200)"
                                    @keydown.arrow-down.prevent="selectedIndex = Math.min(selectedIndex + 1, filteredOptions.length - 1)"
                                    @keydown.arrow-up.prevent="selectedIndex = Math.max(selectedIndex - 1, 0)"
                                    @keydown.enter.prevent="if(selectedIndex >= 0) { search = filteredOptions[selectedIndex]; showOptions = false }"
                                    placeholder="Category"
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

                            <input type="number" step="0.01" name="amount" placeholder="Amount" class="w-full p-2 mb-4 border rounded">

                            <!-- Account and Transaction Number (Income Form) -->
                            <div x-data="{ method: '' }">
                                <select name="method" x-model="method" class="w-full p-2 mb-4 border rounded bg-white">
                                    <option value="">Payment Method</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Bkash">Bkash</option>
                                    <option value="Nagad">Nagad</option>
                                    <option value="Credit Card">Credit Card</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Check">Check</option>
                                </select>
                                
                                <template x-if="method && method !== 'Cash'">
                                    <div class="space-y-4 mb-4">
                                        <input type="text" name="account_number" placeholder="Account Number" 
                                            class="w-full p-2 border rounded">
                                        <input type="text" name="transaction_number" placeholder="Transaction Number" 
                                            class="w-full p-2 border rounded">
                                    </div>
                                </template>
                            
                                <template x-if="method && method !== 'Cash'">
                                    <!-- Bank input -->
                                    <div x-data="makeAutocomplete(<?=str_replace('"', "'", json_encode(FinanceManager::getDistinctColumn('incomes', 'bank')))?>)" 
                                        class="relative mb-4">
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
                                </template>
                            </div>
                            <textarea name="notes" placeholder="Notes" class="w-full p-2 border rounded"></textarea>
                        </div>

                        <button 
                            type="submit" 
                            class="w-full bg-green-500 text-white p-2 rounded disabled:opacity-50"
                            :disabled="submitting"
                        >
                            <span x-show="!submitting">Add Income</span>
                            <span x-show="submitting">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
             
            
            <!-- Expense Form -->
            <script>
                // Shared autocomplete component
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
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-xl font-bold mb-4">Add Expense</h2>
                <form id="expenseForm" method="POST" @submit.prevent="submitExpenseForm" x-data="{
                    formError: '',
                    formSuccess: '',
                    submitting: false,
                    async submitExpenseForm() {
                        this.submitting = true;
                        this.formError = '';
                        this.formSuccess = '';
                        
                        const formData = new FormData(this.$el);
                        const data = Object.fromEntries(formData);
                        
                        <?php if(Users::hasPermission($_SESSION['user_id'], 'APPROVE')): ?>
                        // Add the selected user's id if available
                        if (this.$refs.userAutocomplete && this.$refs.userAutocomplete.__x.$data.selected) {
                            data.expense_by = this.$refs.userAutocomplete.__x.$data.selected.id;
                        }
                        <?php else: ?>
                        // For non-approvers, use their own ID
                        data.expense_by = <?= $_SESSION['user_id'] ?>;
                        <?php endif; ?>
                        
                        try {
                            const response = await fetch('/api/finance/expense/add', {
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
                                window.dispatchEvent(new CustomEvent('refresh-data'));
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
                        <input type="text" readonly value="<?= FinanceManager::getNextExpenseId() ?>" 
                            class="w-full p-2 border rounded bg-gray-100" placeholder="ID">
                        <!-- User Autocomplete -->
                        <?php if(Users::hasPermission($_SESSION['user_id'], 'APPROVE')): ?>
                            <!-- User Autocomplete - only shown to users with APPROVE permission -->
                            <div x-data="autocomplete({
                                apiUrl: '/api/users/namelist',
                                placeholder: 'Search users...',
                                label: 'Select a user'
                            })" 
                            x-init="loadItems()"
                            class="relative mb-4 z-10" x-ref="userAutocomplete">
                                <!-- Hidden input to store the selected user ID -->
                                <input type="hidden" name="expense_by" :value="selected?.id">
                                
                                <!-- Trigger Button -->
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
                                    class="absolute w-full mt-1 bg-white border rounded-md shadow-lg">
                                    <input
                                        x-model="search"
                                        @keydown.escape.prevent="open = false"
                                        @keydown.enter.prevent="selectedIndex >= 0 ? selectItem(selectedIndex) : (!filteredItems.length && addNewItem())"
                                        @keydown.down.prevent="navigateList('down')"
                                        @keydown.up.prevent="navigateList('up')"
                                        class="w-full p-2 border-b"
                                        :placeholder="placeholder"
                                        type="text">

                                    <!-- Loading State -->
                                    <div x-show="loading" class="p-4 text-center text-gray-500">Loading...</div>

                                    <!-- Options List -->
                                    <ul class="max-h-60 overflow-y-auto" x-show="!loading">
                                        <template x-for="(item, index) in filteredItems" :key="item.id">
                                            <li
                                                @click="selected = item; open = false"
                                                :class="{
                                                    'bg-blue-50': selected?.id === item.id,
                                                    'bg-gray-100': selectedIndex === index
                                                }"
                                                class="p-2 cursor-pointer hover:bg-gray-100">
                                                <div class="flex justify-between">
                                                    <span x-text="item.text"></span>
                                                    <span x-text="item.balance" class="text-gray-500"></span>
                                                </div>
                                            </li>
                                        </template>
                                        <li
                                            x-show="search && !filteredItems.length"
                                            @click="addNewItem()"
                                            :class="{'bg-gray-100': selectedIndex === 0 && !filteredItems.length}"
                                            class="p-2 cursor-pointer hover:bg-gray-100 text-blue-600">
                                            Add "<span x-text="search"></span>" as new item
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        <?php endif; ?>
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

                            <!-- Category input -->
                            <div x-data="makeAutocomplete(<?=str_replace('"', "'", json_encode(FinanceManager::getDistinctColumn('expenses', 'category')))?>)" 
                                class="relative mb-4">
                                <input 
                                    type="text" 
                                    name="category"
                                    autocomplete="off"
                                    x-model="search"
                                    @focus="showOptions = true"
                                    @blur="setTimeout(() => showOptions = false, 200)"
                                    @keydown.arrow-down.prevent="selectedIndex = Math.min(selectedIndex + 1, filteredOptions.length - 1)"
                                    @keydown.arrow-up.prevent="selectedIndex = Math.max(selectedIndex - 1, 0)"
                                    @keydown.enter.prevent="if(selectedIndex >= 0) { search = filteredOptions[selectedIndex]; showOptions = false }"
                                    placeholder="Category"
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

                            <!-- Purpose input -->
                            <div x-data="makeAutocomplete(<?=str_replace('"', "'", json_encode(FinanceManager::getDistinctColumn('expenses', 'purpose')))?>)" 
                                class="relative mb-4">
                                <input 
                                    type="text" 
                                    name="purpose"
                                    autocomplete="off"
                                    x-model="search"
                                    @focus="showOptions = true"
                                    @blur="setTimeout(() => showOptions = false, 200)"
                                    @keydown.arrow-down.prevent="selectedIndex = Math.min(selectedIndex + 1, filteredOptions.length - 1)"
                                    @keydown.arrow-up.prevent="selectedIndex = Math.max(selectedIndex - 1, 0)"
                                    @keydown.enter.prevent="if(selectedIndex >= 0) { search = filteredOptions[selectedIndex]; showOptions = false }"
                                    placeholder="Purpose"
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

                            <input type="number" step="0.01" name="amount" placeholder="Amount" class="w-full p-2 mb-4 border rounded">
                            
                            <!-- Account and Transaction Number (Expense Form) -->
                            <div x-data="{ method: '' }">
                                <select name="method" x-model="method" class="w-full p-2 mb-4 border rounded bg-white">
                                    <option value="">Payment Method</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Bkash">Bkash</option>
                                    <option value="Nagad">Nagad</option>
                                    <option value="Credit Card">Credit Card</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Check">Check</option>
                                </select>
                                
                                <template x-if="method && method !== 'Cash'">
                                    <div class="space-y-4 mb-4">
                                        <input type="text" name="account_number" placeholder="Account Number" 
                                            class="w-full p-2 border rounded">
                                        <input type="text" name="transaction_number" placeholder="Transaction Number" 
                                            class="w-full p-2 border rounded">
                                    </div>
                                </template>
    
                                <template x-if="method && method !== 'Cash'">
                                    <!-- Bank input -->
                                    <div x-data="makeAutocomplete(<?=str_replace('"', "'", json_encode(FinanceManager::getDistinctColumn('expenses', 'bank')))?>)" 
                                        class="relative mb-4">
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
                                </template>
                            </div>
                            <textarea name="notes" placeholder="Notes" class="w-full p-2 border rounded"></textarea>
                        </div>


                        <button 
                            type="submit" 
                            class="w-full bg-red-500 text-white p-2 rounded disabled:opacity-50"
                            :disabled="submitting"
                        >
                            <span x-show="!submitting">Add Expense</span>
                            <span x-show="submitting">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>

        </div>
    
        <?php endif; ?>
        <?php include 'views/accounting-tables.php'; ?>