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
      color: white;
      cursor: pointer; z-index: 1001;
      transition: color 0.3s ease;
    }
    .sidebar {
      position: fixed;
      top: 0;
      left: -250px;
      width: 250px;
      height: 100%;
      background: rgba(255,255,255,0.95);
      padding-top: 80px;
      transition: left 0.4s ease;
      z-index: 1000;
    }
    .sidebar.active { left: 0; }
    .sidebar a { display: block; padding: 14px 20px; color: #0c4f97; text-decoration: none; font-weight: bold; }
    .sidebar a.active { background: #0c4f97; color: white; border-left: 4px solid #1a73e8; }
    .main { margin-left: 0; padding: 100px 40px; color: white; transition: margin-left 0.4s ease; }
    .main.shifted { margin-left: 250px; }
    .table-card { background: rgba(255,255,255,0.95); color: #333; padding: 30px; border-radius: 12px; box-shadow: 0 8px 16px rgba(0,0,0,0.3); max-width: 1000px; margin: auto; }
    .table-card h2 { margin: 0 0 20px 0; color: #0c4f97; text-align: center; }
    .search-bar { margin-bottom: 20px; display: flex; gap: 10px; }
    .search-bar input { flex: 1; padding: 10px; border: 1px solid #ccc; border-radius: 8px; }
    .search-bar button { background: #0c4f97; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; }
    .search-bar button:hover { background: #1a73e8; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background: #0c4f97; color: white; }
    tr:hover { background: rgba(0,0,0,0.05); }
    .badge { padding: 6px 12px; border-radius: 20px; font-size: 13px; font-weight: bold; color: white; display:inline-block; white-space:nowrap; }
    .badge.pending { background: #ff9800; }
    .badge.resolved { background: #28a745; }
    .badge.progress { background: #0c4f97; }
  </style>
</head>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<body>
  <a href="dashboard.php"><img src="logo.png" alt="Logo" class="logo"></a>
  <div class="hamburger" onclick="toggleSidebar()">&#9776;</div>

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
    function toggleSidebar() {
      const sidebar = document.getElementById("sidebar");
      const main = document.getElementById("main");
      const hamburger = document.querySelector(".hamburger");

      sidebar.classList.toggle("active");
      main.classList.toggle("shifted");

      if (sidebar.classList.contains("active")) {
        hamburger.style.color = "#0c4f97"; 
      } else {
        hamburger.style.color = "white"; 
      }
    }

    document.getElementById("main").addEventListener("click", function() {
      const sidebar = document.getElementById("sidebar");
      const main = document.getElementById("main");
      const hamburger = document.querySelector(".hamburger");

            if (sidebar.classList.contains("active")) {
        sidebar.classList.remove("active");
        main.classList.remove("shifted");
        hamburger.style.color = "white"; 
      }
    });
  </script>
</body>
</html>
