<?php
session_start();

// If the user is already logged in, redirect them to the home page
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Handle login form submission
$error = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validate input
    if (empty($email) || empty($password)) {
        $error = "Please provide both email and password.";
    } else {
        // Fetch user by email
        require 'includes/db.php';
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['is_admin'] = $user['is_admin'] ?? 0;

            // Redirect based on admin status
            if ($_SESSION['is_admin'] == 1) {
                header("Location: admin/dashboard.php");
                exit;
            } else {
                header("Location: index.php");
                exit;
            }
        } else {
            $error = "Invalid credentials";
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="form-container">
    <h2 class="form-heading">Login</h2>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="login-form">
        <div class="form-group">
            <i class="fas fa-envelope input-icon"></i>
            <input type="email" name="email" class="form-input" placeholder="Email" required>
        </div>

        <div class="form-group">
            <i class="fas fa-lock input-icon"></i>
            <input type="password" name="password" class="form-input" placeholder="Password" required>
        </div>

        <button type="submit" class="form-button">Login</button>

        <div class="form-links">
            <a href="forgot_password.php" class="form-link">Forgot Password?</a>
            <a href="register.php" class="form-link">Don't have an account? Sign Up</a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>