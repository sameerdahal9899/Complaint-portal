<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'session_config.php'; // Centralized session configuration
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Handle status update
if (isset($_POST['update_status']) && isset($_POST['complaint_id'])) {
    $newStatus = $_POST['status'];
    $complaintId = $_POST['complaint_id'];
    $stmt = $pdo->prepare("UPDATE complaints SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $complaintId]);
}

// Fetch complaints
$stmt = $pdo->query("SELECT c.*, u.name AS student_name 
                     FROM complaints c 
                     JOIN users u ON c.user_id = u.id 
                     ORDER BY c.id DESC");
$complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Stats
$total = count($complaints);
$resolved = count(array_filter($complaints, fn($c) => $c['status'] === "Resolved"));
$adminResolved = count(array_filter($complaints, fn($c) => $c['status'] === "Resolved from Admin"));
$pending = count(array_filter($complaints, fn($c) => $c['status'] === "Pending"));
$progress = count(array_filter($complaints, fn($c) => $c['status'] === "In Progress"));

// Category counts
$categories = [];
foreach ($complaints as $c) {
    $cat = $c['category'];
    if (!isset($categories[$cat])) $categories[$cat] = 0;
    $categories[$cat]++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <!-- Hamburger -->
  <div class="hamburger admin" id="hamburger">&#9776;</div>

  <!-- Sidebar -->
  <div class="sidebar admin" id="sidebar">
    <a href="admin_dashboard.php" class="active">Dashboard</a>
    <a href="admin_all_complaints.php">All Complaints</a>
    <a href="logout.php">Logout</a>
  </div>

  <!-- Main Content -->
  <div class="main admin-main" id="main">

    <!-- Stats Cards -->
    <div class="stats">
      <div class="card total" onclick="filterTable('All')"><h3>Show All</h3><p><?= $total ?></p></div>
      <div class="card pending" onclick="filterTable('Pending')"><h3>Pending</h3><p><?= $pending ?></p></div>
      <div class="card progress" onclick="filterTable('In Progress')"><h3>In Progress</h3><p><?= $progress ?></p></div>
      <div class="card admin-resolved" onclick="filterTable('Resolved from Admin')"><h3>Resolved from Admin</h3><p><?= $adminResolved ?></p></div>
      <div class="card resolved" onclick="filterTable('Resolved')"><h3>Resolved</h3><p><?= $resolved ?></p></div>
    </div>

    <!-- Complaints Table -->
    <h3>Complaints</h3>
    <table id="complaintsTable">
      <tr>
        <th>ID</th><th>Student</th><th>Category</th><th>Description</th><th>Status</th>
      </tr>
      <?php foreach($complaints as $row): ?>
      <tr data-status="<?= htmlspecialchars($row['status']) ?>">
        <td><?= htmlspecialchars($row['id']) ?></td>
        <td>
          <?php 
            if (!empty($row['anonymous']) && $row['anonymous'] == 1) {
                echo "Anonymous";
            } else {
                echo htmlspecialchars($row['student_name']);
            }
          ?>
        </td>
        <td><?= htmlspecialchars($row['category']) ?></td>
        <td><?= htmlspecialchars($row['description']) ?></td>
        <td>
          <?php if($row['status'] === 'Resolved'): ?>
            <span class="badge resolved">Resolved</span>
          <?php elseif($row['status'] === 'In Progress'): ?>
            <span class="badge progress">In Progress</span>
          <?php else: ?>
            <span class="badge pending">Pending</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>

    <!-- Charts -->
    <div class="charts">
      <div class="chart-container">
        <h3>Status Distribution</h3>
        <canvas id="statusChart"></canvas>
      </div>
      <div class="chart-container">
        <h3>Complaints by Category</h3>
        <canvas id="categoryChart"></canvas>
      </div>
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

    function filterTable(status) {
      const rows = document.querySelectorAll("#complaintsTable tr[data-status]");
      rows.forEach(row => {
        if (status === "All" || row.getAttribute("data-status") === status) {
          row.style.display = "";
        } else {
          row.style.display = "none";
        }
      });
    }

    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
      type: 'pie',
      data: {
        labels: ['Pending','In Progress','Resolved from Admin','Resolved'],
        datasets: [{
          data: [<?= $pending ?>, <?= $progress ?>, <?= $adminResolved ?>, <?= $resolved ?>],
          backgroundColor: ['#ff9800','#0c4f97','#2e7d32','#28a745']
        }]
      }
    });

    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    new Chart(categoryCtx, {
      type: 'bar',
      data: {
        labels: <?= json_encode(array_keys($categories)) ?>,
        datasets: [{
          label: 'Complaints',
          data: <?= json_encode(array_values($categories)) ?>,
          backgroundColor: '#1a73e8'
        }]
      },
      options: {
        scales: {
          y: { beginAtZero: true }
        }
      }
    });
  </script>
</body>
</html>
