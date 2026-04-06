<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

ini_set('session.gc_maxlifetime', 86400);
ini_set('session.cookie_lifetime', 86400);
session_start();
require 'db.php'; // ensure this connects to your DB

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Fetch admin by email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['role'] = 'admin';
        header("Location: admin_dashboard.php?just_logged_in=1");
        exit();
    } else {
        $error = "Invalid admin email or password.";
    }
}

// Handle hash generator form
$hashResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['plain_password'])) {
    $plain = trim($_POST['plain_password']);
    if ($plain !== '') {
        $hashResult = password_hash($plain, PASSWORD_DEFAULT);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login - Complaint Portal</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .login-card button {
      background: linear-gradient(135deg, #0c4f97, #1a73e8) !important;
    }
    .login-card button:hover {
      background: linear-gradient(135deg, #1a73e8, #0c4f97) !important;
      transform: scale(1.02);
    }
  </style>
</head>
<body class="bg-image">

  <a href="index.php"><img src="logo.png" alt="Logo" class="logo logo-left"></a>
  <div class="page-title">Admin Login</div>

  <!-- Hash generator button -->
  <button class="hash-btn" onclick="toggleHashForm()">Create Hash Password</button>

  <!-- Hash generator form -->
  <div class="hash-form" id="hashForm">
    <span class="close-btn" onclick="toggleHashForm()">X</span>
    <form method="POST" action="admin_login.php">
      <input type="text" name="plain_password" placeholder="Enter plain password" required>
      <button type="submit">Generate Hash</button>
    </form>
    <?php if($hashResult): ?>
      <div class="hash-result"><strong>Hash:</strong> <?= htmlspecialchars($hashResult) ?></div>
    <?php endif; ?>
  </div>

  <div class="login-card">
    <h2>Welcome, Admin</h2>
    <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
    <form action="admin_login.php" method="POST">
      <input type="email" name="email" placeholder="Admin Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Login as Admin</button>
    </form>
    <a href="index.php" class="back-link">Back to Student Login</a>
  </div>

  <script>
    function toggleHashForm() {
      const form = document.getElementById("hashForm");
      form.style.display = (form.style.display === "block") ? "none" : "block";
    }
  </script>
</body>
</html>
