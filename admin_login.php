<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['role'] = 'admin';
        header("Location: admin_dashboard.php");
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
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: 'Segoe UI', Arial, sans-serif;
      background: url('abc.png') no-repeat center center fixed;
      background-size: cover;
    }
    body::before {
      content: "";
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0,0,0,0.5);
      backdrop-filter: blur(6px);
      z-index: -1;
    }
    .logo { position: fixed; top: 45px; left: 45px; width: 100px; cursor: pointer; }
    .page-title {
      text-align: center; font-size: 40px; font-weight: bold;
      color: #ffffff; margin-top: 120px;
      text-shadow: 2px 2px 10px rgba(0,0,0,0.7); letter-spacing: 2px;
    }
    .login-card {
      background: rgba(255,255,255,0.95);
      width: 400px; margin: 50px auto; padding: 35px;
      border-radius: 14px; box-shadow: 0 10px 25px rgba(0,0,0,0.4);
      text-align: center;
    }
    .login-card h2 { margin-bottom: 25px; color: #0c4f97; font-size: 24px; }
    .login-card input {
      width: 100%; padding: 14px; margin: 12px 0;
      border: 1px solid #ccc; border-radius: 8px; font-size: 15px;
    }
    .login-card button {
      width: 100%; padding: 14px;
      background: linear-gradient(135deg, #0c4f97, #1a73e8);
      color: white; border: none; border-radius: 8px;
      font-size: 17px; cursor: pointer; transition: 0.3s ease;
    }
    .login-card button:hover {
      background: linear-gradient(135deg, #1a73e8, #0c4f97);
      transform: scale(1.02);
    }
    .login-card .back-link {
      margin-top: 18px; display: block; font-size: 14px;
      color: #0c4f97; text-decoration: none;
    }
    .login-card .back-link:hover { text-decoration: underline; }
    .error { color: red; margin-bottom: 15px; }

    /* Hash generator button + form */
    .hash-btn {
      position: fixed; top: 20px; right: 20px;
      background: #0c4f97; color: white; padding: 10px 16px;
      border-radius: 6px; cursor: pointer; font-weight: bold;
      border: none; transition: 0.3s ease;
    }
    .hash-btn:hover { background:#1a73e8; }
  .hash-form {
  position: fixed;
  top: 60px;
  right: 20px;
  background: rgba(255,255,255,0.95);
  padding: 20px 20px 30px 20px; /* extra bottom padding */
  border-radius: 10px;
  box-shadow: 0 6px 12px rgba(0,0,0,0.3);
  width: 300px;
  box-sizing: border-box;
}

.hash-form input {
  width: 100%;
  padding: 10px;
  margin-top: 35px; /* ✅ pushes input below the X button */
  margin-bottom: 10px;
  box-sizing: border-box;
}

.close-btn {
  position: absolute;
  top: 8px;
  right: 10px;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: #ff4d4d; /* ✅ red circle background */
  color: #fff;
  font-size: 16px;
  font-weight: bold;
  line-height: 24px;
  text-align: center;
  cursor: pointer;
  box-shadow: 0 2px 6px rgba(0,0,0,0.3);
  transition: background 0.3s ease;
}

.close-btn:hover {
  background: #e60000; /* darker red on hover */
}

    .hash-result { margin-top: 10px; font-size: 13px; word-break: break-all; color:#0c4f97; }
  </style>
</head>
<body>

  <a href="index.php"><img src="logo.png" alt="Logo" class="logo"></a>
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
