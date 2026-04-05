<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'session_config.php'; // Centralized session configuration
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
$totalAdminResolved = count(array_filter($complaints, fn($c) => $c['status'] === "Resolved from Admin"));
$totalPending    = count(array_filter($complaints, fn($c) => $c['status'] === "Pending"));
$totalInProgress = count(array_filter($complaints, fn($c) => $c['status'] === "In Progress"));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Complaint Portal</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-image">
  <a href="dashboard.php"><img src="logo.png" alt="Logo" class="logo"></a>
  <div class="hamburger" onclick="toggleSidebar()">&#9776;</div>

  <div class="sidebar" id="sidebar">
    <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
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
      <div class="card admin-resolved">
        <h3>Admin Resolved</h3>
        <p><?= $totalAdminResolved ?></p>
      </div>
      <div class="card resolved">
        <h3>Complaints Resolved</h3>
        <p><?= $totalResolved ?></p>
      </div>
    </div>

    <div class="banner-card full">
      <h2>Raise Your Voice</h2>
      <p>
Raising your voice against issues in our community is not just a choice—it is a responsibility. 
When problems are ignored and people remain silent, those issues gradually become more severe, 
affecting a larger number of individuals and creating an environment where injustice and dissatisfaction thrive. 
In the context of my project, the Complaint Portal, this idea becomes even more significant. 
The system is designed to empower students by giving them a platform where they can safely and easily express their concerns, 
report problems, and demand necessary action. Instead of suppressing their voices due to fear, hesitation, or lack of opportunity, 
users are encouraged to speak up through a structured and transparent system. By submitting complaints, tracking their status, 
and ensuring accountability from the administration, the portal transforms silence into action. 
It promotes a culture where every concern is acknowledged, every voice matters, and no issue is left unresolved. 
Ultimately, this project reflects the belief that meaningful change begins when individuals choose to express themselves rather than remain silent, 
because only then can problems be identified, addressed, and resolved effectively.
      </p>
    </div>
  </div>

  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById("sidebar");
      const hamburger = document.querySelector(".hamburger");
      sidebar.classList.toggle("active");
      hamburger.classList.toggle("active");
      // Save sidebar state to localStorage
      localStorage.setItem("sidebarOpen", sidebar.classList.contains("active") ? "true" : "false");
    }

    // Restore sidebar state on page load
    window.addEventListener("DOMContentLoaded", function() {
      const sidebar = document.getElementById("sidebar");
      const hamburger = document.querySelector(".hamburger");
      if (localStorage.getItem("sidebarOpen") === "true") {
        sidebar.classList.add("active");
        hamburger.classList.add("active");
      }
    });

    // Close sidebar when clicking outside of it
    document.addEventListener("click", function(e) {
      const sidebar = document.getElementById("sidebar");
      const hamburger = document.querySelector(".hamburger");
      
      // If sidebar is open and click is outside sidebar and not on hamburger
      if (sidebar.classList.contains("active") && 
          !sidebar.contains(e.target) && 
          !hamburger.contains(e.target)) {
        sidebar.classList.remove("active");
        hamburger.classList.remove("active");
        localStorage.setItem("sidebarOpen", "false");
      }
    });
  </script>
  </script>
</body>
</html>
