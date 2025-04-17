<?php
error_reporting(E_ALL & ~E_NOTICE);
class Users {

    // ===== CORE/INITIALIZATION METHODS =====
    
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    // ===== USER CRUD OPERATIONS =====
    
    public static function getAllUsers($page = 1, $limit = 0) {
        $users = self::getAllUserNames();
        foreach ($users as &$user) {
            $user['role'] = self::getUserRole($user['user_id']);
        }
        if ($limit > 0) {
            $offset = ($page - 1) * $limit;
            $users = array_slice($users, $offset, $limit);
        }
        return $users;
    }

    public static function getTotalUsers() {
        $sql = "SELECT COUNT(user_id) as total_users FROM users WHERE is_active = TRUE";
        return DB::fetchOne($sql)['total_users'];
    }

    public static function getTotalUsersRedundant() {
        return count(self::getAllUserNames());
    }

    public static function getAllUserNames(){
        return DB::fetchAll("SELECT user_id, username FROM users WHERE is_active = TRUE  ORDER BY username");
    }
    
    public static function createUser($username, $email, $password, $role_name = null) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $roleId = self::getRoleId($role_name ? $role_name : 'User');
        $sql = "INSERT INTO users (username, email, password, password_hash, role_id) VALUES (?, ?, ?, ?, ?)";
        if (DB::query($sql, [$username, $email, $password, $hashedPassword, $roleId])) {
            $userId = DB::fetchOne("SELECT LAST_INSERT_ID() as id")['id'];
            return $userId;
        }
        return false;
    }
    
    public static function getUserById($userId) {
        $user = DB::fetchOne("SELECT * FROM users WHERE user_id = ?", [$userId]);
        if ($user) {
            $user['role'] = self::getUserRole($userId);
        }
        return $user;
    }
    
    public static function updateUser($userId, $data) {
        $allowedFields = ['username', 'first_name', 'last_name', 'phone_number', 'email', 'profile_picture', 'dob', 'salary', 'role_id', 'is_active', 'address'];
        if (isset($data['role_name'])) {
            $data['role_id'] = self::getRoleId($data['role_name']);
            unset($data['role_name']);
        }
        $updates = array_intersect_key($data, array_flip($allowedFields));
        if (empty($updates)) {
            return false;
        }
        $placeholders = implode(', ', array_map(fn($key) => "$key = ?", array_keys($updates)));
        $params = array_values($updates);
        $params[] = $userId;
        return DB::query("UPDATE users SET $placeholders WHERE user_id = ?", $params);
    }
    
    public static function disableUser($userId) {
        return self::updateUser($userId, ['is_active' => false]);
    }

    public static function deleteUser($userId) {
        return DB::query("DELETE FROM users WHERE user_id = ?", [$userId]);
    }
    
    // ===== PASSWORD MANAGEMENT =====
    
    public static function verifyPassword($userId, $password) {
        $user = DB::fetchOne("SELECT password FROM users WHERE user_id = ?", [$userId]);
        return $user && password_verify($password, $user['password']);
    }
    
    public static function changePassword($userId, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password = ?, password_hash = ? WHERE user_id = ?";
        
        if (DB::query($sql, [$newPassword, $hashedPassword, $userId])) {
            return true;
        }
        return false;
    }
    
    // ===== AUTHENTICATION METHODS =====
    
    public static function login($username, $password, $remember = false) {
        $user = DB::fetchOne(
            "SELECT user_id, username, password_hash, is_active FROM users WHERE username = ?", 
            [$username]
        );
        
        if (!$user || !$user['is_active']) {
            return false;
        }
        
        if (password_verify($password, $user['password_hash'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];

            // Set cookies
            if ($remember) {
                $securityHash = self::generateSecurityHash($user['user_id'], $user['username'], $user['password_hash']);
                setcookie('user_id', $user['user_id'], time() + (86400 * 30), '/', '', true, true); // 30 days
                setcookie('auth_hash', $securityHash, time() + (86400 * 30), '/', '', true, true);
            }

            return true;
        }
        
        return false;
    }

    public static function loginAs($user_id){
        $user = DB::fetchOne(
            "SELECT user_id, username, password_hash, is_active FROM users WHERE user_id = ?", 
            [$user_id]
        );
        
        if (!$user || !$user['is_active']) {
            return false;
        }
        
        // Set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];

        return true;
    }
    
    public static function validateCookieAuth() {
        if (!isset($_COOKIE['user_id']) || !isset($_COOKIE['auth_hash'])) {
            return false;
        }

        $userId = $_COOKIE['user_id'];
        $savedHash = $_COOKIE['auth_hash'];

        $user = DB::fetchOne(
            "SELECT user_id, username, password_hash, is_active FROM users WHERE user_id = ?",
            [$userId]
        );

        if (!$user || !$user['is_active']) {
            return false;
        }

        $calculatedHash = self::generateSecurityHash($user['user_id'], $user['username'], $user['password_hash']);
        return hash_equals($savedHash, $calculatedHash);
    }

    public static function isLoggedIn() {
        return $_SESSION['user_id'] || self::validateCookieAuth();
    }

    public static function logout() {
        $userId = $_SESSION['user_id'] ?? ($_COOKIE['user_id'] ?? null);
        
        if ($userId) {
            // Clear session
            session_destroy();
            
            // Clear cookies
            setcookie('user_id', '', time() - 3600, '/', '', true, true);
            setcookie('auth_hash', '', time() - 3600, '/', '', true, true);
            
            return true;
        }
        return false;
    }

    public static function getCurrentUser() {
        if (!self::isLoggedIn()) {
            return null;
        }
        return self::getUserById($_SESSION['user_id']);
    }
    
    private static function generateSecurityHash($userId, $username, $passwordHash) {
        return hash('sha256', $userId . $username . $passwordHash . $_SERVER['HTTP_USER_AGENT']);
    }
    
    // ===== ROLE AND PERMISSION METHODS =====
    
    public static function createRole($roleName) {
        $sql = "INSERT INTO roles (role_name) VALUES (?)";
        if (DB::query($sql, [$roleName])) {
            $roleId = DB::fetchOne("SELECT LAST_INSERT_ID() as id")['id'];
            return $roleId;
        }
        return false;
    }

    public static function getAllRoles() {
        return DB::fetchAll("SELECT role_id, role_name FROM roles");
    }

    public static function getRolePermissions($roleId) {
        $sql = "SELECT p.permission_name 
                FROM role_permissions rp 
                JOIN permissions p ON rp.permission_id = p.permission_id 
                WHERE rp.role_id = ?";
        return DB::fetchAll($sql, [$roleId]);
    }

    public static function getRoleId($roleName) {
        $sql = "SELECT role_id FROM roles WHERE role_name = ?";
        $result = DB::fetchOne($sql, [$roleName]);
        return $result ? $result['role_id'] : null;
    }

    public static function getUserRole($userId) {
        $sql = "SELECT r.role_id, r.role_name FROM users u JOIN roles r ON u.role_id = r.role_id WHERE u.user_id = ?";
        return DB::fetchOne($sql, [$userId]);
    }

    public static function assignRole($userId, $roleId) {
        $sql = "UPDATE users SET role_id = ? WHERE user_id = ?";
        if (DB::query($sql, [$roleId, $userId])) {
            return true;
        }
        return false;
    }
    
    public static function hasPermission($userId, $permissionName) {
        $permissions = self::getRolePermissions(self::getUserRole($userId)['role_id']);
        return in_array($permissionName, array_column($permissions, 'permission_name'));
    }

    public static function hasPermissionRaw($userId, $permissionName) {
        $sql = "SELECT p.permission_name 
                FROM users u 
                JOIN role_permissions rp ON u.role_id = rp.role_id 
                JOIN permissions p ON rp.permission_id = p.permission_id 
                WHERE u.user_id = ? AND p.permission_name = ?";
        return DB::fetchOne($sql, [$userId, $permissionName]) !== false;
    }
    
    public static function can($action = 'READ') {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return false;
        }
        $permissions = self::getRolePermissions(self::getUserRole($userId)['role_id']);
        return in_array($action, array_column($permissions, 'permission_name'));
    }

    public static function exists($userId) {
        $sql = "SELECT COUNT(user_id) as count FROM users WHERE user_id = ?";
        return DB::fetchOne($sql, [$userId])['count'] > 0;
    }
}




// Initialize the UserManager class
// UserManager::init();

// Example usage of UserManager class

// Create a new user
// $newUserId = UserManager::createUser('john_doe', 'john@example.com', 'password123', 1);
// if ($newUserId) {
//     echo "User created successfully with ID: $newUserId\n";
// } else {
//     echo "Failed to create user\n";
// }

// // Get all users
// $users = UserManager::getAllUsers(1, 10);
// echo "All users:\n";
// print_r($users);

// // Get total number of users
// $totalUsers = UserManager::getTotalUsers();
// echo "Total active users: $totalUsers\n";

// // Get user by ID
// $user = UserManager::getUserById($newUserId);
// echo "User details:\n";
// print_r($user);

// // Update user
// $updateData = ['email' => 'john_new@example.com', 'is_active' => false];
// if (UserManager::updateUser($newUserId, $updateData)) {
//     echo "User updated successfully\n";
// } else {
//     echo "Failed to update user\n";
// }

// // Disable user
// if (UserManager::disableUser($newUserId)) {
//     echo "User disabled successfully\n";
// } else {
//     echo "Failed to disable user\n";
// }

// // Delete user
// if (UserManager::deleteUser($newUserId)) {
//     echo "User deleted successfully\n";
// } else {
//     echo "Failed to delete user\n";
// }

// // Login user
// if (UserManager::login('john_doe', 'password123', true)) {
//     echo "User logged in successfully\n";
// } else {
//     echo "Failed to login user\n";
// }

// // Check if user is logged in
// if (UserManager::isLoggedIn()) {
//     echo "User is logged in\n";
// } else {
//     echo "User is not logged in\n";
// }

// // Logout user
// if (UserManager::logout()) {
//     echo "User logged out successfully\n";
// } else {
//     echo "Failed to logout user\n";
// }