<?php
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

// Handle status update
if (isset($_POST['update_status']) && isset($_POST['complaint_id'])) {
    $newStatus = $_POST['status'];
    $complaintId = $_POST['complaint_id'];
    $stmt = $pdo->prepare("UPDATE complaints SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $complaintId]);
    header("Location: admin_all_complaints.php?category=" . urlencode($_GET['category'] ?? 'All'));
    exit();
}

// Fetch all complaints
$stmt = $pdo->query("SELECT c.*, u.name 
                     FROM complaints c 
                     JOIN users u ON c.user_id = u.id 
                     ORDER BY c.id DESC");
$complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Define all categories
$allCategories = [
    "Academic","Facility","Administration","Canteen","Sports",
    "Library","Hostel","Transport","Examination","Others"
];

// Calculate counts for each category
$categories = [];
foreach ($allCategories as $cat) {
    $categories[$cat] = 0;
}
foreach ($complaints as $c) {
    if (isset($categories[$c['category']])) {
        $categories[$c['category']]++;
    }
}

// Handle filter (works for both category and status)
$filter = $_GET['category'] ?? '';
$filteredComplaints = $complaints;
if ($filter !== '' && $filter !== 'All') {
    $filteredComplaints = array_filter($complaints, fn($c) => $c['category'] === $filter || $c['status'] === $filter);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin - All Complaints</title>
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
    .capsules { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:20px; }
    .capsule {
      display:inline-block; background:#0c4f97; color:white;
      padding:8px 14px; border-radius:20px; font-size:14px;
      cursor:pointer; text-decoration:none;
      transition:background 0.2s ease;
      white-space:nowrap;
    }
    .capsule:hover { background:#1a73e8; }
    table { width:100%; border-collapse:collapse; background:white; border-radius:10px; overflow:hidden; }
    th, td { padding:12px; border-bottom:1px solid #ddd; text-align:left; }
    th { background:#0c4f97; color:white; }
    .badge { padding:6px 12px; border-radius:6px; color:white; font-weight:bold; display:inline-block; white-space:nowrap; }
    .badge.pending { background:#ff9800; }
    .badge.resolved { background:#28a745; }
    .badge.progress { background:#0c4f97; }
    form { display:flex; gap:6px; }
    select, button { padding:6px 10px; border-radius:6px; border:1px solid #ccc; }
    button { background:#0c4f97; color:white; cursor:pointer; }
    button:hover { background:#1a73e8; }

    .modal {
      display:none; position:fixed; z-index:2000; left:0; top:0;
      width:100%; height:100%; overflow:auto;
      background-color: rgba(0,0,0,0.8);
    }
    .modal-content {
      margin:5% auto; display:block; max-width:80%;
      border-radius:8px;
    }
    .close {
      position:absolute; top:20px; right:35px;
      color:#fff; font-size:40px; font-weight:bold;
      cursor:pointer;
    }
  </style>
</head>
<body>
  <div class="hamburger" onclick="toggleSidebar()">&#9776;</div>
  <div class="sidebar" id="sidebar">
    <a href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="admin_all_complaints.php" class="active"><i class="fas fa-users"></i> All Complaints</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>

  <div class="main" id="main">
    <h2>All Complaints (Admin)</h2>

    <!-- Category Capsules -->
    <div class="capsules">
      <a href="admin_all_complaints.php?category=All" class="capsule">
        All (<?= count($complaints) ?>)
      </a>
      <?php foreach($categories as $cat => $count): ?>
        <a href="admin_all_complaints.php?category=<?= urlencode($cat) ?>" class="capsule">
          <?= htmlspecialchars($cat) ?> (<?= $count ?>)
        </a>
      <?php endforeach; ?>

      <!-- ✅ Capsules for statuses -->
      <a href="admin_all_complaints.php?category=Pending" class="capsule">
        Pending (<?= count(array_filter($complaints, fn($c) => $c['status'] === 'Pending')) ?>)
      </a>
      <a href="admin_all_complaints.php?category=In Progress" class="capsule">
        In Progress (<?= count(array_filter($complaints, fn($c) => $c['status'] === 'In Progress')) ?>)
      </a>
      <a href="admin_all_complaints.php?category=Resolved" class="capsule">
        Resolved (<?= count(array_filter($complaints, fn($c) => $c['status'] === 'Resolved')) ?>)
      </a>
    </div>

    <!-- Complaints Table -->
    <table>
      <tr>
        <th>ID</th><th>Student</th><th>Title</th><th>Category</th><th>Description</th><th>Date</th><th>Status</th><th>Image</th><th>Action</th>
      </tr>
      <?php if(empty($filteredComplaints)): ?>
        <tr>
          <td colspan="9" style="text-align:center;">No complaints found for <?= htmlspecialchars($filter) ?></td>
        </tr>
      <?php else: ?>
        <?php foreach($filteredComplaints as $c): ?>
        <tr>
          <td><?= htmlspecialchars($c['id']) ?></td>
          <td><?= !empty($c['anonymous']) && $c['anonymous']==1 ? "Anonymous" : htmlspecialchars($c['name']) ?></td>
          <td><?= htmlspecialchars($c['title']) ?></td>
          <td><?= htmlspecialchars($c['category']) ?></td>
          <td><?= htmlspecialchars($c['description']) ?></td>
          <td><?= htmlspecialchars($c['created_at']) ?></td>
          <td>
            <?php if($c['status'] === 'Resolved'): ?>
              <span class="badge resolved">Resolved</span>
            <?php elseif($c['status'] === 'In Progress'): ?>
              <span class="badge progress">In Progress</span>
            <?php else: ?>
              <span class="badge pending">Pending</span>
            <?php endif; ?>
          </td>
                    <td>
            <?php if(!empty($c['image'])): ?>
              <a href="#" onclick="openModal('<?= htmlspecialchars($c['image']) ?>')">1</a>
            <?php else: ?>
              0
            <?php endif; ?>
          </td>
          <td>
            <form method="post">
              <input type="hidden" name="complaint_id" value="<?= $c['id'] ?>">
              <select name="status">
                <option value="Pending" <?= $c['status']=="Pending"?"selected":"" ?>>Pending</option>
                <option value="In Progress" <?= $c['status']=="In Progress"?"selected":"" ?>>In Progress</option>
                <option value="Resolved" <?= $c['status']=="Resolved"?"selected":"" ?>>Resolved</option>
              </select>
              <button type="submit" name="update_status">Update</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </table>
  </div>

  <!-- Modal for fullscreen image -->
  <div id="imageModal" class="modal">
    <span class="close" onclick="closeModal()">&times;</span>
    <img class="modal-content" id="modalImage">
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

    // ✅ Open modal with image
    function openModal(imageSrc) {
      const modal = document.getElementById("imageModal");
      const modalImg = document.getElementById("modalImage");
      modal.style.display = "block";
      modalImg.src = imageSrc;
    }

    // ✅ Close modal
    function closeModal() {
      document.getElementById("imageModal").style.display = "none";
    }

    // ✅ Close modal when clicking outside image
    window.onclick = function(event) {
      const modal = document.getElementById("imageModal");
      if (event.target === modal) {
        modal.style.display = "none";
      }
    }
  </script>
</body>
</html>
