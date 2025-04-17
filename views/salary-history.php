<div class="max-w-7xl mx-auto" x-data="{ searchTerm: '' }">
    <!-- Salary Records Table -->
    <div class="bg-white p-6 rounded-lg shadow">
        <!-- Search Input -->
        <div class="mb-6">
            <div class="relative max-w-xs">
                <input 
                    type="text"
                    x-model="searchTerm"
                    placeholder="Search by employee name..."
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <h2 class="text-xl font-bold mb-4">Salary Records</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Month</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Net Salary</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php
                    $query = "SELECT s.*, u.username 
                        FROM salaries s 
                        JOIN users u ON s.user_id = u.user_id 
                        ORDER BY s.created_at DESC";



                    $records = DB::fetchAll($query);
                    if (!$records) {
                        echo '<tr><td colspan="5" class="text-center py-4">No records found</td></tr>';
                        return;
                    } else{

                    foreach ($records as $record): ?>
                        <tr x-show="searchTerm === '' || '<?= strtolower(htmlspecialchars($record['username'])) ?>'.includes(searchTerm.toLowerCase())"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100">
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($record['id']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($record['username']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= date('F, Y', strtotime($record['month'])) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">$<?= number_format($record['net_salary'], 2) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button 
                                    class="text-blue-600 hover:text-blue-900"
                                    @click="$dispatch('open-modal', { 
                                        id: <?= $record['id'] ?>,
                                        username: '<?= htmlspecialchars($record['username']) ?>',
                                        basic_salary: <?= $record['basic_salary'] ?>,
                                        allowances: <?= $record['allowances'] ?>,
                                        deductions: <?= $record['deductions'] ?>,
                                        net_salary: <?= $record['net_salary'] ?>,
                                        month: '<?= date('F, Y', strtotime($record['month'])) ?>',
                                        payment_details: '<?= htmlspecialchars($record['payment_details']) ?>'
                                    })">
                                    View Details
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; } ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Salary Details Modal -->
    <div x-data="{ isOpen: false, salaryData: {} }" 
         @open-modal.window="isOpen = true; salaryData = $event.detail"
         @keydown.escape.window="isOpen = false">
        
        <!-- Modal Backdrop -->
        <div x-show="isOpen" 
             class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
        </div>

        <!-- Modal Content -->
        <div x-show="isOpen" 
             class="fixed inset-0 z-10 overflow-y-auto"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mx-4 sm:mt-0 sm:text-left w-full">
                                <h3 class="text-xl font-semibold leading-6 text-gray-900 mb-4">
                                    Salary Details for <span x-text="salaryData.username"></span>
                                </h3>
                                <div class="mt-2 space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Month:</span>
                                        <span x-text="salaryData.month" class="font-medium"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Basic Salary:</span>
                                        <span x-text="'$' + Number(salaryData.basic_salary).toFixed(2)" class="font-medium"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Allowances:</span>
                                        <span x-text="'$' + Number(salaryData.allowances).toFixed(2)" class="font-medium"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Deductions:</span>
                                        <span x-text="'$' + Number(salaryData.deductions).toFixed(2)" class="font-medium"></span>
                                    </div>
                                    <div class="flex justify-between border-t pt-2">
                                        <span class="text-gray-800 font-semibold">Net Salary:</span>
                                        <span x-text="'$' + Number(salaryData.net_salary).toFixed(2)" class="font-semibold"></span>
                                    </div>
                                    <div class="mt-4">
                                        <p class="text-gray-600">Payment Details:</p>
                                        <p x-text="salaryData.payment_details" class="mt-1"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button type="button" 
                                @click="isOpen = false"
                                class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>