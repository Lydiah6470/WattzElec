<?php
session_start();
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header("Location: login.php");
//     exit;
// }

include 'includes/header.php';
include 'includes/db.php';

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch user details
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];

    // Update password only if a new one is provided
    if (!empty($password)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, password=?, role=? WHERE user_id=?");
        $stmt->execute([$username, $email, $password_hash, $role, $user_id]);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, role=? WHERE user_id=?");
        $stmt->execute([$username, $email, $role, $user_id]);
    }

    header("Location: users.php");
    exit;
}
?>

<div class="form-container">
    <h2>Edit User</h2>

    <form method="POST" class="user-form">
        <div class="form-group">
            <label for="username">Full Name:</label>
            <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label for="password">New Password (Leave blank to keep current):</label>
            <input type="password" name="password" id="password">
        </div>

        <div class="form-group">
            <label for="role">Role:</label>
            <select name="role" id="role" required>
                <option value="admin" <?php echo ($user['role'] ?? 'customer') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                <option value="customer" <?php echo ($user['role'] ?? 'customer') === 'customer' ? 'selected' : ''; ?>>Customer</option>
            </select>
        </div>

        <button type="submit" class="btn-submit">Update User</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>