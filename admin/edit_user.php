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
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    die("User not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    // Update password only if a new one is provided
    if (!empty($password)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET name=?, email=?, password=?, is_admin=? WHERE id=?");
        $stmt->execute([$name, $email, $password_hash, $is_admin, $user_id]);
    } else {
        $stmt = $conn->prepare("UPDATE users SET name=?, email=?, is_admin=? WHERE id=?");
        $stmt->execute([$name, $email, $is_admin, $user_id]);
    }

    header("Location: users.php");
    exit;
}
?>

<div class="form-container">
    <h2>Edit User</h2>

    <form method="POST" class="user-form">
        <div class="form-group">
            <label for="name">Name:</label>
            <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
        </div>

        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>

        <div class="form-group">
            <label for="password">New Password (Leave blank to keep current):</label>
            <input type="password" name="password" id="password">
        </div>

        <div class="form-group">
            <label for="is_admin">Role:</label>
            <select name="is_admin" id="is_admin" required>
                <option value="1" <?php echo ($user['is_admin'] == 1) ? 'selected' : ''; ?>>Admin</option>
                <option value="0" <?php echo ($user['is_admin'] == 0) ? 'selected' : ''; ?>>Customer</option>
            </select>
        </div>

        <button type="submit" class="btn-submit">Update User</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>