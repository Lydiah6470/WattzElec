<?php
// Start session only if it's not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once 'includes/db.php';

// Check if the user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;

// Redirect to login if the user is not logged in (for restricted pages)
$public_pages = ['login.php', 'register.php', 'forgot_password.php']; // Add other public pages here
$current_page = basename($_SERVER['PHP_SELF']);

if (!$is_logged_in && !in_array($current_page, $public_pages)) {
    header("Location: login.php");
    exit;
}

// Fetch cart item count for logged-in users
$cart_count = 0;
try {
    if ($is_logged_in) {
        $stmt = $conn->prepare("SELECT COUNT(*) AS cart_count FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cart_count = $stmt->fetch()['cart_count'];
    }
} catch (PDOException $e) {
    // Log the error and set cart_count to 0
    error_log("Database error: " . $e->getMessage());
    $cart_count = 0;
}

// Fetch wishlist item count for logged-in users
$wishlist_count = 0;
try {
    if ($is_logged_in) {
        $stmt = $conn->prepare("SELECT COUNT(*) AS wishlist_count FROM wishlist WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $wishlist_count = $stmt->fetch()['wishlist_count'];
    }
} catch (PDOException $e) {
    // Log the error and set wishlist_count to 0
    error_log("Database error: " . $e->getMessage());
    $wishlist_count = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wattz Electronicz - Your One-Stop Shop</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/user.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #1B3B6F;">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">
      <i class="fas fa-store me-2"></i>Wattz Electonicz
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>" href="index.php">Home</a>
        </li>
        <li class="nav-item dropdown">
          <!-- Products Link -->
          <a class="nav-link dropdown-toggle <?php echo in_array(basename($_SERVER['PHP_SELF']), ['products.php', 'category.php']) ? 'active' : ''; ?>" 
             href="products.php" id="productsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Products
          </a>
          <ul class="dropdown-menu dropdown-menu-dark" style="background-color: #1A1A1A;" aria-labelledby="productsDropdown">
            <li><a class="dropdown-item" href="category.php?cat=phones">Phones</a></li>
            <li><a class="dropdown-item" href="category.php?cat=laptops">Laptops</a></li>
            <li><a class="dropdown-item" href="category.php?cat=audio">Audio</a></li>
            <li><a class="dropdown-item" href="category.php?cat=desktop">Desktop</a></li>
          </ul>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'about.php' ? 'active' : ''; ?>" href="about.php">About Us</a>
        </li>
      </ul>
      <form class="d-flex me-3" action="search.php" method="GET">
        <input class="form-control me-2" type="search" name="query" placeholder="Search products..." required>
        <button class="btn btn-outline-light" type="submit">
          <i class="fas fa-search"></i>
        </button>
      </form>
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" href="cart.php">
            <i class="fas fa-shopping-cart"></i> Cart
            <?php if ($cart_count > 0): ?>
              <span class="badge bg-danger"><?php echo $cart_count; ?></span>
            <?php endif; ?>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="wishlist.php">
            <i class="fas fa-heart"></i> Wishlist
            <?php if ($wishlist_count > 0): ?>
              <span class="badge bg-danger"><?php echo $wishlist_count; ?></span>
            <?php endif; ?>
          </a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
            <i class="fas fa-user"></i> My Account
          </a>
          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark" style="background-color: #1A1A1A;">
            <?php if ($is_logged_in): ?>
                <li><a class="dropdown-item" href="account.php">My Account</a></li>
                <li><a class="dropdown-item" href="wishlist.php">Wishlist</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a class="dropdown-item" href="login.php">Login</a></li>
                <li><a class="dropdown-item" href="register.php">Register</a></li>
            <?php endif; ?>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- Custom JavaScript for Products Link -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const productsDropdown = document.getElementById('productsDropdown');

    // Allow clicking the "Products" link to navigate to products.php
    productsDropdown.addEventListener('click', function (event) {
        if (!event.target.classList.contains('dropdown-item')) {
            window.location.href = productsDropdown.getAttribute('href');
        }
    });
});
</script>

<!-- Bootstrap JS and dependencies -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>