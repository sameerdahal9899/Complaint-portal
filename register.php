<?php
require 'session_config.php'; // Centralized session configuration
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
  <link rel="stylesheet" href="style.css">
</head>
<body class="bg-image">

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
