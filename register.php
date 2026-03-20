<?php
session_start();
require 'db.php'; // include database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // ✅ Default role set to student
    $role = "student";

    // Check if passwords match
    if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Check if email or username already exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        if ($stmt->rowCount() > 0) {
            $error = "Email or Username already exists!";
        } else {
            // Insert new user with default role student
            $stmt = $pdo->prepare("INSERT INTO users (name, email, username, phone, role, password) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $username, $phone, $role, $hashedPassword]);

            // Redirect to login page
            header("Location: index.php?register=success");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register - Complaint Portal</title>
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

    .logo {
      position: fixed;
      top: 45px;
      left: 45px;
      width: 100px;
      height: auto;
      cursor: pointer;
    }

    .page-title {
      text-align: center;
      font-size: 36px;
      font-weight: bold;
      color: white;
      margin-top: 120px;
      text-shadow: 2px 2px 6px rgba(0,0,0,0.6);
    }

    .register-card {
      background: rgba(255,255,255,0.9);
      width: 420px;
      margin: 40px auto;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 8px 16px rgba(0,0,0,0.3);
      text-align: center;
    }

    .register-card h2 {
      margin-bottom: 20px;
      color: #0c4f97;
    }

    .register-card input {
      width: 100%;
      padding: 12px;
      margin: 10px 0;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 14px;
    }

    .register-card button {
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

    .register-card button:hover {
      background: #0c4f97;
    }

    .register-card .login-link {
      margin-top: 15px;
      display: block;
      font-size: 14px;
      color: #0c4f97;
      text-decoration: none;
    }

    .register-card .login-link:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

  <a href="index.php">
    <img src="logo.png" alt="Logo" class="logo">
  </a>

  <div class="page-title">Register</div>

  <div class="register-card">
    <h2>Create Account</h2>

    <?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

    <form action="register.php" method="POST">
      <input type="text" name="name" placeholder="Full Name" required>
      <input type="email" name="email" placeholder="Email" required>
      <input type="text" name="username" placeholder="Username" required>
      <input type="tel" name="phone" 
       placeholder="Phone Number (must be 10-digits)" 
       pattern="\d{10}" 
       title="Phone number must be exactly 10 digits" 
       required>




      <!-- ✅ Removed role selection, defaults to student -->

      <input type="password" name="password" placeholder="Password" required>
      <input type="password" name="confirm_password" placeholder="Confirm Password" required>
      
      <button type="submit">Register</button>
    </form>
    <a href="index.php" class="login-link">Already have an account? Login</a>
  </div>

</body>
</html>
