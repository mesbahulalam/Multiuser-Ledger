<?php
$userId = $_GET['user_id'] ?? $_SESSION['user_id'];
$user = Users::getUserById($userId);
$user['role_name'] = Users::getUserRole($user['user_id'])['role_name'];


$attachments = new Attachments();
$user['profile_picture_url'] = $attachments->getAttachmentUrl($user['profile_picture']);

if (!$user) {
    echo "User not found";
    exit;
}
?>

<div class="container mx-auto px-4 py-8" x-data="userProfile">
    <div class="bg-white rounded-lg shadow-md p-6">
        <!-- Profile Header -->
        <div class="flex items-center space-x-4 mb-6">
            <div class="relative">
                <img 
                    :src="profileImageUrl || '/uploads/default-avatar.png'" 
                    class="w-24 h-24 rounded-full object-cover"
                    alt="Profile picture"
                >
                <?php if (Users::hasPermission($_SESSION['user_id'], 'UPDATE')): ?>
                <label class="absolute bottom-0 right-0 bg-blue-500 text-white rounded-full p-2 cursor-pointer hover:bg-blue-600">
                    <input 
                        type="file" 
                        class="hidden" 
                        accept="image/*"
                        @change="handleImageUpload($event)"
                    >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </label>
                <?php endif; ?>
            </div>
            <div>
                <h1 class="text-2xl font-bold"><?= htmlspecialchars($user['username']) ?></h1>
                <p class="text-gray-600"><?= htmlspecialchars($user['email']) ?></p>
                <p class="text-sm text-gray-500">Role: <?= htmlspecialchars($user['role_name']) ?></p>
            </div>
        </div>

        <!-- Profile Form -->
        <form @submit.prevent="saveProfile" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Personal Information -->
                <div class="space-y-4">
                    <h2 class="text-xl font-semibold">Personal Information</h2>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">First Name</label>
                        <input type="text" x-model="profile.first_name" 
                            class="mt-1 block w-full p-2 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Last Name</label>
                        <input type="text" x-model="profile.last_name" 
                            class="mt-1 block w-full p-2 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Date of Birth</label>
                        <input type="date" x-model="profile.dob" 
                            class="mt-1 block w-full p-2 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                        <input type="tel" x-model="profile.phone_number" 
                            class="mt-1 block w-full p-2 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>

                <!-- Account Settings -->
                <div class="space-y-4">
                    <h2 class="text-xl font-semibold">Account Settings</h2>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Username</label>
                        <input type="text" x-model="profile.username" 
                            <?php if (!Users::hasPermission($_SESSION['user_id'], 'UPDATE')): ?>readonly<?php endif; ?>
                            class="mt-1 block w-full p-2 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500
                            <?php if (!Users::hasPermission($_SESSION['user_id'], 'UPDATE')): ?> bg-gray-100<?php endif; ?>">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" x-model="profile.email" 
                            class="mt-1 block w-full p-2 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Role</label>
                        <?php if ($user['role_id'] == 1): ?>
                            <input type="text" 
                                value="<?= htmlspecialchars($user['role_name']) ?>" 
                                disabled
                                class="mt-1 block w-full p-2 rounded-md border-gray-300 shadow-sm bg-gray-100">
                        <?php else: ?>
                            <select x-model="profile.role_name" 
                                <?php if (!Users::hasPermission($_SESSION['user_id'], 'UPDATE')): ?>disabled<?php endif; ?>
                                class="mt-1 block w-full p-2 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500
                                <?php if (!Users::hasPermission($_SESSION['user_id'], 'UPDATE')): ?> bg-gray-100<?php endif; ?>">
                                <option value="">Select a role</option>
                                <template x-for="role in roles">
                                    <option :value="role.role_name" 
                                           x-text="role.role_name"
                                           :selected="role.role_name === profile.role_name"></option>
                                </template>
                            </select>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Salary</label>
                        <input type="number" x-model="profile.salary" step="0.01"
                            <?php if (!Users::hasPermission($_SESSION['user_id'], 'UPDATE')): ?>readonly<?php endif; ?>
                            class="mt-1 block w-full p-2 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500
                            <?php if (!Users::hasPermission($_SESSION['user_id'], 'UPDATE')): ?> bg-gray-100<?php endif; ?>">
                    </div>
                </div>
            </div>

            <div class="flex justify-end space-x-3">
                <button type="button" @click="resetForm" 
                    class="px-4 py-2 border rounded-md hover:bg-gray-100">
                    Reset
                </button>
                <button type="submit" 
                    class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                    Save Changes
                </button>
            </div>
        </form>
    </div>

    <!-- ID Card Upload Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mt-6">
        <h2 class="text-xl font-semibold mb-4">ID Card</h2>
        <div class="flex items-center space-x-4" x-data="idCardUpload">
            <div class="flex-1">
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center" 
                     x-show="!currentIdCard && !previewUrl"
                     @dragover.prevent="dragActive = true"
                     @dragleave.prevent="dragActive = false"
                     @drop.prevent="handleDrop($event)"
                     :class="{ 'border-blue-500 bg-blue-50': dragActive }"
                >
                    <label class="cursor-pointer block">
                        <input type="file" 
                               class="hidden" 
                               accept="image/*"
                               @change="handleIdCardUpload($event)">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p class="mt-1 text-sm text-gray-600">Drop your ID card here or click to upload</p>
                    </label>
                </div>
                <div class="relative" x-show="currentIdCard || previewUrl">
                    <img :src="previewUrl || currentIdCard" 
                         class="max-w-full h-auto rounded-lg shadow-sm">
                    <button @click="removeIdCard" 
                            class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-2 hover:bg-red-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('userProfile', () => ({
                profile: <?= json_encode($user) ?>,
                originalProfile: null,
                roles: <?= json_encode(Users::getAllRoles()) ?>,
                profileImageUrl: null,

                init() {
                    // Format date of birth to YYYY-MM-DD for date input
                    if (this.profile.dob) {
                        this.profile.dob = new Date(this.profile.dob).toISOString().split('T')[0];
                    }
                    
                    // Format phone number if needed
                    if (this.profile.phone_number) {
                        this.profile.phone_number = this.profile.phone_number.trim();
                    }
                    
                    // Format salary to number
                    if (this.profile.salary) {
                        this.profile.salary = Number(this.profile.salary);
                    }
                    
                    this.originalProfile = {...this.profile};
                    this.profileImageUrl = this.profile.profile_picture_url;
                },

                async handleImageUpload(event) {
                    const file = event.target.files[0];
                    if (!file) return;

                    const formData = new FormData();
                    formData.append('profile_picture', file);
                    formData.append('user_id', this.profile.user_id);

                    try {
                        const response = await fetch('/api/users/profile-picture', {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();
                        if (data.success) {
                            this.profileImageUrl = URL.createObjectURL(file);
                            alert('Profile picture updated successfully');
                        } else {
                            throw new Error(data.error);
                        }
                    } catch (error) {
                        alert('Failed to upload image: ' + error.message);
                    }
                },

                async saveProfile() {
                    try {
                        const formData = new FormData();
                        Object.keys(this.profile).forEach(key => {
                            formData.append(key, this.profile[key]);
                        });

                        const response = await fetch('/api/users/update', {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();
                        if (data.success) {
                            this.originalProfile = {...this.profile};
                            alert('Profile updated successfully');
                        } else {
                            throw new Error(data.error);
                        }
                    } catch (error) {
                        alert('Failed to update profile: ' + error.message);
                    }
                },

                resetForm() {
                    this.profile = {...this.originalProfile};
                }
            }))

            Alpine.data('idCardUpload', () => ({
                currentIdCard: null,
                previewUrl: null,
                dragActive: false,

                init() {
                    this.loadCurrentIdCard();
                },

                async loadCurrentIdCard() {
                    try {
                        const response = await fetch(`/api/users/id-card?user_id=<?= $userId ?>`);
                        const data = await response.json();
                        if (data.success) {
                            this.currentIdCard = data.url;
                        }
                    } catch (error) {
                        console.error('Failed to load ID card:', error);
                    }
                },

                handleDrop(event) {
                    this.dragActive = false;
                    const file = event.dataTransfer.files[0];
                    if (file && file.type.startsWith('image/')) {
                        this.handleIdCardUpload({ target: { files: [file] } });
                    } else {
                        alert('Please drop an image file');
                    }
                },

                async handleIdCardUpload(event) {
                    const file = event.target.files[0];
                    if (!file) return;

                    this.previewUrl = URL.createObjectURL(file);

                    const formData = new FormData();
                    formData.append('id_card', file);
                    formData.append('user_id', <?= $userId ?>);

                    try {
                        const response = await fetch('/api/users/id-card', {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();
                        if (data.success) {
                            this.currentIdCard = data.url;
                            alert('ID card uploaded successfully');
                        } else {
                            throw new Error(data.error);
                        }
                    } catch (error) {
                        alert('Failed to upload ID card: ' + error.message);
                        this.previewUrl = null;
                    }
                },

                removeIdCard() {
                    if (confirm('Are you sure you want to remove the ID card?')) {
                        this.previewUrl = null;
                        this.currentIdCard = null;
                        // Implement API call to remove ID card if needed
                    }
                }
            }));
        });
    </script>
</div>
