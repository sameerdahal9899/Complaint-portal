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

// Handle search filter
$search = $_GET['search'] ?? '';
if ($search !== '') {
    $stmt = $pdo->prepare("SELECT c.*, u.name 
                           FROM complaints c 
                           JOIN users u ON c.user_id = u.id 
                           WHERE c.title LIKE ? OR c.category LIKE ? OR c.description LIKE ? OR u.name LIKE ?
                           ORDER BY c.id DESC");
    $stmt->execute(["%$search%", "%$search%", "%$search%", "%$search%"]);
} else {
    $stmt = $pdo->query("SELECT c.*, u.name 
                         FROM complaints c 
                         JOIN users u ON c.user_id = u.id 
                         ORDER BY c.id DESC");
}
$complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>All Complaints - Complaint Portal</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-image">
  <a href="dashboard.php"><img src="logo.png" alt="Logo" class="logo"></a>
  <div class="hamburger" id="hamburger">&#9776;</div>

  <div class="sidebar" id="sidebar">
    <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="submit_complaint.php"><i class="fas fa-edit"></i> Submit Complaint</a>
    <a href="my_complaints.php"><i class="fas fa-user"></i> My Complaints</a>
    <a href="all_complaints.php" class="active"><i class="fas fa-users"></i> All Complaints</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>

  <div class="main" id="main">
    <div class="table-card">
      <h2>All Complaints</h2>

      <!-- Search Bar -->
      <form class="search-bar" method="GET" action="all_complaints.php">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search complaints...">
        <button type="submit">Search</button>
      </form>

      <?php if(empty($complaints)): ?>
        <p>No complaints found.</p>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Student</th>
              <th>Title</th>
              <th>Category</th>
              <th>Description</th>
              <th>Date Submitted</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($complaints as $c): ?>
              <tr>
                <td><?= htmlspecialchars($c['id']) ?></td>
                <td>
                  <?php 
                    if (!empty($c['anonymous']) && $c['anonymous'] == 1) {
                        echo "Anonymous";
                    } else {
                        echo htmlspecialchars($c['name']);
                    }
                  ?>
                </td>
                <td><?= htmlspecialchars($c['title']) ?></td>
                <td><?= htmlspecialchars($c['category']) ?></td>
                <td><?= htmlspecialchars($c['description']) ?></td>
                <td><?= htmlspecialchars($c['created_at'] ?? '') ?></td>
                <td>
                  <?php if($c['status'] === 'Resolved'): ?>
                    <span class="badge resolved">Resolved</span>
                  <?php elseif($c['status'] === 'Resolved from Admin'): ?>
                    <span class="badge admin-resolved">Resolved from Admin</span>
                  <?php elseif($c['status'] === 'In Progress'): ?>
                    <span class="badge progress">In Progress</span>
                  <?php else: ?>
                    <span class="badge pending">Pending</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>

  <script>
    function toggleSidebar(e) {
      if (e) e.stopPropagation();
      const sidebar = document.getElementById("sidebar");
      const main = document.getElementById("main");
      const hamburger = document.querySelector(".hamburger");
      sidebar.classList.toggle("active");
      hamburger.classList.toggle("active");
      main.classList.toggle("shifted");
      // Save sidebar state to localStorage
      localStorage.setItem("sidebarOpen", sidebar.classList.contains("active") ? "true" : "false");
    }

    function closeSidebar() {
      const sidebar = document.getElementById("sidebar");
      const main = document.getElementById("main");
      const hamburger = document.querySelector(".hamburger");
      sidebar.classList.remove("active");
      hamburger.classList.remove("active");
      main.classList.remove("shifted");
      localStorage.setItem("sidebarOpen", "false");
    }

    // Initialize everything after DOM is ready
    window.addEventListener("DOMContentLoaded", function() {
      // Clear localStorage if user just logged in
      const params = new URLSearchParams(window.location.search);
      if (params.has('just_logged_in')) {
        localStorage.removeItem("sidebarOpen");
        window.history.replaceState({}, document.title, window.location.pathname);
      }
      
      const hamburger = document.querySelector(".hamburger");
      const sidebar = document.getElementById("sidebar");
      const main = document.getElementById("main");
      
      // Attach hamburger click listener
      hamburger.addEventListener("click", toggleSidebar);
      
      // Close sidebar when clicking on sidebar links
      const sidebarLinks = sidebar.querySelectorAll("a");
      sidebarLinks.forEach(link => {
        link.addEventListener("click", closeSidebar);
      });
      
      // Restore sidebar state from localStorage
      if (localStorage.getItem("sidebarOpen") === "true") {
        sidebar.classList.add("active");
        hamburger.classList.add("active");
        main.classList.add("shifted");
      }
      
      // Close sidebar only when clicking on main content (not sidebar links)
      main.addEventListener("click", function(e) {
        // Don't close if click is on a sidebar link or inside sidebar
        if (!sidebar.contains(e.target) && !hamburger.contains(e.target)) {
          if (sidebar.classList.contains("active")) {
            closeSidebar();
          }
        }
      });
    });
  </script>
</body>
</html>
