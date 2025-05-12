<?php
session_start();
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header("Location: login.php");
//     exit;
// }

include 'includes/header.php';
include 'includes/db.php';

// Fetch all users
$stmt = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();
?>

<style>
:root {
    --primary-color: #4361ee;
    --success-color: #2ecc71;
    --warning-color: #f1c40f;
    --danger-color: #e74c3c;
    --info-color: #3498db;
    --text-primary: #2d3436;
    --text-secondary: #636e72;
    --background-light: #f8f9fa;
    --border-color: #e9ecef;
}

.users-container {
    padding: 2rem;
    background-color: var(--background-light);
    min-height: 100vh;
}

.content-wrapper {
    max-width: 1200px;
    margin: 0 auto;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.page-title {
    font-size: 2rem;
    color: var(--text-primary);
    margin: 0;
}

.users-grid {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.users-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.users-table th,
.users-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid var(--border-color);
}

.users-table th {
    background-color: var(--background-light);
    color: var(--text-primary);
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}

.users-table tbody tr {
    transition: background-color 0.2s ease;
}

.users-table tbody tr:hover {
    background-color: var(--background-light);
}

.user-name {
    font-weight: 500;
    color: var(--text-primary);
}

.user-email {
    color: var(--text-secondary);
}

.user-role {
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    display: inline-block;
}

.role-admin {
    background-color: #cce5ff;
    color: #004085;
}

.role-customer {
    background-color: #d1ecf1;
    color: #0c5460;
}

.user-date {
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.actions {
    display: flex;
    gap: 0.5rem;
}

.btn {
    padding: 0.5rem 1rem;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    font-size: 0.9rem;
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.btn-primary {
    background-color: var(--primary-color);
    color: white;
}

.btn-edit {
    background-color: var(--info-color);
    color: white;
}

.btn-delete {
    background-color: var(--danger-color);
    color: white;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    opacity: 0.9;
}

.empty-state {
    text-align: center;
    padding: 2rem;
    color: var(--text-secondary);
}

@media (max-width: 1024px) {
    .users-table {
        display: block;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
}

@media (max-width: 768px) {
    .users-container {
        padding: 1rem;
    }

    .page-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }

    .actions {
        flex-direction: column;
    }

    .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="users-container">
    <div class="content-wrapper">
        <div class="page-header">
            <h2 class="page-title">Manage Users</h2>
            <a href="add_user.php" class="btn btn-primary">+ Add New User</a>
        </div>

        <?php if (count($users) > 0): ?>
            <div class="users-grid">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($user['user_id'] ?? ''); ?></td>
                                <td>
                                    <div class="user-info">
                                        <div class="user-name"><?php echo htmlspecialchars($user['username'] ?? ''); ?></div>
                                        <div class="user-email"><?php echo htmlspecialchars($user['email'] ?? ''); ?></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="user-role <?php echo ($user['role'] ?? 'customer') === 'admin' ? 'role-admin' : 'role-customer'; ?>">
                                        <?php echo ucfirst($user['role'] ?? 'customer'); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a href="edit_user.php?id=<?php echo htmlspecialchars($user['user_id'] ?? ''); ?>" class="btn btn-edit">Edit</a>
                                        <a href="delete_user.php?id=<?php echo htmlspecialchars($user['user_id'] ?? ''); ?>" 
                                           class="btn btn-delete" 
                                           onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                            Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>No users found.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>