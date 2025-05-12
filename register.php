<?php 
require 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['name']; // Using name field as username for now
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->execute([$username, $email, $password]);

    echo "<script>alert('Account created successfully!');</script>";
    header("Refresh: 0; url=login.php");
}
?>

<?php include 'includes/header.php'; ?>

<div class="form-container">
  <h2 class="form-heading">Register</h2>
  
  <form method="POST">
    <div class="form-group">
      <i class="fas fa-user input-icon"></i>
      <input type="text" name="name" class="form-input" placeholder="Full Name" required>
    </div>

    <div class="form-group">
      <i class="fas fa-envelope input-icon"></i>
      <input type="email" name="email" class="form-input" placeholder="Email Address" required>
    </div>

    <div class="form-group password-group">
      <i class="fas fa-lock input-icon"></i>
      <input type="password" name="password" class="form-input" placeholder="Password" required>
      <span class="password-toggle" onclick="togglePassword('password')">Show</span>
    </div>

    <div class="form-group password-group">
      <i class="fas fa-lock input-icon"></i>
      <input type="password" name="confirm_password" class="form-input" placeholder="Confirm Password" required>
      <span class="password-toggle" onclick="togglePassword('confirm_password')">Show</span>
    </div>

    <div class="agree-checkbox">
        <input type="checkbox" id="agree" required>
        <label for="agree">
            I agree to the 
            <a href="terms.php" target="_blank">Terms & Conditions</a>
        </label>
    </div>

    <button type="submit" class="form-button">Create Account</button>
    <a href="login.php" class="form-link">Already have an account? Login</a>
  </form>
</div>

<script>
function togglePassword(field) {
  const input = document.querySelector(`[name="${field}"]`);
  const toggle = document.querySelector(`[onclick="togglePassword('${field}')"]`);
  input.type = input.type === 'password' ? 'text' : 'password';
  toggle.textContent = input.type === 'password' ? 'Show' : 'Hide';
}
</script>
