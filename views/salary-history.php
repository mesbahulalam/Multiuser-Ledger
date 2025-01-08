<div class="max-w-7xl mx-auto">
    <!-- Salary Records Table -->
    <div class="bg-white p-6 rounded-lg shadow">
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
                        ORDER BY s.date_created DESC";

                    foreach (DB::fetchAll($query) as $record): ?>
                        <tr>
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
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>