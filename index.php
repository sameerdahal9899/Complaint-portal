<?php
require 'session_config.php'; // Centralized session configuration
require 'db.php'; // include database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Prevent session fixation and save user info in session
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];

        // Redirect based on role
        if ($user['role'] === 'admin') {
            header("Location: admin_dashboard.php?just_logged_in=1");
        } else {
            header("Location: dashboard.php?just_logged_in=1");
        }
        exit();
    } else {
        $error = "Invalid email or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Complaint Portal</title>
  <link rel="stylesheet" href="style.css">
</head>
<body class="bg-image">
  <a href="admin_login.php" class="admin-btn">Admin Login</a>
  <img src="logo.png" alt="Logo" class="logo logo-left">
  <div class="page-title">Complaint Portal</div>

  <div class="login-card">
    <h2>Login</h2>

    <!-- Show error if any -->
    <?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <?php if(isset($_GET['register']) && $_GET['register'] === 'success') echo "<p style='color:green;'>Registration successful! Please log in.</p>"; ?>
    <?php if(isset($_GET['logout']) && $_GET['logout'] === 'success') echo "<p style='color:green;'>You have logged out successfully.</p>"; ?>

    <form action="index.php" method="POST">
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Login</button>
    </form>
    <a href="register.php" class="register-link">Don't have an account? Register</a>
  </div>
</body>
</html>
