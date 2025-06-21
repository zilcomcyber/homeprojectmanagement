<?php
$page_title = "Manage Admins";
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_role('super_admin'); // Only super admin can manage other admins

$current_admin = get_current_admin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'create_admin') {
            $name = sanitize_input($_POST['name'] ?? '');
            $email = sanitize_input($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? '';
            
            if (empty($name) || empty($email) || empty($password) || empty($role)) {
                $error = 'All fields are required';
            } elseif (!in_array($role, ['admin', 'super_admin'])) {
                $error = 'Invalid role selected';
            } elseif (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters long';
            } else {
                try {
                    // Check if email already exists
                    $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
                    $stmt->execute([$email]);
                    
                    if ($stmt->fetch()) {
                        $error = 'Email already exists';
                    } else {
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO admins (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$name, $email, $password_hash, $role]);
                        
                        $success = 'Admin created successfully';
                    }
                } catch (Exception $e) {
                    error_log("Create admin error: " . $e->getMessage());
                    $error = 'An error occurred while creating admin';
                }
            }
        } elseif ($action === 'update_status') {
            $admin_id = intval($_POST['admin_id'] ?? 0);
            $is_active = intval($_POST['is_active'] ?? 0);
            
            if ($admin_id == $current_admin['id']) {
                $error = 'Cannot change your own status';
            } else {
                try {
                    $stmt = $pdo->prepare("UPDATE admins SET is_active = ? WHERE id = ?");
                    $stmt->execute([$is_active, $admin_id]);
                    
                    $success = 'Admin status updated successfully';
                } catch (Exception $e) {
                    error_log("Update admin status error: " . $e->getMessage());
                    $error = 'An error occurred while updating admin status';
                }
            }
        } elseif ($action === 'update_role') {
            $admin_id = intval($_POST['admin_id'] ?? 0);
            $role = $_POST['role'] ?? '';
            
            if ($admin_id == $current_admin['id']) {
                $error = 'Cannot change your own role';
            } elseif (!in_array($role, ['admin', 'super_admin', 'viewer'])) {
                $error = 'Invalid role selected';
            } else {
                try {
                    $stmt = $pdo->prepare("UPDATE admins SET role = ? WHERE id = ?");
                    $stmt->execute([$role, $admin_id]);
                    
                    $success = 'Admin role updated successfully';
                } catch (Exception $e) {
                    error_log("Update admin role error: " . $e->getMessage());
                    $error = 'An error occurred while updating admin role';
                }
            }
        }
    }
}

// Get all admins
$stmt = $pdo->prepare("SELECT id, name, email, role, is_active, created_at, last_login FROM admins ORDER BY created_at DESC");
$stmt->execute();
$admins = $stmt->fetchAll();

ob_start();
?>

<!-- Messages -->
<?php if (isset($success)): ?>
    <div class="mb-6 rounded-md bg-green-50 dark:bg-green-900 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-check-circle text-green-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-green-700 dark:text-green-300"><?php echo htmlspecialchars($success); ?></p>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="mb-6 rounded-md bg-red-50 dark:bg-red-900 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-circle text-red-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-red-700 dark:text-red-300"><?php echo htmlspecialchars($error); ?></p>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Create New Admin -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Create New Admin</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Add a new administrator to the system</p>
    </div>
    <form method="POST" class="p-6">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <input type="hidden" name="action" value="create_admin">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name *</label>
                <input type="text" name="name" required 
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email *</label>
                <input type="email" name="email" required 
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password *</label>
                <input type="password" name="password" required minlength="6"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Minimum 6 characters</p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Role *</label>
                <select name="role" required 
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    <option value="">Select Role</option>
                    <option value="admin">Admin</option>
                    <option value="super_admin">Super Admin</option>
                </select>
            </div>
        </div>
        
        <div class="mt-6 flex justify-end">
            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                <i class="fas fa-plus mr-2"></i>
                Create Admin
            </button>
        </div>
    </form>
</div>

<!-- Admins List -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">All Administrators</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Manage system administrators</p>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Admin</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Role</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Last Login</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <?php foreach ($admins as $admin): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    <?php echo htmlspecialchars($admin['name']); ?>
                                    <?php if ($admin['id'] == $current_admin['id']): ?>
                                        <span class="text-xs text-blue-600 dark:text-blue-400">(You)</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($admin['email']); ?></div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($admin['id'] != $current_admin['id']): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                    <input type="hidden" name="action" value="update_role">
                                    <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                    <select name="role" onchange="this.form.submit()" 
                                            class="text-sm border border-gray-300 dark:border-gray-600 rounded-md px-2 py-1 dark:bg-gray-700 dark:text-white">
                                        <option value="viewer" <?php echo $admin['role'] === 'viewer' ? 'selected' : ''; ?>>Viewer</option>
                                        <option value="admin" <?php echo $admin['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                        <option value="super_admin" <?php echo $admin['role'] === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                                    </select>
                                </form>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                    <?php echo ucfirst(str_replace('_', ' ', $admin['role'])); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($admin['id'] != $current_admin['id']): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                    <select name="is_active" onchange="this.form.submit()" 
                                            class="text-sm border border-gray-300 dark:border-gray-600 rounded-md px-2 py-1 dark:bg-gray-700 dark:text-white">
                                        <option value="1" <?php echo $admin['is_active'] ? 'selected' : ''; ?>>Active</option>
                                        <option value="0" <?php echo !$admin['is_active'] ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </form>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                    Active
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <?php echo $admin['last_login'] ? format_date($admin['last_login']) : 'Never'; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <span class="text-xs">Member since <?php echo format_date($admin['created_at']); ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
$additional_js = ['../assets/js/admin.js'];
include 'layout.php';
?>
