<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] == 1) {
    header("Location: login.php");
    exit();
}

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    
    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE user_id = ?");
    $stmt->execute([$username, $email, $_SESSION['user_id']]);
    $_SESSION['success'] = "Profile updated successfully";
    header("Location: account.php");
    exit();
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    
    if (password_verify($current_password, $user['password'])) {
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->execute([$new_hash, $_SESSION['user_id']]);
        $_SESSION['success'] = "Password changed successfully";
    } else {
        $_SESSION['error'] = "Current password is incorrect";
    }
    header("Location: account.php");
    exit();
}
?>

<?php include 'includes/header.php'; ?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-4">
            <!-- User Info Card -->
            <div class="card mb-4" style="background-color: #1a1a1a;">
                <div class="card-body text-center">
                    <i class="fas fa-user-circle fa-5x mb-3" style="color: var(--accent1);"></i>
                    <h5 class="card-title text-white"><?= htmlspecialchars($user['username'] ?? '') ?></h5>
                    <p class="card-text text-light"><?= htmlspecialchars($user['email'] ?? '') ?></p>
                    <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Profile Update Form -->
            <div class="card mb-4" style="background-color: #1a1a1a;">
                <div class="card-header text-white">Profile Information</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group mb-3">
                            <label class="text-light">Full Name</label>
                            <input type="text" name="username" class="form-control" 
                                   value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
                        </div>
                        <div class="form-group mb-3">
                            <label class="text-light">Email Address</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
            </div>

            <!-- Password Change Form -->
            <div class="card mb-4" style="background-color: #1a1a1a;">
                <div class="card-header text-white">Change Password</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group mb-3">
                            <label class="text-muted">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="form-group mb-3">
                            <label class="text-muted">New Password</label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>