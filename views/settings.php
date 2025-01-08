                <?php 
                $roles = Users::getAllRoles(); 
                $rolePermissions = [];
                foreach ($roles as $role) {
                    $rolePermissions[$role['role_id']] = Users::getRolePermissions($role['role_id']);
                }
                ?>
                <h1 class="text-3xl font-bold mb-8">Settings</h1>

                <!-- Form 1: Add Role -->
                <form id="addRoleForm" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="roleName">
                            Create New Role
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                               id="roleName" 
                               name="roleName"
                               type="text" 
                               placeholder="Enter role name"
                               required>
                    </div>
                    <div class="flex items-center justify-between">
                        <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" 
                                type="submit">
                            Create Role
                        </button>
                        <div id="addRoleMessage" class="ml-4 hidden">
                            <span class="text-green-500">✓ Role created successfully</span>
                        </div>
                    </div>
                </form>

                <!-- Role-Permission Matrix -->
                <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
                    <h2 class="text-xl font-bold mb-4">Role Permissions Matrix</h2>
                    <div class="overflow-x-auto">
                        <form id="rolePermissionsForm" class="space-y-4">
                            <table class="min-w-full bg-white border border-gray-300">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="px-6 py-3 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Roles</th>
                                        <?php
                                        // Get unique permissions for column headers
                                        $uniquePermissions = [];
                                        foreach ($rolePermissions as $permissions) {
                                            foreach ($permissions as $permission) {
                                                $uniquePermissions[$permission['permission_name']] = true;
                                            }
                                        }
                                        foreach (array_keys($uniquePermissions) as $permission): ?>
                                            <th class="px-6 py-3 border-b text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                <?php echo htmlspecialchars($permission); ?>
                                            </th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($roles as $role): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap border-b text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($role['role_name']); ?>
                                        </td>
                                        <?php 
                                        foreach (array_keys($uniquePermissions) as $permission): 
                                            $hasPermission = false;
                                            foreach ($rolePermissions[$role['role_id']] as $rolePermission) {
                                                if ($rolePermission['permission_name'] === $permission) {
                                                    $hasPermission = true;
                                                    break;
                                                }
                                            }
                                        ?>
                                            <td class="px-6 py-4 whitespace-nowrap border-b text-center">
                                                <input type="checkbox"
                                                    name="permissions[<?php echo $role['role_id']; ?>][<?php echo $permission; ?>]"
                                                    class="form-checkbox h-5 w-5 text-blue-600 rounded border-gray-300 focus:ring-blue-500"
                                                    <?php echo $hasPermission ? 'checked' : ''; ?>
                                                    <?php echo $role['role_id'] == 1 ? 'readonly onclick="return false;"' : ''; /*for admin*/ ?>>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <div class="flex justify-end mt-4">
                                <button type="submit" 
                                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                    Save Permission Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <script>
                document.getElementById('rolePermissionsForm').addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const formData = new FormData(e.target);
                    const permissions = {};
                    
                    for (const [key, value] of formData.entries()) {
                        const [_, roleId, permission] = key.match(/permissions\[(\d+)\]\[(.+)\]/);
                        if (!permissions[roleId]) {
                            permissions[roleId] = [];
                        }
                        permissions[roleId].push(permission);
                    }

                    try {
                        const response = await fetch('/api/roles', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ roles: Object.entries(permissions).map(([roleId, perms]) => ({
                                role_id: roleId,
                                permissions: perms
                            })) }),
                        });

                        if (response.ok) {
                            alert('Permissions updated successfully');
                        } else {
                            throw new Error('Failed to update permissions');
                        }
                    } catch (error) {
                        alert('Error updating permissions: ' + error.message);
                    }
                });

                // Add Role Form Handler
                document.getElementById('addRoleForm').addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const roleName = document.getElementById('roleName').value.trim();
                    const messageDiv = document.getElementById('addRoleMessage');
                    
                    try {
                        const response = await fetch('/api/roles/new', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ role_name: roleName }),
                        });

                        const result = await response.json();
                        
                        if (response.ok) {
                            messageDiv.innerHTML = '<span class="text-green-500">✓ Role created successfully</span>';
                            messageDiv.classList.remove('hidden');
                            document.getElementById('roleName').value = '';
                            
                            // Reload the page after 1 second to refresh the role matrix
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        } else {
                            throw new Error(result.error || 'Failed to create role');
                        }
                    } catch (error) {
                        messageDiv.innerHTML = `<span class="text-red-500">× ${error.message}</span>`;
                        messageDiv.classList.remove('hidden');
                    }

                    // Hide the message after 3 seconds
                    setTimeout(() => {
                        messageDiv.classList.add('hidden');
                    }, 3000);
                });
                </script>