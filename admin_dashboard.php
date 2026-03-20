<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

ini_set('session.gc_maxlifetime', 86400); 
ini_set('session.cookie_lifetime', 0);    

session_start();
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
  <style>
    body { margin:0; font-family:'Segoe UI', Arial, sans-serif; background:#f4f6f9; }
    .sidebar {
      position:fixed; top:0; left:-220px; width:220px; height:100%;
      background:#0c4f97; color:white; padding-top:60px;
      transition:left 0.4s ease; z-index:1000;
    }
    .sidebar.active { left:0; }
    .sidebar a { display:block; padding:14px 20px; color:white; text-decoration:none; font-weight:bold; }
    .sidebar a:hover, .sidebar a.active { background:#1a73e8; }
    .hamburger {
      position:fixed; top:20px; left:20px; font-size:28px;
      color:#0c4f97; cursor:pointer; z-index:1001;
      transition: color 0.3s ease;
    }
    .hamburger.active { color:white; }
    .main { margin:40px 60px 100px 60px; padding:40px; transition:margin-left 0.4s ease; background:#fff; border-radius:12px; box-shadow:0 6px 12px rgba(0,0,0,0.1); }
    .main.shifted { margin-left:280px; }
    .stats { display:flex; gap:20px; margin-bottom:30px; flex-wrap:wrap; }
    .card { flex:1; min-width:180px; padding:20px; border-radius:10px; color:white; text-align:center; box-shadow:0 6px 12px rgba(0,0,0,0.2); transition: transform 0.3s ease, filter 0.3s ease; cursor:pointer; }
    .card:hover { transform:translateY(-5px); filter:brightness(1.1); }
    .total { background:linear-gradient(135deg,#6c757d,#adb5bd); }
    .resolved { background:linear-gradient(135deg,#28a745,#4caf50); }
    .pending { background:linear-gradient(135deg,#ff9800,#ffb74d); }
    .progress { background:linear-gradient(135deg,#0c4f97,#1a73e8); }
    table { width:100%; border-collapse:collapse; background:white; border-radius:10px; overflow:hidden; margin-top:20px; }
    th, td { padding:12px; border-bottom:1px solid #ddd; text-align:left; }
    th { background:#0c4f97; color:white; }
    .badge { padding:6px 12px; border-radius:6px; color:white; font-weight:bold; display:inline-block; white-space:nowrap; }
    .badge.pending { background:#ff9800; }
    .badge.resolved { background:#28a745; }
    .badge.progress { background:#0c4f97; }
    form { display:flex; gap:10px; }
    select, button { padding:6px 10px; border-radius:6px; border:1px solid #ccc; }
    button { background:#0c4f97; color:white; cursor:pointer; }
    button:hover { background:#1a73e8; }
    .charts { display:flex; gap:30px; flex-wrap:wrap; margin-top:30px; }
    .chart-container { flex:1; min-width:300px; background:white; padding:20px; border-radius:10px; box-shadow:0 6px 12px rgba(0,0,0,0.2); }
  </style>
</head>
<body>
  <!-- Hamburger -->
  <div class="hamburger" onclick="toggleSidebar()">&#9776;</div>

  <!-- Sidebar -->
  <div class="sidebar" id="sidebar">
    <a href="admin_dashboard.php" class="active">Dashboard</a>
    <a href="admin_all_complaints.php">All Complaints</a>
    <a href="logout.php">Logout</a>
  </div>

  <!-- Main Content -->
  <div class="main" id="main">

    <!-- Stats Cards -->
    <div class="stats">
      <div class="card total" onclick="filterTable('All')"><h3>Show All</h3><p><?= $total ?></p></div>
      <div class="card pending" onclick="filterTable('Pending')"><h3>Pending</h3><p><?= $pending ?></p></div>
      <div class="card progress" onclick="filterTable('In Progress')"><h3>In Progress</h3><p><?= $progress ?></p></div>
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

    // Filter table rows by status
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

    // Charts
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
      type: 'pie',
      data: {
        labels: ['Pending','In Progress','Resolved'],
        datasets: [{
          data: [<?= $pending ?>, <?= $progress ?>, <?= $resolved ?>],
          backgroundColor: ['#ff9800','#0c4f97','#28a745']
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
