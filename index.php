<?php
session_start();
require 'db.php'; // include database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Save user info in session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];

        // Redirect based on role
        if ($user['role'] === 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: dashboard.php");
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
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: Arial, sans-serif;
      background: url('abc.png') no-repeat center center fixed;
      background-size: cover;
    }

    body::before {
      content: "";
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0,0,0,0.4);
      backdrop-filter: blur(5px);
      z-index: -1;
    }

    .admin-btn {
      position: fixed;
      top: 45px;
      right: 45px;
      background: #931925;
      color: white;
      padding: 10px 18px;
      border-radius: 6px;
      text-decoration: none;
      font-size: 14px;
      font-weight: bold;
      box-shadow: 0 4px 8px rgba(0,0,0,0.3);
      transition: 0.3s ease;
    }
    .admin-btn:hover {
      background: #0c4f97;
      transform: scale(1.05);
    }

    .logo {
      position: absolute;
      top: 20px;
      left: 20px;
      width: 70px;
      height: auto;
    }

    .page-title {
      text-align: center;
      font-size: 36px;
      font-weight: bold;
      color: white;
      margin-top: 120px;
      text-shadow: 2px 2px 6px rgba(0,0,0,0.6);
    }

    .login-card {
      background: rgba(255,255,255,0.9);
      width: 350px;
      margin: 40px auto;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 8px 16px rgba(0,0,0,0.3);
      text-align: center;
    }

    .login-card h2 {
      margin-bottom: 20px;
      color: #0c4f97;
    }

    .login-card input {
      width: 100%;
      padding: 12px;
      margin: 10px 0;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 14px;
    }

    .login-card button {
      width: 100%;
      padding: 12px;
      background: #931925;
      color: white;
      border: none;
      border-radius: 6px;
      font-size: 16px;
      cursor: pointer;
      transition: 0.3s;
    }

    .login-card button:hover {
      background: #0c4f97;
    }

    .login-card .register-link {
      margin-top: 15px;
      display: block;
      font-size: 14px;
      color: #0c4f97;
      text-decoration: none;
    }

    .login-card .register-link:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <a href="admin_login.php" class="admin-btn">Admin Login</a>
  <img src="logo.png" alt="Logo" class="logo">
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
