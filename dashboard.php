<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Extend session lifetime
ini_set('session.gc_maxlifetime', 86400); // 24 hours
ini_set('session.cookie_lifetime', 0);    // until browser closes

session_start();
require 'db.php';

// Only allow logged-in users
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Fetch logged-in user info
$stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$userName = $user ? $user['name'] : "Student";

// Fetch complaint stats for this student
$stmt = $pdo->prepare("SELECT status FROM complaints WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalSubmitted  = count($complaints);
$totalResolved   = count(array_filter($complaints, fn($c) => $c['status'] === "Resolved"));
$totalPending    = count(array_filter($complaints, fn($c) => $c['status'] === "Pending"));
$totalInProgress = count(array_filter($complaints, fn($c) => $c['status'] === "In Progress"));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Complaint Portal</title>
  <style>
    body {
      margin: 0;
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
    .logo { position: fixed; top: 20px; right: 20px; width: 80px; cursor: pointer; z-index: 1001; }
    .hamburger {
      position: fixed; top: 25px; left: 25px; font-size: 28px;
      color: white; cursor: pointer; z-index: 1001;
      transition: color 0.4s ease;
    }
    .hamburger.active { color: #0c4f97; }
    .sidebar {
      position: fixed; top: 0; left: -250px; width: 250px; height: 100%;
      background: rgba(255,255,255,0.95); box-shadow: 2px 0 10px rgba(0,0,0,0.3);
      padding-top: 80px; transition: left 0.4s ease; z-index: 1000;
    }
    .sidebar a { display: block; padding: 14px 20px; color: #0c4f97; text-decoration: none; font-weight: bold; }
    .sidebar a:hover { background: #0c4f97; color: white; }
    .sidebar.active { left: 0; }
    .main {
  margin-left: 40px;   /* ✅ add left margin */
  padding: 100px 40px;
  color: white;
  transition: margin-left 0.4s ease;
}
.main.shifted {
  margin-left: 290px;  /* ✅ adjusted so sidebar + margin look balanced */
}

    .welcome-card {
      background: rgba(255,255,255,0.9); color: #333; padding: 30px;
      border-radius: 12px; box-shadow: 0 8px 16px rgba(0,0,0,0.3);
      max-width: 600px; margin-bottom: 30px;
    }
    .welcome-card h2 { margin: 0; color: #0c4f97; }
    .welcome-card p { margin-top: 10px; font-size: 16px; }
    .stats { display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 40px; }
    .card {
      flex: 1; min-width: 180px; padding: 25px; border-radius: 12px; color: white;
      text-align: center; box-shadow: 0 6px 12px rgba(0,0,0,0.3); transition: transform 0.3s ease;
    }
    .card:hover { transform: translateY(-5px); }
    .card h3 { margin-bottom: 12px; font-size: 18px; font-weight: bold; }
    .card p { font-size: 28px; font-weight: bold; margin: 0; }
    .submitted { background: linear-gradient(135deg, #0c4f97, #1a73e8); }
    .resolved { background: linear-gradient(135deg, #28a745, #4caf50); }
    .pending { background: linear-gradient(135deg, #ff9800, #ffb74d); }
    .progress { background: linear-gradient(135deg, #17a2b8, #0c4f97); }
    .banner-card.full {
      background: linear-gradient(135deg, #0c4f97, #1a73e8); color: white; padding: 40px;
      border-radius: 12px; box-shadow: 0 8px 16px rgba(0,0,0,0.3); width: 100%; box-sizing: border-box;
    }
    .banner-card.full h2 { margin: 0 0 20px 0; font-size: 28px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
    .banner-card.full p { font-size: 18px; line-height: 1.7; margin: 0; }
  </style>
</head>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<body>
  <a href="dashboard.php"><img src="logo.png" alt="Logo" class="logo"></a>
  <div class="hamburger" onclick="toggleSidebar()">&#9776;</div>

  <div class="sidebar" id="sidebar">
    <a href="submit_complaint.php"><i class="fas fa-edit"></i> Submit Complaint</a>
    <a href="my_complaints.php"><i class="fas fa-user"></i> My Complaints</a>
    <a href="all_complaints.php"><i class="fas fa-users"></i> All Complaints</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>

  <div class="main" id="main">
    <div class="welcome-card">
      <h2>Welcome to Complaint Portal</h2>
      <p>Hello <?= htmlspecialchars($userName) ?>, you are now logged in. Use the menu to navigate through your options.</p>
    </div>

    <div class="stats">
      <div class="card submitted">
        <h3>Complaints Submitted</h3>
        <p><?= $totalSubmitted ?></p>
      </div>
      <div class="card pending">
        <h3>Pending Complaints</h3>
        <p><?= $totalPending ?></p>
      </div>
      <div class="card progress">
        <h3>Complaints In Progress</h3>
        <p><?= $totalInProgress ?></p>
      </div>
      <div class="card resolved">
        <h3>Complaints Resolved</h3>
        <p><?= $totalResolved ?></p>
      </div>
    </div>

    <div class="banner-card full">
      <h2>Raise Your Voice</h2>
      <p>
        You should raise your voice to solve the issues in our community, because silence only allows problems to grow unchecked...
      </p>
    </div>
  </div>

  <script>
    function toggleSidebar() {
      document.getElementById("sidebar").classList.toggle("active");
      document.getElementById("main").classList.toggle("shifted");
      document.querySelector(".hamburger").classList.toggle("active");
    }
    document.getElementById("main").addEventListener("click", function() {
      if (document.getElementById("sidebar").classList.contains("active")) {
        document.getElementById("sidebar").classList.remove("active");
        document.getElementById("main").classList.remove("shifted");
        document.querySelector(".hamburger").classList.remove("active");
      }
    });
  </script>
</body>
</html>
