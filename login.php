<?php
session_start();

// If the user is already logged in, redirect them to the home page
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Handle login form submission
$error = [];
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validate input
    if (empty($email)) {
        $error[] = "Email address is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error[] = "Please enter a valid email address";
    }

    if (empty($password)) {
        $error[] = "Password is required";
    }

    if (empty($error)) {
        try {
            // Fetch user by email
            require 'includes/db.php';
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                $error[] = "No account found with this email address";
            } elseif (!password_verify($password, $user['password'])) {
                $error[] = "Incorrect password";
                // Add brute force protection by waiting 1 second
                sleep(1);
            } else {
                // Debug information
                error_log('Login successful for email: ' . $email);
                error_log('User data: ' . print_r($user, true));
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['is_admin'] = $user['role'] == 'admin' ? 1 : 0;

                // Redirect based on admin status
                error_log('Session data: ' . print_r($_SESSION, true));
                
                if ($_SESSION['is_admin'] == 1) {
                    error_log('Redirecting to admin dashboard');
                    header("Location: admin/dashboard.php");
                    exit();
                } else {
                    error_log('Redirecting to index');
                    header("Location: index.php");
                    exit();
                }
            }
        } catch (Exception $e) {
            $error[] = "An error occurred: " . $e->getMessage();
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="form-container">
    <h2 class="form-heading">Login</h2>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <ul class="error-list">
                <?php foreach ($error as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
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